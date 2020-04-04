function __MSLOnFeedScroll()
{
	var windowScroll = BX.GetWindowScrollPos();
	if (windowScroll.scrollTop >= maxScroll)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);		

		BMAjaxWrapper.Wrap({
			'type': 'html',
			'method': 'GET',
			'url': url_next,
			'data': '',
			'processData': false,
			'callback': function(data) 
			{
				BX('lenta_wrapper').insertBefore(BX.create('DIV', {
					html: data
				}), BX('next_post_more'));

				BX.bind(window, 'scroll', __MSLOnFeedScroll);

				var obMore = BX.processHTML(data, true);
				var scripts = obMore.SCRIPT;
				BX.ajax.processScripts(scripts, true);

				var windowSize = BX.GetWindowSize();
				maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;

				setTimeout(function() { __MSLCheckNodesHeight(); }, 1000);
			},
			'callback_failure': function() { }
		});
	}
}

function __MSLOpenLogEntry(log_id, path, bMoveBottom, event)
{
	if (
		event != 'undefined' && event != null && event
		&& event.target != null && event.target != 'undefined'
	)
	{
		if (
			event.target.tagName != 'undefined'
			&& event.target.tagName.toLowerCase() == 'a'
		)
			return false;

		var anchorNode = BX.findParent(event.target, { 'tag': 'A' }, { 'tag': 'div', 'className': 'post-item-post-block' } );
		if (anchorNode)
			return false;
	}

	bMoveBottom = !!bMoveBottom;
	path += (bMoveBottom ? '&BOTTOM=Y' : '');

	if (
		arLogTs['entry_' + log_id] != 'undefined' 
		&& arLogTs['entry_' + log_id] != null
	)
		var pathTs = '&LAST_LOG_TS=' + arLogTs['entry_' + log_id];
	else
		var pathTs = '';

	if (
		arLikeRandomID['entry_' + log_id] != 'undefined' 
		&& arLikeRandomID['entry_' + log_id] != null
	)
		var pathLikeRandomID = '&LIKE_RANDOM_ID=' + arLikeRandomID['entry_' + log_id];
	else
		var pathLikeRandomID = '';

	app.openNewPage(path + pathTs + pathLikeRandomID);
}

function __MSLDetailMoveBottom()
{
	if (window.platform == "android")
	{
		window.scrollTo(0, document.documentElement.scrollHeight);
	}
	else
	{
		if (BX('post-card-wrap'))
			BX('post-card-wrap').scrollTop = BX('post-card-wrap').scrollHeight;
	}
}

function __MSLLogEntryRead(log_id, ts, bPull)
{
	bPull = !!bPull;

	if (
		arLogTs['entry_' + log_id] != 'undefined' 
		&& arLogTs['entry_' + log_id] != null
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
			var val = old_value + parseInt(BX('informer_comments_new_' + log_id).innerHTML);
			BX.remove(BX('informer_comments_new_' + log_id));
			BX.remove(BX('informer_comments_all_' + log_id));
			BX('informer_comments_' + log_id).innerHTML = val;
		}
	}
	if (BX('lenta_item_' + log_id))
		BX.removeClass(BX('lenta_item_' + log_id), 'lenta-item-new');
}

function __MSLLogEntryCommentAdd(log_id)
{
	if (
		BX('informer_comments_' + log_id)
		&& !BX('informer_comments_new_' + log_id)
	)
	{
		var old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
		var val = old_value + 1;
		BX('informer_comments_' + log_id).innerHTML = val;
	}
}

