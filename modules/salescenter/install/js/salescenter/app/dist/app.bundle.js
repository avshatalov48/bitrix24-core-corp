this.BX = this.BX || {};
(function (exports,rest_client,ui_notification,main_loader,salescenter_component_stageBlock_send,salescenter_marketplace,salescenter_component_stageBlock_tile,Hint,Tile,salescenter_manager,catalog_productForm,currency,ui_dropdown,ui_common,ui_alerts,main_popup,main_core_events,ui_vue_vuex,ui_vue,DeliverySelector,salescenter_component_stageBlock_smsMessage,main_core,salescenter_component_stageBlock,salescenter_component_stageBlock_automation,AutomationStage,salescenter_component_stageBlock_timeline,TimeLineItem) {
	'use strict';

	DeliverySelector = DeliverySelector && DeliverySelector.hasOwnProperty('default') ? DeliverySelector['default'] : DeliverySelector;

	var ApplicationModel = /*#__PURE__*/function (_VuexBuilderModel) {
	  babelHelpers.inherits(ApplicationModel, _VuexBuilderModel);

	  function ApplicationModel() {
	    babelHelpers.classCallCheck(this, ApplicationModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ApplicationModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(ApplicationModel, [{
	    key: "getName",

	    /**
	     * @inheritDoc
	     */
	    value: function getName() {
	      return 'application';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        pages: []
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      return {
	        getPages: function getPages(state) {
	          return function () {
	            return state.pages;
	          };
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this = this;

	      return {
	        setPages: function setPages(state, payload) {
	          if (babelHelpers.typeof(payload.pages) === 'object') {
	            state.pages = payload.pages;

	            _this.saveState(state);
	          }
	        },
	        removePage: function removePage(state, payload) {
	          if (babelHelpers.typeof(payload.page) === 'object') {
	            state.pages = state.pages.filter(function (page) {
	              return !(payload.page.id && payload.page.id > 0 && page.id === payload.page.id || payload.page.landingId && payload.page.landingId > 0 && page.landingId === payload.page.landingId);
	            });

	            _this.saveState(state);
	          }
	        },
	        addPage: function addPage(state, payload) {
	          if (babelHelpers.typeof(payload.page) === 'object') {
	            state.pages.push(payload.page);

	            _this.saveState(state);
	          }
	        }
	      };
	    }
	  }]);
	  return ApplicationModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var OrderCreationModel = /*#__PURE__*/function (_VuexBuilderModel) {
	  babelHelpers.inherits(OrderCreationModel, _VuexBuilderModel);

	  function OrderCreationModel() {
	    babelHelpers.classCallCheck(this, OrderCreationModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(OrderCreationModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(OrderCreationModel, [{
	    key: "getName",

	    /**
	     * @inheritDoc
	     */
	    value: function getName() {
	      return 'orderCreation';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        currency: '',
	        processingId: null,
	        showPaySystemSettingBanner: false,
	        selectedProducts: [],
	        basket: [],
	        basketVersion: 0,
	        propertyValues: [],
	        deliveryExtraServicesValues: [],
	        expectedDelivery: null,
	        deliveryResponsibleId: null,
	        personTypeId: null,
	        deliveryId: null,
	        delivery: null,
	        isEnabledSubmit: false,
	        errors: [],
	        total: {
	          sum: null,
	          discount: null,
	          result: null,
	          resultNumeric: null
	        }
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      return {
	        resetBasket: function resetBasket(_ref) {
	          var commit = _ref.commit;
	          commit('clearBasket');
	        },
	        setCurrency: function setCurrency(_ref2, payload) {
	          var commit = _ref2.commit;
	          var currency$$1 = payload || '';
	          commit('setCurrency', currency$$1);
	        },
	        enableSubmitButton: function enableSubmitButton(_ref3, payload) {
	          var commit = _ref3.commit;
	          commit('setSubmitButtonStatus', true);
	        },
	        disableSubmitButton: function disableSubmitButton(_ref4, payload) {
	          var commit = _ref4.commit;
	          commit('setSubmitButtonStatus', false);
	        },
	        setDeliveryId: function setDeliveryId(_ref5, payload) {
	          var commit = _ref5.commit;
	          commit('setDeliveryId', payload);
	        },
	        setDelivery: function setDelivery(_ref6, payload) {
	          var commit = _ref6.commit;
	          commit('setDelivery', payload);
	        },
	        setPropertyValues: function setPropertyValues(_ref7, payload) {
	          var commit = _ref7.commit;
	          commit('setPropertyValues', payload);
	        },
	        setDeliveryExtraServicesValues: function setDeliveryExtraServicesValues(_ref8, payload) {
	          var commit = _ref8.commit;
	          commit('setDeliveryExtraServicesValues', payload);
	        },
	        setExpectedDelivery: function setExpectedDelivery(_ref9, payload) {
	          var commit = _ref9.commit;
	          commit('setExpectedDelivery', payload);
	        },
	        setDeliveryResponsibleId: function setDeliveryResponsibleId(_ref10, payload) {
	          var commit = _ref10.commit;
	          commit('setDeliveryResponsibleId', payload);
	        },
	        setPersonTypeId: function setPersonTypeId(_ref11, payload) {
	          var commit = _ref11.commit;
	          commit('setPersonTypeId', payload);
	        }
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      return {
	        getBasket: function getBasket(state) {
	          return function (index) {
	            return state.basket;
	          };
	        },
	        isAllowedSubmit: function isAllowedSubmit(state) {
	          return state.isEnabledSubmit;
	        },
	        getTotal: function getTotal(state) {
	          return state.total;
	        },
	        getDelivery: function getDelivery(state) {
	          return state.delivery;
	        },
	        getDeliveryId: function getDeliveryId(state) {
	          return state.deliveryId;
	        },
	        getPropertyValues: function getPropertyValues(state) {
	          return state.propertyValues;
	        },
	        getDeliveryExtraServicesValues: function getDeliveryExtraServicesValues(state) {
	          return state.deliveryExtraServicesValues;
	        },
	        getExpectedDelivery: function getExpectedDelivery(state) {
	          return state.expectedDelivery;
	        },
	        getDeliveryResponsibleId: function getDeliveryResponsibleId(state) {
	          return state.deliveryResponsibleId;
	        },
	        getPersonTypeId: function getPersonTypeId(state) {
	          return state.personTypeId;
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      return {
	        setBasket: function setBasket(state, payload) {
	          state.basket = payload;
	        },
	        setTotal: function setTotal(state, payload) {
	          state.total = Object.assign(state.total, payload);
	        },
	        clearBasket: function clearBasket(state, payload) {
	          state.basket = [];
	          state.basketVersion++;
	        },
	        setErrors: function setErrors(state, payload) {
	          state.errors = payload;
	        },
	        setDeliveryId: function setDeliveryId(state, deliveryId) {
	          state.deliveryId = deliveryId;
	        },
	        setDelivery: function setDelivery(state, delivery) {
	          state.delivery = delivery;
	        },
	        setPropertyValues: function setPropertyValues(state, propertyValues) {
	          state.propertyValues = propertyValues;
	        },
	        setDeliveryExtraServicesValues: function setDeliveryExtraServicesValues(state, deliveryExtraServicesValues) {
	          state.deliveryExtraServicesValues = deliveryExtraServicesValues;
	        },
	        setExpectedDelivery: function setExpectedDelivery(state, expectedDelivery) {
	          state.expectedDelivery = expectedDelivery;
	        },
	        setDeliveryResponsibleId: function setDeliveryResponsibleId(state, deliveryResponsibleId) {
	          state.deliveryResponsibleId = deliveryResponsibleId;
	        },
	        clearErrors: function clearErrors(state) {
	          state.errors = [];
	        },
	        setProcessingId: function setProcessingId(state, payload) {
	          state.processingId = payload;
	        },
	        setCurrency: function setCurrency(state, payload) {
	          state.currency = payload;
	        },
	        setPersonTypeId: function setPersonTypeId(state, payload) {
	          state.personTypeId = payload;
	        },
	        showBanner: function showBanner(state) {
	          state.showPaySystemSettingBanner = true;
	        },
	        hideBanner: function hideBanner(state) {
	          state.showPaySystemSettingBanner = false;
	        },
	        enableSubmit: function enableSubmit(state) {
	          state.isEnabledSubmit = true;
	        },
	        disableSubmit: function disableSubmit(state) {
	          state.isEnabledSubmit = false;
	        }
	      };
	    }
	  }]);
	  return OrderCreationModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var config = Object.freeze({
	  databaseConfig: {
	    name: 'salescenter.app'
	  },
	  templateName: 'bx-salescenter-app',
	  templateAddPaymentName: 'bx-salescenter-app-add-payment',
	  templateAddPaymentProductName: 'bx-salescenter-app-add-payment-product',
	  templateAddPaymentBySms: 'bx-salescenter-app-add-payment-by-sms',
	  templateAddPaymentBySmsItem: 'bx-salescenter-app-add-payment-by-sms-item',
	  moduleId: 'salescenter'
	});

	var MixinTemplatesType = {
	  data: function data() {
	    return {
	      editable: true
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.$root.$on("on-change-editable", function (value) {
	      _this.editable = value;
	    });
	  }
	};

	var Send = {
	  props: {
	    allowed: {
	      type: Boolean,
	      required: true
	    },
	    resend: {
	      type: Boolean,
	      required: true
	    }
	  },
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'send-mode-enabled-block': salescenter_component_stageBlock_send.SendModeEnabled,
	    'send-mode-disabled-block': salescenter_component_stageBlock_send.SendModeDisabled
	  },
	  computed: {
	    classes: function classes() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled': this.allowed === false,
	        'salescenter-app-payment-by-sms-item': true,
	        'salescenter-app-payment-by-sms-item-send': true
	      };
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: ''
	      };
	    }
	  },
	  methods: {
	    openWhatClientSee: function openWhatClientSee(event) {
	      BX.Salescenter.Manager.openWhatClientSee(event);
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"classes\"\n\t\t\t:config=\"configForBlock\"\n\t\t>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<send-mode-enabled-block\t\t\t\tv-if=\"allowed\"\n\t\t\t\t\t:resend=\"resend\" \n\t\t\t\t\tv-on:stage-block-send-mode-enabled-send=\"onSend\"\n\t\t\t\t\tv-on:stage-block-send-mode-enabled-see-client=\"openWhatClientSee\"\n\t\t\t\t/>\n\t\t\t\t<send-mode-disabled-block \t\t\t\tv-else\n\t\t\t\t\t:resend=\"resend\" \n\t\t\t\t\tv-on:stage-block-send-mode-disabled-send=\"onSend\"\n\t\t\t\t/>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t"
	};

	var TileCollectionMixins = {
	  components: {
	    'label-block': salescenter_component_stageBlock_tile.Label,
	    'tile-label-block': salescenter_component_stageBlock_tile.TileLabel,
	    'tile-hint-img-block': salescenter_component_stageBlock_tile.TileHintImg,
	    'tile-hint-plus-block': salescenter_component_stageBlock_tile.TileLabelPlus,
	    'tile-hint-img-caption-block': salescenter_component_stageBlock_tile.TileHintImgCaption,
	    'tile-hint-background-block': salescenter_component_stageBlock_tile.TileHintBackground,
	    'tile-hint-background-caption-block': salescenter_component_stageBlock_tile.TileHintBackgroundCaption
	  },
	  methods: {
	    getCollectionTile: function getCollectionTile() {
	      return this.tiles;
	    },
	    getCollectionTileByFilter: function getCollectionTileByFilter(filter) {
	      var map = new Map();
	      var collection = this.getCollectionTile();

	      if (filter.hasOwnProperty('type') && filter.type.length > 0) {
	        collection.forEach(function (item, index) {
	          if (filter.type === item.getType()) {
	            map.set(index, item);
	          }
	        });
	      } else {
	        collection.forEach(function (item, index) {
	          return map.set(index, item);
	        });
	      }

	      return map;
	    },
	    hasTileOfferFromCollection: function hasTileOfferFromCollection() {
	      var map = this.getCollectionTileByFilter({
	        type: Tile.Offer.type()
	      });
	      return map.size > 0;
	    },
	    hasTileMoreFromCollection: function hasTileMoreFromCollection() {
	      var map = this.getCollectionTileByFilter({
	        type: Tile.More.type()
	      });
	      return map.size > 0;
	    },
	    getTileOfferFromCollection: function getTileOfferFromCollection() {
	      var map = this.getCollectionTileByFilter({
	        type: Tile.Offer.type()
	      });
	      var result = {};
	      map.forEach(function (item, inx) {
	        result = {
	          index: inx,
	          tile: item
	        };
	        return false;
	      });
	      return result;
	    },
	    getTileMoreFromCollection: function getTileMoreFromCollection() {
	      var map = this.getCollectionTileByFilter({
	        type: Tile.More.type()
	      });
	      var result = {};
	      map.forEach(function (item, inx) {
	        result = {
	          index: inx,
	          tile: item
	        };
	        return false;
	      });
	      return result;
	    },
	    getTileByIndex: function getTileByIndex(index) {
	      var tile = null;
	      this.getCollectionTileByFilter({}).forEach(function (item, inx) {
	        if (index === inx) {
	          tile = item;
	        }
	      });
	      return tile;
	    },
	    openSlider: function openSlider(inx) {
	      var _this = this;

	      var slider = new salescenter_marketplace.AppSlider();
	      var tile = this.getTileByIndex(inx);
	      slider.openAppLocal(tile, this.getOptionSlider);
	      slider.subscribe(salescenter_marketplace.EventTypes.AppSliderSliderClose, function (e) {
	        return _this.$emit('on-tile-slider-close', {
	          data: e.data
	        });
	      });
	    },
	    isControlTile: function isControlTile(tile) {
	      return [Tile.More.type(), Tile.Offer.type()].includes(tile.getType());
	    },
	    showHint: function showHint(inx, e) {
	      var event = e.data.event;
	      var tile = this.getTileByIndex(inx);
	      this.popup = new Hint.Popup();
	      this.popup.show(event.target, tile.info);
	    },
	    hideHint: function hideHint() {
	      if (this.popup) {
	        this.popup.hide();
	      }
	    }
	  },
	  computed: {
	    getOptionSlider: function getOptionSlider() {
	      return {
	        width: 1000
	      };
	    }
	  }
	};

	var Uninstalled = {
	  props: {
	    tiles: {
	      type: Array,
	      required: true
	    }
	  },
	  mixins: [TileCollectionMixins],
	  template: "\t\t\n\t\t<div>\n\t\t\t<template v-for=\"(tile, index) in tiles\">\n\t\t\t\t<tile-hint-background-caption-block\tv-if=\"tile.img.length > 0 && tile.showTitle\"\n\t\t\t\t\t:src=\"tile.img\"\n\t\t\t\t\t:name=\"tile.name\"\n\t\t\t\t\t:caption=\"tile.name\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-click=\"openSlider(index)\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-mouseenter=\"showHint(index, $event)\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-mouseleave=\"hideHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-hint-background-block\t\tv-else-if=\"tile.img.length > 0\"\n\t\t\t\t\t:src=\"tile.img\"\n\t\t\t\t\t:name=\"tile.name\"\n\t\t\t\t\tv-on:tile-hint-bg-on-click=\"openSlider(index)\"\n\t\t\t\t\tv-on:tile-label-bg-hint-on-mouseenter=\"showHint(index, $event)\"\n\t\t\t\t\tv-on:tile-label-bg-hint-on-mouseleave=\"hideHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-hint-plus-block\t\t\tv-else \n\t\t\t\t\t:name=\"tile.name\" \n\t\t\t\t\tv-on:tile-label-plus-on-click=\"openSlider(index)\"\n\t\t\t\t/> \n\t\t\t</template>\n\t\t</div>\n\t"
	};

	var Installed = {
	  props: {
	    tiles: {
	      type: Array,
	      required: true
	    }
	  },
	  mixins: [TileCollectionMixins],
	  template: "\t\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text\"\n\t\t\t\t\tv-for=\"(tile, index) in tiles\"\n\t\t\t\t\tv-if=\"isControlTile(tile) === false\"\n\t\t\t\t\t:name=\"tile.name\" \n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(index)\"\n\t\t\t\t/>\n\t\t\t\t<br>\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text-add\"\n\t\t\t\t\tv-if=\"hasTileOfferFromCollection() === true\"\n\t\t\t\t\t:name=\"getTileOfferFromCollection().tile.name\"\n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(getTileOfferFromCollection().index)\"\n\t\t\t\t/>\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text-add\"\n\t\t\t\t\tv-if=\"hasTileMoreFromCollection() === true\"\n\t\t\t\t\t:name=\"getTileMoreFromCollection().tile.name\"\n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(getTileMoreFromCollection().index)\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var StageMixin = {
	  computed: {
	    statusClassMixin: function statusClassMixin() {
	      return {
	        'salescenter-app-payment-by-sms-item': true,
	        'salescenter-app-payment-by-sms-item-current': this.status === salescenter_component_stageBlock.StatusTypes.current,
	        'salescenter-app-payment-by-sms-item-disabled': this.status === salescenter_component_stageBlock.StatusTypes.disabled
	      };
	    },
	    containerClassMixin: function containerClassMixin() {
	      return {
	        'salescenter-app-payment-by-sms-item-container': true
	      };
	    },
	    counterCheckedMixin: function counterCheckedMixin() {
	      return this.status === salescenter_component_stageBlock.StatusTypes.complete;
	    }
	  },
	  methods: {
	    onSliderClose: function onSliderClose(e) {
	      this.$emit('on-stage-tile-collection-slider-close', e);
	    }
	  }
	};

	var Cashbox = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    },
	    tiles: {
	      type: Array,
	      required: true
	    },
	    installed: {
	      type: Boolean,
	      required: true
	    },
	    titleItems: {
	      type: Array
	    },
	    initialCollapseState: {
	      type: Boolean,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'tile-collection-installed-block': Installed,
	    'tile-collection-uninstalled-block': Uninstalled
	  },
	  computed: {
	    statusClass: function statusClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false
	      };
	    },
	    title: function title() {
	      return this.installed === true ? main_core.Loc.getMessage('SALESCENTER_CASHBOX_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_TITLE');
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleItems: this.installed ? this.titleItems : [],
	        installed: this.installed,
	        collapsible: true,
	        checked: this.counterCheckedMixin,
	        showHint: !this.installed,
	        initialCollapseState: this.initialCollapseState
	      };
	    }
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openHowToConfigCashBox(e);
	    },
	    saveCollapsedOption: function saveCollapsedOption(option) {
	      this.$emit('on-save-collapsed-option', 'cashbox', option);
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-tile-slider-close=\"onSliderClose\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template v-slot:block-hint-title>".concat(main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_SETTINGS_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<tile-collection-uninstalled-block \t:tiles=\"tiles\" v-if=\"!installed\"/>\n\t\t\t\t\t<tile-collection-installed-block :tiles=\"tiles\" v-on:on-tile-slider-close=\"onSliderClose\" v-else />\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	ui_vue.Vue.component(config.templateAddPaymentProductName, {
	  /**
	   * @emits 'changeBasketItem' {index: number, fields: object}
	   * @emits 'refreshBasket'
	   * @emits 'removeItem' {index: number}
	   */
	  props: ['basketItem', 'basketItemIndex', 'countItems', 'selectedProductIds'],
	  mixins: [MixinTemplatesType],
	  data: function data() {
	    return {
	      timer: null,
	      productSelector: null,
	      isImageAdded: false,
	      imageControlId: null
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.currencySymbol = this.$root.$app.options.currencySymbol;
	    this.defaultMeasure = {
	      name: '',
	      id: null
	    };
	    this.measures = this.$root.$app.options.measures || [];

	    if (BX.type.isArray(this.measures) && this.measures) {
	      this.measures.map(function (measure) {
	        if (measure['IS_DEFAULT'] === 'Y') {
	          _this.defaultMeasure.name = measure.SYMBOL;
	          _this.defaultMeasure.code = measure.CODE;

	          if (!_this.basketItem.measureName && !_this.basketItem.measureName) {
	            _this.changeData({
	              measureCode: _this.defaultMeasure.code,
	              measureName: _this.defaultMeasure.name
	            });
	          }
	        }
	      });
	    }

	    main_core_events.EventEmitter.subscribe('onUploaderIsInited', this.onUploaderIsInitedHandler.bind(this));
	  },
	  mounted: function mounted() {
	    this.productSelector = new BX.UI.Dropdown({
	      searchAction: "salescenter.api.order.searchProduct",
	      searchOptions: {
	        restrictedSearchIds: this.selectedProductIds
	      },
	      enableCreation: true,
	      enableCreationOnBlur: false,
	      searchResultRenderer: null,
	      targetElement: this.$refs.searchProductLine,
	      items: this.getProductSelectorItems(),
	      messages: {
	        creationLegend: this.localize.SALESCENTER_PRODUCT_CREATE,
	        notFound: this.localize.SALESCENTER_PRODUCT_NOT_FOUND
	      },
	      events: {
	        onSelect: this.selectCatalogItem.bind(this),
	        onAdd: this.showCreationForm.bind(this),
	        onReset: this.resetSearchForm.bind(this)
	      }
	    });

	    if (!this.basketItem.hasOwnProperty('id')) {
	      this.initDefaultFileControl();
	    }
	  },
	  updated: function updated() {
	    if (this.basketItem.hasOwnProperty('fileControlJs') && !this.basketItem.productId) {
	      var fileControlJs = this.basketItem.fileControlJs;

	      if (fileControlJs) {
	        fileControlJs.forEach(function (fileControlJsItem) {
	          BX.evalGlobal(fileControlJsItem);
	        });
	      }
	    }
	  },
	  directives: {
	    'bx-search-product': {
	      inserted: function inserted(element, binding) {
	        if (binding.value.selector instanceof BX.UI.Dropdown) {
	          var restrictedSearchIds = binding.value.restrictedIds;
	          binding.value.selector.targetElement = element;

	          if (BX.type.isArray(restrictedSearchIds)) {
	            binding.value.selector.searchOptions = {
	              restrictedSearchIds: restrictedSearchIds
	            };
	            binding.value.selector.items = binding.value.selector.items.filter(function (item) {
	              return !restrictedSearchIds.includes(item.id);
	            });
	          }

	          binding.value.selector.init();
	        }
	      }
	    }
	  },
	  methods: {
	    onUploaderIsInitedHandler: function onUploaderIsInitedHandler(event) {
	      if (this.basketItem.productId) {
	        return;
	      }

	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 2),
	          uploader = _event$getCompatData2[1];

	      main_core_events.EventEmitter.subscribe(uploader, 'onFileIsUploaded', this.onFileIsUploadedHandler.bind(this));
	      main_core_events.EventEmitter.subscribe(uploader, 'onFileIsDeleted', this.onFileIsDeleteHandler.bind(this));
	    },
	    onFileIsUploadedHandler: function onFileIsUploadedHandler(event) {
	      var _event$getCompatData3 = event.getCompatData(),
	          _event$getCompatData4 = babelHelpers.slicedToArray(_event$getCompatData3, 4),
	          fileId = _event$getCompatData4[0],
	          params = _event$getCompatData4[2],
	          uploader = _event$getCompatData4[3];

	      if (!this.imageControlId) {
	        this.imageControlId = uploader.CID;
	      } else if (this.imageControlId !== uploader.CID) {
	        return;
	      }

	      var images = this.basketItem.image,
	          file = params && params['file'] && params['file']['files'] && params['file']['files']['default'] ? params['file']['files']['default'] : false;

	      if (file) {
	        images.push({
	          fileId: fileId,
	          data: {
	            name: file.name,
	            type: file.type,
	            tmp_name: file.path,
	            size: file.size,
	            error: null
	          }
	        });
	        var fields = {
	          image: images
	        };
	        fields.isCreatedProduct = 'Y';
	        this.changeData(fields);
	      }

	      this.isImageAdded = true;
	    },
	    onFileIsDeleteHandler: function onFileIsDeleteHandler(event) {
	      var _event$getCompatData5 = event.getCompatData(),
	          _event$getCompatData6 = babelHelpers.slicedToArray(_event$getCompatData5, 1),
	          fileId = _event$getCompatData6[0];

	      var images = this.basketItem.image;
	      images.forEach(function (item, index, object) {
	        if (item.fileId === fileId) {
	          object.splice(index, 1);
	        }
	      });
	      var fields = {
	        image: images
	      };
	      this.changeData(fields);
	    },
	    toggleDiscount: function toggleDiscount(value) {
	      var _this2 = this;

	      this.changeData({
	        showDiscount: value
	      });
	      value === 'Y' ? setTimeout(function () {
	        return _this2.$refs.discountInput.focus();
	      }) : null;
	    },
	    changeData: function changeData(fields) {
	      this.$emit('changeBasketItem', {
	        index: this.basketItemIndex,
	        fields: fields
	      });
	    },
	    isNeedRefreshAfterChanges: function isNeedRefreshAfterChanges() {
	      if (this.isCreationMode) {
	        return this.basketItem.name.length > 0 && this.basketItem.quantity > 0 && this.basketItem.price > 0;
	      }

	      return true;
	    },
	    refreshBasket: function refreshBasket() {
	      if (this.isNeedRefreshAfterChanges()) {
	        this.$emit('refreshBasket');
	      }
	    },
	    debouncedRefresh: function debouncedRefresh(delay) {
	      var _this3 = this;

	      if (this.timer) {
	        clearTimeout(this.timer);
	      }

	      this.timer = setTimeout(function () {
	        _this3.refreshBasket();

	        _this3.timer = null;
	      }, delay);
	    },
	    changeQuantity: function changeQuantity(event) {
	      event.target.value = event.target.value.replace(/[^.\d]/g, '.');
	      var newQuantity = parseFloat(event.target.value);
	      var lastSymbol = event.target.value.substr(-1);

	      if (!newQuantity || lastSymbol === '.') {
	        return;
	      }

	      var fields = this.basketItem;
	      fields.quantity = newQuantity;
	      this.changeData(fields);
	      this.debouncedRefresh(300);
	    },
	    changeName: function changeName(event) {
	      var newName = event.target.value;
	      var fields = this.basketItem;
	      fields.name = newName;
	      this.changeData(fields);
	      this.refreshBasket();
	    },
	    changePrice: function changePrice(event) {
	      event.target.value = event.target.value.replace(/[^.,\d]/g, '');

	      if (event.target.value === '') {
	        event.target.value = 0;
	      }

	      var lastSymbol = event.target.value.substr(-1);

	      if (lastSymbol === ',') {
	        event.target.value = event.target.value.replace(',', ".");
	      }

	      var newPrice = parseFloat(event.target.value);

	      if (newPrice < 0 || lastSymbol === '.' || lastSymbol === ',') {
	        return;
	      }

	      var fields = this.basketItem;
	      fields.price = newPrice;
	      fields.discount = 0;

	      if (fields.module !== 'catalog') {
	        fields.basePrice = newPrice;
	      } else {
	        fields.isCustomPrice = 'Y';
	      }

	      this.changeData(fields);
	      this.refreshBasket();
	    },

	    /**
	     *
	     * @param discountType {string}
	     */
	    changeDiscountType: function changeDiscountType(discountType) {
	      var type = discountType === 'currency' ? 'currency' : 'percent';
	      var fields = this.basketItem;
	      fields.discountType = type;
	      fields.price = fields.basePrice;
	      fields.isCustomPrice = 'Y';
	      this.changeData(fields);
	      this.refreshBasket();
	    },
	    changeDiscount: function changeDiscount(event) {
	      var discountValue = parseFloat(event.target.value) || 0;

	      if (discountValue === parseFloat(this.basketItem.discount)) {
	        return;
	      }

	      var fields = this.basketItem;
	      fields.discount = discountValue;
	      fields.price = fields.basePrice;
	      fields.isCustomPrice = 'Y';
	      this.changeData(fields);
	      this.refreshBasket();
	    },
	    showCreationForm: function showCreationForm() {
	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      var value = this.productSelector.targetElement.value;
	      var fields = {
	        productId: '',
	        quantity: 1,
	        module: null,
	        sort: this.basketItemIndex,
	        isCreatedProduct: 'Y',
	        name: value,
	        isCustomPrice: 'Y',
	        discountInfos: [],
	        errors: []
	      };

	      if (!this.isImageAdded) {
	        this.initDefaultFileControl(fields);
	      } else {
	        this.changeData(fields);
	      }

	      this.productSelector.destroyPopupWindow();
	    },
	    resetSearchForm: function resetSearchForm() {
	      var _this4 = this;

	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      this.productSelector.targetElement.value = '';
	      this.productSelector.updateItemsList(this.getProductSelectorItems());
	      var fields = {
	        productId: '',
	        code: null,
	        module: null,
	        name: '',
	        quantity: 0,
	        price: 0,
	        basePrice: 0,
	        discount: 0,
	        discountInfos: [],
	        image: [],
	        errors: ['SALE_BASKET_ITEM_NAME']
	      };
	      this.initDefaultFileControl(fields).then(function () {
	        _this4.refreshBasket();
	      });
	      this.isImageAdded = false;
	      this.imageControlId = null;
	      this.productSelector.destroyPopupWindow();
	    },
	    hideCreationForm: function hideCreationForm() {
	      var _this5 = this;

	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      var fields = {
	        isCreatedProduct: 'N',
	        productId: '',
	        name: '',
	        quantity: 0,
	        price: 0,
	        basePrice: 0,
	        discount: 0,
	        discountInfos: [],
	        image: [],
	        errors: []
	      };
	      this.initDefaultFileControl(fields).then(function () {
	        _this5.refreshBasket();
	      });
	      this.isImageAdded = false;
	      this.imageControlId = null;
	    },
	    removeItem: function removeItem() {
	      this.$emit('removeItem', {
	        index: this.basketItemIndex
	      });
	    },
	    selectCatalogItem: function selectCatalogItem(sender, item) {
	      var _this6 = this;

	      this.$root.$app.startProgress();

	      if (!sender instanceof BX.UI.Dropdown) {
	        return true;
	      }

	      if (item.id === undefined || parseInt(item.id) <= 0) {
	        return true;
	      }

	      var quantity = item.attributes && item.attributes.measureRatio ? item.attributes.measureRatio : item.quantity;
	      var fields = {
	        name: item.title,
	        productId: item.id,
	        sort: this.basketItemIndex,
	        module: 'catalog',
	        isCustomPrice: 'N',
	        discount: 0,
	        quantity: quantity,
	        isCreatedProduct: 'N',
	        image: []
	      };

	      if (this.basketItemIndex.productId !== item.id) {
	        fields.encodedFields = null;
	        fields.discount = 0;
	        fields.isCustomPrice = 'N';
	      }

	      BX.ajax.runAction("salescenter.api.order.getFileControl", {
	        data: {
	          productId: item.id
	        }
	      }).then(function (result) {
	        var data = BX.prop.getObject(result, "data", {});

	        if (data.fileControl) {
	          var fileControl = BX.processHTML(data.fileControl);
	          fields.fileControlHtml = fileControl['HTML'];
	        }

	        _this6.changeData(fields);

	        _this6.$emit('refreshBasket');
	      });
	      sender.destroyPopupWindow();
	    },
	    openDiscountEditor: function openDiscountEditor(e, url) {
	      if (!(window.top.BX.SidePanel && window.top.BX.SidePanel.Instance)) {
	        return;
	      }

	      window.top.BX.SidePanel.Instance.open(BX.util.add_url_param(url, {
	        "IFRAME": "Y",
	        "IFRAME_TYPE": "SIDE_SLIDER",
	        "publicSidePanel": "Y"
	      }), {
	        allowChangeHistory: false
	      });
	      e.preventDefault ? e.preventDefault() : e.returnValue = false;
	    },
	    isEmptyProductName: function isEmptyProductName() {
	      return this.basketItem.name.length === 0;
	    },
	    calculateCorrectionFactor: function calculateCorrectionFactor(quantity, measureRatio) {
	      var factoredQuantity = quantity;
	      var factoredRatio = measureRatio;
	      var correctionFactor = 1;

	      while (!(Number.isInteger(factoredQuantity) && Number.isInteger(factoredRatio))) {
	        correctionFactor *= 10;
	        factoredQuantity = quantity * correctionFactor;
	        factoredRatio = measureRatio * correctionFactor;
	      }

	      return correctionFactor;
	    },
	    incrementQuantity: function incrementQuantity() {
	      var correctionFactor = this.calculateCorrectionFactor(this.basketItem.quantity, this.basketItem.measureRatio);
	      this.basketItem.quantity = (this.basketItem.quantity * correctionFactor + this.basketItem.measureRatio * correctionFactor) / correctionFactor;
	      this.changeData(this.basketItem);
	      this.debouncedRefresh(300);
	    },
	    decrementQuantity: function decrementQuantity() {
	      if (this.basketItem.quantity > this.basketItem.measureRatio) {
	        var correctionFactor = this.calculateCorrectionFactor(this.basketItem.quantity, this.basketItem.measureRatio);
	        this.basketItem.quantity = (this.basketItem.quantity * correctionFactor - this.basketItem.measureRatio * correctionFactor) / correctionFactor;
	        this.changeData(this.basketItem);
	        this.debouncedRefresh(300);
	      }
	    },
	    showPopupMenu: function showPopupMenu(target, array, type) {
	      var _this7 = this;

	      if (!this.editable) {
	        return;
	      }

	      var menuItems = [];

	      var setItem = function setItem(ev, param) {
	        target.innerHTML = ev.target.innerHTML;

	        if (type === 'discount') {
	          _this7.changeDiscountType(param.options.type);
	        }

	        _this7.popupMenu.close();
	      };

	      if (type === 'discount') {
	        array = [];
	        array.percent = '%';
	        array.currency = this.currencySymbol;
	      }

	      if (array) {
	        for (var item in array) {
	          var text = array[item];

	          if (type === 'measures') {
	            text = array[item].SYMBOL;
	          }

	          menuItems.push({
	            text: text,
	            onclick: setItem.bind({
	              value: 'settswguy'
	            }),
	            type: type === 'discount' ? item : null
	          });
	        }
	      }

	      this.popupMenu = new main_popup.PopupMenuWindow({
	        bindElement: target,
	        items: menuItems
	      });
	      this.popupMenu.show();
	    },
	    initDefaultFileControl: function initDefaultFileControl() {
	      var _this8 = this;

	      var fields = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return this.getDefaultFileControl().then(function (fileControl) {
	        var fileControlData = BX.processHTML(fileControl);
	        fields.fileControlHtml = fileControlData['HTML'];
	        fields.fileControlJs = [];

	        for (var i in fileControlData['SCRIPT']) {
	          if (fileControlData['SCRIPT'].hasOwnProperty(i)) {
	            fields.fileControlJs.push(fileControlData['SCRIPT'][i]['JS']);
	          }
	        }

	        _this8.changeData(fields);
	      });
	    },
	    getDefaultFileControl: function getDefaultFileControl() {
	      return new Promise(function (resolve, reject) {
	        BX.ajax.runAction("salescenter.api.order.getFileControl").then(function (result) {
	          var data = BX.prop.getObject(result, "data", {
	            'fileControl': ''
	          });

	          if (data.fileControl) {
	            resolve(data.fileControl);
	          }
	        }, function (error) {
	          reject(new Error(error.errors.join('<br />')));
	        });
	      });
	    },
	    getProductSelectorItems: function getProductSelectorItems() {
	      var initialProducts = this.$root.$app.options.mostPopularProducts.map(function (item) {
	        return {
	          id: item.ID,
	          title: item.NAME,
	          quantity: item.MEASURE_RATIO,
	          module: 'salescenter'
	        };
	      });
	      var selectedProductIds = Array.isArray(this.selectedProductIds) ? this.selectedProductIds : [];
	      var productSelectorItems = initialProducts.filter(function (item) {
	        return !item.id || !selectedProductIds.includes(item.id);
	      });

	      if (productSelectorItems.length) {
	        return productSelectorItems;
	      } else {
	        return [{
	          title: '',
	          subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
	        }];
	      }
	    },
	    showProductTooltip: function showProductTooltip(e) {
	      if (!this.productTooltip) {
	        this.productTooltip = new main_popup.Popup({
	          bindElement: e.target,
	          maxWidth: 400,
	          darkMode: true,
	          innerHTML: e.target.value,
	          animation: 'fading-slide'
	        });
	      }

	      this.productTooltip.setContent(e.target.value);
	      e.target.value.length > 0 ? this.productTooltip.show() : null;
	    },
	    hideProductTooltip: function hideProductTooltip() {
	      this.productTooltip ? this.productTooltip.close() : null;
	    }
	  },
	  watch: {
	    selectedProductIds: function selectedProductIds(newValue, oldValue) {
	      var newValueArray = Array.isArray(newValue) ? newValue : [];
	      var oldValueArray = Array.isArray(oldValue) ? oldValue : [];

	      if (newValueArray.join() === oldValueArray.join()) {
	        return;
	      }

	      this.productSelector.updateItemsList(this.getProductSelectorItems());
	    }
	  },
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_PRODUCT_');
	    },
	    showDiscount: function showDiscount() {
	      return this.basketItem.showDiscount === 'Y';
	    },
	    showPrice: function showPrice() {
	      return this.basketItem.discount > 0 || parseFloat(this.basketItem.price) !== parseFloat(this.basketItem.basePrice);
	    },
	    getMeasureName: function getMeasureName() {
	      return this.basketItem.measureName || this.defaultMeasure.name;
	    },
	    getMeasureCode: function getMeasureCode() {
	      return this.basketItem.measureCode || this.defaultMeasure.code;
	    },
	    getBasketFileControl: function getBasketFileControl() {
	      var fileControl = this.basketItem.fileControl,
	          html = '';

	      if (fileControl) {
	        var data = BX.processHTML(fileControl);
	        html = data['HTML'];
	      }

	      return html;
	    },
	    restrictedSearchIds: function restrictedSearchIds() {
	      var _this9 = this;

	      var restrictedSearchIds = this.selectedProductIds;

	      if (this.basketItem.module === 'catalog') {
	        restrictedSearchIds = restrictedSearchIds.filter(function (id) {
	          return id !== _this9.basketItem.productId;
	        });
	      }

	      return restrictedSearchIds;
	    },
	    isCreationMode: function isCreationMode() {
	      return this.basketItem.isCreatedProduct === 'Y';
	    },
	    isNotEnoughQuantity: function isNotEnoughQuantity() {
	      return this.basketItem.errors.includes('SALE_BASKET_AVAILABLE_QUANTITY');
	    },
	    hasPriceError: function hasPriceError() {
	      return this.basketItem.errors.includes('SALE_BASKET_ITEM_WRONG_PRICE');
	    },
	    hasNameError: function hasNameError() {
	      return this.basketItem.errors.includes('SALE_BASKET_ITEM_NAME');
	    },
	    productInputWrapperClass: function productInputWrapperClass() {
	      return {
	        'ui-ctl': true,
	        'ui-ctl-w100': true,
	        'ui-ctl-md': true,
	        'ui-ctl-after-icon': true,
	        'ui-ctl-danger': this.hasNameError
	      };
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-page-content-item\">\n\t\t\t<!--counters anr remover-->\n\t\t\t<div class=\"salescenter-app-counter\">{{basketItemIndex + 1}}</div>\n\t\t\t<div class=\"salescenter-app-remove\" @click=\"removeItem\" v-if=\"countItems > 1 && editable\"></div>\n\t\t\t<!--counters anr remover end-->\n\t\t\t\n\t\t\t<!--if isCreationMode-->\n\t\t\t<div class=\"salescenter-app-form-container\" v-if=\"!isCreationMode\">\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<!--col 1-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-prod\" style=\"flex:8\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-input\">\n\t\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_NAME}}</label>\n\t\t\t\t\t\t\t<div :class=\"productInputWrapperClass\">\n\t\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"resetSearchForm\" v-if=\"basketItem.name.length > 0 && editable\"/>\n\t\t\t\t\t\t\t\t<!--<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"removeItem\" v-if=\"countItems > 1 && editable\"/>-->\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\t\tref=\"searchProductLine\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox salescenter-app-product-search\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.name\"\n\t\t\t\t\t\t\t\t\tv-bx-search-product=\"{selector: productSelector, restrictedIds: restrictedSearchIds}\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\"\n\t\t\t\t\t\t\t\t\t:placeholder=\"localize.SALESCENTER_PRODUCT_NAME_PLACEHOLDER\" \n\t\t\t\t\t\t\t\t\t@mouseover=\"showProductTooltip(event)\"\n\t\t\t\t\t\t\t\t\t@mouseleave=\"hideProductTooltip(event)\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"hasNameError\">{{localize.SALESCENTER_PRODUCT_CHOOSE_PRODUCT}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"getBasketFileControl\" class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- loaded product -->\n\t\t\t\t\t\t\t<div v-html=\"getBasketFileControl\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- selected product -->\n\t\t\t\t\t\t\t<div v-html=\"basketItem.fileControlHtml\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 1 end-->\n\n\t\t\t\t\t<!--col 2-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}\n\t\t\t\t\t\t\t<span @click=\"showPopupMenu($event.target, measures, 'measures')\">{{ getMeasureName }}</span>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\" :class=\"isNotEnoughQuantity ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.quantity\"\n\t\t\t\t\t\t\t\t\t@change=\"changeQuantity\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter\" v-if=\"editable\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-up\" @click=\"incrementQuantity\"></div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-down\" @click=\"decrementQuantity\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"isNotEnoughQuantity\">{{localize.SALESCENTER_PRODUCT_IS_NOT_AVAILABLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 2 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 3-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\" :class=\"hasPriceError ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t:value=\"basketItem.price\"\n\t\t\t\t\t\t\t\t\t@change=\"changePrice\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 3 end-->\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<!--show discount link-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" v-if=\"editable || (!editable && showPrice)\">\n\t\t\t\t\t<div style=\"flex: 8;\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex: 2;\">\n\t\t\t\t\t\t<div v-if=\"showDiscount\" class=\"salescenter-app-collapse-link-pointer-event\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-if=\"showDiscount\">\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-hide\"  @click=\"toggleDiscount('N')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-show\"  @click=\"toggleDiscount('Y')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--show discount link end-->\n\t\t\t\t\n\t\t\t\t<!--dicount controller-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 7px\" v-if=\"showDiscount\">\n\t\t\t\t\t<div class=\"salescenter-app-form-collapse-container\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\">\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 8\"></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col  salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element\" v-html=\"basketItem.basePrice\" disabled=\"true\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t\t\tref=\"discountInput\" \n\t\t\t\t\t\t\t\t\t\t\t:value=\"basketItem.discount\"\n\t\t\t\t\t\t\t\t\t\t\t@change=\"changeDiscount\"\n\t\t\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link\" @click=\"showPopupMenu($event.target.firstChild, null, 'discount')\"><span v-html=\"basketItem.discountType === 'percent' ? '%' : currencySymbol\"></span></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 0;\" v-if=\"editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" v-for=\"discount in basketItem.discountInfos\"\">\n\t\t\t\t\t\t\t\t<span class=\"ui-text-4 ui-color-light\"> {{discount.name}}<a :href=\"discount.editPageUrl\" @click=\"openDiscountEditor(event, discount.editPageUrl)\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--dicount controller end-->\n\t\t\t\t\n\t\t\t</div>\n\t\t\t<!--endif isCreationMode-->\n\t\t\t\n\t\t\t<!--else isCreationMode-->\n\t\t\t<div class=\"salescenter-app-form-container\" v-else>\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<!--col 1-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-prod\" style=\"flex:8\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-input\">\n\t\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>\n\t\t\t\t\t\t\t<div :class=\"productInputWrapperClass\">\n\t\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"hideCreationForm\"> </button>\n\t\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\t\ttype=\"text\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox\" \n\t\t\t\t\t\t\t\t\t@change=\"changeName\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.name\"\n\t\t\t\t\t\t\t\t\t@mouseover=\"showProductTooltip(event)\"\n\t\t\t\t\t\t\t\t\t@mouseleave=\"hideProductTooltip(event)\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-tag\">{{localize.SALESCENTER_PRODUCT_NEW_LABEL}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"hasNameError\">{{localize.SALESCENTER_PRODUCT_EMPTY_PRODUCT_NAME}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- new product -->\n\t\t\t\t\t\t\t<div v-html=\"basketItem.fileControlHtml\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 1 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 2-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}\n\t\t\t\t\t\t\t<span @click=\"showPopupMenu($event.target, measures, 'measures')\">{{ getMeasureName }}</span>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.quantity\" \n\t\t\t\t\t\t\t\t\t@input=\"changeQuantity\" \n\t\t\t\t\t\t\t\t\t@change=\"refreshBasket\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter\" v-if=\"editable\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-up\" @click=\"incrementQuantity\"></div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-down\" @click=\"decrementQuantity\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 2 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 3-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\" :class=\"hasPriceError ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t:value=\"basketItem.price\"\n\t\t\t\t\t\t\t\t\t@change=\"changePrice\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 3 end-->\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<!--show discount link-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" v-if=\"editable || (!editable && showPrice)\">\n\t\t\t\t\t<div style=\"flex: 8;\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex: 2;\">\n\t\t\t\t\t\t<div v-if=\"showDiscount\" class=\"salescenter-app-collapse-link-pointer-event\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-if=\"showDiscount\">\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-hide\"  @click=\"toggleDiscount('N')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-show\"  @click=\"toggleDiscount('Y')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--show discount link end-->\n\t\t\t\t\n\t\t\t\t<!--dicount controller-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 7px\" v-if=\"showDiscount\">\n\t\t\t\t\t<div class=\"salescenter-app-form-collapse-container\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\">\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 8\"></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col  salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element\" v-html=\"basketItem.basePrice\" disabled=\"true\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t\t\tref=\"discountInput\"\n\t\t\t\t\t\t\t\t\t\t\t:value=\"basketItem.discount\"\n\t\t\t\t\t\t\t\t\t\t\t@change=\"changeDiscount\"\n\t\t\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link\" @click=\"showPopupMenu($event.target.firstChild, null, 'discount')\"><span v-html=\"basketItem.discountType === 'percent' ? '%' : currencySymbol\"></span></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 0;\" v-if=\"editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" v-for=\"discount in basketItem.discountInfos\"\">\n\t\t\t\t\t\t\t\t<span class=\"ui-text-4 ui-color-light\"> {{discount.name}} \n\t\t\t\t\t\t\t\t<a :href=\"discount.editPageUrl\" @click=\"openDiscountEditor(event, discount.editPageUrl)\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--dicount controller end-->\n\t\t\t</div>\n\t\t\t<!--endelse isCreationMode-->\n\t\t</div>\n\t"
	});

	var BasketItemAddBlock = {
	  props: [],
	  methods: {
	    refreshBasket: function refreshBasket() {
	      this.$emit('on-refresh-basket');
	    },
	    changeBasketItem: function changeBasketItem(item) {
	      this.$emit('on-change-basket-item', item);
	    },
	    addBasketItemForm: function addBasketItemForm() {
	      this.$emit('on-add-basket-item');
	    },
	    getInternalIndexByProductId: function getInternalIndexByProductId(productId) {
	      var basket = this.$store.getters['orderCreation/getBasket']();
	      return Object.keys(basket).findIndex(function (inx) {
	        return parseInt(basket[inx].productId) === parseInt(productId);
	      });
	    },
	    onAddBasketItem: function onAddBasketItem(params) {
	      var _this = this;

	      this.$store.commit('orderCreation/addBasketItem');
	      var basketItemIndex = this.countItems - 1;
	      this.$store.commit('orderCreation/updateBasketItem', {
	        index: basketItemIndex,
	        fields: params
	      });
	      var basketItem = this.order.basket[basketItemIndex];
	      if (basketItem.id === undefined || parseInt(basketItem.id) <= 0) return true;
	      var fields = {
	        name: basketItem.name,
	        productId: basketItem.id,
	        sort: basketItemIndex,
	        module: 'catalog',
	        quantity: basketItem.quantity > 0 ? basketItem.quantity : 1
	      };
	      BX.ajax.runAction("salescenter.api.order.getFileControl", {
	        data: {
	          productId: basketItem.id
	        }
	      }).then(function (result) {
	        var data = BX.prop.getObject(result, "data", {});

	        if (data.fileControl) {
	          var fileControl = BX.processHTML(data.fileControl);
	          fields.fileControlHtml = fileControl['HTML'];
	        }

	        _this.changeBasketItem({
	          index: basketItemIndex,
	          fields: fields
	        });

	        _this.refreshBasket();
	      });
	    },
	    onUpdateBasketItem: function onUpdateBasketItem(inx, fields) {
	      this.$store.dispatch('orderCreation/changeBasketItem', {
	        index: inx,
	        fields: fields
	      });
	    },

	    /*
	    * By default, basket collection contains a fake|empty item,
	    *  that is deleted when you select items from the catalog.
	    * Also, products can be added to the form and become an empty string,
	    *  while stay a item of basket collection
	    * */
	    removeEmptyItems: function removeEmptyItems() {
	      var _this2 = this;

	      var basket = this.$store.getters['orderCreation/getBasket']();
	      basket.forEach(function (item, i) {
	        if (basket[i].name === '' && basket[i].price < 1e-10) {
	          _this2.$store.dispatch('orderCreation/deleteBasketItem', {
	            index: i
	          });
	        }
	      });
	    },
	    modifyBasketItem: function modifyBasketItem(params) {
	      var productId = parseInt(params.id);

	      if (productId > 0) {
	        var inx = this.getInternalIndexByProductId(productId);

	        if (inx >= 0) {
	          this.showDialogProductExists(params);
	        } else {
	          this.removeEmptyItems();
	          this.onAddBasketItem(params);
	        }
	      }
	    },
	    showDialogProductExists: function showDialogProductExists(params) {
	      var _this3 = this;

	      this.popup = new main_popup.Popup(null, null, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this3.popup.destroy();
	          }
	        },
	        zIndex: 4000,
	        autoHide: true,
	        closeByEsc: true,
	        closeIcon: true,
	        titleBar: main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_TITLE'),
	        draggable: true,
	        resizable: false,
	        lightShadow: true,
	        cacheable: false,
	        overlay: true,
	        content: main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_TEXT').replace('#NAME#', params.name),
	        buttons: this.getButtons(params)
	      });
	      this.popup.show();
	    },
	    getButtons: function getButtons(product) {
	      var _this4 = this;

	      var buttons = [];
	      var params = product;
	      buttons.push(new BX.UI.SaveButton({
	        text: main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_OK'),
	        onclick: function onclick() {
	          var productId = parseInt(params.id);

	          var inx = _this4.getInternalIndexByProductId(productId);

	          if (inx >= 0) {
	            var item = _this4.$store.getters['orderCreation/getBasket']()[inx];

	            var fields = {
	              quantity: parseInt(item.quantity) + 1
	            };

	            _this4.onUpdateBasketItem(inx, fields);
	          }

	          _this4.popup.destroy();
	        }
	      }));
	      buttons.push(new BX.UI.CancelButton({
	        text: main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_PROD_EXIST_DLG_NO'),
	        onclick: function onclick() {
	          _this4.popup.destroy();
	        }
	      }));
	      return buttons;
	    },
	    showDialogProductSearch: function showDialogProductSearch() {
	      var _this5 = this;

	      var funcName = 'addBasketItemFromDialogProductSearch';

	      window[funcName] = function (params) {
	        return _this5.modifyBasketItem(params);
	      };

	      var popup$$1 = new BX.CDialog({
	        content_url: '/bitrix/tools/sale/product_search_dialog.php?' + //todo: 'lang='+this._settings.languageId+
	        //todo: '&LID='+this._settings.siteId+
	        '&caller=order_edit' + '&func_name=' + funcName + '&STORE_FROM_ID=0' + '&public_mode=Y',
	        height: Math.max(500, window.innerHeight - 400),
	        width: Math.max(800, window.innerWidth - 400),
	        draggable: true,
	        resizable: true,
	        min_height: 500,
	        min_width: 800,
	        zIndex: 3100
	      });
	      popup$$1.Show();
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    countItems: function countItems() {
	      return this.order.basket.length;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  template: "\n\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 1; white-space: nowrap;\">\n\t\t\t<a class=\"salescenter-app-add-item-link\" @click=\"addBasketItemForm\">\n\t\t\t\t<slot name=\"product-add-title\"></slot>\n\t\t\t</a>\n\t\t\t<a class=\"salescenter-app-add-item-link salescenter-app-add-item-link-catalog\" @click=\"showDialogProductSearch\">\n\t\t\t\t<slot name=\"product-add-from-catalog-title\"></slot>\n\t\t\t</a>\n\t\t</div>\n\t"
	};

	ui_vue.Vue.component(config.templateAddPaymentName, {
	  mixins: [MixinTemplatesType],
	  components: {
	    'basket-item-add-block': BasketItemAddBlock
	  },
	  data: function data() {
	    return {};
	  },
	  mounted: function mounted() {
	    if (parseInt(this.$root.$app.options.associatedEntityId) > 0) {
	      this.$root.$emit("on-change-editable", false);

	      if (this.productForm) {
	        this.productForm.setEditable(false);
	      }
	    }

	    if (this.productForm) {
	      var formWrapper = this.$root.$el.querySelector('.salescenter-app-form-wrapper');
	      formWrapper.appendChild(this.productForm.layout());
	    }
	  },
	  created: function created() {
	    this.refreshId = null;
	    this.currencySymbol = this.$root.$app.options.currencySymbol;
	    var defaultCurrency = this.$root.$app.options.currencyCode || '';
	    this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);

	    if (main_core.Type.isArray(this.$root.$app.options.basket)) {
	      var fields = [];
	      this.$root.$app.options.basket.forEach(function (item) {
	        fields.push(item.fields);
	      });
	      this.$store.commit('orderCreation/setBasket', fields);
	    }

	    this.productForm = new catalog_productForm.ProductForm({
	      currencySymbol: this.currencySymbol,
	      currency: defaultCurrency,
	      iblockId: this.$root.$app.options.catalogIblockId,
	      basePriceId: this.$root.$app.options.basePriceId,
	      basket: main_core.Type.isArray(this.$root.$app.options.basket) ? this.$root.$app.options.basket : [],
	      totals: this.$root.$app.options.totals,
	      taxList: this.$root.$app.options.vatList,
	      measures: this.$root.$app.options.measures,
	      showDiscountBlock: this.$root.$app.options.showProductDiscounts,
	      showTaxBlock: this.$root.$app.options.showProductTaxes
	    });
	    this.currencySymbol = this.$root.$app.options.currencySymbol;
	    var onChangeWithDebounce = main_core.Runtime.debounce(this.onBasketChange, 500, this);
	    main_core_events.EventEmitter.subscribe(this.productForm, 'ProductForm:onBasketChange', onChangeWithDebounce);

	    if (this.$root.$app.options.showPaySystemSettingBanner) {
	      this.$store.commit('orderCreation/showBanner');
	    }

	    if (main_core.Type.isArray(this.$root.$app.options.basket)) {
	      this.$store.commit('orderCreation/enableSubmit');
	    }
	  },
	  methods: {
	    onBasketChange: function onBasketChange(event) {
	      var _this = this;

	      var data = event.getData();

	      if (!main_core.Type.isArray(data.basket)) {
	        return;
	      }

	      var fields = [];
	      data.basket.forEach(function (item) {
	        fields.push(item.fields);
	      });
	      this.$store.commit('orderCreation/setBasket', fields);

	      if (data.basket.length <= 0) {
	        this.$store.commit('orderCreation/disableSubmit');
	        return;
	      }

	      this.$store.commit('orderCreation/enableSubmit');
	      var requestId = main_core.Text.getRandom(20);
	      this.refreshId = requestId;
	      BX.ajax.runAction("salescenter.api.order.refreshBasket", {
	        data: {
	          basketItems: fields
	        }
	      }).then(function (result) {
	        if (_this.refreshId !== requestId) {
	          return;
	        }

	        var data = BX.prop.getObject(result, "data", {});

	        _this.processRefreshRequest({
	          total: BX.prop.getObject(data, "total", {
	            sum: 0,
	            discount: 0,
	            result: 0,
	            resultNumeric: 0
	          }),
	          basket: BX.prop.get(data, "items", [])
	        });
	      }).catch(function (result) {
	        var data = BX.prop.getObject(result, "data", {});

	        _this.processRefreshRequest({
	          errors: BX.prop.get(result, "errors", []),
	          basket: BX.prop.get(data, "items", [])
	        });
	      });
	    },
	    processRefreshRequest: function processRefreshRequest(data) {
	      if (this.productForm) {
	        this.productForm.setData(data);

	        if (main_core.Type.isArray(data.basket)) {
	          this.$store.commit('orderCreation/setBasket', data.basket);
	        }

	        if (main_core.Type.isObject(data.total)) {
	          this.$store.commit('orderCreation/setTotal', data.total);
	        }
	      }
	    },
	    refreshBasket: function refreshBasket() {
	      var _this2 = this;

	      var timeout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 300;
	      this.$root.$app.startProgress();
	      this.$store.dispatch('orderCreation/refreshBasket', {
	        timeout: timeout,
	        onsuccess: function onsuccess() {
	          _this2.$root.$app.stopProgress();
	        }
	      });
	    },
	    changeBasketItem: function changeBasketItem(item) {
	      this.$store.dispatch('orderCreation/changeBasketItem', {
	        index: item.index,
	        fields: item.fields
	      });
	    },
	    removeItem: function removeItem(item) {
	      this.$store.dispatch('orderCreation/removeItem', {
	        index: item.index
	      });
	      this.refreshBasket();
	    },
	    addBasketItemForm: function addBasketItemForm() {
	      if (this.productForm) {
	        this.productForm.addProduct();
	      }
	    },
	    hideBanner: function hideBanner() {
	      this.$store.commit('orderCreation/hideBanner');
	      var userOptionName = this.$root.$app.options.orderCreationOption || false;
	      var userOptionKeyName = this.$root.$app.options.paySystemBannerOptionName || false;

	      if (userOptionName && userOptionKeyName) {
	        BX.userOptions.save('salescenter', userOptionName, userOptionKeyName, 'Y');
	      }
	    },
	    openControlPanel: function openControlPanel() {
	      salescenter_manager.Manager.openControlPanel();
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_');
	    },
	    total: function total() {
	      return this.order.total;
	    },
	    countItems: function countItems() {
	      return this.order.basket.length;
	    },
	    isShowedBanner: function isShowedBanner() {
	      return this.order.showPaySystemSettingBanner;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  template: "\n\t<div class=\"salescenter-app-payment-side\">\n\t\t<div class=\"salescenter-app-page-content\">\n\t\t\t<div class=\"salescenter-app-form-wrapper\"></div>\n\t\t\t<div class=\"salescenter-app-banner\" v-if=\"isShowedBanner\">\n\t\t\t\t<div class=\"salescenter-app-banner-inner\">\n\t\t\t\t\t<div class=\"salescenter-app-banner-title\">{{localize.SALESCENTER_BANNER_TITLE}}</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-content\">\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-text\">{{localize.SALESCENTER_BANNER_TEXT}}</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-btn-block\">\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect\" @click=\"openControlPanel\">{{localize.SALESCENTER_BANNER_BTN_CONFIGURE}}</button>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide\" @click=\"hideBanner\">{{localize.SALESCENTER_BANNER_BTN_HIDE}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-close\" @click=\"hideBanner\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\t\t\n\t</div>\n"
	});

	var Product = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openHowToSell(e);
	    }
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        showHint: true
	      };
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>\t\t\t\t\t\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'), "\t\n\t\t\t</template>\n\t\t\t<template v-slot:block-hint-title>").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<bx-salescenter-app-add-payment/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var DeliverySelector$1 = {
	  props: {
	    config: {
	      type: Object,
	      required: true
	    }
	  },
	  components: {
	    'delivery-selector': DeliverySelector
	  },
	  data: function data() {
	    return {
	      availableServiceIds: []
	    };
	  },
	  methods: {
	    onChange: function onChange(payload) {
	      var fromPropId = this.getAddressFromPropId();
	      var prevFrom = this.getPrevFrom(fromPropId);
	      var newFrom = this.getNewFrom(fromPropId, payload.relatedPropsValues);
	      this.$store.dispatch('orderCreation/setDelivery', payload.deliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryId', payload.deliveryServiceId);
	      this.$store.dispatch('orderCreation/setPropertyValues', payload.relatedPropsValues);
	      this.$store.dispatch('orderCreation/setDeliveryExtraServicesValues', payload.relatedServicesValues);
	      this.$store.dispatch('orderCreation/setExpectedDelivery', payload.estimatedDeliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryResponsibleId', payload.responsibleUser ? payload.responsibleUser.id : null);

	      if (prevFrom !== newFrom) {
	        this.refreshAvailableServiceIds();
	      }

	      this.$emit('change', payload);
	    },
	    getAddressFromPropId: function getAddressFromPropId() {
	      for (var propId in this.$root.$app.options.deliveryOrderPropOptions) {
	        if (this.$root.$app.options.deliveryOrderPropOptions.hasOwnProperty(propId)) {
	          if (this.$root.$app.options.deliveryOrderPropOptions[propId].hasOwnProperty('isFromAddress')) {
	            return propId;
	          }
	        }
	      }

	      return null;
	    },
	    getPrevFrom: function getPrevFrom(fromPropId) {
	      var _iterator = _createForOfIteratorHelper(this.order.propertyValues),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var prop = _step.value;

	          if (prop.id === fromPropId) {
	            return prop.value;
	          }
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }

	      return null;
	    },
	    getNewFrom: function getNewFrom(fromPropId, relatedPropsValues) {
	      var _iterator2 = _createForOfIteratorHelper(relatedPropsValues),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var prop = _step2.value;

	          if (prop.id === fromPropId) {
	            return prop.value;
	          }
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      return null;
	    },
	    onSettingsChanged: function onSettingsChanged() {
	      this.$emit('delivery-settings-changed');
	    },
	    refreshAvailableServiceIds: function refreshAvailableServiceIds() {
	      var _this = this;

	      main_core.ajax.runAction('salescenter.order.getCompatibleDeliverySystems', {
	        data: {
	          basketItems: this.config.basket ? this.config.basket : [],
	          options: {
	            sessionId: this.config.sessionId,
	            ownerTypeId: this.config.ownerTypeId,
	            ownerId: this.config.ownerId
	          },
	          deliveryServiceId: this.order.deliveryId,
	          deliveryRelatedPropValues: this.order.propertyValues,
	          deliveryRelatedServiceValues: this.order.deliveryExtraServicesValues,
	          deliveryResponsibleId: this.order.deliveryResponsibleId
	        }
	      }).then(function (result) {
	        var data = BX.prop.getObject(result, "data", {});
	        _this.availableServiceIds = data.availableServiceIds ? data.availableServiceIds : [];
	      }).catch(function (result) {
	        _this.availableServiceIds = [];
	      });
	    }
	  },
	  created: function created() {
	    this.$store.dispatch('orderCreation/setPersonTypeId', this.config.personTypeId);
	    this.refreshAvailableServiceIds();
	  },
	  computed: babelHelpers.objectSpread({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_');
	    },
	    sumTitle: function sumTitle() {
	      return main_core.Loc.getMessage('SALESCENTER_PRODUCT_PRODUCTS_PRICE');
	    },
	    productsPriceFormatted: function productsPriceFormatted() {
	      return this.order.total.result;
	    },
	    productsPrice: function productsPrice() {
	      return this.order.total.resultNumeric;
	    },
	    delivery: function delivery() {
	      return this.order.delivery;
	    },
	    deliveryFormatted: function deliveryFormatted() {
	      if (this.isDeliveryCalculated) {
	        return BX.Currency.currencyFormat(this.delivery, this.config.currency, false);
	      }
	    },
	    total: function total() {
	      if (this.productsPrice === null || this.delivery === null) {
	        return null;
	      }

	      return this.productsPrice + this.delivery;
	    },
	    totalFormatted: function totalFormatted() {
	      return BX.Currency.currencyFormat(this.total, this.config.currency, false);
	    },
	    isDeliveryCalculated: function isDeliveryCalculated() {
	      return this.order.delivery !== null;
	    },
	    actionData: function actionData() {
	      return {
	        basketItems: this.config.basket,
	        options: {
	          sessionId: this.config.sessionId,
	          ownerTypeId: this.config.ownerTypeId,
	          ownerId: this.config.ownerId
	        }
	      };
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  template: "\n\t\t<delivery-selector\n\t\t\t:editable=\"this.config.editable\"\n\t\t\t:available-service-ids=\"availableServiceIds\"\n\t\t\t:init-is-calculated=\"config.isExistingItem\"\t\t\n\t\t\t:init-estimated-delivery-price=\"config.expectedDeliveryPrice\"\t\t\n\t\t\t:init-entered-delivery-price=\"config.deliveryPrice\"\n\t\t\t:init-delivery-service-id=\"config.deliveryServiceId\"\n\t\t\t:init-related-services-values=\"config.relatedServicesValues\"\n\t\t\t:init-related-props-values=\"config.relatedPropsValues\"\n\t\t\t:init-related-props-options=\"config.relatedPropsOptions\"\n\t\t\t:init-responsible-id=\"config.responsibleId\"\n\t\t\t:person-type-id=\"config.personTypeId\"\n\t\t\t:action=\"'salescenter.api.order.refreshDelivery'\"\n\t\t\t:action-data=\"actionData\"\n\t\t\t:external-sum=\"productsPrice\"\n\t\t\t:external-sum-label=\"sumTitle\"\n\t\t\t:currency=\"config.currency\"\n\t\t\t:currency-symbol=\"config.currencySymbol\"\n\t\t\t@change=\"onChange\"\n\t\t\t@settings-changed=\"onSettingsChanged\"\n\t\t></delivery-selector>\n\t"
	};

	var DeliveryVuex = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    },
	    tiles: {
	      type: Array,
	      required: true
	    },
	    installed: {
	      type: Boolean,
	      required: true
	    },
	    initialCollapseState: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      selectedDeliveryServiceName: null
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'delivery-selector-block': DeliverySelector$1,
	    'uninstalled-delivery-block': Uninstalled
	  },
	  computed: babelHelpers.objectSpread({
	    statusClass: function statusClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false
	      };
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleName: this.selectedDeliveryServiceName,
	        installed: this.installed,
	        collapsible: true,
	        checked: this.counterCheckedMixin,
	        showHint: false
	      };
	    },
	    config: function config() {
	      var deliveryServiceId = null;

	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('deliveryServiceId')) {
	        deliveryServiceId = this.$root.$app.options.shipmentData.deliveryServiceId;
	      }

	      var responsibleId = null;

	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('responsibleId')) {
	        responsibleId = this.$root.$app.options.shipmentData.responsibleId;
	      } else {
	        responsibleId = this.$root.$app.options.assignedById;
	      }

	      var deliveryPrice = null;

	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')) {
	        deliveryPrice = this.$root.$app.options.shipmentData.deliveryPrice;
	      }

	      var expectedDeliveryPrice = null;

	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')) {
	        expectedDeliveryPrice = this.$root.$app.options.shipmentData.expectedDeliveryPrice;
	      }

	      var relatedPropsValues = {};

	      if (this.$root.$app.options.hasOwnProperty('orderPropertyValues') && !Array.isArray(this.$root.$app.options.orderPropertyValues)) {
	        relatedPropsValues = this.$root.$app.options.orderPropertyValues;
	      }

	      var relatedServicesValues = {};

	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('extraServicesValues') && !Array.isArray(this.$root.$app.options.shipmentData.extraServicesValues)) {
	        relatedServicesValues = this.$root.$app.options.shipmentData.extraServicesValues;
	      }

	      var relatedPropsOptions = {};

	      if (this.$root.$app.options.hasOwnProperty('deliveryOrderPropOptions') && !Array.isArray(this.$root.$app.options.deliveryOrderPropOptions)) {
	        relatedPropsOptions = this.$root.$app.options.deliveryOrderPropOptions;
	      }

	      var isExistingItem = parseInt(this.$root.$app.options.associatedEntityId) > 0;
	      return {
	        isExistingItem: isExistingItem,
	        personTypeId: this.$root.$app.options.personTypeId,
	        basket: this.order.basket,
	        currencySymbol: this.$root.$app.options.currencySymbol,
	        currency: this.order.currency,
	        ownerTypeId: this.$root.$app.options.ownerTypeId,
	        ownerId: this.$root.$app.options.ownerId,
	        sessionId: this.$root.$app.options.sessionId,
	        relatedPropsValues: relatedPropsValues,
	        relatedPropsOptions: relatedPropsOptions,
	        relatedServicesValues: relatedServicesValues,
	        deliveryServiceId: deliveryServiceId,
	        responsibleId: responsibleId,
	        deliveryPrice: deliveryPrice,
	        expectedDeliveryPrice: expectedDeliveryPrice,
	        editable: this.editable
	      };
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  methods: {
	    setTitleName: function setTitleName(state) {
	      this.selectedDeliveryServiceName = state.deliveryServiceName;
	    },
	    saveCollapsedOption: function saveCollapsedOption(option) {
	      this.$emit('on-save-collapsed-option', 'delivery', option);
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>".concat(main_core.Loc.getMessage('SALESCENTER_DELIVERY_BLOCK_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<template v-if=\"!installed\">\n\t\t\t\t\t\t<uninstalled-delivery-block :tiles=\"tiles\" \n\t\t\t\t\t\t\t\tv-on:on-tile-slider-close=\"onSliderClose\"/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-select\">\n\t\t\t\t\t\t\t<delivery-selector-block :config=\"config\" \n\t\t\t\t\t\t\t\tv-on:delivery-settings-changed=\"onSliderClose\"\n\t\t\t\t\t\t\t\tv-on:change=\"setTitleName\" />\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var PaySystem = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    },
	    tiles: {
	      type: Array,
	      required: true
	    },
	    installed: {
	      type: Boolean,
	      required: true
	    },
	    titleItems: {
	      type: Array
	    },
	    initialCollapseState: {
	      type: Boolean,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'tile-collection-installed-block': Installed,
	    'tile-collection-uninstalled-block': Uninstalled
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openHowToConfigDefaultPaySystem(e);
	    },
	    saveCollapsedOption: function saveCollapsedOption(option) {
	      this.$emit('on-save-collapsed-option', 'pay_system', option);
	    }
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleItems: this.installed ? this.titleItems : [],
	        installed: this.installed,
	        collapsible: true,
	        checked: this.counterCheckedMixin,
	        showHint: !this.installed,
	        initialCollapseState: this.initialCollapseState
	      };
	    },
	    statusClass: function statusClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false
	      };
	    },
	    title: function title() {
	      return this.installed === true ? main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE');
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-tile-slider-close=\"onSliderClose\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template v-slot:block-hint-title>".concat(main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<tile-collection-uninstalled-block \t:tiles=\"tiles\" v-if=\"!installed\"/>\n\t\t\t\t\t<tile-collection-installed-block :tiles=\"tiles\" v-on:on-tile-slider-close=\"onSliderClose\" v-else />\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var SmsMessage = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    },
	    manager: {
	      type: Object,
	      required: true
	    },
	    items: {
	      type: Array,
	      required: true
	    },
	    phone: {
	      type: String,
	      required: true
	    },
	    senderSettingsUrl: {
	      type: String,
	      required: true
	    },
	    editorTemplate: {
	      type: String,
	      required: true
	    },
	    editorUrl: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'sms-alert-block': salescenter_component_stageBlock_smsMessage.Alert,
	    'sms-configure-block': salescenter_component_stageBlock_smsMessage.Configure,
	    'sms-sender-list-block': salescenter_component_stageBlock_smsMessage.SenderList,
	    'sms-user-avatar-block': salescenter_component_stageBlock_smsMessage.UserAvatar,
	    'sms-message-edit-block': salescenter_component_stageBlock_smsMessage.MessageEdit,
	    'sms-message-view-block': salescenter_component_stageBlock_smsMessage.MessageView,
	    'sms-message-editor-block': salescenter_component_stageBlock_smsMessage.MessageEditor,
	    'sms-message-control-block': salescenter_component_stageBlock_smsMessage.MessageControl
	  },
	  data: function data() {
	    return {
	      phone: this.phone,
	      senders: {
	        list: this.items,
	        settings: {
	          url: this.senderSettingsUrl
	        }
	      },
	      manager: {
	        name: this.manager.name,
	        photo: this.manager.photo
	      },
	      editor: {
	        template: this.editorTemplate,
	        url: this.editorUrl
	      }
	    };
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        showHint: true
	      };
	    },
	    hasSender: function hasSender() {
	      return this.senders.list.length !== 0;
	    },
	    hasPhone: function hasPhone() {
	      return !(this.phone === '');
	    },
	    containerClass: function containerClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-container-offtop': true
	      };
	    },
	    title: function title() {
	      return main_core.Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_SMS_2').replace('#PHONE#', this.phone);
	    }
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      this.$root.$emit("on-show-company-contacts", e);
	    },
	    smsSenderConfigure: function smsSenderConfigure() {
	      var _this = this;

	      main_core.ajax.runComponentAction("bitrix:salescenter.app", "getSmsSenderList", {
	        mode: "class"
	      }).then(function (resolve) {
	        if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0) {
	          _this.resetSenderList();

	          Object.values(resolve.data).forEach(function (item) {
	            return _this.senders.list.push({
	              name: item.name,
	              id: item.id
	            });
	          });

	          var value = _this.getFirstSender();

	          _this.setSelectedSender(value);
	        }
	      });
	    },
	    resetSenderList: function resetSenderList() {
	      this.senders.list = [];
	    },
	    getFirstSender: function getFirstSender() {
	      return this.hasSender ? this.senders.list[0].id : null;
	    },
	    setSelectedSender: function setSelectedSender(value) {
	      this.$emit('stage-block-sms-send-on-change-provider', value);
	    }
	  },
	  template: "\n\t\t<stage-block-item\t\t\t\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t\tv-on:on-item-hint=\"onItemHint\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template v-slot:block-hint-title>".concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\" :class=\"containerClass\">\n\t\t\t\t\t<template v-if=\"hasSender\">\n\t\t\t\t\t\t<sms-alert-block v-if=\"hasPhone === false\">\n\t\t\t\t\t\t\t<template v-slot:sms-alert-text>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'), "</template>\n\t\t\t\t\t\t</sms-alert-block>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<sms-configure-block \n\t\t\t\t\t\t\t:url=\"senders.settings.url\"\n\t\t\t\t\t\t\tv-on:on-configure=\"smsSenderConfigure\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-slot:sms-configure-text-alert>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_NOT_CONFIGURED'), "</template>\n\t\t\t\t\t\t\t<template v-slot:sms-configure-text-setting>").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE'), "</template>\n\t\t\t\t\t\t</sms-configure-block>\n\t\t\t\t\t</template> \n\t\t\t\t\t\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms\">\n\t\t\t\t\t\t\n\t\t\t\t\t\t<sms-user-avatar-block :manager=\"manager\"/>\n\t\t\t\t\t\t\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content\">\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<sms-message-editor-block :editor=\"editor\"/>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t\t<sms-sender-list-block\n\t\t\t\t\t\t\t\t:list=\"items\"\n\t\t\t\t\t\t\t\t:selected=\"getFirstSender()\"\n\t\t\t\t\t\t\t\t:settingUrl=\"senders.settings.url\"\n\t\t\t\t\t\t\t\tv-on:on-configure=\"smsSenderConfigure\"\n\t\t\t\t\t\t\t\tv-on:on-selected=\"setSelectedSender\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<template v-slot:sms-sender-list-text-send-from>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER'), "</template>\n\t\t\t\t\t\t\t</sms-sender-list-block>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var Automation = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: String,
	      required: true
	    },
	    items: {
	      type: Array,
	      required: true
	    },
	    initialCollapseState: {
	      type: Boolean,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  data: function data() {
	    return {
	      stages: []
	    };
	  },
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'stage-item-list': salescenter_component_stageBlock_automation.StageList
	  },
	  methods: {
	    loadStageCollection: function loadStageCollection() {
	      var _this = this;

	      Object.values(this.items).forEach(function (options) {
	        return _this.stages.push(AutomationStage.Factory.create(options));
	      });
	    },
	    setStageOnOrderPaid: function setStageOnOrderPaid(e) {
	      this.$root.$app.stageOnOrderPaid = e.data;
	    },
	    saveCollapsedOption: function saveCollapsedOption(option) {
	      this.$emit('on-save-collapsed-option', 'automation', option);
	    },
	    updateSelectedStage: function updateSelectedStage(e) {
	      var newStageId = e.data;
	      this.stages.forEach(function (stage) {
	        stage.selected = stage.id === newStageId;
	      });
	    }
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        collapsible: true,
	        initialCollapseState: this.initialCollapseState,
	        titleName: this.selectedStage.name
	      };
	    },
	    selectedStage: function selectedStage() {
	      return this.stages.find(function (stage) {
	        return stage.selected;
	      });
	    }
	  },
	  created: function created() {
	    this.loadStageCollection();
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>".concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<stage-item-list \n\t\t\t\t\t\tv-on:on-choose-select-option=\"updateSelectedStage($event); setStageOnOrderPaid($event)\"\n\t\t\t\t\t\t:stages=\"stages\">\n\t\t\t\t\t\t<template v-slot:stage-list-text>").concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT'), "</template>\n\t\t\t\t\t</stage-item-list>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var TimeLine = {
	  props: {
	    timelineItems: {
	      type: Array,
	      required: true
	    }
	  },
	  components: {
	    'timeline-item-block': salescenter_component_stageBlock_timeline.TimeLineItemBlock,
	    'timeline-item-payment-block': salescenter_component_stageBlock_timeline.TimeLineItemPaymentBlock
	  },
	  methods: {
	    isPayment: function isPayment(item) {
	      return item.type === TimeLineItem.Payment.type();
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline\">\n\t\t\t<template v-for=\"(item) in timelineItems\">\n\t\t\t\t<timeline-item-payment-block\t:item=\"item\"\tv-if=\"isPayment(item)\"/>\n\t\t\t\t<timeline-item-block\t\t\t:item=\"item\" \tv-else/>\n\t\t\t</template>\n\t\t</div>\n\t"
	};

	var StageBlocksList = {
	  props: {
	    sendAllowed: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    var stages = {
	      message: {
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        items: this.$root.$app.options.contactBlock.smsSenders,
	        manager: this.$root.$app.options.contactBlock.manager,
	        phone: this.$root.$app.options.contactPhone,
	        senderSettingsUrl: this.$root.$app.urlSettingsSmsSenders,
	        editorTemplate: this.$root.$app.sendingMethodDesc.text,
	        editorUrl: this.$root.$app.orderPublicUrl
	      },
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current
	      },
	      paysystem: {
	        status: this.$root.$app.options.paySystemList.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.paySystemList.items),
	        installed: this.$root.$app.options.paySystemList.isSet,
	        titleItems: this.getTitleItems(this.$root.$app.options.paySystemList.items),
	        initialCollapseState: this.$root.$app.options.isPaySystemCollapsed ? this.$root.$app.options.isPaySystemCollapsed === 'Y' : this.$root.$app.options.paySystemList.isSet
	      },
	      cashbox: {},
	      delivery: {
	        status: this.$root.$app.options.deliveryList.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.deliveryList.items),
	        installed: this.$root.$app.options.deliveryList.isInstalled,
	        initialCollapseState: this.$root.$app.options.isDeliveryCollapsed ? this.$root.$app.options.isDeliveryCollapsed === 'Y' : this.$root.$app.options.deliveryList.isInstalled
	      }
	    };

	    if (this.$root.$app.options.cashboxList.hasOwnProperty('items')) {
	      stages.cashbox = {
	        status: this.$root.$app.options.cashboxList.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.cashboxList.items),
	        installed: this.$root.$app.options.cashboxList.isSet,
	        titleItems: this.getTitleItems(this.$root.$app.options.cashboxList.items),
	        initialCollapseState: this.$root.$app.options.isCashboxCollapsed ? this.$root.$app.options.isCashboxCollapsed === 'Y' : this.$root.$app.options.cashboxList.isSet
	      };
	    }

	    if (this.$root.$app.options.isAutomationAvailable) {
	      stages.automation = {
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        items: this.$root.$app.options.dealStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }

	    if (BX.type.isObject(this.$root.$app.options.timeline) && Object.values(this.$root.$app.options.timeline).length > 0) {
	      stages.timeline = {
	        items: this.getTimelineCollection(this.$root.$app.options.timeline)
	      };
	    }

	    return {
	      stages: stages
	    };
	  },
	  components: {
	    'send-block': Send,
	    'cashbox-block': Cashbox,
	    'product-block': Product,
	    'delivery-block': DeliveryVuex,
	    'paysystem-block': PaySystem,
	    'automation-block': Automation,
	    'sms-message-block': SmsMessage,
	    'timeline-block': TimeLine
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    hasStageTimeLine: function hasStageTimeLine() {
	      return this.stages.timeline.hasOwnProperty('items');
	    },
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    hasStageCashBox: function hasStageCashBox() {
	      return this.stages.cashbox.hasOwnProperty('tiles');
	    },
	    editableMixin: function editableMixin() {
	      return this.editable === false;
	    }
	  },
	  methods: {
	    initCounter: function initCounter() {
	      this.counter = 1;
	    },
	    getTimelineCollection: function getTimelineCollection(items) {
	      var list = [];
	      Object.values(items).forEach(function (options) {
	        return list.push(TimeLineItem.Factory.create(options));
	      });
	      return list;
	    },
	    getTileCollection: function getTileCollection(items) {
	      var tiles = [];
	      Object.values(items).forEach(function (options) {
	        return tiles.push(Tile.Factory.create(options));
	      });
	      return tiles;
	    },
	    getTitleItems: function getTitleItems(items) {
	      var result = [];
	      items.forEach(function (item) {
	        if (![Tile.More.type(), Tile.Offer.type()].includes(item.type)) {
	          result.push(item);
	        }
	      });
	      return result;
	    },
	    stageRefresh: function stageRefresh(e, type) {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "getAjaxData", {
	        mode: "class",
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          this.refreshTilesByType(response.data, type);
	        }
	      }.bind(this));
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      if (type === 'PAY_SYSTEM') {
	        this.stages.paysystem.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.paysystem.tiles = this.getTileCollection(data.items);
	        this.stages.paysystem.installed = data.isSet;
	      } else if (type === 'CASHBOX') {
	        this.stages.cashbox.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.cashbox.tiles = this.getTileCollection(data.items);
	        this.stages.cashbox.installed = data.isSet;
	        this.stages.cashbox.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'DELIVERY') {
	        this.stages.delivery.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.delivery.tiles = this.getTileCollection(data.items);
	        this.stages.delivery.installed = data.isInstalled;
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    changeProvider: function changeProvider(value) {
	      this.$root.$app.sendingMethodDesc.provider = value;
	    },
	    saveCollapsedOption: function saveCollapsedOption(type, value) {
	      BX.userOptions.save('salescenter', 'add_payment_collapse_options', type, value);
	    }
	  },
	  created: function created() {
	    this.initCounter();
	  },
	  beforeUpdate: function beforeUpdate() {
	    this.initCounter();
	  },
	  template: "\n\t\t<div>\n\t\t\t<sms-message-block \t\t\t\t\t\tv-on:stage-block-sms-send-on-change-provider=\"changeProvider\"\n\t\t\t\t:counter=\t\t\t\"counter++\"\n\t\t\t\t:status=\t\t\t\"stages.message.status\"\n\t\t\t\t:items=\t\t\t\t\"stages.message.items\"\n\t\t\t\t:manager=\t\t\t\"stages.message.manager\"\n\t\t\t\t:phone=\t\t\t\t\"stages.message.phone\"\n\t\t\t\t:senderSettingsUrl=\t\"stages.message.senderSettingsUrl\"\n\t\t\t\t:editorTemplate=\t\"stages.message.editorTemplate\"\n\t\t\t\t:editorUrl=\t\t\t\"stages.message.editorUrl\"\n\t\t\t/>\n\t\t\t\t\n\t\t\t<product-block \n\t\t\t\t:counter=\t\"counter++\"\n\t\t\t\t:status= \t\"stages.product.status\"\t\t\t\t\n\t\t\t/>\n\t\t\t\n\t\t\t<paysystem-block\t\t\t\t\t\tv-on:on-stage-tile-collection-slider-close=\"stageRefresh($event, 'PAY_SYSTEM')\"\n\t\t\t\t:counter=\t\"counter++\"\n\t\t\t\t:status=  \t\"stages.paysystem.status\"\n\t\t\t\t:tiles=  \t\"stages.paysystem.tiles\"\n\t\t\t\t:installed=\t\"stages.paysystem.installed\"\n\t\t\t\t:titleItems=\"stages.paysystem.titleItems\"\n\t\t\t\t:initialCollapseState = \"stages.paysystem.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t\t\n\t\t\t<cashbox-block \tv-if=\"hasStageCashBox\"\tv-on:on-stage-tile-collection-slider-close=\"stageRefresh($event, 'CASHBOX')\"\n\t\t\t\t:counter=\t\"counter++\"\n\t\t\t\t:status=\t\"stages.cashbox.status\"\n\t\t\t\t:tiles=\t\t\"stages.cashbox.tiles\"\n\t\t\t\t:installed=\t\"stages.cashbox.installed\"\n\t\t\t\t:titleItems=\"stages.cashbox.titleItems\"\n\t\t\t\t:initialCollapseState = \"stages.cashbox.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\t\n\t\t\t\n\t\t\t<automation-block v-if=\"hasStageAutomation\"\n\t\t\t\t:counter=\t\"counter++\"\n\t\t\t\t:status=\t\"stages.automation.status\"\n\t\t\t\t:items=\t\t\"stages.automation.items\"\n\t\t\t\t:initialCollapseState = \"stages.automation.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t\n\t\t\t<delivery-block\t\t\t\t\t\t\tv-on:on-stage-tile-collection-slider-close=\"stageRefresh($event, 'DELIVERY')\"\n\t\t\t\t:counter=\t\"counter++\"\n\t\t\t\t:status=  \t\"stages.delivery.status\"\n\t\t\t\t:tiles=  \t\"stages.delivery.tiles\"\n\t\t\t\t:installed=\t\"stages.delivery.installed\"\n\t\t\t\t:initialCollapseState = \"stages.delivery.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t\n\t\t\t<send-block\t\t\t\t\t\t\t\tv-on:stage-block-send-on-send=\"onSend\"\n\t\t\t\t:allowed=\t\"sendAllowed\" \n\t\t\t\t:resend=\t\"editableMixin\"\n\t\t\t/>\n\t\t\t\n\t\t\t<timeline-block  v-if=\"hasStageTimeLine\"\n\t\t\t\t:timelineItems= \"stages.timeline.items\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }

	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	ui_vue.Vue.component(config.templateName, {
	  mixins: [MixinTemplatesType],
	  data: function data() {
	    return {
	      isFaded: false,
	      isShowPreview: false,
	      isShowPayment: false,
	      isShowPaymentBySms: false,
	      isShowPaymentByEmail: false,
	      isShowPaymentByCash: false,
	      isShowPaymentByQr: false,
	      pageTitle: '',
	      currentPageId: null,
	      actions: [],
	      frameCheckShortTimeout: false,
	      frameCheckLongTimeout: false,
	      isPagesOpen: false,
	      isFormsOpen: false,
	      showedPageIds: [],
	      loadedPageIds: [],
	      errorPageIds: [],
	      lastAddedPages: [],
	      ordersCount: null,
	      editedPageId: null,
	      isOrderPublicUrlAvailable: null,
	      currentPageTitle: null
	    };
	  },
	  components: {
	    'deal-receiving-payment': StageBlocksList
	  },
	  created: function created() {
	    var _this = this;

	    this.$root.$on("on-show-company-contacts", function (value) {
	      _this.showCompanyContacts(value);
	    });
	    this.$root.$on('on-start-progress', function () {
	      _this.startFade();
	    });
	    this.$root.$on('on-stop-progress', function () {
	      _this.endFade();
	    });
	  },
	  updated: function updated() {
	    this.renderErrors();
	  },
	  mounted: function mounted() {
	    var _this2 = this;

	    this.createPinner();

	    if (this.$root.$app.context === 'deal') {
	      this.showPaymentBySmsForm();
	    } else {
	      this.createLoader();
	      this.$root.$app.fillPages().then(function () {
	        _this2.refreshOrdersCount();

	        _this2.openFirstPage();
	      });
	    }

	    this.isOrderPublicUrlAvailable = this.$root.$app.isOrderPublicUrlAvailable;
	    this.isOrderPublicUrlExists = this.$root.$app.isOrderPublicUrlExists;

	    if (this.$root.$app.isPaymentsLimitReached) {
	      var paymentsLimitStartNode = this.$root.$nodes.paymentsLimit;
	      var paymentsLimitNode = this.$refs['paymentsLimit'];

	      var _iterator = _createForOfIteratorHelper$1(paymentsLimitStartNode.children),
	          _step;

	      try {
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var node = _step.value;
	          paymentsLimitNode.appendChild(node);
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }

	    this.movePanels();
	  },
	  methods: {
	    startFade: function startFade() {
	      this.isFaded = true;
	    },
	    endFade: function endFade() {
	      this.isFaded = false;
	    },
	    movePanels: function movePanels() {
	      var sidepanel = this.$refs['sidebar'];
	      var leftPanel = this.$root.$nodes.leftPanel;

	      if (!leftPanel) {
	        leftPanel = this.$refs['leftSide'];
	      }

	      if (sidepanel && leftPanel) {
	        // leftPanel.appendChild(sidepanel);
	        // BX.show(sidepanel);
	        var nav = this.$refs['sidepanelNav'];
	      }
	    },
	    createPinner: function createPinner() {
	      var buttonsPanel = this.$refs['buttonsPanel'];

	      if (buttonsPanel) {
	        this.$root.$el.parentNode.appendChild(buttonsPanel);
	        new BX.UI.Pinner(buttonsPanel, {
	          fixBottom: this.$root.$app.isFrame,
	          fullWidth: this.$root.$app.isFrame
	        });
	      }
	    },
	    getActions: function getActions() {
	      var actions = [];

	      if (this.currentPage) {
	        actions = [{
	          text: this.localize.SALESCENTER_RIGHT_ACTION_COPY_URL,
	          onclick: this.copyUrl
	        }];

	        if (this.currentPage.landingId > 0) {
	          actions = [].concat(babelHelpers.toConsumableArray(actions), [{
	            text: this.localize.SALESCENTER_RIGHT_ACTION_HIDE,
	            onclick: this.hidePage
	          }]);
	        } else {
	          actions = [].concat(babelHelpers.toConsumableArray(actions), [{
	            text: this.localize.SALESCENTER_RIGHT_ACTION_DELETE,
	            onclick: this.hidePage
	          }]);
	        }
	      }

	      return [].concat(babelHelpers.toConsumableArray(actions), [{
	        text: this.localize.SALESCENTER_RIGHT_ACTION_ADD,
	        items: this.getAddPageActions()
	      }]);
	    },
	    getAddPageActions: function getAddPageActions() {
	      var _this3 = this;

	      var isWebform = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      return [{
	        text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_SITE_B24,
	        onclick: function onclick() {
	          _this3.addSite(isWebform);
	        }
	      }, {
	        text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_CUSTOM,
	        onclick: function onclick() {
	          _this3.showAddUrlPopup({
	            isWebform: isWebform === true ? 'Y' : null
	          });
	        }
	      }];
	    },
	    openFirstPage: function openFirstPage() {
	      this.isShowPayment = false;
	      this.isShowPaymentBySms = false;
	      this.isShowPreview = true;

	      if (this.pages && this.pages.length > 0) {
	        var firstWebformPage = false;
	        var pageToOpen = false;
	        this.pages.forEach(function (page) {
	          if (!pageToOpen) {
	            if (!page.isWebform) {
	              pageToOpen = page;
	            } else {
	              firstWebformPage = page;
	            }
	          }
	        });

	        if (!pageToOpen && firstWebformPage) {
	          pageToOpen = firstWebformPage;
	        }

	        if (this.currentPageId !== pageToOpen.id) {
	          this.onPageClick(pageToOpen);

	          if (pageToOpen.isWebform) {
	            this.isFormsOpen = true;
	          } else {
	            this.isPagesOpen = true;
	          }
	        } else {
	          this.currentPageId = this.pages[0].id;
	        }
	      } else {
	        this.pageTitle = null;
	        this.currentPageId = null;
	        this.setPageTitle(this.pageTitle);
	      }
	    },
	    onPageClick: function onPageClick(page) {
	      this.pageTitle = page.name;
	      this.currentPageId = page.id;
	      this.hideActionsPopup();
	      this.isShowPayment = false;
	      this.isShowPaymentBySms = false;
	      this.isShowPreview = true;
	      this.setPageTitle(this.pageTitle);

	      if (page.isFrameDenied !== true) {
	        if (!this.showedPageIds.includes(page.id)) {
	          this.startFrameCheckTimeout();
	          this.showedPageIds.push(page.id);
	        }
	      } else {
	        this.onFrameError();
	      }
	    },
	    showActionsPopup: function showActionsPopup(_ref) {
	      var target = _ref.target;
	      BX.PopupMenu.show('salescenter-app-actions', target, this.getActions(), {
	        offsetLeft: 0,
	        offsetTop: 0,
	        closeByEsc: true
	      });
	    },
	    showCompanyContacts: function showCompanyContacts(_ref2) {
	      var target = _ref2.target;
	      BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {
	        width: 1200
	      });
	    },
	    showAddPageActionPopup: function showAddPageActionPopup(_ref3) {
	      var target = _ref3.target;
	      var isWebform = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var menuId = 'salescenter-app-add-page-actions';

	      if (isWebform) {
	        menuId += '-forms';
	      }

	      BX.PopupMenu.show(menuId, target, this.getAddPageActions(isWebform), {
	        offsetLeft: target.offsetWidth + 20,
	        offsetTop: -target.offsetHeight - 15,
	        closeByEsc: true,
	        angle: {
	          position: 'left'
	        }
	      });
	    },
	    hideActionsPopup: function hideActionsPopup() {
	      BX.PopupMenu.destroy('salescenter-app-actions');
	      BX.PopupMenu.destroy('salescenter-app-add-page-actions');
	    },
	    addSite: function addSite() {
	      var _this4 = this;

	      var isWebform = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      salescenter_manager.Manager.addSitePage(isWebform).then(function (result) {
	        var newPage = result.answer.result.page || false;

	        _this4.$root.$app.fillPages().then(function () {
	          if (newPage) {
	            _this4.onPageClick(newPage);

	            _this4.lastAddedPages.push(parseInt(newPage.id));
	          } else {
	            _this4.openFirstPage();
	          }
	        });
	      });
	      this.hideActionsPopup();
	    },
	    copyUrl: function copyUrl(event) {
	      if (this.currentPage && this.currentPage.url) {
	        salescenter_manager.Manager.copyUrl(this.currentPage.url, event);
	        this.hideActionsPopup();
	      }
	    },
	    editPage: function editPage() {
	      if (this.currentPage) {
	        if (this.currentPage.landingId && this.currentPage.landingId > 0) {
	          salescenter_manager.Manager.editLandingPage(this.currentPage.landingId, this.currentPage.siteId);
	          this.hideActionsPopup();
	        } else {
	          this.showAddUrlPopup(this.currentPage);
	        }
	      }
	    },
	    hidePage: function hidePage() {
	      var _this5 = this;

	      if (this.currentPage) {
	        this.$root.$app.hidePage(this.currentPage).then(function () {
	          _this5.openFirstPage();
	        });
	        this.hideActionsPopup();
	      }
	    },
	    showAddUrlPopup: function showAddUrlPopup(newPage) {
	      var _this6 = this;

	      if (!main_core.Type.isPlainObject(newPage)) {
	        newPage = {};
	      }

	      salescenter_manager.Manager.addCustomPage(newPage).then(function (pageId) {
	        if (!_this6.isShowPreview) {
	          _this6.isShowPreview = false;
	        }

	        _this6.$root.$app.fillPages().then(function () {
	          if (pageId && (!main_core.Type.isPlainObject(newPage) || !newPage.id)) {
	            _this6.lastAddedPages.push(parseInt(pageId));
	          }

	          if (!pageId && newPage) {
	            pageId = newPage.id;
	          }

	          if (pageId) {
	            _this6.pages.forEach(function (page) {
	              if (parseInt(page.id) === parseInt(pageId)) {
	                _this6.onPageClick(page);
	              }
	            });
	          } else {
	            if (!_this6.isShowPayment || !_this6.isShowPaymentBySms) {
	              _this6.isShowPreview = true;
	            }
	          }
	        });
	      });
	      this.hideActionsPopup();
	    },
	    showPaymentForm: function showPaymentForm() {
	      this.isShowPayment = true;
	      this.isShowPaymentBySms = false;
	      this.isShowPreview = false;

	      if (this.isOrderPublicUrlAvailable) {
	        this.setPageTitle(this.localize.SALESCENTER_LEFT_PAYMENT_ADD);
	      } else {
	        this.setPageTitle(this.localize.SALESCENTER_DEFAULT_TITLE);
	      }
	    },
	    showPaymentBySmsForm: function showPaymentBySmsForm() {
	      this.isShowPayment = false;
	      this.isShowPaymentBySms = true;
	      this.isShowPreview = false;
	      this.currentPageTitle = this.$root.$app.options.title;

	      if (this.isOrderPublicUrlAvailable) {
	        var title = this.localize.SALESCENTER_LEFT_PAYMENT_BY_SMS;

	        if (this.currentPageTitle) {
	          title = this.currentPageTitle;
	        }

	        this.setPageTitle(title);
	      } else {
	        this.setPageTitle(this.localize.SALESCENTER_DEFAULT_TITLE);
	      }
	    },
	    showOrdersList: function showOrdersList() {
	      var _this7 = this;

	      this.hideActionsPopup();
	      salescenter_manager.Manager.showOrdersList({
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this7.refreshOrdersCount();
	      });
	    },
	    showOrderAdd: function showOrderAdd() {
	      var _this8 = this;

	      this.hideActionsPopup();
	      salescenter_manager.Manager.showOrderAdd({
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this8.refreshOrdersCount();
	      });
	    },
	    showCatalog: function showCatalog() {
	      this.hideActionsPopup();
	      salescenter_manager.Manager.openSlider("/saleshub/catalog/?sessionId=".concat(this.$root.$app.sessionId));
	    },
	    onFormsClick: function onFormsClick() {
	      this.isFormsOpen = !this.isFormsOpen;
	      this.hideActionsPopup();
	    },
	    openControlPanel: function openControlPanel() {
	      salescenter_manager.Manager.openControlPanel();
	      this.hideActionsPopup();
	    },
	    openHelpDesk: function openHelpDesk() {
	      this.hideActionsPopup();
	      salescenter_manager.Manager.openHowItWorks();
	    },
	    isPageSelected: function isPageSelected(page) {
	      return this.currentPage && this.isShowPreview && this.currentPage.id === page.id;
	    },
	    send: function send(event) {
	      var skipPublicMessage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'n';

	      if (!this.isAllowedSubmitButton) {
	        return;
	      }

	      if ((this.isShowPayment || this.isShowPaymentBySms) && !this.isShowStartInfo) {
	        if (this.editable) {
	          this.$root.$app.sendPayment(event.target, skipPublicMessage);
	        } else {
	          this.$root.$app.resendPayment(event.target);
	        }
	      } else if (this.currentPage && this.currentPage.isActive) {
	        this.$root.$app.sendPage(this.currentPage.id);
	      }
	    },
	    close: function close() {
	      this.$root.$app.closeApplication();
	    },
	    setPageTitle: function setPageTitle() {
	      var title = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

	      if (!title) {
	        return;
	      }

	      if (this.$root.$nodes.title) {
	        this.$root.$nodes.title.innerText = title;
	      }
	    },
	    onFrameError: function onFrameError() {
	      clearTimeout(this.frameCheckLongTimeout);

	      if (this.showedPageIds.includes(this.currentPage.id)) {
	        this.loadedPageIds.push(this.currentPage.id);
	      }

	      this.errorPageIds.push(this.currentPage.id);
	    },
	    onFrameLoad: function onFrameLoad(pageId) {
	      var _this9 = this;

	      clearTimeout(this.frameCheckLongTimeout);

	      if (this.showedPageIds.includes(pageId)) {
	        this.loadedPageIds.push(pageId);

	        if (this.currentPage && this.currentPage.id === pageId) {
	          if (this.frameCheckShortTimeout && !this.currentPage.landingId) {
	            this.onFrameError();
	          } else if (this.errorPageIds.includes(this.currentPage.id)) {
	            this.errorPageIds = this.errorPageIds.filter(function (pageId) {
	              return pageId !== _this9.currentPage.id;
	            });
	          }
	        }
	      }

	      if (this.frameCheckShortTimeout && this.currentPage && this.currentPage.id === pageId && !this.currentPage.landingId) {
	        this.onFrameError();
	      }
	    },
	    startFrameCheckTimeout: function startFrameCheckTimeout() {
	      var _this10 = this;

	      // this is a workaround for denied through X-Frame-Options sources
	      if (this.frameCheckShortTimeout) {
	        clearTimeout(this.frameCheckShortTimeout);
	        this.frameCheckShortTimeout = false;
	      }

	      this.frameCheckShortTimeout = setTimeout(function () {
	        _this10.frameCheckShortTimeout = false;
	      }, 500); // to show error on long loading

	      clearTimeout(this.frameCheckLongTimeout);
	      this.frameCheckLongTimeout = setTimeout(function () {
	        if (_this10.currentPage && _this10.showedPageIds.includes(_this10.currentPage.id) && !_this10.loadedPageIds.includes(_this10.currentPage.id)) {
	          _this10.errorPageIds.push(_this10.currentPage.id);
	        }
	      }, 5000);
	    },
	    connect: function connect() {
	      var _this11 = this;

	      var loader = new BX.Loader({
	        size: 200
	      });
	      loader.show(document.body);
	      BX.Salescenter.Manager.connect({
	        no_redirect: 'Y',
	        context: this.$root.$app.context
	      }).then(function () {
	        BX.Salescenter.Manager.loadConfig().then(function (result) {
	          loader.hide();

	          if (result.isSiteExists) {
	            _this11.$root.$app.isSiteExists = result.isSiteExists;
	            _this11.isSiteExists = result.isSiteExists;

	            _this11.$root.$app.fillPages().then(function () {
	              _this11.isOrderPublicUrlExists = true;
	              _this11.$root.$app.isOrderPublicUrlExists = true;
	              _this11.$root.$app.orderPublicUrl = result.orderPublicUrl;
	              _this11.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
	              _this11.$root.$app.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;

	              if (!_this11.isShowPayment && !_this11.isShowPaymentBySms) {
	                _this11.openFirstPage();
	              } else {
	                if (_this11.isShowPaymentBySms) {
	                  _this11.showPaymentBySmsForm();
	                } else {
	                  _this11.showPaymentForm();
	                }
	              }
	            });
	          }
	        });
	      }).catch(function () {
	        loader.hide();
	      });
	    },
	    checkRecycle: function checkRecycle() {
	      salescenter_manager.Manager.openConnectedSite(true);
	    },
	    openConnectedSite: function openConnectedSite() {
	      salescenter_manager.Manager.openConnectedSite();
	    },
	    getFrameSource: function getFrameSource(page) {
	      if (this.showedPageIds.includes(page.id)) {
	        if (page.landingId > 0) {
	          if (page.isActive) {
	            return new main_core.Uri(page.url).setQueryParam('theme', '').toString();
	          }
	        } else {
	          return page.url;
	        }
	      }

	      return null;
	    },
	    refreshOrdersCount: function refreshOrdersCount() {
	      var _this12 = this;

	      this.$root.$app.getOrdersCount().then(function (result) {
	        _this12.ordersCount = result.answer.result || null;
	      }).catch(function () {
	        _this12.ordersCount = null;
	      });
	    },
	    renderErrors: function renderErrors() {
	      if (this.isShowPayment && this.order.errors.length > 0) {
	        var errorMessages = this.order.errors.map(function (item) {
	          return item.message;
	        }).join('<br>');
	        var params = {
	          color: BX.UI.Alert.Color.DANGER,
	          textCenter: true,
	          text: BX.util.htmlspecialchars(errorMessages)
	        };

	        if (this.$refs.errorBlock.innerHTML.length === 0) {
	          params.animated = true;
	        }

	        var alert = new BX.UI.Alert(params);
	        this.$refs.errorBlock.innerHTML = '';
	        this.$refs.errorBlock.appendChild(alert.getContainer());
	      } else if (this.$refs.errorBlock) {
	        this.$refs.errorBlock.innerHTML = '';
	      }
	    },
	    editMenuItem: function editMenuItem(event, page) {
	      this.editedPageId = page.id;
	      setTimeout(function () {
	        event.target.parentNode.parentNode.querySelector('input').focus();
	      }, 50);
	    },
	    saveMenuItem: function saveMenuItem(event) {
	      var _this13 = this;

	      var pageId = this.editedPageId;
	      var name = event.target.value;
	      var oldName;
	      this.pages.forEach(function (page) {
	        if (page.id === _this13.editedPageId) {
	          oldName = page.name;
	        }
	      });

	      if (pageId > 0 && oldName && name !== oldName && name.length > 0) {
	        salescenter_manager.Manager.addPage({
	          id: pageId,
	          name: name,
	          analyticsLabel: 'salescenterUpdatePageTitle'
	        }).then(function () {
	          _this13.$root.$app.fillPages().then(function () {
	            if (_this13.editedPageId === _this13.currentPageId) {
	              _this13.setPageTitle(name);
	            }

	            _this13.editedPageId = null;
	          });
	        });
	      } else {
	        this.editedPageId = null;
	      }
	    },
	    createLoader: function createLoader() {
	      var loader = new main_loader.Loader({
	        size: 200
	      });
	      loader.show(this.$refs['previewLoader']);
	    }
	  },
	  computed: babelHelpers.objectSpread({
	    config: function config$$1() {
	      return config;
	    },
	    currentPage: function currentPage() {
	      var _this14 = this;

	      if (this.currentPageId > 0) {
	        var pages = this.application.pages.filter(function (page) {
	          return page.id === _this14.currentPageId;
	        });

	        if (pages.length > 0) {
	          return pages[0];
	        }
	      }

	      return null;
	    },
	    pagesSubmenuHeight: function pagesSubmenuHeight() {
	      if (this.isPagesOpen) {
	        return this.application.pages.filter(function (page) {
	          return !page.isWebform;
	        }).length * 39 + 30 + 'px';
	      } else {
	        return '0px';
	      }
	    },
	    formsSubmenuHeight: function formsSubmenuHeight() {
	      if (this.isFormsOpen) {
	        return this.application.pages.filter(function (page) {
	          return page.isWebform;
	        }).length * 39 + 30 + 'px';
	      } else {
	        return '0px';
	      }
	    },
	    isFrameError: function isFrameError() {
	      if (this.isShowPreview && this.currentPage) {
	        if (!this.currentPage.isActive) {
	          return true;
	        } else if (!this.currentPage.landingId && this.errorPageIds.includes(this.currentPage.id)) {
	          return true;
	        }
	      }

	      return false;
	    },
	    isShowLoader: function isShowLoader() {
	      return this.isShowPreview && this.currentPageId > 0 && this.showedPageIds.includes(this.currentPageId) && !this.loadedPageIds.includes(this.currentPageId);
	    },
	    isShowStartInfo: function isShowStartInfo() {
	      var res = false;

	      if (this.isShowPreview) {
	        res = !this.pages || this.pages.length <= 0;
	      } else if (this.isShowPayment || this.isShowPaymentBySms) {
	        res = !this.isOrderPublicUrlAvailable;
	      }

	      return res;
	    },
	    getWrapperHeight: function getWrapperHeight() {
	      if (this.isShowPreview || this.isShowPayment || this.isShowPaymentBySms) {
	        var position = BX.pos(this.$root.$el);
	        var offset = position.top + 20;

	        if (this.$root.$nodes.footer) {
	          offset += BX.pos(this.$root.$nodes.footer).height;
	        }

	        var buttonsPanel = this.$refs['buttonsPanel'];

	        if (buttonsPanel) {
	          offset += BX.pos(buttonsPanel).height;
	        }

	        return 'calc(100vh - ' + offset + 'px)';
	      } else {
	        return 'auto';
	      }
	    },
	    lastModified: function lastModified() {
	      if (this.currentPage && this.currentPage.modifiedAgo) {
	        return this.localize.SALESCENTER_MODIFIED.replace('#AGO#', this.currentPage.modifiedAgo);
	      }

	      return false;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_');
	    },
	    pages: function pages() {
	      this.isOrderPublicUrlAvailable = this.$root.$app.isOrderPublicUrlAvailable;
	      return babelHelpers.toConsumableArray(this.application.pages);
	    },
	    isAllowedSubmitButton: function isAllowedSubmitButton() {
	      if (this.$root.$app.disableSendButton) {
	        return false;
	      }

	      if (this.isShowPreview && this.currentPage && !this.currentPage.isActive) {
	        return false;
	      }

	      if (this.isShowPayment || this.isShowPaymentBySms) {
	        if (this.isShowPaymentBySms && this.$root.$app.options.contactPhone === '') {
	          return false;
	        }

	        return this.$store.getters['orderCreation/isAllowedSubmit'];
	      }

	      return this.currentPage;
	    },
	    isOrderPageDeleted: function isOrderPageDeleted() {
	      return this.$root.$app.isSiteExists && !this.isOrderPublicUrlExists;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  template: "\n\t\t<div class=\"salescenter-app-wrapper\" :class=\"{'salescenter-app-wrapper-fade': isFaded}\" :style=\"{minHeight: getWrapperHeight}\">\n\t\t\t<div class=\"ui-sidepanel-sidebar salescenter-app-sidebar\" ref=\"sidebar\">\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\" v-if=\"this.$root.$app.context !== 'deal'\">\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isPagesOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"isPagesOpen = !isPagesOpen;\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAGES}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: pagesSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"!page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrdersList\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDERS}}</div>\n\t\t\t\t\t\t\t<span class=\"ui-sidepanel-counter\" ref=\"ordersCounter\" v-show=\"ordersCount > 0\">{{ordersCount}}</span>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrderAdd\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDER_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isCatalogAvailable\" @click=\"showCatalog\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_CATALOG}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isFormsOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"onFormsClick();\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_FORMS_ALL}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: formsSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t :class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event, true)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\" v-if=\"this.$root.$app.context === 'deal'\">\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPaymentBySms}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentBySmsForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_SEND_BY_SMS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm ui-sidepanel-menu-item-separate\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"showCompanyContacts(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.options.isBitrix24\" class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"BX.Salescenter.Manager.openFeedbackPayOrderForm(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_OFFER_SCRIPT}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"BX.Salescenter.Manager.openHowPayDealWorks(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_HOW_WORKS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-right-side\">\n\t\t\t\t<div class=\"salescenter-app-page-header\" v-show=\"isShowPreview && !isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-dropdown ui-btn-xs\" @click=\"showActionsPopup($event)\">{{localize.SALESCENTER_RIGHT_ACTIONS_BUTTON}}</div>\n\t\t\t\t\t<div class=\"salescenter-btn-delimiter salescenter-btn-action\"></div>\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-xs ui-btn-icon-edit\" @click=\"editPage\">{{localize.SALESCENTER_RIGHT_ACTION_EDIT}}</div>\n\t\t\t\t</div>\n\t\t\t\t<template v-if=\"isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-start-wrapper\">\n\t\t\t\t\t\t<div class=\"ui-title-1 ui-text-center ui-color-medium\" style=\"margin-bottom: 20px;\">{{localize.SALESCENTER_INFO_TEXT_TOP_2}}</div>\n\t\t\t\t\t\t<div class=\"ui-hr ui-mv-25\"></div>\n\t\t\t\t\t\t<template v-if=\"this.isOrderPublicUrlExists\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"openConnectedSite\">{{localize.SALESCENTER_INFO_PUBLIC}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"isOrderPageDeleted\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_ORDER_PAGE_DELETED}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"checkRecycle\">{{localize.SALESCENTER_CHECK_RECYCLE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_2}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"connect\">{{localize.SALESCENTER_INFO_CREATE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-link ui-btn-lg\" @click=\"BX.Salescenter.Manager.openHowPayDealWorks(event)\">{{localize.SALESCENTER_HOW}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else-if=\"isFrameError && isShowPreview\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-lost\">\n\t\t\t\t\t\t<div class=\"salescenter-app-lost-block ui-title-1 ui-text-center ui-color-medium\">{{localize.SALESCENTER_ERROR_TITLE}}</div>\n\t\t\t\t\t\t<div v-if=\"currentPage.isFrameDenied === true\" class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_RIGHT_FRAME_DENIED}}</div>\n\t\t\t\t\t\t<div v-else-if=\"currentPage.isActive !== true\" class=\"salescenter-app-lost-helper salescenter-app-not-active ui-color-medium\">{{localize.SALESCENTER_RIGHT_NOT_ACTIVE}}</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_ERROR_TEXT}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div v-show=\"isShowPreview && !isShowStartInfo && !isFrameError\" class=\"salescenter-app-page-content\">\n\t\t\t\t\t<template v-for=\"page in pages\">\n\t\t\t\t\t\t<iframe class=\"salescenter-app-demo\" v-show=\"currentPage && currentPage.id == page.id\" :src=\"getFrameSource(page)\" frameborder=\"0\" @error=\"onFrameError(page.id)\" @load=\"onFrameLoad(page.id)\" :key=\"page.id\"></iframe>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"salescenter-app-demo-overlay\" :class=\"{\n\t\t\t\t\t\t'salescenter-app-demo-overlay-loading': this.isShowLoader\n\t\t\t\t\t}\">\n\t\t\t\t\t\t<div v-show=\"isShowLoader\" ref=\"previewLoader\"></div>\n\t\t\t\t\t\t<div v-if=\"lastModified\" class=\"salescenter-app-demo-overlay-modification\">{{lastModified}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t    <template v-if=\"this.$root.$app.isPaymentsLimitReached\">\n\t\t\t        <div ref=\"paymentsLimit\" v-show=\"isShowPayment && !isShowStartInfo\"></div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t        <component v-if=\"isShowPayment && !isShowStartInfo\" :is=\"config.templateAddPaymentName\" :key=\"order.basketVersion\"></component>\n\t\t        </template>\n\t\t        <template v-if=\"isShowPaymentBySms && !isShowStartInfo\">\n\t\t\t        <deal-receiving-payment v-on:stage-block-send-on-send=\"send\" :sendAllowed=\"isAllowedSubmitButton\"/>\n\t\t        </template>\n\t\t\t</div>\n\t\t\t<div class=\"ui-button-panel-wrapper salescenter-button-panel\" ref=\"buttonsPanel\">\n\t\t\t\t<div class=\"ui-button-panel\">\n\t\t\t\t\t<button :class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\" v-if=\"editable\">{{localize.SALESCENTER_SEND}}</button>\n\t\t\t\t\t<button :class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\" v-else>{{localize.SALESCENTER_RESEND}}</button>\n\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"close\">{{localize.SALESCENTER_CANCEL}}</button>\n\t\t\t\t\t<button v-if=\"isShowPayment && !isShowStartInfo && !this.$root.$app.isPaymentsLimitReached\" class=\"ui-btn ui-btn-md ui-btn-link btn-send-crm\" @click=\"send($event, 'y')\">{{localize.SALESCENTER_SAVE_ORDER}}</button>\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"this.order.errors.length > 0\" ref=\"errorBlock\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var App = /*#__PURE__*/function () {
	  function App() {
	    var _this = this;

	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
	      dialogId: null,
	      sessionId: null,
	      lineId: null,
	      orderAddPullTag: null,
	      landingPublicationPullTag: null,
	      landingUnPublicationPullTag: null,
	      isFrame: true,
	      isOrderPublicUrlAvailable: false,
	      isCatalogAvailable: false,
	      isOrderPublicUrlExists: false
	    };
	    babelHelpers.classCallCheck(this, App);
	    this.slider = BX.SidePanel.Instance.getTopSlider();
	    this.dialogId = options.dialogId;
	    this.sessionId = parseInt(options.sessionId);
	    this.lineId = parseInt(options.lineId);
	    this.orderAddPullTag = options.orderAddPullTag;
	    this.landingPublicationPullTag = options.landingPublicationPullTag;
	    this.landingUnPublicationPullTag = options.landingUnPublicationPullTag;
	    this.paySystemList = options.paySystemList;
	    this.cashboxList = options.cashboxList;
	    this.options = options;
	    this.isProgress = false;
	    this.fillPagesTimeout = false;
	    this.disableSendButton = false;
	    this.context = '';
	    this.fillPagesQueue = [];
	    this.ownerTypeId = '';
	    this.ownerId = '';
	    this.stageOnOrderPaid = null;
	    this.sendingMethod = '';
	    this.sendingMethodDesc = {};
	    this.urlSettingsSmsSenders = options.urlSettingsSmsSenders;
	    this.orderPublicUrl = '';
	    this.fileControl = options.fileControl;

	    if (main_core.Type.isString(options.stageOnOrderPaid)) {
	      this.stageOnOrderPaid = options.stageOnOrderPaid;
	    }

	    if (main_core.Type.isBoolean(options.isFrame)) {
	      this.isFrame = options.isFrame;
	    } else {
	      this.isFrame = true;
	    }

	    if (main_core.Type.isBoolean(options.isOrderPublicUrlAvailable)) {
	      this.isOrderPublicUrlAvailable = options.isOrderPublicUrlAvailable;
	    } else {
	      this.isOrderPublicUrlAvailable = false;
	    }

	    if (main_core.Type.isBoolean(options.isOrderPublicUrlExists)) {
	      this.isOrderPublicUrlExists = options.isOrderPublicUrlExists;
	    } else {
	      this.isOrderPublicUrlExists = false;
	    }

	    if (main_core.Type.isString(options.orderPublicUrl)) {
	      this.orderPublicUrl = options.orderPublicUrl;
	    }

	    if (main_core.Type.isBoolean(options.isCatalogAvailable)) {
	      this.isCatalogAvailable = options.isCatalogAvailable;
	    } else {
	      this.isCatalogAvailable = false;
	    }

	    if (main_core.Type.isBoolean(options.disableSendButton)) {
	      this.disableSendButton = options.disableSendButton;
	    }

	    if (options.ownerTypeId) {
	      this.ownerTypeId = options.ownerTypeId;
	    }

	    if (options.ownerId) {
	      this.ownerId = options.ownerId;
	    }

	    if (main_core.Type.isString(options.context) && options.context.length > 0) {
	      this.context = options.context;
	    } else if (this.sessionId && this.dialogId) {
	      this.context = 'imopenlines_app';
	    }

	    if (main_core.Type.isBoolean(options.isPaymentsLimitReached)) {
	      this.isPaymentsLimitReached = options.isPaymentsLimitReached;
	    } else {
	      this.isPaymentsLimitReached = false;
	    }

	    if (!main_core.Type.isUndefined(options.sendingMethod)) {
	      this.sendingMethod = options.sendingMethod;
	      this.sendingMethodDesc = this.options.sendingMethodDesc;
	    }

	    this.isPaymentCreationAvailable = this.sessionId > 0 && this.dialogId.length > 0 || this.ownerTypeId && this.ownerId;
	    this.connector = main_core.Type.isString(options.connector) ? options.connector : '';
	    main_core.Event.ready(function () {
	      _this.pull = BX.PULL;

	      _this.initPull();

	      _this.isSiteExists = salescenter_manager.Manager.isSiteExists;
	    });
	    App.initStore().then(function (result) {
	      return _this.initTemplate(result);
	    }).catch(function (error) {
	      return App.showError(error);
	    });
	  }

	  babelHelpers.createClass(App, [{
	    key: "initPull",
	    value: function initPull() {
	      var _this2 = this;

	      if (this.pull) {
	        if (main_core.Type.isString(this.orderAddPullTag)) {
	          this.pull.subscribe({
	            moduleId: config.moduleId,
	            command: this.orderAddPullTag,
	            callback: function callback(params) {
	              if (parseInt(params.sessionId) === _this2.sessionId && params.orderId > 0) {
	                salescenter_manager.Manager.showOrdersListAfterCreate(params.orderId);
	              }
	            }
	          });
	        }

	        if (main_core.Type.isString(this.landingPublicationPullTag)) {
	          this.pull.subscribe({
	            moduleId: config.moduleId,
	            command: this.landingPublicationPullTag,
	            callback: function callback(params) {
	              if (parseInt(params.landingId) > 0) {
	                _this2.fillPages();
	              }

	              if (params.hasOwnProperty('isOrderPublicUrlAvailable') && main_core.Type.isBoolean(params.isOrderPublicUrlAvailable)) {
	                _this2.isOrderPublicUrlAvailable = params.isOrderPublicUrlAvailable;
	                _this2.isOrderPublicUrlExists = true;
	              }
	            }
	          });
	        }

	        if (main_core.Type.isString(this.landingUnPublicationPullTag)) {
	          this.pull.subscribe({
	            moduleId: config.moduleId,
	            command: this.landingUnPublicationPullTag,
	            callback: function callback(params) {
	              if (parseInt(params.landingId) > 0) {
	                _this2.fillPages();
	              }

	              if (params.hasOwnProperty('isOrderPublicUrlAvailable') && main_core.Type.isBoolean(params.isOrderPublicUrlAvailable)) {
	                _this2.isOrderPublicUrlAvailable = params.isOrderPublicUrlAvailable;
	                _this2.isOrderPublicUrlExists = true;
	              }
	            }
	          });
	        }
	      }
	    }
	  }, {
	    key: "initTemplate",
	    value: function initTemplate(result) {
	      var _this3 = this;

	      return new Promise(function (resolve) {
	        var context = _this3;
	        _this3.store = result.store;
	        _this3.templateEngine = ui_vue.Vue.create({
	          el: document.getElementById('salescenter-app-root'),
	          template: "<".concat(config.templateName, "/>"),
	          store: _this3.store,
	          created: function created() {
	            this.$app = context;
	            this.$nodes = {
	              footer: document.getElementById('footer'),
	              leftPanel: document.getElementById('left-panel'),
	              title: document.getElementById('pagetitle'),
	              paymentsLimit: document.getElementById('salescenter-payment-limit-container')
	            };
	          },
	          mounted: function mounted() {
	            resolve();
	          }
	        });
	      });
	    }
	  }, {
	    key: "closeApplication",
	    value: function closeApplication() {
	      if (this.slider) {
	        this.slider.close();
	      }
	    }
	  }, {
	    key: "fillPages",
	    value: function fillPages() {
	      var _this4 = this;

	      return new Promise(function (resolve) {
	        if (_this4.isProgress) {
	          _this4.fillPagesQueue.push(resolve);
	        } else {
	          if (_this4.fillPagesTimeout) {
	            clearTimeout(_this4.fillPagesTimeout);
	          }

	          _this4.fillPagesTimeout = setTimeout(function () {
	            _this4.startProgress();

	            rest_client.rest.callMethod('salescenter.page.list', {}).then(function (result) {
	              _this4.store.commit('application/setPages', {
	                pages: result.answer.result.pages
	              });

	              _this4.stopProgress();

	              resolve();

	              _this4.fillPagesQueue.forEach(function (item) {
	                item();
	              });

	              _this4.fillPagesQueue = [];
	            });
	          }, 100);
	        }
	      });
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 200
	        });
	      }

	      return this.loader;
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader() {
	      if (this.templateEngine) {
	        this.getLoader().show(this.templateEngine.$el);
	      }
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      this.getLoader().hide();
	    }
	  }, {
	    key: "startProgress",
	    value: function startProgress() {
	      var buttonEvent = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      this.isProgress = true;
	      this.templateEngine.$emit('on-start-progress');
	      this.showLoader();

	      if (main_core.Type.isDomNode(buttonEvent)) {
	        buttonEvent.classList.add('ui-btn-wait');
	      }
	    }
	  }, {
	    key: "stopProgress",
	    value: function stopProgress() {
	      var buttonEvent = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      this.isProgress = false;
	      this.templateEngine.$emit('on-stop-progress');
	      this.hideLoader();

	      if (main_core.Type.isDomNode(buttonEvent)) {
	        buttonEvent.classList.remove('ui-btn-wait');
	      }
	    }
	  }, {
	    key: "hidePage",
	    value: function hidePage(page) {
	      var _this5 = this;

	      return new Promise(function (resolve, reject) {
	        var promise;

	        if (page.landingId > 0) {
	          promise = salescenter_manager.Manager.hidePage(page);
	        } else {
	          promise = salescenter_manager.Manager.deleteUrl(page);
	        }

	        promise.then(function () {
	          _this5.store.commit('application/removePage', {
	            page: page
	          });

	          resolve();
	        }).catch(function (result) {
	          App.showError(result.answer.error_description);
	          reject(result.answer.error_description);
	        });
	      });
	    }
	  }, {
	    key: "sendPage",
	    value: function sendPage(pageId) {
	      var _this6 = this;

	      if (this.isProgress) {
	        return;
	      }

	      if (this.disableSendButton) {
	        return;
	      }

	      var pages = this.store.getters['application/getPages']();
	      var page;

	      for (var index in pages) {
	        if (pages.hasOwnProperty(index) && pages[index].id === pageId) {
	          page = pages[index];
	          break;
	        }
	      }

	      var source = 'other';

	      if (page.landingId > 0) {
	        if (parseInt(page.siteId) === parseInt(salescenter_manager.Manager.connectedSiteId)) {
	          source = 'landing_store_chat';
	        } else {
	          source = 'landing_other';
	        }
	      }

	      if (!this.dialogId) {
	        this.slider.data.set('action', 'sendPage');
	        this.slider.data.set('page', page);
	        this.slider.data.set('pageId', pageId);

	        if (this.context === 'sms') {
	          this.startProgress();
	          BX.Salescenter.Manager.addAnalyticAction({
	            analyticsLabel: 'salescenterSendSms',
	            context: this.context,
	            source: source,
	            type: page.isWebform ? 'form' : 'info',
	            code: page.code
	          }).then(function () {
	            _this6.stopProgress();

	            _this6.closeApplication();
	          });
	        } else {
	          this.closeApplication();
	        }

	        return;
	      }

	      this.startProgress();
	      main_core.ajax.runAction('salescenter.page.send', {
	        analyticsLabel: 'salescenterSendChat',
	        getParameters: {
	          dialogId: this.dialogId,
	          context: this.context,
	          source: source,
	          type: page.isWebform ? 'form' : 'info',
	          connector: this.connector,
	          code: page.code
	        },
	        data: {
	          id: pageId,
	          options: {
	            dialogId: this.dialogId,
	            sessionId: this.sessionId
	          }
	        }
	      }).then(function () {
	        _this6.stopProgress();

	        _this6.closeApplication();
	      }).catch(function (result) {
	        App.showError(result.errors.pop().message);

	        _this6.stopProgress();
	      });
	    }
	  }, {
	    key: "sendPayment",
	    value: function sendPayment(buttonEvent) {
	      var _this7 = this;

	      var skipPublicMessage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'n';

	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }

	      var basket = this.store.getters['orderCreation/getBasket']();
	      var deliveryId = this.store.getters['orderCreation/getDeliveryId'];
	      var delivery = this.store.getters['orderCreation/getDelivery'];
	      var propertyValues = this.store.getters['orderCreation/getPropertyValues'];
	      var deliveryExtraServicesValues = this.store.getters['orderCreation/getDeliveryExtraServicesValues'];
	      var expectedDelivery = this.store.getters['orderCreation/getExpectedDelivery'];
	      var deliveryResponsibleId = this.store.getters['orderCreation/getDeliveryResponsibleId'];
	      var personTypeId = this.store.getters['orderCreation/getPersonTypeId'];

	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }

	      this.startProgress(buttonEvent);
	      var data = {
	        dialogId: this.dialogId,
	        sendingMethod: this.sendingMethod,
	        sendingMethodDesc: this.sendingMethodDesc,
	        sessionId: this.sessionId,
	        lineId: this.lineId,
	        ownerTypeId: this.ownerTypeId,
	        ownerId: this.ownerId,
	        skipPublicMessage: skipPublicMessage,
	        deliveryId: deliveryId,
	        deliveryPrice: delivery,
	        expectedDeliveryPrice: expectedDelivery,
	        deliveryResponsibleId: deliveryResponsibleId,
	        personTypeId: personTypeId,
	        propertyValues: propertyValues,
	        deliveryExtraServicesValues: deliveryExtraServicesValues,
	        connector: this.connector
	      };

	      if (this.stageOnOrderPaid !== null) {
	        data.stageOnOrderPaid = this.stageOnOrderPaid;
	      }

	      BX.ajax.runAction('salescenter.order.createPayment', {
	        data: {
	          basketItems: basket,
	          options: data
	        },
	        analyticsLabel: this.context === 'deal' ? 'salescenterCreatePaymentSms' : 'salescenterCreatePayment',
	        getParameters: {
	          dialogId: this.dialogId,
	          context: this.context,
	          connector: this.connector,
	          skipPublicMessage: skipPublicMessage
	        }
	      }).then(function (result) {
	        _this7.store.dispatch('orderCreation/resetBasket');

	        _this7.stopProgress(buttonEvent);

	        if (skipPublicMessage === 'y') {
	          var notify = {
	            content: main_core.Loc.getMessage('SALESCENTER_ORDER_CREATE_NOTIFICATION').replace('#ORDER_ID#', result.data.order.number)
	          };
	          notify.actions = [{
	            title: main_core.Loc.getMessage('SALESCENTER_VIEW'),
	            events: {
	              click: function click() {
	                salescenter_manager.Manager.showOrderAdd(result.data.order.id);
	              }
	            }
	          }];
	          BX.UI.Notification.Center.notify(notify);
	          salescenter_manager.Manager.showOrdersList({
	            orderId: result.data.order.id,
	            ownerId: _this7.ownerId,
	            ownerTypeId: _this7.ownerTypeId
	          });
	        } else {
	          _this7.slider.data.set('action', 'sendPayment');

	          _this7.slider.data.set('order', result.data.order);

	          if (result.data.deal) {
	            _this7.slider.data.set('deal', result.data.deal);
	          }

	          _this7.closeApplication();
	        }
	      }).catch(function (data) {
	        data.errors.forEach(function (error) {
	          alert(error.message);
	        });

	        _this7.stopProgress(buttonEvent);

	        App.showError(data);
	      });
	    }
	  }, {
	    key: "resendPayment",
	    value: function resendPayment(buttonEvent) {
	      var _this8 = this;

	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }

	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }

	      this.startProgress(buttonEvent);
	      BX.ajax.runAction('salescenter.order.resendPayment', {
	        data: {
	          orderId: this.options.associatedEntityId,
	          options: {
	            sendingMethod: this.sendingMethod,
	            sendingMethodDesc: this.sendingMethodDesc,
	            stageOnOrderPaid: this.stageOnOrderPaid
	          }
	        },
	        getParameters: {
	          context: this.context
	        }
	      }).then(function (result) {
	        _this8.stopProgress(buttonEvent);

	        _this8.closeApplication();
	      }).catch(function (data) {
	        data.errors.forEach(function (error) {
	          alert(error.message);
	        });

	        _this8.stopProgress(buttonEvent);

	        App.showError(data);
	      });
	    }
	  }, {
	    key: "getOrdersCount",
	    value: function getOrdersCount() {
	      if (this.sessionId > 0) {
	        return rest_client.rest.callMethod('salescenter.order.getActiveOrdersCount', {
	          sessionId: this.sessionId
	        });
	      } else {
	        return new Promise(function (resolve, reject) {});
	      }
	    }
	  }], [{
	    key: "initStore",
	    value: function initStore() {
	      var builder = new ui_vue_vuex.VuexBuilder();
	      return builder.addModel(ApplicationModel.create()).addModel(OrderCreationModel.create()).useNamespace(true).build();
	    }
	  }, {
	    key: "showError",
	    value: function showError(error) {
	      console.error(error);
	    }
	  }]);
	  return App;
	}();

	exports.App = App;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX,BX,BX.Salescenter.Component.StageBlock.Send,BX.Salescenter,BX.Salescenter.Component.StageBlock,BX.Salescenter.Component.StageBlock,BX.Salescenter.Tile,BX.Salescenter,BX.Catalog,BX,BX,BX,BX.UI,BX.Main,BX.Event,BX,BX,BX.Salescenter,BX.Salescenter.Component.StageBlock,BX,BX.Salescenter.Component,BX.Salescenter.Component.StageBlock,BX.Salescenter.AutomationStage,BX.Salescenter.Component.StageBlock.TimeLine,BX.Salescenter));
//# sourceMappingURL=app.bundle.js.map
