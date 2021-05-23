<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
return array(
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array("ID" => "N", "TOTAL" => "65"),
						array("ID" => "S", "TOTAL" => "85"),
						array("ID" => "P", "TOTAL" => "20")
					),
					"valueField" => "TOTAL",
					"titleField" => "NAME"
				)
			),
			array(
				"data" => array(
					"items" => array(
						array("name" => "sum_inwork", "value" => "50000"),
						array("name" => "sum_success", "value" => "35000"),
						array("name" => "sum_owed", "value" => "15000")
					)
				)
			)
		)
	),
	array(
		"cells" => array(
			array(
				array(
					"data" => array(
						"items" => array(
							array("name" => "qty_overdue", "value" => "3")
						)
					)
				),
				array(
					"data" => array(
						"items" => array(
							array("name" => "sum_overdue", "value" => "5000")
						)
					)
				)
			),
			array(
				"data" => array(
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "DATE",
							"graphs" => array(
								array(
									"name" => "sum_total",
									"selectField" => "TOTAL_INVOICE_SUM",
									"display" => array(
										"graph" => array("clustered" => "N"),
										"colorScheme" => "green"
									)
								),
								array(
									"name" => "sum_owed",
									"selectField" => "TOTAL_OWED",
									"display" => array(
										"graph" => array("clustered" => "N"),
										"colorScheme" => "red"
									)
								)
							),
							"values" => array(
								array("DATE" => "2015-05-01", "TOTAL_INVOICE_SUM" => "6000", "TOTAL_OWED" => "1000"),
								array("DATE" => "2015-05-02", "TOTAL_INVOICE_SUM" => "8000", "TOTAL_OWED" => "1000"),
								array("DATE" => "2015-05-03", "TOTAL_INVOICE_SUM" => "12000", "TOTAL_OWED" => "1000"),
								array("DATE" => "2015-05-04", "TOTAL_INVOICE_SUM" => "14000", "TOTAL_OWED" => "1000"),
								array("DATE" => "2015-05-05", "TOTAL_INVOICE_SUM" => "10000", "TOTAL_OWED" => "1000")
							)
						)
					)
				)
			)
		)
	),
	array(
		'cells' => array(
			array(
				"data" => array(
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "DATE",
							"graphs" => array(
								array(
									"name" => "sum_in_work",
									"selectField" => "SUM_IN_WORK_SUM_TOTAL"
								),
								array(
									"name" => "sum_successful",
									"selectField" => "SUM_SUCCESSFUL_SUM_TOTAL"
								),
								array(
									"name" => "sum_overdue",
									"selectField" => "SUM_OVERDUE_SUM_TOTAL"
								),
								array(
									"name" => "sum_owed",
									"selectField" => "SUM_OWED_SUM_TOTAL"
								)
							),
							"values" => array(
								array(
									"DATE" => "2015-05-01",
									"SUM_IN_WORK_SUM_TOTAL" => "10000",
									"SUM_SUCCESSFUL_SUM_TOTAL" => "5000",
									"SUM_OVERDUE_SUM_TOTAL" => "4000",
									"SUM_OWED_SUM_TOTAL" => "5000"
								),
								array(
									"DATE" => "2015-05-02",
									"SUM_IN_WORK_SUM_TOTAL" => "20000",
									"SUM_SUCCESSFUL_SUM_TOTAL" => "3000",
									"SUM_OVERDUE_SUM_TOTAL" => "2000",
									"SUM_OWED_SUM_TOTAL" => "17000"
								),
								array(
									"DATE" => "2015-05-03",
									"SUM_IN_WORK_SUM_TOTAL" => "30000",
									"SUM_SUCCESSFUL_SUM_TOTAL" => "15000",
									"SUM_OVERDUE_SUM_TOTAL" => "8000",
									"SUM_OWED_SUM_TOTAL" => "15000"
								),
								array(
									"DATE" => "2015-05-04",
									"SUM_IN_WORK_SUM_TOTAL" => "25000",
									"SUM_SUCCESSFUL_SUM_TOTAL" => "10000",
									"SUM_OVERDUE_SUM_TOTAL" => "4000",
									"SUM_OWED_SUM_TOTAL" => "15000"
								),
								array(
									"DATE" => "2015-05-05",
									"SUM_IN_WORK_SUM_TOTAL" => "15000",
									"SUM_SUCCESSFUL_SUM_TOTAL" => "7000",
									"SUM_OVERDUE_SUM_TOTAL" => "2000",
									"SUM_OWED_SUM_TOTAL" => "8000"
								)
							)
						)
					)
				)
			)
		)
	),
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array(
							"name" => "sum_payed",
							"nomineeId" => "1",
							"positions" => array(
								array("id" => "2", "value" => "1", "legend" => "15000"),
								array("id" => "1", "value" => "2", "legend" => "12000"),
								array("id" => "3", "value" => "3", "legend" => "8000")
							)
						),
					)
				)
			)
		)
	)
);
