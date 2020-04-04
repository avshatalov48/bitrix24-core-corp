<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arResult["diskEnabled"] = (\Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false) && CModule::includeModule('disk'));

if (!function_exists("__blogUFfileEditMobile"))
{
	function __blogUFfileEditMobile($arResult, $arParams)
	{
		$result = false;
		if (
			strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_POST_DOC') === 0 
			|| strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_DOC') === 0
		)
		{
			$componentParams = array(
				'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
//				'INPUT_VALUE' => $arResult["VALUE"],
				'MAX_FILE_SIZE' => (intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']) > 0 ? $arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE'] : 5000000),
				'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
				'MODULE_ID' => 'uf',
				'ALLOW_UPLOAD' => 'I',
				'POST_ID' => $arParams['POST_ID']
			);

			$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:mobile.file.upload', '', $componentParams, false, Array("HIDE_ICONS" => "Y"));
		}

		return true;
	}
}

if (
	intval($arParams["SOCNET_GROUP_ID"]) > 0
	&& CModule::IncludeModule("socialnetwork")
)
{
	if ($arSonetGroup = CSocNetGroup::GetByID($arParams["SOCNET_GROUP_ID"]))
	{
		$arResult["SONET_GROUP_NAME"] = $arSonetGroup["NAME"];
	}
}

if (
	intval($_REQUEST["post_id"]) > 0
	&& CModule::IncludeModule("blog")
	&& CModule::IncludeModule("socialnetwork")
	&& $arBlogPost = CBlogPost::GetByID(intval($_REQUEST["post_id"]))
)
{
	$postPerms = CBlogPost::GetSocNetPostPerms(intval($_REQUEST["post_id"]), true, $GLOBALS["USER"]->GetID(), $arBlogPost["AUTHOR_ID"]);
	if (
		$postPerms > BLOG_PERMS_MODERATE 
		|| (
			$postPerms >= BLOG_PERMS_WRITE 
			&& $arBlogPost["AUTHOR_ID"] == $GLOBALS["USER"]->GetID()
		)
	)
	{
		$arResult["Post"] = $arBlogPost;

		$arResult["Post"]["arSonetPerms"] = array();
		$arResult["Post"]["arSonetPermsHidden"] = array();

		$arSonetPerms = CBlogPost::GetSocnetPerms(intval($_REQUEST["post_id"]));
		$bExtranetInstalled = CModule::IncludeModule("extranet");
		$arAvailableGroupID = CSocNetLogTools::GetAvailableGroups();

		foreach($arSonetPerms as $key => $arSonetPerm)
		{
			foreach ($arSonetPerm as $entityId => $arPerm)
			{
				if ($key == "U")
				{
					if (in_array("G2", $arPerm))
					{
						$arResult["Post"]["arSonetPerms"][] = array(
							"type" => "groups",
							"item" => array(
								"id" => "UA"
							)
						);
					}
					else
					{
						$rsUserTmp = CUser::GetByID($entityId);
						if ($arUserTmp = $rsUserTmp->Fetch())
						{
							$arResult["Post"][(!$bExtranetInstalled || CExtranet::IsProfileViewable($arUserTmp) ? "arSonetPerms" : "arSonetPermsHidden")][] = array(
								"type" => "users",
								"item" => array(
									"id" => "U".$entityId,
									"name" => CUser::FormatName(CSite::GetNameFormat(), $arUserTmp, true)
								)
							);
						}
					}
				}
				elseif ($key == "SG")
				{
					$arSonetGroup = CSocNetGroup::GetByID($entityId);
					if ($arSonetGroup)
					{
						$arResult["Post"][(in_array($entityId, $arAvailableGroupID) ? "arSonetPerms" : "arSonetPermsHidden")][] = array(
							"type" => "sonetgroups",
							"item" => array(
								"id" => "SG".$entityId,
								"name" => $arSonetGroup["NAME"]
							)
						);
					}
				}
				elseif (
					$key == "DR"
					&& CModule::IncludeModule("iblock")
				)
				{
					$rsDepartmentTmp = CIBlockSection::GetByID($entityId);
					if ($arDepartmentTmp = $rsDepartmentTmp->GetNext())
					{
						$arResult["Post"]["arSonetPerms"][] = array(
							"type" => "department",
							"item" => array(
								"id" => "DR".$entityId,
								"name" => $arDepartmentTmp["NAME"]
							)
						);
					}
				}
			}
		}

		// get blog images
		$_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])] = array();
		$rsBlogImage = CBlogImage::GetList(
			array("ID" => "ASC"),
			array(
				"POST_ID" => $arBlogPost['ID'], 
				"IS_COMMENT" => "N"
			)
		);
		while ($arBlogImage = $rsBlogImage->Fetch())
		{
			$_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])][] = $arBlogImage["FILE_ID"];
		}		
	}

	$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;

	$rsLog = CSocNetLog::GetList(
		array(),
		array(
			"EVENT_ID" => $blogPostLivefeedProvider->getEventId(),
			"SOURCE_ID" => intval($_REQUEST["post_id"])
		),
		false,
		false,
		array("ID")
	);

	if ($arLog = $rsLog->Fetch())
	{
		$arResult["Post"]["LogID"] = $arLog["ID"];
	}
}

