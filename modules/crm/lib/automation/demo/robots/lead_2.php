<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'NEW'        =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_1"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A80076_39574_91538_16852',
			),
			array(
				'Type'                 => 'SocNetMessageActivity',
				'Properties'           =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_2"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_NOTIFY_TITLE'),
					),
				'Name'                 => 'A98281_91927_73796_24217',
				'Delay'                =>
					array(
						'type'      => 'after',
						'value'     => '1',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'            => 'A51938_34769_33535_69590',
				'ExecuteAfterPrevious' => '1',
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_3"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_CONTROL_TITLE'),
					),
				'Name'       => 'A67355_61522_13033_73744',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A90051_37730_87510_47292',
			),
		),
	'IN_PROCESS' =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_2"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A28086_71643_28365_98086',
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_3"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_CONTROL_TITLE'),
					),
				'Name'       => 'A51190_50950_64719_78291',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A39877_56702_16516_65198',
			),
		),
	'PROCESSED'  =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_2"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A86449_35886_93041_19637',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '0',
						'valueType' => 'i',
						'basis'     => '{=System:Now}',
					),
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_LEAD_2_MESSAGE_3"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_LEAD_2_CONTROL_TITLE'),
					),
				'Name'       => 'A57404_93335_19193_84575',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A20557_60615_25167_38405',
			),
		)
);