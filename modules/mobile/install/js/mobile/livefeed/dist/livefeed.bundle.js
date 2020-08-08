this.BX = this.BX || {};
(function (exports,main_core,main_core_events,mobile_imageviewer,mobile_utils) {
	'use strict';

	var BalloonNotifier = /*#__PURE__*/function () {
	  function BalloonNotifier() {
	    babelHelpers.classCallCheck(this, BalloonNotifier);
	    this.classes = {
	      show: 'lenta-notifier-shown'
	    };
	    this.nodeIdList = {
	      notifier: 'lenta_notifier',
	      notifierCounter: 'lenta_notifier_cnt',
	      notifierCounterTitle: 'lenta_notifier_cnt_title',
	      refreshNeeded: 'lenta_notifier_2'
	    };
	  }

	  babelHelpers.createClass(BalloonNotifier, [{
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
	    this.setTableName(main_core.Type.isPlainObject(params) && main_core.Type.isStringFilled(params.tableName) ? params.tableName : 'b_default');
	    this.setKeyName(main_core.Type.isPlainObject(params) && main_core.Type.isStringFilled(params.keyName) ? params.keyName : 'post_unsent');
	  }

	  babelHelpers.createClass(Database, [{
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
	    key: "check",
	    value: function check(callback) {
	      if (!main_core.Type.isObject(app.db)) {
	        return false;
	      }

	      app.db.createTable({
	        tableName: this.getTableName(),
	        fields: [{
	          name: 'KEY',
	          unique: true
	        }, 'VALUE'],
	        success: function success(res) {
	          callback.success();
	        },
	        fail: function fail(e) {
	          callback.fail();
	        }
	      });
	    }
	  }, {
	    key: "delete",
	    value: function _delete(groupId) {
	      var _this = this;

	      if (parseInt(groupId) <= 0) {
	        groupId = false;
	      }

	      if (!main_core.Type.isObject(app.db)) {
	        return false;
	      }

	      this.check({
	        success: function success() {
	          app.db.deleteRows({
	            tableName: _this.getTableName(),
	            filter: {
	              KEY: _this.getKeyName() + (groupId ? '_' + groupId : '')
	            },
	            success: function success(res) {},
	            fail: function fail(e) {}
	          });
	        },
	        fail: function fail() {}
	      });
	    }
	  }, {
	    key: "save",
	    value: function save(data, groupId) {
	      var _this2 = this;

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

	      if (!main_core.Type.isObject(app.db)) {
	        return false;
	      }

	      this.check({
	        success: function success() {
	          app.db.getRows({
	            tableName: _this2.getTableName(),
	            filter: {
	              KEY: _this2.getKeyName() + (groupId ? '_' + groupId : '')
	            },
	            success: function success(res) {
	              var text = JSON.stringify(data);

	              if (res.items.length > 0) {
	                app.db.updateRows({
	                  tableName: _this2.getTableName(),
	                  updateFields: {
	                    VALUE: text
	                  },
	                  filter: {
	                    KEY: _this2.getKeyName() + (groupId ? '_' + groupId : '')
	                  },
	                  success: function success(res) {},
	                  fail: function fail(e) {}
	                });
	              } else {
	                app.db.addRow({
	                  tableName: _this2.getTableName(),
	                  insertFields: {
	                    KEY: _this2.getKeyName() + (groupId ? '_' + groupId : ''),
	                    VALUE: text
	                  },
	                  success: function success(res) {},
	                  fail: function fail(e) {}
	                });
	              }
	            },
	            fail: function fail(e) {}
	          });
	        },
	        fail: function fail() {}
	      });
	    }
	  }, {
	    key: "load",
	    value: function load(callback, groupId) {
	      var _this3 = this;

	      if (parseInt(groupId) <= 0) {
	        groupId = false;
	      }

	      if (!main_core.Type.isObject(app.db)) {
	        callback.onEmpty();
	        return null;
	      }

	      this.check({
	        success: function success() {
	          app.db.getRows({
	            tableName: _this3.getTableName(),
	            filter: {
	              KEY: _this3.getKeyName() + (groupId ? '_' + groupId : '')
	            },
	            success: function success(res) {
	              if (res.items.length > 0 && res.items[0].VALUE.length > 0) {
	                var result = JSON.parse(res.items[0].VALUE);

	                if (main_core.Type.isPlainObject(result)) {
	                  callback.onLoad(result);
	                } else {
	                  callback.onEmpty();
	                }
	              } else {
	                callback.onEmpty();
	              }
	            },
	            fail: function fail(e) {
	              callback.onEmpty();
	            }
	          });
	        },
	        fail: function fail() {
	          callback.onEmpty();
	        }
	      });
	    }
	  }]);
	  return Database;
	}();

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"post-balloon-hidden post-balloon post-balloon-active\"><span class=\"post-balloon-icon\"></span><span class=\"post-balloon-text\">", "</span></div>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var PublicationQueue = /*#__PURE__*/function (_Event$EventEmitter) {
	  babelHelpers.inherits(PublicationQueue, _Event$EventEmitter);

	  function PublicationQueue() {
	    var _this;

	    babelHelpers.classCallCheck(this, PublicationQueue);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PublicationQueue).call(this));
	    _this.repo = {};
	    _this.nodeId = {
	      container: 'post-balloon-container'
	    };
	    _this.class = {
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
	      main_core_events.Event.bind(document, 'scroll', this.onScroll.bind(this));
	    }
	  }, {
	    key: "onScroll",
	    value: function onScroll() {
	      var containerNode = document.getElementById(this.nodeId.container);

	      if (!main_core.Type.isDomNode(containerNode)) {
	        return;
	      }

	      if (window.pageYOffset > 0) {
	        containerNode.classList.add(this.class.balloonFixed);
	      } else {
	        containerNode.classList.remove(this.class.balloonFixed);
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
	          queue = main_core.Type.isPlainObject(queue) ? queue : JSON.parse(queue);

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
	      this.repo[key].node.classList.add(this.class.balloonShow);
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
	    value: function addSuccess(key) {
	      var _this4 = this;

	      if (this.repo[key] && this.repo[key].node) {
	        this.repo[key].node.classList.remove(this.class.balloonHidden);
	        this.repo[key].node.classList.remove(this.class.balloonShow);
	        this.repo[key].node.classList.add(this.class.balloonPublished);
	        this.repo[key].node.lastElementChild.innerHTML = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_SUCCESS_TITLE');
	      }

	      setTimeout(function () {
	        _this4.removeFromTray(key);
	      }, 5000);
	    }
	  }, {
	    key: "drawItem",
	    value: function drawItem() {
	      var title = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_PUBLICATION_QUEUE_ITEM_TITLE');
	      return main_core.Tag.render(_templateObject(), title);
	    }
	  }, {
	    key: "hideItem",
	    value: function hideItem(key, params) {
	      if (this.repo[key]) {
	        this.repo[key].node.classList.add(this.class.balloonHide);
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

	      var key = params.key ? params.key : '';
	      this.addSuccess(key);
	      this.drawList();
	    }
	  }, {
	    key: "afterPostUpdate",
	    value: function afterPostUpdate(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      var key = params.key ? params.key : '';
	      this.addSuccess(key);
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
	}(main_core_events.Event.EventEmitter);

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"", " ", "\" ontransitionend=\"", "\"></div>"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Feed = /*#__PURE__*/function () {
	  function Feed() {
	    babelHelpers.classCallCheck(this, Feed);
	    this.pageId = null;
	    this.refreshNeeded = false;
	    this.refreshStarted = false;
	    this.options = {};
	    this.nodeId = {
	      feedContainer: 'lenta_wrapper'
	    };
	    this.class = {
	      postNewContainerTransformNew: 'lenta-item-new-cont',
	      postNewContainerTransform: 'lenta-item-transform-cont',
	      postLazyLoadCheck: 'lenta-item-lazyload-check',
	      post: 'lenta-item',
	      postItemTopWrap: 'post-item-top-wrap',
	      postItemTop: 'post-item-top',
	      postItemPostBlock: 'post-item-post-block',
	      postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
	      postItemInformWrap: 'post-item-inform-wrap',
	      postItemInformWrapTree: 'post-item-inform-wrap-tree'
	    };
	    this.newPostContainer = null;
	    this.maxScroll = 0;
	    this.init();
	  }

	  babelHelpers.createClass(Feed, [{
	    key: "init",
	    value: function init() {
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAdd', this.afterPostAdd.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostAddError', this.afterPostAddError.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdate', this.afterPostUpdate.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PublicationQueue::afterPostUpdateError', this.afterPostUpdateError.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::showLoader', this.showLoader.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::hideLoader', this.hideLoader.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed::scrollTop', this.scrollTop.bind(this));
	      main_core_events.Event.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));
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

	      var postId = typeof params.postId != 'undefined' ? parseInt(params.postId) : 0,
	          context = typeof params.context != 'undefined' ? params.context : '',
	          pageId = typeof params.pageId != 'undefined' ? params.pageId : '',
	          groupId = typeof params.groupId != 'undefined' ? params.groupId : null;

	      if (pageId !== this.pageId) {
	        return;
	      }

	      DatabaseUnsentPostInstance.delete(groupId);

	      if (postId <= 0) {
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

	      var context = typeof params.context != 'undefined' ? params.context : '',
	          pageId = typeof params.pageId != 'undefined' ? params.pageId : '',
	          postId = typeof params.postId != 'undefined' ? parseInt(params.postId) : 0;
	      this.getEntryContent({
	        entityType: 'BLOG_POST',
	        entityId: postId,
	        queueKey: params.key,
	        action: 'update'
	      });
	    }
	  }, {
	    key: "afterPostAddError",
	    value: function afterPostAddError(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      var context = main_core.Type.isStringFilled(params.context) ? params.context : '',
	          groupId = params.groupId ? params.groupId : '';
	      var selectedDestinations = {
	        a_users: [],
	        b_groups: []
	      };
	      oMSL.buildSelectedDestinations(params.postData, selectedDestinations);
	      oMSL.setPostFormParams({
	        selectedRecipients: selectedDestinations
	      });
	      oMSL.setPostFormParams({
	        messageText: params.postData.POST_MESSAGE
	      });
	      DatabaseUnsentPostInstance.save(params.postData, groupId);

	      params.callback = function () {
	        app.exec('showPostForm', oMSL.showNewPostForm());
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
	        oMSL.editBlogPost({
	          post_id: parseInt(params.postId)
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
	      var _this = this;

	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      var logId = params.logId ? parseInt(params.logId) : 0,
	          BMAjaxWrapper = new MobileAjaxWrapper();

	      if (logId <= 0 && !(main_core.Type.isStringFilled(params.entityType) && parseInt(params.entityId) > 0)) {
	        return;
	      }

	      BMAjaxWrapper.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryContent', {
	        mode: 'class',
	        signedParameters: this.getOption('signedParameters', {}),
	        data: {
	          params: {
	            logId: parseInt(params.logId) > 0 ? parseInt(params.logId) : 0,
	            entityType: main_core.Type.isStringFilled(params.entityType) ? params.entityType : '',
	            entityId: parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0,
	            siteTemplateId: BX.message('MOBILE_EXT_LIVEFEED_SITE_TEMPLATE_ID')
	          }
	        }
	      }).then(function (response) {
	        if (logId <= 0) {
	          BMAjaxWrapper.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryLogId', {
	            mode: 'class',
	            data: {
	              params: {
	                entityType: main_core.Type.isStringFilled(params.entityType) ? params.entityType : '',
	                entityId: parseInt(params.entityId) > 0 ? parseInt(params.entityId) : 0
	              }
	            }
	          }).then(function (responseLogId) {
	            if (responseLogId.data.logId) {
	              _this.insertPost({
	                logId: responseLogId.data.logId,
	                content: response.data.html,
	                postId: params.postId,
	                queueKey: params.queueKey,
	                action: params.action
	              });
	            }
	          });
	        } else {
	          _this.insertPost({
	            logId: logId,
	            content: response.data.html,
	            postId: params.postId,
	            queueKey: params.queueKey,
	            action: params.action
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

	      var content = contentWrapper.querySelector(selector),
	          container = postContainer.querySelector(selector);

	      if (container && content) {
	        return main_core.Runtime.html(container, content.innerHTML);
	      }

	      return Promise.reject();
	    }
	  }, {
	    key: "insertPost",
	    value: function insertPost(params) {
	      var _this2 = this;

	      var containerNode = document.getElementById(this.nodeId.feedContainer),
	          content = params.content,
	          logId = params.logId,
	          queueKey = params.queueKey,
	          action = params.action;

	      if (!main_core.Type.isDomNode(containerNode) || !main_core.Type.isStringFilled(content)) {
	        return;
	      }

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

	        if (postContainer.id === 'lenta_item') // empty detail
	          {
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this.class.postItemTop));
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this.class.postItemPostBlock)).then(function () {
	              BitrixMobile.LazyLoad.showImages();
	            });
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this.class.postItemAttachedFileWrap)).then(function () {
	              BitrixMobile.LazyLoad.showImages();
	            });
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this.class.postItemInformWrap));
	            this.processDetailBlock(postContainer, contentWrapper, ".".concat(this.class.postItemInformWrapTree));
	          } else {
	          postContainer = postContainer.querySelector("div.".concat(this.class.postItemTopWrap));
	          var contentPostItemTopWrap = contentWrapper.querySelector("div.".concat(this.class.postItemTopWrap));
	          main_core.Runtime.html(postContainer, contentPostItemTopWrap.innerHTML).then(function () {
	            BitrixMobile.LazyLoad.showImages();
	          });
	        }

	        contentWrapper.remove();
	      } else if (action === 'add') {
	        this.setNewPostContainer(main_core.Tag.render(_templateObject$1(), this.class.postNewContainerTransformNew, this.class.postLazyLoadCheck, this.handleInsertPostTransitionEnd.bind(this)));
	        main_core.Dom.prepend(this.getNewPostContainer(), containerNode);
	        mobile_utils.Utils.htmlWithInlineJS(this.getNewPostContainer(), content).then(function () {
	          var postNode = _this2.getNewPostContainer().querySelector("div.".concat(_this2.class.post));

	          main_core.Dom.style(_this2.getNewPostContainer(), 'height', "".concat(postNode.scrollHeight + 15
	          /*margin-bottom*/
	          , "px"));
	        });
	      }

	      PublicationQueueInstance.emit('onPostInserted', new main_core_events.Event.BaseEvent({
	        data: {
	          key: queueKey
	        }
	      }));
	    }
	  }, {
	    key: "handleInsertPostTransitionEnd",
	    value: function handleInsertPostTransitionEnd(event) {
	      if (event.propertyName === 'height') {
	        this.getNewPostContainer().classList.remove(this.class.postNewContainerTransformNew);
	        this.getNewPostContainer().classList.remove(this.class.postNewContainerTransform);
	        main_core.Dom.style(this.getNewPostContainer(), 'height', null);
	        this.recalcMaxScroll();
	        BitrixMobile.LazyLoad.showImages();
	      }
	    }
	  }, {
	    key: "onLazyLoadImageLoaded",
	    value: function onLazyLoadImageLoaded(event) {
	      var _this3 = this;

	      this.recalcMaxScroll();

	      var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	          imageNode = _event$getData2[0];

	      if (imageNode) {
	        var postCheckNode = imageNode.closest('.' + this.class.postLazyLoadCheck);

	        if (postCheckNode) {
	          var postNode = postCheckNode.querySelector("div.".concat(this.class.post));

	          if (postNode) {
	            postCheckNode.classList.add(this.class.postNewContainerTransform);
	            main_core.Dom.style(postCheckNode, 'height', "".concat(postNode.scrollHeight, "px"));
	            setTimeout(function () {
	              postCheckNode.classList.remove(_this3.class.postNewContainerTransform);
	              main_core.Dom.style(postCheckNode, 'height', null);
	            }, 500);
	          }
	        }
	      }
	    }
	  }, {
	    key: "recalcMaxScroll",
	    value: function recalcMaxScroll() {
	      this.setMaxScroll(document.documentElement.scrollHeight - window.innerHeight - 190);
	    }
	  }]);
	  return Feed;
	}();

	var Instance = new Feed(),
	    BalloonNotifierInstance = new BalloonNotifier(),
	    NotificationBarInstance = new NotificationBar(),
	    DatabaseUnsentPostInstance = new Database(),
	    PublicationQueueInstance = new PublicationQueue();

	exports.Instance = Instance;
	exports.BalloonNotifierInstance = BalloonNotifierInstance;
	exports.NotificationBarInstance = NotificationBarInstance;
	exports.DatabaseUnsentPostInstance = DatabaseUnsentPostInstance;
	exports.PublicationQueueInstance = PublicationQueueInstance;

}((this.BX.MobileLivefeed = this.BX.MobileLivefeed || {}),BX,BX,BX,BX));
//# sourceMappingURL=livefeed.bundle.js.map
