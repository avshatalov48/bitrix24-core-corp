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
	)
);
$sellers = array($user["m4"]);
$average = $sum / count($sellers);
$PARAM3_TOTAL_QTY_A = $PARAM3_TOTAL_QTY / count($sellers);
$PARAM4_TOTAL_QTY_A = $PARAM4_TOTAL_QTY / count($sellers);
$s = $sum;
foreach ($sellers as $k => $seller)
{
	$sellers[$k]["DEAL_SUCCESS_SUM_TOTAL"] = rand(0, $average);
	$s -= $sellers[$k]["DEAL_SUCCESS_SUM_TOTAL"];
	$sellers[$k]["PARAM3_TOTAL_QTY"] = rand($PARAM3_TOTAL_QTY_A*0.75, $PARAM3_TOTAL_QTY_A);
	$PARAM3_TOTAL_QTY -= $sellers[$k]["PARAM3_TOTAL_QTY"];
	$sellers[$k]["PARAM4_TOTAL_QTY"] = rand($PARAM4_TOTAL_QTY_A*0.75, $PARAM4_TOTAL_QTY_A);
	$PARAM4_TOTAL_QTY -= $sellers[$k]["PARAM4_TOTAL_QTY"];
}
$average = round($s / count($sellers));
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
			),
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
	),
	"height" => "380"
);

$demoData[] = array(
	"cells" => array(
		array(
			"data" => array(
				"items" => array($user["m4"]),
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
						"name" => "deal_success",
						"nomineeId" => "1",
						"positions" => array(
							array("id" => "2", "value" => "1", "legend" => 1.1 * $sum),
							array("id" => "1", "value" => "2", "legend" => $sum),
							array("id" => "3", "value" => "3", "legend" => 0.9 * $sum)
						)
					)
				)
			)
		)
	)
);

return $demoData;
