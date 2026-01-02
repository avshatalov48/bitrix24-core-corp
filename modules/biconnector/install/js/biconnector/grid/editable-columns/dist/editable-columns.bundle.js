/* eslint-disable */
this.BX = this.BX || {};
this.BX.BIConnector = this.BX.BIConnector || {};
(function (exports,main_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	var _editCell = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("editCell");
	var _buildEditor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildEditor");
	var _cancelEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cancelEdit");
	var _saveValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveValue");
	var _notifyErrors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("notifyErrors");
	class EditableColumnManager {
	  constructor(gridId, field) {
	    var _BX$Main$gridManager$;
	    Object.defineProperty(this, _notifyErrors, {
	      value: _notifyErrors2
	    });
	    Object.defineProperty(this, _saveValue, {
	      value: _saveValue2
	    });
	    Object.defineProperty(this, _cancelEdit, {
	      value: _cancelEdit2
	    });
	    Object.defineProperty(this, _buildEditor, {
	      value: _buildEditor2
	    });
	    Object.defineProperty(this, _editCell, {
	      value: _editCell2
	    });
	    this.gridId = gridId;
	    this.grid = (_BX$Main$gridManager$ = BX.Main.gridManager.getById(gridId)) == null ? void 0 : _BX$Main$gridManager$.instance;
	    this.columnId = field.name;
	    this.saveEndpoint = field.saveEndpoint;
	    this.onSave = field.onSave;
	    this.onValueCheck = field.onValueCheck;
	    if (!EditableColumnManager.instances.has(field.name)) {
	      EditableColumnManager.instances.set(field.name, this);
	    }
	  }
	  static init(gridId, fields) {
	    fields.forEach(field => {
	      new EditableColumnManager(gridId, field);
	    });
	  }
	  static editCell(columnId, rowId) {
	    const instance = EditableColumnManager.instances.get(columnId);
	    if (!instance) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(instance, _editCell)[_editCell](rowId);
	  }
	}
	function _editCell2(rowId) {
	  const row = this.grid.getRows().getById(rowId);
	  if (!row) {
	    return;
	  }
	  main_core.Dom.removeClass(row.getNode(), 'editable-column-edited');
	  const cell = row.getCellById(this.columnId);
	  let currentValue = row.getEditData()[this.columnId];
	  if (!currentValue) {
	    currentValue = '';
	  }
	  const wrapper = cell.querySelector('.editable-column-wrapper');
	  if (!wrapper) {
	    return;
	  }
	  const editor = babelHelpers.classPrivateFieldLooseBase(this, _buildEditor)[_buildEditor](rowId, currentValue);
	  const preview = wrapper.querySelector('.editable-column-preview');
	  if (preview) {
	    main_core.Dom.style(preview, {
	      display: 'none'
	    });
	  }
	  main_core.Dom.append(editor, wrapper);
	  const actionsClickHandler = () => {
	    main_core.Event.unbind(row.getActionsButton(), 'click', actionsClickHandler);
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelEdit)[_cancelEdit](rowId);
	  };
	  main_core.Event.bind(row.getActionsButton(), 'click', actionsClickHandler);
	}
	function _buildEditor2(rowId, value) {
	  const input = main_core.Tag.render(_t || (_t = _`<input class="main-grid-editor main-grid-editor-text" type="text">`));
	  input.value = value;
	  const applyButton = main_core.Tag.render(_t2 || (_t2 = _`
			<a>
				<i class="ui-icon-set --check"></i>
			</a>
		`));
	  const cancelButton = main_core.Tag.render(_t3 || (_t3 = _`
			<a>
				<i class="ui-icon-set --cross-60"></i>
			</a>
		`));
	  const onSave = newValue => {
	    main_core.Dom.removeClass(input, 'editable-column-input-danger');
	    if (!this.onValueCheck(newValue)) {
	      main_core.Dom.addClass(input, 'editable-column-input-danger');
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _saveValue)[_saveValue](rowId, newValue);
	  };
	  const onCancel = () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelEdit)[_cancelEdit](rowId);
	  };
	  main_core.Event.bind(applyButton, 'click', event => {
	    event.preventDefault();
	    onSave(input.value);
	  });
	  main_core.Event.bind(cancelButton, 'click', event => {
	    event.preventDefault();
	    onCancel();
	  });
	  main_core.Event.bind(input, 'keydown', event => {
	    if (event.keyCode === 13) {
	      onSave(input.value);
	      event.preventDefault();
	    } else if (event.keyCode === 27) {
	      onCancel();
	      event.preventDefault();
	    }
	  });
	  return main_core.Tag.render(_t4 || (_t4 = _`
			<div class="editable-column-wrapper__item editable-column-edit">
				${0}
				<div class="editable-column-wrapper__buttons-wrapper">
					<div class="editable-column-wrapper__buttons">
						${0}
						${0}
					</div>
				</div>
			</div>
		`), input, applyButton, cancelButton);
	}
	function _cancelEdit2(rowId) {
	  const row = this.grid.getRows().getById(rowId);
	  const cell = row.getCellById(this.columnId);
	  const wrapper = cell.querySelector('.editable-column-wrapper');
	  const editor = wrapper.querySelector('.editable-column-edit');
	  const preview = wrapper.querySelector('.editable-column-preview');
	  if (editor) {
	    editor.remove();
	  }
	  if (preview) {
	    main_core.Dom.style(preview, {
	      display: 'flex'
	    });
	  }
	}
	function _saveValue2(rowId, newValue) {
	  main_core.ajax.runAction(this.saveEndpoint, {
	    data: {
	      id: rowId,
	      columnId: this.columnId,
	      value: newValue
	    }
	  }).then(() => {
	    this.onSave(rowId, newValue);
	    const row = this.grid.getRows().getById(rowId);
	    const cell = row.getCellById(this.columnId);
	    const wrapper = cell.querySelector('.editable-column-wrapper');
	    const content = wrapper.querySelector('.editable-column-content');
	    main_core.Dom.addClass(row.getNode(), 'editable-column-edited');
	    const rowEditData = row.getEditData();
	    rowEditData[this.columnId] = newValue;
	    const editableData = this.grid.getParam('EDITABLE_DATA');
	    if (main_core.Type.isPlainObject(editableData)) {
	      editableData[row.getId()] = rowEditData;
	    }
	    if (content) {
	      content.innerText = newValue;
	      main_core.Dom.style(content, {
	        display: 'flex'
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _cancelEdit)[_cancelEdit](rowId);
	  }).catch(response => {
	    if (response.errors) {
	      babelHelpers.classPrivateFieldLooseBase(this, _notifyErrors)[_notifyErrors](response.errors);
	    }
	  });
	}
	function _notifyErrors2(errors) {
	  if (errors[0] && errors[0].message) {
	    BX.UI.Notification.Center.notify({
	      content: main_core.Text.encode(errors[0].message)
	    });
	  }
	}
	EditableColumnManager.instances = new Map();

	exports.EditableColumnManager = EditableColumnManager;

}((this.BX.BIConnector.Grid = this.BX.BIConnector.Grid || {}),BX));
//# sourceMappingURL=editable-columns.bundle.js.map
