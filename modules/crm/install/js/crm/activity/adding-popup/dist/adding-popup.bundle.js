this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,main_core,main_popup,ui_buttons,crm_activity_todoEditor,main_core_events) {
	'use strict';

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

	var _popup = /*#__PURE__*/new WeakMap();

	var _todoEditor = /*#__PURE__*/new WeakMap();

	var _eventEmitter = /*#__PURE__*/new WeakMap();

	var _createPopupIfNotExists = /*#__PURE__*/new WeakSet();

	var _saveAndClose = /*#__PURE__*/new WeakSet();

	var _actualizePopupLayout = /*#__PURE__*/new WeakSet();

	var _onChangeEditorDescription = /*#__PURE__*/new WeakSet();

	var _onEditorSaveHotkeyPressed = /*#__PURE__*/new WeakSet();

	let AddingPopup = /*#__PURE__*/function () {
	  function AddingPopup(entityTypeId, entityId, params) {
	    babelHelpers.classCallCheck(this, AddingPopup);

	    _classPrivateMethodInitSpec(this, _onEditorSaveHotkeyPressed);

	    _classPrivateMethodInitSpec(this, _onChangeEditorDescription);

	    _classPrivateMethodInitSpec(this, _actualizePopupLayout);

	    _classPrivateMethodInitSpec(this, _saveAndClose);

	    _classPrivateMethodInitSpec(this, _createPopupIfNotExists);

	    _classPrivateFieldInitSpec(this, _entityId, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec(this, _entityTypeId, {
	      writable: true,
	      value: null
	    });

	    _classPrivateFieldInitSpec(this, _popup, {
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
	    value: function show(bindElement) {
	      const popup = _classPrivateMethodGet(this, _createPopupIfNotExists, _createPopupIfNotExists2).call(this);

	      popup.setBindElement(bindElement);

	      if (popup.isShown()) {
	        return;
	      }

	      if (!popup.getContentContainer().hasChildNodes()) {
	        // just created, initialize
	        babelHelpers.classPrivateFieldSet(this, _todoEditor, new crm_activity_todoEditor.TodoEditor({
	          container: popup.getContentContainer(),
	          ownerTypeId: babelHelpers.classPrivateFieldGet(this, _entityTypeId),
	          ownerId: babelHelpers.classPrivateFieldGet(this, _entityId),
	          events: {
	            onChangeDescription: _classPrivateMethodGet(this, _onChangeEditorDescription, _onChangeEditorDescription2).bind(this),
	            onSaveHotkeyPressed: _classPrivateMethodGet(this, _onEditorSaveHotkeyPressed, _onEditorSaveHotkeyPressed2).bind(this)
	          }
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
	        popup.subscribeOnce('onFirstShow', () => babelHelpers.classPrivateFieldGet(this, _todoEditor).show());
	        popup.subscribe('onAfterShow', () => {
	          _classPrivateMethodGet(this, _actualizePopupLayout, _actualizePopupLayout2).call(this, babelHelpers.classPrivateFieldGet(this, _todoEditor).getDescription());

	          babelHelpers.classPrivateFieldGet(this, _todoEditor).setFocused();
	        });
	        popup.subscribe('onAfterClose', () => {
	          babelHelpers.classPrivateFieldGet(this, _todoEditor).resetToDefaults();
	          babelHelpers.classPrivateFieldGet(this, _eventEmitter).emit('onClose');
	        });
	      }

	      popup.show();
	    }
	  }]);
	  return AddingPopup;
	}();

	function _createPopupIfNotExists2() {
	  if (!babelHelpers.classPrivateFieldGet(this, _popup)) {
	    babelHelpers.classPrivateFieldSet(this, _popup, main_popup.PopupManager.create({
	      id: `kanban_planner_menu_${babelHelpers.classPrivateFieldGet(this, _entityId)}`,
	      overlay: {
	        opacity: 0
	      },
	      isScrollBlock: true,
	      className: 'crm-activity-adding-popup',
	      closeByEsc: true,
	      closeIcon: false,
	      angle: {
	        offset: 27
	      },
	      minWidth: 500,
	      padding: 16,
	      minHeight: 150
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

	exports.AddingPopup = AddingPopup;

}((this.BX.Crm.Activity = this.BX.Crm.Activity || {}),BX,BX.Main,BX.UI,BX.Crm.Activity,BX.Event));
//# sourceMappingURL=adding-popup.bundle.js.map
