<?php
session_start();
require_once '../db.php';

// Ensure the user is an authenticated admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// Force download headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="vehicle_market_report_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, [
    'Listing ID', 
    'Listing Type', 
    'Brand', 
    'Model', 
    'Year', 
    'Mileage (km)', 
    'Transmission', 
    'Fuel Type', 
    'Registration', 
    'Maintenance',
    'Accident History',
    'Asking Price (PHP)', 
    'System Predicted Value (PHP)', 
    'Status', 
    'Date Added'
]);

// Fetch all processed data (skip the pending queue)
$stmt = $pdo->query("SELECT * FROM vehicle_listings WHERE status != 'pending' ORDER BY brand, model, year_manufactured DESC");

// Loop over the rows, outputting them directly to the CSV
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'],
        $row['listing_type'] === 'scraped' ? 'Gathered Data' : 'User Sale',
        $row['brand'],
        $row['model'],
        $row['year_manufactured'],
        $row['mileage'],
        $row['transmission'],
        $row['fuel_type'],
        $row['registration_status'],
        $row['maintenance_history'],
        $row['accident_history'],
        $row['asking_price'],
        $row['predicted_value'] ?: 'N/A', // If null, output N/A
        ucfirst($row['status']),
        $row['date_added']
    ]);
}

// Clean up and exit so no HTML is accidentally appended to the CSV
fclose($output);
exit;
?>