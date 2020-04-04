if(typeof(BX.CrmTimelineManager) === "undefined")
{
	BX.CrmTimelineManager = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._ownerTypeId = 0;
		this._ownerId = 0;
		this._ownerInfo = null;
		this._progressSemantics = "";

		this._commentEditor = null;
		this._waitEditor = null;
		this._smsEditor = null;
		this._chat = null;
		this._schedule = null;
		this._history = null;
		this._fixedHistory = null;
		this._activityEditor = null;
		this._menuBar = null;

		this._userId = 0;
		this._readOnly = false;
		this._pullTagName = "";
	};
	BX.CrmTimelineManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._ownerTypeId = parseInt(this.getSetting("ownerTypeId"));
			this._ownerId = parseInt(this.getSetting("ownerId"));
			this._ownerInfo = this.getSetting("ownerInfo");
			this._progressSemantics = BX.prop.getString(this._settings, "progressSemantics", "");
			this._spotlightFastenShowed = this.getSetting("spotlightFastenShowed", true);
			var containerId = this.getSetting("containerId");
			if(!BX.type.isNotEmptyString(containerId))
			{
				throw "BX.CrmTimelineManager. A required parameter 'containerId' is missing.";
			}

			this._container = BX(containerId);
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmTimelineManager. Container node is not found.";
			}
			this._editorContainer = BX(this.getSetting("editorContainer"));

			this._userId = BX.prop.getInteger(this._settings, "userId", 0);
			this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);

			var activityEditorId = this.getSetting("activityEditorId");
			if(BX.type.isNotEmptyString(activityEditorId))
			{
				this._activityEditor = BX.CrmActivityEditor.items[activityEditorId];
				if(!(this._activityEditor instanceof BX.CrmActivityEditor))
				{
					throw "BX.CrmTimeline. Activity editor instance is not found.";
				}
			}

			var ajaxId = this.getSetting("ajaxId");
			var currentUrl = this.getSetting("currentUrl");
			var serviceUrl = this.getSetting("serviceUrl");

			this._chat = BX.CrmEntityChat.create(
				this._id,
				{
					manager: this,
					container: this._container,
					data: this.getSetting("chatData"),
					isStubMode: this._ownerId <= 0,
					readOnly: this._readOnly
				}
			);

			this._schedule = BX.CrmSchedule.create(
				this._id,
				{
					manager: this,
					container: this._container,
					activityEditor: this._activityEditor,
					itemData: this.getSetting("scheduleData"),
					isStubMode: this._ownerId <= 0,
					ajaxId: ajaxId,
					serviceUrl: serviceUrl,
					currentUrl: currentUrl,
					userId: this._userId,
					readOnly: this._readOnly
				}
			);

			this._fixedHistory = BX.CrmFixedHistory.create(
				this._id,
				{
					manager: this,
					container: this._container,
					editorContainer: this._editorContainer,
					activityEditor: this._activityEditor,
					itemData: this.getSetting("fixedData"),
					isStubMode: this._ownerId <= 0,
					ajaxId: ajaxId,
					serviceUrl: serviceUrl,
					currentUrl: currentUrl,
					userId: this._userId,
					readOnly: this._readOnly
				}
			);

			this._history = BX.CrmHistory.create(
				this._id,
				{
					manager: this,
					container: this._container,
					fixedHistory: this._fixedHistory,
					activityEditor: this._activityEditor,
					itemData: this.getSetting("historyData"),
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

			this._commentEditor = BX.CrmTimelineCommentEditor.create(
				this._id,
				{
					manager: this,
					ownerTypeId: this._ownerTypeId,
					ownerId: this._ownerId,
					serviceUrl: this.getSetting("serviceUrl"),
					container: this.getSetting("editorCommentContainer"),
					input: this.getSetting("editorCommentInput"),
					editorName: this.getSetting("editorCommentEditorName"),
					button: this.getSetting("editorCommentButton"),
					cancelButton: this.getSetting("editorCommentCancelButton")
				}
			);
			this._commentEditor.setVisible(true);
			this._commentEditor.setHistory(this._history);

			if(this._readOnly)
			{
				this._commentEditor.setVisible(false);
			}

			if(BX.prop.getBoolean(this._settings, "enableWait", false))
			{
				this._waitEditor = BX.CrmTimelineWaitEditor.create(
					this._id,
					{
						manager: this,
						ownerTypeId: this._ownerTypeId,
						ownerId: this._ownerId,
						serviceUrl: this.getSetting("serviceUrl"),
						config: this.getSetting("editorWaitConfig", {}),
						targetDates: this.getSetting("editorWaitTargetDates", []),
						container: this.getSetting("editorWaitContainer"),
						configContainer: this.getSetting("editorWaitConfigContainer"),
						input: this.getSetting("editorWaitInput"),
						button: this.getSetting("editorWaitButton"),
						cancelButton: this.getSetting("editorWaitCancelButton")
					}
				);
				this._waitEditor.setVisible(false);
			}

			if(BX.prop.getBoolean(this._settings, "enableSms", false))
			{
				this._smsEditor = BX.CrmTimelineSmsEditor.create(
					this._id,
					{
						manager: this,
						ownerTypeId: this._ownerTypeId,
						ownerId: this._ownerId,
						serviceUrl: this.getSetting("serviceUrl"),
						config: this.getSetting("editorSmsConfig", {}),
						container: this.getSetting("editorSmsContainer"),
						input: this.getSetting("editorSmsInput"),
						button: this.getSetting("editorSmsButton"),
						cancelButton: this.getSetting("editorSmsCancelButton")
					}
				);
				this._smsEditor.setVisible(false);
			}

			if(BX.prop.getBoolean(this._settings, "enableRest", false))
			{
				this._restEditor = BX.CrmTimelineRestEditor.create(
					this._id,
					{
						manager: this,
						ownerTypeId: this._ownerTypeId,
						ownerId: this._ownerId,
						placement: BX.prop.getString(this._settings, "restPlacement", '')
					}
				);
			}

			this._chat.layout();
			this._schedule.layout();
			this._fixedHistory.layout();
			this._history.layout();

			this._pullTagName = BX.prop.getString(this._settings, "pullTagName", "");
			if(this._pullTagName !== "")
			{
				BX.addCustomEvent("onPullEvent-crm", BX.delegate(this.onPullEvent, this));
				this.extendWatch();
			}

			this._menuBar = BX.CrmTimelineMenuBar.create(
				this._id,
				{
					container: BX(this.getSetting("menuBarContainer")),
					ownerInfo: this._ownerInfo,
					activityEditor: this._activityEditor,
					commentEditor: this._commentEditor,
					waitEditor: this._waitEditor,
					smsEditor: this._smsEditor,
					restEditor: this._restEditor,
					readOnly: this._readOnly,
					manager: this
				}
			);

			if(!this._readOnly)
			{
				this._menuBar.setActiveItemById("comment");
			}

			BX.addCustomEvent(window, "Crm.EntityProgress.Change", BX.delegate(this.onEntityProgressChange, this));
			BX.ready(function() {
				window.addEventListener("scroll", BX.throttle(function() {
					BX.LazyLoad.onScroll();
				}, 80));
			});
		},
		extendWatch: function()
		{
			if(BX.type.isFunction(BX.PULL) && this._pullTagName !== "")
			{
				BX.PULL.extendWatch(this._pullTagName);
				window.setTimeout(BX.delegate(this.extendWatch, this), 60000);
			}
		},
		onPullEvent: function(command, params)
		{
			//console.log("Pull command %s", command);
			//console.dir(params);

			if(this._pullTagName !== BX.prop.getString(params, "TAG", ""))
			{
				return;
			}

			if(command === "timeline_chat_create")
			{
				this.processChatCreate(params);
			}
			else if(command === "timeline_activity_add")
			{
				this.processActivityExternalAdd(params);
			}
			else if(command === "timeline_activity_update")
			{
				this.processActivityExternalUpdate(params);
			}
			else if(command === "timeline_activity_delete")
			{
				this.processActivityExternalDelete(params);
			}
			else if(command === "timeline_comment_add")
			{
				this.processCommentExternalAdd(params);
			}
			else if(command === "timeline_link_add")
			{
				this.processLinkExternalAdd(params);
			}
			else if(command === "timeline_document_add")
			{
				this.processLinkExternalAdd(params);
			}
			else if(command === "timeline_document_update")
			{
				this.processDocumentExternalUpdate(params);
			}
			else if(command === "timeline_document_delete")
			{
				this.processDocumentExternalDelete(params);
			}
			else if(command === "timeline_comment_update")
			{
				this.processCommentExternalUpdate(params);
			}
			else if(command === "timeline_comment_delete")
			{
				this.processCommentExternalDelete(params);
			}
			else if(command === "timeline_changed_binding")
			{
				this.processChangeBinding(params);
			}
			else if(command === "timeline_item_change_fasten")
			{
				this.processItemChangeFasten(params);
			}
			else if(command === "timeline_item_update")
			{
				this.processItemExternalUpdate(params);
			}
			else if(command === "timeline_wait_add")
			{
				this.processWaitExternalAdd(params);
			}
			else if(command === "timeline_wait_update")
			{
				this.processWaitExternalUpdate(params);
			}
			else if(command === "timeline_wait_delete")
			{
				this.processWaitExternalDelete(params);
			}
			else if(command === "timeline_bizproc_status")
			{
				this.processBizprocStatus(params);
			}
			else if(command === "timeline_scoring_add")
			{
				this.processScoringExternalAdd(params);
			}
		},
		processChatCreate: function(params)
		{
			if(this._chat)
			{
				this._chat.setData(BX.prop.getObject(params, "CHAT_DATA", {}));
				this._chat.refreshLayout();
			}
		},
		processActivityExternalAdd: function(params)
		{
			var entityData, scheduleItemData, historyItemData, scheduleItem, historyItem;

			entityData = BX.prop.getObject(params, "ENTITY", null);
			scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);


			if(entityData && historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
			{
				historyItemData["ASSOCIATED_ENTITY"] = entityData;
			}

			if(scheduleItemData !== null && this._schedule.getItemByData(scheduleItemData) === null)
			{
				scheduleItem = this.addScheduleItem(scheduleItemData);
				scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
			}

			if(historyItemData !== null)
			{
				historyItem = this.addHistoryItem(historyItemData);
				BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
			}
		},
		processActivityExternalUpdate: function(params)
		{
			var entityData, scheduleItemData, scheduleItem, historyItemData, historyItem, fixedHistoryItem;

			entityData = BX.prop.getObject(params, "ENTITY", null);
			scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

			if(entityData)
			{
				if(historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
				{
					historyItemData["ASSOCIATED_ENTITY"] = entityData;
				}

				var entityId = BX.prop.getInteger(entityData, "ID", 0);
				var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
				for(var i = 0, length = historyItems.length; i < length; i++)
				{
					historyItem = historyItems[i];
					historyItem.setAssociatedEntityData(entityData);
					historyItem.refreshLayout();
				}
				var fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
				for(var i = 0, length = fixedHistoryItems.length; i < length; i++)
				{
					fixedHistoryItem = fixedHistoryItems[i];
					fixedHistoryItem.setAssociatedEntityData(entityData);
					fixedHistoryItem.refreshLayout();
				}
			}

			if(scheduleItemData !== null)
			{
				scheduleItem = this._schedule.getItemByAssociatedEntity(
					BX.CrmEntityType.enumeration.activity,
					BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID")
				);

				if(scheduleItem)
				{
					scheduleItem.setData(scheduleItemData);
					if(!scheduleItem.isDone())
					{
						this._schedule.refreshItem(scheduleItem);
					}
					else
					{
						if(historyItemData)
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
				else if(!BX.CrmScheduleItem.isDone(scheduleItemData))
				{
					scheduleItem = this.addScheduleItem(scheduleItemData);
					scheduleItem.addWrapperClass("crm-entity-stream-section-updated", 1000);
				}
			}

			if(historyItemData !== null)
			{
				historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
				if(!historyItem)
				{
					historyItem = this.addHistoryItem(historyItemData);
					BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
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
		},
		processActivityExternalDelete: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
			for(var i = 0, length = historyItems.length; i < length; i++)
			{
				this._history.deleteItem(historyItems[i]);
			}

			var fixedHistoryItems = this._fixedHistory.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
			for(var i = 0, length = fixedHistoryItems.length; i < length; i++)
			{
				this._fixedHistory.deleteItem(fixedHistoryItems[i]);
			}

			var	scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.activity, entityId);
			if(scheduleItem)
			{
				this._schedule.deleteItem(scheduleItem);
			}
		},
		processCommentExternalAdd: function(params)
		{
			var historyItemData, historyItem;
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			if(historyItemData !== null)
			{
				window.setTimeout(
					BX.delegate(function() {
						if (!this._history.findItemById(historyItemData['ID']))
						{
							historyItem = this.addHistoryItem(historyItemData);
							BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
						}
					}, this),
					1500
				);
			}
		},
		processLinkExternalAdd: function(params)
		{
			var historyItemData, historyItem;
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			if(historyItemData !== null)
			{
				historyItem = this.addHistoryItem(historyItemData);
				BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
			}
		},
		processCommentExternalUpdate: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			var updateItem = this._history.findItemById(entityId);
			if (updateItem instanceof BX.CrmHistoryItemComment && historyItemData !== null)
			{
				updateItem.setData(historyItemData);
				updateItem.switchToViewMode();
			}
			var updateFixedItem = this._fixedHistory.findItemById(entityId);
			if (updateFixedItem instanceof BX.CrmHistoryItemComment && historyItemData !== null)
			{
				updateFixedItem.setData(historyItemData);
				updateFixedItem.switchToViewMode();
			}
		},
		processCommentExternalDelete: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			window.setTimeout(
				BX.delegate(function() {
					var deleteItem = this._history.findItemById(entityId);
					if (deleteItem instanceof BX.CrmHistoryItemComment)
						this._history.deleteItem(deleteItem);
					var deleteFixedItem = this._fixedHistory.findItemById(entityId);
					if (deleteFixedItem instanceof BX.CrmHistoryItemComment)
						this._fixedHistory.deleteItem(deleteFixedItem);
				}, this),
				1200
			);
		},
		processDocumentExternalDelete: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			window.setTimeout(
				BX.delegate(function() {
					var deleteItem = this._history.findItemById(entityId);
					if (deleteItem instanceof BX.CrmHistoryItemDocument)
						this._history.deleteItem(deleteItem);
					var deleteFixedItem = this._fixedHistory.findItemById(entityId);
					if (deleteFixedItem instanceof BX.CrmHistoryItemDocument)
						this._fixedHistory.deleteItem(deleteFixedItem);
				}, this),
				1200
			);
		},
		processDocumentExternalUpdate: function(params)
		{
			var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			var id = BX.prop.getInteger(historyItemData, "ID", 0);
			var updateItem = this._history.findItemById(id);
			if (updateItem instanceof BX.CrmHistoryItemDocument && historyItemData !== null)
			{
				updateItem.setData(historyItemData);
				updateItem.updateWrapper();
			}
			var updateFixedItem = this._fixedHistory.findItemById(id);
			if (updateFixedItem instanceof BX.CrmHistoryItemDocument && historyItemData !== null)
			{
				updateFixedItem.setData(historyItemData);
				updateFixedItem.updateWrapper();
			}
		},
		processChangeBinding: function(params)
		{
			var entityId = BX.prop.getString(params, "OLD_ID", 0);
			var entityNewId = BX.prop.getString(params, "NEW_ID", 0);
			var item = this._history.findItemById(entityId);
			if (item instanceof BX.CrmHistoryItem)
			{
				item._id = entityNewId;
				var itemData = item.getData();
				itemData.ID = entityNewId;
				item.setData(itemData);
			}

			var fixedItem = this._fixedHistory.findItemById(entityId);
			if (fixedItem instanceof BX.CrmHistoryItem)
			{
				fixedItem._id = entityNewId;
				var fixedItemData = fixedItem.getData();
				fixedItemData.ID = entityNewId;
				fixedItem.setData(fixedItemData);
			}
		},
		processItemChangeFasten: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			window.setTimeout(
				BX.delegate(function() {
					var fixedItem = this._fixedHistory.findItemById(entityId);
					if (historyItemData['IS_FIXED'] === 'N' && fixedItem)
					{
						fixedItem.onSuccessUnfasten();
					}
					else if (historyItemData['IS_FIXED'] === 'Y' && !fixedItem)
					{
						var historyItem = this._history.findItemById(entityId);
						if (historyItem)
						{
							historyItem.onSuccessFasten();
						}
						else
						{
							var newFixedItem = this._fixedHistory.createItem(this._data);
							newFixedItem._isFixed = true;
							this._fixedHistory.addItem(newFixedItem, 0);
							newFixedItem.layout();
						}
					}
				}, this),
				1200
			);
		},
		processItemExternalUpdate: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			var historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			var historyItem = this._history.findItemById(entityId);
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
		},
		processWaitExternalAdd: function(params)
		{
			var scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
			if(scheduleItemData !== null)
			{
				this.addScheduleItem(scheduleItemData);
			}
		},
		processWaitExternalUpdate: function(params)
		{
			var entityData, scheduleItemData, scheduleItem, historyItemData, historyItem;

			entityData = BX.prop.getObject(params, "ENTITY", null);
			scheduleItemData = BX.prop.getObject(params, "SCHEDULE_ITEM", null);
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

			if(entityData)
			{
				if(historyItemData && !BX.type.isPlainObject(historyItemData["ASSOCIATED_ENTITY"]))
				{
					historyItemData["ASSOCIATED_ENTITY"] = entityData;
				}

				var entityId = BX.prop.getInteger(entityData, "ID", 0);
				var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
				for(var i = 0, length = historyItems.length; i < length; i++)
				{
					historyItem = historyItems[i];
					historyItem.setAssociatedEntityData(entityData);
					historyItem.refreshLayout();
				}
			}

			if(scheduleItemData !== null)
			{
				scheduleItem = this._schedule.getItemByAssociatedEntity(
					BX.CrmEntityType.enumeration.wait,
					BX.prop.getInteger(scheduleItemData, "ASSOCIATED_ENTITY_ID")
				);
				if(!scheduleItem)
				{
					this.addScheduleItem(scheduleItemData);
				}
				else
				{
					scheduleItem.setData(scheduleItemData);
					if(!scheduleItem.isDone())
					{
						this._schedule.refreshItem(scheduleItem);
					}
					else
					{
						if(historyItemData)
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

			if(historyItemData !== null)
			{
				historyItem = this._history.findItemById(BX.prop.getString(historyItemData, "ID"));
				if(!historyItem)
				{
					historyItem = this.addHistoryItem(historyItemData);
					BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
				}
				else
				{
					historyItem.setData(historyItemData);
					historyItem.refreshLayout();
				}
			}
		},
		processWaitExternalDelete: function(params)
		{
			var entityId = BX.prop.getInteger(params, "ENTITY_ID", 0);
			var historyItems = this._history.getItemsByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
			for(var i = 0, length = historyItems.length; i < length; i++)
			{
				this._history.deleteItem(historyItems[i]);
			}

			var	scheduleItem = this._schedule.getItemByAssociatedEntity(BX.CrmEntityType.enumeration.wait, entityId);
			if(scheduleItem)
			{
				this._schedule.deleteItem(scheduleItem);
			}
		},
		processBizprocStatus: function(params)
		{
			var historyItemData, historyItem;

			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);

			if(historyItemData !== null)
			{
				historyItem = this.addHistoryItem(historyItemData);
				BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
			}
		},
		processScoringExternalAdd: function(params)
		{
			var historyItemData, historyItem;
			historyItemData = BX.prop.getObject(params, "HISTORY_ITEM", null);
			if(historyItemData !== null)
			{
				historyItem = this.addHistoryItem(historyItemData);
				BX.CrmTimelineItemExpand.create(historyItem.getWrapper(), null).run();
			}
		},
		onEntityProgressChange: function(sender, eventArgs)
		{
			if(BX.prop.getInteger(eventArgs, "entityTypeId", 0) !== this._ownerTypeId
				|| BX.prop.getInteger(eventArgs, "entityId", 0) !== this._ownerId
			)
			{
				return;
			}

			var semantics = BX.prop.getString(eventArgs, "semantics", "");
			if(semantics === this._progressSemantics)
			{
				return;
			}

			this._progressSemantics = semantics;
			this._schedule.refreshLayout();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getOwnerTypeId: function()
		{
			return this._ownerTypeId;
		},
		getOwnerId: function()
		{
			return this._ownerId;
		},
		getOwnerInfo: function()
		{
			return this._ownerInfo;
		},
		isStubCounterEnabled: function()
		{
			if(this._ownerId <= 0)
			{
				return false;
			}

			return(
				(this._ownerTypeId === BX.CrmEntityType.enumeration.deal || this._ownerTypeId === BX.CrmEntityType.enumeration.lead)
					&& this._progressSemantics === "process"
			);
		},
		getSchedule: function()
		{
			return this._schedule;
		},
		getHistory: function()
		{
			return this._history;
		},
		getFixedHistory: function()
		{
			return this._fixedHistory;
		},
		getWaitEditor: function()
		{
			return this._waitEditor;
		},
		getSmsEditor: function()
		{
			return this._smsEditor;
		},
		processSheduleLayoutChange: function()
		{
		},
		processHistoryLayoutChange: function()
		{
			this._schedule.refreshLayout();
		},
		processEditingCompletion: function(editor)
		{
			if(this._waitEditor && editor === this._waitEditor)
			{
				this._waitEditor.setVisible(false);
				this._commentEditor.setVisible(true);
				this._menuBar.setActiveItemById("comment");
			}
			if(this._smsEditor && editor === this._smsEditor)
			{
				this._smsEditor.setVisible(false);
				this._commentEditor.setVisible(true);
				this._menuBar.setActiveItemById("comment");
			}
		},
		processEditingCancellation: function(editor)
		{
			if(this._waitEditor && editor === this._waitEditor)
			{
				this._waitEditor.setVisible(false);
				this._commentEditor.setVisible(true);
				this._menuBar.setActiveItemById("comment");
			}
			if(this._smsEditor && editor === this._smsEditor)
			{
				this._smsEditor.setVisible(false);
				this._commentEditor.setVisible(true);
				this._menuBar.setActiveItemById("comment");
			}
		},
		addScheduleItem: function(data)
		{
			var item = this._schedule.createItem(data);
			var index = this._schedule.calculateItemIndex(item);
			var anchor = this._schedule.createAnchor(index);
			this._schedule.addItem(item, index);
			item.layout({ anchor: anchor });

			return item;
		},
		addHistoryItem: function(data)
		{
			var item = this._history.createItem(data);
			var index = this._history.calculateItemIndex(item);
			var historyAnchor = this._history.createAnchor(index);
			this._history.addItem(item, index);
			item.layout({ anchor: historyAnchor });

			return item;
		},
		loadMediaPlayer: function(id, filePath, mediaType, node, duration)
		{
			if(!duration)
			{
				duration = 0;
			}
			var player = new BX.Fileman.Player(id, {
				sources: [
					{
						src: filePath,
						type: mediaType
					}
				],
				isAudio: true,
				skin: 'vjs-timeline_player-skin',
				width: 350,
				height: 30,
				duration: duration,
				onInit: function(player)
				{
					player.vjsPlayer.controlBar.removeChild('timeDivider');
					player.vjsPlayer.controlBar.removeChild('durationDisplay');
					player.vjsPlayer.controlBar.removeChild('fullscreenToggle');
					player.vjsPlayer.controlBar.addChild('timeDivider');
					player.vjsPlayer.controlBar.addChild('durationDisplay');
					if(!player.isPlaying())
					{
						player.play();
					}
				}
			});
			BX.cleanNode(node, false);
			node.appendChild(player.createElement());
			player.init();
		},
		onActivityCreated: function(activity, data)
		{
			//Already processed in onPullEvent
		},
		isSpotlightShowed: function()
		{
			return this._spotlightFastenShowed;
		},
		setSpotlightShowed: function()
		{
			this._spotlightFastenShowed = true;
		}
	};
	BX.CrmTimelineManager.instances = {};
	BX.CrmTimelineManager.create = function(id, settings)
	{
		var self = new BX.CrmTimelineManager();
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmTimeline) === "undefined")
{
	BX.CrmTimeline = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._manager = null;
		this._activityEditor = null;

		this._userTimezoneOffset = null;
		this._serverTimezoneOffset = null;
		this._timeFormat = "";
		this._year = 0;

		this._isStubMode = false;
		this._userId = 0;
		this._readOnly = false;

		this._serviceUrl = "";
	};
	BX.CrmTimeline.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("container"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmTimeline. Container node is not found.";
			}
			this._editorContainer = BX(this.getSetting("editorContainer"));
			this._manager = this.getSetting("manager");
			if(!(this._manager instanceof BX.CrmTimelineManager))
			{
				throw "BX.CrmTimeline. Manager instance is not found.";
			}

			var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
			var dateFormat = BX.message("FORMAT_DATE");
			this._timeFormat = BX.date.convertBitrixFormat(BX.util.trim(datetimeFormat.replace(dateFormat, "")));
			this._year = (new Date()).getFullYear();

			this._activityEditor = this.getSetting("activityEditor");

			this._isStubMode =  BX.prop.getBoolean(this._settings, "isStubMode", false);
			this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);
			this._userId = BX.prop.getInteger(this._settings, "userId", 0);
			this._serviceUrl = BX.prop.getString(this._settings, "serviceUrl", "");

			this.doInitialize();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		doInitialize: function()
		{
		},
		layout: function()
		{
		},
		isStubMode: function()
		{
			return this._isStubMode;
		},
		isReadOnly: function()
		{
			return this._readOnly;
		},
		getUserId: function()
		{
			return this._userId;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		refreshLayout: function()
		{
		},
		getManager: function()
		{
			return this._manager;
		},
		getOwnerInfo: function()
		{
			return this._manager.getOwnerInfo();
		},
		reload: function()
		{
			var currentUrl = this.getSetting("currentUrl");
			var ajaxId = this.getSetting("ajaxId");
			if(ajaxId !== "")
			{
				BX.ajax.insertToNode(BX.util.add_url_param(currentUrl, { bxajaxid: ajaxId }), "comp_" + ajaxId);
			}
			else
			{
				window.location = currentUrl;
			}
		},
		getUserTimezoneOffset: function()
		{
			if(!this._userTimezoneOffset)
			{
				this._userTimezoneOffset = parseInt(BX.message("USER_TZ_OFFSET"));
				if(isNaN(this._userTimezoneOffset))
				{
					this._userTimezoneOffset = 0;
				}
			}
			return this._userTimezoneOffset;
		},
		getServerTimezoneOffset: function()
		{
			if(!this._serverTimezoneOffset)
			{
				this._serverTimezoneOffset = parseInt(BX.message("SERVER_TZ_OFFSET"));
				if(isNaN(this._serverTimezoneOffset))
				{
					this._serverTimezoneOffset = 0;
				}
			}
			return this._serverTimezoneOffset;
		},
		formatTime: function(time, now, utc)
		{
			return BX.date.format(this._timeFormat, time, now, utc);
		},
		formatDate: function(date)
		{
			return (
				BX.date.format(
					[
						["today", "today"],
						["tommorow", "tommorow"],
						["yesterday", "yesterday"],
						["" , (date.getFullYear() === this._year) ? "j F" : "j F Y"]
					],
					date
				)
			);
		},
		cutOffText: function(text, length)
		{
			if(!BX.type.isNumber(length))
			{
				length = 0;
			}

			if(length <= 0 || text.length <= length)
			{
				return text;
			}

			var offset = length - 1;
			var whitespaceOffset = text.substring(offset).search(/\s/i);
			if(whitespaceOffset > 0)
			{
				offset += whitespaceOffset;
			}
			return text.substring(0, offset) + "...";
		}
	};
}
if(typeof(BX.CrmHistory) === "undefined")
{
	BX.CrmHistory = function()
	{
		BX.CrmHistory.superclass.constructor.apply(this);
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
	};
	BX.extend(BX.CrmHistory, BX.CrmTimeline);
	BX.CrmHistory.prototype.doInitialize = function()
	{
		this._fixedHistory = this.getSetting("fixedHistory");
		this._ownerTypeId = this.getSetting("ownerTypeId");
		this._ownerId = this.getSetting("ownerId");
		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!this.isStubMode())
		{
			var itemData = this.getSetting("itemData");
			if(!BX.type.isArray(itemData))
			{
				itemData = [];
			}

			var i, length, item;
			for(i = 0, length = itemData.length; i < length; i++)
			{
				item = this.createItem(itemData[i]);
				this._items.push(item);
			}

			this._navigation = this.getSetting("navigation", {});

			this._filterWrapper = BX("timeline-filter");
			this._filterId = BX.prop.getString(this._settings, "filterId", this._id);
			this._isFilterShown = this._filterWrapper
				&& BX.hasClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
			this._isFilterApplied = BX.prop.getBoolean(this._settings, "isFilterApplied", false);

			BX.addCustomEvent("BX.Main.Filter:apply", this.onFilterApply.bind(this));
		}
	};
	BX.CrmHistory.prototype.layout = function()
	{
		this._wrapper = BX.create("DIV", {});
		this._container.appendChild(this._wrapper);

		var now = BX.prop.extractDate(new Date());
		var i, length, item;

		if(!this.isStubMode())
		{
			if(this._filterWrapper)
			{
				var closeFilterButton = this._filterWrapper.querySelector(".crm-entity-stream-filter-close");
				if(closeFilterButton)
				{
					BX.bind(closeFilterButton, "click", this.onFilterClose.bind(this));
				}
			}

			for(i = 0, length = this._items.length; i < length; i++)
			{
				item = this._items[i];
				item.setContainer(this._wrapper);

				var created = item.getCreatedDate();
				if(this._lastDate === null || this._lastDate.getTime() !== created.getTime())
				{
					this._lastDate = created;
					if(now.getTime() === created.getTime())
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
						attrs: { className: "crm-entity-stream-section crm-entity-stream-section-createEntity crm-entity-stream-section-last" },
						children:
						[
							BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } }),
							BX.create(
								"DIV",
								{
									attrs: { className: "crm-entity-stream-section-content" },
									children:
									[
										BX.create(
											"DIV",
											{
												attrs: { className: "crm-entity-stream-content-event" },
												children:
												[
													BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } }),
													BX.create(
														"DIV",
														{
															attrs: { className: "crm-entity-stream-content-detail" },
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
	};
	BX.CrmHistory.prototype.refreshLayout = function()
	{
		if(this._filterWrapper)
		{
			if(this._wrapper.firstChild && this._filterWrapper !== this._wrapper.firstChild)
			{
				this._wrapper.insertBefore(this._filterWrapper, this._wrapper.firstChild);
			}
			else if(!this._wrapper.firstChild && this._filterWrapper.parentNode !== this._wrapper)
			{
				this._wrapper.appendChild(this._filterWrapper);
			}
		}

		this.adjustFilterButton();

		var length = this._items.length;
		if(length === 0 && this._isFilterApplied)
		{
			if(!this._filterEmptyResultSection)
			{
				this._filterEmptyResultSection = this.createFilterEmptyResultSection();
			}
			this._wrapper.appendChild(this._filterEmptyResultSection);

			return;
		}

		if(this._filterEmptyResultSection)
		{
			this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
		}

		if(length === 0)
		{
			return;
		}

		for(var i = 0;  i < (length - 1); i++)
		{
			var item = this._items[i];
			if(item.isTerminated())
			{
				item.markAsTerminated(false);
			}
		}

		this._items[length - 1].markAsTerminated(true);
	};
	BX.CrmHistory.prototype.calculateItemIndex = function(item)
	{
		return 0;
	};
	BX.CrmHistory.prototype.checkItemForTermination = function(item)
	{
		return this.getLastItem() === item;
	};
	BX.CrmHistory.prototype.hasContent = function()
	{
		return(this._items.length > 0 || this._isFilterApplied || this._isStubMode);
	};
	BX.CrmHistory.prototype.getLastItem = function()
	{
		return this._items.length > 0 ? this._items[this._items.length - 1] : null;
	};
	BX.CrmHistory.prototype.getItemByIndex = function(index)
	{
		return index < this._items.length ? this._items[index] : null;
	};
	BX.CrmHistory.prototype.getItemCount = function()
	{
		return this._items.length;
	};
	BX.CrmHistory.prototype.removeItemByIndex = function(index)
	{
		if(index < this._items.length)
		{
			this._items.splice(index, 1);
		}
	};
	BX.CrmHistory.prototype.getItemIndex = function(item)
	{
		for(var i = 0, length = this._items.length; i < length; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}

		return -1;
	};
	BX.CrmHistory.prototype.getItemsByAssociatedEntity = function($entityTypeId, entityId)
	{
		if(!BX.type.isNumber($entityTypeId))
		{
			$entityTypeId = parseInt($entityTypeId);
		}

		if(!BX.type.isNumber(entityId))
		{
			entityId = parseInt(entityId);
		}

		if(isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
		{
			return [];
		}

		var results = [];
		for(var i = 0, l = this._items.length; i < l; i++)
		{
			var item = this._items[i];
			if(item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId)
			{
				results.push(item);
			}
		}
		return results;
	};
	BX.CrmHistory.prototype.findItemById = function(id)
	{
		id = id.toString();
		for(var i = 0, l = this._items.length; i < l; i++)
		{
			if(this._items[i].getId() === id)
			{
				return this._items[i];
			}
		}
		return null;
	};
	BX.CrmHistory.prototype.createFilterEmptyResultSection = function()
	{
		return BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-filter-empty" },
				children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-section-content" },
								children:
									[
										BX.create("DIV",
											{
												attrs: { className: "crm-entity-stream-filter-empty" },
												children:
													[
														BX.create("DIV",
															{
																attrs: { className: "crm-entity-stream-filter-empty-img" }
															}
														),
														BX.create("DIV",
															{
																attrs: { className: "crm-entity-stream-filter-empty-text" },
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
	};
	BX.CrmHistory.prototype.adjustFilterButton = function()
	{
		if(!this._filterWrapper)
		{
			return;
		}

		if(!this._isFilterShown && this._items.length === 0)
		{
			if(!this._emptySection)
			{
				this._emptySection = this.createEmptySection();
			}
			this._wrapper.insertBefore(this._emptySection, this._filterWrapper);
		}
		else if(this._emptySection)
		{
			this._emptySection = BX.remove(this._emptySection);
		}

		if(!this._filterButton)
		{
			this._filterButton = BX.create("BUTTON",
				{
					attrs: { className: "crm-entity-stream-filter-label" },
					text: this.getMessage("filterButtonCaption")
				}
			);

			BX.bind(this._filterButton, "click", function(e){ this.showFilter(); }.bind(this));
		}

		var section = this._wrapper.querySelector(".crm-entity-stream-section-today-label, .crm-entity-stream-section-planned-label, .crm-entity-stream-section-history-label");
		if(section)
		{
			var sectionWrapper = section.querySelector(".crm-entity-stream-section-content");
			if(sectionWrapper)
			{
				if(this._filterButton.parentNode !== sectionWrapper)
				{
					sectionWrapper.appendChild(this._filterButton);
				}
			}
		}

		if(this._isFilterApplied)
		{
			BX.addClass(this._filterButton, "crm-entity-stream-filter-label-active");
		}
		else
		{
			BX.removeClass(this._filterButton, "crm-entity-stream-filter-label-active");
		}
	};
	BX.CrmHistory.prototype.showFilter = function(params)
	{
		if(!this._filterWrapper)
		{
			return;
		}

		BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");
		BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-show");

		this._isFilterShown = true;

		if(BX.prop.getBoolean(params, "enableAdjust", true))
		{
			this.adjustFilterButton();
		}
	};
	BX.CrmHistory.prototype.hideFilter = function(params)
	{
		if(!this._filterWrapper)
		{
			return;
		}

		BX.removeClass(this._filterWrapper, "crm-entity-stream-section-filter-show");
		BX.addClass(this._filterWrapper, "crm-entity-stream-section-filter-hide");

		this._isFilterShown = false;

		if(BX.prop.getBoolean(params, "enableAdjust", true))
		{
			this.adjustFilterButton();
		}
	};
	BX.CrmHistory.prototype.onFilterClose = function(e)
	{
		this.hideFilter();

		window.setTimeout(
			function()
			{
				var filter = BX.Main.filterManager.getById(this._filterId);
				if(filter)
				{
					filter.resetFilter();
				}
			}.bind(this),
			500
		);
	};
	BX.CrmHistory.prototype.createEmptySection = function()
	{
		return BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-planned-label" },
				children: [ BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" } }) ]
			}
		);
	};
	BX.CrmHistory.prototype.createCurrentDaySection = function()
	{
		return BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-today-label" },
				children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-section-content" },
								children:
									[
										BX.create("DIV",
											{
												attrs: { className: "crm-entity-stream-today-label" },
												text: this.formatDate(BX.prop.extractDate(new Date()))
											}
										)
									]
							}
						)
					]
			}
		);
	};
	BX.CrmHistory.prototype.createDaySection = function(date)
	{
		return BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history-label" },
				children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-section-content" },
								children:
									[
										BX.create("DIV",
											{
												attrs: { className: "crm-entity-stream-history-label" },
												text: this.formatDate(date)
											}
										)
									]
							}
						)
					]
			}
		);
	};
	BX.CrmHistory.prototype.createAnchor = function(index)
	{
		if(this._emptySection)
		{
			this._emptySection = BX.remove(this._emptySection);
		}

		if(this._currentDaySection === null)
		{
			this._currentDaySection = this.createCurrentDaySection();
			if(this._wrapper.firstChild)
			{
				this._wrapper.insertBefore(this._currentDaySection, this._wrapper.firstChild);
			}
			else
			{
				this._wrapper.appendChild(this._currentDaySection);
			}
		}

		if(this._anchor === null)
		{
			this._anchor = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-shadow" } });
			if(this._currentDaySection.nextSibling)
			{
				this._wrapper.insertBefore(this._anchor, this._currentDaySection.nextSibling);
			}
			else
			{
				this._wrapper.appendChild(this._anchor);
			}
		}
		return this._anchor;
	};
	BX.CrmHistory.prototype.createActivityItem = function(data)
	{
		var typeId = BX.prop.getInteger(data, "TYPE_ID", BX.CrmTimelineType.undefined);
		var typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

		if(typeId !== BX.CrmTimelineType.activity)
		{
			return null;
		}

		if(typeCategoryId === BX.CrmActivityType.email)
		{
			return BX.CrmHistoryItemEmail.create(
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
		if(typeCategoryId === BX.CrmActivityType.call)
		{
			return BX.CrmHistoryItemCall.create(
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
		else if(typeCategoryId === BX.CrmActivityType.meeting)
		{
			return BX.CrmHistoryItemMeeting.create(
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
		else if(typeCategoryId === BX.CrmActivityType.task)
		{
			return BX.CrmHistoryItemTask.create(
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
		else if(typeCategoryId === BX.CrmActivityType.provider)
		{
			var providerId = BX.prop.getString(
				BX.prop.getObject(data, "ASSOCIATED_ENTITY", {}),
				"PROVIDER_ID",
				""
			);

			if(providerId === "CRM_WEBFORM")
			{
				return BX.CrmHistoryItemWebForm.create(
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
			else if (providerId === 'CRM_SMS')
			{
				return BX.CrmHistoryItemSms.create(
					data["ID"],
					{
						history: this._history,
						fixedHistory: this._fixedHistory,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data,
						smsStatusDescriptions: this._manager.getSetting('smsStatusDescriptions', {}),
						smsStatusSemantics: this._manager.getSetting('smsStatusSemantics', {})
					}
				);
			}
			else if (providerId === 'CRM_REQUEST')
			{
				return BX.CrmHistoryItemActivityRequest.create(
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
			else if(providerId === "IMOPENLINES_SESSION")
			{
				return BX.CrmHistoryItemOpenLine.create(
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
				return BX.CrmHistoryItemActivityRestApplication.create(
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
			else if(providerId === 'VISIT_TRACKER')
			{
				return BX.CrmHistoryItemVisit.create(
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

		return BX.CrmHistoryItemActivity.create(
			data["ID"],
			{
				history: this._history,
				fixedHistory: this._fixedHistory,
				container: this._wrapper,
				activityEditor: this._activityEditor,
				data: data
			}
		);
	};
	BX.CrmHistory.prototype.createOrderEntityItem = function(data)
	{
		var entityId = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
		var typeId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

		if(entityId !== BX.CrmEntityType.enumeration.order)
		{
			return null;
		}

		if (typeId === BX.CrmTimelineType.creation)
		{
			return BX.CrmHistoryItemOrderCreation.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		if (typeId === BX.CrmTimelineType.modification)
		{
			return BX.CrmHistoryItemOrderModification.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
	};
	BX.CrmHistory.prototype.createItem = function(data)
	{
		var typeId = BX.prop.getInteger(data, "TYPE_ID", BX.CrmTimelineType.undefined);
		var typeCategoryId = BX.prop.getInteger(data, "TYPE_CATEGORY_ID", 0);

		if(typeId === BX.CrmTimelineType.activity)
		{
			return this.createActivityItem(data);
		}
		else if(typeId === BX.CrmTimelineType.order)
		{
			return this.createOrderEntityItem(data);
		}
		else if(typeId === BX.CrmTimelineType.orderCheck)
		{
			return BX.CrmHistoryItemOrcderCheck.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.creation)
		{
			return BX.CrmHistoryItemCreation.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.restoration)
		{
			return BX.CrmHistoryItemRestoration.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.link)
		{
			return BX.CrmHistoryItemLink.create(
				data["ID"],
				{
					history: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.mark)
		{
			return BX.CrmHistoryItemMark.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.comment)
		{
			return BX.CrmHistoryItemComment.create(
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
		else if(typeId === BX.CrmTimelineType.wait)
		{
			return BX.CrmHistoryItemWait.create(
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
		else if(typeId === BX.CrmTimelineType.document)
		{
			return BX.CrmHistoryItemDocument.create(
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
		else if(typeId === BX.CrmTimelineType.sender)
		{
			return BX.CrmHistoryItemSender.create(
				data["ID"],
				{
					history: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.modification)
		{
			return BX.CrmHistoryItemModification.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.conversion)
		{
			return BX.CrmHistoryItemConversion.create(
				data["ID"],
				{
					history: this._history,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else if(typeId === BX.CrmTimelineType.bizproc)
		{
			return BX.CrmHistoryItemBizproc.create(
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
		else if(typeId === BX.CrmTimelineType.scoring)
		{
			return BX.CrmHistoryItemScoring.create(
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

		return BX.CrmHistoryItem.create(
			data["ID"],
			{
				history: this._history,
				fixedHistory: this._fixedHistory,
				container: this._wrapper,
				activityEditor: this._activityEditor,
				data: data
			}
		);
	};
	BX.CrmHistory.prototype.addItem = function(item, index)
	{
		if(!BX.type.isNumber(index) || index < 0)
		{
			index = this.calculateItemIndex(item);
		}

		if(index < this._items.length)
		{
			this._items.splice(index, 0, item);
		}
		else
		{
			this._items.push(item);
		}

		this.refreshLayout();
		this._manager.processHistoryLayoutChange();
	};
	BX.CrmHistory.prototype.deleteItem = function(item)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processHistoryLayoutChange();
	};
	BX.CrmHistory.prototype.resetLayout = function()
	{
		var i;

		for(i = (this._items.length - 1); i >= 0; i--)
		{
			this._items[i].clearLayout();
		}

		this._items = [];

		this._currentDaySection = this._lastDaySection = this._emptySection  = this._filterEmptyResultSection = null;
		this._anchor = null;
		this._lastDate = null;

		//Clean wrapper. Skip filter for prevent trembling.
		var children = [];
		var child;
		for(i = 0; child = this._wrapper.children[i]; i++)
		{
			if(child !== this._filterWrapper)
			{
				children.push(child);
			}
		}

		for(i = 0; child = children[i]; i++)
		{
			this._wrapper.removeChild(child);
		}
	};
	BX.CrmHistory.prototype.onWindowScroll = function(e)
	{
		if(!this._loadingWaiter || !this._enableLoading || this._isRequestRunning)
		{
			return;
		}

		var pos = this._loadingWaiter.getBoundingClientRect();
		if(pos.top <= document.documentElement.clientHeight)
		{
			this.loadItems();
		}
	};
	BX.CrmHistory.prototype.onFilterApply = function(id, data, ctx, promise, params)
	{
		if(id !== this._filterId)
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
						"OWNER_TYPE_ID" : this._manager.getOwnerTypeId(),
						"OWNER_ID": this._manager.getOwnerId()
					}
			}
		).load(
			function(sender, result)
			{
				this.resetLayout();
				this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
				this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));

				this.refreshLayout();
				if(this._items.length > 0)
				{
					this._manager.processHistoryLayoutChange();
				}

				promise.fulfill();
				this._isRequestRunning = false;
			}.bind(this)
		);
	};
	BX.CrmHistory.prototype.bulkCreateItems = function(itemData)
	{
		var length = itemData.length;
		if(length === 0)
		{
			return;
		}

		if(this._filterEmptyResultSection)
		{
			this._filterEmptyResultSection = BX.remove(this._filterEmptyResultSection);
		}

		var now = BX.prop.extractDate(new Date());
		var i, item;
		var lastItemTime = "";
		for(i = 0; i < length; i++)
		{
			var itemId = BX.prop.getInteger(itemData[i], "ID", 0);
			if(itemId <= 0)
			{
				continue;
			}

			lastItemTime = BX.prop.getString(itemData[i], "CREATED_SERVER", "");
			if(this.findItemById(itemId) !== null)
			{
				continue;
			}

			item = this.createItem(itemData[i]);
			this._items.push(item);

			var created = item.getCreatedDate();
			if(this._lastDate === null || this._lastDate.getTime() !== created.getTime())
			{
				this._lastDate = created;
				if(now.getTime() === created.getTime())
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
	};
	BX.CrmHistory.prototype.loadItems = function()
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
						"OWNER_TYPE_ID" : this._manager.getOwnerTypeId(),
						"OWNER_ID": this._manager.getOwnerId(),
						"NAVIGATION" : this._navigation
					}
			}
		).load(
			function(sender, result)
			{
				this.bulkCreateItems(BX.prop.getArray(result, "HISTORY_ITEMS", []));
				this.setNavigation(BX.prop.getObject(result, "HISTORY_NAVIGATION", {}));

				this.refreshLayout();
				if(this._items.length > 0)
				{
					this._manager.processHistoryLayoutChange();
				}

				this._isRequestRunning = false;
			}.bind(this)
		);
	};
	BX.CrmHistory.prototype.getNavigation = function()
	{
		return this._navigation;
	};
	BX.CrmHistory.prototype.setNavigation = function(navigation)
	{
		if(!BX.type.isPlainObject(navigation))
		{
			navigation = {};
		}

		this._navigation = navigation;
		this.enableLoading(
			BX.prop.getString(this._navigation, "OFFSET_TIMESTAMP", "") !== ""
		);
	};
	BX.CrmHistory.prototype.isLoadingEnabled = function()
	{
		return this._enableLoading;
	};
	BX.CrmHistory.prototype.enableLoading = function(enable)
	{
		enable = !!enable;

		if(this._enableLoading === enable)
		{
			return;
		}

		this._enableLoading = enable;

		if(this._enableLoading)
		{
			if(this._items.length > 0)
			{
				this._loadingWaiter = this._items[this._items.length - 1].getWrapper();
			}

			if(!this._scrollHandler)
			{
				this._scrollHandler = BX.delegate(this.onWindowScroll, this);
				BX.bind(window, "scroll", this._scrollHandler);
			}
		}
		else
		{
			this._loadingWaiter = null;

			if(this._scrollHandler)
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this._scrollHandler = null;
			}
		}
	};
	BX.CrmHistory.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistory.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistory.messages) === "undefined")
	{
		BX.CrmHistory.messages = {};
	}

	BX.CrmHistory.instances = {};
	BX.CrmHistory.create = function(id, settings)
	{
		var self = new BX.CrmHistory();
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}
if(typeof(BX.CrmFixedHistory) === "undefined")
{
	BX.CrmFixedHistory = function()
	{
		BX.CrmFixedHistory.superclass.constructor.apply(this);
		this._items = [];
		this._wrapper = null;
		this._fixedHistory = this;
		this._history = this;
		this._isRequestRunning = false;
	};
	BX.extend(BX.CrmFixedHistory, BX.CrmHistory);

	BX.CrmFixedHistory.prototype.doInitialize = function()
	{
		var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		this._timeFormat = BX.date.convertBitrixFormat(datetimeFormat);

		var itemData = this.getSetting("itemData");
		if(!BX.type.isArray(itemData))
		{
			itemData = [];
		}

		var i, length, item;
		for(i = 0, length = itemData.length; i < length; i++)
		{
			item = this.createItem(itemData[i]);
			item._isFixed = true;
			this._items.push(item);
		}
	};

	BX.CrmFixedHistory.prototype.setHistory = function(history)
	{
		this._history = history;
	};

	BX.CrmFixedHistory.prototype.checkItemForTermination = function(item)
	{
		return false;
	};

	BX.CrmFixedHistory.prototype.layout = function()
	{
		this._wrapper = BX.create("DIV", {});
		this.createAnchor();
		this._container.insertBefore(this._wrapper,  this._editorContainer.nextElementSibling);

		for (var i = 0; i < this._items.length; i++)
		{
			this._items[i].setContainer(this._wrapper);
			this._items[i].layout();
		}

		this.refreshLayout();

		this._manager.processHistoryLayoutChange();
	};
	BX.CrmFixedHistory.prototype.refreshLayout = function()
	{
	};
	BX.CrmFixedHistory.prototype.formatDate = function(date)
	{
	};
	BX.CrmFixedHistory.prototype.createCurrentDaySection = function()
	{
	};
	BX.CrmFixedHistory.prototype.createDaySection = function(date)
	{
	};
	BX.CrmFixedHistory.prototype.createAnchor = function(index)
	{
		this._anchor = BX.create("DIV", { attrs:{className: "crm-entity-stream-section-fixed-anchor"} });
		this._wrapper.appendChild(this._anchor);
	};
	BX.CrmFixedHistory.prototype.onWindowScroll = function(e)
	{
	};
	BX.CrmFixedHistory.prototype.onItemsLoad = function(sender, result)
	{
	};
	BX.CrmFixedHistory.prototype.loadItems = function()
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
						"LAST_ITEM_TIME": this._lastLoadedItemTimestamp
					}
			}
		).load(BX.delegate(this.onItemsLoad, this));
	};
	BX.CrmFixedHistory.instances = {};
	BX.CrmFixedHistory.create = function(id, settings)
	{
		var self = new BX.CrmFixedHistory();
		self.initialize(id, settings);
		this.instances[self.getId()] = self;
		return self;
	}
}
if(typeof BX.CrmSchedule === "undefined")
{
	BX.CrmSchedule = function()
	{
		BX.CrmSchedule.superclass.constructor.apply(this);
		this._items = [];
		this._history = null;
		this._wrapper = null;
		this._anchor = null;
		this._stub = null;
		this._timeFormat = "";
	};
	BX.extend(BX.CrmSchedule, BX.CrmTimeline);
	BX.CrmSchedule.prototype.doInitialize = function()
	{
		var datetimeFormat = BX.message("FORMAT_DATETIME").replace(/:SS/, "");
		var dateFormat = BX.message("FORMAT_DATE");
		var timeFormat = BX.util.trim(datetimeFormat.replace(dateFormat, ""));
		this._timeFormat = BX.date.convertBitrixFormat(timeFormat);

		if(!this.isStubMode())
		{
			var itemData = this.getSetting("itemData");
			if(!BX.type.isArray(itemData))
			{
				itemData = [];
			}

			var i, length, item;
			for(i = 0, length = itemData.length; i < length; i++)
			{
				item = this.createItem(itemData[i]);
				if(item)
				{
					this._items.push(item);
				}
			}
		}
	};
	BX.CrmSchedule.prototype.layout = function()
	{
		this._wrapper = BX.create("DIV", {});
		this._container.appendChild(this._wrapper);

		var label = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-planned-label" },
				text: this.getMessage("planned")
			}
		);

		var wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned-label";
		this._wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: wrapperClassName },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-section-content" },
									children: [ label ]
								}
							)
						]
				}
			)
		);

		if(this.isStubMode())
		{
			this.addStub();
		}
		else
		{
			var length = this._items.length;
			if(length === 0)
			{
				this.addStub();
			}
			else
			{
				for(var i = 0; i < length; i++)
				{
					var item = this._items[i];
					item.setContainer(this._wrapper);
					item.layout();
				}
			}
		}

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	};
	BX.CrmSchedule.prototype.refreshLayout = function()
	{
		var length = this._items.length;
		if(length === 0)
		{
			this.addStub();
			if(this._history && this._history.hasContent())
			{
				BX.removeClass(this._stub, "crm-entity-stream-section-last");
			}
			else
			{
				BX.addClass(this._stub, "crm-entity-stream-section-last");
			}

			var stubIcon = this._stub.querySelector(".crm-entity-stream-section-icon");
			if(stubIcon)
			{
				if(this._manager.isStubCounterEnabled())
				{
					BX.addClass(stubIcon, "crm-entity-stream-section-counter");
				}
				else
				{
					BX.removeClass(stubIcon, "crm-entity-stream-section-counter");
				}
			}
			return;
		}

		var i, item;
		if(this._history && this._history.hasContent())
		{
			for(i = 0;  i < length; i++)
			{
				item = this._items[i];
				if(item.isTerminated())
				{
					item.markAsTerminated(false);
				}
			}
		}
		else
		{
			if(length > 1)
			{
				for(i = 0;  i < (length - 1); i++)
				{
					item = this._items[i];
					if(item.isTerminated())
					{
						item.markAsTerminated(false);
					}
				}
			}
			this._items[length - 1].markAsTerminated(true);
		}
	};
	BX.CrmSchedule.prototype.formatDateTime = function(time)
	{
		var now = new Date();
		return BX.date.format(
			[
				[ "today", "today, " + this._timeFormat ],
				[ "tommorow", "tommorow, " + this._timeFormat ],
				[ "yesterday", "yesterday, " + this._timeFormat ],
				[ "" , (time.getFullYear() === now.getFullYear() ? "j F " : "j F Y ") + this._timeFormat ]
			],
			time,
			now
		);
	};
	BX.CrmSchedule.prototype.checkItemForTermination = function(item)
	{
		if(this._history && this._history.getItemCount() > 0)
		{
			return false;
		}
		return this.getLastItem() === item;
	};
	BX.CrmSchedule.prototype.getLastItem = function()
	{
		return this._items.length > 0 ? this._items[this._items.length - 1] : null;
	};
	BX.CrmSchedule.prototype.calculateItemIndex = function(item)
	{
		var i, length;
		var time = item.getDeadline();
		if(time)
		{
			//Item has deadline
			for(i = 0, length = this._items.length; i < length; i++)
			{
				var curTime =  this._items[i].getDeadline();
				if(!curTime || time <= curTime)
				{
					return i;
				}
			}
		}
		else
		{
			//Item has't deadline
			var sourceId = item.getSourceId();
			for(i = 0, length = this._items.length; i < length; i++)
			{
				if(this._items[i].getDeadline())
				{
					continue;
				}

				if(sourceId <= this._items[i].getSourceId())
				{
					return i;
				}
			}
		}
		return this._items.length;
	};
	BX.CrmSchedule.prototype.getItemCount = function()
	{
		return this._items.length;
	};
	BX.CrmSchedule.prototype.getItems = function()
	{
		return this._items;
	};
	BX.CrmSchedule.prototype.getItemByAssociatedEntity = function($entityTypeId, entityId)
	{
		if(!BX.type.isNumber($entityTypeId))
		{
			$entityTypeId = parseInt($entityTypeId);
		}

		if(!BX.type.isNumber(entityId))
		{
			entityId = parseInt(entityId);
		}

		if(isNaN($entityTypeId) || $entityTypeId <= 0 || isNaN(entityId) || entityId <= 0)
		{
			return null;
		}

		for(var i = 0, length = this._items.length; i < length; i++)
		{
			var item = this._items[i];
			if(item.getAssociatedEntityTypeId() === $entityTypeId && item.getAssociatedEntityId() === entityId)
			{
				return item;
			}
		}
		return null;
	};
	BX.CrmSchedule.prototype.getItemByData = function(itemData)
	{
		if(!BX.type.isPlainObject(itemData))
		{
			return null;
		}

		return this.getItemByAssociatedEntity(
			BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_TYPE_ID", 0),
			BX.prop.getInteger(itemData, "ASSOCIATED_ENTITY_ID", 0)
		);
	};
	BX.CrmSchedule.prototype.getItemIndex = function(item)
	{
		for(var i = 0, l = this._items.length; i < l; i++)
		{
			if(this._items[i] === item)
			{
				return i;
			}
		}
		return -1;
	};
	BX.CrmSchedule.prototype.getItemByIndex = function(index)
	{
		return index < this._items.length ? this._items[index] : null;
	};
	BX.CrmSchedule.prototype.removeItemByIndex = function(index)
	{
		if(index < this._items.length)
		{
			this._items.splice(index, 1);
		}
	};
	BX.CrmSchedule.prototype.createItem = function(data)
	{
		var entityTypeID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_TYPE_ID", 0);
		var entityID = BX.prop.getInteger(data, "ASSOCIATED_ENTITY_ID", 0);
		var entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
		var itemId = BX.CrmEntityType.resolveName(entityTypeID) + "_" + entityID.toString();

		if(entityTypeID === BX.CrmEntityType.enumeration.wait)
		{
			return BX.CrmScheduleItemWait.create(
				itemId,
				{
					schedule: this,
					container: this._wrapper,
					activityEditor: this._activityEditor,
					data: data
				}
			);
		}
		else// if(entityTypeID === BX.CrmEntityType.enumeration.activity)
		{
			var typeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
			if(typeId === BX.CrmActivityType.email)
			{
				return BX.CrmScheduleItemEmail.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.call)
			{
				return BX.CrmScheduleItemCall.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.meeting)
			{
				return BX.CrmScheduleItemMeeting.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.task)
			{
				return BX.CrmScheduleItemTask.create(
					itemId,
					{
						schedule: this,
						container: this._wrapper,
						activityEditor: this._activityEditor,
						data: data
					}
				);
			}
			else if(typeId === BX.CrmActivityType.provider)
			{
				if(providerId === "CRM_WEBFORM")
				{
					return BX.CrmScheduleItemWebForm.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "CRM_REQUEST")
				{
					return BX.CrmScheduleItemActivityRequest.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "IMOPENLINES_SESSION")
				{
					return BX.CrmScheduleItemActivityOpenLine.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
				else if(providerId === "REST_APP")
				{
					return BX.CrmScheduleItemActivityRestApplication.create(
						itemId,
						{
							schedule: this,
							container: this._wrapper,
							activityEditor: this._activityEditor,
							data: data
						}
					);
				}
			}
		}

		return null;
	};
	BX.CrmSchedule.prototype.addItem = function(item, index)
	{
		if(!BX.type.isNumber(index) || index < 0)
		{
			index = this.calculateItemIndex(item);
		}

		if(index < this._items.length)
		{
			this._items.splice(index, 0, item);
		}
		else
		{
			this._items.push(item);
		}

		this.removeStub();

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	};
	BX.CrmSchedule.prototype.getHistory = function()
	{
		return this._history;
	};
	BX.CrmSchedule.prototype.setHistory = function(history)
	{
		this._history = history;
	};
	BX.CrmSchedule.prototype.createAnchor = function(index)
	{
		this._anchor = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-shadow" } });
		if(index >= 0 && index < this._items.length)
		{
			this._wrapper.insertBefore(this._anchor, this._items[index].getWrapper());
		}
		else
		{
			this._wrapper.appendChild(this._anchor);
		}
		return this._anchor;
	};
	BX.CrmSchedule.prototype.deleteItem = function(item)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		item.clearLayout();
		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();
	};
	BX.CrmSchedule.prototype.refreshItem = function(item)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		this.removeItemByIndex(index);

		var newItem = this.createItem(item.getData());
		var newIndex = this.calculateItemIndex(newItem);
		if(newIndex === index)
		{
			this.addItem(item, newIndex);
			item.refreshLayout();
			item.addWrapperClass("crm-entity-stream-section-updated", 1000);
			return;
		}

		var anchor = this.createAnchor(newIndex);
		this.addItem(newItem, newIndex);
		newItem.layout({ add: false });

		var animation = BX.CrmTimelineItemAnimation.create(
			"",
			{
				initialItem: item,
				finalItem: newItem,
				anchor: anchor
			}
		);
		animation.run();
	};
	BX.CrmSchedule.prototype.transferItemToHistory = function(item, historyItemData)
	{
		var index = this.getItemIndex(item);
		if(index < 0)
		{
			return;
		}

		this.removeItemByIndex(index);

		this.refreshLayout();
		this._manager.processSheduleLayoutChange();

		var historyItem = this._history.createItem(historyItemData);
		this._history.addItem(historyItem, 0);
		historyItem.layout({ add: false });

		var animation = BX.CrmTimelineItemAnimationNew.create(
			"",
			{
				initialItem: item,
				finalItem: historyItem,
				anchor: this._history.createAnchor(),
				events: { complete: BX.delegate(this.onTransferComplete, this) }
			}
		);
		animation.run();
	};
	BX.CrmSchedule.prototype.onTransferComplete = function()
	{
		this._history.refreshLayout();

		if(this._items.length === 0)
		{
			this.addStub();
		}
	};
	BX.CrmSchedule.prototype.onItemMarkedAsDone = function(item, params)
	{
	};
	BX.CrmSchedule.prototype.addStub = function()
	{
		if(!this._stub)
		{
			var stubClassName = "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-notTask";
			var stubIconClassName = "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";

			var stubMessage = this.getMessage("stub");

			var ownerTypeId = this._manager.getOwnerTypeId();
			if(ownerTypeId === BX.CrmEntityType.enumeration.lead)
			{
				stubMessage = this.getMessage("leadStub");
			}
			else if(ownerTypeId === BX.CrmEntityType.enumeration.deal)
			{
				stubMessage = this.getMessage("dealStub");
			}

			if(this._manager.isStubCounterEnabled())
			{
				stubIconClassName += " crm-entity-stream-section-counter";
			}

			this._stub = BX.create("DIV",
				{
					attrs: { className: stubClassName },
					children:
					[
						BX.create("DIV", { attrs: { className: stubIconClassName } }),
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-section-content" },
								children:
								[
									BX.create("DIV",
										{
											attrs: { className: "crm-entity-stream-content-event" },
											children:
											[
												BX.create("DIV",
													{
														attrs: { className: "crm-entity-stream-content-detail" },
														text: stubMessage
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
			this._wrapper.appendChild(this._stub);
		}

		if(this._history && this._history.getItemCount() > 0)
		{
			BX.removeClass(this._stub, "crm-entity-stream-section-last");
		}
		else
		{
			BX.addClass(this._stub, "crm-entity-stream-section-last");
		}
	};
	BX.CrmSchedule.prototype.removeStub = function()
	{
		if(this._stub)
		{
			this._stub = BX.remove(this._stub);
		}

	};
	BX.CrmSchedule.items = {};
	BX.CrmSchedule.prototype.getMessage = function(name)
	{
		var m = BX.CrmSchedule.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmSchedule.messages) === "undefined")
	{
		BX.CrmSchedule.messages = {};
	}
	BX.CrmSchedule.create = function(id, settings)
	{
		var self = new BX.CrmSchedule();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof BX.CrmEntityChatLayoutType === "undefined")
{
	BX.CrmEntityChatLayoutType =
	{
		none: 0,
		invitation:  1,
		summary: 2
	};
}

if(typeof BX.CrmEntityChat === "undefined")
{
	BX.CrmEntityChat = function()
	{
		BX.CrmEntityChat.superclass.constructor.apply(this);
		this._data = null;
		this._layoutType = BX.CrmEntityChatLayoutType.none;

		this._wrapper = null;
		this._contentWrapper = null;
		this._messageWrapper = null;
		this._messageDateNode = null;
		this._messageTexWrapper = null;
		this._messageTextNode = null;
		this._userWrapper = null;
		this._extraUserCounter = null;

		this._openChatHandler = BX.delegate(this.onOpenChat, this);
	};
	BX.extend(BX.CrmEntityChat, BX.CrmTimeline);
	BX.CrmEntityChat.prototype.doInitialize = function()
	{
		this._data = BX.prop.getObject(this._settings, "data", {});
	};
	BX.CrmEntityChat.prototype.getData = function()
	{
		return this._data;
	};
	BX.CrmEntityChat.prototype.setData = function(data)
	{
		this._data = BX.type.isPlainObject(data) ? data : {};
	};
	BX.CrmEntityChat.prototype.isEnabled = function()
	{
		return BX.prop.getBoolean(this._data, "ENABLED", true);
	};
	BX.CrmEntityChat.prototype.getChatId = function()
	{
		return BX.prop.getInteger(this._data, "CHAT_ID", 0);
	};
	BX.CrmEntityChat.prototype.getUserId = function()
	{
		var userId = parseInt(top.BX.message("USER_ID"));
		return !isNaN(userId) ? userId : 0;
	};
	BX.CrmEntityChat.prototype.getMessageData = function()
	{
		return BX.prop.getObject(this._data, "MESSAGE", {});
	};
	BX.CrmEntityChat.prototype.setMessageData = function(data)
	{
		this._data["MESSAGE"] = BX.type.isPlainObject(data) ? data : {};
	};
	BX.CrmEntityChat.prototype.getUserInfoData = function()
	{
		return BX.prop.getObject(this._data, "USER_INFOS", {});
	};
	BX.CrmEntityChat.prototype.setUserInfoData = function(data)
	{
		this._data["USER_INFOS"] = BX.type.isPlainObject(data) ? data : {};
	};
	BX.CrmEntityChat.prototype.hasUserInfo = function(userId)
	{
		return userId > 0 && BX.type.isPlainObject(this.getUserInfoData()[userId]);
	};
	BX.CrmEntityChat.prototype.getUserInfo = function(userId)
	{
		var userInfos = this.getUserInfoData();
		return userId > 0 && BX.type.isPlainObject(userInfos[userId]) ? userInfos[userId] : null;
	};
	BX.CrmEntityChat.prototype.removeUserInfo = function(userId)
	{
		var userInfos = this.getUserInfoData();
		if(userId > 0 && BX.type.isPlainObject(userInfos[userId]))
		{
			delete userInfos[userId];
		}
	};
	BX.CrmEntityChat.prototype.setUnreadMessageCounter = function(userId, counter)
	{
		var userInfos = this.getUserInfoData();
		if(userId > 0 && BX.type.isPlainObject(userInfos[userId]))
		{
			userInfos[userId]["counter"] = counter;
		}
	};
	BX.CrmEntityChat.prototype.layout = function()
	{
		if(!this.isEnabled() || this.isStubMode())
		{
			return;
		}

		this._wrapper = BX.create("div", { props: { className: "crm-entity-stream-section crm-entity-stream-section-live-im" } });
		this._container.appendChild(this._wrapper);

		this._wrapper.appendChild(
			BX.create("div", { props: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-live-im" } })
		);

		this._contentWrapper = BX.create("div", { props: { className: "crm-entity-stream-content-live-im-detail" } });

		this._wrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-section-content" },
					children:
						[
							BX.create("div",
								{
									props: { className: "crm-entity-stream-content-event" },
									children: [ this._contentWrapper ]
								}
							)
						]
				}
			)
		);

		this._userWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-avatars" } });
		this._contentWrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-live-im-users" },
					children: [ this._userWrapper ]
				}
			)
		);

		this._extraUserCounter = BX.create("div",{ props: { className: "crm-entity-stream-live-im-user-counter" } });
		this._contentWrapper.appendChild(this._extraUserCounter);

		this._layoutType = BX.CrmEntityChatLayoutType.none;
		if(this.getChatId() > 0)
		{
			this.renderSummary();
		}
		else
		{
			this.renderInvitation();
		}

		BX.bind(this._contentWrapper, "click", this._openChatHandler);
		BX.addCustomEvent("onPullEvent-im", this.onChatEvent.bind(this));
	};
	BX.CrmEntityChat.prototype.refreshLayout = function()
	{
		BX.cleanNode(this._contentWrapper);
		this._userWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-avatars" } });
		this._contentWrapper.appendChild(
			BX.create("div",
				{
					props: { className: "crm-entity-stream-live-im-users" },
					children: [ this._userWrapper ]
				}
			)
		);

		this._extraUserCounter = BX.create("div",{ props: { className: "crm-entity-stream-live-im-user-counter" } });
		this._contentWrapper.appendChild(this._extraUserCounter);

		this._layoutType = BX.CrmEntityChatLayoutType.none;
		if(this.getChatId() > 0)
		{
			this.renderSummary();
		}
		else
		{
			this.renderInvitation();
		}
	};
	BX.CrmEntityChat.prototype.renderInvitation = function()
	{
		this._layoutType = BX.CrmEntityChatLayoutType.invitation;
		this.refreshUsers();

		this._messageTextNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-user-invite-text" } });
		this._contentWrapper.appendChild(this._messageTextNode);

		this._messageTextNode.innerHTML = this.getMessage("invite");
	};
	BX.CrmEntityChat.prototype.renderSummary = function()
	{
		this._layoutType = BX.CrmEntityChatLayoutType.summary;
		this.refreshUsers();

		this._contentWrapper.appendChild(
			BX.create("div", { props: { className: "crm-entity-stream-live-im-separator" } })
		);

		this._messageWrapper = BX.create("div", { props: { className: "crm-entity-stream-live-im-messanger" } });
		this._contentWrapper.appendChild(this._messageWrapper);

		this._messageDateNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-time" } });
		this._messageWrapper.appendChild(this._messageDateNode);

		this._messageTexWraper = BX.create("div", { props: { className: "crm-entity-stream-live-im-message" } });
		this._messageWrapper.appendChild(this._messageTexWraper);

		this._messageTextNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-message-text" } });
		this._messageTexWraper.appendChild(this._messageTextNode);

		this._messageCounterNode = BX.create("div", { props: { className: "crm-entity-stream-live-im-message-counter" } });
		this._messageWrapper.appendChild(this._messageCounterNode);

		this.refreshSummary();
	};
	BX.CrmEntityChat.prototype.refreshUsers = function()
	{
		BX.cleanNode(this._userWrapper);

		var infos = this.getUserInfoData();
		var list = Object.values(infos);

		if(list.length === 0)
		{
			this._userWrapper.appendChild(
				BX.create("span",
					{
						props: { className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user" },
						children: [ BX.create("i") ]
					}
				)
			);
		}
		else
		{
			var count = list.length >= 3 ? 3 : list.length;
			for(var i = 0; i < count; i++)
			{
				var info = list[i];

				var icon = BX.create("i");
				var imageUrl = BX.prop.getString(info, "avatar", "");
				if(imageUrl !== "")
				{
					icon.style.backgroundImage = "url(" +  imageUrl + ")";
				}

				this._userWrapper.appendChild(
					BX.create("span",
						{
							props: { className: "crm-entity-stream-live-im-user-avatar ui-icon ui-icon-common-user" },
							children: [ icon ]
						}
					)
				);
			}
		}

		if(this._layoutType === BX.CrmEntityChatLayoutType.summary)
		{
			if(list.length > 3)
			{
				this._extraUserCounter.display = "";
				this._extraUserCounter.innerHTML = "+" + (list.length - 3).toString();
			}
			else
			{
				if(this._extraUserCounter.innerHTML !== "")
				{
					this._extraUserCounter.innerHTML = "";
				}
				this._extraUserCounter.display = "none";
			}
		}
		else //if(this._layoutType === BX.CrmEntityChatLayoutType.invitation)
		{
			if(this._extraUserCounter.innerHTML !== "")
			{
				this._extraUserCounter.innerHTML = "";
			}
			this._extraUserCounter.display = "none";

			this._userWrapper.appendChild(
				BX.create("span", { props: { className: "crm-entity-stream-live-im-user-invite-btn" } })
			);
		}
	};
	BX.CrmEntityChat.prototype.refreshSummary = function()
	{
		if(this._layoutType !== BX.CrmEntityChatLayoutType.summary)
		{
			return;
		}

		var message = this.getMessageData();

		//region Message Date
		var isoDate = BX.prop.getString(message, "date", "");
		if(isoDate === "")
		{
			this._messageDateNode.innerHTML = "";
		}
		else
		{
			var remoteDate = (new Date(isoDate)).getTime()/1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
			var localTime = (new Date).getTime()/1000 + this.getServerTimezoneOffset() + this.getUserTimezoneOffset();
			this._messageDateNode.innerHTML = this.formatTime(remoteDate, localTime, true);
		}
		//endregion

		//region Message Text
		var text = BX.prop.getString(message, "text", "");
		var params = BX.prop.getObject(message, "params", {});
		if(text === "")
		{
			this._messageTextNode.innerHTML = "";
		}
		else
		{
			if(typeof(top.BX.MessengerCommon) !== "undefined")
			{
				text = top.BX.MessengerCommon.purifyText(text, params);
			}
			this._messageTextNode.innerHTML = text;
		}
		//endregion

		//region Unread Message Counter
		var counter = 0;
		var userId = this.getUserId();
		if(userId > 0)
		{
			counter = BX.prop.getInteger(
				BX.prop.getObject(
					BX.prop.getObject(this._data, "USER_INFOS", {}),
					userId,
					null
				),
				"counter",
				0
			);
		}

		this._messageCounterNode.innerHTML = counter.toString();
		this._messageCounterNode.style.display = counter > 0 ? "" : "none";
		//endregion
	};
	BX.CrmEntityChat.prototype.refreshUsersAnimated = function()
	{
		BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
		BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');

		window.setTimeout(
			function()
			{
				this.refreshUsers();
				window.setTimeout(
					function()
					{
						BX.removeClass(this._userWrapper, 'crm-entity-stream-live-im-message-hide');
						BX.addClass(this._userWrapper, 'crm-entity-stream-live-im-message-show');
					}.bind(this),
					50
				);
			}.bind(this),
			500
		);
	};
	BX.CrmEntityChat.prototype.refreshSummaryAnimated = function()
	{
		BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
		BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');

		window.setTimeout(
			function()
			{
				this.refreshSummary();
				window.setTimeout(
					function()
					{
						BX.removeClass(this._messageWrapper, 'crm-entity-stream-live-im-message-hide');
						BX.addClass(this._messageWrapper, 'crm-entity-stream-live-im-message-show');
					}.bind(this),
					50
				);
			}.bind(this),
			500
		);
	};
	BX.CrmEntityChat.prototype.onOpenChat = function(e)
	{
		if(typeof(top.BXIM) === "undefined")
		{
			return;
		}

		var slug = "";

		var chatId = this.getChatId();
		if(chatId > 0 && this.hasUserInfo(this.getUserId()))
		{
			slug = "chat" + chatId.toString();
		}
		else
		{
			var ownerInfo = this.getOwnerInfo();
			var entityId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
			var entityTypeName = BX.prop.getString(ownerInfo, "ENTITY_TYPE_NAME", "");

			if(entityTypeName !== "" && entityId > 0)
			{
				slug = "crm|" + entityTypeName + "|" + entityId.toString();
			}
		}

		if(slug !== "")
		{
			top.BXIM.openMessengerSlider(slug, { RECENT: "N", MENU: "N" });
		}
	};
	BX.CrmEntityChat.prototype.onChatEvent = function(command, params, extras)
	{
		var chatId = this.getChatId();
		if(chatId <= 0 || chatId !== BX.prop.getInteger(params, "chatId", 0))
		{
			return;
		}

		if(command === "chatUserAdd")
		{
			this.setUserInfoData(BX.mergeEx(this.getUserInfoData(), BX.prop.getObject(params, "users", {})));
			this.refreshUsersAnimated();
		}
		else if(command === "chatUserLeave")
		{
			this.removeUserInfo(BX.prop.getInteger(params, "userId", 0));
			this.refreshUsersAnimated();
		}
		else if(command === "messageChat")
		{
			//Message was added.
			this.setMessageData(BX.prop.getObject(params, "message", {}));
			this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
			this.refreshSummaryAnimated();
		}
		else if(command === "messageUpdate" || command === "messageDelete")
		{
			//Message was modified or removed.
			if(command === "messageDelete")
			{
				//HACK: date is not in ISO format
				delete params["date"];
			}

			var message = this.getMessageData();
			if(BX.prop.getInteger(message, "id", 0) === BX.prop.getInteger(params, "id", 0))
			{
				this.setMessageData(BX.mergeEx(message, params));
				this.refreshSummaryAnimated();
			}
		}
		else if(command === "readMessageChat")
		{
			this.setUnreadMessageCounter(this.getUserId(), 0);
			this.refreshSummaryAnimated();
		}
		else if(command === "unreadMessageChat")
		{
			this.setUnreadMessageCounter(this.getUserId(), BX.prop.getInteger(params, "counter", 0));
			this.refreshSummaryAnimated();
		}
	};
	BX.CrmEntityChat.items = {};
	BX.CrmEntityChat.prototype.getMessage = function(name)
	{
		return BX.prop.getString(BX.CrmEntityChat.messages, name, name);
	};
	if(typeof(BX.CrmEntityChat.messages) === "undefined")
	{
		BX.CrmEntityChat.messages = {};
	}
	BX.CrmEntityChat.create = function(id, settings)
	{
		var self = new BX.CrmEntityChat();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

//region Editors
if(typeof BX.CrmTimelineBaseEditor === "undefined")
{
	BX.CrmTimelineBaseEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._manager = null;

		this._ownerTypeId = 0;
		this._ownerId = 0;

		this._container = null;
		this._input = null;
		this._saveButton = null;
		this._cancelButton = null;
		this._ghostInput = null;

		this._saveButtonHandler = BX.delegate(this.onSaveButtonClick, this);
		this._cancelButtonHandler = BX.delegate(this.onCancelButtonClick, this);
		this._focusHandler = BX.delegate(this.onFocus, this);
		this._blurHandler = BX.delegate(this.onBlur, this);
		this._keyupHandler = BX.delegate(this.resizeForm, this);

		this._isVisible = true;
		this._hideButtonsOnBlur = true;
	};

	BX.CrmTimelineBaseEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._manager = this.getSetting("manager");
			if(!(this._manager instanceof BX.CrmTimelineManager))
			{
				throw "BX.CrmTimelineBaseEditor. Manager instance is not found.";
			}

			this._ownerTypeId = this.getSetting("ownerTypeId", 0);
			this._ownerId = this.getSetting("ownerId", 0);

			this._container = BX(this.getSetting("container"));
			this._input = BX(this.getSetting("input"));
			this._saveButton = BX(this.getSetting("button"));
			this._cancelButton = BX(this.getSetting("cancelButton"));

			BX.bind(this._saveButton, "click", this._saveButtonHandler);
			if(this._cancelButton)
			{
				BX.bind(this._cancelButton, "click", this._cancelButtonHandler);
			}

			BX.bind(this._input, "focus", this._focusHandler);
			BX.bind(this._input, "blur", this._blurHandler);
			BX.bind(this._input, "keyup", this._keyupHandler);

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setVisible: function(visible)
		{
			visible = !!visible;
			if(this._isVisible === visible)
			{
				return;
			}

			this._isVisible = visible;
			this._container.style.display = visible ? "" : "none";
		},
		isVisible: function()
		{
			return this._isVisible;
		},
		onFocus: function(e)
		{
			BX.addClass(this._container, "focus");
		},
		onBlur: function(e)
		{
			if(!this._hideButtonsOnBlur)
			{
				return;
			}

			if(this._input.value === "")
			{
				window.setTimeout(
					BX.delegate(function() {
						BX.removeClass(this._container, "focus");
						this._input.style.minHeight = "";
					}, this),
					200
				);
			}
		},
		onSaveButtonClick: function(e)
		{
			this.save();
		},
		onCancelButtonClick: function()
		{
			this.cancel();
			this._manager.processEditingCancellation(this);
		},
		save: function()
		{
		},
		cancel: function()
		{
		},
		release: function()
		{
			if(this._ghostInput)
			{
				this._ghostInput = BX.remove(this._ghostInput);
			}
		},
		ensureGhostCreated: function()
		{
			if(this._ghostInput)
			{
				return this._ghostInput;
			}

			this._ghostInput = BX.create('div', {
				props: { className: 'crm-entity-stream-content-new-comment-textarea-shadow' },
				text: this._input.value
			});

			this._ghostInput.style.width = this._input.offsetWidth + 'px';
			document.body.appendChild(this._ghostInput);
			return this._ghostInput;
		},
		resizeForm: function()
		{
			var ghost = this.ensureGhostCreated();
			var computedStyle = getComputedStyle(this._input);
			var diff = parseInt(computedStyle.paddingBottom) +
				parseInt(computedStyle.paddingTop) +
				parseInt(computedStyle.borderTopWidth) +
				parseInt(computedStyle.borderBottomWidth) || 0;

			ghost.innerHTML = BX.util.htmlspecialchars(this._input.value.replace(/[\r\n]{1}/g, '<br>'));
			this._input.style.minHeight = ghost.scrollHeight + diff + 'px'
		}
	};
}

if(typeof BX.CrmTimelineCommentEditor === "undefined")
{
	BX.CrmTimelineCommentEditor = function()
	{
		BX.CrmTimelineCommentEditor.superclass.constructor.apply(this);
		this._history = null;
		this._serviceUrl = "";
		this._postForm = null;
		this._editor = null;
		this._isRequestRunning = false;
		this._isLocked = false;
	};

	BX.extend(BX.CrmTimelineCommentEditor, BX.CrmTimelineBaseEditor);

	BX.CrmTimelineCommentEditor.prototype.doInitialize = function()
	{
		this._serviceUrl = this.getSetting("serviceUrl", "");
		BX.unbind(this._input, "blur", this._blurHandler);
		BX.unbind(this._input, "keyup", this._keyupHandler);
	};
	BX.CrmTimelineCommentEditor.prototype.loadEditor = function()
	{
		this._editorName = 'CrmTimeLineComment0';

		if (this._postForm)
			return;

		BX.ajax.runAction(
			"crm.api.timeline.loadEditor",
			{ data: { name: this._editorName } }
		).then(this.onLoadEditorSuccess.bind(this));
	};
	BX.CrmTimelineCommentEditor.prototype.onLoadEditorSuccess = function(result)
	{
		var html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
		BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor,this));
	};
	BX.CrmTimelineCommentEditor.prototype.showEditor = function()
	{
		if (LHEPostForm)
		{
			window.setTimeout(BX.delegate(function(){
				this._postForm = LHEPostForm.getHandler(this._editorName);
				this._editor = BXHtmlEditor.Get(this._editorName);
				BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
			} ,this), 100);
		}
	};

	BX.CrmTimelineCommentEditor.prototype.getHistory = function()
	{
		return this._history;
	};
	BX.CrmTimelineCommentEditor.prototype.setHistory = function(history)
	{
		this._history = history;
	};
	BX.CrmTimelineCommentEditor.prototype.onFocus = function(e)
	{
		this._input.style.display = 'none';
		if (this._editor && this._postForm)
		{
			this._postForm.eventNode.style.display = 'block';
			this._editor.Focus();
		}
		else
		{
			if (!BX.type.isDomNode(this._editorContainer))
			{
				this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});
				this._editorContainer.appendChild(
					BX.create("DIV",
					{
						attrs: { className: "crm-timeline-wait" }
					})
				);
				this._container.appendChild(this._editorContainer);
			}

			window.setTimeout(BX.delegate(function(){
				this.loadEditor();
			} ,this), 100);
		}

		BX.addClass(this._container, "focus");
	};
	BX.CrmTimelineCommentEditor.prototype.save = function()
	{
		var text = "";
		var attachmentList = [];
		if (this._postForm)
		{
			text = this._postForm.oEditor.GetContent();
			var controllerList = [];
			for (var fileKey in this._postForm.arFiles)
			{
				if (this._postForm.arFiles.hasOwnProperty(fileKey))
				{
					var controllerId = this._postForm.arFiles[fileKey];
					(controllerList.indexOf(controllerId) === -1) ? controllerList.push(controllerId) : null;
				}
			}

			for (var i = 0, length = controllerList.length; i < length; i++)
			{
				for (var fileId in this._postForm.controllers[controllerList[i]].values)
				{
					if (this._postForm.controllers[controllerList[i]].values.hasOwnProperty(fileId)
						&& attachmentList.indexOf(fileId) === -1
					)
					{
						attachmentList.push(fileId);
					}

				}
			}
		}
		else
		{
			text = this._input.value;
		}

		if(text === "")
		{
			if (!this.emptyCommentMessage)
			{
				this.emptyCommentMessage = new BX.PopupWindow(
					'timeline_empty_new_comment_' + this._ownerId,
					this._saveButton,
					{
						content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
						darkMode: true,
						autoHide: true,
						zIndex: 990,
						angle: {position: 'top', offset: 77},
						closeByEsc: true,
						bindOptions: { forceBindPosition: true}
					}
				);
			}

			this.emptyCommentMessage.show();
			return;
		}

		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = this._isLocked = true;
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION": "SAVE_COMMENT",
					"TEXT": text,
					"OWNER_TYPE_ID": this._ownerTypeId,
					"OWNER_ID": this._ownerId,
					"ATTACHMENTS": attachmentList
				},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
	};
	BX.CrmTimelineCommentEditor.prototype.cancel = function()
	{
		this._input.value = "";
		this._input.style.minHeight = "";
		if (BX.type.isDomNode(this._editorContainer))
			this._postForm.eventNode.style.display = 'none';

		this._input.style.display = 'block';
		BX.removeClass(this._container, "focus");
		this.release();
	};
	BX.CrmTimelineCommentEditor.prototype.onSaveSuccess = function(data)
	{
		this._isRequestRunning = false;
		if (this._postForm)
		{
			this._postForm.reinit();
			for (var cid in this._postForm.controllers)
			{
				if (this._postForm.controllers.hasOwnProperty(cid))
				{
					this._postForm.controllers[cid].values = {};
				}
			}
		}

		this.cancel();
		var itemData = BX.prop.getObject(data, "HISTORY_ITEM");
		var historyItem = this._history.createItem(itemData);
		this._history.addItem(historyItem, 0);

		var anchor = this._history.createAnchor();
		historyItem.layout({ anchor: anchor });

		var move = BX.CrmCommentAnimation.create(
			historyItem.getWrapper(),
			anchor,
			BX.pos(this._input),
			{
				start: BX.delegate(this.onAnimationStart, this),
				complete: BX.delegate(this.onAnimationComplete, this)
			}
		);
		move.run();
	};
	BX.CrmTimelineCommentEditor.prototype.onSaveFailure = function()
	{
		this._isRequestRunning = this._isLocked = false;
	};
	BX.CrmTimelineCommentEditor.prototype.onAnimationStart = function()
	{
		this._input.value = "";
	};
	BX.CrmTimelineCommentEditor.prototype.onAnimationComplete = function()
	{
		this._isLocked = false;
		BX.removeClass(this._container, "focus");

		this._input.style.minHeight = "";
		this._manager.processEditingCompletion(this);

		this.release();

		this._history._anchor = null;
		this._history.refreshLayout();
	};
	BX.CrmTimelineCommentEditor.items = {};
	BX.CrmTimelineCommentEditor.create = function(id, settings)
	{
		var self = new BX.CrmTimelineCommentEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmTimelineWaitType) === "undefined")
{
	BX.CrmTimelineWaitType =
	{
		undefined: 0,
		after: 1,
		before: 2,

		names:
		{
			after: "after",
			before: "before"
		},

		resolveTypeId: function(name)
		{
			if(name === this.names.after)
			{
				return this.after;
			}
			else if(name === this.names.before)
			{
				return this.before;
			}

			return this.undefined;
		}
	};
}

if(typeof BX.CrmTimelineWaitHelper === "undefined")
{
	BX.CrmTimelineWaitHelper =
	{
		getDurationText: function(duration, enableNumber)
		{
			enableNumber = !!enableNumber;

			var type = "D";
			if(enableNumber)
			{
				var result = "";
				if((duration % 7) === 0)
				{
					duration = duration / 7;
					type = "W";
				}
			}

			if(type === "W")
			{
				result = BX.CrmMessageHelper.getCurrent().getNumberDeclension(
					duration,
					this.getMessage("weekNominative"),
					this.getMessage("weekGenitiveSingular"),
					this.getMessage("weekGenitivePlural")
				);
			}
			else
			{
				result = BX.CrmMessageHelper.getCurrent().getNumberDeclension(
					duration,
					this.getMessage("dayNominative"),
					this.getMessage("dayGenitiveSingular"),
					this.getMessage("dayGenitivePlural")
				);
			}

			if(enableNumber)
			{
				result = duration.toString() + " " + result;
			}
			return result;
		},
		getMessage: function(name)
		{
			return this.messages.hasOwnProperty(name) ? this.messages[name] : name;
		}
	};

	if(typeof(BX.CrmTimelineWaitHelper.messages) === "undefined")
	{
		BX.CrmTimelineWaitHelper.messages = {};
	}
}

if(typeof BX.CrmTimelineWaitEditor === "undefined")
{
	BX.CrmTimelineWaitEditor = function()
	{
		BX.CrmTimelineWaitEditor.superclass.constructor.apply(this);
		this._serviceUrl = "";
		this._isRequestRunning = false;
		this._isLocked = false;

		this._hideButtonsOnBlur = false;
		//region Config
		this._type = BX.CrmTimelineWaitType.after;
		this._duration = 1;
		this._target = "";
		this._configContainer = null;
		this._configSelector = null;
		//endregion

		this._isMenuShown = false;
		this._menu = null;
		this._configDialog = null;
	};

	BX.extend(BX.CrmTimelineWaitEditor, BX.CrmTimelineBaseEditor);

	BX.CrmTimelineWaitEditor.prototype.doInitialize = function()
	{
		this._configContainer = BX(this.getSetting("configContainer"));
		this._serviceUrl = this.getSetting("serviceUrl", "");

		var config = BX.prop.getObject(this._settings, "config", {});
		this._type = BX.CrmTimelineWaitType.resolveTypeId(
			BX.prop.getString(
				config,
				"type",
				BX.CrmTimelineWaitType.names.after
			)
		);
		this._duration = BX.prop.getInteger(config, "duration", 1);
		this._target = BX.prop.getString(config, "target", "");
		this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);
		this.layoutConfigurationSummary();
	};
	BX.CrmTimelineWaitEditor.prototype.getDurationText = function(duration, enableNumber)
	{
		return BX.CrmTimelineWaitHelper.getDurationText(duration, enableNumber);
	};
	BX.CrmTimelineWaitEditor.prototype.getTargetDateCaption = function(name)
	{
		for(var i = 0, length = this._targetDates.length; i < length; i++)
		{
			var info = this._targetDates[i];
			if(info["name"] === name)
			{
				return info["caption"];
			}
		}

		return "";
	};
	BX.CrmTimelineWaitEditor.prototype.onSelectorClick = function(e)
	{
		if(!this._isMenuShown)
		{
			this.openMenu();
		}
		else
		{
			this.closeMenu();
		}
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	};
	BX.CrmTimelineWaitEditor.prototype.openMenu = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		var handler = BX.delegate(this.onMenuItemClick, this);

		var menuItems =
			[
				{ id: "day_1", text: this.getMessage("oneDay"), onclick: handler },
				{ id: "day_2", text: this.getMessage("twoDays"), onclick: handler },
				{ id: "day_3", text: this.getMessage("threeDays"), onclick: handler },
				{ id: "week_1", text: this.getMessage("oneWeek"), onclick: handler },
				{ id: "week_2", text: this.getMessage("twoWeek"), onclick: handler },
				{ id: "week_3", text: this.getMessage("threeWeeks"), onclick: handler }
			];

		var customMenu = { id: "custom", text: this.getMessage("custom"), items: [] };
		customMenu["items"].push({ id: "afterDays", text: this.getMessage("afterDays"), onclick: handler });
		if(this._targetDates.length > 0)
		{
			customMenu["items"].push({ id: "beforeDate", text: this.getMessage("beforeDate"), onclick: handler });
		}
		menuItems.push(customMenu);

		BX.PopupMenu.show(
			this._id,
			this._configSelector,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
				{
					onPopupShow: BX.delegate(this.onMenuShow, this),
					onPopupClose: BX.delegate(this.onMenuClose, this),
					onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
				}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
	};
	BX.CrmTimelineWaitEditor.prototype.closeMenu = function()
	{
		if(!this._isMenuShown)
		{
			return;
		}

		if(this._menu)
		{
			this._menu.close();
		}
	};
	BX.CrmTimelineWaitEditor.prototype.onMenuItemClick = function(e, item)
	{
		this.closeMenu();

		if(item.id === "afterDays" || item.id === "beforeDate")
		{
			this.openConfigDialog(
				item.id === "afterDays" ? BX.CrmTimelineWaitType.after : BX.CrmTimelineWaitType.before
			);
			return;
		}

		var params = { type: BX.CrmTimelineWaitType.after };
		if(item.id === "day_1")
		{
			params["duration"] = 1;
		}
		else if(item.id === "day_2")
		{
			params["duration"] = 2;
		}
		else if(item.id === "day_3")
		{
			params["duration"] = 3;
		}
		if(item.id === "week_1")
		{
			params["duration"] = 7;
		}
		else if(item.id === "week_2")
		{
			params["duration"] = 14;
		}
		else if(item.id === "week_3")
		{
			params["duration"] = 21;
		}
		this.saveConfiguration(params);
	};
	BX.CrmTimelineWaitEditor.prototype.openConfigDialog = function(type)
	{
		if(!this._configDialog)
		{
			this._configDialog = BX.CrmTimelineWaitConfigurationDialog.create(
				"",
				{
					targetDates: this._targetDates,
					onSave: BX.delegate(this.onConfigDialogSave, this),
					onCancel: BX.delegate(this.onConfigDialogCancel, this)
				}
			);
		}

		this._configDialog.setType(type);
		this._configDialog.setDuration(this._duration);

		var target = this._target;
		if(target === "" && this._targetDates.length > 0)
		{
			target = this._targetDates[0]["name"];
		}
		this._configDialog.setTarget(target);
		this._configDialog.open();
	};
	BX.CrmTimelineWaitEditor.prototype.onConfigDialogSave = function(sender, params)
	{
		this.saveConfiguration(params);
		this._configDialog.close();
	};
	BX.CrmTimelineWaitEditor.prototype.onConfigDialogCancel = function(sender)
	{
		this._configDialog.close();
	};
	BX.CrmTimelineWaitEditor.prototype.onMenuShow = function()
	{
		this._isMenuShown = true;
	};
	BX.CrmTimelineWaitEditor.prototype.onMenuClose = function()
	{
		if(this._menu && this._menu.popupWindow)
		{
			this._menu.popupWindow.destroy();
		}
	};
	BX.CrmTimelineWaitEditor.prototype.onMenuDestroy = function()
	{
		this._isMenuShown = false;
		this._menu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	};
	BX.CrmTimelineWaitEditor.prototype.saveConfiguration = function(params)
	{
		//region Parse params
		this._type = BX.prop.getInteger(params, "type", BX.CrmTimelineWaitType.after);
		this._duration = BX.prop.getInteger(params, "duration", 0);
		if(this._duration <= 0)
		{
			this._duration = 1;
		}
		this._target = this._type === BX.CrmTimelineWaitType.before
			? BX.prop.getString(params, "target", "") : "";
		//endregion
		//region Save settings
		var optionName = this._manager.getId().toLowerCase();
		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"type",
			this._type === BX.CrmTimelineWaitType.after ? "after" : "before"
		);

		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"duration",
			this._duration
		);

		BX.userOptions.save(
			"crm.timeline.wait",
			optionName,
			"target",
			this._target
		);
		//endregion
		this.layoutConfigurationSummary();
	};
	BX.CrmTimelineWaitEditor.prototype.getSummaryHtml = function()
	{
		if(this._type === BX.CrmTimelineWaitType.before)
		{
			return (
				this.getMessage("completionTypeBefore")
					.replace("#DURATION#", this.getDurationText(this._duration, true))
					.replace("#TARGET_DATE#", this.getTargetDateCaption(this._target))
			);
		}

		return (
			this.getMessage("completionTypeAfter")
				.replace("#DURATION#", this.getDurationText(this._duration, true))
		);
	};
	BX.CrmTimelineWaitEditor.prototype.getSummaryText = function()
	{
		return BX.util.strip_tags(this.getSummaryHtml());
	};
	BX.CrmTimelineWaitEditor.prototype.layoutConfigurationSummary = function()
	{
		this._configContainer.innerHTML = this.getSummaryHtml();
		this._configSelector = this._configContainer.querySelector("a");
		if(this._configSelector)
		{
			BX.bind(this._configSelector, "click", BX.delegate(this.onSelectorClick, this));
		}
	};
	BX.CrmTimelineWaitEditor.prototype.postpone = function(id, offset, callback)
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION": "POSTPONE_WAIT",
					"DATA": { "ID": id, "OFFSET": offset }
				},
				onsuccess: callback
			}
		);
	};
	BX.CrmTimelineWaitEditor.prototype.complete = function(id, completed, callback)
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION": "COMPLETE_WAIT",
					"DATA": { "ID": id, "COMPLETED": completed ? 'Y' : 'N' }
				},
				onsuccess: callback
			}
		);
	};
	BX.CrmTimelineWaitEditor.prototype.save = function()
	{
		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		var description = this.getSummaryText();
		var comment = BX.util.trim(this._input.value);
		if(comment !== "")
		{
			description += "\n" + comment;
 		}

		var data =
			{
				ID: 0,
				typeId: this._type,
				duration: this._duration,
				targetFieldName: this._target,
				subject: "",
				description: description,
				completed: 0,
				ownerType: BX.CrmEntityType.resolveName(this._ownerTypeId),
				ownerID: this._ownerId
			};

		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION": "SAVE_WAIT",
					"DATA": data
				},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
		this._isRequestRunning = this._isLocked = true;
	};
	BX.CrmTimelineWaitEditor.prototype.cancel = function()
	{
		this._input.value = "";
		this._input.style.minHeight = "";
		this.release();
	};
	BX.CrmTimelineWaitEditor.prototype.onSaveSuccess = function(data)
	{
		this._isRequestRunning = this._isLocked = false;

		var error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this._input.value = "";
		this._input.style.minHeight = "";
		this._manager.processEditingCompletion(this);
		this.release();
	};
	BX.CrmTimelineWaitEditor.prototype.onSaveFailure = function()
	{
		this._isRequestRunning = this._isLocked = false;
	};
	BX.CrmTimelineWaitEditor.prototype.getMessage = function(name)
	{
		var m = BX.CrmTimelineWaitEditor.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmTimelineWaitEditor.messages) === "undefined")
	{
		BX.CrmTimelineWaitEditor.messages = {};
	}
	BX.CrmTimelineWaitEditor.items = {};
	BX.CrmTimelineWaitEditor.create = function(id, settings)
	{
		var self = new BX.CrmTimelineWaitEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof BX.CrmTimelineSmsEditor === "undefined")
{
	BX.CrmTimelineSmsEditor = function()
	{
		BX.CrmTimelineSmsEditor.superclass.constructor.apply(this);
		this._history = null;
		this._serviceUrl = "";

		this._isRequestRunning = false;
		this._isLocked = false;

		this._senderId = null;
		this._from = null;
		this._commEntityTypeId = null;
		this._commEntityId = null;
		this._to = null;

		this._canUse = null;
		this._canSendMessage = null;
		this._manageUrl = '';
		this._senders = [];
		this._fromList = [];
		this._toList = [];
		this._defaults = {};
		this._communications = [];

		this._menu = null;
		this._isMenuShown = false;
		this._shownMenuId = null;
		this._documentSelector = null;
	};
	BX.extend(BX.CrmTimelineSmsEditor, BX.CrmTimelineBaseEditor);
	BX.CrmTimelineSmsEditor.prototype.doInitialize = function()
	{
		this._serviceUrl = BX.util.remove_url_param(
			this.getSetting("serviceUrl", ""),
			['sessid', 'site']
		);

		var config = BX.prop.getObject(this._settings, "config", {});

		this._canUse = BX.prop.getBoolean(config, "canUse", false);
		this._canSendMessage = BX.prop.getBoolean(config, "canSendMessage", false);
		this._manageUrl = BX.prop.getString(config, "manageUrl", '');
		this._senders = BX.prop.getArray(config, "senders", []);
		this._defaults = BX.prop.getObject(config, "defaults", {senderId:null,from:null});
		this._communications = BX.prop.getArray(config, "communications", []);
		this._isSalescenterEnabled = BX.prop.getBoolean(config, "isSalescenterEnabled", false);
		this._isDocumentsEnabled = BX.prop.getBoolean(config, "isDocumentsEnabled", false);
		if(this._isDocumentsEnabled)
		{
			this._documentsProvider = BX.prop.getString(config, "documentsProvider", '');
			this._documentsValue = BX.prop.getString(config, "documentsValue", '');
		}
		this._isFilesEnabled = BX.prop.getBoolean(config, "isFilesEnabled", false);
		if(this._isFilesEnabled)
		{
			this._diskUrls = BX.prop.getObject(config, "diskUrls");
			this._isFilesExternalLinkEnabled = BX.prop.getBoolean(config, "isFilesExternalLinkEnabled", true);
		}

		this._senderSelectorNode = this._container.querySelector('[data-role="sender-selector"]');
		this._fromContainerNode = this._container.querySelector('[data-role="from-container"]');
		this._fromSelectorNode = this._container.querySelector('[data-role="from-selector"]');
		this._clientContainerNode = this._container.querySelector('[data-role="client-container"]');
		this._clientSelectorNode = this._container.querySelector('[data-role="client-selector"]');
		this._toSelectorNode = this._container.querySelector('[data-role="to-selector"]');
		this._messageLengthCounterNode = this._container.querySelector('[data-role="message-length-counter"]');
		this._salescenterStarter = this._container.querySelector('[data-role="salescenter-starter"]');
		this._smsDetailSwitcher = this._container.querySelector('[data-role="sms-detail-switcher"]');
		this._smsDetail = this._container.querySelector('[data-role="sms-detail"]');
		this._documentSelectorButton = this._container.querySelector('[data-role="sms-document-selector"]');
		this._fileSelectorButton = this._container.querySelector('[data-role="sms-file-selector"]');
		this._fileUploadZone = this._container.querySelector('[data-role="sms-file-upload-zone"]');
		this._fileUploadLabel = this._container.querySelector('[data-role="sms-file-upload-label"]');
		this._fileSelectorBitrix = this._container.querySelector('[data-role="sms-file-selector-bitrix"]');
		this._fileExternalLinkDisabledContent = this._container.querySelector('[data-role="sms-file-external-link-disabled"]');

		if (this._canUse && this._canSendMessage)
		{
			this.initDetailSwitcher();
			this.initSenderSelector();
			this.initFromSelector();
			this.initClientContainer();
			this.initClientSelector();
			this.initToSelector();
			this.initMessageLengthCounter();
			this.setMessageLengthCounter();
			if(this._isDocumentsEnabled)
			{
				this.initDocumentSelector();
			}
			if(this._isFilesEnabled)
			{
				this.initFileSelector();
			}
		}

		if(this._isSalescenterEnabled)
		{
			this.initSalescenterApplication();
		}
	};

	BX.CrmTimelineSmsEditor.prototype.initDetailSwitcher = function()
	{
		BX.bind(this._smsDetailSwitcher, 'click', function()
		{
			if(this._smsDetail.classList.contains('hidden'))
			{
				this._smsDetail.classList.remove('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_COLLAPSE');
			}
			else
			{
				this._smsDetail.classList.add('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_DETAILS');
			}
		}.bind(this));
	};

	BX.CrmTimelineSmsEditor.prototype.initSenderSelector = function()
	{
		var defaultSenderId = this._defaults.senderId ;
		var defaultSender = this._senders[0].canUse ? this._senders[0] : null;
		var restSender = null;
		var menuItems = [];
		var handler = this.onSenderSelectorClick.bind(this);

		for (var i = 0; i < this._senders.length; ++i)
		{
			if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender))
			{
				defaultSender = this._senders[i];
			}

			if (this._senders[i].id === 'rest')
			{
				restSender = this._senders[i];
				continue;
			}

			menuItems.push({
				text: this._senders[i].name,
				sender: this._senders[i],
				onclick: handler,
				className: (!this._senders[i].canUse || !this._senders[i].fromList.length)
					? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : ''
			});
		}

		if (restSender)
		{
			if (restSender.fromList.length > 0)
			{
				menuItems.push({delimiter: true});
				for (i = 0; i < restSender.fromList.length; ++i)
				{
					menuItems.push({
						text: restSender.fromList[i].name,
						sender: restSender,
						from: restSender.fromList[i],
						onclick: handler
					});
				}
			}
			menuItems.push({delimiter: true}, {
				text: BX.message('CRM_TIMELINE_SMS_REST_MARKETPLACE'),
				href: '/marketplace/category/crm_robot_sms/',
				target: '_blank'
			});
		}

		if (defaultSender)
		{
			this.setSender(defaultSender);
		}

		BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	};
	BX.CrmTimelineSmsEditor.prototype.onSenderSelectorClick = function(e, item)
	{
		if (item.sender)
		{
			if (!item.sender.canUse || !item.sender.fromList.length)
			{
				window.open(item.sender.manageUrl);
				return;
			}

			this.setSender(item.sender, true);
			var from = item.from ? item.from : item.sender.fromList[0];
			this.setFrom(from, true);
		}
		this._menu.close();
	};
	BX.CrmTimelineSmsEditor.prototype.setSender = function(sender, setAsDefault)
	{
		this._senderId = sender.id;
		this._fromList = sender.fromList;
		this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;

		var visualFn = sender.id === 'rest' ? 'hide' : 'show';
		BX[visualFn](this._fromContainerNode);

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
		}
	};
	BX.CrmTimelineSmsEditor.prototype.initFromSelector = function()
	{
		if (this._fromList.length > 0)
		{
			var defaultFromId = this._defaults.from || this._fromList[0].id;
			var defaultFrom = null;
			for (var i = 0; i < this._fromList.length; ++i)
			{
				if (this._fromList[i].id === defaultFromId || !defaultFrom)
				{
					defaultFrom = this._fromList[i];
				}
			}
			if (defaultFrom)
			{
				this.setFrom(defaultFrom);
			}
		}

		BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	};
	BX.CrmTimelineSmsEditor.prototype.onFromSelectorClick = function(e)
	{
		var menuItems = [];
		var handler = this.onFromSelectorItemClick.bind(this);

		for (var i = 0; i < this._fromList.length; ++i)
		{
			menuItems.push({
				text: this._fromList[i].name,
				from: this._fromList[i],
				onclick: handler
			});
		}

		this.openMenu('from_'+this._senderId, this._fromSelectorNode, menuItems, e);
	};
	BX.CrmTimelineSmsEditor.prototype.onFromSelectorItemClick = function(e, item)
	{
		if (item.from)
		{
			this.setFrom(item.from, true);
		}
		this._menu.close();
	};
	BX.CrmTimelineSmsEditor.prototype.setFrom = function(from, setAsDefault)
	{
		this._from = from.id;

		if (this._senderId === 'rest')
		{
			this._senderSelectorNode.textContent = from.name;
		}
		else
		{
		this._fromSelectorNode.textContent = from.name;
		}

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
		}
	};
	BX.CrmTimelineSmsEditor.prototype.initClientContainer = function()
	{
		if (this._communications.length === 0)
		{
			BX.hide(this._clientContainerNode);
		}
	};
	BX.CrmTimelineSmsEditor.prototype.initClientSelector = function()
	{
		var menuItems = [];
		var handler = this.onClientSelectorClick.bind(this);

		for (var i = 0; i < this._communications.length; ++i)
		{
			menuItems.push({
				text: this._communications[i].caption,
				client: this._communications[i],
				onclick: handler
			});
			if (i === 0)
			{
				this.setClient(this._communications[i]);
			}
		}

		BX.bind(this._clientSelectorNode, 'click', this.openMenu.bind(this, 'comm', this._clientSelectorNode, menuItems));
	};
	BX.CrmTimelineSmsEditor.prototype.onClientSelectorClick = function(e, item)
	{
		if (item.client)
		{
			this.setClient(item.client);
		}
		this._menu.close();
	};
	BX.CrmTimelineSmsEditor.prototype.setClient = function(client)
	{
		this._commEntityTypeId = client.entityTypeId;
		this._commEntityId = client.entityId;
		this._clientSelectorNode.textContent = client.caption;
		this._toList = client.phones;
		this.setTo(client.phones[0]);
	};
	BX.CrmTimelineSmsEditor.prototype.initToSelector = function()
	{
		BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	};
	BX.CrmTimelineSmsEditor.prototype.onToSelectorClick = function(e)
	{
		var menuItems = [];
		var handler = this.onToSelectorItemClick.bind(this);

		for (var i = 0; i < this._toList.length; ++i)
		{
			menuItems.push({
				text: this._toList[i].valueFormatted || this._toList[i].value,
				to: this._toList[i],
				onclick: handler
			});
		}

		this.openMenu('to_'+this._commEntityTypeId+'_'+this._commEntityId, this._toSelectorNode, menuItems, e);
	};
	BX.CrmTimelineSmsEditor.prototype.onToSelectorItemClick = function(e, item)
	{
		if (item.to)
		{
			this.setTo(item.to);
		}
		this._menu.close();
	};
	BX.CrmTimelineSmsEditor.prototype.setTo = function(to)
	{
		this._to = to.value;
		this._toSelectorNode.textContent = to.valueFormatted || to.value;
	};
	BX.CrmTimelineSmsEditor.prototype.openMenu = function(menuId, bindElement, menuItems, e)
	{
		if (this._shownMenuId === menuId)
		{
			return;
		}

		if(this._shownMenuId !== null && this._menu)
		{
			this._menu.close();
			this._shownMenuId = null;
		}

		BX.PopupMenu.show(
			this._id + menuId,
			bindElement,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupClose: BX.delegate(this.onMenuClose, this)
					}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
		e.preventDefault();
	};
	BX.CrmTimelineSmsEditor.prototype.onMenuClose = function()
	{
		this._shownMenuId = null;
		this._menu = null;
	};
	BX.CrmTimelineSmsEditor.prototype.initMessageLengthCounter = function()
	{
		this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
		BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
	};
	BX.CrmTimelineSmsEditor.prototype.setMessageLengthCounter = function()
	{
		var length = this._input.value.length;
		this._messageLengthCounterNode.textContent = length;

		var classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
		BX[classFn](this._messageLengthCounterNode, 'crm-entity-stream-content-sms-symbol-counter-number-overhead');

		classFn = length <= 0 ? 'addClass' : 'removeClass';
		BX[classFn](this._saveButton, 'ui-btn-disabled');
	};
	BX.CrmTimelineSmsEditor.prototype.save = function()
	{
		var text = this._input.value;
		if(text === "")
		{
			return;
		}

		if (!this._communications.length)
		{
			alert(BX.message('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));
			return;
		}

		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = this._isLocked = true;
		BX.ajax(
			{
				url: BX.util.add_url_param(this._serviceUrl, {
					"action": "save_sms_message",
					"sender": this._senderId
				}),
				method: "POST",
				dataType: "json",
				data:
					{
						'site': BX.message('SITE_ID'),
						'sessid': BX.bitrix_sessid(),
						"ACTION": "SAVE_SMS_MESSAGE",
						"SENDER_ID": this._senderId,
						"MESSAGE_FROM": this._from,
						"MESSAGE_TO": this._to,
						"MESSAGE_BODY": text,
						"OWNER_TYPE_ID": this._ownerTypeId,
						"OWNER_ID": this._ownerId,
						"TO_ENTITY_TYPE_ID": this._commEntityTypeId,
						"TO_ENTITY_ID": this._commEntityId
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
	};
	BX.CrmTimelineSmsEditor.prototype.cancel = function()
	{
		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this.release();
	};
	BX.CrmTimelineSmsEditor.prototype.onSaveSuccess = function(data)
	{
		this._isRequestRunning = this._isLocked = false;

		var error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this._manager.processEditingCompletion(this);
		this.release();
	};
	BX.CrmTimelineSmsEditor.prototype.onSaveFailure = function()
	{
		this._isRequestRunning = this._isLocked = false;
	};
	BX.CrmTimelineSmsEditor.prototype.initSalescenterApplication = function()
	{
		BX.bind(this._salescenterStarter, 'click', this.startSalescenterApplication.bind(this));
	};
	BX.CrmTimelineSmsEditor.prototype.startSalescenterApplication = function()
	{
		BX.loadExt('salescenter.manager').then(function()
		{
			BX.Salescenter.Manager.openApplication({
				disableSendButton: this._canSendMessage ? '' : 'y',
				context: 'sms',
				ownerTypeId: this._ownerTypeId,
				ownerId: this._ownerId
			}).then(function(result)
			{
				if(result && result.get('action'))
				{
					if(result.get('action') === 'sendPage' && result.get('page') && result.get('page').url)
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('page').name + ' ' + result.get('page').url;
						this.setMessageLengthCounter();
					}
					else if (result.get('action') === 'sendPayment' && result.get('order'))
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('order').title + ' ' + result.get('order').url;
						this.setMessageLengthCounter();
					}
				}
			}.bind(this));
		}.bind(this));
	};
	BX.CrmTimelineSmsEditor.prototype.initDocumentSelector = function()
	{
		BX.bind(this._documentSelectorButton, 'click', this.onDocumentSelectorClick.bind(this));
	};

	BX.CrmTimelineSmsEditor.prototype.onDocumentSelectorClick = function()
	{
		if(!this._documentSelector)
		{
			BX.loadExt('documentgenerator.selector').then(function()
			{
				this._documentSelector = new BX.DocumentGenerator.Selector.Menu({
					node: this._documentSelectorButton,
					moduleId: 'crm',
					provider: this._documentsProvider,
					value: this._documentsValue,
					analyticsLabelPrefix: 'crmTimelineSmsEditor'
				});
				this.selectPublicUrl();
			}.bind(this));
		}
		else
		{
			this.selectPublicUrl();
		}
	};

	BX.CrmTimelineSmsEditor.prototype.selectPublicUrl = function()
	{
		if(!this._documentSelector)
		{
			return;
		}
		this._documentSelector.show().then(function(object)
		{
			if(object instanceof BX.DocumentGenerator.Selector.Template)
			{
				this._documentSelector.createDocument(object).then(function(document)
				{
					this.pasteDocumentUrl(document);
				}.bind(this)).catch(function(error)
				{
					console.error(error);
				}.bind(this));
			}
			else if(object instanceof BX.DocumentGenerator.Selector.Document)
			{
				this.pasteDocumentUrl(object);
			}
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	};

	BX.CrmTimelineSmsEditor.prototype.pasteDocumentUrl = function(document)
	{
		this._documentSelector.getDocumentPublicUrl(document).then(function(publicUrl)
		{
			this._input.focus();
			this._input.value = this._input.value + ' ' + document.getTitle() + ' ' + publicUrl;
			this.setMessageLengthCounter();
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	};

	BX.CrmTimelineSmsEditor.prototype.initFileSelector = function()
	{
		BX.bind(this._fileSelectorButton, 'click', this.onFileSelectorClick.bind(this));
	};

	BX.CrmTimelineSmsEditor.prototype.closeFileSelector = function()
	{
		BX.PopupMenu.destroy('sms-file-selector');
	};

	BX.CrmTimelineSmsEditor.prototype.onFileSelectorClick = function()
	{
		BX.PopupMenu.show('sms-file-selector', this._fileSelectorButton, [
			{
				text: BX.message('CRM_TIMELINE_SMS_UPLOAD_FILE'),
				onclick: this.uploadFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			},
			{
				text: BX.message('CRM_TIMELINE_SMS_FIND_FILE'),
				onclick: this.findFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			}
		])
	};

	BX.CrmTimelineSmsEditor.prototype.getFileUploadInput = function()
	{
		return document.getElementById(this._fileUploadLabel.getAttribute('for'));
	};

	BX.CrmTimelineSmsEditor.prototype.uploadFile = function()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this.getFileUploadInput(), 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	};

	BX.CrmTimelineSmsEditor.prototype.findFile = function()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this._fileSelectorBitrix, 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	};

	BX.CrmTimelineSmsEditor.prototype.getLoader = function()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader(
			{
				size: 50
			});
		}

		return this.loader;
	};

	BX.CrmTimelineSmsEditor.prototype.showLoader = function(node)
	{
		if(node && !this.getLoader().isShown())
		{
			this.getLoader().show(node);
		}
	};

	BX.CrmTimelineSmsEditor.prototype.hideLoader = function()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	};

	BX.CrmTimelineSmsEditor.prototype.initDiskUF = function()
	{
		if(this.isDiskFileUploaderInited || !this._isFilesEnabled)
		{
			return;
		}
		this.isDiskFileUploaderInited = true;
		BX.addCustomEvent(this._fileUploadZone, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
		BX.addCustomEvent(this._fileUploadZone, 'DiskDLoadFormControllerInit', function(uf)
		{
			uf._onUploadProgress = function()
			{
				this.showLoader(this._fileSelectorButton.parentNode.parentNode);
			}.bind(this);
		}.bind(this));

		BX.Disk.UF.add({
			UID: this._fileUploadZone.getAttribute('data-node-id'),
			controlName: this._fileUploadLabel.getAttribute('for'),
			hideSelectDialog: false,
			urlSelect: this._diskUrls.urlSelect,
			urlRenameFile: this._diskUrls.urlRenameFile,
			urlDeleteFile: this._diskUrls.urlDeleteFile,
			urlUpload: this._diskUrls.urlUpload
		});

		BX.onCustomEvent(
			this._fileUploadZone,
			'DiskLoadFormController',
			['show']
		);
	};

	BX.CrmTimelineSmsEditor.prototype.OnFileUploadSuccess = function(fileResult, uf, file, uploaderFile)
	{
		this.hideLoader();
		var diskFileId = parseInt(fileResult.element_id.replace('n', ''));
		var fileName = fileResult.element_name;
		this.pasteFileUrl(diskFileId, fileName);
	};

	BX.CrmTimelineSmsEditor.prototype.pasteFileUrl = function(diskFileId, fileName)
	{
		this.showLoader(this._fileSelectorButton.parentNode.parentNode);
		BX.ajax.runAction('disk.file.generateExternalLink', {
			analyticsLabel: 'crmTimelineSmsEditorGetFilePublicUrl',
			data: {
				fileId: diskFileId
			}
		}).then(function(response)
		{
			this.hideLoader();
			if(response.data.externalLink && response.data.externalLink.link)
			{
				this._input.focus();
				this._input.value = this._input.value + ' ' + fileName + ' ' + response.data.externalLink.link;
				this.setMessageLengthCounter();
			}
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors.pop().message);
		});
	};

	BX.CrmTimelineSmsEditor.prototype.getFeaturePopup = function(content)
	{
		if(this.featurePopup != null)
		{
			return this.featurePopup;
		}
		this.featurePopup = new BX.PopupWindow('bx-popup-crm-sms-editor-feature-popup', null, {
			zIndex: 200,
			autoHide: true,
			closeByEsc: true,
			closeIcon: true,
			overlay : true,
			events : {
				onPopupDestroy : function()
				{
					this.featurePopup = null;
				}.bind(this)
			},
			content : content,
			contentColor: 'white'
		});

		return this.featurePopup;
	};

	BX.CrmTimelineSmsEditor.prototype.showFilesExternalLinkFeaturePopup = function()
	{
		this.getFeaturePopup(this._fileExternalLinkDisabledContent).show();
	};

	BX.CrmTimelineSmsEditor.items = {};
	BX.CrmTimelineSmsEditor.create = function(id, settings)
	{
		var self = new BX.CrmTimelineSmsEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}


if(typeof BX.CrmTimelineRestEditor === "undefined")
{
	BX.CrmTimelineRestEditor = function()
	{
		BX.CrmTimelineRestEditor.superclass.constructor.apply(this);

		this._interfaceInitialized = false;
	};
	BX.extend(BX.CrmTimelineRestEditor, BX.CrmTimelineBaseEditor);

	BX.CrmTimelineRestEditor.prototype.action = function(action)
	{
		if(!this._interfaceInitialized)
		{
			this._interfaceInitialized = true;
			this.initializeInterface();
		}

		if(action === 'activity_rest_applist')
		{
			BX.rest.Marketplace.open({
				PLACEMENT: this.getSetting("placement", '')
			});

			top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', BX.proxy(this.fireUpdateEvent, this));
		}
		else
		{
			var appId = action.replace('activity_rest_', '');
			var appData = appId.split('_');

			BX.rest.AppLayout.openApplication(
				appData[0],
				{
					ID: this._ownerId
				},
				{
					PLACEMENT: this.getSetting("placement", ''),
					PLACEMENT_ID: appData[1]
				}
			);
		}
	};

	BX.CrmTimelineRestEditor.prototype.initializeInterface = function()
	{
		if(!!top.BX.rest && !!top.BX.rest.AppLayout)
		{
			var entityTypeId = this._manager._ownerTypeId, entityId = this._manager._ownerId;

			var PlacementInterface = top.BX.rest.AppLayout.initializePlacement(this.getSetting("placement", ''));

			PlacementInterface.prototype.reloadData = function(params, cb)
			{
				BX.Crm.EntityEvent.fireUpdate(entityTypeId, entityId, '');
				cb();
			};
		}
	};

	BX.CrmTimelineRestEditor.prototype.fireUpdateEvent = function()
	{
		var entityTypeId = this._manager._ownerTypeId, entityId = this._manager._ownerId;
		setTimeout(function(){
			console.log('fireUpdate', entityId, entityTypeId);
			BX.Crm.EntityEvent.fire(BX.Crm.EntityEvent.names.invalidate, entityTypeId, entityId, '');
		}, 3000);
	};

	BX.CrmTimelineRestEditor.items = {};
	BX.CrmTimelineRestEditor.create = function(id, settings)
	{
		var self = new BX.CrmTimelineRestEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}
//endregion

if(typeof(BX.CrmTimelineWaitConfigurationDialog) === "undefined")
{
	BX.CrmTimelineWaitConfigurationDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._type = BX.CrmTimelineWaitType.undefined;
		this._duration = 0;
		this._target = "";
		this._targetDates = null;
		this._container = null;
		this._durationMeasureNode = null;
		this._durationInput = null;
		this._targetDateNode = null;
		this._popup = null;
	};
	BX.CrmTimelineWaitConfigurationDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._type = BX.prop.getInteger(this._settings, "type", BX.CrmTimelineWaitType.after);
			this._duration = BX.prop.getInteger(this._settings, "duration", 1);
			this._target = BX.prop.getString(this._settings, "target", "");
			this._targetDates = BX.prop.getArray(this._settings, "targetDates", []);

			this._menuId = this._id + "_target_date_sel";
		},
		getId: function()
		{
			return this._id;
		},
		getType: function()
		{
			return this._type;
		},
		setType: function(type)
		{
			this._type = type;
		},
		getDuration: function()
		{
			return this._duration;
		},
		setDuration: function(duration)
		{
			this._duration = duration;
		},
		getTarget: function()
		{
			return this._target;
		},
		setTarget: function(target)
		{
			this._target = target;
		},
		getMessage: function(name)
		{
			var m = BX.CrmTimelineWaitConfigurationDialog.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getDurationText: function(duration, enableNumber)
		{
			return BX.CrmTimelineWaitHelper.getDurationText(duration, enableNumber);
		},
		getTargetDateCaption: function(name)
		{
			for(var i = 0, length = this._targetDates.length; i < length; i++)
			{
				var info = this._targetDates[i];
				if(info["name"] === name)
				{
					return info["caption"];
				}
			}

			return "";
		},
		open: function()
		{
			this._popup = new BX.PopupWindow(
				this._id,
				this._configSelector,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					zIndex: 0,
					content: this.prepareDialogContent(),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text: this.getMessage("select"),
								className: "popup-window-button-accept" ,
								events: { click: BX.delegate(this.onSaveButtonClick, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : BX.message("JS_CORE_WINDOW_CANCEL"),
								events: { click: BX.delegate(this.onCancelButtonClick, this) }
							}
						)
					]
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if(this._popup)
			{
				this._popup.close();
			}
		},
		prepareDialogContent: function()
		{
			var container = BX.create("div", { attrs: { className: "crm-wait-popup-select-block" } });
			var wrapper = BX.create("div", { attrs: { className: "crm-wait-popup-select-wrapper" } });
			container.appendChild(wrapper);

			this._durationInput = BX.create(
				"input",
				{
					attrs: { type: "text", className: "crm-wait-popup-settings-input", value: this._duration },
					events: { keyup: BX.delegate(this.onDurationChange, this) }
				}
			);

			this._durationMeasureNode = BX.create(
				"span",
				{ attrs: { className: "crm-wait-popup-settings-title" }, text: this.getDurationText(this._duration, false) }
			);

			if(this._type === BX.CrmTimelineWaitType.after)
			{
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: this.getMessage("prefixTypeAfter") })
				);
				wrapper.appendChild(this._durationInput);
				wrapper.appendChild(this._durationMeasureNode);
			}
			else
			{
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: this.getMessage("prefixTypeBefore") })
				);
				wrapper.appendChild(this._durationInput);
				wrapper.appendChild(this._durationMeasureNode);
				wrapper.appendChild(
					BX.create("span", { attrs: { className: "crm-wait-popup-settings-title" }, text: " " + this.getMessage("targetPrefixTypeBefore") })
				);

				this._targetDateNode = BX.create(
					"span",
					{
						attrs: { className: "crm-automation-popup-settings-link" },
						text: this.getTargetDateCaption(this._target),
						events: { click: BX.delegate(this.toggleTargetMenu, this) }
					}
				);
				wrapper.appendChild(this._targetDateNode);
			}
			return container;
		},
		onDurationChange: function()
		{
			var duration = parseInt(this._durationInput.value);
			if(isNaN(duration) || duration <= 0)
			{
				duration = 1;
			}
			this._duration = duration;
			this._durationMeasureNode.innerHTML = BX.util.htmlspecialchars(this.getDurationText(duration, false));

		},
		toggleTargetMenu: function()
		{
			if(this.isTargetMenuOpened())
			{
				this.closeTargetMenu();
			}
			else
			{
				this.openTargetMenu();
			}
		},
		isTargetMenuOpened: function()
		{
			return !!BX.PopupMenu.getMenuById(this._menuId);
		},
		openTargetMenu: function()
		{
			var menuItems = [];
			for(var i = 0, length = this._targetDates.length; i < length; i++)
			{
				var info = this._targetDates[i];

				menuItems.push(
					{
						text: info["caption"],
						title: info["caption"],
						value: info["name"],
						onclick: BX.delegate(this.onTargetSelect, this)
					}
				);
			}

			BX.PopupMenu.show(
				this._menuId,
				this._targetDateNode,
				menuItems,
				{
					zIndex: 200,
					autoHide: true,
					offsetLeft: BX.pos(this._targetDateNode)["width"] / 2,
					angle: { position: 'top', offset: 0 }
				}
			);
		},
		closeTargetMenu: function()
		{
			BX.PopupMenu.destroy(this._menuId);
		},
		onPopupShow: function(e, item)
		{
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}

			this.closeTargetMenu();
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		},
		onSaveButtonClick: function(e)
		{
			var callback =  BX.prop.getFunction(this._settings, "onSave", null);
			if(!callback)
			{
				return;
			}

			var params = { type: this._type };
			params["duration"] = this._duration;
			params["target"] = this._type === BX.CrmTimelineWaitType.before ? this._target : "";
			callback(this, params);
		},
		onCancelButtonClick: function(e)
		{
			var callback =  BX.prop.getFunction(this._settings, "onCancel", null);
			if(callback)
			{
				callback(this);
			}
		},
		onTargetSelect: function(e, item)
		{
			var fieldName = BX.prop.getString(item, "value", "");
			if(fieldName !== "")
			{
				this._target = fieldName;
				this._targetDateNode.innerHTML = BX.util.htmlspecialchars(this.getTargetDateCaption(fieldName));
			}

			this.closeTargetMenu();
			e.preventDefault ? e.preventDefault() : (e.returnValue = false);
		}
	};
	if(typeof(BX.CrmTimelineWaitConfigurationDialog.messages) === "undefined")
	{
		BX.CrmTimelineWaitConfigurationDialog.messages = {};
	}
	BX.CrmTimelineWaitConfigurationDialog.create = function(id, settings)
	{
		var self = new BX.CrmTimelineWaitConfigurationDialog();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmTimelineType) === "undefined")
{
	BX.CrmTimelineType =
	{
		undefined: 0,
		activity: 1,
		creation: 2,
		modification: 3,
		link: 4,
		mark: 6,
		comment: 7,
		wait: 8,
		bizproc: 9,
		conversion: 10,
		sender: 11,
		document: 12,
		restoration: 13,
		order: 14,
		orderCheck: 15,
		scoring: 16
	};
}