function __MSLLogEntryRatingLike(rating_id, voteAction)
{
	var ratingBlock = BX('bx-ilike-button-' + rating_id);

	if (ratingBlock)
	{
		var counterBlock = BX.findChild(ratingBlock, { className: 'post-item-inform-right-text' }, true, false);
		if (counterBlock)
		{
			var old_value = (counterBlock.innerHTML.length > 0 ? parseInt(counterBlock.innerHTML) : 0);
			if (voteAction == "plus")
			{
				var val = old_value + 1;
				BX.removeClass(ratingBlock, "post-item-inform-likes");				
				BX.addClass(ratingBlock, "post-item-inform-likes-active");
				if (
					BXRL != 'undefined' 
					&& BXRL[rating_id] != 'undefined'
				)
					BXRL[rating_id].lastVote = 'plus';
			}
			else
			{
				var val = old_value - 1;
				BX.removeClass(ratingBlock, "post-item-inform-likes-active");
				BX.addClass(ratingBlock, "post-item-inform-likes");
				if (
					BXRL != 'undefined' 
					&& BXRL[rating_id] != 'undefined'
				)
					BXRL[rating_id].lastVote = 'cancel';
			}

			counterBlock.innerHTML = val;
		}
	}
}

function __MSLLogEntryFollow(log_id)
{
	var followBlock = BX('log_entry_follow_' + log_id, true);

	if (followBlock)
	{
		var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
		var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");

		BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
		BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
		followBlock.setAttribute("data-follow", strFollowNew);
	}
}

function __MSLCheckNodesHeight()
{
	if (
		arBlockToCheck != 'undefined'
		&& arBlockToCheck != null
	)
	{
		var blockHeight = false;
		for (var i = 0; i < arBlockToCheck.length; i++)
		{
			if (
				BX(arBlockToCheck[i].more_block_id)
				&& BX(arBlockToCheck[i].text_block_id)
			)
			{
				blockHeight = BX(arBlockToCheck[i].text_block_id).offsetHeight;
				if (BX(arBlockToCheck[i].title_block_id))
					blockHeight += BX(arBlockToCheck[i].title_block_id).offsetHeight

				if (blockHeight > 180)
				{
					BX(arBlockToCheck[i].more_block_id).style.display = "block";
					if (BX(arBlockToCheck[i].more_corner_id))
						BX(arBlockToCheck[i].more_corner_id).style.display = "block";
					if (BX(arBlockToCheck[i].lenta_item_id))
						BX.removeClass(BX(arBlockToCheck[i].lenta_item_id), "post-without-informers");
				}
				else
				{
					if (BX(arBlockToCheck[i].more_overlay_id))
						BX(arBlockToCheck[i].more_overlay_id).style.display = "none";

					if (
						BX.hasClass(BX(arBlockToCheck[i].lenta_item_id), "post-without-informers")
						&& BX(arBlockToCheck[i].post_inform_wrap_id, true)
					)
						BX(arBlockToCheck[i].post_inform_wrap_id, true).style.display = "none";
				}
			}
		}
	}
}

function __MSLExpandText(id)
{
	var checkBlock = BX('post_block_check_cont_' + id);
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
			BX('post_more_block_' + id).style.display = "none";
		if (BX('post_more_corner_' + id))
			BX('post_more_corner_' + id).style.display = "none";
		if (BX('post_block_check_more_' + id))
			BX('post_block_check_more_' + id).style.display = "none";

	}
}

