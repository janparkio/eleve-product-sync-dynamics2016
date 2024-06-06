<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .search-bar input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .card {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .card h3 {
            margin-top: 0;
        }
        .total-count {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .form-section {
            margin-bottom: 20px;
            display: none;
        }
        .form-section form {
            display: flex;
            flex-wrap: wrap;
        }
        .form-section form div {
            flex: 1 1 45%;
            margin: 10px;
        }
        .form-section form input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-section form button {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .form-section form button:hover {
            background-color: #0056b3;
        }
        .toggle-form {
            cursor: pointer;
            color: #007bff;
        }
        .toggle-form:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .form-section form div {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Productos Admin</h1>
        <div class="total-count" id="total-products">Total de productos: 0</div>
        <div class="search-bar">
            <input type="text" id="search" placeholder="Buscar productos...">
        </div>
        <div class="toggle-form" onclick="toggleForm()">Agregar/Editar Producto</div>
        <div class="form-section" id="form-section">
            <form id="product-form">
                <div>
                    <input type="text" id="hs_slug_code" placeholder="Slug Code" required>
                </div>
                <div>
                    <input type="text" id="md_codigo_carrera" placeholder="Código Carrera" required>
                </div>
                <div>
                    <input type="text" id="hs_nombre_producto" placeholder="Nombre Producto" required>
                </div>
                <div>
                    <input type="text" id="md_id_carrera" placeholder="ID Carrera" required>
                </div>
                <div>
                    <input type="text" id="md_tipo_carrera" placeholder="Tipo Carrera" required>
                </div>
                <div>
                    <input type="text" id="md_nombre_carrera" placeholder="Nombre Carrera" required>
                </div>
                <div>
                    <input type="text" id="md_landing_value" placeholder="Landing Value" required>
                </div>
                <div>
                    <input type="text" id="modality" placeholder="Modalidad" required>
                </div>
                <button type="submit">Guardar Producto</button>
            </form>
        </div>
        <div id="product-list">
            <!-- Las tarjetas de productos se mostrarán aquí -->
        </div>
    </div>

    <script>
        // Verificación de contraseña
        const password = prompt("Ingrese la contraseña:");
        if (password !== "CapitanAmericana") {
            alert("Contraseña incorrecta.");
            window.location.href = "about:blank";
        } else {
            fetch('https://leadwise.pro/eleve/eleve-plugins/api/json-products.php')
                .then(response => response.json())
                .then(products => {
                    let productList = document.getElementById('product-list');
                    let totalProductsElement = document.getElementById('total-products');
                    
                    function displayProducts(products) {
                        productList.innerHTML = '';
                        products.forEach((product, index) => {
                            const card = document.createElement('div');
                            card.className = 'card';
                            card.innerHTML = `
                                <h3>${index + 1}. ${product.hs_nombre_producto} [${index}]</h3>
                                <p><strong>Slug Code:</strong> ${product.hs_slug_code}</p>
                                <p><strong>Código Carrera:</strong> ${product.md_codigo_carrera}</p>
                                <p><strong>ID Carrera:</strong> ${product.md_id_carrera}</p>
                                <p><strong>Tipo Carrera:</strong> ${product.md_tipo_carrera}</p>
                                <p><strong>Nombre Carrera:</strong> ${product.md_nombre_carrera}</p>
                                <p><strong>Landing Value:</strong> ${product.md_landing_value}</p>
                                <p><strong>Modalidad:</strong> ${product.modality}</p>
                            `;
                            productList.appendChild(card);
                        });
                        totalProductsElement.textContent = `Total de productos: ${products.length}`;
                    }

                    displayProducts(products);

                    // Filtro de búsqueda en tiempo real
                    document.getElementById('search').addEventListener('input', function () {
                        const searchTerm = this.value.toLowerCase();
                        const filteredProducts = products.filter(product => {
                            return Object.values(product).some(value =>
                                String(value).toLowerCase().includes(searchTerm)
                            );
                        });
                        displayProducts(filteredProducts);
                    });

                    // Agregar/Editar Producto
                    document.getElementById('product-form').addEventListener('submit', function (event) {
                        event.preventDefault();

                        const newProduct = {
                            hs_slug_code: document.getElementById('hs_slug_code').value,
                            md_codigo_carrera: document.getElementById('md_codigo_carrera').value,
                            hs_nombre_producto: document.getElementById('hs_nombre_producto').value,
                            md_id_carrera: document.getElementById('md_id_carrera').value,
                            md_tipo_carrera: document.getElementById('md_tipo_carrera').value,
                            md_nombre_carrera: document.getElementById('md_nombre_carrera').value,
                            md_landing_value: document.getElementById('md_landing_value').value,
                            modality: document.getElementById('modality').value
                        };

                        products.push(newProduct);

                        fetch('https://leadwise.pro/eleve/eleve-plugins/api/json-products.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(products)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                displayProducts(products);
                                alert('Producto agregado/actualizado exitosamente');
                            } else {
                                alert('Error al agregar/actualizar el producto');
                            }
                        })
                        .catch(error => console.error('Error saving product:', error));
                    });
                })
                .catch(error => console.error('Error fetching products:', error));
        }

        function toggleForm() {
            const formSection = document.getElementById('form-section');
            formSection.style.display = formSection.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>