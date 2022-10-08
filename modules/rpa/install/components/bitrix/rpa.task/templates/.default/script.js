(function (exports,main_core,rpa_manager) {
	'use strict';

	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var TaskComponent = /*#__PURE__*/function () {
	  function TaskComponent(typeId, itemId, options) {
	    babelHelpers.classCallCheck(this, TaskComponent);
	    babelHelpers.defineProperty(this, "typeId", null);
	    babelHelpers.defineProperty(this, "itemId", null);
	    babelHelpers.defineProperty(this, "buttons", []);
	    babelHelpers.defineProperty(this, "task", null);
	    babelHelpers.defineProperty(this, "onTaskComplete", null);
	    babelHelpers.defineProperty(this, "editor", false);
	    babelHelpers.defineProperty(this, "requestStarted", false);
	    this.typeId = typeId;
	    this.itemId = itemId;
	    this.buttons = options.buttons || [];
	    this.task = options.task || null;
	    this.onTaskComplete = options.onTaskComplete || null;
	  }

	  babelHelpers.createClass(TaskComponent, [{
	    key: "init",
	    value: function init() {
	      var _this = this;

	      [].forEach.call(this.buttons, function (btn) {
	        btn.style.color = rpa_manager.Manager.calculateTextColor(btn.style.backgroundColor);
	        BX.bind(btn, 'click', _this.clickButtonHandler.bind(_this, btn));
	      }, this);

	      if (this.task.ACTIVITY === 'RpaRequestActivity') {
	        this.prepareEditor();
	      }
	    }
	  }, {
	    key: "startRequest",
	    value: function startRequest() {
	      this.requestStarted = true;

	      if (BX.UI && BX.UI.ButtonManager) {
	        this.buttons.forEach(function (node) {
	          var uiButton = BX.UI.ButtonManager.createFromNode(node);

	          if (uiButton) {
	            uiButton.setWaiting(true);
	          }
	        });
	      }
	    }
	  }, {
	    key: "stopRequest",
	    value: function stopRequest() {
	      this.requestStarted = false;

	      if (BX.UI && BX.UI.ButtonManager) {
	        this.buttons.forEach(function (node) {
	          var uiButton = BX.UI.ButtonManager.createFromNode(node);

	          if (uiButton) {
	            uiButton.setWaiting(false);
	          }
	        });
	      }
	    }
	  }, {
	    key: "clickButtonHandler",
	    value: function clickButtonHandler(btn, event) {
	      event.preventDefault();

	      if (this.editor) {
	        if (this.validateFields()) {
	          this.startRequest();
	          this.editor.save();
	        }
	      } else {
	        this.doTask(btn);
	      }
	    }
	  }, {
	    key: "doTask",
	    value: function doTask(clickedButton) {
	      var _this2 = this;

	      if (this.requestStarted) {
	        return;
	      }

	      this.startRequest();
	      var formData = new FormData(clickedButton.closest('form'));
	      formData.append(clickedButton.name, clickedButton.value);
	      main_core.ajax.runAction('rpa.task.do', {
	        analyticsLabel: 'rpaTaskDo',
	        data: formData
	      }).then(function (response) {
	        _this2.stopRequest();

	        if (response.data.completed) {
	          if (_this2.onTaskComplete) {
	            _this2.onTaskComplete(response.data);
	          }
	        }
	      });
	    }
	  }, {
	    key: "prepareEditor",
	    value: function prepareEditor() {
	      this.editor = rpa_manager.Manager.getEditor(this.typeId, this.itemId);
	      BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmitFailure', this.onEditorErrors.bind(this));
	      BX.addCustomEvent(window, 'BX.UI.EntityEditorAjax:onSubmit', this.onEditorSubmit.bind(this));
	      BX.addCustomEvent(window, 'BX.UI.EntityEditor:onSave', this.onEditorSave.bind(this));
	      BX.addCustomEvent(window, "BX.UI.EntityEditor:onFailedValidation", this.onEditorErrors.bind(this));
	      this.unregisterActiveFields();
	    }
	  }, {
	    key: "unregisterActiveFields",
	    value: function unregisterActiveFields() {
	      var showSection = this.editor.getControlById('to_show');
	      var fields = showSection ? showSection.getChildren() : [];
	      fields.forEach(function (field) {
	        field._isActive = false;
	      });
	    }
	  }, {
	    key: "validateFields",
	    value: function validateFields() {
	      var _this3 = this;

	      var result = true;
	      var setSection = this.editor.getControlById('to_set');
	      var fields = setSection ? setSection.getChildren() : [];
	      fields.forEach(function (field) {
	        if (BX.Main.UF.Factory.isEmpty(field.getId())) {
	          var control = _this3.editor.getControlById(field.getId());

	          if (!control.hasError()) {
	            control.showError(main_core.Loc.getMessage('RPA_TASK_FIELD_VALIDATION_ERROR'));
	          }

	          result = false;
	        }
	      });
	      return result;
	    }
	  }, {
	    key: "onEditorSubmit",
	    value: function onEditorSubmit(entityData, response) {
	      this.stopRequest();

	      if (response.data.completed && main_core.Type.isFunction(this.onTaskComplete)) {
	        setTimeout(this.onTaskComplete.bind(this, response.data), 10);
	      }
	    }
	  }, {
	    key: "onEditorSave",
	    value: function onEditorSave(editor, eventArgs) {
	      if (this.editor === editor) {
	        eventArgs.enableCloseConfirmation = false;
	      }
	    }
	  }, {
	    key: "onEditorErrors",
	    value: function onEditorErrors(errors) {
	      this.stopRequest();
	      var msg = errors.pop().message;
	      BX.UI.Notification.Center.notify({
	        content: msg
	      });
	    }
	  }]);
	  return TaskComponent;
	}();

	namespace.TaskComponent = TaskComponent;

}((this.window = this.window || {}),BX,BX.Rpa));
//# sourceMappingURL=script.js.map
