@extends('layouts.app')

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('warning') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domain</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($applications as $application)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $application->first_name }} {{ $application->last_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $application->email }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $application->domain }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $application->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $application->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $application->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $application->status === 'deactivated' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                {{ ucfirst($application->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($application->status === 'approved' && isset($application->tenant))
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ ($application->tenant->subscription_plan ?? 'basic') === 'basic' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ ($application->tenant->subscription_plan ?? '') === 'pro' ? 'bg-indigo-100 text-indigo-800' : '' }}
                                                    {{ ($application->tenant->subscription_plan ?? '') === 'premium' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ ($application->tenant->subscription_plan ?? '') === 'enterprise' ? 'bg-pink-100 text-pink-800' : '' }}">
                                                    {{ ucfirst($application->tenant->subscription_plan ?? 'basic') }}
                                                </span>
                                            @else
                                                <span class="text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if($application->status === 'pending')
                                                <form action="{{ route('admin.tenant-applications.approve', $application->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded mr-2">
                                                        Approve
                                                    </button>
                                                </form>
                                                
                                                <button onclick="showRejectModal({{ $application->id }})" 
                                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded">
                                                    Reject
                                                </button>
                                            @endif
                                            @if($application->status === 'approved')
                                                @php
                                                    $tenantId = $application->tenant?->id;
                                                    \Log::info('Tenant data:', [
                                                        'application_id' => $application->id,
                                                        'tenant' => $application->tenant,
                                                        'tenant_id' => $tenantId
                                                    ]);
                                                @endphp
                                                @if($tenantId)
                                                    <a href="{{ route('admin.tenant.manage-plan', $tenantId) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded mr-2">
                                                        Manage Plan
                                                    </a>
                                                    <form action="{{ route('admin.tenant.deactivate', ['id' => $tenantId]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to deactivate this tenant? This will prevent them from accessing their account.');">
                                                        @csrf
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Deactivate</button>
                                                    </form>
                                                @else
                                                    <span class="text-red-600">Unable to manage - No tenant ID found</span>
                                                @endif
                                                <a href="http://{{ $application->domain }}.localhost:8000" target="_blank" class="text-blue-600 hover:text-blue-900 ml-3">Visit Site</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No tenant applications found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Reject Application
                                </h3>
                                <div class="mt-2">
                                    <textarea name="rejection_reason" 
                                        rows="3" 
                                        class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md" 
                                        placeholder="Enter reason for rejection (optional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Reject
                        </button>
                        <button type="button" onclick="hideRejectModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showRejectModal(applicationId) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            form.action = `/admin/tenant-applications/${applicationId}/reject`;
            modal.classList.remove('hidden');
        }

        function hideRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
        }
    </script>
@endsection