<?php
/**
 * Alternativa SEM SSH no servidor: exporta os JSON locais para SQL compatível
 * com sql/schema.sql, importável pelo phpMyAdmin.
 * Executar somente via CLI, em cópia PRIVADA do projeto:
 *   php tools/gerar_importacao_sql_v1.php --arquivo=/pasta/privada/importacao.sql
 * O arquivo gerado CONTÉM hashes de senha, histórico e dados pessoais; não
 * publicar e apagar com segurança depois da importação.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../app/Core/PrivacidadeCpf.php';
$destino = null;
foreach ($argv as $arg) if (str_starts_with($arg, '--arquivo=')) $destino = substr($arg, 10);
if (!$destino || str_ends_with($destino, '/')) { fwrite(STDERR,"Informe --arquivo=/caminho/privado/importacao.sql\n"); exit(2); }
$realRaiz = realpath(__DIR__ . '/..');
$dirDestino = realpath(dirname($destino));
if (!$dirDestino || !$realRaiz) { fwrite(STDERR,"Pasta de destino inexistente.\n"); exit(2); }
if (str_starts_with($dirDestino . '/', $realRaiz . '/public/')) { fwrite(STDERR,"Proibido gerar SQL em pasta pública.\n"); exit(2); }
if (file_exists($destino)) { fwrite(STDERR,"Destino já existe. Não será sobrescrito.\n"); exit(2); }
$colecoes=['users','denuncias','notificacoes','auditoria','login_attempts','rate_limits'];
$storage=$realRaiz.'/storage';
$dados=[];
function sqlTexto(?string $v): string { return $v === null ? 'NULL' : 'CONVERT(0x'.bin2hex($v).' USING utf8mb4)'; }
function sqlInt($v): string { return $v === null ? 'NULL' : (string)(int)$v; }
function sqlFloat($v): string { return $v===null || $v==='' ? 'NULL' : (is_numeric($v)&&is_finite((float)$v) ? (string)(float)$v : 'NULL'); }
function sqlJson(array $v): string { return sqlTexto(json_encode($v,JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)); }
function sqlData($v): string {
    if (!$v || !is_string($v)) return 'NULL';
    $time = strtotime($v);
    if ($time === false) throw new RuntimeException('Data inválida na origem; exportação interrompida.');
    return sqlTexto(date('Y-m-d H:i:s', $time));
}
function appendSql($fp, string $tabela, array $campos, array $valores): void {
    if (count($campos)!==count($valores)) throw new RuntimeException('Campos SQL incompatíveis');
    $col = implode(',', array_map(static fn($x) => '`'.$x.'`', $campos));
    $sql='INSERT INTO `'.$tabela.'` ('.$col.') VALUES ('.implode(',',$valores).');' . "\n";
    if (fwrite($fp,$sql) !== strlen($sql)) throw new RuntimeException('Falha ao escrever SQL privado.');
}
try {
    foreach ($colecoes as $c) {
        $f=$storage.'/'.$c.'.json';
        if (!is_file($f)) throw new RuntimeException('Arquivo ausente: '.$f);
        $dados[$c]=json_decode((string)file_get_contents($f),true,512,JSON_THROW_ON_ERROR);
        if (!is_array($dados[$c])) throw new RuntimeException('Arquivo inválido: '.$f);
    }
    foreach ($dados['users'] as &$u) {
        if (!empty($u['cpf_cifrado']) && !is_file($storage.'/.cpf.key')) throw new RuntimeException('Chave CPF ausente; restaure antes de exportar.');
        if (array_key_exists('cpf',$u)) {
            if (empty($u['cpf_cifrado'])) PrivacidadeCpf::prepararRegistro($u, (string)$u['cpf']); else unset($u['cpf']);
        }
    }
    unset($u);
    $ancora=null;
    if (is_file($storage.'/integridade.json')) {
        $ancora=json_decode((string)file_get_contents($storage.'/integridade.json'),true,512,JSON_THROW_ON_ERROR);
        if (!is_array($ancora) || !is_file($storage.'/integridade.key')) throw new RuntimeException('Histórico sem chave de integridade válida.');
    }
    $fp=fopen($destino,'x');
    if ($fp===false) throw new RuntimeException('Não foi possível criar o arquivo de destino.');
    @chmod($destino,0600);
    fwrite($fp,"-- Dados privados do Olho Cidadão; NÃO publicar no GitHub.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n");
    foreach ($dados['users'] as $u) {
        appendSql($fp,'users',['id','email','cpf_hash','role','ativo','payload'],[
            sqlInt($u['id']??0),sqlTexto(strtolower(trim((string)($u['email']??'')))),sqlTexto(($u['cpf_hash']??null)?:null),
            sqlTexto((string)($u['role']??'cidadao')),sqlInt(($u['ativo']??true)?1:0),sqlJson($u)
        ]);
    }
    foreach ($dados['denuncias'] as $d) {
        $id=(int)($d['id']??0);
        appendSql($fp,'denuncias',['id','usuario_id','categoria','status','prioridade','orgao','criada_em','latitude','longitude','payload'],[
            sqlInt($id),empty($d['usuario_id'])?'NULL':sqlInt($d['usuario_id']),sqlTexto($d['categoria']??'outros'),sqlTexto($d['status']??'Pendente'),
            sqlTexto($d['prioridade']??'Normal'),sqlTexto($d['orgao']??null),sqlData($d['criada_em']??null),sqlFloat($d['latitude']??null),sqlFloat($d['longitude']??null),sqlJson($d)
        ]);
        foreach (($d['historico']??[]) as $seq=>$h) {
            appendSql($fp,'denuncia_historico',['denuncia_id','sequencia','status','data','payload'],[
                sqlInt($id),sqlInt($seq+1),sqlTexto($h['status']??''),sqlData($h['data']??null),sqlJson($h)
            ]);
        }
        foreach (array_unique(array_map('intval',$d['apoios']??[])) as $uid) if ($uid>0) {
            appendSql($fp,'denuncia_apoios',['denuncia_id','usuario_id'],[sqlInt($id),sqlInt($uid)]);
        }
    }
    foreach ($dados['notificacoes'] as $n) {
        appendSql($fp,'notificacoes',['id','usuario_id','denuncia_id','lida','criada_em','payload'],[
            sqlInt($n['id']??0),sqlInt($n['usuario_id']??0),empty($n['denuncia_id'])?'NULL':sqlInt($n['denuncia_id']),
            sqlInt(!empty($n['lida'])?1:0),sqlData($n['criada_em']??null),sqlJson($n)
        ]);
    }
    foreach ($dados['auditoria'] as $a) {
        appendSql($fp,'auditoria',['admin_id','denuncia_id','criada_em','payload'],[
            sqlInt($a['admin_id']??0),empty($a['denuncia_id'])?'NULL':sqlInt($a['denuncia_id']),sqlData($a['data']??null),sqlJson($a)
        ]);
    }
    foreach (['login_attempts','rate_limits'] as $tbl) foreach ($dados[$tbl] as $chave=>$registro) {
        appendSql($fp,$tbl,['chave','payload'],[sqlTexto((string)$chave),sqlJson($registro)]);
    }
    if ($ancora!==null) appendSql($fp,'app_settings',['chave','payload'],[sqlTexto('integridade'),sqlJson($ancora)]);
    fwrite($fp,"COMMIT;\n");
    fclose($fp);
    echo 'SQL privado gerado: '. $destino . "\n";
    foreach ($colecoes as $c) echo $c . ': ' . count($dados[$c]) . "\n";
    echo "Importar somente em BANCO NOVO, depois de sql/schema.sql. Não publicar este arquivo.\n";
} catch (Throwable $e) {
    if (isset($fp)&&is_resource($fp)) fclose($fp);
    if (is_file($destino)) @unlink($destino);
    fwrite(STDERR,'ERRO: '.$e->getMessage()."\n");
    exit(1);
}
