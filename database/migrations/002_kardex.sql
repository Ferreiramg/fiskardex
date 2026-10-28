CREATE TABLE IF NOT EXISTS kardex_runs (
    id TEXT PRIMARY KEY,
    import_id TEXT NOT NULL REFERENCES imports(id) ON DELETE CASCADE,
    algorithm_version TEXT NOT NULL,
    source_sha256 TEXT NOT NULL,
    status TEXT NOT NULL,
    accepted_quantity_base TEXT NOT NULL DEFAULT '0',
    accepted_count INTEGER NOT NULL DEFAULT 0,
    exception_count INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    UNIQUE(import_id, algorithm_version)
);

CREATE TABLE IF NOT EXISTS kardex_movements (
    id TEXT PRIMARY KEY,
    run_id TEXT NOT NULL REFERENCES kardex_runs(id) ON DELETE CASCADE,
    import_id TEXT NOT NULL REFERENCES imports(id) ON DELETE CASCADE,
    source_line INTEGER NOT NULL,
    parent_c100_line INTEGER NOT NULL,
    item_number TEXT NOT NULL,
    product_code TEXT NOT NULL,
    movement_date TEXT,
    quantity_original TEXT NOT NULL,
    unit_original TEXT NOT NULL,
    quantity_base TEXT NOT NULL,
    unit_base TEXT NOT NULL,
    conversion_factor TEXT NOT NULL,
    item_value_original TEXT NOT NULL,
    source_record_base64 TEXT NOT NULL,
    parent_record_base64 TEXT NOT NULL,
    product_record_base64 TEXT NOT NULL,
    conversion_record_base64 TEXT,
    UNIQUE(run_id, source_line)
);

CREATE TABLE IF NOT EXISTS kardex_exceptions (
    id TEXT PRIMARY KEY,
    run_id TEXT NOT NULL REFERENCES kardex_runs(id) ON DELETE CASCADE,
    source_line INTEGER NOT NULL,
    parent_c100_line INTEGER,
    product_code TEXT,
    code TEXT NOT NULL,
    message TEXT NOT NULL,
    details_json TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_kardex_movements_product ON kardex_movements(run_id, product_code, source_line);
CREATE INDEX IF NOT EXISTS idx_kardex_exceptions_run ON kardex_exceptions(run_id, source_line);
