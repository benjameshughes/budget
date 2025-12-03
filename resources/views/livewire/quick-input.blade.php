<div>
    {{-- Trigger: Hidden input that shows ⌘K hint --}}
    <flux:modal.trigger name="quick-input" shortcut="cmd.k">
        <span></span>
    </flux:modal.trigger>

    {{-- The Command Palette Modal --}}
    <flux:modal
        name="quick-input"
        variant="bare"
        class="w-full max-w-xl my-[15vh]"
        x-on:close-quick-input.window="$flux.modal('quick-input').close()"
    >
        <div
            x-data="voiceInput()"
            class="rounded-xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900"
        >
            {{-- Input Section --}}
            <form wire:submit="submit" class="flex items-center gap-3 p-4">
                <div class="flex-1 relative">
                    <input
                        type="text"
                        wire:model="input"
                        x-ref="input"
                        x-on:voice-result.window="$wire.input = $event.detail; $refs.input.focus()"
                        placeholder="£25 at Tesco for groceries..."
                        class="w-full rounded-lg border-0 bg-transparent py-2 text-lg text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-zinc-100 dark:placeholder-zinc-500"
                        autofocus
                    />
                </div>

                {{-- Microphone Button --}}
                <button
                    type="button"
                    x-on:click="toggleRecording()"
                    x-bind:class="recording ? 'bg-red-500 text-white animate-pulse' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700'"
                    class="flex h-10 w-10 items-center justify-center rounded-full transition-colors"
                    x-bind:title="recording ? 'Stop recording' : 'Start voice input'"
                >
                    <template x-if="!recording">
                        <flux:icon.microphone class="size-5" />
                    </template>
                    <template x-if="recording">
                        <flux:icon.stop class="size-5" />
                    </template>
                </button>

                {{-- Submit Button --}}
                <flux:button type="submit" variant="primary" icon="paper-airplane" />
            </form>

            {{-- Helper Text --}}
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                    <span x-show="!recording">
                        Type or speak: "£10 coffee" • "Paid £50 for electricity" • "Got £500 wages"
                    </span>
                    <span x-show="recording" class="text-red-500">
                        Listening... speak now
                    </span>
                    <div class="flex items-center gap-2">
                        <kbd class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-xs dark:bg-zinc-800">⌘K</kbd>
                        <span>to open</span>
                    </div>
                </div>
            </div>

            {{-- Voice Not Supported Warning --}}
            <template x-if="!supported">
                <div class="border-t border-amber-200 bg-amber-50 px-4 py-2 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                    Voice input not supported in this browser. Try Chrome or Edge.
                </div>
            </template>

            {{-- Network Error Warning --}}
            <template x-if="networkError">
                <div class="border-t border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    Voice input unavailable - can't reach speech servers. Check VPN/firewall or try a different browser.
                </div>
            </template>
        </div>
    </flux:modal>

    <script>
        function voiceInput() {
            return {
                recording: false,
                supported: false,
                recognition: null,
                interimText: '',
                networkError: false,

                init() {
                    // Check for browser support
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    this.supported = !!SpeechRecognition;

                    if (this.supported) {
                        this.recognition = new SpeechRecognition();
                        this.recognition.continuous = true; // Keep listening
                        this.recognition.interimResults = true; // Show partial results
                        this.recognition.lang = 'en-GB';

                        this.recognition.onresult = (event) => {
                            let finalTranscript = '';
                            let interimTranscript = '';

                            for (let i = event.resultIndex; i < event.results.length; i++) {
                                const transcript = event.results[i][0].transcript;
                                if (event.results[i].isFinal) {
                                    finalTranscript += transcript;
                                } else {
                                    interimTranscript += transcript;
                                }
                            }

                            // Update input with interim results so user sees feedback
                            if (interimTranscript) {
                                this.interimText = interimTranscript;
                                window.dispatchEvent(new CustomEvent('voice-result', { detail: interimTranscript }));
                            }

                            // When we get a final result, stop and use it
                            if (finalTranscript) {
                                window.dispatchEvent(new CustomEvent('voice-result', { detail: finalTranscript }));
                                this.stopRecording();
                            }
                        };

                        this.recognition.onerror = (event) => {
                            console.error('Speech recognition error:', event.error);

                            if (event.error === 'network') {
                                // Network error - can't reach Google's servers
                                this.recording = false;
                                this.networkError = true;
                            } else if (event.error !== 'no-speech' && event.error !== 'aborted') {
                                this.recording = false;
                            }
                        };

                        this.recognition.onend = () => {
                            // If still recording and no network error, restart (handles browser auto-stop)
                            if (this.recording && !this.networkError) {
                                try {
                                    this.recognition.start();
                                } catch (e) {
                                    this.recording = false;
                                }
                            }
                        };
                    }
                },

                toggleRecording() {
                    if (!this.supported) return;

                    if (this.recording) {
                        this.stopRecording();
                    } else {
                        this.startRecording();
                    }
                },

                startRecording() {
                    try {
                        this.networkError = false; // Reset on new attempt
                        this.recognition.start();
                        this.recording = true;
                        this.interimText = '';
                    } catch (e) {
                        console.error('Failed to start recognition:', e);
                    }
                },

                stopRecording() {
                    this.recording = false;
                    try {
                        this.recognition.stop();
                    } catch (e) {
                        // Ignore errors on stop
                    }
                }
            };
        }
    </script>
</div>
