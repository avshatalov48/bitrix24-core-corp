this.BX = this.BX || {};
(function (exports,main_loader,main_popup,main_core) {
	'use strict';

	var Template = /*#__PURE__*/function () {
	  function Template(data) {
	    babelHelpers.classCallCheck(this, Template);
	    this.data = data;
	  }

	  babelHelpers.createClass(Template, [{
	    key: "getId",
	    value: function getId() {
	      return parseInt(this.data.id);
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      return this.data.name;
	    }
	  }], [{
	    key: "create",
	    value: function create(data) {
	      if (main_core.Type.isPlainObject(data) && parseInt(data.id) > 0 && main_core.Type.isString(data.name)) {
	        return new Template(data);
	      }

	      return null;
	    }
	  }]);
	  return Template;
	}();

	var Document = /*#__PURE__*/function () {
	  function Document(data) {
	    babelHelpers.classCallCheck(this, Document);
	    this.data = data;
	  }

	  babelHelpers.createClass(Document, [{
	    key: "getId",
	    value: function getId() {
	      return parseInt(this.data.id);
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      return this.data.title;
	    }
	  }, {
	    key: "getPublicUrl",
	    value: function getPublicUrl() {
	      return this.data.publicUrl;
	    }
	  }], [{
	    key: "create",
	    value: function create(data) {
	      if (main_core.Type.isPlainObject(data) && parseInt(data.id) > 0 && main_core.Type.isString(data.title)) {
	        return new Document(data);
	      }

	      return null;
	    }
	  }]);
	  return Document;
	}();

	var Menu = /*#__PURE__*/function () {
	  function Menu(params) {
	    babelHelpers.classCallCheck(this, Menu);
	    babelHelpers.defineProperty(this, "progress", false);
	    babelHelpers.defineProperty(this, "templates", null);
	    babelHelpers.defineProperty(this, "documents", null);
	    babelHelpers.defineProperty(this, "analyticsLabelPrefix", 'documentgeneratorSelector');
	    babelHelpers.defineProperty(this, "isDocumentsLimitReached", false);

	    if (main_core.Type.isPlainObject(params)) {
	      if (main_core.Type.isDomNode(params.node)) {
	        this.node = params.node;
	      }

	      if (main_core.Type.isString(params.moduleId)) {
	        this.moduleId = params.moduleId;
	      }

	      if (main_core.Type.isString(params.provider)) {
	        this.provider = params.provider;
	      }

	      if (main_core.Type.isString(params.analyticsLabelPrefix)) {
	        this.analyticsLabelPrefix = params.analyticsLabelPrefix;
	      }

	      if (main_core.Type.isString(params.value) || main_core.Type.isNumber(params.value)) {
	        this.value = params.value;
	      }
	    }
	  }

	  babelHelpers.createClass(Menu, [{
	    key: "isValid",
	    value: function isValid() {
	      return main_core.Type.isString(this.moduleId) && this.moduleId.length > 0 && main_core.Type.isString(this.provider) && this.provider.length > 0 && !main_core.Type.isNil(this.value);
	    }
	  }, {
	    key: "createDocument",
	    value: function createDocument(template) {
	      var _this = this;

	      return new Promise(function (resolve, reject) {
	        if (_this.progress) {
	          reject('loading');
	        }

	        if (_this.isValid() && template instanceof Template) {
	          _this.progress = true;

	          _this.showLoader();

	          BX.DocumentGenerator.Document.askAboutUsingPreviousDocumentNumber(_this.provider, template.getId(), _this.value, function (previousNumber) {
	            var data = {
	              templateId: template.getId(),
	              providerClassName: _this.provider,
	              value: _this.value,
	              values: {}
	            };

	            if (previousNumber) {
	              data.values.DocumentNumber = previousNumber;
	            }

	            main_core.ajax.runAction('documentgenerator.document.add', {
	              data: data,
	              analyticsLabel: _this.analyticsLabelPrefix + 'CreateDocument'
	            }).then(function (response) {
	              _this.progress = false;

	              _this.hideLoader();

	              var document = Document.create(response.data.document);

	              if (document) {
	                if (main_core.Type.isArray(_this.documents)) {
	                  _this.documents.unshift(document);
	                }

	                resolve(document);
	              } else {
	                reject('error trying create document object');
	              }
	            })["catch"](function (response) {
	              _this.progress = false;

	              _this.hideLoader();

	              reject(_this.getErrorMessageFromResponse(response));
	            });
	          }, function () {
	            _this.progress = false;

	            _this.hideLoader();
	          });
	        } else {
	          reject('error trying generate document');
	        }
	      });
	    }
	  }, {
	    key: "getDocumentPublicUrl",
	    value: function getDocumentPublicUrl(document) {
	      var _this2 = this;

	      return new Promise(function (resolve, reject) {
	        if (!(document instanceof Document)) {
	          reject('wrong document');
	          return;
	        }

	        if (main_core.Type.isString(document.getPublicUrl()) && document.getPublicUrl().length > 0) {
	          resolve(document.getPublicUrl());
	        } else {
	          if (_this2.progress) {
	            reject('loading');
	          } else {
	            _this2.progress = true;

	            _this2.showLoader();

	            main_core.ajax.runAction('documentgenerator.document.enablePublicUrl', {
	              data: {
	                id: document.getId(),
	                status: 1
	              },
	              analyticsLabel: _this2.analyticsLabelPrefix + 'GetPublicUrl'
	            }).then(function (response) {
	              _this2.progress = false;

	              _this2.hideLoader();

	              document.data.publicUrl = response.data.publicUrl;
	              resolve(document.getPublicUrl());
	            })["catch"](function (response) {
	              _this2.progress = false;

	              _this2.hideLoader();

	              reject(_this2.getErrorMessageFromResponse(response));
	            });
	          }
	        }
	      });
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this3 = this;

	      var node = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return new Promise(function (resolve, reject) {
	        if (!node) {
	          node = _this3.node;
	        }

	        _this3.getTemplates().then(function (templates) {
	          main_popup.PopupMenu.show(_this3.getPopupMenuId(), node, _this3.prepareTemplatesList(templates, function (object) {
	            var menu = main_popup.PopupMenu.getMenuById(_this3.getPopupMenuId());

	            if (menu) {
	              menu.destroy();
	            }

	            resolve(object);
	          }), {
	            offsetLeft: 0,
	            offsetTop: 0,
	            closeByEsc: true
	          });
	        })["catch"](function (error) {
	          if (error !== 'loading') {
	            reject(error);
	          }
	        });
	      });
	    }
	  }, {
	    key: "getTemplates",
	    value: function getTemplates() {
	      var _this4 = this;

	      return new Promise(function (resolve, reject) {
	        if (!_this4.isValid()) {
	          reject('wrong data');
	          return;
	        }

	        if (_this4.templates === null) {
	          if (_this4.progress) {
	            reject('loading');
	            return;
	          }

	          _this4.progress = true;

	          _this4.showLoader();

	          main_core.ajax.runAction('documentgenerator.api.document.getButtonTemplates', {
	            data: {
	              moduleId: _this4.moduleId,
	              provider: _this4.provider,
	              value: _this4.value
	            },
	            analyticsLabel: _this4.analyticsLabelPrefix + 'LoadTemplates'
	          }).then(function (response) {
	            _this4.progress = false;

	            _this4.hideLoader();

	            _this4.parseButtonResponse(response);

	            resolve(_this4.templates);
	          })["catch"](function (response) {
	            _this4.progress = false;

	            _this4.hideLoader();

	            reject(_this4.getErrorMessageFromResponse(response));
	          });
	        } else {
	          resolve(_this4.templates);
	        }
	      });
	    }
	  }, {
	    key: "getDocuments",
	    value: function getDocuments(node) {
	      var _this5 = this;

	      return new Promise(function (resolve, reject) {
	        if (_this5.progress) {
	          reject('loading');
	          return;
	        }

	        if (_this5.documents === null) {
	          _this5.documents = [];
	          _this5.progress = true;

	          _this5.showLoader(node);

	          main_core.ajax.runAction('documentgenerator.document.list', {
	            data: {
	              select: ['id', 'number', 'title'],
	              filter: {
	                "=provider": _this5.provider.toLowerCase(),
	                "=value": _this5.value
	              },
	              order: {
	                id: 'desc'
	              }
	            },
	            analyticsLabel: _this5.analyticsLabelPrefix + 'LoadDocuments'
	          }).then(function (response) {
	            _this5.progress = false;

	            _this5.hideLoader();

	            response.data.documents.forEach(function (data) {
	              var document = Document.create(data);

	              if (document) {
	                _this5.documents.push(document);
	              }
	            });
	            resolve(_this5.documents);
	          })["catch"](function (response) {
	            _this5.progress = false;

	            _this5.hideLoader();

	            reject(_this5.getErrorMessageFromResponse(response));
	          });
	        } else {
	          resolve(_this5.documents);
	        }
	      });
	    }
	  }, {
	    key: "prepareTemplatesList",
	    value: function prepareTemplatesList(templates, _onclick) {
	      var _this6 = this;

	      var result = [];

	      if (this.isDocumentsLimitReached) {
	        result.push({
	          text: main_core.Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_LIMIT_REACHED_ADD'),
	          className: 'documentgenerator-selector-menu-item-with-lock',
	          onclick: function onclick() {
	            _this6.showTariffPopup();

	            _onclick(null);
	          }
	        });
	      } else if (main_core.Type.isArray(templates) && main_core.Type.isFunction(_onclick)) {
	        templates.forEach(function (template) {
	          result.push({
	            text: template.getName(),
	            onclick: function onclick() {
	              _onclick(template);
	            }
	          });
	        });
	      }

	      if (result.length > 0) {
	        result.push({
	          delimiter: true
	        });
	      }

	      var selector = this;
	      result.push({
	        text: main_core.Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS'),
	        cacheable: true,
	        events: {
	          onSubMenuShow: function onSubMenuShow() {
	            var _this7 = this;

	            if (this.isSubmenuLoaded) {
	              return;
	            }

	            this.isSubmenuLoaded = true;
	            var submenu = this.getSubMenu();
	            var loadingItem = submenu.getMenuItem('loading');
	            selector.getDocuments(loadingItem.getLayout().text).then(function (documents) {
	              if (documents.length <= 0) {
	                if (loadingItem) {
	                  loadingItem.getLayout().text.innerText = main_core.Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_EMPTY');
	                }
	              } else {
	                submenu.removeMenuItem('loading');
	                var menuItems = [];
	                documents.forEach(function (document) {
	                  menuItems.push({
	                    text: document.getTitle(),
	                    onclick: function onclick() {
	                      _onclick(document);
	                    }
	                  });
	                });

	                _this7.addSubMenu(menuItems);

	                _this7.showSubMenu();
	              }
	            })["catch"](function (error) {
	              if (loadingItem) {
	                loadingItem.getLayout().text.innerText = error;
	              }
	            });
	          }
	        },
	        items: [{
	          id: 'loading',
	          text: main_core.Loc.getMessage('DOCGEN_SELECTOR_MENU_DOCUMENTS_LOADING')
	        }]
	      });
	      return result;
	    }
	  }, {
	    key: "parseButtonResponse",
	    value: function parseButtonResponse(response) {
	      var _this8 = this;

	      this.templates = [];

	      if (response.data && response.data.isDocumentsLimitReached) {
	        this.isDocumentsLimitReached = response.data.isDocumentsLimitReached;
	      }

	      if (response.data && response.data.templates && main_core.Type.isArray(response.data.templates)) {
	        response.data.templates.forEach(function (data) {
	          var template = Template.create(data);

	          if (template) {
	            _this8.templates.push(template);
	          }
	        });
	      }

	      return this.templates;
	    }
	  }, {
	    key: "getErrorMessageFromResponse",
	    value: function getErrorMessageFromResponse(response) {
	      var error = '';

	      if (response.errors && main_core.Type.isArray(response.errors)) {
	        response.errors.forEach(function (_ref) {
	          var message = _ref.message;

	          if (error.length > 0) {
	            error += ', ';
	          }

	          error += message;
	        });
	      }

	      return error;
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.loader) {
	        this.loader = new main_loader.Loader({
	          size: 50
	        });
	      }

	      return this.loader;
	    }
	  }, {
	    key: "showLoader",
	    value: function showLoader(node) {
	      if (!main_core.Type.isDomNode(node)) {
	        node = this.node;
	      }

	      if (node && !this.getLoader().isShown()) {
	        this.getLoader().show(node);
	      }
	    }
	  }, {
	    key: "hideLoader",
	    value: function hideLoader() {
	      if (this.getLoader().isShown()) {
	        this.getLoader().hide();
	      }
	    }
	  }, {
	    key: "getPopupMenuId",
	    value: function getPopupMenuId() {
	      return 'documentgenerator-selector-popup-menu';
	    }
	  }, {
	    key: "showTariffPopup",
	    value: function showTariffPopup() {
	      var _this9 = this;

	      this.getFeatureContent().then(function (content) {
	        _this9.getFeaturePopup(content).show();
	      })["catch"](function (error) {
	        console.error(error);
	      });
	    }
	  }, {
	    key: "getFeaturePopup",
	    value: function getFeaturePopup(content) {
	      var _this10 = this;

	      if (this.featurePopup != null) {
	        return this.featurePopup;
	      }

	      this.featurePopup = new main_popup.PopupWindow('bx-popup-documentgenerator-popup', null, {
	        zIndex: 200,
	        autoHide: true,
	        closeByEsc: true,
	        closeIcon: true,
	        overlay: true,
	        events: {
	          onPopupDestroy: function onPopupDestroy() {
	            _this10.featurePopup = null;
	          }
	        },
	        content: content,
	        contentColor: 'white'
	      });
	      return this.featurePopup;
	    }
	  }, {
	    key: "getFeatureContent",
	    value: function getFeatureContent() {
	      var _this11 = this;

	      return new Promise(function (resolve, reject) {
	        if (_this11.featureContent) {
	          resolve(_this11.featureContent);
	          return;
	        }

	        main_core.ajax.runAction('documentgenerator.document.getFeature').then(function (response) {
	          _this11.featureContent = document.createElement('div');

	          _this11.getFeaturePopup(_this11.featureContent);

	          main_core.Runtime.html(_this11.featureContent, response.data.html, {
	            htmlFirst: true
	          }).then(function () {
	            resolve(_this11.featureContent);
	          });
	        })["catch"](function (response) {
	          reject(_this11.getErrorMessageFromResponse(response));
	        });
	      });
	    }
	  }]);
	  return Menu;
	}();

	var Selector = {
	  Menu: Menu,
	  Template: Template,
	  Document: Document
	};

	exports.Selector = Selector;

}((this.BX.DocumentGenerator = this.BX.DocumentGenerator || {}),BX,BX.Main,BX));
//# sourceMappingURL=selector.bundle.js.map
