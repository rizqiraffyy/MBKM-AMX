<?php
require 'connection.php';

try {
    // Ambil data dari form
    $livestock_id = $_POST['livestock_id'];
    $email = $_POST['email'];
    $farmer_id = $_POST['farmer_id'];
    $livestock_img = $_FILES['livestock_img']['name']; // Nama file gambar
    $breed = $_POST['breed'];
    $gender = $_POST['gender'];
    $dob_livestock = $_POST['dob_livestock'];
    $reproductive = $_POST['reproductive'];

    // Validasi email
    $sql_check_email = "SELECT email FROM users WHERE email = :email";
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt_check_email->execute();

    // Validasi farmer_id
    $sql_check_farmer = "SELECT farmer_id FROM farmers WHERE farmer_id = :farmer_id";
    $stmt_check_farmer = $conn->prepare($sql_check_farmer);
    $stmt_check_farmer->bindParam(':farmer_id', $farmer_id, PDO::PARAM_STR);
    $stmt_check_farmer->execute();

    if ($stmt_check_email->rowCount() > 0 && $stmt_check_farmer->rowCount() > 0) {
        // Insert data ke tbl_livestock
        $sql_insert = "
            INSERT INTO livestocks (livestock_id, email, farmer_id, livestock_img, breed, gender, dob_livestock, reproductive)
            VALUES (:livestock_id, :email, :farmer_id, :livestock_img, :breed, :gender, :dob_livestock, :reproductive)
        ";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':livestock_id', $livestock_id, PDO::PARAM_STR);
        $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt_insert->bindParam(':farmer_id', $farmer_id, PDO::PARAM_STR);
        $stmt_insert->bindParam(':livestock_img', $livestock_img, PDO::PARAM_STR);
        $stmt_insert->bindParam(':breed', $breed, PDO::PARAM_STR);
        $stmt_insert->bindParam(':gender', $gender, PDO::PARAM_STR);
        $stmt_insert->bindParam(':dob_livestock', $dob_livestock, PDO::PARAM_STR);
        $stmt_insert->bindParam(':reproductive', $reproductive, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            echo "Data berhasil disimpan.";
        } else {
            echo "Error: " . implode(", ", $stmt_insert->errorInfo());
        }
    } else {
        echo "Email atau Farmer ID tidak terdaftar.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>