SET @fam := 821;

UPDATE tb_titular
SET id_familia = (@fam := @fam + 1)
ORDER BY id
LIMIT 1235;