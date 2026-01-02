/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,main_sidepanel,ui_buttons,intranet_avatarWidget,timeman_workTimeStateIcon,bitrix24_licenseWidget,intranet_licenseWidget,ui_infoHelper,pull_client,main_core_cache,main_core_events,intranet_widgetLoader,intranet_invitationWidget,ui_cnt,main_core) {
	'use strict';

	async function showPartnerConnectForm(params) {
	  main_core.Loc.setMessage(params.messages);
	  await showPartnerFormPopup({
	    ...params,
	    titleBar: main_core.Loc.getMessage('BX24_PARTNER_TITLE'),
	    sendButtonText: main_core.Loc.getMessage('BX24_BUTTON_SEND')
	  });
	}
	async function showPartnerFormPopup(options) {
	  const titleBar = options.titleBar;
	  const sendButtonText = options.sendButtonText;
	  const partnerName = options.partnerName;
	  const partnerUrl = options.partnerUrl;
	  const [{
	    Popup
	  }, {
	    Button,
	    ButtonColor
	  }] = await Promise.all([main_core.Runtime.loadExtension('main.popup'), main_core.Runtime.loadExtension('ui.buttons')]);
	  const popupOptions = {
	    className: 'bitrix24-partner__popup',
	    autoHide: false,
	    cacheable: false,
	    zIndex: 0,
	    offsetLeft: 0,
	    offsetTop: 0,
	    width: 540,
	    height: 350,
	    overlay: true,
	    draggable: {
	      restrict: true
	    },
	    closeByEsc: true,
	    titleBar,
	    closeIcon: true,
	    content: `
			<div class="bitrix24-partner__popup-content">
				<div class="bitrix24-partner__popup-content_title">${main_core.Loc.getMessage('PARTNER_TITLE_FOR_NAME')}</div>
				<div class="bitrix24-partner__popup-content_main">
					<div class="bitrix24-partner__popup-content_name">${partnerName}</div>
					<a class="bitrix24-partner__popup-content_link" href="${encodeURI(partnerUrl)}" target="_blank">${main_core.Loc.getMessage('PARTNER_LINK_NAME_MORE')}</a>
				</div>
				<div class="bitrix24-partner__popup-content_desc">${main_core.Loc.getMessage('PARTNER_POPUP_DESCRIPTION_BOTTOM')}</div>
			</div>
		`,
	    buttons: [new Button({
	      color: ButtonColor.SUCCESS,
	      text: sendButtonText,
	      onclick: async button => {
	        setTimeout(() => {
	          button.setClocking(true);
	        }, 200);
	        await showIntegratorApplicationForm();
	        button.setClocking(false);
	      }
	    })]
	  };
	  const popup = new Popup(popupOptions);
	  popup.show();
	}
	async function showIntegratorApplicationForm() {
	  const {
	    Form
	  } = await main_core.Runtime.loadExtension('ui.feedback.form');
	  Form.open({
	    id: `intranet-license-partner-form-${parseInt(Math.random() * 1000, 10)}`,
	    portalUri: 'https://bitrix24.team',
	    forms: [{
	      zones: ['de'],
	      id: 883,
	      lang: 'de',
	      sec: 'a12ty8'
	    }, {
	      zones: ['com', 'in', 'eu', 'uk'],
	      id: 735,
	      lang: 'en',
	      sec: 'vlhmlv'
	    }, {
	      zones: ['es', 'co', 'mx'],
	      id: 900,
	      lang: 'es',
	      sec: 'w3qmwq'
	    }, {
	      zones: ['com.br'],
	      id: 901,
	      lang: 'pt',
	      sec: 'prnm4x'
	    }, {
	      zones: ['cn/tc'],
	      id: 902,
	      lang: 'cn-tc',
	      sec: 'z8isyg'
	    }, {
	      zones: ['cn'],
	      id: 903,
	      lang: 'cn-sc',
	      sec: 'hsxnam'
	    }, {
	      zones: ['pl'],
	      id: 904,
	      lang: 'pl',
	      sec: '2ph9ph'
	    }, {
	      zones: ['it'],
	      id: 905,
	      lang: 'it',
	      sec: '2r3owa'
	    }, {
	      zones: ['fr'],
	      id: 906,
	      lang: 'fr',
	      sec: 'jt2fii'
	    }, {
	      zones: ['com.tr'],
	      id: 907,
	      lang: 'tr',
	      sec: 'ovevp8'
	    }, {
	      zones: ['id'],
	      id: 908,
	      lang: 'id',
	      sec: '7kq7v2'
	    }, {
	      zones: ['com/my'],
	      id: 909,
	      lang: 'ms',
	      sec: 'kmncmj'
	    }, {
	      zones: ['com/th'],
	      id: 910,
	      lang: 'th',
	      sec: 'sknbp9'
	    }, {
	      zones: ['vn'],
	      id: 911,
	      lang: 'vn',
	      sec: 'a573fy'
	    }, {
	      zones: ['jp'],
	      id: 912,
	      lang: 'jp',
	      sec: '3purdq'
	    }, {
	      zones: ['ru'],
	      id: 2122,
	      lang: 'ru',
	      sec: '8vralr'
	    }, {
	      zones: ['kz'],
	      id: 2128,
	      lang: 'ru',
	      sec: '054phh'
	    }, {
	      zones: ['by'],
	      id: 2129,
	      lang: 'ru',
	      sec: 'srs85j'
	    }],
	    defaultForm: {
	      id: 735,
	      lang: 'en',
	      sec: 'vlhmlv'
	    }
	  });
	}

	async function showPartnerOrderForm(params) {
	  if (main_core.Type.isObject(params) === false) {
	    return;
	  }
	  const {
	    Popup
	  } = await main_core.Runtime.loadExtension('main.popup');
	  const popupOptions = {
	    id: 'B24PartnerOrderForm',
	    autoHide: true,
	    noAllPaddings: true,
	    zIndex: 0,
	    offsetLeft: 0,
	    offsetTop: 0,
	    overlay: true,
	    borderRadius: '10px',
	    contentBorderRadius: '10px',
	    disableScroll: true,
	    height: Math.min(window.innerHeight - 60, 758),
	    width: 560,
	    draggable: {
	      restrict: true
	    },
	    closeByEsc: true,
	    contentColor: 'white',
	    contentNoPaddings: true,
	    cacheable: false,
	    content: `
			<script data-b24-form="inline/${params.id}/${params.sec}" data-skip-moving="true">
				(function(w, d,u) {
					var s = d.createElement("script");
					s.async=true;
					s.src=u + "?" + (Date.now()/180000|0);
					var h = d.getElementsByTagName("script")[0];
					h.parentNode.insertBefore(s,h);
				})(window,document,"https://bitrix24.team/upload/crm/form/loader_${params.id}_${params.sec}.js")
			</script>
		`,
	    events: {
	      onPopupFirstShow() {
	        (function (w, d, u) {
	          var s = d.createElement('script');
	          s.async = true;
	          s.src = u + '?' + (Date.now() / 180000 | 0);
	          var h = d.getElementsByTagName('script')[0];
	          h.parentNode.insertBefore(s, h);
	        })(window, document, `https://bitrix24.team/upload/crm/form/loader_${params.id}_${params.sec}.js`);
	      }
	    }
	  };
	  const popup = new Popup(popupOptions);
	  popup.show();
	}

	const showHelper = async () => {
	  await main_core.Runtime.loadExtension('helper');
	  const Helper = main_core.Reflection.getClass('BX.Helper');
	  Helper.show('redirect=detail&code=20267044');
	};

	class PartnerForm {
	  static async showConnectForm(params) {
	    return showPartnerConnectForm(params);
	  }
	  static showIntegrationOrderForm(params) {
	    return showPartnerOrderForm(params);
	  }
	  static async showHelper() {
	    return showHelper();
	  }
	}

	var _handleChatMenuSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleChatMenuSelect");
	var _handleImLayoutChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleImLayoutChange");
	var _handleCounterUpdate = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCounterUpdate");
	class ChatMenu {
	  constructor() {
	    Object.defineProperty(this, _handleCounterUpdate, {
	      value: _handleCounterUpdate2
	    });
	    Object.defineProperty(this, _handleImLayoutChange, {
	      value: _handleImLayoutChange2
	    });
	    Object.defineProperty(this, _handleChatMenuSelect, {
	      value: _handleChatMenuSelect2
	    });
	    main_core_events.EventEmitter.subscribe('IM.Layout:onLayoutChange', babelHelpers.classPrivateFieldLooseBase(this, _handleImLayoutChange)[_handleImLayoutChange].bind(this));
	    main_core_events.EventEmitter.subscribe('IM.Counters:onUpdate', babelHelpers.classPrivateFieldLooseBase(this, _handleCounterUpdate)[_handleCounterUpdate].bind(this));
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24.ChatMenu:onSelect', babelHelpers.classPrivateFieldLooseBase(this, _handleChatMenuSelect)[_handleChatMenuSelect].bind(this));
	  }
	  getMenu() {
	    /**
	     *
	     * @type {BX.Main.interfaceButtonsManager}
	     */
	    const menuManager = main_core.Reflection.getClass('BX.Main.interfaceButtonsManager');
	    if (menuManager) {
	      return menuManager.getById('chat-menu');
	    }
	    return null;
	  }
	}
	function _handleChatMenuSelect2(event) {
	  var _data$event;
	  const data = event.getData();
	  const {
	    id,
	    entityId
	  } = data;
	  let target = (_data$event = data.event) == null ? void 0 : _data$event.target;
	  if (target) {
	    target = target.closest('.main-buttons-item-link, .menu-popup-item');
	  }
	  const Public = main_core.Reflection.getClass('BX.Messenger.Public');
	  Public == null ? void 0 : Public.openNavigationItem({
	    id,
	    entityId,
	    target
	  });
	}
	function _handleImLayoutChange2(event) {
	  const data = event.getData();
	  const menu = this.getMenu();
	  let fromItemId = data.from.name.toLowerCase();
	  if (fromItemId === 'market' && data.from.entityId) {
	    fromItemId = `${fromItemId}_${data.from.entityId}`;
	  }
	  let toItemId = data.to.name.toLowerCase();
	  if (toItemId === 'market' && data.to.entityId) {
	    toItemId = `${toItemId}_${data.to.entityId}`;
	  }
	  menu == null ? void 0 : menu.unsetActive(fromItemId);
	  menu == null ? void 0 : menu.setActive(toItemId);
	  menu == null ? void 0 : menu.closeMoreMenu();
	}
	function _handleCounterUpdate2(event) {
	  const counters = event.getData();
	  const menu = this.getMenu();
	  for (const [counterId, counterValue] of Object.entries(counters)) {
	    menu == null ? void 0 : menu.updateCounter(counterId, counterValue);
	  }
	}

	function getBackUrl() {
	  const backUrl = window.location.pathname;
	  const query = getQueryString(['logout', 'login', 'back_url_pub', 'user_lang']);
	  return backUrl + (query.length > 0 ? `?${query}` : '');
	}
	function getQueryString(ignoredParams) {
	  const query = window.location.search.slice(1);
	  if (!main_core.Type.isStringFilled(query)) {
	    return '';
	  }
	  const vars = query.split('&');
	  const checkedIgnoredParams = main_core.Type.isArray(ignoredParams) ? ignoredParams : [];
	  let result = '';
	  for (const variable of vars) {
	    const pair = variable.split('=');
	    const equal = variable.indexOf('=');
	    const key = pair[0];
	    const value = main_core.Type.isStringFilled(pair[1]) ? pair[1] : false;
	    if (!checkedIgnoredParams.includes(key)) {
	      if (result !== '') {
	        result += '&';
	      }
	      result += key + (equal === -1 ? '' : '=') + (value === false ? '' : value);
	    }
	  }
	  return result;
	}

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4;
	var _refs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refs");
	var _showLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLoader");
	var _getPageSkeleton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPageSkeleton");
	var _bindEvents = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	class Composite {
	  constructor() {
	    Object.defineProperty(this, _bindEvents, {
	      value: _bindEvents2
	    });
	    Object.defineProperty(this, _getPageSkeleton, {
	      value: _getPageSkeleton2
	    });
	    Object.defineProperty(this, _showLoader, {
	      value: _showLoader2
	    });
	    Object.defineProperty(this, _refs, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    if (this.isEnabled()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _bindEvents)[_bindEvents]();
	    }
	  }
	  isEnabled() {
	    return !main_core.Type.isUndefined(window.frameRequestStart);
	  }
	  isReady() {
	    var _window$BX, _window$BX$frameCache;
	    return ((_window$BX = window.BX) == null ? void 0 : (_window$BX$frameCache = _window$BX.frameCache) == null ? void 0 : _window$BX$frameCache.frameDataInserted) === true || !main_core.Type.isUndefined(window.frameRequestFail);
	  }
	  showLoader() {
	    const page = window.location.pathname;
	    if (page === '/stream/' || page === '/stream/index.php' || page === '/index.php') {
	      babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]('stream');
	    } else {
	      setTimeout(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showLoader)[_showLoader]();
	      }, 500);
	    }
	  }
	  getStubContainer() {
	    return document.querySelector('#page-area');
	  }
	  getLoaderContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('loader', () => {
	      return main_core.Tag.render(_t || (_t = _`
				<div class="composite-skeleton-container">
					<div class="composite-loader-container">
						<svg class="composite-loader-circular" viewBox="25 25 50 50">
							<circle class="composite-loader-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10" />
						</svg>
					</div>
				</div>
			`));
	    });
	  }
	  getLiveFeedSkeleton() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('feed-skeleton', () => {
	      return main_core.Tag.render(_t2 || (_t2 = _`
				<div class="page top-menu-mode start-page no-background no-all-paddings no-page-header">
					<div class="page__workarea">
						<div class="page__sidebar">${0}</div>
						<main class="page__workarea-content">${0}</main>
					</div>
				</div>
			`), this.getLiveFeedSidebar(), this.getLiveFeedWorkArea());
	    });
	  }
	  getLiveFeedSidebar() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('feed-sidebar', () => {
	      return main_core.Tag.render(_t3 || (_t3 = _`
				<div class="skeleton__white-bg-element skeleton__sidebar skeleton__intranet-ustat">
					<div class="skeleton__graph-circle"></div>
					<div class="skeleton__graph-right">
						<div class="skeleton__graph-right_top">
							<div class="skeleton__graph-right_top-circle --first"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
							<div class="skeleton__graph-right_top-circle"></div>
						</div>
						<div class="skeleton__graph-right_bottom">
							<div class="skeleton__graph-right_bottom-line"></div>
							<div class="skeleton__graph-right_bottom-line"></div>
						</div>
					</div>
				</div>
				
				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
						<div class="skeleton__sidebar-header_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
					<div class="skeleton__tasks-row">
						<div class="skeleton__tasks-row_line"></div>
						<div class="skeleton__tasks-row_short-line"></div>
						<div class="skeleton__tasks-row_circle"></div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__sidebar">
					<div class="skeleton__sidebar-header">
						<div class="skeleton__sidebar-header_line"></div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
					<div class="skeleton__birthdays-row">
						<div class="skeleton__birthdays-circle"></div>
						<div class="skeleton__birthdays-info">
							<div class="skeleton__birthdays-name"></div>
							<div class="skeleton__birthdays-date"></div>
						</div>
					</div>
				</div>
			`));
	    });
	  }
	  getLiveFeedWorkArea() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('feed-work-area', () => {
	      return main_core.Tag.render(_t4 || (_t4 = _`
				<div class="skeleton__white-bg-element skeleton__feed-wrap">
					<div class="skeleton__feed-wrap_header">
						<div class="skeleton__feed-wrap_header-link --long"></div>
						<div class="skeleton__feed-wrap_header-link --one"></div>
						<div class="skeleton__feed-wrap_header-link --two"></div>
						<div class="skeleton__feed-wrap_header-link --one"></div>
						<div class="skeleton__feed-wrap_header-link --two"></div>
					</div>
					<div class="skeleton__feed-wrap_header-content">
						<div class="skeleton__feed-wrap_header-text"></div>
					</div>
				</div>
				<div class="skeleton__title-block">
					<div class="skeleton__title-block_text"></div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
				<div class="skeleton__white-bg-element skeleton__feed-item">
					<div class="skeleton__feed-item_user-icon"></div>
					<div class="skeleton__feed-item_content">
						<div class="skeleton__feed-item_main">
							<div class="skeleton__feed-item_text --name"></div>
							<div class="skeleton__feed-item_date"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text"></div>
							<div class="skeleton__feed-item_text --short"></div>
						</div>
						<div class="skeleton__feed-item_nav">
							<div class="skeleton__feed-item_nav-line --one"></div>
							<div class="skeleton__feed-item_nav-line --two"></div>
							<div class="skeleton__feed-item_nav-line --three"></div>
							<div class="skeleton__feed-item_nav-line --four"></div>
						</div>
						<div class="skeleton__feed-item_like">
							<div class="skeleton__feed-item_like-icon"></div>
							<div class="skeleton__feed-item_like-name"></div>
						</div>
						<div class="skeleton__feed-item_comment">
							<div class="skeleton__feed-item_comment-icon"></div>
							<div class="skeleton__feed-item_comment-block">
								<div class="skeleton__feed-item_comment-text"></div>
							</div>
						</div>
					</div>
				</div>
			`));
	    });
	  }
	}
	function _showLoader2(page = null) {
	  if (this.isReady()) {
	    return;
	  }
	  const skeleton = babelHelpers.classPrivateFieldLooseBase(this, _getPageSkeleton)[_getPageSkeleton](page);
	  const container = this.getStubContainer();
	  const stub = skeleton != null ? skeleton : this.getLoaderContainer();
	  if (!container || stub.parentNode) {
	    return;
	  }
	  main_core.Dom.append(stub, container);
	}
	function _getPageSkeleton2(page) {
	  const map = {
	    stream: () => this.getLiveFeedSkeleton()
	  };
	  if (map[page]) {
	    return map[page]();
	  }
	  return null;
	}
	function _bindEvents2() {
	  main_core_events.EventEmitter.subscribe('onFrameDataRequestFail', () => {
	    console.error('Composite ajax request failed');
	    top.location = `/auth/?backurl=${encodeURIComponent(getBackUrl())}`;
	  });
	  main_core_events.EventEmitter.subscribe('onAjaxFailure', event => {
	    const [reason, status] = event.getCompatData();
	    const redirectUrl = `/auth/?backurl=${getBackUrl()}`;
	    if (this.isEnabled() && (reason === 'auth' || reason === 'status' && status === 401)) {
	      console.error('Auth ajax request failed', reason, status);
	      top.location = redirectUrl;
	    }
	  });
	  if (pull_client.PULL) {
	    pull_client.PULL.subscribe({
	      moduleId: 'main',
	      command: 'composite-cache-up',
	      callback: () => {
	        setTimeout(() => {
	          const value = BX.localStorage.get('ajax-composite-cache-up-lock');
	          if (!value) {
	            BX.localStorage.set('ajax-composite-cache-up-lock', 'EXECUTE', 2);
	            main_core.ajax({
	              url: '/blank.php',
	              method: 'GET',
	              processData: false,
	              skipBxHeader: true,
	              emulateOnload: false
	            });
	          }
	        }, Math.floor(Math.random() * 500));
	      }
	    });
	  }
	}

	var _isTransparentMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isTransparentMode");
	var _isScrollMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isScrollMode");
	var _scrollModeThreshold = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scrollModeThreshold");
	var _goTopButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("goTopButton");
	class RightBar {
	  constructor(options) {
	    Object.defineProperty(this, _isTransparentMode, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _isScrollMode, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _scrollModeThreshold, {
	      writable: true,
	      value: window.innerHeight
	    });
	    Object.defineProperty(this, _goTopButton, {
	      writable: true,
	      value: void 0
	    });
	    const redraw = this.redraw.bind(this);
	    main_core.Event.bind(window, 'scroll', redraw, {
	      passive: true
	    });
	    main_core.Event.bind(window, 'resize', redraw);
	    babelHelpers.classPrivateFieldLooseBase(this, _scrollModeThreshold)[_scrollModeThreshold] = window.innerHeight;
	    babelHelpers.classPrivateFieldLooseBase(this, _goTopButton)[_goTopButton] = options.goTopButton;
	    babelHelpers.classPrivateFieldLooseBase(this, _goTopButton)[_goTopButton].subscribe('show', () => {
	      main_core.Dom.addClass(this.getContainer(), '--show-scroll-btn');
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _goTopButton)[_goTopButton].subscribe('hide', () => {
	      main_core.Dom.removeClass(this.getContainer(), '--show-scroll-btn');
	    });
	    main_core.Event.ready(() => {
	      this.redraw();
	    });
	  }
	  redraw() {
	    const rightBar = this.getContainer();
	    babelHelpers.classPrivateFieldLooseBase(this, _scrollModeThreshold)[_scrollModeThreshold] = window.innerHeight;
	    if (window.pageYOffset > babelHelpers.classPrivateFieldLooseBase(this, _scrollModeThreshold)[_scrollModeThreshold]) {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _isScrollMode)[_isScrollMode]) {
	        main_core.Dom.addClass(rightBar, '--scroll-mode');
	        babelHelpers.classPrivateFieldLooseBase(this, _isScrollMode)[_isScrollMode] = true;
	      }
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _isScrollMode)[_isScrollMode]) {
	      main_core.Dom.removeClass(rightBar, '--scroll-mode');
	      babelHelpers.classPrivateFieldLooseBase(this, _isScrollMode)[_isScrollMode] = false;
	    }
	    if (!rightBar || rightBar.dataset.lockRedraw === 'true') {
	      return;
	    }
	    const scrollWidth = document.documentElement.scrollWidth - document.documentElement.clientWidth;
	    if (scrollWidth > 0) {
	      if (!babelHelpers.classPrivateFieldLooseBase(this, _isTransparentMode)[_isTransparentMode]) {
	        main_core.Dom.addClass(rightBar, '--transparent');
	        babelHelpers.classPrivateFieldLooseBase(this, _isTransparentMode)[_isTransparentMode] = true;
	      }
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _isTransparentMode)[_isTransparentMode]) {
	      main_core.Dom.removeClass(rightBar, '--transparent');
	      babelHelpers.classPrivateFieldLooseBase(this, _isTransparentMode)[_isTransparentMode] = false;
	    }
	  }
	  getContainer() {
	    return document.getElementById('right-bar');
	  }
	}

	class Header {
	  getContainer() {
	    return document.getElementById('header');
	  }
	}

	class Footer {
	  constructor() {
	    main_core_events.EventEmitter.subscribe('Kanban.Grid:onFixedModeStart', () => {
	      this.hide();
	    });
	  }
	  show() {
	    main_core.Dom.removeClass(this.getContainer(), 'hidden');
	  }
	  hide() {
	    main_core.Dom.addClass(this.getContainer(), 'hidden');
	  }
	  getContainer() {
	    return document.getElementById('air-footer');
	  }
	}

	var _lastScrollOffset = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("lastScrollOffset");
	var _isReversed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isReversed");
	var _button = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _show = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("show");
	var _hide = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hide");
	var _getButtonWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButtonWrapper");
	var _bindEvents$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("bindEvents");
	var _handleScroll = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleScroll");
	var _handleButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleButtonClick");
	var _setReversed = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setReversed");
	var _getButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getButton");
	class GoTopButton extends main_core_events.EventEmitter {
	  constructor() {
	    super();
	    Object.defineProperty(this, _getButton, {
	      value: _getButton2
	    });
	    Object.defineProperty(this, _setReversed, {
	      value: _setReversed2
	    });
	    Object.defineProperty(this, _handleButtonClick, {
	      value: _handleButtonClick2
	    });
	    Object.defineProperty(this, _handleScroll, {
	      value: _handleScroll2
	    });
	    Object.defineProperty(this, _bindEvents$1, {
	      value: _bindEvents2$1
	    });
	    Object.defineProperty(this, _getButtonWrapper, {
	      value: _getButtonWrapper2
	    });
	    Object.defineProperty(this, _hide, {
	      value: _hide2
	    });
	    Object.defineProperty(this, _show, {
	      value: _show2
	    });
	    Object.defineProperty(this, _lastScrollOffset, {
	      writable: true,
	      value: 0
	    });
	    Object.defineProperty(this, _isReversed, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _button, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('GoTopButton');
	    babelHelpers.classPrivateFieldLooseBase(this, _bindEvents$1)[_bindEvents$1]();
	  }
	  isShown() {
	    return main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWrapper)[_getButtonWrapper](), '--show');
	  }
	}
	function _show2() {
	  this.emit('show');
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWrapper)[_getButtonWrapper](), '--show');
	}
	function _hide2() {
	  this.emit('hide');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWrapper)[_getButtonWrapper](), '--show');
	}
	function _getButtonWrapper2() {
	  return document.getElementById('goTopButtonWrapper');
	}
	function _bindEvents2$1() {
	  main_core.Event.ready(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _handleScroll)[_handleScroll]();
	    main_core.Event.bind(window, 'scroll', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _handleScroll)[_handleScroll]();
	    });
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getButtonWrapper)[_getButtonWrapper](), 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _handleButtonClick)[_handleButtonClick]();
	    });
	  });
	}
	function _handleScroll2() {
	  if (window.pageYOffset > document.documentElement.clientHeight) {
	    babelHelpers.classPrivateFieldLooseBase(this, _show)[_show]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isReversed)[_isReversed]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setReversed)[_setReversed](false);
	      babelHelpers.classPrivateFieldLooseBase(this, _lastScrollOffset)[_lastScrollOffset] = 0;
	    }
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _isReversed)[_isReversed] === false) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hide)[_hide]();
	  }
	}
	function _handleButtonClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isReversed)[_isReversed]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _setReversed)[_setReversed](false);
	    window.scrollTo({
	      top: babelHelpers.classPrivateFieldLooseBase(this, _lastScrollOffset)[_lastScrollOffset],
	      behavior: 'instant'
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _lastScrollOffset)[_lastScrollOffset] = 0;
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _setReversed)[_setReversed](true);
	    babelHelpers.classPrivateFieldLooseBase(this, _lastScrollOffset)[_lastScrollOffset] = window.pageYOffset;
	    window.scrollTo({
	      top: 0,
	      behavior: 'instant'
	    });
	  }
	}
	function _setReversed2(flag = true) {
	  babelHelpers.classPrivateFieldLooseBase(this, _isReversed)[_isReversed] = flag;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isReversed)[_isReversed]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getButton)[_getButton]().setIcon(ui_buttons.ButtonIcon.ANGLE_DOWN);
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _getButton)[_getButton]().setIcon(ui_buttons.ButtonIcon.ANGLE_UP);
	  }
	}
	function _getButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _button)[_button] || ui_buttons.ButtonManager.createFromNode(document.getElementById('goTopButton'));
	}

	var _handleCounterUpdate$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCounterUpdate");
	var _handleLiveFeedCounterDecrement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleLiveFeedCounterDecrement");
	class CollaborationMenu {
	  constructor() {
	    Object.defineProperty(this, _handleLiveFeedCounterDecrement, {
	      value: _handleLiveFeedCounterDecrement2
	    });
	    Object.defineProperty(this, _handleCounterUpdate$1, {
	      value: _handleCounterUpdate2$1
	    });
	    main_core_events.EventEmitter.subscribe('onImUpdateCounterMessage', babelHelpers.classPrivateFieldLooseBase(this, _handleCounterUpdate$1)[_handleCounterUpdate$1].bind(this));
	    main_core_events.EventEmitter.subscribe('onCounterDecrement', babelHelpers.classPrivateFieldLooseBase(this, _handleLiveFeedCounterDecrement)[_handleLiveFeedCounterDecrement].bind(this));
	  }
	  getMenu() {
	    /**
	     *
	     * @type {BX.Main.interfaceButtonsManager}
	     */
	    const menuManager = main_core.Reflection.getClass('BX.Main.interfaceButtonsManager');
	    if (menuManager) {
	      return menuManager.getById('top_menu_id_collaboration');
	    }
	    return null;
	  }
	}
	function _handleCounterUpdate2$1(event) {
	  const menu = this.getMenu();
	  const [counter] = event.getCompatData();
	  menu == null ? void 0 : menu.updateCounter('im-message', counter);
	}
	function _handleLiveFeedCounterDecrement2(event) {
	  const [decrement] = event.getCompatData();
	  const menu = this.getMenu();
	  if (menu) {
	    const item = menu.getItemById('menu_live_feed');
	    if (item) {
	      const itemData = menu.getItemData(item);
	      const {
	        COUNTER,
	        COUNTER_ID
	      } = itemData;
	      menu == null ? void 0 : menu.updateCounter(COUNTER_ID, Math.max(0, COUNTER - decrement));
	    }
	  }
	}

	const DEFAULT_SLIDER_BLUR = 'blur(6px)';
	const IM_SLIDER_BLUR = 'blur(10px)';
	const SIDEPANEL_BORDER_RADIUS = '18px 18px 0 0';
	var _rightBar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("rightBar");
	var _header = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("header");
	var _footer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("footer");
	var _composite = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("composite");
	var _chatMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chatMenu");
	var _goTopButton$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("goTopButton");
	var _collaborationMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("collaborationMenu");
	var _patchPopupMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("patchPopupMenu");
	var _patchJSClock = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("patchJSClock");
	var _fixSliderBorderRadius = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fixSliderBorderRadius");
	var _makeSliderBlurry = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("makeSliderBlurry");
	var _setSliderBlur = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setSliderBlur");
	var _resetSliderBlur = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetSliderBlur");
	var _preventFromIframe = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preventFromIframe");
	var _applyUserAgentRules = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyUserAgentRules");
	var _patchRestAPI = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("patchRestAPI");
	class SiteTemplate {
	  constructor() {
	    Object.defineProperty(this, _patchRestAPI, {
	      value: _patchRestAPI2
	    });
	    Object.defineProperty(this, _applyUserAgentRules, {
	      value: _applyUserAgentRules2
	    });
	    Object.defineProperty(this, _preventFromIframe, {
	      value: _preventFromIframe2
	    });
	    Object.defineProperty(this, _resetSliderBlur, {
	      value: _resetSliderBlur2
	    });
	    Object.defineProperty(this, _setSliderBlur, {
	      value: _setSliderBlur2
	    });
	    Object.defineProperty(this, _makeSliderBlurry, {
	      value: _makeSliderBlurry2
	    });
	    Object.defineProperty(this, _fixSliderBorderRadius, {
	      value: _fixSliderBorderRadius2
	    });
	    Object.defineProperty(this, _patchJSClock, {
	      value: _patchJSClock2
	    });
	    Object.defineProperty(this, _patchPopupMenu, {
	      value: _patchPopupMenu2
	    });
	    Object.defineProperty(this, _rightBar, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _header, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _footer, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _composite, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _chatMenu, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _goTopButton$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _collaborationMenu, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _preventFromIframe)[_preventFromIframe]();
	    babelHelpers.classPrivateFieldLooseBase(this, _makeSliderBlurry)[_makeSliderBlurry]();
	    babelHelpers.classPrivateFieldLooseBase(this, _patchPopupMenu)[_patchPopupMenu]();
	    babelHelpers.classPrivateFieldLooseBase(this, _patchRestAPI)[_patchRestAPI]();
	    babelHelpers.classPrivateFieldLooseBase(this, _patchJSClock)[_patchJSClock]();
	    babelHelpers.classPrivateFieldLooseBase(this, _goTopButton$1)[_goTopButton$1] = new GoTopButton();
	    babelHelpers.classPrivateFieldLooseBase(this, _rightBar)[_rightBar] = new RightBar({
	      goTopButton: babelHelpers.classPrivateFieldLooseBase(this, _goTopButton$1)[_goTopButton$1]
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _header)[_header] = new Header();
	    babelHelpers.classPrivateFieldLooseBase(this, _footer)[_footer] = new Footer();
	    babelHelpers.classPrivateFieldLooseBase(this, _composite)[_composite] = new Composite();
	    babelHelpers.classPrivateFieldLooseBase(this, _chatMenu)[_chatMenu] = new ChatMenu();
	    babelHelpers.classPrivateFieldLooseBase(this, _collaborationMenu)[_collaborationMenu] = new CollaborationMenu();
	    babelHelpers.classPrivateFieldLooseBase(this, _applyUserAgentRules)[_applyUserAgentRules]();
	  }
	  getRightBar() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _rightBar)[_rightBar];
	  }
	  getHeader() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _header)[_header];
	  }
	  getFooter() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _footer)[_footer];
	  }
	  getComposite() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _composite)[_composite];
	  }
	  getChatMenu() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _chatMenu)[_chatMenu];
	  }
	  getCollaborationMenu() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _collaborationMenu)[_collaborationMenu];
	  }
	  canUseBlurry() {
	    return !main_core.Dom.hasClass(document.documentElement, 'bx-integrated-gpu');
	  }
	}
	function _patchPopupMenu2() {
	  main_core_events.EventEmitter.subscribe('BX.Main.Menu:onInit', event => {
	    const {
	      params
	    } = event.getData();
	    if (params && main_core.Type.isNumber(params.maxWidth)) {
	      // We increased menu-item's font-size that's why we increase max-width
	      params.maxWidth += 10;
	    }
	  });
	}
	function _patchJSClock2() {
	  main_core_events.EventEmitter.subscribe('onJCClockInit', config => {
	    window.JCClock.setOptions({
	      centerXInline: 83,
	      centerX: 83,
	      centerYInline: 67,
	      centerY: 79,
	      minuteLength: 31,
	      hourLength: 26,
	      popupHeight: 229,
	      inaccuracy: 15,
	      cancelCheckClick: true
	    });
	  });
	}
	function _fixSliderBorderRadius2(slider, forceBgColor = null) {
	  if (slider.isSelfContained()) {
	    main_core.Dom.style(slider.getContainer(), 'background-color', '#eef2f4');
	  } else {
	    var _slider$getFrameWindo;
	    const frameDocument = slider == null ? void 0 : (_slider$getFrameWindo = slider.getFrameWindow()) == null ? void 0 : _slider$getFrameWindo.document;
	    if (frameDocument && frameDocument.body) {
	      const bgColor = main_core.Dom.style(frameDocument.body, 'background-color');
	      const bgImage = main_core.Dom.style(frameDocument.body, 'background-image');
	      const foreBgColor = bgColor === 'rgba(0, 0, 0, 0)' && bgImage === 'none' ? '#eef2f4' : 'rgba(0, 0, 0, 0)';
	      main_core.Dom.style(slider.getContainer(), 'background-color', forceBgColor === null ? foreBgColor : forceBgColor);
	    } else {
	      main_core.Dom.style(slider.getContainer(), 'background-color', forceBgColor === null ? '#eef2f4' : forceBgColor);
	    }
	  }
	}
	function _makeSliderBlurry2() {
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpenComplete', event => {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    if (!slider.isLoaded()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _fixSliderBorderRadius)[_fixSliderBorderRadius](slider, 'rgba(0, 0, 0, 0)');
	    }
	    const previousSlider = main_sidepanel.SidePanel.Instance.getPreviousSlider();
	    if (previousSlider) {
	      babelHelpers.classPrivateFieldLooseBase(this, _resetSliderBlur)[_resetSliderBlur](previousSlider);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setSliderBlur)[_setSliderBlur](slider);
	  });
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClosing', event => {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    const previousSlider = main_sidepanel.SidePanel.Instance.getPreviousSlider();
	    if (previousSlider) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setSliderBlur)[_setSliderBlur](previousSlider);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _resetSliderBlur)[_resetSliderBlur](slider);
	  });
	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onOpening', () => {
	    if (main_sidepanel.SidePanel.Instance.getOpenSlidersCount() === 1) {
	      main_core.Dom.addClass(document.body, '--ui-reset-bg-blur');
	    }
	    // else
	    // {
	    // 	const previousSlider = SidePanel.Instance.getPreviousSlider();
	    // 	const frameDocument = previousSlider?.getFrameWindow()?.document;
	    // 	Dom.addClass(frameDocument?.body, '--ui-reset-bg-blur');
	    // }
	  });

	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onClosing', () => {
	    if (main_sidepanel.SidePanel.Instance.getOpenSlidersCount() === 1) {
	      main_core.Dom.removeClass(document.body, '--ui-reset-bg-blur');
	    }
	    // else
	    // {
	    // 	const previousSlider = SidePanel.Instance.getPreviousSlider();
	    // 	const frameDocument = previousSlider?.getFrameWindow()?.document;
	    // 	Dom.removeClass(frameDocument?.body, '--ui-reset-bg-blur');
	    // }
	  });

	  main_core_events.EventEmitter.subscribe('SidePanel.Slider:onLoad', event => {
	    const [sliderEvent] = event.getData();
	    const slider = sliderEvent.getSlider();
	    babelHelpers.classPrivateFieldLooseBase(this, _fixSliderBorderRadius)[_fixSliderBorderRadius](slider);
	    requestAnimationFrame(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _fixSliderBorderRadius)[_fixSliderBorderRadius](slider);
	    });
	  });
	}
	function _setSliderBlur2(slider) {
	  if (!this.canUseBlurry()) {
	    return;
	  }
	  const isMessenger = slider.getUrl().startsWith('im:slider');
	  main_core.Dom.style(slider.getOverlay(), '-webkit-backdrop-filter', isMessenger ? IM_SLIDER_BLUR : DEFAULT_SLIDER_BLUR);
	  main_core.Dom.style(slider.getOverlay(), 'backdrop-filter', isMessenger ? IM_SLIDER_BLUR : DEFAULT_SLIDER_BLUR);
	  main_core.Dom.style(slider.getOverlay(), '--sidepanel-border-radius', SIDEPANEL_BORDER_RADIUS);
	}
	function _resetSliderBlur2(slider) {
	  if (!this.canUseBlurry()) {
	    return;
	  }
	  main_core.Dom.style(slider.getOverlay(), '-webkit-backdrop-filter', null);
	  main_core.Dom.style(slider.getOverlay(), 'backdrop-filter', null);
	  main_core.Dom.style(slider.getOverlay(), '--sidepanel-border-radius', null);
	}
	function _preventFromIframe2() {
	  const iframeMode = window !== window.top;
	  if (iframeMode) {
	    window.top.location = window.location.href;
	  }
	}
	function _applyUserAgentRules2() {
	  if (!main_core.Browser.isMobile() && document.referrer !== '' && document.referrer.startsWith(location.origin) === false) {
	    main_core.Runtime.loadExtension('intranet.recognize-links');
	  }
	}
	function _patchRestAPI2() {
	  const AppLayout = main_core.Reflection.getClass('BX.rest.AppLayout');
	  if (!AppLayout) {
	    return;
	  }
	  const placementInterface = AppLayout.initializePlacement('DEFAULT');
	  placementInterface.prototype.showHelper = async function (params, cb) {
	    let query = '';
	    if (main_core.Type.isNumber(params)) {
	      query = `redirect=detail&code=${params}`;
	    } else if (main_core.Type.isStringFilled(params)) {
	      query = params;
	    } else if (main_core.Type.isPlainObject(params)) {
	      for (const param of Object.keys(params)) {
	        if (query.length > 0) {
	          query += '&';
	        }
	        query += `${param}=${params[param]}`;
	      }
	    }
	    if (query.length > 0) {
	      await main_core.Runtime.loadExtension('helper');
	      const Helper = main_core.Reflection.getClass('BX.Helper');
	      Helper.show(query);
	    }
	  };
	}

	var _searchOptions = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("searchOptions");
	var _extensionLoaded = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("extensionLoaded");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _button$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("button");
	var _input = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("input");
	var _searchTitleInstance = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("searchTitleInstance");
	var _handleButtonClick$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleButtonClick");
	var _handleInputFocusOut = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleInputFocusOut");
	class SearchTitle {
	  constructor(options) {
	    Object.defineProperty(this, _handleInputFocusOut, {
	      value: _handleInputFocusOut2
	    });
	    Object.defineProperty(this, _handleButtonClick$1, {
	      value: _handleButtonClick2$1
	    });
	    Object.defineProperty(this, _searchOptions, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _extensionLoaded, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _button$1, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _input, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _searchTitleInstance, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = document.getElementById(options.containerId);
	    babelHelpers.classPrivateFieldLooseBase(this, _button$1)[_button$1] = document.getElementById(options.buttonId);
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input] = document.getElementById(options.inputId);
	    babelHelpers.classPrivateFieldLooseBase(this, _searchOptions)[_searchOptions] = options.searchOptions;
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _button$1)[_button$1], 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleButtonClick$1)[_handleButtonClick$1].bind(this));
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _input)[_input], 'focusout', babelHelpers.classPrivateFieldLooseBase(this, _handleInputFocusOut)[_handleInputFocusOut].bind(this));
	  }
	  open() {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--active');
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].disabled = false;
	    setTimeout(() => {
	      babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].focus();
	    }, 200);
	  }
	  close() {
	    main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--active');
	    babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].disabled = true;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _searchTitleInstance)[_searchTitleInstance] !== null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _searchTitleInstance)[_searchTitleInstance].clearSearch();
	      babelHelpers.classPrivateFieldLooseBase(this, _searchTitleInstance)[_searchTitleInstance].closeResult();
	    }
	  }
	}
	function _handleButtonClick2$1() {
	  if (main_core.Dom.hasClass(babelHelpers.classPrivateFieldLooseBase(this, _container)[_container], '--active')) {
	    this.close();
	  } else {
	    this.open();
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _extensionLoaded)[_extensionLoaded]) {
	    return;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _extensionLoaded)[_extensionLoaded] = true;
	  main_core.Runtime.loadExtension('intranet.search_title').then(() => {
	    const SearchTitleClass = main_core.Reflection.getClass('BX.Intranet.SearchTitle');
	    babelHelpers.classPrivateFieldLooseBase(this, _searchTitleInstance)[_searchTitleInstance] = new SearchTitleClass(babelHelpers.classPrivateFieldLooseBase(this, _searchOptions)[_searchOptions]);
	  }).catch(error => {
	    console.error(error);
	  });
	}
	function _handleInputFocusOut2(event) {
	  if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _input)[_input].value) && event.relatedTarget !== babelHelpers.classPrivateFieldLooseBase(this, _button$1)[_button$1]) {
	    this.close();
	  }
	}

	var _avatarWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("avatarWrapper");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _setHiddenAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setHiddenAvatar");
	var _setVisibleAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setVisibleAvatar");
	var _showWidget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showWidget");
	var _getWidgetLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetLoader");
	var _getContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _showCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showCounter");
	var _showWorkTimeState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showWorkTimeState");
	var _getCounterWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounterWrapper");
	var _getWorkTimeStateWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWorkTimeStateWrapper");
	var _getCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounter");
	var _getWorkTimeState = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWorkTimeState");
	var _setEventHandlerForUpdateCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEventHandlerForUpdateCounter");
	var _setEventHandlerForChangeAvatar = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEventHandlerForChangeAvatar");
	class AvatarButton {
	  static init(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper] = document.querySelector('[data-id="bx-avatar-widget"]');
	    babelHelpers.classPrivateFieldLooseBase(this, _setEventHandlerForChangeAvatar)[_setEventHandlerForChangeAvatar]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].signDocumentsCounter > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showCounter)[_showCounter]();
	      babelHelpers.classPrivateFieldLooseBase(this, _setEventHandlerForUpdateCounter)[_setEventHandlerForUpdateCounter]();
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].workTimeAvailable) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showWorkTimeState)[_showWorkTimeState]();
	    }
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper], 'click', () => {
	      main_core.Event.unbindAll(babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper]);
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().getPopup().setFixed(true);
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().createSkeletonFromConfig(options.skeleton).show();
	      babelHelpers.classPrivateFieldLooseBase(this, _setHiddenAvatar)[_setHiddenAvatar]();
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().getPopup().subscribe('onClose', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _setVisibleAvatar)[_setVisibleAvatar]();
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().getPopup().subscribe('onShow', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _setHiddenAvatar)[_setHiddenAvatar]();
	      });
	      main_core.Runtime.loadExtension(['intranet.avatar-widget']).then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showWidget)[_showWidget]();
	      }).catch(() => {});
	    });
	  }
	}
	function _setHiddenAvatar2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper], 'opacity', '0');
	}
	function _setVisibleAvatar2() {
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper], 'opacity', '1');
	}
	function _showWidget2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getContent)[_getContent]().then(response => {
	    babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().clearBeforeInsertContent();
	    intranet_avatarWidget.AvatarWidget.getInstance().setOptions({
	      buttonWrapper: babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper],
	      loader: babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader)[_getWidgetLoader]().getPopup(),
	      data: response.data
	    }).show();
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper], 'click', () => {
	      intranet_avatarWidget.AvatarWidget.getInstance().show();
	    });
	  }).catch(() => {});
	}
	function _getWidgetLoader2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('widgetLoader', () => {
	    return new intranet_widgetLoader.WidgetLoader({
	      id: 'bx-avatar-header-popup',
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper],
	      className: 'intranet-avatar-widget-base-popup',
	      width: 450,
	      useAngle: false,
	      fixed: true,
	      offsetTop: -50,
	      offsetLeft: -392
	    });
	  });
	}
	function _getContent2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('content', () => {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('intranet.user.widget.getContent').then(response => resolve(response)).catch(response => reject(response));
	    });
	  });
	}
	function _showCounter2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getCounter)[_getCounter]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper)[_getCounterWrapper]());
	}
	function _showWorkTimeState2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getWorkTimeState)[_getWorkTimeState]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getWorkTimeStateWrapper)[_getWorkTimeStateWrapper]());
	}
	function _getCounterWrapper2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('counterWrapper', () => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper].querySelector('.air-user-profile-avatar__counter');
	  });
	}
	function _getWorkTimeStateWrapper2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('workTimeStateWrapper', () => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper].querySelector('.air-user-profile-avatar__work-time-state');
	  });
	}
	function _getCounter2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('counter', () => {
	    return new ui_cnt.Counter({
	      color: ui_cnt.Counter.Color.DANGER,
	      size: ui_cnt.Counter.Size.MEDIUM,
	      value: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].signDocumentsCounter,
	      useAirDesign: true,
	      style: ui_cnt.CounterStyle.FILLED_ALERT
	    });
	  });
	}
	function _getWorkTimeState2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('workTimeState', () => {
	    return new timeman_workTimeStateIcon.WorkTimeStateIcon({
	      state: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].workTimeState,
	      action: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].workTimeAction
	    });
	  });
	}
	function _setEventHandlerForUpdateCounter2() {
	  pull_client.PULL.subscribe({
	    moduleId: 'sign',
	    command: babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].signDocumentsPullEventName,
	    callback: params => {
	      if (!main_core.Type.isNumber(params == null ? void 0 : params.needActionCount)) {
	        return;
	      }
	      if ((params == null ? void 0 : params.needActionCount) > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _getCounter)[_getCounter]().update(params.needActionCount);
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _getCounter)[_getCounter]().destroy();
	      }
	    }
	  });
	}
	function _setEventHandlerForChangeAvatar2() {
	  const avatar = babelHelpers.classPrivateFieldLooseBase(this, _avatarWrapper)[_avatarWrapper].querySelector('i');
	  main_core_events.EventEmitter.subscribe('BX.Intranet.UserProfile:Avatar:changed', event => {
	    const data = event.getData()[0];
	    const url = data && data.url ? data.url : '';
	    const eventUserId = data && data.userId ? data.userId : 0;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options)[_options].userId === eventUserId && avatar) {
	      avatar.style = main_core.Type.isStringFilled(url) ? `background-size: cover; background-image: url('${encodeURI(url)}')` : '';
	    }
	  });
	}
	Object.defineProperty(AvatarButton, _setEventHandlerForChangeAvatar, {
	  value: _setEventHandlerForChangeAvatar2
	});
	Object.defineProperty(AvatarButton, _setEventHandlerForUpdateCounter, {
	  value: _setEventHandlerForUpdateCounter2
	});
	Object.defineProperty(AvatarButton, _getWorkTimeState, {
	  value: _getWorkTimeState2
	});
	Object.defineProperty(AvatarButton, _getCounter, {
	  value: _getCounter2
	});
	Object.defineProperty(AvatarButton, _getWorkTimeStateWrapper, {
	  value: _getWorkTimeStateWrapper2
	});
	Object.defineProperty(AvatarButton, _getCounterWrapper, {
	  value: _getCounterWrapper2
	});
	Object.defineProperty(AvatarButton, _showWorkTimeState, {
	  value: _showWorkTimeState2
	});
	Object.defineProperty(AvatarButton, _showCounter, {
	  value: _showCounter2
	});
	Object.defineProperty(AvatarButton, _getContent, {
	  value: _getContent2
	});
	Object.defineProperty(AvatarButton, _getWidgetLoader, {
	  value: _getWidgetLoader2
	});
	Object.defineProperty(AvatarButton, _showWidget, {
	  value: _showWidget2
	});
	Object.defineProperty(AvatarButton, _setVisibleAvatar, {
	  value: _setVisibleAvatar2
	});
	Object.defineProperty(AvatarButton, _setHiddenAvatar, {
	  value: _setHiddenAvatar2
	});
	Object.defineProperty(AvatarButton, _avatarWrapper, {
	  writable: true,
	  value: void 0
	});
	Object.defineProperty(AvatarButton, _cache, {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	});
	Object.defineProperty(AvatarButton, _options, {
	  writable: true,
	  value: void 0
	});

	var _options$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _buttonWrapper = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonWrapper");
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _getExtensionWidgetName = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getExtensionWidgetName");
	var _showWidget$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showWidget");
	var _getWidget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidget");
	var _getWidgetLoader$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetLoader");
	var _getContent$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _getCounter$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounter");
	var _getCounterWrapper$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounterWrapper");
	var _setCounterValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setCounterValue");
	var _setEventHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEventHandlers");
	var _showInfrastructureSlider = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showInfrastructureSlider");
	class LicenseButton {
	  static init(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper] = document.querySelector('[data-id="licenseWidgetWrapper"]');
	    babelHelpers.classPrivateFieldLooseBase(this, _setEventHandlers)[_setEventHandlers]();
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud && babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].ordersAwaitingPayment > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _setCounterValue)[_setCounterValue](babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].ordersAwaitingPayment);
	    }
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper], 'click', () => {
	      main_core.Event.unbindAll(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper]);
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$1)[_getWidgetLoader$1]().createSkeletonFromConfig(options.skeleton).show();
	      main_core.Runtime.loadExtension([babelHelpers.classPrivateFieldLooseBase(this, _getExtensionWidgetName)[_getExtensionWidgetName]()]).then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showWidget$1)[_showWidget$1]();
	      }).catch(() => {});
	    });
	  }
	}
	function _getExtensionWidgetName2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud) {
	    return 'bitrix24.license-widget';
	  }
	  return 'intranet.license-widget';
	}
	function _showWidget2$1() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getContent$1)[_getContent$1]().then(response => {
	    babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$1)[_getWidgetLoader$1]().clearBeforeInsertContent();
	    let licenseData = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud) {
	      licenseData = response.data;
	      licenseData.loader = babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$1)[_getWidgetLoader$1]().getPopup();
	      licenseData.wrapper = babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper];
	    } else {
	      licenseData = {
	        loader: babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$1)[_getWidgetLoader$1]().getPopup(),
	        buttonWrapper: babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper],
	        data: response.data
	      };
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _getWidget)[_getWidget]().setOptions(licenseData).show();
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper], 'click', () => {
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidget)[_getWidget]().show();
	    });
	  }).catch(() => {});
	}
	function _getWidget2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('widget', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud) {
	      return bitrix24_licenseWidget.LicenseWidget.getInstance();
	    }
	    return intranet_licenseWidget.LicenseWidget.getInstance();
	  });
	}
	function _getWidgetLoader2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('widgetLoader', () => {
	    return new intranet_widgetLoader.WidgetLoader({
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper],
	      width: 374,
	      id: 'bx-license-header-popup'
	    });
	  });
	}
	function _getContent2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('content', () => {
	    return new Promise((resolve, reject) => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud) {
	        main_core.ajax.runComponentAction('bitrix:bitrix24.license.widget', 'getData', {
	          mode: 'class'
	        }).then(response => resolve(response)).catch(response => reject(response));
	      } else {
	        main_core.ajax.runAction('intranet.license.widget.getContent').then(response => resolve(response)).catch(response => reject(response));
	      }
	    });
	  });
	}
	function _getCounter2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('counter', () => {
	    return new ui_cnt.Counter({
	      color: ui_cnt.CounterColor.DANGER,
	      useAirDesign: true,
	      style: ui_cnt.CounterStyle.FILLED_ALERT
	    });
	  });
	}
	function _getCounterWrapper2$1() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('counter-wrapper', () => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper)[_buttonWrapper].querySelector('.air-header-button__counter');
	  });
	}
	function _setCounterValue2(value) {
	  if (value < 1) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$1)[_getCounter$1]().destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].delete('counter');
	  }
	  if (value > 0 && babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper$1)[_getCounterWrapper$1]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$1)[_getCounter$1]().update(value);
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$1)[_getCounter$1]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper$1)[_getCounterWrapper$1]());
	  }
	}
	function _setEventHandlers2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud && babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isSidePanelDemoLicense) {
	    BX.SidePanel.Instance.bindAnchors({
	      rules: [{
	        condition: [/\/settings\/license_demo.php/],
	        handler(event) {
	          ui_infoHelper.FeaturePromotersRegistry.getPromoter({
	            code: 'limit_demo'
	          }).show();
	          event.stopPropagation();
	          event.preventDefault();
	        }
	      }]
	    });
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isCloud && babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].isAdmin) {
	    pull_client.PULL.subscribe({
	      moduleId: 'bitrix24',
	      command: 'updateCountOrdersAwaitingPayment',
	      callback: params => {
	        main_core_events.EventEmitter.emit('BX.Bitrix24.Orders:updateOrdersAwaitingPayment', new main_core_events.BaseEvent({
	          data: {
	            counter: Number(params.count)
	          }
	        }));
	        if (params.count > 0) {
	          babelHelpers.classPrivateFieldLooseBase(this, _setCounterValue)[_setCounterValue](Number(params.count));
	        }
	      }
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'Bitrix24InfrastructureSlider:show', babelHelpers.classPrivateFieldLooseBase(this, _showInfrastructureSlider)[_showInfrastructureSlider].bind(this));
	  }
	}
	function _showInfrastructureSlider2() {
	  const params = babelHelpers.classPrivateFieldLooseBase(this, _options$1)[_options$1].infrastructureForm;
	  BX.SidePanel.Instance.open('bx-infrastructure-slider', {
	    contentCallback: () => {
	      return `<script data-b24-form="inline/${params.id}/${params.secCode}" data-skip-moving="true"></script>`;
	    },
	    width: 664,
	    loader: 'default-loader',
	    cacheable: false,
	    closeByEsc: false,
	    data: {
	      rightBoundary: 0
	    },
	    events: {
	      onOpen: () => {
	        (function (w, d, u) {
	          const s = d.createElement('script');
	          s.async = true;
	          s.src = `${u}?${Date.now() / 180000 | 0}`;
	          const h = d.getElementsByTagName('script')[0];
	          h.parentNode.insertBefore(s, h);
	        })(window, document, `https://bitrix24.team/upload/crm/form/loader_${params.id}_${params.secCode}.js`);
	      },
	      onOpenComplete: () => {
	        top.addEventListener('b24:form:send:success', event => {
	          if (event.detail.object.identification.id === params.id) {
	            main_core.ajax.runComponentAction('bitrix:bitrix24.license.widget', 'setOptionWaitingInfrastructure', {
	              mode: 'class',
	              data: {}
	            });
	          }
	        });
	      }
	    }
	  });
	}
	Object.defineProperty(LicenseButton, _showInfrastructureSlider, {
	  value: _showInfrastructureSlider2
	});
	Object.defineProperty(LicenseButton, _setEventHandlers, {
	  value: _setEventHandlers2
	});
	Object.defineProperty(LicenseButton, _setCounterValue, {
	  value: _setCounterValue2
	});
	Object.defineProperty(LicenseButton, _getCounterWrapper$1, {
	  value: _getCounterWrapper2$1
	});
	Object.defineProperty(LicenseButton, _getCounter$1, {
	  value: _getCounter2$1
	});
	Object.defineProperty(LicenseButton, _getContent$1, {
	  value: _getContent2$1
	});
	Object.defineProperty(LicenseButton, _getWidgetLoader$1, {
	  value: _getWidgetLoader2$1
	});
	Object.defineProperty(LicenseButton, _getWidget, {
	  value: _getWidget2
	});
	Object.defineProperty(LicenseButton, _showWidget$1, {
	  value: _showWidget2$1
	});
	Object.defineProperty(LicenseButton, _getExtensionWidgetName, {
	  value: _getExtensionWidgetName2
	});
	Object.defineProperty(LicenseButton, _options$1, {
	  writable: true,
	  value: void 0
	});
	Object.defineProperty(LicenseButton, _buttonWrapper, {
	  writable: true,
	  value: void 0
	});
	Object.defineProperty(LicenseButton, _cache$1, {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	});

	var _buttonWrapper$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("buttonWrapper");
	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _options$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _showWidget$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showWidget");
	var _getWidgetLoader$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getWidgetLoader");
	var _getContent$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _setEventHandlers$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEventHandlers");
	var _onReceiveCounterValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onReceiveCounterValue");
	var _getCounterWrapper$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounterWrapper");
	var _getCounter$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounter");
	var _getCounterValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCounterValue");
	var _onFirstWatchNewStructure = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onFirstWatchNewStructure");
	class InvitationButton {
	  static init(options) {
	    babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1] = document.querySelector('[data-id="invitationButton"]');
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1], 'click', () => {
	      main_core.Event.unbindAll(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1]);
	      babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$2)[_getWidgetLoader$2]().createSkeletonFromConfig(options.skeleton).show();
	      main_core.Runtime.loadExtension(['intranet.invitation-widget']).then(() => {
	        babelHelpers.classPrivateFieldLooseBase(this, _showWidget$2)[_showWidget$2]();
	      }).catch(() => {});
	    });
	    if (babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].invitationCounter > 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper$2)[_getCounterWrapper$2]());
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _setEventHandlers$1)[_setEventHandlers$1]();
	  }
	}
	function _showWidget2$2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getContent$2)[_getContent$2]().then(response => {
	    babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$2)[_getWidgetLoader$2]().clearBeforeInsertContent();
	    intranet_invitationWidget.InvitationWidget.getInstance().setOptions({
	      buttonWrapper: babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1],
	      loader: babelHelpers.classPrivateFieldLooseBase(this, _getWidgetLoader$2)[_getWidgetLoader$2]().getPopup(),
	      ...response.data
	    }).show();
	    main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1], 'click', () => {
	      intranet_invitationWidget.InvitationWidget.getInstance().show();
	    });
	  }).catch(() => {});
	}
	function _getWidgetLoader2$2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('widgetLoader', () => {
	    return new intranet_widgetLoader.WidgetLoader({
	      bindElement: babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1],
	      width: 350,
	      id: 'bx-invitation-header-popup'
	    });
	  });
	}
	function _getContent2$2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('content', () => {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runAction('intranet.invitationwidget.getData', {
	        data: {},
	        analyticsLabel: {
	          headerPopup: 'Y'
	        }
	      }).then(response => resolve(response)).catch(response => reject(response));
	    });
	  });
	}
	function _setEventHandlers2$1() {
	  main_core_events.EventEmitter.subscribeOnce('HR.company-structure:first-popup-showed', babelHelpers.classPrivateFieldLooseBase(this, _onFirstWatchNewStructure)[_onFirstWatchNewStructure].bind(this));
	  main_core_events.EventEmitter.subscribe('onPullEvent-main', event => {
	    const [command, params] = event.getCompatData();
	    if (command === 'user_counter' && params[main_core.Loc.getMessage('SITE_ID')]) {
	      const value = params[main_core.Loc.getMessage('SITE_ID')][babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].counterId];
	      if (value > 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _onReceiveCounterValue)[_onReceiveCounterValue](value);
	      }
	    }
	  });
	}
	function _onReceiveCounterValue2(value) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].shouldShowStructureCounter) {
	    value++;
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().update(value);
	  babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].invitationCounter = value;
	  if (value > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper$2)[_getCounterWrapper$2]());
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].delete('counter');
	  }
	}
	function _getCounterWrapper2$2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('counter-wrapper', () => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _buttonWrapper$1)[_buttonWrapper$1].querySelector('.invitation-widget-counter');
	  });
	}
	function _getCounter2$2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('counter', () => {
	    return new ui_cnt.Counter({
	      value: babelHelpers.classPrivateFieldLooseBase(this, _getCounterValue)[_getCounterValue](),
	      color: ui_cnt.Counter.Color.DANGER,
	      useAirDesign: true,
	      style: ui_cnt.CounterStyle.FILLED_ALERT
	    });
	  });
	}
	function _getCounterValue2() {
	  var _babelHelpers$classPr;
	  let counterValue = Number(babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].invitationCounter);
	  if ((_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].shouldShowStructureCounter) != null ? _babelHelpers$classPr : false) {
	    counterValue++;
	  }
	  return counterValue;
	}
	function _onFirstWatchNewStructure2() {
	  let value = babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().value;
	  if (!main_core.Type.isNumber(value)) {
	    return;
	  }
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].shouldShowStructureCounter) {
	    return;
	  }
	  value--;
	  babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].shouldShowStructureCounter = false;
	  babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().update(value);
	  babelHelpers.classPrivateFieldLooseBase(this, _options$2)[_options$2].invitationCounter = value;
	  if (value > 0) {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().renderTo(babelHelpers.classPrivateFieldLooseBase(this, _getCounterWrapper$2)[_getCounterWrapper$2]());
	  } else {
	    babelHelpers.classPrivateFieldLooseBase(this, _getCounter$2)[_getCounter$2]().destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].delete('counter');
	  }
	}
	Object.defineProperty(InvitationButton, _onFirstWatchNewStructure, {
	  value: _onFirstWatchNewStructure2
	});
	Object.defineProperty(InvitationButton, _getCounterValue, {
	  value: _getCounterValue2
	});
	Object.defineProperty(InvitationButton, _getCounter$2, {
	  value: _getCounter2$2
	});
	Object.defineProperty(InvitationButton, _getCounterWrapper$2, {
	  value: _getCounterWrapper2$2
	});
	Object.defineProperty(InvitationButton, _onReceiveCounterValue, {
	  value: _onReceiveCounterValue2
	});
	Object.defineProperty(InvitationButton, _setEventHandlers$1, {
	  value: _setEventHandlers2$1
	});
	Object.defineProperty(InvitationButton, _getContent$2, {
	  value: _getContent2$2
	});
	Object.defineProperty(InvitationButton, _getWidgetLoader$2, {
	  value: _getWidgetLoader2$2
	});
	Object.defineProperty(InvitationButton, _showWidget$2, {
	  value: _showWidget2$2
	});
	Object.defineProperty(InvitationButton, _buttonWrapper$1, {
	  writable: true,
	  value: void 0
	});
	Object.defineProperty(InvitationButton, _cache$2, {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	});
	Object.defineProperty(InvitationButton, _options$2, {
	  writable: true,
	  value: void 0
	});

	let _$1 = t => t,
	  _t$1,
	  _t2$1;
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _initPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("initPopup");
	var _renderPopupContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPopupContent");
	class LanguageSwitcher {
	  constructor() {
	    Object.defineProperty(this, _renderPopupContent, {
	      value: _renderPopupContent2
	    });
	    Object.defineProperty(this, _initPopup, {
	      value: _initPopup2
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	  }
	  async showLanguageListPopup(bindElement, languages) {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      await babelHelpers.classPrivateFieldLooseBase(this, _initPopup)[_initPopup](bindElement, languages);
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].isShown()) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hideLanguageListPopup() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) == null ? void 0 : _babelHelpers$classPr.close();
	  }
	  switchPortalLanguage(languageCode) {
	    window.location.href = `/auth/?user_lang=${languageCode}&backurl=${getBackUrl()}`;
	  }
	}
	async function _initPopup2(bindElement, languages) {
	  const windowScrollHandler = () => {
	    this.hideLanguageListPopup();
	  };
	  const {
	    Popup
	  } = await main_core.Runtime.loadExtension('main.popup');
	  const popupOptions = {
	    bindElement,
	    autoHide: true,
	    closeByEcs: true,
	    cachable: false,
	    content: babelHelpers.classPrivateFieldLooseBase(this, _renderPopupContent)[_renderPopupContent](languages),
	    events: {
	      onPopupClose: () => {
	        main_core.Event.unbind(window, 'scroll', windowScrollHandler);
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].destroy();
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	      }
	    }
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new Popup(popupOptions);
	  main_core.Event.bind(window, 'scroll', windowScrollHandler);
	}
	function _renderPopupContent2(languages) {
	  const container = main_core.Tag.render(_t$1 || (_t$1 = _$1`<div class="intranet__language-popup_list"></div>`));
	  Object.entries(languages).forEach(([languageCode, languageItem]) => {
	    const languageItemElement = main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
				<div class="intranet__language-popup_language-item">
					<span class="intranet__language-popup_language-item-name">${0}</span>
					<span class="intranet__language-popup_language-beta">${0}</span>
				</div>
			`), languageItem.NAME, languageItem.IS_BETA ? ', beta' : '');
	    main_core.Event.bind(languageItemElement, 'click', () => {
	      this.switchPortalLanguage(languageCode);
	    });
	    main_core.Dom.append(languageItemElement, container);
	  });
	  return container;
	}
	const languageSwitcher = new LanguageSwitcher();

	const Template = new SiteTemplate();

	// Compatibility
	/**
	 * @deprecated
	 */
	window.showPartnerForm = showPartnerConnectForm;

	/**
	 * @deprecated
	 */
	window.B24 = {
	  /**
	   * @deprecated
	   */
	  licenseInfoPopup: {
	    show(popupId, title, content, showDemoButton) {
	      const LicenseInfoPopup = main_core.Reflection.getClass('BX.Bitrix24.LicenseInfoPopup');
	      if (LicenseInfoPopup) {
	        LicenseInfoPopup.show(popupId, title, content, showDemoButton);
	      }
	    }
	  },
	  /**
	   * @deprecated
	   */
	  updateCounters(counters, send) {
	    const LeftMenu = main_core.Reflection.getClass('BX.Intranet.LeftMenu');
	    if (LeftMenu) {
	      LeftMenu.updateCounters(counters, send);
	    }
	  }
	};

	exports.languageSwitcher = languageSwitcher;
	exports.PartnerForm = PartnerForm;
	exports.Template = Template;
	exports.SearchTitle = SearchTitle;
	exports.InvitationButton = InvitationButton;
	exports.LicenseButton = LicenseButton;
	exports.AvatarButton = AvatarButton;

}((this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {}),BX.SidePanel,BX.UI,BX.Intranet,BX.Timeman,BX.Bitrix24,BX.Intranet,BX.UI,BX,BX.Cache,BX.Event,BX.Intranet,BX.Intranet,BX.UI,BX));
//# sourceMappingURL=bitrix24.bundle.js.map
