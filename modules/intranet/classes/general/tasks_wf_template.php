<?
IncludeModuleLangFile(__FILE__);

$iblockId = COption::GetOptionInt("intranet", "iblock_tasks", 0);
if ($iblockId <= 0)
	return;

$arTaskStatusMap = array();
$dbRes = CIBlockProperty::GetPropertyEnum("TaskStatus", array("SORT" => "ASC"), array("IBLOCK_ID" => $iblockId));
while ($arRes = $dbRes->Fetch())
	$arTaskStatusMap[$arRes["XML_ID"]] = $arRes["ID"];

$arStatesKeys = array("NotAccepted", "NotStarted", "InProgress", "Completed", "Closed", "Waiting", "Deferred");

$arStates = array(
	"NotAccepted" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "author", "A"),
			"delete" => array("author", "A"),
		),
		"Events" => array(
			"SetResponsibleEvent" => array(
				"Permission" => array("responsible", "author", "A"),
				"NextState" => "NotAccepted",
				"Mail" => array(
					'MessageUserFrom' => array('user_1'),
					'MessageUserTo' => array("responsible", "author", "trackers"),
				),
				"Body" => array(
					array(
						'Type' => 'SocNetLogActivity',
						'Name' => "SLAX_NotAccepted_SetResponsibleEvent",
						'Properties' => array(
							"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAXR_SetResponsibleEvent"),
							"EntityType" => array("Template", "TaskType"),
							"EntityId" => array("Template", "OwnerId"),
							"Event" => "tasks",
							"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
							'Title' => "",
						)
					),
					array(
						'Type' => 'ForumReviewActivity',
						'Name' => "FRAX_NotAccepted_SetResponsibleEvent",
						'Properties' => array(
							'Title' => "",
							"IBlockId" => array("Template", "IBlockId"),
							"ForumId" => array("Template", "ForumId"),
							"ForumUser" => array("Document", "MODIFIED_BY"),
							"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_SetResponsibleEvent"),
						)
					),
				),
			),
			"ApproveEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotStarted",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"InProgressEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "InProgress",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CompleteEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Completed",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
		),
	),
	"NotStarted" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "author", "A"),
			"delete" => array("A"),
		),
		"Events" => array(
			"SetResponsibleEvent" => array(
				"Permission" => array("responsible", "author", "A"),
				"NextState" => "NotAccepted",
				"Mail" => array(
					'MessageUserFrom' => array('user_1'),
					'MessageUserTo' => array("responsible", "author", "trackers"),
				),
				"Body" => array(
					array(
						'Type' => 'SocNetLogActivity',
						'Name' => "SLAX_NotStarted_SetResponsibleEvent",
						'Properties' => array(
							"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAXR_SetResponsibleEvent"),
							"EntityType" => array("Template", "TaskType"),
							"EntityId" => array("Template", "OwnerId"),
							"Event" => "tasks",
							"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
							'Title' => "",
						)
					),
					array(
						'Type' => 'ForumReviewActivity',
						'Name' => "FRAX_NotStarted_SetResponsibleEvent",
						'Properties' => array(
							'Title' => "",
							"IBlockId" => array("Template", "IBlockId"),
							"ForumId" => array("Template", "ForumId"),
							"ForumUser" => array("Document", "MODIFIED_BY"),
							"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_SetResponsibleEvent"),
						)
					),
				),
			),
			"InProgressEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "InProgress",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CompleteEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Completed",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
			"WaitingEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Waiting",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"DeferredEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Deferred",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
		),
	),
	"InProgress" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "A"),
			"delete" => array("A"),
		),
		"Events" => array(
			"SetResponsibleEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotAccepted",
				"Mail" => array(
					'MessageUserFrom' => array('user_1'),
					'MessageUserTo' => array("responsible", "author", "trackers"),
				),
				"Body" => array(
					array(
						'Type' => 'SocNetLogActivity',
						'Name' => "SLAX_InProgress_SetResponsibleEvent",
						'Properties' => array(
							"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAXR_SetResponsibleEvent"),
							"EntityType" => array("Template", "TaskType"),
							"EntityId" => array("Template", "OwnerId"),
							"Event" => "tasks",
							"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
							'Title' => "",
						)
					),
					array(
						'Type' => 'ForumReviewActivity',
						'Name' => "FRAX_InProgress_SetResponsibleEvent",
						'Properties' => array(
							'Title' => "",
							"IBlockId" => array("Template", "IBlockId"),
							"ForumId" => array("Template", "ForumId"),
							"ForumUser" => array("Document", "MODIFIED_BY"),
							"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_SetResponsibleEvent"),
						)
					),
				),
			),
			"CompleteEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Completed",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
			"WaitingEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Waiting",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"DeferredEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Deferred",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
		),
	),
	"Completed" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "A"),
			"delete" => array("A"),
		),
		"Events" => array(
			"InProgressEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "InProgress",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
		),
	),
	"Closed" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("A"),
			"delete" => array("A"),
		),
		"Events" => array(),
	),
	"Waiting" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "A"),
			"delete" => array("A"),
		),
		"Events" => array(
			"SetResponsibleEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotAccepted",
				"Mail" => array(
					'MessageUserFrom' => array('user_1'),
					'MessageUserTo' => array("responsible", "author", "trackers"),
				),
				"Body" => array(
					array(
						'Type' => 'SocNetLogActivity',
						'Name' => "SLAX_Waiting_SetResponsibleEvent",
						'Properties' => array(
							"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAXR_SetResponsibleEvent"),
							"EntityType" => array("Template", "TaskType"),
							"EntityId" => array("Template", "OwnerId"),
							"Event" => "tasks",
							"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
							'Title' => "",
						)
					),
					array(
						'Type' => 'ForumReviewActivity',
						'Name' => "FRAX_Waiting_SetResponsibleEvent",
						'Properties' => array(
							'Title' => "",
							"IBlockId" => array("Template", "IBlockId"),
							"ForumId" => array("Template", "ForumId"),
							"ForumUser" => array("Document", "MODIFIED_BY"),
							"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_SetResponsibleEvent"),
						)
					),
				),
			),
			"NotStartedEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotStarted",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"InProgressEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "InProgress",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CompleteEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Completed",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
			"DeferredEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Deferred",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
		),
	),
	"Deferred" => array(
		"Permission" => array(
			"read" => array("responsible", "author", "trackers", "A"),
			"comment" => array("responsible", "author", "trackers", "A"),
			"write" => array("responsible", "A"),
			"delete" => array("A"),
		),
		"Events" => array(
			"SetResponsibleEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotAccepted",
				"Mail" => array(
					'MessageUserFrom' => array('user_1'),
					'MessageUserTo' => array("responsible", "author", "trackers"),
				),
				"Body" => array(
					array(
						'Type' => 'SocNetLogActivity',
						'Name' => "SLAX_Deferred_SetResponsibleEvent",
						'Properties' => array(
							"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAXR_SetResponsibleEvent"),
							"EntityType" => array("Template", "TaskType"),
							"EntityId" => array("Template", "OwnerId"),
							"Event" => "tasks",
							"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
							'Title' => "",
						)
					),
					array(
						'Type' => 'ForumReviewActivity',
						'Name' => "FRAX_Deferred_SetResponsibleEvent",
						'Properties' => array(
							'Title' => "",
							"IBlockId" => array("Template", "IBlockId"),
							"ForumId" => array("Template", "ForumId"),
							"ForumUser" => array("Document", "MODIFIED_BY"),
							"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_SetResponsibleEvent"),
						)
					),
				),
			),
			"NotStartedEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "NotStarted",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"InProgressEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "InProgress",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CompleteEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Completed",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
			"CloseEvent" => array(
				"Permission" => array("author", "A"),
				"NextState" => "Closed",
				"Mail" => array(
					'MessageUserFrom' => array("author"),
					'MessageUserTo' => array("responsible", "trackers"),
				),
			),
			"WaitingEvent" => array(
				"Permission" => array("responsible", "A"),
				"NextState" => "Waiting",
				"Mail" => array(
					'MessageUserFrom' => array("responsible"),
					'MessageUserTo' => array("author", "trackers"),
				),
			),
		),
	),
);

