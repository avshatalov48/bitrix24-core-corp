import {Type, Event, Loc} from 'main.core';

export class Popup
{
	constructor(parent)
	{
		this.parent = parent;
		this.signedParameters = this.parent.signedParameters;
		this.componentName = this.parent.componentName;
		this.userInnerBlockNode = this.parent.userInnerBlockNode || "";
		this.isPopupShown = false;
		this.popupCurrentPage = {};

		Event.bind(this.userInnerBlockNode, 'click', () => {
			this.showPopup('getAllOnlineUser', this.userInnerBlockNode);
		});

		if (this.parent.isTimemanAvailable && Type.isDomNode(this.parent.timemanNode))
		{
			let openedNode = this.parent.timemanNode.querySelector('.js-ustat-online-timeman-opened-block');
			let closedNode = this.parent.timemanNode.querySelector('.js-ustat-online-timeman-closed-block');

			Event.bind(openedNode, 'click', () => {
				this.showPopup('getOpenedTimemanUser', openedNode);
			});

			Event.bind(closedNode, 'click', () => {
				this.showPopup('getClosedTimemanUser', closedNode);
			});
		}
	}

	getPopupTitle(action)
	{
		let title = "";
		if (action === "getAllOnlineUser")
		{
			title = Loc.getMessage("INTRANET_USTAT_ONLINE_USERS");
		}
		else if (action === "getOpenedTimemanUser")
		{
			title = Loc.getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY");
		}
		else if (action === "getClosedTimemanUser")
		{
			title = Loc.getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY");
		}

		return title;
	}

