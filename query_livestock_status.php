<?php
// Sertakan file koneksi.php untuk membuat koneksi ke database
include 'connection.php';

try {
    // Query untuk mengambil data dari tabel tbl_livestock dengan join ke tbl_users dan tbl_farmers
    $query = "
        SELECT 
            livestock_status.farmer_id AS livestock_status_farmer_id,
            livestock_status.livestock_id AS livestock_status_livestock_id,
            livestock_status.health,
            livestock_status.weight,
            livestocks.livestock_id AS livestock_id,
            farmers.farmer_id AS farmer_id
        FROM 
            livestocks AS livestocks
        JOIN 
            users AS users ON livestocks.email = users.email
        JOIN 
            farmers AS farmers ON livestocks.farmer_id = farmers.farmer_id
    ";

    // Eksekusi query menggunakan PDO
    $stmt = $conn->prepare($query);
    $stmt->execute();

    // Ambil hasil dan simpan ke array $livestock_data
    $livestock_status_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Tangani kesalahan query atau koneksi
    die("Query failed: " . $e->getMessage());
}
?>
