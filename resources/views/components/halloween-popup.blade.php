@php
    // Popup chỉ hiện trong dịp Halloween.
    $showFrom = \Illuminate\Support\Carbon::parse('2026-10-20');
    $showUntil = \Illuminate\Support\Carbon::parse('2026-11-01');

    // Khăn bàn caro phối cảnh: các cột hội tụ về điểm (320, 300).
    $vanishY = 300; $tableTop = 620; $bottom = 840; $rows = 6;
    $xAt = fn ($xb, $y) => round(320 + ($xb - 320) * ($y - $vanishY) / ($bottom - $vanishY), 1);
    $rowYs = collect(range(0, $rows))->map(fn ($k) => round($tableTop + ($bottom - $tableTop) * ($k / $rows) ** 1.5, 1));
    $cells = [];
    foreach (range(0, $rows - 1) as $k) {
        foreach (range(-8, 15) as $i) {
            $x1 = $i * 110 - 560; $x2 = $x1 + 110; $y1 = $rowYs[$k]; $y2 = $rowYs[$k + 1];
            $cells[] = ['points' => "{$xAt($x1, $y1)},$y1 {$xAt($x2, $y1)},$y1 {$xAt($x2, $y2)},$y2 {$xAt($x1, $y2)},$y2", 'dark' => ($i + $k) % 2 === 0];
        }
    }

    $burst = fn ($cx, $cy, $outer, $inner, $spikes) => collect(range(0, $spikes * 2 - 1))
        ->map(function ($n) use ($cx, $cy, $outer, $inner, $spikes) {
            $r = $n % 2 ? $inner : $outer; $a = M_PI * $n / $spikes - M_PI / 2;
            return round($cx + $r * cos($a), 1).','.round($cy + $r * sin($a), 1);
        })->implode(' ');

    $stars = [[40, 40], [205, 30], [300, 60], [400, 26], [455, 120], [30, 260], [610, 250], [180, 400], [470, 400], [600, 480], [20, 440], [380, 160]];
@endphp

