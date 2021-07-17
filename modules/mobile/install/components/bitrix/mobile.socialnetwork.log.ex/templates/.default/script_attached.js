(function(){
	if (window["oMSL"])
		return ;
	var repoLog = {}, mid = {},
		f = function(ENTITY_XML_ID, id) {

			var logId = oMSL.getLogId();

			if (mid[id.join('-')] !== "hidden")
			{
				mid[id.join('-')] = "hidden";
				if (repoLog[logId])
				{
					repoLog[logId]["POST_NUM_COMMENTS"]--;
					BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
						log_id: logId,
						num : repoLog[logId]["POST_NUM_COMMENTS"]
					}, true);
				}
			}
		};
	BX.addCustomEvent(window, "OnUCommentWasDeleted", f);
	BX.addCustomEvent(window, "OnUCommentWasHidden", f);
	BX.addCustomEvent(window, "OnUCRecordHasDrawn", function(ENTITY_XML_ID, id) {
		var logId = oMSL.getLogId();
		mid[ENTITY_XML_ID] = (mid[ENTITY_XML_ID] || {});
		if (mid[id.join('-')] !== "drawn")
		{
			mid[id.join('-')] = "drawn";
			var node;
			if (repoLog[logId] && (node = BX('record-' + id.join('-') + '-cover')) &&
				node && node.parentNode == BX('record-' + ENTITY_XML_ID + '-new'))
			{
				repoLog[logId]["POST_NUM_COMMENTS"]++;
				BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
					log_id: logId,
					num : repoLog[logId]["POST_NUM_COMMENTS"]
				},true);
			}
		}
	});
	BX.addCustomEvent(window, "OnUCFormSubmit", function(entity_xml_id, id, obj, post_data)
	{
		if (
			post_data
			&& post_data["mobile_action"]
			&& post_data["mobile_action"] == "add_comment"
			&& id > 0
		)
		{
			post_data["mobile_action"] = post_data["action"] = "edit_comment";
			post_data["edit_id"] = id;
		}
	});

	window.__MSLRepositLog = function(id, data) {
		repoLog[id] = data;
	};
}());

function __MSLOnPostRead(node, e)
{
	if (
		!BX.type.isDomNode(node)
		|| node.hasAttribute('done')
	)
	{
		return false;
	}

	return BX.MobileLivefeed.ImportantManagerInstance.setPostRead(node);
}

function __MSLOnFeedPreInit(params) // only for the list
{
	if (typeof params.arAvailableGroup != 'undefined')
	{
		window.arAvailableGroup = params.arAvailableGroup;
	}

	BX.addCustomEvent("onFrameDataReceivedBefore", function(obCache) {
		BitrixMobile.LazyLoad.clearImages();
	});
	BX.addCustomEvent("BX.LazyLoad:ImageLoaded", function() {
		var windowSize = BX.GetWindowSize();
		window.maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
	});

	BX.addCustomEvent("onFrameDataReceived", function(obCache) {
		window.isPullDownEnabled = false;
		window.isPullDownLocked = false;
		window.isFrameDataReceived = true;
		app.exec('pullDownLoadingStop');
		BitrixMobile.LazyLoad.showImages(true);
	});

	BX.addCustomEvent("onFrameDataProcessed", function(blocks, bFromCache) {
		if (
			typeof blocks != 'undefined'
			&& typeof blocks[0] != 'undefined'
			&& typeof bFromCache != 'undefined'
			&& bFromCache
		)
		{
			if (
				typeof blocks[0]['PROPS'] != 'undefined'
				&& typeof blocks[0]['PROPS']['TS'] != 'undefined'
				&& parseInt(blocks[0]['PROPS']['TS']) > 0
			)
			{
				BX.MobileLivefeed.Instance.setOptions({
					frameCacheTs: parseInt(blocks[0]['PROPS']['TS'])
				});
			}
		}

		BitrixMobile.LazyLoad.showImages(true);

		if (!!bFromCache)
		{
			BX.MobileLivefeed.Instance.setPreventNextPage(true);
		}
	});

	BX.addCustomEvent("onCacheDataRequestStart", function()
	{
		setTimeout(function() {
			if (
				typeof window.isFrameDataReceived == 'undefined'
				|| !window.isFrameDataReceived
			)
			{
				window.isPullDownLocked = true;
				app.exec('pullDownLoadingStart');
			}
		}, 1000);
	});

	BX.addCustomEvent("onFrameDataReceivedError", function() {
		app.BasicAuth({
			'success': BX.delegate(function() {
				BX.frameCache.update(true);
			}),
			'failture': BX.delegate(function() {
				window.isPullDownLocked = false;
				app.exec('pullDownLoadingStop');
				__MSLRefreshError(true);
			})
		});
	});

	BX.addCustomEvent("onFrameDataRequestFail", function(response)
	{
		if (
			typeof response != 'undefined'
			&& typeof response.reason != 'undefined'
			&& response.reason == "bad_eval"
		)
		{
			window.isPullDownLocked = false;
			app.exec('pullDownLoadingStop');
			__MSLRefreshError(true);
		}
		else
		{
			app.BasicAuth({
				'success': BX.delegate(function() {
					BX.frameCache.update(true);
				}),
				'failture': BX.delegate(function() {
					window.isPullDownLocked = false;
					app.exec('pullDownLoadingStop');
					__MSLRefreshError(true);
				})
			});
		}
	});

	BX.addCustomEvent("onCacheInvokeAfter", function(storageBlocks, resultSet) {
		var items = resultSet.items;
		if (items.length <= 0)
		{
			BX.frameCache.update(true, true);
		}
	});

	BXMobileApp.addCustomEvent("onAfterEdit", function(params) {
		oMSL.afterEdit(params.postResponseData, params.postData.data.log_id);
	});

	BX.addCustomEvent("onPullDownDisable", function() {
		BXMobileApp.UI.Page.Refresh.setEnabled(false);
	});

	BX.addCustomEvent("onPullDownEnable", function() {
		BXMobileApp.UI.Page.Refresh.setEnabled(true);
	});

	BXMobileApp.UI.Page.Refresh.setParams({
		callback: function()
		{
			if (!window.isPullDownLocked)
			{
				__MSLRefresh(true);
			}
		},
		backgroundColor: '#E7E9EB'
	});
	BXMobileApp.UI.Page.Refresh.setEnabled(true);
}

function __MSLOnFeedInit(params)
{
	logID = parseInt(params.logID);
	bAjaxCall = !!params.bAjaxCall;
	bReload = !!params.bReload;
	bEmptyPage = !!params.bEmptyPage;
	bFiltered = !!params.bFiltered;
	bEmptyGetComments = !!params.bEmptyGetComments;
	groupID = parseInt(params.groupID);
	tmstmp = parseInt(params.tmstmp);
	strCounterType = params.strCounterType;

	oMSL.bFollowDefault = !!params.bFollowDefault;
	oMSL.groupID = parseInt(params.groupID);
	oMSL.ftMinTokenSize = parseInt(params.ftMinTokenSize);

	oMSL.bShowExpertMode = !!params.bShowExpertMode;
	oMSL.bExpertMode = !!params.bExpertMode;

	oMSL.bDetailEmptyPage = bEmptyPage;
	oMSL.curUrl = params.curUrl;
	oMSL.appCacheDebug = !!params.appCacheDebug;
	oMSL.pageType = (logID <= 0 && !bEmptyPage ? 'list' : 'detail');

	if (
		logID <= 0
		&& !bEmptyPage
		&& !bAjaxCall
	)
	{
		if (!bReload)
		{
			oMSL.initSearchBar();
		}

		oMSL.listPageMenuItems = [];

		if (groupID > 0)
		{
			BX.ready(function()
			{
				oMSL.listPageMenuItems.push({
					id: 'addPost',
					name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_ADD_POST'),
					image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
					action: function()
					{
						if (Application.getApiVersion() >= BX.MobileLivefeed.Instance.getApiVersion('layoutPostForm'))
						{
							BX.MobileLivefeed.PostFormManagerInstance.show({
								pageId: BX.MobileLivefeed.Instance.getPageId(),
								groupId: groupID,
								postId: 0
							});
						}
						else
						{
							oMSL.initPostForm({
								groupId: groupID,
								callback: function() {
									app.exec('showPostForm', oMSL.showNewPostForm({
										groupId: groupID
									}));
								}
							});
						}
					},
					arrowFlag: false
				});

				oMSL.listPageMenuItems.push({
					id: 'groupTasks',
					name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_GROUP_TASKS'),
					image: "/bitrix/templates/mobile_app/images/lenta/menu/n_check.png",
					icon: 'checkbox',
					arrowFlag: true,
					action: function() {

						if (Application.getApiVersion() >= 31)
						{
							BXMobileApp.Events.postToComponent(
								'taskbackground::task::action',
								[{
									groupId: groupID,
									groupName: BX.message('MSLLogTitle')
								}],
								'background'
							);
						}
						else
						{
							var path = BX.message('MSLPathToTasksRouter');
							path = path
								.replace('__ROUTE_PAGE__', 'list')
								.replace('#USER_ID#', BX.message('USER_ID'));
							window.BXMobileApp.PageManager.loadPageUnique({
								url: path,
								bx24ModernStyle: true
							});
						}
					}
				});

				oMSL.listPageMenuItems.push({
					id: 'groupFiles',
					name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_GROUP_FILES'),
					image: "/bitrix/templates/mobile_app/images/lenta/menu/files.png",
					action: function(){
						app.openBXTable({
							url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/?mobile_action=disk_folder_list&type=group&path=/&entityId=' + groupID,
							TABLE_SETTINGS : {
								type : "files",
								useTagsInSearch : false
							}
						});
					},
					arrowFlag: true,
					icon: "file"
				});

				if (BX.message('MSLPathToKnowledgeGroup'))
				{
					oMSL.listPageMenuItems.push({
						id: 'knowledge',
						name: BX.message('MSLTitleKnowledgeGroup'),
						image:"/bitrix/templates/mobile_app/images/lenta/menu/knowledge_base.png",
						action: function(){
							window.BXMobileApp.PageManager.loadPageUnique({
								url: BX.message('MSLPathToKnowledgeGroup').replace(/ /g, '%20'),
								bx24ModernStyle: true,
								cache: false
							});
						}
					});
				}

				oMSL.initPageMenu('list');
			});

			setTimeout(function() {
				BX.MobileLivefeed.Instance.setPageId('list_group_' + groupID);
				BX.MobileLivefeed.Instance.setOptions({
					groupId: groupID
				});
			}, 0); // instead of on ready
		}
		else
		{
			BX.ready(function()
			{
				setTimeout(function()
				{
					if (!bFiltered)
					{
						oMSL.listPageMenuItems.push({
							id: 'addPost',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_ADD_POST'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
							action: function()
							{
								if (Application.getApiVersion() >= BX.MobileLivefeed.Instance.getApiVersion('layoutPostForm'))
								{
									BX.MobileLivefeed.PostFormManagerInstance.show({
										pageId: BX.MobileLivefeed.Instance.getPageId(),
										postId: 0
									});
								}
								else
								{
									oMSL.initPostForm({
										groupId: groupID,
										callback: function() {
											app.exec('showPostForm', oMSL.showNewPostForm());
										}
									});
								}
							},
							arrowFlag: false
						});

						if (
							BX.message('MOBILE_EXT_LIVEFEED_TASKS_INSTALLED') == 'Y'
							|| BX.message('MOBILE_EXT_LIVEFEED_TIMEMAN_INSTALLED') == 'Y'
						)
						{
							oMSL.listPageMenuItems.push({
								id: 'presetWork',
								name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_PRESET_WORK'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/work.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?work=Y",
										cache: false,
										bx24ModernStyle: true,
										useSearchBar: true
									});
								}
							});
						}

						oMSL.listPageMenuItems.push({
							id: 'presetFavorites',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_PRESET_FAVORITES'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/favorite.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?favorites=Y",
									cache: false,
									bx24ModernStyle: true,
									useSearchBar: true
								});
							}
						});

						oMSL.listPageMenuItems.push({
							id: 'presetMy',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_PRESET_MY'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/mine.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?my=Y",
									cache: false,
									bx24ModernStyle: true,
									useSearchBar: true
								});
							}
						});

						oMSL.listPageMenuItems.push({
							id: 'presetImportant',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_PRESET_IMPORTANT'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/important.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?important=Y",
									cache: false,
									bx24ModernStyle: true,
									useSearchBar: true
								});
							}
						});

						if (BX.message('MOBILE_EXT_LIVEFEED_LISTS_INSTALLED') == 'Y')
						{
							oMSL.listPageMenuItems.push({
								id: 'presetBizproc',
								name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_PRESET_BIZPROC'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/workflow.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?bizproc=Y",
										cache: false,
										bx24ModernStyle: true,
										useSearchBar: true
									});
								}
							});
						}

						oMSL.listPageMenuItems.push({
							id: 'refresh',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_REFRESH'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/n_refresh.png",
							arrowFlag: false,
							action: function() {
								oMSL.pullDownAndRefresh();
							}
						});

						if (oMSL.bUseFollow)
						{
							oMSL.listPageMenuItems.push({
								id: 'followDefault',
								name: (
									oMSL.getFollowDefaultValue()
										? BX.message('MSLMenuItemFollowDefaultY')
										: BX.message('MSLMenuItemFollowDefaultN')
								),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/glasses.png",
								arrowFlag: false,
								feature: 'follow',
								action: function() {
									oMSL.setFollowDefault({
										value: !oMSL.getFollowDefaultValue()
									});
								}
							});
						}

						if (oMSL.bShowExpertMode)
						{
							oMSL.listPageMenuItems.push({
								id: 'expertMode',
								name: (
									oMSL.bExpertMode
										? BX.message('MSLMenuItemExpertModeY')
										: BX.message('MSLMenuItemExpertModeN')
								),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/expert.png",
								arrowFlag: false,
								feature: 'expert',
								action: function() {
									oMSL.setExpertMode({
										value: !oMSL.bExpertMode
									});
								}
							});
						}

						oMSL.initPageMenu('list');
					}
				}, 1000);

				BX.MobileLivefeed.Instance.setPageId('list');
			});
		}

		setTimeout(function()
		{
			BX.MobileLivefeed.Instance.setOptions({
				signedParameters: (BX.type.isNotEmptyString(params.signedParameters) ? params.signedParameters : {}),
				pathToEmptyPage: (BX.type.isNotEmptyString(params.pathToEmptyPage) ? params.pathToEmptyPage : ''),
				pathToCalendarEvent: '/mobile/calendar/view_event.php?event_id=#EVENT_ID#',
				pathToTasksRouter: (BX.type.isNotEmptyString(params.pathToTasksRouter) ? params.pathToTasksRouter : ''),
				destinationToAllDeny: (BX.type.isBoolean(params.destinationToAllDeny) && params.destinationToAllDeny),
				destinationToAllDefault: (!BX.type.isBoolean(params.destinationToAllDefault) || params.destinationToAllDefault),
				backgroundImagesData: (BX.type.isNotEmptyObject(params.backgroundImagesData) ? params.backgroundImagesData : {}),
				backgroundCommon: (BX.type.isNotEmptyObject(params.backgroundCommon) ? params.backgroundCommon : {}),
				medalsList: (BX.type.isNotEmptyObject(params.medalsList) ? params.medalsList : {}),
				importantData: (BX.type.isNotEmptyObject(params.importantData) ? params.importantData : {}),
				postFormData: (BX.type.isNotEmptyObject(params.postFormData) ? params.postFormData : {})
			});
		}, 0); // instead of on ready

		if (!bReload)
		{
			setTimeout(function()
			{
				BX.MobileLivefeed.PublicationQueueInstance.emit('onFeedReady');
			}, 0); // instead of on ready

			oMSL.registerBlocksToCheck();

			if (
				typeof window.bFeedInitialized != 'undefined'
				&& window.bFeedInitialized
			)
			{
				BX.ready(function() {
					BX.MobileLivefeed.Instance.recalcMaxScroll();

					setTimeout(function() {

						oMSL.checkNodesHeight();

						BX.onCustomEvent(window, 'BX.UserContentView.onRegisterViewAreaListCall', [{
							containerId: 'lenta_wrapper',
							className: 'post-item-contentview',
							fullContentClassName: 'post-item-full-content'
						}]);
					}, 1000);
				});
				return;
			}
			else
			{
				BX.ready(function() {
					BX.onCustomEvent(window, 'BX.UserContentView.onInitCall', [{
						mobile: true,
						ajaxUrl: BX.message('MSLSiteDir') + 'mobile/ajax.php',
						commentsContainerId: 'post-comments-wrap',
						commentsClassName: 'post-comment-wrap'
					}]);
				});

				window.bFeedInitialized = true;
			}

			window.arLikeRandomID = {};
			window.LiveFeedID = parseInt(Math.random() * 100000);

			if (groupID <= 0)
			{
				BX.ready(function()
				{
					setTimeout(function()
					{
						if (!bFiltered)
						{

						}
						else
						{
							BXMobileApp.UI.Page.TopBar.title.setText(BX.message('MSLLogTitle'));
							BXMobileApp.UI.Page.TopBar.title.setCallback("");
							BXMobileApp.UI.Page.TopBar.title.show();
						}
					}, 1000);
				});
			}
			oMSL.initPostForm({
				groupId: groupID
			});

			BXMobileApp.addCustomEvent("onBlogPostDelete", function(params) {
				oMSL.pullDownAndRefresh();
			});

			BX.ready(function()
			{
				if (
					bFiltered
					&& window.platform == "android"
				)
				{
					BX.addCustomEvent('onOpenPageBefore', function() {
						oMSL.pullDownAndRefresh();
					});
				}
			});

			BXMobileApp.addCustomEvent("onLogEntryRead", function(data) {
				__MSLLogEntryRead(data.log_id, data.ts, (data.bPull === true || data.bPull === 'YES'));
			});

			BXMobileApp.addCustomEvent("onCommentsGet", function(data) {
				if (
					typeof window.arLogTs['entry_' + data.log_id] != 'undefined'
					&& window.arLogTs['entry_' + data.log_id] != null
				)
				{
					arLogTs['entry_' + data.log_id] = data.ts;
				}
			});

			BXMobileApp.addCustomEvent("onLogEntryCommentAdd", function(data) {
				oMSL.onLogEntryCommentAdd(data.log_id);
			});

			// old one
			BXMobileApp.addCustomEvent("onLogEntryRatingLike", function(data) {
				oMSL.onLogEntryRatingLike({
					ratingId: data.rating_id,
					voteAction: data.voteAction,
					logId: data.logId
				});
			});

			BXMobileApp.addCustomEvent('onLogEntryImpPostRead', function(data) {
				BX.MobileLivefeed.ImportantManagerInstance.renderRead({
					node: BX('important_post_' + data.postId),
					value: true
				});
			});

			BXMobileApp.addCustomEvent("onLogEntryFollow", function(data)
			{
				oMSL.setFollow({
					logId: data.logId,
					pageId: data.pageId,
					bOnlyOn: (typeof data.bOnlyOn != 'undefined' && data.bOnlyOn == 'Y'),
					bRunEvent: false
				});
			});

			BXMobileApp.addCustomEvent("onLogEntryFavorites", function(data)
			{
				oMSL.onLogEntryFavorites(data.log_id, data.page_id);
			});

			BXMobileApp.addCustomEvent("onLogEntryCommentsNumRefresh", function(data)
			{
				oMSL.onLogEntryCommentAdd(data.log_id, data.num);
			});

			BXMobileApp.addCustomEvent("onLogEntryPostUpdated", function(data)
			{
				oMSL.onLogEntryPostUpdated(data);
			});

			BX.MobileUI.addLivefeedLongTapHandler(BX("lenta_wrapper_global"), {
				likeNodeClass: "post-item-informer-like",
				copyItemClass: "post-item-copyable",
				copyTextClass: "post-item-copytext"
			});
		}

		BX.MobileLivefeed.RatingInstance.emit('onFeedInit');
	}
	else if (bEmptyPage)
	{
		window.isDetailPullDownEnabled = false;

		oMSL.registerEmptyBlockToCheck();

		BX.ready(function()
		{
			__MSLDrawDetailPage();
			BX.addCustomEvent('onOpenPageBefore', function() { __MSLDrawDetailPage(); } );

			BXMobileApp.addCustomEvent('onEditedPostFailed', function() {
				app.hidePopupLoader();
			});

			BX.MobileImageViewer.viewImageBind(
				'post_item_top_wrap',
				'img[data-bx-image]'
			);
		});

		BX.MobileUI.addLivefeedLongTapHandler(BX("post_item_top_wrap"), {
			likeNodeClass: "post-item-informer-like",
			copyItemClass: "post-item-copyable",
			copyTextClass: "post-item-copytext"
		});

		if (BX("post-comments-wrap"))
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX("post-comments-wrap"), {
				likeNodeClass: "post-comment-control-item-like",
				copyItemClass: "post-comment-block",
				copyTextClass: "post-comment-text"
			});
		}
	}
	else if (
		logID > 0
		&& !bEmptyGetComments
	)
	{
		window.isDetailPullDownEnabled = false;
		window.arCanUserComment = {};

		BXMobileApp.onCustomEvent('onLogEntryRead', { log_id: logID, ts: tmstmp, bPull: false }, true);

		BX.ready(function()
		{
			BX.MobileLivefeed.Instance.setPageId('detail_' + logID);
			BX.MobileLivefeed.Instance.setOptions({
				logId: logID
			});

			oMSL.registerBlocksToCheck();
			setTimeout(function() { oMSL.checkNodesHeight(); }, 100);
		});

		if (BX("post_item_top_wrap_" + logID))
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX("post_item_top_wrap_" + logID), {
				likeNodeClass: "post-item-informer-like",
				copyItemClass: "post-item-copyable",
				copyTextClass: "post-item-copytext"
			});
		}
		else if (BX("post_item_top_wrap"))
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX("post_item_top_wrap"), {
				likeNodeClass: "post-item-informer-like",
				copyItemClass: "post-item-copyable",
				copyTextClass: "post-item-copytext"
			});
		}

		if (BX("post-comments-wrap"))
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX("post-comments-wrap"), {
				likeNodeClass: "post-comment-control-item-like",
				copyItemClass: "post-comment-block",
				copyTextClass: "post-comment-text"
			});
		}
	}

	if (
		bEmptyPage
		|| logID > 0
	)
	{
		BX.ready(function()
		{
			BX.MobileLivefeed.Instance.setOptions({
				destinationToAllDeny: (BX.type.isBoolean(params.destinationToAllDeny) && params.destinationToAllDeny),
				destinationToAllDefault: (!BX.type.isBoolean(params.destinationToAllDefault) || params.destinationToAllDefault),
				backgroundImagesData: (BX.type.isNotEmptyObject(params.backgroundImagesData) ? params.backgroundImagesData : {}),
				backgroundCommon: (BX.type.isNotEmptyObject(params.backgroundCommon) ? params.backgroundCommon : {}),
				medalsList: (BX.type.isNotEmptyObject(params.medalsList) ? params.medalsList : {}),
				importantData: (BX.type.isNotEmptyObject(params.importantData) ? params.importantData : {}),
				postFormData: (BX.type.isNotEmptyObject(params.postFormData) ? params.postFormData : {})
			});
		});
	}

	if (
		!bAjaxCall
		&& !bReload
	)
	{
		BX.ready(function() {
			BXMobileApp.addCustomEvent('onEditedPostInserted', function(data) {
				app.hidePopupLoader();
				oMSL.drawDetailPageText(data);
				BXMobileApp.onCustomEvent('onLogEntryPostUpdated', data, true);
			});
		});
	}

	if (!bAjaxCall)
	{
		BX.ready(function()
		{
			if (
				logID <= 0
				&& !bEmptyPage
			)
			{
				setTimeout(function() {
					BX.MobileLivefeed.Instance.recalcMaxScroll();
				}, 0); // ready is not enough

				oMSL.initScroll(true);

				if (!bReload)
				{
					BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params)
					{
						var networkState = navigator.network.connection.type;

						if (networkState == Connection.UNKNOWN || networkState == Connection.NONE)
						{
							app.exec('pullDownLoadingStop');
							oMSL.initScroll(false, true);
						}
						else
						{
							__MSLPullDownInit(true, false);
							oMSL.initScroll(true, true);
						}
					});
				}

				if (
					!bFiltered
					&& !bReload
				)
				{
					BXMobileApp.addCustomEvent("onUpdateSocnetCounters", oMSL.onUpdateSocnetCountersHandler); // old IM
					BXMobileApp.addCustomEvent("onImUpdateCounter", oMSL.onImUpdateCounterHandler); // old
					BXMobileApp.addCustomEvent("onUpdateUserCounters", oMSL.onUpdateCounterHandler); // from Communication component
					BX.MobileTools.requestUserCounters();
				}
			}

			if (
				bEmptyPage
				|| logID > 0
			)
			{
				if (
					window.platform != "ios"
				) // there's a bug in iOS 11+
				{
					BX.bind(window, 'scroll', oMSL.onScrollDetail);
				}

				setTimeout(function()
				{
					BX.MobileLivefeed.Instance.setOptions({
						importantData: (BX.type.isNotEmptyObject(params.importantData) ? params.importantData : {})
					});
				}, 0); // instead of on ready

				BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params)
				{
					var networkState = navigator.network.connection.type;

					if (
						networkState == Connection.UNKNOWN
						|| networkState == Connection.NONE
					)
					{
						app.exec('pullDownLoadingStop');
					}
					else
					{
						if (oMSL.iLastActivityDate > 0)
						{
							var iNowDate = Math.round(new Date().getTime() / 1000);
							if ((iNowDate - oMSL.iLastActivityDate) > 1740)
							{
								if (bEmptyPage)
								{
									BXMobileApp.UI.Page.isVisible({callback: function(data) {

										if (data.status == 'visible')
										{
											app.getPageParams({
												callback: function(data)
												{
													if (parseInt(data.log_id) > 0)
													{
														oMSL.setLogId(parseInt(data.log_id))
													}

													// get comments on an empty page after become active (wake-up)
													oMSL.getComments({
														ts: oMSL.iDetailTs,
														bPullDown: false,
														obFocus: {
															form: 'NO',
															comments: 'NO'
														}
													});
												}
											});
										}
										else
										{
											oMSL.emptyRefreshCommentsFlag = true;
										}
									}});
								}
								else
								{
									document.location.reload(true);
								}
								// get comments
							}
						}
					}
				});

				__MSLDetailPullDownInit(true);
			}
			else
			{
				setTimeout(function() {

					oMSL.checkNodesHeight();

					BX.onCustomEvent(window, 'BX.UserContentView.onRegisterViewAreaListCall', [{
						containerId: 'lenta_wrapper',
						className: 'post-item-contentview',
						fullContentClassName: 'post-item-full-content'
					}]);
				}, 1000);

				if (logID <= 0)
				{
					__MSLPullDownInit(true);
				}
			}
		});
	}

	// stop playing video on page change
	BXMobileApp.addCustomEvent("onHidePageBefore", function()
	{
		var
			players = document.getElementsByTagName("video"),
			i = null;
		for(i = 0; i < players.length; i++)
		{
			if(!players[i].paused)
			{
				players[i].pause();
			}
		}
		var iframes = document.querySelectorAll('iframe.bx-mobile-video-frame');
		for(i = 0; i < iframes.length; i++)
		{
			var src = iframes[i].getAttribute('src');
			if(src)
			{
				iframes[i].src = iframes[i].src;
			}
		}
	});
}

