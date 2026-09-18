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

        if (
            empty($name) ||
            empty($email) ||
            empty($mobile) ||
            empty($password)
        ) {

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

            if (!$checkEmailStmt) {

                $error = "Something went wrong. Please try again.";

            } else {

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

                    if (!$checkMobileStmt) {

                        $error = "Something went wrong. Please try again.";

                    } else {

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

                            if (!$insStmt) {

                                $error = "Registration failed. Please try again.";

                            } else {

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

                                    // Handle duplicate entry
                                    if ($conn->errno == 1062) {

                                        $error =
                                            "Email or mobile number is already registered.";

                                    } else {

                                        $error =
                                            "Registration failed. Please try again.";
                                    }
                                }

                                $insStmt->close();
                            }
                        }

                        $checkMobileStmt->close();
                    }
                }

                $checkEmailStmt->close();
            }
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        /* =========================================
           RESET
           ========================================= */

        * {
            box-sizing: border-box;
        }


        /* =========================================
           BODY
           ========================================= */

        body {

            margin: 0;

            font-family: 'Poppins', sans-serif;

            min-height: 100vh;

            background-image:

                linear-gradient(
                    rgba(55, 38, 25, 0.18),
                    rgba(55, 38, 25, 0.18)
                ),

                url('guru-woodworks-login-bg.png');

            background-size: cover;

            background-position: center;

            background-repeat: no-repeat;

            background-attachment: fixed;

            display: flex;

            justify-content: center;

            align-items: center;

            padding: 20px;
        }


        /* =========================================
           REGISTRATION CARD
           ========================================= */

        .register-box {

            width: 100%;

            max-width: 380px;

            padding: 38px 34px;

            background: rgba(255, 255, 255, 0.96);

            border-radius: 22px;

            border: 1px solid rgba(255, 255, 255, 0.8);

            box-shadow:
                0 20px 50px
                rgba(45, 30, 20, 0.30);

            text-align: center;

            color: #3b2a1d;

            position: relative;

            overflow: hidden;
        }


        /* =========================================
           WOOD TOP BORDER
           ========================================= */

        .register-box::before {

            content: "";

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 5px;

            background:
                linear-gradient(
                    90deg,
                    #654321,
                    #b47b45,
                    #d2a06b,
                    #b47b45,
                    #654321
                );
        }


        /* =========================================
           HEADING
           ========================================= */

        h2 {

            margin: 0 0 24px 0;

            color: #4b321f;

            font-size: 26px;

            font-weight: 600;
        }


        /* =========================================
           INPUT FIELDS
           ========================================= */

        input {

            display: block;

            width: 100%;

            height: 50px;

            padding: 0 15px;

            margin: 12px 0;

            border: 1px solid #dfcdb8;

            border-radius: 11px;

            outline: none;

            background: #fffaf5;

            color: #3b2a1d;

            font-family: 'Poppins', sans-serif;

            font-size: 14px;

            transition:
                border-color 0.25s,
                box-shadow 0.25s,
                background 0.25s;
        }


        input::placeholder {

            color: #9b8975;
        }


        input:focus {

            border-color: #a87545;

            background: #ffffff;

            box-shadow:
                0 0 0 3px
                rgba(168, 117, 69, 0.13);
        }


        /* =========================================
           MOBILE INPUT
           ========================================= */

        input[name="mobile"] {

            letter-spacing: 0.5px;
        }


        /* =========================================
           REGISTER BUTTON
           ========================================= */

        button {

            width: 100%;

            height: 52px;

            margin-top: 12px;

            border: none;

            border-radius: 30px;

            background:
                linear-gradient(
                    135deg,
                    #6f4e37,
                    #a87545
                );

            color: #ffffff;

            font-family: 'Poppins', sans-serif;

            font-size: 16px;

            font-weight: 600;

            cursor: pointer;

            transition: all 0.3s ease;

            box-shadow:
                0 8px 20px
                rgba(91, 61, 38, 0.25);
        }


        button:hover {

            transform: translateY(-2px);

            background:
                linear-gradient(
                    135deg,
                    #543a29,
                    #8b5e34
                );

            box-shadow:
                0 12px 25px
                rgba(91, 61, 38, 0.32);
        }


        button:active {

            transform: translateY(0);
        }


        /* =========================================
           ERROR MESSAGE
           ========================================= */

        .error {

            background: #fff3f1;

            border: 1px solid #e7b9b1;

            padding: 11px 12px;

            border-radius: 9px;

            color: #a33a32;

            margin-bottom: 15px;

            font-size: 13px;

            text-align: left;

            line-height: 1.5;
        }


        /* =========================================
           LINKS
           ========================================= */

        .links {

            margin-top: 22px;

            font-size: 13px;
        }


        .links a {

            color: #6f4e37;

            font-size: 14px;

            text-decoration: underline;

            transition: color 0.2s ease;
        }


        .links a:hover {

            color: #b47b45;
        }


        /* =========================================
           BACK LINK
           ========================================= */

        .back-link {

            color: #8c7a68 !important;

            font-size: 13px !important;

            text-decoration: none !important;
        }


        .back-link:hover {

            color: #6f4e37 !important;
        }


        /* =========================================
           MOBILE RESPONSIVE
           ========================================= */

        @media (max-width: 600px) {

            body {

                padding: 15px;

                background-attachment: scroll;

                background-position: center center;
            }


            .register-box {

                max-width: 390px;

                padding: 32px 24px;

                border-radius: 20px;
            }


            h2 {

                font-size: 23px;

                margin-bottom: 20px;
            }


            input {

                height: 49px;

                font-size: 14px;
            }


            button {

                height: 50px;

                font-size: 15px;
            }
        }


        /* =========================================
           SMALL MOBILE
           ========================================= */

        @media (max-width: 380px) {

            .register-box {

                padding: 28px 20px;
            }


            h2 {

                font-size: 21px;
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


        <!-- =====================================
             REGISTRATION FORM
             ===================================== -->

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


            <!-- Register Button -->

            <button
                type="submit"
                name="register"
            >
                Register
            </button>

        </form>


        <!-- =====================================
             LINKS
             ===================================== -->

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
