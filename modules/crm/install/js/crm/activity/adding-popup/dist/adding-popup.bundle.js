/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,crm_activity_todoEditorV2,main_core,main_core_events,main_popup,ui_buttons,ui_notification) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }
	var _entityId = /*#__PURE__*/new WeakMap();
	var _entityTypeId = /*#__PURE__*/new WeakMap();
	var _currentUser = /*#__PURE__*/new WeakMap();
	var _pingSettings = /*#__PURE__*/new WeakMap();
	var _calendarSettings = /*#__PURE__*/new WeakMap();
	var _colorSettings = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _popupContainer = /*#__PURE__*/new WeakMap();
	var _popupToDoEditorContainer = /*#__PURE__*/new WeakMap();
	var _todoEditor = /*#__PURE__*/new WeakMap();
	var _eventEmitter = /*#__PURE__*/new WeakMap();
	var _context = /*#__PURE__*/new WeakMap();
	var _createToDoEditor = /*#__PURE__*/new WeakSet();
	var _prepareAndShowPopup = /*#__PURE__*/new WeakSet();
	var _fetchNearActivity = /*#__PURE__*/new WeakSet();
	var _createPopupIfNotExists = /*#__PURE__*/new WeakSet();
	var _getPopupTitle = /*#__PURE__*/new WeakSet();
	var _getPopupParams = /*#__PURE__*/new WeakSet();
	var _saveAndClose = /*#__PURE__*/new WeakSet();
	var _actualizePopupLayout = /*#__PURE__*/new WeakSet();
	var _onEditorSaveHotkeyPressed = /*#__PURE__*/new WeakSet();
	var _onChangeUploaderContainerSize = /*#__PURE__*/new WeakSet();
	var _onFocus = /*#__PURE__*/new WeakSet();
	/**
	 * @event onSave
	 * @event onClose
	 */
	let AddingPopup = /*#__PURE__*/function () {
	  function AddingPopup(entityTypeId, entityId, currentUser, settings, _params) {
	    babelHelpers.classCallCheck(this, AddingPopup);
	    _classPrivateMethodInitSpec(this, _onFocus);
	    _classPrivateMethodInitSpec(this, _onChangeUploaderContainerSize);
	    _classPrivateMethodInitSpec(this, _onEditorSaveHotkeyPressed);
	    _classPrivateMethodInitSpec(this, _actualizePopupLayout);
	    _classPrivateMethodInitSpec(this, _saveAndClose);
	    _classPrivateMethodInitSpec(this, _getPopupParams);
	    _classPrivateMethodInitSpec(this, _getPopupTitle);
	    _classPrivateMethodInitSpec(this, _createPopupIfNotExists);
	    _classPrivateMethodInitSpec(this, _fetchNearActivity);
	    _classPrivateMethodInitSpec(this, _prepareAndShowPopup);
	    _classPrivateMethodInitSpec(this, _createToDoEditor);
	    _classPrivateFieldInitSpec(this, _entityId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _currentUser, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _pingSettings, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _calendarSettings, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _colorSettings, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _popupContainer, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _popupToDoEditorContainer, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _todoEditor, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _eventEmitter, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _context, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldSet(this, _entityId, main_core.Text.toInteger(entityId));
	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, main_core.Text.toInteger(entityTypeId));
	    babelHelpers.classPrivateFieldSet(this, _currentUser, currentUser);
	    babelHelpers.classPrivateFieldSet(this, _eventEmitter, new main_core_events.EventEmitter());
	    babelHelpers.classPrivateFieldGet(this, _eventEmitter).setEventNamespace('Crm.Activity.AddingPopup');
	    if (main_core.Type.isObject(settings)) {
	      var _settings$pingSetting, _settings$calendarSet, _settings$colorSettin;
	      babelHelpers.classPrivateFieldSet(this, _pingSettings, (_settings$pingSetting = settings.pingSettings) !== null && _settings$pingSetting !== void 0 ? _settings$pingSetting : null);
	      babelHelpers.classPrivateFieldSet(this, _calendarSettings, (_settings$calendarSet = settings.calendarSettings) !== null && _settings$calendarSet !== void 0 ? _settings$calendarSet : null);
	      babelHelpers.classPrivateFieldSet(this, _colorSettings, (_settings$colorSettin = settings.colorSettings) !== null && _settings$colorSettin !== void 0 ? _settings$colorSettin : null);
	    }
	    if (!main_core.Type.isPlainObject(_params)) {
	      // eslint-disable-next-line no-param-reassign
	      _params = {};
	    }
	    if (main_core.Type.isObject(_params.events)) {
	      for (const eventName in _params.events) {
	        if (main_core.Type.isFunction(_params.events[eventName])) {
	          babelHelpers.classPrivateFieldGet(this, _eventEmitter).subscribe(eventName, _params.events[eventName]);
	        }
	      }
	    }
	    if (main_core.Type.isPlainObject(_params.context)) {
	      babelHelpers.classPrivateFieldSet(this, _context, _params.context);
	    }
	  }
	  babelHelpers.createClass(AddingPopup, [{
	    key: "show",
	    value: async function show(mode = crm_activity_todoEditorV2.TodoEditorMode.ADD) {
	      const popup = _classPrivateMethodGet(this, _createPopupIfNotExists, _createPopupIfNotExists2).call(this);
	      if (popup.isShown()) {
	        return;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _popupToDoEditorContainer).hasChildNodes()) {
	        await _classPrivateMethodGet(this, _createToDoEditor, _createToDoEditor2).call(this);
	        popup.setButtons([new ui_buttons.SaveButton({
	          id: 'save',
	          color: ui_buttons.ButtonColor.PRIMARY,
	          size: ui_buttons.ButtonSize.EXTRA_SMALL,
	          round: true,
	          events: {
	            click: _classPrivateMethodGet(this, _saveAndClose, _saveAndClose2).bind(this)
	          }
	        }), new ui_buttons.CancelButton({
	          id: 'cancel',
	          size: ui_buttons.ButtonSize.EXTRA_SMALL,
	          round: true,
	          events: {
	            click: () => popup.close()
	          }
	        })]);
	        popup.subscribeOnce('onFirstShow', event => {
	          event.target.getZIndexComponent().setZIndex(1400);
	          babelHelpers.classPrivateFieldGet(this, _todoEditor).show();
	        });
	        popup.subscribe('onAfterShow', () => {
	          _classPrivateMethodGet(this, _actualizePopupLayout, _actualizePopupLayout2).call(this);
	          babelHelpers.classPrivateFieldGet(this, _todoEditor).setFocused();
	        });
	        popup.subscribe('onAfterClose', () => {
	          void babelHelpers.classPrivateFieldGet(this, _todoEditor).resetToDefaults().then(() => {
	            babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onClose');
	          });
	        });
	        popup.subscribe('onShow', () => {
	          const {
	            mode: todoEditorMode,
	            activity
	          } = popup.params;
	          if (todoEditorMode === crm_activity_todoEditorV2.TodoEditorMode.UPDATE && activity) {
	            babelHelpers.classPrivateFieldGet(this, _todoEditor).setMode(todoEditorMode).setActivityId(activity.id).setDescription(activity.description).setDeadline(activity.deadline);
	          }
	        });
	      }
	      _classPrivateMethodGet(this, _prepareAndShowPopup, _prepareAndShowPopup2).call(this, popup, mode);
	    }
	  }]);
	  return AddingPopup;
	}();
	async function _createToDoEditor2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _analytics$c_section, _analytics$c_sub_sect;
	  // just created, initialize
	  const params = {
	    container: babelHelpers.classPrivateFieldGet(this, _popupToDoEditorContainer),
	    ownerTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	    ownerId: babelHelpers.classPrivateFieldGet(this, _entityId),
	    currentUser: babelHelpers.classPrivateFieldGet(this, _currentUser),
	    pingSettings: babelHelpers.classPrivateFieldGet(this, _pingSettings),
	    events: {
	      onSaveHotkeyPressed: _classPrivateMethodGet(this, _onEditorSaveHotkeyPressed, _onEditorSaveHotkeyPressed2).bind(this),
	      onChangeUploaderContainerSize: _classPrivateMethodGet(this, _onChangeUploaderContainerSize, _onChangeUploaderContainerSize2).bind(this),
	      onFocus: _classPrivateMethodGet(this, _onFocus, _onFocus2).bind(this)
	    },
	    popupMode: true
	  };
	  const analytics = (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldGet(this, _context)) === null || _babelHelpers$classPr2 === void 0 ? void 0 : _babelHelpers$classPr2.analytics) !== null && _babelHelpers$classPr !== void 0 ? _babelHelpers$classPr : {};
	  const section = (_analytics$c_section = analytics.c_section) !== null && _analytics$c_section !== void 0 ? _analytics$c_section : null;
	  const subSection = (_analytics$c_sub_sect = analytics.c_sub_section) !== null && _analytics$c_sub_sect !== void 0 ? _analytics$c_sub_sect : null;
	  params.calendarSettings = babelHelpers.classPrivateFieldGet(this, _calendarSettings);
	  params.colorSettings = babelHelpers.classPrivateFieldGet(this, _colorSettings);
	  params.defaultDescription = '';
	  params.analytics = {
	    section,
	    subSection
	  };
	  babelHelpers.classPrivateFieldSet(this, _todoEditor, new crm_activity_todoEditorV2.TodoEditorV2(params));
	}
	function _prepareAndShowPopup2(popup, mode = crm_activity_todoEditorV2.TodoEditorMode.ADD) {
	  // eslint-disable-next-line no-param-reassign
	  popup.params.mode = mode;
	  if (mode === crm_activity_todoEditorV2.TodoEditorMode.ADD) {
	    popup.show();
	    return;
	  }
	  if (mode === crm_activity_todoEditorV2.TodoEditorMode.UPDATE) {
	    void _classPrivateMethodGet(this, _fetchNearActivity, _fetchNearActivity2).call(this).then(data => {
	      if (data) {
	        // eslint-disable-next-line no-param-reassign
	        popup.params.activity = data;
	        popup.show();
	      }
	    });
	    return;
	  }
	  console.error('Wrong TodoEditor mode');
	}
	function _fetchNearActivity2() {
	  const data = {
	    ownerTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	    ownerId: babelHelpers.classPrivateFieldGet(this, _entityId)
	  };
	  return new Promise((resolve, reject) => {
	    main_core.ajax.runAction('crm.activity.todo.getNearest', {
	      data
	    }).then(({
	      data: responseData
	    }) => resolve(responseData)).catch(response => {
	      ui_notification.UI.Notification.Center.notify({
	        content: response.errors[0].message,
	        autoHideDelay: 5000
	      });
	      reject();
	    });
	  });
	}
	function _createPopupIfNotExists2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _popup) || babelHelpers.classPrivateFieldGet(this, _popup).isDestroyed()) {
	    babelHelpers.classPrivateFieldSet(this, _popupToDoEditorContainer, main_core.Tag.render(_t || (_t = _`<div></div>`)));
	    babelHelpers.classPrivateFieldSet(this, _popupContainer, main_core.Tag.render(_t2 || (_t2 = _`
				<div class="crm-activity-adding-popup-container">
					${0}
					${0}
				</div>
			`), _classPrivateMethodGet(this, _getPopupTitle, _getPopupTitle2).call(this), babelHelpers.classPrivateFieldGet(this, _popupToDoEditorContainer)));
	    babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup(_classPrivateMethodGet(this, _getPopupParams, _getPopupParams2).call(this)));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _popup);
	}
	function _getPopupTitle2() {
	  return main_core.Tag.render(_t3 || (_t3 = _`
			<div class="crm-activity-adding-popup-title">
				${0}
			</div>
		`), main_core.Loc.getMessage('CRM_ACTIVITY_ADDING_POPUP_TITLE'));
	}
	function _getPopupParams2() {
	  const {
	    innerWidth
	  } = window;
	  return {
	    id: `kanban_planner_menu_${babelHelpers.classPrivateFieldGet(this, _entityId)}`,
	    content: babelHelpers.classPrivateFieldGet(this, _popupContainer),
	    cacheable: false,
	    isScrollBlock: true,
	    className: 'crm-activity-adding-popup',
	    closeByEsc: true,
	    closeIcon: false,
	    padding: 16,
	    minWidth: 537,
	    width: Math.round(innerWidth * 0.45),
	    maxWidth: 737,
	    minHeight: 150,
	    maxHeight: 482,
	    overlay: {
	      opacity: 50
	    }
	  };
	}
	function _saveAndClose2() {
	  if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	    const saveButton = babelHelpers.classPrivateFieldGet(this, _popup).getButton('save');
	    if (saveButton.getState()) {
	      return; // button is disabled
	    }

	    saveButton === null || saveButton === void 0 ? void 0 : saveButton.setWaiting(true);
	    babelHelpers.classPrivateFieldGet(this, _todoEditor).save().then(() => {
	      babelHelpers.classPrivateFieldGet(this, _popup).close();
	      babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onSave');
	    }).catch(() => {}).finally(() => saveButton === null || saveButton === void 0 ? void 0 : saveButton.setWaiting(false));
	  }
	}
	function _actualizePopupLayout2(description) {
	  if (babelHelpers.classPrivateFieldGet(this, _popup) && babelHelpers.classPrivateFieldGet(this, _popup).isShown()) {
	    babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onActualizePopupLayout', {
	      entityId: babelHelpers.classPrivateFieldGet(this, _entityId)
	    });
	    babelHelpers.classPrivateFieldGet(this, _popup).adjustPosition({
	      forceBindPosition: true
	    });
	  }
	}
	function _onEditorSaveHotkeyPressed2() {
	  _classPrivateMethodGet(this, _saveAndClose, _saveAndClose2).call(this);
	}
	function _onChangeUploaderContainerSize2() {
	  if (babelHelpers.classPrivateFieldGet(this, _popup)) {
	    babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onActualizePopupLayout', {
	      entityId: babelHelpers.classPrivateFieldGet(this, _entityId)
	    });
	    babelHelpers.classPrivateFieldGet(this, _popup).adjustPosition();
	  }
	}
	function _onFocus2() {
	  setTimeout(() => {
	    const popup = _classPrivateMethodGet(this, _createPopupIfNotExists, _createPopupIfNotExists2).call(this);
	    popup.adjustPosition({
	      forceBindPosition: true
	    });
	  }, 0);
	}

	exports.AddingPopup = AddingPopup;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX.Crm.Activity,BX,BX.Event,BX.Main,BX.UI,BX));
//# sourceMappingURL=adding-popup.bundle.js.map
