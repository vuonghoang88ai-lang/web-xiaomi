/**
 * PORTAL RESPONSIVE JS MODULE — Mobile Drawer, Touch Navigation & Swipe Gestures
 * Strictly complies with SOP 23 (<= 300 lines). Follows Gemini & ChatGPT mobile standards.
 */

let touchStartX = 0;
let touchStartY = 0;

function initMobileResponsiveLayout() {
  const sidebar = document.querySelector('.sidebar');
  let overlay = document.getElementById('sidebarOverlay');

  // Tạo overlay nếu chưa có trong DOM
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'sidebarOverlay';
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
  }

  // Click vào overlay để đóng sidebar hoặc drawer
  overlay.addEventListener('click', () => {
    closeMobileSidebar();
    if (typeof toggleChatSessionsDrawer === 'function') {
      toggleChatSessionsDrawer(false);
    }
  });

  // Gán sự kiện cho nút hamburger menu
  const btnMenu = document.getElementById('btnMobileMenu');
  if (btnMenu) {
    btnMenu.addEventListener('click', toggleMobileSidebar);
  }

  // Tự động đóng sidebar khi người dùng chọn một tab trên mobile
  document.querySelectorAll('.sidebar-nav .nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 1024) {
        closeMobileSidebar();
      }
    });
  });

  // Đóng khi ấn phím Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMobileSidebar();
      if (typeof toggleChatSessionsDrawer === 'function') {
        toggleChatSessionsDrawer(false);
      }
    }
  });

  // Lắng nghe cử chỉ vuốt chạm (Mobile Swipe Gestures - Gemini/ChatGPT style)
  setupMobileSwipeGestures();

  // Tự động dọn dẹp khi resize lên desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
      closeMobileSidebar();
    }
  });
}

function setupMobileSwipeGestures() {
  document.addEventListener('touchstart', (e) => {
    if (e.touches && e.touches.length === 1) {
      touchStartX = e.touches[0].clientX;
      touchStartY = e.touches[0].clientY;
    }
  }, { passive: true });

  document.addEventListener('touchend', (e) => {
    if (!e.changedTouches || e.changedTouches.length !== 1) return;
    const touchEndX = e.changedTouches[0].clientX;
    const touchEndY = e.changedTouches[0].clientY;
    const diffX = touchEndX - touchStartX;
    const diffY = touchEndY - touchStartY;

    // Chỉ nhận diện khi cử chỉ vuốt ngang rõ rệt hơn vuốt dọc
    if (Math.abs(diffX) > Math.abs(diffY) * 1.5 && Math.abs(diffX) > 60) {
      const sidebar = document.querySelector('.sidebar');
      const isSidebarOpen = sidebar && sidebar.classList.contains('mobile-open');

      if (diffX > 0 && touchStartX < 40 && !isSidebarOpen) {
        // Vuốt từ mép trái sang phải -> Mở menu sidebar
        openMobileSidebar();
      } else if (diffX < 0 && isSidebarOpen) {
        // Vuốt sang trái khi sidebar đang mở -> Đóng sidebar
        closeMobileSidebar();
      }
    }
  }, { passive: true });
}

function openMobileSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.add('mobile-open');
  if (overlay) overlay.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeMobileSidebar() {
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (sidebar) sidebar.classList.remove('mobile-open');
  if (overlay) overlay.classList.remove('active');
  document.body.style.overflow = '';
}

function toggleMobileSidebar() {
  const sidebar = document.querySelector('.sidebar');
  if (sidebar && sidebar.classList.contains('mobile-open')) {
    closeMobileSidebar();
  } else {
    openMobileSidebar();
  }
}

// Khởi chạy khi DOM sẵn sàng
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMobileResponsiveLayout);
} else {
  initMobileResponsiveLayout();
}

window.openMobileSidebar = openMobileSidebar;
window.closeMobileSidebar = closeMobileSidebar;
window.toggleMobileSidebar = toggleMobileSidebar;
