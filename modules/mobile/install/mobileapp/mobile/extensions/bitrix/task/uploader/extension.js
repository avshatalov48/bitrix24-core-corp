(() => {
	class UnattachedFilesStorage
	{
		constructor()
		{
			this.queueKey = 'unattachedFiles';
			this.storage = Application.sharedStorage('TaskUploader');
		}

		get()
		{
			const unattachedFiles = this.storage.get(this.queueKey);

			if (typeof unattachedFiles === 'string')
			{
				return JSON.parse(unattachedFiles);
			}

			return {};
		}

		set(files)
		{
			this.storage.set(this.queueKey, JSON.stringify(files));
		}

		add(fileId, fileData)
		{
			this.update(fileId, fileData);
		}

		update(key, value)
		{
			const unattachedFiles = this.get();
			unattachedFiles[key] = value;
			this.set(unattachedFiles);
		}

		remove(key)
		{
			if (this.has(key))
			{
				const unattachedFiles = this.get();
				delete unattachedFiles[key];
				this.set(unattachedFiles);
			}
		}

		has(key)
		{
			const unattachedFiles = this.get();
			const has = Object.prototype.hasOwnProperty;

			return has.call(unattachedFiles, key);
		}

		isEmpty()
		{
			return (Object.keys(this.get()).length <= 0);
		}

		clear()
		{
			this.set({});
		}
	}

	const TaskUploader = {};

	TaskUploader.init = function()
	{
		console.log('TaskUploader.init');

		this.filesStorage = new TaskUploadFilesStorage();
		this.filesStorage.clear();

		this.unattachedFilesStorage = new UnattachedFilesStorage();
		this.unattachedFilesStorage.clear();

		this.isAttaching = false;
		this.releaseUnattachedFilesQueue();

		BX.addCustomEvent('onFileUploadStatusChanged', this.listener.bind(this));
	};

	TaskUploader.listener = function(event, data, taskId)
	{
		if (event === BX.FileUploadEvents.FILE_CREATED)
		{
			if (taskId.indexOf('task-') !== 0)
			{
				return false;
			}

			const fileData = data.result.data.file;
			fileData.extra.params = data.file.params;

			this.attachFile(taskId, fileData);
		}

		return true;
	};

	TaskUploader.attachFile = function(taskId, fileData)
	{
		console.info('TaskUploader.attachFile:', [taskId, fileData]);

		if (this.isAttaching)
		{
			if (!this.unattachedFilesStorage.has(taskId))
			{
				this.unattachedFilesStorage.add(taskId, fileData);
			}
			return;
		}
		this.isAttaching = true;

		const config = {
			data: {
				taskId: fileData.extra.params.taskId,
				fileId: fileData.id,
			},
		};

		BX.ajax.runAction('tasks.task.files.attach', config)
			.then(response => this.onAjaxResponse(response, fileData, taskId))
			.catch(response => this.onAjaxError(response, fileData, taskId));
	};

	TaskUploader.onAjaxResponse = function(response, fileData, taskId)
	{
		if (response.status === 'success')
		{
			this.onAjaxSuccess(response, fileData, taskId);
		}
		else
		{
			this.onAjaxError(response, fileData, taskId);
		}
	};

	TaskUploader.onAjaxSuccess = function(response, fileData, taskId)
	{
		const eventName = TaskUploaderEvents.FILE_SUCCESS_UPLOAD;
		const eventData = {
			file: fileData,
			result: response.data,
		};

		this.filesStorage.removeFiles([taskId]);
		this.unattachedFilesStorage.remove(taskId);
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
		this.isAttaching = false;
	};

	TaskUploader.onAjaxError = function(response, fileData, taskId)
	{
		const eventName = TaskUploaderEvents.FILE_FAIL_UPLOAD;
		const eventData = {
			file: fileData,
			errors: response.errors,
		};

		this.filesStorage.removeFiles([taskId]);
		this.unattachedFilesStorage.remove(taskId);
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
		this.isAttaching = false;
	};

	TaskUploader.releaseUnattachedFilesQueue = function()
	{
		if (!this.unattachedFilesStorage.isEmpty())
		{
			const unattachedFiles = this.unattachedFilesStorage.get();
			Object.entries(unattachedFiles).forEach(([taskId, fileData]) => this.attachFile(taskId, fileData));
		}
		setTimeout(() => this.releaseUnattachedFilesQueue(), 5000);
	};

	TaskUploader.init();
})();