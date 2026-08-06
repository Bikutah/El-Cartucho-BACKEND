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
            'name' => 'categorias',
            'label' => 'Categorías',
            'placeholder' => 'Buscar y seleccionar categorías...',
            'type' => 'category_selector',
            'options' => $categorias->pluck('nombre', 'id'),
            'required' => false,
        ],
    ];
@endphp

<input type="hidden" id="subcategorias-data" value="{{ json_encode($subcategoriasData) }}">
<input type="hidden" id="initial-categories" value="{{ json_encode(array_map('intval', old('categorias', $producto->categorias->pluck('id')->toArray()))) }}">
<input type="hidden" id="selected-subcategorias" value="{{ json_encode(array_map('intval', old('subcategorias', $producto->subcategorias->pluck('id')->toArray()))) }}">

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('buscar_categoria_input');
    const dropdown = document.getElementById('categoria_dropdown');
    const chipsContainer = document.getElementById('categoria_chips_container');
    const hiddenInputsContainer = document.getElementById('categoria_hidden_inputs');
    const subcategoriasContainer = document.getElementById('subcategorias-container');
    
    const subcategoriasDataInput = document.getElementById('subcategorias-data');
    const initialCategoriesInput = document.getElementById('initial-categories');
    const selectedSubcategoriasInput = document.getElementById('selected-subcategorias');

    if (!searchInput || !dropdown || !chipsContainer) return;

    const subcategoriasData = JSON.parse(subcategoriasDataInput.value || '{}');
    const selectedCategories = new Set(JSON.parse(initialCategoriesInput.value || '[]'));
    const selectedSubcategories = new Set(JSON.parse(selectedSubcategoriasInput.value || '[]'));

    const categoryOptions = Array.from(document.querySelectorAll('.item-categoria-option'));

    function renderChips() {
        chipsContainer.innerHTML = '';
        hiddenInputsContainer.innerHTML = '';

        selectedCategories.forEach(id => {
            const option = categoryOptions.find(opt => parseInt(opt.dataset.id) === id);
            if (!option) return;

            const chip = document.createElement('span');
            chip.className = 'badge bg-primary d-inline-flex align-items-center gap-2 p-2 fs-6';
            chip.textContent = option.dataset.nombre;

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close btn-close-white ms-1';
            closeBtn.style.fontSize = '0.65rem';
            closeBtn.onclick = function () {
                removeCategory(id);
            };

            chip.appendChild(closeBtn);
            chipsContainer.appendChild(chip);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'categorias[]';
            hiddenInput.value = id;
            hiddenInputsContainer.appendChild(hiddenInput);
        });
    }

    function addCategory(id) {
        selectedCategories.add(id);
        renderChips();
        updateSubcategories();
        dropdown.classList.add('d-none');
        searchInput.value = '';
    }

    function removeCategory(id) {
        selectedCategories.delete(id);

        if (subcategoriasData[id] && subcategoriasData[id].subcategorias) {
            Object.keys(subcategoriasData[id].subcategorias).forEach(subId => {
                selectedSubcategories.delete(parseInt(subId));
            });
        }

        renderChips();
        updateSubcategories();
    }

    function updateSubcategories() {
        subcategoriasContainer.innerHTML = '';

        let totalSubcats = 0;

        selectedCategories.forEach(catId => {
            if (subcategoriasData[catId] && subcategoriasData[catId].subcategorias) {
                const subcats = subcategoriasData[catId].subcategorias;
                const catOption = categoryOptions.find(opt => parseInt(opt.dataset.id) === catId);
                const catNombre = catOption ? catOption.dataset.nombre : 'Categoría ' + catId;

                if (Object.keys(subcats).length > 0) {
                    const header = document.createElement('p');
                    header.className = 'mb-1 mt-2 fw-bold text-secondary border-bottom pb-1';
                    header.textContent = catNombre;
                    subcategoriasContainer.appendChild(header);

                    for (const [subId, subNombre] of Object.entries(subcats)) {
                        totalSubcats++;
                        const sId = parseInt(subId);

                        const checkboxDiv = document.createElement('div');
                        checkboxDiv.className = 'form-check ms-2 mb-1';

                        const checkbox = document.createElement('input');
                        checkbox.className = 'form-check-input';
                        checkbox.type = 'checkbox';
                        checkbox.name = 'subcategorias[]';
                        checkbox.value = sId;
                        checkbox.id = `subcategoria_${sId}`;

                        if (selectedSubcategories.has(sId)) {
                            checkbox.checked = true;
                        }

                        checkbox.onchange = function () {
                            if (this.checked) {
                                selectedSubcategories.add(sId);
                            } else {
                                selectedSubcategories.delete(sId);
                            }
                        };

                        const label = document.createElement('label');
                        label.className = 'form-check-label';
                        label.htmlFor = `subcategoria_${sId}`;
                        label.textContent = subNombre;

                        checkboxDiv.appendChild(checkbox);
                        checkboxDiv.appendChild(label);
                        subcategoriasContainer.appendChild(checkboxDiv);
                    }
                }
            }
        });

        if (selectedCategories.size === 0) {
            subcategoriasContainer.innerHTML = '<p class="mb-0 text-muted">Seleccione primero una categoría para ver las subcategorías disponibles</p>';
        } else if (totalSubcats === 0) {
            subcategoriasContainer.innerHTML = '<p class="mb-0 text-muted">No hay subcategorías disponibles para las categorías seleccionadas</p>';
        }
    }

    function filterOptions() {
        const query = searchInput.value.trim().toLowerCase();
        if (!query) {
            dropdown.classList.add('d-none');
            return;
        }

        let visibleCount = 0;
        categoryOptions.forEach(opt => {
            const id = parseInt(opt.dataset.id);
            const nombre = opt.dataset.nombre.toLowerCase();
            const matches = nombre.includes(query) && !selectedCategories.has(id);
            opt.classList.toggle('d-none', !matches);
            if (matches) visibleCount++;
        });

        dropdown.classList.toggle('d-none', visibleCount === 0);
    }

    searchInput.addEventListener('input', filterOptions);

    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const firstVisible = categoryOptions.find(opt => !opt.classList.contains('d-none'));
            if (firstVisible) {
                addCategory(parseInt(firstVisible.dataset.id));
            }
        }
    });

    categoryOptions.forEach(opt => {
        opt.addEventListener('click', function () {
            addCategory(parseInt(this.dataset.id));
        });
    });

    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('d-none');
        }
    });

    renderChips();
    updateSubcategories();
})();
</script>
@endpush