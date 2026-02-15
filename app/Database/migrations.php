<?php

$schema = [
    // 1. csr_options: Certificate Signing Request metadata
    "CREATE TABLE IF NOT EXISTS csr_options (
        id                      INTEGER PRIMARY KEY AUTOINCREMENT,
        common_name             TEXT NOT NULL,
        organization_identifier TEXT NOT NULL, -- vat number
        organization_name       TEXT NOT NULL,
        organization_unit       TEXT NOT NULL,
        country                 TEXT NOT NULL,
        address                 TEXT NOT NULL,
        business_category       TEXT NOT NULL,
        invoice_type            TEXT NOT NULL,
        egs_solution_name       TEXT NOT NULL,
        egs_model               TEXT NOT NULL,
        egs_serial_number       TEXT NOT NULL,
        user_id                 INTEGER NOT NULL,
        
        deleted_at              DATETIME DEFAULT NULL,
        created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at              DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

    // 2. csr_compliance: Result of Compliance CSID issuance
    "CREATE TABLE IF NOT EXISTS csr_compliance (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id                     INTEGER NOT NULL,
        vat_id                      TEXT NOT NULL,
        csr_options_id              INTEGER NOT NULL,
        request_id                  INTEGER NOT NULL UNIQUE,
        csr_base64                  TEXT NOT NULL,
        private_key_base64          TEXT NOT NULL,
        binary_security_token       TEXT NOT NULL,
        secret                      TEXT NOT NULL,

        standard_compliant                  INTEGER NOT NULL DEFAULT 0,
        standard_credit_note_compliant      INTEGER NOT NULL DEFAULT 0,
        standard_debit_note_compliant       INTEGER NOT NULL DEFAULT 0,
        simplified_compliant                INTEGER NOT NULL DEFAULT 0,
        simplified_credit_note_compliant    INTEGER NOT NULL DEFAULT 0,
        simplified_debit_note_compliant     INTEGER NOT NULL DEFAULT 0,

        deleted_at                  DATETIME DEFAULT NULL,
        created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY(csr_options_id) REFERENCES csr_options(id)
    )",

    // 3. csr_production: Result of Production CSID issuance
    "CREATE TABLE IF NOT EXISTS csr_production (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id                     INTEGER NOT NULL,
        vat_id                      TEXT NOT NULL,
        csr_options_id              INTEGER NOT NULL,
        csr_compliance_id           INTEGER NOT NULL, 
        csr_base64                  TEXT NOT NULL,

        request_id                  INTEGER NOT NULL UNIQUE,
        binary_security_token       TEXT NOT NULL,
        secret                      TEXT NOT NULL,

        deleted_at                  DATETIME DEFAULT NULL,
        created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY(csr_options_id) REFERENCES csr_options(id),
        FOREIGN KEY(csr_compliance_id) REFERENCES csr_compliance(id)
    )",

    "CREATE TABLE IF NOT EXISTS compliance_invoices (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id                     INTEGER NOT NULL,
        request_id                  INTEGER NOT NULL,
        vat_id                      VARCHAR(16)    NOT NULL,
        invoice_id                  VARCHAR(50)    NOT NULL,           -- ZATCA-required unique invoice number
        hash                        VARCHAR(150)    NOT NULL UNIQUE,    -- drop UNIQUE here
        uuid                        VARCHAR(50)    NOT NULL UNIQUE,    -- ZATCA-required UUID
        note                        TEXT,
        note_language_id            INTEGER NOT NULL,
        issue_date                  DATE    NOT NULL,
        issue_time                  TIME    NOT NULL,
        simplified                  INTEGER NOT NULL DEFAULT 0,
        sub_type                    TEXT    NOT NULL,
        payment_means_code          VARCHAR(10)    NOT NULL,
        invoice_type                VARCHAR(10)    NOT NULL,
        currency_code               VARCHAR(3)    NOT NULL,
        accounting_supplier_party   TEXT    NOT NULL,
        accounting_customer_party   TEXT    NOT NULL,
        delivery                    TEXT,
        allowance_charges           TEXT    NOT NULL,
        tax_totals                  TEXT    NOT NULL,
        legal_monetary_total        TEXT    NOT NULL,
        invoice_lines               TEXT    NOT NULL,
        instruction_note            TEXT,
        billing_reference           TEXT,
        base64_encoded              TEXT    NOT NULL,
        base64_qr                   TEXT    NOT NULL,

        deleted_at                  DATETIME DEFAULT NULL,
        created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        UNIQUE(invoice_id, user_id)
    )",

    "CREATE TABLE IF NOT EXISTS invoices (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id                     INTEGER NOT NULL,
        request_id                  INTEGER NOT NULL,
        vat_id                      VARCHAR(16)    NOT NULL,
        invoice_id                  VARCHAR(50)    NOT NULL,           -- ZATCA-required unique invoice number
        hash                        VARCHAR(150)    NOT NULL UNIQUE,    -- drop UNIQUE here
        uuid                        VARCHAR(50)    NOT NULL UNIQUE,    -- ZATCA-required UUID
        note                        TEXT,
        note_language_id            INTEGER NOT NULL,
        issue_date                  DATE    NOT NULL,
        issue_time                  TIME    NOT NULL,
        simplified                  INTEGER NOT NULL DEFAULT 0,
        sub_type                    TEXT    NOT NULL,
        payment_means_code          VARCHAR(10)    NOT NULL,
        invoice_type                VARCHAR(10)    NOT NULL,
        currency_code               VARCHAR(3)    NOT NULL,
        accounting_supplier_party   TEXT    NOT NULL,
        accounting_customer_party   TEXT    NOT NULL,
        delivery                    TEXT,
        allowance_charges           TEXT    NOT NULL,
        tax_totals                  TEXT    NOT NULL,
        legal_monetary_total        TEXT    NOT NULL,
        instruction_note            TEXT,
        billing_reference           TEXT,
        bucket_key                  VARCHAR(255) DEFAULT NULL,

        deleted_at                  DATETIME DEFAULT NULL,
        created_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                  DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        UNIQUE(invoice_id, user_id)
    )",

    "CREATE TABLE IF NOT EXISTS invoice_lines (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id                  INTEGER NOT NULL,
        invoiced_quantity           REAL    NOT NULL,
        data                       TEXT    NOT NULL,
    
        created_at                 DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                 DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS invoice_data (
        id                          INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id                  INTEGER NOT NULL,           -- Foreign key to invoices table
        base64_encoded              TEXT    NOT NULL,
        base64_qr                   TEXT    NOT NULL,
        zatca_response              TEXT    NOT NULL,
    
        created_at                 DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at                 DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
    )",

    // 6. users
    "CREATE TABLE IF NOT EXISTS users (
        id                 INTEGER PRIMARY KEY AUTOINCREMENT,
        email              VARCHAR(50)    NOT NULL UNIQUE,
        password_hash      VARCHAR(255)    NOT NULL,
        is_confirmed       INTEGER NOT NULL DEFAULT 0,
        confirmation_token TEXT DEFAULT NULL,
        active             INTEGER NOT NULL DEFAULT 1,
        api_key            VARCHAR(255) UNIQUE DEFAULT NULL,
        expire_at          DATETIME NOT NULL DEFAULT '2099-12-31T23:59:59',
        last_login_at      DATETIME DEFAULT NULL,
        deleted_at         DATETIME DEFAULT NULL,
        created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at         DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS whitelists (
        id        INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id   INTEGER NOT NULL,
        type      VARCHAR(10)    NOT NULL CHECK(type IN ('ip','domain')),
        value     VARCHAR(50)    NOT NULL,
        created_at TEXT   DEFAULT (datetime('now')) NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS subscriptions (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id        INTEGER NOT NULL,
        plan_id        INTEGER NOT NULL,
        starts_at      DATETIME NOT NULL,
        ends_at        DATETIME NOT NULL,
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(plan_id) REFERENCES plans(id)
    )",

    "CREATE TABLE IF NOT EXISTS plans (
        id              INTEGER PRIMARY KEY AUTOINCREMENT,
        name            VARCHAR(100)    NOT NULL UNIQUE,
        price           REAL    NOT NULL,
        duration_days   INTEGER NOT NULL,
        description     TEXT,
        deleted_at      DATETIME DEFAULT NULL,
    
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS user_logins (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id     INTEGER NOT NULL,
        ip          TEXT,
        user_agent  TEXT,
        browser     TEXT,
        os          TEXT,
        device      TEXT,
        city        TEXT,
        region      TEXT,
        country     TEXT,
        zip         TEXT,
        lat         REAL,
        lon         REAL,
        timezone    TEXT,
        isp         TEXT,
        org         TEXT,
        as_info     TEXT,
        login_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

return $schema;
