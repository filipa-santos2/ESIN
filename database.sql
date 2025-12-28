PRAGMA foreign_keys = ON;

-- =========================
-- DROP (para poderes correr várias vezes)
-- =========================
DROP TABLE IF EXISTS "Evento adverso";
DROP TABLE IF EXISTS "Administração";
DROP TABLE IF EXISTS "Consulta";
DROP TABLE IF EXISTS "Visitas";
DROP TABLE IF EXISTS "Plano AIT - Alergénios";
DROP TABLE IF EXISTS "Planos AIT";
DROP TABLE IF EXISTS "Testes";
DROP TABLE IF EXISTS "Diagnósticos";
DROP TABLE IF EXISTS "Alergénios";
DROP TABLE IF EXISTS "Doenças";
DROP TABLE IF EXISTS "Produtos";
DROP TABLE IF EXISTS "Fabricantes";
DROP TABLE IF EXISTS "Pacientes";
DROP TABLE IF EXISTS "Médicos";

-- =========================
-- TABELAS BASE
-- =========================

CREATE TABLE "Médicos" (
  "id"            INTEGER PRIMARY KEY,
  "nome_completo" TEXT    NOT NULL,
  "num_ordem"     TEXT    NOT NULL UNIQUE,
  "especialidade" TEXT    NOT NULL,
  "telefone"      TEXT,
  "email"         TEXT    NOT NULL UNIQUE,
  "password_hash" TEXT
);


CREATE TABLE "Pacientes" (
  "id"            INTEGER PRIMARY KEY,
  "nome_completo" TEXT NOT NULL,
  "data_nascimento" TEXT NOT NULL,          -- ISO: YYYY-MM-DD
  "sexo"          TEXT NOT NULL CHECK ("sexo" IN ('M','F','X')),
  "telefone"      TEXT,
  "email"         TEXT
);

CREATE TABLE "Doenças" (
  "código"        TEXT PRIMARY KEY,         -- ex: CA23
  "designação"    TEXT NOT NULL
);

CREATE TABLE "Alergénios" (
  "código_who_iuis" TEXT PRIMARY KEY,       -- ex: t1, g6
  "espécie"         TEXT NOT NULL,
  "nome_comum"      TEXT NOT NULL,
  "nome_bioquímico" TEXT,
  "categoria"       TEXT NOT NULL            -- ex: mite/pollen/food
);

CREATE TABLE "Fabricantes" (
  "id"            INTEGER PRIMARY KEY,
  "nome"          TEXT NOT NULL UNIQUE,
  "país"          TEXT,
  "email"         TEXT,
  "telefone"      TEXT
);

CREATE TABLE "Produtos" (
  "id"              INTEGER PRIMARY KEY,
  "nome"            TEXT NOT NULL,
  "tipo"            TEXT NOT NULL,          -- ex: tablet/extract
  "concentração"    REAL,
  "unidade"         TEXT,                   -- ex: IR, ug/mL
  "fabricante_id"   INTEGER NOT NULL,
  FOREIGN KEY ("fabricante_id") REFERENCES "Fabricantes"("id") ON DELETE RESTRICT
);

-- =========================
-- DIAGNÓSTICOS (Paciente + Doença)
-- =========================
CREATE TABLE "Diagnósticos" (
  "id"            INTEGER PRIMARY KEY,
  "paciente_id"   INTEGER NOT NULL,
  "doença_código" TEXT    NOT NULL,
  "data_início"   TEXT    NOT NULL,         -- YYYY-MM-DD
  "data_fim"      TEXT,                     -- YYYY-MM-DD (opcional)
  "estado"        TEXT    NOT NULL CHECK ("estado" IN ('active','resolved','inactive')),
  "notas"         TEXT,
  FOREIGN KEY ("paciente_id") REFERENCES "Pacientes"("id") ON DELETE CASCADE,
  FOREIGN KEY ("doença_código") REFERENCES "Doenças"("código") ON DELETE RESTRICT,
  CHECK ("data_fim" IS NULL OR "data_fim" >= "data_início")
);

-- =========================
-- PLANOS AIT
-- =========================
CREATE TABLE "Planos AIT" (
  "id"                   INTEGER PRIMARY KEY,
  "paciente_id"          INTEGER NOT NULL,
  "produto_id"           INTEGER NOT NULL,
  "data_início"          TEXT    NOT NULL,   -- YYYY-MM-DD
  "data_fim"             TEXT,               -- YYYY-MM-DD
  "via"                  TEXT    NOT NULL CHECK ("via" IN ('subcutaneous','sublingual')),
  "protocolo_build_up"   TEXT    NOT NULL,
  "protocolo_maintenance" TEXT   NOT NULL,
  "estado"               TEXT    NOT NULL CHECK ("estado" IN ('not_started','build_up','maintenance','completed','paused')),
  "notas"                TEXT,
  FOREIGN KEY ("paciente_id") REFERENCES "Pacientes"("id") ON DELETE CASCADE,
  FOREIGN KEY ("produto_id")  REFERENCES "Produtos"("id") ON DELETE RESTRICT,
  CHECK ("data_fim" IS NULL OR "data_fim" >= "data_início")
);

