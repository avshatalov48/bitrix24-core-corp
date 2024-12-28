;(function(){

BX.namespace('BX.Disk');

if(BX.getClass('BX.Disk.Player'))
{
	return;
}

BX.Disk.Player = function(player)
{
	this.isReady = false;
	if(BX.getClass('BX.Fileman.Player') && BX.is_subclass_of(player, BX.Fileman.Player))
	{
		this.player = player;
		this.container = BX(player.id).parentNode;
		this.isReady = true;
		this.player.isDiskErrorShown = this.player.isDiskErrorShown || false;
		this.player.isDiskStatusChecked = this.player.isDiskStatusChecked || false;
	}
};

BX.Disk.Player.prototype.adjustWidth = function(node, maxWidth, maxHeight, videoWidth, videoHeight)
{
	if(!BX.type.isDomNode(node))
	{
		return false;
	}
	if(!maxWidth || !maxHeight || !videoWidth || !videoHeight)
	{
		return false;
	}

	let width = Math.max(videoWidth, 400);
	let height = Math.max(videoHeight, 130);
	if (videoHeight > maxHeight || videoWidth > maxWidth)
	{
		const resultRelativeSize = maxWidth / maxHeight;
		const videoRelativeSize = videoWidth / videoHeight;
		let reduceRatio = 1;
		if (resultRelativeSize > videoRelativeSize)
		{
			reduceRatio = maxHeight / videoHeight;
		}
		else
		{
			reduceRatio = maxWidth / videoWidth;
		}

		width = Math.max(videoWidth * reduceRatio, 400);
		height = Math.max(videoHeight * reduceRatio, 130);
	}

	this.player.vjsPlayer.fluid(false);

	BX.Dom.style(node, 'width', `${width}px`);
	BX.Dom.style(node, 'height', `${height}px`);

	this.player.vjsPlayer.width('');
	this.player.vjsPlayer.height('');

	BX.Dom.style(this.player.vjsPlayer.el(), 'width', '100%');
	BX.Dom.style(this.player.vjsPlayer.el(), 'min-width', '300px');
	BX.Dom.style(this.player.vjsPlayer.el(), 'aspect-ratio', `${width} / ${height}`);
	BX.Dom.style(this.player.vjsPlayer.el(), 'height', 'auto');

	return true;
};

BX.Disk.Player.prototype.onAfterInit = function()
{
	if(this.player.vjsPlayer.error())
	{
		this.onError();
	}
	else if(this.player.vjsPlayer.videoWidth() > 0 && this.player.vjsPlayer.videoHeight() > 0)
	{
		this.adjust();
	}
	else
	{
		this.player.vjsPlayer.one('loadedmetadata', BX.proxy(this.adjust, this));
	}
};

BX.Disk.Player.prototype.adjust = function()
{
	if(!this.container)
	{
		return;
	}
	if(!this.player.vjsPlayer)
	{
		return;
	}

	BX.addClass(this.container, 'player-adjusting');
	this.adjustWidth(
		this.container,
		this.player.width,
		this.player.height,
		this.player.vjsPlayer.videoWidth(),
		this.player.vjsPlayer.videoHeight()
	);

	BX.addClass(this.container, 'player-loaded');
};

BX.Disk.Player.prototype.onError = function()
{
	if(this.container.getAttribute('data-bx-transform-info-url'))
	{
		if(this.player.isDiskStatusChecked)
		{
			return;
		}
		this.player.isDiskStatusChecked = true;
		var url = this.container.getAttribute('data-bx-transform-info-url');
		BX.ajax({
			'method': 'GET',
			'dataType': 'json',
			'url': url,
			'onsuccess': BX.proxy(function(data)
			{
				BX.addClass(this.container, 'player-loaded');
				if(data.status && data.status == 'success' && data.html)
				{
					var html = BX.processHTML(data.html);
					this.container.innerHTML = html.HTML;
					if(html.SCRIPT)
					{
						BX.ajax.processScripts(html.SCRIPT);
					}
				}
				else
				{
					this.showError();
				}
			}, this),
			'onfailure': BX.proxy(this.showError, this)
		});
	}
	else
	{
		this.showError();
	}
};

BX.Disk.Player.prototype.showError = function()
{
	if(this.player.isDiskErrorShown)
	{
		return;
	}
	this.player.isDiskErrorShown = true;
	BX.addClass(this.container, 'player-loaded');
	var errorContainer = BX.create('div', {props: {className: 'disk-player-error-container'}, style: {width: this.player.width + 'px', height: this.player.height + 'px'}, children: [
		BX.create('div', {props: {className: 'disk-player-error-icon'}, html: ''}),
		BX.create('div', {props: {className: 'disk-player-error-message'}, html: BX.message('DISK_JS_PLAYER_ERROR_MESSAGE')})
	]});
	var downloadLink = BX.findChildByClassName(errorContainer, 'disk-player-download');
	if(downloadLink)
	{
		var source = '';
		if(BX.type.isArray(this.player.params.sources))
		{
			source = this.player.params.sources.slice(-1).pop().src;
		}
		else if(BX.type.isPlainObject(this.player.params.sources))
		{
			source = this.player.params.sources.src;
		}
		if(source)
		{
			BX.adjust(downloadLink, {events: {click: function(){location.href = source;}}});
		}
	}
	if(this.container)
	{
		BX.hide(BX(this.player.id));
		BX.append(errorContainer, this.container);
	}
	else
	{
		BX.adjust(BX(this.player.id), {children: [errorContainer]});
	}
};

BX.ready(function()
{
	BX.addCustomEvent('PlayerManager.Player:onAfterInit', function(player)
	{
		if(player.skin !== 'vjs-disk_player-skin')
		{
			return;
		}
		var diskPlayer = new BX.Disk.Player(player);
		if(diskPlayer.isReady)
		{
			diskPlayer.onAfterInit();
		}
	});
	BX.addCustomEvent('PlayerManager.Player:onError', function(player)
	{
		if(player.skin !== 'vjs-disk_player-skin')
		{
			return;
		}
		var diskPlayer = new BX.Disk.Player(player);
		if(diskPlayer.isReady)
		{
			diskPlayer.onError();
		}
	});
});
})(window);
