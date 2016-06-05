<?php

class formUsersField extends cmsForm {

    public function init($do) {

        return array(
            'basic' => array(
                'type' => 'fieldset',
                'childs' => array(
                    new fieldString('name', array(
                        'title' => LANG_SYSTEM_NAME,
                        'rules' => array(
                            array('required'),
                            array('sysname'),
                            array('max_length', 20),
                            $do == 'add' ? array('unique_field') : false
                        )
                    )),
                    new fieldString('title', array(
                        'title' => LANG_CP_FIELD_TITLE,
                        'rules' => array(
                            array('required'),
                            array('max_length', 100)
                        )
                    )),
                    new fieldString('hint', array(
                        'title' => LANG_CP_FIELD_HINT,
                        'rules' => array(
                            array('max_length', 255)
                        )
                    )),
                )
            ),
            'type' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_TYPE,
                'childs' => array(
                    new fieldList('type', array(
                        'default' => 'string',
                        'generator' => function() {
                            $field_types = array();
                            $field_types = cmsForm::getAvailableFormFields();
                            return $field_types;
                        }
                    ))
                )
            ),
            'group' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_FIELDSET,
                'childs' => array(
                    new fieldList('fieldset', array(
                        'title' => LANG_CP_FIELD_FIELDSET_SELECT,
                        'default' => '',
                        'generator' => function($field) {
                            $model = cmsCore::getModel('content');
                            $model->setTablePrefix('');
                            $fieldsets = $model->getContentFieldsets('{users}');
                            $items = array(''  => '-');
                            foreach($fieldsets as $fieldset) { $items[$fieldset] = $fieldset; }
                            return $items;
                        }
                    )),
                    new fieldString('new_fieldset', array(
                        'title' => LANG_CP_FIELD_FIELDSET_ADD,
                        'rules' => array(
                            array('max_length', 100)
                        )
                    )),
                )
            ),
            'visibility' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_VISIBILITY,
                'childs' => array(
                    new fieldCheckbox('is_in_item', array(
                        'title' => LANG_CP_FIELD_IN_PROFILE,
                        'default' => true
                    )),
                    new fieldCheckbox('is_in_filter', array(
                        'title' => LANG_CP_FIELD_IN_FILTER,
                    ))
                )
            ),
            'labels' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_LABELS,
                'childs' => array(
                    new fieldList('options:label_in_item', array(
                        'title' => LANG_CP_FIELD_LABELS_IN_ITEM,
                        'default' => 'left',
                        'items' => array(
                            'left' => LANG_CP_FIELD_LABEL_LEFT,
                            'top' => LANG_CP_FIELD_LABEL_TOP,
                            'none' => LANG_CP_FIELD_LABEL_NONE
                        )
                    )),
                )
            ),
            'format' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_FORMAT,
                'childs' => array(
                    new fieldCheckbox('options:is_required', array(
                        'title' => LANG_VALIDATE_REQUIRED,
                    )),
                    new fieldCheckbox('options:is_digits', array(
                        'title' => LANG_VALIDATE_DIGITS,
                    )),
                    new fieldCheckbox('options:is_alphanumeric', array(
                        'title' => LANG_VALIDATE_ALPHANUMERIC,
                    )),
                    new fieldCheckbox('options:is_email', array(
                        'title' => LANG_VALIDATE_EMAIL,
                    )),
                    new fieldCheckbox('options:is_unique', array(
                        'title' => LANG_VALIDATE_UNIQUE,
                    )),
                )
            ),
            'values' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_VALUES,
                'childs' => array(
                    new fieldText('values', array(
                        'size' => 8
                    ))
                )
            ),
//            'privacy' => array(
//                'type' => 'fieldset',
//                'title' => LANG_CP_FIELD_PRIVACY,
//                'childs' => array(
//                    new fieldCheckbox('is_private', array(
//                        'title' => LANG_CP_FIELD_PRIVATE,
//                    )),
//                )
//            ),
            'read_access' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_GROUPS_READ,
                'childs' => array(
                    new fieldListGroups('groups_read', array(
                        'show_all' => true
                    ))
                )
            ),
            'edit_access' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_GROUPS_EDIT,
                'childs' => array(
                    new fieldListGroups('groups_edit', array(
                        'show_all' => true
                    ))
                )
            ),
            'filter_access' => array(
                'type' => 'fieldset',
                'title' => LANG_CP_FIELD_IN_FILTER,
                'childs' => array(
                    new fieldListGroups('filter_view', array(
                        'show_all' => true
                    ))
                )
            )
        );

    }

}