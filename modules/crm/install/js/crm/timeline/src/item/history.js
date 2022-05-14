import Item from "../item";
import {Item as ItemType} from "../types";
import Fasten from "../animation/fasten";

/** @memberof BX.Crm.Timeline.Items */
export default class History extends Item
{
	constructor()
	{
		super();
		this._history = null;
		this._fixedHistory = null;
		this._typeId = null;
		this._createdTime = null;
		this._isFixed = false;
		this._headerClickHandler = BX.delegate(this.onHeaderClick, this);
	}

	doInitialize()
	{
		this._history = this.getSetting("history");
		this._fixedHistory = this.getSetting("fixedHistory");
	}

	getTypeId()
	{
		if(this._typeId === null)
		{
			this._typeId = BX.prop.getInteger(this._data, "TYPE_ID", ItemType.undefined);
		}
		return this._typeId;
	}

	getTitle()
	{
		return "";
	}

	isContextMenuEnabled()
	{
		return !(this.isReadOnly());
	}

	getCreatedTimestamp()
	{
		return this.getTextDataParam("CREATED_SERVER");
	}

	getCreatedTime()
	{
		if(this._createdTime === null)
		{
			const time = BX.parseDate(
				this.getCreatedTimestamp(),
				false,
				"YYYY-MM-DD",
				"YYYY-MM-DD HH:MI:SS"
			);

			this._createdTime = new Date(time.getTime() + 1000 * Item.getUserTimezoneOffset());
		}
		return this._createdTime;
	}

	getCreatedDate()
	{
		return BX.prop.extractDate(new Date(this.getCreatedTime().getTime()));
	}

	getOwnerInfo()
	{
		return this._history ? this._history.getOwnerInfo() : null;
	}

	getOwnerTypeId()
	{
		return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_TYPE_ID", BX.CrmEntityType.enumeration.undefined);
	}

	getOwnerId()
	{
		return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_ID", 0);
	}

	isReadOnly()
	{
		return this._history.isReadOnly();
	}

	isEditable()
	{
		return !this.isReadOnly();
	}

	isDone()
	{
		const typeId = this.getTypeId();
		if(typeId === ItemType.activity)
		{
			const entityData = this.getAssociatedEntityData();
			return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
		}
		return false;
	}

	isFixed()
	{
		return this._isFixed;
	}

