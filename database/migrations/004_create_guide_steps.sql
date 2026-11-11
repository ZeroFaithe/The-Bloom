CREATE TABLE guide_steps (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    guide_id         INT UNSIGNED NOT NULL,
    step_number      SMALLINT UNSIGNED NOT NULL,
    instruction      TEXT NOT NULL,
    duration_seconds SMALLINT UNSIGNED NULL,
    pour_g           DECIMAL(6,1) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_steps_order (guide_id, step_number),
    CONSTRAINT fk_steps_guide FOREIGN KEY (guide_id) REFERENCES guides (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
