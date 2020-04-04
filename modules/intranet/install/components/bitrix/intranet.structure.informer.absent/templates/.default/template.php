<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//echo '<pre>'; print_r($arResult['ENTRIES']); echo '</pre>';

$today = mktime(0, 0, 0);

$cur_url = $APPLICATION->GetCurPageParam('', array('absence_mode')); 
$cur_url .= strpos($cur_url,'?')===false?'?':'&';
?>
<a name="informer_absent"></a>
<div class="bx-absent-layout-include">
	<div class="bx-absent-mode-switcher"><?
$i = 0;
foreach ($arResult['MODES_LIST'] as $mode):
	if ($i++ > 0) echo '&nbsp;|&nbsp;';
	
	if ($arParams['mode']==$mode):
?><b><?echo GetMessage('INTR_ISIA_TPL_'.strtoupper($mode))?></b><?
	else:
?><a href="<?=$cur_url ?>absence_mode=<?=$mode ?>#informer_absent"><?=getMessage('INTR_ISIA_TPL_'.strtoupper($mode)) ?></a><?
	endif;
endforeach;
	?></div>
<?
$num = 0;

if (count($arResult['ENTRIES']) > 0)
{
	foreach ($arResult['ENTRIES'] as $arEntry)
	{
		if (!$arUser = $arResult['USERS'][$arEntry['USER_ID']])
			continue;
		
		if (++$num > $arParams['NUM_USERS'])
			break;

		$hint_text = '<b>'.htmlspecialcharsbx($arEntry['NAME']).'</b>'
			.(strlen($arEntry['DESCRIPTION']) > 0 ? '<br />'.htmlspecialcharsbx($arEntry['DESCRIPTION']) : '')
			.'<br /><br />'.$arEntry['DATE_FROM'].' - '.$arEntry['DATE_TO'];
?>
	<div class="bx-user-info" id="bx_absence_<?=$arEntry['ID']?>" onmouseover="new BXHint('<?echo CUtil::JSEscape($hint_text)?>', this)">
		<div class="bx-user-info-inner">
			<div class="bx-user-image<?=$arUser['PERSONAL_PHOTO'] ? '' : ' bx-user-image-default'?>"><a href="<?=$arUser['DETAIL_URL']?>"><?=$arUser['PERSONAL_PHOTO'] ? $arUser['PERSONAL_PHOTO'] : '' ?></a></div>
			
			<div class="bx-user-name"><?
		$APPLICATION->IncludeComponent("bitrix:main.user.link",
			'',
			array(
				"ID" => $arUser["ID"],
				"HTML_ID" => "structure_informer_new_".$arUser["ID"],
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
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			false,
			array("HIDE_ICONS" => "Y")
		);
			?></div>
			<div class="bx-user-post"><?echo htmlspecialcharsbx($arUser['WORK_POSITION'])?></div>
			<div class="bx-user-date intranet-date">
<?
		$bAllDayOnly = false;
		if (date('Y-m-d', $arEntry['DATE_ACTIVE_FROM_TS']) == date('Y-m-d', $arEntry['DATE_ACTIVE_TO_TS']))
		{
			if ($arEntry['DATE_ACTIVE_FROM_TS'] == $arEntry['DATE_ACTIVE_TO_TS'])
			{
				$bAllDayOnly = true;
			}
			else
			{
				$arEntry['DATE_FROM'] = date('H:i', $arEntry['DATE_ACTIVE_FROM_TS']);
				$arEntry['DATE_TO'] = date('H:i', $arEntry['DATE_ACTIVE_TO_TS']);
				$delimiter = ' ';
			}
		}
		else
		{
			$delimiter = '<br />';
		}
?>
				<div class="intranet-date-more">
<?
		if ($bAllDayOnly):
?>
				<?=$arEntry['DATE_FROM']?>
<?
		else:
?>
				<?echo GetMessage('INTR_ISIA_TPL_FROM')?> <?=$arEntry['DATE_FROM'].$delimiter.GetMessage('INTR_ISIA_TPL_TILL')?> <?=$arEntry['DATE_TO']?>
<?
		endif;
?>
				</div>
			</div>
			<div class="bx-users-delimiter"></div>
		</div>
	</div>
<?
	} // endforeach
}
else
{
?>
	<br /><?echo GetMessage('INTR_ISIA_TPL_NO_ABSENCES')?>
<?
} // endif;
?>
</div>