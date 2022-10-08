<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

use Bitrix\Main\Localization\Loc;

$map = $dialog->getMap();
$waitType = $map['WaitType'];
$waitDuration = $map['WaitDuration'];
$waitTarget = $map['WaitTarget'];
$waitDescription = $map['WaitDescription'];
$runtimeData = $dialog->getRuntimeData();
?>
<div class="bizproc-automation-popup-settings bizproc-automation-popup-settings-text">
	<a class="bizproc-automation-popup-settings-link"
		data-role="wait-selector"
		data-text-after="<?=htmlspecialcharsbx(GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_AFTER'))?>"
		data-text-before="<?=htmlspecialcharsbx(GetMessage('CRM_WAIT_ACTIVITY_DESCRIPTION_TYPE_BEFORE'))?>"
	>...</a> <?=GetMessage('CRM_WAIT_ACTIVITY_RPD_OR')?> <span><?=GetMessage('CRM_WAIT_ACTIVITY_RPD_OR_CLIENT_ACTIVITY')?></span>
</div>
<div class="bizproc-automation-popup-settings">
	<?= $dialog->renderFieldControl($waitDescription)?>
</div>
<input type="hidden" data-role="wait-type" name="<?=htmlspecialcharsbx($waitType['FieldName'])?>" value="<?=htmlspecialcharsbx($dialog->getCurrentValue($waitType['FieldName'], $waitType['Default']))?>">
<input type="hidden" data-role="wait-duration" name="<?=htmlspecialcharsbx($waitDuration['FieldName'])?>" value="<?=htmlspecialcharsbx($dialog->getCurrentValue($waitDuration['FieldName'], $waitDuration['Default']))?>">
<input type="hidden" data-role="wait-target" name="<?=htmlspecialcharsbx($waitTarget['FieldName'])?>" value="<?=htmlspecialcharsbx($dialog->getCurrentValue($waitTarget['FieldName'], ''))?>">

