/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,ui_analytics,emailInvitationInput,ui_fonts_inter,ui_buttons,main_popup,ui_switcher,intranet_invitationInput,ui_entitySelector,DepartmentControl,main_core,main_core_events) {
	'use strict';

	DepartmentControl = DepartmentControl && DepartmentControl.hasOwnProperty('default') ? DepartmentControl['default'] : DepartmentControl;

	let _ = t => t,
	  _t;
	class Phone {
	  constructor() {
	    this.count = 0;
	    this.index = 0;
	    this.maxCount = 5;
	    this.inputStack = [];
	  }
	  renderPhoneRow(inputNode) {
	    if (this.count >= this.maxCount) {
	      return;
	    }
	    if (!main_core.Type.isDomNode(inputNode)) {
	      return;
	    }
	    const num = inputNode.getAttribute('data-num');
	    if (inputNode.parentNode.querySelector(`#phone_number_${num}`)) {
	      return;
	    }
	    const element = main_core.Tag.render(_t || (_t = _`
			<span style="z-index: 3;" class="ui-ctl-before" data-role="phone-block">
				<input type="hidden" name="PHONE_COUNTRY[]" id="phone_country_${0}" value="">
				<input type="hidden" name="PHONE[]" id="phone_number_${0}" value="">
				<div class="invite-dialog-phone-flag-block" data-role="flag">
					<span data-role="phone_flag_${0}" style="pointer-events: none;"></span>
				</div>
				<input class="invite-dialog-phone-input" type="hidden" id="phone_input_${0}" value="">&nbsp;
			</span>
		`), num, num, num, num);
	    inputNode.style.paddingLeft = '57px';
	    main_core.Dom.append(element, inputNode.parentNode);
	    const flagNode = inputNode.parentNode.querySelector("[data-role='flag']");
	    if (main_core.Type.isDomNode(flagNode)) {
	      main_core.Event.bind(inputNode.parentNode.querySelector("[data-role='flag']"), 'click', () => {
	        this.showCountrySelector(num);
	      });
	    }
	    const changeCallback = function (i, inputNode) {
	      return function (e) {
	        if (main_core.Type.isDomNode(inputNode.parentNode)) {
	          inputNode.parentNode.querySelector(`#phone_number_${i}`).value = e.value;
	          inputNode.parentNode.querySelector(`#phone_country_${i}`).value = e.country;
	        }
	      };
	    };
	    this.inputStack[num] = new BX.PhoneNumber.Input({
	      node: inputNode,
	      flagNode: inputNode.parentNode.querySelector(`[data-role='phone_flag_${num}']`),
	      flagSize: 16,
	      onChange: changeCallback(num, inputNode)
	    });

	    // for ctrl+v paste
	    const id = setInterval(() => {
	      var _inputNode$parentNode, _inputNode$parentNode2;
	      if (!((_inputNode$parentNode = inputNode.parentNode) != null && _inputNode$parentNode.querySelector(`#phone_number_${num}`).value) || !((_inputNode$parentNode2 = inputNode.parentNode) != null && _inputNode$parentNode2.querySelector(`#phone_country_${num}`).value)) {
	        changeCallback(num, inputNode)({
	          value: this.inputStack[num].getValue(),
	          country: this.inputStack[num].formatter ? this.inputStack[num].getCountry() : null
	        });
	      } else {
	        clearInterval(id);
	      }
	    }, 1000);
	  }
	  showCountrySelector(i) {
	    this.inputStack[i]._onFlagClick();
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2;
	const InputRowType = Object.freeze({
	  PHONE: 'phone',
	  EMAIL: 'email',
	  ALL: 'all'
	});
	var _inputRowType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputRowType");
	var _isPhoneEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPhoneEnabled");
	var _isEmailEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEmailEnabled");
	var _checkPhoneInput = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkPhoneInput");
	var _bindPhoneChecker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindPhoneChecker");
	var _renderDefaultRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderDefaultRow");
	var _getDialogInputMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDialogInputMessage");
	class InputRowFactory {
	  constructor(params) {
	    var _params$inputRowType;
	    Object.defineProperty(this, _getDialogInputMessage, {
	      value: _getDialogInputMessage2
	    });
	    Object.defineProperty(this, _renderDefaultRow, {
	      value: _renderDefaultRow2
	    });
	    Object.defineProperty(this, _bindPhoneChecker, {
	      value: _bindPhoneChecker2
	    });
	    Object.defineProperty(this, _checkPhoneInput, {
	      value: _checkPhoneInput2
	    });
	    Object.defineProperty(this, _inputRowType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isPhoneEnabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isEmailEnabled, {
	      writable: true,
	      value: void 0
	    });
	    this.inputNum = 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputRowType)[_inputRowType] = (_params$inputRowType = params.inputRowType) != null ? _params$inputRowType : InputRowType.ALL;
	    babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled] = [InputRowType.ALL, InputRowType.PHONE].includes(babelHelpers.classPrivateFieldLooseBase(this, _inputRowType)[_inputRowType]);
	    babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled)[_isEmailEnabled] = [InputRowType.ALL, InputRowType.EMAIL].includes(babelHelpers.classPrivateFieldLooseBase(this, _inputRowType)[_inputRowType]);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled]) {
	      this.phoneObj = new Phone();
	    }
	  }
	  bindCloseIcons(container) {
	    const inputNodes = Array.prototype.slice.call(container.querySelectorAll('input'));
	    (inputNodes || []).forEach(node => {
	      const closeIcon = node.nextElementSibling;
	      main_core.Event.bind(node, 'input', () => {
	        main_core.Dom.style(closeIcon, 'display', node.value === '' ? 'none' : 'block');
	      });
	      main_core.Event.bind(closeIcon, 'click', event => {
	        event.preventDefault();
	        if (main_core.Type.isDomNode(node.parentNode)) {
	          babelHelpers.classPrivateFieldLooseBase(this, _renderDefaultRow)[_renderDefaultRow](node);
	        }
	        main_core.Dom.style(closeIcon, 'display', 'none');
	      });
	    });
	  }
	  renderInputRowTo(target, showTitles = false) {
	    const element = this.renderEmailInputsRow(showTitles);
	    main_core.Dom.append(element, target);
	  }
	  renderInputRowsTo(target, count = 3) {
	    for (let i = 0; i < count; i++) {
	      this.renderInputRowTo(target, i === 0);
	    }
	  }
	  renderEmailInputsRow(showTitles) {
	    let fieldTitle = '';
	    let nameTitle = '';
	    let lastNameTitle = '';
	    if (showTitles) {
	      fieldTitle = `
				<div class="invite-content__field-lable">
					${babelHelpers.classPrivateFieldLooseBase(this, _getDialogInputMessage)[_getDialogInputMessage]()}
				</div>
			`;
	      nameTitle = `
				<div class="invite-content__field-lable">
					${main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE')}
				</div>
			`;
	      lastNameTitle = `
				<div class="invite-content__field-lable">
					${main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE')}
				</div>
			`;
	    }
	    const element = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="invite-form-row js-form-row">
				<div class="invite-form-col">
					${0}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input 
							name="EMAIL[]" 
							type="text" 
							maxlength="50"
							data-num="${0}" 
							class="ui-ctl-element js-email-phone-input" 
							placeholder="${0}"
						/>
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${0}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="NAME[]" type="text" class="ui-ctl-element js-email-name-input" placeholder="${0}">
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${0}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="LAST_NAME[]" type="text" class="ui-ctl-element js-email-last-name-input" placeholder="${0}">
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
			</div>
		`), showTitles ? fieldTitle : '', this.inputNum++, babelHelpers.classPrivateFieldLooseBase(this, _getDialogInputMessage)[_getDialogInputMessage](), showTitles ? nameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE'), showTitles ? lastNameTitle : '', main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE'));
	    this.bindCloseIcons(element);
	    babelHelpers.classPrivateFieldLooseBase(this, _bindPhoneChecker)[_bindPhoneChecker](element);
	    return element;
	  }
	  parseEmailAndPhone(form) {
	    if (!main_core.Type.isDomNode(form)) {
	      return;
	    }
	    const errorInputData = [];
	    const items = [];
	    const phoneExp = /^[\d+][\d ()-]{4,22}\d$/;
	    const rows = Array.prototype.slice.call(form.querySelectorAll('.js-form-row'));
	    (rows || []).forEach(row => {
	      const emailInput = row.querySelector("input[name='EMAIL[]']");
	      const phoneInput = row.querySelector("input[name='PHONE[]']");
	      const nameInput = row.querySelector("input[name='NAME[]']");
	      const lastNameInput = row.querySelector("input[name='LAST_NAME[]']");
	      const emailValue = emailInput.value.trim();
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled] && main_core.Type.isDomNode(phoneInput)) {
	        const phoneValue = phoneInput.value.trim();
	        if (phoneValue) {
	          if (phoneExp.test(String(phoneValue).toLowerCase())) {
	            const phoneCountryInput = row.querySelector("input[name='PHONE_COUNTRY[]']");
	            items.push({
	              PHONE: phoneValue,
	              PHONE_COUNTRY: phoneCountryInput.value.trim(),
	              NAME: nameInput.value,
	              LAST_NAME: lastNameInput.value
	            });
	          } else {
	            errorInputData.push(phoneValue);
	          }
	        }
	      } else if (babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled)[_isEmailEnabled] && emailValue) {
	        if (main_core.Validation.isEmail(emailValue)) {
	          items.push({
	            EMAIL: emailValue,
	            NAME: nameInput.value,
	            LAST_NAME: lastNameInput.value
	          });
	        } else {
	          errorInputData.push(emailValue);
	        }
	      } else if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled] && !babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled)[_isEmailEnabled] && emailValue) {
	        errorInputData.push(emailValue);
	      }
	    });
	    return [items, errorInputData];
	  }
	}
	function _checkPhoneInput2(element) {
	  const phoneExp = /^[\d+][\d ()-]{2,14}\d$/;
	  if (element.value && phoneExp.test(String(element.value).toLowerCase())) {
	    this.phoneObj.renderPhoneRow(element);
	  } else if (element.value.length === 0) {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _renderDefaultRow)[_renderDefaultRow](element)) == null ? void 0 : _babelHelpers$classPr.focus();
	  }
	}
	function _bindPhoneChecker2(element) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled] && main_core.Type.isDomNode(element)) {
	    const inputNodes = Array.prototype.slice.call(element.querySelectorAll('.js-email-phone-input'));
	    if (inputNodes) {
	      inputNodes.forEach(element => {
	        main_core.Event.bind(element, 'input', () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _checkPhoneInput)[_checkPhoneInput](element);
	        });
	      });
	    }
	  }
	}
	function _renderDefaultRow2(node) {
	  const phoneFlagBlock = node.parentNode.querySelector('[data-role="phone-block"]');
	  if (main_core.Type.isDomNode(phoneFlagBlock)) {
	    main_core.Dom.remove(phoneFlagBlock);
	  }
	  const phoneBlock = node.parentNode.querySelector('input.ui-ctl-element');
	  if (main_core.Type.isDomNode(phoneBlock)) {
	    const newInput = main_core.Tag.render(_t2 || (_t2 = _$1`
				<input
					name="${0}"
					type="text"
					maxlength="50"
					data-num="${0}"
					class="ui-ctl-element js-email-phone-input"
					placeholder="${0}"
				/>
			`), phoneBlock.name, node.getAttribute('data-num'), phoneBlock.placeholder);
	    main_core.Dom.replace(phoneBlock, newInput);
	    this.bindCloseIcons(newInput.parentNode);
	    babelHelpers.classPrivateFieldLooseBase(this, _bindPhoneChecker)[_bindPhoneChecker](newInput.parentNode);
	    main_core.Dom.remove(phoneBlock);
	    return newInput;
	  }
	  return phoneBlock;
	}
	function _getDialogInputMessage2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled] && babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled)[_isEmailEnabled]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled)[_isPhoneEnabled]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_INPUT');
	  } else {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_INPUT');
	  }
	}

	let _$2 = t => t,
	  _t$2;
	var _errorContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("errorContainer");
	var _successContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("successContainer");
	var _wrapMessage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wrapMessage");
	class MessageBar {
	  constructor(options) {
	    Object.defineProperty(this, _wrapMessage, {
	      value: _wrapMessage2
	    });
	    Object.defineProperty(this, _errorContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _successContainer, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer] = main_core.Type.isDomNode(options.errorContainer) ? options.errorContainer : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer] = main_core.Type.isDomNode(options.successContainer) ? options.successContainer : null;
	    this.hideAll();
	  }
	  showError(message) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer] || !main_core.Type.isStringFilled(message)) {
	      return;
	    }
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer], 'display', 'block');
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _wrapMessage)[_wrapMessage](message), babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]);
	  }
	  showSuccess(message) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer] || !main_core.Type.isStringFilled(message)) {
	      return;
	    }
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer]);
	    main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer], 'display', 'block');
	    main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _wrapMessage)[_wrapMessage](message), babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer]);
	  }
	  hideAll() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]) {
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer], 'display', 'none');
	      main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _errorContainer)[_errorContainer]);
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer]) {
	      main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer], 'display', 'none');
	      main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _successContainer)[_successContainer]);
	    }
	  }
	}
	function _wrapMessage2(text) {
	  return main_core.Tag.render(_t$2 || (_t$2 = _$2`<span class="ui-alert-message">${0}</span>`), BX.util.htmlspecialchars(text));
	}

	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _disableSubmitButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disableSubmitButton");
	class SubmitButton extends main_core_events.EventEmitter {
	  constructor(options) {
	    var _options$events;
	    super();
	    Object.defineProperty(this, _disableSubmitButton, {
	      value: _disableSubmitButton2
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = options.node;
	    this.setEventNamespace('BX.Intranet.Invitation.Submit');
	    main_core.Event.unbindAll(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	    if (main_core.Type.isFunction((_options$events = options.events) == null ? void 0 : _options$events.click)) {
	      main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'click', options.events.click);
	    }
	  }
	  wait() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableSubmitButton)[_disableSubmitButton](true);
	  }
	  isWaiting() {
	    return main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'ui-btn-wait');
	  }
	  ready() {
	    babelHelpers.classPrivateFieldLooseBase(this, _disableSubmitButton)[_disableSubmitButton](false);
	  }
	  disable() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'ui-btn-disabled');
	  }
	  enable() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'ui-btn-disabled');
	  }
	  isEnabled() {
	    return !main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], 'ui-btn-disabled');
	  }
	  setLabel(lable) {
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container].innerText = lable;
	  }
	  sendSuccessEvent(users) {
	    BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation:onAdd', {
	      users
	    });
	  }
	}
	function _disableSubmitButton2(isDisable) {
	  if (!main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) || !main_core.Type.isBoolean(isDisable)) {
	    return;
	  }
	  if (isDisable) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], ['ui-btn-wait', 'invite-cursor-auto']);
	  } else {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], ['ui-btn-wait', 'invite-cursor-auto']);
	  }
	}
	SubmitButton.ENABLED_STATE = 'enabled';
	SubmitButton.DISABLED_STATE = 'disabled';

	class Page {
	  constructor() {
	    main_core_events.EventEmitter.subscribe(this, 'BX.Intranet.Invitation:submit', this.onSubmit.bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:onInviteRequestSuccess', this.onInviteSuccess.bind(this));
	  }
	  render() {
	    return new HTMLElement();
	  }
	  onSubmit(event) {}
	  onInviteSuccess(event) {}
	  getSubmitButtonText() {
	    return null;
	  }
	  getButtonState() {
	    return SubmitButton.ENABLED_STATE;
	  }
	}

	var _pages = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pages");
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _first = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("first");
	var _current = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("current");
	var _history = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("history");
	class Navigation extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _pages, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _first, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _current, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _history, {
	      writable: true,
	      value: []
	    });
	    this.setEventNamespace('BX.Intranet.Navigation');
	    babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] = main_core.Type.isDomNode(options.container) ? options.container : null;
	    if (main_core.Type.isMap(options.pages)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages] = new Map([...options.pages].filter(([k, page]) => page instanceof Page));
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages] = new Map();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _first)[_first] = main_core.Type.isStringFilled(options.first) && this.has(options.first) ? options.first : babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].next().value;
	  }
	  show(code) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1] || !this.has(code)) {
	      return;
	    }
	    const page = this.get(code);
	    this.emit('onBeforeChangePage', {
	      current: this.current(),
	      new: page
	    });
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]);
	    main_core.Dom.append(page.render(), babelHelpers.classPrivateFieldLooseBase(this, _container$1)[_container$1]);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _current)[_current]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _history)[_history].push(babelHelpers.classPrivateFieldLooseBase(this, _current)[_current]);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _current)[_current] = code;
	    this.emit('onAfterChangePage', {
	      current: this.current(),
	      previous: this.prev()
	    });
	  }
	  showFirst() {
	    this.show(babelHelpers.classPrivateFieldLooseBase(this, _first)[_first]);
	  }
	  get(code) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].get(code);
	  }
	  has(code) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].has(code);
	  }
	  current() {
	    return this.get(babelHelpers.classPrivateFieldLooseBase(this, _current)[_current]);
	  }
	  getCurrentCode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _current)[_current];
	  }
	  prev() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _history)[_history].length > 0) {
	      const code = babelHelpers.classPrivateFieldLooseBase(this, _history)[_history][babelHelpers.classPrivateFieldLooseBase(this, _history)[_history].length - 1];
	      return this.get(code);
	    }
	    return null;
	  }
	  add(code, page) {
	    if (page instanceof Page) {
	      babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].set(code, page);
	    }
	  }
	  delete(code) {
	    babelHelpers.classPrivateFieldLooseBase(this, _pages)[_pages].delete(code);
	  }
	}

	var _cSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cSection");
	var _isAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	var _getAdminAllowMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAdminAllowMode");
	var _getIsAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getIsAdmin");
	var _getCSection = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCSection");
	class Analytics {
	  constructor(cSection, isAdmin) {
	    Object.defineProperty(this, _getCSection, {
	      value: _getCSection2
	    });
	    Object.defineProperty(this, _getIsAdmin, {
	      value: _getIsAdmin2
	    });
	    Object.defineProperty(this, _getAdminAllowMode, {
	      value: _getAdminAllowMode2
	    });
	    Object.defineProperty(this, _cSection, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _cSection)[_cSection] = cSection;
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] = isAdmin;
	  }
	  send() {
	    ui_analytics.sendData({
	      tool: Analytics.TOOLS,
	      category: Analytics.CATEGORY_INVITATION,
	      event: Analytics.EVENT_COPY,
	      c_section: babelHelpers.classPrivateFieldLooseBase(this, _getCSection)[_getCSection](),
	      p1: babelHelpers.classPrivateFieldLooseBase(this, _getIsAdmin)[_getIsAdmin](),
	      p2: babelHelpers.classPrivateFieldLooseBase(this, _getAdminAllowMode)[_getAdminAllowMode]()
	    });
	  }
	  sendTabData(section, subSection) {
	    if (!section) {
	      return;
	    }
	    ui_analytics.sendData({
	      tool: Analytics.TOOLS,
	      category: Analytics.CATEGORY_INVITATION,
	      event: Analytics.EVENT_TAB_VIEW,
	      c_section: section,
	      c_sub_section: subSection
	    });
	  }
	  sendOpenSliderData(section) {
	    ui_analytics.sendData({
	      tool: Analytics.TOOLS,
	      category: Analytics.CATEGORY_INVITATION,
	      event: Analytics.EVENT_OPEN_SLIDER_INVITATION,
	      c_section: section
	    });
	  }
	  sendOpenLocalEmailPopup() {
	    ui_analytics.sendData({
	      tool: Analytics.TOOLS,
	      category: Analytics.CATEGORY_INVITATION,
	      event: Analytics.EVENT_TAB_VIEW,
	      c_sub_section: Analytics.TAB_EMAIL
	    });
	  }
	  sendLocalEmailProgram() {
	    ui_analytics.sendData({
	      tool: Analytics.TOOLS,
	      category: Analytics.CATEGORY_INVITATION,
	      event: Analytics.EVENT_LOCAL_MAIL
	    });
	  }
	}
	function _getAdminAllowMode2() {
	  var _document$querySelect;
	  return (_document$querySelect = document.querySelector('#allow_register_confirm')) != null && _document$querySelect.checked ? Analytics.ADMIN_ALLOW_MODE_Y : Analytics.ADMIN_ALLOW_MODE_N;
	}
	function _getIsAdmin2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] ? Analytics.IS_ADMIN_Y : Analytics.IS_ADMIN_N;
	}
	function _getCSection2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cSection)[_cSection].source;
	}
	Analytics.TOOLS = 'Invitation';
	Analytics.TOOLS_HEADER = 'headerPopup';
	Analytics.EVENT_OPEN_SLIDER_INVITATION = 'drawer_open';
	Analytics.CATEGORY_INVITATION = 'invitation';
	Analytics.EVENT_COPY = 'copy_invitation_link';
	Analytics.ADMIN_ALLOW_MODE_Y = 'askAdminToAllow_Y';
	Analytics.ADMIN_ALLOW_MODE_N = 'askAdminToAllow_N';
	Analytics.IS_ADMIN_Y = 'isAdmin_Y';
	Analytics.IS_ADMIN_N = 'isAdmin_N';
	Analytics.EVENT_TAB_VIEW = 'tab_view';
	Analytics.EVENT_LOCAL_MAIL = 'invitation_local_mail';
	Analytics.TAB_EMAIL = 'tab_by_email';
	Analytics.TAB_MASS = 'tab_mass';
	Analytics.TAB_DEPARTMENT = 'tab_department';
	Analytics.TAB_INTEGRATOR = 'tab_integrator';
	Analytics.TAB_LINK = 'by_link';
	Analytics.TAB_REGISTRATION = 'registration';
	Analytics.TAB_EXTRANET = 'extranet';
	Analytics.TAB_AD = 'AD';
	Analytics.TAB_LOCAL_EMAIL = 'tab_by_local_email';
	Analytics.TAB_PHONE = 'tab_by_phone';

	var _componentName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("componentName");
	var _signedParameters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("signedParameters");
	class Transport {
	  constructor(options) {
	    Object.defineProperty(this, _componentName, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _signedParameters, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _componentName)[_componentName] = options.componentName;
	    babelHelpers.classPrivateFieldLooseBase(this, _signedParameters)[_signedParameters] = options.signedParameters;
	  }
	  send(request) {
	    return BX.ajax.runComponentAction(babelHelpers.classPrivateFieldLooseBase(this, _componentName)[_componentName], request.action, {
	      signedParameters: babelHelpers.classPrivateFieldLooseBase(this, _signedParameters)[_signedParameters],
	      mode: main_core.Type.isStringFilled(request.mode) ? request.mode : 'ajax',
	      method: main_core.Type.isStringFilled(request.method) ? request.method : 'post',
	      data: request.data,
	      analyticsLabel: request.analyticsLabel
	    });
	  }
	}

	let _$3 = t => t,
	  _t$3;
	var _input = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	class EmailInvitationInput {
	  constructor() {
	    Object.defineProperty(this, _input, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input] = new intranet_invitationInput.InvitationInput({
	      inputType: intranet_invitationInput.InvitationInputType.EMAIL
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].getTagSelector().setPlaceholder(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_PLACEHOLDER'));
	    Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].getTagSelector().getContainer(), 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].getTagSelector().focusTextBox();
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
	      this.clearTags();
	    });
	  }
	  onReadySaveInputHandler(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].unsubscribe('onReadySave', callback);
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].subscribe('onReadySave', callback);
	  }
	  onUnreadySaveInputHandler(callback) {
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].unsubscribe('onUnreadySave', callback);
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].subscribe('onUnreadySave', callback);
	  }
	  getContent(actionBlock = null) {
	    this.clearTags();
	    return main_core.Tag.render(_t$3 || (_t$3 = _$3`
			<div class="email-popup-container">
				<div class="email-popup-container__title">
					${0}
				</div>
				<div class="email-popup-container__description">
					${0}
				</div>
				<div class="email-popup-container__input">
					${0}
				</div>
				${0}
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_DESCRIPTION'), babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].render(), actionBlock != null ? actionBlock : '');
	  }
	  clearTags() {
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].getTagSelector().removeTags();
	  }
	  getValues() {
	    const selector = babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].getTagSelector();
	    const tags = selector.getTags();
	    const emails = [];
	    const errorElements = [];
	    tags.forEach(tag => {
	      if (tag.getEntityType() === 'email') {
	        emails.push({
	          EMAIL: tag.getTitle()
	        });
	      } else {
	        errorElements.push(tag.getTitle());
	      }
	    });
	    return {
	      emails,
	      errorElements
	    };
	  }
	}

	let _$4 = t => t,
	  _t$4;
	var _sendButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("sendButton");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _input$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _actionContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actionContent");
	var _getActionContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getActionContent");
	var _getSendButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSendButton");
	var _getCancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCancelButton");
	class InviteEmailPopup {
	  constructor(input) {
	    Object.defineProperty(this, _getCancelButton, {
	      value: _getCancelButton2
	    });
	    Object.defineProperty(this, _getSendButton, {
	      value: _getSendButton2
	    });
	    Object.defineProperty(this, _getActionContent, {
	      value: _getActionContent2
	    });
	    Object.defineProperty(this, _sendButton, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _input$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _actionContent, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1] = input;
	    babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1].onReadySaveInputHandler(this.onReadySaveInputHandler.bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1].onUnreadySaveInputHandler(this.onUnreadySaveInputHandler.bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
	      var _babelHelpers$classPr;
	      (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	      this.onReadySaveInputHandler();
	      main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
	      main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:InviteFailed', () => {
	      this.onReadySaveInputHandler();
	    });
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = main_popup.PopupManager.create({
	        id: 'email-invitation-email',
	        className: 'email-invitation-container',
	        closeIcon: true,
	        autoHide: false,
	        closeByEsc: true,
	        width: 515,
	        height: 310
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].setContent(babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1].getContent(babelHelpers.classPrivateFieldLooseBase(this, _getActionContent)[_getActionContent]()));
	    babelHelpers.classPrivateFieldLooseBase(this, _input$1)[_input$1].clearTags();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  onReadySaveInputHandler() {
	    var _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _sendButton)[_sendButton]) == null ? void 0 : _babelHelpers$classPr2.setState(null);
	  }
	  onUnreadySaveInputHandler() {
	    var _babelHelpers$classPr3;
	    (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _sendButton)[_sendButton]) == null ? void 0 : _babelHelpers$classPr3.setState(ui_buttons.ButtonState.DISABLED);
	  }
	  submitData() {
	    this.onUnreadySaveInputHandler();
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation.EmailPopup:onSubmit');
	  }
	}
	function _getActionContent2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _actionContent)[_actionContent]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _actionContent)[_actionContent] = main_core.Tag.render(_t$4 || (_t$4 = _$4`
				<div class="email-popup-container__action">
					${0}
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _getSendButton)[_getSendButton](), babelHelpers.classPrivateFieldLooseBase(this, _getCancelButton)[_getCancelButton]());
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _actionContent)[_actionContent];
	}
	function _getSendButton2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _sendButton)[_sendButton] = new ui_buttons.Button({
	    className: 'email-popup-container__action-send',
	    text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_ACTION_SEND'),
	    state: ui_buttons.ButtonState.DISABLED,
	    size: ui_buttons.Button.Size.SMALL,
	    color: ui_buttons.Button.Color.PRIMARY,
	    noCaps: true,
	    onclick: () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _sendButton)[_sendButton].isDisabled()) {
	        return;
	      }
	      this.submitData();
	    }
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _sendButton)[_sendButton].render();
	}
	function _getCancelButton2() {
	  return new ui_buttons.CancelButton({
	    text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_ACTION_CANCEL'),
	    size: ui_buttons.Button.Size.SMALL,
	    onclick: () => babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close(),
	    noCaps: true
	  }).render();
	}

	let _$5 = t => t,
	  _t$5;
	var _departmentControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _linkRegisterEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkRegisterEnabled");
	var _transport = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("transport");
	var _input$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _analytics = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _onSubmitWithLocalEmailProgram = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubmitWithLocalEmailProgram");
	var _openLocalMailProgram = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openLocalMailProgram");
	var _onSubmitWithMailService = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSubmitWithMailService");
	var _showNotification = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showNotification");
	var _getNotificationContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNotificationContent");
	var _openInvitationPageWithFilter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openInvitationPageWithFilter");
	class InviteEmailHandler {
	  constructor(options, input) {
	    Object.defineProperty(this, _openInvitationPageWithFilter, {
	      value: _openInvitationPageWithFilter2
	    });
	    Object.defineProperty(this, _getNotificationContent, {
	      value: _getNotificationContent2
	    });
	    Object.defineProperty(this, _showNotification, {
	      value: _showNotification2
	    });
	    Object.defineProperty(this, _onSubmitWithMailService, {
	      value: _onSubmitWithMailService2
	    });
	    Object.defineProperty(this, _openLocalMailProgram, {
	      value: _openLocalMailProgram2
	    });
	    Object.defineProperty(this, _onSubmitWithLocalEmailProgram, {
	      value: _onSubmitWithLocalEmailProgram2
	    });
	    Object.defineProperty(this, _departmentControl, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _linkRegisterEnabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _transport, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _input$2, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _analytics, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _transport)[_transport] = options.transport;
	    babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled)[_linkRegisterEnabled] = options.linkRegisterEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl)[_departmentControl] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2] = input;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics] = options.analytics;
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:selfChange', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled)[_linkRegisterEnabled] = event.getData().selfEnabled;
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotification)[_showNotification](true);
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:InviteFailed', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _showNotification)[_showNotification](false);
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation.EmailPopup:onSubmit', babelHelpers.classPrivateFieldLooseBase(this, _onSubmitWithMailService)[_onSubmitWithMailService].bind(this));
	  }
	  onSubmit() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled)[_linkRegisterEnabled]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSubmitWithLocalEmailProgram)[_onSubmitWithLocalEmailProgram]();
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _onSubmitWithMailService)[_onSubmitWithMailService]();
	    }
	  }
	}
	function _onSubmitWithLocalEmailProgram2() {
	  const departmentsId = babelHelpers.classPrivateFieldLooseBase(this, _departmentControl)[_departmentControl].getValues();

	  // eslint-disable-next-line promise/catch-or-return
	  babelHelpers.classPrivateFieldLooseBase(this, _transport)[_transport].send({
	    action: 'getInviteLink',
	    data: {
	      departmentsId
	    }
	  }).then(response => {
	    var _response$data;
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	    const invitationUrl = (_response$data = response.data) == null ? void 0 : _response$data.invitationLink;
	    if (main_core.Type.isStringFilled(invitationUrl)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _openLocalMailProgram)[_openLocalMailProgram](invitationUrl);
	    }
	  }, response => {
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	  });
	}
	function _openLocalMailProgram2(invitationUrl) {
	  babelHelpers.classPrivateFieldLooseBase(this, _analytics)[_analytics].sendLocalEmailProgram();
	  const subject = `subject=${encodeURIComponent(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_SUBJECT'))}`;
	  const body = `body=${encodeURIComponent(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_BODY'))} ${invitationUrl}`;
	  window.location = `mailto:?${subject}&${body}`;
	}
	function _onSubmitWithMailService2() {
	  const {
	    emails,
	    errorElements
	  } = babelHelpers.classPrivateFieldLooseBase(this, _input$2)[_input$2].getValues();
	  const errors = [];
	  if (emails.length <= 0) {
	    errors.push(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL'));
	  }
	  if (errorElements.length > 0) {
	    errors.push(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL'));
	  }
	  if (errors.length > 0) {
	    main_core_events.EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
	      errors
	    });
	    return;
	  }
	  const data = {
	    invitations: emails,
	    departmentIds: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl)[_departmentControl].getValues(),
	    tab: 'email'
	  };
	  const analyticsLabel = {
	    INVITATION_TYPE: 'invite',
	    INVITATION_COUNT: emails.length
	  };
	  main_core_events.EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
	    action: 'invite',
	    type: 'invite-email',
	    data,
	    analyticsLabel
	  });
	}
	function _showNotification2(isSuccess) {
	  const notificationOptions = {
	    id: 'invite-notification-result',
	    closeButton: true,
	    autoHideDelay: 4000,
	    autoHide: true,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _getNotificationContent)[_getNotificationContent](isSuccess)
	  };
	  const notify = BX.UI.Notification.Center.notify(notificationOptions);
	  notify.show();
	  notify.activateAutoHide();
	}
	function _getNotificationContent2(isSuccess) {
	  const title = isSuccess ? main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_SUCCESS_TITLE') : main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_FAILED_TITLE');
	  const description = isSuccess ? main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_SUCCESS_DESCRIPTION', {
	    '[LINK]': '<a class="intranet-invitation-dialog-link">',
	    '[/LINK]': '</a>'
	  }) : main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_FAILED_DESCRIPTION');
	  const content = main_core.Tag.render(_t$5 || (_t$5 = _$5`
			<div class="invite-email-notification">
				<div class="invite-email-notification__title">
					${0}
				</div>
				<div class="invite-email-notification__description">
					${0}
				</div>
			</div>
		`), title, description);
	  const link = content.querySelector('.intranet-invitation-dialog-link');
	  if (main_core.Type.isDomNode(link)) {
	    main_core.Event.unbindAll(link);
	    main_core.Event.bind(link, 'click', babelHelpers.classPrivateFieldLooseBase(this, _openInvitationPageWithFilter)[_openInvitationPageWithFilter].bind(this));
	  }
	  return content;
	}
	function _openInvitationPageWithFilter2() {
	  window.top.location = '/company/?apply_filter=Y&INVITED=Y';
	}

	let _$6 = t => t,
	  _t$6,
	  _t2$1,
	  _t3,
	  _t4,
	  _t5,
	  _t6;
	var _container$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _departmentControl$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _inviteEmailPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inviteEmailPopup");
	var _linkRegisterEnabled$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkRegisterEnabled");
	var _input$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _process = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("process");
	var _analytics$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _titleRender = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("titleRender");
	var _renderForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderForm");
	var _getNotifyBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNotifyBlock");
	var _getDepartmentBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDepartmentBlock");
	var _getMailServiceBlock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMailServiceBlock");
	var _openEmailInputPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openEmailInputPopup");
	class LocalEmailPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _openEmailInputPopup, {
	      value: _openEmailInputPopup2
	    });
	    Object.defineProperty(this, _getMailServiceBlock, {
	      value: _getMailServiceBlock2
	    });
	    Object.defineProperty(this, _getDepartmentBlock, {
	      value: _getDepartmentBlock2
	    });
	    Object.defineProperty(this, _getNotifyBlock, {
	      value: _getNotifyBlock2
	    });
	    Object.defineProperty(this, _renderForm, {
	      value: _renderForm2
	    });
	    Object.defineProperty(this, _titleRender, {
	      value: _titleRender2
	    });
	    Object.defineProperty(this, _container$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inviteEmailPopup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _linkRegisterEnabled$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _input$3, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _process, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _analytics$1, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$1)[_linkRegisterEnabled$1] = options.linkRegisterEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$1)[_departmentControl$1] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics$1)[_analytics$1] = options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3] = new EmailInvitationInput();
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process] = new InviteEmailHandler(options, babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3]);
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:selfChange', event => {
	      babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$1)[_linkRegisterEnabled$1] = event.getData().selfEnabled;
	    });
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2];
	    }
	    return main_core.Tag.render(_t$6 || (_t$6 = _$6`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-block">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _titleRender)[_titleRender](), babelHelpers.classPrivateFieldLooseBase(this, _renderForm)[_renderForm]());
	  }
	  onReadySaveInputHandler() {
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	  }
	  onUnreadySaveInputHandler() {
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitDisabled');
	  }
	  onSubmit(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _process)[_process].onSubmit();
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_LOCAL_EMAIL;
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	}
	function _titleRender2() {
	  return main_core.Tag.render(_t2$1 || (_t2$1 = _$6`
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message">
					<div class="ui-icon-set --person-letter"></div>
				</div>
				<div class="invite-title-text">
					${0}
				</div>
				<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=25375678');"></div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_TITLE_EMAIL_MSGVER_1'));
	}
	function _renderForm2() {
	  let topBlock = null;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$1)[_linkRegisterEnabled$1]) {
	    topBlock = babelHelpers.classPrivateFieldLooseBase(this, _getNotifyBlock)[_getNotifyBlock]();
	  } else {
	    topBlock = babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3].getContent();
	    babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3].onReadySaveInputHandler(this.onReadySaveInputHandler);
	    babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3].onUnreadySaveInputHandler(this.onUnreadySaveInputHandler);
	  }
	  return main_core.Tag.render(_t3 || (_t3 = _$6`
			<div class="invite-content-container">
				${0}
				${0}
				${0}
			</div>
		`), topBlock, babelHelpers.classPrivateFieldLooseBase(this, _getDepartmentBlock)[_getDepartmentBlock](), babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$1)[_linkRegisterEnabled$1] && babelHelpers.classPrivateFieldLooseBase(this, _getMailServiceBlock)[_getMailServiceBlock]());
	}
	function _getNotifyBlock2() {
	  return main_core.Tag.render(_t4 || (_t4 = _$6`
			<div class="invite-content-notify">
				<div class="invite-content-notify-wrapper">
					<div class="invite-content-notify__image"></div>
				</div>
				<div class="invite-content-notify-content">
					<div class="invite-content-notify-content__description"> 
						${0}
					</div>
					<div class="invite-content-notify-content__step">
						${0}
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_MAIL_CONTENT_DESCRIPTION'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_MAIL_CONTENT_STEPS'));
	}
	function _getDepartmentBlock2() {
	  return main_core.Tag.render(_t5 || (_t5 = _$6`
			<div class="invite-content-department">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$1)[_departmentControl$1].render());
	}
	function _getMailServiceBlock2() {
	  const text = main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_MAIL_SERVICE', {
	    '[LINK]': '<a class="invite-content-mail-service__link">',
	    '[/LINK]': '</a>'
	  });
	  const block = main_core.Tag.render(_t6 || (_t6 = _$6`
			<div class="invite-content-mail-service">
				${0}
			</div>
		`), text);
	  const link = block.querySelector('.invite-content-mail-service__link');
	  if (main_core.Type.isDomNode(link)) {
	    main_core.Event.unbindAll(link);
	    main_core.Event.bind(link, 'click', babelHelpers.classPrivateFieldLooseBase(this, _openEmailInputPopup)[_openEmailInputPopup].bind(this));
	  }
	  return block;
	}
	function _openEmailInputPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _inviteEmailPopup)[_inviteEmailPopup]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _inviteEmailPopup)[_inviteEmailPopup] = new InviteEmailPopup(babelHelpers.classPrivateFieldLooseBase(this, _input$3)[_input$3]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _analytics$1)[_analytics$1].sendOpenLocalEmailPopup();
	  babelHelpers.classPrivateFieldLooseBase(this, _inviteEmailPopup)[_inviteEmailPopup].show();
	}

	var _prepareOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareOptions");
	class Selector {
	  constructor(params) {
	    Object.defineProperty(this, _prepareOptions, {
	      value: _prepareOptions2
	    });
	    this.options = params.options;
	    this.entities = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _prepareOptions)[_prepareOptions]();
	  }
	  renderTo(target) {
	    this.tagSelector = this.renderTagSelector();
	    if (main_core.Type.isDomNode(target)) {
	      this.tagSelector.renderTo(target);
	    }
	  }
	  renderTagSelector() {
	    var _this$options;
	    const preselectedItems = [];
	    if (((_this$options = this.options) == null ? void 0 : _this$options.projectId) > 0) {
	      preselectedItems.push(['project', this.options.projectId]);
	    }
	    return new ui_entitySelector.TagSelector({
	      dialogOptions: {
	        preselectedItems,
	        entities: this.entities,
	        context: 'INTRANET_INVITATION'
	      }
	    });
	  }
	  getItems() {
	    let departments = [];
	    let projects = [];
	    const tagSelectorItems = this.tagSelector.getDialog().getSelectedItems();
	    tagSelectorItems.forEach(item => {
	      const id = parseInt(item.getId());
	      const type = item.getEntityId();
	      if (type === "department") {
	        departments.push(id);
	      } else if (type === "project") {
	        projects.push(id);
	      }
	    });
	    return {
	      departments: departments,
	      projects: projects
	    };
	  }
	}
	function _prepareOptions2() {
	  for (const type in this.options) {
	    if (!this.options.hasOwnProperty(type)) {
	      continue;
	    }
	    if (type === "project" && !!this.options[type]) {
	      var _this$options$showCre;
	      const options = {
	        fillRecentTab: true,
	        '!type': ['collab'],
	        createProjectLink: (_this$options$showCre = this.options.showCreateButton) != null ? _this$options$showCre : true
	      };
	      if (Object.prototype.hasOwnProperty.call(this.options, 'projectLimitExceeded') && Object.prototype.hasOwnProperty.call(this.options, 'projectLimitFeatureId')) {
	        options.lockProjectLink = this.options.projectLimitExceeded;
	        options.lockProjectLinkFeatureId = this.options.projectLimitFeatureId;
	      }
	      const optionValue = {
	        id: 'project',
	        options
	      };
	      if (this.options[type] === "extranet") {
	        optionValue["options"]["extranet"] = true;
	      }
	      this.entities.push(optionValue);
	    }
	  }
	}

	let _$7 = t => t,
	  _t$7,
	  _t2$2,
	  _t3$1;
	var _container$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _inputsFactory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputsFactory");
	var _smsAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("smsAvailable");
	var _useOnlyPhone = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useOnlyPhone");
	var _onClickSwitchMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickSwitchMode");
	var _onClickAddInputRow = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickAddInputRow");
	var _departmentControl$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _titleRender$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("titleRender");
	var _renderForm$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderForm");
	var _getValidateError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getValidateError");
	var _getEmptyError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyError");
	class EmailPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getEmptyError, {
	      value: _getEmptyError2
	    });
	    Object.defineProperty(this, _getValidateError, {
	      value: _getValidateError2
	    });
	    Object.defineProperty(this, _renderForm$1, {
	      value: _renderForm2$1
	    });
	    Object.defineProperty(this, _titleRender$1, {
	      value: _titleRender2$1
	    });
	    Object.defineProperty(this, _container$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputsFactory, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _smsAvailable, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useOnlyPhone, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onClickSwitchMode, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onClickAddInputRow, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$2, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory)[_inputsFactory] = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable)[_smsAvailable] = options.smsAvailable === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone] = options.useLocalEmailProgram === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickSwitchMode)[_onClickSwitchMode] = main_core.Type.isFunction(options.onClickSwitchMode) ? options.onClickSwitchMode : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow)[_onClickAddInputRow] = main_core.Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$2)[_departmentControl$2] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3];
	    }
	    const title = babelHelpers.classPrivateFieldLooseBase(this, _titleRender$1)[_titleRender$1]();
	    const form = babelHelpers.classPrivateFieldLooseBase(this, _renderForm$1)[_renderForm$1]();
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory)[_inputsFactory].renderInputRowsTo(form.querySelector('div[data-role="rows-container"]'), 5);
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$2)[_departmentControl$2].renderTo(form.querySelector('form'));
	    babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3] = main_core.Tag.render(_t$7 || (_t$7 = _$7`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-block">
				${0}
				${0}
			</div>
		`), title, form);
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$3)[_container$3];
	  }
	  getAnalyticTab() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone] ? Analytics.TAB_PHONE : Analytics.TAB_EMAIL;
	  }
	  onSubmit(event) {
	    var _event$getData;
	    const formNode = this.render().querySelector('form');
	    const [items, errorInputData] = babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory)[_inputsFactory].parseEmailAndPhone(formNode);
	    const errors = [];
	    if (errorInputData.length > 0) {
	      errors.push(`${babelHelpers.classPrivateFieldLooseBase(this, _getValidateError)[_getValidateError]()}: ${errorInputData.join(', ')}`);
	    }
	    if (items.length <= 0) {
	      errors.push(babelHelpers.classPrivateFieldLooseBase(this, _getEmptyError)[_getEmptyError]());
	    }
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    if (errors.length > 0) {
	      main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	        errors
	      });
	      return;
	    }
	    const data = {
	      invitations: items,
	      departmentIds: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$2)[_departmentControl$2].getValues(),
	      tab: 'email'
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'invite',
	      INVITATION_COUNT: items.length
	    };
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'invite',
	      data,
	      analyticsLabel
	    });
	  }
	  onInviteSuccess(event) {
	    var _this$render$querySel;
	    (_this$render$querySel = this.render().querySelector('form')) == null ? void 0 : _this$render$querySel.reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$2)[_departmentControl$2].reset();
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	}
	function _titleRender2$1() {
	  const suffix = babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone] ? 'PHONE_MSGVER_1' : babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable)[_smsAvailable] ? 'EMAIL_AND_PHONE' : 'EMAIL';
	  const code = babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone] || babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable)[_smsAvailable] ? '21141922' : '25375678';
	  return main_core.Tag.render(_t2$2 || (_t2$2 = _$7`
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message">
					<div class="ui-icon-set --person-letter"></div>
				</div>
				<div class="invite-title-text">
					${0}
				</div>
				<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=${0}');"></div>
			</div>
		`), main_core.Loc.getMessage(`INTRANET_INVITE_DIALOG_TITLE_${suffix}`), code);
	}
	function _renderForm2$1() {
	  const form = main_core.Tag.render(_t3$1 || (_t3$1 = _$7`
			<div class="invite-content-container">
				<form method="POST" name="INVITE_DIALOG_FORM" class="invite-form-container">
					<div data-role="rows-container"></div>
					<div class="invite-form-buttons --border-bottom --pt-3">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round"
								  data-role="invite-more"
							>
								${0}
							</span>
						<span style="padding: 0 10px;">${0}</span>
						<span
							class="invite-content__switch_button swith-email-invitation-persons-mode"
						>
								${0}
							</span>
					</div>
				</form>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_OR'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MASSIVE'));
	  const moreButton = form.querySelector("[data-role='invite-more']");
	  if (main_core.Type.isDomNode(moreButton)) {
	    main_core.Event.unbindAll(moreButton);
	    main_core.Event.bind(moreButton, 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory)[_inputsFactory].renderInputRowTo(form.querySelector('div[data-role="rows-container"]'));
	      if (babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow)[_onClickAddInputRow]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow)[_onClickAddInputRow]();
	      }
	    });
	  }
	  const switchBtn = form.querySelector('.swith-email-invitation-persons-mode');
	  if (main_core.Type.isDomNode(switchBtn)) {
	    main_core.Event.unbindAll(switchBtn);
	    main_core.Event.bind(switchBtn, 'click', babelHelpers.classPrivateFieldLooseBase(this, _onClickSwitchMode)[_onClickSwitchMode]);
	  }
	  return form;
	}
	function _getValidateError2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_VALIDATE_ERROR');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable)[_smsAvailable]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR');
	  }
	  return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_VALIDATE_ERROR');
	}
	function _getEmptyError2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone)[_useOnlyPhone]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_EMPTY_ERROR');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable)[_smsAvailable]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR');
	  }
	  return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_EMPTY_ERROR');
	}

	let _$8 = t => t,
	  _t$8;
	var _container$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _inputsFactory$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputsFactory");
	var _tagSelectorGroup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelectorGroup");
	var _onClickAddInputRow$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickAddInputRow");
	class ExtranetPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _container$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputsFactory$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tagSelectorGroup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onClickAddInputRow$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$1)[_inputsFactory$1] = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup)[_tagSelectorGroup] = options.tagSelectorGroup instanceof ui_entitySelector.TagSelector ? options.tagSelectorGroup : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$1)[_onClickAddInputRow$1] = main_core.Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
	  }
	  render() {
	    var _babelHelpers$classPr;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4] = main_core.Tag.render(_t$8 || (_t$8 = _$8`
			<div class="invite-wrap js-intranet-invitation-block" data-role="extranet-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message">
						<div class="ui-icon-set --earth"></div>
					</div>
					<div class="invite-title-text">${0}</div>
				</div>
				<div class="invite-content-container">
					<form method="POST" name="EXTRANET_DIALOG_FORM" class="invite-form-container">
						<div class="invite-form-row" style="margin-bottom: 15px;">
							<div class="invite-form-col">
								<div class="ui-ctl-label-text">${0}</div>
								<div data-role="entity-selector-container"></div>
							</div>
						</div>
						<div data-role="rows-container"></div>
						<div class="invite-form-buttons">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
								${0}
							</span>
						</div>
					</form>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EXTRANET_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EXTRANET_GROUP'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE'));
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup)[_tagSelectorGroup]) == null ? void 0 : _babelHelpers$classPr.renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4].querySelector('[data-role="entity-selector-container"]'));
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$1)[_inputsFactory$1].renderInputRowsTo(babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4].querySelector('div[data-role="rows-container"]'), 3);
	    const moreButton = babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4].querySelector("[data-role='invite-more']");
	    if (main_core.Type.isDomNode(moreButton)) {
	      main_core.Event.unbindAll(moreButton);
	      main_core.Event.bind(moreButton, 'click', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$1)[_inputsFactory$1].renderInputRowTo(babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4].querySelector('div[data-role="rows-container"]'));
	        if (babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$1)[_onClickAddInputRow$1]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$1)[_onClickAddInputRow$1]();
	        }
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$4)[_container$4];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_EXTRANET;
	  }
	  onSubmit(event) {
	    var _event$getData;
	    const formNode = this.render().querySelector('form');
	    const [items, errorInputData] = babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$1)[_inputsFactory$1].parseEmailAndPhone(formNode);
	    const errors = [];
	    if (errorInputData.length > 0) {
	      errors.push(`${main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR')}: ${errorInputData.join(', ')}`);
	    }
	    if (items.length <= 0) {
	      errors.push(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR'));
	    }
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    if (errors.length > 0) {
	      main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	        errors
	      });
	      return;
	    }
	    const tagSelectorItems = babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup)[_tagSelectorGroup].getDialog().getSelectedItems();
	    const projectIds = [];
	    tagSelectorItems.forEach(item => {
	      const id = parseInt(item.getId(), 10);
	      projectIds.push(id);
	    });
	    const data = {
	      invitations: items,
	      workgroupIds: projectIds,
	      tab: 'email'
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'extranet',
	      INVITATION_COUNT: items.length
	    };
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'extranet',
	      data,
	      analyticsLabel
	    });
	  }
	  onInviteSuccess(event) {
	    var _this$render$querySel;
	    (_this$render$querySel = this.render().querySelector('form')) == null ? void 0 : _this$render$querySel.reset();
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	}

	let _$9 = t => t,
	  _t$9;
	var _container$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _inputsFactory$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputsFactory");
	var _tagSelectorGroup$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelectorGroup");
	var _onClickAddInputRow$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickAddInputRow");
	var _departmentControl$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	class GroupPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _container$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputsFactory$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tagSelectorGroup$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onClickAddInputRow$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$3, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$2)[_inputsFactory$2] = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$1)[_tagSelectorGroup$1] = options.tagSelectorGroup instanceof ui_entitySelector.TagSelector ? options.tagSelectorGroup : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$2)[_onClickAddInputRow$2] = main_core.Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$3)[_departmentControl$3] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5] = main_core.Tag.render(_t$9 || (_t$9 = _$9`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-with-group-dp-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message">
						<div class="ui-icon-set --group"></div>
					</div>
					<div class="invite-title-text">
						${0}
					</div>
				</div>
				<div class="invite-content-container">
					<form method="POST" name="INVITE_WITH_GROUP_DP_DIALOG_FORM" class="invite-form-container">
						<div data-role="rows-container"></div>
						<div class="invite-form-buttons --border-bottom --pt-3 --mb-17">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
								${0}
							</span>
						</div>
						<div class="invite-form-row">
							<div class="invite-form-col">
								<div class="invite-content__field-lable">${0}</div>
								<div data-role="entity-selector-container"></div>
							</div>
						</div>
						<div class="invite-form-row" id="intranet-invitation__department-control-palce">
						</div>
					</form>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_INPUT'));
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$2)[_inputsFactory$2].renderInputRowsTo(babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].querySelector('div[data-role="rows-container"]'), 3);
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$1)[_tagSelectorGroup$1].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].querySelector('[data-role="entity-selector-container"]'));
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$3)[_departmentControl$3].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].querySelector('#intranet-invitation__department-control-palce'));
	    const moreButton = babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].querySelector("[data-role='invite-more']");
	    if (main_core.Type.isDomNode(moreButton)) {
	      main_core.Event.unbindAll(moreButton);
	      main_core.Event.bind(moreButton, 'click', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$2)[_inputsFactory$2].renderInputRowTo(babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5].querySelector('div[data-role="rows-container"]'));
	        if (babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$2)[_onClickAddInputRow$2]) {
	          babelHelpers.classPrivateFieldLooseBase(this, _onClickAddInputRow$2)[_onClickAddInputRow$2]();
	        }
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$5)[_container$5];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_DEPARTMENT;
	  }
	  onSubmit(event) {
	    var _event$getData;
	    const formNode = this.render().querySelector('form');
	    const [items, errorInputData] = babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$2)[_inputsFactory$2].parseEmailAndPhone(formNode);
	    const errors = [];
	    if (errorInputData.length > 0) {
	      errors.push(`${main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR')}: ${errorInputData.join(', ')}`);
	    }
	    if (items.length <= 0) {
	      errors.push(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR'));
	    }
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    if (errors.length > 0) {
	      main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	        errors
	      });
	      return;
	    }
	    const tagSelectorItems = babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$1)[_tagSelectorGroup$1].getDialog().getSelectedItems();
	    const projectIds = [];
	    tagSelectorItems.forEach(item => {
	      const id = parseInt(item.getId(), 10);
	      projectIds.push(id);
	    });
	    const data = {
	      invitations: items,
	      departmentIds: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$3)[_departmentControl$3].getValues(),
	      workgroupIds: projectIds,
	      tab: 'group'
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'withGroupOrDepartment',
	      INVITATION_COUNT: items.length
	    };
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'inviteWithGroupDp',
	      data,
	      analyticsLabel
	    });
	  }
	  onInviteSuccess(event) {
	    var _this$render$querySel;
	    (_this$render$querySel = this.render().querySelector('form')) == null ? void 0 : _this$render$querySel.reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$3)[_departmentControl$3].reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$1)[_tagSelectorGroup$1].removeTags();
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	}

	let _$a = t => t,
	  _t$a,
	  _t2$3;
	var _container$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	class IntegratorPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _container$6, {
	      writable: true,
	      value: void 0
	    });
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6] = main_core.Tag.render(_t$a || (_t$a = _$a`
			<div class="invite-wrap js-intranet-invitation-block" data-role="integrator-block">
				<form method="POST" name="INTEGRATOR_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-mass">
							<div class="ui-icon-set --persons-3"></div>
						</div>
					<div class="invite-title-text">${0}</div>
					<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=7725333');"></div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container">
								<div class="invite-form-row">
									<div class="invite-form-col">
										<div class="invite-content__field-lable">${0}</div>
										<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
											<input 
												type="text" 
												class="ui-ctl-element" 
												value="" 
												maxlength="50"
												name="integrator_email" 
												id="integrator_email" 
												placeholder="${0}"
											/>
											<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		`), main_core.Loc.getMessage('BX24_INVITE_DIALOG_TAB_INTEGRATOR_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL'));
	    const input = babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6].querySelector('input');
	    const closeIcon = input.nextElementSibling;
	    main_core.Event.bind(input, 'input', () => {
	      main_core.Dom.style(closeIcon, 'display', input.value === '' ? 'none' : 'block');
	    });
	    main_core.Event.bind(closeIcon, 'click', event => {
	      event.preventDefault();
	      main_core.Dom.style(closeIcon, 'display', 'none');
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$6)[_container$6];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_INTEGRATOR;
	  }
	  onSubmit(event) {
	    const formNode = this.render().querySelector('form');
	    const data = {
	      integrator_email: formNode.integrator_email.value
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'integrator'
	    };
	    const message = main_core.Tag.render(_t2$3 || (_t2$3 = _$a`
			<div class="invite-integrator-confirm-message">
				${0}
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_DESCRIPTION'));
	    const moreLink = message.querySelector('span');
	    main_core.Event.bind(moreLink, 'click', () => {
	      top.BX.Helper.show('redirect=detail&code=20682986');
	    });
	    const request = {
	      action: 'inviteIntegrator',
	      data,
	      analyticsLabel
	    };
	    const popup = new main_popup.Popup({
	      id: 'integrator-confirm-invitation-popup',
	      maxWidth: 500,
	      closeIcon: false,
	      overlay: true,
	      contentPadding: 10,
	      titleBar: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_TITLE'),
	      content: message,
	      offsetLeft: 100,
	      buttons: [new ui_buttons.CreateButton({
	        text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_YES'),
	        onclick: () => {
	          var _event$getData;
	          const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	          main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', request);
	          popup.close();
	        }
	      }), new ui_buttons.CancelButton({
	        text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_NO'),
	        onclick: () => {
	          popup.close();
	        }
	      })],
	      events: {
	        onClose: () => {
	          popup.destroy();
	        }
	      }
	    });
	    popup.show();
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	  onInviteSuccess(event) {
	    var _this$render$querySel;
	    (_this$render$querySel = this.render().querySelector('form')) == null ? void 0 : _this$render$querySel.reset();
	  }
	}

	var _container$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _isEnabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEnabled");
	var _analytics$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _transport$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("transport");
	var _departmentControl$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _page = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("page");
	class SelfRegister {
	  constructor(options) {
	    Object.defineProperty(this, _container$7, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isEnabled, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _transport$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _page, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7] = options.container;
	    babelHelpers.classPrivateFieldLooseBase(this, _isEnabled)[_isEnabled] = options.isSelfRegisterEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics$2)[_analytics$2] = options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _transport$1)[_transport$1] = options.transport;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$4)[_departmentControl$4] = options.departmentControl;
	    babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] = options.page;
	    if (main_core.Type.isDomNode(babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7])) {
	      this.bindActions();
	      const switcherNode = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector('.invite-dialog-fast-reg-control-switcher');
	      this.switcher = new ui_switcher.Switcher({
	        inputName: 'allow_register',
	        id: 'allow_register',
	        checked: babelHelpers.classPrivateFieldLooseBase(this, _isEnabled)[_isEnabled],
	        node: switcherNode,
	        size: ui_switcher.SwitcherSize.small,
	        handlers: {
	          toggled: this.toggleSettings.bind(this)
	        }
	      });
	    }
	    const generateLinkBtnNode = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='selfRegenerateSecretButton']");
	    this.generateLinkHint = null;
	    main_core.Event.bind(generateLinkBtnNode, 'mouseover', event => {
	      this.generateLinkHint = this.showHintPopup(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_WARNING_MSGVER_1'), generateLinkBtnNode);
	    });
	    main_core.Event.bind(generateLinkBtnNode, 'mouseout', () => {
	      var _this$generateLinkHin;
	      (_this$generateLinkHin = this.generateLinkHint) == null ? void 0 : _this$generateLinkHin.destroy();
	      this.generateLinkHint = null;
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:confirmShutdown', event => {
	      const data = event.getData();
	      this.showNotificationPopup(data);
	    });
	  }
	  bindActions() {
	    const regenerateButton = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='selfRegenerateSecretButton']");
	    if (main_core.Type.isDomNode(regenerateButton)) {
	      main_core.Event.bind(regenerateButton, 'click', () => {
	        this.regenerateSecret();
	      });
	    }
	    const copyRegisterUrlButton = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='copyRegisterUrlButton']");
	    if (main_core.Type.isDomNode(copyRegisterUrlButton)) {
	      main_core.Event.bind(copyRegisterUrlButton, 'click', () => {
	        this.copyRegisterUrl();
	      });
	    }
	    const allowRegisterConfirm = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='allowRegisterConfirm']");
	    if (main_core.Type.isDomNode(allowRegisterConfirm)) {
	      main_core.Event.bind(allowRegisterConfirm, 'change', () => {
	        main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	        babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].setButtonState(SubmitButton.ENABLED_STATE);
	        this.toggleWhiteList(allowRegisterConfirm);
	      });
	    }
	    const selfWhiteList = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='selfWhiteList']");
	    if (main_core.Type.isDomNode(selfWhiteList)) {
	      main_core.Event.bind(selfWhiteList, 'input', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].setButtonState(SubmitButton.ENABLED_STATE);
	        main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	      });
	    }
	  }
	  regenerateSecret() {
	    babelHelpers.classPrivateFieldLooseBase(this, _transport$1)[_transport$1].send({
	      action: 'self',
	      data: {
	        allow_register_secret: main_core.Text.getRandom(8)
	      }
	    }).then(response => {
	      top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_SUCCESS'),
	        autoHideDelay: 2500
	      });
	    }, response => {
	      top.BX.UI.Notification.Center.notify({
	        content: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_ERROR'),
	        autoHideDelay: 2500
	      });
	    });
	  }
	  copyRegisterUrl() {
	    const copyBtnNode = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='copyRegisterUrlButton']");
	    if (main_core.Dom.hasClass(copyBtnNode, 'ui-btn-wait')) {
	      return;
	    }
	    main_core.Dom.addClass(copyBtnNode, 'ui-btn-wait');
	    babelHelpers.classPrivateFieldLooseBase(this, _transport$1)[_transport$1].send({
	      action: 'getInviteLink',
	      data: {
	        departmentsId: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$4)[_departmentControl$4].getValues()
	      }
	    }).then(response => {
	      var _response$data;
	      main_core.Dom.removeClass(copyBtnNode, 'ui-btn-wait');
	      const invitationUrl = (_response$data = response.data) == null ? void 0 : _response$data.invitationLink;
	      if (main_core.Type.isStringFilled(invitationUrl)) {
	        this.copyToClipboard(invitationUrl).then(r => {
	          top.BX.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('BX24_INVITE_DIALOG_COPY_URL'),
	            autoHideDelay: 2500
	          });
	        }).catch(e => {
	          console.log(e);
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _analytics$2)[_analytics$2].send();
	      }
	    }, response => {
	      main_core.Dom.removeClass(copyBtnNode, 'ui-btn-wait');
	    });
	  }
	  async copyToClipboard(textToCopy) {
	    var _BX$clipboard;
	    if (!main_core.Type.isString(textToCopy)) {
	      return Promise.reject();
	    }

	    // navigator.clipboard defined only if window.isSecureContext === true
	    // so or https should be activated, or localhost address
	    if (window.isSecureContext && navigator.clipboard) {
	      // safari not allowed clipboard manipulation as result of ajax request
	      // so timeout is hack for this, to prevent "not have permission"
	      return new Promise((resolve, reject) => {
	        setTimeout(() => navigator.clipboard.writeText(textToCopy).then(() => resolve()).catch(e => reject(e)), 0);
	      });
	    }
	    return (_BX$clipboard = BX.clipboard) != null && _BX$clipboard.copy(textToCopy) ? Promise.resolve() : Promise.reject();
	  }
	  showHintPopup(message, bindNode) {
	    if (!main_core.Type.isDomNode(bindNode) || !message) {
	      return;
	    }
	    const popup = new BX.PopupWindow('inviteHint' + main_core.Text.getRandom(8), bindNode, {
	      content: message,
	      zIndex: 15000,
	      angle: true,
	      offsetTop: 0,
	      offsetLeft: 50,
	      closeIcon: false,
	      autoHide: true,
	      darkMode: true,
	      overlay: false,
	      maxWidth: 400,
	      events: {
	        onAfterPopupShow() {
	          setTimeout(() => {
	            this.close();
	          }, 4000);
	        }
	      }
	    });
	    popup.show();
	    return popup;
	  }
	  showNotificationPopup(data) {
	    const popup = new BX.PopupWindow({
	      className: 'confirm-self-register',
	      titleBar: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_TITLE'),
	      content: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_TEXT'),
	      width: 364,
	      height: 188,
	      closeIcon: true,
	      buttons: [new BX.UI.Button({
	        text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_OK'),
	        color: BX.UI.Button.Color.PRIMARY,
	        round: true,
	        events: {
	          click() {
	            popup.close();
	            main_core_events.EventEmitter.emit(data.page, 'BX.Intranet.Invitation:submit', {
	              context: data.context,
	              isConfirm: true
	            });
	          }
	        }
	      }), new BX.UI.ApplyButton({
	        text: main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_CANCEL'),
	        round: true,
	        events: {
	          click() {
	            popup.close();
	          }
	        }
	      })],
	      events: {
	        onClose: () => {
	          main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
	        }
	      }
	    });
	    popup.show();
	  }
	  toggleSettings() {
	    main_core_events.EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
	    babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].setButtonState(SubmitButton.ENABLED_STATE);
	    const controlBlock = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector(".js-invite-dialog-fast-reg-control-container");
	    if (main_core.Type.isDomNode(controlBlock)) {
	      main_core.Dom.toggleClass(controlBlock, 'disallow-registration');
	    }
	    const settingsBlock = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='selfSettingsBlock']");
	    if (main_core.Type.isDomNode(settingsBlock)) {
	      main_core.Dom.style(settingsBlock, 'display', this.switcher.checked ? 'block' : 'none');
	    }
	  }
	  toggleWhiteList(inputElement) {
	    const selfWhiteList = babelHelpers.classPrivateFieldLooseBase(this, _container$7)[_container$7].querySelector("[data-role='selfWhiteList']");
	    if (main_core.Type.isDomNode(selfWhiteList)) {
	      main_core.Dom.style(selfWhiteList, 'display', inputElement.checked ? 'block' : 'none');
	    }
	  }
	}

	let _$b = t => t,
	  _t$b,
	  _t2$4,
	  _t3$2,
	  _t4$1,
	  _t5$1,
	  _t6$1,
	  _t7;
	var _container$8 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _isAdmin$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	var _isCloud = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCloud");
	var _fastRegistrationAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fastRegistrationAvailable");
	var _needConfirmRegistration = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("needConfirmRegistration");
	var _wishlist = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("wishlist");
	var _departmentControl$5 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _linkRegisterEnabled$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("linkRegisterEnabled");
	var _analytics$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("analytics");
	var _transport$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("transport");
	var _btnState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("btnState");
	var _renderCopyBtnDescription = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderCopyBtnDescription");
	var _renderUpdateLinkBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderUpdateLinkBtn");
	var _renderFastRegistrationSwitcher = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFastRegistrationSwitcher");
	var _renderConfirmRegistrationCheckbox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderConfirmRegistrationCheckbox");
	var _renderWhiteListField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderWhiteListField");
	var _renderWarningContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderWarningContainer");
	class LinkPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _renderWarningContainer, {
	      value: _renderWarningContainer2
	    });
	    Object.defineProperty(this, _renderWhiteListField, {
	      value: _renderWhiteListField2
	    });
	    Object.defineProperty(this, _renderConfirmRegistrationCheckbox, {
	      value: _renderConfirmRegistrationCheckbox2
	    });
	    Object.defineProperty(this, _renderFastRegistrationSwitcher, {
	      value: _renderFastRegistrationSwitcher2
	    });
	    Object.defineProperty(this, _renderUpdateLinkBtn, {
	      value: _renderUpdateLinkBtn2
	    });
	    Object.defineProperty(this, _renderCopyBtnDescription, {
	      value: _renderCopyBtnDescription2
	    });
	    Object.defineProperty(this, _container$8, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isAdmin$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isCloud, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _fastRegistrationAvailable, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _needConfirmRegistration, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _wishlist, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$5, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _linkRegisterEnabled$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _analytics$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _transport$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _btnState, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] = options.isAdmin === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isCloud)[_isCloud] = options.isCloud === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _fastRegistrationAvailable)[_fastRegistrationAvailable] = options.fastRegistrationAvailable === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _needConfirmRegistration)[_needConfirmRegistration] = options.needConfirmRegistration === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _wishlist)[_wishlist] = main_core.Type.isStringFilled(options.wishlist) ? options.wishlist : '';
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$5)[_departmentControl$5] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$2)[_linkRegisterEnabled$2] = options.linkRegisterEnabled;
	    babelHelpers.classPrivateFieldLooseBase(this, _analytics$3)[_analytics$3] = options.analytics;
	    babelHelpers.classPrivateFieldLooseBase(this, _transport$2)[_transport$2] = options.transport;
	    babelHelpers.classPrivateFieldLooseBase(this, _btnState)[_btnState] = SubmitButton.DISABLED_STATE;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8] = main_core.Tag.render(_t$b || (_t$b = _$b`
			<div class="invite-wrap js-intranet-invitation-block" data-role="self-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-link">
						<div class="ui-icon-set --link-3"></div>
					</div>
					<div class="invite-title-text">${0}</div>
					<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=6546149');"></div>
				</div>
				<form method="POST" name="SELF_DIALOG_FORM">
					${0}
					<div class="invite-content-container --department-bg">
						<div class="invite-form-container">
							<div 
								style="border-top: none; ${0}" 
								data-role="selfSettingsBlock" 
								id="intranet-dialog-tab-content-self-block" 
								class="invite-dialog-inv-link-block"
							>
								<div id="invitation-department-status" class="invitation-department__status"></div>
								<div>
									${0}
									<div class="intranet-invitation__link-department-control"></div>
									<div class="invite-form-container-reg-row">
										<span class="ui-btn ui-btn-primary ui-btn-themes ui-btn-icon-copy invite-form-btn-copy" data-role="copyRegisterUrlButton">
											${0}
										</span>
										${0}
									</div>
									${0}
									${0}
								</div>
							</div>
						</div>
					</div>
				</form>
				${0}
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] ? babelHelpers.classPrivateFieldLooseBase(this, _renderFastRegistrationSwitcher)[_renderFastRegistrationSwitcher]() : '', babelHelpers.classPrivateFieldLooseBase(this, _fastRegistrationAvailable)[_fastRegistrationAvailable] ? '' : 'display: none;', babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] ? '' : babelHelpers.classPrivateFieldLooseBase(this, _renderCopyBtnDescription)[_renderCopyBtnDescription](), main_core.Loc.getMessage('BX24_INVITE_DIALOG_COPY_LINK'), babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] ? babelHelpers.classPrivateFieldLooseBase(this, _renderUpdateLinkBtn)[_renderUpdateLinkBtn]() : '', babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] && babelHelpers.classPrivateFieldLooseBase(this, _isCloud)[_isCloud] ? babelHelpers.classPrivateFieldLooseBase(this, _renderConfirmRegistrationCheckbox)[_renderConfirmRegistrationCheckbox]() : '', babelHelpers.classPrivateFieldLooseBase(this, _isCloud)[_isCloud] ? babelHelpers.classPrivateFieldLooseBase(this, _renderWhiteListField)[_renderWhiteListField]() : '', !babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] && !babelHelpers.classPrivateFieldLooseBase(this, _fastRegistrationAvailable)[_fastRegistrationAvailable] ? babelHelpers.classPrivateFieldLooseBase(this, _renderWarningContainer)[_renderWarningContainer]() : '');
	    main_core.Dom.prepend(babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$5)[_departmentControl$5].render(), babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8].querySelector('.intranet-invitation__link-department-control'));
	    new SelfRegister({
	      container: babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8],
	      isSelfRegisterEnabled: babelHelpers.classPrivateFieldLooseBase(this, _linkRegisterEnabled$2)[_linkRegisterEnabled$2],
	      analytics: babelHelpers.classPrivateFieldLooseBase(this, _analytics$3)[_analytics$3],
	      transport: babelHelpers.classPrivateFieldLooseBase(this, _transport$2)[_transport$2],
	      departmentControl: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$5)[_departmentControl$5],
	      page: this
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$8)[_container$8];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_LINK;
	  }
	  onSubmit(event) {
	    var _formNode$allow_regis, _formNode$allow_regis2, _formNode$allow_regis3, _event$getData, _event$getData2;
	    const formNode = this.render().querySelector('form');
	    const data = {
	      allow_register: formNode.allow_register.value,
	      allow_register_confirm: formNode != null && (_formNode$allow_regis = formNode.allow_register_confirm) != null && _formNode$allow_regis.checked ? 'Y' : 'N',
	      allow_register_whitelist: (_formNode$allow_regis2 = formNode == null ? void 0 : (_formNode$allow_regis3 = formNode.allow_register_whitelist) == null ? void 0 : _formNode$allow_regis3.value) != null ? _formNode$allow_regis2 : ''
	    };
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    const isConfirm = (_event$getData2 = event.getData()) == null ? void 0 : _event$getData2.isConfirm;
	    if (formNode.allow_register.value === 'N' && !isConfirm) {
	      main_core_events.EventEmitter.emit('BX.Intranet.Invitation:confirmShutdown', {
	        page: this,
	        context
	      });
	      return;
	    }
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'self',
	      data
	    });
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_SAVE');
	  }
	  getButtonState() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _btnState)[_btnState];
	  }
	  setButtonState(state) {
	    babelHelpers.classPrivateFieldLooseBase(this, _btnState)[_btnState] = state;
	  }
	}
	function _renderCopyBtnDescription2() {
	  return main_core.Tag.render(_t2$4 || (_t2$4 = _$b`
			<div class="invite-form__copy-button-description">
				${0}
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_COPY_BUTTON_DESCRIPTION'));
	}
	function _renderUpdateLinkBtn2() {
	  return main_core.Tag.render(_t3$2 || (_t3$2 = _$b`
			<span data-role="selfRegenerateSecretButton">
				<a
				href="javascript:void(0)"
				class="invite-dialog-update-link"
				>
					${0}
				</a>
			</span>
		`), main_core.Loc.getMessage('BX24_INVITE_DIALOG_REGISTER_NEW_LINK'));
	}
	function _renderFastRegistrationSwitcher2() {
	  return main_core.Tag.render(_t4$1 || (_t4$1 = _$b`
			<label class="invite-dialog-fast-reg-control-container js-invite-dialog-fast-reg-control-container ${0}" for="allow_register">
				<div class="invite-dialog-fast-reg-control-switcher" data-role="self-switcher"></div>
				<span class="invite-dialog-fast-reg-control-label">
					<div class="invite-dialog-fast-reg-control-label-title">${0}</div>
				</span>
			</label>
		`), babelHelpers.classPrivateFieldLooseBase(this, _fastRegistrationAvailable)[_fastRegistrationAvailable] ? '' : 'disallow-registration', main_core.Loc.getMessage('INTRANET_INVITE_ALLOW_INVITATION_LINK'));
	}
	function _renderConfirmRegistrationCheckbox2() {
	  return main_core.Tag.render(_t5$1 || (_t5$1 = _$b`
			<div style="padding-top: 12px;">
				<label class="ui-ctl ui-ctl-w100 ui-ctl-checkbox invite-form-check-box">
					<input
					type="checkbox"
					class="ui-ctl-element"
					name="allow_register_confirm"
					id="allow_register_confirm"
					data-role="allowRegisterConfirm"
					${0}
					${0}
					/>
					<div class="ui-ctl-label-text">${0}</div>
				</label>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _needConfirmRegistration)[_needConfirmRegistration] ? 'checked' : '', babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] ? '' : 'disabled', main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_TYPE'));
	}
	function _renderWhiteListField2() {
	  return main_core.Tag.render(_t6$1 || (_t6$1 = _$b`
		<div id="intranet-dialog-tab-content-self-whitelist" data-role="selfWhiteList" ${0}>
			<span class="invite-form-ctl-title">
				${0}
			</span>
			<label class="ui-ctl ui-ctl-w75 ui-ctl-textbox">
				<input
				type="text"
				${0}
				class="ui-ctl-element"
				name="allow_register_whitelist"
				value="${0}"
				placeholder="${0}"
				/>
			</label>
		</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _needConfirmRegistration)[_needConfirmRegistration] ? '' : 'style="display: none"', main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_FAST_REG_DOMAINS'), babelHelpers.classPrivateFieldLooseBase(this, _isAdmin$1)[_isAdmin$1] ? '' : 'disabled', babelHelpers.classPrivateFieldLooseBase(this, _wishlist)[_wishlist], main_core.Loc.getMessage('BX24_INVITE_DIALOG_REGISTER_TYPE_DOMAINS_PLACEHOLDER'));
	}
	function _renderWarningContainer2() {
	  return main_core.Tag.render(_t7 || (_t7 = _$b`
			<div class="intranet-invitation__error-box">
				<div class="intranet-invitation__error-icon"></div>
				<div class="intranet-invitation__error-title">${0}</div>
				<div class="intranet-invitation__error-desc">${0}</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_ALERT_INVITATION_LINK_DISABLED'), main_core.Loc.getMessage('INTRANET_INVITE_ALERT_INVITATION_LINK_DISABLED_DESCRIPTION'));
	}

	var _input$4 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _invitationType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("invitationType");
	var _isPhoneEnabled$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isPhoneEnabled");
	var _isEmailEnabled$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEmailEnabled");
	var _getDialogInputMessage$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDialogInputMessage");
	class MassInvitationField {
	  constructor(options) {
	    Object.defineProperty(this, _getDialogInputMessage$1, {
	      value: _getDialogInputMessage2$1
	    });
	    Object.defineProperty(this, _input$4, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _invitationType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isPhoneEnabled$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isEmailEnabled$1, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _invitationType)[_invitationType] = options.useOnlyPhone ? intranet_invitationInput.InvitationInputType.PHONE : options.smsAvailable ? intranet_invitationInput.InvitationInputType.ALL : intranet_invitationInput.InvitationInputType.EMAIL;
	    babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled$1)[_isPhoneEnabled$1] = [intranet_invitationInput.InvitationInputType.ALL, intranet_invitationInput.InvitationInputType.PHONE].includes(babelHelpers.classPrivateFieldLooseBase(this, _invitationType)[_invitationType]);
	    babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled$1)[_isEmailEnabled$1] = [intranet_invitationInput.InvitationInputType.ALL, intranet_invitationInput.InvitationInputType.EMAIL].includes(babelHelpers.classPrivateFieldLooseBase(this, _invitationType)[_invitationType]);
	    babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4] = new intranet_invitationInput.InvitationInput({
	      inputType: babelHelpers.classPrivateFieldLooseBase(this, _invitationType)[_invitationType]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().setPlaceholder(main_core.Type.isStringFilled(options.placeholder) ? options.placeholder : babelHelpers.classPrivateFieldLooseBase(this, _getDialogInputMessage$1)[_getDialogInputMessage$1]());
	    BX.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().getContainer(), 'height', '103px');
	    BX.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().getContainer(), 'cursor', 'text');
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().getContainer(), 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().focusTextBox();
	    });
	  }
	  reset() {
	    babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector().removeTags();
	  }
	  renderTo(node) {
	    babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].renderTo(node);
	  }
	  getValue() {
	    const selector = babelHelpers.classPrivateFieldLooseBase(this, _input$4)[_input$4].getTagSelector();
	    const tags = selector.getTags();
	    const values = [];
	    const errorElements = [];
	    tags.forEach(tag => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled$1)[_isPhoneEnabled$1] && tag.getEntityType() === 'phone') {
	        values.push(tag.getTitle());
	      } else if (babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled$1)[_isEmailEnabled$1] && tag.getEntityType() === 'email') {
	        values.push(tag.getTitle());
	      } else {
	        errorElements.push(tag.getTitle());
	      }
	    });
	    return {
	      values,
	      errorElements
	    };
	  }
	}
	function _getDialogInputMessage2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled$1)[_isPhoneEnabled$1] && babelHelpers.classPrivateFieldLooseBase(this, _isEmailEnabled$1)[_isEmailEnabled$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isPhoneEnabled$1)[_isPhoneEnabled$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_INPUT');
	  } else {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_INPUT');
	  }
	}

	let _$c = t => t,
	  _t$c,
	  _t2$5;
	var _container$9 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _smsAvailable$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("smsAvailable");
	var _useOnlyPhone$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("useOnlyPhone");
	var _onClickSwitchMode$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onClickSwitchMode");
	var _massInvitationField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("massInvitationField");
	var _departmentControl$6 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _getEmptyError$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getEmptyError");
	var _getValidateError$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getValidateError");
	class MassPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getValidateError$1, {
	      value: _getValidateError2$1
	    });
	    Object.defineProperty(this, _getEmptyError$1, {
	      value: _getEmptyError2$1
	    });
	    Object.defineProperty(this, _container$9, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _smsAvailable$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _useOnlyPhone$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onClickSwitchMode$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _massInvitationField, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$6, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1] = options.smsAvailable === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone$1)[_useOnlyPhone$1] = options.useOnlyPhone === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _onClickSwitchMode$1)[_onClickSwitchMode$1] = main_core.Type.isFunction(options.onClickSwitchMode) ? options.onClickSwitchMode : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _massInvitationField)[_massInvitationField] = new MassInvitationField({
	      placeholder: '',
	      useOnlyPhone: babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone$1)[_useOnlyPhone$1],
	      smsAvailable: babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$6)[_departmentControl$6] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	  }
	  render() {
	    var _babelHelpers$classPr;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9];
	    }
	    const helper = main_core.Tag.render(_t$c || (_t$c = _$c`
			<div class="invite-title-helper"
				 data-hint="${0}"
				 data-hint-no-icon
			>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_MASS_INVITE_HINT'));
	    const sufix = babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone$1)[_useOnlyPhone$1] ? 'PHONE' : babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1] ? 'EMAIL_AND_PHONE' : 'EMAIL';
	    babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9] = main_core.Tag.render(_t2$5 || (_t2$5 = _$c`
			<div class="invite-wrap js-intranet-invitation-block" data-role="mass-invite-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-mass">
						<div class="ui-icon-set --person-letter"></div>
					</div>
					<div class="invite-title-text">${0}</div>
					${0}
				</div>
				<div class="invite-content-container">
					<div class="invite-form-container">
						<form method="POST" name="MASS_INVITE_DIALOG_FORM" class="invite-form-container">
							<div class="invite-content__field-lable">
								${0}
							</div>
							<div id="invite-content__mass-field">
							</div>
							<div class="invite-form-ctl-description --border-bottom">
								<span href="javascript:void(0)"
								   class="invite-content__switch_button swith-email-invitation-mass-mode"
								>
									${0}
								</span>
							</div>
							<div class="invite-form-row" id="intranet-invitation__department-control-palce"></div>
						</form>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_MASS_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1] ? helper : '', main_core.Loc.getMessage(`INTRANET_INVITE_DIALOG_MASS_TITLE_${sufix}`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_PERSONAL'));
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _massInvitationField)[_massInvitationField]) == null ? void 0 : _babelHelpers$classPr.renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9].querySelector('#invite-content__mass-field'));
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$6)[_departmentControl$6].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9].querySelector('#intranet-invitation__department-control-palce'));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9].querySelector('.swith-email-invitation-mass-mode'), 'click', babelHelpers.classPrivateFieldLooseBase(this, _onClickSwitchMode$1)[_onClickSwitchMode$1]);
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$9)[_container$9];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_MASS;
	  }
	  onSubmit(event) {
	    var _event$getData;
	    const {
	      values,
	      errorElements
	    } = babelHelpers.classPrivateFieldLooseBase(this, _massInvitationField)[_massInvitationField].getValue();
	    const errors = [];
	    if (values.length <= 0) {
	      errors.push(babelHelpers.classPrivateFieldLooseBase(this, _getEmptyError$1)[_getEmptyError$1]());
	    }
	    if (errorElements.length > 0) {
	      errors.push(babelHelpers.classPrivateFieldLooseBase(this, _getValidateError$1)[_getValidateError$1]());
	    }
	    if (errors.length > 0) {
	      main_core_events.EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
	        errors
	      });
	      return;
	    }
	    const data = {
	      invitationText: values.join(' '),
	      departmentIds: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$6)[_departmentControl$6].getValues(),
	      tab: 'mass'
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'mass'
	    };
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'massInvite',
	      data,
	      analyticsLabel
	    });
	  }
	  onInviteSuccess(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _massInvitationField)[_massInvitationField].reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$6)[_departmentControl$6].reset();
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	  }
	}
	function _getEmptyError2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone$1)[_useOnlyPhone$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_PHONE');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL_AND_PHONE');
	  }
	  return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL');
	}
	function _getValidateError2$1() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _useOnlyPhone$1)[_useOnlyPhone$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_PHONE');
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _smsAvailable$1)[_smsAvailable$1]) {
	    return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL_AND_PHONE');
	  }
	  return main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL');
	}

	let _$d = t => t,
	  _t$d,
	  _t2$6;
	var _container$a = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _inputsFactory$3 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputsFactory");
	var _isCloud$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCloud");
	var _withoutConfirm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("withoutConfirm");
	var _tagSelectorGroup$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelectorGroup");
	var _departmentControl$7 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentControl");
	var _renderFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderFields");
	class RegisterPage extends Page {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _renderFields, {
	      value: _renderFields2
	    });
	    Object.defineProperty(this, _container$a, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputsFactory$3, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isCloud$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _withoutConfirm, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _tagSelectorGroup$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentControl$7, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isCloud$1)[_isCloud$1] = options.isCloud === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _withoutConfirm)[_withoutConfirm] = options.withoutConfirm === true;
	    babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$3)[_inputsFactory$3] = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$2)[_tagSelectorGroup$2] = options.tagSelectorGroup instanceof ui_entitySelector.TagSelector ? options.tagSelectorGroup : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$7)[_departmentControl$7] = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a] = main_core.Tag.render(_t$d || (_t$d = _$d`
			<div class="invite-wrap js-intranet-invitation-block" data-role="add-block">
				<form method="POST" name="ADD_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-registration">
							<div class="ui-icon-set --person-plus"></div>
						</div>
						<div class="invite-title-text">${0}</div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container">
								${0}
							</div>
							<div class="invite-form-row">
								<div class="invite-form-col">
									<div class="invite-content__field-lable">${0}</div>
									<div data-role="entity-selector-container"></div>
								</div>
							</div>
							<div class="invite-form-row" id="intranet-invitation__department-control-palce"></div>
							<div class="invite-form-row">
								<div class="invite-form-col">
									<div class="invite-dialog-inv-form-checkbox-wrap">
										<input
										type="checkbox"
										name="ADD_SEND_PASSWORD"
										id="ADD_SEND_PASSWORD"
										value="Y"
										class="invite-dialog-inv-form-checkbox"
										${0}
										>
										<label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD">
											${0}
											${0}
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _renderFields)[_renderFields](), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_INPUT'), babelHelpers.classPrivateFieldLooseBase(this, _withoutConfirm)[_withoutConfirm] ? 'checked' : '', main_core.Loc.getMessage(babelHelpers.classPrivateFieldLooseBase(this, _isCloud$1)[_isCloud$1] ? 'BX24_INVITE_DIALOG_ADD_WO_CONFIRMATION_TITLE' : 'BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _isCloud$1)[_isCloud$1] ? '' : '<span id="ADD_SEND_PASSWORD_EMAIL"></span>');
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$2)[_tagSelectorGroup$2].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a].querySelector('[data-role="entity-selector-container"]'));
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$7)[_departmentControl$7].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a].querySelector('#intranet-invitation__department-control-palce'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$a)[_container$a];
	  }
	  getAnalyticTab() {
	    return Analytics.TAB_REGISTRATION;
	  }
	  onSubmit(event) {
	    var _event$getData;
	    const addForm = this.render().querySelector('form');
	    const tagSelectorItems = babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$2)[_tagSelectorGroup$2].getDialog().getSelectedItems();
	    const projectIds = [];
	    tagSelectorItems.forEach(item => {
	      const id = parseInt(item.getId(), 10);
	      projectIds.push(id);
	    });
	    const data = {
	      ADD_EMAIL: addForm.ADD_EMAIL.value,
	      ADD_NAME: addForm.ADD_NAME.value,
	      ADD_LAST_NAME: addForm.ADD_LAST_NAME.value,
	      ADD_POSITION: addForm.ADD_POSITION.value,
	      ADD_SEND_PASSWORD: addForm.ADD_SEND_PASSWORD && Boolean(addForm.ADD_SEND_PASSWORD.checked) ? addForm.ADD_SEND_PASSWORD.value : 'N',
	      departmentIds: babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$7)[_departmentControl$7].getValues(),
	      SONET_GROUPS_CODE: projectIds
	    };
	    const analyticsLabel = {
	      INVITATION_TYPE: 'add'
	    };
	    const context = (_event$getData = event.getData()) == null ? void 0 : _event$getData.context;
	    main_core_events.EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
	      action: 'add',
	      data,
	      analyticsLabel
	    });
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');
	  }
	  onInviteSuccess(event) {
	    var _this$render$querySel;
	    (_this$render$querySel = this.render().querySelector('form')) == null ? void 0 : _this$render$querySel.reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentControl$7)[_departmentControl$7].reset();
	    babelHelpers.classPrivateFieldLooseBase(this, _tagSelectorGroup$2)[_tagSelectorGroup$2].removeTags();
	  }
	}
	function _renderFields2() {
	  const element = main_core.Tag.render(_t2$6 || (_t2$6 = _$d`
			<div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_NAME" placeholder="${0}" id="ADD_NAME" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_LAST_NAME" placeholder="${0}" id="ADD_LAST_NAME" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_EMAIL" placeholder="${0}" id="ADD_EMAIL" class="ui-ctl-element" maxlength="50">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${0}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_POSITION" placeholder="${0}" id="ADD_POSITION" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>			
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_NAME'), main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_LAST_NAME'), main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_EMAIL_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_EMAIL'), main_core.Loc.getMessage('BX24_INVITE_DIALOG_ADD_POSITION_TITLE'), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_POSITION'));
	  babelHelpers.classPrivateFieldLooseBase(this, _inputsFactory$3)[_inputsFactory$3].bindCloseIcons(element);
	  return element;
	}

	let _$e = t => t,
	  _t$e;
	var _container$b = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	class SuccessPage extends Page {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _container$b, {
	      writable: true,
	      value: void 0
	    });
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container$b)[_container$b]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container$b)[_container$b];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _container$b)[_container$b] = main_core.Tag.render(_t$e || (_t$e = _$e`
			<div class="invite-wrap js-intranet-invitation-block" data-role="success-block" style="position: fixed; left: 0; right: 0; top: 0; bottom: 0; background: #fff; z-index: 90;">
				<div style="height: 78vh;" class="invite-send-success-wrap">
					<div class="invite-send-success-text">${0}</div>
					<div class="invite-send-success-decal-1"></div>
					<div class="invite-send-success-decal-2"></div>
					<div class="invite-send-success-decal-3"></div>
					<div class="invite-send-success-decal-4"></div>
					<div class="invite-send-success-decal-5"></div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_SUCCESS_SEND'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _container$b)[_container$b];
	  }
	  getAnalyticTab() {
	    return null;
	  }
	  onSubmit(event) {
	    var _event$getData, _event$getData$contex, _event$getData2, _event$getData2$conte, _event$getData3, _event$getData3$conte, _event$getData4, _event$getData4$conte, _event$getData5, _event$getData5$conte, _event$getData6, _event$getData6$conte;
	    (_event$getData = event.getData()) == null ? void 0 : (_event$getData$contex = _event$getData.context) == null ? void 0 : _event$getData$contex.submitButton.enable();
	    (_event$getData2 = event.getData()) == null ? void 0 : (_event$getData2$conte = _event$getData2.context) == null ? void 0 : _event$getData2$conte.submitButton.ready();
	    (_event$getData3 = event.getData()) == null ? void 0 : (_event$getData3$conte = _event$getData3.context) == null ? void 0 : _event$getData3$conte.changeContent((_event$getData4 = event.getData()) == null ? void 0 : (_event$getData4$conte = _event$getData4.context) == null ? void 0 : _event$getData4$conte.firstInvitationBlock);
	    (_event$getData5 = event.getData()) == null ? void 0 : (_event$getData5$conte = _event$getData5.context) == null ? void 0 : _event$getData5$conte.activeMenuItem((_event$getData6 = event.getData()) == null ? void 0 : (_event$getData6$conte = _event$getData6.context) == null ? void 0 : _event$getData6$conte.firstInvitationBlock);
	  }
	  getSubmitButtonText() {
	    return main_core.Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE_MORE');
	  }
	}

	class PageFactory {
	  createLocalEmailPage() {
	    return new LocalEmailPage({
	      departmentControl: this.createDepartmentControl(),
	      transport: this.transport,
	      linkRegisterEnabled: this.isSelfRegisterEnabled,
	      analytics: this.analytics
	    });
	  }
	  createEmailPage() {
	    return new EmailPage({
	      departmentControl: this.createDepartmentControl(),
	      inputsFactory: this.createInputRowFactory(this.useLocalEmailProgram),
	      smsAvailable: this.isInvitationBySmsAvailable,
	      useLocalEmailProgram: this.useLocalEmailProgram,
	      onClickSwitchMode: () => {
	        this.changeContent('mass-invite');
	      },
	      onClickAddInputRow: () => {
	        this.getSetupArrow();
	        this.updateArrow();
	      }
	    });
	  }
	  createGroupPage() {
	    var _this$userOptions;
	    const projectId = (_this$userOptions = this.userOptions) != null && _this$userOptions.groupId ? parseInt(this.userOptions.groupId, 10) : 0;
	    const intranetSelector = new Selector({
	      options: {
	        project: true,
	        projectId,
	        isAdmin: this.isAdmin,
	        projectLimitExceeded: this.projectLimitExceeded,
	        projectLimitFeatureId: this.projectLimitFeatureId
	      }
	    });
	    return new GroupPage({
	      departmentControl: this.createDepartmentControl(),
	      inputsFactory: this.createInputRowFactory(),
	      tagSelectorGroup: intranetSelector.renderTagSelector(),
	      onClickAddInputRow: () => {
	        this.getSetupArrow();
	        this.updateArrow();
	      }
	    });
	  }
	  createExtranetPage() {
	    var _this$userOptions2;
	    const projectId = (_this$userOptions2 = this.userOptions) != null && _this$userOptions2.groupId ? parseInt(this.userOptions.groupId, 10) : 0;
	    const selectorParams = {
	      options: {
	        project: 'extranet',
	        projectId,
	        isAdmin: this.isAdmin,
	        projectLimitExceeded: this.projectLimitExceeded,
	        projectLimitFeatureId: this.projectLimitFeatureId,
	        showCreateButton: !this.isCollabEnabled
	      }
	    };
	    return new ExtranetPage({
	      inputsFactory: this.createInputRowFactory(),
	      tagSelectorGroup: new Selector(selectorParams).renderTagSelector(),
	      onClickAddInputRow: () => {
	        this.getSetupArrow();
	        this.updateArrow();
	      }
	    });
	  }
	  createRegisterPage() {
	    var _this$userOptions3;
	    const projectId = (_this$userOptions3 = this.userOptions) != null && _this$userOptions3.groupId ? parseInt(this.userOptions.groupId, 10) : 0;
	    const intranetSelector = new Selector({
	      options: {
	        project: true,
	        projectId,
	        isAdmin: this.isAdmin,
	        projectLimitExceeded: this.projectLimitExceeded,
	        projectLimitFeatureId: this.projectLimitFeatureId
	      }
	    });
	    return new RegisterPage({
	      departmentControl: this.createDepartmentControl(),
	      isCloud: this.isCloud,
	      inputsFactory: this.createInputRowFactory(),
	      withoutConfirm: !this.registerNeedeConfirm,
	      tagSelectorGroup: intranetSelector.renderTagSelector()
	    });
	  }
	  createIntegratorPage() {
	    return new IntegratorPage();
	  }
	  createLinkPage() {
	    return new LinkPage({
	      departmentControl: this.createDepartmentControl(),
	      isAdmin: this.isAdmin,
	      fastRegistrationAvailable: this.isSelfRegisterEnabled,
	      needConfirmRegistration: this.registerNeedeConfirm,
	      wishlist: this.wishlistValue,
	      isCloud: this.isCloud,
	      linkRegisterEnabled: this.isSelfRegisterEnabled,
	      analytics: this.analytics,
	      transport: this.transport
	    });
	  }
	  createMassPage() {
	    return new MassPage({
	      departmentControl: this.createDepartmentControl(),
	      smsAvailable: this.isInvitationBySmsAvailable,
	      useOnlyPhone: this.useLocalEmailProgram,
	      onClickSwitchMode: () => {
	        this.changeContent('invite');
	      }
	    });
	  }
	  createSeccessPage() {
	    return new SuccessPage();
	  }
	}

	class PageProvider {
	  provide() {
	    const pageFactory = new PageFactory();
	    const pages = new Map();
	    if (this.canCurrentUserInvite) {
	      if (this.useLocalEmailProgram) {
	        pages.set('invite-email', pageFactory.createLocalEmailPage.call(this));
	      }
	      pages.set('invite', pageFactory.createEmailPage.call(this));
	      pages.set('mass-invite', pageFactory.createMassPage.call(this));
	      pages.set('invite-with-group-dp', pageFactory.createGroupPage.call(this));
	      pages.set('add', pageFactory.createRegisterPage.call(this));
	      pages.set('self', pageFactory.createLinkPage.call(this));
	    }
	    if (this.isExtranetInstalled) {
	      pages.set('extranet', pageFactory.createExtranetPage.call(this));
	    }
	    if (this.isCloud && this.canCurrentUserInvite) {
	      pages.set('integrator', pageFactory.createIntegratorPage.call(this));
	    }
	    pages.set('success', pageFactory.createSeccessPage.call(this));
	    return pages;
	  }
	}

	class ActiveDirectory {
	  showForm() {
	    BX.UI.Feedback.Form.open({
	      id: 'intranet-active-directory',
	      forms: [{
	        zones: ['ru'],
	        id: 309,
	        lang: 'ru',
	        sec: 'fbc0n3'
	      }]
	    });
	  }
	}

	var _initMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initMenu");
	class Form extends main_core_events.EventEmitter {
	  constructor(formParams) {
	    var _this$navigation, _this$navigation2;
	    super();
	    Object.defineProperty(this, _initMenu, {
	      value: _initMenu2
	    });
	    this.setEventNamespace('BX.Intranet.Invitation');
	    const params = main_core.Type.isPlainObject(formParams) ? formParams : {};
	    this.menuContainer = params.menuContainerNode;
	    this.subMenuContainer = params.subMenuContainerNode;
	    this.contentContainer = params.contentContainerNode;
	    this.pageContainer = this.contentContainer.querySelector('.popup-window-tabs-content-invite');
	    this.userOptions = params.userOptions;
	    this.isExtranetInstalled = params.isExtranetInstalled === 'Y';
	    this.isCloud = params.isCloud === 'Y';
	    this.isAdmin = params.isAdmin === 'Y';
	    this.canCurrentUserInvite = params.canCurrentUserInvite === true;
	    this.isInvitationBySmsAvailable = params.isInvitationBySmsAvailable === 'Y';
	    this.isCreatorEmailConfirmed = params.isCreatorEmailConfirmed === 'Y';
	    this.firstInvitationBlock = params.firstInvitationBlock;
	    this.isSelfRegisterEnabled = params.isSelfRegisterEnabled;
	    this.analyticsLabel = params.analyticsLabel;
	    this.projectLimitExceeded = main_core.Type.isBoolean(params.projectLimitExceeded) ? params.projectLimitExceeded : true;
	    this.projectLimitFeatureId = main_core.Type.isString(params.projectLimitFeatureId) ? params.projectLimitFeatureId : '';
	    this.wishlistValue = main_core.Type.isStringFilled(params.wishlistValue) ? params.wishlistValue : '';
	    this.isCollabEnabled = params.isCollabEnabled === 'Y';
	    this.registerNeedeConfirm = params.registerConfirm === true;
	    this.useLocalEmailProgram = params.useLocalEmailProgram === true;
	    this.transport = new Transport({
	      componentName: params.componentName,
	      signedParameters: params.signedParameters
	    });
	    if (main_core.Type.isDomNode(this.contentContainer)) {
	      this.messageBar = new MessageBar({
	        errorContainer: this.contentContainer.querySelector("[data-role='error-message']"),
	        successContainer: this.contentContainer.querySelector("[data-role='success-message']")
	      });
	      BX.UI.Hint.init(this.contentContainer);
	    }
	    if (main_core.Type.isDomNode(this.menuContainer)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _initMenu)[_initMenu]();
	    }
	    this.submitButton = new SubmitButton({
	      node: document.querySelector('#intranet-invitation-btn'),
	      events: {
	        click: event => {
	          if (!this.isCreatorEmailConfirmed && !(this.navigation.current() instanceof LinkPage) && !(this.navigation.current() instanceof LocalEmailPage)) {
	            this.messageBar.showError(main_core.Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_CREATOR_EMAIL_ERROR'));
	            return;
	          }
	          if (!this.submitButton.isWaiting() && this.submitButton.isEnabled()) {
	            this.submitButton.wait();
	            main_core_events.EventEmitter.emit(this.navigation.current(), 'BX.Intranet.Invitation:submit', {
	              context: this
	            });
	            if (this.isCloud) {
	              BX.userOptions.del('intranet.invitation', 'open_invitation_form_ts');
	            }
	          }
	        }
	      }
	    });
	    this.analytics = new Analytics(this.analyticsLabel, this.isAdmin);
	    this.analytics.sendOpenSliderData(this.analyticsLabel.source);
	    this.arrowBox = document.querySelector('.invite-wrap-decal-arrow');
	    if (main_core.Type.isDomNode(this.arrowBox)) {
	      this.arrowRect = this.arrowBox.getBoundingClientRect();
	      this.arrowHeight = this.arrowRect.height;
	      this.setSetupArrow();
	    }
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:onChangeForm', () => {
	      this.submitButton.enable();
	    });
	    this.navigation = this.createNavigation();
	    (_this$navigation = this.navigation) == null ? void 0 : _this$navigation.subscribe('onBeforeChangePage', () => {
	      this.messageBar.hideAll();
	    });
	    (_this$navigation2 = this.navigation) == null ? void 0 : _this$navigation2.subscribe('onAfterChangePage', this.onAfterChangePage.bind(this));
	    this.navigation.showFirst();
	    this.subscribe('BX.Intranet.Invitation:onSendData', this.onSendRequest.bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:onSubmitReady', () => {
	      this.submitButton.ready();
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:onSubmitDisabled', () => {
	      this.submitButton.disable();
	    });
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Invitation:onSubmitWait', () => {
	      this.submitButton.wait();
	    });
	  }
	  onAfterChangePage(event) {
	    const section = this.getSubSection();
	    const page = event.getData().current;
	    let subSection = null;
	    if (page) {
	      subSection = page.getAnalyticTab();
	      if (page.getSubmitButtonText()) {
	        this.submitButton.setLabel(page.getSubmitButtonText());
	      }
	      if (page.getButtonState() === SubmitButton.ENABLED_STATE) {
	        this.submitButton.enable();
	      } else if (page.getButtonState() === SubmitButton.DISABLED_STATE) {
	        this.submitButton.disable();
	      }
	    }
	    if (this.analytics && section && subSection) {
	      this.analytics.sendTabData(section, subSection);
	    }
	  }
	  getSubSection() {
	    const regex = /analyticsLabel\[source]=(\w*)&/gm;
	    const match = regex.exec(decodeURI(window.location));
	    if ((match == null ? void 0 : match.length) > 1) {
	      return match[1];
	    }
	    return null;
	  }
	  activeMenuItem(itemType) {
	    (this.menuItems || []).forEach(item => {
	      main_core.Dom.removeClass(item.parentElement, 'ui-sidepanel-menu-active');
	      if (item.getAttribute('data-action') === itemType) {
	        main_core.Dom.addClass(item.parentElement, 'ui-sidepanel-menu-active');
	      }
	    });
	  }
	  changeContent(action) {
	    if (!main_core.Type.isStringFilled(action)) {
	      return;
	    }
	    if (action === 'active-directory') {
	      if (!this.activeDirectory) {
	        this.activeDirectory = new ActiveDirectory(this);
	      }
	      this.activeDirectory.showForm();
	      this.analytics.sendTabData(this.getSubSection(), Analytics.TAB_AD);
	      return;
	    }
	    this.navigation.show(action);
	  }
	  createNavigation() {
	    return new Navigation({
	      container: this.pageContainer,
	      first: this.firstInvitationBlock,
	      pages: new PageProvider().provide.call(this)
	    });
	  }
	  onSendRequest(event) {
	    this.submitButton.disable();
	    const request = event.getData();
	    this.messageBar.hideAll();
	    if (main_core.Type.isArray(request.errors) && request.errors.length > 0) {
	      this.submitButton.enable();
	      this.submitButton.ready();
	      this.messageBar.showError(request.errors[0]);
	      return;
	    }
	    request.userOptions = this.userOptions;
	    request.analyticsData = this.analyticsLabel;
	    request.data.analyticsData = this.analyticsLabel;
	    // eslint-disable-next-line promise/catch-or-return
	    this.transport.send(request).then(response => {
	      this.submitButton.ready();
	      if (response.data) {
	        if ((request == null ? void 0 : request.action) === 'self') {
	          this.messageBar.showSuccess(response.data);
	          this.isSelfRegisterEnabled = request.data.allow_register === 'Y';
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Invitation:selfChange', {
	            selfEnabled: this.isSelfRegisterEnabled
	          });
	        } else if (this.useLocalEmailProgram && (request == null ? void 0 : request.action) === 'invite' && (request == null ? void 0 : request.type) === 'invite-email') {
	          main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Invitation:InviteSuccess');
	        } else {
	          this.changeContent('success');
	          this.submitButton.sendSuccessEvent(response.data);
	        }
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Invitation:onInviteRequestSuccess', {
	          response
	        });
	      }
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'SidePanel.Slider:onClose', () => {
	        BX.SidePanel.Instance.postMessageTop(window, 'BX.Bitrix24.EmailConfirmation:showPopup');
	      });
	    }, response => {
	      this.submitButton.enable();
	      this.submitButton.ready();
	      if (response.data === 'user_limit') {
	        // eslint-disable-next-line no-undef
	        B24.licenseInfoPopup.show('featureID', main_core.Loc.getMessage('BX24_INVITE_DIALOG_USERS_LIMIT_TITLE'), main_core.Loc.getMessage('BX24_INVITE_DIALOG_USERS_LIMIT_TEXT'));
	      } else {
	        this.messageBar.showError(response.errors[0].message);
	      }
	      if ((request == null ? void 0 : request.action) === 'invite' && this.useLocalEmailProgram && (request == null ? void 0 : request.type) === 'invite-email') {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.Invitation:InviteFailed');
	      }
	    });
	  }
	  createDepartmentControl() {
	    var _this$userOptions, _this$userOptions2;
	    const departmentsId = main_core.Type.isArray((_this$userOptions = this.userOptions) == null ? void 0 : _this$userOptions.departmentList) ? this.userOptions.departmentList : [];
	    const rootDepartment = (_this$userOptions2 = this.userOptions) == null ? void 0 : _this$userOptions2.rootDepartment;
	    return new DepartmentControl({
	      departmentList: departmentsId,
	      rootDepartment: main_core.Type.isObject(rootDepartment) ? rootDepartment : null
	    });
	  }
	  createInputRowFactory(useOnlyPhone = false) {
	    const inputRowType = useOnlyPhone ? InputRowType.PHONE : this.isInvitationBySmsAvailable ? InputRowType.ALL : InputRowType.EMAIL;
	    return new InputRowFactory({
	      inputRowType
	    });
	  }
	  getSetupArrow() {
	    var _this$sliderHeader;
	    this.body = document.querySelector('.invite-body');
	    this.panelConfirmBtn = document.getElementById('intranet-invitation-btn');
	    this.sliderContent = document.querySelector('.ui-page-slider-workarea');
	    this.sliderHeader = document.querySelector('.ui-side-panel-wrap-title-wrap');
	    this.buttonPanel = document.querySelector('.ui-button-panel');
	    this.sliderHeaderHeight = (_this$sliderHeader = this.sliderHeader) == null ? void 0 : _this$sliderHeader.getBoundingClientRect().height;
	    this.buttonPanelRect = this.buttonPanel.getBoundingClientRect();
	    this.panelRect = this.panelConfirmBtn.getBoundingClientRect();
	    this.btnWidth = Math.ceil(this.panelRect.width);
	    this.arrowWidth = Math.ceil(this.arrowRect.width);
	    this.sliderContentRect = this.sliderContent.getBoundingClientRect();
	    this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
	    this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
	  }
	  updateArrow() {
	    this.bodyHeight = this.body.getBoundingClientRect().height - this.buttonPanelRect.height + this.sliderHeaderHeight;
	    this.contentHeight = this.arrowHeight + this.sliderContentRect.height + this.buttonPanelRect.height + this.sliderHeaderHeight - 65;
	    // eslint-disable-next-line no-unused-expressions
	    this.contentHeight > this.bodyHeight ? main_core.Dom.addClass(this.body, 'js-intranet-invitation-arrow-hide') : main_core.Dom.removeClass(this.body, 'js-intranet-invitation-arrow-hide');
	  }
	  setSetupArrow() {
	    this.getSetupArrow();
	    const btnPadding = 40;
	    main_core.Dom.style(this.arrowBox, 'left', `${this.panelRect.left + this.btnWidth / 2 - this.arrowWidth / 2 - btnPadding}px`);
	    // eslint-disable-next-line no-unused-expressions
	    this.contentHeight > this.bodyHeight ? main_core.Dom.addClass(this.body, 'js-intranet-invitation-arrow-hide') : main_core.Dom.removeClass(this.body, 'js-intranet-invitation-arrow-hide');
	    main_core.Event.bind(window, 'resize', () => {
	      if (window.innerWidth <= 1100) {
	        main_core.Dom.style(this.arrowBox, 'left', `${this.panelRect.left + this.btnWidth / 2 - this.arrowWidth / 2 - btnPadding}px`);
	      }
	      this.getSetupArrow();
	      this.updateArrow();
	    });
	  }
	}
	function _initMenu2() {
	  this.menuItems = Array.prototype.slice.call(this.menuContainer.querySelectorAll('a'));
	  if (main_core.Type.isDomNode(this.subMenuContainer)) {
	    const subMenuItem = Array.prototype.slice.call(this.subMenuContainer.querySelectorAll('a'));
	    this.menuItems = [...this.menuItems, ...subMenuItem];
	  }
	  (this.menuItems || []).forEach(item => {
	    main_core.Event.bind(item, 'click', () => {
	      this.changeContent(item.getAttribute('data-action'));
	      this.activeMenuItem(this.navigation.getCurrentCode());
	    });
	    if (item.getAttribute('data-action') === this.firstInvitationBlock) {
	      main_core.Dom.addClass(item.parentElement, 'ui-sidepanel-menu-active');
	    } else {
	      main_core.Dom.removeClass(item.parentElement, 'ui-sidepanel-menu-active');
	    }
	  });
	}

	exports.Form = Form;
	exports.MassInvitationField = MassInvitationField;

}((this.BX.Intranet.Invitation = this.BX.Intranet.Invitation || {}),BX.UI.Analytics,BX,BX,BX.UI,BX.Main,BX.UI,BX.Intranet,BX.UI.EntitySelector,BX.Intranet,BX,BX.Event));
//# sourceMappingURL=script.js.map
