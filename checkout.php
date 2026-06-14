<?php
/**
 * checkout.php - Buyurtma rasmiylashtirish sahifasi
 * 
 * Vazifalari:
 * - Foydalanuvchi ma'lumotlarini ko'rsatish
 * - Yetkazib berish manzilini tanlash (viloyat, tuman)
 * - Xarita orqali joy belgilash (Leaflet)
 * - To'lov turini tanlash
 */

require_once __DIR__ . '/config/config.php';

// Kirish talab qilinadi
User::requireLogin();

// Admin buyurtma bera olmaydi
if (User::isAdmin()) {
    flash('error', 'Adminlar buyurtma bera olmaydi.');
    redirect('index.php');
}

$title = 'Buyurtma rasmiylashtirish';

// Savatni tekshirish
$cart = new Cart();
$items = $cart->getItems();
$total = $cart->getTotal();

if (empty($items)) {
    flash('error', 'Savat bo\'sh. Avval mahsulot qo\'shing.');
    redirect('cart.php');
}

// Sotuvchi o'z mahsulotiga buyurtma bera olmaydi
if (User::isSeller()) {
    foreach ($items as $item) {
        if ((int)$item['seller_id'] === (int)$_SESSION['user_id']) {
            flash('error', 'Siz o\'z mahsulotingizni sotib ololmaysiz. Iltimos, savatdan o\'z mahsulotingizni olib tashlang.');
            redirect('cart.php');
        }
    }
}

// Foydalanuvchi va viloyat ma'lumotlari
$userModel = new User();
$user = $userModel->getById($_SESSION['user_id']);
$regionModel = new Region();
$regions = $regionModel->getAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Leaflet (xarita) uchun CSS va JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <a href="<?= SITE_URL ?>/cart.php">Savat</a> <span>/</span>
    <span>Buyurtma</span>
</div>

<h1 class="page-title"><i class="fas fa-credit-card"></i> Buyurtma rasmiylashtirish</h1>

