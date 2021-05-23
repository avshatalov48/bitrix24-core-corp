<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
use Bitrix\Main\Type\Date;
/**
 * @var $commonFilter \Bitrix\Crm\Widget\Filter
 * @var $end Bitrix\Main\Type\Date
 */
define("crm.channel_tracker.demo", "Y");
$period = $commonFilter->getPeriod();
if (is_null($period["START"]))
{
	$period["START"] = clone $period['END'];
	$period["START"]->add("-10 days");
}
$pointer = clone $period['START'];
$dynamic = array();
$sales = array();
$templateFolder = "/bitrix/components/bitrix/crm.channel_tracker/templates/.default/images/";
$days = 0;
$PARAM3_TOTAL_QTY = 0;
$PARAM4_TOTAL_QTY = 0;
while ($pointer->getTimestamp() <= $period["END"]->getTimestamp())
{
	$d = array(
		"DATE" => $pointer->format("Y-m-d"),
		"PARAM3_TOTAL_QTY" => rand(3, 15),
		"PARAM4_TOTAL_QTY" => rand(5, 18)
	);
	$PARAM3_TOTAL_QTY += $d["PARAM3_TOTAL_QTY"];
	$PARAM4_TOTAL_QTY += $d["PARAM4_TOTAL_QTY"];

	$dynamic[] = $d;
	if ($pointer->getTimestamp() == $period["END"]->getTimestamp())
		$sale = 24000;
	else
		$sale = rand(10000, 30000);

	$sum += $sale;
	$days++;
	$sales[] = array(
		"DATE" => $pointer->format("Y-m-d"),
		"DEAL_SUCCESS_SUM_TOTAL" => $sale,
	);
	$pointer->add("1 day");
};
$user = array(
	"m1" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_M_1"),
		"USER_ID" => "m1",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-man-1.png",
			"width" => "98",
			"height" => "98",
			"size" => "6404",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(24, 0),
			"DEAL" => Array(6, 0),
			"COMPANY" => Array(6, 0),
			"CONTACT" => Array(3, 0)
		)
	),
	"m2" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_M_2"),
		"USER_ID" => "m2",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-man-2.png",
			"width" => "98",
			"height" => "98",
			"size" => "6472",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(2, 7),
			"DEAL" => Array(8, 3),
			"COMPANY" => Array(6, 0),
			"CONTACT" => Array(3, 5)
		)
	),
	"m3" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_M_3"),
		"USER_ID" => "m3",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-man-3.png",
			"width" => "98",
			"height" => "98",
			"size" => "6388",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(4, 16),
			"DEAL" => Array(1, 8),
			"COMPANY" => Array(3, 5),
			"CONTACT" => Array(0, 6)
		)
	),
	"m4" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_M_4"),
		"USER_ID" => "m4",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-man-4.png",
			"width" => "98",
			"height" => "98",
			"size" => "5724",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(4, 7),
			"DEAL" => Array(6, 5),
			"COMPANY" => Array(6, 20),
			"CONTACT" => Array(13, 0)
		)
	),
	"f1" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_F_1"),
		"USER_ID" => "f1",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-girl-1.png",
			"width" => "98",
			"height" => "98",
			"size" => "7085",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(0, 12),
			"DEAL" => Array(5, 6),
			"COMPANY" => Array(1, 2),
			"CONTACT" => Array(3, 0)
		)
	),
	"f2" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_F_2"),
		"USER_ID" => "f2",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-girl-2.png",
			"width" => "98",
			"height" => "98",
			"size" => "6425",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(10, 7),
			"DEAL" => Array(6, 6),
			"COMPANY" => Array(1, 5),
			"CONTACT" => Array(3, 0)
		)
	),
	"f3" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_F_3"),
		"USER_ID" => "f3",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-girl-3.png",
			"width" => "98",
			"height" => "98",
			"size" => "7465",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(22, 1),
			"DEAL" => Array(6, 7),
			"COMPANY" => Array(45, 15),
			"CONTACT" => Array(32, 10)
		)
	),
	"f4" => array(
		"USER" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_F_4"),
		"USER_ID" => "f4",
		"USER_PHOTO" => array(
			"src" => $templateFolder."/avatar-girl-4.png",
			"width" => "98",
			"height" => "98",
			"size" => "8541",
		),
		"DEAL_SUCCESS_SUM_TOTAL" => 0,
		"WORK_POSITION" => GetMessage("CRM_CH_WGT_DATA_EMPLOYEE_WORK_POSITION"),
		"VALUE" => Array(
			"LEAD" => Array(6, 8),
			"DEAL" => Array(0, 4),
			"COMPANY" => Array(0, 10),
			"CONTACT" => Array(1, 2)
		)
	),
);
$sellers = array($user["m1"], $user["f1"], $user["f2"], $user["f3"], $user["m2"], $user["m3"]);
$average = $sum / count($sellers);
$PARAM3_TOTAL_QTY_A = $PARAM3_TOTAL_QTY / count($sellers);
$PARAM4_TOTAL_QTY_A = $PARAM4_TOTAL_QTY / count($sellers);
foreach ($sellers as $k => $seller)
{
	$sellers[$k]["DEAL_SUCCESS_SUM_TOTAL"] = rand(0, $average);
	$sum -= $sellers[$k]["DEAL_SUCCESS_SUM_TOTAL"];
	$sellers[$k]["PARAM3_TOTAL_QTY"] = rand($PARAM3_TOTAL_QTY_A*0.75, $PARAM3_TOTAL_QTY_A);
	$PARAM3_TOTAL_QTY -= $sellers[$k]["PARAM3_TOTAL_QTY"];
	$sellers[$k]["PARAM4_TOTAL_QTY"] = rand($PARAM4_TOTAL_QTY_A*0.75, $PARAM4_TOTAL_QTY_A);
	$PARAM4_TOTAL_QTY -= $sellers[$k]["PARAM4_TOTAL_QTY"];
}
$average = round($sum / count($sellers));
$PARAM3_TOTAL_QTY_A = round($PARAM3_TOTAL_QTY / count($sellers));
$PARAM4_TOTAL_QTY_A = round($PARAM4_TOTAL_QTY / count($sellers));
foreach ($sellers as $k => $seller)
{
	$sellers[$k]["DEAL_SUCCESS_SUM_TOTAL"] += $average;
	$sellers[$k]["PARAM3_TOTAL_QTY"] += $PARAM3_TOTAL_QTY_A;
	$sellers[$k]["PARAM4_TOTAL_QTY"] += $PARAM4_TOTAL_QTY_A;
}

