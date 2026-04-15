-- UP

CREATE TABLE news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    title VARCHAR(1000) NOT NULL,
    slug VARCHAR(255) UNIQUE,

    summary TEXT,

    image_url VARCHAR(1000),

    source_id SMALLINT UNSIGNED,
    source_url VARCHAR(1000),

    category_id SMALLINT UNSIGNED,

    published_at DATETIME,
    fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    is_featured TINYINT(1) DEFAULT 0,

    views INT UNSIGNED DEFAULT 0,

    UNIQUE KEY unique_article (source_url),

    INDEX idx_category (category_id),
    INDEX idx_published (published_at),
    INDEX idx_source (source_id),

    FOREIGN KEY (source_id) REFERENCES sources(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- DOWN

DROP TABLE news;