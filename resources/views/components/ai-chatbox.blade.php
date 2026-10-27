@php($aiConfigured = filled(config('services.gemini.api_key')))
<section id="ai-chatbox" class="ai-widget" data-ask-url="{{ route('cooking-assistant.ask') }}" data-cart-url="{{ route('cooking-assistant.add-products') }}" data-configured="{{ $aiConfigured ? 'true' : 'false' }}" aria-label="Trợ lý AI MiniMart">
    <button class="ai-widget-launcher" type="button" aria-label="Mở trợ lý AI MiniMart" aria-expanded="false" aria-controls="ai-widget-panel">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 10h.01M12 10h.01M16 10h.01M7 16l-4 3v-5a8 8 0 1 1 4 2Z"/></svg>
        <span>Hỏi trợ lý AI</span>
    </button>
    <div id="ai-widget-panel" class="ai-widget-panel" role="dialog" aria-modal="false" aria-labelledby="ai-widget-title" hidden>
        <header class="ai-widget-head">
            <span class="ai-widget-logo" aria-hidden="true">M</span>
            <div><strong id="ai-widget-title">Trợ lý AI MiniMart</strong><small><i></i> Đang hoạt động</small></div>
            <button class="ai-widget-close" type="button" aria-label="Đóng trợ lý AI">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </header>
        <div class="ai-widget-messages" aria-live="polite" aria-relevant="additions">
            <article class="ai-widget-message assistant"><span class="ai-widget-avatar">M</span><div><b>Xin chào!</b><p>Hãy nói món bạn muốn ăn hoặc nguyên liệu đang có. Mình sẽ gợi ý công thức và tìm sản phẩm phù hợp.</p></div></article>
            @unless($aiConfigured)<div class="ai-widget-warning" role="alert">Chưa cấu hình Gemini API key.</div>@endunless
            <div class="ai-widget-suggestions" aria-label="Câu hỏi gợi ý">
                <button type="button" data-ai-prompt="Tôi muốn nấu cá kho, cần những nguyên liệu gì?">Nấu cá kho</button>
                <button type="button" data-ai-prompt="Gợi ý bữa tối dưới 200.000đ cho gia đình">Bữa tối tiết kiệm</button>
            </div>
        </div>
        <form class="ai-widget-composer">
            @csrf
            <label class="sr-only" for="ai-widget-input">Nhập câu hỏi cho trợ lý</label>
            <textarea id="ai-widget-input" rows="1" maxlength="500" placeholder="Bạn muốn ăn món gì?" {{ $aiConfigured ? '' : 'disabled' }} required></textarea>
            <button type="submit" aria-label="Gửi câu hỏi" {{ $aiConfigured ? '' : 'disabled' }}>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 4 17 8-17 8 3-8-3-8Zm3 8h14"/></svg>
            </button>
        </form>
        <p class="ai-widget-disclaimer">AI có thể nhầm. Hãy kiểm tra dị ứng và độ chín an toàn.</p>
    </div>
</section>
