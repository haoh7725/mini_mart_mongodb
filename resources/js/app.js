import './bootstrap';
import 'bootstrap';

const escapeHtml = value => {
    const node = document.createElement('div');
    node.textContent = value ?? '';
    return node.innerHTML;
};

const initAiChatbox = () => {
    const widget = document.getElementById('ai-chatbox');
    if (!widget) return;

    const launcher = widget.querySelector('.ai-widget-launcher');
    const panel = widget.querySelector('.ai-widget-panel');
    const closeButton = widget.querySelector('.ai-widget-close');
    const form = widget.querySelector('.ai-widget-composer');
    const input = widget.querySelector('#ai-widget-input');
    const submit = form.querySelector('button[type="submit"]');
    const messages = widget.querySelector('.ai-widget-messages');
    const token = form.querySelector('input[name="_token"]').value;

    const setOpen = open => {
        panel.hidden = !open;
        widget.classList.toggle('is-open', open);
        launcher.setAttribute('aria-expanded', String(open));
        if (open && !input.disabled) window.setTimeout(() => input.focus(), 80);
    };
    const addMessage = (role, html, extraClass = '') => {
        const article = document.createElement('article');
        article.className = `ai-widget-message ${role} ${extraClass}`.trim();
        const avatar = role === 'assistant' ? document.getElementById('ai-pet-avatar')?.innerHTML ?? 'M' : 'B';
        article.innerHTML = `<span class="ai-widget-avatar">${avatar}</span><div>${html}</div>`;
        messages.appendChild(article);
        messages.scrollTop = messages.scrollHeight;
        return article;
    };

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    closeButton.addEventListener('click', () => { setOpen(false); launcher.focus(); });
    document.querySelectorAll('[data-ai-chat-open]').forEach(link => link.addEventListener('click', event => { event.preventDefault(); setOpen(true); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) setOpen(false); });
    widget.querySelectorAll('[data-ai-prompt]').forEach(button => button.addEventListener('click', () => { input.value = button.dataset.aiPrompt; input.focus(); }));
    input.addEventListener('keydown', event => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); } });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || submit.disabled) return;
        widget.querySelector('.ai-widget-suggestions')?.remove();
        addMessage('user', `<p>${escapeHtml(message)}</p>`);
        input.value = '';
        submit.disabled = true;
        const loading = addMessage('assistant', '<p><span class="ai-typing"><i></i><i></i><i></i></span><span class="sr-only">Đang trả lời</span></p>', 'loading');
        try {
            const response = await fetch(widget.dataset.askUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ message }) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Không thể nhận câu trả lời.');
            const ingredients = (data.ingredients || []).map(item => `<li>${escapeHtml(item)}</li>`).join('');
            const steps = (data.steps || []).map(item => `<li>${escapeHtml(item)}</li>`).join('');
            const products = (data.products || []).map(item => `<label class="ai-widget-product"><input type="checkbox" value="${escapeHtml(String(item.id))}" checked><span>${item.image ? `<img src="${escapeHtml(item.image)}" alt="">` : '<i>M</i>'}<b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.price)}</small></span></label>`).join('');
            loading.classList.remove('loading');
            loading.querySelector('div').innerHTML = `<b>${escapeHtml(data.dish)}</b><p>${escapeHtml(data.answer)}</p>${ingredients ? `<details><summary>Nguyên liệu</summary><ul>${ingredients}</ul></details>` : ''}${steps ? `<details><summary>Cách làm</summary><ol>${steps}</ol></details>` : ''}${products ? `<div class="ai-widget-products"><b>Có tại MiniMart</b>${products}<button type="button" class="ai-widget-add">Thêm vào giỏ</button></div>` : ''}`;
            const addButton = loading.querySelector('.ai-widget-add');
            addButton?.addEventListener('click', async () => {
                const productIds = [...loading.querySelectorAll('.ai-widget-product input:checked')].map(item => String(item.value));
                if (!productIds.length) return;
                addButton.disabled = true;
                try {
                    const cartResponse = await fetch(widget.dataset.cartUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ product_ids: productIds }) });
                    const cartData = await cartResponse.json();
                    if (!cartResponse.ok) throw new Error(cartData.message || 'Không thể cập nhật giỏ hàng.');
                    addButton.textContent = cartData.message;
                    const badge = document.querySelector('.cart-link b');
                    if (badge && cartData.cart_count !== undefined) {
                        badge.textContent = cartData.cart_count;
                        badge.hidden = !cartData.cart_count;
                    }
                } catch (error) { addButton.textContent = error.message; addButton.disabled = false; }
            });
        } catch (error) {
            loading.classList.remove('loading');
            loading.querySelector('div').innerHTML = `<b>Chưa thể trả lời</b><p>${escapeHtml(error.message)}</p>`;
        } finally { submit.disabled = false; input.focus(); }
    });
};

