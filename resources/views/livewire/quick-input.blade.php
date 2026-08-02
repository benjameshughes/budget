<div>
    <flux:modal.trigger name="quick-input" shortcut="cmd.k">
        <span></span>
    </flux:modal.trigger>

    <flux:modal
        name="quick-input"
        variant="bare"
        class="w-full max-w-xl my-[15vh] shadow rounded"
        x-on:close-quick-input.window="$flux.modal('quick-input').close()"
    >
        <div x-data="quickInputState" class="rounded-xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <form wire:submit="submit" x-on:submit="startLoading()" class="flex items-center gap-3 p-4">
                <div class="flex-1 relative">
                    <input
                        type="text"
                        wire:model="input"
                        x-ref="input"
                        x-on:voice-result.window="$wire.input = $event.detail; $refs.input.focus()"
                        placeholder="£25 at Tesco for groceries..."
                        class="w-full rounded-lg border-0 bg-transparent py-2 text-lg text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100 dark:placeholder-zinc-500"
                        x-bind:disabled="loading || transcribing"
                        autofocus
                    />
                </div>

                <button
                    type="button"
                    x-on:click="toggleRecording()"
                    x-bind:class="transcribing ? 'bg-blue-500 text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700'"
                    x-bind:style="recording ? `background-color: rgba(239, 68, 68, ${pulseLevel}); color: white;` : ''"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition-colors duration-100"
                    x-bind:title="recording ? 'Stop recording' : transcribing ? 'Transcribing...' : 'Start voice input (⌘M)'"
                    x-bind:disabled="loading || transcribing"
                >
                    <template x-if="!recording && !transcribing">
                        <flux:icon.microphone class="size-5" />
                    </template>
                    <template x-if="recording">
                        <flux:icon.stop class="size-5" />
                    </template>
                    <template x-if="transcribing">
                        <svg class="size-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                </button>

                <flux:button type="submit" variant="primary" icon="paper-airplane" x-bind:disabled="loading || transcribing" loading />
            </form>

            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span x-show="!recording && !loading && !transcribing" x-cloak>
                        Type or speak: "£10 coffee" | "Paid £50 for electricity" | "Got £500 wages"
                    </span>
                    <span x-show="recording" x-cloak class="text-red-500">
                        Recording... click stop when done
                    </span>
                    <span x-show="transcribing" x-cloak class="text-blue-500">
                        Transcribing your voice...
                    </span>
                    <span x-show="loading" x-cloak class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <span class="inline-flex">
                            <span class="animate-[bounce_1s_ease-in-out_infinite]">.</span>
                            <span class="animate-[bounce_1s_ease-in-out_0.1s_infinite]">.</span>
                            <span class="animate-[bounce_1s_ease-in-out_0.2s_infinite]">.</span>
                        </span>
                        <span x-text="loadingMessage" class="transition-opacity duration-300"></span>
                    </span>
                    <div class="flex items-center gap-2" x-show="!loading && !transcribing" x-cloak>
                        <kbd class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-xs dark:bg-zinc-800">⌘K</kbd>
                        <span>to open</span>
                    </div>
                </div>
            </div>

            <template x-if="!supported">
                <div class="border-t border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                    Voice input not supported in this browser (requires microphone access).
                </div>
            </template>

            <template x-if="transcriptionError">
                <div class="border-t border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <span x-text="transcriptionError"></span>
                </div>
            </template>
        </div>
    </flux:modal>
</div>
