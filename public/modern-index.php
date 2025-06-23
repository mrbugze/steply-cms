<?php
require_once __DIR__ . 
    '/../config/db.php';
require_once __DIR__ . 
    '/../src/Auth/Auth.php'; // To check login status

$auth = new Auth($conn);
$isLoggedIn = $auth->isLoggedIn();
$userRole = $isLoggedIn ? $auth->getRole() : null;
$userId = $isLoggedIn ? $auth->getUserId() : null;

$pageTitle = "Course Catalog";
include __DIR__ . 
    '/../templates/partials/modern-header.php';

// Fetch all courses
try {
    $stmt = $conn->prepare("SELECT course_id, title, description, price, image_path, instructor_id FROM courses ORDER BY created_at DESC");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = []; // Set courses to empty array on error
    echo "<div class=\"alert alert-danger\">Could not load courses. Please try again later.</div>";
}

?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Transform Your Future with Expert-Led Courses</h1>
        <p class="hero-subtitle">Discover world-class learning experiences designed to accelerate your career and unlock your potential</p>
        <div class="hero-cta">
            <?php if (!$isLoggedIn): ?>
                <a href="/cms/public/register.php" class="btn btn-primary btn-lg">Start Learning Today</a>
                <a href="/cms/public/login.php" class="btn btn-outline btn-lg ml-4">Sign In</a>
            <?php else: ?>
                <a href="#courses" class="btn btn-primary btn-lg">Explore Courses</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-24 bg-secondary-50" id="features">
    <div class="container">
        <div class="text-center mb-16">
            <h2 class="reveal slide-in-up">Why Choose Steply?</h2>
            <p class="reveal slide-in-up stagger-1 text-lg text-secondary-600 max-w-2xl mx-auto">
                Experience learning like never before with our cutting-edge platform designed for modern learners
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 stagger-container">
            <div class="card reveal slide-in-up text-center">
                <div class="card-body">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="mb-4">Expert Instructors</h3>
                    <p class="text-secondary-600">Learn from industry professionals with years of real-world experience</p>
                </div>
            </div>
            
            <div class="card reveal slide-in-up text-center">
                <div class="card-body">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-4">Interactive Learning</h3>
                    <p class="text-secondary-600">Engage with hands-on projects and real-world applications</p>
                </div>
            </div>
            
            <div class="card reveal slide-in-up text-center">
                <div class="card-body">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="mb-4">Certified Learning</h3>
                    <p class="text-secondary-600">Earn recognized certificates to boost your professional credentials</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Course Catalog Section -->
