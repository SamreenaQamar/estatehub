<?php
$page_title = 'My Wishlist';
include 'includes/config.php';
include 'includes/header.php';

// Redirect if not logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ FIXED: Correct query to get user's wishlist properties
$query = "SELECT 
            w.id as wishlist_id,
            w.created_at as wishlist_date,
            p.id as property_id,
            p.title,
            p.description,
            p.property_type,
            p.purpose,
            p.price,
            p.location,
            p.city,
            p.area_size,
            p.bedrooms,
            p.bathrooms,
            p.image_main,
            p.status
          FROM wishlist w 
          INNER JOIN properties p ON w.property_id = p.id 
          WHERE w.user_id = $user_id 
          ORDER BY w.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$wishlist_count = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - EstateHub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #F8FAFC; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        .wishlist-hero { 
            background: linear-gradient(135deg, #123524 0%, #0A2318 100%); 
            padding: 60px 0 50px; 
            text-align: center; 
            color: white; 
        }
        .wishlist-hero h1 { font-family: 'Fraunces', serif; font-size: 42px; font-weight: 600; margin-bottom: 12px; }
        .wishlist-hero p { font-size: 16px; opacity: 0.85; }
        
        .wishlist-section { padding: 60px 0; background: #F8FAFC; min-height: 400px; }
        .wishlist-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 24px; 
        }
        
        .property-card { 
            background: white; 
            border-radius: 18px; 
            overflow: hidden; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            transition: all 0.3s ease; 
            position: relative; 
        }
        .property-card:hover { 
            transform: translateY(-4px); 
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); 
        }
        
        .card-image { 
            height: 220px; 
            background-size: cover; 
            background-position: center; 
            position: relative; 
            background-color: #E5E7EB; 
        }
        .card-tag { 
            position: absolute; 
            top: 12px; 
            left: 12px; 
            padding: 5px 14px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
            z-index: 2; 
        }
        .card-tag.for-sale { background: #0E7A4E; color: white; }
        .card-tag.for-rent { background: #10B981; color: white; }
        
        .remove-wishlist { 
            position: absolute; 
            top: 12px; 
            right: 12px; 
            background: rgba(0,0,0,0.6); 
            width: 34px; 
            height: 34px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            transition: all 0.3s; 
            border: none; 
            z-index: 2; 
            text-decoration: none; 
            color: white;
            font-size: 18px;
        }
        .remove-wishlist:hover { 
            background: #DC2626; 
            transform: scale(1.05); 
        }
        
        .card-body { padding: 20px; }
        .card-body h3 { 
            font-size: 18px; 
            font-weight: 700; 
            margin-bottom: 8px; 
            color: #1F2937; 
        }
        .card-location { 
            font-size: 13px; 
            color: #6B7280; 
            margin-bottom: 10px; 
            display: flex; 
            align-items: center; 
            gap: 4px; 
        }
        .card-price { 
            font-size: 22px; 
            font-weight: 700; 
            color: #0E7A4E; 
            margin-bottom: 15px; 
        }
        .per-month { 
            font-size: 13px; 
            font-weight: 400; 
            color: #6B7280; 
        }
        
        .card-meta { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 12px; 
            margin-bottom: 18px; 
            padding-bottom: 15px; 
            border-bottom: 1px solid #E5E7EB; 
        }
        .meta-item { 
            display: flex; 
            align-items: center; 
            gap: 4px; 
            font-size: 12px; 
            color: #6B7280; 
        }
        
        .view-detail-btn { 
            display: inline-block; 
            width: 100%; 
            padding: 10px; 
            background: #0E7A4E; 
            color: white; 
            text-align: center; 
            border-radius: 10px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px; 
            transition: all 0.2s; 
        }
        .view-detail-btn:hover { background: #0A5C3A; }
        
        .empty-wishlist { 
            text-align: center; 
            padding: 80px 20px; 
            background: white; 
            border-radius: 20px; 
        }
        .empty-wishlist h3 { margin: 20px 0 10px; font-size: 22px; }
        .browse-btn { 
            display: inline-block; 
            padding: 12px 30px; 
            background: #0E7A4E; 
            color: white; 
            border-radius: 10px; 
            text-decoration: none; 
            font-weight: 600; 
        }
        
        .alert { 
            padding: 15px 20px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
        }
        .alert-success { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }
        
        @media (max-width: 768px) { 
            .wishlist-grid { grid-template-columns: 1fr; } 
        }
    </style>
</head>
<body>

<section class="wishlist-hero">
    <div class="container">
        <h1>❤️ My Wishlist</h1>
        <p>Properties you have saved (<?php echo $wishlist_count; ?> items)</p>
    </div>
</section>

<section class="wishlist-section">
    <div class="container">
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if ($wishlist_count > 0): ?>
            <div class="wishlist-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $price_in_millions = $row['price'] / 1000000;
                    $image_path = getPropertyImage($row);
                ?>
                    <div class="property-card">
                        <div class="card-image" style="background-image: url('<?php echo $image_path; ?>');">
                            <div class="card-tag <?php echo $row['purpose'] == 'Rent' ? 'for-rent' : 'for-sale'; ?>">
                                <?php echo $row['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                            </div>
                            <a href="wishlist.php?remove=<?php echo $row['property_id']; ?>" 
                               class="remove-wishlist" 
                               onclick="return confirm('Remove this property from wishlist?')">
                                ✕
                            </a>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <div class="card-location">
                                📍 <?php echo htmlspecialchars($row['location']); ?>, <?php echo htmlspecialchars($row['city']); ?>
                            </div>
                            <div class="card-price">
                                PKR <?php echo number_format($price_in_millions, 1); ?>M
                                <?php if ($row['purpose'] == 'Rent'): ?>
                                    <span class="per-month">/ Month</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-meta">
                                <?php if ($row['bedrooms'] > 0): ?>
                                <span class="meta-item">🛏️ <?php echo $row['bedrooms']; ?> Beds</span>
                                <?php endif; ?>
                                <?php if ($row['bathrooms'] > 0): ?>
                                <span class="meta-item">🛁 <?php echo $row['bathrooms']; ?> Baths</span>
                                <?php endif; ?>
                                <?php if (!empty($row['area_size'])): ?>
                                <span class="meta-item">📐 <?php echo htmlspecialchars($row['area_size']); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="property-detail.php?id=<?php echo $row['property_id']; ?>" class="view-detail-btn">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <div style="font-size: 64px; margin-bottom: 20px;">💔</div>
                <h3>Your wishlist is empty</h3>
                <p style="color: #6B7280; margin-bottom: 20px;">Start adding properties to your wishlist!</p>
                <a href="listings.php" class="browse-btn">Browse Properties</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>