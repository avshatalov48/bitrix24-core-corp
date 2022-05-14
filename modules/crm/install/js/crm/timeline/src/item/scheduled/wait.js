import Scheduled from "../scheduled";
import {Item as ItemType} from "../../types";
import Item from "../../item";
import SchedulePostponeController from "../../tools/schedule-postpone-controller";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Wait extends Scheduled
{
	constructor()
	{
		super();
		this._postponeController = null;
	}
	
	getTypeId()
	{
		return ItemType.wait;
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-wait";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-wait";
	}

	prepareActions()
	{
	}

	isCounterEnabled()
	{
		return false;
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

	isDone()
	{
		return (BX.prop.getString(this.getAssociatedEntityData(), "COMPLETED", "N") ===  "Y");
	}

	setAsDone(isDone)
	{
		isDone = !!isDone;
		if(this.isDone() === isDone)
		{
			return;
		}

		const id = this.getAssociatedEntityId();
		if(id > 0)
		{
			const editor = this._schedule.getManager().getWaitEditor();
			if(editor)
			{
				editor.complete(
					id,
					isDone,
					BX.delegate(this.onSetAsDoneCompleted, this)
				);
			}
		}
	}

	postpone(offset)
	{
		const id = this.getAssociatedEntityId();
		if(id > 0 && offset > 0)
		{
			const editor = this._schedule.getManager().getWaitEditor();
			if(editor)
			{
				editor.postpone(
					id,
					offset,
					BX.delegate(this.onPosponeCompleted, this)
				);
			}
		}
	}

	isContextMenuEnabled()
	{
		return !!this.getDeadline() && this.canPostpone();
	}

	prepareContent()
	{
		const deadline = this.getDeadline();
		const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		const entityData = this.getAssociatedEntityData();
		const isDone = this.isDone();
		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

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
			description = BX.util.trim(description);
			description = BX.util.strip_tags(description);
			description = this.cutOffText(description, 512);
			description = BX.util.nl2br(description);
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
				attrs: {className: "crm-entity-stream-content-header"},
				children:
					[
						BX.create("SPAN",
							{
								attrs: {className: "crm-entity-stream-content-event-title"},
								text: this.getMessage("wait")
							}
						),
						this._deadlineNode
					]
			}
		);
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
					attrs: { className: "crm-entity-stream-content-detail-description" },
					html: description
				}
			)
		);

		const members = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-contact-info"}}
		);

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

	prepareContextMenuItems()
	{
		const menuItems = [];
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

	getTypeDescription()
	{
		return this.getMessage("wait");
	}

	static create(id, settings)
	{
		const self = new Wait();
		self.initialize(id, settings);
		return self;
	}
}
