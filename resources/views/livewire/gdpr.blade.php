<div>
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2"></div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary" wire:click="exportUserData">
                {{ __('Show My Data') }}
            </button>

            <select
                    class="nice-select dark:focus:ring-blue-500r rounded border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500"
                    wire:change="downloadData($event.target.value)"
                    aria-label="{{ __('Select a download format') }}"
            >
                <option value="" selected disabled>{{ __('Download data as...') }}</option>
                <option value="json">{{ __('JSON') }}</option>
                <option value="csv">{{ __('CSV') }}</option>
                <option value="xml">{{ __('XML') }}</option>
                <option value="html">{{ __('HTML') }}</option>
            </select>
        </div>
    </div>

    <div class="panel">
        @if ($this->userData)
            <div class="panel max-h-[80vh] overflow-auto rounded-md bg-gray-100 p-4 text-sm">
                <pre>
                    <code class="language-json">
                        {{ json_encode(json_decode($this->userData), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                    </code>
                </pre>
            </div>
        @endif
    </div>
</div>
