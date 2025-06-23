// Enhanced Microinteractions and Performance Optimizations
class MicroInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.setupButtonInteractions();
        this.setupFormInteractions();
        this.setupMenuAnimations();
        this.setupHoverEffects();
        this.setupLoadingStates();
        this.setupPerformanceOptimizations();
    }

    // Enhanced button interactions
    setupButtonInteractions() {
        document.querySelectorAll('.btn').forEach(button => {
            // Ripple effect
            button.addEventListener('click', this.createRipple.bind(this));
            
            // Press feedback
            button.addEventListener('mousedown', () => {
                button.style.transform = 'scale(0.98)';
            });
            
            button.addEventListener('mouseup', () => {
                button.style.transform = 'scale(1)';
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = 'scale(1)';
            });
            
            // Magnetic effect for large buttons
            if (button.classList.contains('btn-lg')) {
                this.addMagneticEffect(button);
            }
        });
    }

    createRipple(e) {
        const button = e.currentTarget;
        const ripple = document.createElement('span');
        const rect = button.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
        `;
        
        ripple.classList.add('ripple');
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    addMagneticEffect(element) {
        element.addEventListener('mousemove', (e) => {
            const rect = element.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            element.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
        });
        
        element.addEventListener('mouseleave', () => {
            element.style.transform = 'translate(0, 0)';
        });
    }

    // Enhanced form interactions
    setupFormInteractions() {
        // Floating labels with smooth animations
        document.querySelectorAll('.form-control').forEach(input => {
            const wrapper = this.createFloatingLabelWrapper(input);
            
            input.addEventListener('focus', () => {
                wrapper.classList.add('focused');
                this.animateLabel(wrapper, true);
            });
            
            input.addEventListener('blur', () => {
                wrapper.classList.remove('focused');
                if (!input.value) {
                    this.animateLabel(wrapper, false);
                }
            });
            
            // Check initial state
            if (input.value) {
                this.animateLabel(wrapper, true);
            }
        });

        // Real-time validation feedback
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('input', this.debounce(() => {
                this.validateEmail(input);
            }, 300));
        });

        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('input', this.debounce(() => {
                this.validatePassword(input);
            }, 300));
        });

        // Form submission with loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                if (submitBtn) {
                    this.setLoadingState(submitBtn, true);
                }
            });
        });
    }

    createFloatingLabelWrapper(input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'floating-label-wrapper';
        wrapper.style.cssText = `
            position: relative;
            margin-bottom: 1.5rem;
        `;
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        
        const label = input.previousElementSibling;
        if (label && label.tagName === 'LABEL') {
            wrapper.appendChild(label);
            label.style.cssText = `
                position: absolute;
                left: 1rem;
                top: 50%;
                transform: translateY(-50%);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                pointer-events: none;
                color: var(--text-tertiary);
                background: var(--bg-primary);
                padding: 0 0.25rem;
                z-index: 1;
            `;
        }
        
        return wrapper;
    }

    animateLabel(wrapper, float) {
        const label = wrapper.querySelector('label');
        if (label) {
            if (float) {
                label.style.transform = 'translateY(-1.5rem) scale(0.85)';
                label.style.color = 'var(--primary-600)';
            } else {
                label.style.transform = 'translateY(-50%) scale(1)';
                label.style.color = 'var(--text-tertiary)';
            }
        }
    }

    validateEmail(input) {
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value);
        this.setValidationState(input, isValid, 'Please enter a valid email address');
    }

    validatePassword(input) {
        const isValid = input.value.length >= 8;
        this.setValidationState(input, isValid, 'Password must be at least 8 characters');
    }

    setValidationState(input, isValid, message) {
        const wrapper = input.closest('.floating-label-wrapper') || input.parentNode;
        let feedback = wrapper.querySelector('.validation-feedback');
        
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'validation-feedback';
            feedback.style.cssText = `
                font-size: 0.875rem;
                margin-top: 0.25rem;
                transition: all 0.3s ease;
                opacity: 0;
                transform: translateY(-10px);
            `;
            wrapper.appendChild(feedback);
        }
        
        if (input.value) {
            if (isValid) {
                input.style.borderColor = 'var(--success-500)';
                input.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                feedback.style.opacity = '0';
            } else {
                input.style.borderColor = 'var(--danger-500)';
                input.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                feedback.textContent = message;
                feedback.style.color = 'var(--danger-500)';
                feedback.style.opacity = '1';
                feedback.style.transform = 'translateY(0)';
            }
        } else {
            input.style.borderColor = 'var(--border-color)';
            input.style.boxShadow = 'none';
            feedback.style.opacity = '0';
        }
    }

    setLoadingState(button, loading) {
        if (loading) {
            button.disabled = true;
            button.style.position = 'relative';
            button.style.color = 'transparent';
            
            const spinner = document.createElement('div');
            spinner.className = 'button-spinner';
            spinner.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 20px;
                height: 20px;
                border: 2px solid rgba(255, 255, 255, 0.3);
                border-top: 2px solid white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            `;
            
            button.appendChild(spinner);
        } else {
            button.disabled = false;
            button.style.color = '';
            const spinner = button.querySelector('.button-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
    }

    // Enhanced menu animations
    setupMenuAnimations() {
        const mobileToggle = document.querySelector('.mobile-menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        if (mobileToggle && navLinks) {
            mobileToggle.addEventListener('click', () => {
                const isActive = navLinks.classList.contains('active');
                
                if (!isActive) {
                    navLinks.classList.add('active');
                    this.animateMenuItems(navLinks, true);
                } else {
                    this.animateMenuItems(navLinks, false);
                    setTimeout(() => {
                        navLinks.classList.remove('active');
                    }, 300);
                }
            });
        }
    }

    animateMenuItems(menu, show) {
        const items = menu.querySelectorAll('li');
        items.forEach((item, index) => {
            if (show) {
                item.style.opacity = '0';
                item.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 50);
            } else {
                item.style.transition = 'all 0.2s ease';
                item.style.opacity = '0';
                item.style.transform = 'translateY(-10px)';
            }
        });
    }

    // Enhanced hover effects
    setupHoverEffects() {
        // Card hover effects with 3D transform
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.willChange = 'transform';
                card.style.transform = 'translateY(-8px) scale(1.02)';
                card.style.boxShadow = 'var(--shadow-2xl)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.willChange = 'auto';
                card.style.transform = 'translateY(0) scale(1)';
                card.style.boxShadow = 'var(--shadow)';
            });
            
            // Tilt effect
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = (y - centerY) / 20;
                const rotateY = (centerX - x) / 20;
                
                card.style.transform = `
                    perspective(1000px) 
                    rotateX(${rotateX}deg) 
                    rotateY(${rotateY}deg) 
                    translateZ(10px)
                    scale(1.02)
                `;
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateZ(0) scale(1)';
            });
        });

        // Link hover effects
        document.querySelectorAll('a:not(.btn)').forEach(link => {
            link.addEventListener('mouseenter', () => {
                link.style.transform = 'translateY(-1px)';
            });
            
            link.addEventListener('mouseleave', () => {
                link.style.transform = 'translateY(0)';
            });
        });
    }

    // Loading states and skeleton screens
    setupLoadingStates() {
        // Create skeleton loader for course cards
        this.createSkeletonLoaders();
        
        // Image lazy loading with fade-in effect
        this.setupLazyLoading();
    }

    createSkeletonLoaders() {
        const style = document.createElement('style');
        style.textContent = `
            .skeleton {
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: loading 1.5s infinite;
            }
            
            @keyframes loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
            
            .skeleton-card {
                border-radius: var(--radius-lg);
                overflow: hidden;
                margin-bottom: 1.5rem;
            }
            
            .skeleton-image {
                height: 200px;
                width: 100%;
            }
            
            .skeleton-text {
                height: 1rem;
                margin: 0.5rem 1rem;
                border-radius: 0.25rem;
            }
            
            .skeleton-title {
                height: 1.5rem;
                margin: 1rem;
                border-radius: 0.25rem;
            }
        `;
        document.head.appendChild(style);
    }

    setupLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.style.opacity = '0';
                    img.src = img.dataset.src;
                    img.onload = () => {
                        img.style.transition = 'opacity 0.5s ease';
                        img.style.opacity = '1';
                    };
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // Performance optimizations
    setupPerformanceOptimizations() {
        // Debounced scroll handler
        let ticking = false;
        const scrollHandler = () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        };
        
        window.addEventListener('scroll', scrollHandler, { passive: true });
        
        // Intersection Observer for animations
        this.setupIntersectionObserver();
        
        // Preload critical resources
        this.preloadCriticalResources();
    }

    handleScroll() {
        const scrollY = window.scrollY;
        
        // Update scroll progress
        const scrollProgress = document.getElementById('scrollProgress');
        if (scrollProgress) {
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrollY / scrollHeight) * 100;
            scrollProgress.style.width = `${progress}%`;
        }
        
        // Parallax effects (throttled)
        const hero = document.querySelector('.hero');
        if (hero && scrollY < window.innerHeight) {
            hero.style.transform = `translateY(${scrollY * 0.3}px)`;
        }
    }

    setupIntersectionObserver() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Remove observer after animation to improve performance
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.reveal').forEach(el => {
            observer.observe(el);
        });
    }

    preloadCriticalResources() {
        // Preload critical fonts
        const fontLinks = [
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
            'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap'
        ];
        
        fontLinks.forEach(href => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'style';
            link.href = href;
            document.head.appendChild(link);
        });
    }

    // Utility functions
    debounce(func, wait) {
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

    throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Enhanced notification system
class NotificationSystem {
    constructor() {
        this.container = this.createContainer();
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            pointer-events: none;
        `;
        document.body.appendChild(container);
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1rem 1.5rem;
            margin-bottom: 0.5rem;
            box-shadow: var(--shadow-lg);
            pointer-events: auto;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            max-width: 400px;
        `;
        
        // Add icon based on type
        const icon = this.getIcon(type);
        notification.innerHTML = `
            ${icon}
            <span>${message}</span>
            <button class="notification-close" style="
                background: none;
                border: none;
                font-size: 1.25rem;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.2s;
                margin-left: auto;
            ">&times;</button>
        `;
        
        this.container.appendChild(notification);
        
        // Animate in
        requestAnimationFrame(() => {
            notification.style.transform = 'translateX(0)';
        });
        
        // Auto remove
        const autoRemove = setTimeout(() => {
            this.remove(notification);
        }, duration);
        
        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            clearTimeout(autoRemove);
            this.remove(notification);
        });
        
        return notification;
    }

    getIcon(type) {
        const icons = {
            success: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        return icons[type] || icons.info;
    }

    remove(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Initialize enhanced interactions
document.addEventListener('DOMContentLoaded', () => {
    new MicroInteractions();
    window.notifications = new NotificationSystem();
});

// Export for global use
window.MicroInteractions = MicroInteractions;
window.NotificationSystem = NotificationSystem;

