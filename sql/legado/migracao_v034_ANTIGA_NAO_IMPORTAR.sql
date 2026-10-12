-- Olho Cidadão v0.34 — colunas novas (só necessárias ao migrar para MySQL).
-- Rode DEPOIS do schema.sql. No modo JSON (padrão) nada disso é preciso:
-- os campos novos são criados automaticamente quando usados.

ALTER TABLE users
  ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER role;   -- 0 = conta suspensa

ALTER TABLE denuncias
  ADD COLUMN prioridade ENUM('Baixa','Normal','Alta','Urgente') NOT NULL DEFAULT 'Normal' AFTER status,
  ADD COLUMN editada_em DATETIME NULL AFTER criada_em;
