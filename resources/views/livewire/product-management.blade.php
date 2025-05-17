<div>
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
                <tr>
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
                        <button class="btn btn-sm btn-danger" wire:click="confirmDelete({{ $product->id }})">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal @if($showDeleteModal) show @endif" 
         tabindex="-1" 
         role="dialog" 
         style="display: @if($showDeleteModal) block @else none @endif;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="$set('showDeleteModal', false)"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this product?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">Cancel</button>
                    <button type="button" class="btn btn-danger" wire:click="deleteProduct">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Backdrop -->
    @if($showDeleteModal)
    <div class="modal-backdrop fade show"></div>
    @endif
</div> 