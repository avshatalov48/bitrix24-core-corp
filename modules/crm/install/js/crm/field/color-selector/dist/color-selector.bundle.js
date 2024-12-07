this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,ui_designTokens,main_core_events,main_popup) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var ColorSelectorEvents = {
	  EVENT_COLORSELECTOR_VALUE_CHANGE: 'crm.field.colorselector:change'
	};
	var _target = /*#__PURE__*/new WeakMap();
	var _colorList = /*#__PURE__*/new WeakMap();
	var _selectedColorId = /*#__PURE__*/new WeakMap();
	var _readOnlyMode = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _icon = /*#__PURE__*/new WeakMap();
	var _container = /*#__PURE__*/new WeakMap();
	var _create = /*#__PURE__*/new WeakSet();
	var _getColorById = /*#__PURE__*/new WeakSet();
	var _getPopup = /*#__PURE__*/new WeakSet();
	var _getContent = /*#__PURE__*/new WeakSet();
	var ColorSelector = /*#__PURE__*/function () {
	  function ColorSelector(params) {
	    babelHelpers.classCallCheck(this, ColorSelector);
	    _classPrivateMethodInitSpec(this, _getContent);
	    _classPrivateMethodInitSpec(this, _getPopup);
	    _classPrivateMethodInitSpec(this, _getColorById);
	    _classPrivateMethodInitSpec(this, _create);
	    _classPrivateFieldInitSpec(this, _target, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _colorList, {
	      writable: true,
	      value: []
	    });
	    _classPrivateFieldInitSpec(this, _selectedColorId, {
	      writable: true,
	      value: 'default'
	    });
	    _classPrivateFieldInitSpec(this, _readOnlyMode, {
	      writable: true,
	      value: false
	    });
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _icon, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _container, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(this, _target, main_core.Type.isDomNode(params.target) ? params.target : null);
	    babelHelpers.classPrivateFieldSet(this, _colorList, main_core.Type.isArrayFilled(params.colorList) ? params.colorList : []);
	    babelHelpers.classPrivateFieldSet(this, _selectedColorId, main_core.Type.isStringFilled(params.selectedColorId) ? params.selectedColorId : []);
	    babelHelpers.classPrivateFieldSet(this, _readOnlyMode, params.readOnlyMode === true);
	    this.togglePopup = this.togglePopup.bind(this);
	    _classPrivateMethodGet(this, _create, _create2).call(this);
	  }

	  // region DOM management
	  babelHelpers.createClass(ColorSelector, [{
	    key: "togglePopup",
	    value: function togglePopup() {
	      if (babelHelpers.classPrivateFieldGet(this, _readOnlyMode)) {
	        return;
	      }
	      var popup = _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this);
	      if (popup.isShown()) {
	        popup.close();
	      } else {
	        popup.show();
	      }
	    }
	  }, {
	    key: "onSelectColor",
	    value: function onSelectColor(id) {
	      _classPrivateMethodGet(this, _getPopup, _getPopup2).call(this).close();
	      this.setValue(id);
	      main_core_events.EventEmitter.emit(this, ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE, {
	        value: id
	      });
	    }
	  }, {
	    key: "setValue",
	    value: function setValue(id) {
	      babelHelpers.classPrivateFieldSet(this, _selectedColorId, id);
	      var backgroundColor = _classPrivateMethodGet(this, _getColorById, _getColorById2).call(this, babelHelpers.classPrivateFieldGet(this, _selectedColorId)).color;
	      main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _icon), {
	        backgroundColor: backgroundColor
	      });
	      if (!babelHelpers.classPrivateFieldGet(this, _container)) {
	        return;
	      }
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(this, _container).querySelector('.--selected'), '--selected');
	      var target = babelHelpers.classPrivateFieldGet(this, _container).querySelector("#crm-field-color-selector-menu-item-".concat(id));
	      if (target) {
	        main_core.Dom.addClass(target, '--selected');
	      }
	    }
	  }, {
	    key: "onKeyUpHandler",
	    value: function onKeyUpHandler(event) {
	      if (event.keyCode === 13) {
	        var _babelHelpers$classPr;
	        (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _popup)) === null || _babelHelpers$classPr === void 0 ? void 0 : _babelHelpers$classPr.close();
	      }
	    }
	  }]);
	  return ColorSelector;
	}();
	function _create2() {
	  var _this = this;
	  if (!babelHelpers.classPrivateFieldGet(this, _target)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldSet(this, _icon, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div \n\t\t\t\tclass=\"crm-field-color-selector ", "\"\n\t\t\t></div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _readOnlyMode) ? '--readonly' : ''));
	  main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _icon), babelHelpers.classPrivateFieldGet(this, _target));
	  var background = _classPrivateMethodGet(this, _getColorById, _getColorById2).call(this, babelHelpers.classPrivateFieldGet(this, _selectedColorId)).color;
	  main_core.Dom.style(babelHelpers.classPrivateFieldGet(this, _icon), {
	    '--crm-field-color-selector-color': background
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _icon), 'click', function (event) {
	    event.preventDefault();
	    _this.togglePopup();
	  });
	}
	function _getColorById2(id) {
	  return babelHelpers.classPrivateFieldGet(this, _colorList).find(function (item) {
	    return item.id === id;
	  });
	}
	function _getPopup2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _popup)) {
	    babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup({
	      id: "crm-todo-color-selector-popup-".concat(main_core.Text.getRandom()),
	      autoHide: true,
	      bindElement: babelHelpers.classPrivateFieldGet(this, _target),
	      content: _classPrivateMethodGet(this, _getContent, _getContent2).call(this),
	      closeByEsc: true,
	      closeIcon: false,
	      draggable: false,
	      width: 188,
	      padding: 0,
	      angle: true,
	      offsetLeft: 6,
	      offsetTop: 14
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _popup);
	}
	function _getContent2() {
	  var _this2 = this;
	  babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-field-color-selector-menu-container\"></div>"]))));
	  babelHelpers.classPrivateFieldGet(this, _colorList).forEach(function (item) {
	    var id = main_core.Text.encode("crm-field-color-selector-menu-item-".concat(item.id));
	    var element = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tid=\"", "\"\n\t\t\t\t\tclass=\"crm-field-color-selector-menu-item ", "\"\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t>\n\t\t\t\t</span>\n\t\t\t"])), id, item.id === babelHelpers.classPrivateFieldGet(_this2, _selectedColorId) ? '--selected' : '', _this2.onSelectColor.bind(_this2, item.id));
	    main_core.Dom.append(element, babelHelpers.classPrivateFieldGet(_this2, _container));
	    main_core.Dom.style(element, {
	      '--crm-field-color-selector-color': item.color
	    });
	  });
	  return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-field-color-selector-popup\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"])), babelHelpers.classPrivateFieldGet(this, _container));
	}

	exports.ColorSelectorEvents = ColorSelectorEvents;
	exports.ColorSelector = ColorSelector;

}((this.BX.Crm.Field = this.BX.Crm.Field || {}),BX,BX,BX.Event,BX.Main));
//# sourceMappingURL=color-selector.bundle.js.map
