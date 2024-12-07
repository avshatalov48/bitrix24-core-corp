/* eslint-disable */

import { Item } from 'crm.timeline.item';
import Expand from './animations/expand';
import Scheduled from './items/scheduled';
import PullActionProcessor from './pull-action-processor';
import EntityChat from './streams/entitychat';
import FixedHistory from './streams/fixedhistory';
import History from './streams/history';
import Schedule from './streams/schedule';
import AudioPlaybackRateSelector from './tools/audio-playback-rate-selector';

/** @memberof BX.Crm.Timeline */
export default class Manager
{
	#itemPullActionProcessor: PullActionProcessor = null;

	constructor()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._ownerTypeId = 0;
		this._ownerId = 0;
		this._ownerInfo = null;
		this._progressSemantics = "";

		this._chat = null;
		this._schedule = null;
		this._history = null;
		this._fixedHistory = null;
		this._activityEditor = null;

		this._userId = 0;
		this._readOnly = false;
		this._currentUser = null;
		this._pingSettings = null;
		this._calendarSettings = null;
		this._colorSettings = null;
		this._pullTagName = "";
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._settings = settings ? settings : {};

		this._ownerTypeId = parseInt(this.getSetting("ownerTypeId"));
		this._ownerId = parseInt(this.getSetting("ownerId"));
		this._ownerInfo = this.getSetting("ownerInfo");
		this._progressSemantics = BX.prop.getString(this._settings, "progressSemantics", "");
		this._spotlightFastenShowed = this.getSetting("spotlightFastenShowed", true);
		this._audioPlaybackRate = parseFloat(this.getSetting("audioPlaybackRate", 1));
		const containerId = this.getSetting("containerId");
		if (!BX.type.isNotEmptyString(containerId))
		{
			throw "Manager. A required parameter 'containerId' is missing.";
		}

		this._container = BX(containerId);
		if (!BX.type.isElementNode(this._container))
		{
			throw "Manager. Container node is not found.";
		}
		this._editorContainer = BX(this.getSetting("editorContainer"));

		this._userId = BX.prop.getInteger(this._settings, "userId", 0);
		this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
		this._currentUser = BX.prop.getObject(this._settings, "currentUser", null);
		this._pingSettings = BX.prop.getObject(this._settings, "pingSettings", null);
		this._calendarSettings = BX.prop.getObject(this._settings, "calendarSettings", null);
		this._colorSettings = BX.prop.getObject(this._settings, "colorSettings", null);

		const activityEditorId = this.getSetting("activityEditorId");
		if (BX.type.isNotEmptyString(activityEditorId))
		{
			this._activityEditor = BX.CrmActivityEditor.items[activityEditorId];
			if (!(this._activityEditor instanceof BX.CrmActivityEditor))
			{
				throw "BX.CrmTimeline. Activity editor instance is not found.";
			}
		}

		const ajaxId = this.getSetting("ajaxId");
		const currentUrl = this.getSetting("currentUrl");
		const serviceUrl = this.getSetting("serviceUrl");

		this._chat = EntityChat.create(
			this._id,
			{
				manager: this,
				container: this._container,
				data: this.getSetting("chatData"),
				isStubMode: this._ownerId <= 0,
				readOnly: this._readOnly
			}
		);

		this._schedule = Schedule.create(
			this._id,
			{
				manager: this,
				container: this._container,
				activityEditor: this._activityEditor,
				itemData: this.getSetting("scheduleData"),
				templates: this.getSetting("templates"),
				isStubMode: this._ownerId <= 0,
				ajaxId: ajaxId,
				serviceUrl: serviceUrl,
				currentUrl: currentUrl,
				userId: this._userId,
				readOnly: this._readOnly
			}
		);

