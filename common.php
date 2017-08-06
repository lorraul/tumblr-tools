<?php

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
