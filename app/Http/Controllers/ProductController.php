<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        try {
            DB::connection('tenant')->table('products')->insert([
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'image' => $imagePath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('status', 'Product created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit($id)
    {
        try {
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return redirect()->back()->with('error', 'Product not found');
            }

            $data = [
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'updated_at' => now(),
            ];

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                
                $data['image'] = $request->file('image')->store('products', 'public');
            }

            DB::connection('tenant')->table('products')->where('id', $id)->update($data);

            return redirect()->back()->with('status', 'Product updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy($id)
    {
        try {
            $product = DB::connection('tenant')->table('products')->where('id', $id)->first();
            
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }

            // Delete the image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            DB::connection('tenant')->table('products')->where('id', $id)->delete();

            return response()->json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} 