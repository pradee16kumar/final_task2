<?php


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = $_POST['customer'];
    $customerName = $_POST['customer_name'];
    $products = $_POST['product'];
    $quantities = $_POST['quantity'];
    
    $conn = new mysqli("localhost", "root", "", "task");

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    //$backorder = 0;

    
    $customerCheckSql = "SELECT * FROM customers WHERE id = '$customerId'";
    $customerCheckResult = mysqli_query($conn, $customerCheckSql);

    if (mysqli_num_rows($customerCheckResult) == 0) {
        
        $insertCustomerSql = "INSERT INTO customers (id, customer_name) VALUES ('$customerId', '$customerName')";
        mysqli_query($conn, $insertCustomerSql);
    }

    
    $customerReportCheckSql = "SELECT * FROM customer_report WHERE customer_id = '$customerId'";
    $customerReportCheckResult = mysqli_query($conn, $customerReportCheckSql);

    if (mysqli_num_rows($customerReportCheckResult) == 0) {
        
        $insertCustomerReportSql = "INSERT INTO customer_report (customer_id, order_count_a, order_count_b, order_count_c, order_count_d, order_count_e) 
            VALUES ('$customerId', 0, 0, 0, 0, 0)";
        mysqli_query($conn, $insertCustomerReportSql);
    }

    
    $sql = "INSERT INTO orders (customer_id) VALUES ('$customerId')";
    mysqli_query($conn, $sql);

    
    $orderId = mysqli_insert_id($conn);

    foreach ($products as $index => $product) {
        $quantity = $quantities[$index];
    
        
        $sql = "SELECT quantity FROM inventory WHERE product = '$product'";
        $result = mysqli_query($conn, $sql);
        $inventoryRow = mysqli_fetch_assoc($result);
        $inventory = $inventoryRow['quantity'];
    
        if ($inventory >= $quantity) {
            
            $newQuantity = $inventory - $quantity;
            $updateInventorySql = "UPDATE inventory SET quantity = '$newQuantity' WHERE product = '$product'";
            mysqli_query($conn, $updateInventorySql);
    
            $insertLineSql = "INSERT INTO order_lines (order_id, product, quantity, backordered) VALUES ('$orderId', '$product', '$quantity', 0)";
            mysqli_query($conn, $insertLineSql);
    
            $backorder = 0; 
        } else {
            
            $backorder = $quantity - $inventory;
            $updateInventorySql = "UPDATE inventory SET quantity = 0 WHERE product = '$product'";
            mysqli_query($conn, $updateInventorySql);
    
            $insertLineSql = "INSERT INTO order_lines (order_id, product, quantity, backordered) VALUES ('$orderId', '$product', '$inventory', '$backorder')";
            mysqli_query($conn, $insertLineSql);
        }
    
        
        if ($backorder > 0) {
            $updateOrderReportSql = "UPDATE order_report 
                SET total_order = total_order + '$quantity', 
                    total_backorder = total_backorder + '$backorder'
                WHERE product = '$product'";
        } else {
            $updateOrderReportSql = "UPDATE order_report 
                SET total_order = total_order + '$quantity' WHERE product = '$product'";
        }
        mysqli_query($conn, $updateOrderReportSql);
    
        
        $orderCountColumn = 'order_count_' . strtolower($product);
        $updateCustomerReportSql = "UPDATE customer_report 
            SET $orderCountColumn = $orderCountColumn + 1
            WHERE customer_id = '$customerId'";
        mysqli_query($conn, $updateCustomerReportSql);
    }
    

    echo "Orders processed successfully.";
    mysqli_close($conn);
} else {
    echo "Invalid request method.";
}
?>

