<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    protected $tenantDatabase;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // Get the current host and extract domain
            $host = $request->getHost();
            $domain = str_replace('.localhost:8000', '', $host);
            $domain = str_replace('.localhost', '', $domain);

            // Find tenant by domain
            $tenant = DB::connection('mysql')->table('tenants')->where('domain', $domain)->first();
            
            if ($tenant) {
                $this->tenantDatabase = $tenant->database;
                // Configure tenant connection
                config(['database.connections.tenant.database' => $this->tenantDatabase]);
                DB::purge('tenant');
                DB::reconnect('tenant');
            }

            return $next($request);
        });
    }

    protected function ensureTenantConnection()
    {
        if ($this->tenantDatabase) {
            config(['database.connections.tenant.database' => $this->tenantDatabase]);
            DB::purge('tenant');
            DB::reconnect('tenant');
        }
    }

    public function index()
    {
        $this->ensureTenantConnection();
        $products = DB::connection('tenant')->table('products')->get();
        return view('tenant.dashboard', compact('products'));
    }

    public function store(Request $request)
    {
        $this->ensureTenantConnection();
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now()
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        DB::connection('tenant')->table('products')->insert($data);

        return redirect()->back()->with('success', 'Product added successfully');
    }

    public function update(Request $request, $id)
    {
        $this->ensureTenantConnection();
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048'
        ]);

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'updated_at' => now()
        ];

        if ($request->hasFile('image')) {
            // Delete old image if exists
            $product = DB::connection('tenant')->table('products')->find($id);
            if ($product && $product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        DB::connection('tenant')->table('products')->where('id', $id)->update($data);

        return redirect()->back()->with('success', 'Product updated successfully');
    }

    public function destroy($id)
    {
        $this->ensureTenantConnection();
        // Get product to delete image
        $product = DB::connection('tenant')->table('products')->find($id);
        
        if ($product && $product->image) {
            Storage::disk('public')->delete($product->image);
        }

        DB::connection('tenant')->table('products')->where('id', $id)->delete();

        return redirect()->back()->with('success', 'Product deleted successfully');
    }
} 