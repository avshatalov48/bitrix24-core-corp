import {Cache, Loc, ajax, Type, Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {PopupManager, MenuItem} from 'main.popup';
import PresetCustomController from './controllers/preset-custom-controller';
import PresetDefaultController from './controllers/preset-default-controller';
import SettingsController from './controllers/settings-controller';
import Options from './options';
import Backend from './backend';
import ItemsController from './controllers/items-controller'
import ItemDirector from './controllers/item-director'
import Item from "./items/item";
import ItemSystem from "./items/item-system";
import Utils from './utils';
import ItemUserFavorites from "./items/item-user-favorites";
import {MessageBox, MessageBoxButtons} from 'ui.dialogs.messagebox';

export default class Menu
{
	//region containers
	menuContainer;
	menuHeader;
	menuBody;
	header;
	headerBurger;
	headerSettings;
	mainTable;
	upButton;
	menuMoreButton;
	//endregion

	cache = new Cache.MemoryCache();

	scrollModeThreshold = 20;//
	lastScrollOffset = 0;
	slidingModeTimeoutId = 0;

	topMenuSelectedNode = null;//
	topItemSelectedObj = null;

	isMenuMouseEnterBlocked = false;
	isMenuMouseLeaveBlocked = [];
	isCollapsedMode = false;

	workgroupsCounterData = {};

	constructor(params)
	{
		//TODO     html
		this.menuContainer = document.getElementById("menu-items-block");
		if (!this.menuContainer)
		{
			return false;
		}
		params = typeof params === "object" ? params : {};

		Options.isExtranet = params.isExtranet === 'Y';
		Options.isAdmin = params.isAdmin;
		Options.isCustomPresetRestricted = params.isCustomPresetAvailable !== 'Y';

		this.isCollapsedMode = params.isCollapsedMode;

		this.workgroupsCounterData = params.workgroupsCounterData;

		this.initAndBindNodes();
		this.bindEvents();
		this.getItemsController();
		//Emulate document scroll because init() can be invoked after page load scroll (a hard reload with script at the bottom).
		this.handleDocumentScroll();
	}

	initAndBindNodes()
	{
		this.menuContainer.addEventListener("dblclick", this.handleMenuDoubleClick.bind(this));
		this.menuContainer.addEventListener("mouseenter", this.handleMenuMouseEnter.bind(this));
		this.menuContainer.addEventListener("mouseleave", this.handleMenuMouseLeave.bind(this));
		this.menuContainer.addEventListener("transitionend", this.handleSlidingTransitionEnd.bind(this));

		this.menuHeader = this.menuContainer.querySelector(".menu-items-header");
		this.menuBody = this.menuContainer.querySelector(".menu-items-body");
		this.menuItemsBlock = this.menuContainer.querySelector(".menu-items");

		this.header = document.querySelector("#header");
		this.headerBurger = this.header.querySelector(".menu-switcher");

		const headerLogoBlock = this.header.querySelector(".header-logo-block");
		this.headerSettings = this.header.querySelector(".header-logo-block-settings");
		if (this.headerSettings)
		{
			headerLogoBlock.addEventListener("mouseenter", this.handleHeaderLogoMouserEnter.bind(this));
			headerLogoBlock.addEventListener("mouseleave", this.handleHeaderLogoMouserLeave.bind(this));
			this.menuHeader.addEventListener("mouseenter", this.handleHeaderLogoMouserEnter.bind(this));
			this.menuHeader.addEventListener("mouseleave", this.handleHeaderLogoMouserLeave.bind(this));
		}
		document.addEventListener("scroll", this.handleDocumentScroll.bind(this));

		this.mainTable = document.querySelector(".bx-layout-table");
		this.menuHeaderBurger = this.menuHeader.querySelector(".menu-switcher");
		this.menuHeaderBurger
			.addEventListener('click', this.handleBurgerClick.bind(this));
		this.menuHeader.querySelector(".menu-items-header-title")
			.addEventListener('click', this.handleBurgerClick.bind(this, true));

		this.upButton = this.menuContainer.querySelector(".menu-btn-arrow-up");
		this.upButton.addEventListener("click", this.handleUpButtonClick.bind(this));
		this.menuMoreButton = this.menuContainer.querySelector(".menu-favorites-more-btn");
		this.menuMoreButton.addEventListener("click", this.handleShowHiddenClick.bind(this));

		const helperItem = this.menuContainer.querySelector(".menu-help-btn");

		if (helperItem)
		{
			helperItem.addEventListener('click', this.handleHelperClick.bind(this));
		}

		const siteMapItem = this.menuContainer.querySelector(".menu-sitemap-btn");
		if (siteMapItem)
		{
			siteMapItem.addEventListener('click', this.handleSiteMapClick.bind(this));
		}

		const settingsSaveBtn = this.menuContainer.querySelector(".menu-settings-save-btn")
		if (settingsSaveBtn)
		{
			settingsSaveBtn.addEventListener('click', this.handleViewMode.bind(this));
		}
		this.menuContainer.querySelector(".menu-settings-btn").addEventListener('click', () => {
			this.getSettingsController().show();
		})
	}

	// region Controllers
	getItemsController(): ItemsController
	{
		return this.cache.remember('itemsController', () => {
			return new ItemsController(
				this.menuContainer,
				{
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
						onDragModeOn: ({target}) => {
							this.switchToSlidingMode(true);
							this.isMenuMouseLeaveBlocked.push('drag');
						},
						onDragModeOff: ({target}) => {
							this.isMenuMouseLeaveBlocked.pop();
						},
						onHiddenBlockIsVisible: this.onHiddenBlockIsVisible.bind(this),
						onHiddenBlockIsHidden: this.onHiddenBlockIsHidden.bind(this),
						onHiddenBlockIsEmpty: this.onHiddenBlockIsEmpty.bind(this),
						onHiddenBlockIsNotEmpty: this.onHiddenBlockIsNotEmpty.bind(this),
						onShow: () => { this.isMenuMouseLeaveBlocked.push('items'); },
						onClose: () => { this.isMenuMouseLeaveBlocked.pop(); },
					}
				}
			);
		});
	}

	getItemDirector(): ItemDirector
	{
		return this.cache.remember('itemsCreator', () => {
			return new ItemDirector(
				this.menuContainer,
				{
					events: {
						onItemHasBeenAdded: ({data}) => {
							this.getItemsController().addItem(data);
						}
					}
				}
			);
		});
	}

	getSettingsController(): SettingsController
	{
		return this.cache.remember('presetController', () => {
			return new SettingsController(
				this.menuContainer.querySelector(".menu-settings-btn"),
				{
					events: {
						onGettingSettingMenuItems: this.onGettingSettingMenuItems.bind(this),
						onShow: () => { this.isMenuMouseLeaveBlocked.push('settings'); },
						onClose: () => { this.isMenuMouseLeaveBlocked.pop(); },
					}
				}
			);
		});
	}

	getCustomPresetController(): PresetCustomController
	{
		return this.cache.remember('customPresetController', () => {
			return new PresetCustomController(
				this.menuContainer,
				{
					events: {
						onPresetIsSet: ({data}) => {
							const {saveSortItems, firstItemLink, customItems} = this.getItemsController().export();
							return Backend.setCustomPreset(data, saveSortItems, customItems, firstItemLink)
						},
						onShow: () => { this.isMenuMouseLeaveBlocked.push('presets'); },
						onClose: () => { this.isMenuMouseLeaveBlocked.pop(); },
					}
				}
			);
		});
	}

	getDefaultPresetController(): PresetDefaultController
	{
		return this.cache.remember('defaultPresetController', () => {
			return new PresetDefaultController(
				this.menuContainer,
				{
					events: {
						onPresetIsSet: ({data: {mode, presetId}}) => {
							return Backend.setSystemPreset(mode, presetId);
						},
						onPresetIsPostponed: ({data: {mode}}) => {
							const result =  Backend.postponeSystemPreset(mode);
							EventEmitter.emit(this, Options.eventName('onPresetIsPostponed'));
							return result;
						}
/*
						onShow: () => { this.isMenuMouseLeaveBlocked.push('presets-default'); },
						onClose: () => { this.isMenuMouseLeaveBlocked.pop(); },
*/
					}
				}
			);
		});
	}
	//endregion

	bindEvents()
	{
		//just to hold opened menu in collapsing mode when groups are shown
		BX.addCustomEvent("BX.Bitrix24.GroupPanel:onOpen", this.handleGroupPanelOpen.bind(this));
		BX.addCustomEvent("BX.Bitrix24.GroupPanel:onClose", this.handleGroupPanelClose.bind(this));

		//region Top menu integration
		BX.addCustomEvent('BX.Main.InterfaceButtons:onFirstItemChange', (firstPageLink, firstNode) => {
			if (!firstPageLink || !Type.isDomNode(firstNode))
			{
				return;
			}

			const topMenuId = firstNode.getAttribute("data-top-menu-id");
			const leftMenuNode = this.menuBody.querySelector(`[data-top-menu-id="${topMenuId}"]`);
			if (leftMenuNode)
			{
				leftMenuNode.setAttribute("data-link", firstPageLink);
				const leftMenuLink = leftMenuNode.querySelector('a.menu-item-link');
				if (leftMenuLink)
				{
					leftMenuLink.setAttribute("href", firstPageLink);
				}

				if (leftMenuNode.previousElementSibling === this.menuContainer.querySelector('#left-menu-empty-item'))
				{
					Backend.setFirstPage(firstPageLink);
				}
				else
				{
					Backend.clearCache();
				}
			}
			this.showMessage(firstNode, Loc.getMessage('MENU_ITEM_MAIN_SECTION_PAGE'));
		});
		BX.addCustomEvent("BX.Main.InterfaceButtons:onHideLastVisibleItem", (bindElement) => {
			this.showMessage(bindElement, Loc.getMessage("MENU_TOP_ITEM_LAST_HIDDEN"));
		});
		//when we edit top menu item
		BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeCreateEditMenu", (contextMenu, dataItem, topMenu) => {
			let item = this.#getLeftMenuItemByTopMenuItem(dataItem);
			if (!item && dataItem && Type.isStringFilled(dataItem.URL) && !dataItem.URL.match(/javascript:/))
			{
				contextMenu.addMenuItem({
					text: Loc.getMessage("MENU_ADD_TO_LEFT_MENU"),
					onclick: (event, item) => {
						this.getItemDirector()
							.saveStandardPage(dataItem)
						;
						item.getMenuWindow().close();
					}
				});
			}
			else if (item instanceof ItemUserFavorites)
			{
				contextMenu.addMenuItem({
					text: Loc.getMessage("MENU_DELETE_FROM_LEFT_MENU"),
					onclick: (event, item) => {
						this.getItemDirector()
							.deleteStandardPage(dataItem)
						;
						item.getMenuWindow().close();
					}
				});
			}
		});
		//endregion
		//service event for UI.Toolbar
		top.BX.addCustomEvent('UI.Toolbar:onRequestMenuItemData', ({currentFullPath, context}) => {
			if (Type.isStringFilled(currentFullPath))
			{
				BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onSendMenuItemData', [{
					currentPageInMenu: this.menuContainer.querySelector(`.menu-item-block[data-link="${currentFullPath}"]`),
					context: context,
				}]);
			}
		});
		//When clicked on a start Favorites like
		EventEmitter.subscribe('UI.Toolbar:onStarClick', ({compatData: [params]}) => {
			if (params.isActive)
			{
				this.getItemDirector().deleteCurrentPage({
					context: params.context,
					pageLink: params.pageLink,
				}).then(({itemInfo}) => {
					BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [itemInfo, this]);
					BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
						isActive: false,
						context: params.context,
					}]);
				});
			}
			else
			{
				this.getItemDirector()
					.saveCurrentPage({
					pageTitle: params.pageTitle,
					pageLink: params.pageLink,
				}).then(({itemInfo}) => {
					BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);
					BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
						isActive: true,
						context: params.context,
					}]);
				});
			}
		});
		EventEmitter.subscribe('BX.Main.InterfaceButtons:onBeforeResetMenu', ({compatData: [promises]}) => {
			promises.push(() => {
				const p = new BX.Promise();
				Backend
					.clearCache()
					.then(
						() => { p.fulfill();},
						(response) => { p.reject("Error: " +  response.errors[0].message);}
					)
				;
				return p;
			});
		});
	}

	isEditMode()
	{
		return this.getItemsController().isEditMode;
	}

	isCollapsed()
	{
		return this.isCollapsedMode;
	}

	showMessage(bindElement, message, position)
	{
		var popup = PopupManager.create(
			"left-menu-message",
			bindElement,
			{
				content: '<div class="left-menu-message-popup">' + message + '</div>',
				darkMode: true,
				offsetTop: position === "right" ? -45 : 2,
				offsetLeft: position === "right" ? 215 : 0,
				angle: position === "right" ? {position: "left"} : true,
				cacheable: false,
				autoHide: true,
				events: {
					onDestroy: function () {
						popup = null;
					}
				},
			})
		;

		popup.show();

		setTimeout(function ()
		{
			if (popup)
			{
				popup.close();
				popup = null;
			}
		}, 3000);
	}

	showError(bindElement)
	{
		this.showMessage(bindElement, Loc.getMessage('edit_error'));
	}

	showGlobalPreset()
	{
		this.getDefaultPresetController().show('global');
	}

	handleShowHiddenClick()
	{
		this.getItemsController().toggleHiddenContainer(true);
	}

	onHiddenBlockIsVisible()
	{
		Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
		this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = Loc.getMessage("more_items_hide");
	}

	onHiddenBlockIsHidden()
	{
		Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
		this.menuMoreButton.querySelector("#menu-more-btn-text").innerHTML = Loc.getMessage("more_items_show");
	}

	onHiddenBlockIsEmpty()
	{
		Dom.addClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	}

	onHiddenBlockIsNotEmpty()
	{
		Dom.removeClass(this.menuMoreButton, 'menu-favorites-more-btn-hidden');
	}

	setDefaultMenu()
	{
		MessageBox.show({
			message: Loc.getMessage('MENU_SET_DEFAULT_CONFIRM'),
			onYes: (messageBox, button) => {
				button.setWaiting();
				Backend
					.setDefaultPreset()
					.then(() => {
						button.setWaiting(false);
						messageBox.close();
						document.location.reload();
					})
				;
			},
			buttons: MessageBoxButtons.YES_CANCEL
		});
	}

	clearCompositeCache()
	{
		ajax.runAction('intranet.leftmenu.clearCache', {data: {}});
	}

	#getLeftMenuItemByTopMenuItem({DATA_ID, NODE}): ?Item
	{
		let item = this.getItemsController().items.get(DATA_ID);
		if (!item)
		{
			const topMenuId = NODE.getAttribute('data-top-menu-id');
			if (NODE === NODE.parentNode.querySelector('[data-top-menu-id]'))
			{
				const leftMenuNode = this.menuItemsBlock.querySelector(`[data-top-menu-id="${topMenuId}"]`);
				if (leftMenuNode)
				{
					item = this.getItemsController().items.get(leftMenuNode.getAttribute('data-id'));
				}
			}
		}
		return item ?? null;
	}
	// region Events servicing functions
	onGettingSettingMenuItems()
	{
		const topPoint = ItemUserFavorites.getActiveTopMenuItem();
		let menuItemWithAddingToFavorites = null;
		if (topPoint)
		{
			const node = this.menuContainer.querySelector(`.menu-item-block[data-link="${topPoint['URL']}"]`);
			if (!node)
			{
				menuItemWithAddingToFavorites = {
					text: Loc.getMessage("MENU_ADD_TO_LEFT_MENU"),
					onclick: (event, item) => {
						this.getItemDirector()
							.saveStandardPage(topPoint)
						;
						item.getMenuWindow().destroy();
					}
				};
			}
			else if (node.getAttribute('data-type') === ItemUserFavorites.code)
			{
				menuItemWithAddingToFavorites = {
					text: Loc.getMessage("MENU_DELETE_FROM_LEFT_MENU"),
					onclick: (event, item) => {
						this.getItemDirector()
							.deleteStandardPage(topPoint)
						;
						item.getMenuWindow().destroy();
					}
				};
			}
			else
			{
				menuItemWithAddingToFavorites = {
					text: Loc.getMessage('MENU_DELETE_PAGE_FROM_LEFT_MENU'),
					className: 'menu-popup-disable-text',
					onclick: () => { }
				};
			}
		}


		const menuItems = [
			{
				text: Loc.getMessage('SORT_ITEMS'),
				onclick: () => { this.getItemsController().switchToEditMode();}
			},
			{
				text: this.isCollapsed() ? Loc.getMessage('MENU_EXPAND') : Loc.getMessage('MENU_COLLAPSE'),
				onclick: (event, item: MenuItem) => {
					this.toggle();
					item.getMenuWindow().destroy();
				}
			},
			menuItemWithAddingToFavorites,
			{
				text: Loc.getMessage('MENU_ADD_SELF_PAGE'),
				onclick: (event, item: MenuItem) => {
					this
						.getItemDirector()
						.showAddToSelf(
							this.getSettingsController().getContainer()
						)
					;
				},
			},
			Options.isExtranet ? null : {
				text: Loc.getMessage('MENU_SET_DEFAULT2'),
				onclick: () => {
					this.getDefaultPresetController().show('personal')
				}
			},
			Options.isExtranet ? null : {
				text: Loc.getMessage('MENU_SET_DEFAULT'),
				onclick: this.setDefaultMenu.bind(this)
			}
		];
		//custom preset
		if (Options.isAdmin)
		{
			let itemText = Loc.getMessage('MENU_SAVE_CUSTOM_PRESET');

			if (Options.isCustomPresetRestricted)
			{
				itemText+= "<span class='menu-lock-icon'></span>";
			}

			menuItems.push({
				html: itemText,
				className: (Options.isCustomPresetRestricted ? ' menu-popup-disable-text' : ''),
				onclick: (event, item) => {
					if (Options.isCustomPresetRestricted)
					{
						BX.UI.InfoHelper.show('limit_office_menu_to_all');
					}
					else
					{
						this.getCustomPresetController().show();
					}
				}
			});
		}
		return menuItems.filter((value) => {return value !== null;})
	}

	// endregion

	handleSiteMapClick()
	{
		this.switchToSlidingMode(false);

		BX.SidePanel.Instance.open(
			(Loc.getMessage('SITE_DIR') || '/') + 'sitemap/',
			{
				allowChangeHistory: false,
				customLeftBoundary: 0
			}
		);
	}

	handleHelperClick()
	{
		this.switchToSlidingMode(false);
		BX.Helper.show();
	}

	// region Sliding functions
	blockSliding()
	{
		this.stopSliding()
		this.isMenuMouseEnterBlocked = true;
	}

	releaseSliding()
	{
		this.isMenuMouseEnterBlocked = false;
	}

	stopSliding()
	{
		clearTimeout(this.slidingModeTimeoutId);
		this.slidingModeTimeoutId = 0;
	}

	startSliding()
	{
		this.stopSliding();
		if (this.isMenuMouseEnterBlocked === true)
		{
			return;
		}
		this.slidingModeTimeoutId = setTimeout(function() {
			this.slidingModeTimeoutId = 0;
			this.switchToSlidingMode(true);
		}.bind(this), 400);
	}

	handleBurgerClick(open)
	{
		this.getItemsController().switchToViewMode();

		this.menuHeaderBurger.classList.add("menu-switcher-hover");

		this.toggle(open, function() {

			this.blockSliding();

			setTimeout(function() {
				this.menuHeaderBurger.classList.remove("menu-switcher-hover");

				this.releaseSliding();

			}.bind(this), 100);

		}.bind(this));
	}

	handleMenuMouseEnter(event)
	{
		if (!this.isCollapsed())
		{
			return;
		}
		this.startSliding();
	}

	handleMenuMouseLeave(event)
	{
		this.stopSliding();
		if (this.isMenuMouseLeaveBlocked.length <= 0)
		{
			this.switchToSlidingMode(false);
		}
	}

	handleMenuDoubleClick(event)
	{
		if (event.target === this.menuBody)
		{
			this.toggle();
		}
	}

	handleHeaderLogoMouserEnter(event)
	{
		BX.addClass(this.headerSettings, "header-logo-block-settings-show");
	}

	handleHeaderLogoMouserLeave(event)
	{
		if (!this.headerSettings.hasAttribute("data-rename-portal"))
		{
			BX.removeClass(this.headerSettings, "header-logo-block-settings-show");
		}
	}

	handleUpButtonClick()
	{
		this.blockSliding();

		if (this.isUpButtonReversed())
		{
			window.scrollTo(0, this.lastScrollOffset);
			this.lastScrollOffset = 0;
			this.unreverseUpButton();
		}
		else
		{
			this.lastScrollOffset = window.pageYOffset;
			window.scrollTo(0, 0);
			this.reverseUpButton();
		}

		setTimeout(this.releaseSliding.bind(this), 100);
	}

	handleUpButtonMouseLeave()
	{
		this.releaseSliding();
	}

	handleDocumentScroll()
	{
		this.#adjustAdminPanel();
		this.applyScrollMode();

		if (window.pageYOffset > document.documentElement.clientHeight)
		{
			this.showUpButton();

			if (this.isUpButtonReversed())
			{
				this.unreverseUpButton();
				this.lastScrollOffset = 0;
			}
		}
		else if (!this.isUpButtonReversed())
		{
			this.hideUpButton();
		}

		if (window.pageXOffset > 0)
		{
			this.menuContainer.style.left = -window.pageXOffset + "px";
			this.upButton.style.left = -window.pageXOffset + (this.isCollapsed() ? 0 : 172) + "px";
		}
		else
		{
			this.menuContainer.style.removeProperty("left");
			this.upButton.style.removeProperty("left");
		}
	}

	switchToSlidingMode(enable, immediately)
	{
		if (enable === false)
		{
			this.stopSliding();

			if (BX.hasClass(this.mainTable, "menu-sliding-mode"))
			{
				if (immediately !== true)
				{
					BX.addClass(this.mainTable, "menu-sliding-closing-mode");
				}

				BX.removeClass(this.mainTable, "menu-sliding-mode menu-sliding-opening-mode");
			}
		}
		else if (this.isCollapsedMode && !BX.hasClass(this.mainTable, "menu-sliding-mode"))
		{
			BX.removeClass(this.mainTable, "menu-sliding-closing-mode");

			if (immediately !== true)
			{
				BX.addClass(this.mainTable, "menu-sliding-opening-mode");
			}

			BX.addClass(this.mainTable, "menu-sliding-mode");
		}
	}

	handleSlidingTransitionEnd(event)
	{
		if (event.target === this.menuContainer)
		{
			BX.removeClass(this.mainTable, "menu-sliding-opening-mode menu-sliding-closing-mode");
		}
	}

	switchToScrollMode(enable)
	{
		if (enable === false)
		{
			this.mainTable.classList.remove('menu-scroll-mode');
		}
		else if (!this.mainTable.classList.contains('menu-scroll-mode'))
		{
			this.mainTable.classList.add('menu-scroll-mode');
		}
	}

	//region logo
	#isLogoMaskNeeded(): boolean
	{
		return this.cache.remember('isLogoMaskNeeded', () => {
			const menuHeaderLogo = this.menuHeader.querySelector(".logo");
			let result = false;
			if (menuHeaderLogo && !menuHeaderLogo.querySelector(".logo-image-container"))
			{
				let widthMeasure = menuHeaderLogo.offsetWidth === 0 ?
					(this.header.querySelector(".logo") ?
						this.header.querySelector(".logo").offsetWidth : 0)
					: menuHeaderLogo.offsetWidth
				;

				result = widthMeasure > 200;
			}
			return result;
		});
	}

	switchToLogoMaskMode(enable)
	{
		if (!this.#isLogoMaskNeeded())
		{
			return
		}
		if (enable === false)
		{
			this.mainTable.classList.remove('menu-logo-mask-mode');
		}
		else if (!this.mainTable.classList.contains('menu-logo-mask-mode'))
		{
			this.mainTable.classList.add('menu-logo-mask-mode');
		}
	}
	//endregion

	toggle(flag, fn)
	{
		let leftColumn = BX("layout-left-column");
		if (!leftColumn)
		{
			return;
		}

		const isOpen = !this.mainTable.classList.contains('menu-collapsed-mode');

		if (flag === isOpen || this.mainTable.classList.contains('menu-animation-mode'))
		{
			return;
		}

		BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", [flag, this]);

		var logoImageContainer = this.menuHeader.querySelector(".logo-image-container");
		if (logoImageContainer)
		{
			var logoWidth = this.header.querySelector(".logo-image-container").offsetWidth;
			if (logoWidth > 0)
			{
				logoImageContainer.style.width = logoWidth + "px";
			}
		}

		this.blockSliding();
		this.switchToSlidingMode(false, true);
		this.applyScrollMode();

		leftColumn.style.overflow = "hidden";
		this.mainTable.classList.add("menu-animation-mode", (isOpen ? "menu-animation-closing-mode" : "menu-animation-opening-mode"));

		var menuLinks = [].slice.call(leftColumn.querySelectorAll('.menu-item-link'));
		var menuMoreBtn = leftColumn.querySelector('.menu-collapsed-more-btn');
		var menuMoreBtnDefault = leftColumn.querySelector('.menu-default-more-btn');

		var menuSitemapIcon = leftColumn.querySelector('.menu-sitemap-icon-box');
		var menuSitemapText = leftColumn.querySelector('.menu-sitemap-btn-text');

		var menuEmployeesText = leftColumn.querySelector('.menu-invite-employees-text');
		var menuEmployeesIcon = leftColumn.querySelector('.menu-invite-icon-box');

		var licenseContainer = leftColumn.querySelector('.menu-license-all-container');
		var licenseBtn = leftColumn.querySelector('.menu-license-all-default');
		var licenseHeight = licenseBtn ? licenseBtn.offsetHeight : 0;
		var licenseCollapsedBtn = leftColumn.querySelector('.menu-license-all-collapsed');

		const settingsIconBox = this.menuContainer.querySelector(".menu-settings-icon-box");
		const settingsBtnText = this.menuContainer.querySelector(".menu-settings-btn-text");

		const helpIconBox = this.menuContainer.querySelector(".menu-help-icon-box");
		const helpBtnText = this.menuContainer.querySelector(".menu-help-btn-text");

		var menuTextDivider = leftColumn.querySelector('.menu-item-separator');
		var menuMoreCounter = leftColumn.querySelector('.menu-item-index-more');

		var pageHeader = this.mainTable.querySelector(".page-header");
		var imBar = document.getElementById("bx-im-bar");
		var imBarWidth = imBar ? imBar.offsetWidth : 0;

		(new BX.easing({
			duration: 300,
			start: {
				translateIcon: isOpen ? -100 : 0,
				translateText: isOpen ? 0 : -100,
				translateMoreBtn: isOpen ? 0 : -84,
				translateLicenseBtn: isOpen ? 0 : -100,
				heightLicenseBtn: isOpen ? licenseHeight : 40,
				burgerMenuWidth: isOpen ? 33 : 66,
				sidebarWidth: isOpen ? 240 : 66, /* these values are duplicated in style.css as well */
				opacity: isOpen ? 100 : 0,
				opacityRevert: isOpen ? 0 : 100
			},
			finish: {
				translateIcon: isOpen ? 0 : -100,
				translateText: isOpen ? -100 : -18,
				translateMoreBtn: isOpen ? -84 : 0,
				translateLicenseBtn: isOpen ? -100 : 0,
				heightLicenseBtn: isOpen ? 40 : licenseHeight,
				burgerMenuWidth: isOpen ? 66 : 33,
				sidebarWidth: isOpen ? 66 : 240,
				opacity: isOpen ? 0 : 100,
				opacityRevert: isOpen ? 100 : 0
			},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state)
			{
				leftColumn.style.width = state.sidebarWidth + "px";
				this.menuContainer.style.width = state.sidebarWidth + "px";
				this.menuHeaderBurger.style.width = state.burgerMenuWidth + "px";
				this.headerBurger.style.width = state.burgerMenuWidth + "px";

				//Change this formula in template_style.css as well
				if (pageHeader)
				{
					pageHeader.style.maxWidth = "calc(100vw - " + state.sidebarWidth + "px - " + imBarWidth + "px)";
				}

				if (isOpen)
				{
					//Closing Mode
					if (menuSitemapIcon)
					{
						menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuSitemapIcon.style.opacity = state.opacityRevert / 100;
					}

					if (menuSitemapText)
					{
						menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
						menuSitemapText.style.opacity = state.opacity / 100;
					}

					if (menuEmployeesIcon)
					{
						menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
					}

					if (menuEmployeesText)
					{
						menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
						menuEmployeesText.style.opacity = state.opacity / 100;
					}

					settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
					settingsIconBox.style.opacity = state.opacityRevert / 100;

					settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
					settingsBtnText.style.opacity = state.opacity / 100;

					helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
					helpIconBox.style.opacity = state.opacityRevert / 100;

					helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
					helpBtnText.style.opacity = state.opacity / 100;

					menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
					menuMoreBtn.style.opacity = state.opacityRevert / 100;

					menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
					menuMoreBtnDefault.style.opacity = state.opacity / 100;

					if (menuMoreCounter)
					{
						menuMoreCounter.style.transform = "translateX(" + state.translateIcon + "px)";
						menuMoreCounter.style.opacity = state.opacityRevert / 100;
					}

					if (licenseContainer)
					{
						licenseBtn.style.transform = "translateX(" + state.translateLicenseBtn + "px)";
						licenseBtn.style.opacity = state.opacity / 100;
						licenseBtn.style.height = state.heightLicenseBtn + "px";

						licenseCollapsedBtn.style.transform = "translateX(" + state.translateIcon + "px)";
						licenseCollapsedBtn.style.opacity = state.opacityRevert / 100;
					}

					menuLinks.forEach(function(item) {
						var menuIcon = item.querySelector(".menu-item-icon-box");
						var menuLinkText = item.querySelector(".menu-item-link-text");
						var menuCounter = item.querySelector(".menu-item-index");
						var menuArrow = item.querySelector('.menu-item-link-arrow');

						menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
						menuLinkText.style.opacity = state.opacity / 100;

						menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuIcon.style.opacity = state.opacityRevert / 100;

						if (menuArrow)
						{
							menuArrow.style.transform = "translateX(" + state.translateText + "px)";
							menuArrow.style.opacity = state.opacity / 100;
						}

						if (menuCounter)
						{
							menuCounter.style.transform = "translateX(" + state.translateIcon + "px)";
							menuCounter.style.opacity = state.opacityRevert / 100;
						}
					});
				}
				else
				{
					//Opening Mode
					menuTextDivider.style.opacity = 0;

					if (menuSitemapIcon)
					{
						menuSitemapIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuSitemapIcon.style.opacity = state.opacityRevert / 100;
					}

					if (menuSitemapText)
					{
						menuSitemapText.style.transform = "translateX(" + state.translateText + "px)";
						menuSitemapText.style.opacity = state.opacity / 100;
					}

					if (menuEmployeesIcon)
					{
						menuEmployeesIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuEmployeesIcon.style.opacity = state.opacityRevert / 100;
					}

					if (menuEmployeesText)
					{
						menuEmployeesText.style.transform = "translateX(" + state.translateText + "px)";
						menuEmployeesText.style.opacity = state.opacity / 100;
					}

					settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
					settingsIconBox.style.opacity = state.opacityRevert / 100;

					settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
					settingsBtnText.style.opacity = state.opacity / 100;

					helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
					helpIconBox.style.opacity = state.opacityRevert / 100;

					helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
					helpBtnText.style.opacity = state.opacity / 100;

					menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
					menuMoreBtn.style.opacity = state.opacityRevert / 100;

					menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
					menuMoreBtnDefault.style.opacity = state.opacity / 100;

					if (menuMoreCounter)
					{
						menuMoreCounter.style.transform = "translateX(" + state.translateText + "px)";
					}

					if (licenseContainer)
					{
						licenseBtn.style.transform = "translateX(" + state.translateLicenseBtn + "px)";
						licenseBtn.style.opacity = state.opacity / 100;
						licenseBtn.style.height = state.heightLicenseBtn + "px";

						licenseCollapsedBtn.style.transform = "translateX(" + state.translateIcon + "px)";
						licenseCollapsedBtn.style.opacity = state.opacityRevert / 100;
					}

					menuLinks.forEach(function(item) {
						var menuIcon = item.querySelector(".menu-item-icon-box");
						var menuLinkText = item.querySelector(".menu-item-link-text");
						var menuCounter = item.querySelector(".menu-item-index");
						var menuArrow = item.querySelector('.menu-item-link-arrow');

						menuLinkText.style.transform = "translateX(" + state.translateText + "px)";
						menuLinkText.style.opacity = state.opacity / 100;
						menuLinkText.style.display = "inline-block";

						menuIcon.style.transform = "translateX(" + state.translateIcon + "px)";
						menuIcon.style.opacity = state.opacityRevert / 100;

						if (menuArrow)
						{
							menuArrow.style.transform = "translateX(" + state.translateText + "px)";
							// menuArrow.style.opacity = state.opacityRevert / 100;
						}

						if (menuCounter)
						{
							menuCounter.style.transform = "translateX(" + state.translateText + "px)";
						}
					});
				}

				var event = document.createEvent("Event");
				event.initEvent("resize", true, true);
				window.dispatchEvent(event);

			}.bind(this),
			complete: function ()
			{
				if (isOpen)
				{
					this.isCollapsedMode = true;
					BX.addClass(this.mainTable, "menu-collapsed-mode");
				}
				else
				{
					this.isCollapsedMode = false;
					BX.removeClass(this.mainTable, "menu-collapsed-mode");
				}

				BX.removeClass(
					this.mainTable,
					"menu-animation-mode menu-animation-opening-mode menu-animation-closing-mode"
				);

				var containers = [
					leftColumn,
					menuTextDivider,
					this.menuHeaderBurger,
					this.headerBurger,
					settingsIconBox,
					settingsBtnText,
					helpIconBox,
					helpBtnText,
					menuMoreBtnDefault,
					menuMoreBtn,
					logoImageContainer,
					menuSitemapIcon,
					menuSitemapText,
					menuEmployeesIcon,
					menuEmployeesText,
					menuMoreCounter,
					licenseBtn,
					licenseCollapsedBtn,
					this.menuContainer,
					pageHeader
				];

				containers.forEach(function(container) {
					if (container)
					{
						container.style.cssText = "";
					}
				});

				menuLinks.forEach(function(item) {
					var menuIcon = item.querySelector(".menu-item-icon-box");
					var menuLinkText = item.querySelector(".menu-item-link-text");
					var menuCounter = item.querySelector(".menu-item-index");
					var menuArrow = item.querySelector('.menu-item-link-arrow');

					item.style.cssText = "";
					menuLinkText.style.cssText = "";
					menuIcon.style.cssText = "";

					if (menuArrow)
					{
						menuArrow.style.cssText = "";
					}

					if (menuCounter)
					{
						menuCounter.style.cssText = "";
					}
				});

				this.releaseSliding();
				this.#adjustAdminPanel();

				if (BX.type.isFunction(fn))
				{
					fn();
				}

				Backend.toggleMenu(isOpen);

				var event = document.createEvent("Event");
				event.initEvent("resize", true, true);
				window.dispatchEvent(event);

			}.bind(this)
		})).animate();
	}
	//endregion

	handleViewMode()
	{
		this.getItemsController().switchToViewMode();
	}

	applyScrollMode()
	{
		this.switchToLogoMaskMode(true);
		const threshold = this.scrollModeThreshold + Utils.adminPanel.height;
		this.switchToScrollMode(window.pageYOffset > threshold);
	}

	handleGroupPanelOpen()
	{
		this.isMenuMouseLeaveBlocked.push('group');
	}

	handleGroupPanelClose()
	{
		this.isMenuMouseLeaveBlocked.pop();
	}

	showUpButton()
	{
		this.menuContainer.classList.add("menu-up-button-active");
	}

	hideUpButton()
	{
		this.menuContainer.classList.remove("menu-up-button-active");
	}

	reverseUpButton()
	{
		this.menuContainer.classList.add("menu-up-button-reverse");
	}

	unreverseUpButton()
	{
		this.menuContainer.classList.remove("menu-up-button-reverse");
	}

	isUpButtonReversed()
	{
		return this.menuContainer.classList.contains("menu-up-button-reverse");
	}

	isDefaultTheme()
	{
		return document.body.classList.contains("bitrix24-default-theme");
	}

	getTopPadding()
	{
		return this.isDefaultTheme() ? 0 : 9;
	}

	// region Public functions
	initPagetitleStar(): boolean
	{
		return ItemUserFavorites.isCurrentPageStandard(
			ItemUserFavorites.getActiveTopMenuItem()
		);
	}

	getStructureForHelper()
	{
		const items = {menu: {}};
		["show", "hide"].forEach((state) => {
			Array.from(this.menuContainer
				.querySelectorAll(`[data-status="${state}"][data-type="${ItemSystem.code}"]`)
			)
			.forEach((node) => {
				items[state] = items[state] || [];
				items[state].push(node.getAttribute("data-id"))
			});
		});
		return items;
	}

	showItemWarning({itemId, title, events})
	{
		if (this.getItemsController().items.has(itemId))
		{
			this.getItemsController().items.get(itemId).showWarning(title, events);
		}
	}

	removeItemWarning(itemId)
	{
		if (this.getItemsController().items.has(itemId))
		{
			this.getItemsController().items.get(itemId).removeWarning();
		}
	}

	#specialLiveFeedDecrement = 0;
	decrementCounter(node, iDecrement)
	{
		if (!node || node.id !== 'menu-counter-live-feed')
		{
			return;
		}
		this.#specialLiveFeedDecrement += parseInt(iDecrement);
		this.getItemsController().decrementCounter({
			'live-feed' : parseInt(iDecrement)
		});
	}

	updateCounters(counters, send)
	{
		if (!counters)
		{
			return;
		}
		if (counters['**'] !== undefined)
		{
			counters['live-feed'] = counters['**'];
			delete counters['**'];
		}

		let workgroupsCounterUpdated = false;
		if (!Type.isUndefined(counters['**SG0']))
		{
			this.workgroupsCounterData['livefeed'] = counters['**SG0'];
			delete counters['**SG0'];
			workgroupsCounterUpdated = true;
		}

		if (!Type.isUndefined(counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')]))
		{
			this.workgroupsCounterData[Loc.getMessage('COUNTER_PROJECTS_MAJOR')] = counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
			delete counters[Loc.getMessage('COUNTER_PROJECTS_MAJOR')];
			workgroupsCounterUpdated = true;
		}

		if (!Type.isUndefined(counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')]))
		{
			this.workgroupsCounterData[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')] = counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
			delete counters[Loc.getMessage('COUNTER_SCRUM_TOTAL_COMMENTS')];
			workgroupsCounterUpdated = true;
		}

		if (workgroupsCounterUpdated)
		{
			counters['workgroups'] = Object.entries(this.workgroupsCounterData).reduce((prevValue, [, curValue]) => {
				return prevValue + Number(curValue);
			}, 0);
		}

		if (counters['live-feed'])
		{
			if (counters['live-feed'] <= 0)
			{
				this.#specialLiveFeedDecrement = 0;
			}
			else
			{
				counters['live-feed'] -= this.#specialLiveFeedDecrement;
			}
		}

		this.getItemsController().updateCounters(counters, send);
	}
	//endregion

	#adjustAdminPanel()
	{
		if (!this['menuAdjustAdminPanel'])
		{
			this['menuAdjustAdminPanel'] = ({data}) => {
				this.menuContainer.style.top = [data, 'px'].join('');
			}
			EventEmitter.subscribe(Utils.adminPanel,
				Options.eventName('onPanelHasChanged'),
				this['menuAdjustAdminPanel']
			);
		}
		this.menuContainer.style.top = [Utils.adminPanel.top, 'px'].join('');
	}
}
