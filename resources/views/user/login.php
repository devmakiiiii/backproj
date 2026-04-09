
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<p>" . htmlspecialchars($error) . "</p>"; ?>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="submit" value="Login">
    <p>Don't have an account? <a href="/signup">Sign up here</a>.</p>
</form>