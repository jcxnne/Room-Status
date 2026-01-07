<?php
session_start();

// Database configuration
$host = 'localhost';
$user_db = 'user_db';
$schedule_db = 'schedule';
$notification_db = 'notification';
$username = 'root';
$password = '';

try {
    // Connection for user authentication
    $pdo_user = new PDO("mysql:host=$host;dbname=$user_db", $username, $password);
    $pdo_user->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for schedule management
    $pdo = new PDO("mysql:host=$host;dbname=$schedule_db", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Connection for notifications
    $pdo_notification = new PDO("mysql:host=$host;dbname=$notification_db", $username, $password);
    $pdo_notification->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}



// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

$user_id = $_SESSION['admin_id'];

// Fetch admin user data
$sql = "SELECT * FROM admins WHERE id = ?";
$stmt = $pdo_user->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: admin-login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add':
                $program = $_POST['program'];
                $year_level = $_POST['year_level'];
                $section_number = $_POST['section_number'];
                
                // Generate section name
                $section_name = "$program $year_level-$section_number";
                
                $sql = "INSERT INTO sections (program, year_level, section_number, name) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$program, $year_level, $section_number, $section_name]);
                
                // Create notification
                $notificationHelper->notifySectionChange('add', $section_name, $user_id);
                
                echo json_encode(['success' => true, 'message' => 'Section added successfully']);
                break;
                
            case 'update':
                $id = $_POST['id'];
                $program = $_POST['program'];
                $year_level = $_POST['year_level'];
                $section_number = $_POST['section_number'];
                
                // Generate section name
                $section_name = "$program $year_level-$section_number";
                
                $sql = "UPDATE sections SET program = ?, year_level = ?, section_number = ?, name = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$program, $year_level, $section_number, $section_name, $id]);
                
                // Create notification
                $notificationHelper->notifySectionChange('update', $section_name, $user_id);
                
                echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
                break;
                
            case 'delete':
                $ids = json_decode($_POST['ids'], true);
                
                // Get section names before deleting
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $sql = "SELECT name FROM sections WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
                $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Delete sections
                $sql = "DELETE FROM sections WHERE id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($ids);
                
                // Create notification
                $count = count($ids);
                $section_list = implode(', ', $sections);
                $message = "$count section(s) have been removed from the system: $section_list";
                
                $notificationHelper->createNotification(
                    'alert',
                    'Section(s) Deleted',
                    $message,
                    'all',
                    'trash-2',
                    $user_id
                );
                
                echo json_encode(['success' => true, 'message' => 'Section(s) deleted successfully']);
                break;
                
            case 'get':
                $id = $_POST['id'];
                $sql = "SELECT * FROM sections WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $section = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'section' => $section]);
                break;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred']);
    }
    exit();
}

// Fetch all sections from database
$sql = "SELECT * FROM sections ORDER BY program, year_level, section_number";
$stmt = $pdo->query($sql);
$sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="admin-dashboard.css"> 
  <link rel="stylesheet" href="admin-schedule.css"> 
  <script src="https://unpkg.com/lucide@latest"></script>
  <title>Schedules - Admin Dashboard</title>
