/* Tela da denúncia: compartilhar, copiar protocolo e destacar o botão de apoiar (#apoiar). */
document.addEventListener('DOMContentLoaded', function () {
    function toast(m, t) { if (window.mostrarToast) window.mostrarToast(m, t || 'sucesso', 2600); }
    function copiar(texto, msg) {
        const ok = function () { toast(msg); };
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(texto).then(ok, antigo); else antigo();
        function antigo() {
            const c = document.createElement('textarea'); c.value = texto; c.style.position = 'fixed'; c.style.opacity = '0';
            document.body.appendChild(c); c.select();
            try { document.execCommand('copy'); ok(); } catch (e) {} c.remove();
        }
    }
    const comp = document.getElementById('btn-compartilhar');
    if (comp) comp.addEventListener('click', function () {
        const url = new URL(comp.dataset.url, location.origin).href;
        if (navigator.share) navigator.share({ title: comp.dataset.titulo, text: 'Acompanhe esta denúncia no Olho Cidadão', url: url }).catch(function () {});
        else copiar(url, 'Link da consulta copiado. É só colar e enviar.');
    });
    const prot = document.getElementById('btn-copiar-protocolo');
    if (prot) prot.addEventListener('click', function () { copiar(prot.dataset.protocolo, 'Protocolo ' + prot.dataset.protocolo + ' copiado.'); });

    // Veio do "deslizar para apoiar" do mapa: rola até o botão e chama atenção.
    if (location.hash === '#apoiar') {
        const alvo = document.querySelector('#apoiar .acao-redonda');
        if (alvo) { alvo.scrollIntoView({ block: 'center' }); alvo.classList.add('chamando'); alvo.focus(); }
    }
});
