import {Dom, Type, Text, Loc} from 'main.core';
import {Menu} from 'main.popup'
import {baseZIndex, nop} from './common'

export class CallList
{
	constructor(params)
	{
		this.node = params.node;
		this.id = params.id;
		this.isDesktop = params.isDesktop;

		this.entityType = '';
		this.statuses = new Map(); // {STATUS_ID (string): { STATUS_NAME; string, CLASS: string, ITEMS: []}
		this.elements = {};
		this.currentStatusId = params.callListStatusId || 'IN_WORK';
		this.currentItemIndex = params.itemIndex || 0;
		this.callingStatusId = null;
		this.callingItemIndex = null;
		this.selectionLocked = false;

		// this.itemActionMenu = null;
		this.callbacks = {
			onError: Type.isFunction(params.onError) ? params.onError : nop,
			onSelectedItem: Type.isFunction(params.onSelectedItem) ? params.onSelectedItem : nop
		};

		this.showLimit = 10;
		this.showDelta = 10;
	}

	init(next)
	{
		if (!Type.isFunction(next))
		{
			next = nop;
		}

		this.load(() =>
		{
			const currentStatus = this.statuses.get(this.currentStatusId)
			if (currentStatus && currentStatus.ITEMS.length > 0)
			{
				this.update();
				this.selectItem(this.currentStatusId, this.currentItemIndex);
				next();
			}
			else
			{
				BX.debug('empty call list. don\'t know what to do');
			}
		})
	};

	/**
	 * @param {object} params
	 * @param {Node} params.node DOM node to render call list.
	 */
	reinit(params)
	{
		if (Type.isDomNode(params.node))
		{
			this.node = params.node;
		}

		this.update();
		this.selectItem(this.currentStatusId, this.currentItemIndex);
		if (this.callingStatusId !== null && this.callingItemIndex !== null)
		{
			this.setCallingElement(this.callingStatusId, this.callingItemIndex);
		}

	};

	load(next)
	{
		const params = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': 'GET_CALL_LIST',
			'callListId': this.id
		};

