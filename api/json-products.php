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
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background-color: #f9f9f9;
            padding: 15px;
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

        /* Responsive Styles */
        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .card-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Productos Admin</h1>
        <div id="total-products" class="total-count">Total de productos: 0</div>
        <div class="search-bar">
            <input type="text" id="search" placeholder="Buscar productos...">
        </div>
        <div id="product-list" class="card-container">
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
                    const productList = document.getElementById('product-list');
                    const totalProductsElement = document.getElementById('total-products');
                    
                    function displayProducts(products) {
                        productList.innerHTML = '';
                        products.forEach((product, index) => {
                            const card = document.createElement('div');
                            card.className = 'card';
                            card.innerHTML = `
                                <h3>${index + 1}. ${product.hs_nombre_producto}</h3>
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
                })
                .catch(error => console.error('Error fetching products:', error));
        }
    </script>
</body>
</html>