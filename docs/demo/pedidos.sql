-- Tabela de exemplo para integração local (módulo Pedidos).
-- Executar no banco da aplicação (ex.: mystique) ou em banco dedicado.

CREATE TABLE IF NOT EXISTS demo_pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente VARCHAR(255) NOT NULL,
    valor_total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    status VARCHAR(64) NOT NULL DEFAULT 'Inputar',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO demo_pedidos (cliente, valor_total, status) VALUES
    ('Acme Ltda', 1500.00, 'Inputar'),
    ('Beta Corp', 3200.50, 'Inputar'),
    ('Cliente Especial', 890.00, 'Em Andamento'),
    ('Delta SA', 4100.00, 'Aprovados')
ON DUPLICATE KEY UPDATE cliente = VALUES(cliente);
