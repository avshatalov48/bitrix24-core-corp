BX.namespace("BX.Disk.ExternalLoader");
BX.Disk.ExternalLoader = (function (){
	var queue = new BX.Disk.Queue;

	return {
		startLoad: function(file) {
			var queueItem = new BX.Disk.QueueItem(
				new BX.Disk.ExternalLoader.NewItemClass(file),
				function (item)
				{
					item.start();
				},
				function (item)
				{
					return [item, "onFinish"];
				}
			);

			queue.push(queueItem).process();
		},
		reloadLoadAttachedObject: function(attachedObject) {
			return new BX.Disk.ExternalLoader.AttachedItemClass(attachedObject);
		}
	};
})();

BX.Disk.ExternalLoader.NewItemClass = (function ()
{
	var NewItemClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/tools/disk/uf.php';

		this.file = {
			id: parameters.file.id,
			service: parameters.file.service
		};
		this.cloudImport = {};
		this.handlers = {
			onFinish: parameters.onFinish,
			onProgress: parameters.onProgress
		};

		this.setEvents();
	};

	NewItemClass.prototype.setEvents = function()
	{};

	NewItemClass.prototype.onFinish = function(response)
	{
		BX.onCustomEvent(this, "onFinish", [this]);
		this.handlers.onFinish(response.file);
	};

	NewItemClass.prototype.onProgress = function(progress)
	{
		this.handlers.onProgress(progress);
	};

	NewItemClass.prototype.start = function() {
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'startUpload'),
			data: {
				fileId: this.file.id,
				service: this.file.service
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return null;
				}
				this.cloudImport = {
					id: response.cloudImport.id
				};

				this.processChunkDownload();
			}, this)
		});
	};

	NewItemClass.prototype.processChunkDownload = function(){
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'uploadChunk'),
			data: {
				cloudImportId: this.cloudImport.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return null;
				}

				this.onProgress(parseInt(response.downloadedContentSize / response.contentSize * 100, 10));

				if(response.step == 'finish') {
					this.saveAsNewFile();
				}
				else{
					this.processChunkDownload();
				}

			}, this)
		});
	};

	NewItemClass.prototype.saveAsNewFile = function() {
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'saveAsNewFile'),
			data: {
				cloudImportId: this.cloudImport.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return null;
				}

				this.onFinish(response);
			}, this)
		});
	};

	return NewItemClass;
})();

BX.Disk.ExternalLoader.AttachedItemClass = (function ()
{
	var AttachedItemClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/tools/disk/uf.php';

		this.attachedObject = {
			id: parameters.attachedObject.id,
			service: parameters.attachedObject.service
		};
		this.cloudImport = {};
		this.handlers = {
			onFinish: parameters.onFinish,
			onProgress: parameters.onProgress
		};

		this.setEvents();
	};

	AttachedItemClass.prototype.setEvents = function()
	{};

	AttachedItemClass.prototype.onFinish = function(response)
	{
		this.handlers.onFinish(response);
	};

	AttachedItemClass.prototype.onProgress = function(progress)
	{
		this.handlers.onProgress(progress);
	};

	AttachedItemClass.prototype.start = function() {
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'reloadAttachedObject'),
			data: {
				attachedId: this.attachedObject.id,
				service: this.attachedObject.service
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return;
				}
				if(!response.hasNewVersion)
				{
					this.onFinish(response);
					return;
				}

				this.cloudImport = {
					id: response.cloudImport.id
				};

				this.processChunkDownload();
			}, this)
		});
	};

	AttachedItemClass.prototype.processChunkDownload = function(){
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'uploadChunk'),
			data: {
				cloudImportId: this.cloudImport.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return null;
				}

				this.onProgress(parseInt(response.downloadedContentSize / response.contentSize * 100, 10));

				if(response.step == 'finish') {
					this.updateFile();
				}
				else{
					this.processChunkDownload();
				}

			}, this)
		});
	};

	AttachedItemClass.prototype.updateFile = function() {
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'updateAttachedObject'),
			data: {
				attachedId: this.attachedObject.id,
				cloudImportId: this.cloudImport.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status != 'success')
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					});
					return null;
				}

				this.onFinish(response);
			}, this)
		});
	};

	return AttachedItemClass;
})();