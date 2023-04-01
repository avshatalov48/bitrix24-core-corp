import Stream from "../stream";
import {Item as ItemType, Order as OrderType} from "../types";
import HistoryItem from "../items/history";
import Modification from "../items/modification";
import Conversion from "../items/conversion";
import HistoryActivity from "../items/history-activity";
import Email from "../items/email";
import Call from "../items/call";
import Meeting from "../items/meeting";
import Task from "../items/task";
import WebForm from "../items/webform";
import Request from "../items/request";
import OpenLine from "../items/openline";
import Rest from "../items/rest";
import Visit from "../items/visit";
import Zoom from "../items/zoom";
import ExternalNoticeStatusModification from "../items/external-notice-status-modification";
import ExternalNoticeModification from "../items/external-notice-modification";
import Creation from "../items/creation";
import Restoration from "../items/restoration";
import Link from "../items/link";
import Unlink from "../items/unlink";
import Mark from "../items/mark";
import Comment from "../items/comment";
import Wait from "../items/wait";
import Document from "../items/document";
import Sender from "../items/sender";
import Bizproc from "../items/bizproc";
import Scoring from "../items/scoring";
import {ConfigurableItem, StreamType} from "crm.timeline.item";
import Expand from "../animations/expand";

/** @memberof BX.Crm.Timeline.Streams */
export default class History extends Stream
{
	constructor()
	{
		super();
		this._items = [];
		this._wrapper = null;
		this._fixedHistory = null;
		this._emptySection = null;
		this._currentDaySection = null;
		this._lastDaySection = null;
		this._lastDate = null;
		this._anchor = null;
		this._history = this;
		this._enableLoading = false;
		this._navigation = null;
		this._scrollHandler = null;
		this._loadingWaiter = null;

		this._filterId = "";
		this._isFilterApplied = false;
		this._isFilterShown = false;

		this._isRequestRunning = false;

		this._filterButton = null;
		this._filterWrapper = null;
		this._filterResultStub = null;
	}

	doInitialize()
	{
		this._fixedHistory = this.getSetting("fixedHistory");
		this._ownerTypeId = this.getSetting("ownerTypeId");
		this._ownerId = this.getSetting("ownerId");
		this._serviceUrl = this.getSetting("serviceUrl", "");
		if (!this.isStubMode())
		{
			let itemData = this.getSetting("itemData");
			if (!BX.type.isArray(itemData))
			{
				itemData = [];
			}

			let i, length, item;
			for (i = 0, length = itemData.length; i < length; i++)
			{
				item = this.createItem(itemData[i]);
				if (item)
				{
					this._items.push(item);
				}
			}

			this._navigation = this.getSetting("navigation", {});

			this._filterWrapper = BX("timeline-filter");
			this._filterId = BX.prop.getString(this._settings, "filterId", this._id);
			this._isFilterShown = this._filterWrapper
				&& BX.hasClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
			this._isFilterApplied = BX.prop.getBoolean(this._settings, "isFilterApplied", false);

			BX.addCustomEvent("BX.Main.Filter:apply", this.onFilterApply.bind(this));
		}
	}

	layout()
	{
		this._wrapper = BX.create("DIV", {});
		this._container.appendChild(this._wrapper);

		const now = BX.prop.extractDate(new Date());
		let i, length, item;

		if (!this.isStubMode())
		{
			if (this._filterWrapper)
			{
				const closeFilterButton = this._filterWrapper.querySelector(".crm-entity-stream-filter-close");
				if (closeFilterButton)
				{
					BX.bind(closeFilterButton, "click", this.onFilterClose.bind(this));
				}
			}

			for (i = 0, length = this._items.length; i < length; i++)
			{
				item = this._items[i];
				item.setContainer(this._wrapper);

				const created = item.getCreatedDate();
				if (this._lastDate === null || this._lastDate.getTime() !== created.getTime())
				{
					this._lastDate = created;
					if (now.getTime() === created.getTime())
					{
						this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
						this._wrapper.appendChild(this._currentDaySection);
					}
					else
					{
						this._lastDaySection = this.createDaySection(this._lastDate);
						this._wrapper.appendChild(this._lastDaySection);
					}
				}

				item._lastDate = this._lastDate;

				item.layout();
			}

			this.enableLoading(this._items.length > 0);
			this.refreshLayout();
		}
		else
		{
			this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
			this._wrapper.appendChild(this._currentDaySection);

			this._wrapper.appendChild(
				BX.create(
					"DIV",
					{
						attrs: {className: "crm-entity-stream-section crm-entity-stream-section-createEntity crm-entity-stream-section-last"},
						children:
							[
								BX.create("DIV", {attrs: {className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info"}}),
								BX.create(
									"DIV",
									{
										attrs: {className: "crm-entity-stream-section-content"},
										children:
											[
												BX.create(
													"DIV",
													{
														attrs: {className: "crm-entity-stream-content-event"},
														children:
															[
																BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}}),
																BX.create(
																	"DIV",
																	{
																		attrs: {className: "crm-entity-stream-content-detail"},
																		text: BX.message("CRM_TIMELINE_HISTORY_STUB")
																	}
																)
															]
													}
												)
											]
									}
								)
							]
					}
				)
			);
		}

		this._manager.processHistoryLayoutChange();
	}