function __MSLShowPosts(arPosts)
{
	var ratingNode = null;
	var postNode = null;
	var anchor_id = null;	

	for (var i = 0; i < arPosts.length; i++)
	{
		anchor_id = Math.floor(Math.random()*100000) + 1;

		if (
			arComments[i]["EVENT_FORMATTED"]
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE']
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE'].length > 0
		)
			comment_message = arComments[i]['EVENT_FORMATTED']['MESSAGE'];
		else
			comment_message = arComments[i]['EVENT']['MESSAGE'];

		if (comment_message.length > 0)
		{
			if (arComments[i]["AVATAR_SRC"] && arComments[i]["AVATAR_SRC"] != 'undefined')
				var avatar = BX.create(
					'div', 
					{ 
						props: { 'className': 'avatar' }, 
						style: { 
							backgroundImage: "url('" + arComments[i]["AVATAR_SRC"] + "')",
//							backgroundPosition: "4px 4px",
							backgroundRepeat: "no-repeat",
							backgroundSize: "29px 29px"
						} 
					} 
				);
			else
				var avatar = BX.create(
					'div', { 
						props: { 'className': 'avatar' } 
					} 
				);

			if (
				arComments[i]["EVENT_FORMATTED"] != 'undefined'
				&& arComments[i]["EVENT_FORMATTED"]['DATETIME'] != 'undefined'
			)
				comment_datetime = arComments[i]["EVENT_FORMATTED"]['DATETIME'];
			else
				comment_datetime = arComments[i]["LOG_TIME_FORMAT"];

/*
			if (
				parseInt(arComments[i]["LOG_DATE_TS"]) > ts
				&& arComments[i]["EVENT"]["USER_ID"] != BX.message('sonetLCurrentUserID')
			)
				class_name_unread = ' feed-com-block-new';
			else
*/
			class_name_unread = '';

			ratingNode = null;

			if (
				arComments[i]["EVENT"]["RATING_TYPE_ID"].length > 0
				&& arComments[i]["EVENT"]["RATING_ENTITY_ID"] > 0
			)
			{
				you_like_class = (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0) ? "post-item-inform-likes-active" : "post-item-inform-likes";

				ratingNode = BX.create('div', {
					props: {
						'className': 'post-item-inform-wrap'
					},
					children: [
						BX.create('div', {
							props: {
								'id': 'bx-ilike-button-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
								'className': 'post-item-informers ' + you_like_class
							},
							children: [
								BX.create('div', {
									props: {
										'className': 'post-item-inform-left'
									}
								}),
								BX.create('div', {
									props: {
										'className': 'post-item-inform-right'
									},
									children: [
										BX.create('span', {
											props: {
												'className': 'post-item-inform-right-text'
											},
											html: arComments[i]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]
										})
									]
								})
							]
						})
					]
				});
			}

			commentNode = BX.create('div', {
				props: { 'className': 'post-comment-block' },
				children: [
					avatar,
					BX.create('div', {
						props: { 'className': 'post-comment-cont' },
						children: [
							BX.create('a', {
								props: { 'className': 'post-comment-author' },
								attrs: { 'href': arComments[i]["CREATED_BY"]["URL"] },
								html: arComments[i]["CREATED_BY"]["FORMATTED"]
							}),
							BX.create('div', {
								props: { 'className': 'post-comment-text' },
								html: comment_message
							}),
							BX.create('div', {
								props: { 'className': 'post-comment-time' },
								html: comment_datetime
							}),
							ratingNode
						]
					})
				]
			});

			BX('post-comment-hidden').appendChild(commentNode);

			if (ratingNode)
			{
				if (!window.RatingLike && top.RatingLike)
					RatingLike = top.RatingLike;

				RatingLike.Set(
					arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
					arComments[i]["EVENT"]["RATING_TYPE_ID"],
					arComments[i]["EVENT"]["RATING_ENTITY_ID"],
					(!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT']) ? 'N' : 'Y'
				);
			}
		}
	}

	BX('post-comment-hidden').style.display = "block";
	BX('post-comment-more').style.display = "none";
}

