;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.TagsUsersPopup)
	{
		return;
	}

	namespace.TagsUsersPopup = function(params) {

		setTimeout(function() {
			this.init(params);
		}.bind(this), 0);

		return this;
	};

	namespace.TagsUsersPopup.prototype = {
		init: function(params)
		{
			this.tagsInstance = params.tagsInstance;
			this.managerInstance = this.tagsInstance.managerInstance;
			this.tagNode = (BX.type.isDomNode(params.tagNode) ? params.tagNode : null);
			this.tag = params.tag;
			this.checksum = params.checksum;
			this.page = 1;

			this.contentNode = (
				BX(params.containerNodeId)
					? BX.findChild(BX(params.containerNodeId), {
							tagName: 'span',
							className: 'iup-tags-item-users-popup'
						}, true, false)
					: null
			);

			this.actionNode = (
				BX(params.containerNodeId)
					? BX.findChild(BX(params.containerNodeId), {
						tagName: 'span',
						className: 'iup-tags-item-users-action'
					}, true, false)
					: null
			);

			this.popup = null;
			this.initialized = false;

			this.showPopupTimeoutId = null;
			this.hidePopupTimeoutId = null;
			this.leavePopupTimeoutId = null;

			this.popupUsersShownIdList = [];

			BX.bind(this.actionNode, 'click', function() {
				this.tagsInstance.addTag({
					userId: BX.message('USER_ID'),
					tag: this.tag,
					tagNode: this.tagNode
				});
				this.tagsInstance.reindexUser({
					userId: BX.message('USER_ID')
				});
				this.popup.close();
			}.bind(this));
		},

		onMouseOver: function(params)
		{
			if (
				this.tagsInstance.popupContent
				&& BX.findParent(params.event.target, { className: 'intranet-user-profile-tags-popup-empty-cont'}, this.tagsInstance.popupContent)
			)
			{
				return;
			}

			if (
				this.popup !== null
				&& this.popup.isShown()
			)
			{
				clearTimeout(this.hidePopupTimeoutId);
				return;
			}

			clearTimeout(this.showPopupTimeoutId);

			this.showPopupTimeoutId = setTimeout(function() {
				this.show({
					bindNode: params.event.target
				});
			}.bind(this), 400);
		},

		onMouseOut: function(params)
		{
			clearTimeout(this.showPopupTimeoutId);

			if(!this.popup)
			{
				return;
			}

			this.hidePopupTimeoutId = setTimeout(function() {
				this.popup.close();
			}.bind(this), 1000);
		},

		onClick: function(params)
		{
			clearTimeout(this.showPopupTimeoutId);
			this.show({
				bindNode: params.event.currentTarget
			});
		},

		show: function(params)
		{
			this.open({
				bindNode: params.bindNode
			});

			if (!this.initialized)
			{
				this.page = 1;

				BX.ajax.runComponentAction(this.managerInstance.componentName, 'getTagData', {
					mode: 'class',
					signedParameters: this.managerInstance.signedParameters,
					data: {
						params: {
							tag: this.tag,
							page: this.page
						}
					}
				}).then(function (response) {
					this.buildContent(response.data);
					this.initialized = true;
				}.bind(this), function (response) {

				}.bind(this));
			}
		},

		open: function(params)
		{
			if (this.popup == null)
			{
				this.popup = new BX.PopupWindow('tags-users-popup-' + this.checksum, params.bindNode, {
					lightShadow : true,
					offsetLeft: -22,
					autoHide: true,
					closeByEsc: true,
					zIndex: 2005,
					bindOptions: {
						position: 'top'
					},
					animationOptions: {
						show: {
							type: 'opacity-transform'
						},
						close: {
							type: 'opacity'
						}
					},
					events : {
						onPopupClose : function() {
//							BX.UserContentView.currentPopupId = null;
						},
						onPopupDestroy : function() {  }
					},
					content : BX('iup-tags-item-users-popup-container-' + this.checksum),
					className: 'iup-tags-users-popup'
				});

				BX.bind(BX('tags-users-popup-' + this.checksum), 'mouseleave' , function() {
					clearTimeout(this.hidePopupTimeoutId);
					this.hidePopupTimeoutId = setTimeout(function() {
						this.popup.close();
					}.bind(this), 1000);
				}.bind(this));

				BX.bind(BX('tags-users-popup-' + this.checksum), 'mouseenter' , function() {
					clearTimeout(this.hidePopupTimeoutId);
				}.bind(this));
			}

			this.popup.show();
			this.adjustPopup();

		},

		adjustPopup: function()
		{
			if (this.popup != null && this.popup.getPopupContainer())
			{
				this.popup.bindOptions.forceBindPosition = true;
				this.popup.adjustPosition();
				this.popup.bindOptions.forceBindPosition = false;
			}
		},

		buildContent: function(data)
		{
			if (BX.type.isArray(data.USERS))
			{
				if (
					this.page == 1
					&& this.contentNode
				)
				{
					this.contentNode.innerHTML = '';
				}

				this.page += 1;

				var avatarNode = null;

				for (var i = 0; i < data.USERS.length; i++)
				{
					if (BX.util.in_array(data.USERS[i]['ID'], this.popupUsersShownIdList))
					{
						continue;
					}

					this.popupUsersShownIdList.push(data.USERS[i]['ID']);

					if (data.USERS[i].PERSONAL_PHOTO.SRC.length > 0)
					{
						avatarNode = BX.create("IMG", {
							attrs: {
								src: encodeURI(data.USERS[i].PERSONAL_PHOTO.SRC)
							},
							props: {
								className: "iup-tags-item-users-popup-avatar-img"
							}
						});
					}
					else
					{
						avatarNode = BX.create("IMG", {
							attrs: {
								src: '/bitrix/images/main/blank.gif'
							},
							props: {
								className: "iup-tags-item-users-popup-avatar-img bx-contentview-popup-avatar-img-default"
							}
						});
					}

					this.contentNode.appendChild(
						BX.create("A", {
							attrs: {
								href: data.USERS[i]['URL'],
								target: '_blank',
								title: data.USERS[i]['NAME_FORMATTED']
							},
							props: {
								className: "iup-tags-item-users-popup-img" + (!!data.USERS[i]['TYPE'] ? " iup-tags-item-users-popup-img-" + data.USERS[i]['TYPE'] : "")
							},
							children: [
								BX.create("SPAN", {
									props: {
										className: "iup-tags-item-users-popup-avatar-new"
									},
									children: [
										avatarNode,
										BX.create("SPAN", {
											props: {
												className: "bx-contentview-popup-avatar-status-icon"
											}
										})
									]
								}),
								BX.create("SPAN", {
									attrs: {
										'bx-tooltip-user-id': data.USERS[i]['ID']
									},
									props: {
										className: "iup-tags-item-users-popup-name-new"
									},
									html: data.USERS[i]['NAME_FORMATTED']
								})
							]
						})
					);
				}

				this.adjustPopup();
				this.bindScroll();
			}

			if (
				BX.type.isNotEmptyString(data.CAN_ADD)
				&& data.CAN_ADD == 'Y'
			)
			{
				this.actionNode.classList.add('iup-tags-item-users-action-visible');
			}
			else
			{
				this.actionNode.classList.remove('iup-tags-item-users-action-visible');
			}
		},

		bindScroll: function()
		{
			BX.bind(this.contentNode, 'scroll' , function(e) {
				var node = e.currentTarget;
				if (node.scrollTop > (node.scrollHeight - node.offsetHeight) / 1.5)
				{

					BX.ajax.runComponentAction(this.managerInstance.componentName, 'getTagData', {
						mode: 'class',
						signedParameters: this.managerInstance.signedParameters,
						data: {
							params: {
								tag: this.tag,
								page: this.page
							}
						}
					}).then(function (response) {
						if (
							BX.type.isArray(response.data.USERS)
							&& response.data.USERS.length > 0
						)
						{
							this.buildContent(response.data);
						}
					}.bind(this), function (response) {
					}.bind(this));

					BX.unbindAll(node);
				}
			}.bind(this));
		},
	};




})();