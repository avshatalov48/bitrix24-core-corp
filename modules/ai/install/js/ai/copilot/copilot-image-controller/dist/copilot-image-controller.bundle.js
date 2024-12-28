/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ai_engine,ai_ajaxErrorHandler,main_loader,ui_buttons,main_core_events,main_popup,main_core,ui_iconSet_api_core) {
	'use strict';

	class BaseCommand {
	  constructor(options) {
	    this.copilotImageController = options.copilotImageController;
	  }
	  execute() {
	    throw new Error('You must implement this method!');
	  }
	}

	var _inputField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _copilotContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	class CancelImageCommand extends BaseCommand {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _inputField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotContainer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer)[_copilotContainer] = options.copilotContainer;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField] = options.inputField;
	  }
	  execute() {
	    this.copilotImageController.emit('cancel');
	    this.copilotImageController.destroyAllMenus();
	    this.copilotImageController.showImageConfigurator();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].clearErrors();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].clear();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].enable();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer)[_copilotContainer], '--error');
	    // this.#selectedCommand = null;
	    // this.#resultStack = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField)[_inputField].focus();
	    this.copilotImageController.getAnalytics().sendEventCancel();
	  }
	}

	class PlaceImageUnderCommand extends BaseCommand {
	  execute() {
	    this.copilotImageController.emit('place-under');
	  }
	}

	class PlaceImageAboveCommand extends BaseCommand {
	  execute() {
	    this.copilotImageController.emit('place-above');
	  }
	}

	class SaveImageCommand extends BaseCommand {
	  execute() {
	    this.copilotImageController.emit('save', new main_core_events.BaseEvent({
	      data: {
	        imageUrl: this.copilotImageController.getResultImageUrl()
	      }
	    }));
	    this.copilotImageController.getAnalytics().sendEventSave();
	  }
	}

	class RepeatImageCompletion extends BaseCommand {
	  execute() {
	    this.copilotImageController.completions();
	  }
	}

	class ImageConfiguratorErrorMenuItems {
	  static getMenuItems(options) {
	    const copilotImageController = options.copilotImageController;
	    const inputField = options.inputField;
	    const copilotContainer = options.copilotContainer;
	    return [{
	      code: 'repeat',
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_REPEAT'),
	      icon: 'left-semicircular-anticlockwise-arrow-1',
	      notHighlight: true,
	      command: new RepeatImageCompletion({
	        copilotImageController
	      })
	    }, {
	      code: 'cancel',
	      text: main_core.Loc.getMessage('AI_COPILOT_COMMAND_CANCEL'),
	      icon: 'cross-45',
	      notHighlight: true,
	      command: new CancelImageCommand({
	        copilotImageController,
	        inputField,
	        copilotContainer
	      })
	    }];
	  }
	}

	class ImageConfiguratorResultMenuItems {
	  static getMenuItems(options) {
	    const copilotImageController = options == null ? void 0 : options.copilotImageController;
	    const inputField = options.inputField;
	    const useAboveAndUnderTextMenuItems = options.useInsertAboveAndUnderMenuItems;
	    return [{
	      code: 'save',
	      text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_SAVE'),
	      icon: ui_iconSet_api_core.Main.CHECK,
	      notHighlight: true,
	      command: new SaveImageCommand({
	        copilotImageController
	      })
	    }, useAboveAndUnderTextMenuItems ? {
	      code: 'place_above',
	      text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_PLACE_ABOVE_TEXT'),
	      icon: ui_iconSet_api_core.Actions.ARROW_TOP,
	      notHighlight: true,
	      command: new PlaceImageAboveCommand({
	        copilotImageController
	      })
	    } : null, useAboveAndUnderTextMenuItems ? {
	      code: 'place_under',
	      text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_PLACE_UNDER_TEXT'),
	      icon: ui_iconSet_api_core.Actions.ARROW_DOWN,
	      notHighlight: true,
	      command: new PlaceImageUnderCommand({
	        copilotImageController
	      })
	    } : null, {
	      code: 'repeat',
	      text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_REPEAT'),
	      icon: ui_iconSet_api_core.Actions.LEFT_SEMICIRCULAR_ANTICLOCKWISE_ARROW_1,
	      notHighlight: true,
	      command: new RepeatImageCompletion({
	        copilotImageController
	      })
	    }, {
	      separator: true
	    }, {
	      code: 'cancel',
	      text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_RESULT_MENU_CANCEL'),
	      icon: ui_iconSet_api_core.Actions.CROSS_45,
	      notHighlight: true,
	      command: new CancelImageCommand({
	        copilotImageController,
	        inputField
	      })
	    }].filter(item => Boolean(item));
	  }
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6;
	const ImageConfiguratorStylesEvents = Object.freeze({
	  select: 'select'
	});
	var _mainStylesCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mainStylesCount");
	var _currentMainStylesCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentMainStylesCount");
	var _selectedStyle = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedStyle");
	var _isExpanded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExpanded");
	var _styleList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("styleList");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _styles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("styles");
	var _renderHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderHeader");
	var _isShowExpandBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isShowExpandBtn");
	var _renderExpandListBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderExpandListBtn");
	var _renderStylesList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStylesList");
	var _renderStyleItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStyleItems");
	var _renderStyleItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderStyleItem");
	class ImageConfiguratorStyles extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _renderStyleItem, {
	      value: _renderStyleItem2
	    });
	    Object.defineProperty(this, _renderStyleItems, {
	      value: _renderStyleItems2
	    });
	    Object.defineProperty(this, _renderStylesList, {
	      value: _renderStylesList2
	    });
	    Object.defineProperty(this, _renderExpandListBtn, {
	      value: _renderExpandListBtn2
	    });
	    Object.defineProperty(this, _isShowExpandBtn, {
	      value: _isShowExpandBtn2
	    });
	    Object.defineProperty(this, _renderHeader, {
	      value: _renderHeader2
	    });
	    Object.defineProperty(this, _mainStylesCount, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _currentMainStylesCount, {
	      writable: true,
	      value: 9
	    });
	    Object.defineProperty(this, _selectedStyle, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isExpanded, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _styleList, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _styles, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _styles)[_styles] = options.styles;
	    babelHelpers.classPrivateFieldLooseBase(this, _mainStylesCount)[_mainStylesCount] = babelHelpers.classPrivateFieldLooseBase(this, _styles)[_styles].length;
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedStyle)[_selectedStyle] = babelHelpers.classPrivateFieldLooseBase(this, _styles)[_styles][0].code;
	    this.setEventNamespace('AI.Copilot.ImageConfiguratorStyles');
	  }
	  getSelectedStyle() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedStyle)[_selectedStyle];
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t || (_t = _`
			<div class="ai__image-configurator-styles">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderHeader)[_renderHeader](), babelHelpers.classPrivateFieldLooseBase(this, _renderStylesList)[_renderStylesList]());
	    requestAnimationFrame(() => {
	      const styleListStyles = getComputedStyle(babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList]);
	      const paddingTop = styleListStyles.getPropertyValue('padding-top');
	      const paddingBottom = styleListStyles.getPropertyValue('padding-bottom');
	      const padding = parseFloat(paddingTop) + parseFloat(paddingBottom);
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList], 'height', `${babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList].offsetHeight - padding + 4}px`);
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	}
	function _renderHeader2() {
	  const expandListBtn = babelHelpers.classPrivateFieldLooseBase(this, _isShowExpandBtn)[_isShowExpandBtn]() ? babelHelpers.classPrivateFieldLooseBase(this, _renderExpandListBtn)[_renderExpandListBtn]() : null;
	  return main_core.Tag.render(_t2 || (_t2 = _`
			<header class="ai__image-configurator-styles_header">
				<div
					class="ai__image-configurator-styles_title"
					title="${0}"
				>
					${0}
				</div>
				${0}
			</header>
		`), main_core.Loc.getMessage('AI_COPILOT_IMAGE_POPULAR_STYLES'), main_core.Loc.getMessage('AI_COPILOT_IMAGE_POPULAR_STYLES'), expandListBtn);
	}
	function _isShowExpandBtn2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _mainStylesCount)[_mainStylesCount] > babelHelpers.classPrivateFieldLooseBase(this, _currentMainStylesCount)[_currentMainStylesCount];
	}
	function _renderExpandListBtn2() {
	  const expandListBtn = main_core.Tag.render(_t3 || (_t3 = _`
			<div
				class="ai__image-configurator-styles_all-styles"
				title="${0}"
			>
				${0}
			</div>
		`), main_core.Loc.getMessage('AI_COPILOT_IMAGE_ALL_STYLES'), main_core.Loc.getMessage('AI_COPILOT_IMAGE_ALL_STYLES'));
	  main_core.Event.bind(expandListBtn, 'click', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _isExpanded)[_isExpanded] = !babelHelpers.classPrivateFieldLooseBase(this, _isExpanded)[_isExpanded];
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isExpanded)[_isExpanded]) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList], '--expanded');
	      babelHelpers.classPrivateFieldLooseBase(this, _currentMainStylesCount)[_currentMainStylesCount] = Object.values(babelHelpers.classPrivateFieldLooseBase(this, _styles)[_styles]).length;
	      babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList].innerHTML = '';
	      babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList].append(...babelHelpers.classPrivateFieldLooseBase(this, _renderStyleItems)[_renderStyleItems]());
	    } else {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList], '--expanded');
	    }
	  });
	  return expandListBtn;
	}
	function _renderStylesList2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList] = main_core.Tag.render(_t4 || (_t4 = _`
			<div class="ai__image-configurator-styles_list">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderStyleItems)[_renderStyleItems]());
	  return babelHelpers.classPrivateFieldLooseBase(this, _styleList)[_styleList];
	}
	function _renderStyleItems2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _styles)[_styles].slice(0, babelHelpers.classPrivateFieldLooseBase(this, _currentMainStylesCount)[_currentMainStylesCount]).map(styleItem => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderStyleItem)[_renderStyleItem](styleItem);
	  });
	}
	function _renderStyleItem2(style) {
	  const radioButton = main_core.Tag.render(_t5 || (_t5 = _`
			<input
				${0}
				id="${0}"
				name="ai__image-configurator-style"
				type="radio"
				class="ai__image-configurator-style_item-radio-btn"
			/>
		`), style.code === babelHelpers.classPrivateFieldLooseBase(this, _selectedStyle)[_selectedStyle] ? 'checked' : '', style.code);
	  const item = main_core.Tag.render(_t6 || (_t6 = _`
			<div
				title="${0}"
				class="ai__image-configurator-styles_item"
			>
				${0}
				<label
					for="${0}"
					class="ai__image-configurator-styles_item-inner"
					style="background-image: url(${0})"
				>
					<div class="ai__image-configurator-styles_item-title">${0}</div>
				</label>
			</div>
		`), style.name, radioButton, style.code, style.preview, style.name);
	  main_core.Event.bind(radioButton, 'input', () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedStyle)[_selectedStyle] = style.code;
	    this.emit(ImageConfiguratorStylesEvents.select, new main_core_events.BaseEvent({
	      data: style.code
	    }));
	  });
	  return item;
	}

	const getParams = options => {
	  return {
	    format: {
	      title: main_core.Loc.getMessage('AI_COPILOT_IMAGE_FORMAT_OPTION_TITLE'),
	      icon: ui_iconSet_api_core.Editor.INCERT_IMAGE,
	      options: getOptionsFromFormats(options.formats)
	    },
	    engine: {
	      title: main_core.Loc.getMessage('AI_COPILOT_IMAGE_ENGINE_OPTION_TITLE'),
	      icon: ui_iconSet_api_core.Main.ROBOT,
	      options: getOptionsFromEngines(options.engines)
	    }
	  };
	};
	const getOptionsFromFormats = formats => {
	  return formats.map(format => {
	    return {
	      title: format.name,
	      value: format.code
	    };
	  });
	};
	const getOptionsFromEngines = engines => {
	  return engines.map(engine => {
	    return {
	      title: engine.title,
	      value: engine.code
	    };
	  });
	};

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1;
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _currentValues = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentValues");
	var _openOptionsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openOptionsMenu");
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _getSelectedEngineCodeFromEngines = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectedEngineCodeFromEngines");
	var _renderParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderParams");
	var _renderParam = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderParam");
	var _showOptionsMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showOptionsMenu");
	var _getMenuOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuOptions");
	var _getMenuItemsFromOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemsFromOptions");
	var _getMenuItemHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItemHtml");
	var _handleMenuItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleMenuItemClick");
	var _initParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initParams");
	class ImageConfiguratorParams extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super(_options);
	    Object.defineProperty(this, _initParams, {
	      value: _initParams2
	    });
	    Object.defineProperty(this, _handleMenuItemClick, {
	      value: _handleMenuItemClick2
	    });
	    Object.defineProperty(this, _getMenuItemHtml, {
	      value: _getMenuItemHtml2
	    });
	    Object.defineProperty(this, _getMenuItemsFromOptions, {
	      value: _getMenuItemsFromOptions2
	    });
	    Object.defineProperty(this, _getMenuOptions, {
	      value: _getMenuOptions2
	    });
	    Object.defineProperty(this, _showOptionsMenu, {
	      value: _showOptionsMenu2
	    });
	    Object.defineProperty(this, _renderParam, {
	      value: _renderParam2
	    });
	    Object.defineProperty(this, _renderParams, {
	      value: _renderParams2
	    });
	    Object.defineProperty(this, _getSelectedEngineCodeFromEngines, {
	      value: _getSelectedEngineCodeFromEngines2
	    });
	    Object.defineProperty(this, _container$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentValues, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _openOptionsMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: {}
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = babelHelpers.classPrivateFieldLooseBase(this, _initParams)[_initParams](_options);
	    const data = {
	      format: babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].format.options[0].value,
	      engine: babelHelpers.classPrivateFieldLooseBase(this, _getSelectedEngineCodeFromEngines)[_getSelectedEngineCodeFromEngines](_options.engines)
	    };
	    const handler = {
	      set: (target, property, value) => {
	        Reflect.set(target, property, value);
	        if (babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] && (property === 'format' || property === 'engine')) {
	          const option = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][property].options.find(currentOption => currentOption.value === value);
	          const optionElem = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1].querySelector(`#ai__copilot-image-params-item-${property}`);
	          if (optionElem) {
	            optionElem.innerText = option.title;
	            main_core.Dom.attr(optionElem, 'title', option.title);
	          }
	        }
	        return true;
	      }
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues] = new Proxy(data, handler);
	    this.setEventNamespace('AI.Copilot.ImageParams');
	  }
	  getCurrentValues() {
	    return {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues]
	    };
	  }
	  isContainsTarget(target) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4;
	    return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]) == null ? void 0 : _babelHelpers$classPr.contains(target)) || ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu]) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.getPopupWindow()) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getPopupContainer()) == null ? void 0 : _babelHelpers$classPr4.contains(target));
	  }
	  setFormats(formats) {
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].format = getParams({
	      formats,
	      engines: []
	    }).format;
	  }
	  setSelectedEngine(engineCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues].engine = engineCode;
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="ai__copilot-image-params">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderParams)[_renderParams](babelHelpers.classPrivateFieldLooseBase(this, _params)[_params]));
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1];
	  }
	}
	function _getSelectedEngineCodeFromEngines2(engines) {
	  var _engines$;
	  const selectedEngine = engines.find(engine => engine.selected);
	  return selectedEngine.code || (engines == null ? void 0 : (_engines$ = engines[0]) == null ? void 0 : _engines$.code);
	}
	function _renderParams2(parameters) {
	  return Object.entries(parameters).map(([parameterName, parameter]) => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _renderParam)[_renderParam](parameter, parameterName);
	  });
	}
	function _renderParam2(options, parameterName) {
	  const selectedOption = options.options.find(option => option.value === babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues][parameterName]);
	  const icon = new ui_iconSet_api_core.Icon({
	    size: 24,
	    icon: options.icon,
	    color: '#8E52EC'
	  });
	  const rightChevronIcon = new ui_iconSet_api_core.Icon({
	    size: 16,
	    icon: ui_iconSet_api_core.Actions.CHEVRON_RIGHT,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-base-50')
	  });
	  const param = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="ai__copilot-image-params-item">
				<div class="ai__copilot-image-params-item_title">
					<div class="ai__copilot-image-params-item_title-icon">
						${0}
					</div>
					<div
						class="ai__copilot-image-params-item_title-text"
						title="${0}"
					>
						${0}
					</div>
				</div>
				<div ref="value" class="ai__copilot-image-params-item_value">
					<div
						id="ai__copilot-image-params-item-${0}"
						class="ai__copilot-image-params-item_value-text"
						title="${0}"
					>
						${0}
					</div>
					<div class="ai__copilot-image-params-item_value-arrow-icon">
						${0}
					</div>
				</div>
			</div>
		`), icon.render(), options.title, options.title, parameterName, selectedOption.title, selectedOption.title, rightChevronIcon.render());
	  main_core.Event.bind(param.root, 'click', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu].close();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _showOptionsMenu)[_showOptionsMenu](param.value, babelHelpers.classPrivateFieldLooseBase(this, _params)[_params][parameterName].options, parameterName);
	    }
	  });
	  return param.root;
	}
	function _showOptionsMenu2(bindElement, options, parameterName) {
	  babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu] = new main_popup.Menu({
	    ...babelHelpers.classPrivateFieldLooseBase(this, _getMenuOptions)[_getMenuOptions](bindElement, options, parameterName)
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu].show();
	}
	function _getMenuOptions2(bindElement, imageConfiguratorParams, parameterName) {
	  const position = main_core.Dom.getPosition(bindElement);
	  return {
	    bindElement: {
	      top: position.top - 6,
	      left: position.right + 18
	    },
	    cacheable: false,
	    minWidth: 200,
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemsFromOptions)[_getMenuItemsFromOptions](imageConfiguratorParams, parameterName),
	    events: {
	      onPopupAfterClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu] = null;
	      }
	    }
	  };
	}
	function _getMenuItemsFromOptions2(options, parameterName) {
	  return options.map(option => {
	    const isSelectedOption = option.value === babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues][parameterName];
	    return {
	      id: option.value,
	      text: option.title,
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItemHtml)[_getMenuItemHtml](option.title, isSelectedOption),
	      onclick: babelHelpers.classPrivateFieldLooseBase(this, _handleMenuItemClick)[_handleMenuItemClick](parameterName, option.value)
	    };
	  });
	}
	function _getMenuItemHtml2(title, isSelected) {
	  const selectedIcon = new ui_iconSet_api_core.Icon({
	    size: 18,
	    icon: ui_iconSet_api_core.Main.CHECK,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-link-primary-base')
	  });
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
			<div class="ai__copilot-image-params-popup-item">
				<span class="ai__copilot-image-params-popup-item_title">${0}</span>
				${0}
			</div>
		`), title, isSelected ? selectedIcon.render() : null);
	}
	function _handleMenuItemClick2(parameterName, parameterValue) {
	  return (e, menuItem) => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues][parameterName] !== parameterValue) {
	      this.emit('change-parameter', {
	        parameter: parameterName,
	        value: parameterValue
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues][parameterName] = parameterValue;
	    menuItem.getMenuWindow().close();
	    babelHelpers.classPrivateFieldLooseBase(this, _openOptionsMenu)[_openOptionsMenu] = null;
	  };
	}
	function _initParams2(options) {
	  const params = getParams({
	    formats: Object.values(options.formats),
	    engines: options.engines
	  });
	  const changeParamsHandler = {
	    set: (target, property, value) => {
	      Reflect.set(target, property, value);
	      if (property === 'format') {
	        babelHelpers.classPrivateFieldLooseBase(this, _currentValues)[_currentValues].format = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].format.options[0].value;
	      }
	      return true;
	    }
	  };
	  return new Proxy(params, changeParamsHandler);
	}

	let _$2 = t => t,
	  _t$2;
	var _container$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _imageConfiguratorStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imageConfiguratorStyles");
	var _imageConfiguratorParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imageConfiguratorParams");
	var _renderImageStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderImageStyles");
	var _renderImageParams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderImageParams");
	class ImageConfigurator extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$styles, _options$formats, _options$engines;
	    super(options);
	    Object.defineProperty(this, _renderImageParams, {
	      value: _renderImageParams2
	    });
	    Object.defineProperty(this, _renderImageStyles, {
	      value: _renderImageStyles2
	    });
	    Object.defineProperty(this, _container$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _imageConfiguratorStyles, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _imageConfiguratorParams, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.Copilot.ImageConfigurator');
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorStyles)[_imageConfiguratorStyles] = new ImageConfiguratorStyles({
	      styles: (_options$styles = options == null ? void 0 : options.styles) != null ? _options$styles : []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams] = new ImageConfiguratorParams({
	      formats: (_options$formats = options == null ? void 0 : options.formats) != null ? _options$formats : [],
	      engines: (_options$engines = options == null ? void 0 : options.engines) != null ? _options$engines : []
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams].subscribe('change-parameter', event => {
	      const data = event.getData();
	      this.emit('change-parameter', data);
	    });
	  }
	  setFormats(formats) {
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams].setFormats(formats);
	  }
	  setSelectedEngine(engineCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams].setSelectedEngine(engineCode);
	  }
	  getParams() {
	    return {
	      style: babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorStyles)[_imageConfiguratorStyles].getSelectedStyle(),
	      ...babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams].getCurrentValues()
	    };
	  }
	  isContainsTarget(target) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    return ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2]) == null ? void 0 : _babelHelpers$classPr.contains(target)) || ((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams]) == null ? void 0 : _babelHelpers$classPr2.isContainsTarget(target));
	  }
	  render() {
	    babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2] = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="ai__copilot-image-configurator">
				<div class="ai__copilot-image-configurator_styles">
					${0}
				</div>
				<div class="ai__copilot-image-configurator_params">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderImageStyles)[_renderImageStyles](), babelHelpers.classPrivateFieldLooseBase(this, _renderImageParams)[_renderImageParams]());
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2];
	  }
	}
	function _renderImageStyles2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorStyles)[_imageConfiguratorStyles].render();
	}
	function _renderImageParams2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorParams)[_imageConfiguratorParams].render();
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$2,
	  _t3$2;
	const ImageConfiguratorPopupEvents = Object.freeze({
	  completions: 'completions',
	  back: 'back',
	  selectEngine: 'selectEngine'
	});
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _popupOffset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupOffset");
	var _popupId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupId");
	var _imageConfigurator = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imageConfigurator");
	var _withoutBackBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("withoutBackBtn");
	var _submitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("submitButton");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _loaderOverlay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loaderOverlay");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	var _renderBackBtnIfNeeded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderBackBtnIfNeeded");
	var _initSubmitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initSubmitButton");
	class ImageConfiguratorPopup extends main_core_events.EventEmitter {
	  constructor(options) {
	    super(options);
	    Object.defineProperty(this, _initSubmitButton, {
	      value: _initSubmitButton2
	    });
	    Object.defineProperty(this, _renderBackBtnIfNeeded, {
	      value: _renderBackBtnIfNeeded2
	    });
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popupOffset, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popupId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _imageConfigurator, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _withoutBackBtn, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _submitButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loaderOverlay, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _popupId)[_popupId] = options.popupId || String(Math.random());
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = options.bindElement;
	    babelHelpers.classPrivateFieldLooseBase(this, _popupOffset)[_popupOffset] = options.popupOffset;
	    babelHelpers.classPrivateFieldLooseBase(this, _withoutBackBtn)[_withoutBackBtn] = options.withoutBackBtn === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator] = new ImageConfigurator({
	      formats: options.imageConfiguratorOptions.formats,
	      styles: options.imageConfiguratorOptions.styles,
	      engines: options.imageConfiguratorOptions.engines
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].subscribe('change-parameter', event => {
	      const data = event.getData();
	      if (data.parameter === 'engine') {
	        this.emit(ImageConfiguratorPopupEvents.selectEngine, data.value);
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _initSubmitButton)[_initSubmitButton]();
	    this.setEventNamespace('AI.Copilot:ImagePopup');
	  }
	  getPopupId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popupId)[_popupId];
	  }
	  getPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	  destroy() {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr2.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	  }
	  isShown() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].isShown();
	  }
	  isContainsTarget(target) {
	    var _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5;
	    return ((_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : (_babelHelpers$classPr4 = _babelHelpers$classPr3.getPopupContainer()) == null ? void 0 : _babelHelpers$classPr4.contains(target)) || ((_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator]) == null ? void 0 : _babelHelpers$classPr5.isContainsTarget(target));
	  }
	  adjustPosition() {
	    var _babelHelpers$classPr6;
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr6.adjustPosition({
	      forceBindPosition: true
	    });
	  }
	  getImageConfiguration() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].getParams();
	  }
	  setFormats(formats) {
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].setFormats(formats);
	  }
	  setSelectedEngine(engineCode) {
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].setSelectedEngine(engineCode);
	  }
	  disableSubmitButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setDisabled(true);
	  }
	  enableSubmitButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].setDisabled(false);
	  }
	  showLoader() {
	    var _babelHelpers$classPr7;
	    babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay] = main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div class="ai__copilot-image-configurator-popup-loader-overlay"></div>
		`));
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay], (_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr7.getPopupContainer());
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      size: 110,
	      color: getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary'),
	      target: babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay]);
	  }
	  hideLoader() {
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].destroy();
	    main_core.Dom.remove(babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay]);
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _loaderOverlay)[_loaderOverlay] = null;
	  }
	}
	function _createPopup2() {
	  var _babelHelpers$classPr8, _babelHelpers$classPr9;
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    id: babelHelpers.classPrivateFieldLooseBase(this, _popupId)[_popupId],
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement],
	    cacheable: true,
	    width: 278,
	    padding: 0,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent]()
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setOffset({
	    offsetTop: (_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _popupOffset)[_popupOffset]) == null ? void 0 : _babelHelpers$classPr8.top,
	    offsetLeft: (_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _popupOffset)[_popupOffset]) == null ? void 0 : _babelHelpers$classPr9.left
	  });
	}
	function _renderPopupContent2() {
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
			<div class="ai__copilot-image-configurator-popup-content">
				<header class="ai__copilot-image-configurator-popup-content_header">
					${0}
					<div class="ai__copilot-image-configurator-popup-content_title">
						${0}
					</div>
				</header>
				<div class="ai__copilot-image-configurator-popup-content_params">
					${0}
				</div>
				<div class="ai__copilot-image-configurator-popup-content_footer">
					${0}
				</div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _renderBackBtnIfNeeded)[_renderBackBtnIfNeeded](), main_core.Loc.getMessage('AI_COPILOT_IMAGE_POPUP_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].render(), babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton].render());
	}
	function _renderBackBtnIfNeeded2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _withoutBackBtn)[_withoutBackBtn]) {
	    return null;
	  }
	  const backBtnIcon = new ui_iconSet_api_core.Icon({
	    size: 24,
	    color: getComputedStyle(document.body).getPropertyValue('--ui-color-base-90'),
	    icon: ui_iconSet_api_core.Actions.CHEVRON_LEFT
	  });
	  const backBtnIconElem = backBtnIcon.render();
	  main_core.Event.bind(backBtnIconElem, 'click', () => {
	    this.emit(ImageConfiguratorPopupEvents.back, new main_core_events.BaseEvent());
	  });
	  return main_core.Tag.render(_t3$2 || (_t3$2 = _$3`
			<div class="ai__copilot-image-configurator-popup-content_back-btn">
				${0}
			</div>
		`), backBtnIconElem);
	}
	function _initSubmitButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _submitButton)[_submitButton] = new ui_buttons.Button({
	    color: ui_buttons.Button.Color.AI,
	    text: main_core.Loc.getMessage('AI_COPILOT_IMAGE_POPUP_GENERATE_BTN'),
	    round: true,
	    noCaps: true,
	    onclick: () => {
	      this.emit(ImageConfiguratorPopupEvents.completions, new main_core_events.BaseEvent({
	        data: babelHelpers.classPrivateFieldLooseBase(this, _imageConfigurator)[_imageConfigurator].getParams()
	      }));
	    }
	  });
	}

	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _copilotContainer$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotContainer");
	var _inputField$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputField");
	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _imageConfiguratorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("imageConfiguratorPopup");
	var _errorMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorMenu");
	var _resultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultMenu");
	var _resultImageUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resultImageUrl");
	var _copilotInputEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("copilotInputEvents");
	var _CopilotMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("CopilotMenu");
	var _popupWithoutBackBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popupWithoutBackBtn");
	var _currentGenerateRequestId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentGenerateRequestId");
	var _useInsertAboveAndUnderTextMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useInsertAboveAndUnderTextMenuItems");
	var _formats = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formats");
	var _styles$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("styles");
	var _engines = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engines");
	var _inputFieldCancelLoadingEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldCancelLoadingEventHandler");
	var _inputFieldSubmitEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldSubmitEventHandler");
	var _inputFieldAdjustHeightEventHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputFieldAdjustHeightEventHandler");
	var _initImageConfiguratorPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initImageConfiguratorPopup");
	var _subscribeToInputFieldEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeToInputFieldEvents");
	var _unsubscribeFromInputFieldEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribeFromInputFieldEvents");
	var _handleCompletionsError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCompletionsError");
	var _setPayload = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setPayload");
	var _handleInputFieldSubmitEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldSubmitEvent");
	var _handleInputFieldCancelLoadingEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldCancelLoadingEvent");
	var _handleInputFieldAdjustHeightEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFieldAdjustHeightEvent");
	var _showResultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showResultMenu");
	var _initResultMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initResultMenu");
	var _showErrorMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showErrorMenu");
	var _initErrorMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initErrorMenu");
	class CopilotImageController extends main_core_events.EventEmitter {
	  constructor(_options) {
	    super(_options);
	    Object.defineProperty(this, _initErrorMenu, {
	      value: _initErrorMenu2
	    });
	    Object.defineProperty(this, _showErrorMenu, {
	      value: _showErrorMenu2
	    });
	    Object.defineProperty(this, _initResultMenu, {
	      value: _initResultMenu2
	    });
	    Object.defineProperty(this, _showResultMenu, {
	      value: _showResultMenu2
	    });
	    Object.defineProperty(this, _handleInputFieldAdjustHeightEvent, {
	      value: _handleInputFieldAdjustHeightEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldCancelLoadingEvent, {
	      value: _handleInputFieldCancelLoadingEvent2
	    });
	    Object.defineProperty(this, _handleInputFieldSubmitEvent, {
	      value: _handleInputFieldSubmitEvent2
	    });
	    Object.defineProperty(this, _setPayload, {
	      value: _setPayload2
	    });
	    Object.defineProperty(this, _handleCompletionsError, {
	      value: _handleCompletionsError2
	    });
	    Object.defineProperty(this, _unsubscribeFromInputFieldEvents, {
	      value: _unsubscribeFromInputFieldEvents2
	    });
	    Object.defineProperty(this, _subscribeToInputFieldEvents, {
	      value: _subscribeToInputFieldEvents2
	    });
	    Object.defineProperty(this, _initImageConfiguratorPopup, {
	      value: _initImageConfiguratorPopup2
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotContainer$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputField$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _imageConfiguratorPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _errorMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resultImageUrl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _copilotInputEvents, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _CopilotMenu, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popupWithoutBackBtn, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _currentGenerateRequestId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useInsertAboveAndUnderTextMenuItems, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _formats, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _styles$1, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _engines, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _inputFieldCancelLoadingEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldSubmitEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputFieldAdjustHeightEventHandler, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('AI.CopilotImage');
	    babelHelpers.classPrivateFieldLooseBase(this, _resultImageUrl)[_resultImageUrl] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1] = _options.inputField;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1] = _options.copilotContainer;
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = _options.engine;
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents] = _options.copilotInputEvents;
	    babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu] = _options.copilotMenu;
	    babelHelpers.classPrivateFieldLooseBase(this, _popupWithoutBackBtn)[_popupWithoutBackBtn] = _options.popupWithoutBackBtn === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _useInsertAboveAndUnderTextMenuItems)[_useInsertAboveAndUnderTextMenuItems] = _options.useInsertAboveAndUnderMenuItems;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = _options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldSubmitEvent)[_handleInputFieldSubmitEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldCancelLoadingEvent)[_handleInputFieldCancelLoadingEvent].bind(this);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler] = babelHelpers.classPrivateFieldLooseBase(this, _handleInputFieldAdjustHeightEvent)[_handleInputFieldAdjustHeightEvent].bind(this);
	  }
	  setCopilotContainer(copilotContainer) {
	    babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1] = copilotContainer;
	  }
	  getResultImageUrl() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _resultImageUrl)[_resultImageUrl];
	  }
	  isContainsTarget(target) {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	    const isImageConfiguratorPopup = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr.isContainsTarget(target);
	    const isErrorMenu = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr2.contains(target);
	    const isResultMenu = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr3.contains(target);
	    return isImageConfiguratorPopup || isErrorMenu || isResultMenu;
	  }
	  async init() {
	    const res = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getImageCopilotTooling();
	    babelHelpers.classPrivateFieldLooseBase(this, _formats)[_formats] = res.data.params.formats;
	    babelHelpers.classPrivateFieldLooseBase(this, _engines)[_engines] = res.data.engines;
	    babelHelpers.classPrivateFieldLooseBase(this, _styles$1)[_styles$1] = res.data.params.styles;
	  }
	  showImageConfigurator() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initImageConfiguratorPopup)[_initImageConfiguratorPopup]();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].show();
	  }
	  showMenu() {
	    var _babelHelpers$classPr4, _babelHelpers$classPr5, _babelHelpers$classPr6;
	    (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr4.show();
	    (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr5.show();
	    (_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr6.show();
	  }
	  getAnalytics() {
	    const usedTextInput = babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].usedTextInput();
	    const usedVoiceRecord = babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].usedVoiceRecord();
	    if (usedTextInput && usedVoiceRecord) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromTextAndAudio();
	    } else if (usedTextInput) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromText();
	    } else if (usedVoiceRecord) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setContextTypeFromAudio();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setCategoryImage();
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].setTypeImageNew();
	    return babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics];
	  }
	  start() {
	    this.showImageConfigurator();
	    babelHelpers.classPrivateFieldLooseBase(this, _subscribeToInputFieldEvents)[_subscribeToInputFieldEvents]();
	  }
	  finish() {
	    babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] = -1;
	    this.destroyAllMenus();
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeFromInputFieldEvents)[_unsubscribeFromInputFieldEvents]();
	  }
	  isShown() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].isShown();
	  }
	  getOpenMenuPopup() {
	    var _babelHelpers$classPr7, _babelHelpers$classPr8, _babelHelpers$classPr9;
	    return ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr7.getPopup()) || ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr8.getPopup()) || ((_babelHelpers$classPr9 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr9.getPopup()) || null;
	  }
	  hideAllMenus() {
	    var _babelHelpers$classPr10, _babelHelpers$classPr11, _babelHelpers$classPr12;
	    (_babelHelpers$classPr10 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr10.hide();
	    (_babelHelpers$classPr11 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr11.hide();
	    (_babelHelpers$classPr12 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr12.hide();
	  }
	  destroyAllMenus() {
	    var _babelHelpers$classPr13, _babelHelpers$classPr14, _babelHelpers$classPr15;
	    (_babelHelpers$classPr13 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr13.close();
	    (_babelHelpers$classPr14 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr14.close();
	    (_babelHelpers$classPr15 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr15.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup] = null;
	  }
	  adjustMenusPosition() {
	    var _babelHelpers$classPr16, _babelHelpers$classPr17, _babelHelpers$classPr18;
	    (_babelHelpers$classPr16 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr16.adjustPosition();
	    (_babelHelpers$classPr17 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr17.adjustPosition();
	    (_babelHelpers$classPr18 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr18.adjustPosition();
	  }
	  async completions() {
	    this.destroyAllMenus();
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1], '--error');
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].startGenerating();
	    try {
	      const id = Math.round(Math.random() * 10000);
	      babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] = id;
	      const res = await babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].imageCompletions();
	      if (babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] !== id) {
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _resultImageUrl)[_resultImageUrl] = JSON.parse(res.data.result)[0];
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].finishGenerating();
	      babelHelpers.classPrivateFieldLooseBase(this, _showResultMenu)[_showResultMenu]();
	      this.emit('completion-result', new main_core_events.BaseEvent({
	        data: {
	          imageUrl: babelHelpers.classPrivateFieldLooseBase(this, _resultImageUrl)[_resultImageUrl]
	        }
	      }));
	    } catch (error) {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].finishGenerating();
	      babelHelpers.classPrivateFieldLooseBase(this, _handleCompletionsError)[_handleCompletionsError](error);
	    }
	  }
	}
	function _initImageConfiguratorPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup] = new ImageConfiguratorPopup({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1],
	    popupId: 'ai-image-configuration-popup',
	    popupOffset: {
	      top: 8
	    },
	    withoutBackBtn: babelHelpers.classPrivateFieldLooseBase(this, _popupWithoutBackBtn)[_popupWithoutBackBtn],
	    imageConfiguratorOptions: {
	      styles: babelHelpers.classPrivateFieldLooseBase(this, _styles$1)[_styles$1],
	      formats: babelHelpers.classPrivateFieldLooseBase(this, _formats)[_formats],
	      engines: babelHelpers.classPrivateFieldLooseBase(this, _engines)[_engines]
	    }
	  });
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].getValue()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].disableSubmitButton();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].subscribe(ImageConfiguratorPopupEvents.completions, e => {
	    const {
	      style,
	      format,
	      engine
	    } = e.getData();
	    babelHelpers.classPrivateFieldLooseBase(this, _setPayload)[_setPayload]({
	      style,
	      format,
	      engine
	    });
	    this.completions();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].subscribe(ImageConfiguratorPopupEvents.selectEngine, event => {
	    const engineCode = event.getData();
	    const oldSelectedEngineCode = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].getImageConfiguration().engine;
	    let isRequestComplete = false;
	    setTimeout(() => {
	      if (isRequestComplete === false) {
	        babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].showLoader();
	      }
	    }, 300);
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getImageEngineParams(engineCode).then(res => {
	      const data = res.data;
	      babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].setFormats(data.formats);
	    }).catch(error => {
	      BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('AI_COPILOT_IMAGE_FETCH_NEW_ENGINE_PARAMS_ERROR')
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].setSelectedEngine(oldSelectedEngineCode);
	      console.error(error);
	    }).finally(() => {
	      isRequestComplete = true;
	      babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].hideLoader();
	    });
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].subscribe(ImageConfiguratorPopupEvents.back, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeFromInputFieldEvents)[_unsubscribeFromInputFieldEvents]();
	    this.emit('back');
	  });
	}
	function _subscribeToInputFieldEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].input, event => {
	    const inputFieldValue = event.getData();
	    if (inputFieldValue) {
	      var _babelHelpers$classPr19;
	      (_babelHelpers$classPr19 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr19.enableSubmitButton();
	    } else {
	      var _babelHelpers$classPr20;
	      (_babelHelpers$classPr20 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr20.disableSubmitButton();
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].cancelLoading, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].submit, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].adjustHeight, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler]);
	}
	function _unsubscribeFromInputFieldEvents2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].cancelLoading, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldCancelLoadingEventHandler)[_inputFieldCancelLoadingEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].submit, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldSubmitEventHandler)[_inputFieldSubmitEventHandler]);
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].unsubscribe(babelHelpers.classPrivateFieldLooseBase(this, _copilotInputEvents)[_copilotInputEvents].adjustHeight, babelHelpers.classPrivateFieldLooseBase(this, _inputFieldAdjustHeightEventHandler)[_inputFieldAdjustHeightEventHandler]);
	}
	function _handleCompletionsError2(error) {
	  var _error$errors;
	  const firstError = error == null ? void 0 : (_error$errors = error.errors) == null ? void 0 : _error$errors[0];
	  if (firstError && (firstError == null ? void 0 : firstError.code) === 'LIMIT_IS_EXCEEDED_BAAS') {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].disable();
	  } else if (firstError && (firstError.code === 'LIMIT_IS_EXCEEDED_MONTHLY' || firstError.code === 'LIMIT_IS_EXCEEDED_DAILY' || firstError.code === 'SERVICE_IS_NOT_AVAILABLE_BY_TARIFF')) {
	    this.emit('close');
	  } else if (firstError) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1], '--error');
	    babelHelpers.classPrivateFieldLooseBase(this, _showErrorMenu)[_showErrorMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].setErrors([{
	      code: firstError.code,
	      message: firstError.message
	    }]);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].setErrors([{
	      code: -1,
	      message: main_core.Loc.getMessage('AI_COPILOT_IMAGE_GENERATION_ERROR')
	    }]);
	  }
	  ai_ajaxErrorHandler.AjaxErrorHandler.handleImageGenerateError({
	    baasOptions: {
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].getContainer().querySelector('.ai__copilot_input-field-baas-point'),
	      context: babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].getContextId(),
	      useAngle: false
	    },
	    errorCode: firstError == null ? void 0 : firstError.code
	  });
	}
	function _setPayload2(options) {
	  const payload = new ai_engine.Text({
	    prompt: babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].getValue(),
	    engineCode: options.engine
	  });
	  payload.setMarkers({
	    style: options.style,
	    format: options.format
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setPayload(payload);
	  babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].setAnalyticParameters({
	    type: this.getAnalytics().getType(),
	    c_sub_section: this.getAnalytics().getCSubSection(),
	    c_section: this.getAnalytics().getCSection(),
	    c_element: this.getAnalytics().getCElement()
	  });
	  this.getAnalytics().setP1('prompt', options.style).setP2('format', options.format);
	}
	function _handleInputFieldSubmitEvent2() {
	  var _babelHelpers$classPr21, _babelHelpers$classPr22;
	  if (!((_babelHelpers$classPr21 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) != null && _babelHelpers$classPr21.isShown()) || !((_babelHelpers$classPr22 = babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].getValue()) != null && _babelHelpers$classPr22.trim())) {
	    return;
	  }
	  const {
	    style,
	    format,
	    engine
	  } = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup].getImageConfiguration();
	  babelHelpers.classPrivateFieldLooseBase(this, _setPayload)[_setPayload]({
	    style,
	    format,
	    engine
	  });
	  this.completions();
	}
	function _handleInputFieldCancelLoadingEvent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _currentGenerateRequestId)[_currentGenerateRequestId] = -1;
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].finishGenerating();
	  babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1].focus();
	  this.showImageConfigurator();
	}
	function _handleInputFieldAdjustHeightEvent2() {
	  var _babelHelpers$classPr23, _babelHelpers$classPr24, _babelHelpers$classPr25;
	  (_babelHelpers$classPr23 = babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) == null ? void 0 : _babelHelpers$classPr23.adjustPosition();
	  (_babelHelpers$classPr24 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr24.adjustPosition();
	  (_babelHelpers$classPr25 = babelHelpers.classPrivateFieldLooseBase(this, _imageConfiguratorPopup)[_imageConfiguratorPopup]) == null ? void 0 : _babelHelpers$classPr25.adjustPosition();
	}
	function _showResultMenu2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initResultMenu)[_initResultMenu]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].open();
	}
	function _initResultMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu] = new (babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu])({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1],
	    offsetTop: 8,
	    offsetLeft: 0,
	    items: ImageConfiguratorResultMenuItems.getMenuItems({
	      copilotImageController: this,
	      inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1],
	      useInsertAboveAndUnderMenuItems: babelHelpers.classPrivateFieldLooseBase(this, _useInsertAboveAndUnderTextMenuItems)[_useInsertAboveAndUnderTextMenuItems]
	    }),
	    keyboardControlOptions: {
	      clearHighlightAfterType: false,
	      canGoOutFromTop: false,
	      highlightFirstItemAfterShow: true
	    },
	    cacheable: false
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _resultMenu)[_resultMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1], {
	    left: 0,
	    top: 8
	  });
	}
	function _showErrorMenu2() {
	  var _babelHelpers$classPr26;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _initErrorMenu)[_initErrorMenu]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu].setBindElement(babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1], {
	    top: 8,
	    left: 0
	  });
	  (_babelHelpers$classPr26 = babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu]) == null ? void 0 : _babelHelpers$classPr26.open();
	}
	function _initErrorMenu2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _errorMenu)[_errorMenu] = new (babelHelpers.classPrivateFieldLooseBase(this, _CopilotMenu)[_CopilotMenu])({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1],
	    offsetTop: 8,
	    items: ImageConfiguratorErrorMenuItems.getMenuItems({
	      copilotImageController: this,
	      inputField: babelHelpers.classPrivateFieldLooseBase(this, _inputField$1)[_inputField$1],
	      copilotContainer: babelHelpers.classPrivateFieldLooseBase(this, _copilotContainer$1)[_copilotContainer$1]
	    }),
	    keyboardControlOptions: {
	      canGoOutFromTop: false,
	      highlightFirstItemAfterShow: true,
	      clearHighlightAfterType: false
	    },
	    cacheable: false
	  });
	}

	exports.CopilotImageController = CopilotImageController;

}((this.BX.AI = this.BX.AI || {}),BX.AI,BX.AI,BX,BX.UI,BX.Event,BX.Main,BX,BX.UI.IconSet));
//# sourceMappingURL=copilot-image-controller.bundle.js.map
