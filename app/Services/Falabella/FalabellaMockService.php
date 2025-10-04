<?php

namespace App\Services\Falabella;

/**
 * Implementación mock del cliente Falabella
 * Para desarrollo y testing sin usar el API real
 */
class FalabellaMockService implements FalabellaClient
{
    protected array $products;
    protected array $sales;
    protected array $categories;
    protected array $orders;

    public function __construct()
    {
        $this->initializeMockData();
    }

    protected function initializeMockData(): void
    {
        $this->products = [
            [
                'SellerSku' => 'SKU001',
                'Name' => 'Laptop Gaming ASUS',
                'Price' => 899999,
                'Quantity' => 15,
                'Status' => 'active',
                'PrimaryCategory' => 'Tecnología',
                'Brand' => 'ASUS',
                'Description' => 'Laptop gaming de alta performance',
                'Images' => ['https://via.placeholder.com/300x300'],
                'Weight' => 2.5,
                'Dimensions' => '35x25x3 cm'
            ],
            [
                'SellerSku' => 'SKU002',
                'Name' => 'Mouse Gaming RGB',
                'Price' => 45990,
                'Quantity' => 3, // Stock bajo
                'Status' => 'active',
                'PrimaryCategory' => 'Tecnología',
                'Brand' => 'Razer',
                'Description' => 'Mouse gaming con iluminación RGB',
                'Images' => ['https://via.placeholder.com/300x300'],
                'Weight' => 0.12,
                'Dimensions' => '12x6x4 cm'
            ],
            [
                'SellerSku' => 'SKU003',
                'Name' => 'Teclado Mecánico',
                'Price' => 89990,
                'Quantity' => 25,
                'Status' => 'active',
                'PrimaryCategory' => 'Tecnología',
                'Brand' => 'Corsair',
                'Description' => 'Teclado mecánico switches Cherry MX',
                'Images' => ['https://via.placeholder.com/300x300'],
                'Weight' => 1.2,
                'Dimensions' => '44x13x4 cm'
            ],
            [
                'SellerSku' => 'SKU004',
                'Name' => 'Monitor 4K 27"',
                'Price' => 299990,
                'Quantity' => 0, // Sin stock
                'Status' => 'inactive',
                'PrimaryCategory' => 'Tecnología',
                'Brand' => 'Samsung',
                'Description' => 'Monitor 4K UHD de 27 pulgadas',
                'Images' => ['https://via.placeholder.com/300x300'],
                'Weight' => 6.8,
                'Dimensions' => '61x36x21 cm'
            ],
            [
                'SellerSku' => 'SKU005',
                'Name' => 'Auriculares Bluetooth',
                'Price' => 129990,
                'Quantity' => 50,
                'Status' => 'active',
                'PrimaryCategory' => 'Audio',
                'Brand' => 'Sony',
                'Description' => 'Auriculares inalámbricos con cancelación de ruido',
                'Images' => ['https://via.placeholder.com/300x300'],
                'Weight' => 0.25,
                'Dimensions' => '18x16x8 cm'
            ]
        ];

        $this->sales = [
            [
                'OrderId' => 'ORD001',
                'SellerSku' => 'SKU001',
                'Quantity' => 2,
                'Price' => 899999,
                'OrderDate' => now()->subDays(5)->toIso8601String(),
                'Status' => 'shipped',
                'Customer' => 'Juan Pérez'
            ],
            [
                'OrderId' => 'ORD002',
                'SellerSku' => 'SKU002',
                'Quantity' => 5,
                'Price' => 45990,
                'OrderDate' => now()->subDays(3)->toIso8601String(),
                'Status' => 'delivered',
                'Customer' => 'María García'
            ],
            [
                'OrderId' => 'ORD003',
                'SellerSku' => 'SKU003',
                'Quantity' => 1,
                'Price' => 89990,
                'OrderDate' => now()->subDays(2)->toIso8601String(),
                'Status' => 'processing',
                'Customer' => 'Carlos López'
            ],
            [
                'OrderId' => 'ORD004',
                'SellerSku' => 'SKU005',
                'Quantity' => 3,
                'Price' => 129990,
                'OrderDate' => now()->subDays(1)->toIso8601String(),
                'Status' => 'shipped',
                'Customer' => 'Ana Martínez'
            ]
        ];

        $this->categories = [
            ['CategoryId' => 1, 'Name' => 'Tecnología', 'ParentId' => null],
            ['CategoryId' => 2, 'Name' => 'Audio', 'ParentId' => null],
            ['CategoryId' => 3, 'Name' => 'Computadores', 'ParentId' => 1],
            ['CategoryId' => 4, 'Name' => 'Accesorios Gaming', 'ParentId' => 1],
            ['CategoryId' => 5, 'Name' => 'Monitores', 'ParentId' => 1],
        ];

        $this->orders = [
            [
                'OrderId' => '12345678',
                'OrderNumber' => 'FB-2025-001',
                'Status' => 'delivered',
                'CreatedAt' => now()->subDays(7)->toIso8601String(),
                'UpdatedAt' => now()->subDays(5)->toIso8601String(),
                'ItemsCount' => 2,
                'GrandTotal' => '1799998',
                'PaymentMethod' => 'credit_card',
                'CustomerFirstName' => 'Juan',
                'CustomerLastName' => 'Pérez',
                'CustomerEmail' => 'juan.perez@email.com',
                'ShippingAddress' => [
                    'FirstName' => 'Juan',
                    'LastName' => 'Pérez',
                    'Address1' => 'Av. Providencia 1234',
                    'City' => 'Santiago',
                    'PostCode' => '7500000',
                    'Country' => 'Chile'
                ],
                'OrderItems' => [
                    [
                        'OrderItemId' => '11111',
                        'SellerSku' => 'SKU001',
                        'Name' => 'Laptop Gamer',
                        'Quantity' => 2,
                        'UnitPrice' => '899999',
                        'TotalPrice' => '1799998'
                    ]
                ]
            ],
            [
                'OrderId' => '12345679',
                'OrderNumber' => 'FB-2025-002',
                'Status' => 'ready_to_ship',
                'CreatedAt' => now()->subDays(3)->toIso8601String(),
                'UpdatedAt' => now()->subDays(2)->toIso8601String(),
                'ItemsCount' => 5,
                'GrandTotal' => '229950',
                'PaymentMethod' => 'debit_card',
                'CustomerFirstName' => 'María',
                'CustomerLastName' => 'García',
                'CustomerEmail' => 'maria.garcia@email.com',
                'ShippingAddress' => [
                    'FirstName' => 'María',
                    'LastName' => 'García',
                    'Address1' => 'Calle Falsa 123',
                    'City' => 'Valparaíso',
                    'PostCode' => '2340000',
                    'Country' => 'Chile'
                ],
                'OrderItems' => [
                    [
                        'OrderItemId' => '22222',
                        'SellerSku' => 'SKU002',
                        'Name' => 'Mouse Inalámbrico',
                        'Quantity' => 5,
                        'UnitPrice' => '45990',
                        'TotalPrice' => '229950'
                    ]
                ]
            ],
            [
                'OrderId' => '12345680',
                'OrderNumber' => 'FB-2025-003',
                'Status' => 'processing',
                'CreatedAt' => now()->subDays(1)->toIso8601String(),
                'UpdatedAt' => now()->toIso8601String(),
                'ItemsCount' => 1,
                'GrandTotal' => '89990',
                'PaymentMethod' => 'bank_transfer',
                'CustomerFirstName' => 'Carlos',
                'CustomerLastName' => 'López',
                'CustomerEmail' => 'carlos.lopez@email.com',
                'ShippingAddress' => [
                    'FirstName' => 'Carlos',
                    'LastName' => 'López',
                    'Address1' => 'Los Alamos 456',
                    'City' => 'Concepción',
                    'PostCode' => '4030000',
                    'Country' => 'Chile'
                ],
                'OrderItems' => [
                    [
                        'OrderItemId' => '33333',
                        'SellerSku' => 'SKU003',
                        'Name' => 'Teclado Mecánico',
                        'Quantity' => 1,
                        'UnitPrice' => '89990',
                        'TotalPrice' => '89990'
                    ]
                ]
            ],
            [
                'OrderId' => '12345681',
                'OrderNumber' => 'FB-2025-004',
                'Status' => 'shipped',
                'CreatedAt' => now()->subHours(12)->toIso8601String(),
                'UpdatedAt' => now()->subHours(6)->toIso8601String(),
                'ItemsCount' => 3,
                'GrandTotal' => '389970',
                'PaymentMethod' => 'credit_card',
                'CustomerFirstName' => 'Ana',
                'CustomerLastName' => 'Martínez',
                'CustomerEmail' => 'ana.martinez@email.com',
                'ShippingAddress' => [
                    'FirstName' => 'Ana',
                    'LastName' => 'Martínez',
                    'Address1' => 'Pedro de Valdivia 789',
                    'City' => 'La Serena',
                    'PostCode' => '1700000',
                    'Country' => 'Chile'
                ],
                'OrderItems' => [
                    [
                        'OrderItemId' => '44444',
                        'SellerSku' => 'SKU005',
                        'Name' => 'Auriculares Bluetooth',
                        'Quantity' => 3,
                        'UnitPrice' => '129990',
                        'TotalPrice' => '389970'
                    ]
                ]
            ],
            [
                'OrderId' => '12345682',
                'OrderNumber' => 'FB-2025-005',
                'Status' => 'canceled',
                'CreatedAt' => now()->subHours(6)->toIso8601String(),
                'UpdatedAt' => now()->subHours(3)->toIso8601String(),
                'ItemsCount' => 1,
                'GrandTotal' => '299990',
                'PaymentMethod' => 'credit_card',
                'CustomerFirstName' => 'Roberto',
                'CustomerLastName' => 'Silva',
                'CustomerEmail' => 'roberto.silva@email.com',
                'ShippingAddress' => [
                    'FirstName' => 'Roberto',
                    'LastName' => 'Silva',
                    'Address1' => 'Av. España 321',
                    'City' => 'Temuco',
                    'PostCode' => '4780000',
                    'Country' => 'Chile'
                ],
                'OrderItems' => [
                    [
                        'OrderItemId' => '55555',
                        'SellerSku' => 'SKU004',
                        'Name' => 'Monitor 4K 27"',
                        'Quantity' => 1,
                        'UnitPrice' => '299990',
                        'TotalPrice' => '299990'
                    ]
                ]
            ]
        ];
    }

