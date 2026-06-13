<?php
/**
 * footer.php - Saytning pastki qismi (FOOTER)
 * 
 * Bu fayl barcha sahifalarda bir xil:
 * - Asosiy kontentni yopish
 * - Footer ma'lumotlari
 * - JavaScript
 */
?>
</main> <!-- .container -->

<!-- ===== Footer (pastki qism) ===== -->
<footer class="main-footer">
    <div class="footer-inner">
        <div class="footer-grid">
            <!-- Sayt haqida -->
            <div class="footer-col">
                <h4><i class="fas fa-shopping-bag"></i> PhoneStore</h4>
                <p>Eng so'nggi telefonlar, eng yaxshi narxlarda. Yetkazib berish butun O'zbekiston bo'ylab.</p>
                <div class="footer-social">
                    <a href="#"><i class="fab fa-telegram"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            <!-- Ma'lumot -->
            <div class="footer-col">
                <h4>Ma'lumot</h4>
                <ul>
                    <li><a href="#">Biz haqimizda</a></li>
                    <li><a href="#">Yetkazib berish</a></li>
                    <li><a href="#">To'lov usullari</a></li>
                    <li><a href="#">Qaytarish shartlari</a></li>
                </ul>
            </div>
            <!-- Yordam -->
            <div class="footer-col">
                <h4>Yordam</h4>
                <ul>
                    <li><a href="#">Tez-tez so'raladigan savollar</a></li>
                    <li><a href="#">Biz bilan bog'lanish</a></li>
                    <li><a href="#">Maxfiylik siyosati</a></li>
                    <li><a href="#">Foydalanish shartlari</a></li>
                </ul>
            </div>
            <!-- Aloqa -->
            <div class="footer-col">
                <h4>Aloqa</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-phone"></i> +998 90 123 45 67</li>
                    <li><i class="fas fa-envelope"></i> info@phonestore.uz</li>
                    <li><i class="fas fa-map-marker-alt"></i> Toshkent sh., Yunusobod t.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> PhoneStore. Barcha huquqlar himoyalangan.</p>
        </div>
    </div>
</footer>

<!-- JavaScript: Alertlarni yopish -->
<script>
// Alertlarni yopish tugmasi
document.querySelectorAll('.alert button').forEach(btn => {
    btn.addEventListener('click', () => btn.parentElement.remove());
});

// Alertlarni avtomatik yopish (5 soniyadan so'ng)
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => el.remove());
}, 5000);
</script>
</body>
</html>
