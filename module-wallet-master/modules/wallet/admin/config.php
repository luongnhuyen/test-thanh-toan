<?php

/**
 * @Project WALLET 4.x
 * @Author VINADES.,JSC <contact@vinades.vn>
 * @Copyright (C) 2018 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Friday, March 9, 2018 6:24:54 AM
 */

if (!defined('NV_IS_FILE_ADMIN'))
    die('Stop!!!');

$page_title = $lang_module['config_module'];
$array_config = $module_config[$module_name];

if ($nv_Request->isset_request('submit', 'post')) {
    $minimum_amounts = $nv_Request->get_typed_array('minimum_amount', 'post', 'string', array());
    $recharge_rates_s = $nv_Request->get_typed_array('recharge_rate_s', 'post', 'float', array());
    $recharge_rates_r = $nv_Request->get_typed_array('recharge_rate_r', 'post', 'float', array());

    $array_config['minimum_amount'] = array();
    $array_config['recharge_rate'] = array();

    foreach ($global_array_money_sys as $money_sys) {
        $minimum_amount = isset($minimum_amounts[$money_sys['code']]) ? $minimum_amounts[$money_sys['code']] : '';
        $minimum_amount = array_map('trim', explode(',', $minimum_amount));
        if ($money_sys['code'] == 'VND') {
            $minimum_amount = array_map('intval', $minimum_amount);
        } else {
            $minimum_amount = array_map('floatval', $minimum_amount);
        }
        $minimum_amount = array_map('abs', $minimum_amount);
        $minimum_amount = array_filter(array_unique($minimum_amount));
        $minimum_amount = implode(',', $minimum_amount);
        $array_config['minimum_amount'][$money_sys['code']] = $minimum_amount;

        $recharge_rate_s = isset($recharge_rates_s[$money_sys['code']]) ? abs(floatval($recharge_rates_s[$money_sys['code']])) : 0;
        $recharge_rate_r = isset($recharge_rates_r[$money_sys['code']]) ? abs(floatval($recharge_rates_r[$money_sys['code']])) : 0;
        if ($recharge_rate_s > 0 and $recharge_rate_r > 0) {
            $array_config['recharge_rate'][$money_sys['code']] = array(
                's' => $recharge_rate_s,
                'r' => $recharge_rate_r
            );
        }
    }
    $array_config['minimum_amount'] = !empty($array_config['minimum_amount']) ? serialize($array_config['minimum_amount']) : '';
    $array_config['recharge_rate'] = !empty($array_config['recharge_rate']) ? serialize($array_config['recharge_rate']) : '';
    $array_config['payport_content'] = $nv_Request->get_editor('payport_content', '', NV_ALLOWED_HTML_TAGS);
    if (!empty($array_config['payport_content'])) {
        $array_config['payport_content'] = nv_editor_nl2br($array_config['payport_content']);
    }
    $array_config['allow_exchange_pay'] = ($nv_Request->get_int('allow_exchange_pay', 'post', 0) == 1) ? 1 : 0;

    $sth = $db->prepare("UPDATE " . NV_CONFIG_GLOBALTABLE . "
	SET config_value = :config_value
	WHERE lang = '" . NV_LANG_DATA . "' AND module = '" . $module_name . "' AND config_name = :config_name");

    foreach ($array_config as $key => $value) {
        $sth->bindParam(':config_name', $key, PDO::PARAM_STR);
        $sth->bindParam(':config_value', $value, PDO::PARAM_INT);
        $exc = $sth->execute();
    }

    nv_insert_logs(NV_LANG_DATA, $module_name, 'Change config module', ' ', $admin_info['userid']);

    $nv_Cache->delMod('settings');
    $nv_Cache->delMod($module_name);
    nv_redirect_location(NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op);
}

if (defined('NV_EDITOR')) {
    require_once (NV_ROOTDIR . '/' . NV_EDITORSDIR . '/' . NV_EDITOR . '/nv.php');
}

$array_config['payport_content'] = htmlspecialchars(nv_editor_br2nl($array_config['payport_content']));
if (defined('NV_EDITOR') and nv_function_exists('nv_aleditor')) {
    $array_config['payport_content'] = nv_aleditor('payport_content', '100%', '300px', $array_config['payport_content']);
} else {
    $array_config['payport_content'] = '<textarea class="form-control" style="width:100%;height:300px" name="payport_content">' . $array_config['payport_content'] . '</textarea>';
}

$array_config['allow_exchange_pay'] = empty($array_config['allow_exchange_pay']) ? '' : ' checked="checked"';

$xtpl = new XTemplate($op . '.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_LANG_VARIABLE', NV_LANG_VARIABLE);
$xtpl->assign('NV_LANG_DATA', NV_LANG_DATA);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('MODULE_UPLOAD', $module_upload);
$xtpl->assign('OP', $op);
$xtpl->assign('DATA', $array_config);

$array_config['minimum_amount'] = !empty($array_config['minimum_amount']) ? unserialize($array_config['minimum_amount']) : array();
$array_config['recharge_rate'] = !empty($array_config['recharge_rate']) ? unserialize($array_config['recharge_rate']) : array();

foreach ($global_array_money_sys as $money_sys) {
    $xtpl->assign('MONEY_VALUE', isset($array_config['minimum_amount'][$money_sys['code']]) ? $array_config['minimum_amount'][$money_sys['code']] : '');
    $xtpl->assign('MONEY_SYS', $money_sys);
    $xtpl->parse('main.money_sys');

    $recharge_rate = isset($array_config['recharge_rate'][$money_sys['code']]) ? $array_config['recharge_rate'][$money_sys['code']] : array();
    $xtpl->assign('RECHARGE_RATE_S', !empty($recharge_rate['s']) ? $recharge_rate['s'] : '');
    $xtpl->assign('RECHARGE_RATE_R', !empty($recharge_rate['r']) ? $recharge_rate['r'] : '');

    $xtpl->parse('main.recharge_rate');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
