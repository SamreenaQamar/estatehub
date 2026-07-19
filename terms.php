<?php
$page_title = 'Terms & Conditions';
include 'includes/config.php';
include 'includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>Terms & Conditions</h1>
        <p>Please read our terms carefully</p>
    </div>
</section>

<section class="page-content">
    <div class="container">
        <div class="content-card">

            <h2>1. Introduction</h2>
            <p>Welcome to EstateHub. By using our website and services, you agree to these terms and conditions. Please read them carefully before using our platform.</p>

            <h2>2. User Accounts</h2>
            <p>When you create an account with us, you must provide accurate and complete information. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>

            <h2>3. Property Listings</h2>
            <p>Sellers are responsible for the accuracy of their property listings. EstateHub reserves the right to remove any listing that violates our policies or contains false information.</p>

            <h2>4. User Conduct</h2>
            <p>Users agree not to misuse our platform, including posting false information, harassing other users, or engaging in any illegal activities through our services.</p>

            <h2>5. Limitation of Liability</h2>
            <p>EstateHub acts as a platform connecting buyers and sellers. We are not responsible for any transactions, disputes, or damages arising from the use of our platform.</p>

            <h2>6. Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Continued use of the platform after changes constitutes acceptance of the new terms.</p>

            <p class="last-updated"><strong>Last Updated:</strong> May 2026</p>

        </div>
    </div>
</section>

<style>
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}
.page-hero {
    background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
    padding: 60px 20px;
    text-align: center;
    color: white;
}
.page-hero h1 {
    font-size: 36px;
    font-weight: 800;
    margin-bottom: 10px;
}
.page-hero p {
    opacity: 0.8;
    font-size: 16px;
}
.page-content {
    max-width: 850px;
    margin: 0 auto;
    padding: 50px 20px;
}
.content-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    border: 1px solid #E5E7EB;
}
.content-card h2 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 15px;
}
.content-card p {
    color: #6B7280;
    line-height: 1.8;
    margin-bottom: 25px;
}
.last-updated {
    margin-top: 30px;
}
</style>

<?php include 'includes/footer.php'; ?>
</body>
</html>