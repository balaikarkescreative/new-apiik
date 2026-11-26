@php
    $currentMonth = now()->month;
    $isAugust = $currentMonth === 8;
    $hutYear = now()->year - 1945;
@endphp

@if($isAugust)
<div id="hut-ri-banner" class="hut-ri-banner-simple" data-year="{{ $hutYear }}" style="position: sticky; top: 0; z-index: 40; background: linear-gradient(-45deg, #dc2626, #ffffff, #dc2626, #ffffff); background-size: 400% 400%; border-bottom: 2px solid #b91c1c; padding: 8px 0; text-align: center;">
    <div style="color: #991b1b; font-weight: bold; font-size: 14px; letter-spacing: 0.5px; text-shadow: 1px 1px 2px rgba(255,255,255,0.8);">
        🇮🇩 DIRGAHAYU REPUBLIK INDONESIA KE-{{ $hutYear }} 🇮🇩
    </div>
    <button id="hut-ri-close" aria-label="Tutup banner HUT RI" title="Tutup" style="position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.12); border: none; color: #7f1d1d; padding: 6px 10px; border-radius: 6px; font-weight: 700; cursor: pointer;">
        ×
    </button>
</div>

<style>
.hut-ri-banner-simple {
    animation: patriotic-gradient 4s ease infinite;
    transition: transform 220ms ease, opacity 220ms ease, height 220ms ease, padding 220ms ease;
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
    #hut-ri-close {
        right: 0.25rem;
        padding: 5px 8px;
    }
}
</style>

<script>
    (function(){
        // Early return if localStorage disabled
        try {
            var key = 'hut-ri-dismissed-{{ $hutYear }}';
            if (localStorage && localStorage.getItem && localStorage.getItem(key)) {
                var el = document.getElementById('hut-ri-banner');
                if (el) el.style.display = 'none';
                return;
            }
        } catch (e) {
            // ignore
        }

        document.addEventListener('DOMContentLoaded', function(){
            var banner = document.getElementById('hut-ri-banner');
            var btn = document.getElementById('hut-ri-close');
            if (!banner || !btn) return;

            btn.addEventListener('click', function(){
                try { localStorage.setItem(key, '1'); } catch(e){}
                // animate collapse
                banner.style.opacity = '0';
                banner.style.transform = 'translateY(-6px)';
                banner.style.padding = '0';
                setTimeout(function(){ banner.style.display = 'none'; }, 240);
            });
        });
    })();
</script>
@endif