-- relação N:N Plano-Alergénio
CREATE TABLE "Plano AIT - Alergénios" (
  "plano_id"        INTEGER NOT NULL,
  "alergénio_código" TEXT   NOT NULL,
  PRIMARY KEY ("plano_id","alergénio_código"),
  FOREIGN KEY ("plano_id") REFERENCES "Planos AIT"("id") ON DELETE CASCADE,
  FOREIGN KEY ("alergénio_código") REFERENCES "Alergénios"("código_who_iuis") ON DELETE RESTRICT
);

-- =========================
-- VISITAS (superclasse) + especializações
-- =========================
CREATE TABLE "Visitas" (
  "id"                 INTEGER PRIMARY KEY,
  "tipo"               TEXT NOT NULL CHECK ("tipo" IN ('consulta','administração')),
  "paciente_id"        INTEGER NOT NULL,
  "médico_id"          INTEGER NOT NULL,
  "data_hora_agendada" TEXT NOT NULL,     -- ISO: YYYY-MM-DD HH:MM
  "data_hora_início"   TEXT NOT NULL,
  "data_hora_fim"      TEXT,              -- opcional
  FOREIGN KEY ("paciente_id") REFERENCES "Pacientes"("id") ON DELETE CASCADE,
  FOREIGN KEY ("médico_id")   REFERENCES "Médicos"("id") ON DELETE RESTRICT,
  CHECK ("data_hora_fim" IS NULL OR "data_hora_fim" >= "data_hora_início")
);

CREATE TABLE "Consulta" (
  "visita_id"      INTEGER PRIMARY KEY,
  "subespecialidade" TEXT NOT NULL,
  FOREIGN KEY ("visita_id") REFERENCES "Visitas"("id") ON DELETE CASCADE
);

CREATE TABLE "Administração" (
  "visita_id"            INTEGER PRIMARY KEY,
  "produto_id"           INTEGER NOT NULL,
  "dose_nº"              INTEGER NOT NULL CHECK ("dose_nº" >= 0),
  "fase"                 TEXT    NOT NULL CHECK ("fase" IN ('build_up','maintenance')),
  "local_administração"  TEXT    NOT NULL,
  "dose_ml"              REAL    NOT NULL CHECK ("dose_ml" > 0),
  "minutos_observação"   INTEGER NOT NULL CHECK ("minutos_observação" > 0),
  FOREIGN KEY ("visita_id") REFERENCES "Visitas"("id") ON DELETE CASCADE,
  FOREIGN KEY ("produto_id") REFERENCES "Produtos"("id") ON DELETE RESTRICT
);

CREATE TABLE "Evento adverso" (
  "visita_id"     INTEGER PRIMARY KEY,
  "tipo"          TEXT NOT NULL,
  "início_minutos" INTEGER NOT NULL CHECK ("início_minutos" >= 0),
  "desfecho"      TEXT,
  FOREIGN KEY ("visita_id") REFERENCES "Visitas"("id") ON DELETE CASCADE
);

-- =========================
-- TESTES (simples, ajustas depois se o teu modelo pedir mais)
-- =========================
CREATE TABLE "Testes" (
  "id"           INTEGER PRIMARY KEY,
  "paciente_id"  INTEGER NOT NULL,
  "tipo"         TEXT NOT NULL,           -- ex: prick/IgE
  "data"         TEXT NOT NULL,           -- YYYY-MM-DD
  "resultado"    TEXT,
  "notas"        TEXT,
  FOREIGN KEY ("paciente_id") REFERENCES "Pacientes"("id") ON DELETE CASCADE
);

-- =========================
-- DADOS (seed) - MAIS COMPLETO
-- =========================

INSERT INTO "Médicos" ("id","nome_completo","num_ordem","especialidade","telefone","email") VALUES
(1,'Dra. Ana Lima','12345','Imunoalergologia','912000111','ana.lima@hospital.pt'),
(2,'Dr. Rui Costa','67890','Medicina Geral','913222333','rui.costa@hospital.pt'),
(3,'Dra. Sofia Martins','54321','Pediatria','914555666','sofia.martins@hospital.pt');

