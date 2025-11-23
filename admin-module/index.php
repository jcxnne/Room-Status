<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>admin module</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
    }

    #sidebar-frame {
      border: none;
      width: 80px;
      height: 100vh;
      flex-shrink: 0;
      transition: width 0.3s ease;
    }

    #sidebar-frame:hover {
      width: 260px;
    }

    #content-frame {
      border: none;
      flex: 1;
      height: 100vh;
      background: #ededed;
    }

    body.dark #sidebar-frame {
      background: #121212;
    }

    body.dark #content-frame {
      background: #000000;
    }
  </style>
</head>

<body>
  <iframe id="sidebar-frame" src="sidebar.php"></iframe>
  <iframe id="content-frame" src="dashboard.php"></iframe>

  <script>
    const contentFrame = document.getElementById('content-frame');
    const sidebarFrame = document.getElementById('sidebar-frame');

    const isDarkMode = localStorage.getItem('darkMode') === 'true';

    if (isDarkMode) {
      document.body.classList.add('dark');
    }

    window.addEventListener('message', (e) => {
      if (e.data.navigate) {
        contentFrame.src = e.data.navigate;

        contentFrame.onload = () => {
          const isDark = localStorage.getItem('darkMode') === 'true';
          if (isDark) {
            contentFrame.contentWindow.postMessage({ darkMode: true }, '*');
          }
        };
      }

      if (e.data.darkMode !== undefined) {
        localStorage.setItem('darkMode', e.data.darkMode);
        document.body.classList.toggle('dark', e.data.darkMode);
        contentFrame.contentWindow.postMessage({ darkMode: e.data.darkMode }, '*');
      }
    });

    contentFrame.onload = () => {
      if (isDarkMode) {
        contentFrame.contentWindow.postMessage({ darkMode: true }, '*');
      }
    };
  </script>
</body>

</html>