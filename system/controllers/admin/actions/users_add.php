<?php

class actionAdminUsersAdd extends cmsAction {

    public function run($group_id=false){

        $users_model = cmsCore::getModel('users');
        $form = $this->getForm('user', array('add'));

        // Добавляем поле адреса профиля
        $form->addFieldToBeginning(0, new fieldString('slug', array(
                    'title' => LANG_SLUG,
                    'hint' => LANG_CP_USER_SLUG_HINT,
                    'rules' => array(
                        array('unique', '{users}', 'slug')
                    )
        )));

        // Добавляем поле для подстановки адреса, если нужно
        $slug_field = cmsCore::getController('auth')->options['reg_user_slug'];
        $fields = cmsCore::getModel('content')->
                setTablePrefix('')->
                filterEqual('name', $slug_field)->
                getContentFields('{users}');

        if (isset($fields) && $slug_field != 'slug' && $slug_field != 'id') {

            $slug_field_title = $fields[$slug_field]['title'];

            if (true) {
                $form->addFieldToBeginning(0, new fieldString($slug_field, array(
                    'title' => $slug_field_title,
                    'default' => false,
                    'rules' => array(
                        array('required'),
                    )
                )));
            }
        }

        // Добавляем поле никнейма
        $form->addFieldToBeginning(0, new fieldString('nickname', array(
                    'title' => LANG_NICKNAME,
                    'rules' => array(
                        array('required'),
                    )
        )));

        $is_submitted = $this->request->has('submit');

        $user = $form->parse($this->request, $is_submitted);

        if (!$is_submitted){
            $user['groups'] = array($group_id);
        }

        if ($is_submitted){

            $errors = $form->validate($this,  $user);

            if (mb_strlen($user['password1']) < 6) {
                $errors['password1'] = sprintf(ERR_VALIDATE_MIN_LENGTH, 6);
            }

            if ($user['slug']){
                // Проверяем slug на совпадение с экшенами
                if (cmsCore::getController('users')->isActionExists($user['slug'])){
                    $errors['slug'] = sprintf(LANG_AUTH_RESTRICTED_SLUG, $user['slug']);
                }
            }

            if (!$errors){

                $result = $users_model->addUser($user);

                if ($result['success']){
                    cmsUser::addSessionMessage(sprintf(LANG_CP_USER_CREATED, $user['nickname']), 'success');
                    $this->redirectToAction('users');
                } else {
                    $errors = $result['errors'];
                }

            }

            if ($errors){
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }

        }

        return cmsTemplate::getInstance()->render('user', array(
            'do' => 'add',
            'user' => $user,
            'form' => $form,
            'errors' => isset($errors) ? $errors : false
        ));

    }

}
