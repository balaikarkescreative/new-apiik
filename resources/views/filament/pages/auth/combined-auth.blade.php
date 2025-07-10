<div>
    {{-- Buttons Layout (outside info card) --}}
    <div class="flex flex-col sm:flex-row gap-3 w-full">
        {{-- Masuk Aplikasi Button --}}
        <button
            wire:click="openLoginModal"
            class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-semibold py-3 px-6 rounded-xl shadow-lg transition-all duration-200 flex items-center justify-center gap-2 hover:shadow-xl hover:scale-[1.02]"
        >
            <x-filament::icon
                icon="heroicon-o-arrow-right-on-rectangle"
                class="w-5 h-5 flex-shrink-0"
            />
            <span>Masuk Aplikasi</span>
        </button>

        {{-- Pengajuan Akun Button --}}
        <button
            wire:click="checkOperationalHoursAndOpenRegister"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 px-6 rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
        >
            <x-filament::icon
                icon="heroicon-o-user-plus"
                class="w-5 h-5 flex-shrink-0"
            />
            <span>Pengajuan Akun</span>
        </button>

        {{-- Teleport modals to body --}}
        <template x-teleport="body">
            {{-- Login Modal --}}
            <x-filament::modal
                id="login-modal"
                width="md"
                :close-by-clicking-away="false"
            >
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-arrow-right-on-rectangle"
                            class="h-6 w-6"
                        />
                        Masuk ke Aplikasi
                    </div>
                </x-slot>

                <x-slot name="description">
                    Silakan masukkan username/email dan password Anda
                </x-slot>

                <div class="space-y-4" x-data @keydown.enter="$wire.authenticate()">
                    <x-filament-panels::form wire:submit="authenticate">
                        {{-- Login Form --}}
                        {{ $this->loginForm }}
                        <x-filament::button
                            wire:click="authenticate"
                            size="lg"
                            icon="heroicon-o-arrow-right-on-rectangle"
                            class="w-full"
                            type="submit"
                        >
                            Masuk
                        </x-filament::button>
                    </x-filament-panels::form>
                </div>
            </x-filament::modal>
        </template>

        <template x-teleport="body">

            {{-- Register Modal --}}
            <x-filament::modal
                id="register-modal"
                width="4xl"
                :close-by-clicking-away="false"
            >
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon
                            icon="heroicon-o-user-plus"
                            class="h-6 w-6"
                        />
                        Pengajuan Akun Baru
                    </div>
                </x-slot>

                <x-slot name="description">
                    Silakan isi formulir berikut untuk mengajukan akun baru. Pastikan semua informasi yang diberikan akurat dan lengkap.
                </x-slot>

                <div class="space-y-4" x-data @keydown.enter="$wire.register()">
                    <x-filament-panels::form wire:submit="register">
                        {{ $this->registerForm }}
                    </x-filament-panels::form>
                </div>

                <x-slot name="footerActions">
                    <x-filament::button
                        wire:click="register"
                        size="lg"
                        color="success"
                        icon="heroicon-o-check-circle"
                        class="w-full sm:w-auto"
                    >
                        Kirim Pengajuan
                    </x-filament::button>

                    <x-filament::button
                        wire:click="closeRegisterModal"
                        color="gray"
                        outlined
                    >
                        Batal
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        </template>
    </div>
</div>
