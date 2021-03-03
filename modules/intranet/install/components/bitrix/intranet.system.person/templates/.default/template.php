<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

$arUser = is_array($arParams['~USER']) ? $arParams['~USER'] : array();
$name = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $arResult["bUseLogin"]);

$arUserData = array();
if (is_array($arParams['USER_PROPERTY']))
{
	foreach ($arParams['USER_PROPERTY'] as $key)
	{
		if ($arUser[$key])
			$arUserData[$key] = $arUser[$key];
	}
}

if (!defined('INTRANET_ISP_MUL_INCLUDED')):
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

	define('INTRANET_ISP_MUL_INCLUDED', 1);
endif;
?>

<div class="bx-user-info-big">
	<div class="bx-user-info-inner-big">
	<?
	if ($arUser['SUBTITLE']):
		?>
		<div class="bx-user-subtitle<?echo $arUser['SUBTITLE_FEATURED'] == 'Y' ? ' bx-user-subtitle-featured' : ''?>">
			<?echo (!empty($arUser['PREVIEW_TEXT_TYPE']) && $arUser['PREVIEW_TEXT_TYPE'] == 'html' ? $arUser['SUBTITLE'] : htmlspecialcharsbx($arUser['SUBTITLE']))?>
		</div>
		<?
	endif;
	?>
	<div class="bx-user-controls">
	<?
	if ($USER->IsAuthorized() && (!isset($arUser['ACTIVE']) || $arUser['ACTIVE'] == 'Y')):
		?>
		<div class="bx-user-control">
		<ul>
			<?
			if ($arResult['CAN_MESSAGE'] && $arParams['PM_URL']):
				?>
				<li class="bx-icon bx-icon-message">
					<a href="javascript:void(0)" onclick="if (top.BXIM) { top.BXIM.openMessenger(<?=$arUser['ID']?>); }">
						<?echo GetMessage('INTR_ISP_PM')?>
					</a>
				</li>
				<?
			endif;
			?>
			<?
			if ($arResult['CAN_VIDEO_CALL']):
				?>
				<li class="bx-icon bx-icon-video">
					<a href="javascript:void(0)" onclick="if (top.BXIM) { top.BXIM.callTo(<?=$arUser['ID']?>); }">
					   <?echo GetMessage('INTR_ISP_VIDEO_CALL')?>
					</a>
				</li>
				<?
			endif;
			if ($arResult['CAN_EDIT_USER'] || $arResult['CAN_EDIT_USER_SELF']) :
				?>
				<li class="bx-icon bx-icon-edit"><a href="<?=$arUser["DETAIL_URL"]?>"><?echo GetMessage('INTR_ISP_EDIT_USER')?></a></li>
				<?
			endif;
			?>
		</ul>
		</div>
		<?
	endif;
	if ($arUser['IS_ONLINE'] || $arUser['IS_BIRTHDAY'] || $arUser['IS_ABSENT'] || $arUser['IS_FEATURED']):
		?>
		<div class="bx-user-control">
		<ul>
			<?if ($arUser['IS_ONLINE']):?><li class="bx-icon bx-icon-online"><?echo GetMessage('INTR_ISP_IS_ONLINE')?></li><?endif;?>
			<?if ($arUser['IS_ABSENT']):?><li class="bx-icon bx-icon-away"><?echo GetMessage('INTR_ISP_IS_ABSENT')?></li><?endif;?>
			<?if ($arUser['IS_BIRTHDAY']):?><li class="bx-icon bx-icon-birth"><?echo GetMessage('INTR_ISP_IS_BIRTHDAY')?></li><?endif;?>
			<?if ($arUser['IS_FEATURED']):?><li class="bx-icon bx-icon-featured"><?echo GetMessage('INTR_ISP_IS_FEATURED')?></li><?endif;?>
		</ul>
		</div>
		<?
	endif;
	?></div><?
	if (
		is_array($arParams['USER_PROPERTY'])
		&& in_array('PERSONAL_PHOTO', $arParams['USER_PROPERTY'])
	)
	{
		?><div class="bx-user-image<? if (!$arUser['PERSONAL_PHOTO']) { ?> bx-user-image-default<? } ?>"><?
		if ($arResult['CAN_VIEW_PROFILE'])
		{
			?><a href="<?echo $arUser['DETAIL_URL']?>"><?
		}
		if ($arUser['PERSONAL_PHOTO']) 
		{
			echo $arUser['PERSONAL_PHOTO']; 
		}
		if ($arResult['CAN_VIEW_PROFILE'])
		{
			?></a><? 
		}
		?></div><?
	}
	?><div class="bx-user-text<? if (!is_array($arParams['USER_PROPERTY']) || !in_array('PERSONAL_PHOTO', $arParams['USER_PROPERTY'])) { ?> no-photo<? } ?>">
		<div class="bx-user-name">
			<a href="<?=$arUser['DETAIL_URL']?>" bx-tooltip-user-id="<?=$arUser["ID"]?>"><?=CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, $arParams["SHOW_LOGIN"] != 'N');?></a>
		</div>
		<div class="bx-user-post"><?echo htmlspecialcharsbx($arUser['WORK_POSITION'])?></div>
		<div class="bx-user-properties">
		<? foreach ($arUserData as $key => $value)
		{
			if (in_array($key, array('PERSONAL_PHOTO')))
			{
				continue;
			}
			echo $arParams['USER_PROP'][$key] ? $arParams['USER_PROP'][$key] : GetMessage('ISL_'.$key); ?>:
			<? switch($key)
			{
				case 'EMAIL':
					echo '<a href="mailto:',urlencode($value),'">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_WWW':
					echo '<a href="http://',urlencode($value),'" target="_blank">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_PHONE':
				case 'WORK_PHONE':
				case 'PERSONAL_MOBILE':
				case 'UF_PHONE_INNER':
					$value_encoded = preg_replace('/[^\d\+]+/', '', $value);
					echo '<a href="callto:',$value_encoded,'">',htmlspecialcharsbx($value),'</a>';
					break;

				case 'PERSONAL_GENDER':
					echo $value == 'F' ? GetMessage('INTR_ISP_GENDER_F') : ($value == 'M' ? GetMessage('INTR_ISP_GENDER_M') : '');
					break;

				case 'PERSONAL_BIRTHDAY':
					echo FormatDateEx(
						$value,
						false,
						$arParams['DATE_FORMAT'.(($arParams['SHOW_YEAR'] == 'N' || $arParams['SHOW_YEAR'] == 'M' && $arUser['PERSONAL_GENDER'] == 'F') ? '_NO_YEAR' : '')]
					);

					break;

				case 'DATE_REGISTER':
					echo FormatDateEx(
						$value,
						false,
						$arParams['DATE_TIME_FORMAT']
					);

					break;

				case 'UF_DEPARTMENT':
					$bFirst = true;
					if (is_array($value) && count($value) > 0)
					{
						foreach ($value as $dept_id => $dept_name)
						{
							if (!$bFirst && $dept_name) echo ', ';
							else $bFirst = false;

							if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
								echo htmlspecialcharsbx($dept_name);
							else
							{
								if (trim($arParams["PATH_TO_CONPANY_DEPARTMENT"]) <> '')
									echo '<a href="',CComponentEngine::MakePathFromTemplate($arParams["~PATH_TO_CONPANY_DEPARTMENT"], array("ID" => $dept_id)),'">',htmlspecialcharsbx($dept_name),'</a>';
								else
									echo '<a href="',$arParams['STRUCTURE_PAGE'].'?set_filter_',$arParams['STRUCTURE_FILTER'],'=Y&',$arParams['STRUCTURE_FILTER'],'_UF_DEPARTMENT=',$dept_id,'">',htmlspecialcharsbx($dept_name),'</a>';
							}

						}
					}
					break;

				default:
					if (mb_substr($key, 0, 3) == 'UF_' && is_array($arResult['USER_PROP'][$key]))
					{
						$arResult['USER_PROP'][$key]['VALUE'] = $value;
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.view',
							$arResult['USER_PROP'][$key]['USER_TYPE_ID'],
							array(
								'arUserField' => $arResult['USER_PROP'][$key],
							)
						);
					}
					else
						echo htmlspecialcharsbx($value);

					break;
			} ?>
			<br />
		<? } ?>
		</div>
	</div>
	<div class="bx-users-delimiter"></div>
	</div>
</div>
<?
if ($arParams['LIST_OBJECT'])
{
?>
<script><?echo CUtil::JSEscape($arParams['LIST_OBJECT'])?>[<?echo CUtil::JSEscape($arParams['LIST_OBJECT'])?>.length] = {ID:<?echo $arUser['ID']?>,NAME:'<?echo CUtil::JSEscape($name)?>',CURRENT:<?echo $arUser['IS_HEAD'] ? 'true' : 'false'?>}</script>
<?
}
?>