@if(now()->between($showFrom, $showUntil))
<div class="hw-popup" id="hw-popup" hidden>
    <div class="hw-backdrop" data-hw-close></div>
    <div class="hw-dialog" role="dialog" aria-modal="true" aria-labelledby="hw-title">
        <button type="button" class="hw-close" data-hw-close aria-label="Đóng quảng cáo">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
        <a class="hw-art" href="{{ route('rescue.index') }}" tabindex="-1">
            <svg viewBox="0 0 640 840" role="img" aria-labelledby="hw-title hw-desc">
                <title id="hw-title">Halloween rộn ràng – Sale kinh hoàng</title>
                <desc id="hw-desc">Ưu đãi lên tới 50%, voucher đến 50.000 đồng, miễn phí vận chuyển cho đơn từ 300.000 đồng.</desc>
                <defs>
                    <linearGradient id="hw-sky" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#2e0f52"/><stop offset=".65" stop-color="#1b0933"/><stop offset="1" stop-color="#12061f"/>
                    </linearGradient>
                    <radialGradient id="hw-glow"><stop offset="0" stop-color="#ff9a3c" stop-opacity=".6"/><stop offset="1" stop-color="#ff9a3c" stop-opacity="0"/></radialGradient>
                    <radialGradient id="hw-moon" cx=".4" cy=".35"><stop offset="0" stop-color="#fff7d1"/><stop offset="1" stop-color="#f6cf63"/></radialGradient>
                    <linearGradient id="hw-fade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#12061f" stop-opacity="0"/><stop offset="1" stop-color="#12061f" stop-opacity=".9"/></linearGradient>
                    <linearGradient id="hw-title-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffd166"/><stop offset="1" stop-color="#ff7a1a"/></linearGradient>
                    <symbol id="hw-pumpkin" viewBox="0 0 120 104">
                        <ellipse cx="36" cy="62" rx="30" ry="38" fill="#e2590c"/>
                        <ellipse cx="84" cy="62" rx="30" ry="38" fill="#e2590c"/>
                        <ellipse cx="60" cy="62" rx="32" ry="41" fill="#f97316"/>
                        <path d="M44 26q-6 36 0 76M76 26q6 36 0 76" stroke="#c2410c" stroke-width="2.5" fill="none" opacity=".7"/>
                        <path d="M56 24q1-14 11-19l4 5q-8 4-8 15z" fill="#4d7c0f"/>
                    </symbol>
                    <symbol id="hw-jack" viewBox="0 0 120 104">
                        <use href="#hw-pumpkin" width="120" height="104"/>
                        <g fill="#ffd54a">
                            <polygon points="34,58 50,58 42,43"/><polygon points="70,58 86,58 78,43"/><polygon points="55,69 65,69 60,61"/>
                            <path d="M32 76q28 24 56 0l-6 3-4 7-6-6-6 8-6-8-6 8-6-8-6 6-4-7z"/>
                        </g>
                    </symbol>
                    <symbol id="hw-bat" viewBox="0 0 60 22">
                        <path d="M0 10q10-10 20-2 4-6 8-2 2-4 4 0 4-4 8 2 10-8 20 2-10-1-14 7-6-6-12-2-4 6-8 0-6-4-12 2-4-9-14-7z" fill="#0b0414"/>
                    </symbol>
                    <symbol id="hw-candy" viewBox="0 0 60 26">
                        <polygon points="0,2 16,13 0,24" fill="currentColor" opacity=".8"/><polygon points="60,2 44,13 60,24" fill="currentColor" opacity=".8"/>
                        <ellipse cx="30" cy="13" rx="17" ry="11" fill="currentColor"/><path d="M20 6q10 14 20 0" stroke="#fff" stroke-width="3" fill="none" opacity=".6"/>
                    </symbol>
                </defs>

                {{-- Bầu trời đêm --}}
                <rect width="640" height="840" fill="url(#hw-sky)"/>
                <g fill="#fff">@foreach($stars as [$sx, $sy])<circle class="hw-twinkle" cx="{{ $sx }}" cy="{{ $sy }}" r="{{ 1 + ($sx % 3) * .6 }}" style="animation-delay: {{ ($sy % 7) * .3 }}s"/>@endforeach</g>
                <circle cx="96" cy="104" r="62" fill="#fde68a" opacity=".18"/>
                <circle cx="96" cy="104" r="54" fill="url(#hw-moon)"/>
                <g fill="#e9b949" opacity=".55"><circle cx="76" cy="92" r="9"/><circle cx="112" cy="124" r="12"/><circle cx="116" cy="84" r="5"/></g>
                <use class="hw-flap" href="#hw-bat" x="150" y="52" width="60" height="22"/>
                <use class="hw-flap" href="#hw-bat" x="44" y="180" width="40" height="15" style="animation-delay:.2s"/>
                <use class="hw-flap" href="#hw-bat" x="455" y="150" width="34" height="13" style="animation-delay:.4s"/>
                <use class="hw-flap" href="#hw-bat" x="232" y="418" width="44" height="16" style="animation-delay:.1s"/>
                <use class="hw-flap" href="#hw-bat" x="408" y="440" width="30" height="11" style="animation-delay:.3s"/>

                {{-- Nhện treo --}}
                <line x1="26" y1="0" x2="26" y2="250" stroke="#cbd5e1" stroke-width="1" opacity=".7"/>
                <g class="hw-swing" transform="translate(26 258)">
                    <g stroke="#0b0414" stroke-width="2.5" fill="none"><path d="M-6-2l-12-8M-6 2l-13 2M-6 5l-10 10M6-2l12-8M6 2l13 2M6 5l10 10"/></g>
                    <ellipse rx="9" ry="11" fill="#0b0414"/><circle cx="-3" cy="-3" r="1.8" fill="#f87171"/><circle cx="3" cy="-3" r="1.8" fill="#f87171"/>
                </g>

                {{-- Thương hiệu --}}
                <rect x="232" y="94" width="36" height="36" rx="10" fill="#057a55"/>
                <text x="250" y="120" text-anchor="middle" class="hw-font" font-size="22" font-weight="700" fill="#fff">M</text>
                <text x="278" y="122" class="hw-font" font-size="30" font-weight="700" fill="#fff">Mini<tspan fill="#34d399">Mart</tspan></text>

                {{-- Tiêu đề --}}
                <g class="hw-font hw-headline" font-weight="700" text-anchor="middle">
                    <text x="320" y="205" font-size="50" textLength="540" lengthAdjust="spacingAndGlyphs">HALLOWEEN RỘN RÀNG</text>
                    <text x="320" y="272" font-size="64" textLength="560" lengthAdjust="spacingAndGlyphs">SALE KINH HOÀNG</text>
                </g>

                {{-- Dải ưu đãi --}}
                <g class="hw-font">
                    <rect x="44" y="296" width="552" height="88" rx="12" fill="#fff8ef"/>
                    <path d="M228 308v64M412 308v64" stroke="#f3d9bf" stroke-width="2" stroke-dasharray="4 4"/>
                    <g fill="#ea580c"><circle cx="228" cy="340" r="11"/><circle cx="412" cy="340" r="11"/></g>
                    <g stroke="#fff" stroke-width="3"><path d="M222 340h12M228 334v12M406 340h12M412 334v12"/></g>
                    <g text-anchor="middle" fill="#3b0764">
                        <text x="136" y="330" font-size="17" font-weight="700">ƯU ĐÃI <tspan font-size="13" font-weight="500">lên tới</tspan></text>
                        <text x="136" y="370" font-size="38" font-weight="700" fill="#ea580c">50<tspan font-size="20" dy="-14">%</tspan></text>
                        <text x="320" y="332" font-size="19" font-weight="700">VOUCHER ĐẾN</text>
                        <text x="320" y="364" font-size="22" font-weight="500">50.000 đồng</text>
                        <text x="504" y="325" font-size="12" font-weight="500">MIỄN PHÍ VẬN CHUYỂN</text>
                        <text x="504" y="370" font-size="38" font-weight="700" fill="#ea580c">0<tspan font-size="20" dy="-14">đ</tspan></text>
                    </g>
                </g>

                {{-- Bàn tiệc --}}
                <circle cx="320" cy="560" r="230" fill="url(#hw-glow)"/>
                <g>@foreach($cells as $cell)<polygon points="{{ $cell['points'] }}" fill="{{ $cell['dark'] ? '#1c0f26' : '#f2780f' }}"/>@endforeach</g>
                <rect y="616" width="640" height="8" fill="#0b0414" opacity=".35"/>

                {{-- Ma và đèn bí --}}
                <g class="hw-float" transform="translate(556 404)">
                    <path d="M0 40Q0 0 30 0t30 40v38l-10-8-10 8-10-8-10 8-10-8-10 8z" fill="#f8fafc"/>
                    <ellipse cx="21" cy="34" rx="4" ry="6" fill="#1c0f26"/><ellipse cx="39" cy="34" rx="4" ry="6" fill="#1c0f26"/><ellipse cx="30" cy="50" rx="5" ry="4" fill="#1c0f26"/>
                </g>
                <g>
                    <rect x="28" y="572" width="18" height="118" rx="3" fill="#f5f0e6"/><path d="M28 584q9 6 18 0" stroke="#e2d6c0" stroke-width="2" fill="none"/>
                    <path class="hw-flame" d="M37 548q10 12 0 22-10-10 0-22z" fill="#ffd54a"/>
                </g>
                <use href="#hw-pumpkin" x="66" y="580" width="132" height="114"/>
                <use href="#hw-jack" x="198" y="466" width="244" height="212"/>
                <use href="#hw-jack" x="446" y="560" width="148" height="128"/>
                <use href="#hw-pumpkin" x="150" y="676" width="84" height="73"/>
                <use href="#hw-candy" x="430" y="716" width="60" height="26" color="#a855f7"/>
                <use href="#hw-candy" x="498" y="738" width="52" height="22" color="#22c55e"/>
                <use href="#hw-candy" x="88" y="730" width="48" height="21" color="#ef4444"/>

                {{-- Chú thích --}}
                <rect y="720" width="640" height="120" fill="url(#hw-fade)"/>
                <text x="320" y="776" text-anchor="middle" font-size="14" fill="#f5e9ff" class="hw-font" font-weight="500">(*) Áp dụng từ 27/10 đến 31/10/2026. Miễn phí giao cho đơn từ 300.000đ.</text>

                {{-- Huy hiệu FREESHIP --}}
                <g class="hw-badge">
                    <polygon points="{{ $burst(560, 78, 70, 54, 16) }}" fill="#dc2626"/>
                    <polygon points="{{ $burst(560, 78, 58, 46, 16) }}" fill="#ffd54a"/>
                    <text x="560" y="70" text-anchor="middle" class="hw-font" font-size="15" font-weight="700" fill="#b91c1c">FREESHIP</text>
                    <text x="560" y="104" text-anchor="middle" class="hw-font" font-size="34" font-weight="700" fill="#b91c1c">0Đ</text>
                </g>
            </svg>
        </a>
        <a class="hw-cta" href="{{ route('rescue.index') }}">MUA NGAY <span aria-hidden="true">▸</span></a>
    </div>
