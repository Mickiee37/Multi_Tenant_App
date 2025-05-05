@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product Management</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                            Add New Product
                        </button>
                    </div>
                </div>

                <div class="card-body px-0">
                    @if (session('status'))
                        <div class="alert alert-success mx-3" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger mx-3" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px">Image</th>
                                    <th>Name</th>
                                    <th style="width: 120px">Price</th>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 150px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td class="text-center align-middle">
                                            @if($product->image)
                                                <img src="{{ Storage::url($product->image) }}" 
                                                    alt="{{ $product->name }}" 
                                                    class="img-thumbnail"
                                                    style="max-width: 50px;">
                                            @else
                                                <span class="text-muted">No Image</span>
                                            @endif
                                        </td>
                                        <td class="align-middle">{{ $product->name }}</td>
                                        <td class="align-middle">${{ number_format($product->price, 2) }}</td>
                                        <td class="align-middle">{{ $product->description }}</td>
                                        <td class="text-center align-middle">
                                            <button class="btn btn-sm btn-primary me-1" onclick="editProduct({{ $product->id }})">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteProduct({{ $product->id }})">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-3">No products found. Add your first product!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm" method="POST" action="{{ route('tenant.products.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="productId" name="id">
                <input type="hidden" name="_method" id="method" value="POST">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div id="currentImage" class="mt-2 d-none">
                            <img src="" alt="Current product image" class="img-thumbnail" style="max-width: 100px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function editProduct(id) {
    fetch(`/admin/products/${id}/edit`)
        .then(response => response.json())
        .then(product => {
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('price').value = product.price;
            document.getElementById('description').value = product.description;
            document.getElementById('method').value = 'PUT';
            document.getElementById('productForm').action = `/admin/products/${id}`;
            document.getElementById('productModalLabel').textContent = 'Edit Product';
            
            if (product.image) {
                document.getElementById('currentImage').classList.remove('d-none');
                document.getElementById('currentImage').querySelector('img').src = "{{ Storage::url('') }}" + product.image;
            }
            
            var modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        });
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        fetch(`/admin/products/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                location.reload();
            }
        });
    }
}
</script>
@endpush

@push('styles')
<style>
.table th {
    background-color: #f8f9fa;
}
.table td {
    vertical-align: middle;
}
.card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
</style>
@endpush

@endsection 