import WithEditor from "./witheditor";
import WaitConfigurationDialog from "./tools/wait-configuration-dialog";
import {Loc, Tag} from "main.core";

/** @memberof BX.Crm.Timeline.MenuBar */

export default class Wait extends WithEditor
{
	#waitConfigContainer: HTMLElement = null;

	createLayout(): HTMLElement
	{
		this.#waitConfigContainer = Tag.render`<div class="crm-entity-stream-content-wait-conditions"></div>`;

		this._saveButton = Tag.render`<button onclick="${this.onSaveButtonClick.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round" >${Loc.getMessage('CRM_TIMELINE_CREATE_WAITING')}</button>`;
		this._cancelButton = Tag.render`<span onclick="${this.onCancelButtonClick.bind(this)}"  class="ui-btn ui-btn-xs ui-btn-link">${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}</span>`;
		this._input = Tag.render`<textarea rows="1" class="crm-entity-stream-content-wait-comment-textarea" placeholder="${Loc.getMessage('CRM_TIMELINE_WAIT_PLACEHOLDER')}"></textarea>`;

		return Tag.render`<div class="crm-entity-stream-content-wait-detail --focus --hidden">
			<div class="crm-entity-stream-content-wait-conditions-container">
				${this.#waitConfigContainer}
			</div>
			${this._input}
			<div class="crm-entity-stream-content-wait-comment-btn-container">
				${this._saveButton}
				${this._cancelButton}
			</div>
		</div>`;
	}

	doInitialize(): void
	{
		this._isRequestRunning = false;
		this._isLocked = false;

		this._hideButtonsOnBlur = false;
		//region Config
		this._type = Wait.WaitingType.after;
		this._duration = 1;
		this._target = "";
		this._configSelector = null;
		//endregion

		this._isMenuShown = false;
		this._menu = null;
		this._configDialog = null;

		this._serviceUrl = this.getSetting('serviceUrl', '');

		const config = this.getSetting('config', {});
		this._type = Wait.WaitingType.resolveTypeId(
			BX.prop.getString(
				config,
				'type',
				Wait.WaitingType.names.after
			)
		);
		this._duration = BX.prop.getInteger(config, 'duration', 1);
		this._target = BX.prop.getString(config, 'target', '');
		this._targetDates =this.getSetting('targetDates', []);
		this.layoutConfigurationSummary();
	}

	getDurationText(duration, enableNumber)
	{
		return Wait.Helper.getDurationText(duration, enableNumber);
	}

	getTargetDateCaption(name)
	{
		let i = 0;
		const length = this._targetDates.length;
		for(; i < length; i++)
		{
			const info = this._targetDates[i];
			if(info["name"] === name)
			{
				return info["caption"];
			}
		}

		return "";
	}

	onSelectorClick(e)
	{
		if(!this._isMenuShown)
		{
			this.openMenu();
		}
		else
		{
			this.closeMenu();
		}
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	}

	openMenu()
	{
		if(this._isMenuShown)
		{
			return;
		}

		const handler = BX.delegate(this.onMenuItemClick, this);

		const menuItems =
			[
				{id: "day_1", text: Loc.getMessage('CRM_TIMELINE_WAIT_1D'), onclick: handler},
				{id: "day_2", text: Loc.getMessage('CRM_TIMELINE_WAIT_2D'), onclick: handler},
				{id: "day_3", text: Loc.getMessage('CRM_TIMELINE_WAIT_3D'), onclick: handler},
				{id: "week_1", text: Loc.getMessage('CRM_TIMELINE_WAIT_1W'), onclick: handler},
				{id: "week_2", text: Loc.getMessage('CRM_TIMELINE_WAIT_2W'), onclick: handler},
				{id: "week_3", text: Loc.getMessage('CRM_TIMELINE_WAIT_3W'), onclick: handler}
			];

		const customMenu = {id: "custom", text: Loc.getMessage('CRM_TIMELINE_WAIT_CUSTOM'), items: []};
		customMenu["items"].push({ id: "afterDays", text: Loc.getMessage('CRM_TIMELINE_WAIT_AFTER_CUSTOM_DAYS'), onclick: handler });
		if(this._targetDates.length > 0)
		{
			customMenu["items"].push({ id: "beforeDate", text: Loc.getMessage('CRM_TIMELINE_WAIT_BEFORE_CUSTOM_DATE'), onclick: handler });
		}
		menuItems.push(customMenu);

		BX.PopupMenu.show(
			this._id,
			this._configSelector,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupShow: BX.delegate(this.onMenuShow, this),
						onPopupClose: BX.delegate(this.onMenuClose, this),
						onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
					}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
	}

	closeMenu()
	{
		if(!this._isMenuShown)
		{
			return;
		}

		if(this._menu)
		{
			this._menu.close();
		}
	}

	onMenuItemClick(e, item)
	{
		this.closeMenu();

		if(item.id === "afterDays" || item.id === "beforeDate")
		{
			this.openConfigDialog(
				item.id === "afterDays" ? Wait.WaitingType.after : Wait.WaitingType.before
			);
			return;
		}

		const params = {type: Wait.WaitingType.after};
		if(item.id === "day_1")
		{
			params["duration"] = 1;
		}
		else if(item.id === "day_2")
		{
			params["duration"] = 2;
		}
		else if(item.id === "day_3")
		{
			params["duration"] = 3;
		}
		if(item.id === "week_1")
		{
			params["duration"] = 7;
		}
		else if(item.id === "week_2")
		{
			params["duration"] = 14;
		}
		else if(item.id === "week_3")
		{
			params["duration"] = 21;
		}
		this.saveConfiguration(params);
	}

