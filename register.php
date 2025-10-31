<?php
require __DIR__ . '/config_mysqli.php';
require __DIR__ . '/csrf.php';
?>

<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Custom dark background for a professional dashboard look */
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      background-color: #1f2937 !important; /* A deep dark gray/blue */
    }
    .register-card {
      max-width: 450px;
      width: 100%;
      /* Make the card slightly lighter than the background for contrast */
      background-color: #374151 !important; /* Darker card background */
      border-color: rgba(255, 255, 255, 0.15) !important;
    }
    /* Use a consistent accent color (Indigo) for buttons and links to match a common dashboard theme */
    :root {
      --bs-success: #6366f1; /* Indigo-500 for success color (Create Account button) */
      --bs-success-rgb: 99, 102, 241;
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
  <div class="card shadow-lg register-card p-3 p-md-4">
    <div class="card-body">
      <h1 class="h4 mb-3 text-center text-white">Create Account</h1>

      <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-danger py-2">
          <?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="register_process.php" novalidate>
        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token()); ?>">

        <div class="mb-3">
          <label class="form-label" for="fullname">Full Name</label>
          <input class="form-control" type="text" id="fullname" name="fullname" placeholder="Your name" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" placeholder="you@example.com" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" placeholder="********" required>
        </div>

        <div class="mb-3">
          <label class="form-label" for="confirm_password">Confirm Password</label>
          <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="********" required>
        </div>

        <div class="d-grid mt-3">
          <button class="btn btn-success" type="submit">Create Account</button>
        </div>
      </form>

      <p class="text-center text-muted mt-3 mb-0 small">
        Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a>
      </p>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>