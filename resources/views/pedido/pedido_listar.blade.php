@extends('base.listar')

@php
    $titulo = 'Gestión de Pedidos';
    $items = $pedidos;
    $rutaVer = 'pedidos.show'; 
    $rutaImprimir = 'pedidos.imprimir';
    $columnas = [
        ['label' => 'ID'],
        ['label' => 'Cliente'],
        ['label' => 'Estado'],
        ['label' => 'Total'],
        ['label' => 'Fecha'],
        ['label' => 'Productos'],
    ];
@endphp