<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'NEW' => array(
		array(
			'Type'       => 'SocNetMessageActivity',
			'Properties' =>
				array(
					'MessageText'     => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_1_1_MESSAGE', array(
						'#URL_1#' => '[url=/crm/deal/show/{=Document:ID}/]',
						'#URL_2#' => '[/url]',
					)),
					'MessageUserTo'   => '{=Document:ASSIGNED_BY_ID}',
					'Title'           => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_1_NOTIFY_TITLE'),
					'MessageFormat' => 'robot'
				),
			'Name'       => 'A64539_77252_84355_68246',
		),
		array(
			'Type'       => 'SocNetMessageActivity',
			'Properties' =>
				array(
					'MessageText'     => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_1_2_MESSAGE', array(
						'#URL_1#' => '[url=/crm/deal/show/{=Document:ID}/]',
						'#URL_2#' => '[/url]',
					)),
					'MessageUserTo'   => '{=Document:ASSIGNED_BY_ID}',
					'Title'           => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_1_NOTIFY_TITLE'),
					'MessageFormat' => 'robot'
				),
			'Name'       => 'A13404_37053_48556_20576',
			'Delay'      => array('type' => 'after', 'value' => '2', 'valueType' => 'd', 'basis' => '{=System:Now}')
		),
	)
);