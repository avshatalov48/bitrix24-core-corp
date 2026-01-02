import { ajax, Cache, Dom, Loc, Reflection, Runtime, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MenuItem, PopupManager } from 'main.popup';
import { AirButtonStyle, Button } from 'ui.buttons';
import PresetCustomController from './controllers/preset-custom-controller';
import PresetDefaultController from './controllers/preset-default-controller';
import SettingsController from './controllers/settings-controller';
import Options from './options';
import Backend from './backend';
import ItemsController from './controllers/items-controller';
import ItemDirector from './controllers/item-director';
import Item from './items/item';
import ItemSystem from './items/item-system';
import ItemUserFavorites from './items/item-user-favorites';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { BannerDispatcher } from 'ui.banner-dispatcher';
import { Analytics, AnalyticActions } from './analytics';
import { GroupPanel } from './group-panel';

export default class Menu
{
	menuContainer;
	menuHeader;
	menuBody;
	mainTable;
	upButton;
	menuMoreButton;

	cache = new Cache.MemoryCache();

	scrollModeThreshold = 20;//
	lastScrollOffset = 0;
	slidingModeTimeoutId = 0;

	isMenuMouseEnterBlocked = false;
	isMenuMouseLeaveBlocked = [];
	isCollapsedMode = false;

