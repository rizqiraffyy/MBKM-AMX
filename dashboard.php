<?php
session_start(); // Start session

// Cek apakah user_id ada dalam session
if (!isset($_SESSION['username'])) {
    // Jika tidak ada, redirect ke halaman login
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];

require 'connection.php';

try {
    // 1. Fetch Farmers Data (prioritas pertama)
    $query_farmers = "
        SELECT 
            farmers.farmer_id,
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
            farmers.gender
        FROM 
            farmers 
        JOIN 
            users ON farmers.email = users.email
        WHERE 
            farmers.email = :email
    ";
    $stmt_farmers = $conn->prepare($query_farmers);
    $stmt_farmers->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt_farmers->execute();

    $farmers_data = $stmt_farmers->fetchAll(PDO::FETCH_ASSOC);

    // Jika farmers data ditemukan, lanjutkan fetch query lainnya
    if (!empty($farmers_data)) {
        $farmer_id = $farmers_data[0]['farmer_id']; // Ambil farmer_id

          // 2. Fetch Livestock Data (query diperbarui) 
          $query_livestock = "
          SELECT 
              livestocks.email AS livestock_email,
              livestocks.farmer_id AS livestock_farmer_id,
              livestocks.livestock_id,
              livestocks.livestock_img,
              livestocks.breed,
              livestocks.gender,
              livestocks.dob_livestock,
              livestocks.reproductive,
              users.email AS user_email,
              farmers.farmer_id AS farmer_id
          FROM 
              livestocks AS livestocks
          JOIN 
              users AS users ON livestocks.email = users.email
          JOIN 
              farmers AS farmers ON livestocks.farmer_id = farmers.farmer_id
          WHERE 
              livestocks.email = :email AND livestocks.farmer_id = :farmer_id
      ";
      $stmt_livestock = $conn->prepare($query_livestock);
      $stmt_livestock->bindParam(':email', $email, PDO::PARAM_STR);
      $stmt_livestock->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
      $stmt_livestock->execute();

      $livestock_data = $stmt_livestock->fetchAll(PDO::FETCH_ASSOC);
        
      // Query untuk menghitung total ternak reproductive
      $query_total_reproductive = "
      SELECT 
          COUNT(*) AS total_reproductive
      FROM 
          livestocks
      WHERE 
          farmer_id = :farmer_id AND reproductive = 'reproductive'
      ";
      $stmt_total_reproductive = $conn->prepare($query_total_reproductive);
      $stmt_total_reproductive->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
      $stmt_total_reproductive->execute();

      // Ambil hasil query
      $total_reproductive_data = $stmt_total_reproductive->fetch(PDO::FETCH_ASSOC);
      $total_reproductive = $total_reproductive_data['total_reproductive'] ?? 0;


        // 3. Fetch Livestock Status Data
        $query_livestock_status = "
            SELECT 
                livestock_status.farmer_id AS livestock_status_farmer_id,
                livestock_status.livestock_id AS livestock_status_livestock_id,
                livestock_status.health,
                livestock_status.weight,
                livestocks.livestock_id,
                farmers.farmer_id
            FROM 
                livestock_status
            JOIN 
                livestocks ON livestock_status.livestock_id = livestocks.livestock_id
            JOIN 
                farmers ON livestock_status.farmer_id = farmers.farmer_id
            WHERE 
                livestock_status.farmer_id = :farmer_id
            ORDER BY 
                livestock_status.created_at DESC
        ";
        $stmt_livestock_status = $conn->prepare($query_livestock_status);
        $stmt_livestock_status->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt_livestock_status->execute();

        $livestock_status_data = $stmt_livestock_status->fetchAll(PDO::FETCH_ASSOC);
        // Data terbaru (latest data)
        $latest_status = $livestock_status_data[0] ?? null; // Data terbaru
        $previous_status = $livestock_status_data[1] ?? null; // Data sebelumnya

        // Casting data ke numerik
        $latest_status_weight = (float)($latest_status['weight'] ?? 0);
        $previous_status_weight = (float)($previous_status['weight'] ?? 0);

        // Cek nilai untuk menghindari pembagian dengan 0
        if ($previous_status_weight > 0) {
            $livestock_weight_gain = (($latest_status_weight - $previous_status_weight) / $previous_status_weight) * 100;
        } else {
            $livestock_weight_gain = 0;
        }

        // 4. Hitung Rata-rata Health dan Weight Per Bulan
        $query_avg_health_weight = "
        SELECT 
            TO_CHAR(livestock_status.created_at, 'YYYY-MM') AS month,
            AVG(livestock_status.health) AS avg_health,
            AVG(livestock_status.weight) AS avg_weight
        FROM 
            livestock_status
        WHERE 
            livestock_status.farmer_id = :farmer_id
        GROUP BY 
            TO_CHAR(livestock_status.created_at, 'YYYY-MM')
        ORDER BY 
            month DESC
        ";
        $stmt_avg_health_weight = $conn->prepare($query_avg_health_weight);
        $stmt_avg_health_weight->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt_avg_health_weight->execute();

        $avg_health_weight_data = $stmt_avg_health_weight->fetchAll(PDO::FETCH_ASSOC);

        // 5. Ambil Rata-rata Health dan Weight untuk Bulan Ini dan Bulan Sebelumnya
        $query_avg_current_and_previous_month = "
        SELECT 
            TO_CHAR(CURRENT_DATE, 'YYYY-MM') AS current_month,
            TO_CHAR(CURRENT_DATE - INTERVAL '1 month', 'YYYY-MM') AS previous_month,
            TO_CHAR(CURRENT_DATE - INTERVAL '2 month', 'YYYY-MM') AS previous2_month,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE, 'YYYY-MM') THEN health END) AS avg_health_current,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE, 'YYYY-MM') THEN weight END) AS avg_weight_current,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE - INTERVAL '1 month', 'YYYY-MM') THEN health END) AS avg_health_previous,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE - INTERVAL '1 month', 'YYYY-MM') THEN weight END) AS avg_weight_previous,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE - INTERVAL '2 month', 'YYYY-MM') THEN health END) AS avg_health_previous2,
            AVG(CASE WHEN TO_CHAR(created_at, 'YYYY-MM') = TO_CHAR(CURRENT_DATE - INTERVAL '2 month', 'YYYY-MM') THEN weight END) AS avg_weight_previous2
        FROM 
            livestock_status
        WHERE 
            farmer_id = :farmer_id
        ";
        $stmt_avg_current_and_previous_month = $conn->prepare($query_avg_current_and_previous_month);
        $stmt_avg_current_and_previous_month->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt_avg_current_and_previous_month->execute();

        $avg_months_data = $stmt_avg_current_and_previous_month->fetch(PDO::FETCH_ASSOC);

        // Nilai Rata-rata Bulan Ini
        $current_month_weight = (float)($avg_months_data['avg_weight_current'] ?? 0);
        $previous_month_weight = (float)($avg_months_data['avg_weight_previous'] ?? 0);
        $previous2_month_weight = (float)($avg_months_data['avg_weight_previous2'] ?? 0);
        // Nilai Rata-rata Bulan Ini
        $current_month_health = (float)($avg_months_data['avg_health_current'] ?? 0);
        $previous_month_health = (float)($avg_months_data['avg_health_previous'] ?? 0);
        $previous2_month_health = (float)($avg_months_data['avg_health_previous2'] ?? 0);
        // Nama Bulan
        $current_month_name = date('F Y');
        $previous_month_name = date('F Y', strtotime('-1 month'));
        $previous2_month_name = date('F Y', strtotime('-2 month'));

        if ($previous_month_health != 0) {
          $health_change_percentage = $current_month_health - $previous_month_health;
        } else {
          $health_change_percentage = 0; // Jika previous_month_health = 0, set nilai default
        }
        if ($previous_month_weight != 0) {
          $weight_change = $current_month_weight - $previous_month_weight;
        } else {
          $weight_change = 0; // Jika previous_month_health = 0, set nilai default
        }
        if ($previous_month_weight != 0) {
          $weight_gain_percentage = (($current_month_weight - $previous_month_weight) / $previous_month_weight) * 100;
        } else {
          $weight_gain_percentage = 0; // Jika previous_month_health = 0, set nilai default
        }
        if ($previous2_month_weight > 0) {
          $weight_gain2_percentage = (($previous_month_weight - $previous2_month_weight) / $previous2_month_weight) * 100;
        } else {
            $weight_gain2_percentage = 0; // Default jika tidak ada data
        }
        if ($weight_gain2_percentage > 0) {
          $weight_gain_percentage_change = $weight_gain_percentage - $weight_gain2_percentage;
        } else {
            $weight_gain_percentage_change = 0; // Default jika tidak ada data
        }
        $query_avg_health_weight = "
    SELECT 
        TO_CHAR(livestock_status.created_at, 'YYYY-MM') AS month,
        AVG(livestock_status.health) AS avg_health,
        AVG(livestock_status.weight) AS avg_weight
    FROM 
        livestock_status
    WHERE 
        livestock_status.farmer_id = :farmer_id
    GROUP BY 
        TO_CHAR(livestock_status.created_at, 'YYYY-MM')
    ORDER BY 
        month ASC
