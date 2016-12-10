<?php

class onUsersUserBeforeUpdate extends cmsAction {

    public function run($data){

        list($user, $old, $errors) = $data;

        // Возвращаем прежнее значение никнейма, если он пустой
        //if (!$user['nickname']) { $user['nickname'] = $old['nickname']; }

        // Или выдаём сообщение о недопустимости пустого ника
        if (!$user['nickname']) { $errors['nickname'] = ERR_VALIDATE_REQUIRED; }

        return array($user, $old, $errors);

    }

}