    public function getProducts(int $limit = 100, int $offset = 0): array
    {
        $sliced = array_slice($this->products, $offset, $limit);
        
        return [
            'Product' => $sliced,
            'TotalProducts' => count($this->products)
        ];
    }

    public function getProductStock(): array
    {
        $stock = array_map(function ($product) {
            return [
                'SellerSku' => $product['SellerSku'],
                'Quantity' => $product['Quantity'],
                'Reserved' => 0,
                'Available' => $product['Quantity'],
                'SellableQuantity' => $product['Quantity'],
                'FulfillmentType' => 'seller'
            ];
        }, $this->products);

        return ['Product' => $stock];
    }

    public function getSalesData(?string $startDate = null, ?string $endDate = null): array
    {
        $filteredSales = $this->sales;

        if ($startDate || $endDate) {
            $filteredSales = array_filter($this->sales, function ($sale) use ($startDate, $endDate) {
                $orderDate = $sale['OrderDate'];
                
                if ($startDate && $orderDate < $startDate) return false;
                if ($endDate && $orderDate > $endDate) return false;
                
                return true;
            });
        }

        return [
            'Feed' => array_values($filteredSales),
            'TotalSales' => count($filteredSales)
        ];
    }

    public function getCategories(): array
    {
        return ['Category' => $this->categories];
    }

