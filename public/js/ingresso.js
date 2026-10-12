/* Passo 3: ingresso do protocolo — QR code, copiar e confete na primeira denúncia. */
(function () {
    const script = document.currentScript;

    document.addEventListener('DOMContentLoaded', function () {
        // --------------------------------------------------------- QR code
        const caixaQr = document.getElementById('ingresso-qr');
        if (caixaQr && typeof qrcode === 'function') {
            const url = new URL(caixaQr.dataset.url, location.origin).href;
            const qr = qrcode(0, 'M');
            qr.addData(url);
            qr.make();
            const n = qr.getModuleCount();
            let caminho = '';
            for (let y = 0; y < n; y++) {
                for (let x = 0; x < n; x++) {
                    if (qr.isDark(y, x)) caminho += 'M' + x + ' ' + y + 'h1v1h-1z';
                }
            }
            caixaQr.innerHTML = '<svg viewBox="-2 -2 ' + (n + 4) + ' ' + (n + 4) + '" shape-rendering="crispEdges">' +
                '<rect x="-2" y="-2" width="' + (n + 4) + '" height="' + (n + 4) + '" fill="#fff"/>' +
                '<path d="' + caminho + '" fill="#0b1020"/></svg>';
            caixaQr.title = url;
        }

        // --------------------------------------------------------- copiar
        const btn = document.getElementById('btn-copiar');
        if (btn) {
            btn.addEventListener('click', function () {
                const texto = btn.dataset.protocolo;
                const pronto = function () {
                    btn.classList.add('copiado');
                    btn.querySelector('i').className = 'fa-solid fa-check';
                    btn.querySelector('span').textContent = 'Copiado!';
                    if (window.mostrarToast) window.mostrarToast('Protocolo ' + texto + ' copiado.', 'sucesso', 2500);
                    setTimeout(function () {
                        btn.classList.remove('copiado');
                        btn.querySelector('i').className = 'fa-regular fa-copy';
                        btn.querySelector('span').textContent = 'Copiar protocolo';
                    }, 2200);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(texto).then(pronto, copiarAntigo);
                } else {
                    copiarAntigo();
                }
                // No XAMPP sem HTTPS (fora do localhost) o clipboard moderno não existe.
                function copiarAntigo() {
                    const campo = document.createElement('textarea');
                    campo.value = texto;
                    campo.setAttribute('readonly', '');
                    campo.style.position = 'fixed';
                    campo.style.opacity = '0';
                    document.body.appendChild(campo);
                    campo.select();
                    try { document.execCommand('copy'); pronto(); } catch (e) { /* sem cópia */ }
                    campo.remove();
                }
            });
        }

        // --------------------------------------------------------- confete
        if (script && script.hasAttribute('data-confete') && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            confete();
        }
    });

    function confete() {
        const c = document.createElement('canvas');
        c.className = 'oc-confete';
        c.setAttribute('aria-hidden', 'true');
        document.body.appendChild(c);
        const ctx = c.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        function medir() { c.width = innerWidth * dpr; c.height = innerHeight * dpr; }
        medir();
        addEventListener('resize', medir);
        const cores = ['#22c55e', '#4ade80', '#facc15', '#38bdf8', '#a78bfa', '#ffffff'];
        const pecas = Array.from({ length: 140 }, function () {
            return {
                x: innerWidth / 2 + (Math.random() - .5) * 120,
                y: innerHeight * .35,
                vx: (Math.random() - .5) * 13,
                vy: -Math.random() * 13 - 4,
                r: Math.random() * Math.PI,
                vr: (Math.random() - .5) * .3,
                w: 6 + Math.random() * 6,
                h: 8 + Math.random() * 8,
                cor: cores[Math.floor(Math.random() * cores.length)]
            };
        });
        const inicio = performance.now();
        (function quadro(agora) {
            const t = agora - inicio;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            ctx.clearRect(0, 0, innerWidth, innerHeight);
            pecas.forEach(function (p) {
                p.vy += .32; p.vx *= .99; p.x += p.vx; p.y += p.vy; p.r += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.r);
                ctx.globalAlpha = Math.max(0, 1 - t / 3200);
                ctx.fillStyle = p.cor;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h * Math.abs(Math.cos(p.r * 2)));
                ctx.restore();
            });
            if (t < 3300) requestAnimationFrame(quadro); else { c.remove(); removeEventListener('resize', medir); }
        })(inicio);
    }
})();
