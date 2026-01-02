/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core_events,tasks_v2_lib_entitySelectorDialog) {
	'use strict';

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _createDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDialog");
	var _select = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("select");
	var _hide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hide");
	var _loadUsers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadUsers");
	class UserSelectorDialog extends main_core_events.EventEmitter {
	  constructor(params) {
	    super();
	    Object.defineProperty(this, _loadUsers, {
	      value: _loadUsers2
	    });
	    Object.defineProperty(this, _hide, {
	      value: _hide2
	    });
	    Object.defineProperty(this, _select, {
	      value: _select2
	    });
	    Object.defineProperty(this, _createDialog, {
	      value: _createDialog2
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dialog, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('Tasks:UserSelectorDialog');
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog] = babelHelpers.classPrivateFieldLooseBase(this, _createDialog)[_createDialog]();
	  }
	  getDialog() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog];
	  }
	  show(targetNode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getPopup();
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].setTargetNode(targetNode);
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].show();
	  }
	  selectItemsByIds(items) {
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].selectItemsByIds(items);
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].destroy();
	  }
	}
	function _createDialog2() {
	  return new tasks_v2_lib_entitySelectorDialog.EntitySelectorDialog({
	    id: `tasks-user-selector-dialog-${babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].taskId}`,
	    preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].preselected,
	    ...babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].dialogOptions,
	    events: {
	      'Item:onSelect': babelHelpers.classPrivateFieldLooseBase(this, _select)[_select].bind(this),
	      'Item:onDeselect': babelHelpers.classPrivateFieldLooseBase(this, _select)[_select].bind(this),
	      onHide: babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide].bind(this),
	      onLoad: babelHelpers.classPrivateFieldLooseBase(this, _loadUsers)[_loadUsers].bind(this),
	      ...babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].events
	    }
	  });
	}
	function _select2() {
	  this.emit('select', babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems());
	}
	function _hide2() {
	  this.emit('hide', babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems());
	}
	function _loadUsers2() {
	  const selectedItems = babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems();
	  if (!selectedItems || selectedItems.length === 0) {
	    return;
	  }
	  const users = selectedItems.map(item => {
	    return {
	      id: item.getId(),
	      name: item.getTitle(),
	      image: item.getAvatar(),
	      type: item.getEntityType()
	    };
	  });
	  this.emit('selectedUsersLoaded', users);
	}

	exports.UserSelectorDialog = UserSelectorDialog;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX.Event,BX.Tasks.V2.Lib));
//# sourceMappingURL=user-selector-dialog.bundle.js.map
