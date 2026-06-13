<?php
/**
 * seller_footer.php - Sotuvchi panelining pastki qismi
 */
?>
    </div>
</div>

<script>
document.querySelectorAll('.alert button').forEach(btn => {
    btn.addEventListener('click', () => btn.parentElement.remove());
});
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => el.remove());
}, 5000);
</script>
</body>
</html>
