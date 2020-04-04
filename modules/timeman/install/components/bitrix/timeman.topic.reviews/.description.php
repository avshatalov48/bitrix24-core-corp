<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
	"NAME" => GetMessage("FORUM_TOPIC_REVIEWS"),
	"DESCRIPTION" => GetMessage("FORUM_TOPIC_REVIEWS_DESCRIPTION"),
	"ICON" => "/images/icon.gif",
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "content",
		"CHILD" => array(
			"ID" => "TASKS",
			"NAME" => GetMessage("FORUM")
		)
	),
);
?>