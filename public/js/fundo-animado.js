// Injeta o fundo animado (blobs) em qualquer página que carregue este script.
// Fica como primeiro elemento do <body>, fixo, atrás de todo o conteúdo.
(function () {
    if (document.querySelector('.fundo-animado')) return;

    var fundo = document.createElement('div');
    fundo.className = 'fundo-animado';
    fundo.setAttribute('aria-hidden', 'true');

    ['blob-1', 'blob-2', 'blob-3'].forEach(function (classe) {
        var blob = document.createElement('div');
        blob.className = 'blob ' + classe;
        fundo.appendChild(blob);
    });

    document.body.insertBefore(fundo, document.body.firstChild);
})();
