<?php
session_start();

// База товаров (можно расширять)
$baseProducts = [
    1 => ['name' => 'Базовая футболка Oversize', 'category' => 'Футболки', 'price' => 2490, 'size' => 'S, M, L, XL', 'image' => 'img/futbolka.jpg', 'seller' => 'A.S.98 Official'],
    2 => ['name' => 'Худи из плотного футера', 'category' => 'Толстовки', 'price' => 5990, 'size' => 'M, L, XL', 'image' => 'img/cofta.jpg', 'seller' => 'A.S.98 Official'],
    3 => ['name' => 'Джинсы прямого кроя', 'category' => 'Брюки', 'price' => 6490, 'size' => '30, 32, 34', 'image' => 'img/djinci.jpg', 'seller' => 'A.S.98 Official'],
    4 => ['name' => 'Оверсайз-рубашка из хлопка', 'category' => 'Рубашки', 'price' => 4290, 'size' => 'S, M, L', 'image' => 'img/rubahca.jpg', 'seller' => 'A.S.98 Official'],
    5 => ['name' => 'Свитшот минимализм', 'category' => 'Толстовки', 'price' => 4890, 'size' => 'S, M, L, XL', 'image' => 'img/coftarr.jpg', 'seller' => 'A.S.98 Official'],
    6 => ['name' => 'Карго-брюки с карманами', 'category' => 'Брюки', 'price' => 5790, 'size' => 'M, L, XL', 'image' => 'img/djinccii.jpg', 'seller' => 'A.S.98 Official'],
];

// Подгружаем товары продавцов из файла products.json, если он уже создан
$customProducts = file_exists('products.json') ? json_decode(file_get_contents('products.json'), true) : [];
$products = $baseProducts + ($customProducts ?: []);
// Инициализация корзины
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// Обработка действий (добавление, удаление, заказ)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_id'])) {
        $id = (int)$_POST['add_id'];
        if (isset($products[$id])) {
            $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
            $message = "Товар «{$products[$id]['name']}» добавлен в корзину!";
        }
    } elseif (isset($_POST['remove_id'])) {
        $id = (int)$_POST['remove_id'];
        unset($_SESSION['cart'][$id]);
    } elseif (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
    } elseif (isset($_POST['checkout'])) {
        $customer = htmlspecialchars(trim($_POST['customer_name'] ?? 'Покупатель'));
        $phone = htmlspecialchars(trim($_POST['customer_phone'] ?? ''));
        if (!empty($_SESSION['cart']) && $phone !== '') {
            $_SESSION['cart'] = [];
            $message = "Спасибо, {$customer}! Ваш заказ принят. Мы свяжемся по номеру {$phone}.";
        }
    }
}

// Фильтрация по категории, поиск и сортировка
$selectedCategory = $_GET['category'] ?? 'Все';
$searchQuery      = trim($_GET['search'] ?? '');
$sortBy           = $_GET['sort'] ?? 'default';

$categories = ['Все'];
foreach ($products as $item) {
    if (!in_array($item['category'], $categories)) {
        $categories[] = $item['category'];
    }
}

$filteredProducts = array_filter($products, function ($item) use ($selectedCategory, $searchQuery) {
    $matchCategory = ($selectedCategory === 'Все' || $item['category'] === $selectedCategory);
    $matchSearch   = ($searchQuery === '' || mb_stripos($item['name'], $searchQuery) !== false);
    return $matchCategory && $matchSearch;
});

