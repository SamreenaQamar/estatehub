<?php
/**
 * seed-properties.php
 * EstateHub - Bulk Property Seeder
 * Pure PHP + MySQLi. No frameworks.
 *
 * Generates: 29 Cities x 8 Property Types x 10 Prices = 2320 combinations
 * Skips any combination (city + property_type + price) that already exists
 * in the existing "properties" table.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

// =========================================================
// 1. DATABASE CONNECTION - EDIT THESE TO MATCH YOUR SETUP
// =========================================================
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "estatehub_db";

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// =========================================================
// 2. STATIC DATA
// =========================================================
$cities = [
    "Lahore", "Karachi", "Islamabad", "Rawalpindi", "Faisalabad",
    "Multan", "Peshawar", "Quetta", "Gujranwala", "Sialkot",
    "Bahawalpur", "Sargodha", "Hyderabad", "Sukkur", "Larkana",
    "Abbottabad", "Mardan", "Swat", "Gwadar", "Muzaffarabad",
    "Sahiwal", "Rahim Yar Khan", "Dera Ghazi Khan", "Nawabshah",
    "Mirpur Khas", "Kohat", "Dera Ismail Khan", "Turbat", "Khuzdar"
];

$propertyTypes = [
    "House", "Apartment", "Plot", "Commercial",
    "Farm House", "Villa", "Penthouse", "Portion"
];

// Prices in actual numeric PKR values (1M = 1,000,000)
$prices = [
    1000000, 2000000, 3000000, 4000000, 5000000,
    6000000, 7000000, 8000000, 9000000, 10000000
];

// Generic sub-locality / area names to build a realistic "location" field
$subLocalities = [
    "DHA Phase 5", "Bahria Town", "Gulberg", "Model Town", "Johar Town",
    "Cantt Area", "Garden Town", "Township", "Faisal Town", "Wapda Town",
    "Askari", "Cavalry Ground", "Satellite Town", "Peoples Colony",
    "Green Town", "Valencia Town", "Eden Gardens", "State Life Society",
    "Officers Colony", "Model Colony", "North Nazimabad", "Clifton",
    "Gulshan-e-Iqbal", "Gulistan Colony", "Chaklala Scheme", "Cantonment",
    "Airport Road", "Ring Road Area", "Main Boulevard", "Central Avenue"
];

// Title adjectives for variety
$adjectives = [
    "Luxury", "Modern", "Elegant", "Spacious", "Prime",
    "Beautiful", "Executive", "Premium", "Cozy", "Stylish"
];

// =========================================================
// 3. PROPERTY-TYPE SPECIFIC RULES (size, bedrooms, bathrooms)
// =========================================================
function getTypeSpecs($propertyType) {
    switch ($propertyType) {
        case "House":
            return [
                "sizes" => ["5 Marla", "8 Marla", "10 Marla", "1 Kanal"],
                "bedrooms" => [2, 3, 4, 5, 6],
                "bathrooms" => [2, 3, 4, 5]
            ];
        case "Apartment":
            return [
                "sizes" => ["900 Sqft", "1200 Sqft", "1500 Sqft", "2000 Sqft"],
                "bedrooms" => [1, 2, 3],
                "bathrooms" => [1, 2, 3]
            ];
        case "Plot":
            return [
                "sizes" => ["5 Marla", "10 Marla", "1 Kanal"],
                "bedrooms" => [0],
                "bathrooms" => [0]
            ];
        case "Commercial":
            return [
                "sizes" => ["500 Sqft", "1000 Sqft", "3000 Sqft"],
                "bedrooms" => [0],
                "bathrooms" => [1, 2]
            ];
        case "Farm House":
            return [
                "sizes" => ["2 Kanal", "4 Kanal"],
                "bedrooms" => [3, 4, 5, 6],
                "bathrooms" => [3, 4, 5]
            ];
        case "Villa":
            return [
                "sizes" => ["1 Kanal", "2 Kanal"],
                "bedrooms" => [4, 5, 6],
                "bathrooms" => [4, 5, 6]
            ];
        case "Penthouse":
            return [
                "sizes" => ["2500 Sqft", "3000 Sqft", "3500 Sqft", "4000 Sqft"],
                "bedrooms" => [3, 4, 5],
                "bathrooms" => [3, 4, 5]
            ];
        case "Portion":
            return [
                "sizes" => ["5 Marla", "10 Marla"],
                "bedrooms" => [2, 3, 4],
                "bathrooms" => [2, 3]
            ];
        default:
            return [
                "sizes" => ["5 Marla"],
                "bedrooms" => [2],
                "bathrooms" => [2]
            ];
    }
}

// =========================================================
// 4. HELPER FUNCTIONS
// =========================================================
function formatPriceLabel($price) {
    return round($price / 1000000) . "M";
}

function buildTitle($adjective, $propertyType, $city) {
    return "$adjective $propertyType in $city";
}

function buildDescription($propertyType, $city, $subLocality, $sizeLabel, $bedrooms, $bathrooms, $priceLabel) {
    $desc = "This $propertyType is located in the heart of $subLocality, $city, offering a perfect blend of comfort and convenience. ";
    $desc .= "Spread over $sizeLabel, this property is ideal for families and investors alike. ";

    if ($bedrooms > 0) {
        $desc .= "It features $bedrooms bedroom(s) and $bathrooms bathroom(s), designed with modern finishes and ample natural light. ";
    } else {
        $desc .= "This property offers great potential for construction or business use, with $bathrooms washroom(s) available on-site. ";
    }

    $desc .= "Situated near main roads, schools, hospitals, and shopping areas, this $propertyType in $city is priced at PKR $priceLabel, ";
    $desc .= "making it an excellent opportunity in today's real estate market. Contact us today to schedule a visit.";

    return $desc;
}

function buildLocation($subLocality, $city) {
    return "$subLocality, $city";
}

// =========================================================
// 5. PREPARE STATEMENTS
// =========================================================
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT id FROM properties WHERE city = ? AND property_type = ? AND price = ? LIMIT 1"
);

$insertStmt = mysqli_prepare(
    $conn,
    "INSERT INTO properties
        (title, description, location, city, property_type, purpose, status, price, bedrooms, bathrooms, area_size, featured, views, created_at, updated_at, user_id)
     VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)"
);

if (!$checkStmt || !$insertStmt) {
    die("Failed to prepare statements: " . mysqli_error($conn));
}

// =========================================================
// 6. DEFAULTS
// =========================================================
$purpose = "Sale";
$status = "Active";
$defaultUserId = 1; // Change this if you want properties assigned to a different user/admin ID

$totalInserted = 0;
$totalSkipped = 0;

// =========================================================
// 7. MAIN LOOP: CITY -> PROPERTY TYPE -> PRICE
// =========================================================
foreach ($cities as $city) {
    foreach ($propertyTypes as $propertyType) {

        $specs = getTypeSpecs($propertyType);

        foreach ($prices as $price) {

            // ---- Check for existing duplicate combination ----
            mysqli_stmt_bind_param($checkStmt, "ssd", $city, $propertyType, $price);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);

            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $totalSkipped++;
                continue;
            }

            // ---- Generate realistic values ----
            $sizeLabel   = $specs["sizes"][array_rand($specs["sizes"])];
            $bedrooms    = $specs["bedrooms"][array_rand($specs["bedrooms"])];
            $bathrooms   = $specs["bathrooms"][array_rand($specs["bathrooms"])];
            $subLocality = $subLocalities[array_rand($subLocalities)];
            $adjective   = $adjectives[array_rand($adjectives)];
            $priceLabel  = formatPriceLabel($price);

            $title       = buildTitle($adjective, $propertyType, $city);
            $description = buildDescription($propertyType, $city, $subLocality, $sizeLabel, $bedrooms, $bathrooms, $priceLabel);
            $location    = buildLocation($subLocality, $city);

            $featured = mt_rand(0, 1);
            $views    = mt_rand(10, 500);

            // ---- Insert ----
           mysqli_stmt_bind_param(
    $insertStmt,
    "sssssssdiisiii",
    $title,
    $description,
    $location,
    $city,
    $propertyType,
    $purpose,
    $status,
    $price,
    $bedrooms,
    $bathrooms,
    $sizeLabel,
    $featured,
    $views,
    $defaultUserId
);

            if (mysqli_stmt_execute($insertStmt)) {
                $totalInserted++;
            } else {
                echo "Insert failed for $title ($city, $propertyType, $priceLabel): " . mysqli_stmt_error($insertStmt) . "\n";
                $totalSkipped++;
            }
        }
    }
}

// =========================================================
// 8. CLOSE & REPORT
// =========================================================
mysqli_stmt_close($checkStmt);
mysqli_stmt_close($insertStmt);
mysqli_close($conn);

echo "==============================================\n";
echo "Total Properties Inserted: $totalInserted\n";
echo "Total Properties Skipped (Duplicates): $totalSkipped\n";
echo "==============================================\n";
echo "Done Successfully\n";