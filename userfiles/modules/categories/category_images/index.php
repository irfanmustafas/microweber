<?php

$parent = get_option('fromcategory', $params['id']);
$selected_page = get_option('frompage', $params['id']);

$show_only_for_parent = get_option('single-only', $params['id']);

$show_category_header = get_option('show_category_header', $params['id']);

if ($parent == 'current') {
    $parent = CATEGORY_ID;
}

if (!$parent) {
    $parent = url_param('collection');
}
if (!$parent) {
    $parent = get_category_id_from_url();
}

if (!isset($parent) or $parent == '') {
    $parent = 0;
}

$cats = get_categories('no_limit=true&order_by=position asc&rel_id=[not_null]&parent_id=' . intval($parent));
if(!$cats or $show_only_for_parent){
    $cats = get_categories('no_limit=true&order_by=position asc&rel_id=[not_null]&id=' . intval($parent));
}
if ($selected_page) {
    $cats = get_categories('no_limit=true&order_by=position asc&rel_id=' . intval($selected_page));
}


if (!empty($cats)) {
    foreach ($cats as $k => $cat) {

        $cat['picture'] = get_picture($cat['id'], 'category');

        if ($cat['rel_type'] == 'content') {
            $latest = get_content("order_by=position desc&limit=30&category=" . $cat['id']);

            if (!$cat['picture'] and isset($latest[0])) {
                $latest_product = $latest[0];
                $cat['picture'] = get_picture($latest_product['id']);
            }

            if ($latest) {
                $cat['content_items'] = $latest;
            }

        }
        $cats[$k] = $cat;

    }
}


if (!$cats) {
    print lnotif('Categories not found');
}

$data = $cats;
$module_template = get_option('data-template', $params['id']);

if ($module_template != false and $module_template != 'none') {
    $template_file = module_templates($config['module'], $module_template);
} else {
    if (isset($params['template'])) {
        $template_file = module_templates($config['module'], $params['template']);
    } else {
        $template_file = module_templates($config['module'], 'default');
    }

}
$load_template = false;
$template_file_def = module_templates($config['module'], 'default');
if (isset($template_file) and is_file($template_file) != false) {
    $load_template = $template_file;
} elseif (isset($template_file_def) and is_file($template_file_def) != false) {
    $load_template = $template_file_def;
}

if (isset($load_template) and is_file($load_template) != false) {
    if (!$data) {
        print lnotif(_e('Selected categories return no results'), true);
        return;
    }
    include($load_template);
} else {
    print lnotif(_e('No template found'), true);
}

