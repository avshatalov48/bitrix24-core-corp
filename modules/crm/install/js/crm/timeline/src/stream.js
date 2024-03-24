import { ConfigurableItem, Item, StreamType } from 'crm.timeline.item';
import { Dom, Type } from 'main.core';
import { DateTimeFormat, Timezone } from 'main.date';
import ItemAnimation from './animations/item';
import ItemNew from './animations/item-new';
import Manager from './manager';

/** @memberof BX.Crm.Timeline */
export default class Steam
{
	constructor()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._manager = null;
		this._activityEditor = null;

		this._timeFormat = "";
		this._year = 0;

		this._isStubMode = false;
		this._userId = 0;
		this._readOnly = false;
		this._streamType = StreamType.history;
		this._anchor = null;

		this._serviceUrl = "";
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._container = BX(this.getSetting("container"));
		if (!BX.type.isElementNode(this._container))
		{
			throw "Timeline. Container node is not found.";
		}
		this._editorContainer = BX(this.getSetting("editorContainer"));
		this._manager = this.getSetting("manager");
		if (!(this._manager instanceof Manager))
		{
			throw "Timeline. Manager instance is not found.";
		}

		//
		const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		const dateFormat = BX.message("FORMAT_DATE");
		this._timeFormat = BX.date.convertBitrixFormat(BX.util.trim(datetimeFormat.replace(dateFormat, "")));
		//
		this._year = (new Date()).getFullYear();

		this._activityEditor = this.getSetting("activityEditor");

		this._isStubMode = BX.prop.getBoolean(this._settings, "isStubMode", false);
		this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
		this._userId = BX.prop.getInteger(this._settings, "userId", 0);
		this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");

