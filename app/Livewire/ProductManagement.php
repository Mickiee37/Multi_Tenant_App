<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductManagement extends Component
{
    public $products = [];
    public $showDeleteModal = false;
    public $productToDelete = null;

    public function mount()
    {
        $this->loadProducts();
    }

    public function loadProducts()
    {
        $tenant = tenant();
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $this->products = DB::connection('tenant')
            ->table('products')
            ->get();
    }

    public function confirmDelete($productId)
    {
        $this->productToDelete = $productId;
        $this->showDeleteModal = true;
    }

    public function deleteProduct()
    {
        try {
            $tenant = tenant();
            config(['database.connections.tenant.database' => $tenant->database]);
            DB::purge('tenant');
            DB::reconnect('tenant');

            $product = DB::connection('tenant')
                ->table('products')
                ->where('id', $this->productToDelete)
                ->first();

            if ($product && $product->image) {
                Storage::disk('public')->delete($product->image);
            }

            DB::connection('tenant')
                ->table('products')
                ->where('id', $this->productToDelete)
                ->delete();

            $this->showDeleteModal = false;
            $this->productToDelete = null;
            $this->loadProducts();

            session()->flash('status', 'Product deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete product. ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.product-management');
    }
} 