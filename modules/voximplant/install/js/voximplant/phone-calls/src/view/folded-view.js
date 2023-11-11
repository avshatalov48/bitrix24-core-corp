import {Dom, Loc, Text, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {CallList} from './call-list'

let avatars = {};

const Events = {
	onUnfold: "onUnfold"
}

export class FoldedCallView extends EventEmitter
{
	constructor(params)
	{
		super();
		this.setEventNamespace("BX.VoxImplant.FoldedCallView");
		this.subscribeFromOptions(params.events)

		this.currentItem = {};
		this.callListParams = {
			id: 0,
			webformId: 0,
			webformSecCode: '',
			itemIndex: 0,
			itemStatusId: '',
			statusList: {},
			entityType: ''
		};
		this.node = null;
		this.elements = {
			avatar: null,
			callButton: null,
			nextButton: null,
			unfoldButton: null
		};
		this._lsKey = 'bx-im-folded-call-view-data';
		this._lsTtl = 86400;
		this.init();
	}

	init()
	{
		this.load();
		if (this.callListParams.id > 0)
		{
			this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
			this.render();
		}
	};

	load()
	{
		var savedData = BX.localStorage.get(this._lsKey);
		if (Type.isPlainObject(savedData))
		{
			this.callListParams = savedData;
		}
	};

	destroy()
	{
		if (this.node)
		{
			Dom.remove(this.node);
			this.node = null;
		}

		BX.localStorage.remove(this._lsKey);
	};

	store()
	{
		BX.localStorage.set(this._lsKey, this.callListParams, this._lsTtl);
	};

	fold(params, animation)
	{
		animation = (animation === true);
		this.callListParams.id = params.callListId;
		this.callListParams.webformId = params.webformId;
		this.callListParams.webformSecCode = params.webformSecCode;
		this.callListParams.itemIndex = params.currentItemIndex;
		this.callListParams.itemStatusId = params.currentItemStatusId;
		this.callListParams.statusList = Object.fromEntries(params.statusList);
		this.callListParams.entityType = params.entityType;
		this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
		this.store();
		this.render(animation);
	};

	unfold(makeCall: ?boolean)
	{
		Dom.addClass(this.node, "im-phone-folded-call-view-unfold");
		this.node.addEventListener('animationend', () =>
		{
			if (this.node)
			{
				Dom.remove(this.node);
				this.node = null;
			}

			BX.localStorage.remove(this._lsKey);
			if (this.callListParams.id === 0)
			{
				return false;
			}

			let restoredParams = {};
			if (this.callListParams.webformId > 0 && this.callListParams.webformSecCode !== '')
			{
				restoredParams.webformId = this.callListParams.webformId;
				restoredParams.webformSecCode = this.callListParams.webformSecCode;
			}
			restoredParams.callListStatusId = this.callListParams.itemStatusId;
			restoredParams.callListItemIndex = this.callListParams.itemIndex;
			restoredParams.makeCall = makeCall;

			this.emit(Events.onUnfold, {
				callListId: this.callListParams.id,
				callListParams: restoredParams
			})
		});
	};

	moveToNext()
	{
		this.callListParams.itemIndex++;
		if (this.callListParams.itemIndex >= this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS.length)
		{
			this.callListParams.itemIndex = 0;
		}

		this.currentItem = this.callListParams.statusList[this.callListParams.itemStatusId].ITEMS[this.callListParams.itemIndex];
		this.store();
		this.render();
	};

	render(animation)
	{
		animation = (animation === true);
		if (this.node === null)
		{
			this.node = Dom.create("div", {
				props: {
					id: 'im-phone-folded-call-view',
					className: 'im-phone-call-wrapper im-phone-call-wrapper-fixed im-phone-call-panel'
				},
				events: {
					dblclick: this._onViewDblClick.bind(this)
				}
			});
			document.body.appendChild(this.node);
		}
		else
		{
			Dom.clean(this.node);
		}

		this.node.appendChild(Dom.create("div", {
			props: {className: 'im-phone-call-wrapper-fixed-left'},
			style: (animation ? {bottom: '-90px'} : {}),
			children: [
				Dom.create("div", {
					props: {className: 'im-phone-call-wrapper-fixed-user'}, children: [
						Dom.create("div", {
							props: {className: 'im-phone-call-wrapper-fixed-user-image'}, children: [
								this.elements.avatar = Dom.create("div", {props: {className: 'im-phone-call-wrapper-fixed-user-image-item'}})
							]
						}),
						Dom.create("div", {
							props: {className: 'im-phone-call-wrapper-fixed-user-info'},
							children: this.renderUserInfo()
						})
					]
				})
			]
		}));

		this.node.appendChild(Dom.create("div", {
			props: {className: 'im-phone-call-wrapper-fixed-right'}, children: [
				Dom.create("div", {
					props: {className: 'im-phone-call-wrapper-fixed-btn-container'}, children: [
						this.elements.callButton = Dom.create("span", {
							props: {className: 'im-phone-call-btn im-phone-call-btn-green'},
							text: Loc.getMessage('IM_PHONE_CALL_VIEW_FOLDED_BUTTON_CALL'),
							events: {
								click: this._onDialButtonClick.bind(this)
							}
						}),
						this.elements.nextButton = Dom.create("span", {
							props: {className: 'im-phone-call-btn im-phone-call-btn-gray im-phone-call-btn-arrow'},
							text: Loc.getMessage('IM_PHONE_CALL_VIEW_FOLDED_BUTTON_NEXT'),
							events: {
								click: this._onNextButtonClick.bind(this)
							}
						})
					]
				})
			]
		}));

		this.node.appendChild(Dom.create("div", {
				props: {className: 'im-phone-btn-block'}, children: [
					this.elements.unfoldButton = Dom.create("div", {
						props: {className: 'im-phone-btn-arrow'},
						children: [
							Dom.create("div", {
								props: {className: 'im-phone-btn-arrow-inner'},
								text: Loc.getMessage("IM_PHONE_CALL_VIEW_UNFOLD")
							})
						],
						events: {
							click: this._onUnfoldButtonClick.bind(this)
						}
					})
				]
			})
		);

		if (avatars[this.currentItem.ELEMENT_ID])
		{
			this.elements.avatar.style.backgroundImage = 'url(\'' + Text.encode(avatars[this.currentItem.ELEMENT_ID]) + '\')';
		}
		else
		{
			this.loadAvatar(this.callListParams.entityType, this.currentItem.ELEMENT_ID);
		}

		if (animation)
		{
			Dom.addClass(this.node, 'im-phone-folded-call-view-fold');
			this.node.addEventListener('animationend', function ()
			{
				BX.removeClass(this.node, 'im-phone-folded-call-view-fold');
			})
		}
	};

	renderUserInfo()
	{
		var result = [];

		result.push(Dom.create("div", {
			props: {className: 'im-phone-call-wrapper-fixed-user-name'},
			text: this.currentItem.NAME
		}));
		if (this.currentItem.POST)
		{
			result.push(Dom.create("div", {
				props: {className: 'im-phone-call-wrapper-fixed-user-item'},
				text: this.currentItem.POST
			}));
		}
		if (this.currentItem.COMPANY_TITLE)
		{
			result.push(Dom.create("div", {
				props: {className: 'im-phone-call-wrapper-fixed-user-item'},
				text: this.currentItem.COMPANY_TITLE
			}));
		}

		return result;
	};

	loadAvatar(entityType, entityId)
	{
		BX.ajax({
			url: CallList.getAjaxUrl(),
			method: 'POST',
			dataType: 'json',
			data: {
				'sessid': BX.bitrix_sessid(),
				'ajax_action': 'GET_AVATAR',
				'entityType': entityType,
				'entityId': entityId
			},
			onsuccess: (data) =>
			{
				if (!data.avatar)
				{
					return;
				}

				avatars[entityId] = data.avatar;
				if (this.currentItem.ELEMENT_ID == entityId && this.elements.avatar)
				{
					this.elements.avatar.style.backgroundImage = 'url(\'' + Text.encode(data.avatar) + '\')';
				}
			}
		});
	};

	_onViewDblClick(e)
	{
		e.preventDefault();
		this.unfold(false);
	};

	_onDialButtonClick(e)
	{
		e.preventDefault();
		this.unfold(true);
	};

	_onNextButtonClick(e)
	{
		e.preventDefault();
		this.moveToNext();
	};

	_onUnfoldButtonClick(e)
	{
		e.preventDefault();
		this.unfold(false);
	};

	static Events = Events;
}