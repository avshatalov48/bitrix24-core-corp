<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
IncludeModuleLangFile(__FILE__);

$arFields = [
	'AUTO_EXECUTE' => '0',
	'NAME' => GetMessage('BPT_SYNC_NAME'),
	'DESCRIPTION' => GetMessage('BPT_SYNC_DESC'),
	'TEMPLATE' => [
		[
			'Type' => 'SequentialWorkflowActivity',
			'Name' => 'Template',
			'Properties' => [
				'Title' => GetMessage('BPT_SYNC_SEQ'),
			],
			'Children' => [
				[
					'Type' => 'PublishDocumentActivity',
					'Name' => 'A42976_80938_66279_38005',
					'Properties' => [
						'Title' => GetMessage('BPT_SYNC_PUBLISH'),
					],
				],
				[
					'Type' => 'ControllerRemoteIBlockActivity',
					'Name' => 'A7120_93119_82719_16604',
					'Properties' => [
						'SitesFilterType' => 'all',
						'SyncTime' => 'immediate',
						'Title' => GetMessage('BPT_SYNC_SYNC'),
					],
				],
			],
		],
	],
	'VARIABLES' => [
	],
];
