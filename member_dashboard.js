document.addEventListener('DOMContentLoaded', function () {
    const uploadHub = document.getElementById('uploadHub');
    const toggleUploadHubButton = document.getElementById('toggleUploadHub');
    const toggleUploadFormsButton = document.getElementById('toggleUploadForms');

    function toggleUploadPanel() {
        if (!uploadHub) {
            return;
        }

        uploadHub.classList.toggle('is-open');

        if (toggleUploadFormsButton) {
            toggleUploadFormsButton.textContent = uploadHub.classList.contains('is-open') ? 'Hide upload tools' : 'Open upload tools';
        }
    }

    if (toggleUploadHubButton) {
        toggleUploadHubButton.addEventListener('click', toggleUploadPanel);
    }

    if (toggleUploadFormsButton) {
        toggleUploadFormsButton.addEventListener('click', toggleUploadPanel);
    }

    const revealItems = document.querySelectorAll('[data-reveal]');

    if ('IntersectionObserver' in window && revealItems.length > 0) {
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
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }

    const photoInput = document.getElementById('profile_photo');
    const photoPreview = document.getElementById('profilePhotoPreview');

    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = photoInput.files && photoInput.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                if (photoPreview.tagName === 'IMG') {
                    photoPreview.src = event.target.result;
                } else {
                    const image = document.createElement('img');
                    image.id = 'profilePhotoPreview';
                    image.src = event.target.result;
                    image.alt = 'Profile photo preview';
                    image.className = photoPreview.className;
                    photoPreview.replaceWith(image);
                }
            };
            reader.readAsDataURL(file);
        });
    }
});
