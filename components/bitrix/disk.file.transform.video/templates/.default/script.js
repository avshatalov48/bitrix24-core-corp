;(function(){


BX.namespace("BX.Disk");
BX.Disk.FileTransformVideo = function (options)
{
	this.runGenerationPreviewData = options.runGenerationPreviewData;
	this.runGeneratePreviewLinkClass = options.runGeneratePreviewLinkClass;
	this.downloadLink = options.downloadLink;
	this.pullTag = options.pullTag;
	this.layout = options.layout;
	this.video = {
		width: null,
		height: null,
		sources: null
	};

	this.bindEvents();
	if (this.pullTag)
	{
		this.registerPullEvent(this.pullTag);
	}
};

BX.Disk.FileTransformVideo.prototype =
{
	bindEvents: function ()
	{
		console.log('bindEvents', this.layout.containerId);
		BX.bindDelegate(
			BX(this.layout.containerId),
			'click',
			{className: this.runGeneratePreviewLinkClass},
			this.handleClickToRunTransformation.bind(this)
		);
	},

	handleClickToRunTransformation: function (event)
	{
		event.preventDefault();

		var data = {};
		if (this.runGenerationPreviewData.attachedObjectId)
		{
			data.attachedObjectId = this.runGenerationPreviewData.attachedObjectId;
		}

		if (this.runGenerationPreviewData.attachedObjectId)
		{
			data.fileId = this.runGenerationPreviewData.fileId;
		}

		BX.ajax.runAction(this.runGenerationPreviewData.action, {data: data}).then(function (response) {
			var previewGeneration = response.data.previewGeneration;

			var target = BX.getEventTarget(event);
			var container = BX(this.layout.containerId);
			if (!container)
			{
				return;
			}

			var title = container.querySelector('.disk-file-transform-file-loader-title');
			var desc = container.querySelector('.disk-file-transform-file-loader-desc');
			var inner = container.querySelector('.disk-file-transform-file-loader-inner');

			if (previewGeneration.status === 'success')
			{
				this.registerPullEvent(previewGeneration.data.pullTag);

				BX.adjust(title, {text: BX.message('DISK_FILE_TRANSFORM_VIDEO_IN_PROCESS_TITLE')});
				if (desc)
				{
					BX.adjust(desc, {text: BX.message('DISK_FILE_TRANSFORM_VIDEO_IN_PROCESS_DESC')});
				}
				if (inner)
				{
					BX.adjust(inner, {html: this.getDummyLoaderHtml()});
				}
			}
			else
			{
				BX.adjust(title, {text: BX.message('DISK_FILE_TRANSFORM_VIDEO_ERROR_TITLE')});
				if (desc)
				{
					var message = this.getErrorMessageByCode(previewGeneration.status);
					if (message)
					{
						BX.adjust(desc, {text: message});
					}
				}

				this.showError();
			}

			BX(target).remove();
		}.bind(this));
	},

	registerPullEvent: function(pullTag)
	{
		if (this.isRegisteredPullEvent)
		{
			return;
		}

		this.isRegisteredPullEvent = true;
		console.log('registerPullEvent', pullTag);

		BX.addCustomEvent('onPullEvent-main', function (command, params) {
			if (command === 'transformationComplete')
			{
				console.log('transformationComplete');

				this.loadVideo().then(function(){

					var player = new BX.Fileman.Player('playerId_' + (Math.floor(Math.random() * Math.floor(100000))), {
						width: Math.min(this.width, 800),
						height: (Math.min(this.width, 800) * 9 / 16),
						sources: this.sources
					});

					if (BX(this.layout.containerId))
					{
						BX.replace(BX(this.layout.containerId), player.createElement());
						player.init();
					}

				}.bind(this), function () {
					this.showError();
				}.bind(this));
			}
		}.bind(this));

		BX.PULL.extendWatch(pullTag);
	},

	loadVideo: function ()
	{
		var promise = new BX.Promise();

		BX.ajax.promise({
			url: BX.util.add_url_param(this.downloadLink, {ts: 'bxviewer'}),
			method: 'GET',
			dataType: 'json',
			headers: [
				{
					name: 'BX-Viewer-src',
					value: this.downloadLink
				},
				{
					name: 'BX-Viewer',
					value: 'video'
				}
			]
		}).then(function(response){

			if (response.data.data)
			{
				this.width = response.data.data.width;
				this.height = response.data.data.height;
				this.sources = response.data.data.sources;
			}

			if (response.data.html)
			{
				var html = BX.processHTML(response.data.html);

				BX.load(html.STYLE, function(){
					BX.ajax.processScripts(html.SCRIPT, undefined, function(){
						promise.fulfill(this);
					}.bind(this));
				}.bind(this));
			}
		}.bind(this));

		return promise;
	},

	getDummyLoaderHtml: function()
	{
		return '<svg class="disk-file-transform-file-circular hidden" viewBox="25 25 50 50"><circle class="disk-file-transform-file-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/><circle class="disk-file-transform-file-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/></svg>';
	},

	showError: function()
	{
		var container = BX(this.layout.containerId);
		if (!container)
		{
			return;
		}

		BX.adjust(container.querySelector('.disk-file-transform-file-loader-inner'), {
			html: this.getDummyErrorHtml()
		});
	},

	getDummyErrorHtml: function()
	{
		return '<div class="disk-file-transform-file-loader-button disk-file-transform-file-loader-button-sad"></div>';
	},

	getErrorMessageByCode: function(code)
	{
		switch (code)
		{
			case 'not allowed':
				return BX.message('DISK_FILE_TRANSFORM_VIDEO_ERROR_TRANSFORM_NOT_ALLOWED');
			case 'no module':
				return BX.message('DISK_FILE_TRANSFORM_VIDEO_ERROR_TRANSFORM_NOT_INSTALLED');
			case 'was transformed':
				return BX.message('DISK_FILE_TRANSFORM_VIDEO_ERROR_TRANSFORM_TRANSFORMED');
		}

		return '';
	}
};
})(window);