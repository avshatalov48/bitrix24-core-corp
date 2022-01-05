this.BX = this.BX || {};
this.BX.UI = this.BX.UI || {};
(function (exports,sidepanel,main_core,ui_buttons,ui_sidepanel_menu,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10;

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var UI = BX.UI;
	var SidePanel = BX.SidePanel;

	function prepareOptions() {
	  var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	  options = Object.assign({}, options);
	  options.design = Object.assign({}, options.design || {});
	  options.design = babelHelpers.objectSpread({
	    margin: true,
	    section: true
	  }, options.design);
	  options.extensions = (options.extensions || []).concat(['ui.sidepanel.layout', 'ui.buttons']);

	  if (options.toolbar) {
	    options.extensions.push('ui.buttons.icons');
	  }

	  if (options.design.section) {
	    options.extensions.push('ui.sidepanel-content');
	  }

	  if (options.menu) {
	    options.extensions.push('ui.sidepanel.menu');
	  }

	  return options;
	}

	var _container = /*#__PURE__*/new WeakMap();

	var _options = /*#__PURE__*/new WeakMap();

	var _menu = /*#__PURE__*/new WeakMap();

	var _onMenuItemClick = /*#__PURE__*/new WeakSet();

	var Layout = /*#__PURE__*/function () {
	  babelHelpers.createClass(Layout, null, [{
	    key: "createContent",
	    value: function createContent() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      options = prepareOptions(options);
	      return top.BX.Runtime.loadExtension(options.extensions).then(function () {
	        return new Layout(options).render();
	      });
	    }
	  }]);

	  function Layout() {
	    var _this = this;

	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Layout);

	    _onMenuItemClick.add(this);

	    _container.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _options.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _menu.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _options, prepareOptions(options));
	    var menuOptions = babelHelpers.classPrivateFieldGet(this, _options).menu;

	    if (menuOptions) {
	      babelHelpers.classPrivateFieldSet(this, _menu, new ui_sidepanel_menu.Menu(Object.assign(menuOptions)));

	      if (main_core.Type.isUndefined(menuOptions.contentAttribute)) {
	        menuOptions.contentAttribute = 'data-menu-item-id';
	      }

	      if (menuOptions.contentAttribute) {
	        babelHelpers.classPrivateFieldGet(this, _menu).subscribe('click', function (event) {
	          _classPrivateMethodGet(_this, _onMenuItemClick, _onMenuItemClick2).call(_this, (event.getData() || {}).item);
	        });
	      }
	    }
	  }

	  babelHelpers.createClass(Layout, [{
	    key: "getContainer",
	    value: function getContainer() {
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-sidepanel-layout\"></div>"]))));
	      }

	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this2 = this;

	      var content = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      var promised = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

	      if (babelHelpers.classPrivateFieldGet(this, _options).content && !promised) {
	        content = babelHelpers.classPrivateFieldGet(this, _options).content();

	        if (Object.prototype.toString.call(content) === "[object Promise]" || content.toString && content.toString() === "[object BX.Promise]") {
	          return content.then(function (content) {
	            return _this2.render(content, true);
	          });
	        }
	      }

	      var container = this.getContainer();
	      container.innerHTML = ''; // HEADER

	      if (babelHelpers.classPrivateFieldGet(this, _options).title) {
	        var title = main_core.Tag.safe(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["", ""])), babelHelpers.classPrivateFieldGet(this, _options).title);
	        var header = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-sidepanel-layout-header\">\n\t\t\t\t\t<div class=\"ui-sidepanel-layout-title\">", "</div>\n\t\t\t\t</div>\n\t\t\t"])), title);

	        if (main_core.Type.isFunction(babelHelpers.classPrivateFieldGet(this, _options).toolbar)) {
	          var toolbar = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-sidepanel-layout-toolbar\"></div>"])));
	          babelHelpers.classPrivateFieldGet(this, _options).toolbar(babelHelpers.objectSpread({}, UI)).forEach(function (button) {
	            if (button instanceof ui_buttons.BaseButton) {
	              button.renderTo(toolbar);
	            } else if (main_core.Type.isDomNode(button)) {
	              toolbar.appendChild(button);
	            } else {
	              throw main_core.BaseError('Wrong button type ' + button);
	            }
	          });
	          header.appendChild(toolbar);
	        }

	        container.appendChild(header);
	      } // CONTENT


	      {
	        var design = babelHelpers.classPrivateFieldGet(this, _options).design;
	        var classes = ['ui-sidepanel-layout-content'];
	        var styles = [];

	        if (design.margin) {
	          if (design.margin === true) {
	            classes.push('ui-sidepanel-layout-content-margin');
	          } else {
	            styles.push('margin: ' + design.margin);
	          }
	        }

	        var contentElement = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\" style=\"", "\"></div>"])), classes.join(' '), styles.join('; '));
	        container.appendChild(contentElement);

	        if (babelHelpers.classPrivateFieldGet(this, _menu)) {
	          babelHelpers.classPrivateFieldGet(this, _menu).renderTo(contentElement);
	        }

	        contentElement.appendChild(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-sidepanel-layout-content-inner\"></div>"]))));
	        contentElement = contentElement.lastElementChild;

	        if (design.section) {
	          contentElement.appendChild(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-slider-section ui-sidepanel-layout-content-fill-height\"></div>"]))));
	          contentElement = contentElement.firstElementChild;
	        }

	        if (typeof content === 'string') {
	          contentElement.innerHTML = content;
	        } else if (content instanceof Element) {
	          contentElement.appendChild(content);
	        }

	        if (babelHelpers.classPrivateFieldGet(this, _menu)) {
	          _classPrivateMethodGet(this, _onMenuItemClick, _onMenuItemClick2).call(this, babelHelpers.classPrivateFieldGet(this, _menu).getActiveItem(), contentElement);
	        }
	      } // FOOTER

	      var isButtonsUndefined = typeof babelHelpers.classPrivateFieldGet(this, _options).buttons === 'undefined';

	      if (typeof babelHelpers.classPrivateFieldGet(this, _options).buttons === 'function' || isButtonsUndefined) {
	        var cancelButton = new ui_buttons.CancelButton({
	          onclick: function onclick() {
	            return SidePanel.Instance.close();
	          }
	        });
	        var closeButton = new ui_buttons.CloseButton({
	          onclick: function onclick() {
	            return SidePanel.Instance.close();
	          }
	        });
	        var defaults = babelHelpers.objectSpread({}, UI, {
	          cancelButton: cancelButton,
	          closeButton: closeButton
	        });

	        if (isButtonsUndefined) {
	          babelHelpers.classPrivateFieldGet(this, _options).buttons = function () {
	            return [closeButton];
	          };
	        }

	        var buttonList = babelHelpers.classPrivateFieldGet(this, _options).buttons(defaults);

	        if (buttonList && buttonList.length > 0) {
	          container.appendChild(main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-sidepanel-layout-footer-anchor\"></div>"]))));
	          var footer = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-sidepanel-layout-footer\"></div>"])));
	          var _classes = ['ui-sidepanel-layout-buttons'];

	          if (babelHelpers.classPrivateFieldGet(this, _options).design.alignButtonsLeft) {
	            _classes.push('ui-sidepanel-layout-buttons-align-left');
	          }

	          var buttons = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"></div>"])), _classes.join(' '));
	          footer.appendChild(buttons);
	          buttonList.forEach(function (button) {
	            if (button instanceof ui_buttons.BaseButton) {
	              button.renderTo(buttons);
	            } else if (main_core.Type.isDomNode(button)) {
	              buttons.appendChild(button);
	            } else {
	              throw main_core.BaseError('Wrong button type ' + button);
	            }
	          });
	          container.appendChild(footer);
	        }
	      }

	      return container;
	    }
	  }]);
	  return Layout;
	}();

	function _onMenuItemClick2(item) {
	  var container = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	  if (!item) {
	    return;
	  }

	  var id = item.getId();
	  var attr = babelHelpers.classPrivateFieldGet(this, _options).menu.contentAttribute;

	  if (!attr) {
	    return;
	  }

	  container = container || babelHelpers.classPrivateFieldGet(this, _container);
	  var nodes = container.querySelectorAll("[".concat(attr, "]"));
	  nodes = Array.prototype.slice.call(nodes);
	  nodes.forEach(function (node) {
	    node.hidden = node.getAttribute(attr) !== id;
	  });
	}

	exports.Layout = Layout;

}((this.BX.UI.SidePanel = this.BX.UI.SidePanel || {}),BX,BX,BX.UI,BX.UI.SidePanel,BX.Event));
//# sourceMappingURL=bundle.js.map
