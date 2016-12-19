<?php 
$sitename = "bloggggg";

require 'Parsedown.php';

function generatearticle($article)
{
    global $content, $title;
    $articlestr = file_get_contents($article);
    list($articleheader, $articlecontent) = preg_split('~(?:\r?\n){2}~', $articlestr, 2);  // split into 2 parts : before/after the first blank line
    preg_match("/^TITLE:(.*)$/m", $articleheader, $title);
    preg_match("/^DATE:(.*)$/m", $articleheader, $date);
    $title = isset($title[1]) ? trim($title[1]) : '';
    $date = isset($date[1]) ? trim($date[1]) : '';
    $tagsarray = explode('#', pathinfo($article, PATHINFO_FILENAME));
    array_shift($tagsarray);
    $tagslist = '';
    foreach ($tagsarray as $tag) { $tagslist .= ", <a href=\"tag/$tag\">#$tag</a>"; }
    $url = explode('#', pathinfo($article, PATHINFO_FILENAME))[0];
    $url = end(explode('-', $url)); 
    $content .= "<div class=\"article\"><h2 class=\"articletitle\"><a href=\"$url\">$title</a></h2><div class=\"small\"><a href=\"$url\">$date</a>$tagslist</div>";
    $content .= (new Parsedown())->text($articlecontent);
    $content .= "</div>";
}

$siteroot = substr($_SERVER['PHP_SELF'], 0, - strlen(basename($_SERVER['PHP_SELF'])));   // must have trailing slash, we don't use dirname because produces antislash on Windows
$homepage == (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === $siteroot);
$requestedarticle = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$tagview = (substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen($siteroot), 4) === "tag/");
$content = '';

if (!$homepage and !$tagview)          // article wanted
{
    $articles = glob("./articles/*$requestedarticle{#*,}.{txt,md}", GLOB_BRACE);
    if (empty($articles))
    {
        $homepage = true;     // 404, let's go to homepage
    }
    else
    {
        generatearticle($articles[0]);
    }
}    

if ($homepage or $tagview)
{
    if ($tagview)
    {
        $articles = array_slice(array_reverse(glob("./articles/*#" . $requestedarticle . "*.{txt,md}", GLOB_BRACE)), $_GET['start'], 10);
    }
    else
    {
        $articles = array_slice(array_reverse(glob("./articles/*.{txt,md}", GLOB_BRACE)), $_GET['start'], 10);
    }
    foreach($articles as $article)
    {
        generatearticle($article);
    }
    if ($_GET['start'] > 0)
    { 
        $content .= "<a class=\"navigation\" href=\"" . (($_GET['start'] > 10) ? "?start=" . ($_GET['start'] - 10) : "") . "\">Newer articles</a>&nbsp; "; 
    }
    if (count(array_slice(array_reverse(glob("./articles/*.{txt,md}", GLOB_BRACE)), $_GET['start'], 11)) > 10) 
    { 
        $content .= "<a class=\"navigation\" href=\"?start=" . ($_GET['start'] + 10) . "\">Older articles</a>"; 
    }
}

$pagetitle = $sitename;
if ($tagview) 
{ 
    $pagetitle .= " - #$requestedarticle";
}
else if (!$homepage)
{
    $pagetitle .= " - $title";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pagetitle; ?></title>
<base href="<?php echo htmlspecialchars($siteroot, ENT_QUOTES, 'UTF-8'); ?>">
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>

<div id="nav"><div class="hamburger-body"></div></div>

<div id="left" class="md">
<?php echo (new Parsedown())->text(file_get_contents('sidebar.txt')); ?>
</div>

<div id="content" class="md">
<?php echo $content; ?>
<div id="footer" class="small"><a href="">© <?php echo date('Y') . " " . $sitename; ?></a>. Powered by <a href="https://www.github.com/josephernest/bloggggg">bloggggg</a>.</div>
</div>

<script>
document.getElementById('nav').addEventListener('click', function() { document.getElementById('left').className = 'md opened'; document.getElementById('nav').className = 'hidden'; });
document.getElementById('content').addEventListener('click', function() { document.getElementById('left').className = 'md'; });
</script>

</body>
</html>