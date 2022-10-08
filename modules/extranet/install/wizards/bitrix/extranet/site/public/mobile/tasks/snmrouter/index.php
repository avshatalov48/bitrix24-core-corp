<?php
/**
 * S.N.M. router
 */

require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:mobile.tasks.snmrouter',
	'.default',
	array(
		'PREFIX_FOR_PATH_TO_SNM_ROUTER' => SITE_DIR.'mobile/tasks/snmrouter/', // unused?
		'PATH_TO_SNM_ROUTER_AJAX'     	=> SITE_DIR.'mobile/?mobile_action=task_ajax',
		'DATE_TIME_FORMAT'              => 'j F Y G:i',
		'PATH_TEMPLATE_TO_USER_PROFILE' => SITE_DIR.'mobile/users/?user_id=#USER_ID#',
		'PATH_TO_FORUM_SMILE'           => '/bitrix/images/forum/smile/',
		'AVATAR_SIZE'                   => array('width' => 58, 'height' => 58)
	),
	false
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
