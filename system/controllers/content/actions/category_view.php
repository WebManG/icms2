<?php

class actionContentCategoryView extends cmsAction {

    private $is_remapped, $remap_redirect_ctype = false;

    public function __construct($controller, $params = array()) {

        parent::__construct($controller, $params);

        $this->is_remapped = $this->cms_core->uri_controller_before_remap ? true : false;

    }

    public function run() {

        list($ctype, $category, $slug) = $this->getCategoryAndSlugAndCtype();

        // Текущий набор
        $dataset = $this->request->get('dataset', '');

        // Это вывод на главной?
        $is_frontpage = $this->request->get('is_frontpage', false);

        // Номер страницы
        $page = $this->request->get('page', 1);

        $subcats = array();

        // Получаем список подкатегорий для текущей
        if ($ctype['is_cats']) {
            $current_cat_id = $category['id'] ? $category['id'] : 1;
            $subcats        = $this->model->filterIsNull('is_hidden')->getSubCategories($ctype['name'], $current_cat_id);
        }

        // Получаем список наборов
        $datasets = $this->getCtypeDatasets($ctype, array(
            'cat_id' => $category['id']
        ));

        // Если это не главная, но данный контент выводится на главной и сейчас
        // открыта индексная страница контента - редиректим на главную
        if (!$this->request->isAjax() && !$is_frontpage &&
                $this->cms_config->frontpage == "content:{$ctype['name']}" && $slug == 'index' &&
                !$dataset && $page == 1 && !$this->list_filter) {

            $query = $this->cms_core->uri_query;
            if ($query) {
                $this->redirect(href_to_home() . '?' . http_build_query($query));
            } else {
                $this->redirectToHome();
            }
        }

        // Если есть наборы, применяем фильтры текущего
        // иначе будем сортировать по дате создания
        $current_dataset = array();
        if ($datasets) {

            if ($dataset && empty($datasets[$dataset])) {
                return cmsCore::error404();
            }

            $keys = array_keys($datasets);
            $current_dataset = $dataset ? $datasets[$dataset] : $datasets[$keys[0]];

            $this->model->applyDatasetFilters($current_dataset);
            // устанавливаем максимальное количество записей для набора, если задано
            if (!empty($current_dataset['max_count'])) {
                $this->max_items_count = $current_dataset['max_count'];
            }
            // если набор всего один, например для изменения сортировки по умолчанию,
            // не показываем его на сайте
            if (count($datasets) == 1) {
                $current_dataset = array();
                $datasets        = false;
            }
        } elseif ($dataset) {
            return cmsCore::error404();
        }

        // Фильтр по категории
        if ($ctype['is_cats']) {
            if ($slug != 'index') {
                $this->model->filterCategory($ctype['name'], $category, $ctype['is_cats_recursive']);
            } elseif (!$ctype['is_cats_recursive']) {
                $this->model->filterCategory($ctype['name'], array('id' => 1));
            }
        }

        // Скрываем записи из скрытых родителей (приватных групп и т.п.)
        $this->model->enableHiddenParentsFilter();

        // Формируем базовые URL для страниц
        $base_url = ($this->cms_config->ctype_default && in_array($ctype['name'], $this->cms_config->ctype_default)) ? '' : $ctype['name'];

        $page_url = href_to($ctype['name']);

        if ($dataset) {
            $page_url .= '-' . $dataset;
        }

        if (!empty($category['slug'])) {

            $page_url .= '/' . $category['slug'];

            if (!$base_url) {
                $page_url = str_replace($ctype['name'] . '/', '', $page_url);
            }
        }

        if ($this->list_filter) {
            $page_url = [
                'base'        => $page_url . '/' . $this->list_filter['slug'],
                'filter_link' => $page_url . '/' . $this->list_filter['slug'],
                'cancel'      => $page_url
            ];
        }

        // если не на главной
        if (!$is_frontpage) {
            // canonical
            $this->cms_template->addHead('<link rel="canonical" href="' . $this->cms_config->host . (is_array($page_url) ? $page_url['base'] : $page_url) . '"/>');
            // если название переопределено, а мы по оригинальному адресу,
            // то редиректим со старого на новый адрес
            if ($this->remap_redirect_ctype) {
                $this->redirect(href_to($this->remap_redirect_ctype . ($dataset ? '-' . $dataset : ''), !empty($category['slug']) ? $category['slug'] : ''), 301);
            }
        }

        list($ctype, $category) = cmsEventsManager::hook("content_before_category", array($ctype, $category));
        list($ctype, $category) = cmsEventsManager::hook("content_{$ctype['name']}_before_category", array($ctype, $category));

        $items_list_html = '';
        $is_hide_items   = !empty($ctype['options']['is_empty_root']) && $slug == 'index';

        $list_styles = array();

        $current_style = '';
        if (!empty($ctype['options']['list_style'])) {
            if (is_array($ctype['options']['list_style'])) {
                $current_style = $ctype['options']['list_style'][0] ? '_' . $ctype['options']['list_style'][0] : '';
            } else {
                $current_style = '_' . $ctype['options']['list_style'];
            }
        }

        if (!empty($ctype['options']['list_style'])) {
            if (is_array($ctype['options']['list_style']) && count($ctype['options']['list_style']) > 1) {

                $style_key_name = $ctype['name'] . '_ctype_list_style';

                $ctype_list_style_preset = false;

                if (cmsUser::hasCookie($style_key_name)) {
                    $ctype_list_style_preset = cmsUser::getCookie($style_key_name, 'string', function ($cookie) {
                        return trim(strip_tags($cookie));
                    });

                    if($ctype_list_style_preset === 'default'){
                        $ctype_list_style_preset = '';
                    }
                }

                if ($this->cms_user->is_logged) {
                    $ctype_list_style_preset = cmsUser::getUPS($style_key_name);

                    if($ctype_list_style_preset === null){
                        $ctype_list_style_preset = '';
                    }
                }

                $current_style = $this->request->has('style') ?
                        $this->request->get('style', '') :
                        ($ctype_list_style_preset !== false ? $ctype_list_style_preset : $ctype['options']['list_style'][0]);

                if (!in_array($current_style, $ctype['options']['list_style'])) {
                    $current_style = $ctype['options']['list_style'][0];
                }

                // запоминаем стиль в куки
                if (!$this->cms_user->is_logged) {
                    cmsUser::setCookie($style_key_name, ($current_style === '' ? 'default' : $current_style), 604800);
                } else {
                    cmsUser::setUPS($style_key_name, $current_style);
                }

                $style_titles = array();
                if (!empty($ctype['options']['list_style_names'])) {
                    foreach ($ctype['options']['list_style_names'] as $list_style_names) {
                        $style_titles[$list_style_names['name']] = $list_style_names['value'];
                    }
                }

                foreach ($ctype['options']['list_style'] as $list_style) {
                    $list_styles[] = array(
                        'title' => (isset($style_titles[$list_style]) ? $style_titles[$list_style] : ''),
                        'style' => $list_style,
                        'url'   => (!empty($page_url['base']) ? $page_url['base'] : $page_url) . '?style=' . $list_style,
                        'class' => $list_style . ($current_style === $list_style ? ' active' : ''),
                    );
                }

                $ctype['options']['raw_list_style'] = $ctype['options']['list_style'];
                $ctype['options']['list_style']     = $current_style;
            }
        }

        $ctype['options']['cover_preset'] = '';
        if (!empty($ctype['options']['cover_sizes']) && !empty($ctype['options']['context_list_cover_sizes'])) {
            $cover_key = ltrim($current_style, '_');
            if (array_key_exists($cover_key, $ctype['options']['context_list_cover_sizes'])) {
                $ctype['options']['cover_preset'] = $ctype['options']['context_list_cover_sizes'][$cover_key];
            }
        }

        // кешируем
        cmsModel::cacheResult('current_ctype', $ctype);
        cmsModel::cacheResult('current_ctype_category', $category);
        cmsModel::cacheResult('current_ctype_dataset', $current_dataset);

        // Получаем HTML списка записей
        if (!$is_hide_items) {
            $items_list_html = $this->renderItemsList($ctype, $page_url, false, $category['id'], array(), $dataset);
        }

        $tpl_file = $this->cms_template->getTemplateFileName('controllers/content/category_view_' . $ctype['name'], true) ?
                'category_view_' . $ctype['name'] : 'category_view';

        $hooks_html = cmsEventsManager::hookAll("content_{$ctype['name']}_items_html", array('category_view', $ctype, $category, $current_dataset));

        $toolbar_html = cmsEventsManager::hookAll('content_toolbar_html', array($ctype['name'], $category, $current_dataset, array()));
        if ($toolbar_html) {
            $this->cms_template->addToBlock('before_body', html_each($toolbar_html));
        }

        $list_header = empty($ctype['labels']['list']) ? $ctype['title'] : $ctype['labels']['list'];
        $rss_query   = !empty($category['id']) ? "?category={$category['id']}" : '';

        $base_ds_url = href_to_rel($ctype['name']) . '%s' . (isset($category['slug']) ? '/' . $category['slug'] : '');

        if (!$is_frontpage) {
            $category_seo = $this->applyCategorySeo($ctype, $category, $current_dataset);
        }

        if (empty($ctype['options']['list_off_breadcrumb'])) {

            if ($ctype['options']['list_on'] && !$is_frontpage && !empty($base_url)) {
                $this->cms_template->addBreadcrumb($list_header, href_to($ctype['name']));
            }

            if (isset($category['path']) && $category['path']) {
                foreach ($category['path'] as $c) {
                    $this->cms_template->addBreadcrumb($c['title'], href_to($base_url, $c['slug']));
                }
            }
        }

        // Мы в фильтре
        if ($this->list_filter) {

            if ($subcats) {
                foreach ($subcats as $key => $sub) {
                    $subcats[$key]['slug'] .= '/' . $this->list_filter['slug'];
                }
            }

            $base_ds_url .= '/' . $this->list_filter['slug'];

            $this->cms_template->addBreadcrumb($this->list_filter['title'], (!empty($page_url['base']) ? $page_url['base'] : $page_url));
        }

        return $this->cms_template->render($tpl_file, array(
            'category_seo'    => (!empty($category_seo) ? $category_seo : []),
            'base_ds_url'     => $base_ds_url,
            'base_url'        => $base_url,
            'rss_query'       => $rss_query,
            'list_styles'     => $list_styles,
            'is_frontpage'    => $is_frontpage,
            'is_hide_items'   => $is_hide_items,
            'hooks_html'      => $hooks_html,
            'toolbar_html'    => $toolbar_html,
            'slug'            => $slug,
            'ctype'           => $ctype,
            'datasets'        => $datasets,
            'dataset'         => $dataset,
            'current_dataset' => $current_dataset,
            'category'        => $category,
            'subcats'         => $subcats,
            'items_list_html' => $items_list_html,
            'user'            => $this->cms_user
        ), $this->request);

    }

