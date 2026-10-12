-- Dados fixos usados pelo sistema (categorias e setores reais do Olho Cidadão)
USE `olho_cidadao_modelo`;

INSERT INTO `categorias` (`slug`, `nome`) VALUES
  ('iluminacao',  'Iluminação'),
  ('sinalizacao', 'Sinalização'),
  ('lixo',        'Lixo'),
  ('buracos',     'Buracos e vias'),
  ('esgoto',      'Esgoto e drenagem'),
  ('agua',        'Abastecimento de água'),
  ('arborizacao', 'Árvores e poda'),
  ('outros',      'Outros');

INSERT INTO `orgaos` (`slug`, `nome`, `prazo_dias`) VALUES
  ('obras',      'Secretaria Municipal de Obras',                15),
  ('iluminacao', 'Setor de Iluminação Pública',                   7),
  ('transito',   'Departamento Municipal de Trânsito',           10),
  ('limpeza',    'Secretaria de Meio Ambiente e Limpeza Urbana',  5);
