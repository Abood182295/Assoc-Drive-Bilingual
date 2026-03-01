<?php
// register.php
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if (isset($_POST['register'])) {
    $username  = $conn->real_escape_string($_POST['username']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email     = $conn->real_escape_string($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert into the updated table structure
    $sql = "INSERT INTO users (username, full_name, email, password) 
            VALUES ('$username', '$full_name', '$email', '$password')";
    
    if ($conn->query($sql)) {
        echo "User registered successfully!";
    }
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="register">Register New User</button>
</form>