    private function getCategoryAndSlugAndCtype() {

        $slug = $this->request->get('slug', '');

        $_ctype_name = $this->request->get('ctype_name');

        if (!$_ctype_name) {
            return cmsCore::error404();
        }

        $ctype_names = is_array($_ctype_name) ? $_ctype_name : [$_ctype_name];

        // есть типы контента по умолчанию
        if ($this->cms_config->ctype_default) {
            foreach ($this->cms_config->ctype_default as $ctype_default) {
                if (!in_array($ctype_default, $ctype_names)) {
                    $ctype_names[] = $ctype_default;
                }
            }
        }

        foreach ($ctype_names as $ctype_name) {

            $ctype_name = $this->getCtypeByAlias($ctype_name);

            $ctype = $this->model->getContentTypeByName($ctype_name);
            // типы контента тут должны быть известные
            if (!$ctype) {
                return cmsCore::error404();
            }

            // если типов контента по умолчанию нет, сразу отдаём 404
            // чтобы не перебирать дальше
            if (!$this->cms_config->ctype_default && empty($ctype['options']['list_on'])) {
                return cmsCore::error404();
            }

            // значит переданный $_ctype_name = корневая страница типа контента
            if ($slug === 'index') {
                return [$ctype, ['id' => false, 'description' => (!empty($ctype['description']) ? $ctype['description'] : '')], $slug];
            }

            $category = $this->model->getCategoryBySLUG($ctype['name'], $slug);

            // Урлы фильтров
            // Если категорию не нашли
            if (!$category) {

                $filters_segments = [];

                $segments = explode('/', $slug);

                // отсекаем справа по сегменту и ищем категорию
                while (count($segments) >= 1) {

                    $filters_segments[] = array_pop($segments);

                    if (!$segments) {

                        $category = array(
                            'id'          => false,
                            'description' => (!empty($ctype['description']) ? $ctype['description'] : '')
                        );

                        $slug = 'index';

                        break;
                    }

                    $slug = implode('/', $segments);

                    $category = $this->model->getCategoryBySLUG($ctype['name'], $slug);

                    // если нашли, то это отсеченное - slug фильтра,
                    // оставшееся - slug категории
                    if ($category) {
                        break;
                    }
                }
                // Если нашли, ищем фильтр
                if ($category) {

                    $filters_segments = array_reverse($filters_segments);

                    $filter = $this->model->getContentFilter($ctype, implode('/', $filters_segments));

                    if ($filter) {

                        if (!empty($filter['filters'])) {
                            foreach ($filter['filters'] as $fname => $fvalue) {
                                if ($fvalue && !$this->request->has($fname)) {
                                    $this->request->set($fname, $fvalue);
                                }
                            }
                        }

                        $category['description'] .= $filter['description'];

                        $this->list_filter = $filter;
                    } else {

                        // Не нашли, значит и категорию не нашли
                        // чтобы в хуке ниже при необходимости были исходные условия
                        $category = false;

                        $slug = $this->request->get('slug', '');
                    }
                }
            }

            list(
                $slug,
                $ctype,
                $category
            ) = cmsEventsManager::hook(['content_get_category_by_slug', "content_{$ctype['name']}_get_category_by_slug"], [
                $slug,
                $ctype,
                $category
            ], null, $this->request);

            if (!$category) {

                // если тип контента не входит в список умолчаний, сразу 404
                if (!$this->cms_config->ctype_default || !in_array($ctype['name'], $this->cms_config->ctype_default)) {
                    return cmsCore::error404();
                }

                continue;
            }

            // дошли до сюда, а категории выключены и фильтров нет
            if (!$ctype['is_cats'] && !$this->list_filter) {
                return cmsCore::error404();
            }

            // список выключен
            if (empty($ctype['options']['list_on'])) {
                return cmsCore::error404();
            }

            // должно быть точное совпадение
            if ($slug !== 'index' && strlen($slug) === strlen($category['slug']) && $slug !== $category['slug']) {
                if (($this->cms_config->ctype_default && in_array($ctype['name'], $this->cms_config->ctype_default))) {
                    $this->redirect(href_to($category['slug']), 301);
                }
                $this->redirect(href_to($ctype['name'], $category['slug']), 301);
            }

            // редирект со старых адресов
            if ((!$this->cms_core->uri_controller_before_remap &&
                    !$this->request->get('dataset', '') &&
                    $this->cms_config->ctype_default &&
                    in_array($ctype['name'], $this->cms_config->ctype_default))) {
                $this->redirect(href_to($category['slug']), 301);
            }

            // если тип контента сменился
            if ($ctype['name'] !== $ctype_name) {

                // новый uri
                $this->cms_core->uri              = preg_replace("#^{$_ctype_name}/#", $ctype['name'] . '/', $this->cms_core->uri);
                $this->cms_core->uri_before_remap = preg_replace("#^{$_ctype_name}/#", $ctype['name'] . '/', $this->cms_core->uri_before_remap);

                // обновляем страницы и маски
                $this->cms_core->setMatchedPages(null)->loadMatchedPages();
            }

            return [$ctype, $category, $slug];
        }

        // ничего не нашли
        return cmsCore::error404();

    }

    private function getCtypeByAlias($ctype_name) {

        $mapping = cmsConfig::getControllersMapping();

        if ($mapping) {
            foreach ($mapping as $name => $alias) {

                if($this->is_remapped){
                    break;
                }

                if ($alias == $ctype_name) {

                    $this->is_remapped = true;

                    $ctype_name = $name;

                    break;

                }
                if ($name == $ctype_name) {
                    $this->remap_redirect_ctype = $alias;
                }
            }
        }

        return $ctype_name;
    }

}
