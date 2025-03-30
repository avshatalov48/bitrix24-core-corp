import { MenuManager, Menu } from 'main.popup';
import { Dom, Tag, Event } from 'main.core';
import { RatingStars } from "market.rating-stars";
import { MarketLinks } from "market.market-links";

import 'ui.forms';
import 'ui.design-tokens';
import "./toolbar.css";

export const Toolbar = {
	components: {
		RatingStars,
	},
	props: [
		'categories', 'searchFilters', 'menuInfo', 'marketAction', 'searchAction',
	],
	data() {
		return {
			hoverCategory: 0,
			searchFocus: false,
			catalogShown: false,
			dropdownShown: false,
			searchResult: false,
			search: {
				text: '',
				notFoundText: '',
				loader: false,
				loader2: false,
				currentFilter: '',
				order: {
					currentValue: {},
					currentName: '',
					menuItems: [],
				},
				currentPage: 1,
				pages: 1,
				resultCount: '',
				foundApps: [],
			},
			moreMenu: null,
			searchFilterMenu: null,
			searchOrderMenu: null,
			MarketLinks: MarketLinks,
		}
	},
	computed: {
		getSearchLink: function () {
			if (!this.categories.BANNER_INFO || !this.categories.BANNER_INFO.SEARCH_LINK) {
				return '#';
			}

			return this.categories.BANNER_INFO.SEARCH_LINK;
		},
		getSearchFilterName: function () {
			for (let i = 0; i < this.searchFilters.LIST.length; i++) {
				if (this.searchFilters.LIST[i].CODE && this.searchFilters.LIST[i].CODE === this.search.currentFilter) {
					return this.searchFilters.LIST[i].NAME;
				}
			}

			return '';
		},
		existOrder: function() {
			return Object.keys(this.search.order.currentValue).length > 0;
		},
		getMarketLogoTitle: function() {
			return this.$root.marketLogoTitle.length ? this.$root.marketLogoTitle : this.$Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_MARKET_TITLE');
		},
		getMarketToolbarTitle: function() {
			return this.$root.marketToolbarTitle.length ? this.$root.marketToolbarTitle : this.$Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_MARKET_PLUS_TITLE');
		},
	},
	created: function () {
		this.onSearch = BX.debounce(this.runSearch, 800, this);
	},
	mounted: function () {
		this.bindEvents();
		this.createMoreMenu();
		this.createSearchFilterMenu();
	},
	methods: {
		bindEvents: function() {
			this.$Bitrix.eventEmitter.subscribe('market:closeToolbarPopup', this.closeMoreMenu);

			Event.bind(this.$refs.searchAutoScroll, 'scroll', (event) => {
				if (this.needLoadNextPage(event.currentTarget)) {
					this.search.loader2 = true;
					this.search.currentPage++;
					this.loadItems(true)
				}
			});

			Event.bind(this.$refs.marketSearchInput, 'keydown', (event) => {
				if (event.code.toLowerCase() === 'escape') 
				{
					this.cleanSearch();
					this.closeDropdown();
					this.$refs.marketSearchInput.blur();
					event.stopPropagation();
				}
			});

			Event.bind(document.body, 'keydown', (event) => {
				if (
					event.code.toLowerCase() === 'escape'
					&& this.dropdownShown)
				{
					this.cleanSearch();
					this.closeDropdown();
					this.$refs.marketSearchInput.blur();
					event.stopPropagation();
				}
			});
		},
		createMoreMenu: function() {
			if (!this.menuInfo || !BX.type.isArray(this.menuInfo)) {
				return;
			}

			let menu = [];

			this.menuInfo.forEach((item) => {
				let menuItem = {
					html: item.NAME,
					href: item.PATH,
					className: 'market-toolbar-menu-item',
				}
				if (item.PARAMS) {
					if (item.PARAMS.DELIMITER && item.PARAMS.DELIMITER === 'Y') {
						menu.push({
							id: "delimiter",
							delimiter: true,
						});
						return;
					}

					if (
						(item.PARAMS.INSTALLED_LIST && item.PARAMS.INSTALLED_LIST === 'Y') ||
						(item.PARAMS.NEED_UPDATE_LIST && item.PARAMS.NEED_UPDATE_LIST === 'Y')
					) {
						menuItem.onclick = this.$root.emitLoadContent;
					}

					if (item.PARAMS.DATASET) {
						menuItem.dataset = {};

						if (item.PARAMS.DATASET.LOAD_CONTENT) {
							menuItem.dataset.loadContent = item.PARAMS.DATASET.LOAD_CONTENT;
						}

						if (item.PARAMS.DATASET.IGNORE_AUTOBINDING) {
							menuItem.dataset.sliderIgnoreAutobinding = item.PARAMS.DATASET.IGNORE_AUTOBINDING;
						}
					}
				}

				menu.push(menuItem);
			});

			if (menu.length > 0) {
				this.moreMenu = MenuManager.create(
					'toolbar-popup-menu',
					document.querySelector('.market-toolbar__popup-target'),
					menu,
					{
						closeByEsc : true,
						autoHide : true,
						angle: true,
						offsetLeft: 13,
					}
				);
			}
		},
		showMenu: function () {
			if (this.moreMenu) {
				this.moreMenu.toggle();
			}
		},
		createSearchFilterMenu: function () {
			if (
				!this.searchFilters ||
				!this.searchFilters.LIST ||
				!this.searchFilters.CURRENT ||
				!BX.type.isArray(this.searchFilters.LIST)
			) {
				return;
			}

			let menu = [];
			this.searchFilters.LIST.forEach((item) => {
				let menuItem = {
					id: item.CODE,
					text: item.NAME,
					className: item.CLASS,
					onclick : (event, item) => {
						if (this.search.loader) {
							return;
						}

						if (!BX.hasClass(item.layout.item, "--accept")) {
							this.closeSearchFilterMenu();
							this.searchFilterMenu.getMenuItems().forEach((mItem) => {
								if (BX.hasClass(mItem.layout.item, "--accept")) {
									BX.removeClass(mItem.layout.item, "--accept");
								}
							});

							BX.addClass(item.layout.item, "--accept");
							this.search.currentFilter = item.id;

							if (this.showSearchResult()) {
								this.runSearch();
							}
						}
					}
				};

				if (this.searchFilters.CURRENT === menuItem.id) {
					this.search.currentFilter = menuItem.id;
					menuItem.className += " --accept";
				}

				menu.push(menuItem);
			});

			if (this.search.currentFilter.length <= 0 && menu[0]) {
				this.search.currentFilter = menu[0].id;
				menu[0].className += " --accept";
			}

			if (menu.length > 0) {
				this.searchFilterMenu = new Menu({
					bindElement: this.$refs.marketSearchItem,
					className: "market-toolbar__search-menu",
					width: 257,
					items: menu,
				});
			}
		},
		showSearchFilterMenu: function () {
			if (this.searchFilterMenu) {
				this.searchFilterMenu.show();
			}
		},
		closeSearchFilterMenu: function () {
			if (this.searchFilterMenu) {
				this.searchFilterMenu.close();
			}
		},
		needLoadNextPage: function(el) {
			if (
				!el ||
				!el.scrollHeight ||
				this.search.currentPage >= this.search.pages ||
				this.search.loader2
			) {
				return false;
			}

			return el.scrollTop >= el.scrollHeight - (el.offsetHeight * 1.5);
		},
		onPopupClick: function (event) {
			if (event.target.closest('.market-menu-catalog') === null) {
				this.closeDropdown();
			}
		},
		onSearchButtonClick: function (event) {
			if (this.searchFocus) {
				this.cleanSearch();
				BX('market-search-input').focus();
			} else {
				this.setSearchFocus();
			}
		},
		cleanSearch: function () {
			this.search.text = '';
			this.search.foundApps = [];
			this.searchResult = false;
		},
		closeMoreMenu: function () {
			if (this.moreMenu) {
				this.moreMenu.close();
			}

			if (this.dropdownShown) {
				this.closeDropdown();
			}
		},
		mouseOverCategory: function (categoryIndex) {
			this.hoverCategory = categoryIndex;
		},
		showSubCategories: function (categoryIndex) {
			return this.hoverCategory === categoryIndex;
		},
		setSearchFocus: function () {
			this.searchFocus = true;
			this.catalogShown = false;
			if (!this.dropdownShown) {
				this.showDropdown();
			}
		},
		catalogClick: function () {
			if (this.dropdownShown) {
				if (this.catalogShown) {
					this.closeDropdown();
				} else if (this.searchFocus) {
					this.catalogShown = true;
					this.searchFocus = false;
				}
			} else {
				this.catalogShown = true;
				this.showDropdown();
			}
		},
		cleanSearchFocus: function () {
			// this.searchFocus = false;
		},
		showDropdown: function () {
			this.dropdownShown = !this.dropdownShown;
			if (this.dropdownShown) {
				let marketToolbar = document.querySelector('[data-role="market-toolbar"]');
				let catalogPopup = document.querySelector('[data-role="catalog-popup"]');
				catalogPopup.style.top = marketToolbar.clientHeight + 'px';
				this.lockBody();
			}
		},
		lockBody: function() {
			const body = document.body;
			if (body)
			{
				let getPadding = (target) => {
					const curentPaddingRight = parseInt(window.getComputedStyle(target).paddingRight);
					return curentPaddingRight
							? curentPaddingRight + this.getScrollWidth()
							: this.getScrollWidth();
				};

				body.style.setProperty('overflow', 'hidden');
				this.$refs.marketToolbar.style.setProperty('padding-right', (29 + this.getScrollWidth()) + 'px');

				const marketWrapper = document.querySelector('.market-wrapper-content');
				if (marketWrapper)
				{
					marketWrapper.style.setProperty('padding-right', getPadding(marketWrapper) + 'px');
				}

				const marketWrapperInner = document.getElementById('market-catalog-container-id');
				if (marketWrapperInner)
				{
					marketWrapperInner.style.setProperty('padding-right', getPadding(marketWrapperInner) + 'px');
				}

				const marketContainerSlider = document.querySelector('.market-container-slider');
				if (marketContainerSlider)
				{
					marketContainerSlider.style.setProperty('padding-right', getPadding(marketContainerSlider) + 'px');
				}

			}
		},
		getScrollWidth: function()
		{
			const div = Tag.render`<div style="overflow-y: scroll; width: 50px; height: 50px; opacity: 0; pointer-events: none; position: absolute;"></div>`
			document.body.appendChild(div);
			const scrollWidth = div.offsetWidth - div.clientWidth
			Dom.remove(div);
			return scrollWidth;
		},
		unLockBody: function() {
			const body = document.body;
			if (body)
			{
				body.style.removeProperty('overflow');
				this.$refs.marketToolbar.style.removeProperty('padding-right');

				const marketWrapper = document.querySelector('.market-wrapper-content');
				if (marketWrapper)
				{
					marketWrapper.style.removeProperty('padding-right');
				}

				const marketWrapperInner = document.getElementById('market-catalog-container-id');
				if (marketWrapperInner)
				{
					marketWrapperInner.style.removeProperty('padding-right');
				}

				const marketContainerSlider = document.querySelector('.market-container-slider');
				if (marketContainerSlider)
				{
					marketContainerSlider.style.removeProperty('padding-right');
				}
			}
		},
		closeDropdown: function () {
			this.unLockBody();
			this.dropdownShown = false;
			this.searchFocus = false;
			this.catalogShown = false;
		},
		isEmptySearch: function () {
			return this.searchResult && this.search.foundApps.length <= 0;
		},
		showSearchResult: function () {
			return this.searchResult && !this.search.loader;
		},
		runSearch: function () {
			if (this.search.text.length <= 0) {
				this.searchResult = false;
				return;
			}

			this.search.loader = true;
			this.search.currentPage = 1;
			this.loadItems();
		},
		loadItems: function (append) {
			append = append || false;

			const searchText = this.search.text;
			this.search.notFoundText = searchText;

			BX.ajax.runAction('market.Search.getApps', {
				data: {
					text: searchText,
					page: this.search.currentPage,
					area: this.search.currentFilter,
					order: this.search.order.currentValue,
				}
			}).then(
				response => {
					this.defaultSearchProcess();
					if (response.data && BX.type.isArray(response.data.apps)) {
						this.search.currentPage = (response.data.apps.length > 0) ? parseInt(response.data.cur_page, 10) : 1;
						this.search.pages = (response.data.apps.length > 0) ? parseInt(response.data.pages, 10) : 1;

						if (!append) {
							this.search.resultCount = (response.data.apps.length > 0) ? parseInt(response.data.result_count, 10) : '';
						}

						if (append) {
							this.search.foundApps = this.search.foundApps.concat(response.data.apps);
							return;
						}

						this.search.foundApps = response.data.apps;

						if (response.data.sort_info) {
							if (this.searchOrderMenu) {
								this.searchOrderMenu.destroy();
							}

							this.createSearchOrderMenu(response.data.sort_info);
						}

						if (this.searchAction.length > 0) {
							try {
								eval(this.searchAction.replace('#SEARCH_TEXT#', searchText));
							} catch (e) {}
						}
					}
				},
				response => {
					this.defaultSearchProcess();
				},
			);
		},
		defaultSearchProcess: function () {
			this.searchResult = true;
			this.search.loader = false;
			this.search.loader2 = false;
		},
		getAppIcon: function (appItem) {
			return appItem.IS_SITE_TEMPLATE === 'Y' ? appItem.SITE_PREVIEW : appItem.ICON;
		},
		getAppDescription: function (appItem) {
			if (
				appItem.hasOwnProperty('CATEGORIES') &&
				BX.Type.isArray(appItem.CATEGORIES) &&
				appItem.CATEGORIES.length > 0
			) {
				return appItem.CATEGORIES[0];
			}

			return '';
		},
		openSubscriptionSlider: function () {
			if (this.marketAction.length > 0) {
				try {
					eval(this.marketAction);
				} catch (e) {}
			}

			top.BX.UI.InfoHelper.show(this.$root.marketSlider);
		},
		createSearchOrderMenu: function(sortInfo) {
			if (
				!sortInfo ||
				!sortInfo.LIST ||
				!sortInfo.CURRENT ||
				!BX.type.isArray(sortInfo.LIST)
			) {
				return;
			}

			this.search.order.menuItems = [];
			sortInfo.LIST.forEach((item) => {
				let menuItem = {
					id: item.VALUE,
					text: item.NAME,
					className: 'market-toolbar-popup',
					onclick : (event, item) => {
						if (!BX.hasClass(item.layout.item, "--check")) {
							this.closeSearchOrderMenu();
							this.searchOrderMenu.getMenuItems().forEach((mItem) => {
								if (BX.hasClass(mItem.layout.item, "--check")) {
									BX.removeClass(mItem.layout.item, "--check");
								}
							});

							BX.addClass(item.layout.item, "--check");
							this.search.order.currentValue = item.id;
							this.search.order.currentName = item.text;

							this.runSearch();
						}
					}
				};

				if (Object.keys(sortInfo.CURRENT.VALUE)[0] === Object.keys(menuItem.id)[0]) {
					this.search.order.currentValue = menuItem.id;
					this.search.order.currentName = menuItem.text;
					menuItem.className += " --check";
				}

				this.search.order.menuItems.push(menuItem);
			});

			if (!this.existOrder && this.search.order.menuItems[0]) {
				this.search.order.currentValue = this.search.order.menuItems[0].id;
				this.search.order.currentName = this.search.order.menuItems[0].text;
				this.search.order.menuItems[0].className += " --check";
			}

			this.createOrderMenuObject();
		},
		createOrderMenuObject: function() {
			if (this.search.order.menuItems.length > 0) {
				this.searchOrderMenu = new Menu({
					bindElement: this.$refs.resultDropdown,
					className: "market-search__order-menu",
					items: this.search.order.menuItems
				});
			}
		},
		showSearchOrderMenu: function () {
			this.createOrderMenuObject();
			if (this.searchOrderMenu) {
				this.searchOrderMenu.show();
			}
		},
		closeSearchOrderMenu: function () {
			if (this.searchOrderMenu) {
				this.searchOrderMenu.close();
			}
		},
	},
	template: `
		<div id="market-toolbar-wrapper">
			<div class="market-toolbar"
				 :class="{'--popup-active': dropdownShown}"
				 data-role="market-toolbar"
				 ref="marketToolbar"
			>
				<div class="market-toolbar__title">
					<svg class="market-toolbar__title_svg" width="33" height="30" viewBox="0 0 33 30" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path fill-rule="evenodd" clip-rule="evenodd" d="M25.9055 19.3538C25.8682 19.4242 25.8486 19.5029 25.8486 19.5829L25.8489 24.681C25.849 26.635 27.4098 28.2198 29.3342 28.2198C31.2567 28.2198 32.8144 26.6381 32.8143 24.6861L32.8135 8.24324C32.8135 7.74136 32.1531 7.57235 31.9188 8.01424L25.9055 19.3538Z" fill="#D5D7DB"/>
						<path d="M10.7678 5.70465C12.4386 2.55406 16.3296 1.38682 19.4586 3.09754V3.09754C19.6542 3.20446 19.7281 3.45077 19.6236 3.64768L7.6268 26.2708C6.73923 27.9446 4.67214 28.5647 3.00983 27.6559V27.6559C1.34753 26.747 0.719481 24.6534 1.60706 22.9797L10.7678 5.70465Z" fill="#2FC6F6"/>
						<path d="M22.1018 5.70465C23.7726 2.55406 27.6635 1.38682 30.7926 3.09754V3.09754C30.9882 3.20446 31.0621 3.45077 30.9576 3.64768L18.9608 26.2708C18.0732 27.9446 16.0061 28.5647 14.3438 27.6559V27.6559C12.6815 26.747 12.0535 24.6534 12.941 22.9797L22.1018 5.70465Z" fill="#9DCF00"/>
					</svg>
					<div class="market-toolbar__title-text">
						<span v-if="$root.isMainPage">
							{{ getMarketLogoTitle }}
						</span>
						<a class="market-toolbar__logo_link market-link-to-home"
						   v-else
						   data-slider-ignore-autobinding="true"
						   :href="MarketLinks.mainLink()"
						   data-load-content="main"
						   @click.prevent="$root.emitLoadContent"
						>
							{{ getMarketLogoTitle }}
						</a>

						<div class="market-toolbar__title-description" 
							 v-if="$root.totalApps > 0"
						>{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_MARKET_TOTAL_APPS', {'#TOTAL_APPS#': $root.totalApps}) }}</div>
					</div>
				</div>
				<button class="ui-btn ui-btn-primary ui-btn-icon- ui-btn-no-caps market-toolbar__btn_icon-catalog"
						:class="{'--search-active': searchFocus}"
						@click="catalogClick"
				>
					<span class="market-toolbar__btn_icon-catalog-text">{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_CATALOG_TITLE') }}</span>
				</button>
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-round market-toolbar__search">
					<div ref="marketSearchItem">
						<div class="market-toolbar__search-item"
							 v-if="search.currentFilter"
							 @click="showSearchFilterMenu"
						>
							<span class="market-toolbar__search-item-text">{{ getSearchFilterName }}</span>
						</div>
					</div>
					<input type="text"
						   id="market-search-input"
						   ref="marketSearchInput"
						   :placeholder="$Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_SEARCH_PLACEHOLDER'+ $root.marketNameMessageCode)"
						   autocomplete="off"
						   v-model="search.text"
						   class="ui-ctl-element ui-ctl-textbox"
						   :class="{'--active': searchFocus}"
						   @focus="setSearchFocus()"
						   @blur="cleanSearchFocus()"
						   @input="onSearch"
					>
					<button class="ui-ctl-after ui-ctl-icon-search"
							:class="{'--show': !searchFocus, '--hide': searchFocus}"
							@click="onSearchButtonClick"
					></button>
					<button class="ui-ctl-after ui-ctl-icon-clear"
							:class="{'--hide': !searchFocus, '--show': searchFocus}"
							@click="onSearchButtonClick"
					></button>
				</div>
		
				<div class="market-toolbar__nav">
					<div class="market-toolbar__nav_item">
						<a class="market-toolbar__nav_link"
						   data-slider-ignore-autobinding="true"
						   :href="MarketLinks.favoritesLink()"
						   data-load-content="list"
						   @click.prevent="$root.emitLoadContent"
						>
							<div class="market-toolbar__nav_icon">
								<span class="market-toolbar__nav_counter"
									  v-if="$root.favNumbers > 0"
								>
									{{ $root.getFavNumbers }}
								</span>
								<svg v-if="$root.favNumbers > 0" width="24"
									 height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd"
										  clip-rule="evenodd"
										  d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z"
										  fill="#a8adb4" transform="translate(1, 3)"/>
								</svg>
								<svg v-else width="24"
									 height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd"
										  clip-rule="evenodd"
										  d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z"
										  fill="#dfe0e3" transform="translate(1, 3)"/>
								</svg>
							</div>
							<span class="market-toolbar__nav_text">{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_FAVORITES_TITLE') }}</span>
						</a>
					</div>
					<div class="market-toolbar__nav_item"
						 v-if="$root.showMarketIcon === 'Y'"
					>
						<a href="#" class="market-toolbar__nav_link"
						   @click="openSubscriptionSlider"
						>
							<div class="market-toolbar__nav_icon">
								<span class="market-toolbar__nav_counter --battery --active">
									<svg width="18" height="11" viewBox="0 0 18 11" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M0 2.5C0 1.39543 0.895431 0.5 2 0.5H14.2116C15.3162 0.5 16.2116 1.39543 16.2116 2.5V3.69472C16.2954 3.67192 16.3836 3.65975 16.4747 3.65975H16.6854C17.2377 3.65975 17.6854 4.10746 17.6854 4.65975V6.40589C17.6854 6.95817 17.2377 7.40589 16.6854 7.40589H16.4747C16.3836 7.40589 16.2954 7.39372 16.2116 7.37091V8.56565C16.2116 9.67022 15.3162 10.5656 14.2116 10.5656H2C0.89543 10.5656 0 9.67022 0 8.56565V2.5ZM1.1683 2.78667C1.1683 2.23439 1.61602 1.78667 2.1683 1.78667H14.0433C14.5956 1.78667 15.0433 2.23439 15.0433 2.78667V8.27896C15.0433 8.83124 14.5956 9.27896 14.0433 9.27896H2.1683C1.61602 9.27896 1.1683 8.83124 1.1683 8.27896V2.78667Z" fill="#828B95"/>
										<path d="M5.40069 2.9043H2.45312V8.16166H5.40069V2.9043Z" fill="#2FC6F6"/>
										<path d="M9.52178 2.9043H6.57422V8.16166H9.52178V2.9043Z" fill="#2FC6F6"/>
										<path d="M13.6429 2.9043H10.6953V8.16166H13.6429V2.9043Z" fill="#2FC6F6"/>
									</svg>
								</span>
								<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M15.2988 6.25693C16.4649 4.05798 19.1806 3.24332 21.3645 4.43731C21.501 4.51194 21.5525 4.68385 21.4797 4.82128L13.1065 20.6111C12.487 21.7792 11.0443 22.212 9.88409 21.5777C8.72389 20.9434 8.28555 19.4822 8.90503 18.314L15.2988 6.25693Z" fill="#A8ADB4"/>
									<path d="M7.38082 6.25693C8.54689 4.05798 11.2626 3.24332 13.4465 4.43731C13.583 4.51194 13.6346 4.68385 13.5617 4.82128L5.18853 20.6111C4.56905 21.7792 3.12633 22.212 1.96613 21.5777C0.805923 20.9434 0.36758 19.4822 0.987059 18.314L7.38082 6.25693Z" fill="#A8ADB4"/>
									<path fill-rule="evenodd" clip-rule="evenodd" d="M17.9577 15.7818C17.9316 15.8309 17.918 15.8858 17.918 15.9416L17.9181 19.4999C17.9182 20.8637 19.0076 21.9697 20.3507 21.9697C21.6925 21.9697 22.7797 20.8658 22.7796 19.5034L22.7791 8.02715C22.779 7.67686 22.3182 7.5589 22.1546 7.86732L17.9577 15.7818Z" fill="#A8ADB4"/>
								</svg>
							</div>
							<span class="market-toolbar__nav_text">{{ getMarketToolbarTitle }}</span>
						</a>
					</div>
					<div class="market-toolbar__nav_item">
						<span class="market-toolbar__nav_link market-toolbar__popup-target"
							  @click="showMenu"
						>
							<div class="market-toolbar__nav_icon">
								<span class="market-toolbar__nav_counter"
									  v-if="$root.numUpdates > 0"
								>
									{{ $root.getNumUpdates }}
								</span>
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#A8ADB4"/>
									<path d="M12 14C13.1046 14 14 13.1046 14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14Z" fill="#A8ADB4"/>
									<path d="M20 12C20 13.1046 19.1046 14 18 14C16.8954 14 16 13.1046 16 12C16 10.8954 16.8954 10 18 10C19.1046 10 20 10.8954 20 12Z" fill="#A8ADB4"/>
								</svg>
							</div>
							<span class="market-toolbar__nav_text">{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_MORE') }}</span>
						</span>
					</div>
				</div>
			</div>
		
			<div class="market-menu-catalog__popup"
				 :class="{'--active': dropdownShown}"
				 data-role="catalog-popup"
			>
				<div class="market-menu-catalog__container" @click="onPopupClick">
					<div class="market-menu-catalog" data-role="market-menu-catalog">
						<div class="market-menu-catalog__nav">
							<div class="market-menu-catalog__nav-items --topical">
								<a class="market-menu-catalog__nav-item_link-topical"
								   :href="MarketLinks.categoryLink(categoryTop.CODE)"
								   v-for="categoryTop in categories.FIX_ITEMS"
								   data-slider-ignore-autobinding="true"
								   data-load-content="list"
								   @click.prevent="$root.emitLoadContent"
								>
									<div class="market-menu-catalog__nav-item_link-text"
										 :title="categoryTop.NAME"
									>{{ categoryTop.NAME }}</div>
								</a>
							</div>
							<div class="market-menu-catalog__nav-items">
								<a class="market-menu-catalog__nav-item_link"
								   :class="{'--active': hoverCategory == index}"
								   :href="MarketLinks.categoryLink(category.CODE)"
								   v-for="(category, index) in categories.ITEMS"
								   data-slider-ignore-autobinding="true"
								   data-load-content="list"
								   @click.prevent="$root.emitLoadContent"
								   @mouseover="mouseOverCategory(index)"
								>
									<div class="market-menu-catalog__nav-item_link-text"
										 :title="category.NAME"
									>{{ category.NAME }}</div>
									<span class="market-menu-catalog__nav-item_link-amount">{{ category.CNT }}</span>
								</a>
							</div>
						</div>
		
						<div class="market-menu-catalog__middle-content" ref="searchAutoScroll">
							<div class="market-menu-catalog__subnav" v-if="!searchFocus">
								<template v-for="(category, index) in categories.ITEMS">
									<div class="market-menu-catalog__subnav-items"
										 v-if="showSubCategories(index) && category.SUB_ITEMS"
									>
										<a class="market-menu-catalog__subnav-item_link"
										   :href="MarketLinks.categoryLink(subCategory.CODE)"
										   v-for="subCategory in category.SUB_ITEMS"
										   data-slider-ignore-autobinding="true"
										   data-load-content="list"
										   @click.prevent="$root.emitLoadContent"
										>
											<span class="market-menu-catalog__subnav-item_link-text">
												{{ subCategory.NAME }}
											</span>
										</a>
									</div>
								</template>
							</div>
		
							<div class="market-menu-catalog__search" v-else>
								<div class="market-menu-catalog__search-empty" 
									 v-if="!searchResult && !search.loader"
								>
									<svg width="92" height="92" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M56.6536 62.8186C52.8102 65.3422 48.2117 66.8102 43.2703 66.8102C29.7864 66.8102 18.8555 55.8793 18.8555 42.3953C18.8555 28.9114 29.7864 17.9805 43.2703 17.9805C56.7543 17.9805 67.6852 28.9114 67.6852 42.3953C67.6852 47.3367 66.2172 51.9352 63.6936 55.7786L76.3834 68.4684C77.8804 69.9654 77.8804 72.3925 76.3834 73.8895L74.7645 75.5084C73.2675 77.0054 70.8404 77.0054 69.3434 75.5084L56.6536 62.8186ZM60.7095 42.3953C60.7095 52.0267 52.9017 59.8345 43.2703 59.8345C33.6389 59.8345 25.8311 52.0267 25.8311 42.3953C25.8311 32.7639 33.6389 24.9561 43.2703 24.9561C52.9017 24.9561 60.7095 32.7639 60.7095 42.3953Z" fill="#DFE0E3"/>
									</svg>
									<div class="market-menu-catalog__search-info">
										{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_LOOKING_RIGHT_APPS') }}
									</div>
								</div>
								<img class="market-search-skeleton-img"
									 src="/bitrix/images/market/slider/search.svg"
									 v-if="search.loader"
								>
								<template v-if="showSearchResult()">
									<div class="market-menu-catalog__search-empty" v-if="isEmptySearch()">
										<svg width="92" height="92" viewBox="0 0 92 92" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd" d="M56.6536 62.8186C52.8102 65.3422 48.2117 66.8102 43.2703 66.8102C29.7864 66.8102 18.8555 55.8793 18.8555 42.3953C18.8555 28.9114 29.7864 17.9805 43.2703 17.9805C56.7543 17.9805 67.6852 28.9114 67.6852 42.3953C67.6852 47.3367 66.2172 51.9352 63.6936 55.7786L76.3834 68.4684C77.8804 69.9654 77.8804 72.3925 76.3834 73.8895L74.7645 75.5084C73.2675 77.0054 70.8404 77.0054 69.3434 75.5084L56.6536 62.8186ZM60.7095 42.3953C60.7095 52.0267 52.9017 59.8345 43.2703 59.8345C33.6389 59.8345 25.8311 52.0267 25.8311 42.3953C25.8311 32.7639 33.6389 24.9561 43.2703 24.9561C52.9017 24.9561 60.7095 32.7639 60.7095 42.3953Z" fill="#DFE0E3"/>
										</svg>
										<div class="market-menu-catalog__search-info">
											{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_NO_SEARCH_RESULT') }}
										</div>
										<div class="market-menu-catalog__search-text">'{{ search.notFoundText }}'</div>
									</div>
									<template v-else>
										<div class="market-menu-catalog__search-head">
											<div class="market-menu-catalog__search-result-value">
												{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_SEARCH_RESULT_COUNT', {'#RESULT_COUNT#': search.resultCount}) }}
											</div>
											<div
												ref="resultDropdown"
												@click="showSearchOrderMenu()"
												class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown market-menu-catalog__search-result-dropdown"
												:title="search.order.currentName"
												v-if="existOrder"
											>
												<div class="ui-ctl-after ui-ctl-icon-angle"></div>
												<div class="ui-ctl-element">{{ search.order.currentName }}</div>
											</div>
										</div>
										<a class="market-menu-catalog__search-item"
										   v-for="appItem in search.foundApps"
										   :href="MarketLinks.appDetail(appItem, {from: 'search', text: search.text})"
										   @click="MarketLinks.openSiteTemplate($event, appItem.IS_SITE_TEMPLATE === 'Y')"
										>
											<div class="market-menu-catalog__search-item_img-block">
												<img class="market-menu-catalog__search-item_img"
													 :src="getAppIcon(appItem)" alt="1"
												>
											</div>
											<div class="market-menu-catalog__search-item_info">
												<div class="market-menu-catalog__search-item_name">
														<span class="market-menu-catalog__search-item_title">
															{{ appItem.NAME }}
														</span>
													<span class="market-menu-catalog__search-item_label"
														  :class="{'--blue': appItem.PRICE_POLICY_BLUE}"
													>{{ appItem.PRICE_POLICY_NAME }}</span>
												</div>
												<div class="market-menu-catalog__search-item_category">
													{{ appItem.APP_SEARCH_TYPE }} &#183; {{ getAppDescription(appItem) }}
												</div>
												<div class="market-rating__container">
													<RatingStars
														:rating="appItem.RATING"
														:reviewsNumber="appItem.REVIEWS_NUMBER"
													/>
													
													<div class="market-rating__download">
														<span class="market-rating__download-icon"></span>
														<div class="market-rating__download-amount">{{ appItem.NUM_INSTALLS }}</div>
													</div>
												</div>
											</div>
										</a>
										<img class="market-search-skeleton-img"
											 src="/bitrix/images/market/slider/search.svg"
											 v-if="search.loader2"
										>
									</template>
								</template>
							</div>
						</div>
		
						<div class="market-menu-catalog__info">
							<div class="market-menu-catalog__suggestions">
								<div class="market-menu-catalog__suggestions-content">
									<div class="market-menu-catalog__suggestions_title">
										{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_DIDNT_FIND_SUITABLE_SOLUTION') }}
									</div>
									<div class="market-menu-catalog__suggestions_description">
										{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_MAKE_OR_PUBLISH_YOUR_INTEGRATION') }}
									</div>
								</div>
								<a class="market-menu-catalog__suggestions_btn"
								   :href="getSearchLink" 
								   target="_blank"
								>
									{{ $Bitrix.Loc.getMessage('MARKET_TOOLBAR_JS_DETAILED') }}
									<svg  class="market-menu-catalog__suggestions_btn-svg" width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M10.1588 6.84343L14.6859 11.3705H5.00781V13.6299H14.6859L10.1588 18.1569L11.7563 19.7544L19.0104 12.5003L11.7563 5.24609L10.1588 6.84343Z" fill="#525C69"/>
									</svg>
								</a>
							</div>

							<!-- TODO -->

						</div>
					</div>
					<div class="market-menu-catalog__popup-overlay"></div>
				</div>
		
			</div>
		</div>
	`,
}