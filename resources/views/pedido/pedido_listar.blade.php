@extends('base.listar')

@php
    $titulo = 'Gestión de Pedidos';
    $items = $pedidos;
    $rutaVer = 'pedidos.show'; 
    $rutaImprimir = 'pedidos.imprimir';
    $columnas = [
        ['label' => 'ID'],
        ['label' => 'Cliente'],
        ['label' => 'Pago'],
        ['label' => 'Envío'],
        ['label' => 'Total'],
        ['label' => 'Fecha'],
        ['label' => 'Productos'],
    ];
@endphp