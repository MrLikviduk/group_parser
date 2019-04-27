<?php

require_once 'config.php';
require_once 'api.php';

if (!isset($_REQUEST)) {
    return;
}

$data = json_decode(file_get_contents('php://input'));

switch ($data->type) {
    case 'confirmation':
        echo CONFIRMATION_TOKEN;
        break;
    case 'group_join':
    case 'group_leave':
        $user = api_method('users.get', [
            'user_ids' => $data->object->user_id,
            'access_token' => USER_ACCESS_TOKEN
        ])->response[0];
        $text = $user->first_name . ' ' . $user->last_name . ' ' . ($data->type === 'group_join' ? 'вступил в группу' : 'вышел из группы') . ".\n";
        $group = api_method('groups.getById', [
            'group_id' => GROUP_ID,
            'fields' => 'members_count',
            'access_token' => USER_ACCESS_TOKEN
        ])->response[0];
        $text .= 'Текущее кол-во подписчиков: ' . $group->members_count . '.';
        telegram_api_method('sendMessage', [
            'text' => $text,
            'chat_id' => TELEGRAM_CHAT_ID
        ]);
        echo 'ok';
        break;
    default:
        echo 'ok';
        break;
}