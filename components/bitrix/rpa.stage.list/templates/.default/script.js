(function (exports,main_core,rpa_component,rpa_manager) {
	'use strict';

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-edit-robot-btn-list-target-target\"></div>"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-edit-robot-btn-item-drag-target\"></div>"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["<input class=\"rpa-stage-phase-title-input\" value=\"", "\" onblur=\"", "\" />"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"rpa-stage-phase-title\">\n\t\t\t<span class=\"rpa-stage-phase-title-inner\">\n\t\t\t\t<span class=\"rpa-stage-phase-name\">", "</span>\n\t\t\t\t<span class=\"rpa-stage-phase-icon-edit\" onclick=\"", "\" title=\"", "\"></span>\n\t\t\t</span>\n\t\t\t<span class=\"rpa-stage-phase-title-form\">\n\t\t\t\t", "\n\t\t\t</span>\n\t\t</span>"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"rpa-stage-phase-icon\">\n\t\t\t<span class=\"", "\"></span>\n\t\t</span>"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-stage-phase-panel-button\" title=\"", "\" onclick=\"", "\"></div>"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div title=\"", "\" class=\"rpa-stage-phase-panel-button rpa-stage-phase-panel-button-close\" onclick=\"", "\"></div>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-stage-phase-panel\">\n\t\t\t<div title=\"", "\" class=\"rpa-stage-phase-panel-button rpa-stage-phase-panel-button-refresh\" onclick=\"", "\"></div>\n\t\t\t", "\n\t\t\t", "\n\t\t</div>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-stage-phase-inner\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-stage-phase\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var StagesComponent =
	/*#__PURE__*/
	function (_Component) {
	  babelHelpers.inherits(StagesComponent, _Component);

	  function StagesComponent() {
	    var _babelHelpers$getProt;

	    var _this;

	    babelHelpers.classCallCheck(this, StagesComponent);

	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }

	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(StagesComponent)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    _this.stages = new Set();

	    if (main_core.Type.isArray(_this.params.stages)) {
	      _this.params.stages.forEach(function (data) {
	        if (_this.stages.size <= 0) {
	          data.first = true;
	        }

	        var stage = _this.createStage(data);

	        if (stage) {
	          _this.addStage(stage);
	        }
	      });
	    }

	    _this.typeId = _this.params.typeId;
	    _this.firstStageContainer = _this.form.querySelector('[data-role="rpa-stages-first"]');
	    _this.commonStagesContainer = _this.form.querySelector('[data-role="rpa-stages-common"]');
	    _this.successStageContainer = _this.form.querySelector('[data-role="rpa-stages-success"]');
	    _this.failStageContainer = _this.form.querySelector('[data-role="rpa-stages-fail"]');
	    _this.addCommonStageButton = _this.form.querySelector('[data-role="rpa-stage-common-add"]');
	    _this.addFailStageButton = _this.form.querySelector('[data-role="rpa-stage-fail-add"]');
	    return _this;
	  }

	  babelHelpers.createClass(StagesComponent, [{
	    key: "init",
	    value: function init() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StagesComponent.prototype), "init", this).call(this);
	      this.renderStages();
	    }
	  }, {
	    key: "renderStages",
	    value: function renderStages() {
	      var _this2 = this;

	      this.stages.forEach(function (stage) {
	        if (stage.isFirst() && _this2.firstStageContainer) {
	          main_core.Dom.append(stage.render(), _this2.firstStageContainer);
	        } else if (stage.isSuccess() && _this2.successStageContainer) {
	          main_core.Dom.append(stage.render(), _this2.successStageContainer);
	        } else if (stage.isFail() && _this2.failStageContainer) {
	          main_core.Dom.append(stage.render(), _this2.failStageContainer);
	        } else if (_this2.commonStagesContainer) {
	          main_core.Dom.append(stage.render(), _this2.commonStagesContainer);
	        }
	      });
	    }
	  }, {
	    key: "bindEvents",
	    value: function bindEvents() {
	      var _this3 = this;

	      babelHelpers.get(babelHelpers.getPrototypeOf(StagesComponent.prototype), "bindEvents", this).call(this);

	      if (this.addCommonStageButton && this.commonStagesContainer) {
	        main_core.Event.bind(this.addCommonStageButton, 'click', function () {
	          var data = {
	            name: main_core.Loc.getMessage('RPA_STAGES_NEW_STAGE_NAME'),
	            color: _this3.constructor.defaultCommonStageColor
	          };

	          var stage = _this3.createStage(data);

	          if (stage) {
	            _this3.addStage(stage);

	            main_core.Dom.append(stage.render(), _this3.commonStagesContainer);
	          }
	        });
	      }

	      if (this.addFailStageButton && this.failStageContainer) {
	        main_core.Event.bind(this.addFailStageButton, 'click', function () {
	          var data = {
	            name: main_core.Loc.getMessage('RPA_STAGES_NEW_STAGE_NAME'),
	            color: _this3.constructor.defaultFailStageColor,
	            semantic: 'FAIL'
	          };

	          var stage = _this3.createStage(data);

	          if (stage) {
	            _this3.addStage(stage);

	            main_core.Dom.append(stage.render(), _this3.failStageContainer);
	          }
	        });
	      }
	    }
	  }, {
	    key: "createStage",
	    value: function createStage(data) {
	      var stage = null;

	      if (main_core.Type.isPlainObject(data) && main_core.Type.isString(data.name)) {
	        data.typeId = this.typeId;
	        stage = new Stage(data);
	        stage.setComponent(this);
	      }

	      return stage;
	    }
	  }, {
	    key: "addStage",
	    value: function addStage(stage) {
	      if (stage instanceof Stage) {
	        this.stages.add(stage);
	      }
	    }
	  }, {
	    key: "removeStage",
	    value: function removeStage(stage) {
	      this.stages.delete(stage);
	      return this;
	    }
	  }, {
	    key: "onMoveStage",
	    value: function onMoveStage(movedStage) {
	      var _this4 = this;

	      var groupContainer = movedStage.getContainer().parentElement;

	      if (!groupContainer) {
	        return;
	      }

	      var groupStages = new Set();
	      this.stages.forEach(function (stage) {
	        if (stage.isFail() && movedStage.isFail() || !stage.isFinal() && !movedStage.isFinal() && !stage.isFirst() && !movedStage.isFirst()) {
	          groupStages.add(stage);

	          _this4.removeStage(stage);
	        }
	      });
	      groupContainer.childNodes.forEach(function (container) {
	        var stage = _this4.getStageByElement(container, groupStages);

	        if (stage) {
	          groupStages.delete(stage);

	          _this4.stages.add(stage);
	        }
	      });
	    }
	  }, {
	    key: "getStageByElement",
	    value: function getStageByElement(container, stages) {
	      var result = null;
	      stages.forEach(function (stage) {
	        if (stage.getContainer() === container) {
	          result = stage;
	        }
	      });
	      return result;
	    }
	  }, {
	    key: "prepareData",
	    value: function prepareData() {
	      var data = {
	        stages: [],
	        typeId: this.typeId
	      };
	      var sort = 1000;

	      var pushStage = function pushStage(stage, stages, sort) {
	        stages.push({
	          id: stage.getId(),
	          name: stage.getName(),
	          color: stage.getColor(),
	          semantic: stage.getSemantic(),
	          typeId: stage.getTypeId(),
	          sort: sort
	        });
	      };

	      this.stages.forEach(function (stage) {
	        if (stage.isFirst()) {
	          pushStage(stage, data.stages, sort);
	          sort += 1000;
	        }
	      });
	      this.stages.forEach(function (stage) {
	        if (!stage.isFirst() && !stage.isFinal()) {
	          pushStage(stage, data.stages, sort);
	          sort += 1000;
	        }
	      });
	      this.stages.forEach(function (stage) {
	        if (stage.isSuccess()) {
	          pushStage(stage, data.stages, sort);
	          sort += 1000;
	        }
	      });
	      this.stages.forEach(function (stage) {
	        if (stage.isFail()) {
	          pushStage(stage, data.stages, sort);
	          sort += 1000;
	        }
	      });
	      return data;
	    }
	  }, {
	    key: "afterSave",
	    value: function afterSave(response) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StagesComponent.prototype), "afterSave", this).call(this, response);
	      var slider = this.getSlider();

	      if (slider) {
	        slider.close();
	      }
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StagesComponent.prototype), "showErrors", this).call(this, errors);
	      this.errorsContainer.parentNode.style.display = 'block';
	    }
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(StagesComponent.prototype), "hideErrors", this).call(this);
	      this.errorsContainer.parentNode.style.display = 'none';
	    }
	  }]);
	  return StagesComponent;
	}(rpa_component.Component);

	babelHelpers.defineProperty(StagesComponent, "defaultCommonStageColor", '39A8EF');
	babelHelpers.defineProperty(StagesComponent, "defaultFailStageColor", 'FF5752');

	var Stage =
	/*#__PURE__*/
	function () {
	  function Stage(data) {
	    babelHelpers.classCallCheck(this, Stage);
	    this.id = data.id;
	    this.name = data.name;
	    this.color = data.color;
	    this.sort = data.sort;
	    this.semantic = data.semantic;
	    this.first = data.first;
	    this.typeId = data.typeId;
	    this.initialData = data;
	    this.layout = {};
	  }

	  babelHelpers.createClass(Stage, [{
	    key: "setComponent",
	    value: function setComponent(component) {
	      this.stagesComponent = component;
	      return this;
	    }
	  }, {
	    key: "getComponent",
	    value: function getComponent() {
	      return this.stagesComponent;
	    }
	  }, {
	    key: "getId",
	    value: function getId() {
	      if (this.id > 0) {
	        return this.id;
	      }

	      return 0;
	    }
	  }, {
	    key: "getTypeId",
	    value: function getTypeId() {
	      return this.typeId;
	    }
	  }, {
	    key: "getName",
	    value: function getName() {
	      if (main_core.Type.isString(this.name)) {
	        return this.name;
	      }

	      return '';
	    }
	  }, {
	    key: "getColor",
	    value: function getColor() {
	      if (main_core.Type.isString(this.color)) {
	        return this.color;
	      }

	      return '39A8EF';
	    }
	  }, {
	    key: "setName",
	    value: function setName(name) {
	      this.name = name;
	      return this;
	    }
	  }, {
	    key: "setColor",
	    value: function setColor(color) {
	      this.color = color;
	      return this;
	    }
	  }, {
	    key: "getSemantic",
	    value: function getSemantic() {
	      if (main_core.Type.isString(this.semantic)) {
	        return this.semantic;
	      }

	      return null;
	    }
	  }, {
	    key: "isFirst",
	    value: function isFirst() {
	      return this.first === true;
	    }
	  }, {
	    key: "isFinal",
	    value: function isFinal() {
	      return this.isSuccess() || this.isFail();
	    }
	  }, {
	    key: "isSuccess",
	    value: function isSuccess() {
	      return this.semantic === 'SUCCESS';
	    }
	  }, {
	    key: "isFail",
	    value: function isFail() {
	      return this.semantic === 'FAIL';
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      if (!this.layout.container) {
	        this.layout.container = main_core.Tag.render(_templateObject());
	      }

	      return this.layout.container;
	    }
	  }, {
	    key: "getInnerContainer",
	    value: function getInnerContainer() {
	      if (!this.layout.innerContainer) {
	        this.layout.innerContainer = main_core.Tag.render(_templateObject2());
	      }

	      return this.layout.innerContainer;
	    }
	  }, {
	    key: "render",
	    value: function render() {
	      var innerContainer = this.getInnerContainer();
	      var container = this.getContainer();
	      main_core.Dom.clean(innerContainer);
	      main_core.Dom.append(this.renderPanel(), innerContainer);
	      main_core.Dom.append(this.renderIcon(), innerContainer);
	      main_core.Dom.append(this.renderTitle(), innerContainer);
	      main_core.Dom.append(innerContainer, container);

	      if (!this.isFirst() && !this.isSuccess()) {
	        var item = new DragDropItem({
	          stage: this
	        });
	        item.init();
	      }

	      this.adjustColors();
	      return container;
	    }
	  }, {
	    key: "renderPanel",
	    value: function renderPanel() {
	      this.layout.panel = main_core.Tag.render(_templateObject3(), main_core.Loc.getMessage('RPA_STAGES_STAGE_PANEL_RELOAD'), this.restoreInitialData.bind(this), this.getColorButton(), this.getDeleteButton() ? this.getDeleteButton() : '');
	      return this.layout.panel;
	    }
	  }, {
	    key: "getDeleteButton",
	    value: function getDeleteButton() {
	      if (!this.isSuccess() && !this.isFirst()) {
	        if (!this.layout.deleteButton) {
	          this.layout.deleteButton = main_core.Tag.render(_templateObject4(), main_core.Loc.getMessage('RPA_COMMON_ACTION_DELETE'), this.destroy.bind(this));
	        }
	      } else if (this.layout.deleteButton) {
	        main_core.Dom.remove(this.layout.deleteButton);
	        this.layout.deleteButton = null;
	      }

	      return this.layout.deleteButton;
	    }
	  }, {
	    key: "getColorButton",
	    value: function getColorButton() {
	      if (!this.layout.colorButton) {
	        this.layout.colorButton = main_core.Tag.render(_templateObject5(), main_core.Loc.getMessage('RPA_STAGES_STAGE_PANEL_COLOR'), this.showColorPicker.bind(this));
	      }

	      return this.layout.colorButton;
	    }
	  }, {
	    key: "renderIcon",
	    value: function renderIcon() {
	      this.layout.icon = main_core.Tag.render(_templateObject6(), this.isFirst() || this.isSuccess() ? 'rpa-stage-phase-icon-arrow' : 'rpa-stage-phase-icon-burger');
	      return this.layout.icon;
	    }
	  }, {
	    key: "renderTitle",
	    value: function renderTitle() {
	      this.layout.title = main_core.Tag.render(_templateObject7(), main_core.Text.encode(this.getName()), this.switchToEditMode.bind(this), main_core.Loc.getMessage('RPA_STAGES_STAGE_CHANGE_TITLE'), this.getNameInput());
	      return this.layout.title;
	    }
	  }, {
	    key: "getNameInput",
	    value: function getNameInput() {
	      if (!this.layout.nameInput) {
	        this.layout.nameInput = main_core.Tag.render(_templateObject8(), main_core.Text.encode(this.getName()), this.switchToViewMode.bind(this));
	      }

	      return this.layout.nameInput;
	    }
	  }, {
	    key: "switchToEditMode",
	    value: function switchToEditMode() {
	      this.getContainer().classList.add("rpa-stage-edit-mode");
	      this.getNameInput().value = this.getName();
	      this.focusNameInput();
	    }
	  }, {
	    key: "switchToViewMode",
	    value: function switchToViewMode() {
	      this.name = this.getNameInput().value;
	      this.getContainer().classList.remove("rpa-stage-edit-mode");
	      this.render();
	    }
	  }, {
	    key: "focusNameInput",
	    value: function focusNameInput() {
	      this.getNameInput().focus();
	      this.getNameInput().selectionStart = this.getNameInput().value.length;
	    }
	  }, {
	    key: "adjustColors",
	    value: function adjustColors() {
	      var backgroundColor = '#' + this.getColor();
	      var textColor = rpa_manager.Manager.calculateTextColor(backgroundColor);
	      this.layout.innerContainer.style.backgroundColor = backgroundColor;
	      this.layout.innerContainer.style.color = textColor;
	    }
	  }, {
	    key: "getColorPicker",
	    value: function getColorPicker() {
	      var _this5 = this;

	      if (this.colorPicker) {
	        return this.colorPicker;
	      }

	      this.colorPicker = new BX.ColorPicker({
	        bindElement: this.getColorButton(),
	        onColorSelected: function onColorSelected(color) {
	          _this5.setColor(color.substr(1));

	          _this5.adjustColors();
	        } // popupOptions: {
	        // 	events: {
	        // 		onPopupClose: this.focusTextBox.bind(this)
	        // 	}
	        // }

	      });
	      return this.colorPicker;
	    }
	  }, {
	    key: "showColorPicker",
	    value: function showColorPicker() {
	      this.getColorPicker().open();
	    }
	  }, {
	    key: "restoreInitialData",
	    value: function restoreInitialData() {
	      this.name = this.initialData.name;
	      this.color = this.initialData.color;
	      this.render();
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      main_core.Dom.remove(this.getContainer());

	      if (this.getComponent()) {
	        this.getComponent().removeStage(this);
	      }
	    }
	  }]);
	  return Stage;
	}();

	var DragDropItem =
	/*#__PURE__*/
	function () {
	  function DragDropItem(options) {
	    babelHelpers.classCallCheck(this, DragDropItem);
	    this.stage = options.stage;
	    this.itemContainer = this.stage.getContainer();
	    this.draggableItemContainer = null;
	    this.dragElement = null;
	  }

	  babelHelpers.createClass(DragDropItem, [{
	    key: "init",
	    value: function init() {
	      var dragButton = this.itemContainer.querySelector('.rpa-stage-phase-icon');

	      if (jsDD) {
	        dragButton.onbxdragstart = this.onDragStart.bind(this);
	        dragButton.onbxdrag = this.onDrag.bind(this);
	        dragButton.onbxdragstop = this.onDragStop.bind(this);
	        jsDD.registerObject(dragButton);
	        this.itemContainer.onbxdestdraghover = this.onDragEnter.bind(this);
	        this.itemContainer.onbxdestdraghout = this.onDragLeave.bind(this);
	        this.itemContainer.onbxdestdragfinish = this.onDragDrop.bind(this);
	        jsDD.registerDest(this.itemContainer, 30);
	      }
	    }
	  }, {
	    key: "onDragStart",
	    value: function onDragStart() {
	      main_core.Dom.addClass(this.itemContainer, "rpa-edit-robot-btn-item-disabled");

	      if (!this.dragElement) {
	        this.dragElement = this.itemContainer.cloneNode(true);
	        this.dragElement.style.position = "absolute";
	        this.dragElement.style.width = this.itemContainer.offsetWidth + "px";
	        this.dragElement.className = "rpa-edit-robot-btn-item rpa-edit-robot-btn-item-drag";
	        main_core.Dom.append(this.dragElement, document.body);
	      }
	    }
	  }, {
	    key: "onDrag",
	    value: function onDrag(x, y) {
	      if (this.dragElement) {
	        this.dragElement.style.left = x + "px";
	        this.dragElement.style.top = y + "px";
	      }
	    }
	  }, {
	    key: "onDragStop",
	    value: function onDragStop() {
	      main_core.Dom.removeClass(this.itemContainer, "rpa-edit-robot-btn-item-disabled");
	      main_core.Dom.remove(this.dragElement);
	      this.dragElement = null;
	    }
	  }, {
	    key: "onDragEnter",
	    value: function onDragEnter(draggableItem) {
	      this.draggableItemContainer = draggableItem.closest('.rpa-stage-phase');

	      if (this.draggableItemContainer !== this.itemContainer) {
	        this.showDragTarget();
	      }
	    }
	  }, {
	    key: "onDragLeave",
	    value: function onDragLeave() {
	      this.hideDragTarget();
	    }
	  }, {
	    key: "onDragDrop",
	    value: function onDragDrop() {
	      if (this.draggableItemContainer !== this.itemContainer) {
	        this.hideDragTarget();
	        main_core.Dom.remove(this.draggableItemContainer);
	        main_core.Dom.insertBefore(this.draggableItemContainer, this.itemContainer);
	        this.stage.getComponent().onMoveStage(this.stage);
	      }
	    }
	  }, {
	    key: "showDragTarget",
	    value: function showDragTarget() {
	      main_core.Dom.addClass(this.getDragTarget(), 'rpa-edit-robot-btn-item-drag-target-shown');
	      this.getDragTarget().style.height = this.itemContainer.offsetHeight + "px";
	    }
	  }, {
	    key: "hideDragTarget",
	    value: function hideDragTarget() {
	      main_core.Dom.removeClass(this.getDragTarget(), "rpa-edit-robot-btn-item-drag-target-shown");
	      this.getDragTarget().style.height = 0;
	    }
	  }, {
	    key: "getDragTarget",
	    value: function getDragTarget() {
	      if (!this.dragTarget) {
	        this.dragTarget = main_core.Tag.render(_templateObject9());
	        main_core.Dom.prepend(this.dragTarget, this.itemContainer);
	      }

	      return this.dragTarget;
	    }
	  }]);
	  return DragDropItem;
	}();

	var DragDropItemContainer =
	/*#__PURE__*/
	function () {
	  function DragDropItemContainer(options) {
	    babelHelpers.classCallCheck(this, DragDropItemContainer);
	    this.container = options;
	    this.items = [];
	    this.height = null;
	  }

	  babelHelpers.createClass(DragDropItemContainer, [{
	    key: "init",
	    value: function init() {
	      if (jsDD) {
	        this.container.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
	        this.container.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
	        this.container.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);
	        jsDD.registerDest(this.container, 40);
	      }
	    }
	  }, {
	    key: "onDragEnter",
	    value: function onDragEnter(draggableItem) {
	      this.draggableItemContainer = draggableItem.closest('.rpa-stage-phase');
	      this.height = this.draggableItemContainer.offsetHeight;
	      this.showDragTarget();
	    }
	  }, {
	    key: "onDragLeave",
	    value: function onDragLeave() {
	      this.hideDragTarget();
	    }
	  }, {
	    key: "onDragDrop",
	    value: function onDragDrop() {
	      this.hideDragTarget();
	      main_core.Dom.remove(this.draggableItemContainer);
	      main_core.Dom.insertBefore(this.draggableItemContainer, this.dragTarget);
	    }
	  }, {
	    key: "showDragTarget",
	    value: function showDragTarget() {
	      main_core.Dom.addClass(this.container, 'rpa-edit-robot-btn-list-target-shown');
	      this.getDragTarget().style.height = this.height + "px";
	    }
	  }, {
	    key: "hideDragTarget",
	    value: function hideDragTarget() {
	      main_core.Dom.removeClass(this.container, "rpa-edit-robot-btn-list-target-shown");
	      this.getDragTarget().style.height = 0;
	    }
	  }, {
	    key: "getDragTarget",
	    value: function getDragTarget() {
	      if (!this.dragTarget) {
	        this.dragTarget = main_core.Tag.render(_templateObject10());
	        main_core.Dom.append(this.dragTarget, this.container);
	      }

	      return this.dragTarget;
	    }
	  }]);
	  return DragDropItemContainer;
	}();

	namespace.DragDropItem = DragDropItem;
	namespace.DragDropItemContainer = DragDropItemContainer;
	namespace.StagesComponent = StagesComponent;

}((this.window = this.window || {}),BX,BX.Rpa,BX.Rpa));
//# sourceMappingURL=script.js.map