		this._fixedHistory = FixedHistory.create(
			this._id,
			{
				manager: this,
				container: this._container,
				editorContainer: this._editorContainer,
				activityEditor: this._activityEditor,
				itemData: this.getSetting("fixedData"),
				templates: this.getSetting("templates"),
				isStubMode: this._ownerId <= 0,
				ajaxId: ajaxId,
				serviceUrl: serviceUrl,
				currentUrl: currentUrl,
				userId: this._userId,
				readOnly: this._readOnly
			}
		);

		this._history = History.create(
			this._id,
			{
				manager: this,
				container: this._container,
				fixedHistory: this._fixedHistory,
				activityEditor: this._activityEditor,
				itemData: this.getSetting("historyData"),
				templates: this.getSetting("templates"),
				navigation: this.getSetting("historyNavigation", {}),
				filterId: BX.prop.getString(this._settings, "historyFilterId", this._id),
				isFilterApplied: BX.prop.getBoolean(this._settings, "isHistoryFilterApplied", false),
				isStubMode: this._ownerId <= 0,
				ajaxId: ajaxId,
				serviceUrl: serviceUrl,
				currentUrl: currentUrl,
				userId: this._userId,
				readOnly: this._readOnly
			}
		);
		this._schedule.setHistory(this._history);
		this._fixedHistory.setHistory(this._history);

		this._chat.layout();
		this._schedule.layout();
		this._fixedHistory.layout();
		this._history.layout();

