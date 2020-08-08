this.BX = this.BX || {};
(function (exports,main_core,main_core_events) {
	'use strict';

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div></div>"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-rq-popup-wrapper\">\n\t\t\t\t\t<div class=\"crm-rq-popup-items-list\">", "</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-rq-popup-item crm-rq-popup-item-add-new\">\n\t\t\t\t\t\t<button class=\"crm-rq-popup-item-add-new-btn\">\n\t\t\t\t\t\t\t<span class=\"ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round\" onclick=\"", "\"></span>\n\t\t\t\t\t\t\t<span class=\"crm-rq-popup-item-add-new-btn-text\" onclick=\"", "\">", "</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["<a href=\"\" onclick=\"", "\">", "</a>"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-rq-popup-item crm-rq-popup-item-helper\"></div>"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"text\" placeholder=\"", "\" class=\"ui-ctl-element ui-ctl-textbox\" />"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-search\" onclick=\"", "\"></button>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" onclick=\"", "\"></button>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var RequisiteAutocompleteField = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(RequisiteAutocompleteField, _EventEmitter);

	  function RequisiteAutocompleteField() {
	    var _this;

	    babelHelpers.classCallCheck(this, RequisiteAutocompleteField);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(RequisiteAutocompleteField).call(this));

	    _this.setEventNamespace('BX.Crm.Requisite.Autocomplete');

	    _this._id = "";
	    _this._settings = {};
	    _this._placeholderText = null;
	    _this._isLoading = false;
	    _this._isEnabled = false;
	    _this._context = {};
	    _this._currentItem = null;
	    _this._domNodes = {};
	    _this._dropdown = null;
	    return _this;
	  }

	  babelHelpers.createClass(RequisiteAutocompleteField, [{
	    key: "initialize",
	    value: function initialize(id, settings) {
	      this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
	      this._settings = settings ? settings : {};
	      this._placeholderText = BX.prop.getString(this._settings, "placeholderText", "");
	      this._context = BX.prop.getObject(this._settings, "context", {});
	      this._isEnabled = BX.prop.getBoolean(this._settings, "enabled", false);
	      this._featureRestrictionCallback = BX.prop.getString(this._settings, "featureRestrictionCallback", '');
	      this._isPermitted = this._featureRestrictionCallback === '';
	      this._showFeedbackLink = this._isPermitted ? BX.prop.getBoolean(this._settings, "showFeedbackLink", false) : false;
	      this.doInitialize();
	    }
	  }, {
	    key: "doInitialize",
	    value: function doInitialize() {}
	  }, {
	    key: "layout",
	    value: function layout(container) {
	      if (!main_core.Type.isDomNode(container)) {
	        return;
	      }

	      this._domNodes.requisiteClearButton = main_core.Tag.render(_templateObject(), this.onSearchStringClear.bind(this));
	      this._domNodes.requisiteSearchButton = main_core.Tag.render(_templateObject2(), this.onSearchButtonClick.bind(this));
	      var placeholder = this._placeholderText.length ? main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN').replace('#FIELD_NAME#', this._placeholderText) : "";
	      this._domNodes.requisiteSearchString = main_core.Tag.render(_templateObject3(), placeholder);

	      if (!this._isPermitted) {
	        this._domNodes.requisiteSearchString.setAttribute('onclick', this._featureRestrictionCallback);
	      }

	      this.refreshLayout();
	      main_core.Dom.append(main_core.Tag.render(_templateObject4(), this._domNodes.requisiteSearchButton, this._domNodes.requisiteClearButton, this._domNodes.requisiteSearchString), container);
	      this.initDropdown();
	      this.refreshLayout();
	    }
	  }, {
	    key: "initDropdown",
	    value: function initDropdown() {
	      if (!this._dropdown) {
	        this._dropdown = new Dropdown({
	          searchAction: BX.prop.getString(this._settings, "searchAction", ""),
	          items: [],
	          enableCreation: true,
	          enableCreationOnBlur: false,
	          autocompleteDelay: 1000,
	          messages: {
	            creationLegend: main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADD_REQUISITE'),
	            notFound: main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_NOT_FOUND')
	          }
	        });
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSelect', this.onEntitySelect.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onAdd', this.onEntityAdd.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onReset', this.onEntityReset.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onBeforeSearchStart', this.onEntitySearchStart.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onEntitySearchComplete.bind(this));
	      }

	      this._dropdown.searchOptions = this._context;
	      this._dropdown.targetElement = this._domNodes.requisiteSearchString;

	      this._dropdown.init();

	      this.setEnabled(this._isEnabled);
	      this.setShowFeedbackLink(this._showFeedbackLink);
	    }
	  }, {
	    key: "setCurrentItem",
	    value: function setCurrentItem(autocompleteItem) {
	      this._currentItem = main_core.Type.isPlainObject(autocompleteItem) ? autocompleteItem : null;
	      this.refreshLayout();
	    }
	  }, {
	    key: "setShowFeedbackLink",
	    value: function setShowFeedbackLink(show) {
	      this._showFeedbackLink = !!show;

	      if (this._dropdown) {
	        this._dropdown.setFeedbackFormParams(this._showFeedbackLink ? BX.prop.getObject(this._settings, "feedbackFormParams", {}) : {});
	      }
	    }
	  }, {
	    key: "refreshLayout",
	    value: function refreshLayout() {
	      if (!main_core.Type.isDomNode(this._domNodes.requisiteSearchString) || !main_core.Type.isDomNode(this._domNodes.requisiteSearchButton) || !main_core.Type.isDomNode(this._domNodes.requisiteClearButton)) {
	        return;
	      }

	      var text = '';

	      if (main_core.Type.isObject(this._currentItem) && this._isPermitted) {
	        var textParts = [this._currentItem.title];

	        if (main_core.Type.isStringFilled(this._currentItem.subTitle)) {
	          textParts.push(this._currentItem.subTitle);
	        }

	        text = textParts.join(', ');
	        main_core.Dom.style(this._domNodes.requisiteSearchButton, "display", "none");
	        main_core.Dom.style(this._domNodes.requisiteClearButton, "display", "");

	        if (this._dropdown) {
	          this._dropdown.setCanAddRequisite(false);
	        }
	      } else {
	        main_core.Dom.style(this._domNodes.requisiteClearButton, "display", "none");

	        if (this._isEnabled || !this._isPermitted) {
	          main_core.Dom.style(this._domNodes.requisiteSearchButton, "display", "");
	        } else {
	          main_core.Dom.style(this._domNodes.requisiteSearchButton, "display", "none");
	        }

	        if (this._dropdown) {
	          this._dropdown.setCanAddRequisite(this._isPermitted && BX.prop.getBoolean(this._settings, "canAddRequisite", false));
	        }
	      }

	      this._domNodes.requisiteSearchString.value = text;
	      this.setLoading(false);
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      var state = {
	        currentItem: this._currentItem,
	        searchQuery: main_core.Type.isDomNode(this._domNodes.requisiteSearchString) ? this._domNodes.requisiteSearchString.value : null,
	        items: main_core.Type.isObject(this._dropdown) ? this._dropdown.getItems() : []
	      };
	      return state;
	    }
	  }, {
	    key: "setState",
	    value: function setState(state) {
	      if (!main_core.Type.isPlainObject(state)) {
	        return;
	      }

	      this.setCurrentItem(main_core.Type.isPlainObject(state.currentItem) ? state.currentItem : null);

	      if (main_core.Type.isString(state.searchQuery) && main_core.Type.isDomNode(this._domNodes.requisiteSearchString) && this._isPermitted) {
	        this._domNodes.requisiteSearchString.value = state.searchQuery;
	      }

	      if (main_core.Type.isArray(state.items)) {
	        this._dropdown.setItems(state.items);
	      }
	    }
	  }, {
	    key: "setContext",
	    value: function setContext(context) {
	      this._context = main_core.Type.isPlainObject(context) ? context : {};

	      if (this._dropdown) {
	        this._dropdown.searchOptions = this._context;
	      }
	    }
	  }, {
	    key: "setPlaceholderText",
	    value: function setPlaceholderText(text) {
	      this._placeholderText = main_core.Type.isStringFilled(text) ? text : "";

	      if (main_core.Type.isDomNode(this._domNodes.requisiteSearchString)) {
	        var placeholder = this._placeholderText.length ? main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN').replace('#FIELD_NAME#', this._placeholderText) : "";
	        this._domNodes.requisiteSearchString.placeholder = placeholder;
	      }
	    }
	  }, {
	    key: "setEnabled",
	    value: function setEnabled(enabled) {
	      this._isEnabled = !!enabled;

	      if (this._dropdown) {
	        this._dropdown.setMinSearchStringLength(this._isEnabled ? 3 : 99999);
	      }
	    }
	  }, {
	    key: "setLoading",
	    value: function setLoading(isLoading) {
	      isLoading = !!isLoading;

	      if (isLoading === this._isLoading) {
	        return;
	      }

	      this._isLoading = isLoading;
	      var searchBtn = this._domNodes.requisiteSearchButton;

	      if (main_core.Type.isDomNode(searchBtn)) {
	        if (isLoading) {
	          searchBtn.classList.remove('ui-ctl-icon-search');
	          searchBtn.classList.add('ui-ctl-icon-loader');
	        } else {
	          searchBtn.classList.remove('ui-ctl-icon-loader');
	          searchBtn.classList.add('ui-ctl-icon-search');
	        }
	      }

	      var clearBtn = this._domNodes.requisiteClearButton;

	      if (main_core.Type.isDomNode(clearBtn)) {
	        if (isLoading) {
	          clearBtn.classList.remove('ui-ctl-icon-clear');
	          clearBtn.classList.add('ui-ctl-icon-loader');
	        } else {
	          clearBtn.classList.remove('ui-ctl-icon-loader');
	          clearBtn.classList.add('ui-ctl-icon-clear');
	        }
	      }
	    }
	  }, {
	    key: "onSearchStringClear",
	    value: function onSearchStringClear() {
	      this.emit('onClear');
	    }
	  }, {
	    key: "onSearchButtonClick",
	    value: function onSearchButtonClick() {
	      if (main_core.Type.isObject(this._dropdown)) {
	        this._dropdown.handleTypeInField();
	      }
	    }
	  }, {
	    key: "onEntitySelect",
	    value: function onEntitySelect(event) {
	      var data = event.getData();
	      var dropdown = data[0];
	      var selected = data[1];
	      dropdown.getPopupWindow().close();
	      this.setCurrentItem(selected);
	      this.emit('onSelectValue', selected);
	    }
	  }, {
	    key: "onEntityAdd",
	    value: function onEntityAdd(event) {
	      var data = event.getData();
	      var dropdown = data[0];
	      dropdown.getPopupWindow().close();
	      this.emit('onCreateNewItem');
	    }
	  }, {
	    key: "onEntityReset",
	    value: function onEntityReset() {
	      this.setCurrentItem(null);
	    }
	  }, {
	    key: "onEntitySearchStart",
	    value: function onEntitySearchStart() {
	      this.setLoading(true);

	      this._dropdown.setItems([]);
	    }
	  }, {
	    key: "onEntitySearchComplete",
	    value: function onEntitySearchComplete() {
	      this.setLoading(false);
	    }
	  }], [{
	    key: "create",
	    value: function create(id, settings) {
	      var self = new RequisiteAutocompleteField();
	      self.initialize(id, settings);
	      return self;
	    }
	  }]);
	  return RequisiteAutocompleteField;
	}(main_core_events.EventEmitter);

	var Dropdown = /*#__PURE__*/function (_BX$UI$Dropdown) {
	  babelHelpers.inherits(Dropdown, _BX$UI$Dropdown);

	  function Dropdown(options) {
	    var _this2;

	    babelHelpers.classCallCheck(this, Dropdown);
	    _this2 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Dropdown).call(this, options));
	    _this2.feedbackFormParams = BX.prop.getObject(options, "feedbackFormParams", {});
	    _this2.canAddRequisite = BX.prop.getBoolean(options, "canAddRequisite", false);
	    return _this2;
	  }

	  babelHelpers.createClass(Dropdown, [{
	    key: "isTargetElementChanged",
	    value: function isTargetElementChanged() {
	      return false;
	    }
	  }, {
	    key: "getItemsListContainer",
	    value: function getItemsListContainer() {
	      if (!this.itemListContainer) {
	        this.itemListContainer = BX.create('div', {
	          attrs: {
	            className: 'ui-dropdown-container rq-dropdown-container'
	          }
	        });
	      }

	      return this.itemListContainer;
	    }
	  }, {
	    key: "setFeedbackFormParams",
	    value: function setFeedbackFormParams(feedbackFormParams) {
	      this.feedbackFormParams = main_core.Type.isPlainObject(feedbackFormParams) ? feedbackFormParams : {};
	    }
	  }, {
	    key: "setCanAddRequisite",
	    value: function setCanAddRequisite(canAdd) {
	      this.canAddRequisite = !!canAdd;
	    }
	  }, {
	    key: "setMinSearchStringLength",
	    value: function setMinSearchStringLength(length) {
	      this.minSearchStringLength = length;
	    }
	  }, {
	    key: "getPopupAlertContainer",
	    value: function getPopupAlertContainer() {
	      if (!this.popupAlertContainer) {
	        var items = [];
	        var feedbackAvailable = Object.keys(this.getFeedbackFormParams()).length > 0;

	        if (feedbackAvailable) {
	          var textParts = main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE').split('#ADVICE_NEW_SERVICE_LINK#');
	          var item = main_core.Tag.render(_templateObject5());

	          if (textParts[0] && textParts[0].length) {
	            main_core.Dom.append(document.createTextNode(textParts[0]), item);
	          }

	          main_core.Dom.append(main_core.Tag.render(_templateObject6(), this.showFeedbackForm.bind(this), main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE_LINK')), item);

	          if (textParts[1] && textParts[1].length) {
	            main_core.Dom.append(document.createTextNode(textParts[1]), item);
	          }

	          items.push(item);
	        }

	        if (this.canAddRequisite) {
	          items.push(main_core.Tag.render(_templateObject7(), this.onEmptyValueEvent.bind(this), this.onEmptyValueEvent.bind(this), BX.prop.getString(this.messages, "creationLegend")));
	        }

	        this.popupAlertContainer = items.length ? main_core.Tag.render(_templateObject8(), items) : main_core.Tag.render(_templateObject9());
	      }

	      this.togglePopupAlertVisibility();
	      return this.popupAlertContainer;
	    }
	  }, {
	    key: "togglePopupAlertVisibility",
	    value: function togglePopupAlertVisibility() {
	      if (main_core.Type.isDomNode(this.popupAlertContainer)) {
	        this.popupAlertContainer.style.display = this.getItems().length > 0 ? "none" : "";
	      }
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Dropdown.prototype), "setItems", this).call(this, items);
	      this.togglePopupAlertVisibility();
	    }
	  }, {
	    key: "getNewAlertContainer",
	    value: function getNewAlertContainer(items) {
	      return null;
	    }
	  }, {
	    key: "disableTargetElement",
	    value: function disableTargetElement() {// cancel original handler
	    }
	  }, {
	    key: "getFeedbackFormParams",
	    value: function getFeedbackFormParams() {
	      return this.feedbackFormParams;
	    }
	  }, {
	    key: "showFeedbackForm",
	    value: function showFeedbackForm(event) {
	      event.preventDefault();
	      this.getPopupWindow().close();

	      if (!this._feedbackForm) {
	        this._feedbackForm = new BX.UI.Feedback.Form(this.getFeedbackFormParams());
	      }

	      this._feedbackForm.openPanel();
	    }
	  }]);
	  return Dropdown;
	}(BX.UI.Dropdown);

	exports.RequisiteAutocompleteField = RequisiteAutocompleteField;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event));
//# sourceMappingURL=autocomplete.bundle.js.map
