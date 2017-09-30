<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';
require 'common.php';

//local params
$params["blog"] = "";
$params["period"] = "7"; //in days, most popular posts in the last n days
$params["exclude_tags"] = array();
$params["limit"] = "10"; //number of popular posts returned

$client = new Tumblr\API\Client(CONSUMER_KEY, CONSUMER_SECRET);
$client->setToken(OAUTH_TOKEN, OAUTH_TOKEN_SECRET);

$blog_names = array_map(function($blog){
    return $blog->name;
}, $client->getUserInfo()->user->blogs);

//params error handlers
if(!in_array($params["blog"], $blog_names)){
     die("Blog not found!");
}
if(empty(trim($params["period"]))){
    die("Period not specified!");
}

$allPosts = array();
getPosts(0, 7200); // recursive, start with offset 0

$popularPosts = array();

foreach($allPosts as $post) {
    if (count($post->tags) == 0) continue; //do not insert posts with no tags
    
    //do not insert posts with excluded tags
    $excluded_tag_found = false;
    foreach($params["exclude_tags"] as $excluded_tag){
        if(in_array($excluded_tag, $post->tags)) {
            $excluded_tag_found = true;
            break;
        }
    }
    if ($excluded_tag_found) continue; 
    
    if(!strlen($post->reblog->tree_html) == 0) continue; //do not insert reblogs 
    
    if (count($popularPosts)<$params["limit"]){
        array_push($popularPosts, $post);
        continue;
    }
    
    $note_counts = array();
    foreach($popularPosts as $popularPost)
    {
        array_push($note_counts, $popularPost->note_count);
    }
    
    if($post->note_count > min($note_counts)){  
        foreach($popularPosts as $key => $postToCheck) {
            if ($postToCheck->note_count == min($note_counts)) {
                unset($popularPosts[$key]);
                break;
            }
        }
        array_push($popularPosts, $post);
    }    
}

//create list to publish
$listBody = "<p>Most popular posts in the last ".$params["period"]." days based on the number of notes:</p>\n";
foreach ($popularPosts as $post) {
    $listBody .= "<p>\n<a href=\"".$post->post_url."?utm_source=tumblr&utm_medium=pp\">";
    if(isset($post->caption)){
        $listBody .= mb_strimwidth(strip_tags($post->caption),0,80,"...");
    }
    else if(isset($post->title)){
        $listBody .= mb_strimwidth($post->title,0,50,"...");
    }
    $listBody .= "<br><br>\n";
    if ($post->type == "photo")
        $listBody .= "<img src=\"".previewPhotoUrl($post)."\"></a>\n</p>";
}
$listBody .= "</ul></p>";
$listBodyString = htmlentities($listBody);

//post confirmation
if (!isset($_POST['listBody'])) {
echo <<<EOL
<form action="popular-posts-m.php" method="post">
<input type="hidden" name="listBody" value="$listBodyString" />
<input type="hidden" name="postType" value="publish" />
<input type="submit" name="submit" id="submit" class="button" value="Post now"/>
</form>
<form action="popular-posts-m.php" method="post">
<input type="hidden" name="listBody" value="$listBodyString" />
<input type="hidden" name="postType" value="queue" />
<input type="submit" name="submit" id="submit" class="button" value="To queue"/>
</form>
EOL;
echo "<hr><h3>Popular posts:</h3>";
}
else echo "<hr><h3>Popular posts included:</h3>";

display_posts($popularPosts);

//after post confirmation ------------------------------------------
if (isset($_POST['listBody'])) {
    $client->createPost($params["blog"], array(
        "state" => "queue",
        "tags" => "pp",
        "title" => "Popular posts",
        "body" => $_POST['listBody']
    ));
    echo $_POST['listBody'];
}
