<?php
require 'connection.php';

try {
    // Ambil data dari form
    $livestock_id = $_POST['livestock_id'];
    $farmer_id = $_POST['farmer_id'];
    $health = $_POST['health']; 
    $weight = $_POST['weight'];

    // Validasi farmer_id
    $sql_check_farmer = "SELECT farmer_id FROM farmers WHERE farmer_id = :farmer_id";
    $stmt_check_farmer = $conn->prepare($sql_check_farmer);
    $stmt_check_farmer->bindParam(':farmer_id', $farmer_id, PDO::PARAM_STR);
    $stmt_check_farmer->execute();

    // Validasi livestock_id
    $sql_check_livestock = "SELECT livestock_id FROM livestocks WHERE livestock_id = :livestock_id";
    $stmt_check_livestock = $conn->prepare($sql_check_livestock);
    $stmt_check_livestock->bindParam(':livestock_id', $livestock_id, PDO::PARAM_STR);
    $stmt_check_livestock->execute();

    if ($stmt_check_livestock->rowCount() > 0 && $stmt_check_farmer->rowCount() > 0) {
        // Insert data ke tbl_livestock
        $sql_insert = "
            INSERT INTO livestock_status (livestock_id, farmer_id, health, weight)
            VALUES (:livestock_id, :farmer_id, :health, :weight)
        ";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':livestock_id', $livestock_id, PDO::PARAM_STR);
        $stmt_insert->bindParam(':farmer_id', $farmer_id, PDO::PARAM_STR);
        $stmt_insert->bindParam(':health', $health, PDO::PARAM_STR);
        $stmt_insert->bindParam(':weight', $weight, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            echo "Data berhasil disimpan.";
        } else {
            echo "Error: " . implode(", ", $stmt_insert->errorInfo());
        }
    } else {
        echo "Farmer ID atau Livestock ID tidak terdaftar.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
