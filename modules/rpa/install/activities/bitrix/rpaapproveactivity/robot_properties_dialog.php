<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
\Bitrix\Main\UI\Extension::load(['ui.forms', 'ui.hint', 'ui.buttons', 'color_picker', 'rpa.fieldscontroller']);

global $APPLICATION;
use Bitrix\Main\Localization\Loc;
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$name = $map['Name'];
$desc = $map['Description'];
$responsibleType = $map['ResponsibleType'] ?? null;
$responsible = $map['Responsible'];
$executiveResponsible = $map['ExecutiveResponsible'] ?? null;
$skipAbsent = $map['SkipAbsent'] ?? null;
$alterResponsible = $map['AlterResponsible'] ?? null;
$aprType = $map['ApproveType'] ?? null;
$aprVoteTarget = $map['ApproveVoteTarget'] ?? null;
$actions = $map['Actions'];
$fieldsToShow = $map['FieldsToShow'];
$fieldsToSet = $map['FieldsToSet'] ?? null;
?>
<div class="rpa-automation-block" data-section="general">
	<div class="rpa-automation-title">
		<span class="rpa-automation-title-text"><?= Loc::getMessage("RPA_BP_APR_SPD_TASK") ?></span>
	</div>
	<div class="rpa-automation-section">
		<div class="rpa-automation-field">
			<label class="rpa-automation-label" for="title"><?=htmlspecialcharsbx($name['Name'])?></label>
			<div class="ui-ctl ui-ctl-w100">
				<input type="text" class="ui-ctl-element" id="title"
					   name="<?=htmlspecialcharsbx($name['FieldName'])?>"
					   value="<?=htmlspecialcharsbx($dialog->getCurrentValue($name))?>"
				>
			</div>
		</div>
		<div class="rpa-automation-field">
			<label class="rpa-automation-label" for="text"><?=htmlspecialcharsbx($desc['Name'])?></label>
			<div class="ui-ctl ui-ctl-w100">
				<input type="text" class="ui-ctl-element" id="text"
					   name="<?=htmlspecialcharsbx($desc['FieldName'])?>"
					   value="<?=htmlspecialcharsbx($dialog->getCurrentValue($desc))?>"
				>
			</div>
		</div>
	</div>
