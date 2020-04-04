
include("MediaConverter");
var userId = BX.componentParameters.get("USER_ID", "0");

var FileProcessing = {
	promiseList: {},
	resize: function (taskId, params)
	{
		return new Promise((resolve, reject) =>
		{
			this.promiseList[taskId] = (event, data) =>
			{
				if (event == "onSuccess")
				{
					if(data.path.indexOf("file://") == -1){
						data.path = "file://"+data.path;
					}
					resolve(data.path);
				}
				else
				{
					reject();
				}
			};

			MediaConverter.resize(taskId, params);
		});
	},
	cancel: function ()
	{

	},
	init: function ()
	{
		if(window.MediaConverter)
		{
			MediaConverter.setListener((event, data) =>
			{
				if (this.promiseList[data.id])
				{
					this.promiseList[data.id](event, data);
					delete this.promiseList[data.id];
				}
			});
		}
	},
};

FileProcessing.init();

var FileUploadAgent = {
	taskWriteTimeouts: {},
	taskLastTimeUpdate: {},
	taskInfoSaveInterval: 3000,
	db: new ReactDatabase("uploader_tasks", userId, "en", {
		tasks: {
			name: "tasks",
			fields: [
				{name: "id", unique: true, primary: true},
				{name: "value", class: "BX.FileUploadTask"},
				"userId",
				"date_update"
			]
		},
	}),
	/**
	 * It must set in 5 MB or more because of AWS requirement
	 * @link https://docs.aws.amazon.com/AmazonS3/latest/dev/qfacts.html
	 */
	defaultChunk: 1024 * 1024 * 5,
	init: function ()
	{
		this.FileUploader = new BX.FileUploader((event, data, task) => this.eventHandler(event, data, task),
			{chunk: this.defaultChunk});
		BX.addCustomEvent("onFileUploadTaskReceived",
			data => data.files.forEach(file => this.FileUploader.addTaskFromData(file)));
		BX.addCustomEvent("onFileUploadTaskRequest", data => this.taskRequestHandler(data));
		BX.addCustomEvent("onFileUploadTaskCancel", data => this.cancelTasks(data));
		// this.db.table("tasks").then(table => table.delete());
	},
	postFileEvent: (event, data, taskId) =>
	{
		console.info(event, data, taskId);
		BX.postComponentEvent("onFileUploadStatusChanged", [event, data, taskId]);
		BX.postWebEvent("onFileUploadStatusChanged", {event: event, data: data, taskId: taskId});
	},
	/**
	 *
	 * @param {String} event
	 * @param {Object} data
	 * @param {BX.FileUploadTask} task
	 */
	eventHandler: function (event, data, task)
	{
		if (task)
		{
			if (event == BX.FileUploadEvents.TASK_CREATED)
			{
				task.beforeInitAction = () => this.resizeIfNeeded(task);
				this.updateTaskInfoInDatabase(task, true);
			}

		}

		if (event != BX.FileUploadEvents.ALL_TASK_COMPLETED)
		{
			this.postFileEvent(event, data, task.id);
		}
	},
	updateTaskInfoInDatabase: function (task, create)
	{

		if (create)
		{
			let time = (new Date()).getTime();
			this.db.table("tasks")
				.then(table =>
					table.delete({id: task.id})
						.then(() => table.add({id: task.id, value: this.prepareTaskForDB(task), date_update: time}))
				);

			this.taskLastTimeUpdate[task.id] = time;
		}
		else
		{
			clearTimeout(this.taskWriteTimeouts[task.id]);
			let update = () =>
			{
				let time = (new Date()).getTime();
				this.taskLastTimeUpdate[task.id] = time;
				this.db.table("tasks")
					.then(table => table.update(task.id, {value: this.prepareTaskForDB(task), date_update: time}))
			};

			if ((new Date()).getTime() - this.taskLastTimeUpdate[task.id] >= this.taskInfoSaveInterval)
			{
				update();
			}
			else
			{
				this.taskWriteTimeouts[task.id] = setTimeout(update, 500);
			}
		}
	},
	/**
	 *
	 * @param {BX.FileUploadTask} task
	 */
	prepareTaskForDB: function (task)
	{
		let preparedTask = Object.assign({}, task);
		preparedTask.currentChunk = null;
		preparedTask.fileEntry = null;
		preparedTask.token = null;

		return preparedTask;
	},
	taskRequestHandler: function (data)
	{
		data.files.forEach(file =>
		{
			/**
			 * @var  BX.FileUploadTask task
			 */
			var fireEvent = task =>
			{
				if (task)
				{
					this.postFileEvent(task.lastEventData.event, task.lastEventData.data, file.taskId);
				}
				else
				{
					this.postFileEvent(BX.FileUploadEvents.TASK_NOT_FOUND, {}, file.taskId);
				}
			};

			var task = this.FileUploader.getTask(file.taskId);
			if (task)
			{
				fireEvent(task);
			}
			else
			{
				this.db.table("tasks")
					.then(table =>
						table.get({id: file.taskId})
							.then(tasks =>
							{

								let task = tasks.length > 0 ? tasks[0].VALUE : null;
								if (task)
								{
									console.log("From db1");
									if (task.isFinalStatus())
									{
										console.log(task);
										fireEvent(task);

									}
									else
									{

										console.warn("restart task", task);
										this.FileUploader.addTask(task);
									}
								}
								else
								{
									fireEvent();
								}

							})
					)
			}

		})
	},
	resizeIfNeeded: function (task)
	{
		return new Promise(resolve =>
		{
			if(task.fileData.resize && task.wasProcessed === false)
			{
				let isVideo = (task.fileData.type.toLowerCase() == "mov" || task.fileData.type.toLowerCase() == "mp4");
				let resizeParams = {
					width:task.fileData.resize.width,
					height:task.fileData.resize.height,
					url:task.fileData.url,
					isVideo: isVideo,
					quality:task.fileData.resize.quality
				};
				let internalResolve = path =>{
					if(path)
					{
						task.fileData.url = path;
						task.wasProcessed = true;
					}

					task.callListener("onfileprocessingdone", {url: task.fileData.url});
					resolve();
				};
				task.callListener("onfileprocessing", {});
				console.log(resizeParams);
				FileProcessing.resize(task.id, resizeParams)
					.then(filePath => internalResolve(filePath))
					.catch(() => internalResolve())
			}
			else
			{
				resolve();
			}

		})
	},
	cancelTasks: function (data)
	{
		data.taskIds.forEach(taskId => this.FileUploader.cancelTask(taskId))
	}
};

FileUploadAgent.init();
