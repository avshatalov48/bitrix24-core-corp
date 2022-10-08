(function (exports,ui_notification,main_popup,main_core,main_core_events,main_loader) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	var namespace = main_core.Reflection.namespace('BX.Crm');
	var DedupeAutosearch = /*#__PURE__*/function () {
	  function DedupeAutosearch() {
	    babelHelpers.classCallCheck(this, DedupeAutosearch);
	    this._componentName = '';
	    this._componentSignedParams = '';
	    this._entityTypeId = 0;
	    this._instanceId = '';
	    this._internalMergeStatus = '';
	    this._mergeCheckerTimeoutId = null;
	    this._mergerUrl = '';
	    this._dedupeListUrl = '';
	    this._execInterval = null;
	    this._intervalsList = [];
	    this._isDropdownMenuShown = false;
	    this._selectedExecInterval = null;
	    this._selectedExecIntervalNode = null;
	    this._status = '';
	    this._infoHelperId = '';
	    this._progressData = {};
	    this._settingsPopupId = 'autosearch-settings-popup';
	  }

	  babelHelpers.createClass(DedupeAutosearch, [{
	    key: "initialize",
	    value: function initialize(params) {
	      this._componentName = BX.prop.getString(params, 'componentName', '');
	      this._componentSignedParams = BX.prop.getString(params, 'signedParameters', '');
	      this._entityTypeId = BX.prop.getInteger(params, 'entityTypeId', 0);
	      this._instanceId = BX.Text.getRandom(8);
	      this._internalMergeStatus = 'waiting';
	      this._mergerUrl = BX.prop.getString(params, 'mergerUrl', '');
	      this._dedupeListUrl = BX.prop.getString(params, 'dedupeListUrl', '');
	      this._execInterval = BX.prop.getString(params, 'selectedInterval', '0');
	      this._intervalsList = BX.prop.getArray(params, 'intervals', []);
	      this._status = BX.prop.getString(params, 'status', '');
	      this._infoHelperId = BX.prop.getString(params, 'infoHelperId', '');
	      this._progressData = BX.prop.getObject(params, 'progressData', {});
	      this.tryToStartMerge(true);
	      this.subscribeEvents();
	    }
	  }, {
	    key: "subscribeEvents",
	    value: function subscribeEvents() {
	      var _this = this;

	      if (BX.PULL) {
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'crm',
	          command: 'dedupe.autosearch.startMerge',
	          callback: function callback(params) {
	            if (BX.prop.getInteger(params, 'entityTypeId', 0) === _this._entityTypeId) {
	              _this._status = BX.prop.getString(params, 'status', '');
	              _this._progressData = BX.prop.getObject(params, 'progressData', {});

	              if (_this._status === 'MERGING') {
	                var notification = ui_notification.UI.Notification.Center.getBalloonById('crm.autosearch.start_merge');

	                if (notification) {
	                  notification.close();
	                }
	              }

	              _this.tryToStartMerge();
	            }
	          }
	        });
	        BX.PULL.subscribe({
	          type: BX.PullClient.SubscriptionType.Server,
	          moduleId: 'crm',
	          command: 'dedupe.autosearch.mergeComplete',
	          callback: function callback(params) {
	            if (BX.prop.getInteger(params, 'entityTypeId', 0) === _this._entityTypeId) {
	              var data = BX.prop.getObject(params, 'data', {});
	              _this._status = BX.prop.getInteger(data, 'CONFLICT_COUNT', 0) > 0 ? 'CONFLICTS_RESOLVING' : '';
	              clearTimeout(_this._mergeCheckerTimeoutId);

	              _this.showMergeCompleteNotification(data);
	            }
	          }
	        });
	      }

	      main_core_events.EventEmitter.subscribe('onLocalStorageSet', this.onExternalEvent.bind(this));
	    }
	  }, {
	    key: "showSettings",
	    value: function showSettings() {
	      var _this2 = this;

	      this._selectedExecInterval = this._execInterval;
	      var popup = main_popup.PopupManager.create({
	        id: this._settingsPopupId,
	        cacheable: false,
	        titleBar: main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_TITLE'),
	        content: this.needLoadConflictsCount() ? this.getSettingsPopupLoader() : this.getSettingsPopupContent(),
	        closeByEsc: true,
	        closeIcon: true,
	        draggable: true,
	        width: 500,
	        buttons: [new BX.UI.SaveButton({
	          onclick: function onclick() {
	            popup.close();

	            _this2.saveSelectedExecInterval();
	          }
	        }), new BX.UI.CancelButton({
	          onclick: function onclick() {
	            popup.close();
	          }
	        })]
	      });
	      popup.show();

	      if (this.needLoadConflictsCount()) {
	        this.loadConflictsCount().then(function (conflictsCount) {
	          popup.setContent(_this2.getSettingsPopupContent(conflictsCount));
	          popup.adjustPosition();
	        }, function () {
	          popup.setContent(_this2.getSettingsPopupContent(0));
	          popup.adjustPosition();
	        });
	      }
	    }
	  }, {
	    key: "tryToStartMerge",
	    value: function tryToStartMerge() {
	      var immediately = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (this._status === 'READY_TO_MERGE') {
	        this.showStartConfirmation();
	      }

	      if (this._status === 'MERGING') {
	        this.startMerging(immediately ? 1 : this.getShortTimeout());
	      }

	      if (this._status === 'CONFLICTS_RESOLVING') {
	        this.showMergeCompleteNotification(this._progressData);
	      }
	    }
	  }, {
	    key: "showStartConfirmation",
	    value: function showStartConfirmation() {
	      var _this3 = this;

	      if (!BX.prop.getBoolean(this._progressData, 'SHOW_NOTIFICATION', true)) {
	        // message was already shown in this session
	        return;
	      }

	      var entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);
	      var foundItemsCount = BX.prop.getInteger(this._progressData, 'FOUND_ITEMS', 0);
	      var totalEntitiesCount = BX.prop.getInteger(this._progressData, 'TOTAL_ENTITIES', 0);
	      var notificationContent = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span>\n\t\t\t\t", "\n\t\t\t\t<br>\n\t\t\t\t", "\n\t\t\t\t<span class=\"ui-hint notification-hint-inline\">\n\t\t\t\t\t<span class=\"ui-hint-icon\" onclick=\"", "\"></span>\n\t\t\t\t</span>\n\t\t\t</span>"])), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_TEXT_' + entityTypeName).replace('#FOUND_ITEMS_COUNT#', foundItemsCount).replace('#TOTAL_ENTITIES_COUNT#', totalEntitiesCount), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_TEXT'), this.onHintClick.bind(this));
	      ui_notification.UI.Notification.Center.notify({
	        content: notificationContent,
	        autoHide: false,
	        id: 'crm.autosearch.start_merge',
	        width: 600,
	        actions: [{
	          title: main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_START_CONFIRMATION_BUTTON'),
	          events: {
	            click: function click(event, balloon, action) {
	              _this3.showSettings();

	              balloon.close();
	            }
	          }
	        }],
	        events: {
	          onOpen: function onOpen(event) {
	            var balloon = event.getBalloon();
	            BX.UI.Hint.init(balloon.getContainer());
	          }
	        }
	      });
	    }
	  }, {
	    key: "showMergeCompleteNotification",
	    value: function showMergeCompleteNotification(data) {
	      var _this4 = this;

	      if (!BX.prop.getBoolean(data, 'SHOW_NOTIFICATION', true)) {
	        // message was already shown in this session
	        return;
	      }

	      var entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);
	      var successCount = BX.prop.getInteger(data, 'SUCCESS_COUNT', 0);
	      var conflictsCount = BX.prop.getInteger(data, 'CONFLICT_COUNT', 0);

	      if (successCount === 0 && conflictsCount === 0) {
	        return;
	      }

	      var message = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	      var automaticallyFoundText = successCount > 0 ? main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_TEXT_' + entityTypeName).replace('#FOUND_ITEMS_COUNT#', successCount) : main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_EMPTY_RESULTS_' + entityTypeName);
	      main_core.Dom.append(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), automaticallyFoundText), message);
	      var actions = [];
	      var notificationWidth = 400;

	      if (conflictsCount > 0) {
	        main_core.Dom.append(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_CONFLICTED_TEXT').replace('#CONFLICTS_COUNT#', conflictsCount)), message);
	        actions.push({
	          title: main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_RESOLVE_CONFLICT_BUTTON'),
	          events: {
	            click: function click(event, balloon, action) {
	              _this4.openMerger();

	              balloon.close();
	            }
	          }
	        });
	        notificationWidth = 670;
	      }

	      ui_notification.UI.Notification.Center.notify({
	        content: message,
	        autoHide: false,
	        id: 'crm.autosearch.merge_complete',
	        width: notificationWidth,
	        actions: actions
	      });
	    }
	  }, {
	    key: "saveSelectedExecInterval",
	    value: function saveSelectedExecInterval() {
	      this._execInterval = this._selectedExecInterval;
	      BX.ajax.runComponentAction(this._componentName, 'setExecInterval', {
	        mode: 'class',
	        signedParameters: this._componentSignedParams,
	        data: {
	          interval: this._selectedExecInterval
	        }
	      });
	    }
	  }, {
	    key: "startMerging",
	    value: function startMerging(timeout) {
	      var _this5 = this;

	      var p = new Promise(function (resolve, reject) {
	        _this5.askIfNoActiveMerging(timeout, resolve);
	      });
	      p.then(function () {
	        return _this5.doMerge();
	      });
	    }
	  }, {
	    key: "doMerge",
	    value: function doMerge() {
	      var _this6 = this;

	      BX.ajax.runComponentAction(this._componentName, 'merge', {
	        mode: 'class',
	        signedParameters: this._componentSignedParams,
	        data: {
	          mergeId: this._instanceId
	        }
	      }).then(function (response) {
	        var data = BX.prop.getObject(response, "data", {});
	        var instanceId = BX.prop.getString(data, "MERGE_ID", "");

	        if (instanceId === _this6._instanceId) {
	          var status = BX.prop.getString(data, "STATUS", "");

	          if (status !== "COMPLETED") {
	            window.setTimeout(function () {
	              return _this6.doMerge();
	            }, 400);
	          }
	        } else {
	          _this6.startMerging(_this6.getLongTimeout());
	        }
	      }, function (response) {
	        _this6.startMerging(_this6.getShortTimeout());
	      });
	    }
	  }, {
	    key: "getSettingsPopupLoader",
	    value: function getSettingsPopupLoader() {
	      var loaderContainer = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	      loaderContainer.style.height = '180px';
	      var loader = new main_loader.Loader({
	        target: loaderContainer
	      });
	      setTimeout(function () {
	        loader.show();
	      }, 10);
	      return loaderContainer;
	    }
	  }, {
	    key: "getSettingsPopupContent",
	    value: function getSettingsPopupContent(conflictsCount) {
	      var _this7 = this;

	      var selectedExecInterval = this._intervalsList.reduce(function (prev, item) {
	        return item.value === _this7._selectedExecInterval ? item.title : prev;
	      }, '');

	      this._selectedExecIntervalNode = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl-element\">", "</div>"])), selectedExecInterval);
	      return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div>\n\t\t\t\t", "\n\t\t\t\t<p>", "</p>\n\t\t\t\t<div class=\"ui-ctl-label-text\">", "</div>\n\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), this.getConflictsInfo(conflictsCount), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_NOTE'), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_INTERVAL_TITLE'), this.toggleIntervalsList.bind(this), this._selectedExecIntervalNode);
	    }
	  }, {
	    key: "needLoadConflictsCount",
	    value: function needLoadConflictsCount() {
	      return this._status === 'CONFLICTS_RESOLVING';
	    }
	  }, {
	    key: "loadConflictsCount",
	    value: function loadConflictsCount() {
	      return BX.ajax.runComponentAction(this._componentName, 'getStatistic', {
	        mode: 'class',
	        signedParameters: this._componentSignedParams
	      }).then(function (response) {
	        var data = BX.prop.getObject(response, "data", {});
	        return BX.prop.getInteger(data, "conflictsCount", 0);
	      });
	    }
	  }, {
	    key: "getConflictsInfo",
	    value: function getConflictsInfo(conflictsCount) {
	      if (!this.needLoadConflictsCount()) {
	        return '';
	      }

	      if (conflictsCount <= 0) {
	        return '';
	      }

	      return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-alert ui-alert-warning\">\n\t\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span class=\"ui-link\" onclick=\"", "\">", "</span>\n\t\t\t\t</span>\n\t\t\t</div>"])), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_SETTINGS_CONFLICTS_FOUND').replace('#CONFLICTS_COUNT#', conflictsCount), this.onStartMergeButtonClick.bind(this), main_core.Loc.getMessage('CRM_DP_AUTOSEARCH_COMPLETE_RESOLVE_CONFLICT_BUTTON'));
	    }
	  }, {
	    key: "getShortTimeout",
	    value: function getShortTimeout() {
	      return Math.round(Math.random() * 5000) + 500;
	    }
	  }, {
	    key: "getLongTimeout",
	    value: function getLongTimeout() {
	      return Math.floor(Math.random() * 10000) + 30000;
	    }
	  }, {
	    key: "openDedupeList",
	    value: function openDedupeList() {
	      var url = BX.util.add_url_param(this._dedupeListUrl, {
	        'is_automatic': 'yes'
	      });
	      BX.Crm.Page.openSlider(url);
	    }
	  }, {
	    key: "openMerger",
	    value: function openMerger() {
	      var url = BX.util.add_url_param(this._mergerUrl, {
	        'is_automatic': 'yes'
	      });
	      BX.Crm.Page.openSlider(url);
	      this.bindMergerSliderEvent();
	    }
	  }, {
	    key: "toggleIntervalsList",
	    value: function toggleIntervalsList(e) {
	      if (this._isDropdownMenuShown) {
	        this.closeDropdownMenu();
	      } else {
	        this.showDropdownMenu(e.target);
	      }
	    }
	  }, {
	    key: "showDropdownMenu",
	    value: function showDropdownMenu(bindElement) {
	      var _this8 = this;

	      if (this._isDropdownMenuShown || !bindElement) {
	        return;
	      }

	      var menu = [];

	      for (var i = 0; i < this._intervalsList.length; i++) {
	        menu.push({
	          text: this._intervalsList[i].title,
	          value: this._intervalsList[i].value,
	          onclick: this.onSelectInterval.bind(this, this._intervalsList[i].value)
	        });
	      }

	      main_popup.MenuManager.show('autosearch-settings-intervals-dropdown', bindElement, menu, {
	        width: bindElement.offsetWidth,
	        angle: false,
	        cacheable: false,
	        events: {
	          onPopupShow: function onPopupShow() {
	            _this8._isDropdownMenuShown = true;
	          },
	          onPopupClose: function onPopupClose() {
	            _this8._isDropdownMenuShown = false;
	          }
	        }
	      });
	    }
	  }, {
	    key: "closeDropdownMenu",
	    value: function closeDropdownMenu() {
	      if (!this._isDropdownMenuShown) {
	        return;
	      }

	      var menu = main_popup.MenuManager.getMenuById('autosearch-settings-intervals-dropdown');

	      if (menu) {
	        menu.popupWindow.close();
	      }
	    }
	  }, {
	    key: "askIfNoActiveMerging",
	    value: function askIfNoActiveMerging(timeout, callback) {
	      var _this9 = this;

	      clearTimeout(this._mergeCheckerTimeoutId);
	      this._mergeCheckerTimeoutId = setTimeout(function () {
	        _this9._internalMergeStatus = 'ready'; // ask another tabs

	        BX.localStorage.set("BX.Crm.onCrmEntityAutosearchStartMerge", {
	          entityTypeId: _this9._entityTypeId,
	          instanceId: _this9._instanceId
	        }, 5);
	        _this9._mergeCheckerTimeoutId = setTimeout(function () {
	          // if another tabs don't change status
	          if (_this9._internalMergeStatus === 'ready') {
	            // we can start merging
	            _this9._internalMergeStatus = 'merging';
	            callback();
	          } else {
	            // if there is another tab with active merging, try to wait ~30 sec
	            _this9.askIfNoActiveMerging(_this9.getLongTimeout(), callback);
	          }
	        }, 5000);
	      }, timeout);
	    }
	  }, {
	    key: "bindMergerSliderEvent",
	    value: function bindMergerSliderEvent() {
	      var slider = BX.Crm.Page.getTopSlider();

	      if (!slider) {
	        return;
	      }

	      main_core_events.EventEmitter.subscribe(slider, "SidePanel.Slider:onCloseStart", this.onCloseMergeSlider.bind(this));
	    }
	  }, {
	    key: "onExternalEvent",
	    value: function onExternalEvent(event) {
	      var dataArray = event.getData();

	      if (!main_core.Type.isArray(dataArray)) {
	        return;
	      }

	      var data = dataArray[0];
	      var eventName = BX.prop.getString(data, "key", "");

	      if (eventName === "BX.Crm.onCrmEntityAutosearchStartMerge" || eventName === "BX.Crm.onCrmEntityAutosearchMergeStatusNotify") {
	        var value = BX.prop.getObject(data, "value", {});
	        var entityTypeId = BX.prop.getInteger(value, "entityTypeId", 0);

	        if (entityTypeId !== this._entityTypeId) {
	          return;
	        }

	        var instanceId = BX.prop.getString(value, "instanceId", "");

	        if (eventName === "BX.Crm.onCrmEntityAutosearchStartMerge") {
	          if (instanceId !== this._instanceId && this._internalMergeStatus !== 'waiting') // event from another tab
	            {
	              if (instanceId < this._instanceId || this._internalMergeStatus === 'merging') // notify only if current instance is already merging or if current instanceId is lower then another ready candidate
	                {
	                  BX.localStorage.set("BX.Crm.onCrmEntityAutosearchMergeStatusNotify", {
	                    entityTypeId: this._entityTypeId,
	                    instanceId: instanceId,
	                    status: this._internalMergeStatus
	                  }, 5);
	                } else {
	                this._internalMergeStatus = 'waiting';
	              }
	            }
	        }

	        if (eventName === "BX.Crm.onCrmEntityAutosearchMergeStatusNotify") {
	          if (instanceId === this._instanceId) {
	            // another tab canceled this merging
	            this._internalMergeStatus = 'waiting';
	          }
	        }
	      }
	    }
	  }, {
	    key: "onStartMergeButtonClick",
	    value: function onStartMergeButtonClick() {
	      var popup = main_popup.PopupManager.getPopupById(this._settingsPopupId);

	      if (popup && popup.isShown()) {
	        popup.close();
	      }

	      this.openMerger();
	    }
	  }, {
	    key: "onSelectInterval",
	    value: function onSelectInterval(interval) {
	      var _this10 = this;

	      this._selectedExecInterval = interval;

	      if (main_core.Type.isDomNode(this._selectedExecIntervalNode)) {
	        this._selectedExecIntervalNode.textContent = this._intervalsList.reduce(function (prev, item) {
	          return item.value === _this10._selectedExecInterval ? item.title : prev;
	        }, '');
	      }

	      this.closeDropdownMenu();
	    }
	  }, {
	    key: "onHintClick",
	    value: function onHintClick() {
	      if (this._infoHelperId) {
	        BX.Helper.show("redirect=detail&code=" + this._infoHelperId);
	      }
	    }
	  }, {
	    key: "onCloseMergeSlider",
	    value: function onCloseMergeSlider(event) {
	      if (top.BX.CRM && top.BX.CRM.Kanban) {
	        var kanban = top.BX.CRM.Kanban.Grid.getInstance();

	        if (kanban) {
	          kanban.reload();
	        }
	      }

	      if (top.BX.Main.gridManager) {
	        var gridId = 'CRM_' + BX.CrmEntityType.resolveName(this._entityTypeId) + '_LIST_V12'; // does not support deal categories

	        var grid = top.BX.Main.gridManager.getInstanceById(gridId);

	        if (grid) {
	          grid.reload();
	        }
	      }
	    }
	  }], [{
	    key: "create",
	    value: function create(params) {
	      var autosearch = new DedupeAutosearch();
	      autosearch.initialize(params);
	      return autosearch;
	    }
	  }, {
	    key: "setDefault",
	    value: function setDefault(instance, entityTypeName) {
	      if (!main_core.Type.isObject(DedupeAutosearch.defaultInstance)) {
	        DedupeAutosearch.defaultInstance = {};
	      }

	      DedupeAutosearch.defaultInstance[entityTypeName] = instance;
	    }
	  }, {
	    key: "getDefault",
	    value: function getDefault(entityTypeName) {
	      return DedupeAutosearch.defaultInstance[entityTypeName];
	    }
	  }]);
	  return DedupeAutosearch;
	}();
	namespace.DedupeAutosearch = DedupeAutosearch;

	exports.DedupeAutosearch = DedupeAutosearch;

}((this.window = this.window || {}),BX,BX.Main,BX,BX.Event,BX));
//# sourceMappingURL=script.js.map
