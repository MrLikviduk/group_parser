<?php

spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

function connect_to_database()
{
    $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    return $mysqli;
}

function db_find($value, $table, $key = 'id')
{
    $mysqli = connect_to_database();
    return $mysqli->query("SELECT * FROM $table WHERE `$key` = '$value'")->fetch_assoc();
}

$mysqli = connect_to_database();

function send_message($message, $user_id, $end = TRUE)
{
    $request_params = array(
        'message' => $message,
        'user_id' => $user_id,
        'access_token' => ACCESS_TOKEN,
        'v' => VERSION
    );
    $get_params = http_build_query($request_params);
    file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
    //Возвращаем "ok" серверу Callback API

    if ($end)
        echo('ok');
}

function api_method($method, array $params)
{
    if (empty($params['access_token']))
        $params['access_token'] = ACCESS_TOKEN;
    if (empty($params['v']))
        $params['v'] = VERSION;
    $get_params = http_build_query($params);
    return json_decode(file_get_contents('https://api.vk.com/method/' . $method . '?' . $get_params));
}

function user_api_method($method, array $params) {
    $params['access_token'] = USER_ACCESS_TOKEN;
    return api_method($method, $params);
}

function attachment_is_used($attachment) {
    $type = $attachment->type;
    $id = $attachment->$type->id;
    $owner_id = $attachment->$type->owner_id;
    $mysqli = connect_to_database();
    $hash = 'none';
    if ($type === 'photo')
        $hash = md5_file(get_photo_url($attachment->photo));
    return $mysqli->query("SELECT * FROM used_attachments WHERE id_vk = '".$type.$owner_id.'_'.$id."' OR hash = '".$hash."'")->num_rows > 0;
}

function is_posted($group_id, $post_id, $type_id, $attachment_type = NULL)
{
    $mysqli = connect_to_database();
    $query = "SELECT * FROM posted_content WHERE group_id = '$group_id' AND post_id_vk = '$post_id' AND type_id = $type_id";
    return $mysqli->query($query)->num_rows == 1;
}

function contain_link($text)
{
    $tmp_arr = str_split($text);
    $s = '';
    foreach ($tmp_arr as $char) {
        if ($char === '[' || $char === '|' || $char === ']')
            $s .= $char;
    }
    return (strpos($text, 'http://') !== FALSE || strpos($text, 'https://') !== FALSE) || strpos($s, '[|]') !== FALSE;
}

function get_photo_url($photo)
{
    if (isset($photo->sizes)) {
        $sizes = $photo->sizes;
        $max_length = 0;
        $max_size = 0;
        for ($i = 0; $i < count($sizes); $i++) {
            $size = $sizes[$i];
            if ($size->width > $max_length || $size->height > $max_length) {
                $max_size = $size;
                $max_length = ($size->width > $size->height ? $size->width : $size->height);
            }
        }
        return $max_size->url;
    } else {
        return $photo->photo_2560 ?? $photo->photo_1280 ?? $photo->photo_807 ?? $photo->photo_604 ?? $photo->photo_130 ?? $photo->photo_75 ?? FALSE;
    }
}

function upload_image_on_server_by_url($image_url, $server_url)
{
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/tmp')) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/tmp');
    }
    while (TRUE) {
        $filename = rand(0, 10000);
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/tmp/image' . $filename . '.jpg')) {
            $filename = $_SERVER['DOCUMENT_ROOT'] . '/tmp/image' . $filename . '.jpg';
            break;
        }
    }
    file_put_contents($filename, file_get_contents($image_url));
    $url = $server_url;
    $image = $filename;
    $post_fields = array(
        'photo' => new CURLFile($image)
    );
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => array('Content-Type: multipart/form-data'),
        CURLOPT_RETURNTRANSFER => true
    ));
    $response = json_decode(curl_exec($ch));
    curl_close($ch);
    unlink($filename);
    return $response;
}

function upload_image_to_group_wall($photo)
{
    $photo_url = get_photo_url($photo);

    $server_url = api_method('photos.getWallUploadServer', [
        'group_id' => GROUP_ID,
        'access_token' => USER_ACCESS_TOKEN
    ])->response->upload_url;

    $response = upload_image_on_server_by_url($photo_url, $server_url);

    $photo = api_method('photos.saveWallPhoto', [
        'group_id' => GROUP_ID,
        'photo' => $response->photo,
        'server' => $response->server,
        'hash' => $response->hash,
        'access_token' => USER_ACCESS_TOKEN
    ])->response[0];

    return $photo;
}

function get_count_of_posts() {
    $mysqli = connect_to_database();
    $rubrics = $mysqli->query("SELECT * FROM rubrics")->fetch_all(MYSQLI_ASSOC);
    $sum_of_ratings = 0;
    for ($i = 0; $i < count($rubrics); $i++) {
        $rubric = $rubrics[$i];
        $ratings = [];
        $items = api_method('wall.search', [
            'owner_id' => -GROUP_ID,
            'query' => '#'.$rubric['slug'].'@chillhub',
            'count' => 100,
            'access_token' => USER_ACCESS_TOKEN
        ])->response->items;
        foreach ($items as $item) {
            array_push($ratings, $item->likes->count / $item->views->count);
        }
        $average_rating = array_sum($ratings) / count($ratings);
        $rubrics[$i]['rating'] = ($average_rating == 0 ? 0.000001 : $average_rating);
        $sum_of_ratings += $rubrics[$i]['rating'];
    }
    $posts_count = rand(12, 16);
    $count_of_posts = [];
    foreach ($rubrics as $i => $rubric) {
        $rubrics[$i]['percentage'] = round($rubric['rating'] / $sum_of_ratings * 100);
        if ($rubrics[$i]['percentage'] == 0)
            $rubrics[$i]['percentage'] = 1;
        $count_of_posts[$rubric['slug']] = round($posts_count * ($rubrics[$i]['percentage'] / 100));
        if ($count_of_posts[$rubric['slug']] == 0)
            $count_of_posts[$rubric['slug']] = 1;
    }
    return $count_of_posts;
}

