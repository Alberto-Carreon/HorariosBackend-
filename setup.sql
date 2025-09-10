CREATE TABLE IF NOT EXISTS subjects(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  norm VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS allow_tutores(
  rfc VARCHAR(16) PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS allow_mentores(
  cuenta VARCHAR(16) PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS allow_alumnos(
  cuenta VARCHAR(16) PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users(
  id VARCHAR(32) NOT NULL,
  role VARCHAR(16) NOT NULL,
  name VARCHAR(255),
  email VARCHAR(255),
  created_at DATETIME NOT NULL,
  PRIMARY KEY (id, role)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedules(
  id VARCHAR(40) PRIMARY KEY,
  owner_id VARCHAR(32) NOT NULL,
  owner_role VARCHAR(16) NOT NULL,
  owner_name VARCHAR(255),
  email VARCHAR(255),
  type VARCHAR(16) NOT NULL,        -- 'tutorial' | 'asesoria' | 'mentoria'
  day VARCHAR(16) NOT NULL,         -- Lunes..Sabado (sin acentos)
  start TIME NOT NULL,
  end TIME NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_owner (owner_id),
  KEY idx_type (type),
  KEY idx_day (day),
  KEY idx_time (start,end)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS schedule_subjects(
  schedule_id VARCHAR(40) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  PRIMARY KEY (schedule_id, subject),
  CONSTRAINT fk_sched FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs(
  id VARCHAR(40) PRIMARY KEY,
  ts DATETIME NOT NULL,
  type VARCHAR(16) NOT NULL,        -- 'search' | 'contact'
  account VARCHAR(32),
  filters TEXT,
  schedule_id VARCHAR(40),
  schedule_type VARCHAR(16),
  subject_used TEXT,
  person_name VARCHAR(255),
  person_role VARCHAR(16)
) ENGINE=InnoDB;
