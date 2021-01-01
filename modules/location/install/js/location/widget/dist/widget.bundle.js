this.BX = this.BX || {};
this.BX.Location = this.BX.Location || {};
(function (exports,location_osm,location_google,main_popup,ui_forms,location_core,location_widget,main_core_events,main_core) {
	'use strict';

	/**
	 * Contains
	 * */
	var State = function State() {
	  babelHelpers.classCallCheck(this, State);
	};

	babelHelpers.defineProperty(State, "INITIAL", 'INITIAL');
	babelHelpers.defineProperty(State, "DATA_INPUTTING", 'DATA_INPUTTING');
	babelHelpers.defineProperty(State, "DATA_SELECTED", 'DATA_SELECTED');
	babelHelpers.defineProperty(State, "DATA_LOADING", 'DATA_LOADING');
	babelHelpers.defineProperty(State, "DATA_LOADED", 'DATA_LOADED');

	/**
	 * Base class for the address widget feature
	 */

	var BaseFeature = /*#__PURE__*/function () {
	  function BaseFeature() {
	    babelHelpers.classCallCheck(this, BaseFeature);
	  }

	  babelHelpers.createClass(BaseFeature, [{
	    key: "render",
	    value: function render(props) {
	      throw new location_core.MethodNotImplemented('Method render must be implemented');
	    }
	  }, {
	    key: "setAddressWidget",
	    value: function setAddressWidget(addressWidget) {
	      throw new location_core.MethodNotImplemented('Method render must be implemented');
	    }
	  }, {
	    key: "setAddress",
	    value: function setAddress(address) {
	      throw new location_core.MethodNotImplemented('Method set address must be implemented');
	    }
	  }, {
	    key: "setMode",
	    value: function setMode(mode) {}
	  }, {
	    key: "destroy",
	    value: function destroy() {}
	  }, {
	    key: "resetView",
	    value: function resetView() {}
	  }]);
	  return BaseFeature;
	}();

	var Menu = /*#__PURE__*/function (_MainMenu) {
	  babelHelpers.inherits(Menu, _MainMenu);

	  function Menu(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, Menu);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Menu).call(this, options));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "choseItemIdx", -1);
	    var elRect = options.bindElement.getBoundingClientRect();

	    _this.popupWindow.setMaxWidth(elRect.width);

	    return _this;
	  }

	  babelHelpers.createClass(Menu, [{
	    key: "isMenuEmpty",
	    value: function isMenuEmpty() {
	      return this.menuItems.length <= 0;
	    }
	  }, {
	    key: "isChoseLastItem",
	    value: function isChoseLastItem() {
	      return this.choseItemIdx >= this.menuItems.length - 1;
	    }
	  }, {
	    key: "isChoseFirstItem",
	    value: function isChoseFirstItem() {
	      return this.choseItemIdx === 0;
	    }
	  }, {
	    key: "isItemChosen",
	    value: function isItemChosen() {
	      return this.choseItemIdx >= 0;
	    }
	  }, {
	    key: "isDestroyed",
	    value: function isDestroyed() {
	      return this.getPopupWindow().isDestroyed();
	    }
	  }, {
	    key: "isItemExist",
	    value: function isItemExist(index) {
	      return typeof this.menuItems[this.choseItemIdx] !== 'undefined';
	    }
	  }, {
	    key: "getChosenItem",
	    value: function getChosenItem() {
	      var result = null;

	      if (this.isItemChosen() && this.isItemExist(this.choseItemIdx)) {
	        result = this.menuItems[this.choseItemIdx];
	      }

	      return result;
	    }
	  }, {
	    key: "chooseNextItem",
	    value: function chooseNextItem() {
	      if (!this.isMenuEmpty() && !this.isChoseLastItem()) {
	        this.chooseItem(this.choseItemIdx + 1);
	      }

	      return this.getChosenItem();
	    }
	  }, {
	    key: "choosePrevItem",
	    value: function choosePrevItem() {
	      if (!this.isMenuEmpty() && !this.isChoseFirstItem()) {
	        this.chooseItem(this.choseItemIdx - 1);
	      }

	      return this.getChosenItem();
	    }
	  }, {
	    key: "highlightItem",
	    value: function highlightItem(index) {
	      if (this.isItemExist(index)) {
	        var item = this.getChosenItem();

	        if (item && item.layout.item) {
	          item.layout.item.classList.add("highlighted");
	        }
	      }
	    }
	  }, {
	    key: "unHighlightItem",
	    value: function unHighlightItem(index) {
	      if (this.isItemExist(index)) {
	        var item = this.getChosenItem();

	        if (item && item.layout.item) {
	          item.layout.item.classList.remove("highlighted");
	        }
	      }
	    }
	  }, {
	    key: "chooseItem",
	    value: function chooseItem(index) {
	      this.unHighlightItem(this.choseItemIdx);
	      this.choseItemIdx = index;
	      this.highlightItem(this.choseItemIdx);
	    }
	  }, {
	    key: "clearItems",
	    value: function clearItems() {
	      while (this.menuItems.length > 0) {
	        this.removeMenuItem(this.menuItems[0].id);
	      }
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this.getPopupWindow().isShown();
	    }
	  }]);
	  return Menu;
	}(main_popup.Menu);

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _inputNode = new WeakMap();

	var _menu = new WeakMap();

	var _locationList = new WeakMap();

	var _createMenu = new WeakSet();

	var _setMenuItems = new WeakSet();

	var _createMenuItem = new WeakSet();

	var _onItemSelect = new WeakSet();

	var _getLocationFromList = new WeakSet();

	var Prompt = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Prompt, _EventEmitter);

	  /** Element */

	  /** {Menu} */
	  function Prompt(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Prompt);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Prompt).call(this, props));

	    _getLocationFromList.add(babelHelpers.assertThisInitialized(_this));

	    _onItemSelect.add(babelHelpers.assertThisInitialized(_this));

	    _createMenuItem.add(babelHelpers.assertThisInitialized(_this));

	    _setMenuItems.add(babelHelpers.assertThisInitialized(_this));

	    _createMenu.add(babelHelpers.assertThisInitialized(_this));

	    _inputNode.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _menu.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _locationList.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _this.setEventNamespace('BX.Location.Widget.Prompt');

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _inputNode, props.inputNode);
	    return _this;
	  }

	  babelHelpers.createClass(Prompt, [{
	    key: "getMenu",
	    value: function getMenu() {
	      if (!babelHelpers.classPrivateFieldGet(this, _menu) || babelHelpers.classPrivateFieldGet(this, _menu).isDestroyed()) {
	        babelHelpers.classPrivateFieldSet(this, _menu, _classPrivateMethodGet(this, _createMenu, _createMenu2).call(this));
	      }

	      return babelHelpers.classPrivateFieldGet(this, _menu);
	    }
	    /**
	     * Show menu with list of locations
	     * @param {array} locationsList
	     * @param {string} searchPhrase
	     * @returns void
	     */

	  }, {
	    key: "show",
	    value: function show(locationsList, searchPhrase) {
	      if (locationsList.length > 0) {
	        _classPrivateMethodGet(this, _setMenuItems, _setMenuItems2).call(this, locationsList, searchPhrase);

	        this.getMenu().show();
	      }
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      this.getMenu().close();
	    }
	    /**
	     * @param {array<Location>} locationsList
	     * @param {string} searchPhrase
	     * @returns {*}
	     */

	  }, {
	    key: "choosePrevItem",
	    value: function choosePrevItem() {
	      var result = null;
	      var item = this.getMenu().choosePrevItem();

	      if (item) {
	        result = _classPrivateMethodGet(this, _getLocationFromList, _getLocationFromList2).call(this, item.id);
	      }

	      return result;
	    }
	  }, {
	    key: "chooseNextItem",
	    value: function chooseNextItem() {
	      var result = null;
	      var item = this.getMenu().chooseNextItem();

	      if (item) {
	        result = _classPrivateMethodGet(this, _getLocationFromList, _getLocationFromList2).call(this, item.id);
	      }

	      return result;
	    }
	  }, {
	    key: "isItemChosen",
	    value: function isItemChosen() {
	      return babelHelpers.classPrivateFieldGet(this, _menu).isItemChosen();
	    }
	  }, {
	    key: "getChosenItem",
	    value: function getChosenItem() {
	      var result = null;
	      var menuItem = babelHelpers.classPrivateFieldGet(this, _menu).getChosenItem();

	      if (menuItem && menuItem.id) {
	        result = _classPrivateMethodGet(this, _getLocationFromList, _getLocationFromList2).call(this, menuItem.id);
	      }

	      return result;
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return this.getMenu().isShown();
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _menu)) {
	        babelHelpers.classPrivateFieldGet(this, _menu).destroy();
	        babelHelpers.classPrivateFieldSet(this, _menu, null);
	      }

	      babelHelpers.classPrivateFieldSet(this, _locationList, null);
	    }
	  }], [{
	    key: "createMenuItemText",
	    value: function createMenuItemText(locationName, searchPhrase) {
	      var result = locationName.slice();

	      if (!searchPhrase || searchPhrase.length <= 0) {
	        return result;
	      }

	      var spWords = searchPhrase.replace(/,+/gi, '').split(new RegExp(/\s+/g));
	      var pattern = new RegExp("(".concat(spWords.join('|'), ")"), 'gi');
	      result = locationName.replace(pattern, function (match) {
	        return "<strong>".concat(match, "</strong>");
	      });
	      return result;
	    }
	  }]);
	  return Prompt;
	}(main_core_events.EventEmitter);

	babelHelpers.defineProperty(Prompt, "onItemSelectedEvent", 'onItemSelected');

	var _createMenu2 = function _createMenu2() {
	  return new Menu({
	    bindElement: babelHelpers.classPrivateFieldGet(this, _inputNode),
	    autoHide: false,
	    closeByEsc: true
	  });
	};

	var _setMenuItems2 = function _setMenuItems2(locationsList, searchPhrase) {
	  var _this2 = this;

	  this.getMenu().clearItems();

	  if (Array.isArray(locationsList)) {
	    babelHelpers.classPrivateFieldSet(this, _locationList, locationsList.slice());
	    locationsList.forEach(function (location) {
	      _this2.getMenu().addMenuItem(_classPrivateMethodGet(_this2, _createMenuItem, _createMenuItem2).call(_this2, location, searchPhrase));
	    });
	  }
	};

	var _createMenuItem2 = function _createMenuItem2(location, searchPhrase) {
	  var _this3 = this;

	  var externalId = location.externalId;
	  return {
	    id: externalId,
	    title: location.name,
	    html: Prompt.createMenuItemText(location.name, searchPhrase),
	    onclick: function onclick(event, item) {
	      _classPrivateMethodGet(_this3, _onItemSelect, _onItemSelect2).call(_this3, externalId);

	      _this3.close();
	    }
	  };
	};

	var _onItemSelect2 = function _onItemSelect2(externalId) {
	  var location = _classPrivateMethodGet(this, _getLocationFromList, _getLocationFromList2).call(this, externalId);

	  if (location) {
	    this.emit(Prompt.onItemSelectedEvent, {
	      location: location
	    });
	  }
	};

	var _getLocationFromList2 = function _getLocationFromList2(externalId) {
	  var result = null;

	  var _iterator = _createForOfIteratorHelper(babelHelpers.classPrivateFieldGet(this, _locationList)),
	      _step;

	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var location = _step.value;

	      if (location.externalId === externalId) {
	        result = location;
	        break;
	      }
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }

	  if (!result) {
	    BX.debug('Location with externalId ' + externalId + ' was not found');
	  }

	  return result;
	};

	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } return method; }

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	function _classPrivateMethodGet$1(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	/**
	 * @mixes EventEmitter
	 */

	var _address = new WeakMap();

	var _addressString = new WeakMap();

	var _languageId = new WeakMap();

	var _addressFormat = new WeakMap();

	var _locationRepository = new WeakMap();

	var _prompt = new WeakMap();

	var _autocompleteService = new WeakMap();

	var _minCharsCountToAutocomplete = new WeakMap();

	var _promptDelay = new WeakMap();

	var _maxPromptDelay = new WeakMap();

	var _timerId = new WeakMap();

	var _inputNode$1 = new WeakMap();

	var _searchPhrase = new WeakMap();

	var _state = new WeakMap();

	var _isDestroyed = new WeakMap();

	var _prevKeyUpTime = new WeakMap();

	var _avgKeyUpDelay = new WeakMap();

	var _isAutocompleteRequestStarted = new WeakMap();

	var _convertAddressToString = new WeakSet();

	var _onInputFocusOut = new WeakSet();

	var _onInputFocus = new WeakSet();

	var _getInputValue = new WeakSet();

	var _setAddressFromInput = new WeakSet();

	var _onDocumentClick = new WeakSet();

	var _onPromptsReceived = new WeakSet();

	var _onPromptItemSelected = new WeakSet();

	var _setState = new WeakSet();

	var _fulfillSelection = new WeakSet();

	var _onAddressChangedEventEmit = new WeakSet();

	var _getLocationDetails = new WeakSet();

	var _convertStringToAddress = new WeakSet();

	var _onLocationSelect = new WeakSet();

	var _onInputKeyUp = new WeakSet();

	var _computePromptDelay = new WeakSet();

	var _showPromptInner = new WeakSet();

	var _createTimer = new WeakSet();

	var Autocomplete = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Autocomplete, _EventEmitter);

	  /** {Address} */

	  /** {String} */

	  /** {String} */

	  /** {Format} */

	  /** {LocationRepository} */

	  /** {Prompt} */

	  /** {AutocompleteServiceBase} */

	  /** @type {number} */

	  /** {number} miliseconds promptDelay before the searching will start */

	  /** {number} */

	  /** {number} */

	  /** {Element} */
	  function Autocomplete(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Autocomplete);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Autocomplete).call(this, props));

	    _createTimer.add(babelHelpers.assertThisInitialized(_this));

	    _showPromptInner.add(babelHelpers.assertThisInitialized(_this));

	    _computePromptDelay.add(babelHelpers.assertThisInitialized(_this));

	    _onInputKeyUp.add(babelHelpers.assertThisInitialized(_this));

	    _onLocationSelect.add(babelHelpers.assertThisInitialized(_this));

	    _convertStringToAddress.add(babelHelpers.assertThisInitialized(_this));

	    _getLocationDetails.add(babelHelpers.assertThisInitialized(_this));

	    _onAddressChangedEventEmit.add(babelHelpers.assertThisInitialized(_this));

	    _fulfillSelection.add(babelHelpers.assertThisInitialized(_this));

	    _setState.add(babelHelpers.assertThisInitialized(_this));

	    _onPromptItemSelected.add(babelHelpers.assertThisInitialized(_this));

	    _onPromptsReceived.add(babelHelpers.assertThisInitialized(_this));

	    _onDocumentClick.add(babelHelpers.assertThisInitialized(_this));

	    _setAddressFromInput.add(babelHelpers.assertThisInitialized(_this));

	    _getInputValue.add(babelHelpers.assertThisInitialized(_this));

	    _onInputFocus.add(babelHelpers.assertThisInitialized(_this));

	    _onInputFocusOut.add(babelHelpers.assertThisInitialized(_this));

	    _convertAddressToString.add(babelHelpers.assertThisInitialized(_this));

	    _address.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressString.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: ''
	    });

	    _languageId.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressFormat.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _locationRepository.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _prompt.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _autocompleteService.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _minCharsCountToAutocomplete.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _promptDelay.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _maxPromptDelay.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _timerId.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _inputNode$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _searchPhrase.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: {
	        requested: '',
	        current: '',
	        dropped: ''
	      }
	    });

	    _state.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _isDestroyed.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _prevKeyUpTime.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _avgKeyUpDelay.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _isAutocompleteRequestStarted.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _this.setEventNamespace('BX.Location.Widget.Autocomplete');

	    if (!(props.addressFormat instanceof location_core.Format)) {
	      throw new Error('props.addressFormat must be type of Format');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _addressFormat, props.addressFormat);

	    if (!(props.autocompleteService instanceof location_core.AutocompleteServiceBase)) {
	      throw new Error('props.autocompleteService must be type of AutocompleteServiceBase');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _autocompleteService, props.autocompleteService);

	    if (!props.languageId) {
	      throw new Error('props.languageId must be defined');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _languageId, props.languageId);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _address, props.address);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _locationRepository, props.locationRepository || new location_core.LocationRepository());
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _promptDelay, props.promptDelay || 500);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _maxPromptDelay, props.maxPromptDelay || 1500);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _minCharsCountToAutocomplete, props.minCharsCountToAutocomplete || 3);

	    _classPrivateMethodGet$1(babelHelpers.assertThisInitialized(_this), _setState, _setState2).call(babelHelpers.assertThisInitialized(_this), State.INITIAL);

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _avgKeyUpDelay, babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _promptDelay));
	    return _this;
	  }

	  babelHelpers.createClass(Autocomplete, [{
	    key: "render",
	    value: function render(props) {
	      babelHelpers.classPrivateFieldSet(this, _inputNode$1, props.inputNode);
	      babelHelpers.classPrivateFieldSet(this, _addressString, babelHelpers.classPrivateFieldGet(this, _inputNode$1).value);
	      babelHelpers.classPrivateFieldSet(this, _address, props.address);
	      babelHelpers.classPrivateFieldGet(this, _inputNode$1).addEventListener('keyup', _classPrivateMethodGet$1(this, _onInputKeyUp, _onInputKeyUp2).bind(this));
	      babelHelpers.classPrivateFieldGet(this, _inputNode$1).addEventListener('focus', _classPrivateMethodGet$1(this, _onInputFocus, _onInputFocus2).bind(this));
	      babelHelpers.classPrivateFieldGet(this, _inputNode$1).addEventListener('focusout', _classPrivateMethodGet$1(this, _onInputFocusOut, _onInputFocusOut2).bind(this));
	      babelHelpers.classPrivateFieldSet(this, _prompt, new Prompt({
	        inputNode: props.inputNode
	      }));
	      babelHelpers.classPrivateFieldGet(this, _prompt).subscribe(Prompt.onItemSelectedEvent, _classPrivateMethodGet$1(this, _onPromptItemSelected, _onPromptItemSelected2).bind(this));
	      document.addEventListener('click', _classPrivateMethodGet$1(this, _onDocumentClick, _onDocumentClick2).bind(this));
	    }
	  }, {
	    key: "onAddressChangedEventSubscribe",

	    /**
	     * Subscribe on changed event
	     * @param {Function} listener
	     */
	    value: function onAddressChangedEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onAddressChangedEvent), listener);
	    }
	    /**
	     * Subscribe on loading event
	     * @param {Function} listener
	     */

	  }, {
	    key: "onStateChangedEventSubscribe",
	    value: function onStateChangedEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onStateChangedEvent), listener);
	    }
	    /**
	     * @param {Function} listener
	     */

	  }, {
	    key: "onSearchStartedEventSubscribe",
	    value: function onSearchStartedEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onSearchStartedEvent), listener);
	    }
	    /**
	     * @param {Function} listener
	     */

	  }, {
	    key: "onSearchCompletedEventSubscribe",
	    value: function onSearchCompletedEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onSearchCompletedEvent), listener);
	    }
	    /**
	     * Is called when autocompleteService returned location list
	     * @param {array} locationsList
	     * @param {object} params
	     */

	  }, {
	    key: "showPrompt",

	    /**
	     * @param {string} searchPhrase
	     * @param {Object} params
	     */
	    value: function showPrompt(searchPhrase, params) {
	      babelHelpers.classPrivateFieldGet(this, _searchPhrase).requested = searchPhrase;
	      babelHelpers.classPrivateFieldGet(this, _searchPhrase).current = searchPhrase;
	      babelHelpers.classPrivateFieldGet(this, _searchPhrase).dropped = '';

	      _classPrivateMethodGet$1(this, _showPromptInner, _showPromptInner2).call(this, searchPhrase, params, _classPrivateMethodGet$1(this, _computePromptDelay, _computePromptDelay2).call(this));
	    }
	    /**
	     * @returns {number}
	     */

	  }, {
	    key: "closePrompt",
	    value: function closePrompt() {
	      if (babelHelpers.classPrivateFieldGet(this, _prompt)) {
	        babelHelpers.classPrivateFieldGet(this, _prompt).close();
	      }
	    }
	  }, {
	    key: "isPromptShown",
	    value: function isPromptShown() {
	      if (babelHelpers.classPrivateFieldGet(this, _prompt)) {
	        babelHelpers.classPrivateFieldGet(this, _prompt).isShown();
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _isDestroyed)) {
	        return;
	      }

	      main_core.Event.unbindAll(this);

	      if (babelHelpers.classPrivateFieldGet(this, _prompt)) {
	        babelHelpers.classPrivateFieldGet(this, _prompt).destroy();
	        babelHelpers.classPrivateFieldSet(this, _prompt, null);
	      }

	      babelHelpers.classPrivateFieldSet(this, _timerId, null);

	      if (babelHelpers.classPrivateFieldGet(this, _inputNode$1)) {
	        babelHelpers.classPrivateFieldGet(this, _inputNode$1).removeEventListener('keyup', _classPrivateMethodGet$1(this, _onInputKeyUp, _onInputKeyUp2));
	        babelHelpers.classPrivateFieldGet(this, _inputNode$1).removeEventListener('focus', _classPrivateMethodGet$1(this, _onInputFocus, _onInputFocus2));
	        babelHelpers.classPrivateFieldGet(this, _inputNode$1).removeEventListener('focusout', _classPrivateMethodGet$1(this, _onInputFocusOut, _onInputFocusOut2));
	      }

	      document.removeEventListener('click', _classPrivateMethodGet$1(this, _onDocumentClick, _onDocumentClick2));
	      babelHelpers.classPrivateFieldSet(this, _isDestroyed, true);
	    }
	  }, {
	    key: "address",

	    /**
	     * @param address
	     */
	    set: function set(address) {
	      babelHelpers.classPrivateFieldSet(this, _address, address);

	      if (babelHelpers.classPrivateFieldGet(this, _inputNode$1)) {
	        babelHelpers.classPrivateFieldSet(this, _addressString, babelHelpers.classPrivateFieldGet(this, _inputNode$1).value);
	      }
	    },

	    /**
	     * @returns {Address}
	     */
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _address);
	    }
	    /**
	     * @returns {Prompt}
	     */

	  }, {
	    key: "prompt",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _prompt);
	    }
	  }, {
	    key: "state",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _state);
	    }
	  }]);
	  return Autocomplete;
	}(main_core_events.EventEmitter);

	var _splitPhrase = function _splitPhrase(phrase) {
	  phrase = phrase.trim();

	  if (phrase.length <= 0) {
	    return ['', ''];
	  }

	  var tailPosition = phrase.lastIndexOf(' ');

	  if (tailPosition <= 0) {
	    return ['', ''];
	  }

	  return [phrase.slice(0, tailPosition), phrase.slice(tailPosition + 1)];
	};

	var _onAddressChangedEvent = {
	  writable: true,
	  value: 'onAddressChanged'
	};
	var _onStateChangedEvent = {
	  writable: true,
	  value: 'onStateChanged'
	};
	var _onSearchStartedEvent = {
	  writable: true,
	  value: 'onSearchStarted'
	};
	var _onSearchCompletedEvent = {
	  writable: true,
	  value: 'onSearchCompleted'
	};

	var _convertAddressToString2 = function _convertAddressToString2(address) {
	  if (!address) {
	    return '';
	  }

	  return address.toString(babelHelpers.classPrivateFieldGet(this, _addressFormat), location_core.AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE, location_core.AddressStringConverter.CONTENT_TYPE_TEXT);
	};

	var _onInputFocusOut2 = function _onInputFocusOut2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed)) {
	    return;
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _state) === State.DATA_INPUTTING) {
	    _classPrivateMethodGet$1(this, _setState, _setState2).call(this, State.DATA_SELECTED);

	    _classPrivateMethodGet$1(this, _setAddressFromInput, _setAddressFromInput2).call(this);
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _prompt)) {
	    babelHelpers.classPrivateFieldGet(this, _prompt).close();
	  }
	};

	var _onInputFocus2 = function _onInputFocus2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed)) {
	    return;
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _address) && !babelHelpers.classPrivateFieldGet(this, _address).location && babelHelpers.classPrivateFieldGet(this, _inputNode$1).value.length > 0) {
	    this.showPrompt(babelHelpers.classPrivateFieldGet(this, _inputNode$1).value, {});
	  }
	};

	var _getInputValue2 = function _getInputValue2() {
	  var result = '';

	  if (babelHelpers.classPrivateFieldGet(this, _inputNode$1)) {
	    result = babelHelpers.classPrivateFieldGet(this, _inputNode$1).value;
	  }

	  return result;
	};

	var _setAddressFromInput2 = function _setAddressFromInput2() {
	  babelHelpers.classPrivateFieldSet(this, _address, _classPrivateMethodGet$1(this, _convertStringToAddress, _convertStringToAddress2).call(this, _classPrivateMethodGet$1(this, _getInputValue, _getInputValue2).call(this)));

	  _classPrivateMethodGet$1(this, _onAddressChangedEventEmit, _onAddressChangedEventEmit2).call(this);
	};

	var _onDocumentClick2 = function _onDocumentClick2(event) {
	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed)) {
	    return;
	  }

	  if (event.target === babelHelpers.classPrivateFieldGet(this, _inputNode$1)) {
	    return;
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _prompt).isShown()) {
	    babelHelpers.classPrivateFieldGet(this, _prompt).close();
	  }
	};

	var _onPromptsReceived2 = function _onPromptsReceived2(locationsList, params) {
	  var _this2 = this;

	  if (Array.isArray(locationsList) && locationsList.length > 0) {
	    babelHelpers.classPrivateFieldGet(this, _prompt).show(locationsList, babelHelpers.classPrivateFieldGet(this, _searchPhrase).requested);
	  } else {
	    var split = _classStaticPrivateMethodGet(Autocomplete, Autocomplete, _splitPhrase).call(Autocomplete, babelHelpers.classPrivateFieldGet(this, _searchPhrase).current);

	    babelHelpers.classPrivateFieldGet(this, _searchPhrase).current = split[0];
	    babelHelpers.classPrivateFieldGet(this, _searchPhrase).dropped = split[1] + ' ' + babelHelpers.classPrivateFieldGet(this, _searchPhrase).dropped;

	    if (babelHelpers.classPrivateFieldGet(this, _searchPhrase).current.length > 0) {
	      _classPrivateMethodGet$1(this, _showPromptInner, _showPromptInner2).call(this, babelHelpers.classPrivateFieldGet(this, _searchPhrase).current, params, 1);
	    } else {
	      babelHelpers.classPrivateFieldGet(this, _prompt).getMenu().clearItems();
	      babelHelpers.classPrivateFieldGet(this, _prompt).getMenu().addMenuItem({
	        id: 'notFound',
	        html: "<span>".concat(main_core.Loc.getMessage('LOCATION_WIDGET_PROMPT_RESULTS_NOT_FOUND'), "</span>"),
	        onclick: function onclick(event, item) {
	          babelHelpers.classPrivateFieldGet(_this2, _prompt).close();
	        }
	      });
	      babelHelpers.classPrivateFieldGet(this, _prompt).getMenu().show();
	    }
	  }
	};

	var _onPromptItemSelected2 = function _onPromptItemSelected2(event) {
	  if (event.data.location) {
	    _classPrivateMethodGet$1(this, _fulfillSelection, _fulfillSelection2).call(this, event.data.location);
	  }
	};

	var _setState2 = function _setState2(state) {
	  babelHelpers.classPrivateFieldSet(this, _state, state);
	  this.emit(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onStateChangedEvent), {
	    state: babelHelpers.classPrivateFieldGet(this, _state)
	  });
	};

	var _fulfillSelection2 = function _fulfillSelection2(location) {
	  var _this3 = this;

	  var result;

	  _classPrivateMethodGet$1(this, _setState, _setState2).call(this, State.DATA_SELECTED);

	  if (location) {
	    result = _classPrivateMethodGet$1(this, _getLocationDetails, _getLocationDetails2).call(this, location).then(function (location) {
	      _classPrivateMethodGet$1(_this3, _onLocationSelect, _onLocationSelect2).call(_this3, location);

	      return true;
	    }, function (response) {
	      return location_core.ErrorPublisher.getInstance().notify(response.errors);
	    });
	  } else {
	    result = new Promise(function (resolve) {
	      _classPrivateMethodGet$1(_this3, _onLocationSelect, _onLocationSelect2).call(_this3, null);

	      resolve();
	    });
	  }

	  return result;
	};

	var _onAddressChangedEventEmit2 = function _onAddressChangedEventEmit2() {
	  babelHelpers.classPrivateFieldSet(this, _addressString, babelHelpers.classPrivateFieldGet(this, _address) ? _classPrivateMethodGet$1(this, _convertAddressToString, _convertAddressToString2).call(this, babelHelpers.classPrivateFieldGet(this, _address)) : '');
	  this.emit(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onAddressChangedEvent), {
	    address: babelHelpers.classPrivateFieldGet(this, _address)
	  });
	};

	var _getLocationDetails2 = function _getLocationDetails2(location) {
	  var _this4 = this;

	  _classPrivateMethodGet$1(this, _setState, _setState2).call(this, State.DATA_LOADING);

	  return babelHelpers.classPrivateFieldGet(this, _locationRepository).findByExternalId(location.externalId, location.sourceCode, location.languageId).then(function (location) {
	    _classPrivateMethodGet$1(_this4, _setState, _setState2).call(_this4, State.DATA_LOADED);

	    return location;
	  }, function (response) {
	    location_core.ErrorPublisher.getInstance().notify(response.errors);
	  });
	};

	var _convertStringToAddress2 = function _convertStringToAddress2(addressString) {
	  var result = new location_core.Address({
	    languageId: babelHelpers.classPrivateFieldGet(this, _languageId)
	  });
	  result.setFieldValue(babelHelpers.classPrivateFieldGet(this, _addressFormat).fieldForUnRecognized, addressString);
	  return result;
	};

	var _onLocationSelect2 = function _onLocationSelect2(location) {
	  babelHelpers.classPrivateFieldSet(this, _address, location ? location.toAddress() : null);

	  if (babelHelpers.classPrivateFieldGet(this, _address) && babelHelpers.classPrivateFieldGet(this, _searchPhrase).dropped.length > 0) {
	    babelHelpers.classPrivateFieldGet(this, _address).setFieldValue(babelHelpers.classPrivateFieldGet(this, _addressFormat).fieldForUnRecognized, babelHelpers.classPrivateFieldGet(this, _searchPhrase).dropped);
	  }

	  _classPrivateMethodGet$1(this, _onAddressChangedEventEmit, _onAddressChangedEventEmit2).call(this);
	};

	var _onInputKeyUp2 = function _onInputKeyUp2(e) {
	  var _this5 = this;

	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed)) {
	    return;
	  }

	  var now = Date.now();

	  if (babelHelpers.classPrivateFieldGet(this, _prevKeyUpTime)) {
	    var delta = now - babelHelpers.classPrivateFieldGet(this, _prevKeyUpTime);
	    babelHelpers.classPrivateFieldSet(this, _avgKeyUpDelay, (babelHelpers.classPrivateFieldGet(this, _avgKeyUpDelay) + delta) / 2);
	  }

	  babelHelpers.classPrivateFieldSet(this, _prevKeyUpTime, now);

	  if (babelHelpers.classPrivateFieldGet(this, _state) !== State.DATA_INPUTTING && babelHelpers.classPrivateFieldGet(this, _addressString).trim() !== _classPrivateMethodGet$1(this, _getInputValue, _getInputValue2).call(this).trim()) {
	    _classPrivateMethodGet$1(this, _setState, _setState2).call(this, State.DATA_INPUTTING);
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _prompt).isShown()) {
	    switch (e.code) {
	      case 'NumpadEnter':
	      case 'Enter':
	        if (babelHelpers.classPrivateFieldGet(this, _prompt).isItemChosen()) {
	          _classPrivateMethodGet$1(this, _fulfillSelection, _fulfillSelection2).call(this, babelHelpers.classPrivateFieldGet(this, _prompt).getChosenItem()).then(function () {
	            babelHelpers.classPrivateFieldGet(_this5, _prompt).close();
	          }, function (error) {
	            return BX.debug(error);
	          });
	        }

	        return;

	      case 'Tab':
	      case 'Escape':
	        _classPrivateMethodGet$1(this, _setState, _setState2).call(this, State.DATA_SELECTED);

	        _classPrivateMethodGet$1(this, _setAddressFromInput, _setAddressFromInput2).call(this);

	        babelHelpers.classPrivateFieldGet(this, _prompt).close();
	        return;

	      case 'ArrowUp':
	        babelHelpers.classPrivateFieldGet(this, _prompt).choosePrevItem();
	        return;

	      case 'ArrowDown':
	        babelHelpers.classPrivateFieldGet(this, _prompt).chooseNextItem();
	        return;
	    }
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _addressString).trim() !== _classPrivateMethodGet$1(this, _getInputValue, _getInputValue2).call(this).trim()) {
	    this.showPrompt(babelHelpers.classPrivateFieldGet(this, _inputNode$1).value, {});
	  }
	};

	var _computePromptDelay2 = function _computePromptDelay2() {
	  var delay = babelHelpers.classPrivateFieldGet(this, _promptDelay) > babelHelpers.classPrivateFieldGet(this, _avgKeyUpDelay) ? babelHelpers.classPrivateFieldGet(this, _promptDelay) : babelHelpers.classPrivateFieldGet(this, _avgKeyUpDelay) * 1.5;
	  return delay > babelHelpers.classPrivateFieldGet(this, _maxPromptDelay) ? babelHelpers.classPrivateFieldGet(this, _maxPromptDelay) : delay;
	};

	var _showPromptInner2 = function _showPromptInner2(searchPhrase, params, promptDelay) {
	  if (searchPhrase.length > babelHelpers.classPrivateFieldGet(this, _minCharsCountToAutocomplete)) {
	    if (babelHelpers.classPrivateFieldGet(this, _timerId) !== null) {
	      clearTimeout(babelHelpers.classPrivateFieldGet(this, _timerId));
	    }

	    babelHelpers.classPrivateFieldSet(this, _timerId, _classPrivateMethodGet$1(this, _createTimer, _createTimer2).call(this, searchPhrase, params, promptDelay));
	  }
	};

	var _createTimer2 = function _createTimer2(searchPhrase, params, promptDelay) {
	  var _this6 = this;

	  return setTimeout(function () {
	    // to avoid multiple parallel requests, if server responses are too slow.
	    if (babelHelpers.classPrivateFieldGet(_this6, _isAutocompleteRequestStarted)) {
	      clearTimeout(babelHelpers.classPrivateFieldGet(_this6, _timerId));
	      babelHelpers.classPrivateFieldSet(_this6, _timerId, _classPrivateMethodGet$1(_this6, _createTimer, _createTimer2).call(_this6, searchPhrase, params, promptDelay));
	      return;
	    }

	    _this6.emit(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onSearchStartedEvent));

	    babelHelpers.classPrivateFieldSet(_this6, _isAutocompleteRequestStarted, true);
	    babelHelpers.classPrivateFieldGet(_this6, _autocompleteService).autocomplete(searchPhrase, params).then(function (locationsList) {
	      babelHelpers.classPrivateFieldSet(_this6, _timerId, null);

	      _classPrivateMethodGet$1(_this6, _onPromptsReceived, _onPromptsReceived2).call(_this6, locationsList, params);

	      _this6.emit(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onSearchCompletedEvent));

	      babelHelpers.classPrivateFieldSet(_this6, _isAutocompleteRequestStarted, false);
	    }, function (error) {
	      _this6.emit(_classStaticPrivateFieldSpecGet(Autocomplete, Autocomplete, _onSearchCompletedEvent));

	      babelHelpers.classPrivateFieldSet(_this6, _isAutocompleteRequestStarted, false);
	      BX.debug(error);
	    });
	  }, promptDelay);
	};

	/**
	 * Complex address widget
	 */

	var _autocomplete = new WeakMap();

	var _addressWidget = new WeakMap();

	var AutocompleteFeature = /*#__PURE__*/function (_BaseFeature) {
	  babelHelpers.inherits(AutocompleteFeature, _BaseFeature);

	  function AutocompleteFeature(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, AutocompleteFeature);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(AutocompleteFeature).call(this));

	    _autocomplete.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressWidget.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    if (!(props.autocomplete instanceof Autocomplete)) {
	      BX.debug('props.autocomplete  must be instance of Autocomplete');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _autocomplete, props.autocomplete);
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _autocomplete).onAddressChangedEventSubscribe(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget).setAddressByFeature(data.address, babelHelpers.assertThisInitialized(_this));
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _autocomplete).onStateChangedEventSubscribe(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget).setStateByFeature(data.state);
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _autocomplete).onSearchStartedEventSubscribe(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget).emitFeatureEvent({
	        feature: babelHelpers.assertThisInitialized(_this),
	        eventCode: AutocompleteFeature.searchStartedEvent,
	        payload: data
	      });
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _autocomplete).onSearchCompletedEventSubscribe(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget).emitFeatureEvent({
	        feature: babelHelpers.assertThisInitialized(_this),
	        eventCode: AutocompleteFeature.searchCompletedEvent,
	        payload: data
	      });
	    });
	    return _this;
	  }

	  babelHelpers.createClass(AutocompleteFeature, [{
	    key: "resetView",
	    value: function resetView() {
	      babelHelpers.classPrivateFieldGet(this, _autocomplete).closePrompt();
	    }
	  }, {
	    key: "render",
	    value: function render(props) {
	      if (babelHelpers.classPrivateFieldGet(this, _addressWidget).mode === location_core.ControlMode.edit) {
	        babelHelpers.classPrivateFieldGet(this, _autocomplete).render({
	          inputNode: babelHelpers.classPrivateFieldGet(this, _addressWidget).inputNode,
	          address: babelHelpers.classPrivateFieldGet(this, _addressWidget).address,
	          mode: babelHelpers.classPrivateFieldGet(this, _addressWidget).mode
	        });
	      }
	    }
	  }, {
	    key: "setAddress",
	    value: function setAddress(address) {
	      babelHelpers.classPrivateFieldGet(this, _autocomplete).address = address;
	    }
	  }, {
	    key: "setAddressWidget",
	    value: function setAddressWidget(addressWidget) {
	      babelHelpers.classPrivateFieldSet(this, _addressWidget, addressWidget);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.classPrivateFieldGet(this, _autocomplete).destroy();
	      babelHelpers.classPrivateFieldSet(this, _autocomplete, null);
	    }
	  }, {
	    key: "autocomplete",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _autocomplete);
	    }
	  }]);
	  return AutocompleteFeature;
	}(BaseFeature);

	babelHelpers.defineProperty(AutocompleteFeature, "searchStartedEvent", 'searchStarted');
	babelHelpers.defineProperty(AutocompleteFeature, "searchCompletedEvent", 'searchCompleted');

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classPrivateMethodGet$2(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	/**
	 * Props for the address widget constructor
	 */

	var _mode = new WeakMap();

	var _state$1 = new WeakMap();

	var _address$1 = new WeakMap();

	var _addressFormat$1 = new WeakMap();

	var _languageId$1 = new WeakMap();

	var _features = new WeakMap();

	var _inputNode$2 = new WeakMap();

	var _controlWrapper = new WeakMap();

	var _destroyed = new WeakMap();

	var _isAddressChangedByFeature = new WeakMap();

	var _isInputNodeValueUpdated = new WeakMap();

	var _needWarmBackendAfterAddressChanged = new WeakMap();

	var _locationRepository$1 = new WeakMap();

	var _presetLocationList = new WeakMap();

	var _addFeature = new WeakSet();

	var _executeFeatureMethod = new WeakSet();

	var _emitOnAddressChanged = new WeakSet();

	var _warmBackendAfterAddressChanged = new WeakSet();

	var _onInputFocus$1 = new WeakSet();

	var _onInputClick = new WeakSet();

	var _showPresetLocations = new WeakSet();

	var _convertAddressToString$1 = new WeakSet();

	var _setInputValue = new WeakSet();

	var _onInputFocusOut$1 = new WeakSet();

	var _getAutocompleteFeature = new WeakSet();

	var _destroyFeatures = new WeakSet();

	/**
	 * Address widget
	 */
	var Address = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Address, _EventEmitter);

	  /* If address was changed by user */

	  /* If state of the widget was changed */

	  /* Any feature-related events */

	  /**
	   * Constructor
	   * @param {AddressConstructorProps} props
	   */
	  function Address(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Address);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Address).call(this));

	    _destroyFeatures.add(babelHelpers.assertThisInitialized(_this));

	    _getAutocompleteFeature.add(babelHelpers.assertThisInitialized(_this));

	    _onInputFocusOut$1.add(babelHelpers.assertThisInitialized(_this));

	    _setInputValue.add(babelHelpers.assertThisInitialized(_this));

	    _convertAddressToString$1.add(babelHelpers.assertThisInitialized(_this));

	    _showPresetLocations.add(babelHelpers.assertThisInitialized(_this));

	    _onInputClick.add(babelHelpers.assertThisInitialized(_this));

	    _onInputFocus$1.add(babelHelpers.assertThisInitialized(_this));

	    _warmBackendAfterAddressChanged.add(babelHelpers.assertThisInitialized(_this));

	    _emitOnAddressChanged.add(babelHelpers.assertThisInitialized(_this));

	    _executeFeatureMethod.add(babelHelpers.assertThisInitialized(_this));

	    _addFeature.add(babelHelpers.assertThisInitialized(_this));

	    _mode.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _state$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _address$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressFormat$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _languageId$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _features.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: []
	    });

	    _inputNode$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _controlWrapper.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _destroyed.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _isAddressChangedByFeature.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _isInputNodeValueUpdated.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _needWarmBackendAfterAddressChanged.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: true
	    });

	    _locationRepository$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _presetLocationList.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: []
	    });

	    _this.setEventNamespace('BX.Location.Widget.Address');

	    if (!(props.addressFormat instanceof location_core.Format)) {
	      BX.debug('addressFormat must be instance of Format');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _addressFormat$1, props.addressFormat);

	    if (props.address && !(props.address instanceof location_core.Address)) {
	      BX.debug('address must be instance of Address');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _address$1, props.address || null);

	    if (!location_core.ControlMode.isValid(props.mode)) {
	      BX.debug('mode must be valid ControlMode');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _mode, props.mode);

	    if (!main_core.Type.isString(props.languageId)) {
	      throw new TypeError('props.languageId must be type of string');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _languageId$1, props.languageId);

	    if (props.features) {
	      if (!main_core.Type.isArray(props.features)) {
	        throw new TypeError('features must be an array');
	      }

	      props.features.forEach(function (feature) {
	        _classPrivateMethodGet$2(babelHelpers.assertThisInitialized(_this), _addFeature, _addFeature2).call(babelHelpers.assertThisInitialized(_this), feature);
	      });
	    }

	    if (main_core.Type.isBoolean(props.needWarmBackendAfterAddressChanged)) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _needWarmBackendAfterAddressChanged, props.needWarmBackendAfterAddressChanged);
	    }

	    if (props.locationRepository instanceof location_core.LocationRepository) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _locationRepository$1, props.locationRepository);
	    } else if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _needWarmBackendAfterAddressChanged)) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _locationRepository$1, new location_core.LocationRepository());
	    }

	    if (props.presetLocationList) {
	      if (!main_core.Type.isArray(props.presetLocationList)) {
	        throw new TypeError('Preset location list must be an array');
	      }

	      var _iterator = _createForOfIteratorHelper$1(props.presetLocationList),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var location = _step.value;

	          if (!(location instanceof location_core.Location)) {
	            BX.debug('location must be instance of Location');
	          }

	          babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _presetLocationList).push(location);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _state$1, State.INITIAL);
	    return _this;
	  }
	  /**
	   * @param {AddressEntity} address
	   * @param {BaseFeature} sourceFeature
	   * @internal
	   */


	  babelHelpers.createClass(Address, [{
	    key: "setAddressByFeature",
	    value: function setAddressByFeature(address, sourceFeature) {
	      var addressId = babelHelpers.classPrivateFieldGet(this, _address$1) ? babelHelpers.classPrivateFieldGet(this, _address$1).id : 0;
	      babelHelpers.classPrivateFieldSet(this, _address$1, address);

	      if (addressId > 0) {
	        babelHelpers.classPrivateFieldGet(this, _address$1).id = addressId;
	      }

	      babelHelpers.classPrivateFieldSet(this, _isAddressChangedByFeature, true);

	      _classPrivateMethodGet$2(this, _setInputValue, _setInputValue2).call(this, address);

	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'setAddress', [address], sourceFeature);

	      if (babelHelpers.classPrivateFieldGet(this, _state$1) !== State.DATA_INPUTTING) {
	        _classPrivateMethodGet$2(this, _emitOnAddressChanged, _emitOnAddressChanged2).call(this);
	      }
	    }
	  }, {
	    key: "emitFeatureEvent",
	    value: function emitFeatureEvent(featureEvent) {
	      this.emit(Address.onFeatureEvent, featureEvent);
	    }
	    /**
	     * Add feature to the widget
	     * @param {BaseFeature} feature
	     */

	  }, {
	    key: "onInputKeyup",
	    value: function onInputKeyup(e) {
	      var value = babelHelpers.classPrivateFieldGet(this, _inputNode$2).value;

	      switch (e.code) {
	        case 'Tab':
	        case 'Esc':
	        case 'Enter':
	        case 'NumpadEnter':
	          this.resetView();
	          break;

	        default:
	          babelHelpers.classPrivateFieldSet(this, _isInputNodeValueUpdated, true);
	      }

	      if (value.length === 0 && babelHelpers.classPrivateFieldGet(this, _presetLocationList).length > 0) {
	        _classPrivateMethodGet$2(this, _showPresetLocations, _showPresetLocations2).call(this);
	      }
	    }
	  }, {
	    key: "resetView",
	    value: function resetView() {
	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'resetView');
	    }
	    /**
	     * Render Widget
	     * @param {AddressRenderProps} props
	     */

	  }, {
	    key: "render",
	    value: function render(props) {
	      if (!main_core.Type.isDomNode(props.controlWrapper)) {
	        BX.debug('props.controlWrapper  must be instance of Element');
	      }

	      babelHelpers.classPrivateFieldSet(this, _controlWrapper, props.controlWrapper);

	      if (babelHelpers.classPrivateFieldGet(this, _mode) === location_core.ControlMode.edit) {
	        if (!main_core.Type.isDomNode(props.inputNode)) {
	          BX.debug('props.inputNode  must be instance of Element');
	        }

	        babelHelpers.classPrivateFieldSet(this, _inputNode$2, props.inputNode);

	        _classPrivateMethodGet$2(this, _setInputValue, _setInputValue2).call(this, babelHelpers.classPrivateFieldGet(this, _address$1));

	        main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'focus', _classPrivateMethodGet$2(this, _onInputFocus$1, _onInputFocus2$1).bind(this));
	        main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'focusout', _classPrivateMethodGet$2(this, _onInputFocusOut$1, _onInputFocusOut2$1).bind(this));
	        main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'keyup', this.onInputKeyup.bind(this));
	        main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'click', _classPrivateMethodGet$2(this, _onInputClick, _onInputClick2).bind(this));
	      }

	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'render', [props]);
	    }
	  }, {
	    key: "setStateByFeature",
	    value: function setStateByFeature(state) {
	      babelHelpers.classPrivateFieldSet(this, _state$1, state);
	      this.emit(Address.onStateChangedEvent, {
	        state: state
	      });
	    }
	  }, {
	    key: "subscribeOnStateChangedEvent",
	    value: function subscribeOnStateChangedEvent(listener) {
	      this.subscribe(Address.onStateChangedEvent, listener);
	    }
	  }, {
	    key: "subscribeOnAddressChangedEvent",
	    value: function subscribeOnAddressChangedEvent(listener) {
	      this.subscribe(Address.onAddressChangedEvent, listener);
	    }
	  }, {
	    key: "subscribeOnFeatureEvent",
	    value: function subscribeOnFeatureEvent(listener) {
	      this.subscribe(Address.onFeatureEvent, listener);
	    }
	  }, {
	    key: "subscribeOnErrorEvent",
	    value: function subscribeOnErrorEvent(listener) {
	      location_core.ErrorPublisher.getInstance().subscribe(listener);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _destroyed)) {
	        return;
	      }

	      main_core.Event.unbindAll(this);
	      main_core.Event.unbind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'focus', _classPrivateMethodGet$2(this, _onInputFocus$1, _onInputFocus2$1));
	      main_core.Event.unbind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'focusout', _classPrivateMethodGet$2(this, _onInputFocusOut$1, _onInputFocusOut2$1));
	      main_core.Event.unbind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'keyup', this.onInputKeyup);
	      main_core.Event.unbind(babelHelpers.classPrivateFieldGet(this, _inputNode$2), 'click', this.onInputClick);

	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'destroy');

	      _classPrivateMethodGet$2(this, _destroyFeatures, _destroyFeatures2).call(this);

	      babelHelpers.classPrivateFieldSet(this, _destroyed, true);
	    }
	  }, {
	    key: "isDestroyed",
	    value: function isDestroyed() {
	      return babelHelpers.classPrivateFieldGet(this, _destroyed);
	    }
	  }, {
	    key: "features",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _features);
	    }
	  }, {
	    key: "controlWrapper",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _controlWrapper);
	    }
	  }, {
	    key: "inputNode",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _inputNode$2);
	    }
	  }, {
	    key: "address",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _address$1);
	    },
	    set: function set(address) {
	      if (address && !(address instanceof location_core.Address)) {
	        BX.debug('address must be instance of Address');
	      }

	      babelHelpers.classPrivateFieldSet(this, _address$1, address);

	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'setAddress', [address]);

	      babelHelpers.classPrivateFieldSet(this, _isInputNodeValueUpdated, false);
	      babelHelpers.classPrivateFieldSet(this, _isAddressChangedByFeature, false);

	      _classPrivateMethodGet$2(this, _setInputValue, _setInputValue2).call(this, address);
	    }
	  }, {
	    key: "mode",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _mode);
	    },
	    set: function set(mode) {
	      if (!location_core.ControlMode.isValid(mode)) {
	        BX.debug('mode must be valid ControlMode');
	      }

	      babelHelpers.classPrivateFieldSet(this, _mode, mode);

	      _classPrivateMethodGet$2(this, _executeFeatureMethod, _executeFeatureMethod2).call(this, 'setMode', [mode]);
	    }
	  }, {
	    key: "state",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _state$1);
	    }
	  }, {
	    key: "addressFormat",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _addressFormat$1);
	    }
	  }]);
	  return Address;
	}(main_core_events.EventEmitter);

	babelHelpers.defineProperty(Address, "onAddressChangedEvent", 'onAddressChanged');
	babelHelpers.defineProperty(Address, "onStateChangedEvent", 'onStateChanged');
	babelHelpers.defineProperty(Address, "onFeatureEvent", 'onFeatureEvent');

	var _addFeature2 = function _addFeature2(feature) {
	  if (!(feature instanceof BaseFeature)) {
	    BX.debug('feature must be instance of BaseFeature');
	  }

	  feature.setAddressWidget(this);
	  babelHelpers.classPrivateFieldGet(this, _features).push(feature);
	};

	var _executeFeatureMethod2 = function _executeFeatureMethod2(method) {
	  var params = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : [];
	  var excludeFeature = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
	  var result;

	  var _iterator2 = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _features)),
	      _step2;

	  try {
	    for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	      var feature = _step2.value;

	      if (feature !== excludeFeature) {
	        result = feature[method].apply(feature, params);
	      }
	    }
	  } catch (err) {
	    _iterator2.e(err);
	  } finally {
	    _iterator2.f();
	  }

	  return result;
	};

	var _emitOnAddressChanged2 = function _emitOnAddressChanged2() {
	  this.emit(Address.onAddressChangedEvent, {
	    address: babelHelpers.classPrivateFieldGet(this, _address$1)
	  });

	  if (babelHelpers.classPrivateFieldGet(this, _address$1) && babelHelpers.classPrivateFieldGet(this, _needWarmBackendAfterAddressChanged)) {
	    _classPrivateMethodGet$2(this, _warmBackendAfterAddressChanged, _warmBackendAfterAddressChanged2).call(this, babelHelpers.classPrivateFieldGet(this, _address$1));
	  }
	};

	var _warmBackendAfterAddressChanged2 = function _warmBackendAfterAddressChanged2(address) {
	  if (address.location !== null && address.location.id <= 0) {
	    babelHelpers.classPrivateFieldGet(this, _locationRepository$1).findParents(address.location);
	  }
	};

	var _onInputFocus2$1 = function _onInputFocus2(e) {
	  var value = babelHelpers.classPrivateFieldGet(this, _inputNode$2).value;

	  if (value.length > 0) {
	    BX.setCaretPosition(babelHelpers.classPrivateFieldGet(this, _inputNode$2), value.length - 1);
	  }
	};

	var _onInputClick2 = function _onInputClick2(e) {
	  var value = babelHelpers.classPrivateFieldGet(this, _inputNode$2).value;

	  if (value.length === 0 && babelHelpers.classPrivateFieldGet(this, _presetLocationList).length > 0) {
	    _classPrivateMethodGet$2(this, _showPresetLocations, _showPresetLocations2).call(this);
	  }
	};

	var _showPresetLocations2 = function _showPresetLocations2() {
	  var autocompleteFeature = _classPrivateMethodGet$2(this, _getAutocompleteFeature, _getAutocompleteFeature2).call(this);

	  if (!autocompleteFeature.autocomplete || !autocompleteFeature.autocomplete.prompt) {
	    return;
	  }

	  autocompleteFeature.autocomplete.prompt.show(babelHelpers.classPrivateFieldGet(this, _presetLocationList), '');
	};

	var _convertAddressToString2$1 = function _convertAddressToString2(address) {
	  if (!address) {
	    return '';
	  }

	  return address.toString(babelHelpers.classPrivateFieldGet(this, _addressFormat$1), location_core.AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE, location_core.AddressStringConverter.CONTENT_TYPE_TEXT);
	};

	var _setInputValue2 = function _setInputValue2(address) {
	  if (babelHelpers.classPrivateFieldGet(this, _inputNode$2)) {
	    var selectionStart = babelHelpers.classPrivateFieldGet(this, _inputNode$2).selectionStart;
	    var selectionEnd = babelHelpers.classPrivateFieldGet(this, _inputNode$2).selectionEnd;

	    var addressString = _classPrivateMethodGet$2(this, _convertAddressToString$1, _convertAddressToString2$1).call(this, address);

	    babelHelpers.classPrivateFieldGet(this, _inputNode$2).value = addressString;
	    babelHelpers.classPrivateFieldGet(this, _inputNode$2).title = addressString;
	    babelHelpers.classPrivateFieldGet(this, _inputNode$2).setSelectionRange(selectionStart, selectionEnd);
	  }
	};

	var _onInputFocusOut2$1 = function _onInputFocusOut2(e) {
	  // Seems that we don't have any autocompleter feature
	  if (babelHelpers.classPrivateFieldGet(this, _isInputNodeValueUpdated) && !babelHelpers.classPrivateFieldGet(this, _isAddressChangedByFeature)) {
	    var value = babelHelpers.classPrivateFieldGet(this, _inputNode$2).value.trim();
	    var address = new location_core.Address({
	      languageId: babelHelpers.classPrivateFieldGet(this, _languageId$1)
	    });
	    address.setFieldValue(babelHelpers.classPrivateFieldGet(this, _addressFormat$1).fieldForUnRecognized, value);
	    this.address = address;

	    _classPrivateMethodGet$2(this, _emitOnAddressChanged, _emitOnAddressChanged2).call(this);
	  }

	  babelHelpers.classPrivateFieldSet(this, _isInputNodeValueUpdated, false);
	  babelHelpers.classPrivateFieldSet(this, _isAddressChangedByFeature, false);
	};

	var _getAutocompleteFeature2 = function _getAutocompleteFeature2() {
	  var result = null;

	  var _iterator3 = _createForOfIteratorHelper$1(babelHelpers.classPrivateFieldGet(this, _features)),
	      _step3;

	  try {
	    for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	      var feature = _step3.value;

	      if (feature instanceof AutocompleteFeature) {
	        result = feature;
	        break;
	      }
	    }
	  } catch (err) {
	    _iterator3.e(err);
	  } finally {
	    _iterator3.f();
	  }

	  return result;
	};

	var _destroyFeatures2 = function _destroyFeatures2() {
	  babelHelpers.classPrivateFieldGet(this, _features).splice(0, babelHelpers.classPrivateFieldGet(this, _features).length);
	};

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"location-map-address-container\">\n\t\t\t\t<div class=\"location-map-address-icon\"></div>\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"location-map-address-text\">", "</div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet$3(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _address$2 = new WeakMap();

	var _element = new WeakMap();

	var _stringElement = new WeakMap();

	var _addressFormat$2 = new WeakMap();

	var _convertAddressToString$2 = new WeakSet();

	var AddressString = /*#__PURE__*/function () {
	  function AddressString(props) {
	    babelHelpers.classCallCheck(this, AddressString);

	    _convertAddressToString$2.add(this);

	    _address$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _element.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _stringElement.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _addressFormat$2.set(this, {
	      writable: true,
	      value: void 0
	    });

	    if (!(props.addressFormat instanceof location_core.Format)) {
	      throw new Error('addressFormat must be instance of Format');
	    }

	    babelHelpers.classPrivateFieldSet(this, _addressFormat$2, props.addressFormat);
	    babelHelpers.classPrivateFieldSet(this, _address$2, props.address);
	  }

	  babelHelpers.createClass(AddressString, [{
	    key: "render",
	    value: function render(props) {
	      babelHelpers.classPrivateFieldSet(this, _address$2, props.address);
	      babelHelpers.classPrivateFieldSet(this, _stringElement, main_core.Tag.render(_templateObject(), _classPrivateMethodGet$3(this, _convertAddressToString$2, _convertAddressToString2$2).call(this, babelHelpers.classPrivateFieldGet(this, _address$2))));
	      babelHelpers.classPrivateFieldSet(this, _element, main_core.Tag.render(_templateObject2(), babelHelpers.classPrivateFieldGet(this, _stringElement)));
	      return babelHelpers.classPrivateFieldGet(this, _element);
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (babelHelpers.classPrivateFieldGet(this, _element)) {
	        babelHelpers.classPrivateFieldGet(this, _element).style.display = 'block';
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      if (babelHelpers.classPrivateFieldGet(this, _element)) {
	        babelHelpers.classPrivateFieldGet(this, _element).style.display = 'none';
	      }
	    }
	  }, {
	    key: "isHidden",
	    value: function isHidden() {
	      return !babelHelpers.classPrivateFieldGet(this, _element) || babelHelpers.classPrivateFieldGet(this, _element).style.display === 'none';
	    }
	  }, {
	    key: "address",
	    set: function set(address) {
	      babelHelpers.classPrivateFieldSet(this, _address$2, address);

	      if (!babelHelpers.classPrivateFieldGet(this, _stringElement)) {
	        return;
	      }

	      babelHelpers.classPrivateFieldGet(this, _stringElement).innerHTML = _classPrivateMethodGet$3(this, _convertAddressToString$2, _convertAddressToString2$2).call(this, address);

	      if (!address && !this.isHidden()) {
	        this.hide();
	      } else if (address && this.isHidden()) {
	        this.show();
	      }
	    }
	  }]);
	  return AddressString;
	}();

	var _convertAddressToString2$2 = function _convertAddressToString2(address) {
	  if (!address) {
	    return '';
	  }

	  return address.toString(babelHelpers.classPrivateFieldGet(this, _addressFormat$2), location_core.AddressStringConverter.STRATEGY_TYPE_FIELD_SORT);
	};

	/**
	 * Popup window, which contains map
	 */

	var _detailsPopupPadding = new WeakMap();

	var Popup = /*#__PURE__*/function (_MainPopup) {
	  babelHelpers.inherits(Popup, _MainPopup);

	  function Popup() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, Popup);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Popup)).call.apply(_babelHelpers$getProt, [this].concat(args)));

	    _detailsPopupPadding.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: 20
	    });

	    return _this;
	  }

	  babelHelpers.createClass(Popup, [{
	    key: "getBindElement",
	    value: function getBindElement() {
	      return this.bindElement;
	    }
	  }, {
	    key: "adjustPosition",
	    value: function adjustPosition(bindOptions) {
	      if (this.bindOptions.position && this.bindOptions.position === 'right') {
	        var itemRect = this.bindElement.getBoundingClientRect();
	        var offsetLeft = itemRect.width;
	        var offsetTop = itemRect.height / 2 + babelHelpers.classPrivateFieldGet(this, _detailsPopupPadding);
	        var angleOffset = itemRect.height / 2;
	        var popupWidth = this.getPopupContainer().offsetWidth;
	        var popupHeight = this.getPopupContainer().offsetHeight;
	        var popupBottom = itemRect.top + popupHeight;
	        var clientWidth = document.documentElement.clientWidth;
	        var clientHeight = document.documentElement.clientHeight; // let's try to fit a this to the browser viewport

	        var exceeded = popupBottom - clientHeight;

	        if (exceeded > 0) {
	          var roundOffset = Math.ceil(exceeded / itemRect.height) * itemRect.height;

	          if (roundOffset > itemRect.top) {
	            // it cannot be higher than the browser viewport.
	            roundOffset -= Math.ceil((roundOffset - itemRect.top) / itemRect.height) * itemRect.height;
	          }

	          if (itemRect.bottom > popupBottom - roundOffset) {
	            // let's sync bottom boundaries.
	            roundOffset -= itemRect.bottom - (popupBottom - roundOffset) + babelHelpers.classPrivateFieldGet(this, _detailsPopupPadding);
	          }

	          offsetTop += roundOffset;
	          angleOffset += roundOffset + babelHelpers.classPrivateFieldGet(this, _detailsPopupPadding);
	        }

	        if (itemRect.left + offsetLeft + popupWidth <= clientWidth) {
	          this.setOffset({
	            offsetLeft: offsetLeft,
	            offsetTop: -offsetTop
	          });
	          this.setAngle({
	            position: 'left',
	            offset: angleOffset
	          });
	        } else {
	          this.setAngle(true);
	        }
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(Popup.prototype), "adjustPosition", this).call(this, bindOptions);
	    }
	  }]);
	  return Popup;
	}(main_popup.Popup);

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"location-map-wrapper\">\n\t\t\t\t<div class=\"location-map-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"location-map-inner\"></div>"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet$4(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	function _classStaticPrivateFieldSpecGet$1(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	var _map = new WeakMap();

	var _mode$1 = new WeakMap();

	var _address$3 = new WeakMap();

	var _popup = new WeakMap();

	var _addressString$1 = new WeakMap();

	var _addressFormat$3 = new WeakMap();

	var _gallery = new WeakMap();

	var _locationRepository$2 = new WeakMap();

	var _isMapRendered = new WeakMap();

	var _mapInnerContainer = new WeakMap();

	var _geocodingService = new WeakMap();

	var _contentWrapper = new WeakMap();

	var _renderPopup = new WeakSet();

	var _convertAddressToLocation = new WeakSet();

	var _setLocationInternal = new WeakSet();

	var _renderMap = new WeakSet();

	var MapPopup = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(MapPopup, _EventEmitter);

	  function MapPopup(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, MapPopup);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MapPopup).call(this, props));

	    _renderMap.add(babelHelpers.assertThisInitialized(_this));

	    _setLocationInternal.add(babelHelpers.assertThisInitialized(_this));

	    _convertAddressToLocation.add(babelHelpers.assertThisInitialized(_this));

	    _renderPopup.add(babelHelpers.assertThisInitialized(_this));

	    _map.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _mode$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _address$3.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _popup.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressString$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressFormat$3.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _gallery.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _locationRepository$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _isMapRendered.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _mapInnerContainer.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _geocodingService.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _contentWrapper.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _this.setEventNamespace('BX.Location.Widget.MapPopup');

	    if (!(props.map instanceof location_core.MapBase)) {
	      BX.debug('map must be instance of Map');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _map, props.map);

	    if (props.geocodingService instanceof location_core.GeocodingServiceBase) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _geocodingService, props.geocodingService);
	    }

	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _map).onLocationChangedEventSubscribe(function (event) {
	      var data = event.getData(),
	          location = data.location,
	          address = location.toAddress();
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _address$3, address);
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressString$1).address = address;

	      if (babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _gallery)) {
	        babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _gallery).location = location;
	      }

	      _this.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onChangedEvent), {
	        address: address
	      });
	    });

	    if (!(props.popup instanceof Popup)) {
	      BX.debug('popup must be instance of Popup');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _popup, props.popup);

	    if (!(props.addressFormat instanceof location_core.Format)) {
	      BX.debug('addressFormat must be instance of Format');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _addressFormat$3, props.addressFormat);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _addressString$1, new AddressString({
	      addressFormat: babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressFormat$3)
	    }));

	    if (props.gallery) {
	      babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _gallery, props.gallery);
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _locationRepository$2, props.locationRepository);
	    return _this;
	  }

	  babelHelpers.createClass(MapPopup, [{
	    key: "render",
	    value: function render(props) {
	      babelHelpers.classPrivateFieldSet(this, _address$3, props.address);
	      babelHelpers.classPrivateFieldSet(this, _mode$1, props.mode);
	      babelHelpers.classPrivateFieldSet(this, _isMapRendered, false);
	      babelHelpers.classPrivateFieldSet(this, _mapInnerContainer, main_core.Tag.render(_templateObject$1()));

	      _classPrivateMethodGet$4(this, _renderPopup, _renderPopup2).call(this, props.bindElement, babelHelpers.classPrivateFieldGet(this, _mapInnerContainer));
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this2 = this;

	      _classPrivateMethodGet$4(this, _convertAddressToLocation, _convertAddressToLocation2).call(this, babelHelpers.classPrivateFieldGet(this, _address$3)).then(function (location) {
	        if (!location) {
	          return;
	        }

	        babelHelpers.classPrivateFieldGet(_this2, _popup).show();

	        if (!babelHelpers.classPrivateFieldGet(_this2, _isMapRendered)) {
	          _classPrivateMethodGet$4(_this2, _renderMap, _renderMap2).call(_this2, {
	            location: location
	          }).then(function () {
	            if (babelHelpers.classPrivateFieldGet(_this2, _gallery)) {
	              babelHelpers.classPrivateFieldGet(_this2, _gallery).location = location;
	            }

	            _this2.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onShowedEvent));
	          });

	          babelHelpers.classPrivateFieldSet(_this2, _isMapRendered, true);
	        } else {
	          if (babelHelpers.classPrivateFieldGet(_this2, _gallery)) {
	            babelHelpers.classPrivateFieldGet(_this2, _gallery).location = location;
	          }

	          _this2.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onShowedEvent));
	        }
	      });
	    }
	  }, {
	    key: "isShown",
	    value: function isShown() {
	      return babelHelpers.classPrivateFieldGet(this, _popup).isShown();
	    }
	  }, {
	    key: "close",
	    value: function close() {
	      babelHelpers.classPrivateFieldGet(this, _popup).close();
	      this.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onClosedEvent));
	    }
	  }, {
	    key: "onChangedEventSubscribe",
	    value: function onChangedEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onChangedEvent), listener);
	    }
	  }, {
	    key: "onMouseOverSubscribe",
	    value: function onMouseOverSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onMouseOverEvent), listener);
	    }
	  }, {
	    key: "onMouseOutSubscribe",
	    value: function onMouseOutSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onMouseOutEvent), listener);
	    }
	  }, {
	    key: "subscribeOnShowedEvent",
	    value: function subscribeOnShowedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onShowedEvent), listener);
	    }
	  }, {
	    key: "subscribeOnClosedEvent",
	    value: function subscribeOnClosedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onClosedEvent), listener);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.classPrivateFieldSet(this, _map, null);
	      babelHelpers.classPrivateFieldSet(this, _gallery, null);
	      babelHelpers.classPrivateFieldSet(this, _addressString$1, null);
	      babelHelpers.classPrivateFieldGet(this, _popup).destroy();
	      babelHelpers.classPrivateFieldSet(this, _popup, null);
	      main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _contentWrapper));
	      babelHelpers.classPrivateFieldSet(this, _contentWrapper, null);
	      main_core.Event.unbindAll(this);
	    }
	  }, {
	    key: "bindElement",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _popup).getBindElement();
	    },
	    set: function set(bindElement) {
	      if (main_core.Type.isDomNode(bindElement)) {
	        babelHelpers.classPrivateFieldGet(this, _popup).setBindElement(bindElement);
	      } else {
	        BX.debug('bindElement must be type of dom node');
	      }
	    }
	  }, {
	    key: "address",
	    set: function set(address) {
	      var _this3 = this;

	      babelHelpers.classPrivateFieldSet(this, _address$3, address);
	      babelHelpers.classPrivateFieldGet(this, _addressString$1).address = address;

	      _classPrivateMethodGet$4(this, _convertAddressToLocation, _convertAddressToLocation2).call(this, address).then(function (location) {
	        _classPrivateMethodGet$4(_this3, _setLocationInternal, _setLocationInternal2).call(_this3, location);
	      });
	    }
	  }, {
	    key: "mode",
	    set: function set(mode) {
	      babelHelpers.classPrivateFieldSet(this, _mode$1, mode);
	      babelHelpers.classPrivateFieldGet(this, _map).mode = mode;
	    }
	  }]);
	  return MapPopup;
	}(main_core_events.EventEmitter);

	var _onChangedEvent = {
	  writable: true,
	  value: 'onChanged'
	};
	var _onMouseOverEvent = {
	  writable: true,
	  value: 'onMouseOver'
	};
	var _onMouseOutEvent = {
	  writable: true,
	  value: 'onMouseOut'
	};
	var _onShowedEvent = {
	  writable: true,
	  value: 'onShow'
	};
	var _onClosedEvent = {
	  writable: true,
	  value: 'onClose'
	};

	var _renderPopup2 = function _renderPopup2(bindElement, mapInnerContainer) {
	  var _this4 = this;

	  var gallery = '';

	  if (babelHelpers.classPrivateFieldGet(this, _gallery)) {
	    gallery = babelHelpers.classPrivateFieldGet(this, _gallery).render();
	  }

	  babelHelpers.classPrivateFieldSet(this, _contentWrapper, main_core.Tag.render(_templateObject2$1(), mapInnerContainer, gallery, babelHelpers.classPrivateFieldGet(this, _mode$1) === location_core.ControlMode.edit ? babelHelpers.classPrivateFieldGet(this, _addressString$1).render({
	    address: babelHelpers.classPrivateFieldGet(this, _address$3)
	  }) : ''));
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _contentWrapper), 'click', function (e) {
	    return e.stopPropagation();
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _contentWrapper), 'mouseover', function (e) {
	    return _this4.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onMouseOverEvent), e);
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _contentWrapper), 'mouseout', function (e) {
	    return _this4.emit(_classStaticPrivateFieldSpecGet$1(MapPopup, MapPopup, _onMouseOutEvent), e);
	  });
	  this.bindElement = bindElement;
	  babelHelpers.classPrivateFieldGet(this, _popup).setContent(babelHelpers.classPrivateFieldGet(this, _contentWrapper));
	};

	var _convertAddressToLocation2 = function _convertAddressToLocation2(address) {
	  var _this5 = this;

	  return new Promise(function (resolve) {
	    var location = address ? address.toLocation() : null;

	    if (location) {
	      if (!location.latitude && !location.longitude && address.latitude && address.longitude) {
	        location.latitude = address.latitude;
	        location.longitude = address.longitude;
	      }

	      if (location.latitude && location.longitude) {
	        resolve(location);
	        return;
	      }
	    }

	    if (babelHelpers.classPrivateFieldGet(_this5, _geocodingService)) {
	      var addressStr = null;

	      if (address) {
	        addressStr = address.toString(babelHelpers.classPrivateFieldGet(_this5, _addressFormat$3), location_core.AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE, location_core.AddressStringConverter.CONTENT_TYPE_TEXT);
	      }

	      babelHelpers.classPrivateFieldGet(_this5, _geocodingService).geocode(addressStr).then(function (locationsList) {
	        var location = locationsList.length === 1 ? locationsList[0] : null;
	        resolve(location);
	      });
	    }
	  });
	};

	var _setLocationInternal2 = function _setLocationInternal2(location) {
	  babelHelpers.classPrivateFieldGet(this, _map).location = location;

	  if (babelHelpers.classPrivateFieldGet(this, _gallery)) {
	    babelHelpers.classPrivateFieldGet(this, _gallery).location = location;
	  }
	};

	var _renderMap2 = function _renderMap2(_ref) {
	  var location = _ref.location;
	  return babelHelpers.classPrivateFieldGet(this, _map).render({
	    mapContainer: babelHelpers.classPrivateFieldGet(this, _mapInnerContainer),
	    location: location,
	    mode: babelHelpers.classPrivateFieldGet(this, _mode$1)
	  });
	};

	function _templateObject2$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"location-map-photo-item-block\">\n\t\t\t\t<span class=\"location-map-photo-item-block-image-block-inner\">\n\t\t\t\t\t", "\n\t\t\t\t\t<span \n\t\t\t\t\t\tdata-viewer data-viewer-type=\"image\" \n\t\t\t\t\t\tdata-src=\"", "\" \n\t\t\t\t\t\tdata-title=\"", "\"\n\t\t\t\t\t\tclass=\"location-map-item-photo-image\" \n\t\t\t\t\t\tdata-viewer-group-by=\"", "\"\n\t\t\t\t\t\tstyle=\"background-image: url(", ");\">\t\t\t\t\t\t\t\n\t\t\t\t\t</span>\n\t\t\t\t</span>\n\t\t\t</div>"]);

	  _templateObject2$2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"location-map-item-description\">", "</span>"]);

	  _templateObject$2 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var _description = new WeakMap();

	var _url = new WeakMap();

	var _link = new WeakMap();

	var _location = new WeakMap();

	var _title = new WeakMap();

	var Photo = /*#__PURE__*/function () {
	  function Photo(props) {
	    babelHelpers.classCallCheck(this, Photo);

	    _description.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _url.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _link.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _location.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _title.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _url, props.url);
	    babelHelpers.classPrivateFieldSet(this, _link, props.link || '');
	    babelHelpers.classPrivateFieldSet(this, _description, props.description || '');
	    babelHelpers.classPrivateFieldSet(this, _location, props.location);
	    babelHelpers.classPrivateFieldSet(this, _title, props.title || '');
	  }

	  babelHelpers.createClass(Photo, [{
	    key: "render",
	    value: function render() {
	      var description = '';

	      if (babelHelpers.classPrivateFieldGet(this, _description)) {
	        //todo: sanitize
	        description = main_core.Tag.render(_templateObject$2(), babelHelpers.classPrivateFieldGet(this, _description));
	      }

	      return main_core.Tag.render(_templateObject2$2(), description, babelHelpers.classPrivateFieldGet(this, _link), babelHelpers.classPrivateFieldGet(this, _title), babelHelpers.classPrivateFieldGet(this, _location).externalId, babelHelpers.classPrivateFieldGet(this, _url));
	    }
	  }]);
	  return Photo;
	}();

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }

	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _templateObject2$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"location-map-photo-container\">\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject2$3 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\t\t\t\t\t\n\t\t\t\t<div class=\"location-map-photo-inner\">\t\t\t\t\t\n\t\t\t\t</div>"]);

	  _templateObject$3 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet$5(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _photos = new WeakMap();

	var _container = new WeakMap();

	var _photosContainer = new WeakMap();

	var _thumbnailHeight = new WeakMap();

	var _thumbnailWidth = new WeakMap();

	var _photoService = new WeakMap();

	var _maxPhotoCount = new WeakMap();

	var _location$1 = new WeakMap();

	var _setPhotos = new WeakSet();

	var _renderPhotos = new WeakSet();

	var Gallery = /*#__PURE__*/function () {
	  function Gallery(props) {
	    babelHelpers.classCallCheck(this, Gallery);

	    _renderPhotos.add(this);

	    _setPhotos.add(this);

	    _photos.set(this, {
	      writable: true,
	      value: []
	    });

	    _container.set(this, {
	      writable: true,
	      value: null
	    });

	    _photosContainer.set(this, {
	      writable: true,
	      value: null
	    });

	    _thumbnailHeight.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _thumbnailWidth.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _photoService.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _maxPhotoCount.set(this, {
	      writable: true,
	      value: void 0
	    });

	    _location$1.set(this, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _thumbnailHeight, props.thumbnailHeight);
	    babelHelpers.classPrivateFieldSet(this, _thumbnailWidth, props.thumbnailWidth);
	    babelHelpers.classPrivateFieldSet(this, _maxPhotoCount, props.maxPhotoCount);
	    babelHelpers.classPrivateFieldSet(this, _photoService, props.photoService);
	  }

	  babelHelpers.createClass(Gallery, [{
	    key: "refresh",
	    value: function refresh() {
	      var _this = this;

	      if (babelHelpers.classPrivateFieldGet(this, _location$1)) {
	        babelHelpers.classPrivateFieldGet(this, _photoService).requestPhotos({
	          location: babelHelpers.classPrivateFieldGet(this, _location$1),
	          thumbnailHeight: babelHelpers.classPrivateFieldGet(this, _thumbnailHeight),
	          thumbnailWidth: babelHelpers.classPrivateFieldGet(this, _thumbnailWidth),
	          maxPhotoCount: babelHelpers.classPrivateFieldGet(this, _maxPhotoCount)
	        }).then(function (photosData) {
	          if (Array.isArray(photosData) && photosData.length > 0) {
	            _classPrivateMethodGet$5(_this, _setPhotos, _setPhotos2).call(_this, photosData);

	            _this.show();
	          } else {
	            _this.hide();
	          }
	        });
	      } else {
	        this.hide();
	      }
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldGet(this, _container).style.display = 'none';
	      }
	    }
	  }, {
	    key: "isHidden",
	    value: function isHidden() {
	      return !babelHelpers.classPrivateFieldGet(this, _container) || babelHelpers.classPrivateFieldGet(this, _container).clientWidth <= 0;
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      if (babelHelpers.classPrivateFieldGet(this, _container)) {
	        babelHelpers.classPrivateFieldGet(this, _container).style.display = 'block';
	      }
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      babelHelpers.classPrivateFieldSet(this, _photosContainer, main_core.Tag.render(_templateObject$3()));
	      babelHelpers.classPrivateFieldSet(this, _container, main_core.Tag.render(_templateObject2$3(), babelHelpers.classPrivateFieldGet(this, _photosContainer)));
	      return babelHelpers.classPrivateFieldGet(this, _container);
	    }
	  }, {
	    key: "location",
	    set: function set(location) {
	      babelHelpers.classPrivateFieldSet(this, _location$1, location);
	      this.refresh();
	    }
	  }]);
	  return Gallery;
	}();

	var _setPhotos2 = function _setPhotos2(photosData) {
	  if (!babelHelpers.classPrivateFieldGet(this, _location$1)) {
	    return;
	  }

	  var photos = [];

	  var _iterator = _createForOfIteratorHelper$2(photosData),
	      _step;

	  try {
	    for (_iterator.s(); !(_step = _iterator.n()).done;) {
	      var _photo2 = _step.value;
	      photos.push(new Photo({
	        url: _photo2.thumbnail.url,
	        link: _photo2.url,
	        location: babelHelpers.classPrivateFieldGet(this, _location$1),
	        title: babelHelpers.classPrivateFieldGet(this, _location$1).name + " ( " + BX.util.strip_tags(_photo2.description) + ' )'
	      }));
	    }
	  } catch (err) {
	    _iterator.e(err);
	  } finally {
	    _iterator.f();
	  }

	  if (!Array.isArray(photos)) {
	    BX.debug('Wrong type of photos. Must be array');
	    return;
	  }

	  babelHelpers.classPrivateFieldSet(this, _photos, []);

	  for (var _i = 0, _photos2 = photos; _i < _photos2.length; _i++) {
	    var photo = _photos2[_i];
	    babelHelpers.classPrivateFieldGet(this, _photos).push(photo);
	  }

	  if (babelHelpers.classPrivateFieldGet(this, _photos).length > 0 && babelHelpers.classPrivateFieldGet(this, _photosContainer)) {
	    var renderedPhotos = babelHelpers.classPrivateFieldGet(this, _photos) ? _classPrivateMethodGet$5(this, _renderPhotos, _renderPhotos2).call(this, babelHelpers.classPrivateFieldGet(this, _photos)) : '';
	    babelHelpers.classPrivateFieldGet(this, _photosContainer).innerHTML = '';

	    if (renderedPhotos.length > 0) {
	      var _iterator2 = _createForOfIteratorHelper$2(renderedPhotos),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var _photo = _step2.value;
	          babelHelpers.classPrivateFieldGet(this, _photosContainer).appendChild(_photo);
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }
	    }
	  }
	};

	var _renderPhotos2 = function _renderPhotos2(photos) {
	  var result = [];

	  var _iterator3 = _createForOfIteratorHelper$2(photos),
	      _step3;

	  try {
	    for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	      var photo = _step3.value;
	      result.push(photo.render());
	    }
	  } catch (err) {
	    _iterator3.e(err);
	  } finally {
	    _iterator3.f();
	  }

	  return result;
	};

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-title-6\">\n\t\t\t\t", "\n\t\t\t</div>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"ui-entity-editor-content-block\">\n\t\t\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-w100\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<input type=\"text\" class=\"ui-ctl-element\" value=\"", "\">"]);

	  _templateObject2$4 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"ui-entity-editor-content-block ui-entity-editor-field-text\">\n\t\t\t\t<div class=\"ui-entity-editor-block-title\">\n\t\t\t\t\t<label class=\"ui-entity-editor-block-title-text\">", ":</label>\t\t\t\t\n\t\t\t\t</div>\n\t\t\t</div>"]);

	  _templateObject$4 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classPrivateMethodGet$6(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	function _classStaticPrivateFieldSpecGet$2(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	var _title$1 = new WeakMap();

	var _value = new WeakMap();

	var _type = new WeakMap();

	var _sort = new WeakMap();

	var _mode$2 = new WeakMap();

	var _input = new WeakMap();

	var _viewContainer = new WeakMap();

	var _container$1 = new WeakMap();

	var _state$2 = new WeakMap();

	var _setState$1 = new WeakSet();

	var _renderEditMode = new WeakSet();

	var _renderViewMode = new WeakSet();

	var _refreshLayout = new WeakSet();

	var Field = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Field, _EventEmitter);

	  function Field(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Field);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Field).call(this, props));

	    _refreshLayout.add(babelHelpers.assertThisInitialized(_this));

	    _renderViewMode.add(babelHelpers.assertThisInitialized(_this));

	    _renderEditMode.add(babelHelpers.assertThisInitialized(_this));

	    _setState$1.add(babelHelpers.assertThisInitialized(_this));

	    _title$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _value.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _type.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _sort.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _mode$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _input.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _viewContainer.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _container$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _state$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: State.INITIAL
	    });

	    _this.setEventNamespace('BX.Location.Widget.Field');

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _title$1, props.title);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _type, props.type);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _sort, props.sort);
	    return _this;
	  }

	  babelHelpers.createClass(Field, [{
	    key: "render",
	    value: function render(props) {
	      babelHelpers.classPrivateFieldSet(this, _value, typeof props.value === 'string' ? props.value : '');

	      if (!location_core.ControlMode.isValid(props.mode)) {
	        BX.debug('props.mode must be valid ControlMode');
	      }

	      babelHelpers.classPrivateFieldSet(this, _mode$2, props.mode);
	      babelHelpers.classPrivateFieldSet(this, _container$1, main_core.Tag.render(_templateObject$4(), babelHelpers.classPrivateFieldGet(this, _title$1)));

	      if (babelHelpers.classPrivateFieldGet(this, _mode$2) === location_core.ControlMode.edit) {
	        _classPrivateMethodGet$6(this, _renderEditMode, _renderEditMode2).call(this, babelHelpers.classPrivateFieldGet(this, _container$1));
	      } else {
	        _classPrivateMethodGet$6(this, _renderViewMode, _renderViewMode2).call(this, babelHelpers.classPrivateFieldGet(this, _container$1));
	      }

	      return babelHelpers.classPrivateFieldGet(this, _container$1);
	    }
	  }, {
	    key: "subscribeOnValueChangedEvent",
	    value: function subscribeOnValueChangedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$2(Field, Field, _onValueChangedEvent), listener);
	    }
	  }, {
	    key: "subscribeOnStateChangedEvent",
	    value: function subscribeOnStateChangedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$2(Field, Field, _onStateChangedEvent$1), listener);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Dom.remove(babelHelpers.classPrivateFieldGet(this, _container$1));
	      main_core.Event.unbindAll(this);
	      babelHelpers.classPrivateFieldSet(this, _container$1, null);
	    }
	  }, {
	    key: "container",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _container$1);
	    }
	  }, {
	    key: "state",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _state$2);
	    }
	  }, {
	    key: "type",
	    set: function set(type) {
	      babelHelpers.classPrivateFieldSet(this, _type, type);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _type);
	    }
	  }, {
	    key: "sort",
	    set: function set(sort) {
	      babelHelpers.classPrivateFieldSet(this, _sort, sort);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _sort);
	    }
	  }, {
	    key: "value",
	    set: function set(value) {
	      babelHelpers.classPrivateFieldSet(this, _value, typeof value === 'string' ? value : '');

	      _classPrivateMethodGet$6(this, _refreshLayout, _refreshLayout2).call(this);
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _value);
	    }
	  }]);
	  return Field;
	}(main_core_events.EventEmitter);

	var _onValueChangedEvent = {
	  writable: true,
	  value: 'onValueChanged'
	};
	var _onStateChangedEvent$1 = {
	  writable: true,
	  value: 'onStateChanged'
	};

	var _setState2$1 = function _setState2(state) {
	  babelHelpers.classPrivateFieldSet(this, _state$2, state);
	  this.emit(_classStaticPrivateFieldSpecGet$2(Field, Field, _onStateChangedEvent$1), {
	    state: babelHelpers.classPrivateFieldGet(this, _state$2)
	  });
	};

	var _renderEditMode2 = function _renderEditMode2(container) {
	  var _this2 = this;

	  babelHelpers.classPrivateFieldSet(this, _input, main_core.Tag.render(_templateObject2$4(), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _value))));
	  babelHelpers.classPrivateFieldSet(this, _viewContainer, null);
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _input), 'focus', function (e) {
	    _classPrivateMethodGet$6(_this2, _setState$1, _setState2$1).call(_this2, State.DATA_INPUTTING);
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _input), 'focusout', function (e) {
	    _classPrivateMethodGet$6(_this2, _setState$1, _setState2$1).call(_this2, State.DATA_SELECTED);
	  });
	  main_core.Event.bind(babelHelpers.classPrivateFieldGet(this, _input), 'change', function (e) {
	    _classPrivateMethodGet$6(_this2, _setState$1, _setState2$1).call(_this2, State.DATA_SELECTED);

	    babelHelpers.classPrivateFieldSet(_this2, _value, babelHelpers.classPrivateFieldGet(_this2, _input).value);

	    _this2.emit(_classStaticPrivateFieldSpecGet$2(Field, Field, _onValueChangedEvent), {
	      value: _this2
	    });
	  });
	  container.appendChild(main_core.Tag.render(_templateObject3(), babelHelpers.classPrivateFieldGet(this, _input)));
	};

	var _renderViewMode2 = function _renderViewMode2(container) {
	  babelHelpers.classPrivateFieldSet(this, _input, null);
	  babelHelpers.classPrivateFieldSet(this, _viewContainer, main_core.Tag.render(_templateObject4(), main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _value))));
	  container.appendChild(babelHelpers.classPrivateFieldGet(this, _viewContainer));
	};

	var _refreshLayout2 = function _refreshLayout2() {
	  if (babelHelpers.classPrivateFieldGet(this, _mode$2) === location_core.ControlMode.edit) {
	    babelHelpers.classPrivateFieldGet(this, _input).value = babelHelpers.classPrivateFieldGet(this, _value);
	  } else {
	    babelHelpers.classPrivateFieldGet(this, _viewContainer).innerHTML = main_core.Text.encode(babelHelpers.classPrivateFieldGet(this, _value));
	  }
	};

	function _createForOfIteratorHelper$3(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$3(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$3(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$3(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$3(o, minLen); }

	function _arrayLikeToArray$3(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

	function _classStaticPrivateFieldSpecGet$3(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	function _classPrivateMethodGet$7(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _address$4 = new WeakMap();

	var _addressFormat$4 = new WeakMap();

	var _mode$3 = new WeakMap();

	var _fields = new WeakMap();

	var _languageId$2 = new WeakMap();

	var _container$2 = new WeakMap();

	var _state$3 = new WeakMap();

	var _initFields = new WeakSet();

	var _onFieldChanged = new WeakSet();

	var _setState$2 = new WeakSet();

	var Fields = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Fields, _EventEmitter);

	  function Fields(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Fields);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Fields).call(this, props));

	    _setState$2.add(babelHelpers.assertThisInitialized(_this));

	    _onFieldChanged.add(babelHelpers.assertThisInitialized(_this));

	    _initFields.add(babelHelpers.assertThisInitialized(_this));

	    _address$4.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressFormat$4.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _mode$3.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _fields.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: []
	    });

	    _languageId$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _container$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _state$3.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _this.setEventNamespace('BX.Location.Widget.Fields');

	    if (!(props.addressFormat instanceof location_core.Format)) {
	      BX.debug('addressFormat must be instance of Format');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _addressFormat$4, props.addressFormat);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _languageId$2, props.languageId);

	    _classPrivateMethodGet$7(babelHelpers.assertThisInitialized(_this), _initFields, _initFields2).call(babelHelpers.assertThisInitialized(_this));

	    return _this;
	  }

	  babelHelpers.createClass(Fields, [{
	    key: "render",
	    value: function render(props) {
	      if (props.address && !(props.address instanceof location_core.Address)) {
	        BX.debug('props.address must be instance of Address');
	      }

	      babelHelpers.classPrivateFieldSet(this, _address$4, props.address);

	      if (!location_core.ControlMode.isValid(props.mode)) {
	        BX.debug('props.mode must be valid ControlMode');
	      }

	      babelHelpers.classPrivateFieldSet(this, _mode$3, props.mode);

	      if (!main_core.Type.isDomNode(props.container)) {
	        BX.debug('props.container must be dom node');
	      }

	      babelHelpers.classPrivateFieldSet(this, _container$2, props.container);

	      var _iterator = _createForOfIteratorHelper$3(babelHelpers.classPrivateFieldGet(this, _fields)),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var field = _step.value;
	          var value = babelHelpers.classPrivateFieldGet(this, _address$4) ? babelHelpers.classPrivateFieldGet(this, _address$4).getFieldValue(field.type) : '';

	          if (babelHelpers.classPrivateFieldGet(this, _mode$3) === location_core.ControlMode.view && !value) {
	            continue;
	          }

	          var item = field.render({
	            value: value,
	            mode: babelHelpers.classPrivateFieldGet(this, _mode$3)
	          });
	          babelHelpers.classPrivateFieldGet(this, _container$2).appendChild(item);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	  }, {
	    key: "subscribeOnAddressChangedEvent",
	    value: function subscribeOnAddressChangedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$3(Fields, Fields, _onAddressChangedEvent$1), listener);
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Event.unbindAll(this);

	      var _iterator2 = _createForOfIteratorHelper$3(babelHelpers.classPrivateFieldGet(this, _fields)),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var field = _step2.value;
	          field.destroy();
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      main_core.Dom.clean(babelHelpers.classPrivateFieldGet(this, _container$2));
	    }
	  }, {
	    key: "subscribeOnStateChangedEvent",
	    value: function subscribeOnStateChangedEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$3(Fields, Fields, _onStateChangedEvent$2), listener);
	    }
	  }, {
	    key: "address",
	    set: function set(address) {
	      if (address && !(address instanceof location_core.Address)) {
	        BX.debug('address must be instance of Address');
	      }

	      babelHelpers.classPrivateFieldSet(this, _address$4, address);

	      var _iterator3 = _createForOfIteratorHelper$3(babelHelpers.classPrivateFieldGet(this, _fields)),
	          _step3;

	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var field = _step3.value;
	          field.value = babelHelpers.classPrivateFieldGet(this, _address$4) ? babelHelpers.classPrivateFieldGet(this, _address$4).getFieldValue(field.type) : '';
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }
	    }
	  }, {
	    key: "state",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _state$3);
	    }
	  }]);
	  return Fields;
	}(main_core_events.EventEmitter);

	var _onAddressChangedEvent$1 = {
	  writable: true,
	  value: 'onAddressChanged'
	};
	var _onStateChangedEvent$2 = {
	  writable: true,
	  value: 'onStateChanged'
	};

	var _initFields2 = function _initFields2() {
	  var _this2 = this;

	  var _loop = function _loop(type) {
	    if (!babelHelpers.classPrivateFieldGet(_this2, _addressFormat$4).fieldCollection.fields.hasOwnProperty(type)) {
	      return "continue";
	    }

	    var formatField = babelHelpers.classPrivateFieldGet(_this2, _addressFormat$4).fieldCollection.fields[type];
	    var field = new Field({
	      title: formatField.name,
	      type: formatField.type,
	      sort: formatField.sort
	    });
	    field.subscribeOnValueChangedEvent(function (event) {
	      _classPrivateMethodGet$7(_this2, _onFieldChanged, _onFieldChanged2).call(_this2, field);
	    });
	    field.subscribeOnStateChangedEvent(function (event) {
	      var data = event.getData();

	      _classPrivateMethodGet$7(_this2, _setState$2, _setState2$2).call(_this2, data.state);
	    });
	    babelHelpers.classPrivateFieldGet(_this2, _fields).push(field);
	  };

	  for (var type in babelHelpers.classPrivateFieldGet(this, _addressFormat$4).fieldCollection.fields) {
	    var _ret = _loop(type);

	    if (_ret === "continue") continue;
	  }

	  babelHelpers.classPrivateFieldGet(this, _fields).sort(function (a, b) {
	    return a.sort - b.sort;
	  });
	};

	var _onFieldChanged2 = function _onFieldChanged2(field) {
	  if (!babelHelpers.classPrivateFieldGet(this, _address$4)) {
	    babelHelpers.classPrivateFieldSet(this, _address$4, new location_core.Address({
	      languageId: babelHelpers.classPrivateFieldGet(this, _languageId$2)
	    }));
	  }

	  babelHelpers.classPrivateFieldGet(this, _address$4).setFieldValue(field.type, field.value);

	  if (field.type !== babelHelpers.classPrivateFieldGet(this, _addressFormat$4).fieldForUnRecognized) {
	    babelHelpers.classPrivateFieldGet(this, _address$4).location = null;
	    babelHelpers.classPrivateFieldGet(this, _address$4).latitude = '';
	    babelHelpers.classPrivateFieldGet(this, _address$4).longitude = '';
	  }

	  this.emit(_classStaticPrivateFieldSpecGet$3(Fields, Fields, _onAddressChangedEvent$1), {
	    address: babelHelpers.classPrivateFieldGet(this, _address$4),
	    changedField: field
	  });
	};

	var _setState2$2 = function _setState2(state) {
	  babelHelpers.classPrivateFieldSet(this, _state$3, state);
	  this.emit(_classStaticPrivateFieldSpecGet$3(Fields, Fields, _onStateChangedEvent$2), {
	    state: babelHelpers.classPrivateFieldGet(this, _state$3)
	  });
	};

	/**
	 * Complex address widget
	 */

	var _map$1 = new WeakMap();

	var _mapBindElement = new WeakMap();

	var _addressWidget$1 = new WeakMap();

	var MapFeature = /*#__PURE__*/function (_BaseFeature) {
	  babelHelpers.inherits(MapFeature, _BaseFeature);

	  function MapFeature(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, MapFeature);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MapFeature).call(this));

	    _map$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _mapBindElement.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _addressWidget$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    if (!(props.map instanceof MapPopup)) {
	      BX.debug('props.map must be instance of MapPopup');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _map$1, props.map);
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _map$1).onChangedEventSubscribe(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget$1).setAddressByFeature(data.address, babelHelpers.assertThisInitialized(_this));
	    });
	    return _this;
	  }

	  babelHelpers.createClass(MapFeature, [{
	    key: "showMap",
	    value: function showMap() {
	      if (!babelHelpers.classPrivateFieldGet(this, _map$1).isShown()) {
	        babelHelpers.classPrivateFieldGet(this, _map$1).show();
	      }
	    }
	  }, {
	    key: "closeMap",
	    value: function closeMap() {
	      if (babelHelpers.classPrivateFieldGet(this, _map$1).isShown()) {
	        babelHelpers.classPrivateFieldGet(this, _map$1).close();
	      }

	      babelHelpers.classPrivateFieldGet(this, _map$1).bindelement = babelHelpers.classPrivateFieldGet(this, _mapBindElement);
	    }
	  }, {
	    key: "resetView",
	    value: function resetView() {
	      this.closeMap();
	    }
	    /**
	     * Render Widget
	     * @param {Object} props
	     */

	  }, {
	    key: "render",
	    value: function render(props) {
	      if (!main_core.Type.isDomNode(props.mapBindElement)) {
	        BX.debug('props.mapBindElement  must be instance of Element');
	      }

	      babelHelpers.classPrivateFieldSet(this, _mapBindElement, props.mapBindElement);
	      babelHelpers.classPrivateFieldGet(this, _map$1).render({
	        bindElement: props.mapBindElement,
	        address: babelHelpers.classPrivateFieldGet(this, _addressWidget$1).address,
	        mode: babelHelpers.classPrivateFieldGet(this, _addressWidget$1).mode
	      });
	    }
	  }, {
	    key: "setAddress",
	    value: function setAddress(address) {
	      if (this.addressWidget.state === State.DATA_INPUTTING) {
	        return;
	      }

	      babelHelpers.classPrivateFieldGet(this, _map$1).address = address;
	    }
	  }, {
	    key: "setAddressWidget",
	    value: function setAddressWidget(addressWidget) {
	      babelHelpers.classPrivateFieldSet(this, _addressWidget$1, addressWidget);
	    }
	  }, {
	    key: "setMode",
	    value: function setMode(mode) {
	      babelHelpers.classPrivateFieldGet(this, _map$1).mode = mode;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.classPrivateFieldGet(this, _map$1).destroy();
	      babelHelpers.classPrivateFieldSet(this, _map$1, null);
	    }
	  }, {
	    key: "map",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _map$1);
	    }
	  }, {
	    key: "addressWidget",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _addressWidget$1);
	    }
	  }, {
	    key: "mapBindElement",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _mapBindElement);
	    }
	  }]);
	  return MapFeature;
	}(BaseFeature);

	/**
	 * Fields widget feature
	 */

	var _fields$1 = new WeakMap();

	var _addressWidget$2 = new WeakMap();

	var FieldsFeature = /*#__PURE__*/function (_BaseFeature) {
	  babelHelpers.inherits(FieldsFeature, _BaseFeature);

	  function FieldsFeature(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, FieldsFeature);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FieldsFeature).call(this, props));

	    _fields$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _addressWidget$2.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    if (!(props.fields instanceof Fields)) {
	      BX.debug('props.Fields must be instance of Fields');
	    }

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _fields$1, props.fields);
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _fields$1).subscribeOnAddressChangedEvent(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget$2).setAddressByFeature(data.address, babelHelpers.assertThisInitialized(_this));
	    });
	    babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _fields$1).subscribeOnStateChangedEvent(function (event) {
	      var data = event.getData();
	      babelHelpers.classPrivateFieldGet(babelHelpers.assertThisInitialized(_this), _addressWidget$2).setStateByFeature(data.state);
	    });
	    return _this;
	  }

	  babelHelpers.createClass(FieldsFeature, [{
	    key: "render",
	    value: function render(props) {
	      if (babelHelpers.classPrivateFieldGet(this, _addressWidget$2).mode === location_core.ControlMode.edit) {
	        if (!main_core.Type.isDomNode(props.fieldsContainer)) {
	          BX.debug('props.fieldsContainer  must be instance of Element');
	        }

	        babelHelpers.classPrivateFieldGet(this, _fields$1).render({
	          address: babelHelpers.classPrivateFieldGet(this, _addressWidget$2).address,
	          mode: babelHelpers.classPrivateFieldGet(this, _addressWidget$2).mode,
	          container: props.fieldsContainer
	        });
	      }
	    }
	  }, {
	    key: "setAddressWidget",
	    value: function setAddressWidget(addressWidget) {
	      babelHelpers.classPrivateFieldSet(this, _addressWidget$2, addressWidget);
	    }
	  }, {
	    key: "setAddress",
	    value: function setAddress(address) {
	      babelHelpers.classPrivateFieldGet(this, _fields$1).address = address;
	    }
	  }, {
	    key: "setMode",
	    value: function setMode(mode) {
	      babelHelpers.classPrivateFieldGet(this, _fields$1).mode = mode;
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      babelHelpers.classPrivateFieldGet(this, _fields$1).destroy();
	      babelHelpers.classPrivateFieldSet(this, _fields$1, null);
	    }
	  }]);
	  return FieldsFeature;
	}(BaseFeature);

	function _classPrivateMethodGet$8(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	/**
	 * Map feature for the address widget with auto map opening / closing behavior
	 */

	var _isMouseOver = new WeakMap();

	var _showMapTimerId = new WeakMap();

	var _showMapDelay = new WeakMap();

	var _closeMapTimerId = new WeakMap();

	var _closeMapDelay = new WeakMap();

	var _isDestroyed$1 = new WeakMap();

	var _onInputFocus$2 = new WeakSet();

	var _onControlWrapperClick = new WeakSet();

	var _onDocumentClick$1 = new WeakSet();

	var _processOnMouseOver = new WeakSet();

	var _processOnMouseOut = new WeakSet();

	var MapFeatureAuto = /*#__PURE__*/function (_MapFeature) {
	  babelHelpers.inherits(MapFeatureAuto, _MapFeature);

	  function MapFeatureAuto() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, MapFeatureAuto);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(MapFeatureAuto)).call.apply(_babelHelpers$getProt, [this].concat(args)));

	    _processOnMouseOut.add(babelHelpers.assertThisInitialized(_this));

	    _processOnMouseOver.add(babelHelpers.assertThisInitialized(_this));

	    _onDocumentClick$1.add(babelHelpers.assertThisInitialized(_this));

	    _onControlWrapperClick.add(babelHelpers.assertThisInitialized(_this));

	    _onInputFocus$2.add(babelHelpers.assertThisInitialized(_this));

	    _isMouseOver.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    _showMapTimerId.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _showMapDelay.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: 700
	    });

	    _closeMapTimerId.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: null
	    });

	    _closeMapDelay.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: 800
	    });

	    _isDestroyed$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: false
	    });

	    return _this;
	  }

	  babelHelpers.createClass(MapFeatureAuto, [{
	    key: "render",

	    /**
	     * Render Widget
	     * @param {AddressRenderProps} props
	     */
	    value: function render(props) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(MapFeatureAuto.prototype), "render", this).call(this, props);
	      this.addressWidget.controlWrapper.addEventListener('click', _classPrivateMethodGet$8(this, _onControlWrapperClick, _onControlWrapperClick2).bind(this));
	      this.addressWidget.controlWrapper.addEventListener('mouseover', _classPrivateMethodGet$8(this, _processOnMouseOver, _processOnMouseOver2).bind(this));
	      this.addressWidget.controlWrapper.addEventListener('mouseout', _classPrivateMethodGet$8(this, _processOnMouseOut, _processOnMouseOut2).bind(this));
	      document.addEventListener('click', _classPrivateMethodGet$8(this, _onDocumentClick$1, _onDocumentClick2$1).bind(this));
	      this.map.onMouseOverSubscribe(_classPrivateMethodGet$8(this, _processOnMouseOver, _processOnMouseOver2).bind(this));
	      this.map.onMouseOutSubscribe(_classPrivateMethodGet$8(this, _processOnMouseOut, _processOnMouseOut2).bind(this));
	    }
	  }, {
	    key: "setAddress",
	    value: function setAddress(address) {
	      if (this.addressWidget.state === State.DATA_INPUTTING) {
	        this.closeMap();
	        return;
	      }

	      if (!address) {
	        this.closeMap();
	      }

	      this.map.address = address;

	      if (address) {
	        this.showMap();
	      }
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      if (babelHelpers.classPrivateFieldGet(this, _isDestroyed$1)) {
	        return;
	      }

	      document.removeEventListener('click', _classPrivateMethodGet$8(this, _onDocumentClick$1, _onDocumentClick2$1));

	      if (this.addressWidget.controlWrapper) {
	        this.addressWidget.controlWrapper.removeEventListener('click', _classPrivateMethodGet$8(this, _onControlWrapperClick, _onControlWrapperClick2));
	        this.addressWidget.controlWrapper.removeEventListener('mouseover', _classPrivateMethodGet$8(this, _processOnMouseOver, _processOnMouseOver2));
	        this.addressWidget.controlWrapper.removeEventListener('mouseout', _classPrivateMethodGet$8(this, _processOnMouseOut, _processOnMouseOut2));
	      }

	      babelHelpers.classPrivateFieldSet(this, _showMapTimerId, null);
	      babelHelpers.classPrivateFieldSet(this, _closeMapTimerId, null);
	      babelHelpers.classPrivateFieldSet(this, _isMouseOver, false);
	      babelHelpers.get(babelHelpers.getPrototypeOf(MapFeatureAuto.prototype), "destroy", this).call(this);
	      babelHelpers.classPrivateFieldSet(this, _isDestroyed$1, true);
	    }
	  }]);
	  return MapFeatureAuto;
	}(MapFeature);

	var _onControlWrapperClick2 = function _onControlWrapperClick2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed$1)) {
	    return;
	  }

	  if (this.map.isShown()) {
	    this.closeMap();
	  } else {
	    clearTimeout(babelHelpers.classPrivateFieldGet(this, _showMapTimerId));
	  }
	};

	var _onDocumentClick2$1 = function _onDocumentClick2() {
	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed$1)) {
	    return;
	  }

	  this.closeMap();
	};

	var _processOnMouseOver2 = function _processOnMouseOver2() {
	  var _this2 = this;

	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed$1)) {
	    return;
	  }

	  babelHelpers.classPrivateFieldSet(this, _isMouseOver, true);
	  clearTimeout(babelHelpers.classPrivateFieldGet(this, _showMapTimerId));
	  clearTimeout(babelHelpers.classPrivateFieldGet(this, _closeMapTimerId));

	  if (this.addressWidget.address && !this.map.isShown()) {
	    babelHelpers.classPrivateFieldSet(this, _showMapTimerId, setTimeout(function () {
	      _this2.showMap();
	    }, babelHelpers.classPrivateFieldGet(this, _showMapDelay)));
	  }
	};

	var _processOnMouseOut2 = function _processOnMouseOut2() {
	  var _this3 = this;

	  if (babelHelpers.classPrivateFieldGet(this, _isDestroyed$1)) {
	    return;
	  }

	  babelHelpers.classPrivateFieldSet(this, _isMouseOver, false);
	  clearTimeout(babelHelpers.classPrivateFieldGet(this, _showMapTimerId));
	  clearTimeout(babelHelpers.classPrivateFieldGet(this, _closeMapTimerId));

	  if (this.addressWidget.mode === location_core.ControlMode.view && this.map.isShown()) {
	    babelHelpers.classPrivateFieldSet(this, _closeMapTimerId, setTimeout(function () {
	      _this3.closeMap();
	    }, babelHelpers.classPrivateFieldGet(this, _closeMapDelay)));
	  }
	};

	/**
	 * Props type for the main fabric method
	 */

	/**
	 * Factory class with a set of tools for the address widget creation
	 */
	var Factory = /*#__PURE__*/function () {
	  function Factory() {
	    babelHelpers.classCallCheck(this, Factory);
	  }

	  babelHelpers.createClass(Factory, [{
	    key: "createAddressWidget",

	    /**
	     * Main factory method
	     * @param {FactoryCreateAddressWidgetProps} props
	     * @returns {Address}
	     */
	    value: function createAddressWidget(props) {
	      var sourceCode = props.sourceCode || BX.message('LOCATION_WIDGET_SOURCE_CODE');
	      var sourceParams = props.sourceParams || BX.message('LOCATION_WIDGET_SOURCE_PARAMS');
	      var languageId = props.languageId || BX.message('LOCATION_WIDGET_LANGUAGE_ID');
	      var sourceLanguageId = props.sourceLanguageId || BX.message('LOCATION_WIDGET_SOURCE_LANGUAGE_ID');
	      var addressFormat = props.addressFormat || new location_core.Format(JSON.parse(BX.message('LOCATION_WIDGET_DEFAULT_FORMAT')));
	      var presetLocationList = props.presetLocationList || [];
	      var features = [];

	      if (!props.useFeatures || props.useFeatures.fields !== false) {
	        features.push(this.createFieldsFeature({
	          addressFormat: addressFormat,
	          languageId: languageId
	        }));
	      }

	      var source = null;

	      if (sourceCode && sourceParams) {
	        try {
	          source = this.createSource(sourceCode, sourceParams, languageId, sourceLanguageId);
	        } catch (e) {
	          if (e instanceof location_core.SourceCreationError) {
	            source = null;
	          } else {
	            throw e;
	          }
	        }
	      }

	      if (source) {
	        if (!props.useFeatures || props.useFeatures.autocomplete !== false) {
	          features.push(this.createAutocompleteFeature({
	            languageId: languageId,
	            addressFormat: addressFormat,
	            source: source
	          }));
	        }

	        if (!props.useFeatures || props.useFeatures.map !== false) {
	          var showPhotos = sourceParams.hasOwnProperty('showPhotos') && sourceParams.showPhotos === true;
	          var useGeocodingService = sourceParams.hasOwnProperty('useGeocodingService') && sourceParams.useGeocodingService === true;
	          var DEFAULT_THUMBNAIL_HEIGHT = 80;
	          var DEFAULT_THUMBNAIL_WIDTH = 150;
	          var DEFAULT_MAX_PHOTO_COUNT = showPhotos ? 5 : 0;
	          var DEFAULT_MAP_BEHAVIOR = 'auto';
	          features.push(this.createMapFeature({
	            addressFormat: addressFormat,
	            source: source,
	            popupBindOptions: props.popupBindOptions,
	            thumbnailHeight: props.thumbnailHeight || DEFAULT_THUMBNAIL_HEIGHT,
	            thumbnailWidth: props.thumbnailWidth || DEFAULT_THUMBNAIL_WIDTH,
	            maxPhotoCount: props.maxPhotoCount || DEFAULT_MAX_PHOTO_COUNT,
	            mapBehavior: props.mapBehavior || DEFAULT_MAP_BEHAVIOR,
	            useGeocodingService: useGeocodingService
	          }));
	        }
	      }

	      return new Address({
	        features: features,
	        address: props.address,
	        mode: props.mode,
	        addressFormat: addressFormat,
	        languageId: languageId,
	        presetLocationList: presetLocationList
	      });
	    }
	  }, {
	    key: "createFieldsFeature",
	    value: function createFieldsFeature(props) {
	      var fields = new Fields({
	        addressFormat: props.addressFormat,
	        languageId: props.languageId
	      });
	      return new FieldsFeature({
	        fields: fields
	      });
	    }
	  }, {
	    key: "createAutocompleteFeature",
	    value: function createAutocompleteFeature(props) {
	      var autocomplete = new Autocomplete({
	        languageId: props.languageId,
	        addressFormat: props.addressFormat,
	        autocompleteService: props.source.autocompleteService
	      });
	      return new AutocompleteFeature({
	        autocomplete: autocomplete
	      });
	    }
	  }, {
	    key: "createMapFeature",
	    value: function createMapFeature(props) {
	      var popup = new Popup({
	        cacheable: true,
	        closeByEsc: true,
	        className: 'location-popup-window',
	        animation: 'fading',
	        angle: true,
	        bindOptions: props.popupBindOptions
	      });
	      var gallery = null;

	      if (props.maxPhotoCount > 0) {
	        gallery = new Gallery({
	          photoService: props.source.photoService,
	          thumbnailHeight: props.thumbnailHeight,
	          thumbnailWidth: props.thumbnailWidth,
	          maxPhotoCount: props.maxPhotoCount
	        });
	      }

	      var mapFeatureProps = {
	        map: new MapPopup({
	          addressFormat: props.addressFormat,
	          map: props.source.map,
	          popup: popup,
	          gallery: gallery,
	          locationRepository: new location_core.LocationRepository(),
	          geocodingService: props.useGeocodingService ? props.source.geocodingService : null
	        })
	      };
	      var result;

	      if (props.mapBehavior === 'manual') {
	        result = new MapFeature(mapFeatureProps);
	      } else {
	        result = new MapFeatureAuto(mapFeatureProps);
	      }

	      return result;
	    } // todo: add custom sources

	  }, {
	    key: "createSource",
	    value: function createSource(code, params, languageId, sourceLanguageId) {
	      var source = null;
	      params.languageId = languageId;
	      params.sourceLanguageId = sourceLanguageId;

	      if (code === 'GOOGLE') {
	        source = new location_google.Google(params);
	      } else if (code === 'OSM') {
	        source = location_osm.OSMFactory.createOSMSource(params);
	      } else {
	        throw new RangeError('Wrong source code');
	      }

	      return source;
	    }
	  }]);
	  return Factory;
	}();

	function _templateObject$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\t\t\t\n\t\t\t<span class=\"ui-link ui-link-secondary ui-entity-editor-block-title-link\">\n\t\t\t\t", "\n\t\t\t</span>"]);

	  _templateObject$5 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classStaticPrivateFieldSpecGet$4(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	function _classPrivateMethodGet$9(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _state$4 = new WeakMap();

	var _titleContainer = new WeakMap();

	var _titles = new WeakMap();

	var _getTitle = new WeakSet();

	var Switch = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Switch, _EventEmitter);

	  function Switch() {
	    var _this;

	    var props = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, Switch);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Switch).call(this));

	    _getTitle.add(babelHelpers.assertThisInitialized(_this));

	    _state$4.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _titleContainer.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _titles.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: ['on', 'off']
	    });

	    _this.setEventNamespace('BX.Location.Widget.Switch');

	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _state$4, props.state);
	    babelHelpers.classPrivateFieldSet(babelHelpers.assertThisInitialized(_this), _titles, props.titles);
	    return _this;
	  }

	  babelHelpers.createClass(Switch, [{
	    key: "render",
	    value: function render(mode) {
	      var _this2 = this;

	      babelHelpers.classPrivateFieldSet(this, _titleContainer, main_core.Tag.render(_templateObject$5(), _classPrivateMethodGet$9(this, _getTitle, _getTitle2).call(this)));
	      babelHelpers.classPrivateFieldGet(this, _titleContainer).addEventListener('click', function (event) {
	        _this2.state = babelHelpers.classPrivateFieldGet(_this2, _state$4) === Switch.STATE_OFF ? Switch.STATE_ON : Switch.STATE_OFF;

	        _this2.emit(_classStaticPrivateFieldSpecGet$4(Switch, Switch, _onToggleEvent), {
	          state: babelHelpers.classPrivateFieldGet(_this2, _state$4)
	        });

	        event.stopPropagation();
	        return false;
	      });
	      babelHelpers.classPrivateFieldGet(this, _titleContainer).addEventListener('mouseover', function (event) {
	        event.stopPropagation();
	      });
	      return babelHelpers.classPrivateFieldGet(this, _titleContainer);
	    }
	  }, {
	    key: "subscribeOnToggleEventSubscribe",
	    value: function subscribeOnToggleEventSubscribe(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$4(Switch, Switch, _onToggleEvent), listener);
	    }
	  }, {
	    key: "state",
	    set: function set(state) {
	      babelHelpers.classPrivateFieldSet(this, _state$4, state);

	      if (babelHelpers.classPrivateFieldGet(this, _titleContainer)) {
	        babelHelpers.classPrivateFieldGet(this, _titleContainer).innerHTML = _classPrivateMethodGet$9(this, _getTitle, _getTitle2).call(this);
	      }
	    },
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _state$4);
	    }
	  }]);
	  return Switch;
	}(main_core_events.EventEmitter);

	babelHelpers.defineProperty(Switch, "STATE_OFF", 0);
	babelHelpers.defineProperty(Switch, "STATE_ON", 1);
	var _onToggleEvent = {
	  writable: true,
	  value: "onToggleEvent"
	};

	var _getTitle2 = function _getTitle2() {
	  return babelHelpers.classPrivateFieldGet(this, _titles)[babelHelpers.classPrivateFieldGet(this, _state$4)];
	};

	function _templateObject$6() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"", "\"></div>"]);

	  _templateObject$6 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classStaticPrivateFieldSpecGet$5(receiver, classConstructor, descriptor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }

	function _classPrivateMethodGet$a(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	var _type$1 = new WeakMap();

	var _domNode = new WeakMap();

	var _getClassByType = new WeakSet();

	var Icon = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Icon, _EventEmitter);

	  function Icon() {
	    var _this;

	    babelHelpers.classCallCheck(this, Icon);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Icon).call(this));

	    _getClassByType.add(babelHelpers.assertThisInitialized(_this));

	    _type$1.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: Icon.TYPE_SEARCH
	    });

	    _domNode.set(babelHelpers.assertThisInitialized(_this), {
	      writable: true,
	      value: void 0
	    });

	    _this.setEventNamespace('BX.Location.Widget.Icon');

	    return _this;
	  }

	  babelHelpers.createClass(Icon, [{
	    key: "render",
	    value: function render(props) {
	      var _this2 = this;

	      babelHelpers.classPrivateFieldSet(this, _type$1, props.type);
	      babelHelpers.classPrivateFieldSet(this, _domNode, main_core.Tag.render(_templateObject$6(), _classPrivateMethodGet$a(this, _getClassByType, _getClassByType2).call(this, babelHelpers.classPrivateFieldGet(this, _type$1))));
	      babelHelpers.classPrivateFieldGet(this, _domNode).addEventListener('click', function (e) {
	        _this2.emit(_classStaticPrivateFieldSpecGet$5(Icon, Icon, _onClickEvent));
	      });
	      return babelHelpers.classPrivateFieldGet(this, _domNode);
	    }
	  }, {
	    key: "subscribeOnClickEvent",
	    value: function subscribeOnClickEvent(listener) {
	      this.subscribe(_classStaticPrivateFieldSpecGet$5(Icon, Icon, _onClickEvent), listener);
	    }
	  }, {
	    key: "type",
	    set: function set(type) {
	      babelHelpers.classPrivateFieldSet(this, _type$1, type);

	      if (babelHelpers.classPrivateFieldGet(this, _domNode)) {
	        babelHelpers.classPrivateFieldGet(this, _domNode).className = _classPrivateMethodGet$a(this, _getClassByType, _getClassByType2).call(this, babelHelpers.classPrivateFieldGet(this, _type$1));
	      }
	    }
	  }]);
	  return Icon;
	}(main_core_events.EventEmitter);

	var _onClickEvent = {
	  writable: true,
	  value: 'onClick'
	};
	babelHelpers.defineProperty(Icon, "TYPE_CLEAR", 'clear');
	babelHelpers.defineProperty(Icon, "TYPE_SEARCH", 'search');
	babelHelpers.defineProperty(Icon, "TYPE_LOADER", 'loader');

	var _getClassByType2 = function _getClassByType2(iconType) {
	  var iconClass = '';

	  if (iconType === Icon.TYPE_CLEAR) {
	    iconClass = "ui-ctl-after ui-ctl-icon-btn ui-ctl-icon-clear";
	  } else if (iconType === Icon.TYPE_SEARCH) {
	    iconClass = "ui-ctl-after ui-ctl-icon-search";
	  } else if (iconType === Icon.TYPE_LOADER) {
	    iconClass = "ui-ctl-after ui-ctl-icon-loader";
	  } else {
	    BX.debug('Wrong icon type');
	  }

	  return iconClass;
	};

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"location-search-control-block\">\n\t\t\t\t\t", "\n\t\t\t\t</div>"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"location-search-control-block\">\n\t\t\t\t\t\t<div class=\"ui-entity-editor-content-block-text\">\n\t\t\t\t\t\t\t", "\t\t\t\t\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"ui-link ui-link-dark ui-link-dotted\">", "</span>"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"location-fields-control-block\"></div>"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\t\t\t\t\t\t    \n\t\t\t\t<div class=\"location-search-control-block\">\t\t\t\t\t\n\t\t\t\t\t", "\n\t\t\t\t</div>"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["", ""]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">", "", "", "</div>"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$5() {
	  var data = babelHelpers.taggedTemplateLiteral(["<input value='", "' type=\"hidden\" name=\"", "\">"]);

	  _templateObject2$5 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$7() {
	  var data = babelHelpers.taggedTemplateLiteral(["<input class=\"ui-ctl-element ui-ctl-textbox\" value=\"\" type=\"text\" autocomplete=\"off\" name=\"", "\">"]);

	  _templateObject$7 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	function _classStaticPrivateMethodGet$1(receiver, classConstructor, method) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } return method; }

	function _classPrivateMethodGet$b(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	/**
	 * Address field widget for the ui.entity-editor
	 */

	var _onIconClick = new WeakSet();

	var _onFieldsSwitchToggle = new WeakSet();

	var _hideFields = new WeakSet();

	var _showFields = new WeakSet();

	var _onAddressWidgetChangedState = new WeakSet();

	var _onAddressChanged = new WeakSet();

	var _convertAddressToString$3 = new WeakSet();

	var _getAddress = new WeakSet();

	var UIAddress = /*#__PURE__*/function (_BX$UI$EntityEditorFi) {
	  babelHelpers.inherits(UIAddress, _BX$UI$EntityEditorFi);

	  function UIAddress(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, UIAddress);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(UIAddress).call(this, props));

	    _getAddress.add(babelHelpers.assertThisInitialized(_this));

	    _convertAddressToString$3.add(babelHelpers.assertThisInitialized(_this));

	    _onAddressChanged.add(babelHelpers.assertThisInitialized(_this));

	    _onAddressWidgetChangedState.add(babelHelpers.assertThisInitialized(_this));

	    _showFields.add(babelHelpers.assertThisInitialized(_this));

	    _hideFields.add(babelHelpers.assertThisInitialized(_this));

	    _onFieldsSwitchToggle.add(babelHelpers.assertThisInitialized(_this));

	    _onIconClick.add(babelHelpers.assertThisInitialized(_this));

	    _this._input = null;
	    _this._inputIcon = null;
	    _this._hiddenInput = null;
	    _this._innerWrapper = null;
	    _this._addressWidget = null;
	    _this._addressFieldsContainer = null;
	    return _this;
	  }

	  babelHelpers.createClass(UIAddress, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(UIAddress.prototype), "initialize", this).call(this, id, settings);
	      var value = this.getValue();
	      var address = null;

	      if (main_core.Type.isStringFilled(value)) {
	        try {
	          address = new location_core.Address(JSON.parse(value));
	        } catch (e) {
	          BX.debug('Cant parse address value');
	          return;
	        }
	      }

	      var widgetFactory = new Factory();
	      this._addressWidget = widgetFactory.createAddressWidget({
	        address: address,
	        mode: this._mode === BX.UI.EntityEditorMode.edit ? location_core.ControlMode.edit : location_core.ControlMode.view,
	        popupBindOptions: {
	          position: 'right'
	        }
	      });

	      this._addressWidget.subscribeOnStateChangedEvent(_classPrivateMethodGet$b(this, _onAddressWidgetChangedState, _onAddressWidgetChangedState2).bind(this));

	      this._addressWidget.subscribeOnAddressChangedEvent(_classPrivateMethodGet$b(this, _onAddressChanged, _onAddressChanged2).bind(this));

	      this._fieldsSwitch = new Switch({
	        state: Switch.STATE_OFF,
	        titles: [BX.message('LOCATION_WIDGET_AUI_MORE'), BX.message('LOCATION_WIDGET_AUI_BRIEFLY')]
	      });

	      this._fieldsSwitch.subscribeOnToggleEventSubscribe(_classPrivateMethodGet$b(this, _onFieldsSwitchToggle, _onFieldsSwitchToggle2).bind(this));
	    }
	  }, {
	    key: "focus",
	    value: function focus() {
	      if (!this._input) {
	        return;
	      }

	      BX.focus(this._input);
	      BX.UI.EditorTextHelper.getCurrent().setPositionAtEnd(this._input);
	    }
	  }, {
	    key: "getModeSwitchType",
	    value: function getModeSwitchType(mode) {
	      var result = BX.UI.EntityEditorModeSwitchType.common;

	      if (mode === BX.UI.EntityEditorMode.edit) {
	        // eslint-disable-next-line no-bitwise
	        result |= BX.UI.EntityEditorModeSwitchType.button | BX.UI.EntityEditorModeSwitchType.content;
	      }

	      return result;
	    }
	  }, {
	    key: "doSetMode",
	    value: function doSetMode(mode) {
	      this._addressWidget.mode = mode === BX.UI.EntityEditorMode.edit ? location_core.ControlMode.edit : location_core.ControlMode.view;
	      this._fieldsSwitch.state = Switch.STATE_OFF;
	    }
	  }, {
	    key: "getContentWrapper",
	    value: function getContentWrapper() {
	      return this._innerWrapper;
	    }
	  }, {
	    key: "save",
	    value: function save() {
	      if (!this.isEditable()) {
	        return;
	      }

	      var address = _classPrivateMethodGet$b(this, _getAddress, _getAddress2).call(this);

	      this._model.setField(this.getName(), address ? address.toJson() : '');

	      this._addressWidget.resetView();
	    }
	  }, {
	    key: "showError",
	    value: function showError(error, anchor) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(UIAddress.prototype), "showError", this).apply(this, [error, anchor]);

	      if (this._input) {
	        BX.addClass(this._inputContainer, 'ui-ctl-danger');
	      }
	    }
	  }, {
	    key: "clearError",
	    value: function clearError() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(UIAddress.prototype), "clearError", this).apply(this);

	      if (this._input) {
	        BX.removeClass(this._inputContainer, 'ui-ctl-danger');
	      }
	    }
	  }, {
	    key: "doClearLayout",
	    value: function doClearLayout(options) {
	      this._input = null;
	      this._innerWrapper = null;
	      this._inputContainer = null;
	      this._addressFieldsContainer = null;
	      this._inputIcon = null;
	      this._hiddenInput = null;
	      main_core.Dom.clean(this._innerWrapper);
	    }
	  }, {
	    key: "validate",
	    value: function validate(result) {
	      if (!(this._mode === BX.UI.EntityEditorMode.edit && this._input)) {
	        throw Error('BX.Location.UIAddress. Invalid validation context');
	      }

	      this.clearError();

	      if (this.hasValidators()) {
	        return this.executeValidators(result);
	      }

	      var isValid = !this.isRequired() || BX.util.trim(this._input.value) !== '';

	      if (!isValid) {
	        result.addError(BX.UI.EntityValidationError.create({
	          field: this
	        }));
	        this.showRequiredFieldError(this._input);
	      }

	      return isValid;
	    }
	  }, {
	    key: "getRuntimeValue",
	    value: function getRuntimeValue() {
	      return this._mode === BX.UI.EntityEditorMode.edit ? _classPrivateMethodGet$b(this, _getAddress, _getAddress2).call(this) : null;
	    }
	  }, {
	    key: "layout",
	    value: function layout(options) {
	      if (this._hasLayout) {
	        return;
	      }

	      this.ensureWrapperCreated({
	        classNames: ['ui-entity-card-content-block-field-phone']
	      });
	      this.adjustWrapper();
	      var title = this.getTitle();

	      if (this.isDragEnabled()) {
	        this._wrapper.appendChild(this.createDragButton());
	      }

	      var addressWidgetParams = {};

	      if (this._mode === BX.UI.EntityEditorMode.edit) {
	        this._wrapper.appendChild(this.createTitleNode(title));

	        this._input = main_core.Tag.render(_templateObject$7(), "".concat(this.getName(), "_STRING"));
	        this._hiddenInput = main_core.Tag.render(_templateObject2$5(), this.getValue(), this.getName());
	        this._inputIcon = new Icon();

	        this._inputIcon.subscribeOnClickEvent(_classPrivateMethodGet$b(this, _onIconClick, _onIconClick2).bind(this));

	        var inputIconNode = this._inputIcon.render({
	          type: _classStaticPrivateMethodGet$1(UIAddress, UIAddress, _chooseInputIconTypeByAddress).call(UIAddress, _classPrivateMethodGet$b(this, _getAddress, _getAddress2).call(this))
	        });

	        this._inputContainer = main_core.Tag.render(_templateObject3$1(), inputIconNode, this._input, this._hiddenInput);

	        this._titleWrapper.appendChild(main_core.Tag.render(_templateObject4$1(), this._fieldsSwitch.render(this._mode)));

	        this._innerWrapper = main_core.Tag.render(_templateObject5(), this._inputContainer);
	        addressWidgetParams.inputNode = this._input;
	        addressWidgetParams.mapBindElement = inputIconNode;
	        this._addressFieldsContainer = main_core.Tag.render(_templateObject6());

	        if (this._fieldsSwitch.state === Switch.STATE_ON) {
	          this._addressFieldsContainer.classList.add('visible');
	        }

	        addressWidgetParams.fieldsContainer = this._addressFieldsContainer;

	        this._innerWrapper.appendChild(this._addressFieldsContainer);
	      } else // if(this._mode === BX.UI.EntityEditorMode.view)
	        {
	          this._wrapper.appendChild(this.createTitleNode(title));

	          var addressStringNode;

	          if (this.hasContentToDisplay()) {
	            var addressString = _classPrivateMethodGet$b(this, _convertAddressToString$3, _convertAddressToString2$3).call(this, _classPrivateMethodGet$b(this, _getAddress, _getAddress2).call(this));

	            addressStringNode = main_core.Tag.render(_templateObject7(), addressString);
	            this._innerWrapper = main_core.Tag.render(_templateObject8(), addressStringNode);
	            addressWidgetParams.mapBindElement = addressStringNode;
	          } else {
	            this._innerWrapper = main_core.Tag.render(_templateObject9(), BX.message('UI_ENTITY_EDITOR_FIELD_EMPTY'));
	            addressWidgetParams.mapBindElement = this._innerWrapper;
	          }
	        }

	      addressWidgetParams.controlWrapper = this._innerWrapper;

	      this._addressWidget.render(addressWidgetParams);

	      this._wrapper.appendChild(this._innerWrapper);

	      this._addressWidget.subscribeOnErrorEvent(this.errorListener.bind(this));

	      if (this.isContextMenuEnabled()) {
	        this._wrapper.appendChild(this.createContextMenuButton());
	      }

	      if (this.isDragEnabled()) {
	        this.initializeDragDropAbilities();
	      }

	      this.registerLayout(options);
	      this._hasLayout = true;
	    }
	  }, {
	    key: "errorListener",
	    value: function errorListener(event) {
	      var _this2 = this;

	      var data = event.getData();
	      var errors = data.errors;

	      if (this._inputIcon) {
	        this._inputIcon.type = Icon.TYPE_CLEAR;
	      }

	      if (!main_core.Type.isArray(errors)) {
	        return;
	      } // todo: this.showError supports only one error


	      errors.forEach(function (error) {
	        var message;

	        if (error.message) {
	          message = error.message;
	        } else {
	          message = BX.message('LOCATION_WIDGET_AUI_UNKNOWN_ERROR');
	        }

	        if (error.code) {
	          message += " [".concat(error.code, "]");
	        }

	        _this2.showError(message);
	      });
	    }
	  }, {
	    key: "processModelChange",
	    value: function processModelChange(params) {
	      if (BX.prop.get(params, 'originator', null) === this) {
	        return;
	      }

	      if (!BX.prop.getBoolean(params, 'forAll', false) && BX.prop.getString(params, 'name', '') !== this.getName()) {
	        return;
	      }

	      this.refreshLayout();
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new UIAddress();
	      self.initialize(id, settings);
	      return self;
	    }
	  }, {
	    key: "registerField",
	    value: function registerField() {
	      if (typeof BX.UI.EntityEditorControlFactory !== 'undefined') {
	        BX.UI.EntityEditorControlFactory.registerFactoryMethod('address', UIAddress.registerFieldMethod);
	      } else {
	        BX.addCustomEvent('BX.UI.EntityEditorControlFactory:onInitialize', function (params, eventArgs) {
	          eventArgs.methods.address = UIAddress.registerFieldMethod;
	        });
	      }
	    }
	  }, {
	    key: "registerFieldMethod",
	    value: function registerFieldMethod(type, controlId, settings) {
	      var result = null;

	      if (type === 'address') {
	        result = UIAddress.create(controlId, settings);
	      }

	      return result;
	    }
	  }]);
	  return UIAddress;
	}(BX.UI.EntityEditorField);

	var _chooseInputIconTypeByAddress = function _chooseInputIconTypeByAddress(address) {
	  return address ? Icon.TYPE_CLEAR : Icon.TYPE_SEARCH;
	};

	var _onIconClick2 = function _onIconClick2() {
	  if (this._input.value !== '') {
	    this._input.value = '';
	    this._addressWidget.address = null;
	    this._inputIcon.type = Icon.TYPE_SEARCH;
	  }

	  if (this.hasError()) {
	    this.clearError();
	  }
	};

	var _onFieldsSwitchToggle2 = function _onFieldsSwitchToggle2(event) {
	  var data = event.getData();
	  var state = data.state;

	  if (state === Switch.STATE_OFF) {
	    _classPrivateMethodGet$b(this, _hideFields, _hideFields2).call(this);
	  } else {
	    _classPrivateMethodGet$b(this, _showFields, _showFields2).call(this);
	  }

	  this._addressWidget.resetView();
	};

	var _hideFields2 = function _hideFields2() {
	  if (this._addressFieldsContainer) {
	    this._addressFieldsContainer.classList.remove('visible');
	  }
	};

	var _showFields2 = function _showFields2() {
	  if (this._addressFieldsContainer) {
	    this._addressFieldsContainer.classList.add('visible');
	  }
	};

	var _onAddressWidgetChangedState2 = function _onAddressWidgetChangedState2(event) {
	  var data = event.getData();
	  var state = data.state;
	  var iconType;

	  if (data.state === location_widget.State.DATA_LOADING) {
	    iconType = Icon.TYPE_LOADER;
	  } else {
	    if (data.state === location_widget.State.DATA_INPUTTING) {
	      this.markAsChanged();
	    }

	    iconType = _classStaticPrivateMethodGet$1(UIAddress, UIAddress, _chooseInputIconTypeByAddress).call(UIAddress, _classPrivateMethodGet$b(this, _getAddress, _getAddress2).call(this));
	  }

	  this._inputIcon.type = iconType;
	};

	var _onAddressChanged2 = function _onAddressChanged2(event) {
	  var data = event.getData();
	  var address = data.address;

	  if (this._hiddenInput) {
	    this._hiddenInput.value = address ? address.toJson() : '';
	    this.markAsChanged();
	  }

	  if (this._inputIcon) {
	    this._inputIcon.type = _classStaticPrivateMethodGet$1(UIAddress, UIAddress, _chooseInputIconTypeByAddress).call(UIAddress, address);
	  }
	};

	var _convertAddressToString2$3 = function _convertAddressToString2(address) {
	  if (!address) {
	    return '';
	  }

	  return address.toString(this._addressWidget.addressFormat);
	};

	var _getAddress2 = function _getAddress2() {
	  return this._addressWidget.address;
	};

	UIAddress.registerField();

	exports.Address = Address;
	exports.BaseFeature = BaseFeature;
	exports.MapFeature = MapFeature;
	exports.AutocompleteFeature = AutocompleteFeature;
	exports.FieldsFeature = FieldsFeature;
	exports.Factory = Factory;
	exports.State = State;
	exports.UIAddress = UIAddress;

}((this.BX.Location.Widget = this.BX.Location.Widget || {}),BX.Location.OSM,BX.Location.Google,BX.Main,BX,BX.Location.Core,BX.Location.Widget,BX.Event,BX));
//# sourceMappingURL=widget.bundle.js.map