</div>
<div class="rpa-automation-block" data-section="general" id="rpa-automation-block-hint">
	<div class="rpa-automation-title">
		<span class="rpa-automation-title-text"><?= Loc::getMessage("RPA_BP_APR_SPD_RESPONSIBLE") ?></span>
	</div>
	<div class="rpa-automation-section">
		<?php if ($responsibleType):
			$responsibleTypeValue = $dialog->getCurrentValue($responsibleType);
		?>
			<div class="rpa-automation-control">
				<label class="ui-ctl ui-ctl-radio ui-ctl-xs ui-ctl-wa">
					<input name="<?=htmlspecialcharsbx($responsibleType['FieldName'])?>" value="plain" <?=($responsibleTypeValue !== 'heads')?'checked':''?> type="radio" class="ui-ctl-element">
					<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($responsibleType['Options']['plain'])?></div>
				</label>
			</div>
			<div class="rpa-automation-control">
				<label class="ui-ctl ui-ctl-radio ui-ctl-xs ui-ctl-wa">
					<input name="<?=htmlspecialcharsbx($responsibleType['FieldName'])?>" value="heads" <?=($responsibleTypeValue === 'heads')?'checked':''?> type="radio" class="ui-ctl-element">
					<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($responsibleType['Options']['heads'])?></div>
				</label>
				<span data-hint="<?=htmlspecialcharsbx(GetMessage('RPA_BP_APR_SPD_HELP_HEADS'))?>"></span>
			</div>
			<script>
				BX.ready(function()
				{
					var respTypeSelectors = Array.from(document.querySelectorAll('[name="approve_responsible_type"]'));

					if (!respTypeSelectors)
					{
						return;
					}

					var handler = function(val)
					{
						var containers = Array.from(document.querySelectorAll('[data-role="resp-type-cont"]'));
						containers.forEach(function(cont)
						{
							if (val === cont.getAttribute('data-resp'))
							{
								cont.style.height = cont.children[0].offsetHeight + 'px';
								cont.classList.add('rpa-automation-type-show');
							}
							else
							{
								cont.style.height = 0;
								cont.classList.remove('rpa-automation-type-show');
							}
						});
					};

					var selector = document.querySelector("#id_approve_responsible");
					BX.bind(selector, 'click', function()
					{
						onChangeSelectorHeight();
						setTimeout(function() {
							var selectorPopup = document.getElementById("BXSocNetLogDestination");
							BX.bind(selectorPopup, 'click', onChangeSelectorHeight);
						}, 300);
					});

					function onChangeSelectorHeight()
					{
						var selectorContent = document.querySelector('[data-resp="plain"]');
						var innerSelectorContent = selectorContent.querySelector('.rpa-automation-type-inner');
						selectorContent.style.height = innerSelectorContent.offsetHeight + 'px';
					}

					setTimeout(function() {
						if (document.querySelector('[data-resp="plain"]').offsetHeight > 0) {
							onChangeSelectorHeight();
						}
					}, 210);

					respTypeSelectors.forEach(function(respTypeSelector)
					{
						BX.bind(respTypeSelector, 'change', function()
						{
							if (respTypeSelector.checked)
							{
								handler(respTypeSelector.value);
							}
						});

						if (respTypeSelector.checked)
						{
							handler(respTypeSelector.value);
						}
					});
				});
			</script>
		<?endif;?>

		<div data-role="resp-type-cont" data-resp="plain" class="<?=($responsibleType? 'rpa-automation-type':'')?>">
			<div class="rpa-automation-type-inner">
				<div class="rpa-automation-field">
					<label class="rpa-automation-label" for="title"><?=htmlspecialcharsbx($responsible['Name'])?></label>
					<div class="ui-ctl ui-ctl-w100">
						<?=$dialog->renderFieldControl($responsible)?>
					</div>
				</div>
				<div class="rpa-edit-robot-head">
					<span class="rpa-edit-robot-head-item">
						<span class="rpa-edit-robot-head-item-text" data-role="helper-add-head"></span>
					</span>
				</div>
				<script>
					BX.ready(function()
					{
						var userSelector = BX.Bizproc.UserSelector.getByNode(document.querySelector('[data-role="user-selector"]'));
						var headHelper = document.querySelector('[data-role="helper-add-head"]');

						if (!userSelector || !headHelper || !userSelector.roles.responsible_head)
						{
							return;
						}

						headHelper.textContent = userSelector.roles.responsible_head.name;

						BX.bind(headHelper, 'click', function()
						{
							userSelector.toggleItem(userSelector.roles.responsible_head);
						});
					});
				</script>

				<?php if ($aprType):?>
				<div class="rpa-automation-field rpa-automation-options">
					<input type="hidden" name="<?=htmlspecialcharsbx($aprType['FieldName'])?>" value="<?=htmlspecialcharsbx($dialog->getCurrentValue($aprType))?>" data-role="apr-type-value">
					<div class="rpa-automation-options-item rpa-automation-options-item-anyone" data-role="apr-type-item" data-value="any">
						<span class="rpa-automation-options-subject">
							<span class="rpa-edit-robot-link"><?=htmlspecialcharsbx($aprType['Options']['any'])?></span>
						</span>
					</div>
					<div class="rpa-automation-options-item rpa-automation-options-item-anynumber" data-role="apr-type-item" data-value="vote">
						<span class="rpa-automation-options-subject">
							<span class="rpa-edit-robot-link"><?=htmlspecialcharsbx($aprType['Options']['vote'])?></span>
							<span class="ui-ctl rpa-automation-options-ctl">
								<input type="number" class="ui-ctl-element"
									name="<?=htmlspecialcharsbx($aprVoteTarget['FieldName'])?>" value="<?=htmlspecialcharsbx($dialog->getCurrentValue($aprVoteTarget))?>"
									min="1" max="99"
								>
							</span>
						</span>
					</div>
					<div class="rpa-automation-options-item rpa-automation-options-item-onetime" data-role="apr-type-item" data-value="queue">
						<span class="rpa-automation-options-subject">
							<span class="rpa-edit-robot-link"><?=htmlspecialcharsbx($aprType['Options']['queue'])?></span>
						</span>
					</div>
					<div class="rpa-automation-options-item rpa-automation-options-item-all" data-role="apr-type-item" data-value="all">
						<span class="rpa-automation-options-subject">
							<span class="rpa-edit-robot-link"><?=htmlspecialcharsbx($aprType['Options']['all'])?></span>
						</span>
					</div>
				</div>
				<script>
					BX.ready(function()
					{
						var aprTypeInput = document.querySelector('[data-role="apr-type-value"]');
						var aprTypeItems = Array.from(document.querySelectorAll('[data-role="apr-type-item"]'));

						var select = function(val)
						{
							if (!val)
							{
								val = 'any';
							}
							aprTypeInput.value = val;

							aprTypeItems.forEach(function(element)
							{
								var elementValue = element.getAttribute('data-value');
								var fn = (elementValue === val) ? 'addClass' : 'removeClass';

								BX[fn](element, 'rpa-automation-options-item-selected');
							});
						};

						aprTypeItems.forEach(function(element)
						{
							BX.bind(element, 'click', select.bind(element, element.getAttribute('data-value')));
						});

						select(aprTypeInput.value);
					});
				</script>
				<?endif;?>
			</div>
		</div>
		<div data-role="resp-type-cont" data-resp="heads" class="rpa-automation-type rpa-automation-control-head">
			<?php if ($executiveResponsible):
				$executiveResponsibleValue = $dialog->getCurrentValue($executiveResponsible);
				?>
				<div class="rpa-automation-control rpa-automation-control-assist">
					<label class="ui-ctl ui-ctl-checkbox ui-ctl-xs ui-ctl-wa">
						<input type="checkbox" class="ui-ctl-element" name="<?=htmlspecialcharsbx($executiveResponsible['FieldName'])?>_enable" value="Y" data-role="exec-resp-cb" <?=CBPHelper::isEmptyValue($executiveResponsibleValue)?'':'checked'?>>
						<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($executiveResponsible['Name'])?></div>
					</label>
					<span data-hint="<?=htmlspecialcharsbx(GetMessage('RPA_BP_APR_SPD_HELP_EXECUTIVE'))?>"></span>
					<div class="rpa-automation-field" data-role="exec-resp-block">
						<div class="ui-ctl ui-ctl-w100">
							<?=$dialog->renderFieldControl($executiveResponsible)?>
						</div>
					</div>
				</div>
				<script>
					BX.ready(function()
					{
						var execCheckbox = document.querySelector('[data-role="exec-resp-cb"]');
						var execBlock = document.querySelector('[data-role="exec-resp-block"]');

						if (!execCheckbox || !execBlock)
						{
							return;
						}

						var handler = function(checked)
						{
							var typeNode = document.querySelector('[data-resp="heads"]');
							if(checked)
							{
								execBlock.style.height = execBlock.children[0].offsetHeight + 'px';
								BX.addClass(execBlock, 'rpa-automation-field-show');
								typeNode.style.height = typeNode.children[0].offsetHeight + execBlock.children[0].offsetHeight + 'px';
							}
							else
							{
								execBlock.style.height = '0px';
								BX.removeClass(execBlock, 'rpa-automation-field-show');
								typeNode.style.height = typeNode.children[0].offsetHeight - execBlock.children[0].offsetHeight + 'px';
							}
						};

						var selectorExecutive = document.querySelector("#id_approve_executive_responsible");
						BX.bind(selectorExecutive, 'click', function()
						{
							onChangeExecutiveSelectorHeight();
							setTimeout(function() {
								var selectorExecutivePopup = document.getElementById("BXSocNetLogDestination");

								BX.bind(selectorExecutivePopup, 'click', onChangeExecutiveSelectorHeight);
							}, 200);
						});

						function onChangeExecutiveSelectorHeight()
						{
							var selectorWrap = document.querySelector('.rpa-automation-control-head');
							var innerSelectorWrap = selectorWrap.firstElementChild;
							var selectorContent = document.querySelector('[data-role="exec-resp-block"]');
							var innerSelectorContent = selectorContent.querySelector('.ui-ctl');

							selectorContent.style.height = innerSelectorContent.offsetHeight + 'px';
							setTimeout(function() {
								selectorWrap.style.height = innerSelectorWrap.offsetHeight + 'px';
							}, 200);
						}

						BX.bind(execCheckbox, 'change', function()
						{
							handler(execCheckbox.checked);
						});

						handler(execCheckbox.checked);
					});
				</script>
			<?endif;?>
		</div>

		<!--absent section-->
		<?php if ($skipAbsent):
			$skipAbsentValue = $dialog->getCurrentValue($skipAbsent);
			$alterResponsibleValue = $dialog->getCurrentValue($alterResponsible);
			?>
			<div class="rpa-automation-control">
				<label class="ui-ctl ui-ctl-checkbox ui-ctl-xs ui-ctl-wa">
					<input type="hidden" name="<?=htmlspecialcharsbx($skipAbsent['FieldName'])?>" value="N" class="ui-ctl-element">
					<input type="checkbox" name="<?=htmlspecialcharsbx($skipAbsent['FieldName'])?>" value="Y" <?=($skipAbsentValue === 'Y')?'checked':''?> class="ui-ctl-element" data-role="skip-absent-cb">
					<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($skipAbsent['Name'])?></div>
				</label>
			</div>
			<div class="rpa-automation-control rpa-automation-control-assist" data-role="skip-absent-block">
				<div class="rpa-automation-control-inner">
					<label class="ui-ctl ui-ctl-checkbox ui-ctl-xs ui-ctl-wa">
						<input type="checkbox" class="ui-ctl-element" data-role="alter-resp-cb" name="<?=htmlspecialcharsbx($alterResponsible['FieldName'])?>_enable" value="Y" <?=CBPHelper::isEmptyValue($alterResponsibleValue)?'':'checked'?>>
						<div class="ui-ctl-label-text"><?=htmlspecialcharsbx($alterResponsible['Name'])?></div>
					</label>
					<span data-hint="<?=GetMessage('RPA_BP_APR_SPD_HELP_ABSENCE')?>"></span>
					<div class="rpa-automation-field" data-role="alter-resp-block">
						<div class="ui-ctl ui-ctl-w100">
							<?=$dialog->renderFieldControl($alterResponsible)?>
						</div>
					</div>
				</div>
			</div>
			<script>
				BX.ready(function()
				{
					var skipAbsentCheckbox = document.querySelector('[data-role="skip-absent-cb"]');
					var skipAbsentBlock = document.querySelector('[data-role="skip-absent-block"]');
					var respCheckbox = document.querySelector('[data-role="alter-resp-cb"]');
					var respBlock = document.querySelector('[data-role="alter-resp-block"]');

					if (!skipAbsentCheckbox || !skipAbsentBlock || !respCheckbox || !respBlock)
					{
						return;
					}

					var handler = function(checked)
					{
						if(checked)
						{
							skipAbsentBlock.style.height = skipAbsentBlock.children[0].scrollHeight + 'px';
							BX.addClass(skipAbsentBlock, 'rpa-automation-field-show');
						}
						else
						{
							skipAbsentBlock.style.height = '0px';
							BX.removeClass(skipAbsentBlock, 'rpa-automation-field-show');
						}
					};

					var selectorAlter = document.querySelector("#id_approve_alter_responsible");
					BX.bind(selectorAlter, 'click', function()
					{
						onChangeAlterSelectorHeight();
						setTimeout(function() {
							var selectorAlterPopup = document.getElementById("BXSocNetLogDestination");
							BX.bind(selectorAlterPopup, 'click', onChangeAlterSelectorHeight);
						}, 200);
					});


					function onChangeAlterSelectorHeight()
					{
						var selectorContent = document.querySelector('[data-role="alter-resp-block"]');
						var selectorWrap = document.querySelector('[data-role="skip-absent-block"]');
						var innerSelectorWrap = selectorWrap.querySelector('.rpa-automation-control-inner');
						var innerSelectorContent2 = selectorContent.querySelector('.ui-ctl');
						selectorContent.style.height = innerSelectorContent2.offsetHeight + 'px';
						setTimeout(function() {
							selectorWrap.style.height = innerSelectorWrap.firstElementChild.offsetHeight + innerSelectorWrap.lastElementChild.offsetHeight + 'px';
							}, 200);
					}

					BX.bind(skipAbsentCheckbox, 'change', function()
					{
						handler(skipAbsentCheckbox.checked);
					});

					handler(skipAbsentCheckbox.checked);

					var respHandler = function(checked)
					{
						var height = respBlock.children[0].offsetHeight;
						if (checked)
						{
							respBlock.style.height = height + 'px';
							skipAbsentBlock.style.height =  skipAbsentBlock.children[0].scrollHeight + height + 10 + 'px';
							BX.addClass(respBlock, 'rpa-automation-field-show');
						}
						else {
							respBlock.style.height = '0px';
							skipAbsentBlock.style.height = skipAbsentBlock.children[0].offsetHeight - height - 10 + 'px';
							BX.removeClass(respBlock, 'rpa-automation-field-show');
						}
					};

					BX.bind(respCheckbox, 'change', function()
					{
						respHandler(respCheckbox.checked);
					});

					respHandler(respCheckbox.checked);
				});
			</script>
		<?php endif;?>
	</div>
