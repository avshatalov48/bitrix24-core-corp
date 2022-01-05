(() =>
{
	class LivefeedPublicationQueue
	{
		/**
		 * @param params
		 */
		constructor(params = {})
		{
			this.storageId = 'livefeed';
			this.variableId = 'publicationQueue';
			this.storage = Application.storageById(this.storageId);

			this.init();
		}

		init()
		{
			BX.addCustomEvent('Livefeed.PublicationQueue::setItem', this.setItem.bind(this));
			BX.addCustomEvent('onFileUploadStatusChanged', this.onFileUploadStatusChanged.bind(this));

			this.restoreUploadTasks();
		}

		restoreUploadTasks()
		{
			let uploadTasks = [];
			let queue = this.storage.getObject(this.variableId, {});

			for (let virtualId in queue)
			{
				if (
					!queue.hasOwnProperty(virtualId)
					|| queue[virtualId].tasksList.length <= 0
				)
				{
					continue;
				}

				queue[virtualId].tasksList.forEach((taskId, key) => {
					uploadTasks.push({
						taskId: taskId
					})
				});
			}

			if (uploadTasks.length <= 0)
			{
				return;
			}

			BX.onCustomEvent('onFileUploadTaskRequest', {
				files: uploadTasks
			}, true);
		}

		setItem(data)
		{
			const pageId = (data.pageId ? data.pageId : null);

			if (data.item.tasksList.length > 0)
			{
				let queue = this.storage.getObject(this.variableId, {});

				if (data.groupId)
				{
					data.item.groupId = data.groupId;
				}
				queue[data.key] = data.item;
				queue[data.key].pageId = pageId;

				this.storage.setObject(this.variableId, queue);
			}
			else if (data.item.contentType == 'post')
			{
				const postId = (
					data.item.post_id != 'undefined'
					&& parseInt(data.item.post_id) > 0
						? parseInt(data.item.post_id)
						: 0
				);

				if (postId > 0)
				{
					this.updateBlogPost(postId, data.item, {
						key: data.key,
						pageId: pageId,
						pinned: !!data.pinnedContext
					});
				}
				else
				{
					this.addBlogPost(data.item, {
						key: data.key,
						pageId: pageId,
						groupId: (data.groupId ? data.groupId : null)
					});
				}
			}

			BX.postWebEvent('Livefeed.PublicationQueue::afterSetItem', {
				contentType: data.item.contentType,
				key: data.key,
				pageId: pageId,
			});
		}

		onFileUploadStatusChanged(eventName, eventData, eventTaskId)
		{
			if (
				eventName !== TaskEventConsts.FILE_CREATED_FAILED
				&& eventName !== TaskEventConsts.FILE_READ_ERROR
				&& eventName !== TaskEventConsts.FILE_CREATED
			)
			{
				return;
			}

			if (
				!eventData.file
				|| !eventData.file.params
				|| !eventData.file.params.postVirtualId
			)
			{
				return;
			}

			const queue = this.storage.getObject(this.variableId, {});
			const postVirtualId = eventData.file.params.postVirtualId;
			const pinnedContext = !!eventData.file.params.pinnedContext;

			if (!queue[postVirtualId])
			{
				return;
			}

			if (
				eventName === TaskEventConsts.FILE_CREATED_FAILED
				|| eventName === TaskEventConsts.FILE_READ_ERROR
			)
			{
				this.handleFileUploadError({
					key: postVirtualId,
					queue: queue,
					errorText: BX.message('MOBILEAPP_EXT_LIVEFEED_FILE_UPLOAD_ERROR')
				});
			}
			else if (eventName === TaskEventConsts.FILE_CREATED)
			{
				if (eventData.result.status == 'error')
				{
					this.handleFileUploadError({
						key: postVirtualId,
						queue: queue,
						errorText: BX.message('MOBILEAPP_EXT_LIVEFEED_FILE_UPLOAD_ERROR')
					});
				}
				else
				{
					let postData = queue[postVirtualId];

					const ufCode = (postData.ufCode ? postData.ufCode : (postData.contentType == 'post' ? 'UF_BLOG_POST_FILE' : 'UF_BLOG_COMMENT_FILE'));

					if (
						BX.type.isArray(postData.tasksList)
						&& postData.tasksList.length > 0
					)
					{
						postData.tasksList.forEach((taskId, key) => {

							if (taskId == eventTaskId)
							{
								delete postData.tasksList[key];
								postData.tasksList = postData.tasksList.filter((value) => { return value; });

								if (typeof postData[ufCode] == 'undefined')
								{
									postData[ufCode] = [];
								}
								postData[ufCode].push('n' + eventData.result.data.file.id);
							}
						});
					}

					if (
						!BX.type.isArray(postData.tasksList)
						|| postData.tasksList <= 0
					)
					{
						delete queue[postVirtualId];

						if (postData.contentType == 'post')
						{
							let postFields = {
								POST_MESSAGE: postData.POST_MESSAGE,
								DEST: postData.DEST,
							};

							if (
								postData.POST_TITLE
								&& postData.POST_TITLE.length > 0
							)
							{
								postFields.POST_TITLE = postData.POST_TITLE;
							}

							if (
								typeof postData.GRATITUDE_MEDAL !== 'undefined'
								&& typeof postData.GRATITUDE_EMPLOYEES !== 'undefined'
							)
							{
								postFields.GRATITUDE_MEDAL = postData.GRATITUDE_MEDAL;
								postFields.GRATITUDE_EMPLOYEES = postData.GRATITUDE_EMPLOYEES;
							}

							if (typeof postData.IMPORTANT !== 'undefined')
							{
								postFields.IMPORTANT = postData.IMPORTANT;
								if (typeof postData.IMPORTANT_DATE_END !== 'undefined')
								{
									postFields.IMPORTANT_DATE_END = postData.IMPORTANT_DATE_END;
								}
							}

							if (
								typeof postData.UF_BLOG_POST_VOTE !== 'undefined'
								&& typeof postData['UF_BLOG_POST_VOTE_' + postData.UF_BLOG_POST_VOTE + '_DATA'] !== 'undefined'
							)
							{
								postFields.UF_BLOG_POST_VOTE = postData.UF_BLOG_POST_VOTE;
								postFields['UF_BLOG_POST_VOTE_' + postData.UF_BLOG_POST_VOTE + '_DATA'] = postData['UF_BLOG_POST_VOTE_' + postData.UF_BLOG_POST_VOTE + '_DATA'];
							}

							if (typeof postData.BACKGROUND_CODE !== 'undefined')
							{
								postFields.BACKGROUND_CODE = postData.BACKGROUND_CODE;
							}

							if (
								BX.type.isNotEmptyString(ufCode)
								&& BX.type.isArray(postData[ufCode])
							)
							{
								postFields[ufCode] = postData[ufCode];
							}

							if (
								postData.post_id != 'undefined'
								&& parseInt(postData.post_id) > 0
							)
							{
								this.updateBlogPost(postData.post_id, postFields, {
									key: postVirtualId,
									context: 'afterTask',
									pinned: pinnedContext
								});
							}
							else
							{
								this.addBlogPost(postFields, {
									key: postVirtualId,
									context: 'afterTask',
									groupId: (postData.groupId ? postData.groupId : null),
									pageId: (postData.pageId ? postData.pageId : null)
								});
							}
						}
					}
					else
					{
						queue[eventData.file.params.postVirtualId].tasksList = postData.tasksList;
					}

					this.storage.setObject(this.variableId, queue);
				}
			}
		}

		handleFileUploadError(params)
		{
			let queue = params.queue;

			const key = params.key;
			const errorText = params.errorText;

			if (!queue[key])
			{
				return;
			}

			let postData = queue[key];

			if (postData.contentType == 'post')
			{
				if (
					postData.post_id != 'undefined'
					&& parseInt(postData.post_id) > 0
				)
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostUpdateError', {
						errorText: errorText,
						context: 'afterTask',
						key: key,
						postId: postData.post_id,
						postData: postData
					});
				}
				else
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostAddError', {
						errorText: errorText,
						context: 'afterTask',
						key: key,
						postData: postData,
						groupId: (postData.groupId ? postData.groupId : null)
					});
				}
			}
			else // comment
			{

			}

			delete queue[key];
			this.storage.setObject(this.variableId, queue);
		}

		addBlogPost(fields, params)
		{
			let context = (params.context ? params.context : '');
			let key = (params.key ? params.key : null);
			let pageId = (params.pageId ? params.pageId : null);
			let groupId = (params.groupId ? params.groupId : null);

			if (context != 'afterTask')
			{
				BX.postWebEvent('Livefeed::scrollTop', {
					pageId: pageId
				});
			}

			BX.ajax.runAction('socialnetwork.api.livefeed.blogpost.add', {
				data: {
					params: Object.assign({
						MOBILE: 'Y',
						PARSE_PREVIEW: 'Y',
					}, fields)
				},
				analyticsLabel: {
					b24statAction: 'addLogEntry',
					b24statContext: 'mobile'
				}
			}).then(response => {
				let errors = response.errors;
				if (errors && errors.length > 0)
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostAddError', {
						errorText: errors.filter(error => error.customData && error.customData.public === 'Y')
							.map(error => error.message)
							.join("\n"),
						context: context,
						key: key,
						postData: fields,
						groupId: groupId
					});
				}
				else
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostAdd', {
						postId: response.data.id,
						pageId: pageId,
						context: context,
						key: key,
						groupId: groupId,
						warningText: (response.data.warnings ? response.data.warnings : []).join("\n"),
					});
					analytics.send("addLogEntry", {}, ["fbonly"]);
				}
			}).catch(response => {
				let errors = response.errors;
				if (errors && errors.length > 0)
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostAddError', {
						errorText: errors.filter(error => error.customData && error.customData.public === 'Y')
							.map(error => error.message)
							.join("\n"),
						context: context,
						key: key,
						postData: fields,
						groupId: groupId
					});
				}
			});
		}

		updateBlogPost(postId, fields, params)
		{
			let context = (params.context ? params.context : '');
			let key = (params.key ? params.key : null);
			let pageId = (params.pageId ? params.pageId : null);

			if (context != 'afterTask')
			{
				BX.postWebEvent('Livefeed::scrollTop', {
					pageId: pageId
				});
			}

			BX.ajax.runAction('socialnetwork.api.livefeed.blogpost.update', {
				data: {
					id: postId,
					params: Object.assign({
						MOBILE: 'Y',
						PARSE_PREVIEW: 'Y',
					}, fields)
				},
				analyticsLabel: {
					b24statAction: 'editLogEntry',
					b24statContext: 'mobile'
				}
			}).then(response => {
				let errors = response.errors;
				if(errors && errors.length > 0)
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostUpdateError', {
						errorText: errors.filter(error => error.customData && error.customData.public === 'Y')
							.map(error => error.message)
							.join("\n"),
						context: context,
						key: key,
						postId: postId,
						postData: fields
					});
				}
				else
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostUpdate', {
						pageId: pageId,
						context: context,
						postId: postId,
						key: key,
						pinned: (!!params.pinned)
					});
					analytics.send("editLogEntry", {}, ["fbonly"]);
				}

			}).catch(response => {
				let errors = response.errors;
				if(errors && errors.length > 0)
				{
					BX.postWebEvent('Livefeed.PublicationQueue::afterPostUpdateError', {
						errorText: errors.filter(error => error.customData && error.customData.public === 'Y')
							.map(error => error.message)
							.join("\n"),
						context: context,
						key: key,
						postId: postId,
						postData: fields
					});
				}
			});
		}

		getAjaxErrorText(errors)
		{
			return errors.reduce((fullMessage, errorMessage) => {
				errorMessage = errorMessage.message.replace("<br/>:","\n").replace("<br/>","\n");
				fullMessage += `\n${errorMessage}`;
				return fullMessage;
			}, '').substring(1);
		};
	}

	this.LivefeedPublicationQueue = new LivefeedPublicationQueue();
})();