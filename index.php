<?php
/**
 * НАСТРОЙКИ ВАШИХ ПРОЕКТОВ
 * Просто добавляйте элементы в массив ниже.
 */
$myProjects = [
    ["name" => "Travel Blog", "url" => "https://wanderpath.dev", "icon" => "🌍"],
    ["name" => "Dev Dashboard", "url" => "https://panel.byteforge.io", "icon" => "🖥️"],
    ["name" => "Open Source Repo", "url" => "https://github.com/exampleuser/cloud-tools", "icon" => "🐙"], 
    ["name" => "Game Server Panel", "url" => "https://play.northrealm.gg:25565", "icon" => "🎮"], 
    ["name" => "AI Tools Lab", "url" => "https://neurostack.ai", "icon" => "🤖"],
    ["name" => "Portfolio", "url" => "https://alexdev.space", "icon" => "🚀"],
];

/**
 * ЛОГИКА ХРАНИЛИЩА
 */
$root = __DIR__ . "/files/";
if (!is_dir($root)) mkdir($root, 0777, true);

$path = realpath($root . "/" . ($_GET["dir"] ?? ""));
if ($path === false || strpos($path, realpath($root)) !== 0) {
    $path = realpath($root);
}

// Переименование (AJAX)
if (isset($_POST["old_name"]) && isset($_POST["new_name"])) {
    $old = $path . "/" . basename($_POST["old_name"]);
    $new = $path . "/" . basename($_POST["new_name"]);
    if (file_exists($old)) rename($old, $new);
    echo "ok"; exit;
}

// Создание папки
if (isset($_POST["newfolder"])) {
    $name = preg_replace("/[^a-zA-Z0-9_\-а-яА-Я]/u", "", $_POST["newfolder"]);
    if ($name) mkdir($path . "/" . $name);
    header("Location: ?dir=" . urlencode($_GET["dir"] ?? ""));
    exit;
}

// Загрузка файлов
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["files"])) {
    foreach ($_FILES["files"]["name"] as $i => $name) {
        $clean = basename($name);
        move_uploaded_file($_FILES["files"]["tmp_name"][$i], $path . "/" . $clean);
    }
    header("Location: ?dir=" . urlencode($_GET["dir"] ?? ""));
    exit;
}

// Удаление
if (isset($_GET["delete"])) {
    $file = basename($_GET["delete"]);
    $target = $path . "/" . $file;
    if (is_file($target)) unlink($target);
    if (is_dir($target)) rmdir($target);
    header("Location: ?dir=" . urlencode($_GET["dir"] ?? ""));
    exit;
}

