/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,sign_v2_analytics,sign_v2_helper,main_popup,sign_tour,spotlight,ui_buttons,ui_dialogs_messagebox,ui_infoHelper,sign_backend,date,ui_notification,ui_stamp_uploader,sign_v2_api,crm_form_fields_selector,crm_requisite_fieldsetViewer,sign_type,sign_v2_b2e_fieldSelector,sign_ui,color_picker,sign_document,main_core,ui_draganddrop_draggable,main_core_events) {
	'use strict';

	let _ = t => t,
	  _t;
	class Dummy extends main_core_events.EventEmitter {
	  /**
	   * Constructor.
	   * @param {Block} block
	   */
	  constructor(block) {
	    super();
	    this.data = {};
	    this.events = {
	      onChange: 'onChange',
	      onColorStyleChange: 'onColorStyleChange'
	    };
	    this.setEventNamespace('BX.Sign.V2.Editor.Blocks.Dummy');
	    this.block = block;
	  }

	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return false;
	  }

	  /**
	   * Returns true if style panel mast be showed.
	   * @return {boolean}
	   */
	  isStyleAllowed() {
	    return true;
	  }

	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    this.data = data ? data : {};
	  }

	  /**
	   * Changes only text key in data.
	   * @param {string} text
	   */
	  setText(text) {
	    this.setData({
	      text
	    });
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 250,
	      height: 28
	    };
	  }

	  /**
	   * Returns current data.
	   * @return {any}
	   */
	  getData() {
	    return this.data;
	  }

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    return null;
	  }

	  /**
	   * @return {HTMLElement | null}
	   */
	  getBlockCaption() {
	    return null;
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    return main_core.Tag.render(_t || (_t = _`
			<div>
				${0}
			</div>
		`), main_core.Text.encode(this.data.text || '').toString().replaceAll('[br]', '<br>'));
	  }

	  /**
	   * Calls when block starts being resized or moved.
	   */
	  onStartChangePosition() {}

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {}

	  /**
	   * Calls when block saved.
	   */
	  onSave() {}

	  /**
	   * Calls when block removed.
	   */
	  onRemove() {}

	  /**
	   * Calls when click was out the block.
	   */
	  onClickOut() {
	    this.block.forceSave();
	  }
	  onChange() {
	    this.emit(this.events.onChange);
	  }
	  getStyles() {
	    return {};
	  }
	  changeStyleColor(color) {}
	}
	Dummy.defaultTextBlockPaddingStyles = {
	  padding: "5px 8px"
	};

	let _$1 = t => t,
	  _t$1;
	var _calendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calendar");
	var _calendarOpened = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("calendarOpened");
	var _date = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("date");
	var _closeCalendar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeCalendar");
	class Date extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _closeCalendar, {
	      value: _closeCalendar2
	    });
	    Object.defineProperty(this, _calendar, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _calendarOpened, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _date, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 105,
	      height: 28
	    };
	  }

	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _date)[_date] = this.data.text;
	  }

	  /**
	   * Calls when action button was clicked.
	   */
	  onActionClick() {
	    babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar] = BX.calendar({
	      node: this.block.getLayout(),
	      field: this.getViewContent(),
	      value: babelHelpers.classPrivateFieldLooseBase(this, _date)[_date],
	      bTime: false,
	      callback_after: date$$1 => {
	        this.setText(BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')), date$$1, null));
	        this.block.renderView();
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _calendarOpened)[_calendarOpened] = true;
	    const targetContainer = document.querySelector('.sign-editor__content');
	    const {
	      popup
	    } = babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar];
	    popup.targetContainer = targetContainer;
	    if (popup.popupContainer.parentElement !== targetContainer) {
	      main_core.Dom.append(popup.popupContainer, targetContainer);
	      popup.adjustPosition();
	    }
	  }

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement}
	   */
	  getActionButton() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action">
					${0}
				</button>
			</div>
		`), this.onActionClick.bind(this), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_DATE_ACTION_BUTTON'));
	  }

	  /**
	   * Closes calendar if open.
	   */

	  /**
	   * Calls when block starts being resized or moved.
	   */
	  onStartChangePosition() {
	    babelHelpers.classPrivateFieldLooseBase(this, _closeCalendar)[_closeCalendar]();
	  }

	  /**
	   * Calls when block saved.
	   */
	  onSave() {
	    babelHelpers.classPrivateFieldLooseBase(this, _closeCalendar)[_closeCalendar]();
	  }

	  /**
	   * Calls when block removed.
	   */
	  onRemove() {
	    babelHelpers.classPrivateFieldLooseBase(this, _closeCalendar)[_closeCalendar]();
	  }

	  /**
	   * Calls when click was out the block.
	   */
	  onClickOut() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _calendarOpened)[_calendarOpened]) {
	      this.block.forceSave();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _calendarOpened)[_calendarOpened] = false;
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...Date.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _closeCalendar2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _calendar)[_calendar].Close();
	    babelHelpers.classPrivateFieldLooseBase(this, _calendarOpened)[_calendarOpened] = false;
	  }
	}

	let _$2 = t => t,
	  _t$2;
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _onActionClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _getFieldsNameBlackList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _getCreateFieldTypeNamesBlackList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateFieldTypeNamesBlackList");
	var _filterRequisiteCreateFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterRequisiteCreateFields");
	var _getFieldsetViewer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsetViewer");
	var _onFieldsetViewerFieldsetLoadError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldsetViewerFieldsetLoadError");
	class MyRequisites extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onFieldsetViewerFieldsetLoadError, {
	      value: _onFieldsetViewerFieldsetLoadError2
	    });
	    Object.defineProperty(this, _getFieldsetViewer, {
	      value: _getFieldsetViewer2
	    });
	    Object.defineProperty(this, _filterRequisiteCreateFields, {
	      value: _filterRequisiteCreateFields2
	    });
	    Object.defineProperty(this, _getCreateFieldTypeNamesBlackList, {
	      value: _getCreateFieldTypeNamesBlackList2
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList, {
	      value: _getFieldsNameBlackList2
	    });
	    Object.defineProperty(this, _onActionClick, {
	      value: _onActionClick2
	    });
	    Object.defineProperty(this, _cache, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	  }
	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return true;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 250,
	      height: 220
	    };
	  }

	  /**
	   * Calls when action button was clicked.
	   * @param {PointerEvent} event
	   */

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement}
	   */
	  getActionButton() {
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
					${0}
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick)[_onActionClick].bind(this), this.block.getCode(), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_ACTION_BUTTON'));
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...MyRequisites.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onActionClick2(event) {
	  event.stopPropagation();
	  babelHelpers.classPrivateFieldLooseBase(this, _getFieldsetViewer)[_getFieldsetViewer]().show();
	}
	function _getFieldsNameBlackList2() {
	  return ['ADDRESS', 'REG_ADDRESS'];
	}
	function _getCreateFieldTypeNamesBlackList2() {
	  return new Set(['file', 'employee', 'boolean', 'money']);
	}
	function _filterRequisiteCreateFields2(fields) {
	  return fields.filter(createFieldType => !babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldTypeNamesBlackList)[_getCreateFieldTypeNamesBlackList]().has(createFieldType.name));
	}
	function _getFieldsetViewer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('fieldSetViewer', () => {
	    var _member$cid;
	    const blocksManager = this.block.blocksManager;
	    const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	    const {
	      entityTypeId,
	      presetId,
	      entityId
	    } = member;
	    const cid = (_member$cid = member.cid) != null ? _member$cid : entityId;
	    const fieldsetViewer = new crm_requisite_fieldsetViewer.FieldsetViewer({
	      entityTypeId,
	      entityId: cid,
	      documentUid: blocksManager.getDocumentUid(),
	      events: {
	        onClose: () => {
	          this.block.assign();
	          babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].delete('fieldSetViewer');
	        }
	      },
	      fieldListEditorOptions: {
	        fieldsPanelOptions: {
	          filter: {
	            '+categories': ['COMPANY'],
	            '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'double', 'integer'],
	            '-fields': [({
	              entity_field_name: fieldName
	            }) => babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList)[_getFieldsNameBlackList]().includes(fieldName)]
	          },
	          fieldsFactory: {
	            filter: babelHelpers.classPrivateFieldLooseBase(this, _filterRequisiteCreateFields)[_filterRequisiteCreateFields].bind(this)
	          },
	          presetId,
	          controllerOptions: {
	            hideVirtual: 1,
	            hideRequisites: 0,
	            hideSmartDocument: 1,
	            presetId
	          }
	        }
	      },
	      popupOptions: {
	        overlay: true,
	        cacheable: false
	      }
	    }).setEndpoint('sign.api_v1.Integration.Crm.FieldSet.load');
	    fieldsetViewer.subscribe('onFieldSetLoadError', babelHelpers.classPrivateFieldLooseBase(this, _onFieldsetViewerFieldsetLoadError)[_onFieldsetViewerFieldsetLoadError].bind(this));
	    return fieldsetViewer;
	  });
	}
	function _onFieldsetViewerFieldsetLoadError2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _getFieldsetViewer)[_getFieldsetViewer]().hide();
	  const hasAccessDeniedError = event.getData().requestErrors.some(error => error.code === 'ACCESS_DENIED');
	  if (hasAccessDeniedError) {
	    ui_notification.UI.Notification.Center.notify({
	      content: main_core.Text.encode(main_core.Loc.getMessage('SIGN_EDITOR_ERROR_REQUISITE_BLOCK_REQUISITE_ACCESS_DENIED')),
	      autoHideDelay: 3000,
	      width: 480
	    });
	  }
	}

	let _$3 = t => t,
	  _t$3,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9;
	const ColorPicker = main_core.Reflection.getClass('BX.ColorPicker');
	class UI {
	  /**
	   * Sets width/height/top/left to element.
	   * @param {HTMLElement} element
	   * @param {{[key: string]: number}} rect
	   */
	  static setRect(element, rect) {
	    Object.keys(rect).map(key => {
	      rect[key] = parseInt(rect[key]) + 'px';
	    });
	    main_core.Dom.style(element, rect);
	  }

	  /**
	   * Returns block's layout.
	   * @param {BlockLayoutOptions} options Layout options.
	   * @return {HTMLElement}
	   */
	  static getBlockLayout(options) {
	    return main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div class="sign-document__block-wrapper">
				<div class="sign-document__block-panel--wrapper" data-role="sign-block__actions">
				</div>
				<div class="sign-document__block-content">
				</div>
				<div class="sign-document__block-actions">
					<div class="sign-document__block-actions--wrapper">
						<button class="sign-document__block-actions-btn --remove sign-block-action-remove" data-role="removeAction" onclick="${0}"></button>
						<button class="sign-document__block-actions-btn --save sign-block-action-save" data-role="saveAction" onclick="${0}"></button>
					</div>
				</div>
			</div>
		`), options.onRemove, options.onSave);
	  }

	  /**
	   * Returns member selector for block.
	   * @param {Array<MemberItem>} members All document's members.
	   * @param {number} selectedValue Selected member.
	   * @param {() => {}} onChange Handler on change value.
	   * @return {HTMLElement}
	   */
	  static getMemberSelector(members, selectedValue, onChange) {
	    const menuItems = {};
	    let selectedName = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	    members.map(member => {
	      member.name = member.name || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	      menuItems[member.part] = member.name;
	      if (member.part === selectedValue) {
	        selectedName = member.name;
	      }
	    });
	    const memberSelector = members.length > 1 ? main_core.Tag.render(_t2 || (_t2 = _$3`<span>${0}</span>`), main_core.Text.encode(selectedName)) : main_core.Tag.render(_t3 || (_t3 = _$3`<i>${0}</i>`), main_core.Text.encode(selectedName));
	    if (members.length > 1) {
	      sign_ui.UI.bindSimpleMenu({
	        bindElement: memberSelector,
	        items: menuItems,
	        actionHandler: value => {
	          memberSelector.innerHTML = menuItems[value];
	          onChange(parseInt(value));
	        }
	      });
	    }
	    return main_core.Tag.render(_t4 || (_t4 = _$3`
			<div class="sign-document-block-member-wrapper">
				${0}
			</div>
		`), memberSelector);
	  }

	  /**
	   * Returns resizing area's layout.
	   * @return {HTMLElement}
	   */
	  static getResizeArea() {
	    return main_core.Tag.render(_t5 || (_t5 = _$3`
			<div class="sign-document__resize-area">
				<div class="sign-area-resizable-controls">
					<span class="sign-document__move-control"></span>
					<div class="sign-document__resize-control --middle-top"></div>
					<div class="sign-document__resize-control --right-top"></div>
					<div class="sign-document__resize-control --middle-right"></div>
					<div class="sign-document__resize-control --right-bottom"></div>
					<div class="sign-document__resize-control --middle-bottom"></div>
					<div class="sign-document__resize-control --left-bottom"></div>
					<div class="sign-document__resize-control --middle-left"></div>
				</div>
			</div>
		`));
	  }

	  /**
	   * Returns style panel layout.
	   * @return {HTMLElement}
	   */
	  static getStylePanel(actionHandler, collectStyles) {
	    var _collectStyles$color;
	    // font family selector
	    const fonts = {
	      '"Times New Roman", Times': '<span style="font-family: \'Times New Roman\', Times">Times New Roman</span>',
	      '"Courier New"': '<span style="font-family: \'Courier New\'">Courier New</span>',
	      'Arial, Helvetica': '<span style="font-family: Arial, Helvetica">Arial / Helvetica</span>',
	      '"Arial Black", Gadget': '<span style="font-family: \'Arial Black\', Gadget">Arial Black</span>',
	      'Tahoma, Geneva': '<span style="font-family: Tahoma, Geneva">Tahoma / Geneva</span>',
	      'Verdana': '<span style="font-family: Verdana">Verdana</span>',
	      'Georgia, serif': '<span style="font-family: Georgia, serif">Georgia</span>',
	      'monospace': '<span style="font-family: monospace">monospace</span>'
	    };
	    const fontFamily = main_core.Tag.render(_t6 || (_t6 = _$3`<div class="sign-document__block-style-btn --btn-font-family">${0}</div>`), fonts[collectStyles['font-family']] || 'Font');
	    sign_ui.UI.bindSimpleMenu({
	      bindElement: fontFamily,
	      items: fonts,
	      actionHandler: value => {
	        fontFamily.innerHTML = fonts[value];
	        actionHandler('family', value);
	      }
	    });

	    // font size selector

	    let fontSizereal = parseInt(collectStyles['font-size']);
	    let fontSizeValue = 14;
	    if (fontSizereal) {
	      fontSizeValue = fontSizereal;
	    }
	    const fontSize = main_core.Tag.render(_t7 || (_t7 = _$3`<div class="sign-document__block-style-btn --btn-fontsize">${0}</div>`), fontSizeValue + 'px' || '<i></i>');
	    sign_ui.UI.bindSimpleMenu({
	      bindElement: fontSize,
	      items: ['6px', '7px', '8px', '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px', '18px', '20px', '22px', '24px', '26px', '28px', '36px', '48px', '72px'],
	      actionHandler: value => {
	        fontSize.innerHTML = parseInt(value) + 'px';
	        actionHandler('size', value);
	      },
	      currentValue: fontSizeValue
	    });

	    // color
	    const {
	      layout: fontColor
	    } = UI.getColorSelectorBtn((_collectStyles$color = collectStyles.color) != null ? _collectStyles$color : '#000', color => actionHandler('color', color));
	    return main_core.Tag.render(_t8 || (_t8 = _$3`
			<div class="sign-document__block-style--panel">
<!--				<div class="sign-document__block-style&#45;&#45;move-control"></div>-->
				${0}
				${0}
				${0}
				<div class="sign-document__block-style--separator"></div>
				<div class="sign-document__block-style-btn --btn-bold" data-action="bold"><i></i></div>
				<div class="sign-document__block-style-btn --btn-italic" data-action="italic"><i></i></div>
				<div class="sign-document__block-style-btn --btn-underline" data-action="underline"><i></i></div>
				<div class="sign-document__block-style-btn --btn-strike" data-action="through"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-left" data-action="left"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-center" data-action="center"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-right" data-action="right"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-justify" data-action="justify"><i></i></div>
			</div>
		`), fontFamily, fontSize, fontColor);
	  }
	  static getColorSelectorBtn(defaultColorPickerColor, onColorSelect, colorPickerOptions = {}) {
	    const layout = main_core.Tag.render(_t9 || (_t9 = _$3`<div class="sign-document__block-style-btn --btn-color">
				<span class="sign-document__block-style-btn--color-block"></span> 
				<span>${0}</span>
			</div>`), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_STYLE_COLOR'));
	    UI.updateColorSelectorBtnColor(layout, defaultColorPickerColor);
	    const updatedColorPickerOptions = {
	      ...colorPickerOptions,
	      bindElement: layout,
	      onColorSelected: color => {
	        onColorSelect(color);
	        UI.updateColorSelectorBtnColor(layout, color);
	      }
	    };
	    const picker = new ColorPicker(updatedColorPickerOptions);
	    main_core.Event.bind(layout, 'click', () => {
	      picker.open();
	    });
	    return {
	      layout,
	      colorPicker: picker
	    };
	  }
	  static updateColorSelectorBtnColor(layout, color) {
	    const circleColor = layout.querySelector('.sign-document__block-style-btn--color-block');
	    if (main_core.Type.isNil(circleColor)) {
	      return;
	    }
	    main_core.Dom.style(circleColor, 'background-color', color);
	  }
	}

	let _$4 = t => t,
	  _t$4,
	  _t2$1,
	  _t3$1;
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _defaultSignatureColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultSignatureColor");
	var _availableSignatureColors = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("availableSignatureColors");
	var _selectedSignatureColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedSignatureColor");
	var _getSignatureColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSignatureColor");
	var _closeColorPickerPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeColorPickerPopup");
	var _getColorSelectorBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getColorSelectorBtn");
	class MySign extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getColorSelectorBtn, {
	      value: _getColorSelectorBtn2
	    });
	    Object.defineProperty(this, _closeColorPickerPopup, {
	      value: _closeColorPickerPopup2
	    });
	    Object.defineProperty(this, _getSignatureColor, {
	      value: _getSignatureColor2
	    });
	    Object.defineProperty(this, _cache$1, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _defaultSignatureColor, {
	      writable: true,
	      value: '#0047ab'
	    });
	    Object.defineProperty(this, _availableSignatureColors, {
	      writable: true,
	      value: ['#000', babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor)[_defaultSignatureColor], '#8b00ff']
	    });
	    Object.defineProperty(this, _selectedSignatureColor, {
	      writable: true,
	      value: null
	    });
	  }
	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return true;
	  }

	  /**
	   * Returns true if style panel mast be showed.
	   * @return {boolean}
	   */
	  isStyleAllowed() {
	    return false;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 200,
	      height: 70
	    };
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    const {
	      width,
	      height
	    } = this.block.getPosition();
	    let src = null;
	    if (this.dataSrc) {
	      src = this.dataSrc;
	    } else if (this.data.base64) {
	      src = 'data:image;base64,' + this.data.base64;
	    }
	    if (src) {
	      return main_core.Tag.render(_t$4 || (_t$4 = _$4`
				<img src="${0}" alt="">
			`), src);
	    } else {
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$4`
				<div class="sign-document__block-content_member-nodata">
					${0}
				</div>
			`), main_core.Text.encode(this.data.text || this.getPlaceholderLabel()));
	    }
	  }

	  /**
	   * Returns placeholder's label.
	   * @return {string}
	   */
	  getPlaceholderLabel() {
	    return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_MY_SIGN');
	  }
	  onClickOut() {
	    super.onClickOut();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup)[_closeColorPickerPopup]();
	  }
	  onRemove() {
	    super.onRemove();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup)[_closeColorPickerPopup]();
	  }
	  onSave() {
	    super.onSave();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup)[_closeColorPickerPopup]();
	  }
	  getStyles() {
	    return {
	      backgroundPosition: 'center !important',
	      color: babelHelpers.classPrivateFieldLooseBase(this, _getSignatureColor)[_getSignatureColor]()
	    };
	  }
	  changeStyleColor(color, emitEvent = true) {
	    super.changeStyleColor(color);
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor)[_selectedSignatureColor] = color;
	    const {
	      layout,
	      colorPicker
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn)[_getColorSelectorBtn]();
	    UI.updateColorSelectorBtnColor(layout, color);
	    colorPicker.setSelectedColor(color);
	    if (emitEvent) {
	      this.emit(this.events.onColorStyleChange);
	    }
	  }

	  /**
	   * @return {HTMLElement | null}
	   */
	  getBlockCaption() {
	    const {
	      layout: colorSelectorBtnLayout
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn)[_getColorSelectorBtn]();
	    return main_core.Tag.render(_t3$1 || (_t3$1 = _$4`
			<div style="display: flex; flex-direction: row;">
			${0}
			<div class="sign-document__block-style--separator"></div>
			<div class="sign-document-block-member-wrapper">
				<i>${0}</i>
			</div>
			</div>
		`), colorSelectorBtnLayout, main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SIGN_ACTION_BUTTON'));
	  }
	}
	function _getSignatureColor2() {
	  var _babelHelpers$classPr;
	  return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor)[_selectedSignatureColor]) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor)[_defaultSignatureColor];
	}
	function _closeColorPickerPopup2() {
	  const {
	    colorPicker
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn)[_getColorSelectorBtn]();
	  colorPicker.close();
	}
	function _getColorSelectorBtn2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('colorSelectorBtn', () => {
	    var _babelHelpers$classPr2, _babelHelpers$classPr3;
	    return UI.getColorSelectorBtn((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor)[_selectedSignatureColor]) != null ? _babelHelpers$classPr2 : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor)[_defaultSignatureColor], color => this.changeStyleColor(color), {
	      colors: [babelHelpers.classPrivateFieldLooseBase(this, _availableSignatureColors)[_availableSignatureColors]],
	      allowCustomColor: false,
	      selectedColor: (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor)[_selectedSignatureColor]) != null ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor)[_defaultSignatureColor],
	      colorPreview: false
	    });
	  });
	}

	let _$5 = t => t,
	  _t$5,
	  _t2$2,
	  _t3$2;
	var _uploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploader");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _onActionClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _saveStamp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveStamp");
	class MyStamp extends Dummy {
	  constructor(_block) {
	    super(_block);
	    Object.defineProperty(this, _saveStamp, {
	      value: _saveStamp2
	    });
	    Object.defineProperty(this, _onActionClick$1, {
	      value: _onActionClick2$1
	    });
	    Object.defineProperty(this, _uploader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader] = new ui_stamp_uploader.Uploader({
	      controller: {
	        upload: 'sign.upload.stampUploadController'
	      },
	      contact: {
	        id: 0,
	        label: 'Admin'
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].subscribe('onSaveAsync', async event => {
	      var _eventData$file;
	      MyStamp.currentBlock.await(true);
	      const eventData = event.getData();
	      if (!(eventData != null && (_eventData$file = eventData.file) != null && _eventData$file.serverId)) {
	        return;
	      }
	      this.dataSrc = eventData.file.serverPreviewUrl;
	      babelHelpers.classPrivateFieldLooseBase(this, _saveStamp)[_saveStamp](MyStamp.currentBlock, eventData.file.serverId);
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }

	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return true;
	  }

	  /**
	   * Returns true if style panel mast be showed.
	   * @return {boolean}
	   */
	  isStyleAllowed() {
	    return false;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 151,
	      height: 151
	    };
	  }

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement}
	   */
	  getActionButton() {
	    var _this$data$__view;
	    const title = (_this$data$__view = this.data.__view) != null && _this$data$__view.base64 ? main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_CHANGE_STAMP') : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_LOAD_STAMP');
	    return main_core.Tag.render(_t$5 || (_t$5 = _$5`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action">
					${0}
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$1)[_onActionClick$1].bind(this), title);
	  }

	  /**
	   * Calls when action button was clicked.
	   * @param {PointerEvent} event
	   */

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    var _this$data$__view2;
	    let src = null;
	    if ((_this$data$__view2 = this.data.__view) != null && _this$data$__view2.base64) {
	      src = 'data:image;base64,' + this.data.__view.base64;
	    }
	    if (src) {
	      return main_core.Tag.render(_t2$2 || (_t2$2 = _$5`
				<div class="sign-document__block-content_stamp" style="background-image: url(${0})"></div>
			`), src);
	    } else {
	      return main_core.Tag.render(_t3$2 || (_t3$2 = _$5`
				<div class="sign-document__block-content_member-nodata --stamp"></div>
			`));
	    }
	  }
	}
	function _onActionClick2$1(event) {
	  MyStamp.currentBlock = this.block;
	  babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader].show();
	}
	async function _saveStamp2(block, fileId) {
	  const memberPart = block.getMemberPart();
	  const member = block.blocksManager.getMemberByPart(memberPart);
	  await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].saveStamp(member.uid, fileId);
	  block.assign();
	  const layout = MyStamp.currentBlock.getLayout();
	  const button = layout.querySelector('button[data-role="action"]');
	  button.textContent = main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_CHANGE_STAMP');
	}

	let _$6 = t => t,
	  _t$6,
	  _t2$3;
	var _onActionClick$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	class Number$1 extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onActionClick$2, {
	      value: _onActionClick2$2
	    });
	  }
	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return true;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 100,
	      height: 28
	    };
	  }

	  /**
	   * Calls when action button was clicked.
	   * @param {PointerEvent} event
	   */

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    return main_core.Tag.render(_t$6 || (_t$6 = _$6`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action">
					${0}
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$2)[_onActionClick$2].bind(this), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_NUMBER_ACTION_EDIT'));
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    return main_core.Tag.render(_t2$3 || (_t2$3 = _$6`
			<div class="sign-document__block-number">
				${0}
			</div>
		`), main_core.Text.encode(this.data.text || '').toString());
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...Number$1.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onActionClick2$2(event) {
	  event.stopPropagation();
	  if (!Number$1.sliderOnMessageBind) {
	    Number$1.sliderOnMessageBind = true;
	    BX.addCustomEvent(window, 'SidePanel.Slider:onMessage', async event => {
	      var _event$slider;
	      const isEditor = ((_event$slider = event.slider) == null ? void 0 : _event$slider.url) === 'editor';
	      if (isEditor && event.getEventId() === 'numerator-saved-event') {
	        var _event$sender$options, _event$sender, _event$sender$options2, _event$sender$options3;
	        const numeratorData = event.getData();
	        const currentBlock = (_event$sender$options = event == null ? void 0 : (_event$sender = event.sender) == null ? void 0 : (_event$sender$options2 = _event$sender.options) == null ? void 0 : (_event$sender$options3 = _event$sender$options2.data) == null ? void 0 : _event$sender$options3.block) != null ? _event$sender$options : this.block;
	        if (numeratorData.type === 'CRM_SMART_DOCUMENT') {
	          currentBlock.await(true);
	          const api = new sign_v2_api.Api();
	          const uid = currentBlock.blocksManager.getDocumentUid();
	          await api.refreshEntityNumber(uid);
	          currentBlock.assign();
	        }
	      }
	    });
	  }
	  const {
	    crmNumeratorUrl
	  } = this.data.__view;
	  BX.SidePanel.Instance.open(crmNumeratorUrl, {
	    width: 480,
	    cacheable: false,
	    data: {
	      block: this.block
	    }
	  });
	}
	Number$1.sliderOnMessageBind = false;

	let _$7 = t => t,
	  _t$7,
	  _t2$4,
	  _t3$3;
	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _field = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _onActionClick$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _setActionButtonLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getCrmFieldSelectorPanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldSelectorPanel");
	var _onCrmFieldSelectorSliderCloseComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCrmFieldSelectorSliderCloseComplete");
	var _emitOnFieldSelectEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _getCrmFieldsNameBlackList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldsNameBlackList");
	var _getCreateFieldTypeNamesBlackList$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateFieldTypeNamesBlackList");
	var _filterRequisiteCreateFields$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterRequisiteCreateFields");
	class Reference extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _filterRequisiteCreateFields$1, {
	      value: _filterRequisiteCreateFields2$1
	    });
	    Object.defineProperty(this, _getCreateFieldTypeNamesBlackList$1, {
	      value: _getCreateFieldTypeNamesBlackList2$1
	    });
	    Object.defineProperty(this, _getCrmFieldsNameBlackList, {
	      value: _getCrmFieldsNameBlackList2
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent, {
	      value: _emitOnFieldSelectEvent2
	    });
	    Object.defineProperty(this, _onCrmFieldSelectorSliderCloseComplete, {
	      value: _onCrmFieldSelectorSliderCloseComplete2
	    });
	    Object.defineProperty(this, _getCrmFieldSelectorPanel, {
	      value: _getCrmFieldSelectorPanel2
	    });
	    Object.defineProperty(this, _setActionButtonLabel, {
	      value: _setActionButtonLabel2
	    });
	    Object.defineProperty(this, _onActionClick$3, {
	      value: _onActionClick2$3
	    });
	    Object.defineProperty(this, _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _field, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field)[_field] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$3)[_onActionClick$3]();
	  }

	  /**
	   * Calls when action button was clicked.
	   */

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    if (main_core.Type.isUndefined(crm_form_fields_selector.Selector)) {
	      return null;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton)[_actionButton] = main_core.Tag.render(_t$7 || (_t$7 = _$7`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$3)[_onActionClick$3].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel)[_setActionButtonLabel]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton)[_actionButton];
	  }

	  /**
	   * Sets label to action button.
	   */

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel)[_setActionButtonLabel]();
	    const {
	      width,
	      height
	    } = this.block.getPosition();
	    if (this.data.src) {
	      return main_core.Tag.render(_t2$4 || (_t2$4 = _$7`
				<div style="width: ${0}px; height: ${0}px; background: url(${0}) no-repeat top; background-size: cover;">
				</div>
			`), width - 14, height - 14, this.data.src);
	    } else {
	      const className = !this.data.text ? 'sign-document__block-content_member-nodata' : '';
	      return main_core.Tag.render(_t3$3 || (_t3$3 = _$7`
				<div class="${0}">
					${0}
				</div>
			`), className, main_core.Text.encode(this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA')));
	    }
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...Reference.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onActionClick2$3() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldSelectorPanel)[_getCrmFieldSelectorPanel]().show().then(selectedNames => {
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field)[_field])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field)[_field]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent)[_emitOnFieldSelectEvent](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field)[_field] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field)[_field]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _setActionButtonLabel2() {
	  var _babelHelpers$classPr, _fieldsList$CONTACT$F, _fieldsList$CONTACT, _fieldsList$SMART_DOC, _fieldsList$SMART_DOC2;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton)[_actionButton]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton)[_actionButton].querySelector('button');
	  const defaultCaption = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _field)[_field]) {
	    actionButton.textContent = defaultCaption;
	    return;
	  }
	  const fieldSelector = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('fieldSelector')) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldSelectorPanel)[_getCrmFieldSelectorPanel]();
	  const fieldsList = fieldSelector.getFieldsList();
	  const fields = [...((_fieldsList$CONTACT$F = fieldsList == null ? void 0 : (_fieldsList$CONTACT = fieldsList.CONTACT) == null ? void 0 : _fieldsList$CONTACT.FIELDS) != null ? _fieldsList$CONTACT$F : []), ...((_fieldsList$SMART_DOC = fieldsList == null ? void 0 : (_fieldsList$SMART_DOC2 = fieldsList.SMART_DOCUMENT) == null ? void 0 : _fieldsList$SMART_DOC2.FIELDS) != null ? _fieldsList$SMART_DOC : [])];
	  const field = fields.find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _field)[_field]);
	  const caption = field ? field.caption : defaultCaption;
	  actionButton.textContent = caption;
	}
	function _getCrmFieldSelectorPanel2() {
	  const blocksManager = this.block.blocksManager;
	  const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	  const {
	    presetId
	  } = member;
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('fieldSelector', () => {
	    const selector = new crm_form_fields_selector.Selector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 0,
	        hideSmartDocument: 1,
	        presetId
	      },
	      presetId,
	      filter: {
	        '+categories': ['CONTACT', 'SMART_DOCUMENT'],
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'double', 'integer'],
	        '-fields': [({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldsNameBlackList)[_getCrmFieldsNameBlackList]().has(fieldName)]
	      },
	      fieldsFactory: {
	        filter: babelHelpers.classPrivateFieldLooseBase(this, _filterRequisiteCreateFields$1)[_filterRequisiteCreateFields$1].bind(this)
	      }
	    });
	    selector.subscribe('onSliderCloseComplete', babelHelpers.classPrivateFieldLooseBase(this, _onCrmFieldSelectorSliderCloseComplete)[_onCrmFieldSelectorSliderCloseComplete].bind(this));
	    return selector;
	  });
	}
	function _onCrmFieldSelectorSliderCloseComplete2() {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field)[_field])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent)[_emitOnFieldSelectEvent]([]);
	  });
	}
	function _emitOnFieldSelectEvent2(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _getCrmFieldsNameBlackList2() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _getCreateFieldTypeNamesBlackList2$1() {
	  return new Set(['file', 'employee', 'boolean', 'money']);
	}
	function _filterRequisiteCreateFields2$1(fields) {
	  return fields.filter(createFieldType => !babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldTypeNamesBlackList$1)[_getCreateFieldTypeNamesBlackList$1]().has(createFieldType.name));
	}

	let _$8 = t => t,
	  _t$8,
	  _t2$5,
	  _t3$4;
	var _field$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _cache$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _onActionClick$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _setActionButtonLabel$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getCrmFieldSelectorPanel$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldSelectorPanel");
	var _onCrmFieldSelectorSliderCloseComplete$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCrmFieldSelectorSliderCloseComplete");
	var _getFieldNegativeFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldNegativeFilter");
	var _getFieldsNameBlackList$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _emitOnFieldSelectEvent$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _getCrmFieldsNameBlackList$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldsNameBlackList");
	var _getCreateFieldTypeNamesBlackList$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateFieldTypeNamesBlackList");
	var _filterRequisiteCreateFields$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterRequisiteCreateFields");
	class MyReference extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _filterRequisiteCreateFields$2, {
	      value: _filterRequisiteCreateFields2$2
	    });
	    Object.defineProperty(this, _getCreateFieldTypeNamesBlackList$2, {
	      value: _getCreateFieldTypeNamesBlackList2$2
	    });
	    Object.defineProperty(this, _getCrmFieldsNameBlackList$1, {
	      value: _getCrmFieldsNameBlackList2$1
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent$1, {
	      value: _emitOnFieldSelectEvent2$1
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList$1, {
	      value: _getFieldsNameBlackList2$1
	    });
	    Object.defineProperty(this, _getFieldNegativeFilter, {
	      value: _getFieldNegativeFilter2
	    });
	    Object.defineProperty(this, _onCrmFieldSelectorSliderCloseComplete$1, {
	      value: _onCrmFieldSelectorSliderCloseComplete2$1
	    });
	    Object.defineProperty(this, _getCrmFieldSelectorPanel$1, {
	      value: _getCrmFieldSelectorPanel2$1
	    });
	    Object.defineProperty(this, _setActionButtonLabel$1, {
	      value: _setActionButtonLabel2$1
	    });
	    Object.defineProperty(this, _onActionClick$4, {
	      value: _onActionClick2$4
	    });
	    Object.defineProperty(this, _field$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cache$3, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$4)[_onActionClick$4]();
	  }

	  /**
	   * Calls when action button was clicked.
	   */

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    if (main_core.Type.isUndefined(crm_form_fields_selector.Selector)) {
	      return null;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton$1)[_actionButton$1] = main_core.Tag.render(_t$8 || (_t$8 = _$8`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$4)[_onActionClick$4].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$1)[_setActionButtonLabel$1]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton$1)[_actionButton$1];
	  }

	  /**
	   * Sets label to action button.
	   */

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$1)[_setActionButtonLabel$1]();
	    const {
	      width,
	      height
	    } = this.block.getPosition();
	    if (this.data.src) {
	      return main_core.Tag.render(_t2$5 || (_t2$5 = _$8`
				<div style="width: ${0}px; height: ${0}px; background: url(${0}) no-repeat top; background-size: cover;">
				</div>
			`), width - 14, height - 14, this.data.src);
	    }
	    const className = !this.data.text ? 'sign-document__block-content_member-nodata' : '';
	    return main_core.Tag.render(_t3$4 || (_t3$4 = _$8`
				<div class="${0}">
					${0}
				</div>
			`), className, main_core.Text.encode(this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_MY_BLOCKS')));
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...MyReference.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onActionClick2$4() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldSelectorPanel$1)[_getCrmFieldSelectorPanel$1]().show().then(selectedNames => {
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$1)[_emitOnFieldSelectEvent$1](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _setActionButtonLabel2$1() {
	  var _fieldsList$COMPANY$F, _fieldsList$COMPANY, _fieldsList$SMART_DOC, _fieldsList$SMART_DOC2;
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton$1)[_actionButton$1]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton$1)[_actionButton$1].querySelector('button');
	  const defaultCaption = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REFERENCE_ACTION_BUTTON');
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1]) {
	    actionButton.textContent = defaultCaption;
	    return;
	  }
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldSelectorPanel$1)[_getCrmFieldSelectorPanel$1]();
	  const fieldsList = fieldSelector.getFieldsList();
	  const fields = [...((_fieldsList$COMPANY$F = fieldsList == null ? void 0 : (_fieldsList$COMPANY = fieldsList.COMPANY) == null ? void 0 : _fieldsList$COMPANY.FIELDS) != null ? _fieldsList$COMPANY$F : []), ...((_fieldsList$SMART_DOC = fieldsList == null ? void 0 : (_fieldsList$SMART_DOC2 = fieldsList.SMART_DOCUMENT) == null ? void 0 : _fieldsList$SMART_DOC2.FIELDS) != null ? _fieldsList$SMART_DOC : [])];
	  const field = fields.find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1]);
	  const caption = field ? field.caption : defaultCaption;
	  actionButton.textContent = caption;
	}
	function _getCrmFieldSelectorPanel2$1() {
	  const blocksManager = this.block.blocksManager;
	  const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	  const {
	    presetId
	  } = member;
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$3)[_cache$3].remember('fieldSelector', () => {
	    const selector = new crm_form_fields_selector.Selector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 0,
	        hideSmartDocument: 1,
	        presetId
	      },
	      presetId,
	      filter: {
	        '+categories': ['COMPANY', 'SMART_DOCUMENT'],
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'double', 'integer'],
	        '-fields': [babelHelpers.classPrivateFieldLooseBase(this, _getFieldNegativeFilter)[_getFieldNegativeFilter](), ({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldsNameBlackList$1)[_getCrmFieldsNameBlackList$1]().has(fieldName)]
	      },
	      fieldsFactory: {
	        filter: babelHelpers.classPrivateFieldLooseBase(this, _filterRequisiteCreateFields$2)[_filterRequisiteCreateFields$2].bind(this)
	      }
	    });
	    selector.subscribe('onSliderCloseComplete', babelHelpers.classPrivateFieldLooseBase(this, _onCrmFieldSelectorSliderCloseComplete$1)[_onCrmFieldSelectorSliderCloseComplete$1].bind(this));
	    return selector;
	  });
	}
	function _onCrmFieldSelectorSliderCloseComplete2$1() {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$1)[_field$1])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$1)[_emitOnFieldSelectEvent$1]([]);
	  });
	}
	function _getFieldNegativeFilter2() {
	  const blackList = babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList$1)[_getFieldsNameBlackList$1]();
	  return field => {
	    return blackList.includes(field.name);
	  };
	}
	function _getFieldsNameBlackList2$1() {
	  return ['COMPANY_LINK', 'COMPANY_REG_ADDRESS', 'COMPANY_ORIGIN_VERSION'];
	}
	function _emitOnFieldSelectEvent2$1(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _getCrmFieldsNameBlackList2$1() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _getCreateFieldTypeNamesBlackList2$2() {
	  return new Set(['file', 'employee', 'boolean', 'money']);
	}
	function _filterRequisiteCreateFields2$2(fields) {
	  return fields.filter(createFieldType => !babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldTypeNamesBlackList$2)[_getCreateFieldTypeNamesBlackList$2]().has(createFieldType.name));
	}

	let _$9 = t => t,
	  _t$9,
	  _t2$6;
	var _onActionClick$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _getCrmFieldsNameBlackList$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldsNameBlackList");
	var _getCreateFieldTypeNamesBlackList$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateFieldTypeNamesBlackList");
	var _filterRequisiteCreateFields$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("filterRequisiteCreateFields");
	class Requisites extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _filterRequisiteCreateFields$3, {
	      value: _filterRequisiteCreateFields2$3
	    });
	    Object.defineProperty(this, _getCreateFieldTypeNamesBlackList$3, {
	      value: _getCreateFieldTypeNamesBlackList2$3
	    });
	    Object.defineProperty(this, _getCrmFieldsNameBlackList$2, {
	      value: _getCrmFieldsNameBlackList2$2
	    });
	    Object.defineProperty(this, _onActionClick$5, {
	      value: _onActionClick2$5
	    });
	  }
	  /**
	   * Returns true if block is in singleton mode.
	   * @return {boolean}
	   */
	  isSingleton() {
	    return true;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 250,
	      height: 220
	    };
	  }

	  /**
	   * Calls when action button was clicked.
	   * @param {PointerEvent} event
	   */

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement}
	   */
	  getActionButton() {
	    return main_core.Tag.render(_t$9 || (_t$9 = _$9`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
					${0}
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$5)[_onActionClick$5].bind(this), this.block.getCode(), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_ACTION_BUTTON'));
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    const text = this.data.text || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_REQUISITES');
	    const tagBody = this.data.text ? '' : ' class="sign-document__block-content_member-nodata"';
	    return main_core.Tag.render(_t2$6 || (_t2$6 = _$9`
			<div${0}>
				${0}
			</div>
		`), tagBody, main_core.Text.encode(text).toString().replaceAll('[br]', '<br>'));
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...Requisites.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onActionClick2$5(event) {
	  const blocksManager = this.block.blocksManager;
	  const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	  event.stopPropagation();
	  if (!member) {
	    return;
	  }
	  const {
	    cid,
	    entityTypeId,
	    presetId
	  } = member;
	  new crm_requisite_fieldsetViewer.FieldsetViewer({
	    entityTypeId,
	    entityId: cid,
	    documentUid: blocksManager.getDocumentUid(),
	    events: {
	      onClose: () => {
	        this.block.assign();
	      }
	    },
	    fieldListEditorOptions: {
	      fieldsPanelOptions: {
	        filter: {
	          '+categories': ['CONTACT'],
	          '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'address', 'url', 'double', 'integer'],
	          '-fields': [({
	            entity_field_name: fieldName
	          }) => babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldsNameBlackList$2)[_getCrmFieldsNameBlackList$2]().has(fieldName)]
	        },
	        fieldsFactory: {
	          filter: babelHelpers.classPrivateFieldLooseBase(this, _filterRequisiteCreateFields$3)[_filterRequisiteCreateFields$3].bind(this)
	        },
	        presetId,
	        controllerOptions: {
	          hideVirtual: 1,
	          hideRequisites: 0,
	          hideSmartDocument: 1,
	          presetId
	        }
	      }
	    },
	    popupOptions: {
	      overlay: true,
	      cacheable: false
	    }
	  }).setEndpoint('sign.api_v1.Integration.Crm.FieldSet.load').show();
	}
	function _getCrmFieldsNameBlackList2$2() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _getCreateFieldTypeNamesBlackList2$3() {
	  return new Set(['file', 'employee', 'boolean', 'money']);
	}
	function _filterRequisiteCreateFields2$3(fields) {
	  return fields.filter(createFieldType => !babelHelpers.classPrivateFieldLooseBase(this, _getCreateFieldTypeNamesBlackList$3)[_getCreateFieldTypeNamesBlackList$3]().has(createFieldType.name));
	}

	let _$a = t => t,
	  _t$a,
	  _t2$7;
	class Stamp extends Dummy {
	  /**
	   * Returns true if style panel mast be showed.
	   * @return {boolean}
	   */
	  isStyleAllowed() {
	    return false;
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 151,
	      height: 151
	    };
	  }

	  /**
	   * Returns placeholder's label.
	   * @return {string}
	   */
	  getPlaceholderLabel() {
	    return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_STAMP');
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    const {
	      width,
	      height
	    } = this.block.getPosition();
	    if (this.data.base64) {
	      return main_core.Tag.render(_t$a || (_t$a = _$a`
				<div class="sign-document__block-content_stamp" style="background-image: url(${0})"></div>
			`), src);
	    } else {
	      return main_core.Tag.render(_t2$7 || (_t2$7 = _$a`
				<div class="sign-document__block-content_member-nodata">
					
				</div>
			`));
	    }
	  }
	}

	let _$b = t => t,
	  _t$b,
	  _t2$8;
	var _cache$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _defaultSignatureColor$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("defaultSignatureColor");
	var _availableSignatureColors$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("availableSignatureColors");
	var _selectedSignatureColor$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedSignatureColor");
	var _getColorSelectorBtn$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getColorSelectorBtn");
	var _getSignatureColor$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSignatureColor");
	var _closeColorPickerPopup$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeColorPickerPopup");
	class Sign extends Stamp {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _closeColorPickerPopup$1, {
	      value: _closeColorPickerPopup2$1
	    });
	    Object.defineProperty(this, _getSignatureColor$1, {
	      value: _getSignatureColor2$1
	    });
	    Object.defineProperty(this, _getColorSelectorBtn$1, {
	      value: _getColorSelectorBtn2$1
	    });
	    Object.defineProperty(this, _cache$4, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _defaultSignatureColor$1, {
	      writable: true,
	      value: '#0047ab'
	    });
	    Object.defineProperty(this, _availableSignatureColors$1, {
	      writable: true,
	      value: ['#000', babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor$1)[_defaultSignatureColor$1], '#8b00ff']
	    });
	    Object.defineProperty(this, _selectedSignatureColor$1, {
	      writable: true,
	      value: null
	    });
	  }
	  /**
	   * Returns placeholder's label.
	   * @return {string}
	   */
	  getPlaceholderLabel() {
	    return main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NO_DATA_SIGN');
	  }

	  /**
	   * Returns initial dimension of block.
	   * @return {width: number, height: number}
	   */
	  getInitDimension() {
	    return {
	      width: 200,
	      height: 70
	    };
	  }
	  getBlockCaption() {
	    const {
	      layout: colorSelectorBtnLayout
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn$1)[_getColorSelectorBtn$1]();
	    return main_core.Tag.render(_t$b || (_t$b = _$b`
			<div style="display: flex; flex-direction: row;">
			${0}
			<div class="sign-document__block-style--separator"></div>
			</div>
		`), colorSelectorBtnLayout);
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    return main_core.Tag.render(_t2$8 || (_t2$8 = _$b`
			<div class="sign-document__block-content_member-nodata">
				${0}
			</div>
		`), main_core.Text.encode(this.getPlaceholderLabel()));
	  }
	  getStyles() {
	    return {
	      backgroundPosition: 'center !important',
	      color: babelHelpers.classPrivateFieldLooseBase(this, _getSignatureColor$1)[_getSignatureColor$1]()
	    };
	  }
	  updateColor(color) {
	    super.updateColor(color);
	    const {
	      layout,
	      colorPicker
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn$1)[_getColorSelectorBtn$1]();
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor$1)[_selectedSignatureColor$1] = color;
	    UI.updateColorSelectorBtnColor(layout, color);
	    colorPicker.setSelectedColor(color);
	  }
	  changeStyleColor(color, emitEvent = true) {
	    super.changeStyleColor(color);
	    babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor$1)[_selectedSignatureColor$1] = color;
	    const {
	      layout,
	      colorPicker
	    } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn$1)[_getColorSelectorBtn$1]();
	    UI.updateColorSelectorBtnColor(layout, color);
	    colorPicker.setSelectedColor(color);
	    if (emitEvent) {
	      this.emit(this.events.onColorStyleChange);
	    }
	  }
	  onSave() {
	    super.onSave();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup$1)[_closeColorPickerPopup$1]();
	  }
	  onRemove() {
	    super.onRemove();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup$1)[_closeColorPickerPopup$1]();
	  }
	  onClickOut() {
	    super.onClickOut();
	    babelHelpers.classPrivateFieldLooseBase(this, _closeColorPickerPopup$1)[_closeColorPickerPopup$1]();
	  }
	}
	function _getColorSelectorBtn2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$4)[_cache$4].remember('colorSelectorBtn', () => {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    return UI.getColorSelectorBtn((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor$1)[_selectedSignatureColor$1]) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor$1)[_defaultSignatureColor$1], color => this.changeStyleColor(color), {
	      colors: [babelHelpers.classPrivateFieldLooseBase(this, _availableSignatureColors$1)[_availableSignatureColors$1]],
	      allowCustomColor: false,
	      selectedColor: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor$1)[_selectedSignatureColor$1]) != null ? _babelHelpers$classPr2 : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor$1)[_defaultSignatureColor$1],
	      colorPreview: false
	    });
	  });
	}
	function _getSignatureColor2$1() {
	  var _babelHelpers$classPr3;
	  return (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _selectedSignatureColor$1)[_selectedSignatureColor$1]) != null ? _babelHelpers$classPr3 : babelHelpers.classPrivateFieldLooseBase(this, _defaultSignatureColor$1)[_defaultSignatureColor$1];
	}
	function _closeColorPickerPopup2$1() {
	  const {
	    colorPicker
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getColorSelectorBtn$1)[_getColorSelectorBtn$1]();
	  colorPicker.close();
	}

	let _$c = t => t,
	  _t$c;
	var _textContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("textContainer");
	var _onKeyUp = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onKeyUp");
	var _onPaste = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPaste");
	class Text extends Dummy {
	  constructor(block) {
	    super(block);
	    Object.defineProperty(this, _onPaste, {
	      value: _onPaste2
	    });
	    Object.defineProperty(this, _onKeyUp, {
	      value: _onKeyUp2
	    });
	    Object.defineProperty(this, _textContainer, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.Blocks.Text');
	  }

	  /**
	   * Calls when action button was clicked.
	   */
	  onActionClick() {
	    babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].contentEditable = true;
	    babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].focus();
	  }

	  /**
	   * Calls when typing in text container.
	   */

	  getText() {
	    return this.data.text;
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer];
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement}
	   */
	  getViewContent() {
	    const content = this.data.text === main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER') ? '' : this.data.text;
	    babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer] = main_core.Tag.render(_t$c || (_t$c = _$c`
			<div class="sign-document-block-text" placeholder="${0}">
				${0}
			</div>
		`), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER'), main_core.Text.encode(content).replaceAll('[br]', '<br>'));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer], 'keyup', babelHelpers.classPrivateFieldLooseBase(this, _onKeyUp)[_onKeyUp].bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer], 'paste', event => setTimeout(babelHelpers.classPrivateFieldLooseBase(this, _onPaste)[_onPaste].bind(this, event)));
	    return babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer];
	  }
	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...Text.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _onKeyUp2(event) {
	  this.setText(babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].innerText.replaceAll("\n", '[br]'));
	  this.onChange();
	}
	function _onPaste2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].innerHTML = babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].innerText.replaceAll('\n', '<br>');
	  const range = document.createRange();
	  range.selectNodeContents(babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer]);
	  range.collapse(false);
	  const selection = document.getSelection();
	  selection.removeAllRanges();
	  selection.addRange(range);
	  this.setText(babelHelpers.classPrivateFieldLooseBase(this, _textContainer)[_textContainer].innerHTML.replaceAll('<br>', '[br]'));
	  this.onChange();
	}

	let _$d = t => t,
	  _t$d,
	  _t2$9,
	  _t3$5;
	var _cache$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _field$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _loadFieldsPromise = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldsPromise");
	var _setContentText = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContentText");
	var _loadFieldAndCategoryCaption = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldAndCategoryCaption");
	var _setActionButtonLabel$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getFieldSelectorPanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldSelectorPanel");
	var _getFieldsNameBlackList$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _onActionClick$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _emitOnFieldSelectEvent$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _onFieldSelectorCloseComplete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldSelectorCloseComplete");
	var _updateFieldsList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFieldsList");
	var _getSelectorFieldTypes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorFieldTypes");
	var _getSelectorFieldsFactory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSelectorFieldsFactory");
	class B2eReference extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getSelectorFieldsFactory, {
	      value: _getSelectorFieldsFactory2
	    });
	    Object.defineProperty(this, _getSelectorFieldTypes, {
	      value: _getSelectorFieldTypes2
	    });
	    Object.defineProperty(this, _onFieldSelectorCloseComplete, {
	      value: _onFieldSelectorCloseComplete2
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent$2, {
	      value: _emitOnFieldSelectEvent2$2
	    });
	    Object.defineProperty(this, _onActionClick$6, {
	      value: _onActionClick2$6
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList$2, {
	      value: _getFieldsNameBlackList2$2
	    });
	    Object.defineProperty(this, _getFieldSelectorPanel, {
	      value: _getFieldSelectorPanel2
	    });
	    Object.defineProperty(this, _setActionButtonLabel$2, {
	      value: _setActionButtonLabel2$2
	    });
	    Object.defineProperty(this, _loadFieldAndCategoryCaption, {
	      value: _loadFieldAndCategoryCaption2
	    });
	    Object.defineProperty(this, _setContentText, {
	      value: _setContentText2
	    });
	    Object.defineProperty(this, _cache$5, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _field$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    var _this$data;
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$2)[_setActionButtonLabel$2]();
	    if (main_core.Type.isStringFilled((_this$data = this.data) == null ? void 0 : _this$data.text)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = main_core.Tag.render(_t$d || (_t$d = _$d`
				<div class="sign-document__block-b2e-reference">
					${0}
				</div>
			`), main_core.Text.encode(this.data.text || '').toString());
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = main_core.Tag.render(_t2$9 || (_t2$9 = _$d`
				<div class="sign-document__block-content_member-nodata">
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'));
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadFieldAndCategoryCaption)[_loadFieldAndCategoryCaption]().then(({
	        categoryCaption,
	        fieldCaption
	      }) => {
	        var _this$data2;
	        babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$2)[_setActionButtonLabel$2](fieldCaption);
	        if (!main_core.Type.isStringFilled((_this$data2 = this.data) == null ? void 0 : _this$data2.text)) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setContentText)[_setContentText](main_core.Type.isStringFilled(categoryCaption) && main_core.Type.isStringFilled(fieldCaption) ? main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_NO_DATA_CONTENT_TEMPLATE', {
	            '#CATEGORY#': categoryCaption,
	            '#FIELD#': fieldCaption
	          }) : main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'));
	          const blockLayout = this.block.getLayout();
	          const resizeNode = blockLayout.querySelector('.--b2ereference');
	          this.block.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	      }).catch(err => {
	        console.error(err);
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content];
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$6)[_onActionClick$6]();
	  }
	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton$2)[_actionButton$2] = main_core.Tag.render(_t3$5 || (_t3$5 = _$d`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$6)[_onActionClick$6].bind(this), this.block.getCode());
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$2)[_setActionButtonLabel$2]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton$2)[_actionButton$2];
	  }

	  /**
	   * Sets label to action button.
	   */

	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...B2eReference.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _setContentText2(value) {
	  const text = main_core.Type.isStringFilled(value) ? value : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1');
	  babelHelpers.classPrivateFieldLooseBase(this, _content)[_content].textContent = text;
	  babelHelpers.classPrivateFieldLooseBase(this, _content)[_content].title = text;
	}
	function _loadFieldAndCategoryCaption2() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2])) {
	    return Promise.resolve({
	      categoryCaption: '',
	      fieldCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1')
	    });
	  }
	  const defaultCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1');
	  if (babelHelpers.classPrivateFieldLooseBase(B2eReference, _loadFieldsPromise)[_loadFieldsPromise] === null) {
	    babelHelpers.classPrivateFieldLooseBase(B2eReference, _loadFieldsPromise)[_loadFieldsPromise] = sign_v2_b2e_fieldSelector.FieldSelector.loadFieldList({});
	  }
	  return babelHelpers.classPrivateFieldLooseBase(B2eReference, _loadFieldsPromise)[_loadFieldsPromise].then(fieldList => {
	    var _fieldList$PROFILE$FI, _fieldList$PROFILE;
	    const fields = [...((_fieldList$PROFILE$FI = fieldList == null ? void 0 : (_fieldList$PROFILE = fieldList.PROFILE) == null ? void 0 : _fieldList$PROFILE.FIELDS) != null ? _fieldList$PROFILE$FI : [])];
	    const field = fields.find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2]);
	    return {
	      categoryCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
	      fieldCaption: field ? field.caption : defaultCaption
	    };
	  });
	}
	function _setActionButtonLabel2$2(label) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton$2)[_actionButton$2]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton$2)[_actionButton$2].querySelector('button');
	  actionButton.textContent = main_core.Type.isStringFilled(label) ? label : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1');
	}
	function _getFieldSelectorPanel2() {
	  const blocksManager = this.block.blocksManager;
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$5)[_cache$5].remember('fieldSelector', () => {
	    const selector = new sign_v2_b2e_fieldSelector.FieldSelector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 1,
	        hideSmartB2eDocument: 1
	      },
	      languages: blocksManager.getLanguages(),
	      filter: {
	        '+categories': ['PROFILE'],
	        '+fields': babelHelpers.classPrivateFieldLooseBase(this, _getSelectorFieldTypes)[_getSelectorFieldTypes](),
	        '-fields': [({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList$2)[_getFieldsNameBlackList$2]().has(fieldName)]
	      },
	      title: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1'),
	      categoryCaptions: {
	        'PROFILE': main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E')
	      },
	      fieldsFactory: babelHelpers.classPrivateFieldLooseBase(this, _getSelectorFieldsFactory)[_getSelectorFieldsFactory]()
	    });
	    selector.subscribe('onSliderCloseComplete', event => babelHelpers.classPrivateFieldLooseBase(this, _onFieldSelectorCloseComplete)[_onFieldSelectorCloseComplete](event));
	    return selector;
	  });
	}
	function _getFieldsNameBlackList2$2() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _onActionClick2$6() {
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _getFieldSelectorPanel)[_getFieldSelectorPanel]();
	  fieldSelector.show().then(selectedNames => {
	    babelHelpers.classPrivateFieldLooseBase(B2eReference, _updateFieldsList)[_updateFieldsList](fieldSelector.getFieldsList(false));
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$2)[_emitOnFieldSelectEvent$2](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _emitOnFieldSelectEvent2$2(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _onFieldSelectorCloseComplete2(event) {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$2)[_field$2])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$2)[_emitOnFieldSelectEvent$2]([]);
	  });
	}
	function _updateFieldsList2(fields) {
	  babelHelpers.classPrivateFieldLooseBase(B2eReference, _loadFieldsPromise)[_loadFieldsPromise] = Promise.resolve(fields);
	}
	function _getSelectorFieldTypes2() {
	  const types = ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'url', 'double', 'integer', 'snils'];
	  if (!this.block.blocksManager.isTemplateMode) {
	    types.push('address');
	  }
	  return types;
	}
	function _getSelectorFieldsFactory2() {
	  if (this.block.blocksManager.isTemplateMode) {
	    return {
	      filter: fields => fields.filter(field => main_core.Type.isObject(field) && main_core.Type.isStringFilled(field.name) && ['list', 'string', 'date', 'enumeration'].includes(field.name))
	    };
	  }
	  return null;
	}
	Object.defineProperty(B2eReference, _updateFieldsList, {
	  value: _updateFieldsList2
	});
	Object.defineProperty(B2eReference, _loadFieldsPromise, {
	  writable: true,
	  value: null
	});

	let _$e = t => t,
	  _t$e,
	  _t2$a,
	  _t3$6;
	var _cache$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _field$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _content$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _loadFieldsPromise$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldsPromise");
	var _setActionButtonLabel$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getFieldSelectorPanel$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldSelectorPanel");
	var _getFieldsNameBlackList$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _getCrmFieldsNameBlackList$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCrmFieldsNameBlackList");
	var _getFieldNegativeFilter$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldNegativeFilter");
	var _onActionClick$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _emitOnFieldSelectEvent$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _onFieldSelectorCloseComplete$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldSelectorCloseComplete");
	var _loadFieldAndCategoryCaption$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldAndCategoryCaption");
	var _setContentText$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContentText");
	var _updateFieldsList$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFieldsList");
	class MyB2eReference extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _setContentText$1, {
	      value: _setContentText2$1
	    });
	    Object.defineProperty(this, _loadFieldAndCategoryCaption$1, {
	      value: _loadFieldAndCategoryCaption2$1
	    });
	    Object.defineProperty(this, _onFieldSelectorCloseComplete$1, {
	      value: _onFieldSelectorCloseComplete2$1
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent$3, {
	      value: _emitOnFieldSelectEvent2$3
	    });
	    Object.defineProperty(this, _onActionClick$7, {
	      value: _onActionClick2$7
	    });
	    Object.defineProperty(this, _getFieldNegativeFilter$1, {
	      value: _getFieldNegativeFilter2$1
	    });
	    Object.defineProperty(this, _getCrmFieldsNameBlackList$3, {
	      value: _getCrmFieldsNameBlackList2$3
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList$3, {
	      value: _getFieldsNameBlackList2$3
	    });
	    Object.defineProperty(this, _getFieldSelectorPanel$1, {
	      value: _getFieldSelectorPanel2$1
	    });
	    Object.defineProperty(this, _setActionButtonLabel$3, {
	      value: _setActionButtonLabel2$3
	    });
	    Object.defineProperty(this, _cache$6, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _field$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content$1, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    var _this$data;
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$3)[_setActionButtonLabel$3]();
	    if (main_core.Type.isStringFilled((_this$data = this.data) == null ? void 0 : _this$data.text)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1] = main_core.Tag.render(_t$e || (_t$e = _$e`
				<div class="sign-document__block-b2e-reference">
					${0}
				</div>
			`), main_core.Text.encode(this.data.text || '').toString());
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1] = main_core.Tag.render(_t2$a || (_t2$a = _$e`
				<div class="sign-document__block-content_member-nodata">
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1'));
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadFieldAndCategoryCaption$1)[_loadFieldAndCategoryCaption$1]().then(({
	        categoryCaption,
	        fieldCaption
	      }) => {
	        var _this$data2;
	        babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$3)[_setActionButtonLabel$3](fieldCaption);
	        if (!main_core.Type.isStringFilled((_this$data2 = this.data) == null ? void 0 : _this$data2.text)) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setContentText$1)[_setContentText$1](main_core.Type.isStringFilled(categoryCaption) && main_core.Type.isStringFilled(fieldCaption) ? main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_NO_DATA_CONTENT_TEMPLATE', {
	            '#CATEGORY#': categoryCaption,
	            '#FIELD#': fieldCaption
	          }) : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1'));
	          const blockLayout = this.block.getLayout();
	          const resizeNode = blockLayout.querySelector('.--myb2ereference');
	          this.block.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	      }).catch(err => {
	        console.error(err);
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1];
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$7)[_onActionClick$7]();
	  }

	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton$3)[_actionButton$3] = main_core.Tag.render(_t3$6 || (_t3$6 = _$e`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$7)[_onActionClick$7].bind(this), this.block.getCode());
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$3)[_setActionButtonLabel$3]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton$3)[_actionButton$3];
	  }

	  /**
	   * Sets label to action button.
	   */

	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...MyB2eReference.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _setActionButtonLabel2$3(label) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton$3)[_actionButton$3]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton$3)[_actionButton$3].querySelector('button');
	  actionButton.textContent = main_core.Type.isStringFilled(label) ? label : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1');
	}
	function _getFieldSelectorPanel2$1() {
	  const blocksManager = this.block.blocksManager;
	  const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	  const {
	    presetId
	  } = member;
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$6)[_cache$6].remember('fieldSelector', () => {
	    const categories = ['COMPANY', 'PROFILE'];
	    if (this.block.blocksManager.documentInitiatedByType !== sign_type.DocumentInitiated.employee) {
	      categories.push('SMART_B2E_DOC');
	    }
	    const selector = new sign_v2_b2e_fieldSelector.FieldSelector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 0,
	        hideSmartB2eDocument: 1,
	        presetId
	      },
	      languages: blocksManager.getLanguages(),
	      presetId,
	      filter: {
	        '+categories': categories,
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'enumeration', 'address', 'url', 'double', 'integer', 'snils'],
	        '-fields': [babelHelpers.classPrivateFieldLooseBase(this, _getFieldNegativeFilter$1)[_getFieldNegativeFilter$1](), ({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getCrmFieldsNameBlackList$3)[_getCrmFieldsNameBlackList$3]().has(fieldName)]
	      },
	      categoryCaptions: {
	        'PROFILE': main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E')
	      }
	    });
	    selector.subscribe('onSliderCloseComplete', event => babelHelpers.classPrivateFieldLooseBase(this, _onFieldSelectorCloseComplete$1)[_onFieldSelectorCloseComplete$1](event));
	    return selector;
	  });
	}
	function _getFieldsNameBlackList2$3() {
	  return ['SMART_B2E_DOC_XML_ID', 'SMART_B2E_DOC_STAGE_ID', 'COMPANY_LINK', 'COMPANY_REG_ADDRESS', 'COMPANY_ORIGIN_VERSION'];
	}
	function _getCrmFieldsNameBlackList2$3() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _getFieldNegativeFilter2$1() {
	  const blackList = babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList$3)[_getFieldsNameBlackList$3]();
	  return field => {
	    return blackList.includes(field.name);
	  };
	}
	function _onActionClick2$7() {
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _getFieldSelectorPanel$1)[_getFieldSelectorPanel$1]();
	  fieldSelector.show().then(selectedNames => {
	    babelHelpers.classPrivateFieldLooseBase(MyB2eReference, _updateFieldsList$1)[_updateFieldsList$1](fieldSelector.getFieldsList(false));
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$3)[_emitOnFieldSelectEvent$3](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _emitOnFieldSelectEvent2$3(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _onFieldSelectorCloseComplete2$1(event) {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$3)[_emitOnFieldSelectEvent$3]([]);
	  });
	}
	function _loadFieldAndCategoryCaption2$1() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3])) {
	    return Promise.resolve({
	      categoryCaption: '',
	      fieldCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1')
	    });
	  }
	  const defaultCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1');
	  if (babelHelpers.classPrivateFieldLooseBase(MyB2eReference, _loadFieldsPromise$1)[_loadFieldsPromise$1] === null) {
	    const blocksManager = this.block.blocksManager;
	    const member = blocksManager.getMemberByPart(this.block.getMemberPart());
	    const {
	      presetId
	    } = member;
	    babelHelpers.classPrivateFieldLooseBase(MyB2eReference, _loadFieldsPromise$1)[_loadFieldsPromise$1] = sign_v2_b2e_fieldSelector.FieldSelector.loadFieldList({
	      hideVirtual: 1,
	      hideRequisites: 1,
	      hideSmartB2eDocument: 1,
	      presetId
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(MyB2eReference, _loadFieldsPromise$1)[_loadFieldsPromise$1].then(fieldList => {
	    var _fieldList$PROFILE$FI, _fieldList$PROFILE, _fieldList$SMART_B2E_, _fieldList$SMART_B2E_2, _fieldList$COMPANY$FI, _fieldList$COMPANY;
	    const categories = {
	      profile: fieldList == null ? void 0 : fieldList.PROFILE,
	      document: fieldList == null ? void 0 : fieldList.SMART_B2E_DOC,
	      company: fieldList == null ? void 0 : fieldList.COMPANY
	    };
	    Object.entries(categories).forEach(([key, category]) => {
	      var _category$FIELDS;
	      if (category === undefined) {
	        return;
	      }
	      category.FIELDS = ((_category$FIELDS = category == null ? void 0 : category.FIELDS) != null ? _category$FIELDS : []).map(field => {
	        return {
	          ...field,
	          category: key
	        };
	      });
	    });
	    const fields = [...((_fieldList$PROFILE$FI = fieldList == null ? void 0 : (_fieldList$PROFILE = fieldList.PROFILE) == null ? void 0 : _fieldList$PROFILE.FIELDS) != null ? _fieldList$PROFILE$FI : []), ...((_fieldList$SMART_B2E_ = fieldList == null ? void 0 : (_fieldList$SMART_B2E_2 = fieldList.SMART_B2E_DOC) == null ? void 0 : _fieldList$SMART_B2E_2.FIELDS) != null ? _fieldList$SMART_B2E_ : []), ...((_fieldList$COMPANY$FI = fieldList == null ? void 0 : (_fieldList$COMPANY = fieldList.COMPANY) == null ? void 0 : _fieldList$COMPANY.FIELDS) != null ? _fieldList$COMPANY$FI : [])];
	    const field = fields.find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _field$3)[_field$3]);
	    let categoryCaption = '';
	    if (main_core.Type.isObject(field)) {
	      if ((field == null ? void 0 : field.category) === 'profile') {
	        categoryCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E');
	      } else {
	        var _categories$field$cat, _categories$field$cat2;
	        categoryCaption = (_categories$field$cat = (_categories$field$cat2 = categories[field == null ? void 0 : field.category]) == null ? void 0 : _categories$field$cat2.CAPTION) != null ? _categories$field$cat : '';
	      }
	    }
	    return {
	      categoryCaption,
	      fieldCaption: field ? field.caption : defaultCaption
	    };
	  });
	}
	function _setContentText2$1(value) {
	  const text = main_core.Type.isStringFilled(value) ? value : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1');
	  babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1].textContent = text;
	  babelHelpers.classPrivateFieldLooseBase(this, _content$1)[_content$1].title = text;
	}
	function _updateFieldsList2$1(fields) {
	  babelHelpers.classPrivateFieldLooseBase(MyB2eReference, _loadFieldsPromise$1)[_loadFieldsPromise$1] = Promise.resolve(fields);
	}
	Object.defineProperty(MyB2eReference, _updateFieldsList$1, {
	  value: _updateFieldsList2$1
	});
	Object.defineProperty(MyB2eReference, _loadFieldsPromise$1, {
	  writable: true,
	  value: null
	});

	let _$f = t => t,
	  _t$f,
	  _t2$b,
	  _t3$7;
	var _cache$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _field$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _content$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _loadFieldsPromise$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldsPromise");
	var _setContentText$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContentText");
	var _loadFieldAndCategoryCaption$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldAndCategoryCaption");
	var _setActionButtonLabel$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getFieldSelectorPanel$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldSelectorPanel");
	var _getFieldsNameBlackList$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _onActionClick$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _emitOnFieldSelectEvent$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _onFieldSelectorCloseComplete$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldSelectorCloseComplete");
	var _updateFieldsList$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFieldsList");
	class EmployeeDynamic extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _onFieldSelectorCloseComplete$2, {
	      value: _onFieldSelectorCloseComplete2$2
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent$4, {
	      value: _emitOnFieldSelectEvent2$4
	    });
	    Object.defineProperty(this, _onActionClick$8, {
	      value: _onActionClick2$8
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList$4, {
	      value: _getFieldsNameBlackList2$4
	    });
	    Object.defineProperty(this, _getFieldSelectorPanel$2, {
	      value: _getFieldSelectorPanel2$2
	    });
	    Object.defineProperty(this, _setActionButtonLabel$4, {
	      value: _setActionButtonLabel2$4
	    });
	    Object.defineProperty(this, _loadFieldAndCategoryCaption$2, {
	      value: _loadFieldAndCategoryCaption2$2
	    });
	    Object.defineProperty(this, _setContentText$2, {
	      value: _setContentText2$2
	    });
	    Object.defineProperty(this, _cache$7, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _field$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content$2, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data || {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    var _this$data;
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$4)[_setActionButtonLabel$4]();
	    if (main_core.Type.isStringFilled((_this$data = this.data) == null ? void 0 : _this$data.text)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2] = main_core.Tag.render(_t$f || (_t$f = _$f`
				<div class="sign-document__block-b2e-reference">
					${0}
				</div>
			`), main_core.Text.encode(this.data.text || '').toString());
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2] = main_core.Tag.render(_t2$b || (_t2$b = _$f`
				<div class="sign-document__block-content_member-nodata">
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'));
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadFieldAndCategoryCaption$2)[_loadFieldAndCategoryCaption$2]().then(({
	        categoryCaption,
	        fieldCaption
	      }) => {
	        var _this$data2;
	        babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$4)[_setActionButtonLabel$4](fieldCaption);
	        if (!main_core.Type.isStringFilled((_this$data2 = this.data) == null ? void 0 : _this$data2.text)) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setContentText$2)[_setContentText$2](main_core.Type.isStringFilled(categoryCaption) && main_core.Type.isStringFilled(fieldCaption) ? main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC_NO_DATA_CONTENT_TEMPLATE', {
	            '#CATEGORY#': categoryCaption,
	            '#FIELD#': fieldCaption
	          }) : main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'));
	          const blockLayout = this.block.getLayout();
	          const resizeNode = blockLayout.querySelector('.--employeedynamic');
	          this.block.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	      }).catch(err => {
	        console.error(err);
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2];
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$8)[_onActionClick$8]();
	  }
	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton$4)[_actionButton$4] = main_core.Tag.render(_t3$7 || (_t3$7 = _$f`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$8)[_onActionClick$8].bind(this), this.block.getCode());
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$4)[_setActionButtonLabel$4]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton$4)[_actionButton$4];
	  }

	  /**
	   * Sets label to action button.
	   */

	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...EmployeeDynamic.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _setContentText2$2(value) {
	  const text = main_core.Type.isStringFilled(value) ? value : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC');
	  babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2].textContent = text;
	  babelHelpers.classPrivateFieldLooseBase(this, _content$2)[_content$2].title = text;
	}
	function _loadFieldAndCategoryCaption2$2() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4])) {
	    return Promise.resolve({
	      categoryCaption: '',
	      fieldCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E')
	    });
	  }
	  const defaultCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E');
	  if (babelHelpers.classPrivateFieldLooseBase(EmployeeDynamic, _loadFieldsPromise$2)[_loadFieldsPromise$2] === null) {
	    babelHelpers.classPrivateFieldLooseBase(EmployeeDynamic, _loadFieldsPromise$2)[_loadFieldsPromise$2] = sign_v2_b2e_fieldSelector.FieldSelector.loadFieldList({});
	  }
	  return babelHelpers.classPrivateFieldLooseBase(EmployeeDynamic, _loadFieldsPromise$2)[_loadFieldsPromise$2].then(fieldList => {
	    var _fieldList$DYNAMIC_ME, _fieldList$DYNAMIC_ME2;
	    const fields = [...((_fieldList$DYNAMIC_ME = fieldList == null ? void 0 : (_fieldList$DYNAMIC_ME2 = fieldList.DYNAMIC_MEMBER) == null ? void 0 : _fieldList$DYNAMIC_ME2.FIELDS) != null ? _fieldList$DYNAMIC_ME : [])];
	    const field = fields.find(item => item.name === babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4]);
	    return {
	      categoryCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
	      fieldCaption: field ? field.caption : defaultCaption
	    };
	  });
	}
	function _setActionButtonLabel2$4(label) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton$4)[_actionButton$4]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton$4)[_actionButton$4].querySelector('button');
	  actionButton.textContent = main_core.Type.isStringFilled(label) ? label : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC');
	}
	function _getFieldSelectorPanel2$2() {
	  const blocksManager = this.block.blocksManager;
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$7)[_cache$7].remember('fieldSelector', () => {
	    const selector = new sign_v2_b2e_fieldSelector.FieldSelector({
	      multiple: false,
	      controllerOptions: {
	        hideVirtual: 1,
	        hideRequisites: 1,
	        hideSmartB2eDocument: 1
	      },
	      languages: blocksManager.getLanguages(),
	      filter: {
	        '+categories': ['DYNAMIC_MEMBER'],
	        '+fields': ['list', 'string', 'date', 'typed_string', 'text', 'datetime', 'enumeration', 'url', 'double', 'integer', 'snils'],
	        '-fields': [({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList$4)[_getFieldsNameBlackList$4]().has(fieldName)],
	        allowEmptyFieldList: true
	      },
	      title: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC'),
	      categoryCaptions: {
	        DYNAMIC_MEMBER: main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E')
	      },
	      fieldsFactory: {
	        filter: fields => fields.filter(field => main_core.Type.isObject(field) && main_core.Type.isStringFilled(field.name) && ['list', 'string', 'date', 'enumeration'].includes(field.name))
	      }
	    });
	    selector.subscribe('onSliderCloseComplete', event => babelHelpers.classPrivateFieldLooseBase(this, _onFieldSelectorCloseComplete$2)[_onFieldSelectorCloseComplete$2](event));
	    return selector;
	  });
	}
	function _getFieldsNameBlackList2$4() {
	  return new Set(['ADDRESS', 'REG_ADDRESS']);
	}
	function _onActionClick2$8() {
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _getFieldSelectorPanel$2)[_getFieldSelectorPanel$2]();
	  fieldSelector.show().then(selectedNames => {
	    babelHelpers.classPrivateFieldLooseBase(EmployeeDynamic, _updateFieldsList$2)[_updateFieldsList$2](fieldSelector.getFieldsList(false));
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$4)[_emitOnFieldSelectEvent$4](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _emitOnFieldSelectEvent2$4(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _onFieldSelectorCloseComplete2$2(event) {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$4)[_field$4])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$4)[_emitOnFieldSelectEvent$4]([]);
	  });
	}
	function _updateFieldsList2$2(fields) {
	  babelHelpers.classPrivateFieldLooseBase(EmployeeDynamic, _loadFieldsPromise$2)[_loadFieldsPromise$2] = Promise.resolve(fields);
	}
	Object.defineProperty(EmployeeDynamic, _updateFieldsList$2, {
	  value: _updateFieldsList2$2
	});
	Object.defineProperty(EmployeeDynamic, _loadFieldsPromise$2, {
	  writable: true,
	  value: null
	});

	let _$g = t => t,
	  _t$g,
	  _t2$c,
	  _t3$8;
	var _cache$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _field$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("field");
	var _actionButton$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionButton");
	var _content$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _loadFieldsPromise$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldsPromise");
	var _lastLoadFieldsDocumentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastLoadFieldsDocumentUid");
	var _setContentText$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setContentText");
	var _loadFieldAndCategoryCaption$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadFieldAndCategoryCaption");
	var _setActionButtonLabel$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setActionButtonLabel");
	var _getFieldSelectorPanel$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldSelectorPanel");
	var _getFieldsNameBlackList$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getFieldsNameBlackList");
	var _onActionClick$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onActionClick");
	var _emitOnFieldSelectEvent$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("emitOnFieldSelectEvent");
	var _onFieldSelectorCloseComplete$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldSelectorCloseComplete");
	var _extractPartyFromSelectedField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extractPartyFromSelectedField");
	var _updateFieldsList$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateFieldsList");
	var _getCustomBackendSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCustomBackendSettings");
	class HcmLinkReference extends Dummy {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getCustomBackendSettings, {
	      value: _getCustomBackendSettings2
	    });
	    Object.defineProperty(this, _extractPartyFromSelectedField, {
	      value: _extractPartyFromSelectedField2
	    });
	    Object.defineProperty(this, _onFieldSelectorCloseComplete$3, {
	      value: _onFieldSelectorCloseComplete2$3
	    });
	    Object.defineProperty(this, _emitOnFieldSelectEvent$5, {
	      value: _emitOnFieldSelectEvent2$5
	    });
	    Object.defineProperty(this, _onActionClick$9, {
	      value: _onActionClick2$9
	    });
	    Object.defineProperty(this, _getFieldsNameBlackList$5, {
	      value: _getFieldsNameBlackList2$5
	    });
	    Object.defineProperty(this, _getFieldSelectorPanel$3, {
	      value: _getFieldSelectorPanel2$3
	    });
	    Object.defineProperty(this, _setActionButtonLabel$5, {
	      value: _setActionButtonLabel2$5
	    });
	    Object.defineProperty(this, _loadFieldAndCategoryCaption$3, {
	      value: _loadFieldAndCategoryCaption2$3
	    });
	    Object.defineProperty(this, _setContentText$3, {
	      value: _setContentText2$3
	    });
	    Object.defineProperty(this, _cache$8, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _field$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actionButton$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content$3, {
	      writable: true,
	      value: void 0
	    });
	  }
	  /**
	   * Sets new data.
	   * @param {any} data
	   */
	  setData(data) {
	    var _this$data$field;
	    this.data = data ? data : {};
	    babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5] = (_this$data$field = this.data.field) != null ? _this$data$field : '';
	    const party = babelHelpers.classPrivateFieldLooseBase(this, _extractPartyFromSelectedField)[_extractPartyFromSelectedField](babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5]);
	    if (party > 0) {
	      this.block.setMemberParty(party);
	    }
	  }

	  /**
	   * Returns type's content in view mode.
	   * @return {HTMLElement | string}
	   */
	  getViewContent() {
	    var _this$data;
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$5)[_setActionButtonLabel$5]();
	    if (main_core.Type.isStringFilled((_this$data = this.data) == null ? void 0 : _this$data.text)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3] = main_core.Tag.render(_t$g || (_t$g = _$g`
				<div class="sign-document__block-b2e-hcmlinkreference">
					${0}
				</div>
			`), main_core.Text.encode(this.data.text || '').toString());
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3] = main_core.Tag.render(_t2$c || (_t2$c = _$g`
				<div class="sign-document__block-content_member-nodata">
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'));
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5])) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loadFieldAndCategoryCaption$3)[_loadFieldAndCategoryCaption$3]().then(({
	        categoryCaption,
	        fieldCaption
	      }) => {
	        var _this$data2;
	        babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$5)[_setActionButtonLabel$5](fieldCaption);
	        if (!main_core.Type.isStringFilled((_this$data2 = this.data) == null ? void 0 : _this$data2.text)) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setContentText$3)[_setContentText$3](main_core.Type.isStringFilled(categoryCaption) && main_core.Type.isStringFilled(fieldCaption) ? main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_REFERENCE_NO_DATA_CONTENT_TEMPLATE', {
	            '#CATEGORY#': categoryCaption,
	            '#FIELD#': fieldCaption
	          }) : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'));
	          const blockLayout = this.block.getLayout();
	          const resizeNode = blockLayout.querySelector('.--hcmlinkreference');
	          this.block.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	      }).catch(err => {
	        console.error(err);
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3];
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$9)[_onActionClick$9]();
	  }
	  /**
	   * Returns action button for edit content.
	   * @return {HTMLElement | null}
	   */
	  getActionButton() {
	    babelHelpers.classPrivateFieldLooseBase(this, _actionButton$5)[_actionButton$5] = main_core.Tag.render(_t3$8 || (_t3$8 = _$g`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${0}" data-role="action" data-id="action-${0}">
				</button>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _onActionClick$9)[_onActionClick$9].bind(this), this.block.getCode());
	    babelHelpers.classPrivateFieldLooseBase(this, _setActionButtonLabel$5)[_setActionButtonLabel$5]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _actionButton$5)[_actionButton$5];
	  }

	  /**
	   * Sets label to action button.
	   */

	  getStyles() {
	    return {
	      ...super.getStyles(),
	      ...this.defaultTextBlockPaddingStyles
	    };
	  }
	}
	function _setContentText2$3(value) {
	  const text = main_core.Type.isStringFilled(value) ? value : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE');
	  babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3].textContent = text;
	  babelHelpers.classPrivateFieldLooseBase(this, _content$3)[_content$3].title = text;
	}
	function _loadFieldAndCategoryCaption2$3() {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5])) {
	    return Promise.resolve({
	      categoryCaption: '',
	      fieldCaption: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE')
	    });
	  }
	  const defaultCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE');
	  const documentUid = this.block.blocksManager.getDocumentUid();
	  if (babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _loadFieldsPromise$3)[_loadFieldsPromise$3] === null || documentUid !== babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _lastLoadFieldsDocumentUid)[_lastLoadFieldsDocumentUid]) {
	    babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _lastLoadFieldsDocumentUid)[_lastLoadFieldsDocumentUid] = documentUid;
	    babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _loadFieldsPromise$3)[_loadFieldsPromise$3] = sign_v2_b2e_fieldSelector.FieldSelector.loadFieldList({}, babelHelpers.classPrivateFieldLooseBase(this, _getCustomBackendSettings)[_getCustomBackendSettings]());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _loadFieldsPromise$3)[_loadFieldsPromise$3].then(fieldList => {
	    var _fieldList$REPRESENTA, _fieldList$REPRESENTA2, _fieldList$EMPLOYEE$F, _fieldList$EMPLOYEE, _fieldList$COMPANY$FI, _fieldList$COMPANY;
	    const categories = {
	      representative: fieldList == null ? void 0 : fieldList.REPRESENTATIVE,
	      employee: fieldList == null ? void 0 : fieldList.EMPLOYEE,
	      company: fieldList == null ? void 0 : fieldList.COMPANY
	    };
	    Object.entries(categories).forEach(([key, category]) => {
	      var _category$FIELDS;
	      if (category === undefined) {
	        return;
	      }
	      category.FIELDS = ((_category$FIELDS = category == null ? void 0 : category.FIELDS) != null ? _category$FIELDS : []).map(field => {
	        return {
	          ...field,
	          category: key
	        };
	      });
	    });
	    const fields = [...((_fieldList$REPRESENTA = fieldList == null ? void 0 : (_fieldList$REPRESENTA2 = fieldList.REPRESENTATIVE) == null ? void 0 : _fieldList$REPRESENTA2.FIELDS) != null ? _fieldList$REPRESENTA : []), ...((_fieldList$EMPLOYEE$F = fieldList == null ? void 0 : (_fieldList$EMPLOYEE = fieldList.EMPLOYEE) == null ? void 0 : _fieldList$EMPLOYEE.FIELDS) != null ? _fieldList$EMPLOYEE$F : []), ...((_fieldList$COMPANY$FI = fieldList == null ? void 0 : (_fieldList$COMPANY = fieldList.COMPANY) == null ? void 0 : _fieldList$COMPANY.FIELDS) != null ? _fieldList$COMPANY$FI : [])];
	    const field = fields.find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5]);
	    let categoryCaption = '';
	    if (main_core.Type.isObject(field)) {
	      if ((field == null ? void 0 : field.category) === 'representative') {
	        categoryCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E');
	      } else if ((field == null ? void 0 : field.category) === 'employee') {
	        categoryCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E');
	      } else if ((field == null ? void 0 : field.category) === 'company') {
	        categoryCaption = main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_COMPANY');
	      } else {
	        var _categories$field$cat, _categories$field$cat2;
	        categoryCaption = (_categories$field$cat = (_categories$field$cat2 = categories[field == null ? void 0 : field.category]) == null ? void 0 : _categories$field$cat2.CAPTION) != null ? _categories$field$cat : '';
	      }
	    }
	    return {
	      categoryCaption,
	      fieldCaption: field ? field.caption : defaultCaption
	    };
	  });
	}
	function _setActionButtonLabel2$5(label) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionButton$5)[_actionButton$5]) {
	    return;
	  }
	  const actionButton = babelHelpers.classPrivateFieldLooseBase(this, _actionButton$5)[_actionButton$5].querySelector('button');
	  actionButton.textContent = main_core.Type.isStringFilled(label) ? label : main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE');
	}
	function _getFieldSelectorPanel2$3() {
	  const blocksManager = this.block.blocksManager;
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _cache$8)[_cache$8].remember('fieldSelector', () => {
	    const selector = new sign_v2_b2e_fieldSelector.FieldSelector({
	      multiple: false,
	      languages: blocksManager.getLanguages(),
	      filter: {
	        '+categories': ['REPRESENTATIVE', 'EMPLOYEE'],
	        '+fields': [],
	        '-fields': [({
	          entity_field_name: fieldName
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _getFieldsNameBlackList$5)[_getFieldsNameBlackList$5]().has(fieldName)]
	      },
	      title: main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE'),
	      categoryCaptions: {
	        'REPRESENTATIVE': main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_REPRESENTATIVE_B2E'),
	        'EMPLOYEE': main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'),
	        'COMPANY': main_core.Loc.getMessage('SIGN_EDITOR_BLOCKS_COMPANY')
	      },
	      alwaysHideCreateFieldButton: true
	    });
	    selector.subscribe('onSliderCloseComplete', event => babelHelpers.classPrivateFieldLooseBase(this, _onFieldSelectorCloseComplete$3)[_onFieldSelectorCloseComplete$3](event));
	    return selector;
	  });
	  fieldSelector.setCustomBackendSettings(babelHelpers.classPrivateFieldLooseBase(this, _getCustomBackendSettings)[_getCustomBackendSettings]());
	  return fieldSelector;
	}
	function _getFieldsNameBlackList2$5() {
	  return new Set([]);
	}
	function _onActionClick2$9() {
	  const fieldSelector = babelHelpers.classPrivateFieldLooseBase(this, _getFieldSelectorPanel$3)[_getFieldSelectorPanel$3]();
	  fieldSelector.show().then(selectedNames => {
	    let list = fieldSelector.getFieldsList(false);
	    babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _updateFieldsList$3)[_updateFieldsList$3](list);
	    const eventSelectedNames = [...selectedNames];
	    if (selectedNames.length === 0 && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5])) {
	      eventSelectedNames.push(babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$5)[_emitOnFieldSelectEvent$5](eventSelectedNames);
	    if (selectedNames.length === 0) {
	      return;
	    }
	    main_core.Dom.removeClass(this.block.getLayout(), '--invalid');
	    babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5] = selectedNames[0];
	    this.setData({
	      field: babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5]
	    });
	    setTimeout(() => {
	      this.block.assign();
	    }, 0);
	  });
	}
	function _emitOnFieldSelectEvent2$5(selectedNames) {
	  const onFieldSelectEventData = {
	    selectedFieldNames: selectedNames
	  };
	  const onFieldSelectEvent = new main_core_events.BaseEvent({
	    data: onFieldSelectEventData
	  });
	  this.emit('onFieldSelect', onFieldSelectEvent);
	}
	function _onFieldSelectorCloseComplete2$3(event) {
	  setTimeout(() => {
	    // event already sended
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _field$5)[_field$5])) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _emitOnFieldSelectEvent$5)[_emitOnFieldSelectEvent$5]([]);
	  });
	}
	function _extractPartyFromSelectedField2(fieldName) {
	  if (!main_core.Type.isStringFilled(fieldName)) {
	    return null;
	  }
	  const splitedName = fieldName.split('_');
	  return splitedName[3] ? Number(splitedName[3]) : null;
	}
	function _updateFieldsList2$3(fields) {
	  babelHelpers.classPrivateFieldLooseBase(HcmLinkReference, _loadFieldsPromise$3)[_loadFieldsPromise$3] = Promise.resolve(fields);
	}
	function _getCustomBackendSettings2() {
	  return {
	    uri: 'sign.api_v1.integration.humanresources.hcmLink.loadFields',
	    requestOptions: {
	      documentUid: this.block.blocksManager.getDocumentUid()
	    }
	  };
	}
	Object.defineProperty(HcmLinkReference, _updateFieldsList$3, {
	  value: _updateFieldsList2$3
	});
	Object.defineProperty(HcmLinkReference, _loadFieldsPromise$3, {
	  writable: true,
	  value: null
	});
	Object.defineProperty(HcmLinkReference, _lastLoadFieldsDocumentUid, {
	  writable: true,
	  value: null
	});

	let _$h = t => t,
	  _t$h,
	  _t2$d,
	  _t3$9,
	  _t4$1,
	  _t5$1,
	  _t6$1,
	  _t7$1,
	  _t8$1,
	  _t9$1;
	const ColorPicker$1 = main_core.Reflection.getClass('BX.ColorPicker');
	class UI$1 {
	  /**
	   * Sets width/height/top/left to element.
	   * @param {HTMLElement} element
	   * @param {{[key: string]: number}} rect
	   */
	  static setRect(element, rect) {
	    Object.keys(rect).map(key => {
	      rect[key] = `${Math.round(rect[key])}px`;
	    });
	    main_core.Dom.style(element, rect);
	  }

	  /**
	   * Returns block's layout.
	   * @param {BlockLayoutOptions} options Layout options.
	   * @return {HTMLElement}
	   */
	  static getBlockLayout(options) {
	    return main_core.Tag.render(_t$h || (_t$h = _$h`
			<div class="sign-document__block-wrapper">
				<div class="sign-document__block-panel--wrapper" data-role="sign-block__actions">
				</div>
				<div class="sign-document__block-content">
				</div>
				<div class="sign-document__block-actions">
					<div class="sign-document__block-actions--wrapper">
						<button class="sign-document__block-actions-btn --remove sign-block-action-remove" data-role="removeAction" onclick="${0}"></button>
						<button class="sign-document__block-actions-btn --save sign-block-action-save" data-role="saveAction" onclick="${0}"></button>
					</div>
				</div>
			</div>
		`), options.onRemove, options.onSave);
	  }

	  /**
	   * Returns member selector for block.
	   * @param {Array<MemberItem>} members All document's members.
	   * @param {number} selectedValue Selected member.
	   * @param {() => {}} onChange Handler on change value.
	   * @return {HTMLElement}
	   */
	  static getMemberSelector(members, selectedValue, onChange) {
	    const menuItems = {};
	    let selectedName = main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	    members.map(member => {
	      member.name = member.name || main_core.Loc.getMessage('SIGN_JS_DOCUMENT_MEMBER_NAME_NOT_SET');
	      menuItems[member.part] = member.name;
	      if (member.part === selectedValue) {
	        selectedName = member.name;
	      }
	    });
	    const memberSelector = members.length > 1 ? main_core.Tag.render(_t2$d || (_t2$d = _$h`<span>${0}</span>`), main_core.Text.encode(selectedName)) : main_core.Tag.render(_t3$9 || (_t3$9 = _$h`<i>${0}</i>`), main_core.Text.encode(selectedName));
	    if (members.length > 1) {
	      sign_ui.UI.bindSimpleMenu({
	        bindElement: memberSelector,
	        items: menuItems,
	        actionHandler: value => {
	          memberSelector.innerHTML = menuItems[value];
	          onChange(parseInt(value));
	        }
	      });
	    }
	    return main_core.Tag.render(_t4$1 || (_t4$1 = _$h`
			<div class="sign-document-block-member-wrapper">
				${0}
			</div>
		`), memberSelector);
	  }

	  /**
	   * Returns resizing area's layout.
	   * @return {HTMLElement}
	   */
	  static getResizeArea() {
	    return main_core.Tag.render(_t5$1 || (_t5$1 = _$h`
			<div class="sign-document__resize-area">
				<div class="sign-area-resizable-controls">
					<span class="sign-document__move-control"></span>
					<div class="sign-document__resize-control --middle-top"></div>
					<div class="sign-document__resize-control --right-top"></div>
					<div class="sign-document__resize-control --middle-right"></div>
					<div class="sign-document__resize-control --right-bottom"></div>
					<div class="sign-document__resize-control --middle-bottom"></div>
					<div class="sign-document__resize-control --left-bottom"></div>
					<div class="sign-document__resize-control --middle-left"></div>
				</div>
			</div>
		`));
	  }

	  /**
	   * Returns style panel layout.
	   * @return {HTMLElement}
	   */
	  static getStylePanel(actionHandler, collectStyles) {
	    var _collectStyles$color;
	    // font family selector
	    const fonts = {
	      '"Times New Roman", Times': '<span style="font-family: \'Times New Roman\', Times">Times New Roman</span>',
	      '"Courier New"': '<span style="font-family: \'Courier New\'">Courier New</span>',
	      'Arial, Helvetica': '<span style="font-family: Arial, Helvetica">Arial / Helvetica</span>',
	      '"Arial Black", Gadget': '<span style="font-family: \'Arial Black\', Gadget">Arial Black</span>',
	      'Tahoma, Geneva': '<span style="font-family: Tahoma, Geneva">Tahoma / Geneva</span>',
	      'Verdana': '<span style="font-family: Verdana">Verdana</span>',
	      'Georgia, serif': '<span style="font-family: Georgia, serif">Georgia</span>',
	      'monospace': '<span style="font-family: monospace">monospace</span>'
	    };
	    const fontFamily = main_core.Tag.render(_t6$1 || (_t6$1 = _$h`<div class="sign-document__block-style-btn --btn-font-family">${0}</div>`), fonts[collectStyles['fontFamily']] || 'Font');
	    sign_ui.UI.bindSimpleMenu({
	      bindElement: fontFamily,
	      items: fonts,
	      actionHandler: value => {
	        fontFamily.innerHTML = fonts[value];
	        actionHandler('family', value);
	      }
	    });

	    // font size selector

	    let fontSizereal = parseInt(collectStyles['fontSize']);
	    let fontSizeValue = 14;
	    if (fontSizereal) {
	      fontSizeValue = fontSizereal;
	    }
	    const fontSize = main_core.Tag.render(_t7$1 || (_t7$1 = _$h`<div class="sign-document__block-style-btn --btn-fontsize">${0}</div>`), fontSizeValue + 'px' || '<i></i>');
	    sign_ui.UI.bindSimpleMenu({
	      bindElement: fontSize,
	      items: ['6px', '7px', '8px', '9px', '10px', '11px', '12px', '13px', '14px', '15px', '16px', '18px', '20px', '22px', '24px', '26px', '28px', '36px', '48px', '72px'],
	      actionHandler: value => {
	        fontSize.innerHTML = parseInt(value) + 'px';
	        actionHandler('size', value);
	      },
	      currentValue: fontSizeValue
	    });
	    const {
	      layout: fontColor
	    } = UI$1.getColorSelectorBtn((_collectStyles$color = collectStyles.color) != null ? _collectStyles$color : '#000', color => actionHandler('color', color));
	    return main_core.Tag.render(_t8$1 || (_t8$1 = _$h`
			<div class="sign-document__block-style--panel">
<!--				<div class="sign-document__block-style&#45;&#45;move-control"></div>-->
				${0}
				${0}
				${0}
				<div class="sign-document__block-style--separator"></div>
				<div class="sign-document__block-style-btn --btn-bold" data-action="bold"><i></i></div>
				<div class="sign-document__block-style-btn --btn-italic" data-action="italic"><i></i></div>
				<div class="sign-document__block-style-btn --btn-underline" data-action="underline"><i></i></div>
				<div class="sign-document__block-style-btn --btn-strike" data-action="through"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-left" data-action="left"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-center" data-action="center"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-right" data-action="right"><i></i></div>
				<div class="sign-document__block-style-btn --btn-align-justify" data-action="justify"><i></i></div>
			</div>
		`), fontFamily, fontSize, fontColor);
	  }
	  static getColorSelectorBtn(defaultColorPickerColor, onColorSelect, colorPickerOptions = {}) {
	    const layout = main_core.Tag.render(_t9$1 || (_t9$1 = _$h`<div class="sign-document__block-style-btn --btn-color">
				<span class="sign-document__block-style-btn--color-block"></span> 
				<span>${0}</span>
			</div>`), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_STYLE_COLOR'));
	    UI$1.updateColorSelectorBtnColor(layout, defaultColorPickerColor);
	    const updatedColorPickerOptions = {
	      ...colorPickerOptions,
	      bindElement: layout,
	      onColorSelected: color => {
	        onColorSelect(color);
	        UI$1.updateColorSelectorBtnColor(layout, color);
	      }
	    };
	    const picker = new ColorPicker$1(updatedColorPickerOptions);
	    main_core.Event.bind(layout, 'click', () => {
	      picker.open();
	    });
	    return {
	      layout,
	      colorPicker: picker
	    };
	  }
	  static updateColorSelectorBtnColor(layout, color) {
	    const circleColor = layout.querySelector('.sign-document__block-style-btn--color-block');
	    if (main_core.Type.isNil(circleColor)) {
	      return;
	    }
	    main_core.Dom.style(circleColor, 'background-color', color);
	  }
	}

	var _block = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("block");
	var _buttons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttons");
	var _style = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("style");
	var _onPressButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onPressButton");
	var _applyData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyData");
	class Style {
	  /**
	   * Constructor.
	   * @param {StyleOptions} options
	   */
	  constructor(options) {
	    Object.defineProperty(this, _applyData, {
	      value: _applyData2
	    });
	    Object.defineProperty(this, _onPressButton, {
	      value: _onPressButton2
	    });
	    Object.defineProperty(this, _block, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _buttons, {
	      writable: true,
	      value: {
	        family: {
	          property: 'fontFamily',
	          value: null
	        },
	        size: {
	          property: 'fontSize',
	          value: 14
	        },
	        color: {
	          property: 'color',
	          value: null
	        },
	        bold: {
	          button: null,
	          property: 'fontWeight',
	          value: 'bold',
	          state: false
	        },
	        italic: {
	          button: null,
	          property: 'fontStyle',
	          value: 'italic',
	          state: false
	        },
	        underline: {
	          button: null,
	          property: 'textDecoration',
	          value: 'underline',
	          state: false
	        },
	        through: {
	          button: null,
	          property: 'textDecoration',
	          value: 'line-through',
	          state: false
	        },
	        left: {
	          button: null,
	          property: 'textAlign',
	          value: 'left',
	          state: false,
	          group: 'align'
	        },
	        center: {
	          button: null,
	          property: 'textAlign',
	          value: 'center',
	          state: false,
	          group: 'align'
	        },
	        right: {
	          button: null,
	          property: 'textAlign',
	          value: 'right',
	          state: false,
	          group: 'align'
	        },
	        justify: {
	          button: null,
	          property: 'textAlign',
	          value: 'justify',
	          state: false,
	          group: 'align'
	        }
	      }
	    });
	    Object.defineProperty(this, _style, {
	      writable: true,
	      value: {
	        buttonPressed: 'sign-document__block-style--button-pressed'
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _block)[_block] = options.block;
	    if (options.data) {
	      babelHelpers.classPrivateFieldLooseBase(this, _applyData)[_applyData](options.data);
	    }
	  }

	  /**
	   * Handle on panel button click.
	   * @param {string} code
	   */

	  /**
	   * Applies collected styles to the element.
	   * @param {HTMLElement} element
	   */
	  applyStyles(element) {
	    element.removeAttribute('style');
	    main_core.Dom.style(element, this.collectStyles());
	  }
	  updateFontSize(fontSize) {
	    if (fontSize) {
	      babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons].size['property'] = 'fontSize';
	      babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons].size['value'] = fontSize;
	    }
	  }

	  /**
	   * Collects checked styles in one dataset.
	   * @return {{[key: string]: string}}
	   */
	  collectStyles() {
	    const styles = {};
	    [...Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons])].map(key => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['state'] || typeof babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['state'] === 'undefined') {
	        const property = babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['property'];
	        const value = babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['value'];
	        if (value === null) {
	          return;
	        }
	        if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['group']) {
	          styles[property] = value;
	        } else {
	          styles[property] = (styles[property] ? styles[property] + ' ' : '') + value;
	        }
	      }
	    });
	    return styles;
	  }

	  /**
	   * Returns style panel layout.
	   * @return {HTMLElement}
	   */
	  getLayout() {
	    const layout = UI$1.getStylePanel((code, value) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['value'] = value;
	      babelHelpers.classPrivateFieldLooseBase(this, _block)[_block].renderStyle();
	    }, this.collectStyles());
	    [...layout.querySelectorAll('[data-action]')].map(button => {
	      const action = button.getAttribute('data-action');
	      if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][action]) {
	        main_core.Event.bind(button, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _onPressButton)[_onPressButton](action));
	        babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][action]['button'] = button;
	        if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][action]['state']) {
	          main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][action]['button'], babelHelpers.classPrivateFieldLooseBase(this, _style)[_style].buttonPressed);
	        }
	      }
	    });
	    return layout;
	  }
	  updateColor(color) {
	    babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons].color.value = color;
	  }
	}
	function _onPressButton2(code) {
	  babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['state'] = !babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['state'];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['state']) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['button'], babelHelpers.classPrivateFieldLooseBase(this, _style)[_style].buttonPressed);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['group']) {
	      const group = babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['group'];
	      [...Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons])].map(key => {
	        if (key !== code && babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['group'] === group) {
	          babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['state'] = false;
	          main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['button'], babelHelpers.classPrivateFieldLooseBase(this, _style)[_style].buttonPressed);
	        }
	      });
	    }
	  } else {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][code]['button'], babelHelpers.classPrivateFieldLooseBase(this, _style)[_style].buttonPressed);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _block)[_block].renderStyle();
	}
	function _applyData2(data) {
	  [...Object.keys(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons])].map(key => {
	    const property = babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['property'];
	    if (data[property]) {
	      if (typeof babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['state'] !== 'undefined') {
	        if (data[property].indexOf(babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['value']) !== -1) {
	          babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['state'] = true;
	        }
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _buttons)[_buttons][key]['value'] = data[property];
	      }
	    }
	  });
	}

	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _code = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("code");
	var _layout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _stylePanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("stylePanel");
	var _content$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _memberPart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("memberPart");
	var _panelCreated = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("panelCreated");
	var _allowMembers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("allowMembers");
	var _onClickCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickCallback");
	var _onRemoveCallback = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onRemoveCallback");
	var _contentProviders = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("contentProviders");
	var _currentFontSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentFontSize");
	var _style$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("style");
	var _firstRenderReady = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("firstRenderReady");
	var _api$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _onClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClick");
	var _createLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createLayout");
	var _onRemoveBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onRemoveBtnClick");
	var _onContentChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onContentChange");
	var _onContentColorStyleChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onContentColorStyleChange");
	var _onFieldSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFieldSelect");
	class Block extends main_core_events.EventEmitter {
	  /**
	   * Constructor.
	   * @param {BlockOptions} options
	   */
	  constructor(options) {
	    var _options$style, _options$position;
	    super();
	    Object.defineProperty(this, _onFieldSelect, {
	      value: _onFieldSelect2
	    });
	    Object.defineProperty(this, _onContentColorStyleChange, {
	      value: _onContentColorStyleChange2
	    });
	    Object.defineProperty(this, _onContentChange, {
	      value: _onContentChange2
	    });
	    Object.defineProperty(this, _onRemoveBtnClick, {
	      value: _onRemoveBtnClick2
	    });
	    Object.defineProperty(this, _createLayout, {
	      value: _createLayout2
	    });
	    Object.defineProperty(this, _onClick, {
	      value: _onClick2
	    });
	    this.events = {
	      onColorStyleChange: 'onColorStyleChange'
	    };
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _code, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _stylePanel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content$4, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _memberPart, {
	      writable: true,
	      value: 2
	    });
	    Object.defineProperty(this, _panelCreated, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _allowMembers, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _onClickCallback, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onRemoveCallback, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _contentProviders, {
	      writable: true,
	      value: {
	        date: Date,
	        myrequisites: MyRequisites,
	        mysign: MySign,
	        mystamp: MyStamp,
	        number: Number$1,
	        reference: Reference,
	        myreference: MyReference,
	        requisites: Requisites,
	        sign: Sign,
	        stamp: Stamp,
	        text: Text,
	        b2ereference: B2eReference,
	        myb2ereference: MyB2eReference,
	        employeedynamic: EmployeeDynamic,
	        hcmlinkreference: HcmLinkReference
	      }
	    });
	    Object.defineProperty(this, _currentFontSize, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _style$1, {
	      writable: true,
	      value: {
	        blockContent: '.sign-document__block-content',
	        blockPanel: '.sign-document__block-panel--wrapper',
	        blockLoading: 'sign-document-block-loading',
	        blockEditing: 'sign-document__block-wrapper-editing',
	        pageWithNotAllowed: 'sign-editor__content-document--active-move'
	      }
	    });
	    Object.defineProperty(this, _firstRenderReady, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _api$1, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.Editor.Block');
	    this.blocksManager = options.blocksManager;
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = options.id || null;
	    babelHelpers.classPrivateFieldLooseBase(this, _code)[_code] = options.code;
	    babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart] = options.party;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickCallback)[_onClickCallback] = options.onClick;
	    babelHelpers.classPrivateFieldLooseBase(this, _onRemoveCallback)[_onRemoveCallback] = options.onRemove;
	    babelHelpers.classPrivateFieldLooseBase(this, _firstRenderReady)[_firstRenderReady] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel] = new Style({
	      block: this,
	      data: options.style
	    });
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _contentProviders)[_contentProviders][babelHelpers.classPrivateFieldLooseBase(this, _code)[_code]]) {
	      throw new Error(`Content provider for '${babelHelpers.classPrivateFieldLooseBase(this, _code)[_code]}' not found.`);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4] = new (babelHelpers.classPrivateFieldLooseBase(this, _contentProviders)[_contentProviders][babelHelpers.classPrivateFieldLooseBase(this, _code)[_code]])(this);
	    if (['sign', 'mysign'].includes(babelHelpers.classPrivateFieldLooseBase(this, _code)[_code]) && main_core.Type.isString((_options$style = options.style) == null ? void 0 : _options$style.color)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].changeStyleColor(options.style.color, false);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _createLayout)[_createLayout]();
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout], 'click', babelHelpers.classPrivateFieldLooseBase(this, _onClick)[_onClick].bind(this));
	    if (options.party > 1 && !['b2ereference', 'employeedynamic', 'hcmlinkreference'].includes(babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase())) {
	      babelHelpers.classPrivateFieldLooseBase(this, _allowMembers)[_allowMembers] = true;
	    }
	    this.renderStyle();
	    this.setPosition((_options$position = options.position) != null ? _options$position : babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getInitDimension());
	    if (options.data) {
	      setTimeout(() => {
	        this.setData(options.data);
	      }, 0);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1] = new sign_v2_api.Api();
	  }

	  /**
	   * Returns block's layout.
	   * @return {HTMLElement}
	   */
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout];
	  }

	  /**
	   * Sets new data to the block.
	   * @param {any} data
	   */
	  setData(data) {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].setData(data);
	    this.renderView();
	  }

	  /**
	   * Sets initial position to the block.
	   * @param {PositionType} position
	   */
	  setPosition(position) {
	    UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout], position);
	  }

	  /**
	   * Returns block's data.
	   * @return {any}
	   */
	  getData() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getData();
	  }

	  /**
	   * Returns position.
	   * @return {PositionType}
	   */
	  getPosition() {
	    let {
	      top,
	      left,
	      width,
	      height
	    } = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].getBoundingClientRect();
	    const layout = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout];
	    top = Math.round(top);
	    left = Math.round(left);
	    width = Math.round(width);
	    height = Math.round(height);
	    const documentRect = this.blocksManager.getLayout().getBoundingClientRect();
	    top -= Math.round(documentRect.top);
	    left -= Math.round(documentRect.left);
	    return {
	      top,
	      left,
	      width,
	      height
	    };
	  }

	  /**
	   * Returns block styles.
	   * @return {{{[key: string]: string}}}
	   */
	  getStyle() {
	    return {
	      ...babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getStyles(),
	      ...babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].collectStyles()
	    };
	  }

	  /**
	   * Returns id.
	   * @return {number|null}
	   */
	  getId() {
	    var _babelHelpers$classPr;
	    return (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]) != null ? _babelHelpers$classPr : null;
	  }

	  /**
	   * Returns code.
	   * @return {string}
	   */
	  getCode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _code)[_code];
	  }

	  /**
	   * Shows page's areas not allowed for block's placement.
	   */
	  showNotAllowedArea() {
	    const {
	      page
	    } = this.blocksManager.transferPositionToPage(this.getPosition());
	    const pageElement = document.querySelector(`.sign-editor__content-document--page[data-page="${page}"]`);
	    main_core.Dom.addClass(pageElement, babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].pageWithNotAllowed);
	  }
	  hideNotAllowedArea() {
	    const {
	      page
	    } = this.blocksManager.transferPositionToPage(this.getPosition());
	    const pageElement = document.querySelector(`.sign-editor__content-document--page[data-page="${page}"]`);
	    main_core.Dom.removeClass(pageElement, babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].pageWithNotAllowed);
	  }

	  /**
	   * Returns member part.
	   * @return {number}
	   */
	  getMemberPart() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart];
	  }

	  /**
	   * Handler on click to block.
	   */

	  /**
	   * Calls block's action.
	   */
	  fireAction() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4]['onActionClick']) {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _code)[_code] === 'text') {
	        main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout], babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockEditing);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4]['onActionClick']();
	    }
	  }

	  /**
	   * Handler on member change.
	   * @param {number} part
	   */
	  onMemberSelect(part) {
	    babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart] = part;
	    this.assign();
	  }

	  /**
	   * Sets/removes awaiting class to the block.
	   * @param {boolean} flag
	   */
	  await(flag) {
	    const blockLayouts = [];
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].isSingleton()) {
	      blockLayouts.push(this.getLayout());
	    } else {
	      const currentCode = this.getCode();
	      this.blocksManager.getBlocks().map(block => {
	        if (block.getCode() === currentCode) {
	          blockLayouts.push(block.getLayout());
	        }
	      });
	    }
	    blockLayouts.map((blockLayout, key) => {
	      if (flag) {
	        if (blockLayouts.length === key + 1) {
	          main_core.Dom.addClass(blockLayout, babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockLoading);
	        }
	      } else {
	        main_core.Dom.removeClass(blockLayout, babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockLoading);
	      }
	    });
	  }

	  /**
	   * Assigns block to the document (without saving).
	   */
	  assign() {
	    const blockLayout = this.getLayout();
	    const blocksData = [];
	    const blocksInstance = [];
	    this.await(true);
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].isSingleton()) {
	      blocksData.push({
	        code: babelHelpers.classPrivateFieldLooseBase(this, _code)[_code],
	        part: babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart],
	        data: this.getData()
	      });
	      blocksInstance.push(this);
	    }
	    // if block is a singleton push all blocks with same code
	    else {
	      this.blocksManager.getBlocks().map(block => {
	        if (block.getCode() === this.getCode() && block.getMemberPart() === this.getMemberPart()) {
	          blocksData.push({
	            code: block.getCode(),
	            part: block.getMemberPart(),
	            data: block.getData()
	          });
	          blocksInstance.push(block);
	        }
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].loadBlocksData(this.blocksManager.getDocumentUid(), blocksData).then(result => {
	      if (main_core.Type.isArray(result)) {
	        result.forEach((block, i) => {
	          blocksInstance[i].setData(block.data);
	        });
	      }
	      this.await(false);
	      this.blocksManager.showResizeArea(this);
	    }).catch(response => {
	      main_core.Dom.remove(blockLayout);
	    });
	  }

	  /**
	   * Renders block within document's layout.
	   */
	  renderView() {
	    const contentTag = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].querySelector(babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockContent);

	    // content
	    main_core.Dom.clean(contentTag);
	    switch (babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase()) {
	      case 'stamp':
	      case 'mystamp':
	      case 'sign':
	      case 'mysign':
	        main_core.Dom.addClass(contentTag, '--image');
	    }
	    const resizeNode = babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getViewContent();
	    main_core.Dom.append(resizeNode, contentTag);
	    main_core.Dom.addClass(resizeNode, '--' + babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase());
	    if (babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'requisites' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'myrequisites' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'date' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'number' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'stamp' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'mystamp' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'sign' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'mysign' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'reference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'myreference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'text' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'b2ereference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'myb2ereference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'employeedynamic' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'hcmlinkreference') {
	      resizeNode.style.setProperty('display', 'block');
	      resizeNode.style.setProperty('overflow', 'hidden');
	      if (!this.observerReady) {
	        if (this.getStyle()['fontSize']) {
	          this.maxTextSize = parseFloat(this.getStyle()['fontSize']);
	        } else {
	          this.maxTextSize = 14;
	        }
	        this.isOverflownX = ({
	          clientHeight,
	          scrollHeight
	        }) => {
	          return scrollHeight > clientHeight;
	        };
	        main_core_events.EventEmitter.subscribe(resizeNode.parentNode.parentNode, 'BX.Sign:setFontSize', param => {
	          if (param.data.fontSize) {
	            this.maxTextSize = param.data.fontSize;
	            this.resizeText({
	              element: param.target.querySelector('.sign-document__block-content > div'),
	              step: 0.5
	            });
	          }
	        });
	        this.resizeText = ({
	          element,
	          minSize = 1,
	          step = 1,
	          unit = 'px'
	        }) => {
	          if (this.intervalTextResize) {
	            clearTimeout(this.intervalTextResize);
	          }
	          let i = minSize;
	          let overflow = false;
	          const parent = element.parentNode;
	          while (!overflow && i <= this.maxTextSize) {
	            element.style.fontSize = `${i}${unit}`;
	            overflow = this.isOverflownX(parent);
	            i += step;
	          }
	          babelHelpers.classPrivateFieldLooseBase(this, _currentFontSize)[_currentFontSize] = `${i - step}${unit}`;
	          element.style.fontSize = babelHelpers.classPrivateFieldLooseBase(this, _currentFontSize)[_currentFontSize];
	          this.intervalTextResize = setTimeout(() => {
	            element.parentNode.style.setProperty('font-size', element.style.fontSize);
	            element.style.removeProperty('font-size', element.style.fontSize);
	            babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].updateFontSize(babelHelpers.classPrivateFieldLooseBase(this, _currentFontSize)[_currentFontSize]);
	          }, 1000);
	        };
	        if (babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'requisites' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'myrequisites' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'reference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'myreference' || babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase() === 'text') {
	          this.resizeText({
	            element: resizeNode,
	            step: 0.5
	          });
	        }
	        babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].events.onChange, babelHelpers.classPrivateFieldLooseBase(this, _onContentChange)[_onContentChange].bind(this));
	        babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].subscribe(babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].events.onColorStyleChange, babelHelpers.classPrivateFieldLooseBase(this, _onContentColorStyleChange)[_onContentColorStyleChange].bind(this));
	        babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].subscribe('onFieldSelect', babelHelpers.classPrivateFieldLooseBase(this, _onFieldSelect)[_onFieldSelect].bind(this));
	        this.observerReady = true;
	      }
	      if (babelHelpers.classPrivateFieldLooseBase(this, _firstRenderReady)[_firstRenderReady]) {
	        this.resizeText({
	          element: resizeNode,
	          step: 0.5
	        });
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _firstRenderReady)[_firstRenderReady] = true;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _panelCreated)[_panelCreated]) {
	      return;
	    }

	    // action / style panel
	    const panelTag = babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].querySelector(babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockPanel);
	    main_core.Dom.clean(panelTag);

	    // style
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].isStyleAllowed()) {
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].getLayout(), panelTag);
	    }

	    // action
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getActionButton(), panelTag);

	    // block caption
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getBlockCaption(), panelTag);

	    // member selector
	    if (babelHelpers.classPrivateFieldLooseBase(this, _allowMembers)[_allowMembers]) {
	      if (['hcmlinkreference', 'b2ereference'].includes(babelHelpers.classPrivateFieldLooseBase(this, _code)[_code].toLowerCase())) {
	        return;
	      }
	      const allMembers = this.blocksManager.getMembers();
	      const selectedMembers = allMembers.filter(member => {
	        return member.part > 1;
	      });
	      main_core.Dom.append(UI$1.getMemberSelector(selectedMembers, babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart], this.onMemberSelect.bind(this)), panelTag);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _panelCreated)[_panelCreated] = true;
	  }
	  getCurrentFontSize() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _currentFontSize)[_currentFontSize];
	  }

	  /**
	   * Calls when block starts being resized or moved.
	   */
	  onStartChangePosition() {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].onStartChangePosition();
	  }

	  /**
	   * Calls when block has placed on document.
	   */
	  onPlaced() {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].onPlaced();
	  }
	  updateColor(color, emitEvent = true) {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].changeStyleColor(color, emitEvent);
	    babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].updateColor(color);
	  }

	  /**
	   * Calls when block saved.
	   */
	  onSave() {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].onSave();
	  }

	  /**
	   * Calls when block removed.
	   */
	  onRemove() {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].onRemove();
	  }

	  /**
	   * Calls when click was out the block.
	   */
	  onClickOut() {
	    babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].onClickOut();
	  }

	  /**
	   * Set block styles to layout.
	   */
	  renderStyle() {
	    babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].applyStyles(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].querySelector(babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockContent));
	  }

	  /**
	   * Adjust actions panel.
	   */
	  adjustActionsPanel() {
	    const blockLayout = this.getLayout();
	    const documentLayout = this.blocksManager.getLayout().parentElement;
	    const documentLayoutRect = documentLayout.getBoundingClientRect();
	    const actionsPanel = blockLayout.querySelector('[data-role="sign-block__actions"]');
	    if (actionsPanel) {
	      const actionsPanelRect = actionsPanel.getBoundingClientRect();
	      const marginLeft = parseFloat(getComputedStyle(actionsPanel).marginLeft);
	      if (documentLayoutRect.x >= actionsPanelRect.x) {
	        actionsPanel.style.marginLeft = documentLayoutRect.x - actionsPanelRect.x + marginLeft + 'px';
	      } else {
	        actionsPanel.style.marginLeft = 0;
	      }
	    }
	  }

	  /**
	   * Force saves the block.
	   */
	  forceSave() {
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].querySelector('[data-role="saveAction"]').click();
	  }

	  /**
	   * Creates layout for new block within document.
	   */

	  remove() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _onRemoveCallback)[_onRemoveCallback]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onRemoveCallback)[_onRemoveCallback](this);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hidden = true;
	    this.onRemove();
	  }
	  /**
	   * Returns true, if block was removed.
	   * @return {boolean}
	   */
	  isRemoved() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout].hidden === true;
	  }
	  setMemberParty(party) {
	    babelHelpers.classPrivateFieldLooseBase(this, _memberPart)[_memberPart] = party;
	  }
	}
	function _onClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _onClickCallback)[_onClickCallback]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickCallback)[_onClickCallback](this);
	  }
	}
	function _createLayout2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout] = UI$1.getBlockLayout({
	    onSave: event => {
	      this.renderView();
	      this.blocksManager.unMuteResizeArea();
	      this.blocksManager.hideResizeArea();
	      this.blocksManager.setSavingMark(false);
	      this.onSave();
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _layout)[_layout], babelHelpers.classPrivateFieldLooseBase(this, _style$1)[_style$1].blockEditing);
	      event.stopPropagation();
	    },
	    onRemove: babelHelpers.classPrivateFieldLooseBase(this, _onRemoveBtnClick)[_onRemoveBtnClick].bind(this)
	  });
	  const nodeForPosition = document.body.querySelector('.sign-editor__content');
	  const documentLayout = this.blocksManager.getLayout();
	  const documentLayoutRect = documentLayout.getBoundingClientRect();
	  const blockInitDim = babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getInitDimension();
	  const position = {
	    top: Math.min(nodeForPosition.scrollTop + nodeForPosition.offsetHeight / 2, documentLayoutRect.height - blockInitDim.height),
	    left: documentLayoutRect.width / 2 - 100
	  };
	  if (this.blocksManager.inDeadZone(position.top, position.top + blockInitDim.height)) {
	    position.top += blockInitDim.height + this.blocksManager.getPagesGap();
	  }
	  this.setPosition(position);
	}
	function _onRemoveBtnClick2(event) {
	  this.remove();
	  event.stopPropagation();
	}
	function _onContentChange2() {
	  const content = babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4];
	  if (!(content instanceof Text)) {
	    return;
	  }
	  this.resizeText({
	    element: content.getContainer(),
	    step: 0.5
	  });
	}
	function _onContentColorStyleChange2(event) {
	  var _babelHelpers$classPr2, _babelHelpers$classPr3, _babelHelpers$classPr4, _babelHelpers$classPr5;
	  this.emit(this.events.onColorStyleChange, {
	    color: (_babelHelpers$classPr2 = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getStyles()) == null ? void 0 : _babelHelpers$classPr3.color) != null ? _babelHelpers$classPr2 : null
	  });
	  const color = (_babelHelpers$classPr4 = (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _content$4)[_content$4].getStyles()) == null ? void 0 : _babelHelpers$classPr5.color) != null ? _babelHelpers$classPr4 : null;
	  if (main_core.Type.isString(color)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _stylePanel)[_stylePanel].updateColor(color);
	  }
	}
	function _onFieldSelect2(event) {
	  this.emit('onFieldSelect', event);
	}

	var _wrapperLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wrapperLayout");
	var _layout$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("layout");
	var _fullEditorContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fullEditorContent");
	var _linkedElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkedElement");
	var _linkedBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkedBlock");
	var _style$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("style");
	var _onClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClick");
	var _initResize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initResize");
	var _initMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initMove");
	class Resize {
	  /**
	   * Constructor.
	   * @param {ResizeOptions} options
	   */
	  constructor(options) {
	    Object.defineProperty(this, _initMove, {
	      value: _initMove2
	    });
	    Object.defineProperty(this, _initResize, {
	      value: _initResize2
	    });
	    Object.defineProperty(this, _onClick$1, {
	      value: _onClick2$1
	    });
	    Object.defineProperty(this, _wrapperLayout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _layout$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fullEditorContent, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _linkedElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _linkedBlock, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _style$2, {
	      writable: true,
	      value: {
	        resizeContainer: '.sign-area-resizable-controls > div',
	        moveContainer: '.sign-document__resize-area',
	        blockEditing: 'sign-document__block-wrapper-editing'
	      }
	    });
	    if (!main_core.Type.isDomNode(options.wrapperLayout)) {
	      throw new Error('Option wrapperLayout is undefined or not valid DOM Element.');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1] = UI$1.getResizeArea();
	    babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout] = options.wrapperLayout;
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1], 'click', babelHelpers.classPrivateFieldLooseBase(this, _onClick$1)[_onClick$1].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _initResize)[_initResize]();
	    babelHelpers.classPrivateFieldLooseBase(this, _initMove)[_initMove]();
	  }

	  /**
	   * Returns layout of resizing area.
	   * @return {HTMLElement}
	   */
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1];
	  }

	  /**
	   * Shows resizing area over the element.
	   * @param {Block} block
	   */
	  show(block) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock] === block) {
	      return;
	    }
	    const pointRect = block.getLayout().getBoundingClientRect();
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].style.display = 'block';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement].removeAttribute('data-active');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock] = block;
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement] = block.getLayout();
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement].setAttribute('data-active', 1);
	    const wrapperLayoutRect = babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout].getBoundingClientRect();
	    UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1], {
	      top: pointRect.top - wrapperLayoutRect.top,
	      left: pointRect.left - wrapperLayoutRect.left,
	      width: pointRect.width + Resize.borderDelta,
	      height: pointRect.height + Resize.borderDelta
	    });
	  }
	  setFullEditorContent(editorContent) {
	    babelHelpers.classPrivateFieldLooseBase(this, _fullEditorContent)[_fullEditorContent] = editorContent;
	  }

	  /**
	   * Hides resizing area.
	   */
	  hide() {
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].style.display = 'none';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement].removeAttribute('data-active');
	    }
	  }

	  /**
	   * Returns linked block.
	   * @return {Block|null}
	   */
	  getLinkedBlock() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock];
	  }

	  /**
	   * On click handler (provide click to linked element).
	   */
	}
	function _onClick2$1(e) {
	  if (this.moving || this.resizing) {
	    return;
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].fireAction();
	  }
	}
	function _initResize2() {
	  let initialRect = null;
	  const draggable = new ui_draganddrop_draggable.Draggable({
	    container: babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout],
	    draggable: babelHelpers.classPrivateFieldLooseBase(this, _style$2)[_style$2].resizeContainer,
	    type: BX.UI.DragAndDrop.Draggable.HEADLESS
	  });
	  this.textResize = {
	    wrapper: null,
	    content: null
	  };
	  draggable.subscribe('start', event => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getAttribute('data-disable')) {
	      return;
	    }
	    if (!this.textResize.wrapper || !this.textResize.content) {
	      this.textResize = {
	        wrapper: babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].getLayout(),
	        content: babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].getLayout().querySelector('.sign-document__block-content')
	      };
	    }
	    main_core_events.EventEmitter.emit('BX.Sign:resizeStart', draggable);
	    initialRect = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getBoundingClientRect();
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].onStartChangePosition();
	    this.positionLast = null;
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _style$2)[_style$2].blockEditing);
	  }).subscribe('end', event => {
	    setTimeout(() => {
	      this.resizing = false;
	    }, 0);
	  }).subscribe('move', event => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getAttribute('data-disable')) {
	      return;
	    }
	    this.resizing = true;
	    let left = null;
	    let top = null;
	    let bottomResize = false;
	    let {
	      width,
	      height
	    } = initialRect;
	    const data = event.getData();
	    const areaRect = babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getBoundingClientRect();
	    const wrapperRect = babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout].getBoundingClientRect();
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --left-top')) {
	      left = Math.max(0, initialRect.left + data.offsetX - wrapperRect.left);
	      top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
	      width = initialRect.width - data.offsetX;
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-top')) {
	      top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --right-top')) {
	      top = Math.max(0, initialRect.top + data.offsetY - wrapperRect.top);
	      width = initialRect.width + data.offsetX;
	      height = initialRect.height - data.offsetY;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-right')) {
	      width = initialRect.width + data.offsetX;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --right-bottom')) {
	      width = initialRect.width + data.offsetX;
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-bottom')) {
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --left-bottom')) {
	      left = initialRect.left + data.offsetX - wrapperRect.left;
	      width = initialRect.width - data.offsetX;
	      height = initialRect.height + data.offsetY;
	      bottomResize = true;
	    }
	    if (main_core.Dom.hasClass(data.draggable, 'sign-document__resize-control --middle-left')) {
	      left = initialRect.left + data.offsetX - wrapperRect.left;
	      width = initialRect.width - data.offsetX;
	    }
	    if (width < 60 || height < 20) {
	      return;
	    }
	    const newPosition = {
	      width,
	      height
	    };
	    if (newPosition['width'] + areaRect.left - wrapperRect.left > wrapperRect.width) {
	      width = newPosition['width'] = wrapperRect.width + wrapperRect.left - areaRect.left;
	    }
	    if (newPosition['height'] + areaRect.top - wrapperRect.top > wrapperRect.height) {
	      height = newPosition['height'] = wrapperRect.height + wrapperRect.top - areaRect.top;
	    }
	    if (left) {
	      if (left < 0) {
	        left = 0;
	      }
	      newPosition['left'] = left;
	    }
	    if (top) {
	      newPosition['top'] = top;
	    }
	    let calcDeathTop = initialRect.top;
	    let notAddDeathMargin = true;
	    if (newPosition.top) {
	      calcDeathTop = newPosition.top;
	      notAddDeathMargin = false;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].blocksManager.inDeadZone(calcDeathTop, calcDeathTop + newPosition.height, notAddDeathMargin)) {
	      if (!bottomResize) {
	        babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].showNotAllowedArea();
	      }
	      return;
	    }
	    const newPositionLinked = Object.assign({}, {
	      ...newPosition,
	      width: width - Resize.borderDelta,
	      height: height - Resize.borderDelta
	    });
	    UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1], newPosition);
	    UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement], newPositionLinked);
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].renderView();
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].blocksManager.setSavingMark(false);
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].adjustActionsPanel();
	  });
	}
	function _initMove2() {
	  const dragArea = babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout];
	  let widthInProcess;
	  const draggable = new ui_draganddrop_draggable.Draggable({
	    container: dragArea,
	    draggable: babelHelpers.classPrivateFieldLooseBase(this, _style$2)[_style$2].moveContainer,
	    type: ui_draganddrop_draggable.Draggable.HEADLESS
	  });
	  draggable.subscribe('start', event => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getAttribute('data-disable')) {
	      return;
	    }
	    main_core_events.EventEmitter.emit('BX.Sign:moveStart', draggable);
	    if (this.resizing) {
	      return;
	    }
	    const {
	      source
	    } = event.getData();
	    this.position = main_core.Dom.getPosition(source);
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].onStartChangePosition();
	    this.positionLast = null;
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _style$2)[_style$2].blockEditing);
	  }).subscribe('end', event => {
	    setTimeout(() => {
	      this.moving = false;
	    }, 0);
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock]) {
	      return;
	    }
	    if (this.resizing) {
	      return;
	    }
	    widthInProcess = null;
	    const data = event.getData();
	    let moveTopDelta = null;
	    if (this.positionLast) {
	      moveTopDelta = babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].blocksManager.inDeadZone(this.positionLast.top, this.positionLast.top + this.positionLast.height);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].hideNotAllowedArea();
	    if (moveTopDelta) {
	      UI$1.setRect(data.source, {
	        top: this.positionLast.top + moveTopDelta
	      });
	      UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement], {
	        top: this.positionLast.top + moveTopDelta
	      });
	    }
	  }).subscribe('move', event => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _layout$1)[_layout$1].getAttribute('data-disable')) {
	      return;
	    }
	    if (this.resizing) {
	      return;
	    }
	    this.moving = true;
	    const data = event.getData();
	    const {
	      source,
	      offsetY,
	      offsetX
	    } = data;
	    const areaRect = data.source.getBoundingClientRect();
	    const wrapperRect = babelHelpers.classPrivateFieldLooseBase(this, _wrapperLayout)[_wrapperLayout].getBoundingClientRect();
	    if (this.position) {
	      const newPosition = {
	        top: offsetY - wrapperRect.top + this.position.y,
	        left: offsetX - wrapperRect.left + this.position.x,
	        width: widthInProcess ? widthInProcess : this.position.width,
	        height: this.position.height
	      };
	      if (newPosition.left < 0) {
	        newPosition.left = 0;
	      }
	      if (newPosition.top < 0) {
	        newPosition.top = 0;
	      }
	      if (newPosition.top + areaRect.height > wrapperRect.height) {
	        newPosition.top = wrapperRect.height - areaRect.height;
	      }
	      if (newPosition.left + areaRect.width > wrapperRect.width) {
	        babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].renderView();
	        if (wrapperRect.width - newPosition.left > 50) {
	          widthInProcess = newPosition.width = wrapperRect.width - newPosition.left;
	        } else {
	          newPosition.left = wrapperRect.width - areaRect.width;
	        }
	      }
	      this.positionLast = Object.assign({}, newPosition);
	      let moveTopDelta = null;
	      if (this.positionLast) {
	        moveTopDelta = babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].blocksManager.inDeadZone(this.positionLast.top, this.positionLast.top + this.positionLast.height);
	      }
	      if (moveTopDelta > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].showNotAllowedArea();
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].hideNotAllowedArea();
	      }
	      const newPositionLinked = Object.assign({}, {
	        ...newPosition,
	        width: newPosition.width - Resize.borderDelta,
	        height: newPosition.height - Resize.borderDelta
	      });
	      UI$1.setRect(source, newPosition);
	      UI$1.setRect(babelHelpers.classPrivateFieldLooseBase(this, _linkedElement)[_linkedElement], newPositionLinked);
	      babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].blocksManager.setSavingMark(false);
	      babelHelpers.classPrivateFieldLooseBase(this, _linkedBlock)[_linkedBlock].adjustActionsPanel();
	    }
	  });
	}
	Resize.borderDelta = 2;

	const marginTop = 0;
	const gapBetweenPages = 20;
	var _documentId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentId");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _companyId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("companyId");
	var _initiatorName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initiatorName");
	var _entityId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("entityId");
	var _blankId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blankId");
	var _pagesMinHeight = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pagesMinHeight");
	var _disableEdit = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableEdit");
	var _closeDemoContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeDemoContent");
	var _members = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("members");
	var _documentLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentLayout");
	var _languages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("languages");
	var _blocks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blocks");
	var _eventListeners = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("eventListeners");
	var _pagesRects = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pagesRects");
	var _seams = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("seams");
	var _config = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("config");
	var _helpBtnElementId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("helpBtnElementId");
	var _helpBtnHelperArticleCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("helpBtnHelperArticleCode");
	var _api$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _lastContainerSize = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastContainerSize");
	var _cache$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _pixelToPercent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pixelToPercent");
	var _percentToPixel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("percentToPixel");
	var _addBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addBlock");
	var _subscribeOnBlockEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("subscribeOnBlockEvents");
	var _onRepositoryItemClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onRepositoryItemClick");
	var _startDisabledEditDocumentTour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startDisabledEditDocumentTour");
	var _startEditDocumentTour = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("startEditDocumentTour");
	var _highlightInvalidBlocks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("highlightInvalidBlocks");
	var _onBlockColorStyleChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBlockColorStyleChange");
	var _onReferenceBlockFieldSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReferenceBlockFieldSelect");
	var _onB2eReferenceBlockFieldSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onB2eReferenceBlockFieldSelect");
	var _onBlockDeleteBecauseFieldNotSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onBlockDeleteBecauseFieldNotSelected");
	var _onB2eReferenceBlockDeleteBecauseFieldNotSelected = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onB2eReferenceBlockDeleteBecauseFieldNotSelected");
	var _unsubscribeFromBlocksEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unsubscribeFromBlocksEvents");
	class BlocksManager {
	  /**
	   * Constructor.
	   * @param {DocumentOptions} options
	   */
	  constructor(options) {
	    Object.defineProperty(this, _unsubscribeFromBlocksEvents, {
	      value: _unsubscribeFromBlocksEvents2
	    });
	    Object.defineProperty(this, _onB2eReferenceBlockDeleteBecauseFieldNotSelected, {
	      value: _onB2eReferenceBlockDeleteBecauseFieldNotSelected2
	    });
	    Object.defineProperty(this, _onBlockDeleteBecauseFieldNotSelected, {
	      value: _onBlockDeleteBecauseFieldNotSelected2
	    });
	    Object.defineProperty(this, _onB2eReferenceBlockFieldSelect, {
	      value: _onB2eReferenceBlockFieldSelect2
	    });
	    Object.defineProperty(this, _onReferenceBlockFieldSelect, {
	      value: _onReferenceBlockFieldSelect2
	    });
	    Object.defineProperty(this, _onBlockColorStyleChange, {
	      value: _onBlockColorStyleChange2
	    });
	    Object.defineProperty(this, _highlightInvalidBlocks, {
	      value: _highlightInvalidBlocks2
	    });
	    Object.defineProperty(this, _startEditDocumentTour, {
	      value: _startEditDocumentTour2
	    });
	    Object.defineProperty(this, _startDisabledEditDocumentTour, {
	      value: _startDisabledEditDocumentTour2
	    });
	    Object.defineProperty(this, _onRepositoryItemClick, {
	      value: _onRepositoryItemClick2
	    });
	    Object.defineProperty(this, _subscribeOnBlockEvents, {
	      value: _subscribeOnBlockEvents2
	    });
	    Object.defineProperty(this, _addBlock, {
	      value: _addBlock2
	    });
	    Object.defineProperty(this, _percentToPixel, {
	      value: _percentToPixel2
	    });
	    Object.defineProperty(this, _pixelToPercent, {
	      value: _pixelToPercent2
	    });
	    Object.defineProperty(this, _documentId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _companyId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initiatorName, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _entityId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _blankId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pagesMinHeight, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _disableEdit, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _closeDemoContent, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _members, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentLayout, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _languages, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _blocks, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _eventListeners, {
	      writable: true,
	      value: {
	        blockColorChange: new Map(),
	        blockReferenceFieldSelect: new Map(),
	        blockB2eReferenceFieldSelect: new Map()
	      }
	    });
	    Object.defineProperty(this, _pagesRects, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _seams, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _helpBtnElementId, {
	      writable: true,
	      value: 'sign-editor-help-btn'
	    });
	    Object.defineProperty(this, _helpBtnHelperArticleCode, {
	      writable: true,
	      value: '16571388'
	    });
	    Object.defineProperty(this, _api$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _lastContainerSize, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _cache$9, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    this.isTemplateMode = false;
	    this.documentInitiatedByType = null;
	    const {
	      documentLayout,
	      disableEdit,
	      isTemplateMode,
	      documentInitiatedByType
	    } = options;
	    this.isTemplateMode = Boolean(isTemplateMode);
	    this.documentInitiatedByType = documentInitiatedByType;
	    babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout] = documentLayout;
	    babelHelpers.classPrivateFieldLooseBase(this, _disableEdit)[_disableEdit] = disableEdit;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _disableEdit)[_disableEdit]) {
	      this.resizeArea = new Resize({
	        wrapperLayout: babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout]
	      });
	      main_core.Dom.append(this.resizeArea.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout]);
	    }
	    main_core.Event.bind(documentLayout, 'click', ({
	      target
	    }) => {
	      const preventClickOut = target.closest('.sign-document__block-wrapper') || target.closest('.sign-document__resize-area');
	      if (preventClickOut) {
	        return;
	      }
	      const activeBlock = this.resizeArea.getLinkedBlock();
	      activeBlock == null ? void 0 : activeBlock.onClickOut();
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _languages)[_languages] = options.languages;
	    babelHelpers.classPrivateFieldLooseBase(this, _api$2)[_api$2] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _lastContainerSize)[_lastContainerSize] = {
	      width: 0,
	      height: 0
	    };
	  }
	  initPagesRect() {
	    let top = 0;
	    const pages = [...babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout].querySelectorAll('.sign-editor__document_page img')];
	    babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _seams)[_seams] = [];
	    const content = document.querySelector('.sign-editor__content');
	    pages.forEach(page => {
	      const pageRect = page.getBoundingClientRect();
	      const pageRectToMath = {};

	      // round getBoundingClientRect
	      for (let pageRectKey in pageRect) {
	        if (main_core.Type.isNumber(pageRect[pageRectKey])) {
	          pageRectToMath[pageRectKey] = Math.round(pageRect[pageRectKey]);
	        } else {
	          pageRectToMath[pageRectKey] = pageRect[pageRectKey];
	        }
	      }
	      const topBound = top === 0 ? 0 : pageRectToMath.top - gapBetweenPages + content.scrollTop;
	      const bottomBound = pageRectToMath.top + marginTop + content.scrollTop;
	      // collect seam's gaps
	      babelHelpers.classPrivateFieldLooseBase(this, _seams)[_seams].push([topBound, bottomBound]);

	      // correct top after rounding
	      if (top === 0) {
	        top = pageRectToMath.top;
	      } else {
	        top += gapBetweenPages;
	        pageRectToMath.top = top;
	      }

	      // collect pages rects
	      babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects].push(pageRectToMath);

	      // remember min page height
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _pagesMinHeight)[_pagesMinHeight] || pageRect.height < babelHelpers.classPrivateFieldLooseBase(this, _pagesMinHeight)[_pagesMinHeight]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _pagesMinHeight)[_pagesMinHeight] = pageRect.height;
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout].style.width = `${babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout].offsetWidth}px`;
	  }

	  /**
	   * Transfers absolute position to page position and sets page number.
	   * @param {PositionType} position
	   * @return {PositionType}
	   */
	  transferPositionToPage(position) {
	    position.page = 1;
	    for (let i = 0, c = babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects].length; i < c; i++) {
	      const height = babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects][i].height;
	      if (i !== 0)
	        // skip gap for first page
	        {
	          position.top -= gapBetweenPages;
	        }
	      position.top -= height;
	      if (position.top < 0) {
	        position.top += height;
	        break;
	      } else {
	        position.page++;
	      }
	    }
	    return position;
	  }

	  /**
	   * Transfers pixel of position to percent.
	   * @param {PositionType} position
	   * @return {PositionType}
	   */

	  /**
	   * Returns minimal pages height.
	   * @return {number}
	   */
	  getMinPageHeight() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _pagesMinHeight)[_pagesMinHeight] - marginTop;
	  }

	  /**
	   * Returns document id.
	   * @return {number}
	   */
	  getDocumentId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _documentId)[_documentId];
	  }
	  setDocumentId(documentId) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentId)[_documentId] = documentId;
	    return this;
	  }
	  getDocumentUid() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid];
	  }
	  setEditorContent(editorContent) {
	    this.resizeArea.setFullEditorContent(editorContent);
	  }
	  setDocumentUid(documentUid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = documentUid;
	    return this;
	  }

	  /**
	   * Returns document entity id.
	   * @return {number}
	   */
	  getEntityId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _entityId)[_entityId];
	  }

	  /**
	   * Returns document's layout.
	   * @return {HTMLElement}
	   */
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout];
	  }

	  /**
	   * Returns number pairs (seams between pages).
	   * @return {Array<Array<number, number>>}
	   */
	  getSeams() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _seams)[_seams];
	  }

	  /**
	   * Returns gap between pages.
	   * @return {number}
	   */
	  getPagesGap() {
	    return gapBetweenPages + marginTop;
	  }
	  addMembers(members) {
	    babelHelpers.classPrivateFieldLooseBase(this, _members)[_members] = members;
	  }

	  /**
	   * Returns document's members
	   * @return {Array<MemberItem>}
	   */
	  getMembers() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _members)[_members];
	  }

	  /**
	   * Returns member item by member part.
	   * @param {number} part
	   * @return {null}
	   */
	  getMemberByPart(part) {
	    let returnMember = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _members)[_members].map(member => {
	      if (member.part === part) {
	        returnMember = member;
	      }
	    });
	    return returnMember;
	  }

	  /**
	   * Returns company id.
	   * @return {number}
	   */
	  getCompanyId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _companyId)[_companyId];
	  }

	  /**
	   * @return {string}
	   */
	  getInitiatorName() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _initiatorName)[_initiatorName];
	  }

	  /**
	   * Returns Config object.
	   * @return {Config}
	   */
	  getConfig() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _config)[_config];
	  }

	  /**
	   * Hides resizing area.
	   */
	  hideResizeArea() {
	    this.resizeArea.hide();
	  }

	  /**
	   * Shows resizing area over the block.
	   * @param {Block} block
	   */
	  showResizeArea(block) {
	    this.resizeArea.show(block);
	  }

	  /**
	   * Mutes resizing area.
	   */
	  muteResizeArea() {
	    this.resizeArea.getLayout().setAttribute('data-disable', 1);
	  }

	  /**
	   * Unmutes resizing area.
	   */
	  unMuteResizeArea() {
	    this.resizeArea.getLayout().removeAttribute('data-disable');
	  }

	  /**
	   * Adds block to the document.
	   * @param {BlockItem} data Block data.
	   * @param {boolean} seamShift If true, will be added seam shift to top's position.
	   * @return {Block}
	   */

	  /**
	   * Sets exists blocks to the document.
	   * @param blocks
	   */
	  initBlocks(blocks) {
	    babelHelpers.classPrivateFieldLooseBase(this, _unsubscribeFromBlocksEvents)[_unsubscribeFromBlocksEvents]();
	    babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks] = [];
	    this.hideResizeArea();
	    blocks.map(block => babelHelpers.classPrivateFieldLooseBase(this, _addBlock)[_addBlock](block, true));
	  }
	  initRepository(repositoryItems) {
	    [...repositoryItems].forEach(item => {
	      const code = item.getAttribute('data-code');
	      const part = item.getAttribute('data-part');
	      main_core.Event.bind(item, 'click', e => {
	        babelHelpers.classPrivateFieldLooseBase(this, _onRepositoryItemClick)[_onRepositoryItemClick](code, part);
	        e.preventDefault();
	      });
	    });
	  }

	  /**
	   * Returns blocks collection.
	   * @return {Array<Block>}
	   */
	  getBlocks() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks];
	  }
	  getLanguages() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _languages)[_languages];
	  }

	  /**
	   * Saves document and closes slider.
	   * @return {Promise}
	   */
	  save() {
	    const postData = [];
	    const realDocumentWidth = document.querySelector('.sign-editor__document').offsetWidth;
	    babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].map(block => {
	      if (block.isRemoved()) {
	        return;
	      }
	      const blockLayout = block.getLayout();
	      const blockRect = blockLayout.getBoundingClientRect();
	      const pages = [...babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout].querySelectorAll('.sign-editor__document_page img')];
	      const pagesRect = pages.map(page => {
	        const {
	          top,
	          left,
	          width,
	          height
	        } = page.getBoundingClientRect();
	        return {
	          top,
	          left,
	          width,
	          height
	        };
	      });
	      const nextPageRect = pagesRect.find(pageRect => {
	        if (Math.round(blockRect.top) < Math.round(pageRect.top)) {
	          return true;
	        }
	        return false;
	      });
	      const pageNum = nextPageRect ? pagesRect.indexOf(nextPageRect) : pagesRect.length;
	      const currentPageRect = pagesRect[pageNum - 1];
	      let position = {
	        top: blockRect.top - currentPageRect.top,
	        left: blockRect.left - currentPageRect.left,
	        width: blockRect.width,
	        height: blockRect.height,
	        page: pageNum
	      };
	      position = babelHelpers.classPrivateFieldLooseBase(this, _pixelToPercent)[_pixelToPercent](position, currentPageRect);
	      position.realDocumentWidthPx = realDocumentWidth ? realDocumentWidth : null;
	      const code = block.getCode();
	      let type = 'text';
	      switch (code) {
	        case 'stamp':
	        case 'mystamp':
	        case 'sign':
	        case 'mysign':
	          type = 'image';
	      }
	      postData.push({
	        id: block.getId(),
	        code: block.getCode(),
	        data: block.getData(),
	        party: block.getMemberPart(),
	        style: block.getStyle(),
	        position,
	        type
	      });
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _closeDemoContent)[_closeDemoContent]) ;
	    this.hideResizeArea();
	    return babelHelpers.classPrivateFieldLooseBase(this, _api$2)[_api$2].saveBlank(this.getDocumentUid(), postData.length > 0 ? postData : []).then(response => {
	      var _response$errors$0$cu;
	      if (!('errors' in response)) {
	        return postData;
	      }
	      if (((_response$errors$0$cu = response.errors[0].customData) == null ? void 0 : _response$errors$0$cu.field) === 'requisites') {
	        var _response$errors$;
	        const popup = new main_popup.Popup({
	          id: 'sign_document_error_popup',
	          titleBar: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_TITLE'),
	          content: BX.util.htmlspecialchars((_response$errors$ = response.errors[0]) == null ? void 0 : _response$errors$.message),
	          buttons: [new ui_buttons.Button({
	            text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE'),
	            color: ui_buttons.ButtonColor.SUCCESS,
	            onclick: ({
	              button
	            }) => {
	              var _response$errors$2, _response$errors$3;
	              const target = button;
	              main_core.Dom.addClass(target, 'ui-btn-wait');
	              sign_backend.Backend.controller({
	                command: 'document.restoreRequisiteFields',
	                postData: {
	                  documentId: babelHelpers.classPrivateFieldLooseBase(this, _documentId)[_documentId],
	                  presetId: (_response$errors$2 = response.errors[0]) == null ? void 0 : _response$errors$2.customData.presetId,
	                  code: (_response$errors$3 = response.errors[0]) == null ? void 0 : _response$errors$3.customData.code
	                }
	              }).then(() => {
	                main_core.Dom.removeClass(target, 'ui-btn-wait');
	                babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].map(block => {
	                  var _response$errors$4;
	                  if (block.isRemoved()) {
	                    return;
	                  }
	                  if (block.getCode() === ((_response$errors$4 = response.errors[0]) == null ? void 0 : _response$errors$4.customData.code)) {
	                    block.assign();
	                  }
	                });
	                popup.destroy();
	              }).catch(() => {
	                popup.destroy();
	              });
	            }
	          }), new ui_buttons.Button({
	            text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_REQUISITES_RESTORE_BY_HANDS'),
	            color: ui_buttons.ButtonColor.INFO,
	            onclick: () => {
	              babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].map(block => {
	                var _response$errors$5;
	                if (block.isRemoved()) {
	                  return;
	                }
	                if (block.getCode() === ((_response$errors$5 = response.errors[0]) == null ? void 0 : _response$errors$5.customData.code)) {
	                  const blockActionButton = document.querySelector(`button[data-id="action-${block.getCode()}]`);
	                  if (blockActionButton) {
	                    blockActionButton.click();
	                  }
	                }
	              });
	              popup.destroy();
	            }
	          })]
	        });
	        popup.show();
	        return null;
	      }
	      const firstError = response.errors[0];
	      if (firstError.code === 'REFERENCE_ERROR_FIELD_NOT_SELECTED') {
	        babelHelpers.classPrivateFieldLooseBase(this, _highlightInvalidBlocks)[_highlightInvalidBlocks]();
	        const popup = new main_popup.Popup({
	          id: 'sign_document_error_popup',
	          titleBar: main_core.Loc.getMessage('SIGN_EDITOR_ERROR_SAVE_REFERENCE_BACK_TITLE'),
	          content: BX.util.htmlspecialchars(firstError.message),
	          buttons: [new ui_buttons.Button({
	            text: main_core.Loc.getMessage('SIGN_EDITOR_ERROR_SAVE_REFERENCE_BACK'),
	            color: ui_buttons.ButtonColor.PRIMARY,
	            onclick: () => {
	              popup.destroy();
	            }
	          })]
	        });
	        main_core_events.EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', () => {
	          popup.destroy();
	        });
	        popup.show();
	        return null;
	      }
	      console.error(firstError.message);
	      return postData;
	    });
	  }

	  /**
	   * Registers save button.
	   * @param {DocumentOptions} options
	   */
	  onSave(options) {
	    main_core.Event.bind(options.saveButton, 'click', e => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _disableEdit)[_disableEdit]) {
	        if (options.afterSaveCallback) {
	          options.afterSaveCallback();
	        }
	        return;
	      }
	      this.save().then(result => {
	        //todo: we need to parse response and sets id for each new block
	        if (result === true) {
	          this.setSavingMark(true);
	          if (options.afterSaveCallback) {
	            options.afterSaveCallback();
	          }
	        }
	        if (result !== true) {
	          if (options.saveErrorCallback) {
	            options.saveErrorCallback();
	          }
	        }
	      });
	    });
	  }

	  /**
	   * Registers callback on slider close.
	   */
	  onCloseSlider() {
	    BX.addCustomEvent('SidePanel.Slider:onClose', event => {
	      if (event.slider.url.indexOf('/sign/edit/') === 0) {
	        if (!this.everythingIsSaved()) {
	          event.denyAction();
	          ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_MESSAGE'), main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_TITLE'), messageBox => {
	            this.setSavingMark(true);
	            messageBox.close();
	            event.slider.close();
	          }, main_core.Loc.getMessage('SIGN_JS_DOCUMENT_SAVE_ALERT_APPLY'), messageBox => {
	            messageBox.close();
	          });
	        }
	      }
	    });
	  }

	  /**
	   * Sets mark to document that everything was saved or not.
	   * @param {boolean} mark
	   */
	  setSavingMark(mark) {
	    this.setParam('bxSignEditorAllSaved', mark);
	  }

	  /**
	   * Returns true, if everything was saved within editor.
	   * @return {boolean}
	   */
	  everythingIsSaved() {
	    return this.getParam('bxSignEditorAllSaved') === true;
	  }
	  setParam(name, value) {
	    const slider = BX.SidePanel.Instance.getSliderByWindow(window);
	    if (slider) {
	      slider.getData().set(name, value);
	    }
	  }
	  getParam(name) {
	    const slider = BX.SidePanel.Instance.getSliderByWindow(window);
	    if (slider) {
	      return slider.getData().get(name);
	    }
	    return undefined;
	  }

	  /**
	   * Returns true if element with specified top & bottom over dead zone.
	   * @param {number} top
	   * @param {number} bottom
	   * @param {boolean} notAddMargin
	   * @return {boolean}
	   */
	  inDeadZone(top, bottom, notAddMargin) {
	    const seams = this.getSeams();
	    const content = document.querySelector('.sign-editor__content');
	    top += content.scrollTop;
	    bottom += content.scrollTop;
	    if (seams.length > 0 && notAddMargin !== true) {
	      const documentLayoutRect = babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout].getBoundingClientRect();
	      top += documentLayoutRect.top;
	      bottom += documentLayoutRect.top;
	    }
	    for (let ii = 0, cc = seams.length; ii < cc; ii++) {
	      const seam = seams[ii];

	      // top on page, bottom on seam
	      if (top <= seam[0] && bottom >= seam[0] && bottom <= seam[1]) {
	        return seam[0] - bottom;
	      }

	      // top on one page, bottom on another (seam in a middle)
	      if (top <= seam[0] && bottom >= seam[1]) {
	        //seam[0] - bottom >> shift top
	        //seam[1] - top >> shift bottom
	        return seam[0] - bottom;
	      }

	      // block into a seam
	      if (top >= seam[0] && bottom <= seam[1]) {
	        return seam[0] - bottom;
	      }

	      // top on seam, bottom on page
	      if (top >= seam[0] && top <= seam[1] && bottom >= seam[1]) {
	        return seam[1] - top;
	      }
	    }
	    return 0;
	  }
	}
	function _pixelToPercent2(position, pageRect) {
	  position.widthPx = position.width;
	  position.heightPx = position.height;
	  position.left = position.left * 100 / pageRect.width;
	  position.top = position.top * 100 / pageRect.height;
	  position.width = position.width / pageRect.width * 100;
	  position.height = position.height / pageRect.height * 100;
	  return position;
	}
	function _percentToPixel2(position) {
	  if (!position.page || typeof babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects][position.page - 1] === 'undefined') {
	    return position;
	  }
	  const pageImageRect = babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects][position.page - 1];
	  const pixelPosition = {};
	  if (typeof position.left === 'number') {
	    pixelPosition.left = position.left * pageImageRect.width / 100;
	  }
	  if (typeof position.top === 'number') {
	    let top = position.top * pageImageRect.height / 100;
	    let pageNum = 0;
	    while (pageNum < position.page - 1) {
	      top += babelHelpers.classPrivateFieldLooseBase(this, _pagesRects)[_pagesRects][pageNum].height + gapBetweenPages;
	      pageNum++;
	    }
	    pixelPosition.top = top;
	  }
	  if (typeof position.width === 'number') {
	    pixelPosition.width = position.width * pageImageRect.width / 100;
	  }
	  if (typeof position.height === 'number') {
	    pixelPosition.height = position.height * pageImageRect.height / 100;
	  }
	  return pixelPosition;
	}
	function _addBlock2(data, seamShift) {
	  const position = data.position ? babelHelpers.classPrivateFieldLooseBase(this, _percentToPixel)[_percentToPixel](data.position) : null;
	  const newBlock = new Block({
	    id: data.id,
	    code: data.code,
	    party: data.party,
	    blocksManager: this,
	    data: data.data,
	    position,
	    style: data.style,
	    onClick: block => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _disableEdit)[_disableEdit]) {
	        return;
	      }
	      this.resizeArea.show(block);
	      this.unMuteResizeArea();
	      block.adjustActionsPanel();
	    },
	    onRemove: block => {
	      this.resizeArea.hide();
	    }
	  });
	  const blockLayout = newBlock.getLayout();
	  babelHelpers.classPrivateFieldLooseBase(this, _subscribeOnBlockEvents)[_subscribeOnBlockEvents](newBlock);
	  if (['sign', 'mysign'].includes(newBlock.getCode())) {
	    var _existedSignBlock$get;
	    const existedSignBlock = babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].find(block => block.getCode() === newBlock.getCode());
	    const signColor = existedSignBlock == null ? void 0 : existedSignBlock.getStyle == null ? void 0 : (_existedSignBlock$get = existedSignBlock.getStyle()) == null ? void 0 : _existedSignBlock$get.color;
	    if (!main_core.Type.isNil(signColor)) {
	      newBlock.updateColor(signColor, false);
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].push(newBlock);
	  main_core.Dom.append(blockLayout, babelHelpers.classPrivateFieldLooseBase(this, _documentLayout)[_documentLayout]);
	  return newBlock;
	}
	function _subscribeOnBlockEvents2(newBlock) {
	  const newBlockCode = newBlock.getCode();
	  if (['sign', 'mysign'].includes(newBlockCode)) {
	    const colorChangeListener = event => babelHelpers.classPrivateFieldLooseBase(this, _onBlockColorStyleChange)[_onBlockColorStyleChange](newBlock, event);
	    babelHelpers.classPrivateFieldLooseBase(this, _eventListeners)[_eventListeners].blockColorChange.set(newBlock, {
	      subscription: colorChangeListener,
	      eventName: newBlock.events.onColorStyleChange
	    });
	    newBlock.subscribe(newBlock.events.onColorStyleChange, colorChangeListener);
	  } else if (['myreference', 'reference'].includes(newBlockCode)) {
	    const onFieldSelectListener = event => babelHelpers.classPrivateFieldLooseBase(this, _onReferenceBlockFieldSelect)[_onReferenceBlockFieldSelect](newBlock, event);
	    babelHelpers.classPrivateFieldLooseBase(this, _eventListeners)[_eventListeners].blockReferenceFieldSelect.set(newBlock, {
	      subscription: onFieldSelectListener,
	      eventName: 'onFieldSelect'
	    });
	    newBlock.subscribe('onFieldSelect', onFieldSelectListener);
	  } else if (['b2ereference', 'myb2ereference', 'employeedynamic', 'hcmlinkreference'].includes(newBlockCode)) {
	    const onFieldSelectListener = event => babelHelpers.classPrivateFieldLooseBase(this, _onB2eReferenceBlockFieldSelect)[_onB2eReferenceBlockFieldSelect](newBlock, event);
	    babelHelpers.classPrivateFieldLooseBase(this, _eventListeners)[_eventListeners].blockB2eReferenceFieldSelect.set(newBlock, {
	      subscription: onFieldSelectListener,
	      eventName: 'onFieldSelect'
	    });
	    newBlock.subscribe('onFieldSelect', onFieldSelectListener);
	  }
	}
	function _onRepositoryItemClick2(code, party) {
	  party = parseInt(party);
	  this.setSavingMark(false);
	  const block = babelHelpers.classPrivateFieldLooseBase(this, _addBlock)[_addBlock]({
	    code,
	    party
	  });
	  block.assign();
	  block.onPlaced();
	}
	function _startDisabledEditDocumentTour2() {
	  const guide = new sign_tour.Guide({
	    steps: [{
	      target: document.getElementById('sign-editor-btn-edit'),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_EDIT_TEXT')
	    }],
	    id: 'sign-tour-guide-onboarding-master-document-edit-disabled',
	    autoSave: true,
	    simpleMode: true
	  });
	  guide.startOnce();
	}
	function _startEditDocumentTour2() {
	  const helpBtnSpotlight = new BX.SpotLight({
	    targetElement: document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _helpBtnElementId)[_helpBtnElementId]),
	    targetVertex: 'middle-center',
	    autoSave: true,
	    id: 'sign-spotlight-onboarding-master-document-edit'
	  });
	  const guide = new sign_tour.Guide({
	    steps: [{
	      target: document.getElementById('sign-editor-bar-content'),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_BLOCKS_TEXT'),
	      position: 'left'
	    }, {
	      target: document.getElementById(babelHelpers.classPrivateFieldLooseBase(this, _helpBtnElementId)[_helpBtnElementId]),
	      title: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TITLE'),
	      text: main_core.Loc.getMessage('SIGN_JS_DOCUMENT_TOUR_BTN_HELP_TEXT'),
	      rounded: true,
	      article: babelHelpers.classPrivateFieldLooseBase(this, _helpBtnHelperArticleCode)[_helpBtnHelperArticleCode],
	      events: {
	        onShow: () => helpBtnSpotlight.show(),
	        onClose: () => helpBtnSpotlight.close()
	      }
	    }],
	    id: 'sign-tour-guide-onboarding-master-document-edit',
	    autoSave: true,
	    simpleMode: true
	  });
	  guide.startOnce();
	}
	function _highlightInvalidBlocks2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].forEach(block => {
	    if (block.isRemoved()) {
	      return;
	    }
	    const code = block.getCode();
	    const isCRM = code === 'reference' || code === 'myreference';
	    if (isCRM && main_core.Type.isArray(block.getData())) {
	      main_core.Dom.addClass(block.getLayout(), '--invalid');
	    }
	  });
	}
	function _onBlockColorStyleChange2(updatedBlock, event) {
	  var _event$getData$color;
	  if (!['sign', 'mysign'].includes(updatedBlock.getCode())) {
	    return;
	  }
	  const newColor = (_event$getData$color = event.getData().color) != null ? _event$getData$color : null;
	  if (main_core.Type.isNull(newColor)) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].filter(block => updatedBlock.getCode() === block.getCode()).forEach(block => {
	    if (updatedBlock === block) {
	      return;
	    }
	    block.updateColor(newColor, false);
	  });
	}
	function _onReferenceBlockFieldSelect2(block, event) {
	  if (event.getData().selectedFieldNames.length === 0) {
	    const deleteLatency = 150;
	    setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _onBlockDeleteBecauseFieldNotSelected)[_onBlockDeleteBecauseFieldNotSelected](block), deleteLatency);
	  }
	}
	function _onB2eReferenceBlockFieldSelect2(block, event) {
	  if (event.getData().selectedFieldNames.length === 0) {
	    const deleteLatency = 150;
	    setTimeout(() => babelHelpers.classPrivateFieldLooseBase(this, _onB2eReferenceBlockDeleteBecauseFieldNotSelected)[_onB2eReferenceBlockDeleteBecauseFieldNotSelected](block), deleteLatency);
	  }
	}
	function _onBlockDeleteBecauseFieldNotSelected2(block) {
	  block.remove();
	  // show notification once
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$9)[_cache$9].remember('notify', () => ui_notification.UI.Notification.Center.notify({
	    content: main_core.Text.encode(main_core.Loc.getMessage('SIGN_EDITOR_ERROR_REFERENCE_BLOCK_FIELD_NOT_SELECTED_HINT_TEXT')),
	    autoHideDelay: 3000,
	    width: 480
	  }));
	}
	function _onB2eReferenceBlockDeleteBecauseFieldNotSelected2(block) {
	  block.remove();
	  // show notification once
	  babelHelpers.classPrivateFieldLooseBase(this, _cache$9)[_cache$9].remember('notifyB2e', () => ui_notification.UI.Notification.Center.notify({
	    content: main_core.Text.encode(main_core.Loc.getMessage('SIGN_EDITOR_ERROR_B2E_REFERENCE_BLOCK_FIELD_NOT_SELECTED_HINT_TEXT')),
	    autoHideDelay: 3000,
	    width: 480
	  }));
	}
	function _unsubscribeFromBlocksEvents2() {
	  const subscriptions = Object.values(babelHelpers.classPrivateFieldLooseBase(this, _eventListeners)[_eventListeners]).flatMap(subscription => [...subscription.entries()]);
	  for (const [block, {
	    subscription,
	    eventName
	  }] of subscriptions) {
	    block.unsubscribe(eventName, subscription);
	  }
	  Object.values(babelHelpers.classPrivateFieldLooseBase(this, _eventListeners)[_eventListeners]).forEach(eventListenerCache => eventListenerCache.clear());
	}

	let _$i = t => t,
	  _t$i,
	  _t2$e,
	  _t3$a,
	  _t4$2,
	  _t5$2,
	  _t6$2,
	  _t7$2,
	  _t8$2,
	  _t9$2,
	  _t10;
	const buttonClassList = ['ui-btn', 'ui-btn-sm', 'ui-btn-round', 'ui-btn-light-border'];
	const SectionType = Object.freeze({
	  General: 0,
	  FirstParty: 1,
	  SecondParty: 2,
	  HcmLinkIntegration: 3
	});
	var _sidePanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sidePanel");
	var _closeSidePanel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeSidePanel");
	var _resolvePages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resolvePages");
	var _dom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dom");
	var _documentLayout$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentLayout");
	var _blocksManager = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blocksManager");
	var _wizardType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wizardType");
	var _documentData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentData");
	var _urls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urls");
	var _totalPages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("totalPages");
	var _sectionElementByType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sectionElementByType");
	var _disabledSections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disabledSections");
	var _needSaveBlocksOnSidePanelClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needSaveBlocksOnSidePanelClose");
	var _needToLockSidePanelClose = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needToLockSidePanelClose");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _isTemplateMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTemplateMode");
	var _isB2e = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isB2e");
	var _getArticleCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getArticleCode");
	var _covertRoleToBlockParty = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("covertRoleToBlockParty");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _createHeader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createHeader");
	var _onSaveBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSaveBtnClick");
	var _onEditBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onEditBtnClick");
	var _createContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createContent");
	var _createSections = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createSections");
	var _toggleEditMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleEditMode");
	var _getSectionsData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSectionsData");
	var _isDocumentInitiatedByEmployee = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDocumentInitiatedByEmployee");
	var _onSidePanelCloseStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSidePanelCloseStart");
	var _isDynamicEmployeeFieldAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDynamicEmployeeFieldAvailable");
	var _isBlockCanBeInitialized = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isBlockCanBeInitialized");
	class Editor extends main_core_events.EventEmitter {
	  constructor(wizardType, options) {
	    var _options$documentInit;
	    super();
	    Object.defineProperty(this, _isBlockCanBeInitialized, {
	      value: _isBlockCanBeInitialized2
	    });
	    Object.defineProperty(this, _isDynamicEmployeeFieldAvailable, {
	      value: _isDynamicEmployeeFieldAvailable2
	    });
	    Object.defineProperty(this, _onSidePanelCloseStart, {
	      value: _onSidePanelCloseStart2
	    });
	    Object.defineProperty(this, _isDocumentInitiatedByEmployee, {
	      value: _isDocumentInitiatedByEmployee2
	    });
	    Object.defineProperty(this, _getSectionsData, {
	      value: _getSectionsData2
	    });
	    Object.defineProperty(this, _toggleEditMode, {
	      value: _toggleEditMode2
	    });
	    Object.defineProperty(this, _createSections, {
	      value: _createSections2
	    });
	    Object.defineProperty(this, _createContent, {
	      value: _createContent2
	    });
	    Object.defineProperty(this, _onEditBtnClick, {
	      value: _onEditBtnClick2
	    });
	    Object.defineProperty(this, _onSaveBtnClick, {
	      value: _onSaveBtnClick2
	    });
	    Object.defineProperty(this, _createHeader, {
	      value: _createHeader2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _covertRoleToBlockParty, {
	      value: _covertRoleToBlockParty2
	    });
	    Object.defineProperty(this, _getArticleCode, {
	      value: _getArticleCode2
	    });
	    Object.defineProperty(this, _isB2e, {
	      value: _isB2e2
	    });
	    Object.defineProperty(this, _sidePanel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _closeSidePanel, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _resolvePages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _dom, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentLayout$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _blocksManager, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wizardType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentData, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _urls, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _totalPages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _sectionElementByType, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _disabledSections, {
	      writable: true,
	      value: new Set()
	    });
	    Object.defineProperty(this, _needSaveBlocksOnSidePanelClose, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _needToLockSidePanelClose, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isTemplateMode, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('BX.Sign.V2.Editor');
	    const {
	      Instance
	    } = main_core.Reflection.getClass('BX.SidePanel');
	    babelHelpers.classPrivateFieldLooseBase(this, _sidePanel)[_sidePanel] = Instance;
	    babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom] = main_core.Tag.render(_t$i || (_t$i = _$i`<div class="sign-wizard__scope sign-editor"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1] = main_core.Tag.render(_t2$e || (_t2$e = _$i`
			<div class="sign-editor__document"></div>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager] = new BlocksManager({
	      documentLayout: babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1],
	      disableEdit: false,
	      languages: options.languages,
	      isTemplateMode: Boolean(options.isTemplateMode),
	      documentInitiatedByType: (_options$documentInit = options.documentInitiatedByType) != null ? _options$documentInit : sign_type.DocumentInitiated.company
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode] = Boolean(options.isTemplateMode);
	    babelHelpers.classPrivateFieldLooseBase(this, _wizardType)[_wizardType] = wizardType;
	    babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _totalPages)[_totalPages] = 0;
	  }
	  setUrls(urls, totalPages) {
	    if (urls.length === 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls] = [];
	      babelHelpers.classPrivateFieldLooseBase(this, _totalPages)[_totalPages] = 0;
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls] = [...babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls], ...urls];
	    babelHelpers.classPrivateFieldLooseBase(this, _totalPages)[_totalPages] = totalPages;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].length === totalPages) {
	      var _babelHelpers$classPr, _babelHelpers$classPr2;
	      (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _resolvePages))[_resolvePages]) == null ? void 0 : _babelHelpers$classPr.call(_babelHelpers$classPr2);
	    }
	  }
	  set documentData(documentData) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData] = documentData;
	    const {
	      uid = '',
	      isTemplate
	    } = documentData;
	    if (isTemplate) {
	      babelHelpers.classPrivateFieldLooseBase(this, _toggleEditMode)[_toggleEditMode](false);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _toggleEditMode)[_toggleEditMode](true);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].setDocumentUid(uid);
	  }
	  setSenderType(senderType) {
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].documentInitiatedByType = senderType;
	  }
	  set entityData(entityData) {
	    const forbiddenRoles = new Set([sign_type.MemberRole.editor, sign_type.MemberRole.reviewer]);
	    const members = Object.values(entityData).filter(member => !forbiddenRoles.has(member.role)).map(member => {
	      const {
	        id: cid,
	        title: name,
	        part,
	        presetId,
	        entityTypeId,
	        uid,
	        entityId,
	        role
	      } = member;
	      return {
	        cid,
	        name,
	        part: babelHelpers.classPrivateFieldLooseBase(this, _covertRoleToBlockParty)[_covertRoleToBlockParty](part, role),
	        presetId,
	        entityTypeId,
	        uid,
	        entityId
	      };
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].addMembers(members);
	  }
	  async renderDocument() {
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1]);
	    const {
	      promises,
	      fragment
	    } = babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].reduce((acc, url) => {
	      // eslint-disable-next-line no-shadow
	      const {
	        fragment,
	        promises
	      } = acc;
	      const promise = new Promise(resolve => {
	        const page = main_core.Tag.render(_t3$a || (_t3$a = _$i`
					<div class="sign-editor__document_page">
						<img src="${0}" onload="${0}"  alt=""/>
					</div>
				`), url, resolve);
	        main_core.Dom.append(page, fragment);
	      });
	      promises.push(promise);
	      return acc;
	    }, {
	      fragment: new DocumentFragment(),
	      promises: []
	    });
	    main_core.Dom.append(fragment, babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1]);
	    const {
	      resizeArea
	    } = babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager];
	    main_core.Dom.append(resizeArea.getLayout(), babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1]);
	    await Promise.all(promises);
	    main_core_events.EventEmitter.unsubscribeAll('SidePanel.Slider:onOpenComplete');
	    main_core_events.EventEmitter.subscribeOnce('SidePanel.Slider:onOpenComplete', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].initPagesRect();
	      babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].initBlocks(babelHelpers.classPrivateFieldLooseBase(this, _documentData)[_documentData].blocks.filter(block => babelHelpers.classPrivateFieldLooseBase(this, _isBlockCanBeInitialized)[_isBlockCanBeInitialized](block)));
	    });
	  }
	  show() {
	    return new Promise(resolve => {
	      babelHelpers.classPrivateFieldLooseBase(this, _closeSidePanel)[_closeSidePanel] = resolve;
	      babelHelpers.classPrivateFieldLooseBase(this, _sidePanel)[_sidePanel].open('editor', {
	        contentCallback: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]();
	          return babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom];
	        },
	        events: {
	          onClose: e => babelHelpers.classPrivateFieldLooseBase(this, _onSidePanelCloseStart)[_onSidePanelCloseStart](e),
	          onCloseComplete: () => babelHelpers.classPrivateFieldLooseBase(this, _closeSidePanel)[_closeSidePanel]()
	        }
	      });
	    });
	  }
	  waitForPagesUrls() {
	    return new Promise(resolve => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].length === babelHelpers.classPrivateFieldLooseBase(this, _totalPages)[_totalPages]) {
	        resolve();
	        return;
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _resolvePages)[_resolvePages] = resolve;
	    });
	  }
	  setSectionVisibilityByType(type, visibility) {
	    const sectionElement = babelHelpers.classPrivateFieldLooseBase(this, _sectionElementByType)[_sectionElementByType].get(type);
	    if (sectionElement) {
	      // eslint-disable-next-line @bitrix24/bitrix24-rules/no-style
	      sectionElement.style.display = visibility === true ? 'block' : 'none';
	    }
	    if (visibility) {
	      babelHelpers.classPrivateFieldLooseBase(this, _disabledSections)[_disabledSections].delete(type);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _disabledSections)[_disabledSections].add(type);
	    }
	  }
	  setAnalytics(analytic) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = analytic;
	  }
	}
	function _isB2e2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _wizardType)[_wizardType] === 'b2e';
	}
	function _getArticleCode2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isB2e)[_isB2e]() ? '' : '16571388';
	}
	function _covertRoleToBlockParty2(part, role) {
	  switch (role) {
	    case sign_type.MemberRole.assignee:
	      return 1;
	    case sign_type.MemberRole.signer:
	      return 2;
	    default:
	      return part;
	  }
	}
	function _render2() {
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom]);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _createHeader)[_createHeader](), babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom]);
	  main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _createContent)[_createContent](), babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom]);
	}
	function _createHeader2() {
	  const editButtonClassName = ['sign-editor__header_edit-btn', ...buttonClassList].join(' ');
	  const saveButtonClassName = ['ui-btn-success', ...buttonClassList.slice(0, -1)].join(' ');
	  const editButtonTitle = main_core.Loc.getMessage('SIGN_EDITOR_EDIT');
	  const saveButtonTitle = main_core.Loc.getMessage('SIGN_EDITOR_SAVE');
	  let helpArticleElement = '';
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getArticleCode)[_getArticleCode]()) {
	    helpArticleElement = main_core.Tag.render(_t4$2 || (_t4$2 = _$i`
				<span
								onclick="${0}"
								class="sign-editor__header_help"
								></span>
			`), () => {
	      const Helper = main_core.Reflection.getClass('BX.Helper');
	      Helper.show(`redirect=detail&code=${babelHelpers.classPrivateFieldLooseBase(this, _getArticleCode)[_getArticleCode]()}`);
	    });
	  }
	  const headTitleNode = main_core.Tag.render(_t5$2 || (_t5$2 = _$i`
			<p class="sign-editor__header_title">
				<span>${0}</span>
				<span
					data-hint="${0}"
				></span>
			</p>
		`), main_core.Loc.getMessage('SIGN_EDITOR_EDITING'), main_core.Loc.getMessage('SIGN_EDITOR_EDITING_HINT_MSGVER_1'));
	  sign_v2_helper.Hint.create(headTitleNode);
	  return main_core.Tag.render(_t6$2 || (_t6$2 = _$i`
			<div class="sign-editor__header">
				${0}
				<div class="sign-editor__header_right">
					${0}
					<span
						class="${0}"
						title="${0}"
						onclick="${0}"
					>
						${0}
					</span>
					<span
						class="${0}"
						title="${0}"
						onclick="${0}"
					>
						${0}
					</span>
				</div>
			</div>
		`), headTitleNode, helpArticleElement, editButtonClassName, editButtonTitle, () => babelHelpers.classPrivateFieldLooseBase(this, _onEditBtnClick)[_onEditBtnClick](), editButtonTitle, saveButtonClassName, saveButtonTitle, e => babelHelpers.classPrivateFieldLooseBase(this, _onSaveBtnClick)[_onSaveBtnClick](e), saveButtonTitle);
	}
	async function _onSaveBtnClick2({
	  target
	}) {
	  main_core.Dom.addClass(target, 'ui-btn-wait');
	  const blocks = await babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].save();
	  const uid = babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].getDocumentUid();
	  main_core.Dom.removeClass(target, 'ui-btn-wait');
	  if (!blocks) {
	    return;
	  }
	  main_core_events.EventEmitter.subscribeOnce('SidePanel.Slider:onCloseComplete', () => {
	    this.emit('save', {
	      uid,
	      blocks
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] instanceof sign_v2_analytics.Analytics && babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	        event: 'proceed_step_document_editor',
	        status: 'success'
	      });
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _needSaveBlocksOnSidePanelClose)[_needSaveBlocksOnSidePanelClose] = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _sidePanel)[_sidePanel].close();
	  babelHelpers.classPrivateFieldLooseBase(this, _needSaveBlocksOnSidePanelClose)[_needSaveBlocksOnSidePanelClose] = true;
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleEditMode)[_toggleEditMode](false);
	}
	function _onEditBtnClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _toggleEditMode)[_toggleEditMode](true);
	  if (babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] instanceof sign_v2_analytics.Analytics && babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	      event: 'open_document_editor',
	      status: 'success'
	    });
	  }
	}
	function _createContent2() {
	  const sections = babelHelpers.classPrivateFieldLooseBase(this, _createSections)[_createSections]();
	  const editorContent = main_core.Tag.render(_t7$2 || (_t7$2 = _$i`
			<div class="sign-editor__content">
				<div class="sign-editor__document-container">
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _documentLayout$1)[_documentLayout$1], sections);
	  sign_v2_helper.Hint.create(sections, {
	    bindOptions: {
	      position: 'top'
	    },
	    angle: {
	      position: 'bottom'
	    },
	    targetContainer: editorContent
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].setEditorContent(editorContent);
	  return editorContent;
	}
	function _createSections2() {
	  const sections = babelHelpers.classPrivateFieldLooseBase(this, _getSectionsData)[_getSectionsData]().filter(({
	    singleBlockSection
	  }) => singleBlockSection !== true);
	  const sectionsNodes = sections.map(section => {
	    const entries = Object.entries(section.blocks);
	    const blocks = entries.map(([code, block]) => {
	      const {
	        title,
	        hint
	      } = block;
	      return main_core.Tag.render(_t8$2 || (_t8$2 = _$i`
					<div
						class="sign-editor__section_block"
						data-code="${0}"
						data-part="${0}"
					>
						<div class="sign-editor__section_block-subject">
							<span>${0}</span>
							<span data-hint="${0}"></span>
						</div>
						<span class="sign-editor__section_add-block-btn">
							${0}
						</span>
					</div>
				`), code, section.part, main_core.Loc.getMessage(title), main_core.Text.encode(main_core.Loc.getMessage(hint)), main_core.Loc.getMessage('SIGN_EDITOR_BLOCK_ADD_TO_DOCUMENT'));
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].initRepository(blocks);
	    const sectionElement = main_core.Tag.render(_t9$2 || (_t9$2 = _$i`
				<div class="sign-editor__section">
					<p class="sign-editor__section_title"
						style="display: ${0}"
					>
						${0}
					</p>
					${0}
				</div>
			`), section.title ? 'block' : 'none', section.title ? main_core.Loc.getMessage(section.title) : '', blocks);
	    babelHelpers.classPrivateFieldLooseBase(this, _sectionElementByType)[_sectionElementByType].set(section.part, sectionElement);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _disabledSections)[_disabledSections].has(section.part)) {
	      // eslint-disable-next-line @bitrix24/bitrix24-rules/no-style
	      sectionElement.style.display = 'none';
	    }
	    return sectionElement;
	  });
	  return main_core.Tag.render(_t10 || (_t10 = _$i`
			<div class="sign-editor__sections">
				${0}
			</div>
		`), sectionsNodes);
	}
	function _toggleEditMode2(isEdit) {
	  if (isEdit) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom], '--editable');
	    return;
	  }
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom], '--editable');
	}
	function _getSectionsData2() {
	  const firstPartyBlocks = {};
	  const partnerBlocks = {};
	  const generalBlocks = {
	    text: {
	      title: 'SIGN_EDITOR_BLOCK_TEXT',
	      hint: 'SIGN_EDITOR_BLOCK_TEXT_HINT'
	    }
	  };
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isB2e)[_isB2e]()) {
	    generalBlocks.date = {
	      title: 'SIGN_EDITOR_BLOCK_DATE',
	      hint: 'SIGN_EDITOR_BLOCK_DATE_HINT'
	    };
	  }
	  generalBlocks.number = {
	    title: 'SIGN_EDITOR_BLOCK_NUMBER',
	    hint: 'SIGN_EDITOR_BLOCK_NUMBER_HINT'
	  };
	  let titles = {
	    firstParty: 'SIGN_EDITOR_BLOCKS_FIRST_PARTY',
	    partner: 'SIGN_EDITOR_BLOCKS_PARTNER'
	  };
	  if (babelHelpers.classPrivateFieldLooseBase(this, _wizardType)[_wizardType] === 'b2e') {
	    let personalDataMessageCode = 'SIGN_EDITOR_BLOCK_B2E_REFERENCE_HINT';
	    let otherDataMessageCode = 'SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC_HINT';
	    let mainDataMessageCode = 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_HINT';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].isTemplateMode) {
	      personalDataMessageCode = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee]() ? 'SIGN_EDITOR_BLOCK_B2E_REFERENCE_HINT_TEMPLATE_EMPLOYEE' : 'SIGN_EDITOR_BLOCK_B2E_REFERENCE_HINT_TEMPLATE_COMPANY';
	      otherDataMessageCode = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee]() ? 'SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC_HINT_TEMPLATE_EMPLOYEE' : 'SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC_HINT_TEMPLATE_COMPANY';
	      mainDataMessageCode = babelHelpers.classPrivateFieldLooseBase(this, _isDocumentInitiatedByEmployee)[_isDocumentInitiatedByEmployee]() ? 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_HINT_TEMPLATE_EMPLOYEE' : 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_HINT_TEMPLATE_COMPANY';
	    }
	    Object.assign(firstPartyBlocks, {
	      myb2ereference: {
	        title: 'SIGN_EDITOR_BLOCK_MY_B2E_REFERENCE_MSG_VER_1',
	        hint: mainDataMessageCode
	      },
	      myrequisites: {
	        title: 'SIGN_EDITOR_BLOCK_REQUISITES_MSG_VER_1',
	        hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_REQUISITES_HINT'
	      }
	    });
	    Object.assign(partnerBlocks, {
	      b2ereference: {
	        title: 'SIGN_EDITOR_BLOCK_B2E_REFERENCE_MSG_VER_1',
	        hint: personalDataMessageCode
	      }
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDynamicEmployeeFieldAvailable)[_isDynamicEmployeeFieldAvailable]()) {
	      Object.assign(partnerBlocks, {
	        employeedynamic: {
	          title: 'SIGN_EDITOR_BLOCK_B2E_EMPLOYEE_DYNAMIC',
	          hint: otherDataMessageCode
	        }
	      });
	    }
	    titles = {
	      firstParty: 'SIGN_EDITOR_BLOCKS_FIRST_PARTY_B2E',
	      partner: 'SIGN_EDITOR_BLOCKS_EMPLOYEE_B2E'
	    };
	    return [{
	      title: titles.firstParty,
	      blocks: firstPartyBlocks,
	      part: 1
	    }, {
	      title: titles.partner,
	      blocks: partnerBlocks,
	      part: 2
	    }, {
	      title: 'SIGN_EDITOR_BLOCKS_GENERAL',
	      blocks: generalBlocks,
	      part: 0
	    }, {
	      title: null,
	      blocks: {
	        hcmlinkreference: {
	          title: 'SIGN_EDITOR_BLOCK_B2E_HCMLINK_TITLE',
	          hint: 'SIGN_EDITOR_BLOCK_B2E_HCMLINK_HINT'
	        }
	      },
	      part: 3
	    }];
	  } else {
	    Object.assign(firstPartyBlocks, {
	      myreference: {
	        title: 'SIGN_EDITOR_BLOCK_CRM',
	        hint: 'SIGN_EDITOR_BLOCK_CRM_HINT'
	      },
	      myrequisites: {
	        title: 'SIGN_EDITOR_BLOCK_REQUISITES_MSG_VER_1',
	        hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_REQUISITES_HINT'
	      },
	      mysign: {
	        title: 'SIGN_EDITOR_BLOCK_SIGNATURE',
	        hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_SIGNATURE_HINT'
	      },
	      mystamp: {
	        title: 'SIGN_EDITOR_BLOCK_STAMP_MSGVER_1',
	        hint: 'SIGN_EDITOR_BLOCK_FIRST_PARTY_STAMP_HINT'
	      }
	    });
	    Object.assign(partnerBlocks, {
	      reference: {
	        ...firstPartyBlocks.myreference
	      },
	      requisites: {
	        ...firstPartyBlocks.myrequisites,
	        hint: 'SIGN_EDITOR_BLOCK_PARTNER_REQUISITES_HINT'
	      },
	      sign: {
	        ...firstPartyBlocks.mysign,
	        hint: 'SIGN_EDITOR_BLOCK_PARTNER_SIGNATURE_HINT'
	      },
	      stamp: {
	        ...firstPartyBlocks.mystamp,
	        hint: 'SIGN_EDITOR_BLOCK_PARTNER_STAMP_HINT'
	      }
	    });
	    return [{
	      title: titles.firstParty,
	      blocks: firstPartyBlocks,
	      part: 1
	    }, {
	      title: titles.partner,
	      blocks: partnerBlocks,
	      part: 2
	    }, {
	      title: 'SIGN_EDITOR_BLOCKS_GENERAL',
	      blocks: generalBlocks,
	      part: 0
	    }];
	  }
	}
	function _isDocumentInitiatedByEmployee2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].documentInitiatedByType === sign_type.DocumentInitiated.employee;
	}
	async function _onSidePanelCloseStart2(event) {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _needSaveBlocksOnSidePanelClose)[_needSaveBlocksOnSidePanelClose]) {
	    return;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _needToLockSidePanelClose)[_needToLockSidePanelClose]) {
	    return;
	  }
	  event.denyAction();
	  try {
	    const blocks = await babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].save();
	    if (!blocks) {
	      return;
	    }
	    const uid = babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].getDocumentUid();
	    babelHelpers.classPrivateFieldLooseBase(this, _needToLockSidePanelClose)[_needToLockSidePanelClose] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _sidePanel)[_sidePanel].close();
	    babelHelpers.classPrivateFieldLooseBase(this, _needToLockSidePanelClose)[_needToLockSidePanelClose] = true;
	    this.emit('save', {
	      uid,
	      blocks
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	      event: 'proceed_step_document_editor',
	      status: 'success'
	    });
	  } catch {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] instanceof sign_v2_analytics.Analytics && babelHelpers.classPrivateFieldLooseBase(this, _isTemplateMode)[_isTemplateMode]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].send({
	        event: 'proceed_step_document_editor',
	        status: 'error'
	      });
	    }
	  }
	}
	function _isDynamicEmployeeFieldAvailable2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isB2e)[_isB2e]() && babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].isTemplateMode;
	}
	function _isBlockCanBeInitialized2(block) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _disabledSections)[_disabledSections].has(block.party)) {
	    return false;
	  }
	  return !babelHelpers.classPrivateFieldLooseBase(this, _blocksManager)[_blocksManager].isTemplateMode || !String(block.data.field).startsWith('SMART_B2E_DOC');
	}

	exports.SectionType = SectionType;
	exports.Editor = Editor;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX.Sign.V2,BX.Sign.V2,BX.Main,BX.Sign.Tour,BX,BX.UI,BX.UI.Dialogs,BX.UI,BX.Sign,BX,BX,BX.UI.Stamp,BX.Sign.V2,BX.Crm.Form.Fields,BX.Crm.Requisite,BX.Sign,BX.Sign.B2e,BX.Sign,BX,BX.Sign,BX,BX.UI.DragAndDrop,BX.Event));
//# sourceMappingURL=editor.bundle.js.map
