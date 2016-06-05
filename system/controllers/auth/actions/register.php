<?php
class actionAuthRegister extends cmsAction {

    public function run(){

        if (cmsUser::isLogged() && !cmsUser::isAdmin()) { $this->redirectToHome(); }

        $users_model = cmsCore::getModel('users');
        $form = $this->getForm('registration');

        //
        // Добавляем поле для кода приглашения,
        // если регистрация доступна только по приглашениям
        //
        if ($this->options['is_reg_invites']){

            $fieldset_id = $form->addFieldsetToBeginning(LANG_REG_INVITED_ONLY);

            $form->addField($fieldset_id, new fieldString('inv', array(
                'title' => LANG_REG_INVITE_CODE,
                'rules' => array(
                    array('required'),
                    array('min_length', 10),
                    array('max_length', 10),
                )
            )));

        }

        //
        // Добавляем поле выбора группы,
        // при наличии публичных групп
        //
        $public_groups = $users_model->getPublicGroups();

        if ($public_groups) {

            $pb_items = array();
            foreach($public_groups as $pb) { $pb_items[ $pb['id'] ] = $pb['title']; }

            $form->addFieldToBeginning('basic',
                new fieldList('group_id', array(
                        'title' => LANG_USER_GROUP,
                        'items' => $pb_items
                    )
                )
            );

        }

        // Получаем адрес контроллера пользователей с учётом алиаса
        // для подстановки в пример адреса пользователя
        $users_controller_url = cmsCore::getControllerAliasByName('users');
        if (!$users_controller_url) { $users_controller_url = 'users'; }
        $users_controller_url = cmsConfig::get('host').'/'.$users_controller_url;

        //
        // Добавляем поле для адреса профиля пользователя, если нужно
        //
        if ($this->options['reg_user_slug'] == 'slug'){

            $form->addFieldToBeginning('basic', new fieldString('slug', array(
                'title' => LANG_USER_SLUG,
                'hint' => LANG_USER_SLUG_HINT.'<br />'.sprintf(LANG_USER_URL_HINT, $users_controller_url),
                'rules' => array(
                    array('min_length', 4),
                    array('max_length', 64),
                    array('userslug'),
                    array('required'),
                    array('unique', '{users}', 'slug')
            ))));

        }

        //
        // Добавляем в форму обязательные поля профилей
        //

        // Получаем список обязательных полей
        $fields = cmsCore::getModel('content')->
                setTablePrefix('')->
                getRequiredContentFields('users');

        // Разбиваем поля по группам
        $fieldsets = cmsForm::mapFieldsToFieldsets($fields);

        // Добавляем поля в форму
        foreach($fieldsets as $fieldset){

            $fieldset_id = $form->addFieldset($fieldset['title']);

            // Сортируем поля основного набора (с пустым именем) в обратном порядке,
            // так как их добавление будет в начало набора
            if (!$fieldset['title']) { krsort($fieldset['fields']); }

            foreach($fieldset['fields'] as $field){

                if ($field['name'] == $this->options['reg_user_slug']) {
                    // Добавляем к этому полю подсказку про адрес страницы пользователя
                    $field['handler']->hint .= ( $field['hint'] ? '<br />' : '') .
                            sprintf(LANG_USER_URL_HINT, $users_controller_url);
                }

                if (!$fieldset['title']) {
                    // Добавляем это поле для авторизации в начало формы
                    $form->addFieldToBeginning('basic', $field['handler']);
                } else {
                    // Добавляем это поле в свою группу
                    $form->addField($fieldset_id, $field['handler']);
                }
            }

        }

        //
        // Добавляем галку согласия с правилами сайта,
        // если заполнено поле ссылки на "Правила"
        //
        if ($this->options['rules_url']){

            $fieldset_id = $form->addFieldset(LANG_REG_RULES_SET);

            $form->addField($fieldset_id, new fieldCheckbox('rules_agree', array(
                'title' => sprintf(LANG_REG_RULES_AGREE, $this->options['rules_url']),
                'default' => false,
                'rules' => array(
                    array('required'),
                )
            )));

        }

    	$form = cmsEventsManager::hook('user_register_form', $form);

        $user = array();

        if ($this->request->hasInQuery('inv')){
            $user['inv'] = $this->request->get('inv','');
        }

        $is_submitted = $this->request->has('submit');

        if ($is_submitted){

            if (!$this->options['is_reg_enabled']){
                cmsCore::error404();
            }

            $errors = false;
            $is_captcha_valid = true;

            //
            // Проверяем капчу
            //
            if ($this->options['reg_captcha']){

                $is_captcha_valid = cmsEventsManager::hook('captcha_validate', $this->request);

                if (!$is_captcha_valid){
                    $errors = true;
                    cmsUser::addSessionMessage(LANG_CAPTCHA_ERROR, 'error');
                }

            }

            //
            // Парсим и валидируем форму
            //
            if (!$errors){

                $user = $form->parse($this->request, $is_submitted);

				$user['groups'] = array();

				if (!empty($this->options['def_groups'])){
					$user['groups'] = $this->options['def_groups'];
				}

                if (isset($user['group_id'])) {
					if (!in_array($user['group_id'], $user['groups'])){
						$user['groups'][] = $user['group_id'];
					}
				}

                //
                // убираем поля которые не относятся к выбранной пользователем группе
                //
                foreach($fieldsets as $fieldset){
                    foreach($fieldset['fields'] as $field){

                        if (!$field['groups_edit']) { continue; }
                        if (in_array(0, $field['groups_edit'])) { continue; }

                        if (!in_array($user['group_id'], $field['groups_edit'])){
                            $form->disableField($field['name']);
                            unset($user[$field['name']]);
                        }

                    }
                }

                $errors = $form->validate($this,  $user);

            }

            if (!$errors){

                //
                // проверяем код приглашения
                //
                if ($this->options['is_reg_invites']){
                    $invite = $this->model->getInviteByCode($user['inv']);
                    if (!$invite) {
                        $errors['inv'] = LANG_REG_WRONG_INVITE_CODE;
                    } else {
                        if ($this->options['is_invites_strict'] && ($invite['email'] != $user['email'])) {
                            $errors['inv'] = LANG_REG_WRONG_INVITE_CODE_EMAIL;
                        } else {
                            $user['inviter_id'] = $invite['user_id'];
                        }
                    }
                }

                //
                // Проверяем допустимость e-mail, логина, никнейма и IP
                //
                if (!$this->isEmailAllowed($user['email'])){
                    $errors['email'] = sprintf(LANG_AUTH_RESTRICTED_EMAIL, $user['email']);
                }

                if ($this->options['reg_user_slug'] != 'id'){
                    if (!$this->isSlugAllowed($user[$this->options['reg_user_slug']]) || cmsCore::getController('users')->isActionExists($user[$this->options['reg_user_slug']])){
                        $errors[$this->options['reg_user_slug']] = sprintf(LANG_AUTH_RESTRICTED_SLUG, $user[$this->options['reg_user_slug']]);
                    }
                }

                // Добавляем поле никнейма при регистрации без него
                if (!isset($user['nickname'])) { $user['nickname'] = ''; }

                if (!$this->isNameAllowed($user['nickname'])){
                    $errors['nickname'] = sprintf(LANG_AUTH_RESTRICTED_NAME, $user['nickname']);
                }

                if (!$this->isIPAllowed(cmsUser::get('ip'))){
                    cmsUser::addSessionMessage(sprintf(LANG_AUTH_RESTRICTED_IP, cmsUser::get('ip')), 'error');
                    $errors = true;
                }

            }

			list($user, $errors) = cmsEventsManager::hook('user_before_register', array($user, $errors));

            if (!$errors){

                unset($user['inv']);

                //
                // Блокируем пользователя, если включена верификация e-mail
                //
                if ($this->options['verify_email']){
                    $user = array_merge($user, array(
                        'is_locked' => true,
                        'lock_reason' => LANG_REG_CFG_VERIFY_LOCK_REASON,
                        'pass_token' => string_random(32, $user['email']),
                        'date_token' => ''
                    ));
                }

                $result = $users_model->addUser($user);

                if ($result['success']){

                    $user['id'] = $result['id'];

                    cmsUser::addSessionMessage(LANG_REG_SUCCESS, 'success');

                    cmsUser::setUPS('first_auth', 1, $user['id']);

                    // отправляем письмо верификации e-mail
                    if ($this->options['verify_email']){

                        $messenger = cmsCore::getController('messages');
                        $to = array('email' => $user['email'], 'name' => $user['nickname']);
                        $letter = array('name' => 'reg_verify');

                        $messenger->sendEmail($to, $letter, array(
                            'nickname' => $user['nickname'],
                            'page_url' => href_to_abs('auth', 'verify', $user['pass_token']),
                            'valid_until' => html_date(date('d.m.Y H:i', time() + ($this->options['verify_exp'] * 3600)), true),
                        ));

                        cmsUser::addSessionMessage(sprintf(LANG_REG_SUCCESS_NEED_VERIFY, $user['email']), 'info');

                    } else {

						cmsEventsManager::hook('user_registered', $user);

					}

					// авторизуем пользователя автоматически
					if ($this->options['reg_auto_auth']){

                        $logged_id = cmsUser::login(
                                $user[$this->options['auth_by']],
                                $user['password1'],
                                false,
                                $this->options['auth_by']
                        );

						if ($logged_id){

							cmsEventsManager::hook('auth_login', $logged_id);

						}

					}

                    $back_url = cmsUser::sessionGet('auth_back_url') ?
                                cmsUser::sessionGet('auth_back_url', true) :
                                false;

                    if ($back_url){
                        $this->redirect($back_url);
                    } else {
                        $this->redirect($this->getAuthRedirectUrl($this->options['first_auth_redirect']));
                    }

                } else {
                    $errors = $result['errors'];
                }

            }

            if ($errors && $is_captcha_valid){
                cmsUser::addSessionMessage(LANG_FORM_ERRORS, 'error');
            }

        }

        // Капча
        if ($this->options['reg_captcha']){
            $captcha_html = cmsEventsManager::hook('captcha_html');
        }

        // запоминаем откуда пришли на регистрацию
        if(empty($errors) && $this->options['first_auth_redirect'] == 'none'){
            cmsUser::sessionSet('auth_back_url', $this->getBackURL());
        }

        return $this->cms_template->render('registration', array(
            'user'         => $user,
            'form'         => $form,
            'captcha_html' => isset($captcha_html) ? $captcha_html : false,
            'errors'       => isset($errors) ? $errors : false
        ));

    }

}
