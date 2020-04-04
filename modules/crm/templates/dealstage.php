<?

IncludeModuleLangFile(__FILE__);

$arFields = Array
(
	'DOCUMENT_TYPE' => Array
		(
			'0' => 'crm',
			'1' => 'CCrmDocumentDeal',
			'2' => 'DEAL'
		),

	'AUTO_EXECUTE' => '1',
	'NAME' => GetMessage('CRM_BP_DEAL_TITLE'),
	'DESCRIPTION' => '',
	'TEMPLATE' => Array
		(
			Array
				(
					'Type' => 'StateMachineWorkflowActivity',
					'Name' => 'Template',
					'Properties' => Array
						(
							'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_1'),
							'InitialStateName' => 'A59277_30495_23355_93599'
						),

					'Children' => Array
						(
							Array
								(
									'Type' => 'StateActivity',
									'Name' => 'A59277_30495_23355_93599',
									'Properties' => Array
										(
											'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_1_TITLE'),
										),

									'Children' => Array
										(
											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A51641_45594_14099_22171',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A69918_87044_69725_50089',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => '38fa18844182f65fedfb91c283221046',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A35883_23582_98539_97658',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE'),
																		)

																)

														)

												),

											Array
												(
													'Type' => 'StateInitializationActivity',
													'Name' => 'A93308_97941_99254_12824',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_4_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'SetFieldActivity',
																	'Name' => 'A57101_13247_7902_97091',
																	'Properties' => Array
																		(
																			'FieldValue' => Array
																				(
																					'STAGE_ID' => 'NEW',
																					'CLOSED' => 'N'
																				),

																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_5_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A75288_15421_39712_24121',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A70385_61285_47189_96355',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_6_TITLE')
																		)

																)

														)

												)

										)

								),

							Array
								(
									'Type' => 'StateActivity',
									'Name' => 'A35883_23582_98539_97658',
									'Properties' => Array
										(
											'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_7_TITLE')
										),

									'Children' => Array
										(
											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A79160_7634_73529_72575',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A24749_33049_70446_30730',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_8_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => 'a4dd326ed12dfa421d338e3b207a2ff8',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A35883_23582_98539_97658',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A59714_51755_10783_15592',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2'),
														),

													'Children' => Array
														(
															'0' => Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A18231_36894_42625_30915',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_10_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => 'c6d2778f2afe3f8bc4d40cc538f012f1',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A32408_58730_91796_65400',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A76509_44516_79973_83564',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A58302_74047_9362_61113',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_11_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => '25337cc5af7f86630cf55f745e3d1c27',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A71266_48765_42296_65890',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'StateInitializationActivity',
													'Name' => 'A62362_77051_31694_42326',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_4_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'SetFieldActivity',
																	'Name' => 'A82120_95552_62579_53304',
																	'Properties' => Array
																		(
																			'FieldValue' => Array
																				(
																					'STAGE_ID' => 'PROPOSAL',
																					'CLOSED' => 'N'
																				),

																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_5_TITLE')
																		)

																)

														)

												)

										)

								),

							Array
								(
									'Type' => 'StateActivity',
									'Name' => 'A32408_58730_91796_65400',
									'Properties' => Array
										(
											'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_13_TITLE')
										),

									'Children' => Array
										(
											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A5214_59038_71778_6957',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A36010_23425_22620_69323',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_14_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => 'e86c13e8e4472e0ffb103134206481b1',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A35883_23582_98539_97658',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'StateInitializationActivity',
													'Name' => 'A64312_12056_56814_15992',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_4_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'SetFieldActivity',
																	'Name' => 'A63344_98012_93326_19260',
																	'Properties' => Array
																		(
																			'FieldValue' => Array
																				(
																					'STAGE_ID' => 'LOSE',
																					'CLOSED' => 'Y'
																				),

																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_5_TITLE')
																		)

																)

														)

												)

										)

								),

							Array
								(
									'Type' => 'StateActivity',
									'Name' => 'A71266_48765_42296_65890',
									'Properties' => Array
										(
											'Title' => GetMessage('CRM_BP_DEAL_TITLE')
										),

									'Children' => Array
										(
											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A68849_94603_25069_192',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_15_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A37286_59693_98817_3653',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_16_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => '6620aa3ea4dd1d4719980e057b3e4e51',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A4127_38070_54415_38758',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A10400_42598_96020_89764',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A16708_99276_92290_25289',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_11_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => '4f50f1649b97db7816c1613d7e6a1848',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A71266_48765_42296_65890',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'StateInitializationActivity',
													'Name' => 'A57780_67273_1813_57529',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_4_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'SetFieldActivity',
																	'Name' => 'A82617_11196_73950_36350',
																	'Properties' => Array
																		(
																			'FieldValue' => Array
																				(
																					'STAGE_ID' => 'NEGOTIATION',
																					'CLOSED' => 'N'
																				),

																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_5_TITLE')
																		)

																)

														)

												),

											Array
												(
													'Type' => 'EventDrivenActivity',
													'Name' => 'A41092_891_52713_66202',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_2')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'HandleExternalEventActivity',
																	'Name' => 'A97750_80130_15666_93753',
																	'Properties' => Array
																		(
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_10_TITLE')
																		)

																),

															Array
																(
																	'Type' => 'SetStateActivity',
																	'Name' => 'd0080b78e43a15f98b2d860bb67f0591',
																	'Properties' => Array
																		(
																			'TargetStateName' => 'A32408_58730_91796_65400',
																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_3_TITLE')
																		)

																)

														)

												)

										)

								),

							Array
								(
									'Type' => 'StateActivity',
									'Name' => 'A4127_38070_54415_38758',
									'Properties' => Array
										(
											'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_17_TITLE')
										),

									'Children' => Array
										(
											Array
												(
													'Type' => 'StateInitializationActivity',
													'Name' => 'A85823_2958_32846_56235',
													'Properties' => Array
														(
															'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_4_TITLE')
														),

													'Children' => Array
														(
															Array
																(
																	'Type' => 'SetFieldActivity',
																	'Name' => 'A42850_70608_37660_86346',
																	'Properties' => Array
																		(
																			'FieldValue' => Array
																				(
																					'STAGE_ID' => 'WON',
																					'CLOSED' => 'Y'
																				),

																			'Title' => GetMessage('CRM_BP_DEAL_PROPERTIES_5_TITLE')
																		)

																),
																Array
																(
																	'Type' => 'CodeActivity',
																	'Name' => 'A25978_79790_69359_41530',
																	'Properties' => Array
																		(
																			'ExecuteCode' => '$val = GetTime(time() + 60*60*24*30*12, \'SHORT\');
$rootActivity = $this->GetRootActivity();
$rootActivity->SetVariable(\'task_start\', $val);',
																			'Title' =>  GetMessage('CRM_BP_DEAL_PROPERTIES_18_TITLE')
																		)

																),
																Array
																(
																	'Type' => 'Task2Activity',
																	'Name' => 'A86492_82175_40458_32819',
																	'Properties' => Array
																		(
																			'Fields' => Array
																				(
																					'TITLE' => GetMessage('CRM_BP_DEAL_TASK_NAME'),
																					'CREATED_BY' => 'user_1',
																					'RESPONSIBLE_ID' => Array
																						(
																							'0' => 'Document',
																							'1' => 'ASSIGNED_BY_ID'
																						),

																					'ACCOMPLICES' => '',
																					'START_DATE_PLAN' => '{=Variable:task_start}',
																					'END_DATE_PLAN' => '',
																					'DEADLINE' => '',
																					'DESCRIPTION' => GetMessage('CRM_BP_DEAL_TASK_TEXT'),
																					'PRIORITY' => '0',
																					'GROUP_ID' => '0',
																					'ALLOW_CHANGE_DEADLINE' => 'Y',
																					'TASK_CONTROL' => 'Y',
																					'ADD_IN_REPORT' => 'Y',
																					'AUDITORS' => '',
																					'UF_CRM_TASK' => Array
																						(
																							'0' => 'D_{=Document:ID}',
																						)
																				),
																			'HoldToClose' => '0',
																			'Title' =>  GetMessage('CRM_BP_DEAL_PROPERTIES_19_TITLE')
																		)

																)

														)

												)

										)

								)

						)

				)

		),

	'PARAMETERS' => array(),
	'VARIABLES' => array(
			'task_start' => Array
				(
					'Name' => GetMessage('CRM_BP_DEAL_PROPERTIES_20_TITLE'),
					'Description' => '',
					'Type' => 'string',
					'Required' => '0',
					'Multiple' => '0',
					'Default_printable' => '',
					'Default' => ''
				)

		)
);

?>