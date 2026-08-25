<x-filament-panels::page.simple>
    <form wire:submit.prevent="setNewPassword" class="space-y-6">
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                New Password
            </label>
            <div class="mt-1">
                <input
                    type="password"
                    id="password"
                    wire:model.defer="password"
                    placeholder="Enter new password (min. 8 chars)"
                    required
                    autofocus
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                />
            </div>
            @error('password')
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Confirm New Password
            </label>
            <div class="mt-1">
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model.defer="password_confirmation"
                    placeholder="Confirm new password"
                    required
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                />
            </div>
            @error('password_confirmation')
                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="flex w-full justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
            Reset Password
        </button>
    </form>
</x-filament-panels::page.simple>