		this.doInitialize();
	}

	getId()
	{
		return this._id;
	}

	isScheduleStream(): boolean
	{
		return this.getStreamType() === StreamType.scheduled;
	}

	isFixedHistoryStream(): boolean
	{
		return this.getStreamType() === StreamType.pinned;
	}

	isHistoryStream(): boolean
	{
		return this.getStreamType() === StreamType.history;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	doInitialize()
	{
	}

	layout()
	{
	}

	isStubMode()
	{
		return this._isStubMode;
	}

	isReadOnly()
	{
		return this._readOnly;
	}

	getUserId()
	{
		return this._userId;
	}

	getServiceUrl()
	{
		return this._serviceUrl;
	}

	getAnchor(): ?HTMLElement
	{
		return this._anchor;
	}

	getStreamType() {
		return this._streamType;
	}

	refreshLayout()
	{
	}

	getManager()
	{
		return this._manager;
	}

	getOwnerInfo()
	{
		return this._manager.getOwnerInfo();
	}

	reload()
	{
		const currentUrl = this.getSetting("currentUrl");
		const ajaxId = this.getSetting("ajaxId");
		if (ajaxId !== "")
		{
			BX.ajax.insertToNode(BX.util.add_url_param(currentUrl, {bxajaxid: ajaxId}), "comp_" + ajaxId);
		}
		else
		{
			window.location = currentUrl;
		}
	}

	getUserTimezoneOffset()
	{
		return Timezone.Offset.USER_TO_SERVER;
	}

	getServerTimezoneOffset()
	{
		return Timezone.Offset.SERVER_TO_UTC;
	}

	// @todo replace by DatetimeConverter
	formatTime(time, now, utc)
	{
		return DateTimeFormat.format(this._timeFormat, time, now, utc);
	}

	// @todo replace by DatetimeConverter
	formatDate(date)
	{
		return (
			DateTimeFormat.format(
				[
					["today", "today"],
					["tommorow", "tommorow"],
					["yesterday", "yesterday"],
					["", (date.getFullYear() === this._year) ? DateTimeFormat.getFormat('DAY_MONTH_FORMAT') : DateTimeFormat.getFormat('LONG_DATE_FORMAT')],
				],
				date
			)
		);
	}

	cutOffText(text, length): string
	{
		if (!BX.type.isNumber(length))
		{
			length = 0;
		}

		if (length <= 0 || text.length <= length)
		{
			return text;
		}

		let offset = length - 1;
		const whitespaceOffset = text.substring(offset).search(/\s/i);
		if (whitespaceOffset > 0)
		{
			offset += whitespaceOffset;
		}
		return text.substring(0, offset) + "...";
	}

	getItems(): Array
	{
		return [];
	}

	/**
	 * @abstract
	 */
	setItems(items: Array): void
	{
		throw new Error('Stream.setItems() must be overridden');
	}

	getLastItem(): ?Item
	{
		const items = this.getItems();

		return items.length > 0 ? items[items.length - 1] : null;
	}

	findItemById(id): ?Item
	{
		id = id.toString();

		return this.getItems().find(item => item.getId() === id) || null;
	}

	getItemIndex(item: Item): number
	{
		return this.getItems().findIndex((currentItem) => (currentItem === item));
	}

	removeItemByIndex(index): void
	{
		const items = this.getItems();
		if (index < items.length)
		{
			items.splice(index, 1);
			this.setItems(items);
		}
	}

	/**
	 * @abstract
	 */
	createItem(data): Item
	{
		throw new Error('Stream.createItem() must be overridden');
	}

	createItemCopy(item: Item): Item
	{
		if (item instanceof ConfigurableItem)
		{
			return item.clone();
		}

		return this.createItem(item.getData());
	}

	refreshItem(item: Item, animateUpdate: boolean = true, animateMove: true): Promise
	{
		const index = this.getItemIndex(item);
		if(index < 0)
		{
			return Promise.resolve();
		}

		this.removeItemByIndex(index);

		let itemPositionChanged = false;
		let newIndex = 0;
		let newItem;
		if (this.isScheduleStream())
		{
			newItem = this.createItemCopy(item);
			newIndex = this.calculateItemIndex(newItem);

			itemPositionChanged = newIndex !== index;
		}

		if(!itemPositionChanged)
		{
			this.addItem(item, newIndex);
			item.refreshLayout();
			if (animateUpdate)
			{
				return this.animateItemAdding(item);
			}

			return Promise.resolve();
		}

		const anchor = this.createAnchor(newIndex);
		this.addItem(newItem, newIndex);
		if (animateMove)
		{
			newItem.layout({add: false});

			return new Promise((resolve) => {
				const animation = ItemAnimation.create(
					'',
					{
						initialItem: item,
						finalItem: newItem,
						anchor: anchor,
						events: {
							complete: () => {
								item.destroy();
								resolve();
							}
						}
					}
				);
				animation.run();
			});
		}
		else
		{
			newItem.layout({anchor: anchor});
			item.destroy();

			return Promise.resolve();
		}
	}

	calculateItemIndex(item: Item): Number
	{
		return 0;
	}

	createAnchor(index): HTMLElement
	{
		return null;
	}

	/**
	 * @abstract
	 */
	addItem(item: Item, index): void
	{
		throw new Error('Stream.addItem() must be overridden');
	}

	/**
	 * @abstract
	 */
	deleteItem(item: Item): void
	{
		throw new Error('Stream.deleteItem() must be overridden');
	}

	deleteItemAnimated(item: Item): void
	{
		if (!Type.isDomNode(item.getWrapper()))
		{
			this.deleteItem(item);

			return Promise.resolve();
		}

		return new Promise((resolve) => {
			const wrapperPosition = Dom.getPosition(item.getWrapper());

			if (Dom.hasClass(item.getWrapper(), 'crm-entity-stream-section-planned'))
			{
				Dom.style(item.getWrapper(), {
					animation: 'none',
					opacity: 1,
				});
			}

			const hideEvent = new BX.easing({
				duration: 1000,
				start: {height: wrapperPosition.height, opacity: 1, marginBottom: 15},
				finish: {height: 0, opacity: 0, marginBottom: 0},
				transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: (state) => {
					Dom.style(item.getWrapper(), {
						height:  state.height + 'px',
						opacity:  state.opacity,
						marginBottom:  state.marginBottom,
					});
				},
				complete: () => {
					this.deleteItem(item)
					resolve();
				}
			});

			hideEvent.animate();
		});
	}

	moveItemToStream(item: Item, destinationStream: Steam, destinationItem: Item): void
	{
		this.removeItemByIndex(this.getItemIndex(item));
		if (this.getItems().length > 0)
		{
			this.refreshLayout();
		}

		return new Promise((resolve) => {
			const animation = ItemNew.create(
				'',
				{
					initialItem: item,
					finalItem: destinationItem,
					anchor: destinationStream.createAnchor(),
					events: {complete: () => {
							this.refreshLayout();
							destinationStream.refreshLayout();
							resolve();
						}}
				}
			);
			animation.run();
		});
	}

	animateItemAdding(item): Promise
	{
		return Promise.resolve();
	}
}
