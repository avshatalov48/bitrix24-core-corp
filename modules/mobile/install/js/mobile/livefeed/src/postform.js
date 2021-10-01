import {Instance, DatabaseUnsentPostInstance} from "./feed";
import {Type} from 'main.core';

class PostFormManager
{
	constructor()
	{

	}

	show(params)
	{
		let postData = {
			type: (params.type ? params.type : 'post'),
			groupId: (params.groupId ? parseInt(params.groupId) : 0),
			postId: (params.postId ? parseInt(params.postId) : 0),
			pageId: (Type.isStringFilled(params.pageId) ? params.pageId : '')
		};
		this.getDatabaseData(postData).then((postData) => {
			return this.getWorkgroupData(postData);
		}).then((postData) => {
			return this.getPostData(postData);
		}).then((postData) => {
			return this.processPostData(postData);
		}).then((postData) => {
			app.exec("openComponent", {
				name: "JSStackComponent",
				componentCode: "livefeed.postform",
				scriptPath: BX.message('MOBILE_EXT_LIVEFEED_COMPONENT_URL'),
				params: {
					'SERVER_NAME': BX.message('MOBILE_EXT_LIVEFEED_SERVER_NAME'),
					'DESTINATION_LIST': Instance.getOption('destinationList', {}),
					'DESTINATION_TO_ALL_DENY': Instance.getOption('destinationToAllDeny', false),
					'DESTINATION_TO_ALL_DEFAULT': Instance.getOption('destinationToAllDefault', true),
					'MODULE_DISK_INSTALLED': (BX.message('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') == 'Y' ? 'Y' : 'N'),
					'MODULE_WEBDAV_INSTALLED': (BX.message('MOBILE_EXT_LIVEFEED_WEBDAV_INSTALLED') == 'Y' ? 'Y' : 'N'),
					'MODULE_VOTE_INSTALLED': (BX.message('MOBILE_EXT_LIVEFEED_VOTE_INSTALLED') == 'Y' ? 'Y' : 'N'),
					'USE_IMPORTANT': (BX.message('MOBILE_EXT_LIVEFEED_USE_IMPORTANT') === 'N' ? 'N' : 'Y'),
					'FILE_ATTACH_PATH': BX.message('MOBILE_EXT_LIVEFEED_FILE_ATTACH_PATH'),
					'BACKGROUND_IMAGES_DATA': Instance.getOption('backgroundImagesData', {}),
					'BACKGROUND_COMMON': Instance.getOption('backgroundCommon', {}),
					'MEDALS_LIST': Instance.getOption('medalsList', {}),
					'IMPORTANT_DATA': Instance.getOption('importantData', {}),
					'USER_FOLDER_FOR_SAVED_FILES': BX.message('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES'),
					'MAX_UPLOAD_CHUNK_SIZE': BX.message('MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE'),
					'POST_FILE_UF_CODE': BX.message('MOBILE_EXT_LIVEFEED_POST_FILE_UF_CODE'),
					'POST_FORM_DATA': Instance.getOption('postFormData', {}),
					'POST_DATA': postData,
					'DEVICE_WIDTH': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_WIDTH'),
					'DEVICE_HEIGHT': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_HEIGHT'),
					'DEVICE_RATIO': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_RATIO'),
				},
				rootWidget: {
					name: "layout",
					settings: {
						objectName: "postFormLayoutWidget",
						modal: true
					}
				}
			}, false);
		});
	}

