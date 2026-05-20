<?php
// config/layout.php – shared header/topbar injector
// Usage: include at top of each dashboard page AFTER defining $pageTitle, $activePage, $sidebarLinks
// This file contains a helper function to render the shared HTML head section for consistency across pages.

/**
 * Renders the HTML <head> section.
 * 
 * @param string $pageTitle The title of the page to be displayed in the browser tab.
 */
function renderHead($pageTitle = 'MPES Dashboard') {
    // Retrieve theme from cookie or default to 'light'
    $theme = isset($_COOKIE['mpes-theme']) ? htmlspecialchars($_COOKIE['mpes-theme']) : 'light';
    
    // Output the standard HTML structure, meta tags, and external CSS/JS dependencies
    echo '<!DOCTYPE html>
<html lang="en" data-theme="' . $theme . '">
<head>
  <!-- Character set and viewport configuration for responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Dynamic page title -->
  <title>' . htmlspecialchars($pageTitle) . ' | MPES</title>
  
  <!-- Custom stylesheets -->
  <link rel="stylesheet" href="../assets/css/theme.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  
  <!-- FontAwesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Leaflet CSS and JS for Maps integration -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
  
  <!-- Chart.js for data visualization -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
  
  <!-- Custom application scripts -->
  <script src="../assets/js/main.js" defer></script>
  <script src="../assets/js/dashboard.js" defer></script>
</head>';
}
?>
