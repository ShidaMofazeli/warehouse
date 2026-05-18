<?php
include "../includes/db.php";

// دریافت همه کالاها برای لیست انتخاب
$stmt = $db->query("SELECT id, model FROM products ORDER BY model ASC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// پردازش ثبت فروش
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $variant_id = intval($_POST['variant_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);

    // دریافت اطلاعات موجودی انتخاب‌شده
    $stmt = $db->prepare("
        SELECT v.id, v.quantity, p.model, c.name as color, v.size 
        FROM product_variants v
        JOIN products p ON v.product_id = p.id
        JOIN colors c ON v.color_id = c.id
        WHERE v.id = ?
    ");

    $stmt->execute([$variant_id]);
    $variant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$variant) {

        $error = "مورد انتخاب‌شده یافت نشد";

    } elseif ($quantity <= 0 || $quantity > $variant['quantity']) {

        $error = "تعداد نامعتبر است. موجودی فعلی: " . $variant['quantity'];

    } else {

        // کاهش موجودی
        $newQuantity = $variant['quantity'] - $quantity;

        $stmt = $db->prepare("UPDATE product_variants SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQuantity, $variant_id]);

        // ثبت فروش
        $stmt = $db->prepare("INSERT INTO sales (variant_id, quantity, sale_date) VALUES (?, ?, ?)");

        $stmt->execute([
            $variant_id,
            $quantity,
            date("Y-m-d H:i:s")
        ]);

        // انتقال به فاکتور
        header("Location: invoice.php?sale_id=" . $db->lastInsertId());
        exit;
    }
}
?>

<?php include "../includes/header.php"; ?>

<h2>ثبت فروش</h2>

<a href="../products/list.php" style="display:inline-block; margin-bottom:20px;">
    &larr; بازگشت به لیست انبار
</a>

<?php if (!empty($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form action="" method="post" style="max-width:500px; margin:auto;">

    <label>
        مدل کالا:
        <br>

        <select id="productSelect" required style="width:100%; padding:8px;">

            <option value="">انتخاب مدل</option>

            <?php foreach($products as $p): ?>

                <option value="<?= $p['id'] ?>">
                    <?= htmlspecialchars($p['model']) ?>
                </option>

            <?php endforeach; ?>

        </select>

    </label>

    <br><br>

    <label>
        مشخصات کالا:
        <br>

        <select id="variantSelect" name="variant_id" required style="width:100%; padding:8px;">
            <option value="">انتخاب مشخصات</option>
        </select>

    </label>

    <br><br>

    <label>
        تعداد:
        <br>

        <input type="number"
               name="quantity"
               id="quantityInput"
               min="1"
               required
               style="width:100%; padding:8px;">

    </label>

    <br><br>

    <button
        type="submit"
        style="padding:10px 20px; background:#007BFF; color:white; border:none; border-radius:6px; cursor:pointer;">

        ثبت فروش

    </button>

</form>

<script>

// بارگذاری مشخصات کالا
document.getElementById('productSelect').addEventListener('change', function() {

    const productId = this.value;
    const variantSelect = document.getElementById('variantSelect');
    const quantityInput = document.getElementById('quantityInput');

    variantSelect.innerHTML = '<option value="">در حال بارگذاری...</option>';

    quantityInput.value = '';
    quantityInput.removeAttribute('max');

    if (!productId) {
        variantSelect.innerHTML = '<option value="">انتخاب مشخصات</option>';
        return;
    }

    fetch(`get_variants.php?product_id=${productId}`)
        .then(res => res.json())
        .then(data => {

            variantSelect.innerHTML = '<option value="">انتخاب مشخصات</option>';

            data.forEach(v => {

                variantSelect.innerHTML += `
                    <option value="${v.id}" data-stock="${v.quantity}">
                        ${v.color} - سایز ${v.size} (موجودی: ${v.quantity})
                    </option>
                `;
            });
        });
});

// تعیین سقف تعداد
document.getElementById('variantSelect').addEventListener('change', function() {

    const selectedOption = this.selectedOptions[0];
    const stock = parseInt(selectedOption.getAttribute('data-stock') || 0);

    const quantityInput = document.getElementById('quantityInput');

    quantityInput.value = '';

    if (stock > 0) {
        quantityInput.setAttribute('max', stock);
    } else {
        quantityInput.removeAttribute('max');
    }

});

</script>

<?php include "../includes/footer.php"; ?>