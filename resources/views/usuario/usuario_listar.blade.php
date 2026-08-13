@extends('base.listar')

@php
    $titulo = 'Listado de Usuarios';

    $filtros = [
        ['name' => 'nombre', 'placeholder' => 'Buscar por nombre'],
        ['name' => 'apellido', 'placeholder' => 'Buscar por apellido'],
    ];

    // No habilitamos rutas de crear, editar, ni eliminar porque el CRUD es read-only por ahora.
    $rutaCrear = null;
    $rutaEditar = null;
    $rutaEliminar = null;
    
    $columnas = [
        ['label' => 'Id'],
        ['label' => 'Nombre'],
        ['label' => 'Apellido'],
        ['label' => 'Email'],
        ['label' => 'ID Firebase']
    ];
    
    $items = $usuarios;
    
    // $renderFila is already passed from UserController
@endphp
