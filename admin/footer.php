</main>
</div>

<script>
  // 1. Mobile sidebar toggle
  (function() {
    const menuBtn = document.getElementById('menuBtn');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!menuBtn || !sidebar || !backdrop) return;

    function closeSidebar() {
      sidebar.classList.remove('open');
      backdrop.classList.remove('show');
    }
    menuBtn.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      backdrop.classList.toggle('show');
    });
    backdrop.addEventListener('click', closeSidebar);
    sidebar.querySelectorAll('.nav-item').forEach(function(link) {
      link.addEventListener('click', closeSidebar);
    });
  })();

  // 2. Theme Toggle Logic
  const themeToggle = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  
  function updateThemeIcon(theme) {
      if (theme === 'dark') {
          themeIcon.innerHTML = '<circle cx="12" cy="12" r="5"></circle><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>';
      } else {
          themeIcon.innerHTML = '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>';
      }
  }

  if (themeToggle) {
      updateThemeIcon(document.documentElement.getAttribute('data-theme'));
      themeToggle.addEventListener('click', () => {
          const currentTheme = document.documentElement.getAttribute('data-theme');
          const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', newTheme);
          localStorage.setItem('theme', newTheme);
          updateThemeIcon(newTheme);
      });
  }

  // 3. THE ULTIMATE BACK/FORWARD BUTTON FIX
  window.addEventListener('pageshow', function(event) {
      // If the page is loaded from the browser cache (Back or Forward button)
      if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
          // Force a hard reload from the server
          window.location.reload(true); 
      }
  });
</script>
</body>
</html>