function __MSLOnFeedScroll()
{
	var windowScroll = BX.GetWindowScrollPos();
	var deviceMaxScroll = BX.MobileLivefeed.Instance.getMaxScroll();

	if (!(
		(
			windowScroll.scrollTop >= deviceMaxScroll
			|| document.documentElement.scrollHeight <= window.innerHeight // when small workarea
		)
		&& (
			windowScroll.scrollTop > 0 // refresh patch
			|| deviceMaxScroll > 0
		)
		&& !window.bRefreshing
		&& !window.bGettingNextPage
	))
	{
		return;
	}

	if (BX.MobileLivefeed.Instance.getOption('preventNextPage', false) === true)
	{
		return;
	}

	BX.unbind(window, 'scroll', __MSLOnFeedScroll);

	window.bGettingNextPage = true;

	nextPageXHR = BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'GET',
		url: url_next,
		data: '',
		callback: function(data)
		{
			nextPageXHR = null;
			if (
				typeof data != 'undefined'
				&& typeof (data.PROPS) != 'undefined'
				&& typeof (data.PROPS.CONTENT) != 'undefined'
				&& data.PROPS.CONTENT.length > 0
				&& (
					typeof data.LAST_TS == 'undefined'
					|| parseInt(data.LAST_TS) <= 0
					|| parseInt(BX.message('MSLFirstPageLastTS')) <= 0
					|| parseInt(data.LAST_TS) < parseInt(BX.message('MSLFirstPageLastTS'))
				)
			)
			{
				oMSL.processAjaxBlock(data.PROPS, {
					type: 'next',
					callback: function () {
						BX.MobileLivefeed.Instance.recalcMaxScroll();

						oMSL.registerBlocksToCheck();
						setTimeout(BX.delegate(oMSL.checkNodesHeight, oMSL), 100);

						BX.onCustomEvent(window, 'BX.UserContentView.onRegisterViewAreaListCall', [{
							containerId: 'lenta_wrapper',
							className: 'post-item-contentview',
							fullContentClassName: 'post-item-full-content'
						}]);
					}
				});

				if (
					parseInt(BX.message('MSLPageNavNum')) > 0
					&& parseInt(window.iPageNumber) > 0
				)
				{
					iPageNumber++;
					url_next = BX.util.remove_url_param(url_next, ['PAGEN_' + BX.message('MSLPageNavNum')]);
					url_next += (url_next.indexOf('?') >= 0 ? '&' : '?') + 'PAGEN_' + (parseInt(BX.message('MSLPageNavNum'))) + '=' + (iPageNumber  + 1);
				}
			}

			BX.bind(window, 'scroll', __MSLOnFeedScroll);
			window.bGettingNextPage = false;
		},
		callback_failure: function() {
			nextPageXHR = null;
			window.bGettingNextPage = false;
		}
	});
}

function __MSLOpenLogEntryNew(params, event)
{
	var bShowFull = (typeof params.show_full != 'undefined' ? !!params.show_full : false);

	if (
		typeof params.path == 'undefined'
		|| params.path == null
		|| params.path.length <= 0
	)
	{
		return false;
	}
	else
	{
		var path = params.path;
	}

	if (
		typeof params.log_id == 'undefined'
		|| params.log_id == null
		|| parseInt(params.log_id) <= 0
	)
	{
		return false;
	}

	params.follow = (
		BX('log_entry_follow_' + params.log_id)
			? BX('log_entry_follow_' + params.log_id).getAttribute('data-follow')
			: 'Y'
	);

	params.fav = (
		BX('log_entry_favorites_' + params.log_id)
		&& BX('log_entry_favorites_' + params.log_id).classList.contains('lenta-item-fav-active')
			? 'Y'
			: 'N'
	);

	params.feed_id = (
		typeof window.LiveFeedID != 'undefined'
			? window.LiveFeedID
			: ''
	);

	params.can_user_comment = (
		typeof arCanUserComment != 'undefined'
		&& typeof arCanUserComment[params.log_id] != 'undefined'
		&& arCanUserComment[params.log_id]
	);

	// block anchor click
	if (
		typeof event != 'undefined'
		&& event != null
		&& event
		&& typeof event.target != 'undefined'
		&& event.target != null
	)
	{
		if (
			typeof event.target.tagName != 'undefined'
			&& event.target.tagName.toLowerCase() == 'a'
			&& !BX.hasClass(event.target, 'post-item-more')
		)
		{
			return false;
		}

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'div', 'className': 'post-item-post-block' } );
		if (anchorNode)
		{
			return false;
		}
	}
	// -- block anchor click

	params.RandomID = (
		typeof arLikeRandomID['entry_' + params.log_id] != 'undefined'
		&& arLikeRandomID['entry_' + params.log_id] != null
			? arLikeRandomID['entry_' + params.log_id]
			: false
	);

	var postNode = document.getElementById('post_block_check_cont_' + params.log_id);
	if (BX.type.isDomNode(postNode))
	{
		var matches = null;
		var i = null;

		params.detailText = postNode.innerHTML;
		params.hasTitle = (postNode.getAttribute('bx-data-has-title') === 'Y');
		params.bIsPhoto = postNode.classList.contains('post-item-post-img-block');
		params.bIsImportant = postNode.classList.contains('info-block-important');
		params.medal = false;
		if (postNode.classList.contains('info-block-gratitude'))
		{
			matches = null;
			for(i = 0; i < postNode.classList.length; i++)
			{
				matches = postNode.classList[i].match(/info-block-gratitude-(.+)/i);
				if (matches)
				{
					params.medal = matches[1];
					break;
				}
			}
		}

		params.backgroundCode = false;
		if (postNode.classList.contains('ui-livefeed-background'))
		{
			matches = null;
			for(i = 0; i < postNode.classList.length; i++)
			{
				matches = postNode.classList[i].match(/ui-livefeed-background-(.+)/i);
				if (matches)
				{
					params.backgroundCode = matches[1];
					break;
				}
			}
		}
	}

	var lentaItemNode = document.getElementById('lenta_item_' + params.log_id);
	if (lentaItemNode)
	{
		var inlineScriptNode = lentaItemNode.parentNode.querySelector("span[data-type='inline-script']");
		if (inlineScriptNode)
		{
			params.inlineScript = inlineScriptNode.innerHTML;
		}
	}

	if (BX('post_block_files_' + params.log_id))
	{
		params.filesBlockText = BX('post_block_files_' + params.log_id).innerHTML;
	}

	if (BX('post_more_limiter_' + params.log_id))
	{
		params.showMoreButton = (BX('post_more_limiter_' + params.log_id).style.visibility != 'hidden');
	}

	if (BX('post_item_top_' + params.log_id))
	{
		params.topText = BX('post_item_top_' + params.log_id).innerHTML;
	}

	if (BX('informer_comments_all_' + params.log_id))
	{
		params.commentsNumAll = BX('informer_comments_all_' + params.log_id).innerHTML;
		if (BX('informer_comments_new_' + params.log_id))
		{
			params.commentsNumNew = BX.findChild(BX('informer_comments_new_' + params.log_id), { className: 'post-item-inform-right-new-value' }, true, false).innerHTML;
		}
	}
	else if (BX('informer_comments_' + params.log_id))
	{
		params.commentsNum = BX('informer_comments_' + params.log_id).innerHTML;
	}

	if (BX('comments_control_' + params.log_id))
	{
		params.commentsControl = !!BX('comments_control_' + params.log_id);
	}

	if (BX('rating_block_' + params.log_id))
	{
		params.ratingText = BX('rating_block_' + params.log_id).innerHTML;
		params.ratingCounter = parseInt(BX('rating_block_' + params.log_id).getAttribute('data-counter'));
	}

	if (BX('rating-footer-wrap_' + params.log_id))
	{
		params.ratingFooter = BX('rating-footer-wrap_' + params.log_id).innerHTML;
	}

	params.bShowFull = bShowFull;

	if (
		typeof window.arLogTs['entry_' + params.log_id] != 'undefined'
		&& window.arLogTs['entry_' + params.log_id] != null
	)
	{
		params.TS = arLogTs['entry_' + params.log_id];
	}

	params.bSetFocusOnCommentForm = (typeof params.focus_form != 'undefined' ? !!params.focus_form : false);
	params.bSetFocusOnCommentsList = (typeof params.focus_comments != 'undefined' ? !!params.focus_comments : false);

	app.loadPageBlank({
		url: path,
		bx24ModernStyle: true,
		data: params
	});

	return (
		typeof event != 'undefined'
		&& event != null
		&& event
			? event.preventDefault()
			: false
	);
}

function __MSLDrawDetailPage()
{
	app.getPageParams(
		{
			callback: function(data)
			{
				BXMobileApp.onCustomEvent('onLogEntryRead', {
					log_id: data.log_id,
					ts: BX.message('MSLCurrentTime'),
					bPull: false
				},true);
				oMSL.drawDetailPage(data);
			}
		}
	);
}

function __MSLDetailMoveBottom()
{
	window.scrollTo(0, document.body.scrollHeight);
}

function __MSLDetailMoveTop()
{
	window.scrollTo(0, 0);
}

function __MSLLogEntryRead(log_id, ts, bPull)
{
	bPull = !!bPull;

	if (
		typeof window.arLogTs['entry_' + log_id] != 'undefined'
		&& window.arLogTs['entry_' + log_id] != null
	)
	{
		arLogTs['entry_' + log_id] = ts;

		if (
			BX('informer_comments_' + log_id)
			&& BX('informer_comments_new_' + log_id)
			&& !bPull
		)
		{
			var old_value = (BX('informer_comments_all_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_all_' + log_id).innerHTML) : 0);
			var val = old_value + parseInt(BX.findChild(BX('informer_comments_new_' + log_id), {
				className: 'post-item-inform-right-new-value'
			}, true, false).innerHTML);

			BX.remove(BX('informer_comments_new_' + log_id));
			BX.remove(BX('informer_comments_all_' + log_id));
			BX('informer_comments_' + log_id).innerHTML = val;

			var commentsControlNode = BX('informer_comments_' + log_id).closest('.post-item-informers');
			if (commentsControlNode)
			{
				commentsControlNode.classList.remove('post-item-inform-likes-active');
			}
			BX.MobileLivefeed.PinnedPanelInstance.adjustCollapsedPostsPanel();
		}
	}
	if (BX('lenta_item_' + log_id))
	{
		BX.removeClass(BX('lenta_item_' + log_id), 'lenta-item-new');
	}
}

function __MSLGetHiddenDestinations(log_id, author_id, bindElement)
{
	var get_data = {
		sessid: BX.message('MSLSessid'),
		site: BX.message('SITE_ID'),
		lang: BX.message('MSLLangId'),
		dlim: BX.message('MSLDestinationLimit'),
		log_id: parseInt(log_id),
		nt: BX.message('MSLNameTemplate'),
		sl: BX.message('MSLShowLogin'),
		p_user: BX.message('MSLPathToUser'),
		p_group: BX.message('MSLPathToGroup'),
		p_crmlead: BX.message('MSLPathToCrmLead'),
		p_crmdeal: BX.message('MSLPathToCrmDeal'),
		p_crmcontact: BX.message('MSLPathToCrmContact'),
		p_crmcompany: BX.message('MSLPathToCrmCompany'),
		action: 'get_more_destination',
		mobile_action: 'get_more_destination',
		author_id: parseInt(author_id)
	};

	BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'POST',
		url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
		data: get_data,
		callback: function(get_response_data)
		{
			if (typeof get_response_data["arDestinations"] != 'undefined')
			{
				var arDestinations = get_response_data["arDestinations"];
				if (typeof (arDestinations) == "object")
				{
					if (BX(bindElement))
					{
						var cont = bindElement.parentNode;
						cont.removeChild(bindElement);

						for (var i = 0; i < arDestinations.length; i++)
						{
							if (
								typeof (arDestinations[i]['TITLE']) != 'undefined'
								&& arDestinations[i]['TITLE'].length > 0
							)
							{
								cont.appendChild(BX.create('SPAN', {
									html: ',&nbsp;'
								}));

								if (
									typeof (arDestinations[i]['CRM_PREFIX']) != 'undefined'
									&& arDestinations[i]['CRM_PREFIX'].length > 0)
								{
									cont.appendChild(BX.create('SPAN', {
										props: {
											className: 'post-item-dest-crm-prefix'
										},
										html: arDestinations[i]['CRM_PREFIX'] + ':&nbsp;'
									}));
								}

								if (
									typeof (arDestinations[i]['URL']) != 'undefined'
									&& arDestinations[i]['URL'].length > 0
								)
								{
									cont.appendChild(BX.create('A', {
										props: {
											className: 'post-item-destination' + (typeof arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? ' post-item-dest-'+arDestinations[i]['STYLE'] : ''),
											'href': arDestinations[i]['URL']
										},
										html: arDestinations[i]['TITLE']
									}));
								}
								else
								{
									cont.appendChild(BX.create('SPAN', {
										props: {
											className: 'post-item-destination' + (typeof arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? ' post-item-dest-'+arDestinations[i]['STYLE'] : '')
										},
										html: arDestinations[i]['TITLE']
									}));
								}
							}
						}

						if (
							typeof get_response_data["iDestinationsHidden"] != 'undefined'
							&& parseInt(get_response_data["iDestinationsHidden"]) > 0
						)
						{
							get_response_data["iDestinationsHidden"] = parseInt(get_response_data["iDestinationsHidden"]);
							var suffix = (
								(get_response_data["iDestinationsHidden"] % 100) > 10
								&& (get_response_data["iDestinationsHidden"] % 100) < 20
									? 5
									: get_response_data["iDestinationsHidden"] % 10
							);

							cont.appendChild(BX.create('SPAN', {
								html: '&nbsp;' + BX.message('MSLDestinationHidden' + suffix).replace("#COUNT#", get_response_data["iDestinationsHidden"])
							}));
						}

						oMSL.checkNodesHeight();
					}
				}
			}
		},
		callback_failure: function() { }
	});
}

function __MSLGetNewPosts()
{
}

function __MSLRefresh(bScroll, params)
{
	bScroll = !!bScroll;

	if (
		window.bGettingNextPage
		&& window.nextPageXHR != null
	)
	{
		nextPageXHR.abort();
	}

	BX.addClass(BX('lenta_notifier'), 'lenta-notifier-waiter');

	BX.MobileLivefeed.Instance.setRefreshNeeded(false);
	BX.MobileLivefeed.Instance.setRefreshStarted(true);
	BX.MobileLivefeed.BalloonNotifierInstance.hideRefreshNeededNotifier();
	BX.MobileLivefeed.NotificationBarInstance.hideAll();

	bRefreshing = true;

	var reload_url = document.location.href;

	reload_url = BX.util.remove_url_param(reload_url, [ 'RELOAD', 'RELOAD_JSON', 'FIND' ]);
	reload_url = BX.util.add_url_param(reload_url, {
		RELOAD: 'Y',
		RELOAD_JSON: 'Y'
	});

	if (
		BX.type.isPlainObject(params)
		&& BX.type.isNotEmptyString(params.find)
	)
	{
		reload_url = BX.util.add_url_param(reload_url, {
			FIND: params.find
		});
	}

	var headers = [
		{ name: "BX-ACTION-TYPE", value: "get_dynamic" },
		{ name: "BX-REF", value: document.referrer },
		{ name: "BX-CACHE-MODE", value: "APPCACHE" },
		{ name: "BX-APPCACHE-PARAMS", value: JSON.stringify(window.appCacheVars) },
		{ name: "BX-APPCACHE-URL", value: (typeof BX.frameCache != 'undefined' && typeof BX.frameCache.vars != 'undefined' && typeof BX.frameCache.vars.PAGE_URL != 'undefined' ? BX.frameCache.vars.PAGE_URL : oMSL.curUrl) }
	];

	oMSL.xhr.refresh = BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'GET',
		url: reload_url,
		data: '',
		headers: headers,
		callback: function(data)
		{
			oMSL.xhr.refresh = null;
			BX.MobileLivefeed.Instance.setRefreshStarted(false);
			BX.MobileLivefeed.Instance.setRefreshNeeded(false);

			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');
			app.exec('pullDownLoadingStop');
			app.exec("hideSearchBarProgress");

			if (
				typeof data != 'undefined'
				&& typeof (data.PROPS) != 'undefined'
				&& typeof (data.PROPS.CONTENT) != 'undefined'
				&& data.PROPS.CONTENT.length > 0
			)
			{
				BX.MobileLivefeed.Instance.setPreventNextPage(false);

				BitrixMobile.LazyLoad.clearImages();
				BX.clearNodeCache();
				app.hidePopupLoader();
				BX.MobileLivefeed.BalloonNotifierInstance.hideNotifier();
				BX.MobileLivefeed.BalloonNotifierInstance.hideRefreshNeededNotifier();

				if (typeof (data.COUNTER_TO_CLEAR) != 'undefined')
				{
					BXMobileApp.onCustomEvent('onClearLFCounter', [data.COUNTER_TO_CLEAR], true);

					var eventParams = {
						counterCode: data.COUNTER_TO_CLEAR,
						serverTime: data.COUNTER_SERVER_TIME,
						serverTimeUnix: data.COUNTER_SERVER_TIME_UNIX
					};

					BXMobileApp.Events.postToComponent("onClearLiveFeedCounter", eventParams, "communication");
				}

				oMSL.processAjaxBlock(data.PROPS, {
					type: 'refresh',
					callback: function()
					{
						BX.MobileLivefeed.PinnedPanelInstance.resetFlags();
						BX.MobileLivefeed.PinnedPanelInstance.init();

						if (
							typeof BX.frameCache != 'undefined'
							&& BX("bxdynamic_feed_refresh")
							&& (
								typeof data.REWRITE_FRAMECACHE == 'undefined'
								|| data.REWRITE_FRAMECACHE != 'N'
							)
						)
						{
							var serverTimestamp = (
								typeof (data.TS) != 'undefined'
								&& parseInt(data.TS) > 0
									? parseInt(data.TS)
									: 0
							);

							if (serverTimestamp > 0)
							{
								BX.MobileLivefeed.Instance.setOptions({
									frameCacheTs: serverTimestamp
								});
							}

							BX.MobileLivefeed.Instance.updateFrameCache({
								timestamp: serverTimestamp
							});
						}

						oMSL.registerBlocksToCheck();

						//Android hack.
						//The processing of javascript and insertion of html works not so fast as expected
						setTimeout(function(){
							BitrixMobile.LazyLoad.showImages(); // when refresh
						}, 1000);

					}
				});

				if (bScroll)
				{
					BitrixAnimation.animate({
						duration : 1000,
						start : { scroll : document.body.scrollTop },
						finish : { scroll : 0 },
						transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
						step : function(state)
						{
							window.scrollTo(0, state.scroll);
						},
						complete : function(){}
					});
				}

				if (
					window.applicationCache
					&& data.isManifestUpdated == "1"
					&& !oMSL.appCacheDebug
					&& (
						window.applicationCache.status == window.applicationCache.IDLE
						|| window.applicationCache.status == window.applicationCache.UPDATEREADY
					)
				)//the manifest has been changed
				{
					window.applicationCache.update();
				}
			}
			else
			{
				__MSLRefreshError(true);
			}

			bRefreshing = false;
		},
		callback_failure: function()
		{
			oMSL.xhr.refresh = null;
			BX.MobileLivefeed.Instance.setRefreshStarted(false);
			BX.MobileLivefeed.Instance.setRefreshNeeded(false);

			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');
			app.exec('pullDownLoadingStop');
			app.exec("hideSearchBarProgress");

			__MSLRefreshError(true);
			bRefreshing = false;
		}
	});
}

function __MSLPullDownInit(enable, bRefresh)
{
	if (typeof bRefresh == 'undefined')
	{
		bRefresh = true;
	}

	enable = !!enable;
	if (enable)
	{
		if (
			!window.isPullDownEnabled
			&& bRefresh
		)
		{
			BXMobileApp.UI.Page.Refresh.setParams({
				pulltext: BX.message('MOBILE_EXT_LIVEFEED_NEW_PULL'),
				backgroundColor: '#E7E9EB',
				downtext: BX.message('MOBILE_EXT_LIVEFEED_NEW_PULL_RELEASE'),
				loadtext: BX.message('MOBILE_EXT_LIVEFEED_NEW_PULL_LOADING'),
				callback: function() {
					if (!window.isPullDownLocked)
					{
						app.exec("hideSearchBar");
						__MSLRefresh(true);
					}
				}
			});
			BXMobileApp.UI.Page.Refresh.setEnabled(true);
		}
		isPullDownEnabled = true;
	}
	else
	{
		BXMobileApp.UI.Page.Refresh.setEnabled(false);

		isPullDownEnabled = false;
	}
}

function __MSLDetailPullDownInit(enable)
{
	enable = !!enable;

	if (enable)
	{
		if (!isDetailPullDownEnabled)
		{
			var callbackFunction = function() {
				var bReload = true;

				if (bReload)
				{
					var logID = null;
					var ts = null;

					if (BX('post_log_id'))
					{
						logID = parseInt(BX('post_log_id').getAttribute('data-log-id'));
						ts = parseInt(BX('post_log_id').getAttribute('data-ts'));
					}
					else if (BX('lenta_wrapper'))
					{
						var postWrap = BX.findChild(BX('lenta_wrapper'), { className: 'post-wrap' }, true, false);
						if (
							postWrap
							&& postWrap.id.length > 0
						)
						{
							var arMatch = postWrap.id.match(/^lenta_item_([\d]+)$/i);
							if (arMatch != null)
							{
								document.location.replace(document.location.href);
								isDetailPullDownEnabled = true;
								return;
							}
						}
					}

					if (
						BX('post-comments-wrap')
						&& (typeof logID !== 'undefined')
						&& logID != null
					)
					{
						// get comments when pulldown
						oMSL.getComments({
							ts: ts,
							bPullDown: true,
							obFocus: {
								form: false
							}
						});

						oMSL.refreshPostDetail();
					}
				}
			};

			BXMobileApp.UI.Page.Refresh.setParams({
				pulltext: BX.message('MOBILE_EXT_LIVEFEED_NEW_PULL'),
				backgroundColor: '#E7E9EB',
				downtext: BX.message('MOBILE_EXT_LIVEFEED_NEW_PULL_RELEASE'),
				loadtext: BX.message('MOBILE_EXT_LIVEFEED_DETAIL_NEW_PULL_LOADING'),
				callback: callbackFunction
			});
			BXMobileApp.UI.Page.Refresh.setEnabled(true);
		}
		isDetailPullDownEnabled = true;
	}
	else
	{
		BXMobileApp.UI.Page.Refresh.setEnabled(false);
		isDetailPullDownEnabled = false;
	}
}

function __MSLRefreshError(bShow)
{
	bShow = !!bShow;
	var errorBlock = BX("lenta_refresh_error");
	if (parseInt(window.refreshErrorTimeout) > 0)
	{
		clearTimeout(window.refreshErrorTimeout);
	}

	if (errorBlock)
	{
		if (bShow)
		{
			BX.addClass(errorBlock, "lenta-notifier-shown");
			BX.bind(window, 'scroll', __MSLRefreshErrorScroll);
		}
		else
		{
			BX.unbind(window, 'scroll', __MSLRefreshErrorScroll);
			BX.removeClass(errorBlock, "lenta-notifier-shown");
		}
	}
	else
	{
		window.refreshErrorTimeout = setTimeout(function() {
			__MSLRefreshError(bShow);
		}, 500);
	}
}

function __MSLRefreshErrorScroll()
{
	__MSLRefreshError(false);
}

function __MSLSetFavorites(log_id, favoritesBlock, e)
{
	var postInstance = BX.MobileLivefeed.Instance.getPostFromLogId(log_id);
	if (!postInstance)
	{
		return;
	}

	return postInstance.setFavorites({
		node: favoritesBlock,
		event: e
	});
}

