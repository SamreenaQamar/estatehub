<?php
$page_title = 'Contact';
include 'includes/config.php';
include 'includes/header.php';

$message_sent = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $query = "INSERT INTO inquiries (name, email, subject, message, created_at) 
              VALUES ('$name', '$email', '$subject', '$message', NOW())";

    if (mysqli_query($conn, $query)) {
        $message_sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EstateHub</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .contact-hero {
            background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
            padding: 80px 0 60px;
            text-align: center;
            color: white;
        }

        .contact-hero h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .contact-hero p {
            font-size: 18px;
            color: #CBD5E1;
        }

        .contact-section {
            padding: 80px 0;
            background: white;
        }

        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        .contact-info {
            background: #F8FAFC;
            padding: 40px;
            border-radius: 24px;
        }

        .info-item {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            width: 55px;
            height: 55px;
            background: #E7F5EC;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .info-content p {
            color: #6B7280;
            line-height: 1.6;
        }

        .contact-form {
            background: white;
            padding: 40px;
            border: 1px solid #E5E7EB;
            border-radius: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #0E7A4E;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background: #0A5C3A;
        }

        .success-msg {
            background: #D1FAE5;
            color: #059669;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .map-container {
            margin-top: 60px;
            border-radius: 24px;
            overflow: hidden;
        }

        .map-container iframe {
            width: 100%;
            height: 350px;
            border: 0;
        }

        @media (max-width: 900px) {
            .contact-wrapper {
                grid-template-columns: 1fr;
            }

            .contact-hero h1 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>

<section class="contact-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Get in touch for any inquiry or support.</p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-wrapper">
            <div class="contact-info">
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Our Office</h4>
                        <p>U2 Villa Boulevard, Lahore, Pakistan</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                            <rect x="5" y="2" width="14" height="20" rx="2"/>
                            <line x1="12" y1="18" x2="12.01" y2="18"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Phone</h4>
                        <p>+92 300 1234567</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Email</h4>
                        <p>info@estatehub.com</p>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="info-content">
                        <h4>Working Hours</h4>
                        <p>Mon - Sat 9:00 AM - 6:00 PM</p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <?php if ($message_sent): ?>
                    <div class="success-msg">Thank you! Your message has been sent successfully.</div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="subject" placeholder="Subject">
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>

        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d217295.548657656!2d74.15480682463207!3d31.482859200428948!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39190483e58107d9%3A0xc23abe6ccc7e2462!2sLahore%2C%20Pakistan!5e0!3m2!1sen!2s!4v1714320000000!5m2!1sen!2s" allowfullscreen="" loading="lazy"></iframe>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>