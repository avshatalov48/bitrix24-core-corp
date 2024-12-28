(function(window)
{
	if (window.BX.MobileTools)
	{
		return;
	}

	BX.MobileTools = {
		getTextBlock(block)
		{
			function decodeHtml(html)
			{
				var txt = document.createElement('textarea');
				txt.innerHTML = html;

				return txt.value;
			}

			var text = block.innerHTML.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '').replace(/<br\s*[\/]?>/gi, "~~~---~~~").replace(/~~~---~~~/g, '\n');
			text = text.replace(/<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/ig, (str, key1, key2) => {
				if (/^http/gi.test(key2))
				{
					return `<a href="${key1}">${key1}</a>`;
				}

				return str;
			});

			return decodeHtml(BX.util.strip_tags(text));
		},
		openChat(dialogId, dialogTitleParams)
		{
			dialogTitleParams = dialogTitleParams || false;
			if (!dialogId)
			{
				return false;
			}

			if (typeof Application === 'undefined')
			{
				Application = BXMobileAppContext;
			}

			console.info('BX.MobileTools.openChat: open chat in JSNative component');
			// eslint-disable-next-line no-undef
			BXMobileApp.Events.postToComponent('onOpenDialog', {
				dialogId,
				dialogTitleParams: dialogTitleParams ? {
					name: dialogTitleParams.name || '',
					avatar: dialogTitleParams.avatar || '',
					color: dialogTitleParams.color || '',
					description: dialogTitleParams.description || '',
				} : false,
			}, 'im.recent');

			const openDialogOptions = {
				dialogId,
			};

			if (dialogTitleParams)
			{
				openDialogOptions.dialogTitleParams = dialogTitleParams;
			}

			// eslint-disable-next-line no-undef
			BXMobileApp.Events.postToComponent(
				'ImMobile.Messenger.Dialog:open',
				openDialogOptions,
				'im.messenger',
			);

			return true;
		},
		phoneTo(number, params)
		{
			params = typeof params === 'object' ? params : {};

			if (!this.canUseTelephony())
			{
				params.callMethod = 'device';
			}

			if (params.callMethod === 'telephony')
			{
				// eslint-disable-next-line no-undef
				BXMobileApp.onCustomEvent('onPhoneTo', { number, params }, true);
			}
			else if (params.callMethod === 'device')
			{
				document.location.href = `tel:${this.correctNumberForPstn(number)}`;
			}
			else
			{
				var sheetButtons = [];

				sheetButtons.push(
					{
						title: BX.message('MOBILE_CALL_BY_B24'),
						callback: function()
						{
							params.callMethod = 'telephony';
							this.phoneTo(number, params);
						}.bind(this),
					},
					{
						title: BX.message('MOBILE_CALL_BY_MOBILE'),
						callback: function()
						{
							params.callMethod = 'device';
							this.phoneTo(number, params);
						}.bind(this),
					},
				);

				// eslint-disable-next-line no-undef
				(new BXMobileApp.UI.ActionSheet({ buttons: sheetButtons }, 'im-phone-menu')).show();
			}
		},
		callTo(userId, video)
		{
			video = typeof video === 'undefined' ? false : video;
			// eslint-disable-next-line no-undef
			BXMobileApp.onCustomEvent('onCallInvite', { userId, video }, true);
		},
		correctNumberForPstn(number)
		{
			if (!BX.type.isNotEmptyString(number))
			{
				return number;
			}

			if (number.length < 10)
			{
				return number;
			}

			if (number.substr(0, 1) === '+')
			{
				return number;
			}

			if (number.substr(0, 3) === '011')
			{
				return number;
			}

			if (number.substr(0, 2) === '82')
			{
				return `+${number}`;
			}
			else if (number.substr(0, 1) === '8')
			{
				return number;
			}

			return `+${number}`;
		},
		canUseTelephony()
		{
			return BX.message('can_perform_calls') === 'Y';
		},
		getMobileUrlParams(url)
		{
			var mobileRegReplace = [
				{
					exp: /\/company\/personal\/user\/(\d+)\/calendar\/\?EVENT_ID=(\d+).*/gi,
					replace: '/mobile/calendar/view_event.php?event_id=$2',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: '/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\/\?commentAction=([a-zA-z]+)/gi,
					replace: '/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\//gi,
					replace: '/mobile/tasks/snmrouter/?routePage=list&USER_ID=$1',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/effective\//gi,
					replace: '/mobile/tasks/snmrouter/?routePage=efficiency',
					useNewStyle: true,
				},
				{
					exp: /\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: '/mobile/tasks/snmrouter/?routePage=view&GROUP_ID=$1&TASK_ID=$2',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\/\?commentId=(\d+)#com(\d+)/gi,
					replace: '/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2&commentId=$3#com$4',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\//gi,
					replace: '/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/log\/(\d+)\//gi,
					replace: '/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=$1',
					useNewStyle: true,
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/[$|#]/gi,
					replace: '/mobile/users/?user_id=$1',
					useNewStyle: true,
				},
				{
					exp: /\/crm\/(deal|lead|company|contact)\/(?:show|details)\/(\d+)\//gi,
					replace: '/mobile/crm/$1/?page=view&$1_id=$2',
					useNewStyle: true,
				},
				{
					exp: /\/workgroups\/group\/(\d+)\//gi,
					replace: '/mobile/?group_id=$1',
					useNewStyle: true,
					params: {
						useSearchBar: true,
						cache: false,
					},
				},
				{
					exp: /\/mobile\/log\/\?group_id=(\d+)/gi,
					replace: '/mobile/?group_id=$1',
					useNewStyle: true,
					params: {
						useSearchBar: true,
						cache: false,
					},
				},
				{
					exp: /(^.*\/((?!mobile\/)[\w.,@?^=%&:\/~+#-])*)(\/knowledge)([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/gi,
					replace: '$1/mobile$3$4',
					useNewStyle: true,
				},
				{
					exp: /(\/mobile\/knowledge\/[\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/gi,
					replace: '$1',
					useNewStyle: true,
					notRequireChanges: true,
				},
			];

			var params = null;
			for (var i = 0; i < mobileRegReplace.length; i++)
			{
				var mobileLink = url.replace(mobileRegReplace[i].exp, mobileRegReplace[i].replace);
				var matchUrl = url.match(mobileRegReplace[i].exp);
				if (
					mobileLink !== url
					|| (mobileRegReplace[i].notRequireChanges && matchUrl !== null)
				)
				{
					params = {
						url: mobileLink,
						bx24ModernStyle: mobileRegReplace[i].useNewStyle,
					};

					if (typeof mobileRegReplace[i].params === 'object' && mobileRegReplace[i].params != null)
					{
						params = Object.assign(params, mobileRegReplace[i].params);
					}
					break;
				}
			}

			return params;
		},
		getOpenFunction(url, params = {})
		{
			var resultOpenFunction = null;
			var resolveList = [
				{
					resolveFunction: BX.MobileTools.userIdFromUrl,
					openFunction(userId) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('onUserProfileOpen', [userId]);
					},
				},
				{
					resolveFunction: BX.MobileTools.memberIdFromSignDocumentUrl,
					openFunction(memberId) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('signbackground::router', memberId);
					},
				},
				{
					resolveFunction: BX.MobileTools.actionFromTaskActionUrl,
					openFunction(data) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('task.view.onCommentAction', data);
					},
				},
				{
					resolveFunction: BX.MobileTools.taskIdFromUrl,
					openFunction(data) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('taskbackground::task::open', [data, params], 'background');
					},
				},
				{
					resolveFunction: BX.MobileTools.userIdFromTaskEfficiencyUrl,
					openFunction(data) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('taskbackground::efficiency::open', data, 'background');
					},
				},
				{
					resolveFunction: BX.MobileTools.diskFromUrl,
					openFunction(params) {
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('onDiskFolderOpen', [params], 'background');
					},
				},
				{
					resolveFunction: BX.MobileTools.diskFileIdFromUrl,
					openFunction(fileId) {
						// eslint-disable-next-line no-undef
						BXMobileApp.UI.Document.open({
							url: `/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=${fileId}`,
						});
					},
				},
				{
					resolveFunction: BX.MobileTools.diskFileKnowledge,
					openFunction(data) {
						const fileId = data.fileId;
						const scope = data.scope;
						const landingId = data.landingId;
						const path = '/bitrix/services/main/ajax.php?action=landing.api.diskFile.info&fileId='
							+ fileId
							+ '&scope='
							+ scope
							+ '&landingId='
							+ landingId;

						BX.ajax.get(path, function(data) {
							if (typeof data === 'string')
							{
								data = JSON.parse(data);
							}

							if (!data.data)
							{
								return;
							}

							app.openDocument({
								url: `/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=${fileId}`,
								filename: data.data.NAME ? data.data.NAME : 'File',
							});
						});

						return false;
					},
				},
				{
					resolveFunction: BX.MobileTools.blockLinkKnowledge,
					openFunction(url) {
						if (url)
						{
							window.location.href = url;
						}
					},
				},
				{
					resolveFunction: BX.MobileTools.projectIdFromUrl,
					openFunction(data) {
						data.siteId = BX.message('SITE_ID');
						data.siteDir = BX.message('SITE_DIR');
						// eslint-disable-next-line no-undef
						BXMobileApp.Events.postToComponent('projectbackground::project::action', data, 'background');
					},
				},
				{
					resolveFunction: BX.MobileTools.getMessengerOpenDialogParamsFromUrl,
					openFunction(params = {}) {
						const dialogId = params.dialogId;
						const userCode = params.userCode;
						const sessionId = params.sessionId;
						const fallbackUrl = params.fallbackUrl;
						const openDialogOptions = {};
						if (params.dialogType === MessengerDialogType.lines)
						{
							openDialogOptions.fallbackUrl = fallbackUrl;
							openDialogOptions.dialogTitleParams = {
								chatType: 'lines',
							};
						}

						if (dialogId)
						{
							openDialogOptions.dialogId = dialogId;

							if (params.messageId)
							{
								openDialogOptions.messageId = params.messageId;
								openDialogOptions.withMessageHighlight = true;
							}

							if (params.dialogType === MessengerDialogType.chat)
							{
								BXMobileApp.Events.postToComponent(
									'ImMobile.Messenger.Dialog:open',
									openDialogOptions,
									'im.messenger',
								);

								return;
							}

							if (params.dialogType === MessengerDialogType.copilot)
							{
								BXMobileApp.Events.postToComponent(
									'ImMobile.Messenger.Dialog:open',
									openDialogOptions,
									'im.copilot.messenger',
								);

								return;
							}

							if (params.dialogType === MessengerDialogType.lines)
							{
								BXMobileApp.Events.postToComponent(
									'ImMobile.Messenger.Openlines:open',
									openDialogOptions,
									'im.messenger',
								);

								return;
							}

							return;
						}

						if (params.dialogType === MessengerDialogType.lines && userCode)
						{
							openDialogOptions.userCode = userCode;

							BXMobileApp.Events.postToComponent(
								'ImMobile.Messenger.Openlines:open',
								openDialogOptions,
								'im.messenger',
							);
						}

						if (params.dialogType === MessengerDialogType.lines && sessionId)
						{
							openDialogOptions.sessionId = sessionId;

							BXMobileApp.Events.postToComponent(
								'ImMobile.Messenger.Openlines:open',
								openDialogOptions,
								'im.messenger',
							);
						}
					},
				},
				{
					resolveFunction: BX.MobileTools.getOpenEventIdFromUrl,
					openFunction(data) {
						const { eventId, eventDate } = data;
						BXMobileApp.Events.postToComponent('calendar::event::open', { eventId, eventDate });
					},
				},
				{
					resolveFunction: BX.MobileTools.getDownloadIcsEventIdFromUrl,
					openFunction(data) {
						const { eventId } = data;
						BXMobileApp.Events.postToComponent('calendar::event::ics', { eventId });
					},
				},
			];

			resolveList.push(
				BX.MobileTools.resolverCrmCondition,
				...BX.MobileTools.resolverBizprocCondition,
			);

			var resolveData = null;
			var inputData = null;
			for (var i = 0; i < resolveList.length; i++)
			{
				resolveData = resolveList[i];
				inputData = resolveData.resolveFunction.apply(null, [url]);

				if (inputData)
				{
					break;
				}
			}

			if (inputData)
			{
				resultOpenFunction = function(){resolveData.openFunction.apply(this, [inputData])};
			}

			return resultOpenFunction;
		},
		resolveOpenFunction(url, loadParams = {})
		{
			const openFunction = BX.MobileTools.getOpenFunction(url, loadParams);

			if (!openFunction)
			{
				const mobileUrlParams = BX.MobileTools.getMobileUrlParams(url);
				const pageLoadParams = mobileUrlParams || { url, ...loadParams };

				// eslint-disable-next-line no-undef
				return () => BXMobileApp.PageManager.loadPageBlank(pageLoadParams, true);
			}

			return openFunction;
		},
		resolverCrmCondition: ({
			resolveFunction: (props) => {
				const url = BX.type.isString(props) ? props : props.url;

				if (!url || !BX.type.isStringFilled(url.trim()))
				{
					return null;
				}

				const supportedEvents = [
					'onCRMDealList',
					'onCRMCompanyList',
					'onCRMContactList',
					'onCRMDealView',
					'onCRMCompanyView',
					'onCRMContactView',
				];

				const eventName = Object.keys(pageViewEvents)
					.reverse()
					.find((key) => pageViewEvents[key].test(url));
				if (supportedEvents.includes(eventName))
				{
					return { url, eventName };
				}

				const isCrmBaseLink = /\/crm\/(deal|company|contact|lead|type)/gi.test(url);
				const isCrmTypeLink = /\/page\/(.*)\/(.*)\/type\/(\d+)\/details\/(\d+)/gi.test(url);

				if (isCrmBaseLink || isCrmTypeLink)
				{
					return { url };
				}

				return null;
			},
			openFunction: (props) => {
				// eslint-disable-next-line no-undef
				BXMobileApp.Events.postToComponent('crmbackground::router', props, 'background');
			},
		}),
		resolverBizprocCondition: ([
			{
				resolveFunction: (url) => {
					const isMyBpTask = /\/company\/personal\/bizproc\/(\d+)\//gi.test(url);
					const isBpTask = /\/company\/personal\/bizproc\/(\d+)\/\?user_id=(\d+)/gi.test(url);

					if (isMyBpTask || isBpTask)
					{
						return { url };
					}

					return null;
				},
				openFunction: (props) => {
					// eslint-disable-next-line no-undef
					BXMobileApp.Events.postToComponent('bizprocbackground::task::open', props, 'background');
				},
			},
			{
				resolveFunction: (url) => {
					if (/\/bizproc\/userprocesses\//gi.test(url))
					{
						return true;
					}

					return null;
				},
				openFunction: () => {
					// eslint-disable-next-line no-undef
					BXMobileApp.Events.postToComponent('bizprocbackground::tab::open', {}, 'background');
				},
			},
		]),

		userIdFromUrl(url)
		{
			var regs = [
				/\/company\/personal\/user\/(\d+)\/($|\?)/i,
				/\/mobile\/users\/\?.*user_id=(\d+)/i,
			];
			var replace = '$1';
			var userId = null;

			for (var i = 0; i < regs.length; i++)
			{
				var reg = regs[i];
				var result = url.match(reg, replace);
				if(result && result.length >= 2)
				{
					userId = result[1];
					break;
				}
			}

			return userId;
		},
		taskIdFromUrl(url)
		{
			var
				messageId = 0,
				messageIdRes = url.match(/\A?MID=([^#&]+)/);

			if (messageIdRes)
			{
				messageId = parseInt(messageIdRes[1], 10);
			}

			if (messageId <= 0)
			{
				messageIdRes = url.match(/\A?commentId=([^#&]+)/);
				if (messageIdRes)
				{
					messageId = parseInt(messageIdRes[1], 10);
				}
			}

			var regs = [
				/\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//i,
				/\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//i,
			];

			for (var i = 0; i < regs.length; i++)
			{
				var result = url.match(regs[i]);
				if(result)
				{
					return {
						taskId: result[2],
						messageId,
					};
				}
			}
		},
		actionFromTaskActionUrl(url)
		{
			var regs = [
				/\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\/\?commentAction=([a-zA-Z]+)&deadline=([0-9]+)/i,
				/\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\/\?commentAction=([a-zA-Z]+)/i,
			];
			for (var i = 0; i < regs.length; i++)
			{
				var result = url.match(regs[i]);
				if (result)
				{
					return {
						userId: result[1],
						taskId: result[2],
						action: result[3],
						deadline: result[4],
					};
				}
			}

			return null;
		},
		userIdFromTaskEfficiencyUrl(url)
		{
			var result = url.match(/\/company\/personal\/user\/(\d+)\/tasks\/effective\//i);
			if (result)
			{
				return {
					userId: result[1],
					groupId: 0,
				};
			}

			return null;
		},
		memberIdFromSignDocumentUrl(url)
		{
			var result = url.match(/\/sign\/link\/member\/(\d+)\//i);
			if (result)
			{
				return result[1];
			}

			return null;
		},
		diskFromUrl(url)
		{
			const regExpMap = [
				{
					regExp: /\/bitrix\/tools\/disk\/focus.php\?.*(folderId|objectId)=(\d+)/i,
					result: {
						url,
					},
				},
				{
					regExp: /\/company\/personal\/user\/(\d+)\/disk\/path\//i,
					result: {
						url,
					},
				},
				{
					regExp: /\/workgroups\/group\/(\d+)\/disk\/path\//i,
					result: {
						entityType: 'group',
						url,
					},
					params: [
						{
							name: 'ownerId',
							key: 1,
						},
					],
				},
				{
					regExp: /\/docs\/(path|shared)\//i,
					result: {
						entityType: 'common',
						ownerId: `shared_files_${BX.message('SITE_ID')}`,
						url,
					},
				},
			];

			for (let i = 0; i < regExpMap.length; i++) {
				const found = url.match(regExpMap[i].regExp);
				const params = regExpMap[i].params;
				const result = regExpMap[i].result || {};

				if (!found)
				{
					continue;
				}

				if (Array.isArray(params))
				{
					params.forEach(({ key, name }) => {
						result[name] = found[key];
					});
				}
				else if (Array.isArray(found))
				{
					found.slice(1).forEach((value, index, array) => {
						if (index % 2 === 0 && index + 1 < array.length)
						{
							result[value] = array[index + 1];
						}
					});
				}

				return result;
			}

			return null;
		},

		diskFileIdFromUrl(url)
		{
			var result = url.match(/\/disk\/showFile\/(\d+)\//i);
			if (result)
			{
				return result[1];
			}

			return null;
		},
		diskFileKnowledge(url)
		{
			const data = [];
			const landingId = document.body.getAttribute('data-landing-id');
			const scope = document.body.getAttribute('data-scope');
			const result = url.match(/(file:)?#diskFile(\d+)/i);

			if (scope && landingId && result)
			{
				data.scope = scope;
				data.landingId = landingId;
				data.fileId = result[2];

				return data;
			}

			return null;
		},
		blockLinkKnowledge(url)
		{
			if (/\/knowledge\/.*#.*$/i.test(url))
			{
				return url;
			}

			return null;
		},
		projectIdFromUrl(url)
		{
			var regs = [
				/\/mobile\/log\/\?group_id=(\d+)/i,
				/\/workgroups\/group\/(\d+)\//i,
			];
			for (var i = 0; i < regs.length; i++)
			{
				var result = url.match(regs[i]);
				if (result)
				{
					return {
						projectId: parseInt(result[1], 10),
						action: 'view',
					};
				}
			}

			return null;
		},
		getMessengerOpenDialogParamsFromUrl(url)
		{
			const chatRegs = [
				/\/online\/\?IM_DIALOG=(\d+|chat\d+)&IM_MESSAGE=(\d+)/i,
				/\/online\/\?IM_DIALOG=(\d+|chat\d+)/i,
				/\/online\/\?IM_COPILOT=(\d+|chat\d+)&IM_MESSAGE=(\d+)/i,
				/\/online\/\?IM_COPILOT=(\d+|chat\d+)/i,
				/\/online\/\?IM_LINES=(chat\d+)&IM_MESSAGE=(\d+)/i,
				/\/online\/\?IM_LINES=(chat\d+)/i,
			];

			const openlinesPrefix = 'imol|';
			const checkIsOpenLineSessionId = (dialogId) => {
				if (!(typeof dialogId === 'string' && dialogId !== ''))
				{
					return false;
				}

				if (!dialogId.startsWith(openlinesPrefix))
				{
					return false;
				}

				const sessionIdParts = dialogId.split(openlinesPrefix);
				if (sessionIdParts.length !== 2)
				{
					return false;
				}

				const sessionId = Number(sessionIdParts[1]);

				return !Number.isNaN(sessionId) && typeof sessionId === 'number';
			};

			const checkIsOpenLineUserCode = (dialogId) => {
				return !checkIsOpenLineSessionId(dialogId) && dialogId.startsWith(openlinesPrefix);
			};

			for (const reg of chatRegs)
			{
				const result = url.match(reg);
				if (!result)
				{
					continue;
				}

				let dialogType;
				if (result[0].includes('IM_COPILOT'))
				{
					dialogType = MessengerDialogType.copilot;
				}
				else if (result[0].includes('IM_LINES'))
				{
					dialogType = MessengerDialogType.lines;
				}
				else
				{
					dialogType = MessengerDialogType.chat;
				}

				const dialogId = result[1];
				const messageId = result[2];
				const openDialogParams = {
					dialogType,
					dialogId,
					fallbackUrl: url,
				};

				if (messageId)
				{
					openDialogParams.messageId = parseInt(messageId, 10);
				}

				return openDialogParams;
			}

			const openLineRegs = [
				/\/online\/\?IM_DIALOG=imol([^&]+)&IM_MESSAGE=(\d+)/i,
				/\/online\/\?IM_DIALOG=imol([^&]+)/i,
				/\/online\/\?IM_HISTORY=imol([^&]+)/i,
			];
			for (const reg of openLineRegs)
			{
				const result = url.match(reg);
				if (!result)
				{
					continue;
				}

				const openDialogParams = {
					dialogType: MessengerDialogType.lines,
					fallbackUrl: url,
				};

				const dialogId = `imol${result[1]}`;
				if (checkIsOpenLineUserCode(dialogId))
				{
					openDialogParams.userCode = dialogId;
				}
				else if (checkIsOpenLineSessionId(dialogId))
				{
					openDialogParams.sessionId = Number(dialogId.replace(openlinesPrefix, ''));
				}

				const messageId = result[2];
				if (messageId)
				{
					openDialogParams.messageId = parseInt(messageId, 10);
				}

				return openDialogParams;
			}

			return null;
		},
		getOpenEventIdFromUrl(url)
		{
			const calendarRegs = [
				/\/calendar\/\?EVENT_ID=(\d+)&EVENT_DATE=([^&]+)/i,
				/\/calendar\/\?EVENT_ID=(\d+)/i,
			];

			for (const reg of calendarRegs)
			{
				const result = url.match(reg);
				if (!result)
				{
					continue;
				}

				const eventId = result[1];
				const eventDate = result[2];

				return { eventId, eventDate };
			}
		},
		getDownloadIcsEventIdFromUrl(url)
		{
			const regexp = /calendar\/ics\/\?EVENT_ID=(\d+)/i;
			const isValid = regexp.test(url);

			if (!isValid)
			{
				return null;
			}

			const result = url.match(regexp);
			const eventId = parseInt(result[1], 10);

			if (!eventId)
			{
				return null;
			}

			return { eventId };
		},
		createCardScanner(options)
		{
			return new (function scanner()
			{
				this.onError = function(e)
				{
					console.error('Error', e);
				};

				this.stripEmptyFields = options.stripEmptyFields || false;
				this.options = options;
				this.imageData = null;

				if (options.onResult)
				{
					this.onResult = options.onResult;
				}

				if (options.onError)
				{
					this.onError = options.onError;
				}

				if (options.onImageGet)
				{
					this.onImageGet = options.onImageGet;
				}

				this.open = function()
				{
					app.exec('openBusinessCardScanner', {
						callback: BX.proxy(function(data)
						{
							if (data.canceled != 1 && data.url.length > 0)
							{
								this.imageData = data;

								if (this.options.onImageGet)
								{
									this.onImageGet(data);
								}

								this.send();
							}
						}, this),
					});
				};

				this.send = function()
				{
					if (this.options.url)
					{
						var uploadOptions = new FileUploadOptions();
						uploadOptions.fileKey = 'card_file';
						uploadOptions.fileName = 'image.jpg';
						uploadOptions.mimeType = 'image/jpeg';
						uploadOptions.chunkedMode = false;
						uploadOptions.params = {
							image: 'Y',
						};

						var ft = new FileTransfer();

						ft.upload(this.imageData.url, this.options.url, BX.proxy(function(data)
						{
							try
							{
								var response = JSON.parse(data.response);
								this.UNIQUE_ID = response.UNIQUE_ID;
								if (response.STATUS !== 'success')
								{
									if (response.ERROR)
									{
										this.onError(response.ERROR);
									}

									return;
								}

								this.options.onImageUploaded(response);

								// eslint-disable-next-line no-undef
								BXMobileApp.addCustomEvent('onPull-bizcard', this.handler);
							}
							catch (e)
							{
								this.onError(e);
							}
						}, this), BX.proxy(function(data)
						{
							this.onError({
								code: data.code,
								message: "Can't upload image",
							});
						}, this), uploadOptions);
					}
				};

				this.handler = BX.proxy(function(recognizeData)
				{
					var result = recognizeData.params.RESULT;

					if (!result.ERROR && result.UNIQUE_ID === this.UNIQUE_ID)
					{
						BX.removeCustomEvent('onPull-bizcard', this.handler);

						if (typeof this.onResult === 'function')
						{
							var data = result.DATA;
							var modifiedResult = {
								DATA: {},
								CARD_ID: result.CARD_ID,
							};

							if (typeof data === 'object')
							{
								if (this.stripEmptyFields)
								{
									var strippedResult = {};

									for (var key in data)
									{
										if (data[key] !== '')
										{
											strippedResult[key] = data[key];
										}
									}

									modifiedResult.DATA = strippedResult;
								}
								else
								{
									modifiedResult.DATA = data;
								}

								this.onResult(modifiedResult);
							}
							else
							{
								this.onError(result);
							}
						}
					}
				}, this);
			})();
		},
		requestUserCounters()
		{
			// eslint-disable-next-line no-undef
			BXMobileApp.onCustomEvent('requestUserCounters', { web: true }, true);
		},
		getDesktopStatus()
		{
			return new Promise((resolve) => {
				var responseHandler = function(response)
				{
					resolve(response);
					// eslint-disable-next-line no-undef
					BXMobileApp.Events.unsubscribe('onRequestDesktopStatus');
				};
				// eslint-disable-next-line no-undef
				BXMobileApp.Events.addEventListener('onRequestDesktopStatus', responseHandler);
				// eslint-disable-next-line no-undef
				BXMobileApp.Events.postToComponent('requestDesktopStatus', {
					web: true,
				}, 'communication');
			});
		},
		openDesktopPage(url)
		{
			return BX.rest.callMethod('im.desktop.page.open', { url });
		},
	};

	var MessengerDialogType = Object.freeze({
		chat: 'chat',
		copilot: 'copilot',
		channel: 'channel',
		lines: 'lines',
	});

	var pageViewEvents = {
		onLiveFeedFavoriteView: /\/mobile\/index.php\?favorites=y/gi,
		onCalendarEventView: /\/mobile\/calendar\/view_event.php\?event_id=(\d+)*/gi,
		// tasks
		onTaskView: /\/mobile\/tasks\/snmrouter\/\?routepage=view(.*)task_id=/gi,
		onTaskListView: /\/mobile\/tasks\/snmrouter\/\?routepage=roles/gi,
		onTaskCreate: /\/mobile\/tasks\/snmrouter\/\?routepage=edit(.*)task_id=0/gi,
		onTaskEdit: /\/mobile\/tasks\/snmrouter\/\?routepage=edit(.*)task_id=(\d+)/gi,
		// profile
		onUserProfileView: /\/mobile\/users\/\?user_id=(.*)/gi,
		// crm
		onCRMInvoiceList: /\/mobile\/crm\/invoice/gi,
		onCRMLeadList: /\/mobile\/crm\/lead/gi,
		onCRMDealList: /\/mobile\/crm\/deal/gi,
		onCRMContactList: /\/mobile\/crm\/contact/gi,
		onCRMCompanyList: /\/mobile\/crm\/company/gi,
		onCRMActivityList: /\/mobile\/crm\/activity\/list.php/gi,
		onCRMContactView: /\/mobile\/crm\/contact\/\?page=view&contact_id=/gi,
		onCRMDealView: /\/mobile\/crm\/deal\/\?page=view&deal_id=/gi,
		onCRMCompanyView: /\/mobile\/crm\/company\/\?page=view&company_id=/gi,
		onCRMLeadView: /\/mobile\/crm\/lead\/\?page=view&lead_id=/gi,
		onCRMQuoteView: /\/mobile\/crm\/quote\/\?page=view&quote_id=/gi,
		onCRMProductView: /\/mobile\/crm\/product\/\?page=view&product_id=/gi,
		onGroupView: /\/mobile\/\?group_id=(.*)/gi,
		onBizProcListView: /\/mobile\/bp\/\?user_status=0$/gi,
	};

	var getEventByUrl = function(url)
	{
		for (var eventName in pageViewEvents)
		{
			if (pageViewEvents[eventName].test(url))
			{
				return eventName;
			}
		}

		return null;
	};

	// Analytics

	var originalLoadPageBlank = app.loadPageBlank;
	var originalLoadPageStart = app.loadPageStart;
	var originalShowModalDialog = app.showModalDialog;

	if (window.mwebrtc)
	{
		var origCallInvite = window.mwebrtc.callInvite;
		window.mwebrtc.callInvite = function()
		{
			var eventName = `Outgoing${arguments[1] === true ? 'Video' : 'Audio'}Call`;
			origCallInvite.apply(window.mwebrtc, arguments);

			if (eventName && typeof fabric !== 'undefined')
			{
				fabric.Answers.sendCustomEvent(eventName, {});
			}
		};
	}

	const fixEventByUrl = function(params)
	{
		const url = (typeof params === 'object') ? params.url : params;
		const eventName = getEventByUrl(url);
		if (eventName && typeof fabric !== 'undefined')
		{
			fabric.Answers.sendCustomEvent(eventName, {});
		}
	};

	app.showModalDialog = function(params)
	{
		BX.proxy(originalShowModalDialog, app)(params);
		fixEventByUrl(params);
	};

	app.loadPageBlank = function(params)
	{
		BX.proxy(originalLoadPageBlank, app)(params);
		fixEventByUrl(params);
	};

	app.loadPageStart = function(params)
	{
		BX.proxy(originalLoadPageStart, app)(params);
		fixEventByUrl(params);
	};
})(window);
