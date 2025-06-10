@extends('base.formulario')

@php
    $titulo = 'Crear Producto';
    $action = route('productos.store');
    $method = 'POST';
    $rutaVolver = route('productos.index');
    $textoBoton = 'Crear';

    // Prepare subcategorias data for JavaScript
    $subcategoriasData = $categorias->mapWithKeys(function ($categoria) {
        return [
            $categoria->id => [
                'id' => $categoria->id,
                'subcategorias' => $categoria->subcategorias->pluck('nombre', 'id')->toArray()
            ]
        ];
    })->toArray();

    $campos = [
        [
            'name' => 'nombre',
            'label' => 'Nombre',
            'placeholder' => 'Nombre del producto',
            'required' => true,
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción del producto',
            'required' => true,
            'type' => 'textarea',
            'rows' => 4,
            'cols' => 50,
        ],
        [
            'name' => 'precioUnitario',
            'label' => 'Precio Unitario',
            'placeholder' => 'Precio unitario del producto',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'stock',
            'label' => 'Stock',
            'placeholder' => 'Cantidad de productos en stock',
            'type' => 'number',
            'required' => true,
        ],
        [
            'name' => 'imagenes',
            'label' => 'Imágenes del producto',
            'type' => 'file',
            'required' => true,
            'multiple' => true,
            'accept' => 'image/*',
        ],
        [
            'name' => 'categoria_id',
            'label' => 'Categoría',
            'placeholder' => 'Seleccione una categoría',
            'type' => 'select',
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => true,
            'attributes' => ['id' => 'categoria_id'],
        ],
        [
            'name' => 'subcategorias[]',
            'label' => 'Subcategorías',
            'placeholder' => 'Seleccione una o más subcategorías',
            'type' => 'select',
            'options' => [],
            'required' => false,
            'multiple' => true,
            'attributes' => ['id' => 'subcategorias'],
        ],
    ];
@endphp

<input type="hidden" id="subcategorias-data" value="{{ json_encode($subcategoriasData) }}">

@push('scripts')
    <script>
        (function () {
        console.log('hola')
            const categoriaSelect = document.getElementById('categoria_id');
            const subcategoriasSelect = document.getElementById('subcategorias');
            const subcategoriasDataInput = document.getElementById('subcategorias-data');
            const subcategoriasData = JSON.parse(subcategoriasDataInput.value || '{}');

            function updateSubcategorias() {
                const categoriaId = categoriaSelect.value;
                subcategoriasSelect.innerHTML = '<option value="">Seleccione una o más subcategorías</option>';

                if (categoriaId && subcategoriasData[categoriaId]) {
                    const subcategorias = subcategoriasData[categoriaId].subcategorias;
                    for (const [id, nombre] of Object.entries(subcategorias)) {
                        const option = document.createElement('option');
                        option.value = id;
                        option.text = nombre;
                        subcategoriasSelect.appendChild(option);
                    }
                }
            }

            categoriaSelect.addEventListener('change', updateSubcategorias);
            updateSubcategorias();
        })();
    </script>
@endpush