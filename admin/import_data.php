<?php
session_start();
require_once '../db.php';

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Unauthorized access.");
}

$csvFile = 'market_data.csv';

if (!file_exists($csvFile)) {
    die("Error: Please make sure you saved the Excel file as 'market_data.csv' in the admin folder.");
}

// Open the CSV file for reading
$file = fopen($csvFile, 'r');
$headers = fgetcsv($file); // Skip the header row

$inserted = 0;
$errors = 0;

echo "<h2 style='font-family: sans-serif;'>Database Migration Started...</h2>";

// Prepare the bulk insert statement
$insert = $pdo->prepare("INSERT INTO vehicle_listings
    (vehicle_type, brand, model, year_manufactured, mileage, color, transmission, fuel_type,
     modifications, registration_status, maintenance_history, accident_history,
     asking_price, location, status, listing_type, seller_name, seller_contact)
    VALUES ('Car', ?, ?, ?, ?, ?, ?, ?, 'Stock / None', 'Unknown', 'Average', 'None', ?, ?, 'approved', 'scraped', 'System Import', ?)");

while (($row = fgetcsv($file)) !== FALSE) {
    try {
        // Map CSV columns based on your Excel file structure
        // [0] Brand, [1] Model, [2] Year, [3] Listing #, [4] Price, [5] Mileage, [6] Transmission, [7] Fuel, [8] Color, [9] Location, [10] Source
        
        $brand = trim($row[0]);
        $model = trim($row[1]);
        $year = (int)trim($row[2]);
        $price = (float)trim($row[4]);
        $mileage = (int)trim($row[5]);
        $transmission = trim($row[6]);
        $fuel = trim($row[7]);
        $color = trim($row[8]);
        $location = trim($row[9]);
        $source = trim($row[10]); // We'll save the scraper source in seller_contact

        // Data Sanitization (Fixing formatting from Excel)
        if (!in_array($transmission, ['Automatic', 'Manual', 'CVT'])) { $transmission = 'Automatic'; }
        if ($fuel === 'Gas') { $fuel = 'Gasoline'; }
        if (!in_array($fuel, ['Gasoline', 'Diesel', 'Hybrid', 'Electric'])) { $fuel = 'Gasoline'; }

        // Execute the insertion
        $insert->execute([
            normalize_title($brand), 
            normalize_title($model), 
            $year, 
            $mileage, 
            $color !== '' ? $color : null, 
            $transmission, 
            $fuel, 
            $price, 
            $location !== '' ? $location : null,
            $source !== '' ? $source : 'External Scrape'
        ]);
        
        $inserted++;
    } catch (PDOException $e) {
        $errors++;
    }
}

fclose($file);

echo "<p style='font-family: sans-serif; color: green;'><b>Migration Complete!</b></p>";
echo "<p style='font-family: sans-serif;'>Successfully inserted: <b>$inserted</b> rows.</p>";
if ($errors > 0) echo "<p style='color: red;'>Failed rows: $errors</p>";
echo "<br><a href='dashboard.php' style='padding: 10px 15px; background: blue; color: white; text-decoration: none; border-radius: 5px; font-family: sans-serif;'>Return to Dashboard</a>";
?>