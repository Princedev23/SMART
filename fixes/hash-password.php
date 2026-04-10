<?php
/**
 * Password Hash Generator
 *
 * This script generates password hashes for the attendance system.
 * Usage: Visit http://localhost/attendance-system/hash-password.php?password=yourpassword
 *
 * For security, only use this during setup, then delete or restrict access.
 */

if (!isset($_GET['password'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Hash Generator</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
            }
            .container {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 8px;
            }
            input {
                padding: 10px;
                width: 100%;
                margin: 10px 0;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            button {
                background: #2563eb;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
            }
            button:hover {
                background: #1e40af;
            }
            .result {
                background: white;
                padding: 15px;
                margin-top: 20px;
                border-radius: 4px;
                border: 1px solid #ddd;
            }
            .warning {
                background: #fff3cd;
                padding: 10px;
                border-radius: 4px;
                margin: 10px 0;
                color: #856404;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Password Hash Generator</h1>
            <p>Generate a password hash for the attendance system database.</p>

            <form method="GET">
                <label>Enter Password:</label>
                <input type="password" name="password" required placeholder="Enter password">
                <button type="submit">Generate Hash</button>
            </form>

            <div class="warning">
                <strong>Security Warning:</strong> Delete this file after generating hashes.
                Do not leave it on a production server.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$password = $_GET['password'];
$hash = password_hash($password, PASSWORD_BCRYPT);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Password Hash Generated</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        .container {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
        }
        .result {
            background: white;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            border-left: 4px solid #10b981;
        }
        .result-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .result-value {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
        }
        button {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #1e40af;
        }
        .warning {
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Password Hash Generated</h1>

        <div class="result">
            <div class="result-label">Password:</div>
            <div class="result-value"><?php echo htmlspecialchars($password); ?></div>
        </div>

        <div class="result">
            <div class="result-label">Hash (Use this in Database):</div>
            <div class="result-value"><?php echo $hash; ?></div>
        </div>

        <p>
            <strong>To use this hash:</strong>
        </p>
        <ol>
            <li>Go to phpMyAdmin</li>
            <li>Open "attendance_system" database</li>
            <li>Click on "users" table</li>
            <li>Insert a new row with:
                <ul>
                    <li>email: (the user's email)</li>
                    <li>password_hash: (paste the hash above)</li>
                    <li>role: (admin, lecturer, or student)</li>
                </ul>
            </li>
        </ol>

        <div class="warning">
            <strong>Security Warning:</strong> Delete this file (hash-password.php) after you're done.
            Do not leave it on your server as it's a security risk.
        </div>

        <form action="">
            <button type="submit">Generate Another Hash</button>
        </form>
    </div>
</body>
</html>