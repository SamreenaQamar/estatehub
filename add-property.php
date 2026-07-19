<?php
$page_title = 'Add Property';
include 'includes/config.php';
include 'includes/header.php';

// Only logged-in sellers (or admins) can add a property
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_type = $_SESSION['user_type'] ?? '';
if ($user_type != 'seller' && $user_type != 'admin') {
    $_SESSION['error'] = "Only sellers can add properties. Please contact support if you'd like to become a seller.";
    header("Location: buyer-dashboard.php");
    exit();
}

$property_types = getPropertyTypes();
$purposes = getPropertyPurposes();

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);
?>

<section class="add-property-section">
    <div class="container">

        <form class="property-form">

            <h1>Add New Property</h1>
            <p class="subtitle">Fill in the details below to list your property.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="process-property.php" enctype="multipart/form-data" class="property-form">
            <input type="hidden" name="add_property" value="1">

            <div class="form-group full">
                <label>Property Title</label>
                <input type="text" name="title" placeholder="e.g. Modern 10 Marla House" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Property Type</label>
                    <select name="property_type" required>
                        <?php foreach ($property_types as $pt): ?>
                            <option value="<?php echo htmlspecialchars($pt); ?>"><?php echo htmlspecialchars($pt); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Purpose</label>
                    <select name="purpose" required>
                        <?php foreach ($purposes as $pur): ?>
                            <option value="<?php echo htmlspecialchars($pur); ?>"><?php echo $pur == 'Rent' ? 'For Rent' : 'For Sale'; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
<div class="form-row">
    <div class="form-group">
        <label>Price (PKR)</label>
        <select name="price" required>
            <option value="">Select Price</option>
            <option value="1000000">PKR 1M</option>
            <option value="2000000">PKR 2M</option>
            <option value="3000000">PKR 3M</option>
            <option value="4000000">PKR 4M</option>
            <option value="5000000">PKR 5M</option>
            <option value="6000000">PKR 6M</option>
            <option value="7000000">PKR 7M</option>
            <option value="8000000">PKR 8M</option>
            <option value="9000000">PKR 9M</option>
            <option value="10000000">PKR 10M</option>
        </select>
    </div>

   <div class="form-group">
    <label>Area Size</label>
    <select name="area" required>
        <option value="">Select Area Size</option>
        <option value="3 Marla">3 Marla</option>
        <option value="5 Marla">5 Marla</option>
        <option value="7 Marla">7 Marla</option>
        <option value="10 Marla">10 Marla</option>
        <option value="12 Marla">12 Marla</option>
        <option value="1 Kanal">1 Kanal</option>
        <option value="2 Kanal">2 Kanal</option>
        <option value="500 Sqft">500 Sqft</option>
        <option value="1000 Sqft">1000 Sqft</option>
        <option value="2000 Sqft">2000 Sqft</option>
        <option value="5000 Sqft">5000 Sqft</option>
    </select>
</div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>City</label>
        <select name="city" required>
            <option value="">Select City</option>
            <option value="Lahore">Lahore</option>
            <option value="Karachi">Karachi</option>
            <option value="Islamabad">Islamabad</option>
            <option value="Rawalpindi">Rawalpindi</option>
            <option value="Faisalabad">Faisalabad</option>
            <option value="Multan">Multan</option>
            <option value="Peshawar">Peshawar</option>
            <option value="Quetta">Quetta</option>
            <option value="Gujranwala">Gujranwala</option>
            <option value="Sialkot">Sialkot</option>
            <option value="Bahawalpur">Bahawalpur</option>
            <option value="Sargodha">Sargodha</option>
            <option value="Hyderabad">Hyderabad</option>
            <option value="Sukkur">Sukkur</option>
            <option value="Larkana">Larkana</option>
            <option value="Abbottabad">Abbottabad</option>
            <option value="Mardan">Mardan</option>
            <option value="Swat">Swat</option>
            <option value="Gwadar">Gwadar</option>
            <option value="Muzaffarabad">Muzaffarabad</option>
            <option value="Sahiwal">Sahiwal</option>
            <option value="Rahim Yar Khan">Rahim Yar Khan</option>
            <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
            <option value="Nawabshah">Nawabshah</option>
            <option value="Mirpur Khas">Mirpur Khas</option>
            <option value="Kohat">Kohat</option>
            <option value="Dera Ismail Khan">Dera Ismail Khan</option>
            <option value="Turbat">Turbat</option>
            <option value="Khuzdar">Khuzdar</option>
        </select>
    </div>

    <div class="form-group">
        <label>Location / Area</label>
        <select name="location" required>
            <option value="">Select Location</option>
            <option value="DHA">DHA</option>
            <option value="Bahria Town">Bahria Town</option>
            <option value="Gulberg">Gulberg</option>
            <option value="Johar Town">Johar Town</option>
            <option value="Model Town">Model Town</option>
            <option value="Wapda Town">Wapda Town</option>
            <option value="Canal View">Canal View</option>
            <option value="Lake City">Lake City</option>
            <option value="Valencia Town">Valencia Town</option>
            <option value="Paragon City">Paragon City</option>
            <option value="Askari">Askari</option>
            <option value="Garden Town">Garden Town</option>
            <option value="Township">Township</option>
            <option value="Iqbal Town">Iqbal Town</option>
            <option value="Sabzazar">Sabzazar</option>
            <option value="Shadman">Shadman</option>
            <option value="Muslim Town">Muslim Town</option>
            <option value="Faisal Town">Faisal Town</option>
            <option value="Gulshan-e-Ravi">Gulshan-e-Ravi</option>
            <option value="Defence">Defence</option>
            <option value="Clifton">Clifton</option>
            <option value="Gulshan-e-Iqbal">Gulshan-e-Iqbal</option>
            <option value="North Nazimabad">North Nazimabad</option>
            <option value="PECHS">PECHS</option>
            <option value="Malir">Malir</option>
            <option value="Scheme 33">Scheme 33</option>
            <option value="Saddar">Saddar</option>
            <option value="Nazimabad">Nazimabad</option>
            <option value="F-6">F-6</option>
            <option value="F-7">F-7</option>
            <option value="F-8">F-8</option>
            <option value="F-10">F-10</option>
            <option value="F-11">F-11</option>
            <option value="G-10">G-10</option>
            <option value="G-11">G-11</option>
            <option value="G-13">G-13</option>
            <option value="E-11">E-11</option>
            <option value="Blue Area">Blue Area</option>
            <option value="PWD Housing Society">PWD Housing Society</option>
            <option value="Satellite Town">Satellite Town</option>
            <option value="Chaklala Scheme">Chaklala Scheme</option>
            <option value="University Town">University Town</option>
            <option value="Hayatabad">Hayatabad</option>
            <option value="Cantt">Cantt</option>
            <option value="Gulgasht Colony">Gulgasht Colony</option>
            <option value="Bosan Road">Bosan Road</option>
            <option value="New Multan">New Multan</option>
            <option value="Shah Rukn-e-Alam">Shah Rukn-e-Alam</option>
            <option value="Others">Others</option>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Bedrooms</label>
        <select name="bedrooms">
            <option value="0">Studio</option>
            <option value="1">1 Bedroom</option>
            <option value="2">2 Bedrooms</option>
            <option value="3">3 Bedrooms</option>
            <option value="4">4 Bedrooms</option>
            <option value="5">5 Bedrooms</option>
            <option value="6">6+ Bedrooms</option>
        </select>
    </div>

    <div class="form-group">
        <label>Bathrooms</label>
        <select name="bathrooms">
            <option value="1">1 Bathroom</option>
            <option value="2">2 Bathrooms</option>
            <option value="3">3 Bathrooms</option>
            <option value="4">4 Bathrooms</option>
            <option value="5">5 Bathrooms</option>
            <option value="6">6+ Bathrooms</option>
        </select>
    </div>