//region Base Actions
if(typeof(BX.CrmTimelineAction) === "undefined")
{
	BX.CrmTimelineAction = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
	};
	BX.CrmTimelineAction.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmTimelineAction: Could not find container.";
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		layout: function()
		{
			this.doLayout();
		},
		doLayout: function()
		{
		}
	};
}

if(typeof(BX.CrmTimelineActivityAction) === "undefined")
{
	BX.CrmTimelineActivityAction = function()
	{
		BX.CrmTimelineActivityAction.superclass.constructor.apply(this);

		this._activityEditor = null;
		this._entityData = null;
		this._item = null;
		this._isEnabled = true;
	};
	BX.extend(BX.CrmTimelineActivityAction, BX.CrmTimelineAction);
	BX.CrmTimelineActivityAction.prototype.doInitialize = function()
	{
		this._entityData = this.getSetting("entityData");
		if(!BX.type.isPlainObject(this._entityData))
		{
			throw "BX.CrmTimelineActivityAction. A required parameter 'entityData' is missing.";
		}

		this._activityEditor = this.getSetting("activityEditor");
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmTimelineActivityAction. A required parameter 'activityEditor' is missing.";
		}

		this._item = this.getSetting("item");
		this._isEnabled = this.getSetting("enabled", true);
	};
	BX.CrmTimelineActivityAction.prototype.getActivityId = function()
	{
		return BX.prop.getInteger(this._entityData, "ID", 0);
	};
	BX.CrmTimelineActivityAction.prototype.loadActivityCommunications = function(callback)
	{
		this._activityEditor.getActivityCommunications(
			this.getActivityId(),
			function(communications)
			{
				if(BX.type.isFunction(callback))
				{
					callback(communications);
				}
			},
			true
		);
	};
	BX.CrmTimelineActivityAction.prototype.getItemData = function()
	{
		return this._item ?  this._item.getData() : null;
	}
}