function showHiddenDestination(cont, el)
{
	BX.hide(el);
	BX('blog-destination-hidden-'+cont).style.display = 'inline';
}

function commonNativeInputCallback(text, commentId)
{
	if (window.entryType == 'blog')
	{
		blogCommentsNativeInputCallback({
			text: text,
			oPreviewComment: null,
			commentId: commentId
		});
	}
	else if (
		window.entryType == 'non-blog'
		&& typeof (commentId) == 'undefined'
	)
	{
		commentsNativeInputCallback({
			text: text
		});
	}
}

function blogCommentsNativeInputCallback(params)
{
	var text = (typeof params.text != 'undefined' ? BX.util.htmlspecialchars(params.text) : '');
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : null);
	var commentId = (typeof params.commentId != 'undefined' ? params.commentId : 0);
	var nodeId = (typeof params.nodeId != 'undefined' ? params.nodeId : '');
	var ufCode = (typeof params.ufCode != 'undefined' ? params.ufCode : false);
	var attachedFiles = (typeof params.attachedFiles != 'undefined' ? params.attachedFiles : false);
	var attachedFilesRaw = (typeof params.attachedFilesRaw != 'undefined' ? params.attachedFilesRaw : false);

	if (text.length == 0)
	{
		return;
	}

	var data = {
		'sessid': BX.bitrix_sessid(),
		'comment_post_id': commentVarBlogPostID,
		'act': 'add',
		'post': 'Y',
		'comment': oMSL.parseMentions(text),
		'decode': 'Y'
	};

	if (commentVarAction)
	{
		data.ACTION = commentVarAction;
	}

	if (commentVarEntityTypeID)
	{
		data.ENTITY_TYPE_ID = commentVarEntityTypeID;
	}

	if (commentVarEntityID)
	{
		data.ENTITY_ID = commentVarEntityID;
	}

	if (ufCode && attachedFiles)
	{
		data[ufCode] = attachedFiles;
	}

	if (attachedFilesRaw)
	{
		data.attachedFilesRaw = attachedFilesRaw;
	}

	if (
		typeof (commentId) != 'undefined'
		&& parseInt(commentId) > 0
	)
	{
		data.act = 'edit';
		data.edit_id = parseInt(commentId);
		nodeId = (typeof nodeId != 'undefined' ? nodeId : "");
	}

	if (
		data.act == 'add'
		&& (
			typeof (oPreviewComment) == 'undefined'
			|| oPreviewComment === null
		)
	)
	{
		oPreviewComment = oMSL.showPreviewComment(text);
		app.clearInput();
	}
	else if (
		data.act == 'edit'
		&& BX(nodeId)
	)
	{
		oMSL.showCommentWait({
			nodeId: nodeId,
			status: true
		});

		var textBlock = BX.findChild(BX(nodeId), { className: 'post-comment-text' }, true, false);
		if (textBlock)
		{
			textBlock.innerHTML = text.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
				function(str, id, userName)
				{
					return userName;
				}
			);
		}

		app.clearInput();
	}

	BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'POST',
		url: commentVarURL,
		data: data,
		processData : true,
		callback: function(ajax_response)
		{
			if (
				typeof ajax_response == 'object'
				&& typeof ajax_response.TEXT != 'undefined'
			)
			{
				response = ajax_response.TEXT;
			}

			if (
				typeof response != 'undefined'
				&& response != "*"
				&& response.length > 0
			)
			{
				if (data.act == 'add')
				{
					oMSL.showNewComment({
						commentId: (typeof ajax_response.COMMENT_ID != 'undefined' ? parseInt(ajax_response.COMMENT_ID) : 0),
						text: response,
						bClearForm: false,
						oPreviewComment: oPreviewComment,
						bShowImages: false
					});
					oMSL.parseAndExecCode(response);
					__MSLDetailMoveBottom();
					oMSL.setFollow({
						logId: oMSL.getLogId(),
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showNewComment({
						text: response,
						bClearForm: false,
						oPreviewComment: BX(nodeId),
						bReplace: true,
						bIncrementCounters: false,
						bShowImages: false
					});
					oMSL.parseAndExecCode(response);
				}
			}
			else
			{
				oMSL.showCommentAlert({
					nodeId: (data.act == 'add' ? oPreviewComment : nodeId),
					action: data.act,
					text: text,
					commentType: 'blog',
					callback: function() {
						blogCommentsNativeInputCallback({
							text: text,
							oPreviewComment: (data.act == 'add' ? oPreviewComment : nodeId),
							commentId: (data.act == 'add' ? false : commentId)
						});
					}
				});
			}
		},
		callback_failure: function()
		{
			oMSL.showCommentAlert({
				nodeId: (data.act == 'add' ? oPreviewComment : nodeId),
				action:	data.act,
				text: text,
				commentType: 'blog',
				callback: function() {
					blogCommentsNativeInputCallback({
						text: text,
						oPreviewComment: (data.act == 'add' ? oPreviewComment : nodeId),
						commentId: (data.act == 'add' ? false : commentId)
					});
				}
			});
		}
	});
}

function commentsNativeInputCallback(params)
{
	var text = (typeof params.text != 'undefined' ? BX.util.htmlspecialchars(params.text) : '');
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : null);
	var commentId = (typeof params.commentId != 'undefined' ? params.commentId : 0);
	var nodeId = (typeof params.nodeId != 'undefined' ? params.nodeId : '');
	var ufCode = (typeof params.ufCode != 'undefined' ? params.ufCode : false);
	var attachedFiles = (typeof params.attachedFiles != 'undefined' ? params.attachedFiles : false);
	var attachedFilesRaw = (typeof params.attachedFilesRaw != 'undefined' ? params.attachedFilesRaw : false);

	if (text.length == 0)
	{
		return;
	}

	var post_data = {
		sessid: BX.bitrix_sessid(),
		site: commentVarSiteID,
		lang: commentVarLanguageID,
		log_id: commentVarLogID,
		message: oMSL.parseMentions(text),
		as: commentVarAvatarSize,
		nt: commentVarNameTemplate,
		sl: commentVarShowLogin,
		dtf: commentVarDateTimeFormat,
		p_user: commentVarPathToUser,
		rt: commentVarRatingType,
		action: 'add_comment',
		mobile_action: 'add_comment',
		sr: BX.message('MSLShowRating')
	};

	if (ufCode && attachedFiles)
	{
		post_data[ufCode] = attachedFiles;
	}

	if (attachedFilesRaw)
	{
		post_data.attachedFilesRaw = attachedFilesRaw;
	}

	if (
		typeof (commentId) != 'undefined'
		&& parseInt(commentId) > 0
	)
	{
		post_data.action = 'edit_comment';
		post_data.mobile_action = 'edit_comment';
		post_data.edit_id = parseInt(commentId);

		nodeId = (typeof nodeId != 'undefined' ? nodeId : "");
	}

	if (
		post_data.action == 'add_comment'
		&& (
			typeof (oPreviewComment) == 'undefined'
			|| oPreviewComment === null
		)
	)
	{
		oPreviewComment = oMSL.showPreviewComment(text);
		app.clearInput();
	}
	else if (
		post_data.action == 'edit_comment'
		&& BX(nodeId)
	)
	{
		oMSL.showCommentWait({
			nodeId: nodeId,
			status: true
		});

		var textBlock = BX.findChild(BX(nodeId), { className: 'post-comment-text' }, true, false);
		if (textBlock)
		{
			textBlock.innerHTML = text.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
				function(str, id, userName)
				{
					return userName;
				}
			);
		}

		app.clearInput();
	}

	BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'POST',
		url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
		data: post_data,
		callback: function(post_response_data)
		{
			if (typeof post_response_data["arCommentFormatted"] != 'undefined')
			{
				if (post_data.action == 'add_comment')
				{
					oMSL.showNewComment({
						commentId: parseInt(post_response_data["commentID"]),
						arComment: post_response_data["arCommentFormatted"],
						oPreviewComment: oPreviewComment,
						bClearForm: true
					});
					__MSLDetailMoveBottom();

					oMSL.setFollow({
						logId: post_data.log_id,
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showNewComment({
						arComment: post_response_data["arCommentFormatted"],
						oPreviewComment: BX(nodeId),
						bIncrementCounters: false,
						bReplace: true
					});
				}
			}
			else
			{
				oMSL.alertPreviewComment({
					nodeId: (oPreviewComment ? oPreviewComment : nodeId),
					text: text,
					commentType: 'log',
					commentId: commentId,
					action: post_data.action,
					callback: function()
					{
						commentsNativeInputCallback({
							text: text,
							oPreviewComment: oPreviewComment,
							commentId: commentId,
							nodeId: nodeId
						});
					}
				});
			}
		},
		'callback_failure': function()
		{
			oMSL.alertPreviewComment({
				nodeId: (oPreviewComment ? oPreviewComment : nodeId),
				text: text,
				commentType: 'log',
				commentId: commentId,
				action: post_data.action,
				callback: function()
				{
					commentsNativeInputCallback({
						text: text,
						oPreviewComment: oPreviewComment,
						commentId: commentId,
						nodeId: nodeId
					});
				}
			});
		}
	});
}

BitrixMSL = function ()
{
	this.scriptsAttached = [];
	this.counterTimeout = null;
	this.detailPageId = '';
	this.logId = false;
	this.commentsType = false;
	this.entityXMLId = '';

	this.commentTextCurrent = '';
	this.arMention = [];

	this.bUseFollow = true;
	this.bFollow = true;
	this.bFollowDefault = true;

	this.bShowExpertMode = true;
	this.bExpertMode = false;

	this.detailPageMenuItems = [];
	this.listPageMenuItems = [];

	this.bKeyboardCaptureEnabled = false;
	this.keyboardShown = null;

	this.arBlockToCheck = {};
	this.iLastActivityDate = null;
	this.iDetailTs = 0;
	this.newPostFormParams = {};
	this.newPostFormExtraData = {};
	this.arRatingLikeProcess = {};
	this.bDetailEmptyPage = null;
	this.bCounterReceived = false;

	this.counterValue = 0;
	this.blocksToCheckRegisteredList = [];
	this.menuData = {};
	this.showScrollButtonTimeout = null;
	this.showScrollButtonBottom = false;
	this.showScrollButtonTop = false;
	this.canCheckScrollButton = true;
	this.windowSize = null;

	this.emptyRefreshCommentsFlag = false;
	this.detailPageFocus = null;

	this.emptyCommentsXhr = null;

	this.classes = {
		postItemBlockFull: 'post-item-post-block-full'
	};

	this.findTextMode = false; // for the live search, not used now

	this.xhr = {
		refresh: null
	};
};

BitrixMSL.prototype.setLogId = function(logId)
{
	this.logId = parseInt(data.log_id);
};

BitrixMSL.prototype.getLogId = function()
{
	return parseInt(this.logId);
};

BitrixMSL.prototype.setFollowValue = function(value)
{
	this.bFollow = !!value;
};

BitrixMSL.prototype.getFollowValue = function()
{
	return this.bFollow;
};

BitrixMSL.prototype.setFollowDefaultValue = function(value)
{
	this.bFollowDefault = !!value;
};

BitrixMSL.prototype.getFollowDefaultValue = function()
{
	return this.bFollowDefault;
};

BitrixMSL.prototype.loadScripts = function()
{
	for (var i = 0; i < this.scriptsAttached.length; i++)
	{
		BX.loadScript(this.scriptsAttached[i] + '?' + parseInt(Math.random() * 100000));
	}
};

BitrixMSL.prototype.pullDownAndRefresh = function(params)
{
	app.exec('pullDownLoadingStart');
	window.isPullDownLocked = true;
	__MSLRefresh(true, params);
};

BitrixMSL.prototype.shareBlogPost = function(data)
{
//	alert(JSON.stringify(data));
};

BitrixMSL.prototype.deleteBlogPost = function(data)
{
	app.confirm({
		title: BX.message('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_TITLE'),
		text : BX.message('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_DESCRIPTION'),
		buttons : [
			BX.message('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_OK'),
			BX.message('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_CANCEL')
		],
		callback : function (btnNum)
		{
			if (btnNum == 1)
			{
				app.showPopupLoader({text:""});

				var actionUrl = BX.message('MSLSiteDir') + 'mobile/ajax.php';
				actionUrl = BX.util.add_url_param(actionUrl, {
					b24statAction: 'deleteBlogPost',
					b24statContext: 'mobile'
				});

				BX.Mobile.Ajax.wrap({
					type: 'json',
					method: 'POST',
					url: actionUrl,
					data: {
						action: 'delete_post',
						mobile_action: 'delete_post',
						sessid: BX.bitrix_sessid(),
						site: BX.message('SITE_ID'),
						lang: BX.message('LANGUAGE_ID'),
						post_id: data.post_id
					},
					processData: true,
					callback: function(response_data)
					{
						app.hidePopupLoader();

						if (
							BX.type.isNotEmptyString(response_data.SUCCESS)
							&& response_data.SUCCESS == 'Y'
						)
						{
							BXMobileApp.onCustomEvent('onBlogPostDelete', {}, true, true);
							app.closeController({drop: true});
						}
					},
					callback_failure: function() {
						app.hidePopupLoader();
					}
				});

				return false;
			}
		}
	});
};

BitrixMSL.prototype.getBlogPostData = function(post_id, callbackFunc)
{
	post_id = parseInt(post_id);
	var obResult = {};

	if (post_id > 0)
	{
		app.showPopupLoader();

		BX.Mobile.Ajax.wrap({
			type: 'json',
			method: 'POST',
			url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
			processData: true,
			data: {
				action: 'get_blog_post_data',
				mobile_action: 'get_blog_post_data',
				sessid: BX.bitrix_sessid(),
				site: BX.message('SITE_ID'),
				lang: BX.message('LANGUAGE_ID'),
				post_id: post_id,
				nt: BX.message('MSLNameTemplate'),
				sl: BX.message('MSLShowLogin')
			},
			'callback': function(data)
			{
				app.hidePopupLoader();

				obResult.id = post_id;

				if (
					typeof data.log_id != 'undefined'
					&& parseInt(data.log_id) > 0
				)
				{
					obResult.log_id = data.log_id;
				}

				if (
					typeof data.post_user_id != 'undefined'
					&& parseInt(data.post_user_id) > 0
				)
				{
					obResult.post_user_id = data.post_user_id;
				}

				if (typeof data.PostPerm != 'undefined')
				{
					obResult.PostPerm = data.PostPerm;
				}

				if (typeof data.PostDestination != 'undefined')
				{
					obResult.PostDestination = data.PostDestination;
				}

				if (typeof data.PostDestinationHidden != 'undefined')
				{
					obResult.PostDestinationHidden = data.PostDestinationHidden;
				}

				if (typeof data.PostDetailText != 'undefined')
				{
					obResult.PostDetailText = data.PostDetailText;
				}

				if (typeof data.PostFiles != 'undefined')
				{
					obResult.PostFiles = data.PostFiles;
				}

				if (typeof data.PostBackgroundCode != 'undefined')
				{
					obResult.PostBackgroundCode = data.PostBackgroundCode;
				}

				if (typeof data.PostUFCode != 'undefined')
				{
					obResult.PostUFCode = data.PostUFCode;
				}

				callbackFunc(obResult);
			},
			'callback_failure': function()
			{
				app.hidePopupLoader();
			}
		});
	}
};

BitrixMSL.prototype.getCommentData = function(params, callbackFunc)
{
	var commentType = (typeof params.commentType != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var commentId = (typeof params.commentId != 'undefined' ? parseInt(params.commentId) : 0);

	var obResult = {};

	if (
		commentId > 0
		&& typeof params.postId != 'undefined'
		&& parseInt(params.postId) > 0
	)
	{
		app.showPopupLoader();

		var requestData = {
			action: 'get_comment_data',
			sessid: BX.bitrix_sessid(),
			site: BX.message('SITE_ID'),
			lang: BX.message('LANGUAGE_ID')
		};

		if (commentType == 'blog')
		{
			requestData.mobile_action = 'get_blog_comment_data';
			requestData.comment_id = commentId;
			requestData.post_id = parseInt(params.postId);
		}
		else
		{
			requestData.mobile_action = 'get_log_comment_data';
			requestData.cid = commentId;
			requestData.log_id = parseInt(params.postId);
		}

		BX.Mobile.Ajax.wrap({
			type: 'json',
			method: 'POST',
			url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
			processData: true,
			data: requestData,
			callback: function(data)
			{
				app.hidePopupLoader();

				obResult.id = commentId;

				if (typeof data.CommentCanEdit != 'undefined')
				{
					obResult.CommentCanEdit = data.CommentCanEdit;
				}

				if (typeof data.CommentDetailText != 'undefined')
				{
					obResult.CommentDetailText = data.CommentDetailText;
				}

				if (typeof data.CommentFiles != 'undefined')
				{
					obResult.CommentFiles = data.CommentFiles;
				}

				if (typeof data.CommentUFCode != 'undefined')
				{
					obResult.CommentUFCode = data.CommentUFCode;
				}

				callbackFunc(obResult);
			},
			callback_failure: function()
			{
				app.hidePopupLoader();
			}
		});
	}
};

BitrixMSL.prototype.editBlogPost = function(data)
{
	if (Application.getApiVersion() >= BX.MobileLivefeed.Instance.getApiVersion('layoutPostForm'))
	{
		BX.MobileLivefeed.PostFormManagerInstance.show({
			pageId: BX.MobileLivefeed.Instance.getPageId(),
			postId: data.post_id
		});
	}
	else
	{
		this.getBlogPostData(data.post_id, function(postData)
		{
			oMSL.newPostFormParams = {};

			if (
				typeof postData.PostPerm != 'undefined'
				&& postData.PostPerm >= 'W'
			)
			{
				var selectedDestinations = {
					a_users: [],
					b_groups: []
				};

				oMSL.setPostFormExtraDataArray({
					postId: data.post_id,
					postAuthorId: postData.post_user_id,
					logId: postData.log_id,
					pinnedContext: !!data.pinnedContext,
				});

				if (typeof postData.PostDetailText != 'undefined')
				{
					oMSL.setPostFormParams({
						messageText: postData.PostDetailText
					});
				}

				if (typeof postData.PostDestination != 'undefined')
				{
					for (var key in postData.PostDestination)
					{
						if (!postData.PostDestination.hasOwnProperty(key))
						{
							continue;
						}

						if (
							postData.PostDestination[key]["STYLE"] != 'undefined'
							&& postData.PostDestination[key]["STYLE"] == 'all-users'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'UA'
								}
							);
						}
						else if (
							postData.PostDestination[key]["TYPE"] != 'undefined'
							&& postData.PostDestination[key]["TYPE"] == 'U'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'U',
									id: postData.PostDestination[key]["ID"],
									name: BX.util.htmlspecialcharsback(postData.PostDestination[key]["TITLE"])
								}
							);
						}
						else if (
							postData.PostDestination[key]["TYPE"] != 'undefined'
							&& postData.PostDestination[key]["TYPE"] == 'SG'
						)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'SG',
									id: postData.PostDestination[key]["ID"],
									name: BX.util.htmlspecialcharsback(postData.PostDestination[key]["TITLE"])
								}
							);
						}
					}
				}

				if (typeof postData.PostDestinationHidden != 'undefined')
				{
					oMSL.setPostFormExtraData({
						hiddenRecipients: postData.PostDestinationHidden
					});
				}

				oMSL.setPostFormParams({
					selectedRecipients: selectedDestinations
				});

				if (typeof postData.PostFiles != 'undefined')
				{
					oMSL.setPostFormParams({
						messageFiles: postData.PostFiles
					});
				}

				if (typeof postData.PostUFCode != 'undefined')
				{
					oMSL.setPostFormExtraData({
						messageUFCode: postData.PostUFCode
					});
				}

				app.exec('showPostForm', oMSL.showNewPostForm());
			}
		});
	}
};

