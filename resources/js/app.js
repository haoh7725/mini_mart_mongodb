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
        article.innerHTML = `<span class="ai-widget-avatar">${role === 'assistant' ? 'M' : 'B'}</span><div>${html}</div>`;
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
            const products = (data.products || []).map(item => `<label class="ai-widget-product"><input type="checkbox" value="${Number(item.id)}" checked><span>${item.image ? `<img src="${escapeHtml(item.image)}" alt="">` : '<i>M</i>'}<b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.price)}</small></span></label>`).join('');
            loading.classList.remove('loading');
            loading.querySelector('div').innerHTML = `<b>${escapeHtml(data.dish)}</b><p>${escapeHtml(data.answer)}</p>${ingredients ? `<details><summary>Nguyên liệu</summary><ul>${ingredients}</ul></details>` : ''}${steps ? `<details><summary>Cách làm</summary><ol>${steps}</ol></details>` : ''}${products ? `<div class="ai-widget-products"><b>Có tại MiniMart</b>${products}<button type="button" class="ai-widget-add">Thêm vào giỏ</button></div>` : ''}`;
            const addButton = loading.querySelector('.ai-widget-add');
            addButton?.addEventListener('click', async () => {
                const productIds = [...loading.querySelectorAll('.ai-widget-product input:checked')].map(item => Number(item.value));
                if (!productIds.length) return;
                addButton.disabled = true;
                try {
                    const cartResponse = await fetch(widget.dataset.cartUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token }, body: JSON.stringify({ product_ids: productIds }) });
                    const cartData = await cartResponse.json();
                    if (!cartResponse.ok) throw new Error(cartData.message || 'Không thể cập nhật giỏ hàng.');
                    addButton.textContent = cartData.message;
                    const badge = document.querySelector('.cart-link b');
                    if (badge && cartData.cart_count !== undefined) badge.textContent = cartData.cart_count;
                } catch (error) { addButton.textContent = error.message; addButton.disabled = false; }
            });
        } catch (error) {
            loading.classList.remove('loading');
            loading.querySelector('div').innerHTML = `<b>Chưa thể trả lời</b><p>${escapeHtml(error.message)}</p>`;
        } finally { submit.disabled = false; input.focus(); }
    });
};

document.addEventListener('DOMContentLoaded', initAiChatbox);