<script>

	BX.message({
		CRM_WAIT_ACTIVITY_WEEK_PLURAL_0: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_WEEK_PLURAL_0')) ?>',
		CRM_WAIT_ACTIVITY_WEEK_PLURAL_1: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_WEEK_PLURAL_1')) ?>',
		CRM_WAIT_ACTIVITY_WEEK_PLURAL_2: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_WEEK_PLURAL_2')) ?>',
		CRM_WAIT_ACTIVITY_DAY_PLURAL_0: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_DAY_PLURAL_0')) ?>',
		CRM_WAIT_ACTIVITY_DAY_PLURAL_1: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_DAY_PLURAL_1')) ?>',
		CRM_WAIT_ACTIVITY_DAY_PLURAL_2: '<?= CUtil::JSEscape(Loc::getMessage('CRM_WAIT_ACTIVITY_DAY_PLURAL_2')) ?>',
	});

	BX.ready(function ()
	{
		var dialog = BX.Bizproc.Automation.Designer.getInstance().getRobotSettingsDialog();
		if (!dialog)
		{
			return;
		}

		var targetDateFields = <?=\Bitrix\Main\Web\Json::encode($runtimeData['targetDateFields']);?>;

		var waitSelector = dialog.form.querySelector('[data-role="wait-selector"]');
		var waitTypeInput = dialog.form.querySelector('[data-role="wait-type"]');
		var waitDurationInput = dialog.form.querySelector('[data-role="wait-duration"]');
		var waitTargetInput = dialog.form.querySelector('[data-role="wait-target"]');

		var updateSelectorLabel = function()
		{
			var labelText = waitSelector.getAttribute('data-text-' + waitTypeInput.value);
			if (!labelText)
			{
				return;
			}

			labelText = labelText.replace("#DURATION#", getDurationText(waitDurationInput.value, true));

			if (waitTypeInput.value === 'before')
			{
				labelText = labelText.replace("#TARGET_DATE#", getTargetDateCaption(waitTargetInput.value))
			}

			waitSelector.textContent = labelText;
		};

		var getDurationText = function(duration, enableNumber)
		{
			duration = parseInt(duration);
			var result = '';

			if (enableNumber && (duration % 7) === 0)
			{
				duration = duration / 7;
				result = BX.Loc.getMessagePlural('CRM_WAIT_ACTIVITY_WEEK', duration);
			}
			else
			{
				result = BX.Loc.getMessagePlural('CRM_WAIT_ACTIVITY_DAY', duration);
			}

			if (enableNumber)
			{
				result = duration.toString() + " " + result;
			}
			return result;
		};

		var getTargetDateCaption = function(name)
		{
			for (var i = 0; i < targetDateFields.length; i++)
			{
				var info = targetDateFields[i];
				if (info["name"] === name)
				{
					return info["caption"];
				}
			}

			return name;
		};

		var WaitSelectorController = function()
		{
			this._id = 'crm-wait-activity-selector';
			BX.bind(waitSelector, 'click', this.onSelectorClick.bind(this));

			updateSelectorLabel();
		};

		WaitSelectorController.prototype.onSelectorClick = function(e)
		{
			if (!this._menu || !this._menu.isShown())
			{
				this.openMenu();
			}
			else
			{
				this.closeMenu();
			}
		};
		WaitSelectorController.prototype.openMenu = function()
		{
			if (this._menu && this._menu.isShown())
			{
				return;
			}

			var handler = BX.delegate(this.onMenuItemClick, this);

			var menuItems =
				[
					{text: getDurationText(1, true), duration: 1, onclick: handler},
					{text: getDurationText(2, true), duration: 2, onclick: handler},
					{text: getDurationText(3, true), duration: 3, onclick: handler},
					{text: getDurationText(7, true), duration: 7, onclick: handler},
					{text: getDurationText(14, true), duration: 14, onclick: handler},
					{text: getDurationText(21, true), duration: 21, onclick: handler}
				];

			var customMenu = {id: "custom", text: this.getMessage("custom"), items: []};
			customMenu["items"].push({id: "after", text: this.getMessage("afterDays"), onclick: handler});
			if (targetDateFields.length > 0)
			{
				customMenu["items"].push({id: "before", text: this.getMessage("beforeDate"), onclick: handler});
			}
			menuItems.push(customMenu);

			BX.PopupMenu.show(
				this._id,
				waitSelector,
				menuItems,
				{
					offsetTop: 0,
					offsetLeft: 36,
					angle: {position: "top", offset: 0},
					overlay: { backgroundColor: 'transparent' },
					events:
						{
							onPopupClose: BX.delegate(this.onMenuClose, this),
							onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
						}
				}
			);

			this._menu = BX.PopupMenu.currentItem;
		};
		WaitSelectorController.prototype.closeMenu = function()
		{
			if (this._menu)
			{
				this._menu.close();
			}
		};
		WaitSelectorController.prototype.onMenuItemClick = function(e, item)
		{
			this.closeMenu();

			if (item.id === "after" || item.id === "before")
			{
				this.openConfigDialog(item.id);
				return;
			}

			waitTypeInput.value = 'after';
			waitDurationInput.value = item.duration;

			updateSelectorLabel();
		};
		WaitSelectorController.prototype.onMenuClose = function()
		{
			if (this._menu && this._menu.popupWindow)
			{
				this._menu.popupWindow.destroy();
			}
		};
		WaitSelectorController.prototype.onMenuDestroy = function()
		{
			this._menu = null;

			if (typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		};
		WaitSelectorController.messages =
		{
			custom: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_CUSTOM')?>',
			afterDays: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_AFTER_CUSTOM_DAYS')?>',
			beforeDate: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_BEFORE_CUSTOM_DATE')?>',
			select: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_CONFIG_CHOOSE')?>',
			prefixTypeAfter: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_CONFIG_PREFIX_TYPE_AFTER')?>',
			prefixTypeBefore: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_CONFIG_PREFIX_TYPE_BEFORE')?>',
			targetPrefixTypeBefore: '<?=GetMessageJS('CRM_WAIT_ACTIVITY_TARGET_PREFIX_TYPE_BEFORE')?>'
		};
		WaitSelectorController.prototype.getMessage = function(name)
		{
			var m = WaitSelectorController.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		};
		WaitSelectorController.prototype.openConfigDialog = function(type)
		{
			this._type = type;
			this._popupDialog = new BX.PopupWindow(
				this._id + 'config-dialog',
				waitSelector,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					zIndex: 200,
					content: this.prepareDialogContent(type),
					events:
						{
							onPopupClose: BX.delegate(this.onPopupClose, this),
							onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
						},
					buttons:
						[
							new BX.PopupWindowButton(
								{
									text: this.getMessage("select"),
									className: "popup-window-button-accept" ,
									events: { click: this.onSaveButtonClick.bind(this)}
								}
							),
							new BX.PopupWindowButtonLink(
								{
									text : BX.message("JS_CORE_WINDOW_CANCEL"),
									events: { click: this.closeConfigDialog.bind(this) }
								}
							)
						]
				}
			);
			this._popupDialog.show();
		};
		WaitSelectorController.prototype.closeConfigDialog = function()
		{
			if (this._popupDialog)
			{
				this._popupDialog.close();
			}
		};
		WaitSelectorController.prototype.prepareDialogContent = function(type)
		{
			var container = BX.create("div", { attrs: { className: "bizproc-automation-popup-wait-select-block" } });
			var wrapper = BX.create("div", { attrs: { className: "bizproc-automation-popup-wait-select-wrapper" } });
			container.appendChild(wrapper);

			this._durationInput = BX.create(
				"input",
				{
					attrs: { type: "text", className: "bizproc-automation-popup-wait-settings-input", value: waitDurationInput.value },
					events: { keyup: BX.delegate(this.onDurationChange, this) }
				}
			);

			this._durationMeasureNode = BX.create(
				"span",
				{ attrs: { className: "bizproc-automation-popup-wait-settings-title" }, text: getDurationText(waitDurationInput.value, false) }
			);

			if(type === 'after')
			{
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "bizproc-automation-popup-wait-settings-title" }, text: this.getMessage("prefixTypeAfter") })
				);
				wrapper.appendChild(this._durationInput);
				wrapper.appendChild(this._durationMeasureNode);
			}
			else
			{
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "bizproc-automation-popup-wait-settings-title" }, text: this.getMessage("prefixTypeBefore") })
				);
				wrapper.appendChild(this._durationInput);
				wrapper.appendChild(this._durationMeasureNode);
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "bizproc-automation-popup-wait-settings-title" }, text: " " + this.getMessage("targetPrefixTypeBefore") })
				);

				this._target = waitTargetInput.value;
				if(this._target === '' && targetDateFields.length > 0)
				{
					this._target = targetDateFields[0]["name"];
					waitTargetInput.value = this._target;
				}

				this._targetDateNode = BX.create(
					"span",
					{
						attrs: { className: "bizproc-automation-popup-settings-link" },
						text: getTargetDateCaption(waitTargetInput.value),
						events: { click: BX.delegate(this.toggleTargetMenu, this) }
					}
				);
				wrapper.appendChild(this._targetDateNode);
			}
			return container;
		};
		WaitSelectorController.prototype.onDurationChange = function()
		{
			var duration = parseInt(this._durationInput.value);
			if(isNaN(duration) || duration <= 0)
			{
				duration = 1;
				if (this._durationInput.value !== '')
				{
					this._durationInput.value = duration;
				}
			}
			this._durationMeasureNode.textContent = getDurationText(duration, false);

		};
		WaitSelectorController.prototype.toggleTargetMenu = function()
		{
			if(this.isTargetMenuOpened())
			{
				this.closeTargetMenu();
			}
			else
			{
				this.openTargetMenu();
			}
		};
		WaitSelectorController.prototype.isTargetMenuOpened = function()
		{
			return !!BX.PopupMenu.getMenuById(this._id + 'target');
		};
		WaitSelectorController.prototype.openTargetMenu = function()
		{
			var menuItems = [];
			for(var i = 0; i < targetDateFields.length; i++)
			{
				var info = targetDateFields[i];

				menuItems.push(
					{
						text: info["caption"],
						title: info["caption"],
						value: info["name"],
						onclick: this.onTargetSelect.bind(this)
					}
				);
			}

			BX.PopupMenu.show(
				this._id + 'target',
				this._targetDateNode,
				menuItems,
				{
					zIndex: 200,
					autoHide: true,
					offsetLeft: BX.pos(this._targetDateNode)["width"] / 2,
					angle: { position: 'top', offset: 0 }
				}
			);
		};
		WaitSelectorController.prototype.closeTargetMenu = function()
		{
			BX.PopupMenu.destroy(this._id + 'target');
		};
		WaitSelectorController.prototype.onPopupClose = function()
		{
			if(this._popupDialog)
			{
				this._popupDialog.destroy();
			}

			this.closeTargetMenu();
		};
		WaitSelectorController.prototype.onPopupDestroy = function()
		{
			if(this._popupDialog)
			{
				this._popupDialog = null;
			}
		};
		WaitSelectorController.prototype.onSaveButtonClick = function(e)
		{
			waitTypeInput.value = this._type;
			waitDurationInput.value = this._durationInput.value;
			waitTargetInput.value = this._type === 'before' ? this._target : "";
			this.closeConfigDialog();
			updateSelectorLabel();
		};
		WaitSelectorController.prototype.onTargetSelect = function(e, item)
		{
			var fieldName = BX.prop.getString(item, "value", "");
			if(fieldName !== "")
			{
				this._target = fieldName;
				this._targetDateNode.textContent = getTargetDateCaption(fieldName);
			}

			this.closeTargetMenu();
		};

		var controller = new WaitSelectorController();
	});
</script>