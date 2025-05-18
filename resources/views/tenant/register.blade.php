<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            <h2 class="text-2xl font-bold text-center mb-6">Sign Up</h2>

            <form method="POST" action="{{ route('tenant.register') }}">
                @csrf

                <!-- First Name -->
                <div class="mb-4">
                    <x-input-label for="first_name" :value="__('First Name')" />
                    <x-text-input id="first_name" class="block mt-1 w-full" type="text" name="first_name" :value="old('first_name')" required autofocus />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                </div>

                <!-- Last Name -->
                <div class="mb-4">
                    <x-input-label for="last_name" :value="__('Last Name')" />
                    <x-text-input id="last_name" class="block mt-1 w-full" type="text" name="last_name" :value="old('last_name')" required />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div class="mb-4">
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Domain Name -->
                <div class="mb-4">
                    <x-input-label for="domain" :value="__('Desired Domain')" />
                    <div class="flex items-center">
                        <x-text-input id="domain" class="block mt-1 w-full" type="text" name="domain" :value="old('domain')" required placeholder="your-business" />
                        <span class="ml-2 text-gray-600">.localhost</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">This will be your unique subdomain (e.g., your-business.localhost)</p>
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-primary-button class="ml-4">
                        {{ __('Sign Up') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout> 