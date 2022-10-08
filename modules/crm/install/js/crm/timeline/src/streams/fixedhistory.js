import History from "./history";
import {Item} from 'crm.timeline.item';
import CompatibleItem from "../items/compatible-item";
import {StreamType} from 'crm.timeline.item';

/** @memberof BX.Crm.Timeline.Streams */
export default class FixedHistory extends History
{
	constructor()
	{
		super();
		this._items = [];
		this._wrapper = null;
		this._fixedHistory = this;
		this._history = this;
		this._isRequestRunning = false;
	}

	doInitialize()
	{
		const datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		this._timeFormat = BX.date.convertBitrixFormat(datetimeFormat);

		let itemData = this.getSetting("itemData");
		if(!BX.type.isArray(itemData))
		{
			itemData = [];
		}

		let i, length, item;
		for(i = 0, length = itemData.length; i < length; i++)
		{
			item = this.createItem(itemData[i]);
			item._isFixed = true;
			this._items.push(item);
		}
	}

	setHistory(history)
	{
		this._history = history;
	}

	checkItemForTermination(item)
	{
		return false;
	}

	layout()
	{
		this._wrapper = BX.create("DIV", {});
		this.createAnchor();
		this._container.insertBefore(this._wrapper,  this._editorContainer.nextElementSibling);

		for (let i = 0; i < this._items.length; i++)
		{
			this._items[i].setContainer(this._wrapper);
			this._items[i].layout();
		}

		this.refreshLayout();

		this._manager.processHistoryLayoutChange();
	}

	refreshLayout()
	{
	}

	formatDate(date)
	{
	}

	createCurrentDaySection()
	{
	}

	createDaySection(date)
	{
	}

	createAnchor(index)
	{
		this._anchor = BX.create("DIV", { attrs:{className: "crm-entity-stream-section-fixed-anchor"} });
		this._wrapper.appendChild(this._anchor);
	}

	onWindowScroll(e)
	{
	}

	onItemsLoad(sender, result)
	{
	}

	loadItems()
	{
		this._isRequestRunning = true;

		BX.CrmDataLoader.create(
			this._id,
			{
				serviceUrl: this.getSetting("serviceUrl", ""),
				action: "GET_FIXED_HISTORY_ITEMS",
				params:
					{
						"OWNER_TYPE_ID" : this._manager.getOwnerTypeId(),
						"OWNER_ID": this._manager.getOwnerId(),
					}
			}
		).load(BX.delegate(this.onItemsLoad, this));
	}

	addItem(item: Item, index): void
	{
		super.addItem(item, index);

		if (item instanceof CompatibleItem)
		{
			item._isFixed = true;
		}
	}

	getItemClassName()
	{
		return 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-top-fixed';
	}

	getStreamType(): number
	{
		return StreamType.pinned;
	}

	static create(id, settings)
	{
		let self = new FixedHistory();
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

FixedHistory.instances = {};
