this.BX = this.BX || {};
(function (exports,rest_client,ui_notification,main_loader,popup,ui_buttons,ui_buttons_icons,ui_forms,ui_fonts_opensans,ui_pinner,currency,ui_dropdown,ui_common,ui_alerts,main_core_events,marketplace,applayout,main_popup,salescenter_manager,ui_vue_vuex,main_core,DeliverySelector,ui_vue) {
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
	        propertyValues: [],
	        deliveryExtraServicesValues: [],
	        expectedDelivery: null,
	        deliveryResponsibleId: null,
	        personTypeId: null,
	        deliveryId: null,
	        delivery: null,
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
	        refreshBasket: function refreshBasket(_ref, payload) {
	          var commit = _ref.commit,
	              dispatch = _ref.dispatch,
	              state = _ref.state;

	          if (this.updateTimer) {
	            clearTimeout(this.updateTimer);
	          }

	          this.updateTimer = setTimeout(function () {
	            var currentProcessingId = Math.random() * 100000;
	            commit('setProcessingId', currentProcessingId);
	            BX.ajax.runAction("salescenter.api.order.refreshBasket", {
	              data: {
	                basketItems: state.basket
	              }
	            }).then(function (result) {
	              if (currentProcessingId === state.processingId) {
	                var data = BX.prop.getObject(result, "data", {});
	                dispatch('processRefreshRequest', {
	                  total: BX.prop.getObject(data, "total", {
	                    sum: 0,
	                    discount: 0,
	                    result: 0,
	                    resultNumeric: 0
	                  }),
	                  basket: BX.prop.get(data, "items", [])
	                });

	                if (payload && payload.onsuccess) {
	                  payload.onsuccess();
	                }
	              }
	            }).catch(function (result) {
	              if (currentProcessingId === state.processingId) {
	                var data = BX.prop.getObject(result, "data", {});
	                dispatch('processRefreshRequest', {
	                  errors: BX.prop.get(result, "errors", []),
	                  basket: BX.prop.get(data, "items", [])
	                });

	                if (payload && payload.onfailure) {
	                  payload.onfailure();
	                }
	              }
	            });
	          }, 0);
	        },
	        processRefreshRequest: function processRefreshRequest(_ref2, payload) {
	          var commit = _ref2.commit,
	              dispatch = _ref2.dispatch;

	          if (BX.type.isArray(payload.basket)) {
	            payload.basket.forEach(function (basketItem) {
	              commit('updateBasketItem', {
	                index: basketItem.sort,
	                fields: basketItem
	              });
	            });
	            commit('setSelectedProducts');
	          }

	          if (BX.type.isObject(payload.total)) {
	            commit('setTotal', payload.total);
	          }

	          if (BX.type.isArray(payload.errors)) {
	            commit('setErrors', payload.errors);
	          } else {
	            commit('clearErrors');
	          }

	          commit('setProcessingId', null);
	        },
	        resetBasket: function resetBasket(_ref3) {
	          var commit = _ref3.commit;
	          commit('clearBasket');
	          commit('setTotal', {
	            sum: null,
	            discount: null,
	            result: null,
	            resultNumeric: null
	          });
	          commit('addBasketItem');
	        },
	        deleteBasketItem: function deleteBasketItem(_ref4, payload) {
	          var commit = _ref4.commit,
	              state = _ref4.state,
	              dispatch = _ref4.dispatch;
	          commit('deleteBasketItem', payload);

	          if (state.basket.length > 0) {
	            state.basket.forEach(function (item, i) {
	              commit('updateBasketItem', {
	                index: i,
	                fields: {
	                  sort: i
	                }
	              });
	            });
	          }

	          dispatch('refreshBasket');
	        },
	        removeItem: function removeItem(_ref5, payload) {
	          var commit = _ref5.commit,
	              state = _ref5.state,
	              dispatch = _ref5.dispatch;
	          commit('deleteBasketItem', payload);

	          if (state.basket.length === 0) {
	            commit('addBasketItem');
	          } else {
	            state.basket.forEach(function (item, i) {
	              commit('updateBasketItem', {
	                index: i,
	                fields: {
	                  sort: i
	                }
	              });
	            });
	          }

	          dispatch('refreshBasket');
	        },
	        changeBasketItem: function changeBasketItem(_ref6, payload) {
	          var commit = _ref6.commit,
	              dispatch = _ref6.dispatch;
	          commit('updateBasketItem', payload);
	          commit('setSelectedProducts');
	        },
	        setCurrency: function setCurrency(_ref7, payload) {
	          var commit = _ref7.commit;
	          var currency$$1 = payload || '';
	          commit('setCurrency', currency$$1);
	        },
	        setDeliveryId: function setDeliveryId(_ref8, payload) {
	          var commit = _ref8.commit;
	          commit('setDeliveryId', payload);
	        },
	        setDelivery: function setDelivery(_ref9, payload) {
	          var commit = _ref9.commit;
	          commit('setDelivery', payload);
	        },
	        setPropertyValues: function setPropertyValues(_ref10, payload) {
	          var commit = _ref10.commit;
	          commit('setPropertyValues', payload);
	        },
	        setDeliveryExtraServicesValues: function setDeliveryExtraServicesValues(_ref11, payload) {
	          var commit = _ref11.commit;
	          commit('setDeliveryExtraServicesValues', payload);
	        },
	        setExpectedDelivery: function setExpectedDelivery(_ref12, payload) {
	          var commit = _ref12.commit;
	          commit('setExpectedDelivery', payload);
	        },
	        setDeliveryResponsibleId: function setDeliveryResponsibleId(_ref13, payload) {
	          var commit = _ref13.commit;
	          commit('setDeliveryResponsibleId', payload);
	        },
	        setPersonTypeId: function setPersonTypeId(_ref14, payload) {
	          var commit = _ref14.commit;
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
	          return state.basket.filter(function (basketItem) {
	            return basketItem.module === 'catalog' && parseInt(basketItem.productId) > 0 || basketItem.module !== 'catalog' && BX.type.isNotEmptyString(basketItem.name) && parseFloat(basketItem.quantity) > 0;
	          }).length > 0;
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
	        addBasketItem: function addBasketItem(state, payload) {
	          var item = OrderCreationModel.getBasketItemState();
	          item.sort = state.basket.length;
	          state.basket.push(item);
	        },
	        updateBasketItem: function updateBasketItem(state, payload) {
	          if (typeof state.basket[payload.index] === 'undefined') {
	            ui_vue.Vue.set(state.basket, payload.index, OrderCreationModel.getBasketItemState());
	          }

	          state.basket[payload.index] = Object.assign(state.basket[payload.index], payload.fields);
	        },
	        clearBasket: function clearBasket(state) {
	          state.basket = [];
	        },
	        deleteBasketItem: function deleteBasketItem(state, payload) {
	          state.basket.splice(payload.index, 1);
	        },
	        setSelectedProducts: function setSelectedProducts(state) {
	          state.selectedProducts = state.basket.filter(function (basketItem) {
	            return basketItem.module === 'catalog' && parseInt(basketItem.productId) > 0;
	          }).map(function (filtered) {
	            return filtered.productId;
	          });
	        },
	        setTotal: function setTotal(state, payload) {
	          state.total = Object.assign(state.total, payload);
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
	        }
	      };
	    }
	  }], [{
	    key: "getBasketItemState",
	    value: function getBasketItemState() {
	      return {
	        productId: null,
	        code: null,
	        name: '',
	        sort: 0,
	        price: 0,
	        basePrice: 0,
	        quantity: 0,
	        showDiscount: '',
	        discount: 0,
	        discountInfos: [],
	        discountType: 'percent',
	        module: null,
	        measureCode: 0,
	        measureName: '',
	        measureRatio: 1,
	        taxRate: 0,
	        taxIncluded: 'N',
	        isCustomPrice: 'N',
	        isCreatedProduct: 'N',
	        encodedFields: null,
	        image: [],
	        errors: []
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

	function _createForOfIteratorHelper(o, allowArrayLike) { var it; if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = o[Symbol.iterator](); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it.return != null) it.return(); } finally { if (didErr) throw err; } } }; }

	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
	ui_vue.Vue.component(config.templateName, {
	  mixins: [MixinTemplatesType],
	  data: function data() {
	    return {
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
	  created: function created() {
	    var _this = this;

	    this.$root.$on("on-show-company-contacts", function (value) {
	      _this.showCompanyContacts(value);
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

	      var _iterator = _createForOfIteratorHelper(paymentsLimitStartNode.children),
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
	      BX.Salescenter.Manager.openSlider(this.$root.$app.options.urlSettingsCompanyContacts, {});
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
	  template: "\n\t\t<div class=\"salescenter-app-wrapper\" :style=\"{minHeight: getWrapperHeight}\">\n\t\t\t<div class=\"ui-sidepanel-sidebar salescenter-app-sidebar\" ref=\"sidebar\">\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\" v-if=\"this.$root.$app.context !== 'deal'\">\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isPagesOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"isPagesOpen = !isPagesOpen;\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAGES}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: pagesSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"!page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrdersList\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDERS}}</div>\n\t\t\t\t\t\t\t<span class=\"ui-sidepanel-counter\" ref=\"ordersCounter\" v-show=\"ordersCount > 0\">{{ordersCount}}</span>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrderAdd\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDER_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isCatalogAvailable\" @click=\"showCatalog\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_CATALOG}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isFormsOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"onFormsClick();\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_FORMS_ALL}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: formsSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t :class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event, true)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\" v-if=\"this.$root.$app.context === 'deal'\">\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPaymentBySms}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentBySmsForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_SEND_BY_SMS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm ui-sidepanel-menu-item-separate\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"showCompanyContacts(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"BX.Salescenter.Manager.openFeedbackPayOrderForm(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_OFFER_SCRIPT}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li class=\"ui-sidepanel-menu-item ui-sidepanel-menu-item-sm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" v-on:click=\"BX.Salescenter.Manager.openHowPayDealWorks(event)\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_HOW_WORKS}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-right-side\">\n\t\t\t\t<div class=\"salescenter-app-page-header\" v-show=\"isShowPreview && !isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-dropdown ui-btn-xs\" @click=\"showActionsPopup($event)\">{{localize.SALESCENTER_RIGHT_ACTIONS_BUTTON}}</div>\n\t\t\t\t\t<div class=\"salescenter-btn-delimiter salescenter-btn-action\"></div>\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-xs ui-btn-icon-edit\" @click=\"editPage\">{{localize.SALESCENTER_RIGHT_ACTION_EDIT}}</div>\n\t\t\t\t</div>\n\t\t\t\t<template v-if=\"isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-start-wrapper\">\n\t\t\t\t\t\t<div class=\"ui-title-1 ui-text-center ui-color-medium\" style=\"margin-bottom: 20px;\">{{localize.SALESCENTER_INFO_TEXT_TOP_2}}</div>\n\t\t\t\t\t\t<div class=\"ui-hr ui-mv-25\"></div>\n\t\t\t\t\t\t<template v-if=\"this.isOrderPublicUrlExists\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"openConnectedSite\">{{localize.SALESCENTER_INFO_PUBLIC}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"isOrderPageDeleted\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_ORDER_PAGE_DELETED}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"checkRecycle\">{{localize.SALESCENTER_CHECK_RECYCLE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_2}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"connect\">{{localize.SALESCENTER_INFO_CREATE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-link ui-btn-lg\" @click=\"BX.Salescenter.Manager.openHowPayDealWorks(event)\">{{localize.SALESCENTER_HOW}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else-if=\"isFrameError && isShowPreview\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-lost\">\n\t\t\t\t\t\t<div class=\"salescenter-app-lost-block ui-title-1 ui-text-center ui-color-medium\">{{localize.SALESCENTER_ERROR_TITLE}}</div>\n\t\t\t\t\t\t<div v-if=\"currentPage.isFrameDenied === true\" class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_RIGHT_FRAME_DENIED}}</div>\n\t\t\t\t\t\t<div v-else-if=\"currentPage.isActive !== true\" class=\"salescenter-app-lost-helper salescenter-app-not-active ui-color-medium\">{{localize.SALESCENTER_RIGHT_NOT_ACTIVE}}</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_ERROR_TEXT}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div v-show=\"isShowPreview && !isShowStartInfo && !isFrameError\" class=\"salescenter-app-page-content\">\n\t\t\t\t\t<template v-for=\"page in pages\">\n\t\t\t\t\t\t<iframe class=\"salescenter-app-demo\" v-show=\"currentPage && currentPage.id == page.id\" :src=\"getFrameSource(page)\" frameborder=\"0\" @error=\"onFrameError(page.id)\" @load=\"onFrameLoad(page.id)\" :key=\"page.id\"></iframe>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"salescenter-app-demo-overlay\" :class=\"{\n\t\t\t\t\t\t'salescenter-app-demo-overlay-loading': this.isShowLoader\n\t\t\t\t\t}\">\n\t\t\t\t\t\t<div v-show=\"isShowLoader\" ref=\"previewLoader\"></div>\n\t\t\t\t\t\t<div v-if=\"lastModified\" class=\"salescenter-app-demo-overlay-modification\">{{lastModified}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t    <template v-if=\"this.$root.$app.isPaymentsLimitReached\">\n\t\t\t        <div ref=\"paymentsLimit\" v-show=\"isShowPayment && !isShowStartInfo\"></div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t        <component v-if=\"isShowPayment && !isShowStartInfo\" :is=\"config.templateAddPaymentName\"></component>\n\t\t        </template>\n\t\t        <template v-if=\"isShowPaymentBySms && !isShowStartInfo\">\n\t\t\t        <component :is=\"config.templateAddPaymentBySms\" \n\t\t\t        @send=\"send\" \n\t\t\t        :isAllowedSubmitButton=\"isAllowedSubmitButton\"></component>\n\t\t        </template>\n\t\t\t</div>\n\t\t\t<div class=\"ui-button-panel-wrapper salescenter-button-panel\" ref=\"buttonsPanel\">\n\t\t\t\t<div class=\"ui-button-panel\">\n\t\t\t\t\t<button :class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\" v-if=\"editable\">{{localize.SALESCENTER_SEND}}</button>\n\t\t\t\t\t<button :class=\"{'ui-btn-disabled': !this.isAllowedSubmitButton}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\" v-else>{{localize.SALESCENTER_RESEND}}</button>\n\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"close\">{{localize.SALESCENTER_CANCEL}}</button>\n\t\t\t\t\t<button v-if=\"isShowPayment && !isShowStartInfo && !this.$root.$app.isPaymentsLimitReached\" class=\"ui-btn ui-btn-md ui-btn-link btn-send-crm\" @click=\"send($event, 'y')\">{{localize.SALESCENTER_SAVE_ORDER}}</button>\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"this.order.errors.length > 0\" ref=\"errorBlock\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

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

	      if (!sender instanceof BX.UI.Dropdown) {
	        return true;
	      }

	      if (item.id === undefined || parseInt(item.id) <= 0) {
	        return true;
	      }

	      var fields = {
	        name: item.title,
	        productId: item.id,
	        sort: this.basketItemIndex,
	        module: 'catalog',
	        isCustomPrice: 'N',
	        discount: 0,
	        quantity: this.basketItem.quantity > 0 ? this.basketItem.quantity : 1,
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
	      this.refreshBasket();
	    },
	    decrementQuantity: function decrementQuantity() {
	      if (this.basketItem.quantity > this.basketItem.measureRatio) {
	        var correctionFactor = this.calculateCorrectionFactor(this.basketItem.quantity, this.basketItem.measureRatio);
	        this.basketItem.quantity = (this.basketItem.quantity * correctionFactor - this.basketItem.measureRatio * correctionFactor) / correctionFactor;
	        this.changeData(this.basketItem);
	        this.refreshBasket();
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
	  template: "\n\t\t<div>\n\t\t\t<!--counters anr remover-->\n\t\t\t<div class=\"salescenter-app-counter\">{{basketItemIndex + 1}}</div>\n\t\t\t<div class=\"salescenter-app-remove\" @click=\"removeItem\" v-if=\"countItems > 1 && editable\"></div>\n\t\t\t<!--counters anr remover end-->\n\t\t\t\n\t\t\t<!--if isCreationMode-->\n\t\t\t<div class=\"salescenter-app-form-container\" v-if=\"!isCreationMode\">\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<!--col 1-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-prod\" style=\"flex:8\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-input\">\n\t\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_NAME}}</label>\n\t\t\t\t\t\t\t<div :class=\"productInputWrapperClass\">\n\t\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"resetSearchForm\" v-if=\"basketItem.name.length > 0 && editable\"/>\n\t\t\t\t\t\t\t\t<!--<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"removeItem\" v-if=\"countItems > 1 && editable\"/>-->\n\t\t\t\t\t\t\t\t<input\n\t\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\t\tref=\"searchProductLine\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox salescenter-app-product-search\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.name\"\n\t\t\t\t\t\t\t\t\tv-bx-search-product=\"{selector: productSelector, restrictedIds: restrictedSearchIds}\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\"\n\t\t\t\t\t\t\t\t\t:placeholder=\"localize.SALESCENTER_PRODUCT_NAME_PLACEHOLDER\"\n\t\t\t\t\t\t\t\t>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"hasNameError\">{{localize.SALESCENTER_PRODUCT_CHOOSE_PRODUCT}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-if=\"getBasketFileControl\" class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- loaded product -->\n\t\t\t\t\t\t\t<div v-html=\"getBasketFileControl\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- selected product -->\n\t\t\t\t\t\t\t<div v-html=\"basketItem.fileControlHtml\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 1 end-->\n\n\t\t\t\t\t<!--col 2-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}\n\t\t\t\t\t\t\t<span @click=\"showPopupMenu($event.target, measures, 'measures')\">{{ getMeasureName }}</span>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\" :class=\"isNotEnoughQuantity ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.quantity\"\n\t\t\t\t\t\t\t\t\t@change=\"changeQuantity\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter\" v-if=\"editable\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-up\" @click=\"incrementQuantity\"></div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-down\" @click=\"decrementQuantity\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"isNotEnoughQuantity\">{{localize.SALESCENTER_PRODUCT_IS_NOT_AVAILABLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 2 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 3-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\" :class=\"hasPriceError ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t:value=\"basketItem.price\"\n\t\t\t\t\t\t\t\t\t@change=\"changePrice\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 3 end-->\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<!--show discount link-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" v-if=\"editable || (!editable && showPrice)\">\n\t\t\t\t\t<div style=\"flex: 8;\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex: 2;\">\n\t\t\t\t\t\t<div v-if=\"showDiscount\" class=\"salescenter-app-collapse-link-pointer-event\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-if=\"showDiscount\">\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-hide\"  @click=\"toggleDiscount('N')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-show\"  @click=\"toggleDiscount('Y')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--show discount link end-->\n\t\t\t\t\n\t\t\t\t<!--dicount controller-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 7px\" v-if=\"showDiscount\">\n\t\t\t\t\t<div class=\"salescenter-app-form-collapse-container\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\">\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 8\"></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col  salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element\" v-html=\"basketItem.basePrice\" disabled=\"true\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t\t\tref=\"discountInput\" \n\t\t\t\t\t\t\t\t\t\t\t:value=\"basketItem.discount\"\n\t\t\t\t\t\t\t\t\t\t\t@change=\"changeDiscount\"\n\t\t\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link\" @click=\"showPopupMenu($event.target.firstChild, null, 'discount')\"><span v-html=\"basketItem.discountType === 'percent' ? '%' : currencySymbol\"></span></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 0;\" v-if=\"editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" v-for=\"discount in basketItem.discountInfos\"\">\n\t\t\t\t\t\t\t\t<span class=\"ui-text-4 ui-color-light\"> {{discount.name}}<a :href=\"discount.editPageUrl\" @click=\"openDiscountEditor(event, discount.editPageUrl)\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--dicount controller end-->\n\t\t\t\t\n\t\t\t</div>\n\t\t\t<!--endif isCreationMode-->\n\t\t\t\n\t\t\t<!--else isCreationMode-->\n\t\t\t<div class=\"salescenter-app-form-container\" v-else>\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<!--col 1-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-prod\" style=\"flex:8\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-input\">\n\t\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>\n\t\t\t\t\t\t\t<div :class=\"productInputWrapperClass\">\n\t\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"hideCreationForm\"> </button>\n\t\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" @change=\"changeName\" :value=\"basketItem.name\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-tag\">{{localize.SALESCENTER_PRODUCT_NEW_LABEL}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"hasNameError\">{{localize.SALESCENTER_PRODUCT_EMPTY_PRODUCT_NAME}}</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col-img\">\n\t\t\t\t\t\t\t<!-- new product -->\n\t\t\t\t\t\t\t<div v-html=\"basketItem.fileControlHtml\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 1 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 2-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text salescenter-app-ctl-label-text-link ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', ' ')}}\n\t\t\t\t\t\t\t<span @click=\"showPopupMenu($event.target, measures, 'measures')\">{{ getMeasureName }}</span>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" \n\t\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox\" \n\t\t\t\t\t\t\t\t\t:value=\"basketItem.quantity\" \n\t\t\t\t\t\t\t\t\t@input=\"changeQuantity\" \n\t\t\t\t\t\t\t\t\t@change=\"refreshBasket\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter\" v-if=\"editable\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-up\" @click=\"incrementQuantity\"></div>\n\t\t\t\t\t\t\t\t<div class=\"salescenter-app-input-counter-down\" @click=\"decrementQuantity\"></div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 2 end-->\n\t\t\t\t\t\n\t\t\t\t\t<!--col 3-->\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\">\n\t\t\t\t\t\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_PRICE_2}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\" :class=\"hasPriceError ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t:value=\"basketItem.price\"\n\t\t\t\t\t\t\t\t\t@change=\"changePrice\"\n\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<!--col 3 end-->\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<!--show discount link-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" v-if=\"editable || (!editable && showPrice)\">\n\t\t\t\t\t<div style=\"flex: 8;\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex: 2;\">\n\t\t\t\t\t\t<div v-if=\"showDiscount\" class=\"salescenter-app-collapse-link-pointer-event\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-if=\"showDiscount\">\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-hide\"  @click=\"toggleDiscount('N')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2\" v-else>\n\t\t\t\t\t\t<div class=\"salescenter-app-collapse-link-show\"  @click=\"toggleDiscount('Y')\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--show discount link end-->\n\t\t\t\t\n\t\t\t\t<!--dicount controller-->\n\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 7px\" v-if=\"showDiscount\">\n\t\t\t\t\t<div class=\"salescenter-app-form-collapse-container\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\">\t\t\t\t\t\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 8\"></div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col  salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<div class=\"ui-ctl-element ui-ctl-textbox salescenter-ui-ctl-element\" v-html=\"basketItem.basePrice\" disabled=\"true\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col salescenter-app-form-col-sm\" style=\"flex:2; overflow: hidden;\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown salescenter-app-col-currency\">\n\t\t\t\t\t\t\t\t\t<input \ttype=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"\n\t\t\t\t\t\t\t\t\t\t\tref=\"discountInput\"\n\t\t\t\t\t\t\t\t\t\t\t:value=\"basketItem.discount\"\n\t\t\t\t\t\t\t\t\t\t\t@change=\"changeDiscount\"\n\t\t\t\t\t\t\t\t\t\t\t:disabled=\"!editable\">\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-app-col-currency-symbol salescenter-app-col-currency-symbol-link\" @click=\"showPopupMenu($event.target.firstChild, null, 'discount')\"><span v-html=\"basketItem.discountType === 'percent' ? '%' : currencySymbol\"></span></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 0;\" v-if=\"editable\">\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" v-for=\"discount in basketItem.discountInfos\"\">\n\t\t\t\t\t\t\t\t<span class=\"ui-text-4 ui-color-light\"> {{discount.name}} \n\t\t\t\t\t\t\t\t<a :href=\"discount.editPageUrl\" @click=\"openDiscountEditor(event, discount.editPageUrl)\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}</a></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<!--dicount controller end-->\n\t\t\t</div>\n\t\t\t<!--endelse isCreationMode-->\n\t\t</div>\n\t"
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
	    }
	  },
	  created: function created() {
	    var _this = this;

	    this.currencySymbol = this.$root.$app.options.currencySymbol;
	    var defaultCurrency = this.$root.$app.options.currencyCode || '';
	    this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);

	    if (BX.type.isArray(this.$root.$app.options.basket) && this.$root.$app.options.basket.length > 0) {
	      this.$root.$app.options.basket.forEach(function (fields) {
	        _this.$store.dispatch('orderCreation/changeBasketItem', {
	          index: fields.sort,
	          fields: fields
	        });
	      });

	      if (typeof this.$root.$app.options.totals !== "undefined") {
	        this.$store.commit('orderCreation/setTotal', {
	          sum: this.$root.$app.options.totals.sum,
	          discount: this.$root.$app.options.totals.discount,
	          result: this.$root.$app.options.totals.result,
	          resultNumeric: parseFloat(this.$root.$app.options.totals.result)
	        });
	      }
	    } else {
	      this.addBasketItemForm();
	      this.$store.commit('orderCreation/setTotal', {
	        sum: 0,
	        discount: 0,
	        result: 0
	      });
	    }

	    if (this.$root.$app.options.showPaySystemSettingBanner) {
	      this.$store.commit('orderCreation/showBanner');
	    }
	  },
	  methods: {
	    refreshBasket: function refreshBasket() {
	      this.$store.dispatch('orderCreation/refreshBasket');
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
	      this.$store.commit('orderCreation/addBasketItem');
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
	  template: "\n\t<div class=\"salescenter-app-payment-side\">\n\t\t<div class=\"salescenter-app-page-content\">\n\t\t\t<div v-for=\"(item, index) in order.basket\" class=\"salescenter-app-form-wrapper\" :key=\"index\">\n\t\t\t\t<".concat(config.templateAddPaymentProductName, " \n\t\t\t\t\t:basketItem=\"item\" \n\t\t\t\t\t:basketItemIndex=\"index\"  \n\t\t\t\t\t:countItems=\"countItems\"\n\t\t\t\t\t:selectedProductIds=\"order.selectedProducts\"\n\t\t\t\t\t@changeBasketItem=\"changeBasketItem\" \n\t\t\t\t\t@removeItem=\"removeItem\" \n\t\t\t\t\t@refreshBasket=\"refreshBasket\" \n\t\t\t\t/>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-result-container\"  style=\"padding-right: 15px\">\n\t\t\t\t\n\t\t\t\t<div class=\"salescenter-app-result-grid-row salescenter-app-result-grid-total-sm\">\n\t\t\t\t\t<component :is=\"'basket-item-add-block'\" v-if=\"editable\"\n\t\t\t\t\t\tv-on:on-refresh-basket=\"refreshBasket\"\n\t\t\t\t\t\tv-on:on-add-basket-item=\"addBasketItemForm\"\n\t\t\t\t\t\tv-on:on-change-basket-item=\"changeBasketItem\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<template v-slot:product-add-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT}}</template>\n\t\t\t\t\t\t<template v-slot:product-add-from-catalog-title>{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT_FROM_CATALOG}}</template>\n\t\t\t\t\t</component>\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex: 1; display: flex; justify-content: flex-end; padding: 0;\">\n\t\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_TOTAL_SUM}}:</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-result-grid-item salescenter-app-result-grid-item-currency\" :class=\"total.result !== total.sum ? 'salescenter-app-text-line-through' : ''\" v-html=\"total.sum\"></div>\n\t\t\t\t\t\t<div class=\"salescenter-app-result-grid-item-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-result-grid-row salescenter-app-result-grid-benefit salescenter-app-result-grid-total-sm\">\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_TOTAL_DISCOUNT}}:</div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item salescenter-app-result-grid-item-currency\" v-html=\"total.discount\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item-currency-symbol salescenter-app-result-grid-item\" v-html=\"currencySymbol\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-result-grid-row salescenter-app-result-grid-total salescenter-app-result-grid-total-big\">\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_PRODUCTS_PRICE}}:</div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item salescenter-app-result-grid-item-currency\" v-html=\"total.result\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item-currency-symbol\" v-html=\"currencySymbol\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-banner\"  v-if=\"isShowedBanner\">\n\t\t\t\t<div class=\"salescenter-app-banner-inner\">\n\t\t\t\t\t<div class=\"salescenter-app-banner-title\">{{localize.SALESCENTER_BANNER_TITLE}}</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-content\">\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-text\">{{localize.SALESCENTER_BANNER_TEXT}}</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-btn-block\">\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect\" @click=\"openControlPanel\">{{localize.SALESCENTER_BANNER_BTN_CONFIGURE}}</button>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide\" @click=\"hideBanner\">{{localize.SALESCENTER_BANNER_BTN_HIDE}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-close\" @click=\"hideBanner\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n")
	});

	var Base = /*#__PURE__*/function () {
	  function Base(props) {
	    babelHelpers.classCallCheck(this, Base);
	    this.icon = this.getIcon();
	    this.type = this.getType();
	    this.content = main_core.Type.isString(props.content) && props.content.length > 0 ? props.content : '';
	    this.disabled = main_core.Type.isBoolean(props.disabled) ? props.disabled : false;
	  }

	  babelHelpers.createClass(Base, [{
	    key: "getType",
	    value: function getType() {
	      return '';
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return '';
	    }
	  }]);
	  return Base;
	}();

	var Cash = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Cash, _Base);

	  function Cash() {
	    babelHelpers.classCallCheck(this, Cash);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Cash).apply(this, arguments));
	  }

	  babelHelpers.createClass(Cash, [{
	    key: "getType",
	    value: function getType() {
	      return Cash.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'cash';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'cash';
	    }
	  }]);
	  return Cash;
	}(Base);

	var Check = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Check, _Base);

	  function Check(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Check);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Check).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(Check, [{
	    key: "getType",
	    value: function getType() {
	      return Check.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'check';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'check';
	    }
	  }]);
	  return Check;
	}(Base);

	var CheckSent = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(CheckSent, _Base);

	  function CheckSent(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, CheckSent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CheckSent).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(CheckSent, [{
	    key: "getType",
	    value: function getType() {
	      return CheckSent.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'check-sent';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'check-sent';
	    }
	  }]);
	  return CheckSent;
	}(Base);

	var Payment = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Payment, _Base);

	  function Payment(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Payment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Payment).call(this, props));
	    _this.sum = typeof props.sum != 'undefined' ? props.sum : '0.00'; //.toFixed(2)

	    _this.title = main_core.Type.isString(props.title) && props.title.length > 0 ? props.title : '';
	    _this.currency = main_core.Type.isString(props.currency) && props.currency.length > 0 ? props.currency : '';
	    return _this;
	  }

	  babelHelpers.createClass(Payment, [{
	    key: "getType",
	    value: function getType() {
	      return Payment.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'cash';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'payment';
	    }
	  }]);
	  return Payment;
	}(Base);

	var Sent = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Sent, _Base);

	  function Sent(props) {
	    var _this;

	    babelHelpers.classCallCheck(this, Sent);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Sent).call(this, props));
	    _this.url = main_core.Type.isString(props.url) && props.url.length > 0 ? props.url : '';
	    return _this;
	  }

	  babelHelpers.createClass(Sent, [{
	    key: "getType",
	    value: function getType() {
	      return Sent.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'sent';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'sent';
	    }
	  }]);
	  return Sent;
	}(Base);

	var Watch = /*#__PURE__*/function (_Base) {
	  babelHelpers.inherits(Watch, _Base);

	  function Watch() {
	    babelHelpers.classCallCheck(this, Watch);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Watch).apply(this, arguments));
	  }

	  babelHelpers.createClass(Watch, [{
	    key: "getType",
	    value: function getType() {
	      return Watch.type();
	    }
	  }, {
	    key: "getIcon",
	    value: function getIcon() {
	      return 'watch';
	    }
	  }], [{
	    key: "type",
	    value: function type() {
	      return 'watch';
	    }
	  }]);
	  return Watch;
	}(Base);

	var items = [Cash, Check, CheckSent, Payment, Sent, Watch];

	var Factory = /*#__PURE__*/function () {
	  function Factory() {
	    babelHelpers.classCallCheck(this, Factory);
	  }

	  babelHelpers.createClass(Factory, null, [{
	    key: "create",
	    value: function create(options) {
	      var item = items.filter(function (item) {
	        return options.type === item.type();
	      })[0];

	      if (!item) {
	        throw new Error("Unknown field type '".concat(options.type, "'"));
	      }

	      return new item(options);
	    }
	  }]);
	  return Factory;
	}();

	var SmsConfigureBlock = {
	  props: ['config'],
	  data: function data() {
	    return {
	      url: this.config.url
	    };
	  },
	  methods: {
	    openSlider: function openSlider() {
	      var _this = this;

	      salescenter_manager.Manager.openSlider(this.url).then(function () {
	        return _this.onConfigure();
	      });
	    },
	    onConfigure: function onConfigure() {
	      this.$emit('on-configure');
	    }
	  },
	  template: "\n\t\t<div class=\"ui-alert ui-alert-danger ui-alert-xs salescenter-app-payment-by-sms-item-container-alert\">\n\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t<slot name=\"sms-configure-text-alert\"></slot>\n\t\t\t</span>\n\t\t\t<span class=\"salescenter-app-payment-by-sms-item-container-alert-config\" @click=\"openSlider()\">\n\t\t\t\t<slot name=\"sms-configure-text-setting\"></slot>\n\t\t\t</span>\n\t\t</div>\n\t"
	};

	var SmsAlertBlock = {
	  template: "\n\t\t<div class=\"ui-alert ui-alert-danger ui-alert-icon-danger salescenter-app-payment-by-sms-item-container-alert\">\n\t\t\t<span class=\"ui-alert-message\">\n\t\t\t\t<slot name=\"sms-alert-text\"></slot>\n\t\t\t</span>\t\t\t\n\t\t</div>\n\t"
	};

	var SmsSenderListBlock = {
	  props: ['list', 'config'],
	  computed: {
	    getSenderCode: function getSenderCode() {
	      return this.config.sender.code;
	    },
	    getConfigUrl: function getConfigUrl() {
	      return this.config.url;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_SENDER_LIST_CONTENT_');
	    }
	  },
	  methods: {
	    openSlider: function openSlider() {
	      var _this = this;

	      salescenter_manager.Manager.openSlider(this.getConfigUrl).then(function () {
	        return _this.onConfigure();
	      });
	    },
	    onConfigure: function onConfigure() {
	      this.$emit('on-configure');
	    },
	    onSelectedSender: function onSelectedSender(value) {
	      this.$emit('on-selected', value);
	    },
	    render: function render(target, array) {
	      var _this2 = this;

	      var menuItems = [];

	      var setItem = function setItem(ev) {
	        target.innerHTML = ev.target.innerHTML;

	        _this2.setCode(ev.currentTarget.getAttribute('data-item-sender-value'));

	        _this2.popupMenu.close();
	      };

	      for (var index in array) {
	        if (!array.hasOwnProperty(index)) {
	          continue;
	        }

	        menuItems.push({
	          text: array[index].name,
	          dataset: {
	            'itemSenderValue': array[index].id
	          },
	          onclick: setItem
	        });
	      }

	      menuItems.push({
	        text: this.localize.SALESCENTER_SENDER_LIST_CONTENT_SETTINGS,
	        onclick: function onclick() {
	          _this2.openSlider();

	          _this2.popupMenu.close();
	        }
	      });
	      this.popupMenu = new main_popup.PopupMenuWindow({
	        bindElement: target,
	        items: menuItems
	      });
	      this.popupMenu.show();
	    },
	    getName: function getName() {
	      if (main_core.Type.isArray(this.list)) {
	        for (var index in this.list) {
	          if (!this.list.hasOwnProperty(index)) {
	            continue;
	          }

	          if (this.list[index].id === this.getSenderCode) {
	            return this.list[index].name;
	          }
	        }
	      }

	      return null;
	    },
	    setCode: function setCode(value) {
	      if (typeof value === 'string') {
	        this.onSelectedSender(value);
	        return;
	      }

	      this.onSelectedSender(value.target.value);
	    },
	    isShow: function isShow() {
	      return main_core.Type.isString(this.getName());
	    }
	  },
	  template: "\n\t\t<div v-if=\"isShow()\" class=\"salescenter-app-payment-by-sms-item-container-sms-content-info\">\n\t\t\t<slot name=\"sms-sender-list-text-send-from\"></slot>\n\t\t\t<span @click=\"render($event.target, list)\">{{getName()}}</span>\n\t\t</div>\n\t"
	};

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
	  methods: {
	    onChange: function onChange(payload) {
	      this.$store.dispatch('orderCreation/setDelivery', payload.deliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryId', payload.deliveryServiceId);
	      this.$store.dispatch('orderCreation/setPropertyValues', payload.relatedPropsValues);
	      this.$store.dispatch('orderCreation/setDeliveryExtraServicesValues', payload.relatedServicesValues);
	      this.$store.dispatch('orderCreation/setExpectedDelivery', payload.estimatedDeliveryPrice);
	      this.$store.dispatch('orderCreation/setDeliveryResponsibleId', payload.responsibleUser ? payload.responsibleUser.id : null);
	    },
	    onSettingsChanged: function onSettingsChanged() {
	      this.$emit('delivery-settings-changed');
	    }
	  },
	  created: function created() {
	    this.$store.dispatch('orderCreation/setPersonTypeId', this.config.personTypeId);
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
	  template: "\n\t\t<delivery-selector\n\t\t\t:editable=\"this.config.editable\"\n\t\t\t:init-is-calculated=\"config.isExistingItem\"\t\t\n\t\t\t:init-estimated-delivery-price=\"config.expectedDeliveryPrice\"\t\t\n\t\t\t:init-entered-delivery-price=\"config.deliveryPrice\"\n\t\t\t:init-delivery-service-id=\"config.deliveryServiceId\"\n\t\t\t:init-related-services-values=\"config.relatedServicesValues\"\n\t\t\t:init-related-props-values=\"config.relatedPropsValues\"\n\t\t\t:init-related-props-options=\"config.relatedPropsOptions\"\n\t\t\t:init-responsible-id=\"config.responsibleId\"\n\t\t\t:person-type-id=\"config.personTypeId\"\n\t\t\t:action=\"'salescenter.api.order.refreshDelivery'\"\n\t\t\t:action-data=\"actionData\"\n\t\t\t:external-sum=\"productsPrice\"\n\t\t\t:external-sum-label=\"sumTitle\"\n\t\t\t:currency=\"config.currency\"\n\t\t\t:currency-symbol=\"config.currencySymbol\"\n\t\t\t@change=\"onChange\"\n\t\t\t@settings-changed=\"onSettingsChanged\"\n\t\t></delivery-selector>\n\t"
	};

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t<div data-item-value=\"", "\" class=\"salescenter-app-payment-by-sms-select-popup-option\" style=\"background-color:", ";\" onclick=\"", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</div>\n\t\t\t\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"salescenter-app-payment-by-sms-select-popup\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var classModule = 'salescenter-app-payment-by-sms-item';
	ui_vue.Vue.component(config.templateAddPaymentBySmsItem, {
	  data: function data() {
	    return {
	      type: null,
	      title: null,
	      stage: null,
	      itemData: null,
	      set: null,
	      infoHover: false,
	      smsEditMessageMode: false,
	      smsSenders: null,
	      layout: {
	        paymentInfo: null
	      }
	    };
	  },
	  props: ['data', 'index'],
	  mixins: [MixinTemplatesType],
	  components: {
	    'sms-configure-block': SmsConfigureBlock,
	    'sms-alert-block': SmsAlertBlock,
	    'sms-sender-list-block': SmsSenderListBlock,
	    'delivery-selector': DeliverySelector$1
	  },
	  mounted: function mounted() {
	    this.layout.paymentInfo = this.paymentInfo;
	    this.loadData();
	  },
	  computed: babelHelpers.objectSpread({
	    getSmsSenderConfig: function getSmsSenderConfig() {
	      return {
	        url: this.$root.$app.urlSettingsSmsSenders,
	        phone: this.$root.$app.options.contactPhone,
	        sender: {
	          code: this.$root.$app.sendingMethodDesc.provider
	        }
	      };
	    },
	    deliverySelectorConfig: function deliverySelectorConfig() {
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
	    },
	    countItems: function countItems() {
	      return this.order.basket.length;
	    },
	    listeners: function listeners() {
	      return {
	        blur: this.adjustUpdateMessage,
	        keydown: this.pressKey
	      };
	    }
	  }, ui_vue_vuex.Vuex.mapState({
	    order: function order(state) {
	      return state.orderCreation;
	    }
	  })),
	  methods: {
	    pressKey: function pressKey(event) {
	      if (event.code === "Enter") {
	        this.adjustUpdateMessage();
	        this.smsEditMessageMode = false;
	      }
	    },
	    isHasLink: function isHasLink() {
	      return this.$root.$app.sendingMethodDesc.text.match(/#LINK#/);
	    },
	    getRawSmsMessage: function getRawSmsMessage() {
	      var text = this.$root.$app.sendingMethodDesc.text;
	      return main_core.Text.encode(text);
	    },
	    getSmsMessage: function getSmsMessage() {
	      var link = "<span class=\"".concat(classModule, "-container-sms-content-message-link\">").concat(this.$root.$app.orderPublicUrl, "</span><sapn class=\"").concat(classModule, "-container-sms-content-message-link-ref\">xxxxx</sapn>") + " ";
	      var text = this.$root.$app.sendingMethodDesc.text;
	      return main_core.Text.encode(text).replace(/#LINK#/g, link);
	    },
	    updateMessage: function updateMessage() {
	      this.$root.$app.sendingMethodDesc.text = this.$refs.smsMessageNode.innerText;
	    },
	    saveSmsTemplate: function saveSmsTemplate(smsText) {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "saveSmsTemplate", {
	        mode: "class",
	        data: {
	          smsTemplate: smsText
	        },
	        analyticsLabel: 'salescenterSmsTemplateChange'
	      });
	    },
	    adjustUpdateMessage: function adjustUpdateMessage(event) {
	      this.updateMessage();

	      if (!this.isHasLink()) {
	        this.showPopupHint(this.$refs.smsMessageNode, main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_ERROR'), 2000);
	      } else {
	        this.saveSmsTemplate(this.$root.$app.sendingMethodDesc.text);
	      }

	      if (event && event.type === 'blur') {
	        this.smsEditMessageMode = false;
	      }
	    },
	    loadData: function loadData() {
	      this.type = this.data.type;
	      this.title = this.data.title;
	      this.stage = this.data.stage;
	      this.set = this.data.set;
	      this.itemData = this.data.itemData || null;

	      if (this.isTemplateBeSendSms()) {
	        this.smsSenders = this.data.itemData.smsSenders;

	        if (this.smsSenders) {
	          this.setProviderDefault();
	        }
	      }
	    },
	    setProviderDefault: function setProviderDefault() {
	      this.$root.$app.sendingMethodDesc.provider = this.smsSenders.length !== 0 ? this.smsSenders[0].id : null;
	    },
	    isTemplateBeSendSms: function isTemplateBeSendSms() {
	      return this.type === 'BEE_SEND_SMS';
	    },
	    isTemplateSelectProduct: function isTemplateSelectProduct() {
	      return this.type === 'SELECT_PRODUCTS';
	    },
	    isTemplatePaySystem: function isTemplatePaySystem() {
	      return this.type === 'PAY_SYSTEM';
	    },
	    isTemplateCashBox: function isTemplateCashBox() {
	      return this.type === 'CASHBOX';
	    },
	    isTemplateAutomationBox: function isTemplateAutomationBox() {
	      return this.type === 'AUTOMATION';
	    },
	    isTemplateDeliveryBox: function isTemplateDeliveryBox() {
	      return this.type === 'DELIVERY';
	    },
	    isItemsSet: function isItemsSet() {
	      return this.set;
	    },
	    refreshBasket: function refreshBasket() {
	      this.$store.dispatch('orderCreation/refreshBasket');
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
	    showItem: function showItem(item) {
	      var _this = this;

	      var sliderOptions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	      if (item.hasOwnProperty('width')) {
	        sliderOptions.width = Number(item.width);
	      }

	      if (item.hasOwnProperty('type') && item.type === 'marketplace') {
	        this.showRestApplication(item, sliderOptions);
	      } else {
	        sliderOptions['width'] = 835;
	        salescenter_manager.Manager.openSlider(item.link, sliderOptions).then(function () {
	          return _this.getAjaxData();
	        });
	      }
	    },
	    showRestApplication: function showRestApplication(item) {
	      var sliderOptions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};

	      if (item.hasOwnProperty('installedApp') && item.installedApp) {
	        this.openRestAppLayout(item.id, item.code);
	      } else {
	        this.openMarketPlacePage(item.code, sliderOptions);
	      }
	    },
	    openMarketPlacePage: function openMarketPlacePage(code) {
	      var _this2 = this;

	      var sliderOptions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var applicationUrlTemplate = "/marketplace/detail/#app#/";
	      var url = applicationUrlTemplate.replace("#app#", encodeURIComponent(code));
	      salescenter_manager.Manager.openSlider(url, sliderOptions).then(function () {
	        return _this2.getAjaxData();
	      });
	    },
	    openRestAppLayout: function openRestAppLayout(applicationId, appCode) {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "getRestApp", {
	        data: {
	          code: appCode
	        }
	      }).then(function (response) {
	        var app = response.data;

	        if (app.TYPE === "A") {
	          this.showRestApplication(appCode);
	        } else {
	          BX.rest.AppLayout.openApplication(applicationId);
	        }
	      }.bind(this)).catch(function (response) {
	        this.restAppErrorPopup(" ", response.errors.pop().message);
	      }.bind(this));
	    },
	    restAppErrorPopup: function restAppErrorPopup(title, text) {
	      var popup$$1 = new main_popup.PopupWindow('rest-app-error-alert', null, {
	        closeIcon: true,
	        closeByEsc: true,
	        autoHide: false,
	        titleBar: title,
	        content: text,
	        zIndex: 16000,
	        overlay: {
	          color: 'gray',
	          opacity: 30
	        },
	        buttons: [new main_popup.PopupWindowButton({
	          'id': 'close',
	          'text': main_core.Loc.getMessage('SALESCENTER_JS_POPUP_CLOSE'),
	          'events': {
	            'click': function click() {
	              popup$$1.close();
	            }
	          }
	        })],
	        events: {
	          onPopupClose: function onPopupClose() {
	            this.destroy();
	          },
	          onPopupDestroy: function onPopupDestroy() {
	            popup$$1 = null;
	          }
	        }
	      });
	      popup$$1.show();
	    },
	    isAddItemClass: function isAddItemClass(item) {
	      if (!item.hasOwnProperty('type')) {
	        return true;
	      }

	      return !['paysystem', 'marketplace', 'delivery'].includes(item.type);
	    },
	    getAjaxData: function getAjaxData() {
	      BX.ajax.runComponentAction("bitrix:salescenter.app", "getAjaxData", {
	        mode: "class",
	        data: {
	          type: this.type
	        }
	      }).then(function (response) {
	        if (response.data) {
	          this.updateTemplate(response.data);
	        }
	      }.bind(this));
	    },
	    updateTemplate: function updateTemplate(data) {
	      this.data.itemData = data;
	      this.data.type = this.type;

	      if (typeof this.data.itemData.isSet !== "undefined") {
	        this.data.set = this.data.itemData.isSet;
	        this.data.stage = this.data.itemData.isSet ? 'complete' : 'disabled';
	      }

	      if (this.isTemplatePaySystem()) {
	        this.data.title = this.data.itemData.isSet ? main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE');
	      } else if (this.isTemplateCashBox()) {
	        this.data.title = this.data.itemData.isSet ? main_core.Loc.getMessage('SALESCENTER_CASHBOX_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_TITLE');
	      }

	      this.loadData();
	    },
	    showPaySystemSettingsHint: function showPaySystemSettingsHint() {
	      return !this.isItemsSet() && this.isTemplatePaySystem();
	    },
	    showCashBoxSettingsHint: function showCashBoxSettingsHint() {
	      return !this.isItemsSet() && this.isTemplateCashBox();
	    },
	    showPopupHint: function showPopupHint(target, message, timer) {
	      var _this3 = this;

	      if (this.popup) {
	        this.popup.destroy();
	        this.popup = null;
	      }

	      if (!target && !message) {
	        return;
	      }

	      this.popup = new main_popup.Popup(null, target, {
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this3.popup.destroy();

	            _this3.popup = null;
	          }
	        },
	        darkMode: true,
	        content: message,
	        offsetLeft: target.offsetWidth
	      });

	      if (timer) {
	        setTimeout(function () {
	          _this3.popup.destroy();

	          _this3.popup = null;
	        }, timer);
	      }

	      this.popup.show();
	    },
	    showSmsMessagePopupHint: function showSmsMessagePopupHint(target) {
	      this.showPopupHint(target, main_core.Loc.getMessage('SALESCENTER_SMS_MESSAGE_HINT'));
	    },
	    showSelectPopup: function showSelectPopup(target, options, type) {
	      var _this4 = this;

	      if (!target) {
	        return;
	      }

	      this.selectPopup = new main_popup.Popup(null, target, {
	        closeByEsc: true,
	        autoHide: true,
	        width: 250,
	        offsetTop: 5,
	        events: {
	          onPopupClose: function onPopupClose() {
	            _this4.selectPopup.destroy();
	          }
	        },
	        content: this.getSelectPopupContent(options, type)
	      });
	      this.selectPopup.show();
	    },
	    getSelectPopupContent: function getSelectPopupContent(options, type) {
	      var _this5 = this;

	      if (!this.selectPopupContent) {
	        this.selectPopupContent = main_core.Tag.render(_templateObject());

	        var onClickOptionHandler = function onClickOptionHandler(event) {
	          _this5.onChooseSelectOption(event, type);
	        };

	        for (var i = 0; i < options.length; i++) {
	          var option = main_core.Tag.render(_templateObject2(), options[i].id, options[i].color ? options[i].color : '', onClickOptionHandler.bind(this), options[i].name);

	          if (options[i].colorText === 'light') {
	            option.style.color = '#fff';
	          }

	          main_core.Dom.append(option, this.selectPopupContent);
	        }
	      }

	      return this.selectPopupContent;
	    },
	    onChooseSelectOption: function onChooseSelectOption(event, type) {
	      var currentOption = document.getElementById(type);
	      currentOption.textContent = event.currentTarget.textContent;
	      currentOption.style.color = event.currentTarget.style.color;
	      currentOption.nextElementSibling.style.borderColor = event.currentTarget.style.color;
	      currentOption.parentNode.style.background = event.currentTarget.style.backgroundColor;

	      if (type === 'stageOnOrderPaid') {
	        this.$root.$app.stageOnOrderPaid = event.currentTarget.getAttribute('data-item-value');
	      } else if (type === 'delivery') {
	        this.$root.$app.delivery = event.currentTarget.getAttribute('data-item-value');
	      }

	      this.selectPopup.destroy();
	    },
	    hidePopupHint: function hidePopupHint() {
	      if (this.popup) {
	        this.popup.destroy();
	      }
	    },
	    adjustSmsEditMessageMode: function adjustSmsEditMessageMode() {
	      this.smsEditMessageMode ? this.smsEditMessageMode = false : this.smsEditMessageMode = true;
	    },
	    isSmsEditMessageMode: function isSmsEditMessageMode() {
	      return this.smsEditMessageMode;
	    },
	    smsSenderConfigure: function smsSenderConfigure() {
	      var _this6 = this;

	      main_core.ajax.runComponentAction("bitrix:salescenter.app", "getSmsSenderList", {
	        mode: "class"
	      }).then(function (resolve) {
	        if (BX.type.isObject(resolve.data) && Object.values(resolve.data).length > 0) {
	          _this6.smsSenders = [];
	          Object.values(resolve.data).forEach(function (item) {
	            return _this6.smsSenders.push({
	              name: item.name,
	              id: item.id
	            });
	          });

	          _this6.setProviderDefault();
	        }
	      });
	    },
	    smsSenderSelected: function smsSenderSelected(value) {
	      this.$root.$app.sendingMethodDesc.provider = value;
	    },
	    hasContactPhone: function hasContactPhone() {
	      return !(this.getSmsSenderConfig.phone === '');
	    },
	    showCompanyContacts: function showCompanyContacts(e) {
	      this.$root.$emit("on-show-company-contacts", e);
	    }
	  },
	  template: "\n\t<div class=\"".concat(classModule, "\" \n\t\t:class=\"{ \n\t\t'salescenter-app-payment-by-sms-item-current': stage === 'current', \n\t\t'salescenter-app-payment-by-sms-item-disabled': stage === 'disabled',\n\t\t'salescenter-app-payment-by-sms-item-disabled-bg': !isItemsSet() && (isTemplatePaySystem() || isTemplateCashBox())\n\t\t}\">\n\t\t<div class=\"").concat(classModule, "-counter\">\n\t\t\t<div class=\"").concat(classModule, "-counter-rounder\"></div>\n\t\t\t<div class=\"").concat(classModule, "-counter-line\"></div>\n\t\t\t<div class=\"").concat(classModule, "-counter-number\">\n\t\t\t\t<div v-if=\"stage === 'complete'\" class=\"").concat(classModule, "-counter-number-checker\"></div>\n\t\t\t\t<div class=\"").concat(classModule, "-counter-number-text\">{{ index }}</div>\n\t\t\t</div>\n\t\t</div>\n\t\t<div class=\"").concat(classModule, "-title\">\n\t\t\t<div class=\"").concat(classModule, "-title-text\">{{ title }}</div>\n\t\t\t<div v-if=\"showPaySystemSettingsHint()\" v-on:click=\"BX.Salescenter.Manager.openHowToConfigPaySystem(event)\" class=\"").concat(classModule, "-title-info\">").concat(main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_SETTINGS_TITLE'), "</div>\n\t\t\t<div v-if=\"showCashBoxSettingsHint()\" v-on:click=\"BX.Salescenter.Manager.openHowToConfigCashBox(event)\" class=\"").concat(classModule, "-title-info\">").concat(main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_SETTINGS_TITLE'), "</div>\n\t\t\t<div v-if=\"isTemplateBeSendSms()\" v-on:click=\"showCompanyContacts(event)\" class=\"").concat(classModule, "-title-info\">").concat(main_core.Loc.getMessage('SALESCENTER_LEFT_PAYMENT_COMPANY_CONTACTS'), "</div>\n\t\t</div>\n\t\t<div class=\"").concat(classModule, "-container\" v-bind:class=\"{ 'salescenter-app-payment-by-sms-item-container-offtop': isTemplateBeSendSms() }\">\n\t\t\t<!--BEE_SEND_SMS-->\n\t\t\t<template v-if=\"isTemplateBeSendSms() && smsSenders.length === 0\">\n\t\t\t\t<component :is=\"'sms-configure-block'\"\n\t\t\t\t\t:config=\"getSmsSenderConfig\"\n\t\t\t\t\tv-on:on-configure=\"smsSenderConfigure\"\n\t\t\t\t>\n\t\t\t\t\t<template v-slot:sms-configure-text-alert>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_NOT_CONFIGURED'), "</template>\n\t\t\t\t\t<template v-slot:sms-configure-text-setting>").concat(main_core.Loc.getMessage('SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE'), "</template>\n\t\t\t\t</component>\n\t\t\t</template>\n\t\t\t<template v-if=\"isTemplateBeSendSms() && !hasContactPhone() && smsSenders.length !== 0\">\n\t\t\t\t<component :is=\"'sms-alert-block'\">\n\t\t\t\t\t<template v-slot:sms-alert-text>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_ALERT_PHONE_EMPTY'), "</template>\n\t\t\t\t</component>\n\t\t\t</template>\n\t\t\t<div v-if=\"isTemplateBeSendSms()\" class=\"").concat(classModule, "-container-sms\">\n\t\t\t\t<div class=\"").concat(classModule, "-container-sms-user\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-sms-user-avatar\" v-bind:style=\"[ itemData.manager.photo ? { 'background-image': 'url(' + itemData.manager.photo + ')'} : null ]\"></div>\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-sms-user-name\">{{itemData.manager.name}}</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"").concat(classModule, "-container-sms-content\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-sms-content-message\">\n\t\t\t\t\t\t<div \tv-if=\"smsEditMessageMode\"\n\t\t\t\t\t\t\t\tcontenteditable=\"true\" \n\t\t\t\t\t\t\t\tclass=\"").concat(classModule, "-container-sms-content-message-text ").concat(classModule, "-container-sms-content-message-text-edit\"\n\t\t\t\t\t\t\t\tv-on=\"listeners\"\n\t\t\t\t\t\t\t\tv-html=\"getRawSmsMessage()\"\n\t\t\t\t\t\t\t\tref=\"smsMessageNode\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div v-else contenteditable=\"false\" class=\"").concat(classModule, "-container-sms-content-message-text\" v-html=\"getSmsMessage()\" v-on:mouseenter=\"showSmsMessagePopupHint($event.target)\" v-on:mouseleave=\"hidePopupHint()\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-sms-content-edit\" v-bind:class=\"{ 'salescenter-app-payment-by-sms-item-container-sms-content-save': isSmsEditMessageMode() }\" @click=\"adjustSmsEditMessageMode\"></div>\n\t\t\t\t\t</div>\t\t\t\t\n\t\t\t\t\t\n\t\t\t\t\t<component :is=\"'sms-sender-list-block'\"\n\t\t\t\t\t\t:list=\"smsSenders\"\n\t\t\t\t\t\t:config=\"getSmsSenderConfig\"\n\t\t\t\t\t\tv-on:on-configure=\"smsSenderConfigure\"\n\t\t\t\t\t\tv-on:on-selected=\"smsSenderSelected\" \n\t\t\t\t\t>\n\t\t\t\t\t\t<template v-slot:sms-sender-list-text-send-from>").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER'), "</template>\n\t\t\t\t\t</component>\n\t\t\t\t\t\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<!--SELECT_PRODUCTS-->\n\t\t\t<div v-if=\"isTemplateSelectProduct()\" class=\"").concat(classModule, "-container-payment\">\n\t\t\t\t<").concat(config.templateAddPaymentName, "/>\n\t\t\t</div>\n\t\t\t\n\t\t\t<!--PAY_SYSTEM-->\n\t\t\t<div v-if=\"isTemplatePaySystem()\" class=\"").concat(classModule, "-container-payment\">\n\t\t\t\t<template v-if=\"isItemsSet()\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-inline\">\n\t\t\t\t\t\t<div v-for=\"item in itemData.items\" v-on:click=\"showItem(item, {width: 1000})\" class=\"").concat(classModule, "-container-payment-item-text\">{{item.name}}</div>\n\t\t\t\t\t\t<br><div v-on:click=\"showItem(itemData.paysystemPanel)\" class=\"").concat(classModule, "-container-payment-item-text-add\">{{itemData.paysystemPanel.name}}</div>\n\t\t\t\t\t\t<div v-if=\"itemData.paysystemForm\" v-on:click=\"showItem(itemData.paysystemForm)\" class=\"").concat(classModule, "-container-payment-item-text-add\">{{itemData.paysystemForm.name}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div v-for=\"item in itemData.items\" v-on:click=\"showItem(item)\" v-bind:class=\"[isAddItemClass(item) ? '").concat(classModule, "-container-payment-item-added' : '', '").concat(classModule, "-container-payment-item']\">\n\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-contet\">\n\t\t\t\t\t\t\t<template v-if=\"item.img\">\n\t\t\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-info\" v-on:mouseenter=\"showPopupHint($event.target, item.info)\" v-on:mouseleave=\"hidePopupHint()\"></div>\n\t\t\t\t\t\t\t\t<img class=\"").concat(classModule, "-container-payment-item-img\" :src=\"item.img\">\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<span v-else class=\"").concat(classModule, "-container-payment-item-added-text\">{{ item.name }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t\t\n\t\t\t<!--CASHBOX-->\n\t\t\t<div v-if=\"isTemplateCashBox()\" class=\"").concat(classModule, "-container-payment\">\n\t\t\t\t<template v-if=\"isItemsSet()\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-inline\">\n\t\t\t\t\t\t<div v-for=\"item in itemData.items\" v-on:click=\"showItem(item, {width: 1000})\" class=\"").concat(classModule, "-container-payment-item-text\">{{item.name}}</div>\n\t\t\t\t\t\t<br><div v-on:click=\"showItem(itemData.cashboxPanel)\" class=\"").concat(classModule, "-container-payment-item-text-add\">{{itemData.cashboxPanel.name}}</div>\n\t\t\t\t\t\t<div v-if=\"itemData.cashboxForm\" v-on:click=\"showItem(itemData.cashboxForm)\" class=\"").concat(classModule, "-container-payment-item-text-add\">{{itemData.cashboxForm.name}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div v-for=\"item in itemData.items\" v-on:click=\"showItem(item)\" v-bind:class=\"[item.type !== 'cashbox' ? '").concat(classModule, "-container-payment-item-added' : '', '").concat(classModule, "-container-payment-item']\">\n\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-contet\">\n\t\t\t\t\t\t\t <template v-if=\"item.type === 'cashbox'\" >\n\t\t\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-info\" v-on:mouseenter=\"showPopupHint($event.target, item.info)\" v-on:mouseleave=\"hidePopupHint()\"></div>\n\t\t\t\t\t\t\t\t<img class=\"").concat(classModule, "-container-payment-item-img\" :src=\"item.img\">\n\t\t\t\t\t\t\t\t<div v-if=\"item.showTitle\" class=\"").concat(classModule, "-container-payment-item-title-text\">{{ item.name }}</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<span v-else class=\"").concat(classModule, "-container-payment-item-added-text\">{{ item.name }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t\t\n\t\t\t<!--AUTOMATION-->\n\t\t\t<div v-if=\"isTemplateAutomationBox()\" class=\"").concat(classModule, "-container-payment\">\n\t\t\t\t<div class=\"").concat(classModule, "-container-select\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-select-text\">").concat(main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TEXT'), "</div>\n\t\t\t\t\t<template v-for=\"item in itemData\">\n\t\t\t\t\t\t<div v-if=\"item.selected\" class=\"").concat(classModule, "-container-select-inner\" v-bind:style=\"{background:item.color}\" v-on:click=\"showSelectPopup($event.currentTarget, itemData, 'stageOnOrderPaid')\">\n\t\t\t\t\t\t\t<div  class=\"").concat(classModule, "-container-select-item\" id=\"stageOnOrderPaid\">\n\t\t\t\t\t\t\t\t{{item.name}}\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<span class=\"").concat(classModule, "-container-select-arrow\"></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</template>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<!--DELIVERY-->\n\t\t\t<div v-if=\"isTemplateDeliveryBox()\" class=\"").concat(classModule, "-container-payment\">\n\t\t\t\t<template v-if=\"itemData.isInstalled\">\n\t\t\t\t\t<div class=\"").concat(classModule, "-container-select\">\n\t\t\t\t\t\t<delivery-selector @delivery-settings-changed=\"getAjaxData\" :config=\"deliverySelectorConfig\"></delivery-selector>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t\t\t<div v-for=\"item in itemData.items\" v-on:click=\"showItem(item)\" v-bind:class=\"[isAddItemClass(item) ? '").concat(classModule, "-container-payment-item-added' : '', '").concat(classModule, "-container-payment-item']\">\n\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-contet\">\n\t\t\t\t\t\t\t<template v-if=\"item.img\">\n\t\t\t\t\t\t\t\t<div class=\"").concat(classModule, "-container-payment-item-info\" v-on:mouseenter=\"showPopupHint($event.target, item.info)\" v-on:mouseleave=\"hidePopupHint()\"></div>\n\t\t\t\t\t\t\t\t<div :style=\"{backgroundImage:'url('+encodeURI(item.img)+')'}\" class=\"").concat(classModule, "-container-payment-item-img-del\" :class=\"{ '").concat(classModule, "-container-payment-item-img-del-title' : item.showTitle}\"></div>\n\t\t\t\t\t\t\t\t<div v-if=\"item.showTitle\" class=\"").concat(classModule, "-container-payment-item-title-text\">{{ item.name }}</div>\n\t\t\t\t\t\t\t</template>\n\t\t\t\t\t\t\t<span v-else class=\"").concat(classModule, "-container-payment-item-added-text\">{{ item.name }}</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n\t")
	});

	var TimeLineItemContentBlock = {
	  props: ['item'],
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_TIMELINE_ITEM_CONTENT_');
	    }
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-content\">\n\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text\">\n\t\t\t\t<slot name=\"timeline-content-text\"></slot>\t\t\t\t\n\t\t\t\t<a :href=\"item.url\" v-if=\"item.url\" target=\"_blank\">\n\t\t\t\t\t{{localize.SALESCENTER_TIMELINE_ITEM_CONTENT_VIEW}}\n\t\t\t\t</a>\n\t\t\t</span>\n\t\t</div>\n\t"
	};

	var TimeLineItemBlock = {
	  props: ['item'],
	  components: {
	    'timeline-item-content-block': TimeLineItemContentBlock
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-item\"\n\t\t\t:class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled\n\t\t\t}\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-icon \" \n\t\t\t\t\t:class=\"'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon\"></div>\n\t\t\t</div>\n\t\t\t<component :is=\"'timeline-item-content-block'\" \n\t\t\t\t:item=\"item\">\n\t\t\t\t<template v-slot:timeline-content-text>{{item.content}}</template>\n\t\t\t</component>\t\n\t\t</div>\n\t"
	};

	var TimeLineItemPaymentBlock = {
	  props: ['item'],
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline-item salescenter-app-payment-by-sms-timeline-item-payment\"\n\t\t\t:class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-timeline-item-disabled' : item.disabled\n\t\t\t}\"\n\t\t>\n\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-icon \" \n\t\t\t\t\t:class=\"'salescenter-app-payment-by-sms-item-counter-icon-'+item.icon\"></div>\n\t\t\t</div>\n\t\t\t\n\t\t\t<div class=\"salescenter-app-payment-by-sms-timeline-content\">\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-price\">\n\t\t\t\t\t<span v-html=\"item.sum\"></span>\n\t\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-price-cur\" v-html=\"item.currency\"></span>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text-strong\">\n\t\t\t\t\t{{item.title}}\n\t\t\t\t</span>\n\t\t\t\t<span class=\"salescenter-app-payment-by-sms-timeline-content-text\">\n\t\t\t\t\t{{item.content}}\n\t\t\t\t</span>\n\t\t\t</div>\n\t\t</div>\n\t"
	};

	var TimeLineListBlock = {
	  props: ['items'],
	  components: {
	    'timeline-item-block': TimeLineItemBlock,
	    'timeline-item-payment-block': TimeLineItemPaymentBlock
	  },
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_TIMELINE_');
	    }
	  },
	  data: function data() {
	    return {};
	  },
	  template: "\n\t\t<div class=\"salescenter-app-payment-by-sms-timeline\">\n\t\t\t<template v-for=\"(item) in items\" >\n\t\t\t\t<component v-if=\"item.type == 'payment'\" :is=\"'timeline-item-payment-block'\" :item=\"item\"/>\n\t\t\t\t<component v-else :is=\"'timeline-item-block'\" :item=\"item\"/>\n\t\t\t</template>\n\t\t</div>\n\t"
	};

	ui_vue.Vue.component(config.templateAddPaymentBySms, {
	  /**
	   * @emits 'send' {e: object}
	   */
	  props: ['isAllowedSubmitButton'],
	  mixins: [MixinTemplatesType],
	  components: {
	    'timeline-list-block': TimeLineListBlock
	  },
	  data: function data() {
	    var steps = [{
	      sort: 100,
	      type: 'BEE_SEND_SMS',
	      title: this.$root.$app.options.contactBlock.title,
	      stage: 'complete',
	      itemData: this.$root.$app.options.contactBlock
	    }, {
	      sort: 200,
	      type: 'SELECT_PRODUCTS',
	      title: main_core.Loc.getMessage('SALESCENTER_PRODUCT_BLOCK_TITLE'),
	      stage: this.$root.$app.options.basket && this.$root.$app.options.basket.length > 0 ? 'complete' : 'current'
	    }, {
	      sort: 300,
	      type: 'PAY_SYSTEM',
	      title: this.$root.$app.options.paySystemList.isSet ? main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_PAYSYSTEM_BLOCK_TITLE'),
	      stage: this.$root.$app.options.paySystemList.isSet ? 'complete' : 'disabled',
	      set: this.$root.$app.options.paySystemList.isSet,
	      itemData: this.$root.$app.options.paySystemList
	    }];

	    if (this.$root.$app.options.isAutomationAvailable) {
	      steps.push({
	        sort: 500,
	        type: 'AUTOMATION',
	        title: main_core.Loc.getMessage('SALESCENTER_AUTOMATION_BLOCK_TITLE'),
	        stage: 'complete',
	        itemData: this.$root.$app.options.dealStageList
	      });
	    }

	    if (this.$root.$app.options.cashboxList.hasOwnProperty('items')) {
	      steps.push({
	        sort: 400,
	        type: 'CASHBOX',
	        title: this.$root.$app.options.cashboxList.isSet ? main_core.Loc.getMessage('SALESCENTER_CASHBOX_SET_BLOCK_TITLE') : main_core.Loc.getMessage('SALESCENTER_CASHBOX_BLOCK_TITLE'),
	        stage: this.$root.$app.options.cashboxList.isSet ? 'complete' : 'disabled',
	        set: this.$root.$app.options.cashboxList.isSet,
	        itemData: this.$root.$app.options.cashboxList
	      });
	    }

	    if (this.$root.$app.options.deliveryList.hasOwnProperty('items')) {
	      steps.push({
	        sort: 600,
	        type: 'DELIVERY',
	        title: main_core.Loc.getMessage('SALESCENTER_DELIVERY_BLOCK_TITLE'),
	        stage: this.$root.$app.options.deliveryList.isInstalled ? 'complete' : 'disabled',
	        set: this.$root.$app.options.deliveryList.isInstalled,
	        itemData: this.$root.$app.options.deliveryList
	      });
	    }

	    steps.sort(function (a, b) {
	      return a.sort - b.sort;
	    });
	    return {
	      title: main_core.Loc.getMessage('SALESCENTER_LEFT_CREATE_LINK_AND_SEND'),
	      data: this.$root.$app,
	      steps: steps,
	      timeline: {
	        items: [{
	          sum: '',
	          url: '',
	          type: '',
	          title: '',
	          content: '',
	          currency: '',
	          disabled: ''
	        }]
	      }
	    };
	  },
	  computed: {
	    config: function config$$1() {
	      return config;
	    },
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_TIMELINE_');
	    }
	  },
	  created: function created() {
	    this.timelineItemsInit();
	  },
	  methods: {
	    timelineItemsInit: function timelineItemsInit() {
	      var _this = this;

	      if (BX.type.isObject(this.$root.$app.options.timeline) && Object.values(this.$root.$app.options.timeline).length > 0) {
	        this.timeline.items = [];
	        Object.values(this.$root.$app.options.timeline).forEach(function (options) {
	          return _this.timeline.items.push(Factory.create(options));
	        });
	      }
	    },
	    send: function send(e) {
	      this.$emit('send', e);
	    },
	    initTileGrid: function initTileGrid() {
	      console.log("initTileGrid");
	    }
	  },
	  template: "\n\t<div class=\"salescenter-app-payment-by-sms\">\n\t\t<div class=\"salescenter-app-payment-by-sms-title\">{{ title }}</div>\n\t\t<component \n\t\t\t:is=\"config.templateAddPaymentBySmsItem\"\n\t\t\tv-for=\"(step, index) in steps\"\n\t\t\t:index=\"index + 1\"\n\t\t\t:data=\"step\"\n\t\t\t:complete=\"step.complete\"\n\t\t\t:current=\"step.current\"></component>\n\t\t\t<div :class=\"{\n\t\t\t\t'salescenter-app-payment-by-sms-item-disabled': !this.isAllowedSubmitButton\n\t\t\t\t}\" class=\"salescenter-app-payment-by-sms-item salescenter-app-payment-by-sms-item-send salescenter-app-payment-by-sms-item\">\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-rounder\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-line\"></div>\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-counter-number\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container\">\n\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment\">\n\t\t\t\t\t\t<div class=\"salescenter-app-payment-by-sms-item-container-payment-inline\">\n\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\" v-on:click=\"send($event)\" v-if=\"editable\">".concat(main_core.Loc.getMessage('SALESCENTER_SEND'), "</div>\n\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-lg ui-btn-success ui-btn-round\" v-on:click=\"send($event)\" v-else >").concat(main_core.Loc.getMessage('SALESCENTER_RESEND'), "</div>\n\t\t\t\t\t\t\t<div v-on:click=\"BX.Salescenter.Manager.openWhatClientSee(event)\" class=\"salescenter-app-add-item-link\">").concat(main_core.Loc.getMessage('SALESCENTER_SEND_ORDER_BY_SMS_SENDER_TEMPLATE_WHAT_DOES_CLIENT_SEE'), "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t<component :is=\"'timeline-list-block'\" :items=\"timeline.items\"/>\n\t</div>\n\t")
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
	    this.stageOnOrderPaid = '';
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
	      var total = this.store.getters['orderCreation/getTotal'];
	      var propertyValues = this.store.getters['orderCreation/getPropertyValues'];
	      var deliveryExtraServicesValues = this.store.getters['orderCreation/getDeliveryExtraServicesValues'];
	      var expectedDelivery = this.store.getters['orderCreation/getExpectedDelivery'];
	      var deliveryResponsibleId = this.store.getters['orderCreation/getDeliveryResponsibleId'];
	      var personTypeId = this.store.getters['orderCreation/getPersonTypeId'];

	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }

	      this.startProgress(buttonEvent);
	      this.store.dispatch('orderCreation/refreshBasket', {
	        onsuccess: function onsuccess() {
	          BX.ajax.runAction('salescenter.order.createPayment', {
	            data: {
	              basketItems: basket,
	              options: {
	                dialogId: _this7.dialogId,
	                sendingMethod: _this7.sendingMethod,
	                sendingMethodDesc: _this7.sendingMethodDesc,
	                sessionId: _this7.sessionId,
	                lineId: _this7.lineId,
	                ownerTypeId: _this7.ownerTypeId,
	                ownerId: _this7.ownerId,
	                stageOnOrderPaid: _this7.stageOnOrderPaid,
	                skipPublicMessage: skipPublicMessage,
	                deliveryId: deliveryId,
	                deliveryPrice: delivery,
	                expectedDeliveryPrice: expectedDelivery,
	                deliveryResponsibleId: deliveryResponsibleId,
	                personTypeId: personTypeId,
	                propertyValues: propertyValues,
	                deliveryExtraServicesValues: deliveryExtraServicesValues,
	                connector: _this7.connector
	              }
	            },
	            analyticsLabel: _this7.context === 'deal' ? 'salescenterCreatePaymentSms' : 'salescenterCreatePayment',
	            getParameters: {
	              dialogId: _this7.dialogId,
	              context: _this7.context,
	              connector: _this7.connector,
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
	        },
	        onfailure: function onfailure() {
	          _this7.stopProgress(buttonEvent);
	        }
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

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX,BX,BX,BX.UI,BX,BX,BX,BX,BX,BX,BX,BX.UI,BX.Event,BX,BX,BX.Main,BX.Salescenter,BX,BX,BX.Salescenter,BX));
//# sourceMappingURL=app.bundle.js.map
