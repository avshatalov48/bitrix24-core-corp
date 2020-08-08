this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_core,rest_client,pull_client) {
	'use strict';

	var Popup = /*#__PURE__*/function () {
	  function Popup(parent) {
	    var _this2 = this;

	    babelHelpers.classCallCheck(this, Popup);
	    this.parent = parent;
	    this.signedParameters = this.parent.signedParameters;
	    this.componentName = this.parent.componentName;
	    this.userInnerBlockNode = this.parent.userInnerBlockNode || "";
	    this.isPopupShown = false;
	    this.popupCurrentPage = {};
	    main_core.Event.bind(this.userInnerBlockNode, 'click', function () {
	      _this2.showPopup('getAllOnlineUser', _this2.userInnerBlockNode);
	    });

	    if (this.parent.isTimemanAvailable && main_core.Type.isDomNode(this.parent.timemanNode)) {
	      var openedNode = this.parent.timemanNode.querySelector('.js-ustat-online-timeman-opened-block');
	      var closedNode = this.parent.timemanNode.querySelector('.js-ustat-online-timeman-closed-block');
	      main_core.Event.bind(openedNode, 'click', function () {
	        _this2.showPopup('getOpenedTimemanUser', openedNode);
	      });
	      main_core.Event.bind(closedNode, 'click', function () {
	        _this2.showPopup('getClosedTimemanUser', closedNode);
	      });
	    }
	  }

	  babelHelpers.createClass(Popup, [{
	    key: "getPopupTitle",
	    value: function getPopupTitle(action) {
	      var title = "";

	      if (action === "getAllOnlineUser") {
	        title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_USERS");
	      } else if (action === "getOpenedTimemanUser") {
	        title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_STARTED_DAY");
	      } else if (action === "getClosedTimemanUser") {
	        title = main_core.Loc.getMessage("INTRANET_USTAT_ONLINE_FINISHED_DAY");
	      }

	      return title;
	    }
	  }, {
	    key: "showPopup",
	    value: function showPopup(action, bindNode) {
	      if (this.isPopupShown) {
	        return;
	      }

	      this.popupCurrentPage[action] = 1;
	      this.popupInnerContainer = "";
	      this.allOnlineUserPopup = new BX.PopupWindow('intranet-ustat-online-popup', bindNode, {
	        lightShadow: true,
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
	        events: {
	          onPopupDestroy: function () {
	            this.isPopupShown = false;
	          }.bind(this),
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          },
	          onAfterPopupShow: function (popup) {
	            var popupContent = popup.contentContainer;
	            var popupContainer = BX.create('div', {
	              props: {
	                className: 'intranet-ustat-online-popup-container'
	              }
	            });
	            var popupTitle = BX.create('span', {
	              props: {
	                className: 'intranet-ustat-online-popup-name-title'
	              },
	              text: this.getPopupTitle(action)
	            });
	            this.popupInnerContainer = BX.create('div', {
	              props: {
	                className: 'intranet-ustat-online-popup-inner'
	              }
	            });
	            var popupInnerContent = BX.create('div', {
	              props: {
	                className: 'intranet-ustat-online-popup-content'
	              }
	            });
	            var popupInnerContentBox = BX.create('div', {
	              props: {
	                className: 'intranet-ustat-online-popup-content-box'
	              }
	            });
	            popupContent.appendChild(popupTitle);
	            popupContent.appendChild(popupContainer);
	            popupContainer.appendChild(popupInnerContent);
	            popupInnerContent.appendChild(popupInnerContentBox);
	            popupInnerContentBox.appendChild(this.popupInnerContainer);
	            this.loader = this.showLoader({
	              node: popupInnerContent,
	              loader: null,
	              size: 40
	            });
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
	  }, {
	    key: "popupScroll",
	    value: function popupScroll(action) {
	      if (!BX.type.isDomNode(this.popupInnerContainer)) {
	        return;
	      }

	      BX.bind(this.popupInnerContainer, 'scroll', BX.delegate(function () {
	        var _this = BX.proxy_context;

	        if (_this.scrollTop > (_this.scrollHeight - _this.offsetHeight) / 1.5) {
	          this.showUsersInPopup(action);
	          BX.unbindAll(_this);
	        }
	      }, this));
	    }
	  }, {
	    key: "showUsersInPopup",
	    value: function showUsersInPopup(action) {
	      if (action !== 'getAllOnlineUser' && action !== 'getOpenedTimemanUser' && action !== 'getClosedTimemanUser') {
	        return;
	      }

	      BX.ajax.runComponentAction(this.componentName, action, {
	        signedParameters: this.signedParameters,
	        mode: 'class',
	        data: {
	          pageNum: this.popupCurrentPage[action]
	        }
	      }).then(function (response) {
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
	      }.bind(this), function (response) {
	        this.hideLoader({
	          loader: this.loader
	        });
	      }.bind(this));
	    }
	  }, {
	    key: "renderPopupUsers",
	    value: function renderPopupUsers(users) {
	      if (!this.allOnlineUserPopup || !BX.type.isDomNode(this.popupInnerContainer)) {
	        return;
	      }

	      if (!users || babelHelpers.typeof(users) !== "object") {
	        return;
	      }

	      for (var i in users) {
	        if (!users.hasOwnProperty(i)) {
	          continue;
	        }

	        var avatarNode = void 0;

	        if (BX.type.isNotEmptyString(users[i]['AVATAR'])) {
	          avatarNode = BX.create("div", {
	            props: {
	              className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"
	            },
	            children: [BX.create('i', {
	              style: {
	                backgroundImage: "url('" + users[i]['AVATAR'] + "')"
	              }
	            })]
	          });
	        } else {
	          avatarNode = BX.create("div", {
	            props: {
	              className: "ui-icon ui-icon-common-user intranet-ustat-online-popup-avatar-img"
	            },
	            children: [BX.create('i', {})]
	          });
	        }

	        this.popupInnerContainer.appendChild(BX.create("A", {
	          attrs: {
	            href: users[i]['PATH_TO_USER_PROFILE'],
	            target: '_blank'
	          },
	          props: {
	            className: "intranet-ustat-online-popup-item"
	          },
	          children: [BX.create("SPAN", {
	            props: {
	              className: "intranet-ustat-online-popup-avatar-new"
	            },
	            children: [avatarNode, BX.create("SPAN", {
	              props: {
	                className: "intranet-ustat-online-popup-avatar-status-icon"
	              }
	            })]
	          }), BX.create("SPAN", {
	            props: {
	              className: "intranet-ustat-online-popup-name"
	            },
	            html: users[i]['NAME']
	          })]
	        }));
	      }
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(params) {
	      var loader = null;

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
	  }, {
	    key: "hideLoader",
	    value: function hideLoader(params) {
	      if (params.loader !== null) {
	        params.loader.hide();
	      }

	      if (params.node) {
	        BX.cleanNode(params.node);
	      }

	      if (params.loader !== null) {
	        params.loader = null;
	      }
	    }
	  }]);
	  return Popup;
	}();

	var namespace = main_core.Reflection.namespace('BX.Intranet');

	var UstatOnline = /*#__PURE__*/function () {
	  function UstatOnline(params) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, UstatOnline);
	    this.signedParameters = params.signedParameters;
	    this.componentName = params.componentName;
	    this.userBlockNode = params.userBlockNode || "";
	    this.userInnerBlockNode = params.userInnerBlockNode || "";
	    this.circleNode = params.circleNode || "";
	    this.timemanNode = params.timemanNode || "";
	    this.ustatOnlineContainerNode = params.ustatOnlineContainerNode || "";
	    this.maxUserToShow = 7;
	    this.maxOnlineUserCountToday = params.maxOnlineUserCountToday;
	    this.currentUserId = parseInt(params.currentUserId);
	    this.isTimemanAvailable = params.isTimemanAvailable === "Y";
	    this.limitOnlineSeconds = params.limitOnlineSeconds;
	    var users = params.users;
	    var allOnlineUserIdToday = params.allOnlineUserIdToday;
	    this.users = users.map(function (user) {
	      user.id = parseInt(user.id);
	      user.offline_date = _this.getOfflineDate(user.last_activity_date);
	      return user;
	    });
	    this.allOnlineUserIdToday = allOnlineUserIdToday.map(function (id) {
	      return parseInt(id);
	    });
	    this.online = [].concat(this.users);
	    this.counter = 0; //-------------- for IndexedDb

	    this.ITEMS = {
	      obClientDb: null,
	      obClientDbData: {},
	      obClientDbDataSearchIndex: {},
	      bMenuInitialized: false,
	      initialized: {
	        sonetgroups: false,
	        menuitems: false
	      },
	      oDbSearchResult: {}
	    };
	    BX.Finder(false, 'searchTitle', [], {}, this);
	    BX.onCustomEvent(this, 'initFinderDb', [this.ITEMS, 'searchTitle', null, ['users'], this]); //---------------

	    if (main_core.Type.isDomNode(this.ustatOnlineContainerNode)) {
	      BX.UI.Hint.init(this.ustatOnlineContainerNode);
	    }

	    new Popup(this);
	    var now = new Date();
	    this.currentDate = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();
	    this.checkOnline();

	    if (this.isTimemanAvailable && main_core.Type.isDomNode(this.timemanNode)) {
	      this.timemanValueNodes = this.timemanNode.querySelectorAll('.intranet-ustat-online-value');
	      this.timemanTextNodes = this.timemanNode.querySelectorAll('.js-ustat-online-timeman-text');
	      this.resizeTimemanText();
	    }

	    setTimeout(function () {
	      _this.subscribePullEvent();
	    }, 3000);
	    setInterval(function () {
	      return _this.checkOnline();
	    }, 60000);
	  }

	  babelHelpers.createClass(UstatOnline, [{
	    key: "getOfflineDate",
	    value: function getOfflineDate(date) {
	      return date ? new Date(date).getTime() + parseInt(this.limitOnlineSeconds) * 1000 : null;
	    }
	  }, {
	    key: "checkOnline",
	    value: function checkOnline() {
	      var _this2 = this;

	      this.online = this.online.filter(function (user) {
	        return user && (user.offline_date > +new Date() || user.id === _this2.currentUserId);
	      });
	      var prevCounter = this.counter;

	      if (this.online.length > 0) {
	        this.counter = this.online.map(function (el) {
	          return 1;
	        }).reduce(function (count) {
	          return count + 1;
	        });
	      } else {
	        this.counter = 0;
	      }

	      if (this.checkNewDay() || prevCounter !== this.counter) {
	        this.redrawOnline();
	      }
	    }
	  }, {
	    key: "checkNewDay",
	    value: function checkNewDay() {
	      var now = new Date();
	      var today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).valueOf();

	      if (this.currentDate < today) //new day
	        {
	          this.maxOnlineUserCountToday = this.online.length;

	          if (this.isTimemanAvailable) {
	            this.checkTimeman();
	          }

	          this.currentDate = today;
	          return true;
	        }

	      return false;
	    }
	  }, {
	    key: "checkTimeman",
	    value: function checkTimeman() {
	      BX.ajax.runComponentAction(this.componentName, "checkTimeman", {
	        signedParameters: this.signedParameters,
	        mode: 'class'
	      }).then(function (response) {
	        if (response.data) {
	          this.redrawTimeman(response.data);
	        }
	      }.bind(this), function (response) {}.bind(this));
	    }
	  }, {
	    key: "setUserOnline",
	    value: function setUserOnline(params) {
	      var _this3 = this;

	      var userId = parseInt(params.id);
	      this.findUser(userId).then(function (user) {
	        if (typeof params.last_activity_date !== "undefined") {
	          user.last_activity_date = params.last_activity_date;
	        }

	        _this3.setUserToLocal(user);

	        _this3.checkOnline();
	      }).catch(function (error) {});
	    }
	  }, {
	    key: "setUserToLocal",
	    value: function setUserToLocal(user) {
	      user.offline_date = this.getOfflineDate(user.last_activity_date);
	      this.users = this.users.filter(function (element) {
	        return element.id !== user.id;
	      });
	      this.users.push(user);
	      var isUserOnline = false;
	      this.online.forEach(function (element) {
	        if (element.id === user.id) {
	          isUserOnline = true;
	        }
	      });

	      if (!isUserOnline) {
	        this.online.unshift(user);
	      }

	      if (this.allOnlineUserIdToday.indexOf(user.id) === -1) {
	        this.allOnlineUserIdToday.push(user.id);
	        this.maxOnlineUserCountToday++;
	      }
	    }
	  }, {
	    key: "setUserOnlineMultiply",
	    value: function setUserOnlineMultiply(list) {
	      var _this4 = this;

	      var requestUserList = [];
	      var counterFindUser = 0;
	      var promises = [];
	      list.forEach(function (user) {
	        var userId = parseInt(user.id);
	        promises.push(new Promise(function (resolve, reject) {
	          _this4.findUser(userId, true).then(function (user) {
	            counterFindUser++;

	            _this4.setUserToLocal(user);

	            resolve();
	          }).catch(function (error) {
	            requestUserList.push(userId);
	            resolve();
	          });
	        }));
	      });
	      Promise.all(promises).then(function () {
	        if (requestUserList.length <= 0) {
	          return false;
	        }

	        var requestCount = _this4.maxUserToShow - counterFindUser;

	        if (requestCount <= 0) {
	          return true;
	        }

	        requestUserList = requestUserList.slice(0, requestCount);
	        BX.rest.callMethod('im.user.list.get', {
	          ID: requestUserList
	        }).then(function (result) {
	          var collection = result.data();

	          if (!collection) {
	            return false;
	          }

	          for (var userId in collection) {
	            if (!collection.hasOwnProperty(userId)) {
	              continue;
	            }

	            var userData = collection[userId];

	            if (!userData) {
	              continue;
	            }

	            var user = {};
	            user.id = parseInt(userData.id);
	            user.name = userData.name;
	            user.avatar = BX.MessengerCommon.isBlankAvatar(userData.avatar) ? '' : userData.avatar;
	            user.last_activity_date = userData.last_activity_date;

	            _this4.setUserToLocal(user);
	          }
	        }).catch(function (error) {});
	      });
	    }
	  }, {
	    key: "findUser",
	    value: function findUser(userId) {
	      var _this5 = this;

	      var skipRest = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      userId = parseInt(userId);
	      return new Promise(function (resolve, reject) {
	        var user = null;
	        user = _this5.users.find(function (element) {
	          return element.id === userId;
	        });

	        if (user) {
	          resolve(user);
	          return true;
	        }

	        user = _this5.getUserFromMessenger(userId);

	        if (user) {
	          resolve(user);
	          return true;
	        }

	        _this5.getUserFromDb(userId).then(function (user) {
	          resolve(user);
	          return true;
	        }).catch(function (error) {
	          if (skipRest) {
	            reject(null);
	            return true;
	          }

	          _this5.getUserFromServer(userId).then(function (user) {
	            _this5.addUserToDb(user);

	            resolve(user);
	          }).catch(function (error) {
	            reject(null);
	          });
	        });

	        return true;
	      });
	    }
	  }, {
	    key: "getUserFromMessenger",
	    value: function getUserFromMessenger(userId) {
	      if (typeof window.BX.MessengerCommon === 'undefined') {
	        return null;
	      }

	      var result = BX.MessengerCommon.getUser(userId);

	      if (!result) {
	        return null;
	      }

	      var user = {
	        id: parseInt(result.id),
	        name: result.name,
	        avatar: BX.MessengerCommon.isBlankAvatar(result.avatar) ? '' : result.avatar,
	        last_activity_date: null
	      };

	      if (result.last_activity_date instanceof Date) {
	        user.last_activity_date = result.last_activity_date.toISOString();
	      }

	      if (typeof result.last_activity_date === 'string') {
	        user.last_activity_date = result.last_activity_date;
	      }

	      return user;
	    }
	  }, {
	    key: "getUserFromDb",
	    value: function getUserFromDb(userId) {
	      var _this6 = this;

	      return new Promise(function (resolve, reject) {
	        BX.indexedDB.getValue(_this6.ITEMS.obClientDb, "users", "U" + userId).then(function (user) {
	          if (user && babelHelpers.typeof(user) === 'object') {
	            if (user.hasOwnProperty("entityId")) {
	              user.id = user.entityId;
	            }

	            resolve(user);
	          } else {
	            resolve(null);
	          }
	        }).catch(function (error) {
	          reject(null);
	        });
	      });
	    }
	  }, {
	    key: "addUserToDb",
	    value: function addUserToDb(user) {
	      user.id = "U" + user.id;
	      BX.indexedDB.addValue(this.ITEMS.obClientDb, 'users', user);
	    }
	  }, {
	    key: "getUserFromServer",
	    value: function getUserFromServer(userId) {
	      return new Promise(function (resolve, reject) {
	        if (typeof window.BX.MessengerCommon === 'undefined') {
	          resolve(null);
	          return false;
	        }

	        rest_client.rest.callMethod('im.user.get', {
	          id: userId
	        }).then(function (result) {
	          if (result.data()) {
	            var user = {};
	            user.id = parseInt(result.data().id);
	            user.name = result.data().name;
	            user.avatar = BX.MessengerCommon.isBlankAvatar(result.data().avatar) ? '' : result.data().avatar;
	            user.last_activity_date = result.data().last_activity_date;
	            user.isExtranet = result.data().extranet ? "Y" : "N";
	            user.active = "Y";
	            user.entityId = parseInt(result.data().id);
	            resolve(user);
	          } else {
	            resolve(null);
	          }
	        }).catch(function (error) {
	          resolve(null);
	        });
	      });
	    }
	  }, {
	    key: "subscribePullEvent",
	    value: function subscribePullEvent() {
	      var _this7 = this;

	      pull_client.PULL.subscribe({
	        type: pull_client.PullClient.SubscriptionType.Online,
	        callback: function callback(data) {
	          if (data.command === 'userStatus') {
	            for (var userId in data.params.users) {
	              if (!data.params.users.hasOwnProperty(userId)) {
	                continue;
	              }

	              _this7.setUserOnline(data.params.users[userId]);
	            }
	          }
	          /*else if (data.command === 'list')
	          {
	          	let list = [];
	          		for (let userId in data.params.users)
	          	{
	          		if (data.params.users.hasOwnProperty(userId))
	          		{
	          			list.push({
	          				id: data.params.users[userId].id,
	          				last_activity_date: data.params.users[userId].last_activity_date
	          			});
	          		}
	          	}
	          		this.setUserOnlineMultiply(list);
	          }*/

	        }
	      });
	      pull_client.PULL.subscribe({
	        moduleId: 'intranet',
	        command: 'timemanDayInfo',
	        callback: function callback(data) {
	          _this7.redrawTimeman(data);
	        }
	      });
	    }
	  }, {
	    key: "redrawTimeman",
	    value: function redrawTimeman(data) {
	      if (data.hasOwnProperty("OPENED")) {
	        var openedNode = document.querySelector('.js-ustat-online-timeman-opened');

	        if (BX.type.isDomNode(openedNode)) {
	          openedNode.innerHTML = data["OPENED"];
	        }
	      }

	      if (data.hasOwnProperty("CLOSED")) {
	        var closedNode = document.querySelector('.js-ustat-online-timeman-closed');

	        if (BX.type.isDomNode(closedNode)) {
	          closedNode.innerHTML = data["CLOSED"];
	        }
	      }

	      this.resizeTimemanText();
	    }
	  }, {
	    key: "resizeTimemanText",
	    value: function resizeTimemanText() {
	      if (!main_core.Type.isDomNode(this.timemanNode)) {
	        return;
	      }

	      var textSum = 0;
	      var valueSum = 0;

	      if (main_core.Type.isArrayLike(this.timemanTextNodes)) {
	        this.timemanTextNodes.forEach(function (text) {
	          var textItems = text.textContent.length;
	          textSum += textItems;
	        });
	      }

	      if (main_core.Type.isArrayLike(this.timemanValueNodes)) {
	        this.timemanValueNodes.forEach(function (value) {
	          var valueItems = value.textContent.length;
	          valueSum += valueItems;
	        });
	      }

	      if (textSum >= 17 && valueSum >= 6 || textSum >= 19 && valueSum >= 4) {
	        main_core.Dom.addClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
	      } else {
	        main_core.Dom.removeClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
	      }
	    }
	  }, {
	    key: "redrawOnline",
	    value: function redrawOnline() {
	      this.showCircleAnimation(this.circleNode, this.counter, this.maxOnlineUserCountToday);
	      this.renderAllUser();
	    }
	  }, {
	    key: "renderAllUser",
	    value: function renderAllUser() {
	      var _this8 = this;

	      var renderedUserIds = [];
	      var newUserIds = [];
	      this.online.forEach(function (item) {
	        newUserIds.push(parseInt(item.id));
	      });
	      var renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");

	      if (renderedUserNodes) {
	        for (var item in renderedUserNodes) {
	          if (!renderedUserNodes.hasOwnProperty(item)) {
	            continue;
	          }

	          var renderedItemId = parseInt(renderedUserNodes[item].getAttribute("data-user-id"));

	          if (newUserIds.indexOf(renderedItemId) === -1) {
	            if (main_core.Type.isDomNode(renderedUserNodes[item])) {
	              main_core.Dom.remove(renderedUserNodes[item]); //remove offline avatars
	            }
	          } else {
	            renderedUserIds.push(parseInt(renderedItemId));
	          }
	        }
	      }

	      renderedUserNodes = this.userBlockNode.querySelectorAll(".js-ustat-online-user");
	      var renderedUserCount = renderedUserNodes.length;
	      this.online.forEach(function (item, i) {
	        if (i >= _this8.maxUserToShow || !item.hasOwnProperty("id")) {
	          return;
	        }

	        if (renderedUserIds.indexOf(item.id) === -1) {
	          if (renderedUserCount < _this8.maxUserToShow) {
	            _this8.renderUser(item);

	            renderedUserCount++;
	          } else {
	            var element = _this8.userBlockNode.querySelector(".js-ustat-online-user");

	            if (main_core.Type.isDomNode(element)) {
	              var removedUserId = parseInt(element.getAttribute("data-user-id"));
	              main_core.Dom.remove(element);

	              _this8.renderUser(item);

	              renderedUserIds = renderedUserIds.filter(function (id) {
	                return id !== removedUserId;
	              });
	              renderedUserIds.push(item.id);
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "renderUser",
	    value: function renderUser(user) {
	      if (!user || babelHelpers.typeof(user) !== 'object') {
	        return;
	      }

	      var userStyle = "";

	      if (user.avatar) {
	        userStyle = 'background-image: url("' + user.avatar + '");';
	      }

	      this.userItem = BX.create('span', {
	        attrs: {
	          className: 'ui-icon ui-icon-common-user intranet-ustat-online-icon intranet-ustat-online-icon-show js-ustat-online-user',
	          "data-user-id": user.id
	        },
	        children: [BX.create('i', {
	          attrs: {
	            style: userStyle
	          }
	        })]
	      });
	      this.userBlockNode.appendChild(this.userInnerBlockNode);
	      this.userInnerBlockNode.appendChild(this.userItem);
	    }
	  }, {
	    key: "showCircleAnimation",
	    value: function showCircleAnimation(circleNode, currentUserOnlineCount, maxUserOnlineCount) {
	      maxUserOnlineCount = parseInt(maxUserOnlineCount);
	      currentUserOnlineCount = parseInt(currentUserOnlineCount);

	      if (currentUserOnlineCount <= 0) {
	        currentUserOnlineCount = 1;
	      }

	      if (currentUserOnlineCount > maxUserOnlineCount) {
	        maxUserOnlineCount = currentUserOnlineCount;
	      }

	      var progressPercent = currentUserOnlineCount * 100 / maxUserOnlineCount;

	      if (!this.circle) {
	        this.circle = new BX.UI.Graph.Circle(circleNode, 68, progressPercent, currentUserOnlineCount);
	        this.circle.show();
	      } else {
	        this.circle.updateCounter(progressPercent, currentUserOnlineCount);
	      }
	    }
	  }]);
	  return UstatOnline;
	}();

	namespace.UstatOnline = UstatOnline;

}((this.BX.Intranet.UstatOnline = this.BX.Intranet.UstatOnline || {}),BX,BX,BX));
//# sourceMappingURL=script.js.map
