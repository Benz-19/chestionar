<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chestionar Angajatorii</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset & Font */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            /* Light gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* Full viewport height */
            overflow: hidden;
            /* Prevent scrollbar issues */
            text-align: center;
        }

        /* Container Styling */
        .landing-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 90%;
            /* Responsive width */
            max-width: 600px;
            /* Max width for desktop */
            animation: fadeIn 1s ease-out;
            /* Simple fade-in animation */
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 2.5em;
            /* Larger heading */
        }

        p {
            color: #555;
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .login-button {
            display: inline-block;
            /* Allows padding and margin */
            padding: 15px 30px;
            background-color: #007bff;
            /* Blue button */
            color: white;
            text-decoration: none;
            /* Remove underline from link */
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .login-button:hover {
            background-color: #0056b3;
            /* Darker blue on hover */
            transform: translateY(-2px);
            /* Slight lift effect */
        }

        .login-button:active {
            transform: translateY(0);
            /* Press effect */
        }

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .landing-container {
                padding: 30px;
            }

            h1 {
                font-size: 2em;
            }

            p {
                font-size: 1em;
            }

            .login-button {
                padding: 12px 25px;
                font-size: 1.1em;
            }
        }

        @media (max-width: 480px) {
            .landing-container {
                padding: 25px;
                border-radius: 10px;
            }

            h1 {
                font-size: 1.8em;
                margin-bottom: 15px;
            }

            p {
                font-size: 0.95em;
                margin-bottom: 25px;
            }

            .login-button {
                padding: 10px 20px;
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <div class="landing-container">
        <h1>Welcome to Chestionar Angajatorii</h1>
        <p>
            Your feedback is valuable to us. Please log in to access the employee satisfaction questionnaire.
            Your responses will help us improve our workplace environment.
        </p>
        <a href="resources/Views/login.php" class="login-button">Login to Questionnaire</a>
    </div>
</body>

</html>
