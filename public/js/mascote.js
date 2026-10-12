/*
 * Olhinho — o mascote 3D do Olho Cidadão (v0.50).
 *
 * Boneco de moletom verde com um olho só, feito com Three.js (arquivo local em
 * js/vendor, não depende de internet). Ele:
 *   - respira, balança e pisca sozinho;
 *   - segue o mouse (ou o dedo) com o olho e com a cabeça;
 *   - acena quando a página abre e quando alguém clica nele;
 *   - reage ao formulário (quem chama é o mascote-reacoes.js) através de
 *     window.Mascote.reagir('feliz' | 'comemorar' | 'preocupado' | 'pensando' |
 *     'olharForm' | 'acenar' | 'normal') e window.Mascote.falar('texto').
 *
 * Se o navegador não tiver WebGL, o logo antigo continua aparecendo no lugar.
 * Com "reduzir animações" ligado no sistema, ele fica parado (só muda de expressão).
 */
let THREE = null;

const caixa = document.getElementById('mascote-3d');
const balao = document.getElementById('mascote-balao');
const reduzir = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ------------------------------------------------------------------ fala
// O balão funciona mesmo sem o 3D (fica a API pronta antes de tudo).
let timerFala = null;
function falar(texto, ms = 5200) {
    if (!balao || !texto) return;
    balao.textContent = texto;
    balao.classList.remove('saindo');
    balao.classList.add('visivel');
    clearTimeout(timerFala);
    if (ms > 0) {
        timerFala = setTimeout(() => {
            balao.classList.add('saindo');
            setTimeout(() => balao.classList.remove('visivel', 'saindo'), 250);
        }, ms);
    }
}

const api = {
    falar,
    reagir() {},
    pronto: false,
};
window.Mascote = api;

function webglDisponivel() {
    try {
        const c = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl')));
    } catch (e) {
        return false;
    }
}

const economizarDados = Boolean(navigator.connection && navigator.connection.saveData);
if (caixa && !reduzir && !economizarDados && webglDisponivel()) {
    // Carrega os ~650 KB de Three.js somente quando o mascote 3D será exibido.
    import('./vendor/three.module.min.js').then(modulo => {
        THREE = modulo;
        iniciar();
    }).catch(e => {
        // O PNG existente permanece como fallback, sem bloquear a denúncia.
        console.warn('[Mascote] 3D indisponível:', e);
    });
}


