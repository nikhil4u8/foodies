USE foodies;

INSERT INTO users (name, email, password, role) VALUES
  ('Demo Owner',    'owner@foodies.test',    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyDPBW6dOaTAWO', 'owner'),
  ('Demo Customer', 'customer@foodies.test', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewKyDPBW6dOaTAWO', 'customer');

INSERT INTO restaurants (owner_id, name, description, address) VALUES
  (LAST_INSERT_ID() - 1, 'Punjab Dhaba', 'Home-style North Indian cooking', 'Sector 17, Chandigarh');

SET @r = LAST_INSERT_ID();

INSERT INTO menu_items (restaurant_id, name, description, category, price, is_available) VALUES
  (@r, 'Butter Chicken',  'Creamy tomato curry, boneless',  'Mains',     320.00, 1),
  (@r, 'Dal Makhani',     'Slow-cooked black lentils',      'Mains',     240.00, 1),
  (@r, 'Paneer Tikka',    'Charred spiced cottage cheese',  'Starters',  260.00, 1),
  (@r, 'Garlic Naan',     'Fresh from the tandoor',         'Breads',     70.00, 1),
  (@r, 'Jeera Rice',      'Cumin-tempered basmati',         'Rice',      150.00, 1),
  (@r, 'Gulab Jamun',     'Two pieces, served warm',        'Desserts',  120.00, 1);