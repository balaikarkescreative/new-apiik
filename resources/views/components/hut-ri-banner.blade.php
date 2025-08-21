@php
    $currentMonth = now()->month;
    $isAugust = $currentMonth === 8;
    $hutYear = now()->year - 1945;
@endphp

@if($isAugust)
<div class="hut-ri-banner-simple" style="position: sticky; top: 0; z-index: 40; background: linear-gradient(-45deg, #dc2626, #ffffff, #dc2626, #ffffff); background-size: 400% 400%; border-bottom: 2px solid #b91c1c; padding: 8px 0; text-align: center;">
    <div style="color: #991b1b; font-weight: bold; font-size: 14px; letter-spacing: 0.5px; text-shadow: 1px 1px 2px rgba(255,255,255,0.8);">
        🇮🇩 DIRGAHAYU REPUBLIK INDONESIA KE-{{ $hutYear }} 🇮🇩
    </div>
</div>

<style>
.hut-ri-banner-simple {
    animation: patriotic-gradient 4s ease infinite;
}

@keyframes patriotic-gradient {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .hut-ri-banner-simple div {
        font-size: 12px !important;
    }
}
</style>
@endif
