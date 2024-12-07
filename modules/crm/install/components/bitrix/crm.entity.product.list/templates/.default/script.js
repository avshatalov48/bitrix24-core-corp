/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
this.BX.Crm.Entity = this.BX.Crm.Entity || {};
(function (exports,ui_designTokens,ui_notification,catalog_storeSelector,catalog_storeEnableWizard,catalog_productCalculator,main_popup,main_core_events,currency_currencyCore,catalog_productSelector,catalog_productModel,pull_client,main_core,spotlight,ui_tour,catalog_toolAvailabilityManager) {
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
	          animation: 'fading-slide',
	          autoHide: true
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
	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _row = /*#__PURE__*/new WeakMap();
	var _cache = /*#__PURE__*/new WeakMap();
	var _getDateNode = /*#__PURE__*/new WeakSet();
	var _getReserveInputNode = /*#__PURE__*/new WeakSet();
	var _layoutDateReservation = /*#__PURE__*/new WeakSet();
	var _isInventoryManagementMode1C = /*#__PURE__*/new WeakSet();
	var ReserveControl = /*#__PURE__*/function () {
	  function ReserveControl(options) {
	    babelHelpers.classCallCheck(this, ReserveControl);
	    _classPrivateMethodInitSpec(this, _isInventoryManagementMode1C);
	    _classPrivateMethodInitSpec(this, _layoutDateReservation);
	    _classPrivateMethodInitSpec(this, _getReserveInputNode);
	    _classPrivateMethodInitSpec(this, _getDateNode);
	    _classPrivateFieldInitSpec(this, _row, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    babelHelpers.defineProperty(this, "isReserveEqualProductQuantity", true);
	    babelHelpers.defineProperty(this, "wrapper", null);
	    babelHelpers.classPrivateFieldSet(this, _row, options.row);
	    this.inputFieldName = options.inputName || ReserveControl.INPUT_NAME;
	    this.viewName = ReserveControl.VIEW_NAME;
	    this.dateFieldName = options.dateFieldName || ReserveControl.DATE_NAME;
	    this.quantityFieldName = options.quantityFieldName || ReserveControl.QUANTITY_NAME;
	    this.deductedQuantityFieldName = options.deductedQuantityFieldName || ReserveControl.DEDUCTED_QUANTITY_NAME;
	    this.defaultDateReservation = options.defaultDateReservation || null;
	    this.isBlocked = options.isBlocked || false;
	    this.isInventoryManagementToolEnabled = options.isInventoryManagementToolEnabled || false;
	    this.inventoryManagementMode = options.inventoryManagementMode || '';
	    this.measureName = options.measureName;
	    this.isReserveEqualProductQuantity = options.isReserveEqualProductQuantity && (this.getReservedQuantity() === this.getQuantity() || babelHelpers.classPrivateFieldGet(this, _row).isNewRow());
	  }
	  babelHelpers.createClass(ReserveControl, [{
	    key: "renderTo",
	    value: function renderTo(node) {
	      this.wrapper = node;
	      main_core.Dom.append(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), _classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this)), this.wrapper);
	      main_core.Event.bind(_classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this).querySelector('input'), 'input', main_core.Runtime.debounce(this.onReserveInputChange, 800, this));
	      if (!_classPrivateMethodGet(this, _isInventoryManagementMode1C, _isInventoryManagementMode1C2).call(this)) {
	        if (this.getReservedQuantity() > 0 || this.isReserveEqualProductQuantity) {
	          _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, this.getDateReservation());
	        }
	        main_core.Dom.append(_classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this), this.wrapper);
	        main_core.Event.bind(_classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this), 'click', _classStaticPrivateMethodGet(ReserveControl, ReserveControl, _onDateInputClick).bind(this));
	        main_core.Event.bind(_classPrivateMethodGet(this, _getDateNode, _getDateNode2).call(this).querySelector('input'), 'change', this.onDateChange.bind(this));
	      }
	    }
	  }, {
	    key: "setReservedQuantity",
	    value: function setReservedQuantity(value, isTriggerEvent) {
	      var input = _classPrivateMethodGet(this, _getReserveInputNode, _getReserveInputNode2).call(this).querySelector('input');
	      if (input) {
	        input.value = value;
	        if (isTriggerEvent) {
	          input.dispatchEvent(new window.Event('input'));
	        }
	      }
	    }
	  }, {
	    key: "getReservedQuantity",
	    value: function getReservedQuantity() {
	      return main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _row).getField(this.inputFieldName));
	    }
	  }, {
	    key: "getDateReservation",
	    value: function getDateReservation() {
	      return babelHelpers.classPrivateFieldGet(this, _row).getField(this.dateFieldName) || '';
	    }
	  }, {
	    key: "getQuantity",
	    value: function getQuantity() {
	      return main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _row).getField(this.quantityFieldName));
	    }
	  }, {
	    key: "getDeductedQuantity",
	    value: function getDeductedQuantity() {
	      return main_core.Text.toNumber(babelHelpers.classPrivateFieldGet(this, _row).getField(this.deductedQuantityFieldName));
	    }
	  }, {
	    key: "getAvailableQuantity",
	    value: function getAvailableQuantity() {
	      return this.getQuantity() - this.getDeductedQuantity();
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
	      if (value > this.getAvailableQuantity()) {
	        var errorNotifyId = 'reserveCountError';
	        var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
	        if (!notify) {
	          var notificationOptions = {
	            id: errorNotifyId,
	            closeButton: true,
	            autoHideDelay: 3000,
	            content: main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_WITH_DEDUCTED_THEN_RESERVED'))
	          };
	          notify = BX.UI.Notification.Center.notify(notificationOptions);
	        }
	        notify.show();
	        value = this.getAvailableQuantity();
	        this.setReservedQuantity(value);
	      }
	      if (value > 0) {
	        var dateReservation = this.getDateReservation();
	        if (dateReservation === '') {
	          this.changeDateReservation(this.defaultDateReservation);
	        } else {
	          _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, dateReservation);
	        }
	      } else if (value <= 0) {
	        this.changeDateReservation();
	      }
	      this.setReservedQuantity(value, false);
	      babelHelpers.classPrivateFieldGet(this, _row).updateField(this.inputFieldName, value);
	    }
	  }, {
	    key: "clearCache",
	    value: function clearCache() {
	      babelHelpers.classPrivateFieldGet(this, _cache)["delete"]('dateInput');
	      babelHelpers.classPrivateFieldGet(this, _cache)["delete"]('reserveInput');
	    }
	  }, {
	    key: "isInputDisabled",
	    value: function isInputDisabled() {
	      if (this.isBlocked || !this.isInventoryManagementToolEnabled) {
	        return true;
	      }
	      var model = babelHelpers.classPrivateFieldGet(this, _row).getModel();
	      if (model) {
	        return model.isSimple() || model.isService();
	      }
	      return false;
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
	            content: main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_DATE_IN_PAST'))
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
	      var date = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	      if (date !== this.getDateReservation()) {
	        babelHelpers.classPrivateFieldGet(this, _row).updateField(this.dateFieldName, date);
	      }
	      _classPrivateMethodGet(this, _layoutDateReservation, _layoutDateReservation2).call(this, date);
	    }
	  }, {
	    key: "disable",
	    value: function disable(wrapper) {
	      var node = wrapper || this.wrapper;
	      if (node) {
	        node.innerHTML = this.getReservedQuantity() + ' ' + main_core.Text.encode(this.measureName);
	      }
	    }
	  }]);
	  return ReserveControl;
	}();
	function _onDateInputClick(event) {
	  BX.calendar({
	    node: event.target,
	    field: event.target.parentNode.querySelector('input'),
	    bTime: false
	  });
	}
	function _getDateNode2() {
	  var _this = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('dateInput', function () {
	    return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div>\n\t\t\t\t\t<a class=\"crm-entity-product-list-reserve-date\"></a>\n\t\t\t\t\t<input\n\t\t\t\t\t\tdata-name=\"", "\"\n\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\ttype=\"hidden\"\n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t"])), _this.dateFieldName, _this.dateFieldName, _this.getDateReservation());
	  });
	}
	function _getReserveInputNode2() {
	  var _this2 = this;
	  return babelHelpers.classPrivateFieldGet(this, _cache).remember('reserveInput', function () {
	    var viewReserveNode = _classPrivateMethodGet(_this2, _isInventoryManagementMode1C, _isInventoryManagementMode1C2).call(_this2) ? main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<span>\n\t\t\t\t\t\t\t<span data-name=\"", "\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t&nbsp;\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t"])), _this2.viewName, _this2.getReservedQuantity(), main_core.Text.encode(babelHelpers.classPrivateFieldGet(_this2, _row).getMeasureName())) : null;
	    var tag = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div ", ">\n\t\t\t\t\t", "\n\t\t\t\t\t<input type=\"", "\"\n\t\t\t\t\t\tdata-name=\"", "\"\n\t\t\t\t\t\tname=\"", "\"\n\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox ", "\"\n\t\t\t\t\t\tautoComplete=\"off\"\n\t\t\t\t\t\tvalue=\"", "\"\n\t\t\t\t\t\tplaceholder=\"0\"\n\t\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t\t", "\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t"])), _this2.isInputDisabled() ? 'class="crm-entity-product-list-locked-field-wrapper"' : '', viewReserveNode, _classPrivateMethodGet(_this2, _isInventoryManagementMode1C, _isInventoryManagementMode1C2).call(_this2) ? 'hidden' : 'text', _this2.inputFieldName, _this2.inputFieldName, _this2.isInputDisabled() ? 'crm-entity-product-list-locked-field' : '', _this2.getReservedQuantity(), _this2.getReservedQuantity(), _this2.isInputDisabled() ? 'disabled' : '');
	    if (_this2.isBlocked || !_this2.isInventoryManagementToolEnabled) {
	      tag.onclick = function () {
	        return main_core_events.EventEmitter.emit(_this2, 'onNodeClick');
	      };
	    }
	    return tag;
	  });
	}
	function _layoutDateReservation2() {
	  var date = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
	  var linkText = date === '' ? '' : main_core.Loc.getMessage('CRM_ENTITY_PL_RESERVED_DATE', {
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
	function _isInventoryManagementMode1C2() {
	  return this.inventoryManagementMode === catalog_storeEnableWizard.ModeList.MODE_1C;
	}
	babelHelpers.defineProperty(ReserveControl, "INPUT_NAME", 'INPUT_RESERVE_QUANTITY');
	babelHelpers.defineProperty(ReserveControl, "VIEW_NAME", 'VIEW_RESERVE_QUANTITY');
	babelHelpers.defineProperty(ReserveControl, "DATE_NAME", 'DATE_RESERVE_END');
	babelHelpers.defineProperty(ReserveControl, "QUANTITY_NAME", 'QUANTITY');
	babelHelpers.defineProperty(ReserveControl, "DEDUCTED_QUANTITY_NAME", 'DEDUCTED_QUANTITY');

	var _templateObject$2;
	function _classPrivateMethodInitSpec$1(obj, privateSet) { _checkPrivateRedeclaration$1(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _rowId = /*#__PURE__*/new WeakMap();
	var _model = /*#__PURE__*/new WeakMap();
	var _inventoryManagementMode = /*#__PURE__*/new WeakMap();
	var _node = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _createPopup = /*#__PURE__*/new WeakSet();
	var StoreAvailablePopup = /*#__PURE__*/function () {
	  function StoreAvailablePopup(options) {
	    babelHelpers.classCallCheck(this, StoreAvailablePopup);
	    _classPrivateMethodInitSpec$1(this, _createPopup);
	    _classPrivateFieldInitSpec$1(this, _rowId, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _model, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _inventoryManagementMode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _node, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$1(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _rowId, options.rowId);
	    babelHelpers.classPrivateFieldSet(this, _model, options.model);
	    babelHelpers.classPrivateFieldSet(this, _inventoryManagementMode, options.inventoryManagementMode);
	    this.setNode(options.node);
	  }
	  babelHelpers.createClass(StoreAvailablePopup, [{
	    key: "setNode",
	    value: function setNode(node) {
	      babelHelpers.classPrivateFieldSet(this, _node, node);
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(this, _node), 'store-available-popup-link');
	      main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _node), 'click', this.togglePopup.bind(this));
	    }
	  }, {
	    key: "getPopupContent",
	    value: function getPopupContent() {
	      var _this = this;
	      var storeId = babelHelpers.classPrivateFieldGet(this, _model).getField('STORE_ID');
	      var storeCollection = babelHelpers.classPrivateFieldGet(this, _model).getStoreCollection();
	      var storeQuantity = storeCollection.getStoreAmount(storeId);
	      var reservedQuantity = storeCollection.getStoreReserved(storeId);
	      var availableQuantity = storeCollection.getStoreAvailableAmount(storeId);
	      var renderHead = function renderHead(value) {
	        return "<td class=\"main-grid-cell-head main-grid-col-no-sortable main-grid-cell-right\">\n\t\t\t\t<div class=\"main-grid-cell-inner\">\n\t\t\t\t\t<span class=\"main-grid-cell-head-container\">".concat(value, "</span>\n\t\t\t\t</div>\n\t\t\t</td>");
	      };
	      var renderRow = function renderRow(value) {
	        return "<td class=\"main-grid-cell main-grid-cell-right\">\n\t\t\t\t<div class=\"main-grid-cell-inner\">\n\t\t\t\t\t<span class=\"main-grid-cell-content\">".concat(value, "</span>\n\t\t\t\t</div>\n\t\t\t</td>");
	      };
	      var isReservedQuantityLink = reservedQuantity > 0 && babelHelpers.classPrivateFieldGet(this, _inventoryManagementMode) !== catalog_storeEnableWizard.ModeList.MODE_1C;
	      var reservedQuantityContent = isReservedQuantityLink ? "<a href=\"#\" class=\"store-available-popup-reserves-slider-link\">".concat(reservedQuantity, "</a>") : reservedQuantity;
	      var viewAvailableQuantity = availableQuantity <= 0 ? "<span class=\"text--danger\">".concat(availableQuantity) : availableQuantity;
	      var result = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"store-available-popup-container\">\n\t\t\t\t<table class=\"main-grid-table\">\n\t\t\t\t\t<thead class=\"main-grid-header\">\n\t\t\t\t\t\t<tr class=\"main-grid-row-head\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t</thead>\n\t\t\t\t\t<tbody>\n\t\t\t\t\t\t<tr class=\"main-grid-row main-grid-row-body\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t</tbody>\n\t\t\t\t</table>\n\t\t\t</div>\n\t\t"])), renderHead(main_core.Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_COMMON_MSGVER_1')), renderHead(main_core.Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_RESERVED')), renderHead(main_core.Loc.getMessage('CRM_ENTITY_PL_STORE_AVAILABLE_POPUP_QUANTITY_AVAILABLE')), renderRow(storeQuantity), renderRow(reservedQuantityContent), renderRow(viewAvailableQuantity));
	      if (isReservedQuantityLink) {
	        main_core.Event.bind(result.querySelector('.store-available-popup-reserves-slider-link'), 'click', function (e) {
	          e.preventDefault();
	          _this.openDealsWithReservedProductSlider();
	        });
	      }
	      return result;
	    }
	  }, {
	    key: "openDealsWithReservedProductSlider",
	    value: function openDealsWithReservedProductSlider() {
	      var reservedDealsSliderLink = '/bitrix/components/bitrix/catalog.productcard.reserved.deal.list/slider.php';
	      var storeId = babelHelpers.classPrivateFieldGet(this, _model).getField('STORE_ID');
	      var productId = babelHelpers.classPrivateFieldGet(this, _model).getField('PRODUCT_ID');
	      var sliderLink = new main_core.Uri(reservedDealsSliderLink);
	      sliderLink.setQueryParam('productId', productId);
	      sliderLink.setQueryParam('storeId', storeId);
	      BX.SidePanel.Instance.open(sliderLink.toString(), {
	        allowChangeHistory: false,
	        cacheable: false
	      });
	    }
	  }, {
	    key: "togglePopup",
	    value: function togglePopup() {
	      if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	        if (babelHelpers.classPrivateFieldGet(this, _popup).isShown()) {
	          babelHelpers.classPrivateFieldGet(this, _popup).close();
	        } else {
	          babelHelpers.classPrivateFieldGet(this, _popup).setContent(this.getPopupContent());
	          babelHelpers.classPrivateFieldGet(this, _popup).show();
	        }
	      } else {
	        _classPrivateMethodGet$1(this, _createPopup, _createPopup2).call(this);
	        babelHelpers.classPrivateFieldGet(this, _popup).show();
	      }
	    }
	  }]);
	  return StoreAvailablePopup;
	}();
	function _createPopup2() {
	  var popupId = "store-available-popup-row-".concat(babelHelpers.classPrivateFieldGet(this, _rowId));
	  var popup = main_popup.PopupManager.getPopupById(popupId);
	  if (popup) {
	    babelHelpers.classPrivateFieldSet(this, _popup, popup);
	    babelHelpers.classPrivateFieldGet(this, _popup).setBindElement(babelHelpers.classPrivateFieldGet(this, _node));
	    babelHelpers.classPrivateFieldGet(this, _popup).setContent(this.getPopupContent());
	  } else {
	    babelHelpers.classPrivateFieldSet(this, _popup, main_popup.PopupManager.create({
	      id: popupId,
	      bindElement: babelHelpers.classPrivateFieldGet(this, _node),
	      autoHide: true,
	      draggable: false,
	      offsetLeft: -218,
	      offsetTop: 0,
	      angle: {
	        position: 'top',
	        offset: 250
	      },
	      noAllPaddings: true,
	      bindOptions: {
	        forceBindPosition: true
	      },
	      closeByEsc: true,
	      content: this.getPopupContent()
	    }));
	  }
	}

	var MoneyControl = /*#__PURE__*/function () {
	  function MoneyControl(options) {
	    babelHelpers.classCallCheck(this, MoneyControl);
	    this.node = options.node;
	    this.hint = options.hint;
	  }
	  babelHelpers.createClass(MoneyControl, [{
	    key: "enable",
	    value: function enable() {
	      var _this$node$querySelec;
	      this.node.removeAttribute('disabled');
	      this.node.removeAttribute('data-hint-no-icon');
	      this.node.removeAttribute('data-hint');
	      this.node.classList.remove('ui-ctl-element');
	      var currencyBlock = this.node.querySelector('.main-grid-editor-money-currency');
	      if (currencyBlock) {
	        currencyBlock.classList.add('main-dropdown');
	        currencyBlock.dataset.disabled = false;
	      }
	      (_this$node$querySelec = this.node.querySelector('.main-grid-editor-money-price')) === null || _this$node$querySelec === void 0 ? void 0 : _this$node$querySelec.removeAttribute('disabled');
	    }
	  }, {
	    key: "disable",
	    value: function disable() {
	      var _this$node$querySelec2;
	      this.node.setAttribute('disabled', '');
	      this.node.classList.add('ui-ctl-element');
	      (_this$node$querySelec2 = this.node.querySelector('.main-grid-editor-money-price')) === null || _this$node$querySelec2 === void 0 ? void 0 : _this$node$querySelec2.setAttribute('disabled', '');
	      var currencyBlock = this.node.querySelector('.main-grid-editor-money-currency');
	      if (currencyBlock) {
	        currencyBlock.classList.remove('main-dropdown');
	        currencyBlock.dataset.disabled = true;
	      }
	      if (this.hint) {
	        this.node.setAttribute('data-hint-no-icon', '');
	        this.node.setAttribute('data-hint', this.hint);
	        BX.UI.Hint.init(this.node.parentNode);
	      }
	    }
	  }]);
	  return MoneyControl;
	}();

	var _templateObject$3, _templateObject2$1, _templateObject3$1, _templateObject4$1;
	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function _classPrivateMethodInitSpec$2(obj, privateSet) { _checkPrivateRedeclaration$2(obj, privateSet); privateSet.add(obj); }
	function _checkPrivateRedeclaration$2(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var MODE_EDIT = 'EDIT';
	var MODE_SET = 'SET';
	var enableImageInputCache = new Map();
	var _initActions = /*#__PURE__*/new WeakSet();
	var _isEditableCatalogPrice = /*#__PURE__*/new WeakSet();
	var _isSaveableCatalogPrice = /*#__PURE__*/new WeakSet();
	var _initSelector = /*#__PURE__*/new WeakSet();
	var _onMainSelectorClear = /*#__PURE__*/new WeakSet();
	var _initStoreSelector = /*#__PURE__*/new WeakSet();
	var _initStoreAvailablePopup = /*#__PURE__*/new WeakSet();
	var _applyStoreSelectorRestrictionTweaks = /*#__PURE__*/new WeakSet();
	var _applyStoreSelectorToolAvailabilityTweaks = /*#__PURE__*/new WeakSet();
	var _initReservedControl = /*#__PURE__*/new WeakSet();
	var _onStoreFieldChange = /*#__PURE__*/new WeakSet();
	var _onStoreFieldClear = /*#__PURE__*/new WeakSet();
	var _onChangeStoreData = /*#__PURE__*/new WeakSet();
	var _onProductErrorsChange = /*#__PURE__*/new WeakSet();
	var _shouldShowSmallPriceHint = /*#__PURE__*/new WeakSet();
	var _togglePriceHintPopup = /*#__PURE__*/new WeakSet();
	var _getAllowedStores = /*#__PURE__*/new WeakSet();
	var _isReserveEqualProductQuantity = /*#__PURE__*/new WeakSet();
	var _getNodeChildByDataName = /*#__PURE__*/new WeakSet();
	var _getNodesChild = /*#__PURE__*/new WeakSet();
	var _needReserveControlInput = /*#__PURE__*/new WeakSet();
	var _needStoreSelectorInput = /*#__PURE__*/new WeakSet();
	var Row = /*#__PURE__*/function () {
	  function Row(_id, fields, settings, editor) {
	    babelHelpers.classCallCheck(this, Row);
	    _classPrivateMethodInitSpec$2(this, _needStoreSelectorInput);
	    _classPrivateMethodInitSpec$2(this, _needReserveControlInput);
	    _classPrivateMethodInitSpec$2(this, _getNodesChild);
	    _classPrivateMethodInitSpec$2(this, _getNodeChildByDataName);
	    _classPrivateMethodInitSpec$2(this, _isReserveEqualProductQuantity);
	    _classPrivateMethodInitSpec$2(this, _getAllowedStores);
	    _classPrivateMethodInitSpec$2(this, _togglePriceHintPopup);
	    _classPrivateMethodInitSpec$2(this, _shouldShowSmallPriceHint);
	    _classPrivateMethodInitSpec$2(this, _onProductErrorsChange);
	    _classPrivateMethodInitSpec$2(this, _onChangeStoreData);
	    _classPrivateMethodInitSpec$2(this, _onStoreFieldClear);
	    _classPrivateMethodInitSpec$2(this, _onStoreFieldChange);
	    _classPrivateMethodInitSpec$2(this, _initReservedControl);
	    _classPrivateMethodInitSpec$2(this, _applyStoreSelectorToolAvailabilityTweaks);
	    _classPrivateMethodInitSpec$2(this, _applyStoreSelectorRestrictionTweaks);
	    _classPrivateMethodInitSpec$2(this, _initStoreAvailablePopup);
	    _classPrivateMethodInitSpec$2(this, _initStoreSelector);
	    _classPrivateMethodInitSpec$2(this, _onMainSelectorClear);
	    _classPrivateMethodInitSpec$2(this, _initSelector);
	    _classPrivateMethodInitSpec$2(this, _isSaveableCatalogPrice);
	    _classPrivateMethodInitSpec$2(this, _isEditableCatalogPrice);
	    _classPrivateMethodInitSpec$2(this, _initActions);
	    babelHelpers.defineProperty(this, "fields", {});
	    babelHelpers.defineProperty(this, "externalActions", []);
	    babelHelpers.defineProperty(this, "handleChangeStoreData", _classPrivateMethodGet$2(this, _onChangeStoreData, _onChangeStoreData2).bind(this));
	    babelHelpers.defineProperty(this, "handleProductErrorsChange", main_core.Runtime.debounce(_classPrivateMethodGet$2(this, _onProductErrorsChange, _onProductErrorsChange2), 500, this));
	    babelHelpers.defineProperty(this, "handleMainSelectorClear", main_core.Runtime.debounce(_classPrivateMethodGet$2(this, _onMainSelectorClear, _onMainSelectorClear2).bind(this), 500, this));
	    babelHelpers.defineProperty(this, "handleStoreFieldChange", main_core.Runtime.debounce(_classPrivateMethodGet$2(this, _onStoreFieldChange, _onStoreFieldChange2).bind(this), 500, this));
	    babelHelpers.defineProperty(this, "handleStoreFieldClear", main_core.Runtime.debounce(_classPrivateMethodGet$2(this, _onStoreFieldClear, _onStoreFieldClear2).bind(this), 500, this));
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
	    _classPrivateMethodGet$2(this, _initActions, _initActions2).call(this);
	    _classPrivateMethodGet$2(this, _initSelector, _initSelector2).call(this);
	    _classPrivateMethodGet$2(this, _initStoreSelector, _initStoreSelector2).call(this);
	    _classPrivateMethodGet$2(this, _initStoreAvailablePopup, _initStoreAvailablePopup2).call(this);
	    _classPrivateMethodGet$2(this, _initReservedControl, _initReservedControl2).call(this);
	    this.modifyBasePriceInput();
	    this.modifyQuantityInput();
	    this.refreshFieldsLayout();
	    this.updateUiStoreAmountData();
	    this.initHandlersForSelectors();
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
	    key: "isNewRow",
	    value: function isNewRow() {
	      return isNaN(+this.getField('ID'));
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
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler);
	        // disable drag-n-drop events for text fields
	        main_core.Event.bind(node, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	      });
	      this.getNode().querySelectorAll('select').forEach(function (node) {
	        main_core.Event.bind(node, 'change', editor.changeProductFieldHandler);
	        // disable drag-n-drop events for select fields
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
	      var selectorNames = ['MAIN_INFO', 'STORE_INFO', 'RESERVE_INFO'];
	      selectorNames.forEach(function (name) {
	        _this2.getNode().querySelectorAll('[data-name="' + name + '"] input[type="text"]').forEach(function (node) {
	          main_core.Event.bind(node, 'input', editor.changeProductFieldHandler);
	          main_core.Event.bind(node, 'change', editor.changeProductFieldHandler);
	          // disable drag-n-drop events for select fields
	          main_core.Event.bind(node, 'mousedown', function (event) {
	            return event.stopPropagation();
	          });
	        });
	      });
	    }
	  }, {
	    key: "unsubscribeCustomEvents",
	    value: function unsubscribeCustomEvents() {
	      if (this.mainSelector) {
	        this.mainSelector.unsubscribeEvents();
	        main_core_events.EventEmitter.unsubscribe(this.mainSelector, 'onClear', this.handleMainSelectorClear);
	      }
	      if (this.storeSelector) {
	        this.storeSelector.unsubscribeEvents();
	        main_core_events.EventEmitter.unsubscribe(this.storeSelector, 'onChange', this.handleStoreFieldChange);
	        main_core_events.EventEmitter.unsubscribe(this.storeSelector, 'onClear', this.handleStoreFieldClear);
	      }
	      main_core_events.EventEmitter.unsubscribe(this.model, 'onChangeStoreData', this.handleChangeStoreData);
	      main_core_events.EventEmitter.unsubscribe(this.model, 'onErrorsChange', this.handleProductErrorsChange);
	    }
	  }, {
	    key: "modifyBasePriceInput",
	    value: function modifyBasePriceInput() {
	      var priceNode = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'PRICE');
	      if (!priceNode) {
	        return;
	      }
	      var control = new MoneyControl({
	        node: priceNode,
	        hint: main_core.Loc.getMessage('CRM_ENTITY_PL_PRICE_CHANGING_RESTRICTED')
	      });
	      if (!_classPrivateMethodGet$2(this, _isEditableCatalogPrice, _isEditableCatalogPrice2).call(this)) {
	        control.disable();
	      } else {
	        control.enable();
	      }
	    }
	  }, {
	    key: "modifyQuantityInput",
	    value: function modifyQuantityInput() {
	      if (!this.isRestrictedStoreInfo()) {
	        return;
	      }
	      var countField = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'QUANTITY');
	      if (countField) {
	        var control = new MoneyControl({
	          node: countField,
	          hint: main_core.Loc.getMessage('CRM_ENTITY_PL_ROW_UPDATE_RESTRICTED_BY_STORE')
	        });
	        control.disable();
	      }
	    }
	  }, {
	    key: "layoutStoreSelector",
	    value: function layoutStoreSelector() {
	      var storeWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'STORE_INFO');
	      if (this.storeSelector && storeWrapper) {
	        storeWrapper.innerHTML = '';
	        if (_classPrivateMethodGet$2(this, _needStoreSelectorInput, _needStoreSelectorInput2).call(this)) {
	          this.storeSelector.renderTo(storeWrapper);
	          if (this.isReserveBlocked()) {
	            _classPrivateMethodGet$2(this, _applyStoreSelectorRestrictionTweaks, _applyStoreSelectorRestrictionTweaks2).call(this);
	          } else if (!this.isInventoryManagementToolEnabled()) {
	            _classPrivateMethodGet$2(this, _applyStoreSelectorToolAvailabilityTweaks, _applyStoreSelectorToolAvailabilityTweaks2).call(this);
	          }
	        }
	      }
	    }
	  }, {
	    key: "layoutReserveControl",
	    value: function layoutReserveControl() {
	      var storeWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'RESERVE_INFO');
	      if (storeWrapper && this.reserveControl) {
	        storeWrapper.innerHTML = '';
	        this.reserveControl.clearCache();
	        if (_classPrivateMethodGet$2(this, _needReserveControlInput, _needReserveControlInput2).call(this)) {
	          if (this.isRestrictedStoreInfo()) {
	            storeWrapper.innerHTML = this.reserveControl.getReservedQuantity() + ' ' + main_core.Text.encode(this.getMeasureName());
	            return;
	          }
	          this.reserveControl.renderTo(storeWrapper);
	        }
	      }
	    }
	  }, {
	    key: "clearReserveControl",
	    value: function clearReserveControl() {
	      var storeWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'RESERVE_INFO');
	      if (storeWrapper && this.reserveControl) {
	        storeWrapper.innerHTML = '';
	        this.reserveControl.clearCache();
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
	          this.getModel().setField(name, fields[name]);
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
	    key: "isEmptyRow",
	    value: function isEmptyRow() {
	      return !main_core.Type.isStringFilled(this.getField('NAME', '').trim()) && this.model.isEmpty() && this.getBasePrice() <= 0;
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
	      var mode = event.type === 'input' || event.type === 'change' ? MODE_EDIT : MODE_SET;
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
	        case 'INPUT_RESERVE_QUANTITY':
	          this.changeReserveQuantity(value);
	          break;
	        case 'DATE_RESERVE_END':
	          this.changeDateReserveEnd(value);
	          break;
	        case 'PRICE':
	        case 'BASE_PRICE':
	          this.changeBasePrice(value, mode);
	          break;
	        case 'DEDUCTED_QUANTITY':
	          this.setDeductedQuantity(value);
	          break;
	        case 'ROW_RESERVED':
	          this.setRowReserved(value);
	          break;
	        case 'TYPE':
	          this.setType(value);
	          break;
	        case 'SKU_TREE':
	        case 'DETAIL_URL':
	        case 'IMAGE_INFO':
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
	        if (!taxRate) {
	          taxRate = taxList.find(function (item) {
	            return main_core.Type.isNil(item.VALUE);
	          });
	        }
	        if (taxRate) {
	          this.changeTaxRate(taxRate.VALUE);
	        }
	      }
	    }
	  }, {
	    key: "changeTaxRate",
	    value: function changeTaxRate(value) {
	      var preparedValue = main_core.Type.isNil(value) || value === '' ? null : this.parseFloat(value, this.getCommonPrecision());
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
	      this.setField('STORE_AVAILABLE', this.model.getStoreCollection().getStoreAvailableAmount(value));
	      this.updateUiStoreAmountData();
	      this.layoutReserveControl();
	      this.addActionProductChange();
	      this.initHandlersForSelectors();
	    }
	  }, {
	    key: "updateUiStoreAmountData",
	    value: function updateUiStoreAmountData() {
	      var availableWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'STORE_AVAILABLE');
	      if (!main_core.Type.isDomNode(availableWrapper)) {
	        return;
	      }
	      var storeId = this.getField('STORE_ID');
	      if (!storeId) {
	        return;
	      }
	      var available = this.model.getStoreCollection().getStoreAvailableAmount(storeId);
	      var amount = main_core.Text.toNumber(available);
	      var amountWithMeasure = '';
	      if (!this.getModel().isCatalogExisted() || this.isRestrictedStoreInfo() || this.getModel().isService()) {
	        return;
	      }
	      amountWithMeasure = amount + ' ' + this.getMeasureName();
	      availableWrapper.innerHTML = amount > 0 ? amountWithMeasure : "<span class=\"store-available-popup-link--danger\">".concat(amountWithMeasure, "</span>");
	    }
	  }, {
	    key: "updatePropertyFields",
	    value: function updatePropertyFields() {
	      var productProps = this.model.getField('PRODUCT_PROPERTIES');
	      for (var property in productProps) {
	        var availableWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, property);
	        if (availableWrapper) {
	          var _this$model$getField$;
	          var value = (_this$model$getField$ = this.model.getField('PRODUCT_PROPERTIES')[property]) !== null && _this$model$getField$ !== void 0 ? _this$model$getField$ : '';
	          availableWrapper.innerHTML = value;
	        }
	      }
	    }
	  }, {
	    key: "clearPropertyFields",
	    value: function clearPropertyFields() {
	      var propNodes = _classPrivateMethodGet$2(this, _getNodesChild, _getNodesChild2).call(this);
	      propNodes.forEach(function (property) {
	        property.innerHTML = '';
	      });
	    }
	  }, {
	    key: "setRowReserved",
	    value: function setRowReserved(value) {
	      this.setField('ROW_RESERVED', value);
	      var reserveWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'ROW_RESERVED');
	      if (!main_core.Type.isDomNode(reserveWrapper)) {
	        return;
	      }
	      if (!this.getModel().isCatalogExisted() || this.getModel().isService()) {
	        reserveWrapper.innerHTML = '';
	        return;
	      }
	      reserveWrapper.innerHTML = main_core.Text.toNumber(this.getField('ROW_RESERVED')) + ' ' + this.getMeasureName();
	    }
	  }, {
	    key: "setDeductedQuantity",
	    value: function setDeductedQuantity(value) {
	      this.setField('DEDUCTED_QUANTITY', value);
	      var deductedWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'DEDUCTED_QUANTITY');
	      if (!main_core.Type.isDomNode(deductedWrapper)) {
	        return;
	      }
	      if (!this.getModel().isCatalogExisted() || this.getModel().isService()) {
	        deductedWrapper.innerHTML = '';
	        return;
	      }
	      deductedWrapper.innerHTML = main_core.Text.toNumber(this.getField('DEDUCTED_QUANTITY')) + ' ' + this.getMeasureName();
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
	      var reserveDifference = preparedValue - this.getField('INPUT_RESERVE_QUANTITY');
	      if (reserveDifference === 0 || isNaN(reserveDifference)) {
	        return;
	      }
	      var newReserve = this.getField('ROW_RESERVED') + reserveDifference;
	      this.setField('ROW_RESERVED', newReserve);
	      this.setField('RESERVE_QUANTITY', Math.max(newReserve, 0));
	      this.setField('INPUT_RESERVE_QUANTITY', preparedValue);
	      this.addActionProductChange();
	    }
	  }, {
	    key: "resetReserveFields",
	    value: function resetReserveFields() {
	      this.setField('ROW_RESERVED', null);
	      this.setField('RESERVE_QUANTITY', null);
	      this.setField('INPUT_RESERVE_QUANTITY', null);
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
	        var _fields$STORE_MAP;
	        this.model = new catalog_productModel.ProductModel({
	          id: selectorId,
	          currency: this.getEditor().getCurrencyId(),
	          iblockId: fields['IBLOCK_ID'],
	          basePriceId: fields['BASE_PRICE_ID'],
	          isSimpleModel: main_core.Text.toInteger(fields['PRODUCT_ID']) <= 0 && main_core.Type.isStringFilled(fields['NAME']),
	          skuTree: main_core.Type.isStringFilled(fields['SKU_TREE']) ? JSON.parse(fields['SKU_TREE']) : null,
	          storeMap: (_fields$STORE_MAP = fields['STORE_MAP']) !== null && _fields$STORE_MAP !== void 0 ? _fields$STORE_MAP : {},
	          fields: fields
	        });
	        if (!main_core.Type.isNil(fields['DETAIL_URL'])) {
	          this.model.setDetailPath(fields['DETAIL_URL']);
	        }
	      }

	      // fill after change setting show pictures.
	      var imageInfo = main_core.Type.isStringFilled(fields['IMAGE_INFO']) ? JSON.parse(fields['IMAGE_INFO']) : null;
	      if (main_core.Type.isObject(imageInfo)) {
	        this.model.getImageCollection().setPreview(imageInfo['preview']);
	        this.model.getImageCollection().setEditInput(imageInfo['input']);
	        this.model.getImageCollection().setMorePhotoValues(imageInfo['values']);
	      }
	      if (_classPrivateMethodGet$2(this, _isReserveEqualProductQuantity, _isReserveEqualProductQuantity2).call(this)) {
	        if (!this.getModel().getField('DATE_RESERVE_END')) {
	          this.setField('DATE_RESERVE_END', this.editor.getSettingValue('defaultDateReservation'));
	        }
	      }
	      main_core_events.EventEmitter.subscribe(this.model, 'onErrorsChange', this.handleProductErrorsChange);
	      main_core_events.EventEmitter.subscribe(this.model, 'onChangeStoreData', this.handleChangeStoreData);
	    }
	  }, {
	    key: "getModel",
	    value: function getModel() {
	      return this.model;
	    }
	  }, {
	    key: "setProductId",
	    value: function setProductId(value) {
	      var _this4 = this;
	      var isChangedValue = this.getField('PRODUCT_ID') !== value;
	      if (isChangedValue) {
	        var _this$storeSelector;
	        this.getModel().setOption('isSimpleModel', value <= 0 && main_core.Type.isStringFilled(this.getField('NAME')));
	        this.setField('PRODUCT_ID', value, false);
	        this.setField('OFFER_ID', value, false);
	        (_this$storeSelector = this.storeSelector) === null || _this$storeSelector === void 0 ? void 0 : _this$storeSelector.setProductId(value);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	        if (this.reserveControl && _classPrivateMethodGet$2(this, _isReserveEqualProductQuantity, _isReserveEqualProductQuantity2).call(this) && _classPrivateMethodGet$2(this, _needReserveControlInput, _needReserveControlInput2).call(this)) {
	          if (!this.getModel().getField('DATE_RESERVE_END')) {
	            this.setField('DATE_RESERVE_END', this.editor.getSettingValue('defaultDateReservation'));
	          }
	          this.resetReserveFields();
	          this.onAfterExecuteExternalActions = function () {
	            _this4.reserveControl.changeInputValue(_this4.getField('QUANTITY'));
	          };
	        }
	      }
	    }
	  }, {
	    key: "changeBasePrice",
	    value: function changeBasePrice(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      if (mode === MODE_EDIT && !_classPrivateMethodGet$2(this, _isEditableCatalogPrice, _isEditableCatalogPrice2).call(this)) {
	        value = this.getField('BASE_PRICE');
	        this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
	        return;
	      }
	      var originalPrice = value;
	      // price can't be less than zero
	      value = Math.max(value, 0);
	      if (mode === MODE_SET) {
	        this.updateUiInputField('PRICE', value.toFixed(this.getPricePrecision()));
	      }
	      var isChangedValue = this.getBasePrice() !== value;
	      if (isChangedValue) {
	        var calculatedFields = this.getCalculator().calculateBasePrice(value);
	        this.setFields(calculatedFields);
	        var exceptFieldNames = mode === MODE_EDIT ? ['BASE_PRICE', 'PRICE'] : [];
	        this.refreshFieldsLayout(exceptFieldNames);
	        this.addActionProductChange();
	        this.addActionUpdateTotal();
	      }
	      _classPrivateMethodGet$2(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this, originalPrice < 0 && originalPrice !== value);
	    }
	  }, {
	    key: "setQuantity",
	    value: function setQuantity(value) {
	      var mode = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : MODE_SET;
	      if (mode === MODE_SET) {
	        this.updateUiInputField('QUANTITY', value);
	      }
	      var isChangedValue = this.getField('QUANTITY') !== value;
	      if (isChangedValue) {
	        var errorNotifyId = 'quantityReservedCountError';
	        var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
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
	      var node = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'RESERVE_INFO');
	      var input = node === null || node === void 0 ? void 0 : node.querySelector('input[name="INPUT_RESERVE_QUANTITY"]');
	      if (main_core.Type.isElementNode(input)) {
	        var _this$reserveControl;
	        input.value = value;
	        var view = node === null || node === void 0 ? void 0 : node.querySelector('span[data-name="VIEW_RESERVE_QUANTITY"]');
	        if (view) {
	          view.textContent = value;
	        }
	        (_this$reserveControl = this.reserveControl) === null || _this$reserveControl === void 0 ? void 0 : _this$reserveControl.changeInputValue(value);
	      } else {
	        this.changeReserveQuantity(value);
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
	      _classPrivateMethodGet$2(this, _togglePriceHintPopup, _togglePriceHintPopup2).call(this);
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
	      if (dropdown.getValue() === value) {
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
	      var uiType = this.getUiFieldType(uiName);
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
	          } else if (field === 'DISCOUNT_RATE') {
	            value = this.parseFloat(value, this.getCommonPrecision());
	          } else if (field === 'TAX_RATE') {
	            value = main_core.Type.isNil(value) || value === '' ? '' : this.parseFloat(value, this.getCommonPrecision());
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
	        case 'BASE_PRICE':
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
	        case 'QUANTITY':
	        case 'TAX_RATE':
	        case 'DISCOUNT_PRICE':
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
	      if (this.onAfterExecuteExternalActions) {
	        var callback = this.onAfterExecuteExternalActions;
	        this.onAfterExecuteExternalActions = null;
	        callback.call();
	      }
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
	  }, {
	    key: "isInventoryManagementToolEnabled",
	    value: function isInventoryManagementToolEnabled() {
	      return this.getSettingValue('isInventoryManagementToolEnabled', true);
	    }
	  }, {
	    key: "getInventoryManagementMode",
	    value: function getInventoryManagementMode() {
	      return this.getSettingValue('inventoryManagementMode', '');
	    }
	  }, {
	    key: "isRestrictedStoreInfo",
	    value: function isRestrictedStoreInfo() {
	      var _this$getField;
	      if (!this.editor.getSettingValue('allowReservation', true)) {
	        return false;
	      }
	      var storeId = (_this$getField = this.getField('STORE_ID')) === null || _this$getField === void 0 ? void 0 : _this$getField.toString();
	      if (main_core.Type.isNil(storeId) || storeId === '0') {
	        return false;
	      } else if (this.getModel().isSimple() || this.getModel().isService()) {
	        return false;
	      }
	      return !_classPrivateMethodGet$2(this, _getAllowedStores, _getAllowedStores2).call(this).includes(storeId);
	    }
	  }, {
	    key: "getMeasureName",
	    value: function getMeasureName() {
	      var _this$editor$getDefau;
	      var measureName = main_core.Type.isStringFilled(this.model.getField('MEASURE_NAME')) ? this.model.getField('MEASURE_NAME') : ((_this$editor$getDefau = this.editor.getDefaultMeasure()) === null || _this$editor$getDefau === void 0 ? void 0 : _this$editor$getDefau.SYMBOL) || '';
	      return main_core.Text.encode(measureName);
	    }
	  }, {
	    key: "setType",
	    value: function setType(value) {
	      this.setField('TYPE', value);
	      if (this.getModel().isService()) {
	        this.clearReserveControl();
	      }
	    }
	  }]);
	  return Row;
	}();
	function _initActions2() {
	  var _this7 = this;
	  if (this.getEditor().isReadOnly() || this.isRestrictedStoreInfo()) {
	    return;
	  }
	  var actionCellContentContainer = this.getNode().querySelector('.main-grid-cell-action .main-grid-cell-content');
	  if (main_core.Type.isDomNode(actionCellContentContainer)) {
	    var actionsButton = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<a\n\t\t\t\t\thref=\"#\"\n\t\t\t\t\tclass=\"main-grid-row-action-button\"\n\t\t\t\t></a>\n\t\t\t"])));
	    main_core.Event.bind(actionsButton, 'click', function (event) {
	      var menuItems = [{
	        text: main_core.Loc.getMessage('CRM_ENTITY_PL_COPY'),
	        onclick: _this7.handleCopyAction.bind(_this7),
	        disabled: _this7.editor.getSettingValue('disabledSelectProductInput')
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
	function _isEditableCatalogPrice2() {
	  return this.editor.canEditCatalogPrice() || !this.getModel().isCatalogExisted() || this.getModel().isNew();
	}
	function _initSelector2() {
	  var id = 'crm_grid_' + this.getId();
	  var enableImageInput = this.editor.getSettingValue('enableSelectProductImageInput', true);
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
	        ENABLE_IMAGE_INPUT: enableImageInput,
	        ROLLBACK_INPUT_AFTER_CANCEL: true,
	        ENABLE_INPUT_DETAIL_LINK: true,
	        ROW_ID: this.getId(),
	        ENABLE_SKU_SELECTION: true,
	        ENABLE_EMPTY_PRODUCT_ERROR: false,
	        SELECTOR_INPUT_DISABLED: this.editor.getSettingValue('disabledSelectProductInput'),
	        URL_BUILDER_CONTEXT: this.editor.getSettingValue('productUrlBuilderContext'),
	        RESTRICTED_PRODUCT_TYPES: this.getEditor().getRestrictedProductTypes()
	      },
	      mode: catalog_productSelector.ProductSelector.MODE_EDIT
	    };
	    this.mainSelector = new catalog_productSelector.ProductSelector('crm_grid_' + this.getId(), selectorOptions);
	  } else {
	    this.mainSelector.subscribeEvents();
	    if (enableImageInput !== enableImageInputCache[id]) {
	      this.mainSelector.setConfig('ENABLE_IMAGE_INPUT', enableImageInput);
	      if (enableImageInput) {
	        this.mainSelector.layoutImage();
	      }
	    }
	  }
	  enableImageInputCache[id] = enableImageInput;
	  if (this.isRestrictedStoreInfo()) {
	    this.mainSelector.setMode(catalog_productSelector.ProductSelector.MODE_VIEW);
	  }
	  var mainInfoNode = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'MAIN_INFO');
	  if (mainInfoNode) {
	    var numberSelector = mainInfoNode.querySelector('.main-grid-row-number');
	    if (!main_core.Type.isDomNode(numberSelector)) {
	      main_core.Dom.append(main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"main-grid-row-number\"></div>"]))), mainInfoNode);
	    }
	    var selectorWrapper = mainInfoNode.querySelector('.main-grid-row-product-selector');
	    if (!main_core.Type.isDomNode(selectorWrapper)) {
	      selectorWrapper = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"main-grid-row-product-selector\"></div>"])));
	      main_core.Dom.append(selectorWrapper, mainInfoNode);
	    }
	    this.mainSelector.skuTreeInstance = null;
	    if (this.editor.isVisible()) {
	      this.mainSelector.renderTo(selectorWrapper);
	    } else {
	      this.mainSelector.wrapper = selectorWrapper;
	    }
	  }
	  main_core_events.EventEmitter.subscribe(this.mainSelector, 'onClear', this.handleMainSelectorClear);
	}
	function _onMainSelectorClear2() {
	  this.updateField('OFFER_ID', 0);
	  this.updateField('PRODUCT_NAME', '');
	  this.updateUiStoreAmountData();
	  this.updateField('DEDUCTED_QUANTITY', 0);
	  this.updateField('ROW_RESERVED', 0);
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
	  main_core_events.EventEmitter.subscribe(this.storeSelector, 'onChange', this.handleStoreFieldChange);
	  main_core_events.EventEmitter.subscribe(this.storeSelector, 'onClear', this.handleStoreFieldClear);
	  if (this.isRestrictedStoreInfo() && this.storeSelector.searchInput) {
	    this.storeSelector.searchInput.disable(main_core.Loc.getMessage('CRM_ENTITY_PL_ROW_UPDATE_STORE_RESTRICTED_BY_STORE'));
	  }
	  this.layoutStoreSelector();
	}
	function _initStoreAvailablePopup2() {
	  var storeAvaiableNode = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'STORE_AVAILABLE');
	  if (!storeAvaiableNode) {
	    return;
	  }
	  this.storeAvailablePopup = new StoreAvailablePopup({
	    rowId: this.id,
	    model: this.getModel(),
	    node: storeAvaiableNode,
	    inventoryManagementMode: this.getInventoryManagementMode()
	  });
	}
	function _applyStoreSelectorRestrictionTweaks2() {
	  var _this8 = this;
	  var storeSearchInput = this.storeSelector.searchInput;
	  if (!storeSearchInput || !storeSearchInput.getNameInput()) {
	    return;
	  }
	  storeSearchInput.toggleIcon(this.storeSelector.searchInput.getSearchIcon(), 'none');
	  storeSearchInput.getNameInput().disabled = true;
	  main_core.Dom.addClass(storeSearchInput.getNameInput(), 'crm-entity-product-list-locked-field');
	  if (this.storeSelector.getWrapper()) {
	    main_core.Dom.addClass(this.storeSelector.getWrapper(), 'crm-entity-product-list-locked-field-wrapper');
	    main_core.Event.bind(this.storeSelector.getWrapper(), 'click', function () {
	      _this8.editor.openIntegrationLimitSlider();
	    });
	  }
	}
	function _applyStoreSelectorToolAvailabilityTweaks2() {
	  var _this9 = this;
	  var storeSearchInput = this.storeSelector.searchInput;
	  if (!storeSearchInput || !storeSearchInput.getNameInput()) {
	    return;
	  }
	  storeSearchInput.toggleIcon(this.storeSelector.searchInput.getSearchIcon(), 'none');
	  storeSearchInput.getNameInput().disabled = true;
	  main_core.Dom.addClass(storeSearchInput.getNameInput(), 'crm-entity-product-list-locked-field');
	  if (this.storeSelector.getWrapper()) {
	    main_core.Dom.addClass(this.storeSelector.getWrapper(), 'crm-entity-product-list-locked-field-wrapper');
	    main_core.Event.bind(this.storeSelector.getWrapper(), 'click', function () {
	      _this9.editor.openInventoryManagementToolDisabledSlider();
	    });
	  }
	}
	function _initReservedControl2() {
	  var _this10 = this;
	  var storeWrapper = _classPrivateMethodGet$2(this, _getNodeChildByDataName, _getNodeChildByDataName2).call(this, 'RESERVE_INFO');
	  if (storeWrapper && _classPrivateMethodGet$2(this, _getAllowedStores, _getAllowedStores2).call(this).length) {
	    this.reserveControl = new ReserveControl({
	      row: this,
	      isReserveEqualProductQuantity: _classPrivateMethodGet$2(this, _isReserveEqualProductQuantity, _isReserveEqualProductQuantity2).call(this),
	      defaultDateReservation: this.editor.getSettingValue('defaultDateReservation'),
	      isInventoryManagementToolEnabled: this.isInventoryManagementToolEnabled(),
	      inventoryManagementMode: this.getInventoryManagementMode(),
	      isBlocked: this.isReserveBlocked(),
	      measureName: this.getMeasureName()
	    });
	    main_core_events.EventEmitter.subscribe(this.reserveControl, 'onNodeClick', function () {
	      if (_this10.isReserveBlocked()) {
	        _this10.editor.openIntegrationLimitSlider();
	      } else if (!_this10.isInventoryManagementToolEnabled()) {
	        _this10.editor.openInventoryManagementToolDisabledSlider();
	      }
	    });
	    if (this.isRestrictedStoreInfo()) {
	      this.reserveControl.disable();
	    }
	    this.layoutReserveControl();
	  }
	  var quantityInput = this.getNode().querySelector('div[data-name="QUANTITY"] input');
	  if (quantityInput) {
	    main_core.Event.bind(quantityInput, 'change', function (event) {
	      var _this10$reserveContro;
	      var isReserveEqualProductQuantity = _classPrivateMethodGet$2(_this10, _isReserveEqualProductQuantity, _isReserveEqualProductQuantity2).call(_this10) && ((_this10$reserveContro = _this10.reserveControl) === null || _this10$reserveContro === void 0 ? void 0 : _this10$reserveContro.isReserveEqualProductQuantity);
	      if (isReserveEqualProductQuantity) {
	        _this10.setReserveQuantity(_this10.getField('QUANTITY'));
	        return;
	      }
	      var value = main_core.Text.toNumber(event.target.value);
	      var errorNotifyId = 'quantityReservedCountError';
	      var notify = BX.UI.Notification.Center.getBalloonById(errorNotifyId);
	      if (value < _this10.getField('INPUT_RESERVE_QUANTITY')) {
	        if (!notify) {
	          var notificationOptions = {
	            id: errorNotifyId,
	            closeButton: true,
	            autoHideDelay: 3000,
	            content: main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), main_core.Loc.getMessage('CRM_ENTITY_PL_IS_LESS_QUANTITY_THEN_RESERVED'))
	          };
	          notify = BX.UI.Notification.Center.notify(notificationOptions);
	        }
	        _this10.setReserveQuantity(_this10.getField('QUANTITY'));
	        notify.show();
	      }
	    });
	  }
	}
	function _onStoreFieldChange2(event) {
	  var _this11 = this;
	  var data = event.getData();
	  data.fields.forEach(function (item) {
	    _this11.updateField(item.NAME, item.VALUE);
	  });
	  this.initHandlersForSelectors();
	}
	function _onStoreFieldClear2() {
	  this.initHandlersForSelectors();
	}
	function _onChangeStoreData2() {
	  var storeId = this.getField('STORE_ID');
	  if (!this.isReserveBlocked() && this.isNewRow() && this.storeSelector) {
	    var currentAmount = this.getModel().getStoreCollection().getStoreAmount(storeId);
	    if (currentAmount <= 0 && this.getModel().isChanged()) {
	      var maxStore = this.getModel().getStoreCollection().getMaxFilledStore();
	      if (maxStore.AMOUNT > currentAmount) {
	        this.storeSelector.onStoreSelect(maxStore.STORE_ID, main_core.Text.decode(maxStore.STORE_TITLE));
	      } else if (main_core.Type.isNil(storeId)) {
	        storeId = +this.storeSelector.getStoreId();
	        if (storeId > 0) {
	          this.changeStore(storeId);
	        }
	      }
	    }
	  }
	  this.setField('STORE_AVAILABLE', this.model.getStoreCollection().getStoreAvailableAmount(storeId));
	  this.updateUiStoreAmountData();
	}
	function _onProductErrorsChange2() {
	  this.getEditor().handleProductErrorsChange();
	}
	function _shouldShowSmallPriceHint2() {
	  return main_core.Text.toNumber(this.getField('PRICE')) > 0 && main_core.Text.toNumber(this.getField('PRICE')) < 1 && this.isDiscountPercentage() && (main_core.Text.toNumber(this.getField('DISCOUNT_SUM')) > 0 || main_core.Text.toNumber(this.getField('DISCOUNT_RATE')) > 0 || main_core.Text.toNumber(this.getField('DISCOUNT_ROW')) > 0);
	}
	function _togglePriceHintPopup2() {
	  var showNegative = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	  if (_classPrivateMethodGet$2(this, _shouldShowSmallPriceHint, _shouldShowSmallPriceHint2).call(this)) {
	    this.getHintPopup().load(this.getInputByFieldName('PRICE'), main_core.Loc.getMessage('CRM_ENTITY_PL_SMALL_PRICE_NOTICE')).show();
	  } else if (showNegative) {
	    this.getHintPopup().load(this.getInputByFieldName('PRICE'), main_core.Loc.getMessage('CRM_ENTITY_PL_NEGATIVE_PRICE_NOTICE')).show();
	  } else {
	    this.getHintPopup().close();
	  }
	}
	function _getAllowedStores2() {
	  return this.editor.getSettingValue('allowedStores', []);
	}
	function _isReserveEqualProductQuantity2() {
	  return this.editor.getSettingValue('isReserveEqualProductQuantity', false);
	}
	function _getNodeChildByDataName2(name) {
	  return this.getNode().querySelector("[data-name=\"".concat(name, "\"]"));
	}
	function _getNodesChild2() {
	  return this.getNode().querySelectorAll("span[data-name]");
	}
	function _needReserveControlInput2() {
	  return !this.getModel().isSimple() && !this.getModel().isService();
	}
	function _needStoreSelectorInput2() {
	  return !this.getModel().isSimple() && !this.getModel().isService();
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

	var _templateObject$4, _templateObject2$2, _templateObject3$2, _templateObject4$2, _templateObject5$1;
	function _classPrivateMethodInitSpec$3(obj, privateSet) { _checkPrivateRedeclaration$3(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$2(obj, privateMap, value) { _checkPrivateRedeclaration$3(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$3(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
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
	    _classPrivateMethodInitSpec$3(this, _showNotification);
	    _classPrivateMethodInitSpec$3(this, _setSetting);
	    _classPrivateMethodInitSpec$3(this, _getSettingItem);
	    _classPrivateMethodInitSpec$3(this, _prepareSettingsContent);
	    _classPrivateFieldInitSpec$2(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _settings, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _editor, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$2(this, _cache$1, {
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
	          content: _classPrivateMethodGet$3(_this, _prepareSettingsContent, _prepareSettingsContent2).call(_this)
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
	        _classPrivateMethodGet$3(_this2, _showNotification, _showNotification2).call(_this2, message, {
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
	  var content = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class='ui-entity-editor-popup-create-field-list'></div>\n\t\t"])));
	  babelHelpers.classPrivateFieldGet(this, _settings).forEach(function (item) {
	    content.append(_classPrivateMethodGet$3(_this4, _getSettingItem, _getSettingItem2).call(_this4, item));
	  });
	  return content;
	}
	function _getSettingItem2(item) {
	  var _item$disabled;
	  var input = main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"checkbox\">\n\t\t"])));
	  input.checked = item.checked;
	  input.disabled = (_item$disabled = item.disabled) !== null && _item$disabled !== void 0 ? _item$disabled : false;
	  input.dataset.settingId = item.id;
	  var descriptionNode = main_core.Type.isStringFilled(item.desc) ? main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-entity-editor-popup-create-field-item-desc\">", "</span>"])), item.desc) : '';
	  var hintNode = main_core.Type.isStringFilled(item.hint) ? main_core.Tag.render(_templateObject4$2 || (_templateObject4$2 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-entity-product-list-setting-hint\" data-hint=\"", "\"></span>"])), item.hint) : '';
	  var setting = main_core.Tag.render(_templateObject5$1 || (_templateObject5$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<label class=\"ui-ctl-block ui-entity-editor-popup-create-field-item ui-ctl-w100\">\n\t\t\t\t<div class=\"ui-ctl-w10\" style=\"text-align: center\">", "</div>\n\t\t\t\t<div class=\"ui-ctl-w75\">\n\t\t\t\t\t<span class=\"ui-entity-editor-popup-create-field-item-title ", "\">", "", "</span>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</label>\n\t\t"])), input, item.disabled ? 'crm-entity-product-list-disabled-setting' : '', item.title, hintNode, descriptionNode);
	  BX.UI.Hint.init(setting);
	  main_core.Event.bind(setting, 'change', _classPrivateMethodGet$3(this, _setSetting, _setSetting2).bind(this));
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

	function _classPrivateMethodInitSpec$4(obj, privateSet) { _checkPrivateRedeclaration$4(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$3(obj, privateMap, value) { _checkPrivateRedeclaration$4(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$4(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _gridGetter = /*#__PURE__*/new WeakMap();
	var _contentContainer = /*#__PURE__*/new WeakMap();
	var _bindGridNodeVisionChange = /*#__PURE__*/new WeakSet();
	var _getPossibleToValidateFieldNodes = /*#__PURE__*/new WeakSet();
	var _fieldNodeIsInGridVision = /*#__PURE__*/new WeakSet();
	var _bindSpotlightToNode = /*#__PURE__*/new WeakSet();
	var _freezeGridContainer = /*#__PURE__*/new WeakSet();
	var _tieTourToNode = /*#__PURE__*/new WeakSet();
	var FieldHintManager = /*#__PURE__*/function () {
	  function FieldHintManager(contentContainer, gridGetter) {
	    babelHelpers.classCallCheck(this, FieldHintManager);
	    _classPrivateMethodInitSpec$4(this, _tieTourToNode);
	    _classPrivateMethodInitSpec$4(this, _freezeGridContainer);
	    _classPrivateMethodInitSpec$4(this, _bindSpotlightToNode);
	    _classPrivateMethodInitSpec$4(this, _fieldNodeIsInGridVision);
	    _classPrivateMethodInitSpec$4(this, _getPossibleToValidateFieldNodes);
	    _classPrivateMethodInitSpec$4(this, _bindGridNodeVisionChange);
	    babelHelpers.defineProperty(this, "fieldHintIsBusy", false);
	    babelHelpers.defineProperty(this, "activeHintGuide", null);
	    _classPrivateFieldInitSpec$3(this, _gridGetter, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec$3(this, _contentContainer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldSet(this, _contentContainer, contentContainer);
	    babelHelpers.classPrivateFieldSet(this, _gridGetter, gridGetter);
	  }
	  babelHelpers.createClass(FieldHintManager, [{
	    key: "processFieldTour",
	    value: function processFieldTour(fieldNode, tourData, endTourHandler) {
	      var _this = this;
	      var addictedFieldNodes = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : [];
	      if (this.fieldHintIsBusy) {
	        return;
	      }
	      this.fieldHintIsBusy = true;
	      // When click action in progress tour will be closed -> 'onClose' tour method will be executed
	      tourData.events = {
	        onClose: function onClose() {
	          endTourHandler();
	          _this.fieldHintIsBusy = false;
	          _this.activeHintGuide = null;
	        }
	      };
	      if (_classPrivateMethodGet$4(this, _fieldNodeIsInGridVision, _fieldNodeIsInGridVision2).call(this, fieldNode)) {
	        var tourObject = _classPrivateMethodGet$4(this, _tieTourToNode, _tieTourToNode2).call(this, fieldNode, tourData);
	        _classPrivateMethodGet$4(this, _freezeGridContainer, _freezeGridContainer2).call(this, function () {
	          tourObject.close();
	        });
	      } else {
	        var gridContainer = babelHelpers.classPrivateFieldGet(this, _gridGetter).call(this).getContainer();
	        var leftArrow = gridContainer.querySelector('.main-grid-ear-left');
	        var rightArrow = gridContainer.querySelector('.main-grid-ear-right');
	        var fieldPos = fieldNode.getClientRects()[0].x;
	        var gridPos = gridContainer.getClientRects()[0].x;
	        var spotlight$$1 = null;
	        if (fieldPos > gridPos) {
	          spotlight$$1 = _classPrivateMethodGet$4(this, _bindSpotlightToNode, _bindSpotlightToNode2).call(this, rightArrow);
	        } else {
	          spotlight$$1 = _classPrivateMethodGet$4(this, _bindSpotlightToNode, _bindSpotlightToNode2).call(this, leftArrow);
	        }
	        _classPrivateMethodGet$4(this, _bindGridNodeVisionChange, _bindGridNodeVisionChange2).call(this, fieldNode, function () {
	          spotlight$$1.close();
	          var tourObject = _classPrivateMethodGet$4(_this, _tieTourToNode, _tieTourToNode2).call(_this, fieldNode, tourData);
	          _classPrivateMethodGet$4(_this, _freezeGridContainer, _freezeGridContainer2).call(_this, function () {
	            tourObject.close();
	          });
	        }, [], addictedFieldNodes);
	      }
	    }
	  }, {
	    key: "getActiveHint",
	    value: function getActiveHint() {
	      if (!this.fieldHintIsBusy) {
	        return null;
	      } else if (this.activeHintGuide instanceof ui_tour.Guide) {
	        return this.activeHintGuide;
	      }
	      return null;
	    }
	  }]);
	  return FieldHintManager;
	}();
	function _bindGridNodeVisionChange2(observedNode, onSuccessVisionCallback) {
	  var _classPrivateMethodGe,
	    _this2 = this;
	  var callbackParams = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : [];
	  var addictedNodes = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : [];
	  var observedNodes = (_classPrivateMethodGe = _classPrivateMethodGet$4(this, _getPossibleToValidateFieldNodes, _getPossibleToValidateFieldNodes2)).call.apply(_classPrivateMethodGe, [this, observedNode].concat(babelHelpers.toConsumableArray(addictedNodes)));
	  var observer = function observer(event) {
	    var _classPrivateMethodGe2;
	    if ((_classPrivateMethodGe2 = _classPrivateMethodGet$4(_this2, _fieldNodeIsInGridVision, _fieldNodeIsInGridVision2)).call.apply(_classPrivateMethodGe2, [_this2].concat(babelHelpers.toConsumableArray(observedNodes)))) {
	      main_core.Event.unbind(babelHelpers.classPrivateFieldGet(_this2, _gridGetter).call(_this2).getScrollContainer(), 'scroll', observer);
	      main_core.Event.unbind(window, 'resize', observer);
	      onSuccessVisionCallback.apply(void 0, babelHelpers.toConsumableArray(callbackParams));
	    }
	  };
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _gridGetter).call(this).getScrollContainer(), 'scroll', observer);
	  main_core.Event.bind(window, 'resize', observer);
	}
	function _getPossibleToValidateFieldNodes2(mainNode) {
	  var _babelHelpers$classPr, _babelHelpers$classPr2;
	  var nodesTuple = [];
	  for (var _len = arguments.length, addictedNodes = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
	    addictedNodes[_key - 1] = arguments[_key];
	  }
	  for (var _i = 0, _addictedNodes = addictedNodes; _i < _addictedNodes.length; _i++) {
	    var addictedNode = _addictedNodes[_i];
	    nodesTuple.push({
	      node: addictedNode,
	      nodeRect: addictedNode.getClientRects()[0]
	    });
	  }
	  var mainNodeTupleEl = {
	    node: mainNode,
	    nodeRect: mainNode.getClientRects()[0]
	  };
	  nodesTuple.push(mainNodeTupleEl);
	  nodesTuple.sort(function (firstEl, secondEl) {
	    var firstX = firstEl.nodeRect.x;
	    var secondX = secondEl.nodeRect.x;
	    if (firstX < secondX) {
	      return -1;
	    } else if (firstX > secondX) {
	      return 1;
	    } else {
	      return 0;
	    }
	  });
	  var gridRect = (_babelHelpers$classPr = babelHelpers.classPrivateFieldGet(this, _gridGetter).call(this)) === null || _babelHelpers$classPr === void 0 ? void 0 : (_babelHelpers$classPr2 = _babelHelpers$classPr.getContainer().getClientRects()) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2[0];
	  function widthIsValid(leftPos, rightPos) {
	    return Math.abs(leftPos - rightPos) < gridRect.width;
	  }
	  while (nodesTuple.length > 1 && !widthIsValid(nodesTuple[0].nodeRect.x, nodesTuple[nodesTuple.length - 1].nodeRect.x)) {
	    var firstEl = nodesTuple[0];
	    var lastEl = nodesTuple[nodesTuple.length - 1];
	    if (firstEl === mainNodeTupleEl) {
	      nodesTuple.pop();
	    } else if (lastEl === mainNodeTupleEl) {
	      nodesTuple.shift();
	    } else {
	      var firstElDistance = mainNodeTupleEl.nodeRect.x - firstEl.nodeRect.x;
	      var lastElDistance = lastEl.nodeRect.x - mainNodeTupleEl.nodeRect.x;
	      if (firstElDistance >= lastElDistance) {
	        nodesTuple.shift();
	      } else {
	        nodesTuple.pop();
	      }
	    }
	  }
	  return nodesTuple.map(function (el) {
	    return el.node;
	  });
	}
	function _fieldNodeIsInGridVision2() {
	  var _babelHelpers$classPr3, _babelHelpers$classPr4;
	  var gridRect = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldGet(this, _gridGetter).call(this)) === null || _babelHelpers$classPr3 === void 0 ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getContainer().getClientRects()) === null || _babelHelpers$classPr4 === void 0 ? void 0 : _babelHelpers$classPr4[0];
	  if (gridRect === undefined) {
	    return false;
	  }
	  var gridLeftEdge = gridRect.x;
	  var gridRightEdge = gridRect.x + gridRect.width;
	  for (var _len2 = arguments.length, fieldNodes = new Array(_len2), _key2 = 0; _key2 < _len2; _key2++) {
	    fieldNodes[_key2] = arguments[_key2];
	  }
	  for (var _i2 = 0, _fieldNodes = fieldNodes; _i2 < _fieldNodes.length; _i2++) {
	    var _fieldNode$getClientR;
	    var fieldNode = _fieldNodes[_i2];
	    var fieldRect = (_fieldNode$getClientR = fieldNode.getClientRects()) === null || _fieldNode$getClientR === void 0 ? void 0 : _fieldNode$getClientR[0];
	    if (fieldRect === undefined) {
	      return false;
	    }
	    var fieldLeftEdge = fieldRect.x;
	    var fieldRightEdge = fieldRect.x + fieldRect.width;
	    if (fieldLeftEdge < gridLeftEdge || fieldRightEdge > gridRightEdge) {
	      return false;
	    }
	  }
	  return true;
	}
	function _bindSpotlightToNode2(targetNode) {
	  var spotlight$$1 = new BX.SpotLight({
	    id: 'arrow_spotlight',
	    targetElement: targetNode,
	    autoSave: true,
	    targetVertex: "middle-center",
	    zIndex: 200
	  });
	  spotlight$$1.show();
	  spotlight$$1.container.style.pointerEvents = "none";
	  return spotlight$$1;
	}
	function _freezeGridContainer2(onCloseCallback) {
	  var _this3 = this;
	  var callbackParams = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
	  var gridContainer = babelHelpers.classPrivateFieldGet(this, _gridGetter).call(this).getContainer();
	  var leftArrow = gridContainer.querySelector('.main-grid-ear-left');
	  var rightArrow = gridContainer.querySelector('.main-grid-ear-right');
	  gridContainer.style.pointerEvents = "none";
	  leftArrow.style.pointerEvents = "none";
	  rightArrow.style.pointerEvents = "none";
	  var clickObserver = function clickObserver(event) {
	    gridContainer.style.pointerEvents = "auto";
	    leftArrow.style.pointerEvents = "auto";
	    rightArrow.style.pointerEvents = "auto";
	    main_core.Event.unbind(babelHelpers.classPrivateFieldGet(_this3, _contentContainer), 'click', clickObserver);
	    onCloseCallback.apply(void 0, babelHelpers.toConsumableArray(callbackParams));
	  };
	  setTimeout(function () {
	    main_core.Event.bind(babelHelpers.classPrivateFieldGet(_this3, _contentContainer), 'click', clickObserver);
	  }, 500);
	}
	function _tieTourToNode2(tourTarget, tourData) {
	  var guide = new ui_tour.Guide({
	    steps: [Object.assign({
	      target: tourTarget
	    }, tourData)],
	    onEvents: true
	  });
	  this.activeHintGuide = guide;
	  guide.showNextStep();
	  return guide;
	}

	var _templateObject$5;
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _classPrivateMethodInitSpec$5(obj, privateSet) { _checkPrivateRedeclaration$5(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec$4(obj, privateMap, value) { _checkPrivateRedeclaration$5(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration$5(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classStaticPrivateMethodGet$1(receiver, classConstructor, method) { _classCheckPrivateStaticAccess$1(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess$1(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var GRID_TEMPLATE_ROW = 'template_0';
	var DEFAULT_PRECISION = 2;
	var _fieldHintManager = /*#__PURE__*/new WeakMap();
	var _initSupportCustomRowActions = /*#__PURE__*/new WeakSet();
	var _getCalculatePriceFieldNames = /*#__PURE__*/new WeakSet();
	var _childrenHasErrors = /*#__PURE__*/new WeakSet();
	var Editor = /*#__PURE__*/function () {
	  function Editor(id) {
	    babelHelpers.classCallCheck(this, Editor);
	    _classPrivateMethodInitSpec$5(this, _childrenHasErrors);
	    _classPrivateMethodInitSpec$5(this, _getCalculatePriceFieldNames);
	    _classPrivateMethodInitSpec$5(this, _initSupportCustomRowActions);
	    babelHelpers.defineProperty(this, "ajaxPool", new Map());
	    babelHelpers.defineProperty(this, "products", []);
	    babelHelpers.defineProperty(this, "productsWasInitiated", false);
	    babelHelpers.defineProperty(this, "isChangedGrid", false);
	    babelHelpers.defineProperty(this, "isVisibleGrid", false);
	    babelHelpers.defineProperty(this, "cache", new main_core.Cache.MemoryCache());
	    _classPrivateFieldInitSpec$4(this, _fieldHintManager, {
	      writable: true,
	      value: void 0
	    });
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
	    babelHelpers.defineProperty(this, "onAddViewedProductToDealHandler", this.handleOnAddViewedProductToDeal.bind(this));
	    babelHelpers.defineProperty(this, "onSaveHandler", this.handleOnSave.bind(this));
	    babelHelpers.defineProperty(this, "onFocusToProductList", this.handleProductListFocus.bind(this));
	    babelHelpers.defineProperty(this, "onEntityUpdateHandler", this.handleOnEntityUpdate.bind(this));
	    babelHelpers.defineProperty(this, "onEditorSubmit", this.handleEditorSubmit.bind(this));
	    babelHelpers.defineProperty(this, "onInnerCancelHandler", this.handleOnInnerCancel.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeGridRequestHandler", this.handleOnBeforeGridRequest.bind(this));
	    babelHelpers.defineProperty(this, "onGridUpdatedHandler", this.handleOnGridUpdated.bind(this));
	    babelHelpers.defineProperty(this, "onGridRowMovedHandler", this.handleOnGridRowMoved.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeProductChangeHandler", this.handleOnBeforeProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onProductChangeHandler", this.handleOnProductChange.bind(this));
	    babelHelpers.defineProperty(this, "onBeforeProductClearHandler", this.handleOnBeforeProductClear.bind(this));
	    babelHelpers.defineProperty(this, "onProductClearHandler", this.handleOnProductClear.bind(this));
	    babelHelpers.defineProperty(this, "dropdownChangeHandler", this.handleDropdownChange.bind(this));
	    babelHelpers.defineProperty(this, "pullReloadGrid", null);
	    babelHelpers.defineProperty(this, "changeProductFieldHandler", this.handleFieldChange.bind(this));
	    babelHelpers.defineProperty(this, "updateTotalDataDelayedHandler", main_core.Runtime.debounce(this.updateTotalDataDelayed, 1000, this));
	    this.setId(id);
	  }
	  babelHelpers.createClass(Editor, [{
	    key: "init",
	    value: function init() {
	      var _this = this;
	      var config = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      this.setSettings(config);
	      if (this.canEdit()) {
	        this.addFirstRowIfEmpty();
	        this.enableEdit();
	      }
	      this.initForm();
	      this.initProducts();
	      this.initGridData();
	      babelHelpers.classPrivateFieldSet(this, _fieldHintManager, new FieldHintManager(this.getContainer(), this.getGrid.bind(this)));
	      main_core_events.EventEmitter.emit(window, 'EntityProductListController', [this]);
	      _classPrivateMethodGet$5(this, _initSupportCustomRowActions, _initSupportCustomRowActions2).call(this);
	      this.subscribeDomEvents();
	      this.subscribeCustomEvents();
	      if (this.getSettingValue('isReserveBlocked', false)) {
	        var headersToLock = ['STORE_INFO', 'RESERVE_INFO'];
	        var container = this.getContainer();
	        headersToLock.forEach(function (headerId) {
	          var header = container === null || container === void 0 ? void 0 : container.querySelector(".main-grid-cell-head[data-name=\"".concat(headerId, "\"] .main-grid-cell-head-container"));
	          if (header) {
	            main_core.Dom.addClass(header, 'main-grid-cell-head-locked');
	            header.onclick = function (event) {
	              if (main_core.Dom.hasClass(event.target, 'ui-hint-icon')) {
	                return;
	              }
	              _this.openIntegrationLimitSlider();
	            };
	            var lock = main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-entity-product-list-locked-header\"></span>"])));
	            header.insertBefore(lock, header.firstChild);
	          }
	        });
	      }
	      this.getContainer().querySelectorAll('.crm-entity-product-list-add-block').forEach(function (buttonBlock) {
	        BX.UI.Hint.init(buttonBlock);
	      });
	    }
	  }, {
	    key: "subscribeDomEvents",
	    value: function subscribeDomEvents() {
	      var _this2 = this;
	      this.unsubscribeDomEvents();
	      var container = this.getContainer();
	      if (main_core.Type.isElementNode(container)) {
	        if (!this.getSettingValue('disabledSelectProductButton', false)) {
	          container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	            main_core.Event.bind(selectButton, 'click', _this2.productSelectionPopupHandler);
	          });
	        }
	        if (!this.getSettingValue('disabledAddRowButton', false)) {
	          container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	            if (_this2.getSettingValue('isOnecInventoryManagementRestricted') === true) {
	              main_core.Dom.addClass(addButton, 'ui-btn-icon-lock');
	            }
	            main_core.Event.bind(addButton, 'click', _this2.productRowAddHandler);
	          });
	        }
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.bind(configButton, 'click', _this2.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "unsubscribeDomEvents",
	    value: function unsubscribeDomEvents() {
	      var _this3 = this;
	      var container = this.getContainer();
	      if (main_core.Type.isElementNode(container)) {
	        container.querySelectorAll('[data-role="product-list-select-button"]').forEach(function (selectButton) {
	          main_core.Event.unbind(selectButton, 'click', _this3.productSelectionPopupHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-add-button"]').forEach(function (addButton) {
	          main_core.Event.unbind(addButton, 'click', _this3.productRowAddHandler);
	        });
	        container.querySelectorAll('[data-role="product-list-settings-button"]').forEach(function (configButton) {
	          main_core.Event.unbind(configButton, 'click', _this3.showSettingsPopupHandler);
	        });
	      }
	    }
	  }, {
	    key: "subscribeCustomEvents",
	    value: function subscribeCustomEvents() {
	      var _this4 = this;
	      this.unsubscribeCustomEvents();
	      main_core_events.EventEmitter.subscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.subscribe('onAddViewedProductToDeal', this.onAddViewedProductToDealHandler);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.subscribe('onFocusToProductList', this.onFocusToProductList);
	      main_core_events.EventEmitter.subscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
	      main_core_events.EventEmitter.subscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.subscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onBeforeClear', this.onBeforeProductClearHandler);
	      main_core_events.EventEmitter.subscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.subscribe('Dropdown::change', this.dropdownChangeHandler);
	      if (pull_client.PULL) {
	        this.pullReloadGrid = pull_client.PULL.subscribe({
	          moduleId: 'crm',
	          callback: function callback(data) {
	            if (data.command === 'onCatalogInventoryManagementEnabled' || data.command === 'onCatalogInventoryManagementDisabled') {
	              _this4.reloadGrid(false);
	            }
	          }
	        });
	      }
	    }
	  }, {
	    key: "unsubscribeCustomEvents",
	    value: function unsubscribeCustomEvents() {
	      main_core_events.EventEmitter.unsubscribe('CrmProductSearchDialog_SelectProduct', this.onDialogSelectProductHandler);
	      main_core_events.EventEmitter.unsubscribe('onAddViewedProductToDeal', this.onAddViewedProductToDealHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onSaveHandler);
	      main_core_events.EventEmitter.unsubscribe('onFocusToProductList', this.onFocusToProductList);
	      main_core_events.EventEmitter.unsubscribe('onCrmEntityUpdate', this.onEntityUpdateHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditorAjax:onSubmit', this.onEditorSubmit);
	      main_core_events.EventEmitter.unsubscribe('EntityProductListController:onInnerCancel', this.onInnerCancelHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::beforeRequest', this.onBeforeGridRequestHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::updated', this.onGridUpdatedHandler);
	      main_core_events.EventEmitter.unsubscribe('Grid::rowMoved', this.onGridRowMovedHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeChange', this.onBeforeProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onChange', this.onProductChangeHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onBeforeClear', this.onBeforeProductClearHandler);
	      main_core_events.EventEmitter.unsubscribe('BX.Catalog.ProductSelector:onClear', this.onProductClearHandler);
	      main_core_events.EventEmitter.unsubscribe('Dropdown::change', this.dropdownChangeHandler);
	      if (!main_core.Type.isNil(this.pullReloadGrid)) {
	        this.pullReloadGrid();
	      }
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
	    key: "handleOnAddViewedProductToDeal",
	    value: function handleOnAddViewedProductToDeal(event) {
	      var _event$getCompatData3 = event.getCompatData(),
	        _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 1),
	        productId = _event$getCompatData4[0];
	      var id;
	      if (this.getProductCount() > 0) {
	        id = this.addProductRow();
	      } else {
	        var _this$products$3;
	        id = (_this$products$3 = this.products[0]) === null || _this$products$3 === void 0 ? void 0 : _this$products$3.getField('ID');
	      }
	      this.selectViewedProductInRow(id, productId);
	    }
	  }, {
	    key: "selectViewedProductInRow",
	    value: function selectViewedProductInRow(id, productId) {
	      var _this5 = this;
	      if (!main_core.Type.isStringFilled(id) || main_core.Text.toNumber(productId) <= 0) {
	        return;
	      }
	      requestAnimationFrame(function () {
	        var productSelector = _this5.getProductSelector(id);
	        if (productSelector) {
	          productSelector.onProductSelect(productId);
	        }
	      });
	    }
	  }, {
	    key: "selectProductInRow",
	    value: function selectProductInRow(id, productId) {
	      var _this6 = this;
	      if (!main_core.Type.isStringFilled(id) || main_core.Text.toNumber(productId) <= 0) {
	        return;
	      }
	      requestAnimationFrame(function () {
	        var productSelector = _this6.getProductSelector(id);
	        if (productSelector) {
	          productSelector.searchInput.clearErrors();
	          productSelector.onProductSelect(productId);
	        }
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
	    key: "handleProductListFocus",
	    value: function handleProductListFocus(event) {
	      if (this.isReadOnly()) {
	        return;
	      }
	      var listHaveEmptyRows = false;
	      var _iterator = _createForOfIteratorHelper$1(this.products),
	        _step;
	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var product = _step.value;
	          if (product.isEmptyRow()) {
	            listHaveEmptyRows = true;
	            this.focusProductSelector(product.fields['ID']);
	            break;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	      if (!listHaveEmptyRows) {
	        this.handleProductRowAdd();
	      }
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
	      var _this7 = this;
	      if (this.controller) {
	        this.controller.rollback();
	      }
	      this.setGridChanged(false);
	      main_core_events.EventEmitter.subscribeOnce(this, 'onGridReloaded', function () {
	        return _this7.actionUpdateTotalData({
	          isInternalChanging: true
	        });
	      });
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
	      var _this8 = this;
	      var useProductsFromRequest = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
	      this.getGrid().reloadTable('POST', {
	        useProductsFromRequest: useProductsFromRequest
	      }, function () {
	        return main_core_events.EventEmitter.emit(_this8, 'onGridReloaded');
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
	      var _this9 = this;
	      var _event$getCompatData5 = event.getCompatData(),
	        _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 2),
	        grid = _event$getCompatData6[0],
	        eventArgs = _event$getCompatData6[1];
	      if (!grid || !grid.parent || grid.parent.getId() !== this.getGridId()) {
	        return;
	      }

	      // reload by native grid actions (columns settings, etc), otherwise by this.reloadGrid()
	      var isNativeAction = !('useProductsFromRequest' in eventArgs.data);
	      var useProductsFromRequest = isNativeAction ? true : eventArgs.data.useProductsFromRequest;
	      eventArgs.url = this.getReloadUrl();
	      eventArgs.method = 'POST';
	      eventArgs.sessid = BX.bitrix_sessid();
	      eventArgs.data = _objectSpread(_objectSpread({}, eventArgs.data), {}, {
	        signedParameters: this.getSignedParameters(),
	        products: useProductsFromRequest ? this.getProductsFields(_classStaticPrivateMethodGet$1(Editor, Editor, _getAjaxFields).call(Editor)) : null,
	        locationId: this.getLocationId(),
	        currencyId: this.getCurrencyId()
	      });
	      this.clearEditor();
	      if (isNativeAction && this.isChanged()) {
	        main_core_events.EventEmitter.subscribeOnce('Grid::updated', function () {
	          return _this9.actionUpdateTotalData({
	            isInternalChanging: false
	          });
	        });
	      }
	    }
	  }, {
	    key: "handleOnGridUpdated",
	    value: function handleOnGridUpdated(event) {
	      var _event$getCompatData7 = event.getCompatData(),
	        _event$getCompatData8 = babelHelpers.slicedToArray(_event$getCompatData7, 1),
	        grid = _event$getCompatData8[0];
	      if (!grid || grid.getId() !== this.getGridId()) {
	        return;
	      }
	      this.getSettingsPopup().updateCheckboxState();
	    }
	  }, {
	    key: "handleOnGridRowMoved",
	    value: function handleOnGridRowMoved(event) {
	      var _event$getCompatData9 = event.getCompatData(),
	        _event$getCompatData10 = babelHelpers.slicedToArray(_event$getCompatData9, 3),
	        ids = _event$getCompatData10[0],
	        grid = _event$getCompatData10[2];
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
	      var _this10 = this;
	      if (this.getGrid().getRows().getCountDisplayed() === 0) {
	        requestAnimationFrame(function () {
	          return _this10.addProductRow();
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
	      this.products.forEach(function (current) {
	        current.unsubscribeCustomEvents();
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
	    } /* settings tools */
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
	      var _this11 = this;
	      this.setCurrencyId(currencyId);
	      var products = [];
	      this.products.forEach(function (product) {
	        var priceFields = {};
	        _classPrivateMethodGet$5(_this11, _getCalculatePriceFieldNames, _getCalculatePriceFieldNames2).call(_this11).forEach(function (name) {
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
	        templateRow[field]['CURRENCY']['VALUE'] = _this11.getCurrencyId();
	      });
	      this.setGridEditData(editData);
	    }
	  }, {
	    key: "onCalculatePricesResponse",
	    value: function onCalculatePricesResponse(products) {
	      this.products.forEach(function (product) {
	        if (main_core.Type.isObject(products[product.getId()])) {
	          product.updateUiCurrencyFields();
	          ['BASE_PRICE', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'CURRENCY_ID'].forEach(function (name) {
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
	      var _this12 = this;
	      var totalBlock = BX(this.getSettingValue('totalBlockContainerId', null));
	      if (main_core.Type.isElementNode(totalBlock)) {
	        totalBlock.querySelectorAll('.crm-product-list-payment-side-table-column').forEach(function (column) {
	          var valueElement = column.querySelector('.crm-product-list-result-grid-total');
	          if (valueElement) {
	            column.innerHTML = currency_currencyCore.CurrencyCore.getPriceControl(valueElement, _this12.getCurrencyId());
	          }
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
	    } /* calculate tools finish */
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this13 = this;
	      return this.cache.remember('container', function () {
	        return document.getElementById(_this13.getContainerId());
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
	        main_core.Dom.append(main_core.Dom.create('input', {
	          attrs: {
	            type: "hidden",
	            name: fieldName
	          }
	        }), container);
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
	      var isInventoryManagementToolEnabled = this.getSettingValue('isInventoryManagementToolEnabled', false);
	      var inventoryManagementMode = this.getSettingValue('inventoryManagementMode', null);
	      var _iterator2 = _createForOfIteratorHelper$1(list),
	        _step2;
	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var item = _step2.value;
	          var fields = _objectSpread({}, item.fields);
	          var settings = {
	            selectorId: item.selectorId,
	            isReserveBlocked: isReserveBlocked,
	            isInventoryManagementToolEnabled: isInventoryManagementToolEnabled,
	            inventoryManagementMode: inventoryManagementMode
	          };
	          this.products.push(new Row(item.rowId, fields, settings, this));
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
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
	      var _this14 = this;
	      return this.cache.remember('grid', function () {
	        var gridId = _this14.getGridId();
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
	      if (_classPrivateMethodGet$5(this, _childrenHasErrors, _childrenHasErrors2).call(this)) {
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
	      main_core_events.EventEmitter.subscribeOnce(popup, 'onWindowRegister', BX.defer(function () {
	        popup.Get().style.position = 'fixed';
	        popup.Get().style.top = parseInt(popup.Get().style.top) - BX.GetWindowScrollPos().scrollTop + 'px';
	      }));
	      main_core_events.EventEmitter.subscribeOnce(window, 'EntityProductListController:onInnerCancel', BX.defer(function () {
	        popup.Close();
	      }));
	      if (!main_core.Type.isUndefined(BX.Crm.EntityEvent)) {
	        main_core_events.EventEmitter.subscribeOnce(window, BX.Crm.EntityEvent.names.update, BX.defer(function () {
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
	      if (this.getSettingValue('isOnecInventoryManagementRestricted') === true) {
	        catalog_toolAvailabilityManager.OneCPlanRestrictionSlider.show();
	        return;
	      }
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
	      var _this15 = this;
	      return this.cache.remember('settings-popup', function () {
	        return new SettingsPopup(_this15.getContainer().querySelector('.crm-entity-product-list-add-block-active [data-role="product-list-settings-button"]'), _this15.getSettingValue('popupSettings', []), _this15);
	      });
	    }
	  }, {
	    key: "getHintPopup",
	    value: function getHintPopup() {
	      var _this16 = this;
	      return this.cache.remember('hint-popup', function () {
	        return new HintPopup(_this16);
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
	      var _product$getSelector3;
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
	      var isInventoryManagementToolEnabled = this.getSettingValue('isInventoryManagementToolEnabled', false);
	      var inventoryManagementMode = this.getSettingValue('inventoryManagementMode', null);
	      var settings = {
	        isReserveBlocked: isReserveBlocked,
	        isInventoryManagementToolEnabled: isInventoryManagementToolEnabled,
	        inventoryManagementMode: inventoryManagementMode,
	        selectorId: 'crm_grid_' + rowId
	      };
	      var product = new Row(rowId, fields, settings, this);
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
	      (_product$getSelector3 = product.getSelector()) === null || _product$getSelector3 === void 0 ? void 0 : _product$getSelector3.setConfig('ENABLE_EMPTY_PRODUCT_ERROR', this.getSettingValue('enableEmptyProductError', false));
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
	      var _this17 = this;
	      requestAnimationFrame(function () {
	        var _this17$getProductSel;
	        (_this17$getProductSel = _this17.getProductSelector(newId)) === null || _this17$getProductSel === void 0 ? void 0 : _this17$getProductSel.searchInDialog().focusName();
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
	      var _this18 = this;
	      var data = event.getData();
	      var productRow = this.getProductByRowId(data.rowId);
	      if (productRow && data.fields) {
	        var promise = new Promise(function (resolve, reject) {
	          var fields = data.fields;
	          if (!main_core.Type.isNil(fields['IMAGE_INFO'])) {
	            fields['IMAGE_INFO'] = JSON.stringify(fields['IMAGE_INFO']);
	          }
	          if (_this18.getCurrencyId() !== fields['CURRENCY_ID']) {
	            fields['CURRENCY'] = fields['CURRENCY_ID'];
	            var priceFields = {};
	            _classPrivateMethodGet$5(_this18, _getCalculatePriceFieldNames, _getCalculatePriceFieldNames2).call(_this18).forEach(function (name) {
	              priceFields[name] = data.fields[name];
	            });
	            var products = [{
	              fields: priceFields,
	              id: productRow.getId()
	            }];
	            main_core.ajax.runComponentAction(_this18.getComponentName(), 'calculateProductPrices', {
	              mode: 'class',
	              signedParameters: _this18.getSignedParameters(),
	              data: {
	                products: products,
	                currencyId: _this18.getCurrencyId(),
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
	          if (_this18.products.length > 1) {
	            var taxId = fields['VAT_ID'] || fields['TAX_ID'];
	            var taxIncluded = fields['VAT_INCLUDED'] || fields['TAX_INCLUDED'];
	            if (taxId > 0 && taxIncluded !== productRow.getTaxIncluded()) {
	              var _this18$getTaxList;
	              var taxRate = (_this18$getTaxList = _this18.getTaxList()) === null || _this18$getTaxList === void 0 ? void 0 : _this18$getTaxList.find(function (item) {
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
	          if (productRow.getField('OFFER_ID') !== fields.ID) {
	            fields['ROW_RESERVED'] = 0;
	            fields['DEDUCTED_QUANTITY'] = 0;
	            if (!_this18.getSettingValue('allowDiscountChange', true)) {
	              fields['DISCOUNT_ROW'] = 0;
	              fields['DISCOUNT_SUM'] = 0;
	              fields['DISCOUNT_RATE'] = 0;
	              fields['DISCOUNT'] = 0;
	              productRow.updateUiHtmlField('DISCOUNT_PRICE', currency_currencyCore.CurrencyCore.currencyFormat(0, _this18.getCurrencyId(), true));
	              productRow.updateUiHtmlField('DISCOUNT_ROW', currency_currencyCore.CurrencyCore.currencyFormat(0, _this18.getCurrencyId(), true));
	            }
	          }
	          Object.keys(fields).forEach(function (key) {
	            productRow.updateFieldValue(key, fields[key]);
	          });
	          if (!main_core.Type.isStringFilled(fields['CUSTOMIZED'])) {
	            productRow.setField('CUSTOMIZED', 'N');
	          }
	          productRow.setField('IS_NEW', data.isNew ? 'Y' : 'N');
	          productRow.layoutReserveControl();
	          productRow.layoutStoreSelector();
	          productRow.initHandlersForSelectors();
	          productRow.updateUiStoreAmountData();
	          productRow.updatePropertyFields();
	          productRow.modifyBasePriceInput();
	          productRow.executeExternalActions();
	          _this18.getGrid().tableUnfade();
	        });
	      } else {
	        this.getGrid().tableUnfade();
	      }
	    }
	  }, {
	    key: "handleOnBeforeProductClear",
	    value: function handleOnBeforeProductClear(event) {
	      var _event$getData5 = event.getData(),
	        rowId = _event$getData5.rowId;
	      var product = this.getProductByRowId(rowId);
	      product.clearPropertyFields();
	    }
	  }, {
	    key: "handleOnProductClear",
	    value: function handleOnProductClear(event) {
	      var _event$getData6 = event.getData(),
	        rowId = _event$getData6.rowId;
	      var product = this.getProductByRowId(rowId);
	      if (product) {
	        product.layoutReserveControl();
	        product.initHandlersForSelectors();
	        product.changeBasePrice(0);
	        if (!this.getSettingValue('allowDiscountChange', true)) {
	          product.setDiscount(0);
	          product.updateUiHtmlField('DISCOUNT_PRICE', currency_currencyCore.CurrencyCore.currencyFormat(0, this.getCurrencyId(), true));
	          product.updateUiHtmlField('DISCOUNT_ROW', currency_currencyCore.CurrencyCore.currencyFormat(0, this.getCurrencyId(), true));
	        }
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
	          var saveFields = item.getFields(_classStaticPrivateMethodGet$1(Editor, Editor, _getAjaxFields).call(Editor));
	          if (!/^[0-9]+$/.test(saveFields['ID'])) {
	            saveFields['ID'] = 0;
	          }
	          saveFields['CUSTOMIZED'] = 'Y';
	          productData.push(saveFields);
	        });
	        productDataValue = JSON.stringify(productData);
	      }
	      return productDataValue;
	    }
	  }, {
	    key: "executeActions",
	    /* actions */value: function executeActions(actions) {
	      var _this19 = this;
	      if (!main_core.Type.isArrayFilled(actions)) {
	        return;
	      }
	      var disableSaveButton = actions.filter(function (action) {
	        return action.type === _this19.actions.updateTotal || action.type === _this19.actions.disableSaveButton;
	      }).length > 0;
	      var _iterator3 = _createForOfIteratorHelper$1(actions),
	        _step3;
	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var item = _step3.value;
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
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
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
	      var _iterator4 = _createForOfIteratorHelper$1(this.products),
	        _step4;
	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var row = _step4.value;
	          row.updateFieldByName(item.field, item.value);
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
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
	      var _iterator5 = _createForOfIteratorHelper$1(this.products),
	        _step5;
	      try {
	        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	          var item = _step5.value;
	          productFields.push(item.getFields(fields));
	        }
	      } catch (err) {
	        _iterator5.e(err);
	      } finally {
	        _iterator5.f();
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
	      var _this20 = this;
	      if (this.controller) {
	        var needMarkAsChanged = true;
	        if (main_core.Type.isObject(options) && (options.isInternalChanging === true || options.isInternalChanging === 'true')) {
	          needMarkAsChanged = false;
	        }
	        setTimeout(function () {
	          _this20.controller.changeSumTotal(data, needMarkAsChanged, !_classPrivateMethodGet$5(_this20, _childrenHasErrors, _childrenHasErrors2).call(_this20));
	        }, 500);
	      }
	    }
	    /* action tools finish */
	    /* ajax tools */
	  }, {
	    key: "ajaxRequest",
	    value: function ajaxRequest(action, data) {
	      var _this21 = this;
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
	        return _this21.ajaxResultSuccess(response, data.options);
	      }, function (response) {
	        return _this21.ajaxResultFailure(response, data.options);
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
	      return new Promise(function (resolve, reject) {
	        var currentBalloon = BX.UI.Notification.Center.getBalloonByCategory(catalog_productModel.ProductModel.SAVE_NOTIFICATION_CATEGORY);
	        if (currentBalloon) {
	          main_core_events.EventEmitter.subscribeOnce(currentBalloon, BX.UI.Notification.Event.getFullName('onClose'), function () {
	            setTimeout(resolve, 500);
	          });
	          currentBalloon.close();
	        } else {
	          setTimeout(resolve(), 50);
	        }
	      });
	    }
	  }, {
	    key: "ajaxResultFailure",
	    value: function ajaxResultFailure(response, requestOptions) {
	      this.ajaxPool["delete"](requestOptions.ACTION);
	    }
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
	      }

	      // noinspection RedundantIfStatementJS
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
	      var _this22 = this;
	      this.products.filter(function (item) {
	        return item.isEmpty();
	      }).forEach(function (row) {
	        return _this22.deleteRow(row.getField('ID'), true);
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
	      if (!this.isVisible()) {
	        this.products.forEach(function (product) {
	          var _product$getSelector4;
	          (_product$getSelector4 = product.getSelector()) === null || _product$getSelector4 === void 0 ? void 0 : _product$getSelector4.layout();
	          product.initHandlersForSelectors();
	        });
	      }
	      main_core_events.EventEmitter.emit('onDemandRecalculateWrapper', [this]);
	      this.isVisibleGrid = true;
	    }
	  }, {
	    key: "isVisible",
	    value: function isVisible() {
	      return this.isVisibleGrid;
	    }
	  }, {
	    key: "showFieldTourHint",
	    value: function showFieldTourHint(fieldName, tourData, endTourHandler) {
	      var addictedFields = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : [];
	      var rowId = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : '';
	      if (this.products.length > 0) {
	        var productNode = this.products[0].getNode();
	        if (this.getProductByRowId(rowId)) {
	          productNode = this.getProductByRowId(rowId).getNode();
	        }
	        var addictedNodes = [];
	        var _iterator6 = _createForOfIteratorHelper$1(addictedFields),
	          _step6;
	        try {
	          for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
	            var _fieldName = _step6.value;
	            var _fieldNode = productNode.querySelector("[data-name=\"".concat(_fieldName, "\"]"));
	            if (_fieldNode !== null) {
	              addictedNodes.push(_fieldNode);
	            }
	          }
	        } catch (err) {
	          _iterator6.e(err);
	        } finally {
	          _iterator6.f();
	        }
	        var fieldNode = productNode.querySelector("[data-name=\"".concat(fieldName, "\"]"));
	        if (fieldNode !== null) {
	          babelHelpers.classPrivateFieldGet(this, _fieldHintManager).processFieldTour(fieldNode, tourData, endTourHandler, addictedNodes);
	        }
	      }
	    }
	  }, {
	    key: "getActiveHint",
	    value: function getActiveHint() {
	      return babelHelpers.classPrivateFieldGet(this, _fieldHintManager).getActiveHint();
	    }
	  }, {
	    key: "openIntegrationLimitSlider",
	    value: function openIntegrationLimitSlider() {
	      top.BX.UI.InfoHelper.show('limit_store_crm_integration');
	      var helperSlider = top.BX.UI.InfoHelper.getSlider();
	      top.BX.Event.EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', function (event) {
	        var _event$getData$;
	        var slider = (_event$getData$ = event.getData()[0]) === null || _event$getData$ === void 0 ? void 0 : _event$getData$.getSlider();
	        if (slider !== helperSlider) {
	          return;
	        }
	        window.location.search += '&active_tab=tab_products';
	      });
	    }
	  }, {
	    key: "openInventoryManagementToolDisabledSlider",
	    value: function openInventoryManagementToolDisabledSlider() {
	      main_core.Runtime.loadExtension('catalog.tool-availability-manager').then(function (exports) {
	        var ToolAvailabilityManager = exports.ToolAvailabilityManager;
	        ToolAvailabilityManager.openInventoryManagementToolDisabledSlider();
	      });
	    }
	  }, {
	    key: "getRestrictedProductTypes",
	    value: function getRestrictedProductTypes() {
	      return this.getSettingValue('restrictedProductTypes', []);
	    }
	  }]);
	  return Editor;
	}();
	function _initSupportCustomRowActions2() {
	  this.getGrid()._clickOnRowActionsButton = function () {};
	}
	function _getCalculatePriceFieldNames2() {
	  return ['BASE_PRICE', 'TAX_INCLUDED', 'PRICE_NETTO', 'PRICE_BRUTTO', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'CURRENCY'];
	}
	function _childrenHasErrors2() {
	  return this.products.filter(function (product) {
	    return product.getModel().getErrorCollection().hasErrors();
	  }).length > 0;
	}
	function _getAjaxFields() {
	  return ['ID', 'PRODUCT_ID', 'PRODUCT_NAME', 'QUANTITY', 'TAX_RATE', 'TAX_INCLUDED', 'PRICE_EXCLUSIVE', 'PRICE_NETTO', 'PRICE_BRUTTO', 'PRICE', 'CUSTOMIZED', 'BASE_PRICE', 'DISCOUNT_ROW', 'DISCOUNT_SUM', 'DISCOUNT_TYPE_ID', 'DISCOUNT_RATE', 'CURRENCY', 'STORE_ID', 'INPUT_RESERVE_QUANTITY', 'RESERVE_QUANTITY', 'DATE_RESERVE_END', 'SORT', 'MEASURE_CODE', 'MEASURE_NAME', 'TYPE'];
	}

	exports.Editor = Editor;
	exports.PageEventsManager = PageEventsManager;

}((this.BX.Crm.Entity.ProductList = this.BX.Crm.Entity.ProductList || {}),BX,BX,BX.Catalog,BX.Catalog.Store,BX.Catalog,BX.Main,BX.Event,BX.Currency,BX.Catalog,BX.Catalog,BX,BX,BX,BX.UI.Tour,BX.Catalog));
//# sourceMappingURL=script.js.map