		this._pullTagName = BX.prop.getString(this._settings, "pullTagName", "");
		if (this._pullTagName !== "")
		{
			BX.addCustomEvent("onPullEvent-crm", BX.delegate(this.onPullEvent, this));
			this.extendWatch();
			this.#itemPullActionProcessor = new PullActionProcessor({
				scheduleStream: this._schedule,
				fixedHistoryStream: this._fixedHistory,
				historyStream: this._history,
				ownerTypeId: this._ownerTypeId,
				ownerId: this._ownerId,
				userId: this._userId,
			});
		}

		BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
		BX.ready(function () {
			window.addEventListener("scroll", BX.throttle(function () {
				BX.LazyLoad.onScroll();
			}, 80));
		});
	}

	extendWatch()
	{
		if (BX.type.isFunction(BX.PULL) && this._pullTagName !== "")
		{
			BX.PULL.extendWatch(this._pullTagName);
			window.setTimeout(BX.delegate(this.extendWatch, this), 60000);
		}
	}

	onPullEvent(command, params)
	{
		if (this._pullTagName !== BX.prop.getString(params, "TAG", ""))
		{
			return;
		}

		if (command === 'timeline_item_action')
		{
			this.#itemPullActionProcessor.processAction(params);

			return;
		}

		if (command === "timeline_chat_create")
		{
			this.processChatCreate(params);
		}
		else if (command === "timeline_activity_add")
		{
			this.processActivityExternalAdd(params);
		}
		else if (command === "timeline_activity_update")
		{
			this.processActivityExternalUpdate(params);
		}
		else if (command === "timeline_activity_delete")
		{
			this.processActivityExternalDelete(params);
		}
		else if (command === "timeline_comment_add")
		{
			this.processCommentExternalAdd(params);
		}
		else if (command === "timeline_comment_update")
		{
			this.processCommentExternalUpdate(params);
		}
		else if (command === "timeline_comment_delete")
		{
			this.processCommentExternalDelete(params);
		}
		else if (command === "timeline_changed_binding")
		{
			this.processChangeBinding(params);
		}
		else if (command === "timeline_item_update")
		{
			this.processItemExternalUpdate(params);
		}
		else if (command === "timeline_wait_add")
		{
			this.processWaitExternalAdd(params);
		}
		else if (command === "timeline_wait_update")
		{
			this.processWaitExternalUpdate(params);
		}
		else if (command === "timeline_wait_delete")
		{
			this.processWaitExternalDelete(params);
		}
		else if (command === "timeline_bizproc_status")
		{
			this.processBizprocStatus(params);
		}
		else if (command === "timeline_scoring_add")
		{
			this.processScoringExternalAdd(params);
		}
	}

	processChatCreate(params)
	{
		if (this._chat)
		{
			this._chat.setData(BX.prop.getObject(params, "CHAT_DATA", {}));
			this._chat.refreshLayout();
		}
	}

	processActivityExternalAdd(params)
	{
		let entityData, scheduleItemData, historyItemData, scheduleItem, historyItem;

		entityData = BX.prop.getObject(params, "ENTITY", null);
		scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);


		if (entityData && historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
		{
			historyItemData["ASSOCIATED_ENTITY"] = entityData;
		}

		if (scheduleItemData !== null && this._schedule.getItemByData(scheduleItemData) === null)
		{
			scheduleItem = this.addScheduleItem(scheduleItemData);
			scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
		}

		if (historyItemData !== null)
		{
			historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
			if (!historyItem)
			{
				historyItem = this.addHistoryItem(historyItemData);
				Expand.create(historyItem.getWrapper(), null).run();
			}
		}
	}

	processActivityExternalUpdate(params)
	{
		let entityData, scheduleItemData, scheduleItem, historyItemData, historyItem, fixedHistoryItem;

		entityData = BX.prop.getObject(params, "ENTITY", null);
		scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

		if (entityData)
		{
			if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
			{
				historyItemData["ASSOCIATED_ENTITY"] = entityData;
			}

			const entityId = BX.prop.getInteger(entityData, "ID", 0);
			const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
			for (let i = 0, length = historyItems.length; i < length; i++)
			{
				historyItem = historyItems[i];
				historyItem.setAssociatedEntityData(entityData);
				historyItem.refreshLayout();
			}
			const fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
			for (let i = 0, length = fixedHistoryItems.length; i < length; i++)
			{
				fixedHistoryItem = fixedHistoryItems[i];
				fixedHistoryItem.setAssociatedEntityData(entityData);
				fixedHistoryItem.refreshLayout();
			}
		}

		if (scheduleItemData !== null)
		{
			scheduleItem = this._schedule.getItemByAssociatedEntity(
				BX.CrmEntityType.enumeration.activity,
				BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID")
			);

			if (scheduleItem)
			{
				scheduleItem.setData(scheduleItemData);
				if (!scheduleItem.isDone())
				{
					this._schedule.refreshItem(scheduleItem);
				}
				else
				{
					if (historyItemData)
					{
						this._schedule.transferItemToHistory(
							scheduleItem,
							historyItemData
						);
						//History data are already processed
						historyItemData = null;
					}
					else
					{
						this._schedule.deleteItem(scheduleItem);
					}
				}
			}
			else if (!Scheduled.isDone(scheduleItemData))
			{
				scheduleItem = this.addScheduleItem(scheduleItemData);
				scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
			}
		}

		if (historyItemData !== null)
		{
			historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
			if (!historyItem)
			{
				historyItem = this.addHistoryItem(historyItemData);
				Expand.create(historyItem.getWrapper(), null).run();
			}
			else
			{
				historyItem.setData(historyItemData);
				historyItem.refreshLayout();
				fixedHistoryItem = this._fixedHistory.findItemById(BX.prop.getString(historyItemData, "ID"));
				if (fixedHistoryItem)
				{
					fixedHistoryItem.setData(historyItemData);
					fixedHistoryItem.refreshLayout()
				}
			}
		}
	}

	processActivityExternalDelete(params)
	{
		const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
		const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
		for (let i = 0, length = historyItems.length; i < length; i++)
		{
			this._history.deleteItem(historyItems[i]);
		}

		const fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
		for (let i = 0, length = fixedHistoryItems.length; i < length; i++)
		{
			this._fixedHistory.deleteItem(fixedHistoryItems[i]);
		}

		const scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
		if (scheduleItem)
		{
			this._schedule.deleteItem(scheduleItem);
		}
	}

	processCommentExternalAdd(params)
	{
		let historyItemData, historyItem;
		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
		if (historyItemData !== null)
		{
			window.setTimeout(
				BX.delegate(function () {
					if (!this._history.findItemById(historyItemData['ID']))
					{
						historyItem = this.addHistoryItem(historyItemData);
						Expand.create(historyItem.getWrapper(), null).run();
					}
				}, this),
				1500
			);
		}
	}

	processCommentExternalUpdate(params)
	{
		const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
		const historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
		const updateItem = this._history.findItemById(entityId);
		if (updateItem instanceof Comment && historyItemData !== null)
		{
			updateItem.setData(historyItemData);
			updateItem.switchToViewMode();
		}
		const updateFixedItem = this._fixedHistory.findItemById(entityId);
		if (updateFixedItem instanceof Comment && historyItemData !== null)
		{
			updateFixedItem.setData(historyItemData);
			updateFixedItem.switchToViewMode();
		}
	}

	processCommentExternalDelete(params)
	{
		const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
		window.setTimeout(
			BX.delegate(function () {
				const deleteItem = this._history.findItemById(entityId);
				if (deleteItem instanceof Comment)
				{
					this._history.deleteItem(deleteItem);
				}
				const deleteFixedItem = this._fixedHistory.findItemById(entityId);
				if (deleteFixedItem instanceof Comment)
				{
					this._fixedHistory.deleteItem(deleteFixedItem);
				}
			}, this),
			1200
		);
	}

	processChangeBinding(params)
	{
		const entityId = BX.prop.getString(params, "OLD_ID", 0);
		const entityNewId = BX.prop.getString(params, "NEW_ID", 0);
		const item = this._history.findItemById(entityId);
		if (item instanceof Item)
		{
			item._id = entityNewId;
			const itemData = item.getData();
			itemData.ID = entityNewId;
			item.setData(itemData);
		}

		const fixedItem = this._fixedHistory.findItemById(entityId);
		if (fixedItem instanceof Item)
		{
			fixedItem._id = entityNewId;
			const fixedItemData = fixedItem.getData();
			fixedItemData.ID = entityNewId;
			fixedItem.setData(fixedItemData);
		}
	}

	processItemExternalUpdate(params)
	{
		const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
		const historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
		const historyItem = this._history.findItemById(entityId);
		if (historyItem && historyItemData !== null)
		{
			historyItem.setData(historyItemData);
			historyItem.markAsTerminated(this._history.checkItemForTermination(historyItem));
			historyItem.refreshLayout();
			if (historyItem.isTerminated())
			{
				BX.addClass(historyItem._wrapper, "crm-entity-stream-section-last");
			}
		}
	}

	processWaitExternalAdd(params)
	{
		const scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
		if (scheduleItemData !== null)
		{
			this.addScheduleItem(scheduleItemData);
		}
	}

	processWaitExternalUpdate(params)
	{
		let entityData, scheduleItemData, scheduleItem, historyItemData, historyItem;

		entityData = BX.prop.getObject(params, "ENTITY", null);
		scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

		if (entityData)
		{
			if (historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
			{
				historyItemData["ASSOCIATED_ENTITY"] = entityData;
			}

			const entityId = BX.prop.getInteger(entityData, "ID", 0);
			const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
			let i = 0;
			const length = historyItems.length;
			for (; i < length; i++)
			{
				historyItem = historyItems[i];
				historyItem.setAssociatedEntityData(entityData);
				historyItem.refreshLayout();
			}
		}

		if (scheduleItemData !== null)
		{
			scheduleItem = this._schedule.getItemByAssociatedEntity(
				BX.CrmEntityType.enumeration.wait,
				BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID")
			);
			if (!scheduleItem)
			{
				this.addScheduleItem(scheduleItemData);
			}
			else
			{
				scheduleItem.setData(scheduleItemData);
				if (!scheduleItem.isDone())
				{
					this._schedule.refreshItem(scheduleItem);
				}
				else
				{
					if (historyItemData)
					{
						this._schedule.transferItemToHistory(
							scheduleItem,
							historyItemData
						);
						//History data are already processed
						historyItemData = null;
					}
					else
					{
						this._schedule.deleteItem(scheduleItem);
					}
				}
			}
		}

		if (historyItemData !== null)
		{
			historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
			if (!historyItem)
			{
				historyItem = this.addHistoryItem(historyItemData);
				Expand.create(historyItem.getWrapper(), null).run();
			}
			else
			{
				historyItem.setData(historyItemData);
				historyItem.refreshLayout();
			}
		}
	}

	processWaitExternalDelete(params)
	{
		const entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
		const historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
		let i = 0;
		const length = historyItems.length;
		for (; i < length; i++)
		{
			this._history.deleteItem(historyItems[i]);
		}

		const scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
		if (scheduleItem)
		{
			this._schedule.deleteItem(scheduleItem);
		}
	}

	processBizprocStatus(params)
	{
		let historyItemData, historyItem;

		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

		if (historyItemData !== null)
		{
			historyItem = this.addHistoryItem(historyItemData);
			Expand.create(historyItem.getWrapper(), null).run();
		}
	}

	processScoringExternalAdd(params)
	{
		let historyItemData, historyItem;
		historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
		if (historyItemData !== null)
		{
			historyItem = this.addHistoryItem(historyItemData);
			Expand.create(historyItem.getWrapper(), null).run();
		}
	}

	onEntityProgressChange(sender, eventArgs)
	{
		if (BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this._ownerTypeId
			|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this._ownerId
		)
		{
			return;
		}

		const semantics = BX.prop.getString(eventArgs, "semantics", "");
		if (semantics === this._progressSemantics)
		{
			return;
		}

		this._progressSemantics = semantics;
		this._schedule.refreshLayout();
	}

	getId()
	{
		return this._id;
	}

	getSetting(name, defaultval)
	{
		return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
	}

	getOwnerTypeId()
	{
		return this._ownerTypeId;
	}

	getOwnerId()
	{
		return this._ownerId;
	}

	getOwnerInfo()
	{
		return this._ownerInfo;
	}

	isStubCounterEnabled()
	{
		return false;
	}

	getSchedule()
	{
		return this._schedule;
	}

	getHistory()
	{
		return this._history;
	}

	getFixedHistory()
	{
		return this._fixedHistory;
	}

	processSheduleLayoutChange()
	{
	}

	processHistoryLayoutChange()
	{
		this._schedule.refreshLayout();
	}

	addScheduleItem(data)
	{
		const item = this._schedule.createItem(data);
		const index = this._schedule.calculateItemIndex(item);
		const anchor = this._schedule.createAnchor(index);
		this._schedule.addItem(item, index);
		item.layout({anchor: anchor});

		return item;
	}

	addHistoryItem(data)
	{
		const item = this._history.createItem(data);
		const index = this._history.calculateItemIndex(item);
		const historyAnchor = this._history.createAnchor(index);
		this._history.addItem(item, index);
		item.layout({anchor: historyAnchor});

		return item;
	}

	renderAudioDummy(durationText, onClick)
	{
		return BX.create("DIV", {
			attrs: {className: "crm-audio-cap-wrap-container"},
			children: [
				BX.create("DIV", {
					attrs: {className: "crm-audio-cap-wrap"},
					children:
						[
							BX.create("DIV", {
								attrs: {className: "crm-audio-cap-time"},
								text: durationText
							})
						],
					events: {click: onClick}
				})
			]
		});
	}

	loadMediaPlayer(id, filePath, mediaType, node, duration, options)
	{
		if (!duration)
		{
			duration = 0;
		}
		if (!options)
		{
			options = {};
		}
		const player = new BX.Fileman.Player(id, {
			sources: [
				{
					src: filePath,
					type: mediaType
				}
			],
			isAudio: !options.video,
			skin: options.hasOwnProperty('skin') ? options.skin : 'vjs-timeline_player-skin',
			width: options.width || 350,
			height: options.height || 30,
			duration: duration,
			playbackRate: options.playbackRate || null,
			onInit: function (player) {
				player.vjsPlayer.controlBar.removeChild('timeDivider');
				player.vjsPlayer.controlBar.removeChild('durationDisplay');
				player.vjsPlayer.controlBar.removeChild('fullscreenToggle');
				player.vjsPlayer.controlBar.addChild('timeDivider');
				player.vjsPlayer.controlBar.addChild('durationDisplay');
				if (!player.isPlaying())
				{
					player.play();
				}
			}
		});
		BX.cleanNode(node, false);
		node.appendChild(player.createElement());
		player.init();
		// todo remove this after player will be able to get float playbackRate
		if (options.playbackRate > 1)
		{
			player.vjsPlayer.playbackRate(options.playbackRate);
		}
		return player;
	}

	onActivityCreated(activity, data)
	{
		//Already processed in onPullEvent
	}

	isSpotlightShowed()
	{
		return this._spotlightFastenShowed;
	}

	setSpotlightShowed()
	{
		this._spotlightFastenShowed = true;
	}

	getCurrentUser(): ?Object
	{
		if (BX.type.isObjectLike(this._currentUser) && this._userId > 0)
		{
			this._currentUser.userId = this._userId;
		}

		return this._currentUser;
	}

	getPingSettings(): ?Object
	{
		if (BX.type.isObjectLike(this._pingSettings) && Object.keys(this._pingSettings).length > 0)
		{
			return this._pingSettings;
		}

		return null;
	}

	getCalendarSettings(): ?Object
	{
		if (BX.type.isObjectLike(this._calendarSettings) && Object.keys(this._calendarSettings).length > 0)
		{
			return this._calendarSettings;
		}

		return null;
	}

	getColorSettings(): ?Object
	{
		if (BX.type.isObjectLike(this._colorSettings) && Object.keys(this._colorSettings).length > 0)
		{
			return this._colorSettings;
		}

		return null;
	}

	getAudioPlaybackRateSelector()
	{
		if (!this.audioPlaybackRateSelector)
		{
			this.audioPlaybackRateSelector = new AudioPlaybackRateSelector({
				name: 'timeline_audio_playback',
				currentRate: this._audioPlaybackRate,
				availableRates: [
					{
						rate: 1,
						html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1')
							.replace('#RATE#', '<span class="crm-audio-cap-speed-param">1x</span>')
					},
					{
						rate: 1.5,
						html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_1.5')
							.replace('#RATE#', '<span class="crm-audio-cap-speed-param">1.5x</span>')
					},
					{
						rate: 2,
						html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_2')
							.replace('#RATE#', '<span class="crm-audio-cap-speed-param">2x</span>')
					},
					{
						rate: 3,
						html: BX.Loc.getMessage('CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_RATE_3')
							.replace('#RATE#', '<span class="crm-audio-cap-speed-param">3x</span>')
					}
				],
				textMessageCode: 'CRM_TIMELINE_PLAYBACK_RATE_SELECTOR_TEXT'
			});
		}

		return this.audioPlaybackRateSelector;
	}

	hasScheduledItems(): boolean
	{
		return this._schedule.getItems().length > 0;
	}

	static create(id, settings)
	{
		const self = new Manager();
		self.initialize(id, settings);
		Manager.instances[self.getId()] = self;
		return self;
	}

	static getDefault(): ?Manager
	{
		return Manager.#defaultInstance;
	}
	static setDefault(instance): void
	{
		Manager.#defaultInstance = instance;
	}

	static getById(id): ?Manager
	{
		return Manager.instances[id] || null;
	}

	static #defaultInstance = null;
	static instances = {};
}
