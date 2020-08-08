<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//You may customize user card fields to display
$arResult['USER_PROPERTY'] = array(
	"UF_DEPARTMENT",
);

//Code below searches for appropriate icon for search index item.
//All filenames should be lowercase.

//1
//Check if index item is information block element with property DOC_TYPE set.
//This property should be type list and we'll take it's values XML_ID as parameter
//iblock_doc_type_<xml_id>.png

//2
//When no such fle found we'll check for section attributes
//iblock_section_<code>.png
//iblock_section_<id>.png
//iblock_section_<xml_id>.png

//3
//Next we'll try to detect icon by "extention".
//where extension is all a-z between dot and end of title
//iblock_type_<iblock type id>_<extension>.png

//4
//If we still failed. Try to match information block attributes.
//iblock_iblock_<code>.png
//iblock_iblock_<id>.png
//iblock_iblock_<xml_id>.png

//5
//If indexed item is section when checkj for
//iblock_section.png
//If it is an element when chek for
//iblock_element.png

//6
//If item belongs to main module (static file)
//when check is done by it's extention
//main_<extention>.png

//7
//For blog module we'll check if icon for post or user exists
//blog_post.png
//blog_user.png

//8, 9 and 10
//forum_message.png
//intranet_user.png
//socialnetwork_group.png

//11
//In case we still failed to find an icon
//<module_id>_default.png

//12
//default.png

$arIBlocks = array();

$image_path = $this->GetFolder()."/images/";
$abs_path = $_SERVER["DOCUMENT_ROOT"].$image_path;
require_once($_SERVER["DOCUMENT_ROOT"].$this->GetFolder().'/class.php');

for($i = 0; $i < $arParams["NUM_CATEGORIES"]; $i++)
{
	$categoryCode = $arParams["CATEGORY_".$i];

	if (is_array($categoryCode))
	{
		$categoryCode = $categoryCode[0];
	}

	$categoryTitle = trim($arParams["CATEGORY_".$i."_TITLE"]);
	if(empty($categoryTitle))
		continue;

	switch($categoryCode)
	{
		case "custom_users":
			$prefix = 'U';
			break;
		case "custom_sonetgroups":
			$prefix = 'G';
			break;
		case "custom_menuitems":
			$prefix = 'M';
			break;
		default:
			$prefix = '';
	}

	$arResult["CATEGORIES_ALL"][$i] = array(
		"TITLE" => htmlspecialcharsbx($categoryTitle),
		"CODE" => $categoryCode,
		"CLIENTDB_PREFIX" => $prefix
	);
}

