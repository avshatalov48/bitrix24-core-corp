/* eslint-disable */
this.BX = this.BX || {};
this.BX.AI = this.BX.AI || {};
this.BX.AI.UI = this.BX.AI.UI || {};
(function (exports,ui_formElements_view,main_core,types_js) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8;
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _additionalItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("additionalItems");
	var _hintTitleElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hintTitleElement");
	var _hintDescElement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hintDescElement");
	var _inputNode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("inputNode");
	var _buildSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buildSelector");
	var _getContentItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContentItems");
	var _getCurrentName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentName");
	var _getCurrentValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCurrentValue");
	var _titleBindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("titleBindEvents");
	var _toggleSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("toggleSelect");
	var _closeOtherSelects = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeOtherSelects");
	var _closeSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("closeSelect");
	var _openSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openSelect");
	var _labelBindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("labelBindEvents");
	var _prepareSelectContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareSelectContent");
	class SelectorField extends ui_formElements_view.Selector {
	  constructor(params) {
	    super(params);
	    Object.defineProperty(this, _prepareSelectContent, {
	      value: _prepareSelectContent2
	    });
	    Object.defineProperty(this, _labelBindEvents, {
	      value: _labelBindEvents2
	    });
	    Object.defineProperty(this, _openSelect, {
	      value: _openSelect2
	    });
	    Object.defineProperty(this, _closeSelect, {
	      value: _closeSelect2
	    });
	    Object.defineProperty(this, _closeOtherSelects, {
	      value: _closeOtherSelects2
	    });
	    Object.defineProperty(this, _toggleSelect, {
	      value: _toggleSelect2
	    });
	    Object.defineProperty(this, _titleBindEvents, {
	      value: _titleBindEvents2
	    });
	    Object.defineProperty(this, _getCurrentValue, {
	      value: _getCurrentValue2
	    });
	    Object.defineProperty(this, _getCurrentName, {
	      value: _getCurrentName2
	    });
	    Object.defineProperty(this, _getContentItems, {
	      value: _getContentItems2
	    });
	    Object.defineProperty(this, _buildSelector, {
	      value: _buildSelector2
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _additionalItems, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _hintTitleElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _hintDescElement, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _inputNode, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = params.items;
	    babelHelpers.classPrivateFieldLooseBase(this, _additionalItems)[_additionalItems] = params.additionalItems || [];
	    babelHelpers.classPrivateFieldLooseBase(this, _items)[_items] = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].map(item => {
	      const newItem = item;
	      newItem.recommended = (params.recommendedItems || []).includes(item.value);
	      return newItem;
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _hintTitleElement)[_hintTitleElement] = main_core.Tag.render(_t || (_t = _`<div class="ui-section__title"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _hintDescElement)[_hintDescElement] = main_core.Tag.render(_t2 || (_t2 = _`<div class="ui-section__description"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode] = babelHelpers.classPrivateFieldLooseBase(this, _buildSelector)[_buildSelector]();
	  }
	  renderContentField() {
	    const disableClass = this.isEnable() ? '' : 'ui-ctl-disabled';
	    const lockElement = this.isEnable() ? null : this.renderLockElement();
	    return main_core.Tag.render(_t3 || (_t3 = _`
			<div id="${0}" class="ui-section__field-selector ">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label class="ui-section__field-label" for="${0}">${0}</label> 
						${0}
					</div>
					<div class="ui-section__field-inline-box">
						<div class="ui-section__field">
							<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown ${0}">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								${0}
							</div>
						</div>

						<div class="ui-section__hint">
							${0}
							${0}
						</div>
					</div>
				</div>
			</div>
		`), this.getId(), this.getName(), this.getLabel(), lockElement, disableClass, this.getInputNode(), babelHelpers.classPrivateFieldLooseBase(this, _hintTitleElement)[_hintTitleElement], babelHelpers.classPrivateFieldLooseBase(this, _hintDescElement)[_hintDescElement]);
	  }
	  getInputNode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _inputNode)[_inputNode];
	  }
	}
	function _buildSelector2() {
	  const selectInput = main_core.Tag.render(_t4 || (_t4 = _`
			<input type="hidden" class="select-input" name="${0}" value="${0}"/>
		`), this.getName(), babelHelpers.classPrivateFieldLooseBase(this, _getCurrentValue)[_getCurrentValue]());
	  const selector = main_core.Tag.render(_t5 || (_t5 = _`
			<div class="select" id="${0}" data-state="">
				${0}
				<div class="select-title">${0}</div>
				<div class="select-content"></div>
			</div>
		`), this.getName(), selectInput, babelHelpers.classPrivateFieldLooseBase(this, _getCurrentName)[_getCurrentName]());
	  const selectContentItems = babelHelpers.classPrivateFieldLooseBase(this, _getContentItems)[_getContentItems]();
	  const selectContent = selector.querySelector('.select-content');
	  if (selectContent && selectContentItems) {
	    selectContentItems.forEach(option => {
	      selectContent.append(option);
	    });
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _titleBindEvents)[_titleBindEvents](selector);
	  babelHelpers.classPrivateFieldLooseBase(this, _labelBindEvents)[_labelBindEvents](selector);
	  return selector;
	}
	function _getContentItems2() {
	  const selectContentItems = [];
	  for (const {
	    value,
	    name,
	    selected,
	    recommended
	  } of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	    let selectedClass = '';
	    if (selected === true) {
	      selectedClass = 'selected';
	    }
	    const recommendedLabel = recommended ? main_core.Tag.render(_t6 || (_t6 = _`
						<span class="select-label-recommended">
							${0}
						</span>
					`), main_core.Loc.getMessage('AI_SELECTORFIELD_RECOMMENDED_LABEL')) : '';
	    const contentItemLabel = main_core.Tag.render(_t7 || (_t7 = _`
				<div class="select-label-container ${0}">
					<label class="select-label" value="${0}">${0}</label>
					${0}
					<span class="select-label-icon ui-icon-set --check"></span>
				</div>
			`), selectedClass, value, name, recommendedLabel);
	    selectContentItems.push(contentItemLabel);
	  }
	  const loadedIconSets = [];
	  for (const {
	    type,
	    link,
	    text,
	    icon
	  } of babelHelpers.classPrivateFieldLooseBase(this, _additionalItems)[_additionalItems]) {
	    if (type === 'link') {
	      const set = icon.set || 'ui.icon-set.main';
	      if (!loadedIconSets.includes(set)) {
	        main_core.Runtime.loadExtension(set);
	        loadedIconSets.push(set);
	      }
	      const contentItemLink = main_core.Tag.render(_t8 || (_t8 = _`
					<div class="select-link-container">
						<span class="select-link-icon ui-icon-set ${0}"></span>
						<a class="select-link" href="${0}">${0}</a>
					</div>
				`), icon.code, link, text);
	      selectContentItems.push(contentItemLink);
	    }
	  }
	  return selectContentItems;
	}
	function _getCurrentName2() {
	  let count = 0;
	  let currentName = '';
	  for (const {
	    name,
	    selected
	  } of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	    if (count === 0 || selected === true) {
	      currentName = name;
	    }
	    count++;
	  }
	  return currentName;
	}
	function _getCurrentValue2() {
	  let count = 0;
	  let currentValue = '';
	  for (const {
	    value,
	    selected
	  } of babelHelpers.classPrivateFieldLooseBase(this, _items)[_items]) {
	    if (count === 0 || selected === true) {
	      currentValue = value;
	    }
	    count++;
	  }
	  return currentValue;
	}
	function _titleBindEvents2(selector) {
	  const selectTitle = selector.querySelector('.select-title');
	  if (!selectTitle) {
	    return;
	  }
	  main_core.Event.bind(selectTitle, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _toggleSelect)[_toggleSelect](selector));
	}
	function _toggleSelect2(selector) {
	  babelHelpers.classPrivateFieldLooseBase(this, _closeOtherSelects)[_closeOtherSelects](selector);
	  const selectContent = selector.querySelector('.select-content');
	  const isActive = selector.getAttribute('data-state') === 'active';
	  if (isActive) {
	    babelHelpers.classPrivateFieldLooseBase(this, _closeSelect)[_closeSelect](selector, selectContent);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _openSelect)[_openSelect](selector, selectContent);
	  }
	}
	function _closeOtherSelects2(currentSelector) {
	  document.querySelectorAll('.select').forEach(selectField => {
	    if (currentSelector !== selectField) {
	      selectField.setAttribute('data-state', '');
	      setTimeout(() => {
	        const selectContent = selectField.querySelector('.select-content');
	        babelHelpers.classPrivateFieldLooseBase(this, _prepareSelectContent)[_prepareSelectContent](selectContent);
	      }, SelectorField.timeTransition);
	    }
	  });
	}
	function _closeSelect2(selector, selectContent) {
	  selector.setAttribute('data-state', '');
	  setTimeout(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _prepareSelectContent)[_prepareSelectContent](selectContent);
	  }, SelectorField.timeTransition);
	}
	function _openSelect2(selector, selectContent) {
	  const posSelectContent = selectContent.getBoundingClientRect();
	  const heightToBottom = window.innerHeight - (posSelectContent.top + posSelectContent.height);
	  if (heightToBottom > SelectorField.extraHeightOffset) {
	    babelHelpers.classPrivateFieldLooseBase(this, _prepareSelectContent)[_prepareSelectContent](selectContent);
	  } else {
	    main_core.Dom.addClass(selectContent, 'select-content-reverse');
	    const valueTop = posSelectContent.height + SelectorField.popupOffset;
	    main_core.Dom.style(selectContent, 'top', `-${valueTop}px`);
	  }
	  selector.setAttribute('data-state', 'active');
	}
	function _labelBindEvents2(selector) {
	  const selectLabels = selector.querySelectorAll('.select-label-container .select-label');
	  const selectTitle = selector.querySelector('.select-title');
	  const selectInput = selector.querySelector('.select-input');
	  if (selectLabels && selectTitle && selectInput) {
	    for (const label of selectLabels) {
	      const labelContainer = label.parentNode;
	      main_core.Event.bind(labelContainer, 'click', () => {
	        selector.setAttribute('data-state', '');
	        setTimeout(() => {
	          const selectContent = selector.querySelector('.select-content');
	          babelHelpers.classPrivateFieldLooseBase(this, _prepareSelectContent)[_prepareSelectContent](selectContent);
	        }, SelectorField.timeTransition);
	        if (!main_core.Dom.hasClass(label.parentNode, 'selected')) {
	          selectTitle.textContent = label.textContent;
	          selectInput.setAttribute('value', label.getAttribute('value'));
	          BX.UI.ButtonPanel.show();
	          const selectedItem = selector.querySelector('.select-label-container.selected');
	          if (selectedItem) {
	            main_core.Dom.removeClass(selectedItem, 'selected');
	          }
	          main_core.Dom.addClass(label.parentNode, 'selected');
	        }
	      });
	    }
	  }
	}
	function _prepareSelectContent2(element) {
	  main_core.Dom.removeClass(element, 'select-content-reverse');
	  main_core.Dom.style(element, 'top', SelectorField.defaultTopPosition);
	}
	SelectorField.timeTransition = 300;
	SelectorField.defaultTopPosition = '45px';
	SelectorField.extraHeightOffset = 70;
	SelectorField.popupOffset = 5;

	exports.SelectorField = SelectorField;

}((this.BX.AI.UI.Field = this.BX.AI.UI.Field || {}),BX.UI.FormElements,BX,BX));
//# sourceMappingURL=selectorfield.bundle.js.map
