/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,bizproc_automation,crm_field_colorSelector,main_core,main_core_events,main_popup,ui_entitySelector,ui_iconSet_api_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	const namespace = main_core.Reflection.namespace('BX.Crm.Activity');
	var _isRobot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isRobot");
	var _colorSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("colorSelector");
	var _colorId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("colorId");
	var _locationSelectorWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("locationSelectorWrapper");
	var _additionalSettingsButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("additionalSettingsButton");
	var _documentFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentFields");
	var _additionalSettingsWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("additionalSettingsWrapper");
	var _documentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentType");
	var _additionalSettingsFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("additionalSettingsFields");
	var _dataConfig = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dataConfig");
	var _fileSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileSelector");
	var _fileControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fileControl");
	var _diskControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("diskControl");
	var _diskControlItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("diskControlItems");
	var _attachmentType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("attachmentType");
	var _setOnBeforeSaveSettingsCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setOnBeforeSaveSettingsCallback");
	var _onBeforeSaveRobotSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBeforeSaveRobotSettings");
	var _onColorChanged = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onColorChanged");
	var _getLocationSelectorDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLocationSelectorDialog");
	var _getLocationExpressionTag = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLocationExpressionTag");
	var _renderLocation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLocation");
	var _fetchRoomsManagerData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fetchRoomsManagerData");
	var _prepareMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareMenuItems");
	var _onAdditionalSettingsButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onAdditionalSettingsButtonClick");
	var _renderBaseControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderBaseControl");
	var _renderFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFile");
	var _removeControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeControl");
	var _getCapacityTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCapacityTitle");
	var _getActionItemHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getActionItemHtml");
	var _getColorSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getColorSelector");
	var _onChangeAttachmentTypeHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeAttachmentTypeHandler");
	var _openDiskFileDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openDiskFileDialog");
	var _onSaveButtonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSaveButtonClickHandler");
	var _renderAttachmentFile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderAttachmentFile");
	var _assertValidOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("assertValidOptions");
	class CrmCreateTodoActivity {
	  constructor(_options) {
	    Object.defineProperty(this, _assertValidOptions, {
	      value: _assertValidOptions2
	    });
	    Object.defineProperty(this, _renderAttachmentFile, {
	      value: _renderAttachmentFile2
	    });
	    Object.defineProperty(this, _onSaveButtonClickHandler, {
	      value: _onSaveButtonClickHandler2
	    });
	    Object.defineProperty(this, _openDiskFileDialog, {
	      value: _openDiskFileDialog2
	    });
	    Object.defineProperty(this, _onChangeAttachmentTypeHandler, {
	      value: _onChangeAttachmentTypeHandler2
	    });
	    Object.defineProperty(this, _getColorSelector, {
	      value: _getColorSelector2
	    });
	    Object.defineProperty(this, _getActionItemHtml, {
	      value: _getActionItemHtml2
	    });
	    Object.defineProperty(this, _getCapacityTitle, {
	      value: _getCapacityTitle2
	    });
	    Object.defineProperty(this, _removeControl, {
	      value: _removeControl2
	    });
	    Object.defineProperty(this, _renderFile, {
	      value: _renderFile2
	    });
	    Object.defineProperty(this, _renderBaseControl, {
	      value: _renderBaseControl2
	    });
	    Object.defineProperty(this, _onAdditionalSettingsButtonClick, {
	      value: _onAdditionalSettingsButtonClick2
	    });
	    Object.defineProperty(this, _prepareMenuItems, {
	      value: _prepareMenuItems2
	    });
	    Object.defineProperty(this, _fetchRoomsManagerData, {
	      value: _fetchRoomsManagerData2
	    });
	    Object.defineProperty(this, _renderLocation, {
	      value: _renderLocation2
	    });
	    Object.defineProperty(this, _getLocationExpressionTag, {
	      value: _getLocationExpressionTag2
	    });
	    Object.defineProperty(this, _getLocationSelectorDialog, {
	      value: _getLocationSelectorDialog2
	    });
	    Object.defineProperty(this, _onColorChanged, {
	      value: _onColorChanged2
	    });
	    Object.defineProperty(this, _onBeforeSaveRobotSettings, {
	      value: _onBeforeSaveRobotSettings2
	    });
	    Object.defineProperty(this, _setOnBeforeSaveSettingsCallback, {
	      value: _setOnBeforeSaveSettingsCallback2
	    });
	    Object.defineProperty(this, _isRobot, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _colorSelector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _colorId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _locationSelectorWrapper, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _additionalSettingsButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentFields, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _additionalSettingsWrapper, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _additionalSettingsFields, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dataConfig, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fileSelector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fileControl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _diskControl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _diskControlItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _attachmentType, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields] = _options.documentFields;
	    babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot] = _options.isRobot === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType] = _options.documentType;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _assertValidOptions)[_assertValidOptions](_options);
	      babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsWrapper)[_additionalSettingsWrapper] = _options.additionalSettingsWrapper;
	      babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsButton)[_additionalSettingsButton] = _options.additionalSettingsButton;
	      main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsButton)[_additionalSettingsButton], 'click', babelHelpers.classPrivateFieldLooseBase(this, _onAdditionalSettingsButtonClick)[_onAdditionalSettingsButtonClick].bind(this));
	      babelHelpers.classPrivateFieldLooseBase(this, _dataConfig)[_dataConfig] = _options.dataConfig;
	      babelHelpers.classPrivateFieldLooseBase(this, _colorSelector)[_colorSelector] = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelector)[_getColorSelector](_options.colorSelectorWrapper, _options.colorSettings, _options.isAvailableColor);
	      babelHelpers.classPrivateFieldLooseBase(this, _colorId)[_colorId] = _options.colorSettings.selectedValueId;
	      main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _colorSelector)[_colorSelector], crm_field_colorSelector.ColorSelectorEvents.EVENT_COLORSELECTOR_VALUE_CHANGE, babelHelpers.classPrivateFieldLooseBase(this, _onColorChanged)[_onColorChanged].bind(this));
	      babelHelpers.classPrivateFieldLooseBase(this, _setOnBeforeSaveSettingsCallback)[_setOnBeforeSaveSettingsCallback]();
	      babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields] = {};
	    } else if (_options.attachmentType) {
	      babelHelpers.classPrivateFieldLooseBase(this, _fileControl)[_fileControl] = _options.fileControl;
	      babelHelpers.classPrivateFieldLooseBase(this, _diskControl)[_diskControl] = _options.diskControl;
	      babelHelpers.classPrivateFieldLooseBase(this, _diskControlItems)[_diskControlItems] = _options.diskControlItems;
	      babelHelpers.classPrivateFieldLooseBase(this, _attachmentType)[_attachmentType] = _options.attachmentType.value;
	      main_core.Event.bind(_options.attachmentType, 'change', event => babelHelpers.classPrivateFieldLooseBase(this, _onChangeAttachmentTypeHandler)[_onChangeAttachmentTypeHandler](event.target.value));
	      main_core.Event.bind(_options.showDiskFileDialogButton, 'click', babelHelpers.classPrivateFieldLooseBase(this, _openDiskFileDialog)[_openDiskFileDialog].bind(this));
	    }
	  }
	  async renderControl(fieldName, value = null) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName] || !(fieldName in babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields])) {
	      return;
	    }
	    if (fieldName === 'Colleagues') {
	      this.renderControl('Duration');
	    }
	    const newRow = main_core.Dom.create('div', {
	      attrs: {
	        className: 'bizproc-automation-popup-settings'
	      }
	    });
	    main_core.Dom.append(main_core.Dom.create('span', {
	      text: babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields][fieldName].Name,
	      attrs: {
	        className: 'bizproc-automation-popup-settings-title bizproc-automation-popup-settings-title-autocomplete'
	      }
	    }), newRow);
	    const deleteButton = main_core.Dom.create('a', {
	      attrs: {
	        className: 'bizproc-automation-popup-settings-delete bizproc-automation-popup-settings-link bizproc-automation-popup-settings-link-light'
	      },
	      props: {
	        href: '#'
	      },
	      events: {
	        click: e => babelHelpers.classPrivateFieldLooseBase(this, _removeControl)[_removeControl](fieldName, e)
	      },
	      text: main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ADDITIONAL_FIELD_DELETE')
	    });
	    main_core.Dom.append(deleteButton, newRow);

	    // eslint-disable-next-line init-declarations
	    let node;
	    if (fieldName === 'LocationId') {
	      node = await babelHelpers.classPrivateFieldLooseBase(this, _renderLocation)[_renderLocation](value);
	    } else if (fieldName === 'Attachment') {
	      node = babelHelpers.classPrivateFieldLooseBase(this, _renderFile)[_renderFile]();
	    } else {
	      node = babelHelpers.classPrivateFieldLooseBase(this, _renderBaseControl)[_renderBaseControl](fieldName, value);
	    }
	    main_core.Dom.append(node, newRow);
	    babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName] = newRow;
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName], babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsWrapper)[_additionalSettingsWrapper]);
	  }
	}
	function _setOnBeforeSaveSettingsCallback2() {
	  var _Designer$getInstance;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isRobot)[_isRobot]) {
	    return;
	  }
	  const dialog = (_Designer$getInstance = bizproc_automation.Designer.getInstance()) == null ? void 0 : _Designer$getInstance.getRobotSettingsDialog();
	  if (dialog != null && dialog.robot) {
	    dialog.robot.setOnBeforeSaveRobotSettings(babelHelpers.classPrivateFieldLooseBase(this, _onBeforeSaveRobotSettings)[_onBeforeSaveRobotSettings].bind(this));
	  }
	}
	function _onBeforeSaveRobotSettings2() {
	  const data = {
	    color_id: babelHelpers.classPrivateFieldLooseBase(this, _colorId)[_colorId]
	  };
	  if (babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields].LocationId) {
	    var _this$locationSelecto;
	    data.location_id = (_this$locationSelecto = this.locationSelectorDialog.getTags()[0]) == null ? void 0 : _this$locationSelecto.id;
	  }
	  return data;
	}
	function _onColorChanged2({
	  data
	}) {
	  babelHelpers.classPrivateFieldLooseBase(this, _colorId)[_colorId] = data.value;
	}
	function _getLocationSelectorDialog2(locationId) {
	  if (this.locations === null) {
	    return null;
	  }
	  if (main_core.Type.isNil(this.locationSelectorDialog)) {
	    var _this$locations$rooms;
	    const tabs = [{
	      id: 'location',
	      title: main_core.Loc.getMessage('CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_ENTITY_TITLE')
	    }];
	    const items = [];
	    (_this$locations$rooms = this.locations.rooms) == null ? void 0 : _this$locations$rooms.forEach(room => {
	      var _room$CAPACITY;
	      items.push({
	        id: room.ID,
	        title: room.NAME,
	        subtitle: babelHelpers.classPrivateFieldLooseBase(this, _getCapacityTitle)[_getCapacityTitle]((_room$CAPACITY = room.CAPACITY) != null ? _room$CAPACITY : null),
	        entityId: 'location',
	        tabs: 'location',
	        avatarOptions: {
	          bgColor: room.COLOR,
	          bgSize: '22px',
	          bgImage: 'none'
	        },
	        customData: {
	          locationId: room.LOCATION_ID
	        }
	      });
	    });
	    this.locationSelectorDialog = new ui_entitySelector.TagSelector({
	      multiple: false,
	      textBoxAutoHide: true,
	      dialogOptions: {
	        id: 'todo-robot-calendar-room-selector-dialog',
	        targetNode: babelHelpers.classPrivateFieldLooseBase(this, _locationSelectorWrapper)[_locationSelectorWrapper],
	        context: 'CRM_ACTIVITY_TODO_ROBOT_CALENDAR_ROOM',
	        multiple: false,
	        dropdownMode: true,
	        showAvatars: true,
	        enableSearch: true,
	        width: 450,
	        height: 300,
	        zIndex: 2500,
	        items,
	        tabs
	      }
	    });
	    if (locationId) {
	      var _items$find;
	      const locationTag = (_items$find = items.find(location => location.id === locationId)) != null ? _items$find : babelHelpers.classPrivateFieldLooseBase(this, _getLocationExpressionTag)[_getLocationExpressionTag](locationId);
	      this.locationSelectorDialog.addTag(locationTag);
	    }
	  }
	  return this.locationSelectorDialog;
	}
	function _getLocationExpressionTag2(expression) {
	  return {
	    id: expression,
	    title: expression,
	    entityId: 'location'
	  };
	}
	async function _renderLocation2(value) {
	  this.renderControl('Duration');
	  this.locations = await babelHelpers.classPrivateFieldLooseBase(this, _fetchRoomsManagerData)[_fetchRoomsManagerData]();
	  const wrapper = main_core.Tag.render(_t || (_t = _`<div id="id_location"></div>`));
	  babelHelpers.classPrivateFieldLooseBase(this, _getLocationSelectorDialog)[_getLocationSelectorDialog](value).renderTo(wrapper);
	  return wrapper;
	}
	async function _fetchRoomsManagerData2() {
	  return new Promise(resolve => {
	    main_core.ajax.runAction('calendar.api.locationajax.getRoomsManagerData').then(response => {
	      resolve(response.data);
	    }).catch(errors => {
	      console.log(errors);
	    });
	  });
	}
	function _prepareMenuItems2() {
	  const menuItems = [];
	  // eslint-disable-next-line unicorn/no-this-assignment
	  const createTodo = this;
	  menuItems.push({
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('CALENDAR_1', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_CALENDAR')),
	    onclick() {
	      this.popupWindow.close();
	      createTodo.renderControl('Duration');
	    }
	  }, {
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('PERSON', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_CLIENT')),
	    onclick() {
	      this.popupWindow.close();
	      createTodo.renderControl('Client', 'Y');
	    }
	  }, {
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('PERSONS_2', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_COLLEAGUE')),
	    fieldName: 'Colleagues',
	    onclick() {
	      this.popupWindow.close();
	      createTodo.renderControl('Colleagues');
	    }
	  }, {
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('LOCATION_1', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_ADDRESS')),
	    onclick() {
	      this.popupWindow.close();
	      createTodo.renderControl('Address');
	    }
	  });
	  if ('LocationId' in babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields]) {
	    menuItems.push({
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('CHATS_PERSONS', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_ROOM')),
	      onclick() {
	        this.popupWindow.close();
	        createTodo.renderControl('LocationId');
	      }
	    });
	  }
	  menuItems.push({
	    html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('INSERT_HYPERLINK', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_LINK')),
	    onclick() {
	      this.popupWindow.close();
	      createTodo.renderControl('Link');
	    }
	  });
	  if ('Attachment' in babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields]) {
	    menuItems.push({
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getActionItemHtml)[_getActionItemHtml]('ATTACH', main_core.Loc.getMessage('CRM_BP_CREATE_TODO_ACTIONS_FILE')),
	      text: babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields].Attachment.Name,
	      onclick() {
	        this.popupWindow.close();
	        createTodo.renderControl('Attachment');
	      }
	    });
	  }
	  return menuItems;
	}
	function _onAdditionalSettingsButtonClick2() {
	  const menuId = `bp-create-todo-activity-${Math.random()}`;
	  main_popup.PopupMenu.show(menuId, babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsButton)[_additionalSettingsButton], babelHelpers.classPrivateFieldLooseBase(this, _prepareMenuItems)[_prepareMenuItems](), {
	    autoHide: true,
	    offsetLeft: main_core.Dom.getPosition(this).width / 2,
	    angle: {
	      position: 'top',
	      offset: 0
	    },
	    className: 'bizproc-automation-inline-selector-menu',
	    overlay: {
	      backgroundColor: 'transparent'
	    }
	  });
	}
	function _renderBaseControl2(fieldName, value = null) {
	  return BX.Bizproc.FieldType.renderControl(babelHelpers.classPrivateFieldLooseBase(this, _documentType)[_documentType], babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields][fieldName], babelHelpers.classPrivateFieldLooseBase(this, _documentFields)[_documentFields][fieldName].FieldName, value);
	}
	function _renderFile2() {
	  var _Designer$getInstance2;
	  const wrapper = main_core.Dom.create('div', {
	    attrs: {
	      'data-role': 'file-selector',
	      'data-config': babelHelpers.classPrivateFieldLooseBase(this, _dataConfig)[_dataConfig]
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _fileSelector)[_fileSelector] = new bizproc_automation.FileSelector({
	    context: new bizproc_automation.SelectorContext({
	      fields: bizproc_automation.getGlobalContext().document.getFields(),
	      rootGroupTitle: bizproc_automation.getGlobalContext().document.title
	    })
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _fileSelector)[_fileSelector].renderTo(wrapper);
	  const template = (_Designer$getInstance2 = bizproc_automation.Designer.getInstance().getRobotSettingsDialog()) == null ? void 0 : _Designer$getInstance2.template;
	  if (template) {
	    template.robotSettingsControls.push(babelHelpers.classPrivateFieldLooseBase(this, _fileSelector)[_fileSelector]);
	  }
	  return wrapper;
	}
	function _removeControl2(fieldName, e = null) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName]) {
	    return;
	  }
	  if (fieldName === 'Attachment') {
	    babelHelpers.classPrivateFieldLooseBase(this, _fileSelector)[_fileSelector].destroy();
	  }
	  if (fieldName === 'Duration') {
	    babelHelpers.classPrivateFieldLooseBase(this, _removeControl)[_removeControl]('Colleagues');
	    babelHelpers.classPrivateFieldLooseBase(this, _removeControl)[_removeControl]('LocationId');
	  }
	  e == null ? void 0 : e.preventDefault();
	  main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName]);
	  delete babelHelpers.classPrivateFieldLooseBase(this, _additionalSettingsFields)[_additionalSettingsFields][fieldName];
	}
	function _getCapacityTitle2(value) {
	  if (main_core.Type.isNil(value) || value <= 0) {
	    return '';
	  }
	  return main_core.Loc.getMessage('CRM_BP_CREATE_TODO_LOCATION_SELECTOR_ROOMS_CAPACITY', {
	    '#CAPACITY_VALUE#': value
	  });
	}
	function _getActionItemHtml2(iconKey, message) {
	  const icon = new ui_iconSet_api_core.Icon({
	    icon: ui_iconSet_api_core.Main[iconKey],
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-palette-gray-50'),
	    size: 25
	  });
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<span class="bizproc_automation-todo-activity-actions-menu-item">
				<span class="bizproc_automation-todo-activity-actions-menu-item-icon">${0}</span>
				${0}
			</span>
		`), icon.render(), message);
	}
	function _getColorSelector2(wrapper, settings, isAvailableColor) {
	  return new crm_field_colorSelector.ColorSelector({
	    target: wrapper,
	    colorList: settings.valuesList,
	    selectedColorId: isAvailableColor ? settings.selectedValueId : 'default',
	    readOnlyMode: settings.readOnlyMode
	  });
	}
	function _onChangeAttachmentTypeHandler2(value) {
	  babelHelpers.classPrivateFieldLooseBase(this, _fileControl)[_fileControl].hidden = value === 'disk';
	  babelHelpers.classPrivateFieldLooseBase(this, _diskControl)[_diskControl].hidden = value === 'file';
	  const disableInputs = BX(`BPMA-${babelHelpers.classPrivateFieldLooseBase(this, _attachmentType)[_attachmentType]}-control`).querySelectorAll('input');
	  for (const disableInput of disableInputs) {
	    disableInput.disable = true;
	  }
	  const enableInputs = BX(`BPMA-${value}-control`).querySelectorAll('input');
	  for (const enableInput of enableInputs) {
	    enableInput.disabled = false;
	  }
	}
	function _openDiskFileDialog2() {
	  const urlSelect = `/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=${main_core.Loc.getMessage('SITE_ID')}`;
	  const dialogName = 'BPMA';
	  BX.ajax.get(urlSelect, `multiselect=Y&dialogName=${dialogName}`, () => {
	    setTimeout(() => {
	      BX.DiskFileDialog.obCallback[dialogName] = {
	        saveButton: (tab, path, selected) => babelHelpers.classPrivateFieldLooseBase(this, _onSaveButtonClickHandler)[_onSaveButtonClickHandler](tab, path, selected)
	      };
	      BX.DiskFileDialog.openDialog(dialogName);
	    }, 10);
	  });
	}
	function _onSaveButtonClickHandler2(tab, path, selected) {
	  for (const file of Object.values(selected)) {
	    if (file.type === 'file') {
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _renderAttachmentFile)[_renderAttachmentFile](file), babelHelpers.classPrivateFieldLooseBase(this, _diskControlItems)[_diskControlItems]);
	    }
	  }
	}
	function _renderAttachmentFile2(file) {
	  const fileWrapper = main_core.Tag.render(_t3 || (_t3 = _`
			<div>
				<input type="hidden" name="attachment[]" value="${0}"/>
				<span style="color: grey">${0}</span>
			</div>
		`), file.id.toString().slice(1), BX.util.htmlspecialchars(file.name));
	  const deleteButton = main_core.Tag.render(_t4 || (_t4 = _`
			<a style="color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
		`));
	  main_core.Event.bind(deleteButton, 'click', () => main_core.Dom.remove(fileWrapper));
	  main_core.Dom.append(deleteButton, fileWrapper);
	  return fileWrapper;
	}
	function _assertValidOptions2(options) {
	  if (!main_core.Type.isObject(options.documentFields)) {
	    throw new TypeError('documentFields must be a object');
	  }
	  if (!main_core.Type.isElementNode(options.additionalSettingsWrapper)) {
	    throw new Error('additionalSettingsWrapper must be HTMLElement');
	  }
	  if (!main_core.Type.isElementNode(options.additionalSettingsButton)) {
	    throw new Error('additionalSettingsButton must be HTMLElement');
	  }
	  if (!main_core.Type.isArrayFilled(options.documentType)) {
	    throw new Error('documentType must be filled array');
	  }
	  if (!main_core.Type.isElementNode(options.colorSelectorWrapper)) {
	    throw new Error('colorSelectorWrapper must be HTMLElement');
	  }
	  if (!main_core.Type.isStringFilled(options.formName)) {
	    throw new Error('formName must be filled string');
	  }
	  if (!main_core.Type.isStringFilled(options.dataConfig)) {
	    throw new Error('dataConfig must be a filled string');
	  }
	  if (!main_core.Type.isObject(options.colorSettings)) {
	    throw new TypeError('colorSettings must be a object');
	  }
	}
	namespace.CrmCreateTodoActivity = CrmCreateTodoActivity;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Bizproc.Automation,BX.Crm.Field,BX,BX.Event,BX.Main,BX.UI.EntitySelector,BX.UI.IconSet));
//# sourceMappingURL=script.js.map
