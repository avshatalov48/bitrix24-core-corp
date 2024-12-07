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
			BX.Runtime.loadExtension('crm.entity-list.panel')
				.then(({ createCallList }) => {
					if (BX.Type.isUndefined(createActivity))
					{
						createActivity = true;
					}

					const gridData = grid.getData();

					/** @see BX.Crm.EntityList.Panel.createCallList */
					return createCallList(gridData.entityTypeInt, grid.getCheckedId(), createActivity);
				}).then(({ errorMessages }) => {
					if (BX.Type.isArrayFilled(errorMessages))
					{
						const error = errorMessages.join('. \n');
						BX.Kanban.Utils.showErrorDialog(error);
					}
				})
			;
		},

		/**
		 * Some simple action.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Object} params
		 * @param {boolean} disableNotify
		 * @returns {Promise}
		 */
		simpleAction: function(grid, params, disableNotify)
		{
			return (new BX.CRM.Kanban.Actions.SimpleAction(grid, params))
				.showNotify(!disableNotify)
				.applyFilterAfterAction(!disableNotify)
				.execute()
			;
		},

		setAssigned: function(grid, assigned)
		{
			const params = {
				action: 'setAssigned',
				ids: grid.getCheckedId(),
				assignedId: assigned.entityId,
				assignedName: assigned.name,
			};

			void this.simpleAction(grid, params, false);
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
		 * @deprecated since crm 24.0.0. Use BX.CRM.Kanban.Actions.DeleteAction
		 *
		 * Delete one item.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @param {Number[] | Number | null} ids
		 * @param {BX.Crm.Kanban.DropZone} drop
		 */
		delete: function(grid, ids, drop)
		{
			// eslint-disable-next-line no-param-reassign
			ids = ids ?? grid.getCheckedId();

			const params = {
				ids,
				showNotify: false,
			};

			(new BX.CRM.Kanban.Actions.DeleteAction(grid, params))
				.setDropZone(drop)
				.execute()
			;
		},

		/**
		 * Delete.
		 * @param {BX.CRM.Kanban.Grid} grid
		 * @returns {void}
		 */
		deleteAll: function(grid)
		{
			this.confirm(
				BX.Loc.getMessage('CRM_KANBAN_PANEL_ACTION_CONFIRM'),
				() => {

					const params = {
						ids: grid.getCheckedId(),
						applyFilterAfterAction: true,
						showNotify: false,
					};

					(new BX.CRM.Kanban.Actions.DeleteAction(grid, params)).execute();
				},
				{
					grid,
				},
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
			const gridData = grid.getData();
			let communications = '';
			const ids = grid.getCheckedId();
			const entityType = gridData.entityType;

			for (let i = 0, c = ids.length; i < c; i++)
			{
				communications +=
					BX.CrmOwnerTypeAbbr.resolve(entityType) +
					"_" +
					ids[i] + ";";
			}
			const taskData = {
				UF_CRM_TASK: communications,
				TITLE: "CRM: ",
				TAGS: "crm",
				ta_sec: 'crm',
				ta_sub: entityType.toLowerCase(),
				ta_el: 'context_menu',
			};

			let taskCreatePath = BX.message("CRM_TASK_CREATION_PATH");
			taskCreatePath = taskCreatePath.replace("#user_id#", BX.message("USER_ID"));
			taskCreatePath = BX.util.add_url_param(
				taskCreatePath,
				taskData
			);

			if (BX.SidePanel)
			{
				BX.SidePanel.Instance.open(taskCreatePath);
			}
			else
			{
				window.top.location.href = taskCreatePath;
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
		},
	};
})();
