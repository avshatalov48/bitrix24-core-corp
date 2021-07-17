this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core_events,currency,ui_notification,ui_vue,main_core,pull_client) {
	'use strict';

	var HistoryItemMixin = {
	  props: {
	    self: {
	      required: true,
	      type: Object
	    },
	    langMessages: {
	      required: false,
	      type: Object
	    }
	  },
	  computed: {
	    data: function data() {
	      return this.self._data;
	    },
	    fields: function fields() {
	      return this.data.FIELDS ? this.data.FIELDS : null;
	    },
	    author: function author() {
	      return this.data.AUTHOR ? this.data.AUTHOR : null;
	    },
	    createdAt: function createdAt() {
	      return this.self instanceof BX.CrmHistoryItem ? this.self.formatTime(this.self.getCreatedTime()) : '';
	    }
	  },
	  methods: {
	    getLangMessage: function getLangMessage(key) {
	      return this.langMessages.hasOwnProperty(key) ? this.langMessages[key] : key;
	    }
	  }
	};

	var Product = {
	  props: {
	    product: {
	      required: true,
	      type: Object
	    },
	    dealId: {
	      required: true,
	      type: Number
	    },
	    isAddToDealVisible: {
	      required: true,
	      type: Boolean
	    }
	  },
	  methods: {
	    addProductToDeal: function addProductToDeal() {
	      var _this = this;

	      if (this.product.isInDeal) {
	        return;
	      }

	      this.$emit('product-adding-to-deal');
	      this.product.isInDeal = true;
	      main_core.ajax.runAction('crm.timeline.encouragebuyproducts.addproducttodeal', {
	        data: {
	          dealId: this.dealId,
	          productId: this.product.offerId,
	          options: {
	            price: this.product.price
	          }
	        }
	      }).then(function (result) {
	        _this.$emit('product-added-to-deal');

	        _this.product.isInDeal = true;
	      }).catch(function (result) {
	        _this.product.isInDeal = false;
	      });
	    },
	    openDetailPage: function openDetailPage() {
	      if (BX.type.isNotEmptyString(this.product.adminLink)) {
	        var _this$product;

	        if (((_this$product = this.product) === null || _this$product === void 0 ? void 0 : _this$product.slider) === 'N') {
	          window.open(this.product.adminLink, '_blank');
	        } else {
	          BX.SidePanel.Instance.open(this.product.adminLink);
	        }
	      }
	    }
	  },
	  computed: {
	    isBottomAreaVisible: function isBottomAreaVisible() {
	      return this.isVariationInfoVisible || this.isPriceVisible;
	    },
	    isVariationInfoVisible: function isVariationInfoVisible() {
	      return this.product.hasOwnProperty('variationInfo') && this.product.variationInfo;
	    },
	    isPriceVisible: function isPriceVisible() {
	      return this.product.hasOwnProperty('price') && this.product.hasOwnProperty('currency') && this.product.price && this.product.currency;
	    },
	    price: function price() {
	      return BX.Currency.currencyFormat(this.product.price, this.product.currency, true);
	    },
	    imageStyle: function imageStyle() {
	      if (!this.product.image) {
	        return {};
	      }

	      return {
	        backgroundImage: 'url(' + this.product.image + ')'
	      };
	    },
	    buttonText: function buttonText() {
	      return main_core.Loc.getMessage(this.product.isInDeal ? 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCT_IN_DEAL' : 'CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ADD_PRODUCT_TO_DEAL');
	    }
	  },
	  template: "\n\t\t<li\n\t\t\t:class=\"{'crm-entity-stream-advice-list-item--active': product.isInDeal}\"\n\t\t\tclass=\"crm-entity-stream-advice-list-item\"\n\t\t>\t\n\t\t\t<div class=\"crm-entity-stream-advice-list-content\">\n\t\t\t\t<div\t\n\t\t\t\t\t:style=\"imageStyle\"\n\t\t\t\t\tclass=\"crm-entity-stream-advice-list-icon\"\n\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-advice-list-inner\">\n\t\t\t\t\t<a\n\t\t\t\t\t\t@click.prevent=\"openDetailPage\"\n\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-name\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{product.name}}\n\t\t\t\t\t</a>\n\t\t\t\t\t<div\n\t\t\t\t\t\tv-if=\"isBottomAreaVisible\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-box\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"isVariationInfoVisible\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-name\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{product.variationInfo}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"isPriceVisible\"\n\t\t\t\t\t\t\tv-html=\"price\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-advice-list-desc-value\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div v-if=\"isAddToDealVisible\" class=\"crm-entity-stream-advice-list-btn-box\">\t\t\t\t\n\t\t\t\t<button\n\t\t\t\t\t@click=\"addProductToDeal\"\n\t\t\t\t\tclass=\"ui-btn ui-btn-round ui-btn-xs crm-entity-stream-advice-list-btn\"\n\t\t\t\t>\n\t\t\t\t\t{{buttonText}}\n\t\t\t\t</button>\n\t\t\t</div>\n\t\t</li>\n\t"
	};

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	var component = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'product': Product
	  },
	  data: function data() {
	    return {
	      isShortList: true,
	      shortListProductsCnt: 3,
	      isNotificationShown: false,
	      activeRequestsCnt: 0,
	      dealId: null,
	      products: [],
	      isProductsGridAvailable: false
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.products = this.data.VIEWED_PRODUCTS;
	    this.dealId = this.data.DEAL_ID;
	    this._productsGrid = null;
	    this.subscribeCustomEvents();
	    BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', function (controller) {
	      _this.setProductsGrid(controller.getProductList());
	    });
	  },
	  methods: {
	    setProductsGrid: function setProductsGrid(productsGrid) {
	      this._productsGrid = productsGrid;

	      if (this._productsGrid) {
	        this.onProductsGridChanged();
	        this.isProductsGridAvailable = true;
	      }
	    },
	    showMore: function showMore() {
	      this.isShortList = false;
	      var listWrap = document.querySelector('.crm-entity-stream-advice-list');
	      listWrap.style.maxHeight = 950 + 'px';
	    },
	    // region event handlers
	    handleProductAddingToDeal: function handleProductAddingToDeal() {
	      this.activeRequestsCnt++;
	    },
	    handleProductAddedToDeal: function handleProductAddedToDeal() {
	      var _this2 = this;

	      if (this.activeRequestsCnt > 0) {
	        this.activeRequestsCnt--;
	      }

	      if (!(this.activeRequestsCnt === 0 && this._productsGrid)) {
	        return;
	      }

	      BX.Crm.EntityEditor.getDefault().reload();

	      this._productsGrid.reloadGrid(false);

	      if (!this.isNotificationShown) {
	        ui_notification.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
	          events: {
	            onClose: function onClose(event) {
	              _this2.isNotificationShown = false;
	            }
	          },
	          actions: [{
	            title: main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_EDIT_PRODUCTS'),
	            events: {
	              click: function click(event, balloon, action) {
	                BX.onCustomEvent(window, 'OpenEntityDetailTab', ['tab_products']);
	                balloon.close();
	              }
	            }
	          }]
	        });
	        this.isNotificationShown = true;
	      }
	    },
	    // endregion
	    // region custom events
	    subscribeCustomEvents: function subscribeCustomEvents() {
	      main_core_events.EventEmitter.subscribe('EntityProductListController', this.onProductsGridCreated);
	      main_core_events.EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
	    },
	    unsubscribeCustomEvents: function unsubscribeCustomEvents() {
	      main_core_events.EventEmitter.unsubscribe('EntityProductListController', this.onProductsGridCreated);
	      main_core_events.EventEmitter.unsubscribe('BX.Crm.EntityEditor:onSave', this.onProductsGridChanged);
	    },
	    onProductsGridCreated: function onProductsGridCreated(event) {
	      this.setProductsGrid(event.getData()[0]);
	    },
	    onProductsGridChanged: function onProductsGridChanged(event) {
	      var _this3 = this;

	      if (!this._productsGrid) {
	        return;
	      }

	      var dealOfferIds = this._productsGrid.products.map(function (product, index) {
	        if (!(product.hasOwnProperty('fields') && product.fields.hasOwnProperty('OFFER_ID'))) {
	          return null;
	        }

	        return product.fields.OFFER_ID;
	      });

	      var _iterator = _createForOfIteratorHelper(this.products.entries()),
	          _step;

	      try {
	        var _loop = function _loop() {
	          var _step$value = babelHelpers.slicedToArray(_step.value, 2),
	              i = _step$value[0],
	              product = _step$value[1];

	          var isInDeal = dealOfferIds.some(function (id) {
	            return id == product.offerId;
	          });

	          if (product.isInDeal === isInDeal) {
	            return "continue";
	          }

	          ui_vue.Vue.set(_this3.products, i, Object.assign({}, product, {
	            isInDeal: isInDeal
	          }));
	        };

	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          var _ret = _loop();

	          if (_ret === "continue") continue;
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    },
	    // endregion
	    beforeDestroy: function beforeDestroy() {
	      this.unsubscribeCustomEvents();
	    }
	  },
	  computed: {
	    visibleProducts: function visibleProducts() {
	      var result = [];
	      var i = 1;

	      var _iterator2 = _createForOfIteratorHelper(this.products),
	          _step2;

	      try {
	        for (_iterator2.s(); !(_step2 = _iterator2.n()).done;) {
	          var product = _step2.value;

	          if (this.isShortList && i > this.shortListProductsCnt) {
	            break;
	          }

	          result.push(product);
	          i++;
	        }
	      } catch (err) {
	        _iterator2.e(err);
	      } finally {
	        _iterator2.f();
	      }

	      return result;
	    },
	    isShowMoreVisible: function isShowMoreVisible() {
	      return this.isShortList && this.products.length > this.shortListProductsCnt;
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-advice\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-advice\"></div>\n\t\t\t<div class=\"crm-entity-stream-advice-content\">\n\t\t\t\t<div class=\"crm-entity-stream-advice-info\">\n\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_LOOK_AT_CLIENT_PRODUCTS'), "\n\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_ENCOURAGE_CLIENT_BUY_PRODUCTS'), "\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-entity-stream-advice-inner\">\n\t\t\t\t\t<h3 class=\"crm-entity-stream-advice-subtitle\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_VIEWED_PRODUCTS'), "\n\t\t\t\t\t</h3>\n\t\t\t\t\t<!--<ul class=\"crm-entity-stream-advice-list\">-->\n\t\t\t\t\t<transition-group class=\"crm-entity-stream-advice-list\" name=\"list\" tag=\"ul\">\t\t\t\t\t\t\n\t\t\t\t\t\t<product\n\t\t\t\t\t\t\tv-for=\"product in visibleProducts\"\n\t\t\t\t\t\t\tv-bind:key=\"product\"\n\t\t\t\t\t\t\t:product=\"product\"\n\t\t\t\t\t\t\t:dealId=\"dealId\"\n\t\t\t\t\t\t\t:isAddToDealVisible=\"isProductsGridAvailable\"\n\t\t\t\t\t\t\t@product-added-to-deal=\"handleProductAddedToDeal\"\n\t\t\t\t\t\t\t@product-adding-to-deal=\"handleProductAddingToDeal\"\n\t\t\t\t\t\t></product>\n\t\t\t\t\t</transition-group>\n\t\t\t\t\t<!--</ul>-->\n\t\t\t\t\t<a\n\t\t\t\t\t\tv-if=\"isShowMoreVisible\"\n\t\t\t\t\t\t@click.prevent=\"showMore\"\n\t\t\t\t\t\tclass=\"crm-entity-stream-advice-link\"\n\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t>\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_SHOW_MORE'), "\n\t\t\t\t\t</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t")
	});

	var Author = {
	  props: {
	    author: {
	      required: true,
	      type: Object
	    }
	  },
	  computed: {
	    iStyle: function iStyle() {
	      if (!this.author.IMAGE_URL) {
	        return {};
	      }

	      return {
	        'background-image': 'url(' + this.author.IMAGE_URL + ')',
	        'background-size': '21px'
	      };
	    }
	  },
	  template: "\n\t\t<a\n\t\t\tv-if=\"author.SHOW_URL\"\n\t\t\t:href=\"author.SHOW_URL\"\n\t\t\ttarget=\"_blank\"\n\t\t\t:title=\"author.FORMATTED_NAME\"\n\t\t\tclass=\"ui-icon ui-icon-common-user crm-entity-stream-content-detail-employee\"\n\t\t>\n\t\t\t<i :style=\"iStyle\"></i>\t\n\t\t</a>\n\t"
	};

	var component$1 = ui_vue.Vue.extend({
	  mixins: [HistoryItemMixin],
	  components: {
	    'author': Author
	  },
	  data: function data() {
	    return {
	      entityData: null,
	      messageId: null,
	      text: null,
	      title: null,
	      status: {
	        name: null,
	        semantics: null,
	        description: null
	      },
	      provider: null,
	      isRefreshing: false
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.entityData = this.self.getAssociatedEntityData();

	    if (this.entityData['MESSAGE_INFO']) {
	      this.setMessageInfo(this.entityData['MESSAGE_INFO']);
	    }

	    pull_client.PULL.subscribe({
	      moduleId: 'notifications',
	      command: 'message_update',
	      callback: function callback(params) {
	        if (params.message.ID == _this.messageId) {
	          _this.refresh();
	        }
	      }
	    });

	    if (this.entityData['PULL_TAG_NAME']) {
	      pull_client.PULL.extendWatch(this.entityData['PULL_TAG_NAME']);
	    }
	  },
	  methods: {
	    setMessageInfo: function setMessageInfo(messageInfo) {
	      this.messageId = messageInfo['MESSAGE']['ID'];

	      if (messageInfo['HISTORY_ITEMS'] && Array.isArray(messageInfo['HISTORY_ITEMS']) && messageInfo['HISTORY_ITEMS'].length > 0 && messageInfo['HISTORY_ITEMS'][0] && messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA'] && messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION']) {
	        this.provider = messageInfo['HISTORY_ITEMS'][0]['PROVIDER_DATA']['DESCRIPTION'];
	        this.title = this.provider + ' ' + main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE');
	      } else {
	        this.title = this.capitalizeFirstLetter(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_MESSAGE'));
	      }

	      if (messageInfo['HISTORY_ITEMS'] && Array.isArray(messageInfo['HISTORY_ITEMS']) && messageInfo['HISTORY_ITEMS'].length > 0 && messageInfo['HISTORY_ITEMS'][0] && messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA'] && messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION']) {
	        this.status.name = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['DESCRIPTION'];
	        this.status.semantics = messageInfo['HISTORY_ITEMS'][0]['STATUS_DATA']['SEMANTICS'];
	        this.status.description = messageInfo['HISTORY_ITEMS'][0]['ERROR_MESSAGE'];
	      }

	      this.text = messageInfo['MESSAGE']['TEXT'] ? messageInfo['MESSAGE']['TEXT'] : main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_NO_MESSAGE_TEXT_2');
	    },
	    refresh: function refresh() {
	      var _this2 = this;

	      if (this.isRefreshing) {
	        return;
	      }

	      this.isRefreshing = true;
	      main_core.ajax.runAction('crm.timeline.notification.getmessageinfo', {
	        data: {
	          messageId: this.messageId
	        }
	      }).then(function (result) {
	        _this2.setMessageInfo(result.data);

	        _this2.isRefreshing = false;
	      }).catch(function (result) {
	        _this2.isRefreshing = false;
	      });
	    },
	    viewActivity: function viewActivity() {
	      this.self.view();
	    },
	    capitalizeFirstLetter: function capitalizeFirstLetter(str) {
	      var locale = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : navigator.language;
	      return str.replace(/^(?:[a-z\xB5\xDF-\xF6\xF8-\xFF\u0101\u0103\u0105\u0107\u0109\u010B\u010D\u010F\u0111\u0113\u0115\u0117\u0119\u011B\u011D\u011F\u0121\u0123\u0125\u0127\u0129\u012B\u012D\u012F\u0131\u0133\u0135\u0137\u013A\u013C\u013E\u0140\u0142\u0144\u0146\u0148\u0149\u014B\u014D\u014F\u0151\u0153\u0155\u0157\u0159\u015B\u015D\u015F\u0161\u0163\u0165\u0167\u0169\u016B\u016D\u016F\u0171\u0173\u0175\u0177\u017A\u017C\u017E-\u0180\u0183\u0185\u0188\u018C\u0192\u0195\u0199\u019A\u019E\u01A1\u01A3\u01A5\u01A8\u01AD\u01B0\u01B4\u01B6\u01B9\u01BD\u01BF\u01C5\u01C6\u01C8\u01C9\u01CB\u01CC\u01CE\u01D0\u01D2\u01D4\u01D6\u01D8\u01DA\u01DC\u01DD\u01DF\u01E1\u01E3\u01E5\u01E7\u01E9\u01EB\u01ED\u01EF\u01F0\u01F2\u01F3\u01F5\u01F9\u01FB\u01FD\u01FF\u0201\u0203\u0205\u0207\u0209\u020B\u020D\u020F\u0211\u0213\u0215\u0217\u0219\u021B\u021D\u021F\u0223\u0225\u0227\u0229\u022B\u022D\u022F\u0231\u0233\u023C\u023F\u0240\u0242\u0247\u0249\u024B\u024D\u024F-\u0254\u0256\u0257\u0259\u025B\u025C\u0260\u0261\u0263\u0265\u0266\u0268-\u026C\u026F\u0271\u0272\u0275\u027D\u0280\u0282\u0283\u0287-\u028C\u0292\u029D\u029E\u0345\u0371\u0373\u0377\u037B-\u037D\u0390\u03AC-\u03CE\u03D0\u03D1\u03D5-\u03D7\u03D9\u03DB\u03DD\u03DF\u03E1\u03E3\u03E5\u03E7\u03E9\u03EB\u03ED\u03EF-\u03F3\u03F5\u03F8\u03FB\u0430-\u045F\u0461\u0463\u0465\u0467\u0469\u046B\u046D\u046F\u0471\u0473\u0475\u0477\u0479\u047B\u047D\u047F\u0481\u048B\u048D\u048F\u0491\u0493\u0495\u0497\u0499\u049B\u049D\u049F\u04A1\u04A3\u04A5\u04A7\u04A9\u04AB\u04AD\u04AF\u04B1\u04B3\u04B5\u04B7\u04B9\u04BB\u04BD\u04BF\u04C2\u04C4\u04C6\u04C8\u04CA\u04CC\u04CE\u04CF\u04D1\u04D3\u04D5\u04D7\u04D9\u04DB\u04DD\u04DF\u04E1\u04E3\u04E5\u04E7\u04E9\u04EB\u04ED\u04EF\u04F1\u04F3\u04F5\u04F7\u04F9\u04FB\u04FD\u04FF\u0501\u0503\u0505\u0507\u0509\u050B\u050D\u050F\u0511\u0513\u0515\u0517\u0519\u051B\u051D\u051F\u0521\u0523\u0525\u0527\u0529\u052B\u052D\u052F\u0561-\u0587\u10D0-\u10FA\u10FD-\u10FF\u13F8-\u13FD\u1C80-\u1C88\u1D79\u1D7D\u1D8E\u1E01\u1E03\u1E05\u1E07\u1E09\u1E0B\u1E0D\u1E0F\u1E11\u1E13\u1E15\u1E17\u1E19\u1E1B\u1E1D\u1E1F\u1E21\u1E23\u1E25\u1E27\u1E29\u1E2B\u1E2D\u1E2F\u1E31\u1E33\u1E35\u1E37\u1E39\u1E3B\u1E3D\u1E3F\u1E41\u1E43\u1E45\u1E47\u1E49\u1E4B\u1E4D\u1E4F\u1E51\u1E53\u1E55\u1E57\u1E59\u1E5B\u1E5D\u1E5F\u1E61\u1E63\u1E65\u1E67\u1E69\u1E6B\u1E6D\u1E6F\u1E71\u1E73\u1E75\u1E77\u1E79\u1E7B\u1E7D\u1E7F\u1E81\u1E83\u1E85\u1E87\u1E89\u1E8B\u1E8D\u1E8F\u1E91\u1E93\u1E95-\u1E9B\u1EA1\u1EA3\u1EA5\u1EA7\u1EA9\u1EAB\u1EAD\u1EAF\u1EB1\u1EB3\u1EB5\u1EB7\u1EB9\u1EBB\u1EBD\u1EBF\u1EC1\u1EC3\u1EC5\u1EC7\u1EC9\u1ECB\u1ECD\u1ECF\u1ED1\u1ED3\u1ED5\u1ED7\u1ED9\u1EDB\u1EDD\u1EDF\u1EE1\u1EE3\u1EE5\u1EE7\u1EE9\u1EEB\u1EED\u1EEF\u1EF1\u1EF3\u1EF5\u1EF7\u1EF9\u1EFB\u1EFD\u1EFF-\u1F07\u1F10-\u1F15\u1F20-\u1F27\u1F30-\u1F37\u1F40-\u1F45\u1F50-\u1F57\u1F60-\u1F67\u1F70-\u1F7D\u1F80-\u1FB4\u1FB6\u1FB7\u1FBC\u1FBE\u1FC2-\u1FC4\u1FC6\u1FC7\u1FCC\u1FD0-\u1FD3\u1FD6\u1FD7\u1FE0-\u1FE7\u1FF2-\u1FF4\u1FF6\u1FF7\u1FFC\u214E\u2170-\u217F\u2184\u24D0-\u24E9\u2C30-\u2C5E\u2C61\u2C65\u2C66\u2C68\u2C6A\u2C6C\u2C73\u2C76\u2C81\u2C83\u2C85\u2C87\u2C89\u2C8B\u2C8D\u2C8F\u2C91\u2C93\u2C95\u2C97\u2C99\u2C9B\u2C9D\u2C9F\u2CA1\u2CA3\u2CA5\u2CA7\u2CA9\u2CAB\u2CAD\u2CAF\u2CB1\u2CB3\u2CB5\u2CB7\u2CB9\u2CBB\u2CBD\u2CBF\u2CC1\u2CC3\u2CC5\u2CC7\u2CC9\u2CCB\u2CCD\u2CCF\u2CD1\u2CD3\u2CD5\u2CD7\u2CD9\u2CDB\u2CDD\u2CDF\u2CE1\u2CE3\u2CEC\u2CEE\u2CF3\u2D00-\u2D25\u2D27\u2D2D\uA641\uA643\uA645\uA647\uA649\uA64B\uA64D\uA64F\uA651\uA653\uA655\uA657\uA659\uA65B\uA65D\uA65F\uA661\uA663\uA665\uA667\uA669\uA66B\uA66D\uA681\uA683\uA685\uA687\uA689\uA68B\uA68D\uA68F\uA691\uA693\uA695\uA697\uA699\uA69B\uA723\uA725\uA727\uA729\uA72B\uA72D\uA72F\uA733\uA735\uA737\uA739\uA73B\uA73D\uA73F\uA741\uA743\uA745\uA747\uA749\uA74B\uA74D\uA74F\uA751\uA753\uA755\uA757\uA759\uA75B\uA75D\uA75F\uA761\uA763\uA765\uA767\uA769\uA76B\uA76D\uA76F\uA77A\uA77C\uA77F\uA781\uA783\uA785\uA787\uA78C\uA791\uA793\uA794\uA797\uA799\uA79B\uA79D\uA79F\uA7A1\uA7A3\uA7A5\uA7A7\uA7A9\uA7B5\uA7B7\uA7B9\uA7BB\uA7BD\uA7BF\uA7C3\uA7C8\uA7CA\uA7F6\uAB53\uAB70-\uABBF\uFB00-\uFB06\uFB13-\uFB17\uFF41-\uFF5A]|\uD801[\uDC28-\uDC4F\uDCD8-\uDCFB]|\uD803[\uDCC0-\uDCF2]|\uD806[\uDCC0-\uDCDF]|\uD81B[\uDE60-\uDE7F]|\uD83A[\uDD22-\uDD43])/, function (char) {
	        return char.toLocaleUpperCase(locale);
	      });
	    }
	  },
	  computed: {
	    communication: function communication() {
	      return this.entityData['COMMUNICATION'] ? this.entityData['COMMUNICATION'] : null;
	    },
	    statusClass: function statusClass() {
	      return {
	        'crm-entity-stream-content-event-process': this.status.semantics === 'process',
	        'crm-entity-stream-content-event-successful': this.status.semantics === 'success',
	        'crm-entity-stream-content-event-missing': this.status.semantics === 'failure',
	        'crm-entity-stream-content-event-error-tip': this.isStatusError
	      };
	    },
	    isStatusError: function isStatusError() {
	      return this.status.semantics === 'failure' && !!this.status.description;
	    },
	    statusErrorDescription: function statusErrorDescription() {
	      return this.isStatusError ? this.status.description : '';
	    }
	  },
	  template: "\n\t\t<div class=\"crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-sms\">\n\t\t\t<div class=\"crm-entity-stream-section-icon crm-entity-stream-section-icon-sms\"></div>\n\t\t\t<div class=\"crm-entity-stream-section-content\">\n\t\t\t\t<div class=\"crm-entity-stream-content-event\">\n\t\t\t\t\t<div class=\"crm-entity-stream-content-header\">\n\t\t\t\t\t\t<a\n\t\t\t\t\t\t\t@click.prevent=\"viewActivity\"\n\t\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-event-title\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{title}}\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<span\n\t\t\t\t\t\t\tv-if=\"status\"\n\t\t\t\t\t\t\t:class=\"statusClass\"\n\t\t\t\t\t\t\t:title=\"statusErrorDescription\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{status.name}}\n\t\t\t\t\t\t</span>\n\t\t\t\t\t\t<span class=\"crm-entity-stream-content-event-time\">{{createdAt}}</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-entity-stream-content-detail\">\n\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms\">\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms-status\">\n\t\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_VIA'), " \n\t\t\t\t\t\t\t\t<strong>\n\t\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_BITRIX24'), "\n\t\t\t\t\t\t\t\t</strong>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-entity-stream-content-detail-sms-fragment\">\n\t\t\t\t\t\t\t\t<span>{{text}}</span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div\n\t\t\t\t\t\t\tv-if=\"communication\"\n\t\t\t\t\t\t\tclass=\"crm-entity-stream-content-detail-contact-info\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t{{BX.message('CRM_TIMELINE_SMS_TO')}}\n\t\t\t\t\t\t\t<a v-if=\"communication.SHOW_URL\" :href=\"communication.SHOW_URL\">\n\t\t\t\t\t\t\t\t{{communication.TITLE}}\n\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t\t{{communication.TITLE}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<span v-if=\"communication.VALUE\">{{communication.VALUE}}</span>\n\t\t\t\t\t\t\t<template v-if=\"provider\">\n\t\t\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_TIMELINE_NOTIFICATION_IN_MESSENGER'), " {{provider}}\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<author v-if=\"author\" :author=\"author\"></author>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\t\n\t")
	});

	exports.EncourageBuyProducts = component;
	exports.Notification = component$1;

}((this.BX.Crm.Timeline = this.BX.Crm.Timeline || {}),BX.Event,BX,BX,BX,BX,BX));
//# sourceMappingURL=timeline.bundle.js.map
