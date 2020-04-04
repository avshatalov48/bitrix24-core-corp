<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'NEW' => array(
		array(
			'Type'       => 'SocNetMessageActivity',
			'Properties' =>
				array(
					'MessageText'     => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_1_MESSAGE', array(
						'#URL_1#' => '[url=/crm/lead/show/{=Document:ID}/]',
						'#URL_2#' => '[/url]',
					)),
					'MessageUserTo'   => '{=Document:ASSIGNED_BY_ID}',
					'Title'           => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_NOTIFY_TITLE'),
					'MessageFormat' => 'robot'
				),
			'Name'       => 'A80076_39574_91538_16852'
		),
		array(
			'Type'       => 'SocNetMessageActivity',
			'Properties' =>
				array(
					'MessageText'     => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_2_MESSAGE', array(
						'#URL_1#' => '[url=/crm/lead/show/{=Document:ID}/]',
						'#URL_2#' => '[/url]',
					)),
					'MessageUserTo'   => '{=Document:ASSIGNED_BY_ID}',
					'Title'           => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_NOTIFY_TITLE'),
					'MessageFormat' => 'robot'
				),
			'Name'       => 'A98281_91927_73796_24217',
			'Delay'      => array('type' => 'after', 'value' => '2', 'valueType' => 'd', 'basis' => '{=System:Now}')
		),
		array(
			'Type'       => 'CrmControlNotifyActivity',
			'Properties' =>
				array(
					'MessageText' => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_3_MESSAGE', array(
						'#URL_1#' => '[url=/crm/lead/show/{=Document:ID}/]',
						'#URL_2#' => '[/url]',
					)),
					'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_1_CONTROL_TITLE'),
				),
			'Name'       => 'A67355_61522_13033_73744',
			'Delay'      => array('type' => 'after', 'value' => '3', 'valueType' => 'd', 'basis' => '{=System:Now}')
		),
	),
);