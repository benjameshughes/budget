export default function quickInputState() {
    return {
        recording: false,
        supported: true,
        mediaRecorder: null,
        audioChunks: [],
        transcribing: false,
        transcriptionError: null,
        pausedMedia: [],

        pulseLevel: 0.5,
        pulseInterval: null,

        loading: false,
        loadingMessage: '',
        loadingMessages: [
            'Got your request',
            'Thinking about it',
            'Checking your history',
            'Working it out',
            'Almost there',
        ],
        messageIndex: 0,
        messageInterval: null,

        init() {
            this.$wire.$on('close-quick-input', () => {
                this.stopLoading();
            });

            this.supported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
        },

        pauseAllMedia() {
            this.pausedMedia = [];
            document.querySelectorAll('audio, video').forEach(el => {
                if (!el.paused) {
                    el.pause();
                    this.pausedMedia.push(el);
                }
            });
        },

        resumeAllMedia() {
            this.pausedMedia.forEach(el => {
                el.play().catch(() => {});
            });
            this.pausedMedia = [];
        },

        startPulse() {
            this.pulseInterval = setInterval(() => {
                this.pulseLevel = 0.4 + Math.random() * 0.6;
            }, 150);
        },

        stopPulse() {
            if (this.pulseInterval) {
                clearInterval(this.pulseInterval);
                this.pulseInterval = null;
            }
            this.pulseLevel = 0.5;
        },

        startLoading() {
            this.loading = true;
            this.messageIndex = 0;
            this.loadingMessage = this.loadingMessages[0];

            this.messageInterval = setInterval(() => {
                this.messageIndex++;
                if (this.messageIndex < this.loadingMessages.length) {
                    this.loadingMessage = this.loadingMessages[this.messageIndex];
                }
            }, 800);
        },

        stopLoading() {
            this.loading = false;
            if (this.messageInterval) {
                clearInterval(this.messageInterval);
                this.messageInterval = null;
            }
        },

        async toggleRecording() {
            if (!this.supported || this.loading || this.transcribing) return;

            if (this.recording) {
                this.stopRecording();
            } else {
                await this.startRecording();
            }
        },

        async startRecording() {
            try {
                this.transcriptionError = null;
                this.pauseAllMedia();
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

                this.startPulse();

                this.audioChunks = [];
                this.mediaRecorder = new MediaRecorder(stream, {
                    mimeType: MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : 'audio/mp4'
                });

                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) {
                        this.audioChunks.push(event.data);
                    }
                };

                this.mediaRecorder.onstop = async () => {
                    this.stopPulse();
                    stream.getTracks().forEach(track => track.stop());

                    const audioBlob = new Blob(this.audioChunks, {
                        type: this.mediaRecorder.mimeType
                    });

                    await this.transcribeAudio(audioBlob);
                };

                this.mediaRecorder.start();
                this.recording = true;
            } catch (e) {
                console.error('Failed to start recording:', e);
                this.transcriptionError = 'Microphone access denied. Please allow microphone access.';
                this.supported = false;
                this.resumeAllMedia();
                this.stopPulse();
            }
        },

        stopRecording() {
            this.recording = false;
            if (this.mediaRecorder && this.mediaRecorder.state !== 'inactive') {
                this.mediaRecorder.stop();
            }
        },

        async transcribeAudio(audioBlob) {
            this.transcribing = true;
            this.transcriptionError = null;

            try {
                const formData = new FormData();
                const extension = this.mediaRecorder.mimeType.includes('webm') ? 'webm' : 'm4a';
                formData.append('audio', audioBlob, `recording.${extension}`);

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.content : '';

                const response = await fetch('/api/voice/transcribe', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'include',
                });

                const data = await response.json();

                if (response.ok && data.text) {
                    await this.typeText(data.text);
                } else {
                    this.transcriptionError = data.error || data.message || 'Failed to transcribe audio';
                }
            } catch (e) {
                console.error('Transcription error:', e);
                this.transcriptionError = 'Failed to connect to transcription service';
            } finally {
                this.transcribing = false;
                this.resumeAllMedia();
            }
        },

        async typeText(text) {
            const input = this.$refs.input;
            this.$wire.input = '';
            input.focus();

            for (let i = 0; i < text.length; i++) {
                this.$wire.input += text[i];
                await new Promise(r => setTimeout(r, 20));
            }
        }
    };
}
