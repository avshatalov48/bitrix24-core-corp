(function()
{
	/**
	 * @bxjs_lang_path mobile_ui_messages.php
	 */
	var loader = function(taskId, container, imageNode, processingNode)
	{
		this.onDestroyEventName = null;
		this.shouldDestroyOnEnd = false;
		this.loaderContainer = BX.create('DIV', { props: { className: 'mobile-uploader-rotating' } });
		this.cancelLayout = BX.create('DIV', {
			props: { className: 'mobile-upload-cancel' },
			attrs: { 'data-task-id': taskId },
		});

		this.loaderWrapper = BX.create(
			'DIV',
			{
				props: { className: 'mobile-uploader-loader-wrapper' },
				children: [
					this.loaderOverlay = BX.create(
						'DIV',
						{
							attrs: { id: `container_${taskId}` },
							props: { className: 'mobile-uploader-loader' },
							children: [this.cancelLayout, this.loaderContainer],
						},
					),
				],
			},
		);

		if (!processingNode)
		{
			processingNode = this.loaderWrapper;
		}

		if (processingNode)
		{
			this.proccessingLabel = BX.create('DIV', {
				props: { className: 'mobile-upload-processing' },
				attrs: { style: 'display:none' },
				html: BX.message('MUI_PROCESSING'),
			});
			this.loaderWrapper.appendChild(this.proccessingLabel);
		}

		BX.bind(this.loaderContainer, 'click', BX.proxy(function()
		{
			if (this.bar.value() < 1)
			{
				BX.MobileUploadProvider.cancelTasks([taskId]);
			}
		}, this));
		this.imageLayout = imageNode;

		if (this.imageLayout)
		{
			BX.addClass(this.imageLayout, 'mobile-uploader-img-blurred');
		}

		container.appendChild(this.loaderWrapper);

		this.createProgress();
		this.setProgress(4);
	};

	loader.prototype.setProcessingVisibility = function (visible, animate)
	{
		if (!this.proccessingLabel)
		{
			return;
		}

		if (visible)
		{
			BX.show(this.proccessingLabel);
		}
		else if (animate === true)
		{
			this.proccessingLabel.style.opacity = 0;
		}
		else
		{
			BX.hide(this.proccessingLabel);
		}
	};

	loader.prototype.createProgress = function()
	{
		this.bar = new BX.ProgressBarJs.Circle(this.loaderContainer, {
			easing: 'linear',
			strokeWidth: 4,
			color: '#ffffff',
			from: { color: '#ffffff' },
			to: { color: '#ffffff' },
			step: BX.proxy(function(state, bar)
			{
				if (bar.value() == 1)
				{
					this.isBeingEnd = true;
					this.cancelLayout.style.transform = 'scale(0)';

					setTimeout(BX.proxy(function()
					{
						BX.addClass(this.cancelLayout, 'mobile-upload-done');
						setTimeout(BX.proxy(function()
						{
							this.cancelLayout.style.transform = 'scale(1)';
							this.isBeingEnd = false;
							if (this.shouldDestroyOnEnd)
							{
								setTimeout(BX.proxy(this.destroy, this), 300);
							}
						}, this), 200);
					}, this), 200);
					if (this.onDestroyEventName == null)
					{
						this.destroy();
					}
				}
			}, this),
		});
	};

	loader.prototype.setProgress = function(percent)
	{
		console.log(percent);
		this.bar.animate(percent / 100, { duration: 500 });
	};

	loader.prototype.setByteSent = function(sent, total)
	{
		this.proccessingLabel.innerHTML = `${(sent / 1024 / 1024).toFixed(2)} MB  / ${(total / 1024 / 1024).toFixed(2)} MB`;
	};

	loader.prototype.destroy = function()
	{
		if (this.isBeingEnd)
		{
			this.shouldDestroyOnEnd = true;
		}
		else
		{
			BX.removeClass(this.imageLayout, 'mobile-uploader-img-blurred');
			this.loaderOverlay.style.opacity = 0;
			this.setProcessingVisibility(false, true);
			this.bar.destroy();
		}
	};

	window.loader = loader;

	BX.MobileUploaderConst = {
		FILE_CREATED: 'onfilecreated',
		FILE_CREATED_FAILED: 'onerrorfilecreate',
		FILE_UPLOAD_PROGRESS: 'onprogress',
		FILE_UPLOAD_START: 'onloadstart',
		FILE_UPLOAD_FAILED: 'onfileuploadfailed',
		FILE_READ_ERROR: 'onfilereaderror',
		FILE_PROCESSING: 'onfileprocessing',
		FILE_PROCESSING_DONE: 'onfileprocessingdone',
		ALL_TASK_COMPLETED: 'oncomplete',
		TASK_TOKEN_DEFINED: 'ontasktokendefined',
		TASK_STARTED_FAILED: 'onloadstartfailed',
		TASK_CREATED: 'ontaskcreated',
		TASK_CANCELLED: 'ontaskcancelled',
		TASK_NOT_FOUND: 'ontasknotfound',
	};
	BX.MobileUploadProvider = {
		/**
		 * Event list:
		 *
		 onfilecreated
		 onerrorfilecreate
		 onprogress
		 onloadstart
		 onfileuploadfailed
		 onfilereaderror
		 oncomplete
		 * @param listener
		 * @example
		 *
		 BX.MobileUploadProvider.setListener(function (eventName, data, taskId)
		 {
			 //handle event
		 });
		 */
		setListener(listener)
		{
			this.listener = listener;
		},
		toBXUrl(path)
		{
			return `bx${path}`;
		},
		cancelTasks(taskIds)
		{
			BXMobileApp.onCustomEvent('onFileUploadTaskCancel', { taskIds }, true);
		},
		removeTasks(tasks)
		{
			tasks.forEach(BX.proxy(function(taskId)
			{
				if (this.loaders[taskId])
				{
					this.loaders[taskId].destroy();
					this.loaders[taskId] = null;
				}

				var taskIndex = this.taskIds.indexOf(taskId);
				if (taskIndex >= 0)
				{
					if (this.taskIds.length == 1)
					{
						this.taskIds = [];
					}
					else
					{
						this.taskIds.splice(taskIndex, 1);
					}
				}
			}, this));
		},
		attachToTasks(tasks)
		{
			this.registerTaskLoaders(tasks);
			BXMobileApp.onCustomEvent('onFileUploadTaskRequest', {files: tasks}, true);
		},
		registerTaskLoaders(tasks)
		{
			this.removeTasks(tasks.map(({ taskId }) => taskId));
			tasks.forEach(BX.proxy(function(task)
			{
				if (task.progressNode)
				{
					this.loaders[task.taskId] = new loader(task.taskId, task.progressNode, task.imageNode, task.progressNode);
					if (task.onDestroyEventName)
					{
						this.loaders[task.taskId].onDestroyEventName = task.onDestroyEventName;
					}
				}
				this.taskIds.push(task.taskId);
			}, this));
		},
		/**
		 * @example
		 * BX.MobileUploadProvider.addTasks([
		 {
			 url: "file://path/to/file", //path to file on device
			 params: {someKey: "someValue"},//some data, feel free to use it
			 name: "text1.txt", //final name of file
			 folderId: "5", //folder id in b24disk
			 taskId: "task2", //must be unique
			 chunk: 1024 * 1024 * 100 //chunk size in bytes
			 progressNode: node, //node to display progress
			 imageNode: node// image node that will be blurred
			 onDestroyEventName: "someEventName"//

		 }
		 ]);
		 * * */
		addTasks(tasks)
		{
			this.registerTaskLoaders(tasks);
			BXMobileApp.Events.postToComponent('onFileUploadTaskReceived', [{ files: tasks }], 'background');
		},
		init()
		{
			this.taskIds = [];
			this.loaders = {};
			BXMobileApp.addCustomEvent('onFileUploadStatusChanged', BX.proxy(function(data)
			{
				if (this.loaders[data.taskId])
				{
					/**
					 * @type {loader} taskLoader
					 */
					var taskLoader = this.loaders[data.taskId];
					if (data.event == this.loaders[data.taskId].onDestroyEventName)
					{
						taskLoader.destroy();
					}
					else if (data.event == BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS)
					{
						if ((data.data.byteTotal / 1024 / 2024) > 2) // more then 2MB
						{
							taskLoader.setProcessingVisibility(true);
							taskLoader.setByteSent(data.data.byteSent, data.data.byteTotal);
						}
						else
						{
							taskLoader.setProcessingVisibility(false);
						}

						taskLoader.setProgress(data.data.percent);
					}
					else if (data.event === 'onfileprocessing')
					{
						taskLoader.setProcessingVisibility(true);
					}
				}

				if (this.listener && this.taskIds.includes(data.taskId))
				{
					this.listener.call(null, data.event, data.data, data.taskId);
				}
			}, this));
		},

	};

	BX.MobileUploadProvider.init();
})();
