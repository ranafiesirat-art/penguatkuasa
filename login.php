<?php
session_start();

// Sambungan ke database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "penguatkuasa";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Sambungan gagal: " . $conn->connect_error);
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_pengguna = filter_input(INPUT_POST, 'id_pengguna', FILTER_SANITIZE_STRING);
    $kata_laluan = filter_input(INPUT_POST, 'kata_laluan', FILTER_SANITIZE_STRING);

    $sql = "SELECT kata_laluan FROM pengguna WHERE id_pengguna = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_pengguna);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($kata_laluan, $row['kata_laluan'])) {
            $_SESSION['masuk'] = true;
            header("Location: anggota/menu.php"); // Laluan betul ke menu.php dalam folder anggota
            exit();
        } else {
            $error = "Kata laluan salah.";
        }
    } else {
        $error = "ID Pengguna tidak wujud.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISTEM PENGUATKUASAAN - Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #000000;
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: 2.2rem;
            color: #6f42c1;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        .login-header p {
            color: #6c757d;
            font-size: 1rem;
        }
        .login-header .icon {
            font-size: 3rem;
            color: #6f42c1;
            margin-bottom: 10px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .form-control {
            border: 2px solid #6f42c1;
            border-radius: 10px;
            padding: 12px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus {
            border-color: #9f7aea;
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }
        .form-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .btn-login {
            background-color: #6f42c1;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 10px;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }
        .btn-login:hover {
            background-color: #5a32a3;
            transform: scale(1.05);
        }
        .error {
            color: #dc3545;
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .audio-player {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 2;
        }
    </style>
</head>
<body>
    <!-- Canvas untuk partikel -->
    <canvas id="particles"></canvas>

    <div class="login-container">
        <div class="login-header">
            <i class="bi bi-shield-lock-fill icon"></i>
            <h1>SISTEM REKOD PENGUATKUASAAN</h1>
            <p>Sila log masuk untuk teruskan</p>
        </div>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label class="form-label">ID Pengguna</label>
                <input type="text" name="id_pengguna" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Kata Laluan</label>
                <input type="password" name="kata_laluan" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-login w-100"><i class="bi bi-box-arrow-in-right me-2"></i>LOG MASUK</button>
            <?php if (isset($error)) { ?>
                <p class="error"><?php echo $error; ?></p>
            <?php } ?>
        </form>
    </div>

    <!-- Elemen audio untuk lagu -->
    <div class="audio-player">
        <audio id="background-music" controls autoplay loop muted>
            <source src="audio/the_game.mp3" type="audio/mpeg">
            Pelayar anda tidak menyokong elemen audio.
        </audio>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript untuk partikel -->
    <script>
        const canvas = document.getElementById('particles');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        let particlesArray = [];
        const numberOfParticles = 50;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.size = Math.random() * 5 + 1;
                this.speedX = Math.random() * 1 - 0.5;
                this.speedY = Math.random() * 1 - 0.5;
            }
            update() {
                this.x += this.speedX;
                this.y += this.speedY;
                if (this.size > 0.2) this.size -= 0.1;
                if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
                if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
            }
            draw() {
                ctx.fillStyle = 'rgba(159, 122, 234, 0.5)';
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
                ctx.fill();
            }
        }

        function init() {
            for (let i = 0; i < numberOfParticles; i++) {
                particlesArray.push(new Particle());
            }
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
                particlesArray[i].draw();
                if (particlesArray[i].size <= 0.2) {
                    particlesArray.splice(i, 1);
                    i--;
                    particlesArray.push(new Particle());
                }
            }
            requestAnimationFrame(animate);
        }

        init();
        animate();

        window.addEventListener('resize', function() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });

        // JavaScript untuk mengawal kelantangan audio
        const audio = document.getElementById('background-music');
        audio.volume = 0.3; // Tetapkan kelantangan awal kepada 30%
    </script>
</body>
</html>