import { ref, onMounted, onUnmounted } from 'vue';

export type ScanStatus = 'ready' | 'scanned' | 'not_found';

export function useBarcodeScanner(onScan: (barcode: string) => Promise<boolean> | boolean) {
    const scanStatus = ref<ScanStatus>('ready');
    const lastScanned = ref('');

    let buffer = '';
    let lastKeyTime = 0;
    let resetTimer: ReturnType<typeof setTimeout> | null = null;
    const THRESHOLD = 50; // ms between keystrokes for scanner detection
    const MIN_LENGTH = 4; // minimum barcode length

    function playBeep(success: boolean) {
        try {
            const ctx = new AudioContext();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.frequency.value = success ? 1200 : 400;
            gain.gain.value = 0.1;
            oscillator.start();
            oscillator.stop(ctx.currentTime + (success ? 0.1 : 0.3));
        } catch {
            // Web Audio not available
        }
    }

    function clearStatusAfterDelay() {
        if (resetTimer) clearTimeout(resetTimer);
        resetTimer = setTimeout(() => {
            scanStatus.value = 'ready';
        }, 2000);
    }

    async function handleKeyDown(e: KeyboardEvent) {
        // Ignore if user is typing in an input/textarea (except our hidden scanner field)
        const target = e.target as HTMLElement;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
            return;
        }

        const now = Date.now();

        if (e.key === 'Enter' && buffer.length >= MIN_LENGTH) {
            e.preventDefault();
            const barcode = buffer;
            buffer = '';
            lastScanned.value = barcode;

            const found = await onScan(barcode);
            if (found) {
                scanStatus.value = 'scanned';
                playBeep(true);
            } else {
                scanStatus.value = 'not_found';
                playBeep(false);
            }
            clearStatusAfterDelay();
            return;
        }

        if (e.key.length === 1) {
            if (now - lastKeyTime > THRESHOLD && buffer.length > 0) {
                // Too slow — reset buffer (manual typing, not scanner)
                buffer = '';
            }
            buffer += e.key;
            lastKeyTime = now;
        }
    }

    onMounted(() => {
        document.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown);
        if (resetTimer) clearTimeout(resetTimer);
    });

    return {
        scanStatus,
        lastScanned,
    };
}
