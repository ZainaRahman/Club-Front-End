function loginpage(){
    const loginModal= document.getElementById('loginModal');
    loginModal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeLoginModal(){
    const loginModal= document.getElementById('loginModal');
    loginModal.classList.remove('show');
    document.body.classList.remove('modal-open');
}   

function signuppage(){
    const signupModal= document.getElementById('signupModal');
    signupModal.classList.add('show');
    document.body.classList.add('modal-open');
}

function closeSignupModal(){
    const signupModal= document.getElementById('signupModal');
    signupModal.classList.remove('show');
    document.body.classList.remove('modal-open');
}

window.addEventListener('click', function(event) {
    const loginModal = document.getElementById('loginModal');
    const signupModal = document.getElementById('signupModal'); 
    if (event.target === loginModal) {
        closeLoginModal();
    }   
    if (event.target === signupModal) {
        closeSignupModal();
    }   
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLoginModal();
        closeSignupModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeStorageKey = 'kminds-theme';
    const savedTheme = localStorage.getItem(themeStorageKey);
    const preferredDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initialTheme = savedTheme || (preferredDark ? 'dark' : 'light');

    function applyTheme(theme) {
        document.body.setAttribute('data-theme', theme);

        if (themeToggle) {
            const isDark = theme === 'dark';
            themeToggle.textContent = isDark ? 'Light Mode' : 'Dark Mode';
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    applyTheme(initialTheme);

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const nextTheme = document.body.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem(themeStorageKey, nextTheme);
            applyTheme(nextTheme);
        });
    }

    const revealItems = document.querySelectorAll('[data-reveal]');
    const statNumbers = document.querySelectorAll('[data-counter]');
    let countersStarted = false;

    function animateCounter(element) {
        const target = Number(element.getAttribute('data-counter')) || 0;
        const duration = 1600;
        const startTime = performance.now();

        function step(currentTime) {
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = Math.floor(target * eased);
            element.textContent = value.toString();

            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                element.textContent = target.toString();
            }
        }

        requestAnimationFrame(step);
    }

    function startCountersOnce() {
        if (countersStarted || statNumbers.length === 0) {
            return;
        }

        countersStarted = true;
        statNumbers.forEach(animateCounter);
    }

    const statsSection = document.getElementById('stats');

    if (statsSection && 'IntersectionObserver' in window) {
        const statsObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    startCountersOnce();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.35 });

        statsObserver.observe(statsSection);
    } else {
        startCountersOnce();
    }

    if (!('IntersectionObserver' in window) || revealItems.length === 0) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, observerInstance) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observerInstance.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.18,
        rootMargin: '0px 0px -8% 0px'
    });

    revealItems.forEach((item) => observer.observe(item));
});


document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.team-carousel');
    if (carousel) {
        const items = Array.from(carousel.querySelectorAll('.team-member-card'));
        const numOriginalItems = items.length;
    
        items.forEach(item => {
            const clone = item.cloneNode(true);
            carousel.appendChild(clone);
        });
        
    
        function updateAnimationDistance() {
            const firstCard = carousel.querySelector('.team-member-card');
            if (firstCard) {
                const cardWidth = firstCard.offsetWidth;
                const computedStyle = window.getComputedStyle(carousel);
                const gap = parseInt(computedStyle.gap) || 32;
                
                
                const distanceToMove = (numOriginalItems * cardWidth) + ((numOriginalItems - 1) * gap);
                
               
                carousel.style.setProperty('--carousel-distance', `-${distanceToMove}px`);
            
                if (!document.getElementById('dynamicCarouselStyles')) {
                    const style = document.createElement('style');
                    style.id = 'dynamicCarouselStyles';
                    style.textContent = `
                        @keyframes smoothCarousel {
                            0% { transform: translateX(0); }
                            100% { transform: translateX(var(--carousel-distance, -1728px)); }
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        }
        
       
        window.addEventListener('load', updateAnimationDistance);
        window.addEventListener('resize', updateAnimationDistance);
        updateAnimationDistance();
    }
});


document.addEventListener('DOMContentLoaded', function() {
    const eventsCarousel = document.querySelector('.events-carousel');
    if (eventsCarousel) {
        const originalItems = Array.from(eventsCarousel.querySelectorAll('.event-card'));
        const numOriginalItems = originalItems.length;
        if (numOriginalItems === 0) return;

       
        function getSetWidth() {
            const firstCard = eventsCarousel.querySelector('.event-card');
            const cardWidth = firstCard ? firstCard.offsetWidth : 380;
            const gap = parseInt(window.getComputedStyle(eventsCarousel).gap) || 32;
            return (numOriginalItems * cardWidth) + (numOriginalItems * gap);
        }

     
        function buildClones() {
           
            eventsCarousel.querySelectorAll('.event-card-clone').forEach(el => el.remove());

            const viewportWidth = window.innerWidth;
            const setWidth = getSetWidth();
            
            const setsNeeded = Math.ceil((viewportWidth * 3) / setWidth) + 1;

            for (let s = 0; s < setsNeeded; s++) {
                originalItems.forEach(item => {
                    const clone = item.cloneNode(true);
                    clone.classList.add('event-card-clone');
                    eventsCarousel.appendChild(clone);
                });
            }
        }

        let scrollPosition = 0;
        let scrollSpeed = 0.5;
        let isPaused = false;
        let scrollDistance = 0;
        let animationId = null;
        let animationStarted = false;

        function initializeCarousel() {
            buildClones();
            scrollDistance = getSetWidth();
            scrollPosition = 0;
            eventsCarousel.style.transform = `translateX(0)`;
        }

        function animate() {
            if (!isPaused) {
                scrollPosition += scrollSpeed;
                if (scrollPosition >= scrollDistance) {
                    scrollPosition = 0;
                }
                eventsCarousel.style.transform = `translateX(-${scrollPosition}px)`;
            }
            animationId = requestAnimationFrame(animate);
        }

        eventsCarousel.addEventListener('mouseenter', function() { isPaused = true; });
        eventsCarousel.addEventListener('mouseleave', function() { isPaused = false; });

        function startOnce() {
            if (animationStarted) return;
            animationStarted = true;
            initializeCarousel();
            animate();
        }

       
        window.addEventListener('resize', function() {
            if (!animationStarted) return;
            cancelAnimationFrame(animationId);
            animationStarted = false;
            startOnce();
        });

        window.addEventListener('load', startOnce, { once: true });
        setTimeout(startOnce, 100);
    }
    
});