<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'מאגר תוכן';
$pageHeader = 'חיפוש במאגר התוכן';

$extraStyles = [
    './assets/css/style.css?v=9',
    "<style>
        .logo-preview {
            max-height: 200px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
        audio {
            width: 100%;
            max-width: 100%;
        }
        .card {
            height: 100%;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-actions {
            margin-top: auto;
        }
    </style>"
];

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// includes/templates/header.php
session_start();

?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'מערכת ניהול' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../../public/assets/css/style.css?v=10" rel="stylesheet">
    <?php if (isset($extraStyles)): ?>
        <?php foreach ($extraStyles as $style): ?>
            <link href="<?= $style ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<?php //include '../../includes/templates/toast.html';
//if (hasPermission('manage_categories')): 

 ?>

    <?php if (isset($pageHeader)): ?>
    <div class="bg-light py-3 mb-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0"><?= $pageHeader ?></h1>
                <?php if (isset($pageHeaderButtons)): ?>
                    <div class="btn-group">
                        <?= $pageHeaderButtons ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="container mt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?? 'info' ?> alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

<div class="container-fluid py-4">
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="searchForm">
                <div class="col-md-3">
                    <label class="form-label">סוג תוכן</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="type" value="songs" id="songs">
                        <label class="btn btn-outline-primary" for="songs">
                            <i class="bi bi-music-note"></i> שירים
                        </label>

                        <input type="radio" class="btn-check" name="type" value="logos" id="logos">
                        <label class="btn btn-outline-primary" for="logos">
                            <i class="bi bi-image"></i> לוגואים
                        </label>
                    </div>
                </div>

                <div class="col-md-3 songs-only">
                    <label for="category" class="form-label">קטגוריה</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">כל הקטגוריות</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="search" class="form-label">חיפוש חופשי</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="הקלד לחיפוש...">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> חפש
                        </button>
                        <button type="button" class="btn btn-secondary d-none" id="clearSearch">
                            <i class="bi bi-x-circle"></i> נקה
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Container -->
    <div id="searchResults">
        <div class="row g-4" id="resultsGrid"></div>
    </div>
</div>

<?php
$extraJs = '
// Debounce function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function setFormFromURL() {
    const params = new URLSearchParams(window.location.search);
    
    document.getElementById("search").value = params.get("search") || "";
    
    const type = params.get("type") || "logos";
    document.getElementById(type).checked = true;
    
    document.getElementById("category").value = params.get("category") || "";
    
    const songsOnly = document.querySelector(".songs-only");
    if (type === "songs") {
        songsOnly.classList.remove("d-none");
        songsOnly.querySelector("select").disabled = false;
    } else {
        songsOnly.classList.add("d-none");
        songsOnly.querySelector("select").disabled = true;
    }
    
    updateClearButtonVisibility();
}

function updateURL() {
    const formData = new FormData(document.getElementById("searchForm"));
    const searchParams = new URLSearchParams(formData);
    history.pushState({}, "", `${location.pathname}?${searchParams}`);
}

function renderSongResult(song) {
    return `
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">${song.title}</h5>
                    <p class="card-text">
                        <small class="text-muted">
                            <i class="bi bi-tag"></i> ${song.category_name}
                        </small>
                    </p>
                    <audio controls class="mb-3">
                        <source src="../public/${song.file_path}" type="audio/mpeg">
                    </audio>
                    <div class="card-actions text-end">
                        <a href="../public/${song.file_path}" 
                           class="btn btn-primary btn-sm"
                           download>
                            <i class="bi bi-download"></i> הורד
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderLogoResult(logo) {
    return `
        <div class="col-md-3 col-sm-6">
            <div class="card">
                <div class="card-body text-center">
                    <img src="../public${logo.logo_path}" 
                         alt="${logo.producer_name_heb}"
                         class="img-fluid logo-preview">
                    <h6 class="card-title">${logo.producer_name_heb}</h6>
                    <p class="card-text">
                        <small class="text-muted">${logo.producer_name_eng}</small>
                    </p>
                    <div class="card-actions">
                        <a href="../public${logo.logo_path}" 
                           class="btn btn-primary btn-sm"
                           download>
                            <i class="bi bi-download"></i> הורד
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

async function performSearch() {
    const formData = new FormData(document.getElementById("searchForm"));
    const searchParams = new URLSearchParams(formData);
    const searchType = formData.get("type");
    const resultsGrid = document.getElementById("resultsGrid");
    
    try {
        const response = await fetch(`../api/v1/search.php?${searchParams}`);
        if (!response.ok) throw new Error("Network response was not ok");
        
        const result = await response.json();
        if (!result.success) throw new Error(result.error);
        
        if (result.data.length === 0) {
            resultsGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle"></i> 
                        לא נמצאו ${searchType === "songs" ? "שירים" : "לוגואים"} 
                        התואמים את החיפוש
                    </div>
                </div>
            `;
            return;
        }
        
        resultsGrid.innerHTML = result.data.map(item => 
            searchType === "songs" ? renderSongResult(item) : renderLogoResult(item)
        ).join("");
        
        if (searchType === "songs") {
            attachAudioEventListeners();
        }
    } catch (error) {
        console.error("Error:", error);
        resultsGrid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-triangle"></i> 
                    אירעה שגיאה בביצוע החיפוש
                </div>
            </div>
        `;
    }
}

function updateClearButtonVisibility() {
    const searchInput = document.getElementById("search");
    const categorySelect = document.getElementById("category");
    const clearButton = document.getElementById("clearSearch");
    
    const hasFilters = searchInput.value.trim() !== "" || categorySelect.value !== "";
    clearButton.classList.toggle("d-none", !hasFilters);
}

function attachAudioEventListeners() {
    document.addEventListener("play", function(e) {
        if (e.target.tagName.toLowerCase() === "audio") {
            document.getElementsByTagName("audio").forEach(audio => {
                if (audio !== e.target) audio.pause();
            });
        }
    }, true);
}

document.addEventListener("DOMContentLoaded", function() {
    setFormFromURL();
    performSearch();
    
    const searchForm = document.getElementById("searchForm");
    searchForm.addEventListener("submit", e => {
        e.preventDefault();
        performSearch();
    });
    
    const debouncedSearch = debounce(performSearch, 300);
    const searchInput = document.getElementById("search");
    searchInput.addEventListener("input", () => {
        updateClearButtonVisibility();
        debouncedSearch();
        updateURL();
    });
    
    document.querySelectorAll("input[name=\'type\']").forEach(radio => {
        radio.addEventListener("change", () => {
            const songsOnly = document.querySelector(".songs-only");
            const categorySelect = songsOnly.querySelector("select");
            
            if (radio.value === "songs") {
                songsOnly.classList.remove("d-none");
                categorySelect.disabled = false;
            } else {
                songsOnly.classList.add("d-none");
                categorySelect.disabled = true;
            }
            
            performSearch();
            updateURL();
        });
    });
    
    const categorySelect = document.getElementById("category");
    categorySelect.addEventListener("change", () => {
        updateClearButtonVisibility();
        performSearch();
        updateURL();
    });
    
    document.getElementById("clearSearch").addEventListener("click", () => {
        searchInput.value = "";
        categorySelect.value = "";
        updateClearButtonVisibility();
        performSearch();
        updateURL();
    });
});';

require_once '../includes/templates/footer.php';
?>