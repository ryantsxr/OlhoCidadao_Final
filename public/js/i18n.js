/* =========================================================================
 * Tradução completa da interface (inglês e espanhol) — Olho Cidadão
 *
 * Antes só os elementos com data-i18n eram traduzidos, então a maior parte
 * das telas continuava em português. Este arquivo percorre a página inteira
 * (textos, placeholders, títulos, aria-label, alt e botões) e troca cada texto
 * do sistema pela versão no idioma escolhido, usando o dicionário em
 * i18n-dicionario.js.
 *
 *  - Números, datas e protocolos dentro da frase são preservados: o dicionário
 *    guarda modelos como "Prazo de {0} dias" e os números são reaplicados.
 *  - Frases com nomes ou categorias no meio ("Por Ana Souza", "Encaminhada
 *    para …") são tratadas pelas REGRAS abaixo.
 *  - O que o usuário digitou (descrições, nomes, endereços) não está no
 *    dicionário e fica como foi escrito.
 *  - Textos que aparecem depois (avisos, modais, mapa, mascote) são traduzidos
 *    na hora por um MutationObserver; os gráficos do Chart.js também.
 *  - Voltar para português restaura os textos originais.
 *  - Para não traduzir um trecho, marque o elemento com data-no-i18n.
 * ========================================================================= */
