import {Slider} from "market.slider";
import {ListItem} from "market.list-item";
import {Rating} from "market.rating";
import {PopupInstall} from "market.popup-install";
import {PopupUninstall} from "market.popup-uninstall";
import {ScopeList} from "market.scope-list";
import {marketInstallState} from "market.install-store";
import {marketUninstallState} from "market.uninstall-store";
import {EventEmitter} from 'main.core.events';
import { MenuManager } from 'main.popup';
import { Ears } from 'ui.ears'
import 'ui.design-tokens';

import { mapState, mapActions } from 'ui.vue3.pinia';
import "./detail-component.css";

export const DetailComponent = {
	components: {
		Slider, ListItem, Rating, PopupInstall, PopupUninstall, ScopeList,
	},
	props: [
		'params', 'result',
	],
	data() {
		return {
			headerIsFixed: false,
			hideDescription: true,
			descriptionWrapper: null,
			descriptionHeight: 0,
			showYouMayLike: false,

			popupShown: false,
			installResult: false,

			testInstallProcess: false,

			menuPopup1: null,
			menuPopup2: null,
		};
	},
	computed: {
		isFavoriteApp: function () {
			return this.result.APP.IS_FAVORITE === 'Y';
		},
		favoriteButtonTitle: function () {
			return this.isFavoriteApp ? this.$Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_RM_FAVORITE') : this.$Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_ADD_FAVORITE');
		},
		installDescriptionIsLanding: function () {
			return this.result.APP.LINK_INSTALL.length > 0
		},
		widthInstallSlider: function () {
			return this.installDescriptionIsLanding ? 880 : false;
		},
		pricePolicySlider: function () {
			return this.result.PRICE_POLICY_SLIDER && this.result.PRICE_POLICY_SLIDER.length > 0 ? this.result.PRICE_POLICY_SLIDER : '';
		},
		showInstallButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('INSTALL') && this.result.APP.BUTTONS.INSTALL === 'Y';
		},
		showNoAccessInstallButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('NO_ACCESS_INSTALL') && this.result.APP.BUTTONS.NO_ACCESS_INSTALL === 'Y';
		},
		showConfigButton: function() {
			return Object.prototype.hasOwnProperty.call(this.result.APP.BUTTONS, 'CONFIGURATION_IMPORT')
				&& this.result.APP.BUTTONS.CONFIGURATION_IMPORT === 'Y';
		},
		showReimportButton: function() {
			return Object.prototype.hasOwnProperty.call(this.result.APP.BUTTONS, 'REIMPORT')
				&& this.result.APP.BUTTONS.REIMPORT === 'Y';
		},
		showUpdateButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('UPDATE') && this.result.APP.BUTTONS.UPDATE === 'Y';
		},
		showDeleteButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('DELETE') && this.result.APP.BUTTONS.DELETE === 'Y';
		},
		showPreviewButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('OPEN_PREVIEW') && this.result.APP.BUTTONS.OPEN_PREVIEW === 'Y';
		},
		showOpenAppButton: function () {
			return this.result.APP.hasOwnProperty('BUTTON_OPEN_APP') && this.result.APP.BUTTON_OPEN_APP.length > 0;
		},
		showRightsButton: function () {
			return this.result.APP.BUTTONS.hasOwnProperty('RIGHTS') && this.result.APP.BUTTONS.RIGHTS === 'Y';
		},
		needOpenImport: function () {
			return this.result.hasOwnProperty('OPEN_IMPORT') && this.result.OPEN_IMPORT === 'Y';
		},
		isTestInstall: function () {
			return this.result.hasOwnProperty('START_INSTALL') && this.result.START_INSTALL === true;
		},
		getCategoriesCount: function () {
			return parseInt(this.result.APP.CATEGORIES.length, 10);
		},
		getContactDeveloper: function () {
			return this.result.APP.CONTACT_DEVELOPER ?? '';
		},
		getRequestDemoInfo: function () {
			return this.result.APP.REQUEST_DEMO ?? '';
		},
		countReviews: function () {
			return parseInt(this.result.APP.REVIEWS.RATING.COUNT, 10);
		},
		totalRating: function () {
			if (this.result.APP.REVIEWS.RATING && this.result.APP.REVIEWS.RATING.RATING) {
				return this.result.APP.REVIEWS.RATING.RATING
			}

			return 0;
		},
		canShowAppForm: function () {
			return this.result.APP.hasOwnProperty('HAS_APP_FORM')
				&& this.result.APP.HAS_APP_FORM === true
				&& this.result.APP.hasOwnProperty('INSTALLED')
				&& this.result.APP.INSTALLED === 'Y';
		},
		...mapState(marketInstallState, ['installStep', 'slider', 'timer', 'installError', ]),
	},
	created () {
		window.addEventListener('scroll', this.handleScroll);
		this.checkFixedHeader();
	},
	mounted() {
		this.descriptionWrapper = document.querySelector('[data-role="market-detail__wrapper"]');
		this.descriptionHeight = document.querySelector('[data-role="market-detail__content"]').scrollHeight;
		if (this.descriptionHeight <= 565) {
			this.hideDescription = false;
			this.descriptionWrapper.style.maxHeight = 'none';
		} else {
			this.descriptionWrapper.style.height = `${this.descriptionWrapper.clientHeight}px`;
			this.descriptionWrapper.style.maxHeight = 'none';
		}

		this.setAppInfo(this.result.APP);

		EventEmitter.subscribe('BX.Main.Popup:onShow', this.onShowPopup);
		EventEmitter.subscribe('BX.Main.Popup:onClose', this.onClosePopup);

		this.initOther();

		if(this.needOpenImport) {
			setTimeout(() => this.configApp(), 500);
		}

		this.createPopupMenu();

		if (this.isTestInstall) {
			this.testInstall();
		}
	},
	destroyed () {
		window.removeEventListener('scroll', this.handleScroll);
	},
	methods: {
		onShowPopup: function (event) {
			if (event.target.popupContainer.id === 'menu-popup-detail-popup-menu-2') {
				return;
			}

			this.popupShown = true;
			this.headerIsFixed = false;
		},
		onClosePopup: function () {
			this.popupShown = false;
			this.checkFixedHeader();

			if (this.installStep === 2 && this.installError) {
				this.reloadSlider();
			} else if (this.installStep === 3) {
				clearTimeout(this.timer);
				this.reloadSlider();

				if (this.closeDetailAfterInstall()) {
					this.openApplication();
				}
			}
		},
		handleScroll: function () {
			this.checkFixedHeader();
		},
		checkFixedHeader: function () {
			this.headerIsFixed = !!(scrollY > 204 && !this.popupShown)
		},
		moreDescriptionClick: function () {
			this.hideDescription = false;

			this.descriptionWrapper.clientHeight; // it's needed, Tyutereva magic
			this.descriptionWrapper.style.height = `${this.descriptionHeight}px`;
			this.descriptionWrapper.addEventListener('transitionend', this.setHeightAutoFunction);
		},
		setHeightAutoFunction: function() {
			this.descriptionWrapper.style.height = 'auto';
			this.descriptionWrapper.removeEventListener('transitionend', this.setHeightAutoFunction);
		},
		initOther: function () {
			if (this.showYouMayLike) {
				(new Ears({
					container: document.querySelector(".market-detail__catalog-elements"),
					smallSize: true,
					noScrollbar: true,
				})).init();
			}
		},
		feedbackHeaderClick: function () {
			window.scrollTo({
				top: document.querySelector('.market-detail__app-rating_feedback-content').getBoundingClientRect().top,
				behavior: 'smooth',
			});
		},
		favoritesEvent: function () {
			const action = (this.isFavoriteApp) ? 'rmFavorite' : 'addFavorite';
			this.changeFavorite(action);
		},
		changeFavorite: function (action) {
			BX.ajax.runAction('market.Favorites.' + action, {
				data: {
					appCode: this.result.APP.CODE,
				},
				analyticsLabel: {
					viewMode: 'detail',
					appCode: this.result.APP.CODE,
				},
			}).then(
				response => {
					if (
						response.data &&
						typeof response.data.total !== 'undefined' &&
						BX.type.isString(response.data.currentValue)
					) {
						let total = parseInt(response.data.total, 10);
						BX.SidePanel.Instance.postMessageAll(
							window,
							'total-fav-number', {
								total: total,
								appCode: this.result.APP.CODE,
								currentValue: response.data.currentValue,
							}
						);
						this.result.APP.IS_FAVORITE = response.data.currentValue;
					}
				},
				response => {},
			);
		},
		testInstall: function () {
			if (this.testInstallProcess) {
				return;
			}

			this.testInstallProcess = true;

			this.showInstallPopup();
		},
		installApp: function () {
			if (!this.showInstallButton) {
				return;
			}

			if (this.result.ACCESS_HELPER_CODE) {
				top.BX.UI.InfoHelper.show(this.result.ACCESS_HELPER_CODE);
				return;
			}

			this.showInstallPopup();
		},
		updateApp: function () {
			if (!this.showUpdateButton) {
				return;
			}

			this.showInstallPopup(true);
		},
		deleteApp: function () {
			this.setDeleteActionInfo(this.result.APP.ADDITIONAL_ACTION_DEL);
			this.deleteAction(this.result.APP.CODE)
		},
		configApp: function () {
			BX.SidePanel.Instance.open(this.result.IMPORT_PAGE);
		},
		createPopupMenu: function() {
			if (this.result.APP.MENU_ITEMS.length <= 0 && !this.showRightsButton) {
				return;
			}

			if (this.showRightsButton) {
				this.result.APP.MENU_ITEMS.push({
					text: this.$Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_BTN_ACCESS'),
					onclick: (event) => {
						this.menuPopup1.close();
						this.menuPopup2.close();
						this.setRights();
					},
				})
			}

			if (this.canShowAppForm)
			{
				this.result.APP.MENU_ITEMS.push({
					text: this.$Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_CONFIG'),
					onclick: (event) => {
						this.menuPopup1.close();
						this.menuPopup2.close();
						top.BX.Rest.AppForm.buildByAppWithLoader(this.result.APP.CODE, top.BX.Rest.EventType.DISPLAY).then((form) => {
							form.show();
						});
					},
				})
			}

			let menuParams = {
				closeByEsc : true,
				autoHide : true,
				angle: true,
				offsetLeft: 20,
			};

			this.menuPopup1 = MenuManager.create(
				'detail-popup-menu-1',
				this.$refs.marketDetailMenu,
				this.result.APP.MENU_ITEMS,
				menuParams
			);

			this.menuPopup2 = MenuManager.create(
				'detail-popup-menu-2',
				this.$refs.marketDetailHeaderFixedMenu,
				this.result.APP.MENU_ITEMS,
				menuParams
			);
		},
		showMenu1: function () {
			if (this.menuPopup1 !== null) {
				this.menuPopup1.show();
			}
		},
		showMenu2: function () {
			if (this.menuPopup2 !== null) {
				this.menuPopup2.show();
			}
		},
		setRights: function() {
			BX.Access.Init({
				other: {
					disabled: false,
					disabled_g2: true,
					disabled_cr: true
				},
				groups: {disabled: true},
				socnetgroups: {disabled: true}
			});


			BX.ajax.runAction(
				'market.Application.getRights',
				{
					data: {
						appCode: this.result.APP.CODE,
					},
					analyticsLabel: {
						viewMode: 'detail',
					},
				}
			).then((response) => {
				BX.Access.SetSelected(response.data, "bind");

				BX.Access.ShowForm({
					bind: "bind",
					showSelected: true,
					callback: (rights) => {
						BX.ajax.runAction(
							'market.Application.setRights',
							{
								data: {
									appCode: this.result.APP.CODE,
									rights: rights,
								},
								analyticsLabel: {
									viewMode: 'detail',
								},
							}
						).then((response) => {});
					}
				});
			});
		},
		pricePolicyClick: function () {
			if (!this.pricePolicySlider) {
				return;
			}

			if (this.result.ADDITIONAL_MARKET_ACTION) {
				try {
					eval(this.result.ADDITIONAL_MARKET_ACTION);
				} catch (e) {}
			}

			BX.UI.InfoHelper.show(this.pricePolicySlider);
		},
		...mapActions(marketInstallState, [
			'showInstallPopup', 'setAppInfo', 'openSliderWithContent', 'reloadSlider', 'isSubscriptionApp', 'isHiddenBuy',
			'closeDetailAfterInstall', 'openApplication',
		]),
		...mapActions(marketUninstallState, ['deleteAction', 'setDeleteActionInfo']),
	},
	template: `
		<div class="market-detail">
			<div class="market-detail__header-fixed"
				 :class="{'--fixed': headerIsFixed}"
			>
				<div class="market-detail__header-fixed_logo">
					<img :src="result.APP.ICON" alt="icon"
						 class="market-detail__header-fixed-logo_img">
				</div>
				<div class="market-detail__header-fixed_info">
					<div class="market-detail__header-fixed_info-name"
						 :title="result.APP.NAME"
					>
						{{ result.APP.NAME }}
					</div>
					<div class="market-detail__header-fixed_block-btn">
						<div>
							<button class="ui-btn ui-btn-success ui-btn-xs"
									v-if="showUpdateButton"
									@click="updateApp"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_REFRESH') }}
							</button>
							<button class="ui-btn ui-btn-success ui-btn-xs"
									v-if="showInstallButton"
									@click="installApp"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_INSTALL') }}
							</button>
							<button class="ui-btn ui-btn-success ui-btn-xs ui-btn-disabled"
									v-if="showNoAccessInstallButton"
									:title="$Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_ACCESS_DENIED')"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_INSTALL') }}
							</button>
							<button class="ui-btn ui-btn-success ui-btn-xs"
									v-if="showConfigButton"
									@click="configApp"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_CONFIG') }}
							</button>
							<button class="ui-btn ui-btn-success ui-btn-xs"
									v-if="showReimportButton"
									@click="configApp"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_REIMPORT') }}
							</button>
							<button class="ui-btn ui-btn-light-border ui-btn-xs"
									v-if="showDeleteButton"
									@click="deleteApp"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_DELETE') }}
							</button>
							<button class="ui-btn ui-btn-light-border ui-btn-xs"
									v-if="showPreviewButton"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_VIEW_DEMO') }}
							</button>
							<a class="ui-btn ui-btn-light-border ui-btn-xs"
							   v-if="showOpenAppButton"
							   :href="result.APP.BUTTON_OPEN_APP"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_OPEN_APP') }}
							</a>
						</div>
						<span class="market-detail__header-separator"></span>
						<div class="market-detail__header_info-available"
							 :class="{'market-detail__header_info-available-click': pricePolicySlider}"
							 @click="pricePolicyClick"
						>
							<template v-if="isSubscriptionApp()">
								<svg class="market-detail__header-fixed_info-available-svg" width="15" height="16"
									 viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="7.5" cy="8" r="7.5" fill="#8DBB00"/>
									<path d="M4.41361 7.14596L7.94914 10.6815L6.53493 12.0957L2.9994 8.56017L4.41361 7.14596Z"
										  fill="white"/>
									<path d="M12.1918 6.43885L6.53493 12.0957L5.12072 10.6815L10.7776 5.02464L12.1918 6.43885Z"
										  fill="white"/>
								</svg>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_AVAILABLE_IN_SUBCRIPTION') }}
							</template>
							<template v-else>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_IS_FREE') }}
							</template>
						</div>
						<template v-if="isHiddenBuy()">
							<span class="market-detail__header-separator"></span>
							<div class="market-detail__header_text">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_IN_APP_PURCHASES') }}</div>
						</template>
					</div>
				</div>
				<div class="market-detail__header-fixed_nav">
					<button class="ui-btn ui-btn-round ui-btn-sm market-detail__header-fixed_more-btn"
							ref="marketDetailHeaderFixedMenu"
							@click="showMenu2"
							v-show="menuPopup2 !== null"
					></button>
					<div class="market-detail__header-fixed_info-favorites-btn"
						 @click="favoritesEvent"
						 :title="favoriteButtonTitle"
					>
<!--						<svg class="market-detail__favorites-svg"-->
<!--							 :class="{'&#45;&#45;favorite': isFavoriteApp}"-->
<!--							 width="24" height="24" viewBox="0 0 24 24" fill="none"-->
<!--							 xmlns="http://www.w3.org/2000/svg">-->
<!--							<path class="market-detail__favorites-fill"-->
<!--								  d="M16.9087 3.86475C13.9501 3.86474 12.0024 6.30145 12.0024 6.30145C12.0024 6.30145 10.0548 3.86474 7.09612 3.86475C4.13747 3.86475 1.80078 6.1619 1.80078 9.16008C1.80078 14.9857 9.74639 19.1138 11.6132 20.0033C11.8628 20.1222 12.142 20.1222 12.3917 20.0033C14.2584 19.1138 22.204 14.9857 22.204 9.16008C22.204 6.1619 19.8674 3.86475 16.9087 3.86475Z"/>-->
<!--							<path class="market-detail__favorites-stroke"-->
<!--								  d="M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z"-->
<!--								  stroke-width="2"/>-->
<!--						</svg>-->
						<svg class="market-detail__favorites-svg"
							 :class="{'&#45;&#45;favorite': isFavoriteApp}"
							 width="24" height="24"  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path class="market-detail__favorites-fill"
								  d="M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z"
								  stroke-width="2"></path>
							<path class="market-detail__favorites-stroke" fill-rule="evenodd" clip-rule="evenodd"
								  d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z"
								  fill="#DFE0E3" transform="translate(1, 3)"/>
						</svg>
					</div>
				</div>
			</div>

			<div class="market-detail__header">
				<div class="market-detail__header-logo">
					<img :src="result.APP.ICON" alt="icon"
						 class="market-detail__header-logo_img">
				</div>
				<div class="market-detail__header-info">
					<div class="market-detail__header-info_name"
						 :title="result.APP.NAME"
					>
						{{ result.APP.NAME }}
					</div>
					<div class="market-detail__header-info_description"
						 :title="result.APP.SHORT_DESC"
						 v-html="result.APP.SHORT_DESC"
					></div>
					<div class="market-detail__header-info_block-btn">
						<button class="ui-btn ui-btn-success ui-btn-md"
								v-if="showUpdateButton"
								@click="updateApp"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_REFRESH') }}
						</button>
						<button class="ui-btn ui-btn-success ui-btn-md"
								v-if="showInstallButton"
								@click="installApp"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_INSTALL') }}
						</button>
						<button class="ui-btn ui-btn-success ui-btn-md ui-btn-disabled"
								v-if="showNoAccessInstallButton"
								:title="$Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_ACCESS_DENIED')"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_INSTALL') }}
						</button>
						<button class="ui-btn ui-btn-success ui-btn-md"
								v-if="showConfigButton"
								@click="configApp"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_CONFIG') }}
						</button>
						<button class="ui-btn ui-btn-success ui-btn-md"
								v-if="showReimportButton"
								@click="configApp"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_REIMPORT') }}
						</button>
						<button class="ui-btn ui-btn-light-border ui-btn-md"
								v-if="showDeleteButton"
								@click="deleteApp"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_DELETE') }}
						</button>
						<button class="ui-btn ui-btn-light-border ui-btn-md"
								v-if="showPreviewButton"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_VIEW_DEMO') }}
						</button>
						<a class="ui-btn ui-btn-light-border ui-btn-md"
						   v-if="showOpenAppButton"
						   :href="result.APP.BUTTON_OPEN_APP"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ACTION_JS_OPEN_APP') }}
						</a>
						<span class="market-detail__header-separator"></span>
						<div class="market-detail__header_info-available"
							 :class="{'market-detail__header_info-available-click': pricePolicySlider}"
							 @click="pricePolicyClick"
						>
							<template v-if="isSubscriptionApp()">
								<svg class="market-detail__header-fixed_info-available-svg" width="15" height="16"
									 viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="7.5" cy="8" r="7.5" fill="#8DBB00"/>
									<path d="M4.41361 7.14596L7.94914 10.6815L6.53493 12.0957L2.9994 8.56017L4.41361 7.14596Z"
										  fill="white"/>
									<path d="M12.1918 6.43885L6.53493 12.0957L5.12072 10.6815L10.7776 5.02464L12.1918 6.43885Z"
										  fill="white"/>
								</svg>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_AVAILABLE_IN_SUBCRIPTION') }}
							</template>
							<template v-else>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_IS_FREE') }}
							</template>
						</div>
						<template v-if="isHiddenBuy()">
							<span class="market-detail__header-separator"></span>
							<div class="market-detail__header_text">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_IN_APP_PURCHASES') }}</div>
						</template>
					</div>
				</div>
				<div class="market-detail__header-nav">
					<div class="market-detail__header-info_favorites-btn"
						 @click="favoritesEvent"
						 :title="favoriteButtonTitle"
					>
						<svg class="market-detail__favorites-svg"
							 :class="{'--favorite': isFavoriteApp}"
							 width="24" height="24"  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path class="market-detail__favorites-fill" d="M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z"
								  stroke-width="2"></path>
							<path class="market-detail__favorites-stroke" fill-rule="evenodd" clip-rule="evenodd"
								  d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z"
								  fill="#DFE0E3" transform="translate(1, 3)"/>
						</svg>
					</div>
					<button class="ui-btn ui-btn-round market-detail__more-btn"
							ref="marketDetailMenu"
							@click="showMenu1"
							v-show="menuPopup1 !== null"
					></button>
				</div>
			</div>

			<div class="market-detail__main-info">
				<div class="market-detail__main-info_item">
					<div class="market-detail__main-info_item-title">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_GRADE') }}</div>
					<div class="market-detail__main-info_item-details --with-bg --number --cursor-pointer"
						 @click="feedbackHeaderClick"
					>
						<template v-if="!countReviews || countReviews < 3">
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_FEW_RATINGS') }}
						</template>
						<div class="market-detail__main-info_stars-number" v-else>
							{{ totalRating }}<span class="market-detail__main-info_stars-all-number">/5</span>
							<svg class="market-rating__app-rating_star --active" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M5.53505 1.17539C5.70176 0.753947 6.29824 0.753947 6.46495 1.17539L7.55466 3.93021C7.62451 4.1068 7.78837 4.22857 7.97761 4.24452L10.8494 4.4866C11.2857 4.52338 11.4673 5.06336 11.142 5.35636L8.91785 7.35965C8.78333 7.48081 8.72481 7.66523 8.76486 7.84179L9.43787 10.8084C9.53688 11.2448 9.05662 11.5815 8.68007 11.3397L6.27019 9.79201C6.10558 9.68629 5.89442 9.68629 5.72981 9.79201L3.31993 11.3397C2.94338 11.5815 2.46312 11.2448 2.56213 10.8084L3.23514 7.84179C3.27519 7.66523 3.21667 7.48081 3.08215 7.35965L0.857969 5.35636C0.532663 5.06336 0.714337 4.52338 1.15059 4.4866L4.02239 4.24452C4.21163 4.22857 4.37549 4.1068 4.44534 3.93021L5.53505 1.17539Z"/>
							</svg>
						</div>
					</div>
				</div>
				<div class="market-detail__main-info_item">
					<div class="market-detail__main-info_item-title">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_INSTALLATIONS') }}</div>
					<div class="market-detail__main-info_item-details">{{ result.APP.NUM_INSTALLS }}</div>
				</div>
				<div class="market-detail__main-info_item">
					<div class="market-detail__main-info_item-title">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_DEVELOPER') }}</div>
					<a class="market-detail__main-info_item-details" 
					   v-if="result.APP.PARTNER_URL"
					   :href="result.APP.PARTNER_URL"
					   target="_blank"
					>
						{{ result.APP.PARTNER_NAME }}
					</a>
					<div class="market-detail__main-info_item-details"
						 v-else
					>
						{{ result.APP.PARTNER_NAME }}
					</div>
				</div>
				<div class="market-detail__main-info_item">
					<div class="market-detail__main-info_item-title">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_CATEGORIES') }}</div>
					<div class="market-detail__main-info_item-details" 
						 v-if="getCategoriesCount > 0"
					>
						{{ result.APP.CATEGORIES[0] }}
					</div>
				</div>
			</div>
			<div class="market-detail__slider">
				<Slider
					:info="result.APP.SLIDER_IMAGES"
					:options="{
						sliderId: 'detail-description',
						borderRadius: 12,
						autoSlide: true,
						arrows: result.APP.SLIDER_ARROWS,
						column: 2,
						controls: true,
						viewerGroupBy: 'market-desc-images',
					}"
				/>
			</div>
			<div class="market-detail__description"
				 :class="{'--hide': hideDescription}"
			>
				<div class="market-detail__description-wrapper" data-role="market-detail__wrapper"
					 @transitionend="setHeightAutoFunction"
				>
					<div class="market-detail__description-content" data-role="market-detail__content">
						<div class="market-detail__title">
							{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_DESCRIPTION') }}
						</div>

						<div v-html="result.APP.DESC"></div>

						<div class="market-detail__description_btn-block" v-if="getContactDeveloper.length > 0 || getRequestDemoInfo.length > 0">
							<a class="ui-btn ui-btn-primary ui-btn-round ui-btn-icon- market-detail__description_btn-contact"
							   v-if="getContactDeveloper.length > 0"
							   :href="getContactDeveloper"
							   target="_blank"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_CONTACT_DEVELOPERS') }}
							</a>
							<a class="ui-btn ui-btn-primary ui-btn-round ui-btn-icon- market-detail__description_btn-request"
							   v-if="getRequestDemoInfo.length > 0"
							   :href="getRequestDemoInfo"
							   target="_blank"
							>
								{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_REQUEST_A_DEMO') }}
							</a>
						</div>
					</div>
				</div>
				<div class="market-detail__description_text-more"
					 @click="moreDescriptionClick"
				>
					<div class="market-detail__description_text-more-btn"></div>
				</div>
			</div>

			<div class="market-detail__useful-links">
				<span class="market-detail__useful-links_item --link"
					  @click="openSliderWithContent(slider.install, widthInstallSlider)"
				>
					<div class="market-detail__useful-links_item-icon">
						<svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
								  d="M11.75 10.9091C11.75 9.71666 12.7376 8.75 13.9559 8.75H22.5087C23.1259 8.75 23.7148 9.00309 24.1326 9.44782L29.918 15.6073C30.2923 16.0058 30.5 16.5273 30.5 17.0686V30.3409C30.5 31.5333 29.5124 32.5 28.2941 32.5H13.9559C12.7376 32.5 11.75 31.5333 11.75 30.3409V10.9091ZM22.5087 10.9091H13.9559V30.3409H28.2941V17.0686L22.5087 10.9091Z"
								  fill="#559BE6"/>
							<rect x="15.5" y="15" width="6.25" height="1.875" rx="0.9375" fill="#559BE6"/>
							<rect x="15.5" y="18.75" width="11.25" height="1.875" rx="0.9375" fill="#559BE6"/>
							<rect x="15.5" y="21.25" width="8.75" height="1.875" rx="0.9375" fill="#559BE6"/>
							<rect x="15.5" y="26.25" width="5" height="1.875" rx="0.9375" fill="#559BE6"/>
						</svg>
					</div>
					<div class="market-detail__useful-links_item-text --link">
						{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_INSTALLATION_INSTRUCTIONS') }}
					</div>
				</span>
				<span class="market-detail__useful-links_item --link"
					  @click="openSliderWithContent(slider.support)"
				>
					<div class="market-detail__useful-links_item-icon">
						<svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
								  d="M25.2695 21.0716C24.8758 22.9222 23.4205 24.3775 21.57 24.771C18.0418 25.5213 14.9769 22.4555 15.7274 18.9282C16.0823 17.2602 17.7584 15.5839 19.4264 15.229C22.9539 14.4782 26.02 17.5433 25.2695 21.0716ZM31.9917 18.194L29.5349 17.7841C29.3604 17.0705 29.1077 16.3877 28.7797 15.7494C28.7659 15.7225 28.7691 15.6902 28.7881 15.6667L30.3297 13.7552C30.6642 13.3431 30.649 12.7501 30.2963 12.3523L29.3192 11.2526C28.965 10.8552 28.3777 10.7712 27.9297 11.0543L25.8286 12.3751C24.9178 11.7387 23.8878 11.2663 22.7771 10.988C22.7477 10.9806 22.7253 10.9566 22.7203 10.9266L22.3175 8.50588C22.2312 7.98287 21.7786 7.59912 21.2474 7.59912H19.7749C19.2447 7.59912 18.7905 7.98287 18.7062 8.50588L18.3012 10.9275C18.2961 10.9574 18.2738 10.9814 18.2444 10.9887C17.3456 11.214 16.5014 11.5695 15.728 12.0343C15.7019 12.05 15.6692 12.0488 15.6444 12.0312L13.687 10.6342C13.2564 10.3265 12.6649 10.3747 12.2891 10.7497L11.2488 11.7907C10.8738 12.1665 10.8257 12.758 11.1342 13.1887L12.5344 15.15C12.5521 15.1747 12.5533 15.2074 12.5377 15.2335C12.0774 16.0009 11.7255 16.8401 11.5005 17.7308C11.4931 17.7602 11.4691 17.7824 11.4392 17.7874L9.00436 18.194C8.48216 18.2803 8.09766 18.7329 8.09766 19.2642V20.7356C8.09766 21.2669 8.48216 21.7196 9.00436 21.8058L11.4392 22.2123C11.4691 22.2174 11.4931 22.2396 11.5005 22.2691C11.6835 22.9962 11.9443 23.6918 12.2871 24.3393C12.3013 24.3663 12.2983 24.3991 12.2791 24.4229L10.7451 26.3228C10.4118 26.7346 10.4258 27.328 10.7784 27.7254L11.7548 28.8251C12.109 29.2232 12.6967 29.3057 13.1447 29.0234L15.2339 27.711C15.2595 27.6949 15.2922 27.6955 15.3172 27.7125C16.2089 28.3151 17.2148 28.7588 18.2929 29.0234L18.7062 31.4939C18.7906 32.0169 19.2447 32.4007 19.7749 32.4007H21.2474C21.7787 32.4007 22.2312 32.0169 22.3175 31.4939L22.721 29.0727C22.726 29.0428 22.7483 29.0188 22.7778 29.0114C23.665 28.7892 24.4983 28.44 25.2643 27.9834C25.2904 27.9678 25.323 27.9691 25.3477 27.9867L27.3885 29.4443C27.8184 29.7531 28.4103 29.7046 28.7857 29.3284L29.8264 28.2877C30.201 27.9131 30.2517 27.3219 29.9406 26.8904L28.4878 24.8535C28.4702 24.8287 28.469 24.796 28.4846 24.77C28.9463 24 29.3 23.1592 29.5238 22.2645C29.5311 22.2351 29.5551 22.2127 29.585 22.2077L31.9925 21.8057C32.5163 21.7194 32.8992 21.2668 32.8992 20.7355V19.2641C32.8984 18.7329 32.5155 18.2803 31.9917 18.194Z"
								  fill="#559BE6"/>
						</svg>
					</div>
					<div class="market-detail__useful-links_item-text --link">
						{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_TECHNICAL_SUPPORT') }}
					</div>
				</span>
				<span class="market-detail__useful-links_item --link"
					  @click="openSliderWithContent(slider.scope, 558)"
				>
					<div class="market-detail__useful-links_item-icon">
						<svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
							<g clip-path="url(#clip0_5874_292986)">
							<path fill-rule="evenodd" clip-rule="evenodd" d="M25.989 18.391C24.678 19.702 22.5525 19.702 21.2415 18.391C19.9305 17.08 19.9305 14.9545 21.2415 13.6435C22.5525 12.3325 24.678 12.3325 25.989 13.6435C27.3 14.9545 27.3 17.08 25.989 18.391ZM20.2237 22.0357C22.8566 23.5213 26.2567 23.143 28.4989 20.9009C31.196 18.2037 31.196 13.8308 28.4989 11.1336C25.8017 8.43647 21.4288 8.43647 18.7316 11.1336C16.4909 13.3743 16.1117 16.7716 17.594 19.4037L9.45929 27.5384C9.24219 27.7555 9.24219 28.1075 9.45929 28.3246L11.3039 30.1692C11.521 30.3863 11.873 30.3863 12.0901 30.1692L13.3904 28.869L14.8419 30.3204C15.0422 30.5208 15.3672 30.5208 15.5676 30.3204L17.382 28.5061C17.5824 28.3057 17.5823 27.9807 17.382 27.7803L15.9305 26.3289L20.2237 22.0357Z" fill="#559BE6"/>
							</g>
							<defs>
							<clipPath id="clip0_5874_292986">
							<rect width="30" height="30" fill="white" transform="translate(5.5 5)"/>
							</clipPath>
							</defs>
						</svg>
					</div>
					<div class="market-detail__useful-links_item-text --link">
						{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_DATA_SECURITY') }}
					</div>
				</span>
				<div class="market-detail__useful-links_item ">
					<div class="market-detail__useful-links_item-icon">
						<svg width="41" height="40" viewBox="0 0 41 40" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
								  d="M22.3413 22.1305L24.1021 19.4965V22.1305H22.3413ZM24.1021 24.9818H25.5232V23.4297H26.4752V22.1305H25.5232V17.2403H24.2895L20.9665 22.1366V23.4297H24.1021V24.9818ZM17.1287 21.6573C16.3061 22.425 15.7569 23.0406 15.4768 23.5062C15.1989 23.9703 15.0305 24.4632 14.9754 24.9818H20.1239V23.6092H17.2067C17.2831 23.4752 17.3839 23.3389 17.5074 23.1988C17.6309 23.058 17.9242 22.7741 18.3881 22.3471C18.8513 21.9185 19.1718 21.5915 19.349 21.3621C19.6174 21.0198 19.8129 20.692 19.9379 20.377C20.0599 20.0636 20.1239 19.7335 20.1239 19.386C20.1239 18.7757 19.9078 18.2662 19.4747 17.8558C19.0439 17.4462 18.4491 17.2403 17.6934 17.2403C17.0023 17.2403 16.4281 17.4167 15.9679 17.771C15.5084 18.1261 15.2349 18.7091 15.1482 19.5223L16.6112 19.6707C16.6391 19.2391 16.7428 18.9302 16.9258 18.7447C17.106 18.5585 17.35 18.4661 17.6566 18.4661C17.9669 18.4661 18.2087 18.5539 18.3844 18.7311C18.5616 18.9082 18.6498 19.1611 18.6498 19.4912C18.6498 19.788 18.5477 20.0916 18.3462 20.3952C18.1955 20.6163 17.7897 21.0373 17.1287 21.6573ZM11.1253 28.1057H29.8747V14.9568H11.1253V28.1057ZM30.1129 8.66747H28.3125V9.45336C28.3125 10.7564 27.2626 11.8118 25.9687 11.8118C24.6748 11.8118 23.6249 10.7564 23.6249 9.45336V8.66747H17.3751V9.45336C17.3751 10.7564 16.3252 11.8118 15.0313 11.8118C13.7374 11.8118 12.6875 10.7564 12.6875 9.45336V8.66747H10.8871C9.20056 8.66747 8 9.96063 8 11.8118V29.6775C8 30.5459 8.69916 31.25 9.56227 31.25H31.4377C32.3008 31.25 33 30.5459 33 29.6775V11.8118C33 10.0591 31.6039 8.66747 30.1129 8.66747ZM15.0313 10.2975C15.6658 10.2975 16.1804 9.78044 16.1804 9.14143V7.40612C16.1804 6.76711 15.6658 6.25 15.0313 6.25C14.3961 6.25 13.8822 6.76711 13.8822 7.40612V9.14143C13.8822 9.78044 14.3961 10.2975 15.0313 10.2975ZM25.9687 10.1855C26.5679 10.1855 27.0538 9.6964 27.0538 9.09297V7.45457C27.0538 6.85115 26.5679 6.36205 25.9687 6.36205C25.3695 6.36205 24.8835 6.85115 24.8835 7.45457V9.09297C24.8835 9.6964 25.3695 10.1855 25.9687 10.1855Z"
								  fill="#559BE6"/>
						</svg>
					</div>
					<div class="market-detail__useful-links_item-text">
						{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_PUBLISHED') }} {{ result.APP.DATE_PUBLIC }}
					</div>
				</div>
			</div>

			<Rating
				:appInfo="result.APP"
				:showNoAccessInstallButton="showNoAccessInstallButton"
				@install-app="installApp"
			/>

			<div class="market-detail__catalog-element" v-if="showYouMayLike">
				<div class="market-detail__catalog-elements_row">
					<div class="market-detail__title">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_YOU_MAY_LIKE') }}</div>
					<div class="market-detail__btn">
						{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_ALL') }}
						<svg class="market-detail__btn-icon" width="6" height="12" viewBox="0 0 6 12" fill="none"
							 xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
								  d="M0 3.99088L3.06862 6.79917L3.86345 7.49975L3.06862 8.20075L0 11.009L1.08283 12L6 7.5L1.08283 3L0 3.99088Z"
								  fill="#B9BFC3"/>
						</svg>
					</div>
				</div>
				<div class="market-detail__catalog-elements">
					<ListItem
						v-for="(appItem, index) in result.APPS"
						:item="appItem"
						:params="params"
						:index="index"
					/>
				</div>
			</div>

			<div style="display: none">
				<div id="market-slider-block-support">
					<div class="market-app__scope-list_wrapper">
						<div class="market-app__scope-list_header">
							<div class="market-app__scope-list_title">
								<div class="market-app__scope-list_title-icon">
									<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M25.1298 21.6521C24.7065 23.6418 23.1418 25.2066 21.1521 25.6297C17.3586 26.4365 14.0632 23.1401 14.8701 19.3475C15.2518 17.5541 17.0539 15.7518 18.8473 15.3701C22.6401 14.5629 25.9368 17.8585 25.1298 21.6521ZM32.3575 18.5581L29.716 18.1174C29.5283 17.3502 29.2566 16.616 28.904 15.9297C28.8892 15.9008 28.8926 15.8661 28.913 15.8408L30.5705 13.7856C30.9302 13.3424 30.9138 12.7048 30.5347 12.2772L29.4841 11.0947C29.1032 10.6675 28.4717 10.5771 27.99 10.8815L25.731 12.3016C24.7516 11.6174 23.6442 11.1095 22.45 10.8102C22.4183 10.8023 22.3943 10.7764 22.3889 10.7442L21.9558 8.14145C21.863 7.57911 21.3764 7.1665 20.8052 7.1665H19.222C18.6519 7.1665 18.1636 7.57911 18.0729 8.14145L17.6374 10.7452C17.632 10.7773 17.6081 10.8031 17.5764 10.811C16.61 11.0532 15.7023 11.4355 14.8708 11.9353C14.8427 11.9521 14.8075 11.9509 14.7809 11.9318L12.6763 10.4298C12.2133 10.099 11.5773 10.1508 11.1733 10.554L10.0548 11.6733C9.65156 12.0774 9.59983 12.7134 9.93151 13.1764L11.437 15.2852C11.456 15.3118 11.4573 15.347 11.4405 15.375C10.9456 16.2001 10.5673 17.1024 10.3254 18.0601C10.3174 18.0917 10.2916 18.1156 10.2595 18.121L7.64151 18.5582C7.08005 18.6509 6.66663 19.1376 6.66663 19.7088V21.2909C6.66663 21.8621 7.08005 22.3488 7.64151 22.4415L10.2594 22.8787C10.2916 22.8841 10.3174 22.908 10.3253 22.9397C10.5222 23.7215 10.8025 24.4694 11.1711 25.1656C11.1864 25.1946 11.1832 25.2299 11.1625 25.2554L9.51318 27.2982C9.15476 27.741 9.1698 28.379 9.549 28.8063L10.5988 29.9887C10.9796 30.4167 11.6115 30.5055 12.0932 30.2019L14.3395 28.7908C14.3671 28.7735 14.4022 28.7742 14.4291 28.7924C15.3878 29.4403 16.4694 29.9174 17.6286 30.2019L18.073 32.8581C18.1637 33.4205 18.6519 33.8332 19.222 33.8332H20.8052C21.3764 33.8332 21.863 33.4206 21.9558 32.8582L22.3896 30.255C22.395 30.2227 22.419 30.1969 22.4507 30.189C23.4046 29.9501 24.3006 29.5746 25.1243 29.0837C25.1523 29.067 25.1874 29.0683 25.2139 29.0872L27.4082 30.6544C27.8704 30.9865 28.5068 30.9344 28.9104 30.5299L30.0294 29.4109C30.4321 29.0081 30.4867 28.3724 30.1522 27.9086L28.5902 25.7184C28.5712 25.6918 28.5699 25.6567 28.5867 25.6287C29.0831 24.8008 29.4634 23.8967 29.704 22.9347C29.7119 22.9031 29.7377 22.8791 29.7699 22.8737L32.3584 22.4414C32.9215 22.3487 33.3333 21.862 33.3333 21.2908V19.7087C33.3324 19.1376 32.9207 18.6509 32.3575 18.5581Z" fill="#559BE6"/>
									</svg>
								</div>
								<div class="market-app__scope-list_title-text">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_SUPPORT') }}</div>
							</div>
						</div>
						<div class="market-app__scope-list_content">
							<div class="market-app__scope-list_block-btn" v-if="false">
								<button class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-icon- market-app__scope-list_content-btn">
									{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_CONTACT_DEVELOPERS') }}
								</button>
							</div>
							<div v-html="result.APP.SUPPORT"
							></div>
						</div>
					</div>
				</div>
				<div id="market-slider-block-install">
					<div class="market-slider-iframe-content"
						 v-if="installDescriptionIsLanding"
					>
						<iframe id="market-slider-iframe"
								style="width: 100%; height: 99vh;"
								frameborder="no"
								:src="result.APP.LINK_INSTALL"
						></iframe>
					</div>
					<template v-else>
						<div class="market-app__scope-list_wrapper">
							<div class="market-app__scope-list_header">
								<div class="market-app__scope-list_title">
									<div class="market-app__scope-list_title-icon">
										<svg width="40" height="41" viewBox="0 0 40 41" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd" d="M10.6933 20.5C10.0239 20.5 9.41951 20.9004 9.15852 21.5168L6.66669 27.402H33.3334L30.8415 21.5168C30.5805 20.9004 29.9761 20.5 29.3068 20.5H28.5087L30.1708 24.86C30.3788 25.4055 29.9759 25.9902 29.3921 25.9902H10.4735C9.88101 25.9902 9.47778 25.3894 9.70225 24.8411L11.4796 20.5H10.6933ZM33.3334 27.402H6.66669V32.1668C6.66669 33.0872 7.41288 33.8334 8.33335 33.8334H31.6667C32.5872 33.8334 33.3334 33.0872 33.3334 32.1668V27.402ZM28.3334 31.9512C29.2538 31.9512 30 31.2188 30 30.3153C30 29.4119 29.2538 28.6795 28.3334 28.6795C27.4129 28.6795 26.6667 29.4119 26.6667 30.3153C26.6667 31.2188 27.4129 31.9512 28.3334 31.9512Z" fill="#559BE6"/>
											<path d="M20.5 8.8335C20.5 8.55735 20.2761 8.3335 20 8.3335C19.7239 8.3335 19.5 8.55735 19.5 8.8335L20.5 8.8335ZM19.6464 22.5204C19.8417 22.7156 20.1583 22.7156 20.3536 22.5204L23.5355 19.3384C23.7308 19.1431 23.7308 18.8266 23.5355 18.6313C23.3403 18.436 23.0237 18.436 22.8284 18.6313L20 21.4597L17.1716 18.6313C16.9763 18.436 16.6597 18.436 16.4645 18.6313C16.2692 18.8266 16.2692 19.1431 16.4645 19.3384L19.6464 22.5204ZM19.5 8.8335L19.5 22.1668L20.5 22.1668L20.5 8.8335L19.5 8.8335Z" fill="#559BE6"/>
										</svg>
									</div>
									<div class="market-app__scope-list_title-text">{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_INSTALLATION') }}</div>
								</div>
							</div>
							<div class="market-app__scope-list_content">
								<div class="market-app__scope-list_block-btn" v-if="false">
									<button class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round ui-btn-icon- market-app__scope-list_content-btn">
										{{ $Bitrix.Loc.getMessage('MARKET_DETAIL_ITEM_JS_CONTACT_DEVELOPERS') }}
									</button>
								</div>
								<div v-html="result.APP.INSTALL"
								></div>
							</div>
						</div>
					</template>
				</div>
				<div id="market-slider-block-scope">
					<ScopeList
						:appInfo="result.APP"
					/>
				</div>
			</div>

			<div style="display: none">
				<PopupInstall
					:appInfo="result.APP"
					:licenseInfo="result.LICENSE"
				/>
				<PopupUninstall
					:appCode="result.APP.CODE"
					:appName="result.APP.NAME"
				/>
			</div>
		</div>
	`,
}