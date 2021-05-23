;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.StressLevel)
	{
		return;
	}
	namespace.StressLevel = function(params) {
		this.init(params);
	};

	namespace.StressLevel.prototype = {

		init: function(params)
		{
			this.managerInstance = params.managerInstance;
			this.options = params.options;

			this.loader = null;

			this.stressLevel = {
				value: null,
				type: null,
				typeDescription: null,
				comment: null,
				hash: null
			};

			this.noResultNode = BX('intranet-user-profile-stresslevel-noresult');
			this.resultNode = BX('intranet-user-profile-stresslevel-result');

			if (
				!this.resultNode
				|| !this.noResultNode
			)
			{
				return;
			}
			this.resultStatusInfoNode = BX('intranet-user-profile-stresslevel-status-info');
			this.permsOpenNode = BX('intranet-user-profile-stresslevel-result-perms-open');
			this.permsCloseNode = BX('intranet-user-profile-stresslevel-result-perms-close');
			this.checkNode = BX('intranet-user-profile-stresslevel-check');
			this.widgetNode = BX('intranet-user-profile-stresslevel-widget');
			this.widgetNoResultNode = BX('intranet-user-profile-stresslevel-noresult-widget');
			this.resultCommentNode = BX('intranet-user-profile-stresslevel-comment');
			this.resultCopyLinkNode = BX('intranet-user-profile-stresslevel-status-copy');

			this.widgetLayout = {
				container: null,
				arrow: null,
				level: null,
				content: null,
				title: null,
				progress: null
			};

			this.url = {};
			this.measurementId = null;

			this.classes = {
				widget: 'intranet-stress-level-widget',
				widgetArrow: 'intranet-stress-level-widget-arrow',
				widgetContent: 'intranet-stress-level-widget-content',
				widgetContentTitle: 'intranet-stress-level-widget-content-title',
				widgetContentProgress: 'intranet-stress-level-widget-content-progress',
				widgetContentProgressEmpty: 'intranet-stress-level-widget-content-progress-empty'
			};

			BX.UI.Hint.init(this.noResultNode);
			BX.UI.Hint.init(this.resultNode);

			this.getData();

			if (this.managerInstance.canEditProfile)
			{
				this.getOpenPerms();
			}
/*
			this.managerInstance.processSliderCloseEvent({
				entityType: 'gratPost',
				callback: function() {
					BX.cleanNode(this.postsWrapperNode);
					this.postListPageNum = 1;
					this.getData();
				}.bind(this)
			});
*/

			BX.bind(this.permsOpenNode, 'click', function() {
				this.setOpenPerms({
					value: true
				});
			}.bind(this));

			BX.bind(this.permsCloseNode, 'click', function() {
				this.setOpenPerms({
					value: false
				});
			}.bind(this));

			BX.bind(this.checkNode, 'click', function() {
				this.openSlider({
					page: ''
				});
			}.bind(this));

			BX.bind(this.resultStatusInfoNode, 'click', function() {
				this.openSlider({
					page: 'result'
				});
			}.bind(this));

			if (this.resultCopyLinkNode)
			{
				var matches = navigator.userAgent.match(/chrome\/(\d+)/i);
				if (matches && parseInt(matches[1]) >= 76)
				{
					BX.bind(this.resultCopyLinkNode, 'click', function() {
						if (this.measurementId)
						{
							this.copyToBuffer();
						}
					}.bind(this));
				}
				else
				{
					this.resultCopyLinkNode.style.display = 'none';
				}
			}
		},

		getData: function()
		{
			this.loader = this.managerInstance.showLoader({
				node: this.loaderNode,
				loader: this.loader
			});

			BX.ajax.runAction('socialnetwork.api.user.stresslevel.get', {
				signedParameters: this.managerInstance.signedParameters,
				data: {
					c: 'bitrix:intranet.user.profile',
					fields: {
						userId: this.managerInstance.userId
					}
				}
			}).then(function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});

				if (
					BX.type.isNotEmptyObject(response.data)
					&& BX.type.isNotEmptyObject(response.data.url)
				)
				{
					this.url = response.data.url;
				}

				if (
					BX.type.isNotEmptyObject(response.data)
					&& typeof response.data.id != 'undefined'
				)
				{
					this.measurementId = parseInt(response.data.id);
				}

				if (
					BX.type.isNotEmptyObject(response.data)
					&& typeof response.data.value != 'undefined'
				)
				{
					this.showData(response.data);
				}
				else if (BX.message('USER_ID') == this.managerInstance.userId)
				{
					this.showEmpty();
				}
			}.bind(this), function (response) {
				this.managerInstance.hideLoader({
					node: this.loaderNode,
					loader: this.loader
				});
			}.bind(this));
		},

		showData: function(data)
		{
			if (
				BX.type.isNotEmptyObject(data)
				&& typeof data.value != 'undefined'
			)
			{
				if (this.noResultNode)
				{
					this.resultNode.style.display = 'none';
				}

				if (!this.resultNode)
				{
					return;
				}

				this.stressLevel.value = (parseInt(data.value) > 0 ? parseInt(data.value) : 0);
				this.stressLevel.comment = (BX.type.isNotEmptyString(data.comment) ? data.comment : '');
				this.stressLevel.type = (BX.type.isNotEmptyString(data.type) ? data.type : '');
				this.stressLevel.typeDescription = (BX.type.isNotEmptyString(data.typeDescription) ? data.typeDescription : '');
				this.stressLevel.hash = (BX.type.isNotEmptyString(data.hash) ? data.hash : '');

				this.resultNode.style.display = 'block';

				this.renderData();
			}
		},

		showEmpty: function()
		{
			if (this.noResultNode)
			{
				this.noResultNode.style.display = 'block';
			}

			if (this.resultNode)
			{
				this.resultNode.style.display = 'none';
			}

			this.renderEmptyData();
		},

		getOpenPerms: function()
		{
			BX.ajax.runAction('socialnetwork.api.user.stresslevel.getaccess', {
				data: {
					fields: {
						userId: this.managerInstance.userId
					}
				}
			}).then(function (response) {
				if (BX.type.isNotEmptyObject(response.data))
				{
					this.showOpenPerms(response.data.value);
				}
			}.bind(this), function (response) {
			}.bind(this));
		},

		setOpenPerms: function(params)
		{
			if (!BX.type.isNotEmptyObject(params))
			{
				params = {};
			}

			if (!BX.type.isBoolean(params.value))
			{
				params.value = false;
			}

			BX.ajax.runAction('socialnetwork.api.user.stresslevel.setaccess', {
				data: {
					fields: {
						userId: this.managerInstance.userId,
						value: (params.value ? 'Y' : 'N')
					}
				}
			}).then(function (response) {
				if (BX.type.isNotEmptyString(response.data.value))
				{
					this.showOpenPerms(response.data.value);
				}
			}.bind(this), function (response) {
			}.bind(this));
		},

		showOpenPerms: function(value)
		{
			if (value == 'Y')
			{
				this.permsCloseNode.style.display = 'block';
				this.permsOpenNode.style.display = 'none';
			}
			else
			{
				this.permsCloseNode.style.display = 'none';
				this.permsOpenNode.style.display = 'block';
			}
		},

		openSlider: function(params)
		{
			var page = (
				BX.type.isNotEmptyObject(params)
				&& BX.type.isNotEmptyString(params.page)
					? params.page
					: ''
			);

			var url = '';

			if (
				page == 'result'
				&& BX.type.isNotEmptyString(this.url.result)
			)
			{
				url = this.url.result;
			}
			else if (BX.type.isNotEmptyString(this.url.check))
			{
				url = this.url.check;
			}

			if (BX.type.isNotEmptyString(url))
			{
				BX.SidePanel.Instance.open(url, {
					cacheable: false,
					data: {
					},
					width: 500
				});
			}
		},

		renderData: function()
		{
			if(!this.widgetNode)
			{
				return;
			}

			this.widgetNode.appendChild(this.getRenderContainer());
			this.afterRender();

			if (
				this.resultCommentNode
				&& BX.type.isNotEmptyString(this.stressLevel.comment)
			)
			{
				this.resultCommentNode.innerHTML = BX.util.htmlspecialchars(this.stressLevel.comment);
			}

			if (
				BX.type.isNotEmptyString(this.stressLevel.type)
				&& BX('intranet-user-profile-stresslevel-status-' + this.stressLevel.type)
			)
			{
				BX('intranet-user-profile-stresslevel-status-' + this.stressLevel.type).style.display = 'inline-block';
				BX('intranet-user-profile-stresslevel-status-' + this.stressLevel.type).innerHTML = BX.util.htmlspecialchars(this.stressLevel.typeDescription);
			}
		},

		renderEmptyData: function()
		{
			if(!this.widgetNoResultNode)
			{
				return;
			}

			this.widgetNoResultNode.appendChild(this.getRenderContainer());
			this.afterRender();
		},

		getRenderContainer: function()
		{
			if(!this.widgetLayout.container)
			{
				this.widgetLayout.container = BX.create("DIV", {
					props: {
						className: this.classes.widget
					},
					children: [
						this.getRenderArrowContainer(),
						this.getRenderContentContainer()
					]
				});
			}

			return this.widgetLayout.container;
		},

		getRenderArrowContainer: function()
		{
			if(!this.widgetLayout.arrow)
			{
				this.widgetLayout.arrow = BX.create("DIV", {
					props: {
						className: this.classes.widgetArrow
					}
				});
			}

			return this.widgetLayout.arrow;
		},

		getRenderContentContainer: function()
		{
			if(!this.widgetLayout.content)
			{
				this.widgetLayout.content = BX.create("DIV", {
					props: {
						className: this.classes.widgetContent
					},
					children: [
						this.widgetLayout.title = BX.create("DIV", {
							props: {
								className: this.classes.widgetContentTitle
							},
							text: BX.message('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT')
						}),
						this.widgetLayout.progress = BX.create('DIV', {
							props: {
								className: this.classes.widgetContentProgress
							},
							text: "?"
						})
					]
				})
			}

			return this.widgetLayout.content;
		},

		afterRender: function()
		{
			if(!this.stressLevel.value)
			{
				this.widgetLayout.progress.classList.add(this.classes.widgetContentProgressEmpty);
				this.getRenderArrowContainer().style.transform = "rotate(90deg)";
				return;
			}

			setTimeout(function() {
				this.animateRenderNumber();
				this.getRenderArrowContainer().style.transform = "rotate(" + this.getRenderProgressScale() + "deg)";
			}.bind(this), 1000);
		},

		animateRenderNumber: function()
		{
			var i = 0;
			var time = 600 / this.stressLevel.value;
			var interval = setInterval(function() {
				i++;
				this.widgetLayout.progress.innerHTML = i;
				i === this.stressLevel.value ? clearInterval(interval) : null;
			}.bind(this), time);
		},

		getRenderProgressScale: function()
		{
			return 1.8 * this.stressLevel.value;
		},

		copyToBuffer: function()
		{
			if (!this.measurementId)
			{
				return;
			}

			var imgURL = '/bitrix/components/bitrix/intranet.user.profile/stresslevel_img.php';
			fetch(imgURL).then(function (response)
			{
				return response.blob();
			}).then(function (blob)
			{
				navigator.clipboard.write([
					new ClipboardItem(Object.defineProperty({}, blob.type, {
						value: blob,
						enumerable: true
					}))
				])
//console.log('Image copied.');
			}).catch(function (e)
			{
//console.error(e, e.message);
			});
		}

	};

})();