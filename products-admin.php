<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="container mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-4">Productos Admin</h1>
        <div class="text-lg mb-4" id="total-products">Total de productos: 0</div>
        <div class="search-bar mb-4">
            <input type="text" id="search" placeholder="Buscar productos..." class="w-full p-2 border border-gray-300 rounded">
        </div>
        <div class="text-blue-500 cursor-pointer mb-4" onclick="toggleForm()">Agregar Producto</div>
        <div class="form-section mb-4" id="form-section" style="display:none;">
            <form id="product-form" class="flex flex-wrap">
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="hs_slug_code" class="block text-gray-700">Slug Code</label>
                    <input type="text" id="hs_slug_code" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="md_codigo_carrera" class="block text-gray-700">Código Carrera</label>
                    <input type="text" id="md_codigo_carrera" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="hs_nombre_producto" class="block text-gray-700">Nombre Producto</label>
                    <input type="text" id="hs_nombre_producto" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="md_id_carrera" class="block text-gray-700">ID Carrera</label>
                    <input type="text" id="md_id_carrera" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="md_tipo_carrera" class="block text-gray-700">Tipo Carrera</label>
                    <select id="md_tipo_carrera" required class="w-full p-2 border border-gray-300 rounded">
                        <option value="">Seleccione Tipo Carrera</option>
                        <option value="PREGRADO">PREGRADO</option>
                        <option value="PROGRAMAS ESPECIALES">PROGRAMAS ESPECIALES</option>
                        <option value="POSGRADO">POSGRADO</option>
                        <option value="IDIOMAS">IDIOMAS</option>
                        <option value="WIZARD">WIZARD</option>
                    </select>
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="md_nombre_carrera" class="block text-gray-700">Nombre Carrera</label>
                    <input type="text" id="md_nombre_carrera" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="md_landing_value" class="block text-gray-700">Landing Value</label>
                    <input type="text" id="md_landing_value" required class="w-full p-2 border border-gray-300 rounded">
                </div>
                <div class="w-full md:w-1/2 px-2 mb-4">
                    <label for="modality" class="block text-gray-700">Modalidad</label>
                    <select id="modality" required class="w-full p-2 border border-gray-300 rounded">
                        <option value="">Seleccione Modalidad</option>
                        <option value="Virtual">Virtual</option>
                        <option value="Presencial">Presencial</option>
                    </select>
                </div>
                <button type="submit" id="form-button" class="bg-blue-500 text-white px-4 py-2 rounded">Crear Producto</button>
            </form>
        </div>
        <div id="product-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Las tarjetas de productos se mostrarán aquí -->
        </div>
    </div>

    <script>
        let editingIndex = null;
        let originalProducts = [];

        // Verificación de contraseña
        const password = prompt("Ingrese la contraseña:");
        if (password !== "CapitanAmericana") {
            alert("Contraseña incorrecta.");
            window.location.href = "about:blank";
        } else {
            fetch('api/json-products.php')
                .then(response => response.json())
                .then(products => {
                    originalProducts = products;
                    const productList = document.getElementById('product-list');
                    const totalProductsElement = document.getElementById('total-products');

                    function displayProducts(products, filterIndex = null) {
                        productList.innerHTML = '';
                        products.forEach((product, index) => {
                            if (filterIndex !== null && filterIndex !== index) return;
                            const card = document.createElement('div');
                            card.className = 'card p-4 border border-gray-300 rounded bg-gray-50';
                            if (filterIndex === index) card.classList.add('editing-card');
                            card.innerHTML = `
                                <h3 class="text-xl font-bold">${index + 1}. ${product.hs_nombre_producto} [${index}]</h3>
                                <p><strong>Slug Code:</strong> ${product.hs_slug_code}</p>
                                <p><strong>Código Carrera:</strong> ${product.md_codigo_carrera}</p>
                                <p><strong>ID Carrera:</strong> ${product.md_id_carrera}</p>
                                <p><strong>Tipo Carrera:</strong> ${product.md_tipo_carrera}</p>
                                <p><strong>Nombre Carrera:</strong> ${product.md_nombre_carrera}</p>
                                <p><strong>Landing Value:</strong> ${product.md_landing_value}</p>
                                <p><strong>Modalidad:</strong> ${product.modality}</p>
                                <button class="edit-btn bg-green-500 text-white px-2 py-1 rounded mt-2" onclick="editProduct(${index})">Editar</button>
                                <button class="delete-btn bg-red-500 text-white px-2 py-1 rounded mt-2" onclick="deleteProduct(${index})">Eliminar</button>
                            `;
                            productList.appendChild(card);
                        });
                        totalProductsElement.textContent = `Total de productos: ${products.length}`;
                    }

                    displayProducts(products);

                    // Filtro de búsqueda en tiempo real
                    document.getElementById('search').addEventListener('input', function () {
                        const searchTerm = this.value.toLowerCase();
                        const filteredProducts = originalProducts.filter(product => {
                            return Object.values(product).some(value =>
                                String(value).toLowerCase().includes(searchTerm)
                            );
                        });
                        displayProducts(filteredProducts);
                    });

                    // Manejo del formulario de producto
                    document.getElementById('product-form').addEventListener('submit', function (event) {
                        event.preventDefault();

                        const product = {
                            hs_slug_code: document.getElementById('hs_slug_code').value,
                            md_codigo_carrera: document.getElementById('md_codigo_carrera').value,
                            hs_nombre_producto: document.getElementById('hs_nombre_producto').value,
                            md_id_carrera: document.getElementById('md_id_carrera').value,
                            md_tipo_carrera: document.getElementById('md_tipo_carrera').value,
                            md_nombre_carrera: document.getElementById('md_nombre_carrera').value,
                            md_landing_value: document.getElementById('md_landing_value').value,
                            modality: document.getElementById('modality').value,
                        };

                        const action = editingIndex === null ? 'add' : 'edit';
                        const url = 'api/json-products.php';
                        const payload = {
                            action: action,
                            index: editingIndex,
                            product: product
                        };

                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                if (action === 'add') {
                                    originalProducts.push(product);
                                    displayProducts(originalProducts);
                                } else {
                                    originalProducts[editingIndex] = product;
                                    displayProducts(originalProducts);
                                    document.querySelector('.editing-card').classList.add('saved-card');
                                }
                                toggleForm();
                            } else {
                                alert('Error al guardar el producto.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    });

                    // Función para editar un producto
                    window.editProduct = function(index) {
                        editingIndex = index;
                        const product = originalProducts[index];
                        document.getElementById('hs_slug_code').value = product.hs_slug_code;
                        document.getElementById('md_codigo_carrera').value = product.md_codigo_carrera;
                        document.getElementById('hs_nombre_producto').value = product.hs_nombre_producto;
                        document.getElementById('md_id_carrera').value = product.md_id_carrera;
                        document.getElementById('md_tipo_carrera').value = product.md_tipo_carrera;
                        document.getElementById('md_nombre_carrera').value = product.md_nombre_carrera;
                        document.getElementById('md_landing_value').value = product.md_landing_value;
                        document.getElementById('modality').value = product.modality;
                        document.getElementById('form-button').textContent = 'Guardar Producto';
                        document.getElementById('search').value = product.hs_nombre_producto;
                        displayProducts(originalProducts, index);
                        toggleForm();
                    }

                    // Función para eliminar un producto
                    window.deleteProduct = function(index) {
                        if (confirm('¿Estás seguro de que deseas eliminar este producto?')) {
                            fetch('api/json-products.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'delete',
                                    index: index
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    originalProducts.splice(index, 1);
                                    displayProducts(originalProducts);
                                } else {
                                    alert('Error al eliminar el producto.');
                                }
                            })
                            .catch(error => console.error('Error:', error));
                        }
                    }

                    // Función para alternar la visibilidad del formulario
                    window.toggleForm = function() {
                        const formSection = document.getElementById('form-section');
                        if (editingIndex === null) {
                            document.getElementById('form-button').textContent = 'Crear Producto';
                            document.getElementById('product-form').reset();
                        }
                        formSection.style.display = formSection.style.display === 'none' ? 'block' : 'none';
                    }
                })
                .catch(error => console.error('Error fetching products:', error));
        }
    </script>
    <style>
        .editing-card {
            border: 2px solid blue;
            animation: highlight 0.5s forwards;
        }
        @keyframes highlight {
            from { background-color: lightblue; }
            to { background-color: transparent; }
        }
        .saved-card {
            animation: saved-animation 2s forwards;
        }
        @keyframes saved-animation {
            from { background-color: lightgreen; }
            to { background-color: transparent; }
        }
    </style>
</body>
</html>