$items = array_diff(scandir($path), [".", ".."]);
function size_fmt($bytes) {
    $u = ["B","KB","MB","GB"]; $i = 0;
    while ($bytes > 1024 && $i < 3) { $bytes /= 1024; $i++; }
    return round($bytes, 2) . " " . $u[$i];
}
$currentDir = trim(str_replace(realpath($root), "", $path), DIRECTORY_SEPARATOR);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Cloud Panel</title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --text: #f1f5f9; --text-dim: #94a3b8;
            --accent: #3b82f6; --accent-hover: #2563eb; --border: #334155; --danger: #ef4444;
        }
        .light {
            --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --text-dim: #64748b;
            --accent: #2563eb; --accent-hover: #1d4ed8; --border: #e2e8f0; --danger: #dc2626;
        }
        body { margin: 0; font-family: 'Inter', system-ui, sans-serif; background: var(--bg); color: var(--text); transition: .3s; }
        .container { max-width: 1000px; margin: auto; padding: 20px; }

        /* Projects Grid */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .project-card { 
            background: var(--card); border: 1px solid var(--border); border-radius: 12px; 
            padding: 15px; text-decoration: none; color: var(--text); display: flex; 
            align-items: center; gap: 12px; transition: .2s;
        }
        .project-card:hover { border-color: var(--accent); transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .project-icon { font-size: 1.5rem; background: var(--bg); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }

        /* General UI */
        .section-title { font-size: 0.9rem; font-weight: 700; text-transform: uppercase; color: var(--text-dim); margin-bottom: 15px; display: block; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; margin-bottom: 20px; position: relative; overflow: hidden; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .search-input { background: var(--card); border: 1px solid var(--border); padding: 10px 15px; border-radius: 10px; color: var(--text); outline: none; flex: 1; min-width: 200px; }

        /* Dropzone */
        #dropzone { border: 2px dashed var(--border); border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: .2s; }
        #dropzone:hover { border-color: var(--accent); background: rgba(59, 130, 246, 0.05); }
        
        /* File List */
        .file-item { display: flex; align-items: center; padding: 12px; border-bottom: 1px solid var(--border); transition: .2s; }
        .file-item:last-child { border-bottom: none; }
        .file-item:hover { background: rgba(59, 130, 246, 0.03); }
        
        .preview { width: 42px; height: 42px; border-radius: 8px; background: var(--bg); display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        
        .info { flex: 1; min-width: 0; }
        .name { display: block; font-weight: 500; text-decoration: none; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .meta { font-size: 0.75rem; color: var(--text-dim); }

        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: .2s; }
        .btn-primary { background: var(--accent); color: white; }
        .btn-icon { background: transparent; border: 1px solid var(--border); color: var(--text-dim); padding: 8px; }
        .btn-icon:hover { border-color: var(--accent); color: var(--accent); }

        .progress-bar { position: absolute; bottom: 0; left: 0; height: 4px; background: var(--accent); width: 0; transition: .3s; }

        /* Stats */
        .stats-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 25px; }
        .stat-item { background: var(--card); padding: 10px; border-radius: 10px; border: 1px solid var(--border); font-size: 12px; text-align: center; }

        /* Mobile */
        @media (max-width: 600px) {
            .projects-grid { grid-template-columns: 1fr 1fr; }
            .container { padding: 10px; }
            .actions { display: flex; gap: 4px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1 style="font-size: 1.5rem;">👋 Hello, Rikaniel</h1>
        <button class="btn btn-icon" onclick="toggleTheme()">🌓 Theme</button>
    </div>

    <span class="section-title">My Projects</span>
    <div class="projects-grid">
        <?php foreach ($myProjects as $p): ?>
            <a href="<?= $p['url'] ?>" class="project-card" target="_blank">
                <div class="project-icon"><?= $p['icon'] ?></div>
                <span style="font-weight: 600;"><?= $p['name'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <span class="section-title">Storage & Files</span>
    
    <div class="card">
        <div id="dropzone" onclick="document.getElementById('fileInput').click()">
            <div style="font-size: 2rem; margin-bottom: 10px;">📤</div>
            <b>Кликните или перетащите файлы сюда</b>
            <div id="file-count" style="font-size: 12px; color: var(--accent); margin-top: 5px;"></div>
        </div>
        <form id="uploadForm" method="post" enctype="multipart/form-data">
            <input type="file" name="files[]" id="fileInput" multiple hidden onchange="handleSelect()">
            <button type="submit" id="upBtn" class="btn btn-primary" style="width:100%; margin-top:15px; display:none;">Начать загрузку</button>
        </form>
        <div class="progress-bar" id="pBar"></div>
    </div>
    <div class="stats-bar">
        <div class="stat-item" style = "text-align: center;"><b>Disk:</b> <?= size_fmt(disk_free_space("/")) ?> free</div>
    </div>
    <div style="display: grid; gap: 10px; margin-bottom: 20px;">
        <input type="text" id="search" class="search-input" placeholder="Поиск файлов...">
        <form method="post" style="display:flex; gap:10px;">
            <input type="text" name="newfolder" class="search-input" placeholder="Новая папка" required>
            <button type="submit" class="btn btn-primary">Создать</button>
        </form>
    </div>

    <div class="card" style="padding: 0;">
        <div style="padding: 12px 20px; border-bottom: 1px solid var(--border); font-size: 0.8rem; color: var(--text-dim);">
            Путь: /<?= htmlspecialchars($currentDir) ?>
        </div>
        <div id="fileList">
            <?php if ($currentDir): ?>
                <div class="file-item">
                    <div class="preview">↩️</div>
                    <div class="info">
                        <a href="?dir=<?= urlencode(dirname($currentDir) == "." ? "" : dirname($currentDir)) ?>" class="name">Назад</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($items as $item): 
                $full = $path . "/" . $item;
                $isDir = is_dir($full);
                $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                $relPath = "files/" . ($currentDir ? $currentDir . "/" : "") . $item;
            ?>
            <div class="file-item" data-name="<?= htmlspecialchars(mb_strtolower($item)) ?>">
                <div class="preview">
                    <?php if ($isDir): ?>📁
                    <?php elseif ($isImg): ?>
                        <img src="<?= $relPath ?>" loading="lazy">
                    <?php else: ?>📄
                    <?php endif; ?>
                </div>
                <div class="info">
                    <?php if ($isDir): ?>
                        <a href="?dir=<?= urlencode(trim($currentDir . "/" . $item, "/")) ?>" class="name"><?= htmlspecialchars($item) ?></a>
                        <span class="meta">Папка</span>
                    <?php else: ?>
                        <a href="<?= $relPath ?>" target="_blank" class="name"><?= htmlspecialchars($item) ?></a>
                        <span class="meta"><?= size_fmt(filesize($full)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="actions" style="display:flex; gap:8px;">
                    <button class="btn btn-icon" onclick="copyLink('<?= $relPath ?>')">🔗</button>
                    <button class="btn btn-icon" onclick="renameItem('<?= addslashes($item) ?>')">✏️</button>
                    <a href="?delete=<?= urlencode($item) ?>&dir=<?= urlencode($currentDir) ?>" 
                       class="btn btn-icon" onclick="return confirm('Удалить?')">🗑</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Тема
function toggleTheme() {
    document.body.classList.toggle("light");
    localStorage.setItem("theme", document.body.classList.contains("light") ? "light" : "dark");
}
if (localStorage.getItem("theme") === "light") document.body.classList.add("light");

// Поиск
document.getElementById('search').oninput = function() {
    let val = this.value.toLowerCase();
    document.querySelectorAll('.file-item[data-name]').forEach(el => {
        el.style.display = el.getAttribute('data-name').includes(val) ? 'flex' : 'none';
    });
};

// Выбор файлов
function handleSelect() {
    let input = document.getElementById('fileInput');
    let info = document.getElementById('file-count');
    let btn = document.getElementById('upBtn');
    if(input.files.length > 0) {
        info.innerText = "Выбрано: " + input.files.length + " ф.";
        btn.style.display = "block";
    }
}

// Загрузка
document.getElementById('uploadForm').onsubmit = function(e) {
    e.preventDefault();
    let fd = new FormData(this);
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "");
    xhr.upload.onprogress = e => {
        if (e.lengthComputable) {
            document.getElementById('pBar').style.width = (e.loaded / e.total * 100) + "%";
        }
    };
    xhr.onload = () => location.reload();
    xhr.send(fd);
};

// Функции
function copyLink(path) {
    let url = window.location.origin + '/' + path;
    navigator.clipboard.writeText(url).then(() => alert('Ссылка скопирована!'));
}

function renameItem(oldName) {
    let newName = prompt("Новое имя:", oldName);
    if (newName && newName !== oldName) {
        let fd = new FormData();
        fd.append('old_name', oldName);
        fd.append('new_name', newName);
        fetch(window.location.href, {method: 'POST', body: fd}).then(() => location.reload());
    }
}
</script>
</body>
</html>