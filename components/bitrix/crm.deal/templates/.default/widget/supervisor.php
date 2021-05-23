<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
return array(
	array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array("ID" => "NEW", "TOTAL" => "65"),
						array("ID" => "PREPARATION", "TOTAL" => "50"),
						array("ID" => "PREPAYMENT_INVOICE", "TOTAL" => "35"),
						array("ID" => "EXECUTING", "TOTAL" => "20"),
						array("ID" => "WON", "TOTAL" => "5")
					),
					"valueField" => "TOTAL",
					"titleField" => "NAME"
				)
			),
			array(
				"data" => array(
					"items" => array(
						array("name" => "sum1", "value" => "300000"),
						array("name" => "sum2", "value" => "55000"),
						array("name" => "diff", "value" => "245000")
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
									"name" => "qty1",
									"selectField" => "QTY1_COUNT"
								),
								array(
									"name" => "qty2",
									"selectField" => "QTY2_CALL_QTY"
								),
								array(
									"name" => "qty3",
									"selectField" => "QTY3_MEETING_QTY"
								),
								array(
									"name" => "qty4",
									"selectField" => "QTY4_EMAIL_QTY"
								),
								array(
									"name" => "qty5",
									"selectField" => "QTY5_COUNT"
								)
							),
							"values" => array(
								array(
									"USER" => GetMessage("CRM_DEAL_WGT_DATA_EMPLOYEE_1"),
									"QTY1_COUNT" => "17",
									"QTY2_CALL_QTY" => "90",
									"QTY3_MEETING_QTY"=> "6",
									"QTY4_EMAIL_QTY" => "12",
									"QTY5_COUNT" => "1"
								),
								array(
									"USER" => GetMessage("CRM_DEAL_WGT_DATA_EMPLOYEE_2"),
									"QTY1_COUNT" => "19",
									"QTY2_CALL_QTY" => "85",
									"QTY3_MEETING_QTY"=> "7",
									"QTY4_EMAIL_QTY" => "17",
									"QTY5_COUNT" => "2"
								),
								array(
									"USER" => GetMessage("CRM_DEAL_WGT_DATA_EMPLOYEE_3"),
									"QTY1_COUNT" => "22",
									"QTY2_CALL_QTY" => "125",
									"QTY3_MEETING_QTY"=> "2",
									"QTY4_EMAIL_QTY" => "6",
									"QTY5_COUNT" => "2"
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
						array("name" => "sum1", "value" => "50000"),
						array("name" => "sum2", "value" => "5000"),
						array("name" => "sum3", "value" => "55000")
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
									"name" => "sum1",
									"selectField" => "TOTAL_INVOICE_SUM",
									"display" => array(
										"graph" => array("clustered" => "N"),
										"colorScheme" => "green"
									)
								),
								array(
									"name" => "sum2",
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
		"cells" => array(
			array(
				"data" => array(
					"dateFormat" => "YYYY-MM-DD",
					"items" => array(
						array(
							"groupField" => "DATE",
							"graphs" => array(
								array(
									"name" => "qty1",
									"selectField" => "Q1_COUNT"
								),
								array(
									"name" => "qty2",
									"selectField" => "Q2_CALL_QTY"
								),
								array(
									"name" => "qty3",
									"selectField" => "Q3_TOTAL"
								)
							),
							"values" => array(
								array("DATE" => "2015-05-01", "Q1_COUNT" => "7", "Q2_CALL_QTY" => "45", "Q3_TOTAL" => "55"),
								array("DATE" => "2015-05-02", "Q1_COUNT" => "12", "Q2_CALL_QTY" => "60", "Q3_TOTAL" => "70"),
								array("DATE" => "2015-05-03", "Q1_COUNT" => "20", "Q2_CALL_QTY" => "75", "Q3_TOTAL" => "85"),
								array("DATE" => "2015-05-04", "Q1_COUNT" => "14", "Q2_CALL_QTY" => "65", "Q3_TOTAL" => "75"),
								array("DATE" => "2015-05-05", "Q1_COUNT" => "6", "Q2_CALL_QTY" => "55", "Q3_TOTAL" => "65")
							)
						)
					)
				)
			),
			array(
				"data" => array(
					"items" => array(
						array("name" => "qty1", "value" => "59"),
						array("name" => "qty2", "value" => "350"),
						array("name" => "qty3", "value" => "300")
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
						array("name" => "qty1", "value" => "1")
					)
				)
			)
		)
	)
);
