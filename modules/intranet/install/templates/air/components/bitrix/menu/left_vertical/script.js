/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_buttons,main_core_event,ui_cnt,main_popup,main_core_events,ui_dialogs_messagebox,ui_bannerDispatcher,ui_analytics,main_core,main_core_cache,main_sidepanel) {
	'use strict';

	class Options {
	  static eventName(name) {
	    return ['BX.Intranet.LeftMenu:', ...(main_core.Type.isStringFilled(name) ? [name] : name)].join(':');
	  }
	}
	Options.version = '2021.10';
	Options.eventNameSpace = 'BX.Intranet.LeftMenu:';
	Options.isExtranet = false;
	Options.isAdmin = false;
	Options.isCustomPresetRestricted = false;
	Options.settingsPath = null;
	Options.isMainPageEnabled = false;
	Options.availablePresetTools = null;
	Options.inviteDialogLink = null;
	Options.showMarta = null;
	Options.showSitemapMenuItem = null;

	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	class DefaultController {
	  constructor(container, {
	    events
	  }) {
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    this.container = container;
	    if (events) {
	      Array.from(Object.keys(events)).forEach(key => {
	        main_core_events.EventEmitter.subscribe(this, Options.eventName(key), events[key]);
	      });
	    }
	  }
	  getContainer() {
	    return this.container;
	  }
	  createPopup() {}
	  getPopup() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  show() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = this.createPopup(...arguments);
	      main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup], 'onClose', () => {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onClose'));
	      });
	      main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup], 'onShow', () => {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onShow'));
	      });
	      main_core_events.EventEmitter.subscribe(babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup], 'onDestroy', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].show();
	  }
	  hide() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	    }
	  }
	}

	let _ = t => t,
	  _t;
	class PresetCustomController extends DefaultController {
	  constructor(...args) {
	    super(...args);
	    this.isReady = true;
	  }
	  createPopup() {
	    const form = main_core.Tag.render(_t || (_t = _`
			<form id="customPresetForm" style="min-width: 350px;">
				<div style="margin: 15px 0 15px 9px;">
					<input type="radio" name="userScope" id="customPresetCurrentUser" value="currentUser">
					<label for="customPresetCurrentUser">${0}</label>
				</div>
				<div style="margin: 0 0 38px 9px;">
					<input type="radio" name="userScope" id="customPresetNewUser" value="newUser" checked>
					<label for="customPresetNewUser">${0}</label>
				</div>
				<hr style="background-color: #edeef0; border: none; color:  #edeef0; height: 1px;">
			</form>
		`), main_core.Loc.getMessage('MENU_CUSTOM_PRESET_CURRENT_USER'), main_core.Loc.getMessage('MENU_CUSTOM_PRESET_NEW_USER'));
	    return main_popup.PopupManager.create('custom-preset-form-popup', null, {
	      overlay: true,
	      contentColor: 'white',
	      contentNoPaddings: true,
	      lightShadow: true,
	      draggable: {
	        restrict: true
	      },
	      closeByEsc: true,
	      titleBar: main_core.Loc.getMessage('MENU_CUSTOM_PRESET_POPUP_TITLE'),
	      offsetTop: 1,
	      offsetLeft: 20,
	      cacheable: false,
	      closeIcon: true,
	      content: form,
	      buttons: [new ui_buttons.SaveButton({
	        onclick: button => {
	          if (this.isReady === false) {
	            return;
	          }
	          button.setWaiting(true);
	          this.isReady = false;
	          main_core_events.EventEmitter.emit(this, Options.eventName('onPresetIsSet'), form.elements.userScope.value === 'newUser').forEach(promise => {
	            promise.then(() => {
	              button.setWaiting(false);
	              this.hide();
	              main_popup.PopupManager.create('menu-custom-preset-success-popup', null, {
	                closeIcon: true,
	                contentColor: 'white',
	                titleBar: main_core.Loc.getMessage('MENU_CUSTOM_PRESET_POPUP_TITLE'),
	                content: main_core.Loc.getMessage('MENU_CUSTOM_PRESET_SUCCESS')
	              }).show();
	            }).catch(() => {
	              console.log('Error!!');
	            });
	          });
	          this.isReady = true;
	        }
	      }), new ui_buttons.CancelButton({
	        onclick: () => {
	          this.hide();
	        }
	      })]
	    });
	  }
	}

	var _curPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("curPage");
	var _curUri = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("curUri");
	class Utils {
	  static getCurPage() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _curPage)[_curPage] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _curPage)[_curPage] = document.location.pathname + document.location.search;
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _curPage)[_curPage] === '' ? null : babelHelpers.classPrivateFieldLooseBase(this, _curPage)[_curPage];
	  }
	  static getCurUri() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _curUri)[_curUri] === null) {
	      babelHelpers.classPrivateFieldLooseBase(this, _curUri)[_curUri] = new main_core.Uri(document.location.href);
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _curUri)[_curUri];
	  }
	  static catchError(response) {
	    main_core.Runtime.loadExtension('ui.notification').then(() => {
	      const notificationCenter = main_core.Reflection.getClass('BX.UI.Notification.Center');
	      notificationCenter.notify({
	        content: [main_core.Loc.getMessage('MENU_ERROR_OCCURRED'), response.errors ? `: ${response.errors[0].message}` : ''].join(' '),
	        position: 'bottom-left',
	        category: 'menu-self-item-popup',
	        autoHideDelay: 3000
	      });
	    }).catch(() => {
	      console.log('LeftMenu: cannot load ui.notification.');
	    });
	  }
	  static refineUrl(originUrl) {
	    let url = String(originUrl).trim();
	    if (url !== '') {
	      if (!/^https?:\/\//i.test(url) && !/^\//i.test(url)) {
	        // for external links like "google.com" (without a protocol)
	        url = `https://${url}`;
	      } else {
	        const link = document.createElement('a');
	        link.href = url;
	        if (document.location.host === link.host) {
	          // http://portal.com/path/ => /path/
	          url = link.pathname + link.search + link.hash;
	        }
	      }
	    }
	    return url;
	  }
	}
	Object.defineProperty(Utils, _curPage, {
	  writable: true,
	  value: null
	});
	Object.defineProperty(Utils, _curUri, {
	  writable: true,
	  value: null
	});

	var _unavailableToolPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("unavailableToolPopup");
	var _mode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("mode");
	class PresetDefaultController extends DefaultController {
	  constructor(...args) {
	    super(...args);
	    this.isReady = true;
	    Object.defineProperty(this, _unavailableToolPopup, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _mode, {
	      writable: true,
	      value: void 0
	    });
	  }
	  createPopup(mode) {
	    let button;
	    babelHelpers.classPrivateFieldLooseBase(this, _mode)[_mode] = mode;
	    const content = document.querySelector('#left-menu-preset-popup').cloneNode(true);
	    return main_popup.PopupManager.create(this.constructor.name.toString(), null, {
	      overlay: true,
	      contentColor: "white",
	      contentNoPaddings: true,
	      lightShadow: true,
	      draggable: {
	        restrict: true
	      },
	      closeByEsc: true,
	      offsetTop: 1,
	      offsetLeft: 20,
	      cacheable: false,
	      closeIcon: true,
	      content: content,
	      events: {
	        onFirstShow: () => {
	          [...content.querySelectorAll('.js-left-menu-preset-item')].forEach(node => {
	            node.addEventListener('click', () => {
	              [...content.querySelectorAll('.js-left-menu-preset-item')].forEach(otherNode => {
	                otherNode.classList[otherNode === node ? 'add' : 'remove']('left-menu-popup-selected');
	              });
	            });
	          });
	        }
	      },
	      buttons: [button = new ui_buttons.CreateButton({
	        text: main_core.Loc.getMessage('MENU_CONFIRM_BUTTON'),
	        onclick: () => {
	          if (button.isWaiting()) {
	            return;
	          }
	          button.setWaiting(true);
	          const currentPreset = this.getSelectedPreset();
	          if (!Options.isAdmin && Options.availablePresetTools && Options.availablePresetTools[currentPreset] === false) {
	            button.setWaiting(false);
	            this.showUnavailableToolPopup();
	            return;
	          }
	          main_core_events.EventEmitter.emit(this, Options.eventName('onPresetIsSet'), {
	            presetId: currentPreset,
	            mode
	          }).forEach(promise => {
	            promise.then(response => {
	              button.setWaiting(false);
	              this.hide();
	              if (response.data.hasOwnProperty("url")) {
	                document.location.href = response.data.url;
	              } else {
	                document.location.reload();
	              }
	            }).catch(Utils.catchError);
	          });
	        }
	      }), new ui_buttons.CancelButton({
	        text: main_core.Loc.getMessage('MENU_DELAY_BUTTON'),
	        onclick: () => {
	          main_core_events.EventEmitter.emit(this, Options.eventName('onPresetIsPostponed'), {
	            mode
	          });
	          this.hide();
	        }
	      })]
	    });
	  }
	  getMode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _mode)[_mode];
	  }
	  getSelectedPreset() {
	    let currentPreset = '';
	    if (document.forms['left-menu-preset-form']) {
	      [...document.forms['left-menu-preset-form'].elements['presetType']].forEach(node => {
	        if (node.checked) {
	          currentPreset = node.value;
	        }
	      });
	    }
	    return currentPreset;
	  }
	  showUnavailableToolPopup() {
	    if (!(babelHelpers.classPrivateFieldLooseBase(this, _unavailableToolPopup)[_unavailableToolPopup] instanceof ui_dialogs_messagebox.MessageBox)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _unavailableToolPopup)[_unavailableToolPopup] = ui_dialogs_messagebox.MessageBox.create({
	        message: main_core.Loc.getMessage('MENU_UNAVAILABLE_TOOL_POPUP_DESCRIPTION'),
	        buttons: ui_dialogs_messagebox.MessageBoxButtons.OK
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _unavailableToolPopup)[_unavailableToolPopup].show();
	  }
	}

	var _getMenuItems = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItems");
	var _getMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getMenuItem");
	class SettingsController extends DefaultController {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _getMenuItem, {
	      value: _getMenuItem2
	    });
	    Object.defineProperty(this, _getMenuItems, {
	      value: _getMenuItems2
	    });
	    this.menuId = 'leftMenuSettingsPopup';
	  }
	  createPopup() {
	    const menu = new main_popup.Menu({
	      bindElement: this.container,
	      targetContainer: document.querySelector('.js-app__left-menu'),
	      items: this.getItems(),
	      angle: true,
	      offsetTop: 0,
	      offsetLeft: 50
	    });
	    return menu.getPopupWindow();
	  }
	  getItems() {
	    const notHandledMenuItems = main_core_events.EventEmitter.emit(this, Options.eventName('onGettingSettingMenuItems'));
	    return babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems]([...notHandledMenuItems][0]);
	  }
	}
	function _getMenuItems2(items) {
	  if (main_core.Type.isArray(items) === false) {
	    return [];
	  }
	  return items.map(item => {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getMenuItem)[_getMenuItem](item);
	  });
	}
	function _getMenuItem2(data) {
	  if (!main_core.Type.isPlainObject(data)) {
	    return null;
	  }
	  const {
	    text,
	    html,
	    onclick,
	    className,
	    items = [],
	    delimiter = false
	  } = data;
	  return {
	    html,
	    text: html ? undefined : text,
	    items: babelHelpers.classPrivateFieldLooseBase(this, _getMenuItems)[_getMenuItems](items),
	    delimiter,
	    className: `menu-popup-no-icon ${className}`,
	    onclick: (event, item) => {
	      var _item$getMenuWindow$g, _item$getMenuWindow$g2;
	      if (!main_core.Type.isArrayFilled(items)) {
	        item.getMenuWindow().close();
	      }
	      (_item$getMenuWindow$g = item.getMenuWindow().getParentMenuItem()) == null ? void 0 : (_item$getMenuWindow$g2 = _item$getMenuWindow$g.getMenuWindow()) == null ? void 0 : _item$getMenuWindow$g2.close();
	      if (onclick) {
	        onclick(event, item);
	      }
	    }
	  };
	}

	class Backend {
	  static toggleMenu(collapse) {
	    if (main_core.Loc.getMessage('USER_ID') <= 0) {
	      return;
	    }
	    const siteDir = main_core.Loc.getMessage('SITE_DIR') || '/';
	    const context = window.location.pathname.toString().startsWith(`${siteDir}online/`) ? 'online' : '';
	    return main_core.ajax.runAction(`intranet.leftmenu.${collapse ? "collapseMenu" : "expandMenu"}`, {
	      data: {
	        context
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: collapse ? "collapseMenu" : "expandMenu"
	        }
	      }
	    });
	  }
	  static saveSelfItemMenu(itemData) {
	    const action = itemData.id > 0 ? "update" : "add";
	    return main_core.ajax.runAction(`intranet.leftmenu.${action}SelfItem`, {
	      data: {
	        itemData: itemData
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'selfItemAddOrUpdate'
	        }
	      }
	    });
	  }
	  static deleteSelfITem(id) {
	    return main_core.ajax.runAction('intranet.leftmenu.deleteSelfItem', {
	      data: {
	        menuItemId: id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'selfItemDelete'
	        }
	      }
	    });
	  }
	  static addFavoritesItemMenu(itemData) {
	    return main_core.ajax.runAction('intranet.leftmenu.addStandartItem', {
	      data: {
	        itemData: itemData
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'standardItemAdd'
	        }
	      }
	    });
	  }
	  static deleteFavoritesItemMenu(itemData) {
	    return main_core.ajax.runAction('intranet.leftmenu.deleteStandartItem', {
	      data: {
	        itemData: itemData
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'standardItemDelete'
	        }
	      }
	    });
	  }
	  static updateFavoritesItemMenu(itemData) {
	    return main_core.ajax.runAction('intranet.leftmenu.updateStandartItem', {
	      data: {
	        itemText: itemData.text,
	        itemId: itemData.id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'standardItemUpdate'
	        }
	      }
	    });
	  }
	  static addAdminSharedItemMenu(itemData) {
	    return main_core.ajax.runAction('intranet.leftmenu.addItemToAll', {
	      data: {
	        itemInfo: itemData
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'adminItemAdd'
	        }
	      }
	    });
	  }
	  static deleteAdminSharedItemMenu(id) {
	    return main_core.ajax.runAction('intranet.leftmenu.deleteItemFromAll', {
	      data: {
	        menu_item_id: id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'adminItemDelete'
	        }
	      }
	    });
	  }
	  static saveItemsSort(menuItems, firstItemLink, analyticsLabel) {
	    return main_core.ajax.runAction('intranet.leftmenu.saveItemsSort', {
	      data: {
	        items: menuItems,
	        firstItemLink: firstItemLink,
	        version: Options.version
	      },
	      analyticsLabel: {
	        leftmenu: analyticsLabel
	      }
	    });
	  }
	  static setFirstPage(firstPageLink) {
	    return main_core.ajax.runAction('intranet.leftmenu.setFirstPage', {
	      data: {
	        firstPageUrl: firstPageLink
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'mainPageIsSet'
	        }
	      }
	    });
	  }
	  static setDefaultPreset() {
	    return main_core.ajax.runAction('intranet.leftmenu.setDefaultMenu', {
	      data: {},
	      analyticsLabel: {
	        leftmenu: {
	          action: 'defaultMenuIsSet'
	        }
	      }
	    });
	  }
	  static setCustomPreset(forNewUsersOnly, itemsSort, customItems, firstItemLink) {
	    return main_core.ajax.runAction('intranet.leftmenu.saveCustomPreset', {
	      data: {
	        userApply: forNewUsersOnly === true ? 'newUser' : 'currentUser',
	        itemsSort: itemsSort,
	        customItems: customItems,
	        firstItemLink: firstItemLink,
	        version: Options.version
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'customPresetIsSet'
	        }
	      }
	    });
	  }
	  static deleteCustomItem(id) {
	    return main_core.ajax.runAction('intranet.leftmenu.deleteCustomItemFromAll', {
	      data: {
	        menu_item_id: id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'customItemDelete'
	        }
	      }
	    });
	  }
	  static setSystemPreset(mode, presetId) {
	    return main_core.ajax.runAction('intranet.leftmenu.setPreset', {
	      data: {
	        preset: presetId,
	        mode: mode === 'global' ? 'global' : 'personal'
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'systemPresetIsSet',
	          presetId: presetId,
	          mode: mode,
	          analyticsFirst: mode === 'global' ? 'y' : 'n'
	        }
	      }
	    });
	  }
	  static postponeSystemPreset(mode) {
	    return main_core.ajax.runAction('intranet.leftmenu.delaySetPreset', {
	      data: {},
	      analyticsLabel: {
	        leftmenu: {
	          action: 'systemPresetIsPostponed',
	          mode: mode,
	          analyticsFirst: mode === 'global' ? 'y' : 'n'
	        }
	      }
	    });
	  }
	  static clearCache() {
	    return main_core.ajax.runAction('intranet.leftmenu.clearCache', {
	      data: {},
	      analyticsLabel: {
	        leftmenu: {
	          action: 'clearCache'
	        }
	      }
	    });
	  }
	  static expandGroup(id) {
	    if (main_core.Loc.getMessage('USER_ID') <= 0) {
	      return;
	    }
	    return main_core.ajax.runAction('intranet.leftmenu.expandMenuGroup', {
	      data: {
	        id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'expandMenuGroup'
	        }
	      }
	    });
	  }
	  static collapseGroup(id) {
	    if (main_core.Loc.getMessage('USER_ID') <= 0) {
	      return;
	    }
	    return main_core.ajax.runAction('intranet.leftmenu.collapseMenuGroup', {
	      data: {
	        id
	      },
	      analyticsLabel: {
	        leftmenu: {
	          action: 'collapseMenuGroup'
	        }
	      }
	    });
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2,
	  _t3,
	  _t4;
	class Item {
	  constructor(parentContainer, container) {
	    this.links = [];
	    this.isDraggable = true;
	    this.storage = [];
	    this.parentContainer = parentContainer;
	    this.container = container;
	    this.init();
	    this.onDeleteAsFavorites = this.onDeleteAsFavorites.bind(this);
	    setTimeout(() => {
	      main_core_events.EventEmitter.subscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
	      main_core_events.EventEmitter.incrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
	      main_core_events.EventEmitter.subscribe(this, Options.eventName('onItemDelete'), this.destroy.bind(this));
	    }, 0);
	    this.showError = this.showError.bind(this);
	    this.showMessage = this.showMessage.bind(this);
	  }
	  getId() {
	    return this.container.dataset.id;
	  }
	  getCode() {
	    return this.constructor.code;
	  }
	  getName() {
	    return this.container.querySelector("[data-role='item-text']").textContent;
	  }
	  canDelete() {
	    return false;
	  }
	  delete() {
	    // Just do it.
	  }
	  init() {
	    this.links = [];
	    if (this.container.hasAttribute('data-link') && main_core.Type.isStringFilled(this.container.getAttribute("data-link"))) {
	      this.links.push(this.container.getAttribute("data-link"));
	    }
	    if (this.container.hasAttribute("data-all-links")) {
	      this.container.getAttribute("data-all-links").split(",").forEach(link => {
	        link = String(link).trim();
	        if (main_core.Type.isStringFilled(link)) {
	          this.links.push(link);
	        }
	      });
	    }
	    this.makeTextIcons();
	    this.storage = this.container.dataset.storage.split(',');
	  }
	  update({
	    link,
	    openInNewPage,
	    text
	  }) {
	    openInNewPage = openInNewPage === 'Y' ? 'Y' : 'N';
	    if (this.container.hasAttribute('data-link')) {
	      this.container.setAttribute('data-link', main_core.Text.encode(link));
	      this.container.setAttribute('data-new-page', openInNewPage);
	    }
	    const linkNode = this.container.querySelector('a');
	    if (linkNode) {
	      if (main_core.Type.isString(link)) {
	        linkNode.setAttribute('href', main_core.Text.encode(link));
	      }
	      linkNode.setAttribute('target', openInNewPage === 'Y' ? '_blank' : '_self');
	    }
	    this.container.querySelector("[data-role='item-text']").innerHTML = main_core.Text.encode(text);
	    this.init();
	  }
	  destroy() {
	    main_core_events.EventEmitter.unsubscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
	    main_core_events.EventEmitter.decrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, 'onItemDeleteAsFavorites');
	  }
	  getSimilarToUrl(currentUri) {
	    const result = [];
	    this.links.forEach((link, index) => {
	      if (areUrlsEqual(link, currentUri)) {
	        let priority = 0;
	        if (index === 0)
	          // main link is in higher priority
	          {
	            priority = this.getCode() === 'default' ? 2 : 1;
	          }
	        result.push({
	          priority,
	          url: link
	        });
	      }
	    });
	    return result;
	  }
	  makeTextIcons() {
	    if (!this.container.classList.contains("menu-item-no-icon-state")) {
	      return;
	    }
	    const icon = this.container.querySelector(".menu-item-icon");
	    const text = this.container.querySelector(".menu-item-link-text");
	    if (icon && text) {
	      icon.textContent = getShortName(text.textContent);
	    }
	  }
	  getCounterValue() {
	    const counter = ui_cnt.Counter.initFromCounterNode(this.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`));
	    if (!counter) {
	      return null;
	    }
	    return counter.getRealValue();
	  }
	  updateCounter(counterValue) {
	    const counter = ui_cnt.Counter.initFromCounterNode(this.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`));
	    if (!counter) {
	      return;
	    }
	    const oldValue = counter.getRealValue() || 0;
	    counter.update(parseInt(counterValue, 10));
	    if (counterValue > 0) {
	      main_core.Dom.addClass(this.container, 'menu-item-with-index');
	    } else {
	      main_core.Dom.removeClass(this.container, 'menu-item-with-index');
	    }
	    return {
	      oldValue,
	      newValue: counterValue
	    };
	  }
	  markAsActive() {
	    console.error('This action is only for the group');
	  }
	  showWarning(title, events) {
	    this.removeWarning();
	    const link = this.container.querySelector("a.menu-item-link");
	    if (!link) {
	      return;
	    }
	    title = title ? main_core.Text.encode(title) : '';
	    const node = main_core.Tag.render(_t$1 || (_t$1 = _$1`<a class="menu-post-warn-icon" title="${0}"></a>`), title);
	    if (events) {
	      Object.keys(events).forEach(key => {
	        main_core.Event.bind(node, key, events[key]);
	      });
	    }
	    this.container.classList.add("menu-item-warning-state");
	    link.appendChild(node);
	  }
	  removeWarning() {
	    if (!this.container.classList.contains('menu-item-warning-state')) {
	      return;
	    }
	    this.container.classList.remove('menu-item-warning-state');
	    let node;
	    while (node = this.container.querySelector("a.menu-post-warn-icon")) {
	      node.parentNode.removeChild(node);
	    }
	  }
	  showMessage(message) {
	    if (this.showMessagePopup) {
	      this.showMessagePopup.close();
	    }
	    this.showMessagePopup = main_popup.PopupManager.create("left-menu-message", this.container, {
	      content: '<div class="left-menu-message-popup">' + message + '</div>',
	      darkMode: true,
	      offsetTop: 2,
	      offsetLeft: 0,
	      angle: true,
	      events: {
	        onClose: () => {
	          this.showMessagePopup = null;
	        }
	      },
	      autoHide: true
	    });
	    this.showMessagePopup.show();
	    setTimeout(() => {
	      if (this.showMessagePopup) {
	        this.showMessagePopup.close();
	      }
	    }, 3000);
	  }
	  showError(response) {
	    const errors = [];
	    if (response.errors) {
	      errors.push(response.errors[0].message);
	    } else if (response instanceof TypeError) {
	      errors.push(response.message);
	    }
	    const message = [main_core.Loc.getMessage("MENU_ERROR_OCCURRED"), ...errors].join(' ');
	    this.showMessage(message);
	  }
	  getDropDownActions() {
	    return [];
	  }
	  getEditFields() {
	    return {
	      id: this.getId(),
	      text: this.getName()
	    };
	  }
	  onDeleteAsFavorites({
	    data
	  }) {
	    if (String(data.id) === String(this.getId())) {
	      if (this.getCode() === 'standard' /* instanceof ItemUserFavorites*/) {
	        main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	          item: this,
	          animate: true
	        });
	      } else {
	        this.storage = [...this.storage].filter(v => {
	          return v !== 'standard';
	        });
	        this.container.dataset.storage = this.storage.join(',');
	      }
	      main_core_events.EventEmitter.unsubscribe(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), this.onDeleteAsFavorites);
	      main_core_events.EventEmitter.decrementMaxListeners(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'));
	    }
	  }
	  static detect(node) {
	    return node.getAttribute("data-role") !== 'group' && node.getAttribute("data-type") === this.code;
	  }
	  static createNode({
	    id,
	    text,
	    link,
	    openInNewPage,
	    counterId,
	    counterValue,
	    topMenuId
	  }) {
	    id = main_core.Text.encode(id);
	    text = main_core.Text.encode(text);
	    link = main_core.Text.encode(link);
	    counterId = counterId ? main_core.Text.encode(counterId) : '';
	    counterValue = counterValue ? parseInt(counterValue) : 0;
	    openInNewPage = openInNewPage === 'Y' ? 'Y' : 'N';
	    const counter = new ui_cnt.Counter({
	      size: ui_cnt.Counter.Size.SMALL,
	      style: ui_cnt.Counter.Style.FILLED_ALERT,
	      useAirDesign: true,
	      value: counterValue,
	      id: `menu-counter-${counterId}`
	    });
	    return main_core.Tag.render(_t2 || (_t2 = _$1`<li 
			id="bx_left_menu_${0}" 
			data-status="show" 
			data-id="${0}" 
			data-role="item"
			data-storage="" 
			data-counter-id="${0}" 
			data-link="${0}" 
			data-all-links="" 
			data-type="${0}" 
			data-delete-perm="Y" 
			${0}
			data-new-page="${0}" 
			class="menu-item-block menu-item-no-icon-state">
				<span class="menu-favorites-btn menu-favorites-draggable">
					<span class="menu-fav-draggable-icon"></span>
				</span>
				<a class="menu-item-link" data-slider-ignore-autobinding="true" href="${0}" target="${0}">
					<span class="menu-item-icon-box">
						<span class="menu-item-icon">W</span>
					</span>
					<span class="menu-item-link-text " data-role="item-text">${0}</span>
					${0}
				</a>
				<span data-role="item-edit-control" class="menu-fav-editable-btn menu-favorites-btn">
					<span class="menu-favorites-btn-icon"></span>
				</span>
			</li>`), id, id, counterId, link, this.code, topMenuId ? `data-top-menu-id="${main_core.Text.encode(topMenuId)}"` : "", openInNewPage, link, openInNewPage === 'Y' ? '_blank' : '_self', text, counterId ? `<span class="menu-item-index-wrap">
						${counter.render()}
					</span>` : '');
	  }

	  //region Edition for siblings
	  static backendSaveItem(itemInfo) {
	    throw 'Function backendSaveItem must be replaced';
	  }
	  static showUpdate(item) {
	    return new Promise((resolve, reject) => {
	      this.showForm(item.container, item.getEditFields(), resolve, reject);
	    });
	  }
	  static checkForm(form) {
	    if (String(form.elements["text"].value).trim().length <= 0) {
	      form.elements["text"].classList.add('menu-form-input-error');
	      form.elements["text"].focus();
	      return false;
	    }
	    if (form.elements["link"]) {
	      if (String(form.elements["link"].value).trim().length <= 0 || Utils.refineUrl(form.elements["link"].value).length <= 0) {
	        form.elements["link"].classList.add('menu-form-input-error');
	        form.elements["link"].focus();
	        return false;
	      } else {
	        form.elements["link"].value = Utils.refineUrl(form.elements["link"].value);
	      }
	    }
	    return true;
	  }
	  static showForm(bindElement, itemInfo, resolve, reject) {
	    if (this.popup) {
	      this.popup.close();
	    }
	    const isEditMode = itemInfo.id !== '';
	    const form = main_core.Tag.render(_t3 || (_t3 = _$1`
<form name="menuAddToFavoriteForm">
	<input type="hidden" name="id" value="${0}">
	<label for="menuPageToFavoriteName" class="menu-form-label">${0}</label>
	<input name="text" type="text" id="menuPageToFavoriteName" class="menu-form-input" value="${0}" >
	${0}
	${0}
</form>`), main_core.Text.encode(itemInfo.id || ''), main_core.Loc.getMessage("MENU_ITEM_NAME"), main_core.Text.encode(itemInfo.text || ''), itemInfo['link'] !== undefined ? `<br><br>
	<label for="menuPageToFavoriteLink" class="menu-form-label">${main_core.Loc.getMessage("MENU_ITEM_LINK")}</label>
	<input name="link" id="menuPageToFavoriteLink" type="text" class="menu-form-input" value="${main_core.Text.encode(itemInfo.link)}" >` : '', itemInfo['openInNewPage'] !== undefined ? `<br><br>
	<input name="openInNewPage" id="menuOpenInNewPage" type="checkbox" value="Y" ${itemInfo.openInNewPage === 'Y' ? 'checked' : ''} >
	<label for="menuOpenInNewPage" class="menu-form-label">${main_core.Loc.getMessage("MENU_OPEN_IN_NEW_PAGE")}</label>` : '');
	    Object.keys(itemInfo).forEach(key => {
	      if (['id', 'text', 'link', 'openInNewPage'].indexOf(key) < 0) {
	        const name = main_core.Text.encode(key);
	        const value = main_core.Text.encode(itemInfo[key]);
	        form.appendChild(main_core.Tag.render(_t4 || (_t4 = _$1`<input type="hidden" name="${0}" value="${0}">`), name, value));
	      }
	    });
	    this.popup = main_popup.PopupManager.create('menu-self-item-popup', bindElement, {
	      className: 'menu-self-item-popup',
	      titleBar: itemInfo['link'] === undefined ? main_core.Loc.getMessage("MENU_RENAME_ITEM") : isEditMode ? main_core.Loc.getMessage("MENU_EDIT_SELF_PAGE") : main_core.Loc.getMessage("MENU_ADD_SELF_PAGE"),
	      offsetTop: 1,
	      offsetLeft: 20,
	      cacheable: false,
	      closeIcon: true,
	      lightShadow: true,
	      draggable: {
	        restrict: true
	      },
	      closeByEsc: true,
	      content: form,
	      buttons: [new ui_buttons.SaveButton({
	        onclick: () => {
	          if (this.checkForm(form)) {
	            const itemInfoToSave = {};
	            [...form.elements].forEach(node => {
	              itemInfoToSave[node.name] = node.value;
	            });
	            if (form.elements['openInNewPage']) {
	              itemInfoToSave['openInNewPage'] = form.elements["openInNewPage"].checked ? 'Y' : 'N';
	            }
	            this.backendSaveItem(itemInfoToSave).then(() => {
	              resolve(itemInfoToSave);
	              this.popup.close();
	            }).catch(Utils.catchError);
	          }
	        }
	      }), new ui_buttons.CancelButton({
	        onclick: () => {
	          this.popup.close();
	        }
	      })]
	    });
	    this.popup.show();
	  }
	  //endregion
	}
	Item.code = 'abstract';
	function areUrlsEqual(url, currentUri) {
	  const checkedUri = new main_core.Uri(url);
	  const checkedUrlBrief = checkedUri.getPath().replace('/index.php', '').replace('/index.html', '');
	  const currentUrlBrief = currentUri.getPath().replace('/index.php', '').replace('/index.html', '');
	  if (checkedUri.getHost() !== '' && checkedUri.getHost() !== currentUri.getHost()) {
	    return false;
	  }
	  if (currentUrlBrief.indexOf(checkedUrlBrief) !== 0) {
	    return false;
	  }
	  return true;
	}
	function getShortName(name) {
	  if (!main_core.Type.isStringFilled(name)) {
	    return "...";
	  }
	  name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
	  if (name.length <= 0) {
	    return '...';
	  }
	  let shortName;
	  let words = name.split(/[\s,]+/);
	  if (words.length <= 1) {
	    shortName = name.substring(0, 1);
	  } else if (words.length === 2) {
	    shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
	  } else {
	    let firstWord = words[0];
	    let secondWord = words[1];
	    for (let i = 1; i < words.length; i++) {
	      if (words[i].length > 3) {
	        secondWord = words[i];
	        break;
	      }
	    }
	    shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
	  }
	  return shortName.toUpperCase();
	}

	var _currentPageInTopMenu = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("currentPageInTopMenu");
	class ItemUserFavorites extends Item {
	  canDelete() {
	    return true;
	  }
	  delete() {
	    Backend.deleteFavoritesItemMenu({
	      id: this.getId(),
	      storage: this.storage
	    }).then(() => {
	      this.destroy();
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	        animate: true
	      });
	      const context = this.getSimilarToUrl(Utils.getCurUri()).length > 0 ? window : {
	        'doesnotmatter': ''
	      };
	      BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [{
	        id: this.getId()
	      }, this]);
	      BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	        isActive: false,
	        context: context
	      }]);
	    });
	  }
	  getDropDownActions() {
	    const contextMenuItems = [];
	    contextMenuItems.push({
	      text: main_core.Loc.getMessage("MENU_RENAME_ITEM"),
	      onclick: () => {
	        this.constructor.showUpdate(this).then(this.update.bind(this)).catch(this.showError);
	      }
	    });
	    contextMenuItems.push({
	      text: main_core.Loc.getMessage("MENU_REMOVE_STANDARD_ITEM"),
	      onclick: () => {
	        this.delete();
	      }
	    });
	    if (Options.isAdmin) {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_ADD_ITEM_TO_ALL"),
	        onclick: () => {
	          const itemLinkNode = this.container.querySelector('a');
	          Backend.addAdminSharedItemMenu({
	            id: this.getId(),
	            link: this.links[0],
	            text: this.getName(),
	            counterId: this.container.dataset.counterId,
	            openInNewPage: itemLinkNode && itemLinkNode.getAttribute("target") === "_blank" ? "Y" : "N"
	          }).then(() => {
	            this.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_ADDED_TO_ALL'));
	            this.container.dataset.type = ItemAdminShared.code;
	            this.storage.push(ItemUserFavorites.code);
	            this.container.dataset.storage = this.storage.join(',');
	            main_core_events.EventEmitter.emit(this, Options.eventName('onItemConvert'), this);
	          }).catch(this.showError);
	        }
	      });
	    }
	    return contextMenuItems;
	  }
	  static backendSaveItem(itemInfoToSave) {
	    return Backend.updateFavoritesItemMenu(itemInfoToSave);
	  }
	  static getActiveTopMenuItem() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _currentPageInTopMenu)[_currentPageInTopMenu]) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _currentPageInTopMenu)[_currentPageInTopMenu];
	    }
	    if (!BX.Main || !BX.Main.interfaceButtonsManager) {
	      return null;
	    }
	    const firstTopMenuInstance = Array.from(Object.values(BX.Main.interfaceButtonsManager.getObjects())).shift();
	    if (firstTopMenuInstance) {
	      const topMenuItem = firstTopMenuInstance.getActive();
	      if (topMenuItem && typeof topMenuItem === "object") {
	        const link = document.createElement("a");
	        link.href = topMenuItem['URL'];
	        //IE11 omits slash in the pathname
	        const path = link.pathname[0] !== "/" ? "/" + link.pathname : link.pathname;
	        babelHelpers.classPrivateFieldLooseBase(this, _currentPageInTopMenu)[_currentPageInTopMenu] = {
	          ID: topMenuItem['ID'] || null,
	          NODE: topMenuItem['NODE'] || null,
	          URL: path + link.search,
	          TEXT: topMenuItem['TEXT'],
	          DATA_ID: topMenuItem['DATA_ID'],
	          COUNTER_ID: topMenuItem['COUNTER_ID'],
	          COUNTER: topMenuItem['COUNTER'],
	          SUB_LINK: topMenuItem['SUB_LINK']
	        };
	      }
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _currentPageInTopMenu)[_currentPageInTopMenu];
	  }
	  static isCurrentPageStandard(topMenuPoint) {
	    if (topMenuPoint && topMenuPoint['URL']) {
	      const currentFullPath = document.location.pathname + document.location.search;
	      return topMenuPoint.URL === currentFullPath && topMenuPoint.URL.indexOf('workgroups') < 0;
	    }
	    return false;
	  }
	  static saveCurrentPage({
	    pageTitle,
	    pageLink
	  }) {
	    const topMenuPoint = this.getActiveTopMenuItem();
	    let itemInfo, startX, startY;
	    if (topMenuPoint && topMenuPoint.NODE && this.isCurrentPageStandard(topMenuPoint) && (pageLink === Utils.getCurPage() || pageLink === topMenuPoint.URL || !pageLink)) {
	      const menuNodeCoord = topMenuPoint.NODE.getBoundingClientRect();
	      startX = menuNodeCoord.left;
	      startY = menuNodeCoord.top;
	      itemInfo = {
	        id: topMenuPoint.DATA_ID,
	        text: pageTitle || topMenuPoint.TEXT,
	        link: Utils.getCurPage() || topMenuPoint.URL,
	        counterId: topMenuPoint.COUNTER_ID,
	        counterValue: topMenuPoint.COUNTER,
	        isStandardItem: true,
	        subLink: topMenuPoint.SUB_LINK
	      };
	    } else {
	      itemInfo = {
	        text: pageTitle || document.getElementById('pagetitle').innerText,
	        link: pageLink || Utils.getCurPage(),
	        isStandardItem: pageLink === Utils.getCurPage()
	      };
	      const titleCoord = BX("pagetitle").getBoundingClientRect();
	      startX = titleCoord.left;
	      startY = titleCoord.top;
	    }
	    return Backend.addFavoritesItemMenu(itemInfo).then(({
	      data: {
	        itemId
	      }
	    }) => {
	      itemInfo.id = itemId;
	      itemInfo.topMenuId = itemInfo.id;
	      return {
	        node: this.createNode(itemInfo),
	        animateFromPoint: {
	          startX,
	          startY
	        },
	        itemInfo: itemInfo
	      };
	    });
	  }
	  static deleteCurrentPage({
	    pageLink
	  }) {
	    const topPoint = this.getActiveTopMenuItem();
	    var itemInfo = {},
	      startX,
	      startY;
	    if (topPoint && this.isCurrentPageStandard(topPoint)) {
	      itemInfo['id'] = topPoint.DATA_ID;
	      const menuNodeCoord = topPoint.NODE.getBoundingClientRect();
	      startX = menuNodeCoord.left;
	      startY = menuNodeCoord.top;
	    } else {
	      itemInfo['link'] = pageLink || Utils.getCurPage();
	      const titleCoord = BX("pagetitle").getBoundingClientRect();
	      startX = titleCoord.left;
	      startY = titleCoord.top;
	    }
	    return Backend.deleteFavoritesItemMenu(itemInfo).then(({
	      data
	    }) => {
	      if (!itemInfo.id && data && data['itemId']) {
	        itemInfo.id = data['itemId'];
	      }
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {
	        id: itemInfo.id
	      });
	      return {
	        itemInfo: itemInfo,
	        animateToPoint: {
	          startX,
	          startY
	        }
	      };
	    });
	  }
	  static saveStandardPage({
	    DATA_ID,
	    TEXT,
	    SUB_LINK,
	    COUNTER_ID,
	    COUNTER,
	    NODE,
	    URL
	  }) {
	    const itemInfo = {
	      id: DATA_ID,
	      text: TEXT,
	      link: URL,
	      subLink: SUB_LINK,
	      counterId: COUNTER_ID,
	      counterValue: COUNTER
	    };
	    const pos = NODE.getBoundingClientRect();
	    const startX = pos.left;
	    const startY = pos.top;
	    return Backend.addFavoritesItemMenu(itemInfo).then(({
	      data: {
	        itemId
	      }
	    }) => {
	      itemInfo.id = itemId;
	      itemInfo.topMenuId = itemInfo.id;
	      const topPoint = this.getActiveTopMenuItem();
	      BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);
	      BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	        isActive: true,
	        context: topPoint && topPoint.DATA_ID === DATA_ID ? window : null
	      }]);
	      return {
	        node: this.createNode(itemInfo),
	        animateFromPoint: {
	          startX,
	          startY
	        }
	      };
	    });
	  }
	  static deleteStandardPage({
	    DATA_ID
	  }) {
	    const itemInfo = {
	      id: DATA_ID
	    };
	    return Backend.deleteFavoritesItemMenu(itemInfo).then(() => {
	      main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, Options.eventName('onItemDeleteAsFavorites'), {
	        id: itemInfo.id
	      });
	      BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [itemInfo, this]);
	      BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	        isActive: false
	      }]);
	      return {
	        itemInfo: itemInfo
	      };
	    });
	  }
	}
	ItemUserFavorites.code = 'standard';
	Object.defineProperty(ItemUserFavorites, _currentPageInTopMenu, {
	  writable: true,
	  value: null
	});

	class ItemUserSelf extends Item {
	  canDelete() {
	    return true;
	  }
	  delete() {
	    return Backend.deleteSelfITem(this.getId()).then(() => {
	      if (this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	        Backend.deleteFavoritesItemMenu({
	          id: this.getId()
	        });
	      }
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	        animate: true
	      });
	    }).catch(this.showError);
	  }
	  getDropDownActions() {
	    const contextMenuItems = [];
	    contextMenuItems.push({
	      text: main_core.Loc.getMessage("MENU_EDIT_ITEM"),
	      onclick: () => {
	        this.constructor.showUpdate(this).then(this.update.bind(this)).catch(this.showError);
	      }
	    });
	    contextMenuItems.push({
	      text: main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM'),
	      onclick: () => {
	        ui_dialogs_messagebox.MessageBox.confirm(main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM_CONFIRM'), main_core.Loc.getMessage('MENU_DELETE_SELF_ITEM'), messageBox => {
	          this.delete();
	          messageBox.close();
	        }, main_core.Loc.getMessage('MENU_DELETE'));
	      }
	    });
	    if (Options.isAdmin) {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_ADD_ITEM_TO_ALL"),
	        onclick: () => {
	          const itemLinkNode = this.container.querySelector('a');
	          Backend.addAdminSharedItemMenu({
	            id: this.getId(),
	            link: this.links[0],
	            text: this.getName(),
	            counterId: this.container.dataset.counterId,
	            openInNewPage: itemLinkNode && itemLinkNode.getAttribute("target") === "_blank" ? "Y" : "N"
	          }).then(() => {
	            this.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_ADDED_TO_ALL'));
	            this.container.dataset.type = ItemAdminShared.code;
	            this.storage.push(ItemUserSelf.code);
	            this.container.dataset.storage = this.storage.join(',');
	            main_core_events.EventEmitter.emit(this, Options.eventName('onItemConvert'), this);
	          }).catch(this.showError);
	        }
	      });
	    }
	    return contextMenuItems;
	  }
	  getEditFields() {
	    return {
	      id: this.getId(),
	      text: this.getName(),
	      link: this.links[0],
	      openInNewPage: this.container.getAttribute('data-new-page')
	    };
	  }
	  static backendSaveItem(itemInfo) {
	    return Backend.saveSelfItemMenu(itemInfo).then(({
	      data
	    }) => {
	      if (data && data['itemId']) {
	        itemInfo.id = data['itemId'];
	      }
	      return itemInfo;
	    });
	  }
	  static showAdd(bindElement) {
	    return new Promise((resolve1, reject2) => {
	      this.showForm(bindElement, {
	        id: 0,
	        name: '',
	        link: '',
	        openInNewPage: 'Y'
	      }, resolve1, reject2);
	    }).then(itemInfo => {
	      return {
	        node: this.createNode(itemInfo)
	      };
	    });
	  }
	}
	ItemUserSelf.code = 'self';

	class ItemAdminShared extends Item {
	  canDelete() {
	    return this.container.dataset.deletePerm === 'Y';
	  }
	  delete() {
	    Backend.deleteAdminSharedItemMenu(this.getId()).then(() => {
	      if (this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	        Backend.deleteFavoritesItemMenu({
	          id: this.getId()
	        });
	      }
	      if (this.storage.indexOf(ItemUserSelf.code) >= 0) {
	        Backend.deleteSelfITem(this.getId());
	      }
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	        animate: true
	      });
	    }).catch(this.showError);
	  }
	  getDropDownActions() {
	    if (!this.canDelete()) {
	      return [];
	    }
	    const contextMenuItems = [];
	    /*		contextMenuItems.push({
	    			text: Loc.getMessage("MENU_RENAME_ITEM"),
	    			onclick: () => {
	    				this.constructor
	    					.showUpdate(this)
	    					.then(this.update.bind(this))
	    					.catch(this.showError.bind(this));
	    			}
	    		});
	    */

	    if (this.storage.filter(value => {
	      return value === ItemUserFavorites.code || value === ItemUserSelf.code;
	    }).length > 0) {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage('MENU_REMOVE_STANDARD_ITEM'),
	        onclick: this.delete.bind(this)
	      });
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage('MENU_DELETE_CUSTOM_ITEM_FROM_ALL'),
	        onclick: () => {
	          Backend.deleteAdminSharedItemMenu(this.getId()).then(() => {
	            this.showMessage(main_core.Loc.getMessage('MENU_ITEM_WAS_DELETED_FROM_ALL'));
	            const codeToConvert = this.storage.indexOf(ItemUserSelf.code) >= 0 ? ItemUserSelf.code : ItemUserFavorites.code;
	            this.container.dataset.type = codeToConvert;
	            this.container.dataset.storage = this.storage.filter(v => {
	              return v !== codeToConvert;
	            }).join(',');
	            main_core_events.EventEmitter.emit(this, Options.eventName('onItemConvert'), this);
	          }).catch(this.showError);
	        }
	      });
	    } else {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_DELETE_CUSTOM_ITEM_FROM_ALL"),
	        onclick: this.delete.bind(this)
	      });
	    }
	    return contextMenuItems;
	  }
	}
	ItemAdminShared.code = 'admin';

	class ItemAdminCustom extends Item {
	  canDelete() {
	    return this.container.dataset.deletePerm === 'Y';
	  }
	  delete() {
	    if (this.canDelete()) {
	      Backend.deleteCustomItem(this.getId()).then(() => {
	        if (this.storage.indexOf(ItemUserFavorites.code) >= 0) {
	          Backend.deleteFavoritesItemMenu({
	            id: this.getId()
	          });
	        }
	        main_core_events.EventEmitter.emit(this, Options.eventName('onItemDelete'), {
	          animate: true
	        });
	      }).catch(this.showError);
	    }
	  }
	  getDropDownActions() {
	    const actions = [];
	    if (this.canDelete()) {
	      actions.push({
	        text: main_core.Loc.getMessage("MENU_DELETE_ITEM_FROM_ALL"),
	        onclick: this.delete.bind(this)
	      });
	    }
	    return actions;
	  }
	}
	ItemAdminCustom.code = 'custom';

	class ItemMainPage extends Item {
	  canDelete() {
	    return false;
	  }
	  openSettings() {
	    const url = `${main_core.Loc.getMessage('mainpage_settings_path')}&analyticContext=left_menu`;
	    BX.SidePanel.Instance.open(url);
	  }
	}
	ItemMainPage.code = 'main';

	class ItemSystem extends Item {
	  canDelete() {
	    return false;
	  }
	}
	ItemSystem.code = 'default';

	var _collapsingAnimation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("collapsingAnimation");
	class ItemGroup extends Item {
	  constructor() {
	    super(...arguments);
	    Object.defineProperty(this, _collapsingAnimation, {
	      writable: true,
	      value: void 0
	    });
	    this.container.addEventListener('click', this.toggleAndSave.bind(this), true);
	    this.container.addEventListener('mouseleave', () => {
	      main_core.Dom.removeClass(this.container, 'menu-item-group-actioned');
	    });
	    this.groupContainer = this.container.parentNode.querySelector(`[data-group-id="${this.getId()}"]`);
	    if (this.container.getAttribute('data-collapse-mode') === 'collapsed') {
	      this.groupContainer.style.display = 'none';
	    }
	    setTimeout(() => {
	      this.updateCounter();
	    }, 0);
	  }
	  toggleAndSave(event) {
	    event.preventDefault();
	    event.stopPropagation();
	    if (this.container.getAttribute('data-collapse-mode') === 'collapsed') {
	      Backend.expandGroup(this.getId());
	      this.expand().then(() => {
	        this.container.setAttribute('data-collapse-mode', 'expanded');
	      });
	    } else {
	      Backend.collapseGroup(this.getId());
	      this.collapse().then(() => {
	        this.container.setAttribute('data-collapse-mode', 'collapsed');
	      });
	    }
	    return false;
	  }
	  checkAndCorrect() {
	    const groupContainer = this.groupContainer;
	    if (groupContainer.parentNode === this.container) {
	      main_core.Dom.insertAfter(groupContainer, this.container);
	    }
	    [...groupContainer.querySelectorAll(`.menu-item-block`)].forEach(node => {
	      node.setAttribute('data-status', this.container.getAttribute("data-status"));
	    });
	    return this;
	  }
	  collapse(hideGroupContainer) {
	    return new Promise(resolve => {
	      const groupContainer = this.groupContainer;
	      if (babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation]) {
	        babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation].stop();
	      }
	      groupContainer.style.overflow = 'hidden';
	      main_core.Dom.addClass(this.container, 'menu-item-group-collapsing');
	      main_core.Dom.addClass(this.container, 'menu-item-group-actioned');
	      main_core.Dom.addClass(groupContainer, 'menu-item-group-collapsing');
	      const slideParams = {
	        height: groupContainer.offsetHeight,
	        display: groupContainer.style.display
	      };
	      babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation] = new BX.easing({
	        duration: 500,
	        start: {
	          height: slideParams.height,
	          opacity: 100
	        },
	        finish: {
	          height: 0,
	          opacity: 0
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: function (state) {
	          groupContainer.style.height = state.height + 'px';
	          groupContainer.style.opacity = state.opacity / 100;
	        },
	        complete: () => {
	          groupContainer.style.display = 'none';
	          groupContainer.style.opacity = 'auto';
	          groupContainer.style.height = 'auto';
	          main_core.Dom.style(groupContainer, 'overflow', null);
	          if (this.container.getAttribute('data-contains-active-item') === 'Y') {
	            main_core.Dom.addClass(this.container, 'menu-item-active');
	          }
	          main_core.Dom.removeClass(this.container, 'menu-item-group-collapsing');
	          main_core.Dom.removeClass(groupContainer, 'menu-item-group-collapsing');
	          babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation] = null;
	          if (hideGroupContainer === true) {
	            this.container.appendChild(groupContainer);
	          }
	          resolve();
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation].animate();
	    });
	  }
	  expand(checkAttribute) {
	    return new Promise(resolve => {
	      const container = this.container;
	      const groupContainer = this.groupContainer;
	      if (checkAttribute === true && container.getAttribute('data-collapse-mode') === 'collapsed') {
	        return resolve();
	      }
	      const contentHeight = groupContainer.querySelectorAll('li').length * (container.offsetHeight + 4);
	      main_core.Dom.addClass(container, 'menu-item-group-expanding');
	      main_core.Dom.addClass(container, 'menu-item-group-actioned');
	      main_core.Dom.addClass(groupContainer, 'menu-item-group-expanding');
	      if (groupContainer.parentNode === this.container) {
	        main_core.Dom.insertAfter(groupContainer, this.container);
	      }
	      groupContainer.style.display = 'block';
	      babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation] = new BX.easing({
	        duration: 500,
	        start: {
	          height: 0,
	          opacity: 0
	        },
	        finish: {
	          height: contentHeight,
	          opacity: 100
	        },
	        transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	        step: function (state) {
	          groupContainer.style.height = state.height + 'px';
	          groupContainer.style.opacity = state.opacity / 100;
	        },
	        complete: function () {
	          main_core.Dom.removeClass(container, 'menu-item-group-expanding menu-item-active');
	          main_core.Dom.removeClass(groupContainer, 'menu-item-group-expanding');
	          groupContainer.style.height = 'auto';
	          groupContainer.style.opacity = 'auto';
	          resolve();
	        }
	      });
	      babelHelpers.classPrivateFieldLooseBase(this, _collapsingAnimation)[_collapsingAnimation].animate();
	    });
	  }
	  canDelete() {
	    return false;
	  }
	  updateCounter() {
	    let counterValue = 0;
	    [...this.container.parentNode.querySelector(`[data-group-id="${this.getId()}"]`).querySelectorAll(`.${ui_cnt.Counter.BaseClassname}`)].forEach(node => {
	      const counter = ui_cnt.Counter.initFromCounterNode(node);
	      counterValue += counter.getRealValue();
	    });
	    ui_cnt.Counter.updateCounterNodeValue(this.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`), counterValue);
	    if (counterValue > 0) {
	      main_core.Dom.addClass(this.container, 'menu-item-with-index');
	    } else {
	      main_core.Dom.removeClass(this.container, 'menu-item-with-index');
	    }
	  }
	  markAsActive() {
	    this.container.setAttribute('data-contains-active-item', 'Y');
	    if (this.container.getAttribute('data-collapse-mode') === 'collapsed') main_core.Dom.addClass(this.container, 'menu-item-active');
	  }
	  markAsInactive() {
	    this.container.removeAttribute('data-contains-active-item');
	    main_core.Dom.removeClass(this.container, 'menu-item-active');
	  }
	  isActive() {
	    return this.container.getAttribute('data-contains-active-item') === 'Y';
	  }
	  static detect(node) {
	    return node.getAttribute("data-role") === 'group' && node.getAttribute("data-type") === this.code;
	  }
	}
	ItemGroup.code = 'group';

	class ItemGroupSystem extends ItemGroup {
	  constructor() {
	    super(...arguments);
	    this.container.querySelector('[data-role="item-edit-control"]').style.display = 'none';
	  }
	}
	ItemGroupSystem.code = 'system_group';

	const itemMappings = [Item, ItemAdminShared, ItemUserFavorites, ItemAdminCustom, ItemUserSelf, ItemSystem, ItemGroup, ItemGroupSystem, ItemMainPage];
	function getItem(itemData) {
	  let itemClassName = Item;
	  itemMappings.forEach(itemClass => {
	    if (itemClass.detect(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return itemClassName;
	}

	var _link = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("link");
	var _actualLink = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("actualLink");
	class ItemActive {
	  constructor() {
	    Object.defineProperty(this, _link, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _actualLink, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _actualLink)[_actualLink] = new main_core.Uri(window.location.href);
	  }
	  checkAndSet(item, links) {
	    /*
	    Custom items have more priority than standard items.
	    Example:
	    	Calendar (standard item)
	    		data-link="/company/personal/user/1/calendar/"
	    		data-all-links="/company/personal/user/1/calendar/,/calendar/
	    		Company Calendar (custom item)
	    		 data-link="/calendar/"
	    	We've got two items with the identical link /calendar/'.
	    */
	    if (item === this.item) {
	      return false;
	    }
	    let theMostOfTheLinks = babelHelpers.classPrivateFieldLooseBase(this, _link)[_link];
	    links.forEach(link => {
	      const linkUri = new main_core.Uri(link.url);
	      let changeActiveItem = false;
	      if (!theMostOfTheLinks || theMostOfTheLinks.uri.getPath().length < linkUri.getPath().length) {
	        changeActiveItem = true;
	      } else if (theMostOfTheLinks.uri.getPath().length === linkUri.getPath().length) {
	        const actualParams = babelHelpers.classPrivateFieldLooseBase(this, _actualLink)[_actualLink].getQueryParams();
	        const theMostOfTheLinkServiceData = {
	          params: theMostOfTheLinks.uri.getQueryParams(),
	          matches: 0
	        };
	        const comparedLinkParams = {
	          params: linkUri.getQueryParams(),
	          matches: 0
	        };
	        for (const key of Object.keys(actualParams)) {
	          if (key in actualParams && key in theMostOfTheLinkServiceData.params && String(actualParams[key]) === String(theMostOfTheLinkServiceData.params[key])) {
	            theMostOfTheLinkServiceData.matches++;
	          }
	          if (key in actualParams && key in comparedLinkParams.params && String(actualParams[key]) === String(comparedLinkParams.params[key])) {
	            comparedLinkParams.matches++;
	          }
	        }
	        if (comparedLinkParams.matches > theMostOfTheLinkServiceData.matches || comparedLinkParams.matches === theMostOfTheLinkServiceData.matches && Object.keys(comparedLinkParams.params).length < Object.keys(theMostOfTheLinkServiceData.params).length || theMostOfTheLinks.priority < link.priority) {
	          changeActiveItem = true;
	        }
	      }
	      if (changeActiveItem) {
	        theMostOfTheLinks = {
	          priority: link.priority,
	          url: link.url,
	          uri: linkUri
	        };
	      }
	    });
	    if (theMostOfTheLinks !== babelHelpers.classPrivateFieldLooseBase(this, _link)[_link]) {
	      if (this.item) {
	        this.unhighlight(this.item);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _link)[_link] = theMostOfTheLinks;
	      this.item = item;
	      this.highlight();
	      return true;
	    }
	    return false;
	  }
	  checkAndUnset(item) {
	    if (item instanceof Item && item === this.item) {
	      this.unhighlight(this.item);
	      this.item = null;
	      babelHelpers.classPrivateFieldLooseBase(this, _link)[_link] = null;
	    }
	  }
	  highlight() {
	    if (this.item) {
	      this.item.container.classList.add('menu-item-active');
	      let parent = this.item.container.closest('[data-role="group-content"]');
	      let parentContainer;
	      while (parent) {
	        parentContainer = parent.parentNode.querySelector(`[data-id="${parent.getAttribute('data-group-id')}"]`);
	        if (parentContainer) {
	          parentContainer.setAttribute('data-contains-active-item', 'Y');
	          if (parentContainer.getAttribute('data-collapse-mode') === 'collapsed') {
	            parentContainer.classList.add('menu-item-active');
	          }
	        }
	        parent = parent.closest('[data-relo="group-content"]');
	      }
	    }
	  }
	  unhighlight(item) {
	    if (!(item instanceof Item)) {
	      item = this.item;
	    }
	    if (item instanceof Item) {
	      item.container.classList.remove('menu-item-active');
	      let parent = item.container.closest('[data-role="group-content"]');
	      let parentContainer;
	      while (parent) {
	        parentContainer = parent.parentNode.querySelector(`[data-id="${parent.getAttribute('data-group-id')}"]`);
	        if (parentContainer) {
	          parentContainer.removeAttribute('data-contains-active-item');
	          parentContainer.classList.remove('menu-item-active');
	        }
	        parent = parent.closest('[data-relo="group-content"]');
	      }
	      return item;
	    }
	    return null;
	  }
	}

	var _activeItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("activeItem");
	var _isEditMode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isEditMode");
	var _showHiddenContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showHiddenContainer");
	var _hideHiddenContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideHiddenContainer");
	var _animation = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animation");
	var _recalculateCounters = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("recalculateCounters");
	var _refreshActivity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refreshActivity");
	var _updateCountersLastValue = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("updateCountersLastValue");
	var _getItemsByCounterId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItemsByCounterId");
	var _getItemsToSave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getItemsToSave");
	var _saveItemsSort = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveItemsSort");
	var _getParentItemFor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getParentItemFor");
	var _canChangePaternity = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("canChangePaternity");
	var _openItemMenuPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openItemMenuPopup");
	var _animateTopItemToLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animateTopItemToLeft");
	var _animateTopItemFromLeft = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animateTopItemFromLeft");
	var _registerDND = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("registerDND");
	var _menuItemDragStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemDragStart");
	var _menuItemDragMove = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemDragMove");
	var _menuItemDragHover = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemDragHover");
	var _menuItemDragStop = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("menuItemDragStop");
	class ItemsController extends DefaultController {
	  constructor(container, {
	    events
	  }) {
	    super(container, {
	      events
	    });
	    Object.defineProperty(this, _menuItemDragStop, {
	      value: _menuItemDragStop2
	    });
	    Object.defineProperty(this, _menuItemDragHover, {
	      value: _menuItemDragHover2
	    });
	    Object.defineProperty(this, _menuItemDragMove, {
	      value: _menuItemDragMove2
	    });
	    Object.defineProperty(this, _menuItemDragStart, {
	      value: _menuItemDragStart2
	    });
	    Object.defineProperty(this, _registerDND, {
	      value: _registerDND2
	    });
	    Object.defineProperty(this, _animateTopItemFromLeft, {
	      value: _animateTopItemFromLeft2
	    });
	    Object.defineProperty(this, _animateTopItemToLeft, {
	      value: _animateTopItemToLeft2
	    });
	    Object.defineProperty(this, _canChangePaternity, {
	      value: _canChangePaternity2
	    });
	    Object.defineProperty(this, _getParentItemFor, {
	      value: _getParentItemFor2
	    });
	    Object.defineProperty(this, _saveItemsSort, {
	      value: _saveItemsSort2
	    });
	    Object.defineProperty(this, _getItemsToSave, {
	      value: _getItemsToSave2
	    });
	    Object.defineProperty(this, _getItemsByCounterId, {
	      value: _getItemsByCounterId2
	    });
	    Object.defineProperty(this, _refreshActivity, {
	      value: _refreshActivity2
	    });
	    Object.defineProperty(this, _recalculateCounters, {
	      value: _recalculateCounters2
	    });
	    Object.defineProperty(this, _animation, {
	      value: _animation2
	    });
	    Object.defineProperty(this, _hideHiddenContainer, {
	      value: _hideHiddenContainer2
	    });
	    Object.defineProperty(this, _showHiddenContainer, {
	      value: _showHiddenContainer2
	    });
	    this.items = new Map();
	    Object.defineProperty(this, _activeItem, {
	      writable: true,
	      value: new ItemActive()
	    });
	    Object.defineProperty(this, _isEditMode, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _updateCountersLastValue, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _openItemMenuPopup, {
	      writable: true,
	      value: void 0
	    });
	    this.parentContainer = container;
	    this.container = container.querySelector(".menu-items");
	    this.hiddenContainer = container.querySelector('#left-menu-hidden-items-block');
	    container.querySelectorAll('li.menu-item-block').forEach(this.registerItem.bind(this));
	    container.querySelector('#left-menu-hidden-separator').addEventListener('click', this.toggleHiddenContainer.bind(this));
	    if (this.getActiveItem() && this.getActiveItem().container.getAttribute('data-status') === 'hide') {
	      babelHelpers.classPrivateFieldLooseBase(this, _showHiddenContainer)[_showHiddenContainer](false);
	    }
	  }
	  registerItem(node) {
	    var _item$container$query;
	    const itemClass = getItem(node);
	    const item = new itemClass(this.container, node);
	    this.items.set(item.getId(), item);
	    if (!(item instanceof ItemMainPage)) {
	      babelHelpers.classPrivateFieldLooseBase(this, _registerDND)[_registerDND](item);
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _activeItem)[_activeItem].checkAndSet(item, item.getSimilarToUrl(Utils.getCurUri())) === true) {
	      let parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	      while (parentItem) {
	        parentItem.markAsActive();
	        parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](parentItem);
	      }
	    }
	    main_core_events.EventEmitter.subscribe(item, Options.eventName('onItemDelete'), ({
	      data
	    }) => {
	      this.deleteItem(item, data);
	    });
	    main_core_events.EventEmitter.subscribe(item, Options.eventName('onItemConvert'), ({
	      data
	    }) => {
	      this.convertItem(item, data);
	    });
	    [...item.container.querySelectorAll('a')].forEach(node => {
	      node.addEventListener('click', event => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] === true) {
	          event.preventDefault();
	          event.stopPropagation();
	          return false;
	        }
	      }, true);
	    });
	    (_item$container$query = item.container.querySelector('[data-role="item-edit-control"]')) == null ? void 0 : _item$container$query.addEventListener('click', event => {
	      this.openItemMenu(item, event.target, event.currentTarget);
	    });
	    return item;
	  }
	  unregisterItem(item) {
	    if (!this.items.has(item.getId())) {
	      return;
	    }
	    this.items.delete(item.getId());
	    babelHelpers.classPrivateFieldLooseBase(this, _activeItem)[_activeItem].checkAndUnset(item, item.getSimilarToUrl(Utils.getCurUri()));
	    main_core_events.EventEmitter.unsubscribeAll(item, Options.eventName('onItemDelete'));
	    main_core_events.EventEmitter.unsubscribeAll(item, Options.eventName('onItemConvert'));
	    item.container.parentNode.replaceChild(item.container.cloneNode(true), item.container);
	  }
	  get isEditMode() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode];
	  }
	  switchToEditMode() {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] = true;
	    main_core_events.EventEmitter.emit(this, Options.eventName('EditMode'));
	  }
	  switchToViewMode() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode]) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] = false;
	    main_core_events.EventEmitter.emit(this, Options.eventName('ViewMode'));
	  }
	  isHiddenContainerVisible() {
	    return this.hiddenContainer.classList.contains('menu-item-favorites-more-open');
	  }
	  toggleHiddenContainer(animate) {
	    if (this.hiddenContainer.classList.contains('menu-item-favorites-more-open')) {
	      babelHelpers.classPrivateFieldLooseBase(this, _hideHiddenContainer)[_hideHiddenContainer](animate);
	    } else {
	      babelHelpers.classPrivateFieldLooseBase(this, _showHiddenContainer)[_showHiddenContainer](animate);
	    }
	  }
	  setItemAsAMainPage(item) {
	    const node = item.container;
	    node.setAttribute("data-status", "show");
	    const startTop = node.offsetTop;
	    const dragElement = main_core.Dom.create("div", {
	      attrs: {
	        className: "menu-draggable-wrap"
	      },
	      style: {
	        top: startTop
	      }
	    });
	    const insertBeforeElement = node.nextElementSibling;
	    if (insertBeforeElement) {
	      node.parentNode.insertBefore(dragElement, insertBeforeElement);
	    } else {
	      node.parentNode.appendChild(dragElement);
	    }
	    dragElement.appendChild(node);
	    main_core.Dom.addClass(node, "menu-item-draggable");
	    new BX.easing({
	      duration: 500,
	      start: {
	        top: startTop
	      },
	      finish: {
	        top: 0
	      },
	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function (state) {
	        dragElement.style.top = state.top + "px";
	      },
	      complete: () => {
	        this.container.insertBefore(node, BX("left-menu-empty-item").nextSibling);
	        main_core.Dom.removeClass(node, "menu-item-draggable");
	        main_core.Dom.remove(dragElement);
	        babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]({
	          action: 'mainPageIsSet',
	          itemId: item.getId()
	        });
	      }
	    }).animate();
	  }
	  showItem(item) {
	    const oldParent = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	    const container = this.container;
	    item.container.setAttribute('data-status', 'show');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _canChangePaternity)[_canChangePaternity](item)) {
	      container.appendChild(item.container);
	      babelHelpers.classPrivateFieldLooseBase(this, _refreshActivity)[_refreshActivity](item, oldParent);
	    } else if (oldParent) {
	      container.appendChild(oldParent.container);
	      oldParent.container.setAttribute('data-status', 'show');
	      container.appendChild(oldParent.groupContainer);
	    }
	    if (this.hiddenContainer.querySelector('.menu-item-block') === null) {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
	      babelHelpers.classPrivateFieldLooseBase(this, _hideHiddenContainer)[_hideHiddenContainer](false);
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _recalculateCounters)[_recalculateCounters](item);
	    babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]({
	      action: 'showItem',
	      itemId: item.getId()
	    });
	  }
	  hideItem(item) {
	    const oldParent = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	    const container = this.hiddenContainer.querySelector('#left-menu-hidden-items-list');
	    const emitEvent = container.querySelector('.menu-item-block') === null;
	    item.container.setAttribute('data-status', 'hide');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _canChangePaternity)[_canChangePaternity](item)) {
	      container.appendChild(item.container);
	      babelHelpers.classPrivateFieldLooseBase(this, _refreshActivity)[_refreshActivity](item, oldParent);
	    } else if (oldParent) {
	      container.appendChild(oldParent.container);
	      oldParent.container.setAttribute('data-status', 'hide');
	      container.appendChild(oldParent.groupContainer);
	    }
	    if (emitEvent) {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _recalculateCounters)[_recalculateCounters](item);
	    babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]({
	      action: 'hideItem',
	      itemId: item.getId()
	    });
	  }
	  updateCounters(counters, send) {
	    const countersDynamic = {};
	    send = send !== false;
	    [...Object.entries(counters)].forEach(([counterId, counterValue]) => {
	      [...babelHelpers.classPrivateFieldLooseBase(this, _getItemsByCounterId)[_getItemsByCounterId](counterId)].forEach(item => {
	        const {
	          oldValue,
	          newValue
	        } = item.updateCounter(counterValue);
	        const state = item.container.getAttribute('data-status');
	        if ((counterId.indexOf('crm_') < 0 || counterId.indexOf('crm_all') >= 0) && (counterId.indexOf('tasks_') < 0 || counterId.indexOf('tasks_total') >= 0)) {
	          countersDynamic[state] = countersDynamic[state] || 0;
	          countersDynamic[state] += newValue - oldValue;
	        }
	        let parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	        while (parentItem) {
	          parentItem.updateCounter();
	          parentItem = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](parentItem);
	        }
	      });
	      if (send) {
	        BX.localStorage.set('lmc-' + counterId, counterValue, 5);
	      }
	    });
	    if (countersDynamic['hide'] !== undefined && countersDynamic['hide'] !== 0) {
	      const hiddenCounterNode = this.parentContainer.querySelector('#menu-hidden-counter');
	      hiddenCounterNode.dataset.counterValue = Math.max(0, Number(hiddenCounterNode.dataset.counterValue) + Number(countersDynamic['hide']));
	      if (hiddenCounterNode.dataset.counterValue > 0) {
	        hiddenCounterNode.classList.remove('menu-hidden-counter');
	        hiddenCounterNode.innerHTML = hiddenCounterNode.dataset.counterValue > 99 ? '99+' : hiddenCounterNode.dataset.counterValue;
	      } else {
	        hiddenCounterNode.classList.add('menu-hidden-counter');
	        hiddenCounterNode.innerHTML = '';
	      }
	    }
	    if (typeof BXIM !== 'undefined') {
	      if (babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] === null) {
	        babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] = 0;
	        [...this.items.entries()].forEach(([id, item]) => {
	          if (item instanceof ItemGroup) {
	            return;
	          }
	          const res = item.getCounterValue();
	          if (res > 0) {
	            let counterId = 'doesNotMatter';
	            if (id.indexOf('menu_crm') >= 0 || id.indexOf('menu_tasks') >= 0) {
	              const counterNode = item.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`);
	              if (counterNode) {
	                counterId = counterNode.id;
	              }
	            }
	            if (counterId === 'doesNotMatter' || counterId.indexOf('crm_all') >= 0 || counterId.indexOf('tasks_total') >= 0) {
	              babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] += res;
	            }
	          }
	        });
	      } else {
	        babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] += countersDynamic['show'] !== undefined ? countersDynamic['show'] : 0;
	        babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] += countersDynamic['hide'] !== undefined ? countersDynamic['hide'] : 0;
	      }
	      const visibleValue = babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] > 99 ? '99+' : babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue] < 0 ? '0' : babelHelpers.classPrivateFieldLooseBase(this, _updateCountersLastValue)[_updateCountersLastValue];
	      const DesktopApi = main_core.Reflection.getClass('BX.Messenger.v2.Lib.DesktopApi');
	      if (DesktopApi && DesktopApi.isDesktop()) {
	        DesktopApi.setBrowserIconBadge(visibleValue);
	      }
	    }
	    [...this.items.entries()].forEach(([id, itemGroup]) => {
	      if (itemGroup instanceof ItemGroup) {
	        itemGroup.updateCounter();
	      }
	    });
	  }
	  decrementCounter(counters) {
	    [...Object.entries(counters)].forEach(([counterId, counterValue]) => {
	      const item = babelHelpers.classPrivateFieldLooseBase(this, _getItemsByCounterId)[_getItemsByCounterId](counterId).shift();
	      if (item) {
	        const value = item.getCounterValue();
	        counters[counterId] = value > counterValue ? value - counterValue : 0;
	      } else {
	        delete counters[counterId];
	      }
	    });
	    this.updateCounters(counters, false);
	  }
	  addItem({
	    node,
	    animateFromPoint
	  }) {
	    if (!(node instanceof Element)) {
	      return;
	    }
	    const styleValue = node.style.display;
	    if (animateFromPoint) {
	      node.dataset.styleDisplay = node.style.display;
	      node.style.display = 'none';
	    }
	    if (this.items.has(node.dataset.id) && node.dataset.type === ItemUserFavorites.code) {
	      const item = this.items.get(node.dataset.id);
	      item.storage.push(ItemUserFavorites.code);
	      item.container.dataset.storage = item.storage.join(',');
	      node = item.container;
	    } else {
	      this.container.appendChild(node);
	      this.registerItem(node);
	      babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]();
	    }
	    if (animateFromPoint) {
	      babelHelpers.classPrivateFieldLooseBase(this, _animateTopItemToLeft)[_animateTopItemToLeft](node, animateFromPoint).then(() => {
	        node.style.display = node.dataset.styleDisplay;
	      });
	    }
	  }
	  updateItem(data) {
	    let {
	      id
	    } = data;
	    if (this.items.has(id)) {
	      this.items.get(id).update(data);
	    }
	  }
	  deleteItem(item, {
	    animate
	  }) {
	    this.items.delete(item.getId());
	    babelHelpers.classPrivateFieldLooseBase(this, _activeItem)[_activeItem].checkAndUnset(item);
	    if (item instanceof ItemUserFavorites || animate) {
	      babelHelpers.classPrivateFieldLooseBase(this, _animateTopItemFromLeft)[_animateTopItemFromLeft](item.container).then(() => {
	        item.container.parentNode.removeChild(item.container);
	        babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]();
	      });
	    } else if (item.container) {
	      item.container.parentNode.removeChild(item.container);
	      babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort]();
	    }
	  }
	  convertItem(item) {
	    this.unregisterItem(item);
	    this.registerItem(this.parentContainer.querySelector(`li.menu-item-block[data-id="${item.getId()}"]`));
	  }
	  getActiveItem() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _activeItem)[_activeItem].item;
	  }
	  export() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getItemsToSave)[_getItemsToSave]();
	  }

	  //region DropdownActions

	  openItemMenu(item, target, currentTarget) {
	    if (currentTarget) {
	      main_core.Dom.addClass(currentTarget, '--open');
	    } else {
	      main_core.Dom.addClass(target, '--open');
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _openItemMenuPopup)[_openItemMenuPopup]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _openItemMenuPopup)[_openItemMenuPopup].close();
	    }
	    const contextMenuItems = [];
	    // region hide/show item

	    if (item instanceof ItemMainPage) {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage('MENU_OPEN_SETTINGS_MAIN_PAGE'),
	        onclick: () => {
	          item.openSettings();
	        }
	      });
	    } else if (item.container.getAttribute("data-status") === "show") {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("hide_item"),
	        onclick: () => {
	          this.hideItem(item);
	        }
	      });
	    } else {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("show_item"),
	        onclick: (target, contextMenuItem) => {
	          this.showItem(item);
	        }
	      });
	    }
	    //endregion

	    //region set as main page
	    if (!Options.isExtranet && !Options.isMainPageEnabled && !(item instanceof ItemUserSelf) && !(item instanceof ItemGroup) && this.container.querySelector('li.menu-item-block[data-role="item"]') !== item.container) {
	      contextMenuItems.push({
	        text: main_core.Loc.getMessage("MENU_SET_MAIN_PAGE"),
	        onclick: () => {
	          this.setItemAsAMainPage(item);
	        }
	      });
	    }
	    //endregion

	    item.getDropDownActions().forEach(actionItem => {
	      contextMenuItems.push(actionItem);
	    });
	    if (!(item instanceof ItemMainPage)) {
	      contextMenuItems.push({
	        text: babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] ? main_core.Loc.getMessage("MENU_EDIT_READY_FULL") : main_core.Loc.getMessage("MENU_SETTINGS_MODE"),
	        onclick: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _isEditMode)[_isEditMode] ? this.switchToViewMode() : this.switchToEditMode();
	        }
	      });
	    }
	    contextMenuItems.forEach(item => {
	      var _item$className;
	      item['className'] = ["menu-popup-no-icon", (_item$className = item['className']) != null ? _item$className : ''].join(' ');
	      const {
	        onclick
	      } = item;
	      item['onclick'] = (event, item) => {
	        item.getMenuWindow().close();
	        onclick.call(event, item);
	      };
	    });
	    const targetPosition = main_core.Dom.getPosition(target);
	    babelHelpers.classPrivateFieldLooseBase(this, _openItemMenuPopup)[_openItemMenuPopup] = new main_popup.Menu({
	      bindElement: {
	        top: targetPosition.top - targetPosition.height,
	        left: targetPosition.right
	      },
	      items: contextMenuItems,
	      offsetTop: 0,
	      offsetLeft: main_core.Dom.getPosition(target).width / 2,
	      events: {
	        onClose: () => {
	          main_core_events.EventEmitter.emit(this, Options.eventName('onClose'));
	          item.container.classList.remove('menu-item-block-hover');
	          babelHelpers.classPrivateFieldLooseBase(this, _openItemMenuPopup)[_openItemMenuPopup] = null;
	          if (currentTarget) {
	            main_core.Dom.removeClass(currentTarget, '--open');
	          } else {
	            main_core.Dom.removeClass(target, '--open');
	          }
	        },
	        onShow: () => {
	          item.container.classList.add('menu-item-block-hover');
	          main_core_events.EventEmitter.emit(this, Options.eventName('onShow'));
	        }
	      }
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _openItemMenuPopup)[_openItemMenuPopup].show();
	  }
	  //endregion

	  //region Visible sliding

	  /*endregion*/
	}
	function _showHiddenContainer2(animate) {
	  main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsVisible'));
	  if (animate === false) {
	    return this.hiddenContainer.classList.add('menu-item-favorites-more-open');
	  }
	  this.hiddenContainer.style.height = "0px";
	  this.hiddenContainer.style.opacity = 0;
	  babelHelpers.classPrivateFieldLooseBase(this, _animation)[_animation](true, this.hiddenContainer, this.hiddenContainer.scrollHeight);
	}
	function _hideHiddenContainer2(animate) {
	  main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsHidden'));
	  if (animate === false) {
	    return this.hiddenContainer.classList.remove('menu-item-favorites-more-open');
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _animation)[_animation](false, this.hiddenContainer, this.hiddenContainer.offsetHeight);
	}
	function _animation2(opening, hiddenBlock, maxHeight) {
	  hiddenBlock.style.overflow = "hidden";
	  new BX.easing({
	    duration: 200,
	    start: {
	      opacity: opening ? 0 : 100,
	      height: opening ? 0 : maxHeight
	    },
	    finish: {
	      opacity: opening ? 100 : 0,
	      height: opening ? maxHeight : 0
	    },
	    transition: BX.easing.transitions.linear,
	    step: function (state) {
	      hiddenBlock.style.opacity = state.opacity / 100;
	      hiddenBlock.style.height = state.height + "px";
	    },
	    complete: function () {
	      if (opening) {
	        hiddenBlock.classList.add('menu-item-favorites-more-open');
	      } else {
	        hiddenBlock.classList.remove('menu-item-favorites-more-open');
	      }
	      hiddenBlock.style.opacity = "";
	      hiddenBlock.style.overflow = "";
	      hiddenBlock.style.height = "";
	    }
	  }).animate();
	}
	function _recalculateCounters2(item) {
	  let counterValue = 0;
	  const counter = ui_cnt.Counter.initFromCounterNode(item.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`));
	  if (counter) {
	    counterValue = counter.getRealValue();
	  }
	  if (counterValue <= 0) {
	    return;
	  }
	  [...this.items.entries()].forEach(([id, itemGroup]) => {
	    if (itemGroup instanceof ItemGroup) {
	      itemGroup.updateCounter();
	    }
	  });
	  let hiddenCounterValue = 0;
	  [...this.parentContainer.querySelectorAll(`.menu-item-block[data-status="hide"][data-role='item']`)].forEach(node => {
	    const hiddenMenuItemCounter = ui_cnt.Counter.initFromCounterNode(node.querySelector(`.${ui_cnt.Counter.BaseClassname}`));
	    if (hiddenMenuItemCounter) {
	      hiddenCounterValue += hiddenMenuItemCounter.getRealValue();
	    }
	  });
	  ui_cnt.Counter.updateCounterNodeValue(ui_cnt.Counter.initFromCounterNode(this.parentContainer.querySelector('#menu-hidden-counter')), hiddenCounterValue);
	}
	function _refreshActivity2(item, oldParent) {
	  if (this.getActiveItem() !== item) {
	    return;
	  }
	  const newParent = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	  if (oldParent !== newParent) {
	    if (oldParent instanceof ItemGroup) {
	      oldParent.markAsInactive();
	    }
	    if (newParent instanceof ItemGroup) {
	      newParent.markAsActive();
	    }
	  }
	}
	function _getItemsByCounterId2(counterId) {
	  const result = [];
	  [...this.items.values()].forEach(item => {
	    const counter = ui_cnt.Counter.initFromCounterNode(item.container.querySelector(`.${ui_cnt.Counter.BaseClassname}`));
	    if (counter && counter.getId().includes(counterId)) {
	      result.push(item);
	    }
	  });
	  return result;
	}
	function _getItemsToSave2() {
	  const saveSortItems = {
	    show: [],
	    hide: []
	  };
	  const customItems = [];
	  let firstItemLink = null;
	  ['show', 'hide'].forEach(state => {
	    let items = saveSortItems[state];
	    let currentGroupId = null;
	    const chain = [];
	    Array.from(this.parentContainer.querySelectorAll(`.menu-item-block[data-status="${state}"]`)).forEach(node => {
	      if (node.dataset.role === 'group') {
	        const groupId = node.parentNode.hasAttribute('data-group-id') ? node.parentNode.getAttribute('data-group-id') : null;
	        items = saveSortItems[state];
	        let groupItem;
	        while (groupItem = chain.pop()) {
	          if (groupItem['group_id'] === groupId) {
	            chain.push(groupItem);
	            items = groupItem.items;
	            break;
	          }
	        }
	        const item = {
	          group_id: node.dataset.id,
	          items: []
	        };
	        items.push(item);
	        chain.push(item);
	        items = item.items;
	        currentGroupId = node.dataset.id;
	      } else {
	        if ([ItemAdminCustom.code, ItemUserSelf.code, ItemUserFavorites.code].indexOf(node.getAttribute('data-type')) >= 0) {
	          const item = {
	            ID: node.getAttribute('data-id'),
	            LINK: node.getAttribute('data-link'),
	            TEXT: main_core.Text.decode(node.querySelector("[data-role='item-text']").innerHTML)
	          };
	          if (node.getAttribute("data-new-page") === "Y") {
	            item.NEW_PAGE = "Y";
	          }
	          customItems.push(item);
	        }
	        if (firstItemLink === null && main_core.Type.isStringFilled(node.getAttribute("data-link"))) {
	          firstItemLink = node.getAttribute("data-link");
	        }
	        if (node.closest(`[data-group-id="${currentGroupId}"][data-role="group-content"]`)) {
	          items.push(node.dataset.id);
	        } else {
	          const groupId = node.parentNode.hasAttribute('data-group-id') ? node.parentNode.getAttribute('data-group-id') : null;
	          items = saveSortItems[state];
	          let groupItem;
	          while (groupItem = chain.pop()) {
	            if (groupItem['group_id'] === groupId) {
	              chain.push(groupItem);
	              items = groupItem.items;
	              break;
	            }
	          }
	          items.push(node.dataset.id);
	        }
	      }
	    });
	  });
	  return {
	    saveSortItems,
	    firstItemLink,
	    customItems
	  };
	}
	function _saveItemsSort2(analyticsLabel) {
	  const {
	    saveSortItems,
	    firstItemLink
	  } = babelHelpers.classPrivateFieldLooseBase(this, _getItemsToSave)[_getItemsToSave]();
	  Backend.saveItemsSort(saveSortItems, firstItemLink, analyticsLabel || {
	    action: 'sortItem'
	  });
	}
	function _getParentItemFor2(item) {
	  if (!(item instanceof Item)) {
	    return null;
	  }
	  const parentContainer = item.container.closest('[data-role="group-content"]');
	  if (parentContainer && this.items.has(parentContainer.getAttribute('data-group-id'))) {
	    return this.items.get(parentContainer.getAttribute('data-group-id'));
	  }
	  return null;
	}
	function _canChangePaternity2(item) {
	  if (item instanceof ItemGroup) {
	    return false;
	  }
	  const oldParent = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	  if (oldParent instanceof ItemGroup && item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1) {
	    return false;
	  }
	  return true;
	}
	function _animateTopItemToLeft2(node, animateFromPoint) {
	  return new Promise(resolve => {
	    let {
	      startX,
	      startY
	    } = animateFromPoint;
	    const topMenuNode = document.createElement('DIV');
	    topMenuNode.style = `position: absolute; z-index: 1000; top: ${startY + 25}px;`;
	    const cloneNode = node.cloneNode(true);
	    cloneNode.style.display = node.dataset.styleDisplay;
	    document.body.appendChild(topMenuNode);
	    topMenuNode.appendChild(cloneNode);
	    let finishY = this.hiddenContainer.getBoundingClientRect().top;
	    new BX.easing({
	      duration: 500,
	      start: {
	        left: startX
	      },
	      finish: {
	        left: 30
	      },
	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function (state) {
	        topMenuNode.style.left = state.left + "px";
	      },
	      complete: () => {
	        new BX.easing({
	          duration: 500,
	          start: {
	            top: startY + 25
	          },
	          finish: {
	            top: finishY
	          },
	          transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	          step: function (state) {
	            topMenuNode.style.top = state.top + "px";
	          },
	          complete: () => {
	            main_core.Dom.remove(topMenuNode);
	            resolve();
	          }
	        }).animate();
	      }
	    }).animate();
	  });
	}
	function _animateTopItemFromLeft2(node) {
	  return new Promise(resolve => {
	    new BX.easing({
	      duration: 700,
	      start: {
	        left: node.offsetLeft,
	        opacity: 1
	      },
	      finish: {
	        left: 400,
	        opacity: 0
	      },
	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function (state) {
	        node.style.paddingLeft = state.left + "px";
	        node.style.opacity = state.opacity;
	      },
	      complete: function () {
	        resolve();
	      }
	    }).animate();
	  });
	}
	function _registerDND2(item) {
	  //drag&drop
	  jsDD.Enable();
	  item.container.onbxdragstart = babelHelpers.classPrivateFieldLooseBase(this, _menuItemDragStart)[_menuItemDragStart].bind(this, item);
	  item.container.onbxdrag = (x, y) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemDragMove)[_menuItemDragMove]( /*item,*/x, y);
	  };
	  item.container.onbxdraghover = (dest, x, y) => {
	    babelHelpers.classPrivateFieldLooseBase(this, _menuItemDragHover)[_menuItemDragHover]( /*item, */dest, x, y);
	  };
	  item.container.onbxdragstop = babelHelpers.classPrivateFieldLooseBase(this, _menuItemDragStop)[_menuItemDragStop].bind(this, item);
	  jsDD.registerObject(item.container);
	}
	function _menuItemDragStart2(item) {
	  main_core_events.EventEmitter.emit(main_core_events.EventEmitter.GLOBAL_TARGET, 'BX.Bitrix24.LeftMenuClass:onDragStart');
	  if (!(item instanceof ItemGroup) && item.container.parentNode.querySelectorAll('li.menu-item-block').length <= 1 && babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item) !== null) {
	    item = babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item);
	  }
	  main_core_events.EventEmitter.emit(this, Options.eventName('onDragModeOn'));
	  this.dnd = {
	    container: this.container.parentNode,
	    itemDomBlank: main_core.Dom.create('div', {
	      style: {
	        display: 'none'
	        // border: '2px solid navy'
	      }
	    }),

	    itemMoveBlank: main_core.Dom.create('div', {
	      style: {
	        height: item.container.offsetHeight + 'px'
	        // border: '2px solid red',
	      }
	    }),

	    draggableBlock: main_core.Dom.create('div', {
	      //div to move
	      attrs: {
	        className: "menu-draggable-wrap"
	      },
	      style: {
	        top: [item.container.offsetTop - item.container.offsetHeight, 'px'].join('')
	        // border: '2px solid black'
	      }
	    }),

	    item: item,
	    oldParent: babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](item),
	    isHiddenContainerVisible: this.isHiddenContainerVisible()
	  };
	  babelHelpers.classPrivateFieldLooseBase(this, _showHiddenContainer)[_showHiddenContainer](false);
	  const registerItems = () => {
	    [...this.parentContainer.querySelectorAll('li.menu-item-block')].forEach(node => {
	      if (item instanceof ItemGroup && babelHelpers.classPrivateFieldLooseBase(this, _getParentItemFor)[_getParentItemFor](this.items.get(node.getAttribute('data-id'))) !== null) {
	        return;
	      }
	      jsDD.registerDest(node, 100);
	    });
	    const firstNode = this.parentContainer.querySelector("#left-menu-empty-item");
	    if (item instanceof ItemUserSelf) {
	      jsDD.unregisterDest(firstNode);
	    } else {
	      jsDD.registerDest(firstNode, 100);
	    }
	    jsDD.registerDest(this.parentContainer.querySelector("#left-menu-hidden-empty-item"), 100);
	    jsDD.registerDest(this.parentContainer.querySelector("#left-menu-hidden-separator"), 100);
	  };
	  if (item instanceof ItemGroup) {
	    item.collapse(true).then(() => {
	      if (this.dnd) {
	        this.dnd.pos = BX.pos(this.container.parentNode);
	        registerItems();
	      }
	    });
	  } else {
	    registerItems();
	  }
	  const dragElement = item.container;
	  main_core.Dom.addClass(this.dnd.container, "menu-drag-mode");
	  main_core.Dom.addClass(dragElement, "menu-item-draggable");
	  dragElement.parentNode.insertBefore(this.dnd.itemDomBlank, dragElement); //remember original item place
	  dragElement.parentNode.insertBefore(this.dnd.itemMoveBlank, dragElement); //empty div
	  this.dnd.draggableBlock.appendChild(item.container);
	  this.dnd.container.style.position = 'relative';
	  this.dnd.container.appendChild(this.dnd.draggableBlock);
	  this.dnd.pos = BX.pos(this.container.parentNode);
	}
	function _menuItemDragMove2( /*item,*/x, y) {
	  const item = this.dnd.item;
	  var menuItemsBlockHeight = this.dnd.pos.height;
	  y = Math.max(0, y - this.dnd.pos.top);
	  this.dnd.draggableBlock.style.top = [Math.min(menuItemsBlockHeight - item.container.offsetHeight, y) - 5, 'px'].join('');
	}
	function _menuItemDragHover2( /*item, */dest, x, y) {
	  const item = this.dnd.item;
	  const dragElement = item.container;
	  if (dest === dragElement) {
	    this.dnd.itemDomBlank.parentNode.insertBefore(this.dnd.itemMoveBlank, this.dnd.itemDomBlank);
	    return;
	  }
	  if (dest.id === "left-menu-empty-item" && (dragElement.getAttribute("data-type") === "self" || dragElement.getAttribute("data-disable-first-item") === "Y")) {
	    return; // self-item cannot be moved on the first place
	  }

	  if (dest.getAttribute('data-role') === 'group') {
	    const groupHolder = dest.parentNode.querySelector(`[data-group-id="${dest.getAttribute('data-id')}"]`);
	    if (dest.getAttribute('data-collapse-mode') === 'collapsed') {
	      main_core.Dom.insertAfter(this.dnd.itemMoveBlank, groupHolder);
	    } else if (item instanceof ItemGroup) {
	      main_core.Dom.insertBefore(this.dnd.itemMoveBlank, dest);
	    } else {
	      main_core.Dom.prepend(this.dnd.itemMoveBlank, groupHolder.querySelector('ul'));
	    }
	  } else if (this.dnd.container.contains(dest)) {
	    let itemPlaceHolder = dest;
	    if (item instanceof ItemGroup && dest.closest('[data-role="group-content"]')) {
	      itemPlaceHolder = dest.closest('[data-role="group-content"]');
	    }
	    main_core.Dom.insertAfter(this.dnd.itemMoveBlank, itemPlaceHolder);
	  }
	}
	function _menuItemDragStop2() {
	  const item = this.dnd.item;
	  const oldParent = this.dnd.oldParent;
	  const dragElement = item.container;
	  main_core.Dom.removeClass(this.dnd.container, "menu-drag-mode");
	  main_core.Dom.removeClass(dragElement, "menu-item-draggable");
	  this.dnd.container.style.position = '';
	  let error = null;
	  let onHiddenBlockIsEmptyEmitted = false;
	  if (this.parentContainer.querySelector('.menu-item-block') === item.container) {
	    if (item instanceof ItemUserSelf) {
	      error = main_core.Loc.getMessage('MENU_SELF_ITEM_FIRST_ERROR');
	    } else if (item.container.getAttribute("data-disable-first-item") === "Y") {
	      error = main_core.Loc.getMessage("MENU_FIRST_ITEM_ERROR");
	    }
	  }
	  if (error !== null) {
	    this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	    item.showMessage(error);
	  } else if (!this.dnd.container.contains(this.dnd.itemMoveBlank)) {
	    this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	  } else {
	    try {
	      this.dnd.itemMoveBlank.parentNode.replaceChild(dragElement, this.dnd.itemMoveBlank);
	      if (this.hiddenContainer.contains(dragElement)) {
	        item.container.setAttribute("data-status", "hide");
	        if (this.dnd.itemDomBlank.closest('#left-menu-hidden-items-block') === null && this.hiddenContainer.querySelectorAll('.menu-item-block').length === 1) {
	          main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsNotEmpty'));
	        }
	      } else {
	        item.container.setAttribute("data-status", "show");
	        if (this.hiddenContainer.querySelectorAll('.menu-item-block').length <= 0) {
	          onHiddenBlockIsEmptyEmitted = true;
	          main_core_events.EventEmitter.emit(this, Options.eventName('onHiddenBlockIsEmpty'));
	        }
	      }
	      if (item instanceof ItemGroup) {
	        item.checkAndCorrect().expand(true);
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _refreshActivity)[_refreshActivity](item, oldParent);
	      babelHelpers.classPrivateFieldLooseBase(this, _recalculateCounters)[_recalculateCounters](item);
	      const analyticsLabel = {
	        action: 'sortItem'
	      };
	      if (this.parentContainer.querySelector('.menu-item-block') === item.container && !this.isExtranet) {
	        item.showMessage(main_core.Loc.getMessage("MENU_ITEM_MAIN_PAGE"));
	        analyticsLabel.action = 'mainPage';
	        analyticsLabel.itemId = item.getId();
	      }
	      babelHelpers.classPrivateFieldLooseBase(this, _saveItemsSort)[_saveItemsSort](analyticsLabel);
	    } catch (e) {
	      this.dnd.itemDomBlank.parentNode.replaceChild(dragElement, this.dnd.itemDomBlank);
	    }
	  }
	  main_core.Dom.remove(this.dnd.draggableBlock);
	  main_core.Dom.remove(this.dnd.itemDomBlank);
	  main_core.Dom.remove(this.dnd.itemMoveBlank);
	  jsDD.enableDest(dragElement);
	  this.container.style.position = 'static';
	  if (!this.dnd.isHiddenContainerVisible || onHiddenBlockIsEmptyEmitted === true) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hideHiddenContainer)[_hideHiddenContainer](false);
	  }
	  delete this.dnd;
	  [...this.parentContainer.querySelectorAll('li.menu-item-block')].forEach(node => {
	    jsDD.registerDest(node);
	  });
	  const firstNode = this.parentContainer.querySelector("#left-menu-empty-item");
	  jsDD.unregisterDest(firstNode);
	  jsDD.unregisterDest(this.parentContainer.querySelector("#left-menu-hidden-empty-item"));
	  jsDD.unregisterDest(this.parentContainer.querySelector("#left-menu-hidden-separator"));
	  jsDD.refreshDestArea();
	  main_core_events.EventEmitter.emit(this, Options.eventName('onDragModeOff'));
	}

	class ItemDirector extends DefaultController {
	  constructor(container, params) {
	    super(container, params);
	  }
	  saveCurrentPage(page) {
	    return ItemUserFavorites.saveCurrentPage(page).then(data => {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemHasBeenAdded'), data);
	      return data;
	    }).catch(Utils.catchError);
	  }
	  saveStandardPage(topItem) {
	    return ItemUserFavorites.saveStandardPage(topItem).then(data => {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemHasBeenAdded'), data);
	      return data;
	    }).catch(Utils.catchError);
	  }
	  deleteCurrentPage({
	    pageLink
	  }) {
	    return ItemUserFavorites.deleteCurrentPage({
	      pageLink
	    }).then(data => {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemHasBeenDeleted'), data);
	      return data;
	    }).catch(Utils.catchError);
	  }
	  deleteStandardPage(topItem) {
	    return ItemUserFavorites.deleteStandardPage(topItem).then(data => {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemHasBeenDeleted'), data);
	      return data;
	    }).catch(Utils.catchError);
	  }
	  showAddToSelf(bindElement) {
	    ItemUserSelf.showAdd(bindElement).then(data => {
	      main_core_events.EventEmitter.emit(this, Options.eventName('onItemHasBeenAdded'), data);
	    }).catch(Utils.catchError);
	  }
	}

	var _isAdmin = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isAdmin");
	class Analytics {
	  constructor(isAdmin) {
	    Object.defineProperty(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin] = isAdmin ? AnalyticUserRole.ADMIN : AnalyticUserRole.NOT_ADMIN;
	  }
	  sendSetCustomPreset() {
	    ui_analytics.sendData({
	      tool: AnalyticTool,
	      category: AnalyticCategory.MENU,
	      event: AnalyticEvent.SET,
	      type: 'custom',
	      c_section: AnalyticSection.MENU,
	      p1: babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin]
	    });
	  }
	  sendSetPreset(presetId, isPersonal, action) {
	    ui_analytics.sendData({
	      type: presetId,
	      event: isPersonal ? AnalyticEvent.CHANGE : AnalyticEvent.SELECT,
	      tool: AnalyticTool,
	      category: isPersonal ? AnalyticCategory.MENU : AnalyticCategory.TOOL,
	      c_section: isPersonal ? AnalyticSection.MENU : AnalyticSection.POPUP,
	      c_element: action,
	      p1: babelHelpers.classPrivateFieldLooseBase(this, _isAdmin)[_isAdmin]
	    });
	  }
	  sendClose() {
	    ui_analytics.sendData({
	      event: AnalyticEvent.SHOW,
	      tool: AnalyticTool,
	      category: AnalyticCategory.TOOL,
	      c_section: AnalyticSection.POPUP
	    });
	  }
	}
	const AnalyticCategory = {
	  TOOL: 'main_tool',
	  MENU: 'main_menu'
	};
	const AnalyticEvent = {
	  SHOW: 'window_show',
	  SELECT: 'select',
	  CHANGE: 'change',
	  SET: 'menu_set'
	};
	const AnalyticUserRole = {
	  ADMIN: 'isAdmin_Y',
	  NOT_ADMIN: 'isAdmin_N'
	};
	const AnalyticSection = {
	  POPUP: 'menu_popup',
	  PRESET: 'preset',
	  QUALIFICATION: 'qualification',
	  SETTINGS: 'settings',
	  MENU: 'main_menu'
	};
	const AnalyticActions = {
	  CONFIRM: 'confirm',
	  LATER: 'later',
	  CLOSE: 'close',
	  SAVE: 'save',
	  SKIP: 'skip'
	};
	const AnalyticTool = 'intranet';

	let _$2 = t => t,
	  _t$2,
	  _t2$1,
	  _t3$1,
	  _t4$1,
	  _t5,
	  _t6,
	  _t7,
	  _t8;
	var _refs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refs");
	var _status = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("status");
	var _xhr = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("xhr");
	var _isExtranetInstalled = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isExtranetInstalled");
	var _handleGroupsLinkClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleGroupsLinkClick");
	var _loadContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadContent");
	var _handleFilterClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleFilterClick");
	var _handleItemsClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleItemsClick");
	var _animateStart = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animateStart");
	var _animateCounter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("animateCounter");
	class GroupPanel {
	  constructor(options) {
	    Object.defineProperty(this, _animateCounter, {
	      value: _animateCounter2
	    });
	    Object.defineProperty(this, _animateStart, {
	      value: _animateStart2
	    });
	    Object.defineProperty(this, _handleItemsClick, {
	      value: _handleItemsClick2
	    });
	    Object.defineProperty(this, _handleFilterClick, {
	      value: _handleFilterClick2
	    });
	    Object.defineProperty(this, _loadContent, {
	      value: _loadContent2
	    });
	    Object.defineProperty(this, _handleGroupsLinkClick, {
	      value: _handleGroupsLinkClick2
	    });
	    Object.defineProperty(this, _refs, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _status, {
	      writable: true,
	      value: 'initial'
	    });
	    Object.defineProperty(this, _xhr, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _isExtranetInstalled, {
	      writable: true,
	      value: true
	    });
	    const groupsLink = document.getElementById('menu-all-groups-link');
	    main_core.Event.bind(groupsLink, 'click', babelHelpers.classPrivateFieldLooseBase(this, _handleGroupsLinkClick)[_handleGroupsLinkClick].bind(this));
	    babelHelpers.classPrivateFieldLooseBase(this, _isExtranetInstalled)[_isExtranetInstalled] = main_core.Type.isBoolean(options.isExtranetInstalled) ? options.isExtranetInstalled : babelHelpers.classPrivateFieldLooseBase(this, _isExtranetInstalled)[_isExtranetInstalled];
	  }
	  getContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('container', () => {
	      return main_core.Tag.render(_t$2 || (_t$2 = _$2`
				<div class="group-panel-content">
					<div class="group-panel-header">
						${0}
					</div>
					${0}
				</div>
			`), this.getFilterContainer(), this.getItemsContainer());
	    });
	  }
	  getItemsContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('items-container', () => {
	      return main_core.Tag.render(_t2$1 || (_t2$1 = _$2`
				<div class="group-panel-items" onclick="${0}"></div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _handleItemsClick)[_handleItemsClick].bind(this));
	    });
	  }
	  getFilterContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('filter-container', () => {
	      return main_core.Tag.render(_t3$1 || (_t3$1 = _$2`
				<span class="group-panel-header-filters">${0}
				</span>
			`), [main_core.Tag.render(_t4$1 || (_t4$1 = _$2`
						<span
							class="group-panel-header-filter group-panel-header-filter-all"
							data-filter="all"
							onclick="${0}"
						>${0}</span>
					`), babelHelpers.classPrivateFieldLooseBase(this, _handleFilterClick)[_handleFilterClick].bind(this), main_core.Loc.getMessage('MENU_MY_WORKGROUPS')), babelHelpers.classPrivateFieldLooseBase(this, _isExtranetInstalled)[_isExtranetInstalled] ? main_core.Tag.render(_t5 || (_t5 = _$2`
								<span
									class="group-panel-header-filter group-panel-header-filter-extranet"
									data-filter="extranet"
									onclick="${0}"
								>${0}</span>
							`), babelHelpers.classPrivateFieldLooseBase(this, _handleFilterClick)[_handleFilterClick].bind(this), main_core.Loc.getMessage('MENU_MY_WORKGROUPS_EXTRANET')) : null, main_core.Tag.render(_t6 || (_t6 = _$2`
						<span
							class="group-panel-header-filter group-panel-header-filter-favorites"
							data-filter="favorites"
							onclick="${0}"
						>${0}${0}</span>
					`), babelHelpers.classPrivateFieldLooseBase(this, _handleFilterClick)[_handleFilterClick].bind(this), main_core.Loc.getMessage('MENU_MY_WORKGROUPS_FAVORITES'), this.getCounterContainer())]);
	    });
	  }
	  getCounterContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('counter-container', () => {
	      return main_core.Tag.render(_t7 || (_t7 = _$2`<span class="group-panel-header-filter-counter"></span>`));
	    });
	  }
	  saveFilter(filter) {
	    void main_core.ajax.runAction('intranet.leftmenu.setGroupFilter', {
	      data: {
	        filter
	      }
	    });
	  }
	}
	function _handleGroupsLinkClick2(event) {
	  main_sidepanel.SidePanel.Instance.open('my-groups', {
	    cacheable: false,
	    contentCallback: () => {
	      return main_core.Runtime.loadExtension('ui.sidepanel.layout').then(exports => {
	        const {
	          Layout
	        } = exports;
	        return Layout.createContent({
	          title: main_core.Loc.getMessage('MENU_MY_WORKGROUPS'),
	          design: {
	            section: true,
	            margin: true
	          },
	          buttons: () => [],
	          content: () => {
	            if (babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] === 'loaded') {
	              return this.getContainer();
	            }
	            return babelHelpers.classPrivateFieldLooseBase(this, _loadContent)[_loadContent]();
	          }
	        });
	      });
	    },
	    events: {
	      onClose: () => {
	        if (babelHelpers.classPrivateFieldLooseBase(this, _xhr)[_xhr] && babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] !== 'loaded') {
	          babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] = 'initial';
	          babelHelpers.classPrivateFieldLooseBase(this, _xhr)[_xhr].abort();
	        }
	      }
	    }
	  });
	}
	async function _loadContent2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] = 'loading';
	  const response = await main_core.ajax.runAction('intranet.leftmenu.getGroups', {
	    onrequeststart: xhr => {
	      babelHelpers.classPrivateFieldLooseBase(this, _xhr)[_xhr] = xhr;
	    }
	  });
	  const {
	    groups,
	    filter
	  } = response.data;
	  for (const group of groups) {
	    const classes = ['group-panel-item'];
	    classes.push(group.extranet ? 'group-panel-item-extranet' : 'group-panel-item-intranet');
	    if (group.favorite) {
	      classes.push('group-panel-item-favorite');
	    }
	    const dom = main_core.Tag.render(_t8 || (_t8 = _$2`
				<a href="${0}" 
					class="${0}" 
					data-id="${0}" 
					data-slider-ignore-autobinding="true"
				>
					<span class="group-panel-item-text" title="${0}">${0}</span>
					<span class="group-panel-item-star"></span>
				</a>
			`), encodeURI(group.url), classes.join(' '), group.id, main_core.Text.encode(group.title), main_core.Text.encode(group.title));
	    main_core.Dom.append(dom, this.getItemsContainer());
	  }
	  main_core.Dom.addClass(this.getContainer(), `group-panel-content-${filter}`);
	  main_core.Dom.attr(this.getContainer(), {
	    'data-filter': filter
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _status)[_status] = 'loaded';
	  return this.getContainer();
	}
	function _handleFilterClick2(event) {
	  const filterElement = event.target;
	  const currentFilter = this.getContainer().dataset.filter || 'all';
	  const newFilter = filterElement.dataset.filter || 'all';
	  if (currentFilter !== newFilter) {
	    this.getContainer().dataset.filter = newFilter;
	    this.saveFilter(newFilter);
	    new BX.easing({
	      duration: 50,
	      start: {
	        opacity: 1
	      },
	      finish: {
	        opacity: 0
	      },
	      transition: BX.easing.transitions.linear,
	      step: state => {
	        main_core.Dom.style(this.getItemsContainer(), 'opacity', state.opacity / 100);
	      },
	      complete: () => {
	        main_core.Dom.removeClass(this.getContainer(), `group-panel-content-${currentFilter}`);
	        main_core.Dom.addClass(this.getContainer(), `group-panel-content-${newFilter}`);
	        new BX.easing({
	          duration: 50,
	          start: {
	            opacity: 0
	          },
	          finish: {
	            opacity: 1
	          },
	          transition: BX.easing.transitions.linear,
	          step: state => {
	            main_core.Dom.style(this.getItemsContainer(), 'opacity', state.opacity / 100);
	          },
	          complete: () => {
	            main_core.Dom.style(this.getItemsContainer(), 'opacity', null);
	          }
	        }).animate();
	      }
	    }).animate();
	  }
	  event.stopPropagation();
	}
	function _handleItemsClick2(event) {
	  if (!main_core.Dom.hasClass(event.target, 'group-panel-item-star')) {
	    return;
	  }
	  const star = event.target;
	  const item = star.parentNode;
	  const groupId = item.dataset.id;
	  const action = main_core.Dom.hasClass(item, 'group-panel-item-favorite') ? 'removeFromFavorites' : 'addToFavorites';
	  main_core.Dom.toggleClass(item, 'group-panel-item-favorite');
	  babelHelpers.classPrivateFieldLooseBase(this, _animateStart)[_animateStart](star);
	  babelHelpers.classPrivateFieldLooseBase(this, _animateCounter)[_animateCounter](action === 'addToFavorites');
	  void main_core.ajax.runAction(`intranet.leftmenu.${action}`, {
	    data: {
	      groupId
	    }
	  });
	  event.preventDefault();
	}
	function _animateStart2(star) {
	  const flyingStar = star.cloneNode();
	  main_core.Dom.style(flyingStar, 'margin-left', `-${star.offsetWidth}px`);
	  main_core.Dom.append(flyingStar, star.parentNode);
	  new BX.easing({
	    duration: 200,
	    start: {
	      opacity: 100,
	      scale: 100
	    },
	    finish: {
	      opacity: 0,
	      scale: 300
	    },
	    step: state => {
	      main_core.Dom.style(flyingStar, 'transform', `scale(${state.scale / 100})`);
	      main_core.Dom.style(flyingStar, 'opacity', state.opacity / 100);
	    },
	    complete: () => {
	      flyingStar.parentNode.removeChild(flyingStar);
	    }
	  }).animate();
	}
	function _animateCounter2(positive) {
	  this.getCounterContainer().innerHTML = positive === false ? '-1' : '+1';
	  new BX.easing({
	    duration: 400,
	    start: {
	      opacity: 100,
	      top: 0
	    },
	    finish: {
	      opacity: 0,
	      top: -20
	    },
	    transition: BX.easing.transitions.linear,
	    step: state => {
	      main_core.Dom.style(this.getCounterContainer(), 'top', `${state.top}px`);
	      main_core.Dom.style(this.getCounterContainer(), 'opacity', state.opacity / 100);
	    },
	    complete: () => {
	      main_core.Dom.style(this.getCounterContainer(), 'top', null);
	      main_core.Dom.style(this.getCounterContainer(), 'opacity', null);
	    }
	  }).animate();
	}

	var _getLeftMenuItemByTopMenuItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLeftMenuItemByTopMenuItem");
	var _specialLiveFeedDecrement = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("specialLiveFeedDecrement");
	var _addLicenseButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("addLicenseButton");
	var _getLicenseButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getLicenseButton");
	var _createLicenseButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createLicenseButton");
	class Menu {
	  //

	  constructor(params) {
	    Object.defineProperty(this, _createLicenseButton, {
	      value: _createLicenseButton2
	    });
	    Object.defineProperty(this, _getLicenseButton, {
	      value: _getLicenseButton2
	    });
	    Object.defineProperty(this, _addLicenseButton, {
	      value: _addLicenseButton2
	    });
	    Object.defineProperty(this, _getLeftMenuItemByTopMenuItem, {
	      value: _getLeftMenuItemByTopMenuItem2
	    });
	    this.cache = new main_core.Cache.MemoryCache();
	    this.scrollModeThreshold = 20;
	    this.lastScrollOffset = 0;
	    this.slidingModeTimeoutId = 0;
	    this.isMenuMouseEnterBlocked = false;
	    this.isMenuMouseLeaveBlocked = [];
	    this.isCollapsedMode = false;
	    Object.defineProperty(this, _specialLiveFeedDecrement, {
	      writable: true,
	      value: 0
	    });
	    this.menuContainer = document.getElementById("menu-items-block");
	    if (!this.menuContainer) {
	      return;
	    }
	    params = typeof params === "object" ? params : {};
	    Options.isExtranet = params.isExtranet === 'Y';
	    Options.isMainPageEnabled = params.isMainPageEnabled === 'Y';
	    Options.isAdmin = params.isAdmin;
	    Options.isCustomPresetRestricted = params.isCustomPresetAvailable !== 'Y';
	    Options.availablePresetTools = params.availablePresetTools;
	    Options.settingsPath = params.settingsPath;
	    Options.inviteDialogLink = params.inviteDialogLink;
	    Options.showMarta = params.showMarta;
	    Options.showSitemapMenuItem = params.showSitemapMenuItem;
	    Options.showLicenseButton = params.showLicenseButton;
	    Options.licenseButtonPath = params.licenseButtonPath;
	    this.isCollapsedMode = params.isCollapsedMode;
	    this.analytics = new Analytics(params.isAdmin);
	    this.initAndBindNodes();
	    this.bindEvents();
	    this.getItemsController();
	    babelHelpers.classPrivateFieldLooseBase(this, _addLicenseButton)[_addLicenseButton]();
	    this.groupPanel = new GroupPanel({
	      isExtranetInstalled: params.isExtranetInstalled !== 'N'
	    });

	    // Emulate document scroll because init() can be invoked after page load scroll
	    // (a hard reload with script at the bottom).
	    // this.handleDocumentScroll();
	  }

	  initAndBindNodes() {
	    var _this$menuContainer$q;
	    this.menuContainer.addEventListener("dblclick", this.handleMenuDoubleClick.bind(this));
	    this.menuContainer.addEventListener("mouseenter", this.handleMenuMouseEnter.bind(this));
	    this.menuContainer.addEventListener("mouseleave", this.handleMenuMouseLeave.bind(this));
	    this.menuContainer.addEventListener("transitionend", this.handleSlidingTransitionEnd.bind(this));
	    this.menuHeader = this.menuContainer.querySelector(".menu-items-header");
	    this.menuBody = this.menuContainer.querySelector(".menu-items-body");
	    this.menuItemsBlock = this.menuContainer.querySelector(".menu-items");

	    // document.addEventListener("scroll", this.handleDocumentScroll.bind(this));

	    this.mainTable = document.querySelector(".js-app");
	    this.menuHeaderBurger = this.menuHeader.querySelector(".menu-switcher");
	    this.menuHeaderBurger.addEventListener('click', this.handleBurgerClick.bind(this));
	    this.menuHeader.querySelector(".menu-items-header-title").addEventListener('click', this.handleBurgerClick.bind(this, true));

	    // this.upButton = this.menuContainer.querySelector(".menu-btn-arrow-up");
	    // this.upButton.addEventListener("click", this.handleUpButtonClick.bind(this));
	    this.menuMoreButton = this.menuContainer.querySelector(".menu-item-block.menu-expand");
	    this.menuMoreButton = this.menuContainer.querySelector('[data-role="expand-menu-item"]');
	    this.menuMoreButton.addEventListener("click", this.handleShowHiddenClick.bind(this));
	    const siteMapItem = this.menuContainer.querySelector(".menu-sitemap-btn");
	    if (siteMapItem) {
	      siteMapItem.addEventListener('click', this.handleSiteMapClick.bind(this));
	    }
	    const settingsSaveBtn = this.menuContainer.querySelector(".menu-settings-save-btn");
	    if (settingsSaveBtn) {
	      settingsSaveBtn.addEventListener('click', this.handleViewMode.bind(this));
	    }

	    // this.menuContainer.querySelector(".menu-settings-btn")?.addEventListener('click', () => {
	    // 	this.getSettingsController().show();
	    // });
	    (_this$menuContainer$q = this.menuContainer.querySelector('[data-role="menu-settings-item"]')) == null ? void 0 : _this$menuContainer$q.addEventListener('click', () => {
	      this.getSettingsController().show();
	    });
	  }

	  // region Controllers
	  getItemsController() {
	    return this.cache.remember('itemsController', () => {
	      return new ItemsController(this.menuContainer, {
	        events: {
	          EditMode: () => {
	            this.toggle(true);
	            this.menuContainer.classList.add('menu-items-edit-mode');
	            this.menuContainer.classList.remove('menu-items-view-mode');
	          },
	          ViewMode: () => {
	            this.toggle(true);
	            this.menuContainer.classList.add('menu-items-view-mode');
	            this.menuContainer.classList.remove('menu-items-edit-mode');
	          },
	          onDragModeOn: ({
	            target
	          }) => {
	            this.switchToSlidingMode(true);
	            this.isMenuMouseLeaveBlocked.push('drag');
	          },
	          onDragModeOff: ({
	            target
	          }) => {
	            this.isMenuMouseLeaveBlocked.pop();
	          },
	          onHiddenBlockIsVisible: this.onHiddenBlockIsVisible.bind(this),
	          onHiddenBlockIsHidden: this.onHiddenBlockIsHidden.bind(this),
	          onHiddenBlockIsEmpty: this.onHiddenBlockIsEmpty.bind(this),
	          onHiddenBlockIsNotEmpty: this.onHiddenBlockIsNotEmpty.bind(this),
	          onShow: () => {
	            this.isMenuMouseLeaveBlocked.push('items');
	          },
	          onClose: () => {
	            this.isMenuMouseLeaveBlocked.pop();
	          }
	        }
	      });
	    });
	  }
	  getItemDirector() {
	    return this.cache.remember('itemsCreator', () => {
	      return new ItemDirector(this.menuContainer, {
	        events: {
	          onItemHasBeenAdded: ({
	            data
	          }) => {
	            this.getItemsController().addItem(data);
	          }
	        }
	      });
	    });
	  }
	  getSettingsController() {
	    return this.cache.remember('presetController', () => {
	      const node = this.menuContainer.querySelector('[data-role="menu-settings-item"]');
	      if (!node) {
	        return null;
	      }
	      return new SettingsController(node, {
	        events: {
	          onGettingSettingMenuItems: this.onGettingSettingMenuItems.bind(this),
	          onShow: () => {
	            this.isMenuMouseLeaveBlocked.push('settings');
	          },
	          onClose: () => {
	            this.isMenuMouseLeaveBlocked.pop();
	          }
	        }
	      });
	    });
	  }
	  getCustomPresetController() {
	    return this.cache.remember('customPresetController', () => {
	      return new PresetCustomController(this.menuContainer, {
	        events: {
	          onPresetIsSet: ({
	            data
	          }) => {
	            const {
	              saveSortItems,
	              firstItemLink,
	              customItems
	            } = this.getItemsController().export();
	            if (!data) {
	              this.analytics.sendSetCustomPreset();
	            }
	            return Backend.setCustomPreset(data, saveSortItems, customItems, firstItemLink);
	          },
	          onShow: () => {
	            this.isMenuMouseLeaveBlocked.push('presets');
	          },
	          onClose: () => {
	            this.isMenuMouseLeaveBlocked.pop();
	          }
	        }
	      });
	    });
	  }
	  getDefaultPresetController() {
	    let closeEventWasProcessed = false;
	    const postponeHandler = mode => {
	      const result = Backend.postponeSystemPreset(mode);
	      main_core_events.EventEmitter.emit(this, Options.eventName('onPresetIsPostponed'));
	      return result;
	    };
	    return this.cache.remember('defaultPresetController', () => {
	      const presetController = new PresetDefaultController(this.menuContainer, {
	        events: {
	          onPresetIsSet: ({
	            data: {
	              mode,
	              presetId
	            }
	          }) => {
	            this.analytics.sendSetPreset(presetId, mode === 'personal', AnalyticActions.CONFIRM);
	            closeEventWasProcessed = true;
	            return Backend.setSystemPreset(mode, presetId);
	          },
	          onPresetIsPostponed: ({
	            data: {
	              mode
	            }
	          }) => {
	            this.analytics.sendSetPreset(presetController.getSelectedPreset(), mode === 'personal', AnalyticActions.LATER);
	            closeEventWasProcessed = true;
	            return postponeHandler(mode);
	          },
	          onShow: () => {
	            this.analytics.sendClose();
	          },
	          onClose: () => {
	            if (closeEventWasProcessed !== true) {
	              this.analytics.sendSetPreset(presetController.getSelectedPreset(), presetController.getMode() === 'personal', AnalyticActions.CLOSE);
	              postponeHandler(presetController.getMode());
	            }
	            closeEventWasProcessed = false;
	          }
	        }
	      });
	      return presetController;
	    });
	  }
	  // endregion

	  bindEvents() {
	    // All Counters from IM
	    main_core_events.EventEmitter.subscribe('onImUpdateCounter', event => {
	      const [counters] = event.getCompatData();
	      this.updateCounters(counters, false);
	    });

	    // Messenger counter
	    main_core_events.EventEmitter.subscribe('onImUpdateCounterMessage', event => {
	      const [counter] = event.getCompatData();
	      this.updateCounters({
	        'im-message': counter
	      }, false);
	    });

	    // Live Feed Counter
	    main_core_events.EventEmitter.subscribe('onCounterDecrement', event => {
	      const [decrement] = event.getCompatData();
	      this.decrementCounter(document.getElementById('menu-counter-live-feed'), decrement);
	    });

	    // All Counters
	    main_core_events.EventEmitter.subscribe('onPullEvent-main', event => {
	      const [command, params] = event.getCompatData();
	      if (command === 'user_counter' && params[main_core.Loc.getMessage('SITE_ID')]) {
	        const counters = {
	          ...params[main_core.Loc.getMessage('SITE_ID')]
	        };
	        this.updateCounters(counters, false);
	      }
	    });

	    // just to hold opened menu in collapsing mode when groups are shown
	    BX.addCustomEvent("BX.Bitrix24.GroupPanel:onOpen", this.handleGroupPanelOpen.bind(this));
	    BX.addCustomEvent("BX.Bitrix24.GroupPanel:onClose", this.handleGroupPanelClose.bind(this));

	    // region Top menu integration
	    BX.addCustomEvent('BX.Main.InterfaceButtons:onFirstItemChange', (firstPageLink, firstNode) => {
	      if (!firstPageLink || !main_core.Type.isDomNode(firstNode)) {
	        return;
	      }
	      const topMenuId = firstNode.getAttribute('data-top-menu-id');
	      const leftMenuNode = this.menuBody.querySelector(`[data-top-menu-id="${topMenuId}"]`);
	      if (leftMenuNode) {
	        leftMenuNode.setAttribute('data-link', firstPageLink);
	        const leftMenuLink = leftMenuNode.querySelector('a.menu-item-link');
	        if (leftMenuLink) {
	          leftMenuLink.setAttribute('href', firstPageLink);
	        }
	        if (leftMenuNode.previousElementSibling === this.menuContainer.querySelector('#left-menu-empty-item')) {
	          Backend.setFirstPage(firstPageLink);
	        } else {
	          Backend.clearCache();
	        }
	      }
	      this.showMessage(firstNode, main_core.Loc.getMessage('MENU_ITEM_MAIN_SECTION_PAGE'));
	    });
	    BX.addCustomEvent('BX.Main.InterfaceButtons:onHideLastVisibleItem', bindElement => {
	      this.showMessage(bindElement, main_core.Loc.getMessage('MENU_TOP_ITEM_LAST_HIDDEN'));
	    });

	    // when we edit top menu item
	    BX.addCustomEvent('BX.Main.InterfaceButtons:onBeforeCreateEditMenu', (contextMenu, dataItem, topMenu) => {
	      let item = babelHelpers.classPrivateFieldLooseBase(this, _getLeftMenuItemByTopMenuItem)[_getLeftMenuItemByTopMenuItem](dataItem);
	      if (!item && dataItem && main_core.Type.isStringFilled(dataItem.URL) && !dataItem.URL.match(/javascript:/)) {
	        contextMenu.addMenuItem({
	          text: main_core.Loc.getMessage('MENU_ADD_TO_LEFT_MENU'),
	          onclick: (event, item) => {
	            this.getItemDirector().saveStandardPage(dataItem);
	            item.getMenuWindow().close();
	          }
	        });
	      } else if (item instanceof ItemUserFavorites) {
	        contextMenu.addMenuItem({
	          text: main_core.Loc.getMessage('MENU_DELETE_FROM_LEFT_MENU'),
	          onclick: (event, item) => {
	            this.getItemDirector().deleteStandardPage(dataItem);
	            item.getMenuWindow().close();
	          }
	        });
	      }
	    });
	    // endregion

	    // service event for UI.Toolbar
	    top.BX.addCustomEvent('UI.Toolbar:onRequestMenuItemData', ({
	      currentFullPath,
	      context
	    }) => {
	      if (main_core.Type.isStringFilled(currentFullPath)) {
	        BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onSendMenuItemData', [{
	          currentPageInMenu: this.menuContainer.querySelector(`.menu-item-block[data-link="${currentFullPath}"]`),
	          context
	        }]);
	      }
	    });

	    // When clicked on a start Favorites like
	    main_core_events.EventEmitter.subscribe('UI.Toolbar:onStarClick', ({
	      compatData: [params]
	    }) => {
	      if (params.isActive) {
	        this.getItemDirector().deleteCurrentPage({
	          context: params.context,
	          pageLink: params.pageLink
	        }).then(({
	          itemInfo
	        }) => {
	          BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onMenuItemDeleted', [itemInfo, this]);
	          BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	            isActive: false,
	            context: params.context
	          }]);
	        });
	      } else {
	        this.getItemDirector().saveCurrentPage({
	          pageTitle: params.pageTitle,
	          pageLink: params.pageLink
	        }).then(({
	          itemInfo
	        }) => {
	          BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onMenuItemAdded', [itemInfo, this]);
	          BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
	            isActive: true,
	            context: params.context
	          }]);
	        });
	      }
	    });
	    main_core_events.EventEmitter.subscribe('BX.Main.InterfaceButtons:onBeforeResetMenu', ({
	      compatData: [promises]
	    }) => {
	      promises.push(() => {
	        const p = new BX.Promise();
	        Backend.clearCache().then(() => {
	          p.fulfill();
	        }, response => {
	          p.reject(`Error: ${response.errors[0].message}`);
	        });
	        return p;
	      });
	    });
	  }
	  isEditMode() {
	    return this.getItemsController().isEditMode;
	  }
	  isCollapsed() {
	    return this.isCollapsedMode;
	  }
	  showMessage(bindElement, message, position) {
	    var popup = main_popup.PopupManager.create("left-menu-message", bindElement, {
	      content: '<div class="left-menu-message-popup">' + message + '</div>',
	      darkMode: true,
	      offsetTop: position === "right" ? -45 : 2,
	      offsetLeft: position === "right" ? 215 : 0,
	      angle: position === "right" ? {
	        position: "left"
	      } : true,
	      cacheable: false,
	      autoHide: true,
	      events: {
	        onDestroy: function () {
	          popup = null;
	        }
	      }
	    });
	    popup.show();
	    setTimeout(function () {
	      if (popup) {
	        popup.close();
	        popup = null;
	      }
	    }, 3000);
	  }
	  showError(bindElement) {
	    this.showMessage(bindElement, main_core.Loc.getMessage('edit_error'));
	  }
	  showGlobalPreset() {
	    const loadBannerDispatcherExtensionPromise = main_core.Runtime.loadExtension('ui.banner-dispatcher');
	    loadBannerDispatcherExtensionPromise.then(() => {
	      ui_bannerDispatcher.BannerDispatcher.high.toQueue(onDone => {
	        const presetController = this.getDefaultPresetController();
	        presetController.show('global');
	        presetController.getPopup().subscribe('onAfterClose', event => {
	          onDone();
	        });
	      });
	    }).catch(() => {});
	  }
	  handleShowHiddenClick() {
	    this.getItemsController().toggleHiddenContainer(true);
	  }
	  onHiddenBlockIsVisible() {
	    main_core.Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
	    this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = main_core.Loc.getMessage("more_items_hide");
	  }
	  onHiddenBlockIsHidden() {
	    main_core.Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
	    this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = main_core.Loc.getMessage("more_items_show");
	  }
	  onHiddenBlockIsEmpty() {
	    main_core.Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	  }
	  onHiddenBlockIsNotEmpty() {
	    main_core.Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	  }
	  setDefaultMenu() {
	    ui_dialogs_messagebox.MessageBox.show({
	      message: main_core.Loc.getMessage('MENU_SET_DEFAULT_CONFIRM'),
	      onYes: (messageBox, button) => {
	        button.setWaiting();
	        Backend.setDefaultPreset().then(() => {
	          button.setWaiting(false);
	          messageBox.close();
	          document.location.reload();
	        });
	      },
	      buttons: ui_dialogs_messagebox.MessageBoxButtons.YES_CANCEL
	    });
	  }
	  clearCompositeCache() {
	    main_core.ajax.runAction('intranet.leftmenu.clearCache', {
	      data: {}
	    });
	  }
	  // region Events servicing functions
	  onGettingSettingMenuItems() {
	    const topPoint = ItemUserFavorites.getActiveTopMenuItem();
	    let menuItemWithAddingToFavorites = null;
	    if (topPoint) {
	      const node = this.menuContainer.querySelector(`.menu-item-block[data-link="${topPoint['URL']}"]`);
	      if (!node) {
	        menuItemWithAddingToFavorites = {
	          text: main_core.Loc.getMessage("MENU_ADD_TO_LEFT_MENU"),
	          onclick: (event, item) => {
	            this.getItemDirector().saveStandardPage(topPoint);
	            item.getMenuWindow().destroy();
	          }
	        };
	      } else if (node.getAttribute('data-type') === ItemUserFavorites.code) {
	        menuItemWithAddingToFavorites = {
	          text: main_core.Loc.getMessage("MENU_DELETE_FROM_LEFT_MENU"),
	          onclick: (event, item) => {
	            this.getItemDirector().deleteStandardPage(topPoint);
	            item.getMenuWindow().destroy();
	          }
	        };
	      } else {
	        menuItemWithAddingToFavorites = {
	          text: main_core.Loc.getMessage('MENU_DELETE_PAGE_FROM_LEFT_MENU'),
	          className: 'menu-popup-disable-text',
	          onclick: () => {}
	        };
	      }
	    }
	    const leftMenuSettingItems = [{
	      text: main_core.Loc.getMessage('SORT_ITEMS'),
	      onclick: () => {
	        this.getItemsController().switchToEditMode();
	      }
	    }, {
	      text: this.isCollapsed() ? main_core.Loc.getMessage('MENU_EXPAND') : main_core.Loc.getMessage('MENU_COLLAPSE'),
	      onclick: (event, item) => {
	        this.toggle();
	        item.getMenuWindow().destroy();
	      }
	    }, menuItemWithAddingToFavorites, {
	      text: main_core.Loc.getMessage('MENU_ADD_SELF_PAGE'),
	      onclick: (event, item) => {
	        this.getItemDirector().showAddToSelf(this.getSettingsController().getContainer());
	      }
	    }];

	    //custom preset
	    if (Options.isAdmin) {
	      let itemText = main_core.Loc.getMessage('MENU_SAVE_CUSTOM_PRESET');
	      if (Options.isCustomPresetRestricted) {
	        itemText += "<span class='menu-lock-icon'></span>";
	      }
	      leftMenuSettingItems.push({
	        html: itemText,
	        className: Options.isCustomPresetRestricted ? ' menu-popup-disable-text' : '',
	        onclick: (event, item) => {
	          if (Options.isCustomPresetRestricted) {
	            BX.UI.InfoHelper.show('limit_office_menu_to_all');
	          } else {
	            this.getCustomPresetController().show();
	          }
	        }
	      });
	    }
	    if (!Options.isExtranet) {
	      leftMenuSettingItems.push({
	        text: main_core.Loc.getMessage('MENU_SET_DEFAULT'),
	        onclick: this.setDefaultMenu.bind(this)
	      });
	    }
	    const Messenger = main_core.Reflection.getClass('BX.Messenger.v2.Lib.Messenger');
	    const menuItems = [!Options.isAdmin ? null : {
	      text: main_core.Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_B24_SETTINGS'),
	      onclick: () => {
	        BX.SidePanel.Instance.open(`${Options.settingsPath}?analyticContext=left_menu`, {
	          allowChangeHistory: false,
	          width: 1034
	        });
	      }
	    }, Messenger ? {
	      text: main_core.Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_MESSENGER_SETTINGS'),
	      onclick: () => {
	        Messenger.openSettings();
	      }
	    } : null, Options.isExtranet ? null : {
	      text: main_core.Loc.getMessage('MENU_SET_DEFAULT2'),
	      onclick: () => {
	        this.getDefaultPresetController().show('personal');
	      }
	    }, !Options.inviteDialogLink ? null : {
	      text: main_core.Loc.getMessage('MENU_INVITE_USERS'),
	      onclick: () => {
	        BX.SidePanel.Instance.open(Options.inviteDialogLink, {
	          cacheable: false,
	          allowChangeHistory: false,
	          width: 1100
	        });
	      }
	    }, !Options.isAdmin && Options.isExtranet ? null : {
	      delimiter: true
	    }, {
	      text: main_core.Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_MENU_SETTINGS'),
	      items: leftMenuSettingItems
	    }, {
	      delimiter: true
	    }, !Options.showSitemapMenuItem ? null : {
	      text: main_core.Loc.getMessage('MENU_SITE_MAP'),
	      onclick: () => {
	        this.handleSiteMapClick();
	      }
	    }, {
	      text: main_core.Loc.getMessage('MENU_HELP'),
	      onclick: () => {
	        this.handleHelperClick();
	      }
	    }];
	    return menuItems.filter(value => {
	      return value !== null;
	    });
	  }

	  // endregion

	  handleSiteMapClick() {
	    this.switchToSlidingMode(false);
	    BX.SidePanel.Instance.open((main_core.Loc.getMessage('SITE_DIR') || '/') + 'sitemap/', {
	      allowChangeHistory: false,
	      customLeftBoundary: 0
	    });
	  }
	  handleHelperClick() {
	    this.switchToSlidingMode(false);
	    BX.Helper.show();
	  }

	  // region Sliding functions
	  blockSliding() {
	    this.stopSliding();
	    this.isMenuMouseEnterBlocked = true;
	  }
	  releaseSliding() {
	    this.isMenuMouseEnterBlocked = false;
	  }
	  stopSliding() {
	    clearTimeout(this.slidingModeTimeoutId);
	    this.slidingModeTimeoutId = 0;
	  }
	  startSliding() {
	    this.stopSliding();
	    if (this.isMenuMouseEnterBlocked === true) {
	      return;
	    }
	    this.slidingModeTimeoutId = setTimeout(function () {
	      this.slidingModeTimeoutId = 0;
	      this.switchToSlidingMode(true);
	    }.bind(this), 400);
	  }
	  handleBurgerClick(open) {
	    this.getItemsController().switchToViewMode();
	    this.menuHeaderBurger.classList.add("menu-switcher-hover");
	    this.toggle(open, function () {
	      this.blockSliding();
	      setTimeout(function () {
	        this.menuHeaderBurger.classList.remove("menu-switcher-hover");
	        this.releaseSliding();
	      }.bind(this), 100);
	    }.bind(this));
	  }
	  handleMenuMouseEnter(event) {
	    if (!this.isCollapsed()) {
	      return;
	    }
	    this.startSliding();
	  }
	  handleMenuMouseLeave(event) {
	    this.stopSliding();
	    if (this.isMenuMouseLeaveBlocked.length <= 0) {
	      this.switchToSlidingMode(false);
	    }
	  }
	  handleMenuDoubleClick(event) {
	    if (event.target === this.menuBody) {
	      this.toggle();
	    }
	  }
	  handleUpButtonClick() {
	    this.blockSliding();
	    if (this.isUpButtonReversed()) {
	      window.scrollTo(0, this.lastScrollOffset);
	      this.lastScrollOffset = 0;
	      this.unreverseUpButton();
	    } else {
	      this.lastScrollOffset = window.pageYOffset;
	      window.scrollTo(0, 0);
	      this.reverseUpButton();
	    }
	    setTimeout(this.releaseSliding.bind(this), 100);
	  }
	  handleUpButtonMouseLeave() {
	    this.releaseSliding();
	  }
	  handleDocumentScroll() {
	    if (window.pageYOffset > document.documentElement.clientHeight) {
	      this.showUpButton();
	      if (this.isUpButtonReversed()) {
	        this.unreverseUpButton();
	        this.lastScrollOffset = 0;
	      }
	    } else if (!this.isUpButtonReversed()) {
	      this.hideUpButton();
	    }
	    if (window.pageXOffset > 0) {
	      this.menuContainer.style.left = -window.pageXOffset + "px";
	      this.upButton.style.left = -window.pageXOffset + (this.isCollapsed() ? 0 : 172) + "px";
	    } else {
	      this.menuContainer.style.removeProperty("left");
	      this.upButton.style.removeProperty("left");
	    }
	  }
	  switchToSlidingMode(enable, immediately) {
	    if (enable === false) {
	      this.stopSliding();
	      if (BX.hasClass(this.mainTable, "menu-sliding-mode")) {
	        if (immediately !== true) {
	          BX.addClass(this.mainTable, "menu-sliding-closing-mode");
	          if (Options.showLicenseButton) {
	            babelHelpers.classPrivateFieldLooseBase(this, _getLicenseButton)[_getLicenseButton]().setCollapsed(true);
	          }
	        }
	        BX.removeClass(this.mainTable, "menu-sliding-mode menu-sliding-opening-mode");
	        main_core.Dom.removeClass(this.menuContainer, '--ui-context-edge-dark');
	      }
	    } else if (this.isCollapsedMode && !BX.hasClass(this.mainTable, "menu-sliding-mode")) {
	      BX.removeClass(this.mainTable, "menu-sliding-closing-mode");
	      main_core.Dom.removeClass(this.menuContainer, '--ui-context-edge-dark');
	      if (immediately !== true) {
	        BX.addClass(this.mainTable, "menu-sliding-opening-mode");
	        if (Options.showLicenseButton) {
	          setTimeout(() => {
	            babelHelpers.classPrivateFieldLooseBase(this, _getLicenseButton)[_getLicenseButton]().setCollapsed(false);
	          }, 50);
	        }
	      }
	      BX.addClass(this.mainTable, "menu-sliding-mode");
	      main_core.Dom.addClass(this.menuContainer, '--ui-context-edge-dark');
	    }
	  }
	  handleSlidingTransitionEnd(event) {
	    if (event.target === this.menuContainer) {
	      BX.removeClass(this.mainTable, "menu-sliding-opening-mode menu-sliding-closing-mode");
	    }
	  }
	  switchToScrollMode(enable) {
	    if (enable === false) {
	      main_core.Dom.removeClass(this.mainTable, 'menu-scroll-mode');
	    } else if (!main_core.Dom.hasClass(this.mainTable, 'menu-scroll-mode')) {
	      main_core.Dom.addClass(this.mainTable, 'menu-scroll-mode');
	    }
	  }
	  toggle(flag, fn) {
	    let leftColumn = document.querySelector(".js-app");
	    if (!leftColumn) {
	      return;
	    }
	    const isOpen = !this.mainTable.classList.contains('menu-collapsed-mode');
	    if (flag === isOpen || this.mainTable.classList.contains('menu-animation-mode')) {
	      return;
	    }
	    BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", [flag, this]);
	    this.blockSliding();
	    this.switchToSlidingMode(false, true);

	    // leftColumn.style.overflow = "hidden";
	    this.mainTable.classList.add("menu-animation-mode", isOpen ? "menu-animation-closing-mode" : "menu-animation-opening-mode");
	    var menuLinks = [].slice.call(leftColumn.querySelectorAll('.menu-item-link'));
	    var menuMoreBtn = leftColumn.querySelector('.menu-collapsed-more-btn');
	    var menuMoreBtnDefault = leftColumn.querySelector('.menu-default-more-btn');
	    var menuSitemapIcon = leftColumn.querySelector('.menu-sitemap-icon-box');
	    var menuSitemapText = leftColumn.querySelector('.menu-sitemap-btn-text');
	    var menuEmployeesText = leftColumn.querySelector('.menu-invite-employees-text');
	    var menuEmployeesIcon = leftColumn.querySelector('.menu-invite-icon-box');
	    const settingsIconBox = this.menuContainer.querySelector(".menu-settings-icon-box");
	    const settingsBtnText = this.menuContainer.querySelector(".menu-settings-btn-text");
	    const helpIconBox = this.menuContainer.querySelector(".menu-help-icon-box");
	    const helpBtnText = this.menuContainer.querySelector(".menu-help-btn-text");
	    var menuTextDivider = leftColumn.querySelector('.menu-item-separator');
	    var menuMoreCounter = leftColumn.querySelector('.menu-item-index-more');
	    var pageHeader = this.mainTable.querySelector(".air-header");
	    var imBar = document.getElementById("bx-im-bar");
	    var imBarWidth = imBar ? imBar.offsetWidth : 0;
	    const expandedMenuWidth = parseInt(getComputedStyle(this.menuContainer).getPropertyValue('--menu-width-expanded'), 10);
	    const collapsedMenuWidth = parseInt(getComputedStyle(this.menuContainer).getPropertyValue('--menu-width-collapsed'), 10);
	    new BX.easing({
	      duration: 300,
	      start: {
	        sidebarWidth: isOpen ? expandedMenuWidth : collapsedMenuWidth /* these values are duplicated in style.css as well */
	        // opacity: isOpen ? 100 : 0,
	        // opacityRevert: isOpen ? 0 : 100
	      },

	      finish: {
	        sidebarWidth: isOpen ? collapsedMenuWidth : expandedMenuWidth
	        // opacity: isOpen ? 0 : 100,
	        // opacityRevert: isOpen ? 100 : 0
	      },

	      transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	      step: function (state) {
	        // leftColumn.style.width = state.sidebarWidth + "px";
	        this.menuContainer.style.width = state.sidebarWidth + "px";
	        this.menuHeaderBurger.style.width = state.burgerMenuWidth + "px";
	        // this.headerBurger.style.width = state.burgerMenuWidth + "px";

	        //Change this formula in template_style.css as well
	        if (pageHeader) {
	          pageHeader.style.maxWidth = "calc(100vw - " + state.sidebarWidth + "px - " + imBarWidth + "px)";
	        }
	        if (Options.showLicenseButton && state.sidebarWidth > 160) {
	          babelHelpers.classPrivateFieldLooseBase(this, _getLicenseButton)[_getLicenseButton]().setCollapsed(isOpen);
	        }
	        if (isOpen) {
	          //Closing Mode
	          if (menuSitemapIcon) {
	            menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuSitemapIcon.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuSitemapText) {
	            menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
	            menuSitemapText.style.opacity = state.opacity / 100;
	          }
	          if (menuEmployeesIcon) {
	            menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuEmployeesText) {
	            menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
	            menuEmployeesText.style.opacity = state.opacity / 100;
	          }
	          if (settingsIconBox) {
	            settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            settingsIconBox.style.opacity = state.opacityRevert / 100;
	          }
	          if (settingsBtnText) {
	            settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            settingsBtnText.style.opacity = state.opacity / 100;
	          }
	          if (helpIconBox) {
	            helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            helpIconBox.style.opacity = state.opacityRevert / 100;
	          }
	          if (helpBtnText) {
	            helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            helpBtnText.style.opacity = state.opacity / 100;
	          }
	          if (menuMoreBtn) {
	            menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuMoreBtn.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuMoreBtnDefault) {
	            menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
	            menuMoreBtnDefault.style.opacity = state.opacity / 100;
	          }
	          if (menuMoreCounter) {
	            menuMoreCounter.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuMoreCounter.style.opacity = state.opacityRevert / 100;
	          }
	          menuLinks.forEach(function (item) {
	            var menuIcon = item.querySelector(".menu-item-icon-box");
	            var menuLinkText = item.querySelector(".menu-item-link-text");
	            var menuCounter = item.querySelector(".menu-item-index");
	            var menuArrow = item.querySelector('.menu-item-link-arrow');
	            menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
	            menuLinkText.style.opacity = state.opacity / 100;
	            menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuIcon.style.opacity = state.opacityRevert / 100;
	            if (menuArrow) {
	              menuArrow.style.transform = "translateX(" + state.translateText + "px)";
	              menuArrow.style.opacity = state.opacity / 100;
	            }
	            if (menuCounter) {
	              menuCounter.style.transform = "translateX(" + state.translateIcon + "px)";
	              menuCounter.style.opacity = state.opacityRevert / 100;
	            }
	          });
	        } else {
	          //Opening Mode
	          menuTextDivider.style.opacity = 0;
	          if (menuSitemapIcon) {
	            menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuSitemapIcon.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuSitemapText) {
	            menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
	            menuSitemapText.style.opacity = state.opacity / 100;
	          }
	          if (menuEmployeesIcon) {
	            menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuEmployeesText) {
	            menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
	            menuEmployeesText.style.opacity = state.opacity / 100;
	          }
	          if (settingsIconBox) {
	            settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            settingsIconBox.style.opacity = state.opacityRevert / 100;
	          }
	          if (settingsBtnText) {
	            settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            settingsBtnText.style.opacity = state.opacity / 100;
	          }
	          if (helpIconBox) {
	            helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
	            helpIconBox.style.opacity = state.opacityRevert / 100;
	          }
	          if (helpBtnText) {
	            helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
	            helpBtnText.style.opacity = state.opacity / 100;
	          }
	          if (menuMoreBtn) {
	            menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuMoreBtn.style.opacity = state.opacityRevert / 100;
	          }
	          if (menuMoreBtnDefault) {
	            menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
	            menuMoreBtnDefault.style.opacity = state.opacity / 100;
	          }
	          if (menuMoreCounter) {
	            menuMoreCounter.style.transform = "translateX(" + state.translateText + "px)";
	          }
	          menuLinks.forEach(function (item) {
	            var menuIcon = item.querySelector(".menu-item-icon-box");
	            var menuLinkText = item.querySelector(".menu-item-link-text");
	            var menuCounter = item.querySelector(".menu-item-index");
	            var menuArrow = item.querySelector('.menu-item-link-arrow');
	            menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
	            menuLinkText.style.opacity = state.opacity / 100;
	            menuLinkText.style.display = "inline-block";
	            menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
	            menuIcon.style.opacity = state.opacityRevert / 100;
	            if (menuArrow) {
	              menuArrow.style.transform = "translateX(" + state.translateText + "px)";
	              // menuArrow.style.opacity = state.opacityRevert / 100;
	            }

	            if (menuCounter) {
	              menuCounter.style.transform = "translateX(" + state.translateText + "px)";
	            }
	          });
	        }
	        var event = document.createEvent("Event");
	        event.initEvent("resize", true, true);
	        window.dispatchEvent(event);
	      }.bind(this),
	      complete: function () {
	        if (isOpen) {
	          this.isCollapsedMode = true;
	          BX.addClass(this.mainTable, "menu-collapsed-mode");
	        } else {
	          this.isCollapsedMode = false;
	          BX.removeClass(this.mainTable, "menu-collapsed-mode");
	        }
	        BX.removeClass(this.mainTable, "menu-animation-mode menu-animation-opening-mode menu-animation-closing-mode");
	        var containers = [leftColumn, menuTextDivider, this.menuHeaderBurger, this.headerBurger, settingsIconBox, settingsBtnText, helpIconBox, helpBtnText, menuMoreBtnDefault, menuMoreBtn, menuSitemapIcon, menuSitemapText, menuEmployeesIcon, menuEmployeesText, menuMoreCounter, this.menuContainer, pageHeader];
	        containers.forEach(function (container) {
	          if (container) {
	            container.style.cssText = "";
	          }
	        });
	        menuLinks.forEach(function (item) {
	          var menuIcon = item.querySelector(".menu-item-icon-box");
	          var menuLinkText = item.querySelector(".menu-item-link-text");
	          var menuCounter = item.querySelector(".menu-item-index");
	          var menuArrow = item.querySelector('.menu-item-link-arrow');
	          item.style.cssText = "";
	          menuLinkText.style.cssText = "";
	          menuIcon.style.cssText = "";
	          if (menuArrow) {
	            menuArrow.style.cssText = "";
	          }
	          if (menuCounter) {
	            menuCounter.style.cssText = "";
	          }
	        });
	        this.releaseSliding();
	        if (BX.type.isFunction(fn)) {
	          fn();
	        }
	        Backend.toggleMenu(isOpen);
	        var event = document.createEvent("Event");
	        event.initEvent("resize", true, true);
	        window.dispatchEvent(event);
	      }.bind(this)
	    }).animate();
	  }
	  //endregion

	  handleViewMode() {
	    this.getItemsController().switchToViewMode();
	  }
	  handleGroupPanelOpen() {
	    this.isMenuMouseLeaveBlocked.push('group');
	  }
	  handleGroupPanelClose() {
	    this.isMenuMouseLeaveBlocked.pop();
	  }
	  showUpButton() {
	    this.menuContainer.classList.add("menu-up-button-active");
	  }
	  hideUpButton() {
	    this.menuContainer.classList.remove("menu-up-button-active");
	  }
	  reverseUpButton() {
	    this.menuContainer.classList.add("menu-up-button-reverse");
	  }
	  unreverseUpButton() {
	    this.menuContainer.classList.remove("menu-up-button-reverse");
	  }
	  isUpButtonReversed() {
	    return this.menuContainer.classList.contains("menu-up-button-reverse");
	  }
	  isDefaultTheme() {
	    return document.body.classList.contains("bitrix24-default-theme");
	  }
	  getTopPadding() {
	    return this.isDefaultTheme() ? 0 : 9;
	  }

	  // region Public functions
	  initPagetitleStar() {
	    return ItemUserFavorites.isCurrentPageStandard(ItemUserFavorites.getActiveTopMenuItem());
	  }
	  getStructureForHelper() {
	    const items = {
	      menu: {}
	    };
	    ["show", "hide"].forEach(state => {
	      Array.from(this.menuContainer.querySelectorAll(`[data-status="${state}"][data-type="${ItemSystem.code}"]`)).forEach(node => {
	        items[state] = items[state] || [];
	        items[state].push(node.getAttribute("data-id"));
	      });
	    });
	    return items;
	  }
	  showItemWarning({
	    itemId,
	    title,
	    events
	  }) {
	    if (this.getItemsController().items.has(itemId)) {
	      this.getItemsController().items.get(itemId).showWarning(title, events);
	    }
	  }
	  removeItemWarning(itemId) {
	    if (this.getItemsController().items.has(itemId)) {
	      this.getItemsController().items.get(itemId).removeWarning();
	    }
	  }
	  decrementCounter(node, iDecrement) {
	    if (!node || node.id !== 'menu-counter-live-feed') {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _specialLiveFeedDecrement)[_specialLiveFeedDecrement] += parseInt(iDecrement);
	    this.getItemsController().decrementCounter({
	      'live-feed': parseInt(iDecrement)
	    });
	  }
	  updateCounters(counters, send) {
	    if (!counters) {
	      return;
	    }
	    if (counters['**'] !== undefined) {
	      counters['live-feed'] = counters['**'];
	      delete counters['**'];
	    }
	    if (counters['live-feed']) {
	      if (counters['live-feed'] <= 0) {
	        babelHelpers.classPrivateFieldLooseBase(this, _specialLiveFeedDecrement)[_specialLiveFeedDecrement] = 0;
	      } else {
	        counters['live-feed'] -= babelHelpers.classPrivateFieldLooseBase(this, _specialLiveFeedDecrement)[_specialLiveFeedDecrement];
	      }
	    }
	    this.getItemsController().updateCounters(counters, send);
	  }
	  //endregion
	}
	function _getLeftMenuItemByTopMenuItem2({
	  DATA_ID,
	  NODE
	}) {
	  var _item;
	  let item = this.getItemsController().items.get(DATA_ID);
	  if (!item) {
	    const topMenuId = NODE.getAttribute('data-top-menu-id');
	    if (NODE === NODE.parentNode.querySelector('[data-top-menu-id]')) {
	      const leftMenuNode = this.menuItemsBlock.querySelector(`[data-top-menu-id="${topMenuId}"]`);
	      if (leftMenuNode) {
	        item = this.getItemsController().items.get(leftMenuNode.getAttribute('data-id'));
	      }
	    }
	  }
	  return (_item = item) != null ? _item : null;
	}
	function _addLicenseButton2() {
	  if (Options.showLicenseButton) {
	    const licenseButtonWrapper = this.menuContainer.querySelector('.menu-license-all-wrapper');
	    if (licenseButtonWrapper) {
	      babelHelpers.classPrivateFieldLooseBase(this, _getLicenseButton)[_getLicenseButton]().renderTo(licenseButtonWrapper);
	    }
	  }
	}
	function _getLicenseButton2() {
	  if (this.licenseButton) {
	    return this.licenseButton;
	  }
	  this.licenseButton = babelHelpers.classPrivateFieldLooseBase(this, _createLicenseButton)[_createLicenseButton]();
	  this.licenseButton.setCollapsed(this.isCollapsed());
	  return this.licenseButton;
	}
	function _createLicenseButton2() {
	  return new ui_buttons.Button({
	    size: ui_buttons.Button.Size.SMALL,
	    text: main_core.Loc.getMessage('MENU_LICENSE_ALL'),
	    useAirDesign: true,
	    style: ui_buttons.AirButtonStyle.FILLED_SUCCESS,
	    noCaps: true,
	    wide: true,
	    icon: 'o-rocket',
	    className: 'menu-license-all-button',
	    onclick: () => {
	      BX.SidePanel.Instance.open(Options.licenseButtonPath, {
	        width: 1250,
	        cacheable: false
	      });
	    }
	  });
	}

	exports.Menu = Menu;

}((this.BX.Intranet = this.BX.Intranet || {}),BX.UI,BX,BX.UI,BX.Main,BX.Event,BX.UI.Dialogs,BX.UI,BX.UI.Analytics,BX,BX.Cache,BX.SidePanel));
//# sourceMappingURL=script.js.map
