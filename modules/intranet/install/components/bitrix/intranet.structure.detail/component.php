<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['USER_ID'] = intval($arParams['USER_ID']);

if ($arParams['USER_ID'])
{
	$dbRes = CUser::GetByID($arParams['USER_ID']);
	$arResult['USER'] = $dbRes->Fetch();
}

if (is_array($arResult['USER']))
{
	if (is_array($arResult['USER']['UF_DEPARTMENT']) && count($arResult['USER']['UF_DEPARTMENT']) > 0)
	{
		$dbRes = CIBlockSection::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'), array('ID' => $arResult['USER']['UF_DEPARTMENT']));
		$arResult['DEPARTMENTS'] = array();
		while ($arSection = $dbRes->Fetch())
		{
			$arResult['DEPARTMENTS'][$arSection['ID']] = $arSection['NAME'];
		}
	}

	if ($arResult['USER']['PERSONAL_PHOTO'])
		$arResult['USER']['PERSONAL_PHOTO'] = CFile::ShowImage($arResult['USER']['PERSONAL_PHOTO'], 200, 200, 'border="0"', '', true);
}

if ($APPLICATION->GetShowIncludeAreas() && $USER->IsAdmin())
{
	$arIcons = array(
		// form template edit icon
		array(
			'URL' => "javascript:".$APPLICATION->GetPopupLink(
				array(
					'URL' => "/bitrix/admin/user_edit.php?bxpublic=Y&from_module=intranet&lang=".LANGUAGE_ID."&ID=".$arParams['USER_ID'],
					'PARAMS' => array(
						'width' => 800,
						'height' => 500,
						'resize' => false,
					)
				)
			),
			'ICON' => 'edit',
			'TITLE' => 'Редактировать пользователя',
			'MODE' => array('configure')
		),
	);
	
	$this->AddIncludeAreaIcons($arIcons);
}

$this->IncludeComponentTemplate();
?>