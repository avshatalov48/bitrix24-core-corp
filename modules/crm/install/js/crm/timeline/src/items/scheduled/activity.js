import Scheduled from "../scheduled";
import {Item as ItemType} from "../../types";
import Item from "../../item";
import SchedulePostponeController from "../../tools/schedule-postpone-controller";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Activity extends Scheduled
{
	constructor()
	{
		super();
		this._postponeController = null;
	}

	getTypeId()
	{
		return ItemType.activity;
	}

	isDone()
	{
		const status = BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS");
		return (status ===  BX.CrmActivityStatus.completed || status ===  BX.CrmActivityStatus.autoCompleted);
	}

	setAsDone(isDone)
	{
		isDone = !!isDone;
		if(this.isDone() === isDone)
		{
			return;
		}

		const id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
		if(id > 0)
		{
			this._activityEditor.setActivityCompleted(
				id,
				isDone,
				BX.delegate(this.onSetAsDoneCompleted, this)
			);
		}
	}

	postpone(offset)
	{
		const id = this.getSourceId();
		if(id > 0 && offset > 0)
		{
			this._activityEditor.postponeActivity(
				id,
				offset,
				BX.delegate(this.onPosponeCompleted, this)
			);
		}
	}

	view()
	{
		const id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
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
					const activityType = activityEditor.getSetting('ownerType', '');
					const activityId = activityEditor.getSetting('ownerID', '');

					const serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''),
						{
							id: id,
							action: 'get_activity',
							ownertype: activityType,
							ownerid: activityId
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
								'OWNER_TYPE': activityType,
								'OWNER_ID': activityId
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

	getDeadline()
	{
		const entityData = this.getAssociatedEntityData();
		const time = BX.parseDate(
			entityData["DEADLINE_SERVER"],
			false,
			"YYYY-MM-DD",
			"YYYY-MM-DD HH:MI:SS"
		);

		if(!time)
		{
			return null;
		}

		return new Date(time.getTime() + 1000 * Item.getUserTimezoneOffset());
	}

	markAsDone(isDone)
	{
		isDone = !!isDone;
		this.getAssociatedEntityData()["STATUS"] = isDone ? BX.CrmActivityStatus.completed : BX.CrmActivityStatus.waiting;
	}

	getPrepositionText(direction)
	{
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "from" : "to");
	}

	getTypeDescription(direction)
	{
		return "";
	}

	isContextMenuEnabled()
	{
		return ((!!this.getDeadline() && this.canPostpone()) || this.canComplete());
	}

	prepareContent(options)
	{
		const deadline = this.getDeadline();
		const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		const isDone = this.isDone();
		const subject = BX.prop.getString(entityData, "SUBJECT", "");
		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const title = BX.prop.getString(communication, "TITLE", "");
		const showUrl = BX.prop.getString(communication, "SHOW_URL", "");
		const communicationValue = BX.prop.getString(communication, "TYPE", "") !== ""
			? BX.prop.getString(communication, "VALUE", "") : "";

		let wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
		}

		const wrapper = BX.create("DIV", {attrs: {className: wrapperClassName}});

		let iconClassName = this.getIconClassName();
		if(this.isCounterEnabled())
		{
			iconClassName += " crm-entity-stream-section-counter";
		}
		wrapper.appendChild(BX.create("DIV", { attrs: { className: iconClassName } }));

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		const contentWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-section-content"}}
		);
		wrapper.appendChild(contentWrapper);

		//region Details
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		const contentInnerWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-event"}
			}
		);
		contentWrapper.appendChild(contentInnerWrapper);

		this._deadlineNode = BX.create("SPAN",
			{ attrs: { className: "crm-entity-stream-content-event-time" }, text: timeText }
		);

		const headerWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"}
			}
		);
		headerWrapper.appendChild(BX.create("SPAN",
			{
				attrs:
					{
						className: "crm-entity-stream-content-event-title"
					},
				text: this.getTypeDescription(direction)
			}
		));

		const statusNode = this.getStatusNode();
		if (statusNode)
		{
			headerWrapper.appendChild(statusNode);
		}
		headerWrapper.appendChild(this._deadlineNode);

		contentInnerWrapper.appendChild(headerWrapper);

		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"}
			}
		);
		contentInnerWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-title"},
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: subject
								}
							)
						]
				}
			)
		);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					text: this.cutOffText(description, 128)
				}
			)
		);

		const additionalDetails = this.prepareDetailNodes();
		if(BX.type.isArray(additionalDetails))
		{
			let i = 0;
			const length = additionalDetails.length;
			for(; i < length; i++)
			{
				detailWrapper.appendChild(additionalDetails[i]);
			}
		}

		const members = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-contact-info"}}
		);

		if(title !== '')
		{
			members.appendChild(
				BX.create("SPAN",
					{ text: this.getPrepositionText(direction) + ": " }
				)
			);

			if(showUrl !== '')
			{
				members.appendChild(
					BX.create("A",
						{
							attrs: { href: showUrl },
							text: title
						}
					)
				);
			}
			else
			{
				members.appendChild(BX.create("SPAN", { text: title }));
			}
		}

		if(communicationValue !== '')
		{
			const communicationNode = this.prepareCommunicationNode(communicationValue);
			if(communicationNode)
			{
				members.appendChild(communicationNode);
			}
		}

		detailWrapper.appendChild(members);
		//endregion
		//region Set as Done Button
		const setAsDoneButton = BX.create("INPUT",
			{
				attrs:
					{
						type: "checkbox",
						className: "crm-entity-stream-planned-apply-btn",
						checked: isDone
					},
				events: {change: this._setAsDoneButtonHandler}
			}
		);

		if(!this.canComplete())
		{
			setAsDoneButton.disabled = true;
		}

		const buttonContainer = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail-planned-action"},
				children: [setAsDoneButton]
			}
		);
		contentInnerWrapper.appendChild(buttonContainer);
		//endregion

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentInnerWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-action" }
			}
		);
		contentInnerWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	}

	getStatusNode()
	{
		return null;
	}

	prepareCommunicationNode(communicationValue)
	{
		return BX.create("SPAN", { text: " " + communicationValue });
	}

	prepareDetailNodes()
	{
		return [];
	}

	prepareContextMenuItems()
	{
		const menuItems = [];

		if (!this.isReadOnly())
		{
			if (this.isEditable())
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.edit, this)});
			}

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});
		}

		const handler = BX.delegate(this.onContextMenuItemSelect, this);

		if(!this._postponeController)
		{
			this._postponeController = SchedulePostponeController.create("", { item: this });
		}

		const postponeMenu =
			{
				id: "postpone",
				text: this._postponeController.getTitle(),
				items: []
			};

		const commands = this._postponeController.getCommandList();
		let i = 0;
		const length = commands.length;
		for(; i < length; i++)
		{
			const command = commands[i];
			postponeMenu.items.push(
				{
					id: command["name"],
					text: command["title"],
					onclick: handler
				}
			);
		}
		menuItems.push(postponeMenu);
		return menuItems;
	}

	onContextMenuItemSelect(e, item)
	{
		this.closeContextMenu();
		if(this._postponeController)
		{
			this._postponeController.processCommand(item.id);
		}
	}
}
