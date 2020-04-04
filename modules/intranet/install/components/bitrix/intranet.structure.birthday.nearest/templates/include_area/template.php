<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arMonths_r = array();
for ($i = 1; $i <= 12; $i++)
{
	$arMonths_r[$i] = ToLower(GetMessage('MONTH_'.$i.'_S'));
}
?><div class="bx-birthday-layout-include"><?
if ($arParams['bShowFilter'])
{
	?><div class="bx-birthday-officelink"><?
	if ($arResult['CURRENT_USER']['DEPARTMENT_TOP'])
	{
		if ($arResult['ONLY_MINE'] == 'Y')
		{
			?><a href="<?echo $APPLICATION->GetCurPageParam('', array('department'))?>"><?echo GetMessage('INTR_ISBN_TPL_FILTER_ALL');?></a><br /><?
		}
		else
		{
			?><a href="<?echo $APPLICATION->GetCurPageParam('department='.$arResult['CURRENT_USER']['DEPARTMENT_TOP'], array('department'))?>"><?echo GetMessage('INTR_ISBN_TPL_FILTER_MINE')?></a><br /><?
		}
	}
	?></div><?
}

foreach ($arResult['USERS'] as $arUser)
{
	$birthday = FormatDateEx(
		$arUser['PERSONAL_BIRTHDAY'], 
		false, 
		$arParams['DATE_FORMAT'.($arParams['SHOW_YEAR'] == 'Y' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'M' ? '' : '_NO_YEAR')]
	);
	?><div class="bx-user-info">
		<div class="bx-user-info-inner">
			<div class="bx-user-image<?=$arUser['PERSONAL_PHOTO'] ? '' : ' bx-user-image-default'?>"><a href="<?=$arUser['DETAIL_URL']?>"><?=$arUser['PERSONAL_PHOTO'] ? $arUser['PERSONAL_PHOTO'] : '' ?></a></div>
			<div class="bx-user-birthday<?echo $arUser['IS_BIRTHDAY'] ? ' bx-user-birthday-today' : ''?> intranet-date"><?echo $birthday;?></div>
			<div class="bx-user-name">
			<?
			$APPLICATION->IncludeComponent("bitrix:main.user.link",
				'',
				array(
					"ID" => $arUser["ID"],
					"HTML_ID" => "structure_birthday_nearest_".$arUser["ID"],
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"],
					"USE_THUMBNAIL_LIST" => "N",
					"INLINE" => "Y",
					"PATH_TO_SONET_USER_PROFILE" => $arParams["~DETAIL_URL"],
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
			?></div><?

			foreach($arParams["USER_PROPERTY"] as $field)
			{
				if (
					isset($arUser[$field])
					&& !empty($arUser[$field])
				)
				{
					if (array_key_exists($field, $arResult["arUserField"]))
					{
						?><div class="bx-user-post"><?
						$APPLICATION->IncludeComponent(
							"bitrix:system.field.view", 
							$arResult["arUserField"][$key]["USER_TYPE"]["USER_TYPE_ID"], 
							array(
								"arUserField" => $arResult["arUserField"][$key]
							),
							null,
							array("HIDE_ICONS"=>"Y")
						);
						?></div><?
					}
					else
					{
						switch ($field)
						{
							case "PERSONAL_PHOTO":
							case "FULL_NAME":
							case "NAME":
							case "SECOND_NAME":
							case "LAST_NAME":
								break;
							case "GENDER":
								if (in_array($arUser[$field], array("F", "M")))
								{
									?><div class="bx-user-post"><?=GetMessage("INTR_ISBN_TPL_USER_PROPERTY_".$field)?>: <?=GetMessage("INTR_ISBN_TPL_USER_PROPERTY_".$field."_".$arUser[$field])?></div><?
								}
								break;
							case "ID":
							case "LOGIN":
							case "EMAIL":
							case "DATE_REGISTER":
							case "PERSONAL_WWW":
							case "PERSONAL_ICQ":
							case "PERSONAL_PHONE":
							case "PERSONAL_FAX":
							case "PERSONAL_MOBILE":
							case "PERSONAL_PAGER":
							case "PERSONAL_STREET":
							case "PERSONAL_MAILBOX":
							case "PERSONAL_STATE":
							case "PERSONAL_CITY":
							case "PERSONAL_ZIP":
							case "PERSONAL_COUNTRY":
							case "WORK_PHONE":
							case "WORK_FAX":
							case "XML_ID":
								?><div class="bx-user-post"><?=GetMessage("INTR_ISBN_TPL_USER_PROPERTY_".$field)?>: <?=htmlspecialcharsbx($arUser[$field])?></div><?
								break;
							case "PERSONAL_PROFESSION":
							case "WORK_POSITION":
							case "WORK_COMPANY":
							case "PERSONAL_NOTES":
							case "ADMIN_NOTES":
								?><div class="bx-user-post"><?=htmlspecialcharsbx($arUser[$field])?></div><?
								break;
							default:
						}
					}
				}
			}
			?><div class="bx-users-delimiter"></div>
		</div>
	</div><?
}
?></div>