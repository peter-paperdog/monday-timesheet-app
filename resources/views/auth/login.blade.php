<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')"/>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="flex items-center justify-center h-screen">
            <x-primary-button class="ms-3">
                <a href="/login/google" class="ms-3">Login with Google</a>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
