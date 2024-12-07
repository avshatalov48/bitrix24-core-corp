/* eslint-disable */
(function (exports,main_core,main_core_events) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.BizprocMobile');
	var Comments = /*#__PURE__*/function () {
	  babelHelpers.createClass(Comments, null, [{
	    key: "block",
	    get: function get() {
	      return {
	        comments: 'comments',
	        stub: 'stub'
	      };
	    }
	  }]);
	  function Comments(options) {
	    babelHelpers.classCallCheck(this, Comments);
	    this.userId = options.userId;
	    this.workflowId = options.workflowId;
	    this.workflowIdInt = options.workflowIdInt;
	    this.guid = options.guid;
	    this.canReadCommentsOnInit = true;
	    this.timeout = 0;
	    this.timeoutSec = 2000;
	    this.commentsList = null;
	    this.unreadComments = new Map();
	    this.commentsToRead = new Map();
	    this.initTextField();
	    this.initCommentsBlock();
	    this.sendEvents(options);
	    this.bindEvents();
	  }
	  babelHelpers.createClass(Comments, [{
	    key: "initTextField",
	    value: function initTextField() {
	      if (BX.MobileUI.TextField.defaultParams) {
	        window.BX.MobileUI.TextField.show();
	      } else {
	        main_core_events.EventEmitter.subscribe(BX.MobileUI.events.MOBILE_UI_TEXT_FIELD_SET_PARAMS, function () {
	          return window.BX.MobileUI.TextField.show();
	        });
	      }
	    }
	  }, {
	    key: "initCommentsBlock",
	    value: function initCommentsBlock() {
	      this.stub = BX('commentsStub');
	      this.commentsBlock = BX('workflow-comments-block');
	      var firstComment = BX('post-comments-wrap').querySelector('.post-comment-block');
	      this.resolveVisibility(firstComment ? Comments.block.comments : Comments.block.stub);
	    }
	  }, {
	    key: "resolveVisibility",
	    value: function resolveVisibility() {
	      var visibleBlock = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : Comments.block.stub;
	      var hiddenClass = '--hidden';
	      if (visibleBlock === Comments.block.stub) {
	        if (main_core.Dom.hasClass(this.stub, hiddenClass)) {
	          main_core.Dom.removeClass(this.stub, hiddenClass);
	        }
	        if (!main_core.Dom.hasClass(this.commentsBlock, hiddenClass)) {
	          main_core.Dom.addClass(this.commentsBlock, hiddenClass);
	        }
	      } else if (visibleBlock === Comments.block.comments) {
	        if (main_core.Dom.hasClass(this.commentsBlock, hiddenClass)) {
	          main_core.Dom.removeClass(this.commentsBlock, hiddenClass);
	        }
	        if (!main_core.Dom.hasClass(this.stub, hiddenClass)) {
	          main_core.Dom.addClass(this.stub, hiddenClass);
	        }
	      }
	    }
	  }, {
	    key: "sendEvents",
	    value: function sendEvents(options) {
	      if (options.logId) {
	        var params = {
	          log_id: options.logId,
	          ts: options.currentTs,
	          bPull: false
	        };
	        BXMobileApp.onCustomEvent('onLogEntryRead', params, true);
	      }
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this = this;
	      main_core_events.EventEmitter.subscribe('OnUCHasBeenInitialized', function (event) {
	        var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 2),
	          xmlId = _event$getData2[0],
	          list = _event$getData2[1];
	        if (xmlId === "WF_".concat(_this.workflowId)) {
	          _this.commentsList = list;
	          _this.commentsList.canCheckVisibleComments = true;
	          _this.unreadComments = new Map(_this.commentsList.unreadComments);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('onUCFormSubmit', function () {
	        return _this.resolveVisibility(Comments.block.comments);
	      });
	      main_core_events.EventEmitter.subscribe('OnUCCommentWasPulled', function (event) {
	        var _event$getData3 = event.getData(),
	          _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 2),
	          id = _event$getData4[0],
	          data = _event$getData4[1];
	        if (id[0] === "WF_".concat(_this.workflowId)) {
	          var author = data.messageFields.AUTHOR;
	          if (Number(author.ID) !== Number(_this.userId)) {
	            _this.unreadComments.set(id[1], new Date());
	          }
	          _this.resolveVisibility(Comments.block.comments);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('OnUCommentWasDeleted', function (event) {
	        var _event$getData5 = event.getData(),
	          _event$getData6 = babelHelpers.slicedToArray(_event$getData5, 2),
	          xmlId = _event$getData6[0],
	          id = _event$getData6[1];
	        var commentId = id[1];
	        if (xmlId === "WF_".concat(_this.workflowId)) {
	          if (_this.commentsList.getCommentsCount() <= 0) {
	            _this.resolveVisibility(Comments.block.stub);
	          }
	          _this.unreadComments["delete"](commentId);
	        }
	      });
	      main_core_events.EventEmitter.subscribe('OnUCCommentWasRead', function (event) {
	        var _event$getData7 = event.getData(),
	          _event$getData8 = babelHelpers.slicedToArray(_event$getData7, 2),
	          xmlId = _event$getData8[0],
	          id = _event$getData8[1];
	        var commentId = id[1];
	        if (xmlId === "WF_".concat(_this.workflowId) && _this.unreadComments.has(commentId)) {
	          _this.commentsToRead.set(commentId, _this.unreadComments.get(commentId));
	          _this.unreadComments["delete"](commentId);
	          _this.sendOnCommentsReadEvent(_this.unreadComments.size);
	          if (_this.timeout <= 0) {
	            _this.timeout = setTimeout(function () {
	              return _this.readComments();
	            }, _this.timeoutSec);
	          }
	        }
	      });

	      // BXMobileApp.addCustomEvent('onPull-tasks', () => {});

	      main_core.Event.bind(document, 'visibilitychange', function () {
	        return _this.setCanCheckVisibleComments(!document.hidden);
	      });
	      main_core.Event.bind(window, 'pagehide', function () {
	        return _this.setCanCheckVisibleComments(false);
	      });
	      main_core.Event.bind(window, 'pageshow', function () {
	        return _this.setCanCheckVisibleComments(true);
	      });
	      BX.MobileUI.addLivefeedLongTapHandler(this.commentsBlock, {
	        likeNodeClass: 'post-comment-control-item-like'
	      });
	    }
	  }, {
	    key: "setCanCheckVisibleComments",
	    value: function setCanCheckVisibleComments(canCheck) {
	      if (canCheck && this.canReadCommentsOnInit) {
	        this.canReadCommentsOnInit = false;
	        this.readComments();
	      }
	      if (!this.commentsList) {
	        return;
	      }
	      this.commentsList.canCheckVisibleComments = canCheck;
	      if (canCheck) {
	        var scroll = main_core.GetWindowScrollPos();
	        var position = {
	          top: scroll.scrollTop,
	          bottom: scroll.scrollTop + main_core.GetWindowInnerSize().innerHeight
	        };
	        this.commentsList.checkVisibleComments(position);
	        if (this.canReadCommentsOnInit) {
	          this.canReadCommentsOnInit = false;
	          this.readComments();
	        }
	      }
	    }
	  }, {
	    key: "sendOnCommentsReadEvent",
	    value: function sendOnCommentsReadEvent() {
	    } // const params = {
	    // 	newCommentsCount,
	    // 	workflowId: this.workflowId,
	    // };
	    // BXMobileApp.Events.postToComponent('tasks.task.comments:onCommentsRead', params);
	    // BXMobileApp.Events.postToComponent('task.view.onCommentsRead', params, 'tasks.list');
	  }, {
	    key: "readComments",
	    value: function readComments() {
	      this.timeout = 0;
	      this.commentsToRead.clear();
	      void BX.ajax.runAction('bizproc.workflow.comment.markAsRead', {
	        data: {
	          workflowId: this.workflowId,
	          userId: this.userId
	        }
	      });
	    }
	  }]);
	  return Comments;
	}();
	namespace.Comments = Comments;

	exports.default = Comments;

}((this.window = this.window || {}),BX,BX.Event));
//# sourceMappingURL=script.js.map
