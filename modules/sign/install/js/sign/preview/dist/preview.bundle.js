this.BX = this.BX || {};
(function (exports,loader,main_loader,main_core_events,main_core) {
	'use strict';

	var _templateObject;
	var DocumentEmpty = {
	  render: function render() {
	    return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-preview__document-background\">\n\t\t\t\t<div class=\"sign-preview__document-empty\">\n\t\t\t\t\t<video poster=\"/bitrix/js/sign/preview/images/sign-preview-document-demo.jpg\" autoplay=\"true\" loop=\"true\" muted=\"true\" playsinline=\"true\">\n\t\t\t\t\t\t<source type=\"video/mp4\" src=\"/bitrix/js/sign/preview/images/sign-preview-document-demo.mp4\">\n\t\t\t\t\t\t<source type=\"video/webm\" src=\"/bitrix/js/sign/preview/images/sign-preview-document-demo.webm\">\n\t\t\t\t\t</video>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])));
	  }
	};

	var _templateObject$1, _templateObject2;
	var DocumentLoading = {
	  render: function render() {
	    var nodeLoader = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-preview__loader\"></div>\n\t\t"])));
	    new loader.Loader({
	      size: 80
	    }).show(nodeLoader);
	    return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-preview__document-background\">\n\t\t\t\t<div class=\"sign-preview__document-loading\">\n\t\t\t\t\t<div class=\"sign-preview__document-loading_container\">\n\t\t\t\t\t\t<div class=\"sign-preview__document-loading_logo ", "\"></div>\n\t\t\t\t\t\t", "\t\t\n\t\t\t\t\t\t<div class=\"sign-preview__document-loading_message\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), BX.message('LANGUAGE_ID') === 'ru' ? '--ru' : '', nodeLoader);
	  }
	};

	var _templateObject$2, _templateObject2$1, _templateObject3;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _style = /*#__PURE__*/new WeakMap();
	var _data = /*#__PURE__*/new WeakMap();
	var _layout = /*#__PURE__*/new WeakMap();
	var _getNodeContainer = /*#__PURE__*/new WeakSet();
	var BlockArea = /*#__PURE__*/function () {
	  function BlockArea(_ref) {
	    var id = _ref.id,
	      style = _ref.style,
	      data = _ref.data;
	    babelHelpers.classCallCheck(this, BlockArea);
	    _classPrivateMethodInitSpec(this, _getNodeContainer);
	    _classPrivateFieldInitSpec(this, _style, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _data, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    this.id = id ? id : null;
	    babelHelpers.classPrivateFieldSet(this, _style, style ? style : {});
	    babelHelpers.classPrivateFieldSet(this, _data, data ? data : null);
	    babelHelpers.classPrivateFieldSet(this, _layout, {
	      container: null
	    });
	  }
	  babelHelpers.createClass(BlockArea, [{
	    key: "render",
	    value: function render() {
	      return _classPrivateMethodGet(this, _getNodeContainer, _getNodeContainer2).call(this);
	    }
	  }]);
	  return BlockArea;
	}();
	function _getNodeContainer2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout).container) {
	    babelHelpers.classPrivateFieldGet(this, _layout).container = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__block-area --empty\"></div>\n\t\t\t"])));
	    if (main_core.Type.isString(babelHelpers.classPrivateFieldGet(this, _data).text)) {
	      var preContent = main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _data).text);
	      var content = preContent.replaceAll('[br]', '<br>');
	      babelHelpers.classPrivateFieldGet(this, _layout).container = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"sign-preview__block-area", "\">", "</div>\n\t\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _data).text === '' ? ' --empty' : '', content);
	    }
	    if (babelHelpers.classPrivateFieldGet(this, _data).base64) {
	      var src = 'data:image;base64,' + babelHelpers.classPrivateFieldGet(this, _data).base64;
	      babelHelpers.classPrivateFieldGet(this, _layout).container = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"sign-preview__block-area --image\">\n\t\t\t\t\t\t<img src=\"", "\" alt=\"\">\n\t\t\t\t\t</div>\n\t\t\t\t"])), src);
	    }
	    for (var key in babelHelpers.classPrivateFieldGet(this, _style)) {
	      babelHelpers.classPrivateFieldGet(this, _layout).container.style.setProperty(key, babelHelpers.classPrivateFieldGet(this, _style)[key]);
	    }
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout).container;
	}

	var _templateObject$3, _templateObject2$2, _templateObject3$1, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _getNodeNext = /*#__PURE__*/new WeakSet();
	var _getNodePrev = /*#__PURE__*/new WeakSet();
	var _getNodeTotalPages = /*#__PURE__*/new WeakSet();
	var _getNodeCurrentPage = /*#__PURE__*/new WeakSet();
	var _getNodeContainer$1 = /*#__PURE__*/new WeakSet();
	var _setCurrentPage = /*#__PURE__*/new WeakSet();
	var Navigation = /*#__PURE__*/function () {
	  function Navigation(_ref) {
	    var totalPages = _ref.totalPages;
	    babelHelpers.classCallCheck(this, Navigation);
	    _classPrivateMethodInitSpec$1(this, _setCurrentPage);
	    _classPrivateMethodInitSpec$1(this, _getNodeContainer$1);
	    _classPrivateMethodInitSpec$1(this, _getNodeCurrentPage);
	    _classPrivateMethodInitSpec$1(this, _getNodeTotalPages);
	    _classPrivateMethodInitSpec$1(this, _getNodePrev);
	    _classPrivateMethodInitSpec$1(this, _getNodeNext);
	    this.totalPages = totalPages ? totalPages : null;
	    this.currentPage = 1;
	    this.layout = {
	      container: null,
	      next: null,
	      prev: null,
	      currentPage: null,
	      totalPages: null
	    };
	  }
	  babelHelpers.createClass(Navigation, [{
	    key: "lock",
	    value: function lock() {
	      main_core.Dom.addClass(_classPrivateMethodGet$1(this, _getNodeContainer$1, _getNodeContainer2$1).call(this), '--lock');
	    }
	  }, {
	    key: "unLock",
	    value: function unLock() {
	      main_core.Dom.removeClass(_classPrivateMethodGet$1(this, _getNodeContainer$1, _getNodeContainer2$1).call(this), '--lock');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return _classPrivateMethodGet$1(this, _getNodeContainer$1, _getNodeContainer2$1).call(this);
	    }
	  }]);
	  return Navigation;
	}();
	function _getNodeNext2() {
	  var _this = this;
	  if (!this.layout.next) {
	    var icon = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<i></i>"])));
	    this.layout.next = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__navigation-control --next\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), icon);
	    icon.addEventListener('click', function () {
	      if (_this.currentPage < _this.totalPages) {
	        _this.currentPage++;
	        _classPrivateMethodGet$1(_this, _getNodePrev, _getNodePrev2).call(_this).classList.remove('--lock');
	      }
	      if (_this.currentPage === _this.totalPages) {
	        _classPrivateMethodGet$1(_this, _getNodeNext, _getNodeNext2).call(_this).classList.add('--lock');
	      }
	      _classPrivateMethodGet$1(_this, _setCurrentPage, _setCurrentPage2).call(_this, _this.currentPage);
	      main_core_events.EventEmitter.emit(_this, 'showNextPage', _this.currentPage);
	    });
	  }
	  return this.layout.next;
	}
	function _getNodePrev2() {
	  var _this2 = this;
	  if (!this.layout.prev) {
	    var icon = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<i></i>"])));
	    this.layout.prev = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__navigation-control --prev --lock\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), icon);
	    icon.addEventListener('click', function () {
	      if (_this2.currentPage > 1) {
	        _this2.currentPage--;
	        _classPrivateMethodGet$1(_this2, _getNodeNext, _getNodeNext2).call(_this2).classList.remove('--lock');
	      }
	      if (_this2.currentPage === 1) {
	        _classPrivateMethodGet$1(_this2, _getNodePrev, _getNodePrev2).call(_this2).classList.add('--lock');
	      }
	      _classPrivateMethodGet$1(_this2, _setCurrentPage, _setCurrentPage2).call(_this2, _this2.currentPage);
	      main_core_events.EventEmitter.emit(_this2, 'showPrevPage', _this2.currentPage);
	    });
	  }
	  return this.layout.prev;
	}
	function _getNodeTotalPages2() {
	  if (!this.layout.totalPages) {
	    this.layout.totalPages = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span>/", "</span>\n\t\t\t"])), this.totalPages);
	  }
	  return this.layout.totalPages;
	}
	function _getNodeCurrentPage2() {
	  if (!this.layout.currentPage) {
	    this.layout.currentPage = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span>", "</span>\n\t\t\t"])), this.currentPage);
	  }
	  return this.layout.currentPage;
	}
	function _getNodeContainer2$1() {
	  if (!this.layout.container) {
	    this.layout.container = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__navigation\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"sign-preview__navigation-info\">\n\t\t\t\t\t\t", " ", "", "\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$1(this, _getNodePrev, _getNodePrev2).call(this), main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_PAGE'), _classPrivateMethodGet$1(this, _getNodeCurrentPage, _getNodeCurrentPage2).call(this), _classPrivateMethodGet$1(this, _getNodeTotalPages, _getNodeTotalPages2).call(this), _classPrivateMethodGet$1(this, _getNodeNext, _getNodeNext2).call(this));
	    if (this.totalPages === 1 || !this.totalPages) {
	      this.lock();
	    }
	  }
	  return this.layout.container;
	}
	function _setCurrentPage2(param) {
	  if (main_core.Type.isNumber(param)) {
	    _classPrivateMethodGet$1(this, _getNodeCurrentPage, _getNodeCurrentPage2).call(this).innerText = param;
	  }
	}

	var TouchController = /*#__PURE__*/function () {
	  function TouchController(_ref) {
	    var target = _ref.target;
	    babelHelpers.classCallCheck(this, TouchController);
	    this.target = target ? target : null;
	    this.pos = {
	      top: 0,
	      left: 0,
	      x: 0,
	      y: 0
	    };
	    this.touchInit = false;
	    this.init();
	  }
	  babelHelpers.createClass(TouchController, [{
	    key: "init",
	    value: function init() {
	      if (!this.target) {
	        console.warn('BX.Sign.Preview: TouchController not initialized');
	        return;
	      }
	      this.target.addEventListener('mousedown', this.mouseDownHandler.bind(this));
	      this.target.addEventListener('mousemove', this.mouseMoveHandler.bind(this));
	      this.target.addEventListener('mouseup', this.mouseUpHandler.bind(this));
	      this.target.addEventListener('mouseleave', this.mouseUpHandler.bind(this));
	    }
	  }, {
	    key: "mouseDownHandler",
	    value: function mouseDownHandler(ev) {
	      this.touchInit = true;
	      this.target.style.cursor = 'grabbing';
	      this.target.style.userSelect = 'none';
	      this.pos = {
	        left: this.target.scrollLeft,
	        top: this.target.scrollTop,
	        x: ev.clientX,
	        y: ev.clientY
	      };
	    }
	  }, {
	    key: "mouseMoveHandler",
	    value: function mouseMoveHandler(ev) {
	      if (!this.touchInit) {
	        return;
	      }
	      var dx = ev.clientX - this.pos.x;
	      var dy = ev.clientY - this.pos.y;
	      this.target.scrollTop = this.pos.top - dy;
	      this.target.scrollLeft = this.pos.left - dx;
	    }
	  }, {
	    key: "mouseUpHandler",
	    value: function mouseUpHandler() {
	      this.touchInit = false;
	      this.target.style.cursor = 'grab';
	      this.target.style.removeProperty('user-select');
	    }
	  }]);
	  return TouchController;
	}();

	var _templateObject$4, _templateObject2$3, _templateObject3$2, _templateObject4$1, _templateObject5$1, _templateObject6$1;
	var Zoom = /*#__PURE__*/function () {
	  function Zoom(_ref) {
	    var imageWrapper = _ref.imageWrapper,
	      previewArea = _ref.previewArea;
	    babelHelpers.classCallCheck(this, Zoom);
	    this.imageWrapper = imageWrapper ? imageWrapper : null;
	    this.previewArea = previewArea ? previewArea : null;
	    this.layout = {
	      plus: null,
	      minus: null,
	      value: null
	    };
	    this.currentZoom = 100;
	    this.initTouchScroll();
	  }
	  babelHelpers.createClass(Zoom, [{
	    key: "lockPlus",
	    value: function lockPlus() {
	      main_core.Dom.addClass(this.getNodePlus(), '--lock');
	    }
	  }, {
	    key: "unLockPlus",
	    value: function unLockPlus() {
	      main_core.Dom.removeClass(this.getNodePlus(), '--lock');
	    }
	  }, {
	    key: "lockMinus",
	    value: function lockMinus() {
	      main_core.Dom.addClass(this.getNodeMinus(), '--lock');
	    }
	  }, {
	    key: "unLockMinus",
	    value: function unLockMinus() {
	      main_core.Dom.removeClass(this.getNodeMinus(), '--lock');
	    }
	  }, {
	    key: "getNodePlus",
	    value: function getNodePlus() {
	      var _this = this;
	      if (!this.layout.plus) {
	        var icon = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<i></i>"])));
	        this.layout.plus = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__zoom-control --plus\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), icon);
	        icon.addEventListener('click', function () {
	          _this.zoomPlus();
	          _this.unLockMinus();
	          if (_this.currentZoom === 200) {
	            _this.lockPlus();
	          }
	          _this.adjustOverflow();
	        });
	      }
	      return this.layout.plus;
	    }
	  }, {
	    key: "getNodeMinus",
	    value: function getNodeMinus() {
	      var _this2 = this;
	      if (!this.layout.minus) {
	        var icon = main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<i></i>"])));
	        this.layout.minus = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__zoom-control --minus --lock\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), icon);
	        icon.addEventListener('click', function () {
	          _this2.zoomMinus();
	          _this2.unLockPlus();
	          if (_this2.currentZoom === 100) {
	            _this2.lockMinus();
	          }
	          _this2.adjustOverflow();
	        });
	      }
	      return this.layout.minus;
	    }
	  }, {
	    key: "zoomPlus",
	    value: function zoomPlus() {
	      this.currentZoom += 25;
	      this.getNodeValue().innerText = this.currentZoom;
	      this.imageWrapper.style.setProperty('transform', "scale(".concat(this.currentZoom / 100, ")"));
	    }
	  }, {
	    key: "zoomMinus",
	    value: function zoomMinus() {
	      this.currentZoom -= 25;
	      this.getNodeValue().innerText = this.currentZoom;
	      this.imageWrapper.style.setProperty('transform', "scale(".concat(this.currentZoom / 100, ")"));
	    }
	  }, {
	    key: "adjustOverflow",
	    value: function adjustOverflow() {
	      if (this.currentZoom > 100) {
	        this.previewArea.style.setProperty('height', this.previewArea.offsetHight);
	        main_core.Dom.addClass(this.previewArea, '--overflow-auto');
	      } else {
	        main_core.Dom.removeClass(this.previewArea, '--overflow-auto');
	        this.previewArea.style.removeProperty('height');
	      }
	    }
	  }, {
	    key: "getNodeValue",
	    value: function getNodeValue() {
	      if (!this.layout.value) {
	        this.layout.value = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__zoom-value\">100</div>\n\t\t\t"])));
	      }
	      return this.layout.value;
	    }
	  }, {
	    key: "resetAll",
	    value: function resetAll() {
	      this.currentZoom = 100;
	      this.lockMinus();
	      this.unLockPlus();
	      this.adjustOverflow();
	      this.imageWrapper.style.removeProperty('transform');
	      this.getNodeValue().innerText = this.currentZoom;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-preview__zoom\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), this.getNodeMinus(), this.getNodeValue(), this.getNodePlus());
	    }
	  }, {
	    key: "initTouchScroll",
	    value: function initTouchScroll() {
	      new TouchController({
	        target: this.previewArea
	      });
	    }
	  }]);
	  return Zoom;
	}();

	var _templateObject$5, _templateObject2$4, _templateObject3$3, _templateObject4$2, _templateObject5$2;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _layout$1 = /*#__PURE__*/new WeakMap();
	var _pages = /*#__PURE__*/new WeakMap();
	var _blocks = /*#__PURE__*/new WeakMap();
	var _navigation = /*#__PURE__*/new WeakMap();
	var _zoom = /*#__PURE__*/new WeakMap();
	var _loader = /*#__PURE__*/new WeakMap();
	var _currentPage = /*#__PURE__*/new WeakMap();
	var _getAreaSize = /*#__PURE__*/new WeakSet();
	var _getNodeImageWrapper = /*#__PURE__*/new WeakSet();
	var _getNavigation = /*#__PURE__*/new WeakSet();
	var _loadPage = /*#__PURE__*/new WeakSet();
	var _receiveImg = /*#__PURE__*/new WeakSet();
	var _updatePage = /*#__PURE__*/new WeakSet();
	var _drawBlocks = /*#__PURE__*/new WeakSet();
	var _getNodeImagePreviewAreq = /*#__PURE__*/new WeakSet();
	var _getNodeDocument = /*#__PURE__*/new WeakSet();
	var _getZoom = /*#__PURE__*/new WeakSet();
	var _getNodeError = /*#__PURE__*/new WeakSet();
	var _getCurrentShownImageElement = /*#__PURE__*/new WeakSet();
	var DocumentReady = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(DocumentReady, _EventEmitter);
	  function DocumentReady(_ref) {
	    var _this;
	    var pages = _ref.pages,
	      blocks = _ref.blocks;
	    babelHelpers.classCallCheck(this, DocumentReady);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DocumentReady).call(this));
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getCurrentShownImageElement);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNodeError);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getZoom);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNodeDocument);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNodeImagePreviewAreq);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _drawBlocks);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _updatePage);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _receiveImg);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _loadPage);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNavigation);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getNodeImageWrapper);
	    _classPrivateMethodInitSpec$2(babelHelpers.assertThisInitialized(_this), _getAreaSize);
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _layout$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _pages, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _blocks, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _navigation, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _zoom, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _loader, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(babelHelpers.assertThisInitialized(_this), _currentPage, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Sign.Preview.DocumentReady');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _pages, pages || []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _blocks, blocks || []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _navigation, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _loader, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _zoom, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _currentPage, 1);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _layout$1, {
	      error: null,
	      container: null,
	      imageWrapper: null,
	      previewArea: null
	    });
	    return _this;
	  }
	  babelHelpers.createClass(DocumentReady, [{
	    key: "showError",
	    value: function showError() {
	      main_core.Dom.clean(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	      main_core.Dom.append(_classPrivateMethodGet$2(this, _getNodeError, _getNodeError2).call(this), _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	    }
	  }, {
	    key: "lock",
	    value: function lock() {
	      if (!babelHelpers.classPrivateFieldGet(this, _loader)) {
	        babelHelpers.classPrivateFieldSet(this, _loader, new main_loader.Loader({
	          size: 80
	        }));
	      }
	      babelHelpers.classPrivateFieldGet(this, _loader).show(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	      main_core.Dom.addClass(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this), '--lock');
	    }
	  }, {
	    key: "unLock",
	    value: function unLock() {
	      if (babelHelpers.classPrivateFieldGet(this, _loader)) {
	        babelHelpers.classPrivateFieldGet(this, _loader).hide(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	      }
	      main_core.Dom.removeClass(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this), '--lock');
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      return _classPrivateMethodGet$2(this, _getNodeDocument, _getNodeDocument2).call(this);
	    }
	  }, {
	    key: "updateImageContainerSize",
	    value: function updateImageContainerSize() {
	      var _classPrivateMethodGe;
	      var currentPageImageHeight = (_classPrivateMethodGe = _classPrivateMethodGet$2(this, _getCurrentShownImageElement, _getCurrentShownImageElement2).call(this)) === null || _classPrivateMethodGe === void 0 ? void 0 : _classPrivateMethodGe.offsetHeight;
	      if (currentPageImageHeight) {
	        main_core.Dom.style(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this), 'height', "".concat(currentPageImageHeight, "px"));
	      }
	    }
	  }, {
	    key: "setPages",
	    value: function setPages(pages) {
	      babelHelpers.classPrivateFieldSet(this, _pages, pages);
	    }
	  }]);
	  return DocumentReady;
	}(main_core_events.EventEmitter);
	function _getAreaSize2() {
	  var ratio = (babelHelpers.classPrivateFieldGet(this, _pages)[0].height / babelHelpers.classPrivateFieldGet(this, _pages)[0].width).toString();
	  ratio = ratio.split('.').join('');
	  var result = "".concat(babelHelpers.toConsumableArray(ratio).splice(0, 3).join(''), "%");
	  if (ratio === '1') {
	    result = '100%';
	  }
	  return result;
	}
	function _getNodeImageWrapper2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$1).imageWrapper) {
	    babelHelpers.classPrivateFieldGet(this, _layout$1).imageWrapper = main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__image-container\">\n\t\t\t\t\t<img src=\"", "\" alt=\"", "\" id=\"sign-preview_page-image\" class=\"sign-preview__image-container_img\">\n\t\t\t\t</div>\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _pages)[0].path, babelHelpers.classPrivateFieldGet(this, _pages)[0].name);
	    _classPrivateMethodGet$2(this, _drawBlocks, _drawBlocks2).call(this, babelHelpers.classPrivateFieldGet(this, _currentPage));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$1).imageWrapper;
	}
	function _getNavigation2() {
	  var _this2 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _navigation)) {
	    babelHelpers.classPrivateFieldSet(this, _navigation, new Navigation({
	      totalPages: babelHelpers.classPrivateFieldGet(this, _pages).length
	    }));
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldGet(this, _navigation), 'showNextPage', function (param) {
	      _classPrivateMethodGet$2(_this2, _loadPage, _loadPage2).call(_this2, param.data - 1, '--show-next-page');
	      _classPrivateMethodGet$2(_this2, _getZoom, _getZoom2).call(_this2).resetAll();
	    });
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldGet(this, _navigation), 'showPrevPage', function (param) {
	      _classPrivateMethodGet$2(_this2, _loadPage, _loadPage2).call(_this2, param.data - 1, '--show-prev-page');
	      _classPrivateMethodGet$2(_this2, _getZoom, _getZoom2).call(_this2).resetAll();
	    });
	  }
	  return babelHelpers.classPrivateFieldGet(this, _navigation);
	}
	function _loadPage2(index, direction) {
	  var _this3 = this;
	  main_core.Dom.clean(_classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	  if (!babelHelpers.classPrivateFieldGet(this, _pages)[index].isLoaded) {
	    _classPrivateMethodGet$2(this, _getNavigation, _getNavigation2).call(this).lock();
	    this.lock();
	  }
	  var imageNode = main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<img alt=\"", "\" \n\t\t\t\tclass=\"sign-preview__image-container_img ", "\"\n\t\t\t \tstyle=\"display: none;\">\n\t\t\t"])), babelHelpers.classPrivateFieldGet(this, _pages)[index].name, direction);
	  main_core.Dom.append(imageNode, _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	  imageNode.addEventListener('animationend', function () {
	    main_core.Dom.removeClass(imageNode, direction);
	    _this3.emit('onImageShow');
	  }, {
	    once: true
	  });
	  if (babelHelpers.classPrivateFieldGet(this, _pages)[index].prepared) {
	    imageNode.src = babelHelpers.classPrivateFieldGet(this, _pages)[index].path;
	    _classPrivateMethodGet$2(this, _updatePage, _updatePage2).call(this, index, imageNode);
	    return;
	  }
	  var imgUrl = babelHelpers.classPrivateFieldGet(this, _pages)[index].path;
	  var preparedImage = new Image();
	  preparedImage.crossOrigin = 'Anonymous';
	  preparedImage.src = imgUrl;
	  preparedImage.onload = function () {
	    _classPrivateMethodGet$2(_this3, _receiveImg, _receiveImg2).call(_this3, preparedImage, index, direction, imageNode);
	  };
	  imageNode.onerror = function () {
	    _classPrivateMethodGet$2(_this3, _getNavigation, _getNavigation2).call(_this3).unLock();
	    _this3.unLock();
	    _this3.showError();
	  };
	}
	function _receiveImg2(preparedImage, index, direction, imageNode) {
	  var canvas = document.createElement('canvas');
	  var context = canvas.getContext('2d');
	  canvas.width = preparedImage.width;
	  canvas.height = preparedImage.height;
	  context.drawImage(preparedImage, 0, 0);
	  try {
	    babelHelpers.classPrivateFieldGet(this, _pages)[index].path = canvas.toDataURL('image/png');
	    babelHelpers.classPrivateFieldGet(this, _pages)[index].prepared = true;
	    imageNode.src = babelHelpers.classPrivateFieldGet(this, _pages)[index].path;
	    _classPrivateMethodGet$2(this, _updatePage, _updatePage2).call(this, index, imageNode);
	  } catch (err) {
	    console.error("Error: ".concat(err));
	  }
	}
	function _updatePage2(param, imageNode) {
	  main_core.Dom.append(imageNode, _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	  imageNode.style.removeProperty('display');
	  babelHelpers.classPrivateFieldGet(this, _pages)[param].isLoaded = true;
	  _classPrivateMethodGet$2(this, _getNavigation, _getNavigation2).call(this).unLock();
	  this.unLock();
	  _classPrivateMethodGet$2(this, _drawBlocks, _drawBlocks2).call(this, param + 1);
	  babelHelpers.classPrivateFieldSet(this, _currentPage, param + 1);
	}
	function _drawBlocks2(pageNumber) {
	  var _this4 = this;
	  if (!pageNumber) {
	    return;
	  }
	  var currentBlocks = [];
	  babelHelpers.classPrivateFieldGet(this, _blocks).forEach(function (block) {
	    if (pageNumber === parseInt(block.position.page)) {
	      // copy object to correct change of styles when flipping previews
	      var style = block.style || {};
	      style = _objectSpread({}, style);
	      style.top = block.position.top + '%';
	      style.left = block.position.left + '%';
	      style.width = block.position.width + '%';
	      style.height = block.position.height + '%';
	      var realDocWidth = parseFloat(block.position.realDocumentWidthPx);
	      var currentDocWidth = parseFloat(block.position.currentDocumentWithPx);
	      var fontSize = parseFloat(block.style['font-size']) || 14;
	      if (realDocWidth && currentDocWidth && fontSize) {
	        style['font-size'] = fontSize * (currentDocWidth / realDocWidth) + 'px';
	        // hack from css styles (need refactoring)
	        var verticalPadding = 5 * (currentDocWidth / realDocWidth) + 'px';
	        var horizontalPadding = 8 * (currentDocWidth / realDocWidth) + 'px';
	        style['padding'] = verticalPadding + ' ' + horizontalPadding;
	      }
	      var newBlock = new BlockArea({
	        id: block.id,
	        style: style,
	        data: block.data
	      });
	      currentBlocks.push(newBlock);
	    }
	  });
	  currentBlocks.forEach(function (block) {
	    main_core.Dom.append(block.render(), _classPrivateMethodGet$2(_this4, _getNodeImageWrapper, _getNodeImageWrapper2).call(_this4));
	  });
	}
	function _getNodeImagePreviewAreq2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$1).previewArea) {
	    babelHelpers.classPrivateFieldGet(this, _layout$1).previewArea = main_core.Tag.render(_templateObject3$3 || (_templateObject3$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__image\" style=\"padding-top: ", "\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$2(this, _getAreaSize, _getAreaSize2).call(this), _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$1).previewArea;
	}
	function _getNodeDocument2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$1).container) {
	    babelHelpers.classPrivateFieldGet(this, _layout$1).container = main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__document-ready\">\n\t\t\t\t\t<div class=\"sign-preview__document-background\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"sign-preview__document-controls\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _classPrivateMethodGet$2(this, _getNodeImagePreviewAreq, _getNodeImagePreviewAreq2).call(this), _classPrivateMethodGet$2(this, _getNavigation, _getNavigation2).call(this).render(), _classPrivateMethodGet$2(this, _getZoom, _getZoom2).call(this).render());
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$1).container;
	}
	function _getZoom2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _zoom)) {
	    babelHelpers.classPrivateFieldSet(this, _zoom, new Zoom({
	      imageWrapper: _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this),
	      previewArea: _classPrivateMethodGet$2(this, _getNodeImagePreviewAreq, _getNodeImagePreviewAreq2).call(this)
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _zoom);
	}
	function _getNodeError2() {
	  var _this5 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$1).error) {
	    babelHelpers.classPrivateFieldGet(this, _layout$1).error = main_core.Tag.render(_templateObject5$2 || (_templateObject5$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__image-error\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_LOADIN_ERROR'));
	    var linkNode = babelHelpers.classPrivateFieldGet(this, _layout$1).error.getElementsByTagName('span')[0];
	    linkNode.addEventListener('click', function () {
	      _classPrivateMethodGet$2(_this5, _loadPage, _loadPage2).call(_this5, babelHelpers.classPrivateFieldGet(_this5, _currentPage) - 1);
	    });
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$1).error;
	}
	function _getCurrentShownImageElement2() {
	  return _classPrivateMethodGet$2(this, _getNodeImageWrapper, _getNodeImageWrapper2).call(this).querySelector('img.sign-preview__image-container_img');
	}

	var _templateObject$6, _templateObject2$5, _templateObject3$4;
	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _renderTo = /*#__PURE__*/new WeakMap();
	var _layout$2 = /*#__PURE__*/new WeakMap();
	var _pages$1 = /*#__PURE__*/new WeakMap();
	var _blocks$1 = /*#__PURE__*/new WeakMap();
	var _documentHash = /*#__PURE__*/new WeakMap();
	var _secCode = /*#__PURE__*/new WeakMap();
	var _documentReady = /*#__PURE__*/new WeakMap();
	var _getDocumentReady = /*#__PURE__*/new WeakSet();
	var _getNodeDocument$1 = /*#__PURE__*/new WeakSet();
	var _getNodeFooter = /*#__PURE__*/new WeakSet();
	var _loadFirstImage = /*#__PURE__*/new WeakSet();
	var _receiveImg$1 = /*#__PURE__*/new WeakSet();
	var Preview = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Preview, _EventEmitter);
	  function Preview(_ref) {
	    var _this;
	    var renderTo = _ref.renderTo,
	      pages = _ref.pages,
	      blocks = _ref.blocks,
	      documentHash = _ref.documentHash,
	      secCode = _ref.secCode;
	    babelHelpers.classCallCheck(this, Preview);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Preview).call(this));
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _receiveImg$1);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _loadFirstImage);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getNodeFooter);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getNodeDocument$1);
	    _classPrivateMethodInitSpec$3(babelHelpers.assertThisInitialized(_this), _getDocumentReady);
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _renderTo, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _layout$2, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _pages$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _blocks$1, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _documentHash, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _secCode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(babelHelpers.assertThisInitialized(_this), _documentReady, {
	      writable: true,
	      value: void 0
	    });
	    _this.setEventNamespace('BX.Sign.Preview');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _renderTo, renderTo || null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _pages$1, pages || []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _blocks$1, blocks || []);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _documentHash, documentHash || null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _secCode, secCode || null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _documentReady, null);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _layout$2, {
	      document: null,
	      footer: null
	    });
	    _this.currentWidth = null;
	    _this.init();
	    return _this;
	  }
	  babelHelpers.createClass(Preview, [{
	    key: "showDocumentLoading",
	    value: function showDocumentLoading() {
	      main_core.Dom.clean(_classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	      main_core.Dom.append(DocumentLoading.render(), _classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	    }
	  }, {
	    key: "showDocumentEmpty",
	    value: function showDocumentEmpty() {
	      main_core.Dom.clean(_classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	      main_core.Dom.append(DocumentEmpty.render(), _classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	    }
	  }, {
	    key: "showDocumentReady",
	    value: function showDocumentReady() {
	      main_core.Dom.clean(_classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	      _classPrivateMethodGet$3(this, _getDocumentReady, _getDocumentReady2).call(this).setPages(babelHelpers.classPrivateFieldGet(this, _pages$1));
	      main_core.Dom.append(_classPrivateMethodGet$3(this, _getDocumentReady, _getDocumentReady2).call(this).render(), _classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this));
	      _classPrivateMethodGet$3(this, _getDocumentReady, _getDocumentReady2).call(this).updateImageContainerSize();
	    }
	  }, {
	    key: "afterRender",
	    value: function afterRender() {
	      var _this2 = this;
	      _classPrivateMethodGet$3(this, _getDocumentReady, _getDocumentReady2).call(this).subscribe('onImageShow', function () {
	        var documentReady = _classPrivateMethodGet$3(_this2, _getDocumentReady, _getDocumentReady2).call(_this2);
	        documentReady.updateImageContainerSize();
	      });
	      if (babelHelpers.classPrivateFieldGet(this, _documentHash) && babelHelpers.classPrivateFieldGet(this, _pages$1).length === 0 && BX.Sign.Backend) {
	        this.showDocumentLoading();
	        var checkReady = setInterval(function () {
	          BX.ajax.runAction('sign.api.document.layoutIsReady', {
	            data: {
	              documentHash: babelHelpers.classPrivateFieldGet(_this2, _documentHash),
	              secCode: babelHelpers.classPrivateFieldGet(_this2, _secCode)
	            }
	          });
	        }, 1000 * 30);
	        if (BX.PULL) {
	          BX.PULL.subscribe({
	            moduleId: 'sign',
	            command: 'layoutIsReady',
	            callback: function callback(result) {
	              if (result !== null && result !== void 0 && result.layout) {
	                var layout = result === null || result === void 0 ? void 0 : result.layout;
	                var blocks = result === null || result === void 0 ? void 0 : result.blocks;
	                if (main_core.Type.isArray(layout) && layout.length > 0) {
	                  babelHelpers.classPrivateFieldSet(_this2, _pages$1, layout);
	                  babelHelpers.classPrivateFieldSet(_this2, _blocks$1, blocks);
	                  _this2.subscribe('firstImageIsLoaded', function () {
	                    _this2.showDocumentReady();
	                  });
	                  _this2.subscribe('firstImageIsLoadedFail', function () {
	                    _this2.showDocumentReady();
	                    _classPrivateMethodGet$3(_this2, _getDocumentReady, _getDocumentReady2).call(_this2).showError();
	                  });
	                  _classPrivateMethodGet$3(_this2, _loadFirstImage, _loadFirstImage2).call(_this2);
	                }
	                clearInterval(checkReady);
	              }
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "init",
	    value: function init() {
	      var _this3 = this;
	      if (!babelHelpers.classPrivateFieldGet(this, _renderTo)) {
	        console.warn('BX.Sign.Preview: \'renderTo\' is not defined');
	        return;
	      }
	      if (!this.currentWidth) {
	        this.currentWidth = babelHelpers.classPrivateFieldGet(this, _renderTo).offsetWidth - 20;
	      }
	      var target = babelHelpers.classPrivateFieldGet(this, _renderTo).parentNode;
	      var nodeWrapper = main_core.Tag.render(_templateObject$6 || (_templateObject$6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"sign-preview sign-preview__scope\">\n\t\t\t\t<div class=\"sign-preview__body\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), _classPrivateMethodGet$3(this, _getNodeDocument$1, _getNodeDocument2$1).call(this), _classPrivateMethodGet$3(this, _getNodeFooter, _getNodeFooter2).call(this));
	      if (babelHelpers.classPrivateFieldGet(this, _pages$1).length > 0) {
	        this.showDocumentLoading();
	        _classPrivateMethodGet$3(this, _loadFirstImage, _loadFirstImage2).call(this);
	        this.subscribe('firstImageIsLoaded', function () {
	          _this3.showDocumentReady();
	        });
	        this.subscribe('firstImageIsLoadedFail', function () {
	          _this3.showDocumentReady();
	          _classPrivateMethodGet$3(_this3, _getDocumentReady, _getDocumentReady2).call(_this3).showError();
	        });
	      } else {
	        this.showDocumentEmpty();
	      }
	      main_core.Dom.clean(target);
	      main_core.Dom.append(nodeWrapper, target);
	      this.afterRender();
	    }
	  }]);
	  return Preview;
	}(main_core_events.EventEmitter);
	function _getDocumentReady2() {
	  var _this4 = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _documentReady)) {
	    babelHelpers.classPrivateFieldGet(this, _blocks$1).forEach(function (item) {
	      item.position.currentDocumentWithPx = _this4.currentWidth;
	    });
	    babelHelpers.classPrivateFieldSet(this, _documentReady, new DocumentReady({
	      pages: babelHelpers.classPrivateFieldGet(this, _pages$1),
	      blocks: babelHelpers.classPrivateFieldGet(this, _blocks$1)
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _documentReady);
	}
	function _getNodeDocument2$1() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$2).document) {
	    babelHelpers.classPrivateFieldGet(this, _layout$2).document = main_core.Tag.render(_templateObject2$5 || (_templateObject2$5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__document\"></div>\n\t\t\t"])));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$2).document;
	}
	function _getNodeFooter2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _layout$2).footer) {
	    babelHelpers.classPrivateFieldGet(this, _layout$2).footer = main_core.Tag.render(_templateObject3$4 || (_templateObject3$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-preview__footer\"></div>\n\t\t\t"])));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _layout$2).footer;
	}
	function _loadFirstImage2() {
	  var _this5 = this;
	  var imgUrl = babelHelpers.classPrivateFieldGet(this, _pages$1)[0].path;
	  var preparedImage = new Image();
	  preparedImage.crossOrigin = 'Anonymous';
	  preparedImage.src = imgUrl;
	  preparedImage.addEventListener('load', _classPrivateMethodGet$3(this, _receiveImg$1, _receiveImg2$1).bind(this, preparedImage), false);
	  preparedImage.onerror = function () {
	    _this5.emit('firstImageIsLoadedFail');
	  };
	}
	function _receiveImg2$1(preparedImage) {
	  var _this6 = this;
	  var canvas = document.createElement('canvas');
	  var context = canvas.getContext('2d');
	  canvas.width = preparedImage.width;
	  canvas.height = preparedImage.height;
	  context.drawImage(preparedImage, 0, 0);
	  try {
	    babelHelpers.classPrivateFieldGet(this, _pages$1)[0].path = canvas.toDataURL('image/png');
	    babelHelpers.classPrivateFieldGet(this, _pages$1)[0].prepared = true;
	    this.emit('firstImageIsLoaded');
	    // fix block positioning
	    setTimeout(function () {
	      return _classPrivateMethodGet$3(_this6, _getDocumentReady, _getDocumentReady2).call(_this6).updateImageContainerSize();
	    }, 0);
	  } catch (err) {
	    console.error("Error: ".concat(err));
	  }
	}

	exports.Preview = Preview;

}((this.BX.Sign = this.BX.Sign || {}),BX,BX,BX.Event,BX));
//# sourceMappingURL=preview.bundle.js.map
