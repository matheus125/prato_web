/* =========================================================
   X) TABELA TEMPORÁRIA DE DEPENDENTES (ESTRUTURA ANTIGA)
   ========================================================= */
DROP TABLE IF EXISTS tb_dependentes_import;

CREATE TABLE tb_dependentes_import (
    id INT NULL,
    id_titular INT NULL,
    nome_dependente VARCHAR(100) NULL,
    rg VARCHAR(10) NULL,
    cpf VARCHAR(15) NULL,
    Idade INT NULL,
    genero VARCHAR(10) NULL,
    dependencia_cliente VARCHAR(50) NULL,
    registration_date TIMESTAMP NULL,
    registration_date_update TIMESTAMP NULL
);

/* =========================================================
   COLE AQUI O INSERT ANTIGO DE tb_dependentes
   ========================================================= */
INSERT INTO tb_dependentes_import (
    id,
    id_titular,
    nome_dependente,
    rg,
    cpf,
    Idade,
    genero,
    dependencia_cliente,
    registration_date,
    registration_date_update
) VALUES (73,348,'PAOLA VITORIA DA SILVA DA SILVA','3974082-0','091.783.202-75',16,'Feminino','FILHO/ENTEADO','2026-01-09 13:19:33',NULL),(74,348,'SAMANTHA VICTORIA DA SILVA FARIAS ','0000000-0','091.783.242-62',13,'Feminino','FILHO/ENTEADO','2026-01-09 13:21:20',NULL),(75,360,'ALEXANDRE DA SILVA PACHECO','       - ','081.620.922-77',15,'Feminino','FILHO/ENTEADO','2026-01-09 16:01:37',NULL),(76,395,'AANA ELLOYSA BULCAO DE SOUZA','3989774-5','   .   .   -  ',12,'Feminino','IRMÃO','2026-01-12 15:46:39',NULL),(77,409,'REBECA DE SOUZA CATIQUE','       - ','   .   .   -  ',16,'Feminino','FILHO/ENTEADO','2026-01-13 12:20:24',NULL),(78,444,'NORA GAELA RIVERA CAMPOS','       - ','086.279.222-33',6,'Feminino','FILHO/ENTEADO','2026-01-14 12:42:52',NULL),(79,399,'NATALIA FRANCILEIA CASTRO','4051172-3','088.790.092-57',15,'Feminino','FILHO/ENTEADO','2026-01-14 12:46:23',NULL),(80,450,'MARCELO RODRIGUES DOS SANTO','       - ','706.398.782-88',16,'Masculino','FILHO/ENTEADO','2026-01-14 13:33:38',NULL),(81,459,'JAMILLY FREITAS RODRIGUES','       - ','706.398.782-88',12,'Feminino','FILHO/ENTEADO','2026-01-14 15:25:59',NULL),(82,459,'JOÃO DA COSTA DO NASCIMENTO','       - ','102.613.052-00',15,'Masculino','FILHO/ENTEADO','2026-01-14 15:28:23',NULL),(83,390,'MOISES MACIEL MAFRA','       - ','090.128.562-56',16,'Masculino','FILHO/ENTEADO','2026-01-14 16:27:17',NULL),(84,496,'SAMELA ESTER DE LIMA','3464404-0','705.533.732-18',11,'Feminino','AVÓ','2026-01-15 15:23:28',NULL),(85,500,'BRENDO CAMPOS FIGUEIRDO','       - ','700.792.472-70',12,'Masculino','AVÓ','2026-01-15 15:58:01',NULL),(86,500,'BRUNA CAMPOS FIGUEIREDO','       - ','705.674.382-01',9,'Feminino','AVÓ','2026-01-15 15:59:11',NULL),(87,504,'YASMIM STELLA CARDOSO','       - ','106.334.002-03',13,'Feminino','FILHO/ENTEADO','2026-01-15 16:36:03',NULL),(88,521,'THIAGO NUNES DE AZEVEDO','       - ','081.964.112-00',7,'Masculino','FILHO/ENTEADO','2026-01-16 12:53:22',NULL),(89,532,'JONAS BITECOURT  SANTOS FEITOSA','       - ','077.711.672-33',7,'Masculino','FILHO/ENTEADO','2026-01-20 15:19:11',NULL),(90,533,'RAYANE VITORIA DA SILVA SOARES','       - ','072.752.402-03',14,'Feminino','FILHO/ENTEADO','2026-01-20 15:20:39',NULL),(91,607,'ERICK JHONNY BRSZAO','       - ','072.752.402-03',10,'Masculino','FILHO/ENTEADO','2026-01-20 15:48:15',NULL),(92,607,'CLARA BRAZAO RODRIGUES','       - ','073.869.792-31',7,'Feminino','FILHO/ENTEADO','2026-01-20 15:49:09',NULL),(93,613,'JOÃO PEDRO GOMES GUIMARAES','       - ','080.234.242-62',16,'Masculino','FILHO/ENTEADO','2026-01-20 16:37:54',NULL),(94,646,'RENATO  LUNIERE BEZERRA','       - ','103.926.092-66',8,'Masculino','FILHO/ENTEADO','2026-01-21 14:06:48',NULL),(95,680,'ISRAEL ADONAI VIEIS','       - ','096.850.792-18',5,'Masculino','FILHO/ENTEADO','2026-01-22 12:24:34',NULL),(96,706,'VALENTINA HERREIRA SOAREZ','       - ','116.080.702-78',13,'Feminino','FILHO/ENTEADO','2026-01-22 15:28:19',NULL),(97,706,'ELIAS JOSE HERREIRA SUAREZ','       - ','116.080.592-00',8,'Masculino','FILHO/ENTEADO','2026-01-22 15:30:07',NULL),(98,706,'MOISES DAVID HERREIRA SUAREZ','       - ','116.080.632-24',15,'Masculino','FILHO/ENTEADO','2026-01-22 15:30:58',NULL),(99,817,'DANNA KAYLANE SARAIVA PRATA','       - ','704.819.672-65',16,'Feminino','FILHO/ENTEADO','2026-01-27 13:57:44',NULL),(100,714,'JAMILY IZABEL DOS SANTOS','       - ','080.542.412-16',17,'Feminino','FILHO/ENTEADO','2026-01-29 16:27:30',NULL),(101,860,'JOSUE CABRAL DA SILVA','       - ','076.436.662-96',12,'Masculino','AVÓ','2026-01-30 14:36:40',NULL),(102,877,'EDIVANDRO DA SILVA MEIRELES','       - ','065.306.572-82',14,'Masculino','FILHO/ENTEADO','2026-01-30 16:06:16',NULL),(103,886,'CLARICE RAFAELA COSTA DOS SANTOS','       - ','104.659.852-03',12,'Masculino','FILHO/ENTEADO','2026-02-02 14:38:22',NULL),(104,886,'BERNADO EZEQUIEL COSTA DA SILVA','       - ','104.659.772-86',15,'Masculino','FILHO/ENTEADO','2026-02-02 17:53:13',NULL),(105,532,'RAYANE VITORIA DA SENA SOARES','       - ','072.752.402-03',14,'Feminino','FILHO/ENTEADO','2026-02-02 18:08:39',NULL),(106,533,'RODSON  SAMUEL SOARES PEREIRA','       - ','815.265.712-34',17,'Masculino','FILHO/ENTEADO','2026-02-02 18:12:21',NULL),(107,922,'SAMELA ESTER DE LIMA','       - ','705.533.732-18',12,'Masculino','AVÓ','2026-02-03 13:55:01',NULL),(108,924,'YASMIM CLARA DA SILVA ROMANA','       - ','067.384.722-50',12,'Feminino','AVÓ','2026-02-03 14:10:56',NULL),(109,671,'JOELLY HADASSA LIMA DA SILVA','       - ','701.402.412-48',15,'Feminino','FILHO/ENTEADO','2026-02-03 14:36:28',NULL),(110,937,'MARIA EDUARDA DE SOUZA NEVES','       - ','071.413.422-81',16,'Feminino','FILHO/ENTEADO','2026-02-03 16:26:43',NULL),(111,562,'CINTHIA MARA NONATO DA CUNHA','       - ','099.798.682-44',16,'Feminino','FILHO/ENTEADO','2026-02-04 14:40:53',NULL),(112,1009,'JUAN DANTAS RIBEIRO','       - ','045.028.772-63',16,'Masculino','FILHO/ENTEADO','2026-02-05 16:04:22',NULL),(113,1025,'ANTONNY SENA DE SOUZA','       - ','067.366.952-10',14,'Masculino','FILHO/ENTEADO','2026-02-06 16:11:06',NULL),(114,1051,'SABRINA CUNHA DE OLIVEIRA','       - ','074.100.972-21',12,'Feminino','IRMÃO','2026-02-09 16:40:55',NULL),(115,1072,'FIONELLO DE LOS ANGLES','       - ','712.220.672-69',10,'Masculino','FILHO/ENTEADO','2026-02-11 14:38:26',NULL),(116,1072,'MOISES HERNANDEZ','       - ','712.220.582-78',6,'Masculino','FILHO/ENTEADO','2026-02-11 14:39:42',NULL),(117,1072,'ABRAHAN','       - ','712.220.582-78',12,'Masculino','FILHO/ENTEADO','2026-02-11 14:43:29',NULL),(118,1080,'LAYA HERNANDEZ','       - ','706.045.122-60',14,'Feminino','FILHO/ENTEADO','2026-02-12 14:34:14',NULL),(119,1163,'CHRISTIANE DAYANE ARAUJO GRACIA','       - ','110.186.892-90',8,'Feminino','FILHO/ENTEADO','2026-03-03 15:58:14',NULL),(120,1163,'CHRISTEME EMANUEL ARAUJO GRACIA','       - ','110.186.892-90',12,'Masculino','FILHO/ENTEADO','2026-03-03 15:59:39',NULL),(121,1189,'AGATHA EMANUELLY VILAÇA','       - ','053.107.972-46',9,'Feminino','FILHO/ENTEADO','2026-03-18 14:53:50',NULL),(122,1189,'AGNES ELOA VILAÇA LUCCAS','       - ','086.173.362-26',6,'Masculino','FILHO/ENTEADO','2026-03-18 14:57:22',NULL),(123,1204,'HEITOR BATISTA BRITO','       - ','103.819.522-58',5,'Masculino','FILHO/ENTEADO','2026-03-27 15:14:54',NULL),(124,1204,'HEITOR BATISTA BRITO','       - ','103.819.522-58',5,'Masculino','FILHO/ENTEADO','2026-03-27 15:16:50',NULL),(125,1204,'HEITOR BATISTA BRITO','       - ','103.819.522-58',5,'Masculino','FILHO/ENTEADO','2026-03-27 15:18:30',NULL),(126,1214,'LAYLA RIBEIRO TRAVESSA','       - ','082.808.742-32',10,'Feminino','FILHO/ENTEADO','2026-03-31 13:54:25',NULL),(127,1214,'PEDRO HENRIQUE RIBEIRO','       - ','082.808.742-32',17,'Masculino','FILHO/ENTEADO','2026-03-31 13:55:22',NULL),(128,1214,'SOFIA    RIBEIRO TRAVESSA','       - ','081.079.512-47',15,'Masculino','FILHO/ENTEADO','2026-03-31 13:56:10',NULL),(129,1217,'LUIZ RAFAEL DIAZ BADEL','       - ','081.079.512-47',11,'Masculino','FILHO/ENTEADO','2026-03-31 16:10:57',NULL),(130,1217,'ISAAC JOSE DIAZ BADEL','       - ','111.842.462-03',10,'Masculino','FILHO/ENTEADO','2026-03-31 16:11:57',NULL),(131,1227,'RUAN LUCAS NEVES PENA','       - ','072.193.492-70',16,'Masculino','FILHO/ENTEADO','2026-04-07 16:44:24',NULL),(132,1227,'FABIANO DA SILVA PENA','       - ','106.216.562-40',15,'Masculino','FILHO/ENTEADO','2026-04-07 16:45:15',NULL),(133,1227,'ENZO GABRIEL DA SILVA PENA','       - ','106.216.312-56',10,'Masculino','FILHO/ENTEADO','2026-04-07 16:46:07',NULL),(134,1230,'MARCIELLY PAIVA ALVES','       - ','704.985.722-00',13,'Feminino','FILHO/ENTEADO','2026-04-09 15:03:52',NULL);

