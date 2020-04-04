;(function()
{
	BX.VoximplantStatisticDetail = function(params)
	{
		this.gridContainer = params.gridContainer;
		this.exportButton = params.exportButton;
		this.exportUrl = params.exportUrl;
		this.exportRequestCookieName = params.exportRequestCookieName;
		this.exportAllowed = params.exportAllowed;
		this.exporting = false;

		this.init();
	};

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
