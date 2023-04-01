this.BX = this.BX || {};
(function (exports,main_core,salescenter_manager,ui_ears,ui_vue,location_core,location_widget,ui_notification,main_popup,Hint) {
	'use strict';

	Hint = Hint && Hint.hasOwnProperty('default') ? Hint['default'] : Hint;

	var StringControl = {
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    initValue: {
	      required: false
	    },
	    settings: {
	      required: false
	    },
	    options: {
	      required: false
	    }
	  },
	  created: function created() {
	    this.value = this.initValue;
	  },
	  data: function data() {
	    return {
	      value: null
	    };
	  },
	  methods: {
	    onInput: function onInput(event) {
	      this.value = event.target.value;
	      this.$emit('change', this.value);
	    }
	  },
	  computed: {
	    isMultiline: function isMultiline() {
	      return this.settings && this.settings.MULTILINE === 'Y';
	    }
	  },
	  template: "\n\t\t<div class=\"ui-ctl ui-ctl-w100\">\n\t\t\t<textarea v-if=\"isMultiline\" @input=\"onInput\" :name=\"name\" class=\"ui-ctl-element salescenter-delivery-comment-textarea\" rows=\"1\">{{value}}</textarea>\n\t\t\t<input v-else @input=\"onInput\" type=\"text\" :name=\"name\" :value=\"value\" class=\"ui-ctl-element ui-ctl-textbox\" />\n\t\t</div>\t\t\t\t\t\n\t"
	};

	var handleOutsideClick;
	var ClosableDirective = {
	  bind: function bind(el, binding, vnode) {
	    handleOutsideClick = function handleOutsideClick(e) {
	      if (e.type === 'mousedown' && e.which !== 1) {
	        return;
	      }

	      e.stopPropagation();
	      var _binding$value = binding.value,
	          handler = _binding$value.handler,
	          exclude = _binding$value.exclude;
	      var clickedOnExcludedEl = false;
	      exclude.forEach(function (refName) {
	        if (!clickedOnExcludedEl) {
	          var excludedEl = vnode.context.$refs[refName];

	          if (excludedEl) {
	            clickedOnExcludedEl = excludedEl.contains(e.target);
	          }
	        }
	      });
	      /**
	       * Click inside map wrapper
	       */

	      if (e.target.closest('.location-map-wrapper')) {
	        clickedOnExcludedEl = true;
	      }

	      if (!el.contains(e.target) && !clickedOnExcludedEl) {
	        vnode.context[handler]();
	      }
	    };

	    document.addEventListener('mousedown', handleOutsideClick);
	    document.addEventListener('touchstart', handleOutsideClick);
	  },
	  unbind: function unbind() {
	    document.removeEventListener('mousedown', handleOutsideClick);
	    document.removeEventListener('touchstart', handleOutsideClick);
	  }
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var AddressControl = {
	  directives: {
	    closable: ClosableDirective
	  },
	  props: {
	    name: {
	      type: String,
	      required: true
	    },
	    initValue: {},
	    settings: {},
	    options: {
	      required: false
	    },
	    isStartMarker: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      value: null,
	      enterTookPlace: false,
	      rightIcon: null,
	      isEntering: false,
	      isLoading: false,
	      editMode: false,
	      addressWidgetState: null,
	      enteredAddresses: []
	    };
	  },
	  methods: {
	    switchToEditMode: function switchToEditMode() {
	      this.showMap();
	      this.editMode = true;
	    },
	    clarifyAddress: function clarifyAddress() {
	      var _this = this;

	      setTimeout(function () {
	        _this.$refs['input-node'].focus();

	        _this.$refs['input-node'].click();

	        _this.$refs['input-node'].click();
	      }, 0);
	    },
	    clearAddress: function clearAddress() {
	      this.addressWidget.address = null;
	      this.changeValue(null);
	      this.clarifyAddress();
	    },
	    onControlClicked: function onControlClicked() {
	      this.closeMap();
	    },
	    onControlFocus: function onControlFocus() {
	      this.enterTookPlace = true;
	      this.isEntering = true;
	    },
	    onControlBlur: function onControlBlur() {
	      var _this2 = this;

	      setTimeout(function () {
	        _this2.isEntering = false;
	      }, 200);
	      this.editMode = false;
	      this.closeMap();
	    },
	    changeValue: function changeValue(newValue) {
	      this.value = newValue;
	      this.syncRightIcon();
	      this.$emit('change', this.value);

	      if (this.onChangeCallback) {
	        this.onChangeCallback();
	      }
	    },
	    syncRightIcon: function syncRightIcon() {
	      if (this.$refs['input-node'].value.length === 0) {
	        this.rightIcon = 'search';
	      } else {
	        this.rightIcon = 'clear';
	      }
	    },
	    buildAddress: function buildAddress(value) {
	      try {
	        return new BX.Location.Core.Address(JSON.parse(value));
	      } catch (e) {
	        return null;
	      }
	    },
	    isValueValid: function isValueValid(value) {
	      return value && value.latitude && value.longitude && !(value.latitude === '0' && value.longitude === '0');
	    },
	    getPresetLocationsProvider: function getPresetLocationsProvider() {
	      var _this3 = this;

	      return function () {
	        var result = _this3.options && _this3.options.hasOwnProperty('defaultItems') ? _this3.options.defaultItems.map(function (item) {
	          return new location_core.Location(item);
	        }) : [];

	        var _iterator = _createForOfIteratorHelper(_this3.enteredAddresses),
	            _step;

	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var enteredAddress = _step.value;
	            var location = enteredAddress.toLocation();

	            if (!location) {
	              continue;
	            }

	            location.name = BX.Location.Core.AddressStringConverter.convertAddressToString(enteredAddress, _this3.addressWidget.addressFormat, BX.Location.Core.AddressStringConverter.STRATEGY_TYPE_FIELD_TYPE, BX.Location.Core.AddressStringConverter.CONTENT_TYPE_TEXT);
	            result.push(location);
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }

	        return result.filter(function (location, index, self) {
	          return index === self.findIndex(function (l) {
	            return l.name === location.name;
	          });
	        });
	      };
	    },

	    /**
	     * Map Feature Methods
	     */
	    getMap: function getMap() {
	      if (!this.addressWidget) {
	        return null;
	      }

	      var _iterator2 = _createForOfIteratorHelper(this.addressWidget.features),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var feature = _step2.value;

	          if (feature instanceof BX.Location.Widget.MapFeature) {
	            return feature;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      return null;
	    },
	    showMap: function showMap() {
	      var map = this.getMap();

	      if (map) {
	        map.showMap();
	      }
	    },
	    closeMap: function closeMap() {
	      var map = this.getMap();

	      if (map) {
	        map.closeMap();
	      }
	    }
	  },
	  computed: {
	    addressFormatted: function addressFormatted() {
	      if (!this.value || !this.addressWidget) {
	        return '';
	      }

	      var address = this.buildAddress(this.value);

	      if (!address) {
	        return '';
	      }

	      return address.toString(this.addressWidget.addressFormat, BX.Location.Core.AddressStringConverter.STRATEGY_TYPE_FIELD_SORT);
	    },
	    isEditMode: function isEditMode() {
	      return this.editMode || !this.value;
	    },
	    wrapperClass: function wrapperClass() {
	      return {
	        'ui-ctl': true,
	        'ui-ctl-textbox': true,
	        'ui-ctl-danger': this.needsClarification,
	        'ui-ctl-w100': true,
	        'ui-ctl-after-icon': true,
	        'sale-address-control-top-margin-5 sale-address-control-top-margin-width-820': this.isEditMode
	      };
	    },
	    mapMarkerClass: function mapMarkerClass() {
	      return {
	        'salescenter-delivery-path-icon': true,
	        'salescenter-delivery-path-icon--green': !this.isStartMarker
	      };
	    },
	    rightIconClass: function rightIconClass() {
	      return {
	        'ui-ctl-after': true,
	        'ui-ctl-icon-btn': true,
	        'ui-ctl-icon-search': this.rightIcon === 'search',
	        'ui-ctl-icon-clear': this.rightIcon === 'clear',
	        'sale-address-control-path-input-clear': true
	      };
	    },
	    needsClarification: function needsClarification() {
	      return !this.isEntering && this.enterTookPlace && !this.value && this.addressWidgetState !== location_widget.State.DATA_LOADING;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
	    }
	  },
	  mounted: function mounted() {
	    var _this4 = this;

	    if (this.initValue) {
	      var initValue = null;
	      var address = JSON.parse(this.initValue);

	      if (this.isValueValid(address)) {
	        initValue = this.initValue;
	      } else {
	        /**
	         * Simulate invalid input
	         */
	        this.isEntering = false;
	        this.enterTookPlace = true;
	      }

	      this.changeValue(initValue);
	    }

	    this.addressWidget = new BX.Location.Widget.Factory().createAddressWidget({
	      address: this.initValue ? this.buildAddress(this.initValue) : null,
	      mode: BX.Location.Core.ControlMode.edit,
	      mapBehavior: 'manual',
	      useFeatures: {
	        fields: false,
	        map: true,
	        autocomplete: true
	      },
	      popupOptions: {
	        offsetLeft: 14
	      },
	      popupBindOptions: {
	        forceBindPosition: true
	      },
	      presetLocationsProvider: this.getPresetLocationsProvider()
	    });
	    /**
	     * Redefine native onInputKeyup
	     */

	    var nativeOnInputKeyup = this.addressWidget.onInputKeyup;

	    this.addressWidget.onInputKeyup = function (e) {
	      switch (e.code) {
	        case 'Enter':
	        case 'NumpadEnter':
	          return;

	        default:
	          break;
	      }

	      nativeOnInputKeyup.call(_this4.addressWidget, e);
	    };
	    /**
	     * Subscribe to widget events
	     */


	    this.addressWidget.subscribeOnAddressChangedEvent(function (event) {
	      var data = event.getData();
	      _this4.editMode = true;
	      var address = data.address;

	      if (!_this4.isValueValid(address)) {
	        _this4.changeValue(null);
	      } else {
	        _this4.enteredAddresses.push(address);

	        _this4.changeValue(address.toJson());

	        _this4.showMap();
	      }
	    });
	    this.addressWidget.subscribeOnStateChangedEvent(function (event) {
	      var data = event.getData();
	      _this4.addressWidgetState = data.state;

	      if (data.state === location_widget.State.DATA_INPUTTING) {
	        _this4.changeValue(null);

	        _this4.closeMap();
	      } else if (data.state === location_widget.State.DATA_LOADING) {
	        _this4.isLoading = true;
	      } else if (data.state === location_widget.State.DATA_LOADED) {
	        _this4.isLoading = false;
	      }
	    });
	    this.addressWidget.subscribeOnFeatureEvent(function (event) {
	      var data = event.getData();

	      if (data.feature instanceof location_widget.AutocompleteFeature) {
	        if (data.eventCode === location_widget.AutocompleteFeature.searchStartedEvent) {
	          _this4.isLoading = true;
	        } else if (data.eventCode === location_widget.AutocompleteFeature.searchCompletedEvent) {
	          _this4.isLoading = false;
	        }
	      }
	    });
	    this.addressWidget.subscribeOnErrorEvent(function (event) {
	      var data = event.getData();
	      var errors = data.errors;
	      var errorMessage = errors.map(function (error) {
	        return error.message + (error.code.length ? "".concat(error.code) : '');
	      }).join(', ');
	      _this4.isLoading = false;
	      BX.UI.Notification.Center.notify({
	        content: errorMessage
	      });
	    });
	    /**
	     * Render widget
	     */

	    this.addressWidget.render({
	      inputNode: this.$refs['input-node'],
	      autocompleteMenuElement: this.$refs['autocomplete-menu'],
	      mapBindElement: this.$refs['map-marker'],
	      controlWrapper: this.$refs['autocomplete-menu']
	    });
	    this.syncRightIcon();
	  },
	  template: "\n\t\t<div class=\"salescenter-delivery-path-control\">\n\t\t\t<div ref=\"map-marker\" :class=\"mapMarkerClass\"></div>\n\t\t\t\t<div\n\t\t\t\t\tv-closable=\"{\n\t\t\t\t\t\texclude: ['input-node'],\n\t\t\t\t\t\thandler: 'onControlBlur'\n\t\t\t\t\t}\"\n\t\t\t\t\tclass=\"ui-ctl-w100\"\n\t\t\t\t>\n\t\t\t\t\t<div :class=\"wrapperClass\">\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-show=\"isLoading\"\n\t\t\t\t\t\t\tclass=\"ui-ctl-after ui-ctl-icon-loader\"\n\t\t\t\t\t\t></div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-show=\"isEditMode\" \n\t\t\t\t\t\t\tref=\"autocomplete-menu\"\n\t\t\t\t\t\t\tclass=\"sale-address-control-path-input-wrapper\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t@click=\"onControlClicked\"\n\t\t\t\t\t\t\t\t@focus=\"onControlFocus\"\n\t\t\t\t\t\t\t\tref=\"input-node\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-show=\"!isLoading && isEditMode\"\n\t\t\t\t\t\t\t\t@click=\"clearAddress\"\n\t\t\t\t\t\t\t\t@mouseover.stop.prevent=\"\"\n\t\t\t\t\t\t\t\t:class=\"rightIconClass\"\n\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\tv-show=\"needsClarification\"\n\t\t\t\t\t\t\t\t@mouseover.stop.prevent=\"\"\n\t\t\t\t\t\t\t\t@click=\"clarifyAddress\"\n\t\t\t\t\t\t\t\tclass=\"sale-address-control-path-input--alert\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLARIFY_ADDRESS}}\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-show=\"!isEditMode\"class=\"sale-address-control-path-input-wrapper\">\n\t\t\t\t\t\t\t<span\n\t\t\t\t\t\t\t\t@click=\"switchToEditMode\"\n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox sale-address-control-path-input\"\n\t\t\t\t\t\t\t\tcontenteditable=\"false\"\n\t\t\t\t\t\t\t\tv-html=\"addressFormatted\"\n\t\t\t\t\t\t\t></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<input v-model=\"value\" :name=\"name\" type=\"hidden\" />\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var CheckboxService = {
	  props: {
	    name: {
	      required: false
	    },
	    initValue: {
	      required: false
	    }
	  },
	  created: function created() {
	    this.value = this.initValue;
	  },
	  data: function data() {
	    return {
	      value: null
	    };
	  },
	  methods: {
	    onChange: function onChange(event) {
	      this.value = event.target.checked ? 'Y' : '';
	      this.$emit('change', this.value);
	    }
	  },
	  template: "\n\t\t<label class=\"salescenter-delivery-selector salescenter-delivery-selector--hover salescenter-delivery-selector--checkbox\">\n\t\t\t<input @change=\"onChange\" :checked=\"value == 'Y' ? true : false\" type=\"checkbox\" value=\"Y\" />\n\t\t\t<span class=\"salescenter-delivery-selector-text\">{{name}}</span>\n\t\t</label>\n\t"
	};

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var DropdownService = {
	  props: {
	    name: {
	      required: false
	    },
	    initValue: {
	      required: false
	    },
	    options: {
	      required: true,
	      type: Array
	    }
	  },
	  created: function created() {
	    this.value = this.initValue;
	  },
	  data: function data() {
	    return {
	      value: null
	    };
	  },
	  methods: {
	    getSelectedItemTitle: function getSelectedItemTitle() {
	      var selectedItem = this.getSelectedItem();

	      if (!selectedItem || selectedItem.id === 'null') {
	        return this.name;
	      }

	      return selectedItem.title;
	    },
	    getSelectedItem: function getSelectedItem() {
	      var _iterator = _createForOfIteratorHelper$1(this.options),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var option = _step.value;

	          if (option.id === this.value) {
	            return option;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      return null;
	    },
	    showPopupMenu: function showPopupMenu(e) {
	      var _this = this;

	      var menuItems = [];

	      var _iterator2 = _createForOfIteratorHelper$1(this.options),
	          _step2;

	      try {
	        var _loop = function _loop() {
	          var option = _step2.value;
	          menuItems.push({
	            'text': option.title,
	            onclick: function onclick() {
	              _this.value = option.id;

	              _this.$emit('change', _this.value);

	              _this.popupMenu.close();
	            }
	          });
	        };

	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          _loop();
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      this.popupMenu = new main_popup.Menu({
	        bindElement: e.target,
	        items: menuItems,
	        angle: true,
	        closeByEsc: true,
	        offsetLeft: 40
	      });
	      this.popupMenu.show();
	    }
	  },
	  template: "\n\t\t<div @click=\"showPopupMenu($event)\" class=\"salescenter-delivery-selector salescenter-delivery-selector--dropdown\">\n\t\t\t<span class=\"salescenter-delivery-selector-text\">{{getSelectedItemTitle()}}</span>\n\t\t</div>\n\t"
	};

	var _templateObject;

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }

	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var deliveryselector = {
	  components: {
	    /**
	     * Properties Control Types
	     */
	    'ADDRESS-control': AddressControl,
	    'STRING-control': StringControl,

	    /**
	     * Extra Services Control Types
	     */
	    'checkbox-service': CheckboxService,
	    'dropdown-service': DropdownService
	  },
	  props: {
	    initDeliveryServiceId: {
	      required: false
	    },
	    initRelatedServicesValues: {
	      required: false
	    },
	    initRelatedPropsValues: {
	      required: false
	    },
	    initRelatedPropsOptions: {
	      required: false
	    },
	    initResponsibleId: {
	      "default": null,
	      required: false
	    },
	    initEnteredDeliveryPrice: {
	      required: false
	    },
	    personTypeId: {
	      required: true
	    },
	    action: {
	      type: String,
	      required: true
	    },
	    actionData: {
	      type: Object,
	      required: true
	    },
	    externalSum: {
	      required: true
	    },
	    externalSumLabel: {
	      type: String,
	      required: true
	    },
	    currency: {
	      type: String,
	      required: true
	    },
	    currencySymbol: {
	      type: String,
	      required: true
	    },
	    availableServices: {
	      type: [Object, Array],
	      required: true
	    },
	    excludedServiceIds: {
	      type: Array,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      /**
	       * Selected Service
	       */
	      selectedDeliveryService: null,
	      deliveryServices: [],

	      /**
	       * Props
	       */
	      relatedProps: [],
	      relatedPropsOfAddressType: [],
	      relatedPropsOfOtherTypes: [],
	      relatedPropsValues: {},

	      /**
	       * Extra Services
	       */
	      relatedServices: [],
	      relatedServicesValues: {},

	      /**
	       * Prices
	       */
	      estimatedDeliveryPrice: null,
	      enteredDeliveryPrice: null,

	      /**
	       * Responsible User
	       */
	      responsibleUser: null,

	      /**
	       * Processing Indicators
	       */
	      isCalculated: false,
	      isCalculating: false,
	      calculateErrors: [],
	      restrictionsHintPopup: null
	    };
	  },
	  methods: {
	    initialize: function initialize() {
	      var _this = this;

	      main_core.ajax.runAction('salescenter.deliveryselector.getinitializationdata', {
	        data: {
	          personTypeId: this.personTypeId,
	          responsibleId: this.initResponsibleId,
	          excludedServiceIds: this.excludedServiceIds
	        }
	      }).then(function (result) {
	        /**
	         * Delivery services
	         */
	        _this.deliveryServices = result.data.services;

	        if (_this.deliveryServices.length > 0) {
	          var initDeliveryServiceId = _this.selectedDeliveryService ? _this.selectedDeliveryService.id : _this.initDeliveryServiceId ? _this.initDeliveryServiceId : null;

	          if (initDeliveryServiceId) {
	            var _iterator = _createForOfIteratorHelper$2(_this.deliveryServices),
	                _step;

	            try {
	              for (_iterator.s(); !(_step = _iterator.n()).done;) {
	                var deliveryService = _step.value;

	                if (deliveryService.id == initDeliveryServiceId) {
	                  _this.onDeliveryServiceChanged(deliveryService, true);

	                  break;
	                }

	                var _iterator2 = _createForOfIteratorHelper$2(deliveryService.profiles),
	                    _step2;

	                try {
	                  for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	                    var profile = _step2.value;

	                    if (profile.id == initDeliveryServiceId) {
	                      _this.onDeliveryServiceChanged(profile, true);

	                      break;
	                    }
	                  }
	                } catch (err) {
	                  _iterator2.e(err);
	                } finally {
	                  _iterator2.f();
	                }
	              }
	            } catch (err) {
	              _iterator.e(err);
	            } finally {
	              _iterator.f();
	            }
	          }
	        }
	        /**
	         * Related props
	         */


	        var relatedProps = result.data.properties;
	        /**
	         * Setting default values to related props
	         */

	        var _iterator3 = _createForOfIteratorHelper$2(relatedProps),
	            _step3;

	        try {
	          for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	            var relatedProp = _step3.value;
	            var initValue = null;

	            if (_this.initRelatedPropsValues && _this.initRelatedPropsValues.hasOwnProperty(relatedProp.id)) {
	              initValue = _this.initRelatedPropsValues[relatedProp.id];
	            } else if (relatedProp.initValue) {
	              initValue = relatedProp.initValue;
	            }

	            if (initValue !== null) {
	              initValue = babelHelpers["typeof"](initValue) === 'object' ? JSON.stringify(initValue) : initValue;
	              ui_vue.Vue.set(_this.relatedPropsValues, relatedProp.id, initValue);
	            }
	          }
	        } catch (err) {
	          _iterator3.e(err);
	        } finally {
	          _iterator3.f();
	        }

	        _this.relatedProps = relatedProps;
	        _this.relatedPropsOfAddressType = _this.relatedProps.filter(function (item) {
	          return item.type === 'ADDRESS';
	        });
	        _this.relatedPropsOfOtherTypes = _this.relatedProps.filter(function (item) {
	          return item.type !== 'ADDRESS';
	        });
	        /**
	         * Related services
	         */

	        var relatedServices = result.data.extraServices;

	        var _iterator4 = _createForOfIteratorHelper$2(relatedServices),
	            _step4;

	        try {
	          for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	            var relatedService = _step4.value;
	            var _initValue = null;

	            if (_this.initRelatedServicesValues && _this.initRelatedServicesValues.hasOwnProperty(relatedService.id)) {
	              _initValue = _this.initRelatedServicesValues[relatedService.id];
	            } else if (relatedService.initValue) {
	              _initValue = relatedService.initValue;
	            }

	            if (_initValue !== null) {
	              ui_vue.Vue.set(_this.relatedServicesValues, relatedService.id, _initValue);
	            }
	          }
	        } catch (err) {
	          _iterator4.e(err);
	        } finally {
	          _iterator4.f();
	        }

	        _this.relatedServices = relatedServices;
	        /**
	         * Responsible
	         */

	        _this.responsibleUser = result.data.responsible;
	        /**
	         * Misc
	         */

	        _this._userPageTemplate = result.data.userPageTemplate;
	        _this._deliverySettingsUrl = result.data.deliverySettingsUrl;
	        /**
	         *
	         */

	        if (_this.initEnteredDeliveryPrice !== null) {
	          _this.enteredDeliveryPrice = _this.initEnteredDeliveryPrice;
	        }

	        new ui_ears.Ears({
	          container: _this.$refs['delivery-methods'],
	          smallSize: true,
	          noScrollbar: true
	        }).init();

	        _this.emitChange();

	        _this.recalculateRelatedServiceAvailabilities();
	      });
	    },
	    calculate: function calculate() {
	      var _this2 = this;

	      if (!this.isCalculatingAllowed) {
	        return;
	      }

	      this.isCalculating = true;

	      var calculationFinallyCallback = function calculationFinallyCallback(status, payload) {
	        _this2.isCalculating = false;
	        _this2.isCalculated = true;

	        _this2.emitChange();
	      };

	      var actionData = Object.assign({}, this.actionData, {
	        deliveryServiceId: this.selectedDeliveryServiceId,
	        shipmentPropValues: this.currentRelatedPropsValues,
	        deliveryRelatedServiceValues: this.currentRelatedServicesValues,
	        deliveryResponsibleId: this.responsibleUser ? this.responsibleUser.id : null
	      });
	      main_core.ajax.runAction(this.action, {
	        data: actionData
	      }).then(function (result) {
	        var deliveryPrice = result.data.deliveryPrice;
	        _this2.estimatedDeliveryPrice = deliveryPrice;
	        _this2.enteredDeliveryPrice = deliveryPrice;
	        _this2.calculateErrors = [];
	        calculationFinallyCallback();
	      })["catch"](function (result) {
	        _this2.estimatedDeliveryPrice = null;
	        _this2.enteredDeliveryPrice = 0.00;
	        _this2.calculateErrors = result.errors.map(function (item) {
	          return item.message;
	        });
	        calculationFinallyCallback();
	      });
	    },
	    openChangeResponsibleDialog: function openChangeResponsibleDialog(event) {
	      var self = this;

	      if (typeof this._userEditor === 'undefined') {
	        this._userEditor = new BX.Crm.EntityEditorUserSelector();

	        this._userEditor.initialize('deliverySelectorUserEditor', {
	          callback: function callback(item, type, search, bUndeleted) {
	            self.responsibleUser = {
	              id: type.entityId,
	              name: type.name,
	              photo: type.avatar
	            };
	            self.emitChange();
	          }
	        });
	      }

	      this._userEditor.open(event.target.parentElement);
	    },
	    onDeliveryServiceChanged: function onDeliveryServiceChanged(deliveryService) {
	      var selfCall = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

	      if (!this.isServiceAvailable(deliveryService) && !selfCall) {
	        return;
	      }

	      if (!deliveryService.parentId && deliveryService.profiles.length > 0) {
	        var firstAvailableProfile;

	        var _iterator5 = _createForOfIteratorHelper$2(deliveryService.profiles),
	            _step5;

	        try {
	          for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	            var profile = _step5.value;

	            if (this.isServiceAvailable(profile)) {
	              firstAvailableProfile = profile;
	              break;
	            }
	          }
	        } catch (err) {
	          _iterator5.e(err);
	        } finally {
	          _iterator5.f();
	        }

	        if (firstAvailableProfile) {
	          this.onDeliveryServiceChanged(firstAvailableProfile, true);
	        } else {
	          this.onDeliveryServiceChanged(deliveryService.profiles[0], true);
	        }
	      } else {
	        this.selectedDeliveryService = deliveryService;
	        this.emitChange();
	        this.emitServiceChanged();
	      }
	    },
	    isNoDeliveryService: function isNoDeliveryService(service) {
	      return service['code'] === 'NO_DELIVERY';
	    },
	    isServiceAvailable: function isServiceAvailable(service) {
	      return this.availableServices.hasOwnProperty(service.id);
	    },
	    isServiceProfitable: function isServiceProfitable(service) {
	      return service.hasOwnProperty('tags') && Array.isArray(service.tags) && service.tags.includes('profitable');
	    },
	    onPropValueChanged: function onPropValueChanged(event, relatedProp) {
	      ui_vue.Vue.set(this.relatedPropsValues, relatedProp.id, event);
	      this.emitChange();

	      if (relatedProp.isAddressFrom) {
	        this.emitAddressFromChanged();
	      }
	    },
	    onServiceValueChanged: function onServiceValueChanged(event, relatedService) {
	      ui_vue.Vue.set(this.relatedServicesValues, relatedService.id, event);
	      this.emitChange();
	    },
	    responsibleUserClicked: function responsibleUserClicked() {
	      salescenter_manager.Manager.openSlider(this.responsibleUserLink);
	    },
	    emitChange: function emitChange() {
	      this.$emit('change', this.state);
	    },
	    emitAddressFromChanged: function emitAddressFromChanged() {
	      this.$emit('address-from-changed');
	    },
	    emitServiceChanged: function emitServiceChanged() {
	      this.$emit('delivery-service-changed');
	    },
	    formatMoney: function formatMoney(value) {
	      return BX.Currency.currencyFormat(value, this.currency, false);
	    },
	    isNumber: function isNumber(event) {
	      event = event ? event : window.event;
	      var charCode = event.which ? event.which : event.keyCode;

	      if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
	        event.preventDefault();
	      } else {
	        return true;
	      }

	      return false;
	    },
	    getPropValue: function getPropValue(relatedProp) {
	      if (!this.relatedPropsValues) {
	        return null;
	      }

	      return this.relatedPropsValues.hasOwnProperty(relatedProp.id) ? this.relatedPropsValues[relatedProp.id] : null;
	    },
	    getPropOptions: function getPropOptions(relatedProp) {
	      if (!this.initRelatedPropsOptions) {
	        return null;
	      }

	      return this.initRelatedPropsOptions.hasOwnProperty(relatedProp.id) ? this.initRelatedPropsOptions[relatedProp.id] : null;
	    },
	    getPropName: function getPropName(relatedProp) {
	      if (relatedProp.isAddressFrom) {
	        return main_core.Loc.getMessage('SALE_DELIVERY_SERVICE_SHIPMENT_ADDRESS_FROM_LABEL');
	      }

	      if (relatedProp.isAddressTo) {
	        return main_core.Loc.getMessage('SALE_DELIVERY_SERVICE_SHIPMENT_ADDRESS_TO_LABEL');
	      }

	      return relatedProp.name;
	    },
	    getServiceValue: function getServiceValue(relatedService) {
	      if (!this.relatedServicesValues) {
	        return null;
	      }

	      return this.relatedServicesValues.hasOwnProperty(relatedService.id) ? this.relatedServicesValues[relatedService.id] : null;
	    },
	    onAddMoreClicked: function onAddMoreClicked() {
	      var _this3 = this;

	      salescenter_manager.Manager.openSlider(this._deliverySettingsUrl).then(function () {
	        _this3.initialize();

	        _this3.$emit('settings-changed');
	      });
	    },
	    getDeliveryServiceById: function getDeliveryServiceById(id) {
	      var _iterator6 = _createForOfIteratorHelper$2(this.deliveryServices),
	          _step6;

	      try {
	        for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
	          var deliveryService = _step6.value;

	          if (deliveryService.id == id) {
	            return deliveryService;
	          }
	        }
	      } catch (err) {
	        _iterator6.e(err);
	      } finally {
	        _iterator6.f();
	      }

	      return null;
	    },
	    isParentDeliveryServiceSelected: function isParentDeliveryServiceSelected(deliveryService) {
	      if (!this.selectedParentDeliveryService) {
	        return false;
	      }

	      return this.selectedParentDeliveryService.id == deliveryService.id;
	    },
	    onRestrictionsHintShow: function onRestrictionsHintShow(e, profile) {
	      this.restrictionsHintPopup = new Hint.Popup();
	      this.restrictionsHintPopup.show(e.target, this.buildRestrictionsNode(profile));
	    },
	    isVisibleProfileRestriction: function isVisibleProfileRestriction(profile) {
	      return profile.restrictions && Array.isArray(profile.restrictions) && profile.restrictions.length > 0;
	    },
	    buildRestrictionsNode: function buildRestrictionsNode(profile) {
	      var restrictionsNodes = profile.restrictions.map(function (restriction) {
	        return "<div>".concat(main_core.Text.encode(restriction), "</div>");
	      });
	      return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), restrictionsNodes.join(''));
	    },
	    onRestrictionsHintHide: function onRestrictionsHintHide(e) {
	      if (this.restrictionsHintPopup) {
	        this.restrictionsHintPopup.hide();
	      }
	    },
	    isProfileSelected: function isProfileSelected(profile) {
	      return this.selectedDeliveryService && this.selectedDeliveryService.id == profile.id && this.isServiceAvailable(profile);
	    },
	    getProfileClass: function getProfileClass(profile) {
	      return {
	        'salescenter-delivery-car-item': true,
	        'salescenter-delivery-car-item--selected': this.isProfileSelected(profile),
	        'salescenter-delivery-car-item--disabled': !this.isServiceAvailable(profile)
	      };
	    },
	    isRelatedServiceRelevant: function isRelatedServiceRelevant(relatedService) {
	      return relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId);
	    },
	    isRelatedServiceAvailable: function isRelatedServiceAvailable(relatedService) {
	      return relatedService.hasOwnProperty('isAvailable') && relatedService.isAvailable;
	    },
	    getRelatedServiceStyle: function getRelatedServiceStyle(relatedService) {
	      return {
	        'opacity': this.isRelatedServiceAvailable(relatedService) ? 1 : 0.5,
	        'pointer-events': this.isRelatedServiceAvailable(relatedService) ? 'auto' : 'none'
	      };
	    },
	    getProfileLogoStyle: function getProfileLogoStyle(logo) {
	      if (!logo) {
	        return {};
	      }

	      return {
	        backgroundImage: 'url(' + logo.src + ')',
	        backgroundSize: logo.width < 55 ? 'auto' : 'contain'
	      };
	    },
	    recalculateRelatedServiceAvailabilities: function recalculateRelatedServiceAvailabilities() {
	      for (var i = 0; i < this.relatedServices.length; i++) {
	        var relatedService = this.relatedServices[i];
	        var isAvailable = false;

	        var _iterator7 = _createForOfIteratorHelper$2(relatedService.deliveryServiceIds),
	            _step7;

	        try {
	          for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
	            var deliveryServiceId = _step7.value;

	            if (this.availableServices.hasOwnProperty(deliveryServiceId)) {
	              if (this.availableServices[deliveryServiceId] === null || Array.isArray(this.availableServices[deliveryServiceId]) && this.availableServices[deliveryServiceId].includes(relatedService.id)) {
	                isAvailable = true;
	                break;
	              }
	            }
	          }
	        } catch (err) {
	          _iterator7.e(err);
	        } finally {
	          _iterator7.f();
	        }

	        relatedService.isAvailable = isAvailable;
	        ui_vue.Vue.set(this.relatedServices, i, relatedService);
	      }
	    },
	    isSelectorDisabled: function isSelectorDisabled() {
	      return this.$store.getters['orderCreation/isCompilationMode'];
	    }
	  },
	  created: function created() {
	    this.initialize();
	  },
	  watch: {
	    enteredDeliveryPrice: function enteredDeliveryPrice(value) {
	      this.emitChange();
	    },
	    areProfilesVisible: function areProfilesVisible(newValue, oldValue) {
	      var _this4 = this;

	      if (!oldValue && newValue) {
	        //uncomment the block belowe to apply the ears plugin to profiles' section
	        setTimeout(function () {
	          new ui_ears.Ears({
	            container: _this4.$refs['delivery-profiles'],
	            smallSize: true,
	            noScrollbar: true,
	            className: 'salescenter-delivery-ears'
	          }).init();
	        }, 0);
	      }
	    },
	    isSelectedDeliveryServiceAvailable: function isSelectedDeliveryServiceAvailable(newValue, oldValue) {
	      if (oldValue && !newValue) {
	        this.isCalculated = false;
	        this.estimatedDeliveryPrice = null;
	        this.enteredDeliveryPrice = 0.00;
	      }
	    },
	    availableServices: function availableServices(newValue) {
	      this.recalculateRelatedServiceAvailabilities();
	    }
	  },
	  computed: {
	    state: function state() {
	      return {
	        deliveryServiceId: this.selectedDeliveryServiceId,
	        deliveryServiceName: this.selectedDeliveryServiceName,
	        deliveryPrice: this.deliveryPrice,
	        estimatedDeliveryPrice: this.estimatedDeliveryPrice,
	        relatedPropsValues: this.currentRelatedPropsValues,
	        relatedServicesValues: this.currentRelatedServicesValues,
	        responsibleUser: this.responsibleUser
	      };
	    },
	    selectedDeliveryServiceId: function selectedDeliveryServiceId() {
	      return this.selectedDeliveryService ? this.selectedDeliveryService.id : null;
	    },
	    selectedDeliveryServiceName: function selectedDeliveryServiceName() {
	      if (!this.selectedDeliveryService) {
	        return null;
	      }

	      if (this.selectedParentDeliveryService === this.selectedDeliveryService) {
	        return this.selectedDeliveryService.name;
	      }

	      return this.selectedParentDeliveryService.name + ': ' + this.selectedDeliveryService.name;
	    },
	    selectedParentDeliveryService: function selectedParentDeliveryService() {
	      if (!this.selectedDeliveryService) {
	        return null;
	      }

	      return this.selectedDeliveryService.parentId ? this.getDeliveryServiceById(this.selectedDeliveryService.parentId) : this.selectedDeliveryService;
	    },
	    selectedNoDelivery: function selectedNoDelivery() {
	      return this.selectedDeliveryService && this.isNoDeliveryService(this.selectedDeliveryService);
	    },
	    isCalculatingAllowed: function isCalculatingAllowed() {
	      return this.selectedDeliveryServiceId && this.arePropValuesReady && this.isSelectedDeliveryServiceAvailable && !this.isCalculating;
	    },
	    currentRelatedPropsValues: function currentRelatedPropsValues() {
	      var result = [];

	      if (!this.selectedDeliveryServiceId) {
	        return result;
	      }

	      var _iterator8 = _createForOfIteratorHelper$2(this.relatedProps),
	          _step8;

	      try {
	        for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
	          var relatedProp = _step8.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          if (this.relatedPropsValues.hasOwnProperty(relatedProp.id)) {
	            result.push({
	              id: relatedProp.id,
	              value: this.relatedPropsValues[relatedProp.id]
	            });
	          }
	        }
	      } catch (err) {
	        _iterator8.e(err);
	      } finally {
	        _iterator8.f();
	      }

	      return result;
	    },
	    isResponsibleUserSectionVisible: function isResponsibleUserSectionVisible() {
	      return this.responsibleUser && !this.selectedNoDelivery;
	    },
	    responsibleUserLink: function responsibleUserLink() {
	      if (!this.responsibleUser) {
	        return '';
	      }

	      return this._userPageTemplate.replace('#user_id#', this.responsibleUser.id);
	    },
	    arePropValuesReady: function arePropValuesReady() {
	      if (!this.selectedDeliveryServiceId) {
	        return false;
	      }

	      var _iterator9 = _createForOfIteratorHelper$2(this.relatedProps),
	          _step9;

	      try {
	        for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
	          var relatedProp = _step9.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          if (relatedProp.required && !this.relatedPropsValues[relatedProp.id]) {
	            return false;
	          }
	        }
	      } catch (err) {
	        _iterator9.e(err);
	      } finally {
	        _iterator9.f();
	      }

	      return true;
	    },
	    currentRelatedServicesValues: function currentRelatedServicesValues() {
	      var result = [];

	      if (!this.selectedDeliveryServiceId) {
	        return result;
	      }

	      var _iterator10 = _createForOfIteratorHelper$2(this.relatedServices),
	          _step10;

	      try {
	        for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
	          var relatedService = _step10.value;

	          if (!(this.isRelatedServiceRelevant(relatedService) && this.isRelatedServiceAvailable(relatedService))) {
	            continue;
	          }

	          if (this.relatedServicesValues.hasOwnProperty(relatedService.id)) {
	            result.push({
	              id: relatedService.id,
	              value: this.relatedServicesValues[relatedService.id]
	            });
	          }
	        }
	      } catch (err) {
	        _iterator10.e(err);
	      } finally {
	        _iterator10.f();
	      }

	      return result;
	    },
	    totalPrice: function totalPrice() {
	      var result = this.externalSum;

	      if (this.deliveryPrice !== null) {
	        result += this.deliveryPrice;
	      }

	      return result;
	    },
	    totalPriceFormatted: function totalPriceFormatted() {
	      return this.formatMoney(this.totalPrice);
	    },
	    deliveryPrice: function deliveryPrice() {
	      if (!this.selectedDeliveryServiceId) {
	        return null;
	      }

	      if (this.selectedNoDelivery) {
	        return 0;
	      }

	      if (this.enteredDeliveryPrice) {
	        return +this.enteredDeliveryPrice;
	      }

	      return null;
	    },
	    deliveryPriceFormatted: function deliveryPriceFormatted() {
	      return this.formatMoney(this.deliveryPrice);
	    },
	    estimatedDeliveryPriceFormatted: function estimatedDeliveryPriceFormatted() {
	      return this.formatMoney(this.estimatedDeliveryPrice);
	    },
	    externalSumFormatted: function externalSumFormatted() {
	      return this.formatMoney(this.externalSum);
	    },
	    calculateDeliveryPriceButtonClass: function calculateDeliveryPriceButtonClass() {
	      return {
	        'ui-btn': true,
	        'ui-btn-light-border': true,
	        'salescenter-delivery-bottom-update-icon': true,
	        'ui-btn-disabled': !this.isCalculatingAllowed
	      };
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
	    },
	    extraServicesCount: function extraServicesCount() {
	      var result = 0;

	      var _iterator11 = _createForOfIteratorHelper$2(this.relatedServices),
	          _step11;

	      try {
	        for (_iterator11.s(); !(_step11 = _iterator11.n()).done;) {
	          var relatedService = _step11.value;

	          if (!this.isRelatedServiceRelevant(relatedService)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator11.e(err);
	      } finally {
	        _iterator11.f();
	      }

	      return result;
	    },
	    relatedPropsOfAddressTypeCount: function relatedPropsOfAddressTypeCount() {
	      var result = 0;

	      var _iterator12 = _createForOfIteratorHelper$2(this.relatedPropsOfAddressType),
	          _step12;

	      try {
	        for (_iterator12.s(); !(_step12 = _iterator12.n()).done;) {
	          var relatedProp = _step12.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator12.e(err);
	      } finally {
	        _iterator12.f();
	      }

	      return result;
	    },
	    relatedPropsOfOtherTypeCount: function relatedPropsOfOtherTypeCount() {
	      var result = 0;

	      var _iterator13 = _createForOfIteratorHelper$2(this.relatedPropsOfOtherTypes),
	          _step13;

	      try {
	        for (_iterator13.s(); !(_step13 = _iterator13.n()).done;) {
	          var relatedProp = _step13.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator13.e(err);
	      } finally {
	        _iterator13.f();
	      }

	      return result;
	    },
	    areProfilesVisible: function areProfilesVisible() {
	      return this.selectedParentDeliveryService && this.selectedParentDeliveryService.profiles.length > 0;
	    },
	    selectedParentServiceName: function selectedParentServiceName() {
	      return this.selectedParentDeliveryService ? this.selectedParentDeliveryService.name : '';
	    },
	    selectedParentServiceProfiles: function selectedParentServiceProfiles() {
	      if (!this.selectedParentDeliveryService) {
	        return [];
	      }

	      return this.selectedParentDeliveryService.profiles;
	    },
	    isSelectedDeliveryServiceAvailable: function isSelectedDeliveryServiceAvailable() {
	      return this.selectedDeliveryService && this.isServiceAvailable(this.selectedDeliveryService);
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-delivery\" :class=\"{'salescenter-delivery--disabled': isSelectorDisabled()}\">\n\t\t\t<div class=\"salescenter-delivery-header\">\n\t\t\t\t<div class=\"salescenter-delivery-car-title--sm\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_METHOD}}</div>\n\t\t\t\t<div class=\"salescenter-delivery-method\" ref=\"delivery-methods\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-for=\"deliveryService in deliveryServices\"\n\t\t\t\t\t\t@click=\"onDeliveryServiceChanged(deliveryService)\"\n\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t'salescenter-delivery-method-item': true,\n\t\t\t\t\t\t\t'salescenter-delivery-method-item--selected': isParentDeliveryServiceSelected(deliveryService)\n\t\t\t\t\t\t}\"\n\t\t\t\t\t\t:data-role=\"isParentDeliveryServiceSelected(deliveryService) ? 'ui-ears-active' : ''\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-image\">\n\t\t\t\t\t\t\t<img v-if=\"deliveryService.logo\" :src=\"deliveryService.logo.src\">\n\t\t\t\t\t\t\t<div v-else-if=\"!isNoDeliveryService(deliveryService)\" class=\"salescenter-delivery-method-image-blank\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-info\">\n\t\t\t\t\t\t\t<div v-if=\"deliveryService.title\" class=\"salescenter-delivery-method-title\">{{deliveryService.title}}</div>\n\t\t\t\t\t\t\t<div v-else=\"deliveryService.title\" class=\"salescenter-delivery-method-title\"></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-delivery-method-name\">{{deliveryService.name}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div @click=\"onAddMoreClicked\" class=\"salescenter-delivery-method-item salescenter-delivery-method-item--add\">\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-image-more\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-info\">\n\t\t\t\t\t\t\t<div class=\"salescenter-delivery-method-name\">\n\t\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_ADD_MORE}}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-show=\"areProfilesVisible\">\n\t\t\t\t<div class=\"salescenter-delivery-car-title--sm\">{{selectedParentServiceName}}: {{localize.SALE_DELIVERY_SERVICE_SELECTOR_SHIPPING_SERVICES}}</div>\n\t\t\t\t<div ref=\"delivery-profiles\" class=\"salescenter-delivery-car salescenter-delivery-car--ya-delivery\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-for=\"(profile, index) in selectedParentServiceProfiles\"\n\t\t\t\t\t\t@click=\"onDeliveryServiceChanged(profile)\"\n\t\t\t\t\t\t:class=\"getProfileClass(profile)\"\n\t\t\t\t\t\t:data-role=\"selectedDeliveryService.id == profile.id ? 'ui-ears-active' : ''\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<div v-show=\"isServiceProfitable(profile)\" class=\"salescenter-delivery-car-lable\">\n\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_PROFITABLE}}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-car-container\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tclass=\"salescenter-delivery-car-image\"\n\t\t\t\t\t\t\t\t:style=\"getProfileLogoStyle(profile.logo)\"\n\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-delivery-car-param\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-delivery-car-title\">\n\t\t\t\t\t\t\t\t\t{{profile.name}}\n\t\t\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t\t\tv-show=\"isVisibleProfileRestriction(profile)\"\n\t\t\t\t\t\t\t\t\t\t@mouseenter=\"onRestrictionsHintShow($event, profile)\"\n\t\t\t\t\t\t\t\t\t\t@mouseleave=\"onRestrictionsHintHide($event)\"\n\t\t\t\t\t\t\t\t\t\tclass=\"salescenter-delivery-car-title-info\"\n\t\t\t\t\t\t\t\t\t></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-delivery-car-info\">{{profile.description}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\t\t\t\t\t\t\n\t\t\t<div v-show=\"extraServicesCount > 0\" class=\"salescenter-delivery-additionally\">\n\t\t\t\t<div class=\"salescenter-delivery-additionally-options\">\n\t\t\t\t\t<component\n\t\t\t\t\t\tv-for=\"relatedService in relatedServices\"\n\t\t\t\t\t\tv-show=\"isRelatedServiceRelevant(relatedService)\"\n\t\t\t\t\t\t:is=\"relatedService.type + '-service'\"\n\t\t\t\t\t\t:key=\"relatedService.id\"\n\t\t\t\t\t\t:name=\"relatedService.name\"\n\t\t\t\t\t\t:initValue=\"getServiceValue(relatedService)\"\t\t\t\t\t\t\n\t\t\t\t\t\t:options=\"relatedService.options\"\n\t\t\t\t\t\t:style=\"getRelatedServiceStyle(relatedService)\"\n\t\t\t\t\t\t@change=\"onServiceValueChanged($event, relatedService)\"\n\t\t\t\t\t>\n\t\t\t\t\t</component>\n\t\t\t\t</div>\n\t\t\t</div>\t\t\t\n\t\t\t<div v-show=\"relatedPropsOfAddressTypeCount > 0\" class=\"salescenter-delivery-path\">\n\t\t\t\t<div\n\t\t\t\t\tv-for=\"(relatedProp, index) in relatedPropsOfAddressType\"\n\t\t\t\t\tv-show=\"relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t\t:style=\"{'margin-bottom': '30px'}\"\n\t\t\t\t\tclass=\"salescenter-delivery-path-item\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-title\">\n\t\t\t\t\t\t{{getPropName(relatedProp)}}\n\t\t\t\t\t</div>\n\t\t\t\t\t<component\n\t\t\t\t\t\t:is=\"'ADDRESS-control'\"\n\t\t\t\t\t\t:key=\"relatedProp.id\"\n\t\t\t\t\t\t:name=\"'PROPS_' + relatedProp.id\"\t\t\t\t\t\t\t\n\t\t\t\t\t\t:initValue=\"getPropValue(relatedProp)\"\n\t\t\t\t\t\t:options=\"getPropOptions(relatedProp)\"\n\t\t\t\t\t\t:isStartMarker=\"relatedProp.isAddressFrom\"\n\t\t\t\t\t\t@change=\"onPropValueChanged($event, relatedProp)\"\n\t\t\t\t\t></component>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-show=\"relatedPropsOfOtherTypeCount > 0\" class=\"salescenter-delivery-path --without-bg\">\n\t\t\t\t<div\n\t\t\t\t\tv-for=\"(relatedProp, index) in relatedPropsOfOtherTypes\"\n\t\t\t\t\tv-show=\"relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t\tclass=\"salescenter-delivery-path-item\"\n\t\t\t\t\t:style=\"{'margin-bottom': '30px'}\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-title-ordinary\">{{relatedProp.name}}</div>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-control\">\n\t\t\t\t\t\t<component\n\t\t\t\t\t\t\t:is=\"relatedProp.type + '-control'\"\n\t\t\t\t\t\t\t:key=\"relatedProp.id\"\n\t\t\t\t\t\t\t:name=\"'PROPS_' + relatedProp.id\"\n\t\t\t\t\t\t\t:initValue=\"getPropValue(relatedProp)\"\n\t\t\t\t\t\t\t:settings=\"relatedProp.settings\"\n\t\t\t\t\t\t\t:options=\"getPropOptions(relatedProp)\"\n\t\t\t\t\t\t\t@change=\"onPropValueChanged($event, relatedProp)\"\n\t\t\t\t\t\t></component>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isResponsibleUserSectionVisible\" class=\"salescenter-delivery-manager-wrapper\">\n\t\t\t\t<div class=\"ui-ctl-label-text\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_RESPONSIBLE_MANAGER}}</div>\n\t\t\t\t<div class=\"salescenter-delivery-manager\">\n\t\t\t\t\t<div class=\"salescenter-delivery-manager-avatar\" :style=\"responsibleUser.photo ? {'background-image': 'url(' + responsibleUser.photo + ')'} : {}\"></div>\n\t\t\t\t\t<div class=\"salescenter-delivery-manager-content\">\n\t\t\t\t\t\t<div @click=\"responsibleUserClicked\" class=\"salescenter-delivery-manager-name\">{{responsibleUser.name}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div @click=\"openChangeResponsibleDialog\" class=\"salescenter-delivery-manager-edit\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CHANGE_RESPONSIBLE}}</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\t\t\n\t\t\t<div v-show=\"!selectedNoDelivery\">\n\t\t\t\t<template v-if=\"calculateErrors\">\n\t\t\t\t\t<div v-for=\"(error, index) in calculateErrors\" class=\"ui-alert ui-alert-danger ui-alert-icon-danger salescenter-delivery-errors-container-alert\">\n\t\t\t\t\t\t<span class=\"ui-alert-message\">{{error}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div class=\"salescenter-delivery-bottom\">\n\t\t\t\t\t<div class=\"salescenter-delivery-bottom-row\">\t\t\t\t\t\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\">\n\t\t\t\t\t\t\t<span v-show=\"!isCalculating\" @click=\"calculate\" :class=\"calculateDeliveryPriceButtonClass\">{{isCalculated ? localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE_UPDATE : localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE}}</span>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<span v-show=\"isCalculating\" class=\"salescenter-delivery-waiter\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-waiter-alert\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_LABEL}}</span>\n\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-waiter-text\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_REQUEST_SENT}} {{selectedParentDeliveryService ? selectedParentDeliveryService.name : ''}}</span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-show=\"isSelectedDeliveryServiceAvailable && isCalculated\" class=\"salescenter-delivery-bottom-row\">\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\">\n\t\t\t\t\t\t\t<table class=\"salescenter-delivery-table-total\">\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_EXPECTED_DELIVERY_PRICE}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"estimatedDeliveryPriceFormatted\"></span>&nbsp;<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-wa salescenter-delivery-bottom-input-symbol\">\n\t\t\t\t\t\t\t\t\t\t\t<input v-model=\"enteredDeliveryPrice\" @keypress=\"isNumber($event)\" type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\">\n\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{externalSumLabel}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"externalSumFormatted\"></span><span class=\"salescenter-delivery-table-total-symbol\" v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_DELIVERY}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-show=\"deliveryPrice > 0\">\n\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"deliveryPriceFormatted\"></span>&nbsp;<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t<span v-show=\"!deliveryPrice\" class=\"salescenter-delivery-status salescenter-delivery-status--success\">\n\t\t\t\t\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE_FREE}}\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t<tr class=\"salescenter-delivery-table-total-result\">\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_TOTAL}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"totalPriceFormatted\"></span>\n\t\t\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-table-total-symbol\" v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	exports.default = deliveryselector;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX.Salescenter,BX.UI,BX,BX.Location.Core,BX.Location.Widget,BX,BX.Main,BX.Salescenter.Component.StageBlock));
//# sourceMappingURL=deliveryselector.bundle.js.map