BitrixMSL.prototype.drawDetailPage = function(data)
{
	var bReopen = false;

	BX.MobileLivefeed.Instance.setPageId('detail_' + data.log_id);
	BX.MobileLivefeed.Instance.setOptions({
		logId: data.log_id
	});

	if (BX('post_log_id'))
	{
		var existingLogID = parseInt(BX('post_log_id').getAttribute('data-log-id'));
	}

	if (
		typeof existingLogID === 'undefined'
		|| existingLogID != data.log_id
	)
	{
		app.clearInput();
	}

	if (BX('post_log_id'))
	{
		if (
			typeof existingLogID !== 'undefined'
			&& existingLogID == data.log_id
		)
		{
			bReopen = true;
		}
		else
		{
			BX('post_log_id').setAttribute('data-log-id', data.log_id);
			BX('post_log_id').setAttribute('data-ts', data.TS);
			BX.message({
				MSLLogId: data.log_id
			});
		}
	}

	var bBottom = false;

	window.entryType = data.entry_type;

	oMSL.InitDetail({
		commentsType: (data.entry_type == 'blog' ? 'blog' : 'log'),
		detailPageId: (data.entry_type == 'blog' ? 'blog' : 'log') + '_' + (data.entry_type == 'blog' ? data.post_id : data.log_id),
		logId: data.log_id,
		entityXMLId: data.entity_xml_id,
		bUseFollow: (typeof data.use_follow == 'undefined' || data.use_follow != 'NO'),
		bFollow: (typeof data.follow == 'undefined' || data.follow != 'N'),
		feed_id:  (typeof data.feed_id != 'undefined' ? data.feed_id : null),
		entryParams: {
			destinations: (typeof data.destinations != 'undefined' ? data.destinations : null),
			post_perm: (typeof data.post_perm != 'undefined' ? data.post_perm : null),
			post_id: (typeof data.post_id != 'undefined' ? data.post_id : null),
			post_content_type_id: (BX.type.isNotEmptyString(data.post_content_type_id) ? data.post_content_type_id : null),
			post_content_id: (BX.type.isNotEmptyString(data.post_content_id) ? data.post_content_id : null),
		},
		TS: (typeof data.TS != 'undefined' ? data.TS : null),
		readOnly: (data.read_only != 'undefined' ? data.read_only : 'N')
	});

	if (oMSL.emptyCommentsXhr)
	{
		oMSL.emptyCommentsXhr.abort();
	}

	if (
		typeof data.commentsNumAll != 'undefined'
		|| typeof data.commentsNum != 'undefined'
	)
	{
		bBottom = true;
	}

	if (BX('comments_control'))
	{
		BX('comments_control').style.display = (
			typeof data.commentsControl != 'undefined'
			&& data.commentsControl == 'YES'
				? 'inline-block'
				: 'none'
		);
	}

	if (!bReopen)
	{
		var ratingTextNode = document.getElementById('rating_text');

		if (typeof data.ratingText != 'undefined')
		{
			if (BX('rating-footer-wrap'))
			{
				if (typeof data.ratingFooter != 'undefined')
				{
					oMSL.drawRatingFooter(data.ratingFooter);
				}
				else
				{
					BX('rating-footer-wrap').innerHTML = '';
				}

				BX('rating-footer-wrap').style.display = "block";
			}

			if (ratingTextNode)
			{
				if (typeof (data.ratingCounter) != 'undefined')
				{
					ratingTextNode.setAttribute('data-counter', parseInt(data.ratingCounter));
				}

				if (
					BX.type.isNotEmptyString(data.ratingText)
					&& typeof RatingLike !== 'undefined'
				)
				{
					ratingTextNode.innerHTML = data.ratingText;
					ratingTextNode.style.display = 'inline-block';

					var ratingNode = ratingTextNode.querySelector('[data-rating-vote-id]');
					if (ratingNode)
					{
						var ratingVoteId = ratingNode.getAttribute('data-rating-vote-id');
						var ratingVoteEntityTypeId = ratingNode.getAttribute('data-rating-entity-type-id');
						var ratingVoteEntityId = parseInt(ratingNode.getAttribute('data-rating-entity-id'));

						if (
							BX.type.isNotEmptyString(ratingVoteId)
							&& BX.type.isNotEmptyString(ratingVoteEntityTypeId)
							&& ratingVoteEntityId > 0
						)
						{
							RatingLike.Set(
								ratingVoteId,
								ratingVoteEntityTypeId,
								ratingVoteEntityId,
								'Y',
								BX.message('USER_ID'),
								{
									LIKE_Y: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_Y'),
									LIKE_N: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_Y'),
									LIKE_D: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_D'),
								},
								'like_react',
								BX.message('MSLPathToUser'),
								false,
								true
							);
						}
					}
				}
				else
				{
					ratingTextNode.style.display = 'none';
				}
			}

			bBottom = true;

			oMSL.parseAndExecCode(data.ratingText, 0);

			BX.message({
				RVRunEvent: 'Y'
			});
		}
		else
		{
			if (BX('rating-footer-wrap'))
			{
				if (typeof data.ratingFooter != 'undefined')
				{
					oMSL.drawRatingFooter(data.ratingFooter);
				}
				else
				{
					BX('rating-footer-wrap').innerHTML = '';
				}

				BX('rating-footer-wrap').style.display = "none";
			}

			if (BX('rating_button_cont'))
			{
				BX('rating_button_cont').style.display = 'none';
			}

			if (ratingTextNode)
			{
				ratingTextNode.style.display = 'none';
			}
		}
	}

	if (BX('log_entry_follow'))
	{
		if (
			(
				typeof data.use_follow != 'undefined'
				&& data.use_follow == 'N'
			)
			|| typeof data.follow == 'undefined'
		)
		{
			BX.unbindAll(BX('log_entry_follow'));
			BX('log_entry_follow').style.display = 'none';
		}
		else
		{
			bBottom = true;

			// non-displayed follow value
			BX.removeClass(BX('log_entry_follow'), (data.follow == 'N' ? 'post-item-follow-active' : 'post-item-follow'));
			BX.addClass(BX('log_entry_follow'), (data.follow == 'N' ? 'post-item-follow' : 'post-item-follow-active'));
			BX('log_entry_follow').setAttribute('data-follow', (data.follow == 'N' ? 'N' : 'Y'));

			BX.unbindAll(BX('log_entry_follow'));
			BX.bind(BX('log_entry_follow'), 'click', function()
			{
				oMSL.setFollow({
					logId: data.log_id,
					bAjax: true,
					bRunEvent: false
				});
			});
		}
	}

	if (bBottom)
	{
		BX.removeClass(BX('lenta_item'), 'post-without-informers');
	}
	else
	{
		BX.addClass(BX('lenta_item'), 'post-without-informers');
	}

	// draw follow
	if (
		BX('post_item_top_wrap')
		&& !this.getFollowDefaultValue()
	)
	{
		if (
			typeof data.follow != 'undefined'
			&& data.follow == 'Y'
		)
		{
			BX.addClass(BX('post_item_top_wrap'), 'post-item-follow');
		}
		else
		{
			BX.removeClass(BX('post_item_top_wrap'), 'post-item-follow');
		}
	}

	// draw fav for reopen detail
	if (
		bReopen
		&& BX.type.isNotEmptyString(data.fav)
		&& BX('log_entry_favorites_' + data.log_id)
	)
	{
		if (data.fav == 'Y')
		{
			BX('log_entry_favorites_' + data.log_id).classList.add('lenta-item-fav-active');
		}
		else
		{
			BX('log_entry_favorites_' + data.log_id).classList.remove('lenta-item-fav-active');
		}
	}

	var contentNode = BX('post_block_check_cont');

	if (!bReopen)
	{
		if (BX('post-comments-wrap'))
		{
			BX.cleanNode(BX('post-comments-wrap')); // to be sure to clear comments
		}

		this.drawDetailPageText(data);

		var contMore = BX.findChild(contentNode, { className: 'post-more-block' }, true, false);
		if (
			contMore
			&& BX('post_more_limiter')
		)
		{
			if (
				typeof data.showMoreButton != 'undefined'
				&& data.showMoreButton == 'YES'
			)
			{
				BX('post_more_limiter').style.visibility = 'visible';
				BX.bind(BX('post_more_limiter'), 'click', function()
				{
					oMSL.expandText(data.log_id);
				});
			}
			else
			{
				BX('post_more_limiter').style.visibility = 'hidden';
			}
		}

		if (data.bShowFull === "YES")
		{
			contentNode.className = oMSL.classes.postItemBlockFull;
			if (BX('post_more_block_' + data.log_id))
			{
				BX('post_more_block_' + data.log_id).style.display = "none";
			}
			if (BX('post_block_check_more_' + data.log_id))
			{
				BX('post_block_check_more_' + data.log_id).style.display = "none";
			}

			BX('post_more_limiter').style.visibility = 'hidden';
			BitrixMobile.LazyLoad.showImages(false); // when redraw detail 2
		}
		else
		{
			contentNode.className = (data.bIsPhoto == "YES" ? "post-item-post-img-block" : "post-item-post-block");
		}

		if (
			data.bIsImportant === 'YES'
			|| (
				BX.type.isNotEmptyString(data.medal)
				&& data.medal !== 'NO'
			)
		)
		{
			contentNode.classList.add('info-block-background');

			if (data.hasTitle === "YES")
			{
				contentNode.classList.add('info-block-background-with-title');
			}
			else
			{
				contentNode.classList.remove('info-block-background-with-title');
			}

			if (data.bIsImportant === "YES")
			{
				contentNode.classList.add('info-block-important');
			}
			else
			{
				contentNode.classList.remove('info-block-important');
			}

			if (
				BX.type.isNotEmptyString(data.medal)
				&& data.medal !== 'NO'
			)
			{
				contentNode.classList.add('info-block-gratitude');
				contentNode.classList.add('info-block-gratitude-' + data.medal);
			}
			else
			{
				contentNode.classList.remove('info-block-gratitude');
			}
		}
		else
		{
			contentNode.classList.remove('info-block-background');
		}

		Array.from(contentNode.classList).forEach(function(className) {
			if (className.indexOf('ui-livefeed-background') > -1)
			{
				contentNode.classList.remove(className);
			}
		});

		if (
			BX.type.isNotEmptyString(data.backgroundCode)
			&& data.backgroundCode !== 'NO'
		)
		{
			contentNode.classList.add('ui-livefeed-background');
			contentNode.classList.add('ui-livefeed-background-' + data.backgroundCode);
		}

		if (
			data.bSetFocusOnCommentForm != "YES"
			&& data.bSetFocusOnCommentsList != "YES"
		)
		{
			__MSLDetailMoveTop();
		}

		if (BX('post-comments-form-wrap'))
		{
			if (data.can_user_comment !== "YES")
			{
				if (BX("empty_page_bottom_margin"))
				{
					BX("empty_page_bottom_margin").style.display = "none";
				}

				app.hideInput();
			}
		}
	}
	else // bReopen
	{
		var postTopBlock = BX('post_item_top');
		if (postTopBlock)
		{
			if (typeof data.topText !== 'undefined')
			{
				postTopBlock.innerHTML = data.topText;
				var postScripts = oMSL.parseAndExecCode(data.topText, 0, false, true);
				setTimeout(function() {
					BX.MobileLivefeed.Instance.sendErrorEval(postScripts);
				}, 0);
			}
		}
	}

	if (data.bSetFocusOnCommentForm == "YES")
	{
		oMSL.detailPageFocus = 'form';
	}
	else if (data.bSetFocusOnCommentsList == "YES")
	{
		oMSL.detailPageFocus = 'list';
	}
	else if (data.bSetFocusOnCommentsList == "YES")
	{
		oMSL.detailPageFocus = null;
	}

	if (
		BX('post-comments-wrap')
		&& (
			!bReopen
			|| oMSL.emptyRefreshCommentsFlag // after UIApplicationDidBecomeActiveNotification
		)
	)
	{
		// get comments when draw detail page/empty
		oMSL.getComments({
			ts: data.TS,
			bPullDown: false,
			bPullDownTop: false,
			obFocus: {
				form: data.bSetFocusOnCommentForm,
				comments: data.bSetFocusOnCommentsList
			}
		});

		oMSL.emptyRefreshCommentsFlag = false;
	}

	oMSL.adjustDetailPageFocus();

	BX.removeAllCustomEvents('main.post.form/mobile_simple');
	BX.addCustomEvent('main.post.form/mobile_simple', function() {
		setTimeout(oMSL.adjustDetailPageFocus, 150);
	});
};

BitrixMSL.prototype.adjustDetailPageFocus = function()
{
	if (oMSL.detailPageFocus)
	{
		oMSL.setFocusOnComments(oMSL.detailPageFocus);
	}
};

BitrixMSL.prototype.drawDetailPageText = function(data)
{
	var postScripts = '';
	var logId = (typeof data.logID != 'undefined' ? parseInt(data.logID) : 0);

	var postBlock = (logId && BX('post_block_check_cont_' + logId) ? BX('post_block_check_cont_' + logId) : BX('post_block_check_cont'));
	var postTopBlock = (logId && BX('post_item_top_' + logId) ? BX('post_item_top_' + logId) : BX('post_item_top'));
	var filesBlock = (logId && BX('post_block_files_' + logId) ? BX('post_block_files_' + logId) : BX('post_block_files'));

	if (postBlock || filesBlock)
	{
		BitrixMobile.LazyLoad.clearImages();
	}

	if (postBlock)
	{
		postBlock.innerHTML = '';
		if (BX.type.isNotEmptyString(data.detailText))
		{
			postBlock.innerHTML = data.detailText;
			if (BX.type.isNotEmptyString(data.inlineScript))
			{
				postBlock.innerHTML += data.inlineScript;
			}

			postScripts += oMSL.parseAndExecCode(postBlock.innerHTML, 0, false, true);
		}
	}

	if (filesBlock)
	{
		filesBlock.innerHTML = '';
		if (BX.type.isNotEmptyString(data.filesBlockText))
		{
			filesBlock.innerHTML = data.filesBlockText;
			postScripts += oMSL.parseAndExecCode(data.filesBlockText, 0, false, true);
		}
	}

	if (postTopBlock)
	{
		postTopBlock.innerHTML = '';
		if (typeof data.topText !== 'undefined')
		{
			postTopBlock.innerHTML = data.topText;
			postScripts += oMSL.parseAndExecCode(data.topText, 0, false, true);
		}
	}

	setTimeout(function()
	{
		BX.MobileLivefeed.Instance.sendErrorEval(postScripts);
		BitrixMobile.LazyLoad.showImages(); // when redraw detail
		if (
			BX.message('MSLLoadScriptsNeeded') == 'Y'
			&& typeof(oMSL) === "object"
		)
		{
			oMSL.loadScripts();
		}
	}, 0);
};

BitrixMSL.prototype.onLogEntryPostUpdated = function(data)
{
	var postScripts = '';

	if (typeof data.logID !== 'undefined')
	{
		if (
			typeof data.detailText !== 'undefined'
			&& BX('post_block_check_cont_' + parseInt(data.logID))
		)
		{
			BX('post_block_check_cont_' + parseInt(data.logID)).innerHTML = data.detailText;
			postScripts += oMSL.parseAndExecCode(data.detailText, 0, false, true);
		}

		if (
			typeof data.filesBlockText !== 'undefined'
			&& BX('post_block_files_' + parseInt(data.logID))
		)
		{
			BX('post_block_files_' + parseInt(data.logID)).innerHTML = data.filesBlockText;
			postScripts += oMSL.parseAndExecCode(data.filesBlockText, 0, false, true);
		}

		if (
			typeof data.topText !== 'undefined'
			&& BX('post_item_top_' + parseInt(data.logID))
		)
		{
			BX('post_item_top_' + parseInt(data.logID)).innerHTML = data.topText;
			postScripts += oMSL.parseAndExecCode(data.topText, 0, false, true);
		}

		setTimeout(function()
		{
			BX.MobileLivefeed.Instance.sendErrorEval(postScripts);
			BitrixMobile.LazyLoad.showImages(); // when redraw detail
			if (
				BX.message('MSLLoadScriptsNeeded') == 'Y'
				&& typeof(oMSL) === "object"
			)
			{
				oMSL.loadScripts();
			}
		}, 0);
	}
};

BitrixMSL.prototype.changeCounter = function(cnt, zeroTS, serverTime)
{
	if (this.counterTimeout !== null)
	{
		clearTimeout(this.counterTimeout);
		this.counterTimeout = null;
	}

	zeroTS = (typeof zeroTS != 'undefined' ? parseInt(zeroTS) : 0);
	serverTime = (typeof serverTime != 'undefined' ? parseInt(serverTime) : 0);

	var zeroTime = (zeroTS > 0 ? zeroTS : (serverTime > 0 ? serverTime : 0));
	this.counterValue = cnt;

	if (parseInt(oMSL.counterValue) > 0)
	{
		BX.MobileLivefeed.BalloonNotifierInstance.showNotifier({
			counterValue: oMSL.counterValue
		});
	}
	else
	{
		this.counterTimeout = setTimeout(function()
		{
			if (zeroTime > BX.MobileLivefeed.Instance.getOption('frameCacheTs', 0)) // counter is null but cache is too old
			{
				BX.MobileLivefeed.Instance.setRefreshNeeded(true);
			}

			if (
				BX.MobileLivefeed.Instance.getRefreshNeeded()
				&& !BX.MobileLivefeed.Instance.getRefreshStarted()
			)
			{
				BX.MobileLivefeed.BalloonNotifierInstance.showRefreshNeededNotifier();
			}
			else
			{
				BX.MobileLivefeed.BalloonNotifierInstance.hideNotifier();
			}

			clearTimeout(oMSL.counterTimeout);
		}, 2000);
	}
};

BitrixMSL.prototype.editComment = function(params)
{
	commentType = (typeof params.commentType == 'undefined' ? 'log' : params.commentType);
	postId = (typeof params.postId == 'undefined' ? 0 : parseInt(params.postId));
	nodeId = (typeof params.nodeId == 'undefined' ? "" : params.nodeId);

	if (
		(
			typeof (params.commentText) != 'string'
			&& typeof (params.commentText) != 'number'
		)
		|| params.commentText.length <= 0
		|| parseInt(params.commentId) <= 0
	)
	{
		return;
	}

	this.getCommentData(
		{
			commentType: commentType,
			commentId: params.commentId,
			postId: postId
		},
		function(commentData)
		{
			oMSL.newPostFormParams = {};

			if (
				typeof commentData.CommentCanEdit != 'undefined'
				&& commentData.CommentCanEdit == 'Y'
			)
			{
				oMSL.setPostFormExtraDataArray({
					commentId: params.commentId,
					commentType: commentType,
					postId: postId,
					nodeId: nodeId
				});

				if (typeof commentData.CommentDetailText != 'undefined')
				{
					oMSL.setPostFormParams({
						messageText: commentData.CommentDetailText
					});
				}

				if (typeof commentData.CommentFiles != 'undefined')
				{
					oMSL.setPostFormParams({
						messageFiles: commentData.CommentFiles
					});
				}

				if (typeof commentData.CommentUFCode != 'undefined')
				{
					oMSL.setPostFormExtraData({
						messageUFCode: commentData.CommentUFCode
					});
				}

				app.exec('showPostForm', oMSL.showNewPostForm({
					entityType: 'comment'
				}));
			}
		}
	);
};

BitrixMSL.prototype.deleteComment = function(params)
{
	if (parseInt(params.commentId) <= 0)
	{
		return;
	}

	var
		commentType = (typeof params.commentType == 'undefined' ? 'log' : params.commentType),
		nodeId = (typeof params.nodeId == 'undefined' ? "" : params.nodeId);

	oMSL.showCommentWait({
		nodeId: nodeId,
		status: true
	});
	BXMobileApp.UI.Page.TextPanel.clear();

	if (commentType == 'blog')
	{
		BX.Mobile.Ajax.wrap({
			'type': 'html',
			'method': 'GET',
			'url': commentVarURL + '&sessid=' + BX.bitrix_sessid() + '&delete_comment_id=' + params.commentId,
			'data': '',
			'callback': function(response)
			{
				if (
					response != "*"
					&& response.length > 0
				)
				{
					oMSL.hideComment(BX(nodeId), commentType);
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: nodeId,
						action: 'delete',
						commentType: commentType,
						callback: function()
						{
							oMSL.deleteComment({
								commentId: params.commentId,
								commentType: commentType,
								nodeId: nodeId
							});
						}
					});
				}
			},
			'callback_failure': function()
			{
				oMSL.showCommentAlert({
					nodeId: nodeId,
					action: 'delete',
					commentType: commentType,
					callback: function()
					{
						oMSL.deleteComment({
							commentId: params.commentId,
							commentType: commentType,
							nodeId: nodeId
						});
					}
				});
			}
		});
	}
	else
	{
		var post_data = {
			sessid: BX.bitrix_sessid(),
			site: commentVarSiteID,
			lang: commentVarLanguageID,
			log_id: oMSL.getLogId(),
			delete_id: params.commentId,
			action: 'delete_comment',
			mobile_action: 'delete_comment'
		};

		BX.Mobile.Ajax.wrap({
			'type': 'json',
			'method': 'POST',
			'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
			'data': post_data,
			'callback': function(post_response_data)
			{
				if (
					post_response_data["commentID"] != 'undefined'
					&& parseInt(post_response_data["commentID"]) > 0
					&& parseInt(post_response_data["commentID"]) == params.commentId
				)
				{
					oMSL.hideComment(BX(nodeId), commentType);
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: nodeId,
						action: 'delete',
						commentType: commentType,
						callback: function()
						{
							oMSL.deleteComment({
								commentId: params.commentId,
								commentType: commentType,
								nodeId: nodeId
							});
						}
					});
				}
			},
			'callback_failure': function()
			{
				oMSL.showCommentAlert({
					nodeId: nodeId,
					action: 'delete',
					commentType: commentType,
					callback: function()
					{
						oMSL.deleteComment({
							commentId: params.commentId,
							commentType: commentType,
							nodeId: nodeId
						});
					}
				});
			}
		});
	}
};

BitrixMSL.prototype.hideComment = function(commentNode, commentType)
{
	commentType = (typeof commentType == 'undefined' ? 'log' : commentType);

	BX.cleanNode(commentNode, true);

	var log_id = (commentType == 'blog' ? BX.message('SBPClogID') : 0);
	var old_value = 0;
	var val = false;

	if (BX('informer_comments_' + log_id))
	{
		old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments_' + log_id).innerHTML = (val > 0 ? val : '');
		}
	}
	else if (BX('informer_comments_common'))
	{
		old_value = (BX('informer_comments_common').innerHTML.length > 0 ? parseInt(BX('informer_comments_common').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments_common').innerHTML = (val > 0 ? val : '');
		}
	}
	else if (BX('informer_comments'))
	{
		old_value = (BX('informer_comments').innerHTML.length > 0 ? parseInt(BX('informer_comments').innerHTML) : 0);
		if (old_value > 0)
		{
			val = old_value - 1;
			BX('informer_comments').innerHTML = (val > 0 ? val : '');
		}
	}

	if (BX('comcntleave-all'))
	{
		old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			if (val > 0)
			{
				BX('comcntleave-all').innerHTML = val;
			}
			else
			{
				BX('comcntleave-all').style.dusplay = "none";
			}
		}
	}

	if (BX('comcntleave-old'))
	{
		old_value = (BX('comcntleave-old').innerHTML.length > 0 ? parseInt(BX('comcntleave-old').innerHTML) : 0);

		if (old_value > 0)
		{
			val = old_value - 1;
			if (val > 0)
			{
				BX('comcntleave-old').innerHTML = val;
			}
			else
			{
				BX('comcntleave-old').style.dusplay = "none";
			}
		}
	}

	if (val !== false)
	{
		BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', { log_id: log_id, num: val}, true);
	}
};

BitrixMSL.prototype.createCommentInputForm = function(params)
{
	BXMobileApp.UI.Page.TextPanel.setParams(params);
	BXMobileApp.UI.Page.TextPanel.clear();
	BXMobileApp.UI.Page.TextPanel.show();
};

BitrixMSL.prototype.parseAndExecCode = function(text, timeout, bExec, bReturnScripts)
{
	if (
		typeof text != 'string'
		|| text.length <= 0
	)
	{
		return;
	}

	timeout = (typeof timeout == 'undefined' ? 500 : parseInt(timeout));
	bExec = (typeof bExec == 'undefined' ? true : !!bExec);
	bReturnScripts = !!bReturnScripts;

	var obParserResult = BX.processHTML(text);
	var parsedScripts = '';

	if (
		obParserResult != null
		&& obParserResult.SCRIPT != null
		&& typeof obParserResult.SCRIPT != 'undefined'
	)
	{

		for (var j = 0; j < obParserResult.SCRIPT.length; j++)
		{
			if (obParserResult.SCRIPT[j].isInternal)
			{
				parsedScripts += ';' + obParserResult.SCRIPT[j].JS;
			}
		}

		if (
			bExec
			&& parsedScripts.length > 0
		)
		{
			setTimeout(function() {
				BX.MobileLivefeed.Instance.sendErrorEval(parsedScripts);
			}, timeout);
		}
	}

	return (bReturnScripts ? parsedScripts : false);
};

BitrixMSL.prototype.replaceUserPath = function(text)
{
	if (
		typeof text != 'string'
		|| text.length <= 0
	)
	{
		return;
	}

	if (BX('MSLIsExtranetSite') == 'Y')
	{
		text = text.replace('/mobile/users/?user_id=', BX.message('MSLExtranetSiteDir') + 'mobile/users/?user_id=');
	}
	else
	{
		text = text.replace(BX.message('MSLExtranetSiteDir') + 'mobile/users/?user_id=', '/mobile/users/?user_id=');
	}

	text = text.replace( // anchor
		new RegExp("[\\w\/]+\/personal\/user\/(\\d+)\/\"", 'igm'),
		(
			BX('MSLIsExtranetSite') == 'Y'
				? BX.message('MSLExtranetSiteDir') + 'mobile/users/?user_id=$1"'
				: '/mobile/users/?user_id=$1"'
		)
	);

	return text;
};

BitrixMSL.prototype.createCommentMenu = function(commentNode, arComment, voteId)
{
	BX.bind(commentNode, 'click', function(event)
	{
		event = event||window.event;
		if (event.target.tagName.toUpperCase() == 'A')
		{
			return false;
		}

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'DIV', 'className': 'post-comment-text' } );
		if (anchorNode)
		{
			return false;
		}

		var arCommentMenu = [];

		arCommentMenu.push({
			title: BX.message('MSLReply'),
			callback: function()
			{
				oMSL.replyToComment(arComment["EVENT"]["USER_ID"], BX.util.htmlspecialcharsback(arComment["CREATED_BY"]["FORMATTED"]));
			}
		});

		if (
			typeof arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"] != 'undefined'
			&& parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) > 0
			&& typeof voteId != 'undefined'
			&& voteId
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLLikesList'),
				callback: function()
				{
					RatingLikeComments.List(voteId);
				}
			});
		}

		if (
			BX.type.isNotEmptyObject(arComment["EVENT_FORMATTED"])
			&& BX.type.isNotEmptyString(arComment["EVENT_FORMATTED"]["CAN_EDIT"])
			&& arComment["EVENT_FORMATTED"]["CAN_EDIT"] == "Y"
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLCommentMenuEdit'),
				callback: function()
				{
					oMSL.editComment({
						commentId: arComment["EVENT"]["ID"],
						commentText: arComment["EVENT"]["MESSAGE"],
						commentType: 'log',
						postId: arComment["EVENT"]["LOG_ID"],
						nodeId: commentNode.id
					});
				}
			});
		}

		if (
			typeof arComment["EVENT_FORMATTED"] != 'undefined'
			&& typeof arComment["EVENT_FORMATTED"]["CAN_DELETE"] != 'undefined'
			&& arComment["EVENT_FORMATTED"]["CAN_DELETE"] == "Y"
		)
		{
			arCommentMenu.push({
				title: BX.message('MSLCommentMenuDelete'),
				callback: function()
				{
					oMSL.deleteComment({
						commentId: arComment["EVENT"]["ID"],
						commentType: 'log',
						nodeId: commentNode.id
					});
				}
			});
		}

		oMSL.showCommentMenu(arCommentMenu);
	});
};

BitrixMSL.prototype.showCommentMenu = function(arButtons)
{
	var action = new BXMobileApp.UI.ActionSheet({
			buttons: arButtons
		},
		"commentSheet"
	);
	action.show();
};

BitrixMSL.prototype.InitDetail = function(params)
{
	this.commentsType = (typeof (params.commentsType) != 'undefined' && params.commentsType == 'blog' ? 'blog' : 'log');
	this.entityXMLId = (typeof (params.entityXMLId) != 'undefined' ? params.entityXMLId : '');
	this.bFollow = !(typeof (params.bFollow) != 'undefined' && !params.bFollow);
	this.commentTextCurrent = '';
	this.arMention = [];
	this.iDetailTs = (typeof (params.TS) != 'undefined' ? params.TS : 0);

	if (!this.bKeyboardCaptureEnabled)
	{
		app.enableCaptureKeyboard(true);
		this.bKeyboardCaptureEnabled = true;

		BX.addCustomEvent("onKeyboardWillShow", BX.delegate(function()
		{
			this.keyboardShown = true;
		}, this));

		BX.addCustomEvent("onKeyboardDidHide", BX.delegate(function()
		{
			this.keyboardShown = false;
		}, this));
	}

	if (
		BX.type.isNotEmptyString(params.detailPageId)
		&& this.detailPageId != params.detailPageId
	)
	{
		this.detailPageId = params.detailPageId;
		if (
			typeof (params.logId) != 'undefined'
			&& parseInt(params.logId) > 0
		)
		{
			this.logId = parseInt(params.logId);
		}

		BXMobileApp.addCustomEvent("onMPFCommentSent", BX.proxy(function(post_data)
		{
			if (
				post_data.detailPageId == this.detailPageId
				&& post_data.data.action == 'EDIT_COMMENT'
				&& post_data.data.text.length > 0
				&& parseInt(post_data.data.commentId) > 0
			)
			{
				if (this.commentsType == 'blog')
				{
					blogCommentsNativeInputCallback({
						text: post_data.data.text,
						oPreviewComment: null,
						commentId: post_data.data.commentId,
						nodeId: post_data.nodeId,
						ufCode: post_data.ufCode,
						attachedFiles: post_data.data[ufCode],
						attachedFilesRaw: post_data.data.attachedFilesRaw
					});
				}
				else
				{
					commentsNativeInputCallback({
						text: post_data.data.text,
						oPreviewComment: null,
						commentId: post_data.data.commentId,
						nodeId: post_data.nodeId,
						ufCode: post_data.ufCode,
						attachedFiles: post_data.data[ufCode],
						attachedFilesRaw: post_data.data.attachedFilesRaw
					});
				}
			}
		}, this));
	}

	this.menuData = {
		entry_type: this.commentsType,
		destinations: (typeof params.entryParams != 'undefined' && params.entryParams.destinations != 'undefined' ? params.entryParams.destinations : {}),
		post_perm: (typeof params.entryParams != 'undefined' && params.entryParams.post_perm != 'undefined' ? params.entryParams.post_perm : null),
		post_id: (typeof params.entryParams != 'undefined' && params.entryParams.post_id != 'undefined' ? params.entryParams.post_id : null),
		feed_id: (typeof params.feed_id != 'undefined' ? params.feed_id : null),
		read_only: (typeof params.readOnly != 'undefined' && params.readOnly == 'Y' ? 'Y' : 'N'),
		post_content_type_id: (typeof params.entryParams != 'undefined' && BX.type.isNotEmptyString(params.entryParams.post_content_type_id) ? params.entryParams.post_content_type_id : null),
		post_content_id: (typeof params.entryParams != 'undefined' && typeof params.entryParams.post_content_id != 'undefined' ? parseInt(params.entryParams.post_content_id) : null)
	};

	this.detailPageMenuItems = this.buildDetailPageMenu(this.menuData);
	this.initPageMenu('detail');

	if (Application.getApiVersion() >= 34)
	{
		setTimeout(function() {
			var postInstance = BX.MobileLivefeed.Instance.getPostFromLogId(this.getLogId());
			if (postInstance)
			{
				postInstance.initDetailPin();
			}
		}.bind(this), 0);
	}
};

