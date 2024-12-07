(() => {
	class CommentsUploadQueue
	{
		/**
		 * @param params
		 */
		constructor(params = {})
		{
			this.queue = {};
			this.analyticsData = {};
			this.init();
		}

		init()
		{
			BX.addCustomEvent('Comments.UploadQueue::setItem', this.setItem.bind(this));
			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChanged.bind(this));
		}

		setItem(data = {})
		{
			const {
				formId = null,
				formUniqueId = null,
				entityId = null,
				commentVirtualId = null,
				taskIdList,
				analyticsData = null,
			} = data;

			this.analyticsData = analyticsData;

			if (
				typeof taskIdList === 'undefined'
				|| taskIdList.length <= 0
			)
			{
				BX.postWebEvent('Comments.UploadQueue::ready', {
					commentData: data,
					formId,
					entityId,
					formUniqueId,
					analyticsData: this.analyticsData,
				});
			}
			else
			{
				this.queue[commentVirtualId] = data;
			}
		}

		onFileUploadStatusChanged(eventName, eventData, eventTaskId)
		{
			if (
				eventName !== 'onerrorfilecreate'
				&& eventName !== 'onfilecreated'
			)
			{
				return;
			}

			if (
				!eventData.file
				|| !eventData.file.params
				|| !eventData.file.params.commentVirtualId
			)
			{
				return;
			}

			const commentVirtualId = eventData.file.params.commentVirtualId;

			if (!this.queue[commentVirtualId])
			{
				return;
			}

			if (eventName === 'onerrorfilecreate')
			{
				this.handleFileUploadError({
					commentVirtualId,
					errorText: BX.message('MOBILEAPP_EXT_COMMENTS_FILE_UPLOAD_ERROR'),
				});
			}
			else if (eventName === 'onfilecreated')
			{
				if (eventData.result.status === 'error')
				{
					this.handleFileUploadError({
						commentVirtualId,
						errorText: BX.message('MOBILEAPP_EXT_COMMENTS_FILE_UPLOAD_ERROR'),
					});
				}
				else
				{
					const commentData = this.queue[commentVirtualId];

					if (
						BX.type.isArray(commentData.taskIdList) // uploadTasks
						&& commentData.taskIdList.length > 0
					)
					{
						commentData.taskIdList.forEach(function(taskId, key) {

							if (taskId == eventTaskId)
							{
								delete commentData.taskIdList[key];
								commentData.taskIdList = commentData.taskIdList.filter(function(value) { return value; });

								if (typeof commentData.attachments === 'undefined')
								{
									commentData.attachments = [];
								}
								const file = eventData.result.data.file;

								commentData.attachments.push({
									dataAttributes: {
										ID: file.id,
										IMAGE: typeof file.extra.imagePreviewUri !== 'undefined' ? file.extra.imagePreviewUri : '',
										NAME: file.name,
										URL: {
											URL: typeof file.extra.downloadUri !== 'undefined' ? file.extra.downloadUri : '',
											EXTERNAL: 'YES',
											PREVIEW: typeof file.extra.imagePreviewUri !== 'undefined' ? file.extra.imagePreviewUri : '',
										},
										VALUE: `n${eventData.result.data.file.id}`,
									},
									disk: true,
									name: file.name,
								});
							}
						});
					}

					if (
						!BX.type.isArray(commentData.taskIdList)
						|| commentData.taskIdList.length <= 0
					)
					{
						BX.postWebEvent('Comments.UploadQueue::ready', {
							commentData,
							formId: commentData.formId,
							entityId: commentData.entityId,
							formUniqueId: commentData.formUniqueId,
							analyticsData: this.analyticsData,
						});

						delete this.queue[commentVirtualId];
					}
					else
					{
						this.queue[commentVirtualId].taskIdList = commentData.taskIdList;
					}
				}
			}
		}

		handleFileUploadError(params)
		{
			const commentVirtualId = params.commentVirtualId;
			const errorText = params.errorText;

			if (!this.queue[commentVirtualId])
			{
				return;
			}

			const commentData = this.queue[commentVirtualId];

			BX.postWebEvent('Comments.UploadQueue::error', {
				commentData,
				errorText,
				formId: commentData.formId,
				entityId: commentData.entityId,
				analyticsData: this.analyticsData ? { ...this.analyticsData, status: 'error' } : null,
			});

			delete this.queue[commentVirtualId];
		}
	}

	this.CommentsUploadQueue = new CommentsUploadQueue();
})();
