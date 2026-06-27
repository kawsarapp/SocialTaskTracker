<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" x-data="{ 
        orgName: '{{ old('org_name') }}', 
        slug: '{{ old('org_slug') }}',
        updateSlug() {
            this.slug = this.orgName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        }
    }">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Your Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Organization Name -->
        <div class="mt-4">
            <x-input-label for="org_name" :value="__('Organization Name')" />
            <x-text-input id="org_name" class="block mt-1 w-full" type="text" name="org_name" x-model="orgName" @input="updateSlug()" required placeholder="e.g. Star News" />
            <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
        </div>

        <!-- Organization Slug -->
        <div class="mt-4">
            <x-input-label for="org_slug" :value="__('Organization URL Slug')" />
            <x-text-input id="org_slug" class="block mt-1 w-full" type="text" name="org_slug" x-model="slug" required placeholder="e.g. starnews" />
            <span class="text-[11px] text-gray-400 mt-1 block">Your portal login link will be: <strong class="text-indigo-600" x-text="'http://127.0.0.1:8000/login?org=' + (slug || 'org-slug')"></strong></span>
            <x-input-error :messages="$errors->get('org_slug')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
