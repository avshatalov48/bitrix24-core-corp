<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */


CJSCore::Init(array('fx'));
$APPLICATION->SetAdditionalCSS("/bitrix/js/intranet/intranet-common.css");

$APPLICATION->SetPageProperty("BodyClass", "page-one-column");

$bNetwork = $arResult["IS_BITRIX24"] && COption::GetOptionString('bitrix24', 'network', 'N') == 'Y';

if ($arResult["User"]['EXTERNAL_AUTH_ID'] == 'email')
{
	$arFields = array(
		'MAIN' => array(
			'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHOTO',
		),

		'PERSONAL' => array(),
	);
}
elseif ($arResult["IS_BITRIX24"])
{
	$arFields = array(
		'MAIN' => array(
			'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHOTO',  'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE', 'UF_PHONE_INNER', 'WORK_POSITION', 'GROUP_ID', "UF_DEPARTMENT"
		),

		'PERSONAL' => array(
			'AUTO_TIME_ZONE', 'TIME_ZONE',  'PERSONAL_WWW', 'PERSONAL_CITY', 'WORK_COMPANY', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDAY',
			'UF_SKYPE', "UF_TWITTER", "UF_FACEBOOK", "UF_LINKEDIN", "UF_XING", 'UF_WEB_SITES', 'UF_SKILLS', 'US_INTERESTS'
		),
	);
	if(CModule::IncludeModule("socialservices"))
	{
		if($bNetwork)
		{
			$arFields = array_merge(array('SOCSERV' => array('SOCSERVICES')), $arFields);
		}
		else
		{
			$arFields['SOCSERV'] = array('SOCSERVICES');
		}
	}
}
else
{
	$arFields = array(
		'MAIN' => array(
			'NAME', 'LAST_NAME', 'SECOND_NAME', 'PERSONAL_PHOTO',  'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE', 'UF_PHONE_INNER', 'WORK_POSITION'
		),

		'PERSONAL' => array(
			'AUTO_TIME_ZONE', 'TIME_ZONE',  'PERSONAL_WWW', 'PERSONAL_CITY', 'PERSONAL_GENDER', 'PERSONAL_BIRTHDAY', 'PERSONAL_BIRTHDATE',
			'UF_SKYPE', "UF_TWITTER", "UF_FACEBOOK", "UF_LINKEDIN", "UF_XING", 'UF_WEB_SITES', 'UF_SKILLS', 'US_INTERESTS',
			'PERSONAL_ICQ', 'PERSONAL_FAX', 'PERSONAL_PAGER', 'PERSONAL_COUNTRY', 'PERSONAL_STREET', 'PERSONAL_MAILBOX', 'PERSONAL_CITY', 'PERSONAL_STATE', 'PERSONAL_ZIP', 'WORK_COUNTRY',
			'WORK_CITY', 'WORK_COMPANY', 'WORK_DEPARTMENT', 'WORK_PROFILE', 'WORK_WWW', 'WORK_FAX', 'WORK_PAGER', 'WORK_LOGO', 'PERSONAL_PROFESSION', 'PERSONAL_NOTES'
		),
	);
	if(CModule::IncludeModule("socialservices"))
		$arFields['SOCSERV'] = array('SOCSERVICES');
}

if (
	$arParams['IS_FORUM'] == 'Y'
	&& $arResult["User"]['EXTERNAL_AUTH_ID'] != 'email'
)
{
	$arFields['PERSONAL'][] = 'FORUM_SHOW_NAME';
}

if ($arResult["IS_BITRIX24"] && !in_array(\CBitrix24::getLicensePrefix(), array("ru", "ua", "kz", "by")))
{
	unset($arFields["MAIN"][array_search("SECOND_NAME", $arFields["MAIN"])]);
}

$arSocialFieldsAll = array('UF_TWITTER', 'UF_FACEBOOK', 'UF_LINKEDIN', 'UF_XING');
$arSocialFields = array();

foreach ($arParams['EDITABLE_FIELDS'] as $FIELD)
{
	$bFound = false;
	if ($arResult['USER_PROP'][$FIELD])
	{
		foreach ($arFields as $FIELD_TYPE => $arTypeFields)
		{
			if (in_array($FIELD, $arFields[$FIELD_TYPE]))
			{
				$arFields[$FIELD_TYPE][] = $FIELD;
				$bFound = true;
				break;
			}
		}

		if (
			!$bFound
			&& !$arResult["User"]["IS_EMAIL"]
		)
		{
			$arFields['PERSONAL'][] = $FIELD;
		}
	}

	if (in_array($FIELD, $arSocialFieldsAll))
		$arSocialFields[] = $FIELD;
}

