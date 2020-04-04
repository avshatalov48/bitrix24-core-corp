<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arParameters = Array(
		"PARAMETERS"=> Array(
			"DETAIL_URL"=> Array(
				"NAME" => GetMessage("GD_CONTACTS_P_DETAIL_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/extranet/contacts/personal/personal/user/#ID#/",
			),
			"MESSAGES_CHAT_URL"=> Array(
				"NAME" => GetMessage("GD_CONTACTS_P_MESSAGES_CHAT_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/extranet/contacts/personal/messages/chat/#ID#/",
			),
			"FULLLIST_URL"=> Array(
				"NAME" => GetMessage("GD_CONTACTS_P_FULLLIST_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/extranet/contacts/",
			),
			"EMPLOYEES_FULLLIST_URL"=> Array(
				"NAME" => GetMessage("GD_CONTACTS_P_EMPLOYEES_FULLLIST_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/extranet/contacts/employees.php",
			),
		),
		"USER_PARAMETERS"=> Array(
			"MY_WORKGROUPS_USERS_COUNT" => Array(
				"NAME" => GetMessage("GD_CONTACTS_P_WGU_COUNT"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "5",
			),
			"PUBLIC_USERS_COUNT" => Array(
				"NAME" => GetMessage("GD_CONTACTS_P_PU_COUNT"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "5",
			),

		),
	);

?>