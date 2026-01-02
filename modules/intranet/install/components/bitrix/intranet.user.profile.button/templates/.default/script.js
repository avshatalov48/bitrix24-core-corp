/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
(function (exports,ui_avatar,ui_popupcomponentsmaker,main_qrcode,main_loader,ui_hint,main_core_cache,ui_qrauthorization,main_core,main_popup,main_core_events,im_v2_lib_desktopApi) {
	'use strict';

	class Options {}
	Options.eventNameSpace = 'BX.Intranet.Userprofile:';

	let _ = t => t,
	  _t,
	  _t2;
	class StressLevel {
	  constructor() {}
	  static getOpenSliderFunction(url) {
	    if (main_core.Type.isStringFilled(url)) {
	      return () => {
	        main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + 'onNeedToHide');
	        BX.SidePanel.Instance.open(url, {
	          cacheable: false,
	          data: {},
	          width: 500
	        });
	      };
	    }
	    return () => {};
	  }
	  static showData(data) {
	    data.value = parseInt(data.value || 0);
	    const result = main_core.Tag.render(_t || (_t = _`
			<div class="system-auth-form__item system-auth-form__scope --vertical" id="user-indicator-pulse">
				<div class="system-auth-form__item-block --margin-bottom">
					<div class="system-auth-form__stress-widget">
						<div data-role="value-degree" class="system-auth-form__stress-widget--arrow" style="transform: rotate(90deg);"></div>
						<div class="system-auth-form__stress-widget--content">
							<div class="system-auth-form__stress-widget--content-title">${0}</div>
							<div data-role="value" class="system-auth-form__stress-widget--content-progress ${0}">0</div>
						</div>
					</div>
					<div class="system-auth-form__item-container --stress-widget-sp">
						<div class="system-auth-form__item-block --center --width-100">
							<span class="system-auth-form__stress-widget--status  --flex --${0}">${0}</span>
							<span class="system-auth-form__icon-help" onclick="${0}"></span>
						</div>
						<div class="system-auth-form__item-title --link-dotted" onclick="${0}">${0}</div>
					</div>
				</div>
				<div class="system-auth-form__item-block --flex --center">
					<div class="system-auth-form__stress-widget--message">${0}</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT'), data.value > 0 ? '' : '--empty', main_core.Text.encode(data.type), main_core.Text.encode(data.typeDescription), this.getOpenSliderFunction(data.url.result), this.getOpenSliderFunction(data.url.check), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON'), main_core.Text.encode(data.comment));
	    setTimeout(() => {
	      const intervalId = setInterval(value => {
	        value.current++;
	        value.node.innerHTML = value.current;
	        if (value.current >= value.end) {
	          clearInterval(intervalId);
	        }
	      }, 600 / data.value, {
	        current: 0,
	        end: data.value,
	        node: result.querySelector('[data-role="value"]')
	      });
	      result.querySelector('[data-role="value-degree"]').style.transform = 'rotate(' + 1.8 * data.value + 'deg)';
	    }, 1000);
	    return result;
	  }
	  static showEmpty({
	    url: {
	      check
	    }
	  }) {
	    return main_core.Tag.render(_t2 || (_t2 = _`
			<div class="system-auth-form__item system-auth-form__scope --vertical --empty-stress --clickable" onclick="${0}">
				<div class="system-auth-form__item-block --margin-bottom">
					<div class="system-auth-form__stress-widget">
						<div data-role="value-degree" class="system-auth-form__stress-widget--arrow" style="transform: rotate(90deg);"></div>
						<div class="system-auth-form__stress-widget--content">
							<div class="system-auth-form__stress-widget--content-title">${0}</div>
							<div data-role="value" class="system-auth-form__stress-widget--content-progress --empty">?</div>
						</div>
					</div>
					<div class="system-auth-form__item-container --stress-widget-sp">
						<div class="system-auth-form__stress-widget--message">${0}</div>
					</div>
				</div>
				<div class="system-auth-form__item-block --flex --center">
					<div class="system-auth-form__item-title --link-dotted">${0}</div>
				</div>
				<div class="system-auth-form__item-new">
					<div class="system-auth-form__item-new--title">${0}</div>
				</div>
			</div>`), this.getOpenSliderFunction(check), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_INDICATOR_TEXT'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_NORESULT_BUTTON'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_STRESSLEVEL_RESULT_COME_ON'));
	  }
	  static getPromise({
	    signedParameters,
	    componentName,
	    userId,
	    data
	  }) {
	    return new Promise((resolve, reject) => {
	      const promise = data ? Promise.resolve({
	        data
	      }) : main_core.ajax.runAction('socialnetwork.api.user.stresslevel.get', {
	        signedParameters: signedParameters,
	        data: {
	          c: componentName,
	          fields: {
	            userId: userId
	          }
	        }
	      });
	      promise.then(({
	        data
	      }) => {
	        if (data && data.id !== undefined && data.value !== undefined) {
	          return resolve(this.showData(data));
	        }
	        const node = main_core.Loc.getMessage('USER_ID') === userId ? this.showEmpty(data) : document.createElement('DIV');
	        return resolve(node);
	      }).catch(error => {
	        resolve(this.showData({
	          id: undefined,
	          value: undefined,
	          urls: {
	            check: undefined
	          }
	        }));
	      });
	    });
	  }
	}

	let _$1 = t => t,
	  _t$1;
	var _params = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("params");
	var _container = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	class ThemePicker extends main_core_events.EventEmitter {
	  constructor(data) {
	    super();
	    Object.defineProperty(this, _params, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _container, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params] = Object.assign({}, data);
	    babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning = String(data.id).indexOf('light:') === 0 ? 'light' : String(data.id).indexOf('dark:') === 0 ? 'dark' : null;
	    this.applyTheme = this.applyTheme.bind(this);
	    this.setEventNamespace(Options.eventNameSpace);
	    main_core_events.EventEmitter.subscribe('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', ({
	      data: {
	        id,
	        theme
	      }
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].id = id;
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning = String(id).indexOf('light:') === 0 ? 'light' : String(id).indexOf('dark:') === 0 ? 'dark' : null;
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].title = theme.title;
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewImage = theme.previewImage;
	      babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewColor = theme.previewColor;
	      this.applyTheme();
	    });
	  }
	  applyTheme() {
	    const container = this.getContainer();
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewImage) && babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning) {
	      container.style.removeProperty('backgroundImage');
	      container.style.removeProperty('backgroundSize');
	      container.style.backgroundImage = 'url("' + babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewImage + '")';
	      container.style.backgroundSize = 'cover';
	    } else {
	      container.style.background = 'none';
	    }
	    if (main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewColor)) {
	      this.getContainer().style.backgroundColor = babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].previewColor;
	    }
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning) {
	      container.style.backgroundColor = 'rgba(255,255,255,1)';
	    }
	    main_core.Dom.removeClass(container, '--light --dark');
	    main_core.Dom.addClass(container, '--' + babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning);
	  }
	  getContainer() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _container)[_container]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	    }
	    const onclick = () => {
	      BX.Intranet.Bitrix24.ThemePicker.Singleton.showDialog(false);
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _container)[_container] = main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<div class="system-auth-form__item system-auth-form__scope --border ${0} --padding-sm">
				<div class="system-auth-form__item-logo">
					<div data-role="preview-color" class="system-auth-form__item-logo--image --theme"></div>
				</div>
				<div class="system-auth-form__item-container --flex --column">
					<div class="system-auth-form__item-title --white-space --block">
						<span data-role="title">${0}</span>
					</div>
					<div class="system-auth-form__item-content --margin-top-auto --center --center-force">
						<div class="ui-qr-popupcomponentmaker__btn" onclick="${0}">${0}</div>
					</div>
				</div>
			</div>`), babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning ? '--' + babelHelpers.classPrivateFieldLooseBase(this, _params)[_params].lightning : '', main_core.Loc.getMessage('AUTH_THEME_DIALOG'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CHANGE'));
	    setTimeout(this.applyTheme, 0);
	    return babelHelpers.classPrivateFieldLooseBase(this, _container)[_container];
	  }
	  static getPromise() {
	    return new Promise((resolve, reject) => {
	      main_core.ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getThemePickerData', {
	        mode: 'class'
	      }).then(response => {
	        const themePicker = new this(response.data);
	        resolve(themePicker.getContainer());
	      }).catch(reject);
	    });
	  }
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$1,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7;
	var _renderUsers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderUsers");
	class Ustat {
	  constructor(data) {
	    Object.defineProperty(this, _renderUsers, {
	      value: _renderUsers2
	    });
	    this.data = data;
	    this.onclickHandle = this.onclickHandle.bind(this);
	  }
	  showData() {
	    const {
	      myPosition,
	      userList,
	      range
	    } = babelHelpers.classPrivateFieldLooseBase(this, _renderUsers)[_renderUsers]();
	    let div;
	    if (!this.data['ENABLED']) {
	      div = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div class="system-auth-form__item system-auth-form__scope --without-stat">
				<div class="system-auth-form__item-container --flex --column">
					<div class="system-auth-form__item-title">${0}</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --lighter" data-role="empty-info">${0}</div>
					</div>
				</div>
			</div>
		`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_DISABLED'));
	    } else if (range > 0 && myPosition > 0) {
	      div = main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
			<div class="system-auth-form__item system-auth-form__scope --clickable" onclick="${0}">
				<div class="system-auth-form__item-container">
					<div class="system-auth-form__item-title --without-margin">${0}</div>
					<div class="system-auth-form__item-title --link-light --margin-s">
						<span>${0}</span>
						<span class="system-auth-form__icon-help" data-hint="${0}" data-hint-no-icon></span>
					</div>
					<div class="system-auth-form__item-title --link-light" data-role="empty-info">${0}</div>

					<div class="system-auth-form__item-title --white-space --margin-xl">
						<span>${0}</span>
						<span class="system-auth-form__ustat-icon --up"></span>
					</div>
					<div class="system-auth-form__userlist">
						${0}
					</div>
				</div>
			</div>
		`), this.onclickHandle, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING'), main_core.Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION', {
	        '#POSITION#': myPosition,
	        '#AMONG#': range
	      }), userList);
	    } else {
	      const onclick = range > 0 ? this.onclickHandle : () => {};
	      div = main_core.Tag.render(_t3 || (_t3 = _$2`
			<div class="system-auth-form__item system-auth-form__scope --without-stat ${0}" onclick="${0}">
				<div class="system-auth-form__item-container --flex --column">
					<div class="system-auth-form__item-title">${0}</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --lighter" data-role="empty-info">${0}</div>
					</div>
				</div>
			</div>
		`), range > 0 ? '--clickable' : '', onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY_BRIEF'));
	    }
	    BX.UI.Hint.init(div);
	    return div;
	  }
	  onclickHandle(event) {
	    main_core_events.EventEmitter.emit(Options.eventNameSpace + 'onNeedToHide');
	    if (window['openIntranetUStat']) {
	      openIntranetUStat(event);
	    }
	  }
	  showWideData() {
	    const {
	      myPosition,
	      userList,
	      range
	    } = babelHelpers.classPrivateFieldLooseBase(this, _renderUsers)[_renderUsers]();
	    const div = main_core.Tag.render(_t4 || (_t4 = _$2`
			<div class="system-auth-form__item system-auth-form__scope --center --padding-ustat ${0}">
				<div class="system-auth-form__item-image">
					<div class="system-auth-form__item-image--src --ustat"></div>
				</div>
				<div class="system-auth-form__item-container --overflow">
					<div class="system-auth-form__item-title --xl --without-margin">${0}</div>
				</div>
				<div class="system-auth-form__item-container --block">
					<div class="system-auth-form__item-title --link-light" data-role="empty-info">${0}</div>
					<div class="system-auth-form__item-container--inline" data-role="my-position">
						<div class="system-auth-form__item-title --link-light --without-margin --margin-right">
							${0}
						</div>
						<div class="system-auth-form__item-title --white-space --margin-xl">
							<span>${0}</span>
							<span class="system-auth-form__ustat-icon --up"></span>
						</div>
					</div>
					<div class="system-auth-form__userlist" data-role="user-list">
						${0}
					</div>
				</div>
				<div class="system-auth-form__icon-help --absolute-right-bottom" data-hint="${0}" data-hint-no-icon></div>
			</div>
		`), range > 0 ? '--clickable' : '--without-stat', main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_IS_EMPTY'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_RATING'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_PULSE_MY_POSITION', {
	      '#POSITION#': myPosition,
	      '#AMONG#': range
	    }), userList, main_core.Loc.getMessage('INTRANET_USTAT_COMPANY_HELP_RATING'));
	    if (range > 0) {
	      div.addEventListener('click', this.onclickHandle);
	    }
	    return div;
	  }
	  static getPromise({
	    userId,
	    isNarrow,
	    data
	  }) {
	    return new Promise((resolve, reject) => {
	      (data ? Promise.resolve({
	        data
	      }) : main_core.ajax.runComponentAction('bitrix:intranet.ustat.department', 'getJson', {
	        mode: 'class',
	        data: {}
	      })).then(({
	        data
	      }) => {
	        const ustat = new this(data);
	        resolve(isNarrow ? ustat.showData() : ustat.showWideData());
	      }).catch(errors => {
	        errors = main_core.Type.isArray(errors) ? errors : [errors];
	        const node = document.createElement('ul');
	        errors.forEach(({
	          message
	        }) => {
	          const errorNode = document.createElement('li');
	          errorNode.innerHTML = message;
	          errorNode.className = 'ui-alert-message';
	          node.appendChild(errorNode);
	        });
	        resolve(main_core.Tag.render(_t5 || (_t5 = _$2`
						<div class="ui-alert ui-alert-danger">
							${0}
						</div>`), node));
	      });
	    });
	  }
	}
	function _renderUsers2() {
	  const userList = document.createDocumentFragment();
	  let myPosition = parseInt(this.data['USERS_RATING']['position']);
	  let myActivity = 0;
	  const usersData = main_core.Type.isPlainObject(this.data['USERS_RATING']['top']) ? Object.values(this.data['USERS_RATING']['top']) : main_core.Type.isArray(this.data['USERS_RATING']['top']) && this.data['USERS_RATING']['top'].length > 0 ? [...this.data['USERS_RATING']['top']] : [{
	    'USER_ID': main_core.Loc.getMessage('USER_ID'),
	    ACTIVITY: 0
	  }];
	  const dataResult = myPosition > 5 ? [...usersData.slice(0, 3), ...usersData.slice(-1), null] : usersData;
	  dataResult.forEach((userRating, index) => {
	    if (userRating === null) {
	      userList.appendChild(main_core.Tag.render(_t6 || (_t6 = _$2`<div class="system-auth-form__userlist-item --list"></div>`)));
	      return;
	    }
	    let fullName = userRating['ACTIVITY'];
	    let avatarSrc = '';
	    if (this.data['USERS_INFO'][userRating['USER_ID']]) {
	      fullName = [this.data['USERS_INFO'][userRating['USER_ID']]['FULL_NAME'], ': ', userRating['ACTIVITY']].join('');
	      avatarSrc = String(this.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC']).length > 0 ? this.data['USERS_INFO'][userRating['USER_ID']]['AVATAR_SRC'] : null;
	    }
	    const isCurrentUser = String(userRating['USER_ID']) === String(main_core.Loc.getMessage('USER_ID'));
	    if (isCurrentUser) {
	      myActivity = userRating['ACTIVITY'];
	    }
	    userList.appendChild(main_core.Tag.render(_t7 || (_t7 = _$2`
						<div title="${0}" class="system-auth-form__userlist-item ui-icon ui-icon ui-icon-common-user">
							<i ${0}></i>
						</div>
					`), main_core.Text.encode(fullName), avatarSrc ? `style="background-image: url('${encodeURI(avatarSrc)}');background-size: cover;"` : ''));
	  });
	  return {
	    userList,
	    myPosition,
	    range: parseInt(this.data['USERS_RATING']['range']),
	    myActivity
	  };
	}

	let _$3 = t => t,
	  _t$3,
	  _t2$2,
	  _t3$1,
	  _t4$1,
	  _t5$1,
	  _t6$1;
	var _config = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("config");
	var _widget = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("widget");
	var _isActive = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isActive");
	var _isAvailable = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAvailable");
	var _isConfigured = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isConfigured");
	var _getMainButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMainButton");
	var _getBottomButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBottomButton");
	var _getLockIcon = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLockIcon");
	var _showLogoutPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showLogoutPopup");
	var _prepareDescriptionLoginHistory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareDescriptionLoginHistory");
	var _getLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoader");
	var _prepareDateTimeForLoginHistory = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("prepareDateTimeForLoginHistory");
	class UserLoginHistory {
	  constructor(config, widget) {
	    Object.defineProperty(this, _showLogoutPopup, {
	      value: _showLogoutPopup2
	    });
	    Object.defineProperty(this, _getLockIcon, {
	      value: _getLockIcon2
	    });
	    Object.defineProperty(this, _getBottomButton, {
	      value: _getBottomButton2
	    });
	    Object.defineProperty(this, _getMainButton, {
	      value: _getMainButton2
	    });
	    Object.defineProperty(this, _isConfigured, {
	      value: _isConfigured2
	    });
	    Object.defineProperty(this, _isAvailable, {
	      value: _isAvailable2
	    });
	    Object.defineProperty(this, _isActive, {
	      value: _isActive2
	    });
	    Object.defineProperty(this, _config, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _widget, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _config)[_config] = config;
	    babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget] = widget;
	  }
	  handlerLogoutButton() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]()) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showLogoutPopup)[_showLogoutPopup]();
	    }
	  }
	  getContainer() {
	    const showSliderLoginHistory = () => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().close();
	        BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].url, {
	          allowChangeHistory: false
	        });
	      } else if (!babelHelpers.classPrivateFieldLooseBase(this, _isAvailable)[_isAvailable]()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().close();
	        BX.UI.InfoHelper.show("limit_office_login_history");
	      }
	    };
	    let loginHistoryWidget = main_core.Tag.render(_t$3 || (_t$3 = _$3`
				<div class="system-auth-form__item system-auth-form__scope --vertical --padding-sm">
					<div class="system-auth-form__item-container --center ${0}">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image ${0}" onclick="${0}">
								<i></i>
							</div>
						</div>
						<div class="system-auth-form__item-container --center">
							<div class="system-auth-form__item-title ${0}" onclick="${0}">${0}</div>
							${0}
						</div>
						<div class="system-auth-form__item-content">
							${0}
						</div>
					</div>
					<div class="system-auth-form__visited">
					</div>
					${0}
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]() ? '--border' : '', babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]() ? '--history' : '--history-gray', showSliderLoginHistory, babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]() ? '--sm --link' : '--light', showSliderLoginHistory, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_TITLE'), babelHelpers.classPrivateFieldLooseBase(this, _getLockIcon)[_getLockIcon](), babelHelpers.classPrivateFieldLooseBase(this, _getMainButton)[_getMainButton](), babelHelpers.classPrivateFieldLooseBase(this, _getBottomButton)[_getBottomButton](showSliderLoginHistory));
	    const container = loginHistoryWidget.querySelector('.system-auth-form__visited');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]()) {
	      const loader = babelHelpers.classPrivateFieldLooseBase(UserLoginHistory, _getLoader)[_getLoader]();
	      loader.show(container);
	      main_core.ajax.runComponentAction('bitrix:intranet.user.login.history', 'getListLastLogin', {
	        mode: 'class',
	        data: {
	          limit: 1
	        }
	      }).then(response => {
	        loader.hide();
	        const devices = response.data;
	        const keys = Object.keys(devices);
	        keys.forEach(key => {
	          const description = babelHelpers.classPrivateFieldLooseBase(UserLoginHistory, _prepareDescriptionLoginHistory)[_prepareDescriptionLoginHistory](devices[key]['DEVICE_PLATFORM'], devices[key]['GEOLOCATION'], devices[key]['BROWSER']);
	          const time = babelHelpers.classPrivateFieldLooseBase(UserLoginHistory, _prepareDateTimeForLoginHistory)[_prepareDateTimeForLoginHistory](devices[key]['LOGIN_DATE']);
	          const device = main_core.Tag.render(_t2$2 || (_t2$2 = _$3`
						<div class="system-auth-form__visited-item">
							<div data-hint='${0}' class="system-auth-form__visited-icon --${0}" onclick="${0}" data-hint-no-icon></div>
							<script>
								BX.ready(() => {
									BX.UI.Hint.init(document.querySelector(".system-auth-form__visited-icon --${0}"));
								})
							</script>
							<div class="system-auth-form__visited-text" onclick="${0}">${0}</div>
							<div class="system-auth-form__visited-time" onclick="${0}">${0}</div>
						</div>
					`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_THIS_DEVICE_DESCRIPTION'), devices[key]['DEVICE_TYPE'], showSliderLoginHistory, devices[key]['DEVICE_TYPE'], showSliderLoginHistory, description, showSliderLoginHistory, time);
	          main_core.Dom.append(device, container);
	        });
	      }).catch(() => {
	        loader.hide();
	      });
	    }
	    return loginHistoryWidget;
	  }
	}
	function _isActive2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].isAvailableUserLoginHistory && babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].isConfiguredUserLoginHistory;
	}
	function _isAvailable2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].isAvailableUserLoginHistory;
	}
	function _isConfigured2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _config)[_config].isConfiguredUserLoginHistory;
	}
	function _getMainButton2() {
	  const handlerLogoutButton = () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showLogoutPopup)[_showLogoutPopup]();
	  };
	  const showConfigureSlider = () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().close();
	    BX.Helper.show('redirect=detail&code=16615982');
	  };
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]()) {
	    return main_core.Tag.render(_t3$1 || (_t3$1 = _$3`
				<div class="ui-qr-popupcomponentmaker__btn" onclick=\"${0}\">${0}</div>
			`), handlerLogoutButton, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE'));
	  } else if (!babelHelpers.classPrivateFieldLooseBase(this, _isConfigured)[_isConfigured]()) {
	    return main_core.Tag.render(_t4$1 || (_t4$1 = _$3`
				<div class='system-auth-form__settings' onclick="${0}">${0}</div>
			`), showConfigureSlider, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'));
	  }
	  return null;
	}
	function _getBottomButton2(handler) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _isActive)[_isActive]()) {
	    return main_core.Tag.render(_t5$1 || (_t5$1 = _$3`
				<div class="system-auth-form__item-container">
					<div class="system-auth-form__show-history" onclick="${0}">
						${0}
					</div>
				</div>
			`), handler, main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_SHOW_FULL_LIST'));
	  }
	  return null;
	}
	function _getLockIcon2() {
	  const showInfoSlider = () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().close();
	    BX.UI.InfoHelper.show("limit_office_login_history");
	  };
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _isAvailable)[_isAvailable]()) {
	    return main_core.Tag.render(_t6$1 || (_t6$1 = _$3`
				<div class="system-auth-form__item-title-logo --lock" onclick="${0}">
					<i></i>
				</div>
			`), showInfoSlider);
	  }
	  return null;
	}
	function _showLogoutPopup2() {
	  BX.UI.Dialogs.MessageBox.show({
	    message: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_MESSAGE'),
	    title: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_TITLE'),
	    buttons: BX.UI.Dialogs.MessageBoxButtons.YES_CANCEL,
	    minWidth: 400,
	    popupOptions: {
	      contentBackground: 'transparent',
	      autoHide: true,
	      closeByEsc: true,
	      padding: 0,
	      background: '',
	      events: {
	        onShow: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().getPopup().setAutoHide(false);
	        },
	        onPopupClose: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _widget)[_widget].getPopup().getPopup().setAutoHide(true);
	        }
	      }
	    },
	    onYes: messageBox => {
	      main_core.ajax.runComponentAction('bitrix:intranet.user.profile.password', 'logout', {
	        mode: 'ajax'
	      }).then(() => {
	        messageBox.close();
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_WITHOUT_THIS_RESULT'),
	          autoHideDelay: 1800
	        });
	      }).catch(() => {
	        messageBox.close();
	        BX.UI.Notification.Center.notify({
	          content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_HISTORY_BUTTON_LOGOUT_ALL_DEVICE_ERROR'),
	          autoHideDelay: 3600
	        });
	      });
	    }
	  });
	}
	function _prepareDescriptionLoginHistory2(deviceType, geolocation, browser) {
	  const arrayDescription = [];
	  if (browser) {
	    arrayDescription.push(browser);
	  }
	  if (geolocation) {
	    arrayDescription.push(geolocation);
	  }
	  if (deviceType) {
	    arrayDescription.push(deviceType);
	  }
	  return arrayDescription.join(', ');
	}
	function _getLoader2() {
	  return new main_loader.Loader({
	    size: 20,
	    mode: 'inline'
	  });
	}
	function _prepareDateTimeForLoginHistory2(dateTime) {
	  const format = [['-', 'd.m.Y H:i:s'], ['s', 'sago'], ['i', 'iago'], ['H', 'Hago'], ['d', 'dago'], ['m', 'mago']];
	  return ' - ' + BX.date.format(format, new Date(dateTime), new Date());
	}
	Object.defineProperty(UserLoginHistory, _prepareDateTimeForLoginHistory, {
	  value: _prepareDateTimeForLoginHistory2
	});
	Object.defineProperty(UserLoginHistory, _getLoader, {
	  value: _getLoader2
	});
	Object.defineProperty(UserLoginHistory, _prepareDescriptionLoginHistory, {
	  value: _prepareDescriptionLoginHistory2
	});

	let _$4 = t => t,
	  _t$4,
	  _t2$3;
	var _hidden = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hidden");
	var _disabled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("disabled");
	var _cache = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _salaryVacationMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("salaryVacationMenu");
	var _getMenuButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuButton");
	var _getDisabledHintHtml = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDisabledHintHtml");
	class HcmLinkSalaryVacation {
	  static async load() {
	    try {
	      const {
	        SalaryVacationMenu
	      } = await main_core.Runtime.loadExtension('humanresources.hcmlink.salary-vacation-menu');
	      babelHelpers.classPrivateFieldLooseBase(this, _salaryVacationMenu)[_salaryVacationMenu] = new SalaryVacationMenu();
	      await babelHelpers.classPrivateFieldLooseBase(this, _salaryVacationMenu)[_salaryVacationMenu].load();
	      babelHelpers.classPrivateFieldLooseBase(this, _hidden)[_hidden] = babelHelpers.classPrivateFieldLooseBase(this, _salaryVacationMenu)[_salaryVacationMenu].isHidden();
	      babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled] = babelHelpers.classPrivateFieldLooseBase(this, _salaryVacationMenu)[_salaryVacationMenu].isDisabled();
	    } catch (e) {}
	  }
	  static getLayout() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _hidden)[_hidden]) {
	      return null;
	    }
	    const disabled = babelHelpers.classPrivateFieldLooseBase(this, _disabled)[_disabled];
	    return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('hcmLinkSalaryVacationLayout', () => {
	      const layout = main_core.Tag.render(_t$4 || (_t$4 = _$4`
				<div class="system-auth-form__scope system-auth-form__hcmlink ${0}"
					${0}
					data-hint-no-icon
					data-hint-html
					data-hint-interactivity
				>
					<div class="system-auth-form__item-container --flex" style="flex-direction:row;">
						<div class="system-auth-form__item-logo">
							<div class="system-auth-form__item-logo--image --hcmlink">
								<i></i>
							</div>
						</div>
						<div class="system-auth-form__item-title">
							<span>${0}</span>
						</div>
						${0}
					</div>
				</div>
			`), disabled ? '--disabled' : '', disabled ? `data-hint="${babelHelpers.classPrivateFieldLooseBase(this, _getDisabledHintHtml)[_getDisabledHintHtml]()}"` : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION'), babelHelpers.classPrivateFieldLooseBase(HcmLinkSalaryVacation, _getMenuButton)[_getMenuButton]());
	      if (!disabled) {
	        main_core.Dom.addClass(layout, '--clickable');
	        main_core.Event.bind(layout, 'click', () => {
	          var _babelHelpers$classPr;
	          (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _salaryVacationMenu)[_salaryVacationMenu]) == null ? void 0 : _babelHelpers$classPr.show(babelHelpers.classPrivateFieldLooseBase(this, _getMenuButton)[_getMenuButton]());
	        });
	      }
	      return layout;
	    });
	  }
	  static closeWidget() {
	    var _Widget$getInstance;
	    (_Widget$getInstance = Widget.getInstance()) == null ? void 0 : _Widget$getInstance.hide();
	  }
	}
	function _getMenuButton2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache)[_cache].remember('hcmLinkSalaryVacationMenuButton', () => {
	    return main_core.Tag.render(_t2$3 || (_t2$3 = _$4`
				<div class="system-auth-form__btn--hcmlink ui-icon-set --chevron-right"></div>
			`));
	  });
	}
	function _getDisabledHintHtml2() {
	  return main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_HCMLINK_SALARY_VACATION_DISABLED', {
	    '[LINK]': `
				<a target='_self'
					onclick='(() => {
						BX.Intranet.UserProfile.Widget.getInstance()?.hide();
						BX.Helper.show(\`redirect=detail&code=23343028\`);
					})()'
					style='cursor:pointer;'
				>
			`,
	    '[/LINK]': '</a>'
	  });
	}
	Object.defineProperty(HcmLinkSalaryVacation, _getDisabledHintHtml, {
	  value: _getDisabledHintHtml2
	});
	Object.defineProperty(HcmLinkSalaryVacation, _getMenuButton, {
	  value: _getMenuButton2
	});
	Object.defineProperty(HcmLinkSalaryVacation, _hidden, {
	  writable: true,
	  value: true
	});
	Object.defineProperty(HcmLinkSalaryVacation, _disabled, {
	  writable: true,
	  value: false
	});
	Object.defineProperty(HcmLinkSalaryVacation, _cache, {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	});
	Object.defineProperty(HcmLinkSalaryVacation, _salaryVacationMenu, {
	  writable: true,
	  value: void 0
	});

	let _$5 = t => t,
	  _t$5,
	  _t2$4;
	const analyticsContext = {
	  category: 'documents',
	  c_section: 'ava_menu',
	  type: 'from_employee'
	};
	var _b2eEmployeeSignSettings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("b2eEmployeeSignSettings");
	var _container$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _cache$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _getLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLayout");
	var _onCreateDocumentBtnClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("onCreateDocumentBtnClick");
	class SignDocument {
	  static async getPromise(isLocked) {
	    const {
	      B2EEmployeeSignSettings
	    } = await main_core.Runtime.loadExtension('sign.v2.b2e.sign-settings-employee');
	    babelHelpers.classPrivateFieldLooseBase(SignDocument, _b2eEmployeeSignSettings)[_b2eEmployeeSignSettings] = new B2EEmployeeSignSettings(babelHelpers.classPrivateFieldLooseBase(SignDocument, _container$1)[_container$1].id, analyticsContext);
	    try {
	      await HcmLinkSalaryVacation.load();
	    } catch (e) {}
	    return babelHelpers.classPrivateFieldLooseBase(SignDocument, _getLayout)[_getLayout](isLocked);
	  }
	}
	function _getLayout2(isLocked) {
	  const lockedClass = isLocked ? ' --lock' : '';
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$1)[_cache$1].remember('layout', () => {
	    const layout = main_core.Tag.render(_t2$4 || (_t2$4 = _$5`
				<div>
					<div class="system-auth-form__scope system-auth-form__sign">
						<div class="system-auth-form__item-container --flex" style="flex-direction:row;">
							<div class="system-auth-form__item-logo">
								<div class="system-auth-form__item-logo--image --sign">
									<i></i>
								</div>
							</div>
							<div class="system-auth-form__item-title">
								<span>${0}</span>
								<span class="system-auth-form__item-title --link-light --margin-s">
									${0}
								</span>
							</div>
							<div class="system-auth-form__btn--sign ui-popupcomponentmaker__btn --medium --border${0}" onclick="${0}">
								${0}
							</div>
						</div>
					</div>
					${0}
				</div>
			`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_TITLE_HINT'), lockedClass, () => babelHelpers.classPrivateFieldLooseBase(SignDocument, _onCreateDocumentBtnClick)[_onCreateDocumentBtnClick](isLocked), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SIGNDOCUMENT_CREATE_DOCUMENT'), HcmLinkSalaryVacation.getLayout());
	    if (BX.UI.Hint) {
	      BX.UI.Hint.init(layout);
	    }
	    return layout;
	  });
	}
	function _onCreateDocumentBtnClick2(isLocked) {
	  main_core_events.EventEmitter.emit(SignDocument, SignDocument.events.onDocumentCreateBtnClick);
	  if (isLocked) {
	    top.BX.UI.InfoHelper.show('limit_office_e_signature');
	    return;
	  }
	  const container = babelHelpers.classPrivateFieldLooseBase(SignDocument, _container$1)[_container$1];
	  BX.SidePanel.Instance.open('sign-b2e-settings-init-by-employee', {
	    width: 750,
	    cacheable: false,
	    contentCallback: () => {
	      container.innerHTML = '';
	      return container;
	    },
	    events: {
	      onLoad: () => {
	        babelHelpers.classPrivateFieldLooseBase(SignDocument, _b2eEmployeeSignSettings)[_b2eEmployeeSignSettings].clearCache();
	        babelHelpers.classPrivateFieldLooseBase(SignDocument, _b2eEmployeeSignSettings)[_b2eEmployeeSignSettings].render();
	      }
	    }
	  });
	}
	Object.defineProperty(SignDocument, _onCreateDocumentBtnClick, {
	  value: _onCreateDocumentBtnClick2
	});
	Object.defineProperty(SignDocument, _getLayout, {
	  value: _getLayout2
	});
	SignDocument.events = {
	  onDocumentCreateBtnClick: 'onDocumentCreateBtnClick'
	};
	Object.defineProperty(SignDocument, _b2eEmployeeSignSettings, {
	  writable: true,
	  value: void 0
	});
	Object.defineProperty(SignDocument, _container$1, {
	  writable: true,
	  value: main_core.Tag.render(_t$5 || (_t$5 = _$5`<div id="sign-b2e-employee-settings-container"></div>`))
	});
	Object.defineProperty(SignDocument, _cache$1, {
	  writable: true,
	  value: new main_core_cache.MemoryCache()
	});

	let _$6 = t => t,
	  _t$6,
	  _t2$5,
	  _t3$2,
	  _t4$2,
	  _t5$2,
	  _t6$2;
	class Otp {
	  constructor(isSingle = false, config) {
	    this.isSingle = isSingle;
	    this.config = config;
	  }
	  getContainer() {
	    const isInstalled = this.config.IS_ACTIVE === 'Y';
	    const onclick = () => {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	      if (String(this.config.URL).length > 0) {
	        main_core.Uri.addParam(this.config.URL, {
	          page: 'otpConnected'
	        });
	        BX.SidePanel.Instance.open(main_core.Uri.addParam(this.config.URL, {
	          page: 'otpConnected'
	        }), {
	          width: 1100
	        });
	      } else {
	        console.error('Otp page is not defined. Check the component params');
	      }
	    };
	    const button = isInstalled ? main_core.Tag.render(_t$6 || (_t$6 = _$6`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${0}">${0}</div>`), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_TURNED_ON')) : main_core.Tag.render(_t2$5 || (_t2$5 = _$6`<div class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" onclick="${0}">${0}</div>`), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_TURN_ON'));
	    const onclickHelp = () => {
	      top.BX.Helper.show('redirect=detail&code=17728602');
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen');
	    };
	    if (this.isSingle !== true) {
	      return main_core.Tag.render(_t3$2 || (_t3$2 = _$6`
				<div class="system-auth-form__item system-auth-form__scope --padding-bottom-10 ${0}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --authentication"></div>
					</div>
					<div class="system-auth-form__item-container --flex --column --flex-start">
						<div class="system-auth-form__item-title --without-margin --block">
							${0}
							<span class="system-auth-form__icon-help --inline" onclick="${0}"></span>
						</div>
						${0}
						<div class="system-auth-form__item-content --margin-top-auto --center --center-force">
							${0}
						</div>
					</div>
					${0}
				</div>
			`), isInstalled ? ' --active' : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE'), onclickHelp, isInstalled ? main_core.Tag.render(_t4$2 || (_t4$2 = _$6`
								<div class="system-auth-form__item-title --link-dotted" onclick="${0}">${0}</div>
							`), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE')) : '', button, isInstalled ? '' : `
						<div class="system-auth-form__item-new">
							<div class="system-auth-form__item-new--title">${main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
						</div>`);
	    }
	    let menuPopup = null;
	    const popupClick = event => {
	      event.stopPropagation();
	      const items = [{
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_CONFIGURE'),
	        onclick: () => {
	          menuPopup.close();
	          onclick();
	        }
	      }];
	      menuPopup = menuPopup || new main_popup.Menu(`menu-otp-${main_core.Text.getRandom()}`, event.target, items, {
	        className: 'system-auth-form__popup',
	        angle: true,
	        offsetLeft: 10,
	        autoHide: true,
	        events: {
	          onShow: popup => {
	            main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':showOtpMenu', new main_core_events.BaseEvent({
	              data: {
	                popup: popup.target
	              }
	            }));
	          }
	        }
	      });
	      menuPopup.toggle();
	    };
	    return main_core.Tag.render(_t5$2 || (_t5$2 = _$6`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm-all ${0} --vertical --center">
					<div class="system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex">
						<div class="system-auth-form__item-logo--image --authentication"></div>
					</div>
					<div class="system-auth-form__item-container --flex">
						<div class="system-auth-form__item-title --light --center --s">
							${0}
							 <span class="system-auth-form__icon-help" onclick="${0}"></span> 
						</div>
					</div>
					${0}
					<div class="system-auth-form__item-container --flex --column --space-around">
						<div class="system-auth-form__item-content --flex --display-flex">
							${0}
						</div>
					</div>
					${0}
				</div>
		`), isInstalled ? ' --active' : '', main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_MESSAGE'), onclickHelp, isInstalled ? main_core.Tag.render(_t6$2 || (_t6$2 = _$6`<div class="system-auth-form__config --absolute" onclick="${0}"></div>`), popupClick) : '', button, isInstalled ? '' : `
						<div class="system-auth-form__item-new system-auth-form__item-new-icon --ssl">
							<div class="system-auth-form__item-new--title">${main_core.Loc.getMessage('INTRANET_USER_PROFILE_OTP_TITLE')}</div>
						</div>`);
	  }
	}

	let _$7 = t => t,
	  _t$7,
	  _t2$6,
	  _t3$3,
	  _t4$3,
	  _t5$3,
	  _t6$3,
	  _t7$1,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12,
	  _t13,
	  _t14,
	  _t15,
	  _t16;
	const widgetMarker = Symbol('user.widget');
	var _container$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("container");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _profile = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("profile");
	var _features = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("features");
	var _cache$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("cache");
	var _desktopDownloadLinks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("desktopDownloadLinks");
	var _networkProfileUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("networkProfileUrl");
	var _getProfileContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getProfileContainer");
	var _getPopupContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopupContainer");
	var _setEventHandlers = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setEventHandlers");
	var _getb24NetPanelContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getb24NetPanelContainer");
	var _getAdminPanelContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getAdminPanelContainer");
	var _getThemeContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getThemeContainer");
	var _getMaskContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMaskContainer");
	var _getCompanyPulse = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCompanyPulse");
	var _savePhoto = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("savePhoto");
	var _getSignDocument = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSignDocument");
	var _getStressLevel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getStressLevel");
	var _getQrContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getQrContainer");
	var _getDeskTopContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getDeskTopContainer");
	var _getOTPContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getOTPContainer");
	var _getLoginHistoryContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLoginHistoryContainer");
	var _getBindings = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBindings");
	var _getNotificationContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getNotificationContainer");
	var _getLogoutContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLogoutContainer");
	class Widget extends main_core_events.EventEmitter {
	  constructor(container, {
	    profile: {
	      ID,
	      FULL_NAME,
	      PHOTO,
	      MASK,
	      STATUS,
	      STATUS_CODE,
	      URL,
	      WORK_POSITION
	    },
	    component: {
	      componentName,
	      signedParameters
	    },
	    features,
	    desktopDownloadLinks,
	    networkProfileUrl
	  }) {
	    super();
	    Object.defineProperty(this, _getLogoutContainer, {
	      value: _getLogoutContainer2
	    });
	    Object.defineProperty(this, _getNotificationContainer, {
	      value: _getNotificationContainer2
	    });
	    Object.defineProperty(this, _getBindings, {
	      value: _getBindings2
	    });
	    Object.defineProperty(this, _getLoginHistoryContainer, {
	      value: _getLoginHistoryContainer2
	    });
	    Object.defineProperty(this, _getOTPContainer, {
	      value: _getOTPContainer2
	    });
	    Object.defineProperty(this, _getDeskTopContainer, {
	      value: _getDeskTopContainer2
	    });
	    Object.defineProperty(this, _getQrContainer, {
	      value: _getQrContainer2
	    });
	    Object.defineProperty(this, _getStressLevel, {
	      value: _getStressLevel2
	    });
	    Object.defineProperty(this, _getSignDocument, {
	      value: _getSignDocument2
	    });
	    Object.defineProperty(this, _savePhoto, {
	      value: _savePhoto2
	    });
	    Object.defineProperty(this, _getCompanyPulse, {
	      value: _getCompanyPulse2
	    });
	    Object.defineProperty(this, _getMaskContainer, {
	      value: _getMaskContainer2
	    });
	    Object.defineProperty(this, _getThemeContainer, {
	      value: _getThemeContainer2
	    });
	    Object.defineProperty(this, _getAdminPanelContainer, {
	      value: _getAdminPanelContainer2
	    });
	    Object.defineProperty(this, _getb24NetPanelContainer, {
	      value: _getb24NetPanelContainer2
	    });
	    Object.defineProperty(this, _setEventHandlers, {
	      value: _setEventHandlers2
	    });
	    Object.defineProperty(this, _getPopupContainer, {
	      value: _getPopupContainer2
	    });
	    Object.defineProperty(this, _getProfileContainer, {
	      value: _getProfileContainer2
	    });
	    Object.defineProperty(this, _container$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _profile, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _features, {
	      writable: true,
	      value: {}
	    });
	    Object.defineProperty(this, _cache$2, {
	      writable: true,
	      value: new main_core.Cache.MemoryCache()
	    });
	    Object.defineProperty(this, _desktopDownloadLinks, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _networkProfileUrl, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace(Options.eventNameSpace);
	    babelHelpers.classPrivateFieldLooseBase(this, _setEventHandlers)[_setEventHandlers]();
	    babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2] = container;
	    babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile] = {
	      ID,
	      FULL_NAME,
	      PHOTO,
	      MASK,
	      STATUS,
	      STATUS_CODE,
	      URL,
	      WORK_POSITION
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _features)[_features] = features;
	    if (!main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser = main_core.Browser.isLinux() ? 'Linux' : main_core.Browser.isWin() ? 'Windows' : 'MacOs';
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _desktopDownloadLinks)[_desktopDownloadLinks] = desktopDownloadLinks;
	    babelHelpers.classPrivateFieldLooseBase(this, _networkProfileUrl)[_networkProfileUrl] = networkProfileUrl;
	    babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].set('componentParams', {
	      componentName,
	      signedParameters
	    });
	    this.hide = this.hide.bind(this);
	  }
	  toggle() {
	    if (this.getPopup().isShown()) {
	      this.hide();
	    } else {
	      this.show();
	    }
	  }
	  hide() {
	    this.getPopup().close();
	  }
	  show() {
	    this.getPopup().show();
	  }
	  getPopup() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	    }
	    this.emit('init');
	    let signDocument = main_core.Type.isNull(babelHelpers.classPrivateFieldLooseBase(this, _getSignDocument)[_getSignDocument]()) ? null : {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getSignDocument)[_getSignDocument]()
	    };
	    let content = [babelHelpers.classPrivateFieldLooseBase(this, _getProfileContainer)[_getProfileContainer](), babelHelpers.classPrivateFieldLooseBase(this, _getAdminPanelContainer)[_getAdminPanelContainer]() ? {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getAdminPanelContainer)[_getAdminPanelContainer](),
	      backgroundColor: '#fafafa'
	    } : null, signDocument, [{
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getThemeContainer)[_getThemeContainer](),
	      marginBottom: 24,
	      overflow: true,
	      minHeight: '63px'
	    }, {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getMaskContainer)[_getMaskContainer](),
	      backgroundColor: '#fafafa'
	    }], babelHelpers.classPrivateFieldLooseBase(this, _getCompanyPulse)[_getCompanyPulse](!!babelHelpers.classPrivateFieldLooseBase(this, _getStressLevel)[_getStressLevel]()) ? [{
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getCompanyPulse)[_getCompanyPulse](!!babelHelpers.classPrivateFieldLooseBase(this, _getStressLevel)[_getStressLevel]()),
	      overflow: true,
	      marginBottom: 24,
	      flex: babelHelpers.classPrivateFieldLooseBase(this, _getStressLevel)[_getStressLevel]() ? 0.5 : 1,
	      minHeight: babelHelpers.classPrivateFieldLooseBase(this, _getStressLevel)[_getStressLevel]() ? '115px' : '56px'
	    }, babelHelpers.classPrivateFieldLooseBase(this, _getStressLevel)[_getStressLevel]()] : null, babelHelpers.classPrivateFieldLooseBase(this, _getOTPContainer)[_getOTPContainer](babelHelpers.classPrivateFieldLooseBase(this, _getDeskTopContainer)[_getDeskTopContainer]() === null) && babelHelpers.classPrivateFieldLooseBase(this, _getDeskTopContainer)[_getDeskTopContainer]() ? [{
	      flex: 0.5,
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getQrContainer)[_getQrContainer](0.7),
	      minHeight: '190px'
	    }, [{
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getDeskTopContainer)[_getDeskTopContainer](),
	      displayBlock: true
	    }, babelHelpers.classPrivateFieldLooseBase(this, _getOTPContainer)[_getOTPContainer](false)]] : babelHelpers.classPrivateFieldLooseBase(this, _getDeskTopContainer)[_getDeskTopContainer]() || babelHelpers.classPrivateFieldLooseBase(this, _getOTPContainer)[_getOTPContainer](true) ? [{
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getQrContainer)[_getQrContainer](2),
	      flex: 2
	    }, (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _getDeskTopContainer)[_getDeskTopContainer]()) != null ? _babelHelpers$classPr : babelHelpers.classPrivateFieldLooseBase(this, _getOTPContainer)[_getOTPContainer](true)] : babelHelpers.classPrivateFieldLooseBase(this, _getQrContainer)[_getQrContainer](0), babelHelpers.classPrivateFieldLooseBase(this, _getLoginHistoryContainer)[_getLoginHistoryContainer](), {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getBindings)[_getBindings](),
	      backgroundColor: '#fafafa'
	    }, babelHelpers.classPrivateFieldLooseBase(this, _getb24NetPanelContainer)[_getb24NetPanelContainer]() ? {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getb24NetPanelContainer)[_getb24NetPanelContainer](),
	      marginBottom: 24,
	      backgroundColor: '#fafafa'
	    } : null, [{
	      html: (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _getNotificationContainer)[_getNotificationContainer]()) != null ? _babelHelpers$classPr2 : null,
	      backgroundColor: '#fafafa'
	    }, {
	      html: babelHelpers.classPrivateFieldLooseBase(this, _getLogoutContainer)[_getLogoutContainer](),
	      backgroundColor: '#fafafa'
	    }]];
	    const filterFunc = data => {
	      const result = [];
	      if (main_core.Type.isArray(data)) {
	        for (let i = 0; i < data.length; i++) {
	          if (main_core.Type.isArray(data[i])) {
	            const buff = filterFunc(data[i]);
	            if (buff !== null) {
	              if (main_core.Type.isArray(buff) && buff.length === 1) {
	                result.push(buff[0]);
	              } else {
	                result.push(buff);
	              }
	            }
	          } else if (data[i] !== null) {
	            result.push(data[i]);
	          }
	        }
	      }
	      return result.length <= 0 ? null : result.length === 1 ? result[0] : result;
	    };
	    content = filterFunc(content);
	    const prepareFunc = (item, index, array) => {
	      if (main_core.Type.isArray(item)) {
	        return {
	          html: item.map(prepareFunc)
	        };
	      }
	      return {
	        flex: item['flex'] || 0,
	        html: item['html'] || item,
	        backgroundColor: item['backgroundColor'] || null,
	        disabled: item['disabled'] || null,
	        overflow: item['overflow'] || null,
	        marginBottom: item['marginBottom'] || null,
	        displayBlock: item['displayBlock'] || null,
	        minHeight: item['minHeight'] || null,
	        secondary: item['secondary'] || false
	      };
	    };
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new ui_popupcomponentsmaker.PopupComponentsMaker({
	      target: babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2],
	      content: content.map(prepareFunc),
	      width: 400,
	      offsetTop: -14
	    });
	    main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onMenuShow', this.hide);
	    main_core_events.EventEmitter.subscribe(Options.eventNameSpace + 'onNeedToHide', this.hide);
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  static init(node, options) {
	    if (node[widgetMarker]) {
	      return;
	    }
	    const onclick = () => {
	      if (!node['popupSymbol']) {
	        node['popupSymbol'] = new this(node, options);
	      }
	      node['popupSymbol'].toggle();
	      this.instance = node['popupSymbol'];
	    };
	    node[widgetMarker] = true;
	    node.addEventListener('click', onclick);
	  }
	  static getInstance() {
	    return this.instance;
	  }
	}
	function _getProfileContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('profile', () => {
	    const onclick = event => {
	      this.hide();
	      return BX.SidePanel.Instance.open(babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].URL);
	    };
	    let avatar = null;
	    let avatarNode = null;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].STATUS_CODE === 'collaber') {
	      avatar = new ui_avatar.AvatarRoundGuest({
	        size: 36,
	        userpicPath: encodeURI(babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].PHOTO),
	        baseColor: '#19cc45'
	      });
	      avatarNode = avatar.getContainer();
	    } else {
	      avatarNode = main_core.Tag.render(_t$7 || (_t$7 = _$7`
					<span class="system-auth-form__profile-avatar--image"
						${0}>
					</span>
				`), babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].PHOTO ? `
							style="background-size: cover; background-image: url('${encodeURI(babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].PHOTO)}')"` : '');
	    }
	    const nameNode = main_core.Tag.render(_t2$6 || (_t2$6 = _$7`
				<div class="system-auth-form__profile-name">${0}</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].FULL_NAME);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UserProfile:Avatar:changed', ({
	      data: [{
	        url,
	        userId
	      }]
	    }) => {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID > 0 && userId && babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID.toString() === userId.toString()) {
	        babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].PHOTO = url;
	        if (avatar) {
	          avatar.setUserPic(url);
	        }
	      }
	    });
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Intranet.UserProfile:Name:changed', ({
	      data: [{
	        fullName
	      }]
	    }) => {
	      babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].FULL_NAME = fullName;
	      nameNode.innerHTML = fullName;
	      babelHelpers.classPrivateFieldLooseBase(this, _container$2)[_container$2].querySelector('#user-name').innerHTML = fullName;
	    });
	    let workPosition = main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].WORK_POSITION) ? main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].WORK_POSITION) : '';
	    if (babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].STATUS && (babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].STATUS !== 'collaber' || workPosition === '') && main_core.Loc.hasMessage('INTRANET_USER_PROFILE_' + babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].STATUS)) {
	      workPosition = main_core.Loc.getMessage('INTRANET_USER_PROFILE_' + babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].STATUS);
	    }
	    return main_core.Tag.render(_t3$3 || (_t3$3 = _$7`
				<div class="system-auth-form__item system-auth-form__scope --clickable" onclick="${0}">
					<div class="system-auth-form__profile">
						<div class="system-auth-form__profile-avatar">
							${0}
						</div>
						<div class="system-auth-form__profile-content --margin--right">
							${0}
							<div class="system-auth-form__profile-position">${0}</div>
						</div>
						<div class="system-auth-form__profile-controls">
							<span class="ui-qr-popupcomponentmaker__btn --large --border" >
								${0}
							</span>
					 		<!-- <span class="ui-qr-popupcomponentmaker__btn --large --success">any text</span> -->
						</div>
					</div>
				</div>
			`), onclick, avatarNode, nameNode, workPosition, main_core.Loc.getMessage('INTRANET_USER_PROFILE_PROFILE'));
	  });
	}
	function _getPopupContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('popup-container', () => {
	    return this.getPopup().getPopup().getPopupContainer();
	  });
	}
	function _setEventHandlers2() {
	  const autoHideHandler = event => {
	    console.log(event);
	    if (event.data.popup) {
	      setTimeout(() => {
	        main_core.Event.bind(babelHelpers.classPrivateFieldLooseBase(this, _getPopupContainer)[_getPopupContainer](), 'click', () => {
	          event.data.popup.close();
	        });
	      }, 100);
	    }
	  };
	  this.subscribe('init', () => {
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':onOpen', this.hide);
	    this.subscribe('bindings:open', autoHideHandler);
	    main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventNameSpace + ':showOtpMenu', autoHideHandler);
	  });
	}
	function _getb24NetPanelContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('b24netPanel', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['b24netPanel'] !== 'Y') {
	      return null;
	    }
	    return main_core.Tag.render(_t4$3 || (_t4$3 = _$7`
				<a class="system-auth-form__item system-auth-form__scope --center --padding-sm --clickable" href="${0}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --network"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${0}</div>
					</div>
					<div class="system-auth-form__item-container --block">
						<div class="ui-qr-popupcomponentmaker__btn">${0}</div>
					</div>
				</a>
			`), babelHelpers.classPrivateFieldLooseBase(this, _networkProfileUrl)[_networkProfileUrl], main_core.Loc.getMessage('AUTH_PROFILE_B24NET_MSGVER_1'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_GOTO'));
	  });
	}
	function _getAdminPanelContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('adminPanel', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['adminPanel'] !== 'Y') {
	      return null;
	    }
	    return main_core.Tag.render(_t5$3 || (_t5$3 = _$7`
				<a class="system-auth-form__item system-auth-form__scope --center --padding-sm --clickable" href="/bitrix/admin/">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --admin-panel"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${0}</div>
					</div>
					<div class="system-auth-form__item-container --block">
						<div class="ui-qr-popupcomponentmaker__btn">${0}</div>
					</div>
				</a>
			`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_ADMIN_PANEL'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_GOTO'));
	  });
	}
	function _getThemeContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('themePicker', () => {
	    return ThemePicker.getPromise();
	  });
	}
	function _getMaskContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('Mask', () => {
	    return main_core.Tag.render(_t6$3 || (_t6$3 = _$7`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --mask"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title">
							<span>${0}</span>
							<span style="cursor: default" class="system-auth-form__icon-help"></span>
						</div>
						<div class="system-auth-form__item-content --center --center-force">
							<div class="ui-qr-popupcomponentmaker__btn --disabled">${0}</div>
						</div>
					</div>
					<div class="system-auth-form__item-new --soon">
						<div class="system-auth-form__item-new--title">${0}</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MASKS'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'), main_core.Loc.getMessage('INTRANET_USER_PROFILE_SOON'));
	  });
	}
	function _getCompanyPulse2(isNarrow) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getCompanyPulse', () => {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].pulse === 'Y' && babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID > 0 && babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID === main_core.Loc.getMessage('USER_ID')) {
	      return new Promise(resolve => {
	        main_core.ajax.runComponentAction('bitrix:intranet.user.profile.button', 'getUserStatComponent', {
	          mode: 'class'
	        }).then(response => {
	          BX.Runtime.html(null, response.data.html).then(() => {
	            var _babelHelpers$classPr3;
	            resolve(Ustat.getPromise({
	              userId: babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID,
	              isNarrow,
	              data: (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['pulseData']) != null ? _babelHelpers$classPr3 : null
	            }));
	          });
	        });
	      });
	    }
	    return null;
	  });
	}
	function _savePhoto2(dataObj) {
	  main_core.ajax.runComponentAction(babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('componentParams').componentName, 'loadPhoto', {
	    signedParameters: babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('componentParams').signedParameters,
	    mode: 'ajax',
	    data: dataObj
	  }).then(function (response) {
	    if (response.data) {
	      (top || window).BX.onCustomEvent('BX.Intranet.UserProfile:Avatar:changed', [{
	        url: response.data,
	        userId: babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID
	      }]);
	    }
	  }.bind(this), function (response) {
	    console.log('response: ', response);
	  }.bind(this));
	}
	function _getSignDocument2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['signDocument']['available'] !== 'Y') {
	    return null;
	  }
	  const isLocked = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['signDocument']['locked'] === 'Y';
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getSignDocument', () => {
	    main_core_events.EventEmitter.subscribe(SignDocument, SignDocument.events.onDocumentCreateBtnClick, () => this.hide());
	    return SignDocument.getPromise(isLocked);
	  });
	}
	function _getStressLevel2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['stressLevel'] !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getStressLevel', () => {
	    var _babelHelpers$classPr4;
	    return StressLevel.getPromise({
	      signedParameters: babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('componentParams').signedParameters,
	      componentName: babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].get('componentParams').componentName,
	      userId: babelHelpers.classPrivateFieldLooseBase(this, _profile)[_profile].ID,
	      data: (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['stressLevelData']) != null ? _babelHelpers$classPr4 : null
	    });
	  });
	}
	function _getQrContainer2(flex) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getQrContainer', () => {
	    return new Promise((resolve, reject) => {
	      BX.loadExt(['ui.qrauthorization', 'qrcode']).then(() => {
	        const onclick = () => {
	          this.hide();
	          new ui_qrauthorization.QrAuthorization({
	            title: main_core.Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_TITLE2'),
	            content: main_core.Loc.getMessage('INTRANET_USER_PROFILE_QRCODE_BODY2'),
	            intent: 'profile'
	          }).show();
	        };
	        const onclickHelp = event => {
	          top.BX.Helper.show('redirect=detail&code=22218950');
	          this.hide();
	          event.preventDefault();
	          event.stopPropagation();
	          return false;
	        };
	        let node;
	        if (flex !== 2 && flex !== 0) {
	          // for a small size
	          node = main_core.Tag.render(_t7$1 || (_t7$1 = _$7`
					<div class="system-auth-form__item system-auth-form__scope" style="padding: 10px 14px">
						<div class="system-auth-form__item-container --center --column --center">
							<div class="system-auth-form__item-title --center --margin-xl">${0}</div>
							<div class="system-auth-form__qr" style="margin-bottom: 12px">
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --border" style="margin-top: auto" onclick="${0}">${0}</div>
						</div>
						<div class="system-auth-form__icon-help --absolute" onclick="${0}" title="${0}"></div>
					</div>
				`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR_SMALL'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'));
	        } else if (flex === 0) {
	          //full size
	          node = main_core.Tag.render(_t8 || (_t8 = _$7`
					<div class="system-auth-form__item system-auth-form__scope --padding-qr-xl">
						<div class="system-auth-form__item-container --column --flex --flex-start">
							<div class="system-auth-form__item-title --l">${0}</div>
							<div class="system-auth-form__item-title --link-dotted" onclick="${0}">
								${0}
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --large --border" style="margin-top: auto" onclick="${0}">
								${0}
							</div>
						</div>
						<div class="system-auth-form__item-container --qr">
							<div class="system-auth-form__qr --full-size"></div>
						</div>
					</div>
				`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR'));
	        } else {
	          // for flex 2. It is kind of middle
	          node = main_core.Tag.render(_t9 || (_t9 = _$7`
					<div class="system-auth-form__item system-auth-form__scope --padding-mid-qr">
						<div class="system-auth-form__item-container --column --flex --flex-start">
							<div class="system-auth-form__item-title --block">
								${0}
								<span class="system-auth-form__icon-help --inline" onclick="${0}" title="${0}"></span>
							</div>
							<div class="ui-qr-popupcomponentmaker__btn --border" style="margin-top: auto" onclick="${0}">${0}</div>
						</div>
						<div class="system-auth-form__item-container --qr">
							<div class="system-auth-form__qr --size-2"></div>
						</div>
					</div>
				`), main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_TITLE2_SMALL'), onclickHelp, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_HOW_DOES_IT_WORK'), onclick, main_core.Loc.getMessage('INTRANET_USER_PROFILE_MOBILE_SHOW_QR'));
	        }
	        return resolve(node);
	      }).catch(reject);
	    });
	  });
	}
	function _getDeskTopContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getDeskTopContainer', () => {
	    let isInstalled = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['appInstalled']['APP_MAC_INSTALLED'] === 'Y';
	    let cssPostfix = '--apple';
	    let title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_APPLE');
	    let linkToDistributive = babelHelpers.classPrivateFieldLooseBase(this, _desktopDownloadLinks)[_desktopDownloadLinks].macos;
	    const typesInstallersForLinux = {
	      'DEB': {
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_DEB'),
	        href: babelHelpers.classPrivateFieldLooseBase(this, _desktopDownloadLinks)[_desktopDownloadLinks].linuxDeb
	      },
	      'RPM': {
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD_LINUX_RPM'),
	        href: babelHelpers.classPrivateFieldLooseBase(this, _desktopDownloadLinks)[_desktopDownloadLinks].linuxRpm
	      }
	    };
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser === 'Windows') {
	      isInstalled = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['appInstalled']['APP_WINDOWS_INSTALLED'] === 'Y';
	      cssPostfix = '--windows';
	      title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_WINDOWS');
	      linkToDistributive = babelHelpers.classPrivateFieldLooseBase(this, _desktopDownloadLinks)[_desktopDownloadLinks].windows;
	    }
	    let onclick = isInstalled ? event => {
	      event.preventDefault();
	      event.stopPropagation();
	      return false;
	    } : () => {
	      this.hide();
	      return true;
	    };
	    let menuLinux = null;
	    const showMenuLinux = event => {
	      event.preventDefault();
	      menuLinux = menuLinux || new main_popup.Menu({
	        className: 'system-auth-form__popup',
	        bindElement: event.target,
	        items: [{
	          text: typesInstallersForLinux.DEB.text,
	          href: typesInstallersForLinux.DEB.href,
	          onclick: () => {
	            menuLinux.close();
	          }
	        }, {
	          text: typesInstallersForLinux.RPM.text,
	          href: typesInstallersForLinux.RPM.href,
	          onclick: () => {
	            menuLinux.close();
	          }
	        }],
	        angle: true,
	        offsetLeft: 10,
	        events: {
	          onShow: () => {
	            this.getPopup().getPopup().setAutoHide(false);
	          },
	          onClose: () => {
	            this.getPopup().getPopup().setAutoHide(true);
	          }
	        }
	      });
	      menuLinux.toggle();
	    };
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser === 'Linux') {
	      isInstalled = babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['appInstalled']['APP_LINUX_INSTALLED'] === 'Y';
	      cssPostfix = '--linux';
	      title = main_core.Loc.getMessage('INTRANET_USER_PROFILE_DESKTOP_LINUX');
	      linkToDistributive = '';
	      onclick = isInstalled ? event => {
	        event.preventDefault();
	        event.stopPropagation();
	        return false;
	      } : showMenuLinux;
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['otp'].IS_ENABLED !== 'Y') {
	      let menuPopup = null;
	      let menuItems = [{
	        text: main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD'),
	        href: linkToDistributive,
	        onclick: () => {
	          menuPopup.close();
	          this.hide();
	        }
	      }];
	      if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser === 'Linux') {
	        menuItems = [{
	          text: typesInstallersForLinux.DEB.text,
	          href: typesInstallersForLinux.DEB.href,
	          onclick: () => {
	            menuPopup.close();
	          }
	        }, {
	          text: typesInstallersForLinux.RPM.text,
	          href: typesInstallersForLinux.RPM.href,
	          onclick: () => {
	            menuPopup.close();
	          }
	        }];
	      }
	      const popupClick = event => {
	        menuPopup = menuPopup || new main_popup.Menu({
	          className: 'system-auth-form__popup',
	          bindElement: event.target,
	          items: menuItems,
	          angle: true,
	          offsetLeft: 10,
	          events: {
	            onShow: () => {
	              this.getPopup().getPopup().setAutoHide(false);
	            },
	            onClose: () => {
	              this.getPopup().getPopup().setAutoHide(true);
	            }
	          }
	        });
	        menuPopup.toggle();
	      };
	      return main_core.Tag.render(_t10 || (_t10 = _$7`
					<div data-role="desktop-item" class="system-auth-form__item system-auth-form__scope --padding-sm-all ${0} --vertical --center">
						<div class="system-auth-form__item-logo --margin-bottom --center system-auth-form__item-container --flex">
							<div class="system-auth-form__item-logo--image ${0}"></div>
						</div>
						${0}
						<div class="system-auth-form__item-container --flex --center --display-flex">
							<div class="system-auth-form__item-title --light --center --s">${0}</div>
						</div>
						<div class="system-auth-form__item-content --flex --center --display-flex">
							<a class="ui-qr-popupcomponentmaker__btn" style="margin-top: auto" href="${0}" target="_blank" onclick="${0}">
								${0}
							</a>
						</div>
					</div>
				`), isInstalled ? ' --active' : '', cssPostfix, isInstalled ? main_core.Tag.render(_t11 || (_t11 = _$7`<div class="system-auth-form__config --absolute" onclick="${0}"></div>`), popupClick) : '', title, linkToDistributive, onclick, isInstalled ? main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'));
	    }
	    const getLinkForHiddenState = () => {
	      const link = main_core.Tag.render(_t12 || (_t12 = _$7`
					<a href="${0}" class="system-auth-form__item-title --link-dotted">${0}</a>
				`), linkToDistributive, main_core.Loc.getMessage('INTRANET_USER_PROFILE_DOWNLOAD'));
	      if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].browser === 'Linux') {
	        link.addEventListener('click', showMenuLinux);
	      }
	      return link;
	    };
	    return main_core.Tag.render(_t13 || (_t13 = _$7`
				<div class="system-auth-form__item system-auth-form__scope --padding-bottom-10 ${0}">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image ${0}"></div>
					</div>
					<div class="system-auth-form__item-container">
						<div class="system-auth-form__item-title ${0}">${0}</div>
						${0}
						<div class="system-auth-form__item-content --center --center-force">
							<a class="ui-qr-popupcomponentmaker__btn" href="${0}" target="_blank" onclick="${0}">
								${0}
							</a>
						</div>
					</div>
				</div>
			`), isInstalled ? ' --active' : '', cssPostfix, isInstalled ? ' --without-margin' : '--min-height', title, isInstalled ? getLinkForHiddenState() : '', linkToDistributive, onclick, isInstalled ? main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALLED') : main_core.Loc.getMessage('INTRANET_USER_PROFILE_INSTALL'));
	  });
	}
	function _getOTPContainer2(single) {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].otp.IS_ENABLED !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getOTPContainer', () => {
	    return new Otp(single, babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].otp).getContainer();
	  });
	}
	function _getLoginHistoryContainer2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].loginHistory.isHide) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getLoginHistoryContainer', () => {
	    const history = new UserLoginHistory(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features].loginHistory, this);
	    return {
	      html: history.getContainer(),
	      backgroundColor: '#fafafa'
	    };
	  });
	}
	function _getBindings2() {
	  if (!(main_core.Type.isPlainObject(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']) && main_core.Type.isStringFilled(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']['text']) && main_core.Type.isArray(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']['items']) && babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']['items'].length > 0)) {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getBindingsContainer', () => {
	    const div = main_core.Tag.render(_t14 || (_t14 = _$7`
				<div class="system-auth-form__item --hover system-auth-form__scope --center --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --binding"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${0}</div>
					</div>
					<div data-role="arrow" class="system-auth-form__item-icon --arrow-right"></div>
				</div>
			`), main_core.Text.encode(babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']['text']));
	    div.addEventListener('click', () => {
	      this.__bindingsMenu = this.__bindingsMenu || new main_popup.Menu({
	        className: 'system-auth-form__popup',
	        bindElement: div.querySelector('[data-role="arrow"]'),
	        items: babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['bindings']['items'],
	        angle: true,
	        cachable: false,
	        offsetLeft: 10,
	        events: {
	          onShow: () => {
	            this.emit('bindings:open');
	          }
	        }
	      });
	      this.__bindingsMenu.toggle();
	    });
	    return div;
	  });
	}
	function _getNotificationContainer2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _features)[_features]['im'] !== 'Y') {
	    return null;
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getNotificationContainer', () => {
	    const div = main_core.Tag.render(_t15 || (_t15 = _$7`
				<div class="system-auth-form__item --hover system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --notification"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${0}</div>
					</div>
				</div>
			`), main_core.Loc.getMessage('AUTH_NOTIFICATION'));
	    div.addEventListener('click', () => {
	      this.hide();
	      BXIM.openSettings({
	        'onlyPanel': 'notify'
	      });
	    });
	    return div;
	  });
	}
	function _getLogoutContainer2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _cache$2)[_cache$2].remember('getLogoutContainer', () => {
	    const onclickLogout = () => {
	      if (!main_core.Type.isNil(im_v2_lib_desktopApi.DesktopApi) && im_v2_lib_desktopApi.DesktopApi.isDesktop()) {
	        im_v2_lib_desktopApi.DesktopApi.logout();
	      } else {
	        const backUrl = new main_core.Uri(window.location.pathname);
	        backUrl.removeQueryParam(['logout', 'login', 'back_url_pub', 'user_lang']);
	        const newUrl = new main_core.Uri('/auth/?logout=yes');
	        newUrl.setQueryParam('sessid', BX.bitrix_sessid());
	        newUrl.setQueryParam('backurl', encodeURIComponent(backUrl.toString()));
	        document.location.href = newUrl;
	      }
	    };

	    //TODO
	    return main_core.Tag.render(_t16 || (_t16 = _$7`
				<div class="system-auth-form__item system-auth-form__scope --padding-sm">
					<div class="system-auth-form__item-logo">
						<div class="system-auth-form__item-logo--image --logout"></div>
					</div>
					<div class="system-auth-form__item-container --center">
						<div class="system-auth-form__item-title --light">${0}</div>
					</div>
					<a onclick="${0}" class="system-auth-form__item-link-all"></a>
				</div>
			`), main_core.Loc.getMessage('AUTH_LOGOUT'), onclickLogout);
	  });
	}
	Widget.instance = null;

	exports.Widget = Widget;

}((this.BX.Intranet.UserProfile = this.BX.Intranet.UserProfile || {}),BX.UI,BX.UI,BX,BX,BX,BX.Cache,BX.UI,BX,BX.Main,BX.Event,BX.Messenger.v2.Lib));
//# sourceMappingURL=script.js.map