$UF_DEP = array_search('UF_DEPARTMENT', $arFields['MAIN']);
if ($arResult["User"]["IS_EXTRANET"] && $UF_DEP)
	unset($arFields['MAIN'][$UF_DEP]);

if ($arResult["User"]["IS_EXTRANET"] && in_array("UF_DEPARTMENT", $arParams['EDITABLE_FIELDS']))
{
	$key = array_search("UF_DEPARTMENT", $arParams['EDITABLE_FIELDS']);
	if ($key !== false)
		unset($arParams['EDITABLE_FIELDS'][$key]);
}

$GROUP_ACTIVE = false;

foreach ($arFields as $GROUP => $arGroupFields)
{
	$arFields[$GROUP] = array_unique($arGroupFields);

	foreach ($arGroupFields as $fkey => $FIELD)
	{
		if (!in_array($FIELD, $arParams['EDITABLE_FIELDS']))
		{
			unset($arGroupFields[$fkey]);
		}
		elseif(!$GROUP_ACTIVE)
			$GROUP_ACTIVE = $GROUP;
	}

	$arFields[$GROUP] = array_unique($arGroupFields);
}


if (count($arSocialFields) > 0)
{
	$SocNetProfileExample = array(
		"UF_TWITTER" => "your_account",
		"UF_FACEBOOK" => "http://www.facebook.com/your_profile",
		"UF_XING" => "http://www.xing.com/profile/your_profile",
		"UF_LINKEDIN" => "http://www.linkedin.com/profile/view?id=your_id"
	);
	$sonetLinkAddShow = false;
	foreach ($arSocialFields as $key => $val)
	{
		if (!($arResult['User'][$val]))
		{
			$sonetLinkAddShow = true;
			break;
		}
	}
}

if ($arResult['ERROR_MESSAGE'])
{
	?>
<div class="content-edit-form-notice-error"><span class="content-edit-form-notice-text"><span class="content-edit-form-notice-icon"></span><?=$arResult['ERROR_MESSAGE']?></span></div>
<?
}
?>

<form id="bx_user_profile_form" name="user_profile_edit" method="POST" action="<?echo POST_FORM_ACTION_URI;?>" enctype="multipart/form-data" autocomplete="off">
	<? if (array_search("PERSONAL_GENDER", $arFields["PERSONAL"]) === false):?><input type="hidden" name="PERSONAL_GENDER" value="<?=$arResult["User"]["PERSONAL_GENDER"]?>" /><?endif?>
	<?echo bitrix_sessid_post()?>
<table id="content-edit-form-1" class="content-edit-form" cellspacing="0" cellpadding="0">

