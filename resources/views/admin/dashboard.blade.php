<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-100 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Pending Applications</h3>
                            <p class="text-3xl font-bold">{{ $pendingApplications }}</p>
                        </div>
                        <div class="bg-green-100 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Approved Applications</h3>
                            <p class="text-3xl font-bold">{{ $approvedApplications }}</p>
                        </div>
                        <div class="bg-red-100 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Rejected Applications</h3>
                            <p class="text-3xl font-bold">{{ $rejectedApplications }}</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('admin.tenant-applications') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            View All Applications
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 