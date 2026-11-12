    <footer style="background: #0B1527; color: #94A3B8; padding: 32px 24px; margin-top: auto; border-top: 1px solid #1A2942;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1; min-width: 250px;">
                <div style="font-family: var(--font-display); font-weight: 800; color: #fff; font-size: 20px; margin-bottom: 8px;">T-<span style="color:var(--blue);">Valuate</span></div>
                <p style="font-size: 13px; line-height: 1.6; max-width: 300px;">The most trusted, data-backed Toyota value estimator in the Philippines. Powered by machine learning.</p>
            </div>
            <div style="display: flex; gap: 40px; font-size: 13px;">
                <div>
                    <div style="font-weight: 700; color: #fff; margin-bottom: 12px;">System</div>
                    <a href="browse.php" style="display: block; margin-bottom: 8px; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">Browse Cars</a>
                    <a href="predict.php" style="display: block; margin-bottom: 8px; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">Predict Price</a>
                    <a href="submit_listing.php" style="display: block; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">Sell Your Car</a>
                </div>
                <div>
                    <div style="font-weight: 700; color: #fff; margin-bottom: 12px;">Support & Legal</div>
                    <a href="about.php" style="display: block; margin-bottom: 8px; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">About & Contact Us</a>
                    <a href="javascript:void(0)" onclick="openLegalModal('privacy')" style="display: block; margin-bottom: 8px; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">Privacy Policy</a>
                    <a href="javascript:void(0)" onclick="openLegalModal('terms')" style="display: block; color: #CBD5E1; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#CBD5E1'">Terms of Service</a>
                </div>
            </div>
        </div>
        <div style="max-width: 1200px; margin: 24px auto 0; padding-top: 20px; border-top: 1px solid #1A2942; font-size: 12px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>&copy; <?php echo date('Y'); ?> T-Valuate. All rights reserved.</div>
            <div>Made for Philippine Auto Buyers & Sellers 🇵🇭</div>
        </div>
    </footer>
</main>
</div>

<!-- CUSTOM LEGAL MODAL -->
<div id="legalModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--surface); border-radius: 16px; max-width: 650px; width: 100%; max-height: 85vh; overflow-y: auto; box-shadow: var(--shadow-lg); position: relative; animation: modalFadeIn 0.2s ease; border: 1px solid var(--border);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 24px 32px; border-bottom: 1px solid var(--border); position: sticky; top: 0; background: var(--surface); z-index: 10; border-radius: 16px 16px 0 0;">
            <h3 id="legalTitle" style="font-family: var(--font-display); font-size: 20px; font-weight: 800; color: var(--text); margin: 0;"></h3>
            <button onclick="closeLegalModal()" style="background: var(--paper); border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--text-muted); transition: 0.2s;" onmouseover="this.style.background='var(--border)'; this.style.color='var(--text)'" onmouseout="this.style.background='var(--paper)'; this.style.color='var(--text-muted)'">&times;</button>
        </div>

        <div id="legalContent" style="padding: 32px; font-family: var(--font-body); font-size: 14px; line-height: 1.7; color: var(--text-muted);">
            <!-- Content will be injected here by JavaScript -->
        </div>

        <div style="padding: 20px 32px; border-top: 1px solid var(--border); background: var(--paper); text-align: right; border-radius: 0 0 16px 16px;">
            <button onclick="closeLegalModal()" style="background: var(--blue); color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='var(--blue-dark)'" onmouseout="this.style.background='var(--blue)'">Close</button>
        </div>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
</style>

<script>
  // Mobile sidebar toggle
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

  // Legal Modal Logic
  const legalText = {
    privacy: {
      title: "Privacy Policy",
      content: `
        <p style="margin-bottom: 16px; font-weight: 600; color: var(--text);">Last Updated: <?php echo date('F Y'); ?></p>
        <p style="margin-bottom: 16px;">At T-Valuate, we take your privacy seriously. This policy outlines how we collect, use, and protect the information you provide when using our vehicle valuation and marketplace services.</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">1. Information We Collect</h4>
        <p style="margin-bottom: 16px;">When you submit a vehicle listing or use the predictor, we collect specific details including vehicle specifications (make, model, year, mileage, condition) and personal contact information (name, phone number, location).</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">2. How We Use Your Data</h4>
        <p style="margin-bottom: 16px;">Your data is primarily used to generate accurate market valuations through our machine learning models and to display your listings to potential buyers in our marketplace. We do not sell your personal data to third-party advertisers.</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">3. Data Security</h4>
        <p style="margin-bottom: 16px;">We implement industry-standard security measures to protect your personal information from unauthorized access, alteration, or disclosure.</p>
      `
    },
    terms: {
      title: "Terms of Service",
      content: `
        <p style="margin-bottom: 16px; font-weight: 600; color: var(--text);">Last Updated: <?php echo date('F Y'); ?></p>
        <p style="margin-bottom: 16px;">By accessing and using the T-Valuate platform, you agree to comply with and be bound by the following Terms of Service.</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">1. Valuation Estimates</h4>
        <p style="margin-bottom: 16px;">All prices generated by our system are estimates derived from historical marketplace data and machine learning algorithms. They do not constitute a guaranteed offer to buy or sell. Final transaction prices are subject to physical inspection and mutual agreement between parties.</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">2. User Responsibilities</h4>
        <p style="margin-bottom: 16px;">Users submitting listings are responsible for ensuring all information (mileage, accident history, legal documents) is accurate and truthful. Fraudulent listings will be removed, and the system utilizes a risk engine to flag suspicious data.</p>
        <h4 style="margin: 24px 0 8px; font-size: 15px; color: var(--text);">3. Limitation of Liability</h4>
        <p style="margin-bottom: 16px;">T-Valuate is not liable for any financial losses, vehicle defects, or disputes arising from transactions made between buyers and sellers on this platform.</p>
      `
    }
  };

  function openLegalModal(type) {
    const modal = document.getElementById('legalModal');
    const title = document.getElementById('legalTitle');
    const content = document.getElementById('legalContent');
    
    if (legalText[type]) {
      title.textContent = legalText[type].title;
      content.innerHTML = legalText[type].content;
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
  }

  function closeLegalModal() {
    document.getElementById('legalModal').style.display = 'none';
    document.body.style.overflow = '';
  }

  document.getElementById('legalModal').addEventListener('click', function(e) {
    if (e.target === this) closeLegalModal();
  });

  // Theme Toggle Logic
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
</script>
</body>
</html>