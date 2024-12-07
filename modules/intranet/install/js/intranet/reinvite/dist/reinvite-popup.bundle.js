/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_popup,ui_buttons,main_core) {
	'use strict';

	let _ = t => t,
	  _t;
	var _id = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _value = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("value");
	var _userId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userId");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _formNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formNode");
	class Form {
	  constructor(options) {
	    Object.defineProperty(this, _id, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _value, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userId, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _formNode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _id)[_id] = `form-${options.id}`;
	    babelHelpers.classPrivateFieldLooseBase(this, _value)[_value] = options.inputValue;
	    babelHelpers.classPrivateFieldLooseBase(this, _userId)[_userId] = options.userId;
	  }
	  getTitleRender() {
	    return new HTMLElement();
	  }
	  getFieldRender() {
	    return new HTMLElement();
	  }
	  getFormNode() {
	    return this.render().querySelector('form#' + babelHelpers.classPrivateFieldLooseBase(this, _id)[_id]);
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _content)[_content]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = main_core.Tag.render(_t || (_t = _`
		<div class="intranet-reinvite-popup-wrapper">
			<form method="POST" id="${0}">
				<input type="hidden" name="userId" value="${0}">
				${0}
				${0}
			</form>
		</div>`), babelHelpers.classPrivateFieldLooseBase(this, _id)[_id], babelHelpers.classPrivateFieldLooseBase(this, _userId)[_userId], this.getTitleRender(), this.getFieldRender());
	    return babelHelpers.classPrivateFieldLooseBase(this, _content)[_content];
	  }
	  getValue() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _value)[_value];
	  }
	  getData() {
	    return new FormData(this.getFormNode());
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2;
	class PhoneForm extends Form {
	  getTitleRender() {
	    return main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="intranet-reinvite-popup-title">
			${0}
		</div>`), main_core.Loc.getMessage('INTRANET_JS_PHONE_POPUP_TITLE', {
	      '#CODE#': 'redirect=detail&code=17729332'
	    }));
	  }
	  getFieldRender() {
	    const form = main_core.Tag.render(_t2 || (_t2 = _$1`
			<div class="ui-ctl ui-ctl-textbox ui-ctl-before-icon ui-ctl-after-icon intranet-reinvite-popup-field-row">
				<div class="intranet-reinvite-popup-field-label">
					<label>${0}</label>
				</div>
				<div class="ui-ctl ui-ctl-textbox">
					<div id="intranet_reinvite_phone_flag" class="ui-ctl-before --flag"></div>
					<input id="intranet_reinvite_phone_input" type="text" name="newPhone" value="${0}" class="ui-ctl-element">
				</div>
			</div>`), main_core.Loc.getMessage('INTRANET_JS_PHONE_FIELD_LABEL'), this.getValue());
	    new BX.PhoneNumber.Input({
	      node: form.querySelector('#intranet_reinvite_phone_input'),
	      defaultCountry: 'ru',
	      flagNode: form.querySelector('#intranet_reinvite_phone_flag'),
	      flagSize: 24,
	      onChange: function (e) {}
	    });
	    return form;
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1;
	class EmailForm extends Form {
	  getTitleRender() {
	    return main_core.Tag.render(_t$2 || (_t$2 = _$2`<div class="intranet-reinvite-popup-title">
			${0}
			</div>`), main_core.Loc.getMessage('INTRANET_JS_EMAIL_POPUP_TITLE', {
	      '#CODE#': 'redirect=detail&code=17729332'
	    }));
	  }
	  getFieldRender() {
	    return main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
			<div class="intranet-reinvite-popup-field-row">
				<div class="intranet-reinvite-popup-field-label">
					<label>${0}</label>
				</div>
				<div class="ui-ctl ui-ctl-textbox">
					<input type="text" name="newEmail" value="${0}" class="ui-ctl-element"> 
				</div>
			</div>`), main_core.Loc.getMessage('INTRANET_JS_EMAIL_FIELD_LABEL'), this.getValue());
	  }
	}

	const FormType = {
	  EMAIL: 'email',
	  PHONE: 'phone'
	};

	class FormFactory {
	  constructor() {}
	  static create(type, options) {
	    switch (type) {
	      case FormType.EMAIL:
	        return new EmailForm(options);
	      case FormType.PHONE:
	        return new PhoneForm(options);
	      default:
	        throw new Error('Unknown ContextType value: ' + type);
	    }
	  }
	}

	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _transport = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("transport");
	var _userId$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userId");
	var _id$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("id");
	var _inputValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputValue");
	var _bindElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindElement");
	var _form = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("form");
	var _width = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("width");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	class ReinvitePopup {
	  constructor(options) {
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _transport, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _userId$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _id$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputValue, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _bindElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _form, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _width, {
	      writable: true,
	      value: void 0
	    });
	    if (options.userId <= 0) {
	      throw new Error('Invalide "userId" parameter');
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _userId$1)[_userId$1] = options.userId;
	    babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1] = 'reinvite-popup-' + options.userId;
	    babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement] = main_core.Type.isElementNode(options.bindElement) ? options.bindElement : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _transport)[_transport] = main_core.Type.isFunction(options.transport) ? options.transport : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _width)[_width] = 348;
	    babelHelpers.classPrivateFieldLooseBase(this, _form)[_form] = FormFactory.create(options.formType, {
	      id: babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1],
	      userId: babelHelpers.classPrivateFieldLooseBase(this, _userId$1)[_userId$1],
	      inputValue: options.inputValue
	    });
	  }
	  show() {
	    this.getPopup().show();
	  }
	  getPopup() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  send() {
	    babelHelpers.classPrivateFieldLooseBase(this, _transport)[_transport](babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].getData());
	  }
	}
	function _createPopup2(options) {
	  if (main_popup.PopupManager.isPopupExists(babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1])) {
	    return main_popup.PopupManager.getPopupById(babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1]);
	  }
	  return new main_popup.Popup(babelHelpers.classPrivateFieldLooseBase(this, _id$1)[_id$1], babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement], {
	    content: babelHelpers.classPrivateFieldLooseBase(this, _form)[_form].render(),
	    autoHide: true,
	    angle: {
	      offset: babelHelpers.classPrivateFieldLooseBase(this, _width)[_width] / 2 - 16.5
	    },
	    width: babelHelpers.classPrivateFieldLooseBase(this, _width)[_width],
	    padding: 18,
	    offsetLeft: (babelHelpers.classPrivateFieldLooseBase(this, _bindElement)[_bindElement].offsetWidth / 2 - babelHelpers.classPrivateFieldLooseBase(this, _width)[_width] / 2) / 2 - 10,
	    closeIcon: false,
	    closeByEsc: true,
	    overlay: false,
	    className: 'reinvite-popup-container',
	    bindOptions: {
	      position: 'top'
	    },
	    animation: "fading-slide",
	    buttons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('INTRANET_JS_BTN_SEND'),
	      color: ui_buttons.Button.Color.PRIMARY,
	      round: true,
	      noCaps: true,
	      onclick: button => {
	        this.send();
	        this.getPopup().close();
	      }
	    }), new ui_buttons.Button({
	      text: main_core.Loc.getMessage('INTRANET_JS_BTN_CANCEL'),
	      color: ui_buttons.Button.Color.LIGHT_BORDER,
	      round: true,
	      noCaps: true,
	      onclick: button => {
	        this.getPopup().close();
	      }
	    })]
	  });
	}

	exports.ReinvitePopup = ReinvitePopup;
	exports.FormType = FormType;

}((this.BX.Intranet.Reinvite = this.BX.Intranet.Reinvite || {}),BX.Main,BX.UI,BX));
//# sourceMappingURL=reinvite-popup.bundle.js.map
