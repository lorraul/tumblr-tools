<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';
require 'common.php';

//local params
$params["blog"] = "";
$params["offset"] = "12000"; //get old posts with this offset
$params["limit"] = "20"; //returned number of posts
$params["exclude_tags"] = array("links", "reblog");

$client = new Tumblr\API\Client(CONSUMER_KEY, CONSUMER_SECRET);
$client->setToken(OAUTH_TOKEN, OAUTH_TOKEN_SECRET);

$blog_names = array_map(function($blog){
    return $blog->name;
}, $client->getUserInfo()->user->blogs);

//params error handlers
if(!in_array($params["blog"], $blog_names)){
     die("Blog not found!");
}

$allPosts = array();
getPosts($params["offset"]); // recursive, start with offset 0

$postToLink;

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
    
    if (!isset($postToLink)){ //select first iterated post if $postToLink not set yet
        $postToLink = $post;
        continue;
    }
    
    if($post->note_count > $postToLink->note_count){  
        $postToLink = $post;
    }    
}

echo "Linked post:<hr>";
display_posts(array($postToLink));
echo "<hr>All returned posts:<hr>";
display_posts($allPosts);

$client->createPost($params["blog"], array(
    "type" => "link",
    "state" => "queue",
    "tags" => "links",
    "title" => $postToLink->caption,
    "url" => $postToLink->post_url,
    "description" => "As of ".date("F j, Y", strtotime($postToLink->date)),
    "thumbnail" => previewPhotoUrl($postToLink)
));

function previewPhotoUrl($post){
    if(count($post->photos)>0)
        foreach ($post->photos[0]->alt_sizes as $photoObject){
            if ($photoObject->width == 250) return $photoObject->url;
        }
    return "http://via.placeholder.com/250x150";
}

function getPosts($offset){
    global $allPosts, $client, $params;
    if($offset >= $params["offset"]+$params["limit"]) return null; //maximum requests limited to 360
    $allPosts = array_merge($allPosts, $client->getBlogPosts($params["blog"], array("offset" => $offset, "filter" => "text"))->posts);
    $offset += 20;
    getPosts($offset);
}