	refreshLayout()
	{
		if (this._filterWrapper)
		{
			if (this._wrapper.firstChild && this._filterWrapper !== this._wrapper.firstChild)
			{
				this._wrapper.insertBefore(this._filterWrapper, this._wrapper.firstChild);
			}
			else if (!this._wrapper.firstChild && this._filterWrapper.parentNode !== this._wrapper)
			{
				this._wrapper.appendChild(this._filterWrapper);
			}
		}

		this.adjustFilterButton();

		const length = this._items.length;
		if (length === 0 && this._isFilterApplied)
		{
			if (!this._filterEmptyResultSection)
			{
				this._filterEmptyResultSection = this.createFilterEmptyResultSection();
			}
			this._wrapper.appendChild(this._filterEmptyResultSection);

			return;
		}

		if (this._filterEmptyResultSection)
		{
			this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
		}

		if (length === 0)
		{
			return;
		}

		for (let i = 0; i < (length - 1); i++)
		{
			const item = this._items[i];
			if (item.isTerminated())
			{
				item.markAsTerminated(false);
			}
		}

		this._items[length - 1].markAsTerminated(true);
	}

	calculateItemIndex(item)
	{
		return 0;
	}

	checkItemForTermination(item)
	{
		return this.getLastItem() === item;
	}

	hasContent()
	{
		return (this._items.length > 0 || this._isFilterApplied || this._isStubMode);
	}

	getItems(): Array
	{
		return this._items;
	}

	setItems(items: Array): void
	{
		this._items = items;
	}

	getItemByIndex(index)
	{
		return index < this._items.length ? this._items[index] : null;
	}

	getItemCount()
	{
		return this._items.length;
	}