</div>
<div class="rpa-automation-block" data-section="general">
	<div class="rpa-automation-title">
		<span class="rpa-automation-title-text"><?= htmlspecialcharsbx($actions['Name']) ?></span>
	</div>
	<div class="rpa-automation-section rpa-automation-section-btn">
		<div class="rpa-edit-robot-btn-header">
			<div class="rpa-edit-robot-btn-header-item"><?= Loc::getMessage("RPA_BP_APR_SPD_BTN_NAME") ?></div>
			<div class="rpa-edit-robot-btn-header-item"><?= Loc::getMessage("RPA_BP_APR_SPD_BTN_COLOR") ?></div>
			<div class="rpa-edit-robot-btn-header-item"><?= Loc::getMessage("RPA_BP_APR_SPD_BTN_ACTION") ?></div>
		</div>
		<?php

		$actionsList = $dialog->getCurrentValue($actions);
		$stages = $actions['Options'];
		$stagesJs = [];
		foreach ($stages as $id => $stage)
		{
			$stagesJs[] = ['id' => $id, 'name' => $stage];
		}?>

		<div class="rpa-edit-robot-btn-item-list">
		<?php foreach ($actionsList as $i => $action):
			$actionFieldName = sprintf('%s[%d]', $actions['FieldName'], $i);
			?>
			<div class="rpa-edit-robot-btn-item" data-role="approve-action">
				<input type="hidden" data-role="approve-action-color" name="<?=$actionFieldName?>[color]" value="<?=htmlspecialcharsbx($action['color'])?>">
				<input type="hidden" data-role="approve-action-stage" name="<?=$actionFieldName?>[stageId]" value="<?=htmlspecialcharsbx($action['stageId'])?>">

