<?php
session_start();

// Базовый список товаров (такой же, как на главной странице)
$baseProducts = [
    1 => ['name' => 'Базовая футболка Oversize', 'category' => 'Футболки', 'price' => 2490, 'size' => 'S, M, L, XL', 'image' => 'img/futbolka.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Плотный 100% хлопок премиального качества. Свободный крой оверсайз идеально подходит для повседневных образов.'],
    2 => ['name' => 'Худи из плотного футера', 'category' => 'Толстовки', 'price' => 5990, 'size' => 'M, L, XL', 'image' => 'img/cofta.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Теплое худи из трехнитки без начеса. Глубокий двойной капюшон и эластичные манжеты.'],
    3 => ['name' => 'Джинсы прямого кроя', 'category' => 'Брюки', 'price' => 6490, 'size' => '30, 32, 34', 'image' => 'img/djinci.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Классический плотный деним, который отлично держит форму и сочетается с любой обувью.'],
    4 => ['name' => 'Оверсайз-рубашка из хлопка', 'category' => 'Рубашки', 'price' => 4290, 'size' => 'S, M, L', 'image' => 'img/rubahca.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Легкая дышащая рубашка свободного кроя. Можно носить как самостоятельный верх или поверх футболки.'],
    5 => ['name' => 'Свитшот минимализм', 'category' => 'Толстовки', 'price' => 4890, 'size' => 'S, M, L, XL', 'image' => 'img/coftarr.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Лаконичный базовый свитшот без лишних деталей из мягкого хлопкового трикотажа.'],
    6 => ['name' => 'Карго-брюки с карманами', 'category' => 'Брюки', 'price' => 5790, 'size' => 'M, L, XL', 'image' => 'img/djinccii.jpg', 'seller' => 'A.S.98 Official', 'desc' => 'Функциональные брюки с объемными накладными карманами и регулировкой ширины снизу.'],
];

$customProducts = file_exists('products.json') ? json_decode(file_get_contents('products.json'), true) : [];
$products = $baseProducts + ($customProducts ?: []);

// Получаем ID товара из ссылки (например, product.php?id=1)
$id = (int)($_GET['id'] ?? 0);
$item = $products[$id] ?? null;

// Добавление в корзину со страницы товара
if ($item && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + 1;
    $addedMsg = 'Товар добавлен в вашу корзину!';
}

// Подсчет корзины для шапки
$totalCount = 0;
$totalPrice = 0;
foreach ($_SESSION['cart'] ?? [] as $cId => $qty) {
    if (isset($products[$cId])) {
        $totalCount += $qty;
        $totalPrice += $products[$cId]['price'] * $qty;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= $item ? htmlspecialchars($item['name']) . ' — A.S.98' : 'Товар не найден — A.S.98' ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f5f5; color: #222; }
        header { background: #111; color: #fff; padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; letter-spacing: 3px; color: #fff; text-decoration: none; }
        .cart-badge { background: #222; border: 1px solid #444; padding: 8px 14px; border-radius: 6px; font-size: 14px; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #555; text-decoration: none; font-weight: bold; }
        .back-link:hover { color: #111; }
        .product-box { background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .product-photo img { width: 100%; height: 450px; object-fit: cover; border-radius: 8px; }
        .badge { display: inline-block; background: #eee; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; margin-bottom: 10px; }
        .seller-tag { color: #666; font-size: 14px; margin-bottom: 15px; }
        .price { font-size: 28px; font-weight: bold; margin: 20px 0; }
        .sizes { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0 25px; }
        .size-pill { border: 1px solid #ccc; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; background: #fff; }
        .size-pill:hover { border-color: #111; }
        .desc { line-height: 1.6; color: #444; margin-bottom: 25px; border-top: 1px solid #eee; padding-top: 15px; }
        .btn-buy { width: 100%; background: #111; color: #fff; border: none; padding: 15px; font-size: 16px; font-weight: bold; border-radius: 8px; cursor: pointer; }
        .btn-buy:hover { background: #333; }
        .alert { background: #d4edda; color: #155724; padding: 12px 15px; border-radius: 8px; margin-bottom: 15px; font-weight: bold; }
        @media (max-width: 768px) { .product-box { grid-template-columns: 1fr; } .product-photo img { height: 320px; } }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">A.S.98</a>
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="seller.php" style="background: #fff; color: #111; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px;">Кабинет продавца</a>
        <div class="cart-badge">Корзина: <?= $totalCount ?> шт. (<?= number_format($totalPrice, 0, '', ' ') ?> ₽)</div>
    </div>
</header>

<div class="container">
    <a href="index.php" class="back-link">← Вернуться в каталог</a>

    <?php if (!$item): ?>
        <div class="product-box" style="display: block; text-align: center;">
            <h2>Товар не найден</h2>
            <p>Возможно, он был удален продавцом.</p>
        </div>
    <?php else: ?>
        <div class="product-box">
            <div class="product-photo">
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
            </div>
            <div>
                <span class="badge"><?= htmlspecialchars($item['category']) ?></span>
                <h1 style="margin: 5px 0 10px; font-size: 26px;"><?= htmlspecialchars($item['name']) ?></h1>
                <div class="seller-tag">Продавец: <b><?= htmlspecialchars($item['seller'] ?? 'A.S.98 Official') ?></b></div>
                
                <div class="price"><?= number_format($item['price'], 0, '', ' ') ?> ₽</div>

                <div><b>Доступные размеры:</b></div>
                <div class="sizes">
                    <?php foreach (explode(',', $item['size']) as $sz): ?>
                        <span class="size-pill"><?= htmlspecialchars(trim($sz)) ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="desc">
                    <b>Описание:</b><br>
                    <?= htmlspecialchars($item['desc'] ?? 'Оригинальная вещь из коллекции маркетплейса A.S.98. Качественные материалы и удобная посадка.') ?>
                </div>

                <?php if (!empty($addedMsg)): ?>
                    <div class="alert"><?= $addedMsg ?></div>
                <?php endif; ?>

                <form method="POST">
                    <button type="submit" name="add_to_cart" class="btn-buy">Добавить в корзину</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
