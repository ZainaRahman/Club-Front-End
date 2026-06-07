document.addEventListener('DOMContentLoaded', function () {
    const revealItems = document.querySelectorAll('[data-reveal]');

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
        rootMargin: '0px 0px -10% 0px'
    });

    revealItems.forEach((item) => observer.observe(item));
});
