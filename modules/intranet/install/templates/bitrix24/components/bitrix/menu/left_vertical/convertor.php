<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$oldItemsSort = CUserOptions::GetOption("bitrix24", "user_menu_items_".SITE_ID);
$oldItemsAdded = CUserOptions::GetOption("bitrix24", "user_added_favorite_items_".SITE_ID);

$defaultItemsId = array_keys($newItems);
$convertMapItems = $arResult["MAP_ITEMS"];

$oldLinksMapping = array();
if (!IsModuleInstalled("bitrix24") && COption::GetOptionString("intranet", "intranet_public_converted", "") == "Y")
{
	$oldLinksMapping = array(
		crc32(SITE_DIR."company/absence.php") => "menu_absence",//crc32(SITE_DIR."timeman/"),
		crc32(SITE_DIR."company/work_report.php") => "menu_work_report",//crc32(SITE_DIR."timeman/work_report.php"),
		crc32(SITE_DIR."company/timeman.php") => "menu_timeman",//crc32(SITE_DIR."timeman/timeman.php"),
		crc32(SITE_DIR."services/meeting/") => "menu_meeting",//crc32(SITE_DIR."timeman/meeting/"),
		crc32(SITE_DIR."about/calendar.php") => "menu_company_calendar",//crc32(SITE_DIR."calendar/"),
		crc32(SITE_DIR."services/processes/") => "menu_processes",//crc32(SITE_DIR."bizproc/processes/"),
		//crc32(SITE_DIR."company/meeting/") => crc32(SITE_DIR."bizproc/bizproc/"),
	);
}

if (CModule::IncludeModule("socialnetwork"))
{
	$strGroupSubjectLinkTemplate = SITE_DIR."workgroups/group/#subject_id#/";

	$groups = CSocNetUserToGroup::GetList(
		array("GROUP_NAME" => "ASC"),
		array(
			"USER_ID" => $USER->GetID(),
			"<=ROLE" => SONET_ROLES_USER,
			"GROUP_ACTIVE" => "Y",
			"!GROUP_CLOSED" => "Y",
			"GROUP_SITE_ID" => SITE_ID
		),
		false,
		array("nTopCount" => 50),
		array("ID", "GROUP_ID", "GROUP_NAME", "GROUP_SITE_ID")
	);

	while ($group = $groups->GetNext())
	{
		$link = str_replace("#subject_id#", $group["GROUP_ID"], $strGroupSubjectLinkTemplate);
		$convertMapItems[] = array(
			"TEXT" => $group["GROUP_NAME"],
			"LINK" => $link,
			"PARAMS" => array(
				"menu_item_id" => crc32($link)
			)
		);
	}
}

$mapIds = array();
foreach ($convertMapItems as $index => $item)
{
	$mapIds[$item["PARAMS"]["menu_item_id"]] = $index;
}

$skipId = array("menu_absence", "menu_marketplace", "menu_employee", "menu_telephony_balance", "menu_bizproc", "menu_license", "menu_configs", "menu_openlines_lines");

$convertStandardItems = array();
$convertSortedItems = array();
if (is_array($oldItemsSort) && !empty($oldItemsSort))
{
	if (isset($oldItemsSort["menu-favorites"]) && is_array($oldItemsSort["menu-favorites"]) && !empty($oldItemsSort["menu-favorites"]))
	{
		foreach ($oldItemsSort["menu-favorites"] as $status => $items)
		{
			if (!in_array($status, array("show", "hide")))
				continue;

			foreach ($items as $id)
			{
				/*if (array_key_exists($id, $mapIds))
				{
					$convertSortedItems[$status][] = $id;
				}*/

				if (!is_array($oldItemsAdded) || !in_array($id, $oldItemsAdded))
				{
					continue;
				}

				/*check for new page links*/
				if (!empty($oldLinksMapping) && array_key_exists($id, $oldLinksMapping))
				{
					$id = $oldLinksMapping[$id];
				}

				if (!in_array($id, $defaultItemsId) && array_key_exists($id, $mapIds) && !in_array($id, $skipId))
				{
					$item = $convertMapItems[$mapIds[$id]];
					$convertStandardItems[] = array(
						"ID" => $id,
						"TEXT" => $item["TEXT"],
						"LINK" => $item["LINK"],
					);
				}
			}
		}

		if (!empty($convertStandardItems))
		{
			CUserOptions::SetOption("intranet", "left_menu_standard_items_".SITE_ID, $convertStandardItems);
		}

		/*if (!empty($convertSortedItems))
		{
			CUserOptions::SetOption("intranet", "left_menu_sorted_items_".SITE_ID, $convertSortedItems);
		}*/
	}
}
CUserOptions::SetOption("intranet", "left_menu_converted_".SITE_ID, "Y");

if (COption::GetOptionString("intranet", "left_menu_admin_converted", "N") !== "Y")
{
	$oldItemsAdmin = COption::GetOptionString("bitrix24", "admin_menu_items", "");
	$convertAdminItems = array();

	if (!empty($oldItemsAdmin))
	{
		$oldItemsAdmin = unserialize($oldItemsAdmin);
		if (is_array($oldItemsAdmin) && !empty($oldItemsAdmin))
		{
			foreach ($oldItemsAdmin as $id)
			{
				if (!in_array($id, $defaultItemsId) && array_key_exists($id, $mapIds))
				{
					$item = $convertMapItems[$mapIds[$id]];
					$convertAdminItems[] = array(
						"ID" => $id,
						"TEXT" => $item["TEXT"],
						"LINK" => $item["LINK"],
					);
				}
			}

			if (!empty($convertAdminItems))
			{
				COption::SetOptionString("intranet", "left_menu_items_to_all_".SITE_ID, serialize($convertAdminItems), false, SITE_ID);
			}
		}
	}

	COption::SetOptionString("intranet", "left_menu_admin_converted", "Y", false, SITE_ID);
}



