<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.forms");
\Bitrix\Main\UI\Extension::load("ui.common");

/**
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CUser $USER
 */

CJSCore::Init(array("popup", "ajax"));

ShowMessage($arResult["MESSAGE"] ?? '');

if($USER->IsAuthorized()):?>
<div class="intranet-user-profile-security">
	<?= Loc::getMessage('DAV_CARDDAV_SETTINGS_HELP', array("#SERVER#" => $_SERVER["SERVER_NAME"])) ?>
	<form action="<?=($arParams['ACTION_URI'] ?? '')?>" method="post" id="synchronize_settings_form" name="synchronize-settings-form" >
		<?echo bitrix_sessid_post()?>
        <br>
		<table class="content-edit-form">
            <tr>
                <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_DEFAULT_COLLECTION_TO_SYNC')?></td>
                <td class="content-edit-form-field-input">

					<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						<select name="DAV_SYNC_SETTINGS[COMMON][DEFAULT_COLLECTION_TO_SYNC]" class="ui-ctl-element">
							<?php foreach ($arResult['COMMON']['DEFAULT_COLLECTION_TO_SYNC']['VARIANTS'] as $key => $title):?>
								<option <?= ($arResult['COMMON']['DEFAULT_COLLECTION_TO_SYNC']['VALUE'] == $key) ? 'selected' : '';?> value="<?=$key?>"><?=$title?></option>
							<?php endforeach;?>
						</select>
					</div>
                </td>
                <td class="content-edit-form-field-error">&nbsp;</td>
            </tr>

			<tr>
				<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
					<div class="content-edit-form-header-wrap"><?=Loc::getMessage('DAV_ACCOUNTS_SECTION')?></div>
				</td>
			</tr>

			<tr>
				<td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_ENABLE')?></td>
				<td class="content-edit-form-field-input">
					<input type="hidden" name="DAV_SYNC_SETTINGS[ACCOUNTS][ENABLED]" value="N"/>
					<input type="checkbox" name="DAV_SYNC_SETTINGS[ACCOUNTS][ENABLED]" <?=$arResult['ACCOUNTS']['ENABLED'] ? 'checked' : ''?> value="Y" class="content-edit-form-field-input-checkbox"/>
				</td>
				<td class="content-edit-form-field-error">&nbsp;</td>
			</tr>


			<tr>
				<td class="content-edit-form-field-name content-edit-form-field-name-top"><?=Loc::getMessage('DAV_ACCOUNTS_EXPORT_DEPARTMENT')?></td>
				<td class="content-edit-form-field-input" >
					<div class="ui-ctl ui-ctl-multiple-select">
						<select name="DAV_SYNC_SETTINGS[ACCOUNTS][UF_DEPARTMENT][]" size="5" multiple="multiple" class="ui-ctl-element">
							<?
							$rsDepartments = CIBlockSection::GetTreeList(array(
								"IBLOCK_ID"=> COption::GetOptionInt('intranet', 'iblock_structure', false),
							));
							while($arDepartment = $rsDepartments->GetNext()):
								?><option value="<?echo $arDepartment["ID"]?>" <?if (is_array($arResult['ACCOUNTS']['UF_DEPARTMENT']) && in_array($arDepartment["ID"], $arResult['ACCOUNTS']['UF_DEPARTMENT'])) echo "selected"?>><?echo str_repeat("&nbsp;.&nbsp;", $arDepartment["DEPTH_LEVEL"])?><?echo $arDepartment["NAME"]?></option><?
							endwhile;
							?>
						</select>
					</div>
				</td>
				<td class="content-edit-form-field-error">&nbsp;</td>
			</tr>

			<?php if(\Bitrix\Main\ModuleManager::isModuleInstalled('extranet')):?>
				<tr>
					<td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
						<div class="content-edit-form-header-wrap"><?=Loc::getMessage('DAV_EXTRANET_ACCOUNTS')?></div>
					</td>
				</tr>

				<tr>
					<td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_ENABLE')?></td>
					<td class="content-edit-form-field-input">
						<input type="hidden" name="DAV_SYNC_SETTINGS[EXTRANET_ACCOUNTS][ENABLED]" value="N"/>
						<input type="checkbox" name="DAV_SYNC_SETTINGS[EXTRANET_ACCOUNTS][ENABLED]" <?=$arResult['EXTRANET_ACCOUNTS']['ENABLED'] ? 'checked' : ''?> value="Y" class="content-edit-form-field-input-checkbox"/>
					</td>
					<td class="content-edit-form-field-error">&nbsp;</td>
				</tr>
			<?php endif;?>

            <?php if(\Bitrix\Main\Loader::includeModule('crm')):?>
                <?php if(CCrmContact::CheckExportPermission()):?>
                    <tr>
                        <td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
                            <div class="content-edit-form-header-wrap"><?=Loc::getMessage('DAV_CONTACTS')?></div>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_ENABLE')?></td>
                        <td class="content-edit-form-field-input">
                            <input type="hidden" name="DAV_SYNC_SETTINGS[CONTACTS][ENABLED]" value="N"/>
                            <input type="checkbox" name="DAV_SYNC_SETTINGS[CONTACTS][ENABLED]" <?=$arResult['CONTACTS']['ENABLED'] ? 'checked' : ''?> value="Y" class="content-edit-form-field-input-checkbox"/>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_MAX_COUNT')?></td>
                        <td class="content-edit-form-field-input">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
                            	<input type="text" name="DAV_SYNC_SETTINGS[CONTACTS][MAX_COUNT]" value="<?=$arResult['CONTACTS']['MAX_COUNT']?>" class="ui-ctl-element"/>
							</div>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_EXPORT_FILTER')?></td>
                        <td class="content-edit-form-field-input">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select name="DAV_SYNC_SETTINGS[CONTACTS][FILTER]" class="ui-ctl-element">
									<?php foreach ($arResult['CONTACTS']['FILTER']['ITEMS'] as $key => $title):?>
										<option <?= ($arResult['CONTACTS']['FILTER']['VALUE'] == $key) ? 'selected' : '';?> value="<?=$key?>"><?=$title?></option>
									<?php endforeach;?>
								</select>
							</div>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>
                <?php endif;?>

                <?php if(CCrmCompany::CheckExportPermission()):?>
                    <tr>
                        <td class="content-edit-form-header content-edit-form-header-first" colspan="3" >
                            <div class="content-edit-form-header-wrap"><?=Loc::getMessage('DAV_COMPANIES')?></div>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_ENABLE')?></td>
                        <td class="content-edit-form-field-input">
                            <input type="hidden" name="DAV_SYNC_SETTINGS[COMPANIES][ENABLED]" value="N"/>
                            <input type="checkbox" name="DAV_SYNC_SETTINGS[COMPANIES][ENABLED]" <?=$arResult['COMPANIES']['ENABLED']? 'checked' : ''?> value="Y"  class="content-edit-form-field-input-checkbox"/>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_MAX_COUNT')?></td>
                        <td class="content-edit-form-field-input">
							<div class="ui-ctl ui-ctl-textbox ui-ctl-block">
                            	<input type="text" name="DAV_SYNC_SETTINGS[COMPANIES][MAX_COUNT]" value="<?=$arResult['COMPANIES']['MAX_COUNT']?>" class="ui-ctl-element"/>
							</div>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>

                    <tr>
                        <td class="content-edit-form-field-name"><?=Loc::getMessage('DAV_EXPORT_FILTER')?></td>
                        <td class="content-edit-form-field-input">
							<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select name="DAV_SYNC_SETTINGS[COMPANIES][FILTER]" class="ui-ctl-element">
									<?php foreach ($arResult['COMPANIES']['FILTER']['ITEMS'] as $key => $title):?>
										<option <?= ($arResult['COMPANIES']['FILTER']['VALUE'] == $key) ? 'selected' : '';?> value="<?=$key?>"><?=$title?></option>
									<?php endforeach;?>
								</select>
							</div>
                        </td>
                        <td class="content-edit-form-field-error">&nbsp;</td>
                    </tr>
                <?php endif;?>
            <?php endif;?>

			<tr>
				<td class="content-edit-form-buttons" colspan="3">
					<span class="ui-btn ui-btn-success" id="davSynchroSaveButton"><?= Loc::getmessage("DAV_BUTTON_SAVE") ?></span>
				</td>
			</tr>
		</table>

		<input type="submit" name="submit" value="Y" style="opacity:0; filter: alpha(opacity=0);"/>
	</form>
</div>

	<script>
		BX.ready(function () {
			new BX.Dav.SynchronizeSettings({
				signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
				componentName: '<?=$this->getComponent()->getName() ?>',
				componentAjaxLoad: '<?=$arParams["COMPONENT_AJAX_LOAD"]?>'
			});
		});
	</script>
<?php endif;?>