USE ledger;

INSERT INTO parties (name, phone, kind, notes) VALUES
('أبو علي', '01012345678', 'customer', NULL),
('مطعم النيل', '01023456789', 'customer', NULL),
('الحاج سعيد', '01034567890', 'supplier', NULL),
('عم محمود', '01045678901', 'both', NULL),
('الشيخ فتحي', '01056789012', 'customer', NULL);

INSERT INTO transactions (party_id, tx_date, type, direction, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'opening', 'debit', NULL, NULL, 1250000, 'رصيد مرحل', 1
FROM parties p WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, quantity, unit_id, unit_symbol, base_quantity, base_unit_symbol, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'sell', 'debit',
       18.000, u.id, u.symbol, 18.000, c.base_unit_symbol, 32500, 585000, NULL, 1
FROM parties p
JOIN units u ON u.category_code = 'mass' AND u.is_default = 1 AND u.is_active = 1
JOIN unit_categories c ON c.code = u.category_code
WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'pay', 'credit', 300000, NULL, 1
FROM parties p WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, quantity, unit_id, unit_symbol, base_quantity, base_unit_symbol, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'supply', 'credit',
       42.500, u.id, u.symbol, 42.500, c.base_unit_symbol, 29800, 1266500, NULL, 1
FROM parties p
JOIN units u ON u.category_code = 'mass' AND u.is_default = 1 AND u.is_active = 1
JOIN unit_categories c ON c.code = u.category_code
WHERE p.phone = '01045678901';

INSERT INTO transactions (party_id, tx_date, type, direction, quantity, unit_id, unit_symbol, base_quantity, base_unit_symbol, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'sell', 'debit',
       23.000, u.id, u.symbol, 23.000, c.base_unit_symbol, 31000, 713000, NULL, 1
FROM parties p
JOIN units u ON u.category_code = 'mass' AND u.is_default = 1 AND u.is_active = 1
JOIN unit_categories c ON c.code = u.category_code
WHERE p.phone = '01023456789';

INSERT INTO transactions (party_id, tx_date, type, direction, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pay_supplier', 'debit', 850000, 'دفعة للمورد', 1
FROM parties p WHERE p.phone = '01034567890';

INSERT INTO transactions (party_id, tx_date, type, direction, quantity, unit_id, unit_symbol, base_quantity, base_unit_symbol, unit_price, amount, note, created_by)
SELECT p.id, CURDATE(), 'supply', 'credit',
       31.250, u.id, u.symbol, 31.250, c.base_unit_symbol, 31200, 975000, NULL, 1
FROM parties p
JOIN units u ON u.category_code = 'mass' AND u.is_default = 1 AND u.is_active = 1
JOIN unit_categories c ON c.code = u.category_code
WHERE p.phone = '01034567890';

INSERT INTO transactions (party_id, tx_date, type, direction, quantity, unit_id, unit_symbol, base_quantity, base_unit_symbol, unit_price, amount, note, created_by)
SELECT p.id, CURDATE(), 'sell', 'debit',
       15.500, u.id, u.symbol, 15.500, c.base_unit_symbol, 33000, 511500, NULL, 1
FROM parties p
JOIN units u ON u.category_code = 'mass' AND u.is_default = 1 AND u.is_active = 1
JOIN unit_categories c ON c.code = u.category_code
WHERE p.phone = '01056789012';