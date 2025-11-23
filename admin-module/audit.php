<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Audit Log</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <link rel="stylesheet" href="client/styles/audit.css">
</head>

<body>

  <main class="main">
    <div class="main-header">
      <h1>Audit Log</h1>
    </div>

    <div class="content-wrapper">
      <div class="chart-card">
        <h2>Weekly Room Usage (Hours per Room)</h2>
        <div class="chart-container">
          <canvas id="usageChart"></canvas>
        </div>
        <div class="date-filter">
          <div class="date-group">
            <label for="fromDate">From</label>
            <input type="date" id="fromDate" class="date-input" value="2025-10-01">
          </div>
          <div class="date-group">
            <label for="toDate">To</label>
            <input type="date" id="toDate" class="date-input" value="2025-10-06">
          </div>
        </div>
      </div>

      <div class="flagged-card">
        <h2>Flagged Cases</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Faculty</th>
                <th>Description</th>
                <th>Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr class="highlight">
                <td class="faculty-name">Mr. Daniel Cruz</td>
                <td>Double-booked two classes in Room 204 at 10:00 AM.</td>
                <td>Oct 24, 2025</td>
                <td><span class="status processing">Processing</span></td>
              </tr>
              <tr>
                <td class="faculty-name">Ms. Carla Santos</td>
                <td>Reserved a lab room overlapping another class session.</td>
                <td>Oct 17, 2025</td>
                <td><span class="status resolved">Resolved</span></td>
              </tr>
              <tr>
                <td class="faculty-name">Mr. Anthony Javier</td>
                <td>Missing attendance submissions for 2 subjects.</td>
                <td>Oct 12, 2025</td>
                <td><span class="status resolved">Resolved</span></td>
              </tr>
              <tr class="highlight">
                <td class="faculty-name">Mr. John Macaraig</td>
                <td>Schedule Conflict</td>
                <td>Oct 12, 2025</td>
                <td><span class="status processing">Processing</span></td>
              </tr>
              <tr class="highlight">
                <td class="faculty-name">Ms. Jessica Garcia</td>
                <td>Schedule Conflict</td>
                <td>Oct 12, 2025</td>
                <td><span class="status processing">Processing</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <script>
    const ctx = document.getElementById('usageChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Rm 101', 'Rm 102', 'Rm 103', 'Rm 104', 'Rm 105', 'Rm 106'],
        datasets: [{
          label: 'Hours',
          data: [18, 35, 28, 22, 38, 25],
          backgroundColor: [
            '#93c5fd',
            '#fde047',
            '#93c5fd',
            '#fde047',
            '#93c5fd',
            '#93c5fd'
          ],
          borderRadius: 6,
          barPercentage: 0.7,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            max: 40,
            ticks: {
              stepSize: 10,
              callback: function (value) {
                return value;
              }
            },
            grid: { color: '#f3f4f6' }
          }
        }
      }
    });

    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });
  </script>
</body>

</html>