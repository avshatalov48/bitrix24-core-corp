this.BX = this.BX || {};
(function (exports,rest_client,ui_notification,main_loader,main_core,popup,ui_buttons,ui_buttons_icons,ui_forms,ui_fonts_opensans,ui_pinner,ui_vue_vuex,salescenter_manager,currency,ui_vue) {
	'use strict';

	var ApplicationModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
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

	var OrderCreationModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
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
	        errors: [],
	        total: {
	          sum: null,
	          discount: null,
	          result: null
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
	          payload.timeout = payload.timeout || 300;

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
	                  total: BX.prop.getObject(data, "total", {}),
	                  basket: BX.prop.get(data, "items", [])
	                });

	                if (payload.onsuccess) {
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

	                if (payload.onfailure) {
	                  payload.onfailure();
	                }
	              }
	            });
	          }, payload.timeout);
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
	            dispatch('recalculate');
	          } else {
	            commit('clearErrors');
	          }

	          commit('setProcessingId', null);
	        },
	        recalculate: function recalculate(_ref3) {
	          var commit = _ref3.commit,
	              state = _ref3.state;
	          commit('setProcessingId', null);
	          var productCost = 0;
	          var totalDiscount = 0;
	          var resultSum = 0;
	          state.basket.forEach(function (item, i) {
	            if (item.name === '') {
	              return;
	            }

	            var currentPrice = item.basePrice;

	            if (item.discount > 0) {
	              var discountValue = item.discount;

	              if (item.discountType === 'percent') {
	                discountValue = item.basePrice * item.discount / 100;
	              }

	              currentPrice -= discountValue;
	              totalDiscount += discountValue * item.quantity;
	            }

	            currentPrice = currentPrice > 0 ? currentPrice : 0;
	            resultSum += currentPrice * item.quantity;
	            productCost += item.catalogPrice * item.quantity;
	            commit('updateBasketItem', {
	              index: i,
	              fields: {
	                formattedPrice: BX.Currency.currencyFormat(currentPrice, state.currency, true),
	                formattedCatalogPrice: BX.Currency.currencyFormat(item.catalogPrice, state.currency, true)
	              }
	            });
	          });
	          totalDiscount = Math.min(totalDiscount, productCost);
	          commit('setTotal', {
	            sum: BX.Currency.currencyFormat(productCost, state.currency, true),
	            discount: BX.Currency.currencyFormat(totalDiscount, state.currency, true),
	            result: BX.Currency.currencyFormat(resultSum, state.currency, true)
	          });
	        },
	        resetBasket: function resetBasket(_ref4) {
	          var commit = _ref4.commit;
	          commit('clearBasket');
	          commit('setTotal', {
	            sum: null,
	            discount: null,
	            result: null
	          });
	          commit('addBasketItem');
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

	          dispatch('recalculate');
	        },
	        changeBasketItem: function changeBasketItem(_ref6, payload) {
	          var commit = _ref6.commit,
	              dispatch = _ref6.dispatch;
	          commit('updateBasketItem', payload);
	          commit('setSelectedProducts');
	          dispatch('recalculate');
	        },
	        setCurrency: function setCurrency(_ref7, payload) {
	          var commit = _ref7.commit;
	          var currency$$1 = payload || '';
	          commit('setCurrency', currency$$1);
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
	        clearErrors: function clearErrors(state) {
	          state.errors = [];
	        },
	        setProcessingId: function setProcessingId(state, payload) {
	          state.processingId = payload;
	        },
	        setCurrency: function setCurrency(state, payload) {
	          state.currency = payload;
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
	        basePrice: 0,
	        catalogPrice: 0,
	        quantity: 0,
	        showDiscount: '',
	        discount: 0,
	        discountInfos: [],
	        discountType: 'percent',
	        module: null,
	        formattedPrice: 0,
	        formattedCatalogPrice: null,
	        measureCode: 0,
	        measureName: '',
	        isCustomPrice: 'N',
	        isCreatedProduct: 'N',
	        encodedFields: null,
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
	  moduleId: 'salescenter'
	});

	ui_vue.Vue.component(config.templateName, {
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
	      editedPageId: null,
	      isOrderPublicUrlAvailable: null
	    };
	  },
	  created: function created() {},
	  updated: function updated() {
	    this.renderErrors();
	  },
	  mounted: function mounted() {
	    var _this = this;

	    this.createPinner();
	    this.createLoader();
	    this.$root.$app.fillPages().then(function () {
	      _this.refreshOrdersCount();

	      _this.openFirstPage();
	    });
	    this.isOrderPublicUrlAvailable = this.$root.$app.isOrderPublicUrlAvailable;
	    this.isOrderPublicUrlExists = this.$root.$app.isOrderPublicUrlExists;

	    if (this.$root.$app.isPaymentsLimitReached) {
	      var paymentsLimitStartNode = this.$root.$nodes.paymentsLimit;
	      var paymentsLimitNode = this.$refs['paymentsLimit'];
	      var _iteratorNormalCompletion = true;
	      var _didIteratorError = false;
	      var _iteratorError = undefined;

	      try {
	        for (var _iterator = paymentsLimitStartNode.children[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	          var node = _step.value;
	          paymentsLimitNode.appendChild(node);
	        }
	      } catch (err) {
	        _didIteratorError = true;
	        _iteratorError = err;
	      } finally {
	        try {
	          if (!_iteratorNormalCompletion && _iterator.return != null) {
	            _iterator.return();
	          }
	        } finally {
	          if (_didIteratorError) {
	            throw _iteratorError;
	          }
	        }
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
	      } else {
	        this.$refs['leftSide'].remove();
	      }

	      if (sidepanel && leftPanel) {
	        leftPanel.appendChild(sidepanel);
	        BX.show(sidepanel);
	        var nav = this.$refs['sidepanelNav'];

	        if (nav) {
	          leftPanel.appendChild(nav);
	          BX.show(nav);
	        }
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
	    showAddPageActionPopup: function showAddPageActionPopup(_ref2) {
	      var target = _ref2.target;
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
	    showPaymentForm: function showPaymentForm() {
	      this.isShowPayment = true;
	      this.isShowPreview = false;

	      if (this.isOrderPublicUrlAvailable) {
	        this.setPageTitle(this.localize.SALESCENTER_LEFT_PAYMENT_ADD);
	      } else {
	        this.setPageTitle(this.localize.SALESCENTER_DEFAULT_TITLE);
	      }
	    },
	    showOrdersList: function showOrdersList() {
	      var _this6 = this;

	      this.hideActionsPopup();
	      salescenter_manager.Manager.showOrdersList({
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this6.refreshOrdersCount();
	      });
	    },
	    showOrderAdd: function showOrderAdd() {
	      var _this7 = this;

	      this.hideActionsPopup();
	      salescenter_manager.Manager.showOrderAdd({
	        ownerId: this.$root.$app.ownerId,
	        ownerTypeId: this.$root.$app.ownerTypeId
	      }).then(function () {
	        _this7.refreshOrdersCount();
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

	      if (this.isShowPayment && !this.isShowStartInfo) {
	        this.$root.$app.sendPayment(event.target, skipPublicMessage);
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
	      var _this8 = this;

	      clearTimeout(this.frameCheckLongTimeout);

	      if (this.showedPageIds.includes(pageId)) {
	        this.loadedPageIds.push(pageId);

	        if (this.currentPage && this.currentPage.id === pageId) {
	          if (this.frameCheckShortTimeout && !this.currentPage.landingId) {
	            this.onFrameError();
	          } else if (this.errorPageIds.includes(this.currentPage.id)) {
	            this.errorPageIds = this.errorPageIds.filter(function (pageId) {
	              return pageId !== _this8.currentPage.id;
	            });
	          }
	        }
	      }

	      if (this.frameCheckShortTimeout && this.currentPage && this.currentPage.id === pageId && !this.currentPage.landingId) {
	        this.onFrameError();
	      }
	    },
	    startFrameCheckTimeout: function startFrameCheckTimeout() {
	      var _this9 = this;

	      // this is a workaround for denied through X-Frame-Options sources
	      if (this.frameCheckShortTimeout) {
	        clearTimeout(this.frameCheckShortTimeout);
	        this.frameCheckShortTimeout = false;
	      }

	      this.frameCheckShortTimeout = setTimeout(function () {
	        _this9.frameCheckShortTimeout = false;
	      }, 500); // to show error on long loading

	      clearTimeout(this.frameCheckLongTimeout);
	      this.frameCheckLongTimeout = setTimeout(function () {
	        if (_this9.currentPage && _this9.showedPageIds.includes(_this9.currentPage.id) && !_this9.loadedPageIds.includes(_this9.currentPage.id)) {
	          _this9.errorPageIds.push(_this9.currentPage.id);
	        }
	      }, 5000);
	    },
	    connect: function connect() {
	      var _this10 = this;

	      salescenter_manager.Manager.startConnection({
	        context: this.$root.$app.context
	      }).then(function () {
	        salescenter_manager.Manager.loadConfig().then(function (result) {
	          if (result.isSiteExists) {
	            _this10.$root.$app.isSiteExists = result.isSiteExists;
	            _this10.isSiteExists = result.isSiteExists;

	            _this10.$root.$app.fillPages().then(function () {
	              _this10.isOrderPublicUrlExists = true;
	              _this10.$root.$app.isOrderPublicUrlExists = true;
	              _this10.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;
	              _this10.$root.$app.isOrderPublicUrlAvailable = result.isOrderPublicUrlAvailable;

	              if (!_this10.isShowPayment) {
	                _this10.openFirstPage();
	              } else {
	                _this10.showPaymentForm();
	              }
	            });
	          }
	        });
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
	      var _this11 = this;

	      this.$root.$app.getOrdersCount().then(function (result) {
	        _this11.ordersCount = result.answer.result || null;
	      }).catch(function () {
	        _this11.ordersCount = null;
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
	      var _this12 = this;

	      var pageId = this.editedPageId;
	      var name = event.target.value;
	      var oldName;
	      this.pages.forEach(function (page) {
	        if (page.id === _this12.editedPageId) {
	          oldName = page.name;
	        }
	      });

	      if (pageId > 0 && oldName && name !== oldName && name.length > 0) {
	        salescenter_manager.Manager.addPage({
	          id: pageId,
	          name: name,
	          analyticsLabel: 'salescenterUpdatePageTitle'
	        }).then(function () {
	          _this12.$root.$app.fillPages().then(function () {
	            if (_this12.editedPageId === _this12.currentPageId) {
	              _this12.setPageTitle(name);
	            }

	            _this12.editedPageId = null;
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
	      var _this13 = this;

	      if (this.currentPageId > 0) {
	        var pages = this.application.pages.filter(function (page) {
	          return page.id === _this13.currentPageId;
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
	      } else if (this.isShowPayment) {
	        res = !this.isOrderPublicUrlAvailable;
	      }

	      return res;
	    },
	    wrapperHeight: function wrapperHeight() {
	      if (this.isShowPreview || this.isShowPayment) {
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

	      if (this.isShowPayment) {
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
	  template: "\n\t\t<div class=\"salescenter-app-wrapper\" :style=\"{height: wrapperHeight}\">\n\t\t\t<div class=\"ui-sidepanel-sidebar salescenter-app-sidebar\" ref=\"sidebar\">\n\t\t\t\t<div class=\"ui-sidepanel-head\">\n\t\t\t\t\t<div class=\"ui-sidepanel-title\">{{localize.SALESCENTER_DEFAULT_TITLE}}</div>\n\t\t\t\t</div>\n\t\t\t\t<ul class=\"ui-sidepanel-menu\" ref=\"sidepanelMenu\">\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isPagesOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"isPagesOpen = !isPagesOpen;\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAGES}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: pagesSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"!page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t:class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isPaymentCreationAvailable\" :class=\"{ 'salescenter-app-sidebar-menu-active': this.isShowPayment}\" class=\"ui-sidepanel-menu-item\" @click=\"showPaymentForm\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_PAYMENT_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrdersList\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDERS}}</div>\n\t\t\t\t\t\t\t<span class=\"ui-sidepanel-counter\" ref=\"ordersCounter\" v-show=\"ordersCount > 0\">{{ordersCount}}</span>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li @click=\"showOrderAdd\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_ORDER_ADD}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li v-if=\"this.$root.$app.isCatalogAvailable\" @click=\"showCatalog\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_CATALOG}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t</li>\n\t\t\t\t\t<li :class=\"{'salescenter-app-sidebar-menu-active': isFormsOpen}\" class=\"ui-sidepanel-menu-item\">\n\t\t\t\t\t\t<a class=\"ui-sidepanel-menu-link\" @click.stop.prevent=\"onFormsClick();\">\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{localize.SALESCENTER_LEFT_FORMS_ALL}}</div>\n\t\t\t\t\t\t\t<div class=\"ui-sidepanel-toggle-btn\">{{this.isPagesOpen ? this.localize.SALESCENTER_SUBMENU_CLOSE : this.localize.SALESCENTER_SUBMENU_OPEN}}</div>\n\t\t\t\t\t\t</a>\n\t\t\t\t\t\t<ul class=\"ui-sidepanel-submenu\" :style=\"{height: formsSubmenuHeight}\">\n\t\t\t\t\t\t\t<li v-for=\"page in pages\" v-if=\"page.isWebform\" :key=\"page.id\"\n\t\t\t\t\t\t\t :class=\"{\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-active': (currentPage && currentPage.id == page.id && isShowPreview),\n\t\t\t\t\t\t\t\t'ui-sidepanel-submenu-edit-mode': (editedPageId === page.id)\n\t\t\t\t\t\t\t}\" class=\"ui-sidepanel-submenu-item\">\n\t\t\t\t\t\t\t\t<a :title=\"page.name\" class=\"ui-sidepanel-submenu-link\" @click.stop=\"onPageClick(page)\">\n\t\t\t\t\t\t\t\t\t<input class=\"ui-sidepanel-input\" :value=\"page.name\" v-on:keyup.enter=\"saveMenuItem($event)\" @blur=\"saveMenuItem($event)\" />\n\t\t\t\t\t\t\t\t\t<div v-if=\"lastAddedPages.includes(page.id)\" class=\"ui-sidepanel-badge-new\"></div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-menu-link-text\">{{page.name}}</div>\n\t\t\t\t\t\t\t\t\t<div class=\"ui-sidepanel-edit-btn\"><span class=\"ui-sidepanel-edit-btn-icon\" @click=\"editMenuItem($event, page);\"></span></div>\n\t\t\t\t\t\t\t\t</a>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t\t<li class=\"salescenter-app-helper-nav-item salescenter-app-menu-add-page\" @click.stop=\"showAddPageActionPopup($event, true)\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text salescenter-app-helper-nav-item-add\">+</span><span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_RIGHT_ACTION_ADD}}</span>\n\t\t\t\t\t\t\t</li>\n\t\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n\t\t\t\t</ul>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-helper-nav\" ref=\"sidepanelNav\">\n\t\t\t\t<a class=\"salescenter-app-helper-nav-item\" @click=\"openControlPanel\">\n\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_PAYMENT_TYPE_ADD}}</span>\n\t\t\t\t</a>\n\t\t\t\t<a class=\"salescenter-app-helper-nav-item\" @click=\"openHelpDesk\">\n\t\t\t\t\t<span class=\"salescenter-app-helper-nav-item-text\">{{localize.SALESCENTER_HOW}}</span>\n\t\t\t\t</a>\n\t\t\t</div> \n\t\t\t<div class=\"salescenter-app-left-side\" ref=\"leftSide\"></div>\n\t\t\t<div class=\"salescenter-app-right-side\">\n\t\t\t\t<div class=\"salescenter-app-page-header\" v-show=\"isShowPreview && !isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-dropdown ui-btn-xs\" @click=\"showActionsPopup($event)\">{{localize.SALESCENTER_RIGHT_ACTIONS_BUTTON}}</div>\n\t\t\t\t\t<div class=\"salescenter-btn-delimiter salescenter-btn-action\"></div>\n\t\t\t\t\t<div class=\"salescenter-btn-action ui-btn ui-btn-link ui-btn-xs ui-btn-icon-edit\" @click=\"editPage\">{{localize.SALESCENTER_RIGHT_ACTION_EDIT}}</div>\n\t\t\t\t</div>\n\t\t\t\t<template v-if=\"isShowStartInfo\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-start-wrapper\">\n\t\t\t\t\t\t<div class=\"ui-title-1 ui-text-center ui-color-medium\" style=\"margin-bottom: 20px;\">{{localize.SALESCENTER_INFO_TEXT_TOP}}</div>\n\t\t\t\t\t\t<div class=\"ui-hr ui-mv-25\"></div>\n\t\t\t\t\t\t<template v-if=\"this.isOrderPublicUrlExists\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM_PUBLIC}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"openConnectedSite\">{{localize.SALESCENTER_INFO_PUBLIC}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else-if=\"isOrderPageDeleted\">\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_ORDER_PAGE_DELETED}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"checkRecycle\">{{localize.SALESCENTER_CHECK_RECYCLE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t\t<template v-else>\n\t\t\t\t\t\t\t<div class=\"salescenter-title-5 ui-title-5 ui-text-center ui-color-medium\">{{localize.SALESCENTER_INFO_TEXT_BOTTOM}}</div>\n\t\t\t\t\t\t\t<div style=\"padding-top: 5px;\" class=\"ui-text-center\">\n\t\t\t\t\t\t\t\t<div class=\"ui-btn ui-btn-primary ui-btn-lg\" @click=\"connect\">{{localize.SALESCENTER_INFO_CREATE}}</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</template>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else-if=\"isFrameError && isShowPreview\">\n\t\t\t\t\t<div class=\"salescenter-app-page-content salescenter-app-lost\">\n\t\t\t\t\t\t<div class=\"salescenter-app-lost-block ui-title-1 ui-text-center ui-color-medium\">{{localize.SALESCENTER_ERROR_TITLE}}</div>\n\t\t\t\t\t\t<div v-if=\"currentPage.isFrameDenied === true\" class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_RIGHT_FRAME_DENIED}}</div>\n\t\t\t\t\t\t<div v-else-if=\"currentPage.isActive !== true\" class=\"salescenter-app-lost-helper salescenter-app-not-active ui-color-medium\">{{localize.SALESCENTER_RIGHT_NOT_ACTIVE}}</div>\n\t\t\t\t\t\t<div v-else class=\"salescenter-app-lost-helper ui-color-medium\">{{localize.SALESCENTER_ERROR_TEXT}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</template>\n\t\t\t\t<div v-show=\"isShowPreview && !isShowStartInfo && !isFrameError\" class=\"salescenter-app-page-content\">\n\t\t\t\t\t<template v-for=\"page in pages\">\n\t\t\t\t\t\t<iframe class=\"salescenter-app-demo\" v-show=\"currentPage && currentPage.id == page.id\" :src=\"getFrameSource(page)\" frameborder=\"0\" @error=\"onFrameError(page.id)\" @load=\"onFrameLoad(page.id)\" :key=\"page.id\"></iframe>\n\t\t\t\t\t</template>\n\t\t\t\t\t<div class=\"salescenter-app-demo-overlay\" :class=\"{\n\t\t\t\t\t\t'salescenter-app-demo-overlay-loading': this.isShowLoader\n\t\t\t\t\t}\">\n\t\t\t\t\t\t<div v-show=\"isShowLoader\" ref=\"previewLoader\"></div>\n\t\t\t\t\t\t<div v-if=\"lastModified\" class=\"salescenter-app-demo-overlay-modification\">{{lastModified}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t    <template v-if=\"this.$root.$app.isPaymentsLimitReached\">\n\t\t\t        <div ref=\"paymentsLimit\" v-show=\"isShowPayment && !isShowStartInfo\"></div>\n\t\t\t\t</template>\n\t\t\t\t<template v-else>\n\t\t\t        <component v-show=\"isShowPayment && !isShowStartInfo\" :is=\"config.templateAddPaymentName\"></component>\n\t\t        </template>\n\t\t\t</div>\n\t\t\t<div class=\"ui-button-panel-wrapper salescenter-button-panel\" ref=\"buttonsPanel\">\n\t\t\t\t<div class=\"ui-button-panel\">\n\t\t\t\t\t<button :class=\"{\n\t\t\t\t\t\t'ui-btn-disabled': !this.isAllowedSubmitButton\n\t\t\t\t\t}\" class=\"ui-btn ui-btn-md ui-btn-success\" @click=\"send($event)\">{{localize.SALESCENTER_SEND}}</button>\n\t\t\t\t\t<button class=\"ui-btn ui-btn-md ui-btn-link\" @click=\"close\">{{localize.SALESCENTER_CANCEL}}</button>\n\t\t\t\t\t<button v-if=\"isShowPayment && !isShowStartInfo && !this.$root.$app.isPaymentsLimitReached\" class=\"ui-btn ui-btn-md ui-btn-link btn-send-crm\" @click=\"send($event, 'y')\">{{localize.SALESCENTER_SAVE_ORDER}}</button>\n\t\t\t\t</div>\n\t\t\t\t<div v-if=\"this.order.errors.length > 0\" ref=\"errorBlock\"></div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	ui_vue.Vue.component(config.templateAddPaymentProductName, {
	  /**
	   * @emits 'changeBasketItem' {index: number, fields: object}
	   * @emits 'refreshBasket' {timeout: number}
	   * @emits 'removeItem' {index: number}
	   */
	  props: ['basketItem', 'basketItemIndex', 'countItems', 'selectedProductIds'],
	  data: function data() {
	    return {
	      timer: null,
	      productSelector: null,
	      isNeedRebindSearch: false
	    };
	  },
	  created: function created() {
	    var _this = this;

	    this.currencyName = this.$root.$app.options.currencyName || null;
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

	          _this.changeData({
	            measureCode: _this.defaultMeasure.code,
	            measureName: _this.defaultMeasure.name
	          });
	        }
	      });
	    }
	  },
	  mounted: function mounted() {
	    this.productSelector = new BX.UI.Dropdown({
	      searchAction: "salescenter.api.order.searchProduct",
	      searchOptions: {
	        restrictedSearchIds: this.selectedProductIds
	      },
	      enableCreation: true,
	      searchResultRenderer: null,
	      targetElement: this.$refs.searchProductLine,
	      items: [{
	        title: '',
	        subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
	      }],
	      messages: {
	        creationLegend: this.localize.SALESCENTER_PRODUCT_CREATE,
	        notFound: this.localize.SALESCENTER_PRODUCT_NOT_FOUND
	      },
	      events: {
	        onSelect: this.selectCatalogItem.bind(this),
	        onAdd: this.showCreationForm.bind(this),
	        onReset: this.resetSearchForm(this)
	      }
	    });
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
	    toggleDiscount: function toggleDiscount(value) {
	      this.changeData({
	        showDiscount: value
	      });
	    },
	    changeData: function changeData(fields) {
	      this.$emit('changeBasketItem', {
	        index: this.basketItemIndex,
	        fields: fields
	      });
	    },
	    isNeedRefreshAfterChanges: function isNeedRefreshAfterChanges() {
	      if (this.isCreationMode) {
	        return this.basketItem.name.length > 0 && this.basketItem.quantity > 0 && this.basketItem.basePrice > 0;
	      }

	      return true;
	    },
	    refreshBasket: function refreshBasket() {
	      if (this.isNeedRefreshAfterChanges()) this.$emit('refreshBasket');
	    },
	    debouncedRefresh: function debouncedRefresh(delay) {
	      var _this2 = this;

	      if (this.timer) {
	        clearTimeout(this.timer);
	      }

	      this.timer = setTimeout(function () {
	        _this2.refreshBasket();

	        _this2.timer = null;
	      }, delay);
	    },
	    changeQuantity: function changeQuantity(event) {
	      var newQuantity = parseFloat(event.target.value);

	      if (!newQuantity) {
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
	    changeBasePrice: function changeBasePrice(event) {
	      var newPrice = Number(event.target.value);

	      if (newPrice < 0) {
	        return;
	      }

	      var fields = this.basketItem;
	      fields.basePrice = newPrice;

	      if (fields.module !== 'catalog') {
	        fields.catalogPrice = newPrice;
	      }

	      fields.isCustomPrice = 'Y';
	      this.changeData(fields);
	      this.debouncedRefresh(300);
	    },
	    changeDiscountType: function changeDiscountType(event) {
	      var type = event.target.value === 'currency' ? 'currency' : 'percent';
	      var fields = this.basketItem;
	      fields.discountType = type;
	      fields.isCustomPrice = 'Y';
	      this.changeData(fields);

	      if (parseFloat(this.basketItem.discount) > 0) {
	        this.refreshBasket();
	      }
	    },
	    changeDiscount: function changeDiscount(event) {
	      var discountValue = parseFloat(event.target.value) || 0;

	      if (discountValue === parseFloat(this.basketItem.discount)) {
	        return;
	      }

	      var fields = this.basketItem;
	      fields.discount = discountValue;
	      fields.isCustomPrice = 'Y';
	      this.changeData(fields);
	      this.debouncedRefresh(300);
	    },
	    changeMeasureValue: function changeMeasureValue(event) {
	      var measureCode = parseInt(event.target.value);
	      var measureName = '';
	      this.measures.forEach(function (measure) {
	        if (parseInt(measure.CODE) === measureCode) {
	          measureName = measure.SYMBOL;
	        }
	      });
	      this.changeData({
	        measureCode: measureCode,
	        measureName: measureName
	      });
	    },
	    showCreationForm: function showCreationForm() {
	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      var value = this.productSelector.targetElement.value;
	      this.changeData({
	        productId: 0,
	        quantity: 1,
	        module: null,
	        sort: this.basketItemIndex,
	        isCreatedProduct: 'Y',
	        name: value,
	        encodedFields: null,
	        isCustomPrice: 'Y',
	        discountInfos: []
	      });
	      this.productSelector.destroyPopupWindow();
	    },
	    resetSearchForm: function resetSearchForm() {
	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      this.productSelector.targetElement.value = '';
	      this.productSelector.items = [{
	        title: '',
	        subTitle: this.localize.SALESCENTER_PRODUCT_BEFORE_SEARCH_TITLE
	      }];
	      this.changeData({
	        productId: 0,
	        name: '',
	        encodedFields: null,
	        quantity: 0,
	        basePrice: 0,
	        formattedPrice: 0,
	        catalogPrice: 0,
	        formattedCatalogPrice: null,
	        discount: 0,
	        discountInfos: [],
	        errors: []
	      });
	      this.productSelector.destroyPopupWindow();
	    },
	    hideCreationForm: function hideCreationForm() {
	      if (!(this.productSelector instanceof BX.UI.Dropdown)) return true;
	      this.changeData({
	        isCreatedProduct: 'N',
	        productId: 0,
	        name: '',
	        encodedFields: null,
	        quantity: 0,
	        basePrice: 0,
	        formattedPrice: 0,
	        catalogPrice: 0,
	        formattedCatalogPrice: null,
	        discount: 0,
	        discountInfos: [],
	        errors: []
	      });
	      this.refreshBasket();
	      this.isNeedRebindSearch = true;
	    },
	    removeItem: function removeItem() {
	      this.$emit('removeItem', {
	        index: this.basketItemIndex
	      });
	    },
	    selectCatalogItem: function selectCatalogItem(sender, item) {
	      var _this3 = this;

	      if (!sender instanceof BX.UI.Dropdown) return true;
	      if (item.id === undefined || parseInt(item.id) <= 0) return true;
	      var fields = {
	        name: item.title,
	        productId: item.id,
	        sort: this.basketItemIndex,
	        module: 'catalog',
	        quantity: this.basketItem.quantity > 0 ? this.basketItem.quantity : 1
	      };

	      if (this.basketItemIndex.productId !== item.id) {
	        fields.encodedFields = null;
	        fields.discount = 0;
	        fields.isCustomPrice = 'N';
	      }

	      BX.ajax.runAction("salescenter.api.order.getBaseProductPrice", {
	        data: {
	          productId: item.id
	        }
	      }).then(function (result) {
	        fields.basePrice = BX.prop.getNumber(result, "data", 0);
	        fields.catalogPrice = BX.prop.getNumber(result, "data", 0);

	        _this3.changeData(fields);

	        _this3.$emit('refreshBasket', 0);
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
	    }
	  },
	  computed: {
	    localize: function localize() {
	      return ui_vue.Vue.getFilteredPhrases('SALESCENTER_PRODUCT_');
	    },
	    showDiscount: function showDiscount() {
	      return this.basketItem.showDiscount === 'Y';
	    },
	    showCatalogPrice: function showCatalogPrice() {
	      return this.basketItem.discount > 0 || parseFloat(this.basketItem.basePrice) !== parseFloat(this.basketItem.catalogPrice);
	    },
	    getMeasureName: function getMeasureName() {
	      return this.basketItem.measureName || this.defaultMeasure.name;
	    },
	    getMeasureCode: function getMeasureCode() {
	      return this.basketItem.measureCode || this.defaultMeasure.code;
	    },
	    restrictedSearchIds: function restrictedSearchIds() {
	      var _this4 = this;

	      var restrictedSearchIds = this.selectedProductIds;

	      if (this.basketItem.module === 'catalog') {
	        restrictedSearchIds = restrictedSearchIds.filter(function (id) {
	          return id !== _this4.basketItem.productId;
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
	    isEmptyProductName: function isEmptyProductName() {
	      return this.basketItem.name.length === 0;
	    }
	  },
	  template: "\n\t\t<div>\n\t\t\t<div class=\"salescenter-app-counter\" v-if=\"countItems > 1\">{{basketItemIndex + 1}}</div>\n\t\t\t<div class=\"salescenter-app-remove\" @click=\"removeItem\" v-if=\"countItems > 1\"></div>\n\t\t\t<div class=\"salescenter-app-form-container\" v-if=\"!isCreationMode\">\n\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:8\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"resetSearchForm\" v-if=\"basketItem.name.length > 0\"> </button>\n\t\t\t\t\t\t\t<input \n\t\t\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t\t\tref=\"searchProductLine\" \n\t\t\t\t\t\t\t\tclass=\"ui-ctl-element ui-ctl-textbox salescenter-app-product-search\" \n\t\t\t\t\t\t\t\t:value=\"basketItem.name\"\n\t\t\t\t\t\t\t\tv-bx-search-product=\"{selector: productSelector, restrictedIds: restrictedSearchIds}\"\n\t\t\t\t\t\t\t>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:4\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', getMeasureName)}}\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\" :class=\"isNotEnoughQuantity ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :value=\"basketItem.quantity\" @input=\"changeQuantity\" @change=\"refreshBasket\">\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"salescenter-form-error\" v-if=\"isNotEnoughQuantity\">{{localize.SALESCENTER_PRODUCT_IS_NOT_AVAILABLE}}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:12\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_PRICE.replace('#CURRENCY_NAME#', currencyName)}}\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\" :class=\"hasPriceError ? 'ui-ctl-danger' : ''\">\n\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\"  :value=\"basketItem.basePrice\"  @input=\"changeBasePrice\" @change=\"refreshBasket\">\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-form-container\" v-else>\n\t\t\t\t<div class=\"salescenter-app-form-row\">\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:8\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_TITLE}}</label>\n\t\t\t\t\t\t<div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100 ui-ctl-after-icon\" :class=\"{'ui-ctl-danger' : this.isEmptyProductName}\">\n\t\t\t\t\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" @click=\"hideCreationForm\"> </button>\n\t\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" @change=\"changeName\" :value=\"basketItem.name\">\n\t\t\t\t\t\t\t\t<div class=\"salescenter-ctl-label\">\n\t\t\t\t\t\t\t\t\t<div class=\"salescenter-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_NEW_LABEL}}</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:4\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_QUANTITY.replace('#MEASURE_NAME#', getMeasureName)}}\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\">\n\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :value=\"basketItem.quantity\" @input=\"changeQuantity\" @change=\"refreshBasket\">\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"align-items: flex-end\">\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:8\">\n\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">\n\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_PRICE.replace('#CURRENCY_NAME#', currencyName)}}\n\t\t\t\t\t\t</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\">\n\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :value=\"basketItem.basePrice\"  @input=\"changeBasePrice\" @change=\"refreshBasket\">\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:4;\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_MEASURE}}</label>\n\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t<select class=\"ui-ctl-element\" @change=\"changeMeasureValue\" :value=\"getMeasureCode\">\n\t\t\t\t\t\t\t\t<option v-for=\"item in measures\" :value=\"item.CODE\">{{item.SYMBOL}}</option>\n\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-sale-container\" v-if=\"showDiscount\">\n\n\t\t\t\t<div class=\"salescenter-app-form-collapse-container\">\n\n\t\t\t\t\t<div class=\"salescenter-app-form-col\">\n\t\t\t\t\t\t<label class=\"salescenter-app-ctl-label-text ui-ctl-label-text\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_TITLE}}</label>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"salescenter-app-form-row\">\n\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:1;max-width: 112px;\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-md ui-ctl-w100\">\n\t\t\t\t\t\t\t\t<input type=\"text\" class=\"ui-ctl-element ui-ctl-textbox\" :value=\"basketItem.discount\" @input=\"changeDiscount\"  @change=\"refreshBasket\">\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:1.5;max-width: 84px;\">\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t<select class=\"ui-ctl-element\" :value=\"basketItem.discountType\" @change=\"changeDiscountType\">\n\t\t\t\t\t\t\t\t\t<option value=\"percent\">%</option>\n\t\t\t\t\t\t\t\t\t<option value=\"currency\">{{currencyName}}</option>\n\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" style=\"flex:auto; text-align: right;\">\n\t\t\t\t\t\t\t<div style=\"margin-bottom: 0;\" class=\"ui-text-4 ui-color-light\">{{localize.SALESCENTER_PRODUCT_DISCOUNT_PRICE_TITLE}}</div>\n\t\t\t\t\t\t\t<div class=\"salescenter-app-form-text\">\n\t\t\t\t\t\t\t\t<span class=\"salescenter-app-text-line-through\" v-if=\"showCatalogPrice\" v-html=\"basketItem.formattedCatalogPrice\"></span>\n\t\t\t\t\t\t\t\t<span v-html=\"basketItem.formattedPrice\"></span>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"salescenter-app-form-row\" style=\"margin-bottom: 0;\">\n\t\t\t\t\t\t<div class=\"salescenter-app-form-col\" v-for=\"discount in basketItem.discountInfos\"\">\n\t\t\t\t\t\t\t<span class=\"ui-text-4 ui-color-light\"> {{discount.name}} \n\t\t\t\t\t\t\t<a :href=\"discount.editPageUrl\" @click=\"openDiscountEditor(event, discount.editPageUrl)\">\n\t\t\t\t\t\t\t\t{{localize.SALESCENTER_PRODUCT_DISCOUNT_EDIT_PAGE_URL_TITLE}}\n\t\t\t\t\t\t\t</a></span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"salescenter-app-collapse-link-container\">\n\t\t\t\t\t<a class=\"salescenter-app-collapse-link\" @click=\"toggleDiscount('N')\">{{localize.SALESCENTER_PRODUCT_HIDE_DISCOUNT}}</a>\n\t\t\t\t</div>\n\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-sale-container\" v-else>\n\t\t\t\t<div class=\"salescenter-app-collapse-link-container\">\n\t\t\t\t\t<a class=\"salescenter-app-collapse-link\"  @click=\"toggleDiscount('Y')\">{{localize.SALESCENTER_PRODUCT_ADD_DISCOUNT}}</a>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	ui_vue.Vue.component(config.templateAddPaymentName, {
	  data: function data() {
	    return {};
	  },
	  created: function created() {
	    var defaultCurrency = this.$root.$app.options.currencyCode || '';

	    if (this.$root.$app.options.showPaySystemSettingBanner) {
	      this.$store.commit('orderCreation/showBanner');
	    }

	    var defaultPrice = BX.Currency.currencyFormat(0, defaultCurrency, true);
	    this.$store.dispatch('orderCreation/setCurrency', defaultCurrency);
	    this.$store.commit('orderCreation/setTotal', {
	      sum: defaultPrice,
	      discount: defaultPrice,
	      result: defaultPrice
	    });
	    this.addBasketItemForm();
	  },
	  methods: {
	    refreshBasket: function refreshBasket() {
	      var timeout = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 300;
	      this.$store.dispatch('orderCreation/refreshBasket', {
	        timeout: timeout
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
	  template: "\n\t<div class=\"salescenter-app-payment-side\">\n\t\t<div class=\"salescenter-app-page-content\">\n\t\t\t<div v-for=\"(item, index) in order.basket\" class=\"salescenter-app-form-wrapper\">\n\t\t\t\t<".concat(config.templateAddPaymentProductName, " \n\t\t\t\t\t:basketItem=\"item\" \n\t\t\t\t\t:basketItemIndex=\"index\"  \n\t\t\t\t\t:countItems=\"countItems\"\n\t\t\t\t\t:selectedProductIds=\"order.selectedProducts\"\n\t\t\t\t\t@changeBasketItem=\"changeBasketItem\" \n\t\t\t\t\t@removeItem=\"removeItem\" \n\t\t\t\t\t@refreshBasket=\"refreshBasket\" \n\t\t\t\t/>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-add-item-container\">\n\t\t\t\t<a @click=\"addBasketItemForm\" class=\"salescenter-app-add-item-link\">{{localize.SALESCENTER_PRODUCT_ADD_PRODUCT}}</a>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-result-container\">\n\t\t\t\t<div class=\"salescenter-app-result-grid-row\">\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_TOTAL_SUM}}:</div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\" :class=\"total.result !== total.sum ? 'salescenter-app-text-line-through' : ''\" v-html=\"total.sum\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-result-grid-row salescenter-app-result-grid-benefit\">\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_TOTAL_DISCOUNT}}:</div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\" v-html=\"total.discount\"></div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"salescenter-app-result-grid-row salescenter-app-result-grid-total\">\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\">{{localize.SALESCENTER_PRODUCT_TOTAL_RESULT}}:</div>\n\t\t\t\t\t<div class=\"salescenter-app-result-grid-item\" v-html=\"total.result\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t<div class=\"salescenter-app-banner\"  v-if=\"isShowedBanner\">\n\t\t\t\t<div class=\"salescenter-app-banner-inner\">\n\t\t\t\t\t<div class=\"salescenter-app-banner-title\">{{localize.SALESCENTER_BANNER_TITLE}}</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-content\">\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-text\">{{localize.SALESCENTER_BANNER_TEXT}}</div>\n\t\t\t\t\t\t<div class=\"salescenter-app-banner-btn-block\">\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-primary salescenter-app-banner-btn-connect\" @click=\"openControlPanel\">{{localize.SALESCENTER_BANNER_BTN_CONFIGURE}}</button>\n\t\t\t\t\t\t\t<button class=\"ui-btn ui-btn-sm ui-btn-link salescenter-app-banner-btn-hide\" @click=\"hideBanner\">{{localize.SALESCENTER_BANNER_BTN_HIDE}}</button>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"salescenter-app-banner-close\" @click=\"hideBanner\"></div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n")
	});

	var App =
	/*#__PURE__*/
	function () {
	  function App() {
	    var _this = this;

	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {
	      dialogId: null,
	      sessionId: null,
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
	    this.orderAddPullTag = options.orderAddPullTag;
	    this.landingPublicationPullTag = options.landingPublicationPullTag;
	    this.landingUnPublicationPullTag = options.landingUnPublicationPullTag;
	    this.options = options;
	    this.isProgress = false;
	    this.fillPagesTimeout = false;
	    this.disableSendButton = false;
	    this.context = '';
	    this.fillPagesQueue = [];
	    this.ownerTypeId = '';
	    this.ownerId = '';

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

	      if (!this.store.getters['orderCreation/isAllowedSubmit'] || this.isProgress) {
	        return null;
	      }

	      this.startProgress(buttonEvent);
	      this.store.dispatch('orderCreation/refreshBasket', {
	        timeout: 0,
	        onsuccess: function onsuccess() {
	          BX.ajax.runAction('salescenter.order.createPayment', {
	            data: {
	              basketItems: basket,
	              options: {
	                dialogId: _this7.dialogId,
	                sessionId: _this7.sessionId,
	                ownerTypeId: _this7.ownerTypeId,
	                ownerId: _this7.ownerId,
	                skipPublicMessage: skipPublicMessage
	              }
	            },
	            analyticsLabel: 'salescenterCreatePayment',
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

	              _this7.closeApplication();
	            }
	          }).catch(function (error) {
	            _this7.stopProgress(buttonEvent);

	            App.showError(error);
	          });
	        },
	        onfailure: function onfailure() {
	          _this7.stopProgress(buttonEvent);
	        }
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

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX,BX,BX,BX,BX,BX,BX,BX,BX,BX,BX.Salescenter,BX,BX));
//# sourceMappingURL=app.bundle.js.map
