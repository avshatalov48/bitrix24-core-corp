import { Item } from 'crm.timeline.item';
import { Type } from 'main.core';
import { Timezone } from 'main.date';
import { Item as ItemType } from '../types';

/** @memberof BX.Crm.Timeline */
export default class CompatibleItem extends Item
{
	constructor()
	{
		super();
		this._id = "";
		this._settings = {};
		this._data = {};
		this._container = null;

		this._typeCategoryId = null;
		this._associatedEntityData = null;
		this._associatedEntityTypeId = null;
		this._associatedEntityId = null;
		this._isContextMenuShown = false;
		this._contextMenuButton = null;

		this._activityEditor = null;
		this._actions = [];
		this._actionContainer = null;

		this._existedStreamItemDeadLine = null;
	}

	initialize(id, settings)
	{
		this._setId(id);
		this._settings = settings ? settings : {};

		this._container = this.getSetting("container");

		if(!BX.type.isPlainObject(settings['data']))
		{
			throw "Item. A required parameter 'data' is missing.";
		}
		this._data = settings['data'];

		this._activityEditor = this.getSetting("activityEditor");

		this.doInitialize();
	}

	doInitialize()
	{
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	getData()
	{
		return this._data;
	}

	setData(data)
	{
		if(BX.type.isPlainObject(data))
		{
			this._data = data;
			this.clearCachedData();
		}
	}

	getSort(): Array
	{
		return this._data['sort'] ?? [];
	}

	getAssociatedEntityData()
	{
		if(this._associatedEntityData === null)
		{
			this._associatedEntityData = BX.type.isPlainObject(this._data["ASSOCIATED_ENTITY"])
				? this._data["ASSOCIATED_ENTITY"] : {};
		}

		return this._associatedEntityData;
	}

	getAssociatedEntityTypeId()
	{
		if(this._associatedEntityTypeId === null)
		{
			this._associatedEntityTypeId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_TYPE_ID", 0)
		}
		return this._associatedEntityTypeId;
	}

	getAssociatedEntityId()
	{
		if(this._associatedEntityId === null)
		{
			this._associatedEntityId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_ID", 0)
		}
		return this._associatedEntityId;
	}

	setAssociatedEntityData(associatedEntityData)
	{
		if(!BX.type.isPlainObject(associatedEntityData))
		{
			associatedEntityData = {};
		}

		const data = this._data;
		data.ASSOCIATED_ENTITY = associatedEntityData;

		this.setData(data);
	}

	hasPermissions()
	{
		const entityData = this.getAssociatedEntityData();
		return BX.type.isPlainObject(entityData["PERMISSIONS"]);
	}

	getPermissions()
	{
		return BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
	}

	setPermissions(permissions)
	{
		const data = this._data;

		if(!Type.isPlainObject(data.ASSOCIATED_ENTITY))
		{
			data.ASSOCIATED_ENTITY = {};
		}

		data.ASSOCIATED_ENTITY.PERMISSIONS = permissions;

		this.setData(data);
	}

	getTextDataParam(name)
	{
		return BX.prop.getString(this._data, name, "");
	}

	getObjectDataParam(name)
	{
		return BX.prop.getObject(this._data, name, {});
	}

	getArrayDataParam(name)
	{
		return BX.prop.getArray(this._data, name, []);
	}

	getTypeId()
	{
		return ItemType.undefined;
	}

	getTypeCategoryId()
	{
		if(this._typeCategoryId === null)
		{
			this._typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
		}
		return this._typeCategoryId;
	}

	getContainer()
	{
		return this._container;
	}

	setContainer(container)
	{
		this._container = BX.type.isElementNode(container) ? container : null;
	}

	layout(options)
	{
		if(!BX.type.isElementNode(this._container))
		{
			throw "Item. Container is not assigned.";
		}

		this.prepareLayout(options);
		//region Actions
		/**/
		this.prepareActions();
		const actionQty = this._actions.length;
		for(let i = 0; i < actionQty; i++)
		{
			this._actions[i].layout();
		}
		this.showActions(actionQty > 0);
		/**/
		//endregion
	}

	prepareLayout(options)
	{
	}

	prepareActions()
	{
	}

	showActions(show)
	{
	}

	clearCachedData()
	{
		this._typeCategoryId = null;
		this._associatedEntityData = null;
		this._associatedEntityTypeId = null;
		this._associatedEntityId = null;
	}

	isDone()
	{
		return false;
	}

	markAsDone(isDone)
	{
	}

	view()
	{
	}

	edit()
	{
	}

	fasten()
	{
	}

	unfasten()
	{
	}

	remove()
	{
	}

	cutOffText(text, length)
	{
		if(!BX.type.isNumber(length))
		{
			length = 0;
		}

		if(length <= 0 || text.length <= length)
		{
			return text;
		}

		let offset = length - 1;
		const whilespaceOffset = text.substring(offset).search(/\s/i);
		if(whilespaceOffset > 0)
		{
			offset += whilespaceOffset;
		}
		return text.substring(0, offset);
	}

	prepareMultilineCutOffElements(text, length, clickHandler)
	{
		if(!BX.type.isNumber(length))
		{
			length = 0;
		}

		if(length <= 0 || text.length <= length)
		{
			return [BX.util.htmlspecialchars(text).replace(/(?:\r\n|\r|\n)/g, '<br>')];
		}

		let offset = length - 1;
		const whilespaceOffset = text.substring(offset).search(/\s/i);
		if(whilespaceOffset > 0)
		{
			offset += whilespaceOffset;
		}
		return(
			[
				BX.util.htmlspecialchars(text.substring(0, offset)).replace(/(?:\r\n|\r|\n)/g, '<br>') + "&hellip;&nbsp;" ,
				BX.create("A",
					{
						attrs: { className: "crm-entity-stream-content-letter-more", href: "#" },
						events: { click: clickHandler },
						text: this.getMessage("details")
					}
				)
			]
		);
	}

	prepareCutOffElements(text, length, clickHandler)
	{
		if(!BX.type.isNumber(length))
		{
			length = 0;
		}

		if(length <= 0 || text.length <= length)
		{
			return [BX.util.htmlspecialchars(text)];
		}

		let offset = length - 1;
		const whilespaceOffset = text.substring(offset).search(/\s/i);
		if(whilespaceOffset > 0)
		{
			offset += whilespaceOffset;
		}
		return(
			[
				BX.util.htmlspecialchars(text.substring(0, offset)) + "&hellip;&nbsp;" ,
				BX.create("A",
					{
						attrs: { className: "crm-entity-stream-content-letter-more", href: "#" },
						events: { click: clickHandler },
						text: this.getMessage("details")
					}
				)
			]
		);
	}

	prepareAuthorLayout()
	{
		const authorInfo = this.getObjectDataParam("AUTHOR", null);
		if(!authorInfo)
		{
			return null;
		}

		const showUrl = BX.prop.getString(authorInfo, "SHOW_URL", "");
		if(showUrl === "")
		{
			return null;
		}

		const link = BX.create("A",
			{
				attrs:
					{
						className: "ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee",
						href: showUrl,
						target: "_blank",
						title: BX.prop.getString(authorInfo, "FORMATTED_NAME", "")
					},
				children: [
					BX.create('i', {})
				]
			}
		);
		const imageUrl = BX.prop.getString(authorInfo, "IMAGE_URL", "");
		if(imageUrl !== "")
		{
			link.children[0].style.backgroundImage = "url('" + encodeURI(imageUrl) + "')";
			link.children[0].style.backgroundSize = "21px";
		}

		return link;
	}

	onActivityCreate(activity, data)
	{
	}

	isContextMenuEnabled()
	{
		return false;
	}

	prepareContextMenuButton()
	{
		this._contextMenuButton = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section-context-menu" },
				events: { click: BX.delegate(this.onContextMenuButtonClick, this) }
			}
		);
		return this._contextMenuButton;
	}

	onContextMenuButtonClick(e)
	{
		if(!this._isContextMenuShown)
		{
			this.openContextMenu();
		}
		else
		{
			this.closeContextMenu();
		}
	}

	openContextMenu()
	{
		const menuItems = this.prepareContextMenuItems();

		if (typeof IntranetExtensions !== "undefined")
		{
			menuItems.push(IntranetExtensions);
		}

		if(menuItems.length === 0)
		{
			return;
		}

		BX.PopupMenu.show(
			this._id,
			this._contextMenuButton,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 16,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupShow: BX.delegate(this.onContextMenuShow, this),
						onPopupClose: BX.delegate(this.onContextMenuClose, this),
						onPopupDestroy: BX.delegate(this.onContextMenuDestroy, this)
					}
			}
		);
		this._contextMenu = BX.PopupMenu.currentItem;
	}

	closeContextMenu()
	{
		if(this._contextMenu)
		{
			this._contextMenu.close();
		}
	}

	prepareContextMenuItems()
	{
		return [];
	}

	onContextMenuShow()
	{
		this._isContextMenuShown = true;
		BX.addClass(this._contextMenuButton, "active");
	}

	onContextMenuClose()
	{
		if(this._contextMenu)
		{
			this._contextMenu.popupWindow.destroy();
		}
	}

	onContextMenuDestroy()
	{
		this._isContextMenuShown = false;
		BX.removeClass(this._contextMenuButton, "active");
		this._contextMenu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	}

	getMessage(name)
	{
		const m = CompatibleItem.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static getUserTimezoneOffset()
	{
		return Timezone.Offset.USER_TO_SERVER;
	}

	static messages = {};
}
