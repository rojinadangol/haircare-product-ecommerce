<?php require_once 'admin_check.php'; ?>
<?php
$msg = ''; $type = '';
$action = $_GET['action'] ?? 'list';
$edit = null;
$unreadNotifs = $pdo->query("SELECT COUNT(*) FROM notifications WHERE user_id IS NULL AND is_read = 0")->fetchColumn();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $img = trim($_POST['img'] ?? '');
    $stock = max(0, intval($_POST['stock'] ?? 0));
    
    $textures = json_encode($_POST['hair_textures'] ?? []);
    $scalps   = json_encode($_POST['scalp_types'] ?? []);
    $problems = json_encode($_POST['hair_problems'] ?? []);
    
    // Handle file upload
    $uploadDir = '../uploads/products/';
    if (!is_dir('../uploads')) mkdir('../uploads');
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $file['name']);
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $img = 'uploads/products/' . $filename;
            }
        }
    }
    
    try {
        if ($act === 'add' && $name && $price > 0) {
            $pdo->prepare("INSERT INTO products (name, category, description, price, image_url, stock_quantity, hair_textures, scalp_types, hair_problems) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$name, $category, $desc, $price, $img, $stock, $textures, $scalps, $problems]);
            $msg = "Product added."; $type = "success";
        } elseif ($act === 'edit' && !empty($_POST['id']) && $name && $price > 0) {
            $productId = intval($_POST['id']);
            $newStock  = max(0, intval($_POST['stock'] ?? 0));

            // 1 Fetch old stock & name
            $checkStmt = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
            $checkStmt->execute([$productId]);
            $oldProduct = $checkStmt->fetch();

            // 2 Update product
            $pdo->prepare("UPDATE products SET name=?, category=?, description=?, price=?, image_url=?, stock_quantity=?, hair_textures=?, scalp_types=?, hair_problems=? WHERE id=?")
                ->execute([$name, $category, $desc, $price, $img, $newStock, $textures, $scalps, $problems, $_POST['id']]);

            // 3 Notify users if restocked (0 → >0)
            if ($oldProduct && $oldProduct['stock_quantity'] <= 0 && $newStock > 0) {
                $wishStmt = $pdo->prepare("SELECT user_id FROM wishlist WHERE product_id = ?");
                $wishStmt->execute([$productId]);
                $interestedUsers = $wishStmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($interestedUsers)) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (type, title, message, related_id, user_id) VALUES (?, ?, ?, ?, ?)");
                    foreach ($interestedUsers as $uid) {
                        $notifStmt->execute([
                            'wishlist_restocked',
                            '🎉 Back in Stock!',
                            "{$oldProduct['name']} is now available. Add it to your cart before it sells out!",
                            $productId,
                            $uid
                        ]);
                    }
                    $msg = "✅ Product updated & <strong>" . count($interestedUsers) . " user(s) notified.</strong>";
                } else {
                    $msg = "✅ Product updated successfully.";
                }
            } else {
                $msg = "✅ Product updated successfully.";
            }
            $type = "success";
        } elseif ($act === 'delete' && !empty($_POST['id'])) {
            // Delete image file if exists
            $stmt = $pdo->prepare("SELECT image_url FROM products WHERE id=?");
            $stmt->execute([$_POST['id']]);
            $product = $stmt->fetch();
            if ($product && $product['image_url'] && strpos($product['image_url'], 'uploads/') === 0) {
                $imagePath = '../' . $product['image_url'];
                if (file_exists($imagePath)) unlink($imagePath);
            }
            
            $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$_POST['id']]);
            $msg = "🗑️ Product deleted."; $type = "success";
        }
    } catch(Exception $e) { $msg = "❌ ".$e->getMessage(); $type = "error"; }
    header("Location: products.php?msg=".urlencode($msg)."&type=".$type); exit;
}
if (isset($_GET['msg'])) { $msg = $_GET['msg']; $type = $_GET['type'] ?? 'success'; }

