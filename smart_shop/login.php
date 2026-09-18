<?php
require_once "includes/session.php";
require_once "includes/csrf.php";
require_once "db.php";

// If already logged in, redirect to home
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {

        $error = "Session expired or invalid token. Please try again.";

    } else {

        // Get login details
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate fields
        if (empty($login) || empty($password)) {

            $error = "Please provide your email/mobile number and password.";

        } else {

            // Find user using email OR mobile number
            $stmt = $conn->prepare(
                "SELECT id, name, email, mobile, password
                 FROM users
                 WHERE email = ? OR mobile = ?
                 LIMIT 1"
            );

            if (!$stmt) {

                $error = "Something went wrong. Please try again.";

            } else {

                $stmt->bind_param("ss", $login, $login);
                $stmt->execute();

                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {

                    // Verify password
                    if (password_verify($password, $row['password'])) {

                        // Regenerate session ID
                        session_regenerate_id(true);

                        // Store user information
                        $_SESSION['user_id'] = (int) $row['id'];
                        $_SESSION['user_name'] = $row['name'];
                        $_SESSION['user_email'] = $row['email'];

                        if (!empty($row['mobile'])) {
                            $_SESSION['user_mobile'] = $row['mobile'];
                        }

                        $stmt->close();

                        // Redirect to home
                        header("Location: index.php");
                        exit();

                    } else {

                        $error = "Invalid email/mobile number or password.";
                    }

                } else {

                    $error = "Invalid email/mobile number or password.";
                }

                $stmt->close();
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

    <title>Login - Guru Woodworks</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        /* =========================================
           PAGE BACKGROUND
           ========================================= */

        body {
            margin: 0;

            font-family: 'Poppins', sans-serif;

            min-height: 100vh;

            /* Furniture background image */
            background-image:
                linear-gradient(
                    rgba(55, 38, 25, 0.15),
                    rgba(55, 38, 25, 0.15)
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
           LOGIN CARD
           ========================================= */

        .login-box {

            background: rgba(255, 255, 255, 0.96);

            padding: 40px;

            border-radius: 20px;

            width: 100%;

            max-width: 360px;

            text-align: center;

            color: #3b2a1d;

            border: 1px solid #eadcc9;

            box-shadow:
                0 15px 40px
                rgba(70, 45, 25, 0.25);

            position: relative;

            overflow: hidden;
        }


        /* =========================================
           TOP WOOD COLOR LINE
           ========================================= */

        .login-box::before {

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


        /* =========================================
           HEADING
           ========================================= */

        h2 {

            margin-top: 0;

            margin-bottom: 20px;

            color: #4b321f;

            font-weight: 600;

            font-size: 25px;
        }


        /* =========================================
           INPUT FIELDS
           ========================================= */

        input {

            width: 100%;

            padding: 13px;

            margin: 10px 0;

            border: 1px solid #dfd0bd;

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


        /* =========================================
           LOGIN BUTTON
           ========================================= */

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

            font-family: inherit;

            cursor: pointer;

            transition: 0.3s;

            box-shadow:
                0 7px 18px
                rgba(92, 61, 38, 0.20);
        }


        button:hover {

            transform: translateY(-2px);

            background:
                linear-gradient(
                    45deg,
                    #543a29,
                    #8b5e34
                );

            box-shadow:
                0 10px 22px
                rgba(92, 61, 38, 0.28);
        }


        button:active {

            transform: translateY(0);
        }


        /* =========================================
           ERROR MESSAGE
           ========================================= */

        .error {

            background: #fff4f2;

            border: 1px solid #e7b9b1;

            padding: 10px;

            border-radius: 8px;

            color: #a33a32;

            margin-bottom: 15px;

            font-size: 13px;

            text-align: left;
        }


        /* =========================================
           LINKS
           ========================================= */

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

                background-attachment: scroll;

                background-position: center center;

                padding: 15px;
            }


            .login-box {

                width: 100%;

                max-width: 380px;

                padding: 32px 24px;

                border-radius: 20px;
            }


            h2 {

                font-size: 23px;
            }


            input {

                padding: 13px;
            }


            button {

                padding: 13px;
            }
        }


        /* =========================================
           SMALL MOBILE
           ========================================= */

        @media (max-width: 380px) {

            .login-box {

                padding: 28px 20px;
            }


            h2 {

                font-size: 21px;
            }
        }

    </style>

</head>


<body>

    <div class="login-box">

        <h2>🔐 Customer Login</h2>


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
             LOGIN FORM
             ===================================== -->

        <form
            method="POST"
            action="login.php"
        >

            <?php echo csrf_field(); ?>


            <!-- Email OR Mobile -->

            <input
                type="text"
                name="login"
                placeholder="Email or Mobile Number"
                value="<?php
                    echo htmlspecialchars(
                        $_POST['login'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>"
                required
                autofocus
            >


            <!-- Password -->

            <input
                type="password"
                name="password"
                placeholder="Password"
                required
            >


            <!-- Login Button -->

            <button
                type="submit"
                name="login"
            >
                Login
            </button>

        </form>


        <!-- =====================================
             LINKS
             ===================================== -->

        <div class="links">

            <a href="register.php">
                New customer? Create an Account
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
