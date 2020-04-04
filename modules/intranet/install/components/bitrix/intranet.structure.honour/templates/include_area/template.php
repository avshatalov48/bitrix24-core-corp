<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arMonths_r = array();
for ($i = 1; $i <= 12; $i++)
	$arMonths_r[$i] = ToLower(GetMessage('MONTH_'.$i.'_S'));
?>
<div class="bx-honour-layout-include">
<?
foreach ($arResult['ENTRIES'] as $arEntry)
{
	$arUser = $arResult['USERS'][$arEntry['PROPERTY_USER_VALUE']];

	$arUser['SUBTITLE'] = $arEntry['NAME'];
	$arUser['PREVIEW_TEXT_TYPE'] = 'text';

	if (!$arUser['SUBTITLE'])
	{
		$arUser['SUBTITLE'] = $arEntry['PREVIEW_TEXT'];
		$arUser['PREVIEW_TEXT_TYPE'] = $arEntry['PREVIEW_TEXT_TYPE'];
	}
	if (!$arUser['SUBTITLE'])
	{
		$arUser['SUBTITLE'] = $arEntry['DETAIL_TEXT'];
		$arUser['PREVIEW_TEXT_TYPE'] = $arEntry['DETAIL_TEXT_TYPE'];
	}
?>
	<div class="bx-user-info">
		<div class="bx-user-info-inner">
			<div class="bx-user-image<?=$arUser['PERSONAL_PHOTO'] ? '' : ' bx-user-image-default'?>"><a href="<?=$arUser['DETAIL_URL']?>"><?=$arUser['PERSONAL_PHOTO'] ? $arUser['PERSONAL_PHOTO'] : '' ?></a></div>
			<div class="bx-user-subtitle"><?echo (!empty($arUser['PREVIEW_TEXT_TYPE']) && $arUser['PREVIEW_TEXT_TYPE'] == 'html' ? $arUser['SUBTITLE'] : htmlspecialcharsbx($arUser['SUBTITLE']))?></div>
			<div class="bx-user-name">
			<?
			$APPLICATION->IncludeComponent("bitrix:main.user.link",
				'',
				array(
					"ID" => $arUser["ID"],
					"HTML_ID" => "structure_honour_".$arUser["ID"],
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"],
					"USE_THUMBNAIL_LIST" => "N",
					"INLINE" => "Y",					
					"PROFILE_URL" => $arUser["DETAIL_URL"],
					"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["~PM_URL"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"CACHE_TYPE" => $arParams["CACHE_TYPE"],
					"CACHE_TIME" => $arParams["CACHE_TIME"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
					"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
				),
				false,
				array("HIDE_ICONS" => "Y")
			);
			?>
			</div>
			<div class="bx-user-post"><?echo htmlspecialcharsbx($arUser['WORK_POSITION'])?></div>
			<div class="bx-users-delimiter"></div>
		</div>
	</div>
<?
}
?>
</div>