// Toast thông báo: tự ẩn sau data-timeout, tạm dừng khi rê chuột hoặc focus vào.
const dismissFlash = flash => {
    if (flash.classList.contains('is-leaving')) return;
    flash.classList.add('is-leaving');
    flash.addEventListener('animationend', () => flash.remove(), { once: true });
};

const armFlash = flash => {
    const timeout = Number(flash.dataset.timeout || 5000);
    flash.style.setProperty('--flash-duration', `${timeout}ms`);
    let remaining = timeout;
    let started = Date.now();
    let timer = window.setTimeout(() => dismissFlash(flash), remaining);
    const pause = () => { window.clearTimeout(timer); remaining -= Date.now() - started; flash.classList.add('is-paused'); };
    const resume = () => { started = Date.now(); timer = window.setTimeout(() => dismissFlash(flash), Math.max(remaining, 1200)); flash.classList.remove('is-paused'); };
    flash.addEventListener('mouseenter', pause);
    flash.addEventListener('mouseleave', resume);
    flash.addEventListener('focusin', pause);
    flash.addEventListener('focusout', resume);
    flash.querySelector('[data-flash-close]')?.addEventListener('click', () => dismissFlash(flash));
};

// Cho JS khác hiện toast: window.showToast('error', 'Nội dung', 'Tiêu đề')
window.showToast = (type, message, title) => {
    const stack = document.getElementById('flash-stack');
    if (!stack) return;
    const titles = { success: 'Thành công', info: 'Thông báo', warning: 'Lưu ý', error: 'Có lỗi xảy ra' };
    const icon = document.querySelector(`#flash-icons svg[data-type="${type}"]`)?.innerHTML ?? '';
    const flash = document.createElement('div');
    flash.className = `flash flash-${type}`;
    flash.setAttribute('role', type === 'error' ? 'alert' : 'status');
    flash.dataset.timeout = type === 'error' ? 8000 : 5000;
    flash.innerHTML = `<span class="flash-icon" aria-hidden="true"><svg viewBox="0 0 24 24">${icon}</svg></span>`
        + `<div class="flash-body"><b>${escapeHtml(title ?? titles[type])}</b><p>${escapeHtml(message)}</p></div>`
        + '<button type="button" class="flash-close" data-flash-close aria-label="Đóng thông báo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"/></svg></button>'
        + '<span class="flash-progress" aria-hidden="true"></span>';
    stack.append(flash);
    armFlash(flash);
};

// Hộp xác nhận: <form data-confirm="Nội dung" data-confirm-title="..." data-confirm-ok="...">
const initConfirmDialog = () => {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    const title = dialog.querySelector('#confirm-title');
    const message = dialog.querySelector('#confirm-message');
    const ok = dialog.querySelector('[data-confirm-ok]');
    let pendingForm = null;
    let opener = null;

    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') return;
        event.preventDefault();
        pendingForm = form;
        opener = event.submitter ?? document.activeElement;
        title.textContent = form.dataset.confirmTitle || 'Bạn chắc chắn chứ?';
        message.textContent = form.dataset.confirm;
        ok.textContent = form.dataset.confirmOk || 'Đồng ý';
        dialog.returnValue = '';
        dialog.showModal();
        dialog.querySelector('[data-confirm-cancel]').focus();
    }, true);

    dialog.addEventListener('close', () => {
        const form = pendingForm;
        pendingForm = null;
        if (dialog.returnValue === 'ok' && form) {
            form.dataset.confirmed = '1';
            form.requestSubmit();
            delete form.dataset.confirmed;
        } else {
            opener?.focus?.();
        }
    });
    // Bấm ra ngoài hộp để đóng.
    dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close('cancel'); });
};

