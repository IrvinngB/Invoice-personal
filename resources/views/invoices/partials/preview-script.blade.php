<script>
// Shared by the create and edit screens: renders the unsaved form as a PDF in the side panel.
function invoicePreview() {
    return {
        preview: { open: false, loading: false, error: false, url: null, timer: null, controller: null },

        // The edit form spoofs PUT through `_method`; sending that along would route the
        // preview request to the update endpoint instead of the read-only preview one.
        previewPayload() {
            const data = new FormData(document.getElementById('invoiceForm'));
            data.delete('_method');
            return data;
        },

        togglePreview() {
            this.preview.open = !this.preview.open;
            if (this.preview.open) this.refreshPreview(true);
        },

        refreshPreview(immediate = false) {
            if (!this.preview.open) return;
            clearTimeout(this.preview.timer);
            this.preview.timer = setTimeout(() => this.loadPreview(), immediate ? 0 : 600);
        },

        async loadPreview() {
            this.preview.controller?.abort();
            const controller = new AbortController();
            this.preview.controller = controller;
            this.preview.loading = true;
            this.preview.error = false;
            try {
                const res = await fetch('{{ route('invoices.preview-draft') }}', {
                    method: 'POST',
                    body: this.previewPayload(),
                    signal: controller.signal,
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const url = URL.createObjectURL(await res.blob());
                const previous = this.preview.url;
                this.preview.url = url;
                if (previous) URL.revokeObjectURL(previous);
            } catch (e) {
                if (e.name !== 'AbortError') this.preview.error = true;
            } finally {
                if (this.preview.controller === controller) this.preview.loading = false;
            }
        }
    };
}
</script>
