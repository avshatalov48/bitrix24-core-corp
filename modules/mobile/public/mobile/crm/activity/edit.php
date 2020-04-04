<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.activity.edit',
	'',
	array(
		'UID' => 'mobile_crm_activity_edit',
		'SERVICE_URL_TEMPLATE'=> '#SITE_DIR#mobile/ajax.php?mobile_action=crm_activity_edit&site_id=#SITE#&sessid=#SID#',
		'ACTIVITY_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/view.php?activity_id=#activity_id#',
		'ACTIVITY_CREATE_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#',
		'ACTIVITY_EDIT_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/edit.php?activity_id=#activity_id#',
		'COMMUNICATION_SELECTOR_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/comm/selector.php',
		'DEAL_SELECTOR_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/entity/?entity=deal',
		'USER_EMAIL_CONFIGURATOR_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/config_email.php'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
