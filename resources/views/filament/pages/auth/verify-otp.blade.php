<x-filament-panels::page.simple>
    <form wire:submit.prevent="verifyOtp" class="space-y-6">
        <div>
            <label for="otp" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                6-Digit Verification OTP
            </label>
            <div class="mt-1">
                <input
                    type="text"
                    id="otp"
                    wire:model.defer="otp"
                    maxlength="6"
                    placeholder="Enter 6-digit OTP"
                    required
                    autofocus
                    class="block w-full text-center tracking-widest text-lg font-bold rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                />
            </div>
            @error('otp')
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
            Verify OTP
        </button>

        <div class="flex items-center justify-between text-sm">
            <button
                type="button"
                wire:click="resendOtp"
                class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400"
            >
                Resend OTP
            </button>

            <a href="/admin/forgot-password" class="font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">
                Change Number
            </a>
        </div>
    </form>
</x-filament-panels::page.simple>
