<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Situs Sedang Dalam Pemeliharaan</title>
  <style>
    body {
      background-color: #f3f4f6;
      color: #1f2937;
      font-family: Arial, sans-serif;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      margin: 0;
      text-align: center;
    }

    h1 {
      font-size: 2.5rem;
      margin-bottom: 0.5rem;
    }

    p {
      font-size: 1.2rem;
      margin-bottom: 2rem;
    }

    .spinner {
      border: 8px solid #e5e7eb;
      border-top: 8px solid #3b82f6;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      animation: spin 1s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <h1>Sedang Dalam Pemeliharaan Oleh Divisi Kramad</h1>
  <p>Mohon maaf atas ketidaknyamanannya. Kami sedang melakukan pemeliharaan sistem.<br />Silakan kembali lagi nanti.</p>
  <div class="spinner"></div>
</body>
</html>
