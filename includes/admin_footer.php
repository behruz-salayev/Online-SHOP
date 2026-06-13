<?php
/**
 * admin_footer.php - Admin panelining pastki qismi
 */
?>
    </div> <!-- .admin-content -->
</div> <!-- .admin-layout -->

<!-- JavaScript: Alertlarni yopish -->
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