// Menu thả xuống dạng <details data-dropdown>: đóng khi bấm ra ngoài hoặc nhấn Esc.
const initDropdowns = () => {
    const closeOpen = except => document.querySelectorAll('details[data-dropdown][open]').forEach(menu => {
        if (menu !== except) menu.open = false;
    });
    document.addEventListener('click', event => closeOpen(event.target.closest('details[data-dropdown]')));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeOpen(null); });
};

// Chuông thông báo: hỏi server định kỳ, cập nhật số chưa đọc, hiện toast khi có thông báo mới.
// Nhân viên nghe tiếng "ting" và nhận thông báo trên máy tính khi có đơn mới.
const initNotifications = () => {
    const bell = document.querySelector('[data-notify]');
    if (!bell) return;
    const list = bell.querySelector('[data-notify-list]');
    const count = bell.querySelector('[data-notify-count]');
    const staff = bell.dataset.staff === '1';
    const baseTitle = document.title.replace(/^\(\d+\+?\) /, '');
    const seen = new Set(JSON.parse(bell.dataset.seen || '[]'));
    const canNotify = 'Notification' in window;

    const setUnread = unread => {
        const label = unread > 99 ? '99+' : unread;
        count.hidden = !unread;
        count.textContent = label;
        document.title = unread ? `(${label}) ${baseTitle}` : baseTitle;
    };
    const render = items => {
        list.innerHTML = items.length ? items.map(item => `<li class="${item.read ? '' : 'unread'}"><a href="${escapeHtml(item.url)}">`
            + `<span class="notify-icon notify-${escapeHtml(item.kind)}" aria-hidden="true">${escapeHtml(item.icon)}</span>`
            + `<span><b>${escapeHtml(item.title)}</b>${item.body ? `<small>${escapeHtml(item.body)}</small>` : ''}<time>${escapeHtml(item.time)}</time></span></a></li>`).join('')
            : '<li class="notify-empty">Chưa có thông báo nào.</li>';
    };
    const ding = () => {
        try {
            const audio = new AudioContext();
            [880, 1320].forEach((frequency, index) => {
                const tone = audio.createOscillator();
                const gain = audio.createGain();
                const start = audio.currentTime + index * 0.18;
                tone.frequency.value = frequency;
                gain.gain.setValueAtTime(0.12, start);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.35);
                tone.connect(gain).connect(audio.destination);
                tone.start(start);
                tone.stop(start + 0.35);
            });
        } catch { /* Trình duyệt chặn âm thanh khi chưa tương tác với trang. */ }
    };

    const poll = async () => {
        if (!staff && document.hidden) return;
        try {
            const response = await fetch(bell.dataset.feed, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            setUnread(data.unread);
            render(data.items);
            const fresh = data.items.filter(item => !item.read && !seen.has(item.id)).reverse();
            data.items.forEach(item => seen.add(item.id));
            fresh.forEach(item => {
                window.showToast?.(item.kind === 'new_order' ? 'success' : 'info', item.body ?? '', `${item.icon} ${item.title}`);
                if (staff && document.hidden && canNotify && Notification.permission === 'granted') {
                    const desktop = new Notification(item.title, { body: item.body ?? '', tag: item.id });
                    desktop.onclick = () => { window.focus(); window.location.href = item.url; };
                }
            });
            if (staff && fresh.some(item => item.kind === 'new_order')) ding();
            const pending = document.querySelector('[data-pending-orders]');
            if (pending && data.pending_orders !== null) {
                pending.textContent = data.pending_orders;
                pending.hidden = !data.pending_orders;
            }
        } catch { /* Mất mạng tạm thời: lần gọi sau sẽ cập nhật lại. */ }
    };

    setUnread(count.hidden ? 0 : Number(count.textContent.replace('+', '')));
    window.setInterval(poll, staff ? 15000 : 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    const enable = bell.querySelector('[data-notify-enable]');
    if (enable && canNotify && Notification.permission === 'default') {
        enable.hidden = false;
        enable.addEventListener('click', async () => {
            await Notification.requestPermission();
            enable.hidden = true;
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    initAiChatbox();
    initDropdowns();
    initNotifications();
    document.querySelectorAll('[data-flash]').forEach(armFlash);
    initConfirmDialog();
});
