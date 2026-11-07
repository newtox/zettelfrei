import Alpine from 'alpinejs';
import QRCode from 'qrcode';

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

Alpine.data('poller', (url, key, interval = 4000) => ({
    data: null,
    timer: null,
    init() {
        this.tick();
        this.timer = setInterval(() => this.tick(), interval);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') this.tick();
        });
    },
    async tick() {
        if (document.visibilityState === 'hidden') return;
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!res.ok) return;
            const json = await res.json();
            if (key && json.key && json.key !== key) {
                clearInterval(this.timer);
                window.location.reload();
                return;
            }
            this.data = json;
        } catch (e) {}
    },
}));

Alpine.data('lights', (initial = 0) => ({
    value: Number(initial) || 0,
    preview: 0,
    get shown() {
        return this.preview || this.value;
    },
    pick(n) {
        this.value = n;
        if (navigator.vibrate) navigator.vibrate(8);
    },
}));

Alpine.data('ballot', (count) => ({
    count,
    filled: 0,
    update() {
        this.filled = this.$root.querySelectorAll('input[type=radio]:checked').length;
    },
    init() {
        this.update();
    },
}));

Alpine.data('listEditor', (initial = [], placeholder = '') => ({
    items: (initial.length ? initial : ['']).map((v) => ({ id: crypto.randomUUID?.() ?? Math.random(), v })),
    placeholder,
    add(value = '') {
        this.items.push({ id: crypto.randomUUID?.() ?? Math.random(), v: value });
        this.$nextTick(() => {
            const inputs = this.$root.querySelectorAll('input[data-item]');
            inputs[inputs.length - 1]?.focus();
        });
    },
    remove(i) {
        this.items.splice(i, 1);
        if (!this.items.length) this.add();
    },
    move(i, d) {
        const j = i + d;
        if (j < 0 || j >= this.items.length) return;
        [this.items[i], this.items[j]] = [this.items[j], this.items[i]];
    },
    replace(values) {
        this.items = values.map((v) => ({ id: crypto.randomUUID?.() ?? Math.random(), v }));
    },
    onEnter(i) {
        if (i === this.items.length - 1 && this.items[i].v.trim()) this.add();
    },
}));

async function shrink(file, max = 2000, quality = 0.85) {
    if (!file.type.startsWith('image/')) return file;
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, max / Math.max(bitmap.width, bitmap.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', quality));
        if (!blob) return file;
        return new File([blob], (file.name || 'foto').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
    } catch (e) {
        return file;
    }
}

Alpine.data('photoUpload', (url) => ({
    busy: false,
    done: 0,
    total: 0,
    error: '',
    async upload(event) {
        const files = [...event.target.files];
        if (!files.length) return;
        this.busy = true;
        this.error = '';
        this.done = 0;
        this.total = files.length;
        for (const file of files) {
            const body = new FormData();
            body.append('photo', await shrink(file));
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
                });
                if (!res.ok) {
                    const json = await res.json().catch(() => ({}));
                    this.error = json.errors?.photo?.[0] ?? json.message ?? 'Hochladen hat nicht geklappt.';
                    break;
                }
                this.done++;
            } catch (e) {
                this.error = 'Keine Verbindung. Versuch es gleich nochmal.';
                break;
            }
        }
        event.target.value = '';
        this.busy = false;
        if (this.done) window.location.reload();
    },
}));

Alpine.data('qr', (text) => ({
    big: false,
    copied: false,
    init() {
        this.$nextTick(() => {
            this.$root.querySelectorAll('canvas[data-qr]').forEach((canvas) => {
                QRCode.toCanvas(canvas, text, { width: Number(canvas.dataset.qr), margin: 1, color: { dark: '#14221b', light: '#ffffff' } });
            });
        });
    },
    async copy() {
        try {
            await navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => (this.copied = false), 1800);
        } catch (e) {}
    },
    async share() {
        if (navigator.share) {
            try {
                await navigator.share({ url: text });
            } catch (e) {}
        } else {
            this.copy();
        }
    },
}));

Alpine.data('themePicker', (baseTheme) => ({
    current: null,
    init() {
        try {
            this.current = localStorage.getItem('zf-theme');
        } catch (e) {}
    },
    set(theme) {
        this.current = theme;
        document.documentElement.dataset.theme = theme || baseTheme;
        try {
            if (theme) localStorage.setItem('zf-theme', theme);
            else localStorage.removeItem('zf-theme');
        } catch (e) {}
    },
}));

document.addEventListener(
    'submit',
    (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }
        const dialog = document.getElementById('confirm-dialog');
        if (!dialog?.showModal) return;
        event.preventDefault();
        const submitter = event.submitter;
        dialog.querySelector('[data-confirm-text]').textContent = form.dataset.confirm;
        const ok = dialog.querySelector('[data-confirm-ok]');
        ok.textContent = form.dataset.confirmAction || 'Ja';
        ok.className = 'btn ' + ('confirmDanger' in form.dataset ? 'btn-live' : 'btn-primary');
        dialog.returnValue = '';
        dialog.addEventListener(
            'close',
            () => {
                if (dialog.returnValue !== 'ok') return;
                form.dataset.confirmed = '1';
                form.requestSubmit(submitter && form.contains(submitter) ? submitter : undefined);
            },
            { once: true },
        );
        dialog.showModal();
        ok.focus();
    },
    true,
);

window.Alpine = Alpine;
Alpine.start();
