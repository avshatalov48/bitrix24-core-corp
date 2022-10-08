<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\DocumentGenerator\UserPermissions;

Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'sidepanel',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.alerts',
	'ui.info-helper',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');

?>
	<div class="docs-config-wrap" id="docs-role">
		<div id="role-alert-container"></div>
		<form>
			<input type="hidden" name="id" value="<?=intval($arResult['role']->getId());?>" />
			<div class="docs-role-block-wrap">
				<div class="docs-role-input-container">
					<label class="docs-role-title"><?=Loc::getMessage('DOCGEN_ROLE_LABEL');?></label>
					<input class="docs-role-input" name="name" value="<?=htmlspecialcharsbx($arResult['role']->getName());?>" />
				</div>
			</div>
			<div class="docs-config-block-wrap">
				<table class="table-blue-wrapper">
					<tr>
						<td>
							<table class="table-blue">
								<tr>
									<th class="table-blue-td-title"><?=GetMessage('DOCGEN_ROLE_ENTITY')?></th>
									<th class="table-blue-td-title"><?=GetMessage('DOCGEN_ROLE_ACTION')?></th>
									<th class="table-blue-td-title"><?=GetMessage('DOCGEN_ROLE_PERMISSION')?></th>
								</tr>
								<?foreach(UserPermissions::getMap() as $entity => $actions)
								{
									$firstAction = true;
									foreach($actions as $action => $availablePermissions)
									{
										?>
										<tr class="<?=($firstAction ? 'tr-first' : '')?>">
											<td class="table-blue-td-name">
												<?=($firstAction ? htmlspecialcharsbx(UserPermissions::getEntityTitles()[$entity]) : '&nbsp;')?>
											</td>
											<td class="table-blue-td-param">
												<?=htmlspecialcharsbx(UserPermissions::getActionTitles()[$action])?>
											</td>
											<td class="table-blue-td-select">
												<select name="permissions[<?=$entity?>][<?=$action?>]" data-entity="<?=$entity;?>" data-action="<?=$action;?>" class="table-blue-select">
													<?foreach ($availablePermissions as $permission):?>
														<option value="<?=$permission?>" <?=($permission === $arResult['role']->getPermissions()[$entity][$action] ? 'selected' : '')?>>
															<?=htmlspecialcharsbx(UserPermissions::getPermissionTitles($entity)[$permission])?>
														</option>
													<?endforeach;?>
												</select>
											</td>

										</tr>
										<?
										$firstAction = false;
									}
								}
								?>
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
			BX.DocumentGenerator.Role.init({isPermissionsFeatureEnabled: <?=($arResult['isPermissionsFeatureEnabled'] ? 'true' : 'false');?>});
		})
	</script>
<?

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ONCLICK' => 'BX.DocumentGenerator.Role.save()',
		],
		[
			'TYPE' => 'close',
			'LINK' => '',
		]
	]
]);