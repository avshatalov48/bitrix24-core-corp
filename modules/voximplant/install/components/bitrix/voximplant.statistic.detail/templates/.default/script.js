;(function()
{
	var instance = null;

	BX.VoximplantStatisticDetail = function(params)
	{
		instance = this;

		this.gridContainer = params.gridContainer;
		this.exportButton = params.exportButton;
		this.exportUrl = params.exportUrl;
		this.exportRequestCookieName = params.exportRequestCookieName;
		this.exportAllowed = params.exportAllowed;
		this.exporting = false;

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
		this.exportButton.addEventListener('click', this.startExport.bind(this));

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
		if(this.exporting)
		{
			return;
		}
		if(!this.exportAllowed)
		{
			viOpenTrialPopup('excel-export');
			return;
		}

		var exportRequest = BX.util.getRandomString(16);

		this.exportButton.classList.add('ui-btn-wait');
		this.exportButton.classList.add('ui-btn-disabled');
		window.location.href =  BX.util.add_url_param(this.exportUrl, {exportRequest: exportRequest});

		this.waitForExportFinish(exportRequest).then(function()
		{
			this.exportButton.classList.remove('ui-btn-wait');
			this.exportButton.classList.remove('ui-btn-disabled');
			this.exporting = false;
		}.bind(this))
	};

	BX.VoximplantStatisticDetail.prototype.waitForExportFinish = function(exportRequest)
	{
		var result = new BX.Promise();

		var interval = setInterval(function()
		{
			if(BX.getCookie(this.exportRequestCookieName) === exportRequest)
			{
				clearInterval(interval);
				result.resolve();
			}
		}.bind(this), 1000);

		return result;
	};
})();
