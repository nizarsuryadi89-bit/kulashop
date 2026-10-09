<?php

namespace App\Services;

use App\Models\InventoryCost;
use App\Models\InventoryCostLayer;
use App\Models\InventoryCostHistory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\DB;

class InventoryCostService
{
    /**
     * Handle cost update from a Goods Receipt
     */
    public function handlePurchaseReceipt(GoodsReceipt $receipt)
    {
        DB::transaction(function () use ($receipt) {
            foreach ($receipt->items as $item) {
                // Determine Net Cost
                $po = $receipt->purchaseOrder;
                $poSubtotal = $po->subtotal;
                $poDiscount = $po->discount;
                
                $poItem = $item->purchaseOrderItem;
                if (!$poItem) continue;

                // Item gross total
                $qtyReceived = $item->qty_received;
                $itemGrossTotal = $qtyReceived * $poItem->unit_price;
                
                $itemDiscount = 0;
                if ($poSubtotal > 0 && $poDiscount > 0) {
                    $proportion = $itemGrossTotal / $poSubtotal;
                    $itemDiscount = $poDiscount * $proportion;
                }
                
                $itemNetTotal = $itemGrossTotal - $itemDiscount;
                $unitNetCost = $qtyReceived > 0 ? ($itemNetTotal / $qtyReceived) : 0;
                
                // Create Cost Layer
                $this->createCostLayer(
                    $item->product_id,
                    $receipt->warehouse_id,
                    'purchase',
                    $receipt->id,
                    $qtyReceived,
                    $unitNetCost
                );
                
                // Recalculate Average Cost (Add)
                $this->addStockAndRecalculate(
                    $item->product_id,
                    $receipt->warehouse_id,
                    $qtyReceived,
                    $unitNetCost,
                    'purchase',
                    $receipt->id
                );
            }
        });
    }

    /**
     * Set Opening Stock Cost
     */
    public function handleOpeningStock(Product $product, Warehouse $warehouse, $qty, $unitCost)
    {
        DB::transaction(function () use ($product, $warehouse, $qty, $unitCost) {
            $this->createCostLayer(
                $product->id,
                $warehouse->id,
                'opening_stock',
                null,
                $qty,
                $unitCost
            );

            $this->addStockAndRecalculate(
                $product->id,
                $warehouse->id,
                $qty,
                $unitCost,
                'opening_stock',
                null
            );
        });
    }

    /**
     * Handle Stock Adjustment In
     */
    public function handleStockAdjustmentIn(Product $product, Warehouse $warehouse, $qty, $unitCost, $referenceId = null)
    {
        DB::transaction(function () use ($product, $warehouse, $qty, $unitCost, $referenceId) {
            $this->createCostLayer(
                $product->id,
                $warehouse->id,
                'adjustment_in',
                $referenceId,
                $qty,
                $unitCost
            );

            $this->addStockAndRecalculate(
                $product->id,
                $warehouse->id,
                $qty,
                $unitCost,
                'adjustment_in',
                $referenceId
            );
        });
    }
    
    /**
     * Handle Stock Deductions (Sales, Transfer Out, Adjustment Out)
     */
    public function deductStock(Product $product, Warehouse $warehouse, $qty, $referenceType, $referenceId = null)
    {
        DB::transaction(function () use ($product, $warehouse, $qty, $referenceType, $referenceId) {
            $inventoryCost = InventoryCost::where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->lockForUpdate()
                ->first();
                
            if (!$inventoryCost) {
                return;
            }
            
            $oldQty = $inventoryCost->quantity;
            $oldAvgCost = $inventoryCost->average_cost;
            $oldValue = $inventoryCost->inventory_value;
            
            $newQty = $oldQty - $qty;
            if ($newQty < 0) {
                $newQty = 0;
            }
            
            $newAvgCost = $oldAvgCost;
            $newValue = $newQty * $newAvgCost;
            
            $inventoryCost->quantity = $newQty;
            $inventoryCost->inventory_value = $newValue;
            $inventoryCost->calculated_at = now();
            $inventoryCost->save();
            
            InventoryCostHistory::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'old_average_cost' => $oldAvgCost,
                'new_average_cost' => $newAvgCost,
                'old_quantity' => $oldQty,
                'new_quantity' => $newQty,
                'old_inventory_value' => $oldValue,
                'new_inventory_value' => $newValue,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'effective_at' => now()
            ]);
            
            $this->depleteLayers($product->id, $warehouse->id, $qty);
        });
    }

    /**
     * Handle Stock Transfer (Warehouse A to Warehouse B)
     */
    public function handleStockTransfer(Product $product, Warehouse $fromWarehouse, Warehouse $toWarehouse, $qty, $referenceId = null)
    {
        DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $qty, $referenceId) {
            // Get current WA cost from source
            $sourceCost = InventoryCost::where('product_id', $product->id)
                ->where('warehouse_id', $fromWarehouse->id)
                ->first();
                
            $unitCost = $sourceCost ? $sourceCost->average_cost : 0;
            
            // Deduct from source
            $this->deductStock($product, $fromWarehouse, $qty, 'transfer_out', $referenceId);
            
            // Add to destination
            $this->createCostLayer(
                $product->id,
                $toWarehouse->id,
                'transfer_in',
                $referenceId,
                $qty,
                $unitCost
            );

            $this->addStockAndRecalculate(
                $product->id,
                $toWarehouse->id,
                $qty,
                $unitCost,
                'transfer_in',
                $referenceId
            );
        });
    }

    private function addStockAndRecalculate($productId, $warehouseId, $qtyIn, $unitCostIn, $referenceType, $referenceId)
    {
        $inventoryCost = InventoryCost::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['quantity' => 0, 'average_cost' => 0, 'inventory_value' => 0, 'calculated_at' => now()]
        );
        
        $oldQty = $inventoryCost->quantity;
        $oldAvgCost = $inventoryCost->average_cost;
        $oldValue = $inventoryCost->inventory_value;
        
        $newQty = $oldQty + $qtyIn;
        $newValue = $oldValue + ($qtyIn * $unitCostIn);
        
        $newAvgCost = 0;
        if ($newQty > 0) {
            $newAvgCost = $newValue / $newQty;
        }
        
        $inventoryCost->quantity = $newQty;
        $inventoryCost->average_cost = $newAvgCost;
        $inventoryCost->inventory_value = $newValue;
        $inventoryCost->calculated_at = now();
        $inventoryCost->save();
        
        InventoryCostHistory::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'old_average_cost' => $oldAvgCost,
            'new_average_cost' => $newAvgCost,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'old_inventory_value' => $oldValue,
            'new_inventory_value' => $newValue,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'effective_at' => now()
        ]);
    }

    private function createCostLayer($productId, $warehouseId, $referenceType, $referenceId, $quantity, $unitCost)
    {
        InventoryCostLayer::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'remaining_quantity' => $quantity,
            'status' => 'active',
            'cost_date' => now()
        ]);
    }
    
    private function depleteLayers($productId, $warehouseId, $qtyToDeduct)
    {
        $layers = InventoryCostLayer::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();
            
        foreach ($layers as $layer) {
            if ($qtyToDeduct <= 0) break;
            
            if ($layer->remaining_quantity <= $qtyToDeduct) {
                $qtyToDeduct -= $layer->remaining_quantity;
                $layer->remaining_quantity = 0;
                $layer->status = 'depleted';
                $layer->save();
            } else {
                $layer->remaining_quantity -= $qtyToDeduct;
                $layer->save();
                $qtyToDeduct = 0;
            }
        }
    }
}
