this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_core_events,main_popup,crm_activity_todoEditor,ui_buttons,crm_activity_todoNotificationSkip,crm_activity_todoNotificationSkipMenu) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2;
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
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _getSliderInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSliderInstance");
	var _onCloseSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCloseSlider");
	var _onEntityUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityUpdate");
	var _onEntityDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityDelete");
	var _onEntityModelChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEntityModelChange");
	var _onSkippedPeriodChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkippedPeriodChange");
	var _onToolbarMenuBuild = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onToolbarMenuBuild");
	var _onChangeDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeDescription");
	var _onSaveHotkeyPressed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSaveHotkeyPressed");
	var _onChangeUploaderContainerSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeUploaderContainerSize");
	var _onSkipMenuItemSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSkipMenuItemSelect");
	var _saveTodo = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveTodo");
	var _revertButtonsState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("revertButtonsState");
	var _closePopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closePopup");
	var _closeSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeSlider");
	var _showTodoCreationNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showTodoCreationNotification");
	var _getPopupTitle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupTitle");
	var _getPopupDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupDescription");
	var _getPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContent");
	var _getPopupButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupButtons");
	var _getSkipMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSkipMenuItems");
	var _showCancelNotificationInParentWindow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCancelNotificationInParentWindow");
	class TodoCreateNotification {
	  constructor(params) {
	    Object.defineProperty(this, _showCancelNotificationInParentWindow, {
	      value: _showCancelNotificationInParentWindow2
	    });
	    Object.defineProperty(this, _getSkipMenuItems, {
	      value: _getSkipMenuItems2
	    });
	    Object.defineProperty(this, _getPopupButtons, {
	      value: _getPopupButtons2
	    });
	    Object.defineProperty(this, _getPopupContent, {
	      value: _getPopupContent2
	    });
	    Object.defineProperty(this, _getPopupDescription, {
	      value: _getPopupDescription2
	    });
	    Object.defineProperty(this, _getPopupTitle, {
	      value: _getPopupTitle2
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
	    Object.defineProperty(this, _onChangeDescription, {
	      value: _onChangeDescription2
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
	    babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] = params.entityTypeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId] = params.entityId;
	    babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId] = params.entityStageId;
	    babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField] = params.stageIdField;
	    babelHelpers.classPrivateFieldLooseBase(this, _finalStages)[_finalStages] = params.finalStages;
	    babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = !!params.skipPeriod;
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
	function _onCloseSlider2(event) {
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

	  if (babelHelpers.classPrivateFieldLooseBase(this, _finalStages)[_finalStages].indexOf(babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId]) > -1) {
	    return; // element has final stage
	  }

	  sliderEvent.denyAction();
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showTodoCreationNotification)[_showTodoCreationNotification]();
	  }, 100);
	}
	function _onEntityUpdate2(event) {
	  const [eventParams] = event.getCompatData();
	  if (eventParams.hasOwnProperty('entityData') && eventParams.entityData.hasOwnProperty(babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField])) {
	    babelHelpers.classPrivateFieldLooseBase(this, _entityStageId)[_entityStageId] = eventParams.entityData[babelHelpers.classPrivateFieldLooseBase(this, _stageIdField)[_stageIdField]];
	  }
	}
	function _onEntityDelete2(event) {
	  const [eventParams] = event.getCompatData();
	  if (eventParams.hasOwnProperty('id') && eventParams.id == babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId]) {
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
	  babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = !!period;
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
	function _onChangeDescription2(event) {
	  var _babelHelpers$classPr;
	  const {
	    description
	  } = event.getData();
	  const saveButton = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.getButton(SAVE_BUTTON_ID);
	  if (!description.length && !saveButton.getState()) {
	    saveButton.setState(ui_buttons.ButtonState.DISABLED);
	  } else if (description.length && saveButton.getState() === ui_buttons.ButtonState.DISABLED) {
	    saveButton.setState(null);
	  }
	}
	function _onSaveHotkeyPressed2(event) {
	  var _babelHelpers$classPr2;
	  const saveButton = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr2.getButton(SAVE_BUTTON_ID);
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
	  var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6, _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9, _babelHelpers$classPr10, _babelHelpers$classPr11;
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getButton(SKIP_BUTTON_ID)) == null ? void 0 : (_babelHelpers$classPr5 = _babelHelpers$classPr4.getMenuWindow()) == null ? void 0 : _babelHelpers$classPr5.close();
	  (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr7 = _babelHelpers$classPr6.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr7.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr9 = _babelHelpers$classPr8.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr9.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr11 = _babelHelpers$classPr10.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr11.setState(ui_buttons.ButtonState.WAITING);
	  babelHelpers.classPrivateFieldLooseBase(this, _skipProvider)[_skipProvider].saveSkippedPeriod(period).then(() => {
	    var _babelHelpers$classPr12;
	    babelHelpers.classPrivateFieldLooseBase(this, _isSkipped)[_isSkipped] = !!period;
	    babelHelpers.classPrivateFieldLooseBase(this, _skipMenu)[_skipMenu].setSelectedValue(period);
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _showCancelNotificationInParentWindow)[_showCancelNotificationInParentWindow]();
	    (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr12.close();
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	  });
	}
	function _saveTodo2() {
	  var _babelHelpers$classPr13, _babelHelpers$classPr14, _babelHelpers$classPr15, _babelHelpers$classPr16, _babelHelpers$classPr17, _babelHelpers$classPr18;
	  (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr14 = _babelHelpers$classPr13.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr14.setState(ui_buttons.ButtonState.WAITING);
	  (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr16 = _babelHelpers$classPr15.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr16.setState(ui_buttons.ButtonState.DISABLED);
	  (_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr18 = _babelHelpers$classPr17.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr18.setState(ui_buttons.ButtonState.DISABLED);
	  babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].save().then(result => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	    if (!(result.hasOwnProperty('errors') && result.errors.length)) {
	      var _babelHelpers$classPr19;
	      babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup]();
	      (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr19.close();
	    }
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _revertButtonsState)[_revertButtonsState]();
	  });
	}
	function _revertButtonsState2() {
	  var _babelHelpers$classPr20, _babelHelpers$classPr21, _babelHelpers$classPr22, _babelHelpers$classPr23, _babelHelpers$classPr24, _babelHelpers$classPr25;
	  (_babelHelpers$classPr20 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr21 = _babelHelpers$classPr20.getButton(SAVE_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr21.setState(null);
	  (_babelHelpers$classPr22 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr23 = _babelHelpers$classPr22.getButton(CANCEL_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr23.setState(null);
	  (_babelHelpers$classPr24 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr25 = _babelHelpers$classPr24.getButton(SKIP_BUTTON_ID)) == null ? void 0 : _babelHelpers$classPr25.setState(null);
	}
	function _closePopup2() {
	  var _babelHelpers$classPr26;
	  (_babelHelpers$classPr26 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr26.close();
	}
	function _closeSlider2() {
	  var _babelHelpers$classPr27;
	  babelHelpers.classPrivateFieldLooseBase(this, _allowCloseSlider)[_allowCloseSlider] = true;
	  (_babelHelpers$classPr27 = babelHelpers.classPrivateFieldLooseBase(this, _getSliderInstance)[_getSliderInstance]()) == null ? void 0 : _babelHelpers$classPr27.close();
	}
	function _showTodoCreationNotification2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    const htmlStyles = getComputedStyle(document.documentElement);
	    const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
	    const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
	    const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = main_popup.PopupManager.create({
	      id: 'todo-create-confirm-' + babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId] + '-' + babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	      closeIcon: true,
	      padding: popupPaddingNumberValue,
	      overlay: {
	        opacity: 40,
	        backgroundColor: popupOverlayColor
	      },
	      content: babelHelpers.classPrivateFieldLooseBase(this, _getPopupContent)[_getPopupContent](),
	      buttons: babelHelpers.classPrivateFieldLooseBase(this, _getPopupButtons)[_getPopupButtons](),
	      width: 545,
	      events: {
	        onClose: babelHelpers.classPrivateFieldLooseBase(this, _closeSlider)[_closeSlider].bind(this)
	      },
	      className: 'crm-activity__todo-create-notification-popup'
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].setFocused();
	  }, 10);
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setClosingByEsc(true);
	  }, 300);
	}
	function _getPopupTitle2() {
	  return main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_TITLE');
	}
	function _getPopupDescription2() {
	  let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION';
	  switch (babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId]) {
	    case BX.CrmEntityType.enumeration.lead:
	      messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION_LEAD';
	      break;
	    case BX.CrmEntityType.enumeration.deal:
	      messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION_DEAL';
	      break;
	  }
	  return main_core.Loc.getMessage(messagePhrase);
	}
	function _getPopupContent2() {
	  const editorContainer = main_core.Tag.render(_t || (_t = _`<div></div>`));
	  const content = main_core.Tag.render(_t2 || (_t2 = _`<div class="crm-activity__todo-create-notification">
			<div class="crm-activity__todo-create-notification_title">${0}</div>
			<div class="crm-activity__todo-create-notification_content">
				<div class="crm-activity__todo-create-notification_description">${0}</div>
				${0}
			</div>
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _getPopupTitle)[_getPopupTitle](), babelHelpers.classPrivateFieldLooseBase(this, _getPopupDescription)[_getPopupDescription](), editorContainer);
	  babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor] = new crm_activity_todoEditor.TodoEditor({
	    container: editorContainer,
	    ownerTypeId: babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId],
	    ownerId: babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId],
	    currentUser: babelHelpers.classPrivateFieldLooseBase(this, _timeline)[_timeline].getCurrentUser(),
	    events: {
	      onChangeDescription: babelHelpers.classPrivateFieldLooseBase(this, _onChangeDescription)[_onChangeDescription].bind(this),
	      onSaveHotkeyPressed: babelHelpers.classPrivateFieldLooseBase(this, _onSaveHotkeyPressed)[_onSaveHotkeyPressed].bind(this),
	      onChangeUploaderContainerSize: babelHelpers.classPrivateFieldLooseBase(this, _onChangeUploaderContainerSize)[_onChangeUploaderContainerSize].bind(this)
	    },
	    borderColor: crm_activity_todoEditor.TodoEditor.BorderColor.PRIMARY
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].show();
	  return content;
	}
	function _getPopupButtons2() {
	  return [new ui_buttons.SaveButton({
	    id: SAVE_BUTTON_ID,
	    round: true,
	    state: babelHelpers.classPrivateFieldLooseBase(this, _toDoEditor)[_toDoEditor].getDescription() ? null : ui_buttons.ButtonState.DISABLED,
	    events: {
	      click: babelHelpers.classPrivateFieldLooseBase(this, _saveTodo)[_saveTodo].bind(this)
	    }
	  }), new ui_buttons.CancelButton({
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCEL'),
	    color: ui_buttons.ButtonColor.LIGHT_BORDER,
	    id: CANCEL_BUTTON_ID,
	    round: true,
	    events: {
	      click: babelHelpers.classPrivateFieldLooseBase(this, _closePopup)[_closePopup].bind(this)
	    }
	  }), new ui_buttons.Button({
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP'),
	    color: ui_buttons.ButtonColor.LINK,
	    id: SKIP_BUTTON_ID,
	    dropdown: true,
	    menu: {
	      closeByEsc: true,
	      items: babelHelpers.classPrivateFieldLooseBase(this, _getSkipMenuItems)[_getSkipMenuItems](),
	      minWidth: 233
	    }
	  })];
	}
	function _getSkipMenuItems2() {
	  const menuItems = [];
	  menuItems.push({
	    id: 'day',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_DAY'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'day')
	  });
	  menuItems.push({
	    id: 'week',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_WEEK'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'week')
	  });
	  menuItems.push({
	    id: 'month',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_MONTH'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'month')
	  });
	  menuItems.push({
	    id: 'forever',
	    text: main_core.Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOREVER'),
	    onclick: babelHelpers.classPrivateFieldLooseBase(this, _onSkipMenuItemSelect)[_onSkipMenuItemSelect].bind(this, 'forever')
	  });
	  return menuItems;
	}
	function _showCancelNotificationInParentWindow2() {
	  if (top.BX && top.BX.Runtime) {
	    const entityTypeId = babelHelpers.classPrivateFieldLooseBase(this, _entityTypeId)[_entityTypeId];
	    top.BX.Runtime.loadExtension('crm.activity.todo-notification-skip').then(exports => {
	      const skipProvider = new exports.TodoNotificationSkip({
	        entityTypeId
	      });
	      skipProvider.showCancelPeriodNotification();
	    });
	  }
	}

	exports.TodoCreateNotification = TodoCreateNotification;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Event,BX.Main,BX.Crm.Activity,BX.UI,BX.Crm.Activity,BX.Crm.Activity));
//# sourceMappingURL=todo-create-notification.bundle.js.map
