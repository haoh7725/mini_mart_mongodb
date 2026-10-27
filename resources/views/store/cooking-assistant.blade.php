@extends('layouts.app')
@section('title', 'Trợ lý nấu ăn')
@section('content')
<section class="page-hero compact"><div class="container"><span class="eyebrow">MiniMart AI Kitchen</span><h1>Hôm nay bạn muốn ăn gì?</h1><p>Hỏi món ăn, nhận danh sách nguyên liệu và thêm sản phẩm MiniMart vào giỏ.</p></div></section>
<div class="container cooking-layout">
    <aside class="cooking-sidebar"><h2>Gợi ý để bắt đầu</h2><button type="button" data-prompt="Tôi muốn nấu cá kho cho 4 người, cần những nguyên liệu gì?">Cá kho cho 4 người</button><button type="button" data-prompt="Gợi ý bữa tối dưới 200.000đ cho gia đình">Bữa tối dưới 200.000đ</button><button type="button" data-prompt="Tôi có trứng và thịt heo, có thể nấu món gì?">Có trứng và thịt heo</button><div class="ai-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3 8 8 9 5-1 8-4 8-9V7l-8-4Zm0 5v5m0 3h.01"/></svg><p>AI có thể nhầm. Hãy kiểm tra dị ứng, khẩu phần và độ chín an toàn trước khi dùng.</p></div></aside>
    <section class="chat-panel" aria-label="Trò chuyện với trợ lý nấu ăn">
        <div id="chat-messages" class="chat-messages" aria-live="polite">
            <article class="chat-message assistant"><div class="chat-avatar">M</div><div><b>Trợ lý MiniMart</b><p>Chào bạn! Hãy nói món muốn ăn hoặc những nguyên liệu đang có. Mình sẽ gợi ý công thức và tìm sản phẩm phù hợp trong cửa hàng.</p></div></article>
            @unless($configured)<div class="chat-config-warning"><b>Chưa có Gemini API key</b><p>Thêm <code>GEMINI_API_KEY</code> vào file <code>.env</code>, sau đó chạy <code>php artisan optimize:clear</code>.</p></div>@endunless
        </div>
        <form id="chat-form" class="chat-composer">
            @csrf
            <label class="sr-only" for="chat-input">Nội dung muốn hỏi</label>
            <textarea id="chat-input" maxlength="500" rows="2" placeholder="Ví dụ: Tôi muốn ăn cá kho nhưng chưa biết cần nguyên liệu gì..." required></textarea>
            <button id="chat-submit" type="submit" aria-label="Gửi câu hỏi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 4 17 8-17 8 3-8-3-8Zm3 8h14"/></svg><span>Gửi</span></button>
        </form>
    </section>
</div>
<script>
(() => {
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const messages = document.getElementById('chat-messages');
    const submit = document.getElementById('chat-submit');
    const token = form.querySelector('input[name="_token"]').value;
    const escapeHtml = value => { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; };
    const addMessage = (role, html) => {
        const article = document.createElement('article'); article.className = `chat-message ${role}`;
        article.innerHTML = `<div class="chat-avatar">${role === 'assistant' ? 'M' : 'B'}</div><div>${html}</div>`;
        messages.appendChild(article); messages.scrollTop = messages.scrollHeight; return article;
    };
    document.querySelectorAll('[data-prompt]').forEach(button => button.addEventListener('click', () => { input.value = button.dataset.prompt; input.focus(); }));
    form.addEventListener('submit', async event => {
        event.preventDefault(); const message = input.value.trim(); if (!message) return;
        addMessage('user', `<p>${escapeHtml(message)}</p>`); input.value = ''; submit.disabled = true;
        const loading = addMessage('assistant loading', '<p>Đang xem công thức và kiểm tra sản phẩm trong cửa hàng…</p>');
        try {
            const response = await fetch(@json(route('cooking-assistant.ask')), {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token}, body:JSON.stringify({message})});
            const data = await response.json(); if (!response.ok) throw new Error(data.message || 'Không thể nhận câu trả lời.');
            const ingredients = data.ingredients.map(item => `<li>${escapeHtml(item)}</li>`).join('');
            const steps = data.steps.map(item => `<li>${escapeHtml(item)}</li>`).join('');
            const products = data.products.map(item => `<label class="chat-product"><input type="checkbox" value="${item.id}" checked><span>${item.image ? `<img src="${escapeHtml(item.image)}" alt="">` : '<i>M</i>'}<b>${escapeHtml(item.name)}</b><small>${escapeHtml(item.price)}</small></span></label>`).join('');
            loading.classList.remove('loading'); loading.querySelector('div:last-child').innerHTML = `<b>${escapeHtml(data.dish)}</b><p>${escapeHtml(data.answer)}</p><h3>Nguyên liệu</h3><ul>${ingredients}</ul><h3>Cách làm nhanh</h3><ol>${steps}</ol>${products ? `<div class="chat-products"><h3>Sản phẩm có tại MiniMart</h3>${products}<button type="button" class="button primary add-ai-products">Thêm món đã chọn vào giỏ</button></div>` : '<p><small>Hiện chưa tìm thấy sản phẩm khớp trong cửa hàng.</small></p>'}`;
            loading.querySelector('.add-ai-products')?.addEventListener('click', async event => {
                const ids = [...loading.querySelectorAll('.chat-product input:checked')].map(item => Number(item.value)); if (!ids.length) return;
                event.currentTarget.disabled = true;
                const cartResponse = await fetch(@json(route('cooking-assistant.add-products')), {method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({product_ids:ids})});
                const cartData = await cartResponse.json(); event.currentTarget.textContent = cartData.message || 'Đã cập nhật giỏ hàng';
                const badge = document.querySelector('.cart-link b'); if (badge && cartData.cart_count !== undefined) badge.textContent = cartData.cart_count;
            });
        } catch (error) { loading.classList.remove('loading'); loading.querySelector('div:last-child').innerHTML = `<b>Chưa thể trả lời</b><p>${escapeHtml(error.message)}</p>`; }
        finally { submit.disabled = false; input.focus(); }
    });
})();
</script>
@endsection