if(typeof(BX.CrmTimelineEmailAction) === "undefined")
{
	BX.CrmTimelineEmailAction = function()
	{
		BX.CrmTimelineEmailAction.superclass.constructor.apply(this);
		this._clickHandler = BX.delegate(this.onClick, this);
		this._saveHandler = BX.delegate(this.onSave, this);
	};
	BX.extend(BX.CrmTimelineEmailAction, BX.CrmTimelineActivityAction);
	BX.CrmTimelineEmailAction.prototype.onClick = function(e)
	{
		var settings =
			{
				"ownerType": BX.CrmEntityType.resolveName(BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0)),
				"ownerID": BX.prop.getInteger(this._entityData, "OWNER_ID", 0),
				"ownerUrl": BX.prop.getString(this._entityData, "OWNER_URL", ""),
				"ownerTitle": BX.prop.getString(this._entityData, "OWNER_TITLE", ""),
				"originalMessageID": BX.prop.getInteger(this._entityData, "ID", 0),
				"messageType": "RE"
			};

		if (BX.CrmActivityProvider && top.BX.Bitrix24 && top.BX.Bitrix24.Slider)
		{
			var activity = this._activityEditor.addEmail(settings);
			activity.addOnSave(this._saveHandler);
		}
		else
		{
			this.loadActivityCommunications(
				BX.delegate(
					function(communications)
					{
						settings['communications'] = BX.type.isArray(communications) ? communications : [];
						settings['communicationsLoaded'] = true;

						BX.CrmActivityEmail.prepareReply(settings);

						var activity = this._activityEditor.addEmail(settings);
						activity.addOnSave(this._saveHandler);
					},
					this
				)
			);
		}
		return BX.PreventDefault(e);
	};
	BX.CrmTimelineEmailAction.prototype.onSave = function(activity, data)
	{
		if(BX.type.isFunction(this._item.onActivityCreate))
		{
			this._item.onActivityCreate(activity, data);
		}
	};
}

