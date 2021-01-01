(function() {

	"use strict";

	BX.namespace("BX.Tasks.Kanban");

	/**
	 * Multiple actions for CRM Kanban.
	 * @constructor
	 */
	BX.Tasks.Kanban.Actions = //function()
	{
		/**
		 * Notify by message code (if exists).
		 * @param {Strings} code Message code.
		 * @param {Object params Some params.
		 * @return {void}
		 */
		notifySimpleAction: function(code, params)
		{
			code = "TASKS_KANBAN_PANEL_" + code;
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
		 * Some simple action.
		 * @param {BX.Tasks.Kanban.Grid} grid
		 * @param {Object} params
		 * @param {boolean} disableNotify
		 * @returns {void}
		 */
		simpleAction: function(grid, params, disableNotify)
		{
			params["groupAction"] = "Y";
			params["taskId"] = this.getMapKeys(grid.getSelectedItems());
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
						this.notifySimpleAction(
							params.action.toUpperCase(),
							params
						);
					}
					else if (data)
					{
						BX.Kanban.Utils.showErrorDialog(data.error, data.fatal);
					}
				}.bind(this),
				function(error)
				{
					BX.Kanban.Utils.showErrorDialog("Error: " + error, true);
				}.bind(this)
			);
		},

		/**
		 * Returns keys array for the map.
		 * @param {Map} map
		 * @return {Array}
		 */
		getMapKeys: function(map)
		{
			var result = [];
			map.forEach(function(value, key) {
				result.push(key);
			});
			return result;
		},

		/**
		 * Changes task deadline.
		 * @param {BX.Tasks.Kanban.Grid} grid
		 * @param {Element} node Node element.
		 * @returns {void}
		 */
		deadline: function(grid, node)
		{
			BX.calendar({
				node: node,//BX.proxy_context,
				bTime: true,
				bCompatibility: false,
				callback: function(data)
				{
					var format = BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME"));
					this.simpleAction(grid, {
							action: "deadlineTask",
							deadline: BX.date.format(format, data)
						}
					);
				}.bind(this)
			});
		},

		/**
		 * Changes task deadline.
		 * @param {BX.Tasks.Kanban.Grid} grid
		 * @param {Element} node Node element.
		 * @param {string} action Action type.
		 * @param {string} mode Work mode.
		 * @returns {void}
		 */
		member: function(grid, node, action, mode)
		{
			if (typeof mode === "undefined")
			{
				mode = "user";
			}
			var selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
				scope: node,
				id: action,
				mode: mode,
				query: false,
				useSearch: true,
				useAdd: false,
				parent: this,
				popupOffsetTop: 5,
				popupOffsetLeft: 40
			});
			selector.bindEvent("item-selected", function(data)
			{
				var sendData = {
					action: action
				};
				if (mode === "group")
				{
					sendData["groupId"] = data["id"];
				}
				else if (mode === "user")
				{
					sendData["userId"] = data["id"];
				}
				sendData["entityName"] = data["nameFormatted"];
				this.simpleAction(grid, sendData);
				selector.close();
			}.bind(this));
			selector.open();
		}
	};

})();
