@extends('base.formulario')

@php
    $titulo = 'Editar Producto';
    $action = route('productos.update', $producto);
    $method = 'PUT';
    $rutaVolver = route('productos.index');
    $textoBoton = 'Actualizar';

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
            'value' => $producto->nombre
        ],
        [
            'name' => 'descripcion',
            'label' => 'Descripción',
            'placeholder' => 'Descripción del producto',
            'required' => true,
            'type' => 'textarea',
            'value' => $producto->descripcion,
            'rows' => 4,
            'cols' => 50,
        ],
        [
            'name' => 'precioUnitario',
            'label' => 'Precio Unitario',
            'placeholder' => 'Precio unitario del producto',
            'type' => 'number',
            'required' => true,
            'value' => $producto->precioUnitario
        ],
        [
            'name' => 'stock',
            'label' => 'Stock',
            'placeholder' => 'Cantidad de productos en stock',
            'type' => 'number',
            'required' => true,
            'value' => $producto->stock
        ],
        [
            'name' => 'imagenes[]',
            'label' => 'Imágenes del producto',
            'type' => 'file',
            'required' => false,
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
            'value' => $producto->categoria_id,
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
            'value' => $producto->subcategorias->pluck('id')->toArray(),
            'attributes' => ['id' => 'subcategorias'],
        ],
    ];
@endphp

<input type="hidden" id="subcategorias-data" value="{{ json_encode($subcategoriasData) }}">
<input type="hidden" id="selected-subcategorias" value="{{ json_encode($producto->subcategorias->pluck('id')->toArray()) }}">

@push('scripts')
    <script>
        (function () {
            const categoriaSelect = document.getElementById('categoria_id');
            const subcategoriasSelect = document.getElementById('subcategorias');
            const subcategoriasDataInput = document.getElementById('subcategorias-data');
            const selectedSubcategoriasInput = document.getElementById('selected-subcategorias');
            
            const subcategoriasData = JSON.parse(subcategoriasDataInput.value || '{}');
            const selectedSubcategorias = JSON.parse(selectedSubcategoriasInput.value || '[]');

            function updateSubcategorias() {
                const categoriaId = categoriaSelect.value;
                subcategoriasSelect.innerHTML = '<option value="">Seleccione una o más subcategorías</option>';

                if (categoriaId && subcategoriasData[categoriaId]) {
                    const subcategorias = subcategoriasData[categoriaId].subcategorias;
                    for (const [id, nombre] of Object.entries(subcategorias)) {
                        const option = document.createElement('option');
                        option.value = id;
                        option.text = nombre;
                        
                        // Marcar como seleccionada si está en el array de subcategorías seleccionadas
                        if (selectedSubcategorias.includes(parseInt(id))) {
                            option.selected = true;
                        }
                        
                        subcategoriasSelect.appendChild(option);
                    }
                }
            }

            categoriaSelect.addEventListener('change', updateSubcategorias);
            updateSubcategorias(); // Inicializar al cargar la página
        })();
    </script>
@endpush