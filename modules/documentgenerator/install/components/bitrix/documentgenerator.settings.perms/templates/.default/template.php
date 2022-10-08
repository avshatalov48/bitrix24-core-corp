<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'sidepanel',
	'access',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.alerts',
    'ui.info-helper',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');
?>
	<div class="docs-config-wrap" id="docs-perms">
		<div id="perms-alert-container"></div>
		<form>
			<div class="docs-config-block-wrap">
				<table class="table-blue-wrapper">
					<tr>
						<td>
							<table class="table-blue docgen-role-access-table">
								<tr>
									<td class="table-blue-td-title">&nbsp;</td>
									<td class="table-blue-td-title">&nbsp;</td>
									<td class="table-blue-td-title"><?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_ROLE_TITLE')?></td>
									<td class="table-blue-td-title"></td>
								</tr>
								<?foreach($arResult['roleAccessCodes'] as $roleAccessCode)
								{?>
									<tr data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>" data-role-id="<?=htmlspecialcharsbx($roleAccessCode['ROLE_ID'])?>">
										<td class="table-blue-td-name"><?=htmlspecialcharsbx($roleAccessCode['ACCESS_PROVIDER'])?></td>
										<td class="table-blue-td-param"><?=htmlspecialcharsbx($roleAccessCode['ACCESS_NAME'])?></td>
										<td class="table-blue-td-select">
											<select class="docgen-select-role table-blue-select" name="PERMS[<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>]" data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>">
												<? /** @var \Bitrix\DocumentGenerator\Model\Role $role */
												foreach($arResult['roles'] as $role)
												{?>
													<option title="<?=htmlspecialcharsbx($role->getName())?>" value="<?=intval($role->getId())?>" <?=($role->getId() == $roleAccessCode['ROLE_ID'] ? 'selected' : '')?> data-role-id="<?=intval($role->getId());?>">
														<?=htmlspecialcharsbx($role->getName())?>
													</option>
												<?}?>
											</select>
										</td>
										<td class="table-blue-td-action">
											<span class="docgen-delete-access table-blue-delete" data-access-code="<?=htmlspecialcharsbx($roleAccessCode['ACCESS_CODE'])?>"></span>
										</td>
									</tr>
								<?}?>
								<tr class="docgen-access-table-last-row">
									<td colspan="4" class="table-blue-td-link">
										<a class="docgen-add-access table-blue-link" href="javascript:void(0);"><?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_ADD_ACCESS')?></a>
									</td>
								</tr>
							</table>
						</td>
						<td>
							<table class="table-blue docgen-roles-table">
								<tr>
									<td colspan="2" class="table-blue-td-title"><?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_ROLE_LIST')?>:</td>
								</tr>
								<?
								foreach($arResult['roles'] as $role)
								{
									?>
									<tr data-role-id="<?=htmlspecialcharsbx($role->getId())?>">
										<td class="table-blue-td-name">
											<?=htmlspecialcharsbx($role->getName())?>
										</td>
										<td class="table-blue-td-action">
											<a class="docgen-edit-role table-blue-edit" title="<?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_EDIT')?>" href="<?=$this->__component->getEditRoleUrl($role)?>"></a>
											<span class="table-blue-delete docgen-delete-role" title="<?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_DELETE')?>" data-role-id="<?=htmlspecialcharsbx($role->getId())?>"></span>
										</td>
									</tr>
								<?}?>
								<tr class="docgen-roles-table-last-row">
									<td colspan="2" class="docgen-edit-role table-blue-td-link">
										<a href="<?=$this->__component->getEditRoleUrl()?>" class="table-blue-link"><?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_ADD')?></a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?if(!$arResult['isPermissionsFeatureEnabled'])
		{
			?><div class="dcogen-roles-feature"><?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_FEATURE_PANEL');?></div><?
		}
		?>
	</div>
	<script>
		BX.ready(function() {
			<?='BX.message('.\CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)).');'?>
			BX.message({
				DOCGEN_SETTINGS_PERMS_FEATURE_TITLE: '<?=CUtil::JSEscape(Loc::getMessage('DOCGEN_SETTINGS_PERMS_FEATURE_TITLE'));?>',
				DOCGEN_SETTINGS_PERMS_FEATURE_TEXT: '<?=CUtil::JSEscape(Loc::getMessage('DOCGEN_SETTINGS_PERMS_FEATURE_TEXT'));?>',
			});
			BX.DocumentGenerator.Perms.init(BX('docs-perms'), {isPermissionsFeatureEnabled: <?=($arResult['isPermissionsFeatureEnabled'] ? 'true' : 'false');?>});
		})
	</script>
	<script type="text/template" id="docgen-new-access-row">
		<td class="table-blue-td-name">#PROVIDER#</td>
		<td class="table-blue-td-param">#NAME#</td>
		<td class="table-blue-td-select">
			<select class="docgen-select-role table-blue-select" name="PERMS[#ACCESS_CODE#]" data-access-code="#ACCESS_CODE#">
			</select>
		</td>
		<td class="table-blue-td-action">
			<span class="docgen-delete-access table-blue-delete" data-access-code="#ACCESS_CODE#"></span>
		</td>
	</script>
	<script type="text/template" id="docgen-new-role-row">
		<td class="table-blue-td-name">
			#NAME#
		</td>
		<td class="table-blue-td-action">
			<a class="docgen-edit-role table-blue-edit" title="<?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_EDIT')?>" href="#EDIT_URL#"></a>
			<span class="table-blue-delete docgen-delete-role" title="<?=Loc::getMessage('DOCGEN_SETTINGS_PERMS_DELETE')?>" data-role-id="#ID#"></span>
		</td>
	</script>
<?

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'BX.DocumentGenerator.Perms.save()',
		],
		[
			'TYPE' => 'close',
			'LINK' => '',
		]
	]
]);