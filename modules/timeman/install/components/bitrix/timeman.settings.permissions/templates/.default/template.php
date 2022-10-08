<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'access',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.alerts',
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/table/style.css');
\Bitrix\Main\Page\Asset::getInstance()->addJS('/bitrix/js/timeman/component/basecomponent.js');
?>
<div class="tm-config-wrap" id="tm-permissions">
	<form data-role="access-table-form">
		<div class="tm-config-block-wrap">
			<table class="table-blue-wrapper">
				<tr>
					<td>
						<table class="table-blue" data-role="tm-role-access-table">
							<tr>
								<td class="table-blue-td-title">&nbsp;</td>
								<td class="table-blue-td-title">&nbsp;</td>
								<td class="table-blue-td-title"><?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_ROLE_TITLE')) ?></td>
								<td class="table-blue-td-title"></td>
							</tr>
							<?
							foreach ($arResult['taskAccessCodes'] as $taskAccessCode)
							{
								?>
								<tr data-access-code="<?= htmlspecialcharsbx($taskAccessCode['ACCESS_CODE']) ?>"
										data-task-id="<?= htmlspecialcharsbx($taskAccessCode['TASK_ID']) ?>">
									<td class="table-blue-td-name"><?= htmlspecialcharsbx($taskAccessCode['ACCESS_PROVIDER']) ?></td>
									<td class="table-blue-td-param"><?= htmlspecialcharsbx($taskAccessCode['ACCESS_NAME']) ?></td>
									<td class="table-blue-td-select">
										<select class="tm-select-role table-blue-select"
												name="PERMS[<?= htmlspecialcharsbx($taskAccessCode['ACCESS_CODE']) ?>]"
												data-access-code="<?= htmlspecialcharsbx($taskAccessCode['ACCESS_CODE']) ?>">
											<?php foreach ($arResult['tasks'] as $task): ?>
												<option title="<?= htmlspecialcharsbx($task['NAME']) ?>"
														value="<?= intval($task['ID']) ?>" <?= ($task['ID'] == $taskAccessCode['TASK_ID'] ? 'selected' : '') ?>
														data-task-id="<?= intval($task['ID']); ?>">
													<?= htmlspecialcharsbx($task['NAME']) ?>
												</option>
											<? endforeach; ?>
										</select>
									</td>
									<td class="table-blue-td-action">
										<span class="tm-delete-access table-blue-delete"
												data-access-code="<?= htmlspecialcharsbx($taskAccessCode['ACCESS_CODE']) ?>"></span>
									</td>
								</tr>
								<?
							} ?>
							<tr class="tm-access-table-last-row">
								<td colspan="4" class="table-blue-td-link">
									<a class="table-blue-link" href="javascript:void(0);"
											data-role="add-role-access-mapping"><?=
										htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_ADD_ACCESS')) ?></a>
								</td>
							</tr>
						</table>
					</td>
					<td>
						<table class="table-blue tm-roles-table">
							<tr>
								<td colspan="2" class="table-blue-td-title"><?=
									htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_ROLE_LIST')) ?>:
								</td>
							</tr>
							<?php foreach ($arResult['tasks'] as $task): ?>
								<tr data-task-id="<?= htmlspecialcharsbx($task['ID']) ?>">
									<td class="table-blue-td-name">
										<?= htmlspecialcharsbx($task['NAME']) ?>
									</td>
									<td class="table-blue-td-action">
										<? if ($task['CAN_BE_EDIT'] || $task['SYS'] === 'Y'): ?>
											<a class="tm-edit-task table-blue-edit" title=
											"<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_EDIT')) ?>" href=
											"<?= $this->__component->getEditTaskUrl($task) ?>"></a>
										<? endif; ?>
										<? if ($task['CAN_BE_DELETED']): ?>
											<span class="table-blue-delete tm-delete-role"
												title="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_DELETE')) ?>"
												data-task-id="<?= htmlspecialcharsbx($task['ID']) ?>"></span>
										<? endif; ?>
									</td>
								</tr>
							<? endforeach; ?>
							<tr class="tm-roles-table-last-row">
								<td colspan="2" class="tm-edit-task table-blue-td-link">
									<a href="<?= $this->__component->getEditTaskUrl() ?>" class="table-blue-link"><?=
										htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_ADD')) ?></a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</div>
	</form>
	<div id="ui-button-panel" class="ui-button-panel-wrapper ui-pinner ui-pinner-bottom ">
		<div class="ui-button-panel ">
			<button data-role="tm-save-task-to-access-code-map" name="save" value="Y" class="ui-btn ui-btn-success"><?php
				echo htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_SAVE')); ?></button>
		</div>
	</div>
</div>
<script>
	BX.ready(function ()
	{
		<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) . ');'?>

		new BX.Timeman.Component.Settings.Permissions({
			containerSelector: '#tm-permissions'
		});
	});

</script>
<script type="text/template" id="tm-new-access-row">
	<td class="table-blue-td-name">#PROVIDER#</td>
	<td class="table-blue-td-param">#NAME#</td>
	<td class="table-blue-td-select">
		<select class="tm-select-role table-blue-select" name="PERMS[#ACCESS_CODE#]" data-access-code="#ACCESS_CODE#">
			<?
			foreach ($arResult['tasks'] as $task)
			{
				?>
				<option title="<?= htmlspecialcharsbx($task['NAME']) ?>" value="<?= intval($task['ID']) ?>">
					<?= htmlspecialcharsbx($task['NAME']) ?>
				</option>
				<?
			} ?>
		</select>
	</td>
	<td class="table-blue-td-action">
		<span class="tm-delete-access table-blue-delete" data-access-code="#ACCESS_CODE#"></span>
	</td>
</script>
<script type="text/template" id="tm-new-role-row">
	<td class="table-blue-td-name">
		#NAME#
	</td>
	<td class="table-blue-td-action">
		<a class="tm-edit-task table-blue-edit" title="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_EDIT')) ?>" href="#EDIT_URL#"></a>
		<span class="table-blue-delete tm-delete-role" title="<?= htmlspecialcharsbx(Loc::getMessage('TIMEMAN_SETTINGS_PERMS_DELETE')) ?>" data-task-id="#ID#"></span>
	</td>
</script>