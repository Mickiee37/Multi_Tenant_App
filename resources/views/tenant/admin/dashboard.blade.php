@extends('layouts.app')

@section('content')
<!-- Add SweetAlert2 CSS and JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Product Management') }}</span>
                    <button class="btn btn-primary" onclick="openModal()">Add New Product</button>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($products as $product)
                                <tr data-product-id="{{ $product->id }}">
                                    <td>
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" 
                                                alt="{{ $product->name }}" 
                                                class="img-thumbnail"
                                                style="max-width: 50px;"
                                                onclick="openImageZoom('{{ asset('storage/' . $product->image) }}')"
                                            >
                                        @else
                                            <span>No image</span>
                                        @endif
                                    </td>
                                    <td>{{ $product->name }}</td>
                                    <td>₱{{ number_format($product->price, 2) }}</td>
                                    <td>{{ $product->description }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editProduct({{ $product->id }})">Edit</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteProduct({{ $product->id }})">Delete</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
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

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <img id="zoomedImage" src="" alt="Product image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="productNameToDelete"></span>"?</p>
                <p class="mb-0 text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let deleteModal;
let currentProductId;

document.addEventListener('DOMContentLoaded', function() {
    deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    
    // Set up delete confirmation button handler
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentProductId) {
            fetch(`/admin/products/${currentProductId}`, {
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
                } else if (data.error) {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error deleting product:', error);
                alert('Error deleting product');
            });
            
            // Hide the modal after initiating delete
            deleteModal.hide();
        }
    });
});

function deleteProduct(id) {
    // Show the custom modal
    const productName = document.querySelector(`tr[data-product-id="${id}"] td:nth-child(2)`).textContent;
    document.getElementById('productNameToDelete').textContent = productName;
    currentProductId = id;
    deleteModal.show();
}

function openModal() {
    document.getElementById('productModalLabel').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('currentImage').classList.add('d-none');
    document.getElementById('productForm').action = "{{ route('tenant.products.store') }}";
    document.getElementById('method').value = 'POST';
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

function editProduct(id) {
    fetch(`/admin/products/${id}/edit`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('productId').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('price').value = data.price;
            document.getElementById('description').value = data.description;
            
            if (data.image) {
                document.getElementById('currentImage').classList.remove('d-none');
                document.getElementById('currentImage').querySelector('img').src = `/storage/${data.image}`;
            }

            document.getElementById('productModalLabel').textContent = 'Edit Product';
            document.getElementById('productForm').action = `/admin/products/${data.id}`;
            document.getElementById('method').value = 'PUT';
            
            const productModal = new bootstrap.Modal(document.getElementById('productModal'));
            productModal.show();
        });
}

function openImageZoom(imageSrc) {
    document.getElementById('zoomedImage').src = imageSrc;
    const imageZoomModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    imageZoomModal.show();
}
</script>
@endpush
@endsection 