if(typeof(BX.CrmTimelineCallAction) === "undefined")
{
	BX.CrmTimelineCallAction = function()
	{
		BX.CrmTimelineCallAction.superclass.constructor.apply(this);
		this._clickHandler = BX.delegate(this.onClick, this);
		this._menu = null;
		this._isMenuShown = false;
		this._menuItems = null;
	};
	BX.extend(BX.CrmTimelineCallAction, BX.CrmTimelineActivityAction);
	BX.CrmTimelineCallAction.prototype.getButton = function()
	{
		return null;
	};
	BX.CrmTimelineCallAction.prototype.onClick = function(e)
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("telephonyNotSupported"));
			return;
		}

		var phone = "";
		var itemData = this.getItemData();
		var phones = BX.prop.getArray(itemData, "PHONE", []);

		if(phones.length === 1)
		{
			this.addCall(phones[0]['VALUE']);
		}
		else if(phones.length > 1)
		{
			this.showMenu();
		}
		else
		{
			var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
			if(communication)
			{
				if(BX.prop.getString(communication, "TYPE") === "PHONE")
				{
					phone = BX.prop.getString(communication, "VALUE");
					if(phone)
					{
						this.addCall(phone);
					}
				}
			}
		}

		return BX.PreventDefault(e);
	};
	BX.CrmTimelineCallAction.prototype.showMenu = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		this.prepareMenuItems();

		if(!this._menuItems || this._menuItems.length === 0)
		{
			return;
		}


		this._menu = new BX.PopupMenuWindow(
			this._id,
			this._container,
			this._menuItems,
			{
				offsetTop: 0,
				offsetLeft: 16,
				events:
					{
						onPopupShow: BX.delegate(this.onMenuShow, this),
						onPopupClose: BX.delegate(this.onMenuClose, this),
						onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
					}
			}
		);

		this._menu.popupWindow.show();
	};
	BX.CrmTimelineCallAction.prototype.closeMenu = function()
	{
		if(!this._isMenuShown)
		{
			return;
		}

		if(this._menu)
		{
			this._menu.close();
		}
	};
	BX.CrmTimelineCallAction.prototype.prepareMenuItems = function()
	{
		if(this._menuItems)
		{
			return;
		}

		var itemData = this.getItemData();
		var phones = BX.prop.getArray(itemData, "PHONE", []);
		var handler = BX.delegate(this.onMenuItemClick, this);
		this._menuItems = [];

		if(phones.length === 0)
		{
			return;
		}

		for(var i = 0, l = phones.length; i < l; i++)
		{
			var value = BX.prop.getString(phones[i], "VALUE");
			var formattedValue = BX.prop.getString(phones[i], "VALUE_FORMATTED");
			var complexName = BX.prop.getString(phones[i], "COMPLEX_NAME");
			var itemText = (complexName ? complexName + ': ' : '') + (formattedValue ? formattedValue : value);

			if(value !== "")
			{
				this._menuItems.push({ id: value, text:  itemText, onclick: handler});
			}
		}
	};
	BX.CrmTimelineCallAction.prototype.onMenuItemClick = function(e, item)
	{
		this.closeMenu();
		this.addCall(item.id);
	};
	BX.CrmTimelineCallAction.prototype.onMenuShow = function()
	{
		this._isMenuShown = true;
	};
	BX.CrmTimelineCallAction.prototype.onMenuClose = function()
	{
		this._isMenuShown = false;
		this._menu.popupWindow.destroy();
	};
	BX.CrmTimelineCallAction.prototype.onMenuDestroy = function()
	{
		this._menu = null;
	};
	BX.CrmTimelineCallAction.prototype.addCall = function(phone)
	{
		var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
		var entityTypeId = parseInt(BX.prop.getString(communication, "ENTITY_TYPE_ID", "0"));
		if(isNaN(entityTypeId))
		{
			entityTypeId = 0;
		}

		var entityId = parseInt(BX.prop.getString(communication, "ENTITY_ID", "0"));
		if(isNaN(entityId))
		{
			entityId = 0;
		}

		var ownerTypeId = 0;
		var ownerId = 0;

		var ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");
		if(ownerInfo)
		{
			ownerTypeId = BX.prop.getInteger(ownerInfo, "ENTITY_TYPE_ID", 0);
			ownerId = BX.prop.getInteger(ownerInfo, "ENTITY_ID", 0);
		}

		if(ownerTypeId <= 0 || ownerId <= 0)
		{
			ownerTypeId = BX.prop.getInteger(this._entityData, "OWNER_TYPE_ID", 0);
			ownerId = BX.prop.getInteger(this._entityData, "OWNER_ID", "0");
		}

		if(ownerTypeId <= 0 || ownerId <= 0)
		{
			ownerTypeId = entityTypeId;
			ownerId = entityId;
		}

		var activityId = parseInt(BX.prop.getString(this._entityData, "ID", "0"));
		if(isNaN(activityId))
		{
			activityId = 0;
		}

		var params =
			{
				"ENTITY_TYPE_NAME": BX.CrmEntityType.resolveName(entityTypeId),
				"ENTITY_ID": entityId,
				"AUTO_FOLD": true
			};
		if(ownerTypeId !== entityTypeId || ownerId !== entityId)
		{
			params["BINDINGS"] = [ { "OWNER_TYPE_NAME": BX.CrmEntityType.resolveName(ownerTypeId), "OWNER_ID": ownerId } ];
		}

		if(activityId > 0)
		{
			params["SRC_ACTIVITY_ID"] = activityId;
		}

		window.top['BXIM'].phoneTo(phone, params);

	};
	BX.CrmTimelineCallAction.prototype.getMessage = function(name)
	{
		var m = BX.CrmTimelineCallAction.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmTimelineCallAction.messages) === "undefined")
	{
		BX.CrmTimelineCallAction.messages = {};
	}
}

if(typeof(BX.CrmTimelineOpenLineAction) === "undefined")
{
	BX.CrmTimelineOpenLineAction = function()
	{
		BX.CrmTimelineOpenLineAction.superclass.constructor.apply(this);
		this._clickHandler = BX.delegate(this.onClick, this);
		this._button = null;
	};
	BX.extend(BX.CrmTimelineOpenLineAction, BX.CrmTimelineActivityAction);
	BX.CrmTimelineOpenLineAction.prototype.getButton = function()
	{
		return this._button;
	};
	BX.CrmTimelineOpenLineAction.prototype.onClick = function()
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("openLineNotSupported"));
			return;
		}

		var slug = "";
		var communication = BX.prop.getObject(this._entityData, "COMMUNICATION", null);
		if(communication)
		{
			if(BX.prop.getString(communication, "TYPE") === "IM")
			{
				slug = BX.prop.getString(communication, "VALUE");
			}
		}

		if(slug !== "")
		{
			window.top['BXIM'].openMessengerSlider(slug, {RECENT: 'N', MENU: 'N'});
		}
	};
	BX.CrmTimelineOpenLineAction.prototype.doLayout = function()
	{
		this._button = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-action-reply-btn" },
				events: { "click": this._clickHandler }
			}
		);
		this._container.appendChild(this._button);
	};
	BX.CrmTimelineOpenLineAction.prototype.getMessage = function(name)
	{
		var m = BX.CrmTimelineOpenLineAction.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmTimelineOpenLineAction.messages) === "undefined")
	{
		BX.CrmTimelineOpenLineAction.messages = {};
	}
}
//endregion

//region History Actions
if(typeof(BX.CrmHistoryEmailAction) === "undefined")
{
	BX.CrmHistoryEmailAction = function()
	{
		BX.CrmHistoryEmailAction.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryEmailAction, BX.CrmTimelineEmailAction);
	BX.CrmHistoryEmailAction.prototype.doLayout = function()
	{
		this._container.appendChild(
			BX.create("A",
			{
				attrs: { className: "crm-entity-stream-content-action-reply-btn" },
				events: { "click": this._clickHandler }
			})
		);
	};
	BX.CrmHistoryEmailAction.create = function(id, settings)
	{
		var self = new BX.CrmHistoryEmailAction();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmHistoryCallAction) === "undefined")
{
	BX.CrmHistoryCallAction = function()
	{
		BX.CrmHistoryCallAction.superclass.constructor.apply(this);
		this._button = null;
	};

	BX.extend(BX.CrmHistoryCallAction, BX.CrmTimelineCallAction);

	BX.CrmHistoryCallAction.prototype.getButton = function()
	{
		return this._button;
	};

	BX.CrmHistoryCallAction.prototype.doLayout = function()
	{
		this._button = BX.create("A",
			{
				attrs: { className: "crm-entity-stream-content-action-reply-btn" },
				events: { "click": this._clickHandler }
			}
		);
		this._container.appendChild(this._button);
	};

	BX.CrmHistoryCallAction.create = function(id, settings)
	{
		var self = new BX.CrmHistoryCallAction();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmHistoryOpenLineAction) === "undefined")
{
	BX.CrmHistoryOpenLineAction = function()
	{
		BX.CrmHistoryOpenLineAction.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryOpenLineAction, BX.CrmTimelineOpenLineAction);
	BX.CrmHistoryOpenLineAction.create = function(id, settings)
	{
		var self = new BX.CrmHistoryOpenLineAction();
		self.initialize(id, settings);
		return self;
	}
}
//endregion

//region Schedule Actions
if(typeof(BX.CrmScheduleEmailAction) === "undefined")
{
	BX.CrmScheduleEmailAction = function()
	{
		BX.CrmScheduleEmailAction.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleEmailAction, BX.CrmTimelineEmailAction);
	BX.CrmScheduleEmailAction.prototype.doLayout = function()
	{
		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-action-reply-btn" },
					events: { "click": this._clickHandler }
				}
			)
		);
	};
	BX.CrmScheduleEmailAction.create = function(id, settings)
	{
		var self = new BX.CrmScheduleEmailAction();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmSchedulePostponeController) === "undefined")
{
	BX.CrmSchedulePostponeController = function()
	{
		this._item = null;
	};
	BX.CrmSchedulePostponeController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};
			this._item = BX.prop.get(this._settings, "item", null);
		},
		getTitle: function()
		{
			return this.getMessage("title");
		},
		getCommandList: function()
		{
			return(
				[
					{ name: "postpone_hour_1", title: this.getMessage("forOneHour") },
					{ name: "postpone_hour_2", title: this.getMessage("forTwoHours") },
					{ name: "postpone_hour_3", title: this.getMessage("forThreeHours") },
					{ name: "postpone_day_1", title: this.getMessage("forOneDay") },
					{ name: "postpone_day_2", title: this.getMessage("forTwoDays") },
					{ name: "postpone_day_3", title: this.getMessage("forThreeDays") }
				]
			);
		},
		processCommand: function(command)
		{
			if(command.indexOf("postpone") !== 0)
			{
				return false;
			}

			var offset = 0;
			if(command === "postpone_hour_1")
			{
				offset = 3600;
			}
			else if(command === "postpone_hour_2")
			{
				offset = 7200;
			}
			else if(command === "postpone_hour_3")
			{
				offset = 10800;
			}
			else if(command === "postpone_day_1")
			{
				offset = 86400;
			}
			else if(command === "postpone_day_2")
			{
				offset = 172800;
			}
			else if(command === "postpone_day_3")
			{
				offset = 259200;
			}

			if(offset > 0 && this._item)
			{
				this._item.postpone(offset);
			}

			return true;
		},
		getMessage: function(name)
		{
			var m = BX.CrmSchedulePostponeController.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		}
	};

	if(typeof(BX.CrmSchedulePostponeController.messages) === "undefined")
	{
		BX.CrmSchedulePostponeController.messages = {};
	}
	BX.CrmSchedulePostponeController.create = function(id, settings)
	{
		var self = new BX.CrmSchedulePostponeController();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmSchedulePostponeAction) === "undefined")
{
	BX.CrmSchedulePostponeAction = function()
	{
		BX.CrmSchedulePostponeAction.superclass.constructor.apply(this);
		this._button = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._isMenuShown = false;

		this._menu = false;
	};
	BX.extend(BX.CrmSchedulePostponeAction, BX.CrmTimelineActivityAction);
	BX.CrmSchedulePostponeAction.prototype.doLayout = function()
	{
		this._button = BX.create("DIV",
			{
				attrs:
					{
						className: this._isEnabled
							? "crm-entity-stream-planned-action-aside"
							: "crm-entity-stream-planned-action-aside-disabled"
					},
				text: this.getMessage("postpone")
			}
		);

		if(this._isEnabled)
		{
			BX.bind(this._button, "click", this._clickHandler)
		}

		this._container.appendChild(this._button);
	};
	BX.CrmSchedulePostponeAction.prototype.openMenu = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		var handler = BX.delegate(this.onMenuItemClick, this);
		var menuItems =
			[
				{ id: "hour_1", text: this.getMessage("forOneHour"), onclick: handler },
				{ id: "hour_2", text: this.getMessage("forTwoHours"), onclick: handler },
				{ id: "hour_3", text: this.getMessage("forThreeHours"), onclick: handler },
				{ id: "day_1", text: this.getMessage("forOneDay"), onclick: handler },
				{ id: "day_2", text: this.getMessage("forTwoDays"), onclick: handler },
				{ id: "day_3", text: this.getMessage("forThreeDays"), onclick: handler }
			];

		BX.PopupMenu.show(
			this._id,
			this._button,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 16,
				events:
				{
					onPopupShow: BX.delegate(this.onMenuShow, this),
					onPopupClose: BX.delegate(this.onMenuClose, this),
					onPopupDestroy: BX.delegate(this.onMenuDestroy, this)
				}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
	};
	BX.CrmSchedulePostponeAction.prototype.closeMenu = function()
	{
		if(!this._isMenuShown)
		{
			return;
		}

		if(this._menu)
		{
			this._menu.close();
		}
	};
	BX.CrmSchedulePostponeAction.prototype.onClick = function()
	{
		if(!this._isEnabled)
		{
			return;
		}

		if(this._isMenuShown)
		{
			this.closeMenu();
		}
		else
		{
			this.openMenu();
		}
	};
	BX.CrmSchedulePostponeAction.prototype.onMenuItemClick = function(e, item)
	{
		this.closeMenu();

		var offset = 0;
		if(item.id === "hour_1")
		{
			offset = 3600;
		}
		else if(item.id === "hour_2")
		{
			offset = 7200;
		}
		else if(item.id === "hour_3")
		{
			offset = 10800;
		}
		else if(item.id === "day_1")
		{
			offset = 86400;
		}
		else if(item.id === "day_2")
		{
			offset = 172800;
		}
		else if(item.id === "day_3")
		{
			offset = 259200;
		}

		if(offset > 0 && this._item)
		{
			this._item.postpone(offset);
		}
	};
	BX.CrmSchedulePostponeAction.prototype.onMenuShow = function()
	{
		this._isMenuShown = true;
	};
	BX.CrmSchedulePostponeAction.prototype.onMenuClose = function()
	{
		if(this._menu && this._menu.popupWindow)
		{
			this._menu.popupWindow.destroy();
		}
	};
	BX.CrmSchedulePostponeAction.prototype.onMenuDestroy = function()
	{
		this._isMenuShown = false;
		this._menu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	};
	BX.CrmSchedulePostponeAction.prototype.getMessage = function(name)
	{
		var m = BX.CrmSchedulePostponeAction.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};

	if(typeof(BX.CrmSchedulePostponeAction.messages) === "undefined")
	{
		BX.CrmSchedulePostponeAction.messages = {};
	}
	BX.CrmSchedulePostponeAction.create = function(id, settings)
	{
		var self = new BX.CrmSchedulePostponeAction();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmScheduleCallAction) === "undefined")
{
	BX.CrmScheduleCallAction = function()
	{
		BX.CrmScheduleCallAction.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleCallAction, BX.CrmTimelineCallAction);
	BX.CrmScheduleCallAction.prototype.doLayout = function()
	{
		this._container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-action-reply-btn" },
					events: { "click": this._clickHandler }
				}
			)
		);
	};
	BX.CrmScheduleCallAction.create = function(id, settings)
	{
		var self = new BX.CrmScheduleCallAction();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmScheduleOpenLineAction) === "undefined")
{
	BX.CrmScheduleOpenLineAction = function()
	{
		BX.CrmScheduleOpenLineAction.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleOpenLineAction, BX.CrmTimelineOpenLineAction);
	BX.CrmScheduleOpenLineAction.create = function(id, settings)
	{
		var self = new BX.CrmScheduleOpenLineAction();
		self.initialize(id, settings);
		return self;
	}
}
//endregion

//region Base Item
if(typeof(BX.CrmTimelineItem) === "undefined")
{
	BX.CrmTimelineItem = function()
	{
		this._id = "";
		this._settings = {};
		this._data = {};
		this._container = null;
		this._wrapper = null;

		this._typeCategoryId = null;
		this._associatedEntityData = null;
		this._associatedEntityTypeId = null;
		this._associatedEntityId = null;
		this._isContextMenuShown = false;
		this._contextMenuButton = null;

		this._activityEditor = null;
		this._actions = [];
		this._actionContainer = null;

		this._isTerminated = false;
	};
	BX.CrmTimelineItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = this.getSetting("container");

			if(!BX.type.isPlainObject(settings['data']))
			{
				throw "BX.CrmTimelineItem. A required parameter 'data' is missing.";
			}
			this._data = settings['data'];

			this._activityEditor = this.getSetting("activityEditor");

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getData: function()
		{
			return this._data;
		},
		setData: function(data)
		{
			if(BX.type.isPlainObject(data))
			{
				this._data = data;
				this.clearCachedData();
			}
		},
		getAssociatedEntityData: function()
		{
			if(this._associatedEntityData === null)
			{
				this._associatedEntityData = BX.type.isPlainObject(this._data["ASSOCIATED_ENTITY"])
					? this._data["ASSOCIATED_ENTITY"] : {};
			}

			return this._associatedEntityData;
		},
		getAssociatedEntityTypeId: function()
		{
			if(this._associatedEntityTypeId === null)
			{
				this._associatedEntityTypeId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_TYPE_ID", 0)
			}
			return this._associatedEntityTypeId;
		},
		getAssociatedEntityId: function()
		{
			if(this._associatedEntityId === null)
			{
				this._associatedEntityId = BX.prop.getInteger(this._data, "ASSOCIATED_ENTITY_ID", 0)
			}
			return this._associatedEntityId;
		},
		setAssociatedEntityData: function(associatedEntityData)
		{
			if(!BX.type.isPlainObject(associatedEntityData))
			{
				associatedEntityData = {};
			}

			this._data["ASSOCIATED_ENTITY"] = associatedEntityData;
			this.clearCachedData();
		},
		hasPermissions: function()
		{
			var entityData = this.getAssociatedEntityData();
			return BX.type.isPlainObject(entityData["PERMISSIONS"]);
		},
		getPermissions: function()
		{
			return BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
		},
		setPermissions: function(permissions)
		{
			if(!BX.type.isPlainObject(this._data["ASSOCIATED_ENTITY"]))
			{
				this._data["ASSOCIATED_ENTITY"] = {};
			}
			this._data["ASSOCIATED_ENTITY"]["PERMISSIONS"] = permissions;
			this.clearCachedData();
		},
		getTextDataParam: function(name)
		{
			return BX.prop.getString(this._data, name, "");
		},
		getObjectDataParam: function(name)
		{
			return BX.prop.getObject(this._data, name, {});
		},
		getArrayDataParam: function(name)
		{
			return BX.prop.getArray(this._data, name, []);
		},
		getTypeId: function()
		{
			return BX.CrmTimelineType.undefined;
		},
		getTypeCategoryId: function()
		{
			if(this._typeCategoryId === null)
			{
				this._typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
			}
			return this._typeCategoryId;
		},
		getContainer: function()
		{
			return this._container;
		},
		setContainer: function(container)
		{
			this._container = BX.type.isElementNode(container) ? container : null;
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		addWrapperClass: function(className, timeout)
		{
			if(!this._wrapper)
			{
				return;
			}

			BX.addClass(this._wrapper, className);

			if(BX.type.isNumber(timeout) && timeout >= 0)
			{
				window.setTimeout(
					BX.delegate(
						function(){ this.removeWrapperClass(className); },
						this
					),
					timeout
				);
			}
		},
		removeWrapperClass: function(className, timeout)
		{
			if(!this._wrapper)
			{
				return;
			}

			BX.removeClass(this._wrapper, className);

			if(BX.type.isNumber(timeout) && timeout >= 0)
			{
				window.setTimeout(
					BX.delegate(
						function(){ this.addWrapperClass(className); },
						this
					),
					timeout
				);
			}

		},
		layout: function(options)
		{
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmTimelineItem. Container is not assigned.";
			}

			this.prepareLayout(options);
			//region Actions
			/**/
			this.prepareActions();
			var actionQty = this._actions.length;
			for(var i = 0; i < actionQty; i++)
			{
				this._actions[i].layout();
			}
			this.showActions(actionQty > 0);
			/**/
			//endregion
		},
		prepareLayout: function(options)
		{
		},
		prepareActions: function()
		{
		},
		showActions: function(show)
		{
		},
		clearLayout: function()
		{
			this._wrapper = BX.remove(this._wrapper);
		},
		refreshLayout: function()
		{
			var anchor = this._wrapper.previousSibling;
			this._wrapper = BX.remove(this._wrapper);
			this.layout({ anchor: anchor });
		},
		clearCachedData: function()
		{
			this._typeCategoryId = null;
			this._associatedEntityData = null;
			this._associatedEntityTypeId = null;
			this._associatedEntityId = null;
		},
		isDone: function()
		{
			return false;
		},
		markAsDone: function(isDone)
		{
		},
		isTerminated: function()
		{
			return this._isTerminated;
		},
		markAsTerminated: function(terminated)
		{
			terminated = !!terminated;

			if(this._isTerminated === terminated)
			{
				return;
			}

			this._isTerminated = terminated;
			if(!this._wrapper)
			{
				return;
			}

			if(terminated)
			{
				BX.addClass(this._wrapper, "crm-entity-stream-section-last");
			}
			else
			{
				BX.removeClass(this._wrapper, "crm-entity-stream-section-last");
			}
		},
		view: function()
		{
		},
		edit: function()
		{
		},
		fasten: function()
		{
		},
		unfasten: function()
		{
		},
		remove: function()
		{
		},
		cutOffText: function(text, length)
		{
			if(!BX.type.isNumber(length))
			{
				length = 0;
			}

			if(length <= 0 || text.length <= length)
			{
				return text;
			}

			var offset = length - 1;
			var whilespaceOffset = text.substring(offset).search(/\s/i);
			if(whilespaceOffset > 0)
			{
				offset += whilespaceOffset;
			}
			return text.substring(0, offset);
		},
		prepareCutOffElements: function(text, length, clickHandler)
		{
			if(!BX.type.isNumber(length))
			{
				length = 0;
			}

			if(length <= 0 || text.length <= length)
			{
				return [BX.util.htmlspecialchars(text)];
			}

			var offset = length - 1;
			var whilespaceOffset = text.substring(offset).search(/\s/i);
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
		},
		prepareAuthorLayout: function()
		{
			var authorInfo = this.getObjectDataParam("AUTHOR", null);
			if(!authorInfo)
			{
				return null;
			}

			var showUrl = BX.prop.getString(authorInfo, "SHOW_URL", "");
			if(showUrl === "")
			{
				return null;
			}

			var link = BX.create("A",
				{
					attrs:
						{
							className: "crm-entity-stream-content-detail-employee",
							href: showUrl,
							target: "_blank",
							title: BX.prop.getString(authorInfo, "FORMATTED_NAME", "")
						}
				}
			);
			var imageUrl = BX.prop.getString(authorInfo, "IMAGE_URL", "");
			if(imageUrl !== "")
			{
				link.style.backgroundImage = "url('" + imageUrl + "')";
				link.style.backgroundSize = "21px";
			}
			return link;
		},
		onActivityCreate: function(activity, data)
		{
		}
	};
	BX.CrmTimelineItem.userTimezoneOffset = null;
	BX.CrmTimelineItem.getUserTimezoneOffset = function()
	{
		if(!this.userTimezoneOffset)
		{
			this.userTimezoneOffset = parseInt(BX.message("USER_TZ_OFFSET"));
			if(isNaN(this.userTimezoneOffset))
			{
				this.userTimezoneOffset = 0;
			}
		}
		return this.userTimezoneOffset;
	};
	BX.CrmTimelineItem.prototype.isContextMenuEnabled = function()
	{
		return false;
	};
	BX.CrmTimelineItem.prototype.prepareContextMenuButton = function()
	{
		this._contextMenuButton = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section-context-menu" },
				events: { click: BX.delegate(this.onContextMenuButtonClick, this) }
			}
		);
		return this._contextMenuButton;
	};
	BX.CrmTimelineItem.prototype.onContextMenuButtonClick = function(e)
	{
		if(!this._isContextMenuShown)
		{
			this.openContextMenu();
		}
		else
		{
			this.closeContextMenu();
		}
	};
	BX.CrmTimelineItem.prototype.openContextMenu = function()
	{
		var menuItems = this.prepareContextMenuItems();
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
	};
	BX.CrmTimelineItem.prototype.closeContextMenu = function()
	{
		if(this._contextMenu)
		{
			this._contextMenu.close();
		}
	};
	BX.CrmTimelineItem.prototype.prepareContextMenuItems = function()
	{
		return [];
	};
	BX.CrmTimelineItem.prototype.onContextMenuShow = function()
	{
		this._isContextMenuShown = true;
		BX.addClass(this._contextMenuButton, "active");
	};
	BX.CrmTimelineItem.prototype.onContextMenuClose = function()
	{
		if(this._contextMenu)
		{
			this._contextMenu.popupWindow.destroy();
		}
	};
	BX.CrmTimelineItem.prototype.onContextMenuDestroy = function()
	{
		this._isContextMenuShown = false;
		BX.removeClass(this._contextMenuButton, "active");
		this._contextMenu = null;

		if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
		{
			delete(BX.PopupMenu.Data[this._id]);
		}
	};

	BX.CrmTimelineItem.prototype.getMessage = function(name)
	{
		var m = BX.CrmTimelineItem.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmTimelineItem.messages) === "undefined")
	{
		BX.CrmTimelineItem.messages = {};
	}
}
//endregion

