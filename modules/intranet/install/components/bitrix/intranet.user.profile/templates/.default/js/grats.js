;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Grats)
	{
		return;
	}
	namespace.Grats = function(params) {
		this.init(params);
	};

	namespace.Grats.prototype = {

		init: function(params)
		{
			this.managerInstance = params.managerInstance;
			this.options = params.options;

			this.initBadges(this.options.badgesData);
			this.postListPageSize = parseInt(this.options.gratPostListPageSize);

			this.postListPageNum = 1;
			this.loader = null;

			this.badgesWrapperNode = BX('intranet-user-profile-thanks');
			this.postsWrapperNode = BX('intranet-user-profile-thanks-users-wrapper');
			this.moreLinkNode = BX('intranet-user-profile-load-users-link');
			this.loaderNode = BX('intranet-user-profile-thanks-users-loader');

			this.classes = {
				item: 'intranet-user-profile-thanks-item',
				itemActive: 'intranet-user-profile-thanks-item-active',
				itemCounter: 'intranet-user-profile-thanks-item-counter',
				badgesDisabled: 'intranet-user-profile-thanks-disabled'
			};

			if (this.moreLinkNode)
			{
				BX.bind(this.moreLinkNode, 'click', function()
				{
					this.getData();
				}.bind(this));
			}
			this.getData();

			this.managerInstance.processSliderCloseEvent({
				entityType: 'gratPost',
				callback: function() {
					BX.cleanNode(this.postsWrapperNode);
					this.postListPageNum = 1;
					this.getData();
				}.bind(this)
			});

			if (this.badgesWrapperNode)
			{
				BX.bind(this.badgesWrapperNode, 'click', function(e)
				{
					this.openGratSlider(e);
				}.bind(this));
			}
		},

		initBadges: function(badgesData)
		{
			this.badgesData = {};
			for (var key in badgesData)
			{
				if (!badgesData.hasOwnProperty(key))
				{
					continue;
				}
				this.badgesData[badgesData[key].ID] = {
					CODE: badgesData[key].CODE,
					NAME: badgesData[key].NAME
				}
			}
		},

		showMoreLink: function()
		{
			if (this.moreLinkNode)
			{
				this.moreLinkNode.style.display = 'block';
			}
		},

		hideMoreLink: function()
		{
			if (this.moreLinkNode)
			{
				this.moreLinkNode.style.display = 'none';
			}
		},

		getData: function()
		{
			this.loader = this.managerInstance.showLoader({
				node: this.loaderNode,
				loader: this.loader
			});
			this.showBadgesLoader();

			BX.ajax.runComponentAction(this.managerInstance.componentName, 'getGratitudePostList', {
				mode: 'class',
				signedParameters: this.managerInstance.signedParameters,
				data: {
					params: {
						pageSize: this.postListPageSize,
						pageNum: this.postListPageNum
					}
				}
			}).then(function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
				this.hideBadgesLoader();
				if (BX.type.isNotEmptyObject(response.data))
				{
					if (
						typeof response.data.POSTS_COUNT != 'undefined'
						&& (this.postListPageSize * this.postListPageNum >=  parseInt(response.data.POSTS_COUNT))
					)
					{
						this.hideMoreLink();
					}
					else
					{
						this.showMoreLink();
					}

					this.showData(response.data);
					this.postListPageNum++;
				}
			}.bind(this), function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
				this.hideBadgesLoader();
				this.showMoreLink();
				/**
				 {
					 "status": "error",
					 "errors": [...]
				 }
				 **/
			}.bind(this));
		},

		showData: function(data)
		{
			if (BX.type.isNotEmptyObject(data))
			{
				if (BX.type.isNotEmptyObject(data.BADGES))
				{
					this.showBadges(data.BADGES);
				}
				if (BX.type.isNotEmptyObject(data.POSTS))
				{
					this.showPosts(data.POSTS, (BX.type.isNotEmptyObject(data.AUTHORS) ? data.AUTHORS : {}));
				}
			}
		},

		showBadges: function(data)
		{
			if (!this.badgesWrapperNode)
			{
				return;
			}

			var gratNodesList = BX.findChildren(this.badgesWrapperNode, { className: this.classes.item });

			if (gratNodesList)
			{
				var enumId = null;
				for (var i = 0; i < gratNodesList.length; i++)
				{
					enumId = gratNodesList[i].getAttribute('data-bx-grat-enum');
					if (
						BX.type.isNotEmptyString(enumId)
						&& BX.type.isNotEmptyObject(data[enumId])
						&& typeof data[enumId].COUNT != 'undefined'
						&& parseInt(data[enumId].COUNT) > 0
					)
					{
						gratNodesList[i].appendChild(BX.create('DIV', {
							props: {
								className: this.classes.itemCounter
							},
							text: parseInt(data[enumId].COUNT)
						}));
						gratNodesList[i].classList.add(this.classes.itemActive);
					}
					else
					{
						BX.cleanNode(gratNodesList[i]);
						gratNodesList[i].classList.remove(this.classes.itemActive);
					}
				}

				var gratNodesListClone = BX.clone(gratNodesList);

				gratNodesListClone.sort(function(a, b) {
					if (
						a.classList.contains(this.classes.itemActive)
						&& !b.classList.contains(this.classes.itemActive)
					)
					{
						return -1;
					}
					else if (
						!a.classList.contains(this.classes.itemActive)
						&& b.classList.contains(this.classes.itemActive)
					)
					{
						return 1;
					}
					else
					{
						return 0;
					}
				}.bind(this));

				BX.cleanNode(this.badgesWrapperNode);
				for (i = 0; i < gratNodesListClone.length; i++)
				{
					this.badgesWrapperNode.appendChild(gratNodesListClone[i]);
				}
			}
		},

		showBadgesLoader: function()
		{
			if (!this.badgesWrapperNode)
			{
				return;
			}

			this.badgesWrapperNode.classList.add(this.classes.badgesDisabled);
		},

		hideBadgesLoader: function()
		{
			if (!this.badgesWrapperNode)
			{
				return;
			}

			this.badgesWrapperNode.classList.remove(this.classes.badgesDisabled);
		},

		showPosts: function(data, authorsData)
		{
			if (!this.postsWrapperNode)
			{
				return;
			}

			var
				postData = null,
				ratingNode = null,
				reactionsNode = null;

			var postsList = [];

			for (var postId in data)
			{
				if (!data.hasOwnProperty(postId))
				{
					continue;
				}
				postsList.push(data[postId]);
			}

			postsList.sort(function(a, b) {
				if (a.DATE_PUBLISH_TS == b.DATE_PUBLISH_TS)
				{
					return 0;
				}
				return (a.DATE_PUBLISH_TS < b.DATE_PUBLISH_TS) ? +1 : -1;
			});

			for (var key = 0; key < postsList.length; key++)
			{
				postData = postsList[key];

				ratingNode = BX.create('DIV', {
					props: {
						className: 'intranet-user-profile-thanks-user-content-likes'
					},
					children: [

					]
				});
				if (BX.type.isNotEmptyObject(postData.RATING_DATA))
				{
					reactionsNode = BX.create('SPAN', {
						props: {
							className: 'intranet-user-profile-thanks-user-content-likes-emodji'
						}
					});

					if (BX.type.isNotEmptyObject(postData.RATING_DATA.REACTIONS_LIST))
					{
						for (var reaction in postData.RATING_DATA.REACTIONS_LIST)
						{
							if (
								!postData.RATING_DATA.REACTIONS_LIST.hasOwnProperty(reaction)
								|| parseInt(postData.RATING_DATA.REACTIONS_LIST[reaction]) <= 0
							)
							{
								continue;
							}

							reactionsNode.appendChild(BX.create('SPAN', {
								props: {
									className: 'intranet-user-profile-thanks-emoji intranet-user-profile-thanks-emoji-' + reaction
								}
							}));
						}
					}

					ratingNode.appendChild(reactionsNode);

					if (
						typeof postData.RATING_DATA.TOTAL_POSITIVE_VOTES != 'undefined'
						&& parseInt(postData.RATING_DATA.TOTAL_POSITIVE_VOTES) > 0
					)
					{
						ratingNode.appendChild(BX.create('SPAN', {
							props: {
								className: 'intranet-user-profile-thanks-user-content-likes-counter'
							},
							text: parseInt(postData.RATING_DATA.TOTAL_POSITIVE_VOTES)
						}));
					}
				}

				var
					userPic = '',
					userName = '';

				if (
					typeof postData.AUTHOR_ID != 'undefined'
					&& parseInt(postData.AUTHOR_ID) > 0
					&& BX.type.isNotEmptyObject(authorsData)
					&& BX.type.isNotEmptyObject(authorsData[postData.AUTHOR_ID])
					&& BX.type.isNotEmptyString(authorsData[postData.AUTHOR_ID].PHOTO)
				)
				{
					userPic = "background-size: cover; background-image: url('" + encodeURI(authorsData[postData.AUTHOR_ID].PHOTO) + "')";
					userName = authorsData[postData.AUTHOR_ID].NAME + ' ' + authorsData[postData.AUTHOR_ID].LAST_NAME;
				}

				this.postsWrapperNode.appendChild(BX.create('DIV', {
					props: {
						className: 'intranet-user-profile-thanks-user'
					},
					children: [
						BX.create('DIV', {
							props: {
								className: 'intranet-user-profile-thanks-user-avatar'
							},
							children: [
								BX.create('A', {
									attrs: {
										href: authorsData[postData.AUTHOR_ID].URL,
										'bx-tooltip-user-id': postData.AUTHOR_ID

									},
									props: {
										className: 'ui-icon ui-icon-common-user intranet-user-profile-thanks-user-userpic'
									},
									children: [
										BX.create('i', {
											props: {
												style: userPic
											},
											attrs: {
												title: userName
											}
										})
									]
								}),
								BX.create('DIV', {
									props: {
										className: 'intranet-user-profile-thanks-user-picture' + (this.badgesData[postData.BADGE_ID] ? ' intranet-user-profile-thanks-item-' + this.badgesData[postData.BADGE_ID].CODE : '')
									}
								})
							]
						}),
						BX.create('DIV', {
							props: {
								className: 'intranet-user-profile-thanks-user-content'
							},
							children: [
								BX.create('A', {
									attrs: {
										'bx-post-url': postData.URL + (postData.URL.indexOf('?') == -1 ? '?' : '&') + 'IFRAME=Y',
										href: '#'
									},
									props: {
										className: 'intranet-user-profile-thanks-user-content-title'
									},
									html: postData.TITLE,
									events: {
										click: function(e) {
											BX.SidePanel.Instance.open(e.currentTarget.getAttribute('bx-post-url'), {
												width: 1000
											});
											e.stopPropagation();
											e.preventDefault();
										}
									}
								}),
								BX.create('DIV', {
									props: {
										className: 'intranet-user-profile-thanks-user-content-info'
									},
									children: [
										BX.create('SPAN', {
											props: {
												className: 'intranet-user-profile-thanks-user-content-date'
											},
											text: (BX.type.isNotEmptyString(postData.DATE_FORMATTED) ? postData.DATE_FORMATTED : '')
										}),
										(
											typeof postData.CONTENT_VIEW_CNT != 'undefined'
											&& parseInt(postData.CONTENT_VIEW_CNT) > 0
												? BX.create('SPAN', {
													props: {
														className: 'intranet-user-profile-thanks-user-content-views'
													},
													text: postData.CONTENT_VIEW_CNT
												})
												: null
										),
										ratingNode
									]
								})
							]
						})
					]
				}));
			}
		},

		openGratSlider: function(e)
		{
			if (!this.badgesWrapperNode)
			{
				return;
			}

			var url = this.badgesWrapperNode.getAttribute('data-bx-grat-url');
			if (!BX.type.isNotEmptyString(url))
			{
				return;
			}

			var badgeNode = null;
			if (e.target.classList.contains(this.classes.item))
			{
				badgeNode = e.target;
			}

			if (!badgeNode)
			{
				badgeNode = BX.findParent(e.target, { className: 'intranet-user-profile-thanks-item'}, this.badgesWrapperNode);
			}

			if (!badgeNode)
			{
				return;
			}

			var gratCode = badgeNode.getAttribute('data-bx-grat-code');
			if (!BX.type.isNotEmptyString(gratCode))
			{
				return;
			}

			url += ((url.indexOf('?') == -1) ? '?' : '&') + 'gratUserId=' + this.managerInstance.userId + '&gratCode=' + gratCode;

			BX.SidePanel.Instance.open(url, {
				cacheable: false,
				data: {
					entityType: 'gratPost',
					entityId: this.managerInstance.userId
				},
				width: 1000
			});
		}
	};

})();
