(function (window)
{
	if (window.BX.MobileTools) return;

	BX.MobileTools = {
		getTextBlock: function(block)
		{
			function decodeHtml(html) {
				var txt = document.createElement("textarea");
				txt.innerHTML = html;
				return txt.value;
			}

			var text = block.innerHTML.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '').replace(/<br\s*[\/]?>/gi, "~~~---~~~").replace(/~~~---~~~/g, "\n");
			text = text.replace(/<a\s+href="([^"]+)"[^>]*>([^<]+)<\/a>/ig, function (str, key1, key2) {
				if (key2.match(/^http/ig))
				{
					return '<a href="' + key1 + '">' + key1 + '</a>'
				}
				else
				{
					return str;
				}
			});

			return decodeHtml(BX.util.strip_tags(text));
		},
		openChat: function (dialogId, dialogTitleParams)
		{
			dialogTitleParams = dialogTitleParams || false;
			if (!dialogId)
				return false;

			if (typeof Application == "undefined")
			{
            	Application = BXMobileAppContext;
			}

			if (Application.getApiVersion() >= 25)
			{
				console.info('BX.MobileTools.openChat: open chat in JSNative component');
				BXMobileApp.Events.postToComponent("onOpenDialog", {
					dialogId : dialogId,
					dialogTitleParams : dialogTitleParams? {
						name: dialogTitleParams.name || '',
						avatar: dialogTitleParams.avatar || '',
						color: dialogTitleParams.color || '',
						description: dialogTitleParams.description || ''
					}: false
				}, 'im.recent');

				const openDialogOptions = {
					dialogId,
				};

				if (dialogTitleParams)
				{
					openDialogOptions.dialogTitleParams = dialogTitleParams;
				}

				BXMobileApp.Events.postToComponent(
					'ImMobile.Messenger.Dialog:open',
					openDialogOptions,
					'im.messenger'
				);
			}
			else
			{
				console.info('BX.MobileTools.openChat: open new browser page (legacy)');
				BXMobileApp.PageManager.loadPageUnique({
					url : (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/im/dialog.php'+(!app.enableInVersion(11)? "?id="+dialogId:""),
					bx24ModernStyle : true,
					data: {dialogId: dialogId}
				});
			}

			return true;
		},
		phoneTo: function (number, params)
		{
			params = typeof(params) == 'object' ? params : {};

			if (!this.canUseTelephony())
			{
				params.callMethod = 'device';
			}

			if (params.callMethod == 'telephony')
			{
				BXMobileApp.onCustomEvent("onPhoneTo", {number: number, params: params}, true);
			}
			else if (params.callMethod == 'device')
			{
				document.location.href = "tel:" + this.correctNumberForPstn(number);
			}
			else
			{
				var sheetButtons = [];

				sheetButtons.push({
					title: BX.message("MOBILE_CALL_BY_B24"),
					callback: function ()
					{
						params.callMethod = 'telephony';
						this.phoneTo(number, params);
					}.bind(this)
				});
				sheetButtons.push({
					title: BX.message("MOBILE_CALL_BY_MOBILE"),
					callback: function ()
					{
						params.callMethod = 'device';
						this.phoneTo(number, params);
					}.bind(this)
				});

				(new BXMobileApp.UI.ActionSheet({buttons: sheetButtons}, "im-phone-menu")).show();
			}
		},
		callTo: function (userId, video)
		{
			video = typeof(video) == 'undefined' ? false : video;
			BXMobileApp.onCustomEvent("onCallInvite", {userId: userId, video: video}, true);
		},
		correctNumberForPstn: function (number)
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
				return '+' + number;
			}
			else if (number.substr(0, 1) === '8')
			{
				return number;
			}

			return '+' + number;
		},
		canUseTelephony: function ()
		{
			return BX.message('can_perform_calls') === 'Y';
		},
		getMobileUrlParams: function (url)
		{
			var mobileRegReplace = [
				{
					exp: /\/company\/personal\/user\/(\d+)\/calendar\/\?EVENT_ID=(\d+).*/gi,
					replace: "/mobile/calendar/view_event.php?event_id=$2",
					useNewStyle: false
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\/\?commentAction=([a-zA-z]+)/gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=list&USER_ID=$1",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/effective\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=efficiency",
					useNewStyle: true
				},
				{
					exp: /\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&GROUP_ID=$1&TASK_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\/\?commentId=(\d+)#com(\d+)/gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2&commentId=$3#com$4",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\//gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/log\/(\d+)\//gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=$1",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/[$|#]/gi,
					replace: "/mobile/users/?user_id=$1",
					useNewStyle: true
				},
				{
					exp: /\/crm\/(deal|lead|company|contact)\/(?:show|details)\/(\d+)\//gi,
					replace: "/mobile/crm/$1/?page=view&$1_id=$2",
					useNewStyle: true
				},
				{
					exp: /\/workgroups\/group\/(\d+)\//gi,
					replace: "/mobile/?group_id=$1",
					useNewStyle: true,
					params:{
						useSearchBar:true,
						cache:false
					}
				},
				{
					exp: /\/mobile\/log\/\?group_id=(\d+)/gi,
					replace: "/mobile/?group_id=$1",
					useNewStyle: true,
					params:{
						useSearchBar:true,
						cache:false
					}
				},
				{
					exp: /(^.*\/((?!mobile\/)[\w.,@?^=%&:\/~+#-])*)(\/knowledge)([\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/gi,
					replace: "$1/mobile$3$4",
					useNewStyle: true
				},
				{
					exp: /(\/mobile\/knowledge\/[\w.,@?^=%&:\/~+#-]*[\w@?^=%&\/~+#-])/gi,
					replace: "$1",
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
					mobileLink != url
					|| (mobileRegReplace[i].notRequireChanges && matchUrl !== null)
				)
				{
					params = {
						url: mobileLink,
						bx24ModernStyle: mobileRegReplace[i].useNewStyle
					};

					if(typeof mobileRegReplace[i].params == "object" && mobileRegReplace[i].params != null)
					{
						params = Object.assign(params, mobileRegReplace[i].params);
					}
					break;
				}
			}

			return params;
		},
		getOpenFunction: function (url)
		{
			var resultOpenFunction = null;
			var resolveList = [
				{
					resolveFunction: BX.MobileTools.userIdFromUrl,
					openFunction: function(userId) {
						BXMobileApp.Events.postToComponent("onUserProfileOpen", [userId]);
					}
				},
				{
					resolveFunction: BX.MobileTools.actionFromTaskActionUrl,
					openFunction: function(data) {
						BXMobileApp.Events.postToComponent("task.view.onCommentAction", data, "tasks.view");
						BXMobileApp.Events.postToComponent("task.view.onCommentAction", data, "tasks.task.tabs");
					}
				},
				{
					resolveFunction: BX.MobileTools.taskIdFromUrl,
					openFunction: function(data) {
						BXMobileApp.Events.postToComponent("taskbackground::task::open", data, "background");
					}
				},
				{
					resolveFunction: BX.MobileTools.userIdFromTaskEfficiencyUrl,
					openFunction: function(data) {
						BXMobileApp.Events.postToComponent("taskbackground::efficiency::open", data, "background");
					}
				},
				{
					resolveFunction: BX.MobileTools.diskFromUrl,
					openFunction: function(params) {
						BXMobileApp.Events.postToComponent("onDiskFolderOpen", [params], "background");
					}
				},
				{
					resolveFunction: BX.MobileTools.diskFileIdFromUrl,
					openFunction: function(fileId) {
						BXMobileApp.UI.Document.open({
							url: "/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=" + fileId
						})
					}
				},
				{
					resolveFunction: BX.MobileTools.diskFileKnowledge,
					openFunction: function(data) {
						const fileId = data['fileId'];
						const scope = data['scope'];
						const landingId = data['landingId'];
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
								url: "/mobile/ajax.php?mobile_action=disk_download_file&action=downloadFile&fileId=" + fileId,
								filename: data.data.NAME ? data.data.NAME : "File"
							});
						});

						return false;
					}
				},
				{
					resolveFunction: BX.MobileTools.blockLinkKnowledge,
					openFunction: function(url) {
						if (url)
						{
							window.location.href = url;
						}
					}
				},
				{
					resolveFunction: BX.MobileTools.projectIdFromUrl,
					openFunction: function(data) {
						data.siteId = BX.message('SITE_ID');
						data.siteDir = BX.message('SITE_DIR');
						BXMobileApp.Events.postToComponent('projectbackground::project::action', data, 'background');
					},
				},
			];

			if (Application.getApiVersion() >= 45)
			{
				resolveList.push(BX.MobileTools.resolverCrmCondition);
			}

			var resolveData = null;
			var inputData = null;
			for (var i = 0; i < resolveList.length; i++)
			{
				resolveData = resolveList[i];
				inputData = resolveData.resolveFunction.apply(null, [url]);

				if(inputData)
				{
					break;
				}
			}

			if(inputData)
			{
				resultOpenFunction = function(){resolveData.openFunction.apply(this, [inputData])};
			}


			return resultOpenFunction;
		},
		resolveOpenFunction: function(url, loadParams = {})
		{
			const openFunction = BX.MobileTools.getOpenFunction(url);

			if(!openFunction)
			{
				const mobileUrlParams = BX.MobileTools.getMobileUrlParams(url);
				const pageLoadParams = mobileUrlParams || { url, ...loadParams };

				return () => BXMobileApp.PageManager.loadPageBlank(pageLoadParams, true);
			}

			return openFunction;
		},
		resolverCrmCondition: ({
			resolveFunction: (props) => {
				const url = BX.type.isString(props) ? props : props.url;

				if(!url || !BX.type.isStringFilled(url.trim()))
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

				const isValidLink = /\/crm\/(deal|company|contact|lead|type)/gi.test(url);
				if(isValidLink)
				{
					return { url };
				}

				return null;

			},
			openFunction: (props) => {
				BXMobileApp.Events.postToComponent('crmbackground::router', props, 'background');
			},
		}),

		userIdFromUrl:function(url)
		{
			var regs = [
				/\/company\/personal\/user\/(\d+)\/($|\?)/i,
				/\/mobile\/users\/\?.*user_id=(\d+)/i
			];
			var replace =  "$1";
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
		taskIdFromUrl:function(url)
		{
			var
				messageId = 0,
				messageIdRes = url.match(/\A?MID=([^#&]+)/);

			if (messageIdRes)
			{
				messageId = parseInt(messageIdRes[1]);
			}
			if (messageId <= 0)
			{
				messageIdRes = url.match(/\A?commentId=([^#&]+)/);
				if (messageIdRes)
				{
					messageId = parseInt(messageIdRes[1]);
				}
			}

			var regs = [
				/\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//i,
				/\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//i
			];

			for (var i = 0; i < regs.length; i++)
			{
				var result = url.match(regs[i]);
				if(result)
				{
					return {
						taskId: result[2],
						messageId: messageId
					};
				}
			}
		},
		actionFromTaskActionUrl: function(url)
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
						deadline: result[4]
					};
				}
			}

			return null;
		},
		userIdFromTaskEfficiencyUrl: function(url)
		{
			var result = url.match(/\/company\/personal\/user\/(\d+)\/tasks\/effective\//i);
			if (result)
			{
				return {
					userId: result[1],
					groupId: 0
				};
			}

			return null;
		},
		diskFromUrl:function(url)
		{
			const regExpMap = [
				{
					regExp: /\/bitrix\/tools\/disk\/focus.php\?.*(folderId|objectId)=(\d+)/i,
					params: [
						{
							name: 'folderId',
							key: 2,
						}
					],
				},
				{
					regExp: /\/company\/personal\/user\/(\d+)\/disk\/path\//i,
					result: {}
				},
				{
					regExp: /\/workgroups\/group\/(\d+)\/disk\/path\//i,
					result: {
						entityType: 'group',
					},
					params: [
						{
							name: 'ownerId',
							key: 1,
						},
					]
				},
				{
					regExp: /\/docs\/(path|shared)\//i,
					result: {
						entityType: 'common',
						ownerId: `shared_files_${BX.message('SITE_ID')}`
					}
				},
			];

			for (let i = 0; i < regExpMap.length; i++) {
				const found = url.match(regExpMap[i].regExp);
				const params = regExpMap[i].params;
				const result = regExpMap[i].result || {};

				if(!found) {
					continue;
				}

				if(Array.isArray(params))
				{
					params.forEach(({key, name}) => {
						result[name] = found[key];
					});
				}

				return result;

			}

			return null;
		},

		diskFileIdFromUrl:function(url)
		{
			var result = url.match(/\/disk\/showFile\/(\d+)\//i);
			if(result)
			{
				return result[1];
			}

			return null;
		},
		diskFileKnowledge:function(url)
		{
			const data = [];
			const landingId = document.body.getAttribute('data-landing-id');
			const scope = document.body.getAttribute('data-scope');
			const result = url.match(/(file:)?#diskFile(\d+)/i);

			if (scope && landingId && result)
			{
				data['scope'] = scope;
				data['landingId'] = landingId;
				data['fileId'] = result[2];
				return data;
			}
			else
			{
				return null;
			}
		},
		blockLinkKnowledge:function(url)
		{
			if (url.match(/\/knowledge\/.*#.*$/i))
			{
				return url;
			}

			return null;
		},
		projectIdFromUrl: function(url)
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
						projectId: parseInt(result[1]),
						action: 'view',
					};
				}
			}

			return null;
		},
		createCardScanner: function (options)
		{
			return new (function scanner()
			{

				this.onError = function (e)
				{
					console.error("Error", e);
				};

				this.stripEmptyFields = options.stripEmptyFields || false;
				this.options = options;
				this.imageData = null;

				if (options["onResult"])
				{
					this.onResult = options["onResult"];
				}

				if (options["onError"])
				{
					this.onError = options["onError"];
				}
				if (options["onImageGet"])
				{
					this.onImageGet = options["onImageGet"];
				}
				this.open = function ()
				{
					app.exec("openBusinessCardScanner", {
						callback: BX.proxy(function (data)
						{

							if (data.canceled != 1 && data.url.length > 0)
							{
								this.imageData = data;

								if (this.options["onImageGet"])
								{
									this.onImageGet(data);
								}

								this.send();
							}

						}, this)
					});
				};

				this.send = function ()
				{
					if (this.options.url)
					{
						var uploadOptions = new FileUploadOptions();
						uploadOptions.fileKey = "card_file";
						uploadOptions.fileName = "image.jpg";
						uploadOptions.mimeType = "image/jpeg";
						uploadOptions.chunkedMode = false;
						uploadOptions.params = {
							image: "Y"
						};

						var ft = new FileTransfer();

						ft.upload(this.imageData.url, this.options.url, BX.proxy(function (data)
						{
							try
							{
								var response = JSON.parse(data.response);
								this.UNIQUE_ID = response.UNIQUE_ID;
								if (response.STATUS != "success")
								{
									if (response.ERROR)
									{
										this.onError(response.ERROR);
									}

									return;
								}
								else
								{
									this.options["onImageUploaded"](response);
								}

								BXMobileApp.addCustomEvent("onPull-bizcard", this.handler);
							}
							catch (e)
							{
								this.onError(e);
							}
						}, this), BX.proxy(function (data)
						{
							this.onError({
								"code": data.code,
								"message": "Can't upload image"
							});

						}, this), uploadOptions);
					}

				};

				this.handler = BX.proxy(function (recognizeData)
				{
					var result = recognizeData.params.RESULT;

					if (!result.ERROR && result.UNIQUE_ID == this.UNIQUE_ID)
					{
						BX.removeCustomEvent("onPull-bizcard", this.handler);

						if (typeof this.onResult == "function")
						{
							var data = result.DATA;
							var modifiedResult = {
								DATA: {},
								CARD_ID: result.CARD_ID
							};

							if (typeof data == "object")
							{
								if (this.stripEmptyFields)
								{
									var strippedResult = {};

									for (var key in data)
									{
										if (data[key] != "")
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

								this.onResult(modifiedResult)
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
		requestUserCounters: function ()
		{
			BXMobileApp.onCustomEvent("requestUserCounters", {web: true}, true);
		},
		getDesktopStatus: function()
		{
			return new Promise(function(resolve)
			{
				var responseHandler = function(response)
				{
					resolve(response);
					BXMobileApp.Events.unsubscribe("onRequestDesktopStatus");
				};
				BXMobileApp.Events.addEventListener("onRequestDesktopStatus", responseHandler);
				BXMobileApp.Events.postToComponent("requestDesktopStatus", {
					web: true
				}, 'communication');
			});
		},
		openDesktopPage: function(url)
		{
			return BX.rest.callMethod('im.desktop.page.open', {url: url});
		}
	};

	var pageViewEvents = {
		onLiveFeedFavoriteView: /\/mobile\/index.php\?favorites=Y/gi,
		onCalendarEventView: /\/mobile\/calendar\/view_event.php\?event_id=(\d+)*/gi,
		//tasks
		onTaskView: /\/mobile\/tasks\/snmrouter\/\?routePage=view(.*)TASK_ID=/gi,
		onTaskListView: /\/mobile\/tasks\/snmrouter\/\?routePage=roles/gi,
		onTaskCreate: /\/mobile\/tasks\/snmrouter\/\?routePage=edit(.*)TASK_ID=0/gi,
		onTaskEdit: /\/mobile\/tasks\/snmrouter\/\?routePage=edit(.*)TASK_ID=(\d+)/gi,
		//profile
		onUserProfileView: /\/mobile\/users\/\?user_id=(.*)/gi,
		//crm
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
		onBizProcListView: /\/mobile\/bp\/\?USER_STATUS=0$/gi
	};

	var getEventByUrl = function (url)
	{
		for (var eventName in pageViewEvents)
		{
			if (url.match(pageViewEvents[eventName]))
			{
				return eventName;
			}
		}

		return null;
	};

	//Analytics

	var originalLoadPageBlank = app.loadPageBlank;
	var originalLoadPageStart = app.loadPageStart;
	var originalShowModalDialog = app.showModalDialog;

	if (window.mwebrtc)
	{
		var origCallInvite = window.mwebrtc.callInvite;
		window.mwebrtc.callInvite = function ()
		{
			var eventName = "Outgoing" + (arguments[1] === true ? "Video" : "Audio") + "Call";
			origCallInvite.apply(window.mwebrtc, arguments);

			if (eventName && typeof fabric != "undefined")
			{
				fabric.Answers.sendCustomEvent(eventName, {});
			}
		}
	}

	var fixEventByUrl = function (params)
	{
		var url = (typeof params == "object") ? params.url : params;
		var eventName = getEventByUrl(url);
		if (eventName && typeof fabric != "undefined")
		{
			fabric.Answers.sendCustomEvent(eventName, {});
		}
	};

	app.showModalDialog = function (params)
	{

		BX.proxy(originalShowModalDialog, app)(params);
		fixEventByUrl(params);
	};

	app.loadPageBlank = function (params)
	{
		BX.proxy(originalLoadPageBlank, app)(params);
		fixEventByUrl(params);
	};

	app.loadPageStart = function (params)
	{
		BX.proxy(originalLoadPageStart, app)(params);
		fixEventByUrl(params);
	};

})(window);
