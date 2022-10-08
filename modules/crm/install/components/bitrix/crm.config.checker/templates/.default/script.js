this.BX = this.BX || {};
(function (exports,ui_buttons,main_core,main_core_events) {
	'use strict';

	var _templateObject, _templateObject2;

	var Step = /*#__PURE__*/function () {
	  // does not use yet
	  function Step(options, iterator) {
	    babelHelpers.classCallCheck(this, Step);
	    babelHelpers.defineProperty(this, "actual", null);
	    babelHelpers.defineProperty(this, "correct", null);
	    babelHelpers.defineProperty(this, "started", false);
	    babelHelpers.defineProperty(this, "finished", false);
	    babelHelpers.defineProperty(this, "statusAttributeName", "data-bx-status");
	    babelHelpers.defineProperty(this, "url", "");
	    babelHelpers.defineProperty(this, "errors", new Map());
	    babelHelpers.defineProperty(this, "notes", new Map());
	    this.id = String(options["ID"]);
	    this.iterator = iterator;
	    this.nodeMain = document.querySelector("#" + options["NODE_ID"]);
	    this.node = this.nodeMain.querySelector("[data-bx-block=\"info-block\"]");
	    this.actual = options["IS_ACTUAL"];
	    this.correct = options["IS_CORRECT"];
	    this.started = options["IS_STARTED"];
	    this.finished = options["IS_FINISHED"];
	    main_core_events.EventEmitter.subscribe(this.iterator, "Iterator:reset", this.reset.bind(this));
	    main_core_events.EventEmitter.subscribe(this.iterator, "Iterator:response", this.checkResponse.bind(this));
	    main_core_events.EventEmitter.subscribe(this.iterator, "Iterator:error", this.setError.bind(this));
	    this.adjustNode();
	    this.adjustInfoBlock(options["ERRORS"], options["NOTES"]);
	    var button = this.nodeMain.querySelector("[data-bx-url]");

	    if (button) {
	      this.url = button.getAttribute("data-bx-url");
	      button.addEventListener("click", this.onClickUrl.bind(this));
	    }
	  }

	  babelHelpers.createClass(Step, [{
	    key: "reset",
	    value: function reset() {
	      this.started = true;
	      this.finished = false;
	      this.actual = null;
	      this.correct = null;
	      this.adjustNode();
	    }
	  }, {
	    key: "checkResponse",
	    value: function checkResponse(_ref) {
	      var steps = _ref.data;

	      for (var stepId in steps) {
	        if (steps.hasOwnProperty(stepId) && this.id === stepId) {
	          this.actual = steps[stepId].actual;
	          this.correct = steps[stepId].correct;
	          this.started = steps[stepId].started;
	          this.finished = steps[stepId].finished;
	          this.adjustNode();
	          this.adjustInfoBlock(steps[stepId]["errors"], steps[stepId]["notes"]);
	        }
	      }
	    }
	  }, {
	    key: "adjustNode",
	    value: function adjustNode() {
	      var status = "ok";

	      if (!this.started) {
	        status = "not-checked";
	      } else if (!this.finished) {
	        status = "in-progress";
	      } else if (!this.actual) {
	        status = "not-actual";
	      } else if (!this.correct) {
	        status = "not-correct";
	      }

	      this.nodeMain.setAttribute(this.statusAttributeName, status);
	    }
	  }, {
	    key: "setError",
	    value: function setError() {
	      this.node.innerHTML = "Some error was occurred.";
	    }
	  }, {
	    key: "adjustInfoBlock",
	    value: function adjustInfoBlock(errors, notes) {
	      var child = this.node.lastChild;

	      while (child) {
	        this.node.removeChild(child);
	        child = this.node.lastChild;
	      }

	      this.parseErrors(errors, notes);

	      if (this.node.hasChildNodes()) {
	        main_core.Tag.style(this.node.parentNode)(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\theight: ", "px;\n\t\t\t\topacity: 1;\n\t\t\t"])), this.node.offsetHeight);
	      } else {
	        main_core.Tag.style(this.node.parentNode)(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\theight: 0;\n\t\t\t\topacity: 0;\n\t\t\t"])));
	      }
	    }
	  }, {
	    key: "onClickUrl",
	    value: function onClickUrl() {
	      if (main_core.Type.isStringFilled(this.url)) {
	        BX.SidePanel.Instance.open(this.url);
	      }
	    }
	  }, {
	    key: "parseErrors",
	    value: function parseErrors(errors, notes) {}
	  }]);
	  return Step;
	}();

	var _templateObject$1;

	var StepTelephony = /*#__PURE__*/function (_Step) {
	  babelHelpers.inherits(StepTelephony, _Step);

	  function StepTelephony(options, iterator) {
	    babelHelpers.classCallCheck(this, StepTelephony);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StepTelephony).call(this, options, iterator));
	  }

	  babelHelpers.createClass(StepTelephony, [{
	    key: "parseErrors",
	    value: function parseErrors(errors, notes) {
	      for (var _i = 0, _Object$entries = Object.entries(errors); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            key = _Object$entries$_i[0],
	            error = _Object$entries$_i[1];

	        if (key !== "VOXIMPLANT_IS_NOT_CONFIGURED") {
	          this.node.append(main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</span>"])), error["message"]));
	        }
	      }
	    }
	  }]);
	  return StepTelephony;
	}(Step);

	var _templateObject$2, _templateObject2$1;

	var StepCrmForm = /*#__PURE__*/function (_Step) {
	  babelHelpers.inherits(StepCrmForm, _Step);

	  function StepCrmForm(options, iterator) {
	    babelHelpers.classCallCheck(this, StepCrmForm);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StepCrmForm).call(this, options, iterator));
	  }

	  babelHelpers.createClass(StepCrmForm, [{
	    key: "onChange",
	    value: function onChange(_ref) {
	      var target = _ref.target;
	      var select = this.node.querySelector("#crm_numbers");

	      if (select.value !== null) {
	        main_core_events.EventEmitter.emit(this, "Step:action", {
	          action: "setNumber",
	          data: {
	            numberId: select.value
	          }
	        });
	      }

	      var butt = this.node.querySelector("#crm_button");

	      if (butt) {
	        main_core.Dom.addClass(butt, "ui-btn-wait");
	        setTimeout(function () {
	          main_core.Dom.removeClass(butt, "ui-btn-wait");
	        }, 2000);
	      }
	    }
	  }, {
	    key: "parseErrors",
	    value: function parseErrors(errors, notes) {
	      for (var _i = 0, _Object$entries = Object.entries(errors); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            key = _Object$entries$_i[0],
	            error = _Object$entries$_i[1];

	        this.node.append(main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</span>"])), error["message"]));
	      }

	      if (notes && main_core.Type.isArray(notes["items"])) {
	        var numberIsInUse = [];
	        notes["items"].forEach(function (number) {
	          if (number['IS_IN_USE'] === true) {
	            numberIsInUse.push(number['LINE_NUMBER']);
	          }
	        });
	        var numbers = '';

	        if (numberIsInUse.length > 1) {
	          numbers = "<option value=\"null\" selected>".concat(main_core.Loc.getMessage("CRM_SEVERAL_NUMBERS_IS_IN_USE"), "</option>");
	        } else if (numberIsInUse.length <= 0) {
	          numbers = "<option value=\"null\" selected>".concat(main_core.Loc.getMessage("CRM_PICK_UP_THE_NUMBER_FOR_CRMFORM"), "</option>");
	        }

	        notes["items"].forEach(function (number) {
	          numbers += "\n\t\t\t\t\t<option value=\"".concat(number['LINE_NUMBER'], "\" ").concat(numberIsInUse.length === 1 && number['IS_IN_USE'] ? "selected" : "", ">\n\t\t\t\t\t\t[").concat(number['LINE_NUMBER'], "] ").concat(number['SHORT_NAME'], "\n\t\t\t\t\t</option>\n\t\t\t");
	        });
	        main_core.Dom.append(main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t<div class=\"crm-wizard-settings-block-hidden-input\">\n\t\t\t\t\t\t<div class=\"crm-wizard-settings-block-hidden-input-inner\">\n\t\t\t\t\t\t\t<div class=\"crm-wizard-settings-block-hidden-input-label\">", "</div>\n\t\t\t\t\t\t\t<div class=\"ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown\">\n\t\t\t\t\t\t\t\t<div class=\"ui-ctl-after ui-ctl-icon-angle\"></div>\n\t\t\t\t\t\t\t\t<select class=\"ui-ctl-element\" id=\"crm_numbers\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</select>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<button class=\"ui-btn ui-btn-light-border\" id=\"crm_button\" onclick=\"", "\">", "</button>\n\t\t\t\t\t</div>\n"])), main_core.Loc.getMessage("CRM_CHANGE_CRM_FORM_NUMBER"), numbers, this.onChange.bind(this), main_core.Loc.getMessage("CRM_BUTTON_APPLY")), this.node);
	      }
	    }
	  }]);
	  return StepCrmForm;
	}(Step);

	var _templateObject$3, _templateObject2$2, _templateObject3, _templateObject4;

	var StepImconnector = /*#__PURE__*/function (_Step) {
	  babelHelpers.inherits(StepImconnector, _Step);

	  function StepImconnector(options, iterator) {
	    babelHelpers.classCallCheck(this, StepImconnector);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StepImconnector).call(this, options, iterator));
	  }

	  babelHelpers.createClass(StepImconnector, [{
	    key: "parseErrors",
	    value: function parseErrors(errors, notes) {
	      var node = null;

	      for (var _i = 0, _Object$entries = Object.entries(errors); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            key = _Object$entries$_i[0],
	            error = _Object$entries$_i[1];

	        if (error["code"] === "IMCONNECTOR_IS_NOT_CORRECT" && main_core.Type.isArray(error["customData"])) {
	          node = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<ul class=\"crm-wizard-settings-block-list\"></ul>"])));
	          error["customData"].forEach(function (item) {
	            node.append(main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t<li class=\"", "\">\n\t\t<a href=\"", "\" onclick=\"BX.SidePanel.Instance.open(this.href); return false;\">", "</a>\n\t</li>\n\t\t\t\t\t\t"])), item["icon_class"], item["link"], item["name"]));
	          });
	          node = main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "", "</div>"])), error["message"], node);
	        } else {
	          node = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</div>"])), error["message"]);
	        }

	        this.node.append(node);
	      }
	    }
	  }]);
	  return StepImconnector;
	}(Step);

	var _templateObject$4, _templateObject2$3, _templateObject3$1, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8;

	var StepMessageService = /*#__PURE__*/function (_Step) {
	  babelHelpers.inherits(StepMessageService, _Step);

	  function StepMessageService(options, iterator) {
	    babelHelpers.classCallCheck(this, StepMessageService);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StepMessageService).call(this, options, iterator));
	  }

	  babelHelpers.createClass(StepMessageService, [{
	    key: "onClickUrl",
	    value: function onClickUrl() {
	      if (main_core.Type.isStringFilled(this.url)) {
	        window.open(this.url);
	      }
	    }
	  }, {
	    key: "parseErrors",
	    value: function parseErrors(errors) {
	      var node = null,
	          node2 = null;
	      var errorNode = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\"></div>"])));

	      for (var _i = 0, _Object$entries = Object.entries(errors); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            key = _Object$entries$_i[0],
	            error = _Object$entries$_i[1];

	        if (error["code"] === "NONEXISTENT_PROVIDER") {
	          if (node2 === null) {
	            node2 = main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["<ul class=\"crm-wizard-settings-block-provider-list\"></ul>"])));
	            errorNode.append(main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</div>"])), node2));
	          }

	          node2.append(main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<li>", "</li>"])), error["message"]));
	        } else if (error["code"] === "NONWORKING_PROVIDER") {
	          if (node === null) {
	            node = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<ul class=\"crm-wizard-settings-block-provider-list\"></ul>"])));
	            errorNode.append(main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</div>"])), node));
	          }

	          node.append(main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t<li>\n\t\t", "\n\t\t<a href=\"", "\" onclick=\"BX.SidePanel.Instance.open(this.href); return false;\">", "</a>\n\t</li>\n\t\t\t\t\t\t"])), error["message"], error["customData"]["manageUrl"], error["customData"]["shortName"]));
	        } else {
	          errorNode.append(main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["<div>", "</div>"])), error["message"]));
	        }
	      }

	      if (errorNode.hasChildNodes()) {
	        this.node.append(errorNode);
	      }
	    }
	  }]);
	  return StepMessageService;
	}(Step);

	var _templateObject$5;

	var StepPaySystem = /*#__PURE__*/function (_Step) {
	  babelHelpers.inherits(StepPaySystem, _Step);

	  function StepPaySystem(options, iterator) {
	    babelHelpers.classCallCheck(this, StepPaySystem);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(StepPaySystem).call(this, options, iterator));
	  }

	  babelHelpers.createClass(StepPaySystem, [{
	    key: "onClickUrl",
	    value: function onClickUrl() {
	      if (main_core.Type.isStringFilled(this.url)) {
	        window.open(this.url);
	      }
	    }
	  }, {
	    key: "parseErrors",
	    value: function parseErrors(errors, notes) {
	      for (var _i = 0, _Object$entries = Object.entries(errors); _i < _Object$entries.length; _i++) {
	        var _Object$entries$_i = babelHelpers.slicedToArray(_Object$entries[_i], 2),
	            key = _Object$entries$_i[0],
	            error = _Object$entries$_i[1];

	        if (key !== "PAY_SYSTEM_IS_NOT_CONFIGURED") {
	          this.node.append(main_core.Tag.render(_templateObject$5 || (_templateObject$5 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected\">", "</span>"])), error["message"]));
	        }
	      }
	    }
	  }]);
	  return StepPaySystem;
	}(Step);

	var stepMappings = {
	  'Step': Step,
	  'StepTelephony': StepTelephony,
	  'StepCrmForm': StepCrmForm,
	  'StepImconnector': StepImconnector,
	  'StepMessageService': StepMessageService,
	  'StepPaySystem': StepPaySystem
	};

	var Iterator = /*#__PURE__*/function () {
	  function Iterator(id, data) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Iterator);
	    babelHelpers.defineProperty(this, "started", false);
	    babelHelpers.defineProperty(this, "finished", false);
	    babelHelpers.defineProperty(this, "steps", new Map());
	    babelHelpers.defineProperty(this, "resetButton", null);
	    babelHelpers.defineProperty(this, "closeButton", null);
	    this.id = id;
	    this.started = data.started;
	    this.finished = data.finished;
	    this.steps = new Map();
	    data.steps.map(function (stepOption) {
	      _this.addStep(stepOption);
	    });

	    if (data["buttons"]["start"]) {
	      this.resetButton = ui_buttons.ButtonManager.createFromNode(data["buttons"]["start"]);
	      main_core.Event.bind(this.resetButton.getContainer(), "click", function (event) {
	        event.preventDefault();

	        _this.start();
	      });
	      main_core_events.EventEmitter.subscribe(this, "Iterator:reset", function () {
	        _this.resetButton.setWaiting(true);
	      });
	      main_core_events.EventEmitter.subscribe(this, "Iterator:finish", function () {
	        _this.resetButton.setWaiting(false);
	      });
	      main_core_events.EventEmitter.subscribe(this, "Iterator:error", function () {
	        _this.resetButton.setWaiting(false);
	      });
	    }

	    this.componentName = data["componentName"];
	    this.signedParameters = data["signedParameters"];
	  }

	  babelHelpers.createClass(Iterator, [{
	    key: "addStep",
	    value: function addStep(stepOption) {
	      var _this2 = this;

	      var id = String(stepOption["ID"]);
	      var stepClassName = id.substring(id.lastIndexOf("\\") + 1);
	      var step;

	      if (stepMappings[stepClassName]) {
	        step = new stepMappings[stepClassName](stepOption, this);
	      } else {
	        step = new Step(stepOption, this);
	      }

	      main_core_events.EventEmitter.subscribe(step, "Step:action", function (_ref) {
	        var target = _ref.target,
	            data = _ref.data;

	        _this2.execute(target.id, data.action, data.data);
	      });
	      this.steps.set(id, step);
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "start",
	    value: function start() {
	      main_core_events.EventEmitter.emit(this, "Iterator:reset", []);
	      this.send("reset");
	    }
	  }, {
	    key: "continue",
	    value: function _continue() {
	      main_core_events.EventEmitter.emit(this, "Iterator:continue", []);
	      this.send("continue");
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      main_core_events.EventEmitter.emit(this, "Iterator:finish", []);
	      this.resetButton.setDisabled(true);
	    }
	  }, {
	    key: "error",
	    value: function error(_ref2) {
	      var errors = _ref2.errors;
	      main_core_events.EventEmitter.emit(this, "Iterator:error", data);
	    }
	  }, {
	    key: "execute",
	    value: function execute(stepId, stepAction, stepData) {
	      this.send("executeStep", {
	        stepId: stepId,
	        stepAction: stepAction,
	        stepData: stepData
	      });
	    }
	  }, {
	    key: "send",
	    value: function send(action, data) {
	      data = main_core.Type.isPlainObject(data) ? data : {};
	      main_core.ajax.runComponentAction(this.componentName, action, {
	        signedParameters: this.signedParameters,
	        mode: "class",
	        data: data
	      }).then(this.response.bind(this), this.error.bind(this));
	    }
	  }, {
	    key: "response",
	    value: function response(_ref3) {
	      var data = _ref3.data;
	      this.started = data.started;
	      this.finished = data.finished;
	      main_core_events.EventEmitter.emit(this, "Iterator:response", data["stepSteps"]);

	      if (this.finished !== true) {
	        this["continue"]();
	      } else {
	        this.finish();
	      }
	    }
	  }]);
	  return Iterator;
	}();

	exports.Iterator = Iterator;

}((this.BX.Crm = this.BX.Crm || {}),BX.UI,BX,BX.Event));
//# sourceMappingURL=script.js.map
