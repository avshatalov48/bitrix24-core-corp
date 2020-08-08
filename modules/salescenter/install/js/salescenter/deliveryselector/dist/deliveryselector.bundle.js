this.BX = this.BX || {};
(function (exports,salescenter_manager,ui_vue,main_core) {
	'use strict';

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
	    editable: {
	      required: true,
	      type: Boolean
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
	  template: "\n\t\t<div class=\"ui-ctl ui-ctl-w100\">\n\t\t\t<textarea v-if=\"isMultiline\" :disabled=\"!editable\" @input=\"onInput\" :name=\"name\" class=\"ui-ctl-element salescenter-delivery-comment-textarea\" rows=\"1\">{{value}}</textarea>\n\t\t\t<input v-else :disabled=\"!editable\" @input=\"onInput\" type=\"text\" :name=\"name\" :value=\"value\" class=\"ui-ctl-element ui-ctl-textbox\" />\n\t\t</div>\t\t\t\t\t\n\t"
	};

	var handleOutsideClick;
	var ClosableDirective = {
	  bind: function bind(el, binding, vnode) {
	    handleOutsideClick = function handleOutsideClick(e) {
	      e.stopPropagation();
	      var _binding$value = binding.value,
	          handler = _binding$value.handler,
	          exclude = _binding$value.exclude;
	      var clickedOnExcludedEl = false;
	      exclude.forEach(function (refName) {
	        if (!clickedOnExcludedEl) {
	          var excludedEl = vnode.context.$refs[refName];
	          clickedOnExcludedEl = excludedEl.contains(e.target);
	        }
	      });

	      if (!el.contains(e.target) && !clickedOnExcludedEl) {
	        vnode.context[handler]();
	      }
	    };

	    document.addEventListener('click', handleOutsideClick);
	    document.addEventListener('touchstart', handleOutsideClick);
	  },
	  unbind: function unbind() {
	    document.removeEventListener('click', handleOutsideClick);
	    document.removeEventListener('touchstart', handleOutsideClick);
	  }
	};

	function _createForOfIteratorHelper(o) { if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (o = _unsupportedIterableToArray(o))) { var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var it, normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(n); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

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
	    editable: {
	      type: Boolean,
	      default: true
	    }
	  },
	  data: function data() {
	    return {
	      enterTookPlace: false,
	      isEntering: false,
	      editMode: false,
	      value: null,
	      addressWidgetState: null
	    };
	  },
	  methods: {
	    onInputClicked: function onInputClicked() {
	      if (!this.editable) {
	        return;
	      }

	      if (this.value) {
	        this.showMap();
	      } else {
	        this.closeMap();
	      }
	    },
	    onTextClicked: function onTextClicked() {
	      if (!this.editable) {
	        return;
	      }

	      this.editMode = true;
	    },
	    onClearClicked: function onClearClicked() {
	      if (!this.editable) {
	        return;
	      }

	      this.addressWidget.address = null;
	      this.changeValue(null);
	      this.closeMap();
	    },
	    onInputFocus: function onInputFocus() {
	      this.enterTookPlace = true;
	      this.isEntering = true;
	    },
	    onInputBlur: function onInputBlur() {
	      this.isEntering = false;
	      this.closeMap();
	    },
	    onInputEnterKeyDown: function onInputEnterKeyDown() {
	      this.isEntering = false;
	    },
	    changeValue: function changeValue(newValue) {
	      this.value = newValue;
	      this.$emit('change', this.value);

	      if (this.onChangeCallback) {
	        this.onChangeCallback();
	      }
	    },
	    buildAddress: function buildAddress(value) {
	      try {
	        return new BX.Location.Core.Address(JSON.parse(value));
	      } catch (e) {
	        return null;
	      }
	    },

	    /**
	     * Map Feature Methods
	     */
	    getMap: function getMap() {
	      if (!this.addressWidget) {
	        return null;
	      }

	      var _iterator = _createForOfIteratorHelper(this.addressWidget.features),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var feature = _step.value;

	          if (feature instanceof BX.Location.Widget.MapFeature) {
	            return feature;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
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

	      this.editMode = false;
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
	      var showDangerIndicator = !this.isEntering && this.enterTookPlace && !this.value && this.addressWidgetState !== BX.Location.Widget.State.DATA_LOADING;
	      return {
	        'ui-ctl': true,
	        'ui-ctl-textbox': true,
	        'ui-ctl-danger': showDangerIndicator,
	        'ui-ctl-w100': true,
	        'ui-ctl-after-icon': true,
	        'sale-address-control-top-margin-5': this.isEditMode
	      };
	    }
	  },
	  mounted: function mounted() {
	    var _this = this;

	    if (this.initValue) {
	      this.value = this.initValue;
	    }

	    this.addressWidget = new BX.Location.Widget.Factory().createAddressWidget({
	      address: this.initValue ? this.buildAddress(this.initValue) : null,
	      mapBehavior: 'manual',
	      popupBindOptions: {
	        position: 'right'
	      },
	      mode: BX.Location.Core.ControlMode.edit,
	      useFeatures: {
	        fields: false,
	        map: true,
	        autocomplete: true
	      }
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

	      nativeOnInputKeyup.call(_this.addressWidget, e);
	    };
	    /**
	     * Subscribe to widget events
	     */


	    this.addressWidget.subscribeOnAddressChangedEvent(function (event) {
	      var data = event.getData();
	      _this.editMode = true;
	      var address = data.address;

	      if (!address.latitude || !address.longitude) {
	        _this.changeValue(null);

	        _this.closeMap();
	      } else {
	        _this.changeValue(address.toJson());

	        _this.showMap();
	      }
	    });
	    this.addressWidget.subscribeOnStateChangedEvent(function (event) {
	      var data = event.getData();
	      _this.addressWidgetState = data.state;

	      if (data.state === BX.Location.Widget.State.DATA_INPUTTING) {
	        _this.changeValue(null);

	        _this.closeMap();
	      }
	    });
	    /**
	     * Render widget
	     */

	    this.addressWidget.render({
	      inputNode: this.$refs['input-node'],
	      mapBindElement: this.$refs['input-node'],
	      controlWrapper: this.$refs['control-wrapper']
	    });
	  },
	  template: "\n\t\t<div\n\t\t\tv-closable=\"{\n\t\t\t\texclude: ['input-node'],\n\t\t\t\thandler: 'onInputBlur'\n\t\t\t}\"\n\t\t\tclass=\"ui-ctl-w100\"\n\t\t>\n\t\t\t<div :class=\"wrapperClass\" ref=\"control-wrapper\">\n\t\t\t\t<div\n\t\t\t\t\tv-show=\"isEditMode\"\n\t\t\t\t\t@click=\"onClearClicked\"\n\t\t\t\t\tclass=\"ui-ctl-after ui-ctl-icon-btn ui-ctl-icon-clear\"\n\t\t\t\t></div>\n\t\t\t\t<input\n\t\t\t\t\tv-show=\"isEditMode\"\n\t\t\t\t\t@click=\"onInputClicked\"\n\t\t\t\t\t@focus=\"onInputFocus\"\n\t\t\t\t\t@keydown.enter=\"onInputEnterKeyDown\"\n\t\t\t\t\t:disabled=\"!editable\"\n\t\t\t\t\tref=\"input-node\"\n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t/>\n\t\t\t\t<span\n\t\t\t\t\tv-show=\"!isEditMode\"\n\t\t\t\t\t@click=\"onTextClicked\"\n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox sale-address-control-path-input\"\n\t\t\t\t\tcontenteditable=\"false\"\n\t\t\t\t\tv-html=\"addressFormatted\"\n\t\t\t\t></span>\n\t\t\t\t<input v-model=\"value\" :name=\"name\" type=\"hidden\" />\n\t\t\t</div>\t\t\t\t\t\n\t\t</div>\n\t"
	};

	var CheckboxService = {
	  props: {
	    name: {
	      required: false
	    },
	    initValue: {
	      required: false
	    },
	    editable: {
	      required: true,
	      type: Boolean
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
	  template: "\n\t\t<label class=\"salescenter-delivery-selector salescenter-delivery-selector--hover salescenter-delivery-selector--checkbox\">\n\t\t\t<input :disabled=\"!editable\" @change=\"onChange\" :checked=\"value == 'Y' ? true : false\" type=\"checkbox\" value=\"Y\" />\n\t\t\t<span class=\"salescenter-delivery-selector-text\">{{name}}</span>\n\t\t</label>\n\t"
	};

	var Car = {
	  template: "\n\t\t<div class=\"salescenter-delivery-car-container\">\n\t\t\t<div class=\"salescenter-delivery-car-image salescenter-delivery-car-image--car\"></div>\n\t\t\t<div class=\"salescenter-delivery-car-param\">\n\t\t\t\t<table>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH}}</td>\n\t\t\t\t\t\t<td>130 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WIDTH}}</td>\n\t\t\t\t\t\t<td>100 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_HEIGHT}}</td>\n\t\t\t\t\t\t<td>50 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT}}</td>\n\t\t\t\t\t\t<td>20 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t</table>\n\t\t\t</div>\n\t\t</div>\n\t",
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
	    }
	  }
	};

	var Truck = {
	  template: "\n\t\t<div class=\"salescenter-delivery-car-container\">\n\t\t\t<div class=\"salescenter-delivery-car-image salescenter-delivery-car-image--truck\"></div>\n\t\t\t<div class=\"salescenter-delivery-car-param\">\n\t\t\t\t<table>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH}}</td>\n\t\t\t\t\t\t<td>3 400 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WIDTH}}</td>\n\t\t\t\t\t\t<td>1 950 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_HEIGHT}}</td>\n\t\t\t\t\t\t<td>1 600 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_LENGTH_DIMENSION_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT}}</td>\n\t\t\t\t\t\t<td>2 200 {{localize.SALE_DELIVERY_SERVICE_SELECTOR_WEIGHT_UNIT}}</td>\n\t\t\t\t\t</tr>\n\t\t\t\t</table>\n\t\t\t</div>\n\t\t</div>\n\t",
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALE_DELIVERY_SERVICE_SELECTOR_');
	    }
	  }
	};

	var YandexTaxiVehicleType = {
	  props: {
	    options: {
	      type: Array,
	      required: true
	    },
	    name: {
	      type: String,
	      required: true
	    },
	    initValue: {
	      type: String,
	      required: false
	    },
	    editable: {
	      required: true,
	      type: Boolean
	    }
	  },
	  data: function data() {
	    return {
	      value: null
	    };
	  },
	  methods: {
	    onItemClick: function onItemClick(value) {
	      if (!this.editable) {
	        return;
	      }

	      this.value = value;
	      this.$emit('change', this.value);
	    }
	  },
	  components: {
	    'express': Car,
	    'cargo': Truck
	  },
	  created: function created() {
	    this.value = this.initValue;
	  },
	  template: "\n\t\t<div class=\"salescenter-delivery-car\">\n\t\t\t<div v-for=\"option in options\" @click=\"onItemClick(option.id)\" :class=\"{'salescenter-delivery-car-item': true, 'salescenter-delivery-car-item--selected': option.id == value}\" >\n\t\t\t\t<div class=\"salescenter-delivery-car-title\">{{option.title}}</div>\n\t\t\t\t<component\n\t\t\t\t\t:is=\"option.code\"\n\t\t\t\t\t:key=\"option.code\"\n\t\t\t\t>\n\t\t\t\t</component>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var customServiceRegistry = {
	  'SERVICE_YANDEX_TAXI_VEHICLE_TYPE': YandexTaxiVehicleType
	};

	function _createForOfIteratorHelper$1(o) { if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (o = _unsupportedIterableToArray$1(o))) { var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var it, normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(n); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
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
	    'checkbox-service': CheckboxService
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
	    initResponsibleId: {
	      default: null,
	      required: false
	    },
	    initEstimatedDeliveryPrice: {
	      required: false
	    },
	    initEnteredDeliveryPrice: {
	      required: false
	    },
	    initIsCalculated: {
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
	    editable: {
	      type: Boolean,
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
	      relatedServicesOfCheckboxType: [],
	      customServices: [],
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
	      calculateErrors: []
	    };
	  },
	  methods: {
	    initialize: function initialize() {
	      var _this = this;

	      main_core.ajax.runAction('salescenter.deliveryselector.getinitializationdata', {
	        data: {
	          personTypeId: this.personTypeId,
	          responsibleId: this.initResponsibleId
	        }
	      }).then(function (result) {
	        /**
	         * Delivery services
	         */
	        _this.deliveryServices = result.data.services;

	        if (_this.deliveryServices.length > 0) {
	          var initDeliveryServiceId = _this.selectedDeliveryService ? _this.selectedDeliveryService.id : _this.initDeliveryServiceId ? _this.initDeliveryServiceId : null;

	          if (initDeliveryServiceId) {
	            var _iterator = _createForOfIteratorHelper$1(_this.deliveryServices),
	                _step;

	            try {
	              for (_iterator.s(); !(_step = _iterator.n()).done;) {
	                var deliveryService = _step.value;

	                if (deliveryService.id == initDeliveryServiceId) {
	                  _this.selectedDeliveryService = deliveryService;
	                  break;
	                }
	              }
	            } catch (err) {
	              _iterator.e(err);
	            } finally {
	              _iterator.f();
	            }
	          }

	          if (!_this.selectedDeliveryService) {
	            _this.selectedDeliveryService = _this.deliveryServices[0];
	          }
	        }
	        /**
	         * Related props
	         */


	        var relatedProps = result.data.properties;
	        /**
	         * Setting default values to related props
	         */

	        var _iterator2 = _createForOfIteratorHelper$1(relatedProps),
	            _step2;

	        try {
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            var relatedProp = _step2.value;
	            var initValue = null;

	            if (_this.initRelatedPropsValues && _this.initRelatedPropsValues.hasOwnProperty(relatedProp.id)) {
	              initValue = _this.initRelatedPropsValues[relatedProp.id];
	            } else if (relatedProp.initValue) {
	              initValue = relatedProp.initValue;
	            }

	            if (initValue !== null) {
	              initValue = babelHelpers.typeof(initValue) === 'object' ? JSON.stringify(initValue) : initValue;
	              ui_vue.Vue.set(_this.relatedPropsValues, relatedProp.id, initValue);
	            }
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
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

	        var _iterator3 = _createForOfIteratorHelper$1(relatedServices),
	            _step3;

	        try {
	          for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	            var relatedService = _step3.value;
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
	          _iterator3.e(err);
	        } finally {
	          _iterator3.f();
	        }

	        _this.relatedServices = relatedServices;
	        _this.relatedServicesOfCheckboxType = _this.relatedServices.filter(function (item) {
	          return item.type === 'checkbox';
	        });
	        /**
	         * Custom extra services
	         */

	        for (var component in customServiceRegistry) {
	          _this.$options.components[component] = customServiceRegistry[component];
	        }

	        _this.customServices = [];
	        var registeredComponents = Object.keys(_this.$options.components).filter(function (item) {
	          return item.startsWith('SERVICE_');
	        });

	        var _iterator4 = _createForOfIteratorHelper$1(_this.relatedServices),
	            _step4;

	        try {
	          for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	            var _relatedService = _step4.value;
	            var componentName = 'SERVICE_' + _relatedService.deliveryServiceCode + '_' + _relatedService.code;

	            if (registeredComponents.includes(componentName)) {
	              _this.customServices.push({
	                name: componentName,
	                service: _relatedService
	              });
	            }
	          }
	          /**
	           * Responsible
	           */

	        } catch (err) {
	          _iterator4.e(err);
	        } finally {
	          _iterator4.f();
	        }

	        _this.responsibleUser = result.data.responsible;
	        /**
	         * Misc
	         */

	        _this._userPageTemplate = result.data.userPageTemplate;
	        _this._deliverySettingsUrl = result.data.deliverySettingsUrl;
	        /**
	         *
	         */

	        if (_this.initEstimatedDeliveryPrice !== null) {
	          _this.estimatedDeliveryPrice = _this.initEstimatedDeliveryPrice;
	        }

	        if (_this.initEnteredDeliveryPrice !== null) {
	          _this.enteredDeliveryPrice = _this.initEnteredDeliveryPrice;
	        }

	        if (_this.initIsCalculated !== null) {
	          _this.isCalculated = _this.initIsCalculated;
	        }

	        _this.emitChange();
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
	        deliveryRelatedPropValues: this.currentRelatedPropsValues,
	        deliveryRelatedServiceValues: this.currentRelatedServicesValues,
	        deliveryResponsibleId: this.responsibleUser ? this.responsibleUser.id : null
	      });
	      main_core.ajax.runAction(this.action, {
	        data: actionData
	      }).then(function (result) {
	        var deliveryPrice = result.data.deliveryCalculationResult.price;
	        _this2.estimatedDeliveryPrice = deliveryPrice;
	        _this2.enteredDeliveryPrice = deliveryPrice;
	        _this2.calculateErrors = [];
	        calculationFinallyCallback();
	      }).catch(function (result) {
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
	      if (!this.editable) {
	        return;
	      }

	      this.selectedDeliveryService = deliveryService;
	      this.emitChange();
	    },
	    onPropValueChanged: function onPropValueChanged(event, relatedProp) {
	      ui_vue.Vue.set(this.relatedPropsValues, relatedProp.id, event);
	      this.emitChange();
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
	      return this.relatedPropsValues.hasOwnProperty(relatedProp.id) ? this.relatedPropsValues[relatedProp.id] : null;
	    },
	    getServiceValue: function getServiceValue(relatedService) {
	      return this.relatedServicesValues.hasOwnProperty(relatedService.id) ? this.relatedServicesValues[relatedService.id] : null;
	    },
	    getCustomServiceValue: function getCustomServiceValue(customService) {
	      return this.relatedServicesValues.hasOwnProperty(customService.service.id) ? this.relatedServicesValues[customService.service.id] : null;
	    },
	    onAddMoreClicked: function onAddMoreClicked() {
	      var _this3 = this;

	      if (!this.editable) {
	        return;
	      }

	      salescenter_manager.Manager.openSlider(this._deliverySettingsUrl).then(function () {
	        _this3.initialize();

	        _this3.$emit('settings-changed');
	      });
	    }
	  },
	  created: function created() {
	    this.initialize();
	  },
	  watch: {
	    enteredDeliveryPrice: function enteredDeliveryPrice(value) {
	      this.emitChange();
	    }
	  },
	  computed: {
	    state: function state() {
	      return {
	        deliveryServiceId: this.selectedDeliveryServiceId,
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
	    selectedNoDelivery: function selectedNoDelivery() {
	      return this.selectedDeliveryService && this.selectedDeliveryService['code'] === 'NO_DELIVERY';
	    },
	    isCalculatingAllowed: function isCalculatingAllowed() {
	      return this.selectedDeliveryServiceId && this.arePropValuesReady && !this.isCalculating && this.editable;
	    },
	    currentRelatedPropsValues: function currentRelatedPropsValues() {
	      var result = [];

	      if (!this.selectedDeliveryServiceId) {
	        return result;
	      }

	      var _iterator5 = _createForOfIteratorHelper$1(this.relatedProps),
	          _step5;

	      try {
	        for (_iterator5.s(); !(_step5 = _iterator5.n()).done;) {
	          var relatedProp = _step5.value;

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
	        _iterator5.e(err);
	      } finally {
	        _iterator5.f();
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

	      var _iterator6 = _createForOfIteratorHelper$1(this.relatedProps),
	          _step6;

	      try {
	        for (_iterator6.s(); !(_step6 = _iterator6.n()).done;) {
	          var relatedProp = _step6.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          if (relatedProp.required && !this.relatedPropsValues[relatedProp.id]) {
	            return false;
	          }
	        }
	      } catch (err) {
	        _iterator6.e(err);
	      } finally {
	        _iterator6.f();
	      }

	      return true;
	    },
	    currentRelatedServicesValues: function currentRelatedServicesValues() {
	      var result = [];

	      if (!this.selectedDeliveryServiceId) {
	        return result;
	      }

	      var _iterator7 = _createForOfIteratorHelper$1(this.relatedServices),
	          _step7;

	      try {
	        for (_iterator7.s(); !(_step7 = _iterator7.n()).done;) {
	          var relatedService = _step7.value;

	          if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
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
	        _iterator7.e(err);
	      } finally {
	        _iterator7.f();
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
	    extraServiceCheckboxesCount: function extraServiceCheckboxesCount() {
	      var result = 0;

	      var _iterator8 = _createForOfIteratorHelper$1(this.relatedServicesOfCheckboxType),
	          _step8;

	      try {
	        for (_iterator8.s(); !(_step8 = _iterator8.n()).done;) {
	          var relatedService = _step8.value;

	          if (!relatedService.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator8.e(err);
	      } finally {
	        _iterator8.f();
	      }

	      return result;
	    },
	    relatedPropsOfAddressTypeCount: function relatedPropsOfAddressTypeCount() {
	      var result = 0;

	      var _iterator9 = _createForOfIteratorHelper$1(this.relatedPropsOfAddressType),
	          _step9;

	      try {
	        for (_iterator9.s(); !(_step9 = _iterator9.n()).done;) {
	          var relatedProp = _step9.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator9.e(err);
	      } finally {
	        _iterator9.f();
	      }

	      return result;
	    },
	    relatedPropsOfOtherTypeCount: function relatedPropsOfOtherTypeCount() {
	      var result = 0;

	      var _iterator10 = _createForOfIteratorHelper$1(this.relatedPropsOfOtherTypes),
	          _step10;

	      try {
	        for (_iterator10.s(); !(_step10 = _iterator10.n()).done;) {
	          var relatedProp = _step10.value;

	          if (!relatedProp.deliveryServiceIds.includes(this.selectedDeliveryServiceId)) {
	            continue;
	          }

	          result++;
	        }
	      } catch (err) {
	        _iterator10.e(err);
	      } finally {
	        _iterator10.f();
	      }

	      return result;
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-delivery\">\n\t\t\t<div class=\"salescenter-delivery-header\">\n\t\t\t\t<span class=\"salescenter-delivery-header-method\">\n\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_SERVICE}}\n\t\t\t\t</span>\n\t\t\t\t\n\t\t\t\t<div\n\t\t\t\t\tv-for=\"deliveryService in deliveryServices\"\n\t\t\t\t\t@click=\"onDeliveryServiceChanged(deliveryService)\"\n\t\t\t\t\t:class=\"{'salescenter-delivery-method-item': true, 'salescenter-delivery-method-item--selected': (selectedDeliveryService && deliveryService.id == selectedDeliveryService.id) ? true : false}\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-delivery-method-image\">\n\t\t\t\t\t\t<img :src=\"deliveryService.logo\">\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-delivery-method-info\">\n\t\t\t\t\t\t<div v-if=\"deliveryService.title\" class=\"salescenter-delivery-method-title\">{{deliveryService.title}}</div>\n\t\t\t\t\t\t<div v-else=\"deliveryService.title\" class=\"salescenter-delivery-method-title\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-name\">{{deliveryService.name}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div @click=\"onAddMoreClicked\" class=\"salescenter-delivery-method-item salescenter-delivery-method-item--add\">\n\t\t\t\t\t<div class=\"salescenter-delivery-method-image-more\"></div>\n\t\t\t\t\t<div class=\"salescenter-delivery-method-info\">\n\t\t\t\t\t\t<div class=\"salescenter-delivery-method-name\">\n\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_ADD_MORE}}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<component\n\t\t\t\tv-for=\"customService in customServices\"\n\t\t\t\tv-show=\"customService.service.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t:is=\"customService.name\"\n\t\t\t\t:key=\"customService.service.id\"\n\t\t\t\t:name=\"customService.service.name\"\n\t\t\t\t:initValue=\"getCustomServiceValue(customService)\"\n\t\t\t\t:options=\"customService.service.options\"\n\t\t\t\t:editable=\"editable\"\n\t\t\t\t@change=\"onServiceValueChanged($event, customService.service)\"\n\t\t\t>\n\t\t\t</component>\n\t\t\t\n\t\t\t<div v-show=\"extraServiceCheckboxesCount > 0\" class=\"salescenter-delivery-additionally\">\n\t\t\t\t<div class=\"salescenter-delivery-additionally-options\">\n\t\t\t\t\t<component\n\t\t\t\t\t\tv-for=\"relatedService in relatedServicesOfCheckboxType\"\n\t\t\t\t\t\tv-show=\"relatedService.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t\t\t:is=\"'checkbox-service'\"\n\t\t\t\t\t\t:key=\"relatedService.id\"\n\t\t\t\t\t\t:name=\"relatedService.name\"\n\t\t\t\t\t\t:initValue=\"getServiceValue(relatedService)\"\t\t\t\t\t\t\n\t\t\t\t\t\t:options=\"relatedService.options\"\n\t\t\t\t\t\t:editable=\"editable\"\n\t\t\t\t\t\t@change=\"onServiceValueChanged($event, relatedService)\"\n\t\t\t\t\t>\n\t\t\t\t\t</component>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-show=\"relatedPropsOfAddressTypeCount > 0\" class=\"salescenter-delivery-path\">\n\t\t\t\t<div\n\t\t\t\t\tv-for=\"(relatedProp, index) in relatedPropsOfAddressType\"\n\t\t\t\t\tv-show=\"relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t\tclass=\"salescenter-delivery-path-item\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-title\">{{relatedProp.name}}</div>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-control\">\n\t\t\t\t\t\t<div :class=\"{'salescenter-delivery-path-icon': true, 'salescenter-delivery-path-icon--green': index > 0}\"></div>\n\t\t\t\t\t\t<component\n\t\t\t\t\t\t\t:is=\"'ADDRESS-control'\"\n\t\t\t\t\t\t\t:key=\"relatedProp.id\"\n\t\t\t\t\t\t\t:name=\"'PROPS_' + relatedProp.id\"\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t:initValue=\"getPropValue(relatedProp)\"\n\t\t\t\t\t\t\t:editable=\"editable\"\n\t\t\t\t\t\t\t@change=\"onPropValueChanged($event, relatedProp)\"\n\t\t\t\t\t\t></component>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<div v-show=\"relatedPropsOfOtherTypeCount > 0\" class=\"salescenter-delivery-path\">\n\t\t\t\t<div\n\t\t\t\t\tv-for=\"(relatedProp, index) in relatedPropsOfOtherTypes\"\n\t\t\t\t\tv-show=\"relatedProp.deliveryServiceIds.includes(selectedDeliveryServiceId)\"\n\t\t\t\t\tclass=\"salescenter-delivery-path-item\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-title-ordinary\">{{relatedProp.name}}</div>\n\t\t\t\t\t<div class=\"salescenter-delivery-path-control\">\n\t\t\t\t\t\t<component\n\t\t\t\t\t\t\t:is=\"relatedProp.type + '-control'\"\n\t\t\t\t\t\t\t:key=\"relatedProp.id\"\n\t\t\t\t\t\t\t:name=\"'PROPS_' + relatedProp.id\"\n\t\t\t\t\t\t\t:editable=\"editable\"\n\t\t\t\t\t\t\t:initValue=\"getPropValue(relatedProp)\"\n\t\t\t\t\t\t\t:settings=\"relatedProp.settings\"\n\t\t\t\t\t\t\t@change=\"onPropValueChanged($event, relatedProp)\"\n\t\t\t\t\t\t></component>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isResponsibleUserSectionVisible\" class=\"salescenter-delivery-manager-wrapper\">\n\t\t\t\t<div class=\"ui-ctl-label-text\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_RESPONSIBLE_MANAGER}}</div>\n\t\t\t\t<div class=\"salescenter-delivery-manager\">\n\t\t\t\t\t<div class=\"salescenter-delivery-manager-avatar\" :style=\"responsibleUser.photo ? {'background-image': 'url(' + responsibleUser.photo + ')'} : {}\"></div>\n\t\t\t\t\t<div class=\"salescenter-delivery-manager-content\">\n\t\t\t\t\t\t<div @click=\"responsibleUserClicked\" class=\"salescenter-delivery-manager-name\">{{responsibleUser.name}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-if=\"editable\" @click=\"openChangeResponsibleDialog\" class=\"salescenter-delivery-manager-edit\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CHANGE_RESPONSIBLE}}</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\t\t\n\t\t\t<div v-show=\"!selectedNoDelivery\">\n\t\t\t\t<template v-if=\"calculateErrors\">\n\t\t\t\t\t<div v-for=\"(error, index) in calculateErrors\" class=\"ui-alert ui-alert-danger ui-alert-icon-danger salescenter-delivery-errors-container-alert\">\n\t\t\t\t\t\t<span  class=\"ui-alert-message\">{{error}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div class=\"salescenter-delivery-bottom\">\n\t\t\t\t\t<div v-if=\"editable\" class=\"salescenter-delivery-bottom-row\">\t\t\t\t\t\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\">\n\t\t\t\t\t\t\t<span v-show=\"!isCalculating\" @click=\"calculate\" :class=\"calculateDeliveryPriceButtonClass\">{{isCalculated ? localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE_UPDATE : localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATE}}</span>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<span v-show=\"isCalculating\" class=\"salescenter-delivery-waiter\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-waiter-alert\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_LABEL}}</span>\n\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-waiter-text\">{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CALCULATING_REQUEST_SENT}} {{selectedDeliveryService ? selectedDeliveryService.name : ''}}</span>\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-show=\"isCalculated\" class=\"salescenter-delivery-bottom-row\">\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-delivery-bottom-col\">\n\t\t\t\t\t\t\t<table class=\"salescenter-delivery-table-total\">\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_EXPECTED_DELIVERY_PRICE}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"estimatedDeliveryPriceFormatted\"></span>&nbsp;<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-wa salescenter-delivery-bottom-input-symbol\">\n\t\t\t\t\t\t\t\t\t\t\t<input :disabled=\"!editable\" v-model=\"enteredDeliveryPrice\" @keypress=\"isNumber($event)\" type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\">\n\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{externalSumLabel}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"externalSumFormatted\"></span><span class=\"salescenter-delivery-table-total-symbol\" v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_DELIVERY_DELIVERY}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-show=\"deliveryPrice > 0\">\n\t\t\t\t\t\t\t\t\t\t\t<span v-html=\"deliveryPriceFormatted\"></span>&nbsp;<span v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t\t<span v-show=\"!deliveryPrice\" class=\"salescenter-delivery-status salescenter-delivery-status--success\">\n\t\t\t\t\t\t\t\t\t\t\t{{localize.SALE_DELIVERY_SERVICE_SELECTOR_CLIENT_DELIVERY_PRICE_FREE}}\n\t\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t\t<tr class=\"salescenter-delivery-table-total-result\">\n\t\t\t\t\t\t\t\t\t<td>{{localize.SALE_DELIVERY_SERVICE_SELECTOR_TOTAL}}:</td>\n\t\t\t\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t\t\t\t<span v-html=\"totalPriceFormatted\"></span>\n\t\t\t\t\t\t\t\t\t\t<span class=\"salescenter-delivery-table-total-symbol\" v-html=\"currencySymbol\"></span>\n\t\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t\t</table>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	exports.default = deliveryselector;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX.Salescenter,BX,BX));
//# sourceMappingURL=deliveryselector.bundle.js.map
