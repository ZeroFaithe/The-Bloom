CREATE TABLE video_cache (
    video_id      VARCHAR(20)  NOT NULL,
    title         VARCHAR(255) NOT NULL,
    channel_title VARCHAR(150) NOT NULL,
    thumbnail_url VARCHAR(255) NULL,
    fetched_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (video_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
