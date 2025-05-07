@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Manage Subscription Plan</h4>
                    <a href="{{ route('admin.tenant-applications') }}" class="btn btn-secondary btn-sm">Back to Applications</a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Tenant Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> {{ $tenant->name }}</p>
                                <p><strong>Domain:</strong> {{ $tenant->domain }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Plan:</strong> {{ ucfirst($tenant->subscription_plan ?? 'basic') }}</p>
                                <p><strong>Expires:</strong> {{ $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('Y-m-d') : 'Not set' }}</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.tenant.update-plan', $tenant->id) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="plan" class="form-label">Subscription Plan</label>
                                    <select name="plan" id="plan" class="form-select">
                                        @foreach($plans as $planKey => $plan)
                                            <option value="{{ $planKey }}" {{ ($tenant->subscription_plan ?? 'basic') == $planKey ? 'selected' : '' }}>
                                                {{ $plan['name'] }} - ${{ number_format($plan['monthlyPrice'], 2) }}/month ({{ $plan['productLimit'] }} products)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expires_at" class="form-label">Expiration Date</label>
                                    <input type="date" id="expires_at" name="expires_at" class="form-control" 
                                           value="{{ $tenant->subscription_expires_at ? $tenant->subscription_expires_at->format('Y-m-d') : now()->addYear()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Update Subscription Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Plan Comparison</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    @foreach($plans as $planKey => $plan)
                                        <th class="{{ ($tenant->subscription_plan ?? 'basic') == $planKey ? 'table-primary' : '' }}">
                                            {{ $plan['name'] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Monthly Price</td>
                                    @foreach($plans as $planKey => $plan)
                                        <td class="{{ ($tenant->subscription_plan ?? 'basic') == $planKey ? 'table-primary' : '' }}">
                                            ${{ number_format($plan['monthlyPrice'], 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Product Limit</td>
                                    @foreach($plans as $planKey => $plan)
                                        <td class="{{ ($tenant->subscription_plan ?? 'basic') == $planKey ? 'table-primary' : '' }}">
                                            {{ $plan['productLimit'] }}
                                        </td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 