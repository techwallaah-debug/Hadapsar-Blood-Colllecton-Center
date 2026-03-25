// ====== SPLIDE CAROUSEL INITIALIZATION ======
document.addEventListener('DOMContentLoaded', function() {
    // Testimonial Carousel
    if (document.querySelector('.testimonial-carousel')) {
        new Splide('.testimonial-carousel', {
            type: 'carousel',
            perPage: 3,
            perMove: 1,
            gap: '2rem',
            autoplay: true,
            interval: 5000,
            pagination: true,
            arrows: true,
            speed: 800,
            breakpoints: {
                768: {
                    perPage: 1,
                    gap: '1rem'
                },
                1024: {
                    perPage: 2,
                    gap: '1.5rem'
                }
            }
        }).mount();
    }

    // Gallery Carousel
    if (document.querySelector('.gallery-carousel')) {
        new Splide('.gallery-carousel', {
            type: 'carousel',
            perPage: 4,
            perMove: 1,
            gap: '2rem',
            autoplay: false,
            pagination: true,
            arrows: true,
            speed: 800,
            breakpoints: {
                768: {
                    perPage: 1,
                    gap: '1rem'
                },
                1024: {
                    perPage: 2,
                    gap: '1.5rem'
                }
            }
        }).mount();
    }

    // Initialize Counter Animations
    initializeCounters();
    
    // Initialize Scroll Animations
    initializeScrollAnimations();
});

// ====== COUNTER ANIMATION ======
function initializeCounters() {
    const counters = document.querySelectorAll('[data-count]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
                animateCounter(entry.target);
                entry.target.classList.add('counted');
            }
        });
    }, { threshold: 0.5 });
    
    counters.forEach(counter => observer.observe(counter));
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000; // 2 seconds
    const step = target / (duration / 16); // 60fps
    let current = 0;
    
    const updateCount = () => {
        current += step;
        if (current < target) {
            element.textContent = Math.floor(current);
            requestAnimationFrame(updateCount);
        } else {
            element.textContent = target;
        }
    };
    
    updateCount();
}

// ====== SCROLL ANIMATIONS ======
function initializeScrollAnimations() {
    const elements = document.querySelectorAll('[class*="animate-"]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });
    
    elements.forEach(el => {
        observer.observe(el);
    });
}

// ====== MOBILE MENU TOGGLE ======
const menuBtn = document.getElementById('menuBtn');
const navMenu = document.getElementById('navMenu');
const mobileMenu = document.getElementById('mobileMenu');

if (menuBtn) {
    menuBtn.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });
}

// Close mobile menu when link is clicked
const mobileLinks = document.querySelectorAll('#mobileMenu a');
mobileLinks.forEach(link => {
    link.addEventListener('click', function() {
        mobileMenu.classList.add('hidden');
    });
});

// ====== SMOOTH SCROLLING ======
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

// ====== FORM VALIDATION ======
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (input.value.trim() === '') {
            input.style.borderColor = '#C0392B';
            isValid = false;
        } else {
            input.style.borderColor = '#ddd';
        }
    });

    return isValid;
}

// Phone number validation
function validatePhone(phone) {
    const phoneRegex = /^[0-9]{10}$/;
    return phoneRegex.test(phone.replace(/\D/g, ''));
}

// Email validation
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ====== PHONE NUMBER FORMATTING ======
function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.slice(0, 3) + ' ' + value.slice(3);
        } else {
            value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 10);
        }
    }
    input.value = value;
}

// ====== ACTIVE NAVIGATION LINK ======
window.addEventListener('load', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('nav a[href]');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('text-red-700', 'font-bold');
        }
    });
});

// ====== LIGHTBOX / IMAGE MODAL ======
function setupLightbox() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            if (img) {
                showLightbox(img.src, img.alt);
            }
        });
    });
}

function showLightbox(src, alt) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-95 flex items-center justify-center z-50 p-4';
    modal.innerHTML = `
        <div class="relative max-w-4xl w-full animate-fade-in-up">
            <button onclick="this.parentElement.parentElement.remove()" class="absolute -top-10 right-0 text-white text-3xl hover:text-red-600 transition">
                <i class="fas fa-times"></i>
            </button>
            <img src="${src}" alt="${alt}" class="w-full h-auto rounded-lg shadow-2xl">
            <p class="text-white text-center mt-4 font-semibold">${alt}</p>
        </div>
    `;
    document.body.appendChild(modal);
    
    // Close on outside click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.parentElement) {
            modal.remove();
        }
    });
}

// Initialize lightbox on page load
document.addEventListener('DOMContentLoaded', setupLightbox);

// ====== SCROLL TO TOP BUTTON ======
function createScrollToTopButton() {
    const button = document.createElement('button');
    button.id = 'scrollToTop';
    button.innerHTML = '<i class="fas fa-arrow-up"></i>';
    button.className = 'fixed bottom-8 right-8 bg-gradient-to-r from-red-700 to-red-800 text-white w-12 h-12 rounded-full shadow-lg hidden z-40 hover:shadow-xl transition transform hover:scale-110';
    document.body.appendChild(button);

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            button.classList.remove('hidden');
        } else {
            button.classList.add('hidden');
        }
    });

    button.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

document.addEventListener('DOMContentLoaded', createScrollToTopButton);

// ====== TOAST NOTIFICATION ======
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg text-white shadow-lg z-50 animate-fade-in-up ${
        type === 'success' ? 'bg-green-600' : 'bg-red-700'
    }`;
    toast.textContent = message;
    toast.style.animation = 'fadeInUp 0.3s ease-out';
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeInUp 0.3s ease-out reverse';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 3000);
}

// ====== FORM SUBMISSION ======
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add any custom form handling here if needed
        });
    });
});

// ====== PARALLAX EFFECT ======
window.addEventListener('scroll', function() {
    const elements = document.querySelectorAll('[data-parallax]');
    
    elements.forEach(element => {
        const scrollTop = window.pageYOffset;
        const elementOffset = element.offsetTop;
        const parallaxSpeed = element.getAttribute('data-parallax');
        
        if (scrollTop + window.innerHeight > elementOffset) {
            element.style.transform = `translateY(${scrollTop * parallaxSpeed}px)`;
        }
    });
});

// ====== PAGE LOAD ANIMATION ======
window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// Add service hover effect
document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('[data-service-card]');
    
    serviceCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
