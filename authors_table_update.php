<?php
// This script creates an author record for each user
// Run this once to set up the author records for existing users

// Koneksi ke database
require_once 'db_config.php';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Cek koneksi
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get users without author records
$sql = "SELECT u.user_id, u.username, u.email 
        FROM users u 
        LEFT JOIN authors a ON u.email = a.email 
        WHERE a.author_id IS NULL";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Creating author records for users:<br>";
    
    while($row = $result->fetch_assoc()) {
        $username = $row['username'];
        $email = $row['email'];
        $user_id = $row['user_id'];
        
        // Create author record
        $insert_sql = "INSERT INTO authors (name, email, bio) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $bio = "User " . $username;
        $stmt->bind_param("sss", $username, $email, $bio);
        
        if ($stmt->execute()) {
            echo "Created author record for user: " . $username . "<br>";
        } else {
            echo "Error creating author record for " . $username . ": " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    }
} else {
    echo "All users already have author records.";
}

$conn->close();
?>