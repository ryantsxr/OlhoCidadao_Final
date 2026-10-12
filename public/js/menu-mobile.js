// ===============================
// MENU LATERAL RESPONSIVO (MOBILE)
// ===============================
// Controla a abertura/fechamento do menu em formato "gaveta"
// nas telas pequenas (dashboard, perfil e configurações
// compartilham a mesma estrutura .layout > .menu + .conteudo).
document.addEventListener('DOMContentLoaded', () => {
    const menu = document.querySelector('.menu');
    const btnAbrir = document.getElementById('btn-menu-abrir');
    const overlay = document.getElementById('menu-overlay');

    if (!menu || !btnAbrir || !overlay) return;

    btnAbrir.setAttribute('aria-controls', menu.id || 'menu-lateral');
    if (!menu.id) menu.id = 'menu-lateral';
    btnAbrir.setAttribute('aria-expanded', 'false');

    function abrirMenu() {
        menu.classList.add('menu-aberto');
        overlay.classList.add('ativo');
        document.body.classList.add('menu-bloqueado');
        btnAbrir.setAttribute('aria-expanded', 'true');
        const primeiroLink = menu.querySelector('a, button');
        if (primeiroLink) primeiroLink.focus({ preventScroll: true });
    }

    function fecharMenu(devolverFoco = false) {
        menu.classList.remove('menu-aberto');
        overlay.classList.remove('ativo');
        document.body.classList.remove('menu-bloqueado');
        btnAbrir.setAttribute('aria-expanded', 'false');
        if (devolverFoco) btnAbrir.focus({ preventScroll: true });
    }

    btnAbrir.addEventListener('click', abrirMenu);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('menu-aberto')) {
            fecharMenu(true);
        }
    });
    overlay.addEventListener('click', () => fecharMenu(true));

    // Fecha o menu automaticamente ao clicar em algum link de navegação
    menu.querySelectorAll('nav a, .usuario-acoes a').forEach((link) => {
        link.addEventListener('click', () => fecharMenu());
    });

    // Se o usuário girar a tela ou redimensionar para desktop, garante que o menu volte ao normal
    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) {
            fecharMenu();
        }
    });
});