";
$stmt_avg_health_weight = $conn->prepare($query_avg_health_weight);
$stmt_avg_health_weight->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
$stmt_avg_health_weight->execute();

$avg_health_weight_data = $stmt_avg_health_weight->fetchAll(PDO::FETCH_ASSOC);

// Format data untuk JSON
$timeline = [];
$avg_health = [];
$avg_weight = [];
$weight_gain = [];

// Proses data untuk grafik
$previous_weight = null;
foreach ($avg_health_weight_data as $row) {
    $timeline[] = $row['month'];
    $avg_health[] = round($row['avg_health'], 1);
    $avg_weight[] = round($row['avg_weight'], 1);

    // Hitung weight gain dibandingkan bulan sebelumnya
    if ($previous_weight !== null) {
        $weight_gain[] = round((($row['avg_weight'] - $previous_weight) / $previous_weight) * 100, 1);
    } else {
        $weight_gain[] = 0; // Tidak ada perbandingan untuk bulan pertama
    }
    $previous_weight = $row['avg_weight'];
}
        // 6. Hitung Total Jumlah Ternak Berdasarkan farmer_id
        $query_livestock_count = "
        SELECT 
            COUNT(livestock_id) AS total_livestock
        FROM 
            livestocks
        WHERE 
            farmer_id = :farmer_id
        ";
        $stmt_livestock_count = $conn->prepare($query_livestock_count);
        $stmt_livestock_count->bindParam(':farmer_id', $farmer_id, PDO::PARAM_INT);
        $stmt_livestock_count->execute();

        // Ambil hasil query
        $total_livestock_data = $stmt_livestock_count->fetch(PDO::FETCH_ASSOC);
        $total_livestock = $total_livestock_data['total_livestock'] ?? 0; // Default 0 jika tidak ada ternak
        // RUMUS
        $farm_rating = (($weight_gain_percentage * 10) + $current_month_health) / 2;
        //RULES
        $farm_rating = ceil($farm_rating); // Pastikan nilainya bulat ke atas
        // Klasifikasi berdasarkan rating
        if ($farm_rating > 90) {
            $status = "Excellent";
        } elseif ($farm_rating >= 76) {
            $status = "Good";
        } elseif ($farm_rating >= 51) {
            $status = "Fair";
        } elseif ($farm_rating >= 26) {
            $status = "Poor";
        } else {
            $status = "Bad";
        }

    } else {
        // Kosongkan jika tidak ada data
        $livestock_data = [];
        $livestock_status_data = [];
    }

} catch (PDOException $e) {
    // Tangkap error jika terjadi
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>UGM x SMITEK</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/typicons/typicons.css">
    <link rel="stylesheet" href="assets/vendors/simple-line-icons/css/simple-line-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <link rel="stylesheet" href="assets/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
    <link rel="stylesheet" type="text/css" href="assets/js/select.dataTables.min.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="assets/images/mini-logo-ugm.png" />
  </head>
  <body class="with-welcome-text">
    <div class="container-scroller">
      <!-- partial:partials/_navbar.html -->
      <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
        <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
          <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
              <span class="icon-menu"></span>
            </button>
          </div>
          <div>
            <a class="navbar-brand brand-logo" href="#dashboard">
              <img src="assets/images/logo-ugm.png" alt="logo" />
            </a>
            <a class="navbar-brand brand-logo-mini" href="#dashboard">
              <img src="assets/images/mini-logo-ugm.png" alt="logo" />
            </a>
          </div>
        </div>
        <div class="navbar-menu-wrapper d-flex align-items-top">
          <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">
              <h1 class="welcome-text">Hi, <span class="text-black fw-bold"><?php echo htmlspecialchars($fullname); ?></span></h1>
              <h3 class="welcome-sub-text">Welcome to your farm performance summary!</h3>
            </li>
          </ul>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item d-none d-lg-block">
              <div id="datepicker-popup" class="input-group date datepicker navbar-date-picker">
                <span class="input-group-addon input-group-prepend border-right">
                  <span class="icon-calendar input-group-text calendar-icon"></span>
                </span>
                <input type="text" class="form-control" id="date-input">
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                <i class="icon-bell"></i>
                <span class="count"></span>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown">
                <a class="dropdown-item py-3 border-bottom">
                  <p class="mb-0 fw-medium float-start">You have 4 new notifications </p>
                  <span class="badge badge-pill badge-primary float-end">View all</span>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-alert m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">Application Error</h6>
                    <p class="fw-light small-text mb-0"> Just now </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-lock-outline m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">Settings</h6>
                    <p class="fw-light small-text mb-0"> Private message </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item py-3">
                  <div class="preview-thumbnail">
                    <i class="mdi mdi-airballoon m-auto text-primary"></i>
                  </div>
                  <div class="preview-item-content">
                    <h6 class="preview-subject fw-normal text-dark mb-1">New user registration</h6>
                    <p class="fw-light small-text mb-0"> 2 days ago </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link count-indicator" id="countDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="icon-mail icon-lg"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="countDropdown">
                <a class="dropdown-item py-3">
                  <p class="mb-0 fw-medium float-start">You have 7 unread mails </p>
                  <span class="badge badge-pill badge-primary float-end">View all</span>
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face10.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Marian Garner </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/faces/face12.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">David Grey </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
                <a class="dropdown-item preview-item">
                  <div class="preview-thumbnail">
                    <img src="assets/images/profile.jpg" alt="image" class="img-sm profile-pic">
                  </div>
                  <div class="preview-item-content flex-grow py-2">
                    <p class="preview-subject ellipsis fw-medium text-dark">Travis Jenkins </p>
                    <p class="fw-light small-text mb-0"> The meeting is cancelled </p>
                  </div>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown d-none d-lg-block user-dropdown">
              <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="img-xs rounded-circle" src="assets/images/profile.jpg" alt="Profile image"> </a>
              <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                <div class="dropdown-header text-center">
                  <img class="img-xs rounded-circle" src="assets/images/profile.jpg" alt="Profile image">
                  <p class="mb-1 mt-3 fw-semibold"><?php echo htmlspecialchars($fullname); ?></p>
                  <p class="fw-light text-muted mb-0"><?php echo htmlspecialchars($email); ?></p>
                </div>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> My Profile <span class="badge badge-pill badge-danger">1</span></a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-message-text-outline text-primary me-2"></i> Messages</a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-calendar-check-outline text-primary me-2"></i> Activity</a>
                <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i> FAQ</a>
                <a class="dropdown-item" href="logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Sign Out</a>
              </div>
            </li>
          </ul>
          <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
          </button>
        </div>
      </nav>
      <!-- partial -->
      <div class="container-fluid page-body-wrapper">
        <!-- partial:partials/_sidebar.html -->
        <nav class="sidebar sidebar-offcanvas" id="sidebar">
          <ul class="nav">
            <li class="nav-item">
              <a class="nav-link" href="#dashboard">
                <i class="mdi mdi-grid-large menu-icon"></i>
                <span class="menu-title">Dashboard</span>
              </a>
            </li>
            <li class="nav-item nav-category">Main</li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#tables" aria-expanded="false" aria-controls="tables">
                <i class="menu-icon mdi mdi-table"></i>
                <span class="menu-title">Farm Tables</span>
                <i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="tables">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"> <a class="nav-link" href="#farm-table">Main Table</a></li>
                </ul>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-bs-toggle="collapse" href="#form-elements" aria-expanded="false" aria-controls="form-elements">
                <i class="menu-icon mdi mdi-card-text-outline"></i>
                <span class="menu-title">Forms</span>
                <i class="menu-arrow"></i>
              </a>
              <div class="collapse" id="form-elements">
                <ul class="nav flex-column sub-menu">
                  <li class="nav-item"><a class="nav-link" href="#farmer-register">Farmer Register</a></li>
                  <li class="nav-item"><a class="nav-link" href="#livestock-register">Livestock Register</a></li>
                </ul>
              </div>
            </li>
          </ul>
        </nav>
        <!-- partial -->
        <div class="main-panel">
          <!-- Dashboard -->
          <section id="dashboard" class="section active">
            <div class="content-wrapper">
              <div class="row">
                <div class="col-sm-12">
                  <div class="home-tab">
                    <div class="tab-content tab-content-basic">
                      <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="statistics-details d-flex align-items-center justify-content-between">
                              <div>
                                <p class="statistics-title">Total livestock</p>
                                <h3 class="rate-percentage"><?php echo htmlspecialchars($total_livestock ?? '0'); ?></h3>
                              </div>
                              <div class="d-none d-md-block">
                                <p class="statistics-title">Reproductive</p>
                                <h3 class="rate-percentage"><?php echo htmlspecialchars($total_reproductive); ?></h3>
                              </div>
                              <div>
                                <p class="statistics-title">Average Health</p>
                                <h3 class="rate-percentage"><?php echo htmlspecialchars(number_format($current_month_health ?? '0', 1)); ?>%</h3>
                                <p class="text-danger d-flex"><i class="mdi mdi-menu-down"></i><span><?php echo htmlspecialchars($health_change_percentage ?? '0'); ?>%</span></p>
                              </div>
                              <div>
                                <p class="statistics-title">Average Weight</p>
                                <h3 class="rate-percentage"><?php echo htmlspecialchars(number_format($current_month_weight ?? '0',1)); ?>Kg</h3>
                                <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span><?php echo htmlspecialchars($weight_change ?? '0'); ?>Kg</span></p>
                              </div>
                              <div>
                                <p class="statistics-title">Weight Gain</p>
                                <h3 class="rate-percentage"><?php echo htmlspecialchars(number_format($weight_gain_percentage ?? '0', 1)); ?>%</h3>
                                <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span><?php echo htmlspecialchars(number_format($weight_gain_percentage_change ?? '0', 1)); ?>%</span></p>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-lg-8 d-flex flex-column">
                            <div class="row flex-grow">
                              <div class="col-12 col-lg-4 col-lg-12 grid-margin stretch-card">
                                <div class="card card-rounded">
                                  <div class="card-body">
                                    <h4 class="card-title">Farm Overview</h4>
                                    <div class="d-sm-flex justify-content-between align-items-start">
                                      <div id="performanceLine-legend"></div>
                                    </div>
                                    <div class="chartjs-wrapper mt-4">
                                      <canvas id="performanceLine"></canvas>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-lg-4 d-flex flex-column">
                            <div class="row flex-grow">
                              <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                <div class="card bg-primary card-rounded">
                                  <div class="card-body pb-0">
                                    <h4 class="card-title card-title-dash text-white mb-4">Farm Rating</h4>
                                    <div class="row">
                                      <div class="col-sm-4">
                                      <p class="status-summary-ight-white mb-1"><?php echo htmlspecialchars($status ?? '-'); ?></p>
                                        <h2 class="text-info"><?php echo htmlspecialchars(ceil($farm_rating ?? '0')); ?>%</h2>
                                      </div>
                                      <div class="col-sm-8">
                                        <div class="status-summary-chart-wrapper pb-4">
                                          <canvas id="status-summary"></canvas>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                <div class="card card-rounded">
                                  <div class="card-body">
                                    <ul class="bullet-line-list">
                                      <div class="d-flex align-items-center justify-content-between mb-3">
                                        <h4 class="card-title card-title-dash">Status</h4>
                                      </div>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Excellent</span></div>
                                          <p>10 Livestock</p>
                                        </div>
                                      </li>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Good</span></div>
                                          <p>10 Livestock</p>
                                        </div>
                                      </li>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Fair</span></div>
                                          <p>10 Livestock</p>
                                        </div>
                                      </li>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Poor</span></div>
                                          <p>10 Livestock</p>
                                        </div>
                                      </li>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Bad</span></div>
                                          <p>10 Livestock</p>
                                        </div>
                                      </li>
                                      <li>
                                        <div class="d-flex justify-content-between">
                                          <div><span class="text-light-green">Last Measurement</span></div>
                                          <p>10 Days</p>
                                        </div>
                                      </li>
                                    </ul>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
          <!-- Dashboard -->
          <!-- Farm Table -->
          <section id="farm-table" class="sectio">
            <div class="content-wrapper">
              <div class="row">
                <div class="col-lg-12 grid-margin stretch-card">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Main Table</h4>
                      <div class="table-responsive">
                          <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Livestock</th>
                                    <th>ID</th>
                                    <th>Details</th>
                                    <th>Age</th>
                                    <th>Health</th>
                                    <th>Weight</th>
                                    <th>Weight Gain</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($livestock_data)): ?>
                                    <?php foreach ($livestock_data as $row): ?>
                                        <?php
                                        // Cari data health dan weight dari livestock_status_data berdasarkan livestock_id
                                        $health = '-';
                                        $weight = '-';
                                        foreach ($livestock_status_data as $status) {
                                            if ($status['livestock_id'] === $row['livestock_id']) {
                                                $health = htmlspecialchars($status['health']) . '%';
                                                $weight = htmlspecialchars($status['weight']) . ' Kg';
                                                break;
                                            }
                                        }
                                        $dob = new DateTime($row['dob_livestock']);
                                        $now = new DateTime();
                                        $age = $dob->diff($now)->format('%y Y, %m M, %d D');
                                        ?>
                                        <tr>
                                            <td class="py-1">
                                              <?php if (!empty($row['livestock_img'])): ?>
                                                  <img class="img-lg rounded-circle" src="path/to/uploads/<?php echo htmlspecialchars($row['livestock_img']); ?>" alt="Livestock image">
                                              <?php else: ?>
                                                  <img class="img-lg rounded-circle" src="assets/images/profile.jpg" alt="Default image">
                                              <?php endif; ?>
                                            </td>
                                            <td>
                                              <p><?php echo htmlspecialchars($row['livestock_id']); ?></p>
                                              <p><?php echo htmlspecialchars($row['breed']); ?></p>
                                            </td>
                                            <td>
                                              <p><?php echo htmlspecialchars($row['gender']); ?></p>
                                              <p><?php echo htmlspecialchars($row['reproductive']); ?></p>
                                            </td>
                                            <td>
                                              <p><?php echo $age; ?></p>
                                            </td>
                                            <td>
                                              <p><?php echo $health; ?></p>
                                            </td>
                                            <td>
                                              <p><?php echo $weight; ?></p>
                                            </td>
                                            <td>
                                              <p><?php echo htmlspecialchars(number_format($livestock_weight_gain, 1)); ?>%</p>
                                            </td>
                                            <td>
                                              <p><a href="graph.php?id=<?php echo urlencode($row['livestock_id']); ?>">View Graph</a></p>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>
          <!-- Register Forms -->
            <!-- Farmer -->
            <section id="farmer-register" class="sectio">
              <div class="content-wrapper">
                <div class="row">
                  <div class="col-12 grid-margin">
                    <div class="card">
                      <div class="card-body">
                        <h4 class="card-title">Farmer Registration</h4>
                        <form class="form-sample" action="register_farmer.php" method="POST" enctype="multipart/form-data">
                          <p class="card-description"> Farmer Details </p>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                  <input type="email" class="form-control" required readonly id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" value="<?php echo htmlspecialchars($email); ?>"/>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Phone Number</label>
                                <div class="col-sm-9">
                                  <input type="tel" class="form-control" required id="phone" name="phone" pattern="0[0-9]{9,14}" title="Valid phone number starting with 0 and 10-15 digits long."/>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Farmer ID</label>
                                <div class="col-sm-9">
                                  <input type="text" class="form-control" required id="farmer_id" name="farmer_id" placeholder="Scan Farmer ID"/>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">ID Card</label>
                                <div class="col-sm-9">
                                  <input type="tel" class="form-control" required id="id_card" name="id_card" pattern="[0-9]{16}" maxlength="16" placeholder="Enter 16-digit ID card number" title="Valid 16 digits ID Card."/>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Gender</label>
                                <div class="col-sm-9">
                                  <select class="form-select" required id="gender" name="gender">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                  </select>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Date of Birth</label>
                                <div class="col-sm-9">
                                  <input type="date" class="form-control" required id="dob_farmer" name="dob_farmer" placeholder="dd/mm/yyyy" max="2024-12-31" title="Select date of birth"/>
                                </div>
                              </div>
                            </div>
                          </div>
                          <p class="card-description"> Address </p>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Province</label>
                                <div class="col-sm-9">
                                  <select class="form-control" required id="province" name="province"></select>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Regency</label>
                                <div class="col-sm-9">
                                  <select class="form-control" required id="regency" name="regency"></select>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">District</label>
                                <div class="col-sm-9">
                                  <select class="form-control" required  id="district" name="district"></select>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Sub District</label>
                                <div class="col-sm-9">
                                  <select class="form-control" required id="subdistrict" name="subdistrict"></select>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Post Code</label>
                                <div class="col-sm-9">
                                  <input type="number" class="form-control" required id="postcode" name="postcode" maxlength="5" pattern="\d{5}" title="Enter a valid 5-digit postal code"/>
                                </div>
                              </div>
                            </div>
                            <div class="col-md-6">
                              <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Full Address</label>
                                <div class="col-sm-9">
                                  <input type="text" class="form-control" required id="address" name="address"/>
                                </div>
                              </div>
                            </div>
                          </div>
                          <button type="submit" class="btn btn-primary me-2">Register</button>
                          <button type="reset" class="btn btn-light">Reset</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>
            <!-- User -->
            <!-- Livestock -->
            <section id="livestock-register" class="sectio">
            <div class="content-wrapper">
              <div class="row">
                <div class="col-12 grid-margin">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Livestock Registration</h4>
                      <form class="form-sample" action="register_livestock.php" method="POST" enctype="multipart/form-data">
                        <p class="card-description">Validate Information</p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Email</label>
                              <div class="col-sm-9">
                                <input type="email" class="form-control" required id="email" name="email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" value="<?php echo htmlspecialchars($email); ?>"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Farmer ID</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" required id="farmer_id" name="farmer_id" placeholder="Scan Farmer ID Card"/>
                              </div>
                            </div>
                          </div>
                        </div>
                        <p class="card-description">Livestock Details</p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Livestock ID</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" required id="livestock_id" name="livestock_id" placeholder="Scan Livestock ID Tag"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Image</label>
                              <div class="col-sm-9">
                                <input type="file" class="form-control" required id="livestock_img" name="livestock_img" accept=".jpg, .jpeg, .png" title="Valid image file (.jpg, .jpeg, .png)"/>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Breed</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" required id="breed" name="breed"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Gender</label>
                              <div class="col-sm-9">
                                <select class="form-select" required id="gender" name="gender">
                                  <option value="male">Male</option>
                                  <option value="female">Female</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Date of Birth</label>
                              <div class="col-sm-9">
                                <input type="date" class="form-control" required id="dob_livestock" name="dob_livestock" required max="2024-12-31" title="Select date of birth"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Reproductive</label>
                              <div class="col-sm-9">
                                <select class="form-select" required id="reproductive" name="reproductive">
                                  <option value="reproductive">Reproductive</option>
                                  <option value="unreproductive">Unreproductive</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>
                        <button type="submit" class="btn btn-primary me-2">Register</button>
                        <button class="btn btn-light">Reset</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </section>
            <!-- Livestock Status -->
            <section id="livestock-status" class="sectio">
            <div class="content-wrapper">
              <div class="row">
                <div class="col-12 grid-margin">
                  <div class="card">
                    <div class="card-body">
                      <h4 class="card-title">Livestock Status</h4>
                      <form class="form-sample" action="register_livestock_status.php" method="POST" enctype="multipart/form-data">
                      <p class="card-description">Validate Information</p>
                      <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Farmer ID</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" required id="farmer_id" name="farmer_id" placeholder="Scan Farmer ID Card"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Livestock ID</label>
                              <div class="col-sm-9">
                                <input type="text" class="form-control" required id="livestock_id" name="livestock_id" placeholder="Scan Livestock ID Card"/>
                              </div>
                            </div>
                          </div>
                        </div>
                        <p class="card-description">Status Details</p>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Health</label>
                              <div class="col-sm-9">
                                <input type="number" class="form-control" required id="health" name="health"/>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group row">
                              <label class="col-sm-3 col-form-label">Weight</label>
                              <div class="col-sm-9">
                                <input type="number" class="form-control" required name="weight" id="weight"/>
                              </div>
                            </div>
                          </div>
                        </div>
                        <button type="submit" class="btn btn-primary me-2">Register</button>
                        <button class="btn btn-light">Reset</button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            </section>
            <!-- Livestock Status -->
            <!-- Livestock -->
          <!-- Register -->
          <!-- content-wrapper ends -->
          <!-- partial:partials/_footer.html -->
          <footer class="footer">
            <div class="d-sm-flex justify-content-center justify-content-sm-between">
              <span class="float-none float-sm-end d-block mt-1 mt-sm-0 text-center">Copyright © 2023 by Rizqi Raffy. All rights reserved.</span>
            </div>
          </footer>
          <!-- partial -->
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- Plugin js for this page -->
    <script src="assets/vendors/chart.js/chart.umd.js"></script>
    <script src="assets/vendors/progressbar.js/progressbar.min.js"></script>
    <!-- End plugin js for this page -->
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <!-- endinject -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/hoverable-collapse.js"></script>
    <script src="assets/js/todolist.js"></script>
    <script src="assets/js/extend.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="assets/js/dashboard.js"></script>
    <!-- <script src="assets/js/Chart.roundedBarCharts.js"></script> -->
    <!-- End custom js for this page-->
  </body>
</html>