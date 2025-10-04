<?php

namespace App\Services\Falabella;

/**
 * Interfaz para el cliente de Falabella
 * Define todos los métodos disponibles para interactuar con el API de Falabella
 */
interface FalabellaClient
{
    /**
     * Obtener productos de Falabella
     */
    public function getProducts(int $limit = 100, int $offset = 0): array;

    /**
     * Obtener stock de productos
     */
    public function getProductStock(): array;

    /**
     * Obtener datos de ventas
     */
    public function getSalesData(?string $startDate = null, ?string $endDate = null): array;

    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts(int $threshold = 5): array;

    /**
     * Obtener productos más vendidos
     */
    public function getBestSellingProducts(int $days = 30): array;

    /**
     * Obtener resumen de inventario
     */
    public function getInventorySummary(): array;

    /**
     * Obtener categorías disponibles
     */
    public function getCategories(): array;

    /**
     * Actualizar precio de un producto
     */
    public function updateProductPrice(string $sellerSku, float $price): array;

    /**
     * Actualizar stock de un producto
     */
    public function updateProductStock(string $sellerSku, int $quantity): array;

    /**
     * Obtener órdenes de Falabella
     */
    public function getOrders(?string $createdAfter = null, ?string $createdBefore = null, ?string $status = null, int $limit = 50, int $offset = 0): array;
}
