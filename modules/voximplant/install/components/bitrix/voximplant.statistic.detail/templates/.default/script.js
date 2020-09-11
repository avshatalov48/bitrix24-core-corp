;(function()
{
	var instance = null;

	BX.VoximplantStatisticDetail = function(params)
	{
		instance = this;

		this.gridContainer = params.gridContainer;
		this.exportButton = params.exportButton;
		this.exportAllowed = params.exportAllowed;
		this.exportType = params.exportType;

		this.siteId = params.exportParams.siteId;
		this.componentName = params.exportParams.componentName;
		this.sToken = params.exportParams.sToken;
		this.cToken = "";
		this.token = "";

		this.exporting = false;
		this.exportPopup = null;
		this.exportProgressBar = null;
		this.exportExecuteButton = null;
		this.exportStopButton = null;
		this.exportInfo = null;

		var grid = BX.Main.gridManager.getById(this.gridContainer.id);
		this.grid = grid.instance;

		this.progressBar = null;
		this.progressBarLine = null;
		this.progressBarText = null;

		this.init();
	};
	Object.defineProperty(BX.VoximplantStatisticDetail, "Instance", {
		get: function()
		{
			return instance
		}
	});

	BX.VoximplantStatisticDetail.prototype.init = function()
	{
		this.exportButton.addEventListener('click', this.createExportPopup.bind(this));

		this.initPlayer();
	};

	BX.VoximplantStatisticDetail.prototype.initPlayer = function()
	{
		var player = new BX.Fileman.Player('vi_records_player', {
			'width': 10,
			'height': 10,
			'onInit': function(player)
			{
				player.vjsPlayer.on('pause', function()
				{
					var buttons = BX.findChildrenByClassName(this.gridContainer, 'vi-player-pause');
					for(var i in buttons)
					{
						BX.removeClass(buttons[i], 'vi-player-pause');
					}
				}.bind(this));
			}
		});
		player.isAudio = true;
		var playerNode = player.createElement();
		playerNode.style.display = 'none';
		BX.insertAfter(playerNode, this.gridContainer);
		player.init();
		BX.bindDelegate(this.gridContainer, 'click', {className: 'vi-player-button'}, function(event)
		{
			var buttons = BX.findChildrenByClassName(this.gridContainer, 'vi-player-pause');
			for(var i in buttons)
			{
				BX.removeClass(buttons[i], 'vi-player-pause');
			}
			var target = event.srcElement || event.target;
			var source = target.getAttribute('data-bx-record');
			if(source)
			{
				source = {src: source, type: 'audio/mp3'};
				var currentSource = player.getSource();
				if(currentSource && currentSource.indexOf(source.src) !== -1 && player.isPlaying())
				{
					player.pause();
				}
				else
				{
					player.setSource(source);
					player.play();
					BX.addClass(target, 'vi-player-pause');
				}
				event.preventDefault();
				return false;
			}
		}.bind(this));
	};

	BX.VoximplantStatisticDetail.prototype.onShowTotalClick = function(event)
	{
		var placeholder = BX.create("span", {
			props: {className: "main-grid-panel-content-text"}
		});
		var button = event.currentTarget;

		this.showTotal(placeholder).then(function(count)
		{
			button.parentElement.appendChild(placeholder);
			placeholder.innerText = count;
			BX.cleanNode(button, true);
		});
		event.stopPropagation();
		event.preventDefault();
	};

	BX.VoximplantStatisticDetail.prototype.showTotal = function()
	{
		return new Promise(function (resolve)
		{
			BX.ajax.runComponentAction("bitrix:voximplant.statistic.detail", "getRowsCount", {
				mode: "class",
			}).then(function(response)
			{
				var data = response.data;
				resolve(data.rowsCount);
			}).catch(function(response)
			{
				if(response.errors)
				{
					response.errors.forEach(function(error)
					{
						console.error(error.message);
					})
				}
			});
		});
	};

	BX.VoximplantStatisticDetail.prototype.startExport = function()
	{
		if (!this.exportExecuteButton.isActive())
		{
			return
		}

		this.exportExecuteButton.removeClass("ui-btn-success");
		this.exportExecuteButton.addClass("ui-btn-disabled ui-btn-wait");
		this.exportExecuteButton.setActive(false);

		this.exportStopButton.removeClass("ui-btn-disabled");
		this.exportStopButton.addClass("ui-btn ui-btn-danger-light");
		this.exportStopButton.setActive(true);

		this.exportProgressBar = new BX.UI.ProgressBar({
			statusType: BX.UI.ProgressBar.Status.PERCENT,
			size: BX.UI.ProgressBar.Size.LARGE,
			fill: true
		});

		this.exportInfo = BX.create("div", {
			props: { className: "tel-stat-export-info" }
		});

		var progressBarContainer = BX.create("div", {
			props: { className: "tel-stat-export-progress-container" },
			state: BX.UI.Button.State.DISABLED,
			children: [
				this.exportInfo,
				this.exportProgressBar.getContainer()
			]
		});

		this.exportExecuteButton.removeClass("ui-btn-wait");
		this.exportPopup.setContent(progressBarContainer);

		this.cToken = "c" + Date.now();
		this.token = this.sToken + this.cToken;
		this.exporting = true;

		this.nextExportStep();
	};

	BX.VoximplantStatisticDetail.prototype.nextExportStep = function()
	{
		if (!this.exporting)
		{
			return;
		}

		var request = BX.ajax.runAction("bitrix:voximplant.export.dispatcher", {
			data: {
				"SITE_ID": this.siteId,
				"PROCESS_TOKEN": this.token,
				"EXPORT_TYPE": this.exportType,
				"COMPONENT_NAME": this.componentName
			}
		});

		request.then(function(response)
		{
			var result = response.data;

			if (result["STATUS"] === "PROGRESS")
			{
				this.setExportProgress(result["PROCESSED_ITEMS"], result["TOTAL_ITEMS"], result["SUMMARY_HTML"]);
				this.nextExportStep();
			}
			else if (result["STATUS"] === "COMPLETED")
			{
				this.exportFinish(result);
			}
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors);

			this.setExportPopupMessage({ exportInfo: BX.message('TEL_STAT_EXPORT_ERROR') });
		}.bind(this));
	};

	BX.VoximplantStatisticDetail.prototype.exportFinish = function(exportResult)
	{
		var exportInfo = exportResult["SUMMARY_HTML"];
		var totalItems = exportResult["TOTAL_ITEMS"];
		var processedItems = exportResult["PROCESSED_ITEMS"];

		var params;

		setTimeout(function()
		{
			this.setExportProgress(processedItems, totalItems, exportInfo);

			if  (exportResult["DOWNLOAD_LINK"] !== undefined)
			{
				var downloadFileLink = exportResult["DOWNLOAD_LINK"];
				var downloadFileLinkTitle = exportResult["DOWNLOAD_LINK_NAME"];
				var removeFileLinkTitle = exportResult["CLEAR_LINK_NAME"];

				params = {
					exportInfo: exportInfo,
					downloadFileLinkTitle: downloadFileLinkTitle,
					removeFileLinkTitle: removeFileLinkTitle,
					downloadFileLink: downloadFileLink
				};

				this.createExportFileActions(params);
			}
			else
			{
				this.setExportPopupMessage({ exportInfo: exportInfo });
			}
		}.bind(this), 300);
	};

	BX.VoximplantStatisticDetail.prototype.stopExport = function()
	{
		if (!this.exportStopButton.isActive())
		{
			return;
		}

		this.exporting = false;

		var request = BX.ajax.runAction("bitrix:voximplant.export.cancel", {
			data: {
				"SITE_ID": this.siteId,
				"PROCESS_TOKEN": this.token,
				"EXPORT_TYPE": this.exportType,
				"COMPONENT_NAME": this.componentName
			}
		});

		request.then(function(response)
		{
			this.setExportPopupMessage({ exportInfo: response.data["SUMMARY_HTML"] });
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors);

			this.setExportPopupMessage({ exportInfo: BX.message('TEL_STAT_EXPORT_ERROR') });
		}.bind(this));
	};

	BX.VoximplantStatisticDetail.prototype.createExportPopup = function()
	{
		if(!this.exportAllowed)
		{
			BX.UI.InfoHelper.show('limit_contact_center_telephony_excel_export');
			return;
		}

		this.exportButton.classList.add("ui-btn-wait");
		this.exportButton.classList.add("ui-btn-disabled");

		this.exportPopup = new BX.PopupWindow("tel-stat-export-popup", null, {
			zIndex: 10000,
			titleBar: BX.message("TEL_STAT_EXPORT_DETAIL_TO_EXCEL"),
			content: BX.create("div", {
				props: { className: "tel-stat-export-description" },
				text: BX.message("TEL_STAT_EXPORT_DETAIL_TO_EXCEL_DESCRIPTION") + "\n\n"
					+ BX.message("TEL_STAT_EXPORT_DETAIL_TO_EXCEL_LONG_PROCESS")
			}),
			closeByEsc: false,
			closeIcon: {
				opacity: 1
			},
			overlay: {
				backgroundColor: "black",
				opacity: 500
			},
			buttons: [
				this.exportExecuteButton = new BX.UI.Button({
					text: BX.message("TEL_STAT_ACTION_EXECUTE"),
					id: "export-execute-btn",
					className: "ui-btn ui-btn-success",
					state: BX.UI.Button.State.ACTIVE,
					events: {
						click: function()
						{
							this.startExport();
						}.bind(this)
					}
				}),
				this.exportStopButton = new BX.UI.Button({
					text: BX.message("TEL_STAT_ACTION_STOP"),
					id: "export-stop-btn",
					className: "ui-btn ui-btn-disabled",
					state: BX.UI.Button.State.DISABLED,
					events: {
						click: function()
						{
							this.stopExport();
						}.bind(this)
					}
				})
			],
			events: {
				onPopupClose: function()
				{
					this.closeExportPopup();
				}.bind(this)
			}
		});

		this.exportPopup.show();
	};

	BX.VoximplantStatisticDetail.prototype.closeExportPopup = function()
	{
		if (!this.exporting)
		{
			this.exportButton.classList.remove("ui-btn-wait");
			this.exportButton.classList.remove("ui-btn-disabled");
			this.exportPopup.destroy();

			return;
		}

		var request = BX.ajax.runAction("bitrix:voximplant.export.cancel", {
			data: {
				"SITE_ID": this.siteId,
				"PROCESS_TOKEN": this.token,
				"EXPORT_TYPE": this.exportType,
				"COMPONENT_NAME": this.componentName
			}
		});

		request.then(function()
		{
			this.exportButton.classList.remove("ui-btn-wait");
			this.exportButton.classList.remove("ui-btn-disabled");
			this.exportPopup.destroy();
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors);

			this.setExportPopupMessage({ exportInfo: BX.message('TEL_STAT_EXPORT_ERROR') });
		}.bind(this));
	};

	BX.VoximplantStatisticDetail.prototype.setExportProgress = function(current, total, exportInfo)
	{
		this.exportInfo.innerHTML = exportInfo;

		this.exportProgressBar.setMaxValue(total);
		this.exportProgressBar.update(current);
	};

	BX.VoximplantStatisticDetail.prototype.createExportFileActions = function(params)
	{
		var exportInfo = params.exportInfo;
		var downloadFileLinkTitle = params.downloadFileLinkTitle;
		var removeFileLinkTitle = params.removeFileLinkTitle;
		var downloadFileLink = params.downloadFileLink;

		var exportResultContainer = BX.create("div", {
			props: { className: "tel-stat-export-result-container" },
			children: [
				BX.create("div", {
					props: { className: "tel-stat-export-info" },
					html: exportInfo
				}),
				new BX.UI.Button({
					text: downloadFileLinkTitle,
					id: "export-execute-btn",
					className: "ui-btn ui-btn-success",
					icon: BX.UI.Button.Icon.DOWNLOAD,
					onclick: function()
					{
						location.href = downloadFileLink;
					}
				}).getContainer(),
				new BX.UI.Button({
					text: removeFileLinkTitle,
					id: "export-execute-btn",
					className: "ui-btn",
					icon: BX.UI.Button.Icon.REMOVE,
					onclick: function()
					{
						this.removeExportFile();
					}.bind(this)
				}).getContainer()
			]
		});

		this.afterExportFinish(exportResultContainer);
	};

	BX.VoximplantStatisticDetail.prototype.setExportPopupMessage = function(params)
	{
		var exportInfo = params.exportInfo;

		var exportResultContainer = BX.create("div", {
			props: { className: "tel-stat-export-result-container" },
			children: [
				BX.create("div", {
					props: { className: "tel-stat-export-message" },
					html: exportInfo
				})
			]
		});

		this.afterExportFinish(exportResultContainer);
	};

	BX.VoximplantStatisticDetail.prototype.afterExportFinish = function(exportResultContainer)
	{
		this.exportExecuteButton.addClass("ui-btn-disabled");
		this.exportExecuteButton.setActive(false);

		this.exportStopButton.setActive(false);
		this.exportStopButton.removeClass("ui-btn-danger-light");
		this.exportStopButton.addClass("ui-btn-disabled");

		this.exportPopup.setContent(exportResultContainer);
	};

	BX.VoximplantStatisticDetail.prototype.removeExportFile = function()
	{
		var request = BX.ajax.runAction("bitrix:voximplant.export.clear", {
			data: {
				"SITE_ID": this.siteId,
				"PROCESS_TOKEN": this.token,
				"EXPORT_TYPE": this.exportType,
				"COMPONENT_NAME": this.componentName
			}
		});

		request.then(function(response)
		{
			this.setExportPopupMessage({ exportInfo: response.data["SUMMARY_HTML"] });
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors);

			this.setExportPopupMessage({ exportInfo: BX.message('TEL_STAT_EXPORT_ERROR') });
		}.bind(this));
	};

	BX.VoximplantStatisticDetail.prototype.downloadSelectedVoxRecords = function()
	{
		var selectedIds = this.grid.getRows().getSelectedIds();
		var selectedRecordsCount = selectedIds.length;

		var records = [];

		for (var recordIndex = 0; recordIndex < selectedRecordsCount; recordIndex++)
		{
			records.push({
				historyId: selectedIds[recordIndex]
			});
		}

		this.downloadVoxRecords(records);
	};

	BX.VoximplantStatisticDetail.prototype.downloadVoxRecords = function (records)
	{
		var recordsCount = records.length;
		var progress = 0;

		var popupWindow = this.createDownloadPopup();
		popupWindow.show();
		this.setDownloadProgress(progress, recordsCount);

		for (var currentRecord = 0; currentRecord < recordsCount; currentRecord++)
		{
			var historyId = records[currentRecord].historyId;

			this.downloadRecordByHistoryId(historyId).then(function()
			{
				progress++;
				this.setDownloadProgress(progress, recordsCount);

				if (progress === recordsCount)
				{
					setTimeout(function()
					{
						popupWindow.destroy();

						this.grid.reloadTable('GET', {
							apply_filter: 'Y',
							clear_nav: 'Y'
						});

					}.bind(this), 300);
				}
			}.bind(this)).catch(function() {
				popupWindow.destroy();

				this.grid.reloadTable('GET', {
					apply_filter: 'Y',
					clear_nav: 'Y'
				});
			}.bind(this));
		}
	};

	BX.VoximplantStatisticDetail.prototype.downloadRecordByHistoryId = function (historyId)
	{
		return new Promise(function (resolve, reject)
		{
			BX.ajax.runComponentAction("bitrix:voximplant.statistic.detail", "downloadRecord", {
				mode: "class",
				data: {
					'historyId': historyId
				}
			}).then(function()
			{
				resolve();
			}).catch(function()
			{
				BX.Voximplant.alert(BX.message("TEL_STAT_ERROR"), BX.message("TEL_STAT_DOWNLOAD_VOX_RECORD_ERROR"));
				reject();
			});
		});
	};

	BX.VoximplantStatisticDetail.prototype.createDownloadPopup = function()
	{
		var progressBarTextBefore = BX.create("div", {
			props: { className: "ui-progressbar-text-before" },
			text: BX.message('TEL_STAT_LOADING')
		});

		var progressBarLine = BX.create("div", {
			props: { className: "ui-progressbar-bar" }
		});

		var progressBarTrack = BX.create("div", {
			props: { className: "ui-progressbar-track" },
			children: [
				progressBarLine
			]
		});

		var progressBarTextAfter = BX.create("div", {
			props: { className: "ui-progressbar-text-after" }
		});

		var progressBar = BX.create("div", {
			props: { className: "ui-progressbar" },
			children: [
				progressBarTextBefore,
				progressBarTrack,
				progressBarTextAfter
			]
		});

		var progressBarContainer = BX.create("div", {
			props: { className: "tel-stat-download-progress-container" },
			children: [
				progressBar
			]
		});

		this.progressBar = progressBar;
		this.progressBarLine = progressBarLine;
		this.progressBarText = progressBarTextAfter;

		return new BX.PopupWindow('bx-voximplant-statistic-detail-download-popup', null, {
			zIndex: 10000,
			closeByEsc: false,
			buttons: '',
			overlay: true,
			content: progressBarContainer
		});
	};

	BX.VoximplantStatisticDetail.prototype.setDownloadProgress = function(current, total)
	{
		var progressInPercent = Math.round(current / total * 100).toPrecision(2);

		this.progressBarLine.style.width = progressInPercent + '%';
		this.progressBarText.innerHTML = current + ' / ' + total;
	};

})();