	showPopup(action, bindNode)
	{
		if (this.isPopupShown)
		{
			return;
		}

		this.popupCurrentPage[action] = 1;
		this.popupInnerContainer = "";

		this.allOnlineUserPopup = new BX.PopupWindow('intranet-ustat-online-popup', bindNode, {
			lightShadow : true,
			offsetLeft: action === 'getClosedTimemanUser' ? -60 : -22,
			offsetTop: 7,
			autoHide: true,
			closeByEsc: true,
			bindOptions: {
				position: 'bottom'
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
				onPopupDestroy : function() {
					this.isPopupShown = false;
				}.bind(this),
				onPopupClose: function() {
					this.destroy();
				},
				onAfterPopupShow: function(popup)
				{
					let popupContent = popup.contentContainer;

					let popupContainer = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-container'
						},
					});

					let popupTitle = BX.create('span', {
						props: {
							className: 'intranet-ustat-online-popup-name-title'
						},
						text: this.getPopupTitle(action)
					});

					this.popupInnerContainer = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-inner'
						},
					});

					let popupInnerContent = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-content'
						},
					});

					let popupInnerContentBox = BX.create('div', {
						props: {
							className: 'intranet-ustat-online-popup-content-box'
						},
					});

					popupContent.appendChild(popupTitle);
					popupContent.appendChild(popupContainer);
					popupContainer.appendChild(popupInnerContent);
					popupInnerContent.appendChild(popupInnerContentBox);
					popupInnerContentBox.appendChild(this.popupInnerContainer);

					this.loader = this.showLoader({node: popupInnerContent, loader: null, size: 40});
					this.showUsersInPopup(action);

					this.isPopupShown = true;

				}.bind(this)
			},
			className: 'intranet-ustat-online-popup'
		});

		/*BX.bind(BX('intranet-ustat-online-popup'), 'mouseout' , BX.delegate(function() {
			clearTimeout(this.popupTimeout);
			this.popupTimeout = setTimeout(BX.delegate(function() {
				this.allOnlineUserPopup.close();
			}, this), 1000);
		}, this));

		BX.bind(BX('intranet-ustat-online-popup'), 'mouseover' , BX.delegate(function() {
			clearTimeout(this.popupTimeout);
			clearTimeout(this.mouseLeaveTimeoutId);
		}, this));

		BX.bind(this.userInnerBlockNode, 'mouseleave' , BX.delegate(function() {
			this.mouseLeaveTimeoutId = setTimeout(BX.delegate(function() {
				this.allOnlineUserPopup.close();
			}, this), 1000);
		}, this));*/

		this.popupScroll(action);

		this.allOnlineUserPopup.show();
	}

	popupScroll(action)
	{
		if (!BX.type.isDomNode(this.popupInnerContainer))
		{
			return;
		}

		BX.bind(this.popupInnerContainer, 'scroll', BX.delegate(function() {
			var _this = BX.proxy_context;
			if (_this.scrollTop > (_this.scrollHeight - _this.offsetHeight) / 1.5)
			{
				this.showUsersInPopup(action);
				BX.unbindAll(_this);
			}
		}, this));
	};

	showUsersInPopup(action)
	{
		if (
			action !== 'getAllOnlineUser'
			&& action !== 'getOpenedTimemanUser'
			&& action !== 'getClosedTimemanUser'
		)
		{
			return;
		}

		BX.ajax.runComponentAction(this.componentName, action, {
			signedParameters: this.signedParameters,
			mode: 'class',
			data: {
				pageNum: this.popupCurrentPage[action]
			}
		}).then(function (response) {
			if (response.data)
			{
				this.renderPopupUsers(response.data);
				this.popupCurrentPage[action]++;
				this.popupScroll(action);
			}
			else
			{
				if (!this.popupInnerContainer.hasChildNodes())
				{
					this.popupInnerContainer.innerText = Loc.getMessage('INTRANET_USTAT_ONLINE_EMPTY');
				}
			}
			this.hideLoader({loader: this.loader});
		}.bind(this), function (response) {
			this.hideLoader({loader: this.loader});
		}.bind(this));
	}

	renderPopupUsers(users)
	{
		if (!this.allOnlineUserPopup || !BX.type.isDomNode(this.popupInnerContainer))
		{
			return;
		}

		if (!users || typeof users !== "object")
		{
			return;
		}

		for (var i in users)
		{
			if (!users.hasOwnProperty(i))
			{
				continue;
			}

			let avatarNode;

			if (BX.type.isNotEmptyString(users[i]['AVATAR']))
			{
				avatarNode = BX.create("div", {
					props: {className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"},
					children: [
						BX.create('i', {
							style : { backgroundImage : "url('" + users[i]['AVATAR'] + "')"}
						})
					]
				});
			}
			else
			{
				avatarNode = BX.create("div", {
					props: {className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"},
					children: [
						BX.create('i', {})
					]
				});
			}

			this.popupInnerContainer.appendChild(
				BX.create("A", {
					attrs: {
						href: users[i]['PATH_TO_USER_PROFILE'],
						target: '_blank',
					},
					props: {
						className: "intranet-ustat-online-popup-item"
					},
					children: [
						BX.create("SPAN", {
							props: {
								className: "intranet-ustat-online-popup-avatar-new"
							},
							children: [
								avatarNode,
								BX.create("SPAN", {
									props: {className: "intranet-ustat-online-popup-avatar-status-icon"}
								})
							]
						}),
						BX.create("SPAN", {
							props: {
								className: "intranet-ustat-online-popup-name"
							},
							html: users[i]['NAME']
						})
					]
				})
			);
		}

	}

	showLoader(params)
	{
		var loader = null;

		if (params.node)
		{
			if (params.loader === null)
			{
				loader = new BX.Loader({
					target: params.node,
					size: params.hasOwnProperty("size") ? params.size : 40
				});
			}
			else
			{
				loader = params.loader;
			}

			loader.show();
		}

		return loader;
	}

	hideLoader(params)
	{
		if (params.loader !== null)
		{
			params.loader.hide();
		}

		if (params.node)
		{
			BX.cleanNode(params.node);
		}

		if (params.loader !== null)
		{
			params.loader = null;
		}
	}
}