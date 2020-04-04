(()=>{

	/** *********
	 * Uploader
	 *********** */

	let userId = BX.componentParameters.get("USER_ID", "0");

	BX.FileDataSender = function (config)
	{
		this.config = config;
	};

	BX.FileDataSender.prototype = {
		start: function ()
		{
			return new Promise((resolve, reject) =>
			{
				"use strict";

				let config = this.config;
				let xhr = new XMLHTTPRequest(true);
				xhr.open("POST", config["url"]);

				if (config.headers)
				{
					Object.keys(config.headers).forEach(
						headerName => xhr.setRequestHeader(headerName, config.headers[headerName]))
				}
				if (config.timeout)
				{
					xhr.timeout = config.timeout;
				}

				if (config["onUploadProgress"])
				{
					if (Application.getPlatform() == "android")
					{
						xhr.upload = {};
					}
					xhr.upload.onprogress = config["onUploadProgress"];
				}

				xhr.onerror = e => reject({error: e});
				xhr.onload = () =>
				{
					var isSuccess = BX.ajax.xhrSuccess(xhr);
					if (isSuccess)
					{
						try
						{
							var json = BX.parseJSON(xhr.responseText);
							resolve(json);
						}
						catch (e)
						{
							reject({error: e});
						}
					}
					else
					{
						reject({error: {message: "XMLHTTPRequest error status " + xhr.status}});
					}
				};

				xhr.send(config["data"]);
				this.config = config = null;

			});
		},

	};

	/**
	 * @param config
	 * @returns {BX.FileDataSender}
	 */
	BX.FileDataSender.create = function (config)
	{
		return new BX.FileDataSender(config);
	};

	/**
	 *
	 * @param {Object} fileData
	 * @param defaultChunk
	 * @constructor
	 */
	BX.FileUploadTask = function (fileData, defaultChunk)
	{

		if (fileData)
		{
			this.applyData(fileData);
		}

		this.chunkSize = defaultChunk;
		this.fileEntry = null;
		this.listener = _ => null;
		this.token = null;
		this.status = Statuses.PENDING;
		this.lastEventData = {event: TaskEventConsts.TASK_CREATED, data: {}}
	};

	BX.FileUploadTask.Statuses = {
		PENDING: 0,
		PROGRESS: 1,
		DONE: 2,
		CANCELLED: 3,
		FAILED: 4,
	};

	/**
	 * @readonly
	 * @typedef {number} TaskStatus
	 * @enum {TaskStatus}
	 */

	let Statuses = BX.FileUploadTask.Statuses;

	BX.FileUploadTask.prototype = {
		progress: {byteSent: 0, percent: 0},
		/**
		 * @type {TaskStatus}
		 */
		status: Statuses.PROGRESS,
		lastEventData: {},
		wasProcessed: false,
		beforeCommitAction: null,
		afterCommitAction: null,
		beforeInitAction: null,
		applyData: function (fileData)
		{
			this.id = fileData.taskId || "";
			this.fileData = fileData;
		},
		start: function ()
		{
			this.status = Statuses.PROGRESS;
			this.status =
				this.startTime = (new Date()).getTime();
			this.initFileData().then(() =>
			{
				if (!this.fileEntry.folderId)
				{
					this.status = Statuses.FAILED;
					this.callListener(TaskEventConsts.TASK_STARTED_FAILED, {
						error: {code: 4, message: "The property 'folderId' is not set"}
					});
					return;
				}
				else
				{
					this.callListener(TaskEventConsts.FILE_UPLOAD_START, {});
					this.onNext();
				}
			}).catch(e =>
			{
				this.status = Statuses.FAILED;
				this.callListener(TaskEventConsts.TASK_STARTED_FAILED, {
					error: {code: 0, message: "Unknown error", error: e}
				});
			});
		},
		cancel: function ()
		{
			this.callListener(TaskEventConsts.TASK_CANCELLED, {});
			this.status = Statuses.CANCELLED;
		},
		isCancelled: function ()
		{
			return this.status == Statuses.CANCELLED;
		},
		isFinalStatus: function ()
		{
			return (
				this.status == Statuses.CANCELLED
				|| this.status == Statuses.DONE
				|| this.status == Statuses.FAILED
			)
		},
		initFileData: function ()
		{
			return new Promise((resolve, reject) =>
			{

				let readError = (e) =>
				{
					this.status = Statuses.FAILED;
					this.callListener(TaskEventConsts.FILE_READ_ERROR, {error: e});
					reject();
				};

				if (this.fileData)
				{
					this.beforeInit()
						.then(() =>
							{
								let url = null;
								if (this.fileData.url.startsWith("file://"))
								{
									url = this.fileData.url;
								}
								else
								{
									url = "file://" + this.fileData.url;
								}

								let name = this.fileData.name? this.fileData.name: null;
								let mimeType = this.fileData.mimeType? this.fileData.mimeType: null;

								return BX.FileUtils.fileForReading(url)
									.then(entry =>
									{
										entry.params = this.fileData.params;
										entry.folderId = this.fileData.folderId;
										entry.chunk = this.fileData.chunk || this.chunkSize;

										if (name)
										{
											entry.file.name = name;
										}

										if (mimeType)
										{
											entry.file.mimeType = mimeType;
										}

										this.fileEntry = entry;
										resolve();
									})
									.catch(e => readError(e));
							}
						);
				}
				else
				{
					readError()
				}
			});
		},
		commit: function ()
		{
			return new Promise(resolve =>
			{
				this.uploadPreview().then(previewData =>
					{
						let body = "";
						let headers = {};
						if (previewData)
						{

							// let comp = BX.utils.parseUrl(this.fileData.previewUrl);
							// console.error(comp);
							let previewName = "preview_" + this.fileEntry.getName() + ".jpg";
							let boundary = "FormUploaderBoundary";
							headers = {"Content-Type": "multipart/form-data; boundary=" + boundary};
							body = "--" + boundary + "\r\n" +
								"Content-Disposition: form-data; name=\"previewFile\"; filename=\"" + previewName + "\"\r\n" +
								"Content-Type: image/jpeg\r\n\r\n" + previewData + "\r\n\r\n" +
								"--" + boundary + "--";
						}

						if (this.fileEntry.getMimeType())
						{
							headers['X-Upload-Content-Type'] = this.fileEntry.getMimeType();
						}

						BX.ajax({
							method: "POST",
							dataType: "json",
							prepareData: false,
							headers: headers,
							data: body,
							uploadBinary: true,
							url: "/bitrix/services/main/ajax.php?action=disk.api.file.createByContent&filename="
								+ this.fileEntry.getName()
								+ "&folderId=" + this.fileEntry.folderId
								+ "&contentId=" + this.token
								+ "&generateUniqueName=Y"
						}).then((res) =>
						{
							this.endTime = (new Date()).getTime();
							console.info("Task execution time:", (this.endTime - this.startTime) / 1000, this.fileEntry);
							this.status = Statuses.DONE;
							this.callListener(TaskEventConsts.FILE_CREATED, {result: res});
							resolve();

						}).catch(error =>
						{
							this.status = Statuses.FAILED;
							this.callListener(TaskEventConsts.FILE_CREATED_FAILED, {error: error});
							resolve();
						});
					}
				);
			});
		},

		onNext: function ()
		{
			if (!this.isCancelled())
			{
				this.fileEntry.readNext()
					.then(data =>
					{
						this.currentChunk = data;
						this.sendChunk(data);
					})
					.catch(e =>
					{
						this.currentChunk = null;
						if (e.code === 101) //eof
						{
							this.beforeCommit()
								.then(() => this.commit())
								.then(() => this.afterCommit())

						}
						else
						{
							this.status =
								this.callListener(TaskEventConsts.FILE_UPLOAD_FAILED, {error: e})
						}
					})
			}
			else
			{
				this.currentChunk = null;
			}

		},
		callAction: function (actionName)
		{
			return new Promise(resolve =>
			{
				if (typeof this[actionName] == "function" && this.hasOwnProperty(actionName))
				{
					let promise = this[actionName](this);
					if (promise instanceof Promise)
					{
						promise.then(() => resolve(), () => resolve());
					}
					else
					{
						resolve();
					}
				}
				else
				{
					resolve();
				}
			});
		},
		beforeInit: function ()
		{
			return this.callAction("beforeInitAction");
		},
		beforeCommit: function ()
		{
			return this.callAction("beforeCommitAction");
		},
		afterCommit: function ()
		{
			return this.callAction("afterCommitAction");
		},
		getFileName:function(){
			if(this.fileData.name)
				return this.fileData.name;

			return this.fileEntry.getFileName();
		},
		sendChunk: function (data)
		{
			let url = "/bitrix/services/main/ajax.php?action=disk.api.content.upload&filename="
				+ this.getFileName()
				+ (this.token ? "&token=" + this.token : "");

			let headers = {
				"Content-Type": this.fileEntry.getType(),
				"Content-Range": "bytes " + data.start + "-" + (data.end - 1) + "/" + this.fileEntry.getSize()
			};

			let config = {
				headers: headers,
				onUploadProgress: (e) =>
				{
					let currentTotalSent = data.start + e.loaded;
					this.progress.byteSent = currentTotalSent;
					this.progress.percent = Math.round((currentTotalSent / this.fileEntry.getSize()) * 100);
					this.callListener(TaskEventConsts.FILE_UPLOAD_PROGRESS, {
						percent: Math.round((currentTotalSent / this.fileEntry.getSize()) * 100),
						byteSent: currentTotalSent,
						byteTotal: this.fileEntry.getSize(),
					});
				},
				data: data.content,
				url: url
			};

			let error = e =>
			{
				this.status = Statuses.FAILED;
				let error = {};
				if (e.xhr)
				{
					error = {
						message: "Ajax request error",
						code: 0
					}
				}
				else
				{
					error = e;
				}

				this.callListener(TaskEventConsts.FILE_CREATED_FAILED, {error: error})
			};

			BX.FileDataSender.create(config).start()
				.then(res =>
				{
					if (!res.status || res.status !== "success")
					{
						error({code: 0, message: "wrong response", response: res});
					}
					else
					{
						if (res.data.token && this.token == null)
						{
							this.token = res.data.token;

							this.callListener(TaskEventConsts.TASK_TOKEN_DEFINED, {token: this.token});
						}

						this.onNext();
					}
				})
				.catch(data =>
				{
					if (data.error.code && data.error.code == -2 && data.error.code == 0) //offline
					{
						console.warn("Wait for online....");
						let sendChuckWhenOnline = () =>
						{
							BX.removeCustomEvent("online", sendChuckWhenOnline);
							this.sendChunk(this.currentChunk);
						};
						BX.addCustomEvent("online", sendChuckWhenOnline);
					}
					else
					{
						error(data);
					}
				});
		},
		uploadPreview: function ()
		{
			return new Promise((resolve) =>
			{
				if (this.fileData.previewUrl)
				{
					BX.FileUtils.readFileByPath(this.fileData.previewUrl, "readAsBinaryString")
						.then(result => resolve(result), () => resolve())
				}
				else
				{
					resolve();
				}
			})
		},
		/**
		 *
		 * @param {Events} event
		 * @param data
		 */
		callListener: function (event, data)
		{
			if (!this.isCancelled())
			{
				if (data && !data.file && this.fileEntry)
				{
					data.file = {
						params: this.fileEntry.params,
						folderId: this.fileEntry.folderId,
					};
				}
				this.lastEventData = {event: event, data: data};
				this.listener(event, data, this);
			}
		}
	};

	BX.FileUploader = function (listener, options)
	{

		/**
		 * file data format:
		 *   url - path to file
		 *   name - name of file
		 *   type - type of file (jpeg, png, pdf and etc.)
		 */


		let uploaderOptions = options || {};
		this.chunk = uploaderOptions.chunk || null;
		this.listener = listener;
		this.queue = [];
	};

	BX.FileUploader.prototype = {
		/**
		 * @param taskId
		 * @returns {BX.FileUploadTask}
		 */
		getTask: function (taskId)
		{
			let tasks = this.queue.filter(task => task.id === taskId);
			if (tasks.length > 0)
			{
				return tasks[0];
			}
			return null;
		},
		/**
		 * @param fileData
		 * @config {string} url path to file
		 * @config {string} name - name of file
		 * @config {string} taskId - task id
		 * @config {string} folderId - folder id
		 * @config {any} params object
		 */
		addTaskFromData: function (fileData)
		{
			if (!fileData.taskId)
			{
				console.error("Add task error: 'taskId' must be defined");
				return;
			}

			let task = new BX.FileUploadTask(fileData, this.chunk);
			this.addTask(task);
		},
		/**
		 * @param taskId
		 */
		cancelTask: function (taskId)
		{
			/**
			 * @type {BX.FileUploadTask}
			 */
			let task = this.queue.find(queueItem => queueItem.id == taskId);

			if (task)
			{
				task.cancel();
			}

		},
		/**
		 * @param {BX.FileUploadTask} taskEntry
		 */
		addTask: function (taskEntry)
		{
			taskEntry.status = Statuses.PENDING;
			taskEntry.listener = (event, data, task) => this.onTaskEvent(event, data, task);
			this.onTaskCreated(taskEntry)
		},
		onTaskEvent: function (event, data, task)
		{
			if (this.listener)
			{
				this.listener(event, data, task);
			}
			this.attemptToStartNextTask();
		},
		onTaskCreated: function (task)
		{
			this.onTaskEvent(TaskEventConsts.TASK_CREATED, {}, task);
			this.queue.push(task);
			this.attemptToStartNextTask();
		},
		attemptToStartNextTask: function ()
		{
			let inProgressTasks = this.queue.filter(queueTask =>
				queueTask.status === Statuses.PROGRESS);
			if (inProgressTasks.length <= 10)
			{
				let pendingTasks = this.queue.filter(queueTask =>
					queueTask.status === Statuses.PENDING);
				if (pendingTasks.length > 0)
				{
					pendingTasks[0].start();
				}
			}
		}
	};

	let FileUploadAgent = {
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

	this.FileUploadAgent = FileUploadAgent;
})();

