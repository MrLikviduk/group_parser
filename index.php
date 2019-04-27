<?php
require_once 'config.php';
require_once 'api.php';
if (!isset($_GET['access_key']) || $_GET['access_key'] !== ACCESS_KEY_TO_PANEL) {
    die('Access denied');
}
$types = $mysqli->query("SELECT * FROM types")->fetch_all(MYSQLI_ASSOC);
if (isset($_POST['group_id_vk_to_add'])) {
    $group_id = array_pop(explode('/', trim($_POST['group_id_vk_to_add'])));
    $type_id = (int)$_POST['type_id'];
    $request = api_method('groups.getById', ['group_id' => $group_id, 'access_token' => USER_ACCESS_TOKEN]);
    if (isset($request->error) && $request->error->error_code == 100) {
        $group_id = str_replace('public', 'club', $group_id);
        $request = api_method('groups.getById', ['group_id' => $group_id]);
    }
    if (!isset($request->error)) {
        $group = $request->response[0];
        $group_id = $group->id;
        if (!is_exists_many(['id_vk' => $group_id, 'type_id' => $type_id], 'groups') && $group_id != GROUP_ID) {
            $mysqli->query("INSERT INTO groups (id, id_vk, type_id) VALUES (NULL, '$group_id', $type_id)");
            header("Location: " . $_SERVER['REQUEST_URI']);
        }
    }
}
if (isset($_POST['group_id_to_delete'])) {
    $group_id = $_POST['group_id_to_delete'];
    if (is_exists_many(['id' => $group_id], 'groups')) {
        $mysqli->query("DELETE FROM groups WHERE id = '$group_id'");
        header("Location: " . $_SERVER['REQUEST_URI']);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <script src="bootstrap/js/bootstrap.min.js"></script>
    <title>Panel</title>
    <style>
        h2 {
            margin: 30px auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Добро пожаловать в админ-панель!</h2>
    <br>
    Список групп:
    <? foreach ($types as $type) { ?>
        <h3 style="margin-top: 30px"><?= $type['name'] ?>:</h3>
        <form method="post" action="">
            <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
            <input type="text" name="group_id_vk_to_add" class="form-control my-1"
                   placeholder="Вставьте ссылку на группу">
            <button class="btn btn-success my-1">Добавить</button>
        </form>
        <?php
        $groups = $mysqli->query("SELECT * FROM groups WHERE type_id = " . $type['id'])->fetch_all(MYSQLI_ASSOC);
        $group_ids = [];
        foreach ($groups as $group) {
            array_push($group_ids, $group['id_vk']);
        }
        $group_ids = implode(',', $group_ids);
        $group_names = api_method('groups.getById', [
            'group_ids' => $group_ids,
            'access_token' => USER_ACCESS_TOKEN
        ])->response;
        for ($i = 0; $i < count($groups); $i++) {
            $groups[$i]['name'] = $group_names[$i]->name;
        }
        ?>
        <ul>
            <? foreach ($groups as $group) { ?>
                <li class="my-2">
                    <a href="https://vk.com/club<?= $group['id_vk'] ?>" target="_blank">
                        <?= $group['name'] ?>
                    </a>
                    <form action="" method="post" class="d-inline-block mx-2">
                        <input type="hidden" name="group_id_to_delete" value="<?= $group['id'] ?>">
                        <button class="btn btn-danger py-0" onclick="return confirm('Вы действительно хотите удалить группу?')">Удалить</button>
                    </form>
                </li>
            <? } ?>
        </ul>
    <? } ?>
</div>
</body>
</html>