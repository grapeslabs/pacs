-- ============================================
-- Carplates Database Schema
-- ============================================

-- Таблица камер 
CREATE TABLE IF NOT EXISTS cameras (
    cam_id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    stream_to_parse TEXT NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    description TEXT,
    timedelay INTEGER DEFAULT 333,
    resize FLOAT DEFAULT 1.0,
    is_recognition BOOLEAN DEFAULT true,
    threshold FLOAT DEFAULT 75,
    zone JSONB DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Индексы для таблицы камер
CREATE INDEX IF NOT EXISTS cameras_user_id_idx ON cameras USING btree (user_id);
CREATE INDEX IF NOT EXISTS cameras_status_idx ON cameras USING btree (status);

-- Таблица автомобилей пользователей
CREATE TABLE IF NOT EXISTS cars (
    id SERIAL PRIMARY KEY,
    plate VARCHAR(20) NOT NULL,
    description TEXT,
    user_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (plate, user_id)
);

CREATE INDEX IF NOT EXISTS cars_user_id_idx ON cars USING btree (user_id);
CREATE INDEX IF NOT EXISTS cars_plate_idx ON cars USING btree (plate);

-- Таблица распознанных номеров 
CREATE TABLE IF NOT EXISTS recognized_plates (
    id SERIAL PRIMARY KEY,
    plate_text VARCHAR(20) NOT NULL,
    camera_id VARCHAR(255),
    user_id VARCHAR(255),
    is_authorized BOOLEAN,
    image TEXT,
    plate TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Индексы для таблицы распознанных номеров
CREATE INDEX IF NOT EXISTS plates_created_at_idx ON recognized_plates USING btree (created_at);
CREATE INDEX IF NOT EXISTS plates_plate_text_idx ON recognized_plates USING btree (plate_text);
CREATE INDEX IF NOT EXISTS plates_user_id_idx ON recognized_plates USING btree (user_id);
CREATE INDEX IF NOT EXISTS plates_camera_id_idx ON recognized_plates USING btree (camera_id);