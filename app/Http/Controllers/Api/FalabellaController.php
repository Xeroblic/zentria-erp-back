<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Falabella\FalabellaClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * Controlador para exponer endpoints de Falabella al frontend
 * Actúa como proxy entre React y el servicio de Falabella
 */
class FalabellaController extends Controller
{
    public function __construct(
        private FalabellaClient $falabellaClient
    ) {}

    /**
     * Obtener productos
     * GET /api/falabella/products?limit=100&offset=0
     */
    public function products(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query('limit', 100);
            $offset = (int) $request->query('offset', 0);
            
            $data = $this->falabellaClient->getProducts($limit, $offset);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'service' => 'falabella'
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener stock de productos
     * GET /api/falabella/stock
     */
    public function stock(): JsonResponse
    {
        try {
            $data = $this->falabellaClient->getProductStock();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos de ventas
     * GET /api/falabella/sales?startDate=2025-01-01&endDate=2025-12-31
     */
    public function sales(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');
            
            $data = $this->falabellaClient->getSalesData($startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos con stock bajo
     * GET /api/falabella/low-stock?threshold=5
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $threshold = (int) $request->query('threshold', 5);
            
            $data = $this->falabellaClient->getLowStockProducts($threshold);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'threshold' => $threshold,
                    'count' => count($data)
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener productos más vendidos
     * GET /api/falabella/best-sellers?days=30
     */
    public function bestSellers(Request $request): JsonResponse
    {
        try {
            $days = (int) $request->query('days', 30);
            
            $data = $this->falabellaClient->getBestSellingProducts($days);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'days' => $days,
                    'count' => count($data)
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener resumen de inventario
     * GET /api/falabella/inventory-summary
     */
    public function inventorySummary(): JsonResponse
    {
        try {
            $data = $this->falabellaClient->getInventorySummary();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener categorías
     * GET /api/falabella/categories
     */
    public function categories(): JsonResponse
    {
        try {
            $data = $this->falabellaClient->getCategories();
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar precio de producto
     * PUT /api/falabella/products/{sku}/price
     */
    public function updatePrice(Request $request, string $sku): JsonResponse
    {
        try {
            $request->validate([
                'price' => 'required|numeric|min:0'
            ]);
            
            $price = (float) $request->input('price');
            
            $data = $this->falabellaClient->updateProductPrice($sku, $price);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Precio actualizado correctamente'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar stock de producto
     * PUT /api/falabella/products/{sku}/stock
     */
    public function updateStock(Request $request, string $sku): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:0'
            ]);
            
            $quantity = (int) $request->input('quantity');
            
            $data = $this->falabellaClient->updateProductStock($sku, $quantity);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Stock actualizado correctamente'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener órdenes de Falabella
     * GET /api/falabella/orders?from=2025-03-01T00:00:00Z&to=2025-03-31T23:59:59Z&status=delivered&limit=50&offset=0
     */
    public function orders(Request $request): JsonResponse
    {
        try {
            $createdAfter = $request->query('from');
            $createdBefore = $request->query('to');
            $status = $request->query('status');
            $limit = (int) $request->query('limit', 50);
            $offset = (int) $request->query('offset', 0);

            $raw = $this->falabellaClient->getOrders($createdAfter, $createdBefore, $status, $limit, $offset);

            // ⚠️ Minimizar datos sensibles hacia el frontend
            $orders = collect($raw['Orders']['Order'] ?? [])->map(function($order) {
                return [
                    'OrderId' => $order['OrderId'] ?? null,
                    'OrderNumber' => $order['OrderNumber'] ?? null,
                    'Status' => $order['Status'] ?? null,
                    'CreatedAt' => $order['CreatedAt'] ?? null,
                    'UpdatedAt' => $order['UpdatedAt'] ?? null,
                    'ItemsCount' => (int)($order['ItemsCount'] ?? 0),
                    'GrandTotal' => $order['GrandTotal'] ?? null,
                    'PaymentMethod' => $order['PaymentMethod'] ?? null,
                    // Sin datos sensibles como Address / RUT / teléfonos
                ];
            })->values();

            return response()->json([
                'success' => true,
                'total' => (int)($raw['Head']['TotalCount'] ?? count($orders)),
                'limit' => $limit,
                'offset' => $offset,
                'orders' => $orders,
                'meta' => [
                    'from' => $createdAfter,
                    'to' => $createdBefore,
                    'status' => $status
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
