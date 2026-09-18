<?php
require_once "includes/session.php";
require_once "includes/csrf.php";
require_once "db.php";

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {

        $error = "Session expired or invalid token. Please try again.";

    } else {

        // Get form values
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';

        // ---------------------------------
        // Validate form fields
        // ---------------------------------

        if (empty($name) || empty($email) || empty($mobile) || empty($password)) {

            $error = "All fields are required.";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $error = "Please provide a valid email address.";

        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {

            $error = "Please enter a valid 10-digit mobile number.";

        } elseif (strlen($password) < 6) {

            $error = "Password must be at least 6 characters long.";

        } else {

            // ---------------------------------
            // Check if email already exists
            // ---------------------------------

            $checkEmailStmt = $conn->prepare(
                "SELECT id FROM users WHERE email = ? LIMIT 1"
            );

            $checkEmailStmt->bind_param("s", $email);
            $checkEmailStmt->execute();

            $checkEmailRes = $checkEmailStmt->get_result();

            if ($checkEmailRes->num_rows > 0) {

                $error = "This email is already registered. Please login.";

            } else {

                // ---------------------------------
                // Check if mobile already exists
                // ---------------------------------

                $checkMobileStmt = $conn->prepare(
                    "SELECT id FROM users WHERE mobile = ? LIMIT 1"
                );

                $checkMobileStmt->bind_param("s", $mobile);
                $checkMobileStmt->execute();

                $checkMobileRes = $checkMobileStmt->get_result();

                if ($checkMobileRes->num_rows > 0) {

                    $error = "This mobile number is already registered. Please login.";

                } else {

                    // ---------------------------------
                    // Hash password
                    // ---------------------------------

                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // ---------------------------------
                    // Insert new user
                    // ---------------------------------

                    $insStmt = $conn->prepare(
                        "INSERT INTO users
                        (name, email, mobile, password)
                        VALUES (?, ?, ?, ?)"
                    );

                    $insStmt->bind_param(
                        "ssss",
                        $name,
                        $email,
                        $mobile,
                        $hashedPassword
                    );

                    if ($insStmt->execute()) {

                        // Registration successful
                        header("Location: login.php?registered=1");
                        exit();

                    } else {

                        // Handle duplicate entry from database
                        if ($conn->errno == 1062) {

                            $error = "Email or mobile number is already registered.";

                        } else {

                            $error = "Registration failed. Please try again.";
                        }
                    }

                    $insStmt->close();
                }

                $checkMobileStmt->close();
            }

            $checkEmailStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Register - Guru Woodworks</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        /* ---------------------------------
           Existing page background
           --------------------------------- */

        body {
            margin: 0;

            font-family: 'Poppins', sans-serif;

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #ff758c,
                    #ff7eb3,
                    #667eea
                );

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }


        /* ---------------------------------
           Registration Card
           --------------------------------- */

        .register-box {

            background: #ffffff;

            padding: 40px;

            border-radius: 20px;

            width: 100%;

            max-width: 360px;

            text-align: center;

            color: #3b2a1d;

            border:
                1px solid #eadcc9;

            box-shadow:
                0 10px 35px
                rgba(70, 45, 25, 0.18);

            position: relative;

            overflow: hidden;
        }


        /* ---------------------------------
           Premium wood top line
           --------------------------------- */

        .register-box::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 4px;

            background:
                linear-gradient(
                    90deg,
                    #6f4e37,
                    #c49a6c,
                    #6f4e37
                );
        }


        /* ---------------------------------
           Heading
           --------------------------------- */

        h2 {

            margin-top: 0;

            margin-bottom: 20px;

            color: #4b321f;

            font-weight: 600;
        }


        /* ---------------------------------
           Input fields
           --------------------------------- */

        input {

            width: 100%;

            padding: 13px;

            margin: 10px 0;

            border:
                1px solid #dfd0bd;

            border-radius: 10px;

            outline: none;

            background: #fffaf4;

            color: #3b2a1d;

            font-family: inherit;

            font-size: 14px;

            transition: 0.25s;
        }


        input::placeholder {

            color: #9b8975;
        }


        input:focus {

            border-color: #9a6b3f;

            background: #ffffff;

            box-shadow:
                0 0 0 3px
                rgba(154, 107, 63, 0.12);
        }


        /* ---------------------------------
           Register Button
           --------------------------------- */

        button {

            width: 100%;

            padding: 13px;

            margin-top: 10px;

            border: none;

            border-radius: 30px;

            background:
                linear-gradient(
                    45deg,
                    #6f4e37,
                    #a87545
                );

            color: #ffffff;

            font-weight: 600;

            font-size: 16px;

            cursor: pointer;

            transition: 0.3s;
        }


        button:hover {

            transform: scale(1.03);

            background:
                linear-gradient(
                    45deg,
                    #543a29,
                    #8b5e34
                );
        }


        /* ---------------------------------
           Error message
           --------------------------------- */

        .error {

            background: #fff4f2;

            border:
                1px solid #e7b9b1;

            padding: 10px;

            border-radius: 8px;

            color: #a33a32;

            margin-bottom: 15px;

            font-size: 13px;

            text-align: left;
        }


        /* ---------------------------------
           Links
           --------------------------------- */

        .links {

            margin-top: 20px;

            font-size: 13px;
        }


        .links a {

            color: #6f4e37;

            font-size: 14px;

            text-decoration: underline;

            transition: 0.2s;
        }


        .links a:hover {

            color: #b07a45;
        }


        /* ---------------------------------
           Back link
           --------------------------------- */

        .back-link {

            color: #8c7a68 !important;

            font-size: 13px !important;

            text-decoration: none !important;
        }


        .back-link:hover {

            color: #6f4e37 !important;
        }


        /* ---------------------------------
           Mobile responsive
           --------------------------------- */

        @media (max-width: 480px) {

            body {

                padding: 15px;
            }

            .register-box {

                padding: 32px 24px;

                border-radius: 20px;
            }

            h2 {

                font-size: 23px;
            }
        }

    </style>

</head>


<body>

    <div class="register-box">

        <h2>🪵 Create Account</h2>


        <?php if (!empty($error)): ?>

            <div class="error">

                <?php
                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="register.php"
        >

            <?php echo csrf_field(); ?>


            <!-- Full Name -->

            <input
                type="text"
                name="name"
                placeholder="Full Name"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
                required
                autofocus
            >


            <!-- Email -->

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
                required
            >


            <!-- Mobile Number -->

            <input
                type="tel"
                name="mobile"
                placeholder="Mobile Number"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['mobile'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
                maxlength="10"
                pattern="[6-9][0-9]{9}"
                inputmode="numeric"
                required
            >


            <!-- Password -->

            <input
                type="password"
                name="password"
                placeholder="Password (min 6 chars)"
                minlength="6"
                required
            >


            <!-- Register -->

            <button
                type="submit"
                name="register"
            >
                Register
            </button>

        </form>


        <div class="links">

            <a href="login.php">
                Already have an account? Login
            </a>

            <br>
            <br>

            <a
                href="index.php"
                class="back-link"
            >
                ← Back to Furniture Collection
            </a>

        </div>

    </div>

</body>

</html>
