/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_events,ui_sidepanel_layout) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _fields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fields");
	var _inputByFieldCodeMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputByFieldCodeMap");
	var _chosenField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chosenField");
	var _preselectedFieldName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preselectedFieldName");
	var _loadAvailableFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadAvailableFields");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _fillList = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fillList");
	var _chooseField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chooseField");
	class ProfileFieldSelector extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _chooseField, {
	      value: _chooseField2
	    });
	    Object.defineProperty(this, _fillList, {
	      value: _fillList2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _loadAvailableFields, {
	      value: _loadAvailableFields2
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        fieldList: HTMLDivElement = null
	      }
	    });
	    Object.defineProperty(this, _fields, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _inputByFieldCodeMap, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _chosenField, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _preselectedFieldName, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Sign.V2.B2e.ProfileFieldSelector');
	    if (main_core.Type.isStringFilled(options == null ? void 0 : options.preselectedFieldName)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _preselectedFieldName)[_preselectedFieldName] = options.preselectedFieldName;
	    }
	  }
	  getFieldCaptionByName(fieldName) {
	    const field = babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields].find(field => field.name === fieldName);
	    if (!main_core.Type.isObject(field)) {
	      return '';
	    }
	    return main_core.Type.isStringFilled(field == null ? void 0 : field.caption) ? field.caption : '';
	  }
	  open() {
	    const instance = this;
	    BX.SidePanel.Instance.open("sign.b2e:profile-field-selector", {
	      width: 700,
	      cacheable: false,
	      events: {
	        onCloseComplete: () => {
	          instance.emit('onSliderCloseComplete', {
	            field: babelHelpers.classPrivateFieldLooseBase(instance, _chosenField)[_chosenField]
	          });
	        }
	      },
	      contentCallback: () => {
	        return ui_sidepanel_layout.Layout.createContent({
	          extensions: ['ui.forms'],
	          title: main_core.Loc.getMessage('SIGN_V2_B2E_PROFILE_FIELD_SELECTOR_TITLE'),
	          design: {
	            section: true
	          },
	          content() {
	            return babelHelpers.classPrivateFieldLooseBase(instance, _loadAvailableFields)[_loadAvailableFields]();
	          },
	          buttons({
	            SaveButton,
	            closeButton
	          }) {
	            return [new SaveButton({
	              text: main_core.Loc.getMessage('SIGN_V2_B2E_PROFILE_FIELD_SELECTOR_CHOOSE_BUTTON'),
	              onclick: () => {
	                BX.SidePanel.Instance.close();
	              }
	            }), closeButton];
	          }
	        });
	      }
	    });
	  }
	}
	function _loadAvailableFields2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields].length > 0) {
	    return Promise.resolve(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	  }
	  return new Promise(resolve => {
	    BX.ajax.runAction('sign.api_v1.b2e.fields.getAvailableProfileFields', {
	      json: {}
	    }).then(response => {
	      if (main_core.Type.isObject(response.data.fields)) {
	        babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields] = response.data.fields;
	        babelHelpers.classPrivateFieldLooseBase(this, _fillList)[_fillList]();
	      }
	      resolve(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    }, response => {
	      resolve(babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]());
	    });
	  });
	}
	function _render2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].fieldList = main_core.Tag.render(_t || (_t = _`
			<div class="sign-b2e-profile-fields-selector-fields-list"></div>
		`));
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-b2e-profile-fields-selector">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].fieldList);
	  babelHelpers.classPrivateFieldLooseBase(this, _fillList)[_fillList]();
	  const preselectedField = babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields].find(field => field.name === babelHelpers.classPrivateFieldLooseBase(this, _preselectedFieldName)[_preselectedFieldName]);
	  if (main_core.Type.isObject(preselectedField)) {
	    babelHelpers.classPrivateFieldLooseBase(this, _chooseField)[_chooseField](preselectedField);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	}
	function _fillList2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _fields)[_fields].forEach(field => {
	    const radioInput = main_core.Tag.render(_t3 || (_t3 = _`
				<input type="radio" class="ui-ctl-element">
			`));
	    main_core.Event.bind(radioInput, 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _chooseField)[_chooseField](field);
	    });
	    const element = main_core.Tag.render(_t4 || (_t4 = _`
				<div class="sign-b2e-profile-field-selector">
					<label class="ui-ctl ui-ctl-checkbox sign-b2e-profile-field-selector-checkbox">
						${0}
						<div class="ui-ctl-label-text">${0}</div>
					</label>
				</div>
			`), radioInput, field.caption);
	    babelHelpers.classPrivateFieldLooseBase(this, _inputByFieldCodeMap)[_inputByFieldCodeMap].set(field.name, radioInput);
	    main_core.Dom.append(element, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].fieldList);
	  });
	}
	function _chooseField2(field) {
	  // Uncheck previous field
	  if (!main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField])) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _inputByFieldCodeMap)[_inputByFieldCodeMap].has(babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField].name)) {
	      const input = babelHelpers.classPrivateFieldLooseBase(this, _inputByFieldCodeMap)[_inputByFieldCodeMap].get(babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField].name);
	      input.checked = false;
	    }
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField] = field;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _inputByFieldCodeMap)[_inputByFieldCodeMap].has(babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField].name)) {
	    const input = babelHelpers.classPrivateFieldLooseBase(this, _inputByFieldCodeMap)[_inputByFieldCodeMap].get(babelHelpers.classPrivateFieldLooseBase(this, _chosenField)[_chosenField].name);
	    input.checked = true;
	  }
	}

	exports.ProfileFieldSelector = ProfileFieldSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Event,BX.UI.SidePanel));
//# sourceMappingURL=profile-field-selector.bundle.js.map
