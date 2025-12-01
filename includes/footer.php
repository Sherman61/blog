</main>
<footer class="site-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Shiya's Blog. All rights reserved.</p>
    </div>
</footer>
<script>
const menuToggle = document.getElementById('menu-toggle');
const siteHeader = document.querySelector('.site-header');
menuToggle?.addEventListener('click', () => {
    siteHeader.classList.toggle('nav-open');
});
</script>
</body>
</html>
