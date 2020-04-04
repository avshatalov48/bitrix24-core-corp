<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
return array(
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array("ID" => "NEW", "TOTAL" => "65"),
						array("ID" => "IN_PROCESS", "TOTAL" => "50"),
						array("ID" => "PROCESSED", "TOTAL" => "35"),
						array("ID" => "CONVERTED", "TOTAL" => "5")
					),
					"valueField" => "TOTAL",
					"titleField" => "NAME"
				)
			),
			array(
				"data" => array(
					"items" => array(
						array("SOURCE_ID" => "SELF", "COUNT" => "15"),
						array("SOURCE_ID" => "CALL", "COUNT" => "15"),
						array("SOURCE_ID" => "WEB", "COUNT" => "15"),
						array("SOURCE_ID" => "EMAIL", "COUNT" => "15"),
						array("SOURCE_ID" => "OTHER", "COUNT" => "5")
					),
					"valueField" => "COUNT",
					"titleField" => "SOURCE",
					"identityField" => "SOURCE_ID"
				)
			)
		)
	),
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array("name" => "qty_inwork", "value" => "65"),
						array("name" => "qty_success", "value" => "5"),
						array("name" => "qty_fail", "value" => "10")
					)
				)
			),
			array(
				array(
					"data" => array(
						"items" => array(
							array("name" => "rate_success", "value" => "7.7")
						)
					)
				),
				array(
					"data" => array(
						"items" => array(
							array("name" => "rate_fail", "value" => "15.4")
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
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "DATE",
							"graphs" => array(
								array(
									"name" => "rate_success",
									"selectField" => "SUCCESS"
								),
								array(
									"name" => "rate_fail",
									"selectField" => "FAIL"
								)
							),
							"values" => array(
								array("DATE" => "2015-05-01", "SUCCESS" => "1", "FAIL" => "2"),
								array("DATE" => "2015-05-02", "SUCCESS" => "1", "FAIL" => "2"),
								array("DATE" => "2015-05-03", "SUCCESS" => "1", "FAIL" => "2"),
								array("DATE" => "2015-05-04", "SUCCESS" => "1", "FAIL" => "2"),
								array("DATE" => "2015-05-05", "SUCCESS" => "1", "FAIL" => "2")
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
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "USER",
							"graphs" => array(
								array(
									"name" => "qty_inwork",
									"selectField" => "QTY_INWORK_QTY"
								),
								array(
									"name" => "qty_activity",
									"selectField" => "QTY_ACTIVITY_QTY"
								),
								array(
									"name" => "qty_success",
									"selectField" => "QTY_SUCCESS_QTY"
								),
								array(
									"name" => "qty_fail",
									"selectField" => "QTY_FAIL_QTY"
								),
								array(
									"name" => "qty_idle",
									"selectField" => "QTY_IDLE_COUNT"
								)
							),
							"values" => array(
								array(
									"USER" => GetMessage("CRM_LEAD_WGT_DATA_EMPLOYEE_1"),
									"QTY_INWORK_QTY" => "30",
									"QTY_ACTIVITY_QTY" => "60",
									"QTY_SUCCESS_QTY"=> "2",
									"QTY_FAIL_QTY" => "3",
									"QTY_IDLE_COUNT" => "2"
								),
								array(
									"USER" => GetMessage("CRM_LEAD_WGT_DATA_EMPLOYEE_2"),
									"QTY_INWORK_QTY" => "20",
									"QTY_ACTIVITY_QTY" => "40",
									"QTY_SUCCESS_QTY"=> "3",
									"QTY_FAIL_QTY" => "6",
									"QTY_IDLE_COUNT" => "3"
								),
								array(
									"USER" => GetMessage("CRM_LEAD_WGT_DATA_EMPLOYEE_3"),
									"QTY_INWORK_QTY" => "15",
									"QTY_ACTIVITY_QTY" => "30",
									"QTY_SUCCESS_QTY"=> "2",
									"QTY_FAIL_QTY" => "1",
									"QTY_IDLE_COUNT" => "1"
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
							"name" => "qty_success",
							"nomineeId" => "1",
							"positions" => array(
								array("id" => "2", "value" => "1", "legend" => "3"),
								array("id" => "1", "value" => "2", "legend" => "2"),
								array("id" => "3", "value" => "3", "legend" => "1")
							)
						),
					)
				)
			)
		)
	)
);
