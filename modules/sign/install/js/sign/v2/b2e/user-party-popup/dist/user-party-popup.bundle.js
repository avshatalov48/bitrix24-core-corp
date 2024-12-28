/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,main_core,main_popup,sign_v2_api,main_loader) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	const pageSize = 20;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _ui = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ui");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _documentUid = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("documentUid");
	var _membersObserver = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("membersObserver");
	var _departmentsObserver = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentsObserver");
	var _initialized = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initialized");
	var _membersLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("membersLoaded");
	var _departmentsLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentsLoaded");
	var _membersLoadingLocked = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("membersLoadingLocked");
	var _departmentsLoadingLocked = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("departmentsLoadingLocked");
	var _init = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("init");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _createPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createPopup");
	var _renderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContent");
	var _switchTab = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("switchTab");
	var _loadNextMembersPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadNextMembersPage");
	var _loadNextDepartmentsPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadNextDepartmentsPage");
	var _loadMembersPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadMembersPage");
	var _loadDepartmentsPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadDepartmentsPage");
	var _appendDepartmentsToPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendDepartmentsToPopup");
	var _appendMembersToPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("appendMembersToPopup");
	class UserPartyPopup {
	  constructor(options) {
	    Object.defineProperty(this, _appendMembersToPopup, {
	      value: _appendMembersToPopup2
	    });
	    Object.defineProperty(this, _appendDepartmentsToPopup, {
	      value: _appendDepartmentsToPopup2
	    });
	    Object.defineProperty(this, _loadDepartmentsPage, {
	      value: _loadDepartmentsPage2
	    });
	    Object.defineProperty(this, _loadMembersPage, {
	      value: _loadMembersPage2
	    });
	    Object.defineProperty(this, _loadNextDepartmentsPage, {
	      value: _loadNextDepartmentsPage2
	    });
	    Object.defineProperty(this, _loadNextMembersPage, {
	      value: _loadNextMembersPage2
	    });
	    Object.defineProperty(this, _switchTab, {
	      value: _switchTab2
	    });
	    Object.defineProperty(this, _renderContent, {
	      value: _renderContent2
	    });
	    Object.defineProperty(this, _createPopup, {
	      value: _createPopup2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _init, {
	      value: _init2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _ui, {
	      writable: true,
	      value: {
	        membersContent: HTMLDivElement = null,
	        departmentsContent: HTMLDivElement = null,
	        tabs: HTMLElement = null
	      }
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _documentUid, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _membersObserver, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _departmentsObserver, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _initialized, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _membersLoaded, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _departmentsLoaded, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _membersLoadingLocked, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _departmentsLoadingLocked, {
	      writable: true,
	      value: false
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	    const observerOptions = {
	      // rootMargin: (Browser.isMobile() ? '0%' : '-10% 0% -10% 0%'),
	      threshold: 0.1
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _membersObserver)[_membersObserver] = new IntersectionObserver(entries => {
	      entries.forEach(entry => {
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _membersLoaded)[_membersLoaded] && entry.isIntersecting) {
	          babelHelpers.classPrivateFieldLooseBase(this, _membersObserver)[_membersObserver].unobserve(entry.target);
	          babelHelpers.classPrivateFieldLooseBase(this, _loadNextMembersPage)[_loadNextMembersPage]().then(members => {
	            if (members.length > 0) {
	              babelHelpers.classPrivateFieldLooseBase(this, _appendMembersToPopup)[_appendMembersToPopup](members);
	              if (members.length >= pageSize) {
	                babelHelpers.classPrivateFieldLooseBase(this, _membersObserver)[_membersObserver].observe(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent.lastChild);
	              }
	            }
	          });
	        }
	      });
	    }, observerOptions);
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentsObserver)[_departmentsObserver] = new IntersectionObserver(entries => {
	      entries.forEach(entry => {
	        if (!babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoaded)[_departmentsLoaded] && entry.isIntersecting) {
	          babelHelpers.classPrivateFieldLooseBase(this, _departmentsObserver)[_departmentsObserver].unobserve(entry.target);
	          babelHelpers.classPrivateFieldLooseBase(this, _loadNextDepartmentsPage)[_loadNextDepartmentsPage]().then(departments => {
	            if (departments.length > 0) {
	              babelHelpers.classPrivateFieldLooseBase(this, _appendDepartmentsToPopup)[_appendDepartmentsToPopup](departments);
	              if (departments.length >= pageSize) {
	                babelHelpers.classPrivateFieldLooseBase(this, _departmentsObserver)[_departmentsObserver].observe(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent.lastChild);
	              }
	            }
	          });
	        }
	      });
	    }, observerOptions);
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _initialized)[_initialized] === false) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	      main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent);
	      main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent);
	      babelHelpers.classPrivateFieldLooseBase(this, _init)[_init]().then(async () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].hide();
	        babelHelpers.classPrivateFieldLooseBase(this, _initialized)[_initialized] = true;
	      });
	    }
	  }
	  setDocumentUid(documentUid) {
	    babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid] = documentUid;
	    return this;
	  }
	  resetData() {
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent);
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent);
	    babelHelpers.classPrivateFieldLooseBase(this, _initialized)[_initialized] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _membersLoaded)[_membersLoaded] = false;
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoaded)[_departmentsLoaded] = false;
	    return this;
	  }
	}
	async function _init2() {
	  const [members, departments] = await Promise.all([babelHelpers.classPrivateFieldLooseBase(this, _loadMembersPage)[_loadMembersPage](1), babelHelpers.classPrivateFieldLooseBase(this, _loadDepartmentsPage)[_loadDepartmentsPage](1)]);
	  if (members.length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendMembersToPopup)[_appendMembersToPopup](members);
	    if (members.length >= pageSize) {
	      babelHelpers.classPrivateFieldLooseBase(this, _membersObserver)[_membersObserver].observe(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent.lastChild);
	    }
	  }
	  if (departments.length > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _appendDepartmentsToPopup)[_appendDepartmentsToPopup](departments);
	    if (departments.length >= pageSize) {
	      babelHelpers.classPrivateFieldLooseBase(this, _departmentsObserver)[_departmentsObserver].observe(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent.lastChild);
	    }
	  }
	}
	function _getPopup2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _createPopup)[_createPopup]();
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _createPopup2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent](),
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].bindElement,
	    // height: 250,
	    width: 330,
	    autoHide: true,
	    closeByEsc: true
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _renderContent2() {
	  const membersOnclick = e => {
	    babelHelpers.classPrivateFieldLooseBase(this, _switchTab)[_switchTab]('members');
	    e.preventDefault();
	  };
	  const departmentsOnclick = e => {
	    babelHelpers.classPrivateFieldLooseBase(this, _switchTab)[_switchTab]('departments');
	    e.preventDefault();
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs = main_core.Tag.render(_t || (_t = _`
			<span class="bx-user-party-popup-popup-head">
				<span onclick="${0}" class="bx-user-party-popup-popup-head-item --member bx-user-party-popup-popup-head-item-current">
					<span class="bx-user-party-popup-popup-head-icon"></span>
					<span class="bx-user-party-popup-popup-head-text">${0}</span>
				</span>
				<span onclick="${0}" class="bx-user-party-popup-popup-head-item --department">
					<span class="bx-user-party-popup-popup-head-icon"></span>
					<span class="bx-user-party-popup-popup-head-text">${0}</span>
				</span>
			</span>
		`), membersOnclick, main_core.Loc.getMessage('SIGN_USER_PARTY_POPUP_TAB_MEMBERS'), departmentsOnclick, main_core.Loc.getMessage('SIGN_USER_PARTY_POPUP_TAB_DEPARTMENTS'));
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent = main_core.Tag.render(_t2 || (_t2 = _`<div class="bx-user-party-popup-popup-content-container"></div>`));
	  babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent = main_core.Tag.render(_t3 || (_t3 = _`
			<div class="bx-user-party-popup-popup-content-container bx-user-party-popup-popup-content-invisible"></div>
		`));
	  const wrapper = main_core.Tag.render(_t4 || (_t4 = _`
			<div class="bx-sign-user-party-popup">
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent, babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent);
	  babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	    size: 80,
	    target: wrapper,
	    offset: {
	      top: '10px'
	    }
	  });
	  return wrapper;
	}
	function _switchTab2(tab) {
	  switch (tab) {
	    case 'departments':
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent, 'bx-user-party-popup-popup-content-invisible');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent, 'bx-user-party-popup-popup-content-invisible');
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs.querySelector('.bx-user-party-popup-popup-head-item.--member'), 'bx-user-party-popup-popup-head-item-current');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs.querySelector('.bx-user-party-popup-popup-head-item.--department'), 'bx-user-party-popup-popup-head-item-current');
	      break;
	    case 'members':
	    default:
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent, 'bx-user-party-popup-popup-content-invisible');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent, 'bx-user-party-popup-popup-content-invisible');
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs.querySelector('.bx-user-party-popup-popup-head-item.--department'), 'bx-user-party-popup-popup-head-item-current');
	      main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].tabs.querySelector('.bx-user-party-popup-popup-head-item.--member'), 'bx-user-party-popup-popup-head-item-current');
	      break;
	  }
	}
	async function _loadNextMembersPage2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _membersLoadingLocked)[_membersLoadingLocked] === true) {
	    return [];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _membersLoadingLocked)[_membersLoadingLocked] = true;
	  const page = Math.ceil(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent.children.length / pageSize) + 1;
	  const newMembers = await babelHelpers.classPrivateFieldLooseBase(this, _loadMembersPage)[_loadMembersPage](page);
	  if (newMembers.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _membersLoaded)[_membersLoaded] = true;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _membersLoadingLocked)[_membersLoadingLocked] = false;
	  return newMembers;
	}
	async function _loadNextDepartmentsPage2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoadingLocked)[_departmentsLoadingLocked] === true) {
	    return [];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoadingLocked)[_departmentsLoadingLocked] = true;
	  const page = Math.ceil(babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent.children.length / pageSize) + 1;
	  const newDepartments = await babelHelpers.classPrivateFieldLooseBase(this, _loadDepartmentsPage)[_loadDepartmentsPage](page);
	  if (newDepartments.length === 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoaded)[_departmentsLoaded] = true;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _departmentsLoadingLocked)[_departmentsLoadingLocked] = false;
	  return newDepartments;
	}
	async function _loadMembersPage2(page) {
	  const response = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getMembersForDocument(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid], page, pageSize);
	  return (response == null ? void 0 : response.members) || [];
	}
	async function _loadDepartmentsPage2(page) {
	  const response = await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].getDepartmentsForDocument(babelHelpers.classPrivateFieldLooseBase(this, _documentUid)[_documentUid], page, pageSize);
	  return (response == null ? void 0 : response.departments) || [];
	}
	function _appendDepartmentsToPopup2(departments) {
	  departments.forEach(department => {
	    const deptName = main_core.Text.encode(department.name);
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].departmentsContent.append(main_core.Tag.render(_t5 || (_t5 = _`
				<div data-department-id="${0}" class="bx-user-party-popup-popup-user-item --department">
					<span class="bx-user-party-popup-popup-user-icon --default"></span>
					<span class="bx-user-party-popup-popup-user-name" title="${0}">${0}</span>
				</div>
			`), main_core.Text.encode(department.id), deptName, deptName));
	  });
	}
	function _appendMembersToPopup2(members) {
	  members.forEach(member => {
	    const memberName = main_core.Text.encode(member.name);
	    const avatar = main_core.Tag.render(_t6 || (_t6 = _`<span class="bx-user-party-popup-popup-user-icon"></span>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _ui)[_ui].membersContent.append(main_core.Tag.render(_t7 || (_t7 = _`
				<a href="${0}" data-member-id="${0}" class="bx-user-party-popup-popup-user-item --user">
					${0}
					<span class="bx-user-party-popup-popup-user-name" title="${0}">${0}</span>
				</a>
			`), main_core.Text.encode(member.profileUrl), main_core.Text.encode(member.memberId), avatar, memberName, memberName));
	    if (member.avatar) {
	      const avatarUrl = main_core.Text.encode(`data:image;base64,${member.avatar}`);
	      main_core.Dom.style(avatar, 'backgroundImage', `url('${avatarUrl}')`);
	    } else {
	      main_core.Dom.addClass(avatar, '--default');
	    }
	  });
	}

	exports.UserPartyPopup = UserPartyPopup;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX,BX.Main,BX.Sign.V2,BX));
//# sourceMappingURL=user-party-popup.bundle.js.map