	constructor(params)
	{
		this.menuContainer = document.getElementById("menu-items-block");
		if (!this.menuContainer)
		{
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
		this.#addLicenseButton();

		this.groupPanel = new GroupPanel({
			isExtranetInstalled: params.isExtranetInstalled !== 'N',
		});

		// Emulate document scroll because init() can be invoked after page load scroll
		// (a hard reload with script at the bottom).
		// this.handleDocumentScroll();
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

		// document.addEventListener("scroll", this.handleDocumentScroll.bind(this));

		this.mainTable = document.querySelector(".js-app");
		this.menuHeaderBurger = this.menuHeader.querySelector(".menu-switcher");
		this.menuHeaderBurger.addEventListener('click', this.handleBurgerClick.bind(this));
		this.menuHeader.querySelector(".menu-items-header-title")
			.addEventListener('click', this.handleBurgerClick.bind(this, true));

		// this.upButton = this.menuContainer.querySelector(".menu-btn-arrow-up");
		// this.upButton.addEventListener("click", this.handleUpButtonClick.bind(this));
		this.menuMoreButton = this.menuContainer.querySelector(".menu-item-block.menu-expand");
		this.menuMoreButton = this.menuContainer.querySelector('[data-role="expand-menu-item"]');
		this.menuMoreButton.addEventListener("click", this.handleShowHiddenClick.bind(this));

		const siteMapItem = this.menuContainer.querySelector(".menu-sitemap-btn");
		if (siteMapItem)
		{
			siteMapItem.addEventListener('click', this.handleSiteMapClick.bind(this));
		}

		const settingsSaveBtn = this.menuContainer.querySelector(".menu-settings-save-btn");
		if (settingsSaveBtn)
		{
			settingsSaveBtn.addEventListener('click', this.handleViewMode.bind(this));
		}

		// this.menuContainer.querySelector(".menu-settings-btn")?.addEventListener('click', () => {
		// 	this.getSettingsController().show();
		// });
		this.menuContainer.querySelector('[data-role="menu-settings-item"]')?.addEventListener('click', () => {
			this.getSettingsController().show();
		});
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

	getSettingsController(): ?SettingsController
	{
		return this.cache.remember('presetController', () => {
			const node = this.menuContainer.querySelector('[data-role="menu-settings-item"]');

			if (!node)
			{
				return null;
			}

			return new SettingsController(
				node,
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
						onPresetIsSet: ({ data }) => {
							const { saveSortItems, firstItemLink, customItems } = this.getItemsController().export();

							if (!data)
							{
								this.analytics.sendSetCustomPreset();
							}

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
		let closeEventWasProcessed = false;
		const postponeHandler = (mode: String): Promise => {
			const result = Backend.postponeSystemPreset(mode);
			EventEmitter.emit(this, Options.eventName('onPresetIsPostponed'));

			return result;
		};

		return this.cache.remember('defaultPresetController', () => {
			const presetController = new PresetDefaultController(
				this.menuContainer,
				{
					events: {
						onPresetIsSet: ({ data: { mode, presetId } }) => {
							this.analytics.sendSetPreset(
								presetId,
								mode === 'personal',
								AnalyticActions.CONFIRM,
							);
							closeEventWasProcessed = true;

							return Backend.setSystemPreset(mode, presetId);
						},
						onPresetIsPostponed: ({ data: { mode } }) => {
							this.analytics.sendSetPreset(
								presetController.getSelectedPreset(),
								mode === 'personal',
								AnalyticActions.LATER,
							);
							closeEventWasProcessed = true;

							return postponeHandler(mode);
						},
						onShow: () => {
							this.analytics.sendClose();
						},
						onClose: () => {
							if (closeEventWasProcessed !== true)
							{
								this.analytics.sendSetPreset(
									presetController.getSelectedPreset(),
									presetController.getMode() === 'personal',
									AnalyticActions.CLOSE,
								);

								postponeHandler(presetController.getMode());
							}
							closeEventWasProcessed = false;
						},
					},
				},
			);

			return presetController;
		});
	}
	// endregion

	bindEvents(): void
	{
		// All Counters from IM
		EventEmitter.subscribe('onImUpdateCounter', (event: BaseEvent) => {
			const [counters] = event.getCompatData();
			this.updateCounters(counters, false);
		});

		// Messenger counter
		EventEmitter.subscribe('onImUpdateCounterMessage', (event: BaseEvent) => {
			const [counter] = event.getCompatData();
			this.updateCounters({ 'im-message': counter }, false);
		});

		// Live Feed Counter
		EventEmitter.subscribe('onCounterDecrement', (event: BaseEvent) => {
			const [decrement] = event.getCompatData();
			this.decrementCounter(document.getElementById('menu-counter-live-feed'), decrement);
		});

		// All Counters
		EventEmitter.subscribe('onPullEvent-main', (event: BaseEvent) => {
			const [command, params] = event.getCompatData();
			if (command === 'user_counter' && params[Loc.getMessage('SITE_ID')])
			{
				const counters = { ...params[Loc.getMessage('SITE_ID')] };
				this.updateCounters(counters, false);
			}
		});

		// just to hold opened menu in collapsing mode when groups are shown
		BX.addCustomEvent("BX.Bitrix24.GroupPanel:onOpen", this.handleGroupPanelOpen.bind(this));
		BX.addCustomEvent("BX.Bitrix24.GroupPanel:onClose", this.handleGroupPanelClose.bind(this));

		// region Top menu integration
		BX.addCustomEvent('BX.Main.InterfaceButtons:onFirstItemChange', (firstPageLink, firstNode) => {
			if (!firstPageLink || !Type.isDomNode(firstNode))
			{
				return;
			}

			const topMenuId = firstNode.getAttribute('data-top-menu-id');
			const leftMenuNode = this.menuBody.querySelector(`[data-top-menu-id="${topMenuId}"]`);
			if (leftMenuNode)
			{
				leftMenuNode.setAttribute('data-link', firstPageLink);
				const leftMenuLink = leftMenuNode.querySelector('a.menu-item-link');
				if (leftMenuLink)
				{
					leftMenuLink.setAttribute('href', firstPageLink);
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

		BX.addCustomEvent('BX.Main.InterfaceButtons:onHideLastVisibleItem', (bindElement) => {
			this.showMessage(bindElement, Loc.getMessage('MENU_TOP_ITEM_LAST_HIDDEN'));
		});

		// when we edit top menu item
		BX.addCustomEvent('BX.Main.InterfaceButtons:onBeforeCreateEditMenu', (contextMenu, dataItem, topMenu) => {
			let item = this.#getLeftMenuItemByTopMenuItem(dataItem);
			if (!item && dataItem && Type.isStringFilled(dataItem.URL) && !dataItem.URL.match(/javascript:/))
			{
				contextMenu.addMenuItem({
					text: Loc.getMessage('MENU_ADD_TO_LEFT_MENU'),
					onclick: (event, item) => {
						this.getItemDirector()
							.saveStandardPage(dataItem)
						;
						item.getMenuWindow().close();
					},
				});
			}
			else if (item instanceof ItemUserFavorites)
			{
				contextMenu.addMenuItem({
					text: Loc.getMessage('MENU_DELETE_FROM_LEFT_MENU'),
					onclick: (event, item) => {
						this.getItemDirector().deleteStandardPage(dataItem);
						item.getMenuWindow().close();
					},
				});
			}
		});
		// endregion

		// service event for UI.Toolbar
		top.BX.addCustomEvent('UI.Toolbar:onRequestMenuItemData', ({ currentFullPath, context }) => {
			if (Type.isStringFilled(currentFullPath))
			{
				BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onSendMenuItemData', [{
					currentPageInMenu: this.menuContainer.querySelector(`.menu-item-block[data-link="${currentFullPath}"]`),
					context,
				}]);
			}
		});

		// When clicked on a start Favorites like
		EventEmitter.subscribe('UI.Toolbar:onStarClick', ({compatData: [params]}) => {
			if (params.isActive)
			{
				this.getItemDirector().deleteCurrentPage({
					context: params.context,
					pageLink: params.pageLink,
				}).then(({itemInfo}) => {
					BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onMenuItemDeleted', [itemInfo, this]);
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
						BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onMenuItemAdded', [itemInfo, this]);
						BX.onCustomEvent('BX.Bitrix24.LeftMenuClass:onStandardItemChangedSuccess', [{
							isActive: true,
							context: params.context,
						}]);
					})
				;
			}
		});

		EventEmitter.subscribe('BX.Main.InterfaceButtons:onBeforeResetMenu', ({compatData: [promises]}) => {
			promises.push(() => {
				const p = new BX.Promise();
				Backend
					.clearCache()
					.then(
						() => {
							p.fulfill();
						},
						(response) => {
							p.reject(`Error: ${response.errors[0].message}`);
						},
					)
				;

				return p;
			});
		});
	}

	isEditMode(): boolean
	{
		return this.getItemsController().isEditMode;
	}

	isCollapsed(): boolean
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
		const loadBannerDispatcherExtensionPromise = Runtime.loadExtension('ui.banner-dispatcher');

		loadBannerDispatcherExtensionPromise.then(() => {
			BannerDispatcher.high.toQueue((onDone) => {
				const presetController = this.getDefaultPresetController();
				presetController.show('global');
				presetController.getPopup().subscribe('onAfterClose', (event) => {
					onDone();
				});
			});
		}).catch(() => {});
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

		const leftMenuSettingItems = [
			{
				text: Loc.getMessage('SORT_ITEMS'),
				onclick: () => { this.getItemsController().switchToEditMode();}
			},
			{
				text: this.isCollapsed() ? Loc.getMessage('MENU_EXPAND') : Loc.getMessage('MENU_COLLAPSE'),
				onclick: (event, item: MenuItem) => {
					this.toggle();
					item.getMenuWindow().destroy();
				},
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
		];

		//custom preset
		if (Options.isAdmin)
		{
			let itemText = Loc.getMessage('MENU_SAVE_CUSTOM_PRESET');

			if (Options.isCustomPresetRestricted)
			{
				itemText+= "<span class='menu-lock-icon'></span>";
			}

			leftMenuSettingItems.push({
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

		if (!Options.isExtranet)
		{
			leftMenuSettingItems.push({
				text: Loc.getMessage('MENU_SET_DEFAULT'),
				onclick: this.setDefaultMenu.bind(this)
			})
		}

		const Messenger = Reflection.getClass('BX.Messenger.v2.Lib.Messenger');

		const menuItems = [
			!Options.isAdmin ? null : {
				text: Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_B24_SETTINGS'),
				onclick: () => {
					BX.SidePanel.Instance.open(`${Options.settingsPath}?analyticContext=left_menu`, {
						allowChangeHistory: false,
						width: 1034,
					});
				},
			},
			Messenger ? {
				text: Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_MESSENGER_SETTINGS'),
				onclick: () => {
					Messenger.openSettings();
				},
			} : null,
			Options.isExtranet ? null : {
				text: Loc.getMessage('MENU_SET_DEFAULT2'),
				onclick: () => {
					this.getDefaultPresetController().show('personal');
				},
			},
			!Options.inviteDialogLink ? null : {
				text: Loc.getMessage('MENU_INVITE_USERS'),
				onclick: () => {
					BX.SidePanel.Instance.open(Options.inviteDialogLink, {cacheable: false, allowChangeHistory: false, width: 1100});
				},
			},
			!Options.isAdmin && Options.isExtranet ? null : {
				delimiter: true,
			},
			{
				text: Loc.getMessage('LEFT_MENU_SETTINGS_ITEM_MENU_SETTINGS'),
				items: leftMenuSettingItems,
			},
			{
				delimiter: true,
			},
			!Options.showSitemapMenuItem ? null : {
				text: Loc.getMessage('MENU_SITE_MAP'),
				onclick: () => {
					this.handleSiteMapClick();
				},
			},
			{
				text: Loc.getMessage('MENU_HELP'),
				onclick: () => {
					this.handleHelperClick();
				},
			},
		];

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

					if (Options.showLicenseButton)
					{
						this.#getLicenseButton().setCollapsed(true);
					}
				}

				BX.removeClass(this.mainTable, "menu-sliding-mode menu-sliding-opening-mode");
				Dom.removeClass(this.menuContainer, '--ui-context-edge-dark');
			}
		}
		else if (this.isCollapsedMode && !BX.hasClass(this.mainTable, "menu-sliding-mode"))
		{
			BX.removeClass(this.mainTable, "menu-sliding-closing-mode");
			Dom.removeClass(this.menuContainer, '--ui-context-edge-dark');

			if (immediately !== true)
			{
				BX.addClass(this.mainTable, "menu-sliding-opening-mode");
				if (Options.showLicenseButton)
				{
					setTimeout(() => {
						this.#getLicenseButton().setCollapsed(false);
					}, 50);
				}
			}

			BX.addClass(this.mainTable, "menu-sliding-mode");
			Dom.addClass(this.menuContainer, '--ui-context-edge-dark');
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
			Dom.removeClass(this.mainTable, 'menu-scroll-mode');
		}
		else if (!Dom.hasClass(this.mainTable, 'menu-scroll-mode'))
		{
			Dom.addClass(this.mainTable, 'menu-scroll-mode');
		}
	}

	toggle(flag, fn)
	{
		let leftColumn = document.querySelector(".js-app");
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

		this.blockSliding();
		this.switchToSlidingMode(false, true);

		// leftColumn.style.overflow = "hidden";
		this.mainTable.classList.add("menu-animation-mode", (isOpen ? "menu-animation-closing-mode" : "menu-animation-opening-mode"));

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

		(new BX.easing({
			duration: 300,
			start: {
				sidebarWidth: isOpen ? expandedMenuWidth : collapsedMenuWidth, /* these values are duplicated in style.css as well */
				// opacity: isOpen ? 100 : 0,
				// opacityRevert: isOpen ? 0 : 100
			},
			finish: {
				sidebarWidth: isOpen ? collapsedMenuWidth : expandedMenuWidth,
				// opacity: isOpen ? 0 : 100,
				// opacityRevert: isOpen ? 100 : 0
			},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state)
			{
				// leftColumn.style.width = state.sidebarWidth + "px";
				this.menuContainer.style.width = state.sidebarWidth + "px";
				this.menuHeaderBurger.style.width = state.burgerMenuWidth + "px";
				// this.headerBurger.style.width = state.burgerMenuWidth + "px";

				//Change this formula in template_style.css as well
				if (pageHeader)
				{
					pageHeader.style.maxWidth = "calc(100vw - " + state.sidebarWidth + "px - " + imBarWidth + "px)";
				}

				if (Options.showLicenseButton && state.sidebarWidth > 160)
				{
					this.#getLicenseButton().setCollapsed(isOpen);
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

					if (settingsIconBox)
					{
						settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
						settingsIconBox.style.opacity = state.opacityRevert / 100;
					}

					if (settingsBtnText)
					{
						settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
						settingsBtnText.style.opacity = state.opacity / 100;
					}

					if (helpIconBox)
					{
						helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
						helpIconBox.style.opacity = state.opacityRevert / 100;
					}

					if (helpBtnText)
					{
						helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
						helpBtnText.style.opacity = state.opacity / 100;
					}

					if (menuMoreBtn)
					{
						menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
						menuMoreBtn.style.opacity = state.opacityRevert / 100;
					}

					if (menuMoreBtnDefault)
					{
						menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
						menuMoreBtnDefault.style.opacity = state.opacity / 100;
					}

					if (menuMoreCounter)
					{
						menuMoreCounter.style.transform = "translateX(" + state.translateIcon + "px)";
						menuMoreCounter.style.opacity = state.opacityRevert / 100;
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

					if (settingsIconBox)
					{
						settingsIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
						settingsIconBox.style.opacity = state.opacityRevert / 100;
					}

					if (settingsBtnText)
					{
						settingsBtnText.style.transform = "translateX(" + state.translateText + "px)";
						settingsBtnText.style.opacity = state.opacity / 100;
					}

					if (helpIconBox)
					{
						helpIconBox.style.transform = "translateX(" + state.translateIcon + "px)";
						helpIconBox.style.opacity = state.opacityRevert / 100;
					}

					if (helpBtnText)
					{
						helpBtnText.style.transform = "translateX(" + state.translateText + "px)";
						helpBtnText.style.opacity = state.opacity / 100;
					}

					if (menuMoreBtn)
					{
						menuMoreBtn.style.transform = "translateX(" + state.translateIcon + "px)";
						menuMoreBtn.style.opacity = state.opacityRevert / 100;
					}

					if (menuMoreBtnDefault)
					{
						menuMoreBtnDefault.style.transform = "translateX(" + state.translateMoreBtn + "px)";
						menuMoreBtnDefault.style.opacity = state.opacity / 100;
					}

					if (menuMoreCounter)
					{
						menuMoreCounter.style.transform = "translateX(" + state.translateText + "px)";
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
					menuSitemapIcon,
					menuSitemapText,
					menuEmployeesIcon,
					menuEmployeesText,
					menuMoreCounter,
					this.menuContainer,
					pageHeader,
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

	#addLicenseButton(): void
	{
		if (Options.showLicenseButton)
		{
			const licenseButtonWrapper = this.menuContainer.querySelector('.menu-license-all-wrapper');

			if (licenseButtonWrapper)
			{
				this.#getLicenseButton().renderTo(licenseButtonWrapper);
			}
		}
	}

	#getLicenseButton(): Button
	{
		if (this.licenseButton)
		{
			return this.licenseButton;
		}

		this.licenseButton = this.#createLicenseButton();
		this.licenseButton.setCollapsed(this.isCollapsed());

		return this.licenseButton;
	}

	#createLicenseButton(): Button
	{
		return new Button({
			size: Button.Size.SMALL,
			text: Loc.getMessage('MENU_LICENSE_ALL'),
			useAirDesign: true,
			style: AirButtonStyle.FILLED_SUCCESS,
			noCaps: true,
			wide: true,
			icon: 'o-rocket',
			className: 'menu-license-all-button',
			onclick: () => {
				BX.SidePanel.Instance.open(Options.licenseButtonPath, { width: 1250, cacheable: false });
			},
		});
	}
}
