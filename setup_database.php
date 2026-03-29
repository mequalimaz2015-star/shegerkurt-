<?php
function get_env_val($key, $default = '') {
    return $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
}

$host = get_env_val('DB_HOST', 'localhost');
$user = get_env_val('DB_USER', 'root');
$pass = get_env_val('DB_PASS', '');
$db   = get_env_val('DB_NAME', 'sheger_kurt_db');

$db_url = get_env_val('DATABASE_URL');
if ($db_url) {
    $parts = parse_url($db_url);
    if ($parts) {
        $host = $parts['host'] ?? $host;
        $db   = ltrim($parts['path'] ?? '', '/') ?: $db;
        $user = $parts['user'] ?? $user;
        $pass = $parts['pass'] ?? $pass;
    }
}

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Select database or create if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    
    echo "Database '$db' ready.<br>";

    // Tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Explicitly drop tables if they are corrupted in the engine
    $tablesToDrop = [
        'users', 'activity_logs', 'recycle_bin', 'menu_items', 'reservations', 
        'employees', 'attendance', 'salary_advances', 'payroll', 'jobs', 
        'company_info', 'orders', 'favorites', 'chat_messages', 'chat_sessions'
    ];
    foreach ($tablesToDrop as $table) {
        $pdo->exec("DROP TABLE IF EXISTS $table");
    }
    
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100),
            email VARCHAR(100) UNIQUE,
            password VARCHAR(255),
            role VARCHAR(50) DEFAULT 'Admin',
            permissions TEXT,
            profile_pic VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action TEXT,
            admin_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS recycle_bin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            record_id INT,
            record_data TEXT,
            deleted_by VARCHAR(100),
            deletion_reason TEXT,
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            category VARCHAR(50),
            description TEXT,
            price DECIMAL(10,2),
            image_url VARCHAR(255),
            likes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_name VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(50),
            reservation_date DATE,
            reservation_time TIME,
            guests INT,
            table_number VARCHAR(20),
            message TEXT,
            status ENUM('Pending', 'Confirmed', 'Rejected') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS employees (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_number VARCHAR(20) UNIQUE,
            title VARCHAR(20),
            name VARCHAR(150),
            first_name VARCHAR(50),
            middle_name VARCHAR(50),
            last_name VARCHAR(50),
            role VARCHAR(50),
            salary DECIMAL(10,2),
            salary_type ENUM('Monthly', 'Daily', 'Hourly') DEFAULT 'Monthly',
            email VARCHAR(100),
            phone VARCHAR(50),
            address TEXT,
            emergency_contact_name VARCHAR(100),
            emergency_contact_phone VARCHAR(50),
            date_of_birth DATE,
            gender VARCHAR(20),
            join_date DATE,
            hire_date DATE,
            bio TEXT,
            photo VARCHAR(255),
            status ENUM('Active', 'Inactive', 'On Leave') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT,
            attendance_date DATE,
            check_in DATETIME,
            check_out DATETIME,
            status ENUM('Present', 'Absent', 'Late', 'Half Day') DEFAULT 'Present',
            late_minutes INT DEFAULT 0,
            work_hours DECIMAL(5,2) DEFAULT 0,
            overtime_hours DECIMAL(5,2) DEFAULT 0,
            notes TEXT,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS salary_advances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT,
            amount DECIMAL(10,2),
            advance_date DATE,
            reason TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS payroll (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT,
            salary_month VARCHAR(10),
            year INT,
            base_salary DECIMAL(10,2),
            bonus DECIMAL(10,2) DEFAULT 0,
            deductions DECIMAL(10,2) DEFAULT 0,
            net_salary DECIMAL(10,2),
            working_days INT,
            present_days DECIMAL(5,1),
            absent_days DECIMAL(5,1),
            late_count INT,
            total_overtime_hours DECIMAL(5,2),
            overtime_amount DECIMAL(10,2),
            advance_deduction DECIMAL(10,2),
            status ENUM('Pending', 'Paid') DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100),
            category VARCHAR(50),
            type VARCHAR(50),
            location VARCHAR(100),
            description TEXT,
            closing_date DATE,
            status ENUM('Open', 'Closed') DEFAULT 'Open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS company_info (
            id INT PRIMARY KEY,
            company_name VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(50),
            address TEXT,
            about_text TEXT,
            hero_title VARCHAR(255),
            hero_subtitle VARCHAR(255),
            hero_button_text VARCHAR(50),
            hero_image VARCHAR(255),
            hero_video VARCHAR(255),
            hero_audio VARCHAR(255),
            hero2_title VARCHAR(255),
            hero2_subtitle TEXT,
            hero2_button_text VARCHAR(50),
            hero2_image VARCHAR(255),
            hero3_title VARCHAR(255),
            hero3_subtitle TEXT,
            hero3_button_text VARCHAR(50),
            hero3_image VARCHAR(255),
            about_subtitle VARCHAR(255),
            about_image_main VARCHAR(255),
            about_image_sub1 VARCHAR(255),
            about_image_sub2 VARCHAR(255),
            history_title VARCHAR(255),
            history_text1 TEXT,
            history_text2 TEXT,
            dev_name VARCHAR(100),
            dev_email VARCHAR(100),
            dev_phone VARCHAR(50),
            dev_photo VARCHAR(255),
            copyright_text VARCHAR(255)
        )",
        "CREATE TABLE IF NOT EXISTS promo_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100),
            description TEXT,
            image_url VARCHAR(255),
            icon_svg TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(255),
            category VARCHAR(50),
            title VARCHAR(100),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS blogs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            author VARCHAR(100) DEFAULT 'Admin',
            content TEXT,
            image_url VARCHAR(255),
            category VARCHAR(50),
            created_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            role VARCHAR(100),
            feedback TEXT,
            rating INT,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_details TEXT,
            platform VARCHAR(50),
            status VARCHAR(50) DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_item_id INT,
            customer_email VARCHAR(100),
            dish_name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS restaurant_tables (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_name VARCHAR(100),
            description TEXT,
            image_url VARCHAR(255),
            capacity INT DEFAULT 4,
            status ENUM('Available', 'Reserved', 'Occupied') DEFAULT 'Available',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS payment_proofs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_phone VARCHAR(50),
            payment_method VARCHAR(50),
            transaction_ref VARCHAR(100) UNIQUE,
            proof_image VARCHAR(255),
            status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
            verified_by VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS chat_sessions (
            session_id VARCHAR(50) PRIMARY KEY,
            customer_name VARCHAR(100),
            customer_email VARCHAR(100),
            customer_phone VARCHAR(50),
            department VARCHAR(50) DEFAULT 'Restaurant',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(50),
            sender ENUM('User', 'Admin') DEFAULT 'User',
            message TEXT,
            image_path VARCHAR(255) DEFAULT NULL,
            location_lat VARCHAR(50) DEFAULT NULL,
            location_lng VARCHAR(50) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    // New columns check for versioning
    // Check if favorites has menu_item_id
    $check = $pdo->query("SHOW COLUMNS FROM favorites LIKE 'menu_item_id'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE favorites ADD COLUMN menu_item_id INT AFTER id");
        echo "Added menu_item_id to favorites table.<br>";
    }

    // Ensure chat_messages has all required columns
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lat VARCHAR(50) DEFAULT NULL");
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN IF NOT EXISTS location_lng VARCHAR(50) DEFAULT NULL");
        echo "Chat table columns verified.<br>";
    } catch (PDOException $e) {
        echo "Chat column check: " . htmlspecialchars($e->getMessage()) . "<br>";
    }

    // Seed Restaurant Tables
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurant_tables");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $r_tables = [
            ['Table 1 - Traditional', 'Two set ground, traditional Mesob seating for an authentic experience. Perfect for traditional kurt lovers.', './assets/images/table_1_mesob.png', 4],
            ['Table 2 - Skyline', 'Night view of the breathtaking city skyline, perfect for romantic dinners and evening drinks.', './assets/images/banner-1.jpg', 2],
            ['Table 3 - Friendship', 'Spacious social table for friendship, family gatherings, and celebrations. Large and comfortable.', './assets/images/banner-2.jpg', 8]
        ];
        $rt_stmt = $pdo->prepare("INSERT INTO restaurant_tables (table_name, description, image_url, capacity) VALUES (?, ?, ?, ?)");
        foreach ($r_tables as $tb) $rt_stmt->execute($tb);
        echo "Restaurant tables seeded.<br>";
    }

    // Check if menu_items has likes
    $check = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'likes'");
    if (!$check->fetch()) {
        $pdo->exec("ALTER TABLE menu_items ADD COLUMN likes INT DEFAULT 0 AFTER price");
        echo "Added likes to menu_items table.<br>";
    }

    // Check company_info columns
    $cols = ['delivery_title', 'delivery_text', 'delivery_image', 'footer_text', 'opening_hours_1', 'opening_hours_2', 'opening_hours_3', 'facebook', 'twitter', 'instagram', 'footer_bg_image', 'delivery_rider_image'];
    foreach ($cols as $col) {
        $check = $pdo->query("SHOW COLUMNS FROM company_info LIKE '$col'");
        if (!$check->fetch()) {
            $pdo->exec("ALTER TABLE company_info ADD COLUMN $col TEXT");
            echo "Added $col to company_info table.<br>";
        }
    }


    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "All tables created successfully.<br>";
    
    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@shegerkurt.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)")
            ->execute(['Super Admin', 'admin@shegerkurt.com', $pass, 'Admin']);
        echo "Default admin user created.<br>";
    }
    
    // Insert initial company info if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM company_info WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO company_info (id, company_name, email, phone, address, about_text, hero_title, hero_subtitle, about_subtitle, hero_image, about_image_main, delivery_title, delivery_text, delivery_image) 
           VALUES (1, 'Sheger Kurt', 'info@shegerkurt.com', '+251 911 223344', 'Addis Ababa, Ethiopia', 
           'Experience the authentic taste of Ethiopian Kurt in the heart of the city.', 
           'Traditional Ethiopian Kurt & Bar!', 'Eat Sleep And', 'Sheger Kurt, Traditional Meat, and Best Bar in Town!', 
           './assets/images/hero-banner.png', './assets/images/about-banner.png', 'A Moments Of Delivered On Right Time & Place', 
           'The restaurants in Hangzhou also catered to many northern Chinese who had fled south from Kaifeng during the Jurchen invasion of the 1120s, while it is also known that many restaurants were run by families.', 
           './assets/images/delivery-banner-bg.png')")
            ->execute();
        echo "Initial company info inserted.<br>";
    }

    // Seed Menu Items as templates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $items = [
            // BAR (4)
            ['Special Beer Bucket', 'Bar', 450, 'Selection of cold premium local beers.', './assets/images/food-menu-1.png'],
            ['Premium Whiskey Shot', 'Bar', 150, 'Top-shelf whiskey served neat or on rocks.', './assets/images/food-menu-2.png'],
            ['House Red Wine', 'Bar', 250, 'Rich Ethiopian red wine with deep flavor.', './assets/images/food-menu-3.png'],
            ['Exotic Cocktail', 'Bar', 200, 'Freshly mixed signature house cocktail.', './assets/images/food-menu-4.png'],
            
            // KURT (4)
            ['Special Sheger Kurt', 'Kurt', 850, 'Premium raw beef served with traditional spices.', './assets/images/food-menu-5.png'],
            ['Traditional Meat Platter', 'Kurt', 1200, 'Assorted raw meat cuts for sharing.', './assets/images/food-menu-6.png'],
            ['Spicy Kurt Special', 'Kurt', 900, 'Hot and spicy premium beef delicacy.', './assets/images/hero-banner.png'],
            ['Fresh Raw Beef', 'Kurt', 750, 'Freshly prepared traditional Ethiopian meat.', './assets/images/about-banner.png'],
            
            // DRINKS (4)
            ['Fresh Mango Juice', 'Drinks', 80, 'Thick and fresh seasonal mango juice.', './assets/images/food-menu-1.png'],
            ['Traditional Tej', 'Drinks', 120, 'Authentic Ethiopian honey wine.', './assets/images/food-menu-2.png'],
            ['Ethiopian Coffee', 'Drinks', 50, 'Freshly roasted traditional stove coffee.', './assets/images/food-menu-3.png'],
            ['Sparkling Water', 'Drinks', 40, 'Chilled premium sparkling mineral water.', './assets/images/food-menu-4.png'],
            
            // TIBS (4)
            ['Sheger Special Tibs', 'Tibs', 550, 'Sautéed beef with onions and peppers.', './assets/images/food-menu-5.png'],
            ['Derek Tibs', 'Tibs', 600, 'Crunchy dry-fried beef with traditional spices.', './assets/images/food-menu-6.png'],
            ['Leku Tibs', 'Tibs', 580, 'Juicy and tender sautéed beef cubes.', './assets/images/blog-1.jpg'],
            ['Tibs Platter', 'Tibs', 1100, 'Large platter of assorted beef tibs.', './assets/images/blog-2.jpg']
        ];
        $istmt = $pdo->prepare("INSERT INTO menu_items (name, category, price, description, image_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) $istmt->execute($item);
        echo "Menu items seeded successfully.<br>";
    }

    // Seed Promo Items
    $pdo->exec("TRUNCATE TABLE promo_items");
    $promos = [
        ['Traditional Kurt Dish', 'Food is any substance consumed to provide nutritional support for an organism.', './assets/images/promo-1.png'],
        ['Soft Drinks', 'Refreshing beverages to accompany your meat.', './assets/images/promo-2.png'],
        ['Special Ethiopian Kurt', 'Food is any substance consumed to provide nutritional support.', './assets/images/promo-4.png'],
        ['Juicy Drinks Selection', 'Refreshing beverages to accompany your meat.', './assets/images/promo-5.png'],
        ['French Fry Selection', 'Crispy and delicious side dish for our guests.', './assets/images/promo-3.png']
    ];
    $pstmt = $pdo->prepare("INSERT INTO promo_items (title, description, image_url) VALUES (?, ?, ?)");
    foreach ($promos as $p) $pstmt->execute($p);
    echo "Promo items seeded successfully.<br>";

    // Seed Blogs as templates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM blogs");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $blogs = [
            ['What Do You Think About Traditional Kurt Recipes?', 'Jonathan Smith', 'Financial experts support or help you to find out which way you can raise your funds more...', './assets/images/blog-1.jpg', 'Kurt', '2022-01-01'],
            ['Making Chicken Strips With New Delicious Ingridents.', 'Jonathan Smith', 'Financial experts support or help you to find out which way you can raise your funds more...', './assets/images/blog-2.jpg', 'Bar', '2022-01-01'],
            ['Innovative Hot Chessyraw Pasta Make Creator Fact.', 'Jonathan Smith', 'Financial experts support or help you to find out which way you can raise your funds more...', './assets/images/blog-3.jpg', 'Chicken', '2022-01-01']
        ];
        $bstmt = $pdo->prepare("INSERT INTO blogs (title, author, content, image_url, category, created_date) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($blogs as $b) $bstmt->execute($b);
        echo "Blog templates imported.<br>";
    }

    echo "<h3>Setup complete!</h3><p><a href='admin.php'>Go to Admin Panel</a></p>";

} catch (PDOException $e) {
    die("<div style='font-family:sans-serif; text-align:center; margin-top:100px;'>
            <h1 style='color:#ef4444;'>No Database Connected!</h1>
            <p>Render is trying to setup your database, but it cannot find the MySQL Server.</p>
            <p><b>Error Details:</b> " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Because Render does not have a free MySQL database, you MUST create a free online MySQL database at <b>Aiven.io</b> or <b>RemoteMySQL.com</b> and add the credentials to Render's Environment Variables.</p>
            <br>
            <a href='setup_database.php' style='padding:12px 24px; background:#ff9d2d; color:#fff; font-weight:bold; text-decoration:none; border-radius:10px;'>Try Again</a>
          </div>");
}
?>
