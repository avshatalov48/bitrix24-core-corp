<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;
if (CModule::IncludeModule('socialnetwork'))
	$bCurrentUserModuleAdmin = CSocNetUser::IsCurrentUserModuleAdmin();

$APPLICATION->IncludeComponent("bitrix:main.user.link",
		'',
		array(
			"AJAX_ONLY" => "Y",	
			"PATH_TO_SONET_USER_PROFILE" => COption::GetOptionString('intranet', 'search_user_url', '/company/personal/user/#ID#/'),
			"PATH_TO_SONET_MESSAGES_CHAT" => $arParams["PM_URL"],
			"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
			"SHOW_YEAR" => $arParams["SHOW_YEAR"],
			"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
			"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
			"PATH_TO_CONPANY_DEPARTMENT" => $arParams["~PATH_TO_CONPANY_DEPARTMENT"],
			"PATH_TO_VIDEO_CALL" => $arParams["~PATH_TO_VIDEO_CALL"],
		),
		false,
		array("HIDE_ICONS" => "Y")
	);
						

if (!is_array($arResult['USERS']) || !($USERS_CNT = count($arResult['USERS']))):
	if ($arResult['EMPTY_UNFILTERED_LIST'] == 'Y'):
		ShowNote(GetMessage('ISL_TPL_NOTE_UNFILTERED'));
	elseif ($arParams['SHOW_ERROR_ON_NULL'] == 'Y'):
		ShowError(GetMessage('ISL_TPL_NOTE_NULL'));
	endif;
else:
	if (!is_array($arParams['USER_PROPERTY']) || count($arParams['USER_PROPERTY']) <= 0)
		$arParams['USER_PROPERTY'] = array('FULL_NAME', 'EMAIL', 'WORK_POSITION', 'WORK_PHONE');
?>
<table class="bx-users-table data-table" style="border: 0px" width="100%">
	<thead>
		<tr>
<?
foreach ($arParams['USER_PROPERTY'] as $key):
?>
			<td><?=$arResult['USER_PROP'][$key] ? $arResult['USER_PROP'][$key] : GetMessage('ISL_'.$key)?></td>
<?
endforeach;
?>
		</tr>
	</thead>
	<tbody>

<?

	$arDeptsChain = array();
	$arCurrentDepth = array();
	$cnt = 0;
	foreach ($arResult['DEPARTMENTS'] as $arDept)
	{
		$arDeptsChain[$arDept['DEPTH_LEVEL']] = '<a style="font-size:15px; color:#000;" href="'.$arParams['STRUCTURE_PAGE'].'?set_filter_'.$arParams['STRUCTURE_FILTER'].'=Y&'.$arParams['STRUCTURE_FILTER'].'_UF_DEPARTMENT='.$arDept['ID'].'">'.htmlspecialcharsbx($arDept['NAME']).'</a>';
	
		if (count($arDept['USERS']) <= 0)
			continue;
?>

	<tr>
		<td colspan="<?=count($arParams['USER_PROPERTY'])?>">
			<br><?if ($cnt++ > 0):?><br><?endif?>
			<div class="users-departments-chain" style="margin-bottom:4px;"><?= isset($arDept['DEPTH_LEVEL'])? implode('&nbsp;-&nbsp;', array_slice($arDeptsChain, 0, $arDept['DEPTH_LEVEL'])) : GetMessage('ISL_DEPARTMENT_NOT_FOUND')?></div>
		</td>
	</tr>
<?foreach ($arDept['USERS'] as $arUser):?>
	<tr>
	<?foreach ($arParams['USER_PROPERTY'] as $key):?>
		<td><?
			switch($key)
			{
				case 'FULL_NAME':
					if(true):
					?>
					<div class="bx-user-name">
					<?
		
					$arUser["NAME_FORMATTED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, $bUseLogin);						

					$anchor_id = RandString(8);
					$ajax_page = $APPLICATION->GetCurPageParam("", array("bxajaxid", "logout"));
					$bShowLink = true;

					if ($bShowLink):
						?><a href="<?=$arUser["DETAIL_URL"]?>" id="anchor_<?=$anchor_id?>" bx-tooltip-user-id="<?=$arUser["ID"]?>"><?=$arUser["NAME_FORMATTED"]?></a><?
					else:
						?><?=$arUser["NAME_FORMATTED"]?><?
					endif;
					?>
					</div>
					<?
						$result = '';
					else:
						$bUseLogin = $arParams['SHOW_LOGIN'] != "N" ? true : false;
						$result = '<a href="'.$arUser['DETAIL_URL'].'">'.CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $bUseLogin).'</a>';
					endif;
				break;
				
				case 'EMAIL':
					$result = '<a href="mailto:'.urlencode($arUser[$key]).'">'.htmlspecialcharsbx($arUser[$key]).'</a>';
				break;

				case 'PERSONAL_WWW':
					$result = '<a href="http://'.urlencode($arUser[$key]).'" target="_blank">'.htmlspecialcharsbx($arUser[$key]).'</a>';
				break;
				
				case 'PERSONAL_GENDER':
					$result = $arUser[$key] == 'F' ? GetMessage('INTR_ISL_TPL_GENDER_F') : ($arUser[$key] == 'M' ? GetMessage('INTR_ISL_TPL_GENDER_M') : '');
				break;
				
				case 'PERSONAL_PHOTO':
					if (!$arUser[$key])
					{
						$result = '<div class="user-avatar"></div>';
					}
					else
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arUser["PERSONAL_PHOTO_SOURCE"],
							array("width" => 100, "height" => 100),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$result = "<div class='user-avatar' style='background: url(\"".$arFileTmp["src"]."\") no-repeat center center; background-size: cover;'></div>";
					}
				break;
				
				case 'PERSONAL_PHONE':
				case 'WORK_PHONE':
				case 'PERSONAL_MOBILE':
				case 'UF_PHONE_INNER':
					$result = $arUser[$key] ? '<a href="callto:'.urlencode($arUser[$key]).'">'.htmlspecialcharsbx($arUser[$key]).'</a>' : '';
				break;
				
				case 'PERSONAL_BIRTHDAY':
					$result = FormatDateEx(
						$arUser[$key], 
						false, 
						$arParams['DATE_FORMAT'.(($arParams['SHOW_YEAR'] == 'N' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'F') ? '_NO_YEAR' : '')]
					);
				
					break;
				
				case 'DATE_REGISTER':
					$result = FormatDateEx(
						$arUser[$key], 
						false, 
						$arParams['DATE_TIME_FORMAT']
					);
				
				break;
				
				case 'UF_SKYPE':
					$result = $arUser[$key] ? '<a href="callto:'.urlencode($arUser[$key]).'">'.htmlspecialcharsEx($arUser[$key]).'</a>' : '';
				break;
				
				default: 
					if (mb_substr($key, 0, 3) == 'UF_' && is_array($arResult['USER_PROPERTIES'][$key]))
					{
						ob_start();
						$arResult['USER_PROPERTIES'][$key]['VALUE'] = $arUser[$key];
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.view', 
							$arResult['USER_PROPERTIES'][$key]['USER_TYPE_ID'], 
							array(
								'arUserField' => $arResult['USER_PROPERTIES'][$key],
							)
						);
						$result = ob_get_contents();
						ob_end_clean();
					}
					else
						$result = htmlspecialcharsbx($arUser[$key]);
						
					break;
			}
			echo $result;
		?></td>
	<?endforeach;?>
	</tr>
<?endforeach;?>
<?
	}
?>
</tbody>
</table>
<?


endif;
?>
