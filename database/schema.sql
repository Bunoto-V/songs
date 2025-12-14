-- ========================================
-- מסד נתונים מאוחד למערכת ניהול שירים ותוכן
-- ========================================

-- יצירת מסד הנתונים
CREATE DATABASE IF NOT EXISTS songs_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE songs_management;

-- ========================================
-- טבלאות ניהול משתמשים ותפקידים
-- ========================================

-- טבלת תפקידים
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת משתמשים
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role_id INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת הרשאות משתמשים
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    permission_value BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission_key),
    INDEX idx_permission_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול קטגוריות ותגיות
-- ========================================

-- טבלת קטגוריות
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT NULL,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category_name (category_name),
    INDEX idx_parent_id (parent_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת תגיות לשירים
CREATE TABLE IF NOT EXISTS song_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tag_name (tag_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול שירים
-- ========================================

-- טבלת שירים מרכזית (מיזוג משתי המערכות)
CREATE TABLE IF NOT EXISTS songs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- פרטים בסיסיים
    title VARCHAR(255) NOT NULL,
    title_he VARCHAR(255) DEFAULT NULL,
    title_en VARCHAR(255) DEFAULT NULL,
    
    -- קטגוריזציה
    category_id INT DEFAULT NULL,
    
    -- קישורים וקבצים
    youtube_link VARCHAR(500) DEFAULT NULL,
    google_drive_link VARCHAR(500) DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT NULL,
    
    -- תוכן
    lyrics TEXT DEFAULT NULL,
    xml_content TEXT DEFAULT NULL,
    
    -- מטא-דאטה
    artist VARCHAR(255) DEFAULT NULL,
    album VARCHAR(255) DEFAULT NULL,
    duration INT DEFAULT NULL COMMENT 'משך זמן בשניות',
    file_size BIGINT DEFAULT NULL COMMENT 'גודל קובץ בבתים',
    
    -- סטטוס
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    
    -- חותמות זמן
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- מפתחות זרים
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    
    -- אינדקסים
    INDEX idx_title (title),
    INDEX idx_title_he (title_he),
    INDEX idx_title_en (title_en),
    INDEX idx_category_id (category_id),
    INDEX idx_is_active (is_active),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת קישורי שירים לתגיות (many-to-many)
CREATE TABLE IF NOT EXISTS song_tag_relations (
    song_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (song_id, tag_id),
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES song_tags(id) ON DELETE CASCADE,
    INDEX idx_song_id (song_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת תמונות/מילים לשירים (PNG של מילים)
CREATE TABLE IF NOT EXISTS song_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    song_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_type ENUM('lyrics_page', 'cover', 'other') DEFAULT 'lyrics_page',
    page_number INT DEFAULT NULL COMMENT 'מספר עמוד (למילים)',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    INDEX idx_song_id (song_id),
    INDEX idx_image_type (image_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- טבלת מטא-דאטה נוספת לשירים
CREATE TABLE IF NOT EXISTS song_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    song_id INT NOT NULL,
    metadata_key VARCHAR(100) NOT NULL,
    metadata_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_song_metadata (song_id, metadata_key),
    INDEX idx_song_id (song_id),
    INDEX idx_metadata_key (metadata_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול לוגואים
-- ========================================

-- טבלת לוגואים
CREATE TABLE IF NOT EXISTS logos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producer_name_heb VARCHAR(255) NOT NULL,
    producer_name_eng VARCHAR(255) DEFAULT NULL,
    logo_path VARCHAR(500) NOT NULL,
    logo_type ENUM('producer', 'brand', 'event', 'other') DEFAULT 'producer',
    file_format VARCHAR(20) DEFAULT NULL COMMENT 'PNG, SVG, etc.',
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_producer_name_heb (producer_name_heb),
    INDEX idx_producer_name_eng (producer_name_eng),
    INDEX idx_logo_type (logo_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול פלאגינים
-- ========================================

-- טבלת פלאגינים
CREATE TABLE IF NOT EXISTS plugins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_name VARCHAR(255) NOT NULL,
    plugin_name_en VARCHAR(255) DEFAULT NULL,
    description TEXT,
    version VARCHAR(50) DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT DEFAULT NULL,
    plugin_type ENUM('audio', 'video', 'graphics', 'other') DEFAULT 'other',
    compatible_software TEXT COMMENT 'תוכנות תואמות',
    is_active BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plugin_name (plugin_name),
    INDEX idx_plugin_type (plugin_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול סטוריז (Stories)
-- ========================================

-- טבלת תוכן לסטוריז
CREATE TABLE IF NOT EXISTS story_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content_type ENUM('music', 'logo', 'video', 'image', 'mixed') DEFAULT 'mixed',
    
    -- קישורים לתוכן
    music_file_path VARCHAR(500) DEFAULT NULL,
    logo_id INT DEFAULT NULL,
    background_image VARCHAR(500) DEFAULT NULL,
    
    -- הגדרות
    duration INT DEFAULT NULL COMMENT 'משך זמן בשניות',
    template_name VARCHAR(100) DEFAULT NULL,
    
    -- מטא-דאטה
    description TEXT,
    tags TEXT,
    
    -- סטטוס
    is_active BOOLEAN DEFAULT TRUE,
    usage_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (logo_id) REFERENCES logos(id) ON DELETE SET NULL,
    INDEX idx_content_type (content_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול לקוחות
-- ========================================

-- טבלת לקוחות
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    company_name VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_name (client_name),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלאות ניהול פוסטים לרשתות חברתיות
-- ========================================

-- פלטפורמות פרסום
CREATE TABLE IF NOT EXISTS post_platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(50) NOT NULL UNIQUE,
    platform_name_heb VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_platform_name (platform_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- פוסטים לרשתות חברתיות
CREATE TABLE IF NOT EXISTS social_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    platform_id INT,
    client_id INT DEFAULT NULL,
    scheduled_time DATETIME DEFAULT NULL,
    published_time DATETIME DEFAULT NULL,
    status ENUM('draft', 'scheduled', 'published', 'failed') DEFAULT 'draft',
    media_path VARCHAR(500) DEFAULT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_id) REFERENCES post_platforms(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_platform_id (platform_id),
    INDEX idx_client_id (client_id),
    INDEX idx_scheduled_time (scheduled_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- טבלת לוג פעילות
-- ========================================

-- יומן פעילות
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) DEFAULT NULL,
    record_id INT DEFAULT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- הכנסת נתונים ראשוניים
-- ========================================

-- הכנסת תפקידים בסיסיים
INSERT INTO roles (role_name, description) VALUES
('admin', 'מנהל מערכת - גישה מלאה לכל הפונקציות'),
('editor', 'עורך - יכול לערוך ולהעלות תוכן'),
('viewer', 'צופה - צפייה בלבד')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- הכנסת קטגוריות בסיסיות
INSERT INTO categories (category_name, description) VALUES
('חסידי', 'שירים חסידיים'),
('ליטאי', 'שירים ליטאיים'),
('מזרחי', 'שירים מזרחיים'),
('ילדים', 'שירי ילדים'),
('חתונה', 'שירים לחתונות'),
('שמחה', 'שירי שמחה')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- הכנסת פלטפורמות רשתות חברתיות
INSERT INTO post_platforms (platform_name, platform_name_heb) VALUES
('facebook', 'פייסבוק'),
('instagram', 'אינסטגרם'),
('youtube', 'יוטיוב'),
('tiktok', 'טיקטוק'),
('twitter', 'טוויטר')
ON DUPLICATE KEY UPDATE platform_name_heb=VALUES(platform_name_heb);
