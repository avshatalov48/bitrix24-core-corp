<?
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Type\Collection;
use Bitrix\Socialnetwork\UserToGroupTable;
use Bitrix\Socialnetwork\WorkgroupFavoritesTable;
use Bitrix\Socialnetwork\WorkgroupSiteTable;
use Bitrix\Socialnetwork\WorkgroupTable;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userId = $GLOBALS["USER"]->getId();
$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600;
$cacheId = "bitrix24_group_list_".$userId."_".SITE_ID."_".isModuleInstalled("extranet");
$cacheDir = "/bx/bitrix24_group_list/".$userId;
$cache = new CPHPCache;

if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
{
	return $cache->getVars();
}

$cache->startDataCache();

if (defined("BX_COMP_MANAGED_CACHE"))
{
	$GLOBALS["CACHE_MANAGER"]->startTagCache($cacheDir);
	$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_user2group_U".$userId);
	$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_group");
	$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_group_favorites_U".$userId);
	$GLOBALS["CACHE_MANAGER"]->endTagCache();
}

$groups = array();
$userId = $GLOBALS["USER"]->getId();

if (!CModule::IncludeModule("socialnetwork") || $userId <= 0)
{
	return $groups;
}

$extranetSiteId = COption::GetOptionString("extranet", "extranet_site");

$getGroups = function($siteId, $limit, $ids = array()) use($userId, $extranetSiteId)
{
	$groups = WorkgroupTable::getList(array(
		"filter" => array(
			"=ACTIVE" => "Y",
			"!=CLOSED" => "Y",
			"=GS.SITE_ID" => $siteId,
			"<=UG.ROLE" => UserToGroupTable::ROLE_USER,
			'!=TYPE' => \Bitrix\Socialnetwork\Item\Workgroup\Type::Collab->value,
		) + (empty($ids) ? array() : array("!@ID" => $ids)),
		"order" => array(
			"NAME" => "ASC"
		),
		"select" => array("ID", "NAME"),

		"count_total" => false,
		"offset" => 0,
		"limit" => $limit,

		"runtime" => array(
			new ReferenceField(
				"UG",
				UserToGroupTable::getEntity(),
				array(
					"=ref.GROUP_ID" => "this.ID",
					"=ref.USER_ID" => new SqlExpression($userId)
				),
				array("join_type" => "INNER")
			),
			new ReferenceField(
				"GS",
				WorkgroupSiteTable::getEntity(),
				array(
					"=ref.GROUP_ID" => "this.ID"
				),
				array("join_type" => "INNER")
			)
		)
	));

	$result = array();
	while ($group = $groups->fetch())
	{
		$group["EXTRANET"] = $siteId === $extranetSiteId;
		$group["FAVORITE"] = false;
		$result[$group["ID"]] = $group;
	}

	return $result;
};

$getFavorites = function($siteId, $limit, $ids = array()) use($userId, $extranetSiteId)
{
	$groups = WorkgroupTable::getList(array(
		"filter" => array(
			"=GF.USER_ID" => $userId,
			"=GS.SITE_ID" => $siteId,
			array(
				"LOGIC" => "OR",
				"=VISIBLE" => "Y",
				"<=UG.ROLE" => UserToGroupTable::ROLE_USER
			)
		) + (empty($ids) ? array() : array("!@ID" => $ids)),
		"order" => array(
			"NAME" => "ASC"
		),
		"select" => array("ID", "NAME"),

		"count_total" => false,
		"offset" => 0,
		"limit" => $limit,

		"runtime" => array(
			new ReferenceField(
				"UG",
				UserToGroupTable::getEntity(),
				array(
					"=ref.GROUP_ID" => "this.ID",
					"=ref.USER_ID" => new SqlExpression($userId)
				),
				array("join_type" => "LEFT")
			),
			new ReferenceField(
				"GS",
				WorkgroupSiteTable::getEntity(),
				array(
					"=ref.GROUP_ID" => "this.ID"
				),
				array("join_type" => "INNER")
			),
			new \Bitrix\Main\Entity\ReferenceField(
				"GF",
				WorkgroupFavoritesTable::getEntity(),
				array(
					"=ref.GROUP_ID" => "this.ID"
				),
				array("join_type" => "INNER")
			)
		)
	));

	$result = array();
	while ($group = $groups->fetch())
	{
		$group["EXTRANET"] = $siteId === $extranetSiteId;
		$group["FAVORITE"] = true;
		$result[$group["ID"]] = $group;
	}

	return $result;

};

$extranetGroups = isModuleInstalled("extranet") ? $getGroups($extranetSiteId, 150) : array();
$intranetGroups = $getGroups(SITE_ID, 150, array_keys($extranetGroups));
$favoriteExtranetGroups = isModuleInstalled("extranet") ? $getFavorites($extranetSiteId, 100) : array();
$favoriteIntranetGroups = $getFavorites(SITE_ID, 100, array_keys($favoriteExtranetGroups));

$groups = array_replace($extranetGroups, $intranetGroups, $favoriteExtranetGroups, $favoriteIntranetGroups);
Collection::sortByColumn($groups, "NAME");
$cache->endDataCache($groups);

return $groups;