<?
foreach ($arFields as $GROUP_ID => $arGroupFields):
	$groupMsg = "SOCNET_SUPE_TPL_GROUP_".$GROUP_ID;
	$additionalClassName = "";
	if($bNetwork && $GROUP_ID == "SOCSERV")
	{
		$groupMsg = "SOCNET_SUPE_TPL_GROUP_AUTH";
		$additionalClassName = 'content-edit-form-header-wrap-blue';
	}
	elseif($GROUP_ID == "MAIN")
	{
		$additionalClassName = "content-edit-form-header-wrap-blue";
	}

	if (!empty($arGroupFields))
	{
		?>
		<tr>
			<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
				<div class="content-edit-form-header-wrap <?=$additionalClassName;?>"><?=GetMessage($groupMsg)?></div>
			</td>
		</tr>
		<?
	}

	if ($GROUP_ID == "MAIN"):?>
		<?if (in_array('PASSWORD', $arParams['EDITABLE_FIELDS'])):?>
		<tr>
			<td class="content-edit-form-field-name"></td>
			<td class="content-edit-form-event-link" colspan="2"><a href="#showPassword" onclick="return bxUpeToggleHiddenField('content-edit-form-1', 'password')" class="content-edit-form-event-link-tag"><span class="content-edit-form-event-link-icon content-edit-form-icons content-edit-form-icon-password"></span><span class="content-edit-form-event-link-name"><?=GetMessage("SOCNET_CHANGE_PASSWORD")?></span></a></td>
		</tr>
		<tr data-field-id="password" style="display:none">
			<td class="content-edit-form-field-name"><?=GetMessage("ISL_PASSWORD_NEW")?></td>
			<td class="content-edit-form-field-input">
				<input type="password" style="display: none; ">
				<input name="PASSWORD" autocomplete="new-password" type="password" class="content-edit-form-field-input-text"/>
				<div style="color: rgb(181, 178, 178); font-size: 11px; line-height: 1.5"><?=$arResult["User"]["PASSWORD_REQUIREMENTS"]?></div>
			</td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<tr data-field-id="password" style="display:none">
			<td class="content-edit-form-field-name"><?=GetMessage("ISL_CONFIRM_PASSWORD")?></td>
			<td class="content-edit-form-field-input"><input name="CONFIRM_PASSWORD" autocomplete="off" type="password" class="content-edit-form-field-input-text"/></td>
			<td class="content-edit-form-field-error"></td>
		</tr>
		<?endif;?>
		<?if (in_array('EMAIL', $arParams['EDITABLE_FIELDS'])):?>
		<tr>
			<td class="content-edit-form-field-name"><?=$bNetwork ? GetMessage("ISL_EMAIL_CONTACT") : GetMessage("ISL_EMAIL");?></td>
			<td class="content-edit-form-field-input">
				<input type="text" name="EMAIL" value="<?=$arResult['User']["EMAIL"]?>" class="content-edit-form-field-input-text"/>
				<input type="hidden" name="LOGIN" value="<?=$arResult['User']["LOGIN"]?>"/>
			</td>
			<td class="content-edit-form-field-error">&nbsp;</td>
		</tr>
		<?endif;?>
	<?endif;?>
	<?foreach ($arGroupFields as $FIELD):
		if ($FIELD == "FORUM_SHOW_NAME")
		{
			?><tr><td><input type="hidden" name="<?echo $FIELD?>" value="Y"/></td></tr><?
			continue;
		}
		$value = $arResult['User'][$FIELD];?>
	<tr <?if ($arResult["IS_BITRIX24"] && (in_array($FIELD, array("UF_TWITTER", "UF_FACEBOOK", "UF_LINKEDIN", "UF_XING")) || in_array($FIELD, array("PERSONAL_MOBILE", "WORK_PHONE", "UF_PHONE_INNER"))) && empty($value) || $FIELD == "GROUP_ID" && $arResult["User"]["IS_EXTRANET"]):?>style="display:none"<?endif;?> data-role="<?=$FIELD?>"<?=($FIELD == "TIME_ZONE" ? " data-field-id=\"time_zone\" style=\"display: ".($arResult["User"]["AUTO_TIME_ZONE"] <> "N" ? "none" : "table-row").";\"" : "")?>>
<?if(!$bNetwork || $GROUP_ID !== "SOCSERV"):?>
		<td class="content-edit-form-field-name"><?=$arResult['USER_PROP'][$FIELD] ? $arResult['USER_PROP'][$FIELD] : GetMessage('ISL_'.$FIELD);?></td>
