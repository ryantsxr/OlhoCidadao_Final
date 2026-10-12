<?php
if (PHP_SAPI !== 'cli' || !is_file(__DIR__.'/.sandbox_test_enabled')) exit(2);
$raiz = dirname(__DIR__);
// Os testes funcionais da versão JSON executam numa cópia isolada, nunca no MySQL.
define('DB_DRIVER', 'json');
require $raiz.'/app/config.php';
require $raiz.'/app/Core/CompatibilidadeTexto.php';
spl_autoload_register(function($classe) use($raiz) {
    foreach (['Core','Models'] as $d) {
        $p=$raiz.'/app/'.$d.'/'.$classe.'.php';
        if (is_file($p)) {require_once $p; return;}
    }
});
$quantos=0;
function confere($condicao, $descricao) {
    global $quantos;
    if (!$condicao) { fwrite(STDERR, "FALHOU: $descricao\n"); exit(1); }
    $quantos++;
    echo "OK: $descricao\n";
}
try {
    confere(!MySqlStore::ativo(),'teste isolado sempre em JSON');
    $u=new User;
    confere(count($u->todos())===1,'administrador preservado no pacote');
    confere(($u->findByEmail('admin@olhocidadao.com')['role']??'')==='admin','admin original existente');
    $a=$u->create(['nome'=>'Pessoa de Teste','email'=>'cidadao_teste@example.invalid','senha'=>'SenhaDeTeste#42']);
    confere((int)$a['id']>1,'cidadão cadastrado');
    confere($u->findByEmail('CIDADAO_TESTE@example.invalid')!==null,'email case insensitive');
    $bloqueou=false;
    try {$u->create(['nome'=>'Outro','email'=>'cidadao_teste@example.invalid','senha'=>'SenhaDeTeste#44']);}catch(DomainException $e){$bloqueou=true;}
    confere($bloqueou,'duplicidade de e-mail bloqueada');
    $outro=$u->create(['nome'=>'Outro Cidadão','email'=>'outro@example.invalid','senha'=>'SenhaForte#56']);
    confere((int)$outro['id']>(int)$a['id'],'IDs crescentes');
    $d=new Denuncia;
    $nova=$d->create((int)$a['id'],'iluminacao','Teste de lâmpada quebrada','','-10.89'===null?null:-10.89,-65.32,'Via de exemplo',true);
    confere(count($d->todas())===1,'nova denúncia persistida');
    confere(str_starts_with(Denuncia::protocolo($nova),'OC-'),'protocolo gerado');
    confere($d->apoiar((int)$nova['id'],(int)$outro['id']),'apoio por outro cidadão');
    confere(!$d->apoiar((int)$nova['id'],(int)$outro['id']),'apoio duplicado impedido');
    confere($d->atualizarStatus((int)$nova['id'],'Rejeitada','Relato não confirmado no local'),'rejeição justificada');
    confere(!$d->recorrerRejeicao((int)$nova['id'],(int)$outro['id'],str_repeat('Contestação válida ',3)),'recurso de terceiro bloqueado');
    confere($d->recorrerRejeicao((int)$nova['id'],(int)$a['id'],str_repeat('Peço nova análise ',3)),'recurso do autor permitido');
    confere($d->find((int)$nova['id'])['status']==='Em análise','recurso reabre análise');
    confere($d->atualizarStatus((int)$nova['id'],'Aguardando confirmação'),'encaminhamento a confirmação');
    confere(!$d->confirmarResolucao((int)$nova['id'],(int)$outro['id']),'terceiro não confirma');
    confere($d->confirmarResolucao((int)$nova['id'],(int)$a['id'],5),'autor confirma resolução');
    confere($d->find((int)$nova['id'])['status']==='Resolvido','denúncia resolvida');
    $n=new Notificacao;
    $n->criar((int)$a['id'],'Status de teste',(int)$nova['id']);
    confere($n->contarNaoLidas((int)$a['id'])===1,'notificação não lida');
    $n->marcarTodasComoLidas((int)$a['id']);
    confere($n->contarNaoLidas((int)$a['id'])===0,'marcação de leitura');
    (new LogAuditoria)->registrar(1,'Admin','Ação fictícia',(int)$nova['id']);
    confere(count((new LogAuditoria)->ultimos())===1,'log de auditoria');
    confere(count(Integridade::verificar($d->todas())['problemas'])===0,'selos de integridade');
    // Etapa 6: segurança, recuperação, integridade e entrada inválida.
    confere(PoliticaSenha::erro('12345678')!==null,'senha fraca recusada');
    confere(PoliticaSenha::erro('SenhaLonga#42')===null,'senha forte aceita');
    $cpfExemplo = '52998224725'; // número sintético usado só na cópia de teste
    $cifrado = PrivacidadeCpf::cifrar($cpfExemplo);
    confere($cifrado !== $cpfExemplo && PrivacidadeCpf::decifrar($cifrado)===$cpfExemplo,'CPF cifrado e recuperável');
    confere(PrivacidadeCpf::indice($cpfExemplo)===PrivacidadeCpf::indice($cpfExemplo),'índice do CPF estável');
    $u->update((int)$a['id'], ['cpf'=>$cpfExemplo]);
    confere($u->cpfEmUsoPorOutro($cpfExemplo,(int)$outro['id']),'CPF duplicado identificável');
    confere(!str_contains(file_get_contents($raiz.'/storage/users.json'), '"cpf": "'.$cpfExemplo.'"'),'CPF não gravado em texto puro');
    $reset = $u->gerarTokenReset((int)$a['id']);
    confere(is_string($reset) && strlen($reset)===64 && $u->findByTokenReset($reset)!==null,'reset com token temporário');
    $u->redefinirSenha((int)$a['id'],'OutraSenhaForte#64');
    confere($u->findByTokenReset($reset)===null,'token não pode ser reutilizado após reset');
    confere(password_verify('OutraSenhaForte#64', $u->find((int)$a['id'])['senha']),'senha nova tem hash verificável');
    $segredo=Totp::segredo();
    $recuperacao=SegurancaConta::ativar2fa(1,$segredo);
    confere(count($recuperacao)===8,'2FA com oito códigos de recuperação');
    $passo=intdiv(time(),30);
    $codigo=Totp::codigo($segredo,$passo);
    confere(SegurancaConta::verificar2fa(1,$codigo),'código de aplicativo autenticador aceito');
    confere(!SegurancaConta::verificar2fa(1,$codigo),'código TOTP não reutilizável');
    confere(SegurancaConta::verificar2fa(1,$recuperacao[0]),'código de recuperação aceito');
    confere(!SegurancaConta::verificar2fa(1,$recuperacao[0]),'código de recuperação de uso único');
    $novos=SegurancaConta::regenerarCodigos2fa(1,$recuperacao[1]);
    confere(count($novos)===8 && SegurancaConta::contarCodigosRecuperacao(1)===8,'novos 8 códigos de recuperação gerados');
    confere(!SegurancaConta::verificar2fa(1,$recuperacao[2]),'códigos antigos invalidados após regenerar');
    confere(SegurancaConta::regenerarCodigos2fa(1,'000000')===[],'regenerar exige fator válido');
    confere(SegurancaConta::verificar2fa(1,$novos[0]),'novo código de recuperação aceito');
    confere(SegurancaConta::desativar2fa(1),'desativação do 2FA');
    // Corromper JSON temporariamente: leitor deve recusar, sem sobrescrever nada.
    $arquivoLimites=$raiz.'/storage/rate_limits.json';
    $originalLimites=file_get_contents($arquivoLimites);
    try {
        file_put_contents($arquivoLimites,'{json inválido');
        $recusou=false;
        try { LeituraJson::array($arquivoLimites); } catch (RuntimeException $e) { $recusou=true; }
        confere($recusou,'JSON corrompido gera erro explícito');
    } finally { file_put_contents($arquivoLimites,$originalLimites); }
    confere(count(Integridade::verificar($d->todas())['problemas'])===0,'histórico continua íntegro após testes de conta');
    // v1.0.3: senhas, limites por conta, backup e restauração.
    $hash=PoliticaSenha::hash('SenhaForte#2026');
    confere(!defined('PASSWORD_ARGON2ID') || str_starts_with($hash,'$argon2id$'),'senha nova em Argon2id');
    confere(PoliticaSenha::verificar('SenhaForte#2026',$hash) && !PoliticaSenha::verificar('Errada#2026',$hash),'verificação de senha');
    confere(!PoliticaSenha::verificar('qualquer',null),'conta inexistente nunca autentica');
    confere(PoliticaSenha::precisaRehash(password_hash('x',PASSWORD_BCRYPT))===defined('PASSWORD_ARGON2ID'),'bcrypt antigo marcado para conversão');
    $lim=new RateLimiter('teste-dia',maxTentativas:2,bloqueioSegundos:60,identificador:'u'.$a['id'],janelaSegundos:86400);
    confere($lim->restantes()===2,'limite diário começa cheio');
    $lim->registrarTentativa(); $lim->registrarTentativa();
    confere($lim->bloqueado()>0 && $lim->restantes()===0,'limite diário bloqueia ao atingir o máximo');
    confere((new RateLimiter('teste-dia',maxTentativas:2,bloqueioSegundos:60,identificador:'u'.$outro['id'],janelaSegundos:86400))->restantes()===2,'limite é por conta');
    $pastaBackup=BackupDiario::raiz().'/teste-'.bin2hex(random_bytes(3));
    confere(BackupDiario::criar($pastaBackup,'teste') && !BackupDiario::verificar($pastaBackup),'backup criado e conferido por SHA-256');
    file_put_contents($pastaBackup.'/users.json','[]');
    confere(count(BackupDiario::verificar($pastaBackup))>0,'backup adulterado é detectado');
    confere(Ambiente::local() && !Ambiente::admin2faObrigatorio(),'2FA opcional no computador local (auto)');
    confere(PainelSeguranca::mascararEmail('cidadao@x.com')==='ci*****@x.com','e-mail mascarado no painel');
    // Remoção de metadados sem GD: JPEG mínimo com EXIF "GPS-SECRETO" e comentário.
    $exif="Exif\0\0MM\x00\x2A\x00\x00\x00\x08\x00\x00\x00\x00\x00\x00GPS-SECRETO";
    $jpeg="\xFF\xD8\xFF\xE1".pack('n',strlen($exif)+2).$exif."\xFF\xFE".pack('n',9)."segredo"."\xFF\xDA\x00\x02\x11\x22\xFF\xD9<?php echo 1;";
    $limpo=LimpaMetadados::limpar($jpeg,'image/jpeg');
    confere($limpo!==null && !str_contains($limpo,'GPS-SECRETO') && !str_contains($limpo,'segredo'),'sem GD: EXIF e comentário removidos');
    confere($limpo!==null && !str_contains($limpo,'<?php') && str_ends_with($limpo,"\xFF\xD9"),'sem GD: nada colado depois da imagem');
    echo "APROVADOS $quantos testes em cópia temporária.\n";
} catch(Throwable $e) {
    fwrite(STDERR,'ERRO: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()."\n");
    exit(1);
}
