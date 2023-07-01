function __MSLOnFeedPreInit(params) // only for the list
{
}

function __MSLOnFeedInit(params)
{
	logID = parseInt(params.logID);
	bAjaxCall = !!params.bAjaxCall;
	bReload = !!params.bReload;
	bEmptyPage = !!params.bEmptyPage;
	bFiltered = !!params.bFiltered;
	groupID = parseInt(params.groupID);
	groupImage = params.groupImage;
	tmstmp = parseInt(params.tmstmp);
	strCounterType = params.strCounterType;
	var canAddPost = (!BX.type.isUndefined(params.canAddPost) ? !!params.canAddPost : true);

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
			BX.addCustomEvent('BX.MobileLF:onSearchBarRefreshStart', function(event) {
				BX.MobileLivefeed.PageInstance.refresh(true, {
					find: event.text,
				});
			});
			BX.addCustomEvent('BX.MobileLF:onSearchBarRefreshAbort', function() {
				if (!BX.type.isNull(BX.MobileLivefeed.PageInstance.refreshXhr))
				{
					BX.MobileLivefeed.PageInstance.refreshXhr.abort();
				}
			});

			BX.MobileLivefeed.SearchBarInstance.init({
				ftMinTokenSize: oMSL.ftMinTokenSize,
			});
		}

		BX.MobileLivefeed.PageMenuInstance.listPageMenuItems = [];

		if (groupID > 0)
		{
			BX.ready(function()
			{
				if (canAddPost)
				{
					BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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
									postId: 0,
								});
							}
							else
							{
								oMSL.initPostForm({
									groupId: groupID,
									callback: function() {
										app.exec('showPostForm', BX.MobileLivefeed.PostFormOldManagerInstance.show({
											groupId: groupID,
										}));
									}
								});
							}
						},
						arrowFlag: false
					});
				}

				if (Application.getApiVersion() < BX.MobileLivefeed.Instance.getApiVersion('tabs'))
				{
					BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
						id: 'groupTasks',
						name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_GROUP_TASKS'),
						image: '/bitrix/templates/mobile_app/images/lenta/menu/n_check.png',
						icon: 'checkbox',
						arrowFlag: true,
						action: function() {

							if (Application.getApiVersion() >= 31)
							{
								BXMobileApp.Events.postToComponent(
									'taskbackground::taskList::open',
									[{
										groupId: groupID,
										groupName: BX.message('MSLLogTitle'),
										groupImageUrl: groupImage,
										ownerId: BX.message('USER_ID')
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

					BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
						id: 'groupFiles',
						name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_GROUP_FILES'),
						image: '/bitrix/templates/mobile_app/images/lenta/menu/files.png',
						action: function(){
							app.openBXTable({
								url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/?mobile_action=disk_folder_list&type=group&path=/&entityId=' + groupID,
								TABLE_SETTINGS: {
									type: 'files',
									name: BX.message('MSLLogTitle'),
									useTagsInSearch : false
								}
							});
						},
						arrowFlag: true,
						icon: 'file',
					});
				}

				if (BX.message('MSLPathToKnowledgeGroup'))
				{
					BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

				BX.MobileLivefeed.PageMenuInstance.init({
					type: 'list',
				});
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
						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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
											app.exec('showPostForm', BX.MobileLivefeed.PostFormOldManagerInstance.show());
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
							BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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
							BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
							id: 'refresh',
							name: BX.message('MOBILE_EXT_LIVEFEED_LIST_MENU_REFRESH'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/n_refresh.png",
							arrowFlag: false,
							action: function() {
								oMSL.pullDownAndRefresh();
							}
						});

						BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
							id: 'followDefault',
							name: (
								BX.MobileLivefeed.FollowManagerInstance.getFollowDefaultValue()
									? BX.message('MSLMenuItemFollowDefaultY')
									: BX.message('MSLMenuItemFollowDefaultN')
							),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/glasses.png",
							arrowFlag: false,
							feature: 'follow',
							action: function() {
								BX.MobileLivefeed.FollowManagerInstance.setFollowDefault({
									value: !BX.MobileLivefeed.FollowManagerInstance.getFollowDefaultValue()
								});
							}
						});

						if (oMSL.bShowExpertMode)
						{
							BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.push({
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

						BX.MobileLivefeed.PageMenuInstance.init({
							type: 'list',
						});
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
				postFormData: (BX.type.isNotEmptyObject(params.postFormData) ? params.postFormData : {}),
			});

			BX.MobileLivefeed.FollowManagerInstance.setFollowDefaultValue(BX.type.isBoolean(params.bFollowDefault) ? params.bFollowDefault : true);

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
				BX.MobileLivefeed.CommentsInstance.onLogEntryCommentAdd(data.log_id);
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
				BX.MobileLivefeed.FollowManagerInstance.setFollow({
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
				BX.MobileLivefeed.CommentsInstance.onLogEntryCommentAdd(data.log_id, data.num);
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
	}
	else if (logID > 0)
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
	}

	if (
		bEmptyPage
		|| logID > 0
	)
	{
		if (
			logID > 0
			&& BX('post_item_top_wrap_' + logID)
		)
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX('post_item_top_wrap_' + logID), {
				likeNodeClass: "post-item-informer-like",
				copyItemClass: "post-item-copyable",
				copyTextClass: "post-item-copytext"
			});
		}
		else if (BX("post_item_top_wrap"))
		{
			BX.MobileUI.addLivefeedLongTapHandler(BX('post_item_top_wrap'), {
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
					BX.MobileLivefeed.PageInstance.initScroll(true);
				}, 0); // ready is not enough

				if (!bReload)
				{
					BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function()
					{
						var networkState = navigator.network.connection.type;

						if (networkState == Connection.UNKNOWN || networkState == Connection.NONE)
						{
							app.exec('pullDownLoadingStop');
							BX.MobileLivefeed.PageInstance.initScroll(false, true);
						}
						else
						{
							__MSLPullDownInit(true, false);
							BX.MobileLivefeed.PageInstance.initScroll(true, true)
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
				BX.MobileLivefeed.PageScrollInstance.init();

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
						var lastActivityDate = BX.MobileLivefeed.Instance.getLastActivityDate();
						if (lastActivityDate > 0)
						{
							var iNowDate = Math.round(new Date().getTime() / 1000);
							if ((iNowDate - lastActivityDate) > 1740)
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
														BX.MobileLivefeed.Instance.setLogId(data.log_id);
													}

													// get comments on an empty page after become active (wake-up)
													BX.MobileLivefeed.CommentsInstance.getList({
														ts: oMSL.iDetailTs,
														bPullDown: false,
														obFocus: {
															form: 'NO',
															comments: 'NO'
														},
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
				if (parseInt(data.log_id) > 0)
				{
					BX.MobileLivefeed.Instance.setLogId(data.log_id);
				}

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
		lang: BX.message('LANGUAGE_ID'),
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
			!BX.MobileLivefeed.Instance.isPullDownEnabled
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
						BX.MobileLivefeed.PageInstance.refresh(true);
						BX.onCustomEvent('BX.MobileLivefeed.SearchBar::setHideByRefresh');
						app.exec("hideSearchBar");
						setTimeout(function() {
							BX.onCustomEvent('BX.MobileLivefeed.SearchBar::unsetHideByRefresh');
						}, 1000);
					}
				}
			});
			BXMobileApp.UI.Page.Refresh.setEnabled(true);
		}
		BX.MobileLivefeed.Instance.isPullDownEnabled = true;
	}
	else
	{
		BXMobileApp.UI.Page.Refresh.setEnabled(false);

		BX.MobileLivefeed.Instance.isPullDownEnabled = false;
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
						BX.MobileLivefeed.CommentsInstance.getList({
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

BitrixMSL = function ()
{
	this.scriptsAttached = [];
	this.counterTimeout = null;
	this.commentsType = false;
	this.entityXMLId = '';

	this.commentTextCurrent = '';
	this.arMention = {};

	this.bShowExpertMode = true;
	this.bExpertMode = false;

	this.bKeyboardCaptureEnabled = false;
	this.keyboardShown = null;

	this.arBlockToCheck = {};
	this.iDetailTs = 0;
	this.arRatingLikeProcess = {};
	this.bDetailEmptyPage = null;
	this.bCounterReceived = false;

	this.counterValue = 0;
	this.blocksToCheckRegisteredList = [];
	this.menuData = {};

	this.emptyRefreshCommentsFlag = false;
	this.detailPageFocus = null;

	this.classes = {
		postItemBlockFull: 'post-item-post-block-full'
	};

	this.xhr = {
		refresh: null
	};
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
	BX.MobileLivefeed.PageInstance.refresh(true, params);
};

BitrixMSL.prototype.shareBlogPost = function(data)
{
//	alert(JSON.stringify(data));
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
		bFollow: (typeof data.follow == 'undefined' || data.follow != 'N'),
		feed_id:  (typeof data.feed_id != 'undefined' ? data.feed_id : null),
		entryParams: {
			destinations: (typeof data.destinations != 'undefined' ? data.destinations : null),
			post_perm: (typeof data.post_perm != 'undefined' ? data.post_perm : null),
			post_id: (typeof data.post_id != 'undefined' ? data.post_id : null),
			post_content_type_id: (BX.type.isNotEmptyString(data.post_content_type_id) ? data.post_content_type_id : null),
			post_content_id: (!BX.type.isUndefined(data.post_content_id) ? data.post_content_id : null),
		},
		TS: (typeof data.TS != 'undefined' ? data.TS : null),
		readOnly: (data.read_only != 'undefined' ? data.read_only : 'N')
	});

	BX.MobileLivefeed.CommentsInstance.abortXhr();

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

		BX('comments_control').removeEventListener('click', BX.MobileLivefeed.CommentsInstance.setFocusOnCommentForm);
		if (data.can_user_comment !== 'NO')
		{
			BX('comments_control').addEventListener('click', BX.MobileLivefeed.CommentsInstance.setFocusOnCommentForm);
		}
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
								{
									likeId: ratingVoteId,
									entityTypeId: ratingVoteEntityTypeId,
									entityId: ratingVoteEntityId,
									available: 'Y',
									userId: BX.message('USER_ID'),
									localize: {
										LIKE_Y: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_Y'),
										LIKE_N: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_Y'),
										LIKE_D: BX.message('MOBILE_EXT_LIVEFEED_RATING_TEXT_LIKE_D'),
									},
									template: 'like_react',
									pathToUserProfile: BX.message('MSLPathToUser'),
									mobile: true
								}
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
				BX.MobileLivefeed.FollowManagerInstance.setFollow({
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
		&& !BX.MobileLivefeed.FollowManagerInstance.getFollowDefaultValue()
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
			BX.MobileLivefeed.Post.moveTop();
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

	if (
		BX('post-comments-wrap')
		&& (
			!bReopen
			|| oMSL.emptyRefreshCommentsFlag // after UIApplicationDidBecomeActiveNotification
		)
	)
	{
		// get comments when draw detail page/empty
		BX.MobileLivefeed.CommentsInstance.getList({
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

	if (!bReopen)
	{
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

		oMSL.adjustDetailPageFocus();
	}
	else
	{
		if (
			!BX.type.isUndefined(data.commentsNumNew)
			&& parseInt(data.commentsNumNew) > 0
		)
		{
			BX.MobileLivefeed.CommentsInstance.setFocusOnComments('list');
		}
	}

	BX.removeAllCustomEvents('main.post.form/mobile_simple');
	BX.addCustomEvent('main.post.form/mobile_simple', function() {
		setTimeout(oMSL.adjustDetailPageFocus, 150);
	});
};

BitrixMSL.prototype.adjustDetailPageFocus = function()
{
	if (!oMSL.detailPageFocus)
	{
		return;
	}

	BX.MobileLivefeed.CommentsInstance.setFocusOnComments(oMSL.detailPageFocus);
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

BitrixMSL.prototype.InitDetail = function(params)
{
	this.commentsType = (typeof (params.commentsType) != 'undefined' && params.commentsType == 'blog' ? 'blog' : 'log');
	this.entityXMLId = (typeof (params.entityXMLId) != 'undefined' ? params.entityXMLId : '');
	this.commentTextCurrent = '';
	this.arMention = {};
	this.iDetailTs = (typeof (params.TS) != 'undefined' ? params.TS : 0);

	BX.MobileLivefeed.FollowManagerInstance.setFollowValue(!(!BX.Type.isUndefined(params.bFollow) && !params.bFollow));

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
		&& BX.MobileLivefeed.Instance.getOption('detailPageId') != params.detailPageId
	)
	{
		BX.MobileLivefeed.Instance.setOptions({
			detailPageId: params.detailPageId,
		});

		if (
			typeof (params.logId) != 'undefined'
			&& parseInt(params.logId) > 0
		)
		{
			this.logId = parseInt(params.logId);
		}
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

	BX.MobileLivefeed.PageMenuInstance.detailPageMenuItems = BX.MobileLivefeed.PageMenuInstance.buildDetailPageMenu(this.menuData);
	BX.MobileLivefeed.PageMenuInstance.init({
		type: 'detail',
	});

	if (Application.getApiVersion() >= 34)
	{
		setTimeout(function() {
			var postInstance = BX.MobileLivefeed.Instance.getPostFromLogId(BX.MobileLivefeed.Instance.getLogId());
			if (postInstance)
			{
				postInstance.initDetailPin();
			}
		}.bind(this), 0);
	}
};

BitrixMSL.prototype.setExpertMode = function(params)
{
	if (typeof params.value == 'undefined')
	{
		return;
	}

	var newValue = !!params.value;

	if (!BX.type.isNotEmptyString(BX.MobileLivefeed.Instance.getOption('detailPageId')))
	{
		oMSL.bExpertMode = newValue;
		this.setExpertModeMenuItemName();
	}

	var post_data = {
		sessid: BX.bitrix_sessid(),
		site: BX.message('SITE_ID'),
		lang: BX.message('LANGUAGE_ID'),
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
			if (!BX.type.isNotEmptyString(BX.MobileLivefeed.Instance.getOption('detailPageId')))
			{
				failCallbackFunc(post_data);
			}
		}
	}.bind(this), function(response) {
		app.hidePopupLoader();
		if (!BX.type.isNotEmptyString(BX.MobileLivefeed.Instance.getOption('detailPageId')))
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

		if (BX('comcntleave-all'))
		{
			old_value = (BX('comcntleave-all').innerHTML.length > 0 ? parseInt(BX('comcntleave-all').innerHTML) : 0);
			val = old_value + 1;
			BX('comcntleave-all').innerHTML = val;
		}

		BXMobileApp.onCustomEvent('onLogEntryCommentAdd', { logId: logId }, true);
	}
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
				logId: BX.MobileLivefeed.Instance.getLogId(),
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

BitrixMSL.prototype.setExpertModeMenuItemName = function()
{
	var menuItem = false;
	for(var i = 0; i < BX.MobileLivefeed.PageMenuInstance.listPageMenuItems.length; i++)
	{
		menuItem = BX.MobileLivefeed.PageMenuInstance.listPageMenuItems[i];
		if (
			typeof menuItem.feature != 'undefined'
			&& menuItem.feature == 'expert'
		)
		{
			menuItem.name = (oMSL.bExpertMode ? BX.message('MSLMenuItemExpertModeY') : BX.message('MSLMenuItemExpertModeN'));
			BX.MobileLivefeed.PageMenuInstance.listPageMenuItems[i] = menuItem;
			BX.MobileLivefeed.PageMenuInstance.init({
				type: 'list',
			});
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
			if (
				nodeToCheckId.title2_block_id
				&& BX(nodeToCheckId.title2_block_id)
			)
			{
				blockHeight += BX(nodeToCheckId.title2_block_id).offsetHeight;
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

BitrixMSL.prototype.refreshPostDetail = function()
{
	BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.mobileGetDetail', {
		data: {
			logId: BX.MobileLivefeed.Instance.getLogId()
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

			BX.Mobile.Ajax.runComponentAction('bitrix:tasks.task', 'legacyAdd', {
				mode: 'class',
				data: {
					data: taskData,
					parameters: {
						RETURN_ENTITY: true,
						PLATFORM: 'mobile',
					},
				},
				onrequeststart: function(xhr) {
					this.xhr = xhr;
				}.bind(this),
			}).then(
				function(response)
				{
					app.hidePopupLoader();

					if (
						typeof response.data != 'undefined'
						&& typeof response.data.DATA != 'undefined'
						&& typeof response.data.DATA.ID != 'undefined'
						&& parseInt(response.data.DATA.ID) > 0
					)
					{
						this.createTaskSetContentSuccess(response.data.DATA.ID);

						BX.Mobile.Ajax.runAction('socialnetwork.api.livefeed.createTaskComment', {
							data: {
								params: {
									postEntityType: (BX.type.isNotEmptyString(params.postEntityType) ? params.postEntityType : params.entityType),
									entityType: params.entityType,
									entityId: params.entityId,
									taskId: response.data.DATA.ID,
									logId: (BX.type.isNumber(params.logId) ? params.logId : null)
								}
							}
						}).then(function(response) {
						}, function(response) {
						});
					}
				}.bind(this),
				function(response)
				{
					if (response.errors && response.errors.length)
					{
						var errors = [];
						for (var i = 0; i < response.errors.length; i++)
						{
							errors.push(response.errors[i]['message']);
						}
						this.createTaskSetContentFailure(errors);
					}
				}.bind(this)
			);
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
				BXMobileApp.Events.postToComponent(
					'taskbackground::task::open',
					[{taskId: taskId}]
				);
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

BitrixMSL.prototype.getDiskUploadFolder = function()
{

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
				BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
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
					BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
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
				BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
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

	BX.MobileLivefeed.PostFormOldManagerInstance.setExtraDataArray({
		postId: false,
		postAuthorId: false,
		logId: false,
		messageUFCode: BX.message('MOBILE_EXT_LIVEFEED_POST_FILE_UF_CODE')
	});

	BX.MobileLivefeed.PostFormOldManagerInstance.setParams({
		selectedRecipients: selectedDestinations,
		messageText: '',
		messageFiles: [],
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
									BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
										selectedDestinations,
										{
											type: 'UA'
										}
									);
								}
							}
							else
							{
								BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
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
							BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
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

					BX.MobileLivefeed.PostFormOldManagerInstance.setParams({
						selectedRecipients: selectedDestinations
					});

					if (
						typeof obResult.POST_MESSAGE != 'undefined'
						&& obResult.POST_MESSAGE != null
					)
					{
						BX.MobileLivefeed.PostFormOldManagerInstance.setParams({
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
		BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
			selectedDestinations,
			{
				type: 'SG',
				id:  parseInt(groupID),
				name: BX.message('MSLGroupName')
			}
		);
	}
	else if (BX.MobileLivefeed.Instance.availableGroupList !== false)
	{
		for (key in BX.MobileLivefeed.Instance.availableGroupList)
		{
			if (!BX.MobileLivefeed.Instance.availableGroupList.hasOwnProperty(key))
			{
				continue;
			}

			BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
				selectedDestinations,
				{
					type: 'SG',
					id:  parseInt(BX.MobileLivefeed.Instance.availableGroupList[key]['entityId']),
					name: BX.MobileLivefeed.Instance.availableGroupList[key]['name']
				}
			);
		}
	}
	else if (BX.message('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DEFAULT') == 'Y')
	{
		BX.MobileLivefeed.PostFormOldManagerInstance.addDestination(
			selectedDestinations,
			{
				type: 'UA'
			}
		);
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
							title2_block_id: 'post_text_title_' + lentaItemId,
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

if (!window.oMSL)
{
	oMSL = new BitrixMSL;
	window.oMSL = oMSL;
}

function openTaskComponentByTaskId(e, taskId, data) {
	data = data || {};
	data.selectedTab = data.selectedTab || 'taskTab';

	BXMobileApp.Events.postToComponent("taskbackground::task::open", [{taskId:taskId, data:data}], "background");

	e.preventDefault();
	e.stopPropagation();
	return false;
}
