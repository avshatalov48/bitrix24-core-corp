/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_helper,ui_entitySelector,sign_v2_b2e_userPartyCounters,sign_v2_b2e_userPartyPopup,sign_v2_api) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	const Mode = Object.freeze({
	  view: 'view',
	  edit: 'edit'
	});
	const defaultAvatarLink = '/bitrix/js/sign/v2/b2e/user-party/images/user.svg';
	const departmentAvatarLink = '/bitrix/js/sign/v2/b2e/user-party/images/department.svg';
	const HelpdeskCodes = Object.freeze({
	  SignEdmWithEmployees: '19740792'
	});
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _preselectedUserData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preselectedUserData");
	var _viewMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewMode");
	var _tagSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelector");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _userPartyCounters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userPartyCounters");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _userPartyPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userPartyPopup");
	var _counterDelayTimeout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("counterDelayTimeout");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _createUserPartyPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createUserPartyPopup");
	var _displayShowMoreBtn = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("displayShowMoreBtn");
	var _loadPreselectedUsersData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadPreselectedUsersData");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _removeItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeItem");
	var _addItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addItem");
	var _updateEditModeCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateEditModeCounter");
	var _updateCounterWithDelay = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCounterWithDelay");
	var _createItemLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createItemLayout");
	var _createDepartmentItemLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDepartmentItemLayout");
	var _createUserItemLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createUserItemLayout");
	var _clean = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clean");
	var _getViewModeItemsCount = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getViewModeItemsCount");
	class UserParty {
	  constructor(_options) {
	    Object.defineProperty(this, _getViewModeItemsCount, {
	      value: _getViewModeItemsCount2
	    });
	    Object.defineProperty(this, _clean, {
	      value: _clean2
	    });
	    Object.defineProperty(this, _createUserItemLayout, {
	      value: _createUserItemLayout2
	    });
	    Object.defineProperty(this, _createDepartmentItemLayout, {
	      value: _createDepartmentItemLayout2
	    });
	    Object.defineProperty(this, _createItemLayout, {
	      value: _createItemLayout2
	    });
	    Object.defineProperty(this, _updateCounterWithDelay, {
	      value: _updateCounterWithDelay2
	    });
	    Object.defineProperty(this, _updateEditModeCounter, {
	      value: _updateEditModeCounter2
	    });
	    Object.defineProperty(this, _addItem, {
	      value: _addItem2
	    });
	    Object.defineProperty(this, _removeItem, {
	      value: _removeItem2
	    });
	    Object.defineProperty(this, _getLoader, {
	      value: _getLoader2
	    });
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _loadPreselectedUsersData, {
	      value: _loadPreselectedUsersData2
	    });
	    Object.defineProperty(this, _displayShowMoreBtn, {
	      value: _displayShowMoreBtn2
	    });
	    Object.defineProperty(this, _createUserPartyPopup, {
	      value: _createUserPartyPopup2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        itemContainer: HTMLDivElement = null,
	        header: HTMLSpanElement = null,
	        description: HTMLParagraphElement = null,
	        userPartyCounterContainer: HTMLDivElement = null,
	        showMoreSignersContainer: HTMLDivElement = null
	      }
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _preselectedUserData, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _viewMode, {
	      writable: true,
	      value: Mode.edit
	    });
	    Object.defineProperty(this, _tagSelector, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _userPartyCounters, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _userPartyPopup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _counterDelayTimeout, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] = _options.mode;
	    babelHelpers.classPrivateFieldLooseBase(this, _init)[_init](_options);
	  }
	  getLayout(region) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer = main_core.Tag.render(_t || (_t = _`
			<div class="sign-document-b2e-user-party__item-list"></div>
		`));
	    if (babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] !== Mode.edit) {
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer, '--view');
	      const link = main_core.Tag.render(_t2 || (_t2 = _`
				<a href="#">${0}</a>
			`), main_core.Loc.getMessage('SIGN_USER_PARTY_VIEW_SHOW_MORE', {
	        '#EMPLOYEE_COUNT#': '<span class="--count-placeholder">…</span>'
	      }));
	      babelHelpers.classPrivateFieldLooseBase(this, _userPartyPopup)[_userPartyPopup] = babelHelpers.classPrivateFieldLooseBase(this, _createUserPartyPopup)[_createUserPartyPopup](link);
	      main_core.Event.bind(link, 'click', event => {
	        babelHelpers.classPrivateFieldLooseBase(this, _userPartyPopup)[_userPartyPopup].setDocumentUid(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid]).show();
	        event.preventDefault();
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer = main_core.Tag.render(_t3 || (_t3 = _`
				<div class="sign-document-b2e-user-party__item-show_more">
					${0}
				</div>
			`), link);
	      main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer);
	      main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer);
	      return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer;
	    }
	    const descriptionMessage = region === 'ru' ? sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_USER_PARTY_DESCRIPTION'), HelpdeskCodes.SignEdmWithEmployees) : main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_CHOOSE_MEMBER_USER_PARTY_DESCRIPTION');
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description = main_core.Tag.render(_t4 || (_t4 = _`
			<p class="sign-document-b2e-user-party__description">
				${0}
			</p>
		`), descriptionMessage);
	    return main_core.Tag.render(_t5 || (_t5 = _`
			<div>
				<div class="sign-b2e-settings__header-wrapper">
					<h1 class="sign-b2e-settings__header">${0}</h1>
					${0}
				</div>
				<div class="sign-b2e-settings__item">
					<p class="sign-b2e-settings__item_title">
						${0}
					</p>
					${0}
					${0}
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_USER_PARTY_HEADER'), babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters].getLayout(), main_core.Loc.getMessage('SIGN_USER_PARTY_ITEM_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description);
	  }
	  async load(ids) {
	    const {
	      dialog
	    } = babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector];
	    dialog.preselectedItems = ids.map(userId => ['user', userId]);
	    const promise = new Promise(resolve => {
	      dialog.subscribeOnce('onLoad', resolve);
	    });
	    dialog.load();
	    await promise;
	  }
	  async setSignersIds(usersData) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clean)[_clean]();
	    const maxShownItems = babelHelpers.classPrivateFieldLooseBase(this, _getViewModeItemsCount)[_getViewModeItemsCount]();
	    babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData] = usersData.sort((a, b) => a.entityType === 'department' ? -1 : 1).slice(0, maxShownItems);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData].length < maxShownItems) {
	      const membersResponse = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getMembersForDocument(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid], 1, maxShownItems);
	      const preselectedIds = new Set(usersData.filter(item => item.entityType === 'user').map(item => item.entityId));
	      const addMembers = membersResponse.members.filter(member => !preselectedIds.has(member.userId)).slice(0, maxShownItems - babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData].length);
	      babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData] = [...babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData], ...addMembers.map(member => {
	        return {
	          entityType: 'user',
	          entityId: member.userId
	        };
	      })];
	    }

	    // workaround because prepend is used in the interface instead of append
	    babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData].reverse();
	    await babelHelpers.classPrivateFieldLooseBase(this, _loadPreselectedUsersData)[_loadPreselectedUsersData]();
	    babelHelpers.classPrivateFieldLooseBase(this, _displayShowMoreBtn)[_displayShowMoreBtn]();
	  }
	  validate() {
	    const isValid = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size > 0 && babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters].getCount() > 0;
	    const tagSelectorContainer = babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].getOuterContainer();
	    if (isValid) {
	      main_core.Dom.removeClass(tagSelectorContainer, '--invalid');
	    } else {
	      main_core.Dom.addClass(tagSelectorContainer, '--invalid');
	    }
	    return isValid;
	  }
	  getUserIds() {
	    return [...babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].keys()];
	  }
	  getEntities() {
	    return [...babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].values()];
	  }
	  resetUserPartyPopup() {
	    babelHelpers.classPrivateFieldLooseBase(this, _userPartyPopup)[_userPartyPopup].resetData();
	  }
	  setDocumentUid(uid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = uid;
	  }
	}
	function _init2(options) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] === Mode.view) {
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = this.getLayout(options.region);
	    return;
	  }
	  const {
	    b2eSignersLimitCount,
	    region
	  } = options;
	  babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters] = new sign_v2_b2e_userPartyCounters.UserPartyCounters({
	    userCountersLimit: b2eSignersLimitCount
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container = this.getLayout(region);
	  babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector] = new ui_entitySelector.TagSelector({
	    events: {
	      onTagRemove: event => {
	        const {
	          tag
	        } = event.getData();
	        babelHelpers.classPrivateFieldLooseBase(this, _removeItem)[_removeItem](tag);
	      },
	      onTagAdd: event => {
	        const {
	          tag
	        } = event.getData();
	        babelHelpers.classPrivateFieldLooseBase(this, _addItem)[_addItem](tag);
	      }
	    },
	    dialogOptions: {
	      width: 425,
	      height: 363,
	      multiple: true,
	      targetNode: babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer,
	      context: 'sign_b2e_user_party',
	      entities: [{
	        id: 'user',
	        options: {
	          intranetUsersOnly: true
	        }
	      }, {
	        id: 'department',
	        options: {
	          selectMode: 'usersAndDepartments',
	          fillRecentTab: true,
	          allowFlatDepartments: true
	        }
	      }],
	      dropdownMode: false,
	      hideOnDeselect: false
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer);
	}
	function _createUserPartyPopup2(bindElement) {
	  return new sign_v2_b2e_userPartyPopup.UserPartyPopup({
	    bindElement
	  });
	}
	async function _displayShowMoreBtn2() {
	  const shownUsers = babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData].reduce((count, item) => item.entityType === 'user' ? count + 1 : count, 0);
	  const signersCountResponse = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getUniqUserCountForDocument(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid]);
	  const showMoreCount = signersCountResponse.count - shownUsers;
	  if (showMoreCount > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer.querySelector('.--count-placeholder').textContent = showMoreCount;
	    main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer.querySelector('.--count-placeholder').textContent = 0;
	    main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].showMoreSignersContainer);
	  }
	}
	async function _loadPreselectedUsersData2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	  await new Promise(resolve => {
	    const dialog = new ui_entitySelector.Dialog({
	      entities: [{
	        id: 'user'
	      }],
	      events: {
	        onLoad: () => {
	          dialog.getSelectedItems().forEach(item => {
	            babelHelpers.classPrivateFieldLooseBase(this, _addItem)[_addItem](item);
	          });
	          resolve();
	        }
	      },
	      preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserData)[_preselectedUserData].map(entity => {
	        return [entity.entityType, entity.entityId];
	      })
	    });
	    dialog.load();
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	}
	function _showLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer.style.display = 'none';
	  babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().show();
	}
	function _hideLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer.style.display = 'flex';
	  babelHelpers.classPrivateFieldLooseBase(this, _getLoader)[_getLoader]().hide();
	}
	function _getLoader2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new BX.Loader({
	    target: babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].container,
	    mode: 'inline',
	    size: 40
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader];
	}
	function _removeItem2(tag) {
	  const userId = tag.id;
	  const item = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].get(userId);
	  if (item != null && item.container) {
	    main_core.Dom.remove(item.container);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].delete(userId);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateEditModeCounter)[_updateEditModeCounter]();
	}
	function _addItem2(tag) {
	  var _tag$customData, _tag$customData2;
	  const item = {
	    id: tag.id,
	    title: tag == null ? void 0 : tag.title.text,
	    name: (_tag$customData = tag.customData) == null ? void 0 : _tag$customData.get('name'),
	    lastName: (_tag$customData2 = tag.customData) == null ? void 0 : _tag$customData2.get('lastName'),
	    avatar: tag == null ? void 0 : tag.avatar,
	    entityId: tag.id,
	    entityType: tag == null ? void 0 : tag.entityId
	  };
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] === Mode.view ? babelHelpers.classPrivateFieldLooseBase(this, _createItemLayout)[_createItemLayout](item) : null;
	  if (container) {
	    main_core.Dom.prepend(container, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer);
	  }
	  item.container = container;
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].set(item.id, item);
	  babelHelpers.classPrivateFieldLooseBase(this, _updateEditModeCounter)[_updateEditModeCounter]();
	}
	function _updateEditModeCounter2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] === Mode.edit) {
	    babelHelpers.classPrivateFieldLooseBase(this, _updateCounterWithDelay)[_updateCounterWithDelay](babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].getTags().map(member => {
	      return {
	        entityId: member.id,
	        entityType: member.entityId
	      };
	    }));
	  }
	}
	function _updateCounterWithDelay2(selectedMembers) {
	  clearTimeout(babelHelpers.classPrivateFieldLooseBase(this, _counterDelayTimeout)[_counterDelayTimeout]);
	  babelHelpers.classPrivateFieldLooseBase(this, _counterDelayTimeout)[_counterDelayTimeout] = setTimeout(async () => {
	    var _babelHelpers$classPr;
	    const response = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getUniqUserCountForMembers(selectedMembers);
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters]) == null ? void 0 : _babelHelpers$classPr.update(response.count);
	  }, 100);
	}
	function _createItemLayout2(item) {
	  return item.entityType === 'department' ? babelHelpers.classPrivateFieldLooseBase(this, _createDepartmentItemLayout)[_createDepartmentItemLayout](item) : babelHelpers.classPrivateFieldLooseBase(this, _createUserItemLayout)[_createUserItemLayout](item);
	}
	function _createDepartmentItemLayout2(item) {
	  const title = main_core.Text.encode(item.title);
	  return main_core.Tag.render(_t6 || (_t6 = _`
			<div class="sign-document-b2e-user-party__item-list_item --department">
				<div>
					<img
						class="sign-document-b2e-user-party__item-list_item-avatar"
						title="${0}" src='${0}' alt="avatar"
					/>
				</div>
				<div title="${0}" class="sign-document-b2e-user-party__item-list_item-text">
					${0}
				</div>
			</div>
		`), title, departmentAvatarLink, title, title);
	}
	function _createUserItemLayout2(item) {
	  const title = main_core.Text.encode(item.title);
	  const itemAvatar = item.avatar || defaultAvatarLink;
	  const profileLink = `/company/personal/user/${main_core.Text.encode(item.entityId)}/`;
	  return main_core.Tag.render(_t7 || (_t7 = _`
			<div class="sign-document-b2e-user-party__item-list_item --user">
				<a href="${0}">
					<img
						class="sign-document-b2e-user-party__item-list_item-avatar"
						title="${0}" src='${0}' alt="avatar"
					/>
				</a>
				<div title="${0}" class="sign-document-b2e-user-party__item-list_item-text">
					${0}
				</div>
			</div>
		`), profileLink, title, main_core.Text.encode(itemAvatar), title, title);
	}
	function _clean2() {
	  var _babelHelpers$classPr2;
	  [...babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].values()].forEach(item => main_core.Dom.remove(item.container));
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].clear();
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters]) == null ? void 0 : _babelHelpers$classPr2.update(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size);
	}
	function _getViewModeItemsCount2() {
	  return 7; // for fixed slider width
	}

	exports.UserParty = UserParty;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2,BX.UI.EntitySelector,BX.Sign.V2.B2e,BX.Sign.V2.B2e,BX.Sign.V2));
//# sourceMappingURL=user-party.bundle.js.map
