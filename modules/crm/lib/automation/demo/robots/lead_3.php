<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'NEW'        =>
		array(
			array(
				'Type'       => 'CrmConvertDocumentActivity',
				'Properties' =>
					array(
						'Items' => array('DEAL', 'CONTACT'),
						'DealCategoryId' => '0',
						'DisableActivityCompletion' => 'Y',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_3_CONVERT_TITLE'),
					),
				'Name'       => 'A10703_53817_8984_23943',
			),
		),
);