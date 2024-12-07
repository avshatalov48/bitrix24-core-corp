/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,sign_v2_helper,ui_entitySelector,sign_v2_b2e_userPartyCounters) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	const Mode = Object.freeze({
	  view: 'view',
	  edit: 'edit'
	});
	const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
	const HelpdeskCodes = Object.freeze({
	  SignEdmWithEmployees: '19740792'
	});
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _items = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("items");
	var _preselectedUserIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preselectedUserIds");
	var _viewMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewMode");
	var _tagSelector = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("tagSelector");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _userPartyCounters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userPartyCounters");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _loadPreselectedUsersData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadPreselectedUsersData");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _removeItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeItem");
	var _addItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addItem");
	var _createItemLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createItemLayout");
	var _clean = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("clean");
	class UserParty {
	  constructor(_options) {
	    Object.defineProperty(this, _clean, {
	      value: _clean2
	    });
	    Object.defineProperty(this, _createItemLayout, {
	      value: _createItemLayout2
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
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        container: HTMLDivElement = null,
	        itemContainer: HTMLDivElement = null,
	        header: HTMLSpanElement = null,
	        description: HTMLParagraphElement = null,
	        userPartyCounterContainer: HTMLDivElement = null
	      }
	    });
	    Object.defineProperty(this, _items, {
	      writable: true,
	      value: new Map()
	    });
	    Object.defineProperty(this, _preselectedUserIds, {
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
	      return babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer;
	    }
	    const descriptionMessage = region === 'ru' ? sign_v2_helper.Helpdesk.replaceLink(main_core.Loc.getMessage('SIGN_USER_PARTY_DESCRIPTION'), HelpdeskCodes.SignEdmWithEmployees) : main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_CHOOSE_MEMBER_USER_PARTY_DESCRIPTION');
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].description = main_core.Tag.render(_t2 || (_t2 = _`
			<p class="sign-document-b2e-user-party__description">
				${0}
			</p>
		`), descriptionMessage);
	    return main_core.Tag.render(_t3 || (_t3 = _`
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
	  setUsersIds(usersData) {
	    babelHelpers.classPrivateFieldLooseBase(this, _clean)[_clean]();
	    babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserIds)[_preselectedUserIds] = usersData;
	    babelHelpers.classPrivateFieldLooseBase(this, _loadPreselectedUsersData)[_loadPreselectedUsersData]();
	  }
	  validate() {
	    const isValid = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size > 0;
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
	        babelHelpers.classPrivateFieldLooseBase(this, _removeItem)[_removeItem](tag.id);
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
	      entities: [{
	        id: 'user',
	        options: {
	          intranetUsersOnly: true
	        }
	      }],
	      dropdownMode: true,
	      hideOnDeselect: true
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _tagSelector)[_tagSelector].renderTo(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer);
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
	      preselectedItems: babelHelpers.classPrivateFieldLooseBase(this, _preselectedUserIds)[_preselectedUserIds].map(userId => {
	        return ['user', userId];
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
	function _removeItem2(userId) {
	  var _babelHelpers$classPr;
	  const item = babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].get(userId);
	  if (item != null && item.container) {
	    main_core.Dom.remove(item.container);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].delete(userId);
	  (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters]) == null ? void 0 : _babelHelpers$classPr.update(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size);
	}
	function _addItem2(tag) {
	  var _tag$customData, _tag$customData2, _babelHelpers$classPr2;
	  const item = {
	    id: tag.id,
	    name: (_tag$customData = tag.customData) == null ? void 0 : _tag$customData.get('name'),
	    lastName: (_tag$customData2 = tag.customData) == null ? void 0 : _tag$customData2.get('lastName'),
	    avatar: tag == null ? void 0 : tag.avatar
	  };
	  const container = babelHelpers.classPrivateFieldLooseBase(this, _viewMode)[_viewMode] === Mode.view ? babelHelpers.classPrivateFieldLooseBase(this, _createItemLayout)[_createItemLayout](item) : null;
	  if (container) {
	    main_core.Dom.append(container, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].itemContainer);
	  }
	  item.container = container;
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].set(item.id, item);
	  (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters]) == null ? void 0 : _babelHelpers$classPr2.update(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size);
	}
	function _createItemLayout2(item) {
	  var _TextFormat$encode, _TextFormat$encode2;
	  const name = (_TextFormat$encode = main_core.Text.encode(item.name)) != null ? _TextFormat$encode : '';
	  const lastName = (_TextFormat$encode2 = main_core.Text.encode(item.lastName)) != null ? _TextFormat$encode2 : '';
	  return main_core.Tag.render(_t4 || (_t4 = _`
			<img
				class="sign-document-b2e-user-party__item-list_item-avatar"
				title="${0} ${0}" src='${0}'
			/>
		`), name, lastName, item.avatar || defaultAvatarLink);
	}
	function _clean2() {
	  var _babelHelpers$classPr3;
	  [...babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].values()].forEach(item => main_core.Dom.remove(item.container));
	  babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].clear();
	  (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _userPartyCounters)[_userPartyCounters]) == null ? void 0 : _babelHelpers$classPr3.update(babelHelpers.classPrivateFieldLooseBase(this, _items)[_items].size);
	}

	exports.UserParty = UserParty;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Sign.V2,BX.UI.EntitySelector,BX.Sign.V2.B2e));
//# sourceMappingURL=user-party.bundle.js.map
