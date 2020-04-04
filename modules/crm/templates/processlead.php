<?
IncludeModuleLangFile(__FILE__);

$arFields = Array(
   'DOCUMENT_TYPE' => Array
		(
			'0' => 'crm',
			'1' => 'CCrmDocumentLead',
			'2' => 'LEAD'
		),

	'AUTO_EXECUTE' => '1',
	'NAME' => GetMessage('CRM_BP_LEAD_TITLE'),
	'DESCRIPTION' => '',
	'TEMPLATE' => Array
		(
			Array
				(
					'Type' => 'SequentialWorkflowActivity',
					'Name' => 'Template',
					'Properties' => Array
						(
							'Title' => GetMessage('CRM_BP_LEAD_TYPE'),
						),

					'Children' => Array
						(
							Array
								(
									'Type' => 'Task2Activity',
									'Name' => 'A52446_44048_65764_1869',
									'Properties' => Array
										(
											'Fields' => Array
												(
													'TITLE' => GetMessage('CRM_BP_LEAD_LEADTASK_NAME'),
													'CREATED_BY' => 'user_1',
													'RESPONSIBLE_ID' => Array
														(
															'0' => 'Document',
															'1' => 'ASSIGNED_BY_ID'
														),

													'ACCOMPLICES' => '',
													'START_DATE_PLAN' => '',
													'END_DATE_PLAN' => '',
													'DEADLINE' => '',
													'DESCRIPTION' => GetMessage('CRM_BP_LEAD_TASK_TEXT'),
													'PRIORITY' => '0',
													'GROUP_ID' => '0',
													'ALLOW_CHANGE_DEADLINE' => 'Y',
													'TASK_CONTROL' => 'Y',
													'ADD_IN_REPORT' => 'Y',
													'AUDITORS' => '',
													'UF_CRM_TASK' => Array
														(
															'0' => 'L_{=Document:ID}',
														)
												),
											'HoldToClose' => '0',
											'Title' => GetMessage('CRM_BP_LEAD_TITLE')
										)

								)

						)

				)

		),

	'PARAMETERS' => Array(),
	'VARIABLES' => Array()
)

?>