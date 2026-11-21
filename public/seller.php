<?php
session_start();

// Простой вход в кабинет продавца (пароль: 1234)
if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === '1234') {
        $_SESSION['is_seller'] = true;
        $_SESSION['seller_name'] = trim($_POST['seller_name']) ?: 'Частный продавец';
    } else {
        $error = 'Неверный пароль продавца!';
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['is_seller'], $_SESSION['seller_name']);
    header('Location: seller.php');
    exit;
}

$file = 'products.json';
$customProducts = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
if (!is_array($customProducts)) $customProducts = [];

// Добавление нового товара
if (isset($_SESSION['is_seller']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name     = trim($_POST['name']);
    $category = $_POST['category'];
    $price    = (int)$_POST['price'];
    $size     = trim($_POST['size']);
    $seller   = $_SESSION['seller_name'];

    // Загрузка картинки в папку img/
    $imagePath = 'img/futbolka.jpg';
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $newName = 'img/' . uniqid('item_') . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $newName)) {
                $imagePath = $newName;
            }
        }
    }

    // Генерируем новый ID (начиная со 100, чтобы не пересекаться с базовыми)
    $newId = empty($customProducts) ? 100 : (max(array_keys($customProducts)) + 1);

    $customProducts[$newId] = [
        'name'     => $name,
        'category' => $category,
        'price'    => $price,
        'size'     => $size,
        'image'    => $imagePath,
        'seller'   => $seller
    ];

    file_put_contents($file, json_encode($customProducts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    $success = 'Товар успешно опубликован на главной странице A.S.98!';
}

// Удаление товара продавцом
if (isset($_SESSION['is_seller']) && isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if (isset($customProducts[$delId])) {
        unset($customProducts[$delId]);
        file_put_contents($file, json_encode($customProducts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    header('Location: seller.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Кабинет продавца — A.S.98</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; color: #222; }
        header { background: #111; color: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; margin-left: 20px; }
        .logo { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .box { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 25px; }
        input, select { width: 100%; padding: 10px; margin: 8px 0 15px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #111; color: #fff; border: none; padding: 12px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .btn:hover { background: #333; }
        .btn-del { background: #c0392b; padding: 6px 12px; font-size: 13px; }
        .alert { padding: 12px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 15px; }
        .error { padding: 12px; background: #f8d7da; color: #721c24; border-radius: 5px; margin-bottom: 15px; }
        .item-row { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 10px 0; }
        .item-row img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
    </style>
</head>
<body>

<header>
    <div class="logo">A.S.98 • SELLER</div>
    <nav>
        <a href="index.php">← На витрину</a>
        <?php if (!empty($_SESSION['is_seller'])): ?>
            <a href="seller.php?logout=1">Выйти (<?= htmlspecialchars($_SESSION['seller_name']) ?>)</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <?php if (empty($_SESSION['is_seller'])): ?>
        <div class="box" style="max-width: 400px; margin: 50px auto;">
            <h2>Вход для продавцов</h2>
            <?php if (!empty($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
            <form method="POST">
                <label>Название вашего магазина или имя:</label>
                <input type="text" name="seller_name" placeholder="Например: Vintage Shop" required>
                <label>Пароль доступа (по умолчанию: 1234):</label>
                <input type="password" name="login_pass" placeholder="Введите 1234" required>
                <button class="btn" type="submit" style="width:100%;">Войти в кабинет</button>
            </form>
        </div>
    <?php else: ?>
        <div class="box">
            <h2>Добавить новый товар в каталог A.S.98</h2>
            <?php if (!empty($success)): ?><div class="alert"><?= $success ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_item" value="1">
                
                <label>Название вещи:</label>
                <input type="text" name="name" placeholder="Например: Кожаная куртка косуха" required>

                <label>Категория:</label>
                <select name="category">
                    <option value="Футболки">Футболки</option>
                    <option value="Толстовки">Толстовки</option>
                    <option value="Брюки">Брюки</option>
                    <option value="Рубашки">Рубашки</option>
                    <option value="Верхняя одежда">Верхняя одежда</option>
                    <option value="Обувь">Обувь</option>
                </select>

                <label>Доступные размеры:</label>
                <input type="text" name="size" placeholder="Например: S, M, L" required>

                <label>Цена (₽):</label>
                <input type="number" name="price" placeholder="Например: 7500" required>

                <label>Фотография товара (JPG, PNG, WEBP):</label>
                <input type="file" name="photo" accept="image/*" required>

                <button class="btn" type="submit">Опубликовать товар</button>
            </form>
        </div>

        <div class="box">
            <h3>Добавленные товары продавцов (<?= count($customProducts) ?>)</h3>
            <?php if (empty($customProducts)): ?>
                <p style="color:#777;">Пока не добавлено ни одного нового товара.</p>
            <?php else: ?>
                <?php foreach ($customProducts as $id => $p): ?>
                    <div class="item-row">
                        <div style="display:flex; align-items:center;">
                            <img src="<?= htmlspecialchars($p['image']) ?>" alt="">
                            <div>
                                <b><?= htmlspecialchars($p['name']) ?></b> — <?= number_format($p['price'], 0, '', ' ') ?> ₽<br>
                                <small style="color:#666;">Продавец: <?= htmlspecialchars($p['seller'] ?? 'Продавец') ?> | Размеры: <?= htmlspecialchars($p['size']) ?></small>
                            </div>
                        </div>
                        <a class="btn btn-del" href="seller.php?delete=<?= $id ?>">Удалить</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