	openConfigDialog(type)
	{
		if(!this._configDialog)
		{
			this._configDialog = WaitConfigurationDialog.create(
				"",
				{
					targetDates: this._targetDates,
					onSave: BX.delegate(this.onConfigDialogSave, this),
					onCancel: BX.delegate(this.onConfigDialogCancel, this)
				}
			);
		}

		this._configDialog.setType(type);
		this._configDialog.setDuration(this._duration);

		let target = this._target;
		if(target === "" && this._targetDates.length > 0)
		{
			target = this._targetDates[0]["name"];
		}
		this._configDialog.setTarget(target);
		this._configDialog.open();
	}

	onConfigDialogSave(sender, params)
	{
		this.saveConfiguration(params);
		this._configDialog.close();
	}

	onConfigDialogCancel(sender)
	{
		this._configDialog.close();
	}

	onMenuShow()
	{
		this._isMenuShown = true;
	}

	onMenuClose()
	{
		if(this._menu && this._menu.popupWindow)
		{
			this._menu.popupWindow.destroy();
		}
	}

	onMenuDestroy()
	{
		this._isMenuShown = false;
		this._menu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	}

	saveConfiguration(params)
	{
		//region Parse params
		this._type = BX.prop.getInteger(params, "type", Wait.WaitingType.after);
		this._duration = BX.prop.getInteger(params, "duration", 0);
		if(this._duration <= 0)
		{
			this._duration = 1;
		}
		this._target = this._type === Wait.WaitingType.before
			? BX.prop.getString(params, "target", "") : "";
		//endregion
		//region Save settings
		const optionName = this.getSetting('optionName');
		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"type",
			this._type === Wait.WaitingType.after ? "after" : "before"
		);

		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"duration",
			this._duration
		);

		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"target",
			this._target
		);
		//endregion
		this.layoutConfigurationSummary();
	}

	getSummaryHtml()
	{
		if(this._type === Wait.WaitingType.before)
		{
			return (
				Loc.getMessage('CRM_TIMELINE_WAIT_COMPLETION_TYPE_BEFORE')
					.replace("#DURATION#", this.getDurationText(this._duration, true))
					.replace("#TARGET_DATE#", this.getTargetDateCaption(this._target))
			);
		}

		return (
			Loc.getMessage('CRM_TIMELINE_WAIT_COMPLETION_TYPE_AFTER')
				.replace("#DURATION#", this.getDurationText(this._duration, true))
		);
	}

	getSummaryText()
	{
		return BX.util.strip_tags(this.getSummaryHtml());
	}

	layoutConfigurationSummary()
	{
		this.#waitConfigContainer.innerHTML = this.getSummaryHtml();
		this._configSelector = this.#waitConfigContainer.querySelector("a");
		if(this._configSelector)
		{
			BX.bind(this._configSelector, 'click', this.onSelectorClick.bind(this));
		}
	}

	postpone(id, offset, callback)
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "POSTPONE_WAIT",
						"DATA": { "ID": id, "OFFSET": offset }
					},
				onsuccess: callback
			}
		);
	}

	complete(id, completed, callback)
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "COMPLETE_WAIT",
						"DATA": { "ID": id, "COMPLETED": completed ? 'Y' : 'N' }
					},
				onsuccess: callback
			}
		);
	}

	save()
	{
		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		let description = this.getSummaryText();
		const comment = BX.util.trim(this._input.value);
		if(comment !== "")
		{
			description += "\n" + comment;
		}

		const data =
			{
				ID: 0,
				typeId: this._type,
				duration: this._duration,
				targetFieldName: this._target,
				subject: "",
				description: description,
				completed: 0,
				ownerType: BX.CrmEntityType.resolveName(this.getEntityTypeId()),
				ownerID: this.getEntityId()
			};

		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "SAVE_WAIT",
						"DATA": data
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
		this._isRequestRunning = this._isLocked = true;
	}

	cancel()
	{
		this._input.value = "";
		this._input.style.minHeight = "";
		this.release();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = this._isLocked = false;

		const error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this._input.value = "";
		this._input.style.minHeight = "";
		this.emitFinishEditEvent();
		this.release();
	}

	onSaveFailure()
	{
		this._isRequestRunning = this._isLocked = false;
	}

	getMessage(name)
	{
		const m = Wait.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static WaitingType = {
		undefined: 0,
		after: 1,
		before: 2,

		names:
			{
				after: "after",
				before: "before"
			},
		resolveTypeId: function(name)
		{
			if(name === this.names.after)
			{
				return this.after;
			}
			else if(name === this.names.before)
			{
				return this.before;
			}

			return this.undefined;
		}
	};

	static messages = {};

	static Helper =
	{
		getDurationText: function(duration, enableNumber)
		{
			enableNumber = !!enableNumber;

			let result = "";
			let type = "D";
			if(enableNumber)
			{
				if((duration % 7) === 0)
				{
					duration = duration / 7;
					type = "W";
				}
			}

			if (type === "W")
			{
				result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_WEEK', duration);
			}
			else
			{
				result = BX.Loc.getMessagePlural('CRM_TIMELINE_WAIT_DAY', duration);
			}

			if(enableNumber)
			{
				result = duration.toString() + " " + result;
			}
			return result;
		},
		getMessage: function(name)
		{
			return Wait.Helper.messages.hasOwnProperty(name) ? Wait.Helper.messages[name] : name;
		},
		messages: {},
	}
}
