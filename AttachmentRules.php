<?php
/**
 * Created by PhpStorm.
 * User: Виталий
 * Date: 22.09.2018
 * Time: 21:08
 */

spl_autoload_register(function ($class_name) {
    require_once $class_name . '.php';
});

class AttachmentRules
{
    public $type;
    public $legal_numbers;
    public $fields;
    public $count = 0;

    public function __construct($type, array $legal_numbers, $fields = [])
    {
        $this->type = $type;
        $this->legal_numbers = $legal_numbers;
        $this->fields = $fields;
    }

    public function comply_rules($attachment, $count = -1, $strict = FALSE) {
        $attachment = json_decode(json_encode($attachment), TRUE);
        $rules = $this;
        if ($count >= 0 && !in_array($count, $rules->legal_numbers))
            return FALSE;
        if ($attachment['type'] !== $rules->type)
            return FALSE;
        $attachment = $attachment[$attachment['type']];
        foreach ($rules->fields as $key => $value) {
            if ($strict === TRUE) {
                if ($attachment[$key] !== $value)
                    return FALSE;
            }
            else
                if ($attachment[$key] != $value)
                    return FALSE;
        }
        return TRUE;
    }

    public function reset() {
        $this->count = 0;
    }
}