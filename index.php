<?php 
session_start(); 
require_once 'db.php'; 

$category     = $_GET['cat'] ?? 'all';
$searchQuery  = trim($_GET['q'] ?? '');
$selTextures  = $_GET['textures'] ?? [];
$selScalps    = $_GET['scalps'] ?? [];
$selProblems  = $_GET['problems'] ?? [];

$where = ["stock_quantity > 0"];
$params = [];

if ($category !== 'all') { $where[] = "category = ?"; $params[] = $category; }
if (!empty($searchQuery)) { 
    $like = "%$searchQuery%"; 
    $where[] = "(name LIKE ? OR description LIKE ?)"; 
    $params[] = $like; $params[] = $like; 
}

// JSON Filter Builder
$buildJsonFilter = function($col, $values) use (&$where, &$params) {
    if (empty($values)) return;
    $conds = [];
    foreach ($values as $v) {
        $conds[] = "JSON_CONTAINS($col, ?)";
        $params[] = '"' . htmlspecialchars(trim($v)) . '"';
    }
    $where[] = "(" . implode(" OR ", $conds) . ")";
};

$buildJsonFilter('hair_textures', $selTextures);
$buildJsonFilter('scalp_types', $selScalps);
$buildJsonFilter('hair_problems', $selProblems);