INSERT INTO "Pacientes" ("id","nome_completo","data_nascimento","sexo","telefone","email") VALUES
(1,'Joana Queirós','2001-03-12','F','911111111','joana.q@email.pt'),
(2,'Maria Silva','1995-07-22','F','922222222','maria.s@email.pt'),
(3,'Pedro Almeida','1989-11-05','M','933333333','pedro.a@email.pt'),
(4,'Inês Rocha','2004-02-18','F','944444444','ines.rocha@email.pt');

INSERT INTO "Doenças" ("código","designação") VALUES
('CA23','Asma'),
('DA61','Rinite alérgica'),
('EB12','Dermatite atópica');

INSERT INTO "Alergénios" ("código_who_iuis","espécie","nome_comum","nome_bioquímico","categoria") VALUES
('t1','Dermatophagoides pteronyssinus','Ácaro do pó','Der p 1','mite'),
('g6','Lolium perenne','Azevém','Lol p 1','pollen'),
('d1','Dermatophagoides farinae','Ácaro da farinha','Der f 1','mite'),
('c1','Felis catus','Gato','Fel d 1','dander');

INSERT INTO "Fabricantes" ("id","nome","país","email","telefone") VALUES
(1,'ALK-Abelló','Dinamarca','contact@alk.net','451234567'),
(2,'Stallergenes','França','info@stallergenes.com','331234567'),
(3,'AllergyLabs','Portugal','hello@allergylabs.pt','211234567');

INSERT INTO "Produtos" ("id","nome","tipo","concentração","unidade","fabricante_id") VALUES
(1,'Acarizax','tablet',12.0,'IR',1),
(2,'Grazax','tablet',10.0,'IR',2),
(3,'Extrato Ácaros MiteMix','extract',100.0,'ug/mL',3);

INSERT INTO "Diagnósticos" ("id","paciente_id","doença_código","data_início","data_fim","estado","notas") VALUES
(1,1,'CA23','2015-01-01',NULL,'active','Asma controlada com terapêutica.'),
(2,1,'DA61','2016-04-10',NULL,'active','Sintomas sazonais.'),
(3,2,'DA61','2010-09-01','2018-06-01','resolved','Sem sintomas desde 2018.'),
(4,4,'EB12','2012-02-01',NULL,'active','Dermatite com surtos.');

INSERT INTO "Planos AIT" ("id","paciente_id","produto_id","data_início","data_fim","via","protocolo_build_up","protocolo_maintenance","estado","notas") VALUES
(1,1,1,'2024-01-10',NULL,'subcutaneous','standard','standard','build_up','Plano iniciado em janeiro.'),
(2,2,2,'2024-05-03',NULL,'sublingual','standard','standard','maintenance','Boa adesão.'),
(3,3,3,'2025-02-01',NULL,'subcutaneous','rush','standard','not_started','A iniciar após avaliação.');

INSERT INTO "Plano AIT - Alergénios" ("plano_id","alergénio_código") VALUES
(1,'t1'),
(1,'g6'),
(2,'g6'),
(3,'d1'),
(3,'c1');

-- Visitas: uma consulta + duas administrações
INSERT INTO "Visitas" ("id","tipo","paciente_id","médico_id","data_hora_agendada","data_hora_início","data_hora_fim") VALUES
(1,'consulta',1,1,'2025-01-15 10:00','2025-01-15 10:05',NULL),
(2,'administração',1,1,'2025-01-20 09:00','2025-01-20 09:05','2025-01-20 09:35'),
(3,'administração',2,2,'2025-02-11 11:00','2025-02-11 11:02','2025-02-11 11:40');

INSERT INTO "Consulta" ("visita_id","subespecialidade") VALUES
(1,'Imunoalergologia');

INSERT INTO "Administração" ("visita_id","produto_id","dose_nº","fase","local_administração","dose_ml","minutos_observação") VALUES
(2,1,0,'build_up','Braço esquerdo',0.20,30),
(3,2,6,'maintenance','Braço direito',0.30,35);

INSERT INTO "Evento adverso" ("visita_id","tipo","início_minutos","desfecho") VALUES
(2,'Urticária ligeira',10,'Resolvido com anti-histamínico');

INSERT INTO "Testes" ("id","paciente_id","tipo","data","resultado","notas") VALUES
(1,1,'Prick test','2024-12-01','Positivo a ácaros','Wheal 6mm'),
(2,1,'IgE específica','2024-12-05','Der p 1 elevado','Confirmar sensibilização'),
(3,2,'Prick test','2024-04-20','Positivo a gramíneas','Wheal 5mm');
