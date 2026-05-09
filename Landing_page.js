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
        const items = Array.from(eventsCarousel.querySelectorAll('.event-card'));
        const numOriginalItems = items.length;
        
    
        items.forEach(item => {
            const clone = item.cloneNode(true);
            eventsCarousel.appendChild(clone);
        });
        
        let scrollPosition = 0;
        let scrollSpeed = 0.5; 
        let isPaused = false;
        let scrollDistance = 0;
        
        function getScrollDistance() {
            const firstCard = eventsCarousel.querySelector('.event-card');
            if (firstCard) {
                const cardWidth = firstCard.offsetWidth;
                const computedStyle = window.getComputedStyle(eventsCarousel);
                const gap = parseInt(computedStyle.gap) || 32;
                return (numOriginalItems * cardWidth) + ((numOriginalItems - 1) * gap);
            }
            return 0;
        }
        
      
        function initializeCarousel() {
            scrollDistance = getScrollDistance();
            
        }
        

        function animate() {
            if (!isPaused) {
                scrollPosition += scrollSpeed;
                
               
                if (scrollPosition >= scrollDistance) {
                    scrollPosition = 0;
                }
                
                eventsCarousel.style.transform = `translateX(-${scrollPosition}px)`;
            }
            requestAnimationFrame(animate);
        }
        
   
        eventsCarousel.addEventListener('mouseenter', function() {
            isPaused = true;
        });
        
        eventsCarousel.addEventListener('mouseleave', function() {
            isPaused = false;
        });
        
    
        window.addEventListener('load', function() {
            initializeCarousel();
            animate();
        }, { once: true });
        
        
        setTimeout(function() {
            if (scrollDistance === 0) {
                initializeCarousel();
                animate();
            }
        }, 100);
    }
});
