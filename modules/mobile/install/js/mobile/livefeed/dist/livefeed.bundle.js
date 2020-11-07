this.BX = this.BX || {};
(function (exports,main_core,mobile_imageviewer,mobile_utils) {
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
	}(main_core.Event.EventEmitter);

	var Post = /*#__PURE__*/function () {
	  function Post(data) {
	    babelHelpers.classCallCheck(this, Post);
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

	  babelHelpers.createClass(Post, [{
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
	          event = data.event; // for old versions without post menu in the feed

	      if (!main_core.Type.isDomNode(node)) {
	        node = document.getElementById('log_entry_favorites_' + this.logId);
	      }

	      if (main_core.Type.isDomNode(node)) {
	        var oldValue = node.getAttribute('data-favorites') === 'Y' ? 'Y' : 'N';
	        var newValue = oldValue === 'Y' ? 'N' : 'Y'; // for old versions without post menu in the feed

	        if (node.classList.contains('lenta-item-fav')) {
	          if (oldValue === 'Y') {
	            node.classList.remove('lenta-item-fav-active');
	          } else {
	            node.classList.add('lenta-item-fav-active');
	          }
	        }

	        node.setAttribute('data-favorites', newValue);
	        var BMAjaxWrapper = new MobileAjaxWrapper();
	        BMAjaxWrapper.runAction('socialnetwork.api.livefeed.changeFavorites', {
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
	              oMSL.setFollow({
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

	        if (context === 'detail') {
	          BXMobileApp.onCustomEvent('Livefeed::showLoader', {}, true, true);
	        }

	        var BMAjaxWrapper = new MobileAjaxWrapper();
	        BMAjaxWrapper.runAction('socialnetwork.api.livefeed.logentry.' + (newValue === 'Y' ? 'pin' : 'unpin'), {
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
	        BXMobileApp.Events.postToComponent('taskbackground::task::action', [{
	          id: this.taskId,
	          title: 'TASK',
	          taskInfo: {
	            title: this.taskData.title,
	            creatorIcon: this.taskData.creatorIcon,
	            responsibleIcon: this.taskData.responsibleIcon
	          }
	        }, this.taskId, {
	          taskId: this.taskId,
	          getTaskInfo: true
	        }]);
	      } else {
	        var path = pathToEmptyPage;

	        if (this.calendarEventId > 0 && !focusComments && pathToCalendarEvent.length > 0) {
	          path = pathToCalendarEvent.replace('#EVENT_ID#', this.calendarEventId);
	        } else if (this.taskId > 0 && this.taskData && pathToTasksRouter.length > 0) // API version <= 31
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

	      var postNode = BX.findParent(menuNode, {
	        className: 'post-wrap'
	      });

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
	  return Post;
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
	      this.logId = parseInt(data.logId);
	      this.postId = parseInt(data.postId);
	      this.postPerms = main_core.Type.isStringFilled(data.postPerms) ? data.postPerms : 'R';
	      this.pageId = data.pageId;
	      this.contentTypeId = main_core.Type.isStringFilled(data.contentTypeId) ? data.contentTypeId : null;
	      this.contentId = main_core.Type.isInteger(data.contentId) ? data.contentId : 0;
	      this.useShare = !!data.useShare && this.postId > 0;
	      this.useFavorites = !!data.useFavorites && this.logId > 0;
	      this.useFollow = !!data.useFollow && this.logId > 0;
	      this.usePinned = !!data.usePinned && this.logId > 0;
	      this.useTasks = main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_USE_TASKS') === 'Y';
	      this.useRefreshComments = !!data.useRefreshComments;
	      this.favoritesValue = !!data.favoritesValue;
	      this.followValue = !!data.followValue;
	      this.pinnedValue = !!data.pinnedValue;
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
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_PINNED_' + (!!this.pinnedValue ? 'Y' : 'N')),
	          iconUrl: this.iconUrlFolderPath + (!!this.pinnedValue ? 'unpin.png' : 'pin.png'),
	          sectionCode: this.sectionCode,
	          action: function action() {
	            var postInstance = new Post({
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
	            iconUrl: this.iconUrlFolderPath + 'n_plus.png',
	            sectionCode: this.sectionCode,
	            action: function action() {
	              app.openTable({
	                callback: function callback() {
	                  oMSL.shareBlogPost();
	                },
	                url: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR') + 'mobile/index.php?mobile_action=' + (main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_CURRENT_EXTRANET_SITE') === 'Y' ? 'get_group_list' : 'get_usergroup_list') + '&feature=blog',
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
	          iconUrl: this.iconUrlFolderPath + 'pencil.png',
	          sectionCode: this.sectionCode,
	          action: function action() {
	            oMSL.editBlogPost({
	              feed_id: window.LiveFeedID,
	              post_id: _this.postId,
	              pinnedContext: !!_this.pinnedValue
	            });
	          },
	          arrowFlag: false,
	          feature: 'edit'
	        });
	        result.push({
	          id: 'delete',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_DELETE'),
	          iconName: 'delete',
	          sectionCode: this.sectionCode,
	          action: function action() {
	            oMSL.deleteBlogPost({
	              post_id: _this.postId
	            });
	          },
	          arrowFlag: false
	        });
	      }

	      if (this.useFavorites) {
	        result.push({
	          id: 'favorites',
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FAVORITES_' + (!!this.favoritesValue ? 'Y' : 'N')),
	          iconUrl: this.iconUrlFolderPath + 'favorite.png',
	          sectionCode: this.sectionCode,
	          action: function action() {
	            var postInstance = new Post({
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
	          title: main_core.Loc.getMessage('MOBILE_EXT_LIVEFEED_POST_MENU_FOLLOW_' + (!!this.followValue ? 'Y' : 'N')),
	          iconUrl: this.iconUrlFolderPath + 'eye.png',
	          sectionCode: this.sectionCode,
	          action: function action() {
	            oMSL.setFollow({
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
	          iconUrl: this.iconUrlFolderPath + 'n_refresh.png',
	          action: function action() {
	            if (oMSL.bDetailEmptyPage) {
	              // get comments on refresh from detail page menu
	              oMSL.getComments({
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
	          iconUrl: this.iconUrlFolderPath + 'link.png',
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
	            iconUrl: this.iconUrlFolderPath + 'n_check.png',
	            sectionCode: this.sectionCode,
	            action: function action() {
	              oMSL.createTask({
	                entityType: _this.contentTypeId,
	                entityId: _this.contentId,
	                logId: _this.logId
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

	var PinnedPanel = /*#__PURE__*/function () {
	  function PinnedPanel(data) {
	    babelHelpers.classCallCheck(this, PinnedPanel);
	    this.class = {
	      postItemPinned: 'lenta-item-pinned',
	      postItemPinActive: 'lenta-item-pin-active',
	      postItemPinnedBlock: 'post-item-pinned-block',
	      postItemPinnedTitle: 'post-item-pinned-title',
	      postItemPinnedTextBox: 'post-item-pinned-text-box',
	      postItemPinnedDesc: 'post-item-pinned-desc'
	    };
	  }

	  babelHelpers.createClass(PinnedPanel, [{
	    key: "getPinnedData",
	    value: function getPinnedData(params) {
	      var logId = params.logId ? parseInt(params.logId) : 0;
	      var BMAjaxWrapper = new MobileAjaxWrapper();

	      if (logId <= 0) {
	        return Promise.reject();
	      }

	      return new Promise(function (resolve, reject) {
	        BMAjaxWrapper.runAction('socialnetwork.api.livefeed.logentry.getPinData', {
	          data: {
	            params: {
	              logId: logId
	            }
	          }
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
	    value: function insertEntry(_ref) {
	      var logId = _ref.logId,
	          postNode = _ref.postNode,
	          pinnedPanelNode = _ref.pinnedPanelNode,
	          pinnedContent = _ref.pinnedContent;

	      if (!main_core.Type.isDomNode(postNode) || !main_core.Type.isDomNode(pinnedPanelNode)) {
	        return;
	      }

	      var postItemPinnedBlock = postNode.querySelector(".".concat(this.class.postItemPinnedBlock));

	      if (!main_core.Type.isDomNode(postItemPinnedBlock)) {
	        return;
	      }

	      postNode.classList.add(this.class.postItemPinned);
	      postNode.classList.add(this.class.postItemPinActive);
	      postItemPinnedBlock.innerHTML = "".concat(main_core.Type.isStringFilled(pinnedContent.TITLE) ? "<div class=\"".concat(this.class.postItemPinnedTitle, "\">").concat(pinnedContent.TITLE, "</div>") : '', "<div class=\"").concat(this.class.postItemPinnedTextBox, "\"><div class=\"").concat(this.class.postItemPinnedDesc, "\">").concat(pinnedContent.DESCRIPTION, "</div></div>");
	      main_core.Dom.prepend(postNode, pinnedPanelNode);
	    }
	  }, {
	    key: "extractEntry",
	    value: function extractEntry(_ref2) {
	      var postNode = _ref2.postNode,
	          containerNode = _ref2.containerNode;

	      if (!main_core.Type.isDomNode(postNode) || !main_core.Type.isDomNode(containerNode)) {
	        return;
	      }

	      postNode.classList.remove(this.class.postItemPinned);
	      postNode.classList.remove(this.class.postItemPinActive);
	      main_core.Dom.prepend(postNode, containerNode);
	    }
	  }]);
	  return PinnedPanel;
	}();

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
	      postItemAttachedFileWrap: 'post-item-attached-disk-file-wrap',
	      postItemInformWrap: 'post-item-inform-wrap',
	      postItemInformWrapTree: 'post-item-inform-wrap-tree',
	      postItemInformComments: 'post-item-inform-comments',
	      postItemInformMore: 'post-item-more',
	      postItemMore: 'post-more-block',
	      postItemPinnedBlock: 'post-item-pinned-block',
	      postItemPinActive: 'lenta-item-pin-active'
	    };
	    this.newPostContainer = null;
	    this.maxScroll = 0;
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
	      BXMobileApp.addCustomEvent("Livefeed::onLogEntryDetailNotFound", this.removePost.bind(this)); // from detail page

	      BXMobileApp.addCustomEvent('Livefeed.PinnedPanel::change', this.onPinnedPanelChange.bind(this));
	      BXMobileApp.addCustomEvent('Livefeed.PostDetail::pinChanged', this.onPostPinChanged.bind(this));
	      main_core.Event.EventEmitter.subscribe('BX.LazyLoad:ImageLoaded', this.onLazyLoadImageLoaded.bind(this));
	      document.addEventListener('DOMContentLoaded', function () {
	        document.addEventListener('click', _this.handleClick.bind(_this));
	      });
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

	      var postId = typeof params.postId != 'undefined' ? parseInt(params.postId) : 0;
	      var context = typeof params.context != 'undefined' ? params.context : '';
	      var pageId = typeof params.pageId != 'undefined' ? params.pageId : '';
	      var groupId = typeof params.groupId != 'undefined' ? params.groupId : null;

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
	      var _this2 = this;

	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      var logId = params.logId ? parseInt(params.logId) : 0;
	      var BMAjaxWrapper = new MobileAjaxWrapper();

	      if (logId <= 0 && !(main_core.Type.isStringFilled(params.entityType) && parseInt(params.entityId) > 0)) {
	        return;
	      }

	      BMAjaxWrapper.runComponentAction('bitrix:mobile.socialnetwork.log.ex', 'getEntryContent', {
	        mode: 'class',
	        signedParameters: this.getOption('signedParameters', {}),
	        data: {
	          params: {
	            logId: parseInt(params.logId) > 0 ? parseInt(params.logId) : 0,
	            pinned: !!params.pinned ? 'Y' : 'N',
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
	              _this2.insertPost({
	                logId: responseLogId.data.logId,
	                content: response.data.html,
	                postId: params.postId,
	                queueKey: params.queueKey,
	                action: params.action
	              });
	            }
	          });
	        } else {
	          _this2.insertPost({
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
	      var _this3 = this;

	      var containerNode = document.getElementById(this.nodeId.feedContainer);
	      var content = params.content;
	      var logId = params.logId;
	      var queueKey = params.queueKey;
	      var action = params.action;

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
	          var postNode = _this3.getNewPostContainer().querySelector("div.".concat(_this3.class.listPost));

	          main_core.Dom.style(_this3.getNewPostContainer(), 'height', "".concat(postNode.scrollHeight + 15
	          /*margin-bottom*/
	          , "px"));
	        });
	      }

	      PublicationQueueInstance.emit('onPostInserted', new main_core.Event.BaseEvent({
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
	      var _this4 = this;

	      this.recalcMaxScroll();

	      var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	          imageNode = _event$getData2[0];

	      if (imageNode) {
	        var postCheckNode = imageNode.closest(".".concat(this.class.postLazyLoadCheck));

	        if (postCheckNode) {
	          var postNode = postCheckNode.querySelector("div.".concat(this.class.listPost));

	          if (postNode) {
	            postCheckNode.classList.add(this.class.postNewContainerTransform);
	            main_core.Dom.style(postCheckNode, 'height', "".concat(postNode.scrollHeight, "px"));
	            setTimeout(function () {
	              postCheckNode.classList.remove(_this4.class.postNewContainerTransform);
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
	  }, {
	    key: "setPreventNextPage",
	    value: function setPreventNextPage(status) {
	      this.setOptions({
	        preventNextPage: !!status
	      });
	      var refreshNeededNode = document.getElementById('next_page_refresh_needed');
	      var nextPageCurtainNode = document.getElementById('next_post_more');

	      if (refreshNeededNode && nextPageCurtainNode) {
	        refreshNeededNode.style.display = !!status ? 'block' : 'none';
	        nextPageCurtainNode.style.display = !!status ? 'none' : 'block';
	      }
	    }
	  }, {
	    key: "getPinnedPanelNode",
	    value: function getPinnedPanelNode() {
	      return document.querySelector('[data-livefeed-pinned-panel]');
	    }
	  }, {
	    key: "onPinnedPanelChange",
	    value: function onPinnedPanelChange(params) {
	      var _this5 = this;

	      var logId = params.logId ? parseInt(params.logId) : 0;
	      var value = ['Y', 'N'].indexOf(params.value) !== -1 ? params.value : null;
	      var pinActionContext = main_core.Type.isStringFilled(params.pinActionContext) ? params.pinActionContext : 'list';

	      if (!logId || !value || !this.getPinnedPanelNode()) {
	        return;
	      }

	      var pinnedPanel = new PinnedPanel({});
	      var postNode = main_core.Type.isDomNode(params.postNode) ? params.postNode : null;

	      if (!main_core.Type.isDomNode(params.postNode)) // from detail in list
	        {
	          postNode = document.getElementById("lenta_item_".concat(logId));
	        }

	      if (!main_core.Type.isDomNode(postNode)) {
	        return;
	      }

	      if (value === 'N') {
	        pinnedPanel.extractEntry({
	          postNode: postNode,
	          containerNode: document.getElementById(this.nodeId.feedContainer)
	        });
	      } else if (value === 'Y') {
	        if (pinActionContext === 'list') {
	          app.showPopupLoader({
	            text: ""
	          });
	        }

	        pinnedPanel.getPinnedData({
	          logId: logId
	        }).then(function (pinnedData) {
	          app.hidePopupLoader();
	          pinnedPanel.insertEntry({
	            logId: logId,
	            postNode: postNode,
	            pinnedPanelNode: _this5.getPinnedPanelNode(),
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

	      var postNode = menuNode.closest(".".concat(this.class.detailPost));

	      if (!postNode) {
	        postNode = menuNode.closest(".".concat(this.class.listPost));
	      }

	      if (!postNode) {
	        return;
	      }

	      if (value === 'Y') {
	        postNode.classList.add(this.class.postItemPinActive);
	      } else {
	        postNode.classList.remove(this.class.postItemPinActive);
	      }
	    }
	  }, {
	    key: "handleClick",
	    value: function handleClick(e) {
	      if (e.target.classList.contains(this.class.pin)) {
	        var post = null;
	        var menuNode = null;
	        var context = 'list';
	        var postNode = e.target.closest(".".concat(this.class.listPost));

	        if (postNode) // lest
	          {
	            menuNode = postNode.querySelector('[data-menu-type="post"]');
	            post = this.getPostFromNode(postNode);
	          } else // detail
	          {
	            context = 'detail';
	            postNode = e.target.closest(".".concat(this.class.detailPost));

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
	      } else if ((e.target.closest(".".concat(this.class.listWrapper)) || e.target.closest(".".concat(this.class.pinnedPanel))) && !(e.target.tagName.toLowerCase() === 'a' && main_core.Type.isStringFilled(e.target.getAttribute('target')) && e.target.getAttribute('target').toLowerCase() === '_blank')) {
	        var detailFromPinned = !!(e.target.classList.contains(this.class.postItemPinnedBlock) || e.target.closest(".".concat(this.class.postItemPinnedBlock)));
	        var detailFromNormal = !!(!detailFromPinned && (e.target.classList.contains(this.class.postItemPostBlock) || e.target.closest(".".concat(this.class.postItemPostBlock))));
	        var detailToComments = !!(!detailFromPinned && !detailFromNormal && (e.target.classList.contains(this.class.postItemInformComments) || e.target.closest(".".concat(this.class.postItemInformComments))));
	        var detailToExpanded = !!(!detailFromPinned && !detailFromNormal && !detailToComments && (e.target.classList.contains(this.class.postItemInformMore) || e.target.closest(".".concat(this.class.postItemInformMore))));

	        if (detailFromPinned || detailFromNormal || detailToComments || detailToExpanded) {
	          var _postNode = e.target.closest(".".concat(this.class.listPost));

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
	      } else if (e.target.closest(".".concat(this.class.postWrapper))) {
	        var expand = !!(e.target.classList.contains(this.class.postItemInformMore) || e.target.closest(".".concat(this.class.postItemInformMore)) || e.target.classList.contains(this.class.postItemMore) || e.target.closest(".".concat(this.class.postItemMore)));

	        if (expand) {
	          var logId = this.getOption('logId', 0);

	          var _post2 = new Post({
	            logId: logId
	          });

	          _post2.expandText();

	          e.stopPropagation();
	          return e.preventDefault();
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

	      return new Post({
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

	      result = new Post({
	        logId: logId
	      });
	      return result;
	    }
	  }]);
	  return Feed;
	}();

	var Instance = new Feed();
	var BalloonNotifierInstance = new BalloonNotifier();
	var NotificationBarInstance = new NotificationBar();
	var DatabaseUnsentPostInstance = new Database();
	var PublicationQueueInstance = new PublicationQueue();
	var PostMenuInstance = new PostMenu();

	exports.Instance = Instance;
	exports.BalloonNotifierInstance = BalloonNotifierInstance;
	exports.NotificationBarInstance = NotificationBarInstance;
	exports.DatabaseUnsentPostInstance = DatabaseUnsentPostInstance;
	exports.PublicationQueueInstance = PublicationQueueInstance;
	exports.PostMenuInstance = PostMenuInstance;

}((this.BX.MobileLivefeed = this.BX.MobileLivefeed || {}),BX,BX,BX));
//# sourceMappingURL=livefeed.bundle.js.map
