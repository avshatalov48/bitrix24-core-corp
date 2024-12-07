<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Location\Entity\Source\ConfigItem;
use Bitrix\Location;

\Bitrix\Main\UI\Extension::load([
	'access',
	'ui.hint',
	'ui.dialogs.messagebox',
	'ui.forms',
	'ui.alerts',
	'ui.design-tokens',
]);

$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

?>

<?if(isset($_GET['success'])): ?>
	<div class="content-edit-form-notice-successfully">
		<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=GetMessage('CONFIG_SAVE_SUCCESSFULLY')?></span>
	</div>
<?endif;?>
<div class="content-edit-form-notice-error" <?if (!$arResult["ERROR"]):?>style="display: none;"<?endif?> id="config_error_block">
	<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=$arResult["ERROR"]?></span>
</div>

<form name="configPostForm" id="configPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
	<input type="hidden" name="save_settings" value="true" >
	<?=bitrix_sessid_post();?>

	<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
		<tr>
			<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_HEADER_SETTINGS')?></div>
			</td>
		</tr>

		<?if ($arResult["IS_BITRIX24"]):?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_COMPANY_NAME')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-textbox">
					<input type="text" class="ui-ctl-element" name="logo_name" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "site_name", ""));?>"/>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_COMPANY_TITLE_NAME')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-textbox">
					<input type="text" class="ui-ctl-element" name="site_title" value="<?=htmlspecialcharsbx(COption::GetOptionString("bitrix24", "site_title", ""));?>" />
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('config_rating_label_likeY')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-textbox">
					<input class="ui-ctl-element" type="text" name="rating_text_like_y" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_y", ""));?>" />
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<!--
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('config_rating_label_likeN')?></td>
			<td class="content-edit-form-field-input"><input type="text" name="rating_text_like_n" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "rating_text_like_n", ""));?>"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		-->

		<?if ($arResult["IS_BITRIX24"]):?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_EMAIL_FROM')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-textbox">
					<input class="ui-ctl-element" type="text" name="email_from" value="<?=htmlspecialcharsbx(COption::GetOptionString("main", "email_from", ""));?>"/>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

		<?
			$logo24show = COption::GetOptionString("bitrix24", "logo24show", "Y");
			if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("remove_logo24"))
			{
				$logo24show = "Y";
			}
		?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="logo24"><?=GetMessage('CONFIG_LOGO_24')?></label>
				<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("remove_logo24")):?>
				<span class="tariff-lock" style="padding-left: 9px" onclick="BX.UI.InfoHelper.show('limit_admin_logo24');"></span>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input
					type="checkbox"
					id="logo24"
					name="logo24"
					<?if ($logo24show == "" || $logo24show == "Y"):?>checked<?endif?>
					class="content-edit-form-field-input-selector"
					<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("remove_logo24")):?>disabled<?endif?>
				/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="congig_date">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_DATE_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="cultureId" data-role="culture-selector" class="ui-ctl-element">
						<?foreach($arResult['CULTURES'] as $culture):?>
						<option
							value="<?=$culture['ID']?>"
							data-value="<?=$culture['CODE']?>"
							<?if ($culture['ID'] === $arResult['CURRENT_CULTURE_ID']) echo 'selected'?>
						>
							<?=$culture['NAME']?>
						</option>
						<?endforeach?>
					</select>
				</div>
				<div class="ui-alert ui-alert-warning" style="margin-top: 5px">
					<div class="ui-alert-message"><?=Loc::getMessage('CONFIG_EXAMPLE')?>:
					<div>
						<span data-role="culture-short-date-format">
							<?=$arResult['CULTURES'][$arResult['CURRENT_CULTURE_ID']]['SHORT_DATE_FORMAT']?>
						</span>
					</div>
					<div>
						<span data-role="culture-long-date-format">
							<?=$arResult['CULTURES'][$arResult['CURRENT_CULTURE_ID']]['LONG_DATE_FORMAT']?>
						</span>
					</div>
				</div>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_TIME_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<input type="radio" id="12_format" name="time_format" value="12" <?if ($arResult["TIME_FORMAT_TYPE"] === 12) echo "checked"?>>
				<label for="12_format"><?=GetMessage("CONFIG_TIME_FORMAT_12")?></label>
				<br/>
				<input type="radio" id="24_format" name="time_format" value="24" <?if ($arResult["TIME_FORMAT_TYPE"] === 24) echo "checked"?>>
				<label for="24_format"><?=GetMessage("CONFIG_TIME_FORMAT_24")?></label>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_NAME_FORMAT')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element" name="" onchange="if(this.value != 'other'){this.form.FORMAT_NAME.value = this.value;this.form.FORMAT_NAME.parentNode.parentNode.style.display='none';} else {this.form.FORMAT_NAME.parentNode.parentNode.style.display='block';}">
						<?
						$formatExists = false;
						foreach ($arResult["NAME_FORMATS"] as $template => $value)
						{
							if ($template == $arResult["CUR_NAME_FORMAT"])
								$formatExists = true;

							echo '<option value="'.$template.'"'.($template == $arResult["CUR_NAME_FORMAT"] ? ' selected' : '').'>'.htmlspecialcharsex($value).'</option>'."\n";
						}
						?>
						<option value="other" <?=($formatExists ? '' : "selected")?>><?echo GetMessage("CONFIG_CULTURE_OTHER")?></option>
					</select>
				</div>
				<div style="margin-top: 10px;<?=($formatExists ? 'display:none' : '')?>">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" type="text" name="FORMAT_NAME" value="<?=htmlspecialcharsbx($arResult["CUR_NAME_FORMAT"])?>" />
					</div>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_week_start">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WEEK_START')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="WEEK_START" class="ui-ctl-element">
						<?
						for ($i = 0; $i < 7; $i++)
						{
							echo '<option value="'.$i.'"'.($i == $arResult["WEEK_START"] ? ' selected="selected"' : '').'>'.GetMessage('DAY_OF_WEEK_' .$i).'</option>';
						}
						?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WORK_TIME')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-inline">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="work_time_start" class="ui-ctl-element">
						<?foreach($arResult["WORKTIME_LIST"] as $key => $val):?>
							<option value="<?= $key?>" <? if ($arResult["CALENDAT_SET"]['work_time_start'] == $key) echo ' selected="selected" ';?>><?= $val?></option>
						<?endforeach;?>
					</select>
				</div>
				&nbsp;&nbsp;&mdash;
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-inline">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select name="work_time_end" class="ui-ctl-element">
						<?foreach($arResult["WORKTIME_LIST"] as $key => $val):?>
							<option value="<?= $key?>" <? if ($arResult["CALENDAT_SET"]['work_time_end'] == $key) echo ' selected="selected" ';?>><?= $val?></option>
						<?endforeach;?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_WEEK_HOLIDAYS')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-multiple-select">
					<select class="ui-ctl-element" size="7" multiple id="cal_week_holidays" name="week_holidays[]">
						<?foreach($arResult["WEEK_DAYS"] as $day):?>
							<option value="<?= $day?>" <?if (in_array($day, $arResult["CALENDAT_SET"]['week_holidays']))echo ' selected="selected"';?>><?= GetMessage('CAL_OPTION_FIRSTDAY_'.$day)?></option>
						<?endforeach;?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr data-field-id="config_time">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_YEAR_HOLIDAYS')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-textbox">
					<input class="ui-ctl-element" name="year_holidays" type="text" value="<?= htmlspecialcharsbx($arResult["CALENDAT_SET"]['year_holidays'])?>" id="cal_year_holidays" size="60" />
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_PHONE_NUMBER_DEFAULT_COUNTRY')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element" name="phone_number_default_country">
						<?foreach($arResult["COUNTRIES"] as $key => $val):?>
							<option value="<?= $key?>" <? if ($arResult["PHONE_NUMBER_DEFAULT_COUNTRY"] == $key) echo ' selected="selected" ';?>><?= $val?></option>
						<?endforeach;?>
					</select>
				</div>
			</td>
		</tr>

		<?if($arResult['SHOW_ADDRESS_FORMAT']):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_LOCATION_ADDRESS_FORMAT')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" id="location_address_format_select" name="address_format_code">
						<?foreach($arResult['LOCATION_ADDRESS_FORMAT_LIST'] as $code => $name):?>
							<option
									value="<?=htmlspecialcharsbx($code)?>"
									<?=$arResult['LOCATION_ADDRESS_FORMAT_CODE'] === $code ? ' selected' : ''?>>
									<?=htmlspecialcharsbx($name)?>
							</option>
						<?endforeach;?>
						</select>
					</div>
					<div class="ui-alert ui-alert-warning" id="location_address_format_description" style="margin-top: 5px">
						<span class="ui-alert-message"><?=$arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION']?></span>
					</div>

				</td>
			</tr>
			<tr>
				<td colspan="3">
				</td>
			</tr>
		<?endif;?>

		<?if ($arResult["IS_BITRIX24"]):?>
	<!-- Organization type-->
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_ORGANIZATION')?></td>
			<td class="content-edit-form-field-input">
				<input type="radio" id="organisation" name="organization" value="" <?if ($arResult["ORGANIZATION_TYPE"] == "") echo "checked"?>>
				<label for="organization"><?=GetMessage("CONFIG_ORGANIZATION_DEF")?></label>
				<br/>
				<input type="radio" id="organization_public" name="organization" value="public_organization" <?if ($arResult["ORGANIZATION_TYPE"] == "public_organization") echo "checked"?>>
				<label for="organization_public"><?=GetMessage("CONFIG_ORGANIZATION_PUBLIC")?></label>
				<?if (in_array(LANGUAGE_ID, array("ru", "ua"))):?>
					<br/>
					<input type="radio" id="organization_gov" name="organization" value="gov_organization" <?if ($arResult["ORGANIZATION_TYPE"] == "gov_organization") echo "checked"?>>
					<label for="organization_gov"><?=GetMessage("CONFIG_ORGANIZATION_GOV")?></label>
				<?endif?>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif?>

	<!-- show fired employees -->
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="show_fired_employees"><?=GetMessage('CONFIG_SHOW_FIRED_EMPLOYEES')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="show_fired_employees" id="show_fired_employees" <?if (COption::GetOptionString("bitrix24", "show_fired_employees", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- webdav/disk-->
		<tr data-field-id="congig_date">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_DISK_VIEWER_SERVICE')?></td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element" name="default_viewer_service">
						<?foreach($arResult["DISK_VIEWER_SERVICE"] as $code => $name):?>
							<option value="<?=$code?>" <?if ($code == $arResult["DISK_VIEWER_SERVICE_DEFAULT"]) echo "selected"?>><?=$name?></option>
						<?endforeach?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<?if ($arResult["IS_DISK_CONVERTED"]):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_edit_object_in_uf"><?=GetMessage('CONFIG_DISK_ALLOW_EDIT_OBJECT_IN_UF')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_edit_object_in_uf" id="disk_allow_edit_object_in_uf" <?if (COption::GetOptionString("disk", "disk_allow_edit_object_in_uf", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="disk_allow_autoconnect_shared_objects"><?=GetMessage('CONFIG_WEBDAV_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="disk_allow_autoconnect_shared_objects" id="disk_allow_autoconnect_shared_objects" <?if (COption::GetOptionString("disk", "disk_allow_autoconnect_shared_objects", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?else:?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_global"><?=GetMessage('CONFIG_WEBDAV_SERVICES_GLOBAL')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_global" id="webdav_global" <?if (COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_global", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_local"><?=GetMessage('CONFIG_WEBDAV_SERVICES_LOCAL')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_local" id="webdav_local" <?if (COption::GetOptionString("webdav", "webdav_allow_ext_doc_services_local", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="webdav_autoconnect_share_group_folder"><?=GetMessage('CONFIG_WEBDAV_ALLOW_AUTOCONNECT_SHARE_GROUP_FOLDER')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="webdav_autoconnect_share_group_folder" id="webdav_autoconnect_share_group_folder" <?if (COption::GetOptionString("webdav", "webdav_allow_autoconnect_share_group_folder", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left" style="padding-top: 10px">
				<label for="disk_version_limit_per_file"><?=GetMessage('CONFIG_DISK_VERSION_LIMIT_PER_FILE')?></label>
				<?if ($arResult["IS_BITRIX24"])
				{
					if (!Feature::isFeatureEnabled("disk_version_limit_per_file"))
					{
					?>
						<img src="<?=$this->GetFolder();?>/images/lock.png" onclick="BX.UI.InfoHelper.show('limit_max_entries_in_document_history'); " style="position: relative;bottom: -1px; margin-left: 5px;"/>
					<?
					}

					$maxTimeInDocumentHistory = Feature::getVariable('disk_file_history_ttl');
					?>
					<br>
					<span><?=Loc::getMessage("CONFIG_LIMIT_MAX_TIME_IN_DOCUMENT_HISTORY", [
						"#NUM#" => $maxTimeInDocumentHistory,
					])?></span>
					<a href="javascript:void(0)" onclick="BX.UI.InfoHelper.show('limit_max_time_in_document_history')">
						<?=Loc::getMessage("CONFIG_MORE")?>
					</a>
				<?
				}
				?>
			</td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element" name="disk_version_limit_per_file" <?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_version_limit_per_file")) echo "disabled";?>>
						<?foreach($arResult["DISK_LIMIT_PER_FILE"] as $code => $name):?>
							<option value="<?=$code?>" <?if ($code == $arResult["DISK_LIMIT_PER_FILE_SELECTED"]) echo "selected"?>><?=$name?></option>
						<?endforeach?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_allow_use_external_link"><?=GetMessage('CONFIG_DISK_ALLOW_USE_EXTERNAL_LINK')?></label>
				<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_switch_external_link")):?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" onclick="BX.UI.InfoHelper.show('limit_admin_share_link'); " style="position: relative;bottom: -1px; margin-left: 5px;"/>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox"
					<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_switch_external_link")) echo "disabled";?>
					id="disk_allow_use_external_link"
					name="disk_allow_use_external_link"
					<?if (
						COption::GetOptionString("disk", "disk_allow_use_external_link", "Y") == "Y"
						&& (!$arResult["IS_BITRIX24"] || $arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("disk_manual_external_link"))
					):?>
						checked
					<?endif?>
					class="content-edit-form-field-input-selector"
				/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_object_lock_enabled"><?=GetMessage('CONFIG_DISK_OBJECT_LOCK_ENABLED')?></label>
				<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_object_lock_enabled")):?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" onclick="BX.UI.InfoHelper.show('limit_document_lock');" style="position: relative;bottom: -1px; margin-left: 5px;"/>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" <?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_object_lock_enabled")) echo "disabled";?> id="disk_object_lock_enabled" name="disk_object_lock_enabled" <?if (COption::GetOptionString("disk", "disk_object_lock_enabled", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="disk_allow_use_extended_fulltext"><?=GetMessage('CONFIG_DISK_ALLOW_USE_EXTENDED_FULLTEXT')?></label>
				<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_allow_use_extended_fulltext")):?>
					<img src="<?=$this->GetFolder();?>/images/lock.png" onclick="BX.UI.InfoHelper.show('limit_in_text_search');" style="position: relative;bottom: -1px; margin-left: 5px;"/>
				<?endif?>
			</td>
			<td class="content-edit-form-field-input">
				<input
					type="checkbox"
					<?if ($arResult["IS_BITRIX24"] && !Feature::isFeatureEnabled("disk_allow_use_extended_fulltext")) echo "disabled";?>
					id="disk_allow_use_extended_fulltext"
					name="disk_allow_use_extended_fulltext"
					<?if (COption::GetOptionString("disk", "disk_allow_use_extended_fulltext", "N") == "Y"):?>checked<?endif?>
					class="content-edit-form-field-input-selector"
					<?if (
						$arResult["IS_BITRIX24"]
						&& Feature::isFeatureEnabled("disk_allow_use_extended_fulltext")
					):?>
						<?if (COption::GetOptionString("disk", "disk_allow_use_extended_fulltext", "N") === "Y"):?>checked<?endif?>
					<?endif?>
				/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<?php
			if (
				Loader::includeModule('imconnector')
				&& method_exists('\Bitrix\ImConnector\Connectors\Network', 'isSearchEnabled')
			):
		?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_search_network"><?= GetMessage('CONFIG_ALLOW_SEARCH_NETWORK_MSGVER_1') ?></label></td>
				<td class="content-edit-form-field-input">
					<input type="checkbox" id="allow_search_network" name="allow_search_network" <?php if (\Bitrix\ImConnector\Connectors\Network::isSearchEnabled()): ?>checked<?php endif; ?> class="content-edit-form-field-input-selector"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?php endif; ?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_livefeed_toall"><?=GetMessage('CONFIG_ALLOW_TOALL')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" id="allow_livefeed_toall" name="allow_livefeed_toall" <?if (COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- live feed right-->
		<tr id="RIGHTS_all" style="display: <?=(COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;">
			<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
			<td class="content-edit-form-field-input">
				<?
				$APPLICATION->IncludeComponent(
					"bitrix:main.user.selector",
					"",
					[
						"ID" => "livefeed_toall_rights",
						"INPUT_NAME" => 'livefeed_toall_rights[]',
						"LIST" => $arResult['arToAllRights'],
						"USE_SYMBOLIC_ID" => true,
						"API_VERSION" => 3,
						"SELECTOR_OPTIONS" => array(
							'contextCode' => 'U',
							'context' => "livefeed_toall_rights",
							'departmentSelectDisable' => 'N',
							'userSearchArea' => 'I',
							'enableAll' => 'Y',
							'departmentFlatEnable' => 'Y',
						),
					]
				);
				?>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr id="DEFAULT_all" style="display: <?=(COption::GetOptionString("socialnetwork", "allow_livefeed_toall", "Y") == "Y" ? "table-row" : "none")?>;">
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="default_livefeed_toall"><?=GetMessage('CONFIG_DEFAULT_TOALL')?></label>
			</td>
			<td class="content-edit-form-field-input"><input type="checkbox" id="default_livefeed_toall" name="default_livefeed_toall" <?if (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

	<!-- im general chat right-->
		<?php if (!method_exists('\Bitrix\Im\V2\Chat\GeneralChat', 'getRightsForIntranetConfig')): ?>
			<?
			$imAllow = COption::GetOptionString("im", "allow_send_to_general_chat_all");
			?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_general_chat_toall"><?=GetMessage('CONFIG_IM_CHAT_RIGHTS')?></label></td>
				<td class="content-edit-form-field-input">
					<input type="checkbox" id="allow_general_chat_toall" name="allow_general_chat_toall" <?if ($imAllow == "Y" || $imAllow == "N" && !empty($arResult['arChatToAllRights'])):?>checked<?endif?> class="content-edit-form-field-input-selector"/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr id="chat_rights_all" style="display: <?=($imAllow == "Y" || $imAllow == "N" && !empty($arResult['arChatToAllRights']) ? "table-row" : "none")?>;">
				<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
				<td class="content-edit-form-field-input">
					<?

					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							"ID" => "imchat_toall_rights",
							"INPUT_NAME" => 'imchat_toall_rights[]',
							"LIST" => $arResult['arChatToAllRights'],
							"USE_SYMBOLIC_ID" => true,
							"API_VERSION" => 3,
							"SELECTOR_OPTIONS" => array(
								'contextCode' => 'U',
								'context' => "imchat_toall_rights",
								'departmentSelectDisable' => 'N',
								'userSearchArea' => 'I',
								'enableAll' => 'Y',
								'departmentFlatEnable' => 'Y',
							),
						]
					);
					?>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?php elseif (isset($arResult['generalChatCanPostList'])): ?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="allow_general_chat_toall"><?=GetMessage('CONFIG_IM_CHAT_RIGHTS')?></label></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select class="ui-ctl-element" name="general_chat_can_post" id="general_chat_can_post_select">
							<?php foreach($arResult["generalChatCanPostList"] as $code => $name): ?>
								<option value="<?=$code?>" <?= ($code == $arResult["generalChatCanPost"]) ? 'selected' : '' ?>><?= $name ?></option>
							<?php endforeach ?>
						</select>
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr id="chat_rights_all" style="display: <?= ($arResult["generalChatCanPost"] === $arResult["generalChatShowManagersList"]) ? "table-row" : "none" ?>;">
				<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
				<td class="content-edit-form-field-input">
					<?php
					$APPLICATION->IncludeComponent(
						"bitrix:main.user.selector",
						"",
						[
							"ID" => "imchat_toall_rights",
							"INPUT_NAME" => 'imchat_toall_rights[]',
							"LIST" => $arResult['generalChatManagersList'],
							"USE_SYMBOLIC_ID" => true,
							"API_VERSION" => 3,
							"SELECTOR_OPTIONS" => array(
								'contextCode' => 'U',
								'context' => "imchat_toall_rights",
								'departmentSelectDisable' => 'Y',
								'userSearchArea' => 'I',
								'enableAll' => 'N',
								'departmentFlatEnable' => 'Y',
							),
						]
					);
					?>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?php endif ?>

		<?php if (isset($arResult['arChatToAllRights']) || isset($arResult['generalChatCanPostList'])): ?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="general_chat_message_join"><?=GetMessage('CONFIG_IM_GENERSL_CHAT_MESSAGE_JOIN')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="general_chat_message_join" id="general_chat_message_join" <?if (COption::GetOptionString("im", "general_chat_message_join")):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="general_chat_message_leave"><?=GetMessage('CONFIG_IM_GENERSL_CHAT_MESSAGE_LEAVE')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="general_chat_message_leave" id="general_chat_message_leave" <?if (COption::GetOptionString("im", "general_chat_message_leave")):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?php endif ?>

		<?if ($arResult["IS_BITRIX24"]):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="general_chat_message_admin_rights"><?=GetMessage('CONFIG_IM_GENERSL_CHAT_MESSAGE_ADMIN_RIGHTS')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="general_chat_message_admin_rights" id="general_chat_message_admin_rights" <?if (COption::GetOptionString("im", "general_chat_message_admin_rights", true)):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="url_preview_enable"><?=GetMessage('CONFIG_URL_PREVIEW_ENABLE')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="url_preview_enable" id="url_preview_enable" <?if (COption::GetOptionString("main", "url_preview_enable", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
<?
$mpUserAllowInstall = count($arResult['MP_ALLOW_USER_INSTALL']) > 0;
?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="mp_allow_user_install"><?=GetMessage('CONFIG_MP_ALLOW_USER_INSTALL1')?></label></td>
			<td class="content-edit-form-field-input">
				<input type="hidden" id="mp_allow_user_install_changed" name="mp_allow_user_install_changed" value="N" />
				<input type="hidden" name="mp_allow_user_install" value="N" />
				<input type="checkbox" name="mp_allow_user_install" id="mp_allow_user_install" value="Y" <?=$mpUserAllowInstall ? ' checked="checked"' : ''?> class="content-edit-form-field-input-selector" onclick="BX('mp_allow_user_install_changed').value = 'Y'" />
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
<?php
		if($arResult['MP_ALLOW_USER_INSTALL_EXTENDED'])
		{
?>
		<tr id="mp_user_install" <?if(!$mpUserAllowInstall) echo ' style="display: none;"'?> >
			<td class="content-edit-form-field-name content-edit-form-field-name-left">&nbsp;</td>
			<td class="content-edit-form-field-input"><?
				$APPLICATION->IncludeComponent(
					"bitrix:main.user.selector",
					"",
					[
						"ID" => "mp_user_install_rights",
						"INPUT_NAME" => 'mp_user_install_rights[]',
						"LIST" => $arResult['MP_ALLOW_USER_INSTALL'],
						"USE_SYMBOLIC_ID" => true,
						"API_VERSION" => 3,
						"SELECTOR_OPTIONS" => array(
							'contextCode' => 'U',
							'context' => "mp_user_install_rights",
							'departmentSelectDisable' => 'N',
							'userSearchArea' => 'I',
							'enableAll' => 'Y',
							'departmentFlatEnable' => 'Y',
						),
					]
				);
			?>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
<?
		}

		if ($arResult["IS_BITRIX24"])
		{
		?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_register"><?=GetMessage('CONFIG_ALLOW_SELF_REGISTER')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="allow_register" id="configs_allow_register" <?if ($arResult["ALLOW_SELF_REGISTER"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_invite_users"><?=GetMessage('CONFIG_ALLOW_INVITE_USERS')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="allow_invite_users" value="Y" id="configs_allow_invite_users" <?if ($arResult["ALLOW_INVITE_USERS"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="configs_allow_new_user_lf"><?=GetMessage('CONFIG_ALLOW_NEW_USER_LF')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="allow_new_user_lf" value="Y" id="configs_allow_new_user_lf" <?if ($arResult["ALLOW_NEW_USER_LF"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="show_year_for_female"><?=GetMessage('CONFIG_SHOW_YEAR')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="show_year_for_female" value="N" id="show_year_for_female" <?if ($arResult["SHOW_YEAR_FOR_FEMALE"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?
		}

		if (
			!$arResult["IS_BITRIX24"]
			|| \Bitrix\Bitrix24\Release::isAvailable('stresslevel')
		)
		{
			?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="stresslevel_available"><?=GetMessage('CONFIG_STRESSLEVEL_AVAILABLE')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="stresslevel_available" value="Y" id="stresslevel_available" <?if ($arResult["STRESSLEVEL_AVAILABLE"] == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<?
		}

		if ($arResult["IS_BITRIX24"])
		{
			?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="buy_tariff_by_all"><?=GetMessage('CONFIG_BUY_TARIFF_BY_ALL_MSGVER_1')?></label></td>
				<td class="content-edit-form-field-input"><input type="checkbox" name="buy_tariff_by_all" value="N" id="buy_tariff_by_all" <?if (COption::GetOptionString("bitrix24", "buy_tariff_by_all", "Y") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<?
		}
		?>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="create_overdue_chats"><?=GetMessage('CONFIG_CREATE_OVERDUE_CHATS')?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="create_overdue_chats" id="create_overdue_chats" <?if (COption::GetOptionString("tasks", "create_overdue_chats", "N") == "Y"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="collect_geo_data"><?=Loc::getMessage('CONFIG_COLLECT_GEO_DATA') ?></label>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" name="collect_geo_data" id="collect_geo_data" value="Y"
					onclick="BX.Intranet.Configs.Functions.geoDataSwitch(this); "
					<? if (\Bitrix\Main\Config\Option::get('main', 'collect_geo_data', 'N') == 'Y'): ?> checked<? endif ?>
					class="content-edit-form-field-input-selector">
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="track_outgoing_emails_read"><?=Loc::getMessage('CONFIG_TRACK_OUTGOING_EMAILS_READ') ?></label>
				<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage('CONFIG_TRACK_OUTGOING_EMAILS_READ_HINT')) ?>"></span>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" name="track_outgoing_emails_read" id="track_outgoing_emails_read" value="Y"
					<? if (\Bitrix\Main\Config\Option::get('main', 'track_outgoing_emails_read', 'Y') == 'Y'): ?> checked<? endif ?>
					class="content-edit-form-field-input-selector">
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<label for="track_outgoing_emails_click"><?=Loc::getMessage('CONFIG_TRACK_OUTGOING_EMAILS_CLICK') ?></label>
				<span data-hint="<?=htmlspecialcharsbx(Loc::getMessage('CONFIG_TRACK_OUTGOING_EMAILS_CLICK_HINT')) ?>"></span>
			</td>
			<td class="content-edit-form-field-input">
				<input type="checkbox" name="track_outgoing_emails_click" id="track_outgoing_emails_click" value="Y"
					<? if (\Bitrix\Main\Config\Option::get('main', 'track_outgoing_emails_click', 'Y') == 'Y'): ?> checked<? endif ?>
					class="content-edit-form-field-input-selector">
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>

		<?if($arResult['SHOW_YANDEX_MAP_KEY_FIELD']):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_NAME_FILEMAN_YANDEX_MAP_API_KEY')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" name="yandex_map_api_key" value="<?=\Bitrix\Main\Text\HtmlFilter::encode($arResult['YANDEX_MAP_API_KEY'])?>">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
		<?endif;?>

		<?if($arResult['SHOW_GOOGLE_API_KEY_FIELD']):?>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_NAME_FILEMAN_GOOGLE_API_KEY')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" name="google_api_key" value="<?=\Bitrix\Main\Text\HtmlFilter::encode($arResult['GOOGLE_API_KEY'])?>">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<?/*if($arResult['GOOGLE_API_KEY_HOST'] <> '' && $arResult['GOOGLE_API_KEY'] <> ''):?>
				<tr>
					<td colspan="3" style="padding: 10px 20px">
						<div class="ui-alert ui-alert-warning">
							<span class="ui-alert-message"><?=GetMessage("CONFIG_NAME_GOOGLE_API_HOST_HINT", array(
								'#domain#' => \Bitrix\Main\Text\HtmlFilter::encode($arResult['GOOGLE_API_KEY_HOST'])
							))?></span>
						</div>
					</td>
				</tr>
			<?else:?>
				<tr>
					<td colspan="3" style="padding: 10px 20px">
						<div class="ui-alert ui-alert-warning">
							<span class="ui-alert-message"><?=GetMessage("CONFIG_NAME_GOOGLE_API_KEY_HINT")?></span>
						</div>
					</td>
				</tr>
			<?endif;*/?>
		<?endif;?>

	<!-- GDPR for Europe-->
		<?
		if (
			$arResult["IS_BITRIX24"]
			&& !in_array($arResult["LICENSE_PREFIX"], array("ru", "ua", "kz", "by"))
		)
		{
		?>
			<tr>
				<td class="content-edit-form-header" colspan="3">
					<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue">
						<?=Loc::getMessage("CONFIG_HEADER_GDRP", null, $arResult["LICENSE_PREFIX"])?>
					</div>
				</td>
			</tr>
			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-left">
					<?=GetMessage('CONFIG_GDRP_LABEL3')?>
				</td>
				<td class="content-edit-form-field-input">
					<input
						type="checkbox"
						name="gdpr_data_processing"
						onchange="BX.Intranet.Configs.Functions.onGdprChange(this);"
						<?if (COption::GetOptionString("bitrix24", "gdpr_data_processing", "") == "Y"):?>
							checked
						<?endif?>
						class="content-edit-form-field-input-selector"
					/>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<?
			$isGdprDataShown = COption::GetOptionString("bitrix24", "gdpr_data_processing", "") == "Y";
			?>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left" colspan="3"><?=GetMessage('CONFIG_GDRP_TITLE2')?></td>
			</tr>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_GDRP_LABEL4')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" type="text" name="gdpr_legal_name" value="<?=htmlspecialcharsbx(isset($_POST["gdpr_legal_name"]) ? $_POST["gdpr_legal_name"] : COption::GetOptionString("bitrix24", "gdpr_legal_name", ""))?>" size="60">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_GDRP_LABEL5')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" type="text" name="gdpr_contact_name" value="<?=htmlspecialcharsbx(isset($_POST["gdpr_contact_name"]) ? $_POST["gdpr_contact_name"] : COption::GetOptionString("bitrix24", "gdpr_contact_name", ""))?>" size="60">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_GDRP_LABEL6')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" type="text" name="gdpr_title" value="<?=htmlspecialcharsbx(isset($_POST["gdpr_title"]) ? $_POST["gdpr_title"] : COption::GetOptionString("bitrix24", "gdpr_title", ""))?>" size="60">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_GDRP_LABEL7')?></td>
				<td class="content-edit-form-field-input">
					<?$APPLICATION->IncludeComponent(
						"bitrix:main.calendar",
						"",
						array(
							"SHOW_INPUT"=>"Y",
							"INPUT_NAME"=> "gdpr_date",
							"INPUT_VALUE"=> htmlspecialcharsbx(isset($_POST["gdpr_date"]) ? $_POST["gdpr_date"] : COption::GetOptionString("bitrix24", "gdpr_date", "")),
							"INPUT_ADDITIONAL_ATTR"=>'class="content-edit-form-field-input-text" style="width: 100px;"',
							"SHOW_TIME" =>  'N',
						),
						$component,
						array("HIDE_ICONS"=>true)
					);?>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>
			<tr data-role="gdpr-data" <?if (!$isGdprDataShown):?>style="visibility:collapse"<?endif?>>
				<td class="content-edit-form-field-name content-edit-form-field-name-left"><?=GetMessage('CONFIG_GDRP_LABEL8')?></td>
				<td class="content-edit-form-field-input">
					<div class="ui-ctl ui-ctl-textbox">
						<input class="ui-ctl-element" type="text" name="gdpr_notification_email" value="<?=htmlspecialcharsbx(isset($_POST["gdpr_notification_email"]) ? $_POST["gdpr_notification_email"] : COption::GetOptionString("bitrix24", "gdpr_notification_email", ""))?>" size="60">
					</div>
				</td>
				<td class="content-edit-form-field-error"></td>
			</tr>

			<?\CJSCore::init("sidepanel");?>
			<tr>
				<td colspan="3" style="padding: 10px 20px">
					<div class="ui-alert ui-alert-warning">
						<span class="ui-alert-message">
						<?=GetMessage("CONFIG_GDRP_TITLE3")?><br/>
						<a href="javascript:void(0)" onclick="BX.SidePanel.Instance.open('<?=Marketplace::getMainDirectory()?>detail/integrations24.gdprstaff/');"><?=GetMessage("CONFIG_GDRP_APP1")?></a>
						<br/>
						<a href="javascript:void(0)" onclick="BX.SidePanel.Instance.open('<?=Marketplace::getMainDirectory()?>detail/integrations24.gdpr/');"><?=GetMessage("CONFIG_GDRP_APP2")?></a>
						</span>
					</div>
				</td>
			</tr>
		<?
		}
		?>
	<!-- //GDPR for Europe-->
	<?
	if ($arResult["SECURITY_MODULE"])
	{
	?>
	<!-- secutity -->
		<tr>
			<td class="content-edit-form-header" colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_HEADER_SECUTIRY')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<?=GetMessage('CONFIG_OTP_SECURITY2')?>
			</td>
			<td class="content-edit-form-field-input">
				<input
					name="security_otp"
					type="checkbox"
					class="content-edit-form-field-input-selector"
					<?if (!$arResult["SECURITY_IS_USER_OTP_ACTIVE"] && !$arResult["SECURITY_OTP"]):?>
						onclick="BX.Intranet.Configs.Functions.adminOtpIsRequiredInfo(this);return false;"
					<?endif?>
					onchange="BX.Intranet.Configs.Functions.otpSwitchOffInfo(this);"
					<?if ($arResult["SECURITY_OTP"]):?>checked<?endif?>
				/>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<?=GetMessage('CONFIG_OTP_SECURITY_DAYS')?>
			</td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select id="security_otp_days" name="security_otp_days" class="ui-ctl-element">
						<?for($i=5; $i<=10; $i++):?>
							<option
								value="<?=$i?>"
								<?if ($arResult["SECURITY_OTP_DAYS"] == $i) echo 'selected="selected"';?>
							><?=FormatDate("ddiff", time()-60*60*24*$i)?></option>
						<?endfor;?>
					</select>
				</div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?
		if ($arResult["IM_MODULE"])
		{
		?>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"><label for="send_otp_push"><?echo GetMessage("CONFIG_SEND_OTP_PUSH")?></label></td>
			<td class="content-edit-form-field-input"><input type="checkbox" name="send_otp_push" id="send_otp_push" <?if (COption::GetOptionString("intranet", "send_otp_push", "Y") <> "N"):?>checked<?endif?> class="content-edit-form-field-input-selector"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?
		}
		?>
		<tr>
			<td colspan="3" style="padding: 10px 20px">
				<div class="ui-alert ui-alert-warning">
					<span class="ui-alert-message">
					<?=GetMessage("CONFIG_OTP_SECURITY_INFO")?>
					<a href="javascript:void(0)" onclick="BX.nextSibling(this).style.display='block'; BX.remove(this)"><?=GetMessage("CONFIG_MORE")?></a>
					<span style="display: none">
						<?=GetMessage("CONFIG_OTP_SECURITY_INFO_1")?>
						<?=!$arResult["IS_BITRIX24"] ? GetMessage("CONFIG_OTP_SECURITY_INFO_2") : "";?>
						<?=GetMessage("CONFIG_OTP_SECURITY_INFO_3")?>
					</span>
					</span>
				</div>
			</td>
		</tr>
	<?
	}

	if ($arResult["IS_BITRIX24"])
	{
	?>
	<!-- features -->
		<tr>
			<td class="content-edit-form-header " colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_FEATURES_TITLE')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input" colspan="2">

				<input type="checkbox" name="feature_crm" id="feature_crm" <?if (IsModuleInstalled("crm")) echo "checked"?>/>
				<label for="feature_crm"><?=GetMessage("CONFIG_FEATURES_CRM")?></label><br/>

				<input type="checkbox" name="feature_extranet" id="feature_extranet" <?if (IsModuleInstalled("extranet")) echo "checked"?>/>
				<label for="feature_extranet"><?=GetMessage("CONFIG_FEATURES_EXTRANET")?></label><br/>

				<?if (Feature::isFeatureEnabled("timeman")):?>
					<input type="checkbox" name="feature_timeman" id="feature_timeman" <?if (IsModuleInstalled("timeman")) echo "checked"?>/>
					<label for="feature_timeman"><?=GetMessage("CONFIG_FEATURES_TIMEMAN")?></label><br/>
				<?endif?>
				<?if (Feature::isFeatureEnabled("meeting")):?>
					<input type="checkbox" name="feature_meeting" id="feature_meeting" <?if (IsModuleInstalled("meeting")) echo "checked"?>/>
					<label for="feature_meeting"><?=GetMessage("CONFIG_FEATURES_MEETING")?></label><br/>
				<?endif?>
				<?if (Feature::isFeatureEnabled("lists")):?>
					<input type="checkbox" name="feature_lists" id="feature_lists" <?if (IsModuleInstalled("lists")) echo "checked"?>/>
					<label for="feature_lists"><?=GetMessage("CONFIG_FEATURES_LISTS")?></label><br/>
				<?endif?>
			</td>
		</tr>
	<!--ip -->
		<?
		$arCurIpRights = $arResult["IP_RIGHTS"];
		if (!is_array($arCurIpRights))
			$arCurIpRights = array();
		$access = new CAccess();
		$arNames = $access->GetNames(array_keys($arCurIpRights));
		?>
		<tr>
			<td class="content-edit-form-header " colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_IP_TITLE')?></div>
			</td>
		</tr>
		<?
		if (Feature::isFeatureEnabled("ip_access_rights"))
		{
			foreach($arCurIpRights as $right => $arIps)
			{
				?>
				<tr data-bx-right="<?=$right?>">
					<td class="content-edit-form-field-name">
						<?=(!empty($arNames[$right]["provider"]) ? $arNames[$right]["provider"].": " : "").htmlspecialcharsbx($arNames[$right]["name"])?>
					</td>
					<td class="content-edit-form-field-input" colspan="2">
						<?foreach($arIps as $ip):?>
							<div>
								<input name="ip_access_rights_<?=$right?>[]" value="<?=$ip?>" size="30"/>
								<a href="javascript:void(0);" data-role="ip-right-delete" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a>
							</div>
						<?endforeach?>
						<div>
							<input name="ip_access_rights_<?=$right?>[]" size="30" onclick="BX.Intranet.Configs.IpSettingsClass.addInputForIp(this)"/>
							<a href="javascript:void(0);" data-role="ip-right-delete" class="access-delete" title="<?=GetMessage("CONFIG_TOALL_DEL")?>"></a>
						</div>
					</td>
				</tr>
				<?
			}
		}
		?>
		<tr id="ip_add_right_button">
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a href="javascript:void(0)" class="bx-action-href" data-role="ip-add-button"><?
					echo GetMessage("CONFIG_TOALL_RIGHTS_ADD");
					if (!Feature::isFeatureEnabled("ip_access_rights")):
						?><span class="tariff-lock" style="padding-left: 9px"></span><?
					endif;
				?></a>
			</td>
		</tr>

		<tr>
			<td colspan="3" style="padding: 10px 20px">
				<div class="ui-alert ui-alert-warning">
					<span class="ui-alert-message"><?=Loc::getMessage("CONFIG_IP_HELP_TEXT2")?></span>
				</div>
			</td>
		</tr>

		<?if (LANGUAGE_ID == "ru"):?>
		<!-- marketplace -->
		<tr>
			<td class="content-edit-form-header " colspan="3" >
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_MARKETPLACE_TITLE')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left"></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a href="<?=Marketplace::getMainDirectory()?>category/migration/"><?=GetMessage("CONFIG_MARKETPLACE_MORE")?></a>
			</td>
		</tr>
		<?endif?>
	<?
	}


	if (isset($arResult['ALLOW_DOMAIN_CHANGE']) && $arResult['ALLOW_DOMAIN_CHANGE'])
	{
	?>
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_NAME_CHANGE_SECTION')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_NAME_CHANGE_FIELD')?></td>
			<td class="content-edit-form-field-input" colspan="2">
				<a href="javascript:void(0)" onclick="BX.Bitrix24.renamePortal()"><?=GetMessage('CONFIG_NAME_CHANGE_ACTION')?></a>
			</td>
		</tr>
		<tr>
			<td colspan="3" style="padding: 10px 20px">
				<div class="ui-alert ui-alert-warning">
					<span class="ui-alert-message"><?=GetMessage("CONFIG_NAME_CHANGE_INFO")?></span>
				</div>
			</td>
		</tr>
	<?
	}

	if($arResult['IS_LOCATION_MODULE_INCLUDED'])
	{
		?>
		<tr>
			<td class="content-edit-form-header " colspan="3">
				<div class="content-edit-form-header-wrap content-edit-form-header-wrap-blue"><?=GetMessage('CONFIG_LOCATION_SOURCES_SETTINGS')?></div>
			</td>
		</tr>
		<tr>
			<td class="content-edit-form-field-name content-edit-form-field-name-left">
				<?=Loc::getMessage('CONFIG_NAME_CURRENT_MAP_PROVIDER')?>
			</td>
			<td class="content-edit-form-field-input">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element" name="LOCATION_SOURCE_CODE">
						<?
						$locationSourceCode = Location\Infrastructure\SourceCodePicker::getSourceCode();

						/** @var Bitrix\Location\Entity\Source $source */
						foreach ($arResult['LOCATION_SOURCES'] as $source):
							$sourceCode = $source->getCode();
							$sourceName = $source->getName();
							$isSelected = ($locationSourceCode === $sourceCode);
							?>
							<option value="<?=htmlspecialcharsbx($source->getCode())?>" <?=($isSelected) ? ' selected="selected" ' : ''?>>
								<?=htmlspecialcharsbx($sourceName)?>
							</option>
						<?endforeach;?>
					</select>
				</div>
			</td>
		</tr>
		<?
		/** @var Bitrix\Location\Entity\Source $source */
		foreach ($arResult['LOCATION_SOURCES'] as $source):
			$sourceCode = $source->getCode();

			/**
			 * OSM provider does not have any settings
			 */
			if ($sourceCode === \Bitrix\Location\Entity\Source\Factory::OSM_SOURCE_CODE)
			{
				continue;
			}

			$config = $source->getConfig();
			$note = Loc::getMessage(
				sprintf(
					'CONFIG_LOCATION_SOURCE_%s_NOTE',
					$sourceCode
				)
			);
			?>
			<tr class="heading">
				<td></td>
				<td>
					<strong>
						<?=htmlspecialcharsbx(
							Loc::getMessage('CONFIG_NAME_MAP_PROVIDER_SETTINGS', ['#PROVIDER#' => $source->getName()])
						)?>
					</strong>
				</td>
			</tr>
			<?if (!is_null($config)):?>
				<?
				/** @var ConfigItem $configItem */
				foreach ($config as $configItem):
					if (!$configItem->isVisible())
					{
						continue;
					}

					$code = $configItem->getCode();

					$inputName = sprintf(
						'LOCATION_SOURCE[%s][CONFIG][%s]',
						$sourceCode,
						$code
					);
					$name = Loc::getMessage(
						sprintf(
							'CONFIG_LOCATION_SOURCE_%s_%s',
							$sourceCode,
							$code
						)
					);
					?>
					<tr>
						<td class="content-edit-form-field-name">
							<?=$name?>
						</td>
						<td class="content-edit-form-field-input" colspan="2">
							<?if ($configItem->getType() == ConfigItem::STRING_TYPE):?>
								<div class="ui-ctl ui-ctl-textbox">
									<input type="text" class="ui-ctl-element" name="<?=htmlspecialcharsbx($inputName)?>" value="<?=htmlspecialcharsbx($configItem->getValue())?>">
								</div>
							<?elseif ($configItem->getType() == ConfigItem::BOOL_TYPE):?>
								<input type="hidden" name="<?=htmlspecialcharsbx($inputName)?>" value="N">
								<input type="checkbox" name="<?=htmlspecialcharsbx($inputName)?>" value="Y" <?=($configItem->getValue() ? ' checked' : '')?> >
							<?endif;?>
						</td>
					</tr>
				<?endforeach;?>
			<?endif;?>

			<?if ($note):?>
				<tr>
					<td colspan="3" style="padding: 10px 20px">
						<div class="ui-alert ui-alert-warning">
							<span class="ui-alert-message"><?=$note?></span>
						</div>
					</td>
				</tr>
			<?endif;?>
		<?endforeach;?>

		<?
	}
	?>
	</table>
</form>

<br/><br/>
<table class="content-edit-form" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content-edit-form-buttons" style="border-top: 1px #eaeae1 solid; text-align:center">
			<span onclick="BX.Intranet.Configs.Functions.submitForm(this)" class="webform-button webform-button-create">
				<?=GetMessage("CONFIG_SAVE")?>
			</span>
		</td>
	</tr>
</table>
<br/><br/><br/><br/>
<!-- logo -->
<?
$clientLogoID = "";
$clientLogoRetinaID = "";
$isLogoFeatureAvailable = $arResult["IS_BITRIX24"] && Feature::isFeatureEnabled("set_logo") || !$arResult["IS_BITRIX24"];

if ($isLogoFeatureAvailable)
{
	$clientLogoID = COption::GetOptionInt("bitrix24", "client_logo", "");
	$clientLogoRetinaID = COption::GetOptionInt("bitrix24", "client_logo_retina", "");
}
?>
<table id="content-edit-form-config" class="content-edit-form" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content-edit-form-header" colspan="3" >
			<div class="content-edit-form-header-wrap content-edit-form-header-wrap"><?=GetMessage('CONFIG_CLIENT_LOGO')?></div>
		</td>
	</tr>
	<tr>
		<td colspan="3" >
			<div class="content-edit-form-notice-error" style="display: none;" id="config_logo_error_block">
				<span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span></span>
			</div>
		</td>
	</tr>
	<tr>
		<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_CLIENT_LOGO')?></td>
		<td class="content-edit-form-field-input" colspan="2">
			<?=GetMessage('CONFIG_CLIENT_LOGO_DESCR')?>
			<form name="configLogoPostForm" id="configLogoPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
				<?=bitrix_sessid_post();?>
				<div>
					<label for="clientLogo" class="config-webform-field-upload" style="margin-top:10px;vertical-align: middle;" onmouseup="BX.removeClass(this,'content-edit-form-button-press')" onmousedown="BX.addClass(this, 'content-edit-form-button-press')">
						<span class="ui-btn ui-btn-light-border <?=($isLogoFeatureAvailable ? "" : "ui-btn-disabled")?>"><?=GetMessage('CONFIG_ADD_LOGO_BUTTON')?></span>
						<?if (!$isLogoFeatureAvailable): ?>
							<span class="tariff-lock" style="padding-left: 9px" onclick="BX.UI.InfoHelper.show('limit_admin_logo');"></span>
						<?else:?>
							<input type="file" name="client_logo" id="clientLogo" value=""/>
						<?endif?>
					</label>

					<br/><br/>
					<div id="configWaitLogo" style="display:none;"><img src="<?=$this->GetFolder();?>/images/wait.gif"/></div>

					<div id="configBlockLogo" class="config-webform-logo-img" <?if (!$clientLogoID):?>style="display:none"<?endif?>>
						<img id="configImgLogo" src="<?if ($clientLogoID) echo CFile::GetPath($clientLogoID)?>" />
					</div>

					<a href="javascript:void(0)" id="configDeleteLogo" class="config_logo_delete_link"  <?if (!$clientLogoID):?>style="display:none"<?endif?>>
						<?=GetMessage("CONFIG_ADD_LOGO_DELETE")?>
					</a>
				</div>
			</form>
		</td>
	</tr>

	<tr>
		<td class="content-edit-form-field-name" style="width:370px; padding-right:30px"><?=GetMessage('CONFIG_CLIENT_LOGO_RETINA')?></td>
		<td class="content-edit-form-field-input" colspan="2">
			<?=GetMessage('CONFIG_CLIENT_LOGO_DESC_RETINA')?>
			<form name="configLogoRetinaPostForm" id="configLogoRetinaPostForm" method="POST" action="<?=POST_FORM_ACTION_URI?>" enctype="multipart/form-data">
				<?=bitrix_sessid_post();?>

				<!-- retina -->
				<div style="margin-top:20px">
					<label for="clientLogoretina" class="config-webform-field-upload" style="margin-top:10px;vertical-align: middle;" onmouseup="BX.removeClass(this,'content-edit-form-button-press')" onmousedown="BX.addClass(this, 'content-edit-form-button-press')">
						<span class="ui-btn ui-btn-light-border <?=($isLogoFeatureAvailable ? "" : "ui-btn-disabled")?>"><?=GetMessage('CONFIG_ADD_LOGO_BUTTON')?></span>
						<?if (!$isLogoFeatureAvailable): ?>
							<span class="tariff-lock" style="padding-left: 9px" onclick="BX.UI.InfoHelper.show('limit_admin_logo');"></span>
						<?else:?>
							<input type="file" name="client_logo_retina" id="clientLogoretina" value=""/>
						<?endif?>
					</label>
					<br/><br/>
					<div id="configWaitLogoretina" style="display:none;"><img src="<?=$this->GetFolder();?>/images/wait.gif"/></div>

					<div id="configBlockLogoretina" class="config-webform-logo-img" <?if (!$clientLogoRetinaID):?>style="display:none"<?endif?>>
						<img id="configImgLogoretina" src="<?if ($clientLogoRetinaID) echo CFile::GetPath($clientLogoRetinaID)?>" />
					</div>

					<a href="javascript:void(0)" id="configDeleteLogoretina" class="config_logo_retina_delete_link"  <?if (!$clientLogoRetinaID):?>style="display:none"<?endif?>>
						<?=GetMessage("CONFIG_ADD_LOGO_DELETE")?>
					</a>
				</div>
			</form>
		</td>
	</tr>
</table>

<?
if (isset($_GET["otp"]))
{
?>
	<form id="bitrix24-otp-tell-about-form" style="display: none" action="<?= htmlspecialcharsbx($arParams["CONFIG_PATH_TO_POST"]) ?>" method="POST">
		<div style="padding: 4px">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="POST_TITLE" value="<?=GetMessage("CONFIG_OTP_IMPORTANT_TITLE")?>">
			<textarea name="POST_MESSAGE" style="display: none"><?=GetMessage("CONFIG_OTP_IMPORTANT_TEXT")?></textarea>
			<input type="hidden" name="changePostFormTab" value="important">
			<div style="margin-bottom: 10px; font-size: 14px">
				<?=GetMessage("CONFIG_OTP_POPUP_TEXT")?>
			</div>
		</div>
	</form>

	<script>
		BX.ready(function(){
			var popup = BX.PopupWindowManager.create("bitrix24OtpImportant", null, {
				autoHide: true,
				offsetLeft: 0,
				offsetTop: 0,
				overlay : true,
				draggable: { restrict:true },
				closeByEsc: true,
				closeIcon: true,
				titleBar: "<?=GetMessageJS("CONFIG_OTP_POPUP_TITLE")?>",
				content: BX("bitrix24-otp-tell-about-form"),
				buttons: [
					new BX.PopupWindowButton({
						text: "<?=GetMessage("CONFIG_OTP_POPUP_SHARE")?>",
						className: "popup-window-button-accept",
						events: {
							click: function() {
								BX.submit(BX("bitrix24-otp-tell-about-form"), 'dummy')
							}
						}
					}),
					new BX.PopupWindowButtonLink({
						text: "<?=GetMessage("CONFIG_OTP_POPUP_CLOSE")?>",
						events: {
							click: function() {
								this.popupWindow.close();
							}
						}
					})
				]
			});

			popup.show();
		});
	</script>
<?
}
?>

<script>
	BX.message({
		SLToAllDel: '<?=CUtil::JSEscape(GetMessage("CONFIG_TOALL_DEL"))?>',
		LogoDeleteConfirm: '<?=GetMessageJS("CONFIG_ADD_LOGO_DELETE_CONFIRM")?>',
		CONFIG_OTP_SECURITY_SWITCH_OFF_INFO: '<?=GetMessageJS("CONFIG_OTP_SECURITY_SWITCH_OFF_INFO")?>',
		CONFIG_OTP_ADMIN_IS_REQUIRED_INFO: '<?=GetMessageJS("CONFIG_OTP_ADMIN_IS_REQUIRED_INFO")?>',
		CONFIG_DISK_EXTENDED_FULLTEXT_INFO: "<?=GetMessageJS("CONFIG_DISK_EXTENDED_FULLTEXT_INFO")?>",
		CONFIG_COLLECT_GEO_DATA: '<?=\CUtil::jsEscape(Loc::getMessage('CONFIG_COLLECT_GEO_DATA')) ?>',
		CONFIG_COLLECT_GEO_DATA_CONFIRM: '<?=\CUtil::jsEscape(Loc::getMessage('CONFIG_COLLECT_GEO_DATA_CONFIRM')) ?>',
		CONFIG_COLLECT_GEO_DATA_OK: '<?=\CUtil::jsEscape(Loc::getMessage('CONFIG_COLLECT_GEO_DATA_OK')) ?>'
	});

	BX.ready(function(){
		var initData = {
			ajaxPath: "<?=CUtil::JSEscape(POST_FORM_ACTION_URI)?>",
			cultureList: <?=CUtil::PhpToJSObject($arResult['CULTURES'])?>
		};

		<?if($arResult['SHOW_ADDRESS_FORMAT']):?>
			initData["addressFormatList"] = <?=CUtil::PhpToJSObject($arResult['LOCATION_ADDRESS_FORMAT_DESCRIPTION_LIST'])?>;
		<?endif;?>

		BX.Intranet.Configs.Functions = new BX.Intranet.Configs.Functions(initData);

		<?if ($arResult["IS_BITRIX24"]):?>
			BX.Intranet.Configs.IpSettingsClass = new BX.Intranet.Configs.IpSettingsClass(<?=CUtil::PhpToJSObject(array_keys($arCurIpRights))?>);
			var ipAddButton = document.querySelector("[data-role='ip-add-button']");
			if (BX.type.isDomNode(ipAddButton))
			{
				BX.bind(ipAddButton, "click", function(){
					<?if (Feature::isFeatureEnabled("ip_access_rights")):?>
						BX.Intranet.Configs.IpSettingsClass.ShowIpAccessPopup(BX.Intranet.Configs.IpSettingsClass.arCurIpRights);
					<?else:?>
						BX.UI.InfoHelper.show('limit_admin_ip');
				<?endif?>
				});
			}
		<?endif?>
	});

	BX.UI.Hint.init(BX('configPostForm'));

	<?if ($arResult['SHOW_RENAME_POPUP']):?>
		BX.ready(function(){
			if (typeof BX.Bitrix24.renamePortal != 'undefined')
				BX.Bitrix24.renamePortal();
		});
	<?endif?>
</script>