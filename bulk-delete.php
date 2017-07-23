<?php

require __DIR__ . '/vendor/autoload.php';
require 'config.php';

//local params
$params["blog"] = "";
$params["tag"]= "";
$params["keep_time"] = "7"; //in days, do not delete posts newer than this period
$params["limit"] = "20";

$client = new Tumblr\API\Client(CONSUMER_KEY, CONSUMER_SECRET);
$client->setToken(OAUTH_TOKEN, OAUTH_TOKEN_SECRET);

$blog_names = array_map(function($blog){
    return $blog->name;
}, $client->getUserInfo()->user->blogs);

//params error handlers
if(!in_array($params["blog"], $blog_names)){
     die("Blog not found!");
}
if(empty(trim($params["tag"]))){
    die("Tag not specified!");
}

$deletePosts = array_filter($client->getBlogPosts($params["blog"], $options = null)->posts, function($post) use ($params){
    return in_array($params["tag"], $post->tags);
});

$toDelete= array();
foreach($deletePosts as $post){
    if (date_diff(date_create($post->date), new DateTime())->format('%a') >= $params["keep_time"])
        array_push($toDelete, array("id"=>$post->id, "reblog_key"=>$post->reblog_key));
}

$i = 0;
$deletedPosts = array();
foreach ($toDelete as $post) {
    $client->deletePost($params["blog"], $post["id"], $post["reblog_key"]);
    $deletedPosts = array_merge($deletedPosts,array_filter(
        $deletePosts,
        function ($e) use ($post) {
            return $e->id == $post["id"];
        }
    ));
    //apply limit param
    if (++$i >= $params["limit"]) break;
}

/*echo "<pre>";
print_r($deletedPosts);
echo "</pre><hr>";*/

//display deleted posts
echo "<h3>Posts deleted:</h3>";
foreach($deletedPosts as $post) {

    $post->time_ago = date_diff(date_create($post->date), new DateTime())->format('%a');
    
    switch ($post->type) {
    case 'text':
        echo <<<EOL
Id: {$post->id}<br>
Time: {$post->date}<br>
Posted: {$post->time_ago} days ago<br>
Type: {$post->type}<br>
Title: {$post->title}<br>
Body: {$post->body}
<hr>
EOL;
        break;
    case 'photo':
        echo <<<EOL
Id: {$post->id}<br>
Time: {$post->date}<br>
Posted: {$post->time_ago} days ago<br>
Type: {$post->type}<br>
Caption: {$post->caption}
<hr>
EOL;
        break;
    case 'link':
        echo <<<EOL
Id: {$post->id}<br>
Time: {$post->date}<br>
Posted: {$post->time_ago} days ago<br>
Type: {$post->type}<br>
Title: {$post->title}<br>
URL: {$post->url}
<hr>
EOL;
        break;
    }
}
