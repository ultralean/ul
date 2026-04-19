-- UP

CREATE TABLE sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    base_url VARCHAR(255),
    rss_url VARCHAR(255),

    category_id INT, 

    language VARCHAR(50) DEFAULT 'en',
    country VARCHAR(50),

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- DOWN

DROP TABLE sources;