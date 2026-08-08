<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReasociarUsuariosPedidos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:reasociar-usuarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completa el user_id de los pedidos existentes cruzando su firebase_uid con la tabla users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $affected = DB::update("
            UPDATE pedidos
            SET user_id = (
                SELECT id FROM users WHERE users.firebase_uid = pedidos.firebase_uid
            )
            WHERE user_id IS NULL
              AND firebase_uid IS NOT NULL
              AND firebase_uid NOT IN ('Max Verstappen', 'anonimo')
        ");

        $this->info("Reasociación completada. Pedidos actualizados: {$affected}");

        return Command::SUCCESS;
    }
}
