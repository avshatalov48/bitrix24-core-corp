this.BX = this.BX || {};
(function (exports,ui_designTokens,main_core,main_popup,main_polyfill_intersectionobserver,main_core_events,main_loader) {
	'use strict';

	var intersectionObserver;

	function observeIntersection(entity, callback) {
	  if (!intersectionObserver) {
	    intersectionObserver = new IntersectionObserver(function (entries) {
	      entries.forEach(function (entry) {
	        if (entry.isIntersecting) {
	          intersectionObserver.unobserve(entry.target);
	          var observedCallback = entry.target.observedCallback;
	          delete entry.target.observedCallback;
	          setTimeout(observedCallback);
	        }
	      });
	    }, {
	      threshold: 0
	    });
	  }

	  entity.observedCallback = callback;
	  intersectionObserver.observe(entity);
	}

	var Pagination = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Pagination, _EventEmitter);

	  function Pagination(callback) {
	    var _this;

	    babelHelpers.classCallCheck(this, Pagination);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Pagination).call(this, 'disk.users'));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "busy", false);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "finished", true);
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "pageNumber", 1);

	    if (callback instanceof Function) {
	      _this.finished = false;
	      _this.callback = callback;
	    }

	    return _this;
	  }

	  babelHelpers.createClass(Pagination, [{
	    key: "isFinished",
	    value: function isFinished() {
	      return this.finished === true;
	    }
	  }, {
	    key: "getNext",
	    value: function getNext() {
	      var _this2 = this;

	      if (this.busy === true || this.finished === true) {
	        return false;
	      }

	      this.busy = true;
	      this.callback(++this.pageNumber).then(function (_ref) {
	        var _ref$data = _ref.data,
	            data = _ref$data.data,
	            getPageCount = _ref$data.getPageCount,
	            getCurrentPage = _ref$data.getCurrentPage,
	            errors = _ref.errors;

	        _this2.emit('onGetPage', {
	          data: data,
	          getPageCount: getPageCount,
	          getCurrentPage: getCurrentPage
	        });

	        if (getCurrentPage >= getPageCount) {
	          _this2.finished = true;

	          _this2.emit('onEndPage', {
	            getPageCount: getPageCount,
	            getCurrentPage: getCurrentPage
	          });
	        }

	        _this2.busy = false;
	      }, function () {
	        _this2.emit('onError');

	        _this2.busy = false;
	      });
	    }
	  }]);
	  return Pagination;
	}(main_core_events.EventEmitter);

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8;
	var repo = [];

	var Users = /*#__PURE__*/function () {
	  babelHelpers.createClass(Users, null, [{
	    key: "get",

	    /*
	     * @test
	     * @return {*}
	     */
	    value: function get(index) {
	      return repo[index > 0 ? index : 0];
	    }
	  }]);

	  function Users(data, paginationCallback, options) {
	    babelHelpers.classCallCheck(this, Users);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "maxCount", 3);
	    babelHelpers.defineProperty(this, "title", null);
	    babelHelpers.defineProperty(this, "items", new Map());
	    babelHelpers.defineProperty(this, "options", {});
	    this.options = options || {};
	    data.forEach(this.addItem.bind(this));
	    this.renderFirst();
	    this.pagination = new Pagination(paginationCallback);
	    this.pagination.subscribe('onGetPage', this.onGetPage.bind(this));
	    this.pagination.subscribe('onEndPage', this.onEndPage.bind(this));
	    repo.push(this);
	  }
	  /**
	   * @private
	   */


	  babelHelpers.createClass(Users, [{
	    key: "addItem",
	    value: function addItem(user) {
	      user.id = user.id || user.entityId;
	      this.items.set(user.id, user);
	      return user;
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "renderFirst",
	    value: function renderFirst() {
	      var visibleCount = 0;
	      var keys = this.items.keys();
	      var key;
	      var usersContainer = this.getUserListContainer();

	      while (visibleCount < this.maxCount && (key = keys.next().value)) {
	        var userNode = this.getUserContainer(this.items.get(key));

	        if (!usersContainer.contains(userNode)) {
	          usersContainer.appendChild(userNode);
	        }

	        visibleCount++;
	      }

	      if (this.items.size > this.maxCount) {
	        this.getMoreButton().innerHTML = this.items.size - this.maxCount;
	        this.getMoreButton().style.display = 'flex';
	        this.getContainer().style.cursor = 'pointer';
	      } else {
	        this.getMoreButton().style.display = 'none';
	        this.getContainer().style.cursor = '';
	      }

	      if (this.items.size <= 0) {
	        this.getContainer().style.display = 'none';
	      } else if (this.getContainer().style.display === 'none') {
	        this.getContainer().style.display = 'flex';
	      }
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getUserContainer",
	    value: function getUserContainer(user) {
	      return this.cache.remember('userContainer' + user['id'], function () {
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-icon ui-icon-common-user disk-active-user-list-item\" title=\"", "\">\n\t\t\t\t\t<i ", ">\n\t\t\t\t\t</i>\n\t\t\t\t</div>\n\t\t\t"])), main_core.Text.encode(user['name']), user['avatar'] ? "style=\"background: url('".concat(encodeURI(main_core.Text.encode(user['avatar'])), "') no-repeat center; background-size: cover;\" ") : '');
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "renderPopupUser",
	    value: function renderPopupUser(user) {
	      var _wrapper;

	      var wrapper;

	      if (user.url) {
	        wrapper = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a href=\"", "\" class=\"disk-active-user-popup-item\">\n\t\t\t\t</a>>\n\t\t\t"])), user['url']);
	      } else {
	        wrapper = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"disk-active-user-popup-item\">\n\t\t\t\t</div>>\n\t\t\t"])));
	      }

	      var userRow = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-icon ui-icon-common-user disk-active-user-popup-icon\">\n\t\t\t\t<i ", ">\n\t\t\t\t</i>\n\t\t\t</div>\n\t\t\t<div class=\"disk-active-user-popup-name\">", "</div>\n\t\t"])), user['avatar'] ? "style=\"background: url('".concat(encodeURI(main_core.Text.encode(user['avatar'])), "') no-repeat center; background-size: cover;\" ") : '', main_core.Text.encode(user['name']));

	      (_wrapper = wrapper).append.apply(_wrapper, babelHelpers.toConsumableArray(userRow));

	      return wrapper;
	    }
	    /**
	     * @public
	     */

	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this = this;

	      var placeInGrid = this.options.placeInGrid || false;
	      return this.cache.remember('mainContainer', function () {
	        var style = _this.items.size <= 0 ? ' style="display: none;" ' : '';
	        var gridModifier = placeInGrid ? 'disk-active-user--grid' : '';
	        return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-active-user-box ", "\" ", " onclick=\"", "\">\n\t\t\t<div class=\"disk-active-user\">\n\t\t\t\t<div class=\"disk-active-user-inner\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>"])), gridModifier, style, _this.showPopupUsers.bind(_this), _this.getUserListContainer(), _this.getMoreButton());
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getUserListContainer",
	    value: function getUserListContainer() {
	      return this.cache.remember('users', function () {
	        return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"disk-active-user-list\"></div>"])));
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getMoreButton",
	    value: function getMoreButton() {
	      return this.cache.remember('more', function () {
	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"disk-active-user-value\" style=\"display: none;\"></div>"])));
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "showPopupUsers",
	    value: function showPopupUsers() {
	      var _this2 = this;

	      this.getPopup().show();
	      this.items.forEach(function (item) {
	        _this2.getPopupUsersContainer().appendChild(_this2.renderPopupUser(item));
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      if (this.popup) {
	        return this.popup;
	      }

	      this.popup = new main_popup.Popup({
	        className: 'disk-active-user-popup',
	        content: main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"disk-active-user-popup-content disk-active-user-popup--grid\">\n\t\t\t\t", "\n\t\t\t\t<div class=\"disk-active-user-popup-box\">\n\t\t\t\t\t<div class=\"disk-active-user-popup-inner\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), this.title ? "<div class=\"disk-active-user-popup-title\">".concat(this.title, "</div>") : '', this.getPopupUsersContainer(), this.getPopupUsersEndBlock()),
	        bindElement: this.getContainer(),
	        closeByEsc: true,
	        autoHide: true
	      });
	      this.popup.subscribeOnce('onAfterClose', function () {
	        delete this.popup;
	        this.cache["delete"]('popupUsers');
	        this.cache["delete"]('popupUsersEndBlock');
	      }.bind(this));
	      return this.popup;
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getPopupUsersContainer",
	    value: function getPopupUsersContainer() {
	      return this.cache.remember('popupUsers', function () {
	        return document.createElement('div');
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getPopupUsersEndBlock",
	    value: function getPopupUsersEndBlock() {
	      var _this3 = this;

	      return this.cache.remember('popupUsersEndBlock', function () {
	        var res = document.createElement('div');

	        if (_this3.pagination.isFinished()) {
	          return res;
	        }

	        var onclick = _this3.getNextPage.bind(_this3);

	        res.className = 'disk-active-user-popup-box-pagination-loader';
	        res.innerHTML = main_core.Loc.getMessage('JS_DISK_USERS_PAGINATION');
	        res.addEventListener('click', onclick);
	        observeIntersection(res, onclick);
	        return res;
	      });
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "getNextPage",
	    value: function getNextPage() {
	      this.loader = this.loader || new main_loader.Loader({
	        target: this.getPopupUsersEndBlock(),
	        size: 20
	      });
	      this.loader.show();
	      this.pagination.getNext();
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "onGetPage",
	    value: function onGetPage(_ref) {
	      var _this4 = this;

	      var data = _ref.data;

	      if (main_core.Type.isArray(data)) {
	        data.forEach(function (item) {
	          var user = _this4.addItem(item);

	          _this4.getPopupUsersContainer().appendChild(_this4.renderPopupUser(user));
	        });
	      }
	    }
	    /**
	     * @private
	     */

	  }, {
	    key: "onEndPage",
	    value: function onEndPage() {
	      if (this.loader) {
	        this.loader.hide();
	      }

	      this.getPopupUsersEndBlock().style.display = 'none';
	    }
	    /**
	     * @public
	     */

	  }, {
	    key: "addUser",
	    value: function addUser(userData) {
	      this.addItem(userData);
	      this.renderFirst();
	    }
	  }, {
	    key: "hasUser",
	    value: function hasUser(userId) {
	      return this.items.has(userId);
	    }
	  }, {
	    key: "getUser",
	    value: function getUser(userId) {
	      return this.items.get(userId);
	    }
	  }, {
	    key: "forEach",
	    value: function forEach(fn) {
	      this.items.forEach(fn);
	    }
	    /**
	     * @public
	     */

	  }, {
	    key: "deleteUser",
	    value: function deleteUser(userId) {
	      if (!this.hasUser(userId)) {
	        return;
	      }

	      var user = this.items.get(userId);
	      this.items["delete"](userId);

	      if (this.cache.has('userContainer' + user['id'])) {
	        var usersContainer = this.getUserListContainer();
	        var userNode = this.cache.get('userContainer' + userId);
	        this.cache["delete"]('userContainer' + userId);

	        if (usersContainer.contains(userNode)) {
	          usersContainer.removeChild(userNode);
	        }
	      }

	      this.renderFirst();
	    }
	  }]);
	  return Users;
	}();

	exports.Users = Users;

}((this.BX.Disk = this.BX.Disk || {}),BX,BX,BX.Main,BX,BX.Event,BX));
//# sourceMappingURL=users.bundle.js.map
