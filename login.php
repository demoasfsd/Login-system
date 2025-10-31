<?php
require __DIR__ . '/config_mysqli.php';
require __DIR__ . '/csrf.php';
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Custom dark background for a professional dashboard look */
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      background-color: #1f2937 !important; /* A deep dark gray/blue */
    }
    .login-card {
      max-width: 420px;
      width: 100%;
      /* Make the card slightly lighter than the background for contrast */
      background-color: #374151 !important; /* Darker card background */
      border-color: rgba(255, 255, 255, 0.15) !important;
    }
    /* Use a consistent accent color (Indigo) for buttons and links to match a common dashboard theme */
    :root {
      --bs-primary: #6366f1; /* Indigo-500 for primary color (Sign in button) */
      --bs-primary-rgb: 99, 102, 241;
      --bs-link-color: #818cf8; /* Indigo-400 for links */
      --bs-link-hover-color: #a5b4fc; /* Indigo-300 on hover */
    }
    .text-decoration-none {
      color: var(--bs-link-color) !important;
    }
    .text-decoration-none:hover {
      color: var(--bs-link-hover-color) !important;
    }
    /* Ensure shadows look good on dark theme */
    .shadow-lg {
      box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5) !important;
    }
  </style>
</head>
<body>
  <main class="container d-flex justify-content-center">
    <div class="card shadow-lg login-card p-3 p-md-4">
      <div class="card-body">
        <h1 class="h4 mb-3 text-center text-white">Welcome</h1>

        <?php if (!empty($_SESSION['flash'])): ?>
          <div class="alert alert-danger py-2">
            <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
          </div>
        <?php endif; ?>

        <form method="post" action="login_process.php" novalidate>
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">
          
          <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" placeholder="you@example.com" required>
          </div>

          <div class="mb-2">
            <label class="form-label d-flex justify-content-between" for="password">
              <span>Password</span>
              <a href="#" class="small text-decoration-none" onclick="alert('Please contact admin to reset your password');return false;">Forgot?</a>
            </label>
            <input class="form-control" type="password" id="password" name="password" placeholder="********" required>
          </div>

          <div class="d-grid mt-3">
            <button class="btn btn-primary" type="submit">Sign in</button>
          </div>
        </form>


        <p class="text-center text-muted mt-3 mb-0 small">
          Don’t have an account? 
          <a href="register.php" class="text-decoration-none">Create one</a>
        </p>

        <p class="text-center text-muted mt-3 mb-0 small">
          Demo only — do not use weak passwords.
        </p>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>