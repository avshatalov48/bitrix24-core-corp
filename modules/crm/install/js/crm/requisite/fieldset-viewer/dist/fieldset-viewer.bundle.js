this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,main_popup,main_loader,ui_buttons,crm_field_listEditor) {
	'use strict';

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-footer\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div \n\t\t\t\t\tclass=\"crm-requisite-fieldset-viewer-close-button\"\n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t></div>\n\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-item\">\n\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-item-left\">\n\t\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-item-label\">", "</div>\n\t\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-item-value\">", "</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-item-right\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<span \n\t\t\t\t\t\tclass=\"ui-btn ui-btn-link\" \n\t\t\t\t\t\tonclick=\"", "\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-requisite-fieldset-viewer-list-container\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-requisite-fieldset-viewer-list\">\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-requisite-fieldset-viewer-banner\">\n\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-banner-text\">\n\t\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-banner-text-title\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-requisite-fieldset-viewer-banner-text-description\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"crm-requisite-fieldset-viewer-content\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</div>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	/**
	 * @namespace BX.Crm.Requisite
	 */
	var FieldsetViewer = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(FieldsetViewer, _EventEmitter);

	  function FieldsetViewer() {
	    var _this;

	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, FieldsetViewer);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FieldsetViewer).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());

	    _this.setEventNamespace('BX.Crm.Requisite.FieldsetViewer');

	    _this.subscribeFromOptions((options === null || options === void 0 ? void 0 : options.events) || {});

	    _this.setOptions(options);

	    main_core.Event.bind(options.bindElement, 'click', _this.onBindElementClick.bind(babelHelpers.assertThisInitialized(_this)));
	    return _this;
	  }

	  babelHelpers.createClass(FieldsetViewer, [{
	    key: "setData",
	    value: function setData(data) {
	      this.cache.set('data', data);
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      return this.cache.get('data', {});
	    }
	  }, {
	    key: "load",
	    value: function load() {
	      var _this2 = this;

	      return new Promise(function (resolve) {
	        var _this2$getOptions = _this2.getOptions(),
	            entityTypeId = _this2$getOptions.entityTypeId,
	            entityId = _this2$getOptions.entityId;

	        BX.ajax.runAction('crm.api.fieldset.load', {
	          json: {
	            entityTypeId: entityTypeId,
	            entityId: entityId
	          }
	        }).then(function (result) {
	          resolve(result.data);
	        });
	      });
	    }
	  }, {
	    key: "setOptions",
	    value: function setOptions(options) {
	      this.cache.set('options', babelHelpers.objectSpread({}, options));
	    }
	  }, {
	    key: "getOptions",
	    value: function getOptions() {
	      return this.cache.get('options');
	    }
	  }, {
	    key: "getPopup",
	    value: function getPopup() {
	      var _this3 = this;

	      return this.cache.remember('popup', function () {
	        var options = _this3.getOptions();

	        return new main_popup.Popup(babelHelpers.objectSpread({
	          bindElement: options.bindElement,
	          autoHide: false,
	          width: 570,
	          height: 478,
	          className: 'crm-requisite-fieldset-viewer',
	          noAllPaddings: true
	        }, main_core.Type.isPlainObject(options === null || options === void 0 ? void 0 : options.popupOptions) ? options === null || options === void 0 ? void 0 : options.popupOptions : {}, {
	          events: {
	            onClose: function onClose() {
	              _this3.emit('onClose', {
	                changed: _this3.getIsChanged()
	              });

	              _this3.setIsChanged(false);
	            }
	          }
	        }));
	      });
	    }
	  }, {
	    key: "setIsChanged",
	    value: function setIsChanged(value) {
	      this.cache.set('isChanged', main_core.Text.toBoolean(value));
	    }
	  }, {
	    key: "getIsChanged",
	    value: function getIsChanged() {
	      return this.cache.get('isChanged', false);
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      return this.cache.remember('loader', function () {
	        return new main_loader.Loader();
	      });
	    }
	  }, {
	    key: "show",
	    value: function show() {
	      var _this4 = this;

	      var popup = this.getPopup();
	      main_core.Dom.clean(popup.getContentContainer());
	      void this.getLoader().show(popup.getContentContainer());
	      this.load().then(function (result) {
	        _this4.setData(babelHelpers.objectSpread({}, result));

	        popup.setContent(_this4.createPopupContent(result));
	      });
	      popup.show();
	    }
	  }, {
	    key: "hide",
	    value: function hide() {
	      this.getPopup().close();
	    }
	  }, {
	    key: "onBindElementClick",
	    value: function onBindElementClick(event) {
	      event.preventDefault();
	      this.show();
	    }
	  }, {
	    key: "createPopupContent",
	    value: function createPopupContent(data) {
	      return main_core.Tag.render(_templateObject(), this.createBannerLayout(data), this.createListLayout(data), this.getFooterLayout(), this.createCloseButton());
	    }
	  }, {
	    key: "createBannerLayout",
	    value: function createBannerLayout(data) {
	      var title = main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_TITLE').replace('{{requisite}}', " <strong>".concat(data === null || data === void 0 ? void 0 : data.title, "</strong>"));

	      var description = function () {
	        var text = main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_DESCRIPTION');

	        if (main_core.Type.isStringFilled(data === null || data === void 0 ? void 0 : data.more)) {
	          text += " <a class=\"ui-link\" href=\"".concat(main_core.Text.encode(data === null || data === void 0 ? void 0 : data.more), "\">\n\t\t\t\t\t\t").concat(main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_BANNER_MORE_LINK_LABEL'), "\n\t\t\t\t\t</a>");
	        }

	        return text;
	      }();

	      return main_core.Tag.render(_templateObject2(), title, description);
	    }
	  }, {
	    key: "createListLayout",
	    value: function createListLayout(data) {
	      return main_core.Tag.render(_templateObject3(), this.createListContainer(data.fields));
	    }
	  }, {
	    key: "createListContainer",
	    value: function createListContainer(fields) {
	      var _this5 = this;

	      return main_core.Tag.render(_templateObject4(), fields.map(function (options) {
	        return _this5.createListItem(options);
	      }));
	    }
	  }, {
	    key: "createListItem",
	    value: function createListItem(options) {
	      var _this6 = this;

	      var editButton = function () {
	        var _options$editing;

	        if (main_core.Type.isStringFilled(options === null || options === void 0 ? void 0 : (_options$editing = options.editing) === null || _options$editing === void 0 ? void 0 : _options$editing.url)) {
	          var onEditButtonClick = function onEditButtonClick() {
	            var _options$editing2;

	            BX.SidePanel.Instance.open(options === null || options === void 0 ? void 0 : (_options$editing2 = options.editing) === null || _options$editing2 === void 0 ? void 0 : _options$editing2.url, {
	              events: {
	                onClose: function onClose() {
	                  _this6.show();
	                }
	              }
	            });

	            _this6.setIsChanged(true);
	          };

	          return main_core.Tag.render(_templateObject5(), onEditButtonClick, main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_LIST_ITEM_VALUE_LINK_LABEL'));
	        }

	        return '';
	      }();

	      return main_core.Tag.render(_templateObject6(), main_core.Text.encode(options === null || options === void 0 ? void 0 : options.label), main_core.Text.encode(options === null || options === void 0 ? void 0 : options.value), editButton);
	    }
	  }, {
	    key: "createCloseButton",
	    value: function createCloseButton() {
	      var _this7 = this;

	      return this.cache.remember('closeButton', function () {
	        var onCloseClick = function onCloseClick() {
	          _this7.hide();
	        };

	        return main_core.Tag.render(_templateObject7(), onCloseClick);
	      });
	    }
	  }, {
	    key: "getFieldListEditor",
	    value: function getFieldListEditor() {
	      var _this8 = this;

	      return this.cache.remember('fieldListEditor', function () {
	        var options = _this8.getOptions();

	        return new crm_field_listEditor.ListEditor(babelHelpers.objectSpread({
	          setId: _this8.getData().id,
	          title: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_TITLE'),
	          editable: {
	            label: {
	              label: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER__SET_EDITOR_NAME_LABEL'),
	              type: 'string'
	            }
	          },
	          autoSave: false,
	          events: {
	            onSave: function onSave() {
	              return _this8.show();
	            }
	          }
	        }, main_core.Type.isPlainObject(options.fieldListEditorOptions) ? options.fieldListEditorOptions : {}));
	      });
	    }
	  }, {
	    key: "getEditButton",
	    value: function getEditButton() {
	      var _this9 = this;

	      return this.cache.remember('editButton', function () {
	        return new ui_buttons.Button({
	          text: main_core.Loc.getMessage('CRM_REQUISITE_FIELDSET_VIEWER_EDIT_BUTTON_LABEL'),
	          color: ui_buttons.Button.Color.LIGHT_BORDER,
	          icon: ui_buttons.Button.Icon.EDIT,
	          size: ui_buttons.Button.Size.SMALL,
	          round: true,
	          events: {
	            click: _this9.onEditButtonClick.bind(_this9)
	          }
	        });
	      });
	    }
	  }, {
	    key: "onEditButtonClick",
	    value: function onEditButtonClick() {
	      this.getFieldListEditor().showSlider();
	      this.setIsChanged(true);
	    }
	  }, {
	    key: "getFooterLayout",
	    value: function getFooterLayout() {
	      var _this10 = this;

	      return this.cache.remember('footerLayout', function () {
	        return main_core.Tag.render(_templateObject8(), _this10.getEditButton().render());
	      });
	    }
	  }]);
	  return FieldsetViewer;
	}(main_core_events.EventEmitter);

	exports.FieldsetViewer = FieldsetViewer;

}((this.BX.Crm.Requisite = this.BX.Crm.Requisite || {}),BX,BX.Event,BX.Main,BX,BX.UI,BX.Crm.Field));
//# sourceMappingURL=fieldset-viewer.bundle.js.map