function iniciar() {
    // ------------------------------------------------------------ cena
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'low-power' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, window.innerWidth <= 600 ? 1.5 : 2));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.05;
    renderer.domElement.setAttribute('aria-hidden', 'true');
    caixa.appendChild(renderer.domElement);

    const cena = new THREE.Scene();
    const relogio = new THREE.Clock();
    const lerp = (a, b, t) => a + (b - a) * t;
    const camera = new THREE.PerspectiveCamera(28, 1, 0.1, 50);
    camera.position.set(0, 1.85, 10.2);
    camera.lookAt(0, 1.55, 0);

    cena.add(new THREE.HemisphereLight(0xeaf9ff, 0x26382c, 1.7));
    const sol = new THREE.DirectionalLight(0xffffff, 2.2);
    sol.position.set(3, 6, 5);
    cena.add(sol);
    const contraLuz = new THREE.DirectionalLight(0x3cff8a, 1.6); // contorno verde, combina com a marca
    contraLuz.position.set(-4, 3, -4);
    cena.add(contraLuz);
    const preenchimento = new THREE.DirectionalLight(0x9ec5ff, 0.6);
    preenchimento.position.set(-5, 1, 4);
    cena.add(preenchimento);

    // ------------------------------------------------------------ materiais
    const mat = (cor, extra = {}) => new THREE.MeshStandardMaterial({ color: cor, roughness: 0.62, metalness: 0.02, ...extra });
    const M = {
        moletom: mat(0x16a34a, { roughness: 0.82 }),
        moletomEscuro: mat(0x0f7a37, { roughness: 0.85 }),
        capuzInterno: mat(0x0b5e2b, { roughness: 0.9 }),
        pele: mat(0xf2c094, { roughness: 0.55 }),
        calca: mat(0x26324a, { roughness: 0.8 }),
        tenis: mat(0xf4f6f8, { roughness: 0.45 }),
        sola: mat(0x22c55e, { roughness: 0.5 }),
        branco: new THREE.MeshPhysicalMaterial({ color: 0xffffff, roughness: 0.18, clearcoat: 1, clearcoatRoughness: 0.08 }),
        iris: new THREE.MeshPhysicalMaterial({ color: 0x22c55e, roughness: 0.25, clearcoat: 1, emissive: 0x0a3d1d, emissiveIntensity: 0.35 }),
        irisAnel: mat(0x0b6b30, { roughness: 0.4 }),
        pupila: new THREE.MeshPhysicalMaterial({ color: 0x0a0d12, roughness: 0.15, clearcoat: 1 }),
        brilho: new THREE.MeshBasicMaterial({ color: 0xffffff }),
        boca: mat(0x5a2a1e, { roughness: 0.6 }),
        sobrancelha: mat(0x3b2a20, { roughness: 0.7 }),
        bochecha: new THREE.MeshBasicMaterial({ color: 0xff8a8a, transparent: true, opacity: 0.35, depthWrite: false }),
        selo: mat(0xffffff, { roughness: 0.35 }),
    };

    const boneco = new THREE.Group();
    cena.add(boneco);

    // ------------------------------------------------------------ sombra no chão
    const tex = (() => {
        const c = document.createElement('canvas');
        c.width = c.height = 128;
        const g = c.getContext('2d');
        const grad = g.createRadialGradient(64, 64, 4, 64, 64, 62);
        grad.addColorStop(0, 'rgba(0,0,0,0.45)');
        grad.addColorStop(1, 'rgba(0,0,0,0)');
        g.fillStyle = grad;
        g.fillRect(0, 0, 128, 128);
        return new THREE.CanvasTexture(c);
    })();
    const sombra = new THREE.Mesh(new THREE.PlaneGeometry(2.4, 1.1), new THREE.MeshBasicMaterial({ map: tex, transparent: true, depthWrite: false }));
    sombra.rotation.x = -Math.PI / 2;
    sombra.position.y = 0.005;
    cena.add(sombra);

    // ------------------------------------------------------------ pernas e tênis
        [-1, 1].forEach((lado) => {
        const perna = new THREE.Group();
        perna.position.set(0.27 * lado, 0.95, 0);
        const coxa = new THREE.Mesh(new THREE.CapsuleGeometry(0.19, 0.5, 8, 16), M.calca);
        coxa.position.y = -0.4;
        perna.add(coxa);
        const tenis = new THREE.Mesh(new THREE.SphereGeometry(0.26, 24, 16), M.tenis);
        tenis.scale.set(1.05, 0.62, 1.45);
        tenis.position.set(0.02 * lado, -0.82, 0.1);
        perna.add(tenis);
        const sola = new THREE.Mesh(new THREE.CylinderGeometry(0.25, 0.26, 0.06, 24), M.sola);
        sola.scale.set(1.05, 1, 1.45);
        sola.position.set(0.02 * lado, -0.93, 0.1);
        perna.add(sola);
        boneco.add(perna);
    });

    // ------------------------------------------------------------ tronco (moletom)
    const tronco = new THREE.Group();
    tronco.position.y = 1.0;
    boneco.add(tronco);

    const corpo = new THREE.Mesh(new THREE.CapsuleGeometry(0.62, 0.5, 12, 32), M.moletom);
    corpo.scale.set(1, 1, 0.86);
    corpo.position.y = 0.62;
    tronco.add(corpo);

    // Quadril (calça) cobre a parte de baixo do moletom; a barra fica por cima.
    const quadril = new THREE.Mesh(new THREE.SphereGeometry(0.56, 32, 20), M.calca);
    quadril.scale.set(1, 0.62, 0.84);
    quadril.position.y = -0.02;
    tronco.add(quadril);
    const barra = new THREE.Mesh(new THREE.TorusGeometry(0.58, 0.08, 12, 40), M.moletomEscuro);
    barra.rotation.x = Math.PI / 2;
    barra.scale.set(1, 0.86, 1);
    barra.position.y = 0.16;
    tronco.add(barra);

    const bolso = new THREE.Mesh(new THREE.CapsuleGeometry(0.13, 0.42, 8, 16), M.moletomEscuro);
    bolso.rotation.z = Math.PI / 2;
    bolso.scale.set(1, 1, 0.45);
    bolso.position.set(0, 0.32, 0.5);
    tronco.add(bolso);

    // Selo no peito: um olhinho, igual ao do logo.
    const selo = new THREE.Group();
    selo.position.set(0.27, 0.86, 0.5);
    selo.rotation.y = 0.32;
    const seloBase = new THREE.Mesh(new THREE.CylinderGeometry(0.12, 0.12, 0.03, 32), M.selo);
    seloBase.rotation.x = Math.PI / 2;
    selo.add(seloBase);
    const seloIris = new THREE.Mesh(new THREE.CylinderGeometry(0.065, 0.065, 0.035, 24), M.iris);
    seloIris.rotation.x = Math.PI / 2;
    seloIris.position.z = 0.005;
    selo.add(seloIris);
    const seloPupila = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.03, 0.04, 16), M.pupila);
    seloPupila.rotation.x = Math.PI / 2;
    seloPupila.position.z = 0.008;
    selo.add(seloPupila);
    tronco.add(selo);

    // ------------------------------------------------------------ braços
    const bracos = {};
    [-1, 1].forEach((lado) => {
        const ombro = new THREE.Group();
        ombro.position.set(0.6 * lado, 1.0, 0);
        const braco = new THREE.Mesh(new THREE.CapsuleGeometry(0.15, 0.52, 8, 16), M.moletom);
        braco.position.y = -0.36;
        ombro.add(braco);
        const punho = new THREE.Mesh(new THREE.TorusGeometry(0.135, 0.045, 10, 24), M.moletomEscuro);
        punho.rotation.x = Math.PI / 2;
        punho.position.y = -0.68;
        ombro.add(punho);
        const mao = new THREE.Mesh(new THREE.SphereGeometry(0.16, 20, 16), M.pele);
        mao.position.y = -0.82;
        ombro.add(mao);
        ombro.rotation.z = 0.16 * lado;
        tronco.add(ombro);
        bracos[lado === 1 ? 'dir' : 'esq'] = ombro;
    });

    // ------------------------------------------------------------ cabeça + capuz
    const cabeca = new THREE.Group();
    cabeca.position.y = 1.62;
    tronco.add(cabeca);

    const rosto = new THREE.Mesh(new THREE.SphereGeometry(0.74, 48, 32), M.pele);
    rosto.position.y = 0.45;
    cabeca.add(rosto);

    // Capuz: metade de trás de uma esfera maior + a borda em volta do rosto.
    const capuz = new THREE.Mesh(
        new THREE.SphereGeometry(0.84, 48, 32, Math.PI, Math.PI, 0, Math.PI * 0.82),
        M.moletom
    );
    capuz.position.set(0, 0.47, -0.04);
    cabeca.add(capuz);
    const capuzDentro = new THREE.Mesh(
        new THREE.SphereGeometry(0.82, 48, 32, Math.PI, Math.PI, 0, Math.PI * 0.82),
        M.capuzInterno
    );
    capuzDentro.material = M.capuzInterno.clone();
    capuzDentro.material.side = THREE.BackSide;
    capuzDentro.position.copy(capuz.position);
    cabeca.add(capuzDentro);
    const borda = new THREE.Mesh(new THREE.TorusGeometry(0.76, 0.11, 16, 64), M.moletom);
    borda.position.set(0, 0.5, 0.06);
    borda.scale.set(1, 1.04, 1);
    cabeca.add(borda);

    // O olho (um só!).
    const olho = new THREE.Group();
    olho.position.set(0, 0.54, 0.56);
    cabeca.add(olho);
    const esclera = new THREE.Mesh(new THREE.SphereGeometry(0.4, 40, 32), M.branco);
    esclera.scale.set(1, 1, 0.58);
    olho.add(esclera);
    const irisGrupo = new THREE.Group();
    irisGrupo.position.z = 0.2;
    olho.add(irisGrupo);
    const iris = new THREE.Mesh(new THREE.SphereGeometry(0.235, 32, 24), M.iris);
    iris.scale.set(1, 1, 0.3);
    irisGrupo.add(iris);
    const anel = new THREE.Mesh(new THREE.TorusGeometry(0.23, 0.02, 10, 40), M.irisAnel);
    anel.position.z = 0.02;
    irisGrupo.add(anel);
    const pupila = new THREE.Mesh(new THREE.SphereGeometry(0.11, 24, 16), M.pupila);
    pupila.scale.set(1, 1, 0.35);
    pupila.position.z = 0.045;
    irisGrupo.add(pupila);
    const brilho1 = new THREE.Mesh(new THREE.SphereGeometry(0.042, 12, 8), M.brilho);
    brilho1.position.set(0.075, 0.085, 0.085);
    irisGrupo.add(brilho1);
    const brilho2 = new THREE.Mesh(new THREE.SphereGeometry(0.016, 8, 6), M.brilho);
    brilho2.position.set(-0.05, -0.05, 0.08);
    irisGrupo.add(brilho2);

    // Pálpebra: uma casca de pele que desce para piscar.
    const palpebra = new THREE.Mesh(
        new THREE.SphereGeometry(0.415, 40, 24, 0, Math.PI * 2, 0, Math.PI * 0.5),
        M.pele
    );
    palpebra.scale.set(1, 1, 0.62);
    palpebra.rotation.x = -Math.PI * 0.5; // começa aberta (virada para cima/trás)
    olho.add(palpebra);

    const sobrancelha = new THREE.Mesh(new THREE.CapsuleGeometry(0.045, 0.34, 6, 12), M.sobrancelha);
    sobrancelha.rotation.z = Math.PI / 2;
    sobrancelha.position.set(0, 1.03, 0.56);
    cabeca.add(sobrancelha);

    // Boca: um arco (sorriso) e um "o" para surpresa.
    const sorriso = new THREE.Mesh(new THREE.TorusGeometry(0.15, 0.032, 10, 32, Math.PI), M.boca);
    sorriso.position.set(0, 0.12, 0.7);
    sorriso.rotation.z = Math.PI;
    cabeca.add(sorriso);
    const bocaO = new THREE.Mesh(new THREE.TorusGeometry(0.06, 0.028, 10, 24), M.boca);
    bocaO.position.set(0, 0.08, 0.71);
    bocaO.visible = false;
    cabeca.add(bocaO);

    [-1, 1].forEach((lado) => {
        const b = new THREE.Mesh(new THREE.CircleGeometry(0.1, 20), M.bochecha);
        b.position.set(0.47 * lado, 0.22, 0.55);
        b.rotation.y = 0.55 * lado;
        cabeca.add(b);
    });

    // ------------------------------------------------------------ estado / expressões
    const estado = {
        humor: 'normal',      // normal | feliz | preocupado | pensando
        olharX: 0, olharY: 0, // para onde o olho quer olhar (-1..1)
        alvoX: 0, alvoY: 0,
        olharForm: 0,         // > agora: olha para o formulário (à direita)
        aceno: 0,             // tempo restante do aceno
        festa: 0,             // tempo restante da comemoração
        piscar: 0,
        proxPiscar: 2 + Math.random() * 3,
    };

    function aplicarHumor() {
        const h = estado.humor;
        bocaO.visible = h === 'surpreso';
        sorriso.visible = h !== 'surpreso';
        // Sorriso para cima (feliz/normal) ou de cabeça para baixo (preocupado).
        sorriso.rotation.z = h === 'preocupado' ? 0 : Math.PI;
        sorriso.position.y = h === 'preocupado' ? 0.02 : 0.12;
        sorriso.scale.setScalar(h === 'feliz' ? 1.25 : (h === 'pensando' ? 0.7 : 1));
        sobrancelha.rotation.z = Math.PI / 2 + (h === 'preocupado' ? 0.22 : (h === 'pensando' ? -0.18 : 0));
        sobrancelha.position.y = h === 'feliz' ? 1.07 : (h === 'surpreso' ? 1.1 : 1.03);
    }

    api.reagir = function (tipo, duracao = 3500) {
        switch (tipo) {
            case 'acenar':
                estado.aceno = 1.8;
                estado.humor = 'feliz';
                break;
            case 'comemorar':
                estado.festa = 1.6;
                estado.humor = 'feliz';
                break;
            case 'feliz':
            case 'preocupado':
            case 'pensando':
            case 'surpreso':
                estado.humor = tipo;
                break;
            case 'olharForm':
                estado.olharForm = performance.now() + duracao;
                break;
            default:
                estado.humor = 'normal';
        }
        aplicarHumor();
        if (tipo !== 'normal' && tipo !== 'olharForm') {
            clearTimeout(api._volta);
            api._volta = setTimeout(() => { estado.humor = 'normal'; aplicarHumor(); }, duracao);
        }
        if (reduzir) desenhar(0); // sem animação contínua, redesenha na hora
    };

    // ------------------------------------------------------------ mouse / dedo
    function apontar(x, y) {
        const r = caixa.getBoundingClientRect();
        const cx = r.left + r.width / 2;
        const cy = r.top + r.height * 0.32; // altura aproximada do olho
        estado.alvoX = Math.max(-1, Math.min(1, (x - cx) / (window.innerWidth * 0.45)));
        estado.alvoY = Math.max(-1, Math.min(1, (y - cy) / (window.innerHeight * 0.45)));
    }
    window.addEventListener('pointermove', (e) => apontar(e.clientX, e.clientY), { passive: true });
    window.addEventListener('touchstart', (e) => { if (e.touches[0]) apontar(e.touches[0].clientX, e.touches[0].clientY); }, { passive: true });

    // ------------------------------------------------------------ tamanho
    function ajustar() {
        const w = caixa.clientWidth || 200;
        const h = caixa.clientHeight || 240;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        if (reduzir) desenhar(0);
    }
    if ('ResizeObserver' in window) new ResizeObserver(ajustar).observe(caixa);
    window.addEventListener('resize', ajustar);
    ajustar();

    // ------------------------------------------------------------ animação
    function desenhar(dt) {
        const t = relogio.elapsedTime;
        const mov = reduzir ? 0 : 1;

        // Para onde olhar: mouse, ou o formulário (à direita) enquanto a pessoa digita.
        const olhandoForm = performance.now() < estado.olharForm;
        const ax = olhandoForm ? 0.85 : estado.alvoX;
        const ay = olhandoForm ? 0.15 : estado.alvoY;
        const k = Math.min(1, dt * 6) || 1;
        estado.olharX = lerp(estado.olharX, ax, k);
        estado.olharY = lerp(estado.olharY, ay, k);

        cabeca.rotation.y = estado.olharX * 0.32;
        cabeca.rotation.x = estado.olharY * 0.22 + (estado.humor === 'pensando' ? -0.1 : 0);
        cabeca.rotation.z = (estado.humor === 'pensando' ? 0.12 : 0) + (estado.humor === 'preocupado' ? -0.06 : 0) + Math.sin(t * 0.9) * 0.025 * mov;
        irisGrupo.position.x = estado.olharX * 0.12;
        irisGrupo.position.y = -estado.olharY * 0.09;

        // Respiração e balanço.
        tronco.position.y = 1.0 + Math.sin(t * 2.1) * 0.025 * mov;
        corpo.scale.y = 1 + Math.sin(t * 2.1) * 0.015 * mov;
        boneco.rotation.y = Math.sin(t * 0.6) * 0.08 * mov + estado.olharX * 0.12;

        // Piscar (a pálpebra gira para baixo e volta).
        estado.proxPiscar -= dt;
        if (estado.proxPiscar <= 0 && estado.piscar <= 0) {
            estado.piscar = 0.18;
            estado.proxPiscar = 2.2 + Math.random() * 3.5;
        }
        let fechado = 0;
        if (estado.piscar > 0) {
            estado.piscar -= dt;
            fechado = Math.sin(Math.max(0, estado.piscar) / 0.18 * Math.PI);
        }
        if (estado.humor === 'feliz') fechado = Math.max(fechado, 0.28); // olho "sorrindo"
        palpebra.rotation.x = -Math.PI * 0.5 + fechado * Math.PI * 0.5;

        // Braços: parado, acenando ou comemorando.
        const bd = bracos.dir, be = bracos.esq;
        let alvoDir = 0.16, alvoEsq = -0.16, balancoDir = 0;
        if (estado.aceno > 0) {
            estado.aceno -= dt;
            alvoDir = 2.55;
            balancoDir = Math.sin(t * 14) * 0.35;
        }
        if (estado.festa > 0) {
            estado.festa -= dt;
            alvoDir = 2.7 + Math.sin(t * 16) * 0.15;
            alvoEsq = -2.7 - Math.sin(t * 16) * 0.15;
            boneco.position.y = Math.abs(Math.sin(t * 9)) * 0.28;
        } else {
            boneco.position.y = lerp(boneco.position.y, 0, k);
        }
        if (estado.humor === 'pensando' && estado.aceno <= 0 && estado.festa <= 0) {
            alvoDir = 2.0; // mão perto do queixo
        }
        if (mov === 0) { // sem animação: só a pose final
            bd.rotation.z = alvoDir; be.rotation.z = alvoEsq;
        } else {
            bd.rotation.z = lerp(bd.rotation.z, alvoDir + balancoDir, Math.min(1, dt * 10));
            be.rotation.z = lerp(be.rotation.z, alvoEsq + Math.sin(t * 2.1) * 0.03, Math.min(1, dt * 10));
        }

        sombra.scale.setScalar(1 - boneco.position.y * 0.6);
        renderer.render(cena, camera);
    }

    let visivel = true;
    let executando = false;
    function laco() {
        if (!executando) return;
        desenhar(Math.min(relogio.getDelta(), 0.05));
        requestAnimationFrame(laco);
    }
    function atualizarLaco() {
        if (reduzir || !visivel || document.hidden) {
            executando = false;
            return;
        }
        if (!executando) {
            executando = true;
            relogio.getDelta(); // ignora o tempo parado antes de retomar a animação
            requestAnimationFrame(laco);
        }
    }
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(entradas => {
            visivel = entradas[0].isIntersecting;
            atualizarLaco();
        }).observe(caixa);
    }
    document.addEventListener('visibilitychange', atualizarLaco);

    caixa.classList.add('mascote-pronto'); // esconde o logo de reserva
    aplicarHumor();
    if (reduzir) desenhar(0);
    else {
        atualizarLaco();
        setTimeout(() => api.reagir('acenar'), 450);
    }

    // Clique/toque no boneco: acena (o assistente abre pelo mascote-reacoes.js).
    caixa.addEventListener('click', () => api.reagir('acenar'));

    api.pronto = true;
    document.dispatchEvent(new CustomEvent('mascote:pronto'));
}
