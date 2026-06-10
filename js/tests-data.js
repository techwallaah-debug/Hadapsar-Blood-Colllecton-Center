(function () {
    let cachedPromise = null;

    function extractArray(source, name) {
        const pattern = new RegExp(`const\\s+${name}\\s*=\\s*(\\[[\\s\\S]*?\\]);`, 'm');
        const match = source.match(pattern);
        if (!match || !match[1]) {
            throw new Error(`Unable to find ${name} data in index.html`);
        }
        return new Function(`return ${match[1]}`)();
    }

    async function loadTestsData() {
        if (window.__HBC_TESTS_DATA__) {
            return window.__HBC_TESTS_DATA__;
        }

        if (!cachedPromise) {
            cachedPromise = (async () => {
                const response = await fetch('index.html', { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error(`Failed to load index.html (${response.status})`);
                }

                const html = await response.text();
                const data = {
                    packages: extractArray(html, 'packages'),
                    individualTests: extractArray(html, 'individualTests')
                };

                window.__HBC_TESTS_DATA__ = data;
                return data;
            })();
        }

        return cachedPromise;
    }

    window.loadTestsData = loadTestsData;
})();
