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
		this.componentName = params.exportParams.componentName;

		/** @type {BX.UI.StepProcessing.Process} */
		this.exporter = null;

		this.recordDownloading = null;
		this.downloadPopup = null;

		var grid = BX.Main.gridManager.getById(this.gridContainer.id);
		/** @type {BX.Main.grid} */
		this.grid = grid.instance;

		this.progressBar = null;
		this.progressBarLine = null;
		this.progressBarText = null;

		this.reportParams = params.reportParams;

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
		if (!this.reportParams['from_analytics'])
		{
			this.createDownloadHint();
			BX.addCustomEvent('Grid::updated', this.createDownloadHint);

			this.exportButton.addEventListener('click', this.showExporter.bind(this));
		}

		if(this.exportAllowed)
		{
			this.initExporter();
		}

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

	//region: Total

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
		var config = {
			mode: "class"
		}

		if (this.reportParams != null)
		{
			config.data = this.reportParams;
		}

		return new Promise(function (resolve)
		{
			BX.ajax.runComponentAction("bitrix:voximplant.statistic.detail", "getRowsCount", config).then(function(response)
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

	//endregion

	//region: Export

	BX.VoximplantStatisticDetail.prototype.initExporter = function()
	{
		this.exporter = new BX.UI.StepProcessing.Process({
			id: 'VoximplantStatisticDetailExport',
			controller: "bitrix:voximplant.export",
			messages: {
				DialogTitle: BX.message('TEL_STAT_EXPORT_DETAIL_TO_EXCEL'),
				DialogSummary: BX.message('TEL_STAT_EXPORT_DETAIL_TO_EXCEL_DESCRIPTION') + "\n\n"
					+ BX.message('TEL_STAT_EXPORT_DETAIL_TO_EXCEL_LONG_PROCESS'),
				DialogStartButton: BX.message('TEL_STAT_ACTION_EXECUTE'),
				DialogStopButton: BX.message('TEL_STAT_ACTION_STOP'),
				DialogCloseButton: BX.message('TEL_STAT_ACTION_CLOSE'),
				RequestError: BX.message('TEL_STAT_EXPORT_ERROR')
			},
			showButtons: {
				start: true,
				stop: true,
				close: true
			},
			dialogMaxWidth: 650
		});

		if (this.reportParams != null)
		{
			this.exporter.setParams(this.reportParams);
		}

		this.exporter
			.setParam("EXPORT_TYPE", this.exportType)
			.setParam("COMPONENT_NAME", this.componentName)
			.addQueueAction({
				action: 'dispatcher'
			})
		;
	};

	BX.VoximplantStatisticDetail.prototype.showExporter = function()
	{
		if(!this.exportAllowed)
		{
			BX.UI.InfoHelper.show('limit_contact_center_telephony_excel_export');
			return;
		}

		this.exporter.showDialog();
	};

	//endregion

	//region: Download Records

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

		BX.ajax.runComponentAction("bitrix:voximplant.statistic.detail", "isRecordsAlreadyUploaded", {
			mode: "class",
			data: {
				historyIds: records
			}
		}).then(function(response)
		{
			if (response.data)
			{
				BX.Voximplant.alert(BX.message('TEL_STAT_RECORDS_ALREADY_DOWNLOADED_TITLE'), BX.message("TEL_STAT_RECORDS_ALREADY_DOWNLOADED"));
			}
			else
			{
				this.downloadVoxRecords(records);
			}
		}.bind(this)).catch(function()
		{
			BX.Voximplant.alert(BX.message("TEL_STAT_ERROR"), BX.message("TEL_STAT_DOWNLOAD_VOX_RECORD_ERROR"));
			reject();
		});
	};

	BX.VoximplantStatisticDetail.prototype.downloadVoxRecords = function(records)
	{
		var recordsCount = records.length;
		var currentRecord = 0;
		var historyId = records[currentRecord].historyId;

		this.downloadPopup = this.createDownloadPopup();

		this.downloadPopup.show();
		this.setDownloadProgress(currentRecord, recordsCount);

		this.recordDownloading = true;
		this.downloadVoxRecordsSequentially(historyId, records, currentRecord, recordsCount);
	};

	BX.VoximplantStatisticDetail.prototype.downloadVoxRecordsSequentially = function (historyId, records, currentRecord, recordsCount)
	{
		if (!this.recordDownloading)
		{
			this.downloadPopup.destroy();

			this.grid.reloadTable('GET', {
				apply_filter: 'Y',
				clear_nav: 'Y'
			});

			return;
		}

		this.downloadRecordByHistoryId(historyId).then(function()
		{
			currentRecord++;
			this.setDownloadProgress(currentRecord, recordsCount);

			if (currentRecord === recordsCount)
			{
				setTimeout(function()
				{
					this.downloadPopup.destroy();

					this.grid.reloadTable('GET', {
						apply_filter: 'Y',
						clear_nav: 'Y'
					});

					BX.Voximplant.alert(
						BX.message("TEL_STAT_RECORDS_ALREADY_DOWNLOADED_TITLE"),
						BX.message("TEL_STAT_RECORDS_DOWNLOADED_AVAILABLE")
					);
				}.bind(this), 300);

				return;
			}

			historyId = records[currentRecord].historyId;
			this.downloadVoxRecordsSequentially(historyId, records, currentRecord, recordsCount);
		}.bind(this));
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
			}.bind(this)).catch(function()
			{
				resolve();
			}.bind(this));
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

		var downloadPopup = new BX.PopupWindow('bx-voximplant-statistic-detail-download-popup', null, {
			titleBar: BX.message('TEL_STAT_RECORDS_ALREADY_DOWNLOADED_TITLE'),
			content: progressBarContainer,
			buttons: [
				new BX.UI.Button({
					text: BX.message('TEL_STAT_ACTION_STOP'),
					id: "tel-download-cancel-btn",
					color: BX.UI.Button.Color.LIGHT_BORDER,
					onclick: function()
					{
						this.recordDownloading = false;
						downloadPopup.destroy();
					}.bind(this)
				})
			],
			overlay: true,
			closeByEsc: false
		});

		return downloadPopup;
	};

	BX.VoximplantStatisticDetail.prototype.setDownloadProgress = function(current, total)
	{
		var progressInPercent = Math.round(current / total * 100).toPrecision(2);

		this.progressBarLine.style.width = progressInPercent + '%';
		this.progressBarText.innerHTML = current + ' / ' + total;
	};

	BX.VoximplantStatisticDetail.prototype.createDownloadHint = function()
	{
		if (!BX("download_records_hint"))
		{
			var hint = BX.UI.Hint.createNode(BX.message('TEL_STAT_ACTION_VOX_DOWNLOAD_HINT'));
			hint.setAttribute('id', 'download_records_hint');

			BX("download_records").appendChild(hint);
		}
	};

	//endregion
})();