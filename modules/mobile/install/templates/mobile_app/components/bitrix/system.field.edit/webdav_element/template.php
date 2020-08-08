<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (CModule::IncludeModule('webdav'))
{
		if (mb_strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_POST_FILE') === 0 || mb_strpos($arParams['arUserField']['FIELD_NAME'], 'UF_BLOG_COMMENT_FILE') === 0)
		{
			$componentParams = array(
				'INPUT_NAME' => $arParams["arUserField"]["FIELD_NAME"],
				'INPUT_NAME_UNSAVED' => 'FILE_NEW_TMP',
//				'INPUT_VALUE' => $arResult["VALUE"],
				'MAX_FILE_SIZE' => (intval($arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE']) > 0 ? $arParams['arUserField']['SETTINGS']['MAX_ALLOWED_SIZE'] : 5000000),
				'MULTIPLE' => $arParams['arUserField']['MULTIPLE'],
				'MODULE_ID' => 'uf',
				'ALLOW_UPLOAD' => 'I',
				'POST_ID' => (isset($arParams['POST_ID']) && intval($arParams['POST_ID']) > 0 ? intval($arParams['POST_ID']) : 0)
			);

			$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:mobile.file.upload', '', $componentParams, false, Array("HIDE_ICONS" => "Y"));
		}
}
?>