<?
function GetGlobalID()
{
	global $GLOBAL_IBLOCK_ID;
	global $GLOBAL_FORUM_ID;
	global $GLOBAL_BLOG_GROUP;
	global $GLOBAL_STORAGE_ID;
	$ttl = 2592000;
	$cache_id = 'id_to_code_';
	$cache_dir = '/bx/code';
	$obCache = new CPHPCache;

	if ($obCache->InitCache($ttl, $cache_id, $cache_dir))
	{
		$tmpVal = $obCache->GetVars();
		$GLOBAL_IBLOCK_ID = $tmpVal['IBLOCK_ID'];
		$GLOBAL_FORUM_ID = $tmpVal['FORUM_ID'];
		$GLOBAL_BLOG_GROUP = $tmpVal['BLOG_GROUP'];
		$GLOBAL_STORAGE_ID = $tmpVal['STORAGE_ID'];

		unset($tmpVal);
	}
	else
	{
		if (CModule::IncludeModule("iblock"))
		{
			$res = CIBlock::GetList(
				Array(),
				Array("CHECK_PERMISSIONS" => "N")
			);

			while ($ar_res = $res->Fetch())
			{
				$GLOBAL_IBLOCK_ID[$ar_res["CODE"]] = $ar_res["ID"];
			}
		}

		if (CModule::IncludeModule("forum"))
		{
			$res = CForumNew::GetList(
				Array()
			);

			while ($ar_res = $res->Fetch())
			{
				$GLOBAL_FORUM_ID[$ar_res["XML_ID"]] = $ar_res["ID"];
			}
		}

		if (CModule::IncludeModule("blog"))
		{
			$arFields = Array("ID", "SITE_ID");

			$dbGroup = CBlogGroup::GetList(array(), array(), false, false, $arFields);
			if ($arGroup = $dbGroup->Fetch())
			{
				$GLOBAL_BLOG_GROUP[$arGroup["SITE_ID"]] = $arGroup["ID"];
			}
		}

		if (CModule::IncludeModule("disk"))
		{
			$dbDisk = Bitrix\Disk\Storage::getList(array("filter"=>array("=ENTITY_TYPE" => Bitrix\Disk\ProxyType\Common::className())));
			if ($commonStorage = $dbDisk->Fetch())
			{
				$GLOBAL_STORAGE_ID["shared_files"] = $commonStorage["ID"];
			}
		}

		if ($obCache->StartDataCache())
		{
			$obCache->EndDataCache(array(
			   'IBLOCK_ID' => $GLOBAL_IBLOCK_ID,
			   'FORUM_ID' => $GLOBAL_FORUM_ID,
			   'BLOG_GROUP' => $GLOBAL_BLOG_GROUP,
			   'STORAGE_ID' => $GLOBAL_STORAGE_ID,
		   ));
		}
	}
}
?>
