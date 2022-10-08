(() => {
	const TaskChecklistUploader = {};

	TaskChecklistUploader.init = function()
	{
		this.filesStorage = new TaskChecklistUploadFilesStorage();
		this.filesStorage.clear();

		BX.addCustomEvent('onFileUploadStatusChanged', this.listener.bind(this));
	};

	TaskChecklistUploader.listener = function(event, data, taskId)
	{
		if (event === BX.FileUploadEvents.FILE_CREATED)
		{
			if (taskId.indexOf('taskChecklist-') !== 0)
			{
				return false;
			}

			const {params} = data.file;
			const fileData = data.result.data.file;

			fileData.extra.params = params;

			this.attachFile(taskId, params.ajaxData, fileData);
		}

		return true;
	};

	TaskChecklistUploader.attachFile = function(taskId, ajaxData, fileData)
	{
		console.info('TaskChecklistUploader.attachFile:', [taskId, fileData]);
		const {entityTypeId, entityId, checkListItemId, mode} = ajaxData;
		const config = {
			data: {
				checkListItemId,
				[entityTypeId]: entityId,
				filesIds: [fileData.id],
			},
		};

		if (mode === 'edit')
		{
			this.onAjaxSuccess({data: {checkListItemId}}, fileData, taskId);
		}
		else
		{
			BX.ajax.runAction('tasks.task.checklist.addAttachmentsFromDisk', config)
				.then(response => this.onAjaxResponse(response, fileData, taskId))
				.catch(response => this.onAjaxError(response, fileData, taskId));
		}
	};

	TaskChecklistUploader.onAjaxResponse = function(response, fileData, taskId)
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

	TaskChecklistUploader.onAjaxSuccess = function(response, fileData, taskId)
	{
		const eventName = TaskChecklistUploaderEvents.FILE_SUCCESS_UPLOAD;
		const eventData = {file: fileData, result: response.data};

		this.filesStorage.removeFiles([taskId]);
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
	};

	TaskChecklistUploader.onAjaxError = function(response, fileData, taskId)
	{
		const eventName = TaskChecklistUploaderEvents.FILE_FAIL_UPLOAD;
		const eventData = {file: fileData, errors: response.errors};

		this.filesStorage.removeFiles([taskId]);
		FileUploadAgent.postFileEvent(eventName, eventData, taskId);
	};

	TaskChecklistUploader.init();
})();