/*
 * O que o Olhinho fala e como ele reage em cada passo da denúncia (v0.50).
 * Funciona mesmo se o 3D não carregar: aí só o balão de fala aparece.
 * Os textos das dicas vêm do PHP (app/Views/denuncia/_lado-esquerdo.php).
 */
(function () {
    'use strict';

    const cfg = window.OC_MASCOTE || {};
    const passo = cfg.passo || 1;
    const dicas = cfg.dicas || [];
    const M = () => window.Mascote || { falar() {}, reagir() {} };
    let ultimaAcao = Date.now();
    let indiceDica = 0;
    const jaFalou = {};

    // Fala só uma vez por evento (pra não ficar repetitivo).
    function umaVez(chave, texto, humor, ms) {
        if (jaFalou[chave]) return;
        jaFalou[chave] = true;
        falar(texto, humor, ms);
    }
    function falar(texto, humor, ms) {
        ultimaAcao = Date.now();
        M().falar(texto, ms || 5200);
        if (humor) M().reagir(humor, 3200);
    }

    // ------------------------------------------------------------ boas-vindas
    const nome = cfg.nomeUsuario ? ', ' + cfg.nomeUsuario : '';
    const saudacoes = {
        1: 'Oi' + nome + '! Que tipo de problema você encontrou? 👀',
        2: 'Agora me conta o que aconteceu. Foto e local ajudam muito!',
        3: 'Prontinho! Sua denúncia chegou na prefeitura. 🎉',
    };
    const inicio = () => {
        if (passo === 3) {
            falar(saudacoes[3], 'comemorar', 6000);
        } else {
            falar(saudacoes[passo] || saudacoes[1], 'acenar', 6000);
        }
    };
    if (window.Mascote && window.Mascote.pronto) setTimeout(inicio, 500);
    else document.addEventListener('mascote:pronto', () => setTimeout(inicio, 500), { once: true });
    // Se o 3D não carregar, ainda mostra a fala.
    setTimeout(() => { if (!window.Mascote || !window.Mascote.pronto) inicio(); }, 2500);

    // ------------------------------------------------------------ passo 1: categoria
    const nomesCategoria = { iluminacao: 'Iluminação', sinalizacao: 'Sinalização', lixo: 'Lixo', outros: 'Outros' };
    const comentariosCategoria = {
        iluminacao: 'Poste apagado é perigoso à noite. Boa escolha!',
        sinalizacao: 'Sinalização ruim causa acidente. Bora resolver!',
        lixo: 'Lixo acumulado atrai bicho. Vamos avisar a limpeza!',
        outros: 'Beleza! No próximo passo você explica direitinho.',
    };
    document.addEventListener('click', (e) => {
        const card = e.target.closest('.grid-cards .card, [data-categoria-cartao]');
        if (!card || passo !== 1) return;
        const cat = card.dataset.categoria || (document.getElementById('categoria') || {}).value;
        setTimeout(() => {
            const escolhida = (document.getElementById('categoria') || {}).value || cat;
            falar(comentariosCategoria[escolhida] || ('Categoria: ' + (nomesCategoria[escolhida] || escolhida)), 'feliz', 4200);
        }, 60);
    });

    // ------------------------------------------------------------ passo 2: formulário
    const desc = document.getElementById('descricao');
    if (desc) {
        let timerDigitando = null;
        desc.addEventListener('focus', () => {
            M().reagir('olharForm', 4000);
            umaVez('foco-desc', 'Capricha: diga o que é, onde fica e desde quando.', 'pensando', 4500);
        });
        desc.addEventListener('input', () => {
            ultimaAcao = Date.now();
            M().reagir('olharForm', 2500);
            clearTimeout(timerDigitando);
            timerDigitando = setTimeout(() => {
                const t = desc.value.toLowerCase();
                if (t.length >= 20 && /\b(rua|avenida|av\.|esquina|perto|frente|ao lado|bairro|pra[çc]a)\b/.test(t)) {
                    umaVez('referencia', 'Ótimo, ponto de referência ajuda a equipe a achar rapidinho!', 'feliz');
                } else if (t.length >= 40) {
                    umaVez('detalhe', 'Isso! Quanto mais detalhe, melhor.', 'feliz');
                }
            }, 900);
        });
    }

    const inputFoto = document.getElementById('input-foto');
    if (inputFoto) {
        inputFoto.addEventListener('change', () => {
            if (inputFoto.files && inputFoto.files.length) {
                falar('Foto anexada! Isso ajuda muito. 📸', 'comemorar', 4200);
            }
        });
    }
    // A foto também pode chegar arrastando (o foto-denuncia.js troca a prévia).
    const previa = document.getElementById('zona-foto-previa');
    if (previa && 'MutationObserver' in window) {
        new MutationObserver(() => {
            if (!previa.hidden) umaVez('foto-arrastada', 'Foto anexada! Isso ajuda muito. 📸', 'comemorar', 4200);
        }).observe(previa, { attributes: true, attributeFilter: ['hidden'] });
    }

    // Local marcado no mapa: os campos escondidos não avisam, então olhamos a cada segundo.
    const lat = document.getElementById('input-latitude');
    if (lat) {
        let ultimo = lat.value;
        setInterval(() => {
            if (lat.value && lat.value !== ultimo) {
                ultimo = lat.value;
                umaVez('local', 'Local marcado no mapa. Perfeito! 📍', 'feliz', 4000);
            }
        }, 1000);
    }

    // Aviso de denúncia parecida por perto.
    const parecida = document.getElementById('aviso-parecida');
    if (parecida && 'MutationObserver' in window) {
        new MutationObserver(() => {
            if (!parecida.hidden && parecida.textContent.trim() !== '') {
                falar('Opa! Já tem uma denúncia parecida aqui perto. Que tal apoiar a que já existe?', 'surpreso', 6500);
            }
        }).observe(parecida, { attributes: true, attributeFilter: ['hidden'], childList: true });
    }

    const anonima = document.querySelector('input[name="anonima"]');
    if (anonima) {
        anonima.addEventListener('change', () => {
            if (anonima.checked) falar('Combinado: seu nome não vai aparecer pros vizinhos. 🤫', 'feliz', 4000);
        });
    }

    const form = desc ? desc.closest('form') : null;
    if (form) {
        form.addEventListener('submit', (e) => {
            if (form.checkValidity && !form.checkValidity()) {
                falar('Falta escrever a descrição (pelo menos 5 letras).', 'preocupado', 4500);
                return;
            }
            // O denuncia-form.js abre a tela "Confira antes de enviar" e segura o primeiro envio.
            if (e.defaultPrevented) {
                falar('Confere tudo antes de mandar! 👀', 'pensando', 5000);
            } else {
                falar('Enviando… cruza os dedos! 🤞', 'comemorar', 6000);
            }
        });
        form.addEventListener('invalid', () => {
            umaVez('invalido', 'Falta preencher a descrição!', 'preocupado', 4500);
        }, true);
    }

    // Microfone (denúncia por voz).
    document.addEventListener('voz:inicio', () => falar('Tô ouvindo! Pode falar. 🎙️', 'pensando', 0));
    document.addEventListener('voz:fim', () => falar('Anotei! Confere se ficou certinho.', 'feliz', 4000));

    // ------------------------------------------------------------ dicas quando a pessoa para
    if (dicas.length) {
        setInterval(() => {
            if (document.hidden) return;
            if (Date.now() - ultimaAcao < 14000) return;
            falar(dicas[indiceDica % dicas.length], null, 6000);
            indiceDica++;
        }, 4000);
    }

    // Clique no mascote abre o assistente; enquanto isso ele diz algo.
    document.addEventListener('click', (e) => {
        if (e.target.closest('#mascote-3d')) {
            ultimaAcao = Date.now();
        }
    });
})();
