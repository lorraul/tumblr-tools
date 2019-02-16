<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';
require 'common.php';

//local params
$params["blog"] = "";
$params["source_blog"] = "";
$params["source_tag"] = "";
//$params["publish_frequency"] = "daily";
$params["delay"] = "1"; //delay first publish date, in days
$params["removePosts"] = 1; //remove n number of posts from the start
$params["publish_time"] = "";
$params["tags"] = "";

$client = new Tumblr\API\Client(CONSUMER_KEY, CONSUMER_SECRET);
$client->setToken(OAUTH_TOKEN, OAUTH_TOKEN_SECRET);

$blog_names = array_map(function($blog){
    return $blog->name;
}, $client->getUserInfo()->user->blogs);

//params error handlers
if(!in_array($params["blog"], $blog_names)){
     die("Blog not found!");
}

$allPosts = $client->getBlogPosts($params["source_blog"], array("tag" => $params["source_tag"]))->posts;

if($params["removePosts"] > 0){
    for ($i = 0; $i < $params["removePosts"]; $i++) {
        array_shift($allPosts);
    }
}

foreach($allPosts as $key=>$post) {
    $publish_date = date("Y-m-d", strtotime("+ ".($key+$params["delay"])." day"));
    $client->reblogPost($params["blog"], $post->id, $post->reblog_key, array(
        "state" => "queue",
        "tags" => $params["tags"],
        "publish_on" => "$publish_date {$params['publish_time']}"
    ));
}

display_posts($allPosts);
