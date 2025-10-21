<?php
// Sertakan file koneksi.php untuk membuat koneksi ke database
include 'connection.php';

try {
    // Query untuk mengambil data dari tabel tbl_livestock dengan join ke tbl_users dan tbl_farmers
    $query = "
        SELECT 
            farmers.email AS farmer_email,
            farmers.phone,
            farmers.id_card,
            farmers.dob_farmer,
            farmers.province,
            farmers.regency,
            farmers.district,
            farmers.subdistrict,
            farmers.postcode,
            farmers.address,
            farmers.gender,
            users.email
        FROM 
            farmers AS farmers
        JOIN 
            users AS users ON farmers.email = users.email
    ";

    // Eksekusi query menggunakan PDO
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Simpan hasil ke dalam array $farmers_data
    $farmers_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tangkap error jika terjadi
    echo "Error: " . $e->getMessage();
}
?>
