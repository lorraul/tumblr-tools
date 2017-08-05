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

$deletePosts = $client->getBlogPosts($params["blog"], array('tag' => $params["tag"]))->posts;

//apply param criterias
//filtered post ids
$i = 0;
$toDelete= array();
foreach($deletePosts as $post){
    if (date_diff(date_create($post->date), new DateTime())->format('%a') >= $params["keep_time"])
        array_push($toDelete, array("id"=>$post->id, "reblog_key"=>$post->reblog_key));
    //apply limit param
    if (++$i >= $params["limit"]) break;
}

//filtered posts with details
$deletedPosts = array();
foreach ($toDelete as $post) {
    $deletedPosts = array_merge($deletedPosts,array_filter(
        $deletePosts,
        function ($e) use ($post) {
            return $e->id == $post["id"];
        }
    ));
    //delete posts
    $client->deletePost($params["blog"], $post["id"], $post["reblog_key"]);
}

//display posts
echo "Deleted posts:<hr><pre>";
print_r($deletedPosts);
echo "</pre>";
