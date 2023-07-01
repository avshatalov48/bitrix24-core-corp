import {Type, Text, Tag, Loc, bind, unbind} from "main.core";
import {EventEmitter} from 'main.core.events';
import {PopupManager} from "main.popup";
import {Button} from 'ui.buttons';
import 'ui.design-tokens';

import "./summary-list.css"

type Settings = {
	controller: Object;
	anchor: Object;
	wrapper: Object;
	clientSearchBox: Object;
};

class ItemInfo
{
	#entityTypeName: "";
	#entityId: 0;
	#entityTypeTitle: "";
	#entityTitle: "";
	#isMy: false;
	#entityUrl: "";
	#relatedEntityTitle = "";
	#communications: {
		phone: string[],
		email: string[],
	};

	constructor()
	{
		this.#communications = {
			phone: [],
			email: [],
		};
	}

	toPlainObject()
	{
		return {
			entityTypeName: this.#entityTypeName,
			entityId: this.#entityId,
			entityTypeTitle: this.#entityTypeTitle,
			entityTitle: this.#entityTitle,
			isMy: this.#isMy,
			entityUrl: this.#entityUrl,
			relatedEntityTitle: this.#relatedEntityTitle,
			communications: this.#communications,
		};
	}

	set entityTypeName(value: string)
	{
		this.#entityTypeName = value;
	}

	get entityTypeName(): string
	{
		return this.#entityTypeName;
	}

	set entityId(value: number)
	{
		this.#entityId = value;
	}

	get entityId(): number
	{
		return this.#entityId;
	}

	set entityTypeTitle(value: string)
	{
		this.#entityTypeTitle = value;
	}

	get entityTypeTitle(): string
	{
		return this.#entityTypeTitle;
	}

	set entityTitle(value: string)
	{
		this.#entityTitle = value;
	}

	get entityTitle(): string
	{
		return this.#entityTitle;
	}

	set isMy(value: boolean)
	{
		this.#isMy = value;
	}

	get isMy(): boolean
	{
		return this.#isMy;
	}

	set entityUrl(value: string)
	{
		this.#entityUrl = value;
	}

	get entityUrl(): string
	{
		return this.#entityUrl;
	}

	set relatedEntityTitle(value: string)
	{
		this.#relatedEntityTitle = value;
	}

	get relatedEntityTitle(): string
	{
		return this.#relatedEntityTitle;
	}

	#addCommunicationValue(communicationType: string, value: string)
	{
		if (this.#communications[communicationType].indexOf(value) < 0)
		{
			this.#communications[communicationType].push(value);
		}
	}

	#addCommunicationList(communicationType: string, list: Array)
	{
		for (let i = 0; i < list.length; i++)
		{
			this.#addCommunicationValue(communicationType, list[i]);
		}
	}
	
	addPhones(values: Array)
	{
		this.#addCommunicationList("phone", values);
	}

	addEmails(values: Array)
	{
		this.#addCommunicationList("email", values);
	}
}

class SummaryList extends EventEmitter
{
	#handleWindowResize: Function | null;

	constructor()
	{
		super();
		this.setEventNamespace('crm.entity-editor.summary-list.close')

		this.id = '';
		this.popupId = '';
		this.settings = {};
		this.anchor = null;
		this.wrapper = null;
		this.clientSearchBox = null;
		this.enableEntitySelect = false;
		this.items = [];
		this.padding = 0;
		this.#handleWindowResize = null;
	}

	initialize(id: string, settings: Settings)
	{
		this.id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this.popupId = this.id + "_popup";

		if (Type.isPlainObject(settings))
		{
			this.settings = settings;
			this.anchor = BX.prop.getElementNode(settings, "anchor", null);
			this.wrapper = BX.prop.getElementNode(settings, "wrapper", null);
			this.clientSearchBox = BX.prop.get(settings, "clientSearchBox", null);
			this.enableEntitySelect = BX.prop.getBoolean(settings, "enableEntitySelect", false);
		}

		this.padding = BX.prop.getInteger(settings, 'padding', 14);
	}

