<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schedules</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="client/styles/schedule.css">
</head>

<body>

  <main class="main">
    <div class="main-header">
      <h1>Schedules</h1>
    </div>

    <div class="schedule-container">
      <div class="controls">
        <div class="search-wrapper">
          <div class="search-icon">
            <svg viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
          </div>
          <input type="text" class="search-input" id="searchInput" placeholder="Search section">
        </div>

        <div class="department-filter">
          <button class="department-button" id="departmentButton">Department</button>
          <div class="department-dropdown" id="departmentDropdown">
            <div class="department-option">
              <span>Department of computer studies</span>
              <svg viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </div>
            <div class="department-option add-department">
              <span>Add more</span>
              <svg viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </div>
          </div>
        </div>

        <div class="action-buttons">
          <button class="btn btn-add" id="addButton">Add</button>
          <button class="btn btn-update" id="updateButton" disabled>Update</button>
          <button class="btn btn-delete" id="deleteButton">
            <svg viewBox="0 0 24 24">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
        </div>
      </div>

      <div class="sections-grid" id="sectionsGrid"></div>
    </div>
  </main>

  <div class="modal-overlay" id="modalOverlay">
    <div class="modal">
      <h2 id="modalTitle">Add Schedule</h2>

      <div class="form-group">
        <label class="form-label">Department</label>
        <input type="text" class="form-input" id="departmentInput" placeholder="Enter department">
      </div>

      <div class="form-group">
        <label class="form-label">Program and Section</label>
        <input type="text" class="form-input" id="sectionInput" placeholder="Enter program and section">
      </div>

      <div class="form-group">
        <label class="form-label">Select Background Color</label>
        <div class="color-selector">
          <div class="color-options" id="colorOptions"></div>
          <button class="add-color-btn" id="addColorBtn">+ Add color</button>
        </div>
      </div>

      <div class="form-group">
        <div class="upload-section">
          <div class="upload-icon">
            <svg viewBox="0 0 24 24">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="12" y1="18" x2="12" y2="12"></line>
              <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
          </div>
          <div class="upload-text">Drop or .Pdf</div>
          <div class="upload-link">Drag or Drop Files to Upload</div>
          <div class="upload-button">Select file to upload</div>
        </div>
      </div>

      <div class="modal-actions">
        <button class="modal-btn modal-btn-cancel" id="cancelButton">Cancel</button>
        <button class="modal-btn modal-btn-save" id="saveButton">Save</button>
      </div>
    </div>
  </div>

  <script>
    const sections = [
      { name: 'BSCS 1-1', color: '#7f1d1d' },
      { name: 'BSCS 1-2', color: '#7f1d1d' },
      { name: 'BSCS 1-3', color: '#7f1d1d' },
      { name: 'BSCS 1-4', color: '#7f1d1d' },
      { name: 'BSCS 1-5', color: '#7f1d1d' },
      { name: 'BSCS 2-1', color: '#7f1d1d' },
      { name: 'BSCS 2-2', color: '#7f1d1d' },
      { name: 'BSCS 2-3', color: '#7f1d1d' },
      { name: 'BSCS 2-4', color: '#7f1d1d' },
      { name: 'BSCS 2-5', color: '#7f1d1d' },
      { name: 'BSCS 3-1', color: '#7f1d1d' },
      { name: 'BSCS 3-2', color: '#7f1d1d' },
      { name: 'BSCS 3-3', color: '#7f1d1d' },
      { name: 'BSCS 3-4', color: '#7f1d1d' },
      { name: 'BSCS 4-1', color: '#7f1d1d' },
      { name: 'BSCS 4-2', color: '#7f1d1d' },
      { name: 'BSCS 4-3', color: '#7f1d1d' },
      { name: 'BSIT 1-1', color: '#1a1a1a' },
      { name: 'BSIT 1-2', color: '#1a1a1a' },
      { name: 'BSIT 1-3', color: '#1a1a1a' }
    ];

    const availableColors = ['#7f1d1d', '#1a1a1a', '#059669', '#dc2626', '#7c3aed', '#ea580c'];
    let selectedSection = null;
    let editMode = false;

    const sectionsMap = new Map();
    sections.forEach((section, index) => {
      sectionsMap.set(section.name.toLowerCase(), index);
    });

    function renderSections() {
      const grid = document.getElementById('sectionsGrid');
      grid.innerHTML = '';
      sections.forEach((section, index) => {
        const badge = document.createElement('div');
        badge.className = 'section-badge';
        badge.style.backgroundColor = section.color;
        badge.textContent = section.name;
        badge.dataset.index = index;
        badge.addEventListener('click', () => selectSection(index));
        grid.appendChild(badge);
      });
    }

    function selectSection(index) {
      const badges = document.querySelectorAll('.section-badge');
      badges.forEach(b => b.classList.remove('selected'));
      badges[index].classList.add('selected');
      selectedSection = index;
      document.getElementById('updateButton').disabled = false;
    }

    function renderColorOptions() {
      const container = document.getElementById('colorOptions');
      container.innerHTML = '';
      availableColors.forEach(color => {
        const option = document.createElement('div');
        option.className = 'color-option';
        option.style.backgroundColor = color;
        option.dataset.color = color;
        option.addEventListener('click', () => {
          document.querySelectorAll('.color-option').forEach(o => o.classList.remove('selected'));
          option.classList.add('selected');
        });
        container.appendChild(option);
      });
      container.firstChild?.classList.add('selected');
    }

    document.getElementById('searchInput').addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase().trim();
      const badges = document.querySelectorAll('.section-badge');

      if (!query) {
        badges.forEach(badge => badge.classList.remove('hidden'));
        return;
      }

      badges.forEach(badge => {
        const name = sections[badge.dataset.index].name.toLowerCase();
        badge.classList.toggle('hidden', !name.includes(query));
      });
    });

    document.getElementById('departmentButton').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('departmentDropdown').classList.toggle('active');
    });

    document.addEventListener('click', () => {
      document.getElementById('departmentDropdown').classList.remove('active');
    });

    document.getElementById('addButton').addEventListener('click', () => {
      editMode = false;
      document.getElementById('modalTitle').textContent = 'Add Schedule';
      document.getElementById('departmentInput').value = '';
      document.getElementById('sectionInput').value = '';
      renderColorOptions();
      document.getElementById('modalOverlay').classList.add('active');
    });

    document.getElementById('updateButton').addEventListener('click', () => {
      if (selectedSection === null) return;
      editMode = true;
      const section = sections[selectedSection];
      document.getElementById('modalTitle').textContent = 'Update Schedule';
      document.getElementById('departmentInput').value = '';
      document.getElementById('sectionInput').value = section.name;
      renderColorOptions();
      document.querySelectorAll('.color-option').forEach(opt => {
        if (opt.dataset.color === section.color) {
          opt.classList.add('selected');
        }
      });
      document.getElementById('modalOverlay').classList.add('active');
    });

    document.getElementById('deleteButton').addEventListener('click', () => {
      if (selectedSection === null) return;
      if (confirm('Delete this section?')) {
        sections.splice(selectedSection, 1);
        selectedSection = null;
        document.getElementById('updateButton').disabled = true;
        renderSections();
      }
    });

    document.getElementById('cancelButton').addEventListener('click', () => {
      document.getElementById('modalOverlay').classList.remove('active');
    });

    document.getElementById('modalOverlay').addEventListener('click', (e) => {
      if (e.target.id === 'modalOverlay') {
        document.getElementById('modalOverlay').classList.remove('active');
      }
    });

    document.getElementById('saveButton').addEventListener('click', () => {
      const sectionName = document.getElementById('sectionInput').value.trim();
      const selectedColor = document.querySelector('.color-option.selected')?.dataset.color;

      if (!sectionName || !selectedColor) {
        alert('Please fill all fields');
        return;
      }

      if (editMode && selectedSection !== null) {
        sections[selectedSection] = { name: sectionName, color: selectedColor };
        selectedSection = null;
        document.getElementById('updateButton').disabled = true;
      } else {
        sections.push({ name: sectionName, color: selectedColor });
      }

      renderSections();
      document.getElementById('modalOverlay').classList.remove('active');
    });

    document.getElementById('addColorBtn').addEventListener('click', () => {
      const color = prompt('Enter hex color (e.g., #ff0000):');
      if (color && /^#[0-9A-F]{6}$/i.test(color)) {
        availableColors.push(color);
        renderColorOptions();
      }
    });

    renderSections();
    renderColorOptions();

    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });
  </script>
</body>

</html>