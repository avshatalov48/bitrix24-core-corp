<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return array(
	'NEW'                =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Name'       => 'A62135_91243_31045_54952',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_1_MESSAGE'),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
			),
			array(
				'Type'                 => 'SocNetMessageActivity',
				'Properties'           =>
					array(
						'MessageText'   => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_2_MESSAGE'),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'                 => 'A89107_4202_45529_8329',
				'Delay'                =>
					array(
						'type'      => 'after',
						'value'     => '1',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'            => 'A51022_79869_90377_43100',
				'ExecuteAfterPrevious' => '1',
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_3_MESSAGE"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CONTROL_TITLE'),
					),
				'Name'       => 'A43681_96121_49224_5177',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A66060_21783_17513_34188',
			),
		),
	'LOSE'               =>
		array(
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_4_MESSAGE"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CONTROL_TITLE'),
					),
				'Name'       => 'A72347_45258_84718_96743',
			),
		),
	'PREPARATION'        =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_5_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A55494_39534_99534_32980',
			),
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_6_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A76441_86956_49274_46412',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '2',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A41273_63113_44732_37123',
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_7_MESSAGE"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CONTROL_TITLE'),
					),
				'Name'       => 'A1071_37131_72289_5010',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A30261_40869_78632_48276',
			),
		),
	'PREPAYMENT_INVOICE' =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_8_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A48681_34712_75278_71233',
			),
			array(
				'Type'       => 'CrmCreateCallActivity',
				'Properties' =>
					array(
						'Subject'     => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_CALL_SUBJECT"),
						'StartTime'   => '=dateadd({=System:Date},"12h")',
						'EndTime'     => '=dateadd({=System:Date},"13h")',
						'Description' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_9_MESSAGE"),
						'Responsible' => '{=Document:ASSIGNED_BY_ID}',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CALL_TITLE'),
					),
				'Name'       => 'A95123_55448_52897_4285',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '2',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A97707_87515_17023_50438',
			),
			array(
				'Type'                 => 'CrmControlNotifyActivity',
				'Properties'           =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_10_MESSAGE"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CONTROL_TITLE'),
					),
				'Name'                 => 'A46965_67429_31101_13033',
				'Delay'                =>
					array(
						'type'      => 'after',
						'value'     => '3',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'            => 'A84579_65221_73488_18887',
				'ExecuteAfterPrevious' => '1',
			),
		),
	'EXECUTING'          =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_11_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A13242_91178_35989_15181',
			),
			array(
				'Type'       => 'CrmControlNotifyActivity',
				'Properties' =>
					array(
						'MessageText' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_12_MESSAGE"),
						'ToHead'      => 'Y',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CONTROL_TITLE'),
					),
				'Name'       => 'A47783_37796_13345_67547',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '4',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A71236_60894_73042_11860',
			),
		),
	'FINAL_INVOICE'      =>
		array(
			array(
				'Type'       => 'SocNetMessageActivity',
				'Properties' =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_13_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'       => 'A12064_69309_94555_95843',
			),
			array(
				'Type'       => 'CrmCreateCallActivity',
				'Properties' =>
					array(
						'Subject'     => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_CALL_SUBJECT"),
						'StartTime'   => '=dateadd({=System:Date},"12h")',
						'EndTime'     => '=dateadd({=System:Date},"13h")',
						'Description' => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_14_MESSAGE"),
						'Responsible' => '{=Document:ASSIGNED_BY_ID}',
						'Title'       => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_CALL_TITLE'),
					),
				'Name'       => 'A36005_50238_19111_65974',
				'Delay'      =>
					array(
						'type'      => 'after',
						'value'     => '2',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'  => 'A35385_73834_72296_23500',
			),
			array(
				'Type'                 => 'SocNetMessageActivity',
				'Properties'           =>
					array(
						'MessageText'   => Loc::getMessage("CRM_AUTOMATION_DEMO_DEAL_2_15_MESSAGE"),
						'MessageFormat' => 'robot',
						'MessageUserTo' => '{=Document:ASSIGNED_BY_ID}',
						'Title'         => Loc::getMessage('CRM_AUTOMATION_DEMO_DEAL_2_NOTIFY_TITLE'),
					),
				'Name'                 => 'A86014_91886_52193_79319',
				'Delay'                =>
					array(
						'type'      => 'after',
						'value'     => '1',
						'valueType' => 'd',
						'basis'     => '{=System:Now}',
					),
				'DelayName'            => 'A41023_53505_80540_39496',
				'ExecuteAfterPrevious' => '1',
			),
		),
);