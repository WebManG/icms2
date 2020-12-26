<?php

class fieldParent extends cmsFormField {

    public $title         = LANG_PARSER_PARENT;
    public $is_public     = false;
    public $sql           = 'varchar(1024) NULL DEFAULT NULL';
    public $allow_index   = false;
    public $var_type      = 'string';
    public $filter_type   = 'str';
    protected $input_action = 'bind';
    private $parent_ctype_name = false;
    private $parent_items = null;

	public function __construct($name, $options = false) {

        parent::__construct($name, $options);

        $this->setParentContentTypeName();
    }

	private function setParentContentTypeName(){

		preg_match('/parent_([a-z0-9\-\_]+)_id/i', $this->name, $matches);

        if (!empty($matches[1])){
            $this->parent_ctype_name = $matches[1];
        }
	}

    public function setItem($item) {

        parent::setItem($item);

        if(!empty($item['ctype']['name'])){
            $this->item['ctype_name'] = $item['ctype']['name'];
        }

        return $this;
    }

    public function getStringValue($value){

        if (!$value){
            return '';
        }

        $parent_items = $this->getParentItems();

        if (!$parent_items){
            return '';
        }

		$result = [];

		foreach($parent_items as $parent_item) {
			$result[] = $parent_item['title'];
		}

		return $result ? implode(', ', $result) : '';
    }

    public function parseTeaser($value){
        return $this->parse($value, true);
    }

    public function parse($value, $is_teaser = false){

        if (!$value){
            return '';
        }

        $parent_items = $this->getParentItems(!$is_teaser);

        if (!$parent_items){
            return '';
        }

		$result = [];

		foreach($parent_items as $parent_item) {
			$parent_url = href_to($this->parent_ctype_name, $parent_item['slug'].'.html');
			$result[] = '<a href="'.$parent_url.'">'.$parent_item['title'].'</a>';
		}

		return $result ? implode(', ', $result) : '';
    }

    public function getInput($value) {

		$this->title = $this->element_title;

		$parent_items = false;
        $auth_user_id = cmsUser::get('id');

        $author_id = isset($this->item['user_id']) ? $this->item['user_id'] : $auth_user_id;

        if ($value){
            $parent_items = $this->getParentItemsByIds($value);
        } else {
            $parent_items = $this->getParentItems();
        }

		$perm = cmsUser::getPermissionValue($this->item['ctype_name'], 'bind_to_parent');
		$is_allowed_to_bind = ($perm && (
								($perm == 'all_to_all') || ($perm == 'all_to_own') || ($perm == 'all_to_other') ||
								($perm == 'own_to_all' && $author_id == $auth_user_id) ||
								($perm == 'own_to_other' && $author_id == $auth_user_id) ||
								($perm == 'own_to_own' && $author_id == $auth_user_id) ||
								($perm == 'other_to_own' && $author_id != $auth_user_id) ||
								($perm == 'other_to_other' && $author_id != $auth_user_id) ||
								($perm == 'other_to_all' && $author_id != $auth_user_id)
							)) || cmsUser::isAdmin();

        $allowed_to_unbind_perm = cmsUser::getPermissionValue($this->item['ctype_name'], 'bind_off_parent');
        if(cmsUser::isAdmin()){
            $allowed_to_unbind_perm = 'all';
        }

        if(!$parent_items && !$is_allowed_to_bind){
            return '';
        }

        return cmsTemplate::getInstance()->renderFormField($this->class, array(
			'ctype_name'         => $this->parent_ctype_name,
            'child_ctype_name'   => $this->item ? $this->item['ctype_name'] : false,
            'parent_ctype'       => $this->parent_ctype_name ? cmsCore::getModel('content')->getContentTypeByName($this->parent_ctype_name) : [],
            'field'              => $this,
            'input_action'       => $this->input_action,
            'value'              => $value,
            'field_item'         => $this->item,
            'items'              => $parent_items,
            'auth_user_id'       => $auth_user_id,
            'allowed_to_unbind_perm' => $allowed_to_unbind_perm,
            'is_allowed_to_bind' => $is_allowed_to_bind
        ));
    }

