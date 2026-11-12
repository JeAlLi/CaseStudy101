<?php
require_once 'db.php';
$current_page = 'about';

// Fetch total listings to show dynamic stats
$stats = $pdo->query("SELECT COUNT(*) AS total FROM vehicle_listings WHERE status = 'approved'")->fetch();
$total_listings = (int)$stats['total'];

include 'header.php';
?>

<style>
    /* Reliable Toyota SUV Hero */
    .about-hero {
        background-image: linear-gradient(rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.9)), 
                          url('https://images.unsplash.com/photo-1616422285623-14ee7c111b0d?auto=format&fit=crop&q=80&w=1600');
        background-size: cover;
        background-position: center;
        border-radius: 24px;
        padding: 80px 40px;
        color: #fff;
        margin-bottom: 32px;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .about-hero h1 {
        font-family: var(--font-display);
        font-size: 46px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 16px;
        position: relative;
        z-index: 2;
        letter-spacing: -1px;
        text-shadow: 0 4px 15px rgba(0,0,0,0.8);
    }
    .about-hero p {
        font-size: 16px;
        color: #E2E8F0;
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.6;
        position: relative;
        z-index: 2;
        text-shadow: 0 2px 10px rgba(0,0,0,0.8);
    }

    /* 2-Column Mission Section */
    .mission-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    .mission-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 32px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .mission-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--border-hover);
    }
    .mission-card h3 {
        font-family: var(--font-display);
        font-size: 20px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .mission-card p {
        font-size: 14.5px;
        color: var(--text-muted);
        line-height: 1.7;
        font-weight: 500;
    }

    /* Tech Stack Section */
    .tech-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 32px;
        box-shadow: var(--shadow-sm);
        transition: var(--transition);
    }
    .tech-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-top: 32px;
    }
    .tech-item {
        text-align: center;
        padding: 32px 24px;
        background: var(--paper);
        border-radius: 16px;
        border: 1px solid var(--border);
        transition: var(--transition);
    }
    .tech-item:hover {
        border-color: var(--blue);
        box-shadow: 0 4px 12px var(--blue-soft);
    }
    .tech-icon {
        width: 56px; height: 56px;
        background: var(--blue-soft);
        color: var(--blue);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 16px;
        border: 1px solid var(--border);
    }
    .tech-icon svg { width: 28px; height: 28px; }
    .tech-item h4 { font-family: var(--font-display); font-size: 18px; font-weight: 800; color: var(--text); margin-bottom: 8px; }
    .tech-item p { font-size: 14px; color: var(--text-muted); line-height: 1.6; font-weight: 500; }

    /* Contact Section */
    .contact-banner {
        background: var(--blue-soft);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 20px;
        padding: 32px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 24px;
        transition: var(--transition);
    }
    .contact-banner h3 {
        font-family: var(--font-display);
        font-size: 24px;
        font-weight: 800;
        color: var(--text);
        margin-bottom: 8px;
    }
    .contact-banner p {
        font-size: 15px;
        color: var(--text-muted);
        font-weight: 500;
    }

    .phone-btn {
        display: flex; align-items: center; gap: 16px; 
        background: var(--surface); 
        border: 1px solid var(--border); 
        padding: 16px 24px; 
        border-radius: 12px; 
        text-decoration: none; 
        box-shadow: var(--shadow-sm); 
        transition: 0.2s;
    }
    .phone-btn:hover {
        transform: translateY(-2px); 
        box-shadow: var(--shadow-md); 
        border-color: var(--blue);
    }
    .phone-icon {
        width: 44px; height: 44px; border-radius: 50%; 
        background: var(--blue-soft); 
        display: flex; align-items: center; justify-content: center;
    }
    .phone-lbl {
        font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; 
        color: var(--blue); font-weight: 800; margin-bottom: 2px;
    }
    .phone-num {
        font-family: var(--font-mono); font-size: 20px; font-weight: 800; color: var(--text);
    }

    @media (max-width: 900px) {
        .mission-grid { grid-template-columns: 1fr; }
        .tech-grid { grid-template-columns: 1fr; }
        .contact-banner { flex-direction: column; text-align: center; }
        .phone-btn { width: 100%; justify-content: center; }
    }
</style>

<div class="view">

    <!-- HERO -->
    <div class="about-hero">
        <h1>Empowering Smarter Toyota Transactions</h1>
        <p>The <strong>Toyota Valuation Engine</strong> is a data-driven platform designed to eliminate the guesswork from buying and selling used Toyota vehicles in the Philippines. We combine real marketplace data with machine learning to provide fair, transparent, and accurate valuations.</p>
    </div>

    <!-- MISSION -->
    <div class="mission-grid">
        <div class="mission-card">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--coral)" stroke-width="2.5" style="width:24px;height:24px;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                The Problem
            </h3>
            <p>For decades, the Philippine used-car market has relied on gut feeling and scattered online listings. This often leads to buyers overpaying for Toyota vehicles with hidden issues, or sellers underselling their cars simply because they don't know the true market value.</p>
        </div>
        <div class="mission-card">
            <h3>
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--teal)" stroke-width="2.5" style="width:24px;height:24px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Our Solution
            </h3>
            <p>We analyze thousands of data points—including model, depreciation curves, mileage, location, and legal status—to generate an instant, unbiased price estimate. Our goal is to create a trusted, transparent ecosystem where both buyers and sellers win.</p>
        </div>
    </div>

    <!-- TECHNOLOGY -->
    <div class="tech-section">
        <div style="text-align: center;">
            <h2 style="font-family: var(--font-display); font-size: 28px; font-weight: 800; color: var(--text); margin-bottom: 8px;">Powered by Technology</h2>
            <p style="font-size: 15px; color: var(--text-muted);">We don't just guess prices. We use advanced statistical modeling.</p>
        </div>
        
        <div class="tech-grid">
            <div class="tech-item">
                <div class="tech-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
                <h4>Machine Learning</h4>
                <p>Our Random Forest ML model is trained on live market data to understand complex depreciation and pricing trends for Toyota models.</p>
            </div>
            <div class="tech-item">
                <div class="tech-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <h4>Risk Engine</h4>
                <p>Every listing is scanned for red flags like flood zones, tampered odometers, and missing legal documents.</p>
            </div>
            <div class="tech-item">
                <div class="tech-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/></svg>
                </div>
                <h4>Real Market Data</h4>
                <p>With <strong><?php echo number_format($total_listings); ?> active listings</strong> in our database, our valuations are grounded in reality.</p>
            </div>
        </div>
    </div>

    <!-- CONTACT -->
    <div class="contact-banner">
        <div>
            <h3>Still have questions?</h3>
            <p>Our support team is ready to help you with your listing or valuation.</p>
        </div>
        
        <a href="tel:09117651227" class="phone-btn">
            <div class="phone-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" style="width: 20px; height: 20px;"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
            </div>
            <div>
                <div class="phone-lbl">Call or Text Us</div>
                <div class="phone-num">0911 765 1227</div>
            </div>
        </a>
    </div>

</div>

<?php include 'footer.php'; ?>