if ($sortBy === 'price_asc') {
    uasort($filteredProducts, fn($a, $b) => $a['price'] <=> $b['price']);
} elseif ($sortBy === 'price_desc') {
    uasort($filteredProducts, fn($a, $b) => $b['price'] <=> $a['price']);
}
// Подсчёт корзины
$totalCount = array_sum($_SESSION['cart']);
$totalPrice = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    if (isset($products[$id])) {
        $totalPrice += $products[$id]['price'] * $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A.S.98 — Магазин одежды</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #f5f6fa; color: #222; line-height: 1.5; }
        header { background: #111; color: #fff; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10; }
        .logo { font-size: 22px; font-weight: 800; letter-spacing: 2px; }
        .cart-badge { background: #e63946; color: #fff; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 14px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 3fr 1.2fr; gap: 30px; }
        @media (max-width: 850px) { .container { grid-template-columns: 1fr; } }
        .alert { background: #d4edda; color: #155724; padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .filters { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 25px; }
        .filters a { text-decoration: none; padding: 8px 16px; border-radius: 20px; background: #fff; color: #333; border: 1px solid #ddd; font-size: 14px; transition: 0.2s; }
        .filters a.active, .filters a:hover { background: #111; color: #fff; border-color: #111; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; }
        .card-img { height: 200px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; font-size: 18px; letter-spacing: 1px; }
        .card-body { padding: 18px; }
        .cat-tag { font-size: 12px; color: #888; text-transform: uppercase; }
        .card-title { font-size: 17px; font-weight: 700; margin: 5px 0; }
        .card-meta { font-size: 13px; color: #666; margin-bottom: 15px; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding: 0 18px 18px; }
        .price { font-size: 18px; font-weight: 800; }
        button { cursor: pointer; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; transition: 0.2s; }
        .btn-add { background: #111; color: #fff; }
        .btn-add:hover { background: #333; }
        .sidebar { background: #fff; padding: 22px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; }
        .sidebar h2 { font-size: 20px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px; }
        .btn-del { background: transparent; color: #e63946; padding: 4px 8px; font-size: 16px; }
        .total-row { font-size: 18px; font-weight: 800; margin: 15px 0; display: flex; justify-content: space-between; }
        input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn-order { width: 100%; background: #2a9d8f; color: #fff; padding: 12px; font-size: 15px; }
        .btn-order:hover { background: #21867a; }
    </style>
</head>
<body>

<header>
    <div class="logo">A.S.98</div>
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="seller.php" style="background: #fff; color: #111; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">Кабинет продавца</a>
        <div class="cart-badge">Корзина: <?= $totalCount ?> шт. (<?= number_format($totalPrice, 0, '', ' ') ?> ₽)</div>
    </div>
</header>
<div class="container">
    <main>
        <?php if ($message): ?>
            <div class="alert"><?= $message ?></div>
        <?php endif; ?>

        <div class="filters">
            <?php foreach ($categories as $cat): ?>
                <a href="?category=<?= urlencode($cat) ?>" class="<?= $selectedCategory === $cat ? 'active' : '' ?>">
                    <?= $cat ?>
                </a>
            <?php endforeach; ?>
            </div>

    <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
        <input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>">
        
        <input type="text" name="search" value="<?= htmlspecialchars($searchQuery) ?>" 
               placeholder="Поиск по названию (например: худи, jeans)..." 
               style="flex: 1; min-width: 220px; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; margin: 0;">

        <select name="sort" onchange="this.form.submit()" 
                style="padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; background: #fff; cursor: pointer;">
            <option value="default" <?= $sortBy === 'default' ? 'selected' : '' ?>>По умолчанию</option>
            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Сначала дешевле</option>
            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Сначала дороже</option>
        </select>

        <button type="submit" style="background: #111; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold;">Найти</button>

        <?php if ($searchQuery !== '' || $sortBy !== 'default'): ?>
            <a href="index.php?category=<?= urlencode($selectedCategory) ?>" 
               style="padding: 10px 14px; background: #e0e0e0; color: #222; text-decoration: none; border-radius: 8px; font-size: 14px; display: flex; align-items: center;">Сбросить</a>
        <?php endif; ?>
    </form>

    <div class="grid">
        <?php foreach ($filteredProducts as $id => $item): ?>
                        <div class="card">
                <a href="product.php?id=<?= $id ?>" style="text-decoration: none; color: inherit; display: block;">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="card-img" style="width: 100%; object-fit: cover;">
                </a>

                <div class="card-body">
                    <div class="cat-tag"><?= $item['category'] ?></div>
                    <div class="card-title">
                        <a href="product.php?id=<?= $id ?>" style="text-decoration: none; color: inherit;"><?= $item['name'] ?></a>
                    </div>
                    <div class="card-meta">Размеры: <?= $item['size'] ?></div>
                </div>

                    <div class="card-footer">
                        <div class="price"><?= number_format($item['price'], 0, '', ' ') ?> ₽</div>
                        <form method="POST">
                            <input type="hidden" name="add_id" value="<?= $id ?>">
                            <button type="submit" class="btn-add">В корзину</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <aside class="sidebar">
        <h2>Ваш заказ</h2>
        <?php if (empty($_SESSION['cart'])): ?>
            <p style="color:#777; font-size:14px;">Корзина пока пуста. Выберите понравившиеся вещи из каталога.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['cart'] as $id => $qty): ?>
                <?php if (!isset($products[$id])) continue; ?>
                <div class="cart-item">
                                            <div>
                            <strong><?= $products[$id]['name'] ?></strong><br>
                            <?php if (!empty($_SESSION['cart_sizes'][$id])): ?>
                                <small style="color: #111; font-weight: bold;">Размеры: <?= htmlspecialchars(implode(', ', $_SESSION['cart_sizes'][$id])) ?></small><br>
                            <?php endif; ?>
                            <small><?= $qty ?> × <?= number_format($products[$id]['price'], 0, '', ' ') ?> ₽</small>
                        </div>

                    <form method="POST">
                        <input type="hidden" name="remove_id" value="<?= $id ?>">
                        <button type="submit" class="btn-del" title="Удалить">✕</button>
                    </form>
                </div>
            <?php endforeach; ?>

            <div class="total-row">
                <span>Итого:</span>
                <span><?= number_format($totalPrice, 0, '', ' ') ?> ₽</span>
            </div>

            <form method="POST">
                <input type="text" name="customer_name" placeholder="Ваше имя" required>
                <input type="tel" name="customer_phone" placeholder="Телефон (+7...)" required>
                <button type="submit" name="checkout" class="btn-order">Оформить заказ</button>
            </form>
        <?php endif; ?>
    </aside>
</div>

</body>
</html>
