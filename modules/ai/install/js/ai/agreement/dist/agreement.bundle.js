/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ai_engine,main_popup,ui_buttons,main_core) {
	'use strict';

	let _ = t => t,
	  _t;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _title = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("title");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _onApply = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onApply");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	class AgreementPopup {
	  constructor(props) {
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _title, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onApply, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = props.content || '';
	    babelHelpers.classPrivateFieldLooseBase(this, _title)[_title] = props.title || '';
	    babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply] = props.onApply;
	  }
	  show() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	    }
	  }
	}
	function _createPopup2() {
	  const maxHeight = window.innerHeight - 60;
	  return new main_popup.Popup({
	    closeIcon: true,
	    maxWidth: 800,
	    maxHeight,
	    disableScroll: true,
	    titleBar: babelHelpers.classPrivateFieldLooseBase(this, _title)[_title],
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](),
	    overlay: true,
	    cacheable: false,
	    className: 'ai__copilot-agreement_popup',
	    contentColor: getComputedStyle(document.body).getPropertyValue('--ui-color-base-02'),
	    buttons: [new ui_buttons.Button({
	      text: main_core.Loc.getMessage('AI_AGREEMENT_ACCEPT'),
	      color: ui_buttons.Button.Color.SUCCESS,
	      onclick: button => {
	        if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply])) {
	          button.setState(ui_buttons.Button.State.CLOCKING);
	          if (main_core.Type.isFunction(babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply])) {
	            babelHelpers.classPrivateFieldLooseBase(this, _onApply)[_onApply]().then(() => {
	              this.hide();
	              button.setState(null);
	            }).catch(err => {
	              console.error(err);
	              button.setState(null);
	            });
	          }
	        }
	      }
	    })]
	  });
	}
	function _renderPopupContent2() {
	  return main_core.Tag.render(_t || (_t = _`
			<div class="ai__picker_agreement">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _content)[_content]);
	}

	var _engine = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engine");
	var _agreement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("agreement");
	var _type = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("type");
	var _engineCode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("engineCode");
	var _acceptAgreement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("acceptAgreement");
	class Agreement {
	  constructor(options) {
	    Object.defineProperty(this, _acceptAgreement, {
	      value: _acceptAgreement2
	    });
	    Object.defineProperty(this, _engine, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _agreement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _type, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _engineCode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _agreement)[_agreement] = options.agreement;
	    babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine] = options.engine;
	    babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] = options.type;
	    babelHelpers.classPrivateFieldLooseBase(this, _engineCode)[_engineCode] = options.engineCode;
	  }
	  showAgreementPopup(onApply) {
	    const agreement = babelHelpers.classPrivateFieldLooseBase(this, _agreement)[_agreement];
	    const popup = new AgreementPopup({
	      title: agreement.title,
	      content: agreement.text,
	      onApply: () => {
	        return new Promise((resolve, reject) => {
	          babelHelpers.classPrivateFieldLooseBase(this, _acceptAgreement)[_acceptAgreement]().then(() => {
	            agreement.accepted = true;
	            onApply();
	            resolve();
	          }).catch(() => {
	            BX.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('AI_COPILOT_AGREE_WITH_TERMS_SERVER_ERROR')
	            });
	            reject();
	          });
	        });
	      }
	    });
	    popup.show();
	  }
	}
	function _acceptAgreement2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === 'text') {
	    return babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].acceptTextAgreement(babelHelpers.classPrivateFieldLooseBase(this, _engineCode)[_engineCode]);
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _type)[_type] === 'image') {
	    return babelHelpers.classPrivateFieldLooseBase(this, _engine)[_engine].acceptImageAgreement(babelHelpers.classPrivateFieldLooseBase(this, _engineCode)[_engineCode]);
	  }
	  throw new Error('AI: Agreement: acceptAgreement: Type can be "text" or "image"');
	}

	exports.Agreement = Agreement;

}((this.BX.AI = this.BX.AI || {}),BX.AI,BX.Main,BX.UI,BX));
//# sourceMappingURL=agreement.bundle.js.map
