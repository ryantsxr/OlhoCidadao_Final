document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('inputFoto');
    const preview = document.getElementById('fotoPreview');

    if (!input || !preview) return;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            preview.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const inputFoto = document.getElementById('input-foto');
    const boxFotos = document.getElementById('box-fotos');

    if (!inputFoto || !boxFotos) return;

    const textoOriginal = boxFotos.innerHTML;

    inputFoto.addEventListener('change', () => {
        const file = inputFoto.files && inputFoto.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            inputFoto.value = '';
            boxFotos.innerHTML = textoOriginal;
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            boxFotos.innerHTML = '<img src="' + event.target.result + '" alt="Pré-visualização da foto" class="preview-foto-denuncia">';
        };
        reader.readAsDataURL(file);
    });
});

// Mantida para compatibilidade com versões anteriores da view.
function previewImagem(event) {
    const input = event?.target;
    const preview = document.getElementById('fotoPreview');
    const file = input?.files?.[0];

    if (!preview || !file || !file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