/* =========================================================
   X+1) IMPORTA PARA tb_dependentes REAL
   ========================================================= */
INSERT INTO tb_dependentes (
    id,
    id_titular,
    id_familia,
    nome,
    rg,
    cpf,
    data_nascimento,
    idade,
    genero,
    dependencia_cliente,
    registration_date,
    registration_date_update
)
SELECT
    d.id AS id,
    CASE
        WHEN d.id_titular IS NULL THEN NULL
        WHEN EXISTS (
            SELECT 1
            FROM tb_titular t
            WHERE t.id = d.id_titular
        ) THEN d.id_titular
        ELSE NULL
    END AS id_titular,
    (
        SELECT t.id_familia
        FROM tb_titular t
        WHERE t.id = d.id_titular
        LIMIT 1
    ) AS id_familia,
    NULLIF(TRIM(d.nome_dependente), '') AS nome,
    CASE
        WHEN d.rg IS NULL OR TRIM(d.rg) = '' OR TRIM(d.rg) IN ('-', '0000000-0', '0000000', '       - ') THEN NULL
        ELSE TRIM(d.rg)
    END AS rg,
    CASE
        WHEN d.cpf IS NULL THEN NULL
        WHEN REPLACE(REPLACE(REPLACE(TRIM(d.cpf), '.', ''), '-', ''), ' ', '') IN ('', '00000000000') THEN NULL
        ELSE TRIM(d.cpf)
    END AS cpf,
    NULL AS data_nascimento,
    d.Idade AS idade,
    CASE
        WHEN UPPER(TRIM(d.genero)) IN ('MASCULINO', 'M') THEN 'M'
        WHEN UPPER(TRIM(d.genero)) IN ('FEMININO', 'F') THEN 'F'
        ELSE 'Outro'
    END AS genero,
    NULLIF(TRIM(d.dependencia_cliente), '') AS dependencia_cliente,
    d.registration_date,
    d.registration_date_update
FROM tb_dependentes_import d
ON DUPLICATE KEY UPDATE
    id_titular = VALUES(id_titular),
    id_familia = VALUES(id_familia),
    nome = VALUES(nome),
    rg = VALUES(rg),
    cpf = VALUES(cpf),
    data_nascimento = VALUES(data_nascimento),
    idade = VALUES(idade),
    genero = VALUES(genero),
    dependencia_cliente = VALUES(dependencia_cliente),
    registration_date = VALUES(registration_date),
    registration_date_update = VALUES(registration_date_update);

/* =========================================================
   X+2) AJUSTA AUTO_INCREMENT
   ========================================================= */
SET @max_id_dependente = (SELECT IFNULL(MAX(id), 0) + 1 FROM tb_dependentes);
SET @sql_dependente = CONCAT('ALTER TABLE tb_dependentes AUTO_INCREMENT = ', @max_id_dependente);
PREPARE stmt_dependente FROM @sql_dependente;
EXECUTE stmt_dependente;
DEALLOCATE PREPARE stmt_dependente;

/* =========================================================
   X+3) LIMPEZA
   ========================================================= */
DROP TABLE IF EXISTS tb_dependentes_import;