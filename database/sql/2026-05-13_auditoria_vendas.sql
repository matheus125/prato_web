-- Auditoria e reforco de duplicidade das vendas de senhas.
-- Banco alvo: prato_cheio

SET @schema_name := DATABASE();

SET @col_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tb_senhas'
      AND COLUMN_NAME = 'dedupe_key'
);
SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE tb_senhas ADD COLUMN dedupe_key VARCHAR(80) NULL AFTER id_dependente',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE tb_senhas
SET dedupe_key = CASE
    WHEN UPPER(TRIM(IFNULL(tipoSenha, ''))) = 'GENERICA' THEN NULL
    WHEN IFNULL(id_dependente, 0) > 0 THEN CONCAT('DEPENDENTE:', id_dependente)
    WHEN IFNULL(id_titular, 0) > 0 THEN CONCAT('TITULAR:', id_titular)
    WHEN REGEXP_REPLACE(IFNULL(cpf, ''), '[^0-9]', '') <> '' THEN CONCAT('CPF:', REGEXP_REPLACE(IFNULL(cpf, ''), '[^0-9]', ''))
    ELSE NULL
END
WHERE dedupe_key IS NULL;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tb_senhas'
      AND INDEX_NAME = 'uq_senhas_data_dedupe'
);
SET @ddl := IF(
    @idx_exists = 0,
    'CREATE UNIQUE INDEX uq_senhas_data_dedupe ON tb_senhas (data_refeicao, dedupe_key)',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tb_userlogs'
      AND INDEX_NAME = 'idx_userlogs_created_at'
);
SET @ddl := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_userlogs_created_at ON tb_userlogs (created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'tb_userlogs'
      AND INDEX_NAME = 'idx_userlogs_modulo_created'
);
SET @ddl := IF(
    @idx_exists = 0,
    'CREATE INDEX idx_userlogs_modulo_created ON tb_userlogs (modulo, created_at)',
    'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
