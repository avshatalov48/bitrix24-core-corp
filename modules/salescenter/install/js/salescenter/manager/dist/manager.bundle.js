this.BX = this.BX || {};
(function (exports,rest_client,main_core) {
	'use strict';

	var Manager =
	/*#__PURE__*/
	function () {
	  function Manager() {
	    babelHelpers.classCallCheck(this, Manager);
	  }

	  babelHelpers.createClass(Manager, null, [{
	    key: "init",
	    value: function init(options) {
	      options.connectedSiteId = parseInt(options.connectedSiteId);

	      if (options.connectedSiteId > 0) {
	        Manager.connectedSiteId = options.connectedSiteId;
	      }

	      options.sessionId = parseInt(options.sessionId);

	      if (options.sessionId > 0) {
	        Manager.sessionId = options.sessionId;
	      }

	      if (main_core.Type.isString(options.siteTemplateCode)) {
	        Manager.siteTemplateCode = options.siteTemplateCode;
	      }

	      if (main_core.Type.isString(options.connectPath)) {
	        Manager.connectPath = options.connectPath;
	      }

	      if (main_core.Type.isBoolean(options.isSitePublished)) {
	        Manager.isSitePublished = options.isSitePublished;
	      }

	      if (main_core.Type.isBoolean(options.isSiteExists)) {
	        Manager.isSiteExists = options.isSiteExists;
	      }

	      if (main_core.Type.isBoolean(options.isOrderPublicUrlAvailable)) {
	        Manager.isOrderPublicUrlAvailable = options.isOrderPublicUrlAvailable;
	      } else {
	        Manager.isOrderPublicUrlAvailable = false;
	      }

	      if (!Manager.isPullInited) {
	        main_core.Event.ready(Manager.initPull);
	      } // the crutch to load landings dynamically


	      if (!top.BX.Landing) {
	        top.BX.Landing = {
	          PageObject: {},
	          Main: {}
	        };
	      }
	    }
	  }, {
	    key: "loadConfig",
	    value: function loadConfig() {
	      return new Promise(function (resolve, reject) {
	        rest_client.rest.callMethod('salescenter.manager.getConfig').then(function (result) {
	          Manager.init(result.answer.result);
	          resolve(result.answer.result);
	        }).catch(function (reason) {
	          reject(reason);
	        });
	      });
	    }
	    /**
	     * Shows slider with module description and connect button.
	     *
	     * @returns {Promise<any>}
	     */

	  }, {
	    key: "startConnection",
	    value: function startConnection() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      return new Promise(function (resolve, reject) {
	        if (!Manager.connectPath) {
	          reject('no connect path');
	        }

	        var url = new main_core.Uri(Manager.connectPath);

	        if (!main_core.Type.isPlainObject(params)) {
	          params = {};
	        }

	        params.analyticsLabel = 'salescenterStartConnection';
	        url.setQueryParams(params);
	        Manager.openSlider(url.toString(), {
	          width: 760
	        }).then(function () {
	          resolve();
	        }).catch(function (reason) {
	          reject(reason);
	        });
	      });
	    }
	    /**
	     * Shows slider with landing template.
	     *
	     * @param {Object} params
	     * @returns {Promise<any>}
	     */

	  }, {
	    key: "connect",
	    value: function connect(params) {
	      return new Promise(function (resolve) {
	        if (Manager.connectedSiteId > 0 && Manager.isSiteExists) {
	          resolve();
	        } else {
	          var url = new main_core.Uri('/shop/stores/site/edit/0/');

	          if (!main_core.Type.isPlainObject(params)) {
	            params = {};
	          }

	          params.analyticsLabel = 'salescenterConnect';

	          if (Manager.siteTemplateCode) {
	            params.tpl = Manager.siteTemplateCode;
	          }

	          url.setQueryParams(params);
	          Manager.openSlider(url.toString()).then(function () {
	            resolve();
	          });
	        }
	      });
	    }
	  }, {
	    key: "publicConnectedSite",
	    value: function publicConnectedSite() {
	      return new Promise(function (resolve, reject) {
	        if (Manager.connectedSiteId > 0 && !Manager.isSitePublished) {
	          rest_client.rest.callMethod('landing.site.publication', {
	            id: Manager.connectedSiteId
	          }).then(function (result) {
	            Manager.isSitePublished = true;
	            Manager.firePublicConnectedSiteEvent();
	            resolve(result);
	          }).catch(function (reason) {
	            reject(reason);
	          });
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "firePublicConnectedSiteEvent",
	    value: function firePublicConnectedSiteEvent() {
	      top.BX.onCustomEvent('Salescenter.Manager:onPublicConnectedSite', {
	        isSitePublished: true
	      });
	    }
	    /**
	     * @returns BX.PopupWindow
	     */

	  }, {
	    key: "getPopup",
	    value: function getPopup(_ref) {
	      var _ref$id = _ref.id,
	          id = _ref$id === void 0 ? '' : _ref$id,
	          _ref$title = _ref.title,
	          title = _ref$title === void 0 ? '' : _ref$title,
	          _ref$text = _ref.text,
	          text = _ref$text === void 0 ? '' : _ref$text,
	          _ref$buttons = _ref.buttons,
	          buttons = _ref$buttons === void 0 ? [] : _ref$buttons;
	      var popup$$1 = BX.PopupWindowManager.getPopupById(id);
	      var content = "<div class=\"salescenter-popup\">\n\t\t\t<div class=\"salescenter-popup-title\">".concat(title, "</div>\n\t\t\t<div class=\"salescenter-popup-text\">").concat(text, "</div>\n\t\t</div>");

	      if (popup$$1) {
	        popup$$1.setContent(content);
	        popup$$1.setButtons(buttons);
	      } else {
	        popup$$1 = new BX.PopupWindow(id, null, {
	          zIndex: 200,
	          className: "salescenter-connect-popup",
	          autoHide: true,
	          closeByEsc: true,
	          padding: 0,
	          closeIcon: true,
	          content: content,
	          width: 400,
	          overlay: true,
	          lightShadow: false,
	          buttons: buttons
	        });
	      }

	      return popup$$1;
	    }
	  }, {
	    key: "showAfterConnectPopup",
	    value: function showAfterConnectPopup() {
	      var popup$$1 = Manager.getPopup({
	        id: 'salescenter-connect-popup',
	        title: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_TITLE'),
	        text: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_DESCRIPTION'),
	        buttons: [new BX.PopupWindowButton({
	          text: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_GO_BUTTON'),
	          className: "ui-btn ui-btn-md ui-btn-primary",
	          events: {
	            click: function click() {
	              Manager.openConnectedSite();
	              popup$$1.close();
	            }
	          }
	        })]
	      });
	      popup$$1.show();
	    }
	  }, {
	    key: "copyUrl",
	    value: function copyUrl(url, event) {
	      BX.clipboard.copy(url);

	      if (event && event.target) {
	        Manager.showCopyLinkPopup(event.target);
	      }
	    }
	  }, {
	    key: "addCustomPage",
	    value: function addCustomPage(page) {
	      return new Promise(function (resolve) {
	        Manager.getAddUrlPopup().then(function (popup$$1) {
	          Manager.templateEngine.$emit('onAddUrlPopupCreate', page);
	          popup$$1.show();
	        });
	        Manager.addUrlResolve = resolve;
	        Manager.addingCustomPage = page;
	      });
	    }
	  }, {
	    key: "resolveAddPopup",
	    value: function resolveAddPopup(pageId, isSaved) {
	      if (Manager.addUrlResolve && main_core.Type.isFunction(Manager.addUrlResolve)) {
	        Manager.addUrlResolve(pageId);
	        Manager.addUrlResolve = null;
	      }

	      if (isSaved && pageId > 0) {
	        if (Manager.addingCustomPage && Manager.addingCustomPage.id && parseInt(Manager.addingCustomPage.id) === parseInt(pageId)) {
	          Manager.showNotification(BX.message('SALESCENTER_MANAGER_UPDATE_URL_SUCCESS'));
	        } else {
	          Manager.showNotification(BX.message('SALESCENTER_MANAGER_ADD_URL_SUCCESS'));
	        }
	      }
	    }
	  }, {
	    key: "initPopupTemplate",
	    value: function initPopupTemplate() {
	      return new Promise(function (resolve) {
	        BX.loadExt('salescenter.url_popup').then(function () {
	          Manager.templateEngine = BX.Vue.create({
	            el: document.createElement('div'),
	            template: '<bx-salescenter-url-popup/>',
	            mounted: function mounted() {
	              Manager.popupNode = this.$el;
	              this.$app = Manager;
	              resolve();
	            }
	          });
	        });
	      });
	    }
	  }, {
	    key: "handleAddUrlPopupAutoHide",
	    value: function handleAddUrlPopupAutoHide(event) {
	      if (!Manager.addUrlPopup) {
	        return true;
	      }

	      if (event.target !== Manager.addUrlPopup.getPopupContainer() && !Manager.addUrlPopup.getPopupContainer().contains(event.target)) {
	        var urlFieldsPopupWindow = null;
	        var urlFieldsPopupMenu = BX.PopupMenu.getMenuById('salescenter-url-fields-popup');

	        if (urlFieldsPopupMenu) {
	          urlFieldsPopupWindow = urlFieldsPopupMenu.popupWindow;
	        }

	        if (!urlFieldsPopupWindow) {
	          return true;
	        } else {
	          if (event.target.dataset['rootMenu'] === 'salescenter-url-fields-popup' || event.target.parentNode.dataset['rootMenu'] === 'salescenter-url-fields-popup') {
	            if (!event.target.classList.contains('menu-popup-item-submenu') && !event.target.parentNode.classList.contains('menu-popup-item-submenu')) {
	              urlFieldsPopupWindow.close();
	            }

	            return false;
	          } else {
	            return true;
	          }
	        }
	      }

	      return false;
	    }
	  }, {
	    key: "getAddUrlPopup",
	    value: function getAddUrlPopup() {
	      return new Promise(function (resolve) {
	        if (!Manager.addUrlPopup) {
	          Manager.initPopupTemplate().then(function () {
	            Manager.addUrlPopup = new BX.PopupWindow({
	              id: 'salescenter-app-add-url',
	              zIndex: 200,
	              autoHide: true,
	              closeByEsc: true,
	              closeIcon: true,
	              padding: 0,
	              contentPadding: 0,
	              content: Manager.popupNode,
	              titleBar: BX.message('SALESCENTER_ACTION_ADD_CUSTOM_TITLE'),
	              contentColor: 'white',
	              width: 600,
	              autoHideHandler: Manager.handleAddUrlPopupAutoHide,
	              events: {
	                onPopupClose: function onPopupClose() {
	                  var newPageId = document.getElementById('salescenter-app-add-custom-url-id');
	                  var isSaved = document.getElementById('salescenter-app-add-custom-url-is-saved').value === 'y';

	                  if (newPageId) {
	                    newPageId = newPageId.value;
	                  } else {
	                    newPageId = false;
	                  }

	                  Manager.resolveAddPopup(newPageId, isSaved);
	                },
	                onPopupDestroy: function onPopupDestroy() {
	                  Manager.addUrlPopup = null;
	                }
	              }
	            });
	            resolve(Manager.addUrlPopup);
	          });
	        } else {
	          resolve(Manager.addUrlPopup);
	        }
	      });
	    }
	  }, {
	    key: "addPage",
	    value: function addPage(fields) {
	      return new Promise(function (resolve, reject) {
	        var method, analyticsLabel;

	        if (fields.analyticsLabel) {
	          analyticsLabel = fields.analyticsLabel;
	          delete fields.analyticsLabel;
	        }

	        if (fields.id > 0) {
	          method = rest_client.rest.callMethod('salescenter.page.update', {
	            id: fields.id,
	            fields: fields
	          });

	          if (!analyticsLabel) {
	            analyticsLabel = 'salescenterUpdatePage';
	          }
	        } else {
	          method = rest_client.rest.callMethod('salescenter.page.add', {
	            fields: fields
	          });

	          if (!analyticsLabel) {
	            analyticsLabel = 'salescenterAddPage';
	          }
	        }

	        method.then(function (result) {
	          if (result.answer.result.page) {
	            var page = result.answer.result.page;
	            var source = 'other';

	            if (page.landingId > 0) {
	              if (parseInt(page.siteId) === parseInt(Manager.connectedSiteId)) {
	                source = 'landing_store_chat';
	              } else {
	                source = 'landing_other';
	              }
	            }

	            Manager.addAnalyticAction({
	              analyticsLabel: analyticsLabel,
	              source: source,
	              type: page.isWebform ? 'forms' : 'info',
	              code: page.code
	            }).then(function () {
	              resolve(result);
	            });
	          } else {
	            resolve(result);
	          }
	        }).catch(function (reason) {
	          reject(reason);
	        });
	      });
	    }
	  }, {
	    key: "checkUrl",
	    value: function checkUrl(url) {
	      return rest_client.rest.callMethod('salescenter.page.geturldata', {
	        url: url
	      });
	    }
	  }, {
	    key: "addSitePage",
	    value: function addSitePage() {
	      var isWebform = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      return new Promise(function (resolve) {
	        if (Manager.connectedSiteId > 0) {
	          BX.loadExt('landing.master').then(function () {
	            BX.Landing.UI.Panel.URLList.getInstance().show('landing', {
	              siteId: Manager.connectedSiteId
	            }).then(function (result) {
	              Manager.addPage({
	                hidden: false,
	                landingId: result.id,
	                isWebform: isWebform
	              }).then(function (result) {
	                resolve(result);
	                Manager.showNotification(BX.message('SALESCENTER_MANAGER_ADD_URL_SUCCESS'));
	              });
	            });
	          });
	        } else {
	          Manager.openSlider('/bitrix/components/bitrix/salescenter.connect/slider.php').then(function () {
	            resolve();
	          });
	        }
	      });
	    }
	  }, {
	    key: "showNotification",
	    value: function showNotification(message) {
	      if (!message) {
	        return;
	      }

	      BX.loadExt('ui.notification').then(function () {
	        BX.UI.Notification.Center.notify({
	          content: message
	        });
	      });
	    }
	  }, {
	    key: "hidePage",
	    value: function hidePage(page) {
	      var method = 'salescenter.page.hide';
	      var source = 'other';

	      if (page.landingId > 0) {
	        if (parseInt(page.siteId) === parseInt(Manager.connectedSiteId)) {
	          source = 'landing_store_chat';
	        } else {
	          source = 'landing_other';
	        }
	      }

	      var data = {
	        id: page.id,
	        fields: {
	          hidden: true
	        },
	        analyticsLabel: 'salescenterDeletePage',
	        source: source,
	        type: page.isWebform ? 'form' : 'info',
	        code: page.code
	      };
	      return new Promise(function (resolve, reject) {
	        rest_client.rest.callMethod(method, data).then(function (result) {
	          resolve(result);
	          Manager.showNotification(BX.message('SALESCENTER_MANAGER_HIDE_URL_SUCCESS'));
	        }).catch(function (result) {
	          reject(result);
	        });
	      });
	    }
	  }, {
	    key: "deleteUrl",
	    value: function deleteUrl(page) {
	      var method = 'salescenter.page.delete';
	      var data = {
	        id: page.id,
	        analyticsLabel: 'salescenterDeletePage',
	        source: 'other',
	        type: page.isWebform ? 'form' : 'info'
	      };
	      return new Promise(function (resolve, reject) {
	        rest_client.rest.callMethod(method, data).then(function (result) {
	          resolve(result);
	          Manager.showNotification(BX.message('SALESCENTER_MANAGER_DELETE_URL_SUCCESS'));
	        }).catch(function (result) {
	          reject(result);
	        });
	      });
	    }
	  }, {
	    key: "editLandingPage",
	    value: function editLandingPage(pageId) {
	      var siteId = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : Manager.connectedSiteId;
	      window.open("/shop/stores/site/".concat(siteId, "/view/").concat(pageId, "/"), '_blank');
	    }
	  }, {
	    key: "openSlider",
	    value: function openSlider(url, options) {
	      if (!main_core.Type.isPlainObject(options)) {
	        options = {};
	      }

	      options = babelHelpers.objectSpread({}, {
	        cacheable: false,
	        allowChangeHistory: false,
	        events: {}
	      }, options);
	      return new Promise(function (resolve) {
	        if (main_core.Type.isString(url) && url.length > 1) {
	          options.events.onClose = function (event) {
	            resolve(event.getSlider());
	          };

	          BX.SidePanel.Instance.open(url, options);
	        } else {
	          resolve();
	        }
	      });
	    }
	  }, {
	    key: "getOrdersListUrl",
	    value: function getOrdersListUrl(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      if (Manager.sessionId > 0) {
	        params['sessionId'] = Manager.sessionId;
	      }

	      return new main_core.Uri('/saleshub/orders/').setQueryParams(params).toString();
	    }
	  }, {
	    key: "showOrdersList",
	    value: function showOrdersList(params) {
	      return Manager.openSlider(Manager.getOrdersListUrl(params));
	    }
	  }, {
	    key: "getOrderAddUrl",
	    value: function getOrderAddUrl(params) {
	      if (!main_core.Type.isPlainObject(params)) {
	        params = {};
	      }

	      if (Manager.sessionId > 0) {
	        params['sessionId'] = Manager.sessionId;
	      }

	      return new main_core.Uri('/saleshub/orders/order/').setQueryParams(params).toString();
	    }
	  }, {
	    key: "showOrderAdd",
	    value: function showOrderAdd(params) {
	      return Manager.openSlider(Manager.getOrderAddUrl(params));
	    }
	  }, {
	    key: "showOrdersListAfterCreate",
	    value: function showOrdersListAfterCreate(orderId) {
	      var ordersListUrl = Manager.getOrdersListUrl({
	        orderId: orderId
	      });
	      var listSlider = BX.SidePanel.Instance.getSlider(ordersListUrl);

	      if (!listSlider) {
	        ordersListUrl = Manager.getOrdersListUrl({
	          orderId: orderId
	        });
	        listSlider = BX.SidePanel.Instance.getSlider(ordersListUrl);
	      }

	      var orderAddUrl = Manager.getOrderAddUrl();
	      var addSlider = BX.SidePanel.Instance.getSlider(orderAddUrl);

	      if (addSlider) {
	        addSlider.destroy();
	      }

	      if (!listSlider) {
	        Manager.showOrdersList({
	          orderId: orderId
	        });
	      } else {
	        top.BX.onCustomEvent(listSlider.getFrameWindow(), 'salescenter-order-create', [{
	          orderId: orderId
	        }]);
	      }
	    }
	  }, {
	    key: "initPull",
	    value: function initPull() {
	      if (BX.PULL) {
	        Manager.isPullInited = true;
	        BX.PULL.subscribe({
	          moduleId: 'salescenter',
	          command: 'SETCONNECTEDSITE',
	          callback: function callback(params) {
	            Manager.init(params);
	          }
	        });
	      }
	    }
	  }, {
	    key: "openControlPanel",
	    value: function openControlPanel() {
	      window.open('/saleshub/', '_blank');
	    }
	  }, {
	    key: "getFormAddUrl",
	    value: function getFormAddUrl() {
	      var formId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;
	      return new main_core.Uri("/crm/webform/edit/".concat(parseInt(formId), "/")).setQueryParams({
	        ACTIVE: 'Y',
	        RELOAD_LIST: 'N'
	      }).toString();
	    }
	  }, {
	    key: "addNewForm",
	    value: function addNewForm() {
	      return new Promise(function (resolve, reject) {
	        Manager.openSlider(Manager.getFormAddUrl()).then(function (slider) {
	          var formId = slider.getData().get('formId');

	          if (formId > 0) {
	            Manager.addNewFormPage(formId).then(function (result) {
	              resolve(result);
	            }).catch(function (reason) {
	              reject(reason);
	            });
	          }
	        });
	      });
	    }
	  }, {
	    key: "addNewFormPage",
	    value: function addNewFormPage(formId) {
	      return new Promise(function (resolve, reject) {
	        var popupId = 'salescenter-add-new-page-popup';
	        Manager.getPopup({
	          id: popupId,
	          title: BX.message('SALESCENTER_MANAGER_NEW_PAGE_POPUP_TITLE'),
	          text: BX.message('SALESCENTER_MANAGER_NEW_PAGE_WAIT')
	        }).show();
	        rest_client.rest.callMethod('salescenter.page.addformpage', {
	          formId: formId
	        }).then(function (result) {
	          if (result.answer.result.page) {
	            resolve(result.answer.result.page);
	            var landingId = result.answer.result.page.landingId;
	            var popup$$1 = Manager.getPopup({
	              id: popupId,
	              title: BX.message('SALESCENTER_MANAGER_NEW_PAGE_POPUP_TITLE'),
	              text: BX.message('SALESCENTER_MANAGER_NEW_PAGE_COMPLETE'),
	              buttons: [new BX.PopupWindowButton({
	                text: BX.message('SALESCENTER_MANAGER_CONNECT_POPUP_GO_BUTTON'),
	                className: "ui-btn ui-btn-md ui-btn-primary",
	                events: {
	                  click: function click() {
	                    Manager.editLandingPage(landingId);
	                    popup$$1.close();
	                  }
	                }
	              })]
	            });
	            popup$$1.show();
	          } else {
	            Manager.getPopup({
	              id: popupId,
	              title: BX.message('SALESCENTER_MANAGER_ERROR_POPUP')
	            }).show();
	            reject();
	          }
	        }).catch(function (error) {
	          Manager.getPopup({
	            id: popupId,
	            title: BX.message('SALESCENTER_MANAGER_ERROR_POPUP'),
	            text: error
	          }).show();
	          reject(error);
	        });
	      });
	    }
	  }, {
	    key: "openConnectedSite",
	    value: function openConnectedSite() {
	      var isRecycle = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;

	      if (Manager.connectedSiteId > 0) {
	        var url = new main_core.Uri("/shop/stores/site/".concat(Manager.connectedSiteId, "/"));
	        var params = {
	          apply_filter: 'y'
	        };

	        if (isRecycle) {
	          params.DELETED = 'Y';
	        } else {
	          params.clear_filter = 'y';
	        }

	        url.setQueryParams(params);
	        window.open(url.toString(), '_blank');
	      }
	    }
	  }, {
	    key: "openHowItWorks",
	    value: function openHowItWorks(event) {
	      Manager.openHelper(event, 'redirect=detail&code=9289135', 'chat_connect');
	    }
	  }, {
	    key: "openHowSmsWorks",
	    value: function openHowSmsWorks(event) {
	      Manager.openHelper(event, 'redirect=detail&code=9680407', 'sms_connect');
	    }
	  }, {
	    key: "openHowToConfigOpenLines",
	    value: function openHowToConfigOpenLines(event) {
	      Manager.openHelper(event, 'redirect=detail&code=7872935', 'openlines_connect');
	    }
	  }, {
	    key: "openHowToConfigPaySystem",
	    value: function openHowToConfigPaySystem(event) {
	      Manager.openHelper(event, 'redirect=detail&code=9600843', 'pay_system_connect');
	    }
	  }, {
	    key: "openHelper",
	    value: function openHelper() {
	      var event = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var url = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : '';
	      var analyticsArticle = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : '';

	      if (event) {
	        event.preventDefault();
	      }

	      if (analyticsArticle) {
	        Manager.addAnalyticAction({
	          analyticsLabel: 'salescenterOpenHelp',
	          article: analyticsArticle
	        }).then(function () {
	          if (top.BX.Helper) {
	            top.BX.Helper.show(url);
	          }
	        });
	      } else if (top.BX.Helper) {
	        top.BX.Helper.show(url);
	      }
	    }
	  }, {
	    key: "openFeedbackForm",
	    value: function openFeedbackForm(event) {
	      if (event && main_core.Type.isFunction(event.preventDefault)) {
	        event.preventDefault();
	      }

	      return Manager.openSlider('/bitrix/components/bitrix/salescenter.feedback/slider.php', {
	        width: 735
	      });
	    }
	  }, {
	    key: "openApplication",
	    value: function openApplication() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var url = new main_core.Uri('/saleshub/app/');

	      if (main_core.Type.isPlainObject(params)) {
	        url.setQueryParams(params);
	      }

	      return new Promise(function (resolve, reject) {
	        Manager.openSlider(url.toString(), {
	          width: 873
	        }).then(function (slider) {
	          resolve(slider.getData());
	        }).catch(function (reason) {
	          reject(reason);
	        });
	      });
	    }
	  }, {
	    key: "addAnalyticAction",
	    value: function addAnalyticAction(params) {
	      return new Promise(function (resolve, reject) {
	        if (!main_core.Type.isPlainObject(params) || !params.analyticsLabel) {
	          reject('wrong params');
	        }

	        var request = new XMLHttpRequest();
	        var url = new main_core.Uri('/bitrix/services/main/ajax.php');
	        url.setQueryParams(params);
	        request.open('GET', url.toString());

	        request.onload = function () {
	          resolve();
	        };

	        request.onerror = function () {
	          reject();
	        };

	        request.send();
	      });
	    }
	  }, {
	    key: "getFieldsMap",
	    value: function getFieldsMap() {
	      return new Promise(function (resolve, reject) {
	        if (Manager.fieldsMap !== null) {
	          resolve(Manager.fieldsMap);
	          return;
	        }

	        main_core.ajax.runAction('salescenter.manager.getFieldsMap', {
	          analyticsLabel: 'salescenterFieldsMapLoading'
	        }).then(function (response) {
	          Manager.fieldsMap = response.data.fields;
	          resolve(response.data.fields);
	        }).catch(function (response) {
	          reject(response.errors);
	        });
	      });
	    }
	  }, {
	    key: "getPageUrl",
	    value: function getPageUrl(pageId, entities) {
	      var context = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;
	      return new Promise(function (resolve, reject) {
	        if (!main_core.Type.isInteger(pageId)) {
	          resolve(null);
	        }

	        if (!main_core.Type.isPlainObject(entities) || entities.length <= 0) {
	          resolve(null);
	        }

	        main_core.ajax.runAction('salescenter.manager.getPageUrl', {
	          data: {
	            pageId: pageId,
	            entities: entities
	          },
	          analyticsLabel: 'salescenterGetPageUrlWithParameters',
	          getParameters: {
	            context: context
	          }
	        }).then(function (response) {
	          resolve(response.data.pageUrl);
	        }).catch(function (response) {
	          reject(response.errors);
	        });
	      });
	    }
	  }]);
	  return Manager;
	}();
	babelHelpers.defineProperty(Manager, "sessionId", null);
	babelHelpers.defineProperty(Manager, "connectedSiteId", null);
	babelHelpers.defineProperty(Manager, "addUrlPopup", null);
	babelHelpers.defineProperty(Manager, "addUrlResolve", null);
	babelHelpers.defineProperty(Manager, "popupNode", null);
	babelHelpers.defineProperty(Manager, "siteTemplateCode", null);
	babelHelpers.defineProperty(Manager, "isSitePublished", null);
	babelHelpers.defineProperty(Manager, "isSiteExists", null);
	babelHelpers.defineProperty(Manager, "isOrderPublicUrlAvailable", null);
	babelHelpers.defineProperty(Manager, "isPullInited", false);
	babelHelpers.defineProperty(Manager, "connectPath", null);
	babelHelpers.defineProperty(Manager, "fieldsMap", null);
	babelHelpers.defineProperty(Manager, "showCopyLinkPopup", function (node) {
	  if (Manager.popupOuterLink) {
	    Manager.popupOuterLink.destroy();
	    Manager.popupOuterLink = null;

	    if (Manager.hideCopyLinkTimeout > 0) {
	      clearTimeout(Manager.hideCopyLinkTimeout);
	      Manager.hideCopyLinkTimeout = 0;
	    }

	    if (Manager.destroyCopyLinkTimeout > 0) {
	      clearTimeout(Manager.destroyCopyLinkTimeout);
	      Manager.destroyCopyLinkTimeout = 0;
	    }
	  }

	  Manager.popupOuterLink = new BX.PopupWindow('salescenter-popup-copy-link', node, {
	    className: 'salescenter-popup-copy-link',
	    darkMode: true,
	    content: BX.message('SALESCENTER_MANAGER_COPY_URL_SUCCESS'),
	    zIndex: 5000
	  });
	  Manager.popupOuterLink.show();
	  Manager.hideCopyLinkTimeout = setTimeout(function () {
	    BX.hide(BX(Manager.popupOuterLink.uniquePopupId));
	    Manager.hideCopyLinkTimeout = 0;
	  }, 2000);
	  Manager.destroyCopyLinkTimeout = setTimeout(function () {
	    Manager.popupOuterLink.destroy();
	    Manager.popupOuterLink = null;
	    Manager.destroyCopyLinkTimeout = 0;
	  }, 2200);
	});

	exports.Manager = Manager;

}((this.BX.Salescenter = this.BX.Salescenter || {}),BX,BX));
//# sourceMappingURL=manager.bundle.js.map