// 1) Для проверки документов нужно будет в поле $legal_count_attachments под ключом "doc"
// добавить массив, ключами которого будет id типов документа, а значениями - допустимые количества
function get_random_item($type, $current_attachment_rules, $attachment_rules, $count_of_posts = 100, $text_can_set = FALSE, $text_must_set = FALSE, $links_can_be = FALSE) {
    $type_id = (int)db_find($type, 'types', 'slug')['id'];
    $mysqli = connect_to_database();
    $groups = $mysqli->query("SELECT * FROM groups WHERE `type_id` = {$type_id} ORDER BY rand()")->fetch_all(MYSQLI_ASSOC);
    foreach ($groups as $group) {
        $max_rating = -1;
        $items = api_method('wall.get', [
            'owner_id' => -$group['id_vk'],
            'count' => $count_of_posts,
            'access_token' => USER_ACCESS_TOKEN
        ])->response->items;
        foreach ($items as $item) {
            if (is_posted($group['id'], $item->id, $type_id) ||
                isset($item->is_pinned) ||
                ((contain_link($item->text) || isset($item->copy_history)) && !$links_can_be) ||
                (strlen($item->text) > 0 && !$text_can_set) ||
                (strlen($item->text) == 0 && $text_must_set))
                continue;
            if ($item->likes->count / $item->views->count <= $max_rating)
                continue;
            $current_attachments = [];
            $tmp_attachment_rules = $attachment_rules;
            $attachments_are_legal = TRUE;
            foreach ($item->attachments as $attachment) {
                $attachment_type = $attachment->type;
                $v = FALSE;
                for ($i = 0; $i < count($tmp_attachment_rules); $i++) {
                    $attachment_rule = $attachment_rules[$i];
                    if ($attachment_rule->comply_rules($attachment)) {
                        if ($current_attachment_rules->comply_rules($attachment) && !attachment_is_used($attachment))
                            array_push($current_attachments, new Attachment($attachment->type, $attachment->$attachment_type, $current_attachment_rules, $item->likes->count / $item->views->count, $item->id, $group['id']));
                        $tmp_attachment_rules[$i]->count++;
                        $v = TRUE;
                        break;
                    }
                }
                if ($v == FALSE) {
                    $attachments_are_legal = FALSE;
                    break;
                }
            }
            foreach ($tmp_attachment_rules as $tmp_attachment_rule) {
                if (!in_array($tmp_attachment_rule->count, $tmp_attachment_rule->legal_numbers)) {
                    $attachments_are_legal = FALSE;
                    break;
                }
            }
            foreach ($tmp_attachment_rules as $i => $tmp_attachment_rule) {
                $tmp_attachment_rules[$i]->reset();
            }
            if (!$attachments_are_legal || count($current_attachments) == 0)
                continue;
            $tmp_attachment = $current_attachments[rand(0, count($current_attachments) - 1)];
            $max_rating = $tmp_attachment->rating;
            $current_attachment = clone $tmp_attachment;
        }
        if (isset($current_attachment))
            break;
    }
    if (empty($current_attachment))
        return FALSE;
    $mysqli->query("INSERT INTO posted_content (id, group_id, post_id_vk, type_id) VALUES (NULL, {$current_attachment->group_id}, '{$current_attachment->post_id_vk}', {$type_id})");
    $hash = ($current_attachment->type === 'photo' ? md5_file(get_photo_url($current_attachment->object)) : '');
    $mysqli->query("INSERT INTO used_attachments (id, id_vk, hash) VALUES (NULL, '".$current_attachment->type.$current_attachment->object->owner_id.'_'.$current_attachment->object->id."', '$hash')");
    if ($current_attachment->type === 'photo')
        return upload_image_to_group_wall($current_attachment->object);
    return $current_attachment->object;
}

function telegram_api_method($method, $params = []) {
    $url = 'https://api.telegram.org/bot'.TELEGRAM_ACCESS_TOKEN.'/'.$method.'?'.http_build_query($params);

    return json_decode(file_get_contents($url));
}

function is_exists($value, $key, $table)
{
    return connect_to_database()->query("SELECT * FROM $table WHERE `$key` = '$value'")->num_rows > 0;
}

function is_exists_many(array $params, $table)
{
    $params_not_assoc = [];
    foreach ($params as $key => $value) {
        array_push($params_not_assoc, "`$key` = '$value'");
    }
    $query = "SELECT * FROM $table WHERE " . implode(' AND ', $params_not_assoc);
    return connect_to_database()->query($query)->num_rows > 0;
}