	show()
	{
		const popup = PopupManager.create({
			id: this.popupId,
			cacheable: false,
			padding: this.padding,
			contentPadding: 0,
			content: this.getLayout(),
			closeIcon: {
				top: '10px',
				right: '5px',
			},
			closeByEsc: false,
			background: this.#getPopupBackgroundColor(),
			animation: {
				closeAnimationType: 'animation',
				showClassName: 'crm-dups-popup-open',
				closeClassName: 'crm-dups-popup-close',
			}
		});

		if (!this.#handleWindowResize)
		{
			this.#handleWindowResize = this.adjustPosition.bind(this);

			bind(window, 'resize', this.#handleWindowResize);
		}

		popup.subscribe('onDestroy', () => {
			this.emit('close', this);
		});

		popup.subscribe("onFirstShow", (event) => {
			event.target.getZIndexComponent().subscribe("onZIndexChange", (event) => {
				if (event.target.getZIndex() !== 850)
				{
					event.target.setZIndex(850);
				}
			});
		});
		popup.show();
		this.adjustPosition();
	}

	getController()
	{
		const controller = BX.prop.get(this.settings, "controller", null);

		return (controller instanceof BX.CrmDupController) ? controller : null;
	}

	getTargetEntityTypeName()
	{
		const controller = this.getController();

		return controller ? controller.getEntityTypeName() : "";
	}

	getDuplicateData()
	{
		const controller = this.getController();

		return controller ? controller.getDuplicateData() : {};
	}

	getGroup(groupId)
	{
		const controller = Type.isStringFilled(groupId) ? this.getController() : null;

		return controller ? controller.getGroup(groupId) : null;
	}

	getGroupSummaryTitle(groupId, groupData)
	{
		if (
			Type.isPlainObject(groupData)
			&& groupData.hasOwnProperty("totalText")
			&& Type.isStringFilled(groupData['totalText'])
		)
		{
			const group = this.getGroup(groupId);
			const title = group ? group.getSummaryTitle() : "";
			if (Type.isStringFilled(title))
			{
				return groupData['totalText'] + " " + title;
			}
		}

		return "";
	}

	getLayoutData()
	{
		const result = {
			title: "",
			groups: []
		};

		const data = this.getDuplicateData();

		let totalItemCount = 0;
		for (const groupId in data)
		{
			if (!data.hasOwnProperty(groupId))
			{
				continue;
			}

			const groupData = Type.isPlainObject(data[groupId]) ? data[groupId] : {};
			const items = Type.isArray(groupData["items"]) ? groupData["items"] : [];

			const groupInfo = {
				title: this.getGroupSummaryTitle(groupId, groupData),
				items: []
			};

			const entityTypeIdMap = [];
			for (let i = 0; i < items.length; i++)
			{
				const item = items[i];
				const entities = Type.isArray(item["ENTITIES"]) ? item["ENTITIES"] : [];
				for (let j = 0; j < entities.length; j++)
				{
					const entity = entities[j];
					const entityTypeId = this.getEntityTypeId(entity);
					if (!BX.CrmEntityType.isDefined(entityTypeId))
					{
						continue;
					}
					const entityTypeName = BX.CrmEntityType.resolveName(entityTypeId);
					const entityId = this.getEntityId(entity);

					let needAdd = false;

					if (!entityTypeIdMap.hasOwnProperty(entityTypeName))
					{
						entityTypeIdMap[entityTypeName] = [entityId];
						needAdd = true;
					}
					else
					{
						const isExists = (entityTypeIdMap[entityTypeName].indexOf(entityId) >= 0);
						if (!isExists)
						{
							entityTypeIdMap[entityTypeName].push(entityId);
							needAdd = true;
						}
					}

					if (needAdd)
					{
						groupInfo.items.push(this.prepareItemInfo(entity));
					}
				}
			}

			if (groupInfo.items.length > 0)
			{
				totalItemCount += groupInfo.items.length;
				result.groups.push(groupInfo);
			}
		}

		result.title = Loc.getMessage(
			"DUPLICATE_SUMMARY_LIST_TOTAL_COUNT_TITLE",
			{ "#COUNT#": totalItemCount }
		);

		return result;
	}

	getEntityTypeId(entity: Object)
	{
		return Type.isStringFilled(entity["ENTITY_TYPE_ID"]) ? parseInt(entity["ENTITY_TYPE_ID"]) : 0;
	}

	getEntityId(entity: Object)
	{
		return Type.isStringFilled(entity["ENTITY_ID"]) ? parseInt(entity["ENTITY_ID"]) : 0;
	}

	prepareItemInfo(entity: Object): Object
	{
		const itemInfo = new ItemInfo();

		const entityTypeId = this.getEntityTypeId(entity);
		itemInfo.entityTypeName = BX.CrmEntityType.resolveName(entityTypeId)
		itemInfo.entityId = this.getEntityId(entity);
		itemInfo.entityTypeTitle = BX.CrmEntityType.getCaption(entityTypeId)
		itemInfo.entityTitle = BX.prop.getString(entity, "TITLE", "");
		itemInfo.isMy = (
			entityTypeId === BX.CrmEntityType.enumeration.company
			&& BX.prop.getString(entity, "IS_MY_COMPANY", "") === "Y"
		);
		itemInfo.entityUrl = BX.prop.getString(entity, "URL", "");
		itemInfo.addPhones(BX.prop.getArray(entity, "PHONE", []));
		itemInfo.addEmails(BX.prop.getArray(entity, "EMAIL", []));

		return itemInfo.toPlainObject();
	}

	renderItemDetails(item: Object): string
	{
		let content = "";

		const communications = item["communications"];

		let needDots = false;

		["phone", "email"].forEach((type) => {
			if (!needDots && communications[type].length > 5)
			{
				needDots = true;
			}
			if (communications[type].length > 0)
			{
				for (let i = 0; i < communications[type].length; i++)
				{
					if (content.length > 0)
					{
						content += ", ";
					}
					content += communications[type][i];
				}
			}
		});

		if (needDots)
		{
			content += ", ...";
		}

		return content;
	}

	getLayout()
	{
		const layoutData = this.getLayoutData();

		if (
			!(
				Type.isStringFilled(layoutData["title"])
				&& Type.isArray(layoutData["groups"])
				&& layoutData["groups"].length > 0
			)
		)
		{
			return "";
		}

		return Tag.render`
			<div class="crm-dups-wrapper">
				<div class="crm-dups-header">${Text.encode(layoutData["title"])}</div>
				<div class="crm-dups-list">${layoutData["groups"].map((group) => Tag.render`
					<div class="crm-dups-group">
						<div class="crm-dups-group-header">${Text.encode(group["title"])}</div>
						<div class="crm-dups-group-items">${group["items"].map((item) => Tag.render`
							<div class="crm-dups-item">
								<div class="crm-dups-item-top">
									<div class="crm-dups-item-header">
										<div class="crm-dups-item-type">${Text.encode(item["entityTypeTitle"])}</div>
										<a
											href="${Text.encode(item["entityUrl"])}"
											class="crm-dups-item-title">${Text.encode(item["entityTitle"])}</a>
										<div class="crm-dups-item-rel-title hidden"></div>
									</div>
									${this.#renderAddButton({
										"type": item["entityTypeName"],
										"id": item["entityId"],
										"title": item["entityTitle"],
										"isMy": item["isMy"]
									})}
								</div>
								<div class="crm-dups-item-details">
									${Text.encode(this.renderItemDetails(item))}
								</div>
							</div>
						`)}</div>
					</div>
				`)}</div>
			</div>
		`;
	}

	adjustPosition()
	{
		const popup = PopupManager.getPopupById(this.popupId);
		if (
			!popup
			|| !popup.isShown()
			|| !Type.isDomNode(this.anchor)
			|| !Type.isDomNode(this.wrapper)
		)
		{
			return;
		}

		const wrapperRect = this.wrapper.getBoundingClientRect();
		const itemRect = this.anchor.getBoundingClientRect();
		const viewRect = document.documentElement.getBoundingClientRect();
		const viewTop = - viewRect.top;
		const viewBottom = viewRect.height - viewRect.top;
		const offsetLeft = -viewRect.left + wrapperRect.left + wrapperRect.width + this.padding;
		const popupHeight = popup.getPopupContainer().clientHeight;

		let popupVerticalPosition;
		let angleOffset;
		const itemVerticalCenter = viewTop + itemRect.top + itemRect.height / 2;
		if (itemVerticalCenter < viewTop)
		{
			popupVerticalPosition = viewTop + itemRect.top - this.padding;
			angleOffset = this.padding + itemRect.height / 2;
		}
		else if (itemVerticalCenter > viewBottom)
		{
			popupVerticalPosition = viewTop + itemRect.bottom + this.padding - popupHeight;
			angleOffset = popupHeight - this.padding - itemRect.height / 2;
		}
		else if (popupHeight < viewRect.height)
		{
			let verticalOffset = 0;
			popupVerticalPosition = itemVerticalCenter - popupHeight / 2;
			if (popupVerticalPosition < viewTop)
			{
				verticalOffset = viewTop - popupVerticalPosition
			}
			else if (viewBottom < popupVerticalPosition + popupHeight)
			{
				verticalOffset = viewBottom - popupVerticalPosition - popupHeight;
			}
			popupVerticalPosition += verticalOffset;
			angleOffset = itemVerticalCenter - popupVerticalPosition;
		}
		else
		{
			popupVerticalPosition = viewTop;
			angleOffset = itemVerticalCenter - popupVerticalPosition;
			if (angleOffset < 0)
			{
				angleOffset += popupHeight;
			}
		}
		angleOffset -= this.padding;

		popup.setBindElement({left: offsetLeft, top: popupVerticalPosition});
		popup.setAngle({position: "left", offset: angleOffset});
		popup.adjustPosition();
		setTimeout(() => popup.getZIndexComponent().setZIndex(850), 0);
	}

	isShown()
	{
		const popup = PopupManager.getPopupById(this.popupId);
		return popup && popup.isShown();
	}

	close()
	{
		const popup = PopupManager.getPopupById(this.popupId);
		popup ? popup.close() : null;
		unbind(document, 'resize', this.#handleWindowResize);
		this.#handleWindowResize = null;
	}

	#getPopupBackgroundColor(): string
	{
		const bodyStyles = getComputedStyle(document.body);
		return bodyStyles?.getPropertyValue("--ui-color-palette-gray-03") || '#F5F7F8';
	}

	#renderAddButton(options): HTMLElement|string
	{
		if (
			!this.enableEntitySelect
			|| (
				options.hasOwnProperty("isMy")
				&& options["isMy"]
			)
			|| (
				Type.isPlainObject(options)
				&& options.hasOwnProperty("type")
				&& options["type"] !== this.getTargetEntityTypeName()
			)
		)
		{
			return "";
		}

		const btn = new Button({
			round: true,
			color: Button.Color.LIGHT_BORDER,
			size: Button.Size.EXTRA_SMALL,
			text: Loc.getMessage('DUPLICATE_SUMMARY_LIST_ITEM_ADD_BUTTON'),
			context: {
				type: options["type"],
				id: options["id"],
				title: BX.prop.getString(options, "title", ""),
			},
			onclick: (btn, e) => {
				e.stopPropagation();
				this.onAddButtonClick(btn.getContext());
			},
		});

		return Tag.render`<div class="crm-dups-item-add-btn">${btn.render()}</div>`;
	}

	onAddButtonClick(context)
	{
		if (this.clientSearchBox)
		{
			EventEmitter.emit(this.clientSearchBox, 'onSelectEntityExternal', context);
		}
		this.close();
	}

	static create(id, settings)
	{
		const self = new SummaryList();
		self.initialize(id, settings);
		return self;
	}
}

export {
	SummaryList
};
