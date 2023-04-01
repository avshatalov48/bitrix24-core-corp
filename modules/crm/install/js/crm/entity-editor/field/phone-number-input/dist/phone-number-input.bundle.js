this.BX = this.BX || {};
(function (exports,main_core,crm_entitySelector) {
	'use strict';

	var _templateObject;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var NAMESPACE = main_core.Reflection.namespace('BX.Crm');
	var FLAG_ICON_PATH = '/bitrix/js/crm/entity-selector/src/images/';
	var FLAG_ICON_EXT = 'png';
	var FLAG_SIZE = 24;
	var PLUS_CHAR = '+';
	var GLOBAL_COUNTRY_CODE = 'XX';
	var LAST_RECENT_ITEMS_TITLE_COLOR = '#00789E';
	var _searchDialogContextCode = /*#__PURE__*/new WeakMap();
	var _isSelectionIndicatorEnabled = /*#__PURE__*/new WeakMap();
	var _countryDialog = /*#__PURE__*/new WeakMap();
	var _countryFlagTickNode = /*#__PURE__*/new WeakMap();
	var _initSelectionIndicator = /*#__PURE__*/new WeakSet();
	var _initCountryDialogEvents = /*#__PURE__*/new WeakSet();
	var PhoneNumberInput = /*#__PURE__*/function (_BX$PhoneNumber$Input) {
	  babelHelpers.inherits(PhoneNumberInput, _BX$PhoneNumber$Input);
	  function PhoneNumberInput(params) {
	    var _this;
	    babelHelpers.classCallCheck(this, PhoneNumberInput);
	    // set permanent options
	    params.flagSize = FLAG_SIZE;

	    // show global icon when empty country code
	    if (params.savedCountryCode === '') {
	      params.savedCountryCode = GLOBAL_COUNTRY_CODE;
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(PhoneNumberInput).call(this, params));
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _initCountryDialogEvents);
	    _classPrivateMethodInitSpec(babelHelpers.assertThisInitialized(_this), _initSelectionIndicator);
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _searchDialogContextCode, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _isSelectionIndicatorEnabled, {
	      writable: true,
	      value: void 0
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _countryDialog, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(babelHelpers.assertThisInitialized(_this), _countryFlagTickNode, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _searchDialogContextCode, main_core.Type.isStringFilled(params.searchDialogContextCode) ? params.searchDialogContextCode : '');
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _isSelectionIndicatorEnabled, main_core.Type.isBoolean(params.isSelectionIndicatorEnabled) ? params.isSelectionIndicatorEnabled : false);
	    if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _isSelectionIndicatorEnabled)) {
	      _classPrivateMethodGet(babelHelpers.assertThisInitialized(_this), _initSelectionIndicator, _initSelectionIndicator2).call(babelHelpers.assertThisInitialized(_this));
	    }
	    return _this;
	  }
	  babelHelpers.createClass(PhoneNumberInput, [{
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _countryDialog)) {
	        babelHelpers.classPrivateFieldGet(this, _countryDialog).destroy();
	      }
	    } // region overridden methods from BX.PhoneNumber.Input ------------------------------------------------------------
	    /**
	     * Override default behavior with PopupWindow. EntitySelectorEx.Dialog component used.
	     *
	     * @param event
	     *
	     * @override (parent method BX.PhoneNumber.Input.prototype._onFlagClick)
	     */
	  }, {
	    key: "_onFlagClick",
	    value: function _onFlagClick(event) {
	      if (!main_core.Type.isDomNode(this.flagNode)) {
	        return;
	      }
	      if (babelHelpers.classPrivateFieldGet(this, _countryDialog)) {
	        babelHelpers.classPrivateFieldGet(this, _countryDialog).show();
	        return;
	      }

	      // new popup dialog
	      babelHelpers.classPrivateFieldSet(this, _countryDialog, new crm_entitySelector.Dialog({
	        targetNode: this.flagNode,
	        context: babelHelpers.classPrivateFieldGet(this, _searchDialogContextCode),
	        multiple: false,
	        dropdownMode: true,
	        enableSearch: true,
	        width: 350,
	        tagSelectorOptions: {
	          placeholder: main_core.Loc.getMessage('CRM_PHONE_INPUT_FIELD_TAG_SELECTOR_SEARCH_PLACEHOLDER'),
	          textBoxWidth: '100%'
	        },
	        entities: [{
	          id: 'country',
	          options: {
	            isEmptyCountryEnabled: false
	          }
	        }],
	        events: _classPrivateMethodGet(this, _initCountryDialogEvents, _initCountryDialogEvents2).call(this)
	      }));
	      babelHelpers.classPrivateFieldGet(this, _countryDialog).show();
	    }
	    /**
	     * New icons to display country flag added.
	     *
	     * @override (parent method BX.PhoneNumber.Input.prototype.drawCountryFlag)
	     */
	  }, {
	    key: "drawCountryFlag",
	    value: function drawCountryFlag() {
	      if (!main_core.Type.isDomNode(this.flagNode)) {
	        return;
	      }
	      var country = this.getCountry();
	      if (!main_core.Type.isStringFilled(country)) {
	        return;
	      }
	      this.adjustFlag(country);
	    }
	  }, {
	    key: "tryRedrawCountryFlag",
	    /**
	     * Add 'global' flag functionality when countryCode is undefined.
	     *
	     * @override
	     */
	    value: function tryRedrawCountryFlag() {
	      var useGlobalCode = !main_core.Type.isStringFilled(this.inputNode.value) || main_core.Type.isNull(this.formatter.country) || !this.formatter.isInternational;
	      if (useGlobalCode) {
	        this.formatter.replaceCountry(GLOBAL_COUNTRY_CODE);
	        this.adjustFlag(GLOBAL_COUNTRY_CODE);
	      } else {
	        this.drawCountryFlag();
	      }
	      this.callbacks.countryChange({
	        country: this.getCountry(),
	        countryCode: this.getCountryCode()
	      });
	    }
	    /**
	     * @param {String} newValue
	     * @param {String} savedCountryCode
	     *
	     * @override
	     */
	  }, {
	    key: "setValue",
	    value: function setValue(newValue, savedCountryCode) {
	      this.waitForInitialization().then(function () {
	        this.inputNode.value = this.formatter.format(newValue.toString());
	        this.callbacks.change({
	          value: this.getValue(),
	          formattedValue: this.getFormattedValue(),
	          country: this.getCountry(),
	          countryCode: this.getCountryCode()
	        });
	        if (this._countryBefore !== this.getCountry()) {
	          this.drawCountryFlag();
	          this.callbacks.countryChange({
	            country: this.getCountry(),
	            countryCode: this.getCountryCode()
	          });
	        }

	        // NEW: redraw country flag if saved country code exists and does not match with formatter code
	        if (main_core.Type.isStringFilled(savedCountryCode) && this.formatter.country !== savedCountryCode) {
	          this.formatter.replaceCountry(savedCountryCode);
	          this.tryRedrawCountryFlag();
	        }
	      }.bind(this));
	    }
	    /**
	     * Handler when user select the country from list
	     * (userOptions saving not used).
	     *
	     * @param event
	     *
	     * @override
	     */
	  }, {
	    key: "onCountrySelect",
	    value: function onCountrySelect(event) {
	      var item = event.getData().item;
	      if (item) {
	        var country = item.getId();
	        if (country === this.getCountry()) {
	          return; // nothing to do
	        }

	        this.formatter.replaceCountry(country);
	        this.inputNode.value = this.formatter.getFormattedNumber();
	        this.drawCountryFlag();
	        this.callbacks.change({
	          value: this.getValue(),
	          formattedValue: this.getFormattedValue(),
	          country: this.getCountry(),
	          countryCode: this.getCountryCode()
	        });
	        this.callbacks.countryChange({
	          country: this.getCountry(),
	          countryCode: this.getCountryCode()
	        });
	      }
	    }
	  }, {
	    key: "adjustFlag",
	    // endregion -------------------------------------------------------------------------------------------------------
	    value: function adjustFlag(country) {
	      var countryFlagIconUrl = FLAG_ICON_PATH + country.toLowerCase() + '.' + FLAG_ICON_EXT;
	      main_core.Dom.adjust(this.flagNode, {
	        props: {
	          className: this.flagNodeInitialClass + ' crm-entity-phone-number-input-flag-' + this.flagSize
	        }
	      });
	      main_core.Dom.style(this.flagNode, {
	        'border': '1px solid rgba(82, 92, 105, 0.2)',
	        'background-image': 'url("' + countryFlagIconUrl + '")'
	      });
	      if (country === GLOBAL_COUNTRY_CODE) {
	        main_core.Dom.style(this.flagNode, {
	          'border': 0,
	          'background-position': 'center',
	          'background-size': 'contain',
	          'background-repeat': 'no-repeat'
	        });
	      }
	    } // region PRIVATE methods ------------------------------------------------------------------------------------------
	  }], [{
	    key: "isCountryCodeOnly",
	    // endregion -------------------------------------------------------------------------------------------------------
	    value: function isCountryCodeOnly(input, countryCode) {
	      return input === PLUS_CHAR || input === countryCode || input === PLUS_CHAR + countryCode;
	    }
	  }]);
	  return PhoneNumberInput;
	}(BX.PhoneNumber.Input);
	function _initSelectionIndicator2() {
	  if (main_core.Type.isDomNode(this.flagNode)) {
	    babelHelpers.classPrivateFieldSet(this, _countryFlagTickNode, main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-entity-widget-content-country-flag-tick\"></span>"]))));
	    main_core.Dom.append(babelHelpers.classPrivateFieldGet(this, _countryFlagTickNode), this.flagNode);
	  }
	}
	function _initCountryDialogEvents2() {
	  var me = this;
	  var events = {
	    'Item:onSelect': function ItemOnSelect(event) {
	      me.onCountrySelect(event);
	    }
	  };
	  events.onLoad = function (event) {
	    var dialogItems = event.getTarget().getItems();
	    var filtered = dialogItems.filter(function (row) {
	      return row.contextSort;
	    });
	    filtered.forEach(function (item) {
	      return item.setTextColor(LAST_RECENT_ITEMS_TITLE_COLOR);
	    });
	    var country = me.formatter.country;
	    if (country) {
	      var selectedIdem = dialogItems.find(function (item) {
	        return item.getId() === country;
	      });
	      if (selectedIdem) {
	        selectedIdem.select();
	      }
	    }
	  };
	  events.onFirstShow = function (event) {
	    var popupContainer = event.getTarget().getPopup().getContentContainer();
	    if (main_core.Type.isDomNode(popupContainer)) {
	      main_core.Dom.addClass(popupContainer, 'crm-entity-country-selector-popup');
	    }
	  };
	  if (babelHelpers.classPrivateFieldGet(this, _isSelectionIndicatorEnabled)) {
	    events.onShow = function (event) {
	      if (babelHelpers.classPrivateFieldGet(me, _countryFlagTickNode)) {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldGet(me, _countryFlagTickNode), '--flipped');
	      }
	      var country = me.formatter.country;
	      if (country) {
	        var dialog = event.getTarget();
	        var selectedIdem = dialog.getItems().find(function (item) {
	          return item.getId() === country;
	        });
	        if (selectedIdem) {
	          selectedIdem.select();
	        }
	      }
	    };
	    events.onHide = function () {
	      if (babelHelpers.classPrivateFieldGet(me, _countryFlagTickNode)) {
	        main_core.Dom.removeClass(babelHelpers.classPrivateFieldGet(me, _countryFlagTickNode), '--flipped');
	      }
	    };
	  }
	  return events;
	}
	NAMESPACE.PhoneNumberInput = PhoneNumberInput;

	exports.default = PhoneNumberInput;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Crm.EntitySelectorEx));
//# sourceMappingURL=phone-number-input.bundle.js.map
