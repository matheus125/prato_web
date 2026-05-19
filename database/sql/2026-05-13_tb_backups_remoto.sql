CREATE TABLE IF NOT EXISTS tb_backups (
  id INT NOT NULL AUTO_INCREMENT,
  nome_banco VARCHAR(150) DEFAULT NULL,
  nome_arquivo VARCHAR(255) DEFAULT NULL,
  status_upload VARCHAR(50) NOT NULL DEFAULT 'RECEBIDO',
  data_backup DATETIME DEFAULT NULL,
  data_upload TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  caminho_remoto VARCHAR(500) DEFAULT NULL,
  hash_arquivo CHAR(64) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_tb_backups_hash (hash_arquivo),
  KEY idx_tb_backups_nome_banco (nome_banco),
  KEY idx_tb_backups_upload (data_upload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
