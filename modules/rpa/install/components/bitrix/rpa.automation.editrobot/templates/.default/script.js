(function (exports,main_core,rpa_manager,dd_js) {
	'use strict';

	dd_js = dd_js && dd_js.hasOwnProperty('default') ? dd_js['default'] : dd_js;

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-edit-robot-btn-list-target-target\"></div>"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"rpa-edit-robot-btn-item-drag-target\"></div>"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"rpa-edit-robot-btn-icon-draggable\"></span>\n\t\t\t"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var namespace = main_core.Reflection.namespace('BX.Rpa');

	var DragDropBtn =
	/*#__PURE__*/
	function () {
	  function DragDropBtn(options) {
	    babelHelpers.classCallCheck(this, DragDropBtn);
	    this.btnContainer = options;
	    this.draggableBtnContainer = null;
	    this.dragElement = null;
	    this.cache = new main_core.Cache.MemoryCache();
	  }

	  babelHelpers.createClass(DragDropBtn, [{
	    key: "init",
	    value: function init() {
	      var dragButton = this.getDragButton();
	      main_core.Dom.prepend(dragButton, this.btnContainer);
	      dragButton.onbxdragstart = this.onDragStart.bind(this);
	      dragButton.onbxdrag = this.onDrag.bind(this);
	      dragButton.onbxdragstop = this.onDragStop.bind(this);
	      jsDD.registerObject(dragButton);
	      this.btnContainer.onbxdestdraghover = this.onDragEnter.bind(this);
	      this.btnContainer.onbxdestdraghout = this.onDragLeave.bind(this);
	      this.btnContainer.onbxdestdragfinish = this.onDragDrop.bind(this);
	      jsDD.registerDest(this.btnContainer, 30);
	    }
	  }, {
	    key: "getDragButton",
	    value: function getDragButton() {
	      return this.cache.remember('dragButton', function () {
	        return main_core.Tag.render(_templateObject());
	      });
	    }
	  }, {
	    key: "onDragStart",
	    value: function onDragStart() {
	      main_core.Dom.addClass(this.btnContainer, "rpa-edit-robot-btn-item-disabled");

	      if (!this.dragElement) {
	        this.dragElement = this.btnContainer.cloneNode(true);
	        this.dragElement.style.position = "absolute";
	        this.dragElement.style.width = this.btnContainer.offsetWidth + "px";
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
	      main_core.Dom.removeClass(this.btnContainer, "rpa-edit-robot-btn-item-disabled");
	      main_core.Dom.remove(this.dragElement);
	      this.dragElement = null;
	    }
	  }, {
	    key: "onDragEnter",
	    value: function onDragEnter(draggableItem) {
	      this.draggableBtnContainer = draggableItem.closest('.rpa-edit-robot-btn-item');

	      if (this.draggableBtnContainer !== this.btnContainer) {
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
	      if (this.draggableBtnContainer !== this.btnContainer) {
	        this.hideDragTarget();
	        main_core.Dom.remove(this.draggableBtnContainer);
	        main_core.Dom.insertBefore(this.draggableBtnContainer, this.btnContainer);
	      }
	    }
	  }, {
	    key: "showDragTarget",
	    value: function showDragTarget() {
	      main_core.Dom.addClass(this.btnContainer, 'rpa-edit-robot-btn-item-target-shown');
	      this.getDragTarget().style.height = this.btnContainer.offsetHeight + "px";
	    }
	  }, {
	    key: "hideDragTarget",
	    value: function hideDragTarget() {
	      main_core.Dom.removeClass(this.btnContainer, "rpa-edit-robot-btn-item-target-shown");
	      this.getDragTarget().style.height = 0;
	    }
	  }, {
	    key: "getDragTarget",
	    value: function getDragTarget() {
	      if (!this.dragTarget) {
	        this.dragTarget = main_core.Tag.render(_templateObject2());
	        main_core.Dom.prepend(this.dragTarget, this.btnContainer);
	      }

	      return this.dragTarget;
	    }
	  }]);
	  return DragDropBtn;
	}();

	var DragDropBtnContainer =
	/*#__PURE__*/
	function () {
	  function DragDropBtnContainer() {
	    babelHelpers.classCallCheck(this, DragDropBtnContainer);
	    this.container = document.querySelector('.rpa-edit-robot-btn-item-list');
	    this.height = null;
	  }

	  babelHelpers.createClass(DragDropBtnContainer, [{
	    key: "init",
	    value: function init() {
	      this.container.onbxdestdraghover = BX.delegate(this.onDragEnter, this);
	      this.container.onbxdestdraghout = BX.delegate(this.onDragLeave, this);
	      this.container.onbxdestdragfinish = BX.delegate(this.onDragDrop, this);
	      jsDD.registerDest(this.container, 40);
	    }
	  }, {
	    key: "onDragEnter",
	    value: function onDragEnter(draggableItem) {
	      this.draggableBtnContainer = draggableItem.closest('.rpa-edit-robot-btn-item');
	      this.height = this.draggableBtnContainer.offsetHeight;
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
	      main_core.Dom.remove(this.draggableBtnContainer);
	      main_core.Dom.insertBefore(this.draggableBtnContainer, this.dragTarget);
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
	        this.dragTarget = main_core.Tag.render(_templateObject3());
	        main_core.Dom.append(this.dragTarget, this.container);
	      }

	      return this.dragTarget;
	    }
	  }]);
	  return DragDropBtnContainer;
	}();

	namespace.DragDropBtn = DragDropBtn;
	namespace.DragDropBtnContainer = DragDropBtnContainer;

}((this.window = this.window || {}),BX,BX.Rpa,BX));
//# sourceMappingURL=script.js.map
