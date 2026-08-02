export default function advisorTerminal() {
    return {
        output: '',
        loading: false,

        startStream(transactionId) {
            this.output = '';
            this.loading = true;

            const eventSource = new EventSource(`/advisor/stream/${transactionId}`);

            eventSource.addEventListener('text_delta', (event) => {
                const data = JSON.parse(event.data);
                this.output += data.delta;
            });

            eventSource.addEventListener('stream_end', () => {
                this.loading = false;
                eventSource.close();
            });

            eventSource.onerror = () => {
                this.loading = false;
                eventSource.close();
            };
        }
    };
}