<!--				<span class="rpa-edit-robot-btn-icon-draggable"></span>-->
				<div class="rpa-edit-robot-btn-block" style="background: #<?=htmlspecialcharsbx($action['color'])?>">
					<div class="rpa-edit-robot-btn-view">
						<span class="rpa-edit-robot-btn-view-text"><?=htmlspecialcharsbx($action['label'])?></span>
						<span class="rpa-edit-robot-btn-edit-icon" data-role="edit-icon-btn"></span>
					</div>
					<div class="rpa-edit-robot-btn-edit">
						<input class="rpa-edit-robot-btn-edit-input" data-role="edit-btn-input" type="text" name="<?=$actionFieldName?>[label]" value="<?=htmlspecialcharsbx($action['label'])?>">
					</div>
				</div>
				<span class="rpa-edit-robot-btn-color">
				<span class="rpa-edit-robot-btn-color-item" style="background: #<?=htmlspecialcharsbx($action['color'])?>"></span>
				<span class="rpa-edit-robot-btn-edit-icon" data-role="edit-icon-color"></span>
			</span>
				<span class="rpa-edit-robot-link" data-role="approve-action-stage-selector"><?=htmlspecialcharsbx($stages[$action['stageId']] ?? '?')?></span>
<!--				<span class="rpa-edit-robot-btn-delete"></span>-->
			</div>
		<?php endforeach;?>
		</div>
		<?php /*
		<span style="opacity: .3" class="rpa-edit-robot-btn-add"><?= Loc::getMessage("RPA_BP_APR_SPD_BTN_ADD_BTN") ?></span>
 		*/?>
	</div>
