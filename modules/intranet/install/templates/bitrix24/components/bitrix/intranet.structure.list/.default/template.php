<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	if ($arResult['EMPTY_UNFILTERED_LIST'] == 'Y'):
		ShowNote(GetMessage('INTR_ISL_TPL_NOTE_UNFILTERED'));
	elseif ($arParams['SHOW_ERROR_ON_NULL'] == 'Y'):
		ShowError(GetMessage('INTR_ISL_TPL_NOTE_NULL'));
	endif;
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('FULL_NAME', 'PERSONAL_PHONE', 'EMAIL', 'WORK_POSITION', 'UF_DEPARTMENT');
	$arLinkKeys = array('FULL_NAME', 'LAST_NAME', 'NAME', 'LOGIN', 'ID');
	
	foreach ($arLinkKeys as $key)
	{
		if (in_array($key, $arParams['USER_PROPERTY']))
		{
			$LINK_KEY = $key;
			break;
		}
	}
?>
<?if ($arParams['SHOW_NAV_TOP'] == 'Y'):?>
<div class="bx-users-nav"><?echo $arResult['USERS_NAV']?></div>
<?else:?>
<a name="nav_start"></a>
<?endif;?>
<table class="bx-users-table data-table">
	<thead>
		<tr>
<?
foreach ($arParams['USER_PROPERTY'] as $key):
?>
			<td><?=$arResult['USER_PROP'][$key]["EDIT_FORM_LABEL"] <> '' ? $arResult['USER_PROP'][$key]["EDIT_FORM_LABEL"] : GetMessage('ISL_'.$key)?></td>
<?
endforeach;
?>
			<td></td>
		</tr>
	</thead>
	<tbody>
<?
	//for ($i = 0; $i < $USERS_CNT; $i++):
	foreach ($arResult['USERS'] as $i => $arUser):
?>
		<tr>
<?
		foreach ($arParams['USER_PROPERTY'] as $key):
?>
			<td><?
			switch($key)
			{
				case 'UF_DEPARTMENT':
					$bFirst = true;
					
					if (is_array($arResult['USERS'][$i][$key]) && count($arResult['USERS'][$i][$key]) > 0)
					{
						$str = '';
						foreach ($arResult['USERS'][$i][$key] as $dept_id => $dept_name)
						{
							if (!$bFirst) $str .= ', ';
							else $bFirst = false;
							
							$str .= '<a href="'.$arParams['STRUCTURE_PAGE'].'?set_filter_'.$arParams['STRUCTURE_FILTER'].'=Y&'.$arParams['STRUCTURE_FILTER'].'_UF_DEPARTMENT='.$dept_id.'">'.htmlspecialcharsbx($dept_name).'</a>';
						}
						
						$arResult['USERS'][$i][$key] = $str;
					}
					else
						$arResult['USERS'][$i][$key] = '';
				break;

				case 'FULL_NAME':
					$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;
					$arResult['USERS'][$i][$key] = CUser::FormatName($arParams['NAME_TEMPLATE'], $arResult['USERS'][$i], $bUseLogin);
				break;
				
				case 'EMAIL':
					$arResult['USERS'][$i][$key] = '<a href="mailto:'.urlencode($arResult['USERS'][$i][$key]).'">'.htmlspecialcharsbx($arResult['USERS'][$i][$key]).'</a>';
				break;

				case 'PERSONAL_WWW':
					$arResult['USERS'][$i][$key] = '<a href="http://'.urlencode($arResult['USERS'][$i][$key]).'" target="_blank">'.htmlspecialcharsbx($arResult['USERS'][$i][$key]).'</a>';
				break;
				
				case 'PERSONAL_GENDER':
					$arResult['USERS'][$i][$key] = $arResult['USERS'][$i][$key] == 'F' ? GetMessage('INTR_ISL_TPL_GENDER_F') : ($arResult['USERS'][$i][$key] == 'M' ? GetMessage('INTR_ISL_TPL_GENDER_M') : '');
				break;
				
				case 'PERSONAL_PHOTO':
					if (!$arResult['USERS'][$i][$key])
						$arResult['USERS'][$i][$key] = '<div class="user-avatar user-default-avatar"></div>';
					else
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arResult['USERS'][$i]["PERSONAL_PHOTO_SOURCE"],
							array("width" => 100, "height" => 100),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$arResult['USERS'][$i][$key] = "<div class='user-avatar' style='background: url(\"".$arFileTmp["src"]."\") no-repeat center center; background-size: cover;'></div>";
					}
				break;
				
				case 'PERSONAL_PHONE':
				case 'WORK_PHONE':
				case 'PERSONAL_MOBILE':
				case 'UF_PHONE_INNER':
					$arResult['USERS'][$i][$key] = $arResult['USERS'][$i][$key] ? '<a href="callto:'.urlencode($arResult['USERS'][$i][$key]).'">'.htmlspecialcharsbx($arResult['USERS'][$i][$key]).'</a>' : '';
				break;
				
				case 'PERSONAL_BIRTHDAY':
					$arResult['USERS'][$i][$key] = FormatDateEx(
						$arResult['USERS'][$i][$key], 
						false, 
						$arParams['DATE_FORMAT'.(($arParams['SHOW_YEAR'] == 'N' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'F') ? '_NO_YEAR' : '')]
					);
				
					break;
				
				case 'DATE_REGISTER':
					$arResult['USERS'][$i][$key] = FormatDateEx(
						$arResult['USERS'][$i][$key], 
						false, 
						$arParams['DATE_TIME_FORMAT']
					);
				
					break;
				
				default:
					if (mb_substr($key, 0, 3) == 'UF_' && is_array($arResult['USER_PROP'][$key]))
					{
						$arResult['USER_PROP'][$key]['VALUE'] = $arResult['USERS'][$i][$key];
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.view',
							$arResult['USER_PROP'][$key]['USER_TYPE_ID'],
							array(
								'arUserField' => $arResult['USER_PROP'][$key],
							)
						);
						$arResult['USERS'][$i][$key] = '';
					}
					else
						$arResult['USERS'][$i][$key] = htmlspecialcharsbx($arResult['USERS'][$i][$key]);

					break;
			}
			
			if ($LINK_KEY == $key)
				$arResult['USERS'][$i][$key] = $GLOBALS["APPLICATION"]->IncludeComponent("bitrix:main.user.link",
					'',
					array(
						"ID" => $arResult['USERS'][$i]['ID'],
						"HTML_ID" => "structure_list_".$arResult['USERS'][$i]['ID'],
						"NAME" => $arResult['USERS'][$i]['NAME'],
						"LAST_NAME" => $arResult['USERS'][$i]['LAST_NAME'],
						"SECOND_NAME" => $arResult['USERS'][$i]['SECOND_NAME'],
						"LOGIN" => $arResult['USERS'][$i]['LOGIN'],									
						"PROFILE_URL" => $arResult['USERS'][$i]['DETAIL_URL'],
						"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PM_URL"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
						"USE_THUMBNAIL_LIST" => "N",						
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"DO_RETURN" => "Y",
					),
					false
					, array("HIDE_ICONS" => "Y")
				);
			
			echo $arResult['USERS'][$i][$key];
