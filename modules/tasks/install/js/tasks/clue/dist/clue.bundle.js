/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_tour,ui_bannerDispatcher,spotlight,main_core) {
	'use strict';

	var _targetElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("targetElement");
	var _spotlight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("spotlight");
	class Spot {
	  constructor() {
	    Object.defineProperty(this, _targetElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _spotlight, {
	      writable: true,
	      value: null
	    });
	    if (new.target === Spot) {
	      throw new Error('This class is abstract and cannot be instantiated directly');
	    }
	  }
	  getWidth() {
	    return Spot.WIDTH;
	  }
	  getAngleOffset() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement].offsetWidth / 2;
	  }
	  isAutoHide() {
	    return true;
	  }
	  setTargetElement(targetElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement] = targetElement;
	  }
	  showLight() {
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight] = new BX.SpotLight({
	      targetElement: babelHelpers.classPrivateFieldLooseBase(this, _targetElement)[_targetElement],
	      targetVertex: 'middle-center',
	      color: this.getSpotlightColor()
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight].show();
	  }
	  close() {
	    babelHelpers.classPrivateFieldLooseBase(this, _spotlight)[_spotlight].close();
	  }
	  getIconSrc() {
	    return null;
	  }
	  getSpotlightColor() {
	    return null;
	  }
	  getConditionColor() {
	    return 'primary';
	  }
	  getTitle() {}
	  getText() {}
	}
	Spot.WIDTH = 380;
	Spot.PATH_TO_IMAGES = '/bitrix/js/tasks/clue/images/spot/';

	class FlowCopilotAdvice extends Spot {
	  getWidth() {
	    return 340;
	  }
	  getIconSrc() {
	    return null;
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_COPILOT_ADVICE_TITLE');
	  }
	  getText() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_COPILOT_ADVICE_TEXT');
	  }
	  getSpotlightColor() {
	    return '#8e52ec';
	  }
	  getConditionColor() {
	    return 'copilot';
	  }
	}

	class MyTasks extends Spot {
	  getIconSrc() {
	    return `${Spot.PATH_TO_IMAGES}my-tasks.svg`;
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_MY_TASKS_TITLE');
	  }
	  getText() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_MY_TASKS_TEXT');
	  }
	}

	class TaskStart extends Spot {
	  getIconSrc() {
	    return `${Spot.PATH_TO_IMAGES}task-start.svg`;
	  }
	  getTitle() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_TASK_START_TITLE');
	  }
	  getText() {
	    return main_core.Loc.getMessage('TASKS_CLUE_FLASH_TASK_START_TEXT');
	  }
	}

	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _spot = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("spot");
	var _guide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("guide");
	class Clue {
	  constructor(params) {
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _spot, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _guide, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = params;
	  }
	  show(spot, bindElement) {
	    babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot] = spot;
	    babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].setTargetElement(bindElement);
	    babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide] = new ui_tour.Guide({
	      id: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id,
	      autoSave: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].autoSave === true,
	      overlay: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].overlay === true,
	      simpleMode: true,
	      onEvents: true,
	      steps: [{
	        target: bindElement,
	        iconSrc: babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getIconSrc(),
	        title: babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getTitle(),
	        text: babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getText(),
	        position: 'bottom',
	        condition: {
	          top: true,
	          bottom: false,
	          color: babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getConditionColor()
	        }
	      }]
	    });
	    ui_bannerDispatcher.BannerDispatcher.normal.toQueue(onDone => {
	      const guidePopup = babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getPopup();
	      const onClose = () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].close();
	        onDone();
	      };
	      guidePopup.setWidth(babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getWidth());
	      guidePopup.setAngle({
	        offset: babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].getAngleOffset()
	      });
	      guidePopup.setAutoHide(babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].isAutoHide());
	      guidePopup.subscribe('onClose', onClose);
	      guidePopup.subscribe('onDestroy', onClose);
	      babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].showLight();
	      babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].start();
	    });
	  }
	  isShown() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide] === null) {
	      return false;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getPopup().isShown();
	  }
	  close() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].close();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].close();
	    }
	  }
	  adjustPosition(bindElement) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getPopup().setBindElement(bindElement);
	      babelHelpers.classPrivateFieldLooseBase(this, _guide)[_guide].getPopup().adjustPosition();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].setTargetElement(bindElement);
	      babelHelpers.classPrivateFieldLooseBase(this, _spot)[_spot].showLight();
	    }
	  }
	}
	Clue.SPOT = Object.freeze({
	  MY_TASKS: new MyTasks(),
	  TASK_START: new TaskStart(),
	  FLOW_COPILOT_ADVICE: new FlowCopilotAdvice()
	});

	exports.Clue = Clue;

}((this.BX.Tasks = this.BX.Tasks || {}),BX.UI.Tour,BX.UI,BX,BX));
//# sourceMappingURL=clue.bundle.js.map
