<?php
include 'db_connect.php';

echo "Syncing Permissions...\n";

// Sync Sellers
$sql_seller = "UPDATE users u 
               JOIN seller_applications sa ON u.user_id = sa.user_id 
               SET u.seller_approved = 1 
               WHERE sa.status = 'Approved'";

if ($conn->query($sql_seller)) {
    echo "Seller permissions synced. Rows updated: " . $conn->affected_rows . "\n";
} else {
    echo "Error syncing sellers: " . $conn->error . "\n";
}

// Sync Delivery
$sql_delivery = "UPDATE users u 
                 JOIN delivery_applications da ON u.user_id = da.user_id 
                 SET u.delivery_approved = 1 
                 WHERE da.status = 'Approved'";

if ($conn->query($sql_delivery)) {
    echo "Delivery permissions synced. Rows updated: " . $conn->affected_rows . "\n";
} else {
    echo "Error syncing delivery: " . $conn->error . "\n";
}

echo "Done.\n";
?>
