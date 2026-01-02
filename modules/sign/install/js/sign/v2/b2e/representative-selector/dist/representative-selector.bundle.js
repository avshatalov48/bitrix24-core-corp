/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_popup,sign_v2_b2e_userSelector,sign_v2_helper,sign_type) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16;
	const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
	const HelpdeskCodes = Object.freeze({
	  WhoCanBeRepresentative: '19740734'
	});
	const representativeSelectorMenuButtonId = 'sign-document-b2e-representative_selector_menu_button';
	var _userSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userSelector");
	var _description = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("description");
	var _isDescriptionVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isDescriptionVisible");
	var _isMenuButtonVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMenuButtonVisible");
	var _isMenuVisible = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isMenuVisible");
	var _uuid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uuid");
	var _itemType = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("itemType");
	var _onDelete = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onDelete");
	var _onSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelect");
	var _onHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onHide");
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _showMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showMenu");
	var _setInfoState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setInfoState");
	var _setEmptyState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEmptyState");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _onChangeButtonClickHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onChangeButtonClickHandler");
	var _onSelectorDialogHide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelectorDialogHide");
	var _onSelectorItemSelectedHandler = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onSelectorItemSelectedHandler");
	var _showItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showItem");
	var _showUserItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showUserItem");
	var _showRoleItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showRoleItem");
	var _refreshView = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refreshView");
	class RepresentativeSelector {
	  constructor(options = {}) {
	    var _options$isDescriptio, _options$isMenuButton, _options$roleEnabled, _options$context, _options$excludedEnti, _options$onDelete, _options$onSelect, _options$onHide;
	    Object.defineProperty(this, _refreshView, {
	      value: _refreshView2
	    });
	    Object.defineProperty(this, _showRoleItem, {
	      value: _showRoleItem2
	    });
	    Object.defineProperty(this, _showUserItem, {
	      value: _showUserItem2
	    });
	    Object.defineProperty(this, _showItem, {
	      value: _showItem2
	    });
	    Object.defineProperty(this, _onSelectorItemSelectedHandler, {
	      value: _onSelectorItemSelectedHandler2
	    });
	    Object.defineProperty(this, _onSelectorDialogHide, {
	      value: _onSelectorDialogHide2
	    });
	    Object.defineProperty(this, _onChangeButtonClickHandler, {
	      value: _onChangeButtonClickHandler2
	    });
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _setEmptyState, {
	      value: _setEmptyState2
	    });
	    Object.defineProperty(this, _setInfoState, {
	      value: _setInfoState2
	    });
	    Object.defineProperty(this, _showMenu, {
	      value: _showMenu2
	    });
	    Object.defineProperty(this, _userSelector, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _description, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _isDescriptionVisible, {
	      writable: true,
	      value: true
	    });
	    Object.defineProperty(this, _isMenuButtonVisible, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isMenuVisible, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _uuid, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _itemType, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onDelete, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onSelect, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _onHide, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        info: {
	          container: HTMLDivElement = null,
	          avatar: HTMLImageElement = null,
	          title: {
	            container: HTMLDivElement = null,
	            name: HTMLDivElement = null,
	            position: HTMLDivElement = null
	          }
	        },
	        changeBtn: {
	          container: HTMLDivElement = null,
	          element: HTMLDivElement = null
	        },
	        menuBtn: {
	          container: HTMLDivElement = null,
	          element: HTMLSpanElement = null
	        },
	        select: {
	          container: HTMLDivElement = null,
	          text: HTMLSpanElement = null,
	          button: HTMLButtonElement = null
	        },
	        description: HTMLParagraphElement = null
	      }
	    });
	    Object.defineProperty(this, _data, {
	      writable: true,
	      value: {
	        id: null,
	        name: null,
	        position: null,
	        avatarLink: null
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = main_core.Type.isInteger(options.userId) ? options.userId : null;
	    babelHelpers.classPrivateFieldLooseBase(this, _description)[_description] = options.description;
	    babelHelpers.classPrivateFieldLooseBase(this, _isDescriptionVisible)[_isDescriptionVisible] = (_options$isDescriptio = options.isDescriptionVisible) != null ? _options$isDescriptio : true;
	    babelHelpers.classPrivateFieldLooseBase(this, _isMenuButtonVisible)[_isMenuButtonVisible] = (_options$isMenuButton = options.isMenuButtonVisible) != null ? _options$isMenuButton : false;
	    babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector] = new sign_v2_b2e_userSelector.UserSelector({
	      cacheable: options.cacheable,
	      multiple: false,
	      roleEnabled: (_options$roleEnabled = options.roleEnabled) != null ? _options$roleEnabled : false,
	      context: (_options$context = options.context) != null ? _options$context : 'sign_b2e_representative_selector',
	      excludedEntityList: (_options$excludedEnti = options.excludedEntityList) != null ? _options$excludedEnti : []
	    });
	    const defaultCallback = () => null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onDelete)[_onDelete] = (_options$onDelete = options.onDelete) != null ? _options$onDelete : defaultCallback;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSelect)[_onSelect] = (_options$onSelect = options.onSelect) != null ? _options$onSelect : defaultCallback;
	    babelHelpers.classPrivateFieldLooseBase(this, _onHide)[_onHide] = (_options$onHide = options.onHide) != null ? _options$onHide : defaultCallback;
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = this.getLayout();
	  }
	  setExcludedEntityList(excludedEntityList) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].setExcludedEntityList(excludedEntityList);
	  }
	  getContainerId() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _uuid)[_uuid]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _uuid)[_uuid] = main_core.Text.getRandom();
	    }
	    return `sign_b2e_representative_selector_${babelHelpers.classPrivateFieldLooseBase(this, _uuid)[_uuid]}`;
	  }
	  getLayout() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.name = main_core.Tag.render(_t || (_t = _`
			<div class="sign-document-b2e-representative-info-user-name"></div>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.position = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="sign-document-b2e-representative-info-user-pos"></div>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.avatar = main_core.Tag.render(_t3 || (_t3 = _`
			<img src="${0}">
		`), defaultAvatarLink);
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.container = main_core.Tag.render(_t4 || (_t4 = _`
			<div class="sign-document-b2e-representative-info-user-title">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.name, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.position);
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.text = main_core.Tag.render(_t5 || (_t5 = _`
			<span class="sign-document-b2e-representative-select-text">
				${0}
			</span>
		`), main_core.Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_SELECT_TEXT'));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.button = main_core.Tag.render(_t6 || (_t6 = _`
			<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-round">
				${0}
			</button>
		`), main_core.Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_SELECT_BUTTON'));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.container = main_core.Tag.render(_t7 || (_t7 = _`
			<div class="sign-document-b2e-representative-select">
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.text, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.button);
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.element = main_core.Tag.render(_t8 || (_t8 = _`
			<span class="sign-document-b2e-representative-change-btn"></span>
		`));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.container = main_core.Tag.render(_t9 || (_t9 = _`
			<div class="sign-document-b2e-representative-change">
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.element);
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].menuBtn.container = main_core.Tag.render(_t10 || (_t10 = _`
			<div id="${0}" class="sign-document-b2e-company-info-edit" onclick="${0}"></div>
		`), representativeSelectorMenuButtonId, () => babelHelpers.classPrivateFieldLooseBase(this, _showMenu)[_showMenu]());
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.container = main_core.Tag.render(_t11 || (_t11 = _`
			<div class="sign-document-b2e-representative-info">
				<div class="sign-document-b2e-representative-info-user-photo">
					${0}
				</div>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.avatar, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.container);
	    let description = '';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isDescriptionVisible)[_isDescriptionVisible]) {
	      description = babelHelpers.classPrivateFieldLooseBase(this, _description)[_description] ? main_core.Tag.render(_t12 || (_t12 = _`<p class="sign-document-b2e-representative__info_paragraph">${0}</p>`), babelHelpers.classPrivateFieldLooseBase(this, _description)[_description]) : main_core.Tag.render(_t13 || (_t13 = _`
					<span>
						${0}
					</span>
				`), sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_PARTIES_REPRESENTATIVE_INFO'), HelpdeskCodes.WhoCanBeRepresentative));
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description = main_core.Tag.render(_t14 || (_t14 = _`
			<div class="sign-document-b2e-representative__info">
				${0}
			</div>
		`), description);
	    const menuBtn = babelHelpers.classPrivateFieldLooseBase(this, _isMenuButtonVisible)[_isMenuButtonVisible] ? babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].menuBtn.container : main_core.Tag.render(_t15 || (_t15 = _``));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = main_core.Tag.render(_t16 || (_t16 = _`
			<div id="${0}">
				<div class="sign-document-b2e-representative__selector">
					${0}
					${0}
					${0}
					${0}
				</div>
				${0}
			</div>
		`), this.getContainerId(), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.container, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.container, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.container, menuBtn, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description);
	    babelHelpers.classPrivateFieldLooseBase(this, _setEmptyState)[_setEmptyState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	    return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	  }
	  formatSelectButton(className) {
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.button.className = `ui-btn ${className}`;
	  }
	  format(id, className) {
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui][id].className = className;
	  }
	  validate() {
	    const isValid = main_core.Type.isInteger(this.getRepresentativeId()) && this.getRepresentativeId() > 0;
	    if (isValid) {
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container.firstElementChild, '--invalid');
	    } else {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container.firstElementChild, '--invalid');
	    }
	    return isValid;
	  }
	  load(representativeId, entityType = sign_type.EntityType.USER) {
	    const dialog = babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].getDialog();
	    dialog.subscribeOnce('onLoad', () => {
	      const userItems = dialog.items.get(entityType);
	      const userItem = userItems.get(`${representativeId}`);
	      userItem.select();
	      babelHelpers.classPrivateFieldLooseBase(this, _showItem)[_showItem](userItem);
	    });
	    dialog.load();
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = representativeId;
	    babelHelpers.classPrivateFieldLooseBase(this, _itemType)[_itemType] = entityType;
	  }
	  loadFistRepresentative() {
	    const dialog = babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].getDialog();
	    dialog.subscribeOnce('onLoad', () => {
	      const userItems = dialog.items.get('user');
	      if (userItems.size === 0) {
	        return;
	      }
	      const firstUserItem = userItems.values().next().value;
	      firstUserItem.select();
	      babelHelpers.classPrivateFieldLooseBase(this, _showItem)[_showItem](firstUserItem);
	    });
	    dialog.load();
	  }
	  getRepresentativeId() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id;
	  }
	  getRepresentativeItemType() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _itemType)[_itemType];
	  }
	  onSelectorItemDeselectedHandler(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _onSelectorItemSelectedHandler)[_onSelectorItemSelectedHandler](event);
	    babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].setPreselectedEntityList([]);
	  }
	}
	function _showMenu2() {
	  const representativeSelectorElement = document.getElementById(this.getContainerId());
	  if (representativeSelectorElement === null) {
	    return;
	  }
	  const menuButtonElement = representativeSelectorElement.querySelector(`#${representativeSelectorMenuButtonId}`);
	  if (menuButtonElement === null) {
	    return;
	  }
	  const menu = new main_popup.Menu({
	    bindElement: menuButtonElement,
	    cacheable: false,
	    events: {
	      onPopupClose: popup => {
	        babelHelpers.classPrivateFieldLooseBase(this, _isMenuVisible)[_isMenuVisible] = false;
	      }
	    }
	  });
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isMenuVisible)[_isMenuVisible]) {
	    menu.close();
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _isMenuVisible)[_isMenuVisible] = true;
	  menu.addMenuItem({
	    text: main_core.Loc.getMessage('SIGN_B2E_REPRESENTATIVE_SELECTOR_DELETE_BUTTON_TITLE'),
	    onclick: () => {
	      representativeSelectorElement.remove();
	      babelHelpers.classPrivateFieldLooseBase(this, _onDelete)[_onDelete](this.getContainerId());
	      menu.close();
	    }
	  });
	  menu.show();
	}
	function _setInfoState2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.container.style.display = 'flex';
	  BX.show(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.container);
	  BX.show(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description);
	  BX.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.container);
	}
	function _setEmptyState2() {
	  BX.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.container);
	  BX.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.container);
	  BX.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description);
	  BX.show(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.container);
	}
	function _bindEvents2() {
	  BX.bind(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].changeBtn.element, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _onChangeButtonClickHandler)[_onChangeButtonClickHandler]());
	  BX.bind(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].select.button, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _onChangeButtonClickHandler)[_onChangeButtonClickHandler]());
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].subscribe(sign_v2_b2e_userSelector.UserSelectorEvent.onItemSelect, event => babelHelpers.classPrivateFieldLooseBase(this, _onSelectorItemSelectedHandler)[_onSelectorItemSelectedHandler](event));
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].subscribe(sign_v2_b2e_userSelector.UserSelectorEvent.onItemDeselect, event => this.onSelectorItemDeselectedHandler(event));
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].subscribe(sign_v2_b2e_userSelector.UserSelectorEvent.onHide, event => babelHelpers.classPrivateFieldLooseBase(this, _onSelectorDialogHide)[_onSelectorDialogHide](event));
	}
	function _onChangeButtonClickHandler2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].getDialog().setTargetNode(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container.firstElementChild);
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].toggle();
	}
	function _onSelectorDialogHide2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _onHide)[_onHide](this.getContainerId());
	}
	function _onSelectorItemSelectedHandler2(event) {
	  var _event$data, _event$data2, _event$data2$items;
	  babelHelpers.classPrivateFieldLooseBase(this, _onSelect)[_onSelect](this.getContainerId());
	  if (!(event != null && (_event$data = event.data) != null && _event$data.items) || Number(event == null ? void 0 : (_event$data2 = event.data) == null ? void 0 : (_event$data2$items = _event$data2.items) == null ? void 0 : _event$data2$items.length) === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _setEmptyState)[_setEmptyState]();
	    babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].setPreselectedEntityList([]);
	    return;
	  }
	  const item = event.data.items[0];
	  babelHelpers.classPrivateFieldLooseBase(this, _userSelector)[_userSelector].setPreselectedEntityList([{
	    id: item.id,
	    type: item.entityId
	  }]);
	  babelHelpers.classPrivateFieldLooseBase(this, _showItem)[_showItem](item);
	}
	function _showItem2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _itemType)[_itemType] = item.entityId;
	  if (item.entityId === sign_type.EntityType.USER) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showUserItem)[_showUserItem](item);
	  } else if (item.entityId === sign_type.EntityType.STRUCTURE_NODE_ROLE) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showRoleItem)[_showRoleItem](item);
	  }
	}
	function _showUserItem2(item) {
	  var _item$customData$get, _item$customData, _item$customData$get2, _item$customData2, _item$customData4;
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = item.id;
	  if (!main_core.Type.isInteger(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id) || babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id <= 0) {
	    return;
	  }
	  const name = (_item$customData$get = (_item$customData = item.customData) == null ? void 0 : _item$customData.get('name')) != null ? _item$customData$get : '';
	  const lastName = (_item$customData$get2 = (_item$customData2 = item.customData) == null ? void 0 : _item$customData2.get('lastName')) != null ? _item$customData$get2 : '';
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name = main_core.Type.isStringFilled(name) ? name : '';
	  if (main_core.Type.isStringFilled(lastName)) {
	    if (main_core.Type.isStringFilled(name)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name += ' ';
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name += lastName;
	  }
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name)) {
	    var _item$customData$get3, _item$customData3;
	    babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name = (_item$customData$get3 = (_item$customData3 = item.customData) == null ? void 0 : _item$customData3.get('login')) != null ? _item$customData$get3 : '';
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].position = ((_item$customData4 = item.customData) == null ? void 0 : _item$customData4.get('position')) || '';
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].avatarLink = (item == null ? void 0 : item.avatar) || defaultAvatarLink;
	  babelHelpers.classPrivateFieldLooseBase(this, _refreshView)[_refreshView]();
	}
	function _showRoleItem2(item) {
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id = item.id;
	  if (!main_core.Type.isInteger(babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id) || babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].id <= 0) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].name = item.title;
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].avatarLink = defaultAvatarLink;
	  babelHelpers.classPrivateFieldLooseBase(this, _data)[_data].position = '';
	  babelHelpers.classPrivateFieldLooseBase(this, _refreshView)[_refreshView]();
	}
	function _refreshView2() {
	  var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.name.innerText = main_core.Text.encode((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) == null ? void 0 : _babelHelpers$classPr.name);
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.title.position.innerText = main_core.Text.encode((_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) == null ? void 0 : _babelHelpers$classPr2.position);
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].info.avatar.src = (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _data)[_data]) == null ? void 0 : _babelHelpers$classPr3.avatarLink;
	  babelHelpers.classPrivateFieldLooseBase(this, _setInfoState)[_setInfoState]();
	}

	exports.RepresentativeSelector = RepresentativeSelector;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Main,BX.Sign.V2.B2e,BX.Sign.V2,BX.Sign));
//# sourceMappingURL=representative-selector.bundle.js.map