BitrixMSL.prototype.showNewComment = function(params)
{
	var text = (typeof params.text != 'undefined' ? params.text : '');
	var arComment = (typeof params.arComment != 'undefined' ? params.arComment : false);
	var oPreviewComment = (typeof params.oPreviewComment != 'undefined' ? params.oPreviewComment : false);
	var bClearForm = (typeof params.bClearForm != 'undefined' ? !!params.bClearForm : false);
	var bReplace = (typeof params.bReplace != 'undefined' ? !!params.bReplace : false);
	var bIncrementCounters = (typeof params.bIncrementCounters != 'undefined' ? !!params.bIncrementCounters : true);
	var bShowImages = (typeof params.bShowImages != 'undefined' ? !!params.bShowImages : true);

	if (
		!!oPreviewComment
		&& typeof oMSL.entityXMLId != 'undefined'
		&& BX('entry-comment-' + oMSL.entityXMLId + '-' + params.commentId)
	)
	{
		BX.cleanNode(BX(params.oPreviewComment), true);
		return;
	}

	var newCommentNode = null;

	if (arComment)
	{
		if (
			!bReplace
			&& BX('entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"])
		)
		{
			return;
		}

		var avatar = (
			arComment["AVATAR_SRC"]
			&& typeof arComment["AVATAR_SRC"] != 'undefined'
				? BX.create('DIV', {
					props:
					{
						className: 'avatar'
					},
					style:
					{
						backgroundImage: "url('" + arComment["AVATAR_SRC"] + "')",
						backgroundRepeat: "no-repeat"
					}
				})
				: BX.create('DIV', {
					props:
					{
						className: 'avatar'
					}
				})
		);

		var anchor_id = Math.floor(Math.random()*100000) + 1;

		var ratingNode = (
			typeof (arComment["EVENT"]) != 'undefined'
			&& typeof (arComment["EVENT"]["RATING_TYPE_ID"]) != 'undefined'
			&& arComment["EVENT"]["RATING_TYPE_ID"].length > 0
			&& typeof (arComment["EVENT"]["RATING_ENTITY_ID"]) != 'undefined'
			&& parseInt(arComment["EVENT"]["RATING_ENTITY_ID"]) > 0
			&& typeof (arComment["EVENT"]["RATING_USER_VOTE_VALUE"]) != 'undefined'
				? oMSL.buildCommentRatingNode(arComment, anchor_id)
				: null
		);

		var replyNode = oMSL.buildCommentReplyNode({
			EVENT: {
				USER_ID: arComment.USER_ID
			},
			CREATED_BY: {
				FORMATTED: BX.util.htmlspecialcharsback(arComment.CREATED_BY.FORMATTED)
			}
		});

		UFNode = (
			typeof arComment["UF_FORMATTED"] != 'undefined'
			&& arComment["UF_FORMATTED"].length > 0
				? BX.create('div', {
						props:
						{
							className: 'post-item-attached-file-wrap',
							id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"] + '-files'
						},
						html: arComment['UF_FORMATTED']
					})
				: null
		);

		newCommentNode = BX.create('DIV', {
			attrs: {
				id: 'entry-comment-' + oMSL.entityXMLId + '-' + arComment["SOURCE_ID"]
			},
			props: {
				className: 'post-comment-block'
			},
			children: [
				BX.create('DIV', {
					props:
					{
						className: 'post-user-wrap'
					},
					children: [
						avatar,
						BX.create('DIV', {
							props:
							{
								className: 'post-comment-cont'
							},
							children: [
								BX.create('A', {
									props: {
										className: 'post-comment-author'
									},
									attrs:
									{
										href: arComment["CREATED_BY"]["URL"]
									},
									html: arComment["CREATED_BY"]["FORMATTED"]
								}),
								BX.create('DIV', {
									props:
									{
										className: 'post-comment-time'
									},
									html: arComment["LOG_TIME_FORMAT"]
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					props:
					{
						className: 'post-comment-text'
					},
					html: (
						typeof arComment["MESSAGE_FORMAT_MOBILE"] != 'undefined'
						&& arComment["MESSAGE_FORMAT_MOBILE"].length > 0
							? arComment["MESSAGE_FORMAT_MOBILE"]
							: arComment["MESSAGE_FORMAT"]
					)
				}),
				UFNode,
				ratingNode,
				replyNode
			]
		});
	}
	else
	{
		newCommentNode = BX.create('DIV', { html: text} );
	}

	if (!!oPreviewComment)
	{
		if (bReplace)
		{
			oPreviewComment.parentNode.insertBefore(newCommentNode, oPreviewComment);
		}
		BX.cleanNode(BX(oPreviewComment), true);
	}

	if (!bReplace)
	{
		BX('post-comment-last-after').parentNode.insertBefore(newCommentNode, BX('post-comment-last-after'));
	}

	var voteId = false;

	if (
		arComment
		&& ratingNode
	)
	{
		if (
			!window.RatingLikeComments
			&& top.RatingLikeComments
		)
		{
			RatingLikeComments = top.RatingLikeComments;
		}

		voteId = arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id;

		RatingLikeComments.Set(
			voteId,
			arComment["EVENT"]["RATING_TYPE_ID"],
			arComment["EVENT"]["RATING_ENTITY_ID"],
			'Y'
		);
	}

	if (arComment)
	{
		var menuCommentData = {
			EVENT: {
				ID: arComment.EVENT.ID,
				LOG_ID: arComment.EVENT.LOG_ID,
				USER_ID: arComment.USER_ID,
				MESSAGE: arComment.MESSAGE,
				RATING_TOTAL_POSITIVE_VOTES: arComment.EVENT.RATING_TOTAL_POSITIVE_VOTES
			},
			EVENT_FORMATTED: {
				CAN_EDIT: arComment.CAN_EDIT,
				CAN_DELETE: arComment.CAN_DELETE
			},
			CREATED_BY: arComment.CREATED_BY
		};

		oMSL.createCommentMenu(newCommentNode, menuCommentData, voteId);
	}

	if (
		bClearForm
		&& BX('comment_send_form_comment')
	)
	{
		BX('comment_send_form_comment').value = '';
	}

	if (bIncrementCounters)
	{
		oMSL.incrementCounters(oMSL.getLogId());
	}

	if (typeof arComment['UF_FORMATTED'] != 'undefined')
	{
		oMSL.parseAndExecCode(arComment['UF_FORMATTED'], 0);
	}

	if (bShowImages)
	{
		setTimeout(function()
		{
			BitrixMobile.LazyLoad.showImages();
		}, 500);
	}
};

BitrixMSL.prototype.showPreviewComment = function(text)
{
	if (!text)
		return false;
	var emptyComment = BX('empty_comment', true);
	var previewCommentID = Math.floor(Math.random()*100000) + 1;
	var lastCommentAfter = BX('post-comment-last-after');

	if (emptyComment && lastCommentAfter)
	{
		var previewComment = BX.clone(emptyComment, true);

		BX.adjust(BX(previewComment), {
			attrs: {
				id: 'new_comment_' + previewCommentID
			}
		});
		var previewCommentText = BX.findChild(previewComment, { className: 'post-comment-text' }, true, false);
		previewCommentText.innerHTML = text.replace(/\n/g, "<br />");
		lastCommentAfter.parentNode.insertBefore(previewComment, lastCommentAfter);
		BX(previewComment).style.display = "block";

		// animate scrolling
		var postCard  = document.body;
		var maxScrollTop = postCard.scrollHeight - postCard.offsetHeight;
		var delta = (window.platform == "android" ? 600 : 120);

		if (
			postCard
			&& postCard.scrollTop >= (maxScrollTop - delta)
		)
		{
			BitrixAnimation.animate({
				duration : 1000,
				start : { scroll : postCard.scrollTop },
				finish : { scroll : postCard.scrollTop + delta + 20 },
				transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
				step : function(state)
				{
					window.scrollTo(0, state.scroll);
				},
				complete : function(){}
			});
		}
	}

	return (!!previewComment ? BX(previewComment) : false);
};

BitrixMSL.prototype.showCommentWait = function(params)
{
	if (typeof params.nodeId != 'undefined')
	{
		var authorBlock = BX.findChild(BX(params.nodeId), { className: 'post-comment-cont' }, true, false);
		var waitBlock = false;
		var undeliveredBlock = false;

		if (authorBlock)
		{
			waitBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-wait' }, true, false);
			undeliveredBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-undelivered' }, true, false);
		}

		if (params.status)
		{
			if (!waitBlock)
			{
				authorBlock.appendChild(BX.create('DIV', {
					props: {
						id: params.nodeId + '-status',
						className: 'post-comment-preview-wait'
					}
				}));
			}

			if (!undeliveredBlock)
			{
				authorBlock.appendChild(BX.create('DIV', {
					props: {
						className: 'post-comment-preview-undelivered'
					}
				}));
			}
		}
	}
};

BitrixMSL.prototype.showCommentAlert = function(params)
{
	var commentType = (typeof (params.commentType) != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var commentId = (typeof (params.commentId) != 'undefined' && params.commentId ? params.commentId : 0);
	var text = (typeof (params.text) != 'undefined' && params.text ? params.text : '');
	var callback = (typeof (params.callback) != 'undefined' ? params.callback : false);
	var action = (typeof (params.action) != 'undefined' ? params.action : false);

	if (typeof params.nodeId != 'undefined')
	{
		if (action == 'add')
		{
			this.alertPreviewComment({
				nodeId: params.nodeId,
				text: text,
				commentType: commentType,
				commentId: commentId,
				action: action,
				callback: callback
			});
		}
		else
		{
			var authorBlock = BX.findChild(BX(params.nodeId), { className: 'post-comment-cont' }, true, false);
			if (authorBlock)
			{
				var undeliveredBlock = BX.findChild(authorBlock, { className: 'post-comment-preview-undelivered' }, true, false);

				if (!undeliveredBlock)
				{
					authorBlock.appendChild(BX.create('DIV', {
						props: {
							id: params.nodeId + '-status',
							className: 'post-comment-preview-undelivered'
						},
						style: { display: "block" }
					}));
				}

				this.alertPreviewComment({
					nodeId: params.nodeId,
					text: text,
					commentType: commentType,
					commentId: commentId,
					action: action,
					callback: callback
				});
			}
		}
	}
};

BitrixMSL.prototype.alertPreviewComment = function(params)
{
	var commentId = (typeof (params.commentId) != 'undefined' && params.commentId ? params.commentId : 0);
	var commentType = (typeof (params.commentType) != 'undefined' && params.commentType == 'blog' ? 'blog' : 'log');
	var text = (typeof (params.text) != 'undefined' && params.text ? params.text : '');
	var callback = (typeof (params.callback) != 'undefined' ? params.callback : false);

	if (typeof params.nodeId != 'undefined')
	{
		var previewCommentWaiter = BX.findChild(BX(params.nodeId), { className: 'post-comment-preview-wait' }, true, false);
		var previewCommentUndelivered = BX.findChild(BX(params.nodeId), { className: 'post-comment-preview-undelivered' }, true, false);

		if (
			!!previewCommentWaiter
			&& !!previewCommentUndelivered
		)
		{
			BX(previewCommentWaiter).style.display = "none";
			BX(previewCommentUndelivered).style.display = "block";

			BX.bind(BX(previewCommentUndelivered), 'click', function()
			{
				BX.unbindAll(BX(previewCommentUndelivered));
				BX(previewCommentWaiter).style.display = "block";
				BX(previewCommentUndelivered).style.display = "none";

				if (callback)
				{
					callback();
				}
			});
		}
	}
};

BitrixMSL.prototype.setFollow = function(params)
{
	var
		logId = (typeof params.logId != 'undefined' ? parseInt(params.logId) : 0),
		pageId = (typeof params.pageId != 'undefined' ? params.pageId : false),
		bOnlyOn = (typeof params.bOnlyOn != 'undefined' ? params.bOnlyOn : false),
		bRunEvent = (typeof params.bRunEvent != 'undefined' ? params.bRunEvent : true),
		bAjax = (typeof params.bAjax != 'undefined' ? params.bAjax : false),
		menuNode = (typeof params.menuNode != 'undefined' && BX(params.menuNode) ? BX(params.menuNode) : null);

	if (!menuNode)
	{
		menuNode = BX('log-entry-menu-' + logId);
	}

	if (bOnlyOn == 'NO')
	{
		bOnlyOn = false;
	}

	var followBlock = BX('log_entry_follow_' + logId);
	if (!followBlock)
	{
		followBlock = BX('log_entry_follow');
	}

	var followWrap = BX('post_item_top_wrap_' + logId);
	if (!followWrap)
	{
		followWrap = BX('post_item_top_wrap');
	}

	var strFollowOld = null;

	if (menuNode)
	{
		strFollowOld = (menuNode.getAttribute("data-follow") == "Y" ? "Y" : "N");
	}
	else if (followBlock)
	{
		strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
	}

	if (!strFollowOld)
	{
		return false;
	}

	var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");

	if (
		(
			!BX.type.isNotEmptyString(this.detailPageId)
			|| this.detailPageId != pageId
		)
		&& (
			!bOnlyOn
			|| strFollowOld == "N"
		)
	)
	{
		if (followBlock)
		{
			BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
			BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
			followBlock.setAttribute("data-follow", strFollowNew);
		}
		if (menuNode)
		{
			menuNode.setAttribute("data-follow", strFollowNew);
		}

		if (bRunEvent)
		{
			BXMobileApp.onCustomEvent('onLogEntryFollow', {
				logId: logId,
				pageId: (BX.type.isNotEmptyString(oMSL.detailPageId) ? oMSL.detailPageId : ''),
				bOnlyOn: (bOnlyOn ? 'Y' : 'N')
			}, true);
		}

		if (
			!this.getFollowDefaultValue()
			&& followWrap
		)
		{
			if (strFollowOld == "Y")
			{
				BX.removeClass(followWrap, 'post-item-follow');
			}
			else
			{
				BX.addClass(followWrap, 'post-item-follow');
			}
		}

		if (BX.type.isNotEmptyString(this.detailPageId))
		{
			this.setFollowValue(strFollowNew == "Y");
			this.setFollowMenuItemName();
		}
	}

	if (bAjax)
	{
		BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.changeFollow', {
			data: {
				logId: logId,
				value: strFollowNew
			},
			analyticsLabel: {
				b24statAction: (strFollowNew == 'Y' ? 'setFollow' : 'setUnfollow')
			}
		}).then(function(response) {
			if (!response.data.success)
			{
				if (followBlock)
				{
					BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
					BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
					followBlock.setAttribute("data-follow", strFollowOld);
				}
				if (menuNode)
				{
					menuNode.setAttribute("data-follow", strFollowOld);
				}

				if (BX.type.isNotEmptyString(this.detailPageId))
				{
					this.setFollowValue(strFollowOld == "Y");
					this.setFollowMenuItemName();
				}

				if (
					!this.getFollowDefaultValue()
					&& followWrap
				)
				{
					if (strFollowOld == "Y")
					{
						BX.addClass(followWrap, 'post-item-follow');
					}
					else
					{
						BX.removeClass(followWrap, 'post-item-follow');
					}
				}

				if (this.getLogId() > 0)
				{
					BXMobileApp.onCustomEvent('onLogEntryFollow', {
						logId: logId,
						pageId: (BX.type.isNotEmptyString(this.detailPageId) ? this.detailPageId : ''),
						bOnlyOn: (bOnlyOn ? 'Y' : 'N')
					}, true);
				}
			}
		}.bind(this), function(response) {
			if (followBlock)
			{
				BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
				BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
				followBlock.setAttribute("data-follow", strFollowOld);
			}
			if (menuNode)
			{
				menuNode.setAttribute("data-follow", strFollowOld);
			}

			if (
				!this.getFollowDefaultValue()
				&& followWrap
			)
			{
				if (strFollowOld == "Y")
				{
					BX.addClass(followWrap, 'post-item-follow');
				}
				else
				{
					BX.removeClass(followWrap, 'post-item-follow');
				}
			}

			if (BX.type.isNotEmptyString(this.detailPageId))
			{
				this.setFollowValue(strFollowOld == "Y");
				this.setFollowMenuItemName();
			}
		}.bind(this));
	}

	return false;
};

BitrixMSL.prototype.setFollowDefault = function(params)
{
	if (typeof params.value == 'undefined')
	{
		return;
	}

	var newValue = !!params.value;

	if (!BX.type.isNotEmptyString(oMSL.detailPageId))
	{
		this.setFollowDefaultValue(newValue);
		this.setDefaultFollowMenuItemName();
	}

	var post_data = {
		sessid: BX.bitrix_sessid(),
		site: BX.message('SITE_ID'),
		lang: BX.message('MSLLangId'),
		value: (newValue ? 'Y' : 'N'),
		action: 'change_follow_default',
		mobile_action: 'change_follow_default'
	};

	this.changeListMode(
		post_data,
		function(post_data) {
			oMSL.pullDownAndRefresh();
		},
		function(post_data) {
			oMSL.setFollowDefaultValue(post_data.value != 'Y');
			oMSL.setDefaultFollowMenuItemName();
		}
	);
};

BitrixMSL.prototype.setExpertMode = function(params)
{
	if (typeof params.value == 'undefined')
	{
		return;
	}

	var newValue = !!params.value;

	if (!BX.type.isNotEmptyString(oMSL.detailPageId))
	{
		oMSL.bExpertMode = newValue;
		this.setExpertModeMenuItemName();
	}

	var post_data = {
		sessid: BX.bitrix_sessid(),
		site: BX.message('SITE_ID'),
		lang: BX.message('MSLLangId'),
		value: (newValue ? 'Y' : 'N'),
		action: 'change_expert_mode',
		mobile_action: 'change_expert_mode'
	};

	this.changeListMode(
		post_data,
		function(post_data) {
			oMSL.pullDownAndRefresh();
		},
		function(post_data) {
			oMSL.bExpertMode = !(post_data.value == 'Y');
			oMSL.setExpertModeMenuItemName();
		}
	);
};

BitrixMSL.prototype.changeListMode = function(post_data, successCallbackFunc, failCallbackFunc)
{
	app.showPopupLoader({text: ""});

	var
		action = false,
		statAction = '';

	if (post_data.action == 'change_follow_default')
	{
		action = 'socialnetwork.api.livefeed.changeFollowDefault';
		statAction = (post_data.value == 'Y' ? 'setFollowType' : 'unsetFollowType');
	}
	else if (post_data.action == 'change_expert_mode')
	{
		action = 'socialnetwork.api.livefeed.changeExpertMode';
		statAction = (post_data.value == 'Y' ? 'setExpertMode' : 'unsetExpertMode');
	}

	if (!action)
	{
		return;
	}

	BX.Mobile.Ajax.runAction(action, {
		data: {
			value: post_data.value
		},
		analyticsLabel: {
			b24statAction: statAction,
			b24statContext: 'mobile'
		}
	}).then(function(response) {
		app.hidePopupLoader();
		if (response.data.success)
		{
			successCallbackFunc(post_data);
		}
		else
		{
			if (!BX.type.isNotEmptyString(this.detailPageId))
			{
				failCallbackFunc(post_data);
			}
		}
	}.bind(this), function(response) {
		app.hidePopupLoader();
		if (!BX.type.isNotEmptyString(this.detailPageId))
		{
			failCallbackFunc(post_data);
		}
	}.bind(this));
};


BitrixMSL.prototype.incrementCounters = function(logId)
{
	logId = parseInt(logId);
	if (logId > 0)
	{
		var old_value = 0;
		var val = 0;

		if (
			BX('informer_comments_' + logId)
			&& !BX('informer_comments_new_' + logId)
		)
		{
			old_value = (BX('informer_comments_' + logId).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + logId).innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_' + logId).innerHTML = val;
		}
		else if (BX('informer_comments_common'))
		{
			old_value = (BX('informer_comments_common').innerHTML.length > 0 ? parseInt(BX('informer_comments_common').innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_common').innerHTML = val;
		}

		if (BX('comcntleave-all'))
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
			BX('comcntleave-all').innerHTML = val;
		}

		BXMobileApp.onCustomEvent('onLogEntryCommentAdd', { log_id: logId }, true);
	}
};

BitrixMSL.prototype.getComment = function(params)
{
	var url = '';

	if (params.commentType == 'blog')
	{
		if (
			!!params.oPreviewComment
			&& typeof oMSL.entityXMLId != 'undefined'
			&& BX('entry-comment-' + oMSL.entityXMLId + '-' + params.commentId)
		)
		{
			BX.cleanNode(BX(params.oPreviewComment), true);
			return;
		}
		url = BX.message('SBPCurlToNew').replace(/#post_id#/, params.entryId).replace(/#comment_id#/, params.commentId)
	}

	if (url.length > 0)
	{
		BX.Mobile.Ajax.wrap({
			'type': 'json',
			'method': 'GET',
			'url': url,
			'data': '',
			'processData': true,
			'callback': function(comment_responce)
			{
				if (typeof comment_responce.TEXT != 'undefined')
				{
					oMSL.showNewComment({
						commentType: params.commentType,
						commentId: params.commentId,
						text: comment_responce.TEXT,
						bClearForm: false,
						oPreviewComment: params.oPreviewComment,
						bReplace: false,
						bIncrementCounters: true,
						bShowImages: false
					});
					oMSL.parseAndExecCode(comment_responce.TEXT);
					__MSLDetailMoveBottom();

					oMSL.setFollow({
						logId: oMSL.getLogId(),
						bOnlyOn: true
					});
				}
				else
				{
					oMSL.showCommentAlert({
						nodeId: params.oPreviewComment,
						commentType: params.commentType,
						callback: function() {
							oMSL.getComment(params);
						}
					});
				}
			},
			'callback_failure': function()
			{
				oMSL.showCommentAlert({
					nodeId: params.oPreviewComment,
					commentType: params.commentType,
					callback: function() {
						oMSL.getComment(params);
					}
				});
			}
		});
	}
};

BitrixMSL.prototype.setFocusOnComments = function(type)
{
	type = (type == 'list' ? 'list' : 'form');

	if (type == 'form')
	{
		this.setFocusOnCommentForm();
		__MSLDetailMoveBottom();
	}
	else if (type == 'list')
	{
		if (BX('post-comments-wrap'))
		{
			var firstNewComment = BX.findChild(BX('post-comments-wrap'), { className : 'post-comment-block-new' }, true);
			if (firstNewComment)
			{
				window.scrollTo(0, firstNewComment.offsetTop);
			}
			else
			{
				var firstComment = BX.findChild(BX('post-comments-wrap'), { className : 'post-comment-block' }, true);
				window.scrollTo(0, (firstComment ? firstComment.offsetTop : 0));
			}
		}
	}

	return false;
};

BitrixMSL.prototype.setFocusOnCommentForm = function()
{
	BXMobileApp.UI.Page.TextPanel.focus();

	return false;
};

BitrixMSL.prototype.buildDetailPageMenu = function(data)
{
	var menuNode = null;

	if (Application.getApiVersion() >= 34)
	{
		menuNode = document.getElementById('log-entry-menu-' + this.getLogId());
	}

	BX.MobileLivefeed.PostMenuInstance.init({
		logId: parseInt(this.getLogId()),
		postId: parseInt(data.post_id),
		postPerms: data.post_perm,
		useShare: (data.entry_type === 'blog'),
		useFavorites: (menuNode && menuNode.getAttribute('data-use-favorites') === 'Y'),
		useFollow: (oMSL.bUseFollow && (data.read_only !== 'Y')),
		usePinned: (parseInt(this.getLogId()) > 0),
		useRefreshComments: true,
		favoritesValue: (menuNode && menuNode.getAttribute('data-favorites') === 'Y'),
		followValue: (oMSL.getFollowValue()),
		pinnedValue: (menuNode && menuNode.getAttribute('data-pinned') === 'Y'),
		contentTypeId: data.post_content_type_id,
		contentId: parseInt(data.post_content_id),
		target: menuNode,
		context: 'detail'
	});

	return BX.MobileLivefeed.PostMenuInstance.getMenuItems().map(function(item) {
		item.name = item.title;
		item.image = item.iconUrl;

		delete item.title;
		delete item.iconUrl;

		return item;
	});
};

BitrixMSL.prototype.getPageMenuItems = function(type)
{
	type = (type == 'detail' ? 'detail' : 'list');
	return (type == 'detail' ? this.detailPageMenuItems : this.listPageMenuItems);
};

BitrixMSL.prototype.showPageMenu = function()
{
	if (this.pageType == 'detail')
	{
		this.detailPageMenuItems = this.buildDetailPageMenu(this.menuData);
	}

	var menuItems = this.getPageMenuItems(this.pageType);

	if (menuItems.length > 0)
	{
		var
			popupMenuItems = [],
			popupMenuActions = {};

		for(var i = 0; i < menuItems.length; i++)
		{
			popupMenuItems.push({
				id: menuItems[i].id,
				title: menuItems[i].name,
				iconUrl: (BX.type.isNotEmptyString(menuItems[i].image) ? menuItems[i].image : ''),
				iconName: (BX.type.isNotEmptyString(menuItems[i].iconName) ? menuItems[i].iconName : ''),
				sectionCode: 'defaultSection'
			});

			popupMenuActions[menuItems[i].id] = menuItems[i].action;
		}

		app.exec('setPopupMenuData', {
			items: popupMenuItems,
			sections: [
				{
					id: 'defaultSection'
				}
			],
			callback: function(event) {
				if (event.eventName == 'onDataSet')
				{
					app.exec("showPopupMenu");
				}
				else if (
					event.eventName == 'onItemSelected'
					&& BX.type.isNotEmptyObject(event.item)
					&& BX.type.isNotEmptyString(event.item.id)
					&& typeof popupMenuActions[event.item.id] != 'undefined'
				)
				{
					popupMenuActions[event.item.id]();
				}
			}
		});
	}
};

BitrixMSL.prototype.initPageMenu = function(type)
{
	type = (type == 'detail' ? 'detail' : 'list');
	var
		menuItems = this.getPageMenuItems(type),
		title = (
			type == 'detail'
				? (BX.message("MSLLogEntryTitle") != null ? BX.message("MSLLogEntryTitle") : '')
				: (BX.message("MSLLogTitle") != null ? BX.message("MSLLogTitle") : '')
		);

	if (menuItems.length > 0)
	{
		if (Application.getApiVersion() >= 34)
		{
			BXMobileApp.UI.Page.TopBar.title.params.largeMode = true;
			BXMobileApp.UI.Page.TopBar.title._applyParams();

			oMSL.initPagePopupMenu();
		}
		else
		{
			app.menuCreate({
				items: menuItems
			});
			BXMobileApp.UI.Page.TopBar.title.setCallback(function ()
			{
				app.menuShow();
			});
		}
	}
	else
	{
		if (Application.getApiVersion() >= 34)
		{

		}
		else
		{
			BXMobileApp.UI.Page.TopBar.title.setCallback("");
		}
	}

	BXMobileApp.UI.Page.TopBar.title.setText(title);
	BXMobileApp.UI.Page.TopBar.title.show();
};

BitrixMSL.prototype.showPostMenu = function(event)
{
	var target = null;

	if (
		BX.type.isString(event.target.getAttribute('data-menu-type'))
		&& event.target.getAttribute('data-menu-type') == 'post'
	)
	{
		target = event.target;
	}
	else
	{
		target = BX.findParent(event.target, { className: 'lenta-menu' }, { className: 'post-item-top' } );
	}

	if (!BX(target))
	{
		return event.preventDefault();
	}

	BX.MobileLivefeed.PostMenuInstance.init({
		logId: parseInt(target.getAttribute('data-log-id')),
		postId: parseInt(target.getAttribute('data-post-id')),
		postPerms: target.getAttribute('data-post-perm'),
		useFavorites: (target.getAttribute('data-use-favorites') === 'Y'),
		useFollow: (target.getAttribute('data-use-follow') === 'Y'),
		usePinned: (target.getAttribute('data-use-pinned') === 'Y'),
		favoritesValue: (target.getAttribute('data-favorites') === 'Y'),
		followValue: (target.getAttribute('data-follow') === 'Y'),
		pinnedValue: (target.getAttribute('data-pinned') === 'Y'),
		contentTypeId: target.getAttribute('data-content-type-id'),
		contentId: parseInt(target.getAttribute('data-content-id')),
		target: target,
		context: 'list'
	});

	var popupMenuItems = BX.MobileLivefeed.PostMenuInstance.getMenuItems();

	if (popupMenuItems.length <= 0)
	{
		return event.preventDefault();
	}

	var popupMenuActions = {};

	for(var i = 0; i < popupMenuItems.length; i++)
	{
		popupMenuActions[popupMenuItems[i].id] = popupMenuItems[i].action;
	}

	app.exec('setPopupMenuData', {
		position: 'center',
		items: popupMenuItems,
		sections: [
			{
				id: 'defaultSection'
			}
		],
		callback: function(event) {
			if (event.eventName == 'onDataSet')
			{
				app.exec("showPopupMenu");
			}
			else if (
				event.eventName == 'onItemSelected'
				&& BX.type.isNotEmptyObject(event.item)
				&& BX.type.isNotEmptyString(event.item.id)
				&& typeof popupMenuActions[event.item.id] != 'undefined'
			)
			{
				popupMenuActions[event.item.id]();
			}
		}
	});

	return event.preventDefault();
};

BitrixMSL.prototype.copyPostLink = function(params)
{
	var contentTypeId = (BX.type.isNotEmptyString(params.contentTypeId) ? params.contentTypeId: '');
	var contentId = (BX.type.isNumber(params.contentId) && params.contentId > 0 ? params.contentId : 0);

	if (
		!BX.type.isNotEmptyString(contentTypeId)
		|| contentId <= 0
	)
	{
		return;
	}

	BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.getRawEntryData', {
		data: {
			params: {
				entityType: contentTypeId,
				entityId: contentId,
				logId: this.getLogId(),
				additionalParams: {
					getLivefeedUrl: 'Y',
					absoluteUrl: 'Y',
					returnFields: [ 'LIVEFEED_URL' ]
				}
			}
		}
	}).then(function(response) {
		this.copyLinkSuccess(response.data);
	}.bind(this), function(response) {
	});
};


BitrixMSL.prototype.setFollowMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < this.detailPageMenuItems.length; i++)
	{
		menuItem = this.detailPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'follow'
		)
		{
			menuItem.name = (oMSL.getFollowValue() ? BX.message('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_Y') : BX.message('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_N'));
			this.detailPageMenuItems[i] = menuItem;
			this.initPageMenu('detail');
			break;
		}
	}
};

BitrixMSL.prototype.setDefaultFollowMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < this.listPageMenuItems.length; i++)
	{
		menuItem = this.listPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'follow'
		)
		{
			menuItem.name = (oMSL.getFollowDefaultValue() ? BX.message('MSLMenuItemFollowDefaultY') : BX.message('MSLMenuItemFollowDefaultN'));
			this.listPageMenuItems[i] = menuItem;
			this.initPageMenu('list');
			break;
		}
	}
};