//region EDITOR MODE
if(typeof BX.Crm.TimelineEditorMode === "undefined")
{
	BX.Crm.TimelineEditorMode =
		{
			view: 1,
			edit: 2
		};
}
//endregion

//region History Items
if(typeof(BX.CrmHistoryItem) === "undefined")
{
	BX.CrmHistoryItem = function()
	{
		BX.CrmHistoryItem.superclass.constructor.apply(this);
		this._history = null;
		this._fixedHistory = null;
		this._typeId = null;
		this._createdTime = null;
		this._isFixed = false;
		this._headerClickHandler = BX.delegate(this.onHeaderClick, this);
	};
	BX.extend(BX.CrmHistoryItem, BX.CrmTimelineItem);
	BX.CrmHistoryItem.prototype.doInitialize = function()
	{
		this._history = this.getSetting("history");
		this._fixedHistory = this.getSetting("fixedHistory");
	};
	BX.CrmHistoryItem.prototype.getTypeId = function()
	{
		if(this._typeId === null)
		{
			this._typeId = BX.prop.getInteger(this._data, "TYPE_ID", BX.CrmTimelineType.undefined);
		}
		return this._typeId;
	};
	BX.CrmHistoryItem.prototype.getTitle = function()
	{
		return "";
	};
	BX.CrmHistoryItem.prototype.isContextMenuEnabled = function()
	{
		return !(this.isReadOnly());
	};
	BX.CrmHistoryItem.prototype.getCreatedTimestamp = function()
	{
		return this.getTextDataParam("CREATED_SERVER");
	};
	BX.CrmHistoryItem.prototype.getCreatedTime = function()
	{
		if(this._createdTime === null)
		{
			var time = BX.parseDate(
				this.getCreatedTimestamp(),
				false,
				"YYYY-MM-DD",
				"YYYY-MM-DD HH:MI:SS"
			);

			this._createdTime = new Date(time.getTime() + 1000 * BX.CrmTimelineItem.getUserTimezoneOffset());
		}
		return this._createdTime;
	};
	BX.CrmHistoryItem.prototype.getCreatedDate = function()
	{
		return BX.prop.extractDate(new Date(this.getCreatedTime().getTime()));
	};
	BX.CrmHistoryItem.prototype.getOwnerInfo = function()
	{
		return this._history ? this._history.getOwnerInfo() : null;
	};
	BX.CrmHistoryItem.prototype.getOwnerTypeId = function()
	{
		return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_TYPE_ID", BX.CrmEntityType.enumeration.undefined);
	};
	BX.CrmHistoryItem.prototype.getOwnerId = function()
	{
		return BX.prop.getInteger(this.getOwnerInfo(), "ENTITY_ID", 0);
	};
	BX.CrmHistoryItem.prototype.isReadOnly = function()
	{
		return this._history.isReadOnly();
	};
	BX.CrmHistoryItem.prototype.isEditable = function()
	{
		return !this.isReadOnly();
	};
	BX.CrmHistoryItem.prototype.isDone = function()
	{
		var typeId = this.getTypeId();
		if(typeId === BX.CrmTimelineType.activity)
		{
			var entityData = this.getAssociatedEntityData();
			return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
		}
		return false;
	};
	BX.CrmHistoryItem.prototype.isFixed = function()
	{
		return this._isFixed;
	};
	BX.CrmHistoryItem.prototype.fasten = function(e)
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
	};
	BX.CrmHistoryItem.prototype.onSuccessFasten = function(result)
	{
		if (BX.type.isNotEmptyString(result.ERROR))
			return;

		if (!this.isFixed())
		{
			this._data.IS_FIXED = 'Y';
			var fixedItem = this._fixedHistory.createItem(this._data);
			fixedItem._isFixed = true;
			this._fixedHistory.addItem(fixedItem, 0);
			fixedItem.layout({ add: false });
			this.refreshLayout();
			var animation = BX.CrmTimelineItemFasten.create(
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
	};
	BX.CrmHistoryItem.prototype.onFinishFasten = function(e)
	{
	};
	BX.CrmHistoryItem.prototype.unfasten = function(e)
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
	};
	BX.CrmHistoryItem.prototype.onSuccessUnfasten = function(result)
	{
		if (BX.type.isNotEmptyString(result.ERROR))
			return;

		var item = null;
		var historyItem = null;

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
			var index = this._fixedHistory.getItemIndex(item);
			item.clearAnimate();
			this._fixedHistory.removeItemByIndex(index);
			if (historyItem)
			{
				historyItem._data.IS_FIXED = 'N';
				historyItem.refreshLayout();
				BX.LazyLoad.showImages();
			}
		}
	};
	BX.CrmHistoryItem.prototype.clearAnimate = function()
	{
		if (!BX.type.isDomNode(this._wrapper))
			return ;

		var wrapperPosition = BX.pos(this._wrapper);
		var hideEvent = new BX.easing({
			duration : 1000,
			start : { height: wrapperPosition.height, opacity: 1, marginBottom: 15},
			finish: { height: 0, opacity: 0, marginBottom: 0},
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: BX.proxy(function(state) {
				this._wrapper.style.height = state.height + "px";
				this._wrapper.style.opacity = state.opacity;
				this._wrapper.style.marginBottom = state.marginBottom;
			}, this),
			complete: BX.proxy(function () {
				this.clearLayout();
			}, this)
		});

		hideEvent.animate();
	};
	BX.CrmHistoryItem.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmHistoryItem.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-info";
	};
	BX.CrmHistoryItem.prototype.prepareContentDetails = function()
	{
		return [];
	};
	BX.CrmHistoryItem.prototype.prepareContent = function()
	{
		var wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
		}
		var wrapper = BX.create("DIV", { attrs: { className: wrapperClassName } });
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		var header = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-header" },
				children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-content-event-title" },
								children:
									[
										BX.create("A",
											{
												attrs: { href: "#" },
												events: { click: this._headerClickHandler },
												text: this.getTitle()
											}
										)
									]
							}
						),
						BX.create("SPAN",
							{
								attrs: { className: "crm-entity-stream-content-event-time" },
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
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItem.prototype.prepareLayout = function(options)
	{
		this._wrapper = this.prepareContent();
		if(this._wrapper)
		{
			var enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
			if(enableAdd)
			{
				var anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
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
	};
	BX.CrmHistoryItem.prototype.onHeaderClick = function(e)
	{
		this.view();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	};
	BX.CrmHistoryItem.prototype.prepareTitleLayout = function()
	{
		return BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-event-title" }, text: this.getTitle() });
	};
	BX.CrmHistoryItem.prototype.prepareFixedSwitcherLayout = function()
	{
		var isFixed = (this.getTextDataParam("IS_FIXED") === 'Y');
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
			var manager = this._history.getManager();
			if (!manager.isSpotlightShowed())
			{
				manager.setSpotlightShowed();
				BX.addClass(this._switcher, "crm-entity-stream-section-top-fixed-btn-spotlight");
				var spotlight = new BX.SpotLight({
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
	};
	BX.CrmHistoryItem.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
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
	};
	BX.CrmHistoryItem.prototype.onActivityCreate = function(activity, data)
	{
		this._history.getManager().onActivityCreated(activity, data);
	};
	BX.CrmHistoryItem.prototype.formatTime = function(time)
	{
		if (this.isFixed())
		{
			return this._fixedHistory.formatTime(time);
		}

		return this._history.formatTime(time);
	};
	BX.CrmHistoryItem.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItem();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmHistoryItem.isCounterEnabled = function(deadline)
	{
		if(!BX.type.isDate(deadline))
		{
			return false;
		}

		var start = new Date();
		start.setHours(0);
		start.setMinutes(0);
		start.setSeconds(0);
		start.setMilliseconds(0);
		start = start.getTime();

		var end = new Date();
		end.setHours(23);
		end.setMinutes(59);
		end.setSeconds(59);
		end.setMilliseconds(999);
		end = end.getTime();

		var time = deadline.getTime();
		return time < start || (time >= start && time <= end);
	}
}

if(typeof(BX.CrmHistoryItemActivity) === "undefined")
{
	BX.CrmHistoryItemActivity = function()
	{
		BX.CrmHistoryItemActivity.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemActivity, BX.CrmHistoryItem);

	BX.CrmHistoryItemActivity.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemActivity.superclass.doInitialize.apply(this);
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmHistoryItemActivity. The field 'activityEditor' is not assigned.";
		}
	};
	BX.CrmHistoryItemActivity.prototype.getTitle = function()
	{
		return BX.prop.getString(this.getAssociatedEntityData(), "SUBJECT", "");
	};
	BX.CrmHistoryItemActivity.prototype.getTypeDescription = function()
	{
		var entityData = this.getAssociatedEntityData();
		var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);

		var typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
		}
		else if(typeCategoryId === BX.CrmActivityType.call)
		{
			return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
		}
		else if(typeCategoryId === BX.CrmActivityType.meeting)
		{
			return this.getMessage("meeting");
		}
		else if(typeCategoryId === BX.CrmActivityType.task)
		{
			return this.getMessage("task");
		}
		else if(typeCategoryId === BX.CrmActivityType.provider)
		{
			var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");

			if(providerId === "CRM_WEBFORM")
			{
				return this.getMessage("webform");
			}
			else if (providerId === "CRM_SMS")
			{
				return this.getMessage("sms");
			}
			else if (providerId === "CRM_REQUEST")
			{
				return this.getMessage("activityRequest");
			}
			else if (providerId === "IMOPENLINES_SESSION")
			{
				return this.getMessage("openLine");
			}
			else if (providerId === "REST_APP")
			{
				return this.getMessage("restApplication");
			}
			else if (providerId === "VISIT_TRACKER")
			{
				return this.getMessage("visit");
			}
		}

		return "";
	};
	BX.CrmHistoryItemActivity.prototype.prepareTitleLayout = function()
	{
		return BX.create("A",
			{
				attrs: { href: "#",  className: "crm-entity-stream-content-event-title" },
				events: { "click": this._headerClickHandler },
				text: this.getTypeDescription()
			}
		);
	};
	BX.CrmHistoryItemActivity.prototype.prepareTimeLayout = function()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	};
	BX.CrmHistoryItemActivity.prototype.prepareMarkLayout = function()
	{
		var entityData = this.getAssociatedEntityData();
		var markTypeId = BX.prop.getInteger(entityData, "MARK_TYPE_ID", 0);
		if(markTypeId <= 0)
		{
			return null;
		}

		var messageName = "";
		if(markTypeId === 2)
		{
			messageName = "SuccessMark";
		}
		else if(markTypeId === 3)
		{
			messageName = "RenewMark";
		}

		if(messageName === "")
		{
			return null;
		}

		var markText = "";
		var typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			markText = this.getMessage("email" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.call)
		{
			markText = this.getMessage("call" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.meeting)
		{
			markText = this.getMessage("meeting" + messageName);
		}
		else if(typeCategoryId === BX.CrmActivityType.task)
		{
			markText = this.getMessage("task" + messageName);
		}

		if(markText === "")
		{
			return null;
		}

		return(
			BX.create(
				"SPAN",
				{
					props: { className: "crm-entity-stream-content-event-skipped" },
					text: markText
				}
			)
		);
	};
	BX.CrmHistoryItemActivity.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		var typeCategoryId = this.getTypeCategoryId();
		if(typeCategoryId === BX.CrmActivityType.email)
		{
			this._actions.push(
				BX.CrmHistoryEmailAction.create(
					"email",
					{
						item: this,
						container: this._actionContainer,
						entityData: this.getAssociatedEntityData(),
						activityEditor: this._activityEditor
					}
				)
			);
		}
	};
	BX.CrmHistoryItemActivity.prototype.prepareContextMenuItems = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		var menuItems = [];

		if (!this.isReadOnly())
		{
			if (this.isEditable())
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.edit, this)});
			}
			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}
		return menuItems;
	};
	BX.CrmHistoryItemActivity.prototype.view = function()
	{
		this.closeContextMenu();
		var entityData = this.getAssociatedEntityData();
		var id = BX.prop.getInteger(entityData, "ID", 0);
		if(id > 0)
		{
			this._activityEditor.viewActivity(id);
		}
	};
	BX.CrmHistoryItemActivity.prototype.edit = function()
	{
		this.closeContextMenu();
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityData = this.getAssociatedEntityData();
			var id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.editActivity(id);
			}
		}
	};
	BX.CrmHistoryItemActivity.prototype.processRemoval = function()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getRemoveMessage()
				}
			);
		}

		dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	};
	BX.CrmHistoryItemActivity.prototype.getRemoveMessage = function()
	{
		return this.getMessage('removeConfirm');
	};
	BX.CrmHistoryItemActivity.prototype.onRemovalConfirm = function(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.remove();
	};
	BX.CrmHistoryItemActivity.prototype.onRemovalCancel = function()
	{
	};
	BX.CrmHistoryItemActivity.prototype.remove = function()
	{
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();

		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityData = this.getAssociatedEntityData();

			var id = BX.prop.getInteger(entityData, "ID", 0);

			if(id > 0)
			{
				var activityEditor = this._activityEditor;
				var item = activityEditor.getItemById(id);
				if (item)
				{
					activityEditor.deleteActivity(id, true);
				}
				else
				{
					var serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''),
						{
							id: id,
							action: 'get_activity',
							ownertype: activityEditor.getSetting('ownerType', ''),
							ownerid: activityEditor.getSetting('ownerID', '')
						}
					);
					BX.ajax({
						'url': serviceUrl,
						'method': 'POST',
						'dataType': 'json',
						'data':
							{
								'ACTION' : 'GET_ACTIVITY',
								'ID': id,
								'OWNER_TYPE': activityEditor.getSetting('ownerType', ''),
								'OWNER_ID': activityEditor.getSetting('ownerID', '')
							},
						onsuccess: BX.delegate(
							function(data)
							{
								if(typeof(data['ACTIVITY']) !== 'undefined')
								{
									activityEditor._handleActivityChange(data['ACTIVITY']);
									window.setTimeout(BX.delegate(this.remove ,this), 500);
								}
							},
							this
						),
						onfailure: function(data){}
					});
				}
			}
		}
	};
	if(typeof(BX.CrmHistoryItemActivity.messages) === "undefined")
	{
		BX.CrmHistoryItemActivity.messages = {};
	}
	BX.CrmHistoryItemActivity.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemActivity();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemComment) === "undefined")
{
	BX.CrmHistoryItemComment = function()
	{
		BX.CrmHistoryItemComment.superclass.constructor.apply(this);
		this._isCollapsed = false;
		this._isMenuShown = false;
		this._isFixed = false;
		this._hasFiles = false;
		this._postForm = null;
		this._editor = null;
		this._commentMessage = '';
		this._mode = BX.Crm.TimelineEditorMode.view;
	};
	BX.extend(BX.CrmHistoryItemComment, BX.CrmHistoryItem);
	BX.CrmHistoryItemComment.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemComment.superclass.doInitialize.apply(this);
		this._hasFiles = (this.getTextDataParam("HAS_FILES") === 'Y');
	};
	BX.CrmHistoryItemComment.prototype.getTitle = function()
	{
		return this.getMessage("comment");
	};
	BX.CrmHistoryItemComment.prototype.prepareContent = function()
	{
		var comment = this.getTextDataParam("COMMENT", "");
		var wrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-comment" }
			}
		);

		if (this.isReadOnly())
		{
			BX.addClass(wrapper, "crm-entity-stream-section-comment-read-only");
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-comment" } }
			)
		);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = this.prepareHeaderLayout();

		content.appendChild(header);

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		var detailChildren = [];
		if (this._mode !== BX.Crm.TimelineEditorMode.edit)
		{
			this._commentWrapper = BX.create("DIV", {
					attrs: { className: "crm-entity-stream-content-detail-description" }
				}
			);
			BX.html(this._commentWrapper, this.getTextDataParam("COMMENT", ""));
			detailChildren.push(this._commentWrapper);

			if (!this.isReadOnly())
			{
				BX.bind(this._commentWrapper, "click", BX.delegate(this.switchToEditMode, this));
				BX.bind(header, "click", BX.delegate(this.switchToEditMode, this));
			}
		}
		else
		{
			if (!BX.type.isDomNode(this._editorContainer))
				this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});

			detailChildren.push(this._editorContainer);

			var buttons = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-comment-edit-btn-container" },
					children:
						[
							BX.create("button",
								{
									attrs: { className: "ui-btn ui-btn-xs ui-btn-primary" },
									html: this.getMessage("send"),
									events : {
										click: BX.delegate(this.save, this)
									}
								}
							),
							BX.create("a",
								{
									attrs: { className: "ui-btn ui-btn-xs ui-btn-link" },
									html: this.getMessage("cancel"),
									events : {
										click: BX.delegate(this.switchToViewMode, this)
									}
								}
							)
						]
				}
			);

			detailChildren.push(buttons);
		}

		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: detailChildren
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion
		var cleanText = this.getTextDataParam("TEXT", "");
		var _hasInlineAttachment = (this.getTextDataParam("HAS_INLINE_ATTACHMENT", "") === 'Y');
		if ((cleanText.length <= 128 && !_hasInlineAttachment) || this._mode === BX.Crm.TimelineEditorMode.edit)
		{
			this._isCollapsed = false;

			wrapper.appendChild(
				BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
			);
		}
		else
		{
			this._isCollapsed = true;

			wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-section-content crm-entity-stream-section-content-collapsed" },
						children:
						[
							content
						]
					}
				)
			);

			wrapper.querySelector(".crm-entity-stream-content-event").appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-section-content-expand-btn-container" },
						children:
							[
								BX.create("A",
									{
										attrs:
											{
												className: "crm-entity-stream-section-content-expand-btn",
												href: "#"
											},
										events:
											{
												click: BX.delegate(this.onExpandButtonClick, this)
											},
										text: this.getMessage("expand")
									}
								)
							]
					}
				)
			);
		}

		if (this._mode === BX.Crm.TimelineEditorMode.view && this._hasFiles)
		{
			this._textLoaded = false;
			this._fileBlock = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-files-inner" },
					children: [BX.create("DIV", { attrs: { className: "crm-timeline-wait" }})]
				});
			wrapper.querySelector(".crm-entity-stream-section-content").appendChild(
				BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-files" },
					children: [this._fileBlock]
				})
			);
			BX.ready(BX.delegate(function() {
				window.setTimeout(BX.delegate(function(){
					this.loadContent(this._fileBlock, "GET_FILE_BLOCK")
				} ,this), 100);
			},this));
		}

		return wrapper;
	};
	BX.CrmHistoryItemComment.prototype.prepareActions = function()
	{
		if (this._mode === BX.Crm.TimelineEditorMode.view && BX.type.isDomNode(this._commentWrapper))
		{
			this.registerImages(this._commentWrapper);
			if (!BX.getClass('BX.Disk.apiVersion'))
			{
				BX.viewElementBind(
					this._commentWrapper,
					{showTitle: true},
					function(node){
						return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
					}
				);
			}
		}
	};
	BX.CrmHistoryItemComment.prototype.loadContent = function(node, type)
	{
		if (!BX.type.isDomNode(node))
			return;

		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "GET_COMMENT_CONTENT",
						"ID": this.getId(),
						"ENTITY_TYPE_ID": this.getOwnerTypeId(),
						"ENTITY_ID": this.getOwnerId(),
						"TYPE": type
					},
				onsuccess: BX.delegate(function(result)
				{
					if (BX.type.isNotEmptyString(result.ERROR) && type === 'GET_FILE_BLOCK')
					{
						BX.remove(node);
						return;
					}

					if (BX.type.isNotEmptyString(result.BLOCK))
					{
						var promise = BX.html(node, result.BLOCK);
						promise.then(
							BX.delegate(function(){
								this.registerImages(node);
								BX.LazyLoad.showImages();
							}, this)
						);
					}
				}, this)
			}
		);
	};
	BX.CrmHistoryItemComment.prototype.loadEditor = function()
	{
		this._editorName = 'CrmTimeLineComment'+this._id + BX.util.getRandomString(4);
		if (this._postForm)
		{
			this._postForm.oEditor.SetContent(this._commentMessage);
			this._editor.ReInitIframe();
			return;
		}

		actionData = {
			data: {
				id: this._id,
				name: this._editorName
			}
		};
		BX.ajax.runAction("crm.api.timeline.loadEditor", actionData)
			.then(this.onLoadEditorSuccess.bind(this))
			.catch(	this.switchToViewMode.bind(this));
	};
	BX.CrmHistoryItemComment.prototype.onLoadEditorSuccess = function(result)
	{
		if (!BX.type.isDomNode(this._editorContainer))
			this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});

		var html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
		BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor,this));
	};
	BX.CrmHistoryItemComment.prototype.showEditor = function()
	{
		if (LHEPostForm)
		{
			window.setTimeout(BX.delegate(function(){
				this._postForm = LHEPostForm.getHandler(this._editorName);
				this._editor = BXHtmlEditor.Get(this._editorName);
				BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
				this._commentMessage = this._postForm.oEditor.GetContent();
			} ,this), 0);
		}
	};
	BX.CrmHistoryItemComment.prototype.onFinishFasten = function()
	{
		this.registerImages(this._commentWrapper);
		if (BX.type.isDomNode(this._fileBlock))
			this.registerImages(this._fileBlock);
		BX.LazyLoad.showImages();
	};
	BX.CrmHistoryItemComment.prototype.registerImages = function(node)
	{
		var commentImages = node.querySelectorAll('[data-bx-viewer="image"]');
		var commentImagesLength = commentImages.length;
		if (commentImagesLength > 0)
		{
			var idsList = [];
			for (var i = 0; i < commentImagesLength; ++i)
			{
				if (BX.type.isDomNode(commentImages[i]))
				{
					commentImages[i].id += BX.util.getRandomString(4);
					idsList.push(commentImages[i].id);
				}
			}

			if (idsList.length > 0)
			{
				BX.LazyLoad.registerImages(idsList);
			}
		}
		BX.LazyLoad.registerImages(idsList);
	};
	BX.CrmHistoryItemComment.prototype.ensureGhostCreated = function()
	{
		if(this._ghostInput)
		{
			return this._ghostInput;
		}

		this._ghostInput = BX.create('div', {
			props: { className: 'crm-entity-stream-content-new-comment-textarea-shadow' },
			text: this._input.value
		});

		this._ghostInput.style.width = this._input.offsetWidth + 'px';
		document.body.appendChild(this._ghostInput);
		return this._ghostInput;
	};
	BX.CrmHistoryItemComment.prototype.toggleMode = function(type)
	{
		this._mode = parseInt(type);
		this._hasFiles = (this.getTextDataParam("HAS_FILES") === 'Y');
		this.refreshLayout();
		this.closeContextMenu();
	};

	BX.CrmHistoryItemComment.prototype.switchToViewMode = function(e)
	{
		// if (LHEPostForm)
		// 	LHEPostForm.unsetHandler(this._editorName);
		this.toggleMode(BX.Crm.TimelineEditorMode.view);
	};

	BX.CrmHistoryItemComment.prototype.switchToEditMode = function(e)
	{
		var tagName = e.target.tagName.toLowerCase();
		if (tagName === 'a'
			|| tagName === 'img'
			|| BX.hasClass(e.target, "feed-con-file-changes-link-more")
			|| BX.hasClass(e.target, "feed-com-file-inline")
		)
		{
			return;
		}

		this.toggleMode(BX.Crm.TimelineEditorMode.edit);
		window.setTimeout(BX.delegate(function(){
			this.loadEditor();
		} ,this), 100);
	};

	BX.CrmHistoryItemComment.prototype.prepareContextMenuItems = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		var menuItems = [];

		if (!this.isReadOnly())
		{
			if (this._mode !== BX.Crm.TimelineEditorMode.edit)
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.switchToEditMode, this)});
			}
			else
			{
				menuItems.push({ id: "cancel", text: this.getMessage("menuCancel"), onclick: BX.delegate(this.switchToViewMode, this)});
			}

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}

		return menuItems;
	};

	BX.CrmHistoryItemComment.prototype.save = function(e)
	{
		var attachmentList = [];
		var text = "";
		if (this._postForm)
		{
			text = this._postForm.oEditor.GetContent();
			this._commentMessage = text;
			var controllerList = [];
			for (var fileKey in this._postForm.arFiles)
			{
				if (this._postForm.arFiles.hasOwnProperty(fileKey))
				{
					var controllerId = this._postForm.arFiles[fileKey];
					(controllerList.indexOf(controllerId) === -1) ? controllerList.push(controllerId) : null;
				}
			}

			for (var i = 0, length = controllerList.length; i < length; i++)
			{
				for (var fileId in this._postForm.controllers[controllerList[i]].values)
				{
					if (this._postForm.controllers[controllerList[i]].values.hasOwnProperty(fileId)
						&& attachmentList.indexOf(fileId) === -1
					)
					{
						attachmentList.push(fileId);
					}

				}
			}
		}
		else
		{
			text = this._input.value;
		}

		if (!BX.type.isNotEmptyString(text))
		{
			if (!this.emptyCommentMessage)
			{
				this.emptyCommentMessage = new BX.PopupWindow(
					'timeline_empty_comment_' + this._id,
					e.target,
					{
						content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
						darkMode: true,
						autoHide: true,
						zIndex: 990,
						angle: {position: 'top', offset: 77},
						closeByEsc: true,
						bindOptions: { forceBindPosition: true}
					}
				);
			}

			this.emptyCommentMessage.show();
			return;
		}

		if(this._isRequestRunning && BX.type.isNotEmptyString(text))
		{
			return;
		}

		this._isRequestRunning = true;
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "UPDATE_COMMENT",
						"ID": this.getId(),
						"TEXT": text,
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ATTACHMENTS": attachmentList
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);
	};

	BX.CrmHistoryItemComment.prototype.processRemoval = function()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getMessage('commentRemove')
				}
			);
		}

		dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	};

	BX.CrmHistoryItemComment.prototype.onRemovalConfirm = function(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.remove();
	};
	BX.CrmHistoryItemComment.prototype.onRemovalCancel = function()
	{
	};

	BX.CrmHistoryItemComment.prototype.remove = function(e)
	{
		if(this._isRequestRunning)
		{
			return;
		}

		var history = this._history._manager.getHistory();
		var deleteItem = history.findItemById(this._id);
		if (deleteItem instanceof BX.CrmHistoryItemComment)
			deleteItem.clearAnimate();

		var fixedHistory = this._history._manager.getFixedHistory();
		var deleteFixedItem = fixedHistory.findItemById(this._id);
		if (deleteFixedItem instanceof BX.CrmHistoryItemComment)
			deleteFixedItem.clearAnimate();

		this._isRequestRunning = true;
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "DELETE_COMMENT",
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this.getId()
					},
				onsuccess: BX.delegate(this.onRemoveSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);
	};

	BX.CrmHistoryItemComment.prototype.onSaveSuccess = function(data)
	{
		this._isRequestRunning = false;
		var itemData = BX.prop.getObject(data, "HISTORY_ITEM");

		var updateFixedItem = this._fixedHistory.findItemById(this._id);
		if (updateFixedItem instanceof BX.CrmHistoryItemComment)
		{
			if (!BX.type.isNotEmptyString(itemData['IS_FIXED']))
				itemData['IS_FIXED'] = 'Y';

			updateFixedItem.setData(itemData);
			updateFixedItem._id = BX.prop.getString(itemData, "ID");
			updateFixedItem.switchToViewMode();
		}

		var updateItem = this._history.findItemById(this._id);
		if (updateItem instanceof BX.CrmHistoryItemComment)
		{
			updateItem.setData(itemData);
			updateItem._id = BX.prop.getString(itemData, "ID");
			updateItem.switchToViewMode();
		}

		this._postForm = null;
	};

	BX.CrmHistoryItemComment.prototype.onRemoveSuccess = function(data)
	{
	};

	BX.CrmHistoryItemComment.prototype.onRequestFailure = function(data)
	{
		this._isRequestRunning = this._isLocked = false;
	};

	BX.CrmHistoryItemComment.prototype.onExpandButtonClick = function(e)
	{
		if(!this._wrapper)
		{
			return BX.PreventDefault(e);
		}

		var contentWrapper = this._wrapper.querySelector("div.crm-entity-stream-section-content");
		if(!contentWrapper)
		{
			return BX.PreventDefault(e);
		}

		if (this._hasFiles && BX.type.isDomNode(this._commentWrapper) && !this._textLoaded)
		{
			this._textLoaded = true;
			this.loadContent(this._commentWrapper, "GET_TEXT")
		}
		var eventWrapper = contentWrapper.querySelector(".crm-entity-stream-content-event");
		if(this._isCollapsed)
		{
			eventWrapper.style.maxHeight = eventWrapper.scrollHeight + 130 + "px";
			BX.removeClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
			BX.addClass(contentWrapper, "crm-entity-stream-section-content-expand");
			setTimeout(
				BX.delegate(function() {
					eventWrapper.style.maxHeight = "";
					}, this),
				300
			);
		}
		else
		{
			eventWrapper.style.maxHeight = eventWrapper.clientHeight + "px";
			BX.removeClass(contentWrapper, "crm-entity-stream-section-content-expand");
			BX.addClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
			setTimeout(
				BX.delegate(function() {
					eventWrapper.style.maxHeight = "";
					}, this),
				0
			);
		}

		this._isCollapsed = !this._isCollapsed;

		var button = contentWrapper.querySelector("a.crm-entity-stream-section-content-expand-btn");
		if(button)
		{
			button.innerHTML = this.getMessage(this._isCollapsed ? "expand" : "collapse");
		}
		return BX.PreventDefault(e);
	};
	BX.CrmHistoryItemComment.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemComment();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemModification) === "undefined")
{
	BX.CrmHistoryItemModification = function()
	{
		BX.CrmHistoryItemModification.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemModification, BX.CrmHistoryItem);
	BX.CrmHistoryItemModification.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemModification.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmHistoryItemModification.prototype.getTitle = function()
	{
		return this.getTextDataParam("TITLE");
	};
	BX.CrmHistoryItemModification.prototype.prepareContent = function()
	{
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-info" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } })
		);

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = this.prepareHeaderLayout();

		var contentChildren = [];
		if (BX.type.isNotEmptyString(this.getTextDataParam("START_NAME")))
		{
			contentChildren.push(
				BX.create("SPAN",
				{
					attrs: {className: "crm-entity-stream-content-detain-info-status"},
					text: this.getTextDataParam("START_NAME")
				})
			);
			contentChildren.push(
				BX.create("SPAN",{ attrs: { className: "crm-entity-stream-content-detail-info-separator-icon" } })
			);
		}

		if (BX.type.isNotEmptyString(this.getTextDataParam("FINISH_NAME")))
		{
			contentChildren.push(
				BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-detain-info-status" },
					text: this.getTextDataParam("FINISH_NAME")
				})
			);
		}


		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" },
				children:
				[
					BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-info" },
						children: contentChildren
					})
				]
			})
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	};
	if(typeof(BX.CrmHistoryItemModification.messages) === "undefined")
	{
		BX.CrmHistoryItemModification.messages = {};
	}
	BX.CrmHistoryItemModification.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemModification();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemMark) === "undefined")
{
	BX.CrmHistoryItemMark = function()
	{
		BX.CrmHistoryItemMark.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemMark, BX.CrmHistoryItem);
	BX.CrmHistoryItemMark.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemMark.superclass.doInitialize.apply(this);
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmHistoryItemMark. The field 'activityEditor' is not assigned.";
		}
	};
	BX.CrmHistoryItemMark.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemMark.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmHistoryItemMark.prototype.getTitle = function()
	{
		var title = "";
		var entityData = this.getAssociatedEntityData();
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();
		var typeCategoryId = this.getTypeCategoryId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
			var activityProviderId = BX.prop.getString(entityData, "PROVIDER_ID", '');

			if(entityTypeId === BX.CrmActivityType.email)
			{
				if(typeCategoryId === 2) //SUCCESS
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") +
						"SuccessMark"
					);
				}
				else if(typeCategoryId === 3) //RENEW
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") +
						"RenewMark"
					);
				}
			}
			else if(entityTypeId === BX.CrmActivityType.call)
			{
				if(typeCategoryId === 2) //SUCCESS
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") +
						"SuccessMark"
					);
				}
				else if(typeCategoryId === 3) //RENEW
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") +
						"RenewMark"
					);
				}
			}
			else if(entityTypeId === BX.CrmActivityType.meeting)
			{
				if(typeCategoryId === 2) //SUCCESS
				{
					title = this.getMessage("meetingSuccessMark");
				}
				else if(typeCategoryId === 3) //RENEW
				{
					title = this.getMessage("meetingRenewMark");
				}
			}
			else if(entityTypeId === BX.CrmActivityType.task)
			{
				if(typeCategoryId === 2) //SUCCESS
				{
					title = this.getMessage("taskSuccessMark");
				}
				else if(typeCategoryId === 3) //RENEW
				{
					title = this.getMessage("taskRenewMark");
				}
			}
			else if(entityTypeId === BX.CrmActivityType.provider)
			{
				if (activityProviderId === 'CRM_REQUEST')
				{
					if(typeCategoryId === 2) //SUCCESS
					{
						title = this.getMessage("requestSuccessMark");
					}
					else if(typeCategoryId === 3) //RENEW
					{
						title = this.getMessage("requestRenewMark");
					}
				}
				else if(typeCategoryId === 2) //SUCCESS
				{
					title = this.getMessage("webformSuccessMark");
				}
				else if(typeCategoryId === 3) //RENEW
				{
					title = this.getMessage("webformRenewMark");
				}
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.deal)
		{
			if(typeCategoryId === 2) //SUCCESS
			{
				title = this.getMessage("dealSuccessMark");
			}
			else if(typeCategoryId === 5) //FAILED
			{
				title = this.getMessage("dealFailedMark");
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
        {
			if(typeCategoryId === 2) //SUCCESS
			{
				title = this.getMessage("orderSuccessMark");
			}
			else if(typeCategoryId === 5) //FAILED
			{
				title = this.getMessage("orderFailedMark");
			}
        }

		return title;
	};
	BX.CrmHistoryItemMark.prototype.prepareTitleLayout = function()
	{
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();

		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
		{
			return BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					text: this.getTitle()
				}
			);
		}
		else
		{
			return BX.create("A",
				{
					attrs: { href: "#", className: "crm-entity-stream-content-event-title" },
					events: { "click": this._headerClickHandler },
					text: this.getTitle()
				}
			);
		}
	};
	BX.CrmHistoryItemMark.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();

		var wrapper = BX.create(
			"DIV",
			{ attrs: { className: "crm-entity-stream-section crm-entity-stream-section-completed" } }
		);

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = this.prepareHeaderLayout();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			var iconClassName = "crm-entity-stream-section-icon";
			if(entityTypeId === BX.CrmActivityType.email)
			{
				iconClassName += " crm-entity-stream-section-icon-email";
			}
			else if(entityTypeId === BX.CrmActivityType.call)
			{
				iconClassName += " crm-entity-stream-section-icon-call";
			}
			else if(entityTypeId === BX.CrmActivityType.meeting)
			{
				iconClassName += " crm-entity-stream-section-icon-meeting";
			}
			else if(entityTypeId === BX.CrmActivityType.task)
			{
				iconClassName += " crm-entity-stream-section-icon-task";
			}
			else if(entityTypeId === BX.CrmActivityType.provider)
			{
				var providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
				if(providerId === "CRM_WEBFORM")
				{
					iconClassName += " crm-entity-stream-section-icon-crmForm";
				}
			}

			wrapper.appendChild(BX.create("DIV", { attrs: { className: iconClassName } }));
			content.appendChild(header);


			var detailWrapper = BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-content-detail" } }
			);
			content.appendChild(detailWrapper);

			detailWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-title" },
						children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.cutOffText(BX.prop.getString(entityData, "SUBJECT", ""), 128)
								}
							)
						]
					}
				)
			);

			var summary = this.getTextDataParam("SUMMARY");
			if(summary !== "")
			{
				detailWrapper.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-entity-stream-content-detail-description" },
							text: summary
						}
					)
				);
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
		{
            wrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } }));
            content.appendChild(header);
            content.appendChild(
                BX.create("DIV",
                    {
                        attrs: { className: "crm-entity-stream-content-detail" },
                        text: this.cutOffText(this.getTextDataParam("MESSAGE"), 128)
                    }
                )
            );
		}
		else
		{
			wrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } }));
			content.appendChild(header);
			content.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail" },
						text: this.cutOffText(BX.prop.getString(entityData, "TITLE", ""), 128)
					}
				)
			);
		}

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);
		return wrapper;
	};
	BX.CrmHistoryItemMark.prototype.view = function()
	{
		var entityData = this.getAssociatedEntityData();
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.viewActivity(id);
			}
		}
		else
		{
			var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
			if(showUrl !== "")
			{
				BX.Crm.Page.open(showUrl);
			}
		}
	};
	if(typeof(BX.CrmHistoryItemMark.messages) === "undefined")
	{
		BX.CrmHistoryItemMark.messages = {};
	}
	BX.CrmHistoryItemMark.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemMark();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemCreation) === "undefined")
{
	BX.CrmHistoryItemCreation = function()
	{
		BX.CrmHistoryItemCreation.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemCreation, BX.CrmHistoryItem);
	BX.CrmHistoryItemCreation.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemCreation.superclass.doInitialize.apply(this);
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmHistoryItemCreation. The field 'activityEditor' is not assigned.";
		}
	};
	BX.CrmHistoryItemCreation.prototype.getTitle = function()
	{
		var entityTypeId = this.getAssociatedEntityTypeId();
		var entityData = this.getAssociatedEntityData();
		if(entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var typeId = BX.prop.getInteger(entityData, "TYPE_ID");
			var title = this.getMessage(typeId === BX.CrmActivityType.task ? "task" : "activity");
			return title.replace(/#TITLE#/gi, this.cutOffText(BX.prop.getString(entityData, "SUBJECT")), 64);
		}

		var msg = this.getMessage(BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase());
		if(!BX.type.isNotEmptyString(msg))
		{
			msg = this.getTextDataParam("TITLE");
		}

		return msg;
	};
	BX.CrmHistoryItemCreation.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-createEntity";
	};
	BX.CrmHistoryItemCreation.prototype.prepareContentDetails = function()
	{
		var entityTypeId = this.getAssociatedEntityTypeId();
		var entityId = this.getAssociatedEntityId();
		var entityData = this.getAssociatedEntityData();

		if(entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var link = BX.create("A",
				{
					attrs: { href: "#" },
					html: this.cutOffText(BX.prop.getString(entityData, "DESCRIPTION_RAW"), 128)
				}
			);
			BX.bind(link, "click", this._headerClickHandler);

			return [ link ];
		}

		var title = BX.prop.getString(entityData, "TITLE", "");
		var htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
		var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		if(title !== "" || htmlTitle !== "")
		{
			var nodes = [];
			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				var spanAttrs = (htmlTitle !== "") ? { html: htmlTitle } : { text: title };
				nodes.push(BX.create("SPAN", spanAttrs));
			}
			else
			{
				var linkAttrs = { attrs: { href: showUrl }, text: title };
				if (htmlTitle !== "")
				{
					linkAttrs = { attrs: { href: showUrl }, html: htmlTitle };
				}
				nodes.push(BX.create("A", linkAttrs));
			}

			var legend = this.getTextDataParam("LEGEND");
			if(legend !== "")
			{
				nodes.push(BX.create("BR"));
				nodes.push(BX.create("SPAN", { text: legend }));
			}

			var baseEntityData = this.getObjectDataParam("BASE");
			var baseEntityInfo = BX.prop.getObject(baseEntityData, "ENTITY_INFO");
			if(baseEntityInfo)
			{
				nodes.push(BX.create("BR"));
				nodes.push(BX.create("SPAN", { text: BX.prop.getString(baseEntityData, "CAPTION") + ": " }));
				nodes.push(
					BX.create("A",
						{
							attrs: { href: BX.prop.getString(baseEntityInfo, "SHOW_URL", "#") },
							text: BX.prop.getString(baseEntityInfo, "TITLE", "")
						}
					)
				);
			}
			return nodes;
		}
		return [];
	};
	BX.CrmHistoryItemCreation.prototype.view = function()
	{
		var entityTypeId = this.getAssociatedEntityTypeId();
		if(entityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityData = this.getAssociatedEntityData();
			var id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.viewActivity(id);
			}
		}
	};
	BX.CrmHistoryItemCreation.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemCreation.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistoryItemCreation.messages) === "undefined")
	{
		BX.CrmHistoryItemCreation.messages = {};
	}
	BX.CrmHistoryItemCreation.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemCreation();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemRestoration) === "undefined")
{
	BX.CrmHistoryItemRestoration = function()
	{
		BX.CrmHistoryItemRestoration.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemRestoration, BX.CrmHistoryItem);
	BX.CrmHistoryItemRestoration.prototype.getTitle = function()
	{
		return this.getTextDataParam("TITLE");
	};
	BX.CrmHistoryItemRestoration.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-restoreEntity";
	};
	BX.CrmHistoryItemRestoration.prototype.prepareContentDetails = function()
	{
		var entityData = this.getAssociatedEntityData();
		var title = BX.prop.getString(entityData, "TITLE");
		return title !== "" ?  [ BX.create("SPAN", { text: title }) ] : [];
	};
	BX.CrmHistoryItemRestoration.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemRestoration.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistoryItemRestoration.messages) === "undefined")
	{
		BX.CrmHistoryItemRestoration.messages = {};
	}
	BX.CrmHistoryItemRestoration.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemRestoration();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemLink) === "undefined")
{
	BX.CrmHistoryItemLink = function()
	{
		BX.CrmHistoryItemLink.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemLink, BX.CrmHistoryItem);
	BX.CrmHistoryItemLink.prototype.getTitle = function()
	{
		return this.getMessage(BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase());
	};
	BX.CrmHistoryItemLink.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-createEntity";
	};
	BX.CrmHistoryItemLink.prototype.prepareContentDetails = function()
	{
		var entityData = this.getAssociatedEntityData();
		var nodes = [];
		nodes.push(
			BX.create("A",
				{
					attrs: { href: BX.prop.getString(entityData, "SHOW_URL", "#") },
					text: BX.prop.getString(entityData, "TITLE")
				}
			)
		);
		return nodes;
	};
	BX.CrmHistoryItemLink.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemLink.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistoryItemLink.messages) === "undefined")
	{
		BX.CrmHistoryItemLink.messages = {};
	}
	BX.CrmHistoryItemLink.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemLink();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemEmail) === "undefined")
{
	BX.CrmHistoryItemEmail = function()
	{
		BX.CrmHistoryItemEmail.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemEmail, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemEmail.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());

		var entityData = this.getAssociatedEntityData();
		var emailInfo =  BX.prop.getObject(entityData, "EMAIL_INFO", null);
		var statusText = emailInfo !== null ? BX.prop.getString(emailInfo, "STATUS_TEXT", "") : "";

		if(statusText !== "")
		{
			header.appendChild(
				BX.create(
					"SPAN",
					{
						props: { className: "crm-entity-stream-content-event-skipped" },
						text: statusText
					}
				)
			);
		}

		var markNode = this.prepareMarkLayout();
		if(markNode)
		{
			header.appendChild(markNode);
		}

		header.appendChild(this.prepareTimeLayout());
		return header;
	};
	BX.CrmHistoryItemEmail.prototype.prepareContextMenuItems = function ()
	{
		if (this._isMenuShown)
		{
			return;
		}

		var menuItems = [];

		if (!this.isReadOnly())
		{
			menuItems.push({id: "view", text: this.getMessage("menuView"), onclick: BX.delegate(this.view, this)});

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}

		return menuItems;
	};
	BX.CrmHistoryItemEmail.prototype.reply = function ()
	{
	};
	BX.CrmHistoryItemEmail.prototype.replyAll = function ()
	{
	};
	BX.CrmHistoryItemEmail.prototype.forward = function ()
	{
	};
	BX.CrmHistoryItemEmail.prototype.getRemoveMessage = function()
	{
		var title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage('emailRemove').replace("#TITLE#", title);
	};
	BX.CrmHistoryItemEmail.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "VALUE", "");

		var outerWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-email" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-email" }
				}
			)
		);

		if (this.isFixed())
			BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		var header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-email" }
			}
		);
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: [ detailWrapper ]
				}
			)
		);

		//TODO: Add status text
		/*
		detailWrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-email-read-status" } })
		);
		*/

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-email-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		var communicationWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-content-detail-email-to" } }
		);
		detailWrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(communicationValue !== "")
		{
			if(communicationTitle !== "")
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: " " }));
			}
			communicationWrapper.appendChild(
				BX.create(
					"SPAN",
					{
						attrs: { className: "crm-entity-stream-content-detail-email-address" },
						text: communicationValue
					}
				)
			);
		}

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-email-fragment" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		wrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		return outerWrapper;
	};
	BX.CrmHistoryItemEmail.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmHistoryEmailAction.create(
				"email",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor
				}
			)
		);
	};
	BX.CrmHistoryItemEmail.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemEmail.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemEmail();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemCall) === "undefined")
{
	BX.CrmHistoryItemCall = function()
	{
		BX.CrmHistoryItemCall.superclass.constructor.apply(this);
		this._playerDummyClickHandler = BX.delegate(this.onPlayerDummyClick, this);
		this._playerWrapper = null;
		this._transcriptWrapper = null;
		this._mediaFileInfo = null;
	};
	BX.extend(BX.CrmHistoryItemCall, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemCall.prototype.getTypeDescription = function()
	{
		var entityData = this.getAssociatedEntityData();
		var callInfo =  BX.prop.getObject(entityData, "CALL_INFO", null);
		var callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
		if(callTypeText !== "")
		{
			return callTypeText;
		}

		var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	};
	BX.CrmHistoryItemCall.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());

		//Position is important
		var entityData = this.getAssociatedEntityData();
		var callInfo =  BX.prop.getObject(entityData, "CALL_INFO", null);
		var hasCallInfo = callInfo !== null;
		var isSuccessfull = hasCallInfo ? BX.prop.getBoolean(callInfo, "SUCCESSFUL", false) : false;
		var statusText = hasCallInfo ? BX.prop.getString(callInfo, "STATUS_TEXT", "") : "";

		if(hasCallInfo)
		{
			header.appendChild(
				BX.create("DIV",
					{
						attrs:
							{
								className: isSuccessfull
									? "crm-entity-stream-content-event-successful"
									: "crm-entity-stream-content-event-missing"
							},
						text: statusText
					}
				)
			);
		}

		header.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		);

		return header;
	};
	BX.CrmHistoryItemCall.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "VALUE", "");
		var communicationValueFormatted = BX.prop.getString(communication, "FORMATTED_VALUE", communicationValue);

		var callInfo =  BX.prop.getObject(entityData, "CALL_INFO", null);
		var hasCallInfo = callInfo !== null;
		var durationText = hasCallInfo ? BX.prop.getString(callInfo, "DURATION_TEXT", "") : "";
		var hasTranscript = hasCallInfo ? BX.prop.getBoolean(callInfo, "HAS_TRANSCRIPT", "") : "";
		var isTranscriptPending = hasCallInfo ? BX.prop.getBoolean(callInfo, "TRANSCRIPT_PENDING", "") : "";
		var callId = hasCallInfo ? BX.prop.getString(callInfo, "CALL_ID", "") : "";
		var callComment = hasCallInfo ? BX.prop.getString(callInfo, "COMMENT", "") : "";

		var outerWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-call" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-call" }
				}
			)
		);

		if (this.isFixed())
			BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		var header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" }
			}
		);
		wrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		if(hasCallInfo)
		{
			var callInfoWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-call" }
				}
			);
			detailWrapper.appendChild(callInfoWrapper);

			this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
			if(this._mediaFileInfo !== null)
			{
				this._playerWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-audio-cap-wrap-container"}
					}
				);
				this._playerWrapper.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-audio-cap-wrap" },
							children:
								[
									BX.create(
										"DIV",
										{ attrs: { className: "crm-audio-cap-time" }, text: durationText }
									)
								],
							events: { click: this._playerDummyClickHandler }
						}
					)
				);
				callInfoWrapper.appendChild(
					//crm-entity-stream-content-detail-call
					this._playerWrapper
				);
			}

			if(hasTranscript)
			{
				this._transcriptWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-audio-transcript-wrap-container"},
						events: {
							click: function(e)
							{
								if(BX.Voximplant && BX.Voximplant.Transcript)
								{
									BX.Voximplant.Transcript.create({
										callId: callId
									}).show();
								}
							}
						},
						children: [
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-icon"}	}),
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-conversation"}, text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT") } )
						]
					}
				);
				callInfoWrapper.appendChild(this._transcriptWrapper);
			}
			else if(isTranscriptPending)
			{
				this._transcriptWrapper = BX.create("DIV",
					{
						attrs: { className: "crm-audio-transcript-wrap-container-pending"},
						children: [
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-icon-pending"}, html: '<svg class="crm-transcript-loader-circular" viewBox="25 25 50 50"><circle class="crm-transcript-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"></circle></svg>'}),
							BX.create("DIV", { attrs: { className: "crm-audio-transcript-conversation"}, text: BX.message("CRM_TIMELINE_CALL_TRANSCRIPT_PENDING") } )
						]
					}
				);
				callInfoWrapper.appendChild(this._transcriptWrapper);
			}

			if(callComment)
			{
				detailWrapper.appendChild(BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-description"},
						text: callComment
					}
				));
			}
		}
		var communicationWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-contact-info" }
			}
		);
		detailWrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(communicationValueFormatted !== "")
		{
			if(communicationTitle !== "")
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: " " }));
			}
			communicationWrapper.appendChild(
				BX.create(
					"SPAN",
					{
						attrs: { className: "crm-entity-stream-content-detail-email-address" },
						text: communicationValueFormatted
					}
				)
			);
		}

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		wrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		return outerWrapper;
	};
	BX.CrmHistoryItemCall.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmHistoryCallAction.create(
				"call",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._history.getOwnerInfo()
				}
			)
		);
	};
	BX.CrmHistoryItemCall.prototype.getRemoveMessage = function()
	{
		var entityData = this.getAssociatedEntityData();
		var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		var messageName =  (direction === BX.CrmActivityDirection.incoming) ? 'incomingCallRemove' : 'outgoingCallRemove';
		var title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage(messageName).replace("#TITLE#", title);
	};
	BX.CrmHistoryItemCall.prototype.onPlayerDummyClick = function(e)
	{
		var stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
		if(stubNode)
		{
			BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
		}

		this._history.getManager().loadMediaPlayer(
			"history_" + this.getId(),
			this._mediaFileInfo["URL"],
			this._mediaFileInfo["TYPE"],
			this._playerWrapper,
			this._mediaFileInfo["DURATION"]
		);
	};
	BX.CrmHistoryItemCall.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemCall();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemMeeting) === "undefined")
{
	BX.CrmHistoryItemMeeting = function()
	{
		BX.CrmHistoryItemMeeting.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemMeeting, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemMeeting.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());

		var markNode = this.prepareMarkLayout();
		if(markNode)
		{
			header.appendChild(markNode);
		}

		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemMeeting.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "VALUE", "");

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-meeting" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-meeting" } })
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		var communicationWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-contact-info" } } );
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			communicationWrapper.appendChild(
				BX.create("SPAN",
					{ text: this.getMessage("reciprocal") + ": " }
				)
			);

			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		communicationWrapper.appendChild(BX.create("SPAN", { text: " " + communicationValue }));

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemMeeting.prototype.getRemoveMessage = function()
	{
		var title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage('meetingRemove').replace("#TITLE#", title);
	};
	BX.CrmHistoryItemMeeting.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemMeeting.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemMeeting.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemMeeting();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemTask) === "undefined")
{
	BX.CrmHistoryItemTask = function()
	{
		BX.CrmHistoryItemTask.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemTask, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemTask.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());

		var markNode = this.prepareMarkLayout();
		if(markNode)
		{
			header.appendChild(markNode);
		}

		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemTask.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-task" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-task" } })
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		var communicationWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-contact-info" } } );
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			communicationWrapper.appendChild(
				BX.create("SPAN",
					{ text: this.getMessage("reciprocal") + ": " }
				)
			);

			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemTask.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemTask.prototype.getRemoveMessage = function()
	{
		var title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage('taskRemove').replace("#TITLE#", title);
	};
	BX.CrmHistoryItemTask.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemTask.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemTask();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemWebForm) === "undefined")
{
	BX.CrmHistoryItemWebForm = function()
	{
		BX.CrmHistoryItemWebForm.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemWebForm, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemWebForm.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemWebForm.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-crmForm" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm" } })
		);

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemWebForm.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemWebForm.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemWebForm.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemWebForm();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemWait) === "undefined")
{
	BX.CrmHistoryItemWait = function()
	{
		BX.CrmHistoryItemWait.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemWait, BX.CrmHistoryItem);
	BX.CrmHistoryItemWait.prototype.getTitle = function()
	{
		return this.getMessage("wait");
	};
	BX.CrmHistoryItemWait.prototype.prepareTitleLayout = function()
	{
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				BX.create("A", {
					attrs: { href: "#" },
					events: { "click": this._headerClickHandler },
					text: this.getTitle()
				})
			]
		});


	};
	BX.CrmHistoryItemWait.prototype.prepareTimeLayout = function()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	};
	BX.CrmHistoryItemWait.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemWait.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();
		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			description = BX.util.trim(description);
			description = BX.util.strip_tags(description);
			description = BX.util.nl2br(description);
		}

		var wrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait" }
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete" }
				}
			)
		);

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" },
				html: description
			}
		);
		contentWrapper.appendChild(detailWrapper);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItemWait.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemWait.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemWait.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemWait();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemDocument) === "undefined")
{
	BX.CrmHistoryItemDocument = function()
	{
		BX.CrmHistoryItemDocument.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemDocument, BX.CrmHistoryItem);
	BX.CrmHistoryItemDocument.prototype.getTitle = function()
	{
		return this.getMessage("document");
	};
	BX.CrmHistoryItemDocument.prototype.prepareTitleLayout = function()
	{
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				BX.create("A", {
					attrs: { href: "#" },
					events: { "click": BX.delegate(this.editDocument, this) },
					text: this.getTitle()
				})
			]
		});
	};
	BX.CrmHistoryItemDocument.prototype.prepareTimeLayout = function()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	};
	BX.CrmHistoryItemDocument.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemDocument.prototype.prepareContent = function()
	{
		var text = this.getTextDataParam("COMMENT", "");

		var wrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-document" }
			}
		);

		if(this.isFixed())
		{
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
		}

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-document" }
				}
			)
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if(!this.isReadOnly())
		{
			wrapper.appendChild(this.prepareFixedSwitcherLayout());
		}

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" },
				html: text
			}
		);
		var title = BX.findChildByClassName(detailWrapper, 'document-title-link');
		if(title)
		{
			BX.bind(title, 'click', BX.proxy(this.editDocument, this));
		}
		contentWrapper.appendChild(detailWrapper);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItemDocument.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemDocument.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemDocument.prototype.prepareContextMenuItems = function()
	{
		if(this._isMenuShown)
		{
			return;
		}

		var menuItems = [];

		if(!this.isReadOnly())
		{
			menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.editDocument, this)});
			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.confirmDelete, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
			{
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			}
			else
			{
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
			}
		}

		return menuItems;
	};
	BX.CrmHistoryItemDocument.prototype.confirmDelete = function()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getMessage('documentRemove')
				}
			);
		}

		dlg.open().then(BX.delegate(this.onConfirmDelete, this), BX.DoNothing);
	};
	BX.CrmHistoryItemDocument.prototype.onConfirmDelete = function(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.deleteDocument();
	};
	BX.CrmHistoryItemDocument.prototype.deleteDocument = function()
	{
		if(this._isRequestRunning)
		{
			return;
		}
		this._isRequestRunning = true;
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "DELETE_DOCUMENT",
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this.getId()
					},
				onsuccess: BX.delegate(function(result)
				{
					this._isRequestRunning = false;
					if(BX.type.isNotEmptyString(result.ERROR))
					{
						alert(result.ERROR);
					}
					else
					{
						var deleteItem = this._history.findItemById(this._id);
						if (deleteItem instanceof BX.CrmHistoryItemDocument)
						{
							deleteItem.clearAnimate();
						}

						var deleteFixedItem = this._fixedHistory.findItemById(this._id);
						if (deleteFixedItem instanceof BX.CrmHistoryItemDocument)
						{
							deleteFixedItem.clearAnimate();
						}
					}
				}, this),
				onfailure: BX.delegate(function()
				{
					this._isRequestRunning = false;
				}, this)
			}
		);
	};
	BX.CrmHistoryItemDocument.prototype.editDocument = function()
	{
		var documentId = this.getData().DOCUMENT_ID || 0;
		if(documentId > 0)
		{
			var url = '/bitrix/components/bitrix/crm.document.view/slider.php';
			url = BX.util.add_url_param(url, {documentId: documentId});
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(url, {width: 980});
			}
			else
			{
				top.location.href = url;
			}
		}
	};
	BX.CrmHistoryItemDocument.prototype.updateWrapper = function()
	{
		var wrapper = this.getWrapper();
		if(wrapper)
		{
			var detailWrapper = BX.findChildByClassName(wrapper, 'crm-entity-stream-content-detail');
			if(detailWrapper)
			{
				BX.adjust(detailWrapper, {html: this.getTextDataParam("COMMENT", "")});
				var title = BX.findChildByClassName(detailWrapper, 'document-title-link');
				if(title)
				{
					BX.bind(title, 'click', BX.proxy(this.editDocument, this));
				}
			}
		}
	};
	BX.CrmHistoryItemDocument.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemDocument();
		self.initialize(id, settings);
		return self;
	};
};

