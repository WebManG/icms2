<?php

class actionUsersProfileEdit extends cmsAction {

    public $lock_explicit_call = true;

    public function run($profile, $do=false, $param=false){

		if (!cmsUser::isLogged()) { cmsCore::error404(); }

        // если нужно, передаем управление другому экшену
        if ($do){
            $this->runAction('profile_edit_'.$do, array($profile) + array_slice($this->params, 2, null, true));
            return;
        }

        // проверяем наличие доступа
        if ($profile['id'] != $this->cms_user->id && !$this->cms_user->is_admin) { cmsCore::error404(); }

        // Получаем поля
        $content_model = cmsCore::getModel('content');
        $content_model->setTablePrefix('');
        $content_model->orderBy('ordering');
        $fields = $content_model->getContentFields('{users}', $profile['id']);

        // Строим форму
        $form = new cmsForm();

        // Разбиваем поля по группам
        $fieldsets = cmsForm::mapFieldsToFieldsets($fields, function($field, $user){

            // проверяем что группа пользователя имеет доступ к редактированию этого поля
            if ($field['groups_edit'] && !$user->isInGroups($field['groups_edit'])) { return false; }

            return true;

        });

        // Получаем адрес контроллера пользователей с учётом алиаса
        // для подстановки в пример адреса пользователя
        $users_controller_url = cmsCore::getControllerAliasByName('users');
        if (!$users_controller_url) { $users_controller_url = 'users'; }
        $users_controller_url = cmsConfig::get('host').'/'.$users_controller_url;

        // Получаем разрешение на изменение поля адреса профиля
        $is_slug_edit_allowed = ($this->cms_user->is_admin ||
                $this->options['slug_edit'] == 'allowed' ||
                ($this->options['slug_edit'] == 'oncefromid' && is_numeric($profile['slug'])));

        // Добавляем поля в форму
        foreach($fieldsets as $fieldset){

            $fieldset_id = $form->addFieldset($fieldset['title']);

            foreach($fieldset['fields'] as $field){

                // Изменяем правило проверки на уникальность на правило unique_exclude
                if ($field['options']['is_unique']) {
                    foreach ($field['handler']->rules as $key=>$rule) {
                        if ($rule[0] == 'unique') {
                            $field['options']['is_unique'] = 0;
                            $field['rules'][$key][0] = 'unique_exclude';
                            $field['rules'][$key][3] = $profile['id'];
                            $field['handler']->options['is_unique'] = 0;
                            $field['handler']->rules[$key][0] = 'unique_exclude';
                            $field['handler']->rules[$key][3] = $profile['id'];
                        }
                    }
                }

                // Обрабатываем поле, используемое для подстановки адреса профиля
                if ($field['name'] == $this->options['slug_field']) {

                    if ($is_slug_edit_allowed) {
                        // Добавляем подсказку про адрес страницы пользователя,
                        // если подстановка разрешена
                        $field['handler']->hint .= ( $field['hint'] ? '<br />' : '') .
                                sprintf(LANG_USER_URL_HINT, $users_controller_url);
                    } else {
                        // Отменяем вывод поля, если подстановка запрещена
                        continue;
                    }

                }

                // добавляем поле в форму
                $form->addField($fieldset_id, $field['handler']);

            }

        }

        // Добавляем поле адреса профиля, если нужно
        if ($is_slug_edit_allowed && $this->options['slug_field'] == 'slug') {

            if (!$fieldsets[0]['title']) {
                // Добавляем поле в первый набор полей, если он основной (с пустым именем)
                $fieldset_id = 0;
            } else {
                // Или содаём такой набор вверху формы, если его нет
                $fieldset_id = $form->addFieldsetToBeginning('');
            }

            $form->addField($fieldset_id, new fieldString('slug', array(
                'title' => LANG_USER_SLUG.' '.($this->options['slug_edit'] == 'oncefromid' ? LANG_USERS_PROFILE_SLUG_ONCEFROMID : ''),
                'hint' => LANG_USER_SLUG_HINT.'<br />'.sprintf(LANG_USER_URL_HINT, $users_controller_url),
                'rules' => array(
                    array('max_length', 64),
                    array('unique_exclude', '{users}', 'slug', $profile['id'])
            ))));

        }

        // Добавляем поле выбора часового пояса
        $fieldset_id = $form->addFieldset( LANG_TIME_ZONE );
        $form->addField($fieldset_id, new fieldList('time_zone', array(
            'default' => $this->cms_config->time_zone,
            'generator' => function($item){
                return cmsCore::getTimeZones();
            }
        )));

    	$form = cmsEventsManager::hook('user_profile_form', $form);

        // Форма отправлена?
        $is_submitted = $this->request->has('submit');

        if ($is_submitted){

            // Парсим форму и получаем поля записи
            $new = $form->parse($this->request, $is_submitted, $profile);
            $old = $profile;
            $profile = array_merge($profile, $new);

            // Проверям правильность заполнения
            $errors = $form->validate($this,  $profile);

            // Проверяем поле адреса профиля только если оно изменилось
            if ($profile[$this->options['slug_field']] != $old[$this->options['slug_field']]) {

                // Проверяем длину адреса, если адрес не цифровой
                if (!is_numeric($profile[$this->options['slug_field']]) && strlen($profile[$this->options['slug_field']])<4) {
                    $errors[$this->options['slug_field']] = sprintf(ERR_VALIDATE_MIN_LENGTH, 4);
                }

                // Проверяем формат адреса по регулярному выражению
                $result = cmsController::validate_userslug($profile[$this->options['slug_field']]);
                if ($result !== true) {
                    $errors[$this->options['slug_field']] = $result;
                }

                // Проверка адреса на запрещённые слова и на имена экшенов
                if (!cmsCore::getController('auth')->isSlugAllowed($profile[$this->options['slug_field']]) || $this->controller->isActionExists($profile[$this->options['slug_field']])){
                    $errors[$this->options['slug_field']] = sprintf(LANG_AUTH_RESTRICTED_SLUG, $profile[$this->options['slug_field']]);
                }

            }

            if (!$errors){
                $is_allowed = cmsEventsManager::hookAll('user_profile_update', $profile, true);
                if (is_array($is_allowed)) {
                    $errors = array();
                    foreach ($is_allowed as $error_list) {
                        if(is_array($error_list) && $error_list){
                            $errors = array_merge($error_list);
                        }
                    }
                }
            }

			list($profile, $old, $errors) = cmsEventsManager::hook('user_before_update', array($profile, $old, $errors));

            if (!$errors){

                // Проверяем разрешение на изменение поля адреса профиля
                if ($is_slug_edit_allowed) {

                    // Обновляем поле адреса профиля, если нужно
                    if ($this->options['slug_field'] != 'slug') {
                        $profile['slug'] = $profile[$this->options['slug_field']];
                    }

                } else {

                    // Если изменение адреса запрещено, то на всякий случай
                    // восстанавливаем прежний адрес (защита от подделки поля в форме)
                    $profile['slug'] = $old['slug'];

                }

                // Обновляем профиль и редиректим на его просмотр
                $this->model->updateUser($profile['id'], $profile);

                // Отдельно обновляем часовой пояс в сессии
                cmsUser::sessionSet('user_data:time_zone', $profile['time_zone']);

                // Постим уведомление о смене аватара в ленту
                if (!$this->model->isAvatarsEqual($new['avatar'], $old['avatar'])){
                    $activity_controller = cmsCore::getController('activity');
                    $activity_controller->deleteEntry($this->name, 'avatar', $profile['id']);
					if (!empty($new['avatar'])){

						$activity_controller->addEntry($this->name, 'avatar', array(
							'user_id'       => $profile['id'],
                            'subject_title' => $profile['nickname'],
                            'subject_id'    => $profile['id'],
                            'subject_url'   => href_to_rel('users', $profile['slug']),
                            'is_private'    => 0,
                            'group_id'      => null,
                            'images'        => array(
                                array(
                                    'url' => href_to_rel('users', $profile['slug']),
                                    'src' => html_image_src($new['avatar'], 'normal')
                                )
                            ),
                            'images_count'  => 1
                        ));
					}
                }

                cmsUser::addSessionMessage(LANG_SUCCESS_MSG, 'success');

                $this->redirectTo('users', $profile['slug']);

            }

            if ($errors){
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }

        }

        return $this->cms_template->render('profile_edit', array(
            'do'      => 'edit',
            'id'      => $profile['id'],
            'slug'    => ( isset($old['slug']) ? $old['slug'] : $profile['slug'] ),
            'profile' => $profile,
            'form'    => $form,
            'errors'  => isset($errors) ? $errors : false
        ));

    }

}
