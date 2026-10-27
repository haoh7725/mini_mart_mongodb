@props(['theme' => 'green'])
<svg class="hero-art" viewBox="0 0 520 440" aria-hidden="true" focusable="false">
    <path class="hero-art-blob" d="M262 24c78-8 168 30 207 102 40 74 22 172-38 228-58 54-150 76-232 58C116 394 40 340 22 262 4 186 42 96 110 56c44-26 96-28 152-32z"/>
    <circle class="hero-art-ring" cx="262" cy="220" r="196" fill="none" stroke-width="1.5" stroke-dasharray="6 10"/>
    <g class="hero-art-spark">
        <path d="M70 70l6 16 16 6-16 6-6 16-6-16-16-6 16-6z"/><path d="M462 330l4 11 11 4-11 4-4 11-4-11-11-4 11-4z"/><path d="M440 52l3 8 8 3-8 3-3 8-3-8-8-3 8-3z"/>
    </g>
    @switch($theme)
        @case('green')
            <g class="hero-art-deco">
                <path d="M44 300c10-56 58-86 108-80-8 52-50 90-108 80z"/><path d="M60 292c26-22 52-40 86-64" fill="none" stroke="#fff" stroke-width="3" opacity=".7"/>
                <path d="M420 120c40-30 88-26 96 4-34 30-78 30-96-4z"/>
                <circle cx="470" cy="236" r="18" class="hero-art-accent"/><path d="M466 216q6-10 14-8-4 8-14 8z"/>
                <circle cx="96" cy="140" r="12" class="hero-art-accent"/>
            </g>
            @break
        @case('ocean')
            <g class="hero-art-deco" fill="none" stroke-width="5" stroke-linecap="round">
                <path d="M30 330q30-22 60 0t60 0t60 0"/><path d="M340 380q30-22 60 0t60 0"/><path d="M380 70q22-16 44 0t44 0"/>
            </g>
            <g class="hero-art-accent"><circle cx="78" cy="120" r="10"/><circle cx="104" cy="92" r="6"/><circle cx="470" cy="250" r="12"/><circle cx="492" cy="222" r="6"/></g>
            <g class="hero-art-deco"><path d="M40 210q30-26 64-6l20-14v40l-20-14q-34 20-64-6z"/><circle cx="56" cy="206" r="3" fill="#fff"/></g>
            @break
        @case('sun')
            <g class="hero-art-deco"><circle cx="452" cy="96" r="30"/><g stroke-width="5" stroke-linecap="round" fill="none" class="hero-art-stroke"><path d="M452 46v-16M452 162v-16M402 96h-16M518 96h-16M417 61l-11-11M498 142l-11-11M417 131l-11 11M498 50l-11 11"/></g></g>
            <g class="hero-art-accent"><ellipse cx="70" cy="300" rx="26" ry="32"/><ellipse cx="112" cy="330" rx="22" ry="28"/></g>
            <path d="M60 140c10-20 40-20 50 0 20-4 30 20 14 30H52c-16-8-8-34 8-30z" fill="#fff" opacity=".9"/>
            @break
        @case('earth')
            <g class="hero-art-deco">
                <path d="M60 330c-6-40 4-90 20-120M80 210c-14 4-24-6-22-18 12 0 22 8 22 18zM74 250c-14 4-24-6-22-18 12 0 22 8 22 18zM70 290c-14 4-24-6-22-18 12 0 22 8 22 18zM86 230c14 2 22-10 18-22-12 2-20 12-18 22zM82 270c14 2 22-10 18-22-12 2-20 12-18 22z" fill="none" stroke-width="4" class="hero-art-stroke"/>
            </g>
            <g class="hero-art-accent"><ellipse cx="452" cy="300" rx="7" ry="12" transform="rotate(30 452 300)"/><ellipse cx="474" cy="320" rx="7" ry="12" transform="rotate(-20 474 320)"/><ellipse cx="440" cy="330" rx="7" ry="12" transform="rotate(70 440 330)"/><ellipse cx="462" cy="96" rx="7" ry="12" transform="rotate(10 462 96)"/></g>
            @break
    @endswitch
</svg>
