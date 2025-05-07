@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Upgrade Your Subscription</h3>
                    <p class="card-text">Choose a plan that fits your needs and take your business to the next level.</p>
                    <div class="alert alert-info">
                        <strong>Current Plan:</strong> {{ ucfirst($currentPlan) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($plans as $planKey => $plan)
            <div class="col-md-3 mb-4">
                <div class="card h-100 {{ $currentPlan == $planKey ? 'border-primary' : '' }}">
                    <div class="card-header {{ $currentPlan == $planKey ? 'bg-primary text-white' : '' }}">
                        <h4 class="my-0 fw-normal">{{ $plan['name'] }}</h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h1 class="card-title pricing-card-title">${{ number_format($plan['monthlyPrice'], 2) }}<small class="text-muted fw-light">/mo</small></h1>
                        <ul class="list-unstyled mt-3 mb-4">
                            @foreach($plan['features'] as $feature)
                                <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-auto">
                            @if($currentPlan == $planKey)
                                <button type="button" class="w-100 btn btn-outline-primary" disabled>Current Plan</button>
                            @else
                                <form action="{{ url('/admin/subscription/upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $planKey }}">
                                    <button type="submit" class="w-100 btn btn-primary">
                                        {{ $planKey == 'basic' ? 'Downgrade' : 'Upgrade' }} to {{ $plan['name'] }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4>Plan Comparison</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    @foreach($plans as $planKey => $plan)
                                        <th class="{{ $currentPlan == $planKey ? 'table-primary' : '' }}">{{ $plan['name'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Price</td>
                                    @foreach($plans as $planKey => $plan)
                                        <td class="{{ $currentPlan == $planKey ? 'table-primary' : '' }}">${{ number_format($plan['monthlyPrice'], 2) }}/month</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Product Limit</td>
                                    @foreach($plans as $planKey => $plan)
                                        <td class="{{ $currentPlan == $planKey ? 'table-primary' : '' }}">{{ $plan['productLimit'] }}</td>
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