<div class="checkout-layout">
    <!-- Buyurtma formasi -->
    <div class="checkout-form">
        <form method="POST" action="<?= SITE_URL ?>/order_process.php" id="orderForm">

            <!-- 1-qadam: Foydalanuvchi ma'lumotlari -->
            <div class="checkout-section">
                <h3><i class="fas fa-user"></i> 1. Ma'lumotlaringiz</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>To'liq ismingiz</label>
                        <input type="text" name="full_name" 
                               value="<?= htmlspecialchars($user['full_name']) ?>" 
                               readonly class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" 
                               readonly class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Telefon raqam</label>
                    <input type="text" name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                           required class="form-control" placeholder="+998901234567">
                </div>
            </div>

            <!-- 2-qadam: Yetkazib berish manzili -->
            <div class="checkout-section">
                <h3><i class="fas fa-map-marker-alt"></i> 2. Yetkazib berish manzili</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Viloyat / Shahar</label>
                        <select name="region_id" id="regionSelect" required class="form-control"
                                onchange="loadDistricts(this.value)">
                            <option value="">Viloyatni tanlang</option>
                            <?php foreach ($regions as $region): ?>
                                <option value="<?= $region['id'] ?>">
                                    <?= htmlspecialchars($region['name_uz']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tuman / Shahar</label>
                        <select name="district_id" id="districtSelect" required class="form-control"
                                onchange="updateMap()">
                            <option value="">Avval viloyatni tanlang</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>To'liq manzil (ko'cha, uy, xonadon)</label>
                    <textarea name="address" id="addressInput" required class="form-control" 
                              rows="2" placeholder="Masalan: Urganch shahri, Al-Xorazmiy ko'chasi, 15-uy"
                              oninput="updateMap()"></textarea>
                </div>
                <div class="form-group">
                    <label>Xaritadan manzilni belgilash <small>(xaritadagi joyni bosing)</small></label>
                    <div id="map" style="height: 300px; border-radius: 12px; border: 2px solid #e8eaed;"></div>
                    <input type="hidden" name="latitude" id="latInput" value="41.2995">
                    <input type="hidden" name="longitude" id="lngInput" value="69.2401">
                    <p class="help-text" id="addressPreview"></p>
                </div>
            </div>

            <!-- CSRF himoya -->
            <?= csrf_field() ?>

            <!-- 3-qadam: To'lov turi -->
            <div class="checkout-section">
                <h3><i class="fas fa-credit-card"></i> 3. To'lov turi</h3>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cash" checked>
                        <div class="payment-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <strong>Naqd pul</strong>
                                <small>Yetkazib berishda naqd pul bilan to'lang</small>
                            </div>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="card">
                        <div class="payment-card">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <strong>Plastik karta</strong>
                                <small>Uzcard, Humo, Visa, Mastercard</small>
                            </div>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="transfer">
                        <div class="payment-card">
                            <i class="fas fa-university"></i>
                            <div>
                                <strong>Pul o'tkazmasi</strong>
                                <small>Bank hisob raqamiga o'tkazma</small>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-check-circle"></i> Buyurtmani tasdiqlash
            </button>
        </form>
    </div>

    <!-- Buyurtma yakuni (o'ng tomonda) -->
    <div class="checkout-summary">
        <h3>Buyurtma qilinadigan mahsulotlar</h3>
        <?php foreach ($items as $item): ?>
            <div class="order-item">
                <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" alt="">
                <div>
                    <p class="order-item-name"><?= htmlspecialchars($item['name']) ?></p>
                    <p class="order-item-qty"><?= $item['quantity'] ?> x <?= formatPrice($item['price']) ?></p>
                </div>
                <span class="order-item-total"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
            </div>
        <?php endforeach; ?>
        <hr>
        <div class="summary-row">
            <span>Jami:</span>
            <span class="total-price"><?= formatPrice($total) ?></span>
        </div>
    </div>
</div>

<!-- JavaScript: Xarita, viloyat/tuman -->
<script>
let map, marker;

// Xaritani ishga tushirish
function initMap() {
    map = L.map('map').setView([41.2995, 69.2401], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    marker = L.marker([41.2995, 69.2401], {draggable: true}).addTo(map);

    marker.on('dragend', function() {
        const pos = marker.getLatLng();
        updateCoords(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng.lat, e.latlng.lng);
    });
}

// Koordinatalarni yangilash
function updateCoords(lat, lng) {
    document.getElementById('latInput').value = lat.toFixed(7);
    document.getElementById('lngInput').value = lng.toFixed(7);
    map.setView([lat, lng], 15);

    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=uz')
        .then(r => r.json())
        .then(data => {
            document.getElementById('addressPreview').textContent = '📍 ' + (data.display_name || 'Manzil aniqlanmadi');
        });
}

// Viloyat bo'yicha tumanlarni yuklash
function loadDistricts(regionId) {
    const select = document.getElementById('districtSelect');
    select.innerHTML = '<option value="">Yuklanmoqda...</option>';
    select.disabled = true;

    fetch('<?= SITE_URL ?>/get_districts.php?region_id=' + regionId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Tumanni tanlang</option>';
            data.forEach(d => {
                select.innerHTML += '<option value="' + d.id + '">' + d.name_uz + '</option>';
            });
            select.disabled = false;
        });
}

// Manzil bo'yicha xaritani yangilash
function updateMap() {
    const address = document.getElementById('addressInput').value;
    if (address.length > 5) {
        fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(address) + ', Uzbekistan&limit=1')
            .then(r => r.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    marker.setLatLng([lat, lng]);
                    updateCoords(lat, lng);
                }
            });
    }
}

// Formani tekshirish
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const address = document.getElementById('addressInput').value.trim();
    if (!address) {
        e.preventDefault();
        alert('Iltimos, manzilingizni kiriting');
        return;
    }
});

initMap();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
