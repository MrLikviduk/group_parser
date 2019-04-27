<?php
/**
 * Created by PhpStorm.
 * User: Виталий
 * Date: 21.09.2018
 * Time: 14:53
 */

spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

class Attachment
{
    public $type;
    public $object;
    public $rules;
    public $rating;
    public $post_id_vk;
    public $group_id;

    public function __construct($type, $object, AttachmentRules $rules, $rating, $post_id_vk, $group_id)
    {
        $this->type = $type;
        $this->object = $object;
        $this->rules = $rules;
        $this->rating = $rating;
        $this->post_id_vk = $post_id_vk;
        $this->group_id = $group_id;
    }

    public function attach_object($type, $object)
    {
        $object = json_decode(json_encode($object), TRUE);
        $attachment = ['type' => $type, $type => $object];
        if ($this->rules->comply_rules($attachment)) {
            $this->type = $type;
            $this->object = $object;
            return TRUE;
        }
        return FALSE;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}