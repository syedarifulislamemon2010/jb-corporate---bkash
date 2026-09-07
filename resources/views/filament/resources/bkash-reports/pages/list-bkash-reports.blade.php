<x-filament-panels::page>
    <div class="jb-batch-container">
        <!-- Exact Same High-Contrast Tabs Bar as bkash-transactions -->
        <div class="jb-tabs-bar">
            <div class="jb-tabs-group">
                <button
                    type="button"
                    wire:click="$set('activeTab', 'all')"
                    class="jb-tab-all {{ ($activeTab === 'all' || empty($activeTab)) ? 'active' : '' }}"
                >
                    All Transactions
                </button>
                <button
                    type="button"
                    wire:click="$set('activeTab', 'a2a')"
                    class="jb-tab-a2a {{ $activeTab === 'a2a' ? 'active' : '' }}"
                >
                    Account to Account (A2A) - Janata Bank PLC.
                </button>
                <button
                    type="button"
                    wire:click="$set('activeTab', 'beftn')"
                    class="jb-tab-beftn {{ $activeTab === 'beftn' ? 'active' : '' }}"
                >
                    BEFTN
                </button>
                <button
                    type="button"
                    wire:click="$set('activeTab', 'rtgs')"
                    class="jb-tab-rtgs {{ $activeTab === 'rtgs' ? 'active' : '' }}"
                >
                    RTGS
                </button>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>