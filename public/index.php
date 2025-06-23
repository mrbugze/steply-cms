<?php
require_once __DIR__ . 
'/../config/db.php';
require_once __DIR__ . 
'/../src/Auth/Auth.php';

$auth = new Auth($conn);
$isLoggedIn = $auth->isLoggedIn();
$userRole = $isLoggedIn ? $auth->getRole() : null;
$userId = $isLoggedIn ? $auth->getUserId() : null;

$pageTitle = "Steply - Learn Without Limits";

// Get selected category filter
$selectedCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch categories for filter
try {
    $stmt = $conn->prepare("SELECT category_id, name, color, icon FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    $categories = [];
}

// Fetch courses with category information
try {
    if ($selectedCategory > 0) {
        $stmt = $conn->prepare("\n            SELECT c.course_id, c.title, c.description, c.price, c.image_path, c.instructor_id,\n                   cat.name as category_name, cat.color as category_color, cat.icon as category_icon,\n                   u.username AS instructor_name\n            FROM courses c \n            LEFT JOIN categories cat ON c.category_id = cat.category_id\n            LEFT JOIN users u ON c.instructor_id = u.user_id\n            WHERE c.category_id = :category_id\n            ORDER BY c.created_at DESC\n        ");
        $stmt->bindParam(':category_id', $selectedCategory, PDO::PARAM_INT);
    } else {
        $stmt = $conn->prepare("\n            SELECT c.course_id, c.title, c.description, c.price, c.image_path, c.instructor_id,\n                   cat.name as category_name, cat.color as category_color, cat.icon as category_icon,\n                   u.username AS instructor_name\n            FROM courses c \n            LEFT JOIN categories cat ON c.category_id = cat.category_id\n            LEFT JOIN users u ON c.instructor_id = u.user_id\n            ORDER BY c.created_at DESC\n        ");
    }
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

// Get course statistics
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_courses FROM courses");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCourses = $stats['total_courses'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM users WHERE role = 'student'");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalStudents = $stats['total_students'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total_instructors FROM users WHERE role = 'instructor'");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalInstructors = $stats['total_instructors'];
} catch (PDOException $e) {
    $totalCourses = 0;
    $totalStudents = 0;
    $totalInstructors = 0;
}

include __DIR__ . '/../templates/partials/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-background">
        <div class="floating-elements">
            <div class="floating-element" style="--delay: 0s; --duration: 20s;"></div>
            <div class="floating-element" style="--delay: 5s; --duration: 25s;"></div>
            <div class="floating-element" style="--delay: 10s; --duration: 30s;"></div>
        </div>
    </div>
    
    <div class="container">
        <div class="hero-content" style="margin:0 auto;">
            <div class="hero-text">
                <h1 class="hero-title">
                    <span class="title-line">Learn Without</span>
                    <span class="title-line highlight">Limits</span>
                </h1>
                <p class="hero-subtitle">
                    Master new skills with expert-led courses designed for the modern learner. 
                    Join thousands of students already transforming their careers.
                </p>
                <div class="hero-cta">
                    <?php if (!$isLoggedIn): ?>
                        <a href="/cms/public/register.php" class="btn btn-primary btn-large ripple-effect">
                            <span>Start Learning Today</span>
                            <div class="ripple"></div>
                        </a>
                        <a href="#courses" class="btn btn-outline btn-large ripple-effect">
                            <span>Browse Courses</span>
                            <div class="ripple"></div>
                        </a>
                    <?php else: ?>
                        <a href="#courses" class="btn btn-primary btn-large ripple-effect">
                            <span>Explore Courses</span>
                            <div class="ripple"></div>
                        </a>
                        <?php if ($userRole === 'student'): ?>
                            <a href="/cms/student/index.php" class="btn btn-outline btn-large ripple-effect">
                                <span>My Dashboard</span>
                                <div class="ripple"></div>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="hero-stats" style="margin-top: 30px;">
                <div class="stat-item reveal-on-scroll">
                    <div class="stat-number" data-target="<?php echo $totalCourses; ?>"><?php echo $totalCourses; ?></div>
                    <div class="stat-label">Courses</div>
                </div>
                <div class="stat-item reveal-on-scroll">
                    <div class="stat-number" data-target="<?php echo $totalStudents; ?>"><?php echo $totalStudents; ?></div>
                    <div class="stat-label">Students</div>
                </div>
                <div class="stat-item reveal-on-scroll">
                    <div class="stat-number" data-target="<?php echo $totalInstructors; ?>"><?php echo $totalInstructors; ?></div>
                    <div class="stat-label">Instructors</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="scroll-indicator">
        <div class="scroll-arrow"></div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title reveal-on-scroll">Why Choose Steply?</h2>
            <p class="section-subtitle reveal-on-scroll">Experience learning like never before with our cutting-edge platform</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card reveal-on-scroll" style="--delay: 0.1s;">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h3 class="feature-title">Expert Instructors</h3>
                <p class="feature-description">Learn from industry professionals with years of real-world experience</p>
            </div>
            
            <div class="feature-card reveal-on-scroll" style="--delay: 0.2s;">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                </div>
                <h3 class="feature-title">Flexible Learning</h3>
                <p class="feature-description">Study at your own pace with lifetime access to course materials</p>
            </div>
            
            <div class="feature-card reveal-on-scroll" style="--delay: 0.3s;">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                </div>
                <h3 class="feature-title">Interactive Content</h3>
                <p class="feature-description">Engage with hands-on projects and real-world applications</p>
            </div>
            
            <div class="feature-card reveal-on-scroll" style="--delay: 0.4s;">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        <path d="M9 14l2 2 4-4"/>
                    </svg>
                </div>
                <h3 class="feature-title">Certificates</h3>
                <p class="feature-description">Earn recognized certificates to showcase your new skills</p>
            </div>
        </div>
    </div>
</section>

<!-- Courses Section -->
<section class="courses" id="courses">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title reveal-on-scroll">Explore Our Courses</h2>
            <p class="section-subtitle reveal-on-scroll">Discover courses tailored to your learning goals</p>
        </div>
        
        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
        <div class="category-filter reveal-on-scroll">
            <div class="filter-tabs">
                <a href="?category=0" class="filter-tab <?php echo $selectedCategory === 0 ? 'active' : ''; ?>">
                    All Courses
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="?category=<?php echo $category['category_id']; ?>" 
                       class="filter-tab <?php echo $selectedCategory === (int)$category['category_id'] ? 'active' : ''; ?>"
                       style="--category-color: <?php echo htmlspecialchars($category['color']); ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Course Grid -->
        <div class="courses-grid">
            <?php if (empty($courses)): ?>
                <div class="no-courses">
                    <div class="no-courses-icon">📚</div>
                    <h3>No courses available</h3>
                    <p>Check back soon for new courses!</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $index => $course): ?>
                    <div class="course-card reveal-on-scroll" style="--delay: <?php echo ($index % 6) * 0.1; ?>s;">
                        <?php if (!empty($course['category_name'])): ?>
                            <div class="course-category" style="background-color: <?php echo htmlspecialchars($course['category_color']); ?>">
                                <?php echo htmlspecialchars($course['category_name']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="course-image">
                            <?php 
                            if (!empty($course["image_path"])) {
                                $relative_image_path = $course["image_path"];
                                if (strpos($relative_image_path, '/uploads/') !== 0) {
                                    $relative_image_path = '/uploads/' . ltrim($relative_image_path, '/');
                                }
                                if (file_exists(__DIR__ . '/..' . $relative_image_path)) {
                                    $image_url = '/cms' . $relative_image_path;
                                }
                            }
                            ?>
                             <?php 
                        $image_url = 'https://archive.org/download/placeholder-image//placeholder-image.jpg'; // Default placeholder
                        if (!empty($course["image_path"])) {
                            $relative_image_path = $course["image_path"];
                            // Ensure the path starts with /uploads/ if it's a relative path from the project root
                           
                            if (file_exists(__DIR__ . '/..' . $relative_image_path)) {
                                $image_url = $relative_image_path;
                            }else{                                $image_url = $relative_image_path;}
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" style="
    width: 430px;
    height: 290px;
    " alt="<?php echo htmlspecialchars($course["title"]); ?>">
                            <div class="course-overlay">
                                <div class="course-price">$<?php echo number_format($course['price'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="course-content">
                            <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="course-instructor">
                                by <?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown'); ?>
                            </p>
                            <p class="course-description">
                                <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . (strlen($course['description']) > 120 ? '...' : ''); ?>
                            </p>
                            
                            <div class="course-actions">
                                <?php 
                                $details_url = "/cms/public/view_course_details.php?id=" . $course["course_id"];
                                $login_redirect_url = "/cms/public/login.php?redirect=" . urlencode($details_url);
                                ?>
                                
                                <?php if ($isLoggedIn && $userRole === 'student'): ?>
                                    <a href="<?php echo $details_url; ?>" class="btn btn-primary ripple-effect">
                                        <span>View Details</span>
                                        <div class="ripple"></div>
                                    </a>
                                <?php elseif (!$isLoggedIn): ?>
                                    <a href="<?php echo $login_redirect_url; ?>" class="btn btn-outline ripple-effect">
                                        <span>Login to Enroll</span>
                                        <div class="ripple"></div>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo $details_url; ?>" class="btn btn-outline ripple-effect">
                                        <span>View Details</span>
                                        <div class="ripple"></div>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- About Us Section HTML -->
<section class="about-us" id="about">
    <div class="about-background">
        <div class="about-pattern"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
        </div>
    </div>
    
    <div class="container">
        <div class="about-content">
            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title reveal-on-scroll">About Steply</h2>
                <p class="section-subtitle reveal-on-scroll">
                    Revolutionizing education through innovative technology and expert instruction
                </p>
            </div>
            
            <!-- Main About Content -->
            <div class="about-main">
                <div class="about-text reveal-on-scroll" style="--delay: 0.2s;">
                    <div class="about-intro">
                        <h3 class="about-heading">Our Mission</h3>
                        <p class="about-description">
                            At Steply, we believe that quality education should be accessible to everyone, everywhere. 
                            Our platform connects passionate learners with world-class instructors, creating an 
                            environment where knowledge flows freely and skills are developed through practical, 
                            hands-on experience.
                        </p>
                    </div>
                    
                    <div class="about-values">
                        <h3 class="about-heading">What We Stand For</h3>
                        <div class="values-grid">
                            <div class="value-item reveal-on-scroll" style="--delay: 0.3s;">
                                <div class="value-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 12l2 2 4-4"/>
                                        <path d="M21 12c-1 0-3-1-3-3s2-3 3-3 3 1 3 3-2 3-3 3"/>
                                        <path d="M3 12c1 0 3-1 3-3s-2-3-3-3-3 1-3 3 2 3 3 3"/>
                                        <path d="M3 12c0 5.5 4.5 10 10 10s10-4.5 10-10"/>
                                    </svg>
                                </div>
                                <h4>Excellence</h4>
                                <p>We maintain the highest standards in course quality and student experience.</p>
                            </div>
                            
                            <div class="value-item reveal-on-scroll" style="--delay: 0.4s;">
                                <div class="value-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <h4>Community</h4>
                                <p>Building a supportive global community of learners and educators.</p>
                            </div>
                            
                            <div class="value-item reveal-on-scroll" style="--delay: 0.5s;">
                                <div class="value-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                                    </svg>
                                </div>
                                <h4>Innovation</h4>
                                <p>Continuously evolving our platform with cutting-edge technology.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="about-visual reveal-on-scroll" style="--delay: 0.6s;">
                    <div class="visual-container">
                        <div class="stats-showcase">
                            <div class="stat-card">
                                <div class="stat-number" data-target="50000">50000</div>
                                <div class="stat-label">Happy Students</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" data-target="500">1412</div>
                                <div class="stat-label">Expert Instructors</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" data-target="1000">2431</div>
                                <div class="stat-label">Courses Available</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number" data-target="95">95</div>
                                <div class="stat-label">Success Rate %</div>
                            </div>
                        </div>
                        
                        <div class="achievement-badges">
                            <div class="badge">
                                <div class="badge-icon">🏆</div>
                                <span>Top Rated Platform</span>
                            </div>
                            <div class="badge">
                                <div class="badge-icon">🌟</div>
                                <span>5-Star Reviews</span>
                            </div>
                            <div class="badge">
                                <div class="badge-icon">🚀</div>
                                <span>Fast Growing</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Team Section -->
            <div class="team-section reveal-on-scroll" style="--delay: 0.7s;">
                <h3 class="team-title">Meet Our Leadership</h3>
                <div class="team-grid">
                    <div class="team-member reveal-on-scroll" style="--delay: 0.8s;">
                        <div class="member-avatar">
                            <div class="avatar-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                        </div>
                        <h4 class="member-name">Sarah Johnson</h4>
                        <p class="member-role">CEO & Founder</p>
                        <p class="member-bio">Former education director with 15+ years of experience in online learning.</p>
                    </div>
                    
                    <div class="team-member reveal-on-scroll" style="--delay: 0.9s;">
                        <div class="member-avatar">
                            <div class="avatar-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                        </div>
                        <h4 class="member-name">Michael Chen</h4>
                        <p class="member-role">CTO</p>
                        <p class="member-bio">Tech innovator passionate about creating seamless learning experiences.</p>
                    </div>
                    
                    <div class="team-member reveal-on-scroll" style="--delay: 1.0s;">
                        <div class="member-avatar">
                            <div class="avatar-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                        </div>
                        <h4 class="member-name">Emily Rodriguez</h4>
                        <p class="member-role">Head of Education</p>
                        <p class="member-bio">Curriculum expert dedicated to delivering world-class educational content.</p>
                    </div>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="about-cta reveal-on-scroll" style="--delay: 1.1s;">
                <h3 class="cta-title">Ready to Join Our Learning Community?</h3>
                <p class="cta-description">
                    Start your journey with thousands of students who have already transformed their careers with Steply.
                </p>
                <div class="cta-buttons">
                    <a href="/cms/public/register.php" class="btn btn-primary btn-large ripple-effect">
                        <span>Start Learning Today</span>
                        <div class="ripple"></div>
                    </a>
                    <a href="#courses" class="btn btn-outline btn-large ripple-effect">
                        <span>Explore Courses</span>
                        <div class="ripple"></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="container">
        <div class="cta-content reveal-on-scroll">
            <h2 class="cta-title">Ready to Start Your Learning Journey?</h2>
            <p class="cta-subtitle">Join thousands of students who have already transformed their careers with Steply</p>
            <?php if (!$isLoggedIn): ?>
                <a href="/cms/public/register.php" class="btn btn-primary btn-large ripple-effect">
                    <span>Get Started Now</span>
                    <div class="ripple"></div>
                </a>
            <?php else: ?>
                <a href="#courses" class="btn btn-primary btn-large ripple-effect">
                    <span>Explore More Courses</span>
                    <div class="ripple"></div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modern Footer HTML -->
<?php include __DIR__ . '/../templates/partials/footer.php';?>

<!-- Include modern JavaScript -->
<script>// Enhanced JavaScript for animations and interactions
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for reveal animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                
                // Trigger counter animation for stat numbers
                if (entry.target.classList.contains('stat-number')) {
                    animateCounter(entry.target);
                }
            }
        });
    }, observerOptions);

    // Observe all reveal elements
    document.querySelectorAll('.reveal-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Counter animation function
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 16);
    }

    // Ripple effect for buttons
    document.querySelectorAll('.ripple-effect').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = this.querySelector('.ripple');
            if (ripple) {
                ripple.remove();
            }

            const newRipple = document.createElement('div');
            newRipple.classList.add('ripple');
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            newRipple.style.width = newRipple.style.height = size + 'px';
            newRipple.style.left = x + 'px';
            newRipple.style.top = y + 'px';
            
            this.appendChild(newRipple);
        });
    });

    // Scroll to top functionality
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (scrollToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });

        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Header scroll effect
    const header = document.querySelector('.site-header');
    if (header) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Parallax effect for floating elements
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.floating-element, .shape');
        
        parallaxElements.forEach((element, index) => {
            const speed = 0.5 + (index * 0.1);
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    });

    // Feature cards hover effect enhancement
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.setProperty('--hover-scale', '1.05');
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.setProperty('--hover-scale', '1');
        });
    });

    // Newsletter form enhancement
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Add loading state
            const button = this.querySelector('.newsletter-btn');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<div class="spinner"></div>';
            button.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                button.innerHTML = '✓';
                button.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    button.style.background = '';
                    this.querySelector('input[type="email"]').value = '';
                }, 2000);
            }, 1000);
        });
    }

    // Add CSS for spinner
    const style = document.createElement('style');
    style.textContent = `
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});

// Additional utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Optimized scroll handler
const optimizedScrollHandler = debounce(() => {
    // Handle scroll-based animations here
}, 16);

window.addEventListener('scroll', optimizedScrollHandler);

</script>

