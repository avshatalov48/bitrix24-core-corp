<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm-preview.css');
?>

<div class="crm-preview">
	<div class="crm-preview-header">
		<span class="crm-preview-header-icon crm-preview-header-icon-contact"></span>
		<? if($arResult['HEAD_IMAGE_URL'] !== ''): ?>
			<span class="crm-preview-header-company-img">
					<img alt="" src="<?=htmlspecialcharsbx($arResult['HEAD_IMAGE_URL'])?>" />
				</span>
		<? endif; ?>
		<span class="crm-preview-header-title">
			<?=GetMessage("CRM_TITLE_COMPANY")?>:
			<a href="<?=htmlspecialcharsbx($arParams['URL'])?>" target="_blank"><?=htmlspecialcharsbx($arResult['TITLE'])?></a>
		</span>
	</div>
	<table class="crm-preview-info">
		<tr>
			<td><?= GetMessage('CRM_CONTACT_RESPONSIBLE')?>: </td>
			<td>
				<a id="a_<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_UNIQID'])?>" href="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_PROFILE"])?>" bx-tooltip-user-id="<?=htmlspecialcharsbx($arResult["ASSIGNED_BY_ID"])?>">
					<?=htmlspecialcharsbx($arResult['ASSIGNED_BY_FORMATTED_NAME'])?>
				</a>
			</td>
		</tr>
		<? foreach($arResult['CONTACT_INFO'] as $contactInfoType => $contactInfoValue): ?>
			<tr>
				<td><?= GetMessage('CRM_CONTACT_INFO_'.$contactInfoType)?>: </td>
				<td>
					<?
					$contactInfoValue = htmlspecialcharsbx($contactInfoValue);
					switch($contactInfoType)
					{
						case 'EMAIL':
							?><a href="mailto:<?=$contactInfoValue?>" title="<?=$contactInfoValue?>"><?=$contactInfoValue?></a><?
							break;
						case 'PHONE':
							?><a href="callto://<?=$contactInfoValue?>" onclick="if(typeof(BXIM) !== 'undefined') { BXIM.phoneTo('8 4012 531249'); return BX.PreventDefault(event); }" title="<?=$contactInfoValue?>"><?=$contactInfoValue?></a><?
							break;
						case 'WEB':
							?><a href="http://<?=$contactInfoValue?>"><?=$contactInfoValue?></a><?
							break;
						default:
							echo $contactInfoValue;
					}
					?>
				</td>
			</tr>
		<? endforeach ?>
	</table>
</div>