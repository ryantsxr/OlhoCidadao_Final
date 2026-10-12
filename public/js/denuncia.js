function selecionarCard(el, categoria) {
 document.querySelectorAll('.card').forEach(c=>c.classList.remove('selecionado'));
 el.classList.add('selecionado');
 document.getElementById('categoria').value=categoria;
 const info={
 iluminacao:['fa-lightbulb','Iluminação','Esses problemas podem ser postes queimados, falta de luz constante na rua ou fios caídos.'],
 sinalizacao:['fa-traffic-light','Sinalização de Trânsito','Relate placas quebradas ou apagadas, semáforos com defeito ou faixas de pedestre apagadas.'],
 lixo:['fa-trash','Lixo nas ruas','Denuncie acúmulo de lixo irregular, descarte de entulho ou terrenos baldios sujos.'],
 buracos:['fa-road','Buracos e vias','Relate buracos, danos no asfalto, calçadas perigosas ou ruas em más condições.'],
 esgoto:['fa-water','Esgoto e drenagem','Reporte esgoto a céu aberto, bueiros entupidos ou drenagem inadequada.'],
 agua:['fa-faucet-drip','Abastecimento de água','Relate falta de abastecimento, vazamentos na rua e problemas de água.'],
 arborizacao:['fa-tree','Árvores e poda','Relate árvores em risco de queda, galhos perigosos e pedidos de poda.'],
 outros:['fa-plus','Outros','Seu problema não se encaixa nas opções acima? Escolha esta categoria e descreva para nós.']
 }[categoria];
 document.getElementById('info-icone').className='fa-solid '+info[0];
 document.getElementById('info-titulo').innerText=info[1];
 document.getElementById('info-texto').innerText=info[2];
 document.getElementById('btn-continuar').classList.remove('oculto');
}

// Cartões de categoria: clique, Enter ou Espaço (sem onclick no HTML, compatível com a CSP).
document.querySelectorAll('.grid-cards .card[data-categoria]').forEach(function (card) {
 card.addEventListener('click', function () { selecionarCard(card, card.dataset.categoria); });
 card.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
 });
});
// Vindo das categorias do painel (?categoria=lixo), já deixa o cartão selecionado.
(function () {
 const cat = new URLSearchParams(location.search).get('categoria');
 if (!cat || !/^[a-z]+$/.test(cat)) return;
 const card = document.querySelector('.grid-cards .card[data-categoria="' + cat + '"]');
 if (card) card.click();
})();
