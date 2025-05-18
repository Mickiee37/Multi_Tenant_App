@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Subscription Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Current Plan:</strong> {{ ucfirst($tenant->subscription_plan ?? 'basic') }}</p>
                            <p class="mb-1"><strong>Product Limit:</strong> {{ count($products) }} of {{ $tenant->getProductLimit() }} used</p>
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar {{ count($products) < $tenant->getProductLimit() ? 'bg-success' : 'bg-danger' }}" role="progressbar" 
                                    style="width: {{ (count($products) / $tenant->getProductLimit()) * 100 }}%" 
                                    aria-valuenow="{{ count($products) }}" aria-valuemin="0" aria-valuemax="{{ $tenant->getProductLimit() }}"></div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            @if(count($products) >= $tenant->getProductLimit())
                                <p class="text-danger mb-2">You've reached your product limit!</p>
                                <a href="{{ route(request()->getHost() . '.subscription.upgrade-page') }}" class="btn btn-warning">Upgrade Now</a>
                            @else
                                <p class="text-success mb-2">You can add {{ $tenant->getProductLimit() - count($products) }} more product(s)</p>
                                @if($tenant->subscription_plan != 'enterprise')
                                    <a href="{{ route(request()->getHost() . '.subscription.upgrade-page') }}" class="btn btn-outline-primary">Upgrade for More</a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme Customization Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Theme Customization</h5>
                        <span class="badge bg-info">{{ ucfirst($tenant->subscription_plan ?? 'basic') }} Plan</span>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $themesAvailable = $tenant->getThemeLimit();
                        $currentTheme = $tenant->getTheme();
                    @endphp
                    
                    <p>
                        @if(($tenant->subscription_plan ?? 'basic') == 'basic')
                            Your Basic plan includes the default theme only.
                        @elseif(($tenant->subscription_plan ?? '') == 'pro')
                            Your Pro plan includes access to 2 themes.
                        @elseif(($tenant->subscription_plan ?? '') == 'premium')
                            Your Premium plan includes access to 5 themes.
                        @elseif(($tenant->subscription_plan ?? '') == 'enterprise')
                            Your Enterprise plan includes access to unlimited themes and customizations.
                        @endif
                    </p>
                    
                    <form id="themeForm" method="POST" action="{{ url('/admin/theme/update') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="themeSelect" class="form-label">Select Theme</label>
                                    <select id="themeSelect" name="theme" class="form-select" data-current="{{ $currentTheme }}">
                                        <option value="default" {{ $currentTheme == 'default' ? 'selected' : '' }}>Default</option>
                                        <option value="dark" {{ $currentTheme == 'dark' ? 'selected' : '' }} {{ $themesAvailable < 2 ? 'disabled' : '' }}>Dark Mode</option>
                                        <option value="light" {{ $currentTheme == 'light' ? 'selected' : '' }} {{ $themesAvailable < 2 ? 'disabled' : '' }}>Light Mode</option>
                                        <option value="blue" {{ $currentTheme == 'blue' ? 'selected' : '' }} {{ $themesAvailable < 3 ? 'disabled' : '' }}>Blue Ocean</option>
                                        <option value="green" {{ $currentTheme == 'green' ? 'selected' : '' }} {{ $themesAvailable < 3 ? 'disabled' : '' }}>Green Forest</option>
                                        <option value="purple" {{ $currentTheme == 'purple' ? 'selected' : '' }} {{ $themesAvailable < 5 ? 'disabled' : '' }}>Purple Elegance</option>
                                        <option value="red" {{ $currentTheme == 'red' ? 'selected' : '' }} {{ $themesAvailable < 5 ? 'disabled' : '' }}>Red Passion</option>
                                        <option value="orange" {{ $currentTheme == 'orange' ? 'selected' : '' }} {{ $themesAvailable < 5 ? 'disabled' : '' }}>Orange Sunset</option>
                                        <option value="custom" {{ $currentTheme == 'custom' ? 'selected' : '' }} {{ $themesAvailable < 10 ? 'disabled' : '' }}>Custom Theme</option>
                                    </select>
                                    <small class="d-none">Tenant ID: {{ $tenant->id }}, Current Theme: <span id="current-theme-info">{{ $currentTheme }}</span></small>
                                    @if($themesAvailable < 5)
                                        <small class="text-muted">Upgrade to access more themes</small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="row theme-preview">
                                    <div class="col-md-6">
                                        <div class="card mb-3 theme-card" style="background-color: #f8f9fa; border: 1px solid #ddd;">
                                            <div class="card-body">
                                                <h5 class="card-title">Theme Preview</h5>
                                                <p class="card-text">This is how your theme will look.</p>
                                                <button type="button" class="btn btn-primary">Sample Button</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mt-3">
                                        <p><strong>Theme Features:</strong></p>
                                        <ul>
                                            <li>Color scheme customization</li>
                                            <li>Navigation style options</li>
                                            <li>Font selection</li>
                                            @if($themesAvailable >= 5)
                                                <li>Custom header images</li>
                                                <li>Widget positioning</li>
                                            @endif
                                            @if($themesAvailable >= 10)
                                                <li>Advanced CSS customization</li>
                                                <li>White label options</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" id="saveThemeBtn" class="btn btn-primary">Save Theme Settings</button>
                            @if($themesAvailable < 5)
                                <a href="{{ route(request()->getHost() . '.subscription.upgrade-page') }}" class="btn btn-outline-primary ms-2">Upgrade for More Themes</a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product Management</h5>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" {{ count($products) >= $tenant->getProductLimit() ? 'disabled' : '' }}>
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
                            @if(str_contains(session('error'), 'Database schema issue'))
                                Database schema issue detected.
                                <button type="button" class="btn btn-sm btn-warning" onclick="fixDatabaseSchema()">
                                    Click here to fix automatically
                                </button>
                                <span id="schema-fix-status"></span>
                            @else
                                {{ session('error') }}
                            @endif
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
                                    <tr data-product-id="{{ $product->id }}">
                                        <td class="text-center align-middle">
                                            @if(isset($product->image) && $product->image)
                                                <img src="{{ Storage::url($product->image) }}" 
                                                    alt="{{ $product->name }}" 
                                                    class="img-thumbnail product-thumbnail"
                                                    style="max-width: 50px; cursor: pointer;"
                                                    onclick="showImageModal('{{ Storage::url($product->image) }}', '{{ $product->name }}')"
                                                    data-bs-toggle="tooltip"
                                                    title="Click to enlarge">
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

