(function (exports,main_core,main_core_events,main_popup,ui_dialogs_messagebox) {
	'use strict';

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"main-grid-delete-button\" \n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t></span>\n\t\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<li data-role=\"createItem\"\n\t\t\t\t\t\t class=\"catalog-productcard-popup-select-item catalog-productcard-popup-select-item-new\"\n\t\t\t\t\t\t onclick=\"BX.Catalog.VariationGrid.firePropertyModification(", ")\">\n\t\t\t\t\t\t<label class=\"catalog-productcard-popup-select-label\">\n\t\t\t\t\t\t\t<span class=\"catalog-productcard-popup-select-add\"></span>\n\t\t\t\t\t\t\t<span class=\"catalog-productcard-popup-select-text\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t</label>\n\t\t\t\t\t</li>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"catalog-productcard-popup-select-item catalog-productcard-popup-multi-select-item-new\">\n\t\t\t\t<label \n\t\t\t\t\tclass=\"catalog-productcard-popup-select-label main-dropdown-item\">\n\t\t\t\t\t<span class=\"catalog-productcard-popup-select-add\"></span>\n\t\t\t\t\t<span class=\"catalog-productcard-popup-select-text\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</span>\n\t\t\t\t</label>\n\t\t\t</div>\n\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var GRID_TEMPLATE_ROW = 'template_0';

	var VariationGrid = /*#__PURE__*/function () {
	  function VariationGrid() {
	    var settings = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	    babelHelpers.classCallCheck(this, VariationGrid);
	    babelHelpers.defineProperty(this, "grid", null);
	    babelHelpers.defineProperty(this, "isNew", false);
	    babelHelpers.defineProperty(this, "propertiesWithMenu", []);
	    this.createPropertyId = settings.createPropertyId;
	    this.createPropertyHintId = settings.createPropertyHintId;
	    this.gridId = settings.gridId;
	    this.isNew = settings.isNew;
	    this.hiddenProperties = settings.hiddenProperties;
	    this.modifyPropertyLink = settings.modifyPropertyLink;
	    this.gridEditData = settings.gridEditData;
	    this.canHaveSku = settings.canHaveSku || false;

	    if (settings.copyItemsMap) {
	      this.getGrid().arParams.COPY_ITEMS_MAP = settings.copyItemsMap;
	    }

	    var isGridReload = settings.isGridReload || false;

	    if (!isGridReload) {
	      this.addCustomClassToGrid();
	      this.bindCreateNewVariation();
	      this.bindCreateSkuProperty();
	      this.clearGridSettingsPopupStuff();
	    }

	    var gridEditData = settings.gridEditData || null;

	    if (gridEditData) {
	      this.setGridEditData(gridEditData);
	    }

	    if (this.isNew) {
	      this.enableEdit();
	      this.prepareNewNodes();
	      this.getGrid().updateCounterSelected();
	      this.getGrid().disableCheckAllCheckboxes();
	    } else {
	      this.bindInlineEdit();
	    }

	    main_core.Event.bind(this.getGrid().getScrollContainer(), 'scroll', main_core.Runtime.throttle(this.onScrollHandler.bind(this), 50));
	    main_core.Event.bind(this.getGridSettingsButton(), 'click', this.showGridSettingsWindowHandler.bind(this));
	    this.subscribeCustomEvents();
	  }

	  babelHelpers.createClass(VariationGrid, [{
	    key: "subscribeCustomEvents",
	    value: function subscribeCustomEvents() {
	      this.onGridUpdatedHandler = this.onGridUpdated.bind(this);
	      main_core_events.EventEmitter.subscribe('Grid::updated', this.onGridUpdatedHandler);
	      this.onPropertySaveHandler = this.onPropertySave.bind(this);
	      main_core_events.EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onPropertySaveHandler);
	      this.onAllRowsSelectHandler = this.enableEdit.bind(this);
	      main_core_events.EventEmitter.subscribe('Grid::allRowsSelected', this.onAllRowsSelectHandler);
	      this.onAllRowsUnselectHandler = this.disableEdit.bind(this);
	      main_core_events.EventEmitter.subscribe('Grid::allRowsUnselected', this.onAllRowsUnselectHandler);
	      this.showPropertySettingsSliderHandler = this.showPropertySettingsSlider.bind(this);
	      main_core_events.EventEmitter.subscribe('VariationGrid::propertyModify', this.showPropertySettingsSliderHandler);
	      this.onPrepareDropDownItemsHandler = this.onPrepareDropDownItems.bind(this);
	      main_core_events.EventEmitter.subscribe('Dropdown::onPrepareItems', this.onPrepareDropDownItemsHandler);
	      this.onCreatePopupHandler = this.onCreatePopup.bind(this);
	      main_core_events.EventEmitter.subscribe('UiSelect::onCreatePopup', this.onCreatePopupHandler);
	    }
	  }, {
	    key: "unsubscribeCustomEvents",
	    value: function unsubscribeCustomEvents() {
	      if (this.onGridUpdatedHandler) {
	        main_core_events.EventEmitter.unsubscribe('Grid::updated', this.onGridUpdatedHandler);
	        this.onGridUpdatedHandler = null;
	      }

	      if (this.onPropertySaveHandler) {
	        main_core_events.EventEmitter.unsubscribe('SidePanel.Slider:onMessage', this.onPropertySaveHandler);
	        this.onPropertySaveHandler = null;
	      }

	      if (this.showPropertySettingsSliderHandler) {
	        main_core_events.EventEmitter.unsubscribe('VariationGrid::propertyModify', this.showPropertySettingsSliderHandler);
	        this.showPropertySettingsSliderHandler = null;
	      }

	      if (this.onPrepareDropDownItemsHandler) {
	        main_core_events.EventEmitter.unsubscribe('Dropdown::onPrepareItems', this.onPrepareDropDownItemsHandler);
	        this.onPrepareDropDownItemsHandler = null;
	      }

	      if (this.onAllRowsSelectHandler) {
	        main_core_events.EventEmitter.unsubscribe('Grid::allRowsSelected', this.onAllRowsSelectHandler);
	        this.onAllRowsSelectHandler = null;
	      }

	      if (this.onAllRowsUnselectHandler) {
	        main_core_events.EventEmitter.unsubscribe('Grid::allRowsUnselected', this.onAllRowsUnselectHandler);
	        this.onAllRowsUnselectHandler = null;
	      }

	      if (this.onCreatePopupHandler) {
	        main_core_events.EventEmitter.unsubscribe('UiSelect::onCreatePopup', this.onCreatePopupHandler);
	        this.onCreatePopupHandler = null;
	      }
	    }
	  }, {
	    key: "getGridSettingsButton",
	    value: function getGridSettingsButton() {
	      return this.getGrid().getContainer().querySelector('.' + this.getGrid().settings.get('classSettingsButton'));
	    }
	  }, {
	    key: "showGridSettingsWindowHandler",
	    value: function showGridSettingsWindowHandler(event) {
	      var _this = this;

	      event.preventDefault();
	      event.stopPropagation();
	      this.askToLossGridData(function () {
	        _this.getGrid().getSettingsWindow()._onSettingsButtonClick();
	      });
	    }
	  }, {
	    key: "onScrollHandler",
	    value: function onScrollHandler(event) {
	      var popup = main_popup.PopupManager.getCurrentPopup();

	      if (popup) {
	        popup.close();
	      }

	      this.propertiesWithMenu.forEach(function (propertyId) {
	        var menu = main_popup.MenuManager.getMenuById(propertyId + '_menu');

	        if (menu) {
	          menu.close();
	        }
	      });
	    }
	  }, {
	    key: "onPrepareDropDownItems",
	    value: function onPrepareDropDownItems(event) {
	      var _event$getData = event.getData(),
	          _event$getData2 = babelHelpers.slicedToArray(_event$getData, 3),
	          controlId = _event$getData2[0],
	          menuId = _event$getData2[1],
	          items = _event$getData2[2];

	      if (!main_core.Type.isStringFilled(controlId)) {
	        return;
	      }

	      this.propertiesWithMenu.push(controlId);

	      if (controlId.indexOf('SKU_GRID_PROPERTY_') === -1) {
	        return;
	      }

	      if (!main_core.Type.isArray(items)) {
	        return;
	      }

	      var actionList = items.filter(function (item) {
	        return item.action === 'create-new';
	      });

	      if (actionList.length > 0) {
	        return;
	      }

	      var propertyId = controlId.replace('SKU_GRID_PROPERTY_', '').replace('_control', '');
	      items.push({
	        'action': 'create-new',
	        'html': "\n\t\t\t\t<li data-role=\"createItem\" class=\"catalog-productcard-popup-select-item catalog-productcard-popup-select-item-new\">\n\t\t\t\t\t<label class=\"catalog-productcard-popup-select-label main-dropdown-item\" data-pseudo=\"true\">\n\t\t\t\t\t\t<span class=\"catalog-productcard-popup-select-add\"></span>\n\t\t\t\t\t\t<span class=\"catalog-productcard-popup-select-text\">\n\t\t\t\t\t\t\t".concat(main_core.Loc.getMessage('C_PVG_ADD_NEW_PROPERTY_VALUE_BUTTON'), "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</label>\n\t\t\t\t</li>"),
	        'onclick': function onclick() {
	          return BX.Catalog.VariationGrid.firePropertyModification(propertyId, menuId);
	        }
	      });
	      requestAnimationFrame(function () {
	        var popup = document.getElementById('menu-popup-' + menuId);
	        main_core.Dom.addClass(popup, 'catalog-productcard-popup-list');
	      });
	    }
	  }, {
	    key: "onCreatePopup",
	    value: function onCreatePopup(event) {
	      var _popup$bindElement;

	      var _event$getData3 = event.getData(),
	          _event$getData4 = babelHelpers.slicedToArray(_event$getData3, 1),
	          popup = _event$getData4[0];

	      var bindElementId = popup === null || popup === void 0 ? void 0 : (_popup$bindElement = popup.bindElement) === null || _popup$bindElement === void 0 ? void 0 : _popup$bindElement.id;

	      if (!main_core.Type.isStringFilled(bindElementId)) {
	        return;
	      }

	      if (bindElementId.indexOf('SKU_GRID_PROPERTY_') === -1) {
	        return;
	      }

	      var propertyId = bindElementId.replace('SKU_GRID_PROPERTY_', '').replace('_control', '');
	      var addButton = main_core.Tag.render(_templateObject(), main_core.Loc.getMessage('C_PVG_ADD_NEW_PROPERTY_VALUE_BUTTON'));
	      main_core.Event.bind(addButton, 'mousedown', BX.Catalog.VariationGrid.firePropertyModification.bind(this, propertyId));
	      popup.contentContainer.appendChild(addButton);
	    }
	  }, {
	    key: "clearGridSettingsPopupStuff",
	    value: function clearGridSettingsPopupStuff() {
	      main_core.Dom.remove(document.getElementById(this.gridId + '-grid-settings-window'));
	    }
	  }, {
	    key: "bindCreateNewVariation",
	    value: function bindCreateNewVariation() {
	      if (!this.canHaveSku) {
	        return;
	      }

	      var addRowButton = document.querySelector('[data-role="catalog-productcard-variation-add-row"]');

	      if (main_core.Type.isDomNode(addRowButton)) {
	        main_core.Event.bind(addRowButton, 'click', this.addRowToGrid.bind(this));
	      }
	    }
	  }, {
	    key: "addCustomClassToGrid",
	    value: function addCustomClassToGrid() {
	      main_core.Dom.addClass(this.getGrid().getContainer(), 'catalog-product-variation-grid');
	    }
	    /**
	     * @returns {BX.Main.grid|null}
	     */

	  }, {
	    key: "getGrid",
	    value: function getGrid() {
	      if (this.grid === null) {
	        if (!main_core.Reflection.getClass('BX.Main.gridManager.getInstanceById')) {
	          throw Error("Cannot find grid with '".concat(this.gridId, "' id."));
	        }

	        this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
	      }

	      return this.grid;
	    }
	  }, {
	    key: "emitEditedRowsEvent",
	    value: function emitEditedRowsEvent() {
	      if (this.getGrid().getRows().isSelected()) {
	        main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);
	      } else {
	        main_core_events.EventEmitter.emit('Grid::noEditedRows', []);
	      }
	    }
	  }, {
	    key: "disableEdit",
	    value: function disableEdit() {
	      if (this.isNew) {
	        return;
	      }

	      this.getGrid().getRows().getRows().forEach(function (current) {
	        if (!main_core.Dom.hasClass(current.getNode(), 'main-grid-row-new')) {
	          current.editCancel();
	          current.unselect();
	        }
	      });
	      this.emitEditedRowsEvent();
	    }
	  }, {
	    key: "enableEdit",
	    value: function enableEdit() {
	      this.getGrid().getRows().selectAll();
	      this.getGrid().getRows().editSelected();
	    }
	  }, {
	    key: "prepareNewNodes",
	    value: function prepareNewNodes() {
	      var _this2 = this;

	      this.getGrid().getRows().getBodyChild().map(function (row) {
	        var newNode = row.getNode();

	        _this2.markNodeAsNew(newNode);

	        _this2.addSkuListCreationItem(newNode);

	        _this2.modifyCustomSkuProperties(newNode);

	        _this2.disableCheckbox(row);
	      });
	    }
	  }, {
	    key: "disableCheckbox",
	    value: function disableCheckbox(row) {
	      var checkbox = row.getCheckbox();

	      if (main_core.Type.isDomNode(checkbox)) {
	        checkbox.setAttribute('disabled', 'disabled');
	      }
	    }
	  }, {
	    key: "markNodeAsNew",
	    value: function markNodeAsNew(node) {
	      main_core.Dom.addClass(node, 'main-grid-row-new');
	    }
	  }, {
	    key: "bindInlineEdit",
	    value: function bindInlineEdit() {
	      var _this3 = this;

	      this.getGrid().getRows().getBodyChild().forEach(function (item) {
	        return main_core.Event.bind(item.node, 'click', function (event) {
	          return _this3.toggleInlineEdit(item, event);
	        });
	      });
	    }
	    /**
	     * @returns {BX.UI.EntityEditor|null}
	     */

	  }, {
	    key: "getEditorInstance",
	    value: function getEditorInstance() {
	      if (main_core.Reflection.getClass('BX.UI.EntityEditor')) {
	        return BX.UI.EntityEditor.getDefault();
	      }

	      return null;
	    }
	  }, {
	    key: "bindCreateSkuProperty",
	    value: function bindCreateSkuProperty() {
	      if (!this.canHaveSku) {
	        return;
	      }

	      var createPropertyNode = document.getElementById(this.createPropertyId);
	      var control = this.getEditorInstance().getControlByIdRecursive('variation_grid');

	      if (main_core.Type.isDomNode(createPropertyNode) && control) {
	        control._createChildButton = createPropertyNode;
	        main_core.Event.bind(createPropertyNode, 'click', control.onCreateFieldBtnClick.bind(control));
	      }

	      var createPropertyHintNode = document.getElementById(this.createPropertyHintId);
	      main_core.Event.bind(createPropertyHintNode, 'click', this.showHelp.bind(this));
	    }
	  }, {
	    key: "showHelp",
	    value: function showHelp(event) {
	      if (main_core.Reflection.getClass('top.BX.Helper')) {
	        top.BX.Helper.show('redirect=detail&code=11657102');
	        event.preventDefault();
	      }
	    } // ToDo auto focus on input under event.point?

	  }, {
	    key: "toggleInlineEdit",
	    value: function toggleInlineEdit(item, event) {
	      var changed = false;

	      if (item.isEdit()) {
	        if (this.hasClickedOnCheckboxArea(item, event.target)) {
	          changed = true;
	          this.deactivateInlineEdit(item);
	        }
	      } else {
	        if (event.target.nodeName !== 'A') {
	          changed = true;
	          this.activateInlineEdit(item);
	        }
	      }

	      if (changed) {
	        this.emitEditedRowsEvent();
	        this.getGrid().adjustRows();
	        this.getGrid().updateCounterSelected();
	        this.getGrid().updateCounterDisplayed();
	        this.getGrid().adjustCheckAllCheckboxes();
	      }
	    }
	  }, {
	    key: "hasClickedOnCheckboxArea",
	    value: function hasClickedOnCheckboxArea(item, target) {
	      if (!main_core.Type.isDomNode(target)) {
	        return;
	      }

	      var cells = item.getCells();

	      for (var i in cells) {
	        if (cells.hasOwnProperty(i) && cells[i].contains(item.getCheckbox()) && (cells[i] === target || cells[i].contains(target))) {
	          return true;
	        }
	      }

	      return false;
	    }
	  }, {
	    key: "activateInlineEdit",
	    value: function activateInlineEdit(item) {
	      item.select();
	      item.edit();
	      this.addSkuListCreationItem(item.getNode());
	    }
	  }, {
	    key: "deactivateInlineEdit",
	    value: function deactivateInlineEdit(item) {
	      var _this4 = this;

	      item.editCancel();
	      item.unselect(); // disable multi-selection(and self re-selection) while disabling editing

	      this.getGrid().clickPrevent = true;
	      setTimeout(function () {
	        _this4.getGrid().clickPrevent = false;
	      }, 100);
	    }
	  }, {
	    key: "modifyCustomSkuProperties",
	    value: function modifyCustomSkuProperties(node) {
	      var postfix = '_' + node.getAttribute('data-id');
	      node.querySelectorAll('input[type="radio"]').forEach(function (input) {
	        input.id += postfix;
	        input.setAttribute('name', input.getAttribute('name') + postfix);
	      });
	      node.querySelectorAll('label[data-role]').forEach(function (label) {
	        label.setAttribute('for', label.getAttribute('for') + postfix);
	      });
	    }
	  }, {
	    key: "addSkuListCreationItem",
	    value: function addSkuListCreationItem(node) {
	      node.querySelectorAll('[data-role="dropdownContent"] ul').forEach(function (listNode) {
	        if (!listNode.querySelector('[data-role="createItem"]')) {
	          var propertyId = listNode.getAttribute('data-propertyId');
	          var createItem = main_core.Tag.render(_templateObject2(), propertyId, BX.message('C_PVG_ADD_NEW_PROPERTY_VALUE_BUTTON'));
	          listNode.appendChild(createItem);
	        }
	      });
	    }
	  }, {
	    key: "addRowToGrid",
	    value: function addRowToGrid() {
	      var originalTemplate = this.redefineTemplateEditData();
	      var grid = this.getGrid();
	      var newRow = grid.prependRowEditor();
	      this.disableCheckbox(newRow);
	      var newNode = newRow.getNode();
	      grid.getRows().reset();

	      if (main_core.Type.isDomNode(newNode)) {
	        var newRowDataId = main_core.Text.getRandom();
	        this.gridEditData[newRowDataId] = babelHelpers.objectSpread({}, this.gridEditData['template_0']);
	        newNode.setAttribute('data-id', newRowDataId);
	        this.markNodeAsNew(newNode);
	        this.modifyCustomSkuProperties(newNode);
	        this.addSkuListCreationItem(newNode);
	        this.setDeleteButton(newNode);
	        newRow.makeCountable();
	      }

	      if (originalTemplate) {
	        this.setOriginalTemplateEditData(originalTemplate);
	      }

	      main_core_events.EventEmitter.emit('Grid::thereEditedRows', []);
	      grid.adjustRows();
	      grid.updateCounterDisplayed();
	      grid.updateCounterSelected();
	      this.updateCounterTotal();
	    }
	  }, {
	    key: "updateCounterTotal",
	    value: function updateCounterTotal() {
	      var grid = this.getGrid();
	      var counterTotalTextContainer = grid.getCounterTotal().querySelector('.main-grid-panel-content-text');
	      counterTotalTextContainer.textContent = grid.getRows().getCountDisplayed();
	    }
	  }, {
	    key: "setDeleteButton",
	    value: function setDeleteButton(row) {
	      var _row$dataset;

	      var actionCellContentContainer = row.querySelector('.main-grid-cell-action .main-grid-cell-content');
	      var rowId = row === null || row === void 0 ? void 0 : (_row$dataset = row.dataset) === null || _row$dataset === void 0 ? void 0 : _row$dataset.id;

	      if (rowId) {
	        var deleteButton = main_core.Tag.render(_templateObject3(), this.removeNewRowFromGrid.bind(this, rowId));
	        main_core.Dom.append(deleteButton, actionCellContentContainer);
	      }
	    }
	  }, {
	    key: "removeNewRowFromGrid",
	    value: function removeNewRowFromGrid(rowId) {
	      if (!main_core.Type.isStringFilled(rowId)) {
	        return;
	      }

	      var gridRow = this.getGrid().getRows().getById(rowId);

	      if (gridRow) {
	        main_core.Dom.remove(gridRow.getNode());
	        this.getGrid().getRows().reset();
	        this.getGrid().updateCounterDisplayed();
	        this.getGrid().updateCounterSelected();
	        this.updateCounterTotal();
	        this.emitEditedRowsEvent();
	      }
	    }
	  }, {
	    key: "removeRowFromGrid",
	    value: function removeRowFromGrid(skuId) {
	      var data = {
	        'id': skuId,
	        'action': 'deleteRow'
	      };
	      this.getGrid().reloadTable('POST', data);
	    }
	  }, {
	    key: "getGridEditData",
	    value: function getGridEditData() {
	      return this.getGrid().arParams.EDITABLE_DATA;
	    } // rewrite edit data because of grid component cuts necessary fields (VIEW_HTML/EDIT_HTML)

	  }, {
	    key: "setGridEditData",
	    value: function setGridEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA = data;
	    }
	  }, {
	    key: "setOriginalTemplateEditData",
	    value: function setOriginalTemplateEditData(data) {
	      this.getGrid().arParams.EDITABLE_DATA[GRID_TEMPLATE_ROW] = data;
	    }
	  }, {
	    key: "redefineTemplateEditData",
	    value: function redefineTemplateEditData() {
	      var newRowData = this.getEditDataFromSelectedValues();

	      if (!newRowData) {
	        newRowData = this.getEditDataFromNotSelectedValues();
	      }

	      if (newRowData) {
	        newRowData = babelHelpers.objectSpread({}, newRowData);
	        this.prepareNewRowData(newRowData);
	        var data = this.getGridEditData();
	        var originalTemplateData = data[GRID_TEMPLATE_ROW];
	        var customEditData = this.prepareCustomEditData(originalTemplateData);
	        this.setOriginalTemplateEditData(babelHelpers.objectSpread({}, originalTemplateData, newRowData, customEditData));
	        return originalTemplateData;
	      }

	      return null;
	    }
	  }, {
	    key: "getEditDataFromSelectedValues",
	    value: function getEditDataFromSelectedValues() {
	      var rowNodes = this.getGrid().getRows().getSelected();
	      return rowNodes.length ? rowNodes[0].editGetValues() : null;
	    }
	  }, {
	    key: "getEditDataFromNotSelectedValues",
	    value: function getEditDataFromNotSelectedValues() {
	      var values = this.getGrid().arParams.EDITABLE_DATA;
	      var id = Object.keys(values).reverse().find(function (index) {
	        return index !== GRID_TEMPLATE_ROW && main_core.Type.isPlainObject(values[index]);
	      });
	      return id ? values[id] : null;
	    }
	  }, {
	    key: "prepareNewRowData",
	    value: function prepareNewRowData(newRowData) {
	      for (var i in newRowData) {
	        if (newRowData.hasOwnProperty(i) && (i.includes('[VIEW_HTML]') || i.includes('[EDIT_HTML]'))) {
	          delete newRowData[i];
	        }
	      }
	    }
	  }, {
	    key: "prepareCustomEditData",
	    value: function prepareCustomEditData(originalEditData) {
	      var customEditData = {};

	      for (var i in originalEditData) {
	        if (originalEditData.hasOwnProperty(i) && i.includes('[EDIT_HTML]')) {
	          // modify file input instance ids (due to collisions with default id)
	          if (originalEditData[i].indexOf('new BX.UI.ImageInput') >= 0) {
	            var filePrefix = 'bx_file_' + i.replace('[EDIT_HTML]', '').toLowerCase() + '_';
	            var matches = originalEditData[i].match(new RegExp('\'(' + filePrefix + '[A-Za-z0-9_]+)\''));

	            if (matches[1]) {
	              customEditData[i] = originalEditData[i].replace(new RegExp(matches[1], 'g'), filePrefix + this.getRandomInt());
	            }
	          }
	        }
	      }

	      return customEditData;
	    }
	  }, {
	    key: "getRandomInt",
	    value: function getRandomInt() {
	      var max = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 100000;
	      return Math.floor(Math.random() * Math.floor(max));
	    }
	  }, {
	    key: "getHeaderNames",
	    value: function getHeaderNames() {
	      var headers = [];
	      var cells = this.getGrid().getRows().getHeadFirstChild().getCells();
	      Array.from(cells).forEach(function (header) {
	        if ('name' in header.dataset) {
	          headers.push(header.dataset.name);
	        }
	      });
	      return headers;
	    }
	  }, {
	    key: "addPropertyToGridHeader",
	    value: function addPropertyToGridHeader(item) {
	      var _this5 = this;

	      BX.ajax.runComponentAction('bitrix:catalog.productcard.variation.grid', 'addPropertyHeader', {
	        mode: 'ajax',
	        data: {
	          gridId: this.getGrid().getId(),
	          propertyCode: item.id,
	          anchor: item.anchor || null,
	          currentHeaders: this.getHeaderNames()
	        }
	      }).then(function (response) {
	        _this5.reloadGrid();
	      });
	    }
	  }, {
	    key: "reloadGrid",
	    value: function reloadGrid() {
	      this.getGrid().reload();
	    }
	  }, {
	    key: "onGridUpdated",
	    value: function onGridUpdated(event) {
	      var _this6 = this;

	      this.getGrid().getSettingsWindow().getItems().forEach(function (column) {
	        if (_this6.getHeaderNames().indexOf(column.node.dataset.name) !== -1) {
	          column.state.selected = true;
	          column.checkbox.checked = true;
	        } else {
	          column.state.selected = false;
	          column.checkbox.checked = false;
	        }
	      });
	    }
	  }, {
	    key: "onPropertySave",
	    value: function onPropertySave(event) {
	      var _event$getCompatData = event.getCompatData(),
	          _event$getCompatData2 = babelHelpers.slicedToArray(_event$getCompatData, 1),
	          sliderEvent = _event$getCompatData2[0];

	      if (sliderEvent.getEventId() === 'PropertyCreationForm:onAdd') {
	        var eventArgs = sliderEvent.getData();
	        this.addPropertyToGridHeader({
	          id: eventArgs.fields.CODE
	        });
	      }

	      if (sliderEvent.getEventId() === 'PropertyCreationForm:onModify') {
	        this.reloadGrid();
	      }
	    }
	  }, {
	    key: "showPropertySettingsSlider",
	    value: function showPropertySettingsSlider(event) {
	      var _event$getData5 = event.getData(),
	          _event$getData6 = babelHelpers.slicedToArray(_event$getData5, 1),
	          propertyId = _event$getData6[0];

	      var link = this.modifyPropertyLink.replace('#PROPERTY_ID#', propertyId);
	      this.askToLossGridData(function () {
	        BX.SidePanel.Instance.open(link, {
	          width: 550,
	          allowChangeHistory: false,
	          cacheable: false
	        });
	      });
	    }
	  }, {
	    key: "askToLossGridData",
	    value: function askToLossGridData(okCallback, cancelCallback, options) {
	      if (this.isGridInEditMode()) {
	        var defaultOptions = {
	          title: main_core.Loc.getMessage('C_PVG_UNSAVED_DATA_TITLE'),
	          message: main_core.Loc.getMessage('C_PVG_UNSAVED_DATA_MESSAGE'),
	          modal: true,
	          buttons: ui_dialogs_messagebox.MessageBoxButtons.OK_CANCEL,
	          okCaption: main_core.Loc.getMessage('C_PVG_UNSAVED_DATA_CONTINUE'),
	          onOk: function onOk(messageBox) {
	            okCallback && okCallback();
	            messageBox.close();
	          },
	          onCancel: function onCancel(messageBox) {
	            cancelCallback && cancelCallback();
	            messageBox.close();
	          }
	        };
	        ui_dialogs_messagebox.MessageBox.show(babelHelpers.objectSpread({}, defaultOptions, options));
	      } else {
	        okCallback && okCallback();
	      }
	    }
	  }, {
	    key: "isGridInEditMode",
	    value: function isGridInEditMode() {
	      return this.getGrid().getRows().getBodyChild().filter(function (row) {
	        return row.isShown() && row.isEdit();
	      }).length > 0;
	    }
	  }], [{
	    key: "firePropertyModification",
	    value: function firePropertyModification(propertyId, menuId) {
	      if (menuId) {
	        var menu = main_popup.MenuManager.getMenuById(menuId);

	        if (menu) {
	          menu.close();
	          menu.destroy();
	        }
	      } else {
	        var popup = main_popup.PopupManager.getCurrentPopup();

	        if (popup) {
	          popup.close();
	        }
	      }

	      main_core_events.EventEmitter.emit('VariationGrid::propertyModify', [propertyId]);
	    }
	  }]);
	  return VariationGrid;
	}();

	main_core.Reflection.namespace('BX.Catalog').VariationGrid = VariationGrid;

}((this.window = this.window || {}),BX,BX.Event,BX.Main,BX.UI.Dialogs));
//# sourceMappingURL=script.js.map