    public function getFilterInput($value) {

        if(!$this->show_filter_input_title){
            $this->title = false;
        }

        $this->input_action = 'select';

        if ($value){
            $parent_items = $this->getParentItemsByIds($value);
        } else {
            $parent_items = [];
        }

        return cmsTemplate::getInstance()->renderFormField($this->class, array(
			'ctype_name'         => $this->parent_ctype_name,
            'child_ctype_name'   => $this->item ? $this->item['ctype_name'] : false,
            'parent_ctype'       => $this->parent_ctype_name ? cmsCore::getModel('content')->getContentTypeByName($this->parent_ctype_name) : [],
            'field'              => $this,
            'input_action'       => $this->input_action,
            'value'              => $value,
            'field_item'         => [],
            'items'              => $parent_items,
            'auth_user_id'       => cmsUser::get('id'),
            'allowed_to_unbind_perm' => true,
            'is_allowed_to_bind' => true
        ));
    }

    public function applyFilter($model, $values) {

        $ids = $this->idsStringToArray($values);
        if (!$ids) { return parent::applyFilter($model, $values); }

        $alias_name = 'rr_'.$this->name;

        $model->joinInner('content_relations_bind', $alias_name, $alias_name.'.child_item_id = i.id AND '.$alias_name.'.child_ctype_id '.($this->ctype_id ? '='.$this->ctype_id : 'IS NULL'));

        return $model->filterIn($alias_name.'.parent_item_id', $ids);
    }

	private function idsStringToArray($ids_list){

        $ids = array();

        if (!$ids_list) { return $ids; }

        foreach(explode(',', $ids_list) as $id){
            if (!is_numeric($id)) { continue; }
            $ids[] = trim($id);
        }

		return $ids;
	}

    private function getParentItemsByIds($ids_list){

        $ids = $this->idsStringToArray($ids_list);
        if (!$ids) { return false; }

        $content_model = cmsCore::getModel('content');
        $content_model->filterIn('id', $ids);

        return $content_model->getContentItems($this->parent_ctype_name);
    }

    private function getParentItems($store_result = true){

        if($this->parent_items !== null){
            return $this->parent_items;
        }

		if (!$this->parent_ctype_name) { return false; }

		if (empty($this->item['id'])) { return false; }

		$content_model = cmsCore::getModel('content');

		$ctypes = $content_model->getContentTypes();

		$parent_ctype = $child_ctype = false;

		foreach($ctypes as $ctype){
			if ($ctype['name'] == $this->parent_ctype_name){
				$parent_ctype = $ctype;
			}
			if ($ctype['name'] == $this->item['ctype_name']){
				$child_ctype = $ctype;
			}
		}

		if (!$parent_ctype) { return false; }
		if (!$child_ctype) {
            if (cmsController::enabled($this->item['ctype_name'])){
                $child_ctype = array(
                    'name'       => $this->item['ctype_name'],
                    'controller' => $this->item['ctype_name'],
                    'id'         => null
                );
            } else {
                return false;
            }
        } else {
            $child_ctype['controller'] = 'content';
        }

        $filter =  "r.parent_ctype_id = {$parent_ctype['id']} AND ".
                   "r.child_item_id = {$this->item['id']} AND ".
                   'r.child_ctype_id '.($child_ctype['id'] ? '='.$child_ctype['id'] : 'IS NULL' ).' AND '.
                   "r.parent_item_id = i.id AND r.target_controller = '{$child_ctype['controller']}'";

        $content_model->join('content_relations_bind', 'r', $filter);

		$items = $content_model->selectList([
            'i.id'      => 'id',
            'i.user_id' => 'user_id',
            'i.title'   => 'title',
            'i.slug'    => 'slug',
        ], true)->getContentItems($this->parent_ctype_name);

        if ($items){
            foreach($items as $id=>$item){
                $items[$id]['ctype_name'] = $this->parent_ctype_name;
            }
        }

        if($store_result){
            $this->parent_items = $items;
        }

        return $items;
    }

}
