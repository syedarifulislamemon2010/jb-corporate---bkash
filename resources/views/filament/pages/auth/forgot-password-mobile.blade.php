<x-filament-panels::page.simple>
    <form wire:submit.prevent="sendOtp" class="space-y-6">
        <div>
            <label for="mobile_no" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Registered Mobile Number
            </label>
            <div class="mt-1">
                <input
                    type="text"
                    id="mobile_no"
                    wire:model.defer="mobile_no"
                    placeholder="e.g. 01712345678"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                />
            </div>
            @error('mobile_no')
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
            Send OTP
        </button>

        <div class="text-center text-sm">
            <a href="/admin/login" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                &larr; Back to Login
            </a>
        </div>
    </form>
</x-filament-panels::page.simple>
