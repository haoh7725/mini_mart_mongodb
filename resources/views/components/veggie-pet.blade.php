@props(['pet' => \App\Support\AssistantPet::DEFAULT])
@php
    // Mỗi thú cưng: phần thân vẽ bằng SVG và vị trí khuôn mặt [tâm x, mắt y, nửa khoảng cách hai mắt].
    $pets = [
        'broccoli' => ['face' => [32, 43, 5], 'body' => '<path d="M21 34h22l-3 21a5 5 0 0 1-5 4h-6a5 5 0 0 1-5-4z" fill="#b5e48c"/><g fill="#2f9e44"><circle cx="19" cy="27" r="10"/><circle cx="32" cy="20" r="12"/><circle cx="45" cy="27" r="10"/><circle cx="26" cy="33" r="8"/><circle cx="38" cy="33" r="8"/></g><g fill="#51cf66"><circle cx="28" cy="16" r="3"/><circle cx="16" cy="24" r="2.5"/><circle cx="43" cy="22" r="2.5"/></g>'],
        'carrot' => ['face' => [32, 27, 6], 'body' => '<path d="M32 17c-3-6-8-9-12-8 2 5 7 8 12 8zm0 0c0-7 2-11 6-13 1 5-1 10-6 13zm0 0c3-5 8-7 12-6-2 4-7 6-12 6z" fill="#40c057"/><path d="M17 23c0-4 7-6 15-6s15 2 15 6c0 12-7 28-13 36-1 2-3 2-4 0-6-8-13-24-13-36z" fill="#fd7e14"/><path d="M22 41h4m14 4h4m-15 7h4" stroke="#e8590c" stroke-width="2" stroke-linecap="round"/>'],
        'tomato' => ['face' => [32, 38, 7], 'body' => '<circle cx="32" cy="38" r="21" fill="#fa5252"/><ellipse cx="22" cy="29" rx="5" ry="3" fill="#fff" opacity=".35"/><path d="M32 22l-7-6 5 4-6 3h7l1 5 1-5h7l-6-3 5-4z" fill="#2f9e44"/><path d="M32 18v-7" stroke="#2f9e44" stroke-width="3" stroke-linecap="round"/>'],
        'eggplant' => ['face' => [32, 38, 6], 'body' => '<path d="M23 23c7-5 17-3 21 6 6 12 4 28-8 31-12 3-20-8-18-20 1-7 2-13 5-17z" fill="#7048e8"/><ellipse cx="25" cy="33" rx="3" ry="6" fill="#fff" opacity=".2"/><path d="M20 24c5-7 17-9 24-2-4 0-8 2-10 5-3-3-9-4-14-3z" fill="#2f9e44"/><path d="M33 18l2-9" stroke="#2f9e44" stroke-width="3" stroke-linecap="round"/>'],
        'corn' => ['face' => [32, 32, 5], 'body' => '<path d="M32 8c8 0 12 10 12 22s-4 27-12 27-12-15-12-27S24 8 32 8z" fill="#fcc419"/><g fill="#f59f00"><circle cx="27" cy="16" r="1.6"/><circle cx="32" cy="14" r="1.6"/><circle cx="37" cy="16" r="1.6"/><circle cx="26" cy="22" r="1.6"/><circle cx="38" cy="22" r="1.6"/><circle cx="28" cy="46" r="1.6"/><circle cx="36" cy="46" r="1.6"/><circle cx="32" cy="51" r="1.6"/></g><path d="M20 30c-5 10-3 23 9 29-4-9-4-19-1-27zm24 0c5 10 3 23-9 29 4-9 4-19 1-27z" fill="#51cf66"/>'],
        'avocado' => ['face' => [32, 29, 6], 'body' => '<path d="M32 6c9 0 15 14 17 26 2 14-6 26-17 26S13 46 15 32C17 20 23 6 32 6z" fill="#2b8a3e"/><path d="M32 11c7 0 12 12 13 22 1 11-5 20-13 20s-14-9-13-20c1-10 6-22 13-22z" fill="#c0eb75"/><circle cx="32" cy="44" r="7" fill="#a0522d"/><circle cx="30" cy="42" r="2" fill="#fff" opacity=".3"/>'],
    ];
    ['face' => [$cx, $ey, $dx], 'body' => $body] = $pets[$pet] ?? $pets[\App\Support\AssistantPet::DEFAULT];
@endphp
<svg {{ $attributes->merge(['class' => 'veggie-pet pet-'.$pet]) }} viewBox="0 0 64 64" aria-hidden="true" focusable="false">
    <g class="pet-body">{!! $body !!}</g>
    <g class="pet-eyes">
        @foreach([$cx - $dx, $cx + $dx] as $x)
            <circle cx="{{ $x }}" cy="{{ $ey }}" r="3.4" fill="#fff"/><circle cx="{{ $x + .4 }}" cy="{{ $ey + .3 }}" r="2.1" fill="#212529"/><circle cx="{{ $x + 1.1 }}" cy="{{ $ey - .6 }}" r=".7" fill="#fff"/>
        @endforeach
    </g>
    <ellipse cx="{{ $cx - $dx - 2.5 }}" cy="{{ $ey + 5 }}" rx="2.6" ry="1.6" fill="#ff8787" opacity=".75"/>
    <ellipse cx="{{ $cx + $dx + 2.5 }}" cy="{{ $ey + 5 }}" rx="2.6" ry="1.6" fill="#ff8787" opacity=".75"/>
    <path d="M{{ $cx - 3 }} {{ $ey + 4.5 }}q3 3.2 6 0" fill="none" stroke="#212529" stroke-width="1.8" stroke-linecap="round"/>
</svg>