$demoData = array();
$demoData[] = array(
	"cells" => array(
		array(
			"data" => array(
				"items" => array()
			)
		),
	)
);
$demoData[] = array(
	"cells" => array(
		array(
			"data" => array(
				"items" => array(
					array(
						"graphs" => array(
							array(
								"name" => "param1",
								"selectField" => "PARAM1_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "blue",
								)
							),
							array(
								"name" => "param2",
								"selectField" => "PARAM2_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "green",
								)
							),
							array(
								"name" => "param3",
								"selectField" => "PARAM3_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "yellow",
								)
							),
							array(
								"name" => "param4",
								"selectField" => "PARAM4_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "red",
								)
							)
						),
						"groupField" => "DATE",
						"values" => $dynamic
					)
				),
				"dateFormat" => "YYYY-MM-DD"
			)
		),
		array(
			"data" => array(
				"items" => array(
					array(
						"graphs" => array(
							array(
								"name" => "param3",
								"selectField" => "PARAM3_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "yellow",
								)
							),
							array(
								"name" => "param4",
								"selectField" => "PARAM4_TOTAL_QTY",
								"display" => array(
									"colorScheme" => "red",
								)
							)
						),
						"groupField" => "USER",
						"values" => $sellers

					)

				),
				"dateFormat" => "YYYY-MM-DD",
			)
		)
	)
);
$demoData[] = array(
	"cells" => array(
		array(
			array(
				"data" => array(
					"items" => array(
						array(
							"name" => "client_success1",
							"value" => "13",
						)
					)
				)
			)
		),
		array(
			array(
				"data" => array(
					"items" => array(
						array(
							"name" => "client_success1",
							"value" => "124",
						)
					)
				)
			)
		)
	)
);
$demoData[] = array(
	"cells" => array(
		array(
			"data" => array(
				"items" => array($user["f1"], $user["m2"], $user["f4"], $user["m4"], $user["f2"]),
				"attributes" => array(
					"isConfigurable" => false
				)
			)
		)
	)
);
if (!Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") ||
	(Bitrix\Main\Loader::includeModule("bitrix24") && Bitrix\Bitrix24\Feature::isFeatureEnabled("crm_sale_target")))
{
	$demoData[] = array(
		"cells" => array(
			array(
				"data" => array(
					"items" => array(
						array(
							'configuration' => array(
								'id' => 0,
								'type' => 'COMPANY',
								'period' => array(
									'type' => 'M',
									'year' => (int)date('Y'),
									'half' => null,
									'quarter' => null,
									'month' => (int)date('n')
								),
								'target' => array(
									'type' => 'S',
									'goal' => array('COMPANY' => 500000),
									'totalGoal' => 500000
								),
								'isDemo' => true
							),
							'current' => array('COMPANY' => 375000),
							'totalCurrent' => 375000
						)
					),
					"attributes" => array(
						"isConfigurable" => false
					)
				)
			)
		)
	);
}
$demoData[] = array(
	"cells" => array(
		array(
			"data" => array(
				"items" => array(
					array(
						"graphs" => array(
							array(
								"name" => "deal_success",
								"title" => "deal_success",
								"selectField" => "DEAL_SUCCESS_SUM_TOTAL",
								"display" => array(
									"colorScheme" => "green",
									"graph" => array(
										"clustered" => "Y",
									)
								)
							)
						),
						"groupField" => "USER",
						"values" => $sellers
					)
				), "dateFormat" => "YYYY-MM-DD",
			)
		)
	),
	"height" => "380",
);

return $demoData;