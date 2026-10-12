/* Olho Cidadão v1.0.3 — PWA: páginas privadas nunca são armazenadas.
 * Rede primeiro para CSS/JS/imagens públicas (evita alterações antigas após deploy).
 * Só cacheia recursos da pasta pública, nunca respostas JSON ou anexos de cidadãos.
 */
const ESCOPO = new URL(self.registration.scope).pathname;
const VERSAO = `oc-estaticos-v103-${ESCOPO.replace(/[^a-z0-9]/gi, '-')}`;
const ESTATICOS = [
    'offline.html', 'js/offline.js', 'img/icon-192.png', 'img/logo.png',
    'css/dashboard.css', 'css/responsivo.css', 'css/acessibilidade.css'
].map(caminho => new URL(caminho, self.registration.scope).href);
const OFFLINE = new URL('offline.html', self.registration.scope).href;

self.addEventListener('install', event => {
    event.waitUntil((async () => {
        const cache = await caches.open(VERSAO);
        // Um recurso opcional fora do ar não deve impedir a instalação do PWA.
        await Promise.all(ESTATICOS.map(async recurso => {
            try {
                const resposta = await fetch(recurso, { cache: 'reload' });
                if (resposta.ok && resposta.type === 'basic') await cache.put(recurso, resposta);
            } catch (_) { /* O PWA ainda pode usar os recursos que estiverem disponíveis. */ }
        }));
        await self.skipWaiting();
    })());
});

self.addEventListener('activate', event => {
    event.waitUntil((async () => {
        const chaves = await caches.keys();
        await Promise.all(chaves.filter(chave =>
            (chave.startsWith('oc-estaticos-v') && chave.endsWith(ESCOPO.replace(/[^a-z0-9]/gi, '-')) && chave !== VERSAO)
            || chave === 'oc-v0528-mapas'
        ).map(chave => caches.delete(chave)));
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', event => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin || !url.pathname.startsWith(ESCOPO)) return;

    if (req.mode === 'navigate') {
        // Nunca cachear HTML autenticado, formulários, tokens ou relatórios.
        event.respondWith(fetch(req).catch(async () =>
            (await caches.match(OFFLINE)) || Response.error()
        ));
        return;
    }

    if (url.searchParams.has('rota') || /\/uploads?\//i.test(url.pathname)) return;
    const relativo = url.pathname.substring(ESCOPO.length);
    if (!/^(?:css|js|img|vendor)\/[a-z0-9_./-]+\.(?:css|js|png|jpg|jpeg|svg|webp|ico|woff2)$/i.test(relativo)) return;
    // Não armazenar nada com token, sessão ou parâmetros inesperados.
    if ([...url.searchParams.keys()].some(chave => chave !== 'v')) return;
    event.respondWith((async () => {
        const cache = await caches.open(VERSAO);
        try {
            const rede = await fetch(req, { cache: 'no-cache' });
            if (rede.ok && rede.type === 'basic') {
                await cache.put(req, rede.clone());
            }
            return rede;
        } catch (_) {
            return (await cache.match(req)) || (await cache.match(url.origin + url.pathname)) || Response.error();
        }
    })());
});
