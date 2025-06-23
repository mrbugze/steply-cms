<!-- Modern Footer HTML -->
<footer class="site-footer">
    <div class="footer-background">
        <div class="footer-pattern"></div>
        <div class="footer-gradient"></div>
    </div>
    
    <div class="container">
        <div class="footer-content">
            <!-- Main Footer Content -->
            <div class="footer-main">
                <div class="footer-section footer-brand">
                    <div class="brand-logo">
                        <h3>Steply</h3>
                        <span class="brand-tagline">Learn Without Limits</span>
                    </div>
                    <p class="brand-description">
                        Empowering learners worldwide with expert-led courses and cutting-edge educational technology. 
                        Join our community and transform your career today.
                    </p>
                    <div class="social-links">
                        <a href="https://twitter.com/steply" class="social-link twitter" aria-label="Follow us on Twitter">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <a href="https://facebook.com/steply" class="social-link facebook" aria-label="Follow us on Facebook">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="mailto:contact@steply.com" class="social-link email" aria-label="Send us an email">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="#courses">Browse Courses</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="/cms/public/register.php">Sign Up</a></li>
                        <li><a href="/cms/public/login.php">Login</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Support</h4>
                    <ul class="footer-links">
                        <li><a href="/cms/public/help.php">Help Center</a></li>
                        <li><a href="/cms/public/contact.php">Contact Us</a></li>
                        <li><a href="/cms/public/faq.php">FAQ</a></li>
                        <li><a href="/cms/public/privacy.php">Privacy Policy</a></li>
                        <li><a href="/cms/public/terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Newsletter</h4>
                    <p class="newsletter-description">Stay updated with our latest courses and educational content.</p>
                    <form class="newsletter-form" action="/cms/public/newsletter.php" method="POST">
                        <div class="newsletter-input-group">
                            <input type="email" name="email" placeholder="Enter your email" class="newsletter-input" required>
                            <button type="submit" class="newsletter-btn">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22,2 15,22 11,13 2,9 22,2"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="copyright">
                        &copy; 2024 Steply. All rights reserved. Made with ❤️ for learners worldwide.
                    </p>
                    <div class="footer-bottom-links">
                        <a href="/cms/public/privacy.php">Privacy</a>
                        <a href="/cms/public/terms.php">Terms</a>
                        <a href="/cms/public/cookies.php">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scroll to top button -->
    <button class="scroll-to-top" id="scrollToTop" aria-label="Scroll to top">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18,15 12,9 6,15"/>
        </svg>
    </button>
</footer>
<script>document.addEventListener("DOMContentLoaded", function() {
    const tables = document.querySelectorAll("table");

    tables.forEach(table => {
        const rows = table.querySelectorAll("tbody tr");
        rows.forEach((row, index) => {
            row.classList.add("js-animate-row");
            row.style.animationDelay = `${index * 0.1}s`; // Stagger by 0.1 seconds
        });
    });
});


</script>
</body></html>