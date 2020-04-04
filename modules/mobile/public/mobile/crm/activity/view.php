<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.activity.view',
	'',
	array(
		'UID' => 'mobile_crm_activity_view',
		'SERVICE_URL_TEMPLATE'=> '#SITE_DIR#mobile/ajax.php?mobile_action=crm_activity_edit&site_id=#SITE#&sessid=#SID#',
		'ACTIVITY_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/view.php?activity_id=#activity_id#',
		'ACTIVITY_CREATE_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#',
		'ACTIVITY_EDIT_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/activity/edit.php?activity_id=#activity_id#',
		'LEAD_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/lead/?page=view&lead_id=#lead_id#',
		'DEAL_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/deal/?page=view&deal_id=#deal_id#',
		'CONTACT_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/contact/?page=view&contact_id=#contact_id#',
		'COMPANY_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/company/?page=view&company_id=#company_id#',
		'USER_PROFILE_URL_TEMPLATE' => '#SITE_DIR#mobile/users/?user_id=#user_id#',
		'COMMUNICATION_LIST_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/comm/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#&type_id=#type_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
