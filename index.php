<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | TEAU CourseFinder</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        /* Base Styles - for all screens */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #2E8B57, #3CB371);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: #fff;
            text-align: center;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Main content container should push footer down */
        .container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        h1 {
            font-weight: 800;
            font-size: 3rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
        }

        h1 span {
            color: #FFD700;
        }

        p {
            font-size: 1.25rem;
            margin-bottom: 40px;
            max-width: 600px;
            line-height: 1.4;
        }

        /* BUTTON STYLES: Small style retained */
        .btn-start {
            background-color: #FFD700;
            color: #2E8B57;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 20px;
            font-size: 1rem;
            text-decoration: none;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            display: inline-block;
        }

        .btn-start:hover {
            background-color: #fff;
            color: #2E8B57;
            transform: translateY(-2px);
        }

        /* REVISED FOOTER STYLES */
        footer {
            width: 100%;
            padding: 15px 0;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            background-color: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(5px);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: auto;
        }

        /* Use Bootstrap's `text-center` on the inner container for centering */

        footer p {
            margin: 0 0 5px 0;
            font-size: 0.85rem;
        }

        footer i {
            color: #FFD700;
        }

        .social-icons a {
            color: #fff;
            margin: 0 8px;
            font-size: 1.1rem;
            transition: color 0.3s;
            display: inline-block;
        }

        .social-icons a:hover {
            color: #FFD700;
        }


        /* =======================================================
        MEDIA QUERIES FOR RESPONSIVENESS
        ======================================================= */

        /* Mobile Devices (up to 576px) */
        @media (max-width: 576px) {
            h1 {
                font-size: 1.8rem;
                line-height: 1.3;
            }

            p {
                font-size: 1rem;
                margin-bottom: 30px;
            }

            .btn-start {
                padding: 8px 18px;
                font-size: 0.9rem;
            }

            footer {
                padding: 10px 0;
                font-size: 0.75rem;
            }

            footer p {
                font-size: 0.75rem;
            }

            .social-icons a {
                font-size: 1rem;
                margin: 0 5px;
            }
        }

        /* Tablets and larger phones (577px to 992px) */
        @media (min-width: 577px) and (max-width: 992px) {
            h1 {
                font-size: 2.5rem;
            }

            p {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>

    <div class="container text-center">
        <h1>Welcome to <span>TEAU CourseFinder</span></h1>
        <p>A Unified, <span style="color:#FFD700;">AI-Powered</span> Course Discovery Platform</p>
        <a href="auth.php" class="btn btn-start"><i class="fa-solid fa-right-to-bracket"></i> Get Started</a>
    </div>

    <footer>
        <div class="container text-center">
            <p>Created with <i class="fa fa-heart"></i> for TEAU. &copy; 2025.</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>