</head>
<body>
  <div class="sidebar">
    <!-- Profile Section -->
    <div class="profile">
      <a href="admin-profile.php" class="profile-link">
        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'https://freesvg.org/img/abstract-user-flat-3.png'); ?>" alt="Profile" />
      </a>
      <div class="profile-info">
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?><br>Administrator</p>
      </div>
    </div>

    <!-- Main Navigation Links -->
    <div class="nav-links nav-top">
      <a href="admin-dashboard.php">
        <i data-lucide="layout-dashboard"></i><span>Dashboard</span>
      </a>
      <a href="admin-notification.php">
        <i data-lucide="bell"></i><span>Notification</span>
      </a>
      <a href="admin-schedule.php" class="active">
        <i data-lucide="calendar"></i><span>Schedule</span>
      </a>
      <a href="admin-reservation.php">
        <i data-lucide="clipboard-check"></i><span>Reservation</span>
      </a>
      <a href="admin-audit.php">
        <i data-lucide="file-search"></i><span>Audit</span>
      </a>
      <a href="admin-report.php">
        <i data-lucide="bar-chart-3"></i><span>Reports</span>
      </a>
    </div>

    <!-- Bottom Navigation -->
    <div class="nav-links nav-bottom">
      <a id="darkModeToggle" href="#">
        <i data-lucide="moon" class="icon-moon"></i>
        <i data-lucide="sun" class="icon-sun" style="display: none;"></i>
        <span>Dark Mode</span>
      </a>
      <a href="login-signup.php">
        <i data-lucide="log-out"></i><span>Log Out</span>
      </a>
    </div>
  </div>

  <div class="main">
    <div class="main-header">
      <h1>Schedules</h1>
      <div class="logo"><?php echo strtoupper(substr($user['full_name'], 0, 2)); ?></div>
    </div>

    <!-- Schedule Controls -->
    <div class="schedule-controls">
      <div class="search-wrapper">
        <i data-lucide="search" class="search-icon"></i>
        <input type="text" placeholder="Search section" class="search-bar" id="searchSection" />
      </div>
      
      <select class="department-filter" id="departmentFilter">
        <option value="all">All Programs</option>
        <option value="BSCS">BSCS</option>
        <option value="BSIT">BSIT</option>
      </select>

      <button class="action-btn primary" id="addBtn">
        <i data-lucide="plus"></i> Add
      </button>
      
      <button class="action-btn secondary" id="updateBtn">
        <i data-lucide="edit"></i> Update
      </button>
      
      <button class="action-btn danger" id="deleteBtn">
        <i data-lucide="trash-2"></i> Delete
      </button>
    </div>

    <!-- Sections Grid -->
    <div class="sections-container">
      <?php foreach ($sections as $section): ?>
        <div class="section-card <?php echo strtolower($section['program']); ?>" 
             data-id="<?php echo $section['id']; ?>"
             data-program="<?php echo $section['program']; ?>"
             data-year="<?php echo $section['year_level']; ?>"
             data-section="<?php echo $section['section_number']; ?>"
             data-name="<?php echo strtolower($section['name'] ?? $section['program'] . ' ' . $section['year_level'] . '-' . $section['section_number']); ?>">
          <a href="admin-schedule-management.php?section_id=<?php echo $section['id']; ?>" class="section-link">
            <div class="section-header">
              <h3><?php echo htmlspecialchars($section['name'] ?? $section['program'] . ' ' . $section['year_level'] . '-' . $section['section_number']); ?></h3>
              <input type="checkbox" class="section-checkbox" onclick="event.stopPropagation(); event.preventDefault();" />
            </div>
            <div class="section-info">
              <span class="badge"><?php echo $section['program']; ?></span>
              <span class="year-badge">Year <?php echo $section['year_level']; ?></span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add/Edit Modal -->
  <div class="modal" id="sectionModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Add New Section</h2>
        <button class="close-btn" id="closeModal">
          <i data-lucide="x"></i>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="sectionId" />
        <div class="form-group">
          <label for="sectionProgram">Program</label>
          <select id="sectionProgram">
            <option value="">Select Program</option>
            <option value="BSCS">BSCS</option>
            <option value="BSIT">BSIT</option>
            <option value="BSIS">BSIS</option>
          </select>
        </div>
        <div class="form-group">
          <label for="sectionYear">Year Level</label>
          <select id="sectionYear">
            <option value="">Select Year</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
          </select>
        </div>
        <div class="form-group">
          <label for="sectionNumber">Section Number</label>
          <input type="number" id="sectionNumber" placeholder="e.g., 1, 2, 3" min="1" />
        </div>
        <div class="form-group">
          <label>Preview</label>
          <div id="sectionPreview" style="padding: 10px; background: #f0f0f0; border-radius: 5px; font-weight: bold;">
            -
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-cancel" id="cancelBtn">Cancel</button>
        <button class="btn-save" id="saveBtn">Save</button>
      </div>
    </div>
  </div>

  <script>
    lucide.createIcons();

    // Dark Mode
    (function() {
      'use strict';

      function initDarkMode() {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        if (isDarkMode) {
          document.body.classList.add('dark');
        }
        updateDarkModeUI(isDarkMode);
      }

      function updateDarkModeUI(isDark) {
        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        const moonIcon = toggle.querySelector('.icon-moon');
        const sunIcon = toggle.querySelector('.icon-sun');
        const modeText = toggle.querySelector('span');

        if (moonIcon && sunIcon) {
          if (isDark) {
            moonIcon.style.display = 'none';
            sunIcon.style.display = 'inline';
          } else {
            moonIcon.style.display = 'inline';
            sunIcon.style.display = 'none';
          }
        }

        if (modeText) {
          modeText.textContent = isDark ? 'Light Mode' : 'Dark Mode';
        }
      }

      function toggleDarkMode(e) {
        e.preventDefault();
        const body = document.body;
        const isDark = body.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeUI(isDark);
      }

      initDarkMode();
      
      const toggle = document.getElementById('darkModeToggle');
      if (toggle) {
        toggle.addEventListener('click', toggleDarkMode);
      }
    })();

    // Schedule Functionality
    const searchInput = document.getElementById('searchSection');
    const departmentFilter = document.getElementById('departmentFilter');
    const modal = document.getElementById('sectionModal');
    const addBtn = document.getElementById('addBtn');
    const updateBtn = document.getElementById('updateBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const closeModal = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    
    let isEditMode = false;

    // Search and Filter
    function filterSections() {
      const searchQuery = searchInput.value.toLowerCase();
      const selectedProgram = departmentFilter.value;
      const sectionCards = document.querySelectorAll('.section-card');

      sectionCards.forEach(card => {
        const name = card.dataset.name;
        const program = card.dataset.program;
        
        const matchesSearch = name.includes(searchQuery);
        const matchesProgram = selectedProgram === 'all' || program === selectedProgram;

        if (matchesSearch && matchesProgram) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterSections);
    departmentFilter.addEventListener('change', filterSections);

    // Section Card Selection
    document.addEventListener('click', (e) => {
      const card = e.target.closest('.section-card');
      if (card && !e.target.classList.contains('section-checkbox') && !e.target.closest('.section-link')) {
        const checkbox = card.querySelector('.section-checkbox');
        checkbox.checked = !checkbox.checked;
      }
    });

    // Update section preview
    function updatePreview() {
      const program = document.getElementById('sectionProgram').value;
      const year = document.getElementById('sectionYear').value;
      const section = document.getElementById('sectionNumber').value;
      const preview = document.getElementById('sectionPreview');
      
      if (program && year && section) {
        preview.textContent = `${program} ${year}-${section}`;
      } else {
        preview.textContent = '-';
      }
    }

    document.getElementById('sectionProgram').addEventListener('change', updatePreview);
    document.getElementById('sectionYear').addEventListener('change', updatePreview);
    document.getElementById('sectionNumber').addEventListener('input', updatePreview);

    // Modal Functions
    function openModal(title = 'Add New Section', editMode = false) {
      isEditMode = editMode;
      document.getElementById('modalTitle').textContent = title;
      modal.style.display = 'flex';
      setTimeout(() => {
        modal.classList.add('show');
      }, 10);
      lucide.createIcons();
    }

    function closeModalFunc() {
      modal.classList.remove('show');
      setTimeout(() => {
        modal.style.display = 'none';
        // Clear form
        document.getElementById('sectionId').value = '';
        document.getElementById('sectionProgram').value = '';
        document.getElementById('sectionYear').value = '';
        document.getElementById('sectionNumber').value = '';
        document.getElementById('sectionPreview').textContent = '-';
        isEditMode = false;
      }, 300);
    }

    addBtn.addEventListener('click', () => openModal('Add New Section', false));
    
    updateBtn.addEventListener('click', async () => {
      const selectedCards = document.querySelectorAll('.section-checkbox:checked');
      if (selectedCards.length === 0) {
        alert('Please select a section to update');
        return;
      }
      if (selectedCards.length > 1) {
        alert('Please select only one section to update');
        return;
      }
      
      const card = selectedCards[0].closest('.section-card');
      const sectionId = card.dataset.id;
      
      // Fetch section data
      const formData = new FormData();
      formData.append('action', 'get');
      formData.append('id', sectionId);
      
      const response = await fetch('admin-schedule.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        document.getElementById('sectionId').value = result.section.id;
        document.getElementById('sectionProgram').value = result.section.program;
        document.getElementById('sectionYear').value = result.section.year_level;
        document.getElementById('sectionNumber').value = result.section.section_number;
        updatePreview();
        openModal('Update Section', true);
      }
    });

    deleteBtn.addEventListener('click', async () => {
      const selectedCards = document.querySelectorAll('.section-checkbox:checked');
      if (selectedCards.length === 0) {
        alert('Please select section(s) to delete');
        return;
      }
      
      if (!confirm(`Are you sure you want to delete ${selectedCards.length} section(s)?`)) {
        return;
      }
      
      const ids = Array.from(selectedCards).map(checkbox => 
        checkbox.closest('.section-card').dataset.id
      );
      
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('ids', JSON.stringify(ids));
      
      const response = await fetch('admin-schedule.php', {
        method: 'POST',
        body: formData
      });
      
      const result = await response.json();
      
      if (result.success) {
        alert(result.message);
        location.reload();
      } else {
        alert('Error: ' + result.message);
      }
    });

    closeModal.addEventListener('click', closeModalFunc);
    cancelBtn.addEventListener('click', closeModalFunc);

    saveBtn.addEventListener('click', async () => {
      const program = document.getElementById('sectionProgram').value;
      const year = document.getElementById('sectionYear').value;
      const section = document.getElementById('sectionNumber').value;
      const sectionId = document.getElementById('sectionId').value;

      if (!program || !year || !section) {
        alert('Please fill in all fields');
        return;
      }

      const formData = new FormData();
      formData.append('action', isEditMode ? 'update' : 'add');
      formData.append('program', program);
      formData.append('year_level', year);
      formData.append('section_number', section);
      
      if (isEditMode) {
        formData.append('id', sectionId);
      }

      const response = await fetch('admin-schedule.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        alert(result.message);
        closeModalFunc();
        location.reload();
      } else {
        alert('Error: ' + result.message);
      }
    });

    // Close modal on outside click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModalFunc();
      }
    });
  </script>
</body>
</html>