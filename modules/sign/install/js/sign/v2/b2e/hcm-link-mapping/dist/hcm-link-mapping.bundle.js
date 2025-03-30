/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core_events,humanresources_hcmlink_dataMapper,main_core,main_loader,ui_entitySelector,sign_v2_api) {
	'use strict';

	const EntityTypes = Object.freeze({
	  User: 'user',
	  Company: 'company'
	});
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _userDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("userDialog");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _data = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("data");
	var _viewData = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("viewData");
	var _loadCompany = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadCompany");
	var _loadUser = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadUser");
	var _load = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("load");
	var _refreshView = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refreshView");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4$1,
	  _t5$1;
	const maxPreviewUserAvatarCount = 6;
	const defaultAvatarLink = '/bitrix/js/socialnetwork/entity-selector/src/images/default-user.svg';
	var _api$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _integrationId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("integrationId");
	var _employeeIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("employeeIds");
	var _participantsIds = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("participantsIds");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _usersPreviewContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("usersPreviewContainer");
	var _enabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("enabled");
	var _openMapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openMapper");
	var _loadUsersAvatarMap = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadUsersAvatarMap");
	var _updateUsersPreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateUsersPreview");
	var _getUsersPreviewContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getUsersPreviewContainer");
	class HcmLinkMapping extends main_core_events.EventEmitter {
	  constructor(options) {
	    super();
	    Object.defineProperty(this, _getUsersPreviewContainer, {
	      value: _getUsersPreviewContainer2
	    });
	    Object.defineProperty(this, _updateUsersPreview, {
	      value: _updateUsersPreview2
	    });
	    Object.defineProperty(this, _loadUsersAvatarMap, {
	      value: _loadUsersAvatarMap2
	    });
	    Object.defineProperty(this, _openMapper, {
	      value: _openMapper2
	    });
	    Object.defineProperty(this, _api$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _integrationId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _employeeIds, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _participantsIds, {
	      writable: true,
	      value: []
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _usersPreviewContainer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _enabled, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1] = options.api;
	    this.setEventNamespace('BX.Sign.V2.B2e.HcmLinkMapping');
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = this.render();
	  }
	  render() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	    }
	    const {
	      root,
	      syncButton
	    } = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="sign-b2e-hcm-link-party-checker-container --orange">
				<div class="sign-b2e-hcm-link-party-checker-wrapper">
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --left">
						<div class="sign-b2e-hcm-link-party-checker-title">
							${0}
						</div>
						<div class="sign-b2e-hcm-link-party-checker-description">
							${0}
						</div>
					</div>
					<div class="sign-b2e-hcm-link-party-checker-wrapper-part --right">
						${0}
						<div class="sign-b2e-hcm-link-party-checker__action-button" ref="syncButton">
							${0}
						</div>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TITLE'), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_TEXT'), babelHelpers.classPrivateFieldLooseBase(this, _getUsersPreviewContainer)[_getUsersPreviewContainer](), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_MAPPING_SYNC_BUTTON'));
	    main_core.Event.bind(syncButton, 'click', () => babelHelpers.classPrivateFieldLooseBase(this, _openMapper)[_openMapper]());
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = root;
	    this.hide();
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  setEnabled(value) {
	    babelHelpers.classPrivateFieldLooseBase(this, _enabled)[_enabled] = value;
	  }
	  setDocumentUid(uid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = uid;
	  }
	  async check() {
	    if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid])) {
	      return true;
	    }
	    const {
	      integrationId,
	      userIds,
	      allUserIds
	    } = await babelHelpers.classPrivateFieldLooseBase(this, _api$1)[_api$1].checkNotMappedMembersHrIntegration(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid]);
	    babelHelpers.classPrivateFieldLooseBase(this, _participantsIds)[_participantsIds] = allUserIds;
	    babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds] = userIds;
	    babelHelpers.classPrivateFieldLooseBase(this, _integrationId)[_integrationId] = integrationId;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds].length > 0) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _updateUsersPreview)[_updateUsersPreview]();
	    }
	    return !main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds]);
	  }
	  hide() {
	    main_core.Dom.hide(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  }
	  show() {
	    main_core.Dom.show(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]);
	  }
	}
	function _openMapper2() {
	  humanresources_hcmlink_dataMapper.Mapper.openSlider({
	    companyId: babelHelpers.classPrivateFieldLooseBase(this, _integrationId)[_integrationId],
	    userIds: new Set(babelHelpers.classPrivateFieldLooseBase(this, _participantsIds)[_participantsIds]),
	    mode: humanresources_hcmlink_dataMapper.Mapper.MODE_DIRECT
	  }, {
	    onCloseHandler: () => {
	      this.emit('update');
	    }
	  });
	}
	function _loadUsersAvatarMap2(userIds) {
	  return new Promise(resolve => {
	    const dialog = new ui_entitySelector.Dialog({
	      entities: [{
	        id: EntityTypes.User
	      }],
	      events: {
	        'onLoad': event => {
	          const users = dialog.getSelectedItems();
	          const avatarByUserMap = new Map();
	          users.forEach(item => {
	            avatarByUserMap.set(Number(item.id), item.avatar);
	          });
	          resolve(avatarByUserMap);
	        }
	      },
	      preselectedItems: userIds.map(userId => ['user', userId])
	    });
	    dialog.load();
	  });
	}
	async function _updateUsersPreview2() {
	  const usersCount = babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds].length;
	  const userIds = babelHelpers.classPrivateFieldLooseBase(this, _employeeIds)[_employeeIds].slice(0, maxPreviewUserAvatarCount);
	  const usersAvatarMap = await babelHelpers.classPrivateFieldLooseBase(this, _loadUsersAvatarMap)[_loadUsersAvatarMap](userIds);
	  main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _getUsersPreviewContainer)[_getUsersPreviewContainer]());
	  const userAvatarContainer = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="sign-b2e-hcm-link-party-checker-users-avatar-container"></div>
		`));
	  userIds.forEach(userId => {
	    var _usersAvatarMap$get;
	    const avatarLink = (_usersAvatarMap$get = usersAvatarMap.get(userId)) != null ? _usersAvatarMap$get : defaultAvatarLink;
	    const previewElement = main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
				<div class="sign-b2e-hcm-link-party-checker-user-preview --orange">
					<img src="${0}">
				</div>
			`), avatarLink);
	    main_core.Dom.append(previewElement, userAvatarContainer);
	  });
	  main_core.Dom.append(userAvatarContainer, babelHelpers.classPrivateFieldLooseBase(this, _getUsersPreviewContainer)[_getUsersPreviewContainer]());
	  const additionalUserCount = usersCount - maxPreviewUserAvatarCount;
	  if (additionalUserCount > 0) {
	    const counterElement = main_core.Tag.render(_t4$1 || (_t4$1 = _$1`
				<div class="sign-b2e-hcm-link-party-checker-users-preview-counter">
					${0}
				</div>
			`), main_core.Loc.getMessage('SIGN_V2_B2E_HCM_LINK_EMPLOYEE_USERS_COUNT_PLUS', {
	      '#COUNT#': additionalUserCount
	    }));
	    main_core.Dom.append(counterElement, babelHelpers.classPrivateFieldLooseBase(this, _getUsersPreviewContainer)[_getUsersPreviewContainer]());
	  }
	}
	function _getUsersPreviewContainer2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _usersPreviewContainer)[_usersPreviewContainer]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _usersPreviewContainer)[_usersPreviewContainer] = main_core.Tag.render(_t5$1 || (_t5$1 = _$1`
				<div class="sign-b2e-hcm-link-party-checker-users-preview-container"></div>
			`));
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _usersPreviewContainer)[_usersPreviewContainer];
	}

	exports.HcmLinkMapping = HcmLinkMapping;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Event,BX.Humanresources.Hcmlink,BX,BX,BX.UI.EntitySelector,BX.Sign.V2));
//# sourceMappingURL=hcm-link-mapping.bundle.js.map
