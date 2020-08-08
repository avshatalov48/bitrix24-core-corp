<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Location\Service\FormatService;

$module_id = 'location';
$moduleAccess = $APPLICATION::GetGroupRight($module_id);

if($moduleAccess >= 'W' && Loader::includeModule($module_id)):

	/**
	 * @global CUser $USER
	 * @global CMain $APPLICATION
	 **/

	IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
	IncludeModuleLangFile(__FILE__);

	$aTabs = array(
		array('DIV' => 'edit1', 'TAB' => Loc::getMessage('LOCATION_OPT_TAB_OPTIONS'), 'ICON' => "", 'TITLE' => Loc::getMessage('LOCATION_OPT_TAB_OPTIONS')),
		array('DIV' => 'edit2', 'TAB' => Loc::getMessage('MAIN_TAB_RIGHTS'), 'ICON' => "", 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_RIGHTS'))
	);

	$tabControl = new CAdminTabControl('tabControl', $aTabs);

	if($_SERVER['REQUEST_METHOD'] === 'POST' && $_REQUEST['Update'] !== "" && check_bitrix_sessid())
	{

		if(isset($_REQUEST['use_google_api']))
		{
			$useGoogleApi = $_REQUEST['use_google_api'] === 'Y' ? 'Y' : 'N';
			Option::set('location', 'use_google_api', $useGoogleApi);
		}

		if(isset($_REQUEST['google_map_api_key']))
		{
			Option::set('location', 'google_map_api_key', $_REQUEST['google_map_api_key']);
		}

		if(isset($_REQUEST['google_map_api_key_backend']))
		{
			Option::set('location', 'google_map_api_key_backend', $_REQUEST['google_map_api_key_backend']);
		}

		if(isset($_REQUEST['address_format_code']))
		{
			Bitrix\Location\Infrastructure\FormatCode::setCurrent($_REQUEST['address_format_code']);
		}

		ob_start();
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin/group_rights.php');
		ob_end_clean();

		if($_REQUEST['back_url_settings'] <> '')
			LocalRedirect($_REQUEST['back_url_settings']);

		LocalRedirect($APPLICATION->GetCurPage().'?mid='.urlencode($module_id).'&lang='.urlencode(LANGUAGE_ID).'&'.$tabControl->ActiveTabParam());
	}

	$formatCode = Bitrix\Location\Infrastructure\FormatCode::getCurrent();
	$formatList = [];
	$formatDescriptionList = [];
	$formatDescription = '';

	foreach(FormatService::getInstance()->findAll(LANGUAGE_ID) as $format)
	{
		$formatList[$format->getCode()] = $format->getName();
		$formatDescriptionList[$format->getCode()] = $format->getDescription();

		if($format->getCode() === $formatCode)
		{
			$formatDescription = $format->getDescription();
		}
	}

	$useGoogleApi = Option::get('location', 'use_google_api', $location_default_option['use_google_api']);
	$googleApiKey = Option::get('location', 'google_map_api_key', $location_default_option['google_map_api_key']);
	$googleApiKeyBakend = Option::get('location', 'google_map_api_key_backend', $location_default_option['google_map_api_key_backend']);

	$apiKeyDisplayString = $useGoogleApi === 'Y' ? '' : ' style="display:none;"';
	$tabControl->Begin();
	?>
	<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
	<?$tabControl->BeginNextTab();?>
		<tr>
			<td width="40%" valign="top"><?=Loc::getMessage('LOCATION_OPT_USE_GOOGLE')?>:</td>
			<td width="60%">
				<input type="hidden" name="use_google_api" value="N">
				<input type="checkbox" name="use_google_api" value="Y"<?=($useGoogleApi === 'Y' ? ' checked' : '')?> onclick="onUseGoogleApiChange(this.checked);">
			</td>
		</tr>
		<tr id="location-key-input-row-1"<?=$apiKeyDisplayString?>>
			<td width="40%" valign="top"><?=Loc::getMessage('LOCATION_OPT_GOOGLE_API_KEY2')?>:</td>
			<td width="60%">
				<input type="text" name="google_map_api_key" size="40" value="<?=htmlspecialcharsbx($googleApiKey)?>">
			</td>
		</tr>
		<tr id="location-key-input-row-2"<?=$apiKeyDisplayString?>>
			<td width="40%" valign="top"><?=Loc::getMessage('LOCATION_OPT_GOOGLE_API_KEY_BACK')?>:</td>
			<td width="60%">
				<input type="text" name="google_map_api_key_backend" size="40" value="<?=htmlspecialcharsbx($googleApiKeyBakend)?>">
				<?=BeginNote();?>
				<?=GetMessage(
					"LOCATION_OPT_GOOGLE_API_KEY_NOTE",
					[
						"#KEY_LINK#" => '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key">https://developers.google.com/maps/documentation/javascript/get-api-key</a>'
					]
				)?>
				<?=EndNote();?>
			</td>
		</tr>
		<tr>
			<td width="40%" valign="top"><?=Loc::getMessage("LOCATION_OPT_FORMAT")?>:</td>
			<td width="60%">
				<select name="address_format_code" onchange="onLocationOptionFormatChanged(this.value);">
					<?foreach($formatList as $code => $name):?>
						<option
								value="<?=htmlspecialcharsbx($code)?>"
								<?=$formatCode === $code ? ' selected' : ''?>>
									<?=htmlspecialcharsbx($name)?>
						</option>
					<?endforeach;?>
				</select>
				<?=BeginNote();?>
					<div id="location_address_format_description">
						<?=$formatDescription?>
					</div>
				<?=EndNote();?>
			</td>
		</tr>
	<?$tabControl->BeginNextTab();?>
		<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>

	<?$tabControl->Buttons();?>
		<input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
		<?=bitrix_sessid_post();?>
		<?if($_REQUEST["back_url_settings"] <> ''):?>
			<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" onclick="window.location="<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>''>
			<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
		<?endif;?>
	<?$tabControl->End();?>
	</form>

	<script>
		function onLocationOptionFormatChanged(formatCode)
		{
			var formatDescriptionsList = <?=CUtil::PhpToJSObject($formatDescriptionList)?>;
			var note = document.getElementById('location_address_format_description');
			note.innerHTML = formatDescriptionsList[formatCode];
		}

		function onUseGoogleApiChange(checked)
		{
			var display = checked ? '' : 'none';
			BX('location-key-input-row-1').style.display = display;
			BX('location-key-input-row-2').style.display = display;
		}

	</script>
<?endif;?>