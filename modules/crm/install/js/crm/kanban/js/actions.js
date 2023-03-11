(function() {

	"use strict";

	BX.namespace("BX.CRM.Kanban");

	/**
	 * Multiple actions for CRM Kanban.
	 * @constructor
	 */
	BX.CRM.Kanban.Actions = //function()
	{
		/**
		 * Start calling list.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Boolean} createActivity
		 * @returns {void}
		 */
		startCallList: function(grid, createActivity)
		{
			if (typeof BX.CrmCallListHelper === "undefined")
			{
				return;
			}
			if (typeof createActivity === "undefined")
			{
				createActivity = true;
			}

			var gridData = grid.getData();

			BX.CrmCallListHelper.createCallList(
				{
					entityType: gridData.entityType,
					entityIds: grid.getCheckedId(),
					createActivity: createActivity
				},
				function(response)
				{
					if( !BX.type.isPlainObject(response))
					{
						return;
					}
					if (!response.SUCCESS && response.ERRORS)
					{
						var error = response.ERRORS.join(". \n");
						BX.Kanban.Utils.showErrorDialog(error);
					}
					else if (response.SUCCESS && response.DATA)
					{
						var data = response.DATA;
						if (data.RESTRICTION)
						{
							if (BX.Type.isPlainObject(data.RESTRICTION) && B24 && B24.licenseInfoPopup)
							{
								B24.licenseInfoPopup.show('ivr-limit-popup', data.RESTRICTION.HEADER, data.RESTRICTION.CONTENT);
							}
							else if (BX.Type.isStringFilled(data.RESTRICTION))
							{
								eval(data.RESTRICTION);
							}
						}
						else
						{
							var callListId = data.ID;
							if (createActivity && top.BXIM)
							{
								top.BXIM.startCallList(callListId, {});
							}
							else
							{
								(new BX.Crm.Activity.Planner()).showEdit({
									PROVIDER_ID: "CALL_LIST",
									PROVIDER_TYPE_ID: "CALL_LIST",
									ASSOCIATED_ENTITY_ID: callListId
								});
							}
						}
					}
				}
			);
		},

		/**
		 * Notify by message code (if exists).
		 * @param {Strings} code Message code.
		 * @param {Object params Some params.
		 * @return {void}
		 */
		notifySimpleAction: function(code, params)
		{
			if (code === "DEAL_CHANGECATEGORY")
			{
				code = "DEAL_CHANGECATEGORY_LINK2";
			}
			if (code === "DYNAMIC_CHANGECATEGORY")
			{
				code = "DYNAMIC_CHANGECATEGORY_LINK2";
			}
			code = "CRM_KANBAN_NOTIFY_" + code;
			if (typeof BX.message[code] !== "undefined")
			{
				var mess = BX.message[code];
				if (BX.type.isPlainObject(params))
				{
					for (var k in params)
					{
						mess = mess.replace(
							"#" + k + "#",
							params[k]
						);
					}
				}
				BX.UI.Notification.Center.notify({
					content: mess
				});
			}
		},

		/**
		 *Some simple action.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Object} params
		 * @param {boolean} disableNotify
		 * @returns {void}
		 */
		simpleAction: function(grid, params, disableNotify)
		{
			if (grid.isMultiSelectMode())
			{
				grid.resetMultiSelectMode();
			}

			params.eventId = (params.eventId || BX.Crm.Kanban.PullManager.registerRandomEventId());

			return new Promise(function(resolve,reject){
				grid.ajax(
					params,
					function(data)
					{
						var gridData = grid.getData();

						if (data && !data.error)
						{
							if (!disableNotify)
							{
								grid.onApplyFilter();
							}
							grid.stopActionPanel();
							var code = gridData.entityType;
							if (code.indexOf('DYNAMIC') === 0)
							{
								code = 'DYNAMIC';
							}
							if (
								params.action === "delete" &&
								params.ignore === "Y"
							)
							{
								code += "_IGNORE";
							}
							else
							{
								code += "_" + params.action.toUpperCase();
							}
							if (disableNotify !== true)
							{
								this.notifySimpleAction(code, params);
							}
							resolve(data);
						}
						else if (data)
						{
							// for change column
							if (params.action === "status")
							{
								grid.stopActionPanel();
								grid.onApplyFilter();
								if (grid.getTypeInfoParam('showPersonalSetStatusNotCompletedText'))
								{
									var messageCode = gridData.isDynamicEntity
										? "CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_DYNAMIC"
										: "CRM_KANBAN_SET_STATUS_NOT_COMPLETED_TEXT_" + gridData.entityType;

									BX.Kanban.Utils.showErrorDialog(BX.message(messageCode));
									reject(new Error(BX.message(messageCode)));
								}
								else
								{
									BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
									reject(new Error(data.error));
								}
							}
							else
							{
								BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
								reject(new Error(data.error));
							}
						}
					}.bind(this),
					function(error)
					{
						BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
						reject(new Error(error));
					}.bind(this)
				);
			}.bind(this));
		},

		/**
		 * Start calling list.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Object} assigned
		 * @returns {void}
		 */
		setAssigned: function(grid, assigned)
		{
			this.simpleAction(grid, {
				action: "setAssigned",
				ids: grid.getCheckedId(),
				assignedId: assigned.entityId,
				assignedName: assigned.name
			}, false);
		},

		/**
		 * Merge selected items.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		merge: function(grid)
		{
			var selectedIds = grid.getCheckedId();
			var mergeManager = BX.Crm.BatchMergeManager.getItem(grid.getData().gridId);
			if(mergeManager && !mergeManager.isRunning() && selectedIds.length > 1)
			{
				mergeManager.setEntityIds(selectedIds);
				mergeManager.execute();
			}
		},

		/**
		 * Change category id for deals.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param category
		 * @package {int} category
		 * @returns {void}
		 */
		changeCategory: function(grid, category)
		{
			var categoryLink = "";

			if (category.url)
			{
				categoryLink = category.url;
			}

			this.simpleAction(grid, {
				action: "changeCategory",
				id: grid.getCheckedId(),
				category: category.ID,
				categoryName: BX.util.htmlspecialchars(category.NAME),
				categoryLink: categoryLink
			}, false);
		},

		/**
		 * Change column.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @package {Object} column
		 * @returns {void}
		 */
		changeColumn: function(grid, column)
		{
			var gridData = grid.getData();
			grid.firstRenderComplete = false;

			this.simpleAction(grid, {
				action: "status",
				entity_id: grid.getCheckedId(),
				status: column.id,
				statusName: BX.util.htmlspecialchars(column.name),
				entity_type: gridData.entityType
			}, false);
		},

		/**
		 * Delete one item.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param ids
		 * @param {BX.CRM.Kanban.DropZone} drop
		 */
		delete: function(grid, ids, drop)
		{
			ids = ids ? ids : grid.getCheckedId();

			this.simpleAction(grid, {
				action: "delete",
				id: ids
			}, true)
				.then(
					function(response){

						if (drop)
						{
							var removedItems = (ids.length ? drop.droppedItems : [drop.droppedItem]);
							var deleteTitle = (
								ids.length
								? BX.message('CRM_KANBAN_DELETE_SUCCESS_MULTIPLE')
								: BX.message('CRM_KANBAN_DELETE_SUCCESS').replace('#ELEMENT_NAME#', drop.droppedItem.data.name)
							);

							drop.empty();
							drop.getDropZoneArea().hide();
							drop.droppedItems = [];
							grid.dropZonesShow = false;
							grid.resetMultiSelectMode();
							grid.resetActionPanel();
							grid.resetDragMode();

							var ballonOptions = {
								content: deleteTitle
							};

							if (grid.getTypeInfoParam('isRecyclebinEnabled'))
							{
								ballonOptions.actions = [
									{
										title: BX.message('CRM_KANBAN_DELETE_CANCEL'),
										events: {
											click: function() {
												balloon.close();

												BX.ajax.runComponentAction('bitrix:crm.kanban', 'restore', {
													mode: 'ajax',
													data: {
														entityIds: (Array.isArray(ids) ? ids : [ids]),
														entityTypeId: grid.data.entityTypeInt
													}
												}).then(function(response) {
													removedItems.forEach(function(item){
														var column = grid.getColumn(item.options.columnId);
														var items = column.getItems();
														var beforeItem = items.length ? items[0] : null;
														item.visible = true;
														column.addItem(item, beforeItem);
													});
													var autoHideDelay = 6000;
													BX.UI.Notification.Center.notify({
														content: BX.message('CRM_KANBAN_DELETE_RESTORE_SUCCESS'),
														autoHideDelay: autoHideDelay,
													});
												}, function(response) {
													BX.UI.Notification.Center.notify({
														content: response.errors[0].message
													});
												});
											}
										}
									}
								];
							}

							var balloon = BX.UI.Notification.Center.notify(ballonOptions);
						}
					}, function(response) {
						BX.UI.Notification.Center.notify({
							content: response.errors[0].message
						});
					}
				);
		},

		/**
		 * Delete.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		deleteAll: function(grid)
		{
			this.confirm(
				BX.message("CRM_KANBAN_PANEL_ACTION_CONFIRM"),
				function()
				{
					this.simpleAction(grid, {
						action: "delete",
						id: grid.getCheckedId()
					}, false);
				}.bind(this),
				{ grid }
			);
		},

		/**
		 * Open / close.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Boolean} open Open or close.
		 * @returns {void}
		 */
		open: function(grid, open)
		{
			if (typeof open === "undefined")
			{
				open = false;
			}
			this.simpleAction(grid, {
				action: "open",
				id: grid.getCheckedId(),
				flag: open ? "Y" : "N"
			}, false);
		},

		/**
		 * Ignore.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Boolean} open Open or close.
		 * @returns {void}
		 */
		ignore: function(grid, open)
		{
			this.confirm(
				BX.message("CRM_KANBAN_PANEL_ACTION_CONFIRM"),
				function()
				{
					grid.fadeOut();
					BX.ajax.runComponentAction('bitrix:crm.kanban', 'excludeEntity', {
						mode: 'ajax',
						data: {
							entityType: grid.getData().entityType,
							ids: grid.getCheckedId(),
						}
					}).then(function(response) {
						this.simpleAction(grid, {
							action: "delete",
							ignore: "Y",
							id: grid.getCheckedId()
						}, false);
					}.bind(this), function(response) {
						grid.stopActionPanel();
						grid.onApplyFilter();
						BX.UI.Notification.Center.notify({
							content: response.errors[0].message
						});
					}.bind(this));
				}.bind(this),
				{ grid }
			);
		},

		/**
		 * Refresh deals accounts.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		refreshaccount: function(grid)
		{
			this.simpleAction(grid, {
				action: "refreshAccount",
				id: grid.getCheckedId()
			}, false);
		},

		/**
		 * Send email.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		email: function(grid)
		{
			if (
				BX.CrmActivityEditor &&
				BX.CrmActivityProvider &&
				BX.CrmActivityEditor.items["kanban_activity_editor"]
			)
			{
				var gridData = grid.getData();
				var communications = [];
				var ids = grid.getCheckedId();

				for (var i = 0, c = ids.length; i < c; i++)
				{
					communications.push({
						type: "EMAIL",
						entityId: ids[i],
						entityType: gridData.entityType
					});
				}

				BX.CrmActivityEditor.items["kanban_activity_editor"].addEmail({
					communications: communications,
					communicationsLoaded: true
				});
			}
		},

		/**
		 * Add task.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		task: function(grid)
		{
			if (typeof window["taskIFramePopup"] !== "undefined")
			{
				var gridData = grid.getData();
				var communications = "";
				var ids = grid.getCheckedId();

				for (var i = 0, c = ids.length; i < c; i++)
				{
					communications +=
						BX.CrmOwnerTypeAbbr.resolve(gridData.entityType) +
						"_" +
						ids[i] + ";";
				}

				window["taskIFramePopup"].add({
					UF_CRM_TASK: communications,
					TITLE: "CRM: ",
					TAGS: "crm"
				});
			}
		},

		/**
		 * Confirm for some actions.
		 * @param {String} message
		 * @param {Function} acceptFunc
		 * @param {Object} params
		 * @return {BX.PopupWindowManager}
		 */
		confirm: function(message, acceptFunc, params = {})
		{
			var dialog = BX.PopupWindowManager.create(
				"crm-kanban-confirm-dialog",
				null,
				{
					titleBar: BX.message("CRM_KANBAN_CONFIRM_TITLE"),
					content: "",
					width: 400,
					autoHide: false,
					overlay: true,
					closeByEsc : true,
					closeIcon : true,
					draggable : { restrict : true}
				}
			);

			dialog.setContent(message);

			dialog.setButtons([,
				new BX.PopupWindowButton({
					text: BX.message("CRM_KANBAN_CONFIRM_Y"),
					className: "popup-window-button-accept",
					events: {
						click: function()
						{
							acceptFunc();
							this.popupWindow.close();
						}
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message("CRM_KANBAN_CONFIRM_N"),
					className: "popup-window-button-cancel",
					events: {
						click: function()
						{
							if (params.grid instanceof BX.CRM.Kanban.Grid)
							{
								params.grid.resetMultiSelectMode();
							}
							this.popupWindow.close();
						}
					}
				})
			]);

			dialog.show();

			return dialog;
		}
	};

})();
