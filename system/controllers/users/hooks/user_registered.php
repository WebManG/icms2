<?php

class onUsersUserRegistered extends cmsAction {

    public function run($user){

        if ($user['nickname'] == 'user') {

            // Если ник временный, то делаем его более осмысленным, добавляя id
            $user['nickname'] = 'user'.$user['id'];

            // Обновляем профиль
            cmsCore::getModel('users')->updateUser($user['id'], $user);

        }

        return true;

    }

}
