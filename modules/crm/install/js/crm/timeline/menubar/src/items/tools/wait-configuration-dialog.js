import Wait from "../wait";
import {Loc} from "main.core";

/** @memberof BX.Crm.Timeline.Tools */
export default class WaitConfigurationDialog
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._type = Wait.WaitingType.undefined;
		this._duration = 0;
		this._target = "";
		this._targetDates = null;
		this._container = null;
		this._durationMeasureNode = null;
		this._durationInput = null;
		this._targetDateNode = null;
		this._popup = null;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};
		this._type = BX.prop.getInteger(this._settings, "type", Wait.WaitingType.after);
		this._duration = BX.prop.getInteger(this._settings, "duration", 1);
		this._target = BX.prop.getString(this._settings, "target", "");
		this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);

		this._menuId = this._id + "_target_date_sel";
	}

	getId()
	{
		return this._id;
	}

	getType()
	{
		return this._type;
	}

	setType(type)
	{
		this._type = type;
	}

	getDuration()
	{
		return this._duration;
	}

	setDuration(duration)
	{
		this._duration = duration;
	}

	getTarget()
	{
		return this._target;
	}

	setTarget(target)
	{
		this._target = target;
	}

	getMessage(name)
	{
		const m = WaitConfigurationDialog.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getDurationText(duration, enableNumber)
	{
		return Wait.Helper.getDurationText(duration, enableNumber);
	}

	getTargetDateCaption(name)
	{
		const length = this._targetDates.length;
		for(let i = 0; i < length; i++)
		{
			const info = this._targetDates[i];
			if(info["name"] === name)
			{
				return info["caption"];
			}
		}

		return "";
	}

	open()
	{
		this._popup = new BX.PopupWindow(
			this._id,
			null, //this._configSelector,
			{
				autoHide: true,
				draggable: false,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				zIndex: 0,
				content: this.prepareDialogContent(),
				events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
				buttons:
					[
						new BX.PopupWindowButton(
							{
								text: Loc.getMessage('CRM_TIMELINE_CHOOSE'),
								className: "popup-window-button-accept" ,
								events: { click: BX.delegate(this.onSaveButtonClick, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CANCEL"),
								events: { click: BX.delegate(this.onCancelButtonClick, this) }
							}
						)
					]
			}
		);
		this._popup.show();
	}

	close()
	{
		if(this._popup)
		{
			this._popup.close();
		}
	}

	prepareDialogContent()
	{
		const container = BX.create("div", {attrs: {className: "crm-wait-popup-select-block"}});
		const wrapper = BX.create("div", {attrs: {className: "crm-wait-popup-select-wrapper"}});
		container.appendChild(wrapper);

		this._durationInput = BX.create(
			"input",
			{
				attrs: { type: "text", className: "crm-wait-popup-settings-input", value: this._duration },
				events: { keyup: BX.delegate(this.onDurationChange, this) }
			}
		);

		this._durationMeasureNode = BX.create(
			"span",
			{ attrs: { className: "crm-wait-popup-settings-title" }, text: this.getDurationText(this._duration, false) }
		);

		if(this._type === Wait.WaitingType.after)
		{
			wrapper.appendChild(
				BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: Loc.getMessage('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_AFTER') })
			);
			wrapper.appendChild(this._durationInput);
			wrapper.appendChild(this._durationMeasureNode);
		}
		else
		{
			wrapper.appendChild(
				BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: Loc.getMessage('CRM_TIMELINE_WAIT_CONFIG_PREFIX_TYPE_BEFORE') })
			);
			wrapper.appendChild(this._durationInput);
			wrapper.appendChild(this._durationMeasureNode);
			wrapper.appendChild(
				BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: " " + Loc.getMessage('CRM_TIMELINE_WAIT_TARGET_PREFIX_TYPE_BEFORE') })
			);

			this._targetDateNode = BX.create(
				"span",
				{
					attrs: { className: "crm-automation-popup-settings-link" },
					text: this.getTargetDateCaption(this._target),
					events: { click: BX.delegate(this.toggleTargetMenu, this) }
				}
			);
			wrapper.appendChild(this._targetDateNode);
		}
		return container;
	}

	onDurationChange()
	{
		let duration = parseInt(this._durationInput.value);
		if(isNaN(duration) || duration <= 0)
		{
			duration = 1;
		}
		this._duration = duration;
		this._durationMeasureNode.innerHTML = BX.util.htmlspecialchars(this.getDurationText(duration, false));

	}

	toggleTargetMenu()
	{
		if(this.isTargetMenuOpened())
		{
			this.closeTargetMenu();
		}
		else
		{
			this.openTargetMenu();
		}
	}

	isTargetMenuOpened()
	{
		return !!BX.PopupMenu.getMenuById(this._menuId);
	}

	openTargetMenu()
	{
		const menuItems = [];
		let i = 0;
		const length = this._targetDates.length;
		for(; i < length; i++)
		{
			const info = this._targetDates[i];

			menuItems.push(
				{
					text: info["caption"],
					title: info["caption"],
					value: info["name"],
					onclick: BX.delegate(this.onTargetSelect, this)
				}
			);
		}

		BX.PopupMenu.show(
			this._menuId,
			this._targetDateNode,
			menuItems,
			{
				zIndex: 200,
				autoHide: true,
				offsetLeft: BX.pos(this._targetDateNode)["width"] / 2,
				angle: { position: 'top', offset: 0 }
			}
		);
	}

	closeTargetMenu()
	{
		BX.PopupMenu.destroy(this._menuId);
	}

	onPopupShow(e, item)
	{
	}

	onPopupClose()
	{
		if(this._popup)
		{
			this._popup.destroy();
		}

		this.closeTargetMenu();
	}

	onPopupDestroy()
	{
		if(this._popup)
		{
			this._popup = null;
		}
	}

	onSaveButtonClick(e)
	{
		const callback = BX.prop.getFunction(this._settings, "onSave", null);
		if(!callback)
		{
			return;
		}

		const params = {type: this._type};
		params["duration"] = this._duration;
		params["target"] = this._type === Wait.WaitingType.before ? this._target : "";
		callback(this, params);
	}

	onCancelButtonClick(e)
	{
		const callback = BX.prop.getFunction(this._settings, "onCancel", null);
		if(callback)
		{
			callback(this);
		}
	}

	onTargetSelect(e, item)
	{
		const fieldName = BX.prop.getString(item, "value", "");
		if(fieldName !== "")
		{
			this._target = fieldName;
			this._targetDateNode.innerHTML = BX.util.htmlspecialchars(this.getTargetDateCaption(fieldName));
		}

		this.closeTargetMenu();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	}

	static create(id, settings)
	{
		const self = new WaitConfigurationDialog();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