BitrixMSL.prototype.setExpertModeMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < this.listPageMenuItems.length; i++)
	{
		menuItem = this.listPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'expert'
		)
		{
			menuItem.name = (oMSL.bExpertMode ? BX.message('MSLMenuItemExpertModeY') : BX.message('MSLMenuItemExpertModeN'));
			this.listPageMenuItems[i] = menuItem;
			this.initPageMenu('list');
			break;
		}
	}
};

BitrixMSL.prototype.replyToComment = function(userId, userName, event)
{
	userId = parseInt(userId);

	var currentText = (typeof this.commentTextCurrent != 'undefined' ? this.commentTextCurrent : '');
	this.arMention[userName] = '[USER=' + userId + ']' + userName + '[/USER]';

	currentText = currentText + ' ' + userName + ', ';
	if (typeof this.commentTextCurrent != 'undefined')
	{
		this.commentTextCurrent = currentText;
	}

	BXMobileApp.UI.Page.TextPanel.setText(currentText);
	BXMobileApp.UI.Page.TextPanel.focus();

	return (
		typeof (event) != 'undefined'
			? event.preventDefault()
			: false
	);
};

BitrixMSL.prototype.buildCommentRatingNode = function(arComment, anchor_id)
{
	var you_like_class = (
		arComment["EVENT"]["RATING_USER_VOTE_VALUE"] > 0
			? "post-comment-likes-liked"
			: "post-comment-likes"
	);

	var bCounterNeeded = (
		parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) > 1
		|| (
			parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) == 1
			&& arComment["EVENT"]["RATING_USER_VOTE_VALUE"] <= 0
		)
	);

	return BX.create('DIV', {
		props: {
			id: 'bx-ilike-button-' + arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
			className: you_like_class
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comment-likes-text'
				},
				html: (bCounterNeeded ? BX.message('MSLLike2') : BX.message('MSLLike'))
			}),
			BX.create('DIV', {
				props: {
					id: 'bx-ilike-count-' + arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
					className: 'post-comment-likes-counter'
				},
				style: {
					display: (bCounterNeeded ? 'inner-block' : 'none')
				},
				events: {
					click: function(e) {
						RatingLikeComments.List(arComment["EVENT"]["RATING_TYPE_ID"] + '-' + arComment["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id);
						e.preventDefault();
					}
				},
				html: '' + parseInt(arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) + ''
			})
		]
	});
};

BitrixMSL.prototype.buildCommentReplyNode = function(arComment)
{
	return BX.create('DIV', {
		props: {
			className: 'post-comment-reply'
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comment-reply-text'
				},
				events: {
					click: function(e) {
						oMSL.replyToComment(arComment["EVENT"]["USER_ID"], arComment["CREATED_BY"]["FORMATTED"], (e || window.event));
					}
				},
				html: BX.message('MSLReply')
			})
		]
	});
};

BitrixMSL.prototype.drawRatingFooter = function(ratingFooterText)
{
	if (BX('rating-footer-wrap'))
	{
		BX('rating-footer-wrap').innerHTML = ratingFooterText;
		this.parseAndExecCode(ratingFooterText, 0);
	}
};

BitrixMSL.prototype.parseMentions = function(text)
{
	var parsedText = text;

	if (typeof this.arMention != 'undefined')
	{
		for (var userName in this.arMention)
		{
			parsedText = parsedText.replace(new RegExp(userName, 'g'), this.arMention[userName]);
		}

		this.arMention = [];
		this.commentTextCurrent = '';
	}

	return parsedText;
};

BitrixMSL.prototype.unParseMentions = function(text)
{
	var unParsedText = text;

	unParsedText = unParsedText.replace(/\[USER\s*=\s*(\d+)\]((?:\s|\S)*?)\[\/USER\]/ig,
		function(str, id, userName)
		{
			oMSL.arMention[userName] = str;
			return userName;
		}
	);

	return unParsedText;
};

BitrixMSL.prototype.expandText = function(id)
{
	if (!document.body.classList.contains('post-card'))
	{
		return;
	}

	var checkBlock = (
		typeof id == 'undefined'
		|| id == null
		|| !BX('post_block_check_cont_' + id)
			? BX('post_block_check_cont')
			: BX('post_block_check_cont_' + id)
	);

	if (!checkBlock)
	{
		return;
	}

	if (BX.hasClass(checkBlock, "post-item-post-block"))
	{
		BX.addClass(checkBlock, this.classes.postItemBlockFull);
		BX.removeClass(checkBlock, 'post-item-post-block');
	}
	else if (BX.hasClass(checkBlock, "lenta-info-block-wrapp"))
	{
		BX.addClass(checkBlock, 'lenta-info-block-wrapp-full');
		BX.removeClass(checkBlock, 'lenta-info-block-wrapp');
	}

	var nodeMoreOverlay = checkBlock.querySelector('.post-more-block');
	if (nodeMoreOverlay)
	{
		nodeMoreOverlay.style.display = "none";
	}

	if (BX('post_more_limiter_' + id))
	{
		BX('post_more_limiter_' + id).style.visibility = "hidden";
	}
	else if (BX('post_more_limiter'))
	{
		BX('post_more_limiter').style.visibility = "hidden";
	}

	var arImages = BX.findChildren(checkBlock, { tagName: "img" }, true);

	if (BX.type.isArray(arImages))
	{
		for (var i = 0; i < arImages.length; i++)
		{
			if (
				BX.type.isString(arImages[i].getAttribute('data-src'))
				&& arImages[i].getAttribute('data-src').length > 0
				&& !!arImages[i].id
			)
			{
				BitrixMobile.LazyLoad.registerImage(arImages[i].id);
			}
		}
		BitrixMobile.LazyLoad.showImages(false);
	}
};

BitrixMSL.prototype.showEmptyCommentsBlockWaiter = function(el, enable)
{
	enable = !!enable;
	if (!BX(el))
	{
		return;
	}

	var waiterBlock = BX.findChild(BX(el), { className: 'post-comments-load-btn-wrap' }, true, false);
	if (waiterBlock)
	{
		BX.cleanNode(waiterBlock, true);
	}

	if (enable)
	{
		BX(el).appendChild(BX.create('DIV', {
			props: {
				className: 'post-comments-load-btn-wrap'
			},
			children: [
				BX.create('DIV', {
					props: {
						className: 'post-comments-loader'
					}
				}),
				BX.create('DIV', {
					props: {
						className: 'post-comments-load-text'
					},
					text: BX.message('MSLDetailCommentsLoading')
				})
			]
		}));

	}
};

BitrixMSL.prototype.showEmptyCommentsBlockFailed = function(el, ts, bPullDown, bMoveBottom, data)
{
	if (!BX(el))
	{
		return;
	}

	var errorMessage = (
		typeof data != 'undefined'
		&& typeof data.ERROR_MESSAGE != 'undefined'
		&& data.ERROR_MESSAGE.length > 0
			? data.ERROR_MESSAGE
			: BX.message('MSLDetailCommentsFailed')
	);

	BX(el).appendChild(BX.create('DIV', {
		props: {
			className: 'post-comments-load-btn-wrap'
		},
		children: [
			BX.create('DIV', {
				props: {
					className: 'post-comments-load-text'
				},
				text: errorMessage
			}),
			BX.create('A', {
				props: {
					className: 'post-comments-load-btn'
				},
				events: {
					click: function() {
						BX.cleanNode(this.parentNode, true);

						// repeat get comments request (after error shown)
						oMSL.getComments({
							ts: ts,
							bPullDown: bPullDown,
							obFocus: {
								form: false
							}
						});
					},
					touchstart: function()
					{
						this.classList.add('post-comments-load-btn-active');
					},
					touchend: function()
					{
						this.classList.remove('post-comments-load-btn-active');
					}
				},
				text: BX.message('MSLDetailCommentsReload')
			})
		]
	}));
};

BitrixMSL.prototype.afterEdit = function(postResponseData, logId) // in livefeed only
{
	logId = (typeof logId != 'undefined' ? parseInt(logId) : 0);

	var newPostNode = BX.create('DIV', {
		html: postResponseData.text
	});
	BX('blog-post-first-after').parentNode.insertBefore(newPostNode, BX('blog-post-first-after').nextSibling);

	var
		detailTextNode = BX.findChild(newPostNode, { className: 'post-item-post-block' }, true),
		topNode = BX.findChild(newPostNode, { className: 'post-item-top' }, true),
		filesNode = BX.findChild(newPostNode, { className: 'post-item-attached-file-wrap' }, true);

	if (
		logId > 0
		&& detailTextNode
		&& topNode
	)
	{
		var postData = {
			detailText: detailTextNode.innerHTML,
			topText: topNode.innerHTML,
			logID: logId
		};
		if (filesNode)
		{
			postData.filesBlockText = filesNode.innerHTML;
		}

		BXMobileApp.onCustomEvent('onEditedPostInserted', postData, true, true);
	}

	BitrixMobile.LazyLoad.showImages();
};

BitrixMSL.prototype.onLogEntryFavorites = function(log_id, page_id)
{
	var favoritesBlock = BX('log_entry_favorites_' + log_id);

	if (
		favoritesBlock
		&& (
			BX.message('MSLPageId') == undefined
			|| BX.message('MSLPageId') != page_id
		)
	)
	{
		var strFavoritesOld = (favoritesBlock.getAttribute("data-favorites") == "Y" ? "Y" : "N");
		var strFavoritesNew = (strFavoritesOld == "Y" ? "N" : "Y");

		if (strFavoritesOld == "Y")
		{
			BX.removeClass(favoritesBlock, 'lenta-item-fav-active');
		}
		else
		{
			BX.addClass(favoritesBlock, 'lenta-item-fav-active');
		}

		favoritesBlock.setAttribute("data-favorites", strFavoritesNew);
	}
};

BitrixMSL.prototype.onLogEntryCommentAdd = function(log_id, iValue) // for the feed
{
	var val, old_value;

	var valuePassed = (typeof iValue != 'undefined');
	if (typeof iValue == 'undefined')
	{
		iValue = 0;
	}

	if (
		BX('informer_comments_' + log_id)
		&& !BX('informer_comments_new_' + log_id)
	) // detail page
	{
		if (parseInt(iValue) > 0)
		{
			val = parseInt(iValue);
		}
		else if (!valuePassed)
		{
			old_value = (
				BX('informer_comments_' + log_id).innerHTML.length > 0
					? parseInt(BX('informer_comments_' + log_id).innerHTML)
					: 0
			);
			val = old_value + 1;
		}

		if (parseInt(val) > 0)
		{
			BX('informer_comments_' + log_id).innerHTML = val;
			BX('informer_comments_' + log_id).style.display = 'inline-block';
			BX('informer_comments_text2_' + log_id).style.display = 'inline-block';
			BX('informer_comments_text_' + log_id).style.display = 'none';
		}
	}

	if (BX('comcntleave-all')) // more comments
	{
		if (parseInt(iValue) > 0)
		{
			val = parseInt(iValue);
		}
		else if (!valuePassed)
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
		}
		BX('comcntleave-all').innerHTML = val;
	}
};

BitrixMSL.prototype.onLogEntryRatingLike = function(params)
{
	var rating_id = params.ratingId;

	if (typeof this.arRatingLikeProcess[rating_id] != 'undefined')
	{
		setTimeout(function() {
			oMSL.onLogEntryRatingLike(params)
		}, 100);
		return;
	}

	this.arRatingLikeProcess[rating_id] = true;

	var voteAction = params.voteAction;
	var logId = parseInt(params.logId);
	var userId = (typeof (params.userId) != 'undefined' ? parseInt(params.userId) : BX.message('USER_ID'));

	if (
		logId <= 0
		&& this.isUserCurrent(userId)
	) /* pull from the same author */
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	var ratingBox = BX('bx-ilike-box-' + rating_id);
	if (!ratingBox)
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	var ratingButton = BX('bx-ilike-button-' + rating_id);
	if (!ratingButton)
	{
		ratingButton = BX('rating_button');
	}

	if (
		!ratingButton
		|| (
			!BX.hasClass(ratingButton, 'post-item-inform-likes')
			&& !BX.hasClass(ratingButton, 'post-item-inform-likes-active')
		)
	)
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	var tmpNode = null;
	var old_value = 0;
	var val = 0;

	var ratingFooter = null;

	var ratingBlock = (
		logId > 0
		&& BX('rating_block_' + logId)
			? BX('rating_block_' + logId)
			: BX('rating_button_cont')
	);

	if (
		!ratingBlock
		&& ratingBox
	)
	{
		ratingBlock = BX.findParent(ratingBox, { 'tag': 'SPAN', 'className': 'bx-ilike-block' } );
	}

	if (ratingBox)
	{
		tmpNode = BX.findChild(ratingBox, {className: 'post-item-inform-right-text'}, true, false);
		if (
			tmpNode
			&& ratingBlock
		)
		{
			old_value = parseInt(ratingBlock.getAttribute('data-counter'));
			val = (voteAction == 'plus' ? (old_value + 1) : (old_value - 1));
			tmpNode.innerHTML = val;
		}

		if (this.isUserCurrent(userId))
		{
			BX.removeClass(ratingButton, (voteAction == 'plus' ? 'post-item-inform-likes' : 'post-item-inform-likes-active'));
			BX.addClass(ratingButton, (voteAction == 'plus' ? 'post-item-inform-likes-active' : 'post-item-inform-likes'));
		}

		var bFull = (
			(
				BX.hasClass(ratingButton, 'post-item-inform-likes-active')
				&& val > 1
			)
			|| (
				!BX.hasClass(ratingButton, 'post-item-inform-likes-active')
				&& val > 0
			)
		);

		tmpNode = BX.findChild(ratingButton, {className: 'post-item-inform-right'}, true, false);
		if (tmpNode)
		{
			tmpNode.innerHTML = val;
			tmpNode.style.display = (bFull ? 'inline-block' : 'none');

			tmpNode = BX.findChild(ratingButton, {className: 'post-item-inform-left'}, true, false);
			tmpNode.innerHTML = (bFull ? BX.message('MSLLike2') : BX.message('MSLLike'));
		}
	}

	if (ratingBlock)
	{
		ratingBlock.setAttribute('data-counter', parseInt(val));
	}

	if (
		logId > 0
		&& BX('rating-footer_' + logId)
	)
	{
		ratingFooter = BX('rating-footer-wrap_' + logId);
	}
	else if (ratingBlock)
	{
		ratingFooter = BX('rating-footer-wrap');
	}

	if (
		!ratingFooter
		&& ratingBlock
		&& typeof ratingBlock.id != 'undefined'
	)
	{
		var arMatch = ratingBlock.id.match(/^rating_block_([\d]+)$/i);
		if (arMatch != null)
		{
			ratingFooter = BX('rating-footer-wrap_' + arMatch[1]);
		}
	}

	if (
		this.isUserCurrent(userId)
		&& typeof BXRL != 'undefined'
		&& typeof BXRL[rating_id] != 'undefined'
	)
	{
		BXRL[rating_id].lastVote = (voteAction == 'plus' ? 'plus' : 'cancel');
	}

	delete this.arRatingLikeProcess[rating_id];
};

BitrixMSL.prototype.onLogCommentRatingLike = function(params)
{
	var rating_id = params.ratingId;

	if (typeof this.arRatingLikeProcess[rating_id] != 'undefined')
	{
		setTimeout(function() {
			oMSL.onLogCommentRatingLike(params)
		}, 100);
		return;
	}

	this.arRatingLikeProcess[rating_id] = true;

	var voteAction = params.voteAction;
	var userId = (typeof (params.userId) != 'undefined' ? parseInt(params.userId) : BX.message('USER_ID'));
	var counterNode = BX('bx-ilike-count-' + rating_id);

	if (!counterNode)
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	var oldValue = parseInt(counterNode.innerHTML);
	var val = (voteAction == 'plus' ? (oldValue + 1) : (oldValue - 1));

	if (this.isUserCurrent(userId))
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	var ratingButton = BX('bx-ilike-button-' + rating_id);
	if (
		!ratingButton
		|| (
			!BX.hasClass(ratingButton, 'post-comment-likes')
			&& !BX.hasClass(ratingButton, 'post-comment-likes-liked')
		)
	)
	{
		delete this.arRatingLikeProcess[rating_id];
		return;
	}

	if (counterNode)
	{
		var bFull = (
			(
				BX.hasClass(ratingButton, 'post-comment-likes-liked')
				&& val > 1
			)
			|| (
				!BX.hasClass(ratingButton, 'post-comment-likes-liked')
				&& val > 0
			)
		);

		counterNode.innerHTML = val;
		counterNode.style.display = (bFull ? 'inline-block' : 'none');

		var tmpNode = BX.findChild(ratingButton, {className: 'post-comment-likes-text'}, true, false);
		tmpNode.innerHTML = (bFull ? BX.message('MSLLike2') : BX.message('MSLLike'));
	}

	delete this.arRatingLikeProcess[rating_id];
};

BitrixMSL.prototype.checkVisibility = function(image)
{
	var img = image.node;

	if (BX.hasClass(document.body, "lenta-page"))
	{
		var isVisible = oMSL.checkImageOffset(img);
		if (isVisible === false)
		{
			image.status = BitrixMobile.LazyLoad.status.hidden;
		}
		return isVisible;
	}
	else if (
		!oMSL.isPostFull()
		&& oMSL.isImageFromPost(image)
	)
	{
		return oMSL.checkImageOffset(img);
	}

	return true;
};

BitrixMSL.prototype.checkImageOffset = function(img)
{
	if (!BX.hasClass(img.parentNode, "post-item-attached-img-block")) //Inline post image
	{
		return img.offsetTop < 315;
	}

	return true;
};

BitrixMSL.prototype.isPostFull = function()
{
	var checkBlock = (
		BX("post_block_check_cont_" + this.logId)
			? BX("post_block_check_cont_" + this.logId)
			: BX("post_block_check_cont", true)
	);

	return (
		BX.hasClass(checkBlock, this.classes.postItemBlockFull)
		|| BX.hasClass(checkBlock, "lenta-info-block-wrapp-full")
	);
};

BitrixMSL.prototype.isImageFromPost = function(image)
{
	if (typeof(image.fromPost) != "undefined")
	{
		return image.fromPost;
	}

	var maxParent = 5;
	var parent = image.node;

	while (parent = parent.parentNode)
	{
		if (BX.hasClass(parent, "post-item-post-block"))
		{
			image.fromPost = true;
			return true;
		}
		if (maxParent <= 0)
		{
			image.fromPost = false;
			return false;
		}

		maxParent--;
	}
};

BitrixMSL.prototype.isUserCurrent = function(userId)
{
	return (userId == BX.message('USER_ID'));
};

