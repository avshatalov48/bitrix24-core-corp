BX.ready(function(){
	BX.PULL.extendWatch('IMOL_STATISTICS');

	BX.addCustomEvent("onPullEvent-imopenlines", function(command,params) {
		if (command == 'voteHead')
		{
			if(typeof params.voteValue !== 'undefined')
			{
				var placeholderVote = BX("ol-vote-head-placeholder-"+params.sessionId);
				if (placeholderVote)
				{
					BX.cleanNode(placeholderVote);
					placeholderVote.appendChild(
						BX.MessengerCommon.linesVoteHeadNodes(params.sessionId, params.voteValue, true)
					);
				}
			}

			if(typeof params.commentValue !== 'undefined')
			{
				var placeholderComment = BX("ol-comment-head-placeholder-"+params.sessionId);
				if (placeholderComment)
				{
					BX.cleanNode(placeholderComment);
					placeholderComment.appendChild(
						BX.MessengerCommon.linesCommentHeadNodes(params.sessionId, params.commentValue, true, "statistics")
					);
				}
			}
		}
	});

	BX.namespace("BX.OpenLines");

	if(typeof BX.OpenLines.ExportManager === "undefined")
	{
		BX.OpenLines.ExportManager = function()
		{
			this._id = "";
			this._settings = {};
			this._processDialog = null;
			this._siteId = "";
			this._sToken = "";
			this._cToken = "";
			this._token = "";
		};

		BX.OpenLines.ExportManager.prototype =
			{
				initialize: function(id, settings)
				{
					this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
					this._settings = settings ? settings : {};

					this._siteId = this.getSetting("siteId", "");
					if (!BX.type.isNotEmptyString(this._siteId))
						throw "BX.OpenLines.ExportManager: parameter 'siteId' is not found.";

					this._sToken = this.getSetting("sToken", "");
					if (!BX.type.isNotEmptyString(this._sToken))
						throw "BX.OpenLines.ExportManager: parameter 'sToken' is not found.";
				},
				getId: function()
				{
					return this._id;
				},
				getSetting: function(name, defaultval)
				{
					return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
				},
				callAction: function(action)
				{
					this._processDialog.setAction(action);
					this._processDialog.start();
				},
				startExport: function (exportType) {
					if (!BX.type.isNotEmptyString(exportType))
						throw "BX.OpenLines.ExportManager: parameter 'exportType' has invalid value.";

					this._cToken = "c" + Date.now();

					this._token = this._sToken + this._cToken;

					var params = {
						"SITE_ID": this._siteId,
						"PROCESS_TOKEN": this._token,
						"EXPORT_TYPE": exportType,
						"COMPONENT_NAME": 'bitrix:imopenlines.statistics.detail',
						"signedParameters": this.getSetting("componentParams", {})
					};

					var exportTypeMsgSuffix = exportType.charAt(0).toUpperCase() + exportType.slice(1);

					this._processDialog = BX.OpenlinesLongRunningProcessDialog.create(
						this._id + "_LrpDlg",
						{
							componentName: 'bitrix:imopenlines.statistics.detail',
							action: "dispatcher",
							params: params,
							title: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgTitle"),
							summary: this.getMessage("stExport" + exportTypeMsgSuffix + "DlgSummary"),
							isSummaryHtml: false,
							requestHandler: function(result){
								if(BX.type.isNotEmptyString(result["STATUS"]) && result["STATUS"]=="COMPLETED")
								{
									if(BX.type.isNotEmptyString(result["DOWNLOAD_LINK"]))
									{
										result["SUMMARY_HTML"] +=
											'<br><br>' +
											'<a href="' + result["DOWNLOAD_LINK"] + '" class="ui-btn ui-btn-sm ui-btn-success ui-btn-icon-download">' +
											result['DOWNLOAD_LINK_NAME'] + '</a>' +
											'<button onclick="BX.OpenLines.ExportManager.currentInstance().callAction(\'clear\')" class="ui-btn ui-btn-sm ui-btn-default ui-btn-icon-remove">' +
											result['CLEAR_LINK_NAME'] + '</button>';

									}
								}
							}
						}
					);

					this._processDialog.show();
				},
				destroy: function ()
				{
					this._id = "";
					this._settings = {};
					this._processDialog = null;
					this._siteId = "";
					this._sToken = "";
					this._cToken = "";
					this._token = "";
				}
			};

		BX.OpenLines.ExportManager.prototype.getMessage = function(name)
		{
			var message = name;
			var messages = this.getSetting("messages", null);
			if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
			{
				message =  messages[name];
			}
			else
			{
				messages = BX.OpenLines.ExportManager.messages;
				if (messages !== null && typeof(messages) === "object" && messages.hasOwnProperty(name))
				{
					message =  messages[name];
				}
			}
			return message;
		};

		if(typeof(BX.OpenLines.ExportManager.messages) === "undefined")
		{
			BX.OpenLines.ExportManager.messages = {};
		}

		if(typeof(BX.OpenLines.ExportManager.items) === "undefined")
		{
			BX.OpenLines.ExportManager.items = {};
		}

		BX.OpenLines.ExportManager.create = function(id, settings)
		{
			var self = new BX.OpenLines.ExportManager();
			self.initialize(id, settings);
			BX.OpenLines.ExportManager.items[id] = self;
			BX.OpenLines.ExportManager.currentId = id;
			return self;
		};

		BX.OpenLines.ExportManager.delete = function(id)
		{
			if (BX.OpenLines.ExportManager.items.hasOwnProperty(id))
			{
				BX.OpenLines.ExportManager.items[id].destroy();
				delete BX.OpenLines.ExportManager.items[id];
			}
		};

		BX.OpenLines.ExportManager.currentId = '';
		BX.OpenLines.ExportManager.currentInstance = function()
		{
			return BX.OpenLines.ExportManager.items[BX.OpenLines.ExportManager.currentId];
		};
	}

	if(typeof(BX.OpenLines.Actions) === "undefined")
	{
		BX.OpenLines.Actions = {
			actionController: 'imopenlines.session.groupactions',
			gridId: null,
			transferDialog: null,
			transferItems: null,
			transferInputId: null,
			transferInputName: null,

			init: function (gridId, data)
			{
				this.gridId = gridId;
				this.transferItems = data.transfer.items;
				this.transferInputId = data.transfer.inputId;
				this.transferInputName = data.transfer.inputName;
			},

			initTransferDialogSelector: function ()
			{
				if(!this.transferDialog)
				{
					this.transferDialog = new BX.UI.EntitySelector.Dialog({
						targetNode: BX(this.transferInputId),
						context: this.getGridInstance(),
						multiple: false,
						entities: [
							{
								id: 'user',
								dynamicLoad: true,
								dynamicSearch: true,
								options: {
									intranetUsersOnly: true,
									inviteEmployeeLink: false
								}
							},
							{
								id: 'department',
								dynamicLoad: true,
								dynamicSearch: true,
								options: {
									inviteEmployeeLink: false,
									selectMode: 'users',
								}
							}
						],
						tabs: [
							{ id: 'open-lines', title: BX.message('OL_COMPONENT_SESSION_GROUP_ACTION_OPEN_LINES_TITLE')},
						],
						items: this.transferItems,
						events: {
							'Item:onSelect': BX.proxy(function() {
								var textTitle = '';
								var item = this.getSelectedItemTransfer();
								if(item)
								{
									textTitle = item.title.text;
								}
								this.setValueTransferInput(textTitle);
							}, this),
							'Item:onDeselect': BX.proxy(function() {
								this.setValueTransferInput('');
							}, this),
						},
					});
				}

				BX(this.transferInputId).addEventListener('click', function() {
					BX.OpenLines.Actions.transferDialog.show();
				});

				BX.lastChild(BX(this.transferInputId)).addEventListener('input', function() {
					BX.OpenLines.Actions.transferDialog.show();
					BX.OpenLines.Actions.transferDialog.search(this.value);
				});
			},

			destroyTransferDialogSelector: function ()
			{
				if(!!this.transferDialog)
				{
					BX(this.transferInputId).removeEventListener('click', function() {
						BX.OpenLines.Actions.transferDialog.show();
					});

					BX(this.transferInputId).removeEventListener('input', function() {
						BX.OpenLines.Actions.transferDialog.show();
						BX.OpenLines.Actions.transferDialog.search(this.value);
					});
					this.transferDialog.destroy();
					delete this.transferDialog;
				}
			},

			setValueTransferInput: function(value)
			{
				if(!value)
				{
					value = ''
				}
				var input = BX.findChild(BX(this.transferInputId), {
						"tag" : "input",
						"name" : this.transferInputName,
					}
				);

				if(!!input)
				{
					input.value = value;
				}
			},

			getSelectedItemTransfer: function()
			{
				var result = null;

				if(!!this.transferDialog)
				{
					this.transferDialog.getSelectedItems().forEach(BX.proxy(function (entity)
					{
						result = entity;
					}, this));
				}

				return result;
			},

			getGridInstance: function()
			{
				var gridId = this.gridId;
				if(gridId !== null)
				{
					var gridInfo = BX.Main.gridManager.getById(gridId);
					return (BX.type.isPlainObject(gridInfo) && gridInfo["instance"] !== "undefined" ? gridInfo["instance"] : null);
				}

				return null;
			},
			refreshGrid: function()
			{
				window.requestAnimationFrame(
					function()
					{
						var grid = BX.Main.gridManager.getInstanceById(this.gridId);
						if(grid)
						{
							grid.reload();
						}
					}.bind(this)
				);

				//BX.OpenLines.Actions.getGridInstance().refreshGrid('POST');
			},
			getPanelControl: function(controlId)
			{
				return BX(controlId + "_" + this.gridId + "_control");
			},
			getCheckBoxValue: function(controlId)
			{
				var control = this.getControl(controlId);
				return control && control.checked;
			},
			getControl: function(controlId)
			{
				return BX(controlId + "_" + this.gridId);
			},

			applyAction: function(actionName)
			{
				var errors = null;
				var grid = this.getGridInstance();
				if(grid)
				{
					var forAll = this.getCheckBoxValue("actallrows");
					var selectedIds = grid.getRows().getSelectedIds();

					if(selectedIds.length !== 0 || forAll)
					{
						var fields = {
							idsSession: selectedIds
							//idsChat: []
						};

						if (forAll)
						{
							//fields.idsChat = this.getChatSessionForAll();
							fields.forAll = 'Y';
						}

						if (actionName === "spam")
						{
							this.runCloseSpam(fields);
						}
						else if (actionName === "close")
						{
							this.runClose(fields);
						}
						else if (actionName === "transfer")
						{
							var transferId = null;

							if(this.getSelectedItemTransfer().entityId === 'user')
							{
								transferId = this.getSelectedItemTransfer().id;
							}
							else if(this.getSelectedItemTransfer().entityId === 'open-line')
							{
								transferId = 'queue' + this.getSelectedItemTransfer().id;
							}

							if(transferId !== null)
							{
								fields.transferId = transferId;

								this.runTransfer(fields);
							}

							if(!!this.transferInputId)
							{
								this.destroyTransferDialogSelector();
							}
						}

						//this.refreshGrid();
					}
				}
			},
			confirmGroupAction: function (gridId)
			{
				if(this.gridId === null)
				{
					this.gridId = gridId;
				}

				this.applyAction(
					BX.data(this.getPanelControl("action_button"), 'value')
				);
			},

			runAction: function (action, data)
			{
				var result = {
					data: null,
					errors: null
				};

				BX.ajax.runAction(action, {
					data: data
				}).then(function (response) {
					result.data = response.data;
				}, function (response) {
					result.errors = response.errors;
				});

				return result;
			},

			runStepProcess: function (action, data, title)
			{
				var progress = new BX.UI.StepProcessing.Process({
					'id': 'OpenLinesGroupActions',
					'controller': this.actionController,
					'messages': {
						'DialogTitle': BX.message('LIST_GROUP_ACTION_TITLE'),
						'DialogSummary': BX.message('LIST_GROUP_ACTION_SUMMARY')
					},
					'showButtons': {
						'start': true,
						'stop': true,
						'close': true
					},
					'dialogMaxWidth': 600
				});

				// for all in grid
				if ('forAll' in data)
				{
					delete data.idsSession;
					delete data.forAll;

					// add spetial step for determine total sessions
					progress.addQueueAction({
						'title': title,
						'action': 'getdialog',
						'handlers': {
							'StepCompleted': function (state, result)
							{
								/** @type {BX.UI.StepProcessing.Process} this */
								if (state === BX.UI.StepProcessing.ProcessResultStatus.completed)
								{
									var fields = this.getParam('fields') || [];
									// add all ids in request
									fields.idsChat = [];
									fields.idsSession = [];
									if(result.sessions)
									{
										result.sessions.forEach(function(item) {
											fields.idsChat.push(item.chatId);
											fields.idsSession.push(item.sessionId);
										})
									}
									// add total in request
									if(result.TOTAL_ITEMS)
									{
										fields.totalItems = parseInt(result.TOTAL_ITEMS);
									}
									this.setParam('fields', fields);
								}
							}
						}
					});
				}

				progress
					// on finish
					.setHandler(
						BX.UI.StepProcessing.ProcessCallback.StateChanged,
						function (state, result)
						{
							/** @type {BX.UI.StepProcessing.Process} this */
							if (state === BX.UI.StepProcessing.ProcessResultStatus.completed)
							{
								BX.OpenLines.Actions.refreshGrid();
								this.closeDialog();
							}
						}
					)
					// on cancel
					.setHandler(
						BX.UI.StepProcessing.ProcessCallback.RequestStop,
						function (actionData)
						{
							/** @type {BX.UI.StepProcessing.Process} this */
							setTimeout(
								BX.delegate(
									function(){
										BX.OpenLines.Actions.refreshGrid();
										this.closeDialog();
									},
									this
								),
								2000
							);
						}
					)
					// payload action step
					.addQueueAction({
						'title': title,
						'action': action,
						'handlers': {
							// keep total and processed in request
							'StepCompleted': function (state, result)
							{
								/** @type {BX.UI.StepProcessing.Process} this */
								if (state === BX.UI.StepProcessing.ProcessResultStatus.progress)
								{
									var fields = this.getParam('fields') || [];
									if (result.TOTAL_ITEMS)
									{
										fields.totalItems = parseInt(result.TOTAL_ITEMS);
									}
									if (result.PROCESSED_ITEMS)
									{
										fields.processedItems = parseInt(result.PROCESSED_ITEMS);
									}
									this.setParam('fields', fields);
								}
							}
						}
					})
					// params
					.setParam('fields', data)
					// dialog
					.showDialog()
					//.start()
				;
				return progress;
			},

			runClose: function (fields)
			{
				this.runStepProcess('close', fields, BX.message('LIST_GROUP_ACTION_CLOSE'));
			},
			runCloseSpam: function (fields)
			{
				this.runStepProcess('closespam', fields, BX.message('LIST_GROUP_ACTION_SPAM'));
			},
			runTransfer: function (fields)
			{
				//return this.runAction('imopenlines.session.groupactions.transfer', {fields: fields});
				this.runStepProcess('transfer', fields, BX.message('LIST_GROUP_ACTION_TRANSFER'));
			},

			close: function(chatId)
			{
				this.runAction(this.actionController+'.close', {fields: {idsChat: [chatId]}});
				this.refreshGrid();
			},
			closeSpam: function(chatId)
			{
				this.runAction(this.actionController+'.closespam', {fields: {idsChat: [chatId]}});
				this.refreshGrid();
			},

			getChatSessionForAll: function()
			{
				var result = [];
				BX.ajax.runAction(this.actionController+'.getdialog', {
					data: {}
				}).then(function (response) {
					if(response.data)
					{
						response.data.forEach(function(item) {
							result.push(item.chatId);
						})
					}
					result = response.data;
				}, function (response) {

				});

				return result;
			}
		}
	}

	//TODO: del
	if(typeof(BX.OpenLines.GridActions) === "undefined")
	{
		BX.OpenLines.GridActions = {
			gridId: null,
			groupSelector: null,
			registeredTimerNodes: {},
			defaultPresetId: '',

			initPopupBaloon: function(mode, searchField, groupIdField) {

				this.groupSelector = null;

				BX.bind(BX(searchField + '_control'), 'click', BX.delegate(function(){

					if (!this.groupSelector)
					{
						/*this.groupSelector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
							scope: BX(searchField + '_control'),
							id: 'group-selector-' + this.gridId,
							mode: mode,
							query: false,
							useSearch: true,
							useAdd: false,
							parent: this,
							popupOffsetTop: 5,
							popupOffsetLeft: 40
						});*/
						this.groupSelector.bindEvent('item-selected', BX.delegate(function(data){
							BX(searchField + '_control').value = BX.util.htmlspecialcharsback(data.nameFormatted) || '';
							BX(groupIdField + '_control').value = data.id || '';
							this.groupSelector.close();
						}, this));
					}

					this.groupSelector.open();

				}, this));
			},
			applyAction: function(actionName)
			{
				var grid = this.getGrid();
				if(grid)
				{
					var forAll = this.getCheckBoxValue("actallrows");
					var selectedIds = grid.getRows().getSelectedIds();
					console.log(selectedIds);
					if(selectedIds.length !== 0 || forAll)
					{
						if(actionName === "close") {
							this.refreshGrid();
							/*var deletionManager = BX.Crm.BatchDeletionManager.getItem(this.getGridId());
							if(deletionManager && !deletionManager.isRunning())
							{
								if(!forAll)
								{
									deletionManager.setEntityIds(selectedIds);
								}
								else
								{
									deletionManager.resetEntityIds();
								}

								deletionManager.execute();

								if(!this._batchDeletionCompleteHandler)
								{
									this._batchDeletionCompleteHandler = BX.delegate(this.onDeletionComplete, this);
									BX.addCustomEvent(
										window,
										"BX.Crm.BatchDeletionManager:onProcessComplete",
										this._batchDeletionCompleteHandler
									);
								}
							}*/
						}
						else if(actionName === "spam"){

						}
					}
				}
			},
			confirmGroupAction: function (gridId) {
				if(this.gridId === null)
				{
					this.gridId = gridId;
				}

				this.applyAction(
					BX.data(this.getPanelControl("action_button"), 'value')
				);

				/*//TODO: Temporarily class BX.Tasks.confirm. Replace with your own.
				BX.Tasks.confirm(BX.message('OL_COMPONENT_SESSION_CONFIRM_GROUP_ACTION')).then(function () {
					BX.Main.gridManager.getById(gridId).instance.sendSelected();
					//TODO: Its counterUpdate handler.
					counterUpdate();
				}.bind(this));*/
			},
			getPanelControl: function(controlId)
			{
				return BX(controlId + "_" + this.gridId + "_control");
			},
			getGrid: function()
			{
				var gridId = this.gridId;
				if(gridId !== null)
				{
					var gridInfo = BX.Main.gridManager.getById(gridId);
					return (BX.type.isPlainObject(gridInfo) && gridInfo["instance"] !== "undefined" ? gridInfo["instance"] : null);
				}

				return null;
			},
			getCheckBoxValue: function(controlId)
			{
				var control = this.getControl(controlId);
				return control && control.checked;
			},
			getControl: function(controlId)
			{
				return BX(controlId + "_" + this.gridId);
			},
			refreshGrid: function()
			{
				window.requestAnimationFrame(
					function()
					{
						var grid = BX.Main.gridManager.getInstanceById(this.gridId);
						if(grid)
						{
							grid.reload();
						}
					}.bind(this)
				);
			}
		};
	}
});

