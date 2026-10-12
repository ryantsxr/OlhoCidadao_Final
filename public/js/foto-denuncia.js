/* Foto da denúncia (passo 2): arrastar e soltar, prévia, trocar e remover.
 * O arquivo continua indo pelo <input type="file" name="foto"> normal do formulário;
 * aqui só melhoramos a forma de escolher e conferimos tipo/tamanho antes de enviar.
 * A validação de verdade continua no servidor (Uploader.php). */
document.addEventListener('DOMContentLoaded', function () {
    const zona = document.getElementById('zona-foto');
    const input = document.getElementById('input-foto');
    if (!zona || !input) return;

    const vazia = document.getElementById('zona-foto-vazia');
    const previa = document.getElementById('zona-foto-previa');
    const img = document.getElementById('zona-foto-img');
    const nome = document.getElementById('zona-foto-nome');
    const tamanho = document.getElementById('zona-foto-tamanho');
    const remover = document.getElementById('zona-foto-remover');

    const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
    const MAXIMO = 5 * 1024 * 1024;
    let urlPrevia = null;

    function avisar(msg) {
        if (typeof window.mostrarToast === 'function') window.mostrarToast(msg, 'aviso');
        else alert(msg);
    }

    function formatarTamanho(bytes) {
        return bytes < 1024 * 1024 ? Math.round(bytes / 1024) + ' KB' : (bytes / 1024 / 1024).toFixed(1).replace('.', ',') + ' MB';
    }

    function limpar() {
        input.value = '';
        if (urlPrevia) URL.revokeObjectURL(urlPrevia);
        urlPrevia = null;
        previa.hidden = true;
        vazia.hidden = false;
        zona.classList.remove('com-foto');
    }

    function mostrar(arquivo) {
        if (urlPrevia) URL.revokeObjectURL(urlPrevia);
        urlPrevia = URL.createObjectURL(arquivo);
        img.src = urlPrevia;
        nome.textContent = arquivo.name;
        tamanho.textContent = formatarTamanho(arquivo.size);
        previa.hidden = false;
        vazia.hidden = true;
        zona.classList.add('com-foto');
    }

    function validar(arquivo) {
        if (!arquivo) return false;
        if (TIPOS.indexOf(arquivo.type) === -1) {
            avisar('Formato não aceito. Use uma foto JPG, PNG ou WEBP.');
            return false;
        }
        if (arquivo.size > MAXIMO) {
            avisar('A foto tem ' + formatarTamanho(arquivo.size) + '. O limite é 5 MB.');
            return false;
        }
        return true;
    }

    input.addEventListener('change', function () {
        const arquivo = input.files && input.files[0];
        if (!arquivo) return;
        if (validar(arquivo)) mostrar(arquivo); else limpar();
    });

    remover.addEventListener('click', function () {
        limpar();
        vazia.focus();
    });

    // Arrastar e soltar (computador).
    let profundidade = 0;
    zona.addEventListener('dragenter', function (e) {
        e.preventDefault();
        profundidade++;
        zona.classList.add('arrastando');
    });
    zona.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });
    zona.addEventListener('dragleave', function () {
        profundidade = Math.max(0, profundidade - 1);
        if (!profundidade) zona.classList.remove('arrastando');
    });
    zona.addEventListener('drop', function (e) {
        e.preventDefault();
        profundidade = 0;
        zona.classList.remove('arrastando');
        const arquivo = e.dataTransfer.files && e.dataTransfer.files[0];
        if (!validar(arquivo)) return;
        // Coloca o arquivo solto dentro do input, para ir junto com o formulário.
        try {
            const dt = new DataTransfer();
            dt.items.add(arquivo);
            input.files = dt.files;
        } catch (erro) {
            avisar('Seu navegador não aceitou arrastar a foto. Clique na área para escolher o arquivo.');
            return;
        }
        mostrar(arquivo);
    });

    // Soltar a foto fora da área não deve abrir a imagem no navegador.
    ['dragover', 'drop'].forEach(function (ev) {
        window.addEventListener(ev, function (e) {
            if (!zona.contains(e.target)) e.preventDefault();
        });
    });
});