if(typeof(BX.CrmHistoryItemSender) === "undefined")
{
	BX.CrmHistoryItemSender = function()
	{
		BX.CrmHistoryItemSender.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemSender, BX.CrmHistoryItem);
	BX.CrmHistoryItemSender.prototype.getDataSetting = function(name)
	{
		var settings = this.getObjectDataParam('SETTINGS') || {};
		return settings[name] || null;
	};
	BX.CrmHistoryItemSender.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemSender.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmHistoryItemSender.prototype.getTitle = function()
	{
		return this.getDataSetting('messageName');
	};
	BX.CrmHistoryItemSender.prototype.prepareTitleLayout = function()
	{
		var self = this;
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				this.isRemoved()
					?
					BX.create("SPAN", {text: this.getTitle()})
					:
					BX.create("A", {
						attrs: {
							href: ""
						},
						events: {
							"click": function (e)
							{
								if (BX.SidePanel)
								{
									BX.SidePanel.Instance.open(self.getDataSetting('path'));
								}
								else
								{
									top.location.href = self.getDataSetting('path');
								}

								e.preventDefault();
								e.stopPropagation();
							}
						},
						text: this.getTitle()
					})
			]
		});


	};
	BX.CrmHistoryItemSender.prototype.prepareTimeLayout = function()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	};
	BX.CrmHistoryItemSender.prototype.prepareStatusLayout = function()
	{
		var layoutClassName, textCaption;
		if (this.getDataSetting('isUnsub'))
		{
			textCaption = this.getMessage('unsub');
			layoutClassName = "crm-entity-stream-content-event-missing";
		}
		else if (this.getDataSetting('isClick'))
		{
			textCaption = this.getMessage('click');
			layoutClassName = "crm-entity-stream-content-event-successful";
		}
		else
		{
			textCaption = this.getMessage('read');
			layoutClassName = "crm-entity-stream-content-event-skipped";
		}

		return BX.create("SPAN", {attrs: {className: layoutClassName}, text: textCaption});
	};
	BX.CrmHistoryItemSender.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		if (this.getDataSetting('isRead') || this.getDataSetting('isUnsub'))
		{
			header.appendChild(this.prepareStatusLayout());
		}
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemSender.prototype.isRemoved = function()
	{
		return !this.getDataSetting('letterTitle');
	};
	BX.CrmHistoryItemSender.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();
		var description = this.isRemoved() ? this.getMessage('removed') : this.getMessage('title') + ': ' + this.getDataSetting('letterTitle');

		var wrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait" }
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete" }
				}
			)
		);

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" },
				html: description
			}
		);
		contentWrapper.appendChild(detailWrapper);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItemSender.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemSender();
		self.initialize(id, settings);
		return self;
	};
}


if(typeof(BX.CrmHistoryItemBizproc) === "undefined")
{
	BX.CrmHistoryItemBizproc = function()
	{
		BX.CrmHistoryItemBizproc.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemBizproc, BX.CrmHistoryItem);
	BX.CrmHistoryItemBizproc.prototype.getTitle = function()
	{
		return this.getMessage("bizproc");
	};
	BX.CrmHistoryItemBizproc.prototype.prepareContent = function()
	{
		var wrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-bp" }
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-bp" } }
			)
		);

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = this.prepareHeaderLayout();

		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-content-detail-description" },
									html: this.prepareContentTextHtml()
								}
							)
						]
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	};
	BX.CrmHistoryItemBizproc.prototype.prepareContentTextHtml = function()
	{
		var type = this.getTextDataParam("TYPE");
		if (type === 'ACTIVITY_ERROR')
		{
			return '<strong>#TITLE#</strong>: #ERROR_TEXT#'
				.replace('#TITLE#', BX.util.htmlspecialchars(this.getTextDataParam("ACTIVITY_TITLE")))
				.replace('#ERROR_TEXT#', BX.util.htmlspecialchars(this.getTextDataParam("ERROR_TEXT")))
		}

		var workflowName = this.getTextDataParam("WORKFLOW_TEMPLATE_NAME");
		var workflowStatus = this.getTextDataParam("WORKFLOW_STATUS_NAME");
		if (!workflowName
			|| workflowStatus !== 'Created' && workflowStatus !== 'Completed' && workflowStatus !== 'Terminated'
		)
		{
			return BX.util.htmlspecialchars(this.getTextDataParam("COMMENT"));
		}

		var label = BX.message('CRM_TIMELINE_BIZPROC_CREATED');
		if (workflowStatus === 'Completed')
		{
			label = BX.message('CRM_TIMELINE_BIZPROC_COMPLETED');
		}
		else if (workflowStatus === 'Terminated')
		{
			label = BX.message('CRM_TIMELINE_BIZPROC_TERMINATED');
		}

		return BX.util.htmlspecialchars(label)
			.replace('#NAME#', '<strong>' + BX.util.htmlspecialchars(workflowName) + '</strong>');
	};
	BX.CrmHistoryItemBizproc.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemBizproc();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemSms) === "undefined")
{
	BX.CrmHistoryItemSms = function()
	{
		BX.CrmHistoryItemSms.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemSms, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemSms.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareMessageStatusLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemSms.prototype.prepareMessageStatusLayout = function()
	{
		return this._messageStatusNode = BX.create("SPAN");
	};
	BX.CrmHistoryItemSms.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "VALUE", "");
		var smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});

		var wrapperClassName = "crm-entity-stream-section-sms";
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history" + " " + wrapperClassName } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-sms" } })
		);

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		var messageWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-sms" } });

		if (smsInfo.senderId)
		{
			var senderId = smsInfo.senderId;
			var senderName = smsInfo.senderShortName;
			if (senderId === 'rest' && smsInfo.fromName)
			{
				senderName = smsInfo.fromName;
			}

			var messageSenderWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-sms-status"},
				children: [
					BX.message('CRM_TIMELINE_SMS_SENDER')+' ',
					BX.create('STRONG', {text: senderName})
				]
			});
			if (senderId !== 'rest' && smsInfo.fromName)
			{
				messageSenderWrapper.innerHTML += ' '+BX.message('CRM_TIMELINE_SMS_FROM')+' ';
				messageSenderWrapper.appendChild(BX.create('STRONG', {text: smsInfo.fromName}));
			}
			messageWrapper.appendChild(messageSenderWrapper);
		}

		if (smsInfo.statusId !== '')
		{
			this.setMessageStatus(smsInfo.statusId, smsInfo.errorText);
		}

		var bodyText = BX.util.htmlspecialchars(entityData['DESCRIPTION_RAW']).replace(/\r\n|\r|\n/g, "<br/>");
		var messageBodyWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-sms-fragment" } });
		messageBodyWrapper.appendChild(BX.create('SPAN', {html: bodyText}));

		messageWrapper.appendChild(messageBodyWrapper);
		detailWrapper.appendChild(messageWrapper);

		var communicationWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-contact-info" },
			text: BX.message('CRM_TIMELINE_SMS_TO')+' '} );
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		communicationWrapper.appendChild(BX.create("SPAN", { text: " " + communicationValue }));

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemSms.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemSms.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemSms.prototype.setMessageStatus = function(status, errorText)
	{
		status = parseInt(status);
		if (isNaN(status) || !this._messageStatusNode)
			return;

		var statuses = this.getSetting('smsStatusDescriptions', {});
		if (statuses.hasOwnProperty(status))
		{
			this._messageStatusNode.textContent = statuses[status];
			this.setMessageStatusErrorText(errorText);

			var statusSemantic = this.getMessageStatusSemantic(status);
			this.setMessageStatusSemantic(statusSemantic);
		}
	};
	BX.CrmHistoryItemSms.prototype.setMessageStatusSemantic = function(semantic)
	{
		var classMap =
		{
			process: 'crm-entity-stream-content-event-process',
			success: 'crm-entity-stream-content-event-successful',
			failure: 'crm-entity-stream-content-event-missing'
		};

		for (var checkSemantic in classMap)
		{
			var fn = (checkSemantic === semantic) ? 'addClass' : 'removeClass';
			BX[fn](this._messageStatusNode, classMap[checkSemantic]);
		}
	};
	BX.CrmHistoryItemSms.prototype.setMessageStatusErrorText = function(errorText)
	{
		if (!errorText)
		{
			this._messageStatusNode.removeAttribute('title');
			BX.removeClass(this._messageStatusNode,'crm-entity-stream-content-event-error-tip');
		}
		else
		{
			this._messageStatusNode.setAttribute('title', errorText);
			BX.addClass(this._messageStatusNode,'crm-entity-stream-content-event-error-tip');
		}
	};
	BX.CrmHistoryItemSms.prototype.getMessageStatusSemantic = function(status)
	{
		var semantics = this.getSetting('smsStatusSemantics', {});
		return semantics.hasOwnProperty(status) ? semantics[status] : 'failure';
	};

	BX.CrmHistoryItemSms.prototype.subscribe = function()
	{
		if (!BX.CrmSmsWatcher)
			return;

		var entityData = this.getAssociatedEntityData();
		var smsInfo = BX.prop.getObject(entityData, "SMS_INFO", {});

		if (smsInfo.id)
		{
			BX.CrmSmsWatcher.subscribeOnMessageUpdate(
				smsInfo.id,
				this.onMessageUpdate.bind(this)
			);
		}
	};
	BX.CrmHistoryItemSms.prototype.onMessageUpdate = function(message)
	{
		if (message.STATUS_ID)
		{
			this.setMessageStatus(message.STATUS_ID, message.EXEC_ERROR);
		}
	};

	BX.CrmHistoryItemSms.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemSms();
		self.initialize(id, settings);
		self.subscribe();
		return self;
	};
}

