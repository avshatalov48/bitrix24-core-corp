import {PopupInstall} from "market.popup-install";
import {PopupUninstall} from "market.popup-uninstall";
import {mapActions} from "ui.vue3.pinia";
import {marketInstallState} from "market.install-store";
import {marketUninstallState} from "market.uninstall-store";
import {ratingStore} from "market.rating-store";
import {BIcon, Set} from 'ui.icon-set.api.vue';
import {MenuManager} from "main.popup";

import "./list-item.css";

export const ListItem = {
	components: {
		PopupInstall, PopupUninstall, BIcon,
	},
	props: [
		'item', 'params', 'index',
	],
	data() {
		return {
			favoriteProcess: false,
			favoriteProcessStart: false,
			contextMenu: false,
		}
	},
	computed: {
		fromParam: function () {
			let value = 'list';

			if (this.$parent.isCollection) {
				value = 'collection';
			} else if (this.$parent.isCategory) {
				value = 'category';
			} else if (this.$parent.isFavorites) {
				value = 'favorites';
			} else if (this.$parent.isInstalledList) {
				value = 'installed';
			}

			return value;
		},
		isFavoriteApp: function () {
			return this.$parent.isFavorites || this.item.IS_FAVORITE === 'Y';
		},
		favoriteButtonTitle: function () {
			return this.item.IS_FAVORITE === 'Y' ? this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_RM_FAVORITE') : this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_ADD_FAVORITE');
		},
		showContextMenu: function () {
			return this.item.SHOW_CONTEXT_MENU && this.item.SHOW_CONTEXT_MENU === 'Y';
		},
		isPublishedApp: function () {
			if (this.$parent.isInstalledList || this.$parent.isFavorites) {
				return this.item.UNPUBLISHED !== 'Y';
			}

			return true;
		},
		isSiteTemplate: function () {
			return this.item.IS_SITE_TEMPLATE === 'Y';
		},
		getBackgroundPath: function () {
			if (this.isSiteTemplate) {
				return this.item.SITE_PREVIEW;
			}

			return "/bitrix/js/market/images/backgrounds/" + this.getIndex + ".png";
		},
		getIndex: function () {
			return (parseInt(this.index, 10) % 30) + 1;
		},
		getAppCode: function () {
			return this.item.CODE;
		},
		iconSet: function () {
			return Set;
		},
	},
	mounted: function() {
		BX.addCustomEvent("SidePanel.Slider:onMessage", this.onMessageSlider);
		this.$Bitrix.eventEmitter.subscribe('market:rmFavorite', this.rmFavorite);

		if (!this.isPublishedApp) {
			BX.UI.Hint.init(this.$refs.listItemNoPublishedApp);
		}
	},
	methods: {
		labelTitle: function (dateFormat) {
			return dateFormat ? this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_PREMIUM_RATING') : '';
		},
		showMenu: function (event) {
			if (!this.showContextMenu) {
				return;
			}

			let menu = [];

			if (this.item.BUTTONS.RIGHTS === 'Y') {
				menu.push({
					text: this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_ACCESS'),
					onclick: this.setRights,
				});
			}

			if (this.item.BUTTONS.DELETE === 'Y') {
				menu.push({
					text: this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_DELETE'),
					onclick: () => {
						if (this.contextMenu) {
							this.contextMenu.close();
						}

						this.deleteApp(event, this.item.CODE, this.$root.currentUri);
					},
				});
			}

			if (menu.length > 0) {
				this.contextMenu = MenuManager.create(
					'list-item-menu-' + this.getAppCode,
					this.$refs.listItemContextMenu,
					menu,
					{
						closeByEsc : true,
						autoHide : true,
						angle: true,
						offsetLeft: 20,
					}
				);
			}

			this.contextMenu.show();
		},
		onMessageSlider: function (event) {
			if (event.eventId === 'total-fav-number') {
				if (this.getAppCode === event.data.appCode) {
					this.setFavorite(event.data.currentValue);
				}
			}
		},
		rmFavorite: function (event) {
			if (!this.$parent.isFavorites) {
				return;
			}

			if (event.data.favoriteIndex === this.index) {
				this.favoriteProcess = false;
			}
		},
		favoriteDebounce: function () {
			let timeout = null;

			const callback = () => this.favoriteProcess = this.favoriteProcessStart;

			return function() {
				clearTimeout(timeout);
				timeout = setTimeout(callback, 80);
			}()
		},
		rmFavoriteProcess: function () {
			this.favoriteProcessStart = false;
			this.favoriteProcess = false;
		},
		changeFavorite: function () {
			this.favoriteProcessStart = true;
			this.favoriteDebounce();
			const action = this.item.IS_FAVORITE === 'Y' ? 'rmFavorite' : 'addFavorite';
			BX.ajax.runAction('market.Favorites.' + action, {
				data: {
					appCode: this.getAppCode,
				},
				analyticsLabel: {
					viewMode: 'list',
				},
			}).then(
				response => {
					if (
						response.data &&
						typeof response.data.total !== 'undefined' &&
						BX.type.isString(response.data.currentValue)
					) {
						if (this.$parent.isFavorites) {
							this.$parent.options.page = 1;
							this.$parent.loadItems(false, this.index);
						}

						if (!this.$parent.isFavorites) {
							this.rmFavoriteProcess();
						}

						this.$root.favNumbers = response.data.total;
						this.setFavorite(response.data.currentValue);
					}
				},
				response => {
					this.rmFavoriteProcess();
				},
			);
		},
		setFavorite: function (value) {
			this.item.IS_FAVORITE = value;
		},
		setRights: function() {
			if (this.contextMenu) {
				this.contextMenu.close();
			}

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
						appCode: this.getAppCode,
					},
					analyticsLabel: {
						viewMode: 'list',
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
									appCode: this.getAppCode,
									rights: rights,
								},
								analyticsLabel: {
									viewMode: 'list',
								},
							}
						).then((response) => {});
					}
				});
			});
		},
		updateApp: function () {
			this.setAppInfo(this.item);
			this.showInstallPopup(true);
		},
		...mapActions(marketInstallState, ['showInstallPopup', 'setAppInfo',]),
		...mapActions(marketUninstallState, ['deleteApp',]),
		...mapActions(ratingStore, ['isActiveStar', 'getAppRating',]),
	},
	template: `
	<div class="market-catalog__elements-item"
		 :class="{
			'--disabled': favoriteProcess, 
			'--unpublished': !isPublishedApp,
			'--installed': $parent.isInstalledList,
			}"
		 :data-app-code="getAppCode"
	>
		<template v-if="!isPublishedApp">
			<a class="market-catalog__elements-item_img-link" href="#">
				<div class="ui-hint market-catalog__elements-item--hint"
					 ref="listItemNoPublishedApp"
				>
					<span class="ui-hint-icon" 
						  :data-hint="$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_HERE_UNAVAILABLE')"
						  data-hint-no-icon=""
					></span>
				</div>
				<img class="market-catalog__elements-item_img" src="/bitrix/js/market/images/unpublised-app.svg" alt="">
			</a>
			<div class="market-catalog__elements-item_info">
				<div class="market-catalog__elements-item_info-head">
					<span class="market-catalog__elements-item_info-title"></span>
					<div class="market-catalog__elements-item_info-favorites"
						 @click="changeFavorite"
						 :title="favoriteButtonTitle"
					>
						<svg class="market-catalog__elements-item_info-favorites-svg"
							 :class="{'--favorite': isFavoriteApp}"
							 width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path class="market-catalog__favorites-fill" d="M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z" stroke-width="2"></path>
							<path class="market-catalog__favorites-stroke" fill-rule="evenodd" clip-rule="evenodd" d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z" transform="translate(1, 3)"/>
						</svg>
					</div>
				</div>
				<div class="market-rating__container"></div>
				<div class="market-catalog__elements-item_btn-block" v-if="$parent.isInstalledList">
					<button class="ui-btn ui-btn-xs ui-btn-light market-catalog__elements-item_btn-more"
							v-if="showContextMenu"
							@click="showMenu($event)"
							ref="listItemContextMenu"
					>
						<BIcon :name="iconSet.MORE"/>
					</button>
				</div>
			</div>
		</template>
		<template v-else>
			<a class="market-catalog__elements-item_img-link"
			   :style="{'background-image': 'url(\\'' + getBackgroundPath + '\\')'}"
			   :title="item.NAME"
			   :href="$root.getDetailUri(this.getAppCode, this.isSiteTemplate, this.fromParam)"
			   @click="$root.openSiteTemplate($event, this.isSiteTemplate)"
			>
				<img class="market-catalog__elements-item_img" 
					 :src="item.ICON" 
					 v-if="!isSiteTemplate" 
					 alt=""
				>

				<span class="market-catalog__elements-item_labels" v-if="item.LABELS && !$parent.isInstalledList">
					<span class="market-catalog__elements-item_label"
						  :class="{'--recommended': label.CODE === 'recommended'}"
						  v-for="label in item.LABELS"
						  :style="{background: label.COLOR_2}"
						  :title="labelTitle(label.PREMIUM_UNTIL_FORMAT)"
					>
						{{ label.TEXT }}
					</span>
				</span>
				<span class="market-catalog__elements-item_labels-status" v-if="item.PRICE_POLICY_NAME">
					<span class="market-catalog__elements-item_label-status"
						  :class="{'--blue': item.PRICE_POLICY_BLUE}"
					>
						{{ item.PRICE_POLICY_NAME }}
					</span>
				</span>
			</a>
			<div class="market-catalog__elements-item_info">
				<div class="market-catalog__elements-item_info-head">
					<a class="market-catalog__elements-item_info-title"
					   :title="item.NAME"
					   :href="$root.getDetailUri(this.getAppCode, this.isSiteTemplate, this.fromParam)"
					   @click="$root.openSiteTemplate($event, this.isSiteTemplate)"
					>
						{{ item.NAME }}
					</a>
					<div class="market-catalog__elements-item_info-favorites"
						 @click="changeFavorite"
						 :title="favoriteButtonTitle"
					>
						<svg class="market-catalog__elements-item_info-favorites-svg"
							 :class="{'--favorite': isFavoriteApp}"
							 width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path class="market-catalog__favorites-fill" d="M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z" stroke-width="2"></path>
							<path class="market-catalog__favorites-stroke" fill-rule="evenodd" clip-rule="evenodd" d="M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z" transform="translate(1, 3)"/>
						</svg>
					</div>
				</div>
				
				<div class="market-catalog__elements-item_info-description"
					 v-if="!$parent.isInstalledList"
					 :title="item.SHORT_DESC"
					 v-html="item.SHORT_DESC"
				></div>
				
				<div class="market-rating__container">
					<div class="market-rating__stars" v-if="!this.isSiteTemplate">
						<svg class="market-rating__star"
							 :class="{'--active': isActiveStar(1, getAppRating(item.RATING))}"
							 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
						</svg>
						<svg class="market-rating__star"
							 :class="{'--active': isActiveStar(2, getAppRating(item.RATING))}"
							 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
						</svg>
						<svg class="market-rating__star"
							 :class="{'--active': isActiveStar(3, getAppRating(item.RATING))}"
							 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
						</svg>
						<svg class="market-rating__star"
							 :class="{'--active': isActiveStar(4, getAppRating(item.RATING))}"
							 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
						</svg>
						<svg class="market-rating__star"
							 :class="{'--active': isActiveStar(5, getAppRating(item.RATING))}"
							 width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z"/>
						</svg>
						<span class="market-rating__stars-amount"
							  v-if="item.REVIEWS_NUMBER"
						>({{ item.REVIEWS_NUMBER }})</span>
					</div>
					<div class="market-rating__download">
						<span class="market-rating__download-icon"></span>
						<div class="market-rating__download-amount">{{ item.NUM_INSTALLS }}</div>
					</div>
				</div>

				<template v-if="$parent.isInstalledList">
					<a class="market-catalog__elements-item_info-partner"
					   v-if="item.PARTNER_URL"
					   :href="item.PARTNER_URL"
					   :title="item.PARTNER_NAME"
					   target="_blank"
					>
						{{ item.PARTNER_NAME }}
					</a>
					<span class="market-catalog__elements-item_info-partner"
						  :title="item.PARTNER_NAME"
						  v-else
					>
						{{ item.PARTNER_NAME }}
					</span>
				</template>

				<div class="market-catalog__elements-item_btn-block" v-if="$parent.isInstalledList">
					<button class="ui-btn ui-btn-xs ui-btn-success"
							v-if="item.BUTTONS.UPDATE === 'Y'"
							@click="updateApp"
					>
						{{ $Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_REFRESH') }}
					</button>

					<button class="ui-btn ui-btn-xs ui-btn-light market-catalog__elements-item_btn-more"
							v-if="showContextMenu"
							@click="showMenu($event)"
							ref="listItemContextMenu"
					>
						<BIcon :name="iconSet.MORE"/>
					</button>
				</div>
			</div>
		</template>
		<div v-if="$parent.isInstalledList">
			<div style="display: none">
				<PopupInstall
					v-if="item.BUTTONS.UPDATE === 'Y'"
					:appInfo="item"
					:licenseInfo="item.LICENSE"
				/>
				<PopupUninstall
					v-if="item.BUTTONS.DELETE === 'Y'"
					:appCode="item.CODE"
					:appName="item.NAME"
				/>
			</div>
		</div>
	</div>
	`,
};