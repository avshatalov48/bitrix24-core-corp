/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_core_events,ui_entitySelector) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _dom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("dom");
	var _selector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selector");
	var _selectedItemId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedItemId");
	var _selectedItemCaption = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectedItemCaption");
	var _onSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelect");
	class SignDropdown extends main_core_events.EventEmitter {
	  constructor(dialogOptions) {
	    super();
	    Object.defineProperty(this, _onSelect, {
	      value: _onSelect2
	    });
	    this.events = {
	      onSelect: 'onSelect'
	    };
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
	    Object.defineProperty(this, _selectedItemCaption, {
	      writable: true,
	      value: ''
	    });
	    this.setEventNamespace('BX.V2.B2e.SignDropdown');
	    const {
	      className,
	      withCaption,
	      isEnableSearch,
	      width,
	      height
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
	      width: width != null ? width : 500,
	      height: height != null ? height : 350,
	      showAvatars: false,
	      dropdownMode: true,
	      multiple: false,
	      enableSearch: isEnableSearch != null ? isEnableSearch : true,
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
	  addItems(items) {
	    items.forEach(item => babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].addItem(item));
	  }
	  removeItems() {
	    babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].removeItems();
	  }
	  selectFirstItem() {
	    const [firstItem] = babelHelpers.classPrivateFieldLooseBase(this, _selector)[_selector].getItems();
	    if (!main_core.Type.isUndefined(firstItem)) {
	      firstItem.select();
	    }
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
	  getSelectedCaption() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _selectedItemCaption)[_selectedItemCaption];
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
	    this.emit(this.events.onSelect, {
	      item
	    });
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _selectedItemCaption)[_selectedItemCaption] = caption.text;
	  titleNode.title = `${title} ${caption}`;
	  titleNode.firstElementChild.textContent = title;
	  titleNode.lastElementChild.textContent = caption;
	  this.emit(this.events.onSelect, {
	    item
	  });
	}

	exports.SignDropdown = SignDropdown;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Event,BX.UI.EntitySelector));
//# sourceMappingURL=sign-dropdown.bundle.js.map
