CREATE TABLE IF NOT EXISTS imports (
    id TEXT PRIMARY KEY,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    size_bytes INTEGER NOT NULL,
    sha256 TEXT NOT NULL UNIQUE,
    kind TEXT NOT NULL,
    status TEXT NOT NULL,
    encoding TEXT,
    period_start TEXT,
    period_end TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS efd_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_id TEXT NOT NULL,
    line_number INTEGER NOT NULL,
    record_type TEXT NOT NULL,
    fields_json TEXT NOT NULL,
    raw_base64 TEXT NOT NULL,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    UNIQUE (import_id, line_number)
);

CREATE TABLE IF NOT EXISTS import_issues (
    id TEXT PRIMARY KEY,
    import_id TEXT NOT NULL,
    severity TEXT NOT NULL,
    code TEXT NOT NULL,
    message TEXT NOT NULL,
    location TEXT,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_efd_records_import_type
    ON efd_records (import_id, record_type);

CREATE INDEX IF NOT EXISTS idx_import_issues_import
    ON import_issues (import_id);
