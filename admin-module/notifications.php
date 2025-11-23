<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="client/styles/notifications.css">
</head>

<body>
  <main class="main">
    <div class="main-header">
      <h1>Notifications</h1>
    </div>

    <div class="notifications-container">
      <div class="tabs-header">
        <div class="tab active" data-tab="all">All</div>
        <div class="tab" data-tab="announcements">Announcements</div>
        <div class="tab" data-tab="updates">Updates</div>
        <div class="tab" data-tab="flagged">Flagged</div>
        <div class="more-options">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="1"></circle>
            <circle cx="12" cy="5" r="1"></circle>
            <circle cx="12" cy="19" r="1"></circle>
          </svg>
        </div>
      </div>

      <div class="notifications-list">
        <div class="notification-item unread" data-category="announcements">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">New semester schedule uploaded by Admin Santos.</div>
          </div>
          <div class="notification-date">Oct 24</div>
        </div>

        <div class="notification-item" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 210 reservation request denied due to overlapping schedule.</div>
          </div>
          <div class="notification-date">Oct 24</div>
        </div>

        <div class="notification-item" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">New schedule added for Room 105 – Web Development, 1:00–3:00 PM.</div>
          </div>
          <div class="notification-date">Oct 21</div>
        </div>

        <div class="notification-item unread" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 204 schedule updated by Prof. Cruz.</div>
          </div>
          <div class="notification-date">Oct 20</div>
        </div>

        <div class="notification-item unread" data-category="announcements">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 301 marked as Under Maintenance until further notice.</div>
          </div>
          <div class="notification-date">Oct 18</div>
        </div>

        <div class="notification-item" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Scheduling conflict detected between Room 302 and Room 303.</div>
          </div>
          <div class="notification-date">Oct 18</div>
        </div>

        <div class="notification-item unread" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 102 reservation approved for Networking class.</div>
          </div>
          <div class="notification-date">Oct 18</div>
        </div>

        <div class="notification-item" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 108: Class schedule extended by Prof. Santos until 3:30 PM.</div>
          </div>
          <div class="notification-date">Oct 17</div>
        </div>

        <div class="notification-item" data-category="updates">
          <div class="checkbox"></div>
          <div class="notification-content">
            <div class="notification-text">Room 404 left idle for over 3 hours — marked as Available.</div>
          </div>
          <div class="notification-date">Oct 16</div>
        </div>
      </div>
    </div>
  </main>

  <script>
    const tabs = document.querySelectorAll('.tab');
    const notifications = document.querySelectorAll('.notification-item');
    const checkboxes = document.querySelectorAll('.checkbox');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const category = tab.dataset.tab;

        notifications.forEach(notif => {
          if (category === 'all') {
            notif.style.display = 'flex';
          } else if (category === 'flagged') {
            notif.style.display = notif.querySelector('.checkbox').classList.contains('checked') ? 'flex' : 'none';
          } else {
            notif.style.display = notif.dataset.category === category ? 'flex' : 'none';
          }
        });
      });
    });

    checkboxes.forEach(checkbox => {
      checkbox.addEventListener('click', (e) => {
        e.stopPropagation();
        checkbox.classList.toggle('checked');
      });
    });

    notifications.forEach(notif => {
      notif.addEventListener('click', (e) => {
        if (!e.target.classList.contains('checkbox')) {
          notif.classList.remove('unread');
          notif.querySelector('.notification-text').style.fontWeight = '400';
        }
      });
    });

    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });
  </script>
</body>

</html>