(function () {
    'use strict';

    const DIC = { en: Object.create(null), es: Object.create(null) };
    (window.OC_I18N_DICIONARIO || []).forEach(function (l) {
        DIC.en[l[0]] = l[1];
        DIC.es[l[0]] = l[2];
    });

    let idioma = 'pt-br';
    const memTexto = new WeakMap();   // nó de texto → { orig, trad }
    const memAttr = new WeakMap();    // elemento → { atributo: { orig, trad } }
    const ATRIBUTOS = ['placeholder', 'title', 'aria-label', 'alt'];
    const PULAR = 'script,style,noscript,textarea,code,pre,[contenteditable],[data-no-i18n]';
    const PULAR_ATRIBUTOS = '[contenteditable],[data-no-i18n]';   // placeholder de <textarea> é traduzido
    const LETRA = /[A-Za-zÀ-ÿ]/;
    const NUMERO = /(^|[^A-Za-z\d])(\d+(?:[\/:.,]\d+)*)(?![A-Za-z\d])/g;

    function normalizar(sigla) {
        sigla = String(sigla || '').toLowerCase();
        if (sigla.indexOf('en') === 0) return 'en';
        if (sigla.indexOf('es') === 0) return 'es';
        return 'pt-br';
    }

    function idiomaSalvo() {
        try {
            const c = window.CONFIG_SERVIDOR || JSON.parse(localStorage.getItem('configOlhoCidadao') || 'null');
            return normalizar(c && c.idioma);
        } catch (e) {
            return 'pt-br';
        }
    }

    // ------------------------------------------------------------ regras
    // Cada regra recebe o resultado do regex e "t" (traduz um pedaço, ou o
    // devolve igual se não souber). Retorna { en, es } ou só a função do idioma.
    function t(s) { const r = traduzirNucleo(s, 0); return r == null ? s : r; }
    // Traduz um trecho com várias frases, uma a uma (mantém o espaço inicial).
    function frases(s) {
        s = (s || '').trim();
        if (!s) return '';
        const partes = s.match(/[^.!?]+[.!?]+(?:\s*[^\sA-Za-zÀ-ÿ\d]+(?=\s|$))?/g) || [s];
        return ' ' + partes.map(function (p) { return t(p.trim()); }).join(' ');
    }

    const REGRAS = [
        [/^Encaminhada para (.+?)(?:, prazo previsto de (\d+) dias \(até ([\d\/]+)\))?(\.?)$/, function (m) {
            const prazoEn = m[2] ? ', expected within ' + m[2] + ' days (by ' + m[3] + ')' : '';
            const prazoEs = m[2] ? ', plazo previsto de ' + m[2] + ' días (hasta ' + m[3] + ')' : '';
            return { en: 'Forwarded to ' + t(m[1]) + prazoEn + m[4], es: 'Derivada a ' + t(m[1]) + prazoEs + m[4] };
        }],
        [/^Denúncia (OC-\d+-\d+) excluída\.$/, function (m) {
            return { en: 'Report ' + m[1] + ' deleted.', es: 'Denuncia ' + m[1] + ' eliminada.' };
        }],
        [/^(.+?) agora está em "(.+)"\. O cidadão foi avisado\.$/, function (m) {
            const p = m[1] === 'Denúncia' ? null : m[1];
            return {
                en: (p ? 'Report ' + p : 'The report') + ' is now "' + t(m[2]) + '". The citizen has been notified.',
                es: (p ? 'La denuncia ' + p : 'La denuncia') + ' ahora está en "' + t(m[2]) + '". El ciudadano fue avisado.'
            };
        }],
        [/^(“?)Complemento do cidadão: ([\s\S]*?)(”?)$/, function (m) {
            return { en: m[1] + 'Citizen addition: ' + m[2] + m[3], es: m[1] + 'Complemento del ciudadano: ' + m[2] + m[3] };
        }],
        [/^(“?)Recurso do cidadão contra a rejeição: ([\s\S]*?)(”?)$/, function (m) {
            return { en: m[1] + 'Citizen appeal against the rejection: ' + m[2] + m[3], es: m[1] + 'Recurso del ciudadano contra el rechazo: ' + m[2] + m[3] };
        }],
        [/^Você registrou uma denúncia de (.+)$/, function (m) {
            return { en: 'You filed a report about ' + t(m[1]), es: 'Registraste una denuncia de ' + t(m[1]) };
        }],
        [/^(Oi|Olá), ([^!,?]+)([!,.])\s*([\s\S]*)$/, function (m) {
            const resto = m[4] ? ' ' + t(m[4]) : '';
            return { en: 'Hi, ' + m[2] + m[3] + resto, es: 'Hola, ' + m[2] + m[3] + resto };
        }],
        [/^(Por|por) ([A-ZÀ-Ý][^\s\d·]*(?: (?:d[aeo]s? )?[A-ZÀ-Ý][^\s\d·]*)*)$/, function (m) {
            return { en: (m[1] === 'Por' ? 'By ' : 'by ') + m[2], es: m[1] + ' ' + m[2] };
        }],
        [/^([\d\/]+(?: [\d:]+)?) por (.+)$/, function (m) {
            return { en: m[1] + ' by ' + m[2], es: m[1] + ' por ' + m[2] };
        }],
        [/^Relatório emitido automaticamente pelo sistema Olho Cidadão em (\S+) às (\S+), por (.+)\.$/, function (m) {
            return {
                en: 'Report automatically generated by the Olho Cidadão system on ' + m[1] + ' at ' + m[2] + ', by ' + m[3] + '.',
                es: 'Informe emitido automáticamente por el sistema Olho Cidadão el ' + m[1] + ' a las ' + m[2] + ', por ' + m[3] + '.'
            };
        }],
        [/^([A-Za-zçáé]+(?:-feira)?), (\d+) de ([a-zç]+)(?: de (\d+))?$/i, function (m) {
            const d = t(m[1]), mes = t(m[3]);
            return {
                en: d + ', ' + mes + ' ' + m[2] + (m[4] ? ', ' + m[4] : ''),
                es: d + ', ' + m[2] + ' de ' + mes + (m[4] ? ' de ' + m[4] : '')
            };
        }],
        [/^(\d+) de ([a-zç]+)(?: de (\d+))?$/i, function (m) {
            const mes = t(m[2]);
            return { en: mes + ' ' + m[1] + (m[3] ? ', ' + m[3] : ''), es: m[1] + ' de ' + mes + (m[3] ? ' de ' + m[3] : '') };
        }],
        [/^(\d+) dias? e (\d+)h$/, function (m) {
            return { en: m[1] + ' days and ' + m[2] + 'h', es: m[1] + ' días y ' + m[2] + ' h' };
        }],
        [/^(\d+)h$/, function (m) { return { en: m[1] + 'h', es: m[1] + ' h' }; }],
        [/^Últimos (\d+) meses: (.*)$/, function (m) {
            return { en: 'Last ' + m[1] + ' months: ' + m[2], es: 'Últimos ' + m[1] + ' meses: ' + m[2] };
        }],
        [/^Você atingiu o limite de (\d+) (\S+) por dia\. Tente novamente amanhã\.$/, function (m) {
            return {
                en: 'You reached the limit of ' + m[1] + ' ' + t(m[2]) + ' per day. Try again tomorrow.',
                es: 'Alcanzaste el límite de ' + m[1] + ' ' + t(m[2]) + ' por día. Inténtalo de nuevo mañana.'
            };
        }],
        [/^#(\d+) — (.+) \(resolvida em ([\d\/]+)\)$/, function (m) {
            return { en: '#' + m[1] + ' — ' + m[2] + ' (resolved on ' + m[3] + ')', es: '#' + m[1] + ' — ' + m[2] + ' (resuelta el ' + m[3] + ')' };
        }],
        // Olhinho (assistente): respostas montadas com dados da denúncia.
        [/^A denúncia (OC-\d+-\d+) \((.+?)\) está "(.+?)"(?: desde ([\d\/]+))?\.\s*([\s\S]*)$/, function (m) {
            const resto = frases(m[5]);
            return {
                en: 'Report ' + m[1] + ' (' + t(m[2]) + ') is "' + t(m[3]) + '"' + (m[4] ? ' since ' + m[4] : '') + '.' + resto,
                es: 'La denuncia ' + m[1] + ' (' + t(m[2]) + ') está "' + t(m[3]) + '"' + (m[4] ? ' desde ' + m[4] : '') + '.' + resto
            };
        }],
        [/^Está com: (.+?)(?:, prazo até ([\d\/]+))?\.$/, function (m) {
            return {
                en: 'Handled by: ' + t(m[1]) + (m[2] ? ', due by ' + m[2] : '') + '.',
                es: 'Está con: ' + t(m[1]) + (m[2] ? ', plazo hasta ' + m[2] : '') + '.'
            };
        }],
        [/^Você tem (\d+) denúncias?: (.+?)\.\s*([\s\S]*)$/, function (m) {
            const partes = m[2].split(', ').map(function (p) {
                const x = p.match(/^(\d+) (.+)$/);
                return x ? x[1] + ' ' + t(x[2]) : p;
            }).join(', ');
            const resto = frases(m[3]);
            return {
                en: 'You have ' + m[1] + ' report' + (m[1] === '1' ? '' : 's') + ': ' + partes + '.' + resto,
                es: 'Tienes ' + m[1] + ' denuncia' + (m[1] === '1' ? '' : 's') + ': ' + partes + '.' + resto
            };
        }],
        [/^Parece um problema de "(.+?)"\.\s*([\s\S]*)$/, function (m) {
            const resto = frases(m[2]);
            return { en: 'Looks like a "' + t(m[1]) + '" problem.' + resto, es: 'Parece un problema de "' + t(m[1]) + '".' + resto };
        }],
        [/^Categoria: (.+)$/, function (m) {
            return { en: 'Category: ' + t(m[1]), es: 'Categoría: ' + t(m[1]) };
        }],
        [/^([a-zç]{3})\/(\d{2,4})$/i, function (m) {
            const mes = t(m[1]);
            return mes === m[1] ? null : { en: mes + '/' + m[2], es: mes + '/' + m[2] };
        }],
    ];

    // ------------------------------------------------------------ tradução
    function porModelo(s, d) {
        const nums = [];
        const modelo = s.replace(NUMERO, function (_, antes, n) {
            nums.push(n);
            return antes + '{' + (nums.length - 1) + '}';
        });
        if (!nums.length || d[modelo] === undefined) return null;
        return d[modelo].replace(/\{(\d+)\}/g, function (_, i) { return nums[+i] !== undefined ? nums[+i] : ''; });
    }

    function traduzirNucleo(s, prof) {
        if (idioma === 'pt-br' || !s || !LETRA.test(s) || prof > 3) return null;
        const d = DIC[idioma];
        if (d[s] !== undefined) return d[s];
        let r = porModelo(s, d);
        if (r != null) return r;

        for (let i = 0; i < REGRAS.length; i++) {
            const m = s.match(REGRAS[i][0]);
            if (!m) continue;
            const res = REGRAS[i][1](m);
            if (res && res[idioma] != null) return res[idioma];
        }

        // Partes separadas por " · " (ex.: "Por Ana Souza · 12/10/2026 14:06").
        if (s.indexOf(' · ') > -1) {
            let mudou = false;
            const partes = s.split(' · ').map(function (p) {
                const x = traduzirNucleo(p, prof + 1);
                if (x != null) { mudou = true; return x; }
                return p;
            });
            if (mudou) return partes.join(' · ');
        }

        // Mesma frase com a primeira letra maiúscula/minúscula ("opcional" × "Opcional").
        const primeira = s.charAt(0);
        const trocada = (primeira === primeira.toLowerCase() ? primeira.toUpperCase() : primeira.toLowerCase()) + s.slice(1);
        if (trocada !== s && d[trocada] !== undefined) {
            const x = d[trocada];
            return (primeira === primeira.toLowerCase() ? x.charAt(0).toLowerCase() : x.charAt(0).toUpperCase()) + x.slice(1);
        }

        // Pontuação nas pontas: "Nome:", "Senha *", "(opcional)", "Carregando…".
        const p = s.match(/^([(“"«]?)([\s\S]*?)(\s*[:*…)”"»!?.]+)?$/);
        if (p && (p[1] || p[3])) {
            const x = traduzirNucleo(p[2].trim(), prof + 1);
            if (x != null) return p[1] + x + (p[3] || '');
        }
        return null;
    }

    /** Traduz uma frase para o idioma atual; devolve null se não houver tradução. */
    function traduzirTexto(texto) {
        if (idioma === 'pt-br' || texto == null) return null;
        const m = String(texto).match(/^(\s*)([\s\S]*?)(\s*)$/);
        const nucleo = m[2].replace(/\s+/g, ' ');
        const r = traduzirNucleo(nucleo, 0);
        return r == null ? null : m[1] + r + m[3];
    }

    // ------------------------------------------------------------ DOM
    function devePular(el) {
        return !el || (el.closest && el.closest(PULAR));
    }

    function traduzirNoTexto(no) {
        const atual = no.nodeValue;
        if (!atual || !LETRA.test(atual)) return;
        const mem = memTexto.get(no);
        const origem = mem && (atual === mem.trad || atual === mem.orig) ? mem.orig : atual;
        const novo = idioma === 'pt-br' ? origem : (traduzirTexto(origem) ?? origem);
        memTexto.set(no, { orig: origem, trad: novo });
        if (novo !== atual) no.nodeValue = novo;
    }

    function traduzirAtributo(el, attr) {
        const atual = el.getAttribute(attr);
        if (!atual || !LETRA.test(atual)) return;
        let mem = memAttr.get(el);
        if (!mem) { mem = {}; memAttr.set(el, mem); }
        const m = mem[attr];
        const origem = m && (atual === m.trad || atual === m.orig) ? m.orig : atual;
        const novo = idioma === 'pt-br' ? origem : (traduzirTexto(origem) ?? origem);
        mem[attr] = { orig: origem, trad: novo };
        if (novo !== atual) el.setAttribute(attr, novo);
    }

    function traduzirElemento(el) {
        if (!el || (el.closest && el.closest(PULAR_ATRIBUTOS))) return;
        ATRIBUTOS.forEach(function (a) { if (el.hasAttribute(a)) traduzirAtributo(el, a); });
        if (el.tagName === 'INPUT' && (el.type === 'submit' || el.type === 'button' || el.type === 'reset')) {
            traduzirAtributo(el, 'value');
        }
    }

    function traduzirArvore(raiz) {
        if (!raiz) return;
        if (raiz.nodeType === 3) {
            if (!devePular(raiz.parentElement)) traduzirNoTexto(raiz);
            return;
        }
        if (raiz.nodeType !== 1 && raiz.nodeType !== 9 && raiz.nodeType !== 11) return;
        if (raiz.nodeType === 1) {
            traduzirElemento(raiz);
            if (devePular(raiz)) return;
        }
        const andador = document.createTreeWalker(raiz, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, {
            acceptNode: function (n) {
                if (n.nodeType === 1 && n.matches(PULAR)) {
                    traduzirElemento(n);
                    return NodeFilter.FILTER_REJECT;
                }
                return NodeFilter.FILTER_ACCEPT;
            }
        });
        let n;
        while ((n = andador.nextNode())) {
            if (n.nodeType === 3) traduzirNoTexto(n);
            else traduzirElemento(n);
        }
    }

    let tituloOriginal = null;
    function traduzirTitulo() {
        if (tituloOriginal === null) tituloOriginal = document.title;
        document.title = idioma === 'pt-br' ? tituloOriginal : (traduzirTexto(tituloOriginal) ?? tituloOriginal);
    }

    // ------------------------------------------------------------ gráficos (Chart.js)
    function registrarGraficos() {
        if (!window.Chart || window.Chart.__ocI18n) return;
        window.Chart.__ocI18n = true;
        const traduzirRotulo = function (r) {
            if (typeof r === 'string') return traduzirTexto(r) ?? r;
            if (Array.isArray(r)) return r.map(traduzirRotulo);
            return r;
        };
        window.Chart.register({
            id: 'ocI18n',
            beforeUpdate: function (chart) {
                const dados = chart.data || {};
                if (!chart.$ocOriginal) {
                    chart.$ocOriginal = {
                        labels: (dados.labels || []).slice(),
                        series: (dados.datasets || []).map(function (s) { return s.label; })
                    };
                }
                const o = chart.$ocOriginal;
                dados.labels = idioma === 'pt-br' ? o.labels.slice() : o.labels.map(traduzirRotulo);
                (dados.datasets || []).forEach(function (s, i) {
                    if (o.series[i] != null) s.label = idioma === 'pt-br' ? o.series[i] : traduzirRotulo(o.series[i]);
                });
            }
        });
    }

    // ------------------------------------------------------------ observador
    const observador = new MutationObserver(function (lista) {
        if (idioma === 'pt-br') return;
        lista.forEach(function (m) {
            if (m.type === 'childList') {
                m.addedNodes.forEach(traduzirArvore);
            } else if (m.type === 'characterData') {
                if (!devePular(m.target.parentElement)) traduzirNoTexto(m.target);
            } else if (m.type === 'attributes') {
                if (!m.target.closest(PULAR_ATRIBUTOS)) {
                    if (m.attributeName === 'value') {
                        if (m.target.tagName === 'INPUT' && /^(submit|button|reset)$/.test(m.target.type)) traduzirAtributo(m.target, 'value');
                    } else {
                        traduzirAtributo(m.target, m.attributeName);
                    }
                }
            }
        });
    });
    let observando = false;
    function observar() {
        if (observando || !document.body) return;
        observando = true;
        observador.observe(document.body, {
            childList: true, subtree: true, characterData: true,
            attributes: true, attributeFilter: ATRIBUTOS.concat(['value'])
        });
        const titulo = document.querySelector('title');
        if (titulo) new MutationObserver(function () {
            if (idioma !== 'pt-br' && document.title !== (traduzirTexto(tituloOriginal) ?? tituloOriginal)) {
                tituloOriginal = document.title; traduzirTitulo();
            }
        }).observe(titulo, { childList: true });
    }

    // Caixas nativas do navegador (alert/confirm/prompt) também no idioma.
    ['alert', 'confirm', 'prompt'].forEach(function (nome) {
        const original = window[nome];
        if (typeof original !== 'function') return;
        window[nome] = function (msg) {
            const args = Array.prototype.slice.call(arguments);
            if (typeof msg === 'string') args[0] = traduzirTexto(msg) ?? msg;
            return original.apply(window, args);
        };
    });

    /** Aplica o idioma na página inteira (chamado também por configuracoes.js). */
    function aplicar(sigla) {
        const novo = normalizar(sigla);
        const anterior = idioma;
        idioma = novo;
        document.documentElement.setAttribute('lang', novo === 'pt-br' ? 'pt-BR' : novo);
        if (novo === 'pt-br' && anterior === 'pt-br') return;
        traduzirArvore(document.body);
        traduzirTitulo();
        if (window.Chart && window.Chart.instances) {
            Object.values(window.Chart.instances).forEach(function (c) { try { c.update(); } catch (e) {} });
        }
        if (novo !== 'pt-br') observar();
    }

    window.ocI18n = {
        aplicar: aplicar,
        traduzir: function (s) { return traduzirTexto(s) ?? s; },
        idioma: function () { return idioma; }
    };

    function iniciar() {
        registrarGraficos();
        aplicar(idiomaSalvo());
    }
    registrarGraficos();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
    // Bibliotecas carregadas depois (ex.: Chart.js no fim da página).
    window.addEventListener('load', registrarGraficos);
})();
