/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_activity_todoEditorV2,crm_activity_todoNotificationSkip,crm_activity_todoNotificationSkipMenu,main_core,main_core_events,main_popup,ui_buttons) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	const SAVE_BUTTON_ID = 'save';
	const CANCEL_BUTTON_ID = 'cancel';
	const SKIP_BUTTON_ID = 'skip';
	var _timeline = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("timeline");
	var _entityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityTypeId");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _entityStageId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityStageId");
	var _stageIdField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stageIdField");
	var _finalStages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("finalStages");
	var _allowCloseSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("allowCloseSlider");
	var _isSkipped = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSkipped");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _toDoEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toDoEditor");
	var _skipProvider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipProvider");
	var _skipMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("skipMenu");
	var _sliderIsMinimizing = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sliderIsMinimizing");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _refs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refs");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _getSliderInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSliderInstance");
	var _isSliderMinimizeAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isSliderMinimizeAvailable");
	var _onCloseSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCloseSlider");
	var _onEntityUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityUpdate");
	var _onEntityDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityDelete");
	var _onEntityModelChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityModelChange");
	var _onSkippedPeriodChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkippedPeriodChange");
	var _onToolbarMenuBuild = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onToolbarMenuBuild");
	var _onSaveHotkeyPressed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSaveHotkeyPressed");
	var _onChangeUploaderContainerSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeUploaderContainerSize");
	var _onSkipMenuItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemSelect");
	var _saveTodo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveTodo");
	var _cancel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancel");
	var _revertButtonsState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("revertButtonsState");
	var _closePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closePopup");
	var _closeSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeSlider");
	var _showTodoCreationNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showTodoCreationNotification");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _getTodoEditorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTodoEditorContainer");
	var _getNotificationTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNotificationTitle");
	var _getPreparedForV2NotificationSkipButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPreparedForV2NotificationSkipButton");
	var _createNotificationSkipButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createNotificationSkipButton");
	var _getSkipMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSkipMenuItems");
	var _showCancelNotificationInParentWindow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCancelNotificationInParentWindow");
	class TodoCreateNotification {
	  constructor(params) {
	    var _params$analytics;
	    Object.defineProperty(this, _showCancelNotificationInParentWindow, {
	      value: _showCancelNotificationInParentWindow2
	    });
	    Object.defineProperty(this, _getSkipMenuItems, {
	      value: _getSkipMenuItems2
	    });
	    Object.defineProperty(this, _createNotificationSkipButton, {
	      value: _createNotificationSkipButton2
	    });
	    Object.defineProperty(this, _getPreparedForV2NotificationSkipButton, {
	      value: _getPreparedForV2NotificationSkipButton2
	    });
	    Object.defineProperty(this, _getNotificationTitle, {
	      value: _getNotificationTitle2
	    });
	    Object.defineProperty(this, _getTodoEditorContainer, {
	      value: _getTodoEditorContainer2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _showTodoCreationNotification, {
	      value: _showTodoCreationNotification2
	    });
	    Object.defineProperty(this, _closeSlider, {
	      value: _closeSlider2
	    });
	    Object.defineProperty(this, _closePopup, {
	      value: _closePopup2
	    });
	    Object.defineProperty(this, _revertButtonsState, {
	      value: _revertButtonsState2
	    });
	    Object.defineProperty(this, _cancel, {
	      value: _cancel2
	    });
	    Object.defineProperty(this, _saveTodo, {
	      value: _saveTodo2
	    });
	    Object.defineProperty(this, _onSkipMenuItemSelect, {
	      value: _onSkipMenuItemSelect2
	    });
	    Object.defineProperty(this, _onChangeUploaderContainerSize, {
	      value: _onChangeUploaderContainerSize2
	    });
	    Object.defineProperty(this, _onSaveHotkeyPressed, {
	      value: _onSaveHotkeyPressed2
	    });
	    Object.defineProperty(this, _onToolbarMenuBuild, {
	      value: _onToolbarMenuBuild2
	    });
	    Object.defineProperty(this, _onSkippedPeriodChange, {
	      value: _onSkippedPeriodChange2
	    });
	    Object.defineProperty(this, _onEntityModelChange, {
	      value: _onEntityModelChange2
	    });
	    Object.defineProperty(this, _onEntityDelete, {
	      value: _onEntityDelete2
	    });
	    Object.defineProperty(this, _onEntityUpdate, {
	      value: _onEntityUpdate2
	    });
	    Object.defineProperty(this, _onCloseSlider, {
	      value: _onCloseSlider2
	    });
	    Object.defineProperty(this, _isSliderMinimizeAvailable, {
	      value: _isSliderMinimizeAvailable2
	    });
	    Object.defineProperty(this, _getSliderInstance, {
	      value: _getSliderInstance2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _timeline, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _entityStageId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _stageIdField, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _finalStages, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _allowCloseSlider, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isSkipped, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _toDoEditor, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _skipProvider, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _skipMenu, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _sliderIsMinimizing, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _refs, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = params.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId] = params.entityStageId;
	    babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField] = params.stageIdField;
	    babelHelpers.classPrivateFieldLooseBase(this, _finalStages)[_finalStages] = params.finalStages;
	    babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = Boolean(params.skipPeriod);
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = (_params$analytics = params.analytics) != null ? _params$analytics : {};
	    if (BX.CrmTimelineManager) {
	      babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline] = BX.CrmTimelineManager.getDefault();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _skipProvider)[_skipProvider] = new crm_activity_todoNotificationSkip.TodoNotificationSkip({
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      onSkippedPeriodChange: babelHelpers.classPrivateFieldLooseBase(this, _onSkippedPeriodChange)[_onSkippedPeriodChange].bind(this)
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _skipMenu)[_skipMenu] = new crm_activity_todoNotificationSkipMenu.TodoNotificationSkipMenu({
	      entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      selectedValue: params.skipPeriod
	    });
	  }
	  getTodoEditor() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor] !== null) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor];
	    }
	    const params = {
	      container: babelHelpers.classPrivateFieldLooseBase(this, _getTodoEditorContainer)[_getTodoEditorContainer](),
	      ownerTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	      ownerId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	      currentUser: babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].getCurrentUser(),
	      pingSettings: babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].getPingSettings(),
	      events: {
	        onSaveHotkeyPressed: babelHelpers.classPrivateFieldLooseBase(this, _onSaveHotkeyPressed)[_onSaveHotkeyPressed].bind(this),
	        onChangeUploaderContainerSize: babelHelpers.classPrivateFieldLooseBase(this, _onChangeUploaderContainerSize)[_onChangeUploaderContainerSize].bind(this)
	      },
	      borderColor: crm_activity_todoEditorV2.TodoEditorV2.BorderColor.PRIMARY
	    };
	    params.calendarSettings = babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].getCalendarSettings();
	    params.colorSettings = babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].getColorSettings();
	    params.defaultDescription = '';
	    params.analytics = babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics];
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor] = new crm_activity_todoEditorV2.TodoEditorV2(params);
	    return babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor];
	  }
	}
	function _bindEvents2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) {
	    main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance](), 'SidePanel.Slider:onClose', babelHelpers.classPrivateFieldLooseBase(this, _onCloseSlider)[_onCloseSlider].bind(this));
	    main_core_events.EventEmitter.subscribe('Crm.EntityModel.Change', babelHelpers.classPrivateFieldLooseBase(this, _onEntityModelChange)[_onEntityModelChange].bind(this));
	    main_core_events.EventEmitter.subscribe('onCrmEntityUpdate', babelHelpers.classPrivateFieldLooseBase(this, _onEntityUpdate)[_onEntityUpdate].bind(this));
	    main_core_events.EventEmitter.subscribe('onCrmEntityDelete', babelHelpers.classPrivateFieldLooseBase(this, _onEntityDelete)[_onEntityDelete].bind(this));
	  }
	  main_core_events.EventEmitter.subscribe('Crm.InterfaceToolbar.MenuBuild', babelHelpers.classPrivateFieldLooseBase(this, _onToolbarMenuBuild)[_onToolbarMenuBuild].bind(this));
	}
	function _getSliderInstance2() {
	  if (top.BX && top.BX.SidePanel) {
	    const slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
	    if (slider && slider.isOpen()) {
	      return slider;
	    }
	  }
	  return null;
	}
	function _isSliderMinimizeAvailable2() {
	  return Object.hasOwn(BX.SidePanel.Slider.prototype, 'minimize') && Object.hasOwn(BX.SidePanel.Slider.prototype, 'isMinimizing');
	}
	function _onCloseSlider2(event) {
	  var _sliderEvent$getSlide;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] || babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped]) {
	    return;
	  }
	  const [sliderEvent] = event.getCompatData();
	  if (sliderEvent.getSlider() !== top.BX.SidePanel.Instance.getSliderByWindow(window)) {
	    return;
	  }
	  if (!sliderEvent.isActionAllowed()) {
	    return; // editor has unsaved fields
	  }

	  if (!babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline] || babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].hasScheduledItems()) {
	    return; // timeline already has scheduled activities
	  }

	  if (babelHelpers.classPrivateFieldLooseBase(this, _finalStages)[_finalStages].includes(babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId])) {
	    return; // element has final stage
	  }

	  babelHelpers.classPrivateFieldLooseBase(this, _sliderIsMinimizing)[_sliderIsMinimizing] = babelHelpers.classPrivateFieldLooseBase(this, _isSliderMinimizeAvailable)[_isSliderMinimizeAvailable]() && ((_sliderEvent$getSlide = sliderEvent.getSlider()) == null ? void 0 : _sliderEvent$getSlide.isMinimizing());
	  sliderEvent.denyAction();
	  setTimeout(async () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showTodoCreationNotification)[_showTodoCreationNotification]();
	  }, 100);
	}
	function _onEntityUpdate2(event) {
	  const [eventParams] = event.getCompatData();
	  if (Object.hasOwn(eventParams, 'entityData') && Object.hasOwn(eventParams.entityData, babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId] = eventParams.entityData[babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField]];
	  }
	}
	function _onEntityDelete2(event) {
	  const [eventParams] = event.getCompatData();
	  if (Object.hasOwn(eventParams, 'id') && Text.toString(eventParams.id) === Text.toString(babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	  }
	}
	function _onEntityModelChange2(event) {
	  const [model, eventParams] = event.getCompatData();
	  if (eventParams.fieldName === babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId] = model.getStringField(babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField], babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId]);
	  }
	}
	function _onSkippedPeriodChange2(period) {
	  babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = Boolean(period);
	}
	function _onToolbarMenuBuild2(event) {
	  const [, {
	    items
	  }] = event.getData();
	  items.push({
	    delimiter: true
	  });
	  for (const skipItem of babelHelpers.classPrivateFieldLooseBase(this, _skipMenu)[_skipMenu].getItems()) {
	    items.push(skipItem);
	  }
	}
	function _onSaveHotkeyPressed2() {
	  var _babelHelpers$classPr;
	  const saveButton = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.getButton(SAVE_BUTTON_ID);
	  if (!saveButton.getState())
	    // if save button is not disabled
	    {
	      babelHelpers.classPrivateFieldLooseBase(this, _saveTodo)[_saveTodo]();
	    }
	}
	function _onChangeUploaderContainerSize2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].adjustPosition();
	  }
	}
	function _onSkipMenuItemSelect2(period) {
	  var _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10;
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.getButton(SKIP_BUTTON_ID)) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getMenuWindow()) == null ? void 0 : _babelHelpers$classPr4.close();
	  (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr6 = _babelHelpers$classPr5.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr6.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr8 = _babelHelpers$classPr7.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr8.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr10 = _babelHelpers$classPr9.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr10.setState(ui_buttons.ButtonState.WAITING);
	  this.getTodoEditor().cancel({
	    analytics: {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics],
	      element: crm_activity_todoEditorV2.TodoEditorV2.AnalyticsElement.skipPeriodButton,
	      notificationSkipPeriod: period
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _skipProvider)[_skipProvider].saveSkippedPeriod(period).then(() => {
	    var _babelHelpers$classPr11;
	    babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = Boolean(period);
	    babelHelpers.classPrivateFieldLooseBase(this, _skipMenu)[_skipMenu].setSelectedValue(period);
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _showCancelNotificationInParentWindow)[_showCancelNotificationInParentWindow]();
	    (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr11.close();
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	  });
	}
	function _saveTodo2() {
	  var _babelHelpers$classPr12, _babelHelpers$classPr13, _babelHelpers$classPr14, _babelHelpers$classPr15, _babelHelpers$classPr16, _babelHelpers$classPr17;
	  (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr13 = _babelHelpers$classPr12.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr13.setState(ui_buttons.ButtonState.WAITING);
	  (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr15 = _babelHelpers$classPr14.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr15.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr16 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr17 = _babelHelpers$classPr16.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr17.setState(ui_buttons.ButtonState.DISABLED);
	  this.getTodoEditor().save().then(result => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	    if (!(Object.hasOwn(result, 'errors') && result.errors.length > 0)) {
	      var _babelHelpers$classPr18;
	      babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	      (_babelHelpers$classPr18 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr18.close();
	    }
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	  });
	}
	function _cancel2() {
	  void this.getTodoEditor().cancel({
	    analytics: {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics],
	      element: crm_activity_todoEditorV2.TodoEditorV2.AnalyticsElement.cancelButton
	    }
	  }).then(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	  });
	}
	function _revertButtonsState2() {
	  var _babelHelpers$classPr19, _babelHelpers$classPr20, _babelHelpers$classPr21, _babelHelpers$classPr22, _babelHelpers$classPr23, _babelHelpers$classPr24;
	  (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr20 = _babelHelpers$classPr19.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr20.setState(null);
	  (_babelHelpers$classPr21 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr22 = _babelHelpers$classPr21.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr22.setState(null);
	  (_babelHelpers$classPr23 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr24 = _babelHelpers$classPr23.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr24.setState(null);
	}
	function _closePopup2() {
	  var _babelHelpers$classPr25;
	  (_babelHelpers$classPr25 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr25.close();
	}
	function _closeSlider2() {
	  var _babelHelpers$classPr27;
	  babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isSliderMinimizeAvailable)[_isSliderMinimizeAvailable]() && babelHelpers.classPrivateFieldLooseBase(this, _sliderIsMinimizing)[_sliderIsMinimizing]) {
	    var _babelHelpers$classPr26;
	    (_babelHelpers$classPr26 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr26.minimize();
	    return;
	  }
	  (_babelHelpers$classPr27 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr27.close();
	}
	function _showTodoCreationNotification2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    const htmlStyles = getComputedStyle(document.documentElement);
	    const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
	    const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
	    const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';
	    const {
	      innerWidth
	    } = window;
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = main_popup.PopupManager.create({
	      id: `todo-create-confirm-${babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]}-${babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]}`,
	      closeIcon: false,
	      padding: popupPaddingNumberValue,
	      overlay: {
	        opacity: 40,
	        backgroundColor: popupOverlayColor
	      },
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	      minWidth: 537,
	      width: Math.round(innerWidth * 0.45),
	      maxWidth: 737,
	      events: {
	        onClose: babelHelpers.classPrivateFieldLooseBase(this, _closeSlider)[_closeSlider].bind(this),
	        onFirstShow: () => {
	          this.getTodoEditor().show();
	          this.getTodoEditor().setFocused();
	        }
	      },
	      className: 'crm-activity__todo-create-notification-popup'
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  setTimeout(() => {
	    this.getTodoEditor().setFocused();
	  }, 10);
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setClosingByEsc(true);
	    main_core.Event.bind(document, 'keyup', event => {
	      if (event.key === 'Escape') {
	        void this.getTodoEditor().cancel({
	          analytics: {
	            ...babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics],
	            element: crm_activity_todoEditorV2.TodoEditorV2.AnalyticsElement.cancelButton
	          }
	        });
	      }
	    });
	  }, 300);
	}
	function _getPopupContent2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('content', () => {
	    const buttonsContainer = main_core.Tag.render(_t || (_t = _`
				<div class="crm-activity__todo-create-notification_footer">
					<div class="crm-activity__todo-create-notification_buttons-container">
						<button 
							class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round"
							onclick="${0}"
						>
							${0}
						</button>
						<button
							class="ui-btn ui-btn-xs ui-btn-link"
							onclick="${0}"
						>
							${0}
						</button>
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _saveTodo)[_saveTodo].bind(this), main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_OK_BUTTON_V2'), babelHelpers.classPrivateFieldLooseBase(this, _cancel)[_cancel].bind(this), main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCEL_BUTTON_V2'), babelHelpers.classPrivateFieldLooseBase(this, _getPreparedForV2NotificationSkipButton)[_getPreparedForV2NotificationSkipButton]().render());
	    return main_core.Tag.render(_t2 || (_t2 = _`
				<div>
					<div class="crm-activity__todo-create-notification_title --v2">
						${0}
					</div>
					<div>
						${0}
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getNotificationTitle)[_getNotificationTitle](), babelHelpers.classPrivateFieldLooseBase(this, _getTodoEditorContainer)[_getTodoEditorContainer](), buttonsContainer);
	  });
	}
	function _getTodoEditorContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('editor', () => {
	    return main_core.Tag.render(_t3 || (_t3 = _`<div></div>`));
	  });
	}
	function _getNotificationTitle2() {
	  let code = null;
	  switch (babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]) {
	    case BX.CrmEntityType.enumeration.lead:
	      code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2_LEAD';
	      break;
	    case BX.CrmEntityType.enumeration.deal:
	      code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2_DEAL';
	      break;
	    default:
	      code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2';
	  }
	  return main_core.Loc.getMessage(code);
	}
	function _getPreparedForV2NotificationSkipButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _createNotificationSkipButton)[_createNotificationSkipButton]().setNoCaps().addClass('crm-activity__todo-create-notification_skip-button');
	}
	function _createNotificationSkipButton2() {
	  return new ui_buttons.Button({
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_V2'),
	    color: ui_buttons.ButtonColor.LINK,
	    id: SKIP_BUTTON_ID,
	    dropdown: true,
	    menu: {
	      closeByEsc: true,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getSkipMenuItems)[_getSkipMenuItems](),
	      minWidth: 233
	    }
	  });
	}
	function _getSkipMenuItems2() {
	  return [{
	    id: 'day',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_DAY'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'day')
	  }, {
	    id: 'week',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_WEEK'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'week')
	  }, {
	    id: 'month',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_MONTH'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'month')
	  }, {
	    id: 'forever',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOREVER'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'forever')
	  }];
	}
	function _showCancelNotificationInParentWindow2() {
	  if (top.BX && top.BX.Runtime) {
	    const entityTypeId = babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId];
	    void top.BX.Runtime.loadExtension('crm.activity.todo-notification-skip').then(exports => {
	      const skipProvider = new exports.TodoNotificationSkip({
	        entityTypeId
	      });
	      skipProvider.showCancelPeriodNotification();
	    });
	  }
	}

	exports.TodoCreateNotification = TodoCreateNotification;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Crm.Activity,BX.Crm.Activity,BX.Crm.Activity,BX,BX.Event,BX.Main,BX.UI));
//# sourceMappingURL=todo-create-notification.bundle.js.map