if(typeof(BX.CrmHistoryItemActivityRequest) === "undefined")
{
	BX.CrmHistoryItemActivityRequest = function()
	{
		BX.CrmHistoryItemActivityRequest.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemActivityRequest, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemActivityRequest.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemActivityRequest.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
			{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
					}

		//var entityData = this.getAssociatedEntityData();
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-robot" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot" } })
		);
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemActivityRequest.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemActivityRequest.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemActivityRequest.prototype.isEditable = function()
	{
		return false;
	};
	BX.CrmHistoryItemActivityRequest.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemActivityRequest();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemActivityRestApplication) === "undefined")
{
	BX.CrmHistoryItemActivityRestApplication = function()
	{
		BX.CrmHistoryItemActivityRestApplication.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemActivityRestApplication, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemActivityRestApplication.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemActivityRestApplication.prototype.getTypeDescription = function()
	{
		var entityData = this.getAssociatedEntityData();
		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME'])
		{
			return entityData['APP_TYPE']['NAME'];
		}

		return BX.CrmHistoryItemActivityRestApplication.superclass.getTypeDescription.apply(this);
	};
	BX.CrmHistoryItemActivityRestApplication.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		//var entityData = this.getAssociatedEntityData();
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-today crm-entity-stream-section-rest" } });

		var iconNode = BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest" } });

		wrapper.appendChild(iconNode);

		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['ICON_SRC'])
		{
			if (iconNode)
			{
				iconNode.style.backgroundImage = "url(" +  entityData['APP_TYPE']['ICON_SRC'] + ")";
				iconNode.style.backgroundPosition = "center center";
			}
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemActivityRestApplication.prototype.prepareActions = function()
	{
	};
	BX.CrmHistoryItemActivityRestApplication.prototype.showActions = function(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	};
	BX.CrmHistoryItemActivityRestApplication.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemActivityRestApplication();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemOpenLine) === "undefined")
{
	BX.CrmHistoryItemOpenLine = function()
	{
		BX.CrmHistoryItemOpenLine.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemOpenLine, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemOpenLine.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
	};
	BX.CrmHistoryItemOpenLine.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-IM" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM" } })
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		var header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		var detailWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail" } });
		contentWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-title" },
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: this.getTitle()
							}
						)
					]
				}
			)
		);

		//Content
		var entityDetailWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-content-detail-IM" } }
		);
		detailWrapper.appendChild(entityDetailWrapper);

		var messageWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-content-detail-IM-messages" } }
		);
		entityDetailWrapper.appendChild(messageWrapper);

		var openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);
		if(openLineData)
		{
			var messages = BX.prop.getArray(openLineData, "MESSAGES", []);
			for(var i = 0, length = messages.length; i < length; i++)
			{
				var message = messages[i];
				var isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);

				messageWrapper.appendChild(
					BX.create("DIV",
						{
							attrs:
							{
								className: isExternal
							        ? "crm-entity-stream-content-detail-IM-message-incoming"
								    : "crm-entity-stream-content-detail-IM-message-outgoing"
							},
							text: BX.prop.getString(message, "MESSAGE", "")
						}
					)
				);
			}
		}


		var communicationWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-contact-info" } } );
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			communicationWrapper.appendChild(
				BX.create("SPAN",
					{ text: this.getMessage("reciprocal") + ": " }
				)
			);

			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	};
	BX.CrmHistoryItemOpenLine.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmHistoryOpenLineAction.create(
				"openline",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._history.getOwnerInfo()
				}
			)
		);
	};
	BX.CrmHistoryItemOpenLine.prototype.view = function()
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("openLineNotSupported"));
			return;
		}

		var slug = "";
		var communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);
		if(communication)
		{
			if(BX.prop.getString(communication, "TYPE") === "IM")
			{
				slug = BX.prop.getString(communication, "VALUE");
			}
		}

		if(slug !== "")
		{
			window.top['BXIM'].openMessengerSlider(slug, {RECENT: 'N', MENU: 'N'});
		}
	};
	BX.CrmHistoryItemOpenLine.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemOpenLine();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemConversion) === "undefined")
{
	BX.CrmHistoryItemConversion = function()
	{
		BX.CrmHistoryItemConversion.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemConversion, BX.CrmHistoryItem);
	BX.CrmHistoryItemConversion.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemConversion.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmHistoryItemConversion.prototype.getTitle = function()
	{
		return this.getTextDataParam("TITLE");
	};
	BX.CrmHistoryItemConversion.prototype.prepareContent = function()
	{
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-convert crm-entity-stream-section-history" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-convert" } })
		);

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = this.prepareHeaderLayout();

		content.appendChild(header);

		var entityNodes = [];
		var entityInfos = this.getArrayDataParam("ENTITIES");
		for(var i = 0, length = entityInfos.length; i < length; i++)
		{
			var entityInfo = entityInfos[i];

			var entityNode;
			if(BX.prop.getString(entityInfo, 'SHOW_URL', "") === "")
			{
				entityNode = BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-convert" },
						children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("SPAN",
													{
														attrs: { className: "crm-entity-stream-content-detail-status-text" },
														text: BX.CrmEntityType.getNotFoundMessage(entityInfo['ENTITY_TYPE_ID'])
													}
												)
											]
									}
								)
							]
					}
				);
			}
			else
			{
				entityNode = BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-convert" },
						children:
							[
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("SPAN",
													{
														attrs: { className: "crm-entity-stream-content-detail-status-text" },
														text: BX.CrmEntityType.getCaption(entityInfo['ENTITY_TYPE_ID'])
													}
												)
											]
									}
								),
								BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-convert-separator-icon" } }),
								BX.create("DIV",
									{
										attrs: { className: "crm-entity-stream-content-detain-convert-status" },
										children:
											[
												BX.create("A",
													{
														attrs:
															{
																className: "crm-entity-stream-content-detail-target",
																href: entityInfo['SHOW_URL']
															},
														text: entityInfo['TITLE']
													}
												)
											]
									}
								)
							]
					}
				);
			}
			entityNodes.push(entityNode);
		}

		content.appendChild(
			BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" },
				children: entityNodes
			})
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	};
	if(typeof(BX.CrmHistoryItemConversion.messages) === "undefined")
	{
		BX.CrmHistoryItemConversion.messages = {};
	}
	BX.CrmHistoryItemConversion.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemConversion();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemVisit) === "undefined")
{
	BX.CrmHistoryItemVisit = function()
	{
		BX.CrmHistoryItemVisit.superclass.constructor.apply(this);
		this._playerDummyClickHandler = BX.delegate(this.onPlayerDummyClick, this);
		this._playerWrapper = null;
		this._transcriptWrapper = null;
		this._mediaFileInfo = null;
	};
	BX.extend(BX.CrmHistoryItemVisit, BX.CrmHistoryItemActivity);
	BX.CrmHistoryItemVisit.prototype.prepareHeaderLayout = function()
	{
		var header = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-header" } });
		header.appendChild(this.prepareTitleLayout());

		var entityData = this.getAssociatedEntityData();
		var visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
		var recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
		var recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_FULL", "");

		header.appendChild(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: (recordLength > 0 ? recordLengthFormatted + ', ' + BX.message('CRM_TIMELINE_VISIT_AT') + ' ' : '') + this.formatTime(this.getCreatedTime())
				}
			)
		);

		return header;
	};
	BX.CrmHistoryItemVisit.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var communicationTitle = BX.prop.getString(communication, "TITLE", "");
		var communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "VALUE", "");
		var communicationValueFormatted = BX.prop.getString(communication, "FORMATTED_VALUE", communicationValue);

		var visitInfo = BX.prop.getObject(entityData, "VISIT_INFO", {});
		var recordLength = BX.prop.getInteger(visitInfo, "RECORD_LENGTH", 0);
		var recordLengthFormatted = BX.prop.getString(visitInfo, "RECORD_LENGTH_FORMATTED_SHORT", "");
		var vkProfile = BX.prop.getString(visitInfo, "VK_PROFILE", "");

		var outerWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-visit" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-visit" }
				}
			)
		);

		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		var header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" }
			}
		);
		wrapper.appendChild(detailWrapper);

		this._mediaFileInfo = BX.prop.getObject(entityData, "MEDIA_FILE_INFO", null);
		if(this._mediaFileInfo !== null && recordLength > 0)
		{
			this._playerWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-audio-cap-wrap-container"}
				}
			);
			this._playerWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-audio-cap-wrap" },
						children:
							[
								BX.create(
									"DIV",
									{ attrs: { className: "crm-audio-cap-time" }, text: recordLengthFormatted }
								)
							],
						events: { click: this._playerDummyClickHandler }
					}
				)
			);
			detailWrapper.appendChild(
				//crm-entity-stream-content-detail-call
				this._playerWrapper
			);
		}

		var communicationWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-contact-info" }
			}
		);
		detailWrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			communicationWrapper.appendChild(document.createTextNode(BX.message("CRM_TIMELINE_VISIT_WITH") + ' '));
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(BX.type.isNotEmptyString(vkProfile))
		{
			communicationWrapper.appendChild(document.createTextNode(" "));
			communicationWrapper.appendChild(
				BX.create(
					"a",
					{
						attrs:
							{
								className: "crm-entity-stream-content-detail-additional",
								target: "_blank",
								href: this.getVkProfileUrl(vkProfile)
							},
						text: BX.message('CRM_TIMELINE_VISIT_VKONTAKTE_PROFILE')
					}
				)
			)

		}

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		return outerWrapper;
	};

	BX.CrmHistoryItemVisit.prototype.onPlayerDummyClick = function(e)
	{
		var stubNode = this._playerWrapper.querySelector(".crm-audio-cap-wrap");
		if(stubNode)
		{
			BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
		}

		this._history.getManager().loadMediaPlayer(
			"history_" + this.getId(),
			this._mediaFileInfo["URL"],
			this._mediaFileInfo["TYPE"],
			this._playerWrapper,
			this._mediaFileInfo["DURATION"]
		);
	};

	BX.CrmHistoryItemVisit.prototype.getVkProfileUrl = function(profile)
	{
		return 'https://vk.com/' + BX.util.htmlspecialchars(profile);
	};
	BX.CrmHistoryItemVisit.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemVisit();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemScoring) === "undefined")
{
	BX.CrmHistoryItemScoring = function()
	{
		BX.CrmHistoryItemScoring.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemScoring, BX.CrmHistoryItem);
	BX.CrmHistoryItemScoring.prototype.prepareContent = function()
	{
		var outerWrapper = BX.create("DIV", {
			attrs: {
				className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-scoring"
			},
			events: {
				click: function()
				{
					var url = "/crm/ml/#entity#/#id#/detail";
					var ownerTypeId = this.getOwnerTypeId();
					var ownerId = this.getOwnerId();

					var ownerType;
					if(ownerTypeId == 1)
					{
						ownerType = "lead";
					}
					else if(ownerTypeId == 2)
					{
						ownerType = "deal";
					}
					else
					{
						return;
					}

					url = url.replace("#entity#", ownerType);
					url = url.replace("#id#", ownerId);

					if(BX.SidePanel)
					{
						BX.SidePanel.Instance.open(url, {width: 840});
					}
					else
					{
						top.location.href = url;
					}
				}.bind(this)
			}
			});

		var scoringInfo = BX.prop.getObject(this._data, "SCORING_INFO", null);
		if(!scoringInfo)
		{
			return outerWrapper;
		}

		var score = BX.prop.getNumber(scoringInfo, "SCORE", 0);
		var scoreDelta = BX.prop.getNumber(scoringInfo, "SCORE_DELTA", 0);
		score = Math.round(score * 100);
		scoreDelta = Math.round(scoreDelta * 100);

		var result = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-scoring-total-result" },
				text: score + "%"
			});

		var iconClass = "crm-entity-stream-content-scoring-total-icon";
		if (score < 50)
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-fail";
		}
		else if (score < 75)
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-middle";
		}
		else
		{
			iconClass += " crm-entity-stream-content-scoring-total-icon-success";
		}

		var icon = BX.create("DIV",
			{
				attrs: { className: iconClass }
			}
			);

		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-content-scoring-total" },
								children: [
									BX.create("DIV",
										{
											attrs: { className: "crm-entity-stream-content-scoring-total-text" },
											text: BX.message("CRM_TIMELINE_SCORING_TITLE_2")
										}
									),
									result,
									icon
								]
							}
						),
						BX.create("DIV",
							{
								attrs: { className: "crm-entity-stream-content-scoring-event" },
								children: [
									(
										scoreDelta !== 0 ?
											BX.create("DIV",
												{
													attrs: { className: "crm-entity-stream-content-scoring-event-offset" },
													text: (scoreDelta > 0 ? "+" : "") + scoreDelta + "%"
												}
											)
											:
											null
									),
									/*BX.create("DIV",
										{
											attrs: { className: "crm-entity-stream-content-scoring-event-detail" },
											text: "<activity subject>"
										}
									)*/
								]
							}
						)
					]

				}
			)
		);

		return outerWrapper;
	};
	BX.CrmHistoryItemScoring.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemScoring();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemOrderCreation) === "undefined")
{
	BX.CrmHistoryItemOrderCreation = function()
	{
		BX.CrmHistoryItemOrderCreation.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemOrderCreation, BX.CrmHistoryItem);
	BX.CrmHistoryItemOrderCreation.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemOrderCreation.superclass.doInitialize.apply(this);
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmHistoryItemOrderCreation. The field 'activityEditor' is not assigned.";
		}
	};
	BX.CrmHistoryItemOrderCreation.prototype.getTitle = function()
	{
		var msg = this.getMessage(BX.CrmEntityType.resolveName(this.getAssociatedEntityTypeId()).toLowerCase());
		if(!BX.type.isNotEmptyString(msg))
		{
			msg = this.getTextDataParam("TITLE");
		}

		return msg;
	};
	BX.CrmHistoryItemOrderCreation.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-createOrderEntity";
	};
	BX.CrmHistoryItemOrderCreation.prototype.getHeaderChildren = function()
	{
		var statusMessage = this.getMessage("unpaid");
		var statusClass = "crm-entity-stream-content-event-not-paid";
		var fields = this.getObjectDataParam('FIELDS');

		if (BX.prop.get(fields, 'DONE') === 'Y')
		{
			statusMessage = this.getMessage("done");
			statusClass = "crm-entity-stream-content-event-done";
		}
		else if (BX.prop.get(fields, 'CANCELED') === 'Y')
		{
			statusMessage = this.getMessage("canceled");
			statusClass = "crm-entity-stream-content-event-canceled";
		}
		else if (BX.prop.get(fields, 'PAID') === 'Y')
		{
			statusMessage = this.getMessage("paid");
			statusClass = "crm-entity-stream-content-event-paid";
		}

		return [
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { click: this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: statusClass },
					text: statusMessage
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		];
	};
	BX.CrmHistoryItemOrderCreation.prototype.prepareContentDetails = function()
	{
		var entityData = this.getAssociatedEntityData();
		var entityTypeId = this.getAssociatedEntityTypeId();
		var entityId = this.getAssociatedEntityId();
		var title = BX.prop.getString(entityData, "TITLE");
		var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		var nodes = [];
		if(title !== "")
		{
			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				nodes.push(BX.create("SPAN", { text: title }));
			}
			else
			{
				nodes.push(BX.create("A", { attrs: { href: showUrl }, text: title }));
			}

			var legend =  BX.prop.getString(entityData, "LEGEND", "");
			if(legend !== "")
			{
				nodes.push(BX.create("SPAN", { html: " " + legend }));
			}
		}
		return nodes;
	};
	BX.CrmHistoryItemOrderCreation.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-store";
	};
	BX.CrmHistoryItemOrderCreation.prototype.prepareContent = function()
	{
		wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history";
		var wrapper = BX.create("DIV", { attrs: { className: wrapperClassName } });
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		var header = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-header" },
				children: this.getHeaderChildren()
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
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItemOrderCreation.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemOrderCreation.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistoryItemOrderCreation.messages) === "undefined")
	{
		BX.CrmHistoryItemOrderCreation.messages = {};
	}
	BX.CrmHistoryItemOrderCreation.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemOrderCreation();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemOrderModification) === "undefined")
{
	BX.CrmHistoryItemOrderModification = function()
	{
		BX.CrmHistoryItemOrderModification.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemOrderModification, BX.CrmHistoryItem);
	BX.CrmHistoryItemOrderModification.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemOrderModification.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	BX.CrmHistoryItemOrderModification.prototype.getTitle = function()
	{
		return this.getTextDataParam("TITLE");
	};
	BX.CrmHistoryItemOrderModification.prototype.getStatusInfo = function()
	{
		var statusInfo = {};
		var value =
			classCode = null;
		var fieldName = this.getTextDataParam("CHANGED_ENTITY");
		var fields = this.getObjectDataParam('FIELDS');
		if (fieldName === BX.CrmEntityType.names.order)
		{
			if (BX.prop.get(fields, 'ORDER_CANCELED') === 'Y')
			{
				value = "canceled";
				classCode  = "not-paid";
			}
			else if (BX.prop.get(fields, 'ORDER_DONE') === 'Y')
			{
				value = "done";
				classCode  = "done";
			}
		}
		if (fieldName === BX.CrmEntityType.names.orderpayment)
		{
			value = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "unpaid";
			classCode  = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "not-paid";
		}
		else if (fieldName === BX.CrmEntityType.names.ordershipment)
		{
			value = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "deducted" : "unshipped";
			classCode  = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "shipped" : "not-shipped";
		}

		if (value)
		{
			statusInfo.className = "crm-entity-stream-content-event-" + classCode;
			statusInfo.message = this.getMessage(value);
		}

		return statusInfo;
	};
	BX.CrmHistoryItemOrderModification.prototype.getHeaderChildren = function()
	{
		var children = [
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { click: this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		];
		var statusInfo = this.getStatusInfo();
		if (BX.type.isNotEmptyObject(statusInfo))
		{
			children.push(
				BX.create("SPAN",
				{
					attrs: { className: statusInfo.className },
					text: statusInfo.message
				}
			));
		}
		children.push(
			BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		));
		return children;
	};
	BX.CrmHistoryItemOrderModification.prototype.prepareContentDetails = function()
	{
		var entityData = this.getAssociatedEntityData();
		var entityTypeId = this.getAssociatedEntityTypeId();
		var entityId = this.getAssociatedEntityId();
		var title = BX.prop.getString(entityData, "TITLE");
		var showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		var nodes = [];
		if(title !== "")
		{
			var descriptionNode = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-description"}});
			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				descriptionNode.appendChild(BX.create("SPAN", { text: title }));
			}
			else
			{
				descriptionNode.appendChild(BX.create("A", { attrs: { href: showUrl }, text: title }));
			}
			var legend = BX.prop.getString(entityData, "LEGEND");
			if(legend !== "")
			{
				descriptionNode.appendChild(BX.create("SPAN", { html: " " + legend }));
			}
			nodes.push(descriptionNode);
		}

		nodes = nodes.concat(this.prepareAdditionalContent());
		return nodes;
	};
	BX.CrmHistoryItemOrderModification.prototype.prepareAdditionalContent = function()
	{
		var entityData = this.getAssociatedEntityData();
		var fields =
			subnodes = [];
		var sublegend = '';
		var fieldName = this.getTextDataParam("CHANGED_ENTITY");

		if (fieldName === BX.CrmEntityType.names.orderpayment || fieldName === BX.CrmEntityType.names.order)
		{
			var paymentsInfo = BX.prop.get(entityData, "PAYMENTS_INFO", {});
			fields = this.getObjectDataParam('FIELDS');
			var payments = BX.prop.getObject(fields, 'PAYMENTS_FIELDS');
			for (var id in payments)
			{
				if (!payments.hasOwnProperty(id))
				{
					continue;
				}

				if (BX.type.isNotEmptyObject(paymentsInfo[id]))
				{
					payment = paymentsInfo[id];
					messageCode = (BX.prop.getString(payments[id], "PAID") === "Y") ? "orderPaymentLegendPaid" : "orderPaymentLegendUnpaid";
					sublegend = " " + this.getMessage(messageCode);
					sublegend = sublegend
						.replace("#SUM_WITH_CURRENCY#", BX.prop.getString(payment, 'SUM_WITH_CURRENCY'))
						.replace("#PAY_SYSTEM_NAME#", BX.util.htmlspecialchars(BX.prop.getString(payment, 'PAY_SYSTEM_NAME')));
					paymentNode = BX.create("DIV", {
						attrs: { className: 'crm-entity-stream-content-detail-payment-info' },
						children: [
							BX.create("A", {
								attrs: { href: BX.prop.getString(payment, "SHOW_URL") },
								text: this.getMessage("orderPayment")
							}),
							BX.create("SPAN", {
								html: sublegend
							})
						]
					});

					subnodes.push(paymentNode);
				}
			}
		}
		else if (fieldName === BX.CrmEntityType.names.ordershipment)
		{
			var shipmentsInfo = BX.prop.get(entityData, "SHIPMENTS_INFO", {});
			fields = this.getObjectDataParam('FIELDS');
			var shipments = BX.prop.getObject(fields, 'SHIPMENTS_FIELDS');
			for (var id in shipments)
			{
				if (!shipments.hasOwnProperty(id))
				{
					continue;
				}

				if (BX.type.isNotEmptyObject(shipmentsInfo[id]))
				{
					var shipment = shipmentsInfo[id];
					messageCode = (BX.prop.getString(shipments[id], "DEDUCTED") === "Y") ? "orderShipmentLegendDeducted" : "orderShipmentLegendUnshipped";
					deliveryName = BX.util.htmlspecialchars( BX.prop.getString(shipment, 'DELIVERY_NAME'));
					sublegend = " " + this.getMessage(messageCode).replace("#DELIVERY_NAME#", deliveryName);
					shipmentNode = BX.create("DIV", {
						attrs: { className: 'crm-entity-stream-content-detail-payment-info' },
						children: [
							BX.create("A", {
								attrs: { href: BX.prop.getString(shipment, "SHOW_URL") },
								text: this.getMessage("orderShipment")
							}),
							BX.create("SPAN", {
								html: sublegend
							})
						]
					});
					subnodes.push(shipmentNode);
				}
			}
		}
		return subnodes;
	};
	BX.CrmHistoryItemOrderModification.prototype.prepareContent = function()
	{
		var wrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-section crm-entity-stream-section-history" } });

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-store" } })
		);

		var content = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		var header = BX.create("DIV",
		{
			attrs: { className: "crm-entity-stream-content-header" },
			children: this.getHeaderChildren()
		});

		var contentChildren = this.prepareContentDetails();
		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: contentChildren
				})
		);

		//region Author
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	};
	if(typeof(BX.CrmHistoryItemOrderModification.messages) === "undefined")
	{
		BX.CrmHistoryItemOrderModification.messages = {};
	}
	BX.CrmHistoryItemOrderModification.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemOrderModification();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmHistoryItemOrcderCheck) === "undefined")
{
	BX.CrmHistoryItemOrcderCheck = function()
	{
		BX.CrmHistoryItemOrcderCheck.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmHistoryItemOrcderCheck, BX.CrmHistoryItem);
	BX.CrmHistoryItemOrcderCheck.prototype.doInitialize = function()
	{
		BX.CrmHistoryItemOrcderCheck.superclass.doInitialize.apply(this);
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmHistoryItemOrcderCheck. The field 'activityEditor' is not assigned.";
		}
	};
	BX.CrmHistoryItemOrcderCheck.prototype.getTitle = function()
	{
		return this.getMessage('orderCheck');
	};
	BX.CrmHistoryItemOrcderCheck.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-createOrderEntity";
	};
	BX.CrmHistoryItemOrcderCheck.prototype.getHeaderChildren = function()
	{
		var statusMessage = this.getMessage("printed");
		var statusClass = "crm-entity-stream-content-event-successful";
		if (this.getTextDataParam("PRINTED") !== 'Y')
		{
			statusMessage = this.getMessage("unprinted");
			statusClass = "crm-entity-stream-content-event-missing";
		}

		return [
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { click: this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: statusClass },
					text: statusMessage
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		];
	};
	BX.CrmHistoryItemOrcderCheck.prototype.prepareContentDetails = function()
	{
		var entityData = this.getAssociatedEntityData();
		var title = this.getTextDataParam("TITLE");
		var showUrl = BX.prop.getString(entityData, "SHOW_URL", '');
		var nodes = [];
		if(title !== "")
		{
			var descriptionNode = BX.create("DIV", { attrs: { className: 'crm-entity-stream-content-detail-description' } });
			descriptionNode.appendChild(BX.create("A", {
				attrs: { href: showUrl},
				events: {
					click: BX.delegate(function(e) {
						BX.Crm.Page.openSlider(showUrl, { width: 500 });
						e.preventDefault ? e.preventDefault() : (e.returnValue = false);
					}, this)
				},
				text: title
			}));

			var legend =  this.getTextDataParam("LEGEND");
			if(legend !== "")
			{
				descriptionNode.appendChild(BX.create("SPAN", { text: " " + legend }));
			}

			nodes.push(descriptionNode);
		}

		var listUrl = BX.prop.getString(entityData, "LIST_URL", "");
		if(listUrl)
		{
			nodes.push(
				BX.create("DIV", {
					attrs: { className: 'crm-entity-stream-content-detail-payment-info' },
					children: [
						BX.create("A", { attrs: { href: listUrl }, text: this.getMessage('listLink') })
					]
				})
			);
		}

		return nodes;
	};
	BX.CrmHistoryItemOrcderCheck.prototype.prepareContent = function()
	{
		var entityData = this.getAssociatedEntityData();
		wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-createOrderEntity";
		var wrapper = BX.create("DIV", { attrs: { className: wrapperClassName } });
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		var contentWrapper = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		var header = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-header" },
				children: this.getHeaderChildren()
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
		var authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	};
	BX.CrmHistoryItemOrcderCheck.prototype.getMessage = function(name)
	{
		var m = BX.CrmHistoryItemOrcderCheck.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmHistoryItemOrcderCheck.messages) === "undefined")
	{
		BX.CrmHistoryItemOrcderCheck.messages = {};
	}
	BX.CrmHistoryItemOrcderCheck.create = function(id, settings)
	{
		var self = new BX.CrmHistoryItemOrcderCheck();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region Schedule Items
if(typeof(BX.CrmScheduleItem) === "undefined")
{
	BX.CrmScheduleItem = function()
	{
		BX.CrmScheduleItem.superclass.constructor.apply(this);
		this._schedule = null;
		this._deadlineNode = null;

		this._headerClickHandler = BX.delegate(this.onHeaderClick, this);
		this._setAsDoneButtonHandler = BX.delegate(this.onSetAsDoneButtonClick, this);
	};
	BX.extend(BX.CrmScheduleItem, BX.CrmTimelineItem);
	BX.CrmScheduleItem.prototype.doInitialize = function()
	{
		this._schedule = this.getSetting("schedule");
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "BX.CrmScheduleItem. The field 'activityEditor' is not assigned.";
		}

		if(this.hasPermissions() && !this.verifyPermissions())
		{
			this.loadPermissions();
		}
	};
	BX.CrmScheduleItem.prototype.getTypeId = function()
	{
		return BX.CrmTimelineType.undefined;
	};
	BX.CrmScheduleItem.prototype.verifyPermissions = function()
	{
		var userId = BX.prop.getInteger(this.getPermissions(), "USER_ID", 0);
		return userId <= 0 || userId === this._schedule.getUserId();
	};
	BX.CrmScheduleItem.prototype.loadPermissions = function()
	{
		BX.ajax(
			{
				url: this._schedule.getServiceUrl(),
				method: "POST",
				dataType: "json",
				data: { "ACTION": "GET_PERMISSIONS", "TYPE_ID": this.getTypeId(), "ID": this.getAssociatedEntityId() },
				onsuccess: this.onPermissionsLoad.bind(this)
			}
		);
	};
	BX.CrmScheduleItem.prototype.onPermissionsLoad = function(result)
	{
		var permissions = BX.prop.getObject(result, "PERMISSIONS", null);
		if(!permissions)
		{
			return;
		}

		this.setPermissions(permissions);
		window.setTimeout(function(){ this.refreshLayout(); }.bind(this), 0);
	};
	BX.CrmScheduleItem.prototype.getDeadline = function()
	{
		return null;
	};
	BX.CrmScheduleItem.prototype.hasDeadline = function()
	{
		return BX.type.isDate(this.getDeadline());
	};
	BX.CrmScheduleItem.prototype.isCounterEnabled = function()
	{
		var deadline = this.getDeadline();
		return deadline && BX.CrmHistoryItem.isCounterEnabled(deadline);
	};
	BX.CrmScheduleItem.prototype.getSourceId = function()
	{
		return BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
	};
	BX.CrmScheduleItem.prototype.onSetAsDoneCompleted = function(data)
	{
		if(!BX.prop.getBoolean(data, "COMPLETED"))
		{
			return;
		}

		this.markAsDone(true);
		this._schedule.onItemMarkedAsDone(
			this,
			{ 'historyItemData': BX.prop.getObject(data, "HISTORY_ITEM") }
		);
	};
	BX.CrmScheduleItem.prototype.onPosponeCompleted = function(data)
	{
	};
	BX.CrmScheduleItem.prototype.refreshDeadline = function()
	{
		this._deadlineNode.innerHTML = this.formatDateTime(this.getDeadline());
	};
	BX.CrmScheduleItem.prototype.formatDateTime = function(time)
	{
		return this._schedule.formatDateTime(time);
	};
	BX.CrmScheduleItem.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmScheduleItem.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon";
	};

	BX.CrmScheduleItem.prototype.isReadOnly = function()
	{
		return this._schedule.isReadOnly();
	};
	BX.CrmScheduleItem.prototype.isEditable = function()
	{
		return !this.isReadOnly();
	};
	BX.CrmScheduleItem.prototype.canPostpone = function()
	{
		if(this.isReadOnly())
		{
			return false;
		}

		var perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
		return BX.prop.getBoolean(perms, "POSTPONE", false);
	};
	BX.CrmScheduleItem.prototype.isDone = function()
	{
		return BX.CrmActivityStatus.isFinal(
			BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS", 0)
		);
	};
	BX.CrmScheduleItem.prototype.canComplete = function()
	{
		if(this.isReadOnly())
		{
			return false;
		}

		var perms = BX.prop.getObject(this.getAssociatedEntityData(), "PERMISSIONS", {});
		return BX.prop.getBoolean(perms, "COMPLETE", false);
	};
	BX.CrmScheduleItem.prototype.setAsDone = function(isDone)
	{
	};
	BX.CrmScheduleItem.prototype.prepareContent = function(options)
	{
		return null;
	};
	BX.CrmScheduleItem.prototype.prepareLayout = function(options)
	{
		this._wrapper = this.prepareContent(options);
		if(this._wrapper)
		{
			var enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
			if(enableAdd)
			{
				var anchor = BX.type.isPlainObject(options) && BX.type.isElementNode(options["anchor"]) ? options["anchor"] : null;
				if(anchor && anchor.nextSibling)
				{
					this._container.insertBefore(this._wrapper, anchor.nextSibling);
				}
				else
				{
					this._container.appendChild(this._wrapper);
				}
			}

			this.markAsTerminated(this._schedule.checkItemForTermination(this));
		}
	};
	BX.CrmScheduleItem.prototype.onHeaderClick = function(e)
	{
		this.view();
		e.preventDefault ? e.preventDefault() : (e.returnValue = false);
	};
	BX.CrmScheduleItem.prototype.onSetAsDoneButtonClick = function(e)
	{
		if(this.canComplete())
		{
			this.setAsDone(!this.isDone());
		}
	};
	BX.CrmScheduleItem.prototype.onActivityCreate = function(activity, data)
	{
		this._schedule.getManager().onActivityCreated(activity, data);
	};

	BX.CrmScheduleItem.isDone = function(data)
	{
		var entityData = BX.prop.getObject(data, "ASSOCIATED_ENTITY", {});
		return BX.CrmActivityStatus.isFinal(BX.prop.getInteger(entityData, "STATUS", 0));
	};
	BX.CrmScheduleItem.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItem();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemActivity) === "undefined")
{
	BX.CrmScheduleItemActivity = function()
	{
		BX.CrmScheduleItemActivity.superclass.constructor.apply(this);
		this._postponeController = null;
	};
	BX.extend(BX.CrmScheduleItemActivity, BX.CrmScheduleItem);
	BX.CrmScheduleItemActivity.prototype.getTypeId = function()
	{
		return BX.CrmTimelineType.activity;
	};
	BX.CrmScheduleItemActivity.prototype.isDone = function()
	{
		var status = BX.prop.getInteger(this.getAssociatedEntityData(), "STATUS");
		return (status ===  BX.CrmActivityStatus.completed || status ===  BX.CrmActivityStatus.autoCompleted);
	};
	BX.CrmScheduleItemActivity.prototype.setAsDone = function(isDone)
	{
		isDone = !!isDone;
		if(this.isDone() === isDone)
		{
			return;
		}

		var id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
		if(id > 0)
		{
			this._activityEditor.setActivityCompleted(
				id,
				isDone,
				BX.delegate(this.onSetAsDoneCompleted, this)
			);
		}
	};
	BX.CrmScheduleItemActivity.prototype.postpone = function(offset)
	{
		var id = this.getSourceId();
		if(id > 0 && offset > 0)
		{
			this._activityEditor.postponeActivity(
				id,
				offset,
				BX.delegate(this.onPosponeCompleted, this)
			);
		}
	};
	BX.CrmScheduleItemActivity.prototype.view = function()
	{
		var id = BX.prop.getInteger(this.getAssociatedEntityData(), "ID", 0);
		if(id > 0)
		{
			this._activityEditor.viewActivity(id);
		}
	};
	BX.CrmScheduleItemActivity.prototype.edit = function()
	{
		this.closeContextMenu();
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityData = this.getAssociatedEntityData();
			var id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.editActivity(id);
			}
		}
	};
	BX.CrmScheduleItemActivity.prototype.processRemoval = function()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		var dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getRemoveMessage()
				}
			);
		}

		dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	};
	BX.CrmScheduleItemActivity.prototype.getRemoveMessage = function()
	{
		return this.getMessage('removeConfirm');
	};
	BX.CrmScheduleItemActivity.prototype.onRemovalConfirm = function(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.remove();
	};
	BX.CrmScheduleItemActivity.prototype.onRemovalCancel = function()
	{
	};
	BX.CrmScheduleItemActivity.prototype.remove = function()
	{
		var associatedEntityTypeId = this.getAssociatedEntityTypeId();

		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			var entityData = this.getAssociatedEntityData();

			var id = BX.prop.getInteger(entityData, "ID", 0);

			if(id > 0)
			{
				var activityEditor = this._activityEditor;
				var item = activityEditor.getItemById(id);
				if (item)
				{
					activityEditor.deleteActivity(id, true);
				}
				else
				{
					var activityType = activityEditor.getSetting('ownerType', '');
					var activityId = activityEditor.getSetting('ownerID', '');

					var serviceUrl = BX.util.add_url_param(activityEditor.getSetting('serviceUrl', ''),
						{
							id: id,
							action: 'get_activity',
							ownertype: activityType,
							ownerid: activityId
						}
					);
					BX.ajax({
						'url': serviceUrl,
						'method': 'POST',
						'dataType': 'json',
						'data':
							{
								'ACTION' : 'GET_ACTIVITY',
								'ID': id,
								'OWNER_TYPE': activityType,
								'OWNER_ID': activityId
							},
						onsuccess: BX.delegate(
							function(data)
							{
								if(typeof(data['ACTIVITY']) !== 'undefined')
								{
									activityEditor._handleActivityChange(data['ACTIVITY']);
									window.setTimeout(BX.delegate(this.remove ,this), 500);
								}
							},
							this
						),
						onfailure: function(data){}
					});
				}
			}
		}
	};
	BX.CrmScheduleItemActivity.prototype.getDeadline = function()
	{
		var entityData = this.getAssociatedEntityData();
		var time = BX.parseDate(
			entityData["DEADLINE_SERVER"],
			false,
			"YYYY-MM-DD",
			"YYYY-MM-DD HH:MI:SS"
		);

		if(!time)
		{
			return null;
		}

		return new Date(time.getTime() + 1000 * BX.CrmTimelineItem.getUserTimezoneOffset());
	};
	BX.CrmScheduleItemActivity.prototype.markAsDone = function(isDone)
	{
		isDone = !!isDone;
		this.getAssociatedEntityData()["STATUS"] = isDone ? BX.CrmActivityStatus.completed : BX.CrmActivityStatus.waiting;
	};
	BX.CrmScheduleItemActivity.prototype.getPrepositionText = function(direction)
	{
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "from" : "to");
	};
	BX.CrmScheduleItemActivity.prototype.getTypeDescription = function(direction)
	{
		return "";
	};
	BX.CrmScheduleItemActivity.prototype.isContextMenuEnabled = function()
	{
		return ((!!this.getDeadline() && this.canPostpone()) || this.canComplete());
	};
	BX.CrmScheduleItemActivity.prototype.prepareContent = function(options)
	{
		var deadline = this.getDeadline();
		var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		var entityData = this.getAssociatedEntityData();
		var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		var isDone = this.isDone();
		var subject = BX.prop.getString(entityData, "SUBJECT", "");
		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

		var communication =  BX.prop.getObject(entityData, "COMMUNICATION", {});
		var title = BX.prop.getString(communication, "TITLE", "");
		var showUrl = BX.prop.getString(communication, "SHOW_URL", "");
		var communicationValue = BX.prop.getString(communication, "TYPE", "") !== ""
			? BX.prop.getString(communication, "VALUE", "") : "";

		var wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
		}

		var wrapper = BX.create("DIV", { attrs: { className: wrapperClassName } });

		var iconClassName = this.getIconClassName();
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

		var contentWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-section-content" } }
		);
		wrapper.appendChild(contentWrapper);

		//region Details
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		var contentInnerWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-event" }
			}
		);
		contentWrapper.appendChild(contentInnerWrapper);

		this._deadlineNode = BX.create("SPAN",
			{ attrs: { className: "crm-entity-stream-content-event-time" }, text: timeText }
		);

		var headerWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-header" },
				children:
					[
						BX.create("SPAN",
							{
								attrs:
								{
									className: "crm-entity-stream-content-event-title"
								},
								text: this.getTypeDescription(direction)
							}
						),
						this._deadlineNode
					]
			}
		);
		contentInnerWrapper.appendChild(headerWrapper);

		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" }
			}
		);
		contentInnerWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-title"},
					children:
					[
						BX.create("A",
							{
								attrs: { href: "#" },
								events: { "click": this._headerClickHandler },
								text: subject
							}
						)
					]
				}
			)
		);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-description" },
					text: this.cutOffText(description, 128)
				}
			)
		);

		var additionalDetails = this.prepareDetailNodes();
		if(BX.type.isArray(additionalDetails))
		{
			for(var i = 0, length = additionalDetails.length; i < length; i++)
			{
				detailWrapper.appendChild(additionalDetails[i]);
			}
		}

		var members = BX.create("DIV",
			{  attrs: { className: "crm-entity-stream-content-detail-contact-info" } }
		);

		if(title !== '')
		{
			members.appendChild(
				BX.create("SPAN",
					{ text: this.getPrepositionText(direction) + ": " }
				)
			);

			if(showUrl !== '')
			{
				members.appendChild(
					BX.create("A",
						{
							attrs: { href: showUrl },
							text: title
						}
					)
				);
			}
			else
			{
				members.appendChild(BX.create("SPAN", { text: title }));
			}
		}

		if(communicationValue !== '')
		{
			var communicationNode = this.prepareCommunicationNode(communicationValue);
			if(communicationNode)
			{
				members.appendChild(communicationNode);
			}
		}

		detailWrapper.appendChild(members);
		//endregion
		//region Set as Done Button
		var setAsDoneButton = BX.create("INPUT",
			{
				attrs:
					{
						type: "checkbox",
						className: "crm-entity-stream-planned-apply-btn",
						checked: isDone
					},
				events: { change: this._setAsDoneButtonHandler }
			}
		);

		if(!this.canComplete())
		{
			setAsDoneButton.disabled = true;
		}

		var buttonContainer = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-planned-action" },
				children: [ setAsDoneButton ]
			}
		);
		contentInnerWrapper.appendChild(buttonContainer);
		//endregion

		//region Author
		var authorNode = this.prepareAuthorLayout();
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
	};
	BX.CrmScheduleItemActivity.prototype.prepareCommunicationNode = function(communicationValue)
	{
		return BX.create("SPAN", { text: " " + communicationValue });
	};
	BX.CrmScheduleItemActivity.prototype.prepareDetailNodes = function()
	{
		return [];
	};
	BX.CrmScheduleItemActivity.prototype.prepareContextMenuItems = function()
	{
		var menuItems = [];

		if (!this.isReadOnly())
		{
			if (this.isEditable())
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.edit, this)});
			}

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});
		}

		var handler = BX.delegate(this.onContextMenuItemSelect, this);

		if(!this._postponeController)
		{
			this._postponeController = BX.CrmSchedulePostponeController.create("", { item: this });
		}

		var postponeMenu =
			{
				id: "postpone",
				text: this._postponeController.getTitle(),
				items: []
			};

		var commands = this._postponeController.getCommandList();
		for(var i = 0, length = commands.length; i < length; i++)
		{
			var command = commands[i];
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
	};
	BX.CrmScheduleItemActivity.prototype.onContextMenuItemSelect = function(e, item)
	{
		this.closeContextMenu();
		if(this._postponeController)
		{
			this._postponeController.processCommand(item.id);
		}
	};
}

