<?php

// Этот код нужно добавить в cron выполняться каждый день

require_once 'config.php';
require_once 'api.php';
spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

if ($_SERVER['argv'][1] === ACCESS_KEY_TO_PANEL) {
    $count_of_posts = [
        'postirony' => rand(7, 10)
    ];
    $posts = [];
    foreach ($count_of_posts as $type => $count) {
        for ($i = 0; $i < $count; $i++) {
            array_push($posts, $type);
        }
    }
    shuffle($posts);
    foreach ($posts as $post_num => $post_type) {
        $attachments = [];
        $text = '';
        switch ($post_type) {
            case 'postirony':
                $photo = get_random_item($post_type, new AttachmentRules('photo', [1]), [
                    new AttachmentRules('photo', [1])
                ], 20);
                array_push($attachments, 'photo' . $photo->owner_id . '_' . $photo->id);

                break;
        }
        api_method('wall.post', [
            'owner_id' => -GROUP_ID,
            'attachments' => implode(',', $attachments),
            'access_token' => USER_ACCESS_TOKEN,
            'publish_date' => time() + 60 * 52 * $post_num + 60 * rand(45, 60)
        ]);
    }
} else
    echo 'Access denied';
?>