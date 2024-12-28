// noinspection NpmUsedModulesInstalled

(() => {
	const { ModernFileDataSender, DiskFileDataSender } = jn.require('uploader/sender');
	const userId = BX.componentParameters.get('USER_ID', '0');

	/**
	 *
	 * @param {Object} fileData
	 * @param defaultChunk
	 * @constructor
	 */
	BX.FileUploadTask = function(fileData, defaultChunk)
	{
		if (fileData)
		{
			this.applyData(fileData);
		}

		this.chunkSize = defaultChunk;
		this.fileEntry = null;
		this.listener = (_) => null;
		this.token = null;
		this.status = Statuses.PENDING;
		this.lastEventData = { event: TaskEventConsts.TASK_CREATED, data: {} };
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
		progress: { byteSent: 0, percent: 0 },
		/**
		 * @type {TaskStatus}
		 */
		status: Statuses.PROGRESS,
		lastEventData: {},
		wasProcessed: false,
		beforeCommitAction: null,
		afterCommitAction: null,
		beforeInitAction: null,
		applyData(fileData)
		{
			console.error(fileData);
			this.id = fileData.taskId || '';
			this.fileData = fileData;
		},

		async start()
		{
			this.status = Statuses.PROGRESS;
			this.startTime = Date.now();

			let url = null;
			if (this.fileData.url.startsWith('file://'))
			{
				url = this.fileData.url;
			}
			else
			{
				url = `file://${this.fileData.url}`;
			}

			try
			{
				await this.initFileData();
				if (!this.fileEntry.folderId && !this.fileEntry.controller)
				{
					this.status = Statuses.FAILED;
					this.callListener(TaskEventConsts.TASK_STARTED_FAILED, {
						error: { code: 4, message: "Property 'folderId' or 'controller' are not set" },
					});
				}
				else
				{
					this.callListener(TaskEventConsts.FILE_UPLOAD_START, {});
					this.onNext();
				}
			}
			catch (e)
			{
				this.status = Statuses.FAILED;
				this.callListener(TaskEventConsts.TASK_STARTED_FAILED, {
					error: { code: 0, message: 'Unknown error', error: e },
				});
			}
		},
		cancel()
		{
			this.callListener(TaskEventConsts.TASK_CANCELLED, {});
			this.status = Statuses.CANCELLED;
		},
		isCancelled()
		{
			return this.status == Statuses.CANCELLED;
		},
		isFinalStatus()
		{
			return (
				this.status == Statuses.CANCELLED
				|| this.status == Statuses.DONE
				|| this.status == Statuses.FAILED
			);
		},
		initFileData()
		{
			return new Promise((resolve, reject) => {
				const readError = (e) => {
					this.status = Statuses.FAILED;
					this.callListener(TaskEventConsts.FILE_READ_ERROR, { error: e, file: this.fileData });
					reject();
				};

				if (this.fileData)
				{
					this.beforeInit()
						.then(() => {
							let url = null;
							if (this.fileData.url.startsWith('file://'))
							{
								url = this.fileData.url;
							}
							else
							{
								url = `file://${this.fileData.url}`;
							}

							const name = this.fileData.name ? this.fileData.name : null;
							const mimeType = this.fileData.mimeType ? this.fileData.mimeType : null;

							return BX.FileUtils.fileForReading(url)
								.then((entry) => {
									entry.params = this.fileData.params;
									entry.controller = this.fileData.controller;
									entry.controllerOptions = this.fileData.controllerOptions;
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
								.catch((e) => readError(e));
						});
				}
				else
				{
					readError();
				}
			});
		},
		onNext()
		{
			if (this.isCancelled())
			{
				this.currentChunk = null;
			}
			else
			{
				this.fileEntry.readNext()
					.then((data) => {
						this.currentChunk = data;
						this.sendChunk(data);
					})
					.catch((e) => {
						this.currentChunk = null;
						this.callListener(TaskEventConsts.FILE_UPLOAD_FAILED, { error: e });
					});
			}
		},
		callAction(actionName)
		{
			return new Promise((resolve) => {
				if (typeof this[actionName] === 'function' && this.hasOwnProperty(actionName))
				{
					const promise = this[actionName](this);
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
		beforeInit()
		{
			return this.callAction('beforeInitAction');
		},
		beforeCommit()
		{
			return this.callAction('beforeCommitAction');
		},
		afterCommit()
		{
			return this.callAction('afterCommitAction');
		},
		getFileName()
		{
			if (this.fileData.name)
			{
				// replacing file extension by the real file extension
				return this.fileData.name.replaceAll(/\.(\w+)$/gi, `.${this.fileEntry.getExtension()}`);
			}

			return this.fileEntry.getName();
		},
		sendChunk(data)
		{
			const progress = (e) => {
				const currentTotalSent = data.start + e.loaded;
				this.progress.byteSent = currentTotalSent;
				this.progress.percent = Math.round((currentTotalSent / this.fileEntry.getSize()) * 100);
				this.callListener(TaskEventConsts.FILE_UPLOAD_PROGRESS, {
					percent: Math.round((currentTotalSent / this.fileEntry.getSize()) * 100),
					byteSent: currentTotalSent,
					byteTotal: this.fileEntry.getSize(),
				});
			};

			const error = (e) => {
				this.status = Statuses.FAILED;
				let error = {};
				if (e.xhr)
				{
					error = { message: 'Ajax request error', code: 0 };
				}
				else
				{
					error = e;
				}

				this.callListener(TaskEventConsts.FILE_CREATED_FAILED, { error });
			};

			const failed = (data) => {
				if (data.error && data.error.code && data.error.code === -2 && data.error.code === 0) // offline
				{
					console.warn('Wait for online....');
					const sendChuckWhenOnline = () => {
						BX.removeCustomEvent('online', sendChuckWhenOnline);
						this.sendChunk(this.currentChunk);
					};
					BX.addCustomEvent('online', sendChuckWhenOnline);
				}
				else
				{
					error(data);
				}
			};

			const config = {
				name: this.getFileName(),
				token: this.token,
				type: this.fileEntry.getType(),
				content: data.content,
				start: data.start,
				end: data.end,
				size: this.fileEntry.getSize(),
				folderId: this.fileEntry.folderId,
				disableAutoCommit: this.fileEntry.params?.disableAutoCommit,
				controller: this.fileEntry.controller,
				controllerOptions: this.fileEntry.controllerOptions ? JSON.stringify(this.fileEntry.controllerOptions) : [],
			};

			this.createSender(config)
				.on('chunkUploaded', () => this.onNext())
				.on('newToken', (token) => this.token = token)
				.on('progress', (e) => progress(e))
				.on('error', (e) => error(e))
				.on('failed', (e) => failed(e))
				.on('committed', (result) => {
					this.status = Statuses.DONE;
					this.callListener(TaskEventConsts.FILE_CREATED, { result });
				})
				.send();
		},
		/**
		 * @param config
		 * @returns {BaseFileDataSender}
		 */
		createSender: (config) => {
			if (config.controller)
			{
				return new ModernFileDataSender(config);
			}

			return new DiskFileDataSender(config);
		},
		/**
		 *
		 * @param {Events} event
		 * @param data
		 */
		callListener(event, data)
		{
			if (!this.isCancelled())
			{
				if (data && !data.file && this.fileEntry)
				{
					data.file = {
						attachToEntityController: this.fileData.attachToEntityController,
						params: this.fileEntry.params,
						folderId: this.fileEntry.folderId,
						controller: this.fileEntry.controller,
						controllerOptions: this.fileEntry.controllerOptions,
					};
				}
				this.lastEventData = { event, data };
				this.listener(event, data, this);
			}
		},
	};

	BX.FileUploader = function(listener, options)
	{
		/**
		 * file data format:
		 *   url - path to file
		 *   name - name of file
		 *   type - type of file (jpeg, png, pdf and etc.)
		 */

		const uploaderOptions = options || {};
		this.chunk = uploaderOptions.chunk || null;
		this.listener = listener;
		this.queue = [];
	};

	BX.FileUploader.prototype = {
		/**
		 * @param taskId
		 * @returns {BX.FileUploadTask}
		 */
		getTask(taskId)
		{
			const tasks = this.queue.filter((task) => task.id === taskId);
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
		addTaskFromData(fileData)
		{
			if (!fileData.taskId)
			{
				console.error("Add task error: 'taskId' must be defined");

				return;
			}

			const task = new BX.FileUploadTask(fileData, this.chunk);
			this.addTask(task);
		},
		/**
		 * @param taskId
		 */
		cancelTask(taskId)
		{
			/**
			 * @type {BX.FileUploadTask}
			 */
			const task = this.queue.find((queueItem) => queueItem.id == taskId);

			if (task)
			{
				task.cancel();
			}
		},
		deleteTask(taskId)
		{
			this.queue = this.queue.filter((queueItem) => queueItem.id !== taskId);
		},
		/**
		 * @param {BX.FileUploadTask} taskEntry
		 */
		addTask(taskEntry)
		{
			taskEntry.status = Statuses.PENDING;
			taskEntry.listener = (event, data, task) => this.onTaskEvent(event, data, task);
			this.onTaskCreated(taskEntry);
		},
		onTaskEvent(event, data, task)
		{
			if (this.listener)
			{
				this.listener(event, data, task);
			}
			this.attemptToStartNextTask();
		},
		onTaskCreated(task)
		{
			this.onTaskEvent(TaskEventConsts.TASK_CREATED, {}, task);
			this.queue.push(task);
			this.attemptToStartNextTask();
		},
		attemptToStartNextTask()
		{
			const inProgressTasks = this.queue.filter((queueTask) => queueTask.status === Statuses.PROGRESS);
			if (inProgressTasks.length <= 10)
			{
				const pendingTasks = this.queue.filter((queueTask) => queueTask.status === Statuses.PENDING);
				if (pendingTasks.length > 0)
				{
					pendingTasks[0].start();
				}
			}
		},
	};

	const FileUploadAgent = {
		taskWriteTimeouts: {},
		taskLastTimeUpdate: {},
		taskInfoSaveInterval: 3000,
		db: new ReactDatabase('uploader_tasks', userId, 'en', {
			tasks: {
				name: 'tasks',
				fields: [
					{ name: 'id', unique: true, primary: true },
					{ name: 'value', class: 'BX.FileUploadTask' },
					'userId',
					'date_update',
				],
			},
		}),
		/**
		 * It must set in 5 MB or more because of AWS requirement
		 * @link https://docs.aws.amazon.com/AmazonS3/latest/dev/qfacts.html
		 */
		defaultChunk: 1024 * 1024 * 5,
		init()
		{
			this.FileUploader = new BX.FileUploader(
				(event, data, task) => this.eventHandler(event, data, task),
				{ chunk: this.defaultChunk },
			);
			BX.addCustomEvent(
				'onFileUploadTaskReceived',
				(data) => data.files.forEach((file) => this.FileUploader.addTaskFromData(file)),
			);
			BX.addCustomEvent('onFileUploadTaskRequest', (data) => this.taskRequestHandler(data));
			BX.addCustomEvent('onFileUploadTaskCancel', (data) => this.cancelTasks(data));
			// this.db.table("tasks").then(table => table.delete());
		},
		postFileEvent: (event, data, taskId) => {
			console.info(event, data, taskId);
			BX.postComponentEvent('onFileUploadStatusChanged', [event, data, taskId]);
			BX.postWebEvent('onFileUploadStatusChanged', { event, data, taskId });
		},
		/**
		 *
		 * @param {String} event
		 * @param {Object} data
		 * @param {BX.FileUploadTask} task
		 */
		eventHandler(event, data, task)
		{
			if (task)
			{
				const isNewTask = event === BX.FileUploadEvents.TASK_CREATED;
				if (isNewTask)
				{
					task.beforeInitAction = () => this.resizeIfNeeded(task);
				}

				this.updateTaskInfoInDatabase(task, isNewTask);
			}

			if (event != BX.FileUploadEvents.ALL_TASK_COMPLETED)
			{
				this.postFileEvent(event, data, task.id);
			}
		},
		updateTaskInfoInDatabase(task, create)
		{
			if (create)
			{
				const time = Date.now();
				this.db.table('tasks')
					.then((table) => table.delete({ id: task.id })
						.then(() => table.add({ id: task.id, value: this.prepareTaskForDB(task), date_update: time })));

				this.taskLastTimeUpdate[task.id] = time;
			}
			else
			{
				clearTimeout(this.taskWriteTimeouts[task.id]);
				const update = () => {
					const time = Date.now();
					this.taskLastTimeUpdate[task.id] = time;
					this.db.table('tasks')
						.then((table) => table.update(task.id, { value: this.prepareTaskForDB(task), date_update: time }));
				};

				if (Date.now() - this.taskLastTimeUpdate[task.id] >= this.taskInfoSaveInterval)
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
		prepareTaskForDB(task)
		{
			const preparedTask = { ...task };
			preparedTask.currentChunk = null;
			preparedTask.fileEntry = null;
			preparedTask.token = null;

			return preparedTask;
		},
		taskRequestHandler(data)
		{
			data.files.forEach((file) => {
				/**
				 * @var  BX.FileUploadTask task
				 */
				const fireEvent = (task) => {
					if (task)
					{
						this.postFileEvent(task.lastEventData.event, task.lastEventData.data, file.taskId);
					}
					else
					{
						this.postFileEvent(BX.FileUploadEvents.TASK_NOT_FOUND, {}, file.taskId);
					}
				};

				const task = this.FileUploader.getTask(file.taskId);
				if (task)
				{
					fireEvent(task);
				}
				else
				{
					this.db.table('tasks')
						.then((table) => table.get({ id: file.taskId })
							.then((tasks) => {
								const task = tasks.length > 0 ? tasks[0].VALUE : null;
								if (task)
								{
									console.log('From db1');
									if (task.isFinalStatus())
									{
										console.log(task);
										fireEvent(task);
									}
									else
									{
										console.warn('restart task', task);
										this.FileUploader.addTask(task);
									}
								}
								else
								{
									fireEvent();
								}
							}));
				}
			});
		},
		resizeIfNeeded(task)
		{
			return new Promise((resolve) => {
				if (task.fileData.resize && task.wasProcessed === false)
				{
					const isVideo = (task.fileData.type.toLowerCase() == 'mov' || task.fileData.type.toLowerCase() == 'mp4');
					const resizeParams = {
						width: task.fileData.resize.width,
						height: task.fileData.resize.height,
						url: task.fileData.url,
						isVideo,
						quality: task.fileData.resize.quality,
					};
					const internalResolve = (path) => {
						if (path)
						{
							task.fileData.url = path;
							task.wasProcessed = true;
						}

						task.callListener('onfileprocessingdone', { url: task.fileData.url });
						resolve();
					};
					task.callListener('onfileprocessing', {});
					console.log(resizeParams);
					FileProcessing.resize(task.id, resizeParams)
						.then((filePath) => internalResolve(filePath))
						.catch(() => internalResolve());
				}
				else
				{
					resolve();
				}
			});
		},
		cancelTasks(data)
		{
			data.taskIds.forEach((taskId) => {
				FileProcessing.cancel(taskId);
				this.FileUploader.cancelTask(taskId);
			});
		},
	};

	FileUploadAgent.init();

	this.FileUploadAgent = FileUploadAgent;
})();

/**
 * @module uploader
 */
jn.define('uploader', (require, exports, module) => {
	module.exports = { FileUploadAgent: this.FileUploadAgent };
});
