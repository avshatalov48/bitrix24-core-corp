/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_analytics,main_core,main_core_events,mobile_utils,mobile_ajax) {
	'use strict';

	var BalloonNotifier = /*#__PURE__*/function () {
	  function BalloonNotifier() {
	    var _this = this;
	    babelHelpers.classCallCheck(this, BalloonNotifier);
	    this.initialized = false;
	    this.classes = {
	      show: 'lenta-notifier-shown'
	    };
	    this.nodeIdList = {
	      notifier: 'lenta_notifier',
	      notifierCounter: 'lenta_notifier_cnt',
	      notifierCounterTitle: 'lenta_notifier_cnt_title',
	      refreshNeeded: 'lenta_notifier_2',
	      refreshError: 'lenta_refresh_error',
	      nextPageError: 'lenta_nextpage_error'
	    };
	    this.init();
	    main_core_events.EventEmitter.subscribe('onFrameDataProcessed', function () {
	      _this.init();
	    });
	  }
	  babelHelpers.createClass(BalloonNotifier, [{
	    key: "init",
	    value: function init() {
	      var notifierNode = this.getNotifierNode();
	      if (!notifierNode || this.initialized) {
	        return;
	      }
	      this.initialized = true;
	      this.initEvents();
	    }
	  }, {
	    key: "initEvents",
	    value: function initEvents() {
	      var notifierNode = this.getNotifierNode();
	      notifierNode.addEventListener('click', function () {
	        PageInstance.refresh(true);
	        return false;
	      });
	      var refreshNeededNode = this.getRefreshNeededNode();
	      if (refreshNeededNode) {
	        refreshNeededNode.addEventListener('click', function () {
	          app.exec('pullDownLoadingStart');
	          PageInstance.refresh(true);
	          return false;
	        });
	      }
	      var refreshErrorNode = this.getRefreshErrorNode();
	      if (refreshErrorNode) {
	        refreshErrorNode.addEventListener('click', function () {
	          PageInstance.requestError('refresh', false);
	        });
	      }
	      var nextPageErrorNode = this.getNextPageErrorNode();
	      if (nextPageErrorNode) {
	        nextPageErrorNode.addEventListener('click', function () {
	          PageInstance.requestError('nextPage', false);
	        });
	      }
	    }
	  }, {
	    key: "getNotifierNode",
	    value: function getNotifierNode() {
	      return document.getElementById(this.nodeIdList.notifier);
	    }
	  }, {
	    key: "getNotifierCounterNode",
	    value: function getNotifierCounterNode() {
	      return document.getElementById(this.nodeIdList.notifierCounter);
	    }
	  }, {
	    key: "getNotifierCounterTitleNode",
	    value: function getNotifierCounterTitleNode() {
	      return document.getElementById(this.nodeIdList.notifierCounterTitle);
	    }
	  }, {
	    key: "getRefreshNeededNode",
	    value: function getRefreshNeededNode() {
	      return document.getElementById(this.nodeIdList.refreshNeeded);
	    }
	  }, {
	    key: "getRefreshErrorNode",
	    value: function getRefreshErrorNode() {
	      return document.getElementById(this.nodeIdList.refreshError);
	    }
	  }, {
	    key: "getNextPageErrorNode",
	    value: function getNextPageErrorNode() {
	      return document.getElementById(this.nodeIdList.nextPageError);
	    }
	  }, {
	    key: "showRefreshNeededNotifier",
	    value: function showRefreshNeededNotifier() {
	      var refreshNeededBlock = this.getRefreshNeededNode();
	      if (refreshNeededBlock) {
	        refreshNeededBlock.classList.add(this.classes.show);
	      }
	    }
	  }, {
	    key: "hideRefreshNeededNotifier",
	    value: function hideRefreshNeededNotifier() {
	      var refreshNeededNode = this.getRefreshNeededNode();
	      if (refreshNeededNode) {
	        refreshNeededNode.classList.remove(this.classes.show);
	      }
	    }
	  }, {
	    key: "showNotifier",
	    value: function showNotifier(params) {
	      var cnt = parseInt(params.counterValue);
	      var cnt_cent = cnt % 100,
	        reminder = cnt % 10;
	      var suffix = '';
	      if (cnt_cent >= 10 && cnt_cent < 15) {
	        suffix = 3;
	      } else if (reminder == 0) {
	        suffix = 3;
	      } else if (reminder == 1) {
	        suffix = 1;
	      } else if (reminder == 2 || reminder == 3 || reminder == 4) {
	        suffix = 2;
	      } else {
	        suffix = 3;
	      }
	      if (Instance.getRefreshNeeded()) {
	        this.getNotifierCounterNode().innerHTML = cnt ? cnt + '+' : '';
	        this.hideRefreshNeededNotifier();
	      } else {
	        this.getNotifierCounterNode().innerHTML = cnt || '';
	      }
	      this.getNotifierCounterTitleNode().innerHTML = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_COUNTER_TITLE_' + suffix);
	      this.getNotifierNode().classList.add(this.classes.show);
	    }
	  }, {
	    key: "hideNotifier",
	    value: function hideNotifier() {
	      var notifierNode = this.getNotifierNode();
	      if (notifierNode) {
	        notifierNode.classList.remove(this.classes.show);
	      }
	    }
	  }]);
	  return BalloonNotifier;
	}();

	var NextPageLoader = /*#__PURE__*/function () {
	  function NextPageLoader() {
	    var _this = this;
	    babelHelpers.classCallCheck(this, NextPageLoader);
	    this.initialized = false;
	    this.init();
	    main_core_events.EventEmitter.subscribe('onFrameDataProcessed', function () {
	      _this.init();
	    });
	  }
	  babelHelpers.createClass(NextPageLoader, [{
	    key: "init",
	    value: function init() {
	      var buttonNode = this.getButtonNode();
	      if (!buttonNode || this.initialized) {
	        return;
	      }
	      this.initialized = true;
	      this.initEvents();
	    }
	  }, {
	    key: "initEvents",
	    value: function initEvents() {
	      var buttonNode = this.getButtonNode();
	      buttonNode.addEventListener('click', function (e) {
	        PageInstance.refresh(true);
	        return false;
	      });
	    }
	  }, {
	    key: "getButtonNode",
	    value: function getButtonNode() {
	      return document.getElementById('next_page_refresh_needed_button');
	    }
	  }, {
	    key: "startWaiter",
	    value: function startWaiter() {
	      var button = this.getButtonNode();
	      if (button) {
	        button.classList.add('--loading');
	      }
	    }
	  }, {
	    key: "stopWaiter",
	    value: function stopWaiter() {
	      var button = this.getButtonNode();
	      if (button) {
	        button.classList.remove('--loading');
	      }
	    }
	  }]);
	  return NextPageLoader;
	}();

	var NotificationBar = /*#__PURE__*/function () {
	  function NotificationBar() {
	    babelHelpers.classCallCheck(this, NotificationBar);
	    this.repo = [];
	    this.color = {
	      background: {
	        error: '#affb0000',
	        info: '#3a3735'
	      },
	      text: {
	        error: '#ffffff',
	        info: '#ffffff'
	      }
	    };
	  }
	  babelHelpers.createClass(NotificationBar, [{
	    key: "hideAll",
	    value: function hideAll() {
	      this.repo = this.repo.filter(function (notifyBar) {
	        return notifyBar;
	      });
	      this.repo.forEach(function (notifyBar) {
	        notifyBar.hide();
	      });
	    }
	  }, {
	    key: "showError",
	    value: function showError(params) {
	      var bar = new BXMobileApp.UI.NotificationBar({
	        message: params.text ? params.text : '',
	        color: this.color.background.error,
	        textColor: this.color.text.error,
	        useLoader: params.useLoader ? !!params.useLoader : false,
	        groupId: params.groupId ? params.groupId : '',
	        align: params.textAlign ? params.textAlign : 'center',
	        autoHideTimeout: params.autoHideTimeout ? params.autoHideTimeout : 30000,
	        hideOnTap: params.hideOnTap ? !!params.hideOnTap : true,
	        onTap: params.onTap ? params.onTap : function () {}
	      }, params.id ? params.id : parseInt(Math.random() * 100000));
	      this.repo.push(bar);
	      bar.show();
	    }
	  }, {
	    key: "showInfo",
	    value: function showInfo(params) {
	      var bar = new BXMobileApp.UI.NotificationBar({
	        message: params.text ? params.text : '',
	        color: this.color.background.info,
	        textColor: this.color.text.info,
	        useLoader: params.useLoader ? !!params.useLoader : false,
	        groupId: params.groupId ? params.groupId : '',
	        maxLines: params.maxLines ? params.maxLines : false,
	        align: params.textAlign ? params.textAlign : 'center',
	        isGlobal: params.isGlobal ? !!params.isGlobal : true,
	        useCloseButton: params.useCloseButton ? !!params.useCloseButton : true,
	        autoHideTimeout: params.autoHideTimeout ? params.autoHideTimeout : 1000,
	        hideOnTap: params.hideOnTap ? !!params.hideOnTap : true
	      }, params.id ? params.id : parseInt(Math.random() * 100000));
	      this.repo.push(bar);
	      bar.show();
	    }
	  }]);
	  return NotificationBar;
	}();

	var Database = /*#__PURE__*/function () {
	  function Database(params) {
	    babelHelpers.classCallCheck(this, Database);
	    this.tableName = null;
	    this.keyName = null;
	    this.setTableName(main_core.Type.isPlainObject(params) && main_core.Type.isStringFilled(params.tableName) ? params.tableName : 'livefeed');
	    this.setKeyName(main_core.Type.isPlainObject(params) && main_core.Type.isStringFilled(params.keyName) ? params.keyName : 'postUnsent');
	    this.init();
	  }
	  babelHelpers.createClass(Database, [{
	    key: "init",
	    value: function init() {
	      BXMobileApp.addCustomEvent('Livefeed.Database::clear', this.onClear.bind(this));
	    }
	  }, {
	    key: "setTableName",
	    value: function setTableName(value) {
	      this.tableName = value;
	    }
	  }, {
	    key: "getTableName",
	    value: function getTableName() {
	      return this.tableName;
	    }
	  }, {
	    key: "setKeyName",
	    value: function setKeyName(value) {
	      this.keyName = value;
	    }
	  }, {
	    key: "getKeyName",
	    value: function getKeyName() {
	      return this.keyName;
	    }
	  }, {
	    key: "onClear",
	    value: function onClear(params) {
	      this["delete"](params.groupId);
	    }
	  }, {
	    key: "delete",
	    value: function _delete(groupId) {
	      if (parseInt(groupId) <= 0) {
	        groupId = false;
	      }
	      app.exec('setStorageValue', {
	        storageId: this.getTableName(),
	        key: this.getKeyName() + (groupId ? '_' + groupId : ''),
	        value: {},
	        callback: function callback(res) {}
	      });
	    }
	  }, {
	    key: "save",
	    value: function save(data, groupId) {
	      if (parseInt(groupId) <= 0) {
	        groupId = false;
	      }
	      for (var x in data) {
	        if (!data.hasOwnProperty(x)) {
	          continue;
	        }
	        if (x === 'sessid') {
	          delete data[x];
	          break;
	        }
	      }
	      app.exec('setStorageValue', {
	        storageId: this.getTableName(),
	        key: this.getKeyName() + (groupId ? '_' + groupId : ''),
	        value: data,
	        callback: function callback(res) {}
	      });
	    }
	  }, {
	    key: "load",
	    value: function load(_callback, groupId) {
	      if (parseInt(groupId) <= 0) {
	        groupId = false;
	      }
	      app.exec('getStorageValue', {
	        storageId: this.getTableName(),
	        key: this.getKeyName() + (groupId ? '_' + groupId : ''),
	        callback: function callback(value) {
	          value = main_core.Type.isPlainObject(value) ? value : main_core.Type.isStringFilled(value) ? JSON.parse(value) : {};
	          if (main_core.Type.isPlainObject(value) && Object.keys(value).length > 0) {
	            _callback.onLoad(value);
	          } else {
	            _callback.onEmpty();
	          }
	        }
	      });
	    }
	  }]);
	  return Database;
	}();

	var _templateObject;
	var PublicationQueue = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(PublicationQueue, _EventEmitter);
	  function PublicationQueue() {
	    var _this;
	    babelHelpers.classCallCheck(this, PublicationQueue);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PublicationQueue).call(this));
	    _this.repo = {};
	    _this.nodeId = {
	      container: 'post-balloon-container'
	    };
	    _this["class"] = {
	      balloonHidden: 'post-balloon-hidden',
	      balloonFixed: 'post-balloon-box-fixed',
	      balloonPublished: 'post-balloon-done',
	      balloonShow: 'post-balloon-show',
	      balloonHide: 'post-balloon-hide'
	    };
	    _this.timeout = {
	      show: 750
	    };
	    _this.init();
	    return _this;
	  }
	  babelHelpers.createClass(PublicationQueue, [{
	    key: "init",
	    value: function init() {
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterSetItem', this.afterSetItem.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', this.afterPostAdd.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAddError', this.afterPostAddError.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', this.afterPostUpdate.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdateError', this.afterPostUpdateError.bind(this));
	      this.setEventNamespace('BX.Mobile.Livefeed');
	      this.subscribe('onFeedReady', this.onFeedLoaded.bind(this));
	      this.subscribe('onPostInserted', this.onPostInserted.bind(this));
	      main_core.Event.bind(document, 'scroll', this.onScroll.bind(this));
	    }
	  }, {
	    key: "onScroll",
	    value: function onScroll() {
	      var containerNode = document.getElementById(this.nodeId.container);
	      if (!main_core.Type.isDomNode(containerNode)) {
	        return;
	      }
	      if (window.pageYOffset > 0) {
	        containerNode.classList.add(this["class"].balloonFixed);
	      } else {
	        containerNode.classList.remove(this["class"].balloonFixed);
	      }
	    }
	  }, {
	    key: "onFeedLoaded",
	    value: function onFeedLoaded() {
	      var _this2 = this;
	      app.exec('getStorageValue', {
	        storageId: 'livefeed',
	        key: 'publicationQueue',
	        callback: function callback(queue) {
	          queue = main_core.Type.isPlainObject(queue) ? queue : main_core.Type.isStringFilled(queue) ? JSON.parse(queue) : {};
	          if (!main_core.Type.isPlainObject(queue)) {
	            return;
	          }
	          for (var key in queue) {
	            if (!queue.hasOwnProperty(key)) {
	              continue;
	            }
	            _this2.addToTray(key, {});
	          }
	          _this2.drawList();
	        }
	      });
	    }
	  }, {
	    key: "onPostInserted",
	    value: function onPostInserted(event) {
	      this.removeFromTray(event.data.key);
	    }
	  }, {
	    key: "addToTray",
	    value: function addToTray(key, params) {
	      this.repo[key] = params;
	      this.repo[key].node = this.drawItem();
	      this.repo[key].node.classList.add(this["class"].balloonShow);
	    }
	  }, {
	    key: "removeFromTray",
	    value: function removeFromTray(key, params) {
	      var _this3 = this;
	      this.hideItem(key);
	      setTimeout(function () {
	        if (_this3.repo[key]) {
	          delete _this3.repo[key];
	        }
	      }, 3000);
	    }
	  }, {
	    key: "addSuccess",
	    value: function addSuccess(key, warningText) {
	      var _this4 = this;
	      if (this.repo[key] && this.repo[key].node) {
	        this.repo[key].node.classList.remove(this["class"].balloonHidden);
	        this.repo[key].node.classList.remove(this["class"].balloonShow);
	        this.repo[key].node.classList.add(this["class"].balloonPublished);
	        this.repo[key].node.lastElementChild.innerHTML = main_core.Type.isStringFilled(warningText) ? warningText : main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_SUCCESS_TITLE');
	      }
	      setTimeout(function () {
	        _this4.removeFromTray(key);
	      }, 5000);
	    }
	  }, {
	    key: "drawItem",
	    value: function drawItem() {
	      var title = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_ITEM_TITLE');
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"post-balloon-hidden post-balloon post-balloon-active\"><span class=\"post-balloon-icon\"></span><span class=\"post-balloon-text\">", "</span></div>\n\t\t\t"])), title);
	    }
	  }, {
	    key: "hideItem",
	    value: function hideItem(key, params) {
	      if (this.repo[key]) {
	        this.repo[key].node.classList.add(this["class"].balloonHide);
	      }
	    }
	  }, {
	    key: "drawList",
	    value: function drawList() {
	      var containerNode = document.getElementById(this.nodeId.container);
	      if (!main_core.Type.isDomNode(containerNode)) {
	        return;
	      }
	      main_core.Dom.clean(containerNode);
	      for (var key in this.repo) {
	        if (!this.repo.hasOwnProperty(key) || !main_core.Type.isDomNode(this.repo[key].node)) {
	          continue;
	        }
	        main_core.Dom.append(this.repo[key].node, containerNode);
	      }
	    }
	  }, {
	    key: "afterSetItem",
	    value: function afterSetItem(params) {
	      var _this5 = this;
	      var key = params.key ? params.key : '',
	        pageId = params.pageId ? params.pageId : '',
	        contentType = params.contentType ? params.contentType : '';
	      if (pageId != Instance.getPageId() || !key || !main_core.Type.isStringFilled(contentType)) {
	        return;
	      }
	      if (contentType == 'post') {
	        this.addToTray(key, {
	          key: key
	        });
	        setTimeout(function () {
	          _this5.drawList();
	        }, this.timeout.show);
	      }
	    }
	  }, {
	    key: "afterPostAdd",
	    value: function afterPostAdd(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      this.addSuccess(params.key ? params.key : '', params.warningText);
	      this.drawList();
	    }
	  }, {
	    key: "afterPostUpdate",
	    value: function afterPostUpdate(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      this.addSuccess(params.key ? params.key : '');
	      this.drawList();
	    }
	  }, {
	    key: "afterPostAddError",
	    value: function afterPostAddError(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var key = params.key ? params.key : '';
	      this.removeFromTray(key, {
	        key: key
	      });
	      this.drawList();
	    }
	  }, {
	    key: "afterPostUpdateError",
	    value: function afterPostUpdateError(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var key = params.key ? params.key : '';
	      this.removeFromTray(key, {
	        key: key
	      });
	      this.drawList();
	    }
	  }]);
	  return PublicationQueue;
	}(main_core_events.EventEmitter);

	var Post$$1 = /*#__PURE__*/function () {
	  babelHelpers.createClass(Post$$1, null, [{
	    key: "moveBottom",
	    value: function moveBottom() {
	      window.scrollTo(0, document.body.scrollHeight);
	    }
	  }, {
	    key: "moveTop",
	    value: function moveTop() {
	      window.scrollTo(0, 0);
	    }
	  }]);
	  function Post$$1(data) {
	    babelHelpers.classCallCheck(this, Post$$1);
	    this.logId = 0;
	    this.entryType = '';
	    this.useFollow = false;
	    this.useTasks = false;
	    this.perm = '';
	    this.destinations = {};
	    this.postId = 0;
	    this.url = '';
	    this.entityXmlId = '';
	    this.readOnly = false;
	    this.contentTypeId = '';
	    this.contentId = 0;
	    this.showFull = false;
	    this.taskId = 0;
	    this.taskData = null;
	    this.calendarEventId = 0;
	    this.init(data);
	  }
	  babelHelpers.createClass(Post$$1, [{
	    key: "init",
	    value: function init(data) {
	      var logId = data.logId,
	        entryType = data.entryType,
	        useFollow = data.useFollow,
	        useTasks = data.useTasks,
	        perm = data.perm,
	        destinations = data.destinations,
	        postId = data.postId,
	        url = data.url,
	        entityXmlId = data.entityXmlId,
	        readOnly = data.readOnly,
	        contentTypeId = data.contentTypeId,
	        contentId = data.contentId,
	        showFull = data.showFull,
	        taskId = data.taskId,
	        taskData = data.taskData,
	        calendarEventId = data.calendarEventId;
	      logId = parseInt(logId);
	      if (logId <= 0) {
	        return;
	      }
	      this.logId = logId;
	      this.postId = parseInt(postId);
	      this.contentId = parseInt(contentId);
	      this.taskId = parseInt(taskId);
	      this.calendarEventId = parseInt(calendarEventId);
	      this.useFollow = !!useFollow;
	      this.useTasks = !!useTasks;
	      this.readOnly = !!readOnly;
	      this.showFull = !!showFull;
	      if (main_core.Type.isStringFilled(entryType)) {
	        this.entryType = entryType;
	      }
	      if (main_core.Type.isStringFilled(perm)) {
	        this.perm = perm;
	      }
	      if (main_core.Type.isStringFilled(url)) {
	        this.url = url;
	      }
	      if (main_core.Type.isStringFilled(entityXmlId)) {
	        this.entityXmlId = entityXmlId;
	      }
	      if (main_core.Type.isStringFilled(contentTypeId)) {
	        this.contentTypeId = contentTypeId;
	      }
	      if (main_core.Type.isStringFilled(taskData)) {
	        try {
	          this.taskData = JSON.parse(taskData);
	        } catch (e) {
	          this.taskData = null;
	        }
	      }
	      if (main_core.Type.isPlainObject(destinations)) {
	        this.destinations = destinations;
	      }
	    }
	  }, {
	    key: "setFavorites",
	    value: function setFavorites(data) {
	      var _this = this;
	      if (this.logId <= 0) {
	        return;
	      }
	      var node = data.node,
	        event = data.event;

	      // for old versions without post menu in the feed
	      if (!main_core.Type.isDomNode(node)) {
	        node = document.getElementById('log_entry_favorites_' + this.logId);
	      }
	      if (main_core.Type.isDomNode(node)) {
	        var oldValue = node.getAttribute('data-favorites') === 'Y' ? 'Y' : 'N';
	        var newValue = oldValue === 'Y' ? 'N' : 'Y';

	        // for old versions without post menu in the feed
	        if (node.classList.contains('lenta-item-fav')) {
	          if (oldValue === 'Y') {
	            node.classList.remove('lenta-item-fav-active');
	          } else {
	            node.classList.add('lenta-item-fav-active');
	          }
	        }
	        node.setAttribute('data-favorites', newValue);
	        mobile_ajax.Ajax.runAction('socialnetwork.api.livefeed.changeFavorites', {
	          data: {
	            logId: this.logId,
	            value: newValue
	          },
	          analyticsLabel: {
	            b24statAction: newValue === 'Y' ? 'addFavorites' : 'removeFavorites',
	            b24statContext: 'mobile'
	          }
	        }).then(function (response) {
	          if (response.data.success) {
	            if (newValue === 'Y') {
	              FollowManagerInstance.setFollow({
	                logId: _this.logId,
	                bOnlyOn: true,
	                bRunEvent: true,
	                bAjax: false
	              });
	            }
	            BXMobileApp.onCustomEvent('onLogEntryFavorites', {
	              log_id: _this.logId,
	              page_id: main_core.Loc.getMessage('MSLPageId')
	            }, true);
	          } else {
	            node.setAttribute('data-favorites', oldValue);
	          }
	        }, function () {
	          node.setAttribute('data-favorites', oldValue);
	        });
	      }
	      if (event instanceof Event) {
	        event.preventDefault();
	        event.stopPropagation();
	      }
	    }
	  }, {
	    key: "setPinned",
	    value: function setPinned(data) {
	      var _this2 = this;
	      if (this.logId <= 0) {
	        return;
	      }
	      var menuNode = data.menuNode,
	        context = data.context;
	      if (main_core.Type.isDomNode(menuNode)) {
	        var oldValue = menuNode.getAttribute('data-pinned') === 'Y' ? 'Y' : 'N';
	        var newValue = oldValue === 'Y' ? 'N' : 'Y';
	        menuNode.setAttribute('data-pinned', newValue);
	        BXMobileApp.onCustomEvent('Livefeed::showLoader', {}, true, true);
	        var action = newValue === 'Y' ? 'socialnetwork.api.livefeed.logentry.pin' : 'socialnetwork.api.livefeed.logentry.unpin';
	        mobile_ajax.Ajax.runAction(action, {
	          data: {
	            params: {
	              logId: this.logId
	            }
	          },
	          analyticsLabel: {
	            b24statAction: newValue === 'Y' ? 'pinLivefeedEntry' : 'unpinLivefeedEntry',
	            b24statContext: 'mobile'
	          }
	        }).then(function (response) {
	          BXMobileApp.onCustomEvent('Livefeed::hideLoader', {}, true, true);
	          if (response.data.success) {
	            BXMobileApp.onCustomEvent('Livefeed.PinnedPanel::change', {
	              logId: _this2.logId,
	              value: newValue,
	              postNode: menuNode.closest('.lenta-item'),
	              pinActionContext: context
	            }, true, true);
	            BXMobileApp.onCustomEvent('Livefeed.PostDetail::pinChanged', {
	              logId: _this2.logId,
	              value: newValue
	            }, true, true);
	          } else {
	            menuNode.setAttribute('data-pinned', oldValue);
	          }
	        }, function () {
	          BXMobileApp.onCustomEvent('Livefeed::hideLoader', {}, true, true);
	          menuNode.setAttribute('data-pinned', oldValue);
	        });
	      }
	    }
	  }, {
	    key: "openDetail",
	    value: function openDetail(params) {
	      var pathToEmptyPage = params.pathToEmptyPage,
	        pathToCalendarEvent = params.pathToCalendarEvent,
	        pathToTasksRouter = params.pathToTasksRouter,
	        event = params.event,
	        focusComments = params.focusComments,
	        showFull = params.showFull;
	      if (!main_core.Type.isStringFilled(pathToEmptyPage)) {
	        return;
	      }
	      if (this.taskId > 0 && BXMobileAppContext.getApiVersion() >= 31 && this.taskData) {
	        BXMobileApp.Events.postToComponent('taskbackground::task::open', [{
	          id: this.taskId,
	          taskId: this.taskId,
	          title: 'TASK',
	          taskInfo: {
	            title: this.taskData.title,
	            creatorIcon: this.taskData.creatorIcon,
	            responsibleIcon: this.taskData.responsibleIcon
	          }
	        }, {
	          taskId: this.taskId,
	          getTaskInfo: true
	        }]);
	      } else {
	        var path = pathToEmptyPage;
	        if (this.calendarEventId > 0 && !focusComments && pathToCalendarEvent.length > 0) {
	          path = pathToCalendarEvent.replace('#EVENT_ID#', this.calendarEventId);
	        } else if (this.taskId > 0 && this.taskData && pathToTasksRouter.length > 0)
	          // API version <= 31
	          {
	            path = pathToTasksRouter.replace('__ROUTE_PAGE__', 'view').replace('#USER_ID#', main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_CURRENT_USER_ID')) + '&TASK_ID=' + this.taskId;
	          }
	        __MSLOpenLogEntryNew({
	          path: path,
	          log_id: this.logId,
	          entry_type: this.entryType,
	          use_follow: this.useFollow ? 'Y' : 'N',
	          use_tasks: this.useTasks ? 'Y' : 'N',
	          post_perm: this.perm,
	          destinations: this.destinations,
	          post_id: this.postId,
	          post_url: this.url,
	          entity_xml_id: this.entityXmlId,
	          focus_comments: focusComments,
	          focus_form: false,
	          show_full: this.showFull || showFull,
	          read_only: this.readOnly ? 'Y' : 'N',
	          post_content_type_id: this.contentTypeId,
	          post_content_id: this.contentId
	        }, event);
	      }
	    }
	  }, {
	    key: "initDetailPin",
	    value: function initDetailPin() {
	      var menuNode = document.getElementById('log-entry-menu-' + this.logId);
	      if (!menuNode) {
	        return;
	      }
	      var postNode = menuNode.closest('.post-wrap');
	      if (!postNode) {
	        return;
	      }
	      var pinnedValue = menuNode.getAttribute('data-pinned');
	      if (pinnedValue === 'Y') {
	        postNode.classList.add('lenta-item-pin-active');
	      } else {
	        postNode.classList.remove('lenta-item-pin-active');
	      }
	    }
	  }, {
	    key: "expandText",
	    value: function expandText() {
	      oMSL.expandText(this.logId);
	    }
	  }]);
	  return Post$$1;
	}();

	var BlogPost$$1 = /*#__PURE__*/function () {
	  function BlogPost$$1() {
	    babelHelpers.classCallCheck(this, BlogPost$$1);
	  }
	  babelHelpers.createClass(BlogPost$$1, null, [{
	    key: "delete",
	    value: function _delete(params) {
	      var context = main_core.Type.isStringFilled(params.context) ? params.context : 'list';
	      var postId = !main_core.Type.isUndefined(params.postId) ? parseInt(params.postId) : 0;
	      if (postId <= 0) {
	        return false;
	      }
	      app.confirm({
	        title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_TITLE'),
	        text: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_DESCRIPTION'),
	        buttons: [main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_OK'), main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_CANCEL')],
	        callback: function callback(btnNum) {
	          if (parseInt(btnNum) !== 1) {
	            return false;
	          }
	          app.showPopupLoader({
	            text: ''
	          });
	          var actionUrl = main_core.Uri.addParam("".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/ajax.php"), {
	            b24statAction: 'deleteBlogPost',
	            b24statContext: 'mobile'
	          });
	          mobile_ajax.Ajax.wrap({
	            type: 'json',
	            method: 'POST',
	            url: actionUrl,
	            data: {
	              action: 'delete_post',
	              mobile_action: 'delete_post',
	              sessid: main_core.Loc.getMessage('bitrix_sessid'),
	              site: main_core.Loc.getMessage('SITE_ID'),
	              lang: main_core.Loc.getMessage('LANGUAGE_ID'),
	              post_id: postId
	            },
	            processData: true,
	            callback: function callback(response) {
	              app.hidePopupLoader();
	              if (!main_core.Type.isStringFilled(response.SUCCESS) || response.SUCCESS !== 'Y') {
	                return;
	              }
	              BXMobileApp.onCustomEvent('onBlogPostDelete', {}, true, true);
	              if (context === 'detail') {
	                app.closeController({
	                  drop: true
	                });
	              }
	            },
	            callback_failure: function callback_failure() {
	              app.hidePopupLoader();
	            }
	          });
	          return false;
	        }
	      });
	    }
	  }, {
	    key: "edit",
	    value: function edit(params) {
	      var postId = !main_core.Type.isUndefined(params.postId) ? parseInt(params.postId) : 0;
	      if (postId <= 0) {
	        return;
	      }
	      var pinnedContext = !main_core.Type.isUndefined(params.pinnedContext) ? !!params.pinnedContext : false;
	      if (Application.getApiVersion() >= Instance.getApiVersion('layoutPostForm')) {
	        PostFormManagerInstance.show({
	          pageId: Instance.getPageId(),
	          postId: postId
	        });
	      } else {
	        this.getData({
	          postId: postId,
	          callback: function callback(postData) {
	            PostFormOldManagerInstance.formParams = {};
	            if (!main_core.Type.isUndefined(postData.PostPerm) && postData.PostPerm >= 'W') {
	              var selectedDestinations = {
	                a_users: [],
	                b_groups: []
	              };
	              PostFormOldManagerInstance.setExtraDataArray({
	                postId: postId,
	                postAuthorId: postData.post_user_id,
	                logId: postData.log_id,
	                pinnedContext: pinnedContext
	              });
	              if (!main_core.Type.isUndefined(postData.PostDetailText)) {
	                PostFormOldManagerInstance.setParams({
	                  messageText: postData.PostDetailText
	                });
	              }
	              if (main_core.Type.isPlainObject(postData.PostDestination)) {
	                for (var _i = 0, _Object$entries = Object.entries(postData.PostDestination); _i < _Object$entries.length; _i++) {
	                  var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	                    key = _Object$entries$_i[0],
	                    value = _Object$entries$_i[1];
	                  if (main_core.Type.isStringFilled(postData.PostDestination[key].STYLE) && postData.PostDestination[key].STYLE === 'all-users') {
	                    PostFormOldManagerInstance.addDestination(selectedDestinations, {
	                      type: 'UA'
	                    });
	                  } else if (main_core.Type.isStringFilled(postData.PostDestination[key].TYPE) && ['U', 'SG'].includes(postData.PostDestination[key].TYPE)) {
	                    PostFormOldManagerInstance.addDestination(selectedDestinations, {
	                      type: postData.PostDestination[key].TYPE,
	                      id: postData.PostDestination[key].ID,
	                      name: main_core.Text.decode(postData.PostDestination[key].TITLE)
	                    });
	                  }
	                }
	              }
	              if (!main_core.Type.isUndefined(postData.PostDestinationHidden)) {
	                PostFormOldManagerInstance.setExtraData({
	                  hiddenRecipients: postData.PostDestinationHidden
	                });
	              }
	              PostFormOldManagerInstance.setParams({
	                selectedRecipients: selectedDestinations
	              });
	              if (!main_core.Type.isUndefined(postData.PostFiles)) {
	                PostFormOldManagerInstance.setParams({
	                  messageFiles: postData.PostFiles
	                });
	              }
	              if (!main_core.Type.isUndefined(postData.PostUFCode)) {
	                PostFormOldManagerInstance.setExtraData({
	                  messageUFCode: postData.PostUFCode
	                });
	              }
	              app.exec('showPostForm', PostFormOldManagerInstance.show());
	            }
	          }
	        });
	      }
	    }
	  }, {
	    key: "getData",
	    value: function getData(params) {
	      var postId = !main_core.Type.isUndefined(params.postId) ? parseInt(params.postId) : 0;
	      if (postId <= 0) {
	        return;
	      }
	      var callbackFunction = main_core.Type.isFunction(params.callback) ? params.callback : null;
	      if (main_core.Type.isNull(callbackFunction)) {
	        return;
	      }
	      var result = {};
	      if (postId > 0) {
	        app.showPopupLoader();
	        mobile_ajax.Ajax.wrap({
	          type: 'json',
	          method: 'POST',
	          url: "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/ajax.php"),
	          processData: true,
	          data: {
	            action: 'get_blog_post_data',
	            mobile_action: 'get_blog_post_data',
	            sessid: main_core.Loc.getMessage('bitrix_sessid'),
	            site: main_core.Loc.getMessage('SITE_ID'),
	            lang: main_core.Loc.getMessage('LANGUAGE_ID'),
	            post_id: postId,
	            nt: main_core.Loc.getMessage('MSLNameTemplate'),
	            sl: main_core.Loc.getMessage('MSLShowLogin')
	          },
	          callback: function callback(data) {
	            app.hidePopupLoader();
	            result.id = postId;
	            if (!main_core.Type.isUndefined(data.log_id) && parseInt(data.log_id) > 0) {
	              result.log_id = data.log_id;
	            }
	            if (!main_core.Type.isUndefined(data.post_user_id) && parseInt(data.post_user_id) > 0) {
	              result.post_user_id = data.post_user_id;
	            }
	            if (!main_core.Type.isUndefined(data.PostPerm)) {
	              result.PostPerm = data.PostPerm;
	            }
	            if (!main_core.Type.isUndefined(data.PostDestination)) {
	              result.PostDestination = data.PostDestination;
	            }
	            if (!main_core.Type.isUndefined(data.PostDestinationHidden)) {
	              result.PostDestinationHidden = data.PostDestinationHidden;
	            }
	            if (!main_core.Type.isUndefined(data.PostDetailText)) {
	              result.PostDetailText = data.PostDetailText;
	            }
	            if (main_core.Type.isUndefined(data.PostFiles)) {
	              result.PostFiles = data.PostFiles;
	            }
	            if (!main_core.Type.isUndefined(data.PostBackgroundCode)) {
	              result.PostBackgroundCode = data.PostBackgroundCode;
	            }
	            if (!main_core.Type.isUndefined(data.PostUFCode)) {
	              result.PostUFCode = data.PostUFCode;
	            }
	            callbackFunction(result);
	          },
	          callback_failure: function callback_failure() {
	            app.hidePopupLoader();
	          }
	        });
	      }
	    }
	  }]);
	  return BlogPost$$1;
	}();

	var PostMenu = /*#__PURE__*/function () {
	  function PostMenu() {
	    babelHelpers.classCallCheck(this, PostMenu);
	  }
	  babelHelpers.createClass(PostMenu, [{
	    key: "init",
	    value: function init(data) {
	      this.iconUrlFolderPath = '/bitrix/templates/mobile_app/images/lenta/menu/';
	      this.sectionCode = 'defaultSection';
	      this.logId = parseInt(data.logId, 10);
	      this.postId = parseInt(data.postId, 10);
	      this.postPerms = main_core.Type.isStringFilled(data.postPerms) ? data.postPerms : 'R';
	      this.pageId = data.pageId;
	      this.contentTypeId = main_core.Type.isStringFilled(data.contentTypeId) ? data.contentTypeId : null;
	      this.contentId = main_core.Type.isInteger(data.contentId) ? data.contentId : 0;
	      this.useShare = Boolean(data.useShare) && this.postId > 0;
	      this.useFavorites = Boolean(data.useFavorites) && this.logId > 0;
	      this.useFollow = Boolean(data.useFollow) && this.logId > 0;
	      this.usePinned = Boolean(data.usePinned) && this.logId > 0;
	      this.useTasks = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_USE_TASKS') === 'Y';
	      this.useRefreshComments = Boolean(data.useRefreshComments);
	      this.favoritesValue = Boolean(data.favoritesValue);
	      this.followValue = Boolean(data.followValue);
	      this.pinnedValue = Boolean(data.pinnedValue);
	      this.target = main_core.Type.isDomNode(data.target) ? data.target : null;
	      this.context = main_core.Type.isStringFilled(data.context) ? data.context : 'list';
	    }
	  }, {
	    key: "getMenuItems",
	    value: function getMenuItems() {
	      var _this = this;
	      var result = [];
	      if (this.usePinned) {
	        result.push({
	          id: 'pinned',
	          title: main_core.Loc.getMessage("MOBILE_EXT_LIVEFEED_POST_MENU_PINNED_".concat(this.pinnedValue ? 'Y' : 'N')),
	          iconUrl: this.iconUrlFolderPath + (this.pinnedValue ? 'unpin.png' : 'pin.png'),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            var postInstance = new Post$$1({
	              logId: _this.logId
	            });
	            return postInstance.setPinned({
	              menuNode: _this.target,
	              context: _this.context
	            });
	          }
	        });
	      }
	      if (this.useShare) {
	        var selectedDestinations = {
	          a_users: [],
	          b_groups: []
	        };
	        if (selectedDestinations.a_users.length > 0 || selectedDestinations.b_groups.length > 0) {
	          result.push({
	            id: 'sharePost',
	            title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_SHARE'),
	            iconName: 'add',
	            iconUrl: "".concat(this.iconUrlFolderPath, "n_plus.png"),
	            sectionCode: this.sectionCode,
	            action: function action() {
	              app.openTable({
	                callback: function callback() {
	                  oMSL.shareBlogPost();
	                },
	                url: "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/index.php?mobile_action=").concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE') === 'Y' ? 'get_group_list' : 'get_usergroup_list', "&feature=blog"),
	                markmode: true,
	                multiple: true,
	                return_full_mode: true,
	                user_all: true,
	                showtitle: true,
	                modal: true,
	                selected: selectedDestinations,
	                alphabet_index: true,
	                okname: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SHARE_TABLE_BUTTON_OK'),
	                cancelname: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SHARE_TABLE_BUTTON_CANCEL'),
	                outsection: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') !== 'Y'
	              });
	            },
	            arrowFlag: false
	          });
	        }
	      }
	      if (this.postId > 0 && this.postPerms === 'W') {
	        result.push({
	          id: 'edit',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_EDIT'),
	          iconUrl: "".concat(this.iconUrlFolderPath, "pencil.png"),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            BlogPost$$1.edit({
	              feedId: window.LiveFeedID,
	              postId: _this.postId,
	              pinnedContext: Boolean(_this.pinnedValue)
	            });
	          },
	          arrowFlag: false,
	          feature: 'edit'
	        }, {
	          id: 'delete',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_DELETE'),
	          iconName: 'delete',
	          sectionCode: this.sectionCode,
	          action: function action() {
	            BlogPost$$1["delete"]({
	              postId: _this.postId,
	              context: _this.context
	            });
	          },
	          arrowFlag: false
	        });
	      }
	      if (this.useFavorites) {
	        result.push({
	          id: 'favorites',
	          title: main_core.Loc.getMessage("MOBILE_EXT_LIVEFEED_POST_MENU_FAVORITES_".concat(this.favoritesValue ? 'Y' : 'N')),
	          iconUrl: "".concat(this.iconUrlFolderPath, "favorite.png"),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            var postInstance = new Post$$1({
	              logId: _this.logId
	            });
	            return postInstance.setFavorites({
	              node: _this.target
	            });
	          },
	          arrowFlag: false,
	          feature: 'favorites'
	        });
	      }
	      if (this.useFollow) {
	        result.push({
	          id: 'follow',
	          title: main_core.Loc.getMessage("MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_".concat(this.followValue ? 'Y' : 'N')),
	          iconUrl: "".concat(this.iconUrlFolderPath, "eye.png"),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            FollowManagerInstance.setFollow({
	              logId: _this.logId,
	              menuNode: _this.target,
	              pageId: _this.pageId,
	              bOnlyOn: false,
	              bAjax: true,
	              bRunEvent: true
	            });
	          },
	          arrowFlag: false
	        });
	      }
	      if (this.useRefreshComments) {
	        result.push({
	          id: 'refreshPostComments',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_REFRESH_COMMENTS'),
	          iconUrl: "".concat(this.iconUrlFolderPath, "n_refresh.png"),
	          action: function action() {
	            if (oMSL.bDetailEmptyPage) {
	              // get comments on refresh from detail page menu
	              CommentsInstance.getComments({
	                ts: oMSL.iDetailTs,
	                bPullDown: true,
	                obFocus: {
	                  form: false
	                }
	              });
	            } else {
	              document.location.reload(true);
	            }
	          },
	          arrowFlag: false
	        });
	      }
	      if (main_core.Type.isStringFilled(this.contentTypeId) && this.contentId > 0) {
	        result.push({
	          id: 'getPostLink',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_GET_LINK'),
	          iconUrl: "".concat(this.iconUrlFolderPath, "link.png"),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            oMSL.copyPostLink({
	              contentTypeId: _this.contentTypeId,
	              contentId: _this.contentId
	            });
	          },
	          arrowFlag: false
	        });
	        if (this.useTasks && this.logId > 0) {
	          result.push({
	            id: 'createTask',
	            title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_CREATE_TASK'),
	            iconUrl: "".concat(this.iconUrlFolderPath, "n_check.png"),
	            sectionCode: this.sectionCode,
	            action: function action() {
	              oMSL.createTask({
	                entityType: _this.contentTypeId,
	                entityId: _this.contentId,
	                logId: _this.logId
	              });
	              ui_analytics.sendData({
	                tool: 'tasks',
	                category: 'task_operations',
	                event: 'task_create',
	                type: 'task',
	                c_section: 'feed',
	                c_element: 'create_button'
	              });
	              return false;
	            },
	            arrowFlag: false
	          });
	        }
	      }
	      return result;
	    }
	  }]);
	  return PostMenu;
	}();

	var PageMenu = /*#__PURE__*/function () {
	  function PageMenu() {
	    babelHelpers.classCallCheck(this, PageMenu);
	    this.type = 'list';
	    this.listPageMenuItems = [];
	    this.detailPageMenuItems = [];
	  }
	  babelHelpers.createClass(PageMenu, [{
	    key: "init",
	    value: function init(data) {
	      this.type = data.type === 'detail' ? 'detail' : 'list';
	      var menuItems = this.getPageMenuItems();
	      var title = this.type === 'detail' ? main_core.Type.isStringFilled(main_core.Loc.getMessage('MSLLogEntryTitle')) ? main_core.Loc.getMessage('MSLLogEntryTitle') : '' : main_core.Type.isStringFilled(main_core.Loc.getMessage('MSLLogTitle')) ? main_core.Loc.getMessage('MSLLogTitle') : '';
	      if (menuItems.length > 0) {
	        if (BXMobileAppContext.getApiVersion() >= Instance.getApiVersion('pageMenu')) {
	          this.initPagePopupMenu();
	        } else {
	          app.menuCreate({
	            items: menuItems
	          });
	          BXMobileApp.UI.Page.TopBar.title.setCallback(function () {
	            app.menuShow();
	          });
	        }
	      } else if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu')) {
	        BXMobileApp.UI.Page.TopBar.title.setCallback("");
	      }
	      BXMobileApp.UI.Page.TopBar.title.setText(title);
	      BXMobileApp.UI.Page.TopBar.title.show();
	    }
	  }, {
	    key: "initPagePopupMenu",
	    value: function initPagePopupMenu() {
	      var _this = this;
	      if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu')) {
	        return;
	      }
	      var buttons = [];
	      if (!oMSL.logId) {
	        buttons.push({
	          type: 'search',
	          callback: function callback() {
	            app.exec("showSearchBar");
	          }
	        });
	      }
	      var menuItems = this.getPageMenuItems(this.type);
	      if (BX.type.isArray(menuItems) && menuItems.length > 0) {
	        buttons.push({
	          type: 'more',
	          callback: function callback() {
	            _this.showPageMenu(_this.type);
	          }
	        });
	      }
	      app.exec('setRightButtons', {
	        items: buttons
	      });
	    }
	  }, {
	    key: "showPageMenu",
	    value: function showPageMenu() {
	      if (this.type === 'detail') {
	        this.detailPageMenuItems = this.buildDetailPageMenu(oMSL.menuData);
	      }
	      var menuItems = this.getPageMenuItems();
	      if (menuItems.length <= 0) {
	        return;
	      }
	      var popupMenuItems = [];
	      var popupMenuActions = {};
	      menuItems.forEach(function (menuItem) {
	        popupMenuItems.push({
	          id: menuItem.id,
	          title: menuItem.name,
	          iconUrl: main_core.Type.isStringFilled(menuItem.image) ? menuItem.image : '',
	          iconName: main_core.Type.isStringFilled(menuItem.iconName) ? menuItem.iconName : '',
	          sectionCode: 'defaultSection'
	        });
	        popupMenuActions[menuItem.id] = menuItem.action;
	      });
	      app.exec('setPopupMenuData', {
	        items: popupMenuItems,
	        sections: [{
	          id: 'defaultSection'
	        }],
	        callback: function callback(event) {
	          if (event.eventName === 'onDataSet') {
	            app.exec('showPopupMenu');
	          } else if (event.eventName === 'onItemSelected' && main_core.Type.isPlainObject(event.item) && main_core.Type.isStringFilled(event.item.id) && main_core.Type.isFunction(popupMenuActions[event.item.id])) {
	            popupMenuActions[event.item.id]();
	          }
	        }
	      });
	    }
	  }, {
	    key: "getPageMenuItems",
	    value: function getPageMenuItems() {
	      return this.type === 'detail' ? this.detailPageMenuItems : this.listPageMenuItems;
	    }
	  }, {
	    key: "buildDetailPageMenu",
	    value: function buildDetailPageMenu(data) {
	      var menuNode = null;
	      if (BXMobileAppContext.getApiVersion() >= Instance.getApiVersion('pageMenu')) {
	        menuNode = document.getElementById("log-entry-menu-".concat(Instance.getLogId()));
	      }
	      PostMenuInstance.init({
	        logId: Instance.getLogId(),
	        postId: parseInt(data.post_id),
	        postPerms: data.post_perm,
	        useShare: data.entry_type === 'blog',
	        useFavorites: menuNode && menuNode.getAttribute('data-use-favorites') === 'Y',
	        useFollow: data.read_only !== 'Y',
	        usePinned: Instance.getLogId() > 0,
	        useRefreshComments: true,
	        favoritesValue: menuNode && menuNode.getAttribute('data-favorites') === 'Y',
	        followValue: FollowManagerInstance.getFollowValue(),
	        pinnedValue: menuNode && menuNode.getAttribute('data-pinned') === 'Y',
	        contentTypeId: data.post_content_type_id,
	        contentId: parseInt(data.post_content_id),
	        target: menuNode,
	        context: 'detail'
	      });
	      return PostMenuInstance.getMenuItems().map(function (item) {
	        item.name = item.title;
	        item.image = item.iconUrl;
	        delete item.title;
	        delete item.iconUrl;
	        return item;
	      });
	    }
	  }]);
	  return PageMenu;
	}();

	var PostFormManager = /*#__PURE__*/function () {
	  function PostFormManager() {
	    babelHelpers.classCallCheck(this, PostFormManager);
	  }
	  babelHelpers.createClass(PostFormManager, [{
	    key: "show",
	    value: function show(params) {
	      var _this = this;
	      var postData = {
	        type: params.type ? params.type : 'post',
	        groupId: params.groupId ? parseInt(params.groupId) : 0,
	        postId: params.postId ? parseInt(params.postId) : 0,
	        pageId: main_core.Type.isStringFilled(params.pageId) ? params.pageId : ''
	      };
	      this.getDatabaseData(postData).then(function (postData) {
	        return _this.getWorkgroupData(postData);
	      }).then(function (postData) {
	        return _this.getPostData(postData);
	      }).then(function (postData) {
	        return _this.processPostData(postData);
	      }).then(function (postData) {
	        app.exec("openComponent", {
	          name: "JSStackComponent",
	          componentCode: "livefeed.postform",
	          scriptPath: BX.message('MOBILE_EXT_LIVEFEED_COMPONENT_URL'),
	          params: {
	            'SERVER_NAME': BX.message('MOBILE_EXT_LIVEFEED_SERVER_NAME'),
	            'DESTINATION_LIST': Instance.getOption('destinationList', {}),
	            'DESTINATION_TO_ALL_DENY': Instance.getOption('destinationToAllDeny', false),
	            'DESTINATION_TO_ALL_DEFAULT': Instance.getOption('destinationToAllDefault', true),
	            'MODULE_DISK_INSTALLED': BX.message('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') == 'Y' ? 'Y' : 'N',
	            'MODULE_WEBDAV_INSTALLED': BX.message('MOBILE_EXT_LIVEFEED_WEBDAV_INSTALLED') == 'Y' ? 'Y' : 'N',
	            'MODULE_VOTE_INSTALLED': BX.message('MOBILE_EXT_LIVEFEED_VOTE_INSTALLED') == 'Y' ? 'Y' : 'N',
	            'USE_IMPORTANT': BX.message('MOBILE_EXT_LIVEFEED_USE_IMPORTANT') === 'N' ? 'N' : 'Y',
	            'FILE_ATTACH_PATH': BX.message('MOBILE_EXT_LIVEFEED_FILE_ATTACH_PATH'),
	            'BACKGROUND_IMAGES_DATA': Instance.getOption('backgroundImagesData', {}),
	            'BACKGROUND_COMMON': Instance.getOption('backgroundCommon', {}),
	            'MEDALS_LIST': Instance.getOption('medalsList', {}),
	            'IMPORTANT_DATA': Instance.getOption('importantData', {}),
	            'USER_FOLDER_FOR_SAVED_FILES': BX.message('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES'),
	            'MAX_UPLOAD_CHUNK_SIZE': BX.message('MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE'),
	            'POST_FILE_UF_CODE': BX.message('MOBILE_EXT_LIVEFEED_POST_FILE_UF_CODE'),
	            'POST_FORM_DATA': Instance.getOption('postFormData', {}),
	            'POST_DATA': postData,
	            'DEVICE_WIDTH': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_WIDTH'),
	            'DEVICE_HEIGHT': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_HEIGHT'),
	            'DEVICE_RATIO': BX.message('MOBILE_EXT_LIVEFEED_DEVICE_RATIO')
	          },
	          rootWidget: {
	            name: "layout",
	            settings: {
	              objectName: "postFormLayoutWidget",
	              modal: true
	            }
	          }
	        }, false);
	      });
	    }
	  }, {
	    key: "getDatabaseData",
	    value: function getDatabaseData(postData) {
	      var promise = new Promise(function (resolve, reject) {
	        var postId = parseInt(postData.postId);
	        if (postId > 0) {
	          resolve(postData);
	          return;
	        }
	        DatabaseUnsentPostInstance.load({
	          onLoad: function onLoad(data) {
	            if (data.contentType !== postData.type) {
	              resolve(postData);
	              return;
	            }
	            postData.groupId = 0;
	            if (!main_core.Type.isPlainObject(postData.post)) {
	              postData.post = {};
	            }
	            if (main_core.Type.isStringFilled(data.POST_TITLE)) {
	              postData.post.PostTitle = data.POST_TITLE;
	            }
	            if (main_core.Type.isStringFilled(data.POST_MESSAGE)) {
	              postData.post.PostDetailText = data.POST_MESSAGE;
	            }
	            if (main_core.Type.isArrayFilled(data.DEST) && main_core.Type.isPlainObject(data.DEST_DATA)) {
	              postData.post.PostDestination = [];
	              var patterns = [{
	                pattern: /^SG(\d+)$/i,
	                style: 'sonetgroups'
	              }, {
	                pattern: /^U(\d+|A)$/i,
	                style: 'users'
	              }, {
	                pattern: /^DR(\d+)$/i,
	                style: 'department'
	              }];
	              data.DEST.forEach(function (item) {
	                var id = null;
	                var style = null;
	                for (var i = 0; i < patterns.length; i++) {
	                  var matches = item.match(patterns[i].pattern);
	                  if (matches) {
	                    id = matches[1];
	                    style = item === 'UA' ? 'all-users' : patterns[i].style;
	                    break;
	                  }
	                }
	                if (!main_core.Type.isNull(id)) {
	                  postData.post.PostDestination.push({
	                    STYLE: style,
	                    ID: id,
	                    TITLE: main_core.Type.isPlainObject(data.DEST_DATA[item]) && main_core.Type.isStringFilled(data.DEST_DATA[item].title) ? data.DEST_DATA[item].title : ''
	                  });
	                }
	              });
	            }
	            if (main_core.Type.isStringFilled(data.BACKGROUND_CODE)) {
	              postData.post.PostBackgroundCode = data.BACKGROUND_CODE;
	            }
	            if (data.IMPORTANT === 'Y') {
	              postData.post.PostImportantData = {
	                value: 'Y'
	              };
	              if (main_core.Type.isStringFilled(data.IMPORTANT_DATE_END)) {
	                postData.post.PostImportantData.endDate = Date.parse(data.IMPORTANT_DATE_END) / 1000;
	              }
	            }
	            if (main_core.Type.isStringFilled(data.GRATITUDE_MEDAL)) {
	              postData.post.PostGratitudeData = {
	                gratitude: data.GRATITUDE_MEDAL,
	                employees: []
	              };
	              if (Array.isArray(data.GRATITUDE_EMPLOYEES) && main_core.Type.isPlainObject(data.GRATITUDE_EMPLOYEES_DATA)) {
	                data.GRATITUDE_EMPLOYEES.forEach(function (userId) {
	                  var userData = data.GRATITUDE_EMPLOYEES_DATA[userId];
	                  if (!main_core.Type.isPlainObject(userData)) {
	                    return;
	                  }
	                  postData.post.PostGratitudeData.employees.push({
	                    id: userData.id,
	                    imageUrl: main_core.Type.isStringFilled(userData.imageUrl) ? userData.imageUrl : '',
	                    title: main_core.Type.isStringFilled(userData.title) ? userData.title : '',
	                    subtitle: main_core.Type.isStringFilled(userData.subtitle) ? userData.subtitle : ''
	                  });
	                });
	              }
	            }
	            var voteId = 'n0';
	            var dataKey = 'UF_BLOG_POST_VOTE_' + voteId + '_DATA';
	            if (data.UF_BLOG_POST_VOTE === voteId && main_core.Type.isPlainObject(data[dataKey]) && Array.isArray(data[dataKey].QUESTIONS)) {
	              postData.post.PostVoteData = {
	                questions: data[dataKey].QUESTIONS.map(function (question) {
	                  var result = {
	                    value: question.QUESTION,
	                    allowMultiSelect: question.FIELD_TYPE == 1 ? 'Y' : 'N',
	                    answers: []
	                  };
	                  if (Array.isArray(question.ANSWERS)) {
	                    result.answers = question.ANSWERS.map(function (answer) {
	                      return {
	                        value: answer.MESSAGE
	                      };
	                    });
	                  }
	                  return result;
	                })
	              };
	            }
	            resolve(postData);
	          },
	          onEmpty: function onEmpty() {
	            resolve(postData);
	          }
	        }, postData.groupId);
	      });
	      promise["catch"](function (error) {
	        console.error(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "getWorkgroupData",
	    value: function getWorkgroupData(postData) {
	      var groupId = parseInt(postData.groupId);
	      var promise = new Promise(function (resolve, reject) {
	        if (groupId <= 0) {
	          resolve(postData);
	          return;
	        }
	        var promiseData = {
	          resolve: resolve,
	          reject: reject
	        };
	        var currentDateTime = new Date();
	        var returnEventName = 'Livefeed::returnWorkgroupData_' + currentDateTime.getTime();
	        BXMobileApp.addCustomEvent(returnEventName, function (result) {
	          if (result.success) {
	            this.resolve(Object.assign(postData, {
	              group: {
	                ID: parseInt(result.groupData.ID),
	                NAME: result.groupData.NAME
	                //							DESCRIPTION: result.groupData.DESCRIPTION
	              }
	            }));
	          } else {
	            this.reject();
	          }
	        }.bind(promiseData));
	        BXMobileApp.onCustomEvent('Livefeed::getWorkgroupData', {
	          groupId: groupId,
	          returnEventName: returnEventName
	        }, true);
	      });
	      promise["catch"](function (error) {
	        console.error(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "getPostData",
	    value: function getPostData(postData) {
	      var postId = parseInt(postData.postId);
	      var promise = new Promise(function (resolve, reject) {
	        if (postId <= 0) {
	          resolve(postData);
	          return;
	        }
	        var promiseData = {
	          resolve: resolve,
	          reject: reject
	        };
	        var currentDateTime = new Date();
	        var returnEventName = 'Livefeed::returnPostFullData_' + currentDateTime.getTime();
	        BXMobileApp.addCustomEvent(returnEventName, function (result) {
	          if (result.success) {
	            this.resolve(Object.assign(postData, {
	              post: result.postData
	            }));
	          } else {
	            this.reject();
	          }
	        }.bind(promiseData));
	        BXMobileApp.onCustomEvent('Livefeed::getPostFullData', {
	          postId: postId,
	          returnEventName: returnEventName
	        }, true);
	      });
	      promise["catch"](function (error) {
	        console.error(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "processPostData",
	    value: function processPostData(postData) {
	      var postId = parseInt(postData.postId);
	      var promise = new Promise(function (resolve, reject) {
	        if (main_core.Type.isPlainObject(postData.post)) {
	          if (main_core.Type.isArray(postData.post.PostDestination)) {
	            postData.recipients = {};
	            postData.post.PostDestination.forEach(function (item) {
	              var key = null;
	              var code = null;
	              switch (item.STYLE) {
	                case 'users':
	                  key = 'users';
	                  code = item.ID;
	                  break;
	                case 'all-users':
	                  key = 'users';
	                  code = 'A';
	                  break;
	                case 'sonetgroups':
	                  key = 'groups';
	                  code = item.ID;
	                  break;
	                case 'department':
	                  key = 'departments';
	                  code = item.ID;
	                  break;
	                default:
	              }
	              if (key) {
	                if (!main_core.Type.isArray(postData.recipients[key])) {
	                  postData.recipients[key] = [];
	                }
	                postData.recipients[key].push({
	                  id: code,
	                  title: item.TITLE,
	                  shortTitle: main_core.Type.isStringFilled(item.SHORT_TITLE) ? item.SHORT_TITLE : item.TITLE,
	                  avatar: main_core.Type.isStringFilled(item.AVATAR) ? item.AVATAR : ''
	                });
	              }
	            });
	          }
	          if (main_core.Type.isArray(postData.post.PostDestinationHidden)) {
	            postData.hiddenRecipients = [];
	            postData.post.PostDestinationHidden.forEach(function (item) {
	              postData.hiddenRecipients.push(item.TYPE + item.ID);
	            });
	          }
	        } else if (main_core.Type.isPlainObject(postData.group)) {
	          postData.recipients = {
	            groups: [{
	              id: postData.group.ID,
	              title: postData.group.NAME
	            }]
	          };
	        }
	        resolve(postData);
	      });
	      promise["catch"](function (error) {
	        console.error(error);
	      });
	      return promise;
	    }
	  }]);
	  return PostFormManager;
	}();

	var PostFormOldManager = /*#__PURE__*/function () {
	  function PostFormOldManager() {
	    babelHelpers.classCallCheck(this, PostFormOldManager);
	    this.postFormParams = {};
	    this.postFormExtraData = {};
	  }
	  babelHelpers.createClass(PostFormOldManager, [{
	    key: "setExtraDataArray",
	    value: function setExtraDataArray(extraData) {
	      var ob = null;
	      for (var _i = 0, _Object$entries = Object.entries(extraData); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	          key = _Object$entries$_i[0],
	          value = _Object$entries$_i[1];
	        if (extraData.hasOwnProperty(key)) {
	          ob = {};
	          ob[key] = value;
	          this.setExtraData(ob);
	        }
	      }
	    }
	  }, {
	    key: "setExtraData",
	    value: function setExtraData(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        return;
	      }
	      for (var _i2 = 0, _Object$entries2 = Object.entries(params); _i2 < _Object$entries2.length; _i2++) {
	        var _Object$entries2$_i = babelHelpers.slicedToArray(_Object$entries2[_i2], 2),
	          key = _Object$entries2$_i[0],
	          value = _Object$entries2$_i[1];
	        if (key == 'hiddenRecipients' || key == 'logId' || key == 'postId' || key == 'postAuthorId' || key == 'messageUFCode' || key == 'commentId' || key == 'commentType' || key == 'nodeId' || key == 'pinnedContext') {
	          this.postFormExtraData[key] = value;
	        }
	      }
	    }
	  }, {
	    key: "getExtraData",
	    value: function getExtraData() {
	      return this.postFormExtraData;
	    }
	  }, {
	    key: "setParams",
	    value: function setParams(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        return;
	      }
	      for (var _i3 = 0, _Object$entries3 = Object.entries(params); _i3 < _Object$entries3.length; _i3++) {
	        var _Object$entries3$_i = babelHelpers.slicedToArray(_Object$entries3[_i3], 2),
	          key = _Object$entries3$_i[0],
	          value = _Object$entries3$_i[1];
	        if (['selectedRecipients', 'messageText', 'messageFiles'].indexOf(key) !== -1) {
	          this.postFormParams[key] = value;
	        }
	      }
	    }
	  }, {
	    key: "addDestination",
	    value: function addDestination(selectedDestinations, params) {
	      if (!main_core.Type.isPlainObject(params) || !main_core.Type.isStringFilled(params.type)) {
	        return;
	      }
	      var searchRes = null;
	      if (params.type === 'UA') {
	        searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, {
	          value: 0
	        });
	        if (!searchRes) {
	          selectedDestinations.a_users.push({
	            id: 0,
	            name: main_core.Loc.getMessage('MSLPostDestUA'),
	            bubble_background_color: '#A7F264',
	            bubble_text_color: '#54901E'
	          });
	        }
	      } else if (params.type === 'U') {
	        searchRes = selectedDestinations.a_users.some(this.findDestinationCallBack, {
	          value: params.id
	        });
	        if (!searchRes) {
	          selectedDestinations.a_users.push({
	            id: params.id,
	            name: params.name,
	            bubble_background_color: '#BCEDFC',
	            bubble_text_color: '#1F6AB5'
	          });
	        }
	      } else if (params.type === 'SG') {
	        searchRes = selectedDestinations.b_groups.some(this.findDestinationCallBack, {
	          value: params.id
	        });
	        if (!searchRes) {
	          selectedDestinations.b_groups.push({
	            id: params.id,
	            name: params.name,
	            bubble_background_color: '#FFD5D5',
	            bubble_text_color: '#B54827'
	          });
	        }
	      }
	    }
	  }, {
	    key: "findDestinationCallBack",
	    value: function findDestinationCallBack(element, index, array) {
	      return element.id == this.value;
	    }
	  }, {
	    key: "show",
	    value: function show(params) {
	      var _this = this;
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var entityType = main_core.Type.isStringFilled(params.entityType) ? params.entityType : 'post';
	      var extraData = this.getExtraData();
	      var postFormParams = {
	        attachButton: this.getAttachButton(),
	        mentionButton: this.getMentionButton(),
	        attachFileSettings: this.getAttachFileSettings(),
	        extraData: extraData ? extraData : {},
	        smileButton: {},
	        supportLocalFilesInText: entityType === 'post',
	        okButton: {
	          callback: function callback(data) {
	            if (!main_core.Type.isStringFilled(data.text)) {
	              return;
	            }
	            var postData = _this.buildRequestStub({
	              type: entityType,
	              extraData: data.extraData,
	              text: _this.parseMentions(data.text),
	              pinnedContext: main_core.Type.isStringFilled(data.extraData.pinnedContext) && data.extraData.pinnedContext === 'YES'
	            });
	            var ufCode = data.extraData.messageUFCode;
	            _this.buildFiles(postData, data.attachedFiles, {
	              ufCode: ufCode
	            }).then(function () {
	              if (entityType !== 'post') {
	                return;
	              }
	              _this.buildDestinations(postData, data.selectedRecipients, main_core.Type.isPlainObject(data.extraData) && !main_core.Type.isUndefined(data.extraData.hiddenRecipients) ? data.extraData.hiddenRecipients : [], {});
	              if (!postData.postVirtualId) {
	                return;
	              }
	              postData.ufCode = ufCode;
	              postData.contentType = 'post';
	              oMSL.initPostForm({
	                groupId: params.groupId ? params.groupId : null
	              });
	              BXMobileApp.onCustomEvent('Livefeed.PublicationQueue::setItem', {
	                key: postData.postVirtualId,
	                pinnedContext: !!postData.pinnedContext,
	                item: postData,
	                pageId: Instance.getPageId(),
	                groupId: params.groupId ? params.groupId : null
	              }, true);
	            }, function () {});
	          },
	          name: main_core.Loc.getMessage('MSLPostFormSend')
	        },
	        cancelButton: {
	          callback: function callback() {
	            oMSL.initPostForm({
	              groupId: params.groupId ? params.groupId : null
	            });
	          },
	          name: main_core.Loc.getMessage('MSLPostFormCancel')
	        }
	      };
	      if (!main_core.Type.isUndefined(this.postFormParams.messageText)) {
	        postFormParams.message = {
	          text: this.postFormParams.messageText
	        };
	      }
	      if (!main_core.Type.isUndefined(this.postFormParams.messageFiles)) {
	        postFormParams.attachedFiles = this.postFormParams.messageFiles;
	      }
	      if (entityType === 'post') {
	        postFormParams.recipients = {
	          dataSource: this.getRecipientsDataSource()
	        };
	        if (!main_core.Type.isUndefined(this.postFormParams.selectedRecipients)) {
	          postFormParams.recipients.selectedRecipients = this.postFormParams.selectedRecipients;
	        }
	        if (!main_core.Type.isUndefined(this.postFormParams.backgroundCode)) {
	          postFormParams.backgroundCode = this.postFormParams.backgroundCode;
	        }
	      }
	      return postFormParams;
	    }
	  }, {
	    key: "getAttachButton",
	    value: function getAttachButton() {
	      var attachButtonItems = [];
	      if (main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') === 'Y' || main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_WEBDAV_INSTALLED') === 'Y') {
	        var diskAttachParams = {
	          id: 'disk',
	          name: main_core.Loc.getMessage('MSLPostFormDisk'),
	          dataSource: {
	            multiple: 'NO',
	            url: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DISK_INSTALLED') === 'Y' ? "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=").concat(main_core.Loc.getMessage('USER_ID')) : "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/webdav/user/").concat(main_core.Loc.getMessage('USER_ID'), "/")
	          }
	        };
	        var tableSettings = {
	          searchField: 'YES',
	          showtitle: 'YES',
	          modal: 'YES',
	          name: main_core.Loc.getMessage('MSLPostFormDiskTitle')
	        };

	        //FIXME temporary workaround
	        if (window.platform === 'ios') {
	          diskAttachParams.dataSource.table_settings = tableSettings;
	        } else {
	          diskAttachParams.dataSource.TABLE_SETTINGS = tableSettings;
	        }
	        attachButtonItems.push(diskAttachParams);
	      }
	      attachButtonItems.push({
	        id: 'mediateka',
	        name: main_core.Loc.getMessage('MSLPostFormPhotoGallery')
	      });
	      attachButtonItems.push({
	        id: 'camera',
	        name: main_core.Loc.getMessage('MSLPostFormPhotoCamera')
	      });
	      return {
	        items: attachButtonItems
	      };
	    }
	  }, {
	    key: "getMentionButton",
	    value: function getMentionButton() {
	      return {
	        dataSource: {
	          return_full_mode: 'YES',
	          outsection: 'NO',
	          okname: main_core.Loc.getMessage('MSLPostFormTableOk'),
	          cancelname: main_core.Loc.getMessage('MSLPostFormTableCancel'),
	          multiple: 'NO',
	          alphabet_index: 'YES',
	          url: "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/index.php?mobile_action=get_user_list&use_name_format=Y")
	        }
	      };
	    }
	  }, {
	    key: "getAttachFileSettings",
	    value: function getAttachFileSettings() {
	      return {
	        resize: [40, 1, 1, 1000, 1000, 0, 2, false, true, false, null, 0],
	        saveToPhotoAlbum: true
	      };
	    }
	  }, {
	    key: "buildRequestStub",
	    value: function buildRequestStub(params) {
	      var request = null;
	      if (params.type === 'post') {
	        request = {
	          ACTION: 'ADD_POST',
	          AJAX_CALL: 'Y',
	          PUBLISH_STATUS: 'P',
	          is_sent: 'Y',
	          apply: 'Y',
	          sessid: main_core.Loc.getMessage('bitrix_sessid'),
	          POST_MESSAGE: params.text,
	          decode: 'Y',
	          SPERM: {},
	          SPERM_NAME: {},
	          MOBILE: 'Y',
	          PARSE_PREVIEW: 'Y'
	        };
	        if (!main_core.Type.isUndefined(params.extraData.postId) && parseInt(params.extraData.postId) > 0) {
	          request.post_id = parseInt(params.extraData.postId);
	          request.post_user_id = parseInt(params.extraData.postAuthorId);
	          request.pinnedContext = !!params.pinnedContext;
	          request.ACTION = 'EDIT_POST';
	          if (!main_core.Type.isUndefined(params.extraData.logId) && parseInt(params.extraData.logId) > 0) {
	            request.log_id = parseInt(params.extraData.logId);
	          }
	        }
	      } else if (params.type === 'comment' && !main_core.Type.isUndefined(params.extraData.commentId) && parseInt(params.extraData.commentId) > 0 && main_core.Type.isStringFilled(params.extraData.commentType)) {
	        request = {
	          action: 'EDIT_COMMENT',
	          text: this.parseMentions(params.text),
	          commentId: parseInt(params.extraData.commentId),
	          nodeId: params.extraData.nodeId,
	          sessid: main_core.Loc.getMessage('bitrix_sessid')
	        };
	        if (params.extraData.commentType === 'blog') {
	          request.comment_post_id = null;
	        }
	      }
	      return request;
	    }
	  }, {
	    key: "parseMentions",
	    value: function parseMentions(text) {
	      var parsedText = text;
	      if (typeof oMSL.arMention != 'undefined') {
	        for (var _i4 = 0, _Object$entries4 = Object.entries(oMSL.arMention); _i4 < _Object$entries4.length; _i4++) {
	          var _Object$entries4$_i = babelHelpers.slicedToArray(_Object$entries4[_i4], 2),
	            key = _Object$entries4$_i[0],
	            value = _Object$entries4$_i[1];
	          parsedText = parsedText.replace(new RegExp(key, 'g'), value);
	        }
	        oMSL.arMention = {};
	        oMSL.commentTextCurrent = '';
	      }
	      return parsedText;
	    }
	  }, {
	    key: "buildFiles",
	    value: function buildFiles(postData, attachedFiles, params) {
	      var _this2 = this;
	      var promise = new Promise(function (resolve, reject) {
	        var ufCode = params.ufCode;
	        postData.postVirtualId = parseInt(Math.random() * 100000);
	        postData.tasksList = [];
	        if (main_core.Type.isArray(attachedFiles) && attachedFiles.length > 0) {
	          var readedFileCount = 0;
	          var fileTotal = attachedFiles.length;
	          var fileCountIncrement = function fileCountIncrement() {
	            readedFileCount++;
	            if (readedFileCount >= fileTotal) {
	              _this2.postProgressingFiles(postData, attachedFiles, params);
	              resolve();
	            }
	          };
	          var uploadTasks = [];
	          attachedFiles.forEach(function (fileData) {
	            var isFileFromBitrix24Disk = !main_core.Type.isUndefined(fileData.VALUE) // Android
	            || !main_core.Type.isUndefined(fileData.id) && parseInt(fileData.id) > 0 // disk object
	            || main_core.Type.isPlainObject(fileData.dataAttributes) && !main_core.Type.isUndefined(fileData.dataAttributes.VALUE) // iOS and modern Android too
	            || main_core.Type.isStringFilled(fileData.ufCode) && fileData.ufCode === ufCode;
	            var isNewFileOnDevice = main_core.Type.isUndefined(fileData.url) || !main_core.Type.isNumber(fileData.id);
	            if (main_core.Type.isStringFilled(fileData.url) && isNewFileOnDevice && !isFileFromBitrix24Disk) {
	              var taskId = "postTask_".concat(parseInt(Math.random() * 100000));
	              var mimeType = mobile_utils.MobileUtils.getFileMimeType(fileData.type);
	              uploadTasks.push({
	                taskId: taskId,
	                type: fileData.type,
	                mimeType: mimeType,
	                folderId: parseInt(main_core.Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
	                //							chunk: parseInt(Loc.getMessage('MOBILE_EXT_UTILS_MAX_UPLOAD_CHUNK_SIZE')),
	                params: {
	                  postVirtualId: postData.postVirtualId,
	                  pinnedContext: !!postData.pinnedContext
	                },
	                name: mobile_utils.MobileUtils.getUploadFilename(fileData.name, fileData.type),
	                url: fileData.url,
	                previewUrl: fileData.previewUrl ? fileData.previewUrl : null,
	                resize: mobile_utils.MobileUtils.getResizeOptions(fileData.type)
	              });
	              postData.tasksList.push(taskId);
	            } else {
	              if (isFileFromBitrix24Disk) {
	                if (main_core.Type.isUndefined(postData[ufCode])) {
	                  postData[ufCode] = [];
	                }
	                if (!main_core.Type.isUndefined(fileData.VALUE)) {
	                  postData[ufCode].push(fileData.VALUE);
	                } else if (parseInt(fileData.id) > 0) {
	                  postData[ufCode].push(parseInt(fileData.id));
	                } else {
	                  postData[ufCode].push(fileData.dataAttributes.VALUE);
	                }
	              }
	              fileCountIncrement();
	            }
	          });
	          if (uploadTasks.length > 0) {
	            BXMobileApp.onCustomEvent('onFileUploadTaskReceived', {
	              files: uploadTasks
	            }, true);
	          }
	          resolve();
	        } else {
	          _this2.postProgressingFiles(postData, attachedFiles, params);
	          resolve();
	        }
	      });
	      promise["catch"](function (error) {
	        console.error(error);
	      });
	      return promise;
	    }
	  }, {
	    key: "buildDestinations",
	    value: function buildDestinations(postData, selectedRecipients, hiddenRecipients, params) {
	      postData['DEST'] = [];
	      if (main_core.Type.isPlainObject(selectedRecipients.a_users)) {
	        for (var _i5 = 0, _Object$entries5 = Object.entries(selectedRecipients.a_users); _i5 < _Object$entries5.length; _i5++) {
	          var _Object$entries5$_i = babelHelpers.slicedToArray(_Object$entries5[_i5], 2),
	            key = _Object$entries5$_i[0],
	            userData = _Object$entries5$_i[1];
	          var prefix = 'U';
	          if (main_core.Type.isUndefined(postData.SPERM[prefix])) {
	            postData.SPERM[prefix] = [];
	          }
	          if (main_core.Type.isUndefined(postData.SPERM_NAME[prefix])) {
	            postData.SPERM_NAME[prefix] = [];
	          }
	          var id = !main_core.Type.isUndefined(userData.ID) ? userData.ID : userData.id;
	          var name = !main_core.Type.isUndefined(userData.NAME) ? userData.NAME : userData.name;
	          var value = parseInt(id) === 0 ? 'UA' : "U".concat(id);
	          postData.SPERM[prefix].push(value);
	          postData.DEST.push(value);
	          postData.SPERM_NAME[prefix].push(name);
	        }
	      }
	      if (main_core.Type.isPlainObject(selectedRecipients.b_groups)) {
	        for (var _i6 = 0, _Object$entries6 = Object.entries(selectedRecipients.b_groups); _i6 < _Object$entries6.length; _i6++) {
	          var _Object$entries6$_i = babelHelpers.slicedToArray(_Object$entries6[_i6], 2),
	            _key = _Object$entries6$_i[0],
	            groupData = _Object$entries6$_i[1];
	          var _prefix = 'SG';
	          if (main_core.Type.isUndefined(postData.SPERM[_prefix])) {
	            postData.SPERM[_prefix] = [];
	          }
	          if (main_core.Type.isUndefined(postData.SPERM_NAME[_prefix])) {
	            postData.SPERM_NAME[_prefix] = [];
	          }
	          var _id = !main_core.Type.isUndefined(groupData.ID) ? groupData.ID : groupData.id;
	          var _name = !main_core.Type.isUndefined(groupData.NAME) ? groupData.NAME : groupData.name;
	          var _value = "SG".concat(_id);
	          postData.SPERM[_prefix].push(_value);
	          postData.DEST.push(_value);
	          postData.SPERM_NAME[_prefix].push(_name);
	        }
	      }
	      for (var _key2 in hiddenRecipients) {
	        if (!hiddenRecipients.hasOwnProperty(_key2)) {
	          continue;
	        }
	        var _prefix2 = hiddenRecipients[_key2].TYPE;
	        if (main_core.Type.isUndefined(postData.SPERM[_prefix2])) {
	          postData.SPERM[_prefix2] = [];
	        }
	        var _value2 = "".concat(hiddenRecipients[_key2].TYPE).concat(hiddenRecipients[_key2].ID);
	        postData.SPERM[_prefix2].push(_value2);
	        postData.DEST.push(_value2);
	      }
	    }
	  }, {
	    key: "getRecipientsDataSource",
	    value: function getRecipientsDataSource() {
	      return {
	        return_full_mode: 'YES',
	        outsection: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_DEST_TO_ALL_DENIED') !== 'Y' ? 'YES' : 'NO',
	        okname: main_core.Loc.getMessage('MSLPostFormTableOk'),
	        cancelname: main_core.Loc.getMessage('MSLPostFormTableCancel'),
	        multiple: 'YES',
	        alphabet_index: 'YES',
	        showtitle: 'YES',
	        user_all: 'YES',
	        url: "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/index.php?mobile_action=").concat(main_core.Loc.getmessage('MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE') === 'Y' ? 'get_group_list' : 'get_usergroup_list', "&feature=blog")
	      };
	    }
	  }, {
	    key: "postProgressingFiles",
	    value: function postProgressingFiles(postData, attachedFiles, params) {
	      var ufCode = params.ufCode;
	      if (main_core.Type.isUndefined(postData[ufCode])) {
	        postData[ufCode] = [];
	      }
	      if (main_core.Type.isUndefined(attachedFiles)) {
	        attachedFiles = [];
	      }
	      for (var keyOld in this.postFormParams.messageFiles) /* existing */
	      {
	        if (!this.postFormParams.messageFiles.hasOwnProperty(keyOld)) {
	          continue;
	        }
	        for (var keyNew in attachedFiles) {
	          if (!attachedFiles.hasOwnProperty(keyNew)) {
	            continue;
	          }
	          if (this.postFormParams.messageFiles[keyOld].id == attachedFiles[keyNew].id || this.postFormParams.messageFiles[keyOld].id == attachedFiles[keyNew].ID) {
	            postData[ufCode].push(this.postFormParams.messageFiles[keyOld].id);
	            break;
	          }
	        }
	      }
	      if (postData[ufCode].length <= 0) {
	        postData[ufCode].push('empty');
	      }
	    }
	  }]);
	  return PostFormOldManager;
	}();

	var _templateObject$1;
	var PinnedPanel = /*#__PURE__*/function () {
	  function PinnedPanel() {
	    var _this = this;
	    babelHelpers.classCallCheck(this, PinnedPanel);
	    this.panelInitialized = false;
	    this["class"] = {
	      panel: 'lenta-pinned-panel',
	      panelCollapsed: 'lenta-pinned-panel-collapsed',
	      panelActive: 'lenta-pinned-panel-active',
	      collapsedPanel: 'lenta-pinned-collapsed-posts',
	      collapsedPanelPostsValue: 'lenta-pinned-collapsed-count-posts',
	      collapsedPanelComments: 'lenta-pinned-collapsed-posts-comments',
	      collapsedPanelCommentsShown: 'lenta-pinned-collapsed-posts-comments-active',
	      collapsedPanelCommentsValue: 'lenta-pinned-collapsed-count-comments',
	      collapsedPanelCommentsValueNew: 'post-item-inform-right-new-value',
	      collapsedPanelExpandButton: 'lenta-pinned-collapsed-posts-btn',
	      post: 'lenta-item',
	      postItemPinned: 'lenta-item-pinned',
	      postItemPinActive: 'lenta-item-pin-active',
	      postItemPinnedBlock: 'post-item-pinned-block',
	      postItemPinnedTitle: 'post-item-pinned-title',
	      postItemPinnedTextBox: 'post-item-pinned-text-box',
	      postItemPinnedDesc: 'post-item-pinned-desc',
	      cancelPanel: 'post-pinned-cancel-panel',
	      cancelPanelButton: 'post-pinned-cancel-panel-btn'
	    };
	    this.init();
	    main_core_events.EventEmitter.subscribe('onFrameDataProcessed', function () {
	      _this.init();
	    });
	  }
	  babelHelpers.createClass(PinnedPanel, [{
	    key: "init",
	    value: function init() {
	      var _this2 = this;
	      var panel = document.querySelector(".".concat(this["class"].panel));
	      if (!panel || this.panelInitialized) {
	        return;
	      }
	      this.panelInitialized = true;
	      this.adjustCollapsedPostsPanel();
	      var collapsedPanel = document.querySelector(".".concat(this["class"].collapsedPanel));
	      if (collapsedPanel) {
	        collapsedPanel.addEventListener('touchend', function (e) {
	          _this2.expandCollapsedPostsPanel();
	          e.stopPropagation();
	          return e.preventDefault();
	        });
	      }
	    }
	  }, {
	    key: "resetFlags",
	    value: function resetFlags() {
	      this.panelInitialized = false;
	    }
	  }, {
	    key: "getPinnedPanelNode",
	    value: function getPinnedPanelNode() {
	      return document.querySelector('[data-livefeed-pinned-panel]');
	    }
	  }, {
	    key: "recalcPanel",
	    value: function recalcPanel(_ref) {
	      var type = _ref.type;
	      if (this.getPostsCount() > 0) {
	        this.getPinnedPanelNode().classList.add("".concat(this["class"].panelActive));
	      } else {
	        this.getPinnedPanelNode().classList.remove("".concat(this["class"].panelActive));
	      }
	      this.recalcCollapsedPostsPanel({
	        type: type
	      });
	    }
	  }, {
	    key: "recalcCollapsedPostsPanel",
	    value: function recalcCollapsedPostsPanel(_ref2) {
	      var type = _ref2.type;
	      if (this.getPostsCount() >= main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_COLLAPSED_PINNED_PANEL_ITEMS_LIMIT')) {
	        if (type === 'insert' || this.getPinnedPanelNode().classList.contains("".concat(this["class"].panelCollapsed))) {
	          this.getPinnedPanelNode().classList.add("".concat(this["class"].panelCollapsed));
	        }
	      } else {
	        this.getPinnedPanelNode().classList.remove("".concat(this["class"].panelCollapsed));
	      }
	      this.adjustCollapsedPostsPanel();
	    }
	  }, {
	    key: "expandCollapsedPostsPanel",
	    value: function expandCollapsedPostsPanel() {
	      this.getPinnedPanelNode().classList.remove("".concat(this["class"].panelCollapsed));
	    }
	  }, {
	    key: "getPostsCount",
	    value: function getPostsCount() {
	      return Array.from(this.getPinnedPanelNode().getElementsByClassName("".concat(this["class"].post))).length;
	    }
	  }, {
	    key: "adjustCollapsedPostsPanel",
	    value: function adjustCollapsedPostsPanel() {
	      var postsCounter = this.getPostsCount();
	      var postsCounterNode = this.getPinnedPanelNode().querySelector(".".concat(this["class"].collapsedPanelPostsValue));
	      if (postsCounterNode) {
	        postsCounterNode.innerHTML = parseInt(postsCounter);
	      }
	      var commentsCounterNode = this.getPinnedPanelNode().querySelector(".".concat(this["class"].collapsedPanelComments));
	      var commentsCounterValueNode = this.getPinnedPanelNode().querySelector(".".concat(this["class"].collapsedPanelCommentsValue));
	      if (commentsCounterNode && commentsCounterValueNode) {
	        var newCommentCounter = Array.from(this.getPinnedPanelNode().querySelectorAll(".".concat(this["class"].collapsedPanelCommentsValueNew))).reduce(function (acc, node) {
	          return acc + parseInt(node.innerHTML);
	        }, 0);
	        commentsCounterValueNode.innerHTML = '+' + newCommentCounter;
	        if (newCommentCounter > 0) {
	          commentsCounterNode.classList.add("".concat(this["class"].collapsedPanelCommentsShown));
	        } else {
	          commentsCounterNode.classList.remove("".concat(this["class"].collapsedPanelCommentsShown));
	        }
	      }
	    }
	  }, {
	    key: "getPinnedData",
	    value: function getPinnedData(params) {
	      var logId = params.logId ? parseInt(params.logId) : 0;
	      if (logId <= 0) {
	        return Promise.reject();
	      }
	      return new Promise(function (resolve, reject) {
	        mobile_ajax.Ajax.runAction('socialnetwork.api.livefeed.logentry.getPinData', {
	          data: {
	            params: {
	              logId: logId
	            }
	          },
	          headers: [{
	            name: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_AJAX_ENTITY_HEADER_NAME'),
	            value: params.entityValue || ''
	          }, {
	            name: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_AJAX_TOKEN_HEADER_NAME'),
	            value: params.tokenValue || ''
	          }]
	        }).then(function (response) {
	          if (response.status === 'success') {
	            resolve(response.data);
	          } else {
	            reject(response.errors);
	          }
	        }, function (response) {
	          reject();
	        });
	      });
	    }
	  }, {
	    key: "insertEntry",
	    value: function insertEntry(_ref3) {
	      var _this3 = this;
	      var logId = _ref3.logId,
	        postNode = _ref3.postNode,
	        pinnedContent = _ref3.pinnedContent;
	      var pinnedPanelNode = this.getPinnedPanelNode();
	      if (!main_core.Type.isDomNode(postNode) || !main_core.Type.isDomNode(pinnedPanelNode)) {
	        return;
	      }
	      var postItemPinnedBlock = postNode.querySelector(".".concat(this["class"].postItemPinnedBlock));
	      if (!main_core.Type.isDomNode(postItemPinnedBlock)) {
	        return;
	      }
	      postNode.classList.add(this["class"].postItemPinned);
	      postNode.classList.add(this["class"].postItemPinActive);
	      postItemPinnedBlock.innerHTML = "".concat(main_core.Type.isStringFilled(pinnedContent.TITLE) ? "<div class=\"".concat(this["class"].postItemPinnedTitle, "\">").concat(pinnedContent.TITLE, "</div>") : '', "<div class=\"").concat(this["class"].postItemPinnedTextBox, "\"><div class=\"").concat(this["class"].postItemPinnedDesc, "\">").concat(pinnedContent.DESCRIPTION, "</div></div>");
	      var cancelPinnedPanel = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\" data-livefeed-id=\"", "\">\n\t\t\t<div class=\"post-pinned-cancel-panel-content\">\n\t\t\t\t<div class=\"post-pinned-cancel-panel-label\">", "</div>\n\t\t\t\t\t<div class=\"post-pinned-cancel-panel-text\">", "</div>\n\t\t\t\t</div>\n\t\t\t<div class=\"ui-btn ui-btn-light-border ui-btn-round ui-btn-sm ", "\">", "</div>\n\t\t</div>"])), this["class"].cancelPanel, logId, main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_TITLE'), main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_DESCRIPTION'), this["class"].cancelPanelButton, main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_PINNED_CANCEL_BUTTON'));
	      var cancelButton = cancelPinnedPanel.querySelector(".".concat(this["class"].cancelPanelButton));
	      cancelButton.addEventListener('touchend', function (event) {
	        var cancelPanel = event.currentTarget.closest(".".concat(_this3["class"].cancelPanel));
	        if (!cancelPanel) {
	          return;
	        }
	        var logId = parseInt(cancelPanel.getAttribute('data-livefeed-id'));
	        if (logId <= 0) {
	          return;
	        }
	        var postNode = document.querySelector(".".concat(_this3["class"].post, "[data-livefeed-id=\"").concat(logId, "\"]"));
	        if (!postNode) {
	          return;
	        }
	        var menuNode = postNode.querySelector('[data-menu-type="post"]');
	        if (!menuNode) {
	          return;
	        }
	        var postInstance = new Post$$1({
	          logId: logId
	        });
	        return postInstance.setPinned({
	          menuNode: menuNode,
	          context: 'list'
	        });
	      });
	      postNode.parentNode.insertBefore(cancelPinnedPanel, postNode);
	      main_core.Dom.prepend(postNode, pinnedPanelNode);
	      this.recalcPanel({
	        type: 'insert'
	      });
	      PageInstance.onScroll();
	    }
	  }, {
	    key: "extractEntry",
	    value: function extractEntry(_ref4) {
	      var logId = _ref4.logId,
	        postNode = _ref4.postNode,
	        containerNode = _ref4.containerNode;
	      var pinnedPanelNode = this.getPinnedPanelNode();
	      if (!main_core.Type.isDomNode(postNode) || !main_core.Type.isDomNode(containerNode) || !main_core.Type.isDomNode(pinnedPanelNode) || postNode.parentNode !== pinnedPanelNode || parseInt(logId) <= 0) {
	        return;
	      }
	      postNode.classList.remove(this["class"].postItemPinned);
	      postNode.classList.remove(this["class"].postItemPinActive);
	      var cancelPanel = document.querySelector(".".concat(this["class"].cancelPanel, "[data-livefeed-id=\"").concat(parseInt(logId), "\"]"));
	      if (cancelPanel) {
	        cancelPanel.parentNode.insertBefore(postNode, cancelPanel);
	        main_core.Dom.remove(cancelPanel);
	      } else {
	        main_core.Dom.prepend(postNode, containerNode);
	      }
	      this.recalcPanel({
	        type: 'extract'
	      });
	    }
	  }]);
	  return PinnedPanel;
	}();

	var Rating = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Rating, _EventEmitter);
	  function Rating() {
	    var _this;
	    babelHelpers.classCallCheck(this, Rating);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Rating).call(this));
	    _this.init();
	    return _this;
	  }
	  babelHelpers.createClass(Rating, [{
	    key: "init",
	    value: function init() {
	      this.setEventNamespace('BX.Mobile.Livefeed');
	      this.subscribe('onFeedInit', this.onFeedInit.bind(this));
	    }
	  }, {
	    key: "onFeedInit",
	    value: function onFeedInit() {
	      if (!window.BXRL) {
	        return;
	      }
	      Object.keys(window.BXRL).forEach(function (key) {
	        if (key !== 'manager' && key !== 'render') {
	          delete window.BXRL[key];
	        }
	      });
	    }
	  }]);
	  return Rating;
	}(main_core_events.EventEmitter);

	var ImportantManager = /*#__PURE__*/function () {
	  function ImportantManager() {
	    babelHelpers.classCallCheck(this, ImportantManager);
	  }
	  babelHelpers.createClass(ImportantManager, [{
	    key: "setPostRead",
	    value: function setPostRead(node) {
	      var _this = this;
	      if (!main_core.Type.isDomNode(node) || node.hasAttribute('done')) {
	        return false;
	      }
	      var postId = parseInt(node.getAttribute('bx-data-post-id'));
	      if (postId <= 0) {
	        return false;
	      }
	      this.renderRead({
	        node: node,
	        value: true
	      });
	      mobile_ajax.Ajax.runAction('socialnetwork.api.livefeed.blogpost.important.vote', {
	        data: {
	          params: {
	            POST_ID: postId
	          }
	        }
	      }).then(function (response) {
	        if (!main_core.Type.isStringFilled(response.data.success) || response.data.success !== 'Y') {
	          _this.renderRead({
	            node: node,
	            value: false
	          });
	        } else {
	          BXMobileApp.onCustomEvent('onLogEntryImpPostRead', {
	            postId: postId
	          }, true);
	        }
	      }, function (response) {
	        _this.renderRead({
	          node: node,
	          value: false
	        });
	      });
	      return true;
	    }
	  }, {
	    key: "renderRead",
	    value: function renderRead(params) {
	      if (!main_core.Type.isObject(params) || !main_core.Type.isDomNode(params.node)) {
	        return;
	      }
	      var node = params.node;
	      var value = !!params.value;
	      if (value) {
	        node.checked = true;
	        node.setAttribute('done', 'Y');
	        main_core.Event.unbindAll(node);
	      } else {
	        node.checked = false;
	        delete node.checked;
	        node.removeAttribute('done');
	      }
	      var container = node.closest('.post-item-important');
	      if (!container) {
	        return;
	      }
	      var listNode = container.querySelector('.post-item-important-list');
	      if (!listNode) {
	        return;
	      }
	      listNode.classList.add('post-item-important-list-read');
	    }
	  }]);
	  return ImportantManager;
	}();

	var SearchBar = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(SearchBar, _EventEmitter);
	  function SearchBar() {
	    var _this;
	    babelHelpers.classCallCheck(this, SearchBar);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SearchBar).call(this));
	    _this.findTextMode = false;
	    _this.ftMinTokenSize = 3;
	    _this.hideByRefresh = false;
	    return _this;
	  }
	  babelHelpers.createClass(SearchBar, [{
	    key: "init",
	    value: function init(params) {
	      var _this2 = this;
	      if (BXMobileAppContext.getApiVersion() < Instance.getApiVersion('pageMenu')) {
	        return;
	      }
	      if (main_core.Type.isPlainObject(params) && parseInt(params.ftMinTokenSize) > 0) {
	        this.ftMinTokenSize = parseInt(params.ftMinTokenSize);
	      }
	      this.subscribe('onSearchBarCancelButtonClicked', this.searchBarEventCallback.bind(this));
	      this.subscribe('onSearchBarSearchButtonClicked', this.searchBarEventCallback.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.MobileLivefeed.SearchBar::setHideByRefresh', this.setHideByRefresh.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.MobileLivefeed.SearchBar::unsetHideByRefresh', this.unsetHideByRefresh.bind(this));
	      BXMobileApp.UI.Page.params.set({
	        useSearchBar: true
	      });
	      app.exec('setParamsSearchBar', {
	        params: {
	          callback: function callback(event) {
	            if (event.eventName === 'onSearchButtonClicked' && main_core.Type.isPlainObject(event.data) && main_core.Type.isStringFilled(event.data.text)) {
	              if (event.data.text.length >= _this2.ftMinTokenSize) {
	                _this2.findTextMode = true;
	              }
	              _this2.emit('onSearchBarSearchButtonClicked', new main_core_events.BaseEvent({
	                data: {
	                  text: event.data.text
	                }
	              }));
	            } else if (['onCancelButtonClicked', 'onSearchHide'].includes(event.eventName)) {
	              _this2.emit('onSearchBarCancelButtonClicked', new main_core_events.BaseEvent({
	                data: {}
	              }));
	            }
	          }
	        }
	      });
	    }
	  }, {
	    key: "searchBarEventCallback",
	    value: function searchBarEventCallback(event) {
	      var _this3 = this;
	      var eventData = event.getData();
	      var text = main_core.Type.isPlainObject(eventData) && main_core.Type.isStringFilled(eventData.text) ? eventData.text : '';
	      if (text.length >= this.ftMinTokenSize) {
	        app.exec('showSearchBarProgress');
	        this.emitRefreshEvent(text);
	      } else {
	        if (this.findTextMode) {
	          if (!this.hideByRefresh) {
	            main_core_events.EventEmitter.emit('BX.MobileLF:onSearchBarRefreshAbort');
	          }
	          if (BX.frameCache) {
	            app.exec('hideSearchBarProgress');
	            BX.frameCache.readCacheWithID('framecache-block-feed', function (params) {
	              var container = document.getElementById('bxdynamic_feed_refresh');
	              if (!main_core.Type.isArray(params.items) || !container) {
	                _this3.emitRefreshEvent();
	                return;
	              }
	              var block = params.items.find(function (item) {
	                return main_core.Type.isStringFilled(item.ID) && item.ID === 'framecache-block-feed';
	              });
	              if (main_core.Type.isUndefined(block)) {
	                return;
	              }
	              main_core.Runtime.html(container, block.CONTENT).then(function () {
	                BX.processHTML(block.CONTENT, true);
	              });
	              Post$$1.moveTop();
	              setTimeout(function () {
	                BitrixMobile.LazyLoad.showImages();
	              }, 1000);
	            });
	          } else {
	            this.emitRefreshEvent();
	          }
	        }
	        this.findTextMode = false;
	      }
	    }
	  }, {
	    key: "setHideByRefresh",
	    value: function setHideByRefresh() {
	      this.hideByRefresh = true;
	    }
	  }, {
	    key: "unsetHideByRefresh",
	    value: function unsetHideByRefresh() {
	      this.hideByRefresh = false;
	    }
	  }, {
	    key: "emitRefreshEvent",
	    value: function emitRefreshEvent(text) {
	      if (PageInstance.refreshXhr) {
	        return;
	      }
	      text = text || '';
	      var event = new main_core_events.BaseEvent({
	        compatData: [{
	          text: text
	        }]
	      });
	      main_core_events.EventEmitter.emit('BX.MobileLF:onSearchBarRefreshStart', event);
	    }
	  }]);
	  return SearchBar;
	}(main_core_events.EventEmitter);

	var PageScroll = /*#__PURE__*/function () {
	  function PageScroll() {
	    babelHelpers.classCallCheck(this, PageScroll);
	    this.canCheckScrollButton = true;
	    this.showScrollButtonTimeout = null;
	    this.showScrollButtonBottom = false;
	    this.showScrollButtonTop = false;
	    this["class"] = {
	      scrollButton: 'post-comment-block-scroll',
	      scrollButtonTop: 'post-comment-block-scroll-top',
	      scrollButtonBottom: 'post-comment-block-scroll-bottom',
	      scrollButtonTopActive: 'post-comment-block-scroll-top-active',
	      scrollButtonBottomActive: 'post-comment-block-scroll-bottom-active'
	    };
	    this.init();
	  }
	  babelHelpers.createClass(PageScroll, [{
	    key: "init",
	    value: function init() {
	      if (window.platform === 'ios') {
	        return;
	      }
	      document.addEventListener('scroll', this.onScrollDetail.bind(this));
	    }
	  }, {
	    key: "onScrollDetail",
	    value: function onScrollDetail() {
	      var _this = this;
	      if (!this.canCheckScrollButton) {
	        return;
	      }
	      clearTimeout(this.showScrollButtonTimeout);
	      this.showScrollButtonTimeout = setTimeout(function () {
	        Instance.setLastActivityDate();
	        _this.checkScrollButton();
	      }, 200);
	    }
	  }, {
	    key: "checkScrollButton",
	    value: function checkScrollButton() {
	      var scrollTop = window.scrollY; // document.body.scrollTop
	      var maxScroll = document.documentElement.scrollHeight - window.innerHeight - 100; // (this.keyboardShown ? 500 : 300)

	      this.showScrollButtonBottom = !(document.documentElement.scrollHeight - window.innerHeight <= 0 // short page
	      || scrollTop >= maxScroll // too much low
	      && (scrollTop > 0 // refresh patch
	      || maxScroll > 0));
	      this.showScrollButtonTop = scrollTop > 200;
	      this.showHideScrollButton();
	    }
	  }, {
	    key: "showHideScrollButton",
	    value: function showHideScrollButton() {
	      var postScrollButtonBottom = document.querySelector(".".concat(this["class"].scrollButtonBottom));
	      var postScrollButtonTop = document.querySelector(".".concat(this["class"].scrollButtonTop));
	      if (postScrollButtonBottom) {
	        if (this.showScrollButtonBottom) {
	          if (!postScrollButtonBottom.classList.contains("".concat(this["class"].scrollButtonBottomActive))) {
	            postScrollButtonBottom.classList.add("".concat(this["class"].scrollButtonBottomActive));
	          }
	        } else {
	          if (postScrollButtonBottom.classList.contains("".concat(this["class"].scrollButtonBottomActive))) {
	            postScrollButtonBottom.classList.remove("".concat(this["class"].scrollButtonBottomActive));
	          }
	        }
	      }
	      if (postScrollButtonTop) {
	        if (this.showScrollButtonTop) {
	          if (!postScrollButtonTop.classList.contains("".concat(this["class"].scrollButtonTopActive))) {
	            postScrollButtonTop.classList.add("".concat(this["class"].scrollButtonTopActive));
	          }
	        } else {
	          if (postScrollButtonTop.classList.contains("".concat(this["class"].scrollButtonTopActive))) {
	            postScrollButtonTop.classList.remove("".concat(this["class"].scrollButtonTopActive));
	          }
	        }
	      }
	    }
	  }, {
	    key: "scrollTo",
	    value: function scrollTo(type) {
	      var _this2 = this;
	      if (type !== 'top') {
	        type = 'bottom';
	      }
	      this.canCheckScrollButton = false;
	      this.showScrollButtonBottom = false;
	      this.showScrollButtonTop = false;
	      this.showHideScrollButton();
	      var startValue = window.scrollY; // document.body.scrollTop
	      var finishValue = type == 'bottom' ? document.documentElement.scrollHeight : 0;
	      BitrixAnimation.animate({
	        duration: 500,
	        start: {
	          scroll: startValue
	        },
	        finish: {
	          scroll: finishValue
	        },
	        transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
	        step: function step(state) {
	          window.scrollTo(0, state.scroll);
	        },
	        complete: function complete() {
	          _this2.canCheckScrollButton = true;
	          _this2.checkScrollButton();
	        }
	      });
	    }
	  }]);
	  return PageScroll;
	}();

	var FollowManager = /*#__PURE__*/function () {
	  function FollowManager() {
	    babelHelpers.classCallCheck(this, FollowManager);
	    this.defaultValue = true;
	    this.value = true;
	    this["class"] = {
	      postItemFollow: 'post-item-follow',
	      postItemFollowActive: 'post-item-follow-active'
	    };
	  }
	  babelHelpers.createClass(FollowManager, [{
	    key: "init",
	    value: function init() {}
	  }, {
	    key: "setFollow",
	    value: function setFollow(params) {
	      var _this = this;
	      var logId = !main_core.Type.isUndefined(params.logId) ? parseInt(params.logId) : 0;
	      var pageId = !main_core.Type.isUndefined(params.pageId) ? params.pageId : false;
	      var runEvent = !main_core.Type.isUndefined(params.bRunEvent) ? params.bRunEvent : true;
	      var useAjax = !main_core.Type.isUndefined(params.bAjax) ? params.bAjax : false;
	      var turnOnOnly = typeof params.bOnlyOn != 'undefined' ? params.bOnlyOn : false;
	      if (turnOnOnly == 'NO') {
	        turnOnOnly = false;
	      }
	      var menuNode = null;
	      if (main_core.Type.isDomNode(params.menuNode)) {
	        menuNode = params.menuNode;
	      } else if (main_core.Type.isStringFilled(params.menuNode)) {
	        menuNode = document.getElementById(params.menuNode);
	      }
	      if (!menuNode) {
	        menuNode = document.getElementById("log-entry-menu-".concat(logId));
	      }
	      var followBlock = document.getElementById("log_entry_follow_".concat(logId));
	      if (!followBlock) {
	        followBlock = document.getElementById("log_entry_follow");
	      }
	      var followWrap = document.getElementById("post_item_top_wrap_".concat(logId));
	      if (!followWrap) {
	        followWrap = document.getElementById("post_item_top_wrap");
	      }
	      var oldValue = null;
	      if (menuNode) {
	        oldValue = menuNode.getAttribute('data-follow') === 'Y' ? 'Y' : 'N';
	      } else if (followBlock) {
	        oldValue = followBlock.getAttribute('data-follow') == 'Y' ? 'Y' : 'N';
	      } else {
	        return false;
	      }
	      var newValue = oldValue === 'Y' ? 'N' : 'Y';
	      if ((!main_core.Type.isStringFilled(Instance.getOption('detailPageId')) || Instance.getOption('detailPageId') !== pageId) && (!turnOnOnly || oldValue === 'N')) {
	        this.drawFollow({
	          value: oldValue !== 'Y',
	          followBlock: followBlock,
	          followWrap: followWrap,
	          menuNode: menuNode,
	          runEvent: runEvent,
	          turnOnOnly: turnOnOnly,
	          logId: logId
	        });
	      }
	      if (useAjax) {
	        mobile_ajax.Ajax.runAction('socialnetwork.api.livefeed.changeFollow', {
	          data: {
	            logId: logId,
	            value: newValue
	          },
	          analyticsLabel: {
	            b24statAction: newValue === 'Y' ? 'setFollow' : 'setUnfollow'
	          }
	        }).then(function (response) {
	          if (response.data.success) {
	            return;
	          }
	          _this.drawFollow({
	            value: oldValue === 'Y',
	            followBlock: followBlock,
	            followWrap: followWrap,
	            menuNode: menuNode,
	            runEvent: true,
	            turnOnOnly: turnOnOnly,
	            logId: logId
	          });
	        }, function (response) {
	          _this.drawFollow({
	            value: oldValue === 'Y',
	            followBlock: followBlock,
	            followWrap: followWrap,
	            menuNode: menuNode,
	            runEvent: false
	          });
	        });
	      }
	      return false;
	    }
	  }, {
	    key: "drawFollow",
	    value: function drawFollow(params) {
	      var value = main_core.Type.isBoolean(params.value) ? params.value : null;
	      if (main_core.Type.isNull(value)) {
	        return;
	      }
	      var followBlock = main_core.Type.isDomNode(params.followBlock) ? params.followBlock : null;
	      if (followBlock) {
	        followBlock.classList.remove(value ? this["class"].postItemFollow : this["class"].postItemFollowActive);
	        followBlock.classList.add(value ? this["class"].postItemFollowActive : this["class"].postItemFollow);
	        followBlock.setAttribute('data-follow', value ? 'Y' : 'N');
	      }
	      var followWrap = main_core.Type.isDomNode(params.followWrap) ? params.followWrap : null;
	      if (followWrap && !this.getFollowDefaultValue()) {
	        if (value) {
	          followWrap.classList.add(this["class"].postItemFollow);
	        } else {
	          followWrap.classList.remove(this["class"].postItemFollow);
	        }
	      }
	      var menuNode = main_core.Type.isDomNode(params.menuNode) ? params.menuNode : null;
	      if (menuNode) {
	        menuNode.setAttribute('data-follow', value ? 'Y' : 'N');
	      }
	      var detailPageId = Instance.getOption('detailPageId');
	      if (main_core.Type.isStringFilled(detailPageId)) {
	        this.setFollowValue(value);
	        this.setFollowMenuItemName();
	      }
	      var runEvent = main_core.Type.isBoolean(params.runEvent) ? params.runEvent : false;
	      var logId = !main_core.Type.isUndefined(params.logId) ? parseInt(params.logId) : 0;
	      var turnOnOnly = main_core.Type.isBoolean(params.turnOnOnly) ? params.turnOnOnly : false;
	      if (runEvent && logId > 0) {
	        BXMobileApp.onCustomEvent('onLogEntryFollow', {
	          logId: logId,
	          pageId: main_core.Type.isStringFilled(detailPageId) ? detailPageId : '',
	          bOnlyOn: turnOnOnly ? 'Y' : 'N'
	        }, true);
	      }
	    }
	  }, {
	    key: "setFollowDefault",
	    value: function setFollowDefault(params) {
	      var _this2 = this;
	      if (main_core.Type.isUndefined(params.value)) {
	        return;
	      }
	      var value = !!params.value;
	      if (!main_core.Type.isStringFilled(Instance.getOption('detailPageId'))) {
	        this.setFollowDefaultValue(value);
	        this.setDefaultFollowMenuItemName();
	      }
	      var postData = {
	        sessid: BX.bitrix_sessid(),
	        site: main_core.Loc.getMessage('SITE_ID'),
	        lang: main_core.Loc.getMessage('LANGUAGE_ID'),
	        value: value ? 'Y' : 'N',
	        action: 'change_follow_default',
	        mobile_action: 'change_follow_default'
	      };
	      oMSL.changeListMode(postData, function () {
	        oMSL.pullDownAndRefresh();
	      }, function (response) {
	        _this2.setFollowDefaultValue(response.value !== 'Y');
	        _this2.setDefaultFollowMenuItemName();
	      });
	    }
	  }, {
	    key: "setFollowValue",
	    value: function setFollowValue(value) {
	      this.value = !!value;
	    }
	  }, {
	    key: "getFollowValue",
	    value: function getFollowValue() {
	      return this.value;
	    }
	  }, {
	    key: "setFollowDefaultValue",
	    value: function setFollowDefaultValue(value) {
	      this.defaultValue = !!value;
	    }
	  }, {
	    key: "getFollowDefaultValue",
	    value: function getFollowDefaultValue() {
	      return this.defaultValue;
	    }
	  }, {
	    key: "setFollowMenuItemName",
	    value: function setFollowMenuItemName() {
	      var menuItemIndex = PageMenuInstance.detailPageMenuItems.findIndex(function (item) {
	        return main_core.Type.isStringFilled(item.feature) && item.feature === 'follow';
	      });
	      if (menuItemIndex < 0) {
	        return;
	      }
	      var menuItem = PageMenuInstance.detailPageMenuItems[menuItemIndex];
	      menuItem.name = this.getFollowValue() ? main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_Y') : main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_N');
	      PageMenuInstance.detailPageMenuItems[menuItemIndex] = menuItem;
	      PageMenuInstance.init({
	        type: 'detail'
	      });
	    }
	  }, {
	    key: "setDefaultFollowMenuItemName",
	    value: function setDefaultFollowMenuItemName() {
	      var menuItemIndex = PageMenuInstance.listPageMenuItems.findIndex(function (item) {
	        return main_core.Type.isStringFilled(item.feature) && item.feature === 'follow';
	      });
	      if (menuItemIndex < 0) {
	        return;
	      }
	      var menuItem = PageMenuInstance.listPageMenuItems[menuItemIndex];
	      menuItem.name = this.getFollowDefaultValue() ? main_core.Loc.getMessage('MSLMenuItemFollowDefaultY') : main_core.Loc.getMessage('MSLMenuItemFollowDefaultN');
	      PageMenuInstance.listPageMenuItems[menuItemIndex] = menuItem;
	      PageMenuInstance.init({
	        type: 'list'
	      });
	    }
	  }]);
	  return FollowManager;
	}();

	var _templateObject$2, _templateObject2, _templateObject3, _templateObject4;
	var Comments = /*#__PURE__*/function () {
	  function Comments() {
	    babelHelpers.classCallCheck(this, Comments);
	    this.emptyCommentsXhr = null;
	    this.repoLog = {};
	    this.mid = {};
	    this.init();
	  }
	  babelHelpers.createClass(Comments, [{
	    key: "init",
	    value: function init() {
	      main_core_events.EventEmitter.subscribe('OnUCommentWasDeleted', this.deleteHandler.bind(this));
	      main_core_events.EventEmitter.subscribe('OnUCommentWasHidden', this.deleteHandler.bind(this));
	      main_core_events.EventEmitter.subscribe('OnUCRecordHasDrawn', this.drawHandler.bind(this));
	      main_core_events.EventEmitter.subscribe('OnUCFormSubmit', this.submitHandler.bind(this));
	    }
	  }, {
	    key: "deleteHandler",
	    value: function deleteHandler(event) {
	      var _event$getData = event.getData(),
	        _event$getData2 = babelHelpers.slicedToArray(_event$getData, 2),
	        ENTITY_XML_ID = _event$getData2[0],
	        id = _event$getData2[1];
	      var logId = Instance.getLogId();
	      if (this.mid[id.join('-')] !== 'hidden') {
	        this.mid[id.join('-')] = 'hidden';
	        if (this.repoLog[logId]) {
	          this.repoLog[logId]['POST_NUM_COMMENTS']--;
	          BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
	            log_id: logId,
	            num: this.repoLog[logId]['POST_NUM_COMMENTS']
	          }, true);
	        }
	      }
	    }
	  }, {
	    key: "drawHandler",
	    value: function drawHandler(event) {
	      var _event$getData3 = event.getData(),
	        _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 2),
	        ENTITY_XML_ID = _event$getData4[0],
	        id = _event$getData4[1];
	      var logId = Instance.getLogId();
	      this.mid[ENTITY_XML_ID] = this.mid[ENTITY_XML_ID] || {};
	      if (this.mid[id.join('-')] !== 'drawn') {
	        this.mid[id.join('-')] = 'drawn';
	        var node = false;
	        if (this.repoLog[logId] && (node = document.getElementById("record-".concat(id.join('-'), "-cover"))) && node && node.parentNode == document.getElementById("record-".concat(ENTITY_XML_ID, "-new"))) {
	          this.repoLog[logId]['POST_NUM_COMMENTS']++;
	          BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
	            log_id: logId,
	            num: this.repoLog[logId]['POST_NUM_COMMENTS']
	          }, true);
	        }
	      }
	    }
	  }, {
	    key: "submitHandler",
	    value: function submitHandler(event) {
	      var _event$getData5 = event.getData(),
	        _event$getData6 = babelHelpers.slicedToArray(_event$getData5, 4),
	        entity_xml_id = _event$getData6[0],
	        id = _event$getData6[1],
	        obj = _event$getData6[2],
	        post_data = _event$getData6[3];
	      if (post_data && post_data.mobile_action && post_data.mobile_action === 'add_comment' && id > 0) {
	        post_data.mobile_action = post_data.action = 'edit_comment';
	        post_data.edit_id = id;
	      }
	    }
	  }, {
	    key: "setRepoItem",
	    value: function setRepoItem(id, data) {
	      this.repoLog[id] = data;
	    }
	  }, {
	    key: "getList",
	    value: function getList(params) {
	      var _this = this;
	      var timestampValue = params.ts;
	      var pullDown = !!params.bPullDown;
	      var pullDownTop = main_core.Type.isUndefined(params.bPullDownTop) || params.bPullDownTop;
	      var moveBottom = main_core.Type.isUndefined(params.obFocus.form) || params.obFocus.form === 'NO' ? 'NO' : 'YES';
	      var moveTop = main_core.Type.isUndefined(params.obFocus.comments) || params.obFocus.comments === 'NO' ? 'NO' : 'YES';
	      var logId = oMSL.logId;
	      var container = document.getElementById('post-comments-wrap');
	      if (!pullDown) {
	        if (pullDownTop) {
	          BXMobileApp.UI.Page.Refresh.start();
	        }
	        main_core.Dom.clean(container);
	        container.appendChild(main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["<span id=\"post-comment-last-after\"></span>"]))));
	      }
	      this.showEmptyListWaiter({
	        container: container,
	        enable: true
	      });
	      main_core_events.EventEmitter.emit('BX.MobileLF:onCommentsGet');
	      BXMobileApp.UI.Page.TextPanel.hide();
	      this.emptyCommentsXhr = mobile_ajax.Ajax.wrap({
	        type: 'json',
	        method: 'POST',
	        url: "".concat(main_core.Loc.getMessage('MSLPathToLogEntry').replace("#log_id#", logId), "&empty_get_comments=Y").concat(!main_core.Type.isNil(timestampValue) ? "&LAST_LOG_TS=".concat(timestampValue) : ''),
	        data: {},
	        processData: true,
	        callback: function callback(response) {
	          var formWrap = document.getElementById('post-comments-form-wrap');
	          if (pullDown) {
	            app.exec('pullDownLoadingStop');
	          } else if (pullDownTop) {
	            BXMobileApp.UI.Page.Refresh.stop();
	          }
	          _this.showEmptyListWaiter({
	            container: container,
	            enable: false
	          });
	          if (main_core.Type.isStringFilled(response.POST_PERM)) {
	            oMSL.menuData.post_perm = response.POST_PERM;
	            PageMenuInstance.detailPageMenuItems = PageMenuInstance.buildDetailPageMenu(oMSL.menuData);
	            PageMenuInstance.init({
	              type: 'detail'
	            });
	          }
	          if (main_core.Type.isStringFilled(response.TEXT)) {
	            if (pullDown) {
	              main_core.Dom.clean(container);
	              if (!main_core.Type.isUndefined(response.POST_NUM_COMMENTS)) {
	                BXMobileApp.onCustomEvent('onLogEntryCommentsNumRefresh', {
	                  log_id: logId,
	                  num: parseInt(response.POST_NUM_COMMENTS)
	                }, true);
	              }
	            }
	            _this.setRepoItem(logId, {
	              POST_NUM_COMMENTS: response.POST_NUM_COMMENTS
	            });
	            var contentData = BX.processHTML(response.TEXT, true);
	            main_core.Runtime.html(container, contentData.HTML).then(function () {
	              setTimeout(function () {
	                BitrixMobile.LazyLoad.showImages();
	              }, 1000);
	            });
	            container.appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<span id=\"post-comment-last-after\"></span>"]))));
	            var cnt = 0;
	            var func = function func() {
	              cnt++;
	              if (cnt < 100) {
	                if (container.childNodes.length > 0) {
	                  BX.ajax.processScripts(contentData.SCRIPT);
	                } else {
	                  BX.defer(func, _this)();
	                }
	              }
	            };
	            BX.defer(func, _this)();
	            var event = new main_core_events.BaseEvent({
	              compatData: [{
	                mobile: true,
	                ajaxUrl: "".concat(main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR'), "mobile/ajax.php"),
	                commentsContainerId: 'post-comments-wrap',
	                commentsClassName: 'post-comment-wrap'
	              }]
	            });
	            main_core_events.EventEmitter.emit('BX.UserContentView.onInitCall', event);
	            main_core_events.EventEmitter.emit('BX.UserContentView.onClearCall', event);
	            if (!pullDown)
	              // redraw form
	              {
	                if (formWrap) {
	                  formWrap.innerHTML = '';
	                }
	                __MSLDetailPullDownInit(true);
	                if (moveBottom === 'YES') {
	                  _this.setFocusOnComments('form');
	                } else if (moveTop == 'YES') {
	                  _this.setFocusOnComments('list');
	                }
	              }
	            Instance.setLastActivityDate();
	            PageScrollInstance.checkScrollButton();
	            var logIdContainer = document.getElementById('post_log_id');
	            if (!main_core.Type.isUndefined(response.TS) && logIdContainer) {
	              logIdContainer.setAttribute('data-ts', response.TS);
	            }
	          } else {
	            if (!pullDown) {
	              _this.showEmptyListWaiter({
	                container: container,
	                enable: false
	              });
	            }
	            app.alert({
	              title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_TITLE'),
	              text: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_POST_NOT_FOUND_TEXT'),
	              button: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_ALERT_ERROR_BUTTON'),
	              callback: function callback() {
	                BXMobileApp.onCustomEvent('Livefeed::onLogEntryDetailNotFound', {
	                  logId: logId
	                }, true);
	                BXMPage.close();
	              }
	            });
	          }
	        },
	        callback_failure: function callback_failure() {
	          if (pullDown) {
	            app.exec('pullDownLoadingStop');
	          } else {
	            BXMobileApp.UI.Page.Refresh.stop();
	          }
	          _this.showEmptyListWaiter({
	            container: container,
	            enable: false
	          });
	          _this.showEmptyListFailed({
	            container: container,
	            timestampValue: timestampValue,
	            pullDown: pullDown,
	            moveBottom: moveBottom
	          });
	        }
	      });
	    }
	  }, {
	    key: "showEmptyListWaiter",
	    value: function showEmptyListWaiter(params) {
	      var container = params.container;
	      var enable = !!params.enable;
	      if (!main_core.Type.isDomNode(container)) {
	        return;
	      }
	      var waiterNode = container.querySelector('.post-comments-load-btn-wrap');
	      if (waiterNode) {
	        main_core.Dom.clean(waiterNode);
	        main_core.Dom.remove(waiterNode);
	      }
	      if (!enable) {
	        return;
	      }
	      container.appendChild(main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-comments-load-btn-wrap\"><div class=\"post-comments-loader\"></div><div class=\"post-comments-load-text\">", "</div></div>"])), main_core.Loc.getMessage('MSLDetailCommentsLoading')));
	    }
	  }, {
	    key: "showEmptyListFailed",
	    value: function showEmptyListFailed(params) {
	      var _this2 = this;
	      var container = params.container,
	        timestampValue = params.timestampValue,
	        pullDown = params.pullDown,
	        moveBottom = params.moveBottom,
	        data = params.data;
	      if (!main_core.Type.isDomNode(container)) {
	        return;
	      }
	      var errorMessage = main_core.Type.isObject(data) && main_core.Type.isStringFilled(data.ERROR_MESSAGE) ? data.ERROR_MESSAGE : main_core.Loc.getMessage('MSLDetailCommentsFailed');
	      container.appendChild(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-comments-load-btn-wrap\"><div class=\"post-comments-load-text\">", "</div><a class=\"post-comments-load-btn\">", "</a></div>"])), errorMessage, main_core.Loc.getMessage('MSLDetailCommentsReload')));
	      var button = container.querySelector('.post-comments-load-btn');
	      if (!button) {
	        return;
	      }
	      button.addEventListener('click', function (event) {
	        if (main_core.Type.isDomNode(event.target.parent)) {
	          main_core.Dom.clean(event.target.parent);
	          main_core.Dom.remove(event.target.parent);
	        }

	        // repeat get comments request (after error shown)
	        _this2.getList({
	          ts: timestampValue,
	          bPullDown: pullDown,
	          obFocus: {
	            form: false
	          }
	        });
	      });
	      button.addEventListener('touchstart', function (event) {
	        event.target.classList.add('post-comments-load-btn-active');
	      });
	      button.addEventListener('touchend', function (event) {
	        event.target.classList.remove('post-comments-load-btn-active');
	      });
	    }
	  }, {
	    key: "abortXhr",
	    value: function abortXhr() {
	      if (this.emptyCommentsXhr) {
	        this.emptyCommentsXhr.abort();
	      }
	    }
	  }, {
	    key: "setFocusOnComments",
	    value: function setFocusOnComments(type) {
	      type = type === 'list' ? 'list' : 'form';
	      if (type === 'form') {
	        this.setFocusOnCommentForm();
	        Post$$1.moveBottom();
	      } else if (type === 'list') {
	        var container = document.getElementById('post-comments-wrap');
	        if (!container) {
	          return false;
	        }
	        var firstNewComment = container.querySelector('.post-comment-block-new');
	        if (firstNewComment) {
	          window.scrollTo(0, firstNewComment.offsetTop);
	        } else {
	          var firstComment = BX.findChild(container, {
	            className: 'post-comment-block'
	          }, true);
	          window.scrollTo(0, firstComment ? firstComment.offsetTop : 0);
	        }
	      }
	      return false;
	    }
	  }, {
	    key: "setFocusOnCommentForm",
	    value: function setFocusOnCommentForm() {
	      BXMobileApp.UI.Page.TextPanel.focus();
	      return false;
	    }
	  }, {
	    key: "onLogEntryCommentAdd",
	    value: function onLogEntryCommentAdd(logId, value)
	    // for the feed
	    {
	      var newValue;
	      var valuePassed = !main_core.Type.isUndefined(value);
	      value = !main_core.Type.isUndefined(value) ? parseInt(value) : 0;
	      var container = document.getElementById("informer_comments_".concat(logId));
	      var containerNew = document.getElementById("informer_comments_new_".concat(logId));
	      if (container && !containerNew)
	        // detail page
	        {
	          if (value > 0) {
	            newValue = value;
	          } else if (!valuePassed) {
	            newValue = (container.innerHTML.length > 0 ? parseInt(container.innerHTML) : 0) + 1;
	          }
	          if (parseInt(newValue) > 0) {
	            container.innerHTML = newValue;
	            container.style.display = 'inline-block';
	            if (document.getElementById("informer_comments_text2_".concat(logId))) {
	              document.getElementById("informer_comments_text2_".concat(logId)).style.display = 'inline-block';
	            }
	            if (document.getElementById("informer_comments_text_".concat(logId))) {
	              document.getElementById("informer_comments_text_".concat(logId)).style.display = 'none';
	            }
	          }
	        }
	      var containerAll = document.getElementById('comcntleave-all');
	      if (containerAll)
	        // more comments
	        {
	          if (value > 0) {
	            newValue = value;
	          } else if (!valuePassed) {
	            newValue = (containerAll.innerHTML.length > 0 ? parseInt(containerAll.innerHTML) : 0) + 1;
	          }
	          containerAll.innerHTML = newValue;
	        }
	    }
	  }]);
	  return Comments;
	}();

	var Page = /*#__PURE__*/function () {
	  function Page() {
	    babelHelpers.classCallCheck(this, Page);
	    this.isBusyGettingNextPage = false;
	    this.isBusyRefreshing = false;
	    this.pageNumber = 1;
	    this.nextPageXhr = null;
	    this.refreshXhr = null;
	    this.nextUrl = '';
	    this.requestErrorTimeout = {
	      refresh: null,
	      nextPage: null
	    };
	    this["class"] = {
	      notifier: 'lenta-notifier-waiter',
	      notifierActive: 'lenta-notifier-shown'
	    };
	    this.onScroll = this.onScroll.bind(this);
	    this.refreshErrorScroll = this.refreshErrorScroll.bind(this);
	    this.nextPageErrorScroll = this.nextPageErrorScroll.bind(this);
	    this.init();
	  }
	  babelHelpers.createClass(Page, [{
	    key: "init",
	    value: function init() {
	      this.setPageNumber(1);
	    }
	  }, {
	    key: "initScroll",
	    value: function initScroll(enable, process_waiter) {
	      enable = !!enable;
	      process_waiter = !!process_waiter;
	      if (enable) {
	        document.removeEventListener('scroll', this.onScroll);
	        document.addEventListener('scroll', this.onScroll);
	      } else {
	        document.removeEventListener('scroll', this.onScroll);
	      }
	      if (process_waiter && document.getElementById('next_post_more')) {
	        document.getElementById('next_post_more').style.display = enable ? 'block' : 'none';
	      }
	    }
	  }, {
	    key: "onScroll",
	    value: function onScroll() {
	      var _this = this;
	      var deviceMaxScroll = Instance.getMaxScroll();
	      if (!((window.pageYOffset >= deviceMaxScroll || document.documentElement.scrollHeight <= window.innerHeight // when small workarea
	      ) && (window.pageYOffset > 0 // refresh patch
	      || deviceMaxScroll > 0) && !this.isBusyRefreshing && !this.isBusyGettingNextPage)) {
	        return;
	      }
	      if (Instance.getOption('refreshFrameCacheNeeded', false) === true) {
	        return;
	      }
	      document.removeEventListener('scroll', this.onScroll);
	      this.isBusyGettingNextPage = true;
	      this.nextPageXhr = mobile_ajax.Ajax.wrap({
	        type: 'json',
	        method: 'POST',
	        url: this.getNextPageUrl(),
	        data: {},
	        callback: function callback(data) {
	          _this.nextPageXhr = null;
	          if (main_core.Type.isPlainObject(data) && main_core.Type.isPlainObject(data.PROPS) && main_core.Type.isStringFilled(data.PROPS.CONTENT)) {
	            if (main_core.Type.isUndefined(data.LAST_TS) || parseInt(data.LAST_TS) <= 0 || parseInt(main_core.Loc.getMessage('MSLFirstPageLastTS')) <= 0 || parseInt(data.LAST_TS) < parseInt(main_core.Loc.getMessage('MSLFirstPageLastTS')) || parseInt(data.LAST_TS) === parseInt(main_core.Loc.getMessage('MSLFirstPageLastTS')) && (parseInt(data.LAST_ID) <= 0 || parseInt(main_core.Loc.getMessage('MSLFirstPageLastId')) <= 0 || parseInt(data.LAST_ID) !== parseInt(main_core.Loc.getMessage('MSLFirstPageLastId')))) {
	              _this.processAjaxBlock(data.PROPS, {
	                type: 'next',
	                callback: function callback() {
	                  Instance.recalcMaxScroll();
	                  oMSL.registerBlocksToCheck();
	                  setTimeout(oMSL.checkNodesHeight.bind(oMSL), 100);
	                  main_core_events.EventEmitter.emit('BX.UserContentView.onRegisterViewAreaListCall', new main_core_events.BaseEvent({
	                    compatData: [{
	                      containerId: 'lenta_wrapper',
	                      className: 'post-item-contentview',
	                      fullContentClassName: 'post-item-full-content'
	                    }]
	                  }));
	                }
	              });
	              var pageNumber = _this.getPageNumber();
	              if (parseInt(main_core.Loc.getMessage('MSLPageNavNum')) > 0 && pageNumber > 0) {
	                _this.setPageNumber(pageNumber + 1);
	                var nextUrl = main_core.Uri.removeParam(_this.getNextPageUrl(), ['PAGEN_' + main_core.Loc.getMessage('MSLPageNavNum')]);
	                nextUrl = main_core.Uri.addParam(nextUrl, babelHelpers.defineProperty({}, "PAGEN_".concat(parseInt(main_core.Loc.getMessage('MSLPageNavNum'))), _this.getPageNumber() + 1));
	                _this.setNextPageUrl(nextUrl);
	              }
	              document.addEventListener('scroll', _this.onScroll);
	            }
	          } else {
	            _this.requestError('nextPage', true);
	          }
	          _this.isBusyGettingNextPage = false;
	        },
	        callback_failure: function callback_failure() {
	          _this.requestError('nextPage', true);
	          _this.nextPageXhr = null;
	          _this.isBusyGettingNextPage = false;
	        }
	      });
	    }
	  }, {
	    key: "refresh",
	    value: function refresh(bScroll, params) {
	      var _this2 = this;
	      bScroll = !!bScroll;
	      if (this.isBusyGettingNextPage && !main_core.Type.isNull(this.nextPageXhr)) {
	        this.nextPageXhr.abort();
	      }
	      var notifier = document.getElementById('lenta_notifier');
	      if (notifier) {
	        notifier.classList.add(this["class"].notifier);
	      }
	      Instance.setRefreshNeeded(false);
	      Instance.setRefreshStarted(true);
	      BalloonNotifierInstance.hideRefreshNeededNotifier();
	      NextPageLoaderInstance.startWaiter();
	      NotificationBarInstance.hideAll();
	      this.isBusyRefreshing = true;
	      var reloadUrl = main_core.Uri.removeParam(document.location.href, ['RELOAD', 'RELOAD_JSON', 'FIND']);
	      reloadUrl = main_core.Uri.addParam(reloadUrl, {
	        RELOAD: 'Y',
	        RELOAD_JSON: 'Y'
	      });
	      if (main_core.Type.isPlainObject(params) && main_core.Type.isStringFilled(params.find)) {
	        reloadUrl = main_core.Uri.addParam(reloadUrl, {
	          FIND: params.find
	        });
	      }
	      var headers = [{
	        name: 'BX-ACTION-TYPE',
	        value: 'get_dynamic'
	      }, {
	        name: 'BX-REF',
	        value: document.referrer
	      }, {
	        name: 'BX-CACHE-MODE',
	        value: 'APPCACHE'
	      }, {
	        name: 'BX-APPCACHE-PARAMS',
	        value: JSON.stringify(window.appCacheVars)
	      }, {
	        name: 'BX-APPCACHE-URL',
	        value: !main_core.Type.isUndefined(BX.frameCache) && main_core.Type.isPlainObject(BX.frameCache.vars) && main_core.Type.isStringFilled(BX.frameCache.vars.PAGE_URL) ? BX.frameCache.vars.PAGE_URL : oMSL.curUrl
	      }];
	      this.refreshXhr = mobile_ajax.Ajax.wrap({
	        type: 'json',
	        method: 'POST',
	        url: reloadUrl,
	        data: {},
	        headers: headers,
	        callback: function callback(data) {
	          _this2.refreshXhr = null;
	          _this2.setPageNumber(1);
	          Instance.setRefreshStarted(false);
	          Instance.setRefreshNeeded(false);
	          NextPageLoaderInstance.stopWaiter();
	          if (document.getElementById('lenta_notifier')) {
	            document.getElementById('lenta_notifier').classList.remove(_this2["class"].notifier);
	          }
	          app.exec('pullDownLoadingStop');
	          app.exec('hideSearchBarProgress');
	          if (main_core.Type.isPlainObject(data) && main_core.Type.isPlainObject(data.PROPS) && main_core.Type.isStringFilled(data.PROPS.CONTENT)) {
	            _this2.setRefreshFrameCacheNeeded(false);
	            BitrixMobile.LazyLoad.clearImages();
	            app.hidePopupLoader();
	            BalloonNotifierInstance.hideNotifier();
	            BalloonNotifierInstance.hideRefreshNeededNotifier();
	            if (!main_core.Type.isUndefined(data.COUNTER_TO_CLEAR)) {
	              BXMobileApp.onCustomEvent('onClearLFCounter', [data.COUNTER_TO_CLEAR], true);
	              BXMobileApp.Events.postToComponent('onClearLiveFeedCounter', {
	                counterCode: data.COUNTER_TO_CLEAR,
	                serverTime: data.COUNTER_SERVER_TIME,
	                serverTimeUnix: data.COUNTER_SERVER_TIME_UNIX
	              }, 'communication');
	            }
	            _this2.processAjaxBlock(data.PROPS, {
	              type: 'refresh',
	              callback: function callback() {
	                PinnedPanelInstance.resetFlags();
	                PinnedPanelInstance.init();
	                if (!main_core.Type.isUndefined(BX.frameCache) && document.getElementById('bxdynamic_feed_refresh') && (main_core.Type.isUndefined(data.REWRITE_FRAMECACHE) || data.REWRITE_FRAMECACHE !== 'N')) {
	                  var serverTimestamp = !main_core.Type.isUndefined(data.TS) && parseInt(data.TS) > 0 ? parseInt(data.TS) : 0;
	                  if (serverTimestamp > 0) {
	                    Instance.setOptions({
	                      frameCacheTs: serverTimestamp
	                    });
	                  }
	                  Instance.updateFrameCache({
	                    timestamp: serverTimestamp
	                  });
	                }
	                oMSL.registerBlocksToCheck();

	                //Android hack.
	                //The processing of javascript and insertion of html works not so fast as expected
	                setTimeout(function () {
	                  BitrixMobile.LazyLoad.showImages(); // when refresh
	                }, 1000);
	                BalloonNotifierInstance.initEvents();
	              }
	            });
	            if (bScroll) {
	              BitrixAnimation.animate({
	                duration: 1000,
	                start: {
	                  scroll: window.pageYOffset
	                },
	                finish: {
	                  scroll: 0
	                },
	                transition: BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
	                step: function step(state) {
	                  window.scrollTo(0, state.scroll);
	                },
	                complete: function complete() {}
	              });
	            }
	            if (window.applicationCache && data.isManifestUpdated == '1' && !oMSL.appCacheDebug && (window.applicationCache.status == window.applicationCache.IDLE || window.applicationCache.status == window.applicationCache.UPDATEREADY))
	              //the manifest has been changed
	              {
	                window.applicationCache.update();
	              }
	          } else {
	            _this2.requestError('refresh', true);
	          }
	          _this2.isBusyRefreshing = false;
	        },
	        callback_failure: function callback_failure() {
	          _this2.refreshXhr = null;
	          Instance.setRefreshStarted(false);
	          Instance.setRefreshNeeded(false);
	          NextPageLoaderInstance.stopWaiter();
	          if (document.getElementById('lenta_notifier')) {
	            document.getElementById('lenta_notifier').classList.remove(_this2["class"].notifier);
	          }
	          app.exec('pullDownLoadingStop');
	          app.exec('hideSearchBarProgress');
	          _this2.requestError('refresh', true);
	          _this2.isBusyRefreshing = false;
	        }
	      });
	    }
	  }, {
	    key: "processAjaxBlock",
	    value: function processAjaxBlock(block, params) {
	      if (!main_core.Type.isPlainObject(params) || !main_core.Type.isStringFilled(params.type) || ['refresh', 'next'].indexOf(params.type) < 0) {
	        return;
	      }
	      var htmlWasInserted = false;
	      var scriptsLoaded = false;
	      processCSS(insertHTML);
	      processExternalJS(processInlineJS);
	      function processCSS(callback) {
	        if (main_core.Type.isArray(block.CSS) && block.CSS.length > 0) {
	          BX.load(block.CSS, callback);
	        } else {
	          callback();
	        }
	      }
	      function insertHTML() {
	        if (params.type === 'refresh') {
	          document.getElementById('lenta_wrapper_global').innerHTML = block.CONTENT;
	        } else
	          // next
	          {
	            document.getElementById('lenta_wrapper').insertBefore(main_core.Dom.create('div', {
	              html: block.CONTENT
	            }), document.getElementById('next_post_more'));
	          }
	        htmlWasInserted = true;
	        if (scriptsLoaded) {
	          processInlineJS();
	        }
	      }
	      function processExternalJS(callback) {
	        if (main_core.Type.isArray(block.JS) && block.JS.length > 0) {
	          BX.load(block.JS, callback); // to initialize
	        } else {
	          callback();
	        }
	      }
	      function processInlineJS() {
	        scriptsLoaded = true;
	        if (htmlWasInserted) {
	          main_core.ajax.processRequestData(block.CONTENT, {
	            scriptsRunFirst: false,
	            dataType: 'HTML',
	            onsuccess: function onsuccess() {
	              if (main_core.Type.isFunction(params.callback)) {
	                params.callback();
	              }
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "requestError",
	    value: function requestError(type, show) {
	      var _this3 = this;
	      if (!['refresh', 'nextPage'].includes(type)) {
	        type = 'refresh';
	      }
	      show = !!show;
	      var errorBlock = document.getElementById("lenta_".concat(type.toLowerCase(), "_error"));
	      if (this.requestErrorTimeout[type]) {
	        clearTimeout(this.requestErrorTimeout[type]);
	      }
	      if (errorBlock) {
	        if (show) {
	          errorBlock.classList.add(this["class"].notifierActive);
	          if (type === 'refresh') {
	            document.addEventListener('scroll', this.refreshErrorScroll);
	          }
	        } else {
	          if (type === 'refresh') {
	            document.removeEventListener('scroll', this.refreshErrorScroll);
	          }
	          errorBlock.classList.remove(this["class"].notifierActive);
	        }
	      } else {
	        this.requestErrorTimeout[type] = setTimeout(function () {
	          _this3.requestError(type, show);
	        }, 500);
	      }
	      if (type === 'nextPage') {
	        this.initScroll(!show, true);
	      }
	    }
	  }, {
	    key: "refreshErrorScroll",
	    value: function refreshErrorScroll() {
	      this.requestError('refresh', false);
	    }
	  }, {
	    key: "nextPageErrorScroll",
	    value: function nextPageErrorScroll() {
	      this.requestError('nextPage', false);
	    }
	  }, {
	    key: "setPageNumber",
	    value: function setPageNumber(value) {
	      this.pageNumber = parseInt(value);
	    }
	  }, {
	    key: "getPageNumber",
	    value: function getPageNumber() {
	      return this.pageNumber;
	    }
	  }, {
	    key: "setNextPageUrl",
	    value: function setNextPageUrl(value) {
	      this.nextUrl = value;
	    }
	  }, {
	    key: "getNextPageUrl",
	    value: function getNextPageUrl() {
	      return this.nextUrl;
	    }
	  }, {
	    key: "setRefreshFrameCacheNeeded",
	    value: function setRefreshFrameCacheNeeded(status) {
	      Instance.setOptions({
	        refreshFrameCacheNeeded: !!status
	      });
	      var refreshNeededNode = document.getElementById('next_page_refresh_needed');
	      var nextPageCurtainNode = document.getElementById('next_post_more');
	      if (refreshNeededNode && nextPageCurtainNode) {
	        refreshNeededNode.style.display = !!status ? 'block' : 'none';
	        nextPageCurtainNode.style.display = !!status ? 'none' : 'block';
	      }
	      main_core_events.EventEmitter.emit('BX.UserContentView.onSetPreventNextPage', new main_core_events.BaseEvent({
	        compatData: [!!status]
	      }));
	    }
	  }]);
	  return Page;
	}();

	var _templateObject$3, _templateObject2$1;
	var Feed = /*#__PURE__*/function () {
	  function Feed() {
	    babelHelpers.classCallCheck(this, Feed);
	    this.pageId = null;
	    this.logId = false;
	    this.refreshNeeded = false;
	    this.refreshStarted = false;
	    this.options = {};
	    this.nodeId = {
	      feedContainer: 'lenta_wrapper'
	    };
	    this["class"] = {
	      listWrapper: 'lenta-list-wrap',
	      postWrapper: 'post-wrap',
	      pinnedPanel: 'lenta-pinned-panel',
	      pin: 'lenta-item-pin',
	      postNewContainerTransformNew: 'lenta-item-new-cont',
	      postNewContainerTransform: 'lenta-item-transform-cont',
	      postLazyLoadCheck: 'lenta-item-lazyload-check',
	      listPost: 'lenta-item',
	      detailPost: 'post-wrap',
	      postItemTopWrap: 'post-item-top-wrap',
	      postItemTop: 'post-item-top',
	      postItemPostBlock: 'post-item-post-block',
	      postItemPostContentView: 'post-item-contentview',
	      postItemDescriptionBlock: 'post-item-description',
	      postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
	      postItemInformWrap: 'post-item-inform-wrap',
	      postItemInformWrapTree: 'post-item-inform-wrap-tree',
	      postItemInformComments: 'post-item-inform-comments',
	      postItemInformMore: 'post-item-more',
	      postItemMore: 'post-more-block',
	      postItemPinnedBlock: 'post-item-pinned-block',
	      postItemPinActive: 'lenta-item-pin-active',
	      postItemGratitudeUsersSmallContainer: 'lenta-block-grat-users-small-cont',
	      postItemGratitudeUsersSmallHidden: 'lenta-block-grat-users-small-hidden',
	      postItemImportantUserList: 'post-item-important-list',
	      addPostButton: 'feed-add-post-button'
	    };
	    this.newPostContainer = null;
	    this.maxScroll = 0;
	    this.lastActivityDate = 0;
	    this.availableGroupList = {};
	    this.isPullDownEnabled = false;
	    this.isPullDownLocked = false;
	    this.isFrameDataReceived = false;
	    this.init();
	  }
	  babelHelpers.createClass(Feed, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', this.afterPostAdd.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAddError', this.afterPostAddError.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', this.afterPostUpdate.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdateError', this.afterPostUpdateError.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::showLoader', this.showLoader.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::hideLoader', this.hideLoader.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::scrollTop', this.scrollTop.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::onLogEntryDetailNotFound', this.removePost.bind(this)); // from detail page
	      BXMobileApp.addCustomEvent('Livefeed.PinnedPanel::change', this.onPinnedPanelChange.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PostDetail::pinChanged', this.onPostPinChanged.bind(this));
	      main_core_events.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));
	      main_core_events.EventEmitter.subscribe('MobileBizProc:onRenderLogMessages', this.onMobileBizProcRenderLogMessages.bind(this));
	      main_core_events.EventEmitter.subscribe('MobilePlayer:onError', this.onMobilePlayerError);
	      document.addEventListener('DOMContentLoaded', function () {
	        document.addEventListener('click', _this.handleClick.bind(_this));
	      });
	    }
	  }, {
	    key: "initListOnce",
	    value: function initListOnce(params) {
	      var _this2 = this;
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (!main_core.Type.isUndefined(params.arAvailableGroup)) {
	        this.availableGroupList = params.arAvailableGroup;
	      }
	      main_core_events.EventEmitter.subscribe('onFrameDataReceivedBefore', BitrixMobile.LazyLoad.clearImages);
	      main_core_events.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', function () {
	        _this2.recalcMaxScroll();
	      });
	      main_core_events.EventEmitter.subscribe('onFrameDataReceived', function () {
	        _this2.isPullDownEnabled = false;
	        _this2.isPullDownLocked = false;
	        _this2.isFrameDataReceived = true;
	        app.exec('pullDownLoadingStop');
	        BitrixMobile.LazyLoad.showImages(true);
	      });
	      main_core_events.EventEmitter.subscribe('onFrameDataProcessed', function (event) {
	        var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	          blocks = _event$getCompatData2[0],
	          bFromCache = _event$getCompatData2[1];
	        if (!main_core.Type.isUndefined(blocks) && !main_core.Type.isUndefined(blocks[0]) && !main_core.Type.isUndefined(bFromCache) && !!bFromCache) {
	          if (!main_core.Type.isUndefined(blocks[0].PROPS) && !main_core.Type.isUndefined(blocks[0].PROPS.TS) && parseInt(blocks[0].PROPS.TS) > 0) {
	            _this2.setOptions({
	              frameCacheTs: parseInt(blocks[0].PROPS.TS)
	            });
	          }
	        }
	        BitrixMobile.LazyLoad.showImages(true);
	        if (!!bFromCache) {
	          PageInstance.setRefreshFrameCacheNeeded(true);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('onCacheDataRequestStart', function () {
	        setTimeout(function () {
	          if (!_this2.isFrameDataReceived) {
	            _this2.isPullDownLocked = true;
	            app.exec('pullDownLoadingStart');
	          }
	        }, 1000);
	      });
	      main_core_events.EventEmitter.subscribe('onFrameDataReceivedError', function () {
	        app.BasicAuth({
	          success: function success() {
	            BX.frameCache.update(true);
	          },
	          failture: function failture() {
	            _this2.isPullDownLocked = false;
	            app.exec('pullDownLoadingStop');
	            PageInstance.requestError('refresh', true);
	          }
	        });
	      });
	      main_core_events.EventEmitter.subscribe('onFrameDataRequestFail', function (event) {
	        var _event$getCompatData3 = event.getCompatData(),
	          _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	          response = _event$getCompatData4[0];
	        if (!main_core.Type.isUndefined(response) && main_core.Type.isStringFilled(response.reason) && response.reason === 'bad_eval') {
	          _this2.isPullDownLocked = false;
	          app.exec('pullDownLoadingStop');
	          PageInstance.requestError('refresh', true);
	        } else {
	          app.BasicAuth({
	            success: function success() {
	              BX.frameCache.update(true);
	            },
	            failture: function failture() {
	              _this2.isPullDownLocked = false;
	              app.exec('pullDownLoadingStop');
	              PageInstance.requestError('refresh', true);
	            }
	          });
	        }
	      });
	      main_core_events.EventEmitter.subscribe('onCacheInvokeAfter', function (event) {
	        var _event$getCompatData5 = event.getCompatData(),
	          _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 2),
	          storageBlocks = _event$getCompatData6[0],
	          resultSet = _event$getCompatData6[1];
	        if (resultSet.items.length <= 0) {
	          BX.frameCache.update(true, true);
	        }
	      });
	      BXMobileApp.addCustomEvent('onAfterEdit', function (params) {
	        _this2.afterEdit({
	          responseData: params.postResponseData,
	          logId: params.postData.data.log_id
	        });
	      });
	      main_core_events.EventEmitter.subscribe('onPullDownDisable', function () {
	        BXMobileApp.UI.Page.Refresh.setEnabled(false);
	      });
	      main_core_events.EventEmitter.subscribe('onPullDownEnable', function () {
	        BXMobileApp.UI.Page.Refresh.setEnabled(true);
	      });
	      BXMobileApp.UI.Page.Refresh.setParams({
	        callback: function callback() {
	          if (!_this2.isPullDownLocked) {
	            PageInstance.refresh(true);
	          }
	        },
	        backgroundColor: '#E7E9EB'
	      });
	      BXMobileApp.UI.Page.Refresh.setEnabled(true);
	    }
	  }, {
	    key: "setPageId",
	    value: function setPageId(value) {
	      this.pageId = value;
	    }
	  }, {
	    key: "getPageId",
	    value: function getPageId() {
	      return this.pageId;
	    }
	  }, {
	    key: "setLogId",
	    value: function setLogId(value) {
	      this.logId = parseInt(value);
	    }
	  }, {
	    key: "getLogId",
	    value: function getLogId() {
	      return parseInt(this.logId);
	    }
	  }, {
	    key: "setOptions",
	    value: function setOptions(optionsList) {
	      for (var key in optionsList) {
	        if (!optionsList.hasOwnProperty(key)) {
	          continue;
	        }
	        this.options[key] = optionsList[key];
	      }
	    }
	  }, {
	    key: "getOption",
	    value: function getOption(key, defaultValue) {
	      if (main_core.Type.isUndefined(defaultValue)) {
	        defaultValue = null;
	      }
	      if (!main_core.Type.isStringFilled(key)) {
	        return null;
	      }
	      return !main_core.Type.isUndefined(this.options[key]) ? this.options[key] : defaultValue;
	    }
	  }, {
	    key: "setRefreshNeeded",
	    value: function setRefreshNeeded(value) {
	      this.refreshNeeded = value;
	    }
	  }, {
	    key: "getRefreshNeeded",
	    value: function getRefreshNeeded() {
	      return this.refreshNeeded;
	    }
	  }, {
	    key: "setRefreshStarted",
	    value: function setRefreshStarted(value) {
	      this.refreshStarted = value;
	    }
	  }, {
	    key: "getRefreshStarted",
	    value: function getRefreshStarted() {
	      return this.refreshStarted;
	    }
	  }, {
	    key: "setNewPostContainer",
	    value: function setNewPostContainer(value) {
	      this.newPostContainer = value;
	    }
	  }, {
	    key: "getNewPostContainer",
	    value: function getNewPostContainer() {
	      return this.newPostContainer;
	    }
	  }, {
	    key: "setMaxScroll",
	    value: function setMaxScroll(value) {
	      this.maxScroll = value;
	    }
	  }, {
	    key: "getMaxScroll",
	    value: function getMaxScroll() {
	      return this.maxScroll;
	    }
	  }, {
	    key: "afterPostAdd",
	    value: function afterPostAdd(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var postId = typeof params.postId != 'undefined' ? parseInt(params.postId) : 0;
	      var context = typeof params.context != 'undefined' ? params.context : '';
	      var pageId = typeof params.pageId != 'undefined' ? params.pageId : '';
	      var groupId = typeof params.groupId != 'undefined' ? params.groupId : null;
	      if (pageId !== this.pageId) {
	        return;
	      }
	      DatabaseUnsentPostInstance["delete"](groupId);
	      if (postId <= 0 || main_core.Type.isStringFilled(params.warningText)) {
	        return;
	      }
	      this.getEntryContent({
	        entityType: 'BLOG_POST',
	        entityId: postId,
	        queueKey: params.key,
	        action: 'add'
	      });
	    }
	  }, {
	    key: "afterPostUpdate",
	    value: function afterPostUpdate(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var context = typeof params.context != 'undefined' ? params.context : '';
	      var pageId = typeof params.pageId != 'undefined' ? params.pageId : '';
	      var postId = typeof params.postId != 'undefined' ? parseInt(params.postId) : 0;
	      var pinned = typeof params.pinned != 'undefined' && !!params.pinned;
	      this.getEntryContent({
	        entityType: 'BLOG_POST',
	        entityId: postId,
	        queueKey: params.key,
	        action: 'update',
	        pinned: pinned
	      });
	    }
	  }, {
	    key: "afterPostAddError",
	    value: function afterPostAddError(params) {
	      var _this3 = this;
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var context = main_core.Type.isStringFilled(params.context) ? params.context : '';
	      var groupId = params.groupId ? params.groupId : '';
	      var selectedDestinations = {
	        a_users: [],
	        b_groups: []
	      };
	      oMSL.buildSelectedDestinations(params.postData, selectedDestinations);
	      PostFormOldManagerInstance.setParams({
	        selectedRecipients: selectedDestinations
	      });
	      PostFormOldManagerInstance.setParams({
	        messageText: params.postData.POST_MESSAGE
	      });
	      DatabaseUnsentPostInstance.save(params.postData, groupId);
	      params.callback = function () {
	        if (BXMobileAppContext.getApiVersion() >= _this3.getApiVersion('layoutPostForm')) {
	          PostFormManagerInstance.show({
	            pageId: _this3.getPageId(),
	            postId: 0
	          });
	        } else {
	          app.exec('showPostForm', PostFormOldManagerInstance.show());
	        }
	      };
	      this.showPostError(params);
	    }
	  }, {
	    key: "afterPostUpdateError",
	    value: function afterPostUpdateError(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var context = main_core.Type.isStringFilled(params.context) ? params.context : '';
	      params.callback = function () {
	        BlogPost$$1.edit({
	          postId: parseInt(params.postId)
	        });
	      };
	      this.showPostError(params);
	    }
	  }, {
	    key: "showPostError",
	    value: function showPostError(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      params.callback = main_core.Type.isFunction(params.callback) ? params.callback : function () {};
	      var errorText = main_core.Type.isStringFilled(params.errorText) ? params.errorText : false;
	      NotificationBarInstance.showError({
	        text: errorText ? errorText : main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_ERROR'),
	        onTap: function onTap(notificationParams) {
	          params.callback(notificationParams);
	        }
	      });
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (params.pageId && this.pageId !== null && params.pageId != this.pageId) {
	        return;
	      }
	      app.showPopupLoader({
	        text: main_core.Type.isStringFilled(params.text) ? params.text : ''
	      });
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      app.hidePopupLoader();
	    }
	  }, {
	    key: "scrollTop",
	    value: function scrollTop(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      if (params.pageId && this.pageId !== null && params.pageId != this.pageId) {
	        return;
	      }
	      window.scrollTo(0, 0);
	    }
	  }, {
	    key: "getEntryContent",
	    value: function getEntryContent(params) {
	      var _this4 = this;
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }
	      var logId = params.logId ? parseInt(params.logId) : 0;
	      if (logId <= 0 && !(main_core.Type.isStringFilled(params.entityType) && parseInt(params.entityId) > 0)) {
	        return;
	      }
	      mobile_ajax.Ajax.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryContent', {
	        mode: 'class',
	        signedParameters: this.getOption('signedParameters', {}),
	        data: {
	          params: {
	            logId: parseInt(params.logId) > 0 ? parseInt(params.logId) : 0,
	            pinned: !!params.pinned ? 'Y' : 'N',
	            entityType: main_core.Type.isStringFilled(params.entityType) ? params.entityType : '',
	            entityId: parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0,
	            siteTemplateId: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID')
	          }
	        }
	      }).then(function (response) {
	        if (logId <= 0) {
	          mobile_ajax.Ajax.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryLogId', {
	            mode: 'class',
	            data: {
	              params: {
	                entityType: main_core.Type.isStringFilled(params.entityType) ? params.entityType : '',
	                entityId: parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0
	              }
	            }
	          }).then(function (responseLogId) {
	            if (responseLogId.data.logId) {
	              _this4.insertPost({
	                logId: responseLogId.data.logId,
	                content: response.data.html,
	                postId: params.postId,
	                queueKey: params.queueKey,
	                action: params.action,
	                serverTimestamp: parseInt(response.data.componentResult.serverTimestamp)
	              });
	            }
	          });
	        } else {
	          _this4.insertPost({
	            logId: logId,
	            content: response.data.html,
	            postId: params.postId,
	            queueKey: params.queueKey,
	            action: params.action,
	            serverTimestamp: parseInt(response.data.componentResult.serverTimestamp)
	          });
	        }
	      });
	    }
	  }, {
	    key: "processDetailBlock",
	    value: function processDetailBlock(postContainer, contentWrapper, selector) {
	      if (!postContainer || !contentWrapper) {
	        return Promise.reject();
	      }
	      var content = contentWrapper.querySelector(selector);
	      var container = postContainer.querySelector(selector);
	      if (container && content) {
	        return main_core.Runtime.html(container, content.innerHTML);
	      }
	      return Promise.reject();
	    }
	  }, {
	    key: "insertPost",
	    value: function insertPost(params) {
	      var _this5 = this;
	      var containerNode = document.getElementById(this.nodeId.feedContainer);
	      var content = params.content;
	      var logId = params.logId;
	      var queueKey = params.queueKey;
	      var action = params.action;
	      if (!main_core.Type.isDomNode(containerNode) || !main_core.Type.isStringFilled(content)) {
	        return;
	      }
	      var serverTimestamp = typeof params.serverTimestamp != 'undefined' && parseInt(params.serverTimestamp) > 0 ? parseInt(params.serverTimestamp) : 0;
	      if (action === 'update') {
	        var postContainer = document.getElementById('lenta_item_' + logId);
	        if (!postContainer) {
	          postContainer = document.getElementById('lenta_item');
	        }
	        if (!postContainer) {
	          return;
	        }
	        var matches = this.pageId.match(/^detail_(\d+)/i);
	        if (matches && logId != matches[1]) {
	          return;
	        }
	        var contentWrapper = postContainer.appendChild(document.createElement('div'));
	        contentWrapper.style.display = 'none';
	        main_core.Runtime.html(contentWrapper, content);
	        if (postContainer.id === 'lenta_item')
	          // empty detail
	          {
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this["class"].postItemTop));
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this["class"].postItemPostBlock)).then(function () {
	              var pageBlockNode = postContainer.querySelector(".".concat(_this5["class"].postItemPostBlock));
	              var resultBlockNode = contentWrapper.querySelector(".".concat(_this5["class"].postItemPostBlock));
	              if (pageBlockNode || resultBlockNode) {
	                var pageClassList = _this5.filterPostBlockClassList(pageBlockNode.classList);
	                var resultClassList = _this5.filterPostBlockClassList(resultBlockNode.classList);
	                pageClassList.forEach(function (className) {
	                  pageBlockNode.classList.remove(className);
	                });
	                resultClassList.forEach(function (className) {
	                  pageBlockNode.classList.add(className);
	                });
	              }
	              BitrixMobile.LazyLoad.showImages();
	            });
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this["class"].postItemAttachedFileWrap)).then(function () {
	              BitrixMobile.LazyLoad.showImages();
	            });
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this["class"].postItemInformWrap));
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this["class"].postItemInformWrapTree));
	          } else {
	          postContainer = postContainer.querySelector("div.".concat(this["class"].postItemTopWrap));
	          var contentPostItemTopWrap = contentWrapper.querySelector("div.".concat(this["class"].postItemTopWrap));
	          main_core.Runtime.html(postContainer, contentPostItemTopWrap.innerHTML).then(function () {
	            oMSL.checkNodesHeight();
	            BitrixMobile.LazyLoad.showImages();
	            if (document.getElementById('framecache-block-feed')) {
	              setTimeout(function () {
	                _this5.updateFrameCache({
	                  timestamp: serverTimestamp
	                });
	              }, 750);
	            }
	          });
	        }
	        contentWrapper.remove();
	      } else if (action === 'add') {
	        this.setNewPostContainer(main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"", " ", "\" ontransitionend=\"", "\"></div>"])), this["class"].postNewContainerTransformNew, this["class"].postLazyLoadCheck, this.handleInsertPostTransitionEnd.bind(this)));
	        main_core.Dom.prepend(this.getNewPostContainer(), containerNode);
	        mobile_utils.Utils.htmlWithInlineJS(this.getNewPostContainer(), content).then(function () {
	          var postNode = _this5.getNewPostContainer().querySelector("div.".concat(_this5["class"].listPost));
	          main_core.Dom.style(_this5.getNewPostContainer(), 'height', "".concat(postNode.scrollHeight + 12 /*margin-bottom*/, "px"));
	          if (serverTimestamp > 0) {
	            _this5.setOptions({
	              frameCacheTs: serverTimestamp
	            });
	          }
	          oMSL.registerBlocksToCheck();
	          setTimeout(function () {
	            oMSL.checkNodesHeight();
	          }, 100);
	          setTimeout(function () {
	            _this5.updateFrameCache({
	              timestamp: serverTimestamp
	            });
	          }, 750);
	        });
	      }
	      PublicationQueueInstance.emit('onPostInserted', new main_core_events.BaseEvent({
	        data: {
	          key: queueKey
	        }
	      }));
	    }
	  }, {
	    key: "removePost",
	    value: function removePost(params) {
	      var logId = parseInt(params.logId);
	      if (logId <= 0) {
	        return;
	      }
	      var itemNode = document.getElementById('lenta_item_' + logId);
	      if (!itemNode) {
	        return;
	      }
	      itemNode.remove();
	    }
	  }, {
	    key: "filterPostBlockClassList",
	    value: function filterPostBlockClassList(classList) {
	      var result = [];
	      Array.from(classList).forEach(function (className) {
	        if (className === 'info-block-background' || className === 'info-block-background-with-title' || className === 'info-block-gratitude' || className === 'info-block-important' || className === 'ui-livefeed-background' || className.match(/info-block-gratitude-(.+)/i) || className.match(/ui-livefeed-background-(.+)/i)) {
	          result.push(className);
	        }
	      });
	      return result;
	    }
	  }, {
	    key: "handleInsertPostTransitionEnd",
	    value: function handleInsertPostTransitionEnd(event) {
	      if (event.propertyName === 'height') {
	        this.getNewPostContainer().classList.remove(this["class"].postNewContainerTransformNew);
	        this.getNewPostContainer().classList.remove(this["class"].postNewContainerTransform);
	        main_core.Dom.style(this.getNewPostContainer(), 'height', null);
	        this.recalcMaxScroll();
	        BitrixMobile.LazyLoad.showImages();
	      }
	    }
	  }, {
	    key: "onLazyLoadImageLoaded",
	    value: function onLazyLoadImageLoaded(event) {
	      var _this6 = this;
	      this.recalcMaxScroll();
	      var _event$getData = event.getData(),
	        _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	        imageNode = _event$getData2[0];
	      if (imageNode) {
	        var postCheckNode = imageNode.closest(".".concat(this["class"].postLazyLoadCheck));
	        if (postCheckNode) {
	          var postNode = postCheckNode.querySelector("div.".concat(this["class"].listPost));
	          if (postNode) {
	            postCheckNode.classList.add(this["class"].postNewContainerTransform);
	            main_core.Dom.style(postCheckNode, 'height', "".concat(postNode.scrollHeight, "px"));
	            setTimeout(function () {
	              postCheckNode.classList.remove(_this6["class"].postNewContainerTransform);
	              main_core.Dom.style(postCheckNode, 'height', null);
	            }, 500);
	          }
	        }
	      }
	    }
	  }, {
	    key: "recalcMaxScroll",
	    value: function recalcMaxScroll() {
	      this.setMaxScroll(document.documentElement.scrollHeight - 2 * window.innerHeight);
	    }
	  }, {
	    key: "onMobileBizProcRenderLogMessages",
	    value: function onMobileBizProcRenderLogMessages() {
	      this.recalcMaxScroll();
	    }
	  }, {
	    key: "onPinnedPanelChange",
	    value: function onPinnedPanelChange(params) {
	      var logId = params.logId ? parseInt(params.logId) : 0;
	      var value = ['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null;
	      if (!logId || !value || !PinnedPanelInstance.getPinnedPanelNode()) {
	        return;
	      }
	      var postNode = main_core.Type.isDomNode(params.postNode) ? params.postNode : null;
	      if (!main_core.Type.isDomNode(params.postNode))
	        // from detail in list
	        {
	          postNode = document.getElementById("lenta_item_".concat(logId));
	        }
	      if (!main_core.Type.isDomNode(postNode)) {
	        return;
	      }
	      if (value === 'N') {
	        PinnedPanelInstance.extractEntry({
	          logId: logId,
	          postNode: postNode,
	          containerNode: document.getElementById(this.nodeId.feedContainer)
	        });
	      } else if (value === 'Y' && main_core.Type.isDomNode(params.postNode)) {
	        app.showPopupLoader({
	          text: ""
	        });
	        if (this.getOption('refreshFrameCacheNeeded', false) === true) {
	          return;
	        }
	        var entityValue = postNode.getAttribute('data-security-entity-pin');
	        var tokenValue = postNode.getAttribute('data-security-token-pin');
	        PinnedPanelInstance.getPinnedData({
	          logId: logId,
	          entityValue: entityValue,
	          tokenValue: tokenValue
	        }).then(function (pinnedData) {
	          app.hidePopupLoader();
	          PinnedPanelInstance.insertEntry({
	            logId: logId,
	            postNode: params.postNode,
	            pinnedContent: pinnedData
	          });
	        }, function (response) {
	          app.hidePopupLoader();
	        });
	      }
	    }
	  }, {
	    key: "onPostPinChanged",
	    value: function onPostPinChanged(params) {
	      var logId = params.logId ? parseInt(params.logId) : 0;
	      var value = ['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null;
	      if (!logId || !value) {
	        return;
	      }
	      var menuNode = document.getElementById('log-entry-menu-' + logId);
	      if (!menuNode) {
	        return;
	      }
	      var postNode = menuNode.closest(".".concat(this["class"].detailPost));
	      if (!postNode) {
	        postNode = menuNode.closest(".".concat(this["class"].listPost));
	      }
	      if (!postNode) {
	        return;
	      }
	      if (value === 'Y') {
	        postNode.classList.add(this["class"].postItemPinActive);
	      } else {
	        postNode.classList.remove(this["class"].postItemPinActive);
	      }
	    }
	  }, {
	    key: "handleClick",
	    value: function handleClick(e) {
	      if (e.target.classList.contains(this["class"].pin)) {
	        var post = null;
	        var menuNode = null;
	        var context = 'list';
	        var postNode = e.target.closest(".".concat(this["class"].listPost));
	        if (postNode)
	          // list
	          {
	            menuNode = postNode.querySelector('[data-menu-type="post"]');
	            post = this.getPostFromNode(postNode);
	          } else
	          // detail
	          {
	            context = 'detail';
	            postNode = e.target.closest(".".concat(this["class"].detailPost));
	            if (postNode) {
	              menuNode = postNode.querySelector('[data-menu-type="post"]');
	              if (menuNode) {
	                post = this.getPostFromLogId(menuNode.getAttribute('data-log-id'));
	              }
	            }
	          }
	        if (post && menuNode) {
	          return post.setPinned({
	            menuNode: menuNode,
	            context: context
	          });
	        }
	        e.stopPropagation();
	        return e.preventDefault();
	      } else if (e.target.classList.contains(this["class"].addPostButton)) {
	        if (BXMobileAppContext.getApiVersion() >= this.getApiVersion('layoutPostForm')) {
	          var formManager = new PostFormManager();
	          formManager.show({
	            pageId: this.getPageId(),
	            groupId: this.getOption('groupId', 0)
	          });
	        } else {
	          app.exec('showPostForm', PostFormOldManagerInstance.show());
	        }
	      } else if (e.target.classList.contains(".".concat(PageScrollInstance["class"].scrollButton)) || e.target.closest(".".concat(PageScrollInstance["class"].scrollButton))) {
	        if (e.target.classList.contains(".".concat(PageScrollInstance["class"].scrollButtonTop)) || e.target.closest(".".concat(PageScrollInstance["class"].scrollButtonTop))) {
	          PageScrollInstance.scrollTo('top');
	        } else if (e.target.classList.contains(".".concat(PageScrollInstance["class"].scrollButtonBottom)) || e.target.closest(".".concat(PageScrollInstance["class"].scrollButtonBottom))) {
	          PageScrollInstance.scrollTo('bottom');
	        }
	      } else if ((e.target.closest(".".concat(this["class"].listWrapper)) || e.target.closest(".".concat(this["class"].pinnedPanel))) && !(e.target.tagName.toLowerCase() === 'a' && main_core.Type.isStringFilled(e.target.getAttribute('target')) && e.target.getAttribute('target').toLowerCase() === '_blank')) {
	        var detailFromPinned = !!(e.target.classList.contains(this["class"].postItemPinnedBlock) || e.target.closest(".".concat(this["class"].postItemPinnedBlock)));
	        var detailFromNormal = !!(!detailFromPinned && (e.target.classList.contains(this["class"].postItemPostContentView) || e.target.closest(".".concat(this["class"].postItemPostContentView)) || e.target.classList.contains(this["class"].postItemDescriptionBlock) // tasks
	        || e.target.closest(".".concat(this["class"].postItemDescriptionBlock))));
	        var detailToComments = !!(!detailFromPinned && !detailFromNormal && (e.target.classList.contains(this["class"].postItemInformComments) || e.target.closest(".".concat(this["class"].postItemInformComments))));
	        var detailToExpanded = !!(!detailFromPinned && !detailFromNormal && !detailToComments && (e.target.classList.contains(this["class"].postItemInformMore) || e.target.closest(".".concat(this["class"].postItemInformMore))));
	        if (detailFromPinned || detailFromNormal || detailToComments || detailToExpanded) {
	          var _postNode = e.target.closest(".".concat(this["class"].listPost));
	          if (_postNode) {
	            var _post = this.getPostFromNode(_postNode);
	            if (_post) {
	              _post.openDetail({
	                pathToEmptyPage: this.getOption('pathToEmptyPage', ''),
	                pathToCalendarEvent: this.getOption('pathToCalendarEvent', ''),
	                pathToTasksRouter: this.getOption('pathToTasksRouter', ''),
	                event: e,
	                focusComments: detailToComments,
	                showFull: detailToExpanded
	              });
	            }
	          }
	          e.stopPropagation();
	          return e.preventDefault();
	        }
	      } else if (e.target.closest(".".concat(this["class"].postWrapper))) {
	        var expand = !!(e.target.classList.contains(this["class"].postItemInformMore) || e.target.closest(".".concat(this["class"].postItemInformMore)) || e.target.classList.contains(this["class"].postItemMore) || e.target.closest(".".concat(this["class"].postItemMore)));
	        var postItemGratitudeUsersSmallContainer = null;
	        if (e.target.classList.contains(this["class"].postItemGratitudeUsersSmallContainer)) {
	          postItemGratitudeUsersSmallContainer = e.target;
	        } else {
	          postItemGratitudeUsersSmallContainer = e.target.closest(".".concat(this["class"].postItemGratitudeUsersSmallContainer));
	        }
	        if (expand || main_core.Type.isDomNode(postItemGratitudeUsersSmallContainer)) {
	          if (main_core.Type.isDomNode(postItemGratitudeUsersSmallContainer)) {
	            postItemGratitudeUsersSmallContainer.style.display = 'none';
	            var postItemGratitudeUsersSmallHidden = postItemGratitudeUsersSmallContainer.parentNode.querySelector(".".concat(this["class"].postItemGratitudeUsersSmallHidden));
	            if (postItemGratitudeUsersSmallHidden) {
	              postItemGratitudeUsersSmallHidden.style.display = 'block';
	            }
	          }
	          var logId = this.getOption('logId', 0);
	          var _post2 = new Post$$1({
	            logId: logId
	          });
	          _post2.expandText();
	          e.stopPropagation();
	          return e.preventDefault();
	        }
	        var importantUserListNode = null;
	        if (e.target.classList.contains(this["class"].postItemImportantUserList)) {
	          importantUserListNode = e.target;
	        } else {
	          importantUserListNode = e.target.closest(".".concat(this["class"].postItemImportantUserList));
	        }
	        if (importantUserListNode) {
	          var inputNode = importantUserListNode.parentNode.querySelector('input');
	          var postId = 0;
	          if (main_core.Type.isDomNode(inputNode)) {
	            postId = parseInt(inputNode.getAttribute('bx-data-post-id'));
	          }
	          if (postId > 0) {
	            app.exec("openComponent", {
	              name: "JSStackComponent",
	              componentCode: "livefeed.important.list",
	              scriptPath: "/mobileapp/jn/livefeed.important.list/?version=1.0.0",
	              params: {
	                POST_ID: postId,
	                SETTINGS: this.getOption('importantData', {})
	              },
	              rootWidget: {
	                name: 'list',
	                settings: {
	                  objectName: "livefeedImportantListWidget",
	                  title: BX.message('MOBILE_EXT_LIVEFEED_USERS_LIST_TITLE'),
	                  modal: false,
	                  backdrop: {
	                    mediumPositionPercent: 75
	                  }
	                }
	              }
	            }, false);
	          }
	        }
	      }
	    }
	  }, {
	    key: "getPostFromNode",
	    value: function getPostFromNode(node) {
	      if (!main_core.Type.isDomNode(node)) {
	        return;
	      }
	      var logId = parseInt(node.getAttribute('data-livefeed-id'));
	      if (logId <= 0) {
	        return;
	      }
	      return new Post$$1({
	        logId: logId,
	        entryType: node.getAttribute('data-livefeed-post-entry-type'),
	        useFollow: node.getAttribute('data-livefeed-post-use-follow') === 'Y',
	        useTasks: node.getAttribute('data-livefeed-post-use-tasks') === 'Y',
	        perm: node.getAttribute('data-livefeed-post-perm'),
	        destinations: node.getAttribute('data-livefeed-post-destinations'),
	        postId: parseInt(node.getAttribute('data-livefeed-post-id')),
	        url: node.getAttribute('data-livefeed-post-url'),
	        entityXmlId: node.getAttribute('data-livefeed-post-entity-xml-id'),
	        readOnly: node.getAttribute('data-livefeed-post-read-only') === 'Y',
	        contentTypeId: node.getAttribute('data-livefeed-post-content-type-id'),
	        contentId: node.getAttribute('data-livefeed-post-content-id'),
	        showFull: node.getAttribute('data-livefeed-post-show-full') === 'Y',
	        taskId: parseInt(node.getAttribute('data-livefeed-task-id')),
	        taskData: node.getAttribute('data-livefeed-task-data'),
	        calendarEventId: parseInt(node.getAttribute('data-livefeed-calendar-event-id'))
	      });
	    }
	  }, {
	    key: "getPostFromLogId",
	    value: function getPostFromLogId(logId) {
	      var result = null;
	      logId = parseInt(logId);
	      if (logId <= 0) {
	        return result;
	      }
	      result = new Post$$1({
	        logId: logId
	      });
	      return result;
	    }
	  }, {
	    key: "updateFrameCache",
	    value: function updateFrameCache(params) {
	      var contentNode = document.getElementById('framecache-block-feed');
	      if (!main_core.Type.isDomNode(contentNode)) {
	        contentNode = document.getElementById('bxdynamic_feed_refresh');
	      }
	      if (!main_core.Type.isDomNode(contentNode)) {
	        return;
	      }
	      var props = {
	        USE_BROWSER_STORAGE: true,
	        AUTO_UPDATE: true,
	        USE_ANIMATION: false
	      };
	      var timestamp = typeof params.timestamp != 'undefined' ? parseInt(params.timestamp) : 0;
	      if (timestamp > 0) {
	        props.TS = timestamp;
	      }
	      BX.frameCache.writeCacheWithID('framecache-block-feed', contentNode.innerHTML, parseInt(Math.random() * 100000), JSON.stringify(props));
	    }
	  }, {
	    key: "onMobilePlayerError",
	    value: function onMobilePlayerError(event) {
	      var _event$getData3 = event.getData(),
	        _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 2),
	        player = _event$getData4[0],
	        src = _event$getData4[1];
	      if (!main_core.Type.isDomNode(player)) {
	        return;
	      }
	      if (!main_core.Type.isStringFilled(src)) {
	        return;
	      }
	      var container = player.parentNode;
	      if (container) {
	        if (container.querySelector('.disk-mobile-player-error-container')) {
	          return;
	        }
	      } else {
	        if (player.querySelector('.disk-mobile-player-error-container')) {
	          return;
	        }
	      }
	      var sources = player.getElementsByTagName('source');
	      var sourcesLeft = sources.length;
	      Array.from(sources).forEach(function (source) {
	        if (main_core.Type.isStringFilled(source.src) && source.src === src) {
	          main_core.Dom.remove(source);
	          sourcesLeft--;
	        }
	      });
	      if (sourcesLeft > 0) {
	        return;
	      }
	      var errorContainer = main_core.Dom.create('div', {
	        props: {
	          className: 'disk-mobile-player-error-container'
	        },
	        children: [main_core.Dom.create('div', {
	          props: {
	            className: 'disk-mobile-player-error-icon'
	          },
	          html: ''
	        }), main_core.Dom.create('div', {
	          props: {
	            className: 'disk-mobile-player-error-message'
	          },
	          html: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PLAYER_ERROR_MESSAGE')
	        })]
	      });
	      var downloadLink = errorContainer.querySelector('.disk-mobile-player-download');
	      if (downloadLink) {
	        main_core.Dom.adjust(downloadLink, {
	          events: {
	            click: function click() {
	              app.openDocument({
	                url: src
	              });
	            }
	          }
	        });
	      }
	      if (container) {
	        player.style.display = 'none';
	        container.appendChild(errorContainer);
	      } else {
	        main_core.Dom.adjust(player, {
	          children: [errorContainer]
	        });
	      }
	    }
	  }, {
	    key: "getApiVersion",
	    value: function getApiVersion(feature) {
	      var result = 0;
	      switch (feature) {
	        case 'layoutPostForm':
	          result = 37;
	          break;
	        case 'pageMenu':
	          result = 34;
	          break;
	        case 'tabs':
	          result = 41;
	          break;
	        default:
	      }
	      return result;
	    }
	  }, {
	    key: "sendErrorEval",
	    value: function sendErrorEval(script) {
	      BX.evalGlobal('try { ' + script + ' } catch (e) { this.sendError(e.message, e.name, e.number); }');
	    }
	  }, {
	    key: "sendError",
	    value: function sendError(message, url, linenumber) {
	      mobile_ajax.Ajax.runAction('socialnetwork.api.livefeed.mobileLogError', {
	        data: {
	          message: message,
	          url: url,
	          lineNumber: linenumber
	        }
	      }).then(function (response) {}, function (response) {});
	    }
	  }, {
	    key: "setLastActivityDate",
	    value: function setLastActivityDate() {
	      this.lastActivityDate = Math.round(new Date().getTime() / 1000);
	    }
	  }, {
	    key: "getLastActivityDate",
	    value: function getLastActivityDate() {
	      return this.lastActivityDate;
	    }
	  }, {
	    key: "afterEdit",
	    value: function afterEdit(_ref) {
	      var responseData = _ref.responseData,
	        logId = _ref.logId;
	      logId = !main_core.Type.isUndefined(logId) ? parseInt(logId) : 0;
	      var newPostNode = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), responseData.text);
	      var container = document.getElementById('blog-post-first-after');
	      if (container) {
	        container.parentNode.insertBefore(newPostNode, container.nextSibling);
	      }
	      var detailTextNode = newPostNode.querySelector(".post-item-post-block");
	      var topNode = newPostNode.querySelector(".post-item-top");
	      var filesNode = newPostNode.querySelector(".post-item-attached-file-wrap");
	      if (logId > 0 && detailTextNode && topNode) {
	        var postData = {
	          detailText: detailTextNode.innerHTML,
	          topText: topNode.innerHTML,
	          logID: logId
	        };
	        if (filesNode) {
	          postData.filesBlockText = filesNode.innerHTML;
	        }
	        BXMobileApp.onCustomEvent('onEditedPostInserted', postData, true, true);
	      }
	      BitrixMobile.LazyLoad.showImages();
	    }
	  }]);
	  return Feed;
	}();
	var Instance = new Feed();
	var BalloonNotifierInstance = new BalloonNotifier();
	var NextPageLoaderInstance = new NextPageLoader();
	var NotificationBarInstance = new NotificationBar();
	var DatabaseUnsentPostInstance = new Database();
	var PublicationQueueInstance = new PublicationQueue();
	var PostMenuInstance = new PostMenu();
	var PageMenuInstance = new PageMenu();
	var PostFormManagerInstance = new PostFormManager();
	var PostFormOldManagerInstance = new PostFormOldManager();
	var PinnedPanelInstance = new PinnedPanel();
	var RatingInstance = new Rating();
	var ImportantManagerInstance = new ImportantManager();
	var SearchBarInstance = new SearchBar();
	var PageScrollInstance = new PageScroll();
	var FollowManagerInstance = new FollowManager();
	var CommentsInstance = new Comments();
	var PageInstance = new Page();

	exports.Post = Post$$1;
	exports.BlogPost = BlogPost$$1;
	exports.Instance = Instance;
	exports.BalloonNotifierInstance = BalloonNotifierInstance;
	exports.NextPageLoaderInstance = NextPageLoaderInstance;
	exports.NotificationBarInstance = NotificationBarInstance;
	exports.DatabaseUnsentPostInstance = DatabaseUnsentPostInstance;
	exports.PublicationQueueInstance = PublicationQueueInstance;
	exports.PostMenuInstance = PostMenuInstance;
	exports.PageMenuInstance = PageMenuInstance;
	exports.PostFormManagerInstance = PostFormManagerInstance;
	exports.PostFormOldManagerInstance = PostFormOldManagerInstance;
	exports.PinnedPanelInstance = PinnedPanelInstance;
	exports.RatingInstance = RatingInstance;
	exports.ImportantManagerInstance = ImportantManagerInstance;
	exports.SearchBarInstance = SearchBarInstance;
	exports.PageScrollInstance = PageScrollInstance;
	exports.FollowManagerInstance = FollowManagerInstance;
	exports.CommentsInstance = CommentsInstance;
	exports.PageInstance = PageInstance;

}((this.BX.MobileLivefeed = this.BX.MobileLivefeed || {}),BX.UI.Analytics,BX,BX.Event,BX,BX.Mobile));
//# sourceMappingURL=livefeed.bundle.js.map