foreach ($arStatesKeys as $stateKey)
{
	$arChildrenTmp = array();
	foreach ($arStates[$stateKey]["Events"] as $eventKeyTmp => $arEventTmp)
	{
		$arChildrenTmp2 = array(
			array(
				'Type' => "HandleExternalEventActivity",
				'Name' => "HEEA_".$stateKey."_".$eventKeyTmp,
				'Properties' => array(
					'Permission' => $arEventTmp["Permission"],
					'Title' => GetMessage("INTASK_WF_TMPL_HEEA_T_".$eventKeyTmp),
				)
			)
		);

		if (array_key_exists("Body", $arEventTmp) && count($arEventTmp["Body"]) > 0)
			$arChildrenTmp2 = $arChildrenTmp2 + $arEventTmp["Body"];

		if (array_key_exists("Mail", $arEventTmp) && count($arEventTmp["Mail"]) > 0)
		{
			$arChildrenTmp2[] = array(
				'Type' => 'SocNetMessageActivity',
				'Name' => "SNMA_".$stateKey."_".$eventKeyTmp,
				'Properties' => array(
					'MessageText' => GetMessage("INTASK_WF_TMPL_SNMA_M_".$eventKeyTmp),
					'MessageUserFrom' => $arEventTmp["Mail"]["MessageUserFrom"],
					'MessageUserTo' => $arEventTmp["Mail"]["MessageUserTo"],
					'Title' => GetMessage("INTASK_WF_TMPL_SNMA_MT_".$eventKeyTmp)
				),
			);
		}

		if (array_key_exists("NextState", $arEventTmp) && strlen($arEventTmp["NextState"]) > 0)
		{
			$arChildrenTmp2[] = array(
				"Type" => "SetFieldActivity",
				"Name" => "SFA_".$stateKey."_".$eventKeyTmp,
				"Properties" => array(
					"FieldValue" => array("PROPERTY_TaskStatus" => $arTaskStatusMap[$arEventTmp["NextState"]]),
				),
			);
			$arChildrenTmp2[] = array(
				'Type' => 'SetStateActivity',
				'Name' => "SSA_".$stateKey."_".$eventKeyTmp,
				'Properties' => array(
					'TargetStateName' => $arEventTmp["NextState"],
					'Title' => GetMessage("INTASK_WF_TMPL_SSA_S_".$eventKeyTmp)
				)
			);
		}

		$arChildrenTmp[] = array(
			'Type' => "EventDrivenActivity",
			'Name' => "EDA_".$stateKey."_".$eventKeyTmp,
			'Properties' => array(
				'Title' => GetMessage("INTASK_WF_TMPL_HEEA_T_".$eventKeyTmp),
			),
			'Children' => $arChildrenTmp2
		);
	}

	$arOnInitialize = array(
		'Type' => "StateInitializationActivity",
		'Name' => "SIAX_".$stateKey,
		'Properties' => array(
			'Title' => "",
		),
		'Children' => array(
			array(
				'Type' => 'SocNetLogActivity',
				'Name' => "SLAX_".$stateKey,
				'Properties' => array(
					"LogTitle" => GetMessage("INTASK_WF_TMPL_SLAX_".$stateKey."_LOGTITLE"),
					"EntityType" => array("Template", "TaskType"),
					"EntityId" => array("Template", "OwnerId"),
					"Event" => "tasks",
					"LogText" => GetMessage("INTASK_WF_TMPL_SLAX_LOGTEXT"),
					'Title' => "",
				),
			),
			array(
				'Type' => 'ForumReviewActivity',
				'Name' => "FRAX_".$stateKey."_ONINIT",
				'Properties' => array(
					'Title' => "",
					"IBlockId" => array("Template", "IBlockId"),
					"ForumId" => array("Template", "ForumId"),
					"ForumUser" => array("Document", "MODIFIED_BY"),
					"ForumPostMessage" => GetMessage("INTASK_WF_TMPL_FRAXR_".$stateKey."_ONINIT"),
				)
			),
		),
	);

	if ($stateKey == "NotAccepted")
	{
		$arOnInitialize['Children'][] = array(
			"Type" => "IfElseActivity",
			"Name" => "IEA95_NotAccepted",
			"Properties" => array(),
			"Children" => array(
				array(
					"Type" => "IfElseBranchActivity",
					"Name" => "IEBA95_NotAccepted",
					"Properties" => array("PropertyVariableCondition" => array("VAR_ALREADY_STARTED", "=", 1)),
					"Children" => array(
						array(
							'Type' => 'SocNetMessageActivity',
							'Name' => "SNMA95_NotAccepted",
							'Properties' => array(
								'MessageText' => GetMessage("INTASK_WF_TMPL_SNMA95_M_NotAccepted"),
								'MessageUserFrom' => array("author"),
								'MessageUserTo' => array("responsible", "trackers"),
								'Title' => GetMessage("INTASK_WF_TMPL_SNMA95_MT_NotAccepted")
							),
						),
						array(
							"Type" => "SetVariableActivity",
							"Name" => "SVA95_NotAccepted",
							"Properties" => array(
								"VariableValue" => array("VAR_ALREADY_STARTED" => 2),
								"Title" => "",
							),
						),
					),
				),
				array(
					"Type" => "IfElseBranchActivity",
					"Name" => "IEBA951_NotAccepted",
					"Properties" => array(),
					"Children" => array(),
				),
			),
		);
	}
	elseif ($stateKey == "Completed" || $stateKey == "Closed")
	{
		$arOnInitialize['Children'][] = array(
			"Type" => "SetFieldActivity",
			"Name" => "SFA_".$stateKey."_NF1",
			"Properties" => array(
				"FieldValue" => array(
					"PROPERTY_TaskComplete" => 100,
					"PROPERTY_TaskFinish" => array("System", "Now"),
				),
			),
		);
	}

	$arChildrenTmp[] = $arOnInitialize;

	$arFieldsTemplateStates[] = array(
		'Type' => 'StateActivity',
		'Name' => $stateKey,
		'Properties' => array(
			'Permission' => $arStates[$stateKey]["Permission"],
			'Title' => GetMessage("INTASK_WF_TMPL_".$stateKey),
		),
		'Children' => $arChildrenTmp
	);
}