		BX.ajax({
			url: CallList.getAjaxUrl(),
			method: 'POST',
			dataType: 'json',
			data: params,
			onsuccess: (data) =>
			{
				if (!data.ERROR)
				{
					if (Type.isArray(data.STATUSES))
					{
						//this.statuses = data.STATUSES;
						data.STATUSES.forEach((statusRecord) =>
						{
							statusRecord.ITEMS = [];
							this.statuses.set(statusRecord.STATUS_ID, statusRecord);
						});

						data.ITEMS.forEach((item) =>
						{
							let itemStatus = this.statuses.get(item.STATUS_ID);
							if (itemStatus)
							{
								itemStatus.ITEMS.push(item);
							}
						});
					}
					this.entityType = data.ENTITY_TYPE;
					let currentStatus = this.statuses.get(this.currentStatusId);
					if (currentStatus && currentStatus.ITEMS.length === 0)
					{
						this.currentStatusId = this.getNonEmptyStatusId();
						this.currentItemIndex = 0;
					}
					next();
				}
				else
				{
					console.log(data);
				}
			}
		});
	};

	selectItem(statusId, newIndex)
	{
		let currentNode = this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex]._node;
		BX.removeClass(currentNode, 'im-phone-call-list-customer-block-active');

		if (this.itemActionMenu)
		{
			this.itemActionMenu.close();
		}

		this.currentStatusId = statusId;
		this.currentItemIndex = newIndex;

		currentNode = this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex]._node;
		BX.addClass(currentNode, 'im-phone-call-list-customer-block-active');

		const newEntity = this.statuses.get(statusId).ITEMS[newIndex];

		if ((this.entityType == 'DEAL' || this.entityType == 'QUOTE' || this.entityType == 'INVOICE') && newEntity.ASSOCIATED_ENTITY)
		{
			this.callbacks.onSelectedItem({
				type: newEntity.ASSOCIATED_ENTITY.TYPE,
				id: newEntity.ASSOCIATED_ENTITY.ID,
				bindings: [
					{
						type: this.entityType,
						id: newEntity.ELEMENT_ID
					}
				],
				phones: newEntity.ASSOCIATED_ENTITY.PHONES,
				statusId: statusId,
				index: newIndex
			});
		}
		else
		{
			this.callbacks.onSelectedItem({
				type: this.entityType,
				id: newEntity.ELEMENT_ID,
				phones: newEntity.PHONES,
				statusId: statusId,
				index: newIndex
			});
		}
	};

	moveToNextItem()
	{
		var newIndex = this.currentItemIndex + 1;
		if (newIndex >= this.statuses.get(this.currentStatusId).ITEMS.length)
		{
			newIndex = 0;
		}

		this.selectItem(this.currentStatusId, newIndex);
	};

	setCallingElement(statusId, index)
	{
		this.callingStatusId = statusId;
		this.callingItemIndex = index;
		const currentNode = this.statuses.get(this.callingStatusId).ITEMS[this.callingItemIndex]._node;
		BX.addClass(currentNode, 'im-phone-call-list-customer-block-calling');
		this.selectionLocked = true;
	};

	resetCallingElement()
	{
		if (this.callingStatusId === null || this.callingItemIndex === null)
		{
			return;
		}

		const currentNode = this.statuses.get(this.callingStatusId).ITEMS[this.callingItemIndex]._node;
		BX.removeClass(currentNode, 'im-phone-call-list-customer-block-calling');
		this.callingStatusId = null;
		this.callingItemIndex = null;
		this.selectionLocked = false;
	};

	update()
	{
		Dom.clean(this.node)
		this.node.append(this.render())
	}

	render(): HTMLElement
	{
		return Dom.create("div", {
			props: {className: 'im-phone-call-list-container'},
			children: this.renderStatusBlocks()
		});
	};

	renderStatusBlocks(): HTMLElement[]
	{
		let result = [];

		for (let [statusId, status] of this.statuses)
		{
			if (!status || status.ITEMS.length === 0)
			{
				continue;
			}

			status._node = this.renderStatusBlock(status);
			result.push(status._node);
		}
		return result;
	};

	renderCallListItems(statusId): HTMLElement[]
	{
		let result = [];
		const status = this.statuses.get(statusId);

		if (status._shownCount > 0)
		{
			if (status._shownCount > status.ITEMS.length)
			{
				status._shownCount = status.ITEMS.length;
			}
		}
		else
		{
			status._shownCount = Math.min(this.showLimit, status.ITEMS.length);
		}

		for (let i = 0; i < status._shownCount; i++)
		{
			result.push(this.renderCallListItem(status.ITEMS[i], statusId, i));
		}

		if (status.ITEMS.length > status._shownCount)
		{
			status._showMoreNode = Dom.create("div", {
				props: {className: 'im-phone-call-list-show-more-wrap'}, children: [
					Dom.create("span", {
						props: {className: 'im-phone-call-list-show-more-button'},
						dataset: {statusId: statusId},
						text: Loc.getMessage('IM_PHONE_CALL_LIST_MORE').replace('#COUNT#', (status.ITEMS.length - status._shownCount)),
						events: {click: this.onShowMoreClick.bind(this)}
					})
				]
			});
			result.push(status._showMoreNode);
		}
		else
		{
			status._showMoreNode = null;
		}

		return result;
	};

	renderCallListItem(itemDescriptor, statusId, itemIndex)
	{
		const statusName = this.statuses.get(statusId).NAME;

		let phonesText = '';
		if (Type.isArray(itemDescriptor.PHONES))
		{
			itemDescriptor.PHONES.forEach((phone, index) =>
			{
				if (index !== 0)
				{
					phonesText += '; ';
				}

				phonesText += Text.encode(phone.VALUE);
			})
		}

		itemDescriptor._node = Dom.create("div", {
			props: {className: (this.currentStatusId == statusId && this.currentItemIndex == itemIndex ? 'im-phone-call-list-customer-block im-phone-call-list-customer-block-active' : 'im-phone-call-list-customer-block')},
			children: [
				Dom.create("div", {
					props: {className: 'im-phone-call-list-customer-block-action'},
					children: [Dom.create("span", {text: statusName})],
					events: {
						click: (e) =>
						{
							e.preventDefault();
							if (this.itemActionMenu)
							{
								this.itemActionMenu.close();
							}
							else
							{
								this.showItemMenu(itemDescriptor, e.target);
							}
						}
					}
				}),
				Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-name' + (itemDescriptor.ASSOCIATED_ENTITY ? ' im-phone-call-list-connection-line' : '')},
					children: [
						Dom.create("a", {
							attrs: {href: itemDescriptor.EDIT_URL, target: '_blank'},
							props: {className: 'im-phone-call-list-item-customer-link'},
							text: itemDescriptor.NAME,
							events: {
								click: (e) =>
								{
									e.preventDefault();
									window.open(itemDescriptor.EDIT_URL);
								}
							}
						})
					]
				}),

				(itemDescriptor.POST ? Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: itemDescriptor.POST
				}) : null),
				(itemDescriptor.COMPANY_TITLE ? Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: itemDescriptor.COMPANY_TITLE
				}) : null),
				(phonesText ? Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: phonesText
				}) : null),
				(itemDescriptor.ASSOCIATED_ENTITY ? this.renderAssociatedEntity(itemDescriptor.ASSOCIATED_ENTITY) : null)
			],
			events: {
				click: () =>
				{
					if (!this.selectionLocked && (this.currentStatusId != itemDescriptor.STATUS_ID || this.currentItemIndex != itemIndex))
					{
						this.selectItem(itemDescriptor.STATUS_ID, itemIndex);
					}
				}
			}
		});

		return itemDescriptor._node;
	};

	renderAssociatedEntity(associatedEntity)
	{
		let phonesText = '';
		if (Type.isArray(associatedEntity.PHONES))
		{
			associatedEntity.PHONES.forEach((phone, index) =>
			{
				if (index !== 0)
				{
					phonesText += '; ';
				}

				phonesText += Text.encode(phone.VALUE);
			})
		}

		return Dom.create("div", {
			props: {className: 'im-phone-call-list-item-customer-entity im-phone-call-list-connection-line-item'},
			children: [
				Dom.create("a", {
					attrs: {href: associatedEntity.EDIT_URL, target: '_blank'},
					props: {className: 'im-phone-call-list-item-customer-link'},
					text: associatedEntity.NAME,
					events: {
						click: (e) =>
						{
							e.preventDefault();
							window.open(associatedEntity.EDIT_URL);
						}
					}
				}),
				Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: associatedEntity.POST
				}),
				Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: associatedEntity.COMPANY_TITLE
				}),
				(phonesText ? Dom.create("div", {
					props: {className: 'im-phone-call-list-item-customer-info'},
					text: phonesText
				}) : null)
			]
		});
	};

	onShowMoreClick(e)
	{
		var statusId = e.target.dataset.statusId;
		var status = this.statuses.get(statusId);

		status._shownCount += this.showDelta;
		if (status._shownCount > status.ITEMS.length)
		{
			status._shownCount = status.ITEMS.length;
		}

		const newStatusNode = this.renderStatusBlock(status);
		status._node.parentNode.replaceChild(newStatusNode, status._node);
		status._node = newStatusNode;
	};

	showItemMenu(callListItem, node)
	{
		let menuItems = [];
		let menuItem;
		for (let [statusId, status] of this.statuses)
		{
			menuItem = {
				id: "setStatus_" + statusId,
				text: status.NAME,
				onclick: this.actionMenuItemClickHandler(callListItem.ELEMENT_ID, statusId).bind(this)
			};
			menuItems.push(menuItem);
		}
		menuItems.push({
			id: 'callListItemActionMenu_delimiter',
			delimiter: true

		});
		menuItems.push({
			id: "defer15min",
			text: Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_DEFER_15_MIN'),
			onclick: () =>
			{
				this.itemActionMenu.close();
				this.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 35);
			}
		});
		menuItems.push({
			id: "defer1hour",
			text: Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_DEFER_HOUR'),
			onclick: () =>
			{
				this.itemActionMenu.close();
				this.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 185);
			}
		});
		menuItems.push({
			id: "moveToEnd",
			text: Loc.getMessage('IM_PHONE_CALL_VIEW_CALL_LIST_TO_END'),
			onclick: () =>
			{
				this.itemActionMenu.close();
				this.setElementRank(callListItem.ELEMENT_ID, callListItem.RANK + 5100);
			}
		});

		this.itemActionMenu = new Menu(
			'callListItemActionMenu',
			node,
			menuItems,
			{
				autoHide: true,
				offsetTop: 0,
				offsetLeft: 0,
				angle: {position: "top"},
				zIndex: baseZIndex + 200,
				events: {
					onPopupClose: () => this.itemActionMenu.destroy(),
					onPopupDestroy: () => this.itemActionMenu = null
				}
			}
		);
		this.itemActionMenu.show();
	};

	actionMenuItemClickHandler(elementId, statusId)
	{
		return () =>
		{
			this.itemActionMenu.close();
			this.setElementStatus(elementId, statusId);
		}
	};

	setElementRank(elementId, rank)
	{
		this.executeItemAction({
			action: 'SET_ELEMENT_RANK',
			parameters: {
				callListId: this.id,
				elementId: elementId,
				rank: rank
			},
			successCallback: (data) =>
			{
				if (data.ITEMS)
				{
					this.repopulateItems(data.ITEMS);
					this.update();
				}
			}
		});
	};

	setElementStatus(elementId, statusId)
	{
		this.executeItemAction({
			action: 'SET_ELEMENT_STATUS',
			parameters: {
				callListId: this.id,
				elementId: elementId,
				statusId: statusId
			},
			successCallback: (data) =>
			{
				this.repopulateItems(data.ITEMS);
				this.update();
			}
		})
	};

	/**
	 * @param {int} elementId
	 * @param {int} webformResultId
	 */
	setWebformResult(elementId, webformResultId)
	{
		this.executeItemAction({
			action: 'SET_WEBFORM_RESULT',
			parameters: {
				callListId: this.id,
				elementId: elementId,
				webformResultId: webformResultId
			}
		})
	};

	executeItemAction(params)
	{
		if (!Type.isPlainObject(params))
		{
			params = {};
		}

		if (!Type.isFunction(params.successCallback))
		{
			params.successCallback = nop;
		}

		var requestParams = {
			'sessid': BX.bitrix_sessid(),
			'ajax_action': params.action,
			'parameters': params.parameters
		};

		BX.ajax({
			url: CallList.getAjaxUrl(),
			method: 'POST',
			dataType: 'json',
			data: requestParams,
			onsuccess: (data) => params.successCallback(data)
		});
	};

	repopulateItems(items)
	{
		for (let [statusId, status] of this.statuses)
		{
			status.ITEMS = [];
		}

		items.forEach((item) => this.statuses.get(item.STATUS_ID).ITEMS.push(item));

		if (this.statuses.get(this.currentStatusId).ITEMS.length === 0)
		{
			this.currentStatusId = this.getNonEmptyStatusId();
			this.currentItemIndex = 0;
		}
		else
		{
			if (this.currentItemIndex >= this.statuses.get(this.currentStatusId).ITEMS.length)
			{
				this.currentItemIndex = 0;
			}
		}

		this.selectItem(this.currentStatusId, this.currentItemIndex);
	};

	getNonEmptyStatusId()
	{
		let foundStatusId = false;

		for (let [statusId, status] of this.statuses)
		{
			if (status.ITEMS.length > 0)
			{
				foundStatusId = statusId;
				break;
			}
		}
		return foundStatusId;
	};

	getCurrentElement()
	{
		return this.statuses.get(this.currentStatusId).ITEMS[this.currentItemIndex];
	};

	getStatusTitle(statusId)
	{
		const count = this.statuses.get(statusId).ITEMS.length;

		return Text.encode(this.statuses.get(statusId).NAME) + ' (' + count.toString() + ')';
	};

	renderStatusBlock(status)
	{
		let animationTimeout;
		let itemsNode;
		let measuringNode;
		const statusId = status.STATUS_ID;

		if (!status.hasOwnProperty('_folded'))
		{
			status._folded = false;
		}

		let className = 'im-phone-call-list-block';

		if (status.CLASS != '')
		{
			className = className + ' ' + status.CLASS;
		}

		return Dom.create("div", {
			props: {className: className}, children: [
				Dom.create("div", {
					props: {className: 'im-phone-call-list-block-title' + (status._folded ? '' : ' active')},
					children: [
						Dom.create("span", {text: this.getStatusTitle(statusId)}),
						Dom.create("div", {props: {className: 'im-phone-call-list-block-title-arrow'}})
					],
					events: {
						click: (e: Event) =>
						{
							e.preventDefault()
							clearTimeout(animationTimeout);
							status._folded = !status._folded;
							if (status._folded)
							{
								BX.removeClass(e.target, 'active');
								itemsNode.style.height = measuringNode.clientHeight.toString() + 'px';
								animationTimeout = setTimeout(function ()
								{
									itemsNode.style.height = 0;
								}, 50);
							}
							else
							{
								BX.addClass(e.target, 'active');
								itemsNode.style.height = 0;
								animationTimeout = setTimeout(function ()
								{
									itemsNode.style.height = measuringNode.clientHeight + 'px';
								}, 50);
							}
						}
					}
				}),
				itemsNode = Dom.create("div", {
					props: {className: 'im-phone-call-list-items-block'},
					children: [
						measuringNode = Dom.create("div", {
							props: {className: 'im-phone-call-list-items-measuring'},
							children: this.renderCallListItems(statusId)
						})
					],
					events: {
						transitionend: () =>
						{
							if (!status._folded)
							{
								itemsNode.style.removeProperty('height');
							}
						}
					}
				})
			]
		});
	}

	static getAjaxUrl(): string
	{
		return (this.isDesktop ? '/desktop_app/call_list.ajax.php' : '/bitrix/components/bitrix/crm.activity.call_list/ajax.php')
	}
}
