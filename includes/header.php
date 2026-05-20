<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo session_id(); ?>">
    <title>CampusConnect - <?php echo $page_title ?? 'Digital Campus Space'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <style>
    /* Fix for sidebar layout */
    .container-fluid {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .sidebar-card {
        background: white;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .widget-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #0A66C2;
        display: inline-block;
    }
    
    /* Make sure columns don't break */
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    
    .col-lg-3, .col-lg-6, .col-md-4, .col-md-8 {
        position: relative;
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
    }
    
    @media (min-width: 768px) {
        .col-md-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
        .col-md-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
    }
    
    @media (min-width: 992px) {
        .col-lg-3 { flex: 0 0 25%; max-width: 25%; }
        .col-lg-6 { flex: 0 0 50%; max-width: 50%; }
    }
    
    /* Action buttons */
    .action-btn {
        color: #666;
        transition: all 0.2s;
    }
    
    .action-btn:hover {
        color: #0A66C2;
    }
    
    .like-btn .bi-heart-fill {
        color: #dc3545 !important;
    }
    
    /* Toast animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .toast-notification {
        animation: fadeIn 0.3s ease;
    }
</style>
    
    <link href="/campusconnect/assets/css/style.css" rel="stylesheet">
</head>
<body>