BitrixMSL.prototype.checkNodesHeight = function()
{
	var blockHeight = false;
	var nodeToCheckId = null;
	var nodeMoreOverlay = null;

	for (var logId in this.arBlockToCheck)
	{
		if (!this.arBlockToCheck.hasOwnProperty(logId))
		{
			continue
		}

		nodeToCheckId = this.arBlockToCheck[logId];
		nodeMoreOverlay = (
			BX(nodeToCheckId.lenta_item_id)
				? BX(nodeToCheckId.lenta_item_id).querySelector('.post-more-block')
				: false
		);

		if (
			nodeMoreOverlay
			&& BX(nodeToCheckId.text_block_id)
		)
		{
			blockHeight = BX(nodeToCheckId.text_block_id).offsetHeight;
			if (BX(nodeToCheckId.title_block_id))
			{
				blockHeight += BX(nodeToCheckId.title_block_id).offsetHeight;
			}
			if (BX(nodeToCheckId.files_block_id))
			{
				blockHeight += BX(nodeToCheckId.files_block_id).offsetHeight;
			}
			var importantNode = BX(nodeToCheckId.text_block_id).parentNode.querySelector('.post-item-important');
			if (importantNode)
			{
				blockHeight += importantNode.offsetHeight;
			}

			if (
				BX(nodeToCheckId.more_button_id)
				&& BX(nodeToCheckId.more_button_id).closest('[data-livefeed-pinned-panel]')
			)
			{
				BX(nodeToCheckId.more_button_id).style.visibility = "visible";
			}
			else if (
				blockHeight >= 320
				&& !BX.hasClass(nodeToCheckId.text_block_id, this.classes.postItemBlockFull)
			)
			{
				nodeMoreOverlay.style.display = "block";
				if (BX(nodeToCheckId.lenta_item_id))
				{
					BX.removeClass(BX(nodeToCheckId.lenta_item_id), "post-without-informers");
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.visibility = "visible";
				}
			}
			else
			{
				if (nodeMoreOverlay)
				{
					nodeMoreOverlay.style.display = "none";
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.visibility = "hidden";
				}
			}
		}
	}
};

BitrixMSL.prototype.initScroll = function(enable, process_waiter)
{
	enable = !!enable;
	process_waiter = !!process_waiter;

	if (enable)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
		BX.bind(window, 'scroll', __MSLOnFeedScroll);
	}
	else
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
	}

	if (
		process_waiter
		&& BX('next_post_more')
	)
	{
		BX('next_post_more').style.display = (enable ? "block" : "none");
	}
};

BitrixMSL.prototype.onScrollDetail = function()
{
	if (!oMSL.canCheckScrollButton)
	{
		return;
	}

	clearTimeout(oMSL.showScrollButtonTimeout);
	oMSL.showScrollButtonTimeout = setTimeout(function () {
		oMSL.iLastActivityDate = Math.round(new Date().getTime() / 1000);
		oMSL.checkScrollButton();
	}, 200);
};

BitrixMSL.prototype.checkScrollButton = function()
{
	oMSL.windowSize = BX.GetWindowSize();

	var
		scrollTop = window.scrollY, // document.body.scrollTop
		maxScroll = (oMSL.windowSize.scrollHeight - oMSL.windowSize.innerHeight - 100); // (this.keyboardShown ? 500 : 300)

	oMSL.showScrollButtonBottom = !(
		((oMSL.windowSize.scrollHeight - oMSL.windowSize.innerHeight) <= 0) // short page
		|| (
			scrollTop >= maxScroll // too much low
			&& (
				scrollTop > 0 // refresh patch
				|| maxScroll > 0
			)
		)
	);

	oMSL.showScrollButtonTop = (scrollTop > 200);

	oMSL.showHideScrollButton();
};

BitrixMSL.prototype.showHideScrollButton = function()
{
	var postScrollButtonBottom = BX('post-scroll-button-bottom');
	if (postScrollButtonBottom)
	{
		if (oMSL.showScrollButtonBottom)
		{
			if (!postScrollButtonBottom.classList.contains('post-comment-block-scroll-bottom-active'))
			{
				postScrollButtonBottom.classList.add('post-comment-block-scroll-bottom-active');
			}
		}
		else
		{
			if (postScrollButtonBottom.classList.contains('post-comment-block-scroll-bottom-active'))
			{
				postScrollButtonBottom.classList.remove('post-comment-block-scroll-bottom-active');
			}
		}
	}

	var postScrollButtonTop = BX('post-scroll-button-top');
	if (postScrollButtonTop)
	{
		if (oMSL.showScrollButtonTop)
		{
			if (!postScrollButtonTop.classList.contains('post-comment-block-scroll-top-active'))
			{
				postScrollButtonTop.classList.add('post-comment-block-scroll-top-active');
			}
		}
		else
		{
			if (postScrollButtonTop.classList.contains('post-comment-block-scroll-top-active'))
			{
				postScrollButtonTop.classList.remove('post-comment-block-scroll-top-active');
			}
		}
	}
};

BitrixMSL.prototype.scrollTo = function(type)
{
	if (type != 'top')
	{
		type = 'bottom';
	}

	oMSL.canCheckScrollButton = false;
	oMSL.showScrollButtonBottom = false;
	oMSL.showScrollButtonTop = false;

	oMSL.showHideScrollButton();

	var
		startValue = window.scrollY, // document.body.scrollTop
		finishValue = null;

	if (type == 'bottom')
	{
		finishValue = oMSL.windowSize.scrollHeight;
	}
	else
	{
		finishValue = 0;
	}

	BitrixAnimation.animate({
		duration : 500,
		start : { scroll : startValue },
		finish : { scroll : finishValue },
		transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
		step : function(state)
		{
			window.scrollTo(0, state.scroll);
		},
		complete : function() {
			oMSL.canCheckScrollButton = true;
			oMSL.checkScrollButton();
		}
	});
};

BitrixMSL.prototype.getComments = function(params)
{
	var ts = params.ts;
	var bPullDown = !!params.bPullDown;

	var bMoveBottom = (
		typeof params.obFocus.form == 'undefined'
		|| params.obFocus.form == "NO"
			? "NO"
			: "YES"
	);
	var bMoveCommentsTop = (
		typeof params.obFocus.comments == 'undefined'
		|| params.obFocus.comments == "NO"
			? "NO"
			: "YES"
	);
	var logID = this.logId;

	if (!bPullDown)
	{
		if (
			typeof params.bPullDownTop == 'undefined'
			|| params.bPullDownTop
		)
		{
			BXMobileApp.UI.Page.Refresh.start();
		}

		BX.cleanNode(BX('post-comments-wrap'));
		BX('post-comments-wrap').appendChild(BX.create('SPAN', {
			props: {
				id: 'post-comment-last-after'
			}
		}));
	}
	oMSL.showEmptyCommentsBlockWaiter(BX('post-comments-wrap'), true);

	var ratingEmojiSelectorPopup = document.querySelector('.feed-post-emoji-popup-container');
	if (ratingEmojiSelectorPopup)
	{
		ratingEmojiSelectorPopup.style.top = 0;
		ratingEmojiSelectorPopup.style.left = 0;
		ratingEmojiSelectorPopup.classList.remove('feed-post-emoji-popup-active');
		ratingEmojiSelectorPopup.classList.remove('feed-post-emoji-popup-active-final');
		ratingEmojiSelectorPopup.classList.remove('feed-post-emoji-popup-active-final-item');
		ratingEmojiSelectorPopup.classList.add('feed-post-emoji-popup-invisible-final');
		ratingEmojiSelectorPopup.classList.add('feed-post-emoji-popup-invisible-final-mobile');
	}

	oMSL.emptyCommentsXhr = BX.Mobile.Ajax.wrap({
		type: 'json',
		method: 'GET',
		url: BX.message('MSLPathToLogEntry').replace("#log_id#", logID) + "&empty_get_comments=Y" + (typeof ts != 'undefined' && ts != null ? "&LAST_LOG_TS=" + ts : ""),
		data: '',
		processData: true,
		callback: function(get_data)
		{
			var formWrap = BX('post-comments-form-wrap');
			var commentsWrap = BX('post-comments-wrap');

			if (bPullDown)
			{
				app.exec('pullDownLoadingStop');
			}
			else if(
				typeof params.bPullDownTop == 'undefined'
				|| params.bPullDownTop
			)
			{
				BXMobileApp.UI.Page.Refresh.stop();
			}

			oMSL.showEmptyCommentsBlockWaiter(commentsWrap, false);

			if (BX.type.isNotEmptyString(get_data.POST_PERM))
			{
				oMSL.menuData.post_perm = get_data.POST_PERM;
				oMSL.detailPageMenuItems = oMSL.buildDetailPageMenu(oMSL.menuData);
				oMSL.initPageMenu('detail');
			}

			if (BX.type.isNotEmptyString(get_data.TEXT))
			{
				if (bPullDown)
				{
					BX.cleanNode(commentsWrap);
					if (typeof get_data.POST_NUM_COMMENTS != 'undefined')
					{
						if (BX('informer_comments_common'))
						{
							BX('informer_comments_common').style.display = 'inline';
							BX('informer_comments_common').innerHTML = parseInt(get_data.POST_NUM_COMMENTS);
							if (BX('informer_comments_all'))
							{
								BX('informer_comments_all').style.display = 'none';
							}
							if (BX('informer_comments_new'))
							{
								BX('informer_comments_new').style.display = 'none';
							}
						}
						BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
							log_id: logID,
							num: parseInt(get_data.POST_NUM_COMMENTS)
						}, true);
					}
				}

				BX.clearNodeCache();
				__MSLRepositLog(logID, {POST_NUM_COMMENTS : get_data.POST_NUM_COMMENTS});
				var ob = BX.processHTML(get_data.TEXT, true);

				commentsWrap.innerHTML = ob.HTML;
				commentsWrap.appendChild(BX.create('SPAN', {
					props: {
						id: 'post-comment-last-after'
					}
				}));

				var cnt = 0,
				func = function()
				{
					cnt++;
					if (cnt < 100)
					{
						if (BX('post-comments-wrap').childNodes.length > 0)
						{
							BX.ajax.processScripts(ob.SCRIPT);
						}
						else
						{
							BX.defer(func, this)();
						}
					}
				};
				BX.defer(func, this)();

				BX.onCustomEvent(window, 'BX.UserContentView.onInitCall', [{
					mobile: true,
					ajaxUrl: BX.message('MSLSiteDir') + 'mobile/ajax.php',
					commentsContainerId: 'post-comments-wrap',
					commentsClassName: 'post-comment-wrap'
				}]);

				BX.onCustomEvent(window, 'BX.UserContentView.onClearCall', []);

				if (!bPullDown) // redraw form
				{
					if (formWrap)
					{
						formWrap.innerHTML = '';
					}

					__MSLDetailPullDownInit(true);

					if (bMoveBottom == "YES")
					{
						oMSL.setFocusOnComments('form');
					}
					else if (bMoveCommentsTop == "YES")
					{
						oMSL.setFocusOnComments('list');
					}
				}

				oMSL.iLastActivityDate = Math.round(new Date().getTime() / 1000);
				oMSL.checkScrollButton();

				if (
					typeof get_data.TS !== 'undefined'
					&& BX('post_log_id')
				)
				{
					BX('post_log_id').setAttribute('data-ts', get_data.TS);
				}
			}
			else
			{
				if (!bPullDown)
				{
					oMSL.showEmptyCommentsBlockWaiter(commentsWrap, false);
				}
//				oMSL.showEmptyCommentsBlockFailed(commentsWrap, ts, bPullDown, bMoveBottom, get_data);
				app.alert({
					title: BX.message('MOBILE_EXT_LIVEFEED_ALERT_ERROR_TITLE'),
					text: BX.message('MOBILE_EXT_LIVEFEED_ALERT_ERROR_POST_NOT_FOUND_TEXT'),
					button: BX.message('MOBILE_EXT_LIVEFEED_ALERT_ERROR_BUTTON'),
					callback: function() {
						BXMobileApp.onCustomEvent('Livefeed::onLogEntryDetailNotFound', {
							logId: logID
						}, true);
						BXMPage.close();
					}
				});
			}
		},
		callback_failure: function()
		{
			var commentsWrap = BX('post-comments-wrap');
			if (bPullDown)
			{
				app.exec('pullDownLoadingStop');
				bReload = false;
			}
			else
			{
				BXMobileApp.UI.Page.Refresh.stop();
			}
			oMSL.showEmptyCommentsBlockWaiter(commentsWrap, false);
			oMSL.showEmptyCommentsBlockFailed(commentsWrap, ts, bPullDown, bMoveBottom);
		}
	});
};

BitrixMSL.prototype.refreshPostDetail = function()
{
	BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.mobileGetDetail', {
		data: {
			logId: this.getLogId()
		}
	}).then(function(response) {
		if (
			BX.type.isPlainObject(response.data)
			&& BX.type.isNotEmptyString(response.data.html)
			&& BX('post_block_check_cont')
		)
		{
			BX.html(BX('post_block_check_cont'), response.data.html);
			this.registerEmptyBlockToCheck();
			setTimeout(function() {
				this.checkNodesHeight();
				BitrixMobile.LazyLoad.showImages(); // when refresh
			}.bind(this), 500);
		}
	}.bind(this), function(response) {
	});
};


BitrixMSL.prototype.formatTaskDescription = function(taskDescription, livefeedUrl, entityType, suffix)
{
	var result = taskDescription;
	suffix = (BX.type.isNotEmptyString(suffix) ? '_' + suffix : '');

	if (
		!!livefeedUrl
		&& !!entityType
		&& livefeedUrl.length > 0
	)
	{
		result += '<br><br>' + BX.message('SONET_EXT_COMMENTAUX_CREATE_TASK_' + entityType + suffix).replace(
			'#A_BEGIN#', '[URL=' + livefeedUrl + ']'
		).replace(
			'#A_END#', '[/URL]'
		);
	}

	return result;
};

BitrixMSL.prototype.createTask = function(params)
{
	app.showPopupLoader();

	BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.getRawEntryData', {
		data: {
			params: {
				entityType: params.entityType,
				entityId: params.entityId,
				logId: (BX.type.isNumber(params.logId) ? params.logId : null),
				additionalParams: {
					getSonetGroupAvailable: 'Y',
					getLivefeedUrl: 'Y',
					checkPermissions: {
						feature: 'tasks',
						operation: 'create_tasks'
					}
				}
			}
		}
	}).then(function(response) {

		var data = response.data;

		if (
			typeof data.TITLE != 'undefined'
			&& typeof data.DESCRIPTION != 'undefined'
			&& typeof data.DISK_OBJECTS != 'undefined'
			&& (
				BX.type.isNotEmptyString(data.TITLE)
				|| BX.type.isNotEmptyString(data.DESCRIPTION)
			)
			&& BX.type.isNotEmptyString(data.LIVEFEED_URL)
		)
		{
			var taskDescription = this.formatTaskDescription(data.DESCRIPTION, data.LIVEFEED_URL, params.entityType, (BX.type.isNotEmptyString(data.SUFFIX) ? data.SUFFIX : ''));
			var taskData = {
				TITLE: data.TITLE,
				DESCRIPTION: taskDescription,
				RESPONSIBLE_ID: BX.message('USER_ID'),
				CREATED_BY: BX.message('USER_ID'),
				UF_TASK_WEBDAV_FILES: data.DISK_OBJECTS
			};

			var sonetGroupId = [];
			if (typeof data.GROUPS_AVAILABLE != 'undefined')
			{
				for (var i in data.GROUPS_AVAILABLE)
				{
					if (data.GROUPS_AVAILABLE.hasOwnProperty(i))
					{
						sonetGroupId.push(data.GROUPS_AVAILABLE[i]);
					}
				}
			}

			if (sonetGroupId.length == 1)
			{
				taskData.GROUP_ID = parseInt(sonetGroupId[0]);
			}

			(new BX.Tasks.Util.Query({url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') + 'mobile/' : '/mobile/') + '?mobile_action=task_ajax'})).add(
				'task.add',
				{
					data: taskData
				},
				{},
				{
					onExecuted: BX.proxy(function(errors, data)
					{
						app.hidePopupLoader();

						if (
							typeof data != 'undefined'
							&& typeof data.RESULT != 'undefined'
							&& typeof data.RESULT.DATA != 'undefined'
							&& typeof data.RESULT.DATA.ID != 'undefined'
							&& parseInt(data.RESULT.DATA.ID) > 0
						)
						{
							this.createTaskSetContentSuccess(data.RESULT.DATA.ID);

							BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.createTaskComment', {
								data: {
									params: {
										postEntityType: (BX.type.isNotEmptyString(params.postEntityType) ? params.postEntityType : params.entityType),
										entityType: params.entityType,
										entityId: params.entityId,
										taskId: data.RESULT.DATA.ID,
										logId: (BX.type.isNumber(params.logId) ? params.logId : null)
									}
								}
							}).then(function(response) {
							}, function(response) {
							});
						}
						else
						{
							this.createTaskSetContentFailure(errors.getMessages());
						}
					}, this)
				}
			).execute();
		}
		else
		{
			app.hidePopupLoader();
			this.createTaskSetContentFailure([
				BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_ERROR_GET_DATA')
			]);
		}

	}.bind(this), function(response) {
		app.hidePopupLoader();
		this.createTaskSetContentFailure([
			BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_ERROR_GET_DATA')
		]);

	}.bind(this));
};

BitrixMSL.prototype.createTaskSetContentSuccess = function(taskId)
{
	app.confirm({
		title: BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_SUCCESS_TITLE'),
		text: BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_SUCCESS_DESCRIPTION'),
		buttons: [
			BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_SUCCESS_BUTTON_CANCEL'),
			BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_SUCCESS_BUTTON_OK')
		],
		callback: function (buttonId) {
			if (buttonId == 2)
			{
				app.loadPageBlank({
					url: BX.message('MOBILE_EXT_LIVEFEED_TASK_PATH').replace('#user_id#', BX.message('USER_ID')).replace('#task_id#', taskId),
					bx24ModernStyle: true
				});
			}
		}
	});
};

BitrixMSL.prototype.createTaskSetContentFailure = function(errors)
{
	app.alert({
		title: BX.message('MOBILE_EXT_LIVEFEED_CREATE_TASK_FAILURE_TITLE'),
		text: errors.join('. ')
	});
};

BitrixMSL.prototype.copyLink = function(params)
{
	var
		entityType = (BX.type.isNotEmptyString(params.entityType) ? params.entityType : ''),
		entityId = (parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0);

	if (
		!BX.type.isNotEmptyString(entityType)
		|| entityId <= 0

	)
	{
		return;
	}

	BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.getRawEntryData', {
		data: {
			params: {
				entityType: entityType,
				entityId: entityId,
				additionalParams: {
					getLivefeedUrl: 'Y',
					absoluteUrl: 'Y',
					returnFields: [ 'LIVEFEED_URL' ]
				}
			}
		}
	}).then(function(response) {
		this.copyLinkSuccess(response.data);
	}.bind(this), function(response) {
	});
};

BitrixMSL.prototype.copyLinkSuccess = function(data)
{
	if (!BX.type.isNotEmptyString(data.LIVEFEED_URL))
	{
		return;
	}

	app.exec("copyToClipboard", { text: data.LIVEFEED_URL });

	BX.MobileLivefeed.NotificationBarInstance.showInfo({
		id: 'copy',
		text: BX.message('MOBILE_EXT_LIVEFEED_POST_MENU_GET_LINK_SUCCESS'),
		groupId: 'clipboard',
		maxLines: 1
	});
};

BitrixMSL.prototype.setPostFormParams = function(params)
{
	if (typeof params == 'object')
	{
		for (var key in params)
		{
			if (
				[ 'selectedRecipients', 'messageText', 'messageFiles' ].indexOf(key) !== -1
				&& params.hasOwnProperty(key)
			)
			{
				oMSL.newPostFormParams[key] = params[key];
			}
		}
	}
};

BitrixMSL.prototype.setPostFormExtraData = function(params)
{
	if (typeof params == 'object')
	{
		for (var key in params)
		{
			if (
				(
					key == 'hiddenRecipients'
					|| key == 'logId'
					|| key == 'postId'
					|| key == 'postAuthorId'
					|| key == 'messageUFCode'
					|| key == 'commentId'
					|| key == 'commentType'
					|| key == 'nodeId'
					|| key == 'pinnedContext'
				)
				&& params.hasOwnProperty(key)
			)
			{
				oMSL.newPostFormExtraData[key] = params[key];
			}
		}
	}
};

BitrixMSL.prototype.setPostFormExtraDataArray = function(oExtraData)
{
	var ob = null;

	for (var prop in oExtraData)
	{
		if (oExtraData.hasOwnProperty(prop))
		{
			ob = {};
			ob[prop] = oExtraData[prop];
			this.setPostFormExtraData(ob);
		}
	}
};

BitrixMSL.prototype.getPostFormExtraData = function()
{
	return oMSL.newPostFormExtraData;
};

BitrixMSL.prototype.showNewPostForm = function(params)
{
	if (!BX.type.isNotEmptyObject(params))
	{
		params = {};
	}

	var entityType = (
		BX.type.isNotEmptyString(params.entityType)
			? params.entityType
			: 'post'
	);

	var extraData = this.getPostFormExtraData();

	var postFormParams = {
		attachButton : this.getPostFormAttachButton(),
		mentionButton: this.getPostFormMentionButton(),
		attachFileSettings: this.getPostFormAttachFileSettings(),
		extraData: (extraData ? extraData : {}),
		smileButton: {},
		supportLocalFilesInText: (entityType == 'post'),
		okButton: {
			callback: function(data)
			{
				if (data.text.length > 0)
				{
					var postData = oMSL.buildPostFormRequestStub({
						type: entityType,
						extraData: data.extraData,
						text: oMSL.parseMentions(data.text),
						pinnedContext: (
							typeof data.extraData.pinnedContext !== 'undefined'
							&&  data.extraData.pinnedContext === 'YES'
						)
					});

					var ufCode = data.extraData.messageUFCode;
					oMSL.buildPostFormFiles(
						postData,
						data.attachedFiles,
						{
							ufCode: ufCode
						}
					).then(function()
					{
						if (entityType == 'post')
						{
							oMSL.buildPostFormDestinations(
								postData,
								data.selectedRecipients,
								(
									typeof data.extraData != 'undefined'
									&& typeof data.extraData.hiddenRecipients != 'undefined'
										? data.extraData.hiddenRecipients
										: []
								),
								{}
							);

							if (postData.postVirtualId)
							{
								postData.ufCode = ufCode;
								postData.contentType = 'post';

								oMSL.initPostForm({
									groupId: (params.groupId ? params.groupId : null)
								});

								BXMobileApp.onCustomEvent('Livefeed.PublicationQueue::setItem', {
									key: postData.postVirtualId,
									pinnedContext: !!postData.pinnedContext,
									item: postData,
									pageId: BX.MobileLivefeed.Instance.getPageId(),
									groupId: (params.groupId ? params.groupId : null)
								}, true);
							}
						}
					}, function() {}) ;
				}
			},
			name: BX.message('MSLPostFormSend')
		},
		cancelButton: {
			callback: function ()
			{
				oMSL.initPostForm({
					groupId: (params.groupId ? params.groupId : null)
				});
			},
			name: BX.message('MSLPostFormCancel')
		}
	};

	if (typeof oMSL.newPostFormParams.messageText != 'undefined')
	{
		postFormParams.message = {
			text: oMSL.newPostFormParams.messageText
		};
	}

	if (typeof oMSL.newPostFormParams.messageFiles != 'undefined')
	{
		postFormParams.attachedFiles = oMSL.newPostFormParams.messageFiles;
	}

	if (entityType == 'post')
	{
		postFormParams.recipients = {
			dataSource: oMSL.getPostFormRecipientsDataSource()
		};

		if (typeof oMSL.newPostFormParams.selectedRecipients != 'undefined')
		{
			postFormParams.recipients.selectedRecipients = oMSL.newPostFormParams.selectedRecipients;
		}

		if (typeof oMSL.newPostFormParams.backgroundCode != 'undefined')
		{
			postFormParams.backgroundCode = oMSL.newPostFormParams.backgroundCode;
		}
	}

	return postFormParams;
};

BitrixMSL.prototype.findDestinationCallBack = function(element, index, array)
{
	return (element.id == this.value);
};

BitrixMSL.prototype.addPostFormDestination = function(selectedDestinations, params)
{
	if (
		typeof params == 'undefined'
		|| typeof params.type == 'undefined'
	)
	{
		return;
	}

	var searchRes = null;
	if (params.type == 'UA')
	{
		searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: 0 });
		if (!searchRes)
		{
			selectedDestinations.a_users.push({
				id: 0,
				name: BX.message('MSLPostDestUA'),
				bubble_background_color: "#A7F264",
				bubble_text_color: "#54901E"
			});
		}
	}
	else if (params.type == 'U')
	{
		searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, { value: params.id });
		if (!searchRes)
		{
			selectedDestinations.a_users.push({
				id: params.id,
				name: params.name,
				bubble_background_color: "#BCEDFC",
				bubble_text_color: "#1F6AB5"
			});
		}
	}
	else if (params.type == 'SG')
	{
		searchRes = selectedDestinations.b_groups.some(this.findDestinationCallBack, { value: params.id });
		if (!searchRes)
		{
			selectedDestinations.b_groups.push({
				id: params.id,
				name: params.name,
				bubble_background_color: "#FFD5D5",
				bubble_text_color: "#B54827"
			});
		}
	}
};

BitrixMSL.prototype.getPostFormAttachButton = function()
{
	var attachButtonItems = [];

	if (
		BX.message('MSLbDiskInstalled') == 'Y'
		|| BX.message('MSLbWebDavInstalled') == 'Y'
	)
	{
		var diskAttachParams = {
			id: "disk",
			name: BX.message('MSLPostFormDisk'),
			dataSource: {
				multiple: "NO",
				url: (
					BX.message('MSLbDiskInstalled') == 'Y'
						? BX.message('MSLSiteDir') + 'mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=' + BX.message('USER_ID')
						: BX.message('MSLSiteDir') + 'mobile/webdav/user/' + BX.message('USER_ID') + '/'
				)

			}
		};

		var tableSettings = {
			searchField: "YES",
			showtitle: "YES",
			modal: "YES",
			name: BX.message('MSLPostFormDiskTitle')
		};

		//FIXME temporary workaround
		if (platform == "ios")
		{
			diskAttachParams.dataSource.table_settings = tableSettings;
		}
		else
		{
			diskAttachParams.dataSource.TABLE_SETTINGS = tableSettings;
		}

		attachButtonItems.push(diskAttachParams);
	}

	attachButtonItems.push({
		id: "mediateka",
		name: BX.message('MSLPostFormPhotoGallery')
	});

	attachButtonItems.push({
		id: "camera",
		name: BX.message('MSLPostFormPhotoCamera')
	});

	return {
        items: attachButtonItems
	};
};