?></td>
<?
		endforeach;
?>
			<td class="bx-user-controls-cell">
	<?
	if ($USER->IsAuthorized() && (!isset($arUser['ACTIVE']) || $arUser['ACTIVE'] == 'Y')):
		?>
		<ul>
			<?
			if ($arUser['CAN_MESSAGE'] && $arParams['PM_URL']):	
				?>
				<li class="bx-icon bx-icon-message"><a href="<?echo ($url = str_replace('#USER_ID#', $arUser['ID'], $arParams['PM_URL']))?>" onclick="if (BX.IM) { BXIM.openMessenger(<?=$arUser['ID']?>); return false; } else {window.open('<?echo $url ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=700,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 700)/2-5)); return false;}"><?echo GetMessage('INTR_ISP_PM')?></a></li>
				<?
			endif;
			?>
			<?
			if ($arUser['CAN_VIDEO_CALL'] && $arParams['PATH_TO_VIDEO_CALL']):	
				?>
				<li class="bx-icon bx-icon-video"><a href="<?echo $arUser["Urls"]["VideoCall"]?>" onclick="window.open('<?echo $arUser["Urls"]["VideoCall"] ?>', '', 'status=no,scrollbars=yes,resizable=yes,width=1000,height=600,top='+Math.floor((screen.height - 600)/2-14)+',left='+Math.floor((screen.width - 1000)/2-5)); return false;"><?echo GetMessage('INTR_ISP_VIDEO_CALL')?></a></li>
				<?
			endif;
			?>			
			<?
			if ($arResult['CAN_EDIT_USER']):
				?>
				<li class="bx-icon bx-icon-edit"><a href="<?=CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_EDIT'], array("user_id" => $arUser['ID']))?>"><?echo GetMessage('INTR_ISP_EDIT_USER')?></a></li>
				<?
			endif;
			?>
		</ul>
		<?
	endif;
	if ($arUser['IS_ONLINE'] || $arUser['IS_BIRTHDAY'] || $arUser['IS_ABSENT'] || $arUser['IS_FEATURED']):
		?>
		<ul>
			<?if ($arUser['IS_ONLINE']):?><li class="bx-icon bx-icon-online"><?echo GetMessage('INTR_ISP_IS_ONLINE')?></li><?endif;?>
			<?if ($arUser['IS_ABSENT']):?><li class="bx-icon bx-icon-away"><?echo GetMessage('INTR_ISP_IS_ABSENT')?></li><?endif;?>
			<?if ($arUser['IS_BIRTHDAY']):?><li class="bx-icon bx-icon-birth"><?echo GetMessage('INTR_ISP_IS_BIRTHDAY')?></li><?endif;?>
			<?if ($arUser['IS_FEATURED']):?><li class="bx-icon bx-icon-featured"><?echo GetMessage('INTR_ISP_IS_FEATURED')?></li><?endif;?>
		</ul>
		<?
	endif;
	?>
			</td>
		</tr>
<?
	endforeach;
?>
	</tbody>
</table>
<?if ($arParams['SHOW_NAV_BOTTOM'] == 'Y'):?>
<div class="bx-users-nav"><?echo $arResult['USERS_NAV']?></div>
<?endif;?>
<?
endif;
?>