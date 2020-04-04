;(function()
{
	BX.namespace("BX.OpenLines");

	if(typeof(BX.OpenlinesLongRunningProcessState) === "undefined")
	{
		BX.OpenlinesLongRunningProcessState =
			{
				intermediate: 0,
				running: 1,
				completed: 2,
				stoped: 3,
				error: 4
			};
	}

	if(typeof(BX.OpenlinesLongRunningProcessDialog) === "undefined")
	{
		BX.OpenlinesLongRunningProcessDialog = function()
		{
			this._id = "";
			this._settings = {};
			this._serviceUrl = "";
			this._method = "POST";
			this._params = {};
			this._option = {};
			this._dlg = null;
			this._buttons = {};
			this._summary = null;
			this._progressUI = null;
			this._progressbar = null;
			this._isSummaryHtml = false;
			this._isShown = false;
			this._state = BX.OpenlinesLongRunningProcessState.intermediate;
			this._cancelRequest = false;
			this._requestIsRunning = false;
			this._networkErrorCount = 0;
			this._requestHandler = null;
		};
		BX.OpenlinesLongRunningProcessDialog.prototype =
			{
				initialize: function(id, settings)
				{
					this._id = BX.type.isNotEmptyString(id) ? id : "openlines_long_run_proc_" + Math.random().toString().substring(2);
					this._settings = settings ? settings : {};

					this._method = this.getSetting("method", "POST");
					this._serviceUrl = this.getSetting("serviceUrl", "");

					this._action = this.getSetting("action", "");
					if(!BX.type.isNotEmptyString(this._action))
					{
						throw "BX.OpenlinesLongRunningProcess. Could not find action.";
					}

					this._params = this.getSetting("params");
					if(!this._params)
					{
						this._params = {};
					}

					this._isSummaryHtml = !!(this.getSetting("isSummaryHtml", false));

					if(typeof(BX.UI) != "undefined" && typeof(BX.UI.ProgressBar) != "undefined")
					{
						this._progressUI = new BX.UI.ProgressBar({
							statusType: BX.UI.ProgressBar.Status.COUNTER,
							size: BX.UI.ProgressBar.Size.LARGE,
							fill: true
						});
					}

					this._requestHandler = this.getSetting("requestHandler", null);
				},
				getId: function()
				{
					return this._id;
				},
				getSetting: function (name, defaultval)
				{
					return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
				},
				setSetting: function (name, val)
				{
					this._settings[name] = val;
				},
				getMessage: function(name)
				{
					return BX.OpenlinesLongRunningProcessDialog.messages && BX.OpenlinesLongRunningProcessDialog.messages.hasOwnProperty(name) ? BX.OpenlinesLongRunningProcessDialog.messages[name] : "";
				},
				getState: function()
				{
					return this._state;
				},
				getServiceUrl: function()
				{
					return this._serviceUrl;
				},
				getAction: function()
				{
					return this._action;
				},
				setAction: function(action)
				{
					this._action = action;
				},
				getParams: function()
				{
					return this._params;
				},
				show: function()
				{
					if(this._isShown)
					{
						return;
					}

					this._dlg = BX.PopupWindowManager.create(
						this._id.toLowerCase(),
						this._anchor,
						{
							className: "bx-openlines-dialog-wrap bx-openlines-dialog-long-run-proc",
							autoHide: false,
							bindOptions: { forceBindPosition: false },
							buttons: this._prepareDialogButtons(),
							//className: "",
							closeByEsc: false,
							closeIcon: false,
							content: this._prepareDialogContent(),
							draggable: true,
							events: { onPopupClose: BX.delegate(this._onDialogClose, this) },
							offsetLeft: 0,
							offsetTop: 0,
							titleBar: this.getSetting("title", ""),
							overlay: true
						}
					);
					if(!this._dlg.isShown())
					{
						this._dlg.show();
					}
					this._isShown = this._dlg.isShown();
				},
				close: function()
				{
					if(!this._isShown)
					{
						return;
					}

					if(this._dlg)
					{
						this._dlg.close();
					}
					this._isShown = false;
				},
				start: function()
				{
					if(
						this._state === BX.OpenlinesLongRunningProcessState.intermediate ||
						this._state === BX.OpenlinesLongRunningProcessState.stoped ||
						this._state === BX.OpenlinesLongRunningProcessState.completed
					)
					{
						this._startRequest();
					}
				},
				stop: function()
				{
					if(this._state === BX.OpenlinesLongRunningProcessState.running)
					{
						this._stopRequest();
					}
				},
				_prepareDialogContent: function()
				{
					var summary = this.getSetting("summary", "");
					var summaryData = {
						attrs: { className: "bx-openlines-dialog-long-run-proc-summary" }
					};
					if (this._isSummaryHtml)
					{
						summaryData["html"] = summary;
					}
					else
					{
						summaryData["text"] = summary;
					}
					this._summary = BX.create(
						"DIV",
						summaryData
					);

					if(this._progressUI)
					{
						this._progressbar = BX.create(
							"DIV",
							{
								attrs: {className: "bx-openlines-dialog-long-run-proc-progressbar"},
								style: {display: "none"},
								children: [this._progressUI.getContainer()]
							}
						);
					}

					var summaryElements = [this._summary];

					if(this._progressbar)
						summaryElements.push(this._progressbar);

					return BX.create(
						"DIV",
						{
							attrs: { className: "bx-openlines-dialog-long-run-proc-popup" },
							children: summaryElements
						}
					);
				},
				_prepareDialogButtons: function()
				{
					this._buttons = {};

					var startButtonText = this.getMessage("startButton");
					this._buttons["start"] = new BX.PopupWindowButton(
						{
							text: startButtonText !== "" ? startButtonText : "Start",
							className: "popup-window-button-accept",
							events:
								{
									click : BX.delegate(this._handleStartButtonClick, this)
								}
						}
					);

					var stopButtonText = this.getMessage("stopButton");
					this._buttons["stop"] = new BX.PopupWindowButton(
						{
							text: stopButtonText !== "" ? stopButtonText : "Stop",
							className: "popup-window-button-disable",
							events:
								{
									click : BX.delegate(this._handleStopButtonClick, this)
								}
						}
					);

					var closeButtonText = this.getMessage("closeButton");
					this._buttons["close"] = new BX.PopupWindowButtonLink(
						{
							text: closeButtonText !== "" ? closeButtonText : "Close",
							className: "popup-window-button-link-cancel",
							events:
								{
									click : BX.delegate(this._handleCloseButtonClick, this)
								}
						}
					);

					return [ this._buttons["start"], this._buttons["stop"], this._buttons["close"] ];
				},
				_onDialogClose: function(e)
				{
					if(this._dlg)
					{
						this._dlg.destroy();
						this._dlg = null;
					}

					this._setState(BX.OpenlinesLongRunningProcessState.intermediate);
					this._buttons = {};
					this._summary = null;

					this._isShown = false;

					BX.onCustomEvent(this, 'ON_CLOSE', [this]);
				},
				_handleStartButtonClick: function()
				{
					var btn = typeof(this._buttons["start"]) !== "undefined" ? this._buttons["start"] : null;
					if(btn)
					{
						var wasDisabled = BX.data(btn.buttonNode, 'disabled');
						if (wasDisabled === true)
						{
							return;
						}
					}

					this.start();
				},
				_handleStopButtonClick: function()
				{
					var btn = typeof(this._buttons["stop"]) !== "undefined" ? this._buttons["stop"] : null;
					if(btn)
					{
						var wasDisabled = BX.data(btn.buttonNode, 'disabled');
						if (wasDisabled === true)
						{
							return;
						}
					}

					this.stop();
				},
				_handleCloseButtonClick: function()
				{
					if(this._state !== BX.OpenlinesLongRunningProcessState.running)
					{
						this._dlg.close();
					}
				},
				_lockButton: function(bid, lock)
				{
					var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
					if(!btn)
					{
						return;
					}

					if(!!lock)
					{
						BX.removeClass(btn.buttonNode, "popup-window-button-accept");
						BX.addClass(btn.buttonNode, "popup-window-button-disable");
						btn.buttonNode.disabled = true;
						BX.data(btn.buttonNode, 'disabled', true);
					}
					else
					{
						BX.removeClass(btn.buttonNode, "popup-window-button-disable");
						BX.addClass(btn.buttonNode, "popup-window-button-accept");
						btn.buttonNode.disabled = false;
						BX.data(btn.buttonNode, 'disabled', false);
					}
				},
				_showButton: function(bid, show)
				{
					var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
					if(btn)
					{
						btn.buttonNode.style.display = !!show ? "" : "none";
					}
				},
				/**
				 * @param {string} content
				 * @param {bool} isHtml
				 * @private
				 */
				_setSummary: function(content, isHtml)
				{
					isHtml = !!isHtml;
					if(this._summary)
					{
						if (isHtml)
							this._summary.innerHTML = content;
						else
							this._summary.innerHTML = BX.util.htmlspecialchars(content);
					}
				},
				_setProgressBar: function(totalItems, processedItems)
				{
					if(this._progressUI)
					{
						if (BX.type.isNumber(processedItems) && BX.type.isNumber(totalItems) && totalItems > 0)
						{
							BX.show(this._progressbar);

							this._progressUI.setMaxValue(totalItems);
							this._progressUI.update(processedItems);
						}
						else
						{
							BX.hide(this._progressbar);
						}
					}
				},
				_setState: function(state)
				{
					if(this._state === state)
					{
						return;
					}

					this._state = state;
					if(state === BX.OpenlinesLongRunningProcessState.intermediate || state === BX.OpenlinesLongRunningProcessState.stoped)
					{
						this._lockButton("start", false);
						this._lockButton("stop", true);
						this._showButton("close", true);
					}
					else if(state === BX.OpenlinesLongRunningProcessState.running)
					{
						this._lockButton("start", true);
						this._lockButton("stop", false);
						this._showButton("close", false);
					}
					else if(state === BX.OpenlinesLongRunningProcessState.completed || state === BX.OpenlinesLongRunningProcessState.error)
					{
						this._lockButton("start", true);
						this._lockButton("stop", true);
						this._showButton("close", true);
					}

					if(this._progressUI)
					{
						if(state === BX.OpenlinesLongRunningProcessState.completed)
						{
							//this._progressUI.setColor(BX.UI.ProgressBar.Color.SUCCESS);
							BX.hide(this._progressbar);
						}
						if(state === BX.OpenlinesLongRunningProcessState.error)
						{
							this._progressUI.setColor(BX.UI.ProgressBar.Color.DANGER);
						}
					}

					BX.onCustomEvent(this, 'ON_STATE_CHANGE', [this]);
				},
				_startRequest: function()
				{
					if(this._requestIsRunning)
					{
						return;
					}
					this._requestIsRunning = true;

					this._setState(BX.OpenlinesLongRunningProcessState.running);

					var actionData = BX.clone(this._params);

					BX.ajax.runComponentAction
					(
						'bitrix:imopenlines.statistics.detail',
						this._action,
						{
							data: actionData,
							method: this._method
						}
					)
						.then(
							BX.delegate(this._onRequestSuccess, this),
							BX.delegate(this._onRequestFailure, this)
						);

				},
				_stopRequest: function()
				{
					if(this._cancelRequest)
					{
						return;
					}
					this._cancelRequest = true;
					this._requestIsRunning = false;

					this._setState(BX.OpenlinesLongRunningProcessState.stoped);

					var actionData;

					actionData = BX.clone(this._params);

					BX.ajax.runComponentAction
					(
						'bitrix:imopenlines.statistics.detail',
						'cancel',
						{
							data: actionData,
							method: this._method
						}
					)
						.then(
							BX.delegate(this._onRequestSuccess, this),
							BX.delegate(this._onRequestFailure, this)
						);

				},
				/**
				 * @param {Object} result
				 * @private
				 */
				_onRequestSuccess: function(result)
				{
					this._requestIsRunning = false;

					if(!result)
					{
						this._setSummary(this.getMessage("requestError"));
						this._setState(BX.OpenlinesLongRunningProcessState.error);
						return;
					}

					if(BX.type.isArray(result["errors"]) && result["errors"].length > 0)
					{
						var lastError = result["errors"][result["errors"].length - 1];
						this._setState(BX.OpenlinesLongRunningProcessState.error);
						this._setSummary(lastError.message);
						return;
					}

					result = result["data"];

					if(typeof(this._requestHandler) == 'function')
					{
						this._requestHandler.call(this, result);
					}

					this._networkErrorCount = 0;

					var status = BX.type.isNotEmptyString(result["STATUS"]) ? result["STATUS"] : "";
					var summary = BX.type.isNotEmptyString(result["SUMMARY"]) ? result["SUMMARY"] : "";
					var isHtmlSummary = false;
					if (!BX.type.isNotEmptyString(summary))
					{
						summary = BX.type.isNotEmptyString(result["SUMMARY_HTML"]) ? result["SUMMARY_HTML"] : "";
						isHtmlSummary = true;
					}
					if(status === "PROGRESS")
					{
						var processedItems = BX.type.isNumber(result["PROCESSED_ITEMS"]) ? result["PROCESSED_ITEMS"] : 0;
						var totalItems = BX.type.isNumber(result["TOTAL_ITEMS"]) ? result["TOTAL_ITEMS"] : 0;
						if (totalItems > 0)
						{
							this._setProgressBar(totalItems, processedItems);
						}

						if(summary !== "")
						{
							this._setSummary(summary, isHtmlSummary);
						}

						if(this._cancelRequest)
						{
							this._setState(BX.OpenlinesLongRunningProcessState.stoped);
							this._cancelRequest = false;
						}
						else
						{
							var nextAction = BX.type.isNotEmptyString(result["NEXT_ACTION"]) ? result["NEXT_ACTION"] : "";
							if (nextAction !== "")
							{
								this._action = nextAction;
							}

							window.setTimeout(
								BX.delegate(this._startRequest, this),
								200
							);
						}
						return;
					}

					if(status === "NOT_REQUIRED" || status === "COMPLETED")
					{
						this._setState(BX.OpenlinesLongRunningProcessState.completed);
						if(summary !== "")
						{
							this._setSummary(summary, isHtmlSummary);
						}
					}
					else
					{
						this._setSummary(this.getMessage("requestError"));
						this._setState(BX.OpenlinesLongRunningProcessState.error);
					}

					if(this._cancelRequest)
					{
						this._cancelRequest = false;
					}
				},
				/**
				 * @param {Object} result
				 * @private
				 */
				_onRequestFailure: function(result)
				{
					this._requestIsRunning = false;

					if(BX.type.isArray(result["errors"]) && result["errors"].length > 0)
					{
						var lastError = result["errors"][result["errors"].length - 1];

						if (lastError.code === "NETWORK_ERROR")
						{
							this._networkErrorCount ++;
							// Let's give it more chance to complete
							if (this._networkErrorCount <= 2)
							{
								window.setTimeout(
									BX.delegate(this._startRequest, this),
									15000
								);
								return;
							}
						}

						this._setSummary(lastError.message);
					}
					else
					{
						this._setSummary(this.getMessage("requestError"));
					}


					this._setState(BX.OpenlinesLongRunningProcessState.error);
				}
			};
		if(typeof(BX.OpenlinesLongRunningProcessDialog.messages) == "undefined")
		{
			BX.OpenlinesLongRunningProcessDialog.messages = {};
		}
		BX.OpenlinesLongRunningProcessDialog.items = {};
		BX.OpenlinesLongRunningProcessDialog.create = function(id, settings)
		{
			var self = new BX.OpenlinesLongRunningProcessDialog();
			self.initialize(id, settings);
			this.items[self.getId()] = self;
			return self;
		};
	}

})();
