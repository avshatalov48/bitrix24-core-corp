/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,main_core_events,ui_designTokens,crm_placement_detailsearch,ui_dialogs_messagebox) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10;
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
	    _this.currentSearchQueryText = "";
	    _this.detailAutocompletePlacement = null;
	    _this.entitySearchPopupCloseHandler = null;
	    _this.placementsParamsHandler = null;
	    _this.searchQueryInputHandler = null;
	    _this.beforeAddPlacementItemsHandler = null;
	    _this.placementSearchParamsHandler = null;
	    _this.placementSetFoundItemsHandler = null;
	    _this.placementEntitySelectHandler = null;
	    _this.externalSearchHandler = null;
	    _this.externalSearchResultHandlerList = null;
	    _this.beforeEntitySearchPopupCloseHandler = null;
	    _this.onDocumentClickConfirm = null;
	    _this.creatingItem = null;
	    _this.clientResolverPlacementParams = null;
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
	      this.clientResolverPlacementParams = this.filterclientResolverPlacementParams(BX.prop.getObject(this._settings, "clientResolverPlacementParams", null));
	      this.externalSearchHandler = this.onExternalSearch.bind(this);
	      this.externalSearchResultHandlerList = new Map();
	      this.doInitialize();
	    }
	  }, {
	    key: "filterclientResolverPlacementParams",
	    value: function filterclientResolverPlacementParams(params) {
	      if (!main_core.Type.isObject(params)) {
	        return null;
	      }
	      return {
	        isPlacement: BX.prop.getBoolean(params, "isPlacement", false),
	        placementCode: BX.prop.getString(params, "placementCode", ""),
	        numberOfPlacements: BX.prop.getInteger(params, "numberOfPlacements", 0),
	        countryId: BX.prop.getInteger(params, "countryId", 0),
	        defaultAppInfo: BX.prop.getObject(params, "defaultAppInfo", {})
	      };
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
	      this._domNodes.requisiteClearButton = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-clear\" onclick=\"", "\"></button>"])), this.onSearchStringClear.bind(this));
	      this._domNodes.requisiteSearchButton = main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<button class=\"ui-ctl-after ui-ctl-icon-search\" onclick=\"", "\"></button>"])), this.onSearchButtonClick.bind(this));
	      var placeholder = this._placeholderText;
	      this._domNodes.requisiteSearchString = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<input type=\"text\" placeholder=\"", "\" class=\"ui-ctl-element ui-ctl-textbox\" />"])), main_core.Text.encode(placeholder));
	      if (!this._isPermitted) {
	        this._domNodes.requisiteSearchString.setAttribute('onclick', this._featureRestrictionCallback);
	      }
	      this.refreshLayout();
	      main_core.Dom.append(main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon\">\n\t\t\t", "\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"])), this._domNodes.requisiteSearchButton, this._domNodes.requisiteClearButton, this._domNodes.requisiteSearchString), container);
	      this.initDropdown();
	      this.refreshLayout();
	    }
	  }, {
	    key: "initDropdown",
	    value: function initDropdown() {
	      if (!this._dropdown) {
	        var isPlacement = BX.prop.getBoolean(this.clientResolverPlacementParams, "isPlacement", false);
	        this._dropdown = new Dropdown({
	          isDisabled: !this._isPermitted,
	          searchAction: BX.prop.getString(this._settings, "searchAction", ""),
	          externalSearchHandler: isPlacement ? this.externalSearchHandler : null,
	          items: [],
	          enableCreation: true,
	          enableCreationOnBlur: true,
	          autocompleteDelay: 1500,
	          messages: {
	            creationLegend: main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADD_REQUISITE'),
	            notFound: main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_NOT_FOUND')
	          },
	          placementParams: this.clientResolverPlacementParams
	        });
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSelect', this.onEntitySelect.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onAdd', this.onEntityAdd.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onReset', this.onEntityReset.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onBeforeSearchStart', this.onEntitySearchStart.bind(this));
	        main_core_events.EventEmitter.subscribe(this._dropdown, 'BX.UI.Dropdown:onSearchComplete', this.onEntitySearchComplete.bind(this));
	        BX.addCustomEvent(this._dropdown, 'Dropdown:onGetPopupAlertContainer', this.onGetPopupAlertContainer.bind(this));
	        BX.addCustomEvent(this._dropdown, 'Dropdown:onAfterInstallDefaultApp', this.onAfterInstallDefaultApp.bind(this));
	      }
	      this._dropdown.searchOptions = this._context;
	      this._dropdown.targetElement = this._domNodes.requisiteSearchString;
	      this._dropdown.init();
	      this.setEnabled(this._isEnabled);
	      this.setShowFeedbackLink(this._showFeedbackLink);
	    }
	  }, {
	    key: "getDropdownPopup",
	    value: function getDropdownPopup() {
	      var tryCreate = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      if (tryCreate) {
	        return this._dropdown.getPopupWindow();
	      }
	      return this._dropdown.popupWindow;
	    }
	  }, {
	    key: "closeDropdownPopup",
	    value: function closeDropdownPopup() {
	      var dropownPopup = this.getDropdownPopup();
	      if (dropownPopup) {
	        dropownPopup.close();
	      }
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
	      this.onSearchQueryInput();
	      this.setLoading(false);
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        currentItem: this._currentItem,
	        searchQuery: main_core.Type.isDomNode(this._domNodes.requisiteSearchString) ? this._domNodes.requisiteSearchString.value : null,
	        items: main_core.Type.isObject(this._dropdown) ? this._dropdown.getItems() : []
	      };
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
	        this.onSearchQueryInput();
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
	        this._domNodes.requisiteSearchString.placeholder = this._placeholderText;
	      }
	    }
	  }, {
	    key: "setClientResolverPlacementParams",
	    value: function setClientResolverPlacementParams(params) {
	      this.clientResolverPlacementParams = this.filterclientResolverPlacementParams(params);
	      if (this._dropdown) {
	        this._dropdown.setPlacementParams(this.clientResolverPlacementParams);
	        var isPlacement = BX.prop.getBoolean(this.clientResolverPlacementParams, "isPlacement", false);
	        if (isPlacement) {
	          this._dropdown.setExternalSearchHandler(this.externalSearchHandler);
	        }
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
	    key: "getPlacementCode",
	    value: function getPlacementCode() {
	      var placementCode = BX.prop.getString(this.clientResolverPlacementParams, "placementCode", "");
	      return placementCode === "" ? "CRM_REQUISITE_AUTOCOMPLETE" : placementCode;
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
	      if (dropdown === this._dropdown && selected["appSid"] && !selected["created"]) {
	        if (!this.creatingItem) {
	          this.creatingItem = selected;
	          selected._loader = new BX.Loader({
	            target: selected.node,
	            size: 40
	          });
	          selected.node.classList.add('client-editor-active');
	          selected.node.parentNode.classList.add('client-editor-inactive');
	          selected._loader.show();
	          BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:pick", [{
	            appSid: selected["appSid"],
	            data: selected
	          }]);
	        }
	        return;
	      }
	      this.selectEntity(selected);
	    }
	  }, {
	    key: "selectEntity",
	    value: function selectEntity(selected) {
	      if (this.creatingItem) {
	        for (var prop in selected) {
	          if (selected.hasOwnProperty(prop)) {
	            this.creatingItem[prop] = selected[prop];
	          }
	        }
	        this.creatingItem["created"] = true;
	        this.creatingItem = null;
	        this._dropdown.setItems([]);
	      }
	      if (this.onDocumentClickConfirm) {
	        this.onDocumentClickConfirm.close();
	        this.onDocumentClickConfirm = null;
	      }
	      this.closeDropdownPopup();
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
	    key: "onExternalSearch",
	    value: function onExternalSearch() {
	      var _this2 = this;
	      return new Promise(function (resolve) {
	        if (_this2.detailAutocompletePlacement) {
	          var params = {
	            appId: 0
	          };
	          BX.onCustomEvent(_this2.detailAutocompletePlacement, "Placements:startDefaultSearch", [params]);
	          if (main_core.Type.isInteger(params["appId"]) && params["appId"] > 0) {
	            if (!_this2.externalSearchResultHandlerList.has(params["appId"])) {
	              _this2.externalSearchResultHandlerList.set(params["appId"], function (result) {
	                resolve(result);
	              });
	            }
	            return;
	          }
	        }
	        resolve([]);
	      });
	    }
	  }, {
	    key: "initPlacement",
	    value: function initPlacement(searchControl, container) {
	      if (!this.detailAutocompletePlacement) {
	        var isAutocompletePlacementEnabled = main_core.Type.isPlainObject(this.clientResolverPlacementParams) && this.clientResolverPlacementParams.hasOwnProperty("numberOfPlacements") && main_core.Type.isNumber(this.clientResolverPlacementParams["numberOfPlacements"]) && this.clientResolverPlacementParams["numberOfPlacements"] > 0;
	        if (isAutocompletePlacementEnabled) {
	          this.detailAutocompletePlacement = new crm_placement_detailsearch.DetailSearch(this.getPlacementCode());
	        }
	        if (this.detailAutocompletePlacement) {
	          var dropdownPopup = this.getDropdownPopup(true);
	          if (dropdownPopup) {
	            this.beforeEntitySearchPopupCloseHandler = this.onBeforeEntitySearchPopupClose.bind(this, dropdownPopup._tryCloseByEvent.bind(dropdownPopup));
	            dropdownPopup._tryCloseByEvent = this.beforeEntitySearchPopupCloseHandler;
	            this.entitySearchPopupCloseHandler = this.onEntitySearchPopupClose.bind(this);
	            BX.addCustomEvent(dropdownPopup, 'onPopupClose', this.entitySearchPopupCloseHandler);
	            dropdownPopup.show();
	          }
	          this.placementsParamsHandler = this.onPlacementsParams.bind(this);
	          BX.addCustomEvent(this.detailAutocompletePlacement, "Placements:params", this.placementsParamsHandler);
	          this.beforeAddPlacementItemsHandler = this.onBeforeAppendPlacementItems.bind(this);
	          BX.addCustomEvent(this.detailAutocompletePlacement, "Placements:beforeAppendItems", this.beforeAddPlacementItemsHandler);
	          this.placementSearchParamsHandler = this.onPlacamentSearchParams.bind(this);
	          BX.addCustomEvent(this.detailAutocompletePlacement, "Placements:searchParams", this.placementSearchParamsHandler);
	          this.placementSetFoundItemsHandler = this.onPlacementSetFoundItems.bind(this);
	          BX.addCustomEvent(this.detailAutocompletePlacement, "Placements:setFoundItems", this.placementSetFoundItemsHandler);
	          this.placementEntitySelectHandler = this.onPlacementEntitySelect.bind(this);
	          BX.addCustomEvent(this.detailAutocompletePlacement, "Placements:select", this.placementEntitySelectHandler);
	          this.detailAutocompletePlacement.show(container, container.querySelector('div.crm-rq-popup-item-add-new'), {
	            hideLoader: true
	          });
	        }
	        if (main_core.Type.isDomNode(this._domNodes.requisiteSearchString)) {
	          if (!this.searchQueryInputHandler) {
	            this.searchQueryInputHandler = this.onSearchQueryInput.bind(this);
	          }
	          this._domNodes.requisiteSearchString.addEventListener("input", this.searchQueryInputHandler);
	          this._domNodes.requisiteSearchString.addEventListener("keyup", this.searchQueryInputHandler);
	        }
	      }
	    }
	  }, {
	    key: "onSearchQueryInput",
	    value: function onSearchQueryInput() {
	      if (this._domNodes.requisiteSearchString.value !== this.currentSearchQueryText) {
	        this.currentSearchQueryText = this._domNodes.requisiteSearchString.value;
	        this.fireChangeSearchQueryEvent();
	      }
	    }
	  }, {
	    key: "fireChangeSearchQueryEvent",
	    value: function fireChangeSearchQueryEvent() {
	      if (this.detailAutocompletePlacement && main_core.Type.isDomNode(this._domNodes.requisiteSearchString)) {
	        BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:changeQuery", [{
	          currentSearchQuery: this._domNodes.requisiteSearchString.value
	        }]);
	      }
	    }
	  }, {
	    key: "onEntitySearchComplete",
	    value: function onEntitySearchComplete() {
	      this.setLoading(false);
	    } /*dropdown, items*/
	  }, {
	    key: "onGetPopupAlertContainer",
	    value: function onGetPopupAlertContainer(searchControl, container) {
	      this.initPlacement(searchControl, container);
	    }
	  }, {
	    key: "onAfterInstallDefaultApp",
	    value: function onAfterInstallDefaultApp(dropdown) {
	      this.emit("onInstallDefaultApp");
	    }
	  }, {
	    key: "onBeforeEntitySearchPopupClose",
	    value: function onBeforeEntitySearchPopupClose(originalHandler, event) {
	      if (this.onDocumentClickConfirm) {
	        return BX.eventReturnFalse(event);
	      }
	      var eventResult = {
	        active: false
	      };
	      BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:active", [eventResult]);
	      if (eventResult.active) {
	        BX.unbind(document, 'click', this._dropdown.documentClickHandler);
	        var f = function (messageBox, e) {
	          BX.bind(document, 'click', this._dropdown.documentClickHandler);
	          messageBox.close();
	          this.onDocumentClickConfirm = null;
	          BX.eventCancelBubble(e);
	        }.bind(this);
	        this.onDocumentClickConfirm = ui_dialogs_messagebox.MessageBox.create({
	          message: BX.message('CRM_EDITOR_PLACEMENT_CAUTION') || 'Dow you want to terminate process?',
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	          modal: true,
	          onOk: function (messageBox, button, e) {
	            f(messageBox, e);
	            this._dropdown.documentClickHandler(e);
	            originalHandler(e);
	          }.bind(this),
	          onCancel: function onCancel(messageBox, button, e) {
	            f(messageBox, e);
	          }
	        });
	        BX.eventCancelBubble(event);
	        this.onDocumentClickConfirm.show();
	        return BX.eventReturnFalse(event);
	      }
	      originalHandler(event);
	    }
	  }, {
	    key: "onEntitySearchPopupClose",
	    value: function onEntitySearchPopupClose() {
	      this.destroyPlacement();
	    }
	  }, {
	    key: "destroyPlacement",
	    value: function destroyPlacement() {
	      if (this.searchQueryInputHandler) {
	        this._domNodes.requisiteSearchString.removeEventListener("input", this.searchQueryInputHandler);
	        this._domNodes.requisiteSearchString.removeEventListener("keyup", this.searchQueryInputHandler);
	        this.searchQueryInputHandler = null;
	      }
	      if (this._dropdown && this._dropdown.hasOwnProperty("documentClickHandler")) {
	        BX.unbind(document, 'click', this._dropdown.documentClickHandler);
	      }
	      if (this.detailAutocompletePlacement) {
	        var dropdownPopup = this.getDropdownPopup();
	        if (dropdownPopup) {
	          BX.removeCustomEvent(dropdownPopup, "onPopupClose", this.entitySearchPopupCloseHandler);
	        }
	        this.entitySearchPopupCloseHandler = null;
	        BX.onCustomEvent(this.detailAutocompletePlacement, "Placements:destroy");
	        BX.removeCustomEvent(this.detailAutocompletePlacement, "Placements:params", this.placementsParamsHandler);
	        this.placementsParamsHandler = null;
	        BX.removeCustomEvent(this.detailAutocompletePlacement, "Placements:beforeAppendItems", this.beforeAddPlacementItemsHandler);
	        this.beforeAddPlacementItemsHandler = null;
	        BX.removeCustomEvent(this.detailAutocompletePlacement, "Placements:searchParams", this.placementSearchParamsHandler);
	        this.placementSearchParamsHandler = null;
	        BX.removeCustomEvent(this.detailAutocompletePlacement, "Placements:setFoundItems", this.placementSetFoundItemsHandler);
	        this.placementSetFoundItemsHandler = null;
	        BX.removeCustomEvent(this.detailAutocompletePlacement, "Placements:select", this.placementEntitySelectHandler);
	        this.placementEntitySelectHandler = null;
	        this.detailAutocompletePlacement = null;
	        this.creatingItem = null;
	        this._dropdown.setItems([]);
	      }
	    }
	  }, {
	    key: "onPlacementsParams",
	    value: function onPlacementsParams(params) {
	      if (main_core.Type.isObject(params)) {
	        if (this._dropdown) {
	          this._dropdown.setPlacementParams(this.clientResolverPlacementParams);
	        }
	        if (this.clientResolverPlacementParams) {
	          if (params.hasOwnProperty("placementParams")) {
	            params["placementParams"] = this.clientResolverPlacementParams;
	          }
	          if (params.hasOwnProperty("currentSearchQuery")) {
	            params["currentSearchQuery"] = this._domNodes.requisiteSearchString.value;
	          }
	        }
	      }
	    }
	  }, {
	    key: "onBeforeAppendPlacementItems",
	    value: function onBeforeAppendPlacementItems() {
	      var dropdownPopup = this.getDropdownPopup();
	      if (dropdownPopup) {
	        BX.addClass(dropdownPopup.popupContainer, "client-editor-popup");
	      }
	    }
	  }, {
	    key: "onPlacamentSearchParams",
	    value: function onPlacamentSearchParams(params) {
	      params["searchQuery"] = this._domNodes.requisiteSearchString.value;
	    }
	  }, {
	    key: "onPlacementSetFoundItems",
	    value: function onPlacementSetFoundItems(placementItem, results) {
	      var items = [];
	      results.forEach(function (result) {
	        items.push({
	          id: result.id,
	          title: result.name,
	          appSid: placementItem["appSid"],
	          module: 'crm',
	          subModule: 'rest',
	          subTitle: placementItem["title"],
	          attributes: {
	            phone: result.phone ? [{
	              value: result.phone
	            }] : '',
	            email: result.email ? [{
	              value: result.email
	            }] : '',
	            web: result.web ? [{
	              value: result.web
	            }] : ''
	          }
	        });
	      }.bind(this));
	      var appId = parseInt(placementItem["placementInfo"]["id"]);
	      if (appId > 0 && this.externalSearchResultHandlerList.has(appId)) {
	        this.externalSearchResultHandlerList.get(appId)(items);
	        this.externalSearchResultHandlerList["delete"](appId);
	      } else {
	        this._dropdown.setItems(items);
	      }
	    }
	  }, {
	    key: "onPlacementEntitySelect",
	    value: function onPlacementEntitySelect(data) {
	      var entityData = {
	        type: data["entityType"],
	        id: data["id"],
	        title: data["title"],
	        fields: data["fields"]
	      };
	      if (main_core.Type.isPlainObject(entityData["fields"]) && entityData["fields"].hasOwnProperty("RQ_ADDR") && main_core.Type.isPlainObject(entityData["fields"]["RQ_ADDR"])) {
	        var responseHandler = function (response) {
	          var status = BX.prop.getString(response, "status", "");
	          var data = BX.prop.getObject(response, "data", {});
	          var messages = [];
	          if (status === "error") {
	            var errors = BX.prop.getArray(response, "errors", []);
	            for (var i = 0; i < errors.length; i++) {
	              messages.push(BX.prop.getString(errors[i], "message"));
	            }
	          }
	          if (messages.length > 0) {
	            BX.UI.Notification.Center.notify({
	              content: messages.join("<br>"),
	              position: "top-center",
	              autoHideDelay: 10000
	            });
	            delete entityData["fields"]["RQ_ADDR"];
	          } else {
	            entityData["fields"]["RQ_ADDR"] = data;
	          }
	          this.selectEntity(entityData);
	        }.bind(this);
	        BX.ajax.runAction('crm.requisite.address.getLocationAddressJsonByFields', {
	          data: {
	            addresses: entityData["fields"]["RQ_ADDR"]
	          }
	        }).then(responseHandler, responseHandler);
	      } else {
	        this.selectEntity(entityData);
	      }
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
	    var _this3;
	    babelHelpers.classCallCheck(this, Dropdown);
	    _this3 = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Dropdown).call(this, options));
	    _this3.feedbackFormParams = BX.prop.getObject(options, "feedbackFormParams", {});
	    _this3.canAddRequisite = BX.prop.getBoolean(options, "canAddRequisite", false);
	    _this3.externalSearchHandler = BX.prop.getFunction(options, "externalSearchHandler", null);
	    _this3.placementParams = BX.prop.getObject(options, "placementParams", {});
	    _this3.installDefaultAppHandler = _this3.onClickInstallDefaultApp.bind(babelHelpers.assertThisInitialized(_this3));
	    _this3.installDefaultAppTimeout = 7000;
	    _this3.afterInstallDefaultAppHandler = _this3.onAfterInstallDefaultApp.bind(babelHelpers.assertThisInitialized(_this3));
	    _this3.popupAlertContainer = null;
	    _this3.defaultAppInstallLoader = null;
	    _this3.isDefaultAppInstalled = false;
	    return _this3;
	  }
	  babelHelpers.createClass(Dropdown, [{
	    key: "setPlacementParams",
	    value: function setPlacementParams(params) {
	      this.placementParams = params;
	    }
	  }, {
	    key: "setExternalSearchHandler",
	    value: function setExternalSearchHandler(handler) {
	      this.externalSearchHandler = handler;
	    }
	  }, {
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
	    key: "getDefaultAppInfo",
	    value: function getDefaultAppInfo() {
	      return BX.prop.getObject(this.placementParams, "defaultAppInfo", {});
	    }
	  }, {
	    key: "isDefaultAppCanInstall",
	    value: function isDefaultAppCanInstall() {
	      var defaultAppInfo = this.getDefaultAppInfo();
	      return main_core.Type.isStringFilled(BX.prop.get(defaultAppInfo, "code", "")) && main_core.Type.isStringFilled(BX.prop.get(defaultAppInfo, "title", "")) && BX.prop.get(defaultAppInfo, "isAvailable", "N") === "Y"
	      /*&& BX.prop.get(defaultAppInfo, "isInstalled", "N") !== "Y"*/;
	    }
	  }, {
	    key: "getPopupAlertContainer",
	    value: function getPopupAlertContainer() {
	      if (!this.popupAlertContainer) {
	        var items = [];
	        if (this.isDefaultAppCanInstall()) {
	          var appTitleText = main_core.Text.encode(this.getDefaultAppInfo()["title"]);
	          var appInstallText = main_core.Text.encode(main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_INSTALL'));
	          items.push(main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-rq-popup-item crm-rq-popup-item-add-new\">\n\t\t\t\t\t\t<button\n\t\t\t\t\t\t\tclass=\"crm-rq-popup-item-add-inst-app-btn\"><span></span><span\n\t\t\t\t\t\t\t\tclass=\"crm-rq-popup-item-add-new-btn-text\"><span>", "</span><span\n\t\t\t\t\t\t\t\tstyle=\"margin-left: 20px; width: 100px;\"><a\n\t\t\t\t\t\t\t\t\thref=\"#\"\n\t\t\t\t\t\t\t\t\tonclick=\"", "\">", "</a>\n\t\t\t\t\t\t\t</span></span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>"])), appTitleText, this.installDefaultAppHandler, appInstallText));
	        }
	        var feedbackAvailable = Object.keys(this.getFeedbackFormParams()).length > 0;
	        if (feedbackAvailable) {
	          var textParts = main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE').split('#ADVICE_NEW_SERVICE_LINK#');
	          var item = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-rq-popup-item crm-rq-popup-item-helper\"></div>"])));
	          if (textParts[0] && textParts[0].length) {
	            main_core.Dom.append(document.createTextNode(textParts[0]), item);
	          }
	          var newServiceLinkText = main_core.Loc.getMessage('REQUISITE_AUTOCOMPLETE_ADVICE_NEW_SERVICE_LINK');
	          main_core.Dom.append(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["<a href=\"\" onclick=\"", "\">", "</a>"])), this.showFeedbackForm.bind(this), newServiceLinkText), item);
	          if (textParts[1] && textParts[1].length) {
	            main_core.Dom.append(document.createTextNode(textParts[1]), item);
	          }
	          items.push(item);
	        }
	        if (this.canAddRequisite) {
	          items.push(main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-rq-popup-item crm-rq-popup-item-add-new\">\n\t\t\t\t\t\t<button class=\"crm-rq-popup-item-add-new-btn\">\n\t\t\t\t\t\t\t<span class=\"ui-btn crm-rq-btn ui-btn-icon-custom ui-btn-primary ui-btn-round\"\n\t\t\t\t\t\t\t\tonclick=\"", "\"></span>\n\t\t\t\t\t\t\t<span class=\"crm-rq-popup-item-add-new-btn-text\"\n\t\t\t\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\t\t\t\t>", "</span>\n\t\t\t\t\t\t</button>\n\t\t\t\t\t</div>"])), this.onEmptyValueEvent.bind(this), this.onEmptyValueEvent.bind(this), BX.prop.getString(this.messages, "creationLegend")));
	        }
	        this.popupAlertContainer = items.length ? main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-rq-popup-wrapper\">\n\t\t\t\t\t<div class=\"crm-rq-popup-items-list\">", "</div>\n\t\t\t\t</div>\n\t\t\t"])), items) : main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["<div></div>"])));
	        BX.onCustomEvent(this, "Dropdown:onGetPopupAlertContainer", [this, this.popupAlertContainer]);
	      }
	      this.togglePopupAlertVisibility();
	      return this.popupAlertContainer;
	    }
	  }, {
	    key: "getTargetElementValue",
	    value: function getTargetElementValue() {
	      if (this.targetElement !== "undefined" && main_core.Type.isStringFilled(this.targetElement["value"])) {
	        return this.targetElement["value"].trim();
	      }
	      return "";
	    }
	  }, {
	    key: "togglePopupAlertVisibility",
	    value: function togglePopupAlertVisibility() {
	      if (main_core.Type.isDomNode(this.popupAlertContainer)) {
	        var numberOfPlacements = BX.prop.getInteger(this.placementParams, "numberOfPlacements", 0);
	        var isVisible = numberOfPlacements > 0 || this.getItems().length <= 0;
	        this.popupAlertContainer.style.display = isVisible ? "" : "none";
	      }
	    }
	  }, {
	    key: "setItems",
	    value: function setItems(items) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Dropdown.prototype), "setItems", this).call(this, [{
	        id: 1,
	        name: "N",
	        title: "T"
	      }]);
	      this.togglePopupAlertVisibility();
	      babelHelpers.get(babelHelpers.getPrototypeOf(Dropdown.prototype), "setItems", this).call(this, items);
	    }
	  }, {
	    key: "getNewAlertContainer",
	    value: function getNewAlertContainer() {
	      return null;
	    } /*items*/
	  }, {
	    key: "disableTargetElement",
	    value: function disableTargetElement() {
	      // cancel original handler
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
	  }, {
	    key: "searchItemsByStr",
	    value: function searchItemsByStr(target) {
	      if (this.externalSearchHandler) {
	        return this.externalSearchHandler().then(function (result) {
	          return result;
	        });
	      }
	      return babelHelpers.get(babelHelpers.getPrototypeOf(Dropdown.prototype), "searchItemsByStr", this).call(this, target);
	    }
	  }, {
	    key: "onClickInstallDefaultApp",
	    value: function onClickInstallDefaultApp(event) {
	      var _this4 = this;
	      event.stopPropagation();
	      event.preventDefault();
	      if (this.defaultAppInstallLoader) {
	        return;
	      }
	      if (main_core.Type.isDomNode(event.target) && main_core.Type.isDomNode(event.target.parentNode)) {
	        var parent = event.target.parentNode;
	        main_core.Dom.hide(event.target);
	        this.defaultAppInstallLoader = this.defaultAppInstallLoader || new BX.Loader({
	          target: parent,
	          size: 30,
	          offset: {
	            top: "-23px"
	          }
	        });
	        this.defaultAppInstallLoader.show();
	      }
	      if (this.isDefaultAppCanInstall()) {
	        this.isDefaultAppInstalled = false;
	        BX.loadExt('marketplace').then(function () {
	          setTimeout(function () {
	            if (!_this4.isDefaultAppInstalled) {
	              _this4.finalInstallDefaultApp();
	            }
	          }, _this4.installDefaultAppTimeout);
	          top.BX.addCustomEvent(top, "Rest:AppLayout:ApplicationInstall", _this4.afterInstallDefaultAppHandler);
	          BX.rest.Marketplace.install({
	            CODE: _this4.placementParams["defaultAppInfo"]["code"],
	            SILENT_INSTALL: "Y",
	            DO_NOTHING: "Y"
	          });
	        })["catch"](function () {
	          top.BX.removeCustomEvent(top, "Rest:AppLayout:ApplicationInstall", _this4.afterInstallDefaultAppHandler);
	        });
	      }
	    }
	  }, {
	    key: "wait",
	    value: function wait(ms) {
	      return new Promise(function (resolve) {
	        return setTimeout(resolve, parseInt(ms));
	      });
	    }
	  }, {
	    key: "checkDefAppHandler",
	    value: function checkDefAppHandler() {
	      var countryId = BX.prop.getInteger(this.placementParams, "countryId", 0);
	      return new Promise(function (resolve, reject) {
	        BX.ajax.runAction('crm.requisite.autocomplete.checkDefaultAppHandler', {
	          data: {
	            countryId: countryId
	          }
	        }).then(function (data) {
	          if (main_core.Type.isPlainObject(data) && data.hasOwnProperty("data") && data.hasOwnProperty("status") && data["status"] === "success" && main_core.Type.isBoolean(data["data"]) && data["data"]) {
	            resolve();
	          } else {
	            reject();
	          }
	        });
	      });
	    }
	  }, {
	    key: "awaitHandler",
	    value: function awaitHandler(context) {
	      var _this5 = this;
	      this.wait(context.waitTime).then(function () {
	        _this5.checkDefAppHandler().then(function () {
	          _this5.finalInstallDefaultAppSuccess();
	        })["catch"](function () {
	          if (--context.numberOfTimes > 0) {
	            _this5.awaitHandler(context);
	          } else {
	            _this5.finalInstallDefaultApp();
	          }
	        });
	      });
	    }
	  }, {
	    key: "onAfterInstallDefaultApp",
	    value: function onAfterInstallDefaultApp(installed, eventResult) {
	      this.isDefaultAppInstalled = true;
	      top.BX.removeCustomEvent(top, "Rest:AppLayout:ApplicationInstall", this.afterInstallDefaultAppHandler);
	      var numberOfTimes = 3;
	      var waitTime = Math.floor(this.installDefaultAppTimeout / numberOfTimes);
	      if (!!installed) {
	        this.awaitHandler({
	          waitTime: waitTime,
	          numberOfTimes: 3
	        });
	      } else {
	        this.finalInstallDefaultApp();
	      }
	    }
	  }, {
	    key: "finalInstallDefaultAppSuccess",
	    value: function finalInstallDefaultAppSuccess() {
	      BX.onCustomEvent(this, "Dropdown:onAfterInstallDefaultApp", [this]);
	      this.finalInstallDefaultApp();
	    }
	  }, {
	    key: "finalInstallDefaultApp",
	    value: function finalInstallDefaultApp() {
	      if (this.defaultAppInstallLoader) {
	        this.defaultAppInstallLoader.destroy();
	        this.defaultAppInstallLoader = null;
	      }
	      if (this.popupWindow) {
	        this.popupWindow.close();
	      }
	    }
	  }]);
	  return Dropdown;
	}(BX.UI.Dropdown);

	exports.RequisiteAutocompleteField = RequisiteAutocompleteField;

}((this.BX.Crm = this.BX.Crm || {}),BX,BX.Event,BX,BX.Crm.Placement,BX.UI.Dialogs));
//# sourceMappingURL=autocomplete.bundle.js.map
