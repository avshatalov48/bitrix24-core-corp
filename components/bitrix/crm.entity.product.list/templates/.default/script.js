this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Entity = this.BX.Crm.Entity || {};
(function (exports,ui_hint,ui_notification,catalog_storeSelector,catalog_productCalculator,main_popup,main_core,main_core_events,catalog_storeUse,currency_currencyCore,catalog_productSelector,catalog_productModel) {
	'use strict';

	var _templateObject;

	var HintPopup = /*#__PURE__*/function () {
	  function HintPopup(editor) {
	    babelHelpers.classCallCheck(this, HintPopup);
	    this.editor = editor;
	  }

	  babelHelpers.createClass(HintPopup, [{
	    key: "load",
	    value: function load(node, text) {
	      if (!this.hintPopup) {
	        this.hintPopup = new main_popup.Popup('ui-hint-popup-' + this.editor.getId(), null, {
	          darkMode: true,
	          closeIcon: true,
	          animation: 'fading-slide'
	        });
	      }

	      this.hintPopup.setBindElement(node);
	      this.hintPopup.adjustPosition();
	      this.hintPopup.setContent(main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class='ui-hint-content'>", "</div>\n\t\t"])), main_core.Text.encode(text)));
	      return this.hintPopup;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (this.hintPopup) {
	        this.hintPopup.show();
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      if (this.hintPopup) {
	        this.hintPopup.close();
	      }
	    }
	  }]);
	  return HintPopup;
	}();

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6;

	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _model = /*#__PURE__*/new WeakMap();

	var _cache = /*#__PURE__*/new WeakMap();

	var _onDateInputClick = /*#__PURE__*/new WeakSet();

	var _getDateNode = /*#__PURE__*/new WeakSet();

	var _getReserveInputNode = /*#__PURE__*/new WeakSet();

	var _layoutDateReservation = /*#__PURE__*/new WeakSet();

	var ReserveControl = /*#__PURE__*/function () {
	  function ReserveControl(options) {
	    babelHelpers.classCallCheck(this, ReserveControl);

	    _classPrivateMethodInitSpec(this, _layoutDateReservation);

	    _classPrivateMethodInitSpec(this, _getReserveInputNode);

	    _classPrivateMethodInitSpec(this, _getDateNode);

	    _classPrivateMethodInitSpec(this, _onDateInputClick);

	    _classPrivateFieldInitSpec(this, _model, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });

	    babelHelpers.classPrivateFieldSet(this, _model, options.model);
	    this.inputFieldName = options.inputName || ReserveControl.INPUT_NAME;
	    this.dateFieldName = options.dateFieldName || ReserveControl.DATE_NAME;
	    this.quantityFieldName = options.quantityFieldName || ReserveControl.QUANTITY_NAME;
	    this.defaultDateReservation = options.defaultDateReservation || null;
	    this.isInputDisabled = options.isInputDisabled || false;
	  }

	  babelHelpers.createClass(ReserveControl, [{
	    key: "renderTo",
	    value: function renderTo(node) {
	      node.appendChild(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), _classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this)));
	      main_core.Event.bind(_classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this).querySelector('input'), 'input', main_core.Runtime.debounce(this.onReserveInputChange, 800, this));

	      if (this.getReservedQuantity() > 0) {
	        _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, this.getDateReservation());
	      }

	      node.appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["", ""])), _classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this)));
	      main_core.Event.bind(_classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this), 'click', _classPrivateMethodGet(this, _onDateInputClick, _onDateInputClick2).bind(this));
	      main_core.Event.bind(_classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this).querySelector('input'), 'change', this.onDateChange.bind(this));
	    }
	  }, {
	    key: "getReservedQuantity",
	    value: function getReservedQuantity() {
	      return main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _model).getField(this.inputFieldName));
	    }
	  }, {
	    key: "getDateReservation",
	    value: function getDateReservation() {
	      return babelHelpers.classPrivateFieldGet(this, _model).getField(this.dateFieldName) || null;
	    }
	  }, {
	    key: "getQuantity",
	    value: function getQuantity() {
	      return main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _model).getField(this.quantityFieldName));
	    }
	  }, {
	    key: "onReserveInputChange",
	    value: function onReserveInputChange(event) {
	      var value = main_core.Text.toNumber(event.target.value);
	      this.changeInputValue(value);
	    }
	  }, {
	    key: "changeInputValue",
	    value: function changeInputValue(value) {
	      if (value > this.getQuantity()) {
	        var errorNotifyId = 'reserveCountError';
	        var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);

	        if (!notify) {
	          var notificationOptions = {
	            id: errorNotifyId,
	            closeButton: true,
	            autoHideDelay: 3000,
	            content: main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_THEN_RESERVED'))
	          };
	          notify = BX.UI.Notification.Center.notify(notificationOptions);
	        }

	        notify.show();

	        var input = _classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this).querySelector('input');

	        value = this.getQuantity();
	        input.value = value;
	      }

	      if (value > 0) {
	        if (this.getDateReservation() === null) {
	          this.changeDateReservation(this.defaultDateReservation);
	        } else {
	          _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, babelHelpers.classPrivateFieldGet(this, _model).getField(this.dateFieldName));
	        }
	      } else if (value <= 0) {
	        this.changeDateReservation();
	      }

	      babelHelpers.classPrivateFieldGet(this, _model).setField(this.inputFieldName, value);
	      main_core_events.EventEmitter.emit(this, 'onChange', {
	        NAME: this.inputFieldName,
	        VALUE: value
	      });
	    }
	  }, {
	    key: "onDateChange",
	    value: function onDateChange(event) {
	      var value = event.target.value;
	      var newDate = BX.parseDate(value);
	      var current = new Date();
	      current.setHours(0, 0, 0, 0);

	      if (newDate >= current) {
	        this.changeDateReservation(value);
	      } else {
	        var errorNotifyId = 'reserveDateError';
	        var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);

	        if (!notify) {
	          var notificationOptions = {
	            id: errorNotifyId,
	            closeButton: true,
	            autoHideDelay: 3000,
	            content: main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_DATE_IN_PAST'))
	          };
	          notify = BX.UI.Notification.Center.notify(notificationOptions);
	        }

	        notify.show();
	        this.changeDateReservation(this.defaultDateReservation);
	      }
	    }
	  }, {
	    key: "changeDateReservation",
	    value: function changeDateReservation() {
	      var date = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      main_core_events.EventEmitter.emit(this, 'onChange', {
	        NAME: this.dateFieldName,
	        VALUE: date
	      });
	      babelHelpers.classPrivateFieldGet(this, _model).setField(this.dateFieldName, date);

	      _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, date);
	    }
	  }]);
	  return ReserveControl;
	}();

	function _onDateInputClick2(event) {
	  BX.calendar({
	    node: event.target,
	    field: event.target.parentNode.querySelector('input'),
	    bTime: false
	  });
	}

	function _getDateNode2() {
	  var _this = this;

	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('dateInput', function () {
	    return main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<a class=\"crm-entity-product-list-reserve-date\"></a>\n\t\t\t\t\t<input \n\t\t\t\t\t\tdata-name=\"", "\" \n\t\t\t\t\t\tname=\"", "\" \n\t\t\t\t\t\ttype=\"hidden\" \n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t"])), _this.dateFieldName, _this.dateFieldName, _this.getDateReservation());
	  });
	}

	function _getReserveInputNode2() {
	  var _this2 = this;

	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('reserveInput', function () {
	    var tag = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<input type=\"text\" \n\t\t\t\t\t\tdata-name=\"", "\"\n\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox ", "\" \n\t\t\t\t\t\tautoComplete=\"off\" \n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\tplaceholder=\"0\" \n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t", "\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t"])), _this2.inputFieldName, _this2.inputFieldName, _this2.isInputDisabled ? "crm-entity-product-list-locked-field" : "", _this2.getReservedQuantity(), _this2.getReservedQuantity(), _this2.isInputDisabled ? "disabled" : "");

	    if (_this2.isInputDisabled) {
	      tag.onclick = function () {
	        return top.BX.UI.InfoHelper.show('limit_store_crm_integration');
	      };
	    }

	    return tag;
	  });
	}

	function _layoutDateReservation2() {
	  var date = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	  var linkText = date === null ? '' : main_core.Loc.getMessage('CRM_ENTITY_PL_RESERVED_DATE', {
	    '#FINAL_RESERVATION_DATE#': date
	  });

	  var link = _classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this).querySelector('a');

	  if (link) {
	    link.innerText = linkText;
	  }

	  var hiddenInput = _classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this).querySelector('input');

	  if (hiddenInput) {
	    hiddenInput.value = date;
	  }
	}

	babelHelpers.defineProperty(ReserveControl, "INPUT_NAME", 'RESERVE_QUANTITY');
	babelHelpers.defineProperty(ReserveControl, "DATE_NAME", 'DATE_RESERVE_END');
	babelHelpers.defineProperty(ReserveControl, "QUANTITY_NAME", 'QUANTITY');

	var _templateObject$2, _templateObject2$1, _templateObject3$1, _templateObject4$1, _templateObject5$1, _templateObject6$1, _templateObject7, _templateObject8;

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var MODE_EDIT = 'EDIT';
	var MODE_SET = 'SET';

	var _initActions = /*#__PURE__*/new WeakSet();

	var _showChangePriceNotify = /*#__PURE__*/new WeakSet();

	var _isEditableCatalogPrice = /*#__PURE__*/new WeakSet();

	var _isSaveableCatalogPrice = /*#__PURE__*/new WeakSet();

	var _initSelector = /*#__PURE__*/new WeakSet();

	var _initStoreSelector = /*#__PURE__*/new WeakSet();

	var _applyStoreSelectorRestrictionTweaks = /*#__PURE__*/new WeakSet();

	var _initReservedControl = /*#__PURE__*/new WeakSet();

	var _onStoreFieldChange = /*#__PURE__*/new WeakSet();

	var _onStoreFieldClear = /*#__PURE__*/new WeakSet();

	var _showPriceNotifier = /*#__PURE__*/new WeakSet();

	var _onChangeStoreData = /*#__PURE__*/new WeakSet();

	var _handleProductErrorsChange = /*#__PURE__*/new WeakSet();

	var _shouldShowSmallPriceHint = /*#__PURE__*/new WeakSet();

	var _togglePriceHintPopup = /*#__PURE__*/new WeakSet();

	var Row = /*#__PURE__*/function () {
	  function Row(_id, fields, settings, editor) {
	    babelHelpers.classCallCheck(this, Row);

	    _classPrivateMethodInitSpec$1(this, _togglePriceHintPopup);

	    _classPrivateMethodInitSpec$1(this, _shouldShowSmallPriceHint);

	    _classPrivateMethodInitSpec$1(this, _handleProductErrorsChange);

	    _classPrivateMethodInitSpec$1(this, _onChangeStoreData);

	    _classPrivateMethodInitSpec$1(this, _showPriceNotifier);

	    _classPrivateMethodInitSpec$1(this, _onStoreFieldClear);

	    _classPrivateMethodInitSpec$1(this, _onStoreFieldChange);

	    _classPrivateMethodInitSpec$1(this, _initReservedControl);

	    _classPrivateMethodInitSpec$1(this, _applyStoreSelectorRestrictionTweaks);

	    _classPrivateMethodInitSpec$1(this, _initStoreSelector);

	    _classPrivateMethodInitSpec$1(this, _initSelector);

	    _classPrivateMethodInitSpec$1(this, _isSaveableCatalogPrice);

	    _classPrivateMethodInitSpec$1(this, _isEditableCatalogPrice);

	    _classPrivateMethodInitSpec$1(this, _showChangePriceNotify);

	    _classPrivateMethodInitSpec$1(this, _initActions);

	    babelHelpers.defineProperty(this, "fields", {});
	    babelHelpers.defineProperty(this, "externalActions", []);
	    babelHelpers.defineProperty(this, "onFocusUnchangeablePrice", _classPrivateMethodGet$1(this, _showChangePriceNotify, _showChangePriceNotify2).bind(this));
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "modeChanges", {
	      EDIT: MODE_EDIT,
	      SET: MODE_SET
	    });
	    this.setId(_id);
	    this.setSettings(settings);
	    this.setEditor(editor);
	    this.setModel(fields, settings);
	    this.setFields(fields);

	    _classPrivateMethodGet$1(this, _initActions, _initActions2).call(this);

	    _classPrivateMethodGet$1(this, _initSelector, _initSelector2).call(this);

	    _classPrivateMethodGet$1(this, _initStoreSelector, _initStoreSelector2).call(this);

	    _classPrivateMethodGet$1(this, _initReservedControl, _initReservedControl2).call(this);

	    this.modifyBasePriceInput();
	    this.refreshFieldsLayout();
	    requestAnimationFrame(this.initHandlers.bind(this));
	  }

	  babelHelpers.createClass(Row, [{
	    key: "getNode",
	    value: function getNode() {
	      var _this = this;

	      return this.cache.remember('node', function () {
	        var rowId = _this.getField('ID', 0);

	        return _this.getEditorContainer().querySelector('[data-id="' + rowId + '"]');
	      });
	    }
	  }, {
	    key: "getSelector",
	    value: function getSelector() {
	      return this.mainSelector;
	    }
	  }, {
	    key: "clearChanges",
	    value: function clearChanges() {
	      this.getModel().clearChangedList();
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = id;
	    }
	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      return this.settings;
	    }
	  }, {
	    key: "setSettings",
	    value: function setSettings(settings) {
	      this.settings = main_core.Type.isPlainObject(settings) ? settings : {};
	    }
	  }, {
	    key: "getSettingValue",
	    value: function getSettingValue(name, defaultValue) {
	      return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	    }
	  }, {
	    key: "setSettingValue",
	    value: function setSettingValue(name, value) {
	      this.settings[name] = value;
	    }
	  }, {
	    key: "setEditor",
	    value: function setEditor(editor) {
	      this.editor = editor;
	    }
	  }, {
	    key: "getEditor",
	    value: function getEditor() {
	      return this.editor;
	    }
	  }, {
	    key: "getEditorContainer",
	    value: function getEditorContainer() {
	      return this.getEditor().getContainer();
	    }
	  }, {
	    key: "getHintPopup",
	    value: function getHintPopup() {
	      return this.getEditor().getHintPopup();
	    }
	  }, {
	    key: "initHandlers",
	    value: function initHandlers() {
	      var editor = this.getEditor();
	      this.getNode().querySelectorAll('input').forEach(function (node) {
	        main_core.Event.bind(node, 'input', editor.changeProductFieldHandler);
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for text fields

	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	      this.getNode().querySelectorAll('select').forEach(function (node) {
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for select fields

	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	    }
	  }, {
	    key: "initHandlersForSelectors",
	    value: function initHandlersForSelectors() {
	      var _this2 = this;

	      var editor = this.getEditor();
	      var selectorNames = ['MAIN_INFO', 'STORE_INFO'];
	      selectorNames.forEach(function (name) {
	        _this2.getNode().querySelectorAll('[data-name="' + name + '"] input[type="text"]').forEach(function (node) {
	          main_core.Event.bind(node, 'input', editor.changeProductFieldHandler);
	          main_core.Event.bind(node, 'change', editor.changeProductFieldHandler); // disable drag-n-drop events for select fields

	          main_core.Event.bind(node, 'mousedown', function (event) {
	            return event.stopPropagation();
	          });
	        });
	      });
	    }
	  }, {
	    key: "modifyBasePriceInput",
	    value: function modifyBasePriceInput() {
	      var priceNode = this.getNode().querySelector('[data-name="PRICE"]');

	      if (!priceNode) {
	        return;
	      }

	      if (!_classPrivateMethodGet$1(this, _isEditableCatalogPrice, _isEditableCatalogPrice2).call(this)) {
	        var _priceNode$querySelec;

	        priceNode.setAttribute('disabled', true);
	        main_core.Dom.addClass(priceNode, 'ui-ctl-element');
	        (_priceNode$querySelec = priceNode.querySelector('.main-grid-editor-money-price')) === null || _priceNode$querySelec === void 0 ? void 0 : _priceNode$querySelec.setAttribute('disabled', 'true');

	        if (!this.editor.getSettingValue('disableNotifyChangingPrice')) {
	          main_core.Event.bind(priceNode, 'mouseenter', this.onFocusUnchangeablePrice);
	        }
	      } else {
	        var _priceNode$querySelec2;

	        priceNode.removeAttribute('disabled');
	        main_core.Dom.removeClass(priceNode, 'ui-ctl-element');
	        (_priceNode$querySelec2 = priceNode.querySelector('.main-grid-editor-money-price')) === null || _priceNode$querySelec2 === void 0 ? void 0 : _priceNode$querySelec2.removeAttribute('disabled');
	        main_core.Event.unbind(priceNode, 'mouseenter', this.onFocusUnchangeablePrice);
	      }
	    }
	  }, {
	    key: "setRowNumber",
	    value: function setRowNumber(number) {
	      this.getNode().querySelectorAll('.main-grid-row-number').forEach(function (node) {
	        node.textContent = number + '.';
	      });
	    }
	  }, {
	    key: "getFields",
	    value: function getFields() {
	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	      var result;

	      if (!main_core.Type.isArrayFilled(fields)) {
	        result = main_core.Runtime.clone(this.fields);
	      } else {
	        result = {};

	        var _iterator = _createForOfIteratorHelper(fields),
	            _step;

	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var fieldName = _step.value;
	            result[fieldName] = this.getField(fieldName);
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      }

	      if ('PRODUCT_NAME' in result) {
	        var fixedProductName = this.getField('FIXED_PRODUCT_NAME', '');

	        if (main_core.Type.isStringFilled(fixedProductName)) {
	          result['PRODUCT_NAME'] = fixedProductName;
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "getCatalogFields",
	    value: function getCatalogFields() {
	      var fields = this.getFields(['CURRENCY', 'QUANTITY', 'MEASURE_CODE']);
	      fields['PRICE'] = this.getBasePrice();
	      fields['VAT_INCLUDED'] = this.getTaxIncluded();
	      fields['VAT_ID'] = this.getTaxId();
	      return fields;
	    }
	  }, {
	    key: "getCalculateFields",
	    value: function getCalculateFields() {
	      return {
	        'PRICE': this.getPrice(),
	        'BASE_PRICE': this.getBasePrice(),
	        'PRICE_EXCLUSIVE': this.getPriceExclusive(),
	        'PRICE_NETTO': this.getPriceNetto(),
	        'PRICE_BRUTTO': this.getPriceBrutto(),
	        'QUANTITY': this.getQuantity(),
	        'DISCOUNT_TYPE_ID': this.getDiscountType(),
	        'DISCOUNT_RATE': this.getDiscountRate(),
	        'DISCOUNT_SUM': this.getDiscountSum(),
	        'DISCOUNT_ROW': this.getDiscountRow(),
	        'TAX_INCLUDED': this.getTaxIncluded(),
	        'TAX_RATE': this.getTaxRate()
	      };
	    }
	  }, {
	    key: "setFields",
	    value: function setFields(fields) {
	      for (var name in fields) {
	        if (fields.hasOwnProperty(name)) {
	          this.setField(name, fields[name]);
	        }
	      }
	    }
	  }, {
	    key: "getField",
	    value: function getField(name, defaultValue) {
	      return this.fields.hasOwnProperty(name) ? this.fields[name] : defaultValue;
	    }
	  }, {
	    key: "setField",
	    value: function setField(name, value) {
	      var changeModel = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      this.fields[name] = value;

	      if (changeModel) {
	        this.getModel().setField(name, value);
	      }
	    }
	  }, {
	    key: "getUiFieldId",
	    value: function getUiFieldId(field) {
	      return this.getId() + '_' + field;
	    }
	  }, {
	    key: "getBasePrice",
	    value: function getBasePrice() {
	      return this.getField('BASE_PRICE', 0);
	    }
	  }, {
	    key: "getEnteredPrice",
	    value: function getEnteredPrice() {
	      return this.getField('ENTERED_PRICE', this.getBasePrice());
	    }
	  }, {
	    key: "getCatalogPrice",
	    value: function getCatalogPrice() {
	      return this.getField('CATALOG_PRICE', this.getBasePrice());
	    }
	  }, {
	    key: "isPriceNetto",
	    value: function isPriceNetto() {
	      return this.getEditor().isTaxAllowed() && !this.isTaxIncluded();
	    }
	  }, {
	    key: "getPrice",
	    value: function getPrice() {
	      return this.getField('PRICE', 0);
	    }
	  }, {
	    key: "getPriceExclusive",
	    value: function getPriceExclusive() {
	      return this.getField('PRICE_EXCLUSIVE', 0);
	    }
	  }, {
	    key: "getPriceNetto",
	    value: function getPriceNetto() {
	      return this.getField('PRICE_NETTO', 0);
	    }
	  }, {
	    key: "getPriceBrutto",
	    value: function getPriceBrutto() {
	      return this.getField('PRICE_BRUTTO', 0);
	    }
	  }, {
	    key: "getQuantity",
	    value: function getQuantity() {
	      return this.getField('QUANTITY', 1);
	    }
	  }, {
	    key: "getDiscountType",
	    value: function getDiscountType() {
	      return this.getField('DISCOUNT_TYPE_ID', catalog_productCalculator.DiscountType.UNDEFINED);
	    }
	  }, {
	    key: "isDiscountUndefined",
	    value: function isDiscountUndefined() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.UNDEFINED;
	    }
	  }, {
	    key: "isDiscountPercentage",
	    value: function isDiscountPercentage() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.PERCENTAGE;
	    }
	  }, {
	    key: "isDiscountMonetary",
	    value: function isDiscountMonetary() {
	      return this.getDiscountType() === catalog_productCalculator.DiscountType.MONETARY;
	    }
	  }, {
	    key: "isDiscountHandmade",
	    value: function isDiscountHandmade() {
	      return this.isDiscountPercentage() || this.isDiscountMonetary();
	    }
	  }, {
	    key: "getDiscountRate",
	    value: function getDiscountRate() {
	      return this.getField('DISCOUNT_RATE', 0);
	    }
	  }, {
	    key: "getDiscountSum",
	    value: function getDiscountSum() {
	      return this.getField('DISCOUNT_SUM', 0);
	    }
	  }, {
	    key: "getDiscountRow",
	    value: function getDiscountRow() {
	      return this.getField('DISCOUNT_ROW', 0);
	    }
	  }, {
	    key: "isEmptyDiscount",
	    value: function isEmptyDiscount() {
	      if (this.isDiscountPercentage()) {
	        return this.getDiscountRate() === 0;
	      } else if (this.isDiscountMonetary()) {
	        return this.getDiscountSum() === 0;
	      } else if (this.isDiscountUndefined()) {
	        return true;
	      }

	      return false;
	    }
	  }, {
	    key: "getTaxIncluded",
	    value: function getTaxIncluded() {
	      return this.getField('TAX_INCLUDED', 'N');
	    }
	  }, {
	    key: "isTaxIncluded",
	    value: function isTaxIncluded() {
	      return this.getTaxIncluded() === 'Y';
	    }
	  }, {
	    key: "getTaxRate",
	    value: function getTaxRate() {
	      return this.getField('TAX_RATE', 0);
	    }
	  }, {
	    key: "getTaxSum",
	    value: function getTaxSum() {
	      return this.isTaxIncluded() ? this.getPrice() * this.getQuantity() * (1 - 1 / (1 + this.getTaxRate() / 100)) : this.getPriceExclusive() * this.getQuantity() * this.getTaxRate() / 100;
	    }
	  }, {
	    key: "getTaxNode",
	    value: function getTaxNode() {
	      return this.getNode().querySelector('select[data-field-code="TAX_RATE"]');
	    }
	  }, {
	    key: "getTaxId",
	    value: function getTaxId() {
	      var taxNode = this.getTaxNode();

	      if (main_core.Type.isDomNode(taxNode) && taxNode.options[taxNode.selectedIndex]) {
	        return main_core.Text.toNumber(taxNode.options[taxNode.selectedIndex].getAttribute('data-tax-id'));
	      }

	      return 0;
	    }
	  }, {
	    key: "updateFieldByEvent",
	    value: function updateFieldByEvent(fieldCode, event) {
	      var target = event.target;
	      var value = target.type === 'checkbox' ? target.checked : target.value;
	      var mode = event.type === 'input' ? MODE_EDIT : MODE_SET;
	      this.updateField(fieldCode, value, mode);
	    }
	  }, {
	    key: "updateField",
	    value: function updateField(fieldCode, value) {
	      var mode = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : MODE_SET;
	      this.resetExternalActions();
	      this.updateFieldValue(fieldCode, value, mode);
	      this.executeExternalActions();
	    }
	  }, {
	    key: "updateFieldValue",
	    value: function updateFieldValue(code, value) {
	      var mode = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : MODE_SET;

	      switch (code) {
	        case 'ID':
	        case 'OFFER_ID':
	          this.changeProductId(value);
	          break;

	        case 'ENTERED_PRICE':
	        case 'PRICE':
	          this.changeEnteredPrice(value, mode);
	          break;

	        case 'CATALOG_PRICE':
	          this.changeCatalogPrice(value, mode);
	          break;

	        case 'QUANTITY':
	          this.changeQuantity(value, mode);
	          break;

	        case 'MEASURE_CODE':
	          this.changeMeasureCode(value, mode);
	          break;

	        case 'DISCOUNT':
	        case 'DISCOUNT_PRICE':
	          this.changeDiscount(value, mode);
	          break;

	        case 'DISCOUNT_TYPE_ID':
	          this.changeDiscountType(value);
	          break;

	        case 'DISCOUNT_ROW':
	          this.changeRowDiscount(value, mode);
	          break;

	        case 'VAT_ID':
	        case 'TAX_ID':
	          this.changeTaxId(value);
	          break;

	        case 'TAX_RATE':
	          this.changeTaxRate(value);
	          break;

	        case 'VAT_INCLUDED':
	        case 'TAX_INCLUDED':
	          this.changeTaxIncluded(value);
	          break;

	        case 'SUM':
	          this.changeRowSum(value, mode);
	          break;

	        case 'NAME':
	        case 'PRODUCT_NAME':
	        case 'MAIN_INFO':
	          this.changeProductName(value);
	          break;

	        case 'SORT':
	          this.changeSort(value, mode);
	          break;

	        case 'STORE_ID':
	          this.changeStore(value);
	          break;

	        case 'STORE_TITLE':
	          this.changeStoreName(value);
	          break;

	        case 'RESERVE_QUANTITY':
	          this.changeReserveQuantity(value);
	          break;

	        case 'DATE_RESERVE_END':
	          this.changeDateReserveEnd(value);
	          break;

	        case 'BASE_PRICE':
	          this.setBasePrice(value);
	          break;

	        case 'SKU_TREE':
	        case 'DETAIL_URL':
	        case 'IMAGE_INFO':
	        case 'COMMON_STORE_RESERVED':
	        case 'COMMON_STORE_AMOUNT':
	          this.setField(code, value);
	          break;
	      }
	    }
	  }, {
	    key: "updateFieldByName",
	    value: function updateFieldByName(field, value) {
	      switch (field) {
	        case 'TAX_INCLUDED':
	          this.setTaxIncluded(value);
	          break;
	      }
	    }
	  }, {
	    key: "handleCopyAction",
	    value: function handleCopyAction(event, menuItem) {
	      var _this$getEditor;

	      (_this$getEditor = this.getEditor()) === null || _this$getEditor === void 0 ? void 0 : _this$getEditor.copyRow(this);
	      var menu = menuItem.getMenuWindow();

	      if (menu) {
	        menu.destroy();
	      }
	    }
	  }, {
	    key: "handleDeleteAction",
	    value: function handleDeleteAction(event, menuItem) {
	      var _this$getEditor2;

	      (_this$getEditor2 = this.getEditor()) === null || _this$getEditor2 === void 0 ? void 0 : _this$getEditor2.deleteRow(this.getField('ID'));
	      var menu = menuItem.getMenuWindow();

	      if (menu) {
	        menu.destroy();
	      }
	    }
	  }, {
	    key: "changeProductId",
	    value: function changeProductId(value) {
	      var preparedValue = this.parseInt(value);
	      this.setProductId(preparedValue);
	    }
	  }, {
	    key: "changeEnteredPrice",
	    value: function changeEnteredPrice(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var originalPrice = value; // price can't be less than zero

	      value = Math.max(value, 0);
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setField('ENTERED_PRICE', preparedValue);

	      if (mode === MODE_EDIT && originalPrice >= 0) {
	        if (!_classPrivateMethodGet$1(this, _isEditableCatalogPrice, _isEditableCatalogPrice2)) {
	          return;
	        }

	        if (this.getModel().isCatalogExisted() && _classPrivateMethodGet$1(this, _isSaveableCatalogPrice, _isSaveableCatalogPrice2).call(this)) {
	          _classPrivateMethodGet$1(this, _showPriceNotifier, _showPriceNotifier2).call(this, preparedValue);
	        } else {
	          this.setBasePrice(preparedValue, mode);
	        }

	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	        this.addActionDisableSaveButton();
	      } else {
	        this.refreshFieldsLayout();
	      }

	      _classPrivateMethodGet$1(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this, originalPrice < 0 && originalPrice !== value);
	    }
	  }, {
	    key: "changeCatalogPrice",
	    value: function changeCatalogPrice(value) {
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setField('CATALOG_PRICE', preparedValue);
	      this.refreshFieldsLayout();
	    }
	  }, {
	    key: "changeQuantity",
	    value: function changeQuantity(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getQuantityPrecision());
	      this.setQuantity(preparedValue, mode);
	    }
	  }, {
	    key: "changeMeasureCode",
	    value: function changeMeasureCode(value) {
	      var _this3 = this;

	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      this.getEditor().getMeasures().filter(function (item) {
	        return item.CODE === value;
	      }).forEach(function (item) {
	        return _this3.setMeasure(item, mode);
	      });
	    }
	  }, {
	    key: "changeDiscount",
	    value: function changeDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue;

	      if (this.isDiscountPercentage()) {
	        preparedValue = this.parseFloat(value, this.getCommonPrecision());
	      } else {
	        preparedValue = this.parseFloat(value, this.getPricePrecision()).toFixed(this.getPricePrecision());
	      }

	      this.setDiscount(preparedValue, mode);
	    }
	  }, {
	    key: "changeDiscountType",
	    value: function changeDiscountType(value) {
	      var preparedValue = this.parseInt(value, catalog_productCalculator.DiscountType.UNDEFINED);
	      this.setDiscountType(preparedValue);
	    }
	  }, {
	    key: "changeRowDiscount",
	    value: function changeRowDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setRowDiscount(preparedValue, mode);
	    }
	  }, {
	    key: "changeTaxId",
	    value: function changeTaxId(value) {
	      var taxList = this.getEditor().getTaxList();

	      if (main_core.Type.isArrayFilled(taxList)) {
	        var taxRate = taxList.find(function (item) {
	          return parseInt(item.ID) === parseInt(value);
	        });

	        if (taxRate) {
	          this.changeTaxRate(this.parseFloat(taxRate.VALUE));
	        }
	      }
	    }
	  }, {
	    key: "changeTaxRate",
	    value: function changeTaxRate(value) {
	      var preparedValue = this.parseFloat(value, this.getCommonPrecision());
	      this.setTaxRate(preparedValue);
	    }
	  }, {
	    key: "changeTaxIncluded",
	    value: function changeTaxIncluded(value) {
	      if (main_core.Type.isBoolean(value)) {
	        value = value ? 'Y' : 'N';
	      }

	      this.setTaxIncluded(value);
	    }
	  }, {
	    key: "changeRowSum",
	    value: function changeRowSum(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseFloat(value, this.getPricePrecision());
	      this.setRowSum(preparedValue, mode);
	    }
	  }, {
	    key: "changeProductName",
	    value: function changeProductName(value) {
	      var preparedValue = value.toString();
	      var isChangedValue = this.getField('PRODUCT_NAME') !== preparedValue;

	      if (isChangedValue) {
	        this.setField('PRODUCT_NAME', preparedValue);
	        this.setField('NAME', preparedValue);
	        this.addActionProductChange();
	      }
	    }
	  }, {
	    key: "changeSort",
	    value: function changeSort(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var preparedValue = this.parseInt(value);

	      if (mode === MODE_SET) {
	        this.setField('SORT', preparedValue);
	      }

	      var isChangedValue = this.getField('SORT') !== preparedValue;

	      if (isChangedValue) {
	        this.addActionProductChange();
	      }
	    }
	  }, {
	    key: "changeStore",
	    value: function changeStore(value) {
	      if (this.isReserveBlocked()) {
	        return;
	      }

	      var preparedValue = main_core.Text.toNumber(value);

	      if (this.getField('STORE_ID') === preparedValue) {
	        return;
	      }

	      this.setField('STORE_ID', preparedValue);
	      this.updateUiStoreAmountData();
	      this.addActionProductChange();
	    }
	  }, {
	    key: "updateUiStoreAmountData",
	    value: function updateUiStoreAmountData() {
	      var _this$editor$getDefau;

	      var storeId = this.getField('STORE_ID');
	      var reserved = this.model.getStoreCollection().getStoreReserved(storeId);
	      var available = this.model.getStoreCollection().getStoreAvailableAmount(storeId);
	      var measureName = main_core.Type.isStringFilled(this.model.getField('MEASURE_NAME')) ? this.model.getField('MEASURE_NAME') : ((_this$editor$getDefau = this.editor.getDefaultMeasure()) === null || _this$editor$getDefau === void 0 ? void 0 : _this$editor$getDefau.SYMBOL) || '';
	      var amountWrapper = this.getNode().querySelector('[data-name="STORE_RESERVED"]');

	      if (amountWrapper) {
	        amountWrapper.innerHTML = main_core.Text.toNumber(reserved) + ' ' + main_core.Text.encode(measureName);
	      }

	      var availableWrapper = this.getNode().querySelector('[data-name="STORE_AVAILABLE"]');

	      if (availableWrapper) {
	        availableWrapper.innerHTML = main_core.Text.toNumber(available) + ' ' + main_core.Text.encode(measureName);
	      }
	    }
	  }, {
	    key: "changeStoreName",
	    value: function changeStoreName(value) {
	      var preparedValue = value.toString();
	      this.setField('STORE_TITLE', preparedValue);
	      this.addActionProductChange();
	    }
	  }, {
	    key: "changeDateReserveEnd",
	    value: function changeDateReserveEnd(value) {
	      var preparedValue = main_core.Type.isNil(value) ? '' : value.toString();
	      this.setField('DATE_RESERVE_END', preparedValue);
	      this.addActionProductChange();
	    }
	  }, {
	    key: "changeReserveQuantity",
	    value: function changeReserveQuantity(value) {
	      var preparedValue = main_core.Text.toNumber(value);
	      this.setField('RESERVE_QUANTITY', preparedValue);
	      this.addActionProductChange();
	    }
	  }, {
	    key: "refreshFieldsLayout",
	    value: function refreshFieldsLayout() {
	      var exceptFields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];

	      for (var field in this.fields) {
	        if (this.fields.hasOwnProperty(field) && !exceptFields.includes(field)) {
	          this.updateUiField(field, this.fields[field]);
	        }
	      }
	    }
	  }, {
	    key: "getCalculator",
	    value: function getCalculator() {
	      return this.getModel().getCalculator().setFields(this.getCalculateFields()).setSettings(this.getEditor().getSettings());
	    }
	  }, {
	    key: "setModel",
	    value: function setModel() {
	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var settings = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var selectorId = settings.selectorId;

	      if (selectorId) {
	        var model = catalog_productModel.ProductModel.getById(selectorId);

	        if (model) {
	          this.model = model;
	        }
	      }

	      if (!this.model) {
	        this.model = new catalog_productModel.ProductModel({
	          id: selectorId,
	          currency: this.getEditor().getCurrencyId(),
	          iblockId: fields['IBLOCK_ID'],
	          basePriceId: fields['BASE_PRICE_ID'],
	          skuTree: main_core.Type.isStringFilled(fields['SKU_TREE']) ? JSON.parse(fields['SKU_TREE']) : null,
	          fields: fields
	        });
	        var imageInfo = main_core.Type.isStringFilled(fields['IMAGE_INFO']) ? JSON.parse(fields['IMAGE_INFO']) : null;

	        if (main_core.Type.isObject(imageInfo)) {
	          this.model.getImageCollection().setPreview(imageInfo['preview']);
	          this.model.getImageCollection().setEditInput(imageInfo['input']);
	          this.model.getImageCollection().setMorePhotoValues(imageInfo['values']);
	        }

	        if (!main_core.Type.isNil(fields['DETAIL_URL'])) {
	          this.model.setDetailPath(fields['DETAIL_URL']);
	        }
	      }

	      main_core_events.EventEmitter.subscribe(this.model, 'onErrorsChange', main_core.Runtime.debounce(_classPrivateMethodGet$1(this, _handleProductErrorsChange, _handleProductErrorsChange2), 500, this));
	      main_core_events.EventEmitter.subscribe(this.model, 'onChangeStoreData', _classPrivateMethodGet$1(this, _onChangeStoreData, _onChangeStoreData2).bind(this));
	    }
	  }, {
	    key: "getModel",
	    value: function getModel() {
	      return this.model;
	    }
	  }, {
	    key: "setProductId",
	    value: function setProductId(value) {
	      var isChangedValue = this.getField('PRODUCT_ID') !== value;

	      if (isChangedValue) {
	        var _this$storeSelector;

	        this.setField('PRODUCT_ID', value, false);
	        this.setField('OFFER_ID', value, false);
	        (_this$storeSelector = this.storeSelector) === null || _this$storeSelector === void 0 ? void 0 : _this$storeSelector.setProductId(value);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setPrice",
	    value: function setPrice(value) {
	      var originalPrice = value; // price can't be less than zero

	      value = Math.max(value, 0);
	      var calculatedFields = this.getCalculator().setFields(this.getCalculator().calculateBasePrice(this.getBasePrice())).calculatePrice(value);
	      delete calculatedFields['BASE_PRICE'];
	      this.setFields(calculatedFields);
	      this.refreshFieldsLayout(['PRICE_NETTO', 'PRICE_BRUTTO']);
	      this.addActionProductChange();
	      this.addActionUpdateTotal();
	      this.executeExternalActions();

	      _classPrivateMethodGet$1(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this, originalPrice < 0 && originalPrice !== value);
	    }
	  }, {
	    key: "setBasePrice",
	    value: function setBasePrice(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var originalPrice = value; // price can't be less than zero

	      value = Math.max(value, 0);

	      if (mode === MODE_SET) {
	        this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
	      }

	      var isChangedValue = this.getBasePrice() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateBasePrice(value);
	        this.setFields(calculatedFields);
	        var exceptFieldNames = mode === MODE_EDIT ? ['BASE_PRICE', 'PRICE', 'ENTERED_PRICE'] : [];
	        this.refreshFieldsLayout(exceptFieldNames);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }

	      _classPrivateMethodGet$1(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this, originalPrice < 0 && originalPrice !== value);
	    }
	  }, {
	    key: "setQuantity",
	    value: function setQuantity(value) {
	      var _this4 = this;

	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_SET) {
	        this.updateUiInputField('QUANTITY', value);
	      }

	      var isChangedValue = this.getField('QUANTITY') !== value;
	      var errorNotifyId = 'quantityReservedCountError';
	      var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);

	      if (value < this.getField('RESERVE_QUANTITY')) {
	        if (!notify) {
	          var notificationOptions = {
	            id: errorNotifyId,
	            closeButton: true,
	            autoHideDelay: 3000,
	            content: main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_THEN_RESERVED')),
	            events: {
	              onClose: function onClose() {
	                _this4.updateUiInputField('QUANTITY', _this4.getField('QUANTITY'));
	              }
	            }
	          };
	          notify = BX.UI.Notification.Center.notify(notificationOptions);
	        }

	        notify.show();
	      } else if (isChangedValue) {
	        if (notify) {
	          notify.close();
	        }

	        var calculatedFields = this.getCalculator().calculateQuantity(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout(['QUANTITY']);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setReserveQuantity",
	    value: function setReserveQuantity(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (mode === MODE_EDIT) {
	        var node = this.getNode().querySelector('[data-name="RESERVE_INFO"]');
	        var input = node === null || node === void 0 ? void 0 : node.querySelector('input[name="RESERVE_QUANTITY"]');

	        if (main_core.Type.isElementNode(input)) {
	          var _this$reserveControl;

	          input.value = value;
	          (_this$reserveControl = this.reserveControl) === null || _this$reserveControl === void 0 ? void 0 : _this$reserveControl.changeInputValue(value);
	        }
	      }
	    }
	  }, {
	    key: "setMeasure",
	    value: function setMeasure(measure) {
	      var _this5 = this;

	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      this.setField('MEASURE_CODE', measure.CODE);
	      this.setField('MEASURE_NAME', measure.SYMBOL);
	      this.updateUiMoneyField('MEASURE_CODE', measure.CODE, main_core.Text.encode(measure.SYMBOL));

	      if (this.getModel().isNew()) {
	        this.getModel().save(['MEASURE_CODE']);
	      } else if (mode === MODE_EDIT) {
	        this.getModel().showSaveNotifier('measureChanger_' + this.getId(), {
	          title: main_core.Loc.getMessage('CATALOG_PRODUCT_MODEL_SAVING_NOTIFICATION_MEASURE_CHANGED_QUERY'),
	          events: {
	            onSave: function onSave() {
	              _this5.getModel().save(['MEASURE_CODE', 'MEASURE_NAME']);
	            }
	          }
	        });
	      }

	      this.addActionProductChange();
	    }
	  }, {
	    key: "setDiscount",
	    value: function setDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (!this.isDiscountHandmade()) {
	        return;
	      }

	      var fieldName = this.isDiscountPercentage() ? 'DISCOUNT_RATE' : 'DISCOUNT_SUM';
	      var isChangedValue = this.getField(fieldName) !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateDiscount(value);
	        this.setFields(calculatedFields);
	        var exceptFieldNames = mode === MODE_EDIT ? ['DISCOUNT_RATE', 'DISCOUNT_SUM', 'DISCOUNT'] : [];
	        this.refreshFieldsLayout(exceptFieldNames);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }

	      _classPrivateMethodGet$1(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this);
	    }
	  }, {
	    key: "setDiscountType",
	    value: function setDiscountType(value) {
	      var isChangedValue = value !== catalog_productCalculator.DiscountType.UNDEFINED && this.getField('DISCOUNT_TYPE_ID') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateDiscountType(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setRowDiscount",
	    value: function setRowDiscount(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var isChangedValue = this.getField('DISCOUNT_ROW') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateRowDiscount(value);
	        this.setFields(calculatedFields);
	        var exceptFieldNames = mode === MODE_EDIT ? ['DISCOUNT_ROW'] : [];
	        this.refreshFieldsLayout(exceptFieldNames);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setTaxRate",
	    value: function setTaxRate(value) {
	      if (!this.getEditor().isTaxAllowed()) {
	        return;
	      }

	      var isChangedValue = this.getTaxRate() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateTax(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setTaxIncluded",
	    value: function setTaxIncluded(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;

	      if (!this.getEditor().isTaxAllowed()) {
	        return;
	      }

	      if (mode === MODE_SET) {
	        this.updateUiCheckboxField('TAX_INCLUDED', value);
	      }

	      var isChangedValue = this.getTaxIncluded() !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateTaxIncluded(value);
	        this.setFields(calculatedFields);
	        this.refreshFieldsLayout();
	        this.addActionUpdateFieldList('TAX_INCLUDED', value);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    }
	  }, {
	    key: "setRowSum",
	    value: function setRowSum(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      var isChangedValue = this.getField('SUM') !== value;

	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateRowSum(value);
	        this.setFields(calculatedFields);
	        var exceptFieldNames = mode === MODE_EDIT ? ['SUM'] : [];
	        this.refreshFieldsLayout(exceptFieldNames);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	    } // controls

	  }, {
	    key: "getInputByFieldName",
	    value: function getInputByFieldName(fieldName) {
	      var fieldId = this.getUiFieldId(fieldName);
	      var item = document.getElementById(fieldId);

	      if (!main_core.Type.isElementNode(item)) {
	        item = this.getNode().querySelector('[name="' + fieldId + '"]');
	      }

	      return item;
	    }
	  }, {
	    key: "updateUiInputField",
	    value: function updateUiInputField(name, value) {
	      var item = this.getInputByFieldName(name);

	      if (main_core.Type.isElementNode(item)) {
	        item.value = value;
	      }
	    }
	  }, {
	    key: "updateUiCheckboxField",
	    value: function updateUiCheckboxField(name, value) {
	      var item = this.getInputByFieldName(name);

	      if (main_core.Type.isElementNode(item)) {
	        item.checked = value === 'Y';
	      }
	    }
	  }, {
	    key: "updateUiDiscountTypeField",
	    value: function updateUiDiscountTypeField(name, value) {
	      var text = value === catalog_productCalculator.DiscountType.MONETARY ? this.getEditor().getCurrencyText() : '%';
	      this.updateUiMoneyField(name, value, text);
	    }
	  }, {
	    key: "getMoneyFieldDropdownApi",
	    value: function getMoneyFieldDropdownApi(name) {
	      if (!main_core.Reflection.getClass('BX.Main.dropdownManager')) {
	        return null;
	      }

	      return BX.Main.dropdownManager.getById(this.getId() + '_' + name + '_control');
	    }
	  }, {
	    key: "updateMoneyFieldUiWithDropdownApi",
	    value: function updateMoneyFieldUiWithDropdownApi(dropdown, value) {
	      if (dropdown.getValue() == value) {
	        return;
	      }

	      var item = dropdown.menu.itemsContainer.querySelector('[data-value="' + value + '"]');
	      var menuItem = item && dropdown.getMenuItem(item);

	      if (menuItem) {
	        dropdown.refresh(menuItem);
	        dropdown.selectItem(menuItem);
	      }
	    }
	  }, {
	    key: "updateMoneyFieldUiManually",
	    value: function updateMoneyFieldUiManually(name, value, text) {
	      var item = this.getInputByFieldName(name);

	      if (!main_core.Type.isElementNode(item)) {
	        return;
	      }

	      item.dataset.value = value;
	      var span = item.querySelector('span.main-dropdown-inner');

	      if (!main_core.Type.isElementNode(span)) {
	        return;
	      }

	      span.innerHTML = text;
	    }
	  }, {
	    key: "updateUiMoneyField",
	    value: function updateUiMoneyField(name, value, text) {
	      var dropdownApi = this.getMoneyFieldDropdownApi(name);

	      if (dropdownApi) {
	        this.updateMoneyFieldUiWithDropdownApi(dropdownApi, value);
	      } else {
	        this.updateMoneyFieldUiManually(name, value, text);
	      }
	    }
	  }, {
	    key: "updateUiMeasure",
	    value: function updateUiMeasure(code, name) {
	      this.updateUiMoneyField('MEASURE_CODE', code, name);
	      this.updateUiStoreAmountData();
	    }
	  }, {
	    key: "updateUiHtmlField",
	    value: function updateUiHtmlField(name, html) {
	      var item = this.getNode().querySelector('[data-name="' + name + '"]');

	      if (main_core.Type.isElementNode(item)) {
	        item.innerHTML = html;
	      }
	    }
	  }, {
	    key: "updateUiCurrencyFields",
	    value: function updateUiCurrencyFields() {
	      var _this6 = this;

	      var currencyText = this.getEditor().getCurrencyText();
	      var currencyId = '' + this.getEditor().getCurrencyId();
	      var currencyFieldNames = ['PRICE_CURRENCY', 'SUM_CURRENCY', 'DISCOUNT_TYPE_ID', 'DISCOUNT_ROW_CURRENCY'];
	      currencyFieldNames.forEach(function (name) {
	        var dropdownValues = [];

	        if (name === 'DISCOUNT_TYPE_ID') {
	          dropdownValues.push({
	            NAME: '%',
	            VALUE: '' + catalog_productCalculator.DiscountType.PERCENTAGE
	          });
	          dropdownValues.push({
	            NAME: currencyText,
	            VALUE: '' + catalog_productCalculator.DiscountType.MONETARY
	          });

	          if (_this6.getDiscountType() === catalog_productCalculator.DiscountType.MONETARY) {
	            _this6.updateMoneyFieldUiManually(name, catalog_productCalculator.DiscountType.MONETARY, currencyText);
	          }
	        } else {
	          dropdownValues.push({
	            NAME: currencyText,
	            VALUE: currencyId
	          });

	          _this6.updateUiMoneyField(name, currencyId, currencyText);
	        }

	        main_core.Dom.attr(_this6.getInputByFieldName(name), 'data-items', dropdownValues);
	      });
	      this.updateUiField('TAX_SUM', this.getField('TAX_SUM'));
	    }
	  }, {
	    key: "updateUiField",
	    value: function updateUiField(field, value) {
	      var uiName = this.getUiFieldName(field);

	      if (!uiName) {
	        return;
	      }

	      var uiType = this.getUiFieldType(field);

	      if (!uiType) {
	        return;
	      }

	      if (!this.allowUpdateUiField(field)) {
	        return;
	      }

	      switch (uiType) {
	        case 'input':
	          if (field === 'QUANTITY') {
	            value = this.parseFloat(value, this.getQuantityPrecision());
	          } else if (field === 'DISCOUNT_RATE' || field === 'TAX_RATE') {
	            value = this.parseFloat(value, this.getCommonPrecision());
	          } else if (value === 0) {
	            value = '';
	          } else if (main_core.Type.isNumber(value)) {
	            value = this.parseFloat(value, this.getPricePrecision()).toFixed(this.getPricePrecision());
	          }

	          this.updateUiInputField(uiName, value);
	          break;

	        case 'checkbox':
	          this.updateUiCheckboxField(uiName, value);
	          break;

	        case 'discount_type_field':
	          this.updateUiDiscountTypeField(uiName, value);
	          break;

	        case 'html':
	          this.updateUiHtmlField(uiName, value);
	          break;

	        case 'money_html':
	          value = currency_currencyCore.CurrencyCore.currencyFormat(value, this.getEditor().getCurrencyId(), true);
	          this.updateUiHtmlField(uiName, value);
	          break;
	      }
	    }
	  }, {
	    key: "getUiFieldName",
	    value: function getUiFieldName(field) {
	      var result = null;

	      switch (field) {
	        case 'QUANTITY':
	        case 'MEASURE_CODE':
	        case 'DISCOUNT_ROW':
	        case 'DISCOUNT_TYPE_ID':
	        case 'TAX_RATE':
	        case 'TAX_INCLUDED':
	        case 'TAX_SUM':
	        case 'SUM':
	        case 'PRODUCT_NAME':
	        case 'SORT':
	          result = field;
	          break;

	        case 'ENTERED_PRICE':
	          result = 'PRICE';
	          break;

	        case 'DISCOUNT_RATE':
	        case 'DISCOUNT_SUM':
	          result = 'DISCOUNT_PRICE';
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "getUiFieldType",
	    value: function getUiFieldType(field) {
	      var result = null;

	      switch (field) {
	        case 'PRICE':
	        case 'ENTERED_PRICE':
	        case 'QUANTITY':
	        case 'TAX_RATE':
	        case 'DISCOUNT_RATE':
	        case 'DISCOUNT_SUM':
	        case 'DISCOUNT_ROW':
	        case 'SUM':
	        case 'PRODUCT_NAME':
	        case 'SORT':
	          result = 'input';
	          break;

	        case 'DISCOUNT_TYPE_ID':
	          result = 'discount_type_field';
	          break;

	        case 'TAX_INCLUDED':
	          result = 'checkbox';
	          break;

	        case 'TAX_SUM':
	          result = 'money_html';
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "allowUpdateUiField",
	    value: function allowUpdateUiField(field) {
	      var result = true;

	      switch (field) {
	        case 'PRICE_NETTO':
	          result = this.isPriceNetto();
	          break;

	        case 'PRICE_BRUTTO':
	          result = !this.isPriceNetto();
	          break;

	        case 'DISCOUNT_RATE':
	          result = this.isDiscountPercentage();
	          break;

	        case 'DISCOUNT_SUM':
	          result = this.isDiscountMonetary();
	          break;
	      }

	      return result;
	    } // proxy

	  }, {
	    key: "parseInt",
	    value: function parseInt(value) {
	      var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      return this.getEditor().parseInt(value, defaultValue);
	    }
	  }, {
	    key: "parseFloat",
	    value: function parseFloat(value, precision) {
	      var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0;
	      return this.getEditor().parseFloat(value, precision, defaultValue);
	    }
	  }, {
	    key: "getPricePrecision",
	    value: function getPricePrecision() {
	      return this.getEditor().getPricePrecision();
	    }
	  }, {
	    key: "getQuantityPrecision",
	    value: function getQuantityPrecision() {
	      return this.getEditor().getQuantityPrecision();
	    }
	  }, {
	    key: "getCommonPrecision",
	    value: function getCommonPrecision() {
	      return this.getEditor().getCommonPrecision();
	    }
	  }, {
	    key: "resetExternalActions",
	    value: function resetExternalActions() {
	      this.externalActions.length = 0;
	    }
	  }, {
	    key: "addExternalAction",
	    value: function addExternalAction(action) {
	      this.externalActions.push(action);
	    }
	  }, {
	    key: "addActionProductChange",
	    value: function addActionProductChange() {
	      this.addExternalAction({
	        type: this.getEditor().actions.productChange,
	        id: this.getId()
	      });
	    }
	  }, {
	    key: "addActionDisableSaveButton",
	    value: function addActionDisableSaveButton() {
	      this.addExternalAction({
	        type: this.getEditor().actions.disableSaveButton,
	        id: this.getId()
	      });
	    }
	  }, {
	    key: "addActionUpdateFieldList",
	    value: function addActionUpdateFieldList(field, value) {
	      this.addExternalAction({
	        type: this.getEditor().actions.updateListField,
	        field: field,
	        value: value
	      });
	    }
	  }, {
	    key: "addActionStateChanged",
	    value: function addActionStateChanged() {
	      this.addExternalAction({
	        type: this.getEditor().actions.stateChanged,
	        value: true
	      });
	    }
	  }, {
	    key: "addActionStateReset",
	    value: function addActionStateReset() {
	      this.addExternalAction({
	        type: this.getEditor().actions.stateChanged,
	        value: false
	      });
	    }
	  }, {
	    key: "addActionUpdateTotal",
	    value: function addActionUpdateTotal() {
	      this.addExternalAction({
	        type: this.getEditor().actions.updateTotal
	      });
	    }
	  }, {
	    key: "executeExternalActions",
	    value: function executeExternalActions() {
	      if (this.externalActions.length === 0) {
	        return;
	      }

	      this.getEditor().executeActions(this.externalActions);
	      this.resetExternalActions();
	    }
	  }, {
	    key: "isEmpty",
	    value: function isEmpty() {
	      return !main_core.Type.isStringFilled(this.getField('PRODUCT_NAME', '').trim()) && this.getField('PRODUCT_ID', 0) <= 0 && this.getPrice() <= 0;
	    }
	  }, {
	    key: "isReserveBlocked",
	    value: function isReserveBlocked() {
	      return this.getSettingValue('isReserveBlocked', false);
	    }
	  }]);
	  return Row;
	}();

	function _initActions2() {
	  var _this7 = this;

	  if (this.getEditor().isReadOnly()) {
	    return;
	  }

	  var actionCellContentContainer = this.getNode().querySelector('.main-grid-cell-action .main-grid-cell-content');

	  if (main_core.Type.isDomNode(actionCellContentContainer)) {
	    var actionsButton = main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a\n\t\t\t\t\thref=\"#\"\n\t\t\t\t\tclass=\"main-grid-row-action-button\"\n\t\t\t\t></a>\n\t\t\t"])));
	    main_core.Event.bind(actionsButton, 'click', function (event) {
	      var menuItems = [{
	        text: main_core.Loc.getMessage('CRM_ENTITY_PL_COPY'),
	        onclick: _this7.handleCopyAction.bind(_this7)
	      }, {
	        text: main_core.Loc.getMessage('CRM_ENTITY_PL_DELETE'),
	        onclick: _this7.handleDeleteAction.bind(_this7),
	        disabled: _this7.getModel().isEmpty() && _this7.getEditor().products.length <= 1
	      }];
	      main_popup.PopupMenu.show({
	        id: _this7.getId() + '_actions_popup',
	        bindElement: actionsButton,
	        items: menuItems,
	        cacheable: false
	      });
	      event.preventDefault();
	      event.stopPropagation();
	    });
	    main_core.Dom.append(actionsButton, actionCellContentContainer);
	  }
	}

	function _showChangePriceNotify2() {
	  var _this8 = this;

	  if (this.editor.getSettingValue('disableNotifyChangingPrice')) {
	    return;
	  }

	  var hint = main_core.Text.encode(this.editor.getSettingValue('catalogPriceEditArticleHint'));
	  var changePriceNotifyId = 'disabled-crm-changing-price';
	  var changePriceNotify = BX.UI.Notification.Center.getBalloonById(changePriceNotifyId);

	  if (!changePriceNotify) {
	    var content = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<div style=\"padding: 9px\">", "</div>\n\t\t\t\t</div>\n\t\t\t"])), hint);
	    var buttonRow = main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	    content.appendChild(buttonRow);
	    var articleCode = this.editor.getSettingValue('catalogPriceEditArticleCode');

	    if (articleCode) {
	      var moreLink = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span class=\"ui-notification-balloon-action\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\t\t\t\t\n\t\t\t\t"])), main_core.Loc.getMessage('CRM_ENTITY_MORE_LINK'));
	      main_core.Event.bind(moreLink, 'click', function () {
	        top.BX.Helper.show("redirect=detail&code=" + articleCode);
	        changePriceNotify.close();
	      });
	      buttonRow.appendChild(moreLink);
	    }

	    var disableNotificationLink = main_core.Tag.render(_templateObject6$1 || (_templateObject6$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"ui-notification-balloon-action\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\t\t\t\t\n\t\t\t"])), main_core.Loc.getMessage('CRM_ENTITY_DISABLE_NOTIFICATION'));
	    main_core.Event.bind(disableNotificationLink, 'click', function () {
	      changePriceNotify.close();

	      _this8.editor.setSettingValue('disableNotifyChangingPrice', true);

	      main_core.ajax.runComponentAction(_this8.editor.getComponentName(), 'setGridSetting', {
	        mode: 'class',
	        data: {
	          signedParameters: _this8.editor.getSignedParameters(),
	          settingId: 'DISABLE_NOTIFY_CHANGING_PRICE',
	          selected: true
	        }
	      });
	    });
	    buttonRow.appendChild(disableNotificationLink);
	    var notificationOptions = {
	      id: changePriceNotifyId,
	      closeButton: true,
	      category: Row.CATALOG_PRICE_CHANGING_DISABLED,
	      autoHideDelay: 10000,
	      content: content
	    };
	    changePriceNotify = BX.UI.Notification.Center.notify(notificationOptions);
	  }

	  changePriceNotify.show();
	}

	function _isEditableCatalogPrice2() {
	  return this.editor.canEditCatalogPrice() || !this.getModel().isCatalogExisted() || this.getModel().isNew();
	}

	function _isSaveableCatalogPrice2() {
	  return this.editor.canSaveCatalogPrice() || this.getModel().isCatalogExisted() && this.getModel().isNew();
	}

	function _initSelector2() {
	  var id = 'crm_grid_' + this.getId();
	  this.mainSelector = catalog_productSelector.ProductSelector.getById(id);

	  if (!this.mainSelector) {
	    var selectorOptions = {
	      iblockId: this.model.getIblockId(),
	      basePriceId: this.model.getBasePriceId(),
	      currency: this.model.getCurrency(),
	      model: this.model,
	      config: {
	        ENABLE_SEARCH: true,
	        IS_ALLOWED_CREATION_PRODUCT: true,
	        ENABLE_IMAGE_INPUT: true,
	        ROLLBACK_INPUT_AFTER_CANCEL: true,
	        ENABLE_INPUT_DETAIL_LINK: true,
	        ROW_ID: this.getId(),
	        ENABLE_SKU_SELECTION: true,
	        URL_BUILDER_CONTEXT: this.editor.getSettingValue('productUrlBuilderContext')
	      },
	      mode: catalog_productSelector.ProductSelector.MODE_EDIT
	    };
	    this.mainSelector = new catalog_productSelector.ProductSelector('crm_grid_' + this.getId(), selectorOptions);
	  }

	  var mainInfoNode = this.getNode().querySelector('[data-name="MAIN_INFO"]');

	  if (mainInfoNode) {
	    var numberSelector = mainInfoNode.querySelector('.main-grid-row-number');

	    if (!main_core.Type.isDomNode(numberSelector)) {
	      mainInfoNode.appendChild(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<div class=\"main-grid-row-number\"></div>"]))));
	    }

	    var selectorWrapper = mainInfoNode.querySelector('.main-grid-row-product-selector');

	    if (!main_core.Type.isDomNode(selectorWrapper)) {
	      selectorWrapper = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div class=\"main-grid-row-product-selector\"></div>"])));
	      mainInfoNode.appendChild(selectorWrapper);
	    }

	    this.mainSelector.renderTo(selectorWrapper);
	  }
	}

	function _initStoreSelector2() {
	  this.storeSelector = new catalog_storeSelector.StoreSelector(this.getId(), {
	    inputFieldId: 'STORE_ID',
	    inputFieldTitle: 'STORE_TITLE',
	    config: {
	      ENABLE_SEARCH: true,
	      ENABLE_INPUT_DETAIL_LINK: false,
	      ROW_ID: this.getId()
	    },
	    mode: catalog_storeSelector.StoreSelector.MODE_EDIT,
	    model: this.model
	  });
	  var storeWrapper = this.getNode().querySelector('[data-name="STORE_INFO"]');

	  if (this.storeSelector && storeWrapper) {
	    storeWrapper.innerHTML = '';
	    this.storeSelector.renderTo(storeWrapper);

	    if (this.isReserveBlocked()) {
	      _classPrivateMethodGet$1(this, _applyStoreSelectorRestrictionTweaks, _applyStoreSelectorRestrictionTweaks2).call(this);
	    }
	  }

	  main_core_events.EventEmitter.subscribe(this.storeSelector, 'onChange', main_core.Runtime.debounce(_classPrivateMethodGet$1(this, _onStoreFieldChange, _onStoreFieldChange2).bind(this), 500, this));
	  main_core_events.EventEmitter.subscribe(this.storeSelector, 'onClear', main_core.Runtime.debounce(_classPrivateMethodGet$1(this, _onStoreFieldClear, _onStoreFieldClear2).bind(this), 500, this));
	}

	function _applyStoreSelectorRestrictionTweaks2() {
	  var storeSearchInput = this.storeSelector.searchInput;

	  if (!storeSearchInput || !storeSearchInput.getNameInput()) {
	    return;
	  }

	  storeSearchInput.toggleIcon(this.storeSelector.searchInput.getSearchIcon(), 'none');
	  storeSearchInput.getNameInput().disabled = true;
	  storeSearchInput.getNameInput().classList.add('crm-entity-product-list-locked-field');

	  if (this.storeSelector.getWrapper()) {
	    this.storeSelector.getWrapper().onclick = function () {
	      return top.BX.UI.InfoHelper.show('limit_store_crm_integration');
	    };
	  }
	}

	function _initReservedControl2() {
	  var _this9 = this;

	  var storeWrapper = this.getNode().querySelector('[data-name="RESERVE_INFO"]');

	  if (storeWrapper) {
	    storeWrapper.innerHTML = '';
	    this.reserveControl = new ReserveControl({
	      model: this.getModel(),
	      defaultDateReservation: this.editor.getSettingValue('defaultDateReservation'),
	      isInputDisabled: this.isReserveBlocked()
	    });
	    this.reserveControl.renderTo(storeWrapper);
	    main_core_events.EventEmitter.subscribe(this.reserveControl, 'onChange', function (event) {
	      var item = event.getData();

	      _this9.updateField(item.NAME, item.VALUE);
	    });
	  }
	}

	function _onStoreFieldChange2(event) {
	  var _this10 = this;

	  var data = event.getData();
	  data.fields.forEach(function (item) {
	    _this10.updateField(item.NAME, item.VALUE);
	  });
	  this.initHandlersForSelectors();
	}

	function _onStoreFieldClear2(event) {
	  this.initHandlersForSelectors();
	}

	function _showPriceNotifier2(enteredPrice) {
	  var _this11 = this;

	  var disabledPriceNotify = BX.UI.Notification.Center.getBalloonByCategory(Row.CATALOG_PRICE_CHANGING_DISABLED);

	  if (disabledPriceNotify) {
	    disabledPriceNotify.close();
	  }

	  this.getModel().showSaveNotifier('priceChanger_' + this.getId(), {
	    title: main_core.Loc.getMessage('CATALOG_PRODUCT_MODEL_SAVING_NOTIFICATION_PRICE_CHANGED_QUERY'),
	    events: {
	      onCancel: function onCancel() {
	        if (_this11.getBasePrice() > _this11.getEnteredPrice()) {
	          _this11.setField('ENTERED_PRICE', _this11.getBasePrice());

	          _this11.updateUiInputField('PRICE', _this11.getBasePrice());
	        }

	        _this11.setPrice(enteredPrice);

	        if (_this11.getField('DISCOUNT_SUM') > 0) {
	          var settingPopup = _this11.getEditor().getSettingsPopup();

	          var setting = settingPopup === null || settingPopup === void 0 ? void 0 : settingPopup.getSetting('DISCOUNTS');

	          if (setting && setting.checked === false) {
	            settingPopup.requestGridSettings(setting, true);
	          }
	        }
	      },
	      onSave: function onSave() {
	        _this11.setField('ENTERED_PRICE', enteredPrice);

	        _this11.setField('PRICE', enteredPrice);

	        _this11.changeCatalogPrice('CATALOG_PRICE', enteredPrice);

	        _this11.setBasePrice(enteredPrice);

	        _this11.getModel().save(['BASE_PRICE', 'CURRENCY']);

	        _this11.refreshFieldsLayout();

	        _this11.addActionUpdateTotal();

	        _this11.executeExternalActions();
	      }
	    }
	  });
	}

	function _onChangeStoreData2() {
	  if (this.isReserveBlocked()) {
	    return;
	  }

	  var storeId = this.getModel().getField('STORE_ID');
	  var currentAmount = this.getModel().getStoreCollection().getStoreAmount(storeId);

	  if (currentAmount <= 0 && this.getModel().isChanged()) {
	    var maxStore = this.getModel().getStoreCollection().getMaxFilledStore();

	    if (maxStore.AMOUNT > currentAmount && this.storeSelector) {
	      this.storeSelector.onStoreSelect(maxStore.STORE_ID, main_core.Text.decode(maxStore.STORE_TITLE));
	    }
	  }

	  this.updateUiStoreAmountData();
	}

	function _handleProductErrorsChange2() {
	  this.getEditor().handleProductErrorsChange();
	}

	function _shouldShowSmallPriceHint2() {
	  return main_core.Text.toNumber(this.getField('PRICE')) > 0 && main_core.Text.toNumber(this.getField('PRICE')) < 1 && this.isDiscountPercentage() && (main_core.Text.toNumber(this.getField('DISCOUNT_SUM')) > 0 || main_core.Text.toNumber(this.getField('DISCOUNT_RATE')) > 0 || main_core.Text.toNumber(this.getField('DISCOUNT_ROW')) > 0);
	}

	function _togglePriceHintPopup2() {
	  var showNegative = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	  if (_classPrivateMethodGet$1(this, _shouldShowSmallPriceHint, _shouldShowSmallPriceHint2).call(this)) {
	    this.getHintPopup().load(this.getInputByFieldName('PRICE'), main_core.Loc.getMessage('CRM_ENTITY_PL_SMALL_PRICE_NOTICE')).show();
	  } else if (showNegative) {
	    this.getHintPopup().load(this.getInputByFieldName('PRICE'), main_core.Loc.getMessage('CRM_ENTITY_PL_NEGATIVE_PRICE_NOTICE')).show();
	  } else {
	    this.getHintPopup().close();
	  }
	}

	babelHelpers.defineProperty(Row, "CATALOG_PRICE_CHANGING_DISABLED", 'CATALOG_PRICE_CHANGING_DISABLED');

	var PageEventsManager = /*#__PURE__*/function () {
	  function PageEventsManager(settings) {
	    babelHelpers.classCallCheck(this, PageEventsManager);
	    babelHelpers.defineProperty(this, "_settings", {});
	    this._settings = settings ? settings : {};
	    this.eventHandlers = {};
	  }

	  babelHelpers.createClass(PageEventsManager, [{
	    key: "registerEventHandler",
	    value: function registerEventHandler(eventName, eventHandler) {
	      if (!this.eventHandlers[eventName]) this.eventHandlers[eventName] = [];
	      this.eventHandlers[eventName].push(eventHandler);
	      BX.addCustomEvent(this, eventName, eventHandler);
	    }
	  }, {
	    key: "fireEvent",
	    value: function fireEvent(eventName, eventParams) {
	      BX.onCustomEvent(this, eventName, eventParams);
	    }
	  }, {
	    key: "unregisterEventHandlers",
	    value: function unregisterEventHandlers(eventName) {
	      if (this.eventHandlers[eventName]) {
	        for (var i = 0; i < this.eventHandlers[eventName].length; i++) {
	          BX.removeCustomEvent(this, eventName, this.eventHandlers[eventName][i]);
	        }

	        delete this.eventHandlers[eventName];
	      }
	    }
	  }]);
	  return PageEventsManager;
	}();

	var _templateObject$3, _templateObject2$2, _templateObject3$2, _templateObject4$2;

	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$2(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _target = /*#__PURE__*/new WeakMap();

	var _settings = /*#__PURE__*/new WeakMap();

	var _editor = /*#__PURE__*/new WeakMap();

	var _cache$1 = /*#__PURE__*/new WeakMap();

	var _prepareSettingsContent = /*#__PURE__*/new WeakSet();

	var _getSettingItem = /*#__PURE__*/new WeakSet();

	var _setSetting = /*#__PURE__*/new WeakSet();

	var _showNotification = /*#__PURE__*/new WeakSet();

	var SettingsPopup = /*#__PURE__*/function () {
	  function SettingsPopup(target) {
	    var settings = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
	    var editor = arguments.length > 2 ? arguments[2] : undefined;
	    babelHelpers.classCallCheck(this, SettingsPopup);

	    _classPrivateMethodInitSpec$2(this, _showNotification);

	    _classPrivateMethodInitSpec$2(this, _setSetting);

	    _classPrivateMethodInitSpec$2(this, _getSettingItem);

	    _classPrivateMethodInitSpec$2(this, _prepareSettingsContent);

	    _classPrivateFieldInitSpec$1(this, _target, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec$1(this, _settings, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec$1(this, _editor, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec$1(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });

	    babelHelpers.classPrivateFieldSet(this, _target, target);
	    babelHelpers.classPrivateFieldSet(this, _settings, settings);
	    babelHelpers.classPrivateFieldSet(this, _editor, editor);
	  }

	  babelHelpers.createClass(SettingsPopup, [{
	    key: "show",
	    value: function show() {
	      this.getPopup().show();
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this = this;

	      return babelHelpers.classPrivateFieldGet(this, _cache$1).remember('settings-popup', function () {
	        return new main_popup.Popup(babelHelpers.classPrivateFieldGet(_this, _editor).getId() + '_' + Math.random() * 100, babelHelpers.classPrivateFieldGet(_this, _target), {
	          autoHide: true,
	          draggable: false,
	          offsetLeft: 0,
	          offsetTop: 0,
	          angle: {
	            position: 'top',
	            offset: 43
	          },
	          noAllPaddings: true,
	          bindOptions: {
	            forceBindPosition: true
	          },
	          closeByEsc: true,
	          content: _classPrivateMethodGet$2(_this, _prepareSettingsContent, _prepareSettingsContent2).call(_this)
	        });
	      });
	    }
	  }, {
	    key: "getSetting",
	    value: function getSetting(id) {
	      return babelHelpers.classPrivateFieldGet(this, _settings).filter(function (item) {
	        return item.id === id;
	      })[0];
	    }
	  }, {
	    key: "requestGridSettings",
	    value: function requestGridSettings(setting, enabled) {
	      var _this2 = this;

	      var headers = [];
	      var cells = babelHelpers.classPrivateFieldGet(this, _editor).getGrid().getRows().getHeadFirstChild().getCells();
	      Array.from(cells).forEach(function (header) {
	        if ('name' in header.dataset) {
	          headers.push(header.dataset.name);
	        }
	      });
	      main_core.ajax.runComponentAction(babelHelpers.classPrivateFieldGet(this, _editor).getComponentName(), 'setGridSetting', {
	        mode: 'class',
	        data: {
	          signedParameters: babelHelpers.classPrivateFieldGet(this, _editor).getSignedParameters(),
	          settingId: setting.id,
	          selected: enabled,
	          currentHeaders: headers
	        }
	      }).then(function () {
	        var message;
	        setting.checked = enabled;

	        if (setting.id === 'ADD_NEW_ROW_TOP') {
	          var panel = enabled ? 'top' : 'bottom';
	          babelHelpers.classPrivateFieldGet(_this2, _editor).setSettingValue('newRowPosition', panel);
	          var activePanel = babelHelpers.classPrivateFieldGet(_this2, _editor).changeActivePanelButtons(panel);
	          var settingButton = activePanel.querySelector('[data-role="product-list-settings-button"]');

	          _this2.getPopup().setBindElement(settingButton);

	          message = enabled ? main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_ENABLED') : main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_DISABLED');
	          message = message.replace('#NAME#', setting.title);
	        } else if (setting.id === 'WAREHOUSE') {
	          babelHelpers.classPrivateFieldGet(_this2, _editor).reloadGrid(false);
	          message = enabled ? main_core.Loc.getMessage('CRM_ENTITY_CARD_WAREHOUSE_ENABLED') : main_core.Loc.getMessage('CRM_ENTITY_CARD_WAREHOUSE_DISABLED');
	        } else {
	          babelHelpers.classPrivateFieldGet(_this2, _editor).reloadGrid();
	          message = enabled ? main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_ENABLED') : main_core.Loc.getMessage('CRM_ENTITY_PL_SETTING_DISABLED');
	          message = message.replace('#NAME#', setting.title);
	        }

	        _this2.getPopup().close();

	        _classPrivateMethodGet$2(_this2, _showNotification, _showNotification2).call(_this2, message, {
	          category: 'popup-settings'
	        });
	      });
	    }
	  }, {
	    key: "updateCheckboxState",
	    value: function updateCheckboxState() {
	      var _this3 = this;

	      var popupContainer = this.getPopup().getContentContainer();
	      babelHelpers.classPrivateFieldGet(this, _settings).filter(function (item) {
	        return item.action === 'grid' && main_core.Type.isArray(item.columns);
	      }).forEach(function (item) {
	        var allColumnsExist = true;
	        item.columns.forEach(function (columnName) {
	          if (!babelHelpers.classPrivateFieldGet(_this3, _editor).getGrid().getColumnHeaderCellByName(columnName)) {
	            allColumnsExist = false;
	          }
	        });
	        var checkbox = popupContainer.querySelector('input[data-setting-id="' + item.id + '"]');

	        if (main_core.Type.isDomNode(checkbox)) {
	          checkbox.checked = allColumnsExist;
	        }
	      });
	    }
	  }]);
	  return SettingsPopup;
	}();

	function _prepareSettingsContent2() {
	  var _this4 = this;

	  var content = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class='ui-entity-editor-popup-create-field-list'></div>\n\t\t"])));
	  babelHelpers.classPrivateFieldGet(this, _settings).forEach(function (item) {
	    content.append(_classPrivateMethodGet$2(_this4, _getSettingItem, _getSettingItem2).call(_this4, item));
	  });
	  return content;
	}

	function _getSettingItem2(item) {
	  var _this5 = this;

	  var input = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"checkbox\">\n\t\t"])));
	  input.checked = item.checked;
	  input.dataset.settingId = item.id;
	  var descriptionNode = main_core.Type.isStringFilled(item.desc) ? main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-entity-editor-popup-create-field-item-desc\">", "</span>"])), item.desc) : '';
	  var setting = main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<label class=\"ui-ctl-block ui-entity-editor-popup-create-field-item ui-ctl-w100\">\n\t\t\t\t<div class=\"ui-ctl-w10\" style=\"text-align: center\">", "</div>\n\t\t\t\t<div class=\"ui-ctl-w75\">\n\t\t\t\t\t<span class=\"ui-entity-editor-popup-create-field-item-title\">", "</span>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</label>\n\t\t"])), input, item.title, descriptionNode);

	  if (item.id === 'WAREHOUSE') {
	    main_core.Event.bind(setting, 'change', function (event) {
	      new catalog_storeUse.DialogDisable().popup();
	      main_core_events.EventEmitter.subscribe(catalog_storeUse.EventType.popup.disable, function () {
	        return _classPrivateMethodGet$2(_this5, _setSetting, _setSetting2).call(_this5, event);
	      });
	      main_core_events.EventEmitter.subscribe(catalog_storeUse.EventType.popup.disableCancel, function () {
	        return event.target.checked = true;
	      });
	    });
	  } else if (item.id === 'SLIDER') {
	    main_core.Event.bind(setting, 'change', function (event) {
	      new catalog_storeUse.Slider().open(item.url, {}).then(function () {
	        return babelHelpers.classPrivateFieldGet(_this5, _editor).reloadGrid(false);
	      });
	    });
	  } else {
	    main_core.Event.bind(setting, 'change', _classPrivateMethodGet$2(this, _setSetting, _setSetting2).bind(this));
	  }

	  return setting;
	}

	function _setSetting2(event) {
	  var settingItem = this.getSetting(event.target.dataset.settingId);

	  if (!settingItem) {
	    return;
	  }

	  var settingEnabled = event.target.checked;
	  this.requestGridSettings(settingItem, settingEnabled);
	}

	function _showNotification2(content, options) {
	  options = options || {};
	  BX.UI.Notification.Center.notify({
	    content: content,
	    stack: options.stack || null,
	    position: 'top-right',
	    width: 'auto',
	    category: options.category || null,
	    autoHideDelay: options.autoHideDelay || 3000
	  });
	}

	var _templateObject$4;

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }

	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var GRID_TEMPLATE_ROW = 'template_0';
	var DEFAULT_PRECISION = 2;

	var _initSupportCustomRowActions = /*#__PURE__*/new WeakSet();

	var _getCalculatePriceFieldNames = /*#__PURE__*/new WeakSet();

	var _childrenHasErrors = /*#__PURE__*/new WeakSet();

	var Editor = /*#__PURE__*/function () {
	  function Editor(id) {
	    babelHelpers.classCallCheck(this, Editor);

	    _classPrivateMethodInitSpec$3(this, _childrenHasErrors);

	    _classPrivateMethodInitSpec$3(this, _getCalculatePriceFieldNames);

	    _classPrivateMethodInitSpec$3(this, _initSupportCustomRowActions);

	    babelHelpers.defineProperty(this, "ajaxPool", new Map());
	    babelHelpers.defineProperty(this, "products", []);
	    babelHelpers.defineProperty(this, "productsWasInitiated", false);
	    babelHelpers.defineProperty(this, "isChangedGrid", false);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    babelHelpers.defineProperty(this, "actions", {
	      disableSaveButton: 'disableSaveButton',
	      productChange: 'productChange',
	      productListChanged: 'productListChanged',
	      updateListField: 'listField',
	      stateChanged: 'stateChange',
	      updateTotal: 'total'
	    });
	    babelHelpers.defineProperty(this, "stateChange", {
	      changed: false,
	      sended: false
	    });
	    babelHelpers.defineProperty(this, "updateFieldForList", null);
	    babelHelpers.defineProperty(this, "totalData", {
	      inProgress: false
	    });
	    babelHelpers.defineProperty(this, "productSelectionPopupHandler", this.handleProductSelectionPopup.bind(this));
	    babelHelpers.defineProperty(this, "productRowAddHandler", this.handleProductRowAdd.bind(this));
	    babelHelpers.defineProperty(this, "showSettingsPopupHandler", this.handleShowSettingsPopup.bind(this));
	    babelHelpers.defineProperty(this, "onDialogSelectProductHandler", this.handleOnDialogSelectProduct.bind(this));
	    babelHelpers.defineProperty(this, "onSaveHandler", this.handleOnSave.bind(this));
	    babelHelpers.defineProperty(this, "onEntityUpdateHandler", this.handleOnEntityUpdate.bind(this));
	    babelHelpers.defineProperty(this, "onEditorSubmit", this.handleEditorSubmit.bind(this));
	    babelHelpers.defineProperty(this, "onInnerCancelHandler", this.handleOnInnerCancel.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeGridRequestHandler", this.handleOnBeforeGridRequest.bind(this));
	    babelHelpers.defineProperty(this, "onGridUpdatedHandler", this.handleOnGridUpdated.bind(this));
	    babelHelpers.defineProperty(this, "onGridRowMovedHandler", this.handleOnGridRowMoved.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeProductChangeHandler", this.handleOnBeforeProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onProductChangeHandler", this.handleOnProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onProductClearHandler", this.handleOnProductClear.bind(this));
	    babelHelpers.defineProperty(this, "dropdownChangeHandler", this.handleDropdownChange.bind(this));
	    babelHelpers.defineProperty(this, "changeProductFieldHandler", this.handleFieldChange.bind(this));
	    babelHelpers.defineProperty(this, "updateTotalDataDelayedHandler", main_core.Runtime.debounce(this.updateTotalDataDelayed, 1000, this));
	    this.setId(id);
	  }

	  babelHelpers.createClass(Editor, [{
	    key: "init",
	    value: function init() {
	      var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      this.setSettings(config);

	      if (this.canEdit()) {
	        this.addFirstRowIfEmpty();
	        this.enableEdit();
	      }

	      this.initForm();
	      this.initProducts();
	      this.initGridData();
	      main_core_events.EventEmitter.emit(window, 'EntityProductListController', [this]);

	      _classPrivateMethodGet$3(this, _initSupportCustomRowActions, _initSupportCustomRowActions2).call(this);

	      this.subscribeDomEvents();
	      this.subscribeCustomEvents();

	      if (this.getSettingValue('isReserveBlocked', false)) {
	        var headersToLock = ['STORE_INFO', 'RESERVE_INFO'];
	        var container = this.getContainer();
	        headersToLock.forEach(function (headerId) {
	          var header = container === null || container === void 0 ? void 0 : container.querySelector(".main-grid-cell-head[data-name=\"".concat(headerId, "\"] .main-grid-cell-head-container"));
	          var lock = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-entity-product-list-locked-header\"></span>"])));

	          lock.onclick = function () {
	            return top.BX.UI.InfoHelper.show('limit_store_crm_integration');
	          };

	          header === null || header === void 0 ? void 0 : header.insertBefore(lock, header.firstChild);
	        });
	      }
	    }
	  }, {
	    key: "subscribeDomEvents",
	    value: function subscribeDomEvents() {
	      var _this = this;

	      var container = this.getContainer();

	      if (main_core.Type.isElementNode(container)) {
	        container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	          main_core.Event.bind(selectButton, 'click', _this.productSelectionPopupHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	          main_core.Event.bind(addButton, 'click', _this.productRowAddHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.bind(configButton, 'click', _this.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "unsubscribeDomEvents",
	    value: function unsubscribeDomEvents() {
	      var _this2 = this;

	      var container = this.getContainer();

	      if (main_core.Type.isElementNode(container)) {
	        container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	          main_core.Event.unbind(selectButton, 'click', _this2.productSelectionPopupHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	          main_core.Event.unbind(addButton, 'click', _this2.productRowAddHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.unbind(configButton, 'click', _this2.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "subscribeCustomEvents",
	    value: function subscribeCustomEvents() {
	      main_core_events.EventEmitter.subscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.subscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
	      main_core_events.EventEmitter.subscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.subscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.subscribe('Dropdown::change', this.dropdownChangeHandler);
	    }
	  }, {
	    key: "unsubscribeCustomEvents",
	    value: function unsubscribeCustomEvents() {
	      main_core_events.EventEmitter.unsubscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.unsubscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
	      main_core_events.EventEmitter.unsubscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.unsubscribe('Dropdown::change', this.dropdownChangeHandler);
	    }
	  }, {
	    key: "handleOnDialogSelectProduct",
	    value: function handleOnDialogSelectProduct(event) {
	      var _this$products$;

	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          productId = _event$getCompatData2[0];

	      var id;

	      if (this.getProductCount() > 0 || ((_this$products$ = this.products[0]) === null || _this$products$ === void 0 ? void 0 : _this$products$.getField('ID')) <= 0) {
	        id = this.addProductRow();
	      } else {
	        var _this$products$2;

	        id = (_this$products$2 = this.products[0]) === null || _this$products$2 === void 0 ? void 0 : _this$products$2.getField('ID');
	      }

	      this.selectProductInRow(id, productId);
	    }
	  }, {
	    key: "selectProductInRow",
	    value: function selectProductInRow(id, productId) {
	      var _this3 = this;

	      if (!main_core.Type.isStringFilled(id) || main_core.Text.toNumber(productId) <= 0) {
	        return;
	      }

	      requestAnimationFrame(function () {
	        var _this3$getProductSele;

	        (_this3$getProductSele = _this3.getProductSelector(id)) === null || _this3$getProductSele === void 0 ? void 0 : _this3$getProductSele.onProductSelect(productId);
	      });
	    }
	  }, {
	    key: "handleOnSave",
	    value: function handleOnSave(event) {
	      var items = [];
	      this.products.forEach(function (product) {
	        var item = {
	          fields: _objectSpread({}, product.fields),
	          rowId: product.fields.ROW_ID
	        };
	        items.push(item);
	      });
	      this.setSettingValue('items', items);
	    }
	  }, {
	    key: "handleOnEntityUpdate",
	    value: function handleOnEntityUpdate(event) {
	      var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 1),
	          data = _event$getData2[0];

	      if (this.isChanged() && data.entityId === this.getSettingValue('entityId') && data.entityTypeId === this.getSettingValue('entityTypeId')) {
	        this.setGridChanged(false);
	        this.reloadGrid(false);
	      }
	    }
	  }, {
	    key: "handleEditorSubmit",
	    value: function handleEditorSubmit(event) {
	      if (!this.isLocationDependantTaxesEnabled()) {
	        return;
	      }

	      var entityData = event.getData()[0];

	      if (!entityData || !entityData.hasOwnProperty('LOCATION_ID')) {
	        return;
	      }

	      if (entityData['LOCATION_ID'] !== this.getLocationId()) {
	        this.setLocationId(entityData['LOCATION_ID']);
	        this.reloadGrid(false);
	      }
	    }
	  }, {
	    key: "handleOnInnerCancel",
	    value: function handleOnInnerCancel(event) {
	      if (this.controller) {
	        this.controller.rollback();
	      }

	      this.setGridChanged(false);
	      this.reloadGrid(false);
	    }
	  }, {
	    key: "changeActivePanelButtons",
	    value: function changeActivePanelButtons(panelCode) {
	      var container = this.getContainer();
	      var activePanel = container.querySelector('.crm-entity-product-list-add-block-' + panelCode);

	      if (main_core.Type.isDomNode(activePanel)) {
	        main_core.Dom.removeClass(activePanel, 'crm-entity-product-list-add-block-hidden');
	        main_core.Dom.addClass(activePanel, 'crm-entity-product-list-add-block-active');
	      }

	      var hiddenPanelCode = panelCode === 'top' ? 'bottom' : 'top';
	      var removePanel = container.querySelector('.crm-entity-product-list-add-block-' + hiddenPanelCode);

	      if (main_core.Type.isDomNode(removePanel)) {
	        main_core.Dom.addClass(removePanel, 'crm-entity-product-list-add-block-hidden');
	        main_core.Dom.removeClass(removePanel, 'crm-entity-product-list-add-block-active');
	      }

	      return activePanel;
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      var _this4 = this;

	      var useProductsFromRequest = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      var isInternalChanging = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;

	      if (isInternalChanging === null) {
	        isInternalChanging = !useProductsFromRequest;
	      }

	      this.getGrid().reloadTable('POST', {
	        useProductsFromRequest: useProductsFromRequest
	      }, function () {
	        return _this4.actionUpdateTotalData({
	          isInternalChanging: isInternalChanging
	        });
	      });
	    }
	    /*
	    	keep in mind different actions for this handler:
	    	- native reload by grid actions (columns settings, etc)		- products from request
	    	- reload by tax/discount settings button					- products from request		this.reloadGrid(true)
	    	- rollback													- products from db			this.reloadGrid(false)
	    	- reload after SalesCenter order save						- products from db			this.reloadGrid(false)
	    	- reload after save if location had been changed
	     */

	  }, {
	    key: "handleOnBeforeGridRequest",
	    value: function handleOnBeforeGridRequest(event) {
	      var _this5 = this;

	      var _event$getCompatData3 = event.getCompatData(),
	          _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 2),
	          grid = _event$getCompatData4[0],
	          eventArgs = _event$getCompatData4[1];

	      if (!grid || !grid.parent || grid.parent.getId() !== this.getGridId()) {
	        return;
	      } // reload by native grid actions (columns settings, etc), otherwise by this.reloadGrid()


	      var isNativeAction = !('useProductsFromRequest' in eventArgs.data);
	      var useProductsFromRequest = isNativeAction ? true : eventArgs.data.useProductsFromRequest;
	      eventArgs.url = this.getReloadUrl();
	      eventArgs.method = 'POST';
	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.data = _objectSpread(_objectSpread({}, eventArgs.data), {}, {
	        signedParameters: this.getSignedParameters(),
	        products: useProductsFromRequest ? this.getProductsFields() : null,
	        locationId: this.getLocationId(),
	        currencyId: this.getCurrencyId()
	      });
	      this.clearEditor();

	      if (isNativeAction && this.isChanged()) {
	        main_core_events.EventEmitter.subscribeOnce('Grid::updated', function () {
	          return _this5.actionUpdateTotalData({
	            isInternalChanging: false
	          });
	        });
	      }
	    }
	  }, {
	    key: "handleOnGridUpdated",
	    value: function handleOnGridUpdated(event) {
	      var _event$getCompatData5 = event.getCompatData(),
	          _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 1),
	          grid = _event$getCompatData6[0];

	      if (!grid || grid.getId() !== this.getGridId()) {
	        return;
	      }

	      this.getSettingsPopup().updateCheckboxState();
	    }
	  }, {
	    key: "handleOnGridRowMoved",
	    value: function handleOnGridRowMoved(event) {
	      var _event$getCompatData7 = event.getCompatData(),
	          _event$getCompatData8 = babelHelpers.slicedToArray(_event$getCompatData7, 3),
	          ids = _event$getCompatData8[0],
	          grid = _event$getCompatData8[2];

	      if (!grid || grid.getId() !== this.getGridId()) {
	        return;
	      }

	      var changed = this.resortProductsByIds(ids);

	      if (changed) {
	        this.refreshSortFields();
	        this.numerateRows();
	        this.executeActions([{
	          type: this.actions.productListChanged
	        }]);
	      }
	    }
	  }, {
	    key: "initPageEventsManager",
	    value: function initPageEventsManager() {
	      var componentId = this.getSettingValue('componentId');
	      this.pageEventsManager = new PageEventsManager({
	        id: componentId
	      });
	    }
	  }, {
	    key: "getPageEventsManager",
	    value: function getPageEventsManager() {
	      if (!this.pageEventsManager) {
	        this.initPageEventsManager();
	      }

	      return this.pageEventsManager;
	    }
	  }, {
	    key: "canEdit",
	    value: function canEdit() {
	      return this.getSettingValue('allowEdit', false) === true;
	    }
	  }, {
	    key: "canEditCatalogPrice",
	    value: function canEditCatalogPrice() {
	      return this.getSettingValue('allowCatalogPriceEdit', false) === true;
	    }
	  }, {
	    key: "canSaveCatalogPrice",
	    value: function canSaveCatalogPrice() {
	      return this.getSettingValue('allowCatalogPriceSave', false) === true;
	    }
	  }, {
	    key: "enableEdit",
	    value: function enableEdit() {
	      // Cannot use editSelected because checkboxes have been removed
	      var rows = this.getGrid().getRows().getRows();
	      rows.forEach(function (current) {
	        if (!current.isHeadChild() && !current.isTemplate()) {
	          current.edit();
	        }
	      });
	    }
	  }, {
	    key: "addFirstRowIfEmpty",
	    value: function addFirstRowIfEmpty() {
	      var _this6 = this;

	      if (this.getGrid().getRows().getCountDisplayed() === 0) {
	        requestAnimationFrame(function () {
	          return _this6.addProductRow();
	        });
	      }
	    }
	  }, {
	    key: "clearEditor",
	    value: function clearEditor() {
	      this.unsubscribeProductsEvents();
	      this.products = [];
	      this.productsWasInitiated = false;
	      this.destroySettingsPopup();
	      this.unsubscribeDomEvents();
	      this.unsubscribeCustomEvents();
	      main_core.Event.unbindAll(this.container);
	    }
	  }, {
	    key: "wasProductsInitiated",
	    value: function wasProductsInitiated() {
	      return this.productsWasInitiated;
	    }
	  }, {
	    key: "unsubscribeProductsEvents",
	    value: function unsubscribeProductsEvents() {
	      var _this7 = this;

	      this.products.forEach(function (current) {
	        var productSelector = _this7.getProductSelector(current.getField('ID')); // Used to avoid dependence on catalog 21.100.0


	        if (productSelector && typeof productSelector.unsubscribeEvents !== 'undefined') {
	          productSelector.unsubscribeEvents();
	        }
	      });
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      this.setForm(null);
	      this.clearController();
	      this.clearEditor();
	    }
	  }, {
	    key: "setController",
	    value: function setController(controller) {
	      if (this.controller === controller) {
	        return;
	      }

	      if (this.controller) {
	        this.controller.clearProductList();
	      }

	      this.controller = controller;
	    }
	  }, {
	    key: "clearController",
	    value: function clearController() {
	      this.controller = null;
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "setId",
	    value: function setId(id) {
	      this.id = id;
	    }
	    /* settings tools */

	  }, {
	    key: "getSettings",
	    value: function getSettings() {
	      return this.settings;
	    }
	  }, {
	    key: "setSettings",
	    value: function setSettings(settings) {
	      this.settings = settings ? settings : {};
	    }
	  }, {
	    key: "getSettingValue",
	    value: function getSettingValue(name, defaultValue) {
	      return this.settings.hasOwnProperty(name) ? this.settings[name] : defaultValue;
	    }
	  }, {
	    key: "setSettingValue",
	    value: function setSettingValue(name, value) {
	      this.settings[name] = value;
	    }
	  }, {
	    key: "getComponentName",
	    value: function getComponentName() {
	      return this.getSettingValue('componentName', '');
	    }
	  }, {
	    key: "getReloadUrl",
	    value: function getReloadUrl() {
	      return this.getSettingValue('reloadUrl', '');
	    }
	  }, {
	    key: "getSignedParameters",
	    value: function getSignedParameters() {
	      return this.getSettingValue('signedParameters', '');
	    }
	  }, {
	    key: "getContainerId",
	    value: function getContainerId() {
	      return this.getSettingValue('containerId', '');
	    }
	  }, {
	    key: "getGridId",
	    value: function getGridId() {
	      return this.getSettingValue('gridId', '');
	    }
	  }, {
	    key: "getLanguageId",
	    value: function getLanguageId() {
	      return this.getSettingValue('languageId', '');
	    }
	  }, {
	    key: "getSiteId",
	    value: function getSiteId() {
	      return this.getSettingValue('siteId', '');
	    }
	  }, {
	    key: "getCatalogId",
	    value: function getCatalogId() {
	      return this.getSettingValue('catalogId', 0);
	    }
	  }, {
	    key: "isReadOnly",
	    value: function isReadOnly() {
	      return this.getSettingValue('readOnly', true);
	    }
	  }, {
	    key: "setReadOnly",
	    value: function setReadOnly(readOnly) {
	      this.setSettingValue('readOnly', readOnly);
	    }
	  }, {
	    key: "getCurrencyId",
	    value: function getCurrencyId() {
	      return this.getSettingValue('currencyId', '');
	    }
	  }, {
	    key: "setCurrencyId",
	    value: function setCurrencyId(currencyId) {
	      this.setSettingValue('currencyId', currencyId);
	      this.products.forEach(function (product) {
	        var _product$getModel;

	        return (_product$getModel = product.getModel()) === null || _product$getModel === void 0 ? void 0 : _product$getModel.setOption('currency', currencyId);
	      });
	    }
	  }, {
	    key: "isLocationDependantTaxesEnabled",
	    value: function isLocationDependantTaxesEnabled() {
	      return this.getSettingValue('isLocationDependantTaxesEnabled', false);
	    }
	  }, {
	    key: "getLocationId",
	    value: function getLocationId() {
	      return this.getSettingValue('locationId');
	    }
	  }, {
	    key: "setLocationId",
	    value: function setLocationId(locationId) {
	      this.setSettingValue('locationId', locationId);
	    }
	  }, {
	    key: "changeCurrencyId",
	    value: function changeCurrencyId(currencyId) {
	      var _this8 = this;

	      this.setCurrencyId(currencyId);
	      var products = [];
	      this.products.forEach(function (product) {
	        var priceFields = {};

	        _classPrivateMethodGet$3(_this8, _getCalculatePriceFieldNames, _getCalculatePriceFieldNames2).call(_this8).forEach(function (name) {
	          priceFields[name] = product.getField(name);
	        });

	        products.push({
	          fields: priceFields,
	          id: product.getId()
	        });
	      });

	      if (products.length > 0) {
	        this.ajaxRequest('calculateProductPrices', {
	          products: products,
	          currencyId: currencyId
	        });
	      }

	      var editData = this.getGridEditData();
	      var templateRow = editData[GRID_TEMPLATE_ROW];
	      templateRow['CURRENCY'] = this.getCurrencyId();
	      var templateFieldNames = ['DISCOUNT_ROW', 'SUM', 'PRICE'];
	      templateFieldNames.forEach(function (field) {
	        templateRow[field]['CURRENCY']['VALUE'] = _this8.getCurrencyId();
	      });
	      this.setGridEditData(editData);
	    }
	  }, {
	    key: "onCalculatePricesResponse",
	    value: function onCalculatePricesResponse(products) {
	      this.products.forEach(function (product) {
	        if (main_core.Type.isObject(products[product.getId()])) {
	          product.updateUiCurrencyFields();
	          ['BASE_PRICE', 'ENTERED_PRICE', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'CURRENCY_ID'].forEach(function (name) {
	            product.updateField(name, main_core.Text.toNumber(products[product.getId()][name]));
	          });
	          product.setField('CURRENCY', products[product.getId()]['CURRENCY_ID']);
	        }
	      });
	      this.updateTotalUiCurrency();
	    }
	  }, {
	    key: "updateTotalUiCurrency",
	    value: function updateTotalUiCurrency() {
	      var _this9 = this;

	      var totalBlock = BX(this.getSettingValue('totalBlockContainerId', null));

	      if (main_core.Type.isElementNode(totalBlock)) {
	        totalBlock.querySelectorAll('[data-role="currency-wrapper"]').forEach(function (row) {
	          row.innerHTML = _this9.getCurrencyText();
	        });
	      }
	    }
	  }, {
	    key: "getCurrencyText",
	    value: function getCurrencyText() {
	      var currencyId = this.getCurrencyId();

	      if (!main_core.Type.isStringFilled(currencyId)) {
	        return '';
	      }

	      var format = currency_currencyCore.CurrencyCore.getCurrencyFormat(currencyId);
	      return format && format.FORMAT_STRING.replace(/(^|[^&])#/, '$1').trim() || '';
	    }
	  }, {
	    key: "getDataFieldName",
	    value: function getDataFieldName() {
	      return this.getSettingValue('dataFieldName', '');
	    }
	  }, {
	    key: "getDataSettingsFieldName",
	    value: function getDataSettingsFieldName() {
	      var field = this.getDataFieldName();
	      return main_core.Type.isStringFilled(field) ? field + '_SETTINGS' : '';
	    }
	  }, {
	    key: "getDiscountEnabled",
	    value: function getDiscountEnabled() {
	      return this.getSettingValue('enableDiscount', 'N');
	    }
	  }, {
	    key: "getPricePrecision",
	    value: function getPricePrecision() {
	      return this.getSettingValue('pricePrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getQuantityPrecision",
	    value: function getQuantityPrecision() {
	      return this.getSettingValue('quantityPrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getCommonPrecision",
	    value: function getCommonPrecision() {
	      return this.getSettingValue('commonPrecision', DEFAULT_PRECISION);
	    }
	  }, {
	    key: "getTaxList",
	    value: function getTaxList() {
	      return this.getSettingValue('taxList', []);
	    }
	  }, {
	    key: "getTaxAllowed",
	    value: function getTaxAllowed() {
	      return this.getSettingValue('allowTax', 'N');
	    }
	  }, {
	    key: "isTaxAllowed",
	    value: function isTaxAllowed() {
	      return this.getTaxAllowed() === 'Y';
	    }
	  }, {
	    key: "getTaxEnabled",
	    value: function getTaxEnabled() {
	      return this.getSettingValue('enableTax', 'N');
	    }
	  }, {
	    key: "isTaxEnabled",
	    value: function isTaxEnabled() {
	      return this.getTaxEnabled() === 'Y';
	    }
	  }, {
	    key: "isTaxUniform",
	    value: function isTaxUniform() {
	      return this.getSettingValue('taxUniform', true);
	    }
	  }, {
	    key: "getMeasures",
	    value: function getMeasures() {
	      return this.getSettingValue('measures', []);
	    }
	  }, {
	    key: "getDefaultMeasure",
	    value: function getDefaultMeasure() {
	      return this.getSettingValue('defaultMeasure', {});
	    }
	  }, {
	    key: "getRowIdPrefix",
	    value: function getRowIdPrefix() {
	      return this.getSettingValue('rowIdPrefix', 'crm_entity_product_list_');
	    }
	    /* settings tools finish */

	    /* calculate tools */

	  }, {
	    key: "parseInt",
	    value: function (_parseInt) {
	      function parseInt(_x) {
	        return _parseInt.apply(this, arguments);
	      }

	      parseInt.toString = function () {
	        return _parseInt.toString();
	      };

	      return parseInt;
	    }(function (value) {
	      var defaultValue = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
	      var result;
	      var isNumberValue = main_core.Type.isNumber(value);
	      var isStringValue = main_core.Type.isStringFilled(value);

	      if (!isNumberValue && !isStringValue) {
	        return defaultValue;
	      }

	      if (isStringValue) {
	        value = value.replace(/^\s+|\s+$/g, '');
	        var isNegative = value.indexOf('-') === 0;
	        result = parseInt(value.replace(/[^\d]/g, ''), 10);

	        if (isNaN(result)) {
	          result = defaultValue;
	        } else {
	          if (isNegative) {
	            result = -result;
	          }
	        }
	      } else {
	        result = parseInt(value, 10);

	        if (isNaN(result)) {
	          result = defaultValue;
	        }
	      }

	      return result;
	    })
	  }, {
	    key: "parseFloat",
	    value: function (_parseFloat) {
	      function parseFloat(_x2) {
	        return _parseFloat.apply(this, arguments);
	      }

	      parseFloat.toString = function () {
	        return _parseFloat.toString();
	      };

	      return parseFloat;
	    }(function (value) {
	      var precision = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_PRECISION;
	      var defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 0.0;
	      var result;
	      var isNumberValue = main_core.Type.isNumber(value);
	      var isStringValue = main_core.Type.isStringFilled(value);

	      if (!isNumberValue && !isStringValue) {
	        return defaultValue;
	      }

	      if (isStringValue) {
	        value = value.replace(/^\s+|\s+$/g, '');
	        var dot = value.indexOf('.');
	        var comma = value.indexOf(',');
	        var isNegative = value.indexOf('-') === 0;

	        if (dot < 0 && comma >= 0) {
	          var s1 = value.substr(0, comma);
	          var decimalLength = value.length - comma - 1;

	          if (decimalLength > 0) {
	            s1 += '.' + value.substr(comma + 1, decimalLength);
	          }

	          value = s1;
	        }

	        value = value.replace(/[^\d.]+/g, '');
	        result = parseFloat(value);

	        if (isNaN(result)) {
	          result = defaultValue;
	        }

	        if (isNegative) {
	          result = -result;
	        }
	      } else {
	        result = parseFloat(value);
	      }

	      if (precision >= 0) {
	        result = this.round(result, precision);
	      }

	      return result;
	    })
	  }, {
	    key: "round",
	    value: function round(value) {
	      var precision = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : DEFAULT_PRECISION;
	      var factor = Math.pow(10, precision);
	      return Math.round(value * factor) / factor;
	    }
	  }, {
	    key: "calculatePriceWithoutDiscount",
	    value: function calculatePriceWithoutDiscount(price, discount, discountType) {
	      var result = 0.0;

	      switch (discountType) {
	        case catalog_productCalculator.DiscountType.PERCENTAGE:
	          result = price - price * discount / 100;
	          break;

	        case catalog_productCalculator.DiscountType.MONETARY:
	          result = price - discount;
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "calculateDiscountRate",
	    value: function calculateDiscountRate(originalPrice, price) {
	      if (originalPrice === 0.0) {
	        return 0.0;
	      }

	      if (price === 0.0) {
	        return originalPrice > 0 ? 100.0 : -100.0;
	      }

	      return 100 * (originalPrice - price) / originalPrice;
	    }
	  }, {
	    key: "calculateDiscount",
	    value: function calculateDiscount(originalPrice, discountRate) {
	      return originalPrice * discountRate / 100;
	    }
	  }, {
	    key: "calculatePriceWithoutTax",
	    value: function calculatePriceWithoutTax(price, taxRate) {
	      // Tax is not included in price
	      return price / (1 + taxRate / 100);
	    }
	  }, {
	    key: "calculatePriceWithTax",
	    value: function calculatePriceWithTax(price, taxRate) {
	      // Tax is included in price
	      return price * (1 + taxRate / 100);
	    }
	    /* calculate tools finish */

	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this10 = this;

	      return this.cache.remember('container', function () {
	        return document.getElementById(_this10.getContainerId());
	      });
	    }
	  }, {
	    key: "initForm",
	    value: function initForm() {
	      var formId = this.getSettingValue('formId', '');
	      var form = main_core.Type.isStringFilled(formId) ? BX('form_' + formId) : null;

	      if (main_core.Type.isElementNode(form)) {
	        this.setForm(form);
	      }
	    }
	  }, {
	    key: "isExistForm",
	    value: function isExistForm() {
	      return main_core.Type.isElementNode(this.getForm());
	    }
	  }, {
	    key: "getForm",
	    value: function getForm() {
	      return this.form;
	    }
	  }, {
	    key: "setForm",
	    value: function setForm(form) {
	      this.form = form;
	    }
	  }, {
	    key: "initFormFields",
	    value: function initFormFields() {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container)) {
	        var field = this.getDataField();

	        if (!main_core.Type.isElementNode(field)) {
	          this.initDataField();
	        }

	        var settingsField = this.getDataSettingsField();

	        if (!main_core.Type.isElementNode(settingsField)) {
	          this.initDataSettingsField();
	        }
	      }
	    }
	  }, {
	    key: "initFormField",
	    value: function initFormField(fieldName) {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container) && main_core.Type.isStringFilled(fieldName)) {
	        container.appendChild(main_core.Dom.create('input', {
	          attrs: {
	            type: "hidden",
	            name: fieldName
	          }
	        }));
	      }
	    }
	  }, {
	    key: "removeFormFields",
	    value: function removeFormFields() {
	      var field = this.getDataField();

	      if (main_core.Type.isElementNode(field)) {
	        main_core.Dom.remove(field);
	      }

	      var settingsField = this.getDataSettingsField();

	      if (main_core.Type.isElementNode(settingsField)) {
	        main_core.Dom.remove(settingsField);
	      }
	    }
	  }, {
	    key: "initDataField",
	    value: function initDataField() {
	      this.initFormField(this.getDataFieldName());
	    }
	  }, {
	    key: "initDataSettingsField",
	    value: function initDataSettingsField() {
	      this.initFormField(this.getDataSettingsFieldName());
	    }
	  }, {
	    key: "getFormField",
	    value: function getFormField(fieldName) {
	      var container = this.getForm();

	      if (main_core.Type.isElementNode(container) && main_core.Type.isStringFilled(fieldName)) {
	        return container.querySelector('input[name="' + fieldName + '"]');
	      }

	      return null;
	    }
	  }, {
	    key: "getDataField",
	    value: function getDataField() {
	      return this.getFormField(this.getDataFieldName());
	    }
	  }, {
	    key: "getDataSettingsField",
	    value: function getDataSettingsField() {
	      return this.getFormField(this.getDataSettingsFieldName());
	    }
	  }, {
	    key: "getProductCount",
	    value: function getProductCount() {
	      return this.products.filter(function (item) {
	        return !item.isEmpty();
	      }).length;
	    }
	  }, {
	    key: "initProducts",
	    value: function initProducts() {
	      var list = this.getSettingValue('items', []);
	      var isReserveBlocked = this.getSettingValue('isReserveBlocked', false);

	      var _iterator = _createForOfIteratorHelper$1(list),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var item = _step.value;

	          var fields = _objectSpread({}, item.fields);

	          var settings = {
	            selectorId: item.selectorId,
	            isReserveBlocked: isReserveBlocked
	          };
	          this.products.push(new Row(item.rowId, fields, settings, this));
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      this.numerateRows();
	      this.productsWasInitiated = true;
	    }
	  }, {
	    key: "numerateRows",
	    value: function numerateRows() {
	      this.products.forEach(function (product, index) {
	        product.setRowNumber(index + 1);
	      });
	    }
	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      var _this11 = this;

	      return this.cache.remember('grid', function () {
	        var gridId = _this11.getGridId();

	        if (!main_core.Reflection.getClass('BX.Main.gridManager.getInstanceById')) {
	          throw Error("Cannot find grid with '".concat(gridId, "' id."));
	        }

	        return BX.Main.gridManager.getInstanceById(gridId);
	      });
	    }
	  }, {
	    key: "initGridData",
	    value: function initGridData() {
	      var gridEditData = this.getSettingValue('templateGridEditData', null);

	      if (gridEditData) {
	        this.setGridEditData(gridEditData);
	      }
	    }
	  }, {
	    key: "getGridEditData",
	    value: function getGridEditData() {
	      return this.getGrid().arParams.EDITABLE_DATA;
	    }
	  }, {
	    key: "setGridEditData",
	    value: function setGridEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA = data;
	    }
	  }, {
	    key: "setOriginalTemplateEditData",
	    value: function setOriginalTemplateEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA[GRID_TEMPLATE_ROW] = data;
	    }
	  }, {
	    key: "handleProductErrorsChange",
	    value: function handleProductErrorsChange() {
	      if (_classPrivateMethodGet$3(this, _childrenHasErrors, _childrenHasErrors2).call(this)) {
	        this.controller.disableSaveButton();
	      }
	    }
	  }, {
	    key: "handleFieldChange",
	    value: function handleFieldChange(event) {
	      var row = event.target.closest('tr');

	      if (row && row.hasAttribute('data-id')) {
	        var product = this.getProductById(row.getAttribute('data-id'));

	        if (product) {
	          var cell = event.target.closest('td');
	          var fieldCode = this.getFieldCodeByGridCell(row, cell);

	          if (fieldCode) {
	            product.updateFieldByEvent(fieldCode, event);
	          }
	        }
	      }
	    }
	  }, {
	    key: "handleDropdownChange",
	    value: function handleDropdownChange(event) {
	      var _event$getData3 = event.getData(),
	          _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 5),
	          dropdownId = _event$getData4[0],
	          value = _event$getData4[4];

	      var regExp = new RegExp(this.getRowIdPrefix() + '([A-Za-z0-9]+)_(\\w+)_control', 'i');
	      var matches = dropdownId.match(regExp);

	      if (matches) {
	        var _matches = babelHelpers.slicedToArray(matches, 3),
	            rowId = _matches[1],
	            fieldCode = _matches[2];

	        var product = this.getProductById(rowId);

	        if (product) {
	          product.updateField(fieldCode, value, product.modeChanges.EDIT);
	        }
	      }
	    }
	  }, {
	    key: "getProductById",
	    value: function getProductById(id) {
	      var rowId = this.getRowIdPrefix() + id;
	      return this.getProductByRowId(rowId);
	    }
	  }, {
	    key: "getProductByRowId",
	    value: function getProductByRowId(rowId) {
	      return this.products.find(function (row) {
	        return row.getId() === rowId;
	      });
	    }
	  }, {
	    key: "getFieldCodeByGridCell",
	    value: function getFieldCodeByGridCell(row, cell) {
	      if (!main_core.Type.isDomNode(row) || !main_core.Type.isDomNode(cell)) {
	        return null;
	      }

	      var grid = this.getGrid();

	      if (grid) {
	        var headRow = grid.getRows().getHeadFirstChild();
	        var index = babelHelpers.toConsumableArray(row.cells).indexOf(cell);
	        return headRow.getCellNameByCellIndex(index);
	      }

	      return null;
	    }
	  }, {
	    key: "handleProductSelectionPopup",
	    value: function handleProductSelectionPopup(event) {
	      var caller = 'crm_entity_product_list';
	      var jsEventsManagerId = this.getSettingValue('jsEventsManagerId', '');
	      var popup = new BX.CDialog({
	        content_url: '/bitrix/components/bitrix/crm.product_row.list/product_choice_dialog.php?' + 'caller=' + caller + '&JS_EVENTS_MANAGER_ID=' + BX.util.urlencode(jsEventsManagerId) + '&sessid=' + BX.bitrix_sessid(),
	        height: Math.max(500, window.innerHeight - 400),
	        width: Math.max(800, window.innerWidth - 400),
	        draggable: true,
	        resizable: true,
	        min_height: 500,
	        min_width: 800,
	        zIndex: 800
	      });
	      main_core_events.EventEmitter.subscribe(popup, 'onWindowRegister', BX.defer(function () {
	        popup.Get().style.position = 'fixed';
	        popup.Get().style.top = parseInt(popup.Get().style.top) - BX.GetWindowScrollPos().scrollTop + 'px';
	        popup.OVERLAY.style.zIndex = 798;
	      }));
	      main_core_events.EventEmitter.subscribe(window, 'EntityProductListController:onInnerCancel', BX.defer(function () {
	        popup.Close();
	      }));

	      if (typeof BX.Crm.EntityEvent !== "undefined") {
	        main_core_events.EventEmitter.subscribe(window, BX.Crm.EntityEvent.names.update, BX.defer(function () {
	          requestAnimationFrame(function () {
	            popup.Close();
	          }, 0);
	        }));
	      }

	      popup.Show();
	    }
	  }, {
	    key: "addProductRow",
	    value: function addProductRow() {
	      var anchorProduct = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var row = this.createGridProductRow();
	      var newId = row.getId();

	      if (anchorProduct) {
	        var _this$getGrid$getRows;

	        var anchorRowNode = (_this$getGrid$getRows = this.getGrid().getRows().getById(anchorProduct.getField('ID'))) === null || _this$getGrid$getRows === void 0 ? void 0 : _this$getGrid$getRows.getNode();

	        if (anchorRowNode) {
	          anchorRowNode.parentNode.insertBefore(row.getNode(), anchorRowNode.nextSibling);
	        }
	      }

	      this.initializeNewProductRow(newId, anchorProduct);
	      this.getGrid().bindOnRowEvents();
	      return newId;
	    }
	  }, {
	    key: "handleProductRowAdd",
	    value: function handleProductRowAdd() {
	      var id = this.addProductRow();
	      this.focusProductSelector(id);
	    }
	  }, {
	    key: "handleShowSettingsPopup",
	    value: function handleShowSettingsPopup() {
	      this.getSettingsPopup().show();
	    }
	  }, {
	    key: "destroySettingsPopup",
	    value: function destroySettingsPopup() {
	      if (this.cache.has('settings-popup')) {
	        this.cache.get('settings-popup').getPopup().destroy();
	        this.cache["delete"]('settings-popup');
	      }
	    }
	  }, {
	    key: "getSettingsPopup",
	    value: function getSettingsPopup() {
	      var _this12 = this;

	      return this.cache.remember('settings-popup', function () {
	        return new SettingsPopup(_this12.getContainer().querySelector('.crm-entity-product-list-add-block-active [data-role="product-list-settings-button"]'), _this12.getSettingValue('popupSettings', []), _this12);
	      });
	    }
	  }, {
	    key: "getHintPopup",
	    value: function getHintPopup() {
	      var _this13 = this;

	      return this.cache.remember('hint-popup', function () {
	        return new HintPopup(_this13);
	      });
	    }
	  }, {
	    key: "createGridProductRow",
	    value: function createGridProductRow() {
	      var newId = main_core.Text.getRandom();
	      var originalTemplate = this.redefineTemplateEditData(newId);
	      var grid = this.getGrid();
	      var newRow;

	      if (this.getSettingValue('newRowPosition') === 'bottom') {
	        newRow = grid.appendRowEditor();
	      } else {
	        newRow = grid.prependRowEditor();
	      }

	      var newNode = newRow.getNode();

	      if (main_core.Type.isDomNode(newNode)) {
	        newNode.setAttribute('data-id', newId);
	        newRow.makeCountable();
	      }

	      if (originalTemplate) {
	        this.setOriginalTemplateEditData(originalTemplate);
	      }

	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);
	      grid.adjustRows();
	      grid.updateCounterDisplayed();
	      grid.updateCounterSelected();
	      return newRow;
	    }
	  }, {
	    key: "handleDeleteRow",
	    value: function handleDeleteRow(rowId, event) {
	      event.preventDefault();
	      this.deleteRow(rowId);
	    }
	  }, {
	    key: "redefineTemplateEditData",
	    value: function redefineTemplateEditData(newId) {
	      var data = this.getGridEditData();
	      var originalTemplateData = data[GRID_TEMPLATE_ROW];
	      var customEditData = this.prepareCustomEditData(originalTemplateData, newId);
	      this.setOriginalTemplateEditData(_objectSpread(_objectSpread({}, originalTemplateData), customEditData));
	      return originalTemplateData;
	    }
	  }, {
	    key: "prepareCustomEditData",
	    value: function prepareCustomEditData(originalEditData, newId) {
	      var customEditData = {};
	      var templateIdMask = this.getSettingValue('templateIdMask', '');

	      for (var i in originalEditData) {
	        if (originalEditData.hasOwnProperty(i)) {
	          if (main_core.Type.isStringFilled(originalEditData[i]) && originalEditData[i].indexOf(templateIdMask) >= 0) {
	            customEditData[i] = originalEditData[i].replace(new RegExp(templateIdMask, 'g'), newId);
	          } else if (main_core.Type.isPlainObject(originalEditData[i])) {
	            customEditData[i] = this.prepareCustomEditData(originalEditData[i], newId);
	          } else {
	            customEditData[i] = originalEditData[i];
	          }
	        }
	      }

	      return customEditData;
	    }
	  }, {
	    key: "initializeNewProductRow",
	    value: function initializeNewProductRow(newId) {
	      var anchorProduct = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
	      var fields = anchorProduct === null || anchorProduct === void 0 ? void 0 : anchorProduct.getFields();

	      if (main_core.Type.isNil(fields)) {
	        fields = _objectSpread(_objectSpread({}, this.getSettingValue('templateItemFields', {})), {
	          CURRENCY: this.getCurrencyId()
	        });
	        var lastItem = this.products[this.products.length - 1];

	        if (lastItem) {
	          fields.TAX_INCLUDED = lastItem.getField('TAX_INCLUDED');
	        }
	      }

	      var rowId = this.getRowIdPrefix() + newId;
	      fields.ID = newId;

	      if (main_core.Type.isObject(fields.IMAGE_INFO)) {
	        delete fields.IMAGE_INFO.input;
	      }

	      delete fields.RESERVE_ID;
	      var isReserveBlocked = this.getSettingValue('isReserveBlocked', false);
	      var product = new Row(rowId, fields, {
	        isReserveBlocked: isReserveBlocked
	      }, this);
	      product.refreshFieldsLayout();

	      if (anchorProduct instanceof Row) {
	        var _product$getSelector, _product$getSelector2;

	        this.products.splice(1 + this.products.indexOf(anchorProduct), 0, product);
	        (_product$getSelector = product.getSelector()) === null || _product$getSelector === void 0 ? void 0 : _product$getSelector.reloadFileInput();
	        (_product$getSelector2 = product.getSelector()) === null || _product$getSelector2 === void 0 ? void 0 : _product$getSelector2.layout();
	        product.updateUiMeasure(product.getField('MEASURE_CODE'), main_core.Text.encode(product.getField('MEASURE_NAME')));
	      } else if (this.getSettingValue('newRowPosition') === 'bottom') {
	        this.products.push(product);
	      } else {
	        this.products.unshift(product);
	      }

	      this.refreshSortFields();
	      this.numerateRows();
	      product.updateUiCurrencyFields();
	      this.updateTotalUiCurrency();
	      return product;
	    }
	  }, {
	    key: "isTaxIncludedActive",
	    value: function isTaxIncludedActive() {
	      return this.products.filter(function (product) {
	        return product.isTaxIncluded();
	      }).length > 0;
	    }
	  }, {
	    key: "getProductSelector",
	    value: function getProductSelector(newId) {
	      return catalog_productSelector.ProductSelector.getById('crm_grid_' + this.getRowIdPrefix() + newId);
	    }
	  }, {
	    key: "focusProductSelector",
	    value: function focusProductSelector(newId) {
	      var _this14 = this;

	      requestAnimationFrame(function () {
	        var _this14$getProductSel;

	        (_this14$getProductSel = _this14.getProductSelector(newId)) === null || _this14$getProductSel === void 0 ? void 0 : _this14$getProductSel.searchInDialog().focusName();
	      });
	    }
	  }, {
	    key: "handleOnBeforeProductChange",
	    value: function handleOnBeforeProductChange(event) {
	      var data = event.getData();
	      var product = this.getProductByRowId(data.rowId);

	      if (product) {
	        this.getGrid().tableFade();
	        product.resetExternalActions();
	      }
	    }
	  }, {
	    key: "handleOnProductChange",
	    value: function handleOnProductChange(event) {
	      var _this15 = this;

	      var data = event.getData();
	      var productRow = this.getProductByRowId(data.rowId);

	      if (productRow && data.fields) {
	        var promise = new Promise(function (resolve, reject) {
	          var fields = data.fields;

	          if (!main_core.Type.isNil(fields['SKU_TREE'])) {
	            fields['SKU_TREE'] = JSON.stringify(fields['SKU_TREE']);
	          }

	          if (!main_core.Type.isNil(fields['IMAGE_INFO'])) {
	            fields['IMAGE_INFO'] = JSON.stringify(fields['IMAGE_INFO']);
	          }

	          if (_this15.getCurrencyId() !== fields['CURRENCY_ID']) {
	            fields['CURRENCY'] = fields['CURRENCY_ID'];
	            var priceFields = {};

	            _classPrivateMethodGet$3(_this15, _getCalculatePriceFieldNames, _getCalculatePriceFieldNames2).call(_this15).forEach(function (name) {
	              priceFields[name] = data.fields[name];
	            });

	            var products = [{
	              fields: priceFields,
	              id: productRow.getId()
	            }];
	            main_core.ajax.runComponentAction(_this15.getComponentName(), 'calculateProductPrices', {
	              mode: 'class',
	              signedParameters: _this15.getSignedParameters(),
	              data: {
	                products: products,
	                currencyId: _this15.getCurrencyId(),
	                options: {
	                  ACTION: 'calculateProductPrices'
	                }
	              }
	            }).then(function (response) {
	              var changedFields = response.data.result[productRow.getId()];

	              if (changedFields) {
	                changedFields['CUSTOMIZED'] = 'Y';
	                resolve(Object.assign(fields, changedFields));
	              } else {
	                resolve(fields);
	              }
	            });
	          } else {
	            resolve(fields);
	          }
	        });
	        promise.then(function (fields) {
	          if (_this15.products.length > 1) {
	            var taxId = fields['VAT_ID'] || fields['TAX_ID'];
	            var taxIncluded = fields['VAT_INCLUDED'] || fields['TAX_INCLUDED'];

	            if (taxId > 0 && taxIncluded !== productRow.getTaxIncluded()) {
	              var _this15$getTaxList;

	              var taxRate = (_this15$getTaxList = _this15.getTaxList()) === null || _this15$getTaxList === void 0 ? void 0 : _this15$getTaxList.find(function (item) {
	                return parseInt(item.ID) === taxId;
	              });

	              if ((taxRate === null || taxRate === void 0 ? void 0 : taxRate.VALUE) > 0 && taxIncluded === 'Y') {
	                fields['BASE_PRICE'] = fields['BASE_PRICE'] / (1 + taxRate.VALUE / 100);
	              }
	            }

	            ['TAX_INCLUDED', 'VAT_INCLUDED'].forEach(function (name) {
	              return delete fields[name];
	            });
	          }

	          fields['CATALOG_PRICE'] = fields['BASE_PRICE'];
	          fields['ENTERED_PRICE'] = fields['BASE_PRICE'];
	          Object.keys(fields).forEach(function (key) {
	            productRow.updateFieldValue(key, fields[key]);
	          });

	          if (!main_core.Type.isStringFilled(fields['CUSTOMIZED'])) {
	            productRow.setField('CUSTOMIZED', 'N');
	          }

	          productRow.setField('IS_NEW', data.isNew ? 'Y' : 'N');
	          productRow.initHandlersForSelectors();
	          productRow.modifyBasePriceInput();
	          productRow.executeExternalActions();

	          _this15.getGrid().tableUnfade();
	        });
	      } else {
	        this.getGrid().tableUnfade();
	      }
	    }
	  }, {
	    key: "handleOnProductClear",
	    value: function handleOnProductClear(event) {
	      var _event$getData5 = event.getData(),
	          rowId = _event$getData5.rowId;

	      var product = this.getProductByRowId(rowId);

	      if (product) {
	        product.initHandlersForSelectors();
	        product.changeEnteredPrice(0);
	        product.modifyBasePriceInput();
	        product.executeExternalActions();
	      }
	    }
	  }, {
	    key: "compileProductData",
	    value: function compileProductData() {
	      if (!this.isExistForm()) {
	        return;
	      }

	      this.initFormFields();
	      var field = this.getDataField();
	      var settingsField = this.getDataSettingsField();
	      this.cleanProductRows();

	      if (main_core.Type.isElementNode(field) && main_core.Type.isElementNode(settingsField)) {
	        field.value = this.prepareProductDataValue();
	        settingsField.value = JSON.stringify({
	          ENABLE_DISCOUNT: this.getDiscountEnabled(),
	          ENABLE_TAX: this.getTaxEnabled()
	        });
	      }

	      this.addFirstRowIfEmpty();
	    }
	  }, {
	    key: "prepareProductDataValue",
	    value: function prepareProductDataValue() {
	      var productDataValue = '';

	      if (this.getProductCount()) {
	        var productData = [];
	        this.products.forEach(function (item) {
	          var itemFields = item.getFields();

	          if (!/^[0-9]+$/.test(itemFields['ID'])) {
	            itemFields['ID'] = 0;
	          }

	          itemFields['CUSTOMIZED'] = 'Y';
	          productData.push(itemFields);
	        });
	        productDataValue = JSON.stringify(productData);
	      }

	      return productDataValue;
	    }
	    /* actions */

	  }, {
	    key: "executeActions",
	    value: function executeActions(actions) {
	      var _this16 = this;

	      if (!main_core.Type.isArrayFilled(actions)) {
	        return;
	      }

	      var disableSaveButton = actions.filter(function (action) {
	        return action.type === _this16.actions.updateTotal || action.type === _this16.actions.disableSaveButton;
	      }).length > 0;

	      var _iterator2 = _createForOfIteratorHelper$1(actions),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var item = _step2.value;

	          if (!main_core.Type.isPlainObject(item) || !main_core.Type.isStringFilled(item.type)) {
	            continue;
	          }

	          switch (item.type) {
	            case this.actions.productChange:
	              this.actionSendProductChange(item, disableSaveButton);
	              break;

	            case this.actions.productListChanged:
	              this.actionSendProductListChanged(disableSaveButton);
	              break;

	            case this.actions.updateListField:
	              this.actionUpdateListField(item);
	              break;

	            case this.actions.updateTotal:
	              this.actionUpdateTotalData();
	              break;

	            case this.actions.stateChanged:
	              this.actionSendStatusChange(item);
	              break;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	    }
	  }, {
	    key: "actionSendProductChange",
	    value: function actionSendProductChange(item, disableSaveButton) {
	      if (!main_core.Type.isStringFilled(item.id)) {
	        return;
	      }

	      var product = this.getProductByRowId(item.id);

	      if (!product) {
	        return;
	      }

	      main_core_events.EventEmitter.emit(this, 'ProductList::onChangeFields', {
	        rowId: item.id,
	        productId: product.getField('PRODUCT_ID'),
	        fields: this.getProductByRowId(item.id).getCatalogFields()
	      });

	      if (this.controller) {
	        this.controller.productChange(disableSaveButton);
	        this.setGridChanged(true);
	      }
	    }
	  }, {
	    key: "actionSendProductListChanged",
	    value: function actionSendProductListChanged() {
	      var disableSaveButton = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (this.controller) {
	        this.controller.productChange(disableSaveButton);
	        this.setGridChanged(true);
	      }
	    }
	  }, {
	    key: "actionUpdateListField",
	    value: function actionUpdateListField(item) {
	      if (!main_core.Type.isStringFilled(item.field) || !('value' in item)) {
	        return;
	      }

	      if (!this.allowUpdateListField(item.field)) {
	        return;
	      }

	      this.updateFieldForList = item.field;

	      var _iterator3 = _createForOfIteratorHelper$1(this.products),
	          _step3;

	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var row = _step3.value;
	          row.updateFieldByName(item.field, item.value);
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }

	      this.updateFieldForList = null;
	    }
	  }, {
	    key: "actionUpdateTotalData",
	    value: function actionUpdateTotalData() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (this.totalData.inProgress) {
	        return;
	      }

	      this.updateTotalDataDelayedHandler(options);
	    }
	  }, {
	    key: "actionSendStatusChange",
	    value: function actionSendStatusChange(item) {
	      if (!('value' in item)) {
	        return;
	      }

	      if (this.stateChange.changed === item.value) {
	        return;
	      }

	      this.stateChange.changed = item.value;

	      if (this.stateChange.sended) {
	        return;
	      }

	      this.stateChange.sended = true;
	    }
	    /* actions finish */

	    /* action tools */

	  }, {
	    key: "allowUpdateListField",
	    value: function allowUpdateListField(field) {
	      if (this.updateFieldForList !== null) {
	        return false;
	      }

	      var result = true;

	      switch (field) {
	        case 'TAX_INCLUDED':
	          result = this.isTaxUniform() && this.isTaxAllowed();
	          break;
	      }

	      return result;
	    }
	  }, {
	    key: "setGridChanged",
	    value: function setGridChanged(changed) {
	      this.isChangedGrid = changed;
	    }
	  }, {
	    key: "isChanged",
	    value: function isChanged() {
	      return this.isChangedGrid;
	    }
	  }, {
	    key: "updateTotalDataDelayed",
	    value: function updateTotalDataDelayed() {
	      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (this.totalData.inProgress) {
	        return;
	      }

	      this.totalData.inProgress = true;
	      var products = this.getProductsFields(this.getProductFieldListForTotalData());
	      products.forEach(function (item) {
	        return item['CUSTOMIZED'] = 'Y';
	      });
	      this.ajaxRequest('calculateTotalData', {
	        options: options,
	        products: products,
	        currencyId: this.getCurrencyId()
	      });
	    }
	  }, {
	    key: "getProductsFields",
	    value: function getProductsFields() {
	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : [];
	      var productFields = [];

	      var _iterator4 = _createForOfIteratorHelper$1(this.products),
	          _step4;

	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var item = _step4.value;
	          productFields.push(item.getFields(fields));
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }

	      return productFields;
	    }
	  }, {
	    key: "getProductFieldListForTotalData",
	    value: function getProductFieldListForTotalData() {
	      return ['PRODUCT_ID', 'PRODUCT_NAME', 'QUANTITY', 'DISCOUNT_TYPE_ID', 'DISCOUNT_RATE', 'DISCOUNT_SUM', 'TAX_RATE', 'TAX_INCLUDED', 'PRICE_EXCLUSIVE', 'PRICE', 'CUSTOMIZED'];
	    }
	  }, {
	    key: "setTotalData",
	    value: function setTotalData(data) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var item = BX(this.getSettingValue('totalBlockContainerId', null));

	      if (main_core.Type.isElementNode(item)) {
	        var currencyId = this.getCurrencyId();
	        var list = ['totalCost', 'totalDelivery', 'totalTax', 'totalWithoutTax', 'totalDiscount', 'totalWithoutDiscount'];

	        for (var _i = 0, _list = list; _i < _list.length; _i++) {
	          var id = _list[_i];
	          var row = item.querySelector('[data-total="' + id + '"]');

	          if (main_core.Type.isElementNode(row) && id in data) {
	            row.innerHTML = currency_currencyCore.CurrencyCore.currencyFormat(data[id], currencyId, false);
	          }
	        }
	      }

	      this.sendTotalData(data, options);
	      this.totalData.inProgress = false;
	    }
	  }, {
	    key: "sendTotalData",
	    value: function sendTotalData(data, options) {
	      var _this17 = this;

	      if (this.controller) {
	        var needMarkAsChanged = true;

	        if (main_core.Type.isObject(options) && (options.isInternalChanging === true || options.isInternalChanging === 'true')) {
	          needMarkAsChanged = false;
	        }

	        setTimeout(function () {
	          _this17.controller.changeSumTotal(data, needMarkAsChanged, !_classPrivateMethodGet$3(_this17, _childrenHasErrors, _childrenHasErrors2).call(_this17));
	        }, 500);
	      }
	    }
	    /* action tools finish */

	    /* ajax tools */

	  }, {
	    key: "ajaxRequest",
	    value: function ajaxRequest(action, data) {
	      var _this18 = this;

	      var requestKey = main_core.Text.getRandom();
	      this.ajaxPool.set(action, requestKey);

	      if (!main_core.Type.isPlainObject(data.options)) {
	        data.options = {};
	      }

	      data.options.ACTION = action;
	      data.options.REQUEST_KEY = requestKey;
	      main_core.ajax.runComponentAction(this.getComponentName(), action, {
	        mode: 'class',
	        signedParameters: this.getSignedParameters(),
	        data: data
	      }).then(function (response) {
	        return _this18.ajaxResultSuccess(response, data.options);
	      }, function (response) {
	        return _this18.ajaxResultFailure(response);
	      });
	    }
	  }, {
	    key: "ajaxResultSuccess",
	    value: function ajaxResultSuccess(response, requestOptions) {
	      if (!this.ajaxResultCommonCheck(response) || this.ajaxPool.get(response.data.action) !== requestOptions.REQUEST_KEY) {
	        return;
	      }

	      this.ajaxPool["delete"](response.data.action);
	      main_core_events.EventEmitter.emit(this, 'onAjaxSuccess', response.data.action);

	      switch (response.data.action) {
	        case 'calculateTotalData':
	          if (main_core.Type.isPlainObject(response.data.result)) {
	            this.setTotalData(response.data.result, requestOptions);
	          }

	          break;

	        case 'calculateProductPrices':
	          if (main_core.Type.isPlainObject(response.data.result)) {
	            this.onCalculatePricesResponse(response.data.result);
	          }

	          break;
	      }
	    }
	  }, {
	    key: "validateSubmit",
	    value: function validateSubmit() {
	      var _this19 = this;

	      return new Promise(function (resolve, reject) {
	        var currentBalloon = BX.UI.Notification.Center.getBalloonByCategory(catalog_productModel.ProductModel.SAVE_NOTIFICATION_CATEGORY);

	        if (currentBalloon) {
	          main_core_events.EventEmitter.subscribeOnce(currentBalloon, BX.UI.Notification.Event.getFullName('onClose'), function () {
	            main_core_events.EventEmitter.subscribeOnce(_this19, 'onAjaxSuccess', function () {
	              setTimeout(resolve, 100);
	            });
	          });
	          currentBalloon.close();
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "ajaxResultFailure",
	    value: function ajaxResultFailure(response) {}
	  }, {
	    key: "ajaxResultCommonCheck",
	    value: function ajaxResultCommonCheck(responce) {
	      if (!main_core.Type.isPlainObject(responce)) {
	        return false;
	      }

	      if (!main_core.Type.isStringFilled(responce.status)) {
	        return false;
	      }

	      if (responce.status !== 'success') {
	        return false;
	      }

	      if (!main_core.Type.isPlainObject(responce.data)) {
	        return false;
	      }

	      if (!main_core.Type.isStringFilled(responce.data.action)) {
	        return false;
	      } // noinspection RedundantIfStatementJS


	      if (!('result' in responce.data)) {
	        return false;
	      }

	      return true;
	    }
	  }, {
	    key: "deleteRow",
	    value: function deleteRow(rowId) {
	      var skipActions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

	      if (!main_core.Type.isStringFilled(rowId)) {
	        return;
	      }

	      var gridRow = this.getGrid().getRows().getById(rowId);

	      if (gridRow) {
	        main_core.Dom.remove(gridRow.getNode());
	        this.getGrid().getRows().reset();
	      }

	      var productRow = this.getProductById(rowId);

	      if (productRow) {
	        var index = this.products.indexOf(productRow);

	        if (index > -1) {
	          this.products.splice(index, 1);
	          this.refreshSortFields();
	          this.numerateRows();
	        }
	      }

	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);

	      if (!skipActions) {
	        this.addFirstRowIfEmpty();
	        this.executeActions([{
	          type: this.actions.productListChanged
	        }, {
	          type: this.actions.updateTotal
	        }]);
	      }
	    }
	  }, {
	    key: "copyRow",
	    value: function copyRow(row) {
	      this.addProductRow(row);
	      this.refreshSortFields();
	      this.numerateRows();
	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);
	      this.executeActions([{
	        type: this.actions.productListChanged
	      }, {
	        type: this.actions.updateTotal
	      }]);
	    }
	  }, {
	    key: "cleanProductRows",
	    value: function cleanProductRows() {
	      var _this20 = this;

	      this.products.filter(function (item) {
	        return item.isEmpty();
	      }).forEach(function (row) {
	        return _this20.deleteRow(row.getField('ID'), true);
	      });
	    }
	  }, {
	    key: "resortProductsByIds",
	    value: function resortProductsByIds(ids) {
	      var changed = false;

	      if (main_core.Type.isArrayFilled(ids)) {
	        this.products.sort(function (a, b) {
	          if (ids.indexOf(a.getField('ID')) > ids.indexOf(b.getField('ID'))) {
	            return 1;
	          }

	          changed = true;
	          return -1;
	        });
	      }

	      return changed;
	    }
	  }, {
	    key: "refreshSortFields",
	    value: function refreshSortFields() {
	      this.products.forEach(function (item, index) {
	        return item.setField('SORT', (index + 1) * 10);
	      });
	    }
	  }, {
	    key: "handleOnTabShow",
	    value: function handleOnTabShow() {
	      main_core_events.EventEmitter.emit('onDemandRecalculateWrapper');
	    }
	  }]);
	  return Editor;
	}();

	function _initSupportCustomRowActions2() {
	  this.getGrid()._clickOnRowActionsButton = function () {};
	}

	function _getCalculatePriceFieldNames2() {
	  return ['BASE_PRICE', 'ENTERED_PRICE', 'TAX_INCLUDED', 'PRICE_NETTO', 'PRICE_BRUTTO', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'CURRENCY'];
	}

	function _childrenHasErrors2() {
	  return this.products.filter(function (product) {
	    return product.getModel().getErrorCollection().hasErrors();
	  }).length > 0;
	}

	exports.Editor = Editor;
	exports.PageEventsManager = PageEventsManager;

}((this.BX.Crm.Entity.ProductList = this.BX.Crm.Entity.ProductList || {}),BX,BX,BX.Catalog,BX.Catalog,BX.Main,BX,BX.Event,BX.Catalog.StoreUse,BX.Currency,BX.Catalog,BX.Catalog));
//# sourceMappingURL=script.js.map
