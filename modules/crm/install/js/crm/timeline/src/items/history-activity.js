import History from "./history";
import {Mark} from "../types";
import {HistoryEmail} from "../actions/email";

/** @memberof BX.Crm.Timeline.Items */
export default class HistoryActivity extends History
{
	constructor()
	{
		super();
	}

	doInitialize()
	{
		super.doInitialize();
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "HistoryActivity. The field 'activityEditor' is not assigned.";
		}
	}

	getTitle()
	{
		return BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", "");
	}

	getTypeDescription()
	{
		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);

		const typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
		}
		else if(typeCategoryId === BX.CrmActivityType.call)
		{
			return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
		}
		else if(typeCategoryId === BX.CrmActivityType.meeting)
		{
			return this.getMessage("meeting");
		}
		else if(typeCategoryId === BX.CrmActivityType.task)
		{
			return this.getMessage("task");
		}
		else if(typeCategoryId === BX.CrmActivityType.provider)
		{
			const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

			if(providerId === "CRM_WEBFORM")
			{
				return this.getMessage("webform");
			}
			else if (providerId === "CRM_SMS")
			{
				return this.getMessage("sms");
			}
			else if (providerId === "CRM_REQUEST")
			{
				return this.getMessage("activityRequest");
			}
			else if (providerId === "IMOPENLINES_SESSION")
			{
				return this.getMessage("openLine");
			}
			else if (providerId === "REST_APP")
			{
				return this.getMessage("restApplication");
			}
			else if (providerId === "VISIT_TRACKER")
			{
				return this.getMessage("visit");
			}
			else if (providerId === "ZOOM")
			{
				return this.getMessage("zoom");
			}
		}

		return "";
	}

	prepareTitleLayout()
	{
		return BX.create("A",
			{
				attrs: { href: "#",  className: "crm-entity-stream-content-event-title" },
				events: { "click": this._headerClickHandler },
				text: this.getTypeDescription()
			}
		);
	}

	prepareTimeLayout()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	}

	prepareMarkLayout()
	{
		const entityData = this.getAssociatedEntityData();
		const markTypeId = BX.prop.getInteger(entityData, "MARK_TYPE_ID", 0);
		if(markTypeId <= 0)
		{
			return null;
		}

		let messageName = "";
		if(markTypeId === Mark.success)
		{
			messageName = "SuccessMark";
		}
		else if(markTypeId === Mark.renew)
		{
			messageName = "RenewMark";
		}

		if(messageName === "")
		{
			return null;
		}

		let markText = "";
		const typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			markText = this.getMessage("email" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.call)
		{
			markText = this.getMessage("call" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.meeting)
		{
			markText = this.getMessage("meeting" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.task)
		{
			markText = this.getMessage("task" + messageName);
		}

		if(markText === "")
		{
			return null;
		}

		return(
			BX.create(
				"SPAN",
				{
					props: { className: "crm-entity-stream-content-event-skipped" },
					text: markText
				}
			)
		);
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		const typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			this._actions.push(
				HistoryEmail.create(
					"email",
					{
						item: this,
						container: this._actionContainer,
						entityData: this.getAssociatedEntityData(),
						activityEditor: this._activityEditor
					}
				)
			);
		}
	}

	prepareContextMenuItems()
	{
		if(this._isMenuShown)
		{
			return;
		}

		const menuItems = [];

		if (!this.isReadOnly())
		{
			if (this.isEditable())
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.edit, this)});
			}
			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}
		return menuItems;
	}

	view()
	{
		this.closeContextMenu();
		const entityData = this.getAssociatedEntityData();
		const id = BX.prop.getInteger(entityData, "ID", 0);
		if(id > 0)
		{
			this._activityEditor.viewActivity(id);
		}
	}

	edit()
	{
		this.closeContextMenu();
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const entityData = this.getAssociatedEntityData();
			const id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.editActivity(id);
			}
		}
	}

	processRemoval()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getRemoveMessage()
				}
			);
		}

		dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	}

	getRemoveMessage()
	{
		return this.getMessage('removeConfirm');
	}

	onRemovalConfirm(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.remove();
	}

	onRemovalCancel()
	{
	}

	remove()
	{
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();

		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const entityData = this.getAssociatedEntityData();

			const id = BX.prop.getInteger(entityData, "ID", 0);

			if(id > 0)
			{
				const activityEditor = this._activityEditor;
				const item = activityEditor.getItemById(id);
				if (item)
				{
					activityEditor.deleteActivity(id, true);
				}
				else
				{
					const serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''),
						{
							id: id,
							action: 'get_activity',
							ownertype: activityEditor.getSetting('ownerType', ''),
							ownerid: activityEditor.getSetting('ownerID', '')
						}
					);
					BX.ajax({
						'url': serviceUrl,
						'method': 'POST',
						'dataType': 'json',
						'data':
							{
								'ACTION' : 'GET_ACTIVITY',
								'ID': id,
								'OWNER_TYPE': activityEditor.getSetting('ownerType', ''),
								'OWNER_ID': activityEditor.getSetting('ownerID', '')
							},
						onsuccess: BX.delegate(
							function(data)
							{
								if(typeof(data['ACTIVITY']) !== 'undefined')
								{
									activityEditor._handleActivityChange(data['ACTIVITY']);
									window.setTimeout(BX.delegate(this.remove ,this), 500);
								}
							},
							this
						),
						onfailure: function(data){}
					});
				}
			}
		}
	}

	static create(id, settings)
	{
		const self = new HistoryActivity();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