    public function updateProductPrice(string $sellerSku, float $price): array
    {
        // Simular actualización
        foreach ($this->products as &$product) {
            if ($product['SellerSku'] === $sellerSku) {
                $product['Price'] = $price;
                break;
            }
        }

        return [
            'SellerSku' => $sellerSku,
            'Price' => $price,
            'Status' => 'updated',
            'Message' => 'Price updated successfully (MOCK)'
        ];
    }

    public function updateProductStock(string $sellerSku, int $quantity): array
    {
        // Simular actualización
        foreach ($this->products as &$product) {
            if ($product['SellerSku'] === $sellerSku) {
                $product['Quantity'] = $quantity;
                break;
            }
        }

        return [
            'SellerSku' => $sellerSku,
            'Quantity' => $quantity,
            'Status' => 'updated',
            'Message' => 'Stock updated successfully (MOCK)'
        ];
    }

    public function getLowStockProducts(int $threshold = 5): array
    {
        return array_values(array_filter($this->products, function ($product) use ($threshold) {
            return $product['Quantity'] <= $threshold;
        }));
    }

    public function getBestSellingProducts(int $days = 30): array
    {
        // Calcular ventas por SKU
        $salesBySku = [];
        foreach ($this->sales as $sale) {
            $sku = $sale['SellerSku'];
            $salesBySku[$sku] = ($salesBySku[$sku] ?? 0) + $sale['Quantity'];
        }

        $result = [];
        foreach ($this->products as $product) {
            $sku = $product['SellerSku'];
            $totalSold = $salesBySku[$sku] ?? 0;
            
            if ($totalSold > 0) {
                $result[] = [
                    'product' => $product,
                    'totalSold' => $totalSold
                ];
            }
        }

        // Ordenar por ventas descendente
        usort($result, fn($a, $b) => $b['totalSold'] <=> $a['totalSold']);
        
        return $result;
    }

