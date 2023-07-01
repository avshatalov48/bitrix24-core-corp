import {Type, Event, Loc, Text, Tag, Dom} from 'main.core';
import {Popup} from 'main.popup';
import {BaseEvent, EventEmitter} from 'main.core.events';

export class UserPopup
{
	constructor(parent)
	{
		this.parent = parent;
		this.signedParameters = this.parent.signedParameters;
		this.componentName = this.parent.componentName;
		this.userInnerBlockNode = this.parent.userInnerBlockNode || "";
		this.timemanNode = this.parent.timemanNode;
		this.circleNode = this.parent.circleNode || "";
		this.isPopupShown = false;
		this.popupCurrentPage = {};
		this.renderedUsers = [];

		Event.bind(this.userInnerBlockNode, 'click', () => {
			this.showPopup('getAllOnlineUser', this.userInnerBlockNode);
		});

		Event.bind(this.circleNode, 'click', () => {
			this.showPopup('getAllOnlineUser', this.circleNode, -5);
		});

		if (this.parent.isTimemanAvailable && Type.isDomNode(this.parent.timemanNode))
		{
			let openedNode = this.timemanNode.querySelector('.js-ustat-online-timeman-opened-block');
			let closedNode = this.timemanNode.querySelector('.js-ustat-online-timeman-closed-block');

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

	showPopup(action, bindNode, topOffset)
	{
		if (this.isPopupShown)
		{
			return;
		}

		if (Type.isUndefined(topOffset))
		{
			topOffset = 7;
		}

		this.popupCurrentPage[action] = 1;
		this.popupInnerContainer = "";
		this.renderedUsers = [];

		this.allOnlineUserPopup = new Popup(`intranet-ustat-online-popup-${Text.getRandom()}`, bindNode, {
			lightShadow : true,
			offsetLeft: action === 'getClosedTimemanUser' ? -60 : -22,
			offsetTop: topOffset,
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
				onPopupDestroy: () => {
					this.isPopupShown = false;
				},
				onPopupClose: () => {
					this.allOnlineUserPopup.destroy();
				},
				onAfterPopupShow: (popup) => {
					EventEmitter.emit(EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UstatOnline:showPopup', new BaseEvent({
						data: {
							popup: popup,
						}
					}));

					let popupContent = Tag.render`
						<div>
							<span class="intranet-ustat-online-popup-name-title">
								${this.getPopupTitle(action)}
							</span>
							<div class="intranet-ustat-online-popup-container">
								<div class="intranet-ustat-online-popup-content">
									<div class="intranet-ustat-online-popup-content-box">
										<div class="intranet-ustat-online-popup-inner"></div>
									</div>
								</div>
							</div>
						</div>
					`;

					popup.contentContainer.appendChild(popupContent);
					this.popupInnerContainer = popupContent.querySelector(".intranet-ustat-online-popup-inner");
					this.loader = this.showLoader({
						node: popupContent.querySelector(".intranet-ustat-online-popup-content"),
						loader: null,
						size: 40
					});
					this.showUsersInPopup(action);
					this.isPopupShown = true;
				},
				onPopupFirstShow: (popup) => {
					EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', () => {
						popup.close();
					});
				},
			},
			className: 'intranet-ustat-online-popup'
		});

		this.popupScroll(action);
		this.allOnlineUserPopup.show();
	}

	popupScroll(action)
	{
		if (!Type.isDomNode(this.popupInnerContainer))
		{
			return;
		}

		Event.bind(this.popupInnerContainer, 'scroll', () => {
			if (this.popupInnerContainer.scrollTop > (this.popupInnerContainer.scrollHeight - this.popupInnerContainer.offsetHeight) / 1.5)
			{
				this.showUsersInPopup(action);
				Event.unbindAll(this.popupInnerContainer, 'scroll');
			}
		});
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
		}).then((response) => {
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
		}, (response) =>{
			this.hideLoader({loader: this.loader});
		});
	}

	renderPopupUsers(users)
	{
		if (
			!this.allOnlineUserPopup
			|| !Type.isDomNode(this.popupInnerContainer)
			|| !Type.isObjectLike(users)
		)
		{
			return;
		}

		for (let i in users)
		{
			if (!users.hasOwnProperty(i) || this.renderedUsers.indexOf(users[i]['ID']) >= 0)
			{
				continue;
			}

			this.renderedUsers.push(users[i]['ID']);

			let avatarIcon = "<i></i>";

			if (Type.isString(users[i]['AVATAR']) && users[i]['AVATAR'])
			{
				avatarIcon = `<i style="background-image: url('${encodeURI(users[i]['AVATAR'])}')"></i>`;
			}

			const userNode = Tag.render`
				<a 
					class="intranet-ustat-online-popup-item"
					href="${users[i]['PATH_TO_USER_PROFILE']}" 
					target="_blank"
				>
					<span class="intranet-ustat-online-popup-avatar-new">
						<div class="ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img">
							${avatarIcon}
						</div>
						<span class="intranet-ustat-online-popup-avatar-status-icon"></span>
					</span>
					<span class="intranet-ustat-online-popup-name">
						${users[i]['NAME']}
					</span>
				</a>
			`;

			this.popupInnerContainer.appendChild(userNode);
		}
	}

	showLoader(params)
	{
		let loader = null;

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
			Dom.clean(params.node);
		}

		if (params.loader !== null)
		{
			params.loader = null;
		}
	}
}