</div>

            <div class="form-group full">
                <label>Description</label>
                <textarea name="description" rows="5" placeholder="Describe the property..." required></textarea>
            </div>

            <div class="form-group full">
                <label>Property Photo</label>
                <input type="file" name="main_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                <p class="hint">Upload the real photo of this property (JPG, PNG, GIF, or WEBP). If you skip this, a generic placeholder image will be used instead.</p>
            </div>

            <button type="submit" class="submit-btn">Add Property</button>
        </form>
    </div>
</section>
<style>

.add-property-section{
    padding:25px 20px 45px;
    background:#f5f7f9;
}

.add-property-section h1{
    text-align:center;
    font-size:34px;
    font-weight:800;
    color:#1f2937;
    margin:0 0 6px;
}

.add-property-section .subtitle{
    text-align:center;
    color:#6b7280;
    margin-bottom:18px;
    font-size:15px;
}

.alert{
    max-width:1050px;
    margin:0 auto 20px;
    padding:14px 18px;
    border-radius:12px;
    font-size:14px;
}

.alert-error{
    background:#fee2e2;
    color:#991b1b;
}

.alert-success{
    background:#dcfce7;
    color:#166534;
}

.property-form{
    width:100%;
    max-width:700px;
    margin:0 auto;
    background:#fff;
    padding:28px 35px;
    border-radius:18px;
    border:1px solid #e5e7eb;
    box-shadow:0 10px 25px rgba(0,0,0,.07);
}

.form-row{
    display:flex;
    gap:18px;
}

.form-row .form-group{
    flex:1;
}

.form-group{
    margin-bottom:18px;
}

.form-group.full{
    width:100%;
}

.form-group label{
    display:block;
    margin-bottom:7px;
    font-size:14px;
    font-weight:600;
    color:#374151;
}

.form-group input,
.form-group select,
.form-group textarea{
    width:100%;
    height:48px;
    padding:0 14px;
    border:1px solid #d1d5db;
    border-radius:10px;
    font-size:14px;
    background:#fff;
    transition:.3s;
    box-sizing:border-box;
}

.form-group textarea{
    height:120px;
    padding:14px;
    resize:vertical;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{
    outline:none;
    border-color:#15803d;
    box-shadow:0 0 0 4px rgba(22,163,74,.12);
}

input[type=file]{
    padding:10px;
    height:auto;
}

.hint{
    font-size:12px;
    color:#6b7280;
    margin-top:8px;
    line-height:1.5;
}

.submit-btn{
    display:block;
    width:220px;
    height:48px;
    margin:25px auto 0;
    background:#15803d;
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:15px;
    font-weight:700;
    cursor:pointer;
    transition:.3s ease;
}

.submit-btn:hover{
    background:#166534;
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(22,101,52,.25);
}

@media(max-width:768px){

    .add-property-section{
        padding:20px 15px 35px;
    }

    .property-form{
        padding:22px;
    }

    .form-row{
        flex-direction:column;
        gap:0;
    }

    .submit-btn{
        width:100%;
    }

    .add-property-section h1{
        font-size:28px;
    }

}

</style>
<?php include 'includes/footer.php'; ?>
</body>
</html>