	getDatabaseData(postData)
	{
		const promise = new Promise((resolve, reject) =>
		{
			const postId = parseInt(postData.postId);
			if (postId > 0)
			{
				resolve(postData);
				return;
			}

			DatabaseUnsentPostInstance.load(
				{
					onLoad: (data) => {

						if (data.contentType !== postData.type)
						{
							resolve(postData);
							return;
						}

						postData.groupId = 0;

						if (!Type.isPlainObject(postData.post))
						{
							postData.post = {};
						}

						if (Type.isStringFilled(data.POST_TITLE))
						{
							postData.post.PostTitle = data.POST_TITLE;
						}
						if (Type.isStringFilled(data.POST_MESSAGE))
						{
							postData.post.PostDetailText = data.POST_MESSAGE;
						}

						if (
							Type.isArrayFilled(data.DEST)
							&& Type.isPlainObject(data.DEST_DATA)
						)
						{
							postData.post.PostDestination = [];

							const patterns = [
								{
									pattern: /^SG(\d+)$/i,
									style: 'sonetgroups'
								},
								{
									pattern: /^U(\d+|A)$/i,
									style: 'users'
								},
								{
									pattern: /^DR(\d+)$/i,
									style: 'department'
								}
							];

							data.DEST.forEach((item) => {

								let id = null;
								let style = null;

								for (let i = 0; i < patterns.length; i++)
								{
									const matches = item.match(patterns[i].pattern);
									if (matches)
									{
										id = matches[1];
										style = ((item === 'UA') ? 'all-users' : patterns[i].style);
										break;
									}
								}

								if (!Type.isNull(id))
								{
									postData.post.PostDestination.push({
										STYLE: style,
										ID: id,
										TITLE: (Type.isPlainObject(data.DEST_DATA[item]) && Type.isStringFilled(data.DEST_DATA[item].title) ? data.DEST_DATA[item].title : '')
									});
								}
							});
						}

						if (Type.isStringFilled(data.BACKGROUND_CODE))
						{
							postData.post.PostBackgroundCode = data.BACKGROUND_CODE;
						}

						if (data.IMPORTANT === 'Y')
						{
							postData.post.PostImportantData = {
								value: 'Y'
							};

							if (Type.isStringFilled(data.IMPORTANT_DATE_END))
							{
								postData.post.PostImportantData.endDate = Date.parse(data.IMPORTANT_DATE_END) / 1000;
							}
						}

						if (Type.isStringFilled(data.GRATITUDE_MEDAL))
						{
							postData.post.PostGratitudeData = {
								gratitude: data.GRATITUDE_MEDAL,
								employees: []
							};

							if (
								Array.isArray(data.GRATITUDE_EMPLOYEES)
								&& Type.isPlainObject(data.GRATITUDE_EMPLOYEES_DATA)
							)
							{
								data.GRATITUDE_EMPLOYEES.forEach((userId) => {

									const userData = data.GRATITUDE_EMPLOYEES_DATA[userId];
									if (!Type.isPlainObject(userData))
									{
										return;
									}

									postData.post.PostGratitudeData.employees.push({
										id: userData.id,
										imageUrl: (Type.isStringFilled(userData.imageUrl) ? userData.imageUrl : ''),
										title: (Type.isStringFilled(userData.title) ? userData.title : ''),
										subtitle: (Type.isStringFilled(userData.subtitle) ? userData.subtitle : '')
									});
								});
							}
						}

						const voteId = 'n0';
						const dataKey = 'UF_BLOG_POST_VOTE_' + voteId + '_DATA';

						if (
							data.UF_BLOG_POST_VOTE === voteId
							&& Type.isPlainObject(data[dataKey])
							&& Array.isArray(data[dataKey].QUESTIONS)
						)
						{
							postData.post.PostVoteData = {
								questions: data[dataKey].QUESTIONS.map((question) => {
									const result = {
										value: question.QUESTION,
										allowMultiSelect: question.FIELD_TYPE == 1 ? 'Y' : 'N',
										answers: []
									};

									if (Array.isArray(question.ANSWERS))
									{
										result.answers = question.ANSWERS.map((answer) => {
											return {
												value: answer.MESSAGE
											};
										});
									}

									return result;
								})
							};
						}

						resolve(postData);
					},
					onEmpty: () => {
						resolve(postData);
					}
				},
				postData.groupId
			);
		});

		promise.catch((error) => {console.error(error)});

		return promise;
	}

