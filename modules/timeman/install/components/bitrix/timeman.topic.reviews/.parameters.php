<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"FORUM_ID" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("REPORT_FORUM_ID_COMMENT_MESS"),
			"TYPE" => "STRING",
			"DEFAULT" =>"",
			),
		"REPORT_ID" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("REPORT_ID_MESS"),
			"TYPE" => "STRING",
			"DEFAULT" => '={$_REQUEST["ID"]}'),
		"CACHE_TIME" => Array()
	)
);
?>