if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$_GET['id']]);
    $edit = $stmt->fetch();
    if (!$edit) { header("Location: products.php"); exit; }
}
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        :root {
            --bg: #F9F2F2;
            --card: #FFFFFF;
            --accent: #800000;
            --accent-hover: #5C0000;
            --accent-light: #F4EAEA;
            --txt: #2C1810;
            --mut: #6B4C4C;
            --bdr: #E6D5D5;
            --success: #2E7D32;
            --success-bg: #E8F5E9;
            --error: #C62828;
            --error-bg: #FDECEA;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        body { background: var(--bg); color: var(--txt); display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 250px; background: var(--accent); border-right: 4px solid var(--accent-hover);
            padding: 1.5rem; position: fixed; height: 100vh; display: flex; flex-direction: column;
            box-shadow: 2px 0 10px rgba(128, 0, 0, 0.1);
        }
        .sidebar h2 { font-size: 1.3rem; margin-bottom: 2rem; letter-spacing: 1px; color: #FFFFFF; font-weight: 700; }
        .sidebar a {
            display: block; padding: 0.85rem 1rem; margin-bottom: 0.5rem; text-decoration: none;
            color: rgba(255, 255, 255, 0.85); border-radius: 8px; transition: all 0.2s ease; font-weight: 500;
        }
        .sidebar a:hover { background: rgba(255, 255, 255, 0.15); color: #fff; }
        .sidebar a.active { background: #FFFFFF; color: var(--accent); font-weight: 600; }
        .sidebar .logout { margin-top: auto; color: #FFD1D1; border: 1px solid rgba(255,255,255,0.3); }
        .sidebar .logout:hover { background: var(--accent-hover); color: #fff; border-color: transparent; }

        /* Main Content */
        .main { margin-left: 250px; flex: 1; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { font-size: 1.8rem; font-weight: 700; color: var(--txt); }
        .header span { color: var(--accent); font-size: 0.95rem; background: var(--accent-light); padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 500; }

        /* Stats Cards */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.2rem; margin-bottom: 2rem; }
        .stat {
            background: var(--card); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--bdr);
            text-align: center; box-shadow: 0 2px 8px rgba(128, 0, 0, 0.04); transition: transform 0.2s;
        }
        .stat:hover { transform: translateY(-3px); }
        .stat h3 { font-size: 0.85rem; color: var(--mut); margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat p { font-size: 2.2rem; font-weight: 700; color: var(--accent); }

        /* Forms & Boxes */
        .box, .form-box {
            background: var(--card); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--bdr);
            margin-bottom: 1.5rem; box-shadow: 0 2px 8px rgba(128, 0, 0, 0.04);
        }
        .box h3 { margin-bottom: 1rem; color: var(--txt); }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .fg { display: flex; flex-direction: column; gap: 0.4rem; }
        label { font-size: 0.85rem; font-weight: 500; color: var(--mut); }
        input, textarea, select {
            padding: 0.8rem; border: 1px solid var(--bdr); border-radius: 8px; background: #FCF9F9;
            font-size: 0.95rem; transition: all 0.2s; color: var(--txt);
        }
        input:focus, textarea:focus, select:focus {
            outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.15);
        }

        /* Checkbox Groups */
        .checkbox-group { display:flex; flex-wrap:wrap; gap:.6rem; margin-top:.3rem; }
        .checkbox-label { display:flex; align-items:center; gap:.4rem; background:#FAF8F5; padding:.4rem .7rem; border-radius:6px; border:1px solid var(--bdr); cursor:pointer; font-size:.85rem; }
        .checkbox-label:hover { border-color:var(--accent); }
        .checkbox-label input { accent-color:var(--accent); }

        /* Buttons */
        .btn {
            display: inline-block; padding: 0.75rem 1.5rem; background: var(--accent); color: #fff;
            text-decoration: none; border-radius: 8px; font-weight: 600; border: none; cursor: pointer;
            transition: all 0.2s; font-size: 0.95rem;
        }
        .btn:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-gray { background: var(--accent-light); color: var(--accent); border: 1px solid var(--bdr); }
        .btn-gray:hover { background: #EBE3E3; }
        .btn-red { background: var(--error); color: #fff; }
        .btn-red:hover { background: #A52A2A; }
        .btn-sm { padding: 0.4rem 0.9rem; font-size: 0.85rem; }

        /* Messages */
        .msg { padding: 0.85rem; border-radius: 8px; margin-bottom: 1.2rem; text-align: center; font-weight: 500; }
        .success { background: var(--success-bg); color: var(--success); border: 1px solid #C3E6CB; }
        .error { background: var(--error-bg); color: var(--error); border: 1px solid #F5C6CB; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 10px; overflow: hidden; border: 1px solid var(--bdr); box-shadow: 0 2px 8px rgba(128, 0, 0, 0.03); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--bdr); }
        th { background: var(--accent-light); font-weight: 600; color: var(--accent); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #FCF9F9; }
        td { font-size: 0.95rem; vertical-align: middle; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .empty { text-align: center; padding: 2.5rem; color: var(--mut); background: var(--accent-light); border-radius: 8px; }
        img.thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; background: #F0E8E8; border: 1px solid var(--bdr); }

        /* Responsive */
        @media(max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; flex-direction: row; align-items: center; padding: 1rem; overflow-x: auto; }
            .sidebar h2 { margin-bottom: 0; margin-right: 1rem; font-size: 1.1rem; white-space: nowrap; }
            .sidebar a { margin-bottom: 0; margin-right: 0.5rem; white-space: nowrap; }
            .main { margin-left: 0; padding: 1rem; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php require_once 'admin_sidebar.php'; ?>

    <main class="main">
        <h1 style="margin-bottom:1.5rem;"> Product Management</h1>
        <?php if($msg): ?><div class="msg <?= $type ?>"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

        <div class="box">
            <h3><?= $action === 'edit' ? '✏️ Edit Product' : '➕ Add Product' ?></h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top:1rem;">
                <input type="hidden" name="act" value="<?= $action === 'edit' ? 'edit' : 'add' ?>">
                <?php if($action==='edit' && $edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="grid">
                    <div class="fg"><label>Name</label><input type="text" name="name" value="<?= $edit?$edit['name']:'' ?>" required></div>
                    <div class="fg"><label>Category</label>
                        <select name="category" required>
                            <?php 
                            $cats = ['shampoo','conditioner','treatment','hair oil']; 
                            $current = $edit['category'] ?? 'shampoo';
                            foreach($cats as $c): ?>
                                <option value="<?= $c ?>" <?= $c == $current ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Hair Texture -->
                    <div class="fg" style="grid-column: 1/-1;">
                        <label>Hair Textures</label>
                        <div class="checkbox-group">
                            <?php $et = ($edit && $edit['hair_textures']) ? json_decode($edit['hair_textures'], true) : []; ?>
                            <?php foreach(['Straight','Wavy','Curly','Coily'] as $t): 
                                $val = strtolower($t); ?>
                                <label class="checkbox-label"><input type="checkbox" name="hair_textures[]" value="<?= $val ?>" <?= in_array($val, $et) ? 'checked' : '' ?>> <?= $t ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Scalp Type -->
                    <div class="fg" style="grid-column: 1/-1;">
                        <label>Scalp Types</label>
                        <div class="checkbox-group">
                            <?php $es = ($edit && $edit['scalp_types']) ? json_decode($edit['scalp_types'], true) : []; ?>
                            <?php foreach(['Oily','Dry','Normal'] as $s): 
                                $val = strtolower($s); ?>
                                <label class="checkbox-label"><input type="checkbox" name="scalp_types[]" value="<?= $val ?>" <?= in_array($val, $es) ? 'checked' : '' ?>> <?= $s ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Hair Problems -->
                    <div class="fg" style="grid-column: 1/-1;">
                        <label>Hair Problems</label>
                        <div class="checkbox-group">
                            <?php $ep = ($edit && $edit['hair_problems']) ? json_decode($edit['hair_problems'], true) : []; ?>
                            <?php foreach(['Hair Fall','Dandruff','Dry & Damaged','Frizzy','Split Ends'] as $p): 
                                $val = strtolower($p); ?>
                                <label class="checkbox-label"><input type="checkbox" name="hair_problems[]" value="<?= $val ?>" <?= in_array($val, $ep) ? 'checked' : '' ?>> <?= $p ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="fg"><label>Price ($)</label><input type="number" step="0.01" name="price" value="<?= $edit?$edit['price']:'' ?>" required></div>
                    <div class="fg"><label>Stock Quantity</label><input type="number" name="stock" value="<?= $edit?$edit['stock_quantity']:0 ?>" min="0" required></div>
                    <div class="fg"><label>Upload Image</label><input type="file" name="product_image" accept="image/*"></div>
                </div>
                <div class="fg" style="margin-top:.8rem;"><label>Description</label><textarea name="desc" rows="2"><?= $edit?$edit['description']:'' ?></textarea></div>
                <div style="margin-top:1rem; display:flex; gap:.5rem;">
                    <button class="btn"><?= $action==='edit'?'Update':'Add Product' ?></button>
                    <?php if($action==='edit'): ?><a href="products.php" class="btn btn-gray">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="box">
            <h3 style="margin-bottom:1rem;">All Products (<?= count($products) ?>)</h3>
            <table>
                <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach($products as $p): ?>
                    <tr>
                        <td><img src="<?= htmlspecialchars($p['image_url']) ?: 'https://placehold.co/50' ?>" class="thumb"></td>
                        <td><?= htmlspecialchars($p['name']) ?></td>
                        <td><span style="background:#F4EAEA; color:var(--accent); padding:.2rem .5rem; border-radius:4px; font-size:.8rem; font-weight:500;"><?= ucfirst(htmlspecialchars($p['category'])) ?></span></td>
                        <td>$<?= number_format($p['price'],2) ?></td>
                        <td><?= $p['stock_quantity'] ?></td>
                        <td>
                            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn btn-gray" style="padding:.4rem .8rem; font-size:.85rem;">Edit</a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                <input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button class="btn btn-red" style="padding:.4rem .8rem; font-size:.85rem;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($products)): ?><tr><td colspan="6" style="text-align:center;color:var(--mut); padding:2rem;">No products yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>