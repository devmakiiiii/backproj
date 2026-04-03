<?php
include "../includes/config.php";

if (isset($_POST['signup'])) {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $mobilenumber = $_POST['mobilenumber'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];

    if ($password !== $confirmpassword) {
        echo "Passwords do not match.";
        exit();
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    }

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "Username already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (firstname, middlename, lastname, username, email, mobilenumber, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $firstname, $middlename, $lastname, $username, $email, $mobilenumber, $hashed_password);

        if ($stmt->execute()) {
            echo "Registration successful. <a href='login.php'>Login here</a>.";
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>

<form method="POST">
    <h2>Sign Up</h2>
    <input type="text" name="firstname" placeholder="First Name" required><br>
    <input type="text" name="middlename" placeholder="Middle Name"><br>
    <input type="text" name="lastname" placeholder="Last Name" required><br>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="mobilenumber" placeholder="Mobile Number" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="password" name="confirmpassword" placeholder="Confirm Password" required><br>
    <input type="submit" name="signup" value="Sign Up">
</form> 