<!-- Image Zoom Modal -->
<div class="modal fade" id="imageZoomModal" tabindex="-1" aria-labelledby="imageZoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageZoomModalLabel">Product Image</h5>
                <div class="zoom-controls me-auto ms-3">
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="zoomImage(0.9)" title="Zoom out">
                        <i class="bi bi-zoom-out"></i> -
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="resetZoom()" title="Reset zoom">
                        <i class="bi bi-arrows-fullscreen"></i> Reset
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light" onclick="zoomImage(1.1)" title="Zoom in">
                        <i class="bi bi-zoom-in"></i> +
                    </button>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="image-container">
                    <img src="" id="zoomedImage" class="img-fluid" alt="Zoomed product image">
                </div>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Click and drag to pan image when zoomed</small>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

// Add an extra check to see if the theme needs to be loaded due to caching
document.addEventListener('DOMContentLoaded', function() {
    // If we have a theme success message but the theme doesn't look applied, try reloading
    const successAlert = document.querySelector('.alert-success');
    if (successAlert && successAlert.textContent.includes('Theme updated')) {
        // Get the current theme from the theme select
        const themeSelect = document.getElementById('themeSelect');
        const currentTheme = themeSelect ? themeSelect.value : null;
        
        // Check if theme actually looks applied (basic check)
        let themeAppearsMissing = false;
        if (currentTheme === 'dark') {
            // If page background is still light when it should be dark
            const bodyBgColor = getComputedStyle(document.body).backgroundColor;
            if (bodyBgColor.includes('255') || bodyBgColor.includes('rgb(248')) {
                themeAppearsMissing = true;
            }
        }
        
        // If theme doesn't appear to be applied, try a hard reload
        if (themeAppearsMissing) {
            console.log('Theme appears to be missing despite update. Forcing reload...');
            location.reload(true);
        }
    }

    // Initialize delete modal
    deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
    
    // Set up delete confirmation button handler
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentProductId) {
            fetch(`/admin/products/${currentProductId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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

    // Theme preview functionality
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect) {
        themeSelect.addEventListener('change', updateThemePreview);
        // Initialize theme preview
        updateThemePreview();
    }
});

// Force proper styling for selects when in dark mode
if (window.location.href.includes('force_theme=dark')) {
    // Fix the dropdown immediately
    document.addEventListener('DOMContentLoaded', function() {
        // Add data attribute to body for CSS targeting
        document.body.setAttribute('data-theme', 'dark');
        
        // Target all select elements to ensure visibility
        const selects = document.querySelectorAll('select, .form-select');
        selects.forEach(select => {
            // Add direct styling
            select.style.backgroundColor = '#343a40';
            select.style.color = 'white';
            select.style.borderColor = '#6c757d';
            
            // Add class for CSS targeting
            select.classList.add('theme-dark');
            
            // Options need to be styled as well
            Array.from(select.options).forEach(option => {
                option.style.backgroundColor = '#2c3034';
                option.style.color = 'white';
            });
        });
    });
    
    // Also run immediately in case page is already loaded
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        const selects = document.querySelectorAll('select, .form-select');
        selects.forEach(select => {
            select.style.backgroundColor = '#343a40';
            select.style.color = 'white';
            select.style.borderColor = '#6c757d';
        });
    }
}

function fixDatabaseSchema() {
    var statusEl = document.getElementById('schema-fix-status');
    statusEl.innerHTML = '<span class="text-info ms-2">Fixing schema...</span>';
    
    // Make an AJAX call to a route that will fix the schema
    fetch('/admin/products/fix-schema', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusEl.innerHTML = '<span class="text-success ms-2">Schema fixed successfully! Reloading...</span>';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            statusEl.innerHTML = '<span class="text-danger ms-2">Failed to fix schema. Please try again.</span>';
        }
    })
    .catch(error => {
        console.error('Error fixing schema:', error);
        statusEl.innerHTML = '<span class="text-danger ms-2">An error occurred. Please refresh the page and try again.</span>';
    });
}

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
            } else {
                document.getElementById('currentImage').classList.add('d-none');
            }
            
            var modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching product:', error);
            alert('Error loading product data');
        });
}

function deleteProduct(id) {
    // Show the custom modal
    const productName = document.querySelector(`tr[data-product-id="${id}"] td:nth-child(2)`).textContent;
    document.getElementById('productNameToDelete').textContent = productName;
    currentProductId = id;
    deleteModal.show();
}

// Theme preview functionality
function updateThemePreview() {
    const themeSelect = document.getElementById('themeSelect');
    const themeCard = document.querySelector('.theme-card');
    const themeButton = themeCard.querySelector('.btn-primary');
    
    // Reset all styles
    themeCard.style.backgroundColor = '#f8f9fa';
    themeCard.style.color = '#212529';
    themeCard.style.border = '1px solid #ddd';
    themeButton.style.backgroundColor = '#0d6efd';
    themeButton.style.borderColor = '#0d6efd';
    themeButton.style.color = '#fff';
    
    // Apply selected theme styles
    switch(themeSelect.value) {
        case 'dark':
            themeCard.style.backgroundColor = '#343a40';
            themeCard.style.color = '#f8f9fa';
            themeCard.style.border = '1px solid #495057';
            themeButton.style.backgroundColor = '#6c757d';
            themeButton.style.borderColor = '#6c757d';
            break;
        case 'light':
            themeCard.style.backgroundColor = '#ffffff';
            themeCard.style.color = '#212529';
            themeCard.style.border = '1px solid #e9ecef';
            themeButton.style.backgroundColor = '#0d6efd';
            themeButton.style.borderColor = '#0d6efd';
            break;
        case 'blue':
            themeCard.style.backgroundColor = '#e7f5ff';
            themeCard.style.color = '#0c63e4';
            themeCard.style.border = '1px solid #aed9ff';
            themeButton.style.backgroundColor = '#0c63e4';
            themeButton.style.borderColor = '#0c63e4';
            break;
        case 'green':
            themeCard.style.backgroundColor = '#ebfbee';
            themeCard.style.color = '#187741';
            themeCard.style.border = '1px solid #b7e4c7';
            themeButton.style.backgroundColor = '#198754';
            themeButton.style.borderColor = '#198754';
            break;
        case 'purple':
            themeCard.style.backgroundColor = '#f5f3ff';
            themeCard.style.color = '#6d28d9';
            themeCard.style.border = '1px solid #c7d2fe';
            themeButton.style.backgroundColor = '#6f42c1';
            themeButton.style.borderColor = '#6f42c1';
            break;
        case 'red':
            themeCard.style.backgroundColor = '#fff5f5';
            themeCard.style.color = '#e02d44';
            themeCard.style.border = '1px solid #fcc';
            themeButton.style.backgroundColor = '#dc3545';
            themeButton.style.borderColor = '#dc3545';
            break;
        case 'orange':
            themeCard.style.backgroundColor = '#fff7ed';
            themeCard.style.color = '#c2410c';
            themeCard.style.border = '1px solid #fed7aa';
            themeButton.style.backgroundColor = '#fd7e14';
            themeButton.style.borderColor = '#fd7e14';
            break;
        case 'custom':
            themeCard.style.backgroundColor = '#fdf2f8';
            themeCard.style.color = '#be185d';
            themeCard.style.border = '1px solid #fbcfe8';
            themeButton.style.backgroundColor = '#d63384';
            themeButton.style.borderColor = '#d63384';
            break;
        default: // Default theme
            // Leave default styling
            break;
    }
}

// Add theme form submission handler
function submitThemeForm() {
    document.getElementById('themeForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent the normal form submission
        
        const submitButton = this.querySelector('button[type="submit"]');
        const themeSelect = document.getElementById('themeSelect');
        const currentTheme = themeSelect.getAttribute('data-current');
        const newTheme = themeSelect.value;
        
        // Skip if no change
        if (currentTheme === newTheme) {
            alert('No changes to theme were made.');
            return;
        }
        
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // For immediate effect, apply theme classes directly before AJAX call
        document.body.dataset.applyingTheme = newTheme;
        
        if (newTheme === 'dark') {
            document.body.style.backgroundColor = '#343a40';
            document.body.style.color = '#f8f9fa';
            document.querySelectorAll('.card').forEach(card => {
                card.style.backgroundColor = '#2c3034';
                card.style.borderColor = '#495057';
                card.style.color = '#f8f9fa';
            });
        } else if (newTheme === 'red') {
            document.body.style.backgroundColor = '#fff5f5';
            document.body.style.color = '#e02d44';
        }
        
        // Use fetch API for AJAX submission
        fetch('/admin/theme/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                theme: newTheme
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success';
                alertDiv.textContent = data.message;
                
                // Insert at the top of the card
                const cardBody = document.querySelector('.card-body');
                cardBody.insertBefore(alertDiv, cardBody.firstChild);
                
                // Force a complete page reload with the force_theme parameter
                setTimeout(() => {
                    window.location.href = '/admin/tenant-dashboard?theme_updated=1&force_theme=' + newTheme;
                }, 500);
            } else {
                alert(data.message || 'Error updating theme');
                submitButton.disabled = false;
                submitButton.textContent = 'Save Theme Settings';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the theme');
            submitButton.disabled = false;
            submitButton.textContent = 'Save Theme Settings';
        });
    });
}

// Initialize both functions on page load
document.addEventListener('DOMContentLoaded', function() {
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect) {
        themeSelect.addEventListener('change', updateThemePreview);
        updateThemePreview();
        submitThemeForm();
    }
});

// Image zoom functionality
let currentZoom = 1;
let isDragging = false;
let startX, startY, translateX = 0, translateY = 0;

function showImageModal(imageUrl, productName) {
    // Reset zoom state
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    
    // Set the image source
    const img = document.getElementById('zoomedImage');
    img.src = imageUrl;
    img.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
    
    // Set the modal title to the product name
    document.getElementById('imageZoomModalLabel').textContent = productName;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
    modal.show();
    
    // Setup drag events for panning
    setupDragEvents();
}

function zoomImage(factor) {
    currentZoom *= factor;
    // Limit zoom levels
    currentZoom = Math.max(0.5, Math.min(currentZoom, 3));
    updateImageTransform();
}

function resetZoom() {
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
}

function updateImageTransform() {
    const img = document.getElementById('zoomedImage');
    img.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
}

function setupDragEvents() {
    const img = document.getElementById('zoomedImage');
    const container = img.parentElement;
    
    container.addEventListener('mousedown', startDrag);
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', endDrag);
    
    // Touch events for mobile
    container.addEventListener('touchstart', startDrag);
    document.addEventListener('touchmove', drag);
    document.addEventListener('touchend', endDrag);
}

function startDrag(e) {
    if (currentZoom <= 1) return; // Only allow dragging when zoomed in
    
    isDragging = true;
    
    if (e.type === 'touchstart') {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
    } else {
        startX = e.clientX;
        startY = e.clientY;
    }
    
    e.preventDefault();
}

function drag(e) {
    if (!isDragging) return;
    
    let clientX, clientY;
    
    if (e.type === 'touchmove') {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    
    const deltaX = (clientX - startX) / currentZoom;
    const deltaY = (clientY - startY) / currentZoom;
    
    translateX += deltaX;
    translateY += deltaY;
    
    startX = clientX;
    startY = clientY;
    
    updateImageTransform();
    e.preventDefault();
}

function endDrag() {
    isDragging = false;
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
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

/* Fix dropdowns for various themes */
.form-select, select {
    appearance: auto !important; /* Ensure native dropdown arrow shows */
}

/* Dark theme dropdown fix */
body[data-theme="dark"] .form-select option {
    background-color: #2c3034 !important;
    color: #f8f9fa !important;
}

/* Dark theme dropdown fix - alternative approach */
@media (prefers-color-scheme: dark) {
    .form-select option {
        background-color: #2c3034 !important;
        color: #f8f9fa !important;
    }
}

/* Style the dropdown to match theme */
.form-select {
    padding-right: 2rem !important;
}

/* Image zoom functionality styles */
.product-thumbnail {
    transition: transform 0.2s ease;
    border: 2px solid transparent;
}
.product-thumbnail:hover {
    transform: scale(1.1);
    border-color: #0d6efd;
    box-shadow: 0 0 5px rgba(13, 110, 253, 0.5);
}

#imageZoomModal .modal-content {
    background-color: rgba(0,0,0,0.8);
    border: none;
}

#imageZoomModal .modal-header {
    border-bottom: none;
    background-color: rgba(0,0,0,0.8);
    color: white;
}

#imageZoomModal .modal-body {
    padding: 0;
    position: relative;
    background-color: rgba(0,0,0,0.5);
    overflow: hidden;
}

#imageZoomModal .modal-footer {
    border-top: none;
    background-color: rgba(0,0,0,0.8);
    color: white;
}

.image-container {
    position: relative;
    height: 60vh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

#zoomedImage {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    transition: transform 0.2s ease-out;
    cursor: grab;
}

#zoomedImage:active {
    cursor: grabbing;
}

.zoom-controls {
    display: flex;
    gap: 5px;
}

/* For dark theme */
[data-theme="dark"] #imageZoomModal .modal-body {
    background-color: rgba(33,37,41,0.7);
}

[data-theme="dark"] #imageZoomModal .modal-header,
[data-theme="dark"] #imageZoomModal .modal-footer {
    background-color: rgba(33,37,41,0.9);
}
</style>
@endpush

@endsection 