	getWorkgroupData(postData)
	{
		const groupId = parseInt(postData.groupId);

		const promise = new Promise((resolve, reject) =>
		{
			if (groupId <= 0)
			{
				resolve(postData);
				return;
			}

			let promiseData = {
				resolve: resolve,
				reject: reject
			};

			const currentDateTime = new Date();
			const returnEventName = 'Livefeed::returnWorkgroupData_' + currentDateTime.getTime();

			BXMobileApp.addCustomEvent(returnEventName, (function (result) {
				if (result.success)
				{
					this.resolve(Object.assign(postData, {
						group: {
							ID: parseInt(result.groupData.ID),
							NAME: result.groupData.NAME,
//							DESCRIPTION: result.groupData.DESCRIPTION
						}
					}));
				}
				else
				{
					this.reject();
				}
			}).bind(promiseData));

			BXMobileApp.onCustomEvent('Livefeed::getWorkgroupData', {
				groupId: groupId,
				returnEventName: returnEventName
			}, true);
		});

		promise.catch((error) => {console.error(error)});

		return promise;
	}

	getPostData(postData)
	{
		const postId = parseInt(postData.postId);

		const promise = new Promise((resolve, reject) =>
		{
			if (postId <= 0)
			{
				resolve(postData);
				return;
			}

			let promiseData = {
				resolve: resolve,
				reject: reject
			};

			const currentDateTime = new Date();
			const returnEventName = 'Livefeed::returnPostFullData_' + currentDateTime.getTime();

			BXMobileApp.addCustomEvent(returnEventName, (function (result) {
				if (result.success)
				{
					this.resolve(Object.assign(postData, {
						post: result.postData
					}));
				}
				else
				{
					this.reject();
				}
			}).bind(promiseData));

			BXMobileApp.onCustomEvent('Livefeed::getPostFullData', {
				postId: postId,
				returnEventName: returnEventName
			}, true);
		});

		promise.catch((error) => {console.error(error)});

		return promise;
	}

	processPostData(postData)
	{
		const postId = parseInt(postData.postId);

		const promise = new Promise((resolve, reject) =>
		{
			if (Type.isPlainObject(postData.post))
			{
				if (Type.isArray(postData.post.PostDestination))
				{
					postData.recipients = {};

					postData.post.PostDestination.forEach((item) => {

						let key = null;
						let code = null;

						switch (item.STYLE)
						{
							case 'users':
								key = 'users';
								code = item.ID;
								break;
							case 'all-users':
								key = 'users';
								code = 'A';
								break;
							case 'sonetgroups':
								key = 'groups';
								code = item.ID;
								break;
							case 'department':
								key = 'departments';
								code = item.ID;
								break;
							default:
						}

						if (key)
						{
							if (!Type.isArray(postData.recipients[key]))
							{
								postData.recipients[key] = [];
							}
							postData.recipients[key].push({
								id: code,
								title: item.TITLE,
								shortTitle: (Type.isStringFilled(item.SHORT_TITLE) ? item.SHORT_TITLE : item.TITLE),
								avatar: (Type.isStringFilled(item.AVATAR) ? item.AVATAR : ''),
							});
						}
					})
				}

				if (Type.isArray(postData.post.PostDestinationHidden))
				{
					postData.hiddenRecipients = [];

					postData.post.PostDestinationHidden.forEach((item) => {
						postData.hiddenRecipients.push(item.TYPE + item.ID);
					});
				}
			}
			else if (Type.isPlainObject(postData.group))
			{
				postData.recipients = {
					groups: [
						{
							id: postData.group.ID,
							title: postData.group.NAME
						}
					]
				}
			}

			resolve(postData);
		});

		promise.catch((error) => {console.error(error)});

		return promise;
	}
}

export {
	PostFormManager
}
