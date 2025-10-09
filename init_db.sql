-- Расширение для работы с векторами
CREATE EXTENSION IF NOT EXISTS vector;

-- ========== Таблица персон ==========
CREATE TABLE IF NOT EXISTS public.percone (
    id_percone SERIAL,
    user_id VARCHAR(255),
    person_id VARCHAR(255),
    description TEXT,
    tag VARCHAR(255),
    percone_dttm VARCHAR(255),
    view_percone BOOLEAN DEFAULT true NOT NULL,
    CONSTRAINT percone_person_id_key UNIQUE(person_id),
    CONSTRAINT percone_pkey PRIMARY KEY(id_percone)
);

CREATE INDEX IF NOT EXISTS percone_description_idx ON public.percone USING btree (description);
CREATE INDEX IF NOT EXISTS percone_person_id_idx ON public.percone USING btree (person_id);
CREATE INDEX IF NOT EXISTS percone_user_id_idx ON public.percone USING btree (user_id);

ALTER TABLE public.percone OWNER TO postgres;


-- ========== Таблица камер ==========
CREATE TABLE IF NOT EXISTS public.cameras (
    cam_id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,  -- должно быть description, не desc
    stream_to_parse TEXT NOT NULL,
    user_id VARCHAR(255) NOT NULL,
    face_width_max INTEGER DEFAULT 50,
    timedelay INTEGER DEFAULT 333,
    resize FLOAT,
    crop_params JSONB,
    extraqueue INTEGER DEFAULT 1,
    status VARCHAR(50) DEFAULT 'active',
    motion_min_area INTEGER DEFAULT 500,
    motion_threshold INTEGER DEFAULT 25,
    motion_record_after_time INTEGER DEFAULT 3,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- ========== Таблица фотографий ==========
CREATE TABLE IF NOT EXISTS public.photo (
    id_photo SERIAL,
    filein VARCHAR(255),
    person_id VARCHAR(255),
    photo_id VARCHAR(255),
    quality INTEGER,
    photo_dttm VARCHAR(255),
    vector public.vector,
    vector128 vector(128),
    photo TEXT,
    view_photo BOOLEAN DEFAULT true NOT NULL,
    checksum VARCHAR(64),
    CONSTRAINT photo_pkey PRIMARY KEY(id_photo),
    CONSTRAINT photo_person_id_fkey FOREIGN KEY (person_id)
        REFERENCES public.percone(person_id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
);

CREATE INDEX IF NOT EXISTS photo_checksum_idx ON public.photo USING btree (checksum);
CREATE INDEX IF NOT EXISTS photo_person_id_idx ON public.photo USING btree (person_id);
CREATE INDEX IF NOT EXISTS photo_photo_id_idx ON public.photo USING btree (photo_id);

ALTER TABLE public.photo OWNER TO postgres;

-- ========== Таблица неизвестных лиц ==========
CREATE TABLE IF NOT EXISTS public.unknown (
    id SERIAL,
    uuid TEXT,
    data TEXT,
    embedding vector(512),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    last_response_at TIMESTAMP WITH TIME ZONE,
    response_count INTEGER DEFAULT 0,
    CONSTRAINT unknown_pkey PRIMARY KEY(id)
);

CREATE INDEX IF NOT EXISTS embedding_hnsw_idx ON public.unknown USING hnsw (embedding vector_l2_ops);

ALTER TABLE public.unknown OWNER TO postgres;

-- ========== Таблица событий распознавания ==========
CREATE TABLE IF NOT EXISTS public.analytics_events (
    id BIGSERIAL PRIMARY KEY,
    datetime TIMESTAMP NOT NULL,
    camera_id VARCHAR(255) NOT NULL,
    type VARCHAR(255) NOT NULL,
    person_photobank_id VARCHAR(255) NOT NULL,
    event_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    data JSONB NOT NULL,
    user_id BIGINT,
    is_unknown BOOLEAN
);

-- Индексы для ускорения запросов (опционально, но рекомендуются)
CREATE INDEX IF NOT EXISTS analytics_events_datetime_idx ON public.analytics_events(datetime);
CREATE INDEX IF NOT EXISTS analytics_events_camera_id_idx ON public.analytics_events(camera_id);
CREATE INDEX IF NOT EXISTS analytics_events_event_id_idx ON public.analytics_events(event_id);

ALTER TABLE public.analytics_events OWNER TO postgres;

-- ========== Функция поиска/создания неизвестного лица ==========
CREATE OR REPLACE FUNCTION public.find_or_create_by_vector(
    new_uuid text,
    search_vector public.vector,
    max_distance double precision,
    new_data text,
    OUT result_uuid text,
    OUT action_type text,
    OUT distance double precision
)
RETURNS record
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
PARALLEL UNSAFE
COST 100
AS $body$
DECLARE
    nearest_record record;
BEGIN
    -- Ищем ближайший вектор
    SELECT uuid, response_count, embedding <-> search_vector AS dist
    INTO nearest_record
    FROM unknown
    ORDER BY embedding <-> search_vector
    LIMIT 1;

    -- Если нашли запись и она в пределах max_distance
    IF nearest_record.uuid IS NOT NULL AND nearest_record.dist <= max_distance THEN
        -- Обновляем счётчик ответов и время последнего ответа
        UPDATE unknown
        SET
            response_count = COALESCE(response_count, 0) + 1,
            last_response_at = NOW(),
            data = CASE
                WHEN data = '' OR data IS NULL THEN new_data
                ELSE data
            END
        WHERE uuid = nearest_record.uuid;

        -- Возвращаем данные
        result_uuid := nearest_record.uuid;
        action_type := 'found';
        distance := nearest_record.dist;
        RETURN;
    END IF;

    -- Вставляем новую запись
    INSERT INTO unknown (
        uuid,
        data,
        embedding,
        created_at,
        last_response_at,
        response_count
    )
    VALUES (
        new_uuid,
        new_data,
        search_vector,
        NOW(),
        NOW(),
        1
    )
    RETURNING uuid INTO result_uuid;

    action_type := 'created';

    -- Возвращаем расстояние до ближайшей записи (если есть)
    IF nearest_record.uuid IS NOT NULL THEN
        distance := nearest_record.dist;
    ELSE
        distance := NULL;
    END IF;

    RETURN;
END;
$body$;

ALTER FUNCTION public.find_or_create_by_vector(text, vector, double precision, text, OUT text, OUT text, OUT double precision) OWNER TO postgres;

-- ========== Функция поиска похожих лиц среди известных персон ==========
CREATE OR REPLACE FUNCTION public.find_similar_faces(
    p_user_id varchar,
    p_embedding public.vector,
    p_limit integer DEFAULT 3
)
RETURNS TABLE (
    user_id varchar,
    person_id varchar,
    photo_id varchar,
    description text,
    tag varchar,
    distance double precision
)
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
PARALLEL UNSAFE
COST 100 ROWS 1000
AS $body$
BEGIN
    RETURN QUERY
    SELECT
        pe.user_id::VARCHAR,
        pe.person_id::VARCHAR,
        ph.photo_id::VARCHAR,
        pe.description::TEXT,
        pe.tag::VARCHAR,
        (ph.vector <-> p_embedding)::FLOAT AS distance
    FROM
        public.photo ph
    JOIN
        public.percone pe USING(person_id)
    WHERE
        pe.user_id = p_user_id
        AND pe.view_percone
        AND ph.view_photo
        AND ph.vector IS NOT NULL
    ORDER BY
        distance
    LIMIT
        p_limit;
END;
$body$;

ALTER FUNCTION public.find_similar_faces(varchar, public.vector, integer) OWNER TO postgres;