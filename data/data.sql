-- Disable foreign key checks temporarily (if there are foreign key constraints)
SET foreign_key_checks = 0;

-- Delete all data from the tables (Reset data, delete existing records)
DELETE FROM `inventory`;
DELETE FROM `item_types`;
DELETE FROM `categories`;
DELETE FROM `warehouses`;

-- Reset primary key (auto-increment) for each table
ALTER TABLE `inventory` AUTO_INCREMENT = 1;
ALTER TABLE `item_types` AUTO_INCREMENT = 1;
ALTER TABLE `categories` AUTO_INCREMENT = 1;
ALTER TABLE `warehouses` AUTO_INCREMENT = 1;

-- Re-enable foreign key checks
SET foreign_key_checks = 1;

-- Insert Warehouses
INSERT INTO `warehouses` (`name`, `description`, `location`)
VALUES
    ('Warehouse Vinohrady', 'Located in the vibrant Vinohrady district of Prague.', 'Praha'),
    ('Warehouse Žižkov', 'A medium-sized warehouse in the Žižkov neighborhood.', 'Praha'),
    ('Warehouse Karlín', 'Modern warehouse in the Karlín area, close to the city center.', 'Praha'),
    ('Warehouse Smíchov', 'Large warehouse in the Smíchov district, ideal for logistics.', 'Praha'),
    ('Warehouse Holešovice', 'Warehouse in the industrial Holešovice area.', 'Praha'),
    ('Warehouse Brno', 'Warehouse located in Brno, the second-largest city in Czechia.', 'Brno'),
    ('Warehouse Ostrava', 'Warehouse in Ostrava, a major industrial hub.', 'Ostrava'),
    ('Warehouse Plzeň', 'Warehouse in Plzeň, known for its beer production.', 'Plzeň'),
    ('Warehouse Liberec', 'Small warehouse in Liberec, near the German border.', 'Liberec'),
    ('Warehouse Olomouc', 'Warehouse in Olomouc, a historic city in Moravia.', 'Olomouc');


-- Insert Categories (Parent Categories)
INSERT INTO `categories` (`name`, `description`, `parent_id`)
VALUES
    ('Cables', 'All the cables', NULL),
    ('Charging cables', 'All the charging cables', 1),
    ('Data cables', 'All the data cables', 1),
    ('Audio cables', 'All the audio cables', 1),
    ('Video cables', 'All the video cables', 1),
    ('Adapters & Converters', 'Adapters and converters for cables', 1),
    ('Power cables', 'Cables for powering devices', 1),
    ('Networking cables', 'Cables for networking', 1),
    ('Smartphone cables', 'Cables for smartphones', 1);

-- Insert Item Types (with appropriate category_id values)
INSERT INTO `item_types` (`name`, `description`, `weight`, `size_x`, `size_y`, `size_z`, `category_id`)
VALUES
    ('Apple USB-C nabíjecí kabel 2m',
     'Datový kabel pro iPad Air (4. generace) a MacBook či jiná zařízení s USB-C portem, napájecí funkce při využití nabíječky', 20.00, 15.00, 15.00, 15.00, 2),
    ('USB-A to USB-C Cable 1.5m',
     'USB-A to USB-C charging and data transfer cable', 18.00, 14.00, 14.00, 14.00, 2),
    ('HDMI to HDMI Cable 3m',
     'High-quality HDMI cable for 4K video transmission', 35.00, 20.00, 20.00, 20.00, 4),
    ('3.5mm Audio Cable 1m',
     'Standard 3.5mm audio cable for headphones or audio devices', 10.00, 12.00, 12.00, 12.00, 3),
    ('Ethernet Cable Cat6 10m',
     'High-speed Ethernet cable for networking', 50.00, 18.00, 18.00, 18.00, 8),
    ('USB-C to Lightning Cable 2m',
     'USB-C to Lightning charging cable for Apple devices', 20.00, 15.00, 15.00, 15.00, 2),
    ('VGA to HDMI Converter Cable',
     'Adapter cable for converting VGA to HDMI for video output', 25.00, 18.00, 18.00, 18.00, 5),
    ('Power Extension Cable 5m',
     'Power extension cable for household appliances', 40.00, 20.00, 20.00, 20.00, 7),
    ('USB-A to Micro USB Cable 1m',
     'Charging and data transfer cable for older Android devices', 15.00, 12.00, 12.00, 12.00, 2),
    ('USB-C to USB-C Cable 3m',
     'USB-C cable for charging and data transfer', 22.00, 15.00, 15.00, 15.00, 2);

-- Insert Inventory Data (Randomized distribution across warehouses 1-9 for each item type)
INSERT INTO `inventory` (`item_id`, `warehouse_id`, `quantity`)
VALUES
    (1, 1, 50),  -- Apple USB-C nabíjecí kabel 2m
    (2, 2, 100),  -- USB-A to USB-C Cable 1.5m
    (3, 3, 150),  -- HDMI to HDMI Cable 3m
    (4, 4, 200),  -- 3.5mm Audio Cable 1m
    (5, 5, 250),  -- Ethernet Cable Cat6 10m
    (6, 6, 300),  -- USB-C to Lightning Cable 2m
    (7, 7, 400),  -- VGA to HDMI Converter Cable
    (8, 8, 500),  -- Power Extension Cable 5m
    (9, 9, 600),  -- USB-A to Micro USB Cable 1m
    (10, 1, 700);  -- USB-C to USB-C Cable 3m

