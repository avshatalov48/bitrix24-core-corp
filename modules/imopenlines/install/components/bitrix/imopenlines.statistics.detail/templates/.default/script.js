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
});

