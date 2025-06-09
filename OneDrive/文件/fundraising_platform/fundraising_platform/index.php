<?php
require 'db_connect.php';
session_start();

// 處理登入
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT Account, Password, Name FROM User WHERE Account = ?");
    $stmt->execute([$account]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 注意：實際應用應使用 password_verify() 驗證雜湊密碼
    if ($user && $password === $user['Password']) {
        $_SESSION['user_id'] = $user['Account'];
        $_SESSION['user_name'] = $user['Name'];
        $stmt = $pdo->prepare("SELECT AID FROM Administrator WHERE AID = ?");
        $stmt->execute([$account]);
        $_SESSION['role'] = $stmt->fetch() ? 'admin' : 'student';
    } else {
        $login_error = "帳號或密碼錯誤";
    }
}

// 處理註冊
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $account = $_POST['account'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    // 檢查帳號是否已存在
    $stmt = $pdo->prepare("SELECT Account FROM User WHERE Account = ?");
    $stmt->execute([$account]);
    if ($stmt->fetch()) {
        $register_error = "帳號已存在";
    } else {
        // 插入 User 表
        $stmt = $pdo->prepare("INSERT INTO User (Account, Password, Name, Email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$account, $password, $name, $email]); // 實際應用應使用 password_hash()

        // 插入 Student 表
        $stmt = $pdo->prepare("INSERT INTO Student (SID) VALUES (?)");
        $stmt->execute([$account]);

        $register_success = "註冊成功，請登入";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>集資平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- 頁首 -->
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#">集資平台</a>
            <div class="ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text me-2">
                        歡迎，<?php echo htmlspecialchars($_SESSION['user_name']); ?>！
                    </span>
                    <a href="<?php echo $_SESSION['role'] === 'admin' ? 'admin.php' : 'student.php'; ?>" class="btn btn-primary me-2">我的儀表板</a>
                <?php else: ?>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#loginModal">登入</button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registerModal">註冊</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 登入模態框 -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">登入</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($login_error)) echo "<div class='alert alert-danger'>$login_error</div>"; ?>
                    <form method="POST">
                        <input type="hidden" name="login" value="1">
                        <div class="mb-3">
                            <label for="login_account" class="form-label">帳號</label>
                            <input type="text" class="form-control" id="login_account" name="account" required>
                        </div>
                        <div class="mb-3">
                            <label for="login_password" class="form-label">密碼</label>
                            <input type="password" class="form-control" id="login_password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">登入</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 註冊模態框 -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">註冊</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($register_error)) echo "<div class='alert alert-danger'>$register_error</div>"; ?>
                    <?php if (isset($register_success)) echo "<div class='alert alert-success'>$register_success</div>"; ?>
                    <form method="POST">
                        <input type="hidden" name="register" value="1">
                        <div class="mb-3">
                            <label for="register_account" class="form-label">帳號</label>
                            <input type="text" class="form-control" id="register_account" name="account" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_password" class="form-label">密碼</label>
                            <input type="password" class="form-control" id="register_password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_name" class="form-label">姓名</label>
                            <input type="text" class="form-control" id="register_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="register_email" class="form-label">電子郵件</label>
                            <input type="email" class="form-control" id="register_email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-success">註冊</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 主要內容 -->
    <div class="container my-4">
        <!-- 競賽列表 -->
        <h2>競賽列表</h2>
        <div class="row">
            <?php
            $stmt = $pdo->query("SELECT CID, Name, Organizing_Units, Registration_Deadline FROM Competition");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">' . htmlspecialchars($row['Name']) . '</h5>
                            <p class="card-text">主辦單位: ' . htmlspecialchars($row['Organizing_Units']) . '</p>
                            <p class="card-text">報名截止: ' . htmlspecialchars($row['Registration_Deadline']) . '</p>
                            <a href="competition_details.php?cid=' . htmlspecialchars($row['CID']) . '" class="btn btn-info">查看詳情</a>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <!-- 公告輪播 -->
        <h2 class="mt-5">最新公告</h2>
        <div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php
                $stmt = $pdo->query("SELECT Title, Content FROM Announcement");
                $first = true;
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $active = $first ? 'active' : '';
                    echo '
                    <div class="carousel-item ' . $active . '">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">' . htmlspecialchars($row['Title']) . '</h5>
                                <p class="card-text">' . htmlspecialchars($row['Content']) . '</p>
                            </div>
                        </div>
                    </div>';
                    $first = false;
                }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <!-- 頁尾 -->
    <footer class="bg-light text-center py-3">
        <p>版權 © 2025 集資平台</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>