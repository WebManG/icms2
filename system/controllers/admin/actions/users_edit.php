<?php

class actionAdminUsersEdit extends cmsAction {

    public function run($id){

        if (!$id) { cmsCore::error404(); }

        $users_model = cmsCore::getModel('users');
        $user = $users_model->getUser($id);
        if (!$user) { cmsCore::error404(); }

        $form = $this->getForm('user', array('edit'));

        // Добавляем поле адреса профиля
        $form->addFieldToBeginning(0, new fieldString('slug', array(
                    'title' => LANG_SLUG,
                    'hint' => LANG_CP_USER_SLUG_HINT,
                    'rules' => array(
                        array('unique_exclude', '{users}', 'slug', $id)
                    )
        )));

        // Добавляем поле для подстановки адреса, если нужно
        $slug_field = cmsCore::getController('users')->options['slug_field'];
        $fields = cmsCore::getModel('content')->
                setTablePrefix('')->
                filterEqual('name', $slug_field)->
                getContentFields('{users}');

        if (isset($fields) && $slug_field != 'slug' && $slug_field != 'nickname') {

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

        if ($is_submitted){

            cmsCore::loadControllerLanguage('auth');

            $user = $form->parse($this->request, $is_submitted);

            if (!$user['is_locked']){
                $user['lock_until'] = null;
                $user['lock_reason'] = null;
            }

            $errors = $form->validate($this,  $user);

            if ($user['slug']){
                // Проверяем slug на совпадение с экшенами
                if (cmsCore::getController('users')->isActionExists($user['slug'])){
                    $errors['slug'] = sprintf(LANG_AUTH_RESTRICTED_SLUG, $user['slug']);
                }
            }

            if (!$errors){

                if (!$user['slug']){
                    // Подставляем id в поле логина при пустом логине
                    $user['slug'] = $id;
                }

                $result = $users_model->updateUser($id, $user);

                if ($result['success']){

                    $back_url = $this->request->get('back');

                    if ($back_url){
                        $this->redirect($back_url);
                    } else {
                        $this->redirectToAction('users');
                    }

                } else {
                    $errors = $result['errors'];
                }

            }

            if ($errors){
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }

        }

        return cmsTemplate::getInstance()->render('user', array(
            'do' => 'edit',
            'user' => $user,
            'form' => $form,
            'errors' => isset($errors) ? $errors : false
        ));

    }

}
