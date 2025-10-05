<?php

namespace App\Services\Falabella;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Implementación real del cliente Falabella
 * Maneja autenticación HMAC, retry, y todas las llamadas al API real
 */
class FalabellaApiService implements FalabellaClient
{
    protected string $baseUrl;
    protected string $userId;
    protected string $apiKey;
    protected string $version;
    protected string $format;
    protected int $timeout;
    protected int $retryAttempts;
    protected int $retryDelay;

    public function __construct()
    {
        $config = config('falabella');
        
        $this->baseUrl = $config['base_url'];
        $this->userId = $config['user_id'] ?? 'NOT_SET';
        $this->apiKey = $config['api_key'] ?? 'NOT_SET';
        $this->version = $config['version'];
        $this->format = $config['format'];
        $this->timeout = $config['timeout'];
        $this->retryAttempts = $config['retry_attempts'];
        $this->retryDelay = $config['retry_delay'];

        // Validar configuración
        if (empty($this->userId) || empty($this->apiKey)) {
            throw new Exception('Falabella API credentials not configured');
        }
    }

    /**
     * Generar firma HMAC para autenticación
     */
    protected function sign(array $params): string
    {
        ksort($params); // Ordenar por clave ascendente
        $query = collect($params)->map(fn($v, $k) => $k . '=' . $v)->implode('&');
        return hash_hmac('sha256', $query, $this->apiKey);
    }

    /**
     * Realizar llamada al API de Falabella
     */
    protected function call(string $action, array $extra = []): array
    {
        $params = array_merge([
            'UserID' => $this->userId,
            'Version' => $this->version,
            'Format' => $this->format,
            'Timestamp' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'Action' => $action,
        ], $extra);

        $params['Signature'] = $this->sign($params);

        $url = rtrim($this->baseUrl, '/') . '/api';

        Log::info('Falabella API Call', [
            'action' => $action,
            'url' => $url,
            'params_count' => count($params),
            'has_signature' => isset($params['Signature']),
            'user_id' => $params['UserID'] ?? 'missing',
            'timestamp' => $params['Timestamp'] ?? 'missing'
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryAttempts, $this->retryDelay, throw: false)
                ->acceptJson()
                ->get($url, $params);

            if (!$response->ok()) {
                Log::error('Falabella API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Falabella API error: ' . $response->body(), $response->status());
            }

            $json = $response->json();

            if (isset($json['ErrorResponse'])) {
                $errorMessage = $json['ErrorResponse']['Head']['ErrorMessage'] ?? 'Unknown error';
                Log::error('Falabella API Business Error', ['error' => $errorMessage]);
                throw new Exception('Falabella API Error: ' . $errorMessage, 502);
            }

            return $json['SuccessResponse']['Body'] ?? [];

        } catch (Exception $e) {
            Log::error('Falabella API Exception', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getProducts(int $limit = 100, int $offset = 0): array
    {
        return $this->call('GetProducts', [
            'Limit' => $limit,
            'Offset' => $offset
        ]);
    }

    public function getProductStock(): array
    {
        return $this->call('FetchStock');
    }

    public function getSalesData(?string $startDate = null, ?string $endDate = null): array
    {
        $extra = [];
        if ($startDate) $extra['CreatedAfter'] = $startDate;
        if ($endDate) $extra['CreatedBefore'] = $endDate;
        
        return $this->call('FeedList', $extra);
    }

    public function getCategories(): array
    {
        return $this->call('GetCategoryTree');
    }

    public function updateProductPrice(string $sellerSku, float $price): array
    {
        return $this->call('ProductUpdate', [
            'SellerSku' => $sellerSku,
            'Price' => $price
        ]);
    }

    public function updateProductStock(string $sellerSku, int $quantity): array
    {
        return $this->call('ProductUpdate', [
            'SellerSku' => $sellerSku,
            'Quantity' => $quantity
        ]);
    }

    public function getLowStockProducts(int $threshold = 5): array
    {
        $products = $this->getProducts()['Product'] ?? [];
        
        return array_values(array_filter($products, function ($product) use ($threshold) {
            return isset($product['Quantity']) && $product['Quantity'] <= $threshold;
        }));
    }

    public function getBestSellingProducts(int $days = 30): array
    {
        $end = now();
        $start = $end->clone()->subDays($days);
        
        $products = $this->getProducts()['Product'] ?? [];
        $sales = $this->getSalesData($start->toIso8601String(), $end->toIso8601String())['Feed'] ?? [];

        // Agrupar ventas por SKU
        $salesBySku = [];
        foreach ($sales as $sale) {
            $sku = $sale['SellerSku'] ?? null;
            if ($sku) {
                $salesBySku[$sku] = ($salesBySku[$sku] ?? 0) + ($sale['Quantity'] ?? 0);
            }
        }

        // Combinar con información de productos
        $result = [];
        foreach ($products as $product) {
            $sku = $product['SellerSku'] ?? null;
            if (!$sku) continue;
            
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
        $products = $this->getProducts()['Product'] ?? [];
        
        $validProducts = array_values(array_filter($products, function ($product) {
            return is_numeric($product['Price'] ?? null) && is_numeric($product['Quantity'] ?? null);
        }));

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
        ];
    }

    public function getOrders(?string $createdAfter = null, ?string $createdBefore = null, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $extra = [
            'Limit' => (string)$limit,
            'Offset' => (string)$offset,
        ];
        
        if ($createdAfter) $extra['CreatedAfter'] = $createdAfter;    // ISO-8601
        if ($createdBefore) $extra['CreatedBefore'] = $createdBefore; // ISO-8601
        if ($status) $extra['Status'] = $status;                     // ej: delivered, canceled, ready_to_ship

        return $this->call('GetOrders', $extra);
    }
}
