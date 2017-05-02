<?php

class actionGroupsGroupContent extends cmsAction {

    public $lock_explicit_call = true;

    public function run($group, $ctype_name = false){

        if (!$ctype_name) { cmsCore::error404(); }

        $content_controller = cmsCore::getController('content', $this->request);

        $ctype = $content_controller->model->getContentTypeByName($ctype_name);
        if (!$ctype || empty($ctype['is_in_groups'])) { cmsCore::error404(); }

        $content_controller->model->
                filterEqual('parent_id', $group['id'])->
                filterEqual('parent_type', 'group')->
                orderBy('date_pub', 'desc')->forceIndex('parent_id');

        $page_url = href_to($this->name, $group['slug'], array('content', $ctype_name));

        if (($this->cms_user->id == $group['owner_id']) || $this->cms_user->is_admin){
            $content_controller->model->disableApprovedFilter();
			$content_controller->model->disablePubFilter();
        }

        $html = $content_controller->renderItemsList($ctype, $page_url);

        $group['sub_title'] = empty($ctype['labels']['profile']) ? $ctype['title'] : $ctype['labels']['profile'];

        $this->cms_template->setPageTitle($group['sub_title'], $group['title']);
        $this->cms_template->setPageDescription($group['title'].' · '.$group['sub_title']);

        $this->cms_template->addBreadcrumb(LANG_GROUPS, href_to('groups'));
        $this->cms_template->addBreadcrumb($group['title'], href_to('groups', $group['slug']));
        $this->cms_template->addBreadcrumb($group['sub_title']);

        if (cmsUser::isAllowed($ctype['name'], 'add')) {

            $this->cms_template->addToolButton(array(
                'class' => 'add',
                'title' => sprintf(LANG_CONTENT_ADD_ITEM, $ctype['labels']['create']),
                'href'  => href_to($ctype['name'], 'add') . "?group_id={$group['id']}"
            ));

        }

        if (cmsUser::isAdmin()){
            $this->cms_template->addToolButton(array(
                'class' => 'page_gear',
                'title' => sprintf(LANG_CONTENT_TYPE_SETTINGS, mb_strtolower($ctype['title'])),
                'href'  => href_to('admin', 'ctypes', array('edit', $ctype['id']))
            ));
        }

        return $this->cms_template->render('group_content', array(
            'user'  => $this->cms_user,
            'group' => $group,
            'ctype' => $ctype,
            'html'  => $html
        ));

    }

}
