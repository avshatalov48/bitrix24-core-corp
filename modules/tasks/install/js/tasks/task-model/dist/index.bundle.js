/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports,tasks_taskModel,main_core,main_core_events) {
	'use strict';

	class ErrorCollection {
	  constructor(model = {}) {
	    this.errors = new Map();
	    this.model = model;
	  }
	  getErrors() {
	    return Object.fromEntries(this.errors);
	  }
	  setError(code, text) {
	    this.errors.set(code, {
	      code,
	      text
	    });
	    this.model.onErrorCollectionChange();
	    return this;
	  }
	  removeError(code) {
	    if (this.errors.has(code)) {
	      this.errors.delete(code);
	    }
	    this.model.onErrorCollectionChange();
	    return this;
	  }
	  clearErrors() {
	    this.errors.clear();
	    this.model.onErrorCollectionChange();
	    return this;
	  }
	  hasErrors() {
	    return this.errors.size > 0;
	  }
	}

	class FieldCollection {
	  constructor(model = {}) {
	    this.changedFields = new Map();
	    this.fields = new Map();
	    this.model = model;
	  }
	  getFields() {
	    return Object.fromEntries(this.fields);
	  }
	  getField(fieldName) {
	    return this.fields.get(fieldName);
	  }
	  setField(fieldName, value) {
	    const oldValue = this.fields.get(fieldName);
	    this.fields.set(fieldName, value);
	    if (!this.changedFields.has(fieldName) && oldValue !== value) {
	      this.changedFields.set(fieldName, oldValue);
	    }
	    return this;
	  }
	  isChanged() {
	    return this.changedFields.size > 0;
	  }
	  clearChanged(savingFieldNames = null) {
	    if (main_core.Type.isNil(savingFieldNames)) {
	      this.changedFields.clear();
	    } else {
	      savingFieldNames.forEach(name => {
	        this.removeFromChanged(name);
	      });
	    }
	    return this;
	  }
	  removeFromChanged(fieldName) {
	    this.changedFields.delete(fieldName);
	    return this;
	  }
	  getChangedFields() {
	    const changedFieldValues = {};
	    this.fields.forEach((value, key) => {
	      if (this.changedFields.has(key)) {
	        changedFieldValues[key] = value;
	      }
	    });
	    return {
	      ...changedFieldValues
	    };
	  }
	  getChangedValues() {
	    const changedFieldValues = {};
	    this.changedFields.forEach((value, key) => {
	      changedFieldValues[key] = value;
	    });
	    return {
	      ...changedFieldValues
	    };
	  }
	  initFields(fields) {
	    this.fields.clear();
	    this.clearChanged();
	    if (main_core.Type.isObject(fields)) {
	      Object.keys(fields).forEach(key => {
	        this.fields.set(key, fields[key]);
	      });
	    }
	    return this;
	  }
	}

	var _map = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("map");
	var _onChangeData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeData");
	class TaskCollection {
	  constructor() {
	    Object.defineProperty(this, _onChangeData, {
	      value: _onChangeData2
	    });
	    Object.defineProperty(this, _map, {
	      writable: true,
	      value: new Map()
	    });
	  }
	  init(map) {
	    this.clear();
	    map.forEach((item, index) => {
	      if (item['id'] > 0) {
	        let model = new tasks_taskModel.TaskModel({
	          fields: {
	            id: main_core.Text.toNumber(item.id),
	            title: item.title.toString()
	          }
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _map)[_map].set(main_core.Text.toNumber(index), model);
	      }
	    });
	  }
	  refreshByFilter(fields) {
	    return new Promise((resolve, reject) => {
	      const cmd = 'tasks.task.list';
	      let {
	        filter,
	        params
	      } = TaskCollection.internalize(fields);
	      if (Object.keys(filter).length <= 0) {
	        return Promise.reject({
	          status: 'error',
	          errors: ['filter is not set']
	        });
	      }
	      main_core.ajax.runAction(cmd, {
	        data: {
	          filter,
	          params,
	          start: -1
	        }
	      }).then(result => {
	        var _result$data$tasks;
	        let tasks = (_result$data$tasks = result.data.tasks) != null ? _result$data$tasks : null;
	        if (main_core.Type.isArrayFilled(tasks)) {
	          this.init(tasks);
	          babelHelpers.classPrivateFieldLooseBase(this, _onChangeData)[_onChangeData]();
	        }
	        resolve();
	      }).catch(reject);
	    });
	  }
	  static internalize(fields) {
	    const result = {
	      filter: {},
	      params: {}
	    };
	    try {
	      for (let name in fields) {
	        if (!fields.hasOwnProperty(name)) {
	          continue;
	        }
	        switch (name) {
	          case 'id':
	            result.filter.ID = fields[name];
	            break;
	          case 'returnAccess':
	            result.params.RETURN_ACCESS = fields[name];
	            break;
	          case 'siftThroughFilter':
	            result.params.SIFT_THROUGH_FILTER = TaskCollection.internalizeSiftThroughFilter(fields[name]);
	            break;
	        }
	      }
	    } catch (e) {}
	    return result;
	  }
	  static internalizeSiftThroughFilter(fields) {
	    const result = {};
	    try {
	      for (let name in fields) {
	        if (!fields.hasOwnProperty(name)) {
	          continue;
	        }
	        switch (name) {
	          case 'userId':
	            result.userId = fields[name];
	            break;
	          case 'groupId':
	            result.groupId = fields[name];
	            break;
	        }
	      }
	    } catch (e) {}
	    return result;
	  }
	  getById(id) {
	    for (let model of babelHelpers.classPrivateFieldLooseBase(this, _map)[_map].values()) {
	      if (model.getId() === main_core.Text.toNumber(id)) {
	        return model;
	      }
	    }
	  }
	  getFieldsById(id) {
	    var _this$getById;
	    return ((_this$getById = this.getById(id)) == null ? void 0 : _this$getById.getFields()) || 0;
	  }
	  getByIndex(index) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _map)[_map].get(main_core.Text.toNumber(index));
	  }
	  getFieldsByIndex(index) {
	    var _this$getByIndex;
	    return ((_this$getByIndex = this.getByIndex(index)) == null ? void 0 : _this$getByIndex.getFields()) || 0;
	  }
	  count() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _map)[_map].size;
	  }
	  clear() {
	    babelHelpers.classPrivateFieldLooseBase(this, _map)[_map].clear();
	    return this;
	  }
	}
	function _onChangeData2() {
	  main_core_events.EventEmitter.emit(this, 'BX.Tasks.TaskModel.Collection:onChangeData');
	}

	var _fieldCollection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fieldCollection");
	var _errorCollection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorCollection");
	class TaskModel {
	  constructor(options = {}) {
	    Object.defineProperty(this, _fieldCollection, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _errorCollection, {
	      writable: true,
	      value: null
	    });
	    this.options = options || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _errorCollection)[_errorCollection] = new ErrorCollection(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection] = new FieldCollection(this);
	    if (main_core.Type.isObject(options.fields)) {
	      this.initFields(options.fields, false);
	    }
	  }
	  getErrorCollection() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _errorCollection)[_errorCollection];
	  }
	  getFields() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].getFields();
	  }
	  getField(fieldName) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].getField(fieldName);
	  }
	  setField(fieldName, value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].setField(fieldName, value);
	    return this;
	  }
	  setFields(fields) {
	    Object.keys(fields).forEach(key => {
	      this.setField(key, fields[key]);
	    });
	    return this;
	  }
	  initFields(fields) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].initFields(fields);
	    return this;
	  }
	  removeField(fieldName) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].removeField(fieldName);
	    return this;
	  }
	  isChanged() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _fieldCollection)[_fieldCollection].isChanged();
	  }
	  getId() {
	    return this.getField('id');
	  }
	}

	exports.ErrorCollection = ErrorCollection;
	exports.FieldCollection = FieldCollection;
	exports.TaskCollection = TaskCollection;
	exports.TaskModel = TaskModel;

}((this.BX.Tasks.TaskModel = this.BX.Tasks.TaskModel || {}),BX.Tasks.TaskModel,BX,BX.Event));
//# sourceMappingURL=index.bundle.js.map
