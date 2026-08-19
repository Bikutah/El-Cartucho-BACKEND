<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    // Constantes de estado de pago
    public const ESTADO_PAGO_PENDIENTE   = 'pendiente';
    public const ESTADO_PAGO_PAGADO      = 'pagado';
    public const ESTADO_PAGO_RECHAZADO   = 'rechazado';
    public const ESTADO_PAGO_EXPIRADO    = 'expirado';
    public const ESTADO_PAGO_REEMBOLSADO = 'reembolsado';

    // Constantes de estado de envío
    public const ESTADO_ENVIO_SIN_PREPARAR = 'sin_preparar';
    public const ESTADO_ENVIO_PREPARANDO   = 'preparando';
    public const ESTADO_ENVIO_ENVIADO      = 'enviado';
    public const ESTADO_ENVIO_ENTREGADO    = 'entregado';
    public const ESTADO_ENVIO_DEVUELTO      = 'devuelto';

    protected $fillable = [
        'user_id',
        'firebase_uid',
        'email',
        'domicilio',
        'ciudad',
        'codigo_postal',
        'costo_envio',
        'zona_envio_id',
        'estado',
        'estado_pago',
        'estado_envio',
        'transportista',
        'tracking_numero',
        'enviado_at',
        'entregado_at',
        'mercado_pago_id',
        'mercado_pago_preference_id',
        'mercado_pago_init_point',
        'total',
        'expira_at',
    ];

    protected $casts = [
        'costo_envio'  => 'decimal:2',
        'expira_at'    => 'datetime',
        'enviado_at'   => 'datetime',
        'entregado_at' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function zonaEnvio()
    {
        return $this->belongsTo(ZonaEnvio::class, 'zona_envio_id');
    }

    public function historialEstados()
    {
        return $this->hasMany(PedidoHistorialEstado::class, 'pedido_id')->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Cambia el estado de pago del pedido, mantiene sincronizada la columna legacy 'estado' y registra en historial.
     */
    public function cambiarEstadoPago(
        string $nuevoEstado,
        string $origen,
        ?int $userId = null,
        ?string $observacion = null
    ): bool {
        $estadosValidos = [
            self::ESTADO_PAGO_PENDIENTE,
            self::ESTADO_PAGO_PAGADO,
            self::ESTADO_PAGO_RECHAZADO,
            self::ESTADO_PAGO_EXPIRADO,
            self::ESTADO_PAGO_REEMBOLSADO,
        ];

        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            throw new \InvalidArgumentException("Estado de pago no válido: {$nuevoEstado}");
        }

        $estadoAnterior = $this->estado_pago;

        $this->estado_pago = $nuevoEstado;

        // Mantener sincronizada la columna legacy 'estado' en paralelo
        if ($nuevoEstado === self::ESTADO_PAGO_PAGADO) {
            $this->estado = 'pagado';
        } elseif (in_array($nuevoEstado, [self::ESTADO_PAGO_RECHAZADO, self::ESTADO_PAGO_EXPIRADO, self::ESTADO_PAGO_REEMBOLSADO], true)) {
            $this->estado = 'cancelado';
        } else {
            $this->estado = 'pendiente';
        }

        $this->save();

        PedidoHistorialEstado::create([
            'pedido_id'       => $this->id,
            'tipo'            => 'pago',
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => $nuevoEstado,
            'user_id'         => $userId,
            'origen'          => $origen,
            'observacion'     => $observacion,
            'created_at'      => now(),
        ]);

        return true;
    }

    /**
     * Cambia el estado de envío del pedido y registra el evento en el historial.
     */
    public function cambiarEstadoEnvio(
        string $nuevoEstado,
        string $origen,
        ?int $userId = null,
        ?string $observacion = null,
        ?string $transportista = null,
        ?string $trackingNumero = null
    ): bool {
        if ($this->estado_pago !== self::ESTADO_PAGO_PAGADO) {
            throw new \DomainException("No se puede modificar el estado de envío porque el pedido no está pagado.");
        }

        $estadosValidos = [
            self::ESTADO_ENVIO_SIN_PREPARAR,
            self::ESTADO_ENVIO_PREPARANDO,
            self::ESTADO_ENVIO_ENVIADO,
            self::ESTADO_ENVIO_ENTREGADO,
            self::ESTADO_ENVIO_DEVUELTO,
        ];

        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            throw new \InvalidArgumentException("Estado de envío no válido: {$nuevoEstado}");
        }

        $actual = $this->estado_envio ?? self::ESTADO_ENVIO_SIN_PREPARAR;

        if ($actual === $nuevoEstado && $this->estado_envio !== null) {
            if ($transportista !== null) {
                $this->transportista = $transportista;
            }
            if ($trackingNumero !== null) {
                $this->tracking_numero = $trackingNumero;
            }
            $this->save();
            return true;
        }

        if ($actual === self::ESTADO_ENVIO_DEVUELTO) {
            throw new \DomainException("El estado 'devuelto' es terminal y no se puede cambiar.");
        }

        if ($nuevoEstado === self::ESTADO_ENVIO_DEVUELTO) {
            if (!in_array($actual, [self::ESTADO_ENVIO_ENVIADO, self::ESTADO_ENVIO_ENTREGADO], true)) {
                throw new \DomainException("El estado 'devuelto' solo se puede asignar desde 'enviado' o 'entregado'.");
            }
        } else {
            $secuencia = [
                self::ESTADO_ENVIO_SIN_PREPARAR => 1,
                self::ESTADO_ENVIO_PREPARANDO   => 2,
                self::ESTADO_ENVIO_ENVIADO      => 3,
                self::ESTADO_ENVIO_ENTREGADO    => 4,
            ];

            if (isset($secuencia[$actual], $secuencia[$nuevoEstado])) {
                $diff = $secuencia[$nuevoEstado] - $secuencia[$actual];

                if ($diff > 1) {
                    throw new \DomainException("No se pueden saltar estados de envío (de '{$actual}' a '{$nuevoEstado}').");
                }

                if ($diff < 0) {
                    if ($diff < -1) {
                        throw new \DomainException("Solo se puede retroceder un paso a la vez.");
                    }
                    if (empty(trim($observacion ?? ''))) {
                        throw new \DomainException("Para retroceder un estado de envío es obligatoria una observación.");
                    }
                }
            }
        }

        $estadoAnterior = $this->estado_envio;
        $this->estado_envio = $nuevoEstado;

        if ($transportista !== null) {
            $this->transportista = $transportista;
        }
        if ($trackingNumero !== null) {
            $this->tracking_numero = $trackingNumero;
        }

        if ($nuevoEstado === self::ESTADO_ENVIO_ENVIADO && !$this->enviado_at) {
            $this->enviado_at = now();
        }

        if ($nuevoEstado === self::ESTADO_ENVIO_ENTREGADO && !$this->entregado_at) {
            $this->entregado_at = now();
        }

        $this->save();

        PedidoHistorialEstado::create([
            'pedido_id'       => $this->id,
            'tipo'            => 'envio',
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => $nuevoEstado,
            'user_id'         => $userId,
            'origen'          => $origen,
            'observacion'     => $observacion,
            'created_at'      => now(),
        ]);

        return true;
    }

    /**
     * Accessor para el estado visible único del cliente.
     */
    public function getEstadoVisibleAttribute(): string
    {
        if ($this->estado_pago === self::ESTADO_PAGO_REEMBOLSADO) {
            return 'Reembolsado';
        }

        if ($this->estado_pago === self::ESTADO_PAGO_RECHAZADO) {
            return 'Pago rechazado';
        }

        if ($this->estado_pago === self::ESTADO_PAGO_EXPIRADO) {
            return 'Expirado';
        }

        if ($this->estado_pago === self::ESTADO_PAGO_PENDIENTE) {
            return 'Esperando pago';
        }

        if ($this->estado_pago === self::ESTADO_PAGO_PAGADO) {
            $envio = $this->estado_envio ?? self::ESTADO_ENVIO_SIN_PREPARAR;

            switch ($envio) {
                case self::ESTADO_ENVIO_SIN_PREPARAR:
                    return 'Pago confirmado';
                case self::ESTADO_ENVIO_PREPARANDO:
                    return 'Preparando tu pedido';
                case self::ESTADO_ENVIO_ENVIADO:
                    return 'En camino';
                case self::ESTADO_ENVIO_ENTREGADO:
                    return 'Entregado';
                case self::ESTADO_ENVIO_DEVUELTO:
                    return 'Devuelto';
                default:
                    return 'Pago confirmado';
            }
        }

        return 'Desconocido';
    }

    /**
     * Accessor para el estado efectivo del pago (interpreta vencimientos en lectura sin alterar la BD).
     */
    public function getEstadoEfectivoAttribute(): string
    {
        if ($this->estado_pago === self::ESTADO_PAGO_PENDIENTE && $this->expira_at !== null && $this->expira_at <= now()) {
            return self::ESTADO_PAGO_EXPIRADO;
        }

        return $this->estado_pago;
    }
}
