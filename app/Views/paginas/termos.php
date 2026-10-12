<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - Olho Cidadão</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/paginas.css?v=0.52.9">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontes/fontes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/vendor/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/responsivo.css?v=1.0.1">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/telas-cartoes.css?v=0.52.9">
    <meta name="theme-color" content="#181f2c">
    <?php require __DIR__ . "/../_pwa.php"; ?>
</head>
<body class="pagina-publica">

    <div class="pagina-topo">
        <div class="marca">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="Logo Olho Cidadão">
            <span>Olho <span>Cidadão</span></span>
        </div>
        <a href="<?= BASE_URL ?>/?rota=cadastro"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
    </div>

    <div class="pagina-conteudo">
        <h1>Termos de Uso e Privacidade</h1>
        <p class="subtitulo">Este projeto é acadêmico e simula o funcionamento de um sistema real de denúncias. Versão de privacidade: 08/10/2026.</p>
        <section class="secao-card" style="--tom:#3D8BFD">
            <h2><span class="ic-tom"><i class="fa-solid fa-circle-info"></i></span>1. Sobre o uso do sistema</h2>
        <p>
            O Olho Cidadão foi criado para fins de aprendizado e demonstração. Ao criar uma
            conta, você concorda em usar o sistema de forma responsável, enviando apenas
            denúncias que reflitam problemas urbanos reais ou, no contexto deste projeto,
            situações de teste.
        </p>
        </section>

        <section class="secao-card" style="--tom:#14B8A6">
            <h2><span class="ic-tom"><i class="fa-solid fa-database"></i></span>2. Dados cadastrados</h2>
        <ul>
            <li><strong>Cadastro e perfil:</strong> nome, e-mail, senha protegida por hash, cidade, telefone, CPF, CEP e foto (quando fornecidos), para autenticação, identificação da conta, atendimento e prevenção de cadastros duplicados.</li>
            <li><strong>CPF:</strong> é armazenado cifrado e consultado por um código HMAC separado para detectar duplicidade. Só é decifrado pelo servidor quando necessário para o titular; a equipe deve restringir o acesso a esses dados.</li>
            <li>As fotos enviadas junto com uma denúncia ficam associadas a ela e podem ser vistas pela administração.</li>
            <li><strong>Denúncias:</strong> descrição, categoria, protocolo, endereço e localização (quando enviados), data, status e histórico de alterações. O mapa público mostra apenas localização aproximada, categoria e situação. Fotos privadas, relatos completos e endereços exatos não aparecem no mapa. A consulta por protocolo também é limitada. Evite inserir dados pessoais de terceiros.</li>
            <li><strong>Segurança:</strong> registros de acesso, limites de tentativas e logs técnicos podem ser tratados para prevenir abuso, diagnosticar falhas e preservar a integridade do serviço.</li>
            <li><strong>Proteção de acesso:</strong> o administrador pode ativar autenticação em duas etapas; quando a confirmação de e-mail está habilitada e configurada, novos usuários recebem link de verificação. Tokens são temporários e armazenados como hash.</li>
            <li><strong>Assistente Olhinho:</strong> em modo local, a conversa fica no navegador. Se a integração com um provedor externo de IA for ativada, o texto da pergunta poderá ser transmitido a ele, após tentativa de ocultar identificadores. Não escreva informações sensíveis nas perguntas.</li>
            <li><strong>Denúncia por voz:</strong> o microfone só é ativado quando você usa o botão. O reconhecimento de fala pode envolver o serviço do navegador; o sistema armazena o texto enviado, e não o arquivo de áudio.</li>
        </ul>
        </section>

        <section class="secao-card" style="--tom:#3D8BFD">
            <h2><span class="ic-tom"><i class="fa-solid fa-shield-halved"></i></span>3. Finalidades, compartilhamento e proteção</h2>
            <p>Os dados são utilizados para manter a conta, registrar e acompanhar denúncias, permitir ações da moderação, prevenir fraudes e melhorar a segurança. A equipe responsável pelo projeto administra os registros. Indicadores agregados e informações não identificadoras podem ser vistos por visitantes, e serviços externos de mapas, fontes, IA ou reconhecimento de voz podem tratar informações conforme suas próprias políticas.</p>
            <p>A senha fica armazenada como hash; o CPF é cifrado com chave privada no servidor. Os arquivos internos e backups devem permanecer inacessíveis pela web, com acesso limitado aos responsáveis. Essas medidas reduzem riscos, mas não eliminam a necessidade de configuração adequada do servidor.</p>
        </section>

        <section class="secao-card" style="--tom:#14B8A6">
            <h2><span class="ic-tom"><i class="fa-solid fa-user-gear"></i></span>4. Acesso, exclusão e conservação</h2>
            <p>Em <strong>Perfil</strong>, você pode consultar e corrigir dados, exportar uma cópia e solicitar a exclusão da conta (exceto contas administrativas). Denúncias podem permanecer sem vinculação direta à conta para preservar o histórico de atendimento. Backups dos arquivos JSON são gerados diariamente quando há acesso ao sistema, ou por tarefa agendada, e conservados por até 30 dias. Uma exclusão pode demorar esse período para desaparecer das cópias de segurança.</p>
            <p>Os registros ativos permanecem enquanto necessários à operação do projeto. Pedidos adicionais de acesso, correção ou informações devem ser encaminhados à equipe responsável<?= defined('LGPD_CONTATO') && LGPD_CONTATO !== '' ? ' pelo e-mail ' . htmlspecialchars(LGPD_CONTATO, ENT_QUOTES, 'UTF-8') : ' pelos canais divulgados pela equipe do projeto' ?>. Antes de um uso real, os responsáveis precisam definir prazos completos de retenção e as bases legais pertinentes.</p>
        </section>

        <section class="secao-card" style="--tom:#F5B301">
            <h2><span class="ic-tom"><i class="fa-solid fa-gavel"></i></span>5. Denúncias e moderação</h2>
        <p>
            A administração pode analisar, encaminhar, rejeitar com justificativa ou marcar ocorrências como resolvidas, registrando seu histórico. O cidadão pode complementar o relato, recorrer de uma rejeição e confirmar ou contestar a resolução, conforme as regras disponíveis.
        </p>
        </section>

        <section class="secao-card" style="--tom:#EF4444">
            <h2><span class="ic-tom"><i class="fa-solid fa-ban"></i></span>6. Uso indevido</h2>
        <p>
            Não é permitido usar o sistema para enviar denúncias falsas de má-fé, conteúdo
            ofensivo ou informações de terceiros sem consentimento.
        </p>
        </section>

        <section class="secao-card" style="--tom:#A78BFA">
            <h2><span class="ic-tom"><i class="fa-solid fa-graduation-cap"></i></span>7. Contexto do projeto</h2>
        <p>
            Por se tratar de um projeto escolar, este sistema não representa nenhum órgão
            público real e não deve ser usado para relatar emergências. Em caso de urgência,
            procure os canais oficiais da sua cidade.
        </p>
        </section>
    </div>

<?php require __DIR__ . '/../_vlibras.php'; ?>
</body>
</html>
