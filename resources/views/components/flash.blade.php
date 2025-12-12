@if (session('status') || session('error') || session('info'))
    <div class="mb-4 space-y-2">
        @if(session('status'))
            <x-card class="border-emerald-700/70 bg-emerald-900/40">
                <p class="text-xs text-emerald-100">
                    {{ session('status') }}
                </p>
            </x-card>
        @endif

        @if(session('error'))
            <x-card class="border-red-700/70 bg-red-900/40">
                <p class="text-xs text-red-100">
                    {{ session('error') }}
                </p>
            </x-card>
        @endif

        @if(session('info'))
            <x-card class="border-indigo-700/70 bg-indigo-900/40">
                <p class="text-xs text-indigo-100">
                    {{ session('info') }}
                </p>
            </x-card>
        @endif
    </div>
@endif
