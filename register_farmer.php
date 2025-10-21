<?php
require 'connection.php';

try {
    // Ambil data dari form
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $farmer_id = $_POST['farmer_id'];
    $id_card = $_POST['id_card'];
    $dob_farmer = $_POST['dob_farmer'];
    $province = $_POST['province'];
    $regency = $_POST['regency'];
    $district = $_POST['district'];
    $subdistrict = $_POST['subdistrict'];
    $postcode = $_POST['postcode'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];

    // Pengecekan ulang email sebelum penyimpanan
    $sql_check = "SELECT email FROM users WHERE email = :email";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        // Jika email valid, simpan data ke tbl_farmers
        $sql_insert = "
            INSERT INTO farmers (email, phone, farmer_id, id_card, dob_farmer, province, regency, district, subdistrict, postcode, address, gender) 
            VALUES (:email, :phone, :farmer_id, :id_card, :dob_farmer, :province, :regency, :district, :subdistrict, :postcode, :address, :gender)
        ";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt_insert->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt_insert->bindParam(':farmer_id', $farmer_id, PDO::PARAM_STR);
        $stmt_insert->bindParam(':id_card', $id_card, PDO::PARAM_STR);
        $stmt_insert->bindParam(':dob_farmer', $dob_farmer, PDO::PARAM_STR);
        $stmt_insert->bindParam(':province', $province, PDO::PARAM_STR);
        $stmt_insert->bindParam(':regency', $regency, PDO::PARAM_STR);
        $stmt_insert->bindParam(':district', $district, PDO::PARAM_STR);
        $stmt_insert->bindParam(':subdistrict', $subdistrict, PDO::PARAM_STR);
        $stmt_insert->bindParam(':postcode', $postcode, PDO::PARAM_STR);
        $stmt_insert->bindParam(':address', $address, PDO::PARAM_STR);
        $stmt_insert->bindParam(':gender', $gender, PDO::PARAM_STR);

        if ($stmt_insert->execute()) {
            echo "Data berhasil disimpan.";
        } else {
            echo "Error: " . implode(", ", $stmt_insert->errorInfo());
        }
    } else {
        echo "Email tidak terdaftar.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
