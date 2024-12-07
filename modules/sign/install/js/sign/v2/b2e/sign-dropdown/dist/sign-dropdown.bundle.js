/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,ui_entitySelector) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _dom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dom");
	var _selector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selector");
	var _selectedItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedItemId");
	var _onSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelect");
	class SignDropdown {
	  constructor(dialogOptions = {}) {
	    Object.defineProperty(this, _onSelect, {
	      value: _onSelect2
	    });
	    Object.defineProperty(this, _dom, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selector, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _selectedItemId, {
	      writable: true,
	      value: ''
	    });
	    const {
	      className,
	      withCaption
	    } = dialogOptions;
	    const _titleNode = withCaption ? main_core.Tag.render(_t || (_t = _`
				<div class="sign-b2e-dropdown__text">
					<span class="sign-b2e-dropdown__text_title"></span>
					<span class="sign-b2e-dropdown__text_caption"></span>
				</div>
			`)) : main_core.Tag.render(_t2 || (_t2 = _`<span class="sign-b2e-dropdown__text"></span>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom] = main_core.Tag.render(_t3 || (_t3 = _`
			<div
				class="sign-b2e-dropdown"
				onclick="${0}"
			>
				${0}
				<span class="sign-b2e-dropdown__btn"></span>
			</div>
		`), () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].show();
	    }, _titleNode);
	    babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector] = new ui_entitySelector.Dialog({
	      targetNode: babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom],
	      width: 500,
	      height: 350,
	      showAvatars: false,
	      dropdownMode: true,
	      multiple: false,
	      enableSearch: true,
	      hideOnSelect: true,
	      events: {
	        'Item:OnSelect': ({
	          data
	        }) => babelHelpers.classPrivateFieldLooseBase(this, _onSelect)[_onSelect](data.item)
	      },
	      ...dialogOptions
	    });
	    if (className) {
	      const container = babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].getContainer();
	      main_core.Dom.addClass(container, className);
	    }
	  }
	  addItem(item) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].addItem(item);
	  }
	  selectItem(id) {
	    const items = babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].getItems();
	    const foundItem = items.find(item => item.id === id);
	    if (!foundItem) {
	      return;
	    }
	    foundItem.select();
	  }
	  getLayout() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom];
	  }
	  getSelectedId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedItemId)[_selectedItemId];
	  }
	}
	function _onSelect2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedItemId)[_selectedItemId] = item.id;
	  const {
	    title,
	    caption
	  } = item;
	  const {
	    firstElementChild: titleNode
	  } = babelHelpers.classPrivateFieldLooseBase(this, _dom)[_dom];
	  if (!caption) {
	    titleNode.textContent = title;
	    titleNode.title = title;
	    return;
	  }
	  titleNode.title = `${title} ${caption}`;
	  titleNode.firstElementChild.textContent = title;
	  titleNode.lastElementChild.textContent = caption;
	}

	exports.SignDropdown = SignDropdown;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.UI.EntitySelector));
//# sourceMappingURL=sign-dropdown.bundle.js.map
