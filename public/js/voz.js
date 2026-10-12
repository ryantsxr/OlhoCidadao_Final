/*
 * Denúncia por voz (v0.50): botão de microfone ao lado do campo de descrição.
 * Usa o reconhecimento de voz do próprio navegador (Chrome, Edge, Safari) —
 * não precisa de chave nem de instalar nada. Atenção: no Chrome o áudio é
 * convertido em texto por um serviço do Google (precisa de internet); o
 * sistema só recebe o texto. No Firefox o botão não aparece.
 * O navegador só libera o microfone em https:// ou em localhost.
 */
(function () {
    'use strict';
    const Reconhecimento = window.SpeechRecognition || window.webkitSpeechRecognition;
    const campo = document.getElementById('descricao');
    if (!campo || !Reconhecimento) return;

    const rotulo = document.querySelector('label[for="descricao"]');
    const botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'btn-voz';
    botao.setAttribute('aria-pressed', 'false');
    botao.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M12 15a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21h2v-2.08A7 7 0 0 0 19 12h-2z"/></svg><span>Falar</span>';
    botao.title = 'Descrever falando';

    const linha = document.createElement('div');
    linha.className = 'linha-rotulo-voz';
    if (rotulo) {
        rotulo.parentNode.insertBefore(linha, rotulo);
        linha.appendChild(rotulo);
        linha.appendChild(botao);
    } else {
        campo.parentNode.insertBefore(botao, campo);
    }

    const rec = new Reconhecimento();
    rec.lang = 'pt-BR';
    rec.continuous = true;
    rec.interimResults = true;

    let ouvindo = false;
    let textoBase = '';

    function atualizarBotao() {
        botao.classList.toggle('ouvindo', ouvindo);
        botao.setAttribute('aria-pressed', ouvindo ? 'true' : 'false');
        botao.querySelector('span').textContent = ouvindo ? 'Parar' : 'Falar';
        botao.title = ouvindo ? 'Parar de ouvir' : 'Descrever falando';
    }

    rec.onresult = (e) => {
        let final = '', parcial = '';
        for (let i = e.resultIndex; i < e.results.length; i++) {
            const t = e.results[i][0].transcript;
            if (e.results[i].isFinal) final += t; else parcial += t;
        }
        if (final) {
            textoBase = (textoBase + ' ' + final).replace(/\s+/g, ' ').trim();
            // Primeira letra maiúscula, pra ficar com cara de texto.
            textoBase = textoBase.charAt(0).toUpperCase() + textoBase.slice(1);
        }
        campo.value = (textoBase + (parcial ? ' ' + parcial : '')).slice(0, campo.maxLength > 0 ? campo.maxLength : 2000);
        campo.dispatchEvent(new Event('input', { bubbles: true })); // atualiza contador e mascote
    };
    rec.onend = () => {
        if (ouvindo) {
            ouvindo = false;
            atualizarBotao();
            document.dispatchEvent(new CustomEvent('voz:fim'));
        }
    };
    rec.onerror = (e) => {
        ouvindo = false;
        atualizarBotao();
        const msg = e.error === 'not-allowed' || e.error === 'service-not-allowed'
            ? 'O navegador bloqueou o microfone. Libere o acesso no cadeado da barra de endereço.'
            : (e.error === 'no-speech' ? 'Não ouvi nada. Tente de novo, falando perto do celular.' : 'O reconhecimento de voz falhou. Tente de novo ou digite.');
        if (window.Mascote) { window.Mascote.falar(msg, 6000); window.Mascote.reagir('preocupado'); }
        else if (typeof window.mostrarToast === 'function') window.mostrarToast(msg, 'erro');
    };

    botao.addEventListener('click', () => {
        if (ouvindo) {
            rec.stop();
            return;
        }
        textoBase = campo.value.trim();
        try {
            rec.start();
            ouvindo = true;
            atualizarBotao();
            document.dispatchEvent(new CustomEvent('voz:inicio'));
        } catch (e) { /* já estava ouvindo */ }
    });
})();