if (!empty($arParams["POST_PROPERTY"]))
{
	$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_POST", (intval($_REQUEST["post_id"]) > 0 && $arResult["Post"] ? intval($_REQUEST["post_id"]) : 0), LANGUAGE_ID);

	if (count($arParams["POST_PROPERTY"]) > 0)
	{
		foreach ($arPostFields as $FIELD_NAME => $arPostField)
		{
			if (!in_array($FIELD_NAME, $arParams["POST_PROPERTY"]))
			{
				continue;
			}

			$arPostField["EDIT_FORM_LABEL"] = strLen($arPostField["EDIT_FORM_LABEL"]) > 0 ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"];
			$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["EDIT_FORM_LABEL"]);
			$arPostField["~EDIT_FORM_LABEL"] = $arPostField["EDIT_FORM_LABEL"];
			if (
				strlen($arResult["ERROR_MESSAGE"]) > 0 
				&& !empty($_POST[$FIELD_NAME])
			)
			{
					$arPostField["VALUE"] = $_POST[$FIELD_NAME];
			}
			else if (
				intval($_REQUEST["post_id"]) > 0 
				&& $arResult["Post"]
				&& isset($arPostField["VALUE"])
				&& !empty($arPostField["VALUE"])
			)
			{
				if ($FIELD_NAME == "UF_BLOG_POST_DOC")
				{
					$_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])] = $arPostField["VALUE"];
				}
				elseif ($FIELD_NAME == "UF_BLOG_POST_FILE")
				{
					if ($arResult["diskEnabled"])
					{
						$_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])] = array();
						if (is_array($arPostField["VALUE"]))
						{
							foreach($arPostField["VALUE"] as $val)
							{
								$oAttachedModel = \Bitrix\Disk\AttachedObject::loadById($val, array('OBJECT.STORAGE', 'VERSION'));
								if ($oAttachedModel)
								{
									$oDiskFile = $oAttachedModel->getFile();
									if ($oDiskFile)
									{
										$_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])][] = $oDiskFile->getId();
									}
								}
							}
						}
						elseif (intval($arPostField["VALUE"]) > 0)
						{
							$oAttachedModel = \Bitrix\Disk\AttachedObject::loadById($arPostField["VALUE"], array('OBJECT.STORAGE', 'VERSION'));
							if ($oAttachedModel)
							{
								$oDiskFile = $oAttachedModel->getFile();
								if ($oDiskFile)
								{
									$rsFile = CFile::GetByID($oDiskFile->getFileId());
									if ($arFile = $rsFile->Fetch())
									{
										$_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])] = array($arFile["ID"]);
									}
								}
							}
						}
					}
					else
					{
						$_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()."_".intval($_REQUEST["post_id"])] = $arPostField["VALUE"];
					}
				}
			}

			$arPostPropertiesData[$FIELD_NAME] = $arPostField;
		}
	}
}

$arParams["USER_FIELDS"] = array(
	"SHOW" => (!empty($arPostPropertiesData) ? "Y" : "N"),
	"VALUE" => $arPostPropertiesData
);

$bAllowToAll = (
	\Bitrix\Main\Loader::includeModule('socialnetwork')
	&& \Bitrix\Socialnetwork\ComponentHelper::getAllowToAllDestination()
);

$arResult["DENY_TOALL"] = !$bAllowToAll;
$arResult["DEFAULT_TOALL"] = ($bAllowToAll ? (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y") : false);
?>