</div>
<div class="rpa-automation-block rpa-automation-block-hidden" data-section="<?= htmlspecialcharsbx($fieldsToShow['FieldName']) ?>">
	<div class="rpa-automation-title">
		<span class="rpa-automation-title-text"><?= htmlspecialcharsbx($fieldsToShow['Name']) ?></span>
	</div>
	<div class="rpa-automation-section">
		<div id="rpa-automation-fields-view"></div>
		<script>
			BX.ready(function()
			{
				var fieldsToShowSettings = <?=\Bitrix\Main\Web\Json::encode($fieldsToShow['Settings'])?>;
				var currentValues = <?=\Bitrix\Main\Web\Json::encode(
						array_fill_keys(
								$dialog->getCurrentValue($fieldsToShow), true
						)
				)?>;
				var factory = null;
				if(fieldsToShowSettings.isCreationEnabled)
				{
					factory = new BX.UI.UserFieldFactory.Factory(fieldsToShowSettings.entityId, {
						moduleId: 'rpa',
					});
				}

				var fieldsController = new BX.Rpa.FieldsController({
					fields: fieldsToShowSettings.fields,
					factory: factory,
					settings: {
						inputName: '<?=CUtil::JSEscape($fieldsToShow['FieldName'])?>',
						values: currentValues,
					},
					typeId: fieldsToShowSettings.typeId,
				});
				document.getElementById('rpa-automation-fields-view').appendChild(fieldsController.render());
			});
		</script>
	</div>