$arFieldsTemplate = array(
	array(
		'Type' => 'StateMachineWorkflowActivity',
		'Name' => 'Template',
		'Properties' => array(
			'Title' => GetMessage("INTASK_WF_TMPL_BP_NAME"),
			'InitialStateName' => 'NotAccepted',
		),
		'Children' => $arFieldsTemplateStates
	)
);

$arFields = array(
	"DOCUMENT_TYPE" => array("intranet", "CIntranetTasksDocument", "x".$iblockId),
	"AUTO_EXECUTE" => 1,
	"NAME" => GetMessage("INTASK_WF_TMPL_NAME"),
	"DESCRIPTION" => GetMessage("INTASK_WF_TMPL_DESC"),
	"PARAMETERS" => array(
		"OwnerId" => array(
			"Name" => "Owner ID",
			"Description" => "",
			"Type" => "int",
			"Required" => true,
			"Multiple" => false,
			"Default" => 0
		),
		"TaskType" => array(
			"Name" => "Task Type",
			"Description" => "",
			"Type" => "select",
			"Required" => true,
			"Multiple" => false,
			"Default" => "user",
			"Options" => array(
				"user" => "User",
				"group" => "Group",
			),
		),
		"PathTemplate" => array(
			"Name" => "Path Template",
			"Description" => "",
			"Type" => "string",
			"Required" => true,
			"Multiple" => false,
			"Options" => null,
		),
		"ForumId" => array(
			"Name" => "Forum ID",
			"Description" => "",
			"Type" => "int",
			"Required" => true,
			"Multiple" => false,
			"Default" => 0
		),
		"IBlockId" => array(
			"Name" => "IBlock ID",
			"Description" => "",
			"Type" => "int",
			"Required" => true,
			"Multiple" => false,
			"Default" => 0
		),
	),
	"VARIABLES" => array(),
	"TEMPLATE" => $arFieldsTemplate
);
?>