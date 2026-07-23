<?php
include 'includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['reply' => 'Invalid request.']);
    exit();
}

$message = isset($_POST['message']) ? strtolower(trim($_POST['message'])) : '';

if (empty($message)) {
    echo json_encode(['reply' => 'Please type a message.']);
    exit();
}

echo json_encode(['reply' => getChatbotResponse($message, $conn)]);
exit();

function getChatbotResponse($msg, $conn) {
    $msg = strtolower($msg);

    // --- Greetings ---
    if (preg_match('/^(hi|hello|hey|assalam|salam|hiya|yo|greetings)/', $msg)) {
        $greetings = [
            "Hello! Welcome to EstateHub! How can I help you find your dream property today?",
            "Hi there! Looking for a property? I'm here to help!",
            "Assalam-o-Alaikum! Welcome to EstateHub. What type of property are you looking for?"
        ];
        return $greetings[array_rand($greetings)];
    }

    // --- Thank you ---
    if (preg_match('/(thank|thanks|thx|shukria)/', $msg)) {
        return "You're welcome! Feel free to ask if you need any more help with your property search!";
    }

    // --- Goodbye ---
    if (preg_match('/(bye|goodbye|allah hafiz|khuda hafiz|see you)/', $msg)) {
        $goodbyes = [
            "Goodbye! Best of luck with your property search. Visit us again at EstateHub!",
            "Allah Hafiz! Hope you find your dream home soon. Come back anytime!",
            "Take care! Feel free to reach out whenever you need property assistance."
        ];
        return $goodbyes[array_rand($goodbyes)];
    }

    // --- Total properties ---
    if (preg_match('/(how many|total|count|number of).*(propert|listing|home)/', $msg)) {
        $total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM properties WHERE status = 'Active'"))['t'] ?? 0;
        $sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM properties WHERE status = 'Active' AND purpose = 'Sale'"))['t'] ?? 0;
        $rent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM properties WHERE status = 'Active' AND purpose = 'Rent'"))['t'] ?? 0;
        return "We currently have {$total} active properties! {$sale} for sale and {$rent} for rent. Would you like to search by city?";
    }

    // --- Cities (UPDATED: shows live counts) ---
    if (preg_match('/(city|cities|location|area|where|lahore|karachi|islamabad|rawalpindi|multan|faisalabad|peshawar|quetta)/', $msg)) {
        $result = mysqli_query($conn, "SELECT DISTINCT city, COUNT(*) as count FROM properties WHERE status = 'Active' AND city IS NOT NULL AND city != '' GROUP BY city ORDER BY count DESC");
        $cities = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cities[] = $row['city'] . " ({$row['count']} properties)";
        }
        if (empty($cities)) {
            return "We currently have no active properties listed. Please check back later!";
        }
        return "We have properties in: " . implode(', ', array_slice($cities, 0, 8)) . ".\n\nWhich city are you interested in? You can also use the search filters on our website.";
    }

    // --- Property Types (UPDATED: shows live counts) ---
    if (preg_match('/(type|types|house|apartment|plot|commercial|villa|farm|portion|penthouse)/', $msg)) {
        $result = mysqli_query($conn, "SELECT property_type, COUNT(*) as count FROM properties WHERE status = 'Active' AND property_type IS NOT NULL GROUP BY property_type ORDER BY count DESC");
        $types = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $types[] = $row['property_type'] . " ({$row['count']})";
        }
        if (empty($types)) {
            return "We have all types of properties: Houses, Apartments, Plots, Commercial, Villas, Farm Houses, Penthouses, Portions. Check back soon for active listings!";
        }
        return "Here are our available property types:\n- " . implode("\n- ", $types) . "\n\nWhich type suits you? Use our filters to narrow down your search.";
    }

    // --- Price ---
    if (preg_match('/(price|cost|budget|cheap|expensive|affordable|rate|pkr|rupee)/', $msg)) {
        $cheapest = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title, price FROM properties WHERE status = 'Active' ORDER BY price ASC LIMIT 1"));
        $expensive = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title, price FROM properties WHERE status = 'Active' ORDER BY price DESC LIMIT 1"));

        if ($cheapest && $expensive) {
            return "Our prices range from PKR " . number_format($cheapest['price'] / 1000000, 1) . "M to PKR " . number_format($expensive['price'] / 1000000, 1) . "M. What's your budget range?";
        }
        return "Prices vary by location and property type. You can browse our listings to find something in your budget!";
    }

    // --- How to buy ---
    if (preg_match('/(how|buy|purchase|process|step|guide)/', $msg)) {
        return "To buy a property:\n1. Browse listings on our website\n2. Use search filters to find your match\n3. Click 'View Details' on any property\n4. Contact the seller through our messaging system\n5. Schedule a visit\n6. Negotiate and finalize the deal\n\nNeed help with any specific step?";
    }

    // --- Sell / list property ---
    if (preg_match('/(sell|list|advertise|post|add property)/', $msg)) {
        return "To sell your property:\n1. Create a Seller account\n2. Go to your Dashboard\n3. Click 'Add Property'\n4. Fill in details and upload images\n5. Submit for approval\n\nYour property will be live within 24-48 hours after admin approval!";
    }

    // --- Contact ---
    if (preg_match('/(contact|email|phone|call|reach|support|help)/', $msg)) {
        return "You can reach us at:\nEmail: info@estatehub.com\nPhone: +92 300 1234567\nOr visit our Contact page to send us a message directly!";
    }

    // --- Features ---
    if (preg_match('/(feature|service|offer|provide|do you|what can)/', $msg)) {
        return "EstateHub offers:\n- Browse 20+ cities across Pakistan\n- Advanced search filters\n- Direct messaging with sellers\n- Wishlist to save favorites\n- Free buyer accounts\n- 24/7 customer support\n\nIs there anything specific you'd like to know?";
    }

    // --- Default fallbacks ---
    $defaults = [
        "I can help you find properties across Pakistan! Try asking about cities, prices, property types, or how to buy/sell.",
        "That's interesting! I'm here to help with property-related questions. You can ask me about listings, prices, or locations.",
        "I'm EstateHub's virtual assistant. Ask me about available properties, how to list your property, or any real estate questions!",
        "Would you like to browse properties in a specific city? Or learn about how to buy or sell on EstateHub?"
    ];

    return $defaults[array_rand($defaults)];
}