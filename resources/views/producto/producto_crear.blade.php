@extends('base.formulario')

@php
    $titulo = 'Crear Producto';
    $action = route('productos.store');
    $method = 'POST';
    $rutaVolver = route('productos.index');
    $textoBoton = 'Crear';
    $esProducto = true;
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
    ];
@endphp

<input type="hidden" id="subcategorias-data" value="{{ json_encode($subcategoriasData) }}">

@push('scripts')
@if (isset($esProducto) && $esProducto)
    <script>
        (function () {
            const categoriaSelect = document.getElementById('categoria_id');
            const subcategoriasContainer = document.getElementById('subcategorias-container');
            const subcategoriasDataInput = document.getElementById('subcategorias-data');
            const subcategoriasData = JSON.parse(subcategoriasDataInput.value || '{}');

            function updateSubcategorias() {
                const categoriaId = categoriaSelect.value;
                subcategoriasContainer.innerHTML = '';
                if (categoriaId && subcategoriasData[categoriaId]) {
                    const subcategorias = subcategoriasData[categoriaId].subcategorias;

                    if (Object.keys(subcategorias).length > 0) {
                        const title = document.createElement('p');
                        title.className = 'mb-2 fw-bold';
                        title.textContent = 'Subcategorías disponibles:';
                        subcategoriasContainer.appendChild(title);

                        for (const [id, nombre] of Object.entries(subcategorias)) {
                            const checkboxDiv = document.createElement('div');
                            checkboxDiv.className = 'form-check';
                            
                            const checkbox = document.createElement('input');
                            checkbox.className = 'form-check-input';
                            checkbox.type = 'checkbox';
                            checkbox.name = 'subcategorias[]';
                            checkbox.value = id;
                            checkbox.id = `subcategoria_${id}`;
                            
                            const label = document.createElement('label');
                            label.className = 'form-check-label';
                            label.htmlFor = `subcategoria_${id}`;
                            label.textContent = nombre;
                            
                            checkboxDiv.appendChild(checkbox);
                            checkboxDiv.appendChild(label);
                            subcategoriasContainer.appendChild(checkboxDiv);
                            console.log("hola")
                        }
                    } else {
                        subcategoriasContainer.innerHTML = '<p class="text-muted mb-0">No hay subcategorías disponibles para esta categoría</p>';
                    }
                } else {
                    subcategoriasContainer.innerHTML = '<p class="text-muted mb-0">Seleccione primero una categoría para ver las subcategorías disponibles</p>';
                }
            }

            categoriaSelect.addEventListener('change', updateSubcategorias);
            updateSubcategorias();
        })();
    </script>
@endif
@endpush