@php
    $t = $tam ?? 44;
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="{{ $t }}" height="{{ $t }}" role="img" aria-label="CafeQR">
    <defs>
        <linearGradient id="cqg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#9a1830"/>
            <stop offset="1" stop-color="#560a1a"/>
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="16" fill="url(#cqg)"/>
    <path d="M22 11c-3 4 3 6 0 11M32 9c-3 4 3 6 0 11M42 11c-3 4 3 6 0 11" fill="none" stroke="#e8cfa7" stroke-width="2.4" stroke-linecap="round"/>
    <path d="M14 27h30v11a11 11 0 0 1-11 11h-8a11 11 0 0 1-11-11V27z" fill="#fff"/>
    <path d="M44 30h3.5a5.5 5.5 0 0 1 0 11H44" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round"/>
    <g fill="#7d1126">
        <rect x="19" y="31" width="7" height="7" rx="1"/>
        <rect x="32" y="31" width="7" height="7" rx="1"/>
        <rect x="19" y="42" width="7" height="3" rx="1"/>
        <rect x="29" y="42" width="3" height="3"/>
        <rect x="35" y="42" width="4" height="3" rx="1"/>
    </g>
    <g fill="#fff">
        <rect x="21" y="33" width="3" height="3"/>
        <rect x="34" y="33" width="3" height="3"/>
    </g>
</svg>