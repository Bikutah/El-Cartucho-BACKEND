@extends('base.formulario')

@php
    $titulo = 'Editar Producto';
    $action = route('productos.update', $producto);
    $method = 'PUT';
    $rutaVolver = session('productos.listado_url', route('productos.index'));
    $textoBoton = 'Actualizar';
    $esProducto = true;

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
            'name' => 'categorias[]',
            'label' => 'Categorías',
            'placeholder' => 'Seleccione una o más categorías',
            'type' => 'select',
            'multiple' => true,
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => false,
            'value' => old('categorias', $producto->categorias->pluck('id')->toArray()),
            'attributes' => ['id' => 'categoria_id'],
        ],
    ];
@endphp

<input type="hidden" id="subcategorias-data" value="{{ json_encode($subcategoriasData) }}">
<input type="hidden" id="selected-subcategorias" value="{{ json_encode($producto->subcategorias->pluck('id')->toArray()) }}">

@push('scripts')
    @if (isset($esProducto) && $esProducto)
        <script>
            (function () {
                const categoriaSelect = document.getElementById('categoria_id');
                const subcategoriasContainer = document.getElementById('subcategorias-container');
                const subcategoriasDataInput = document.getElementById('subcategorias-data');
                const selectedSubcategoriasInput = document.getElementById('selected-subcategorias');
                
                const subcategoriasData = JSON.parse(subcategoriasDataInput.value || '{}');
                const selectedSubcategorias = JSON.parse(selectedSubcategoriasInput.value || '[]');

                function updateSubcategorias() {
                    const selectedOptions = Array.from(categoriaSelect.selectedOptions).map(opt => opt.value).filter(Boolean);
                    subcategoriasContainer.innerHTML = '';

                    let hasSubcategorias = false;
                    selectedOptions.forEach(categoriaId => {
                        if (subcategoriasData[categoriaId] && subcategoriasData[categoriaId].subcategorias) {
                            if (Object.keys(subcategoriasData[categoriaId].subcategorias).length > 0) {
                                hasSubcategorias = true;
                            }
                        }
                    });

                    if (hasSubcategorias) {
                        const title = document.createElement('p');
                        title.className = 'mb-2 fw-bold';
                        title.textContent = 'Subcategorías disponibles:';
                        subcategoriasContainer.appendChild(title);

                        selectedOptions.forEach(categoriaId => {
                            if (subcategoriasData[categoriaId] && subcategoriasData[categoriaId].subcategorias) {
                                const subcategorias = subcategoriasData[categoriaId].subcategorias;
                                for (const [id, nombre] of Object.entries(subcategorias)) {
                                    const checkboxDiv = document.createElement('div');
                                    checkboxDiv.className = 'form-check';
                                    
                                    const checkbox = document.createElement('input');
                                    checkbox.className = 'form-check-input';
                                    checkbox.type = 'checkbox';
                                    checkbox.name = 'subcategorias[]';
                                    checkbox.value = id;
                                    checkbox.id = `subcategoria_${id}`;
                                    
                                    if (selectedSubcategorias.includes(parseInt(id))) {
                                        checkbox.checked = true;
                                    }
                                    
                                    const label = document.createElement('label');
                                    label.className = 'form-check-label';
                                    label.htmlFor = `subcategoria_${id}`;
                                    label.textContent = nombre;
                                    
                                    checkboxDiv.appendChild(checkbox);
                                    checkboxDiv.appendChild(label);
                                    subcategoriasContainer.appendChild(checkboxDiv);
                                }
                            }
                        });
                    } else if (selectedOptions.length > 0) {
                        subcategoriasContainer.innerHTML = '<p class="text-muted mb-0">No hay subcategorías disponibles para las categorías seleccionadas</p>';
                    } else {
                        subcategoriasContainer.innerHTML = '<p class="text-muted mb-0">Seleccione primero una categoría para ver las subcategorías disponibles</p>';
                    }
                }

                categoriaSelect.addEventListener('change', updateSubcategorias);
                updateSubcategorias(); // Inicializar al cargar la página
            })();
        </script>
    @endif
@endpush