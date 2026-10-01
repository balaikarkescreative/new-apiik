<script>
    if (!window.__apiikNumberInputsDisabled) {
        window.__apiikNumberInputsDisabled = true;

        // Cegah tombol panah keyboard (Arrow Up & Down) mengubah nilai pada input type="number"
        document.addEventListener('keydown', function (e) {
            if (e.target && e.target.matches && e.target.matches('input[type="number"]')) {
                if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                    e.preventDefault();
                }
            }
        });

        // Lepas fokus (blur) saat mouse wheel / scroll di atas input type="number" yang sedang aktif
        document.addEventListener('wheel', function (e) {
            if (document.activeElement && document.activeElement.matches && document.activeElement.matches('input[type="number"]')) {
                document.activeElement.blur();
            }
        }, { passive: true });
    }
</script>
