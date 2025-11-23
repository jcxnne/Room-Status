<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <link rel="stylesheet" href="client/styles/report.css">
</head>

<body>
  <main class="main">
    <div class="main-header">
      <h1>Reports</h1>
    </div>

    <div class="content-wrapper">
      <div class="chart-section">
        <div class="chart-card">
          <h2>Weekly Room Usage (Hours per Room)</h2>
          <div class="chart-container">
            <canvas id="usageChart"></canvas>
          </div>
        </div>
        <button class="generate-button" id="generateBtn">
          <span class="button-text">Generate Report</span>
          <div class="spinner"></div>
        </button>
      </div>

      <div class="stats-column">
        <div class="stat-card">
          <h3>Room Usage Summary</h3>
          <div class="stat-content">
            <div class="stat-icon blue">
              <svg viewBox="0 0 24 24">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
              </svg>
            </div>
            <svg class="usage-circle" viewBox="0 0 100 100">
              <circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="8" />
              <circle cx="50" cy="50" r="40" fill="none" stroke="#93c5fd" stroke-width="8" stroke-dasharray="251.2"
                stroke-dashoffset="17.584" transform="rotate(-90 50 50)" />
              <text x="50" y="58" text-anchor="middle" font-size="24" font-weight="700" fill="#1a1a1a">93%</text>
            </svg>
          </div>
        </div>

        <div class="stat-card">
          <h3>Schedule Conflict Detected</h3>
          <div class="stat-content">
            <div class="stat-icon calendar">
              <svg viewBox="0 0 24 24">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <span class="stat-value">5</span>
          </div>
        </div>

        <div class="stat-card">
          <h3>Flagged Teachers</h3>
          <div class="stat-content">
            <div class="stat-icon teacher">
              <svg viewBox="0 0 24 24">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
            </div>
            <span class="stat-value">2</span>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    const ctx = document.getElementById('usageChart').getContext('2d');
    const chart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Rm 101', 'Rm 102', 'Rm 103', 'Rm 104', 'Rm 105', 'Rm 106'],
        datasets: [{
          label: 'Usage (hours)',
          data: [18, 38, 30, 16, 38, 18],
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
            grid: { display: false },
            ticks: {
              font: {
                size: 12
              }
            }
          },
          y: {
            beginAtZero: true,
            max: 40,
            ticks: {
              stepSize: 10,
              font: {
                size: 12
              }
            },
            grid: {
              color: '#f3f4f6'
            },
            title: {
              display: true,
              text: 'Usage (hours)',
              font: {
                size: 12,
                weight: '500'
              },
              padding: {
                bottom: 10
              }
            }
          }
        }
      }
    });

    async function generatePDF() {
      const { jsPDF } = window.jspdf;
      const btn = document.getElementById('generateBtn');

      btn.disabled = true;
      btn.classList.add('loading');

      try {
        await new Promise(resolve => setTimeout(resolve, 500));

        const pdf = new jsPDF('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const margin = 20;
        let yPos = 20;

        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(24);
        pdf.text('Room Usage Report', margin, yPos);

        yPos += 10;
        pdf.setFontSize(10);
        pdf.setFont('helvetica', 'normal');
        pdf.setTextColor(100);
        const date = new Date().toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        pdf.text(`Generated on ${date}`, margin, yPos);

        pdf.setDrawColor(200);
        pdf.line(margin, yPos + 3, pageWidth - margin, yPos + 3);
        yPos += 15;

        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(0);
        pdf.text('Summary Statistics', margin, yPos);
        yPos += 10;

        const stats = [
          { label: 'Room Usage Summary', value: '93%' },
          { label: 'Schedule Conflicts Detected', value: '5' },
          { label: 'Flagged Teachers', value: '2' }
        ];

        pdf.setFontSize(11);
        pdf.setFont('helvetica', 'normal');

        stats.forEach(stat => {
          pdf.setFont('helvetica', 'bold');
          pdf.text(stat.label + ':', margin, yPos);
          pdf.setFont('helvetica', 'normal');
          pdf.text(stat.value, margin + 70, yPos);
          yPos += 8;
        });

        yPos += 10;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Weekly Room Usage Details', margin, yPos);
        yPos += 10;

        const roomData = [
          ['Room', 'Usage (hours)'],
          ['Room 101', '18'],
          ['Room 102', '38'],
          ['Room 103', '30'],
          ['Room 104', '16'],
          ['Room 105', '38'],
          ['Room 106', '18']
        ];

        const cellWidth = (pageWidth - 2 * margin) / 2;
        const cellHeight = 10;

        pdf.setFillColor(249, 250, 251);
        pdf.rect(margin, yPos - 7, cellWidth * 2, cellHeight, 'F');

        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(11);
        pdf.text(roomData[0][0], margin + 2, yPos);
        pdf.text(roomData[0][1], margin + cellWidth + 2, yPos);
        yPos += cellHeight;

        pdf.setFont('helvetica', 'normal');
        for (let i = 1; i < roomData.length; i++) {
          if (i % 2 === 0) {
            pdf.setFillColor(249, 250, 251);
            pdf.rect(margin, yPos - 7, cellWidth * 2, cellHeight, 'F');
          }
          pdf.text(roomData[i][0], margin + 2, yPos);
          pdf.text(roomData[i][1], margin + cellWidth + 2, yPos);
          yPos += cellHeight;
        }

        yPos += 10;
        pdf.setFontSize(16);
        pdf.setFont('helvetica', 'bold');
        pdf.text('Room Usage Chart', margin, yPos);
        yPos += 10;

        const canvas = document.getElementById('usageChart');
        const chartImage = canvas.toDataURL('image/png', 1.0);
        const imgWidth = pageWidth - 2 * margin;
        const imgHeight = (imgWidth * canvas.height) / canvas.width;

        pdf.addImage(chartImage, 'PNG', margin, yPos, imgWidth, imgHeight);
        yPos += imgHeight + 15;

        pdf.setFontSize(8);
        pdf.setTextColor(150);
        const footerY = pdf.internal.pageSize.getHeight() - 10;
        pdf.text('University Room Reservation System', margin, footerY);
        pdf.text(`Page 1 of 1`, pageWidth - margin - 20, footerY);

        pdf.save(`Room_Usage_Report_${new Date().toISOString().split('T')[0]}.pdf`);

      } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating report. Please try again.');
      } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
      }
    }

    document.getElementById('generateBtn').addEventListener('click', generatePDF);

    window.addEventListener('message', (e) => {
      if (e.data.darkMode !== undefined) {
        document.body.classList.toggle('dark', e.data.darkMode);
      }
    });
  </script>
</body>

</html>