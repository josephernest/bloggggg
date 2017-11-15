<?php 

if (file_exists('config.php'))
    include 'config.php';
else    
    $PASSWORD = 'test123';

session_set_cookie_params(30 * 24 * 3600, dirname($_SERVER['SCRIPT_NAME']));   session_start(); // remember me

$siteroot = substr($_SERVER['PHP_SELF'], 0, - strlen(basename($_SERVER['PHP_SELF'])));

if (isset($_POST['pass']) && $_POST['pass'] === $PASSWORD) { $_SESSION['logged'] = 1; } //header('Location: .');   // reload page to prevent form resubmission popup when refreshing / this works even if no .htaccess RewriteRule 

if (!isset($_SESSION['logged']) || !$_SESSION['logged'] == 1) { echo '<html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><base href="' . htmlspecialchars($siteroot, ENT_QUOTES, 'UTF-8') . '"></head><body><form action="edit" method="post"><input type="password" name="pass" value="" autofocus><input type="submit" value="Submit"></form></body></html>'; exit; }

// STOPS HERE IF UNLOGGED

// POSTING A POST
if (isset($_POST['main']))
{
    $url = empty($_POST['url']) ? ('randomurl' . rand()) : preg_replace("/[^a-zA-Z0-9-]+/", "", $_POST['url']);
    $date = preg_replace("/[^0-9]+/", "", $_POST['date']);
    $tags = preg_replace("/[^a-zA-Z0-9\s]+/", "", trim($_POST['tags']));
    if (strlen($tags) != 0)
        $tags = "#" . preg_replace("/\s/", "#", $tags);
    $fname = "articles/{$date}-{$url}{$tags}.txt";

    file_put_contents($fname, $_POST['main']);

    if (isset($_POST['old']))
    {
        $old = preg_replace("/[^a-zA-Z0-9#-]+/", "", $_POST['old']);
        $oldfname = "articles/{$old}.txt";
       
        if ($oldfname !== $fname)
            unlink($oldfname);
    }

    header("Location: {$siteroot}{$url}");
    exit;
}

$main = ''; $date = date("Y-m-d"); $tags = ''; $url = '';

// EDITING EXISTING POST
if (isset($_GET['url']))
{
    $url = preg_replace("/[^a-zA-Z0-9-]+/", "", $_GET['url']);
    $fname = glob("./articles/*-{$url}{#*,}.txt", GLOB_BRACE)[0];
    $main = file_get_contents($fname);
    preg_match('/.*?(#.*)\.txt/', $fname, $matches);
    $tags = ltrim(str_replace('#', ' #', $matches[1]));
    $date = date("Y-m-d", strtotime(explode('-', pathinfo($fname, PATHINFO_FILENAME))[0]));
    $old = pathinfo($fname, PATHINFO_FILENAME);
}


// DELETING EXISTING POST
if (isset($_GET['url']) && isset($_GET['action']) && ($_GET['action'] === 'delete'))
{
    $url = preg_replace("/[^a-zA-Z0-9-]+/", "", $_GET['url']);
    $fname = glob("./articles/*-{$url}{#*,}.txt", GLOB_BRACE)[0];
    unlink($fname);
    header('Location: .');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="htmleditor">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editor</title>
<base href="<?php echo htmlspecialchars($siteroot, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body class="fullheight">
<!-- <a href="index.php?action=logout" id="logout">✕</a> -->
<form method="POST" action="edit" class="fullheight">
<textarea class="editor" id="main" name="main" autofocus><?php echo $main; ?></textarea><input class="editor" id="date" name="date" value="<?php echo $date; ?>" autocomplete="off"/>
<input class="editor" id="tags" name="tags" placeholder="#tag1 #tag2" value="<?php echo $tags; ?>" autocomplete="off"/>
<input class="editor" id="url" name="url" placeholder="urlofthearticle" value="<?php echo $url; ?>" autocomplete="off"/>
<?php if (isset($old)) echo '<input type="hidden" name="old" value="' . $old. '" />'; ?>
<input type="submit" id="submit" value="Post" />
</form>
<div id="smallcommands"><a href="" id="deletepost">delete</a><a href="logout" id="">logout</a></div>
<script>
unsaved = false;
window.onbeforeunload = function() { if (unsaved) return 'You have not saved your article, closing or reloading the page will reset all changes.'; };

document.getElementById('deletepost').onclick = function(e) {
    e.preventDefault();
    if (confirm('Are you sure to want to delete this article?'))
        window.location.href = "edit.php?action=delete&url=<?php echo $url; ?>";
    return false;
}    
</script>
</body>
</html>