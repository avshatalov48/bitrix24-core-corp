(() => {
	const TaskUploader = {};

	TaskUploader.init = function()
	{
		console.log('TaskUploader.init');

		this.filesStorage = new TaskUploadFilesStorage();
		this.filesStorage.clear();

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
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
	};

	TaskUploader.onAjaxError = function(response, fileData, taskId)
	{
		const eventName = TaskUploaderEvents.FILE_FAIL_UPLOAD;
		const eventData = {
			file: fileData,
			errors: response.errors,
		};

		this.filesStorage.removeFiles([taskId]);
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
	};

	TaskUploader.init();
})();