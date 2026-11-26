<x-filament-panels::page>
    {{ $this->table }}

    <script>
        window.addEventListener('download-multiple-zips', function (event) {
            const urls = event.detail?.urls 
                || (Array.isArray(event.detail) && event.detail[0]?.urls) 
                || (Array.isArray(event.detail) ? event.detail : []);

            if (!Array.isArray(urls) || urls.length === 0) {
                return;
            }

            urls.forEach(function (url, index) {
                setTimeout(function () {
                    const a = document.createElement('a');
                    a.href = url;
                    a.setAttribute('download', '');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }, index * 800);
            });
        });
    </script>
</x-filament-panels::page>
