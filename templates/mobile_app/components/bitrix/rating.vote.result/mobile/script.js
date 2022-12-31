;(function ()
{
	if(window.BX.LikeResultMobile)
	{
		return;
	}

	BX.namespace('BX.LikeResultMobile');

	BX.LikeResultMobile = function (params)
	{
		this.currentReaction = (BX.type.isNotEmptyString(params.currentReaction) ? params.currentReaction : '');
		this.pathToUserProfile = params.pathToUserProfile;
		this.entityTypeId = params.entityTypeId;
		this.entityId = params.entityId;
		this.blockScrollRequest = false;
		this.userIdList = [];
		this.tabData = null;
	};

	BX.LikeResultMobile.initPage = function ()
	{
		app.getPageParams({
			callback: function(data)
			{
				if (BX('like-result-block'))
				{
					var
						pathToUserProfile = BX('like-result-block').getAttribute('data-user-path'),
						entityTypeId = data.entityTypeId,
						entityId = parseInt(data.entityId);

					if (
						!BX.type.isNotEmptyString(entityTypeId)
						|| entityId <= 0
					)
					{
						return
					}

					var likeResult = new BX.LikeResultMobile({
						pathToUserProfile: pathToUserProfile,
						entityTypeId: entityTypeId,
						entityId: entityId
					});

					likeResult.cleanHead();
					likeResult.cleanUsers();
					likeResult.get();
				}
			}
		});

	};

	BX.LikeResultMobile.prototype = {
		get: function ()
		{
			BX.rest.callBatch({
					reactions : ['like.reactions', {
						ENTITY_TYPE_ID: this.entityTypeId,
						ENTITY_ID: this.entityId
					}],
					likeList : ['like.list', {
						ENTITY_TYPE_ID: this.entityTypeId,
						ENTITY_ID: this.entityId,
						REACTION: (this.currentReaction == 'all' ? '' : this.currentReaction),
						PATH_TO_USER_PROFILE: this.pathToUserProfile,
					}],
			}, function (result) {
				if (
					result.reactions.error()
					|| result.likeList.error()
				)
				{
					console.error('LikeResultMobile.requestConfigData: failed', [
						result.reactions.error(),
						result.likeList.error(),
					]);

					return false;
				}

				this.tabData = result.reactions;
				this.buildHead(result.reactions);
				this.processUsersResult(result.likeList);
			}.bind(this));
		},

		buildHead: function (result)
		{
			// head
			var
				tabsNode = BX('like-result-head'),
				reactionsData = result.data(),
				reactionsList = [],
				total = reactionsCount = 0;

			this.cleanHead();

			for (reaction in reactionsData)
			{
				if (reactionsData.hasOwnProperty(reaction))
				{
					if (parseInt(reactionsData[reaction]) > 0)
					{
						reactionsCount++;
						total += parseInt(reactionsData[reaction]);

						reactionsList.push({
							reaction: reaction,
							count: reactionsData[reaction]
						});
					}
				}
			}

			if (reactionsCount > 1)
			{
				tabsNode.appendChild(this.buildTabNode({
					reaction: 'all',
					count: total
				}));
			}

			if (reactionsCount == 0)
			{
				reactionsList.push({
					reaction: BX.message('RATING_LIKE_REACTION_DEFAULT'),
					count: total
				});
			}

			if (BX('like-result-block'))
			{
				if (reactionsCount > 3)
				{
					BX('like-result-block').classList.add('bx-ilike-head-length-modifier');
				}
				else
				{
					BX('like-result-block').classList.remove('bx-ilike-head-length-modifier');
				}
			}

			reactionsList.sort(function(a, b) {
				var sample = {
					like: 0,
					kiss: 1,
					laugh: 2,
					wonder: 3,
					cry: 4,
					angry: 5,
					facepalm: 6
				};
				if (sample[a.reaction] < sample[b.reaction])
				{
					return -1;
				}
				if (sample[a.reaction] > sample[b.reaction])
				{
					return 1;
				}
				return 0;
			});

			for(var ind = 0; ind < reactionsList.length; ind++)
			{
				tabsNode.appendChild(this.buildTabNode(reactionsList[ind]));
			}
		},

		cleanHead: function()
		{
			var
				tabsNode = BX('like-result-head');

			BX.cleanNode(tabsNode);
		},

		buildTabNode: function(params)
		{
			return BX.create('span', {
				props: {
					className: 'bx-ilike-mobile-popup-head-item' + (
						params.reaction == this.currentReaction
						|| (
							params.reaction == 'all' && !BX.type.isNotEmptyString(this.currentReaction)
						)
							? ' bx-ilike-mobile-popup-head-item-current'
							: ''
					)
				},
				attrs: (
					params.reaction != 'all'
						? {
							title: BX.message('RATING_LIKE_EMOTION_' + params.reaction.toUpperCase() + '_CALC')
						}
						: {}
				),
				children: (
					params.reaction == 'all'
						? [
							BX.create('span', {
								props: {
									className: 'bx-ilike-mobile-popup-head-text'
								},
								html: BX.message('RATING_LIKE_POPUP_ALL').replace('#CNT#', params.count)
							})
						]
						: [
							BX.create('span', {
								props: {
									className: 'bx-ilike-mobile-popup-head-icon bx-ilike-mobile-emoji-icon-item bx-ilike-mobile-emoji-icon-' + params.reaction
								}
							}),
							BX.create('span', {
								props: {
									className: 'bx-ilike-mobile-popup-head-text'
								},
								html: params.count
							})
						]
				),
				events: {
					click: BX.proxy(function() {
						this.currentReaction = params.reaction;
						this.userIdList = [];
						this.buildHead(this.tabData);
						this.cleanUsers();
						this.get();
					}, this)
				}
			})
		},

		cleanUsers: function()
		{
			var
				contentNode = BX('like-result-content');

			if (!contentNode)
			{
				return;
			}

			BX.cleanNode(contentNode);
		},

		processUsersResult: function(result)
		{
			var requestReaction = null;

			if (
				BX.type.isNotEmptyObject(result.query)
				&& BX.type.isNotEmptyString(result.query.data)
			)
			{
				result.query.data.split('&').forEach(function(pair) {
					var chunks = pair.split('=');
					if (chunks.length === 2)
					{
						var key = chunks[0];
						var value = chunks[1];
						if (
							BX.type.isNotEmptyString(key)
							&& key === 'REACTION'
						)
						{
							requestReaction = value;
						}

					}
				});
			}

			if (
				requestReaction !== null
				&& this.currentReaction !== requestReaction
				&& !(
					this.currentReaction === 'all'
					&& requestReaction === ''
				)
			)
			{
				return;
			}

			this.blockScrollRequest = false;
			var usersData = result.data();
			var contentNode = BX('like-result-content');

			if (!contentNode)
			{
				return;
			}

			var usersCounter = 0;
			for (ind in usersData)
			{
				if (
					usersData.hasOwnProperty(ind)
					&& !BX.util.in_array(usersData[ind].USER_ID, this.userIdList)
				)
				{
					usersCounter++;
					contentNode.appendChild(this.buildUserNode(usersData[ind]));
					this.userIdList.push(usersData[ind].USER_ID);
				}
			}

			if (usersCounter <= 0)
			{
				return;
			}

			if (
				result.more()
				&& BX.pos(contentNode).height < (window.innerHeight - BX('like-result-head').clientHeight)
			)
			{
				this.blockScrollRequest = true;
				result.next(BX.proxy(this.processUsersResult, this));
			}

			BX.bind(BX('like-result-page'), 'scroll' , BX.proxy(function() {
				var node = BX.proxy_context;
				if (
					!this.blockScrollRequest
					&& node.scrollTop > (node.scrollHeight - node.offsetHeight) / 1.5
				)
				{
					this.blockScrollRequest = true;
					result.next(BX.proxy(this.processUsersResult, this));
					BX.unbindAll(node);
				}

			}, this));
		},

		buildUserNode: function(params)
		{
			return BX.create('a', {
				attrs: {
					href: params.URL
				},
				props: {
					className: 'bx-ilike-mobile-popup-user-item'
				},
				children: [
					BX.create('span', {
						style: (
							BX.type.isNotEmptyString(params.PHOTO_SRC)
								? {
									'background-image': 'url("' + params.PHOTO_SRC + '")'
								}
								: {}
						),
						props: {
							className: 'bx-ilike-mobile-popup-user-icon'
						}
					}),
					BX.create('span', {
						props: {
							className: 'bx-ilike-mobile-popup-user-name'
						},
						html: params.FULL_NAME
					})

				]
			});
		}
	};

	BX.ready(function() {

		BXMPage.getTitle().setText(BX.message('RVR_MOBILE_TITLE'));
		BXMPage.getTitle().show();

		BX.LikeResultMobile.initPage();
		BX.addCustomEvent('onOpenPageBefore', BX.LikeResultMobile.initPage);
	});
})();