</div>

<style>
    .hw-popup{position:fixed;inset:0;z-index:200;display:grid;place-items:center;padding:40px 24px}
    .hw-popup[hidden]{display:none}
    .hw-backdrop{position:absolute;inset:0;background:rgba(10,4,20,.62)}
    .hw-dialog{position:relative;width:min(430px,calc(100vw - 48px),calc((100vh - 110px) * 640 / 840));animation:hw-pop .35s cubic-bezier(.2,.9,.3,1.2) both}
    .hw-art{display:block;border-radius:16px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.45)}
    .hw-art svg{display:block;width:100%;height:auto}
    .hw-font{font-family:'Be Vietnam Pro',sans-serif}
    .hw-headline text{fill:url(#hw-title-fill);stroke:#2a0a3d;stroke-width:6;paint-order:stroke;stroke-linejoin:round}
    .hw-close{position:absolute;top:-16px;right:-16px;z-index:2;width:42px;height:42px;display:grid;place-items:center;border:2px solid #fff;border-radius:50%;background:rgba(30,12,48,.85);cursor:pointer}
    .hw-close svg{width:20px;height:20px;stroke:#fff;stroke-width:2.6;stroke-linecap:round}
    .hw-cta{position:absolute;left:50%;bottom:-24px;transform:translateX(-50%);display:flex;align-items:center;gap:8px;padding:12px 34px;border-radius:999px;border:2px solid #fff;background:linear-gradient(180deg,#ff9a3c,#ea580c);color:#fff;font:700 20px 'Be Vietnam Pro',sans-serif;white-space:nowrap;box-shadow:0 8px 20px rgba(234,88,12,.45)}
    .hw-cta:hover{background:linear-gradient(180deg,#ffab5c,#f26a13);color:#fff}
    .hw-flap{transform-box:fill-box;transform-origin:center;animation:hw-flap .5s ease-in-out infinite alternate}
    .hw-float{animation:hw-float 3s ease-in-out infinite}
    .hw-swing{animation:hw-swing 4s ease-in-out infinite}
    .hw-twinkle{animation:hw-twinkle 2.4s ease-in-out infinite}
    .hw-flame{transform-box:fill-box;transform-origin:bottom;animation:hw-flame .6s ease-in-out infinite alternate}
    .hw-badge{transform-box:fill-box;transform-origin:center;animation:hw-badge 2s ease-in-out infinite}
    @keyframes hw-pop{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:none}}
    @keyframes hw-flap{to{transform:scaleY(.6)}}
    @keyframes hw-float{50%{transform:translate(556px,392px)}}
    @keyframes hw-swing{50%{transform:translate(26px,272px)}}
    @keyframes hw-twinkle{50%{opacity:.25}}
    @keyframes hw-flame{to{transform:scale(.85,1.12)}}
    @keyframes hw-badge{50%{transform:rotate(-6deg) scale(1.05)}}
    @media (prefers-reduced-motion:reduce){.hw-popup *{animation:none!important}}
</style>
<script>
    (() => {
        const popup = document.getElementById('hw-popup');
        const key = 'hw-popup-seen';
        const today = new Date().toISOString().slice(0, 10);
        let seen = null;
        try { seen = localStorage.getItem(key); } catch (e) {}
        if (seen === today) return;

        let lastFocus = null;
        const onKey = event => { if (event.key === 'Escape') close(); };
        const close = () => {
            popup.hidden = true;
            document.removeEventListener('keydown', onKey);
            try { localStorage.setItem(key, today); } catch (e) {}
            lastFocus?.focus();
        };
        popup.querySelectorAll('[data-hw-close]').forEach(el => el.addEventListener('click', close));
        popup.querySelector('.hw-cta').addEventListener('click', () => { try { localStorage.setItem(key, today); } catch (e) {} });

        window.setTimeout(() => {
            lastFocus = document.activeElement;
            popup.hidden = false;
            document.addEventListener('keydown', onKey);
            popup.querySelector('.hw-close').focus();
        }, 600);
    })();
</script>
@endif