$sql = "SELECT * FROM products WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($searchQuery) ? 'Search: ' . htmlspecialchars($searchQuery) : 'Lumière Haircare' ?></title>
    <style>
        :root { --bg:#E0D4C3; --card:#F4ECE1; --accent:#A89078; --accent-h:#8F7963; --txt:#3A3532; --mut:#7A726C; --bdr:#CDBBA6; }
        * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }
        body { background:var(--bg); color:var(--txt); line-height:1.6; }
        
        /* Header & Search */
        header { background:var(--card); padding:1rem 5%; border-bottom:1px solid var(--bdr); position:sticky; top:0; z-index:100; }
        nav { max-width:1200px; margin:0 auto; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .logo { font-size:1.5rem; font-weight:700; letter-spacing:1px; color:var(--txt); text-decoration:none; }
        .nav-wrapper { display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap; flex:1; justify-content:flex-end; }
        
        /* Search Bar */
        .search-box { position:relative; display:flex; align-items:center; }
        .search-box input {
            padding:.6rem 2.5rem .6rem 1rem;
            border:1px solid var(--bdr);
            border-radius:50px;
            background:#FAF8F5;
            font-size:.95rem;
            width:250px;
            transition:.2s;
        }
        .search-box input:focus { outline:none; border-color:var(--accent); width:280px; box-shadow:0 0 0 3px rgba(200,185,165,.2); }
        .search-box button {
            position:absolute; right:5px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:var(--mut); cursor:pointer; padding:.3rem;
        }
        .search-box button:hover { color:var(--accent); }
        
        .nav-links { display:flex; align-items:center; gap:1.2rem; }
        .nav-links a { text-decoration:none; color:var(--mut); font-weight:500; transition:.2s; }
        .nav-links a:hover { color:var(--txt); }
        .btn { background:var(--accent); color:#fff; padding:.6rem 1.5rem; border-radius:50px; text-decoration:none; font-weight:600; border:none; cursor:pointer; transition:.2s; }
        .btn:hover { background:var(--accent-h); }
        .btn-outline { background:transparent; border:1px solid var(--bdr); color:var(--mut); padding:.5rem 1.2rem; border-radius:50px; text-decoration:none; font-weight:500; }
        .btn-outline:hover { border-color:var(--accent); color:var(--txt); }
        
        /* Hero */
        .hero { text-align:center; padding:5rem 1.5rem; background:linear-gradient(145deg,#F5F1EB,#EAE3D9); }
        .hero h1 { font-size:2.8rem; margin-bottom:1rem; font-weight:600; }
        .hero p { color:var(--mut); max-width:600px; margin:0 auto 2rem; font-size:1.1rem; }
        
        /* Search Results Info */
        .results-info { max-width:1200px; margin:2rem auto 1rem; padding:0 1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .results-info h2 { font-size:1.5rem; }
        .results-info p { color:var(--mut); }
        .clear-search { color:var(--accent); text-decoration:none; font-weight:500; }
        .clear-search:hover { text-decoration:underline; }
        
        /* Products Grid */
        .products { max-width:1200px; margin:0 auto 4rem; padding:0 1.5rem; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:2rem; }
        .card { background:var(--card); border:1px solid var(--bdr); border-radius:12px; padding:1.5rem; text-align:center; transition:.3s; }
        .card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(58,53,50,.08); }
        .card img { width:100%; height:180px; object-fit:cover; border-radius:8px; margin-bottom:1rem; background:#EAE3D9; }
        .card h3 { margin:.5rem 0; font-size:1.1rem; }
        .card .price { color:var(--mut); margin-bottom:1rem; font-weight:600; }
        .card .stock { font-size:.85rem; color:var(--success, #4A7C59); margin-bottom:.8rem; display:block; }
        .empty { text-align:center; grid-column:1/-1; color:var(--mut); padding:4rem 2rem; background:var(--card); border:1px dashed var(--bdr); border-radius:12px; }
        .empty h3 { margin-bottom:.5rem; color:var(--txt); }
        
        @media(max-width:768px){ 
            .hero h1{font-size:2.2rem;} 
            nav{justify-content:center;} 
            .search-box input{width:180px;} 
            .search-box input:focus{width:200px;}
            .nav-wrapper{width:100%; justify-content:center;}
            
            .shop-layout { flex-direction:column; gap:1.5rem; }
            .filters { flex:none; width:100%; position:relative; top:auto; }
            .filter-section { margin-bottom:1.5rem; }
            .filter-section h3 { font-size:1.1rem; }
            .f-check { font-size:1rem; margin-bottom:.8rem; }
            .filter-actions { flex-direction:row; justify-content:space-between; align-items:center; }
            .filter-actions .btn { flex:1; margin-right:1rem; }
        }

        /* Tooltip for login prompt buttons */
        .btn-outline[style*="dashed"] { position:relative; }
        .btn-outline[style*="dashed"]:hover::after {
            content: "Create an account or sign in to start shopping!";
            position:absolute; bottom:110%; left:50%; transform:translateX(-50%);
            background:var(--txt); color:#fff; padding:.5rem .8rem; border-radius:6px;
            font-size:.8rem; white-space:nowrap; pointer-events:none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 10;
        }

        /* Category Filter */
        .filter-bar { max-width:1200px; margin:2rem auto 1rem; padding:0 1.5rem; display:flex; gap:.5rem; flex-wrap:wrap; justify-content:center; }
        .filter-btn { padding:.5rem 1.2rem; border-radius:50px; border:1px solid var(--bdr); background:var(--card); color:var(--mut); text-decoration:none; font-size:.9rem; transition:.2s; }
        .filter-btn:hover { border-color:var(--accent); color:var(--txt); }
        .filter-btn.active { background:var(--accent); color:#fff; border-color:var(--accent); }
        .badge-cat { display:inline-block; background:#F9F5F0; color:var(--accent); font-size:.75rem; padding:.2rem .6rem; border-radius:6px; margin-bottom:.6rem; font-weight:500; }

        /* Shop Layout */
        .shop-layout { max-width:1200px; margin:2rem auto; padding:0 1.5rem; display:grid; grid-template-columns:240px 1fr; gap:2rem; }
        .filters { background:var(--card); border:1px solid var(--bdr); border-radius:10px; padding:1.2rem; height:fit-content; position:sticky; top:1rem; }
        .filter-section h3 { font-size:.95rem; margin-bottom:.6rem; color:var(--txt); border-bottom:1px solid var(--bdr); padding-bottom:.3rem; }
        .f-check { display:flex; align-items:center; gap:.4rem; margin-bottom:.4rem; font-size:.9rem; cursor:pointer; }
        .f-check input { accent-color:var(--accent); }
        .filter-actions { margin-top:1rem; display:flex; flex-direction:column; gap:.5rem; }
        .clear-filters { text-align:center; color:var(--mut); font-size:.85rem; text-decoration:none; }
        .clear-filters:hover { color:var(--accent); }
        .product-area { min-width:0; }
        @media(max-width:850px){ .shop-layout{grid-template-columns:1fr;} .filters{position:static; margin-bottom:1.5rem;} }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="index.php" class="logo">LUMIÈRE</a>
            <div class="nav-wrapper">
                <div class="search-box">
                    <form method="GET" action="index.php" style="display:flex; align-items:center;">
                        <input type="text" name="q" placeholder="Search products..." value="<?= htmlspecialchars($searchQuery) ?>" autocomplete="off">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                <div class="nav-links">
                    <a href="#products">Products</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="user/index.php" class="btn-outline">👤 My Account</a>
                        <span style="color:var(--mut); font-size:.9rem;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                        <a href="logout.php" class="btn" style="padding:.45rem 1.2rem; font-size:.9rem;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <?php if (empty($searchQuery)): ?>
            <section class="hero">
                <h1>Nourish Your Natural Beauty</h1>
                <p>Botanical-infused, sulfate-free haircare designed for radiant, healthy hair.</p>
                <a href="#products" class="btn">Shop Collection</a>
            </section>
        <?php else: ?>
            <div class="results-info">
                <h2>🔍 Search Results</h2>
                <p>Found <strong><?= count($products) ?></strong> product(s) for "<?= htmlspecialchars($searchQuery) ?>"</p>
                <a href="index.php" class="clear-search">Clear Search</a>
            </div>
        <?php endif; ?>

        <div class="shop-layout">
            <aside class="filters">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="cat" value="<?= htmlspecialchars($category) ?>">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                    
                    <div class="filter-section">
                        <h3>Hair Texture</h3>
                        <?php foreach(['straight'=>'Straight','wavy'=>'Wavy','curly'=>'Curly','coily'=>'Coily'] as $k=>$v): ?>
                            <label class="f-check"><input type="checkbox" name="textures[]" value="<?= $k ?>" <?= in_array($k,$selTextures)?'checked':'' ?>> <?= $v ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="filter-section">
                        <h3>Scalp Type</h3>
                        <?php foreach(['oily'=>'Oily','dry'=>'Dry','normal'=>'Normal'] as $k=>$v): ?>
                            <label class="f-check"><input type="checkbox" name="scalps[]" value="<?= $k ?>" <?= in_array($k,$selScalps)?'checked':'' ?>> <?= $v ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="filter-section">
                        <h3>Hair Problems</h3>
                        <?php foreach(['hair fall'=>'Hair Fall','dandruff'=>'Dandruff','dry & damaged'=>'Dry & Damaged','frizzy'=>'Frizzy','split ends'=>'Split Ends'] as $k=>$v): ?>
                            <label class="f-check"><input type="checkbox" name="problems[]" value="<?= $k ?>" <?= in_array($k,$selProblems)?'checked':'' ?>> <?= $v ?></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="index.php" class="clear-filters">Clear All</a>
                    </div>
                </form>
            </aside>

            <div class="product-area">
                <div class="filter-bar">
                    <?php 
                    $qStr = $searchQuery ? '&q='.urlencode($searchQuery) : '';
                    $tStr = !empty($selTextures) ? '&textures[]='.implode('&textures[]=', array_map('urlencode',$selTextures)) : '';
                    $sStr = !empty($selScalps) ? '&scalps[]='.implode('&scalps[]=', array_map('urlencode',$selScalps)) : '';
                    $pStr = !empty($selProblems) ? '&problems[]='.implode('&problems[]=', array_map('urlencode',$selProblems)) : '';
                    foreach(['all'=>'All','shampoo'=>'Shampoo','conditioner'=>'Conditioner','treatment'=>'Treatment','hair oil'=>'Hair Oil'] as $k=>$v): ?>
                        <a href="?cat=<?= $k ?><?= $qStr.$tStr.$sStr.$pStr ?>" class="filter-btn <?= $category==$k?'active':'' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                            <div class="card">
                                <img src="<?= htmlspecialchars($p['image_url']) ?: 'https://placehold.co/400x300/EAE3D9/7A726C?text=Product' ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                                <span class="badge-cat"><?= ucfirst(htmlspecialchars($p['category'])) ?></span>
                                <h3><?= htmlspecialchars($p['name']) ?></h3>
                                <span class="stock"><?= $p['stock_quantity'] > 0 ? " In Stock" : " Out of Stock" ?></span>
                                <p class="price">$<?= number_format($p['price'], 2) ?></p>
                                
                                <?php if ($p['stock_quantity'] > 0): ?>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <!-- Logged In: Add to Cart -->
                                        <a href="add_to_cart.php?id=<?= $p['id'] ?>" class="btn-outline">Add to Cart</a>
                                    <?php else: ?>
                                        <!-- Guest: Login Prompt -->
                                        <a href="login.php?redirect=index.php" class="btn-outline" style="border:1px dashed var(--accent); color:var(--accent);"> Login to Purchase</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn-outline" disabled style="opacity:.5; cursor:not-allowed;">Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty">
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or search with different keywords.</p>
                            <a href="index.php" class="btn" style="margin-top:1.5rem; display:inline-block;">Browse All Products</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
