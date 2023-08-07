this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_popup,ui_buttons,crm_activity_todoEditor,main_core_events,ui_notification) {
	'use strict';

	let _ = t => t,
	  _t;
	function _classPrivateMethodInitSpec(obj, privateSet) { _checkPrivateRedeclaration(obj, privateSet); privateSet.add(obj); }
	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }
	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }
	function _classPrivateMethodGet(receiver, privateSet, fn) { if (!privateSet.has(receiver)) { throw new TypeError("attempted to get private field on non-instance"); } return fn; }

	/**
	 * @event onSave
	 * @event onClose
	 */
	var _entityId = /*#__PURE__*/new WeakMap();
	var _entityTypeId = /*#__PURE__*/new WeakMap();
	var _currentUser = /*#__PURE__*/new WeakMap();
	var _popup = /*#__PURE__*/new WeakMap();
	var _popupContainer = /*#__PURE__*/new WeakMap();
	var _todoEditor = /*#__PURE__*/new WeakMap();
	var _eventEmitter = /*#__PURE__*/new WeakMap();
	var _prepareAndShowPopup = /*#__PURE__*/new WeakSet();
	var _fetchNearActivity = /*#__PURE__*/new WeakSet();
	var _createPopupIfNotExists = /*#__PURE__*/new WeakSet();
	var _saveAndClose = /*#__PURE__*/new WeakSet();
	var _actualizePopupLayout = /*#__PURE__*/new WeakSet();
	var _onChangeEditorDescription = /*#__PURE__*/new WeakSet();
	var _onEditorSaveHotkeyPressed = /*#__PURE__*/new WeakSet();
	var _onChangeUploaderContainerSize = /*#__PURE__*/new WeakSet();
	let AddingPopup = /*#__PURE__*/function () {
	  function AddingPopup(entityTypeId, entityId, currentUser, params) {
	    babelHelpers.classCallCheck(this, AddingPopup);
	    _classPrivateMethodInitSpec(this, _onChangeUploaderContainerSize);
	    _classPrivateMethodInitSpec(this, _onEditorSaveHotkeyPressed);
	    _classPrivateMethodInitSpec(this, _onChangeEditorDescription);
	    _classPrivateMethodInitSpec(this, _actualizePopupLayout);
	    _classPrivateMethodInitSpec(this, _saveAndClose);
	    _classPrivateMethodInitSpec(this, _createPopupIfNotExists);
	    _classPrivateMethodInitSpec(this, _fetchNearActivity);
	    _classPrivateMethodInitSpec(this, _prepareAndShowPopup);
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
	    _classPrivateFieldInitSpec(this, _popup, {
	      writable: true,
	      value: null
	    });
	    _classPrivateFieldInitSpec(this, _popupContainer, {
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
	    babelHelpers.classPrivateFieldSet(this, _entityId, main_core.Text.toInteger(entityId));
	    babelHelpers.classPrivateFieldSet(this, _entityTypeId, main_core.Text.toInteger(entityTypeId));
	    babelHelpers.classPrivateFieldSet(this, _currentUser, currentUser);
	    babelHelpers.classPrivateFieldSet(this, _eventEmitter, new main_core_events.EventEmitter());
	    babelHelpers.classPrivateFieldGet(this, _eventEmitter).setEventNamespace('Crm.Activity.AddingPopup');
	    if (!main_core.Type.isPlainObject(params)) {
	      params = {};
	    }
	    if (main_core.Type.isObject(params.events)) {
	      for (const eventName in params.events) {
	        if (main_core.Type.isFunction(params.events[eventName])) {
	          babelHelpers.classPrivateFieldGet(this, _eventEmitter).subscribe(eventName, params.events[eventName]);
	        }
	      }
	    }
	  }
	  babelHelpers.createClass(AddingPopup, [{
	    key: "show",
	    value: function show(bindElement, mode = crm_activity_todoEditor.TodoEditorMode.ADD) {
	      const popup = _classPrivateMethodGet(this, _createPopupIfNotExists, _createPopupIfNotExists2).call(this);
	      popup.setBindElement(bindElement);
	      if (popup.isShown()) {
	        return;
	      }
	      if (!babelHelpers.classPrivateFieldGet(this, _popupContainer).hasChildNodes()) {
	        // just created, initialize
	        babelHelpers.classPrivateFieldSet(this, _todoEditor, new crm_activity_todoEditor.TodoEditor({
	          container: babelHelpers.classPrivateFieldGet(this, _popupContainer),
	          ownerTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	          ownerId: babelHelpers.classPrivateFieldGet(this, _entityId),
	          currentUser: babelHelpers.classPrivateFieldGet(this, _currentUser),
	          events: {
	            onChangeDescription: _classPrivateMethodGet(this, _onChangeEditorDescription, _onChangeEditorDescription2).bind(this),
	            onSaveHotkeyPressed: _classPrivateMethodGet(this, _onEditorSaveHotkeyPressed, _onEditorSaveHotkeyPressed2).bind(this),
	            onChangeUploaderContainerSize: _classPrivateMethodGet(this, _onChangeUploaderContainerSize, _onChangeUploaderContainerSize2).bind(this)
	          },
	          popupMode: true
	        }));
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
	          _classPrivateMethodGet(this, _actualizePopupLayout, _actualizePopupLayout2).call(this, babelHelpers.classPrivateFieldGet(this, _todoEditor).getDescription());
	          babelHelpers.classPrivateFieldGet(this, _todoEditor).setFocused();
	        });
	        popup.subscribe('onAfterClose', () => {
	          babelHelpers.classPrivateFieldGet(this, _todoEditor).resetToDefaults().then(() => {
	            babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onClose');
	          });
	        });
	        popup.subscribe('onShow', () => {
	          const {
	            mode,
	            activity
	          } = popup.params;
	          if (mode === crm_activity_todoEditor.TodoEditorMode.UPDATE && activity) {
	            babelHelpers.classPrivateFieldGet(this, _todoEditor).setMode(mode).setActivityId(activity.id).setDescription(activity.description).setDeadline(activity.deadline);
	            if (main_core.Type.isArrayFilled(activity.storageElementIds)) {
	              babelHelpers.classPrivateFieldGet(this, _todoEditor).setStorageElementIds(activity.storageElementIds);
	            }
	          }
	        });
	      }
	      _classPrivateMethodGet(this, _prepareAndShowPopup, _prepareAndShowPopup2).call(this, popup, mode);
	    }
	  }, {
	    key: "bindPopup",
	    value: function bindPopup(bindElement) {
	      if (!babelHelpers.classPrivateFieldGet(this, _popup)) {
	        return;
	      }
	      if (bindElement !== babelHelpers.classPrivateFieldGet(this, _popup).bindElement) {
	        babelHelpers.classPrivateFieldGet(this, _popup).setBindElement(bindElement);
	      }
	    }
	  }]);
	  return AddingPopup;
	}();
	function _prepareAndShowPopup2(popup, mode = crm_activity_todoEditor.TodoEditorMode.ADD) {
	  popup.params.mode = mode;
	  if (mode === crm_activity_todoEditor.TodoEditorMode.ADD) {
	    popup.show();
	    return;
	  }
	  if (mode === crm_activity_todoEditor.TodoEditorMode.UPDATE) {
	    _classPrivateMethodGet(this, _fetchNearActivity, _fetchNearActivity2).call(this).then(data => {
	      if (data) {
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
	      data
	    }) => resolve(data)).catch(response => {
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
	    babelHelpers.classPrivateFieldSet(this, _popupContainer, main_core.Tag.render(_t || (_t = _`<div class="crm-activity-adding-popup-container"></div>`)));
	    babelHelpers.classPrivateFieldSet(this, _popup, new main_popup.Popup({
	      id: `kanban_planner_menu_${babelHelpers.classPrivateFieldGet(this, _entityId)}`,
	      overlay: {
	        opacity: 0
	      },
	      content: babelHelpers.classPrivateFieldGet(this, _popupContainer),
	      cacheable: false,
	      isScrollBlock: true,
	      className: 'crm-activity-adding-popup',
	      closeByEsc: true,
	      closeIcon: false,
	      angle: {
	        offset: 27
	      },
	      padding: 16,
	      minWidth: 500,
	      maxWidth: 550,
	      minHeight: 150,
	      maxHeight: 400
	    }));
	  }
	  return babelHelpers.classPrivateFieldGet(this, _popup);
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
	    const saveButton = babelHelpers.classPrivateFieldGet(this, _popup).getButton('save');
	    if (!description.length && saveButton && !saveButton.getState()) {
	      saveButton.setState(ui_buttons.ButtonState.DISABLED);
	    } else if (description.length && saveButton && saveButton.getState() === ui_buttons.ButtonState.DISABLED) {
	      saveButton.setState(null);
	    }
	  }
	}
	function _onChangeEditorDescription2(event) {
	  const {
	    description
	  } = event.getData();
	  _classPrivateMethodGet(this, _actualizePopupLayout, _actualizePopupLayout2).call(this, description);
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

	exports.AddingPopup = AddingPopup;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Main,BX.UI,BX.Crm.Activity,BX.Event,BX));
//# sourceMappingURL=adding-popup.bundle.js.map
