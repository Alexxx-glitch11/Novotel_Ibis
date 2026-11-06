@if ($code === 'en')
    <svg viewBox="0 0 60 30" preserveAspectRatio="xMidYMid slice" class="h-[18px] w-6 rounded-sm ring-1 ring-[#ddd]" aria-hidden="true">
        <clipPath id="uk-s"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
        <clipPath id="uk-t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
        <g clip-path="url(#uk-s)">
            <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
            <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
            <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#uk-t)" stroke="#C8102E" stroke-width="4"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
            <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
        </g>
    </svg>
@else
    <svg viewBox="0 0 3 2" preserveAspectRatio="none" class="h-[18px] w-6 rounded-sm ring-1 ring-[#ddd]" aria-hidden="true">
        <rect width="3" height="1" fill="#e11d2e"/>
        <rect y="1" width="3" height="1" fill="#fff"/>
    </svg>
@endif