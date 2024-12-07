/* eslint-disable */
this.BX = this.BX || {};
(function (exports,socialnetwork_common,main_core_events,main_core,main_popup) {
	'use strict';

	var Scrum = /*#__PURE__*/function () {
	  function Scrum(params) {
	    babelHelpers.classCallCheck(this, Scrum);
	    this.scrumMeetings = null;
	    this.scrumMethodology = null;
	    this.init(params);
	  }
	  babelHelpers.createClass(Scrum, [{
	    key: "init",
	    value: function init(params) {
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.urls = main_core.Type.isPlainObject(params.urls) ? params.urls : {};
	      var scrumMeetingsButton = document.getElementById('tasks-scrum-meetings-button');
	      if (scrumMeetingsButton) {
	        scrumMeetingsButton.addEventListener('click', this.showScrumMeetings.bind(this));
	      }
	      var scrumMethodologyButton = document.getElementById('tasks-scrum-methodology-button');
	      if (scrumMethodologyButton) {
	        scrumMethodologyButton.addEventListener('click', this.showScrumMethodology.bind(this));
	      }
	    }
	  }, {
	    key: "showScrumMeetings",
	    value: function showScrumMeetings(event) {
	      var _this = this;
	      event.target.classList.add('ui-btn-wait');
	      main_core.Runtime.loadExtension('tasks.scrum.meetings').then(function (exports) {
	        var Meetings = exports.Meetings;
	        if (_this.scrumMeetings === null) {
	          _this.scrumMeetings = new Meetings({
	            groupId: _this.groupId
	          });
	        }
	        _this.scrumMeetings.showMenu(event.target);
	        event.target.classList.remove('ui-btn-wait');
	      });
	      event.preventDefault();
	    }
	  }, {
	    key: "showScrumMethodology",
	    value: function showScrumMethodology(event) {
	      var _this2 = this;
	      event.target.classList.add('ui-btn-wait');
	      main_core.Runtime.loadExtension('tasks.scrum.methodology').then(function (exports) {
	        var Methodology = exports.Methodology;
	        if (_this2.scrumMethodology === null) {
	          _this2.scrumMethodology = new Methodology({
	            groupId: _this2.groupId,
	            teamSpeedPath: _this2.urls.ScrumTeamSpeed,
	            burnDownPath: _this2.urls.ScrumBurnDown,
	            pathToTask: _this2.urls.TasksTask
	          });
	        }
	        _this2.scrumMethodology.showMenu(event.target);
	        event.target.classList.remove('ui-btn-wait');
	      });
	      event.preventDefault();
	    }
	  }]);
	  return Scrum;
	}();

	var Widget = /*#__PURE__*/function () {
	  function Widget(params) {
	    babelHelpers.classCallCheck(this, Widget);
	    this.projectWidgetInstance = null;
	    this.init(params);
	  }
	  babelHelpers.createClass(Widget, [{
	    key: "init",
	    value: function init(params) {
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.avatarPath = main_core.Type.isStringFilled(params.avatarPath) ? params.avatarPath : '';
	      this.avatarType = main_core.Type.isStringFilled(params.avatarType) ? params.avatarType : '';
	      this.projectTypeCode = main_core.Type.isStringFilled(params.projectTypeCode) ? params.projectTypeCode : '';
	      this.canModify = main_core.Type.isBoolean(params.canModify) ? params.canModify : false;
	      this.editFeaturesAllowed = main_core.Type.isBoolean(params.editFeaturesAllowed) ? params.editFeaturesAllowed : true;
	      this.urls = main_core.Type.isPlainObject(params.urls) ? params.urls : {};
	      var projectWidgetButton = document.getElementById('project-widget-button');
	      if (projectWidgetButton) {
	        projectWidgetButton.addEventListener('click', this.showProjectWidget.bind(this));
	      }
	    }
	  }, {
	    key: "showProjectWidget",
	    value: function showProjectWidget(event) {
	      if (this.projectWidgetInstance === null) {
	        this.projectWidgetInstance = new socialnetwork_common.WorkgroupWidget({
	          groupId: this.groupId,
	          avatarPath: this.avatarPath,
	          avatarType: this.avatarType,
	          projectTypeCode: this.projectTypeCode,
	          perms: {
	            canModify: this.canModify
	          },
	          urls: {
	            card: this.urls.Card,
	            members: this.urls.GroupUsers,
	            features: this.urls.Features
	          },
	          editRolesAllowed: this.editFeaturesAllowed
	        });
	      }
	      this.projectWidgetInstance.show(event.target);
	      if (this.projectWidgetInstance.widget && this.projectWidgetInstance.widget.getPopup()) {
	        BX.UI.Hint.init(this.projectWidgetInstance.widget.getPopup().getContentContainer());
	      }
	      event.preventDefault();
	    }
	  }]);
	  return Widget;
	}();

	var ControlButton = /*#__PURE__*/function () {
	  function ControlButton(params) {
	    babelHelpers.classCallCheck(this, ControlButton);
	    this.init(params);
	  }
	  babelHelpers.createClass(ControlButton, [{
	    key: "init",
	    value: function init(params) {
	      var _this = this;
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.inIframe = !main_core.Type.isUndefined(params.inIframe) ? !!params.inIframe : false;
	      var controlButtonContainer = document.getElementById('group-menu-control-button-cont');
	      if (controlButtonContainer) {
	        main_core.Runtime.loadExtension('intranet.control-button').then(function (exports) {
	          var ControlButton = exports.ControlButton;
	          new ControlButton({
	            container: controlButtonContainer,
	            entityType: 'workgroup',
	            entityId: _this.groupId,
	            buttonClassName: "intranet-control-btn-no-hover".concat(_this.inIframe ? ' ui-btn-themes' : '')
	          });
	        });
	      }
	    }
	  }]);
	  return ControlButton;
	}();

	var SonetGroupEvent = /*#__PURE__*/function () {
	  function SonetGroupEvent(params, additionalData) {
	    babelHelpers.classCallCheck(this, SonetGroupEvent);
	    this.moreButtonInstance = !main_core.Type.isUndefined(additionalData.moreButtonInstance) ? additionalData.moreButtonInstance : null;
	    this.init(params);
	  }
	  babelHelpers.createClass(SonetGroupEvent, [{
	    key: "init",
	    value: function init(params) {
	      var _this = this;
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.urls = main_core.Type.isPlainObject(params.urls) ? params.urls : {};
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          sliderEvent = _event$getCompatData2[0];
	        if (sliderEvent.getEventId() === 'sonetGroupEvent') {
	          _this.sonetGroupEventHandler(sliderEvent.getData());
	        }
	      });
	      main_core_events.EventEmitter.subscribe('sonetGroupEvent', function (event) {
	        var _event$getCompatData3 = event.getCompatData(),
	          _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	          eventData = _event$getCompatData4[0];
	        _this.sonetGroupEventHandler(eventData);
	      });
	    }
	  }, {
	    key: "sonetGroupEventHandler",
	    value: function sonetGroupEventHandler(eventData) {
	      if (!main_core.Type.isStringFilled(eventData.code)) {
	        return;
	      }
	      if (['afterJoinRequestSend', 'afterEdit'].includes(eventData.code)) {
	        var joinContainerNode = document.getElementById('bx-group-menu-join-cont');
	        if (joinContainerNode) {
	          joinContainerNode.style.display = 'none';
	        }
	        socialnetwork_common.Common.reload();
	      } else if (['afterSetFavorites'].includes(eventData.code)) {
	        var sonetGroupMenu = socialnetwork_common.GroupMenu.getInstance();
	        var favoritesValue = sonetGroupMenu.favoritesValue;
	        sonetGroupMenu.setItemTitle(!favoritesValue);
	        sonetGroupMenu.favoritesValue = !favoritesValue;
	      } else if (['afterDelete', 'afterLeave'].includes(eventData.code) && main_core.Type.isPlainObject(eventData.data) && !main_core.Type.isUndefined(eventData.data.groupId) && Number(eventData.data.groupId) === this.groupId) {
	        top.location.href = this.urls.GroupsList;
	      } else if (['afterSetSubscribe'].includes(eventData.code) && main_core.Type.isPlainObject(eventData.data) && !main_core.Type.isUndefined(eventData.data.groupId) && Number(eventData.data.groupId) === this.groupId && this.moreButtonInstance) {
	        this.moreButtonInstance.redrawMenu(eventData.data.value);
	      }
	    }
	  }]);
	  return SonetGroupEvent;
	}();

	var JoinButton = /*#__PURE__*/function () {
	  function JoinButton(params) {
	    babelHelpers.classCallCheck(this, JoinButton);
	    this.init(params);
	  }
	  babelHelpers.createClass(JoinButton, [{
	    key: "init",
	    value: function init(params) {
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.urls = main_core.Type.isPlainObject(params.urls) ? params.urls : {};
	      var joinButtonNode = document.getElementById('bx-group-menu-join');
	      if (joinButtonNode) {
	        joinButtonNode.addEventListener('click', this.sendJoinRequest.bind(this));
	      }
	    }
	  }, {
	    key: "sendJoinRequest",
	    value: function sendJoinRequest(event) {
	      var _this = this;
	      var button = event.currentTarget;
	      socialnetwork_common.Common.showButtonWait(button);
	      main_core.ajax.runAction('socialnetwork.api.usertogroup.join', {
	        data: {
	          params: {
	            groupId: this.groupId
	          }
	        }
	      }).then(function (response) {
	        socialnetwork_common.Common.hideButtonWait(button);
	        if (response.data.success && main_core.Type.isStringFilled(_this.urls.view)) {
	          var sonetGroupEventData = {
	            code: 'afterJoinRequestSend',
	            data: {
	              groupId: _this.groupId
	            }
	          };
	          main_core_events.EventEmitter.emit(window.top, 'sonetGroupEvent', new main_core_events.BaseEvent({
	            compatData: [sonetGroupEventData],
	            data: [sonetGroupEventData]
	          }));
	          window.location.href = _this.urls.view;
	        }
	      }, function () {
	        socialnetwork_common.Common.hideButtonWait(button);
	      });
	    }
	  }]);
	  return JoinButton;
	}();

	var TaskEvent = /*#__PURE__*/function () {
	  function TaskEvent(params) {
	    babelHelpers.classCallCheck(this, TaskEvent);
	    this.init(params);
	  }
	  babelHelpers.createClass(TaskEvent, [{
	    key: "init",
	    value: function init(params) {
	      var _this = this;
	      this.pageId = main_core.Type.isStringFilled(params.pageId) ? params.pageId : '';
	      this.currentUserId = !main_core.Type.isUndefined(params.currentUserId) ? Number(params.currentUserId) : 0;
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.isRoleControlDisabled = !main_core.Type.isUndefined(params.isRoleControlDisabled) ? Boolean(params.isRoleControlDisabled) : false;
	      var compatMode = {
	        compatMode: true
	      };
	      main_core_events.EventEmitter.subscribe('onPullEvent-tasks', function (command, params) {
	        if (command === 'user_counter') {
	          _this.onUserCounter(params);
	        }
	      }, compatMode);
	      if (this.pageId !== 'group_tasks') {
	        return;
	      }
	      document.querySelectorAll('.tasks_role_link').forEach(function (element) {
	        element.addEventListener('click', _this.onTaskMenuItemClick.bind(_this));
	      });
	      main_core_events.EventEmitter.subscribe('BX.Main.Filter:apply', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 3),
	          filterId = _event$getCompatData2[0],
	          data = _event$getCompatData2[1],
	          ctx = _event$getCompatData2[2];
	        _this.onFilterApply(filterId, data, ctx);
	      });
	    }
	  }, {
	    key: "onTaskMenuItemClick",
	    value: function onTaskMenuItemClick(event) {
	      var element = event.currentTarget;
	      event.preventDefault();
	      var roleId = element.dataset.id === 'view_all' ? '' : element.dataset.id;
	      var url = element.dataset.url;
	      main_core_events.EventEmitter.emit('Tasks.TopMenu:onItem', new main_core_events.BaseEvent({
	        compatData: [roleId, url],
	        data: [roleId, url]
	      }));
	      document.querySelectorAll('.tasks_role_link').forEach(function (element) {
	        element.classList.remove('main-buttons-item-active');
	      });
	      element.classList.add('main-buttons-item-active');
	    }
	  }, {
	    key: "onUserCounter",
	    value: function onUserCounter(data) {
	      var _this2 = this;
	      if (this.currentUserId !== Number(data.userId) || !Object.prototype.hasOwnProperty.call(data, this.groupId)) {
	        return;
	      }
	      Object.keys(data[this.groupId]).forEach(function (role) {
	        var roleButton = document.getElementById("group_panel_menu_".concat(_this2.groupId ? _this2.groupId + '_' : '').concat(role));
	        if (roleButton) {
	          roleButton.querySelector('.main-buttons-item-counter').innerText = _this2.getCounterValue(data[_this2.groupId][role].total);
	        }
	      });
	    }
	  }, {
	    key: "getCounterValue",
	    value: function getCounterValue(value) {
	      if (!value) {
	        return '';
	      }
	      var maxValue = 99;
	      return value > maxValue ? "".concat(maxValue, "+") : value;
	    }
	  }, {
	    key: "onFilterApply",
	    value: function onFilterApply(filterId, data, ctx) {
	      if (this.isRoleControlDisabled) {
	        return;
	      }
	      var roleId = ctx.getFilterFieldsValues().ROLEID;
	      document.querySelectorAll('.tasks_role_link').forEach(function (element) {
	        element.classList.remove('main-buttons-item-active');
	      });
	      if (main_core.Type.isUndefined(roleId) || !roleId) {
	        roleId = 'view_all';
	      }
	      var panelMenuNode = document.getElementById("group_panel_menu_".concat(this.groupId, "_").concat(roleId));
	      if (panelMenuNode) {
	        panelMenuNode.classList.add('main-buttons-item-active');
	      }
	    }
	  }]);
	  return TaskEvent;
	}();

	var MoreButton = /*#__PURE__*/function () {
	  function MoreButton(params) {
	    babelHelpers.classCallCheck(this, MoreButton);
	    this.menu = null;
	    this["class"] = {
	      activeItem: 'menu-popup-item-sgm-accept-sm',
	      inactiveItem: 'menu-popup-item-sgm-empty-sm'
	    };
	    this.init(params);
	    return this;
	  }
	  babelHelpers.createClass(MoreButton, [{
	    key: "init",
	    value: function init(params) {
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.bindingMenuItems = main_core.Type.isObject(params.bindingMenuItems) ? Object.values(params.bindingMenuItems) : [];
	      this.userIsMember = main_core.Type.isBoolean(params.userIsMember) ? params.userIsMember : false;
	      this.subscribedValue = main_core.Type.isBoolean(params.subscribedValue) ? params.subscribedValue : false;
	      var moreButton = document.getElementById('group-menu-more-button');
	      if (!moreButton) {
	        return;
	      }
	      moreButton.addEventListener('click', this.showMoreMenu.bind(this));
	    }
	  }, {
	    key: "showMoreMenu",
	    value: function showMoreMenu(event) {
	      var _this = this;
	      event.preventDefault();
	      var bindingMenu = [];
	      this.bindingMenuItems.forEach(function (item) {
	        bindingMenu.push(item);
	      });
	      var menu = [];
	      if (this.userIsMember) {
	        menu.push({
	          id: 'subscribe',
	          text: main_core.Loc.getMessage('SONET_SGM_T_MORE_MENU_SUBSCRIBE'),
	          className: this.subscribedValue ? this["class"].activeItem : this["class"].inactiveItem,
	          onclick: function onclick() {
	            _this.setSubscription(true);
	          }
	        });
	        menu.push({
	          id: 'unsubscribe',
	          text: main_core.Loc.getMessage('SONET_SGM_T_MORE_MENU_UNSUBSCRIBE'),
	          className: !this.subscribedValue ? this["class"].activeItem : this["class"].inactiveItem,
	          onclick: function onclick() {
	            _this.setSubscription(false);
	          }
	        });
	      }
	      if (bindingMenu.length > 0) {
	        if (menu.length > 0) {
	          menu.push({
	            delimiter: true
	          });
	        }
	        menu.push({
	          text: main_core.Loc.getMessage('SONET_SGM_T_MORE_MENU_BINDING'),
	          items: bindingMenu
	        });
	      }
	      if (menu.length <= 0) {
	        return;
	      }
	      var bindElement = event.target;
	      this.menu = main_popup.MenuManager.create({
	        id: 'group-more-menu',
	        offsetTop: 5,
	        offsetLeft: bindElement.offsetWidth - 18,
	        angle: true,
	        items: menu,
	        events: {
	          onPopupClose: function onPopupClose() {
	            if (bindElement.tagName === 'BUTTON') {
	              bindElement.classList.remove('ui-btn-active');
	            }
	          }
	        },
	        subMenuOptions: {}
	      });
	      this.menu.popupWindow.setBindElement(bindElement);
	      this.menu.popupWindow.show();
	    }
	  }, {
	    key: "setSubscription",
	    value: function setSubscription(value) {
	      var _this2 = this;
	      this.redrawMenu(value);
	      main_core.ajax.runAction('socialnetwork.api.workgroup.setSubscription', {
	        data: {
	          params: {
	            groupId: this.groupId,
	            value: value ? 'Y' : 'N'
	          }
	        }
	      }).then(function (data) {
	        var eventData = {
	          code: 'afterSetSubscribe',
	          data: {
	            groupId: _this2.groupId,
	            value: data.RESULT === 'Y'
	          }
	        };
	        window.top.BX.SidePanel.Instance.postMessageAll(window, 'sonetGroupEvent', eventData);
	      })["catch"](function () {
	        _this2.redrawMenu(!value);
	      });
	    }
	  }, {
	    key: "redrawMenu",
	    value: function redrawMenu(value) {
	      if (!this.menu) {
	        return;
	      }
	      var activeItem = this.menu.getMenuItem(value ? 'subscribe' : 'unsubscribe');
	      var inactiveItem = this.menu.getMenuItem(value ? 'unsubscribe' : 'subscribe');
	      if (activeItem) {
	        activeItem.layout.item.classList.remove(this["class"].inactiveItem);
	        activeItem.layout.item.classList.add(this["class"].activeItem);
	      }
	      if (inactiveItem) {
	        inactiveItem.layout.item.classList.remove(this["class"].activeItem);
	        inactiveItem.layout.item.classList.add(this["class"].inactiveItem);
	      }
	    }
	  }]);
	  return MoreButton;
	}();

	var GroupMenu = /*#__PURE__*/function () {
	  function GroupMenu(params) {
	    babelHelpers.classCallCheck(this, GroupMenu);
	    this.initialized = false;
	    this.moreButtonInstance = null;
	    this.init(params);
	  }
	  babelHelpers.createClass(GroupMenu, [{
	    key: "init",
	    value: function init(params) {
	      if (this.initialized === true) {
	        return;
	      }
	      this.initialized = true;
	      this.pageId = main_core.Type.isStringFilled(params.pageId) ? params.pageId : '';
	      this.currentUserId = !main_core.Type.isUndefined(params.currentUserId) ? Number(params.currentUserId) : 0;
	      this.groupId = !main_core.Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
	      this.groupType = main_core.Type.isStringFilled(params.groupType) ? params.groupType : '';
	      this.projectTypeCode = main_core.Type.isStringFilled(params.projectTypeCode) ? params.projectTypeCode : '';
	      this.userRole = main_core.Type.isStringFilled(params.userRole) ? params.userRole : '';
	      this.userIsMember = main_core.Type.isBoolean(params.userIsMember) ? params.userIsMember : false;
	      this.userIsAutoMember = main_core.Type.isBoolean(params.userIsAutoMember) ? params.userIsAutoMember : false;
	      this.userIsScrumMaster = main_core.Type.isBoolean(params.userIsScrumMaster) ? params.userIsScrumMaster : false;
	      this.isProject = main_core.Type.isBoolean(params.isProject) ? params.isProject : false;
	      this.isScrumProject = main_core.Type.isBoolean(params.isScrumProject) ? params.isScrumProject : false;
	      this.isOpened = main_core.Type.isBoolean(params.isOpened) ? params.isOpened : false;
	      this.favoritesValue = main_core.Type.isBoolean(params.favoritesValue) ? params.favoritesValue : false;
	      this.canInitiate = main_core.Type.isBoolean(params.canInitiate) ? params.canInitiate : false;
	      this.canModify = main_core.Type.isBoolean(params.canModify) ? params.canModify : false;
	      this.canProcessRequestsIn = main_core.Type.isBoolean(params.canProcessRequestsIn) ? params.canProcessRequestsIn : false;
	      this.canPickTheme = main_core.Type.isBoolean(params.canPickTheme) ? params.canPickTheme : false;
	      this.avatarPath = main_core.Type.isStringFilled(params.avatarPath) ? params.avatarPath : '';
	      this.avatarType = main_core.Type.isStringFilled(params.avatarType) ? params.avatarType : '';
	      this.urls = main_core.Type.isPlainObject(params.urls) ? params.urls : {};
	      this.editFeaturesAllowed = main_core.Type.isBoolean(params.editFeaturesAllowed) ? params.editFeaturesAllowed : true;
	      this.copyFeatureAllowed = main_core.Type.isBoolean(params.copyFeatureAllowed) ? params.copyFeatureAllowed : true;
	      new JoinButton(params);
	      new ControlButton(params);
	      new Scrum(params);
	      new Widget(params);
	      new TaskEvent(params);
	      this.moreButtonInstance = new MoreButton(params);
	      new SonetGroupEvent(params, {
	        moreButtonInstance: this.moreButtonInstance
	      });
	      var settingsButtonNode = document.getElementById('bx-group-menu-settings');
	      if (settingsButtonNode) {
	        var sonetGroupMenu = socialnetwork_common.GroupMenu.getInstance();
	        sonetGroupMenu.favoritesValue = this.favoritesValue;
	        settingsButtonNode.addEventListener('click', this.showMenu.bind(this));
	      }
	    }
	  }, {
	    key: "showMenu",
	    value: function showMenu(event) {
	      socialnetwork_common.Common.showGroupMenuPopup({
	        bindElement: event.currentTarget,
	        groupId: this.groupId,
	        groupType: this.groupType,
	        userRole: this.userRole,
	        userIsMember: this.userIsMember,
	        userIsAutoMember: this.userIsAutoMember,
	        userIsScrumMaster: this.userIsScrumMaster,
	        isProject: this.isProject,
	        isScrumProject: this.isScrumProject,
	        isOpened: this.isOpened,
	        editFeaturesAllowed: this.editFeaturesAllowed,
	        copyFeatureAllowed: this.copyFeatureAllowed,
	        canPickTheme: this.canPickTheme,
	        perms: {
	          canInitiate: this.canInitiate,
	          canProcessRequestsIn: this.canProcessRequestsIn,
	          canModify: this.canModify
	        },
	        urls: {
	          requestUser: main_core.Type.isStringFilled(this.urls.Invite) ? this.urls.Invite : "".concat(this.urls.Edit).concat(this.urls.Edit.indexOf('?') >= 0 ? '&' : '?', "tab=invite"),
	          edit: "".concat(this.urls.Edit).concat(this.urls.Edit.indexOf('?') >= 0 ? '&' : '?', "tab=edit"),
	          "delete": this.urls.Delete,
	          features: this.urls.Features,
	          members: this.urls.GroupUsers,
	          requests: this.urls.GroupRequests,
	          requestsOut: this.urls.GroupRequestsOut,
	          userRequestGroup: this.urls.UserRequestGroup,
	          userLeaveGroup: this.urls.UserLeaveGroup,
	          copy: this.urls.Copy
	        }
	      });
	      event.preventDefault();
	    }
	  }]);
	  return GroupMenu;
	}();

	exports.GroupMenu = GroupMenu;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.Socialnetwork.UI,BX.Event,BX,BX.Main));
//# sourceMappingURL=script.js.map