if(typeof(BX.CrmScheduleItemEmail) === "undefined")
{
	BX.CrmScheduleItemEmail = function()
	{
		BX.CrmScheduleItemEmail.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemEmail, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemEmail.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-email";
	};
	BX.CrmScheduleItemEmail.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-email";
	};
	BX.CrmScheduleItemEmail.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmScheduleEmailAction.create(
				"email",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor
				}
			)
		);
	};
	BX.CrmScheduleItemEmail.prototype.getTypeDescription = function(direction)
	{
		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail");
	};
	BX.CrmScheduleItemEmail.prototype.getRemoveMessage = function()
	{
		var entityData = this.getAssociatedEntityData();
		var title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('emailRemove').replace("#TITLE#", title);
	};
	BX.CrmScheduleItemEmail.prototype.isEditable = function()
	{
		return false;
	};
	BX.CrmScheduleItemEmail.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemEmail();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemCall) === "undefined")
{
	BX.CrmScheduleItemCall = function()
	{
		BX.CrmScheduleItemCall.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemCall, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemCall.prototype.getWrapperClassName = function()
	{
		return 'crm-entity-stream-section-call';
	};
	BX.CrmScheduleItemCall.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-call";
	};
	BX.CrmScheduleItemCall.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmScheduleCallAction.create(
				"call",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._schedule.getOwnerInfo()
				}
			)
		);
	};
	BX.CrmScheduleItemCall.prototype.getTypeDescription = function(direction)
	{
		var entityData = this.getAssociatedEntityData();
		var callInfo =  BX.prop.getObject(entityData, "CALL_INFO", null);
		var callTypeText = callInfo !== null ? BX.prop.getString(callInfo, "CALL_TYPE_TEXT", "") : "";
		if(callTypeText !== "")
		{
			return callTypeText;
		}

		return this.getMessage(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall");
	};
	BX.CrmScheduleItemCall.prototype.getRemoveMessage = function()
	{
		var entityData = this.getAssociatedEntityData();
		var direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		var title = BX.prop.getString(entityData, "SUBJECT", "");
		var messageName = (direction === BX.CrmActivityDirection.incoming) ? 'incomingCallRemove' : 'outgoingCallRemove';
		title = BX.util.htmlspecialchars(title);
		return this.getMessage(messageName).replace("#TITLE#", title);
	};
	BX.CrmScheduleItemCall.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemCall();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemMeeting) === "undefined")
{
	BX.CrmScheduleItemMeeting = function()
	{
		BX.CrmScheduleItemMeeting.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemMeeting, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemMeeting.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmScheduleItemMeeting.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-meeting";
	};
	BX.CrmScheduleItemMeeting.prototype.prepareActions = function()
	{
	};
	BX.CrmScheduleItemMeeting.prototype.getPrepositionText = function()
	{
		return this.getMessage("reciprocal");
	};
	BX.CrmScheduleItemMeeting.prototype.getRemoveMessage = function()
	{
		var entityData = this.getAssociatedEntityData();
		var title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('meetingRemove').replace("#TITLE#", title);
	};
	BX.CrmScheduleItemMeeting.prototype.getTypeDescription = function()
	{
		return this.getMessage("meeting");
	};
	BX.CrmScheduleItemMeeting.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemMeeting();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemTask) === "undefined")
{
	BX.CrmScheduleItemTask = function()
	{
		BX.CrmScheduleItemTask.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemTask, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemTask.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-planned-task";
	};
	BX.CrmScheduleItemTask.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-task";
	};
	BX.CrmScheduleItemTask.prototype.getTypeDescription = function()
	{
		return this.getMessage("task");
	};
	BX.CrmScheduleItemTask.prototype.getPrepositionText = function(direction)
	{
		return this.getMessage("reciprocal");
	};
	BX.CrmScheduleItemTask.prototype.getRemoveMessage = function()
	{
		var entityData = this.getAssociatedEntityData();
		var title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('taskRemove').replace("#TITLE#", title);
	};
	BX.CrmScheduleItemTask.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemTask();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemWebForm) === "undefined")
{
	BX.CrmScheduleItemWebForm = function()
	{
		BX.CrmScheduleItemWebForm.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemWebForm, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemWebForm.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmScheduleItemWebForm.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-crmForm";
	};
	BX.CrmScheduleItemWebForm.prototype.prepareActions = function()
	{
	};
	BX.CrmScheduleItemWebForm.prototype.getPrepositionText = function()
	{
		return this.getMessage("from");
	};
	BX.CrmScheduleItemWebForm.prototype.getTypeDescription = function()
	{
		return this.getMessage("webform");
	};
	BX.CrmScheduleItemWebForm.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemWebForm();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemWait) === "undefined")
{
	BX.CrmScheduleItemWait = function()
	{
		BX.CrmScheduleItemWait.superclass.constructor.apply(this);
		this._postponeController = null;
	};
	BX.extend(BX.CrmScheduleItemWait, BX.CrmScheduleItem);
	/*BX.CrmScheduleItemWait.prototype.doInitialize = function()
	{
		BX.CrmScheduleItemWait.superclass.doInitialize.apply(this);
	};*/
	BX.CrmScheduleItemWait.prototype.getTypeId = function()
	{
		return BX.CrmTimelineType.wait;
	};
	BX.CrmScheduleItemWait.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-wait";
	};
	BX.CrmScheduleItemWait.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-wait";
	};
	BX.CrmScheduleItemWait.prototype.prepareActions = function()
	{
	};
	BX.CrmScheduleItemWait.prototype.isCounterEnabled = function()
	{
		return false;
	};
	BX.CrmScheduleItemWait.prototype.getDeadline = function()
	{
		var entityData = this.getAssociatedEntityData();
		var time = BX.parseDate(
			entityData["DEADLINE_SERVER"],
			false,
			"YYYY-MM-DD",
			"YYYY-MM-DD HH:MI:SS"
		);

		if(!time)
		{
			return null;
		}

		return new Date(time.getTime() + 1000 * BX.CrmTimelineItem.getUserTimezoneOffset());
	};
	BX.CrmScheduleItemWait.prototype.isDone = function()
	{
		return (BX.prop.getString(this.getAssociatedEntityData(), "COMPLETED", "N") ===  "Y");
	};
	BX.CrmScheduleItemWait.prototype.setAsDone = function(isDone)
	{
		isDone = !!isDone;
		if(this.isDone() === isDone)
		{
			return;
		}

		var id = this.getAssociatedEntityId();
		if(id > 0)
		{
			var editor = this._schedule.getManager().getWaitEditor();
			if(editor)
			{
				editor.complete(
					id,
					isDone,
					BX.delegate(this.onSetAsDoneCompleted, this)
				);
			}
		}
	};
	BX.CrmScheduleItemWait.prototype.postpone = function(offset)
	{
		var id = this.getAssociatedEntityId();
		if(id > 0 && offset > 0)
		{
			var editor = this._schedule.getManager().getWaitEditor();
			if(editor)
			{
				editor.postpone(
					id,
					offset,
					BX.delegate(this.onPosponeCompleted, this)
				);
			}
		}
	};
	BX.CrmScheduleItemWait.prototype.isContextMenuEnabled = function()
	{
		return !!this.getDeadline() && this.canPostpone();
	};
	BX.CrmScheduleItemWait.prototype.prepareContent = function()
	{
		var deadline = this.getDeadline();
		var timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		var entityData = this.getAssociatedEntityData();
		var isDone = this.isDone();
		var description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

		var wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
		}

		var wrapper = BX.create("DIV", { attrs: { className: wrapperClassName } });

		var iconClassName = this.getIconClassName();
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

		var contentWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-section-content" } }
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

		var contentInnerWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-event" }
			}
		);
		contentWrapper.appendChild(contentInnerWrapper);

		this._deadlineNode = BX.create("SPAN",
			{ attrs: { className: "crm-entity-stream-content-event-time" }, text: timeText }
		);

		var headerWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-header" },
				children:
					[
						BX.create("SPAN",
							{
								attrs: { className: "crm-entity-stream-content-event-title" },
								text: this.getMessage("wait")
							}
						),
						this._deadlineNode
					]
			}
		);
		contentInnerWrapper.appendChild(headerWrapper);

		var detailWrapper = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail" }
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

		var members = BX.create("DIV",
			{  attrs: { className: "crm-entity-stream-content-detail-contact-info" } }
		);

		detailWrapper.appendChild(members);
		//endregion

		//region Set as Done Button
		var setAsDoneButton = BX.create("INPUT",
			{
				attrs:
					{
						type: "checkbox",
						className: "crm-entity-stream-planned-apply-btn",
						checked: isDone
					},
				events: { change: this._setAsDoneButtonHandler }
			}
		);

		if(!this.canComplete())
		{
			setAsDoneButton.disabled = true;
		}

		var buttonContainer = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-planned-action" },
				children: [ setAsDoneButton ]
			}
		);
		contentInnerWrapper.appendChild(buttonContainer);
		//endregion

		//region Author
		var authorNode = this.prepareAuthorLayout();
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
	};
	BX.CrmScheduleItemWait.prototype.prepareContextMenuItems = function()
	{
		var menuItems = [];
		var handler = BX.delegate(this.onContextMenuItemSelect, this);

		if(!this._postponeController)
		{
			this._postponeController = BX.CrmSchedulePostponeController.create("", { item: this });
		}

		var postponeMenu =
			{
				id: "postpone",
				text: this._postponeController.getTitle(),
				items: []
			};

		var commands = this._postponeController.getCommandList();
		for(var i = 0, length = commands.length; i < length; i++)
		{
			var command = commands[i];
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
	};
	BX.CrmScheduleItemWait.prototype.onContextMenuItemSelect = function(e, item)
	{
		this.closeContextMenu();
		if(this._postponeController)
		{
			this._postponeController.processCommand(item.id);
		}
	};
	BX.CrmScheduleItemWait.prototype.getTypeDescription = function()
	{
		return this.getMessage("wait");
	};
	BX.CrmScheduleItemWait.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemWait();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemActivityRequest) === "undefined")
{
	BX.CrmScheduleItemActivityRequest = function()
	{
		BX.CrmScheduleItemActivityRequest.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemActivityRequest, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemActivityRequest.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmScheduleItemActivityRequest.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-robot";
	};
	BX.CrmScheduleItemActivityRequest.prototype.getTypeDescription = function()
	{
		return this.getMessage("activityRequest");
	};
	BX.CrmScheduleItemActivityRequest.prototype.isEditable = function()
	{
		return false;
	};
	BX.CrmScheduleItemActivityRequest.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemActivityRequest();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemActivityRestApplication) === "undefined")
{
	BX.CrmScheduleItemActivityRestApplication = function()
	{
		BX.CrmScheduleItemActivityRestApplication.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemActivityRestApplication, BX.CrmScheduleItemActivity);
	BX.CrmScheduleItemActivityRestApplication.prototype.getWrapperClassName = function()
	{
		return "";
	};
	BX.CrmScheduleItemActivityRestApplication.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-rest";
	};
	BX.CrmScheduleItemActivityRestApplication.prototype.prepareContent = function(options)
	{
		var wrapper = BX.CrmScheduleItemActivityRestApplication.superclass.prepareContent.apply(this, options);
		var data = this.getAssociatedEntityData();

		if (data['APP_TYPE'] && data['APP_TYPE']['ICON_SRC'])
		{
			var iconNode = wrapper.querySelector('[class="'+this.getIconClassName()+'"]');
			if (iconNode)
			{
				iconNode.style.backgroundImage = "url(" +  data['APP_TYPE']['ICON_SRC'] + ")";
				iconNode.style.backgroundPosition = "center center";
			}
		}

		return wrapper;
	};
	BX.CrmScheduleItemActivityRestApplication.prototype.getTypeDescription = function()
	{
		var entityData = this.getAssociatedEntityData();
		if (entityData['APP_TYPE'] && entityData['APP_TYPE']['NAME'])
		{
			return entityData['APP_TYPE']['NAME'];
		}

		return this.getMessage("restApplication");
	};
	BX.CrmScheduleItemActivityRestApplication.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemActivityRestApplication();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmScheduleItemActivityOpenLine) === "undefined")
{
	BX.CrmScheduleItemActivityOpenLine = function()
	{
		BX.CrmScheduleItemActivityOpenLine.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmScheduleItemActivityOpenLine, BX.CrmScheduleItemActivity);
	/*BX.CrmScheduleItemActivityOpenLine.prototype.doInitialize = function()
	{
		BX.CrmScheduleItemActivityOpenLine.superclass.doInitialize.apply(this);
	};*/
	BX.CrmScheduleItemActivityOpenLine.prototype.getWrapperClassName = function()
	{
		return "crm-entity-stream-section-IM";
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.getIconClassName = function()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM";
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.prepareActions = function()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			BX.CrmScheduleOpenLineAction.create(
				"openline",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._schedule.getOwnerInfo()
				}
			)
		);
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.getTypeDescription = function()
	{
		return this.getMessage("openLine");
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.getPrepositionText = function(direction)
	{
		return this.getMessage("reciprocal");
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.prepareCommunicationNode = function(communicationValue)
	{
		return null;
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.prepareDetailNodes = function()
	{
		var wrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-content-detail-IM" } }
		);

		var messageWrapper = BX.create("DIV",
			{ attrs: { className: "crm-entity-stream-content-detail-IM-messages" } }
		);
		wrapper.appendChild(messageWrapper);

		var openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);
		if(openLineData)
		{
			var messages = BX.prop.getArray(openLineData, "MESSAGES", []);
			for(var i = 0, length = messages.length; i < length; i++)
			{
				var message = messages[i];
				var isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);

				messageWrapper.appendChild(
					BX.create("DIV",
						{
							attrs:
							{
								className: isExternal
							        ? "crm-entity-stream-content-detail-IM-message-incoming"
								    : "crm-entity-stream-content-detail-IM-message-outgoing"
							},
							text: BX.prop.getString(message, "MESSAGE", "")
						}
					)
				);
			}
		}

		return [ wrapper ];
	};
	BX.CrmScheduleItemActivityOpenLine.prototype.view = function()
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("openLineNotSupported"));
			return;
		}

		var slug = "";
		var communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);
		if(communication)
		{
			if(BX.prop.getString(communication, "TYPE") === "IM")
			{
				slug = BX.prop.getString(communication, "VALUE");
			}
		}

		if(slug !== "")
		{
			window.top['BXIM'].openMessengerSlider(slug, {RECENT: 'N', MENU: 'N'});
		}
	};
	BX.CrmScheduleItemActivityOpenLine.create = function(id, settings)
	{
		var self = new BX.CrmScheduleItemActivityOpenLine();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

//region Animation
if(typeof(BX.CrmTimelineItemAnimation) === "undefined")
{
	BX.CrmTimelineItemAnimation = function()
	{
		this._id = "";
		this._settings = {};
		this._initialItem = null;
		this._finalItem = null;
		this._events = null;
	};

	BX.CrmTimelineItemAnimation.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._initialItem = this.getSetting("initialItem");
			this._finalItem = this.getSetting("finalItem");

			this._anchor = this.getSetting("anchor");
			this._events = this.getSetting("events", {});
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		run: function()
		{
			this._node = this._initialItem.getWrapper();
			var originalPosition = BX.pos(this._node);
			this._initialYPosition = originalPosition.top;
			this._initialXPosition = originalPosition.left;
			this._initialWidth = this._node.offsetWidth;
			this._initialHeight = this._node.offsetHeight;

			this._anchorYPosition = BX.pos(this._anchor).top;

			this.createStub();
			this.createGhost();
			this.moveGhost();
		},
		createStub: function()
		{
			this._stub = BX.create(
				"DIV",
				{
					attrs: { className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow" },
					children :
						[
							BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon" } }),
							BX.create(
								"DIV",
								{
									props: { className: "crm-entity-stream-section-content" },
									style: { height: this._initialHeight + "px" }
								}
							)
						]
				}
			);

			this._node.parentNode.insertBefore(this._stub, this._node);
		},
		createGhost: function()
		{
			this._ghostNode = this._node;
			this._ghostNode.style.position = "absolute";
			this._ghostNode.style.width = this._initialWidth + "px";
			this._ghostNode.style.height = this._initialHeight + "px";
			this._ghostNode.style.top = this._initialYPosition + "px";
			this._ghostNode.style.left = this._initialXPosition + "px";
			document.body.appendChild(this._ghostNode);
			setTimeout(BX.proxy(function (){BX.addClass(this._ghostNode, "crm-entity-stream-section-casper" )}, this), 20);
		},
		moveGhost: function ()
		{
			var node = this._ghostNode;
			var movingEvent = new BX.easing({
				duration : 500,
				start : { top: this._initialYPosition},
				finish: { top: this._anchorYPosition},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					node.style.top = state.top + "px";
				}, this)
			});
			setTimeout( BX.proxy(function () {
				movingEvent.animate();
				node.style.boxShadow = "";
			}, this), 500);

			var placeEventAnim = new BX.easing({
				duration : 500,
				start : { height: 0 },
				finish: { height: this._initialHeight+20 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					this._anchor.style.height = state.height + "px";
				}, this),
				complete: BX.proxy(function () {
					if(BX.type.isFunction(this._events["complete"]))
					{
						this._events["complete"]();
					}

					this.addHistoryItem();
					this.removeGhost();
				}, this)
			});
			setTimeout(function (){placeEventAnim.animate()}, 500);
		},
		addHistoryItem: function()
		{
			var node = this._finalItem.getWrapper();

			this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);

			this._finalItemHeight = this._anchor.offsetHeight - node.offsetHeight;
			this._anchor.style.height = 0;
			node.style.marginBottom = this._finalItemHeight + "px";
		},
		removeGhost: function ()
		{
			var ghostNode = this._ghostNode;
			var finalNode = this._finalItem.getWrapper();

			ghostNode.style.overflow = "hidden";
			var hideCasperItem = new BX.easing({
				duration : 70,
				start : { opacity: 100, height: ghostNode.offsetHeight, marginBottom: this._finalItemHeight },
				finish: { opacity: 0, height: finalNode.offsetHeight, marginBottom: 20 },
				// transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					ghostNode.style.opacity = state.opacity / 100;
					ghostNode.style.height = state.height + "px";
					finalNode.style.marginBottom = state.marginBottom + "px";
				}, this),
				complete: BX.proxy(function () {
					ghostNode.remove();
					finalNode.style.marginBottom = "";
					this.collapseStub();

				}, this)
			});
			hideCasperItem.animate();
		},
		collapseStub: function ()
		{
			var removePlannedEvent = new BX.easing({
				duration : 500,
				start : { opacity: 100, height: this._initialHeight, marginBottom: 15},
				finish: { opacity: 0, height: 0, marginBottom: 0},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					this._stub.style.height = state.height + "px";
					this._stub.style.marginBottom = state.marginBottom + "px";
					this._stub.style.opacity = state.opacity / 100;
				}, this),
				complete: BX.proxy( function(){this.inited = false} , this)

			});
			removePlannedEvent.animate();

		}
	};

	BX.CrmTimelineItemAnimation.create = function(id, settings)
	{
		var self = new BX.CrmTimelineItemAnimation();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmTimelineItemAnimationNew) === "undefined")
{
	BX.CrmTimelineItemAnimationNew = function()
	{
		this._id = "";
		this._settings = {};
		this._initialItem = null;
		this._finalItem = null;
		this._events = null;
	};

	BX.CrmTimelineItemAnimationNew.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._initialItem = this.getSetting("initialItem");
			this._finalItem = this.getSetting("finalItem");

			this._anchor = this.getSetting("anchor");
			this._events = this.getSetting("events", {});
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		addHistoryItem: function()
		{
			var node = this._finalItem.getWrapper();

			this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);

		},
		run: function()
		{
			this._node = this._initialItem.getWrapper();
			this.createStub();

			BX.addClass(this._node, 'crm-entity-stream-section-animate-start');

			this._startPosition = BX.pos(this._stub);

			this._node.style.position = "absolute";
			this._node.style.width = this._startPosition.width + "px";
			this._node.style.height = this._startPosition.height + "px";
			this._node.style.top = this._startPosition.top + "px";
			this._node.style.left = this._startPosition.left + "px";
			this._node.style.zIndex = 960;


			document.body.appendChild(this._node);

			var shift = BX.CrmTimelineItemShift.create(
				this._node,
				this._anchor,
				this._startPosition,
				this._stub,
				{ complete: BX.delegate(this.finish, this) }
			);
			shift.run();
		},
		createStub: function()
		{
			this._stub = BX.create(
				"DIV",
				{
					attrs: { className: "crm-entity-stream-section crm-entity-stream-section-planned crm-entity-stream-section-shadow" },
					children :
					[
						BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon" } }),
						BX.create(
							"DIV",
							{
								props: { className: "crm-entity-stream-section-content" },
								style: { height: this._initialItem._wrapper.clientHeight + "px" }
							}
						)
					]
				}
			);

			this._node.parentNode.insertBefore(this._stub, this._node);
		},
		finish: function()
		{
			var stubContainer = this._stub.querySelector('.crm-entity-stream-section-content');

			this._anchor.style.height = 0;
			//this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);

			setTimeout(
				BX.delegate(function() {
					BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
				}, this),
				0
			);

			this._node.style.opacity = 0;

			setTimeout( BX.delegate(
				function() {
					stubContainer.style.height = 0;
					stubContainer.style.opacity = 0;
					stubContainer.style.paddingTop = 0;
					stubContainer.style.paddingBottom = 0;
				},
				this
			), 120 );

			setTimeout( BX.delegate(
				function() {
					BX.remove(this._stub);
					BX.remove(this._node);
					this.addHistoryItem();

					if(BX.type.isFunction(this._events["complete"]))
					{
						this._events["complete"]();
					}
				},
				this
			), 420 );

		}
	};

	BX.CrmTimelineItemAnimationNew.create = function(id, settings)
	{
		var self = new BX.CrmTimelineItemAnimationNew();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmTimelineItemExpand) === "undefined")
{
	BX.CrmTimelineItemExpand = function()
	{
		this._node = null;
		this._callback = null;
	};

	BX.CrmTimelineItemExpand.prototype =
	{
		initialize: function(node, callback)
		{
			this._node = node;
			this._callback = BX.type.isFunction(callback) ? callback : null;
		},
		run: function()
		{
			var position = BX.pos(this._node);

			this._node.style.height = 0;
			this._node.style.opacity = 0;
			this._node.style.overflow = "hidden";

			(new BX.easing(
					{
						duration : 150,
						start : { height: 0 },
						finish: { height: position.height },
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step: BX.delegate(this.onNodeHeightStep, this),
						complete: BX.delegate(this.onNodeHeightComplete, this)
					}
				)
			).animate();
		},
		onNodeHeightStep: function(state)
		{
			this._node.style.height = state.height + "px";
		},
		onNodeHeightComplete: function()
		{
			this._node.style.overflow = "";
			(new BX.easing(
					{
						duration : 150,
						start : { opacity: 0 },
						finish: { opacity: 100 },
						transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
						step: BX.delegate(this.onNodeOpacityStep, this),
						complete: BX.delegate(this.onNodeOpacityComplete, this)
					}
				)
			).animate();
		},
		onNodeOpacityStep: function(state)
		{
			this._node.style.opacity = state.opacity / 100;
		},
		onNodeOpacityComplete: function()
		{
			this._node.style.height = "";
			this._node.style.opacity = "";
			if(this._callback)
			{
				this._callback();
			}
		}
	};

	BX.CrmTimelineItemExpand.create = function(node, callback)
	{
		var self = new BX.CrmTimelineItemExpand();
		self.initialize(node, callback);
		return self;
	};
}

if(typeof(BX.CrmTimelineItemExtract) === "undefined")
{
	BX.CrmTimelineItemExtract = function()
	{
		this._node = null;
		this._shadowNode = null;
		this._events = null;
	};
	BX.CrmTimelineItemExtract.prototype =
	{
		initialize: function(node, shadowNode, events)
		{
			this._node = node;
			this._shadowNode  = shadowNode;
			this._events = BX.type.isPlainObject(events) ? events : {};

		},
		run: function()
		{
			//TODO: Implement
		},
		finish: function()
		{
			//TODO: Implement
		}
	};
	BX.CrmTimelineItemExtract.create = function(node, shadowNode, events)
	{
		var self = new BX.CrmTimelineItemExtract();
		self.initialize(node, shadowNode, events);
		return self;
	};
}

if(typeof(BX.CrmTimelineItemShift) === "undefined")
{
	BX.CrmTimelineItemShift = function()
	{
		this._node = null;
		this._anchor = null;
		this._nodeParent  = null;
		this._startPosition = null;
		this._events = null;
	};
	BX.CrmTimelineItemShift.prototype =
	{
		initialize: function(node, anchor, startPosition, shadowNode, events)
		{
			this._node = node;
			this._shadowNode = shadowNode;
			this._anchor = anchor;
			this._nodeParent  = node.parentNode;
			this._startPosition = startPosition;
			this._events = BX.type.isPlainObject(events) ? events : {};

		},
		run: function()
		{
			// if( this._shadowNode !== false )
			// {
			// 	this.createStub();
			// }
			this._anchorPosition = BX.pos(this._anchor);

			setTimeout(
				BX.proxy(
					function (){
						BX.addClass(this._node, "crm-entity-stream-section-casper" )
					},
					this
				),
				0
			);

			var movingEvent = new BX.easing({
				duration : 1500,
				start : { top: this._startPosition.top, height: 0},
				finish: { top: this._anchorPosition.top, height: this._startPosition.height + 20},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					this._node.style.top = state.top + "px";
					this._anchor.style.height = state.height + "px";
				}, this),
				complete: BX.proxy(function () {
					this.finish();
				}, this)
			});
			movingEvent.animate();
		},
		finish: function()
		{
			if(BX.type.isFunction(this._events["complete"]))
			{
				this._events["complete"]();
			}
			if( this._shadowNode !== false )
			{
				// this._stub.height = 0;
			}
		}
	};
	BX.CrmTimelineItemShift.create = function(node, anchor, startPosition, shadowNode, events)
	{
		var self = new BX.CrmTimelineItemShift();
		self.initialize(node, anchor, startPosition, shadowNode, events);
		return self;
	};
}

if(typeof(BX.CrmCommentAnimation) === "undefined")
{
	BX.CrmCommentAnimation = function()
	{
		this._node = null;
		this._anchor = null;
		this._nodeParent  = null;
		this._startPosition = null;
		this._events = null;
	};
	BX.CrmCommentAnimation.prototype =
	{
		initialize: function(node, anchor, startPosition, events)
		{
			this._node = node;
			this._anchor = anchor;
			this._nodeParent  = node.parentNode;
			this._startPosition = startPosition;
			this._events = BX.type.isPlainObject(events) ? events : {};

		},
		run: function()
		{
			BX.addClass(this._node, 'crm-entity-stream-section-animate-start');

			this._node.style.position = "absolute";
			this._node.style.width = this._startPosition.width + "px";
			this._node.style.height = this._startPosition.height + "px";
			this._node.style.top = this._startPosition.top - 30 + "px";
			this._node.style.left = this._startPosition.left + "px";
			this._node.style.opacity = 0;
			this._node.style.zIndex = 960;

			document.body.appendChild(this._node);

			var nodeOpacityAnim = new BX.easing({
				duration : 350,
				start : { opacity: 0 },
				finish: { opacity: 100},
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					this._node.style.opacity = state.opacity / 100;
				}, this),
				complete: BX.proxy(function () {
					if(BX.type.isFunction(this._events["start"]))
					{
						this._events["start"]();
					}
					var shift = BX.CrmTimelineItemShift.create(
						this._node,
						this._anchor,
						this._startPosition,
						false,
						{ complete: BX.delegate(this.finish, this) }
					);
					shift.run();
				}, this)
			});
			nodeOpacityAnim.animate();


			if(BX.type.isFunction(this._events["complete"]))
			{
				this._events["complete"]();
			}
		},
		finish: function()
		{
			this._node.style.position = "";
			this._node.style.width = "";
			this._node.style.height = "";
			this._node.style.top = "";
			this._node.style.left = "";
			this._node.style.opacity = "";
			this._node.style.zIndex = "";
			this._anchor.style.height = "";
			this._anchor.parentNode.insertBefore(this._node, this._anchor.nextSibling);
			setTimeout(
				BX.delegate(function() {
					BX.removeClass(this._node, 'crm-entity-stream-section-animate-start');
					BX.remove(this._anchor);
					}, this),
				0
			);
		}
	};
	BX.CrmCommentAnimation.create = function(node, anchor, startPosition, events)
	{
		var self = new BX.CrmCommentAnimation();
		self.initialize(node, anchor, startPosition, events);
		return self;
	};
}

if(typeof(BX.CrmTimelineItemFasten) === "undefined")
{
	BX.CrmTimelineItemFasten = function()
	{
		this._id = "";
		this._settings = {};
		this._initialItem = null;
		this._finalItem = null;
		this._events = null;
	};

	BX.CrmTimelineItemFasten.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._initialItem = this.getSetting("initialItem");
			this._finalItem = this.getSetting("finalItem");

			this._anchor = this.getSetting("anchor");
			this._events = this.getSetting("events", {});
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultValue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultValue;
		},
		addFixedHistoryItem: function()
		{
			var node = this._finalItem.getWrapper();
			BX.addClass(node, 'crm-entity-stream-section-animate-start');
			this._anchor.parentNode.insertBefore(node, this._anchor.nextSibling);
			setTimeout( BX.delegate(
				function() {
					BX.removeClass(node, 'crm-entity-stream-section-animate-start');
				},
				this
			), 0);
			this._finalItem.onFinishFasten();
		},
		run: function()
		{
			var node =  this._initialItem.getWrapper();
			this._clone = node.cloneNode(true);

			BX.addClass(this._clone, 'crm-entity-stream-section-animate-start crm-entity-stream-section-top-fixed');

			this._startPosition = BX.pos(node);
			this._clone.style.position = "absolute";
			this._clone.style.width = this._startPosition.width + "px";

			var _cloneHeight = this._startPosition.height;
			var _minHeight = 65;
			var _sumPaddingContent = 18;
			if (_cloneHeight < _sumPaddingContent + _minHeight)
				_cloneHeight = _sumPaddingContent + _minHeight;

			this._clone.style.height = _cloneHeight + "px";
			this._clone.style.top = this._startPosition.top + "px";
			this._clone.style.left = this._startPosition.left + "px";
			this._clone.style.zIndex = 960;

			document.body.appendChild(this._clone);

			setTimeout(
				BX.proxy(
					function (){
						BX.addClass(this._clone, "crm-entity-stream-section-casper" )
					},
					this
				),
				0
			);

			this._anchorPosition = BX.pos(this._anchor);
			var finish = {
				top: this._anchorPosition.top,
				height: _cloneHeight + 15,
				opacity: 1
			};

			var _difference = this._startPosition.top - this._anchorPosition.bottom;
			var _deepHistoryLimit = 2 * (document.body.clientHeight + this._startPosition.height);

			if (_difference > _deepHistoryLimit)
			{
				finish.top = this._startPosition.top - _deepHistoryLimit;
				finish.opacity = 0;
			}

			var _duration = Math.abs(finish.top - this._startPosition.top) * 2;
			_duration = (_duration < 1500) ? 1500 : _duration;

			var movingEvent = new BX.easing({
				duration : _duration,
				start : { top: this._startPosition.top, height: 0, opacity: 1},
				finish: finish,
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step: BX.proxy(function(state) {
					this._clone.style.top = state.top + "px";
					this._clone.style.opacity = state.opacity;
					this._anchor.style.height = state.height + "px";
				}, this),
				complete: BX.proxy(function () {
					this.finish();
				}, this)
			});
			movingEvent.animate();
		},
		finish: function()
		{
			this._anchor.style.height = 0;
			this.addFixedHistoryItem();
			BX.remove(this._clone);

			if(BX.type.isFunction(this._events["complete"]))
			{
				this._events["complete"]();
			}
		}
	};

	BX.CrmTimelineItemFasten.create = function(id, settings)
	{
		var self = new BX.CrmTimelineItemFasten();
		self.initialize(id, settings);
		return self;
	};
}

//endregion

//region Menu Bar
if(typeof(BX.CrmTimelineMenuBar) === "undefined")
{
	BX.CrmTimelineMenuBar = function()
	{
		this._id = "";
		this._ownerInfo = null;
		this._container = null;
		this._items = null;
		this._moreButton = null;
		this._activityEditor = null;
		this._commentEditor = null;
		this._waitEditor = null;
		this._smsEditor = null;
		this._readOnly = false;

		this._menu = null;
		this._isMenuShown = false;
		this._manager = null;
	};
	BX.CrmTimelineMenuBar.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._ownerInfo = BX.prop.getObject(this._settings, "ownerInfo");
			if(!this._ownerInfo)
			{
				throw "BX.CrmTimelineMenuBar. A required parameter 'ownerInfo' is missing.";
			}

			this._activityEditor = BX.prop.get(this._settings, "activityEditor", null);

			this._commentEditor = BX.prop.get(this._settings, "commentEditor");
			this._waitEditor = BX.prop.get(this._settings, "waitEditor");
			this._smsEditor = BX.prop.get(this._settings, "smsEditor");
			this._restEditor = BX.prop.get(this._settings, "restEditor");
			this._manager = BX.prop.get(this._settings, "manager");
			if(!(this._manager instanceof BX.CrmTimelineManager))
			{
				throw "BX.CrmTimeline. Manager instance is not found.";
			}

			this._container = BX.prop.getElementNode(this._settings, "container");
			if(!this._container)
			{
				throw "BX.CrmTimelineMenuBar. A required parameter 'container' is missing.";
			}

			this._readOnly = BX.prop.getBoolean(this._settings, "readOnly", false);

			this._items = [];
			var itemContainers = this._container.querySelectorAll(".crm-entity-stream-section-new-action");
			for(var i = 0, l = itemContainers.length; i < l; i++)
			{
				var itemContainer = itemContainers[i];
				var item = BX.CrmTimelineMenuBarItem.create(
					itemContainer.getAttribute("data-item-id"),
					{ container: itemContainer, owner: this }
				);
				this._items.push(item);
			}

			this._moreButton = this._container.querySelector(".crm-entity-stream-section-new-action-more");
			if(this._moreButton)
			{
				BX.bind(this._moreButton, "click", BX.delegate(this.onMoreButtonClick, this));
			}

			this.adjust();
			BX.bind(window, 'resize', BX.delegate(this.onResize, this));
		},
		getId: function()
		{
			return this._id;
		},
		getItemById: function(id)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var currentItem = this._items[i];
				if(currentItem.getId() === id)
				{
					return currentItem;
				}
			}
			return null;
		},
		setActiveItemById: function(id)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var currentItem = this._items[i];
				currentItem.setActive(currentItem.getId() === id);
			}
		},
		setActiveItem: function(item)
		{
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var currentItem = this._items[i];
				currentItem.setActive(currentItem === item);
			}
		},
		adjust: function()
		{
			if(this._moreButton)
			{
				this._moreButton.style.display = this.getInvisibleItems().length > 0 ? "" : "none";
			}
		},
		processItemSelection: function(item)
		{
			if(this._readOnly)
			{
				return;
			}

			var planner = null;
			var action = item.getId();
			if(action === "call")
			{
				planner = new BX.Crm.Activity.Planner();
				planner.showEdit(
					{
						"TYPE_ID": BX.CrmActivityType.call,
						"OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
						"OWNER_ID": this._ownerInfo['ENTITY_ID']
					}
				);
			}
			if(action === "meeting")
			{
				planner = new BX.Crm.Activity.Planner();
				planner.showEdit(
					{
						"TYPE_ID": BX.CrmActivityType.meeting,
						"OWNER_TYPE_ID": this._ownerInfo['ENTITY_TYPE_ID'],
						"OWNER_ID": this._ownerInfo['ENTITY_ID']
					}
				);
			}
			else if(action === "email")
			{
				this._activityEditor.addEmail(
					{
						"ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
						"ownerID": this._ownerInfo['ENTITY_ID'],
						"ownerUrl": this._ownerInfo['SHOW_URL'],
						"ownerTitle": this._ownerInfo['TITLE'],
						"subject": ""
					}
				);
			}
			else if(action === "task")
			{
				this._activityEditor.addTask(
					{
						"ownerType": this._ownerInfo['ENTITY_TYPE_NAME'],
						"ownerID": this._ownerInfo['ENTITY_ID']
					}
				);
			}
			else if(action === "comment" && this._commentEditor)
			{
				if(this._waitEditor)
				{
					this._waitEditor.setVisible(false);
				}
				if(this._smsEditor)
				{
					this._smsEditor.setVisible(false);
				}
				this._commentEditor.setVisible(true);
				this.setActiveItem(item);
			}
			else if(action === "wait" && this._waitEditor)
			{
				if(this._commentEditor)
				{
					this._commentEditor.setVisible(false);
				}
				if(this._smsEditor)
				{
					this._smsEditor.setVisible(false);
				}
				this._waitEditor.setVisible(true);
				this.setActiveItem(item);
			}
			else if(action === "sms" && this._smsEditor)
			{
				if(this._commentEditor)
				{
					this._commentEditor.setVisible(false);
				}
				if(this._waitEditor)
				{
					this._waitEditor.setVisible(false);
				}
				this._smsEditor.setVisible(true);
				this.setActiveItem(item);
			}
			else if(action === "visit")
			{
				var visitParameters = this._manager.getSetting("visitParameters");
				visitParameters['OWNER_TYPE'] = this._ownerInfo['ENTITY_TYPE_NAME'];
				visitParameters['OWNER_ID'] = this._ownerInfo['ENTITY_ID'];
				BX.CrmActivityVisit.create(visitParameters).showEdit();
			}
			else if(action.match(/^activity_rest_/))
			{
				if(this._restEditor)
				{
					this._restEditor.action(action);
				}
			}
		},
		getInvisibleItems: function()
		{
			var results = [];
			for(var i = 0, length = this._items.length; i < length; i++)
			{
				var item = this._items[i];
				if(!item.isVisible())
				{
					results.push(item);
				}
			}
			return results;
		},
		openMenu: function()
		{
			var items = this.getInvisibleItems();
			if(items.length === 0)
			{
				return;
			}

			var handler = BX.delegate(this.onMenuItemSelect, this);
			var menuItems = [];
			for(var i = 0, length = items.length; i < length; i++)
			{
				var item = items[i];
				menuItems.push(
					{
						id: item.getId(),
						text: item.getTitle(),
						onclick: handler
					}
				);
			}

			BX.PopupMenu.show(
				this._id,
				this._moreButton,
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
			this._menu = BX.PopupMenu.currentItem;
		},
		closeMenu: function()
		{
			if(this._menu)
			{
				this._menu.close();
			}
		},
		onMoreButtonClick: function(e)
		{
			if(!this._isMenuShown)
			{
				this.openMenu();
			}
			else
			{
				this.closeMenu();
			}
			e.preventDefault ? e.preventDefault() : (e.returnValue = false);
		},
		onMenuItemSelect: function(e, menuItem)
		{
			var item = this.getItemById(menuItem.id);
			if(item)
			{
				this.processItemSelection(item);
			}

			this.closeMenu();
		},
		onContextMenuShow: function()
		{
			this._isMenuShown = true;
		},
		onContextMenuClose: function()
		{
			if(this._menu)
			{
				this._menu.popupWindow.destroy();
			}
		},
		onContextMenuDestroy: function()
		{
			this._isMenuShown = false;
			this._menu = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		},
		onResize: function(e)
		{
			if(this._isMenuShown)
			{
				this.closeMenu();
			}
			this.adjust();
		}
	};
	BX.CrmTimelineMenuBar.create = function(id, settings)
	{
		var self = new BX.CrmTimelineMenuBar();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmTimelineMenuBarItem) === "undefined")
{
	BX.CrmTimelineMenuBarItem = function()
	{
		this._id = "";
		this._owner = null;
		this._container = null;
		this._isActive = false;
	};
	BX.CrmTimelineMenuBarItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._container = BX.prop.getElementNode(this._settings, "container");
			if(!this._container)
			{
				throw "BX.CrmTimelineMenuBarItem. Container node is not found.";
			}

			BX.bind(this._container, "click", BX.delegate(this.onClick, this));
			this._isActive = BX.hasClass(this._container, "crm-entity-stream-section-new-action-active");

			this._owner = BX.prop.get(this._settings, "owner");
			if(!this._owner)
			{
				throw "BX.CrmTimelineMenuBarItem. Owner is not found.";
			}

		},
		getId: function()
		{
			return this._id;
		},
		isActive: function()
		{
			return this._isActive;
		},
		setActive: function(active)
		{
			active = !!active;
			this._isActive = active;
			if(active)
			{
				BX.addClass(this._container, "crm-entity-stream-section-new-action-active");
			}
			else
			{
				BX.removeClass(this._container, "crm-entity-stream-section-new-action-active");
			}
		},
		isVisible: function()
		{
			return this._container.offsetTop === 0;
		},
		getTitle: function()
		{
			var title = this._container.getAttribute("data-item-title");
			if(!BX.type.isNotEmptyString(title))
			{
				title = this._container.innerHTML;
			}
			return BX.util.htmlspecialcharsback(title);
		},
		onClick: function(e)
		{
			this._owner.processItemSelection(this);
			e.preventDefault ? e.preventDefault() : (e.returnValue = false);
		}
	};

	BX.CrmTimelineMenuBarItem.create = function(id, settings)
	{
		var self = new BX.CrmTimelineMenuBarItem();
		self.initialize(id, settings);
		return self;
	}
}
//endregion

//region Watchers
if(typeof(BX.CrmSmsWatcher) === "undefined")
{
	BX.CrmSmsWatcher =
	{
		_pullTagName: 'MESSAGESERVICE',
		_pullInited: false,
		_listeners: {},
		initPull: function()
		{
			if (this._pullInited)
				return;

			BX.addCustomEvent("onPullEvent-messageservice", this.onPullEvent.bind(this));
			this.extendWatch();

			this._pullInited = true;
		},
		subscribeOnMessageUpdate: function(messageId, callback)
		{
			this.initPull();
			this._listeners[messageId] = callback;
		},
		fireExternalStatusUpdate: function(messageId, message)
		{
			var listener = this._listeners[messageId];
			if (listener)
			{
				listener(message);
			}
		},
		onPullEvent: function(command, params)
		{
			// console.log(command, params);
			if (command === 'message_update')
			{
				for (var i = 0; i < params.messages.length; ++i)
				{
					var message = params.messages[i];
					this.fireExternalStatusUpdate(message['ID'], message);
				}
			}
		},
		extendWatch: function()
		{
			if(BX.type.isFunction(BX.PULL))
			{
				BX.PULL.extendWatch(this._pullTagName);
				window.setTimeout(this.extendWatch.bind(this), 60000);
			}
		}
	};
}
//endregion