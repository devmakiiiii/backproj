
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <h2>Sign Up</h2>
    <?php if (isset($error)) echo "<p>" . htmlspecialchars($error) . "</p>"; ?>
    <input type="text" name="firstname" placeholder="First Name" required><br>
    <input type="text" name="middlename" placeholder="Middle Name"><br>
    <input type="text" name="lastname" placeholder="Last Name" required><br>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="mobilenumber" placeholder="Mobile Number" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="password" name="confirmpassword" placeholder="Confirm Password" required><br>
    <input type="submit" value="Sign Up">
</form>