/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_popup,main_core_events,main_core,pull_client,ui_graph_circle) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
	class UserPopup {
	  constructor(parent) {
	    this.parent = parent;
	    this.signedParameters = this.parent.signedParameters;
	    this.componentName = this.parent.componentName;
	    this.userInnerBlockNode = this.parent.userInnerBlockNode || "";
	    this.timemanNode = this.parent.timemanNode;
	    this.circleNode = this.parent.circleNode || "";
	    this.isPopupShown = false;
	    this.popupCurrentPage = {};
	    this.renderedUsers = [];
	    main_core.Event.bind(this.userInnerBlockNode, 'click', () => {
	      this.showPopup('getAllOnlineUser', this.userInnerBlockNode);
	    });
	    main_core.Event.bind(this.circleNode, 'click', () => {
	      this.showPopup('getAllOnlineUser', this.circleNode, -5);
	    });
	    if (this.parent.isTimemanAvailable && main_core.Type.isDomNode(this.parent.timemanNode)) {
	      let openedNode = this.timemanNode.querySelector('.js-ustat-online-timeman-opened-block');
	      let closedNode = this.timemanNode.querySelector('.js-ustat-online-timeman-closed-block');
	      main_core.Event.bind(openedNode, 'click', () => {
	        this.showPopup('getOpenedTimemanUser', openedNode);
	      });
	      main_core.Event.bind(closedNode, 'click', () => {
	        this.showPopup('getClosedTimemanUser', closedNode);
	      });
	    }
	  }
	  getPopupTitle(action) {
	    let title = "";
	    if (action === "getAllOnlineUser") {
	      title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_USERS");
	    } else if (action === "getOpenedTimemanUser") {
	      title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY");
	    } else if (action === "getClosedTimemanUser") {
	      title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY");
	    }
	    return title;
	  }
	  showPopup(action, bindNode, topOffset) {
	    if (this.isPopupShown) {
	      return;
	    }
	    if (main_core.Type.isUndefined(topOffset)) {
	      topOffset = 7;
	    }
	    this.popupCurrentPage[action] = 1;
	    this.popupInnerContainer = "";
	    this.renderedUsers = [];
	    this.allOnlineUserPopup = new main_popup.Popup(`intranet-ustat-online-popup-${main_core.Text.getRandom()}`, bindNode, {
	      lightShadow: true,
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
	      events: {
	        onPopupDestroy: () => {
	          this.isPopupShown = false;
	        },
	        onPopupClose: () => {
	          this.allOnlineUserPopup.destroy();
	        },
	        onAfterPopupShow: popup => {
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UstatOnline:showPopup', new main_core_events.BaseEvent({
	            data: {
	              popup: popup
	            }
	          }));
	          let popupContent = main_core.Tag.render(_t || (_t = _`
						<div>
							<span class="intranet-ustat-online-popup-name-title">
								${0}
							</span>
							<div class="intranet-ustat-online-popup-container">
								<div class="intranet-ustat-online-popup-content">
									<div class="intranet-ustat-online-popup-content-box">
										<div class="intranet-ustat-online-popup-inner"></div>
									</div>
								</div>
							</div>
						</div>
					`), this.getPopupTitle(action));
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
	        onPopupFirstShow: popup => {
	          main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onOpenStart', () => {
	            popup.close();
	          });
	        }
	      },
	      className: 'intranet-ustat-online-popup'
	    });
	    this.popupScroll(action);
	    this.allOnlineUserPopup.show();
	  }
	  popupScroll(action) {
	    if (!main_core.Type.isDomNode(this.popupInnerContainer)) {
	      return;
	    }
	    main_core.Event.bind(this.popupInnerContainer, 'scroll', () => {
	      if (this.popupInnerContainer.scrollTop > (this.popupInnerContainer.scrollHeight - this.popupInnerContainer.offsetHeight) / 1.5) {
	        this.showUsersInPopup(action);
	        main_core.Event.unbindAll(this.popupInnerContainer, 'scroll');
	      }
	    });
	  }
	  showUsersInPopup(action) {
	    if (action !== 'getAllOnlineUser' && action !== 'getOpenedTimemanUser' && action !== 'getClosedTimemanUser') {
	      return;
	    }
	    BX.ajax.runComponentAction(this.componentName, action, {
	      signedParameters: this.signedParameters,
	      mode: 'class',
	      data: {
	        pageNum: this.popupCurrentPage[action]
	      }
	    }).then(response => {
	      if (response.data) {
	        this.renderPopupUsers(response.data);
	        this.popupCurrentPage[action]++;
	        this.popupScroll(action);
	      } else {
	        if (!this.popupInnerContainer.hasChildNodes()) {
	          this.popupInnerContainer.innerText = main_core.Loc.getMessage('INTRANET_USTAT_ONLINE_EMPTY');
	        }
	      }
	      this.hideLoader({
	        loader: this.loader
	      });
	    }, response => {
	      this.hideLoader({
	        loader: this.loader
	      });
	    });
	  }
	  renderPopupUsers(users) {
	    if (!this.allOnlineUserPopup || !main_core.Type.isDomNode(this.popupInnerContainer) || !main_core.Type.isObjectLike(users)) {
	      return;
	    }
	    for (let i in users) {
	      if (!users.hasOwnProperty(i) || this.renderedUsers.indexOf(users[i]['ID']) >= 0) {
	        continue;
	      }
	      this.renderedUsers.push(users[i]['ID']);
	      let avatarIcon = "<i></i>";
	      if (main_core.Type.isString(users[i]['AVATAR']) && users[i]['AVATAR']) {
	        avatarIcon = `<i style="background-image: url('${encodeURI(users[i]['AVATAR'])}')"></i>`;
	      }
	      const userNode = main_core.Tag.render(_t2 || (_t2 = _`
				<a 
					class="intranet-ustat-online-popup-item"
					href="${0}" 
					target="_blank"
				>
					<span class="intranet-ustat-online-popup-avatar-new">
						<div class="ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img">
							${0}
						</div>
						<span class="intranet-ustat-online-popup-avatar-status-icon"></span>
					</span>
					<span class="intranet-ustat-online-popup-name">
						${0}
					</span>
				</a>
			`), users[i]['PATH_TO_USER_PROFILE'], avatarIcon, users[i]['NAME']);
	      this.popupInnerContainer.appendChild(userNode);
	    }
	  }
	  showLoader(params) {
	    let loader = null;
	    if (params.node) {
	      if (params.loader === null) {
	        loader = new BX.Loader({
	          target: params.node,
	          size: params.hasOwnProperty("size") ? params.size : 40
	        });
	      } else {
	        loader = params.loader;
	      }
	      loader.show();
	    }
	    return loader;
	  }
	  hideLoader(params) {
	    if (params.loader !== null) {
	      params.loader.hide();
	    }
	    if (params.node) {
	      main_core.Dom.clean(params.node);
	    }
	    if (params.loader !== null) {
	      params.loader = null;
	    }
	  }
	}

	class Timeman {
	  constructor(parent) {
	    this.parent = parent;
	    this.signedParameters = this.parent.signedParameters;
	    this.componentName = this.parent.componentName;
	    this.isTimemanAvailable = this.parent.isTimemanAvailable;
	    this.timemanNode = this.parent.timemanNode;
	    this.containerNode = this.parent.ustatOnlineContainerNode;
	    if (this.isTimemanAvailable && main_core.Type.isDomNode(this.timemanNode)) {
	      this.timemanValueNodes = this.timemanNode.querySelectorAll('.intranet-ustat-online-value');
	      this.timemanTextNodes = this.timemanNode.querySelectorAll('.js-ustat-online-timeman-text');
	      this.resizeTimemanText();
	      this.subscribePullEvent();
	    }
	  }
	  resizeTimemanText() {
	    if (!main_core.Type.isDomNode(this.timemanNode)) {
	      return;
	    }
	    let textSum = 0;
	    let valueSum = 0;
	    if (main_core.Type.isArrayLike(this.timemanTextNodes)) {
	      for (let text of this.timemanTextNodes) {
	        let textItems = text.textContent.length;
	        textSum += textItems;
	      }
	    }
	    if (main_core.Type.isArrayLike(this.timemanValueNodes)) {
	      for (let value of this.timemanValueNodes) {
	        let valueItems = value.textContent.length;
	        valueSum += valueItems;
	      }
	    }
	    if (textSum >= 17 && valueSum >= 6 || textSum >= 19 && valueSum >= 4) {
	      main_core.Dom.addClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
	    } else {
	      main_core.Dom.removeClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
	    }
	  }
	  redrawTimeman(data) {
	    if (data.hasOwnProperty("OPENED")) {
	      let openedNode = this.containerNode.querySelector('.js-ustat-online-timeman-opened');
	      if (main_core.Type.isDomNode(openedNode)) {
	        openedNode.innerHTML = data["OPENED"];
	      }
	    }
	    if (data.hasOwnProperty("CLOSED")) {
	      let closedNode = this.containerNode.querySelector('.js-ustat-online-timeman-closed');
	      if (main_core.Type.isDomNode(closedNode)) {
	        closedNode.innerHTML = data["CLOSED"];
	      }
	    }
	    this.resizeTimemanText();
	  }
	  checkTimeman() {
	    BX.ajax.runComponentAction(this.componentName, "checkTimeman", {
	      signedParameters: this.signedParameters,
	      mode: 'class'
	    }).then(response => {
	      if (response.data) {
	        this.redrawTimeman(response.data);
	      }
	    });
	  }
	  subscribePullEvent() {
	    pull_client.PULL.subscribe({
	      moduleId: 'intranet',
	      command: 'timemanDayInfo',
	      callback: data => {
	        this.redrawTimeman(data);
	      }
	    });
	  }
	}

	const namespace = main_core.Reflection.namespace('BX.Intranet');
	class UstatOnline {
	  constructor(params) {
	    this.signedParameters = params.signedParameters;
	    this.componentName = params.componentName;
	    this.ustatOnlineContainerNode = params.ustatOnlineContainerNode || '';
	    this.maxOnlineUserCountToday = parseInt(params.maxOnlineUserCountToday, 10);
	    this.isTimemanAvailable = params.isTimemanAvailable === 'Y';
	    if (!main_core.Type.isDomNode(this.ustatOnlineContainerNode)) {
	      return;
	    }
	    this.userInnerBlockNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-icon-inner');
	    this.circleNode = this.ustatOnlineContainerNode.querySelector('.ui-graph-circle');
	    this.timemanNode = this.ustatOnlineContainerNode.querySelector('.intranet-ustat-online-info');
	    this.users = params.users;
	    this.counter = parseInt(params.user_count, 10);
	    this.currentDate = this.getToday();
	    if (main_core.Type.isDomNode(this.ustatOnlineContainerNode)) {
	      BX.UI.Hint.init(this.ustatOnlineContainerNode);
	    }
	    new UserPopup(this);
	    this.timemanObj = new Timeman(this);
	    this.redrawOnline();
	    setInterval(() => {
	      this.checkNewDay();
	    }, 3600000); //1 hour
	  }

	  getToday() {
	    const now = new Date();
	    return new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();
	  }
	  checkNewDay() {
	    const today = this.getToday();
	    if (this.currentDate < today)
	      //new day
	      {
	        if (this.isTimemanAvailable) {
	          this.timemanObj.checkTimeman();
	        }
	        this.currentDate = today;
	        return true;
	      }
	    return false;
	  }
	  isDocumentVisible() {
	    return document.visibilityState === 'visible';
	  }
	  redrawOnline() {
	    this.showCircleAnimation(this.circleNode, this.counter);
	    this.renderAllUser();
	  }
	  renderAllUser() {
	    for (const index in this.users) {
	      this.renderUser(this.users[index]);
	    }
	  }
	  renderUser(user) {
	    let userStyle = '';
	    if (user.avatar) {
	      userStyle = `background-image: url("${encodeURI(user.avatar)}");`;
	    }
	    const itemsClasses = `
			ui-icon ui-icon-common-user intranet-ustat-online-icon js-ustat-online-user
						${this.isDocumentVisible() ? ' intranet-ustat-online-icon-show' : ''}
		`;
	    this.userItem = BX.create('span', {
	      attrs: {
	        className: itemsClasses,
	        'data-user-id': user.id
	      },
	      style: {
	        zIndex: this.userIndex
	      },
	      children: [BX.create('i', {
	        attrs: {
	          style: userStyle
	        }
	      })]
	    });
	    main_core.Dom.prepend(this.userItem, this.userInnerBlockNode);
	  }
	  showCircleAnimation(circleNode, currentUserOnlineCount) {
	    if (currentUserOnlineCount <= 0) {
	      currentUserOnlineCount = 1;
	    }
	    const progressPercent = currentUserOnlineCount * 100 / this.maxOnlineUserCountToday;
	    if (this.circle) {
	      this.circle.updateCounter(progressPercent, currentUserOnlineCount);
	    } else {
	      this.circle = new ui_graph_circle.Circle(circleNode, 68, progressPercent, currentUserOnlineCount, true);
	      this.circle.show();
	    }
	  }
	}
	namespace.UstatOnline = UstatOnline;

	exports.UstatOnline = UstatOnline;

}((this.BX.Intranet.UstatOnline = this.BX.Intranet.UstatOnline || {}),BX.Main,BX.Event,BX,BX,BX.UI.Graph));
//# sourceMappingURL=script.js.map
