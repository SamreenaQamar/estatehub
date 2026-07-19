<?php
$page_title = 'About';
include 'includes/config.php';
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - EstateHub</title>
    <style>
        .about-hero {
            background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
            padding: 80px 0 60px;
            text-align: center;
            color: white;
        }
        .about-hero h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
        }
        .about-hero p {
            font-size: 18px;
            color: #CBD5E1;
            max-width: 600px;
            margin: 0 auto;
        }
        .about-content {
            padding: 80px 0;
            background: white;
        }
        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .about-text h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #111827;
        }
        .about-text p {
            color: #6B7280;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            margin-top: 60px;
            text-align: center;
        }
        .stat-box {
            background: #F8FAFC;
            padding: 30px;
            border-radius: 20px;
        }
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #0E7A4E;
        }
        .stat-label {
            color: #6B7280;
            margin-top: 8px;
        }
        .mission-section {
            background: #F8FAFC;
            padding: 80px 0;
        }
        .mission-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        .mission-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .mission-icon {
            width: 70px;
            height: 70px;
            background: #E7F5EC;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .mission-card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .mission-card p {
            color: #6B7280;
            line-height: 1.6;
        }
        @media (max-width: 900px) {
            .about-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .mission-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<section class="about-hero">
    <div class="container">
        <h1>About EstateHub</h1>
        <p>Pakistan's most trusted real estate platform connecting buyers and sellers</p>
    </div>
</section>

<section class="about-content">
    <div class="container">
        <div class="about-grid">
            <div class="about-text">
                <h2>Your Trusted Real Estate Partner</h2>
                <p>EstateHub is a modern real estate platform that helps you find, buy, sell or rent properties with ease. Our mission is to provide a trusted, transparent and convenient experience for all.</p>
                <p>With thousands of verified listings across Pakistan, we connect property seekers with genuine sellers, ensuring a seamless real estate journey.</p>
            </div>
            <div class="about-image">
                <img src="https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg" alt="About Us" style="width:100%; border-radius:20px;">
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-box"><div class="stat-number">10K+</div><div class="stat-label">Properties</div></div>
            <div class="stat-box"><div class="stat-number">5K+</div><div class="stat-label">Happy Clients</div></div>
            <div class="stat-box"><div class="stat-number">500+</div><div class="stat-label">Verified Sellers</div></div>
            <div class="stat-box"><div class="stat-number">20+</div><div class="stat-label">Cities</div></div>
        </div>
    </div>
</section>

<section class="mission-section">
    <div class="container">
        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div>
                <h3>Trusted Platform</h3>
                <p>Verified listings and genuine sellers</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/></svg></div>
                <h3>All Over Pakistan</h3>
                <p>Properties in 20+ major cities</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l3 2"/></svg></div>
                <h3>Easy Communication</h3>
                <p>Direct contact with property owners</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>