	fasten(e)
	{
		if (this._fixedHistory._items.length >= 3)
		{
			if (!this.fastenLimitPopup)
			{
				this.fastenLimitPopup = new BX.PopupWindow(
					'timeline_fasten_limit_popup_' + this._id,
					this._switcher,
					{
						content: BX.message('CRM_TIMELINE_FASTEN_LIMIT_MESSAGE'),
						darkMode: true,
						autoHide: true,
						zIndex: 990,
						angle: true,
						closeByEsc: true,
						bindOptions: { forceBindPosition: true}
					}
				);
			}

			this.fastenLimitPopup.show();
			this.closeContextMenu();
			return;
		}
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "CHANGE_FASTEN_ITEM",
						"VALUE": 'Y',
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this._id
					},
				onsuccess: BX.delegate(this.onSuccessFasten, this)
			}
		);

		this.closeContextMenu();
	}

	onSuccessFasten(result)
	{
		if (BX.type.isNotEmptyString(result.ERROR))
			return;

		if (!this.isFixed())
		{
			this._data.IS_FIXED = 'Y';
			const fixedItem = this._fixedHistory.createItem(this._data);
			fixedItem._isFixed = true;
			this._fixedHistory.addItem(fixedItem, 0);
			fixedItem.layout({ add: false });
			this.refreshLayout();
			const animation = Fasten.create(
				"",
				{
					initialItem: this,
					finalItem: fixedItem,
					anchor: this._fixedHistory._anchor
				}
			);
			animation.run();
		}

		this.closeContextMenu();
	}

	onFinishFasten(e)
	{
	}

	unfasten(e)
	{
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "CHANGE_FASTEN_ITEM",
						"VALUE": 'N',
						"OWNER_TYPE_ID": this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this._id
					},
				onsuccess: BX.delegate(this.onSuccessUnfasten, this)
			}
		);

		this.closeContextMenu();
	}

	onSuccessUnfasten(result)
	{
		if (BX.type.isNotEmptyString(result.ERROR))
			return;

		let item;
		let historyItem;

		if (this.isFixed())
		{
			item = this;
			historyItem = this._history.findItemById(this._id);
		}
		else
		{
			item = this._fixedHistory.findItemById(this._id);
			historyItem = this;
		}

		if (item)
		{
			const index = this._fixedHistory.getItemIndex(item);
			item.clearAnimate();
			this._fixedHistory.removeItemByIndex(index);
			if (historyItem)
			{
				historyItem._data.IS_FIXED = 'N';
				historyItem.refreshLayout();
				BX.LazyLoad.showImages();
			}
		}
	}

	clearAnimate()
	{
		if (!BX.type.isDomNode(this._wrapper))
			return ;

		const wrapperPosition = BX.pos(this._wrapper);
		const hideEvent = new BX.easing({
			duration: 1000,
			start: {height: wrapperPosition.height, opacity: 1, marginBottom: 15},
			finish: {height: 0, opacity: 0, marginBottom: 0},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function (state) {
				this._wrapper.style.height = state.height + "px";
				this._wrapper.style.opacity = state.opacity;
				this._wrapper.style.marginBottom = state.marginBottom;
			}, this),
			complete: BX.proxy(function () {
				this.clearLayout();
			}, this)
		});

		hideEvent.animate();
	}

	getWrapperClassName()
	{
		return "";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	}

	prepareContentDetails()
	{
		return [];
	}

	prepareContent()
	{
		let wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
		}
		const wrapper = BX.create("DIV", {attrs: {className: wrapperClassName}});
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children:
					[
						BX.create("DIV",
							{
								attrs: {className: "crm-entity-stream-content-event-title"},
								children:
									[
										BX.create("A",
											{
												attrs: {href: "#"},
												events: {click: this._headerClickHandler},
												text: this.getTitle()
											}
										)
									]
							}
						),
						BX.create("SPAN",
							{
								attrs: {className: "crm-entity-stream-content-event-time"},
								text: this.formatTime(this.getCreatedTime())
							}
						)
					]
			}
		);

		contentWrapper.appendChild(header);

		contentWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: this.prepareContentDetails()
				}
			)
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	}

	prepareLayout(options)
	{
		const vueComponent = this.makeVueComponent(options, 'history');
		this._wrapper = vueComponent ? vueComponent : this.prepareContent();
		if(this._wrapper)
		{
			const enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
			if(enableAdd)
			{
				const anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
				if(anchor && anchor.nextSibling)
				{
					this._container.insertBefore(this._wrapper,  anchor.nextSibling);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}
			}

			this.markAsTerminated(this._history.checkItemForTermination(this));
		}
	}

	onHeaderClick(e)
	{
		this.view();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	}

	prepareTitleLayout()
	{
		return BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-event-title" }, text: this.getTitle() });
	}

	prepareFixedSwitcherLayout()
	{
		const isFixed = (this.getTextDataParam("IS_FIXED") === 'Y');
		this._switcher = BX.create("span",
			{
				attrs: { className: "crm-entity-stream-section-top-fixed-btn" },
				events: {
					click: isFixed ? BX.delegate(this.unfasten, this) : BX.delegate(this.fasten, this)
				}
			});
		if (isFixed)
			BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-active");

		if (!this.isReadOnly() && !isFixed)
		{
			const manager = this._history.getManager();
			if (!manager.isSpotlightShowed())
			{
				manager.setSpotlightShowed();
				BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-spotlight");
				const spotlight = new BX.SpotLight({
					targetElement: this._switcher,
					targetVertex: "middle-center",
					lightMode: false,
					id: "CRM_TIMELINE_FASTEN_SWITCHER",
					zIndex: 900,
					top: -3,
					left: -1,
					autoSave: true,
					content: BX.message('CRM_TIMELINE_SPOTLIGHT_FASTEN_MESSAGE')
				});
				spotlight.show();
			}
		}

		return this._switcher;
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		);

		return header;
	}

	onActivityCreate(activity, data)
	{
		this._history.getManager().onActivityCreated(activity, data);
	}

	formatTime(time)
	{
		if (this.isFixed())
		{
			return this._fixedHistory.formatTime(time);
		}

		return this._history.formatTime(time);
	}

	static create(id, settings)
	{
		const self = new History();
		self.initialize(id, settings);
		return self;
	}

	static isCounterEnabled(deadline)
	{
		if(!BX.type.isDate(deadline))
		{
			return false;
		}

		let start = new Date();
		start.setHours(0);
		start.setMinutes(0);
		start.setSeconds(0);
		start.setMilliseconds(0);
		start = start.getTime();

		let end = new Date();
		end.setHours(23);
		end.setMinutes(59);
		end.setSeconds(59);
		end.setMilliseconds(999);
		end = end.getTime();

		const time = deadline.getTime();
		return time < start || (time >= start && time <= end);
	}
}
