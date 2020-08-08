<?
IncludeModuleLangFile(__FILE__);

$arFields = Array(
	'AUTO_EXECUTE' => '0',
	'NAME' => GetMessage("BPT_SYNC_NAME"),
	'DESCRIPTION' => GetMessage("BPT_SYNC_DESC"),
	'TEMPLATE' => array(
		array(
			"Type" => "SequentialWorkflowActivity",
			"Name" => "Template",
			"Properties" => array(
				"Title" => GetMessage("BPT_SYNC_SEQ"),
			),
			"Children" => array(
				array(
					"Type" => "PublishDocumentActivity",
					"Name" => "A42976_80938_66279_38005",
					"Properties" => array(
						"Title" => GetMessage("BPT_SYNC_PUBLISH"),
					),
				),
				array(
					"Type" => "ControllerRemoteIBlockActivity",
					"Name" => "A7120_93119_82719_16604",
					"Properties" => array(
						"SitesFilterType" => "all",
						"SyncTime" => "immediate",
						"Title" => GetMessage("BPT_SYNC_SYNC"),
					),
				),
			),
		),
	),
	'VARIABLES' => array(
	),
);
?>
