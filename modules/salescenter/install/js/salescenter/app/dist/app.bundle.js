/* eslint-disable */
this.BX = this.BX || {};
(function (exports,bitrix24_phoneverify,ui_designTokens,ui_dialogs_messagebox,main_loader,salescenter_marketplace,salescenter_component_stageBlock_tile,Hint,catalog_productForm,ui_vue,DeliverySelector,ui_fonts_ruble,currency,salescenter_component_stageBlock_automation,AutomationStage,salescenter_component_stageBlock_timeline,ui_icons_disk,popup,ui_buttons_icons,ui_forms,ui_fonts_opensans,ui_pinner,main_core_events,salescenter_component_stageBlock_smsMessage,salescenter_manager,TimeLineItem,ui_notification,Tile,ui_entitySelector,salescenter_component_stageBlock,currency_currencyCore,salescenter_lib,landing_backend,landing_pageobject,rest_client,ui_vue_vuex,ui_iconSet_actions,main_core,main_popup,ui_buttons) {
	'use strict';

	DeliverySelector = DeliverySelector && DeliverySelector.hasOwnProperty('default') ? DeliverySelector['default'] : DeliverySelector;

	var MixinTemplatesType = {
	  data: function data() {
	    return {
	      editable: true
	    };
	  },
	  created: function created() {
	    var _this = this;
	    // TODO: this code is really weird; the only place this event is emitted in is in the products block;
	    // if there's no products block on the page, the entire slider breaks
	    // perhaps a little refactoring is due
	    this.$root.$on('on-change-editable', function (value) {
	      _this.editable = value;
	    });
	  }
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
	  template: "\t\t\n\t\t<div>\n\t\t\t<template v-for=\"(tile, index) in tiles\">\n\t\t\t\t<tile-hint-background-caption-block\tv-if=\"tile.img.length > 0 && tile.showTitle\"\n\t\t\t\t\t:src=\"tile.img\"\n\t\t\t\t\t:name=\"tile.name\"\n\t\t\t\t\t:caption=\"tile.psModeName\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-click=\"openSlider(index)\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-mouseenter=\"showHint(index, $event)\"\n\t\t\t\t\tv-on:tile-hint-bg-label-on-mouseleave=\"hideHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-hint-background-block\t\tv-else-if=\"tile.img.length > 0\"\n\t\t\t\t\t:src=\"tile.img\"\n\t\t\t\t\t:name=\"tile.name\"\n\t\t\t\t\tv-on:tile-hint-bg-on-click=\"openSlider(index)\"\n\t\t\t\t\tv-on:tile-label-bg-hint-on-mouseenter=\"showHint(index, $event)\"\n\t\t\t\t\tv-on:tile-label-bg-hint-on-mouseleave=\"hideHint\"\n\t\t\t\t/>\n\t\t\t\t<tile-hint-plus-block\t\t\tv-else \n\t\t\t\t\t:name=\"tile.name\" \n\t\t\t\t\tv-on:tile-label-plus-on-click=\"openSlider(index)\"\n\t\t\t\t/> \n\t\t\t</template>\n\t\t</div>\n\t"
	};

	var Installed = {
	  props: {
	    tiles: {
	      type: Array,
	      required: true
	    }
	  },
	  mixins: [TileCollectionMixins],
	  template: "\t\n\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text\"\n\t\t\t\t\tv-for=\"(tile, index) in tiles\"\n\t\t\t\t\tv-bind:key=\"index\"\n\t\t\t\t\tv-if=\"isControlTile(tile) === false\"\n\t\t\t\t\t:name=\"tile.name\" \n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(index)\"\n\t\t\t\t/>\n\t\t\t\t<br>\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text-add\"\n\t\t\t\t\tv-if=\"hasTileOfferFromCollection() === true\"\n\t\t\t\t\t:name=\"getTileOfferFromCollection().tile.name\"\n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(getTileOfferFromCollection().index)\"\n\t\t\t\t/>\n\t\t\t\t<tile-label-block class=\"salescenter-app-payment-by-sms-item-container-payment-item-text-add\"\n\t\t\t\t\tv-if=\"hasTileMoreFromCollection() === true\"\n\t\t\t\t\t:name=\"getTileMoreFromCollection().tile.name\"\n\t\t\t\t\tv-on:tile-label-on-click=\"openSlider(getTileMoreFromCollection().index)\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t</div>\n\t"
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
	      type: Number,
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
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-tile-slider-close=\"onSliderClose\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template v-slot:block-hint-title>\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_SETTINGS_TITLE'), "\n\t\t\t</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"!installed\"\n\t\t\t\t\tclass=\"salescenter-app-explanation\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-app-explanation-img\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-explanation-area\">\n\t\t\t\t\t\t<div class=\"salescenter-app-explanation-text\">\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_TERMINAL_CASHBOX_SETUP_HINT'), "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<tile-collection-uninstalled-block \t:tiles=\"tiles\" v-if=\"!installed\" v-on:on-tile-slider-close=\"onSliderClose\"/>\n\t\t\t\t\t<tile-collection-installed-block :tiles=\"tiles\" v-on:on-tile-slider-close=\"onSliderClose\" v-else />\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var ModeDictionary = Object.freeze({
	  payment: 'payment',
	  delivery: 'delivery',
	  paymentDelivery: 'payment_delivery',
	  terminalPayment: 'terminal_payment'
	});

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Product = {
	  mixins: [MixinTemplatesType],
	  mounted: function mounted() {
	    var editable = this.$root.$app.options.templateMode !== 'view';
	    var isCompilationMode = this.$root.$app.compilation !== null;
	    this.$root.$emit('on-change-editable', editable);
	    if (this.productForm) {
	      this.productForm.setEditable(editable, isCompilationMode);
	    }
	    if (this.productForm) {
	      var formWrapper = this.$root.$el.querySelector('.salescenter-app-form-wrapper');
	      formWrapper.appendChild(this.productForm.layout());
	    }
	  },
	  created: function created() {
	    var _this$$root$$app$comp;
	    this.refreshId = null;
	    var defaultCurrency = this.$root.$app.options.currencyCode || '';
	    this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);
	    if (main_core.Type.isArray(this.$root.$app.options.basket)) {
	      var fields = [];
	      this.$root.$app.options.basket.forEach(function (item) {
	        fields.push(item.fields);
	      });
	      this.$store.commit('orderCreation/setBasket', fields);
	    }
	    if (main_core.Type.isObject(this.$root.$app.options.totals)) {
	      this.$store.commit('orderCreation/setTotal', this.$root.$app.options.totals);
	    }
	    this.productForm = new catalog_productForm.ProductForm({
	      currencySymbol: this.$root.$app.options.currencySymbol,
	      currency: defaultCurrency,
	      iblockId: this.$root.$app.options.catalogIblockId,
	      basePriceId: this.$root.$app.options.basePriceId,
	      basket: main_core.Type.isArray(this.$root.$app.options.basket) ? this.$root.$app.options.basket : [],
	      totals: this.$root.$app.options.totals,
	      taxList: this.$root.$app.options.vatList,
	      measures: this.$root.$app.options.measures,
	      showDiscountBlock: this.$root.$app.options.showProductDiscounts,
	      showTaxBlock: this.$root.$app.options.showProductTaxes,
	      totalResultLabel: this.$root.$app.options.mode === 'delivery' ? main_core.Loc.getMessage('SALESCENTER_SHIPMENT_PRODUCT_BLOCK_TOTAL') : null,
	      urlBuilderContext: this.$root.$app.options.urlProductBuilderContext,
	      isCatalogPriceEditEnabled: this.$root.$app.options.isCatalogPriceEditEnabled,
	      isCatalogDiscountSetEnabled: this.$root.$app.options.isCatalogDiscountSetEnabled,
	      fieldHints: this.$root.$app.options.fieldHints,
	      hideUnselectedProperties: this.$root.$app.options.templateMode === 'view',
	      showCompilationModeSwitcher: this.$root.$app.options.templateMode === 'create' && this.$root.$app.options.showCompilationModeSwitcher === 'Y' && this.$root.$app.options.mode === ModeDictionary.paymentDelivery,
	      compilationFormType: this.$root.$app.connector === 'facebook' && this.$root.$app.isAllowedFacebookRegion ? 'FACEBOOK' : 'REGULAR',
	      facebookFailProducts: (_this$$root$$app$comp = this.$root.$app.compilation) === null || _this$$root$$app$comp === void 0 ? void 0 : _this$$root$$app$comp.FAIL_PRODUCTS,
	      ownerId: this.$root.$app.options.ownerId,
	      ownerTypeId: this.$root.$app.options.ownerTypeId,
	      dialogId: this.$root.$app.options.dialogId,
	      sessionId: this.$root.$app.options.sessionId,
	      isShortProductViewFormat: true,
	      enableEmptyProductError: false
	    });
	    this.checkProductErrors();
	    main_core_events.EventEmitter.subscribe(this.productForm, 'ProductForm:onBasketChange', main_core.Runtime.debounce(this.onBasketChange, 500, this));
	    main_core_events.EventEmitter.subscribe(this.productForm, 'ProductForm:onErrorsChange', main_core.Runtime.debounce(this.checkProductErrors, 500, this));
	    main_core_events.EventEmitter.subscribe(this.productForm, 'ProductForm:onModeChange', this.onProductFormModeChange);
	    main_core_events.EventEmitter.subscribe(this.productForm, 'ProductForm:onCompilationCreated', this.onProductFormCompilationCreated.bind(this));
	  },
	  methods: {
	    onProductFormCompilationCreated: function onProductFormCompilationCreated(event) {
	      var data = event.getData();
	      this.$root.$app.newCompilationId = data.compilationId;
	      this.$root.$app.ownerId = data.ownerId;
	      this.$root.$app.options.ownerId = data.ownerId;
	    },
	    onProductFormModeChange: function onProductFormModeChange(event) {
	      var mode = event.getData().mode;
	      if (mode === catalog_productForm.FormMode.COMPILATION || mode === catalog_productForm.FormMode.COMPILATION_READ_ONLY) {
	        this.$store.commit('orderCreation/enableCompilationMode');
	      } else {
	        this.$store.commit('orderCreation/disableCompilationMode');
	      }
	      this.$emit('on-product-form-mode-change');
	    },
	    onBasketChange: function onBasketChange(event) {
	      var _this = this;
	      var processRefreshRequest = function processRefreshRequest(data) {
	        if (_this.productForm) {
	          var preparedBasket = [];
	          data.basket.forEach(function (item) {
	            if (!main_core.Type.isStringFilled(item.innerId)) {
	              return;
	            }
	            preparedBasket.push({
	              selectorId: item.innerId,
	              fields: item
	            });
	          });
	          _this.productForm.setData(_objectSpread(_objectSpread({}, data), {}, {
	            basket: preparedBasket
	          }));
	          if (main_core.Type.isArray(data.basket)) {
	            _this.$store.commit('orderCreation/setBasket', data.basket);
	          }
	          if (main_core.Type.isObject(data.total)) {
	            _this.$store.commit('orderCreation/setTotal', data.total);
	          }
	        }
	      };
	      var data = event.getData();
	      if (!main_core.Type.isArray(data.basket)) {
	        return;
	      }
	      var fields = [];
	      data.basket.forEach(function (item) {
	        fields.push(item.fields);
	      });
	      this.$store.commit('orderCreation/setBasket', fields);
	      if (this.$root.$app.newCompilationId) {
	        this.changeCompilationProducts();
	      }
	      if (this.isNeedDisableSubmit()) {
	        this.$store.commit('orderCreation/disableSubmit');
	        return;
	      }
	      this.$store.commit('orderCreation/enableSubmit');
	      var requestId = main_core.Text.getRandom(20);
	      this.refreshId = requestId;
	      main_core.ajax.runAction('salescenter.api.order.refreshBasket', {
	        data: {
	          orderId: this.$root.$app.orderId,
	          basketItems: fields
	        }
	      }).then(function (result) {
	        if (_this.refreshId !== requestId) {
	          return;
	        }
	        var data = BX.prop.getObject(result, 'data', {});
	        processRefreshRequest({
	          total: BX.prop.getObject(data, 'total', {
	            discount: 0,
	            result: 0,
	            sum: 0
	            // resultNumeric: 0,
	          }),

	          basket: BX.prop.get(data, 'items', [])
	        });
	      })["catch"](function (result) {
	        var data = BX.prop.getObject(result, 'data', {});
	        processRefreshRequest({
	          errors: BX.prop.get(result, 'errors', []),
	          basket: BX.prop.get(data, 'items', [])
	        });
	      });
	    },
	    changeCompilationProducts: function changeCompilationProducts() {
	      var basketItems = this.$store.getters['orderCreation/getBasket']();
	      var productIds = basketItems.map(function (basketItem) {
	        return basketItem.skuId;
	      });
	      var compilationId = this.$root.$app.compilation ? this.$root.$app.compilation.ID : this.$root.$app.newCompilationId;
	      if (compilationId) {
	        main_core.ajax.runAction('salescenter.compilation.updateCompilation', {
	          data: {
	            compilationId: compilationId,
	            productIds: productIds
	          }
	        });
	      }
	    },
	    checkProductErrors: function checkProductErrors() {
	      if (this.isNeedDisableSubmit()) {
	        this.$store.commit('orderCreation/disableSubmit');
	      } else {
	        this.$store.commit('orderCreation/enableSubmit');
	      }
	    },
	    isNeedDisableSubmit: function isNeedDisableSubmit() {
	      var basket = this.$store.getters['orderCreation/getBasket']();
	      return basket.length <= 0 || this.productForm && main_core.Type.isFunction(this.productForm.hasErrors) && this.productForm.hasErrors();
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-side\">\n\t\t\t<div class=\"salescenter-app-page-content\">\n\t\t\t\t<div class=\"salescenter-app-form-wrapper\"></div>\n\t\t\t\t<slot name=\"footer\"></slot>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function ownKeys$1(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$1(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$1(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$1(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Product$1 = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    hintTitle: {
	      type: String,
	      required: true
	    },
	    additionalContainerClasses: {
	      type: Object,
	      required: false,
	      "default": function _default() {
	        return {};
	      }
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    product: Product
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openHowToSell(e);
	    },
	    onProductFormModeChange: function onProductFormModeChange(event) {
	      this.$emit('on-product-form-mode-change');
	    }
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        showHint: true
	      };
	    },
	    containerClasses: function containerClasses() {
	      return _objectSpread$1(_objectSpread$1({}, this.statusClassMixin), this.additionalContainerClasses);
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"containerClasses\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{ title }}</template>\n\t\t\t<template v-slot:block-hint-title>{{ hintTitle }}</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<product\n\t\t\t\t\t\t@on-product-form-mode-change=\"onProductFormModeChange\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t"
	};

	function ownKeys$2(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$2(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$2(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$2(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
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
	      availableServices: []
	    };
	  },
	  methods: {
	    onChange: function onChange(payload) {
	      this.$store.dispatch('orderCreation/setDelivery', payload.deliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryId', payload.deliveryServiceId);
	      this.$store.dispatch('orderCreation/setPropertyValues', payload.relatedPropsValues);
	      this.$store.dispatch('orderCreation/setDeliveryExtraServicesValues', payload.relatedServicesValues);
	      this.$store.dispatch('orderCreation/setExpectedDelivery', payload.estimatedDeliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryResponsibleId', payload.responsibleUser ? payload.responsibleUser.id : null);
	      this.$emit('change', payload);
	    },
	    onAddressFromChanged: function onAddressFromChanged() {
	      this.refreshAvailableServices();
	    },
	    onDeliveryServiceChanged: function onDeliveryServiceChanged() {
	      this.refreshAvailableServices();
	    },
	    onSettingsChanged: function onSettingsChanged() {
	      this.$emit('delivery-settings-changed');
	    },
	    refreshAvailableServices: function refreshAvailableServices() {
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
	          shipmentPropValues: this.order.propertyValues,
	          deliveryRelatedServiceValues: this.order.deliveryExtraServicesValues,
	          deliveryResponsibleId: this.order.deliveryResponsibleId
	        }
	      }).then(function (result) {
	        var data = BX.prop.getObject(result, "data", {});
	        _this.availableServices = data.availableServices ? data.availableServices : {};
	      })["catch"](function (result) {
	        _this.availableServices = {};
	      });
	    }
	  },
	  created: function created() {
	    this.$store.dispatch('orderCreation/setPersonTypeId', this.config.personTypeId);
	    this.refreshAvailableServices();
	  },
	  computed: _objectSpread$2({
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_');
	    },
	    sumTitle: function sumTitle() {
	      return main_core.Loc.getMessage('SALESCENTER_PRODUCT_PRODUCTS_PRICE');
	    },
	    productsPrice: function productsPrice() {
	      return this.order.total.result;
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
	    excludedServiceIds: function excludedServiceIds() {
	      return this.$root.$app.options.mode === 'delivery' ? [this.$root.$app.options.emptyDeliveryServiceId] : [];
	    },
	    actionData: function actionData() {
	      return {
	        basketItems: this.config.basket,
	        options: {
	          orderId: this.$root.$app.orderId,
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
	  template: "\n\t\t<delivery-selector\n\t\t\t:available-services=\"availableServices\"\n\t\t\t:excluded-service-ids=\"excludedServiceIds\"\t\t\t\t\n\t\t\t:init-entered-delivery-price=\"config.deliveryPrice\"\n\t\t\t:init-delivery-service-id=\"config.deliveryServiceId\"\n\t\t\t:init-related-services-values=\"config.relatedServicesValues\"\n\t\t\t:init-related-props-values=\"config.relatedPropsValues\"\n\t\t\t:init-related-props-options=\"config.relatedPropsOptions\"\n\t\t\t:init-responsible-id=\"config.responsibleId\"\n\t\t\t:person-type-id=\"config.personTypeId\"\n\t\t\t:action=\"'salescenter.api.order.refreshDelivery'\"\n\t\t\t:action-data=\"actionData\"\n\t\t\t:external-sum=\"productsPrice\"\n\t\t\t:external-sum-label=\"sumTitle\"\n\t\t\t:currency=\"config.currency\"\n\t\t\t:currency-symbol=\"config.currencySymbol\"\n\t\t\t@change=\"onChange\"\n\t\t\t@address-from-changed=\"onAddressFromChanged\"\n\t\t\t@delivery-service-changed=\"onDeliveryServiceChanged\"\n\t\t\t@settings-changed=\"onSettingsChanged\"\n\t\t></delivery-selector>\n\t"
	};

	var ShipmentView = {
	  props: {
	    id: {
	      type: Number,
	      required: true
	    },
	    productsPrice: {
	      type: Number,
	      required: true
	    }
	  },
	  data: function data() {
	    return {
	      shipment: {
	        priceDelivery: null,
	        basePriceDelivery: null,
	        currency: null,
	        deliveryService: {
	          name: null,
	          logo: null,
	          parent: {
	            name: null,
	            logo: null
	          }
	        },
	        extraServices: [],
	        requestProperties: []
	      },
	      canUserPerformCalls: false
	    };
	  },
	  created: function created() {
	    var _this = this;
	    main_core.ajax.runAction('salescenter.deliveryselector.getShipmentData', {
	      data: {
	        id: this.id
	      }
	    }).then(function (result) {
	      _this.shipment = result.data.shipment;
	      _this.canUserPerformCalls = result.data.canUserPerformCalls;
	    });
	  },
	  methods: {
	    getFormattedPrice: function getFormattedPrice(price) {
	      return BX.Currency.currencyFormat(price, this.currency, true);
	    },
	    isPhoneRequestProperty: function isPhoneRequestProperty(property) {
	      if (!main_core.Type.isArray(property.tags)) {
	        return false;
	      }
	      return property.tags.includes('phone');
	    },
	    makeCall: function makeCall(phoneNumber) {
	      if (!main_core.Type.isUndefined(window.top['BXIM']) && this.canUserPerformCalls === true) {
	        window.top['BXIM'].phoneTo(phoneNumber);
	      } else {
	        window.open('tel:' + phoneNumber, '_self');
	      }
	    }
	  },
	  computed: {
	    hasParent: function hasParent() {
	      return this.shipment.hasOwnProperty('deliveryService') && this.shipment.deliveryService.hasOwnProperty('parent') && this.shipment.deliveryService.parent;
	    },
	    deliveryServiceLogo: function deliveryServiceLogo() {
	      return this.shipment.deliveryService.logo ? this.shipment.deliveryService.logo : null;
	    },
	    deliveryServiceProfileLogo: function deliveryServiceProfileLogo() {
	      return this.hasParent && this.shipment.deliveryService.parent.logo ? this.shipment.deliveryService.parent.logo : null;
	    },
	    paymentPrice: function paymentPrice() {
	      return this.productsPrice + this.priceDelivery;
	    },
	    deliveryServiceName: function deliveryServiceName() {
	      return this.hasParent ? this.shipment.deliveryService.parent.name : this.shipment.deliveryService.name;
	    },
	    deliveryServiceProfileName: function deliveryServiceProfileName() {
	      return this.hasParent ? this.shipment.deliveryService.name : null;
	    },
	    priceDelivery: function priceDelivery() {
	      return this.shipment ? this.shipment.priceDelivery : null;
	    },
	    currency: function currency$$1() {
	      return this.shipment ? this.shipment.currency : null;
	    },
	    extraServices: function extraServices() {
	      return main_core.Type.isArray(this.shipment.extraServices) ? this.shipment.extraServices : [];
	    },
	    isExtraServicesVisible: function isExtraServicesVisible() {
	      return this.extraServices.length > 0;
	    },
	    requestProperties: function requestProperties() {
	      return main_core.Type.isArray(this.shipment.requestProperties) ? this.shipment.requestProperties : [];
	    },
	    isRequestPropertiesVisible: function isRequestPropertiesVisible() {
	      return this.requestProperties.length > 0;
	    },
	    priceDeliveryFormatted: function priceDeliveryFormatted() {
	      return this.getFormattedPrice(this.priceDelivery);
	    },
	    productsPriceFormatted: function productsPriceFormatted() {
	      return this.getFormattedPrice(this.productsPrice);
	    },
	    paymentPriceFormatted: function paymentPriceFormatted() {
	      return this.getFormattedPrice(this.paymentPrice);
	    }
	  },
	  template: "\n\t\t<div style=\"width: 100%;\" xmlns=\"http://www.w3.org/1999/html\">\n\t\t\t<div class=\"salescenter-delivery-selector-head\">\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"hasParent && deliveryServiceLogo\"\n\t\t\t\t\t:style=\"{ backgroundImage: 'url(' + deliveryServiceLogo + ')' }\"\n\t\t\t\t\tclass=\"salescenter-delivery-selector-logo\"\n\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-delivery-selector-info\">\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"deliveryServiceProfileLogo\"\n\t\t\t\t\t\t:style=\"{ backgroundImage: 'url(' + deliveryServiceProfileLogo + ')' }\"\n\t\t\t\t\t\tclass=\"salescenter-delivery-selector-logo\"\n\t\t\t\t\t>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-delivery-selector-content\">\n\t\t\t\t\t\t<div class=\"salescenter-delivery-selector-text-light\">{{deliveryServiceName}}</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"deliveryServiceProfileName\"\n\t\t\t\t\t\t\tclass=\"salescenter-delivery-selector-text-dark\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{deliveryServiceProfileName}}\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isExtraServicesVisible\" class=\"salescenter-delivery-selector-main\">\n\t\t\t\t<div class=\"salescenter-delivery-selector-text-light\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_SHIPMENT_EXTRA_SERVICES'), ":\n\t\t\t\t</div>\n\t\t\t\t<ul class=\"salescenter-delivery-selector-list\">\n\t\t\t\t\t<li\n\t\t\t\t\t\tv-for=\"extraService in extraServices\"\n\t\t\t\t\t\tclass=\"salescenter-delivery-selector-list-item salescenter-delivery-selector-text-dark\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{extraService.name}}: {{extraService.value}} \n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div v-if=\"isRequestPropertiesVisible\" class=\"salescenter-delivery-selector-main\">\n\t\t\t\t<div class=\"salescenter-delivery-selector-text-light\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_DELIVERY_REQUEST_DETAILS'), ":\n\t\t\t\t</div>\n\t\t\t\t<ul class=\"salescenter-delivery-selector-list\">\n\t\t\t\t\t<li\n\t\t\t\t\t\tv-for=\"requestProperty in requestProperties\"\n\t\t\t\t\t\tclass=\"salescenter-delivery-selector-list-item salescenter-delivery-selector-text-dark\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{requestProperty.name}}:\n\t\t\t\t\t\t<template v-if=\"isPhoneRequestProperty(requestProperty)\">\n\t\t\t\t\t\t\t<a @click.prevent=\"makeCall(requestProperty.value)\" href=\"#\">{{requestProperty.value}}</a>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t{{requestProperty.value}}\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-delivery-selector-line\"></div>\n\t\t\t<div class=\"catalog-pf-result-wrapper\">\n\t\t\t\t<table class=\"catalog-pf-result\">\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t<span class=\"catalog-pf-text\">\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_PRODUCTS_PRICE'), ":\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</td> \n\t\t\t\t\t\t<td>\n\t\t\t\t\t\t\t<span v-html=\"productsPriceFormatted\" class=\"catalog-pf-text\"></span> \n\t\t\t\t\t\t</td>\n\t\t\t\t\t</tr>\n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td class=\"catalog-pf-result-padding-bottom\">\n\t\t\t\t\t\t\t<span class=\"catalog-pf-text catalog-pf-text--tax\">\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_SHIPMENT_PRODUCT_BLOCK_DELIVERY_PRICE'), ": \n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</td> \n\t\t\t\t\t\t<td class=\"catalog-pf-result-padding-bottom\"> \n\t\t\t\t\t\t\t<span class=\"catalog-pf-text catalog-pf-text--tax\" v-html=\"priceDeliveryFormatted\"></span>\n\t\t\t\t\t\t</td>\n\t\t\t\t\t</tr> \n\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td class=\"catalog-pf-result-padding\">\n\t\t\t\t\t\t\t<span class=\"catalog-pf-text catalog-pf-text--total catalog-pf-text--border\">\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_TOTAL_RESULT'), ": \n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</td> \n\t\t\t\t\t\t<td class=\"catalog-pf-result-padding\">\n\t\t\t\t\t\t\t<span v-html=\"paymentPriceFormatted\" class=\"catalog-pf-text catalog-pf-text--total\"></span> \n\t\t\t\t\t\t</td>\n\t\t\t\t\t</tr>\n\t\t\t\t</table>\n\t\t\t</div>\n\t\t</div>\n\t")
	};

	function ownKeys$3(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$3(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$3(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$3(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var DeliveryVuex = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
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
	    isCollapsible: {
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
	    'shipment-view': ShipmentView,
	    'uninstalled-delivery-block': Uninstalled
	  },
	  computed: _objectSpread$3({
	    statusClass: function statusClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled-bg': this.installed === false || this.status === salescenter_component_stageBlock.StatusTypes.disabled
	      };
	    },
	    productsPrice: function productsPrice() {
	      return this.order.total.result;
	    },
	    shipmentId: function shipmentId() {
	      return this.$root.$app.options.shipmentId;
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleName: this.selectedDeliveryServiceName,
	        installed: this.installed,
	        collapsible: this.isCollapsible,
	        checked: this.counterCheckedMixin,
	        showHint: false,
	        initialCollapseState: this.initialCollapseState
	      };
	    },
	    config: function config() {
	      var deliveryServiceId = null;
	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('deliveryServiceId')) {
	        deliveryServiceId = this.$root.$app.options.shipmentData.deliveryServiceId;
	      }
	      var deliveryPrice = null;
	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('deliveryPrice')) {
	        deliveryPrice = this.$root.$app.options.shipmentData.deliveryPrice;
	      }
	      var relatedPropsValues = {};
	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('propValues')) {
	        relatedPropsValues = this.$root.$app.options.shipmentData.propValues;
	      }
	      var relatedServicesValues = {};
	      if (this.$root.$app.options.hasOwnProperty('shipmentData') && this.$root.$app.options.shipmentData.hasOwnProperty('extraServicesValues') && !Array.isArray(this.$root.$app.options.shipmentData.extraServicesValues)) {
	        relatedServicesValues = this.$root.$app.options.shipmentData.extraServicesValues;
	      }
	      var relatedPropsOptions = {};
	      if (this.$root.$app.options.hasOwnProperty('deliveryOrderPropOptions') && !Array.isArray(this.$root.$app.options.deliveryOrderPropOptions)) {
	        relatedPropsOptions = this.$root.$app.options.deliveryOrderPropOptions;
	      }
	      return {
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
	        responsibleId: this.$root.$app.options.assignedById,
	        deliveryPrice: deliveryPrice
	      };
	    },
	    isViewTemplateMode: function isViewTemplateMode() {
	      return this.$root.$app.options.templateMode === 'view';
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
	  template: "\n\t\t<stage-block-item\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>".concat(main_core.Loc.getMessage('SALESCENTER_DELIVERY_BLOCK_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<template v-if=\"!installed\">\n\t\t\t\t\t\t<uninstalled-delivery-block :tiles=\"tiles\" \n\t\t\t\t\t\t\t\tv-on:on-tile-slider-close=\"onSliderClose\"/>\n\t\t\t\t\t</template>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-select\">\n\t\t\t\t\t\t\t<shipment-view\n\t\t\t\t\t\t\t\tv-if=\"isViewTemplateMode\"\n\t\t\t\t\t\t\t\t:id=\"shipmentId\"\n\t\t\t\t\t\t\t\t:productsPrice=\"productsPrice\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t</shipment-view>\n\t\t\t\t\t\t\t<delivery-selector-block v-else\n\t\t\t\t\t\t\t\t:config=\"config\" \n\t\t\t\t\t\t\t\t@delivery-settings-changed=\"onSliderClose\"\n\t\t\t\t\t\t\t\t@change=\"setTitleName\" \n\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var PaySystem = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
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
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"[statusClassMixin, statusClass]\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t@on-tile-slider-close=\"onSliderClose\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template v-slot:block-hint-title>\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE'), "\n\t\t\t</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div\n\t\t\t\t\tv-if=\"!installed\"\n\t\t\t\t\tclass=\"salescenter-app-explanation\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"salescenter-app-explanation-img\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-explanation-area\">\n\t\t\t\t\t\t<div class=\"salescenter-app-explanation-text\">\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_TERMINAL_PAYMENT_SYSTEM_SETUP_HINT'), "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<tile-collection-uninstalled-block \t:tiles=\"tiles\" v-if=\"!installed\" v-on:on-tile-slider-close=\"onSliderClose\"/>\n\t\t\t\t\t<tile-collection-installed-block :tiles=\"tiles\" v-on:on-tile-slider-close=\"onSliderClose\" v-else />\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var ContextDictionary = Object.freeze({
	  deal: 'deal',
	  smartInvoice: 'smart_invoice',
	  chat: 'chat',
	  sms: 'sms',
	  imOpenlines: 'imopenlines_app',
	  terminalList: 'terminal_list'
	});

	var ChatMessage = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    manager: {
	      type: Object,
	      required: true
	    },
	    titleTemplate: {
	      type: String,
	      required: true
	    },
	    showHint: {
	      type: Boolean,
	      required: true
	    },
	    editorTemplate: {
	      type: String,
	      required: true
	    },
	    editorUrl: {
	      type: String,
	      required: true
	    },
	    selectedMode: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'chat-user-avatar-block': salescenter_component_stageBlock_smsMessage.UserAvatar,
	    'chat-message-editor-block': salescenter_component_stageBlock_smsMessage.MessageEditor
	  },
	  data: function data() {
	    return {
	      currentSenderCode: null,
	      senders: [],
	      pushedToUseBitrix24Notifications: null,
	      smsSenderListComponentKey: 0
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
	    editor: function editor() {
	      return {
	        template: this.editorTemplate,
	        url: this.editorUrl
	      };
	    },
	    title: function title() {
	      return this.titleTemplate;
	    },
	    isMessageReadOnly: function isMessageReadOnly() {
	      return this.$root.$app.context !== ContextDictionary.sms;
	    }
	  },
	  created: function created() {},
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {
	        width: 1200
	      });
	    },
	    openBitrix24NotificationsHelp: function openBitrix24NotificationsHelp(event) {
	      BX.Salescenter.Manager.openBitrix24NotificationsHelp(event);
	    }
	  },
	  template: "\n\t\t<stage-block-item\t\t\t\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t\tv-on:on-item-hint=\"onItemHint\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template\n\t\t\t\tv-if=\"showHint\"\n\t\t\t\tv-slot:block-hint-title\n\t\t\t>\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS_SHORTER_VERSION'), "\n\t\t\t</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\" class=\"salescenter-app-payment-by-sms-item-container-offtop\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms\">\n\t\t\t\t\t\t<chat-user-avatar-block :manager=\"manager\"/>\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content\">\n\t\t\t\t\t\t\t<chat-message-editor-block :editor=\"editor\" :isReadOnly=\"isMessageReadOnly\" :selectedMode=\"selectedMode\"/>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var Automation = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    stageOnOrderPaid: {
	      type: String,
	      required: false
	    },
	    stageOnDeliveryFinished: {
	      type: String,
	      required: false
	    },
	    items: {
	      type: Array,
	      required: true
	    },
	    initialCollapseState: {
	      type: Boolean,
	      required: true
	    },
	    isDeliveryStageVisible: {
	      type: Boolean,
	      required: false,
	      "default": false
	    }
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  data: function data() {
	    return {
	      paymentStages: [],
	      shipmentStages: []
	    };
	  },
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'stage-item-list': salescenter_component_stageBlock_automation.StageList
	  },
	  methods: {
	    saveCollapsedOption: function saveCollapsedOption(option) {
	      this.$emit('on-save-collapsed-option', 'automation', option);
	    },
	    updatePaymentStage: function updatePaymentStage(e) {
	      var newStageId = e.data;
	      this.paymentStages.forEach(function (stage) {
	        stage.selected = stage.id === newStageId;
	      });
	      this.$root.$app.stageOnOrderPaid = e.data;
	    },
	    updateShipmentStage: function updateShipmentStage(e) {
	      var newStageId = e.data;
	      this.shipmentStages.forEach(function (stage) {
	        stage.selected = stage.id === newStageId;
	      });
	      this.$root.$app.stageOnDeliveryFinished = e.data;
	    },
	    initStages: function initStages(stages, currentValue) {
	      Object.values(this.items).forEach(function (options) {
	        options.selected = !currentValue && !options.hasOwnProperty('id') || options.id === currentValue;
	        stages.push(AutomationStage.Factory.create(options));
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
	      var stages = this.isPayment ? this.paymentStages : this.shipmentStages;
	      return stages.find(function (stage) {
	        return stage.selected;
	      });
	    },
	    isPayment: function isPayment() {
	      return this.$root.$app.options.mode === 'payment_delivery' || this.$root.$app.options.mode === 'payment' || this.$root.$app.options.mode === 'terminal_payment';
	    },
	    isHideDeliveryStage: function isHideDeliveryStage() {
	      return !this.isDeliveryStageVisible;
	    }
	  },
	  created: function created() {
	    if (this.isPayment) {
	      this.initStages(this.paymentStages, this.stageOnOrderPaid);
	    }
	    this.initStages(this.shipmentStages, this.stageOnDeliveryFinished);
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t\t@on-adjust-collapsed=\"saveCollapsedOption\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>".concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<div v-if=\"isPayment\">\n\t\t\t\t\t\t<stage-item-list\n\t\t\t\t\t\t\tv-on:on-choose-select-option=\"updatePaymentStage($event)\"\n\t\t\t\t\t\t\t:stages=\"paymentStages\"\n\t\t\t\t\t\t\t:editable=\"editable\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-slot:stage-list-text>").concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT'), "</template>\n\t\t\t\t\t\t</stage-item-list>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div v-if=\"!isHideDeliveryStage\">\n\t\t\t\t\t\t<stage-item-list\n\t\t\t\t\t\t\tv-on:on-choose-select-option=\"updateShipmentStage($event)\"\n\t\t\t\t\t\t\t:stages=\"shipmentStages\"\n\t\t\t\t\t\t\t:editable=\"editable\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<template v-slot:stage-list-text>").concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_DELIVERY_FINISHED'), "</template>\n\t\t\t\t\t\t</stage-item-list>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var Send = {
	  props: {
	    buttonLabel: {
	      type: String,
	      required: true
	    },
	    buttonEnabled: {
	      type: Boolean,
	      required: true
	    },
	    showWhatClientSeesControl: {
	      type: Boolean,
	      required: true
	    },
	    isFacebookForm: {
	      type: Boolean,
	      required: false,
	      "default": false
	    }
	  },
	  computed: {
	    buttonClass: function buttonClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled': this.buttonEnabled === false
	      };
	    },
	    showSubmitCompilationLinkToFacebookButton: function showSubmitCompilationLinkToFacebookButton() {
	      var isCompilationMode = this.$store.getters['orderCreation/isCompilationMode'];
	      return this.isFacebookForm && isCompilationMode;
	    }
	  },
	  methods: {
	    showWhatClientSees: function showWhatClientSees(event) {
	      BX.Salescenter.Manager.openWhatClientSee(event);
	    },
	    submit: function submit(event) {
	      this.$emit('on-submit', event);
	    },
	    submitCompilationLinkToFacebook: function submitCompilationLinkToFacebook(event) {
	      this.$emit('on-submit-compilation-link-to-facebook', event);
	    }
	  },
	  template: "\n\t\t<div\n\t\t\t:class=\"buttonClass\"\n\t\t\tclass=\"salescenter-app-payment-by-sms-item-show salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t@click=\"submit($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{buttonLabel}}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-if=\"showSubmitCompilationLinkToFacebookButton\"\n\t\t\t\t\t\t\t\t@click=\"submitCompilationLinkToFacebook($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-light-border ui-btn-round\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_SEND_COMPILATION_LINK_TO_FACEBOOK'), "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\tv-if=\"showWhatClientSeesControl\"\n\t\t\t\t\t\t\t\t@click=\"showWhatClientSees\"\n\t\t\t\t\t\t\t\tclass=\"salescenter-app-add-item-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE'), "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
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
	    'timeline-item-payment-block': salescenter_component_stageBlock_timeline.TimeLineItemPaymentBlock,
	    'timeline-item-custom-block': salescenter_component_stageBlock_timeline.TimeLineItemCustomBlock
	  },
	  methods: {
	    isPayment: function isPayment(item) {
	      return item.type === TimeLineItem.Payment.type();
	    },
	    isCustom: function isCustom(item) {
	      return item.type === TimeLineItem.Custom.type();
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline\">\n\t\t\t<template v-for=\"(item) in timelineItems\">\n\t\t\t\t<timeline-item-payment-block :item=\"item\" v-if=\"isPayment(item)\"/>\n\t\t\t\t<timeline-item-custom-block :item=\"item\" v-else-if=\"isCustom(item)\"/>\n\t\t\t\t<timeline-item-block :item=\"item\" v-else/>\n\t\t\t</template>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function ownKeys$4(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$4(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$4(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$4(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var DocumentSelector = {
	  props: {
	    counter: {
	      type: Number,
	      required: true
	    },
	    templateAddUrl: {
	      type: String,
	      required: false
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block
	  },
	  computed: _objectSpread$4({
	    status: function status() {
	      if (this.model && this.model.templates && this.model.templates.length || this.model.documents && this.model.documents.length) {
	        return salescenter_component_stageBlock.StatusTypes.complete;
	      }
	      if (this.templateAddUrl && this.templateAddUrl.length) {
	        return salescenter_component_stageBlock.StatusTypes.current;
	      }
	      return salescenter_component_stageBlock.StatusTypes.disabled;
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleItems: [],
	        installed: true,
	        collapsible: false,
	        checked: this.counterCheckedMixin,
	        showHint: false,
	        initialCollapseState: false
	      };
	    },
	    getDocumentTitle: function getDocumentTitle() {
	      var currentDocument = this.getCurrentDocument();
	      if (currentDocument) {
	        return currentDocument.title;
	      }
	      return main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_TEMPLATE');
	    },
	    withStamps: function withStamps() {
	      var isWithStamps = '';
	      var currentDocument = this.getCurrentDocument();
	      if (currentDocument) {
	        if (currentDocument.isWithStamps) {
	          isWithStamps = main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_WITH_SIGNS');
	        } else {
	          isWithStamps = main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_WITHOUT_SIGNS');
	        }
	      }
	      return isWithStamps;
	    },
	    hasData: function hasData() {
	      var document = this.getCurrentDocument();
	      return document && document.detailUrl;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    model: function model(state) {
	      return state.documentSelector;
	    }
	  })),
	  methods: {
	    handleDocumentClick: function handleDocumentClick(_ref) {
	      var target = _ref.target;
	      if (this.getCurrentDocument() !== null) {
	        this.openSelectorMenu(target);
	      } else {
	        this.openTemplatesList();
	      }
	    },
	    openSelectorMenu: function openSelectorMenu(bindElement) {
	      main_popup.MenuManager.show({
	        id: 'payment-document-selector',
	        bindElement: bindElement,
	        items: this.prepareSelectorMenuItems(),
	        closeByEsc: true,
	        cacheable: false
	      });
	    },
	    closeSelectorMenu: function closeSelectorMenu() {
	      main_popup.MenuManager.destroy('payment-document-selector');
	    },
	    prepareSelectorMenuItems: function prepareSelectorMenuItems() {
	      var _this = this;
	      var items = [];
	      if (this.model.documents) {
	        var _iterator = _createForOfIteratorHelper(this.model.documents),
	          _step;
	        try {
	          var _loop = function _loop() {
	            var document = _step.value;
	            items.push({
	              text: document.title,
	              onclick: function onclick() {
	                _this.closeSelectorMenu();
	                _this.$store.dispatch('documentSelector/setBoundDocumentId', {
	                  boundDocumentId: document.id
	                });
	              }
	            });
	          };
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            _loop();
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      }
	      var templateListItem = {
	        text: main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_DOCUMENT'),
	        items: []
	      };
	      if (this.model.templates) {
	        if (items.length > 0) {
	          items.push({
	            delimiter: true
	          });
	        }
	        var _iterator2 = _createForOfIteratorHelper(this.model.templates),
	          _step2;
	        try {
	          var _loop2 = function _loop2() {
	            var template = _step2.value;
	            templateListItem.items.push({
	              text: template.title,
	              onclick: function onclick() {
	                _this.closeSelectorMenu();
	                _this.$store.commit('documentSelector/setSelectedTemplateId', {
	                  selectedTemplateId: template.id
	                });
	              }
	            });
	          };
	          for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	            _loop2();
	          }
	        } catch (err) {
	          _iterator2.e(err);
	        } finally {
	          _iterator2.f();
	        }
	      }
	      if (this.templateAddUrl) {
	        if (templateListItem.items.length > 0) {
	          templateListItem.items.push({
	            delimiter: true
	          });
	        }
	        templateListItem.items.push({
	          text: main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_CREATE_NEW_TEMPLATE'),
	          onclick: function onclick() {
	            _this.closeSelectorMenu();
	            _this.openTemplatesList();
	          }
	        });
	      }
	      if (templateListItem.items.length > 0) {
	        items.push(templateListItem);
	      }
	      return items;
	    },
	    openTemplatesList: function openTemplatesList() {
	      var _this2 = this;
	      salescenter_manager.Manager.openSlider(this.templateAddUrl, {
	        width: 930
	      }).then(function () {
	        _this2.$store.dispatch('documentSelector/loadTemplates');
	      });
	    },
	    handleEditDocumentClick: function handleEditDocumentClick() {
	      var _this3 = this;
	      var currentDocument = this.getCurrentDocument();
	      if (currentDocument.detailUrl) {
	        salescenter_manager.Manager.openSlider(currentDocument.detailUrl, {
	          width: 980
	        }).then(function (slider) {
	          var document = slider.getData().get('document');
	          if (document) {
	            _this3.$store.dispatch('documentSelector/addDocument', {
	              document: document
	            });
	          }
	        });
	      }
	    },
	    getCurrentDocument: function getCurrentDocument() {
	      var document = null;
	      if (this.model.boundDocumentId > 0) {
	        document = this.getDocumentById(this.model.boundDocumentId);
	      }
	      if (!document && this.model.selectedTemplateId > 0) {
	        document = this.getStubDocumentByTemplate(this.model.selectedTemplateId);
	      }
	      if (!document) {
	        var documents = this.model.documents;
	        if (documents && documents[0]) {
	          document = documents[0];
	          this.$store.dispatch('documentSelector/setBoundDocumentId', {
	            boundDocumentId: document.id
	          });
	        }
	      }
	      if (!document) {
	        var templates = this.model.templates;
	        if (templates && templates[0]) {
	          document = this.getStubDocumentByTemplate(templates[0].id);
	          this.$store.commit('documentSelector/setSelectedTemplateId', {
	            selectedTemplateId: templates[0].id
	          });
	        }
	      }
	      return document;
	    },
	    getDocumentById: function getDocumentById(id) {
	      var _iterator3 = _createForOfIteratorHelper(this.model.documents),
	        _step3;
	      try {
	        for (_iterator3.s(); !(_step3 = _iterator3.n()).done;) {
	          var document = _step3.value;
	          if (document.id === id) {
	            return document;
	          }
	        }
	      } catch (err) {
	        _iterator3.e(err);
	      } finally {
	        _iterator3.f();
	      }
	      return null;
	    },
	    getTemplateById: function getTemplateById(id) {
	      var _iterator4 = _createForOfIteratorHelper(this.model.templates),
	        _step4;
	      try {
	        for (_iterator4.s(); !(_step4 = _iterator4.n()).done;) {
	          var template = _step4.value;
	          if (template.id === id) {
	            return template;
	          }
	        }
	      } catch (err) {
	        _iterator4.e(err);
	      } finally {
	        _iterator4.f();
	      }
	      return null;
	    },
	    getStubDocumentByTemplate: function getStubDocumentByTemplate(templateId) {
	      var template = this.getTemplateById(templateId);
	      if (!template) {
	        return null;
	      }
	      var paymentId = this.$root.$app.options.paymentId || 0;
	      var title = null;
	      var detailUrl = null;
	      if (paymentId > 0) {
	        title = main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_DOCUMENT_NEW_SUFFIX', {
	          '#TITLE#': template.title
	        });
	        detailUrl = main_core.Uri.addParam(template.documentCreationUrl, {
	          values: {
	            _paymentId: paymentId
	          }
	        });
	      } else {
	        title = main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_DOCUMENT_CREATED_LATER_SUFFIX', {
	          '#TITLE#': template.title
	        });
	        detailUrl = null;
	      }
	      return {
	        id: 0,
	        title: title,
	        detailUrl: detailUrl,
	        isWithStamps: template.isWithStamps
	      };
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"statusClassMixin\"\n\t\t\t:config=\"configForBlock\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>".concat(main_core.Loc.getMessage('SALESCENTER_DOCUMENT_SELECTOR_BLOCK_TITLE'), "</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\t\t\t\t\t\t\t\t\t\t\n\t\t\t\t\t<div class=\"salescenter-app-payment-item-container-document-selector\">\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-item-container-document-selector-selector\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-payment-item-container-document-selector-selector-file\">\n\t\t\t\t\t\t\t\t<div class=\"ui-icon ui-icon-lg ui-icon-file-pdf\"><i></i></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-payment-item-container-document-selector-title\">\n\t\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\t\tref=\"selectorNode\"\n\t\t\t\t\t\t\t\t\tclass=\"salescenter-app-payment-item-container-document-selector-title-button\"\n\t\t\t\t\t\t\t\t\t@click=\"handleDocumentClick\"\n\t\t\t\t\t\t\t\t>{{getDocumentTitle}}</div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-payment-item-container-document-selector-title-sign\">{{withStamps}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"hasData\"\n\t\t\t\t\t\t\tclass=\"salescenter-app-payment-item-container-document-selector-edit\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<div \n\t\t\t\t\t\t\t\tclass=\"salescenter-app-payment-item-container-document-selector-edit-button\"\n\t\t\t\t\t\t\t\t@click=\"handleEditDocumentClick\"\n\t\t\t\t\t\t\t>\n                              <svg width=\"14\" height=\"14\" viewBox=\"0 0 14 14\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n                                <path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M11.4359 0.03479L13.9865 2.61221L4.00879 12.563L1.45822 9.98561L11.4359 0.03479ZM0.0256074 13.6726C0.00148857 13.7639 0.0273302 13.8603 0.0927957 13.9275C0.159984 13.9947 0.25646 14.0205 0.347767 13.9947L3.19896 13.2265L0.793965 10.8223L0.0256074 13.6726Z\" fill=\"#525C69\"/>\n                              </svg>\n                              ").concat(main_core.Loc.getMessage('SALESCENTER_RIGHT_ACTION_EDIT'), "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var StageBlocksList = {
	  components: {
	    'chat-message-block': ChatMessage,
	    'product-block': Product$1,
	    'paysystem-block': PaySystem,
	    'cashbox-block': Cashbox,
	    'delivery-block': DeliveryVuex,
	    'automation-block': Automation,
	    'send-block': Send,
	    'timeline-block': TimeLine,
	    'document-selector-block': DocumentSelector
	  },
	  data: function data() {
	    var stages = {
	      message: {
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        manager: this.$root.$app.options.entityResponsible,
	        titleTemplate: main_core.Loc.getMessage('SALESCENTER_APP_CHAT_MESSAGE_TITLE'),
	        showHint: this.$root.$app.options.templateMode !== 'view',
	        editorTemplate: this.$root.$app.sendingMethodDesc.text,
	        editorUrl: this.$root.$app.orderPublicUrl,
	        selectedMode: 'payment'
	      },
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current,
	        title: this.$root.$app.options.templateMode === 'view' ? main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW') : main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
	        hintTitle: this.$root.$app.options.templateMode === 'view' ? '' : main_core.Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT')
	      },
	      paysystem: {
	        status: this.$root.$app.options.paySystemList.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.paySystemList.items),
	        installed: this.$root.$app.options.paySystemList.isSet,
	        titleItems: this.getTitleItems(this.$root.$app.options.paySystemList.items),
	        initialCollapseState: this.$root.$app.options.isPaySystemCollapsed ? this.$root.$app.options.isPaySystemCollapsed === 'Y' : this.$root.$app.options.paySystemList.isSet
	      },
	      cashbox: {},
	      automation: {},
	      documentSelector: {
	        status: salescenter_component_stageBlock.StatusTypes.complete
	      }
	    };
	    if (this.$root.$app.options.hasOwnProperty('deliveryList')) {
	      stages.delivery = {
	        isHidden: this.$root.$app.options.templateMode === 'view' && parseInt(this.$root.$app.options.shipmentId) <= 0,
	        status: this.$root.$app.options.deliveryList.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.deliveryList.items),
	        installed: this.$root.$app.options.deliveryList.isInstalled,
	        initialCollapseState: this.$root.$app.options.isDeliveryCollapsed ? this.$root.$app.options.isDeliveryCollapsed === 'Y' : this.$root.$app.options.deliveryList.isInstalled
	      };
	    }
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
	        stageOnOrderPaid: this.$root.$app.options.stageOnOrderPaid,
	        stageOnDeliveryFinished: this.$root.$app.options.stageOnDeliveryFinished,
	        items: this.$root.$app.options.entityStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }
	    if (this.$root.$app.options.hasOwnProperty('timeline')) {
	      stages.timeline = {
	        items: this.getTimelineCollection(this.$root.$app.options.timeline)
	      };
	    }
	    if (this.$root.$app.hasOwnProperty('documentSelector') && this.$root.$app.documentSelector.templateAddUrl) {
	      stages.documentSelector.templateAddUrl = this.$root.$app.documentSelector.templateAddUrl;
	    }
	    return {
	      stages: stages
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    isSendAllowed: function isSendAllowed() {
	      return this.$store.getters['orderCreation/isAllowedSubmit'];
	    },
	    hasStageTimeLine: function hasStageTimeLine() {
	      return this.stages.timeline.hasOwnProperty('items') && this.stages.timeline.items.length > 0;
	    },
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    hasStageCashBox: function hasStageCashBox() {
	      return this.stages.cashbox.hasOwnProperty('tiles');
	    },
	    submitButtonLabel: function submitButtonLabel() {
	      var _this$$root$$app;
	      return this.editable && !((_this$$root$$app = this.$root.$app) !== null && _this$$root$$app !== void 0 && _this$$root$$app.compilation) ? main_core.Loc.getMessage('SALESCENTER_SEND') : main_core.Loc.getMessage('SALESCENTER_RESEND');
	    },
	    isFacebookForm: function isFacebookForm() {
	      var _this$$root$$app2, _this$$root$$app3;
	      return ((_this$$root$$app2 = this.$root.$app) === null || _this$$root$$app2 === void 0 ? void 0 : _this$$root$$app2.connector) === 'facebook' && ((_this$$root$$app3 = this.$root.$app) === null || _this$$root$$app3 === void 0 ? void 0 : _this$$root$$app3.isAllowedFacebookRegion);
	    },
	    isShowDocumentSelector: function isShowDocumentSelector() {
	      return this.$root.$app.hasOwnProperty('documentSelector');
	    }
	  },
	  methods: {
	    initCounter: function initCounter() {
	      this.counter = 1;
	    },
	    getTimelineCollection: function getTimelineCollection(items) {
	      var list = [];
	      Object.values(items).forEach(function (options) {
	        list.push(TimeLineItem.Factory.create(options));
	      });
	      return list;
	    },
	    getTileCollection: function getTileCollection(items) {
	      var tiles = [];
	      Object.values(items).forEach(function (options) {
	        tiles.push(Tile.Factory.create(options));
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
	      var _this = this;
	      main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getAjaxData', {
	        mode: 'class',
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          _this.refreshTilesByType(response.data, type);
	        }
	      }, function () {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR')
	        });
	      });
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      if (type === 'PAY_SYSTEM') {
	        this.stages.paysystem.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.paysystem.tiles = this.getTileCollection(data.items);
	        this.stages.paysystem.installed = data.isSet;
	        this.stages.paysystem.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'CASHBOX') {
	        this.stages.cashbox.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.cashbox.tiles = this.getTileCollection(data.items);
	        this.stages.cashbox.installed = data.isSet;
	        this.stages.cashbox.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'DELIVERY') {
	        this.stages.delivery.status = data.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.delivery.tiles = this.getTileCollection(data.items);
	        this.stages.delivery.installed = data.isInstalled;
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    onSendCompilationLinkToFacebook: function onSendCompilationLinkToFacebook(event) {
	      this.$emit('stage-block-send-on-send-compilation-link-to-facebook', event);
	    },
	    changeProvider: function changeProvider(value) {
	      this.$root.$app.sendingMethodDesc.provider = value;
	      BX.userOptions.save('salescenter', 'payment_sms_provider_options', 'latest_selected_provider', value);
	    },
	    saveCollapsedOption: function saveCollapsedOption(type, value) {
	      BX.userOptions.save('salescenter', 'add_payment_collapse_options', type, value);
	    },
	    onProductFormModeChange: function onProductFormModeChange() {
	      var isCompilationMode = this.$store.getters['orderCreation/isCompilationMode'];
	      if (isCompilationMode) {
	        this.stages.delivery.status = salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.message.selectedMode = 'compilation';
	        this.$root.$app.sendingMethodDesc.text = this.$root.$app.sendingMethodDesc.text_modes.compilation;
	        this.stages.message.editorTemplate = this.$root.$app.sendingMethodDesc.text_modes.compilation;
	      } else {
	        if (this.stages.delivery) {
	          this.stages.delivery.status = this.$root.$app.options.deliveryList.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        }
	        this.stages.message.selectedMode = 'payment';
	        this.$root.$app.sendingMethodDesc.text = this.$root.$app.sendingMethodDesc.text_modes.payment;
	        this.stages.message.editorTemplate = this.$root.$app.sendingMethodDesc.text_modes.payment;
	      }
	    }
	  },
	  created: function created() {
	    this.initCounter();
	  },
	  beforeUpdate: function beforeUpdate() {
	    this.initCounter();
	  },
	  template: "\n\t\t<div>\n\t\t\t<chat-message-block\n\t\t\t\tv-if=\"editable\"\n\t\t\t\t@stage-block-sms-send-on-change-provider=\"changeProvider\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.message.status\"\n\t\t\t\t:manager=\"stages.message.manager\"\n\t\t\t\t:titleTemplate=\"stages.message.titleTemplate\"\n\t\t\t\t:showHint=\"stages.message.showHint\"\n\t\t\t\t:editorTemplate=\"stages.message.editorTemplate\"\n\t\t\t\t:editorUrl=\"stages.message.editorUrl\"\n\t\t\t\t:selectedMode=\"stages.message.selectedMode\"\n\t\t\t/>\n\t\t\t<product-block\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\"stages.product.title\"\n\t\t\t\t:hintTitle=\"stages.product.hintTitle\"\n\t\t\t\t@on-product-form-mode-change=\"onProductFormModeChange\"\n\t\t\t/>\n\t\t\t<paysystem-block\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'PAY_SYSTEM')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.paysystem.status\"\n\t\t\t\t:tiles=\"stages.paysystem.tiles\"\n\t\t\t\t:installed=\"stages.paysystem.installed\"\n\t\t\t\t:titleItems=\"stages.paysystem.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.paysystem.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<cashbox-block\n\t\t\t\tv-if=\"hasStageCashBox\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'CASHBOX')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.cashbox.status\"\n\t\t\t\t:tiles=\"stages.cashbox.tiles\"\n\t\t\t\t:installed=\"stages.cashbox.installed\"\n\t\t\t\t:titleItems=\"stages.cashbox.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.cashbox.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<delivery-block\n\t\t\t\tv-if=\"stages.delivery && !stages.delivery.isHidden\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'DELIVERY')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.delivery.status\"\n\t\t\t\t:tiles=\"stages.delivery.tiles\"\n\t\t\t\t:installed=\"stages.delivery.installed\"\n\t\t\t\t:isCollapsible=\"true\"\n\t\t\t\t:initialCollapseState=\"stages.delivery.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<document-selector-block\n\t\t\t\tv-if=\"isShowDocumentSelector\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:templateAddUrl=\"stages.documentSelector.templateAddUrl\"\n\t\t\t/>\n\t\t\t<automation-block\n\t\t\t\tv-if=\"hasStageAutomation\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.automation.status\"\n\t\t\t\t:stageOnOrderPaid=\"stages.automation.stageOnOrderPaid\"\n\t\t\t\t:stageOnDeliveryFinished=\"stages.automation.stageOnDeliveryFinished\"\n\t\t\t\t:items=\"stages.automation.items\"\n\t\t\t\t:initialCollapseState=\"stages.automation.initialCollapseState\"\n\t\t\t\t:isDeliveryStageVisible=\"stages.delivery && stages.delivery.installed\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<send-block\n\t\t\t\t@on-submit=\"onSend\"\n\t\t\t\t@on-submit-compilation-link-to-facebook=\"onSendCompilationLinkToFacebook\"\n\t\t\t\t:buttonEnabled=\"isSendAllowed\"\n\t\t\t\t:buttonLabel=\"submitButtonLabel\"\n\t\t\t\t:isFacebookForm=\"isFacebookForm\"\n\t\t\t/>\n\t\t\t<timeline-block\n\t\t\t\tv-if=\"hasStageTimeLine\"\n\t\t\t\t:timelineItems=\"stages.timeline.items\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	var ComponentMixin = {
	  data: function data() {
	    return {
	      isFaded: false
	    };
	  },
	  mounted: function mounted() {
	    this.createPinner();
	  },
	  created: function created() {
	    var _this = this;
	    this.$root.$on('on-start-progress', function () {
	      _this.startFade();
	    });
	    this.$root.$on('on-stop-progress', function () {
	      _this.endFade();
	    });
	  },
	  methods: {
	    startFade: function startFade() {
	      this.isFaded = true;
	    },
	    endFade: function endFade() {
	      this.isFaded = false;
	    },
	    createPinner: function createPinner() {
	      var buttonsPanel = this.$refs['buttonsPanel'];
	      if (buttonsPanel) {
	        this.$root.$el.parentNode.appendChild(buttonsPanel);
	        new BX.UI.Pinner(buttonsPanel, {
	          fixBottom: this.$root.$app.isFrame,
	          fullWidth: this.$root.$app.isFrame,
	          anchorBottom: '.salescenter-app-pinner-anchor'
	        });
	      }
	    },
	    close: function close() {
	      this.$root.$app.closeApplication();
	    }
	  },
	  computed: {
	    isOrderPublicUrlAvailable: function isOrderPublicUrlAvailable() {
	      return this.$root.$app.isOrderPublicUrlAvailable;
	    },
	    compilation: function compilation() {
	      return this.$root.$app.compilation;
	    },
	    wrapperClass: function wrapperClass() {
	      return {
	        'salescenter-app-wrapper-fade': this.isFaded
	      };
	    },
	    wrapperStyle: function wrapperStyle() {
	      var position = BX.pos(this.$root.$el);
	      var offset = position.top + 20;
	      if (this.$root.$nodes.footer) {
	        offset += BX.pos(this.$root.$nodes.footer).height;
	      }
	      var buttonsPanel = this.$refs['buttonsPanel'];
	      if (buttonsPanel) {
	        offset += BX.pos(buttonsPanel).height;
	      }

	      //?auto
	      return {
	        'minHeight': 'calc(100vh - ' + offset + 'px)'
	      };
	    }
	  }
	};

	var Start = {
	  data: function data() {
	    return {};
	  },
	  methods: {
	    connect: function connect() {
	      var _this = this;
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
	            _this.$root.$app.isSiteExists = result.isSiteExists;
	            _this.$root.$app.isOrderPublicUrlExists = true;
	            _this.$root.$app.orderPublicUrl = result.orderPublicUrl;
	            _this.$root.$app.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
	          }
	          _this.$emit('on-successfully-connected');
	        });
	      })["catch"](function () {
	        loader.hide();
	      });
	    },
	    checkRecycle: function checkRecycle() {
	      salescenter_manager.Manager.openConnectedSite(true);
	    },
	    publishConnectedSite: function publishConnectedSite() {
	      this.$root.$app.publishShop();
	    },
	    confirmPhoneNumber: function confirmPhoneNumber() {
	      var _this2 = this;
	      main_core_events.EventEmitter.subscribeOnce('BX.Salescenter.App::onPhoneConfirmed', function () {
	        return _this2.$root.$app.publishShop();
	      });
	      this.$root.$app.confirmPhoneNumber();
	    }
	  },
	  computed: {
	    isOrderPageDeleted: function isOrderPageDeleted() {
	      return this.$root.$app.isSiteExists && !this.isOrderPublicUrlExists;
	    },
	    isOrderPublicUrlExists: function isOrderPublicUrlExists() {
	      return this.$root.$app.isOrderPublicUrlExists;
	    },
	    isPhoneConfirmed: function isPhoneConfirmed() {
	      return this.$root.$app.isPhoneConfirmed;
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-page-content salescenter-app-start-wrapper\">\n\t\t\t<div class=\"ui-title-1 ui-text-center ui-color-medium\" style=\"margin-bottom: 20px;\">\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_INFO_TEXT_TOP_2_MSGVER_1'), "\n\t\t\t</div>\n\t\t\t<div class=\"ui-hr ui-mv-25\"></div>\n\t\t\t<template v-if=\"isOrderPublicUrlExists && !isPhoneConfirmed\">\n\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_PHONE_CONFIRMATION_INFO_TEXT_BOTTOM_PUBLIC'), "\n\t\t\t\t</div>\n\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"confirmPhoneNumber\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_PHONE_CONFIRMATION_INFO_CONFIRM'), "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"isOrderPublicUrlExists\">\n\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC'), "\n\t\t\t\t</div>\n\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"publishConnectedSite\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_INFO_PUBLIC'), "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else-if=\"isOrderPageDeleted\">\n\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_INFO_ORDER_PAGE_DELETED'), "\n\t\t\t\t</div>\n\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t<div\n\t\t\t\t\t\t@click=\"checkRecycle\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-primary ui-btn-lg\"\n\t\t\t\t\t>\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CHECK_RECYCLE'), "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t<template v-else>\n\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">\n\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_INFO_TEXT_BOTTOM_2'), "\n\t\t\t\t</div>\n\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t<div\n\t\t\t\t\t\t@click=\"connect\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-primary ui-btn-lg\"\n\t\t\t\t\t>\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_INFO_CREATE'), "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t<div\n\t\t\t\t\t\t@click=\"BX.Salescenter.Manager.openHowPayDealWorks(event)\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-link ui-btn-lg\"\n\t\t\t\t\t>\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_HOW'), "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t")
	};

	var NoPaymentSystemsBanner = {
	  data: function data() {
	    return {
	      isVisible: true
	    };
	  },
	  methods: {
	    hide: function hide() {
	      this.isVisible = false;
	      this.$emit('on-hide');
	    },
	    openControlPanel: function openControlPanel() {
	      salescenter_manager.Manager.openControlPanel();
	    }
	  },
	  template: "\n\t\t<div v-if=\"isVisible\" class=\"salescenter-app-banner\" >\n\t\t\t<div class=\"salescenter-app-banner-inner\">\n\t\t\t\t<div class=\"salescenter-app-banner-title\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_BANNER_TITLE'), "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-banner-content\">\n\t\t\t\t\t<div class=\"salescenter-app-banner-text\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_BANNER_TEXT'), "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-btn-block\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t@click=\"openControlPanel\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_BANNER_BTN_CONFIGURE'), "\n\t\t\t\t\t\t</button>\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t@click=\"hide\"\n\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_BANNER_BTN_HIDE'), "\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div\n\t\t\t\t\t@click=\"hide\"\n\t\t\t\t\tclass=\"salescenter-app-banner-close\"\n\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	};

	function ownKeys$5(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$5(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$5(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$5(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	function _createForOfIteratorHelper$1(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$1(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$1(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$1(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$1(o, minLen); }
	function _arrayLikeToArray$1(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var Chat = {
	  mixins: [MixinTemplatesType, ComponentMixin],
	  data: function data() {
	    return {
	      isShowPreview: false,
	      isShowPayment: false,
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
	      paymentsCount: null,
	      editedPageId: null,
	      currentPageTitle: null,
	      ModeDictionary: ModeDictionary
	    };
	  },
	  components: {
	    'product': Product,
	    'start': Start,
	    'no-payment-systems-banner': NoPaymentSystemsBanner,
	    'chat-receiving-payment': StageBlocksList
	  },
	  updated: function updated() {
	    this.renderErrors();
	  },
	  mounted: function mounted() {
	    var _this = this;
	    this.createLoader();
	    this.$root.$app.fillPages().then(function () {
	      if (_this.$root.$app.isWithOrdersMode) {
	        _this.refreshOrdersCount();
	      } else {
	        _this.refreshPaymentsCount();
	      }
	      _this.openFirstPage();
	    });
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
	  },
	  methods: {
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
	      var _this2 = this;
	      var isWebform = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      return [{
	        text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_SITE_B24,
	        onclick: function onclick() {
	          _this2.addSite(isWebform);
	        }
	      }, {
	        text: this.localize.SALESCENTER_RIGHT_ACTION_ADD_CUSTOM,
	        onclick: function onclick() {
	          _this2.showAddUrlPopup({
	            isWebform: isWebform === true ? 'Y' : null
	          });
	        }
	      }];
	    },
	    openFirstPage: function openFirstPage() {
	      this.isShowPayment = false;
	      this.isShowPreview = true;
	      if (this.$root.$app.isPaymentCreationAvailable) {
	        this.showPaymentForm();
	      } else if (this.pages && this.pages.length > 0) {
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
	      var _this3 = this;
	      var isWebform = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      salescenter_manager.Manager.addSitePage(isWebform).then(function (result) {
	        var newPage = result.answer.result.page || false;
	        _this3.$root.$app.fillPages().then(function () {
	          if (newPage) {
	            _this3.onPageClick(newPage);
	            _this3.lastAddedPages.push(parseInt(newPage.id));
	          } else {
	            _this3.openFirstPage();
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
	      var _this4 = this;
	      if (this.currentPage) {
	        this.$root.$app.hidePage(this.currentPage).then(function () {
	          _this4.openFirstPage();
	        });
	        this.hideActionsPopup();
	      }
	    },
	    hideNoPaymentSystemsBanner: function hideNoPaymentSystemsBanner() {
	      this.$root.$app.hideNoPaymentSystemsBanner();
	    },
	    showAddUrlPopup: function showAddUrlPopup(newPage) {
	      var _this5 = this;
	      if (!main_core.Type.isPlainObject(newPage)) {
	        newPage = {};
	      }
	      salescenter_manager.Manager.addCustomPage(newPage).then(function (pageId) {
	        if (!_this5.isShowPreview) {
	          _this5.isShowPreview = false;
	        }
	        _this5.$root.$app.fillPages().then(function () {
	          if (pageId && (!main_core.Type.isPlainObject(newPage) || !newPage.id)) {
	            _this5.lastAddedPages.push(parseInt(pageId));
	          }
	          if (!pageId && newPage) {
	            pageId = newPage.id;
	          }
	          if (pageId) {
	            _this5.pages.forEach(function (page) {
	              if (parseInt(page.id) === parseInt(pageId)) {
	                _this5.onPageClick(page);
	              }
	            });
	          } else {
	            if (!_this5.isShowPayment) {
	              _this5.isShowPreview = true;
	            }
	          }
	        });
	      });
	      this.hideActionsPopup();
	    },
	    getPaymentItemTitle: function getPaymentItemTitle() {
	      if (!this.isOrderPublicUrlAvailable) {
	        return null;
	      }
	      if (this.$root.$app.options.mode === 'payment') {
	        return this.localize.SALESCENTER_LEFT_PAYMENT_ADD_2_MSGVER_1;
	      }
	      return this.localize.SALESCENTER_LEFT_PAYMENT_AND_DELIVERY_MSGVER_1;
	    },
	    showPaymentForm: function showPaymentForm() {
	      this.isShowPayment = true;
	      this.isShowPreview = false;
	      if (this.compilation) {
	        return;
	      }
	      var title = this.getPaymentItemTitle() || this.localize.SALESCENTER_DEFAULT_TITLE;
	      this.setPageTitle(title);
	    },
	    showOrdersList: function showOrdersList() {
	      var _this6 = this;
	      this.hideActionsPopup();
	      salescenter_manager.Manager.showOrdersList({
	        context: this.$root.$app.context,
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this6.refreshOrdersCount();
	      });
	    },
	    showPaymentsList: function showPaymentsList() {
	      var _this7 = this;
	      this.hideActionsPopup();
	      salescenter_manager.Manager.showPaymentsList({
	        context: this.$root.$app.context,
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this7.refreshPaymentsCount();
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
	    sendCompilationLinkToFacebook: function sendCompilationLinkToFacebook(event) {
	      this.send(event, 'n', true);
	    },
	    send: function send(event) {
	      var skipPublicMessage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'n';
	      var sendCompilationLinkToFacebook = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      if (!this.isAllowedSubmitButton) {
	        return;
	      }
	      if (this.isShowPayment && !this.isShowStartInfo) {
	        if (this.$store.getters['orderCreation/isCompilationMode']) {
	          this.$root.$app.sendCompilation(event.target, sendCompilationLinkToFacebook);
	        } else {
	          this.$root.$app.sendPayment(event.target, skipPublicMessage);
	        }
	      } else if (this.currentPage && this.currentPage.isActive) {
	        this.$root.$app.sendPage(this.currentPage.id);
	      }
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
	    onSuccessfullyConnected: function onSuccessfullyConnected() {
	      var _this10 = this;
	      this.$root.$app.fillPages().then(function () {
	        _this10.openFirstPage();
	      });
	    },
	    startFrameCheckTimeout: function startFrameCheckTimeout() {
	      var _this11 = this;
	      // this is a workaround for denied through X-Frame-Options sources
	      if (this.frameCheckShortTimeout) {
	        clearTimeout(this.frameCheckShortTimeout);
	        this.frameCheckShortTimeout = false;
	      }
	      this.frameCheckShortTimeout = setTimeout(function () {
	        _this11.frameCheckShortTimeout = false;
	      }, 500);

	      // to show error on long loading
	      clearTimeout(this.frameCheckLongTimeout);
	      this.frameCheckLongTimeout = setTimeout(function () {
	        if (_this11.currentPage && _this11.showedPageIds.includes(_this11.currentPage.id) && !_this11.loadedPageIds.includes(_this11.currentPage.id)) {
	          _this11.errorPageIds.push(_this11.currentPage.id);
	        }
	      }, 5000);
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
	      })["catch"](function () {
	        _this12.ordersCount = null;
	      });
	    },
	    refreshPaymentsCount: function refreshPaymentsCount() {
	      var _this13 = this;
	      this.$root.$app.getPaymentsCount().then(function (result) {
	        _this13.paymentsCount = result.answer.result || null;
	      })["catch"](function () {
	        _this13.paymentsCount = null;
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
	      var _this14 = this;
	      var pageId = this.editedPageId;
	      var name = event.target.value;
	      var oldName;
	      this.pages.forEach(function (page) {
	        if (page.id === _this14.editedPageId) {
	          oldName = page.name;
	        }
	      });
	      if (pageId > 0 && oldName && name !== oldName && name.length > 0) {
	        salescenter_manager.Manager.addPage({
	          id: pageId,
	          name: name,
	          analyticsLabel: 'salescenterUpdatePageTitle'
	        }).then(function () {
	          _this14.$root.$app.fillPages().then(function () {
	            if (_this14.editedPageId === _this14.currentPageId) {
	              _this14.setPageTitle(name);
	            }
	            _this14.editedPageId = null;
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
	  computed: _objectSpread$5({
	    ModeDictionary: function ModeDictionary$$1() {
	      return ModeDictionary;
	    },
	    config: function (_config) {
	      function config() {
	        return _config.apply(this, arguments);
	      }
	      config.toString = function () {
	        return _config.toString();
	      };
	      return config;
	    }(function () {
	      return config;
	    }),
	    currentPage: function currentPage() {
	      var _this15 = this;
	      if (this.currentPageId > 0) {
	        var pages = this.application.pages.filter(function (page) {
	          return page.id === _this15.currentPageId;
	        });
	        if (pages.length > 0) {
	          return pages[0];
	        }
	      }
	      return null;
	    },
	    sendButtonLabel: function sendButtonLabel() {
	      var _this$$root$$app;
	      return this.editable && !((_this$$root$$app = this.$root.$app) !== null && _this$$root$$app !== void 0 && _this$$root$$app.compilation) ? main_core.Loc.getMessage('SALESCENTER_SEND') : main_core.Loc.getMessage('SALESCENTER_RESEND');
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
	      } else if (this.isShowPayment) {
	        res = !this.isOrderPublicUrlAvailable;
	      }
	      return res;
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
	      return babelHelpers.toConsumableArray(this.application.pages);
	    },
	    isAllowedSubmitButton: function isAllowedSubmitButton() {
	      if (this.$root.$app.disableSendButton) {
	        return false;
	      }
	      if (this.isShowPreview && this.currentPage && !this.currentPage.isActive) {
	        return false;
	      }
	      if (this.isShowPayment) {
	        return this.$store.getters['orderCreation/isAllowedSubmit'];
	      }
	      return this.currentPage;
	    },
	    showSubmitCompilationLinkToFacebookButton: function showSubmitCompilationLinkToFacebookButton() {
	      var isCompilationMode = this.$store.getters['orderCreation/isCompilationMode'];
	      return this.$root.$app.connector === 'facebook' && this.$root.$app.isAllowedFacebookRegion && isCompilationMode;
	    },
	    isNoPaymentSystemsBannerVisible: function isNoPaymentSystemsBannerVisible() {
	      return this.$root.$app.options.showPaySystemSettingBanner;
	    },
	    mode: function mode() {
	      return this.$root.$app.options.mode;
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  template: "\n\t\t<div\n\t\t\t:class=\"wrapperClass\"\n\t\t\t:style=\"wrapperStyle\"\n\t\t\tclass=\"salescenter-app-wrapper salescenter-app-chat-wrapper\"\n\t\t>\n\t\t\t<div class=\"ui-sidepanel-sidebar salescenter-app-sidebar\" ref=\"sidebar\">\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\">\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable && !this.compilation\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{getPaymentItemTitle()}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.compilation\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{this.compilation.TITLE_TAB}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isPagesOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"isPagesOpen = !isPagesOpen;\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAGES}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: pagesSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"!page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isWithOrdersMode\" @click=\"showOrdersList\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDERS}}</div>\n\t\t\t\t\t\t\t<span class=\"ui-sidepanel-counter\" ref=\"ordersCounter\" v-show=\"ordersCount > 0\">{{ordersCount}}</span>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isWithOrdersMode\" @click=\"showOrderAdd\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDER_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"!this.$root.$app.isWithOrdersMode\" @click=\"showPaymentsList\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENTS}}</div>\n\t\t\t\t\t\t\t<span class=\"ui-sidepanel-counter\" ref=\"paymentsCounter\" v-show=\"paymentsCount > 0\">{{paymentsCount}}</span>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isCatalogAvailable\" @click=\"showCatalog\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_CATALOG}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isFormsOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"onFormsClick();\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_FORMS_ALL}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isFormsOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: formsSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t :class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event, true)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-right-side\">\n\t\t\t\t<div class=\"salescenter-app-page-header\" v-show=\"isShowPreview && !isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-dropdown ui-btn-xs\" @click=\"showActionsPopup($event)\">{{localize.SALESCENTER_RIGHT_ACTIONS_BUTTON}}</div>\n\t\t\t\t\t<div class=\"salescenter-btn-delimiter salescenter-btn-action\"></div>\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-xs ui-btn-icon-edit\" @click=\"editPage\">{{localize.SALESCENTER_RIGHT_ACTION_EDIT}}</div>\n\t\t\t\t</div>\n\t\t\t\t<start\n\t\t\t\t\tv-if=\"isShowStartInfo && mode !== ModeDictionary.terminalPayment\"\n\t\t\t\t\t@on-successfully-connected=\"onSuccessfullyConnected\"\n\t\t\t\t>\n\t\t\t\t</start>\n\t\t\t\t<template v-else-if=\"isFrameError && isShowPreview\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-lost\">\n\t\t\t\t\t\t<div class=\"salescenter-app-lost-block ui-title-1 ui-text-center ui-color-medium\">{{localize.SALESCENTER_ERROR_TITLE}}</div>\n\t\t\t\t\t\t<div v-if=\"currentPage.isFrameDenied === true\" class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_RIGHT_FRAME_DENIED}}</div>\n\t\t\t\t\t\t<div v-else-if=\"currentPage.isActive !== true\" class=\"salescenter-app-lost-helper salescenter-app-not-active ui-color-medium\">{{localize.SALESCENTER_RIGHT_NOT_ACTIVE}}</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_ERROR_TEXT}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div v-show=\"isShowPreview && !isShowStartInfo && !isFrameError\" class=\"salescenter-app-page-content\">\n\t\t\t\t\t<template v-for=\"page in pages\">\n\t\t\t\t\t\t<iframe class=\"salescenter-app-demo\" v-show=\"currentPage && currentPage.id == page.id\" :src=\"getFrameSource(page)\" frameborder=\"0\" @error=\"onFrameError(page.id)\" @load=\"onFrameLoad(page.id)\" :key=\"page.id\"></iframe>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"salescenter-app-demo-overlay\" :class=\"{\n\t\t\t\t\t\t'salescenter-app-demo-overlay-loading': this.isShowLoader\n\t\t\t\t\t}\">\n\t\t\t\t\t\t<div v-show=\"isShowLoader\" ref=\"previewLoader\"></div>\n\t\t\t\t\t\t<div v-if=\"lastModified\" class=\"salescenter-app-demo-overlay-modification\">{{lastModified}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t    <template v-if=\"this.$root.$app.isPaymentsLimitReached\">\n\t\t\t        <div ref=\"paymentsLimit\" v-show=\"isShowPayment && !isShowStartInfo\"></div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<chat-receiving-payment\n\t\t\t\t\t\tv-if=\"isShowPayment && !isShowStartInfo\"\n\t\t\t\t\t\t:key=\"order.basketVersion\"\n\t\t\t\t\t\t@stage-block-send-on-send=\"send($event)\"\n\t\t\t\t\t\t@stage-block-send-on-send-compilation-link-to-facebook=\"sendCompilationLinkToFacebook($event)\"\n\t\t\t\t\t/>\n\t\t        </template>\n\t\t\t</div>\n\t\t\t<div class=\"ui-button-panel-wrapper salescenter-button-panel\" ref=\"buttonsPanel\">\n\t\t\t\t<div class=\"ui-button-panel\">\n\t\t\t\t\t<button :class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\">{{sendButtonLabel}}</button>\n\t\t\t\t\t<button\n\t\t\t\t\t\tv-if=\"showSubmitCompilationLinkToFacebookButton\"\n\t\t\t\t\t\t:class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\"\n\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-light-border\"\n\t\t\t\t\t\t@click=\"sendCompilationLinkToFacebook($event)\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{localize.SALESCENTER_SEND_COMPILATION_LINK_TO_FACEBOOK}}\n\t\t\t\t\t</button>\n\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"close\">{{localize.SALESCENTER_CANCEL}}</button>\n\t\t\t\t\t<button v-if=\"isShowPayment && !isShowStartInfo && !this.$root.$app.isPaymentsLimitReached && this.$root.$app.isWithOrdersMode\" class=\"ui-btn ui-btn-md ui-btn-link btn-send-crm\" @click=\"send($event, 'y')\">{{localize.SALESCENTER_SAVE_ORDER}}</button>\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"this.order.errors.length > 0\" ref=\"errorBlock\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	function _createForOfIteratorHelper$2(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray$2(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray$2(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray$2(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray$2(o, minLen); }
	function _arrayLikeToArray$2(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	var TYPE_PHONE = 'phone';
	var TYPE_SENDER = 'sender';
	var SmsMessage = {
	  props: {
	    initSenders: {
	      type: Array,
	      required: true
	    },
	    initCurrentSenderCode: {
	      type: String,
	      required: false
	    },
	    initPushedToUseBitrix24Notifications: {
	      type: String,
	      required: false
	    },
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    manager: {
	      type: Object,
	      required: true
	    },
	    selectedSmsSender: {
	      type: String,
	      required: false
	    },
	    phone: {
	      type: String,
	      required: true
	    },
	    contactEditorUrl: {
	      type: String,
	      required: true
	    },
	    ownerTypeId: {
	      type: Number,
	      required: true
	    },
	    ownerId: {
	      type: Number,
	      required: true
	    },
	    titleTemplate: {
	      type: String,
	      required: true
	    },
	    showHint: {
	      type: Boolean,
	      required: true
	    },
	    editorTemplate: {
	      type: String,
	      required: true
	    },
	    editorUrl: {
	      type: String,
	      required: true
	    },
	    selectedMode: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block,
	    'sms-error-block': salescenter_component_stageBlock_smsMessage.Error,
	    'sms-sender-list-block': salescenter_component_stageBlock_smsMessage.SenderList,
	    'sms-user-avatar-block': salescenter_component_stageBlock_smsMessage.UserAvatar,
	    'sms-message-edit-block': salescenter_component_stageBlock_smsMessage.MessageEdit,
	    'sms-message-view-block': salescenter_component_stageBlock_smsMessage.MessageView,
	    'sms-message-editor-block': salescenter_component_stageBlock_smsMessage.MessageEditor,
	    'sms-message-control-block': salescenter_component_stageBlock_smsMessage.MessageControl
	  },
	  data: function data() {
	    return {
	      contactPhone: this.phone,
	      currentSenderCode: null,
	      senders: [],
	      pushedToUseBitrix24Notifications: null,
	      smsSenderListComponentKey: 0
	    };
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        showHint: true,
	        hintClassModifier: 'salescenter-app-payment-by-sms-item-title-info--link-gray'
	      };
	    },
	    editor: function editor() {
	      return {
	        template: this.editorTemplate,
	        url: this.editorUrl
	      };
	    },
	    currentSender: function currentSender() {
	      var _this = this;
	      return this.senders.find(function (sender) {
	        return sender.code === _this.currentSenderCode;
	      });
	    },
	    title: function title() {
	      return this.titleTemplate.replace('#PHONE#', this.contactPhone);
	    },
	    errors: function errors() {
	      var _this2 = this;
	      var result = [];
	      var bitrix24ConnectUrlError;
	      if (!this.currentSender) {
	        var _iterator = _createForOfIteratorHelper$2(this.senders),
	          _step;
	        try {
	          for (_iterator.s(); !(_step = _iterator.n()).done;) {
	            var sender = _step.value;
	            if (!salescenter_lib.SenderConfig.needConfigure(sender)) {
	              continue;
	            }
	            result.push({
	              text: this.getConnectionErrorText(sender),
	              fixer: salescenter_lib.SenderConfig.openSliderFreeMessages(sender.connectUrl),
	              fixText: this.getConnectionErrorFixText(sender),
	              type: TYPE_SENDER
	            });
	            if (sender.code === salescenter_lib.SenderConfig.BITRIX24) {
	              bitrix24ConnectUrlError = sender.connectUrl;
	            }
	          }
	        } catch (err) {
	          _iterator.e(err);
	        } finally {
	          _iterator.f();
	        }
	      } else {
	        if (!this.currentSender.isAvailable) {
	          result.push({
	            text: main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_' + this.currentSender.code.toUpperCase() + '_NOT_AVAILABLE'),
	            type: TYPE_SENDER
	          });
	        } else {
	          if (this.currentSender.isConnected) {
	            result = this.currentSender.usageErrors.map(function (error) {
	              return {
	                text: error,
	                type: TYPE_SENDER
	              };
	            });
	          } else {
	            result.push({
	              text: this.getConnectionErrorText(this.currentSender),
	              fixer: this.getFixer(this.currentSender.connectUrl),
	              fixText: this.getConnectionErrorFixText(this.currentSender),
	              type: TYPE_SENDER
	            });
	            if (this.currentSender.code === salescenter_lib.SenderConfig.BITRIX24) {
	              bitrix24ConnectUrlError = this.currentSender.connectUrl;
	            }
	          }
	        }
	      }
	      if (!this.contactPhone) {
	        if (this.contactEditorUrl.length > 0) {
	          result.push({
	            text: main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'),
	            fixer: this.getFixer(this.contactEditorUrl),
	            fixText: main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY_SETTINGS'),
	            type: TYPE_PHONE
	          });
	        } else {
	          result.push({
	            text: main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'),
	            type: TYPE_PHONE
	          });
	        }
	      }
	      if (this.pushedToUseBitrix24Notifications === 'N' && bitrix24ConnectUrlError) {
	        this.getFixer(bitrix24ConnectUrlError)().then(function () {
	          return _this2.handleErrorFix();
	        });
	        BX.userOptions.save('salescenter', 'payment_sender_options', 'pushed_to_use_bitrix24_notifications', 'Y');
	        this.pushedToUseBitrix24Notifications = 'Y';
	      }
	      return result;
	    }
	  },
	  created: function created() {
	    this.initialize(this.initCurrentSenderCode, this.initSenders, this.initPushedToUseBitrix24Notifications);
	  },
	  methods: {
	    onConfigureContactPhone: function onConfigureContactPhone() {
	      this.$emit('stage-block-sms-message-on-change-contact-phone');
	    },
	    getConnectionErrorText: function getConnectionErrorText(sender) {
	      var messageCode = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED_WARNING';
	      var fallback = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED';
	      return main_core.Loc.getMessage(messageCode) || main_core.Loc.getMessage(fallback);
	    },
	    getConnectionErrorFixText: function getConnectionErrorFixText(sender) {
	      var messageCode = 'SALESCENTER_SEND_ORDER_BY_SMS_' + sender.code.toUpperCase() + '_NOT_CONNECTED_FIX';
	      var fallback = 'SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE';
	      return main_core.Loc.getMessage(messageCode) || main_core.Loc.getMessage(fallback);
	    },
	    getFixer: function getFixer(fixUrl) {
	      return function () {
	        if (typeof fixUrl === 'string') {
	          return salescenter_manager.Manager.openSlider(fixUrl, {
	            events: {
	              onLoad: function onLoad(event) {
	                var slider = event.getSlider();
	                var sliderBx = slider.getFrameWindow().BX;
	                sliderBx.addCustomEvent("BX.Crm.EntityEditor:onNothingChanged", function () {
	                  return slider.close();
	                });
	                sliderBx.addCustomEvent("BX.Crm.EntityEditor:onCancel", function () {
	                  return slider.close();
	                });
	                sliderBx.addCustomEvent("onCrmEntityUpdate", function () {
	                  return slider.close();
	                });
	              }
	            }
	          });
	        }
	        if (babelHelpers["typeof"](fixUrl) === 'object' && fixUrl !== null) {
	          if (fixUrl.type === 'ui_helper') {
	            return BX.loadExt('ui.info-helper').then(function () {
	              BX.UI.InfoHelper.show(fixUrl.value);
	            });
	          }
	        }
	        return Promise.resolve();
	      };
	    },
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {
	        width: 1200
	      });
	    },
	    initialize: function initialize(currentSenderCode, senders, pushedToUseBitrix24Notifications) {
	      this.currentSenderCode = currentSenderCode;
	      this.senders = senders;
	      this.pushedToUseBitrix24Notifications = pushedToUseBitrix24Notifications;
	      this.$root.$app.options.currentSenderCode = this.currentSenderCode;
	      this.$root.$app.options.senders = this.senders;
	      this.$root.$app.options.pushedToUseBitrix24Notifications = this.pushedToUseBitrix24Notifications;
	      this.$store.commit('orderCreation/setIsSenderSelected', this.currentSenderCode !== '');
	    },
	    handleOnSmsSenderSelected: function handleOnSmsSenderSelected(value) {
	      this.$emit('stage-block-sms-send-on-change-provider', value);
	    },
	    handleErrorFix: function handleErrorFix() {
	      var _this3 = this;
	      main_core.ajax.runComponentAction("bitrix:salescenter.app", "refreshSenderSettings", {
	        mode: "class"
	      }).then(function (resolve) {
	        if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0) {
	          _this3.initialize(resolve.data.currentSenderCode, resolve.data.senders, resolve.data.pushedToUseBitrix24Notifications);
	          _this3.smsSenderListComponentKey += 1;
	        }
	      });
	    },
	    handlePhoneErrorFix: function handlePhoneErrorFix() {
	      var _this4 = this;
	      main_core.ajax.runComponentAction("bitrix:salescenter.app", "refreshContactPhone", {
	        mode: "class",
	        data: {
	          fields: {
	            ownerId: this.ownerId,
	            ownerTypeId: this.ownerTypeId
	          }
	        }
	      }).then(function (resolve) {
	        if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0) {
	          if (_this4.contactPhone != resolve.data.contactPhone) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_PHONE_CHANGE', {
	                '#TITLE#': resolve.data.title
	              })
	            });
	          }
	          _this4.contactPhone = resolve.data.contactPhone;
	          _this4.onConfigureContactPhone();
	        }
	      });
	    },
	    hendleSmsErrorBlock: function hendleSmsErrorBlock(event) {
	      if (event.data.type === TYPE_PHONE) {
	        this.handlePhoneErrorFix();
	      } else {
	        this.handleErrorFix();
	      }
	    },
	    openBitrix24NotificationsHelp: function openBitrix24NotificationsHelp(event) {
	      BX.Salescenter.Manager.openBitrix24NotificationsHelp(event);
	    },
	    openBitrix24NotificationsWorks: function openBitrix24NotificationsWorks(event) {
	      BX.Salescenter.Manager.openBitrix24NotificationsWorks(event);
	    }
	  },
	  //language=Vue
	  template: "\n\t\t<stage-block-item\t\t\t\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t\tv-on:on-item-hint=\"onItemHint\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{title}}</template>\n\t\t\t<template\n\t\t\t\tv-if=\"showHint\"\n\t\t\t\tv-slot:block-hint-title\n\t\t\t>\n\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS_V3'), "\n\t\t\t</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\" class=\"salescenter-app-payment-by-sms-item-container-offtop\">\n\t\t\t\t\t<sms-error-block\n\t\t\t\t\t\tv-for=\"(error, index) in errors\"\n\t\t\t\t\t\tv-bind:key=\"index\"\n\t\t\t\t\t\tv-on:on-configure=\"hendleSmsErrorBlock($event)\"\n\t\t\t\t\t\t:error=\"error\"\n\t\t\t\t\t>\n\t\t\t\t\t</sms-error-block>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms\">\n\t\t\t\t\t\t<sms-user-avatar-block :manager=\"manager\"/>\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content\">\n\t\t\t\t\t\t\t<div v-if=\"currentSenderCode === 'bitrix24'\" class=\"salescenter-app-payment-by-sms-item-container-sms-content\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content-message\">\n\t\t\t\t\t\t\t\t\t<div contenteditable=\"false\" class=\"salescenter-app-payment-by-sms-item-container-sms-content-message-text\">\n\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_TEMPLATE_BASED_MESSAGE_WILL_BE_SENT'), "\n\t\t\t\t\t\t\t\t\t\t<a @click.stop.prevent=\"openBitrix24NotificationsHelp(event)\" href=\"#\">\n\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_MORE_DETAILS'), "\n\t\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<sms-message-editor-block v-else :editor=\"editor\" :selectedMode=\"selectedMode\"/>\n\t\t\t\t\t\t\t<template v-if=\"currentSenderCode === 'bitrix24'\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-sms-content-info\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_VIA_BITRIX24'), "\n\t\t\t\t\t\t\t\t\t<span @click=\"openBitrix24NotificationsWorks(event)\">\n\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT'), "\n\t\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else-if=\"currentSenderCode === 'sms_provider'\">\n\t\t\t\t\t\t\t\t<sms-sender-list-block\n\t\t\t\t\t\t\t\t\t:key=\"smsSenderListComponentKey\"\n\t\t\t\t\t\t\t\t\t:list=\"currentSender.smsSenders\"\n\t\t\t\t\t\t\t\t\t:initSelected=\"selectedSmsSender\"\n\t\t\t\t\t\t\t\t\t:settingUrl=\"currentSender.connectUrl\"\n\t\t\t\t\t\t\t\t\tv-on:on-configure=\"handleErrorFix\"\n\t\t\t\t\t\t\t\t\tv-on:on-selected=\"handleOnSmsSenderSelected\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<template v-slot:sms-sender-list-text-send-from>\n\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER'), "\n\t\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t\t</sms-sender-list-block>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var StageBlocksList$1 = {
	  components: {
	    'send-block': Send,
	    'cashbox-block': Cashbox,
	    'product-block': Product$1,
	    'delivery-block': DeliveryVuex,
	    'paysystem-block': PaySystem,
	    'automation-block': Automation,
	    'sms-message-block': SmsMessage,
	    'timeline-block': TimeLine
	  },
	  props: {
	    sendAllowed: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    var stages = {
	      message: {
	        initSenders: this.$root.$app.options.senders,
	        initCurrentSenderCode: this.$root.$app.options.currentSenderCode,
	        initPushedToUseBitrix24Notifications: this.$root.$app.options.pushedToUseBitrix24Notifications,
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        selectedSmsSender: this.$root.$app.sendingMethodDesc.provider,
	        manager: this.$root.$app.options.entityResponsible,
	        phone: this.$root.$app.options.contactPhone,
	        ownerId: this.$root.$app.options.ownerId,
	        ownerTypeId: this.$root.$app.options.ownerTypeId,
	        contactEditorUrl: this.$root.$app.options.contactEditorUrl,
	        titleTemplate: this.$root.$app.sendingMethodDesc.sent ? main_core.Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2_PAST_TIME') : main_core.Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2'),
	        showHint: this.$root.$app.options.templateMode !== 'view',
	        editorTemplate: this.$root.$app.sendingMethodDesc.text,
	        editorUrl: this.$root.$app.orderPublicUrl,
	        selectedMode: 'payment'
	      },
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current,
	        title: this.$root.$app.options.templateMode === 'view' ? main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW') : main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
	        hintTitle: this.$root.$app.options.templateMode === 'view' ? '' : main_core.Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT')
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
	      },
	      automation: {}
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
	        stageOnOrderPaid: this.$root.$app.options.stageOnOrderPaid,
	        stageOnDeliveryFinished: this.$root.$app.options.stageOnDeliveryFinished,
	        items: this.$root.$app.options.entityStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }
	    if (this.$root.$app.options.hasOwnProperty('timeline')) {
	      stages.timeline = {
	        items: this.getTimelineCollection(this.$root.$app.options.timeline)
	      };
	    }
	    if (this.$root.$app.options.paySystemList.groups) {
	      stages.paysystem.groups = this.getTileGroupsCollection(this.$root.$app.options.paySystemList.groups, stages.paysystem.tiles);
	    }
	    return {
	      stages: stages
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    hasStageTimeLine: function hasStageTimeLine() {
	      return this.stages.timeline.hasOwnProperty('items') && this.stages.timeline.items.length > 0;
	    },
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    hasStageCashBox: function hasStageCashBox() {
	      return this.stages.cashbox.hasOwnProperty('tiles');
	    },
	    submitButtonLabel: function submitButtonLabel() {
	      return this.editable ? main_core.Loc.getMessage('SALESCENTER_SEND') : main_core.Loc.getMessage('SALESCENTER_RESEND');
	    },
	    isHideDeliveryStage: function isHideDeliveryStage() {
	      return this.isViewWithoutDelivery();
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
	    getTileGroupsCollection: function getTileGroupsCollection(groups, tiles) {
	      var ret = [];
	      if (Array.isArray(groups)) {
	        Object.values(groups).forEach(function (item) {
	          var group = new Tile.Group(item);
	          group.fillTiles(tiles);
	          ret.push(group);
	        });
	      }
	      return ret;
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
	      var _this = this;
	      main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getAjaxData', {
	        mode: 'class',
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          _this.refreshTilesByType(response.data, type);
	        }
	      }, function () {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR')
	        });
	      });
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      if (type === 'PAY_SYSTEM') {
	        this.stages.paysystem.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.paysystem.tiles = this.getTileCollection(data.items);
	        this.stages.paysystem.groups = this.getTileGroupsCollection(data.groups, this.stages.paysystem.tiles);
	        this.stages.paysystem.installed = data.isSet;
	        this.stages.paysystem.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'CASHBOX') {
	        this.stages.cashbox.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.cashbox.tiles = this.getTileCollection(data.items);
	        this.stages.cashbox.installed = data.isSet;
	        this.stages.cashbox.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'DELIVERY') {
	        this.stages.delivery.status = data.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.delivery.tiles = this.getTileCollection(data.items);
	        this.stages.delivery.installed = data.isInstalled;
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    changeProvider: function changeProvider(value) {
	      this.$root.$app.sendingMethodDesc.provider = value;
	      BX.userOptions.save('salescenter', 'payment_sms_provider_options', 'latest_selected_provider', value);
	    },
	    changeContactPhone: function changeContactPhone(event) {
	      this.$emit('stage-block-on-reload', event);
	    },
	    saveCollapsedOption: function saveCollapsedOption(type, value) {
	      BX.userOptions.save('salescenter', 'add_payment_collapse_options', type, value);
	    },
	    onProductFormModeChange: function onProductFormModeChange() {
	      var isCompilationMode = this.$store.getters['orderCreation/isCompilationMode'];
	      if (isCompilationMode) {
	        this.stages.delivery.status = salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.message.selectedMode = 'compilation';
	        this.$root.$app.sendingMethodDesc.text = this.$root.$app.sendingMethodDesc.text_modes.compilation;
	        this.stages.message.editorTemplate = this.$root.$app.sendingMethodDesc.text_modes.compilation;
	      } else {
	        this.stages.delivery.status = this.$root.$app.options.deliveryList.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.message.selectedMode = 'payment';
	        this.$root.$app.sendingMethodDesc.text = this.$root.$app.sendingMethodDesc.text_modes.payment;
	        this.stages.message.editorTemplate = this.$root.$app.sendingMethodDesc.text_modes.payment;
	      }
	    },
	    isViewWithoutDelivery: function isViewWithoutDelivery() {
	      return this.$root.$app.options.templateMode === 'view' && parseInt(this.$root.$app.options.shipmentId) <= 0;
	    }
	  },
	  created: function created() {
	    this.initCounter();
	  },
	  beforeUpdate: function beforeUpdate() {
	    this.initCounter();
	  },
	  //language=Vue
	  template: "\n\t\t<div>\n\t\t\t<sms-message-block\n\t\t\t\t@stage-block-sms-send-on-change-provider=\"changeProvider\"\n\t\t\t\t@stage-block-sms-message-on-change-contact-phone=\"changeContactPhone\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.message.status\"\n\t\t\t\t:initSenders=\"stages.message.initSenders\"\n\t\t\t\t:initCurrentSenderCode=\"stages.message.initCurrentSenderCode\"\n\t\t\t\t:initPushedToUseBitrix24Notifications=\"stages.message.initPushedToUseBitrix24Notifications\"\n\t\t\t\t:selectedSmsSender=\"stages.message.selectedSmsSender\"\n\t\t\t\t:manager=\"stages.message.manager\"\n\t\t\t\t:phone=\"stages.message.phone\"\n\t\t\t\t:contactEditorUrl=\"stages.message.contactEditorUrl\"\n\t\t\t\t:ownerId=\"stages.message.ownerId\"\n\t\t\t\t:ownerTypeId=\"stages.message.ownerTypeId\"\n\t\t\t\t:titleTemplate=\"stages.message.titleTemplate\"\n\t\t\t\t:showHint=\"stages.message.showHint\"\n\t\t\t\t:editorTemplate=\"stages.message.editorTemplate\"\n\t\t\t\t:editorUrl=\"stages.message.editorUrl\"\n\t\t\t\t:selectedMode=\"stages.message.selectedMode\"\n\t\t\t/>\n\t\t\t<product-block\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\"stages.product.title\"\n\t\t\t\t:hintTitle=\"stages.product.hintTitle\"\n\t\t\t\t@on-product-form-mode-change=\"onProductFormModeChange\"\n\t\t\t/>\n\t\t\t<paysystem-block\n\t\t\t\tv-if=\"editable\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'PAY_SYSTEM')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.paysystem.status\"\n\t\t\t\t:tiles=\"stages.paysystem.tiles\"\n\t\t\t\t:installed=\"stages.paysystem.installed\"\n\t\t\t\t:titleItems=\"stages.paysystem.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.paysystem.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<cashbox-block\n\t\t\t\tv-if=\"editable && hasStageCashBox\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'CASHBOX')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.cashbox.status\"\n\t\t\t\t:tiles=\"stages.cashbox.tiles\"\n\t\t\t\t:installed=\"stages.cashbox.installed\"\n\t\t\t\t:titleItems=\"stages.cashbox.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.cashbox.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<delivery-block\n\t\t\t\tv-if=\"!isHideDeliveryStage\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'DELIVERY')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.delivery.status\"\n\t\t\t\t:tiles=\"stages.delivery.tiles\"\n\t\t\t\t:installed=\"stages.delivery.installed\"\n\t\t\t\t:isCollapsible=\"true\"\n\t\t\t\t:initialCollapseState=\"stages.delivery.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<automation-block\n\t\t\t\tv-if=\"editable && hasStageAutomation\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.automation.status\"\n\t\t\t\t:stageOnOrderPaid=\"stages.automation.stageOnOrderPaid\"\n\t\t\t\t:stageOnDeliveryFinished=\"stages.automation.stageOnDeliveryFinished\"\n\t\t\t\t:items=\"stages.automation.items\"\n\t\t\t\t:initialCollapseState=\"stages.automation.initialCollapseState\"\n\t\t\t\t:isDeliveryStageVisible=\"stages.delivery.installed\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<send-block\n\t\t\t\t@on-submit=\"onSend\"\n\t\t\t\t:buttonEnabled=\"sendAllowed\"\n\t\t\t\t:showWhatClientSeesControl=\"!editable\"\n\t\t\t\t:buttonLabel=\"submitButtonLabel\"\n\t\t\t/>\n\t\t\t<timeline-block\n\t\t\t\tv-if=\"hasStageTimeLine\"\n\t\t\t\t:timelineItems=\"stages.timeline.items\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	var EntityCreatePaymentStages = {
	  components: {
	    'send-block': Send,
	    'cashbox-block': Cashbox,
	    'product-block': Product$1,
	    'paysystem-block': PaySystem,
	    'automation-block': Automation,
	    'sms-message-block': SmsMessage,
	    'timeline-block': TimeLine,
	    'document-selector-block': DocumentSelector
	  },
	  props: {
	    sendAllowed: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    var stages = {
	      message: {
	        initSenders: this.$root.$app.options.senders,
	        initCurrentSenderCode: this.$root.$app.options.currentSenderCode,
	        initPushedToUseBitrix24Notifications: this.$root.$app.options.pushedToUseBitrix24Notifications,
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        selectedSmsSender: this.$root.$app.sendingMethodDesc.provider,
	        manager: this.$root.$app.options.entityResponsible,
	        phone: this.$root.$app.options.contactPhone,
	        ownerId: this.$root.$app.options.ownerId,
	        ownerTypeId: this.$root.$app.options.ownerTypeId,
	        contactEditorUrl: this.$root.$app.options.contactEditorUrl,
	        titleTemplate: this.$root.$app.sendingMethodDesc.sent ? main_core.Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2_PAST_TIME') : main_core.Loc.getMessage('SALESCENTER_APP_CONTACT_BLOCK_TITLE_MESSAGE_2'),
	        showHint: this.$root.$app.options.templateMode !== 'view',
	        editorTemplate: this.$root.$app.sendingMethodDesc.text,
	        editorUrl: this.$root.$app.orderPublicUrl,
	        selectedMode: 'payment'
	      },
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current,
	        title: this.$root.$app.options.templateMode === 'view' ? main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW') : main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
	        hintTitle: this.$root.$app.options.templateMode === 'view' ? '' : main_core.Loc.getMessage('SALESCENTER_PRODUCT_SET_BLOCK_TITLE_SHORT')
	      },
	      paysystem: {
	        status: this.$root.$app.options.paySystemList.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.paySystemList.items),
	        installed: this.$root.$app.options.paySystemList.isSet,
	        titleItems: this.getTitleItems(this.$root.$app.options.paySystemList.items),
	        initialCollapseState: this.$root.$app.options.isPaySystemCollapsed ? this.$root.$app.options.isPaySystemCollapsed === 'Y' : this.$root.$app.options.paySystemList.isSet
	      },
	      cashbox: {},
	      automation: {},
	      documentSelector: {
	        status: salescenter_component_stageBlock.StatusTypes.complete
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
	        stageOnOrderPaid: this.$root.$app.options.stageOnOrderPaid,
	        items: this.$root.$app.options.entityStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }
	    if (this.$root.$app.options.hasOwnProperty('timeline')) {
	      stages.timeline = {
	        items: this.getTimelineCollection(this.$root.$app.options.timeline)
	      };
	    }
	    if (this.$root.$app.hasOwnProperty('documentSelector')) {
	      if (this.$root.$app.documentSelector.templateAddUrl) {
	        stages.documentSelector.templateAddUrl = this.$root.$app.documentSelector.templateAddUrl;
	      }
	    }
	    return {
	      stages: stages
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    hasStageTimeLine: function hasStageTimeLine() {
	      return this.stages.timeline.hasOwnProperty('items') && this.stages.timeline.items.length > 0;
	    },
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    hasStageCashBox: function hasStageCashBox() {
	      return this.stages.cashbox.hasOwnProperty('tiles');
	    },
	    submitButtonLabel: function submitButtonLabel() {
	      return this.editable ? main_core.Loc.getMessage('SALESCENTER_SEND') : main_core.Loc.getMessage('SALESCENTER_RESEND');
	    },
	    isShowDocumentSelector: function isShowDocumentSelector() {
	      return this.$root.$app.hasOwnProperty('documentSelector');
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
	      main_core.ajax.runComponentAction("bitrix:salescenter.app", "getAjaxData", {
	        mode: "class",
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          this.refreshTilesByType(response.data, type);
	        }
	      }.bind(this), function () {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR')
	        });
	      });
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      if (type === 'PAY_SYSTEM') {
	        this.stages.paysystem.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.paysystem.tiles = this.getTileCollection(data.items);
	        this.stages.paysystem.installed = data.isSet;
	        this.stages.paysystem.titleItems = this.getTitleItems(data.items);
	      } else if (type === 'CASHBOX') {
	        this.stages.cashbox.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.cashbox.tiles = this.getTileCollection(data.items);
	        this.stages.cashbox.installed = data.isSet;
	        this.stages.cashbox.titleItems = this.getTitleItems(data.items);
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    changeProvider: function changeProvider(value) {
	      this.$root.$app.sendingMethodDesc.provider = value;
	      BX.userOptions.save('salescenter', 'payment_sms_provider_options', 'latest_selected_provider', value);
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
	  template: "\n\t\t<div>\n\t\t\t<sms-message-block\n\t\t\t\t@stage-block-sms-send-on-change-provider=\"changeProvider\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.message.status\"\n\t\t\t\t:initSenders=\"stages.message.initSenders\"\n\t\t\t\t:initCurrentSenderCode=\"stages.message.initCurrentSenderCode\"\n\t\t\t\t:initPushedToUseBitrix24Notifications=\"stages.message.initPushedToUseBitrix24Notifications\"\n\t\t\t\t:selectedSmsSender=\"stages.message.selectedSmsSender\"\n\t\t\t\t:manager=\"stages.message.manager\"\n\t\t\t\t:phone=\"stages.message.phone\"\n\t\t\t\t:contactEditorUrl=\"stages.message.contactEditorUrl\"\n\t\t\t\t:ownerId=\"stages.message.ownerId\"\n\t\t\t\t:ownerTypeId=\"stages.message.ownerTypeId\"\n\t\t\t\t:titleTemplate=\"stages.message.titleTemplate\"\n\t\t\t\t:showHint=\"stages.message.showHint\"\n\t\t\t\t:editorTemplate=\"stages.message.editorTemplate\"\n\t\t\t\t:editorUrl=\"stages.message.editorUrl\"\n\t\t\t\t:selectedMode=\"stages.message.selectedMode\"\n\t\t\t/>\n\t\t\t<product-block\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\"stages.product.title\"\n\t\t\t\t:hintTitle=\"stages.product.hintTitle\"\n\t\t\t/>\n\t\t\t<paysystem-block\n\t\t\t\tv-if=\"editable\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'PAY_SYSTEM')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.paysystem.status\"\n\t\t\t\t:tiles=\"stages.paysystem.tiles\"\n\t\t\t\t:installed=\"stages.paysystem.installed\"\n\t\t\t\t:titleItems=\"stages.paysystem.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.paysystem.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<cashbox-block\n\t\t\t\tv-if=\"editable && hasStageCashBox\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'CASHBOX')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.cashbox.status\"\n\t\t\t\t:tiles=\"stages.cashbox.tiles\"\n\t\t\t\t:installed=\"stages.cashbox.installed\"\n\t\t\t\t:titleItems=\"stages.cashbox.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.cashbox.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<document-selector-block\n\t\t\t\tv-if=\"isShowDocumentSelector\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:templateAddUrl=\"stages.documentSelector.templateAddUrl\"\n\t\t\t/>\n\t\t\t<automation-block\n\t\t\t\tv-if=\"editable && hasStageAutomation\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.automation.status\"\n\t\t\t\t:stageOnOrderPaid=\"stages.automation.stageOnOrderPaid\"\n\t\t\t\t:items=\"stages.automation.items\"\n\t\t\t\t:initialCollapseState=\"stages.automation.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<send-block\n\t\t\t\t@on-submit=\"onSend\"\n\t\t\t\t:buttonEnabled=\"sendAllowed\"\n\t\t\t\t:showWhatClientSeesControl=\"!editable\"\n\t\t\t\t:buttonLabel=\"submitButtonLabel\"\n\t\t\t/>\n\t\t\t<timeline-block\n\t\t\t\tv-if=\"hasStageTimeLine\"\n\t\t\t\t:timelineItems=\"stages.timeline.items\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	var Send$1 = {
	  props: {
	    buttonEnabled: {
	      type: Boolean,
	      required: true
	    }
	  },
	  computed: {
	    buttonClass: function buttonClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled': this.buttonEnabled === false
	      };
	    }
	  },
	  methods: {
	    submit: function submit(event) {
	      this.$emit('on-submit', event);
	    }
	  },
	  template: "\n\t\t<div\n\t\t\t:class=\"buttonClass\"\n\t\t\tclass=\"salescenter-app-payment-by-sms-item-show salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t@click=\"submit($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_CREATE_SHIPMENT'), "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	};

	var StageBlocksListShipment = {
	  components: {
	    'send-block': Send$1,
	    'product-block': Product$1,
	    'delivery-block': DeliveryVuex,
	    'automation-block': Automation
	  },
	  props: {
	    sendAllowed: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    var stages = {
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current,
	        title: this.$root.$app.options.templateMode === 'view' ? main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHIPMENT_VIEW') : main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT_SHIPMENT')
	      },
	      delivery: {
	        status: this.$root.$app.options.deliveryList.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.deliveryList.items),
	        installed: this.$root.$app.options.deliveryList.isInstalled,
	        initialCollapseState: this.$root.$app.options.isDeliveryCollapsed ? this.$root.$app.options.isDeliveryCollapsed === 'Y' : this.$root.$app.options.deliveryList.isInstalled
	      },
	      automation: {}
	    };
	    if (this.$root.$app.options.isAutomationAvailable) {
	      stages.automation = {
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        stageOnDeliveryFinished: this.$root.$app.options.stageOnDeliveryFinished,
	        items: this.$root.$app.options.entityStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }
	    return {
	      stages: stages
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    editableMixin: function editableMixin() {
	      return this.editable === false;
	    },
	    isViewTemplateMode: function isViewTemplateMode() {
	      return this.$root.$app.options.templateMode === 'view';
	    }
	  },
	  methods: {
	    initCounter: function initCounter() {
	      this.counter = 1;
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
	      var _this = this;
	      main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getAjaxData', {
	        mode: 'class',
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          _this.refreshTilesByType(response.data, type);
	        }
	      }, function () {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR')
	        });
	      });
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      if (type === 'DELIVERY') {
	        this.stages.delivery.status = data.isInstalled ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	        this.stages.delivery.tiles = this.getTileCollection(data.items);
	        this.stages.delivery.installed = data.isInstalled;
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    saveCollapsedOption: function saveCollapsedOption(type, value) {
	      BX.userOptions.save('salescenter', 'add_shipment_collapse_options', type, value);
	    }
	  },
	  created: function created() {
	    this.initCounter();
	  },
	  beforeUpdate: function beforeUpdate() {
	    this.initCounter();
	  },
	  template: "\n\t\t<div>\n\t\t\t<product-block \n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\t\"stages.product.title\"\n\t\t\t\t:hintTitle=\"''\"\n\t\t\t/>\n\t\t\t\n\t\t\t<delivery-block v-on:on-stage-tile-collection-slider-close=\"stageRefresh($event, 'DELIVERY')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.delivery.status\"\n\t\t\t\t:tiles=\"stages.delivery.tiles\"\n\t\t\t\t:installed=\"stages.delivery.installed\"\n\t\t\t\t:isCollapsible=\"false\"\n\t\t\t\t:initialCollapseState=\"stages.delivery.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\n\t\t\t<automation-block v-if=\"editable && hasStageAutomation\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.automation.status\"\n\t\t\t\t:stageOnDeliveryFinished=\"stages.automation.stageOnDeliveryFinished\"\n\t\t\t\t:items=\"stages.automation.items\"\n\t\t\t\t:initialCollapseState=\"stages.automation.initialCollapseState\"\n\t\t\t\t:isDeliveryStageVisible=true\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t\n\t\t\t<send-block\n\t\t\t\tv-if=\"!isViewTemplateMode\"\n\t\t\t\t@on-submit=\"onSend\"\n\t\t\t\t:buttonEnabled=\"sendAllowed\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	var ResponsibleSelector = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    selectedUser: {
	      type: Number,
	      required: false
	    },
	    responsible: {
	      type: Object,
	      required: false
	    },
	    isMobileInstalledForResponsible: {
	      type: Boolean,
	      required: false
	    },
	    editable: {
	      type: Boolean,
	      required: true
	    },
	    contact: {
	      type: Object,
	      required: true
	    },
	    hintTitle: {
	      type: String,
	      required: false
	    }
	  },
	  mixins: [StageMixin],
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block
	  },
	  computed: {
	    isCreateMode: function isCreateMode() {
	      return this.$root.$app.options.templateMode === 'create';
	    },
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        titleItems: [],
	        installed: true,
	        collapsible: false,
	        checked: this.counterCheckedMixin,
	        showHint: true
	      };
	    },
	    contactInfo: function contactInfo() {
	      if (this.contact.name || this.contact.phone) {
	        return main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_CONTACT_INFO_TEMPLATE', {
	          '[span]': '<span class="salescenter-responsible-block-contact">',
	          '[/span]': '</span>',
	          '#CONTACT_NAME#': main_core.Text.encode(this.contact.name),
	          '#CONTACT_PHONE#': main_core.Text.encode(this.contact.phone)
	        });
	      }
	      return '';
	    },
	    addEmployee: function addEmployee() {
	      return main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_ADD_EMPLOYEE', {
	        '[link]': '<span id="add-employee-link" class="salescenter-responsible-block-add-employee-link">',
	        '[/link]': '</span>'
	      });
	    },
	    responsibleHeaderText: function responsibleHeaderText() {
	      return this.isCreateMode ? main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_RESPONSIBLE_CREATE') : main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_RESPONSIBLE_VIEW');
	    },
	    avatarStyle: function avatarStyle() {
	      var url = this.responsible.photo ? {
	        'background-image': "url(".concat(this.responsible.photo, ")")
	      } : null;
	      return [url];
	    }
	  },
	  mounted: function mounted() {
	    var _this$isMobileInstall,
	      _this = this;
	    this.$store.commit('orderCreation/setMobileInstalledForResponsible', (_this$isMobileInstall = this.isMobileInstalledForResponsible) !== null && _this$isMobileInstall !== void 0 ? _this$isMobileInstall : true);
	    var addEmployeeLink = document.getElementById('add-employee-link');
	    if (addEmployeeLink) {
	      main_core.Event.bind(addEmployeeLink, 'click', function (event) {
	        _this.$root.$app.openMobileAppPopup();
	      });
	    }
	    if (!this.editable) {
	      return;
	    }
	    var selectorRoot = document.getElementById('salescenterResponsibleSelector');
	    var dialogOptions = {
	      context: 'salescenter_responsible',
	      entities: [{
	        id: 'user'
	      }],
	      events: {
	        'Item:onSelect': function ItemOnSelect(event) {
	          _this.onResponsibleChanged(event);
	        }
	      }
	    };
	    if (this.selectedUser) {
	      dialogOptions.preselectedItems = [['user', this.selectedUser]];
	      dialogOptions.undeselectedItems = [['user', this.selectedUser]];
	    }
	    var tagSelector = new ui_entitySelector.TagSelector({
	      multiple: false,
	      dialogOptions: dialogOptions,
	      deselectable: false
	    });
	    tagSelector.renderTo(selectorRoot);
	  },
	  methods: {
	    onResponsibleChanged: function onResponsibleChanged(event) {
	      var _event$target,
	        _event$target$tagSele,
	        _this2 = this;
	      var _event$getData = event.getData(),
	        item = _event$getData.item;
	      item.setDeselectable(false);
	      (_event$target = event.target) === null || _event$target === void 0 ? void 0 : (_event$target$tagSele = _event$target.tagSelector) === null || _event$target$tagSele === void 0 ? void 0 : _event$target$tagSele.updateTags();
	      this.$store.commit('orderCreation/setPaymentResponsibleId', item.getId());
	      var isSubmitEnabled = this.$store.getters['orderCreation/isAllowedSubmit'];
	      this.$store.commit('orderCreation/disableSubmit');
	      main_core.ajax.runAction('salescenter.terminalResponsible.getUserMobileInfo', {
	        data: {
	          userId: item.getId()
	        }
	      }).then(function (result) {
	        _this2.$store.commit('orderCreation/setMobileInstalledForResponsible', result.data.isMobileInstalled);
	        if (!result.data.isMobileInstalled) {
	          _this2.$store.commit('orderCreation/setResponsiblePhoneNumbers', result.data.phones);
	        }
	        if (isSubmitEnabled) {
	          _this2.$store.commit('orderCreation/enableSubmit');
	        }
	        _this2.$emit('on-responsible-changed', event);
	      });
	    },
	    onItemHint: function onItemHint(event) {
	      BX.Salescenter.Manager.openHowTerminalWorks();
	    }
	  },
	  created: function created() {
	    if (this.selectedUser) {
	      this.$store.commit('orderCreation/setPaymentResponsibleId', this.selectedUser);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<stage-block-item\n\t\t\t:class=\"statusClassMixin\"\n\t\t\t:config=\"configForBlock\"\n\t\t\tv-on:on-item-hint=\"onItemHint\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{ title }}</template>\n\t\t\t<template v-slot:block-hint-title>{{ hintTitle }}</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<p class=\"salescenter-responsible-block-responsible-header\">{{ responsibleHeaderText }}</p>\n\t\t\t\t\t<div id=\"salescenterResponsibleSelector\" v-if=\"editable\"></div>\n\t\t\t\t\t<div class=\"salescenter-responsible-block-responsible-wrapper\" v-else>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tclass=\"salescenter-app-payment-by-sms-item-container-sms-user-avatar salescenter-responsible-block-responsible-photo\"\n\t\t\t\t\t\t\t:style=\"avatarStyle\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-responsible-block-responsible-name-wrapper\">\n\t\t\t\t\t\t\t<a class=\"salescenter-responsible-block-responsible-name\">{{ responsible.fullName }}</a>\n\t\t\t\t\t\t\t<div class=\"salescenter-responsible-block-responsible-position-wrapper\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-responsible-block-responsible-position\">{{ responsible.position }}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div v-html=\"contactInfo\" class=\"salescenter-responsible-block-contact-info\" v-if=\"!isCreateMode\"></div>\n\t\t\t\t\t<div v-html=\"addEmployee\" class=\"salescenter-responsible-block-add-employee\" v-else></div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t"
	};

	var Send$2 = {
	  props: {
	    buttonLabel: {
	      type: String,
	      required: true
	    },
	    buttonEnabled: {
	      type: Boolean,
	      required: true
	    }
	  },
	  computed: {
	    buttonClass: function buttonClass() {
	      return {
	        'salescenter-app-payment-by-sms-item-disabled': this.buttonEnabled === false
	      };
	    }
	  },
	  methods: {
	    submit: function submit(event) {
	      this.$emit('on-submit', event);
	    }
	  },
	  template: "\n\t\t<div\n\t\t\t:class=\"buttonClass\"\n\t\t\tclass=\"salescenter-app-payment-by-sms-item-show salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\"></div>\n\t\t\t</div>\n\t\t\t<div class=\"\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t\t\t\t<div\n\t\t\t\t\t\t\t\t@click=\"submit($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t{{buttonLabel}}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var _templateObject;
	var Amount = {
	  props: {
	    status: {
	      type: String,
	      required: true
	    },
	    counter: {
	      type: Number,
	      required: true
	    },
	    title: {
	      type: String,
	      required: true
	    },
	    hintTitle: {
	      type: String,
	      required: true
	    }
	  },
	  mixins: [StageMixin],
	  mounted: function mounted() {
	    // temporary fix; see the comment in the product block (product.js) for more details
	    var editable = this.$root.$app.options.templateMode !== 'view';
	    this.$root.$emit('on-change-editable', editable);
	    this.$store.commit('orderCreation/enableSubmit');
	  },
	  components: {
	    'stage-block-item': salescenter_component_stageBlock.Block
	  },
	  methods: {
	    onItemHint: function onItemHint(e) {
	      BX.Salescenter.Manager.openHowToSell(e);
	    },
	    onProductFormModeChange: function onProductFormModeChange(event) {
	      this.$emit('on-product-form-mode-change');
	    }
	  },
	  computed: {
	    configForBlock: function configForBlock() {
	      return {
	        counter: this.counter,
	        checked: this.counterCheckedMixin,
	        showHint: true
	      };
	    },
	    formattedSum: function formattedSum() {
	      var defaultCurrency = this.$root.$app.options.currencyCode || '';
	      var sum = currency_currencyCore.CurrencyCore.currencyFormat(this.$root.$app.options.totals.result, defaultCurrency, false);
	      var element = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<span class=\"catalog-pf-text salescenter-amount-block-amount-result-text--total\">", "</span>"])), sum);
	      return currency_currencyCore.CurrencyCore.getPriceControl(element, defaultCurrency);
	    }
	  },
	  template: "\n\t\t<stage-block-item\n\t\t\t@on-item-hint.stop.prevent=\"onItemHint\"\n\t\t\t:config=\"configForBlock\"\n\t\t\t:class=\"statusClassMixin\"\n\t\t>\n\t\t\t<template v-slot:block-title-title>{{ title }}</template>\n\t\t\t<template v-slot:block-hint-title>{{ hintTitle }}</template>\n\t\t\t<template v-slot:block-container>\n\t\t\t\t<div :class=\"containerClassMixin\">\n\t\t\t\t\t<div class=\"salescenter-amount-block-stub-wrapper\">\n\t\t\t\t\t\t<div class=\"salescenter-amount-block-stub-icon\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-amount-block-stub-text-wrapper\">\n\t\t\t\t\t\t\t<h3 class=\"salescenter-amount-block-stub-title\">".concat(main_core.Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_STUB_TITLE'), "</h3>\n\t\t\t\t\t\t\t<p class=\"salescenter-amount-block-stub-text\">").concat(main_core.Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_STUB_TEXT'), "</p>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-amount-block-amount-wrapper\">\n\t\t\t\t\t\t<table class=\"salescenter-amount-block-amount-result\">\n\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t<td class=\"salescenter-amount-block-amount-result-cell\">\n\t\t\t\t\t\t\t\t\t<span class=\"salescenter-amount-block-amount-result-text salescenter-amount-block-amount-result-text--total\">").concat(main_core.Loc.getMessage('SALESCENTER_AMOUNT_BLOCK_TOTAL'), "</span>\n\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t\t<td class=\"salescenter-amount-block-amount-result-cell\">\n\t\t\t\t\t\t\t\t\t<span class=\"salescenter-amount-block-amount-result-symbol salescenter-amount-block-amount-result-symbol--total\" v-html=\"formattedSum\"></span>\n\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t</table>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</stage-block-item>\n\t")
	};

	var TerminalStageBlocksList = {
	  components: {
	    'responsible-block': ResponsibleSelector,
	    'cashbox-block': Cashbox,
	    'product-block': Product$1,
	    'paysystem-block': PaySystem,
	    'automation-block': Automation,
	    'timeline-block': TimeLine,
	    'send-block': Send$2,
	    'amount-block': Amount
	  },
	  props: {
	    sendAllowed: {
	      type: Boolean,
	      required: true
	    }
	  },
	  data: function data() {
	    var _this$$root$$app$opti, _this$$root$$app$opti2, _this$$root$$app$opti3;
	    var stages = {
	      responsible: {
	        status: salescenter_component_stageBlock.StatusTypes.complete,
	        selectedUser: parseInt((_this$$root$$app$opti = this.$root.$app.options.paymentResponsible) !== null && _this$$root$$app$opti !== void 0 ? _this$$root$$app$opti : 0),
	        responsible: this.$root.$app.options.entityResponsible,
	        isMobileInstalledForResponsible: this.$root.$app.options.isMobileInstalledForResponsible,
	        contact: {
	          name: this.$root.$app.options.contactName,
	          phone: this.$root.$app.options.contactPhone
	        },
	        editable: this.$root.$app.options.templateMode === 'create' || ((_this$$root$$app$opti2 = this.$root.$app.options.payment) === null || _this$$root$$app$opti2 === void 0 ? void 0 : _this$$root$$app$opti2.PAID) === 'N',
	        hintTitle: this.$root.$app.options.templateMode === 'view' ? '' : main_core.Loc.getMessage('SALESCENTER_HOW_TERMINAL_WORKS')
	      },
	      product: {
	        status: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.current,
	        title: this.$root.$app.options.templateMode === 'view' ? main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_PAYMENT_VIEW') : main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE_SHORT'),
	        hintTitle: ''
	      },
	      paysystem: {
	        status: this.$root.$app.options.paySystemList.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled,
	        tiles: this.getTileCollection(this.$root.$app.options.paySystemList.items),
	        installed: this.$root.$app.options.paySystemList.isSet,
	        titleItems: this.getTitleItems(this.$root.$app.options.paySystemList.items),
	        initialCollapseState: this.$root.$app.options.isPaySystemCollapsed ? this.$root.$app.options.isPaySystemCollapsed === 'Y' : this.$root.$app.options.paySystemList.isSet
	      },
	      cashbox: {},
	      automation: {}
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
	        stageOnOrderPaid: this.$root.$app.options.stageOnOrderPaid,
	        items: this.$root.$app.options.entityStageList,
	        initialCollapseState: this.$root.$app.options.isAutomationCollapsed ? this.$root.$app.options.isAutomationCollapsed === 'Y' : false
	      };
	    }
	    if (this.$root.$app.options.hasOwnProperty('timeline')) {
	      stages.timeline = {
	        items: this.getTimelineCollection(this.$root.$app.options.timeline)
	      };
	    }
	    if (this.$root.$app.options.paySystemList.groups) {
	      stages.paysystem.groups = this.getTileGroupsCollection(this.$root.$app.options.paySystemList.groups, stages.paysystem.tiles);
	    }
	    if (this.$root.$app.options.templateMode === 'view' && ((_this$$root$$app$opti3 = this.$root.$app.options.payment) === null || _this$$root$$app$opti3 === void 0 ? void 0 : _this$$root$$app$opti3.PAID) === 'Y') {
	      stages.responsible.title = main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_TITLE_PAID_VIEW');
	    } else {
	      stages.responsible.title = main_core.Loc.getMessage('SALESCENTER_PAYMENT_RESPONSIBLE_SELECTOR_BLOCK_TITLE');
	    }
	    return {
	      stages: stages
	    };
	  },
	  mixins: [StageMixin, MixinTemplatesType],
	  computed: {
	    hasStageTimeLine: function hasStageTimeLine() {
	      return this.stages.timeline.hasOwnProperty('items') && this.stages.timeline.items.length > 0;
	    },
	    hasStageAutomation: function hasStageAutomation() {
	      return this.stages.automation.hasOwnProperty('items');
	    },
	    hasStageCashBox: function hasStageCashBox() {
	      return this.stages.cashbox.hasOwnProperty('tiles');
	    },
	    submitButtonLabel: function submitButtonLabel() {
	      return this.editable ? main_core.Loc.getMessage('SALESCENTER_CREATE_TERMINAL_PAYMENT') : main_core.Loc.getMessage('SALESCENTER_SAVE');
	    },
	    hasProducts: function hasProducts() {
	      return !this.$root.$app.options.isPaymentByAmount;
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
	    getTileGroupsCollection: function getTileGroupsCollection(groups, tiles) {
	      var ret = [];
	      if (Array.isArray(groups)) {
	        Object.values(groups).forEach(function (item) {
	          var group = new Tile.Group(item);
	          group.fillTiles(tiles);
	          ret.push(group);
	        });
	      }
	      return ret;
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
	      var _this = this;
	      main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getAjaxData', {
	        mode: 'class',
	        data: {
	          type: type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          _this.refreshTilesByType(response.data, type);
	        }
	      }, function () {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('SALESCENTER_DATA_UPDATE_ERROR')
	        });
	      });
	    },
	    refreshTilesByType: function refreshTilesByType(data, type) {
	      switch (type) {
	        case 'PAY_SYSTEM':
	          {
	            this.stages.paysystem.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	            this.stages.paysystem.tiles = this.getTileCollection(data.items);
	            this.stages.paysystem.groups = this.getTileGroupsCollection(data.groups, this.stages.paysystem.tiles);
	            this.stages.paysystem.installed = data.isSet;
	            this.stages.paysystem.titleItems = this.getTitleItems(data.items);
	            break;
	          }
	        case 'CASHBOX':
	          {
	            this.stages.cashbox.status = data.isSet ? salescenter_component_stageBlock.StatusTypes.complete : salescenter_component_stageBlock.StatusTypes.disabled;
	            this.stages.cashbox.tiles = this.getTileCollection(data.items);
	            this.stages.cashbox.installed = data.isSet;
	            this.stages.cashbox.titleItems = this.getTitleItems(data.items);
	            break;
	          }
	        // No default
	      }
	    },
	    onSend: function onSend(event) {
	      this.$emit('stage-block-send-on-send', event);
	    },
	    onResponsibleChanged: function onResponsibleChanged(event) {
	      this.$emit('on-responsible-changed', event);
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
	  // language=Vue
	  template: "\n\t\t<div class=\"salescenter-app-terminal-wrapper\">\n\t\t\t<responsible-block\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.responsible.status\"\n\t\t\t\t:title=\"stages.responsible.title\"\n\t\t\t\t:selectedUser=\"stages.responsible.selectedUser\"\n\t\t\t\t:responsible=\"stages.responsible.responsible\"\n\t\t\t\t:isMobileInstalledForResponsible=\"stages.responsible.isMobileInstalledForResponsible\"\n\t\t\t\t:contact=\"stages.responsible.contact\"\n\t\t\t\t:editable=\"stages.responsible.editable\"\n\t\t\t\t:hintTitle=\"stages.responsible.hintTitle\"\n\t\t\t\t@on-responsible-changed=\"onResponsibleChanged\"\n\t\t\t/>\n\t\t\t<product-block\n\t\t\t\tv-if=\"hasProducts\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\"stages.product.title\"\n\t\t\t\t:hintTitle=\"stages.product.hintTitle\"\n\t\t\t\t:additionalContainerClasses=\"{ 'salescenter-app-teminal-products-item': true }\"\n\t\t\t/>\n\t\t\t<amount-block\n\t\t\t\tv-else\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.product.status\"\n\t\t\t\t:title=\"stages.product.title\"\n\t\t\t\t:hintTitle=\"stages.product.hintTitle\"\n\t\t\t/>\n\t\t\t<paysystem-block\n\t\t\t\tv-if=\"editable\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'PAY_SYSTEM')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.paysystem.status\"\n\t\t\t\t:tiles=\"stages.paysystem.tiles\"\n\t\t\t\t:installed=\"stages.paysystem.installed\"\n\t\t\t\t:titleItems=\"stages.paysystem.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.paysystem.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<cashbox-block\n\t\t\t\tv-if=\"editable && hasStageCashBox\"\n\t\t\t\t@on-stage-tile-collection-slider-close=\"stageRefresh($event, 'CASHBOX')\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.cashbox.status\"\n\t\t\t\t:tiles=\"stages.cashbox.tiles\"\n\t\t\t\t:installed=\"stages.cashbox.installed\"\n\t\t\t\t:titleItems=\"stages.cashbox.titleItems\"\n\t\t\t\t:initialCollapseState=\"stages.cashbox.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<automation-block\n\t\t\t\tv-if=\"editable && hasStageAutomation\"\n\t\t\t\t:counter=\"counter++\"\n\t\t\t\t:status=\"stages.automation.status\"\n\t\t\t\t:stageOnOrderPaid=\"stages.automation.stageOnOrderPaid\"\n\t\t\t\t:items=\"stages.automation.items\"\n\t\t\t\t:initialCollapseState=\"stages.automation.initialCollapseState\"\n\t\t\t\t@on-save-collapsed-option=\"saveCollapsedOption\"\n\t\t\t/>\n\t\t\t<send-block\n\t\t\t\tv-if=\"editable\"\n\t\t\t\t@on-submit=\"onSend\"\n\t\t\t\t:buttonEnabled=\"sendAllowed\"\n\t\t\t\t:buttonLabel=\"submitButtonLabel\"\n\t\t\t/>\n\t\t\t<timeline-block\n\t\t\t\tv-if=\"hasStageTimeLine\"\n\t\t\t\t:timelineItems=\"stages.timeline.items\"\n\t\t\t/>\n\t\t</div>\n\t"
	};

	function ownKeys$6(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread$6(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys$6(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys$6(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Deal = {
	  mixins: [MixinTemplatesType, ComponentMixin],
	  data: function data() {
	    var _this$$root$$app$opti;
	    var isPanelVisible = true;
	    if (this.$root.$app.options.mode === ModeDictionary.terminalPayment && this.$root.$app.options.templateMode === 'view' && ((_this$$root$$app$opti = this.$root.$app.options.payment) === null || _this$$root$$app$opti === void 0 ? void 0 : _this$$root$$app$opti.PAID) === 'N') {
	      isPanelVisible = false;
	    }
	    return {
	      activeMenuItem: this.$root.$app.options.mode,
	      isLoading: false,
	      isPanelVisible: isPanelVisible,
	      ModeDictionary: ModeDictionary
	    };
	  },
	  components: {
	    'deal-receiving-payment': StageBlocksList$1,
	    'crm-entity-create-payment': EntityCreatePaymentStages,
	    'deal-creating-shipment': StageBlocksListShipment,
	    'deal-terminal-payment': TerminalStageBlocksList,
	    'start': Start
	  },
	  methods: {
	    reload: function reload(form) {
	      if (this.isLoading || !this.editable) {
	        return;
	      }
	      this.isLoading = true;
	      this.activeMenuItem = form;
	      this.$emit('on-reload', {
	        context: this.$root.$app.options.context,
	        orderId: this.$root.$app.orderId,
	        ownerTypeId: this.$root.$app.options.ownerTypeId,
	        ownerId: this.$root.$app.options.ownerId,
	        templateMode: 'create',
	        mode: this.activeMenuItem,
	        showModeList: this.$root.$app.options.showModeList,
	        initialMode: this.$root.$app.options.initialMode
	      });
	    },
	    onSuccessfullyConnected: function onSuccessfullyConnected() {
	      this.reload(this.activeMenuItem);
	    },
	    onReload: function onReload() {
	      this.reload(this.activeMenuItem);
	    },
	    sendPaymentDeliveryForm: function sendPaymentDeliveryForm(event) {
	      var _this = this;
	      if (!this.isAllowedPaymentDeliverySubmitButton) {
	        return;
	      }
	      if (!this.$root.$app.isPhoneConfirmed) {
	        main_core_events.EventEmitter.subscribeOnce('BX.Salescenter.App::onPhoneConfirmed', function () {
	          return _this.sendPaymentDeliveryForm(event);
	        });
	        this.$root.$app.showPhoneConfirmPopup();
	        return;
	      }
	      if (this.$store.getters['orderCreation/isCompilationMode']) {
	        this.$root.$app.sendCompilation(event.target);
	      } else if (this.editable) {
	        this.$root.$app.sendPayment(event.target);
	      } else {
	        this.$root.$app.resendPayment(event.target);
	      }
	    },
	    sendDeliveryForm: function sendDeliveryForm(event) {
	      if (!this.isAllowedDeliverySubmitButton) {
	        return;
	      }
	      this.$root.$app.sendShipment(event.target);
	    },
	    sendTerminalPaymentForm: function sendTerminalPaymentForm(event) {
	      if (!this.isAllowedTerminalPaymentSubmitButton) {
	        return;
	      }
	      if (this.templateMode === 'view') {
	        this.$root.$app.updateTerminalPayment(event.target);
	        return;
	      }
	      this.$root.$app.sendTerminalPayment(event.target);
	    },
	    // region menu item handlers
	    specifyCompanyContacts: function specifyCompanyContacts() {
	      BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {
	        width: 1200
	      });
	    },
	    suggestScenario: function suggestScenario(event) {
	      BX.Salescenter.Manager.openFeedbackPayOrderForm(event);
	    },
	    howItWorks: function howItWorks(event) {
	      if (this.mode === ModeDictionary.payment) {
	        BX.Salescenter.Manager.openHowPaySmartInvoiceWorks(event);
	        return;
	      }
	      if (this.mode === ModeDictionary.terminalPayment) {
	        BX.Salescenter.Manager.openHowTerminalWorks(event);
	        return;
	      }
	      BX.Salescenter.Manager.openHowPayDealWorks(event);
	    },
	    openIntegrationWindow: function openIntegrationWindow(event) {
	      BX.Salescenter.Manager.openIntegrationRequestForm(event);
	    },
	    freeMessages: function freeMessages() {
	      var senders = this.$root.$app.options.senders;
	      var sender = senders.filter(function (item) {
	        return item.code === salescenter_lib.SenderConfig.BITRIX24;
	      });
	      if (sender.length > 0) {
	        var fixed = salescenter_lib.SenderConfig.openSliderFreeMessages(sender[0].connectUrl);
	        fixed().then();
	      }
	    },
	    onResponsibleChanged: function onResponsibleChanged(event) {
	      // it needs to be done like that and not with vue's @class attribute because is breaks BX.UI.Pinner's classes
	      var buttonsPanel = this.$refs['buttonsPanel'];
	      buttonsPanel.classList.remove('salescenter-button-panel-hidden');
	      var pinnerAnchor = document.querySelector('.salescenter-app-pinner-anchor');
	      pinnerAnchor.classList.remove('salescenter-app-pinner-anchor-hidden');
	    },
	    openTerminalToolDisabledSlider: function openTerminalToolDisabledSlider() {
	      main_core.Runtime.loadExtension('ui.info-helper').then(function () {
	        top.BX.UI.InfoHelper.show('limit_crm_terminal_off');
	      });
	    },
	    openSalescanterToolDisabledSlider: function openSalescanterToolDisabledSlider() {
	      BX.loadExt('salescenter.tool-availability-manager').then(function () {
	        BX.Salescenter.ToolAvailabilityManager.openSalescenterToolDisabledSlider();
	      });
	    } // endregion
	  },
	  computed: _objectSpread$6({
	    mode: function mode() {
	      return this.$root.$app.options.mode;
	    },
	    templateMode: function templateMode() {
	      return this.$root.$app.options.templateMode;
	    },
	    initialMode: function initialMode() {
	      return this.$root.$app.options.initialMode;
	    },
	    isAllowedFreeMessagesButton: function isAllowedFreeMessagesButton() {
	      var senders = this.$root.$app.options.senders;
	      var sender = senders.filter(function (item) {
	        return item.code === salescenter_lib.SenderConfig.BITRIX24;
	      });
	      if (sender.length > 0) {
	        return salescenter_lib.SenderConfig.needConfigure(sender[0]);
	      }
	      return false;
	    },
	    isOnlyDeliveryItemVisible: function isOnlyDeliveryItemVisible() {
	      return this.initialMode === ModeDictionary.delivery && this.$root.$app.options.hasOwnProperty('deliveryList') && this.$root.$app.options.deliveryList.hasOwnProperty('hasInstallable') && this.$root.$app.options.deliveryList.hasInstallable;
	    },
	    isTerminalItemVisible: function isTerminalItemVisible() {
	      return this.isPaymentItemVisible && this.$root.$app.options.isTerminalAvailable;
	    },
	    isTerminalToolEnabled: function isTerminalToolEnabled() {
	      return this.$root.$app.options.isTerminalToolEnabled;
	    },
	    isSalescenterToolEnabled: function isSalescenterToolEnabled() {
	      return this.$root.$app.options.isSalescenterToolEnabled;
	    },
	    isPaymentItemVisible: function isPaymentItemVisible() {
	      return this.initialMode === ModeDictionary.payment || this.initialMode === ModeDictionary.paymentDelivery || this.initialMode === ModeDictionary.terminalPayment;
	    },
	    isAllowedPaymentDeliverySubmitButton: function isAllowedPaymentDeliverySubmitButton() {
	      var _this2 = this;
	      if (!this.$root.$app.hasClientContactInfo()) {
	        return false;
	      }
	      var senders = this.$root.$app.options.senders;
	      var filteredSenders = senders.filter(function (sender) {
	        return sender.code === _this2.$root.$app.options.currentSenderCode && sender.isConnected;
	      });
	      if (filteredSenders.length === 0) {
	        this.$store.commit('orderCreation/setIsSenderSelected', false);
	      }
	      return this.$store.getters['orderCreation/isAllowedSubmit'];
	    },
	    isAllowedTerminalPaymentSubmitButton: function isAllowedTerminalPaymentSubmitButton() {
	      return this.$store.getters['orderCreation/isAllowedSubmit'] || this.isTerminalViewModeSaveAllowed;
	    },
	    isTerminalViewModeSaveAllowed: function isTerminalViewModeSaveAllowed() {
	      var _this$$root$$app$opti2;
	      return this.templateMode === 'view' && ((_this$$root$$app$opti2 = this.$root.$app.options.payment) === null || _this$$root$$app$opti2 === void 0 ? void 0 : _this$$root$$app$opti2.PAID) === 'N';
	    },
	    isAllowedDeliverySubmitButton: function isAllowedDeliverySubmitButton() {
	      var deliveryId = this.$store.getters['orderCreation/getDeliveryId'];
	      if (!deliveryId) {
	        return false;
	      }
	      if (!this.$store.getters['orderCreation/isAllowedSubmit']) {
	        return false;
	      }
	      return deliveryId != this.$root.$app.options.emptyDeliveryServiceId;
	    },
	    isSuggestScenarioMenuItemVisible: function isSuggestScenarioMenuItemVisible() {
	      return this.$root.$app.options.isBitrix24;
	    },
	    isRequestIntegrationMenuItemVisible: function isRequestIntegrationMenuItemVisible() {
	      return this.$root.$app.options.isIntegrationButtonVisible;
	    },
	    isModeListVisible: function isModeListVisible() {
	      var _this$$root$$app$opti3;
	      return (_this$$root$$app$opti3 = this.$root.$app.options.showModeList) !== null && _this$$root$$app$opti3 !== void 0 ? _this$$root$$app$opti3 : true;
	    },
	    needShowStoreConnection: function needShowStoreConnection() {
	      return !this.isOrderPublicUrlAvailable && this.mode !== ModeDictionary.delivery;
	    },
	    isSidebarEnabled: function isSidebarEnabled() {
	      return true; // not removing this just yet because who knows...
	    },
	    sendPaymentDeliveryFormButtonText: function sendPaymentDeliveryFormButtonText() {
	      return this.editable ? main_core.Loc.getMessage('SALESCENTER_SEND') : main_core.Loc.getMessage('SALESCENTER_RESEND');
	    },
	    title: function title() {
	      return this.$root.$app.options.title;
	    },
	    // classes region
	    paymentDeliveryFormSubmitButtonClass: function paymentDeliveryFormSubmitButtonClass() {
	      return {
	        'ui-btn-disabled': !this.isAllowedPaymentDeliverySubmitButton
	      };
	    },
	    deliveryFormSubmitButtonClass: function deliveryFormSubmitButtonClass() {
	      return {
	        'ui-btn-disabled': !this.isAllowedDeliverySubmitButton
	      };
	    },
	    terminalPaymentFormSubmitButtonClass: function terminalPaymentFormSubmitButtonClass() {
	      return {
	        'ui-btn-disabled': !this.isAllowedTerminalPaymentSubmitButton
	      };
	    },
	    paymentMenuItemClass: function paymentMenuItemClass() {
	      return {
	        'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.payment
	      };
	    },
	    paymentTerminalMenuItemClass: function paymentTerminalMenuItemClass() {
	      return {
	        'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.terminalPayment
	      };
	    },
	    paymentDeliveryMenuItemClass: function paymentDeliveryMenuItemClass() {
	      return {
	        'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.paymentDelivery
	      };
	    },
	    deliveryMenuItemClass: function deliveryMenuItemClass() {
	      return {
	        'salescenter-app-sidebar-menu-active': this.activeMenuItem === ModeDictionary.delivery
	      };
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    application: function application(state) {
	      return state.application;
	    },
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  //language=Vue
	  template: "\n\t\t<div>\n\t\t\t<div\n\t\t\t\t:class=\"wrapperClass\"\n\t\t\t\t:style=\"wrapperStyle\"\n\t\t\t\tclass=\"salescenter-app-wrapper\"\n\t\t\t>\n\t\t\t\t<div class=\"ui-sidepanel-sidebar salescenter-app-sidebar\" v-if=\"isSidebarEnabled\">\n\t\t\t\t\t<ul class=\"ui-sidepanel-menu\">\n\t\t\t\t\t\t<template v-if=\"templateMode === 'view'\">\n\t\t\t\t\t\t\t<li class=\"ui-sidepanel-menu-item salescenter-app-sidebar-menu-active\">\n\t\t\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{title}}</div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"isModeListVisible\">\n\t\t\t\t\t\t\t<template v-if=\"isOnlyDeliveryItemVisible\">\n\t\t\t\t\t\t\t\t<li\n\t\t\t\t\t\t\t\t\t:class=\"deliveryMenuItemClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('SALESCENTER_LEFT_CREATE_SHIPMENT_MSGVER_1'), "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t<li\n\t\t\t\t\t\t\t\t\tv-if=\"isPaymentItemVisible\"\n\t\t\t\t\t\t\t\t\t@click=\"isSalescenterToolEnabled ? reload(ModeDictionary.payment) : openSalescanterToolDisabledSlider()\"\n\t\t\t\t\t\t\t\t\t:class=\"paymentMenuItemClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_TAKE_PAYMENT'), "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</li>\n\t\n\t\t\t\t\t\t\t\t<li\n\t\t\t\t\t\t\t\t\tv-if=\"isTerminalItemVisible\"\n\t\t\t\t\t\t\t\t\t@click=\"isTerminalToolEnabled ? reload(ModeDictionary.terminalPayment) : openTerminalToolDisabledSlider()\"\n\t\t\t\t\t\t\t\t\t:class=\"paymentTerminalMenuItemClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link salescenter-menu-terminal-payment\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_TERMINAL_PAYMENT'), "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</li>\n\t\n\t\t\t\t\t\t\t\t<li\n\t\t\t\t\t\t\t\t\tv-if=\"isPaymentItemVisible\"\n\t\t\t\t\t\t\t\t\t@click=\"isSalescenterToolEnabled ? reload(ModeDictionary.paymentDelivery) : openSalescanterToolDisabledSlider()\"\n\t\t\t\t\t\t\t\t\t:class=\"paymentDeliveryMenuItemClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_TAKE_PAYMENT_AND_CREATE_SHIPMENT'), "\n\t\t\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</template>\n\t\n\t\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm ui-sidepanel-menu-item-separate\">\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t@click=\"specifyCompanyContacts\"\n\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</li>\n\t\t\t\t\t\t<li\n\t\t\t\t\t\t\tv-if=\"isSuggestScenarioMenuItemVisible\"\n\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t@click=\"suggestScenario($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_OFFER_SCRIPT'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</li>\n\t\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t@click=\"howItWorks($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_HOW_WORKS'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</li>\n\t\t\t\t\t\t<li\n\t\t\t\t\t\t\tv-if=\"isAllowedFreeMessagesButton\"\n\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t@click=\"freeMessages($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_FREE_MESSAGES'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</li>\n\t\t\t\t\t\t<li\n\t\t\t\t\t\t\tv-if=\"isRequestIntegrationMenuItemVisible\"\n\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t\t@click=\"openIntegrationWindow($event)\"\n\t\t\t\t\t\t\t\tclass=\"ui-sidepanel-menu-link\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\"\n\t\t\t\t\t\t\t\t\t data-manager-openIntegrationRequestForm-params=\"sender_page:deal\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_INTEGRATION_MSGVER_3'), "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t</li>\n\t\t\t\t\t</ul>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-right-side\">\n\t\t\t\t\t<start\n\t\t\t\t\t\tv-if=\"needShowStoreConnection && mode !== ModeDictionary.terminalPayment\"\n\t\t\t\t\t\t@on-successfully-connected=\"onSuccessfullyConnected\"\n\t\t\t\t\t>\n\t\t\t\t\t</start>\n\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t<deal-receiving-payment\n\t\t\t\t\t\t\tv-if=\"mode === ModeDictionary.paymentDelivery\"\n\t\t\t\t\t\t\t@stage-block-on-reload=\"onReload\"\n\t\t\t\t\t\t\t@stage-block-send-on-send=\"sendPaymentDeliveryForm($event)\"\n\t\t\t\t\t\t\t:sendAllowed=\"isAllowedPaymentDeliverySubmitButton\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<deal-creating-shipment\n\t\t\t\t\t\t\tv-else-if=\"mode === ModeDictionary.delivery\"\n\t\t\t\t\t\t\t@stage-block-send-on-send=\"sendDeliveryForm($event)\"\n\t\t\t\t\t\t\t:sendAllowed=\"isAllowedDeliverySubmitButton\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<crm-entity-create-payment\n\t\t\t\t\t\t\tv-if=\"mode === ModeDictionary.payment\"\n\t\t\t\t\t\t\t@stage-block-send-on-send=\"sendPaymentDeliveryForm($event)\"\n\t\t\t\t\t\t\t:sendAllowed=\"isAllowedPaymentDeliverySubmitButton\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t\t<deal-terminal-payment\n\t\t\t\t\t\t\tv-if=\"mode === ModeDictionary.terminalPayment\"\n\t\t\t\t\t\t\t@stage-block-send-on-send=\"sendTerminalPaymentForm($event)\"\n\t\t\t\t\t\t\t@on-responsible-changed=\"onResponsibleChanged($event)\"\n\t\t\t\t\t\t\t:sendAllowed=\"isAllowedTerminalPaymentSubmitButton\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t\t<template v-if=\"!(mode === 'terminal_payment' && templateMode === 'view' && !isTerminalViewModeSaveAllowed)\">\n\t\t\t\t\t<div class=\"ui-button-panel-wrapper salescenter-button-panel\" ref=\"buttonsPanel\" :class=\"{ 'salescenter-button-panel-hidden': !isPanelVisible }\">\n\t\t\t\t\t\t<div class=\"ui-button-panel\">\n\t\t\t\t\t\t\t<template v-if=\"mode === ModeDictionary.paymentDelivery || mode === ModeDictionary.payment\">\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t@click=\"sendPaymentDeliveryForm($event)\"\n\t\t\t\t\t\t\t\t\t:class=\"paymentDeliveryFormSubmitButtonClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-success\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t{{sendPaymentDeliveryFormButtonText}}\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t@click=\"close\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-link\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CANCEL'), "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else-if=\"mode === 'terminal_payment'\">\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tv-if=\"editable\"\n\t\t\t\t\t\t\t\t\t@click=\"sendTerminalPaymentForm($event)\"\n\t\t\t\t\t\t\t\t\t:class=\"terminalPaymentFormSubmitButtonClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-success\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CREATE_TERMINAL_PAYMENT'), "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\tv-if=\"isTerminalViewModeSaveAllowed\"\n\t\t\t\t\t\t\t\t\t@click=\"sendTerminalPaymentForm($event)\"\n\t\t\t\t\t\t\t\t\t:class=\"terminalPaymentFormSubmitButtonClass\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-success\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_SAVE'), "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t@click=\"close\"\n\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-link\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CANCEL'), "\n\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<template v-else-if=\"mode === ModeDictionary.delivery\">\n\t\t\t\t\t\t\t\t<template v-if=\"editable\">\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\t@click=\"sendDeliveryForm($event)\"\n\t\t\t\t\t\t\t\t\t\t:class=\"deliveryFormSubmitButtonClass\"\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-success\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CREATE_SHIPMENT'), "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t\t<button\n\t\t\t\t\t\t\t\t\t\t@click=\"close\"\n\t\t\t\t\t\t\t\t\t\tclass=\"ui-btn ui-btn-md ui-btn-link\"\n\t\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('SALESCENTER_CANCEL'), "\n\t\t\t\t\t\t\t\t\t</button>\n\t\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"this.order.errors.length > 0\" ref=\"errorBlock\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-pinner-anchor\" :class=\"{ 'salescenter-app-pinner-anchor-hidden': !isPanelVisible }\"></div>\n\t\t</div>\n\t")
	};

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
	          if (babelHelpers["typeof"](payload.pages) === 'object') {
	            state.pages = payload.pages;
	            _this.saveState(state);
	          }
	        },
	        removePage: function removePage(state, payload) {
	          if (babelHelpers["typeof"](payload.page) === 'object') {
	            state.pages = state.pages.filter(function (page) {
	              return !(payload.page.id && payload.page.id > 0 && page.id === payload.page.id || payload.page.landingId && payload.page.landingId > 0 && page.landingId === payload.page.landingId);
	            });
	            _this.saveState(state);
	          }
	        },
	        addPage: function addPage(state, payload) {
	          if (babelHelpers["typeof"](payload.page) === 'object') {
	            state.pages.push(payload.page);
	            _this.saveState(state);
	          }
	        }
	      };
	    }
	  }]);
	  return ApplicationModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var DocumentSelectorModel = /*#__PURE__*/function (_VuexBuilderModel) {
	  babelHelpers.inherits(DocumentSelectorModel, _VuexBuilderModel);
	  function DocumentSelectorModel() {
	    babelHelpers.classCallCheck(this, DocumentSelectorModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DocumentSelectorModel).apply(this, arguments));
	  }
	  babelHelpers.createClass(DocumentSelectorModel, [{
	    key: "getName",
	    /**
	     * @inheritDoc
	     */
	    value: function getName() {
	      return 'documentSelector';
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      return {
	        addDocument: function addDocument(_ref, _ref2) {
	          var commit = _ref.commit,
	            dispatch = _ref.dispatch;
	          var document = _ref2.document;
	          commit('addDocument', {
	            document: document
	          });
	          dispatch('setBoundDocumentId', {
	            boundDocumentId: document.id
	          });
	        },
	        setBoundDocumentId: function setBoundDocumentId(_ref3, _ref4) {
	          var state = _ref3.state,
	            commit = _ref3.commit;
	          var boundDocumentId = _ref4.boundDocumentId;
	          if (state.paymentId > 0) {
	            rest_client.rest.callMethod('crm.documentgenerator.document.bindToPayment', {
	              id: boundDocumentId,
	              paymentId: state.paymentId
	            }).then(function () {
	              commit('setBoundDocumentId', {
	                boundDocumentId: boundDocumentId
	              });
	            })["catch"](function (response) {
	              console.error(response);
	            });
	          } else {
	            commit('setBoundDocumentId', {
	              boundDocumentId: boundDocumentId
	            });
	          }
	        },
	        loadTemplates: function loadTemplates(_ref5) {
	          var state = _ref5.state,
	            commit = _ref5.commit;
	          if (Number(state.entityTypeId) <= 0 || Number(state.entityId) <= 0) {
	            commit('setTemplates', {
	              templates: []
	            });
	            return;
	          }
	          rest_client.rest.callMethod('crm.documentgenerator.template.listForItem', {
	            entityTypeId: state.entityTypeId,
	            entityId: state.entityId
	          }).then(function (response) {
	            if (response.answer.result.templates) {
	              commit('setTemplates', {
	                templates: response.answer.result.templates
	              });
	            }
	          })["catch"](function (response) {
	            console.error(response);
	          });
	        }
	      };
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        entityTypeId: null,
	        entityId: null,
	        paymentId: null,
	        documents: [],
	        templates: [],
	        boundDocumentId: null,
	        selectedTemplateId: null
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      return {
	        getTemplates: function getTemplates(state) {
	          return state.templates;
	        },
	        getDocuments: function getDocuments(state) {
	          return state.documents;
	        },
	        getBoundDocumentId: function getBoundDocumentId(state) {
	          return state.boundDocumentId;
	        },
	        getSelectedTemplateId: function getSelectedTemplateId(state) {
	          return state.selectedTemplateId;
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      return {
	        fillState: function fillState(state, payload) {
	          state.entityTypeId = payload.entityTypeId;
	          state.entityId = payload.entityId;
	          state.paymentId = payload.paymentId;
	          state.documents = payload.documents;
	          state.templates = payload.templates;
	          state.boundDocumentId = payload.boundDocumentId;
	          state.selectedTemplateId = payload.selectedTemplateId;
	        },
	        setTemplates: function setTemplates(state, payload) {
	          if (babelHelpers["typeof"](payload.templates) === 'object') {
	            state.templates = payload.templates;
	          }
	        },
	        setBoundDocumentId: function setBoundDocumentId(state, payload) {
	          if (typeof payload.boundDocumentId === 'number') {
	            state.boundDocumentId = payload.boundDocumentId;
	          }
	        },
	        setSelectedTemplateId: function setSelectedTemplateId(state, payload) {
	          if (typeof payload.selectedTemplateId === 'number') {
	            state.selectedTemplateId = payload.selectedTemplateId;
	            state.boundDocumentId = null;
	          }
	        },
	        addDocument: function addDocument(state, payload) {
	          if (babelHelpers["typeof"](payload.document) === 'object') {
	            var newDocument = payload.document;
	            if (!newDocument.id) {
	              return;
	            }
	            var documents = state.documents || [];
	            var isUpdated = false;
	            for (var index in documents) {
	              if (documents[index].id === newDocument.id) {
	                documents[index] = newDocument;
	                isUpdated = true;
	                break;
	              }
	            }
	            if (!isUpdated) {
	              documents.unshift(newDocument);
	            }
	            state.documents = documents;
	          }
	        }
	      };
	    }
	  }]);
	  return DocumentSelectorModel;
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
	        isSenderSelected: true,
	        isCompilationMode: false,
	        errors: [],
	        total: {
	          sum: null,
	          discount: null,
	          result: null
	        },
	        /**
	         * ID of selected pay systems available for order payment
	         */
	        availablePaySystemsIds: [],
	        paymentResponsibleId: null,
	        isMobileInstalledForResponsible: false,
	        responsiblePhoneNumbers: []
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
	        setDeliveryId: function setDeliveryId(_ref3, payload) {
	          var commit = _ref3.commit;
	          commit('setDeliveryId', payload);
	        },
	        setDelivery: function setDelivery(_ref4, payload) {
	          var commit = _ref4.commit;
	          commit('setDelivery', payload);
	        },
	        setPropertyValues: function setPropertyValues(_ref5, payload) {
	          var commit = _ref5.commit;
	          commit('setPropertyValues', payload);
	        },
	        setDeliveryExtraServicesValues: function setDeliveryExtraServicesValues(_ref6, payload) {
	          var commit = _ref6.commit;
	          commit('setDeliveryExtraServicesValues', payload);
	        },
	        setExpectedDelivery: function setExpectedDelivery(_ref7, payload) {
	          var commit = _ref7.commit;
	          commit('setExpectedDelivery', payload);
	        },
	        setDeliveryResponsibleId: function setDeliveryResponsibleId(_ref8, payload) {
	          var commit = _ref8.commit;
	          commit('setDeliveryResponsibleId', payload);
	        },
	        setPaymentResponsibleId: function setPaymentResponsibleId(_ref9, payload) {
	          var commit = _ref9.commit;
	          commit('setPaymentResponsibleId', payload);
	        },
	        setMobileInstalledForResponsible: function setMobileInstalledForResponsible(_ref10, payload) {
	          var commit = _ref10.commit;
	          commit('setMobileInstalledForResponsible', payload);
	        },
	        setResponsiblePhoneNumbers: function setResponsiblePhoneNumbers(_ref11, payload) {
	          var commit = _ref11.commit;
	          commit('setResponsiblePhoneNumbers', payload);
	        },
	        setPersonTypeId: function setPersonTypeId(_ref12, payload) {
	          var commit = _ref12.commit;
	          commit('setPersonTypeId', payload);
	        },
	        setAvailablePaySystemsIds: function setAvailablePaySystemsIds(_ref13, payload) {
	          var commit = _ref13.commit;
	          commit('setAvailablePaySystemsIds', payload);
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
	          return state.isEnabledSubmit && state.isSenderSelected;
	        },
	        isCompilationMode: function isCompilationMode(state) {
	          return state.isCompilationMode;
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
	        getPaymentResponsibleId: function getPaymentResponsibleId(state) {
	          return state.paymentResponsibleId;
	        },
	        isMobileInstalledForResponsible: function isMobileInstalledForResponsible(state) {
	          return state.isMobileInstalledForResponsible;
	        },
	        getResponsiblePhoneNumbers: function getResponsiblePhoneNumbers(state) {
	          return state.responsiblePhoneNumbers;
	        },
	        getPersonTypeId: function getPersonTypeId(state) {
	          return state.personTypeId;
	        },
	        getAvailablePaySystemsIds: function getAvailablePaySystemsIds(state) {
	          return state.availablePaySystemsIds;
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
	        setPaymentResponsibleId: function setPaymentResponsibleId(state, paymentResponsibleId) {
	          state.paymentResponsibleId = paymentResponsibleId;
	        },
	        setMobileInstalledForResponsible: function setMobileInstalledForResponsible(state, isMobileInstalledForResponsible) {
	          state.isMobileInstalledForResponsible = isMobileInstalledForResponsible;
	        },
	        setResponsiblePhoneNumbers: function setResponsiblePhoneNumbers(state, responsiblePhoneNumbers) {
	          state.responsiblePhoneNumbers = responsiblePhoneNumbers;
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
	        setAvailablePaySystemsIds: function setAvailablePaySystemsIds(state, payload) {
	          state.availablePaySystemsIds = payload;
	        },
	        setIsSenderSelected: function setIsSenderSelected(state, isSelected) {
	          state.isSenderSelected = isSelected;
	        },
	        enableSubmit: function enableSubmit(state) {
	          state.isEnabledSubmit = true;
	        },
	        disableSubmit: function disableSubmit(state) {
	          state.isEnabledSubmit = false;
	        },
	        enableCompilationMode: function enableCompilationMode(state) {
	          state.isCompilationMode = true;
	        },
	        disableCompilationMode: function disableCompilationMode(state) {
	          state.isCompilationMode = false;
	        }
	      };
	    }
	  }]);
	  return OrderCreationModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var _templateObject$1;
	var MobileAppInstallPopup = /*#__PURE__*/function () {
	  function MobileAppInstallPopup(options) {
	    var _this$phoneNumbers$, _this$phoneNumbers, _this$senders$, _this$senders;
	    babelHelpers.classCallCheck(this, MobileAppInstallPopup);
	    this.initSenders(options.sendersConfig);
	    this.phoneNumbers = options.phoneNumbers;
	    this.userId = options.userId;
	    this.root = options.root;
	    this.selectedPhoneNumber = (_this$phoneNumbers$ = (_this$phoneNumbers = this.phoneNumbers) === null || _this$phoneNumbers === void 0 ? void 0 : _this$phoneNumbers[0]) !== null && _this$phoneNumbers$ !== void 0 ? _this$phoneNumbers$ : null;
	    this.selectedSender = (_this$senders$ = (_this$senders = this.senders) === null || _this$senders === void 0 ? void 0 : _this$senders[0]) !== null && _this$senders$ !== void 0 ? _this$senders$ : null;

	    /** @type Popup  */
	    this.mainPopup = null;
	    /** @type Button */
	    this.sendButton = null;
	    this.sendersMenu = null;
	    this.phoneMenu = null;
	  }
	  babelHelpers.createClass(MobileAppInstallPopup, [{
	    key: "initSenders",
	    value: function initSenders(sendersData) {
	      var _this$sendersConfig;
	      this.sendersConfig = sendersData.find(function (item) {
	        return item.code === 'sms_provider';
	      });
	      this.senders = (_this$sendersConfig = this.sendersConfig) === null || _this$sendersConfig === void 0 ? void 0 : _this$sendersConfig.smsSenders;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var _this = this;
	      var popupContent = this.getPopupContent();
	      var linkButton = new ui_buttons.Button({
	        id: 'copy-install-link',
	        color: ui_buttons.Button.Color.LINK,
	        round: true,
	        icon: ui_buttons.ButtonIcon.COPY,
	        text: main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_BTN_LINK')
	      });
	      this.sendButton = new ui_buttons.Button({
	        state: this.selectedPhoneNumber && this.selectedSender ? null : ui_buttons.ButtonState.DISABLED,
	        color: ui_buttons.Button.Color.PRIMARY,
	        round: true,
	        text: main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_BTN_SEND'),
	        onclick: function onclick() {
	          _this.sendButton.setClocking();
	          _this.sendSms();
	        }
	      });
	      this.mainPopup = new main_popup.Popup({
	        className: 'salescenter-popup-mobile',
	        overlay: true,
	        content: popupContent,
	        closeIcon: true,
	        maxWidth: 666,
	        buttons: [this.sendButton, linkButton],
	        events: {
	          onClose: function onClose() {
	            _this.root.closeApplication();
	          }
	        }
	      });
	      this.mainPopup.show();
	      this.bindCopyLink(linkButton);
	      this.bindSelectors();
	    }
	  }, {
	    key: "sendSms",
	    value: function sendSms() {
	      var _this2 = this;
	      main_core.ajax.runAction('salescenter.terminalResponsible.sendLinkToMobileApp', {
	        data: {
	          userId: this.userId,
	          phone: this.selectedPhoneNumber,
	          senderId: this.selectedSender.id,
	          entity: {
	            entityTypeId: this.root.ownerTypeId,
	            entityId: this.root.ownerId
	          }
	        }
	      }).then(function (result) {
	        _this2.mainPopup.close();
	        _this2.root.closeApplication();
	      });
	    }
	  }, {
	    key: "bindCopyLink",
	    value: function bindCopyLink(linkButton) {
	      BX.clipboard.bindCopyClick(linkButton.getContainer(), {
	        text: this.root.options.mobileAppLink
	      });
	    }
	  }, {
	    key: "bindSelectors",
	    value: function bindSelectors() {
	      this.bindSendersSelector();
	      this.bindPhoneSelector();
	    }
	  }, {
	    key: "bindSendersSelector",
	    value: function bindSendersSelector() {
	      var _this3 = this;
	      var sendersSelector = document.getElementById('senders-selector');
	      if (this.selectedSender) {
	        main_core.Event.bind(sendersSelector, 'click', function () {
	          var menuItems = [];
	          _this3.senders.forEach(function (sender) {
	            menuItems.push({
	              text: main_core.Text.encode(sender.name),
	              onclick: function onclick() {
	                var _this3$sendersMenu;
	                _this3.selectedSender = sender;
	                sendersSelector.firstChild.innerText = main_core.Text.encode(_this3.selectedSender.name);
	                (_this3$sendersMenu = _this3.sendersMenu) === null || _this3$sendersMenu === void 0 ? void 0 : _this3$sendersMenu.close();
	              }
	            });
	          });
	          BX.PopupMenu.show('sender-menu', sendersSelector, menuItems, {
	            offsetTop: 0,
	            offsetLeft: 36,
	            angle: {
	              position: 'top',
	              offset: 0
	            }
	          });
	          _this3.sendersMenu = BX.PopupMenu.getCurrentMenu();
	        });
	      } else {
	        main_core.Event.bind(sendersSelector, 'click', function () {
	          BX.SidePanel.Instance.open(_this3.sendersConfig.connectUrl, {
	            events: {
	              onClose: function onClose() {
	                _this3.refresh();
	              }
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "bindPhoneSelector",
	    value: function bindPhoneSelector() {
	      var _this4 = this;
	      var phoneSelector = document.getElementById('phone-selector');
	      if (this.selectedPhoneNumber) {
	        main_core.Event.bind(phoneSelector, 'click', function () {
	          var menuItems = [];
	          _this4.phoneNumbers.forEach(function (phoneNumber) {
	            menuItems.push({
	              text: main_core.Text.encode(phoneNumber),
	              onclick: function onclick() {
	                var _this4$phoneMenu;
	                _this4.selectedPhoneNumber = phoneNumber;
	                phoneSelector.firstChild.innerText = main_core.Text.encode(_this4.selectedPhoneNumber);
	                (_this4$phoneMenu = _this4.phoneMenu) === null || _this4$phoneMenu === void 0 ? void 0 : _this4$phoneMenu.close();
	              }
	            });
	          });
	          BX.PopupMenu.show('phone-menu', phoneSelector, menuItems, {
	            offsetTop: 0,
	            offsetLeft: 36,
	            angle: {
	              position: 'top',
	              offset: 0
	            }
	          });
	          _this4.phoneMenu = BX.PopupMenu.getCurrentMenu();
	        });
	      } else {
	        main_core.Event.bind(phoneSelector, 'click', function () {
	          BX.SidePanel.Instance.open("/company/personal/user/".concat(_this4.userId, "/"), {
	            events: {
	              onClose: function onClose() {
	                _this4.refresh();
	              }
	            }
	          });
	        });
	      }
	    }
	  }, {
	    key: "refresh",
	    value: function refresh() {
	      var _this5 = this;
	      main_core.ajax.runAction('salescenter.terminalResponsible.refreshDataForSendingLink', {
	        data: {
	          userId: this.userId
	        }
	      }).then(function (result) {
	        var _this5$phoneNumbers$, _this5$phoneNumbers, _this5$senders$, _this5$senders;
	        _this5.initSenders(result.data.senders);
	        _this5.phoneNumbers = result.data.phones;
	        _this5.selectedPhoneNumber = (_this5$phoneNumbers$ = (_this5$phoneNumbers = _this5.phoneNumbers) === null || _this5$phoneNumbers === void 0 ? void 0 : _this5$phoneNumbers[0]) !== null && _this5$phoneNumbers$ !== void 0 ? _this5$phoneNumbers$ : null;
	        _this5.selectedSender = (_this5$senders$ = (_this5$senders = _this5.senders) === null || _this5$senders === void 0 ? void 0 : _this5$senders[0]) !== null && _this5$senders$ !== void 0 ? _this5$senders$ : null;
	        _this5.updateSendButtonState();
	        _this5.updateContent();
	      });
	    }
	  }, {
	    key: "updateSendButtonState",
	    value: function updateSendButtonState() {
	      this.sendButton.setDisabled(!Boolean(this.selectedPhoneNumber && this.selectedSender));
	    }
	  }, {
	    key: "updateContent",
	    value: function updateContent() {
	      this.mainPopup.setContent(this.getPopupContent());
	      this.bindSelectors();
	    }
	  }, {
	    key: "getPopupContent",
	    value: function getPopupContent() {
	      // all for the sake of localization
	      var phoneAndServiceContent = '';
	      if (this.selectedSender) {
	        phoneAndServiceContent = main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_SENDER_AND_PHONE', {
	          '[main]': '<div class="salescenter-popup-mobile__service">',
	          '[/main]': '</div>',
	          '[number]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
	          '#NUMBER#': "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-inline\" id=\"phone-selector\">\n\t\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-value\">\n\t\t\t\t\t\t\t\t".concat(this.selectedPhoneNumber ? main_core.Text.encode(this.selectedPhoneNumber) : main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_ADD_PHONE'), "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t"),
	          '[/number]': "\n\t\t\t\t\t\t\t".concat(this.selectedPhoneNumber ? '<div class="ui-icon-set --chevron-down"></div>' : '', "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"),
	          '[service]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
	          '#SERVICE#': "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-inline\" id=\"senders-selector\">\n\t\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-value\">".concat(main_core.Text.encode(this.selectedSender.name), "</div>\n\t\t\t\t\t"),
	          '[/service]': "\n\t\t\t\t\t\t\t<div class=\"ui-icon-set --chevron-down\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"
	        });
	      } else {
	        phoneAndServiceContent = main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_SENDER_AND_PHONE_NO_SENDER', {
	          '[main]': '<div class="salescenter-popup-mobile__service">',
	          '[/main]': '</div>',
	          '[number]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
	          '#NUMBER#': "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-inline\" id=\"phone-selector\">\n\t\t\t\t\t\t\t<div class=\"salescenter-popup-mobile__service-value\">\n\t\t\t\t\t\t\t\t".concat(this.selectedPhoneNumber ? main_core.Text.encode(this.selectedPhoneNumber) : main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_ADD_PHONE'), "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t"),
	          '[/number]': "\n\t\t\t\t\t\t\t".concat(this.selectedPhoneNumber ? '<div class="ui-icon-set --chevron-down"></div>' : '', "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"),
	          '[service]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-inline" id="senders-selector"><div class="salescenter-popup-mobile__service-value">',
	          '[/service]': "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"ui-icon-set --chevron-down\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"
	        });
	      }
	      return main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"salescenter-popup-mobile__wrap\">\n\t\t\t\t<div class=\"salescenter-popup-mobile__icon-box\">\n\t\t\t\t\t<div class=\"salescenter-popup-mobile__icon\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-popup-mobile__content\">\n\t\t\t\t\t<div class=\"salescenter-popup-mobile__title\">", "</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_TITLE'), phoneAndServiceContent);
	    }
	  }]);
	  return MobileAppInstallPopup;
	}();

	var _templateObject$2, _templateObject2, _templateObject3, _templateObject4;
	var instances = new Map();
	var App = /*#__PURE__*/function () {
	  babelHelpers.createClass(App, null, [{
	    key: "getByDialogId",
	    value: function getByDialogId(dialogId) {
	      return instances.get(dialogId) || null;
	    }
	  }]);
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
	      isOrderPublicUrlExists: false,
	      isWithOrdersMode: true,
	      compilation: null,
	      documentSelector: DocumentSelectorParams | null
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
	    this.orderId = parseInt(options.orderId);
	    this.stageOnOrderPaid = null;
	    this.stageOnDeliveryFinished = null;
	    this.sendingMethod = '';
	    this.sendingMethodDesc = {};
	    this.orderPublicUrl = '';
	    this.fileControl = options.fileControl;
	    this.currencyCode = options.currencyCode;
	    this.compilation = null;
	    this.newCompilationId = null;
	    this.assignedById = options.assignedById;
	    this.isPhoneConfirmed = options.isPhoneConfirmed;
	    if (main_core.Type.isString(options.stageOnOrderPaid)) {
	      this.stageOnOrderPaid = options.stageOnOrderPaid;
	    }
	    if (main_core.Type.isString(options.stageOnDeliveryFinished)) {
	      this.stageOnDeliveryFinished = options.stageOnDeliveryFinished;
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
	    if (main_core.Type.isBoolean(options.isWithOrdersMode)) {
	      this.isWithOrdersMode = options.isWithOrdersMode;
	    } else {
	      this.isWithOrdersMode = false;
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
	      this.context = ContextDictionary.imOpenlines;
	    }
	    if (main_core.Type.isBoolean(options.isPaymentsLimitReached)) {
	      this.isPaymentsLimitReached = options.isPaymentsLimitReached;
	    } else {
	      this.isPaymentsLimitReached = false;
	    }
	    if (!main_core.Type.isUndefined(options.sendingMethod)) {
	      this.sendingMethod = options.sendingMethod;
	    }
	    if (!main_core.Type.isUndefined(options.sendingMethodDesc)) {
	      this.sendingMethodDesc = this.options.sendingMethodDesc;
	    }
	    if (main_core.Type.isObject(options.compilation)) {
	      this.compilation = options.compilation;
	    }
	    this.isPaymentCreationAvailable = this.sessionId > 0 && this.dialogId.length > 0 || this.ownerTypeId && this.ownerId;
	    this.connector = main_core.Type.isString(options.connector) ? options.connector : '';
	    this.isAllowedFacebookRegion = main_core.Type.isBoolean(options.isAllowedFacebookRegion) ? options.isAllowedFacebookRegion : false;
	    if (main_core.Type.isPlainObject(options.documentSelector)) {
	      this.documentSelector = options.documentSelector;
	      this.documentSelector.paymentId = this.options.paymentId;
	    }
	    main_core.Event.ready(function () {
	      _this.pull = BX.PULL;
	      _this.initPull();
	      _this.isSiteExists = salescenter_manager.Manager.isSiteExists;
	    });
	    App.initStore().then(function (result) {
	      return _this.initTemplate(result);
	    })["catch"](function (error) {
	      return App.showError(error);
	    });
	    main_core_events.EventEmitter.subscribe(window.parent, 'onSendCompilationChatButtonClick', this.sendCompilation.bind(this));
	    instances.set(this.dialogId, this);
	  }
	  babelHelpers.createClass(App, [{
	    key: "initPull",
	    value: function initPull() {
	      var _this2 = this;
	      if (this.pull) {
	        if (main_core.Type.isString(this.orderAddPullTag)) {
	          this.pull.subscribe({
	            moduleId: 'salescenter',
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
	            moduleId: 'salescenter',
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
	            moduleId: 'salescenter',
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
	          components: {
	            'chat': Chat,
	            'deal': Deal
	          },
	          template: _this3.isPaymentMode() ? "<deal :key=\"componentKey\" @on-reload=\"reload\"/>" : "<chat :key=\"componentKey\" @on-reload=\"reload\"/>",
	          store: _this3.store,
	          created: function created() {
	            this.$app = context;
	            this.$nodes = {
	              footer: document.getElementById('footer'),
	              leftPanel: document.getElementById('left-panel'),
	              title: document.getElementById('pagetitle'),
	              paymentsLimit: document.getElementById('salescenter-payment-limit-container'),
	              orderSelector: document.getElementById('salescenter-app-order-selector')
	            };
	            this.initOrderSelector();
	            if (context.documentSelector) {
	              this.$store.commit('documentSelector/fillState', context.documentSelector);
	            }
	            if (this.$app.options.showCompilationModeSwitcher === 'N') {
	              this.$store.commit('orderCreation/enableCompilationMode');
	            }
	          },
	          mounted: function mounted() {
	            resolve();
	          },
	          methods: {
	            reload: function reload(arParams) {
	              this.$root.$app.getLoader().show(document.body);
	              main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getComponentResult', {
	                mode: 'class',
	                data: {
	                  arParams: arParams
	                }
	              }).then(function (response) {
	                if (response.data) {
	                  this.$root.$app.options = response.data;
	                  this.$root.$app.orderId = this.$root.$app.options.orderId;
	                  this.componentKey += 1;
	                  this.$root.$app.getLoader().hide();
	                }
	              }.bind(this));
	            },
	            initOrderSelector: function initOrderSelector() {
	              var _this4 = this;
	              try {
	                if (this.$app.options.orderList.length < 2 || this.$app.options.templateMode !== 'create' || !this.$app.options.orderId) {
	                  return;
	                }
	                var orderSelectorBtn = this.$nodes.orderSelector.querySelector('.salescenter-app-order-selector-text');
	                if (!orderSelectorBtn) {
	                  return;
	                }
	                orderSelectorBtn.innerText = main_core.Loc.getMessage('SALESCENTER_ORDER_SELECTOR_ORDER_NUM').replace('#ORDER_ID#', this.$app.options.orderId);
	                orderSelectorBtn.setAttribute('data-hint', main_core.Loc.getMessage('SALESCENTER_ORDER_SELECTOR_TOOLTIP'));
	                var popupMenu;
	                var menuItems = [];
	                this.$app.options.orderList.map(function (orderId) {
	                  var orderCaption = main_core.Loc.getMessage('SALESCENTER_ORDER_SELECTOR_ORDER_NUM').replace('#ORDER_ID#', orderId);
	                  menuItems.push({
	                    text: orderCaption,
	                    onclick: function onclick(event) {
	                      popupMenu.close();
	                      orderSelectorBtn.innerText = orderCaption;
	                      _this4.reload({
	                        context: _this4.$app.options.context,
	                        orderId: orderId,
	                        ownerTypeId: _this4.$app.options.ownerTypeId,
	                        ownerId: _this4.$app.options.ownerId,
	                        templateMode: _this4.$app.options.templateMode,
	                        mode: _this4.$app.options.mode,
	                        initialMode: _this4.$app.options.initialMode
	                      });
	                    }
	                  });
	                });
	                popupMenu = main_popup.MenuManager.create({
	                  id: 'deal-order-selector',
	                  bindElement: orderSelectorBtn,
	                  items: menuItems
	                });
	                this.$nodes.orderSelector.classList.remove('is-hidden');
	                this.$nodes.orderSelector.addEventListener('click', function (e) {
	                  e.preventDefault();
	                  popupMenu.show();
	                  BX.UI.Hint.hide();
	                });
	                BX.UI.Hint.init(this.$nodes.orderSelector);
	              } catch (err) {
	                //
	              }
	            }
	          },
	          data: function data() {
	            return {
	              componentKey: 0
	            };
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
	      var _this5 = this;
	      return new Promise(function (resolve) {
	        if (_this5.isProgress) {
	          _this5.fillPagesQueue.push(resolve);
	        } else {
	          if (_this5.fillPagesTimeout) {
	            clearTimeout(_this5.fillPagesTimeout);
	          }
	          _this5.fillPagesTimeout = setTimeout(function () {
	            _this5.startProgress();
	            rest_client.rest.callMethod('salescenter.page.list', {}).then(function (result) {
	              _this5.store.commit('application/setPages', {
	                pages: result.answer.result.pages
	              });
	              _this5.stopProgress();
	              resolve();
	              _this5.fillPagesQueue.forEach(function (item) {
	                item();
	              });
	              _this5.fillPagesQueue = [];
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
	          size: 200,
	          mode: 'custom'
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
	      var _this6 = this;
	      return new Promise(function (resolve, reject) {
	        var promise;
	        if (page.landingId > 0) {
	          promise = salescenter_manager.Manager.hidePage(page);
	        } else {
	          promise = salescenter_manager.Manager.deleteUrl(page);
	        }
	        promise.then(function () {
	          _this6.store.commit('application/removePage', {
	            page: page
	          });
	          resolve();
	        })["catch"](function (result) {
	          App.showError(result.answer.error_description);
	          reject(result.answer.error_description);
	        });
	      });
	    }
	  }, {
	    key: "sendPage",
	    value: function sendPage(pageId) {
	      var _this7 = this;
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
	        if (this.context === ContextDictionary.sms) {
	          this.startProgress();
	          BX.Salescenter.Manager.addAnalyticAction({
	            analyticsLabel: 'salescenterSendSms',
	            context: this.context,
	            source: source,
	            type: page.isWebform ? 'form' : 'info',
	            code: page.code
	          }).then(function () {
	            _this7.stopProgress();
	            _this7.closeApplication();
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
	        _this7.stopProgress();
	        _this7.closeApplication();
	      })["catch"](function (result) {
	        App.showError(result.errors.pop().message);
	        _this7.stopProgress();
	      });
	    }
	  }, {
	    key: "sendCompilation",
	    value: function sendCompilation() {
	      var buttonEvent = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var sendCompilationLinkToFacebook = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }
	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }
	      if (!this.isAllowedFacebookRegion) {
	        sendCompilationLinkToFacebook = true;
	      }
	      this.startProgress(buttonEvent);
	      var options = {
	        dialogId: this.dialogId,
	        sendingMethod: this.sendingMethod,
	        sendingMethodDesc: this.sendingMethodDesc,
	        ownerTypeId: this.ownerTypeId,
	        ownerId: this.ownerId,
	        connector: this.connector,
	        sessionId: this.sessionId,
	        sendCompilationLinkToFacebook: sendCompilationLinkToFacebook,
	        compilationId: this.compilation ? this.compilation.ID : this.newCompilationId,
	        editable: this.options.templateMode === 'create'
	      };
	      if (this.stageOnOrderPaid !== null) {
	        options.stageOnOrderPaid = this.stageOnOrderPaid;
	      }
	      if (this.stageOnDeliveryFinished !== null) {
	        options.stageOnDeliveryFinished = this.stageOnDeliveryFinished;
	      }
	      if (this.connector === 'facebook' && this.isAllowedFacebookRegion && !sendCompilationLinkToFacebook) {
	        this.sendCompilationToFacebook(buttonEvent, options);
	      } else {
	        this.sendCompilationAjaxAction(buttonEvent, options);
	      }
	    }
	  }, {
	    key: "sendCompilationToFacebook",
	    value: function sendCompilationToFacebook(buttonEvent, options) {
	      var _this8 = this;
	      main_core.ajax.runComponentAction('bitrix:salescenter.app', 'getFacebookSettingsPath', {
	        mode: 'class',
	        data: {
	          dialogId: this.dialogId
	        }
	      }).then(function (response) {
	        var facebookSettingsPath = response.data;
	        if (facebookSettingsPath) {
	          _this8.stopProgress(buttonEvent);
	          _this8.showFacebookCatalogConnectionPopup(buttonEvent, facebookSettingsPath);
	        } else {
	          main_core.ajax.runAction('salescenter.compilation.sendFacebookModerationWaitingNotification', {
	            data: {
	              options: options
	            }
	          }).then(function (result) {
	            _this8.sendCompilationAjaxAction(buttonEvent, options);
	            _this8.store.dispatch('orderCreation/resetBasket');
	            _this8.closeApplication();
	            _this8.stopProgress(buttonEvent);
	          });
	        }
	      });
	    }
	  }, {
	    key: "publishShop",
	    value: function publishShop() {
	      var _this9 = this;
	      if (!this.isPhoneConfirmed) {
	        return;
	      }
	      this.showLoader();
	      landing_backend.Backend.getInstance().action('Site::publication', {
	        id: salescenter_manager.Manager.connectedSiteId
	      }).then(function () {
	        _this9.slider.reload();
	      })["catch"](function (data) {
	        _this9.getLoader().hide();
	        if (data.type === 'error' && !main_core.Type.isUndefined(data.result[0])) {
	          var errorCode = data.result[0].error;
	          switch (errorCode) {
	            case 'PUBLIC_SITE_REACHED':
	              {
	                salescenter_manager.Manager.openLimitShopNumberInfoHelper();
	                break;
	              }
	            case 'PUBLIC_SITE_REACHED_FREE':
	              {
	                salescenter_manager.Manager.openFreeTarifInfoHelper();
	                break;
	              }
	            case 'FREE_DOMAIN_IS_NOT_ALLOWED':
	              {
	                salescenter_manager.Manager.openFreeDomenInfoHelper();
	                break;
	              }
	            case 'PHONE_NOT_CONFIRMED':
	              {
	                _this9.showPhoneConfirmPopup();
	                break;
	              }
	            case 'EMAIL_NOT_CONFIRMED':
	              {
	                _this9.showEmailConfirmPopup();
	                break;
	              }
	            default:
	              {
	                ui_dialogs_messagebox.MessageBox.alert(data.result[0].error_description);
	              }
	          }
	        }
	      });
	    }
	  }, {
	    key: "confirmPhoneNumber",
	    value: function confirmPhoneNumber() {
	      var _this10 = this;
	      if (!BX.Type.isObject(bitrix24_phoneverify.PhoneVerify)) {
	        return;
	      }
	      bitrix24_phoneverify.PhoneVerify.getInstance().setEntityType('landing_site').setEntityId(salescenter_manager.Manager.connectedSiteId).startVerify({
	        mandatory: false,
	        callback: function callback(verified) {
	          if (verified) {
	            _this10.isPhoneConfirmed = true;
	            main_core_events.EventEmitter.emit('BX.Salescenter.App::onPhoneConfirmed');
	          }
	        }
	      });
	    }
	  }, {
	    key: "showPhoneConfirmPopup",
	    value: function showPhoneConfirmPopup() {
	      var _this11 = this;
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('SALESCENTER_PHONE_CONFIRMATION_POPUP_MESSAGE'), main_core.Loc.getMessage('SALESCENTER_PHONE_CONFIRMATION_POPUP_TITLE'), function (messageBox) {
	        messageBox.close();
	        _this11.confirmPhoneNumber();
	      }, main_core.Loc.getMessage('SALESCENTER_CONFIRMATION_POPUP_OK_CAPTION'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('SALESCENTER_CONFIRMATION_POPUP_CANCEL_CAPTION'));
	    }
	  }, {
	    key: "showEmailConfirmPopup",
	    value: function showEmailConfirmPopup() {
	      ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('SALESCENTER_EMAIL_CONFIRMATION_POPUP_MESSAGE'), main_core.Loc.getMessage('SALESCENTER_EMAIL_CONFIRMATION_POPUP_TITLE'), function (messageBox) {
	        messageBox.close();
	        salescenter_manager.Manager.openConfirmEmailInfoHelper();
	      }, main_core.Loc.getMessage('SALESCENTER_CONFIRMATION_POPUP_OK_CAPTION'), function (messageBox) {
	        return messageBox.close();
	      }, main_core.Loc.getMessage('SALESCENTER_CONFIRMATION_POPUP_CANCEL_CAPTION'));
	    }
	  }, {
	    key: "showFacebookCatalogConnectionPopup",
	    value: function showFacebookCatalogConnectionPopup(buttonEvent, facebookSettingsPath) {
	      if (!this.facebookCatalogConnectionPopup) {
	        this.facebookCatalogConnectionPopup = new main_popup.Popup({
	          className: 'salescenter-app-catalog-facebook-connection-popup',
	          content: this.getFacebookCatalogConnectionPopupContent(buttonEvent, facebookSettingsPath),
	          width: 500,
	          overlay: true,
	          offsetTop: 0,
	          offsetLeft: 0,
	          padding: 17,
	          animation: 'fading-slide',
	          angle: false,
	          closeIcon: {
	            top: '5px',
	            right: '5px'
	          }
	        });
	      }
	      this.facebookCatalogConnectionPopup.show();
	    }
	  }, {
	    key: "getFacebookCatalogConnectionPopupContent",
	    value: function getFacebookCatalogConnectionPopupContent(buttonEvent, facebookSettingsPath) {
	      var setFacebookCatalogConnectionButton = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-primary\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), main_core.Loc.getMessage('SALESCENTER_FACEBOOK_CATALOG_POPUP_SET_BUTTON'));
	      main_core.Event.bind(setFacebookCatalogConnectionButton, 'click', this.setFacebookCatalogConnectionPopupHandler.bind(this, facebookSettingsPath));
	      var sendLinkToB24CompilationButton = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-light-border\">\n\t\t\t\t", "\n\t\t\t</button>\n\t\t"])), main_core.Loc.getMessage('SALESCENTER_FACEBOOK_CATALOG_POPUP_SEND_B24_COMPILATION_LINK_BUTTON'));
	      main_core.Event.bind(sendLinkToB24CompilationButton, 'click', this.sendLinkToB24CompilationButtonPopupHandler.bind(this, buttonEvent));
	      return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"salescenter-app-catalog-facebook-connection-popup--container\">\n\t\t\t\t<div class=\"salescenter-app-catalog-facebook-connection-popup--title\">", "</div>\n\t\t\t\t<div class=\"salescenter-app-catalog-facebook-connection-popup--button-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"])), main_core.Loc.getMessage('SALESCENTER_FACEBOOK_CATALOG_POPUP_TITLE_1'), setFacebookCatalogConnectionButton, sendLinkToB24CompilationButton);
	    }
	  }, {
	    key: "setFacebookCatalogConnectionPopupHandler",
	    value: function setFacebookCatalogConnectionPopupHandler(facebookSettingsPath) {
	      BX.SidePanel.Instance.open(facebookSettingsPath);
	      this.facebookCatalogConnectionPopup.close();
	    }
	  }, {
	    key: "sendLinkToB24CompilationButtonPopupHandler",
	    value: function sendLinkToB24CompilationButtonPopupHandler(buttonEvent) {
	      this.facebookCatalogConnectionPopup.close();
	      this.sendCompilation(buttonEvent, true);
	    }
	  }, {
	    key: "sendCompilationAjaxAction",
	    value: function sendCompilationAjaxAction(buttonEvent, options) {
	      var _this12 = this;
	      var basketItems = this.store.getters['orderCreation/getBasket']();
	      var productIds = basketItems.map(function (basketItem) {
	        return basketItem.skuId;
	      });
	      main_core.ajax.runAction('salescenter.compilation.sendCompilation', {
	        data: {
	          productIds: productIds,
	          options: options
	        },
	        analyticsLabel: 'salescenterCreateCompilation'
	      }).then(function (result) {
	        _this12.store.dispatch('orderCreation/resetBasket');
	        _this12.stopProgress(buttonEvent);
	        if (result.data && result.data.compilation) {
	          _this12.slider.data.set('action', 'sendCompilation');
	          _this12.slider.data.set('compilation', result.data.compilation);
	        }
	        _this12.closeApplication();
	        _this12.emitGlobalEvent('salescenter.app:oncompilationcreated');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          alert(error.message);
	        });
	        _this12.stopProgress(buttonEvent);
	        App.showError(data);
	      });
	    }
	  }, {
	    key: "sendShipment",
	    value: function sendShipment(buttonEvent) {
	      var _this13 = this;
	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }
	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }
	      this.startProgress(buttonEvent);
	      var data = {
	        ownerTypeId: this.ownerTypeId,
	        ownerId: this.ownerId,
	        orderId: this.orderId,
	        deliveryId: this.store.getters['orderCreation/getDeliveryId'],
	        deliveryPrice: this.store.getters['orderCreation/getDelivery'],
	        expectedDeliveryPrice: this.store.getters['orderCreation/getExpectedDelivery'],
	        deliveryResponsibleId: this.store.getters['orderCreation/getDeliveryResponsibleId'],
	        personTypeId: this.store.getters['orderCreation/getPersonTypeId'],
	        shipmentPropValues: this.store.getters['orderCreation/getPropertyValues'],
	        deliveryExtraServicesValues: this.store.getters['orderCreation/getDeliveryExtraServicesValues']
	      };
	      if (this.stageOnDeliveryFinished !== null) {
	        data.stageOnDeliveryFinished = this.stageOnDeliveryFinished;
	      }
	      main_core.ajax.runAction('salescenter.order.createShipment', {
	        data: {
	          basketItems: this.store.getters['orderCreation/getBasket'](),
	          options: data
	        },
	        analyticsLabel: 'salescenterCreateShipment'
	      }).then(function (result) {
	        _this13.store.dispatch('orderCreation/resetBasket');
	        _this13.stopProgress(buttonEvent);
	        if (result.data) {
	          if (result.data.order) {
	            _this13.slider.data.set('order', result.data.order);
	          }
	          if (result.data.deal) {
	            _this13.slider.data.set('deal', result.data.deal);
	          }
	        }
	        _this13.closeApplication();
	        _this13.emitGlobalEvent('salescenter.app:onshipmentcreated');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          alert(error.message);
	        });
	        _this13.stopProgress(buttonEvent);
	        App.showError(data);
	      });
	    }
	  }, {
	    key: "sendPayment",
	    value: function sendPayment(buttonEvent) {
	      var _this14 = this;
	      var skipPublicMessage = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'n';
	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }
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
	        orderId: this.orderId,
	        ownerId: this.ownerId,
	        mode: this.options.mode,
	        skipPublicMessage: skipPublicMessage,
	        deliveryId: this.store.getters['orderCreation/getDeliveryId'],
	        deliveryPrice: this.store.getters['orderCreation/getDelivery'],
	        expectedDeliveryPrice: this.store.getters['orderCreation/getExpectedDelivery'],
	        deliveryResponsibleId: this.store.getters['orderCreation/getDeliveryResponsibleId'],
	        personTypeId: this.store.getters['orderCreation/getPersonTypeId'],
	        shipmentPropValues: this.store.getters['orderCreation/getPropertyValues'],
	        deliveryExtraServicesValues: this.store.getters['orderCreation/getDeliveryExtraServicesValues'],
	        availablePaySystemsIds: this.store.getters['orderCreation/getAvailablePaySystemsIds'],
	        connector: this.connector,
	        context: this.context,
	        currency: this.currencyCode,
	        assignedById: this.assignedById
	      };
	      if (this.documentSelector) {
	        data.boundDocumentId = this.store.getters['documentSelector/getBoundDocumentId'];
	        data.selectedTemplateId = this.store.getters['documentSelector/getSelectedTemplateId'];
	      }
	      if (this.stageOnOrderPaid !== null) {
	        data.stageOnOrderPaid = this.stageOnOrderPaid;
	      }
	      if (this.stageOnDeliveryFinished !== null) {
	        data.stageOnDeliveryFinished = this.stageOnDeliveryFinished;
	      }
	      main_core.ajax.runAction('salescenter.order.createPayment', {
	        data: {
	          basketItems: this.store.getters['orderCreation/getBasket'](),
	          options: data
	        },
	        analyticsLabel: this.context === ContextDictionary.deal ? 'salescenterCreatePaymentSms' : 'salescenterCreatePayment',
	        getParameters: {
	          dialogId: this.dialogId,
	          context: this.context,
	          connector: this.connector,
	          skipPublicMessage: skipPublicMessage
	        }
	      }).then(function (result) {
	        _this14.store.dispatch('orderCreation/resetBasket');
	        _this14.stopProgress(buttonEvent);
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
	            ownerId: _this14.ownerId,
	            ownerTypeId: _this14.ownerTypeId,
	            context: _this14.context
	          });
	        } else {
	          _this14.slider.data.set('action', 'sendPayment');
	          _this14.slider.data.set('order', result.data.order);
	          if (result.data.deal) {
	            _this14.slider.data.set('deal', result.data.deal);
	          }
	          if (result.data.entity) {
	            _this14.slider.data.set('entity', result.data.entity);
	          }
	          _this14.closeApplication();
	        }
	        _this14.emitGlobalEvent('salescenter.app:onpaymentcreated');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          top.BX.UI.Notification.Center.notify({
	            content: main_core.Text.encode(error.message)
	          });
	        });
	        _this14.stopProgress(buttonEvent);
	        App.showError(data);
	        if (_this14.needCloseApplication(data.errors)) {
	          _this14.closeApplication();
	        }
	      });
	    }
	  }, {
	    key: "sendTerminalPayment",
	    value: function sendTerminalPayment(buttonEvent) {
	      var _this15 = this;
	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }
	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }
	      this.startProgress(buttonEvent);
	      var data = {
	        ownerTypeId: this.ownerTypeId,
	        ownerId: this.ownerId,
	        orderId: this.orderId,
	        paymentResponsibleId: this.store.getters['orderCreation/getPaymentResponsibleId'],
	        context: this.context,
	        currency: this.currencyCode,
	        assignedById: this.assignedById
	      };
	      if (this.stageOnOrderPaid !== null) {
	        data.stageOnOrderPaid = this.stageOnOrderPaid;
	      }
	      var isMobileInstalledForResponsible = this.store.getters['orderCreation/isMobileInstalledForResponsible'];
	      main_core.ajax.runAction('salescenter.order.createTerminalPayment', {
	        data: {
	          basketItems: this.store.getters['orderCreation/getBasket'](),
	          options: data
	        },
	        analyticsLabel: 'salescenterCreateTerminalPayment',
	        getParameters: {
	          context: this.context
	        }
	      }).then(function (result) {
	        _this15.store.dispatch('orderCreation/resetBasket');
	        _this15.stopProgress(buttonEvent);
	        _this15.slider.data.set('action', 'sendPayment');
	        _this15.slider.data.set('order', result.data.order);
	        if (result.data.deal) {
	          _this15.slider.data.set('deal', result.data.deal);
	        }
	        if (result.data.entity) {
	          _this15.slider.data.set('entity', result.data.entity);
	        }
	        if (isMobileInstalledForResponsible) {
	          _this15.closeApplication();
	        } else {
	          _this15.showMobileAppInstallLinkPopup();
	        }
	        _this15.emitGlobalEvent('salescenter.app:onterminalpaymentcreated');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          top.BX.UI.Notification.Center.notify({
	            content: main_core.Text.encode(error.message)
	          });
	        });
	        _this15.stopProgress(buttonEvent);
	        App.showError(data);
	        if (_this15.needCloseApplication(data.errors)) {
	          _this15.closeApplication();
	        }
	      });
	    }
	  }, {
	    key: "showMobileAppInstallLinkPopup",
	    value: function showMobileAppInstallLinkPopup() {
	      var responsiblePhoneNumbers = this.store.getters['orderCreation/getResponsiblePhoneNumbers'];
	      new MobileAppInstallPopup({
	        sendersConfig: this.options.senders,
	        phoneNumbers: responsiblePhoneNumbers,
	        userId: this.store.getters['orderCreation/getPaymentResponsibleId'],
	        root: this
	      }).render();
	    }
	  }, {
	    key: "updateTerminalPayment",
	    value: function updateTerminalPayment(buttonEvent) {
	      var _this16 = this;
	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }
	      this.startProgress(buttonEvent);
	      var data = {
	        paymentResponsibleId: this.store.getters['orderCreation/getPaymentResponsibleId']
	      };
	      main_core.ajax.runAction('salescenter.order.updateTerminalPayment', {
	        data: {
	          paymentId: this.options.paymentId,
	          options: data
	        },
	        analyticsLabel: 'salescenterUpdateTerminalPayment',
	        getParameters: {
	          context: this.context
	        }
	      }).then(function (result) {
	        _this16.store.dispatch('orderCreation/resetBasket');
	        _this16.stopProgress(buttonEvent);
	        _this16.slider.data.set('action', 'sendPayment');
	        _this16.slider.data.set('order', result.data.order);
	        if (result.data.deal) {
	          _this16.slider.data.set('deal', result.data.deal);
	        }
	        if (result.data.entity) {
	          _this16.slider.data.set('entity', result.data.entity);
	        }
	        _this16.closeApplication();
	        _this16.emitGlobalEvent('salescenter.app:onterminalpaymentupdated');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          top.BX.UI.Notification.Center.notify({
	            content: main_core.Text.encode(error.message)
	          });
	        });
	        _this16.stopProgress(buttonEvent);
	        App.showError(data);
	        if (_this16.needCloseApplication(data.errors)) {
	          _this16.closeApplication();
	        }
	      });
	    }
	  }, {
	    key: "openMobileAppPopup",
	    value: function openMobileAppPopup() {
	      var popupIconClass = this.options.currentLanguage === 'ru' ? 'salescenter-popup-qr__icon --ru' : 'salescenter-popup-qr__icon';

	      // the mobile app popup goes here
	      var popupContent = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"salescenter-popup-qr__box\">\n\t\t\t\t<div class=\"salescenter-popup-qr__title\">", "</div>\n\t\t\t\t<div class=\"salescenter-popup-qr__desc\">", "</div>\n\t\t\t\t<div class=\"salescenter-popup-qr__content\">\n\t\t\t\t\t<div class=\"salescenter-popup-qr__code\"></div>\n\t\t\t\t\t<ul class=\"salescenter-popup-qr__list\">\n\t\t\t\t\t\t<li class=\"salescenter-popup-qr__list_item\">", "</li>\n\t\t\t\t\t\t<li class=\"salescenter-popup-qr__list_item\">", "</li>\n\t\t\t\t\t\t<li class=\"salescenter-popup-qr__list_item\">", "</li>\n\t\t\t\t\t\t<li class=\"salescenter-popup-qr__list_item\">", "</li>\n\t\t\t\t\t</ul>\n\t\t\t\t\t<div class=\"salescenter-popup-qr__icon_box\">\n\t\t\t\t\t\t<div class=\"", "\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\t\t\n\t\t"])), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_TITLE'), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_DESC'), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_LIST_ITEM_1'), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_LIST_ITEM_2'), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_LIST_ITEM_3'), main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_LIST_ITEM_4'), popupIconClass);
	      var mobilePopup = new main_popup.Popup({
	        className: 'salescenter-popup-qr__wrap',
	        content: popupContent,
	        overlay: true,
	        closeIcon: true,
	        maxWidth: 725,
	        autoHide: true,
	        cacheable: false,
	        buttons: [new ui_buttons.Button({
	          color: ui_buttons.Button.Color.PRIMARY,
	          text: main_core.Loc.getMessage('SALESCENTER_JS_POPUP_CLOSE'),
	          onclick: function onclick() {
	            mobilePopup.close();
	          }
	        }), new ui_buttons.Button({
	          color: ui_buttons.Button.Color.LINK,
	          text: main_core.Loc.getMessage('SALESCENTER_TERMINAL_QR_POPUP_BUTTON_ABOUT'),
	          onclick: function onclick() {
	            salescenter_manager.Manager.openHowTerminalWorks();
	          }
	        })]
	      });
	      mobilePopup.show();
	      this.getQRCode();
	    }
	  }, {
	    key: "getQRCode",
	    value: function getQRCode() {
	      var qrNode = document.querySelector('.salescenter-popup-qr__code');
	      return new QRCode(qrNode, {
	        text: this.options.mobileAppLink,
	        width: 143,
	        height: 143
	      });
	    }
	  }, {
	    key: "needCloseApplication",
	    value: function needCloseApplication(errors) {
	      var alwaysOpen = errors.filter(function (error) {
	        return error.code < 1 || error.code > 100;
	      }).length >= 1;
	      return alwaysOpen === false;
	    }
	  }, {
	    key: "resendPayment",
	    value: function resendPayment(buttonEvent) {
	      var _this17 = this;
	      if (!this.isPaymentCreationAvailable) {
	        this.closeApplication();
	        return null;
	      }
	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }
	      this.startProgress(buttonEvent);
	      var options = {
	        sendingMethod: this.sendingMethod,
	        sendingMethodDesc: this.sendingMethodDesc,
	        stageOnOrderPaid: this.stageOnOrderPaid,
	        ownerTypeId: this.ownerTypeId,
	        ownerId: this.ownerId
	      };
	      if (this.documentSelector) {
	        options.boundDocumentId = this.store.getters['documentSelector/getBoundDocumentId'];
	        options.selectedTemplateId = this.store.getters['documentSelector/getSelectedTemplateId'];
	      }
	      main_core.ajax.runAction('salescenter.order.resendPayment', {
	        data: {
	          orderId: this.orderId,
	          paymentId: this.options.paymentId,
	          shipmentId: this.options.shipmentId,
	          options: options
	        },
	        getParameters: {
	          context: this.context
	        }
	      }).then(function (result) {
	        _this17.stopProgress(buttonEvent);
	        _this17.closeApplication();
	        _this17.emitGlobalEvent('salescenter.app:onpaymentresend');
	      })["catch"](function (data) {
	        data.errors.forEach(function (error) {
	          alert(error.message);
	        });
	        _this17.stopProgress(buttonEvent);
	        App.showError(data);
	      });
	    }
	  }, {
	    key: "hideNoPaymentSystemsBanner",
	    value: function hideNoPaymentSystemsBanner() {
	      var userOptionName = this.options.orderCreationOption || false;
	      var userOptionKeyName = this.options.paySystemBannerOptionName || false;
	      if (userOptionName && userOptionKeyName) {
	        BX.userOptions.save('salescenter', userOptionName, userOptionKeyName, 'Y');
	      }
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
	  }, {
	    key: "getPaymentsCount",
	    value: function getPaymentsCount() {
	      if (this.sessionId > 0) {
	        return rest_client.rest.callMethod('salescenter.order.getActivePaymentsCount', {
	          sessionId: this.sessionId
	        });
	      } else {
	        return new Promise(function (resolve, reject) {});
	      }
	    }
	  }, {
	    key: "hasClientContactInfo",
	    value: function hasClientContactInfo() {
	      if (this.options.sendingMethod === 'chat') {
	        return this.options.dialogId !== '';
	      }
	      return this.options.contactPhone !== '';
	    }
	  }, {
	    key: "emitGlobalEvent",
	    value: function emitGlobalEvent(eventName, data) {
	      main_core_events.EventEmitter.emit(eventName, data);
	      BX.SidePanel.Instance.postMessage(this.slider, eventName, data);
	    }
	  }, {
	    key: "isPaymentMode",
	    value: function isPaymentMode() {
	      return this.context === ContextDictionary.deal || this.context === ContextDictionary.smartInvoice || this.context === ContextDictionary.terminalList;
	    }
	  }], [{
	    key: "initStore",
	    value: function initStore() {
	      var builder = new ui_vue_vuex.VuexBuilder();
	      return builder.addModel(ApplicationModel.create()).addModel(OrderCreationModel.create()).addModel(DocumentSelectorModel.create()).useNamespace(true).build();
	    }
	  }, {
	    key: "showError",
	    value: function showError(error) {
	      // console.error(error);
	    }
	  }]);
	  return App;
	}();

	exports.App = App;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX.Bitrix24,BX,BX.UI.Dialogs,BX,BX.Salescenter,BX.Salescenter.Component.StageBlock,BX.Salescenter.Component.StageBlock,BX.Catalog,BX,BX.Salescenter,BX,BX,BX.Salescenter.Component.StageBlock,BX.Salescenter.AutomationStage,BX.Salescenter.Component.StageBlock.TimeLine,BX,BX,BX,BX,BX,BX,BX.Event,BX.Salescenter.Component.StageBlock,BX.Salescenter,BX.Salescenter,BX,BX.Salescenter.Tile,BX.UI.EntitySelector,BX.Salescenter.Component,BX.Currency,BX.Salescenter,BX.Landing,BX.Landing,BX,BX,BX,BX,BX.Main,BX.UI));
//# sourceMappingURL=app.bundle.js.map