<?endif;?>
		<?
		switch ($FIELD)
		{
			case "PASSWORD":
			case "CONFIRM_PASSWORD":
				break;
			case "AUTO_TIME_ZONE":
				?>
				<td class="content-edit-form-field-input" colspan="2">
					<select name="AUTO_TIME_ZONE" onchange="this.form.TIME_ZONE.disabled=(this.value != 'N'); bxUpeToggleHiddenField('content-edit-form-1', 'time_zone', (this.value == 'N'));" class="content-edit-form-field-input-select">
						<option value=""><?echo GetMessage("soc_profile_time_zones_auto_def")?></option>
						<option value="Y"<?=($value == "Y"? ' SELECTED="SELECTED"' : '')?>><?echo GetMessage("soc_profile_time_zones_auto_yes")?></option>
						<option value="N"<?=($value == "N"? ' SELECTED="SELECTED"' : '')?>><?echo GetMessage("soc_profile_time_zones_auto_no")?></option>
					</select>
				</td><?
				break;
			case 'TIME_ZONE':
				?>
				<td class="content-edit-form-field-input" colspan="2">
					<select name="TIME_ZONE"<?if($arResult["User"]["AUTO_TIME_ZONE"] <> "N") echo '  disabled="disabled"'?> class="content-edit-form-field-input-select">
						<?if (is_array($arResult["TIME_ZONE_LIST"]) && !empty($arResult["TIME_ZONE_LIST"])):?>
						<?foreach($arResult["TIME_ZONE_LIST"] as $tz=>$tz_name):?>
							<option value="<?=htmlspecialcharsbx($tz)?>"<?=($value == $tz? ' SELECTED="SELECTED"' : '')?>><?=htmlspecialcharsbx($tz_name)?></option>
							<?endforeach?>
						<?endif?>
					</select>
				</td><?
				break;
			case 'PERSONAL_COUNTRY':
			case 'WORK_COUNTRY':
				?>
				<td class="content-edit-form-field-input" colspan="2">
					<?echo SelectBoxFromArray($FIELD, GetCountryArray(), $value, GetMessage("ISL_COUNTRY_EMPTY"), "class=\"content-edit-form-field-input-select\"");?>
				</td><?
				break;
			case 'SOCSERVICES':
				if(CModule::IncludeModule("socialservices")):
					?>
					<td class="content-edit-form-field-input" colspan="<?=$bNetwork?3:2?>">
						<div class="bx-sonet-profile-field-socserv">
							<?
							$APPLICATION->IncludeComponent("bitrix:socserv.auth.split", $bNetwork ? "network" : "twitpost", array(
									"SHOW_PROFILES" => "Y",
									"CAN_DELETE" => "Y",
									"USER_ID" => $arParams['ID']
								),
								false
							);
							?>
						</div>
					</td>
					<?
				endif;
				break;
			case "PERSONAL_GENDER":?>
				<td class="content-edit-form-field-input" colspan="2">
					<div><label><input type="radio" name="<?echo $FIELD?>" value="M"<?echo $value == 'M' ? ' checked="checked"' : ''?> class="content-edit-form-field-input-selector" /><span class="content-edit-form-field-input-selector-name"><?echo GetMessage('ISL_PERSONAL_GENDER_MALE')?></span></label></div>
					<div><label><input type="radio" name="<?echo $FIELD?>" value="F"<?echo $value == 'F' ? ' checked="checked"' : ''?> class="content-edit-form-field-input-selector" /><span class="content-edit-form-field-input-selector-name"><?echo GetMessage('ISL_PERSONAL_GENDER_FEMALE')?></span></label></div>
				</td><?
				break;

			case "PERSONAL_PHOTO":
			case "WORK_LOGO":
				?>
				<td class="content-edit-form-field-input" colspan="2">
					<?
					$APPLICATION->IncludeComponent('bitrix:main.file.input', '', array(
						'INPUT_NAME' => $FIELD.'_ID',
						'INPUT_NAME_UNSAVED' => $FIELD.'_ID_UNSAVED',
						'CONTROL_ID' => $FIELD.'_IMAGE_ID',
						'INPUT_VALUE' => $arResult["User"][$FIELD."_FILE"]["ID"],
						'MULTIPLE' => 'N',
						'ALLOW_UPLOAD' => 'I',
						'INPUT_CAPTION' => GetMessage("ISL_ADD_PHOTO"),
						'SHOW_AVATAR_EDITOR' => 'Y'
					));
					?>
				</td>
					<?
				break;

			case 'PERSONAL_BIRTHDAY':

				$birthday = ParseDateTime($value);
				$day = is_array($birthday) ? intval($birthday["DD"]) : "";
				$month = is_array($birthday) ? intval($birthday["MM"]) : "";
				$year = is_array($birthday) ? intval($birthday["YYYY"]) : "";
				?><td class="content-edit-form-field-input" colspan="2">
					<?
				$daySelect = '<select class="content-edit-form-field-input-select" id="PERSONAL_BIRTHDAY_DAY" name="PERSONAL_BIRTHDAY_DAY" onchange="onPersonalBirthdayChange(event)" onblur="onPersonalBirthdayChange(event)">';
				$daySelect .= '<option value="">'.GetMessage("ISL_BIRTHDAY_DAY").'</option>';
				for ($i = 1; $i <= 31; $i++)
					$daySelect .= '<option'.($day == $i ? ' selected="selected"' : '').' value="'.$i.'">'.$i.'</option>';
				$daySelect .= '</select>';

				if (LANGUAGE_ID != "en")
					echo $daySelect;
				?>
				<select class="content-edit-form-field-input-select" id="PERSONAL_BIRTHDAY_MONTH" name="PERSONAL_BIRTHDAY_MONTH" onchange="onPersonalBirthdayChange(event)" onblur="onPersonalBirthdayChange(event)">
					<option value=""><?=GetMessage("ISL_BIRTHDAY_MONTH")?></option>
					<?for ($i = 0; $i <= 11 ; $i++):?>
					<option<?if ($month == ($i + 1)):?> selected="selected"<?endif?> value="<?=$i?>"><?=GetMessage("MONTH_".($i + 1))?></option>
					<?endfor?>
				</select>
					<? if (LANGUAGE_ID == "en")
					echo $daySelect;
				?>
				<input type="text" class="content-edit-form-field-input-text content-edit-form-field-input-bd" value="<?=($year > 0 ? $year : GetMessage("ISL_BIRTHDAY_YEAR") )?>" onclick="this.setAttribute('data-focus', 'true'); if (this.value == '<?=GetMessage("ISL_BIRTHDAY_YEAR")?>') { this.value = '';}" maxlength="4" id="PERSONAL_BIRTHDAY_YEAR" name="PERSONAL_BIRTHDAY_YEAR" onkeyup="onPersonalBirthdayChange(event)" onblur="this.setAttribute('data-focus', 'false'); if (!BX.type.isNumber(parseInt(this.value))) this.value='<?=GetMessage("ISL_BIRTHDAY_YEAR")?>'; onPersonalBirthdayChange(event)" />
				<input type="hidden" id="PERSONAL_BIRTHDAY_VALUE" name="PERSONAL_BIRTHDAY" value="<?=$value?>" />

				<script type="text/javascript">
					function onPersonalBirthdayChange(event)
					{
						var daySelect = BX("PERSONAL_BIRTHDAY_DAY", true);
						var monthSelect = BX("PERSONAL_BIRTHDAY_MONTH", true);
						var yearTextbox = BX("PERSONAL_BIRTHDAY_YEAR", true);
						var birthdayHidden = BX("PERSONAL_BIRTHDAY_VALUE", true)

						var day = daySelect.selectedIndex != -1 ? parseInt(daySelect.options[daySelect.selectedIndex].value) : "";
						var month = monthSelect.selectedIndex != -1 ? parseInt(monthSelect.options[monthSelect.selectedIndex].value) : "";
						var year = parseInt(yearTextbox.value);

						var date = null;

						if (BX.type.isNumber(day) && BX.type.isNumber(month) && BX.type.isNumber(year) && (date = new Date(Date.UTC(year, month, day))))
						{
							birthdayHidden.value = BX.message("FORMAT_DATE")
									.replace(/YYYY/ig, date.getUTCFullYear())
									.replace(/MM/ig, BX.util.str_pad_left((date.getUTCMonth()+1).toString(), 2, "0"))
									.replace(/DD/ig, BX.util.str_pad_left(date.getUTCDate().toString(), 2, "0"))
									.replace(/HH/ig, BX.util.str_pad_left(date.getUTCHours().toString(), 2, "0"))
									.replace(/MI/ig, BX.util.str_pad_left(date.getUTCMinutes().toString(), 2, "0"))
									.replace(/SS/ig, BX.util.str_pad_left(date.getUTCSeconds().toString(), 2, "0"));

							yearTextbox.style.borderColor = "";
						}
						else
						{
							birthdayHidden.value = "";
							var eventType = event.type;
							setTimeout(function() {
								if ( eventType == "blur" && BX.type.isNumber(day) && BX.type.isNumber(month)  && !BX.type.isNumber(year) &&  yearTextbox.getAttribute("data-focus") != "true")
									yearTextbox.style.borderColor = "red";
								else
									yearTextbox.style.borderColor = "";
							}, 500);
						}
					}

				</script>
				</td>
			<?
				break;
			case 'GROUP_ID':
				$readonly = ($arResult['IsMyProfile'] ? 'readonly' : '');
				$onClick = ($arResult['IsMyProfile'] ? 'onclick="return false"' : '');
				$checked = ($arResult['User']['IS_ADMIN'] ? 'checked' : '');
				$disabled = (
					$arResult['User']['ACTIVE'] !== "Y"
					|| (
						$arResult['IS_BITRIX24']
						&& $arResult['ADMIN_RIGHTS_RESTRICTED']
						&& (
							$arResult['User']['IS_INVITED']
							|| in_array($arResult['User']['EXTERNAL_AUTH_ID'], \Bitrix\Main\UserTable::getExternalUserTypes())
							|| empty($arResult['User']['LAST_LOGIN'])
						)
					)
						? 'disabled'
						: ''
				);
				?>
				<td class="content-edit-form-field-input" colspan="2">
				<?
				if (!$arResult["User"]["IS_EXTRANET"]):
					?>
					<div class="content-edit-form-field-input-sub">
						<label style="display: inline-block;">
							<input
							 type="checkbox"
							 class="content-edit-form-field-input-selector"
							 id="group_admin"
							 name="IS_ADMIN"
							 <?= $readonly ?>
							 <?= $onClick ?>
							 value="Y"
							 <?= $checked ?>
							 <?= $disabled ?>
							/>

							<span class="content-edit-form-field-input-selector-name"><?=GetMessage("ISL_GROUP_ADMIN")?></span>

							<?if (
								$arResult["IS_BITRIX24"]
								&& $arResult["ADMIN_RIGHTS_RESTRICTED"]
								&& !$arResult["User"]["IS_INVITED"]
								&& empty($arResult['User']["LAST_LOGIN"])
							):?>
								<span id="group_admin_hint" data-text="<?=GetMessage("SOCNET_MOVE_RIGHTS_AUTH_HINT")?>" class="user-profile-edit-help">?</span>

								<script>
									BX.ready(function ()
									{
										BX.Socialnetwork.User.ProfileEdit.init();
									})
								</script>
						<?endif?>

						<?if (
							$arResult["IS_BITRIX24"]
							&& $arResult["ADMIN_RIGHTS_RESTRICTED"]
						):
						?>
							<div style="display: none">
								<div id="adminRestrContent" >
									<?if ($arResult["IS_COMPANY_TARIFF"]):?>
										<div style="padding-bottom: 20px;"><?=GetMessage("SOCNET_RIGHTS_RESTR_COMPANY_TEXT")?></div>
									<?else:?>
										<div style='font-size: 20px; padding-bottom: 20px;'><?=GetMessage("SOCNET_RIGHTS_RESTR_TEXT1")?></div>
										<div style='padding-bottom: 20px;'><?=GetMessage("SOCNET_RIGHTS_RESTR_TEXT2")?></div>
										<table width='100%'>
											<tr align='center' style='font-weight: bold'>
												<td><?=GetMessage("SOCNET_TARIFF_PROJECT")?></td>
												<td><?=GetMessage("SOCNET_TARIFF_TF")?></td>
												<td><?=GetMessage("SOCNET_TARIFF_TEAM")?></td>
												<td><?=GetMessage("SOCNET_TARIFF_COMPANY")?></td>
											</tr>
											<tr align='center'>
												<td>1</td>
												<td>2</td>
												<td>5</td>
												<td><?=GetMessage("SOCNET_UNLIM")?></td>
											</tr>
										</table>
										<br>
										<div>
											<a href='javascript:void' onclick='BX.Helper.show("redirect=detail&code=5869717");'><?=GetMessage("SOCNET_RIGHTS_RESTR_MORE")?></a>
										</div>
									<?endif?>
								</div>
							</div>
						</label>
						<span class="tariff-lock" style="padding-left: 15px" onclick="B24.licenseInfoPopup.show('adminQuantityRestriction', '<?=GetMessageJS("SOCNET_RIGHTS_RESTR_TITLE")?>', BX('adminRestrContent'))"></span>

						<script>
							BX.bind(BX("group_admin"), "click", function (event)
							{
								if (BX("group_admin").checked)
								{
									event.preventDefault();
									BX.PopupWindowManager.create("menu-custom-preset-delete-popup", null, {
										closeIcon: true,
										contentColor : "white",
										contentNoPaddings : true,
										content: "<div style='padding: 10px;'>" + BX.message("SOCNET_MOVE_RIGHTS_CONFIRM") + "</div>",
										titleBar: BX.message("SOCNET_MOVE_RIGHTS_CONFIRM_TITLE"),
										buttons: [
											(button = new BX.PopupWindowButton({
												text: "OK",
												className: "popup-window-button-create",
												events: {
													click: function ()
													{
														BX("group_admin").checked = BX("group_admin").checked ? "" : "checked";
														this.popupWindow.close();
													}
												}
											})),
											new BX.PopupWindowButton({
												text: BX.message('SOCNET_BUTTON_CANCEL'),
												className: "popup-window-button-link popup-window-button-link-cancel",
												events: {
													click: function ()
													{
														this.popupWindow.close();
													}
												}
											})
										]
									}).show();
								}
								else
								{
									BX("group_admin").checked = "";
								}
							})
						</script>
						<?endif?>
					</div>
				<?endif;
				?></td><?
				break;
			case "UF_SKILLS":
			case "UF_INTERESTS":
				?><td class="content-edit-form-field-textarea" colspan="2"><textarea name="<?echo $FIELD?>" class="content-edit-form-field-input-textarea"><?=$value?></textarea></td><?
				break;
			case "UF_DEPARTMENT":?>
			<td class="content-edit-form-field-input" colspan="2">
				<select name="UF_DEPARTMENT[]" size="5" multiple="multiple" >
					<?
					$rsDepartments = CIBlockSection::GetTreeList(array(
						"IBLOCK_ID"=>intval(COption::GetOptionInt('intranet', 'iblock_structure', false)),
					));
					while($arDepartment = $rsDepartments->GetNext()):
						?><option value="<?echo $arDepartment["ID"]?>" <?if(is_array($value) && in_array($arDepartment["ID"], $value)) echo "selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $arDepartment["DEPTH_LEVEL"])?><?echo $arDepartment["NAME"]?></option><?
					endwhile;
					?>
				</select>
			</td>
				<?break;
			case "UF_PHONE_INNER":?>
			<td class="content-edit-form-field-input" colspan="2">
				<input type="text" name="<?echo $FIELD?>" value="<?=$value?>" class="content-edit-form-field-input-text"/>
			</td><?
			break;
			case "UF_TWITTER":
			case "UF_FACEBOOK":
			case "UF_LINKEDIN":
			case "UF_XING":
				?>
				<td class="content-edit-form-field-input" colspan="2">
					<input type="text" name="<?echo $FIELD?>" placeholder="<?=$SocNetProfileExample[$FIELD]?>" value="<?=$arResult["User"][$FIELD]?>" class="content-edit-form-field-input-text"/>
				</td>
					<?
				break;
			default: ?>
			<td class="content-edit-form-field-input" colspan="2"><?
				if (mb_substr($FIELD, 0, 3) == 'UF_' && !in_array($FIELD, array("UF_SKYPE", "UF_WEB_SITES"))):
					$APPLICATION->IncludeComponent(
						'bitrix:system.field.edit',
						$arResult['USER_PROPERTY_ALL'][$FIELD]['USER_TYPE_ID'],
						array(
							'arUserField' => $arResult['USER_PROPERTY_ALL'][$FIELD],
							'form_name' => 'user_profile_edit',
						),
						null,
						array('HIDE_ICONS' => 'Y')
					);
				else:
					?><input type="text" name="<?echo $FIELD?>" value="<?=$value?>" class="content-edit-form-field-input-text"/><?
				endif;
				?></td><?
		}
		?>
	</tr>
		<?if ($arResult["IS_BITRIX24"] && $FIELD == "PERSONAL_PHOTO" && in_array('UF_PHONE_INNER', $arFields['MAIN']) && !($arResult['User']["UF_PHONE_INNER"] && $arResult['User']["PERSONAL_MOBILE"] &&  $arResult['User']["WORK_PHONE"])): // for phones link?>
	<tr>
		<td class="content-edit-form-field-name"></td>
		<td class="content-edit-form-event-link" colspan="2"><a href="#addPhone" onclick="ShowAddPnone(this)" class="content-edit-form-event-link-tag"><span class="content-edit-form-event-link-icon content-edit-form-icons content-edit-form-icon-socnet"></span><span class="content-edit-form-event-link-name"><?=GetMessage("SOCNET_PHONE_ADD")?></span></a></td>
	</tr>
		<?endif;?>
		<?if ($arResult["IS_BITRIX24"] && $FIELD == "UF_SKYPE" && count($arSocialFields) > 0 && $sonetLinkAddShow):?>
	<tr>
		<td class="content-edit-form-field-name"></td>
		<td class="content-edit-form-event-link" colspan="2"><a href="#addSocnet" onclick="ShowAddSocnet(this)" class="content-edit-form-event-link-tag"><span class="content-edit-form-event-link-icon content-edit-form-icons content-edit-form-icon-socnet"></span><span class="content-edit-form-event-link-name"><?=GetMessage("SOCNET_SOCNET_ADD")?></span></a></td>
	</tr>
		<?endif?>
		<?endforeach;?>
	<?endforeach;?>
