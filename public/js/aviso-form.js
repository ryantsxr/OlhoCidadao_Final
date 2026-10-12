/* Aviso dentro do formulário (v0.49.2) — telas de login, cadastro e recuperação de senha.
 * mostrarAviso(mensagem, tipo): mostra a mensagem logo abaixo do título do formulário
 * e ela fica ali até o usuário tentar de novo (não some sozinha como o toast). */
(function () {
    'use strict';
    var ICONES = {
        erro: 'fa-circle-exclamation',
        sucesso: 'fa-circle-check',
        aviso: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    window.mostrarAviso = function (mensagem, tipo) {
        if (!mensagem) return;
        tipo = ICONES[tipo] ? tipo : 'erro';

        var form = document.querySelector('.right form') || document.querySelector('form');
        if (!form) {
            if (window.mostrarToast) window.mostrarToast(mensagem, tipo);
            return;
        }

        var caixa = form.querySelector('.aviso-form');
        if (!caixa) {
            caixa = document.createElement('div');
            var titulo = form.querySelector('h2');
            if (titulo) titulo.insertAdjacentElement('afterend', caixa);
            else form.insertBefore(caixa, form.firstChild);
        }

        caixa.className = 'aviso-form aviso-form-' + tipo;
        caixa.setAttribute('role', tipo === 'erro' ? 'alert' : 'status');

        var icone = document.createElement('i');
        icone.className = 'fa-solid ' + ICONES[tipo];
        icone.setAttribute('aria-hidden', 'true');

        var texto = document.createElement('span');
        texto.textContent = mensagem;

        caixa.replaceChildren(icone, texto);

        // Reinicia a animação quando o aviso é trocado por outro.
        caixa.style.animation = 'none';
        void caixa.offsetWidth;
        caixa.style.animation = '';
    };
})();
