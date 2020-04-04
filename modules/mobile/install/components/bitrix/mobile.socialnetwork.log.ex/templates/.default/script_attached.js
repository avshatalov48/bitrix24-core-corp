(function(){
	if (window["oMSL"])
		return ;
	var repoLog = {}, mid = {},
		f = function(ENTITY_XML_ID, id) {
			if (mid[id.join('-')] !== "hidden")
			{
				mid[id.join('-')] = "hidden";
				if (repoLog[oMSL.logId])
				{
					repoLog[oMSL.logId]["POST_NUM_COMMENTS"]--;
					BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
						log_id: oMSL.logId,
						num : repoLog[oMSL.logId]["POST_NUM_COMMENTS"]
					}, true);
				}
			}
		};
	BX.addCustomEvent(window, "OnUCommentWasDeleted", f);
	BX.addCustomEvent(window, "OnUCommentWasHidden", f);
	BX.addCustomEvent(window, "OnUCRecordHasDrawn", function(ENTITY_XML_ID, id) {
		mid[ENTITY_XML_ID] = (mid[ENTITY_XML_ID] || {});
		if (mid[id.join('-')] !== "drawn")
		{
			mid[id.join('-')] = "drawn";
			var node;
			if (repoLog[oMSL.logId] && (node = BX('record-' + id.join('-') + '-cover')) &&
				node && node.parentNode == BX('record-' + ENTITY_XML_ID + '-new'))
			{
				repoLog[oMSL.logId]["POST_NUM_COMMENTS"]++;
				BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
					log_id: oMSL.logId,
					num : repoLog[oMSL.logId]["POST_NUM_COMMENTS"]
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
		(node = BX(node))
		&& node
		&& !node.hasAttribute("done")
	)
	{
		oMSL.setLogEntryImpPostRead({
			node: node,
			value: true
		});
		var data = {
			mobile_action : 'read_post',
			action : 'read_post',
			post_id : node.getAttribute("bx-data-post-id"),
			options : [{ post_id : node.getAttribute("bx-data-post-id"), name : "BLOG_POST_IMPRTNT", value : "Y"}],
			sessid : BX.bitrix_sessid()};
		BX.ajax({
			method : 'GET',
			url : BX.message('SITE_DIR') + 'mobile/index.php?' + BX.ajax.prepareData(data),
			dataType : 'json',
			onsuccess : BX.delegate(function(responseData) {
				if (
					typeof responseData.SUCCESS != 'undefined'
					&& responseData.SUCCESS == 'Y'
				)
				{
					BXMobileApp.onCustomEvent('onLogEntryImpPostRead', {
						postId: data.post_id
					}, true);
				}
				else
				{
					oMSL.setLogEntryImpPostRead({
						node: node,
						value: false
					});
				}
			}, this),
			onfailure: function() {
				oMSL.setLogEntryImpPostRead({
					node: node,
					value: false
				});
			}
		});
		return true;
	}
	return false;
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

	BX.addCustomEvent("onFrameDataReceived", function(obCache) {
		window.isPullDownEnabled = false;
		window.isPullDownLocked = false;
		window.isFrameDataReceived = true;
		app.pullDownLoadingStop();
		BitrixMobile.LazyLoad.showImages(true);
		BX.localStorage.set('mobileLivefeedRefreshTS',  Math.round(new Date().getTime() / 1000), 86400*30);
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
				oMSL.iframeCacheTs = parseInt(blocks[0]['PROPS']['TS']);
			}
		}

		BitrixMobile.LazyLoad.showImages(true);
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
				app.exec("pullDownLoadingStart");
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
				app.pullDownLoadingStop();
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
			app.pullDownLoadingStop();
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
					app.pullDownLoadingStop();
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
		if (
			window.platform != "ios"
			|| app.enableInVersion(27)
		) // there's a bug in versions before 27/iOS
		{
			BXMobileApp.UI.Page.Refresh.setEnabled(false);
		}
	});

	BX.addCustomEvent("onPullDownEnable", function() {
		if (
			window.platform != "ios"
			|| app.enableInVersion(27)
		) // there's a bug in versions before 27/iOS
		{
			BXMobileApp.UI.Page.Refresh.setEnabled(true);
		}
	});

	if (
		window.platform != "ios"
		|| app.enableInVersion(27)
	) // there's a bug in versions before 27/iOS
	{
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
	else
	{
		app.pullDown({
			enable: true,
			backgroundColor: '#E7E9EB'
		});
	}
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

	oMSL.bShowExpertMode = !!params.bShowExpertMode;
	oMSL.bExpertMode = !!params.bExpertMode;

	oMSL.bDetailEmptyPage = bEmptyPage;
	oMSL.curUrl = params.curUrl;
	oMSL.appCacheDebug = !!params.appCacheDebug;

	if (!bAjaxCall)
	{
/*
		BX.ready(function()
		{
			window.onerror = function(message, url, linenumber) {
				__MSLSendError(message, url, linenumber);
			}
		});
*/
	}

	if (
		logID <= 0
		&& !bEmptyPage
		&& !bAjaxCall
		&& !bReload
	)
	{
		oMSL.registerBlocksToCheck();

		if (
			typeof window.bFeedInitialized != 'undefined'
			&& window.bFeedInitialized
		)
		{
			if (!bAjaxCall)
			{
				BX.ready(function() {
					var windowSize = BX.GetWindowSize();
					window.maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
					setTimeout(function() {

						oMSL.checkNodesHeight();

						BX.onCustomEvent(window, 'BX.UserContentView.onRegisterViewAreaListCall', [{
							containerId: 'lenta_wrapper',
							className: 'post-item-contentview',
							fullContentClassName: 'post-item-full-content'
						}]);
					}, 1000);
				});
			}
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

		oMSL.listPageMenuItems = [];

		if (groupID > 0)
		{
			BX.ready(function()
			{
				oMSL.listPageMenuItems.push({
					name: BX.message('MSLAddPost'),
					image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
					action: function()
					{
						app.exec('showPostForm', oMSL.showNewPostForm());
					},
					arrowFlag: false
				});

				oMSL.listPageMenuItems.push({
					name: BX.message('MSLMenuItemGroupTasks'),
					icon: 'checkbox',
					arrowFlag: true,
					action: function() {

						if (Application.getApiVersion() >= 31)
						{
							BXMobileApp.Events.postToComponent("taskbackground::task::action", [{groupId: groupID}], "background");
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
					name: BX.message('MSLMenuItemGroupFiles'),
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

				oMSL.showPageMenu('list');
			});
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
							name: BX.message('MSLAddPost'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/pencil.png",
							action: function()
							{
								app.exec('showPostForm', oMSL.showNewPostForm());
							},
							arrowFlag: false
						});

						if (BX.message('MSLMenuItemWork'))
						{
							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemWork'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/work.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?work=Y",
										cache: false,
										bx24ModernStyle: true
									});
								}
							});
						}

						oMSL.listPageMenuItems.push({
							name: BX.message('MSLMenuItemFavorites'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/favorite.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?favorites=Y",
									cache: false,
									bx24ModernStyle: true
								});
							}
						});

						oMSL.listPageMenuItems.push({
							name: BX.message('MSLMenuItemMy'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/mine.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?my=Y",
									cache: false,
									bx24ModernStyle: true
								});
							}
						});

						oMSL.listPageMenuItems.push({
							name: BX.message('MSLMenuItemImportant'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/important.png",
							arrowFlag: true,
							action: function() {
								app.loadPageBlank({
									url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?important=Y",
									cache: false,
									bx24ModernStyle: true
								});
							}
						});

						if (BX.message('MSLMenuItemBizproc'))
						{
							oMSL.listPageMenuItems.push({
								name: BX.message('MSLMenuItemBizproc'),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/workflow.png",
								arrowFlag: true,
								action: function() {
									app.loadPageBlank({
										url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?bizproc=Y",
										cache: false,
										bx24ModernStyle: true
									});
								}
							});
						}

						oMSL.listPageMenuItems.push({
							name: BX.message('MSLMenuItemRefresh'),
							image: "/bitrix/templates/mobile_app/images/lenta/menu/n_refresh.png",
							arrowFlag: false,
							action: function() {
								oMSL.pullDownAndRefresh();
							}
						});

						if (oMSL.bUseFollow)
						{
							oMSL.listPageMenuItems.push({
								name: (
									oMSL.bFollowDefault
										? BX.message('MSLMenuItemFollowDefaultY')
										: BX.message('MSLMenuItemFollowDefaultN')
								),
								image: "/bitrix/templates/mobile_app/images/lenta/menu/glasses.png",
								arrowFlag: false,
								feature: 'follow',
								action: function() {
									oMSL.setFollowDefault({
										value: !oMSL.bFollowDefault
									});
								}
							});
						}

						if (oMSL.bShowExpertMode)
						{
							oMSL.listPageMenuItems.push({
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

						oMSL.showPageMenu('list');

						if(app.enableInVersion(20))
						{
							app.addButtons({
								addPostButton:{
									type: "edit",
									style:"custom",
									callback:function(){
										app.exec('showPostForm', oMSL.showNewPostForm());
									}
								}
							});
						}
						else if(app.enableInVersion(13))
						{
							BXMobileApp.UI.Page.SlidingPanel.show({
								hidden_sliding_panel: true,
								scroll_backlash: 20.0,
								buttons:
								{
									addButton: {
										name: BX.message('MSLSliderAddPost'),
										image: "/bitrix/templates/mobile_app/images/lenta/slider/addpost.png?1",
										callback: function () {
											app.exec('showPostForm', oMSL.showNewPostForm());
										}
									},
									favoritesButton: {
										name: BX.message('MSLSliderFavorites'),
										image: "/bitrix/templates/mobile_app/images/lenta/slider/favorites.png?2",
										callback: function () {
											app.loadPageBlank({
												url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + "mobile/index.php?favorites=Y",
												cache: false,
												bx24ModernStyle: true
											});
										}
									}
								}
							});
						}
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

		var selectedDestinations = {
			a_users: [],
			b_groups: []
		};

		oMSL.clearPostFormDestination(selectedDestinations, groupID); // to work before DBLoad

		BX.MSL.DBLoad(
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
								if (BX.message('MSLIsDenyToAll') != 'Y')
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

					if (
						typeof obResult.POST_MESSAGE != 'undefined'
						&& obResult.POST_MESSAGE != null
					)
					{
						oMSL.setPostFormParams({
							messageText: oMSL.unParseMentions(obResult.POST_MESSAGE)
						});
					}
				},
				onEmpty: function (obResult)
				{
					selectedDestinations = {
						a_users: [],
						b_groups: []
					};
					oMSL.clearPostFormDestination(selectedDestinations, groupID);
				}
			},
			(groupID > 0 ? groupID : false)
		);

		oMSL.setPostFormParams({
			selectedRecipients: selectedDestinations
		});

		oMSL.setPostFormExtraData({
			messageUFCode: BX.message('MSLPostFormUFCode')
		});

		BXMobileApp.addCustomEvent("onMPFSent", function(post_data)
		{
			oMSL.onMPFSent(post_data, groupID);
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

		BX.addCustomEvent("onStreamRefresh", function(data) {
			document.location.replace(document.location.href);
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

		BXMobileApp.addCustomEvent("onLogEntryImpPostRead", function(data) {
			if (BX('important_post_' + data.postId))
			{
				oMSL.onLogEntryImpPostRead({
					node: BX('important_post_' + data.postId)
				});
			}
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
	else if (bEmptyPage)
	{
		window.isDetailPullDownEnabled = false;

		oMSL.arBlockToCheck = {
			0: {
				lenta_item_id: 'lenta_item',
				text_block_id: 'post_block_check_cont',
				title_block_id: 'post_block_check_title',
				more_overlay_id: 'post_more_block',
				more_button_id: 'post_more_limiter'
			}
		};

		BX.ready(function()
		{
			BX.addCustomEvent('onMPFSentEditStart', function() {
				app.showPopupLoader({text:""});
			});

			__MSLDrawDetailPage();
			BX.addCustomEvent('onOpenPageBefore', function() { __MSLDrawDetailPage(); } );
			BXMobileApp.addCustomEvent('onEditedPostInserted', function(data) {
				app.hidePopupLoader();
				oMSL.drawDetailPageText(data);
				BXMobileApp.onCustomEvent('onLogEntryPostUpdated', data, true);
			});

			BXMobileApp.addCustomEvent('onEditedPostFailed', function() {
				app.hidePopupLoader();
			});

			BX.MSL.viewImageBind(
				'post_block_check_cont',
				{
					tag: 'IMG',
					attr: 'data-bx-image'
				}
			);
		});

		BX.MobileUI.addLivefeedLongTapHandler(BX("post_item_top_wrap"), {
			likeNodeClass: "post-item-informer-like",
			copyItemClass: "post-item-copyable",
			copyTextClass: "post-item-copytext"
		});

		BX.MobileUI.addLivefeedLongTapHandler(BX("post-comments-wrap"), {
			likeNodeClass: "post-comment-control-item-like",
			copyItemClass: "post-comment-block",
			copyTextClass: "post-comment-text"
		});
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
			oMSL.registerBlocksToCheck();
			setTimeout(function() { oMSL.checkNodesHeight(); }, 100);

			BX.addCustomEvent('onMPFSentEditStart', function() {
				app.showPopupLoader({text:""});
			});

			BXMobileApp.addCustomEvent('onEditedPostInserted', function(data) {
				app.hidePopupLoader();
				oMSL.drawDetailPageText(data);
				BXMobileApp.onCustomEvent('onLogEntryPostUpdated', data, true);
			});
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

		BX.MobileUI.addLivefeedLongTapHandler(BX("post-comments-wrap"), {
			likeNodeClass: "post-comment-control-item-like",
			copyItemClass: "post-comment-block",
			copyTextClass: "post-comment-text"
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
				var windowSize = BX.GetWindowSize();
				window.maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
				oMSL.initScroll(true);

				if (!bReload)
				{
					BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params)
					{
						var networkState = navigator.network.connection.type;

						if (networkState == Connection.UNKNOWN || networkState == Connection.NONE)
						{
							app.pullDownLoadingStop();
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

				BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params)
				{
					var networkState = navigator.network.connection.type;

					if (
						networkState == Connection.UNKNOWN
						|| networkState == Connection.NONE
					)
					{
						app.pullDownLoadingStop();
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
														oMSL.logId = parseInt(data.log_id);
													}

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

	BX.addCustomEvent('MobilePlayer:onError', oMSL.onMobilePlayerError);
}

function __MSLOnFeedScroll()
{
	var windowScroll = BX.GetWindowScrollPos();
	if (
		windowScroll.scrollTop >= window.maxScroll
		&& (
			windowScroll.scrollTop > 0 // refresh patch
			|| window.maxScroll > 0
		)
		&& !window.bRefreshing
	)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);

		bGettingNextPage = true;

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
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
							var windowSize = BX.GetWindowSize();
							window.maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;

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
				bGettingNextPage = false;
			},
			callback_failure: function() {
				nextPageXHR = null;
				bGettingNextPage = false;
			}
		});

		nextPageXHR = BMAjaxWrapper.xhr;
	}
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

	if (BX('post_block_check_cont_' + params.log_id))
	{
		params.detailText = BX('post_block_check_cont_' + params.log_id).innerHTML;
		params.bIsPhoto = (BX.hasClass(BX('post_block_check_cont_' + params.log_id), "post-item-post-img-block"));
		params.bIsImportant = (
			BX.hasClass(BX('post_block_check_cont_' + params.log_id), "info-block-important")
			&& BX.hasClass(BX('post_block_check_cont_' + params.log_id), "lenta-info-block")
		);
	}

	if (BX('post_more_limiter_' + params.log_id))
	{
		params.showMoreButton = (BX('post_more_limiter_' + params.log_id).style.display != 'none');
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
	document.body.scrollTop = document.body.scrollHeight;
}

function __MSLDetailMoveTop()
{
	document.body.scrollTop = 0;
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

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
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

function __MSLRefresh(bScroll)
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

	BX.onCustomEvent('onBeforeMobileLivefeedRefresh', [{}]);

	oMSL.refreshNeeded = false;
	oMSL.refreshStarted = true;
	oMSL.hideRefreshNeededNotifier();

	bRefreshing = true;

	var reload_url = document.location.href;
	reload_url = reload_url.replace("&RELOAD=Y", "").replace("RELOAD=Y&", "").replace("RELOAD=Y", "").replace("&RELOAD_JSON=Y", "").replace("RELOAD_JSON=Y&", "").replace("RELOAD_JSON=Y", "");
	reload_url += (reload_url.indexOf('?') !== -1 ? "&" : "?") + 'RELOAD=Y&RELOAD_JSON=Y';

	var headers = [
		{ name: "BX-ACTION-TYPE", value: "get_dynamic" },
		{ name: "BX-REF", value: document.referrer },
		{ name: "BX-CACHE-MODE", value: "APPCACHE" },
		{ name: "BX-APPCACHE-PARAMS", value: JSON.stringify(window.appCacheVars) },
		{ name: "BX-APPCACHE-URL", value: (typeof BX.frameCache != 'undefined' && typeof BX.frameCache.vars != 'undefined' && typeof BX.frameCache.vars.PAGE_URL != 'undefined' ? BX.frameCache.vars.PAGE_URL : oMSL.curUrl) }
	];

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'GET',
		url: reload_url,
		data: '',
		headers: headers,
		callback: function(data)
		{
			oMSL.refreshStarted = false;
			oMSL.refreshNeeded = false;

			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');
			if (
				typeof data != 'undefined'
				&& typeof (data.PROPS) != 'undefined'
				&& typeof (data.PROPS.CONTENT) != 'undefined'
				&& data.PROPS.CONTENT.length > 0
			)
			{
				BitrixMobile.LazyLoad.clearImages();
				BX.clearNodeCache();
				app.pullDownLoadingStop();
				app.hidePopupLoader();
				oMSL.hideNotifier();
				oMSL.hideRefreshNeededNotifier();

				if (typeof (data.COUNTER_TO_CLEAR) != 'undefined')
				{
					BXMobileApp.onCustomEvent('onClearLFCounter', [data.COUNTER_TO_CLEAR], true);

					var eventParams = {
						counterCode: data.COUNTER_TO_CLEAR,
						serverTime: data.COUNTER_SERVER_TIME,
						serverTimeUnix: data.COUNTER_SERVER_TIME_UNIX
					};


					if (window.app.enableInVersion(25))
					{
						BXMobileApp.Events.postToComponent("onClearLiveFeedCounter", eventParams, "communication");
					}
					else
					{
						BXMobileApp.onCustomEvent('onClearLiveFeedCounter', eventParams, true);
					}
				}

				oMSL.processAjaxBlock(data.PROPS, {
					type: 'refresh',
					callback: function()
					{
						if (
							typeof BX.frameCache != 'undefined'
							&& BX("bxdynamic_feed_refresh")
							&& (
								typeof data.REWRITE_FRAMECACHE == 'undefined'
								|| data.REWRITE_FRAMECACHE != 'N'
							)
						)
						{
							var props = {
								USE_BROWSER_STORAGE: true,
								AUTO_UPDATE: true,
								USE_ANIMATION: false
							};

							if (
								typeof (data.TS) != 'undefined'
								&& parseInt(data.TS) > 0
							)
							{
								props.TS = parseInt(data.TS);
								oMSL.iframeCacheTs = props.TS;
							}

							BX.frameCache.writeCacheWithID(
								"framecache-block-feed",
								BX("bxdynamic_feed_refresh").innerHTML,
								parseInt(Math.random() * 100000),
								JSON.stringify(props)
							);
						}

						oMSL.registerBlocksToCheck();

						//Android hack.
						//The processing of javascript and insertion of html works not so fast as expected
						setTimeout(function(){
							BitrixMobile.LazyLoad.showImages(); // when refresh
						}, 1000);

					}
				});

				BX.localStorage.set('mobileLivefeedRefreshTS',  Math.round(new Date().getTime() / 1000), 86400*30);

				if (bScroll)
				{
					BitrixAnimation.animate({
						duration : 1000,
						start : { scroll : document.body.scrollTop },
						finish : { scroll : 0 },
						transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
						step : function(state)
						{
							document.body.scrollTop = state.scroll;
						},
						complete : function(){}
					});
				}

				if (
					data.isManifestUpdated == "1"
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
				app.pullDownLoadingStop();
				__MSLRefreshError(true);
			}

			bRefreshing = false;
		},
		callback_failure: function()
		{
			oMSL.refreshStarted = false;
			oMSL.refreshNeeded = false;

			BX.removeClass(BX('lenta_notifier'), 'lenta-notifier-waiter');

			app.pullDownLoadingStop();
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
			if (
				window.platform != "ios"
				|| app.enableInVersion(27)
			) // there's a bug in versions before 27/iOS
			{
				BXMobileApp.UI.Page.Refresh.setParams({
					pulltext: BX.message('MSLPullDownText1'),
					backgroundColor: '#E7E9EB',
					downtext: BX.message('MSLPullDownText2'),
					loadtext: BX.message('MSLPullDownText3'),
					callback: function() {
						if (!window.isPullDownLocked)
						{
							__MSLRefresh(true);
						}
					}
				});
				BXMobileApp.UI.Page.Refresh.setEnabled(true);
			}
			else
			{
				app.pullDown({
					enable: true,
					backgroundColor: '#E7E9EB',
					pulltext: BX.message('MSLPullDownText1'),
					downtext: BX.message('MSLPullDownText2'),
					loadtext: BX.message('MSLPullDownText3'),
					callback: function() {
						if (!window.isPullDownLocked)
						{
							__MSLRefresh(true);
						}
					}
				});
			}
		}
		isPullDownEnabled = true;
	}
	else
	{
		if (
			window.platform != "ios"
			|| app.enableInVersion(27)
		) // there's a bug in versions before 27/iOS
		{
			BXMobileApp.UI.Page.Refresh.setEnabled(false);
		}
		else
		{
			app.pullDown({
				enable: false
			});
		}

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
						var iconFailed = BX.findChild(BX('post-comments-wrap'), { className: 'post-comments-failed-outer' }, true, false);
						if (!!iconFailed)
						{
							BX.cleanNode(iconFailed, true);
						}

						oMSL.getComments({
							ts: ts,
							bPullDown: true,
							obFocus: {
								form: false
							}
						});
					}
				}
			};

			if (
				window.platform != "ios"
				|| app.enableInVersion(27)
			) // there's a bug in versions before 27/iOS
			{
				BXMobileApp.UI.Page.Refresh.setParams({
					pulltext: BX.message('MSLDetailPullDownText1'),
					backgroundColor: '#E7E9EB',
					downtext: BX.message('MSLDetailPullDownText2'),
					loadtext: BX.message('MSLDetailPullDownText3'),
					callback: callbackFunction
				});
				BXMobileApp.UI.Page.Refresh.setEnabled(true);
			}
			else
			{
				app.pullDown({
					enable: true,
					backgroundColor: '#E7E9EB',
					pulltext: BX.message('MSLDetailPullDownText1'),
					downtext: BX.message('MSLDetailPullDownText2'),
					loadtext: BX.message('MSLDetailPullDownText3'),
					callback: callbackFunction
				});
			}
		}
		isDetailPullDownEnabled = true;
	}
	else
	{
		if (
			window.platform != "ios"
			|| app.enableInVersion(27)
		) // there's a bug in versions before 27/iOS
		{
			BXMobileApp.UI.Page.Refresh.setEnabled(false);
		}
		else
		{
			app.pullDown({
				enable: false
			});
		}
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

function __MSLSetFavorites(log_id)
{
	var favoritesBlock = BX("log_entry_favorites_" + log_id);

	if (!favoritesBlock)
	{
		return;
	}

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

	var request_data = {
		sessid: BX.bitrix_sessid(),
		site: BX.message('SITE_ID'),
		lang: BX.message('MSLLangId'),
		log_id: parseInt(log_id),
		favorites: strFavoritesNew,
		action: 'change_favorites',
		mobile_action: 'change_favorites'
	};

	var actionUrl = BX.message('MSLSiteDir') + 'mobile/ajax.php';
	actionUrl = BX.util.add_url_param(actionUrl, {
		b24statAction: (strFavoritesNew == 'Y' ? 'addFavorites' : 'removeFavorites'),
		b24statContext: 'mobile'
	});

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: actionUrl,
		data: request_data,
		callback: function(response_data)
		{
			if (response_data["SUCCESS"] == "Y")
			{
				if (strFavoritesNew == "Y")
				{
					oMSL.setFollow({
						logId: log_id,
						bOnlyOn: true,
						bRunEvent: true,
						bAjax: false
					});
				}

				BXMobileApp.onCustomEvent('onLogEntryFavorites', {
					log_id: log_id,
					page_id: (BX.message('MSLPageId') != undefined ? BX.message('MSLPageId') : '')
				}, true);
			}
		},
		callback_failure: function() {}
	});
	return false;
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
		bLockCommentSending = true;
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

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: commentVarURL,
		data: data,
		processData : true,
		callback: function(ajax_response)
		{
			bLockCommentSending = false;

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
						logId: oMSL.log_id,
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
			bLockCommentSending = false;
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

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
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

__MSLSendError = function(message, url, linenumber)
{
	var error_data =  {
		'sessid': BX.bitrix_sessid(),
		'site': BX.message('SITE_ID'),
		'lang': BX.message('MSLLangId'),
		'message': message,
		'url': url,
		'linenumber': linenumber,
		'action': 'log_error',
		'mobile_action': 'log_error'
	};

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': BX.message('MSLSiteDir') + 'mobile/ajax.php',
		'data': error_data,
		'callback': function(data) {},
		'callback_failure': function(data) {}
	});
};

__MSLSendErrorEval = function(script)
{
	BX.evalGlobal('try { ' + script + ' } catch (e) { __MSLSendError(e.message, e.name, e.number); }');
};

BitrixMSL = function ()
{
	this.scriptsAttached = [];
	this.refreshNeeded = false;
	this.refreshStarted = false;
	this.counterTimeout = null;
	this.detailPageId = '';
	this.logId = false;
	this.commentsType = false;
	this.entityXMLId = '';

	this.commentLoadingFilesStack = false;
	this.commentProgressBarAnimation = false;
	this.commentProgressBarState = 0;

	this.sendCommentWritingList = [];
	this.sendCommentWritingListTimeout = [];

	this.commentTextCurrent = '';
	this.arMention = [];

	this.bUseFollow = true;
	this.bFollow = true;
	this.bFollowDefault = true;

	this.bUseTasks = false;

	this.bShowExpertMode = true;
	this.bExpertMode = false;

	this.detailPageMenuItems = [];
	this.listPageMenuItems = [];

	this.bKeyboardCaptureEnabled = false;
	this.keyboardShown = null;

	this.arBlockToCheck = {};
	this.iLastActivityDate = null;
	this.iDetailTs = 0;
	this.iframeCacheTs = 0;
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
};

BitrixMSL.prototype.registerScripts = function(path)
{
	if (!BX.util.in_array(path, this.scriptsAttached))
	{
		this.scriptsAttached.push(path);
	}
};

BitrixMSL.prototype.loadScripts = function()
{
	for (var i = 0; i < this.scriptsAttached.length; i++)
	{
		BX.loadScript(this.scriptsAttached[i] + '?' + parseInt(Math.random() * 100000));
	}
};

BitrixMSL.prototype.pullDownAndRefresh = function()
{
	app.exec("pullDownLoadingStart");
	window.isPullDownLocked = true;
	__MSLRefresh(true);
};

BitrixMSL.prototype.shareBlogPost = function(data)
{
//	alert(JSON.stringify(data));
};

BitrixMSL.prototype.deleteBlogPost = function(data)
{
	app.confirm({
		title: BX.message('MSLDeletePost'),
		text : BX.message('MSLDeletePostDescription'),
		buttons : [
			BX.message('MSLDeletePostButtonOk'),
			BX.message('MSLDeletePostButtonCancel')
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

				var BMAjaxWrapper = new MobileAjaxWrapper;
				BMAjaxWrapper.Wrap({
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
							typeof response_data.SUCCESS != 'undefined'
							&& response_data.SUCCESS == 'Y'
						)
						{
							BXMobileApp.onCustomEvent('onBlogPostDelete', {}, true);
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

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
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

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
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
				logId: postData.log_id
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
					if (postData.PostDestination.hasOwnProperty(key))
					{
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
};

BitrixMSL.prototype.drawDetailPage = function(data)
{
	var bReopen = false;

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
		bUseTasks: (typeof data.use_tasks != 'undefined' && data.use_tasks == 'Y'),
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

			if (BX('rating_text'))
			{
				if (typeof (data.ratingCounter) != 'undefined')
				{
					BX('rating_text').setAttribute('data-counter', parseInt(data.ratingCounter));
				}

				if (BX.type.isNotEmptyString(data.ratingText))
				{
					BX('rating_text').innerHTML = data.ratingText;
					BX('rating_text').style.display = 'inline-block';
				}
				else
				{
					BX('rating_text').style.display = 'none';
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

			if (BX('rating_text'))
			{
				BX('rating_text').style.display = 'none';
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
		&& !this.bFollowDefault
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

	if (!bReopen)
	{
		if (BX('post-comments-wrap'))
		{
			BX.cleanNode(BX('post-comments-wrap')); // to be sure to clear comments
		}

		this.drawDetailPageText(data);

		var contMore = BX.findChild(BX('post_block_check_cont'), { className: 'post-more-block' }, true, false);
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
				BX('post_more_limiter').style.display = 'block';
				BX.bind(BX('post_more_limiter'), 'click', function()
				{
					oMSL.expandText(data.log_id);
				});
			}
			else
			{
				BX('post_more_limiter').style.display = 'none';
			}
		}

		if (data.bShowFull === "YES")
		{
			BX('post_block_check_cont').className = "post-item-post-block-full";
			if (BX('post_more_block_' + data.log_id))
			{
				BX('post_more_block_' + data.log_id).style.display = "none";
			}
			if (BX('post_block_check_more_' + data.log_id))
			{
				BX('post_block_check_more_' + data.log_id).style.display = "none";
			}

			BX('post_more_limiter').style.display = 'none';
			BitrixMobile.LazyLoad.showImages(false); // when redraw detail 2
		}
		else
		{
			BX('post_block_check_cont').className = (data.bIsPhoto == "YES" ? "post-item-post-img-block" : "post-item-post-block");
		}

		if (data.bIsImportant == "YES")
		{
			BX.addClass(BX('post_block_check_cont'), "lenta-info-block");
			BX.addClass(BX('post_block_check_cont'), "info-block-important");
		}
		else
		{
			BX.removeClass(BX('post_block_check_cont'), "lenta-info-block");
			BX.removeClass(BX('post_block_check_cont'), "info-block-important");
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

	if (postBlock)
	{
		postBlock.innerHTML = '';
		BitrixMobile.LazyLoad.clearImages();

		if (typeof data.detailText != 'undefined')
		{
			postBlock.innerHTML = data.detailText;
			postScripts += oMSL.parseAndExecCode(data.detailText, 0, false, true);
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
		__MSLSendErrorEval(postScripts);
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
			typeof data.topText !== 'undefined'
			&& BX('post_item_top_' + parseInt(data.logID))
		)
		{
			BX('post_item_top_' + parseInt(data.logID)).innerHTML = data.topText;
			postScripts += oMSL.parseAndExecCode(data.topText, 0, false, true);
		}

		setTimeout(function()
		{
			__MSLSendErrorEval(postScripts);
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
		oMSL.showNotifier(oMSL.counterValue);
	}
	else
	{
		this.counterTimeout = setTimeout(function()
		{
			if (zeroTime > oMSL.iframeCacheTs) // counter is null but cache is too old
			{
				oMSL.refreshNeeded = true;
			}

			if (
				oMSL.refreshNeeded
				&& !oMSL.refreshStarted
			)
			{
				oMSL.showRefreshNeededNotifier();
			}
			else
			{
				oMSL.hideNotifier();
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

	commentType = (typeof params.commentType == 'undefined' ? 'log' : params.commentType);
	nodeId = (typeof params.nodeId == 'undefined' ? "" : params.nodeId);

	oMSL.showCommentWait({
		nodeId: nodeId,
		status: true
	});
	BXMobileApp.UI.Page.TextPanel.clear();

	var BMAjaxWrapper = false;

	if (commentType == 'blog')
	{
		BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'html',
			'method': 'GET',
			'url': commentVarURL + '&sessid=' + BX.bitrix_sessid() + '&delete_comment_id=' + params.commentId,
			'data': '',
			'callback': function(response)
			{
				bLockCommentSending = false;
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
				bLockCommentSending = false;
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
			'sessid': BX.bitrix_sessid(),
			'site': commentVarSiteID,
			'lang': commentVarLanguageID,
			'log_id': parseInt(oMSL.logId),
			'delete_id': params.commentId,
			'action': 'delete_comment',
			'mobile_action': 'delete_comment'
		};

		BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
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

BitrixMSL.prototype.drawPullComment = function(params)
{
	this.showNewPullComment(params, 'entry-comment-' + params["FULL_ID"].join('-'));
	BXMobileApp.onCustomEvent('onLogEntryRead', {
		log_id: tmp_log_id,
		ts: params["POST_TIMESTAMP"],
		bPull: true
	}, true); // just for TS
};

BitrixMSL.prototype.showNewPullComment = function(params, nodeId)
{
	if(!BX(nodeId))
	{
		var postCard  = document.body;

		params["POST_TIMESTAMP"] = parseInt(params["POST_TIMESTAMP"]) + parseInt(BX.message('USER_TZ_OFFSET')) + parseInt(BX.message('SERVER_TZ_OFFSET'));
		params["POST_DATETIME_FORMATTED"] = (BX.date.format("d F Y", params["POST_TIMESTAMP"]) == BX.date.format("d F Y")
			? BX.date.format((BX.message("MSLDateTimeFormat").indexOf('a') >= 0 ? 'g:i a' : 'G:i'), params["POST_TIMESTAMP"], false, true)
			: BX.date.format(BX.message("MSLDateTimeFormat"), params["POST_TIMESTAMP"], false, true)
		);

		var
			UFNode = null,
			ratingNode = null;

		if (
			typeof (params["POST_MESSAGE_TEXT_MOBILE"]) != 'undefined'
			&& params["POST_MESSAGE_TEXT_MOBILE"].length > 0
			&& params["POST_MESSAGE_TEXT_MOBILE"] != 'NO'
		)
		{
			this.parseAndExecCode(params["POST_MESSAGE_TEXT_MOBILE"]);
		}

		if (
			typeof (params["AFTER_MOBILE"]) != 'undefined'
			&& params["AFTER_MOBILE"].length > 0
			&& params["AFTER_MOBILE"] != 'NO'
		)
		{
			UFNode = BX.create('DIV', {
				props: {
					className: 'post-item-attached-file-wrap',
					id: nodeId + '-files'
				},
				html: params["AFTER_MOBILE"]
			});

			this.parseAndExecCode(params["AFTER_MOBILE"]);
		}

		if (
			typeof (params["BEFORE_ACTIONS_MOBILE"]) != 'undefined'
			&& params["BEFORE_ACTIONS_MOBILE"].length > 0
			&& params["BEFORE_ACTIONS_MOBILE"] != 'NO'
		)
		{
			ratingNode = BX.create('SPAN', {
				html: params["BEFORE_ACTIONS_MOBILE"]
			});

			this.parseAndExecCode(params["BEFORE_ACTIONS_MOBILE"]);
		}

		var replyNode = oMSL.buildCommentReplyNode({
			EVENT: {
				USER_ID: params.AUTHOR.ID
			},
			CREATED_BY: {
				FORMATTED: params.AUTHOR.NAME
			}
		});

		BX('post-comment-last-after').parentNode.insertBefore(BX.create('DIV', {
			attrs: {
				id: nodeId
			},
			props: {
				className: 'post-comment-block'
			},
			children: [
				BX.create('DIV', {
					props: {
						className: 'post-user-wrap'
					},
					children: [
						BX.create('DIV', {
							props: {
								className: 'avatar'
							},
							style: {
								backgroundImage: (
									typeof (params["AUTHOR"]["AVATAR"]) != 'undefined'
									&& params["AUTHOR"]["AVATAR"].length > 0 && params["AUTHOR"]["AVATAR"] != 'NO'
										? "url('" + params["AUTHOR"]["AVATAR"] + "')"
										: ""
								)
							},
							children: []
						}),
						BX.create('DIV', {
							props: {
								className: 'post-comment-cont'
							},
							children: [
								BX.create('A', {
									attrs: {
										href: oMSL.replaceUserPath(params["AUTHOR"]["URL"])
									},
									props: {
										className: 'post-comment-author'
									},
									html: params["AUTHOR"]["NAME"]
								}),
								BX.create('DIV', {
									props: {
										className: 'post-comment-time'
									},
									html: params["POST_DATETIME_FORMATTED"]
								})
							]
						})
					]
				}),
				BX.create('DIV', {
					props: {
						className: 'post-comment-text',
						id: nodeId + '-text'
					},
					html: (
						typeof params["POST_MESSAGE_TEXT_MOBILE"] != 'undefined' && params["POST_MESSAGE_TEXT_MOBILE"].length > 0  && params["POST_MESSAGE_TEXT_MOBILE"] != 'NO'
							? oMSL.replaceUserPath(params["POST_MESSAGE_TEXT_MOBILE"])
							: params["POST_MESSAGE_TEXT"]
					)
				}),
				UFNode,
				ratingNode,
				replyNode
			]
		}), BX('post-comment-last-after'));

		var maxScrollTop = postCard.scrollHeight - postCard.offsetHeight;

		setTimeout(function() {
			if (
				postCard.scrollTop >= (maxScrollTop - 120)
				&& postCard
			)
			{
				BitrixAnimation.animate({
					duration : 1000,
					start : { scroll : postCard.scrollTop },
					finish : { scroll : postCard.scrollTop + 140 },
					transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
					step : function(state)
					{
						postCard.scrollTop = state.scroll;
					},
					complete : function(){}
				});
			}
			BX.addClass(BX(nodeId), "post-comment-new-transition");
		}, 0);

		setTimeout(function() {
			BitrixMobile.LazyLoad.showImages();

			BX.MSL.viewImageBind(nodeId + '-text', { tag: 'IMG', attr: 'data-bx-image' });
			if (UFNode != null)
			{
				BX.MSL.viewImageBind(nodeId + '-files', { tag: 'IMG', attr: 'data-bx-image' });
			}
		}, 500);

		// increment comment counters both in post card and LiveFeed

		var log_id = BX.message('MSLLogId');
		var old_value = 0;
		var val = 0;

		if (
			BX('informer_comments_' + log_id)
			&& !BX('informer_comments_new_' + log_id)
		)
		{
			old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
			val = old_value + 1;
			BX('informer_comments_' + log_id).innerHTML = val;
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

		if (
			typeof (tmp_log_id) != 'undefined'
			&& parseInt(tmp_log_id) > 0
		)
		{
			BXMobileApp.onCustomEvent('onLogEntryCommentAdd', { log_id: tmp_log_id }, true);
		}
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
				__MSLSendErrorEval(parsedScripts);
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

BitrixMSL.prototype.showTextPanelMenu = function()
{
	var action = new BXMobileApp.UI.ActionSheet({
			buttons: [
				{
					title: BX.message('MSLTextPanelMenuPhoto'),
					callback: function()
					{
						app.takePhoto({
							source: 1,
							correctOrientation: true,
							targetWidth: 1000,
							targetHeight: 1000,
							callback: function(fileURI)
							{
								oMSL.uploadCommentFile(fileURI);
							}
						});
					}
				},
				{
					title: BX.message('MSLTextPanelMenuGallery'),
					callback: function()
					{
						app.takePhoto({
							targetWidth: 1000,
							targetHeight: 1000,
							callback: function(fileURI)
							{
								oMSL.uploadCommentFile(fileURI);
							}
						});
					}
				}
			]
		},
		"textPanelSheet"
	);
	action.show();
};

BitrixMSL.prototype.InitDetail = function(params)
{
	this.commentLoadingFilesStack = [];
	this.commentsType = (typeof (params.commentsType) != 'undefined' && params.commentsType == 'blog' ? 'blog' : 'log');
	this.entityXMLId = (typeof (params.entityXMLId) != 'undefined' ? params.entityXMLId : '');
	this.bFollow = !(typeof (params.bFollow) != 'undefined' && !params.bFollow);
	this.bUseTasks = (typeof (params.bUseTasks) != 'undefined' && params.bUseTasks);
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
	this.showPageMenu('detail');
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
		oMSL.incrementCounters(oMSL.logId);
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
					postCard.scrollTop = state.scroll;
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

BitrixMSL.prototype.showCommentProgressBar = function(commentNode)
{
	if (
		typeof (commentNode) == 'undefined'
		|| !BX(commentNode)
	)
	{
		return;
	}

	BX.findChild(commentNode, { className: 'post-comment-text' }, true, false).appendChild(BX.create('DIV', {
		props: {
			id: commentNode.id + '-progressbar-cont',
			className: 'comment-loading'
		},
		style: {
			display: 'none'
		},
		children: [
			BX.create('DIV', {
				props: {
					id: commentNode.id + '-progressbar-label',
					className: 'newpost-progress-label'
				}
			}),
			BX.create('DIV', {
				props: {
					id: commentNode.id + '-progressbar-ind',
					className: 'newpost-progress-indicator'
				}
			})
		]
	}));
	BX(commentNode.id + '-progressbar-cont').style.display = 'block';
	var loading_id = Math.floor(Math.random() * 100000) + 1;
	oMSL.commentLoadingFilesStack[oMSL.commentLoadingFilesStack.length] = loading_id;
	clearInterval(oMSL.commentProgressBarAnimation);
	oMSL.commentProgressBarAnimation = BitrixAnimation.animate({
		duration : oMSL.commentLoadingFilesStack.length * 5000,
		start: {
			width: parseInt(oMSL.commentProgressBarState / oMSL.commentLoadingFilesStack.length) + 10
		},
		finish: {
			width: 90
		},
		transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.linear),
		step : function(state)
		{
			BX(commentNode.id + '-progressbar-ind').style.width = state.width + '%';
			oMSL.commentProgressBarState = state.width;
		},
		complete : function()
		{
			oMSL.commentProgressBarState = 0;
		}
	});

	return loading_id;
};

BitrixMSL.prototype.hideCommentProgressBar = function(loadingId, commentNode)
{
	if (
		typeof (commentNode) == 'undefined'
		|| !BX(commentNode)
	)
	{
		return;
	}

	var newLoadingFilesStack = [];

	for (var i = 0; i < this.commentLoadingFilesStack.length; i++)
	{
		if (this.commentLoadingFilesStack[i] != loadingId)
		{
			newLoadingFilesStack[newLoadingFilesStack.length] = this.commentLoadingFilesStack[i];
		}
	}

	this.commentLoadingFilesStack = newLoadingFilesStack;

	if (this.commentLoadingFilesStack.length == 0)
	{
		clearInterval(this.commentProgressBarAnimation);
		this.commentProgressBarState = 0;
		BX(commentNode.id + '-progressbar-ind').style.width = '100%';

		setTimeout(function() {
			if (BX(commentNode.id + '-progressbar-cont'))
			{
				BX(commentNode.id + '-progressbar-cont').style.display = 'none';
			}
		}, 2000);
	}
};

BitrixMSL.prototype.setFollow = function(params)
{
	var logId = (typeof params.logId != 'undefined' ? parseInt(params.logId) : 0);
	var pageId = (typeof params.pageId != 'undefined' ? params.pageId : false);
	var bOnlyOn = (typeof params.bOnlyOn != 'undefined' ? params.bOnlyOn : false);

	if (bOnlyOn == 'NO')
	{
		bOnlyOn = false;
	}
	var bRunEvent = (typeof params.bRunEvent != 'undefined' ? params.bRunEvent : true);
	var bAjax = (typeof params.bAjax != 'undefined' ? params.bAjax : false);

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

	if (
		followBlock
		&& (
			!BX.type.isNotEmptyString(this.detailPageId)
			|| this.detailPageId != pageId
		)
	)
	{
		var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
		var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");

		if (
			!bOnlyOn
			|| strFollowOld == "N"
		)
		{
			BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
			BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
			followBlock.setAttribute("data-follow", strFollowNew);
			if (bRunEvent)
			{
				BXMobileApp.onCustomEvent('onLogEntryFollow', {
					logId: logId,
					pageId: (BX.type.isNotEmptyString(oMSL.detailPageId) ? oMSL.detailPageId : ''),
					bOnlyOn: (bOnlyOn ? 'Y' : 'N')
				}, true);
			}

			if (
				!this.bFollowDefault
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
				this.bFollow = (strFollowNew == "Y");
				this.setFollowMenuItemName();
			}
		}
	}

	if (bAjax)
	{
		var post_data = {
			sessid: BX.bitrix_sessid(),
			site: BX.message('SITE_ID'),
			lang: BX.message('MSLLangId'),
			log_id: logId,
			follow: strFollowNew,
			action: 'change_follow',
			mobile_action: 'change_follow'
		};

		var actionUrl = BX.message('MSLSiteDir') + 'mobile/ajax.php';
		actionUrl = BX.util.add_url_param(actionUrl, {
			b24statAction: (strFollowNew == 'Y' ? 'setFollow' : 'setUnfollow'),
			b24statContext: 'mobile'
		});

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			type: 'json',
			method: 'POST',
			url: actionUrl,
			data: post_data,
			callback: BX.proxy(function(get_response_data)
			{
				if (get_response_data["SUCCESS"] != "Y")
				{
					if (followBlock)
					{
						BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
						BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
						followBlock.setAttribute("data-follow", strFollowOld);
					}

					if (BX.type.isNotEmptyString(oMSL.detailPageId))
					{
						oMSL.bFollow = (strFollowOld == "Y");
						this.setFollowMenuItemName();
					}

					if (
						!this.bFollowDefault
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

					if (parseInt(oMSL.logId) > 0)
					{
						BXMobileApp.onCustomEvent('onLogEntryFollow', {
							logId: logId,
							pageId: (BX.type.isNotEmptyString(oMSL.detailPageId) ? oMSL.detailPageId : ''),
							bOnlyOn: (bOnlyOn ? 'Y' : 'N')
						}, true);
					}
				}
			}, this),
			callback_failure: BX.proxy(function() {
				if (followBlock)
				{
					BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
					BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
					followBlock.setAttribute("data-follow", strFollowOld);
				}

				if (
					!this.bFollowDefault
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

				if (BX.type.isNotEmptyString(oMSL.detailPageId))
				{
					oMSL.bFollow = (strFollowOld == "Y");
					this.setFollowMenuItemName();
				}
			}, this)
		});
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
		oMSL.bFollowDefault = newValue;
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
			oMSL.bFollowDefault = !(post_data.value == 'Y');
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

	var actionUrl = BX.message('MSLSiteDir') + 'mobile/ajax.php';
	if (post_data.action == 'change_follow_default')
	{
		actionUrl = BX.util.add_url_param(actionUrl, {
			b24statAction: (post_data.value == 'Y' ? 'setFollowType' : 'unsetFollowType'),
			b24statContext: 'mobile'
		});
	}
	else if (post_data.action == 'change_expert_mode')
	{
		actionUrl = BX.util.add_url_param(actionUrl, {
			b24statAction: (post_data.value == 'Y' ? 'setExpertMode' : 'unsetExpertMode'),
			b24statContext: 'mobile'
		});
	}

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: actionUrl,
		data: post_data,
		callback: function(get_response_data)
		{
			app.hidePopupLoader();
			if (get_response_data["SUCCESS"] == "Y")
			{
				successCallbackFunc(post_data);
			}
			else
			{
				if (!BX.type.isNotEmptyString(oMSL.detailPageId))
				{
					failCallbackFunc(post_data);
				}
			}

		},
		callback_failure: function()
		{
			app.hidePopupLoader();
			if (!BX.type.isNotEmptyString(oMSL.detailPageId))
			{
				failCallbackFunc(post_data);
			}
		}
	});
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
		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
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
						logId: oMSL.log_id,
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

BitrixMSL.prototype.uploadCommentFile = function(fileURI)
{
	var oPreviewComment = oMSL.showPreviewComment('');
	var loadingId = oMSL.showCommentProgressBar(oPreviewComment);

	function win(r)
	{
		var arResult = JSON.parse(r.response)

		if (
			typeof arResult.SUCCESS == 'undefined'
			|| arResult.SUCCESS != 'Y'
		)
		{
			fail_1try();
		}
		else
		{
			oMSL.hideCommentProgressBar(loadingId, oPreviewComment);
			if (
				oMSL.commentsType == 'blog'
				&& (
					typeof arResult.BLOG_COMMENT_ID != 'undefined'
					|| parseInt(arResult.BLOG_COMMENT_ID) > 0
				)
			)
			{
				oMSL.getComment({
					oPreviewComment: oPreviewComment,
					commentId: (oMSL.commentsType == 'blog' ? arResult.BLOG_COMMENT_ID : false),
					entryId: (oMSL.commentsType == 'blog' ? commentVarBlogPostID : false),
					commentType: oMSL.commentsType
				});
			}
			else if	(
				oMSL.commentsType == 'log'
				&& (
					typeof arResult.commentID != 'undefined'
					|| parseInt(arResult.commentID) > 0
				)
			)
			{
				oMSL.showNewComment({
					arComment: arResult["arCommentFormatted"],
					bIncrementCounters: true,
					oPreviewComment: oPreviewComment,
					bShowImages: true,
					bClearForm: false
				});
				__MSLDetailMoveBottom();

				oMSL.setFollow({
					logId: oMSL.logId,
					bOnlyOn: true
				});
			}
		}
	}

	function fail_1try(error)
	{
		app.BasicAuth({
			'success': function(auth_data)
			{
				options.params.sessid = auth_data.sessid_md5;
				ft.upload(fileURI, BX.message('MSLAjaxInterfaceFullURI'), win, fail_2try, options);
			},
			'failture': function()
			{
				oMSL.hideCommentProgressBar(loadingId, oPreviewComment);

				oMSL.showCommentAlert({
					nodeId: oPreviewComment,
					action: 'upload_comment_photo',
					commentType: 'blog',
					callback: function() {
						oMSL.uploadCommentFile(fileURI);
					}
				});
			}
		});
	}

	function fail_2try(error)
	{
		oMSL.hideCommentProgressBar(loadingId, oPreviewComment);

		oMSL.showCommentAlert({
			nodeId: oPreviewComment,
			action: 'upload_comment_photo',
			commentType: 'blog',
			callback: function() {
				oMSL.uploadCommentFile(fileURI);
			}
		});
	}

	var options = new FileUploadOptions();
	options.fileKey = 'file';
	options.fileName = fileURI.substr(fileURI.lastIndexOf('/') + 1);
	options.mimeType = "image/jpeg";
	options.params = {
		mobile_action: 'file_upload_' + oMSL.commentsType,
		action: 'file_comment_upload',
		commentsType: oMSL.commentsType,
		sessid: BX.bitrix_sessid(),
		site: BX.message("SITE_ID"),
		lang: BX.message("MSLLangId"),
		nt: BX.message('MSLNameTemplate'),
		sl: BX.message('MSLShowLogin'),
		p_user: BX.message('MSLPathToUser'),
		p_bpost: commentVarPathToBlogPost,
		as: commentVarAvatarSize,
		dtf: commentVarDateTimeFormat,
		sr: BX.message('MSLShowRating')
	};

	if (oMSL.commentsType == 'blog')
	{
		options.params.post_id = commentVarBlogPostID;
	}
	else
	{
		options.params.log_id = oMSL.logId;
	}

	options.chunkedMode = false;

	var ft = new FileTransfer();
	ft.upload(fileURI, BX.message('MSLAjaxInterfaceFullURI'), win, fail_1try, options);
};

BitrixMSL.prototype.sendCommentWriting = function(xmlId, text)
{
	xmlId = (typeof (xmlId) != 'undefined' ? xmlId : '');
	text = (typeof text != 'undefined' ? text : '');

	if (xmlId.length <= 0)
	{
		return;
	}

	if (this.sendCommentWritingList[xmlId])
	{
		return;
	}

	clearTimeout(this.sendCommentWritingListTimeout[xmlId]);
	this.sendCommentWritingList[xmlId] = true;

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: BX.message('MSLSiteDir') + 'mobile/ajax.php',
		data: {
			sessid: BX.bitrix_sessid(),
			site: BX.message('SITE_ID'),
			lang: BX.message('MSLLangId'),
			nt: BX.message('MSLNameTemplate'),
			sl: BX.message('MSLShowLogin'),
			as: commentVarAvatarSize,
			action: 'send_comment_writing',
			mobile_action: 'send_comment_writing',
			ENTITY_XML_ID: xmlId
		},
		callback: function(response_data) {},
		callback_failure: function(response_data) {}
	});

	this.sendCommentWritingListTimeout[xmlId] = setTimeout(BX.delegate(function()
	{
		this.endCommentWriting(xmlId);
	}, this), 30000);
};

BitrixMSL.prototype.endCommentWriting = function(xmlId)
{
	xmlId = (typeof (xmlId) != 'undefined' ? xmlId : '');

	if (xmlId.length <= 0)
	{
		return;
	}

	clearTimeout(this.sendCommentWritingListTimeout[xmlId]);
	this.sendCommentWritingList[xmlId] = false;
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
				document.body.scrollTop = firstNewComment.offsetTop;
			}
			else
			{
				var firstComment = BX.findChild(BX('post-comments-wrap'), { className : 'post-comment-block' }, true);
				document.body.scrollTop = (firstComment ? firstComment.offsetTop : 0);
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
	var menuItems = [];

	if (data.entry_type == 'blog')
	{
		var key = null;

		var arSelectedDestinations = {
			a_users: [],
			b_groups: []
		};

		if (
			false
			&& typeof data.destinations != 'undefined'
		)
		{
			if (typeof data.destinations.U != 'undefined')
			{
				for (key in data.destinations.U)
				{
					if (data.destinations.U.hasOwnProperty(key))
					{
						var objUser = data.destinations.U[key];
						if (typeof objUser.ID != 'undefined')
						{
							arSelectedDestinations.a_users.push(parseInt(objUser.ID) > 0 ? parseInt(objUser.ID) : 0);
						}
					}
				}
			}

			if (typeof data.destinations.SG != 'undefined')
			{
				for (key in data.destinations.SG)
				{
					if (
						data.destinations.SG.hasOwnProperty(key)
						&& parseInt(key) > 0
					)
					{
						arSelectedDestinations.b_groups.push(parseInt(key));
					}
				}
			}
		}

		if (
			arSelectedDestinations.a_users.length > 0
			|| arSelectedDestinations.b_groups.length > 0
		)
		{
			menuItems.push({
				name: BX.message('MSLSharePost'),
				action: function()
				{
					app.openTable({
						callback: function() {
							oMSL.shareBlogPost();
						},
						url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=' + (BX.message('MSLIsExtranetSite') == 'Y' ? 'get_group_list' : 'get_usergroup_list'),
						markmode: true,
						multiple: true,
						return_full_mode: true,
						user_all: true,
						showtitle: true,
						modal: true,
						selected: arSelectedDestinations,
						alphabet_index: true,
						okname: BX.message('MSLShareTableOk'),
						cancelname: BX.message('MSLShareTableCancel'),
						outsection: (BX.message('MSLIsDenyToAll') != 'Y')
					});
				},
				arrowFlag: false,
				icon: "add"
			});
		}

		if (
			typeof data.post_perm != 'undefined'
			&& data.post_perm == 'W'
		)
		{
			menuItems.push({
				name: BX.message('MSLEditPost'),
				action: function() {
					oMSL.editBlogPost({
						feed_id: data.feed_id,
						post_id: parseInt(data.post_id)
					});
				},
				arrowFlag: false,
				icon: 'edit',
				feature: 'edit'
			});

			menuItems.push({
				name: BX.message('MSLDeletePost'),
				action: function() {
					oMSL.deleteBlogPost({
						'post_id': parseInt(data.post_id)
					});
				},
				arrowFlag: false,
				icon: "delete"
			});
		}
	}

	if (
		oMSL.bUseFollow
		&& (
			typeof data.read_only == 'undefined'
			|| data.read_only != 'Y'
		)
	)
	{
		menuItems.push({
			name: (oMSL.bFollow ? BX.message('MSLFollowY') : BX.message('MSLFollowN')),
			image: "/bitrix/templates/mobile_app/images/lenta/menu/eye.png",
			action: function()
			{
				oMSL.setFollow({
					logId: oMSL.logId,
					pageId: oMSL.pageId,
					bOnlyOn: false,
					bAjax: true,
					bRunEvent: true
				});
			},
			arrowFlag: false,
			feature: 'follow'
		});
	}

	menuItems.push({
		name: BX.message('MSLRefreshComments'),
		image: "/bitrix/templates/mobile_app/images/lenta/menu/n_refresh.png",
		action: function() {
			if (oMSL.bDetailEmptyPage)
			{
				oMSL.getComments({
					ts: oMSL.iDetailTs,
					bPullDown: true,
					obFocus: {
						form: false
					}
				});
			}
			else
			{
				document.location.reload(true);
			}
		},
		arrowFlag: false
	});

	if (
		oMSL.bUseTasks
		&& BX.type.isNotEmptyString(data.post_content_type_id)
		&& BX.type.isNumber(data.post_content_id) && data.post_content_id > 0
	)
	{
		menuItems.push({
			name: BX.message('MSLCreateTask'),
			image: "/bitrix/templates/mobile_app/images/lenta/menu/n_check.png",
			action: function()
			{
				oMSL.createTask({
					entityType: data.post_content_type_id,
					entityId: parseInt(data.post_content_id)
				});
				return false;
			},
			arrowFlag: false
		});
	}

	return menuItems;
};

BitrixMSL.prototype.showPageMenu = function(type)
{
	type = (type == 'detail' ? 'detail' : 'list');
	var menuItems = (type == 'detail' ? this.detailPageMenuItems : this.listPageMenuItems);
	var title = (type == 'detail'
		? (BX.message("MSLLogEntryTitle") != null ? BX.message("MSLLogEntryTitle") : '')
		: (BX.message("MSLLogTitle") != null ? BX.message("MSLLogTitle") : '')
	);

	if (menuItems.length > 0)
	{
		app.menuCreate({
			items: menuItems
		});

		BXMobileApp.UI.Page.TopBar.title.setText(title);
		BXMobileApp.UI.Page.TopBar.title.setCallback(function ()
		{
			app.menuShow();
		});
		BXMobileApp.UI.Page.TopBar.title.show();
	}
	else
	{
		BXMobileApp.UI.Page.TopBar.title.setText(title);
		BXMobileApp.UI.Page.TopBar.title.setCallback("");
		BXMobileApp.UI.Page.TopBar.title.show();
	}
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
			menuItem.name = (oMSL.bFollow ? BX.message('MSLFollowY') : BX.message('MSLFollowN'));
			this.detailPageMenuItems[i] = menuItem;
			this.showPageMenu('detail');
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
			menuItem.name = (oMSL.bFollowDefault ? BX.message('MSLMenuItemFollowDefaultY') : BX.message('MSLMenuItemFollowDefaultN'));
			this.listPageMenuItems[i] = menuItem;
			this.showPageMenu('list');
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
			this.showPageMenu('list');
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
	var checkBlock = (
		typeof id == 'undefined'
		|| id == null
		|| !BX('post_block_check_cont_' + id)
			? BX('post_block_check_cont')
			: BX('post_block_check_cont_' + id)
	);

	if (checkBlock)
	{
		if (BX.hasClass(checkBlock, "post-item-post-block"))
		{
			BX.addClass(checkBlock, 'post-item-post-block-full');
			BX.removeClass(checkBlock, 'post-item-post-block');
		}
		else if (BX.hasClass(checkBlock, "lenta-info-block-wrapp"))
		{
			BX.addClass(checkBlock, 'lenta-info-block-wrapp-full');
			BX.removeClass(checkBlock, 'lenta-info-block-wrapp');
		}

		if (BX('post_more_block_' + id))
		{
			BX('post_more_block_' + id).style.display = "none";
		}
		else if (BX('post_more_block'))
		{
			BX('post_more_block').style.display = "none";
		}

		if (BX('post_more_limiter_' + id))
		{
			BX('post_more_limiter_' + id).style.display = "none";
		}
		else if (BX('post_more_limiter'))
		{
			BX('post_more_limiter').style.display = "none";
		}

		var arImages = BX.findChildren(checkBlock, { tagName: "img" }, true);
		var src = null;

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

	var newPostId = 'new_post_ajax_' + Math.random();
	var newPostNode = BX.create('DIV', {
		props: {
			id: newPostId
		},
		html: postResponseData.text
	});
	BX('blog-post-first-after').parentNode.insertBefore(newPostNode, BX('blog-post-first-after').nextSibling);

	if (
		logId > 0
		&& BX('post_block_check_cont_' + logId)
		&& BX('post_item_top_' + logId)
	)
	{
		BXMobileApp.onCustomEvent('onEditedPostInserted', {
			detailText: BX('post_block_check_cont_' + logId).innerHTML,
			topText: BX('post_item_top_' + logId).innerHTML,
			nodeID: newPostId,
			logID: logId
		}, true);
	}
	BX.cleanNode(newPostNode, true);
	BitrixMobile.LazyLoad.showImages();
};

BitrixMSL.prototype.onMPFSent = function(post_data, groupID)
{
	var postId = (
		typeof post_data.data.post_id != 'undefined'
			? parseInt(post_data.data.post_id)
			: 0
	);

	if (
		post_data.LiveFeedID != window.LiveFeedID
		&& postId <= 0
	)
	{
		return;
	}

	window.scrollTo(0,0);

	if (postId <= 0)
	{
		app.showPopupLoader({text:""});
	}

	post_data.data.response_type = 'json';

	var url = (typeof post_data.ajaxUrl != 'undefined' ? post_data.ajaxUrl : oMSL.curUrl);
	url = BX.util.add_url_param(url, {
		b24statAction: (postId > 0 ? "editLogEntry" : "addLogEntry"),
		b24statContext: "mobile"
	});

	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		type: 'json',
		method: 'POST',
		url: url,
		data: post_data.data,
		processData : true,
		callback: function(post_response_data)
		{
			app.hidePopupLoader();

			var selectedDestinations = {
				a_users: [],
				b_groups: []
			};

			if (
				postId <= 0
				&& (
					typeof (post_response_data.error) == "undefined"
					|| post_response_data.error.length <= 0
				)
				&& post_response_data.text.length > 0
			) // add
			{
				BX.localStorage.set('mobileLivefeedRefreshTS',  Math.round(new Date().getTime() / 1000), 86400*30);
				oMSL.refreshNeeded = false;
				oMSL.counterValue = 0;
				oMSL.hideRefreshNeededNotifier();
				oMSL.hideNotifier();

				BX.MSL.DBDelete(groupID);

				oMSL.clearPostFormDestination(selectedDestinations, groupID);
				oMSL.setPostFormParams({
					selectedRecipients: selectedDestinations
				});
				oMSL.setPostFormParams({
					messageText: ''
				});

				app.showPopupLoader({text:""});
				__MSLRefresh(true);
			}
			else if (
				postId > 0
				&& (
					typeof (post_response_data.error) == "undefined"
					|| post_response_data.error.length <= 0
				)
			) // edit
			{
				BXMobileApp.onCustomEvent('onAfterEdit', {
					postResponseData: post_response_data,
					postData: post_data
				}, true);
			}
			else
			{
				if (postId <= 0) // only when add
				{
					BX.MSL.DBSave(post_data.data, groupID);

					oMSL.buildSelectedDestinations(
						post_data.data,
						selectedDestinations
					);

					oMSL.setPostFormParams({
						selectedRecipients: selectedDestinations
					});

					oMSL.setPostFormParams({
						messageText: post_data.data.POST_MESSAGE
					});
				}

				oMSL.showPostError({
					errorText: post_response_data.error,
					feedId: window.LiveFeedID
				});
			}
		},
		callback_failure: function()
		{
			app.hidePopupLoader();

			if (postId <= 0) // only when add
			{
				BX.MSL.DBSave(post_data.data, groupID);

				var selectedDestinations = {
					a_users: [],
					b_groups: []
				};

				oMSL.buildSelectedDestinations(
					post_data.data,
					selectedDestinations
				);

				oMSL.setPostFormParams({
					selectedRecipients: selectedDestinations
				});

				oMSL.setPostFormParams({
					messageText: post_data.data.POST_MESSAGE
				});
			}

			oMSL.showPostError({
				errorText: '',
				feedId: window.LiveFeedID
			});

			BXMobileApp.onCustomEvent('onEditedPostFailed', {}, true);
		}
	});
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

BitrixMSL.prototype.onLogEntryImpPostRead = function(params)
{
	if (
		typeof params == 'undefined'
		|| typeof params.node == 'undefined'
		|| !BX(params.node)
	)
	{
		return;
	}

	oMSL.setLogEntryImpPostRead({
		node: BX(params.node),
		value: true
	});
};

BitrixMSL.prototype.setLogEntryImpPostRead = function(params)
{
	if (
		typeof params == 'undefined'
		|| typeof params.node == 'undefined'
		|| !BX(params.node)
	)
	{
		return;
	}

	var node = BX(params.node);
	var value = !!params.value;

	if (value)
	{
		node.checked = true;
		node.setAttribute("done", "Y");
		BX.unbindAll(node);
	}
	else
	{
		node.checked = false;
		delete node.checked;
		node.removeAttribute("done");
	}
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
	if (BX.hasClass(img.parentNode, "post-item-attached-img-block"))
	{
		//Attached post image
		return img.parentNode.parentNode.offsetTop < 315;
	}
	else
	{
		//Inline post image
		return img.offsetTop < 315;
	}
};

BitrixMSL.prototype.isPostFull = function()
{
	var checkBlock = (
		BX("post_block_check_cont_" + this.logId)
			? BX("post_block_check_cont_" + this.logId)
			: BX("post_block_check_cont", true)
	);

	return (
		BX.hasClass(checkBlock, "post-item-post-block-full")
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

	for (var logId in this.arBlockToCheck)
	{
		nodeToCheckId = this.arBlockToCheck[logId];
		if (
			BX(nodeToCheckId.more_overlay_id)
			&& BX(nodeToCheckId.text_block_id)
		)
		{
			blockHeight = BX(nodeToCheckId.text_block_id).offsetHeight;
			if (BX(nodeToCheckId.title_block_id))
			{
				blockHeight += BX(nodeToCheckId.title_block_id).offsetHeight
			}
			if (BX(nodeToCheckId.files_block_id))
			{
				blockHeight += BX(nodeToCheckId.files_block_id).offsetHeight
			}

			if (blockHeight >= 320)
			{
				BX(nodeToCheckId.more_overlay_id).style.display = "block";
				if (BX(nodeToCheckId.lenta_item_id))
				{
					BX.removeClass(BX(nodeToCheckId.lenta_item_id), "post-without-informers");
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.display = "block";
				}
			}
			else
			{
				if (BX(nodeToCheckId.more_overlay_id))
				{
					BX(nodeToCheckId.more_overlay_id).style.display = "none";
				}

				if (BX(nodeToCheckId.more_button_id))
				{
					BX(nodeToCheckId.more_button_id).style.display = "none";
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

	var maxScroll = (oMSL.windowSize.scrollHeight - oMSL.windowSize.innerHeight - 100); // (this.keyboardShown ? 500 : 300)

	oMSL.showScrollButtonBottom = !(
		((oMSL.windowSize.scrollHeight - oMSL.windowSize.innerHeight) <= 0) // short page
		|| (
			document.body.scrollTop >= maxScroll // too much low
			&& (
				document.body.scrollTop > 0 // refresh patch
				|| maxScroll > 0
			)
		)
	);

	oMSL.showScrollButtonTop = (document.body.scrollTop > 200);

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

	var finishValue = null;

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
		start : { scroll : document.body.scrollTop },
		finish : { scroll : finishValue },
		transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
		step : function(state)
		{
			document.body.scrollTop = state.scroll;
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

	var BMAjaxWrapper = new MobileAjaxWrapper;
	oMSL.emptyCommentsXhr = BMAjaxWrapper.Wrap({
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
				app.pullDownLoadingStop();
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
				oMSL.showPageMenu('detail');
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
			}
			else
			{
				if (!bPullDown)
				{
					oMSL.showEmptyCommentsBlockWaiter(commentsWrap, false);
				}
				oMSL.showEmptyCommentsBlockFailed(commentsWrap, ts, bPullDown, bMoveBottom, get_data);
			}
		},
		callback_failure: function()
		{
			var commentsWrap = BX('post-comments-wrap');
			if (bPullDown)
			{
				app.pullDownLoadingStop();
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

	BX.ajax({
		url: BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') + 'mobile/' : '/mobile/',
		method: 'POST',
		dataType: 'json',
		data: {
			sessid : BX.bitrix_sessid(),
			site : BX.message('SITE_ID'),
			ENTITY_TYPE : params.entityType,
			ENTITY_ID : params.entityId,
			action : 'get_raw_data',
			mobile_action : 'get_raw_data',
			params: {
				getSonetGroupAvailableList: true,
				getLivefeedUrl: true,
				checkParams: {
					feature: 'tasks',
					operation: 'create_tasks'
				}
			}
		},
		onsuccess: BX.proxy(function(data)
		{
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
				var taskDescription = oMSL.formatTaskDescription(data.DESCRIPTION, data.LIVEFEED_URL, params.entityType, (BX.type.isNotEmptyString(data.SUFFIX) ? data.SUFFIX : ''));
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
								oMSL.createTaskSetContentSuccess(data.RESULT.DATA.ID);

								BX.ajax({
									url: BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') + 'mobile/' : '/mobile/',
									method: 'POST',
									dataType: 'json',
									data: {
										sessid : BX.bitrix_sessid(),
										POST_ENTITY_TYPE : (BX.type.isNotEmptyString(params.postEntityType) ? params.postEntityType : params.entityType),
										ENTITY_TYPE : params.entityType,
										ENTITY_ID : params.entityId,
										TASK_ID : data.RESULT.DATA.ID,
										action : 'create_task_comment',
										mobile_action : 'create_task_comment'
									}
								});
							}
							else
							{
								oMSL.createTaskSetContentFailure(errors.getMessages());
							}
                        }, this)
					}
				).execute();
			}
			else
			{
				app.hidePopupLoader();
				oMSL.createTaskSetContentFailure([
					BX.message('MSLCreateTaskErrorGetData')
				]);
			}
		}, this),
		onfailure: function(data)
		{
			app.hidePopupLoader();
			oMSL.createTaskSetContentFailure([
				BX.message('MSLCreateTaskErrorGetData')
			]);
		}
	});
};

BitrixMSL.prototype.createTaskSetContentSuccess = function(taskId)
{
	app.confirm({
		title: BX.message('MSLCreateTaskSuccessTitle'),
		text: BX.message('MSLCreateTaskSuccessDescription'),
		buttons: [
			BX.message('MSLCreateTaskSuccessButtonCancelTitle'),
			BX.message('MSLCreateTaskSuccessButtonOkTitle')
		],
		callback: function (buttonId) {
			if (buttonId == 2)
			{
				app.loadPageBlank({
					url: BX.message('MSLCreateTaskTaskPath').replace('#user_id#', BX.message('USER_ID')).replace('#task_id#', taskId),
					bx24ModernStyle: true
				});
			}
		}
	});
};

BitrixMSL.prototype.createTaskSetContentFailure = function(errors)
{
	app.alert({
		title: BX.message('MSLCreateTaskFailureTitle'),
		text: errors.join('. ')
	});
};

BitrixMSL.prototype.setPostFormParams = function(params)
{
	if (typeof params == 'object')
	{
		for (var key in params)
		{
			if (
				(
					key == 'selectedRecipients'
					|| key == 'messageText'
					|| key == 'messageFiles'
				)
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
	var entityType = (
		typeof params != 'undefined'
		&& typeof params.entityType != 'undefined'
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
						text: oMSL.parseMentions(data.text)
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

							var isNewPost = !(typeof postData.post_id != 'undefined' && parseInt(postData.post_id) > 0);
							if (!isNewPost)
							{
								BX.onCustomEvent('onMPFSentEditStart', {});

								oMSL.onMPFSent({
									ajaxUrl: BX.message('MSLSiteDir') + 'mobile/index.php',
									data: postData,
									LiveFeedID: null // may be feed_id
								}, 0);
							}
							else
							{
								BXMobileApp.onCustomEvent('onMPFSent', {
									data: postData,
									LiveFeedID: window.LiveFeedID
								}, true, isNewPost);
							}
						}
						else if (entityType == 'comment')
						{
							BXMobileApp.onCustomEvent('onMPFCommentSent', {
								data: postData,
								detailPageId: data.extraData.commentType + '_' + parseInt(data.extraData.postId),
								nodeId: data.extraData.nodeId,
								ufCode: ufCode
							}, true);
						}
					}) ;
				}
			},
			name: BX.message('MSLPostFormSend')
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
		outsection: (BX.message('MSLIsDenyToAll') != 'Y' ? "YES" : "NO"),
		okname: BX.message('MSLPostFormTableOk'),
		cancelname: BX.message('MSLPostFormTableCancel'),
		multiple: "YES",
		alphabet_index: "YES",
		showtitle: "YES",
		user_all: "YES",
		url: BX.message('MSLSiteDir') + 'mobile/index.php?mobile_action=' + (BX.message('MSLIsExtranetSite') == 'Y' ? 'get_group_list' : 'get_usergroup_list')
	};
};

BitrixMSL.prototype.buildPostFormFiles = function(postData, attachedFiles, params)
{
	var promise = new Promise(function(resolve, reject)
	{
		ufCode = params.ufCode;
		if (typeof attachedFiles != 'undefined' && attachedFiles.length > 0)
		{
			var readedFileCount = 0;
			var fileTotal = attachedFiles.length;
			var fileCountIncriment = (function(){
				readedFileCount++;
				if(readedFileCount >= fileTotal)
				{
					this.postProgressingFiles(postData, attachedFiles, params);
					resolve();
				}
			}).bind(this);

			attachedFiles.forEach(function(fileData)
			{
				var isFileFromBitrix24Disk = (typeof fileData["VALUE"] != 'undefined') // Android
					|| (typeof fileData["dataAttributes"] != 'undefined' && typeof fileData["dataAttributes"]["VALUE"] != 'undefined')// iOS
				var isNewFileOnDevice = (typeof fileData["id"] == "undefined" || typeof fileData["id"] != "number");
				if(fileData["url"] && isNewFileOnDevice && !isFileFromBitrix24Disk)
				{
					BX.FileUtils.readFileByPath(fileData["url"], "readAsDataURL")
						.then(function(content)
						{
							if (typeof postData.attachedFilesRaw == 'undefined')
							{
								postData.attachedFilesRaw = [];
							}

							var file = fileData;
							file["base64"] = content.substring(content.indexOf(";base64,")+8, content.length);
							postData.attachedFilesRaw.push(file);
							fileCountIncriment();
						})
						.catch(function(error){
							console.log(error);
							fileCountIncriment();
						})
				}
				else {

					if(isFileFromBitrix24Disk)
					{
						if (typeof postData[ufCode] == 'undefined')
						{
							postData[ufCode] = [];
						}

						if (typeof fileData["VALUE"] != 'undefined')
						{
							postData[ufCode].push(fileData["VALUE"]);
						}
						else
						{
							postData[ufCode].push(fileData["dataAttributes"]["VALUE"]);
						}
					}

					fileCountIncriment();
				}
			});
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
		if (oMSL.newPostFormParams.messageFiles.hasOwnProperty(keyOld))
		{
			for (var keyNew in attachedFiles)
			{
				if (
					attachedFiles.hasOwnProperty(keyNew)
					&& (
						oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["id"]
						|| oMSL.newPostFormParams.messageFiles[keyOld]["id"] == attachedFiles[keyNew]["ID"]
					)
				)
				{
					postData[ufCode].push(oMSL.newPostFormParams.messageFiles[keyOld]["id"]);
					break;
				}
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
		key = null;

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

				postData.SPERM[prefix].push(
					id == 0
						? 'UA'
						: 'U' + id
				);

				postData.SPERM_NAME[prefix].push(name);
			}
		}
	}

	if (typeof selectedRecipients.b_groups != 'undefined')
	{
		for (key in selectedRecipients.b_groups)
		{
			if (selectedRecipients.b_groups.hasOwnProperty(key))
			{
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

				postData.SPERM[prefix].push('SG' + id);
				postData.SPERM_NAME[prefix].push(name);
			}
		}
	}

	for (key in hiddenRecipients)
	{
		if (hiddenRecipients.hasOwnProperty(key))
		{
			prefix = hiddenRecipients[key]['TYPE'];
			if (typeof postData.SPERM[prefix] == 'undefined')
			{
				postData.SPERM[prefix] = [];
			}
			postData.SPERM[prefix].push(hiddenRecipients[key]['TYPE'] + hiddenRecipients[key]['ID']);
		}
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
			if (postData.SPERM.U.hasOwnProperty(key))
			{
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
	}

	if (typeof (postData.SPERM.SG) != 'undefined')
	{
		for (key in postData.SPERM.SG)
		{
			if (postData.SPERM.SG.hasOwnProperty(key))
			{
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
			MOBILE: 'Y'
		};

		if (
			typeof params.extraData.postId != 'undefined'
			&& parseInt(params.extraData.postId) > 0
		)
		{
			oRequest.post_id = parseInt(params.extraData.postId);
			oRequest.post_user_id = parseInt(params.extraData.postAuthorId);

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

BitrixMSL.prototype.showPostError = function(params)
{
	var errorText = (typeof params.errorText != 'undefined' && params.errorText.length > 0 ? params.errorText : false);
	var notifyBar = new BXMobileApp.UI.NotificationBar(
	{
		message: (errorText ? errorText : BX.message('MOBILE_LOG_NEW_ERROR')),
		color: "#affb0000",
		textColor: "#ffffff",
		useLoader: false,
		align: "center",
		autoHideTimeout: 30000,
		hideOnTap: true,
		onTap: function(notificationParams)
		{
			app.exec('showPostForm', oMSL.showNewPostForm());
		},
		id: parseInt(Math.random() * 100000)
	});
	notifyBar.show();
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
			if (window.arAvailableGroup.hasOwnProperty(key))
			{
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
	}
	else if (BX.message('MSLIsDefaultToAll') == 'Y')
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

BitrixMSL.prototype.showNotifier = function(cnt)
{
	cnt = parseInt(cnt);
	cnt_cent = cnt % 100;

	var reminder = cnt % 10;
	var suffix = '';

	if (
		cnt_cent >= 10
		&& cnt_cent < 15
	)
	{
		suffix = 3;
	}
	else if (reminder == 0)
	{
		suffix = 3;
	}
	else if (reminder == 1)
	{
		suffix = 1;
	}
	else if (
		reminder == 2
		|| reminder == 3
		|| reminder == 4
	)
	{
		suffix = 2;
	}
	else
	{
		suffix = 3;
	}

	if (oMSL.refreshNeeded)
	{
		BX("lenta_notifier_cnt").innerHTML = (cnt ? cnt + '+' : "");
		oMSL.hideRefreshNeededNotifier();
	}
	else
	{
		BX("lenta_notifier_cnt").innerHTML = cnt || "";
	}

	BX("lenta_notifier_cnt_title").innerHTML = BX.message('MSLLogCounter' + suffix);
	BX.addClass(BX("lenta_notifier"), "lenta-notifier-shown");
};

BitrixMSL.prototype.hideNotifier = function()
{
	if (BX.hasClass(BX("lenta_notifier"), "lenta-notifier-shown"))
	{
		BX.removeClass(BX("lenta_notifier"), "lenta-notifier-shown");
	}
};

BitrixMSL.prototype.showRefreshNeededNotifier = function()
{
	BX.removeClass(BX("lenta_notifier"), "lenta-notifier-shown");

	var refreshNeededBlock = BX("lenta_notifier_2");
	if (refreshNeededBlock)
	{
		BX.addClass(refreshNeededBlock, "lenta-notifier-shown");
	}
};

BitrixMSL.prototype.hideRefreshNeededNotifier = function()
{
	var refreshNeededBlock = BX("lenta_notifier_2");
	if (refreshNeededBlock)
	{
		BX.removeClass(refreshNeededBlock, "lenta-notifier-shown");
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
	var rootNode = null;
	var lentaItemsList = null;
	var lentaItemId = null;
	var match = null;

	if (BX('post-card-wrap'))
	{
		rootNode = BX('post-card-wrap');
	}
	else if (BX('lenta_wrapper'))
	{
		rootNode = BX('lenta_wrapper');
	}

	if (rootNode)
	{
		lentaItemsList = BX.findChildren(rootNode, { className: 'post-wrap'}, true);
		if (
			lentaItemsList === null
			|| lentaItemsList.length <= 0
		)
		{
			lentaItemsList = BX.findChildren(rootNode, { className: 'lenta-item'}, true);
		}
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
							more_overlay_id: 'post_more_block_' + lentaItemId,
							more_button_id: 'post_more_limiter_' + lentaItemId
						};
						this.blocksToCheckRegisteredList.push(lentaItemId);
					}
				}
			}
		}
	}
};

BitrixMSL.prototype.onMobilePlayerError = function(player, src)
{
	if(!BX.type.isDomNode(player))
	{
		return;
	}
	if(!src)
	{
		return;
	}
	var container = player.parentNode;
	if(container)
	{
		if(BX.findChildByClassName(container, 'disk-mobile-player-error-container'))
		{
			return;
		}
	}
	else
	{
		if(BX.findChildByClassName(player, 'disk-mobile-player-error-container'))
		{
			return;
		}
	}
	var sources = player.getElementsByTagName('source');
	var sourcesLeft = sources.length;
	for(var i = 0; i < sources.length; i++)
	{
		if(sources[i].src && sources[i].src == src)
		{
			BX.remove(sources[i]);
			sourcesLeft--;
		}
	}
	if(sourcesLeft > 0)
	{
		return;
	}
	var errorContainer = BX.create('div', {props: {className: 'disk-mobile-player-error-container'}, children: [
		BX.create('div', {props: {className: 'disk-mobile-player-error-icon'}, html: ''}),
		BX.create('div', {props: {className: 'disk-mobile-player-error-message'}, html: BX.message('MSLMobilePlayerErrorMessage')})
	]});
	var downloadLink = BX.findChildByClassName(errorContainer, 'disk-mobile-player-download');
	if(downloadLink)
	{
		BX.adjust(downloadLink, {events: {click: function(){app.openDocument({url: src});}}});
	}
	if(container)
	{
		BX.hide(player);
		BX.append(errorContainer, container);
	}
	else
	{
		BX.adjust(player, {children: [errorContainer]});
	}
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

oMSL = new BitrixMSL;
window.oMSL = oMSL;

function openTaskComponentByTaskId(e, taskId, data) {
	data = data || {};
	data.selectedTab = data.selectedTab || 'taskTab';

	BXMobileApp.Events.postToComponent("taskbackground::task::action", [{taskId:taskId, data:data}], "background");

	e.preventDefault();
	e.stopPropagation();
	return false;
}