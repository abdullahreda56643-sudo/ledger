USE ledger;

INSERT INTO parties (name, phone, kind, notes) VALUES
('أبو علي', '01012345678', 'customer', NULL),
('مطعم النيل', '01023456789', 'customer', NULL),
('الحاج سعيد', '01034567890', 'supplier', NULL),
('عم محمود', '01045678901', 'both', NULL),
('الشيخ فتحي', '01056789012', 'customer', NULL);

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'opening', 'debit', NULL, NULL, 1250000, 'رصيد مرحل', 1
FROM parties p WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'sell', 'debit', 18.000, 32500, 585000, NULL, 1
FROM parties p WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'pay', 'credit', 300000, NULL, 1
FROM parties p WHERE p.phone = '01012345678';

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'supply', 'credit', 42.500, 29800, 1266500, NULL, 1
FROM parties p WHERE p.phone = '01045678901';

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'sell', 'debit', 23.000, 31000, 713000, NULL, 1
FROM parties p WHERE p.phone = '01023456789';

INSERT INTO transactions (party_id, tx_date, type, direction, amount, note, created_by)
SELECT p.id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pay_supplier', 'debit', 850000, 'دفعة للمورد', 1
FROM parties p WHERE p.phone = '01034567890';

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, CURDATE(), 'supply', 'credit', 31.250, 31200, 975000, NULL, 1
FROM parties p WHERE p.phone = '01034567890';

INSERT INTO transactions (party_id, tx_date, type, direction, weight, unit_price, amount, note, created_by)
SELECT p.id, CURDATE(), 'sell', 'debit', 15.500, 33000, 511500, NULL, 1
FROM parties p WHERE p.phone = '01056789012';