</div>
<?php if ($fieldsToSet):?>
<div class="rpa-automation-block rpa-automation-block-hidden" data-section="<?= htmlspecialcharsbx($fieldsToSet['FieldName']) ?>">
	<div class="rpa-automation-title">
		<span class="rpa-automation-title-text"><?= htmlspecialcharsbx($fieldsToSet['Name']) ?></span>
	</div>
	<div class="rpa-automation-section">
		<div id="rpa-automation-fields-set"></div>
		<script>
			BX.ready(function()
			{
				var settings = <?=\Bitrix\Main\Web\Json::encode($fieldsToSet['Settings'])?>;
				var currentValues = <?=\Bitrix\Main\Web\Json::encode(
					array_fill_keys(
						$dialog->getCurrentValue($fieldsToSet), true
					)
				)?>;
				var factory = null;
				if(settings.isCreationEnabled)
				{
					factory = new BX.UI.UserFieldFactory.Factory(settings.entityId, {
						moduleId: 'rpa',
					});
				}

				var fieldsController = new BX.Rpa.FieldsController({
					fields: settings.fields,
					factory: factory,
					settings: {
						inputName: '<?=CUtil::JSEscape($fieldsToSet['FieldName'])?>',
						values: currentValues,
					},
					typeId: settings.typeId,
				});
				document.getElementById('rpa-automation-fields-set').appendChild(fieldsController.render());
				BX.Event.EventEmitter.subscribe(fieldsController, 'onFieldSave', function(event)
				{
					var userField = event.data.userField;
					if(userField)
					{
						var switcher = BX.UI.Switcher.getById(BX.Rpa.FieldsController.getSwitcherId('<?=CUtil::JSEscape($fieldsToSet['FieldName'])?>', userField.getName()));
						if(switcher)
						{
							switcher.check(true);
						}
					}
				});
			});
		</script>
	</div>
