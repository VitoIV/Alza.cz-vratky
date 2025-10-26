-- PostgreSQL schema for Return Intelligence Suite
CREATE TABLE IF NOT EXISTS teams (
    id SERIAL PRIMARY KEY,
    key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    prompt_definition TEXT,
    position INT DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    team_id INT REFERENCES teams(id) ON DELETE CASCADE,
    key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    prompt_definition TEXT,
    position INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS root_causes (
    id SERIAL PRIMARY KEY,
    team_id INT REFERENCES teams(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE CASCADE,
    key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT,
    prompt_definition TEXT,
    position INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS glossary_entries (
    id SERIAL PRIMARY KEY,
    phrase TEXT NOT NULL,
    normalized_phrase TEXT NOT NULL,
    language TEXT DEFAULT 'unknown',
    threshold NUMERIC(4,2) DEFAULT 0.90,
    team_id INT REFERENCES teams(id),
    category_id INT REFERENCES categories(id),
    root_cause_id INT REFERENCES root_causes(id),
    actionable_flag BOOLEAN DEFAULT TRUE,
    notes TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS batches (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    filename TEXT,
    status TEXT DEFAULT 'queued',
    status_message TEXT,
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    actionable_records INT DEFAULT 0,
    backoff_step INT DEFAULT 0,
    backoff_until TIMESTAMP WITH TIME ZONE,
    total_prompt_tokens BIGINT DEFAULT 0,
    total_completion_tokens BIGINT DEFAULT 0,
    total_latency_ms DOUBLE PRECISION DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS batch_records (
    id SERIAL PRIMARY KEY,
    batch_id INT REFERENCES batches(id) ON DELETE CASCADE,
    rma TEXT,
    product_code TEXT,
    seo_prefix TEXT,
    requested_at TEXT,
    product_name TEXT,
    issue_text TEXT,
    language TEXT,
    hash_key TEXT,
    status TEXT DEFAULT 'pending',
    actionable_flag BOOLEAN,
    team_id INT REFERENCES teams(id),
    category_id INT REFERENCES categories(id),
    root_cause_id INT REFERENCES root_causes(id),
    recommended_action TEXT,
    classification_source TEXT,
    classification_json JSONB,
    prompt_tokens INT DEFAULT 0,
    completion_tokens INT DEFAULT 0,
    latency_ms DOUBLE PRECISION,
    needs_review BOOLEAN DEFAULT FALSE,
    error_message TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS proposals (
    id SERIAL PRIMARY KEY,
    batch_record_id INT REFERENCES batch_records(id) ON DELETE CASCADE,
    type TEXT NOT NULL,
    suggestion_key TEXT,
    suggestion_name TEXT,
    suggestion_description TEXT,
    suggestion_prompt_definition TEXT,
    confidence NUMERIC(4,2) DEFAULT 0.50,
    status TEXT DEFAULT 'pending',
    resolved_entity_id INT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS issues (
    id SERIAL PRIMARY KEY,
    batch_id INT REFERENCES batches(id) ON DELETE CASCADE,
    actionable_flag BOOLEAN DEFAULT FALSE,
    team_id INT REFERENCES teams(id),
    category_id INT REFERENCES categories(id),
    root_cause_id INT REFERENCES root_causes(id),
    recommended_action TEXT,
    total_records INT DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS issue_records (
    issue_id INT REFERENCES issues(id) ON DELETE CASCADE,
    batch_record_id INT REFERENCES batch_records(id) ON DELETE CASCADE,
    PRIMARY KEY (issue_id, batch_record_id)
);

CREATE OR REPLACE FUNCTION touch_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_touch_teams BEFORE UPDATE ON teams FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER trg_touch_categories BEFORE UPDATE ON categories FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER trg_touch_root_causes BEFORE UPDATE ON root_causes FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER trg_touch_glossary BEFORE UPDATE ON glossary_entries FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER trg_touch_batches BEFORE UPDATE ON batches FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
CREATE TRIGGER trg_touch_batch_records BEFORE UPDATE ON batch_records FOR EACH ROW EXECUTE FUNCTION touch_updated_at();
