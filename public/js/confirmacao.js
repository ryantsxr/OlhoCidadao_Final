/**
 * Modal de confirmação reutilizável, no tema do site — substitui o
 * confirm() nativo do navegador (que é feio e não combina com o resto).
 *
 * Uso: em qualquer <form>, troque
 *   onsubmit="return confirm('Tem certeza?')"
 * por
 *   data-confirmar="Tem certeza?"
 * Esse script cuida do resto: intercepta o submit, mostra o modal, e só
 * envia o formulário de verdade se a pessoa clicar em "Confirmar".
 */
(function () {
    function criarModal() {
        if (document.getElementById('modal-confirmacao')) return;

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirmacao';
        overlay.className = 'modal-confirmacao-overlay';
        overlay.innerHTML = `
            <div class="modal-confirmacao-caixa" role="alertdialog" aria-modal="true">
                <i class="fa-solid fa-triangle-exclamation modal-confirmacao-icone"></i>
                <p class="modal-confirmacao-texto"></p>
                <div class="modal-confirmacao-botoes">
                    <button type="button" class="modal-confirmacao-cancelar">Cancelar</button>
                    <button type="button" class="modal-confirmacao-confirmar">Confirmar</button>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    document.addEventListener('DOMContentLoaded', function () {
        criarModal();

        const overlay = document.getElementById('modal-confirmacao');
        const texto = overlay.querySelector('.modal-confirmacao-texto');
        const btnCancelar = overlay.querySelector('.modal-confirmacao-cancelar');
        const btnConfirmar = overlay.querySelector('.modal-confirmacao-confirmar');
        let formPendente = null;

        function abrir(form) {
            formPendente = form;
            texto.textContent = form.dataset.confirmar;
            overlay.classList.add('ativo');
            btnConfirmar.focus();
        }

        function fechar() {
            overlay.classList.remove('ativo');
            formPendente = null;
        }

        document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                // Depois de confirmado uma vez, deixa o submit real acontecer.
                if (form.dataset.confirmado === '1') return;
                e.preventDefault();
                abrir(form);
            });
        });

        btnCancelar.addEventListener('click', fechar);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) fechar();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('ativo')) fechar();
        });

        btnConfirmar.addEventListener('click', function () {
            if (formPendente) {
                formPendente.dataset.confirmado = '1';
                if (formPendente.requestSubmit) {
                    formPendente.requestSubmit();
                } else {
                    formPendente.submit();
                }
            }
            fechar();
        });
    });
})();