</div>
<?php endif;?>
<script>
	BX.ready(function() {
		BX.UI.Hint.init(BX('rpa-automation-block-hint'));

		var stages = <?=\Bitrix\Main\Web\Json::encode($stagesJs)?>;
		var stageSelectors = Array.from(document.querySelectorAll('[data-role="approve-action-stage-selector"]'));
		var stageSwitcherClickHandler = function(index)
		{
			var menuItems = [];
			var element = this;

			stages.forEach(function(stage)
			{
				menuItems.push({
					text: BX.util.htmlspecialchars(stage.name),
					stage: stage,
					onclick: function(e, item)
					{
						var container = element.closest('[data-role="approve-action"]');
						var inp = container.querySelector('[data-role="approve-action-stage"]');

						inp.value = item.stage.id;
						element.textContent = item.stage.name;

						this.popupWindow.close();
					}
				});
			});

			BX.PopupMenu.show(
				'rpa-approve-stage-switcher' + index,
				this,
				menuItems,
				{
					events: {
						onPopupClose: function(popup)
						{
							popup.destroy();
						}
					},
					overlay: { backgroundColor: 'transparent' },
				}
			);
		};

		stageSelectors.forEach(function(el, i)
		{
			BX.bind(el, 'click', stageSwitcherClickHandler.bind(el, i));
		});
	});

	BX.RpaApproveActivity = {
		editBtnIcons: document.querySelectorAll('[data-role="edit-icon-btn"]'),
		editBtnFields: document.querySelectorAll('[data-role="edit-btn-input"]'),
		editColorIcons: document.querySelectorAll('[data-role="edit-icon-color"]'),
		colorItem: null,
		parentElement: null,
		input: null,
		btn: null,
	};

	BX.RpaApproveActivity.init = function() {

		[].forEach.call(
			this.editBtnIcons,
			function (item)
			{
				item.addEventListener('click', this.switchToEditMode.bind(this));
			},
			this
		);

		[].forEach.call(
			this.editBtnFields,
			function (item)
			{
				item.addEventListener('keydown', this.handleInputKeyDown.bind(this));
				item.addEventListener('blur', this.hideEditMode.bind(this));
			},
			this
		);

		this.picker = new BX.ColorPicker({
			bindElement: null,
			defaultColor: "#000",
			popupOptions: {
				offsetTop: 10,
				offsetLeft: 10
			}
		});

		[].forEach.call(
			this.editColorIcons,
			function (item)
			{
				item.addEventListener('click', this.editColorBtn.bind(this));
			},
			this
		);
	};

	BX.RpaApproveActivity.switchToEditMode = function(e) {

		this.parentElement = e.target.closest('.rpa-edit-robot-btn-block');
		this.input = this.parentElement.querySelector('.rpa-edit-robot-btn-edit-input');
		this.btn = this.parentElement.querySelector('.rpa-edit-robot-btn-view-text');

		this.parentElement.classList.add("rpa-edit-robot-btn-block-edit");
		this.input.value = this.btn.textContent;
		this.input.focus();
	};

	BX.RpaApproveActivity.handleInputKeyDown = function() {

		if (event.keyCode === 13)
		{
			this.hideEditMode();
		}
	};

	BX.RpaApproveActivity.hideEditMode = function() {
		if (!this.btn)
		{
			return;
		}

		this.btn.textContent = this.input.value;
		this.parentElement.classList.remove("rpa-edit-robot-btn-block-edit");
	};

	BX.RpaApproveActivity.editColorBtn = function(e) {
		this.colorItem = e.target.previousElementSibling;
		var currentBtn = e.target.closest('.rpa-edit-robot-btn-item').querySelector('.rpa-edit-robot-btn-block');
		var currentInp = e.target.closest('.rpa-edit-robot-btn-item').querySelector('[data-role="approve-action-color"]');

		this.picker.open({
			bindElement: e.target,
			onColorSelected: this.onColorSelected.bind(this, currentBtn, currentInp)
		});
	};

	BX.RpaApproveActivity.onColorSelected = function(currentBtn, currentInp, color) {
		this.colorItem.style.background = color;
		currentBtn.style.background = color;
		currentInp.value = color.replace('#', '');
		currentBtn.style.color = BX.Rpa.Manager.calculateTextColor(color);
	};

	[].forEach.call(
		document.querySelectorAll('.rpa-edit-robot-btn-block'),
		function (btn)
		{
			btn.style.color = BX.Rpa.Manager.calculateTextColor(btn.style.backgroundColor);
		}
	);

	BX.RpaApproveActivity.init();
</script>