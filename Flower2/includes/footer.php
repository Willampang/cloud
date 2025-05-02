<?php if(!isset($_SESSION['admin_id'])): ?>
        </div> <!-- Close main-content div for regular users -->
    </main>
    <footer>
        <div class="container">
            <div class="footer-columns">
                <div class="footer-column">
                    <h3>About Us</h3>
                    <p>GraduationShop provides high-quality graduation gifts and memorabilia to help celebrate your achievement.</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="index.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="shipping.php">Shipping Policy</a></li>
                        <li><a href="returns.php">Returns & Refunds</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Graduation Ave, College Town</p>
                    <p><i class="fas fa-phone"></i> (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> info@graduationshop.com</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> GraduationShop. All Rights Reserved.</p>
            </div>
        </div>
    </footer>
<?php else: ?>
    </main>
    <footer class="admin-footer">
        <div class="container" style="text-align: center;">
            <p>&copy; <?php echo date("Y"); ?> GraduationShop Admin Panel. All Rights Reserved.</p>
        </div>
    </footer>
<?php endif; ?>
    <script src="js/script.js"></script>
</body>
</html>