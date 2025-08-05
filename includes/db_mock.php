<?php
// Mock database connection for testing purposes
class MockMysqli {
    public $connect_error = null;
    
    public function __construct($host, $user, $pass, $db) {
        // Simulate successful connection
    }
    
    public function prepare($query) {
        return new MockStatement();
    }
    
    public function query($query) {
        return new MockResult();
    }
}

class MockStatement {
    public function bind_param($types, ...$params) {
        return true;
    }
    
    public function execute() {
        return true;
    }
    
    public function get_result() {
        return new MockResult();
    }
}

class MockResult {
    public function fetch_assoc() {
        // Return mock user data for admin
        return [
            'id' => 1,
            'two_factor_secret' => 'mock_secret',
            'is_admin' => 1
        ];
    }
    
    public function num_rows() {
        return 1;
    }
}

// Start session and set mock admin user
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['is_admin'] = 1;

$conn = new MockMysqli('localhost', 'user', 'pass', 'db');
?>

