<?
IncludeModuleLangFile(__FILE__);

$arFields = Array(
	'DOCUMENT_TYPE' => Array('crm', 'CCrmDocumentDeal', 'DEAL'),
	'AUTO_EXECUTE' => '1',
	'NAME' => GetMessage('CRM_BP_SHOP_TITLE'),
	'DESCRIPTION' => '',
	'TEMPLATE' => Array(
		Array(
			"Type" => "SequentialWorkflowActivity",
			"Name" => "Template",
			"Properties" => Array(
				"Title" => GetMessage('CRM_BP_SHOP_PBP'),
			),
			"Children" => Array(
				Array(
					"Type" => "IfElseActivity",
					"Name" => "A31268_61221_62819_29193",
					"Properties" => Array("Title" => GetMessage('CRM_BP_SHOP_US')),
					"Children" => Array(
						Array(
							"Type" => "IfElseBranchActivity",
							"Name" => "A69458_2051_83991_46479",
							"Properties" => Array(
								"Title" => GetMessage('CRM_BP_SHOP_US'),
								"fieldcondition" => Array(
									Array("ORIGINATOR_ID", ">", 0),
									Array("ASSIGNED_BY_ID", "!=", "")
								)
							),
							"Children" => Array(
								Array(
									"Type" => "IfElseActivity",
									"Name" => "A58615_38814_97519_3695",
									"Properties" => Array("Title" => GetMessage('CRM_BP_SHOP_US')),
									"Children" => Array(
										Array(
											"Type" => "IfElseBranchActivity",
											"Name" => "A37616_3264_77928_55086",
											"Properties" => Array(
												"Title" => GetMessage('CRM_BP_SHOP_US'),
												"fieldcondition" => Array(
													Array("OPPORTUNITY_ACCOUNT", ">", GetMessage('CRM_BP_SHOP_US_10000'))
												)
											),
											"Children" => Array(
												Array(
													"Type" => "SocNetMessageActivity",
													"Name" => "A84587_84379_62092_97538",
													"Properties" => Array(
														"MessageText" => GetMessage('CRM_BP_SHOP_10000')."\n[url]/crm/deal/show/{=Document:ID}/[/url]",
														"MessageUserFrom" => "{=Document:ASSIGNED_BY_ID}",
														"MessageUserTo" => "{=Document:ASSIGNED_BY_ID}",
														"Title" => GetMessage('CRM_BP_SHOP_SS')
													)
												)
											)
										),
										Array(
											"Type" => "IfElseBranchActivity",
											"Name" => "A28885_2998_43250_72329",
											"Properties" => Array("Title" => GetMessage('CRM_BP_SHOP_US')),
											"Children" => Array(
												Array(
													"Type" => "SocNetMessageActivity",
													"Name" => "A69187_71347_15863_8152",
													"Properties" => Array(
														"MessageText" => GetMessage('CRM_BP_SHOP_10000_1')."\n[url]/crm/deal/show/{=Document:ID}/[/url]",
														"MessageUserFrom" => "{=Document:ASSIGNED_BY_ID}",
														"MessageUserTo" => "{=Document:ASSIGNED_BY_ID}",
														"Title" => GetMessage('CRM_BP_SHOP_SS'),
													)
												)
											)
										)
									)
								)
							)
						),
						Array(
							"Type" => "IfElseBranchActivity",
							"Name" => "A57238_81655_60005_12078",
							"Properties" => Array("Title" => GetMessage('CRM_BP_SHOP_US'))
						)
					)
				)
			)
		)
    ),
    'PARAMETERS' => Array(),
    'VARIABLES' => Array()
);
?>