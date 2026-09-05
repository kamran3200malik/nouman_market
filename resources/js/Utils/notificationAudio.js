/**
 * Elegant Notification Chime / Beep Synthesizer using Web Audio API
 * Produces a crystal-clear, luxury bell/chime sound across all modern browsers
 * without requiring external MP3 files.
 */
class NotificationSoundEngine {
    constructor() {
        this.audioCtx = null;
    }

    getAudioContext() {
        if (!this.audioCtx) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                this.audioCtx = new AudioContextClass();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
        return this.audioCtx;
    }

    /**
     * Plays a pleasant, modern two-tone notification chime (D5 -> A5)
     */
    playBeep() {
        try {
            const ctx = this.getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;

            // First Tone (F#5 - 739.99 Hz)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(740, now);
            gain1.gain.setValueAtTime(0, now);
            gain1.gain.linearRampToValueAtTime(0.18, now + 0.03);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

            osc1.connect(gain1);
            gain1.connect(ctx.destination);

            osc1.start(now);
            osc1.stop(now + 0.35);

            // Second Higher Harmonic Tone (B5 - 987.77 Hz)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(988, now + 0.12);
            gain2.gain.setValueAtTime(0, now + 0.12);
            gain2.gain.linearRampToValueAtTime(0.22, now + 0.15);
            gain2.gain.exponentialRampToValueAtTime(0.0001, now + 0.65);

            osc2.connect(gain2);
            gain2.connect(ctx.destination);

            osc2.start(now + 0.12);
            osc2.stop(now + 0.65);
        } catch (e) {
            console.warn('Notification audio playback failed:', e);
        }
    }
}

export const notificationAudio = new NotificationSoundEngine();
export const playNotificationBeep = () => notificationAudio.playBeep();
