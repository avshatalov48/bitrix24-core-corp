/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,ui_buttons,ui_entitySelector,ui_notification,main_core) {
	'use strict';

	var _parentEntityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("parentEntityId");
	var _parentEntityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("parentEntityTypeId");
	var _childEntityTypeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("childEntityTypeId");
	var _gridId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("gridId");
	class Binder {
	  constructor(parentEntityTypeId, parentEntityId, childEntityTypeId, gridId) {
	    Object.defineProperty(this, _parentEntityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _parentEntityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _childEntityTypeId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _gridId, {
	      writable: true,
	      value: void 0
	    });
	    // eslint-disable-next-line no-param-reassign
	    parentEntityTypeId = main_core.Text.toInteger(parentEntityTypeId);
	    if (parentEntityTypeId > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _parentEntityTypeId)[_parentEntityTypeId] = parentEntityTypeId;
	    }

	    // eslint-disable-next-line no-param-reassign
	    parentEntityId = main_core.Text.toInteger(parentEntityId);
	    if (parentEntityId > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _parentEntityId)[_parentEntityId] = parentEntityId;
	    }

	    // eslint-disable-next-line no-param-reassign
	    childEntityTypeId = main_core.Text.toInteger(childEntityTypeId);
	    if (childEntityTypeId > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _childEntityTypeId)[_childEntityTypeId] = childEntityTypeId;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId] = gridId;
	  }
	  getId() {
	    return `relation-${babelHelpers.classPrivateFieldLooseBase(this, _parentEntityTypeId)[_parentEntityTypeId]}-${babelHelpers.classPrivateFieldLooseBase(this, _parentEntityId)[_parentEntityId]}-${babelHelpers.classPrivateFieldLooseBase(this, _childEntityTypeId)[_childEntityTypeId]}`;
	  }
	  getParentEntityId() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _parentEntityId)[_parentEntityId]) != null ? _babelHelpers$classPr : null;
	  }
	  getParenEntityTypeId() {
	    var _babelHelpers$classPr2;
	    return (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _parentEntityTypeId)[_parentEntityTypeId]) != null ? _babelHelpers$classPr2 : null;
	  }
	  getChildEntityTypeId() {
	    var _babelHelpers$classPr3;
	    return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _childEntityTypeId)[_childEntityTypeId]) != null ? _babelHelpers$classPr3 : null;
	  }
	  async bind(selectedIds) {
	    const data = {
	      parentEntityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _parentEntityTypeId)[_parentEntityTypeId],
	      parentEntityId: babelHelpers.classPrivateFieldLooseBase(this, _parentEntityId)[_parentEntityId],
	      childEntityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _childEntityTypeId)[_childEntityTypeId],
	      selectedIds
	    };

	    // eslint-disable-next-line no-async-promise-executor
	    return new Promise(async (resolve, reject) => {
	      main_core.ajax.runAction('crm.controller.item.relation.update', {
	        data
	      }).then(response => {
	        resolve(response);
	        this.refreshLayout();
	      }).catch(response => {
	        if (response.errors) {
	          response.errors.forEach(({
	            message
	          }) => {
	            this.showError(message);
	          });
	        }
	      });
	    });
	  }
	  showError(message) {
	    ui_notification.UI.Notification.Center.notify({
	      content: message,
	      autoHideDelay: 5000
	    });
	  }
	  refreshLayout() {
	    BX.Main.gridManager.getInstanceById(babelHelpers.classPrivateFieldLooseBase(this, _gridId)[_gridId]).reload();
	  }
	}

	var _dialogProp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialogProp");
	var _binder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("binder");
	var _target = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("target");
	var _dialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dialog");
	var _linking = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linking");
	class EntitySelector {
	  constructor(binder, target = null) {
	    Object.defineProperty(this, _linking, {
	      value: _linking2
	    });
	    Object.defineProperty(this, _dialog, {
	      get: _get_dialog,
	      set: void 0
	    });
	    Object.defineProperty(this, _dialogProp, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _binder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _target, {
	      writable: true,
	      value: void 0
	    });
	    if (binder instanceof Binder) {
	      babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder] = binder;
	    }
	    if (main_core.Type.isDomNode(target) || main_core.Type.isNil(target)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _target)[_target] = target;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder]) {
	      console.error('Invalid constructor params:', {
	        binder: binder
	      });
	      throw new Error('Invalid constructor params');
	    }
	  }
	  show() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].subscribeOnce('onShow', resolve);
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].show();
	    });
	  }
	  hide() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].subscribeOnce('onHide', resolve);
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].hide();
	    });
	  }
	  destroy() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].destroy();
	      resolve();
	    });
	  }
	}
	function _get_dialog() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp];
	  }
	  const applyButton = new ui_buttons.ApplyButton({
	    color: ui_buttons.ButtonColor.SUCCESS,
	    onclick: () => {
	      void this.hide();
	      babelHelpers.classPrivateFieldLooseBase(this, _linking)[_linking]();
	    }
	  });
	  const cancelButton = new ui_buttons.CancelButton({
	    onclick: () => {
	      void this.hide();
	    }
	  });
	  const childEntityTypeName = BX.CrmEntityType.resolveName(babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder].getChildEntityTypeId());
	  babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp] = new ui_entitySelector.Dialog({
	    targetNode: babelHelpers.classPrivateFieldLooseBase(this, _target)[_target],
	    enableSearch: true,
	    context: `crm.binder.entity-selector.for-${childEntityTypeName}`,
	    entities: [{
	      id: childEntityTypeName.startsWith(BX.CrmEntityType.dynamicTypeNamePrefix) ? 'DYNAMIC' : childEntityTypeName,
	      dynamicLoad: true,
	      dynamicSearch: true,
	      options: {
	        entityTypeId: babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder].getChildEntityTypeId(),
	        sourceTypeId: babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder].getParenEntityTypeId(),
	        notLinkedOnly: true,
	        withoutRecentItems: true,
	        withoutGlobalRecentItems: true
	      }
	    }],
	    footer: [applyButton.render(), cancelButton.render()],
	    footerOptions: {
	      containerStyles: {
	        display: 'flex',
	        'justify-content': 'center'
	      }
	    },
	    tagSelectorOptions: {
	      textBoxWidth: 565 // same as default dialog width
	    }
	  });

	  // this.#dialogProp.subscribe('Item:onSelect', this.#handleItemSelect.bind(this));

	  return babelHelpers.classPrivateFieldLooseBase(this, _dialogProp)[_dialogProp];
	}
	function _linking2() {
	  const data = [];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems().length === 0) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Loc.getMessage('ENTITY_BINDER_ITEMS_NOT_SELECTED'),
	      autoHideDelay: 5000
	    });
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _dialog)[_dialog].getSelectedItems().forEach(item => {
	    data.push(item.getId());
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _binder)[_binder].bind(data);
	}

	let instance = null;
	var _binders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("binders");
	class Manager {
	  constructor() {
	    Object.defineProperty(this, _binders, {
	      writable: true,
	      value: {}
	    });
	  }
	  static get Instance() {
	    if (instance === null) {
	      instance = new Manager();
	    }
	    return instance;
	  }
	  initializeBinder(parentEntityTypeId, parentEntityId, childEntityTypeId, gridId) {
	    const binder = new Binder(parentEntityTypeId, parentEntityId, childEntityTypeId, gridId);
	    babelHelpers.classPrivateFieldLooseBase(this, _binders)[_binders][binder.getId()] = binder;
	    return binder;
	  }
	  getBinder(binderId) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _binders)[_binders][binderId];
	  }
	  createRelatedSelector(binderId, target = null) {
	    const binder = this.getBinder(binderId);
	    if (!binder) {
	      console.error('Binder with given id not found', binderId, this);
	      return null;
	    }
	    if (!binder.getParenEntityTypeId() || !binder.getParentEntityId() || !binder.getChildEntityTypeId()) {
	      console.error('Not well configured binder', binderId, binder);
	      return null;
	    }
	    return new EntitySelector(binder, document.getElementById(target));
	  }
	}

	exports.EntitySelector = EntitySelector;
	exports.Binder = Binder;
	exports.Manager = Manager;

}((this.BX.Crm.Binder = this.BX.Crm.Binder || {}),BX.UI,BX.UI.EntitySelector,BX,BX));
//# sourceMappingURL=binder.bundle.js.map
