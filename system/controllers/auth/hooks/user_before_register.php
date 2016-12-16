<?php

class onAuthUserBeforeRegister extends cmsAction {

    public function run($data){

        list($user, $errors) = $data;

        // Подставляем временное значение никнейма, если он пустой
        if (!$user['nickname']) { $user['nickname'] = 'user'; }

        return array($user, $errors);

    }

}