<section class="py-24" id="courses">
    <div class="container">
        <div class="text-center mb-16">
            <h2 class="reveal slide-in-up">Featured Courses</h2>
            <p class="reveal slide-in-up stagger-1 text-lg text-secondary-600">
                Discover our most popular courses designed to advance your career
            </p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-semibold mb-4">No Courses Available</h3>
                <p class="text-secondary-600">Check back soon for exciting new learning opportunities!</p>
            </div>
        <?php else: ?>
            <!-- Modern Course Slider -->
            <div class="modern-course-slider">
                <div class="container">
                    <div class="swiper courseSwiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($courses as $course): ?>
                                <?php 
                                    $image_url = '/cms/public/images/placeholder.png';
                                    if (!empty($course["image_path"])) {
                                        $relative_image_path = $course["image_path"];
                                        if (file_exists(__DIR__ . '/..' . $relative_image_path)) {
                                            $image_url = $relative_image_path;
                                        } else {
                                            $image_url = $relative_image_path;
                                        }
                                    }
                                    $details_url = "/cms/public/view_course_details.php?id=" . $course["course_id"];
                                    $login_redirect_url = "/cms/public/login.php?redirect=" . urlencode($details_url);
                                ?>
                                <div class="swiper-slide">
                                    <div class="modern-course-card course-card">
                                        <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo htmlspecialchars($course["title"]); ?>">
                                        <div class="modern-course-overlay">
                                            <h5 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                            <p class="text-accent-400 font-bold mb-3">$<?php echo number_format($course["price"], 2); ?></p>
                                            <p class="text-sm mb-4 opacity-90"><?php echo nl2br(htmlspecialchars(substr($course["description"], 0, 100))) . (strlen($course["description"]) > 100 ? '...' : ''); ?></p>
                                            <div>
                                                <?php if ($isLoggedIn && $userRole === 'student'): ?>
                                                    <a href="<?php echo $details_url; ?>" class="btn btn-primary btn-sm">View & Enroll</a>
                                                <?php else: ?>
                                                    <a href="<?php echo $login_redirect_url; ?>" class="btn btn-outline btn-sm">Login to Enroll</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Navigation arrows -->
                        <div class="swiper-button-next"></div>
                        <div class="swiper-button-prev"></div>
                    </div>
                </div>
            </div>

            <!-- Course Grid -->
            <div class="mt-16">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 stagger-container">
                    <?php foreach (array_slice($courses, 0, 6) as $index => $course): ?>
                        <div class="card course-card reveal slide-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <?php 
                            $image_url = '/cms/public/images/placeholder.png';
                            if (!empty($course["image_path"])) {
                                $relative_image_path = $course["image_path"];
                                if (file_exists(__DIR__ . '/..' . $relative_image_path)) {
                                    $image_url = $relative_image_path;
                                } else {
                                    $image_url = $relative_image_path;
                                }
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_url); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($course["title"]); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course["title"]); ?></h5>
                                <p class="card-text text-secondary-600"><?php echo nl2br(htmlspecialchars(substr($course["description"], 0, 120))) . (strlen($course["description"]) > 120 ? '...' : ''); ?></p>
                                <div class="flex justify-between items-center mt-6">
                                    <span class="text-2xl font-bold text-primary-600">$<?php echo number_format($course["price"], 2); ?></span>
                                    <?php 
                                    $details_url = "/cms/public/view_course_details.php?id=" . $course["course_id"];
                                    $login_redirect_url = "/cms/public/login.php?redirect=" . urlencode($details_url);
                                    ?>
                                    <?php if ($isLoggedIn && $userRole === 'student'): ?>
                                        <a href="<?php echo $details_url; ?>" class="btn btn-primary">View Details</a>
                                    <?php elseif (!$isLoggedIn): ?>
                                        <a href="<?php echo $login_redirect_url; ?>" class="btn btn-secondary">Login to Enroll</a>
                                    <?php else: ?>
                                        <a href="<?php echo $details_url; ?>" class="btn btn-secondary">View Details</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-24 bg-primary-600 text-white">
    <div class="container">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
            <div class="reveal slide-in-up">
                <div class="text-4xl font-bold mb-2" data-counter="1000">0</div>
                <div class="text-primary-200">Students Enrolled</div>
            </div>
            <div class="reveal slide-in-up stagger-1">
                <div class="text-4xl font-bold mb-2" data-counter="50">0</div>
                <div class="text-primary-200">Expert Instructors</div>
            </div>
            <div class="reveal slide-in-up stagger-2">
                <div class="text-4xl font-bold mb-2" data-counter="100">0</div>
                <div class="text-primary-200">Courses Available</div>
            </div>
            <div class="reveal slide-in-up stagger-3">
                <div class="text-4xl font-bold mb-2" data-counter="95">0</div>
                <div class="text-primary-200">Success Rate %</div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-24 bg-gradient-to-r from-primary-600 to-primary-800 text-white">
    <div class="container text-center">
        <div class="max-w-3xl mx-auto">
            <h2 class="text-4xl font-bold mb-6 reveal slide-in-up">Ready to Start Your Learning Journey?</h2>
            <p class="text-xl mb-8 text-primary-100 reveal slide-in-up stagger-1">
                Join thousands of students who have transformed their careers with our expert-led courses
            </p>
            <div class="reveal slide-in-up stagger-2">
                <?php if (!$isLoggedIn): ?>
                    <a href="/cms/public/register.php" class="btn btn-lg bg-white text-primary-600 hover:bg-primary-50 mr-4">Get Started Free</a>
                    <a href="/cms/public/login.php" class="btn btn-lg btn-outline border-white text-white hover:bg-white hover:text-primary-600">Sign In</a>
                <?php else: ?>
                    <a href="#courses" class="btn btn-lg bg-white text-primary-600 hover:bg-primary-50">Explore More Courses</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Include Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<!-- Modern Interactions JS -->
<script src="/cms/public/js/modern-interactions.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Initialize Swiper
    const swiper = new Swiper(".courseSwiper", {
        loop: true,
        slidesPerView: 1,
        spaceBetween: 30,
        autoplay: {
            delay: 4000,
            disableOnInteraction: false,
            pauseOnMouseEnter: true
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev"
        },
        breakpoints: {
            576: { slidesPerView: 1.2, spaceBetween: 20 },
            768: { slidesPerView: 2, spaceBetween: 30 },
            1024: { slidesPerView: 3, spaceBetween: 30 },
            1400: { slidesPerView: 4, spaceBetween: 30 },
        },
        effect: 'slide',
        speed: 800,
    });

    // Animate counters when they come into view
    const counters = document.querySelectorAll('[data-counter]');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.dataset.counter);
                CMSUtils.animateCounter(counter, target, 2000);
                counterObserver.unobserve(counter);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => counterObserver.observe(counter));

    // Enhanced scroll animations
    const revealElements = document.querySelectorAll('.reveal');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(el => revealObserver.observe(el));

    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero');
        if (hero) {
            const rate = scrolled * -0.3;
            hero.style.transform = `translateY(${rate}px)`;
        }
    });
});
</script>

<?php
include __DIR__ . 
    '/../templates/partials/footer.php';
?>