function __MSLShowComments(arComments)
{
	var ratingNode = null;
	var commentNode = null;
	var anchor_id = null;	
	var you_like_class = null;
	var you_like_text = null;
	var nodeStyle = null;

	for (var i = 0; i < arComments.length; i++)
	{
		anchor_id = Math.floor(Math.random()*100000) + 1;

		if (
			arComments[i]["EVENT_FORMATTED"]
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE']
			&& arComments[i]["EVENT_FORMATTED"]['MESSAGE'].length > 0
		)
			comment_message = arComments[i]['EVENT_FORMATTED']['MESSAGE'];
		else
			comment_message = arComments[i]['EVENT']['MESSAGE'];

		if (comment_message.length > 0)
		{
			if (arComments[i]["AVATAR_SRC"] && arComments[i]["AVATAR_SRC"] != 'undefined')
				var avatar = BX.create(
					'div', 
					{
						props: { 'className': 'avatar' }, 
						style: { 
							backgroundImage: "url('" + arComments[i]["AVATAR_SRC"] + "')",
							backgroundRepeat: "no-repeat",
							backgroundSize: "29px 29px"
						}
					}
				);
			else
				var avatar = BX.create(
					'div', {
						props: { 'className': 'avatar' } 
					}
				);

			if (
				arComments[i]["EVENT_FORMATTED"] != 'undefined'
				&& arComments[i]["EVENT_FORMATTED"]['DATETIME'] != 'undefined'
			)
				comment_datetime = arComments[i]["EVENT_FORMATTED"]['DATETIME'];
			else
				comment_datetime = arComments[i]["LOG_TIME_FORMAT"];

/*
			if (
				parseInt(arComments[i]["LOG_DATE_TS"]) > ts
				&& arComments[i]["EVENT"]["USER_ID"] != BX.message('sonetLCurrentUserID')
			)
				class_name_unread = ' feed-com-block-new';
			else
*/
			class_name_unread = '';

			ratingNode = null;

			if (
				arComments[i]["EVENT"]["RATING_TYPE_ID"].length > 0
				&& arComments[i]["EVENT"]["RATING_ENTITY_ID"] > 0
			)
			{
				you_like_class = (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0) ? "post-comment-state-active" : "post-comment-state";
				you_like_text = (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0) ? BX.message('RVCTextN') : BX.message('RVCTextY');				
				nodeStyle = (parseInt(arComments[i]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]) <= 0 ? { 'display': 'none' } : {} );

				ratingNode = BX.create('div', {
					props: {
						'className': 'post-comment-likes'
					},
					children: [
						BX.create('div', {
							props: {
								'id': 'bx-ilike-button-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
								'className': 'post-comment-likes-text ' + you_like_class
							},
							html: you_like_text
						}),
						BX.create('div', {
							props: {
								'id': 'bx-ilike-count-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
								'className': 'post-comment-likes-counter'
							},
							style: nodeStyle,
							html: arComments[i]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]
						})
					]
				});
			}

			commentNode = BX.create('div', {
				props: { 'className': 'post-comment-block' },
				children: [
					avatar,
					BX.create('div', {
						props: { 'className': 'post-comment-cont' },
						children: [
							BX.create('a', {
								props: { 'className': 'post-comment-author' },
								attrs: { 'href': arComments[i]["CREATED_BY"]["URL"] },
								html: arComments[i]["CREATED_BY"]["FORMATTED"]
							}),
							BX.create('div', {
								props: { 'className': 'post-comment-text' },
								html: comment_message
							}),
							BX.create('div', {
								props: { 'className': 'post-comment-time' },
								html: comment_datetime
							}),
							ratingNode
						]
					})
				]
			});

			BX('post-comment-hidden').appendChild(commentNode);

			if (ratingNode)
			{
				if (!window.RatingLikeComments && top.RatingLikeComments)
					RatingLikeComments = top.RatingLikeComments;

				RatingLikeComments.Set(
					arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
					arComments[i]["EVENT"]["RATING_TYPE_ID"],
					arComments[i]["EVENT"]["RATING_ENTITY_ID"],
					(!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT']) ? 'N' : 'Y'
				);
			}
		}
	}

	BX('post-comment-hidden').style.display = "block";
	BX('post-comment-more').style.display = "none";
}

