document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('mi-mobile-menu-toggle');
    var mobileMenu = document.getElementById('mi-mobile-menu');
    var menuIcon = document.getElementById('mi-menu-icon');
    if (toggleBtn && mobileMenu) {
        toggleBtn.addEventListener('click', function() {
            var isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                toggleBtn.setAttribute('aria-expanded', 'true');
                if (menuIcon) menuIcon.textContent = 'close';
            } else {
                mobileMenu.classList.add('hidden');
                toggleBtn.setAttribute('aria-expanded', 'false');
                if (menuIcon) menuIcon.textContent = 'menu';
            }
        });
    }

    // Back to Top Logic
    var backToTopBtn = document.getElementById('mi-back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTopBtn.classList.remove('hidden');
                backToTopBtn.classList.add('flex');
            } else {
                backToTopBtn.classList.add('hidden');
                backToTopBtn.classList.remove('flex');
            }
        });

        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
