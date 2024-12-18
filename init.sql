CREATE TABLE IF NOT EXISTS urls (
     ID INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1 ),
    address VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL
    );

CREATE TABLE IF NOT EXISTS url_checks (
     ID INTEGER  NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1 ),
     url_id INTEGER NOT NULL,
     status_code INTEGER,
     h1 VARCHAR(255),
     title TEXT,
     description TEXT,
     created_at TIMESTAMP NOT NULL
);
