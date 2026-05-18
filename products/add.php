<?php
include "../includes/db.php";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $model = trim($_POST['model'] ?? '');
    $price = $_POST['price'] ?? '';

    if (!$model) $errors['model'] = "لطفاً نام کالا را وارد کنید.";

    if ($price === '' || !is_numeric($price) || floatval($price) <= 0) {
        $errors['price'] = "لطفاً قیمت معتبر وارد کنید.";
    } else {
        $price = floatval($price);
    }

    $colors = $_POST['color'] ?? [];
    $sizes = $_POST['size'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $images = $_FILES['color_image'] ?? null;

    foreach ($colors as $i => $color) {
        $quantity = $quantities[$i] ?? '';
        if ($quantity === '' || !is_numeric($quantity) || intval($quantity) <= 0) {
            $errors['quantity'][$i] = "مقدار معتبر وارد کنید.";
        }
        if (!trim($color)) $errors['color'][$i] = "رنگ نمی‌تواند خالی باشد.";
        if (!trim($sizes[$i] ?? '')) $errors['size'][$i] = "سایز نمی‌تواند خالی باشد.";
    }

    if (empty($errors)) {

        $stmt = $db->prepare("SELECT id, price FROM products WHERE model = ?");
        $stmt->execute([$model]);
        $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingProduct) {
            $productId = $existingProduct['id'];

            if ($price > 0 && $price != $existingProduct['price']) {
                $stmt = $db->prepare("UPDATE products SET price = ? WHERE id = ?");
                $stmt->execute([$price, $productId]);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO products (model, price) VALUES (?, ?)");
            $stmt->execute([$model, $price]);
            $productId = $db->lastInsertId();
        }

        foreach ($colors as $i => $colorName) {
            $colorName = trim($colorName);
            if (!$colorName) continue;

            $stmt = $db->prepare("SELECT id FROM colors WHERE name = ?");
            $stmt->execute([$colorName]);
            $color = $stmt->fetch();
            $colorId = $color ? $color['id'] : null;

            if (!$colorId) {
                $stmt = $db->prepare("INSERT INTO colors (name) VALUES (?)");
                $stmt->execute([$colorName]);
                $colorId = $db->lastInsertId();
            }

            $variantImagePath = null;
            if (!empty($images['name'][$i])) {
                $filename = basename($images['name'][$i]);
                $targetFile = __DIR__ . "/../uploads/" . $filename;

                if (move_uploaded_file($images['tmp_name'][$i], $targetFile)) {
                    $variantImagePath = 'uploads/' . $filename;
                }
            }

            $stmt = $db->prepare("INSERT INTO product_variants (product_id, color_id, size, quantity, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$productId, $colorId, $sizes[$i], intval($quantities[$i]), $variantImagePath]);
        }

        header("Location: list.php");
        exit;
    }
}
?>

<?php include "../includes/header.php"; ?>

<h2>افزودن کالای جدید</h2>
<a href="list.php">&larr; بازگشت به لیست کالاها</a><br><br>

<style>
form { max-width: 800px; margin:auto; }
form input[type=text], form input[type=number], form input[type=file] { padding:6px; width:100%; box-sizing:border-box; }
.error { border:2px solid red; }
table { width:100%; border-collapse: collapse; margin-bottom:10px; }
table, th, td { border:1px solid #ccc; padding:8px; text-align:center; }
button { padding:6px 12px; margin-top:5px; }
.autocomplete-suggestions { border:1px solid #ccc; max-height:150px; overflow-y:auto; position:absolute; background:white; z-index:1000; }
.autocomplete-suggestion { padding:5px; cursor:pointer; }
.autocomplete-suggestion:hover { background:#eee; }
</style>

<form action="" method="post" enctype="multipart/form-data" id="productForm">

    <label>نام کالا:
        <input type="text" name="model" id="modelInput" autocomplete="off"
               value="<?= htmlspecialchars($_POST['model'] ?? '') ?>"
               class="<?= isset($errors['model']) ? 'error' : '' ?>" required>

        <div id="modelSuggestions" class="autocomplete-suggestions"></div>

        <?php if(isset($errors['model'])): ?>
            <span style="color:red"><?= $errors['model'] ?></span>
        <?php endif; ?>
    </label><br><br>

    <label>قیمت:
        <input type="number" step="0.01" name="price" id="priceInput"
               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
               class="<?= isset($errors['price']) ? 'error' : '' ?>" required>

        <?php if(isset($errors['price'])): ?>
            <span style="color:red"><?= $errors['price'] ?></span>
        <?php endif; ?>
    </label><br><br>

    <h3>مشخصات کالا (رنگ + سایز + تعداد + تصویر)</h3>

    <table id="variantsTable">
        <thead>
        <tr>
            <th>رنگ</th>
            <th>سایز</th>
            <th>تعداد</th>
            <th>تصویر</th>
            <th>عملیات</th>
        </tr>
        </thead>

        <tbody>
        <?php
        $numVariants = count($_POST['color'] ?? [1]);
        for($i=0; $i<$numVariants; $i++):
        ?>
        <tr>
            <td>
                <input type="text" name="color[]" value="<?= htmlspecialchars($_POST['color'][$i] ?? '') ?>"
                       class="<?= isset($errors['color'][$i]) ? 'error' : '' ?>" required>
            </td>

            <td>
                <input type="text" name="size[]" value="<?= htmlspecialchars($_POST['size'][$i] ?? '') ?>"
                       class="<?= isset($errors['size'][$i]) ? 'error' : '' ?>" required>
            </td>

            <td>
                <input type="number" name="quantity[]" min="1"
                       value="<?= htmlspecialchars($_POST['quantity'][$i] ?? '') ?>"
                       class="<?= isset($errors['quantity'][$i]) ? 'error' : '' ?>" required>
            </td>

            <td><input type="file" name="color_image[]"></td>

            <td>
                <button type="button" onclick="removeVariant(this)">حذف</button>
            </td>
        </tr>
        <?php endfor; ?>
        </tbody>
    </table>

    <button type="button" onclick="addVariant()">افزودن مشخصه جدید</button><br><br>
    <button type="submit">ثبت کالا</button>

</form>

<script>
function addVariant() {
    const table = document.getElementById('variantsTable').getElementsByTagName('tbody')[0];
    const row = document.createElement('tr');

    row.innerHTML = `
        <td><input type="text" name="color[]" required></td>
        <td><input type="text" name="size[]" required></td>
        <td><input type="number" name="quantity[]" min="1" required></td>
        <td><input type="file" name="color_image[]"></td>
        <td><button type="button" onclick="removeVariant(this)">حذف</button></td>
    `;

    table.appendChild(row);
}

function removeVariant(btn) {
    btn.closest('tr').remove();
}

document.getElementById('variantsTable').addEventListener('keypress', function(e){
    if(e.target.name === 'quantity[]' && !/[0-9]/.test(e.key)) e.preventDefault();
});

const modelInput = document.getElementById('modelInput');
const priceInput = document.getElementById('priceInput');
const suggestionsDiv = document.getElementById('modelSuggestions');

modelInput.addEventListener('input', function() {
    const q = this.value;

    if (!q) {
        suggestionsDiv.innerHTML = '';
        return;
    }

    fetch('fetch_models.php?q=' + encodeURIComponent(q))
    .then(res => res.json())
    .then(data => {
        suggestionsDiv.innerHTML = '';

        data.forEach(item => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            div.textContent = item.model;
            div.dataset.price = item.price;

            div.addEventListener('click', function() {
                modelInput.value = item.model;
                priceInput.value = item.price;
                suggestionsDiv.innerHTML = '';
            });

            suggestionsDiv.appendChild(div);
        });
    });
});
</script>

<?php include "../includes/footer.php"; ?>