    public function getInventorySummary(): array
    {
        $validProducts = $this->products;
        $totalProducts = count($validProducts);
        
        $totalValue = array_reduce($validProducts, function ($sum, $product) {
            return $sum + ($product['Price'] * $product['Quantity']);
        }, 0);

        return [
            'totalProducts' => $totalProducts,
            'totalValue' => $totalValue,
            'lowStockCount' => count(array_filter($validProducts, fn($p) => $p['Quantity'] <= 5)),
            'outOfStockCount' => count(array_filter($validProducts, fn($p) => $p['Quantity'] == 0)),
            'averagePrice' => $totalProducts ? $totalValue / $totalProducts : 0,
            'mode' => 'MOCK'
        ];
    }

    public function getOrders(?string $createdAfter = null, ?string $createdBefore = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $filteredOrders = $this->orders;

        // Filtrar por fecha de creación
        if ($createdAfter || $createdBefore) {
            $filteredOrders = array_filter($filteredOrders, function ($order) use ($createdAfter, $createdBefore) {
                $orderDate = $order['CreatedAt'];
                
                if ($createdAfter && $orderDate < $createdAfter) return false;
                if ($createdBefore && $orderDate > $createdBefore) return false;
                
                return true;
            });
        }

        // Filtrar por estado
        if ($status) {
            $filteredOrders = array_filter($filteredOrders, function ($order) use ($status) {
                return $order['Status'] === $status;
            });
        }

        $totalCount = count($filteredOrders);
        
        // Aplicar paginación
        $slicedOrders = array_slice(array_values($filteredOrders), $offset, $limit);

        return [
            'Head' => [
                'TotalCount' => $totalCount,
            ],
            'Orders' => [
                'Order' => $slicedOrders
            ]
        ];
    }
}
