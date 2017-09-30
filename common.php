<?php
function getPosts($offset, $limitOffset){
    global $allPosts, $client, $params;
    if($offset >= $limitOffset) return null; //maximum requests limited to 360
    $allPosts = array_merge($allPosts, $client->getBlogPosts($params["blog"], array("offset" => $offset, "filter" => "text"))->posts);    
    if (!isset($params["period"]) || date_diff(date_create(end($allPosts)->date), new DateTime())->format('%a') < $params["period"]){
        $offset += 20;
        getPosts($offset, $limitOffset);
    }
}

function previewPhotoUrl($post){
    if(count($post->photos)>0)
        foreach ($post->photos[0]->alt_sizes as $photoObject){
            if ($photoObject->width == 250) return $photoObject->url;
        }
    return "http://via.placeholder.com/250x150";
}

function display_posts($posts){
    foreach($posts as $post) {

        $post->time_ago = date_diff(date_create($post->date), new DateTime())->format('%a');

        switch ($post->type) {
        case 'text':
            echo <<<EOL
Url: <a href="$post->post_url">See post</a><br>
Id: {$post->id}<br>
Notes: {$post->note_count}<br>
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
Url: <a href="$post->post_url">See post</a><br>
Id: {$post->id}<br>
Notes: {$post->note_count}<br>
Time: {$post->date}<br>
Posted: {$post->time_ago} days ago<br>
Type: {$post->type}<br>
Caption: {$post->caption}
<hr>
EOL;
            break;
        case 'link':
            echo <<<EOL
Url: <a href="$post->post_url">See post</a><br>
Id: {$post->id}<br>
Notes: {$post->note_count}<br>
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
}