BitrixMSL.prototype.getPostFormMentionButton = function()
{
	return {
		dataSource: {
			return_full_mode: "YES",
			outsection: "NO",
			okname: BX.message('MSLPostFormTableOk'),
			cancelname: BX.message('MSLPostFormTableCancel'),
			multiple: "NO",
			alphabet_index: "YES",
			url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=get_user_list&use_name_format=Y'
		}
	};
};

BitrixMSL.prototype.getPostFormAttachFileSettings = function()
{
	return {
		resize: [
			40,
			1,
			1,
			1000,
			1000,
			0,
			2,
			false,
			true,
			false,
			null,
			0
		],
		saveToPhotoAlbum: true
	};
};

BitrixMSL.prototype.getPostFormRecipientsDataSource = function()
{
	return {
		return_full_mode: "YES",
		outsection: (BX.message('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') != 'Y' ? "YES" : "NO"),
		okname: BX.message('MSLPostFormTableOk'),
		cancelname: BX.message('MSLPostFormTableCancel'),
		multiple: "YES",
		alphabet_index: "YES",
		showtitle: "YES",
		user_all: "YES",
		url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=' + (BX.message('MSLIsExtranetSite') == 'Y' ? 'get_group_list' : 'get_usergroup_list') + '&feature=blog'
	};
};

BitrixMSL.prototype.getDiskUploadFolder = function()
{

};

BitrixMSL.prototype.buildPostFormFiles = function(postData, attachedFiles, params)
{
	var promise = new Promise(function(resolve, reject)
	{
		var ufCode = params.ufCode;

		postData.postVirtualId = parseInt(Math.random() * 100000);
		postData.tasksList = [];

		if (
			typeof attachedFiles != 'undefined'
			&& attachedFiles.length > 0
		)
		{
			var readedFileCount = 0;
			var fileTotal = attachedFiles.length;
			var fileCountIncrement = (function(){
				readedFileCount++;
				if(readedFileCount >= fileTotal)
				{
					this.postProgressingFiles(postData, attachedFiles, params);
					resolve();
				}
			}).bind(this);

			var
				uploadTasks = [],
				taskId = null,
				isNewFileOnDevice = null,
				isFileFromBitrix24Disk = null,
				mimeType = null;

			attachedFiles.forEach(function(fileData)
			{
				isFileFromBitrix24Disk = (
					typeof fileData.VALUE != 'undefined'  // Android
					|| (
						typeof fileData.id != 'undefined'
						&& parseInt(fileData.id) > 0
					) // disk object
					|| (
						BX.type.isNotEmptyObject(fileData.dataAttributes)
						&& typeof fileData.dataAttributes.VALUE != 'undefined'
					) // iOS and modern Android too
					|| (
						BX.type.isNotEmptyString(fileData.ufCode)
						&& fileData.ufCode == ufCode
					)
				);

				isNewFileOnDevice = (
					typeof fileData.url == 'undefined'
					|| typeof fileData.id != 'number'
				);

				if(
					fileData.url
					&& isNewFileOnDevice
					&& !isFileFromBitrix24Disk
				)
				{
					taskId = 'postTask_' + parseInt(Math.random() * 100000);
					mimeType = BX.MobileUtils.getFileMimeType(fileData.type);

					uploadTasks.push({
						taskId: taskId,
						type: fileData.type,
						mimeType: mimeType,
						folderId: parseInt(BX.message('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
//						chunk: parseInt(BX.message('MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE')),
						params: {
							postVirtualId: postData.postVirtualId,
							pinnedContext: !!postData.pinnedContext
						},
						name: (typeof BX.MobileUtils.getUploadFilename === 'function' ? BX.MobileUtils.getUploadFilename(fileData.name, fileData.type) : fileData.name),
						url: fileData.url,
						previewUrl: (fileData.previewUrl ? fileData.previewUrl : null),
						resize: BX.MobileUtils.getResizeOptions(fileData.type)
					});
					postData.tasksList.push(taskId);
				}
				else
				{
					if(isFileFromBitrix24Disk)
					{
						if (typeof postData[ufCode] == 'undefined')
						{
							postData[ufCode] = [];
						}

						if (typeof fileData.VALUE != 'undefined')
						{
							postData[ufCode].push(fileData.VALUE);
						}
						else if (parseInt(fileData.id) > 0)
						{
							postData[ufCode].push(parseInt(fileData.id));
						}
						else
						{
							postData[ufCode].push(fileData.dataAttributes.VALUE);
						}
					}

					fileCountIncrement();
				}
			}.bind(this));

			if (uploadTasks.length > 0)
			{
				BXMobileApp.onCustomEvent('onFileUploadTaskReceived', {
					files: uploadTasks
				}, true);
			}
			resolve();
		}
		else
		{
			this.postProgressingFiles(postData, attachedFiles, params);
			resolve();
		}
	}.bind(this));

	promise.catch(function(error){console.error(error)});

	return promise;
};

BitrixMSL.prototype.postProgressingFiles = function(postData, attachedFiles, params)
{
	var ufCode = params.ufCode;
	if (typeof postData[ufCode] == 'undefined')
	{
		postData[ufCode] = [];
	}

	if (typeof attachedFiles == 'undefined')
	{
		attachedFiles = [];
	}

	for (var keyOld in oMSL.newPostFormParams.messageFiles) /* existing */
	{
		if (!oMSL.newPostFormParams.messageFiles.hasOwnProperty(keyOld))
		{
			continue;
		}

		for (var keyNew in attachedFiles)
		{
			if (!attachedFiles.hasOwnProperty(keyNew))
			{
				continue;
			}

			if (
				oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["id"]
				|| oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["ID"]
			)
			{
				postData[ufCode].push(oMSL.newPostFormParams.messageFiles[keyOld]["id"]);
				break;
			}
		}
	}

	if (postData[ufCode].length <= 0)
	{
		postData[ufCode].push('empty');
	}
};

BitrixMSL.prototype.buildPostFormDestinations = function(postData, selectedRecipients, hiddenRecipients, params)
{
	var prefix = null,
		id = null,
		name = null,
		key = null,
		value = null;

	postData['DEST'] = [];

	if (typeof selectedRecipients.a_users != 'undefined')
	{
		for (key in selectedRecipients.a_users)
		{
			if (selectedRecipients.a_users.hasOwnProperty(key))
			{
				prefix = 'U';
				if (typeof postData.SPERM[prefix] == 'undefined')
				{
					postData.SPERM[prefix] = [];
				}

				if (typeof postData.SPERM_NAME[prefix] == 'undefined')
				{
					postData.SPERM_NAME[prefix] = [];
				}

				id = (
					typeof selectedRecipients.a_users[key].ID != 'undefined'
						? selectedRecipients.a_users[key].ID
						: selectedRecipients.a_users[key].id
				);

				name = (
					typeof selectedRecipients.a_users[key].NAME != 'undefined'
						? selectedRecipients.a_users[key].NAME
						: selectedRecipients.a_users[key].name
				);

				value = (
					id == 0
						? 'UA'
						: 'U' + id
				);

				postData.SPERM[prefix].push(value);
				postData.DEST.push(value);
				postData.SPERM_NAME[prefix].push(name);
			}
		}
	}

	if (typeof selectedRecipients.b_groups != 'undefined')
	{
		for (key in selectedRecipients.b_groups)
		{
			if (!selectedRecipients.b_groups.hasOwnProperty(key))
			{
				continue;
			}

			prefix = 'SG';
			if (typeof postData.SPERM[prefix] == 'undefined')
			{
				postData.SPERM[prefix] = [];
			}

			if (typeof postData.SPERM_NAME[prefix] == 'undefined')
			{
				postData.SPERM_NAME[prefix] = [];
			}

			id = (
				typeof selectedRecipients.b_groups[key].ID != 'undefined'
					? selectedRecipients.b_groups[key].ID
					: selectedRecipients.b_groups[key].id
			);

			name = (
				typeof selectedRecipients.b_groups[key].NAME != 'undefined'
					? selectedRecipients.b_groups[key].NAME
					: selectedRecipients.b_groups[key].name
			);

			value = 'SG' + id;

			postData.SPERM[prefix].push(value);
			postData.DEST.push(value);
			postData.SPERM_NAME[prefix].push(name);
		}
	}

	for (key in hiddenRecipients)
	{
		if (!hiddenRecipients.hasOwnProperty(key))
		{
			continue;
		}

		prefix = hiddenRecipients[key]['TYPE'];
		if (typeof postData.SPERM[prefix] == 'undefined')
		{
			postData.SPERM[prefix] = [];
		}

		value = (hiddenRecipients[key]['TYPE'] + hiddenRecipients[key]['ID']);

		postData.SPERM[prefix].push(value);
		postData.DEST.push(value);
	}
};

BitrixMSL.prototype.buildSelectedDestinations = function(postData, selectedDestinations)
{
	if (
		typeof (postData.SPERM) == 'undefined'
		|| typeof (postData.SPERM_NAME) == 'undefined'
	)
	{
		return;
	}

	var arMatch = key = null;

	if (typeof (postData.SPERM.U) != 'undefined')
	{
		for (key in postData.SPERM.U)
		{
			if (!postData.SPERM.U.hasOwnProperty(key))
			{
				continue
			}

			if (postData.SPERM.U[key] == 'UA')
			{
				this.addPostFormDestination(
					selectedDestinations,
					{
						type: 'UA'
					}
				);
			}
			else
			{
				arMatch = postData.SPERM.U[key].match(/^U([\d]+)$/);
				if (arMatch != null)
				{
					this.addPostFormDestination(
						selectedDestinations,
						{
							type: 'U',
							id: arMatch[1],
							name: postData.SPERM_NAME.U[key]

						}
					);
				}
			}
		}
	}

	if (typeof (postData.SPERM.SG) != 'undefined')
	{
		for (key in postData.SPERM.SG)
		{
			if (!postData.SPERM.SG.hasOwnProperty(key))
			{
				continue;
			}

			arMatch = postData.SPERM.SG[key].match(/^SG([\d]+)$/);
			if (arMatch != null)
			{
				this.addPostFormDestination(
					selectedDestinations,
					{
						type: 'SG',
						id: arMatch[1],
						name: postData.SPERM_NAME.SG[key]
					}
				);
			}
		}
	}
};

BitrixMSL.prototype.buildPostFormRequestStub = function(params)
{
	var oRequest = null;

	if (params.type == 'post')
	{
		oRequest = {
			ACTION: 'ADD_POST',
			AJAX_CALL: 'Y',
			PUBLISH_STATUS: 'P',
			is_sent: 'Y',
			apply: 'Y',
			sessid: BX.bitrix_sessid(),
			POST_MESSAGE: params.text,
			decode: 'Y',
			SPERM: {},
			SPERM_NAME: {},
			MOBILE: 'Y',
			PARSE_PREVIEW: 'Y'
		};

		if (
			typeof params.extraData.postId != 'undefined'
			&& parseInt(params.extraData.postId) > 0
		)
		{
			oRequest.post_id = parseInt(params.extraData.postId);
			oRequest.post_user_id = parseInt(params.extraData.postAuthorId);
			oRequest.pinnedContext = !!params.pinnedContext;

			oRequest.ACTION = 'EDIT_POST';

			if (
				typeof params.extraData.logId != 'undefined'
				&& parseInt(params.extraData.logId) > 0
			)
			{
				oRequest.log_id = parseInt(params.extraData.logId);
			}
		}
	}
	else if (
		params.type == 'comment'
		&& typeof params.extraData.commentId != 'undefined'
		&& parseInt(params.extraData.commentId) > 0
		&& typeof params.extraData.commentType != 'undefined'
		&& params.extraData.commentType.length > 0
	)
	{
		oRequest = {
			action: 'EDIT_COMMENT',
			text: oMSL.parseMentions(params.text),
			commentId: parseInt(params.extraData.commentId),
			nodeId: params.extraData.nodeId,
			sessid: BX.bitrix_sessid()
		};

		if (params.extraData.commentType == 'blog')
		{
			oRequest.comment_post_id = commentVarBlogPostID;
		}
		else
		{
		}
	}

	return oRequest;
};

BitrixMSL.prototype.initPostForm = function(params)
{
	if (!BX.type.isNotEmptyObject(params))
	{
		params = {};
	}

	var
		groupId = (params.groupId ? params.groupId : false),
		callback = (params.callback ? params.callback : function() {}),
		selectedDestinations = {
			a_users: [],
			b_groups: []
		};

	oMSL.clearPostFormDestination(selectedDestinations, groupId); // to work before DBLoad

	oMSL.setPostFormExtraDataArray({
		postId: false,
		postAuthorId: false,
		logId: false,
		messageUFCode: BX.message('MOBILE_EXT_LIVEFEED_POST_FILE_UF_CODE')
	});

	oMSL.setPostFormParams({
		selectedRecipients: selectedDestinations,
		messageText: '',
		messageFiles: []
	});

	setTimeout(function() {
		BX.MobileLivefeed.DatabaseUnsentPostInstance.load(
			{
				onLoad: function (obResult)
				{
					var i = null;

					selectedDestinations = {
						a_users: [],
						b_groups: []
					};

					if (
						typeof obResult.SPERM != 'undefined'
						&& typeof obResult.SPERM.U != 'undefined'
						&& obResult.SPERM.U != null
					)
					{
						for (i = 0; i < obResult.SPERM.U.length; i++)
						{
							if (obResult.SPERM.U[i] == 'UA')
							{
								if (BX.message('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') != 'Y')
								{
									oMSL.addPostFormDestination(
										selectedDestinations,
										{
											type: 'UA'
										}
									);
								}
							}
							else
							{
								oMSL.addPostFormDestination(
									selectedDestinations,
									{
										type: 'U',
										id: obResult.SPERM.U[i].replace('U', ''),
										name: (
											typeof obResult.SPERM_NAME != 'undefined'
											&& typeof obResult.SPERM_NAME.U != 'undefined'
											&& typeof obResult.SPERM_NAME.U[i] != 'undefined'
											&& obResult.SPERM_NAME.U[i] != null
												? obResult.SPERM_NAME.U[i]
												: ''
										)
									}
								);
							}
						}
					}

					if (
						typeof obResult.SPERM != 'undefined'
						&& typeof obResult.SPERM.SG != 'undefined'
						&& obResult.SPERM.SG != null
					)
					{
						for (i = 0; i < obResult.SPERM.SG.length; i++)
						{
							oMSL.addPostFormDestination(
								selectedDestinations,
								{
									type: 'SG',
									id:  obResult.SPERM.SG[i].replace('SG', ''),
									name: (
										typeof obResult.SPERM_NAME.SG[i] != 'undefined'
										&& typeof obResult.SPERM_NAME.SG != 'undefined'
										&& typeof obResult.SPERM_NAME.SG[i] != 'undefined'
										&& obResult.SPERM_NAME.SG[i] != null
											? obResult.SPERM_NAME.SG[i]
											: ''
									)
								}
							);
						}
					}

					oMSL.setPostFormParams({
						selectedRecipients: selectedDestinations
					});

					if (
						typeof obResult.POST_MESSAGE != 'undefined'
						&& obResult.POST_MESSAGE != null
					)
					{
						oMSL.setPostFormParams({
							messageText: oMSL.unParseMentions(obResult.POST_MESSAGE)
						});
					}

					callback();
				},
				onEmpty: function (obResult)
				{
					selectedDestinations = {
						a_users: [],
						b_groups: []
					};
					oMSL.clearPostFormDestination(selectedDestinations, groupId);
					callback();
				}
			},
			groupId
		);
	}, 0); // instead of on ready
};

BitrixMSL.prototype.clearPostFormDestination = function(selectedDestinations, groupID)
{
	if (
		typeof groupID != 'undefined'
		&& parseInt(groupID) > 0
	)
	{
		oMSL.addPostFormDestination(
			selectedDestinations,
			{
				type: 'SG',
				id:  parseInt(groupID),
				name: BX.message('MSLGroupName')
			}
		);
	}
	else if (window.arAvailableGroup !== false)
	{
		for (key in window.arAvailableGroup)
		{
			if (!window.arAvailableGroup.hasOwnProperty(key))
			{
				continue;
			}

			oMSL.addPostFormDestination(
				selectedDestinations,
				{
					type: 'SG',
					id:  parseInt(window.arAvailableGroup[key]['entityId']),
					name: window.arAvailableGroup[key]['name']
				}
			);
		}
	}
	else if (BX.message('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DEFAULT') == 'Y')
	{
		oMSL.addPostFormDestination(
			selectedDestinations,
			{
				type: 'UA'
			}
		);
	}
};

BitrixMSL.prototype.processAjaxBlock = function(block, params)
{
	if (
		!params
		|| typeof params.type == 'undefined'
		|| !BX.util.in_array(params.type, ['refresh', 'next'])
	)
	{
		return;
	}

	var htmlWasInserted = false;
	var scriptsLoaded = false;

	processCSS(insertHTML);
	processExternalJS(processInlineJS);

	function processCSS(callback)
	{
		if (
			BX.type.isArray(block.CSS)
			&& block.CSS.length > 0
		)
		{
			BX.load(block.CSS, callback);
		}
		else
		{
			callback();
		}
	}

	function insertHTML()
	{
		if (params.type == 'refresh')
		{
			BX('lenta_wrapper_global').innerHTML = block.CONTENT;
		}
		else // next
		{
			BX('lenta_wrapper').insertBefore(BX.create('DIV', {
				html: block.CONTENT
			}), BX('next_post_more'));
		}

		htmlWasInserted = true;
		if (scriptsLoaded)
		{
			processInlineJS();
		}
	}

	function processExternalJS(callback)
	{
		if (
			BX.type.isArray(block.JS)
			&& block.JS.length > 0
		)
		{
			BX.load(block.JS, callback); // to initialize
		}
		else
		{
			callback();
		}
	}

	function processInlineJS()
	{
		scriptsLoaded = true;
		if (htmlWasInserted)
		{
			BX.ajax.processRequestData(block.CONTENT, {
				scriptsRunFirst: false,
				dataType: "HTML",
				onsuccess: function() {
					if (typeof params.callback == 'function')
					{
						params.callback();
					}
				}
			});
		}
	}
};

BitrixMSL.prototype.onUpdateSocnetCountersHandler = function(params)
{
	oMSL.bCounterReceived = true;
	oMSL.changeCounter(parseInt(params[strCounterType]));
};

BitrixMSL.prototype.onUpdateCounterHandler = function(params)
{
	if (typeof params[BX.message('SITE_ID')] != 'undefined')
	{
		oMSL.onImUpdateCounterHandler(params[BX.message('SITE_ID')]);
	}
};

BitrixMSL.prototype.onImUpdateCounterHandler = function(params)
{
	oMSL.bCounterReceived = true;

	if (typeof params.obZeroDate != 'undefined')
	{
		var obZeroCounter = params.obZeroDate;
		delete params.obZeroDate;
	}

	oMSL.changeCounter(
		parseInt(params[strCounterType]),
		(
			typeof obZeroCounter != 'undefined'
			&& obZeroCounter[strCounterType] != 'undefined'
				? parseInt(obZeroCounter[strCounterType])
				: null
		)
	);
};

BitrixMSL.prototype.registerBlocksToCheck = function()
{
	var lentaItemsList = null;
	var lentaItemId = null;
	var match = null;

	lentaItemsList = document.querySelectorAll('.post-wrap');
	if (lentaItemsList.length <= 0)
	{
		lentaItemsList = document.querySelectorAll('.lenta-item');
	}

	if (lentaItemsList != null)
	{
		for (var i = 0; i < lentaItemsList.length; i++)
		{
			if (BX.type.isNotEmptyString(lentaItemsList[i].id))
			{
				match = lentaItemsList[i].id.match(/^lenta_item_([\d]+)$/i);
				if (match != null)
				{
					lentaItemId = match[1];
					if (!BX.util.in_array(lentaItemId, this.blocksToCheckRegisteredList))
					{
						this.arBlockToCheck[lentaItemId] = {
							lenta_item_id: 'lenta_item_' + lentaItemId,
							text_block_id: 'post_block_check_' + lentaItemId,
							title_block_id: 'post_block_check_title_' + lentaItemId,
							files_block_id: 'post_block_check_files_' + lentaItemId,
							more_button_id: 'post_more_limiter_' + lentaItemId
						};
						this.blocksToCheckRegisteredList.push(lentaItemId);
					}
				}
			}
		}
	}
};

BitrixMSL.prototype.registerEmptyBlockToCheck = function()
{
	oMSL.arBlockToCheck = {
		0: {
			lenta_item_id: 'lenta_item',
			text_block_id: 'post_block_check_cont',
			title_block_id: 'post_block_check_title',
			more_button_id: 'post_more_limiter'
		}
	};
};

BitrixMSL.prototype.getCopyText = function(block)
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
};

BitrixMSL.prototype.initPagePopupMenu = function()
{
	if (Application.getApiVersion() >= 34)
	{
		var buttons = [];

		if(!oMSL.logId)
		{
			buttons.push({
				type: 'search',
				callback: function ()
				{
					app.exec("showSearchBar");
				}
			});
		}

		var menuItems = this.getPageMenuItems(this.pageType);

		if (
			BX.type.isArray(menuItems)
			&& menuItems.length > 0
		)
		{
			buttons.push({
				type: 'more',
				callback: function ()
				{
					this.showPageMenu(this.pageType);
				}.bind(this),
			});
		}

		app.exec("setRightButtons", {
			items: buttons
		});
	}
};

BitrixMSL.prototype.initSearchBar = function()
{
	if (Application.getApiVersion() >= 34)
	{
		BX.addCustomEvent(window, "BX.MobileLF:onSearchBarTextChanged", BX.debounce(this.searchBarEventCallback, 1500, this));
		BX.addCustomEvent(window, "BX.MobileLF:onSearchBarCancelButtonClicked", this.searchBarEventCallback.bind(this));
		BX.addCustomEvent(window, "BX.MobileLF:onSearchBarSearchButtonClicked", this.searchBarEventCallback.bind(this));

		BXMobileApp.UI.Page.params.set({
			useSearchBar: true
		});

		app.exec('setParamsSearchBar', {
			params: {
				callback: function(event) {
					if (
						BX.util.in_array(event.eventName, [ /* 'onUserTypeText',*/ 'onSearchButtonClicked' ])
						&& BX.type.isPlainObject(event.data)
						&& BX.type.isString(event.data.text)
					)
					{
						if (event.data.text.length >= this.ftMinTokenSize)
						{
							this.findTextMode = true;
						}

						var eventName = null;
						switch (event.eventName)
						{
/*
						case 'onUserTypeText':
							eventName = 'BX.MobileLF:onSearchBarTextChanged';
							break;
 */
							case 'onSearchButtonClicked':
								eventName = 'BX.MobileLF:onSearchBarSearchButtonClicked';
								break;
							default:
						}
						if (eventName)
						{
							BX.onCustomEvent(window, eventName, [{
								text: event.data.text
							}]);
						}
					}
					else if (BX.util.in_array(event.eventName, [ 'onCancelButtonClicked', 'onSearchHide' ]))
					{
						BX.onCustomEvent(window, 'BX.MobileLF:onSearchBarCancelButtonClicked', []);
					}
				}.bind(this)
			}
		});
	}
};

BitrixMSL.prototype.searchBarEventCallback = function(params)
{
	var text = (BX.type.isPlainObject(params) && BX.type.isNotEmptyString(params.text) ? params.text : '');

	if (text.length >= this.ftMinTokenSize)
	{
		app.exec("showSearchBarProgress");
		__MSLRefresh(true, {
			find: text
		});
	}
	else if (this.findTextMode)
	{
		if (this.xhr.refresh != null)
		{
			this.xhr.refresh.abort();
		}
		app.exec("hideSearchBarProgress");

		BX.frameCache.readCacheWithID('framecache-block-feed', function(params) {
			if (
				!BX.type.isArray(params.items)
				|| !BX('bxdynamic_feed_refresh')
			)
			{
				return;
			}

			for (var key = 0; key < params.items.length; key++)
			{
				if (
					BX.type.isNotEmptyString(params.items[key].ID)
					&& params.items[key].ID == 'framecache-block-feed'
				)
				{
					BX.html(BX('bxdynamic_feed_refresh'), params.items[key].CONTENT).then(function() {
						BX.processHTML(this.content, true);
					}.bind({
						content: params.items[key].CONTENT
					}));

					__MSLDetailMoveTop();
					setTimeout(function(){
						BitrixMobile.LazyLoad.showImages();
					}, 1000);
					break;
				}
			}
		}.bind(this));
	}

	if (text.length < this.ftMinTokenSize)
	{
		this.findTextMode = false;
	}
};

if (!window.oMSL)
{
	oMSL = new BitrixMSL;
	window.oMSL = oMSL;
}

function openTaskComponentByTaskId(e, taskId, data) {
	data = data || {};
	data.selectedTab = data.selectedTab || 'taskTab';

	BXMobileApp.Events.postToComponent("taskbackground::task::action", [{taskId:taskId, data:data}], "background");

	e.preventDefault();
	e.stopPropagation();
	return false;
}