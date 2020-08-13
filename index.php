<?php 

if (file_exists('config.php'))
    include 'config.php';
else    
{    
    $sitename = 'bloggggg';
    $password = 'test123';
}

session_set_cookie_params(30 * 24 * 3600, dirname($_SERVER['SCRIPT_NAME']));   session_start(); // remember me

if (isset($_POST['pass']))
{
    if ($_POST['pass'] === $password) 
    { 
        $_SESSION['logged'] = 1; header('Location: .'); // reload page to prevent form resubmission popup when refreshing
    }
    else
    {
        header('Location: login'); 
    }

}

if (isset($_GET['action']) && $_GET['action'] === 'logout')  { $_SESSION['logged'] = 0; header('Location: .'); }  // reload page to prevent ?action=logout to stay 

if (isset($_GET['action']) && $_GET['action'] === 'login')
{ 
    if (!isset($_SESSION['logged']) || !$_SESSION['logged'] == 1) 
    { 
        echo '<html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"></head><body><form action="." method="post"><input type="password" name="pass" value="" autofocus><input type="submit" value="Submit"></form></body></html>'; 
        exit; 
    }
    else 
    { 
        header('Location: .');   // reload page to prevent ?action=login to stay if already logged in
    }  
}    

function generatearticle($article, $singlearticle = false)
{
    global $content, $title, $metaog;
    $articlestr = file_get_contents($article);
    list($title, $articlecontent) = preg_split('(\r?\n)', $articlestr, 2);  // split into 2 parts : before/after the first blank line
    setlocale(LC_TIME, 'en_US.utf8');
    $date = strftime('%d %B %Y', strtotime((explode('-', pathinfo($article, PATHINFO_FILENAME))[0])));
    $tagsarray = explode('#', pathinfo($article, PATHINFO_FILENAME));
    array_shift($tagsarray);
    $tagslist = '';
    foreach ($tagsarray as $tag)
        $tagslist .= "<a class='tag' href='tag/$tag'>#$tag</a> ";
    $url = explode('#', pathinfo($article, PATHINFO_FILENAME))[0];
    $url = end(explode('-', $url)); 
    $content .= "<div class='article'><h2 class='articletitle'><a href='$url'>$title</a></h2><div class='small'><a class='date' href='$url'>$date</a>$tagslist";
    $content .= ((isset($_SESSION['logged']) && ($_SESSION['logged'] == 1)) ? "<a href='edit/$url'>✍</a>" : "") . "</div>";
    if (function_exists('displayBeforeArticleContent')) 
        $content .= displayBeforeArticleContent($url, $title);
    $renderedcontent = (new Parsedown())->text($articlecontent);
    $content .= $renderedcontent;
    if ($singlearticle)
    { 
       if (function_exists('displayBeforeArticleContent')) 
            $content .= displayAfterArticleContent($url, $title);
        $content .= '<a href="." class="otherarticles">← Other articles</a>';
        $ogtitle = htmlspecialchars($title, ENT_QUOTES);
        $ogdescription = htmlspecialchars(trim(substr(strip_tags(preg_replace('#<script(.*?)>(.*?)</script>#is', '', $renderedcontent)), 0, 100)) . '...', ENT_QUOTES);
        $metaog = "<meta property='og:title' content='{$ogtitle}'/><meta property='og:description' content='{$ogdescription}'>";
        if (preg_match('/\[featuredimage\]:(.*)/', $articlecontent, $match))
            $metaog .= "<meta property='og:image' content='{$match[1]}'><meta name='twitter:image' content='{$match[1]}'><meta name='twitter:card' content='summary_large_image'/>";
    }
    $content .= "</div>";
}

require 'Parsedown.php';

$siteroot = substr($_SERVER['PHP_SELF'], 0, - strlen(basename($_SERVER['PHP_SELF'])));   // must have trailing slash, we don't use dirname because produces antislash on Windows
$homepage = (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == $siteroot);
$requestedarticle = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$tagview = (substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen($siteroot), 4) === "tag/");
$content = '';
$start = isset($_GET['start']) ? $_GET['start'] : 0;

if (!$homepage and !$tagview)          // article wanted
{
    $articles = glob("./articles/*$requestedarticle{#*,}.txt", GLOB_BRACE);
    if (empty($articles))
        $homepage = true;     // 404, let's go to homepage
    else 
        generatearticle($articles[0], true);
}    

if ($homepage or $tagview)
{
    if ($tagview)
        $articles = array_slice(array_reverse(glob("./articles/*#{$requestedarticle}*.txt", GLOB_BRACE)), $start, 10);
    else
        $articles = array_slice(array_reverse(glob("./articles/*.txt", GLOB_BRACE)), $start, 10);
    foreach($articles as $article)
        generatearticle($article);
    if ($start > 0)
        $content .= '<a class="navigation" href="' . (($start > 10) ? '?start=' . ($start - 10) : "") . '">Newer articles</a>&nbsp;'; 
    if (count(array_slice(array_reverse(glob("./articles/*.txt", GLOB_BRACE)), $start, 11)) > 10) 
        $content .= '<a class="navigation" href="?start=' . ($start + 10) . '">Older articles</a>'; 
}

$pagetitle = $sitename;

if ($tagview) 
    $pagetitle .= " - #$requestedarticle";
else if (!$homepage)
    $pagetitle .= " - $title";

?>
<!DOCTYPE html>
<html lang="en" class="htmlmain">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pagetitle; ?></title>
<?php 
if (isset($metaheaders)) echo $metaheaders; 
if (isset($metaog)) echo $metaog;
?>
<base href="<?php echo htmlspecialchars($siteroot, ENT_QUOTES, 'UTF-8'); ?>">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,700" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div id="nav"><div class="hamburger-body"></div></div>

<div id="left" class="md">
<?php echo (new Parsedown())->text(file_get_contents('sidebar.txt')); ?>
</div>

<div id="content" class="md">
<?php echo $content; ?>
<div id="footer" class="small"><a href="">© <?php echo date('Y') . " " . $sitename; ?></a>. Powered by <a href="https://www.github.com/josephernest/bloggggg">bloggggg</a>.
<?php echo (isset($_SESSION['logged']) && ($_SESSION['logged'] == 1)) ? '<a href="edit">New article</a>. <a href="logout">Log out</a>.' : '<a href="login">Login</a>.'; ?>
<?php if (isset($endoffooter)) echo $endoffooter; ?>
</div>
</div>

<script>
document.getElementById('nav').addEventListener('click', function() { document.getElementById('left').className = 'md opened'; document.getElementById('nav').className = 'hidden'; });
document.getElementById('content').addEventListener('click', function() { document.getElementById('left').className = 'md'; });
</script>

<?php if (isset($endofpage)) echo $endofpage; ?>

</body>
</html>