	getItemsByAssociatedEntity($entityTypeId, entityId)
	{
		if (!BX.type.isNumber($entityTypeId))
		{
			$entityTypeId = parseInt($entityTypeId);
		}

		if (!BX.type.isNumber(entityId))
		{
			entityId = parseInt(entityId);
		}

		if (isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
		{
			return [];
		}

		const results = [];
		for (let i = 0, l = this._items.length; i < l; i++)
		{
			const item = this._items[i];
			if (item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId)
			{
				results.push(item);
			}
		}
		return results;
	}

	createFilterEmptyResultSection()
	{
		return BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-filter-empty"},
				children:
					[
						BX.create("DIV",
							{
								attrs: {className: "crm-entity-stream-section-content"},
								children:
									[
										BX.create("DIV",
											{
												attrs: {className: "crm-entity-stream-filter-empty"},
												children:
													[
														BX.create("DIV",
															{
																attrs: {className: "crm-entity-stream-filter-empty-img"}
															}
														),
														BX.create("DIV",
															{
																attrs: {className: "crm-entity-stream-filter-empty-text"},
																text: this.getMessage("filterEmptyResultStub")
															}
														)
													]
											}
										)
									]
							}
						)
					]
			}
		);
	}

	adjustFilterButton()
	{
		if (!this._filterWrapper)
		{
			return;
		}

		if (!this._isFilterShown && this._items.length === 0)
		{
			if (!this._emptySection)
			{
				this._emptySection = this.createEmptySection();
			}
			this._wrapper.insertBefore(this._emptySection, this._filterWrapper);
		}
		else if (this._emptySection)
		{
			this._emptySection = BX.remove(this._emptySection);
		}

		if (!this._filterButton)
		{
			this._filterButton = BX.create("BUTTON",
				{
					attrs: {className: "crm-entity-stream-filter-label"},
					text: this.getMessage("filterButtonCaption")
				}
			);

			BX.bind(this._filterButton, "click", function (e) {
				this.showFilter();
			}.bind(this));
		}

		const section = this._wrapper.querySelector(".crm-entity-stream-section-today-label, .crm-entity-stream-section-planned-label, .crm-entity-stream-section-history-label");
		if (section)
		{
			const sectionWrapper = section.querySelector(".crm-entity-stream-section-content");
			if (sectionWrapper)
			{
				if (this._filterButton.parentNode !== sectionWrapper)
				{
					sectionWrapper.appendChild(this._filterButton);
				}
			}
		}

		if (this._isFilterApplied)
		{
			BX.addClass(this._filterButton, "crm-entity-stream-filter-label-active");
		}
		else
		{
			BX.removeClass(this._filterButton, "crm-entity-stream-filter-label-active");
		}
	}

	showFilter(params)
	{
		if (!this._filterWrapper)
		{
			return;
		}

		BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");
		BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-show");

		this._isFilterShown = true;

		if (BX.prop.getBoolean(params, "enableAdjust", true))
		{
			this.adjustFilterButton();
		}
	}

	hideFilter(params)
	{
		if (!this._filterWrapper)
		{
			return;
		}

		BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
		BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");

		this._isFilterShown = false;

		if (BX.prop.getBoolean(params, "enableAdjust", true))
		{
			this.adjustFilterButton();
		}
	}

	onFilterClose(e)
	{
		this.hideFilter();

		window.setTimeout(
			function () {
				const filter = BX.Main.filterManager.getById(this._filterId);
				if (filter)
				{
					filter.resetFilter();
				}
			}.bind(this),
			500
		);
	}

	createEmptySection()
	{
		return BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-planned-label"},
				children: [BX.create("DIV", {attrs: {className: "crm-entity-stream-section-content"}})]
			}
		);
	}

	createCurrentDaySection()
	{
		let formattedDate = this.formatDate(BX.prop.extractDate(new Date()));
		formattedDate = formattedDate[0].toUpperCase() + formattedDate.substring(1);

		return BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-today-label"},
				children:
					[
						BX.create("DIV",
							{
								attrs: {className: "crm-entity-stream-section-content"},
								children:
									[
										BX.create("DIV",
											{
												attrs: {className: "crm-entity-stream-today-label"},
												text: formattedDate
											}
										)
									]
							}
						)
					]
			}
		);
	}

	createDaySection(date)
	{
		let formattedDate = this.formatDate(date);
		formattedDate = formattedDate[0].toUpperCase() + formattedDate.substring(1);

		return BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history-label"},
				children:
					[
						BX.create("DIV",
							{
								attrs: {className: "crm-entity-stream-section-content"},
								children:
									[
										BX.create("DIV",
											{
												attrs: {className: "crm-entity-stream-history-label"},
												text: formattedDate
											}
										)
									]
							}
						)
					]
			}
		);
	}

	createAnchor(index)
	{
		if (this._emptySection)
		{
			this._emptySection = BX.remove(this._emptySection);
		}

		if (this._currentDaySection === null)
		{
			this._currentDaySection = this.createCurrentDaySection();
			if (this._wrapper.firstChild)
			{
				this._wrapper.insertBefore(this._currentDaySection, this._wrapper.firstChild);
			}
			else
			{
				this._wrapper.appendChild(this._currentDaySection);
			}
		}

		if (this._anchor === null)
		{
			this._anchor = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-shadow"}});
			if (this._currentDaySection.nextSibling)
			{
				this._wrapper.insertBefore(this._anchor, this._currentDaySection.nextSibling);
			}
			else
			{
				this._wrapper.appendChild(this._anchor);
			}
		}
		return this._anchor;
	}

	createActivityItem(data)
	{
		const typeId = BX.prop.getInteger(data, "TYPE_ID", ItemType.undefined);
		const typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
		const providerId = BX.prop.getString(
			BX.prop.getObject(data, "ASSOCIATED_ENTITY", {}),
			"PROVIDER_ID",
			""
		);

		if (typeId !== ItemType.activity)
		{
			return null;
		}

		if (typeCategoryId === BX.CrmActivityType.email)
		{
			return Email.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		if (typeCategoryId === BX.CrmActivityType.call)
		{
			return Call.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeCategoryId === BX.CrmActivityType.meeting)
		{
			return Meeting.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeCategoryId === BX.CrmActivityType.task)
		{
			return Task.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeCategoryId === BX.CrmActivityType.provider)
		{
			if (providerId === "CRM_WEBFORM")
			{
				return WebForm.create(
					data["ID"],
					{
						history: this._history,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === 'CRM_REQUEST')
			{
				return Request.create(
					data["ID"],
					{
						history: this._history,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === "IMOPENLINES_SESSION")
			{
				return OpenLine.create(
					data["ID"],
					{
						history: this._history,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === 'REST_APP')
			{
				return Rest.create(
					data["ID"],
					{
						history: this._history,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === 'VISIT_TRACKER')
			{
				return Visit.create(
					data["ID"],
					{
						history: this,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === 'ZOOM')
			{
				return Zoom.create(
					data["ID"],
					{
						history: this,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if (providerId === 'CRM_CALL_TRACKER')
			{
				return Call.create(
					data["ID"],
					{
						history: this,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
		}

		return HistoryActivity.create(
			data["ID"],
			{
				history: this._history,
				fixedHistory: this._fixedHistory,
				container: this._wrapper,
				activityEditor: this._activityEditor,
				data: data,
			}
		);
	}

	createExternalNotificationItem(data)
	{
		const typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);
		const changedFieldName = BX.prop.getString(data, 'CHANGED_FIELD_NAME', '');

		if (typeId === ItemType.modification && changedFieldName === 'STATUS_ID')
		{
			return ExternalNoticeStatusModification.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}

		return ExternalNoticeModification.create(
			data["ID"],
			{
				history: this._history,
				container: this._wrapper,
				activityEditor: this._activityEditor,
				data: data
			}
		);
	}

	createItem(data)
	{
		if (data.hasOwnProperty('type'))
		{
			return ConfigurableItem.create(data.id, {
				timelineId: this.getId(),
				container: this.getWrapper(),
				itemClassName: this.getItemClassName(),
				useShortTimeFormat: this.getStreamType() === StreamType.history,
				isReadOnly: this.isReadOnly(),
				currentUser: this._manager.getCurrentUser(),
				ownerTypeId: this._manager.getOwnerTypeId(),
				ownerId: this._manager.getOwnerId(),
				streamType: this.getStreamType(),
				data: data,
			})
		}

		const typeId = BX.prop.getInteger(data, "TYPE_ID", ItemType.undefined);
		const typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

		if (typeId === ItemType.activity)
		{
			return this.createActivityItem(data);
		}
		else if (typeId === ItemType.externalNotification)
		{
			return this.createExternalNotificationItem(data);
		}
		else if (typeId === ItemType.creation)
		{
			return Creation.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.restoration)
		{
			return Restoration.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					data: data
				}
			);
		}
		else if (typeId === ItemType.link)
		{
			return Link.create(
				data["ID"],
				{
					history: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.unlink)
		{
			return Unlink.create(
				data["ID"],
				{
					history: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.mark)
		{
			return Mark.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					fixedHistory: this._fixedHistory,
					data: data
				}
			);
		}
		else if (typeId === ItemType.comment)
		{
			return Comment.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.wait)
		{
			return Wait.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.document)
		{
			return Document.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.sender)
		{
			return Sender.create(
				data["ID"],
				{
					history: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.modification)
		{
			return Modification.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.conversion)
		{
			return Conversion.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.bizproc)
		{
			return Bizproc.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if (typeId === ItemType.scoring)
		{
			return Scoring.create(
				data["ID"],
				{
					history: this._history,
					fixedHistory: this._fixedHistory,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}

		return HistoryItem.create(
			data["ID"],
			{
				history: this._history,
				fixedHistory: this._fixedHistory,
				container: this._wrapper,
				activityEditor: this._activityEditor,
				data: data
			}
		);
	}

	getWrapper()
	{
		return this._wrapper;
	}

	getItemClassName()
	{
		return 'crm-entity-stream-section crm-entity-stream-section-history';
	}

	addItem(item, index)
	{
		if (!BX.type.isNumber(index) || index < 0)
		{
			index = this.calculateItemIndex(item);
		}

		if (index < this._items.length)
		{
			this._items.splice(index, 0, item);
		}
		else
		{
			this._items.push(item);
		}

		this.refreshLayout();
		this._manager.processHistoryLayoutChange();
	}

	deleteItem(item)
	{
		const index = this.getItemIndex(item);
		if (index < 0)
		{
			return;
		}

		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processHistoryLayoutChange();
	}

	resetLayout()
	{
		let i;

		for (i = (this._items.length - 1); i >= 0; i--)
		{
			this._items[i].clearLayout();
		}

		this._items = [];

		this._currentDaySection = this._lastDaySection = this._emptySection = this._filterEmptyResultSection = null;
		this._anchor = null;
		this._lastDate = null;

		//Clean wrapper. Skip filter for prevent trembling.
		const children = [];
		let child;
		for (i = 0; child = this._wrapper.children[i]; i++)
		{
			if (child !== this._filterWrapper)
			{
				children.push(child);
			}
		}

		for (i = 0; child = children[i]; i++)
		{
			this._wrapper.removeChild(child);
		}
	}

	onWindowScroll(e)
	{
		if (!this._loadingWaiter || !this._enableLoading || this._isRequestRunning)
		{
			return;
		}

		const pos = this._loadingWaiter.getBoundingClientRect();
		if (pos.top <= document.documentElement.clientHeight)
		{
			this.loadItems();
		}
	}

	onFilterApply(id, data, ctx, promise, params)
	{
		if (id !== this._filterId)
		{
			return;
		}

		params.autoResolve = false;
		this._isFilterApplied = BX.prop.getString(data, "action", "") === "apply";
		this._isRequestRunning = true;

		BX.CrmDataLoader.create(
			this._id,
			{
				serviceUrl: this.getSetting("serviceUrl", ""),
				action: "GET_HISTORY_ITEMS",
				params:
					{
						"GUID": this._id,
						"OWNER_TYPE_ID": this._manager.getOwnerTypeId(),
						"OWNER_ID": this._manager.getOwnerId()
					}
			}
		).load(
			function (sender, result) {
				this.resetLayout();
				this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
				this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));

				this.refreshLayout();
				if (this._items.length > 0)
				{
					this._manager.processHistoryLayoutChange();
				}

				promise.fulfill();
				this._isRequestRunning = false;
			}.bind(this)
		);
	}

	bulkCreateItems(itemData)
	{
		const length = itemData.length;
		if (length === 0)
		{
			return;
		}

		if (this._filterEmptyResultSection)
		{
			this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
		}

		const now = BX.prop.extractDate(new Date());
		let i, item;
		for (i = 0; i < length; i++)
		{
			const itemId = BX.prop.getInteger(
				itemData[i],
				'id',
				BX.prop.getInteger(itemData[i], 'ID', 0)
			);

			if (itemId <= 0)
			{
				continue;
			}

			if (this.findItemById(itemId) !== null)
			{
				continue;
			}

			item = this.createItem(itemData[i]);
			this._items.push(item);

			const created = item.getCreatedDate();
			if (this._lastDate === null || this._lastDate.getTime() !== created.getTime())
			{
				this._lastDate = created;
				if (now.getTime() === created.getTime())
				{
					this._currentDaySection = this._lastDaySection = this.createCurrentDaySection();
					this._wrapper.appendChild(this._currentDaySection);
				}
				else
				{
					this._lastDaySection = this.createDaySection(this._lastDate);
					this._wrapper.appendChild(this._lastDaySection);
				}
			}
			item.layout();
		}
	}

	loadItems()
	{
		this._isRequestRunning = true;
		BX.CrmDataLoader.create(
			this._id,
			{
				serviceUrl: this.getSetting("serviceUrl", ""),
				action: "GET_HISTORY_ITEMS",
				params:
					{
						"GUID": this._id,
						"OWNER_TYPE_ID": this._manager.getOwnerTypeId(),
						"OWNER_ID": this._manager.getOwnerId(),
						"NAVIGATION": this._navigation
					}
			}
		).load(
			function (sender, result) {
				this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
				this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));

				this.refreshLayout();
				if (this._items.length > 0)
				{
					this._manager.processHistoryLayoutChange();
				}

				this._isRequestRunning = false;
			}.bind(this)
		);
	}

	getNavigation()
	{
		return this._navigation;
	}

	setNavigation(navigation)
	{
		if (!BX.type.isPlainObject(navigation))
		{
			navigation = {};
		}

		this._navigation = navigation;
		this.enableLoading(
			BX.prop.getString(this._navigation, "OFFSET_TIMESTAMP", "") !== ""
		);
	}

	isLoadingEnabled()
	{
		return this._enableLoading;
	}

	enableLoading(enable)
	{
		enable = !!enable;

		if (this._enableLoading === enable)
		{
			return;
		}

		this._enableLoading = enable;

		if (this._enableLoading)
		{
			if (this._items.length > 0)
			{
				this._loadingWaiter = this._items[this._items.length - 1].getWrapper();
			}

			if (!this._scrollHandler)
			{
				this._scrollHandler = BX.delegate(this.onWindowScroll, this);
				BX.bind(window, "scroll", this._scrollHandler);
			}
		}
		else
		{
			this._loadingWaiter = null;

			if (this._scrollHandler)
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this._scrollHandler = null;
			}
		}
	}

	getMessage(name)
	{
		const m = History.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	animateItemAdding(item): Promise
	{
		return new Promise((resolve) => {
			Expand.create(item.getWrapper(), resolve).run();
		});
	}

	static create(id, settings)
	{
		const self = new History();
		self.initialize(id, settings);
		History.instances[self.getId()] = self;
		return self;
	}

	static messages = {};
	static instances = {};
}