function __MSLShowNewComment(arComment)
{
	if (arComment["AVATAR_SRC"] && arComment["AVATAR_SRC"] != 'undefined')
		var avatar = BX.create(
			'div', 
			{ 
				props: { 'className': 'avatar' }, 
				style: { 
					backgroundImage: "url('" + arComment["AVATAR_SRC"] + "')",
					backgroundRepeat: "no-repeat",
					backgroundSize: "29px 29px"
				} 
			} 
		);
	else
		var avatar = BX.create(
			'div', { 
				props: { 'className': 'avatar' } 
			} 
		);

	var newCommentNode = BX.create('div', {
		props: { 'className': 'post-comment-block' },
		children: [
			avatar,
			BX.create('div', {
				props: { 'className': 'post-comment-cont' },
				children: [
					BX.create('a', {
						props: { 'className': 'post-comment-author' },
						attrs: { 'href': arComment["CREATED_BY"]["URL"] },
						html: arComment["CREATED_BY"]["FORMATTED"]
					}),
					BX.create('div', {
						props: { 'className': 'post-comment-text' },
						html: arComment["MESSAGE_FORMAT"]
					}),
					BX.create('div', {
						props: { 'className': 'post-comment-time' },
						html: arComment["LOG_TIME_FORMAT"]
					})
				]
			})
		]
	});
	
	BX('post-comment-last-after').parentNode.insertBefore(newCommentNode, BX('post-comment-last-after'));
	BX('comment_send_form_comment').value = '';

	// increment comment counters both in post card and LiveFeed

	var log_id = BX.message('MSLLogId');

	if (
		BX('informer_comments_' + log_id)
		&& !BX('informer_comments_new_' + log_id)
	)
	{
		var old_value = (BX('informer_comments_' + log_id).innerHTML.length > 0 ? parseInt(BX('informer_comments_' + log_id).innerHTML) : 0);
		var val = old_value + 1;
		BX('informer_comments_' + log_id).innerHTML = val;
	}

	BXMobileApp.onCustomEvent('onLogEntryCommentAdd', { log_id: BX.message('MSLLogId') }, true);
}

function __MSLDisableSubmitButton(status)
{
	var button = BX('comment_send_button');
	var waiter = BX('comment_send_button_waiter');

	if (button)
	{
		button.disabled = status;

		if (status)
		{
			BX.addClass(button, 'send-message-button-disabled');
			if (waiter)
			{
				var arPos = BX.pos(button);
				var arPosWaiter = BX.pos(waiter);
				waiter.style.top = (arPos.top + parseInt(arPos.height/2) - 10) + 'px';
				waiter.style.left = (arPos.left + parseInt(arPos.width/2) - 10) + 'px';
				waiter.style.zIndex = 10000;
				waiter.style.display = "block";
			}
		}
		else
		{
			if (waiter)
				waiter.style.display = "none";
			BX.removeClass(button, 'send-message-button-disabled');
		}
	}
}


function __MSLGetHiddenDestinations(log_id, author_id, bindElement)
{
	var get_data = {
		'sessid': BX.message('MSLSessid'),
		'site': BX.message('MSLSiteId'),
		'lang': BX.message('MSLLangId'),
		'dlim': BX.message('MSLDestinationLimit'),
		'log_id': parseInt(log_id),
		'nt': BX.message('MSLNameTemplate'),
		'sl': BX.message('MSLShowLogin'),
		'p_user': BX.message('MSLPathToUser'),
		'action': 'get_more_destination',
		'author_id': parseInt(author_id)
	};

	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': '/bitrix/components/bitrix/mobile.socialnetwork.log/ajax.php',
		'data': get_data,
		'callback': function(get_response_data) 
		{
			if (get_response_data["arDestinations"] != 'undefined')
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
							if (typeof (arDestinations[i]['TITLE']) != 'undefined' && arDestinations[i]['TITLE'].length > 0)
							{
								cont.appendChild(BX.create('SPAN', {
									html: ',&nbsp;'
								}));

								if (typeof (arDestinations[i]['URL']) != 'undefined' && arDestinations[i]['URL'].length > 0)
									cont.appendChild(BX.create('A', {
										props: {
											className: 'post-item-destination' + (arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? ' post-item-dest-'+arDestinations[i]['STYLE'] : ''),
											'href': arDestinations[i]['URL']
										},
										html: arDestinations[i]['TITLE']
									}));
								else
									cont.appendChild(BX.create('SPAN', {
										props: {
											className: 'post-item-destination' + (arDestinations[i]['STYLE'] != 'undefined' && arDestinations[i]['STYLE'].length > 0 ? 'post-item-dest-'+arDestinations[i]['STYLE'] : '')
										},
										html: arDestinations[i]['TITLE']
									}));
							}
						}
						__MSLCheckNodesHeight();
					}
				}
			}
		},
		'callback_failure': function() { }
	});
}

