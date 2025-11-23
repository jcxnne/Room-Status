<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservation</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="client/styles/reservation.css">
</head>

<body>
  <main class="main">
    <div class="main-header">
      <h1>Reservation</h1>
    </div>

    <div class="reservation-container">
      <div class="reservation-card">
        <h2>Reservation</h2>

        <form id="reservationForm">
          <div class="form-grid">
            <div class="form-group">
              <label for="room">Room</label>
              <input type="text" id="room" placeholder="105" required>
            </div>

            <div class="form-group">
              <label for="date">Date</label>
              <div class="input-wrapper">
                <input type="text" id="date" placeholder="10/24/2025" required readonly>
                <span class="input-icon">
                  <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </span>
                <div class="calendar-popup" id="calendarPopup">
                  <div class="calendar-header">
                    <h3 id="calendarMonth">October 2025</h3>
                    <div class="calendar-nav">
                      <button type="button" id="prevMonth">
                        <svg viewBox="0 0 24 24">
                          <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                      </button>
                      <button type="button" id="nextMonth">
                        <svg viewBox="0 0 24 24">
                          <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="calendar-weekdays">
                    <div class="calendar-weekday">Sun</div>
                    <div class="calendar-weekday">Mon</div>
                    <div class="calendar-weekday">Tue</div>
                    <div class="calendar-weekday">Wed</div>
                    <div class="calendar-weekday">Thu</div>
                    <div class="calendar-weekday">Fri</div>
                    <div class="calendar-weekday">Sat</div>
                  </div>
                  <div class="calendar-days" id="calendarDays"></div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="subjectCode">Subject Code</label>
              <input type="text" id="subjectCode" placeholder="COSC75" required>
            </div>

            <div class="form-group">
              <label for="building">Building</label>
              <select id="building" required>
                <option value="">Select Building</option>
                <option value="old">Old Building</option>
                <option value="new" selected>New Building</option>
              </select>
            </div>

            <div class="form-group">
              <label for="startTime">Start Time</label>
              <div class="input-wrapper">
                <input type="text" id="startTime" placeholder="10:00 AM" required readonly>
                <span class="input-icon">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                </span>
                <div class="time-dropdown" id="startTimeDropdown"></div>
              </div>
            </div>

            <div class="form-group">
              <label for="endTime">End Time</label>
              <div class="input-wrapper">
                <input type="text" id="endTime" placeholder="10:00 AM" required readonly>
                <span class="input-icon">
                  <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                </span>
                <div class="time-dropdown" id="endTimeDropdown"></div>
              </div>
            </div>

            <div class="form-group full-width">
              <label for="purpose">Purpose/Reason</label>
              <textarea id="purpose" placeholder="Enter purpose or reason for reservation" required></textarea>
            </div>
          </div>

          <button type="submit" class="reserve-button">Reserve</button>
        </form>
      </div>
    </div>
  </main>

  <script>
    let currentDate = new Date();
    let selectedDate = null;

    function generateTimeOptions() {
      const times = [];
      for (let h = 7; h <= 19; h++) {
        for (let m = 0; m < 60; m += 30) {
          const hour = h > 12 ? h - 12 : h;
          const period = h >= 12 ? 'PM' : 'AM';
          const minute = m.toString().padStart(2, '0');
          times.push(`${hour}:${minute} ${period}`);
        }
      }
      return times;
    }

    function populateTimeDropdown(dropdownId) {
      const dropdown = document.getElementById(dropdownId);
      const times = generateTimeOptions();
      dropdown.innerHTML = times.map(time =>
        `<div class="time-option" data-time="${time}">${time}</div>`
      ).join('');

      dropdown.querySelectorAll('.time-option').forEach(option => {
        option.addEventListener('click', (e) => {
          const inputId = dropdownId.replace('Dropdown', '');
          document.getElementById(inputId).value = e.target.dataset.time;
          dropdown.classList.remove('active');
        });
      });
    }

    function renderCalendar() {
      const year = currentDate.getFullYear();
      const month = currentDate.getMonth();
      const firstDay = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      const daysInPrevMonth = new Date(year, month, 0).getDate();

      document.getElementById('calendarMonth').textContent =
        `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;

      const calendarDays = document.getElementById('calendarDays');
      calendarDays.innerHTML = '';

      // Previous month days
      for (let i = firstDay - 1; i >= 0; i--) {
        const day = document.createElement('div');
        day.className = 'calendar-day other-month';
        day.textContent = daysInPrevMonth - i;
        calendarDays.appendChild(day);
      }

      // Current month days
      const today = new Date();
      for (let i = 1; i <= daysInMonth; i++) {
        const day = document.createElement('div');
        day.className = 'calendar-day';
        day.textContent = i;

        if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
          day.classList.add('today');
        }

        if (selectedDate && year === selectedDate.getFullYear() &&
          month === selectedDate.getMonth() && i === selectedDate.getDate()) {
          day.classList.add('selected');
        }

        day.addEventListener('click', () => {
          selectedDate = new Date(year, month, i);
          document.getElementById('date').value =
            `${(month + 1).toString().padStart(2, '0')}/${i.toString().padStart(2, '0')}/${year}`;
          document.getElementById('calendarPopup').classList.remove('active');
          renderCalendar();
        });

        calendarDays.appendChild(day);
      }

      // Next month days
      const totalCells = firstDay + daysInMonth;
      const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
      for (let i = 1; i <= remainingCells; i++) {
        const day = document.createElement('div');
        day.className = 'calendar-day other-month';
        day.textContent = i;
        calendarDays.appendChild(day);
      }
    }

    // Date input click handler
    document.getElementById('date').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('calendarPopup').classList.toggle('active');
      document.getElementById('startTimeDropdown').classList.remove('active');
      document.getElementById('endTimeDropdown').classList.remove('active');
    });

    // Start time click handler
    document.getElementById('startTime').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('startTimeDropdown').classList.toggle('active');
      document.getElementById('calendarPopup').classList.remove('active');
      document.getElementById('endTimeDropdown').classList.remove('active');
    });

    // End time click handler
    document.getElementById('endTime').addEventListener('click', (e) => {
      e.stopPropagation();
      document.getElementById('endTimeDropdown').classList.toggle('active');
      document.getElementById('calendarPopup').classList.remove('active');
      document.getElementById('startTimeDropdown').classList.remove('active');
    });

    // Close all dropdowns when clicking outside
    document.addEventListener('click', () => {
      document.getElementById('calendarPopup').classList.remove('active');
      document.getElementById('startTimeDropdown').classList.remove('active');
      document.getElementById('endTimeDropdown').classList.remove('active');
    });

    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      currentDate.setMonth(currentDate.getMonth() - 1);
      renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      currentDate.setMonth(currentDate.getMonth() + 1);
      renderCalendar();
    });

    // Prevent calendar popup from closing when clicking inside it
    document.getElementById('calendarPopup').addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Form submission
    document.getElementById('reservationForm').addEventListener('submit', (e) => {
      e.preventDefault();
      alert('Reservation submitted successfully!');
    });

    // Initialize
    populateTimeDropdown('startTimeDropdown');
    populateTimeDropdown('endTimeDropdown');
    renderCalendar();

    // Dark mode support
    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });
  </script>
</body>

</html>