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

	if(typeof(BX.OpenLines.Actions) === "undefined")
	{
		BX.OpenLines.Actions = {
			actionController: 'imopenlines.session.groupactions',
			gridId: null,
			filterId: null,
			transferDialog: null,
			transferItems: null,
			transferInputId: null,
			transferInputName: null,

			init: function (gridId, data)
			{
				this.gridId = gridId;
				this.filterId = data.filterId;
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
				var grid = this.getGridInstance();
				if(grid)
				{
					var forAll = this.getCheckBoxValue("actallrows");
					var selectedIds = grid.getRows().getSelectedIds();

					if(selectedIds.length !== 0 || forAll)
					{
						var fields = {
							idsSession: selectedIds
						};

						if (forAll)
						{
							fields.forAll = 'Y';
							fields.filterId = this.filterId;
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
							if (this.getSelectedItemTransfer())
							{
								if(this.getSelectedItemTransfer().entityId === 'user')
								{
									transferId = this.getSelectedItemTransfer().id;
								}
								else if(this.getSelectedItemTransfer().entityId === 'open-line')
								{
									transferId = 'queue' + this.getSelectedItemTransfer().id;
								}
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

	if (typeof(BX.OpenLines.Configuration) === "undefined")
	{
		BX.OpenLines.Configuration = {
			/** @var {BX.SidePanel.Instance} */
			configurationSlider: null,
			configurationButton: null,
			/** @var {BX.PopupMenuWindow} */
			configurationMenu: null,
			ufFieldListUrl: null,

			init: function(options)
			{
				this.configurationSlider = null;
				this.configurationButton = options.configurationButton;
				this.ufFieldListUrl = options.ufFieldListUrl;

				BX.bind(
					this.configurationButton,
					'click',
					this.showConfigurationMenu.bind(this)
				);
			},

			showConfigurationMenu: function()
			{
				if (!this.configurationMenu)
				{
					var menuItems = [];

					menuItems.push({
						text: BX.message('CONFIGURATION_UF_TITLE'),
						className: 'menu-popup-no-icon',
						onclick: function ()
						{
							this.configurationMenu.popupWindow.close();
							this.openConfigurationSlider();
						}.bind(this),
					});

					this.configurationMenu = new BX.PopupMenuWindow(
						'ol-stat-configuration-control-menu',
						this.configurationButton,
						menuItems,
						{
							closeByEsc: true,
							autoHide: true,
							cacheable: false,
							events: {
								onDestroy: function()
								{
									this.configurationMenu = null;
								}.bind(this)
							}
						}
					);
				}

				this.configurationMenu.toggle();
			},

			openConfigurationSlider: function ()
			{
				this.configurationSlider = BX.SidePanel.Instance;
				this.configurationSlider.open(this.ufFieldListUrl, {
					cacheable: false,
					allowChangeHistory: false,
					requestMethod: 'post',
					requestParams: {
						sessid: BX.bitrix_sessid()
					},
					width: 990
				});
			}
		};
	}

	if (typeof(BX.OpenLines.GridFilter) === "undefined")
	{
		BX.OpenLines.GridFilter = {
			/** @var {BX.Main.Filter} */
			filter: null,
			linkId: null,
			linkButton: null,
			filterId: null,
			filterUrl: null,

			init: function(options)
			{
				this.filterId = options.filterId;
				this.linkId = options.linkId;

				this.filter = BX.Main.filterManager.getById(this.filterId);
				if (!!this.filter && (this.filter instanceof BX.Main.Filter))
				{
					this.linkButton = new BX.UI.Button({
						text: BX.message('FILTER_SHARE_URL'),
						size: BX.UI.Button.Size.MEDIUM,
						color: BX.UI.Button.Color.LIGHT_BORDER,
						icon: BX.UI.Button.Icon.SHARE,
						tag: BX.UI.Button.Tag.SPAN,
						onclick: this.copyLink.bind(this)
					});

					var conteiner = this.filter.getPresetButtonsContainer()
						.querySelector('.main-ui-filter-field-button-inner');

					this.linkButton.renderTo(conteiner);
				}
			},

			copyLink: function()
			{
				var sourceLink = BX(this.linkId);
				if (sourceLink)
				{
					if (window.BX.clipboard.copy(sourceLink.href))
					{
						BX.UI.Notification.Center.notify({
							content: BX.message('FILTER_SHARE_URL_DONE'),
							autoHideDelay: 2000
						});
					}
				}
			}
		};
	}

});