<?


if (mb_substr($_REQUEST['backurl'], 0, 1) != "/")
	$_REQUEST['backurl'] = "/".$_REQUEST['backurl'];
?>
<tr>
	<td class="content-edit-form-field-name"></td>
	<td class="content-edit-form-buttons" colspan="2">
		<span class="webform-button webform-button-create" onclick="BX('bx_user_profile_form').elements['submit'].click()"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage("SOCNET_BUTTON_SAVE")?></span><span class="webform-button-right"></span></span>
			<span class="webform-button" onclick="location.href = '<?echo htmlspecialcharsbx(CUtil::addslashes(
				$_REQUEST['backurl']
					? $_REQUEST['backurl']
					: CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arParams["ID"]))
			))?>'"><span class="webform-button-left"></span><span class="webform-button-text"><?=GetMessage("SOCNET_BUTTON_CANCEL")?></span><span class="webform-button-right"></span></span>
	</td>
</tr>
</table>
<input type="submit" name="submit" value="Y" style="opacity:0; filter: alpha(opacity=0);"/>
</form>

<script>
	BX.message({
		"SOCNET_MOVE_RIGHTS_CONFIRM" : "<?=GetMessageJS("SOCNET_MOVE_RIGHTS_CONFIRM")?>",
		"SOCNET_MOVE_RIGHTS_CONFIRM_TITLE" : "<?=GetMessageJS("SOCNET_MOVE_RIGHTS_CONFIRM_TITLE")?>",
		"SOCNET_BUTTON_CANCEL" : "<?=GetMessageJS("SOCNET_BUTTON_CANCEL")?>"
	});

	function confirmDelete(event)
	{
		if (confirm("<?=$arResult["User"]["ACTIVE"] == "Y" ? GetMessage("SOCNET_CONFIRM_FIRE1") : GetMessage("SOCNET_CONFIRM_RECOVER1")?>"))
			return true;
		else
			event.preventDefault();
	}

	function bxUpeToggleHiddenField(tableId, fieldId, show)
	{
		var elements = BX.findChildren(BX(tableId), {attribute: {'data-field-id': ''+fieldId+''}}, true);
		if (elements != null)
		{
			BX.fx.colorAnimate.addRule('animationRule',"#FFF","#faeeb4", "background-color", 50, 1, true);

			for (var i = 0; i < elements.length; i++)
			{
				if (typeof show == 'undefined')
				{
					if (elements[i].style.display == 'none')
					{
						BX.fx.colorAnimate(elements[i], 'animationRule');

						elements[i].style.display = 'table-row';
					}
					else
						elements[i].style.display = 'none';
				}
				else
				{
					if (elements[i].style.display == 'none' && !!show)
					{
						BX.fx.colorAnimate(elements[i], 'animationRule');

						elements[i].style.display = 'table-row';
					}
					else if (!show)
					{
						elements[i].style.display = 'none';
					}
				}
			}
		}
		return false;
	}

	function ShowAddSocnet(bindElement)
	{
		var form = document.forms["user_profile_edit"];
		BX.PopupMenu.show('socnet_add', bindElement, [
		<?if (in_array("UF_TWITTER", $arSocialFields)):?>
			{ text : "<?=GetMessage("ISL_UF_TWITTER")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='UF_TWITTER']").style.display=""; this.popupWindow.close();} },
			<?endif?>
		<?if (in_array("UF_FACEBOOK", $arSocialFields)):?>
			{ text : "<?=GetMessage("ISL_UF_FACEBOOK")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='UF_FACEBOOK']").style.display=""; this.popupWindow.close();}},
			<?endif?>
		<?if (in_array("UF_LINKEDIN", $arSocialFields)):?>
			{ text : "<?=GetMessage("ISL_UF_LINKEDIN")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='UF_LINKEDIN']").style.display=""; this.popupWindow.close();} },
			<?endif?>
		<?if (in_array("UF_XING", $arSocialFields)):?>
			{ text : "<?=GetMessage("ISL_UF_XING")?>", className : "menu-popup-no-icon",  onclick : function() { form.querySelector("[data-role='UF_XING']").style.display=""; this.popupWindow.close();} }
			<?endif?>
		],
				{
					offsetTop:10,
					offsetLeft:30,
					angle : true
				})
	}

	function ShowAddPnone(bindElement)
	{
		var form = document.forms["user_profile_edit"];
		BX.PopupMenu.show('phone_add', bindElement, [
			{ text : "<?=GetMessage("ISL_PERSONAL_MOBILE")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='PERSONAL_MOBILE']").style.display=""; this.popupWindow.close();} },
			{ text : "<?=GetMessage("ISL_WORK_PHONE")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='WORK_PHONE']").style.display=""; this.popupWindow.close();}},
			{ text : "<?=GetMessage("ISL_UF_PHONE_INNER")?>", className : "menu-popup-no-icon", onclick : function() { form.querySelector("[data-role='UF_PHONE_INNER']").style.display=""; this.popupWindow.close();} }
		],
				{
					offsetTop:10,
					offsetLeft:30,
					angle : true
				})
	}
</script>