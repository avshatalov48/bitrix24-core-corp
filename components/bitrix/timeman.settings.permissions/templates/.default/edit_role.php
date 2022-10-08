<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\DocumentGenerator\UserPermissions;
use Bitrix\Timeman\Security\UserPermissionsManager;

Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'sidepanel',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.alerts',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');

/** @var UserPermissionsManager $permissionsManager */
$permissionsManager = $arResult['userPermissionsManager'];
/** @var \Bitrix\Timeman\Form\Security\TaskForm $taskForm */
$taskForm = $arResult['taskForm'];
?>
	<div class="tm-config-wrap" id="tm-role">
		<div id="role-alert-container"></div>
		<form data-role="task-form">
			<input type="hidden" name="TaskForm[id]" value="<?= CUtil::JSEscape($taskForm->id); ?>"/>
			<input type="hidden" name="TaskForm[isSystem]" value="<?= CUtil::JSEscape($taskForm->isSystem); ?>"/>
			<div class="tm-role-block-wrap">
				<div class="tm-role-input-container">
					<label class="tm-role-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_ROLE_LABEL')); ?></label>
					<input class="tm-role-input" name="TaskForm[name]"
						<? if ($taskForm->isSystem === 'Y'): ?>
							disabled="disabled"
							title="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_CAN_NOT_EDIT_SYSTEM_TASK')); ?>"
						<? endif; ?>
							value="<?= htmlspecialcharsbx($taskForm->name); ?>"/>
				</div>
			</div>
			<div class="tm-config-block-wrap">
				<table class="table-blue-wrapper">
					<tr>
						<td>
							<table class="table-blue">
								<tr>
									<th class="table-blue-td-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_ROLE_ENTITY')) ?></th>
									<? foreach ($arResult['operationsMap'] as $entity => $actions):
										foreach ($actions as $action => $availablePermissions): ?>
											<th class="table-blue-td-title"><?= htmlspecialcharsbx($permissionsManager->getActionTitle($action)) ?></th>
										<? endforeach; ?>
										<? break; ?>
									<? endforeach; ?>
								</tr>

								<? foreach ($arResult['operationsMap'] as $entity => $actions):

									$firstAction = true;
									?>
									<tr class="">
										<td class="table-blue-td-name">
											<?= ($firstAction ? htmlspecialcharsbx($permissionsManager->getEntityTitle($entity)) : '&nbsp;') ?>
										</td>
										<? foreach ($actions as $action => $availablePermissions): ?>
											<td class="table-blue-td-select">
												<select class="table-blue-select <?=
												$action == 'READ' ? 'tm-operation-select-read' : 'tm-operation-select-update' ?>"
													<? if ($taskForm->isSystem === 'Y'): ?>
														disabled="disabled"
													<? endif; ?>
														name="TaskForm[OperationForm][][name]"
														data-entity="<?= $entity; ?>"
														data-action="<?= $action; ?>">
													<?
													foreach ($availablePermissions as $permission):?>
														<option value="<?= $permission ?>"
															<?= ($taskForm->hasOperation($permission) ? 'selected' : '') ?>
														>
															<?
															$title = Loc::getMessage('TIMEMAN_DEFAULT_OPERATION_TITLE');
															if (Loc::getMessage('OP_NAME_'.mb_strtoupper($permission)))
															{
																$title = Loc::getMessage('OP_NAME_'.mb_strtoupper($permission));
															} ?>
															<?= htmlspecialcharsbx($title) ?>
														</option>
													<? endforeach; ?>
												</select>
											</td>
											<?
											$firstAction = false;
										endforeach; ?>
									</tr>
								<? endforeach; ?>
							</table>
						</td>
					</tr>
					<tr class="">
						<td colspan="8">
							<input name="TaskForm[OperationForm][][name]"
								<?php echo($taskForm->hasOperation(UserPermissionsManager::OP_UPDATE_SETTINGS) ? ' checked ' : '') ?>
								<? if ($taskForm->isSystem === 'Y'): ?>
									disabled="disabled"
								<? endif; ?>
									value="<?= UserPermissionsManager::OP_UPDATE_SETTINGS; ?>"
									id="configEdit"
									type="checkbox"
							>
							<label for="configEdit"><?php echo htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_EDIT_SETTINGS')); ?></label>
						</td>
					</tr>
				</table>
			</div>
		</form>
	</div>
	<script>
		BX.ready(function ()
		{
			<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>
			new BX.Timeman.Component.Settings.Permissions.Role({
				containerSelector: '#tm-role',
				isSystem: <?php echo $taskForm->isSystem === 'Y' ? 'true' : 'false'?>
			});
		})
	</script>
<?

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'BUTTONS' => [
		[
			'TYPE' => 'save',
			'ID' => 'tm-save-task',
		],
		[
			'TYPE' => 'close',
			'LINK' => '',
		],
	],
]);