if (!empty($arResult["query"]))
{
	if (!empty($_REQUEST["get_all"]))
	{
		$entitiesList = array();
		$entity = $_REQUEST["get_all"];
		if ($entity == 'sonetgroups')
		{
			$sonetGroupsList = CB24SearchTitle::getSonetGroups();
			foreach($sonetGroupsList as $group)
			{
				$entitiesList['G'.$group['ID']] = CB24SearchTitle::convertAjaxToClientDb($group, $entity);
			}
		}
		elseif ($entity == 'menuitems')
		{
			$menuItemsList = CB24SearchTitle::getMenuItems();
			foreach($menuItemsList as $menuItem)
			{
				$entitiesList['M'.$menuItem['URL']] = CB24SearchTitle::convertAjaxToClientDb($menuItem, $entity);
			}
		}

		$arResult['ALLENTITIES'] = $entitiesList;
	}

	$arResult["customUsersCategoryId"] = $arResult["customSonetGroupsCategoryId"] = false;
	$arResult["customResultEmpty"] = true;

	$searchString = ($arResult["alt_query"] ? $arResult["alt_query"] : $arResult["query"]);

	CB24SearchTitle::customSearch($searchString, $arParams, $arResult);

	if (
		$arResult["customResultEmpty"]
		&& $searchString == $arResult["alt_query"]
		&& $arResult["alt_query"] != $arResult["query"]
	) // if alt_query is guessed by mistake
	{
		CB24SearchTitle::customSearch($arResult["query"], $arParams, $arResult);
	}

	unset($arResult["customResultEmpty"]);

	for($i = 0; $i < $arParams["NUM_CATEGORIES"]; $i++)
	{
		$categoryCode = $arParams["CATEGORY_".$i];

		if (is_array($categoryCode))
		{
			$categoryCode = $categoryCode[0];
		}

		if (
			mb_strpos($categoryCode, 'custom_') === 0
			&& empty($arResult["CATEGORIES"][$i]["ITEMS"])
		)
		{
			unset($arResult["CATEGORIES"][$i]);
		}
	}

	if (
		!empty($arResult["CATEGORIES"]["others"])
		&& !empty($arResult["CATEGORIES"]["others"]["ITEMS"])
	)
	{
		foreach($arResult["CATEGORIES"]["others"]["ITEMS"] as $itemId => $arItem)
		{
			if (
				intval($arResult["customUsersCategoryId"]) > 0
				&& !empty($arResult["CATEGORIES"][$arResult["customUsersCategoryId"]]["ITEMS"])
				&& $arItem['MODULE_ID'] == 'intranet'
				&& preg_match('/^U(\d+)$/i', $arItem['ITEM_ID'], $matches)
			)
			{
				foreach($arResult["CATEGORIES"][$arResult["customUsersCategoryId"]]["ITEMS"] as $arUserItem)
				{
					if ($arItem['ITEM_ID'] == $arUserItem['ITEM_ID'])
					{
						unset($arResult["CATEGORIES"]["others"]["ITEMS"][$itemId]);
						break;
					}
				}
			}

			if (
				intval($arResult["customSonetGroupsCategoryId"]) > 0
				&& !empty($arResult["CATEGORIES"][$arResult["customSonetGroupsCategoryId"]]["ITEMS"])
				&& $arItem['MODULE_ID'] == 'socialnetwork'
				&& preg_match('/^G(\d+)$/i', $arItem['ITEM_ID'], $matches)
			)
			{
				foreach($arResult["CATEGORIES"][$arResult["customSonetGroupsCategoryId"]]["ITEMS"] as $arSonetGroupItem)
				{
					if ($arItem['ITEM_ID'] == $arSonetGroupItem['ITEM_ID'])
					{
						unset($arResult["CATEGORIES"]["others"]["ITEMS"][$itemId]);
						break;
					}
				}
			}
		}

		$arResult["CATEGORIES"]["others"]["ITEMS"] = array_values($arResult["CATEGORIES"]["others"]["ITEMS"]);
		if (empty($arResult["CATEGORIES"]["others"]["ITEMS"]))
		{
			unset($arResult["CATEGORIES"]["others"]);
		}

		foreach($arResult["CATEGORIES"] as $code => $category)
		{
			if (in_array($code, array('all', 'custom_users', 'custom_sonetgroups', 'custom_menugroups')))
			{
				continue;
			}

			if (
				!empty($arResult["CATEGORIES"][$code]["ITEMS"])
				&& is_array($arResult["CATEGORIES"][$code]["ITEMS"])
			)
			{
				foreach($arResult["CATEGORIES"][$code]["ITEMS"] as $key => $item)
				{
					if (isset($item["URL"]))
					{
						$arResult["CATEGORIES"][$code]["ITEMS"][$key]["URL"] = htmlspecialcharsBack($item["URL"]);
					}
				}
			}
		}
	}
}

unset($arResult["customUsersCategoryId"]);
unset($arResult["customSonetGroupsCategoryId"]);

$arResult["SEARCH"] = array();
foreach($arResult["CATEGORIES"] as $category_id => $arCategory)
{
	foreach($arCategory["ITEMS"] as $i => $arItem)
	{
		if(isset($arItem["ITEM_ID"]))
		{
			$arResult["SEARCH"][] = &$arResult["CATEGORIES"][$category_id]["ITEMS"][$i];
		}
	}
}

foreach($arResult["SEARCH"] as $i=>$arItem)
{
	if (!isset($arResult["SEARCH"][$i]["ICON"]))
	{
		$arResult["SEARCH"][$i]["ICON"] = '';
	}
}
?>