function __MSLGetNewPosts()
{
}

function __MSLOnErrorClick()
{
	if (BX('blog-post-new-error'))
	{
		BX('blog-post-new-error').style.display = 'none';
		BX.unbind(BX('blog-post-new-error'), 'click', __MSLOnErrorClick);
	}
}

function __MSLPullDownInit(enable)
{
	enable = !!enable;

	if (enable)
	{
		if (!isPullDownEnabled)
		{
			app.pullDown({
				'enable': true,
				'pulltext': BX.message('MSLPullDownText1'),
				'downtext': BX.message('MSLPullDownText2'),
				'loadtext': BX.message('MSLPullDownText3'),
				'callback': function()
				{
					bReload = true;
					setTimeout(function() { app.pullDownLoadingStop(); bReload = false; }, 30000);

					if (!BMAjaxWrapper.offline)
						app.BasicAuth({
							'success': function() { if (bReload) { document.location.reload(); } },
							'failture': function() { app.pullDownLoadingStop(); }
						});
					else
						app.pullDownLoadingStop();
				}
			});
		}
		isPullDownEnabled = true;
	}
	else
	{
		app.pullDown({
			'enable': false
		});
		isPullDownEnabled = false;
	}
}

function __MSLScrollInit(enable, process_waiter)
{
	enable = !!enable;
	process_waiter = !!process_waiter

	if (enable)
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
		BX.bind(window, 'scroll', __MSLOnFeedScroll);
	}
	else
	{
		BX.unbind(window, 'scroll', __MSLOnFeedScroll);
	}
	
	if (process_waiter && BX('next_post_more'))
		BX('next_post_more').style.display = (enable ? "block" : "none");
}

function __MSLShowNotifier(cnt)
{
	BX("lenta_notifier_cnt", true).innerHTML = cnt || "";

	cnt = parseInt(cnt);
	cnt_cent = cnt % 100;

	var reminder = cnt % 10;
	var suffix = '';

	if (cnt_cent >= 10 && cnt_cent < 15)
		suffix = 3;
	else if (reminder == 0)
		suffix = 3;
	else if (reminder == 1)
		suffix = 1;
	else if (reminder == 2 || reminder == 3 || reminder == 4)
		suffix = 2;
	else
		suffix = 3;

	BX("lenta_notifier_cnt_title", true).innerHTML = BX.message('MSLLogCounter' + suffix);
	BX.addClass(BX("lenta_notifier", true), "lenta-notifier-shown");
}

function __MSLHideNotifier()
{
	BX.removeClass(BX("lenta_notifier", true), "lenta-notifier-shown");
}

function __MSLSetFollow(log_id)
{
	var followBlock = BX("log_entry_follow_" + log_id, true);

	if (followBlock)
	{
		var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
		var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");	

		BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
		BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
		followBlock.setAttribute("data-follow", strFollowNew);
	}

	var follow_data = {
		'sessid': BX.bitrix_sessid(),
		'site': BX.message('MSLSiteId'),
		'lang': BX.message('MSLLangId'),
		'log_id': parseInt(log_id),
		'follow': strFollowNew,
		'action': 'change_follow'
	};

	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': '/bitrix/components/bitrix/mobile.socialnetwork.log/ajax.php',
		'data': follow_data,
		'callback': function(get_response_data) 
		{
			if (
				get_response_data["SUCCESS"] != "Y"
				&& followBlock
			)
			{
				BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
				BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
				followBlock.setAttribute("data-follow", strFollowOld);
			}

			if (parseInt(BX.message('MSLLogId')) > 0)
				BXMobileApp.onCustomEvent('onLogEntryFollow', { log_id: log_id }, true);
		},
		'callback_failure': function() {
			if (followBlock)
			{
				BX.removeClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow' : 'post-item-follow-active'));
				BX.addClass(followBlock, (strFollowOld == "Y" ? 'post-item-follow-active' : 'post-item-follow'));
				followBlock.setAttribute("data-follow", strFollowOld);
			}
		}
	});
	return false;
}