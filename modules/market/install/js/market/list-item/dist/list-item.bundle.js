this.BX = this.BX || {};
(function (exports,market_popupInstall,market_popupUninstall,ui_vue3_pinia,market_installStore,market_uninstallStore,market_ratingStore,ui_iconSet_api_vue,main_popup) {
	'use strict';

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var ListItem = {
	  components: {
	    PopupInstall: market_popupInstall.PopupInstall,
	    PopupUninstall: market_popupUninstall.PopupUninstall,
	    BIcon: ui_iconSet_api_vue.BIcon
	  },
	  props: ['item', 'params', 'index'],
	  data: function data() {
	    return {
	      favoriteProcess: false,
	      favoriteProcessStart: false,
	      contextMenu: false
	    };
	  },
	  computed: {
	    fromParam: function fromParam() {
	      var value = 'list';
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
	    isFavoriteApp: function isFavoriteApp() {
	      return this.$parent.isFavorites || this.item.IS_FAVORITE === 'Y';
	    },
	    favoriteButtonTitle: function favoriteButtonTitle() {
	      return this.item.IS_FAVORITE === 'Y' ? this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_RM_FAVORITE') : this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_ADD_FAVORITE');
	    },
	    showContextMenu: function showContextMenu() {
	      return this.item.SHOW_CONTEXT_MENU && this.item.SHOW_CONTEXT_MENU === 'Y';
	    },
	    isPublishedApp: function isPublishedApp() {
	      if (this.$parent.isInstalledList || this.$parent.isFavorites) {
	        return this.item.UNPUBLISHED !== 'Y';
	      }
	      return true;
	    },
	    isSiteTemplate: function isSiteTemplate() {
	      return this.item.IS_SITE_TEMPLATE === 'Y';
	    },
	    getBackgroundPath: function getBackgroundPath() {
	      if (this.isSiteTemplate) {
	        return this.item.SITE_PREVIEW;
	      }
	      return "/bitrix/js/market/images/backgrounds/" + this.getIndex + ".png";
	    },
	    getIndex: function getIndex() {
	      return parseInt(this.index, 10) % 30 + 1;
	    },
	    getAppCode: function getAppCode() {
	      return this.item.CODE;
	    },
	    iconSet: function iconSet() {
	      return ui_iconSet_api_vue.Set;
	    }
	  },
	  mounted: function mounted() {
	    BX.addCustomEvent("SidePanel.Slider:onMessage", this.onMessageSlider);
	    this.$Bitrix.eventEmitter.subscribe('market:rmFavorite', this.rmFavorite);
	    if (!this.isPublishedApp) {
	      BX.UI.Hint.init(this.$refs.listItemNoPublishedApp);
	    }
	  },
	  methods: _objectSpread(_objectSpread(_objectSpread({
	    labelTitle: function labelTitle(dateFormat) {
	      return dateFormat ? this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_PREMIUM_RATING') : '';
	    },
	    showMenu: function showMenu(event) {
	      var _this = this;
	      if (!this.showContextMenu) {
	        return;
	      }
	      var menu = [];
	      if (this.item.BUTTONS.RIGHTS === 'Y') {
	        menu.push({
	          text: this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_ACCESS'),
	          onclick: this.setRights
	        });
	      }
	      if (this.item.BUTTONS.DELETE === 'Y') {
	        menu.push({
	          text: this.$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_DELETE'),
	          onclick: function onclick() {
	            if (_this.contextMenu) {
	              _this.contextMenu.close();
	            }
	            _this.deleteApp(event, _this.item.CODE, _this.$root.currentUri);
	          }
	        });
	      }
	      if (menu.length > 0) {
	        this.contextMenu = main_popup.MenuManager.create('list-item-menu-' + this.getAppCode, this.$refs.listItemContextMenu, menu, {
	          closeByEsc: true,
	          autoHide: true,
	          angle: true,
	          offsetLeft: 20
	        });
	      }
	      this.contextMenu.show();
	    },
	    onMessageSlider: function onMessageSlider(event) {
	      if (event.eventId === 'total-fav-number') {
	        if (this.getAppCode === event.data.appCode) {
	          this.setFavorite(event.data.currentValue);
	        }
	      }
	    },
	    rmFavorite: function rmFavorite(event) {
	      if (!this.$parent.isFavorites) {
	        return;
	      }
	      if (event.data.favoriteIndex === this.index) {
	        this.favoriteProcess = false;
	      }
	    },
	    favoriteDebounce: function favoriteDebounce() {
	      var _this2 = this;
	      var timeout = null;
	      var callback = function callback() {
	        return _this2.favoriteProcess = _this2.favoriteProcessStart;
	      };
	      return function () {
	        clearTimeout(timeout);
	        timeout = setTimeout(callback, 80);
	      }();
	    },
	    rmFavoriteProcess: function rmFavoriteProcess() {
	      this.favoriteProcessStart = false;
	      this.favoriteProcess = false;
	    },
	    changeFavorite: function changeFavorite() {
	      var _this3 = this;
	      this.favoriteProcessStart = true;
	      this.favoriteDebounce();
	      var action = this.item.IS_FAVORITE === 'Y' ? 'rmFavorite' : 'addFavorite';
	      BX.ajax.runAction('market.Favorites.' + action, {
	        data: {
	          appCode: this.getAppCode
	        },
	        analyticsLabel: {
	          viewMode: 'list'
	        }
	      }).then(function (response) {
	        if (response.data && typeof response.data.total !== 'undefined' && BX.type.isString(response.data.currentValue)) {
	          if (_this3.$parent.isFavorites) {
	            _this3.$parent.options.page = 1;
	            _this3.$parent.loadItems(false, _this3.index);
	          }
	          if (!_this3.$parent.isFavorites) {
	            _this3.rmFavoriteProcess();
	          }
	          _this3.$root.favNumbers = response.data.total;
	          _this3.setFavorite(response.data.currentValue);
	        }
	      }, function (response) {
	        _this3.rmFavoriteProcess();
	      });
	    },
	    setFavorite: function setFavorite(value) {
	      this.item.IS_FAVORITE = value;
	    },
	    setRights: function setRights() {
	      var _this4 = this;
	      if (this.contextMenu) {
	        this.contextMenu.close();
	      }
	      BX.Access.Init({
	        other: {
	          disabled: false,
	          disabled_g2: true,
	          disabled_cr: true
	        },
	        groups: {
	          disabled: true
	        },
	        socnetgroups: {
	          disabled: true
	        }
	      });
	      BX.ajax.runAction('market.Application.getRights', {
	        data: {
	          appCode: this.getAppCode
	        },
	        analyticsLabel: {
	          viewMode: 'list'
	        }
	      }).then(function (response) {
	        BX.Access.SetSelected(response.data, "bind");
	        BX.Access.ShowForm({
	          bind: "bind",
	          showSelected: true,
	          callback: function callback(rights) {
	            BX.ajax.runAction('market.Application.setRights', {
	              data: {
	                appCode: _this4.getAppCode,
	                rights: rights
	              },
	              analyticsLabel: {
	                viewMode: 'list'
	              }
	            }).then(function (response) {});
	          }
	        });
	      });
	    },
	    updateApp: function updateApp() {
	      this.setAppInfo(this.item);
	      this.showInstallPopup(true);
	    }
	  }, ui_vue3_pinia.mapActions(market_installStore.marketInstallState, ['showInstallPopup', 'setAppInfo'])), ui_vue3_pinia.mapActions(market_uninstallStore.marketUninstallState, ['deleteApp'])), ui_vue3_pinia.mapActions(market_ratingStore.ratingStore, ['isActiveStar', 'getAppRating'])),
	  template: "\n\t<div class=\"market-catalog__elements-item\"\n\t\t :class=\"{\n\t\t\t'--disabled': favoriteProcess, \n\t\t\t'--unpublished': !isPublishedApp,\n\t\t\t'--installed': $parent.isInstalledList,\n\t\t\t}\"\n\t\t :data-app-code=\"getAppCode\"\n\t>\n\t\t<template v-if=\"!isPublishedApp\">\n\t\t\t<a class=\"market-catalog__elements-item_img-link\" href=\"#\">\n\t\t\t\t<div class=\"ui-hint market-catalog__elements-item--hint\"\n\t\t\t\t\t ref=\"listItemNoPublishedApp\"\n\t\t\t\t>\n\t\t\t\t\t<span class=\"ui-hint-icon\" \n\t\t\t\t\t\t  :data-hint=\"$Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_HERE_UNAVAILABLE')\"\n\t\t\t\t\t\t  data-hint-no-icon=\"\"\n\t\t\t\t\t></span>\n\t\t\t\t</div>\n\t\t\t\t<img class=\"market-catalog__elements-item_img\" src=\"/bitrix/js/market/images/unpublised-app.svg\" alt=\"\">\n\t\t\t</a>\n\t\t\t<div class=\"market-catalog__elements-item_info\">\n\t\t\t\t<div class=\"market-catalog__elements-item_info-head\">\n\t\t\t\t\t<span class=\"market-catalog__elements-item_info-title\"></span>\n\t\t\t\t\t<div class=\"market-catalog__elements-item_info-favorites\"\n\t\t\t\t\t\t @click=\"changeFavorite\"\n\t\t\t\t\t\t :title=\"favoriteButtonTitle\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<svg class=\"market-catalog__elements-item_info-favorites-svg\"\n\t\t\t\t\t\t\t :class=\"{'--favorite': isFavoriteApp}\"\n\t\t\t\t\t\t\t width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path class=\"market-catalog__favorites-fill\" d=\"M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z\" stroke-width=\"2\"></path>\n\t\t\t\t\t\t\t<path class=\"market-catalog__favorites-stroke\" fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z\" transform=\"translate(1, 3)\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"market-rating__container\"></div>\n\t\t\t\t<div class=\"market-catalog__elements-item_btn-block\" v-if=\"$parent.isInstalledList\">\n\t\t\t\t\t<button class=\"ui-btn ui-btn-xs ui-btn-light market-catalog__elements-item_btn-more\"\n\t\t\t\t\t\t\tv-if=\"showContextMenu\"\n\t\t\t\t\t\t\t@click=\"showMenu($event)\"\n\t\t\t\t\t\t\tref=\"listItemContextMenu\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<BIcon :name=\"iconSet.MORE\"/>\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</template>\n\t\t<template v-else>\n\t\t\t<a class=\"market-catalog__elements-item_img-link\"\n\t\t\t   :style=\"{'background-image': 'url(\\'' + getBackgroundPath + '\\')'}\"\n\t\t\t   :title=\"item.NAME\"\n\t\t\t   :href=\"$root.getDetailUri(this.getAppCode, this.isSiteTemplate, this.fromParam)\"\n\t\t\t   @click=\"$root.openSiteTemplate($event, this.isSiteTemplate)\"\n\t\t\t>\n\t\t\t\t<img class=\"market-catalog__elements-item_img\" \n\t\t\t\t\t :src=\"item.ICON\" \n\t\t\t\t\t v-if=\"!isSiteTemplate\" \n\t\t\t\t\t alt=\"\"\n\t\t\t\t>\n\n\t\t\t\t<span class=\"market-catalog__elements-item_labels\" v-if=\"item.LABELS && !$parent.isInstalledList\">\n\t\t\t\t\t<span class=\"market-catalog__elements-item_label\"\n\t\t\t\t\t\t  :class=\"{'--recommended': label.CODE === 'recommended'}\"\n\t\t\t\t\t\t  v-for=\"label in item.LABELS\"\n\t\t\t\t\t\t  :style=\"{background: label.COLOR_2}\"\n\t\t\t\t\t\t  :title=\"labelTitle(label.PREMIUM_UNTIL_FORMAT)\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ label.TEXT }}\n\t\t\t\t\t</span>\n\t\t\t\t</span>\n\t\t\t\t<span class=\"market-catalog__elements-item_labels-status\" v-if=\"!$parent.isInstalledList\">\n\t\t\t\t\t<span class=\"market-catalog__elements-item_label-status\"\n\t\t\t\t\t\t  :class=\"{'--blue': item.PRICE_POLICY_BLUE}\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ item.PRICE_POLICY_NAME }}\n\t\t\t\t\t</span>\n\t\t\t\t</span>\n\t\t\t</a>\n\t\t\t<div class=\"market-catalog__elements-item_info\">\n\t\t\t\t<div class=\"market-catalog__elements-item_info-head\">\n\t\t\t\t\t<a class=\"market-catalog__elements-item_info-title\"\n\t\t\t\t\t   :title=\"item.NAME\"\n\t\t\t\t\t   :href=\"$root.getDetailUri(this.getAppCode, this.isSiteTemplate, this.fromParam)\"\n\t\t\t\t\t   @click=\"$root.openSiteTemplate($event, this.isSiteTemplate)\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ item.NAME }}\n\t\t\t\t\t</a>\n\t\t\t\t\t<div class=\"market-catalog__elements-item_info-favorites\"\n\t\t\t\t\t\t @click=\"changeFavorite\"\n\t\t\t\t\t\t :title=\"favoriteButtonTitle\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<svg class=\"market-catalog__elements-item_info-favorites-svg\"\n\t\t\t\t\t\t\t :class=\"{'--favorite': isFavoriteApp}\"\n\t\t\t\t\t\t\t width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path class=\"market-catalog__favorites-fill\" d=\"M11.2227 6.92764L11.223 6.92802L11.2235 6.9286L11.2237 6.92878L12.0024 7.9031L12.7813 6.9286C12.781 6.92897 12.7812 6.92877 12.7818 6.92802L12.7821 6.92764L12.7908 6.91717C12.8004 6.90578 12.817 6.88631 12.8404 6.85991C12.8872 6.80704 12.9609 6.72686 13.0595 6.62841C13.2576 6.43063 13.5513 6.16416 13.9255 5.89808C14.6818 5.36036 15.7079 4.86474 16.9087 4.86475C19.32 4.86475 21.204 6.71908 21.204 9.16008C21.204 11.614 19.5141 13.8465 17.3533 15.6682C15.2599 17.4331 12.933 18.635 12.0024 19.081C11.0719 18.635 8.74495 17.4331 6.6515 15.6682C4.49074 13.8465 2.80078 11.614 2.80078 9.16008C2.80078 6.71908 4.68485 4.86475 7.09612 4.86475C8.29688 4.86474 9.32303 5.36036 10.0793 5.89808C10.4535 6.16416 10.7472 6.43063 10.9453 6.62841C11.044 6.72686 11.1176 6.80704 11.1645 6.85991C11.1879 6.88631 11.2045 6.90578 11.214 6.91717L11.2227 6.92764Z\" stroke-width=\"2\"></path>\n\t\t\t\t\t\t\t<path class=\"market-catalog__favorites-stroke\" fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M9.50762 1.97569C10.4604 2.61848 11.0063 3.30145 11.0063 3.30145C11.0063 3.30145 11.5522 2.61848 12.505 1.97569C13.3519 1.40434 14.5203 0.864744 15.9126 0.864746C18.8713 0.86475 21.2079 3.1619 21.2079 6.16008C21.2079 12.7611 11.0063 17.1827 11.0063 17.1827C11.0063 17.1827 0.804688 12.7611 0.804688 6.16008C0.804688 3.1619 3.14137 0.86475 6.10003 0.864746C7.49231 0.864744 8.66071 1.40434 9.50762 1.97569ZM11.0063 14.9661C11.1945 14.8708 11.4105 14.7585 11.6483 14.6298C12.545 14.1444 13.7284 13.439 14.9001 12.5521C17.3825 10.6731 19.2079 8.44129 19.2079 6.16008C19.2079 4.27625 17.7765 2.86475 15.9126 2.86475C14.9904 2.86474 14.1647 3.2468 13.5089 3.71306C13.1889 3.94063 12.9373 4.16899 12.7699 4.3361C12.6871 4.41879 12.6274 4.48397 12.5927 4.52308C12.5762 4.54173 12.5656 4.55422 12.5611 4.55959L11.0063 6.50475L9.45157 4.55959C9.44706 4.55422 9.43643 4.54173 9.4199 4.52308C9.38525 4.48397 9.32555 4.41879 9.24273 4.3361C9.07534 4.16899 8.82375 3.94063 8.5037 3.71306C7.84795 3.2468 7.02222 2.86474 6.10003 2.86475C4.23614 2.86475 2.80469 4.27625 2.80469 6.16008C2.80469 8.44129 4.63016 10.6731 7.11258 12.5521C8.28419 13.439 9.46762 14.1444 10.3643 14.6298C10.6021 14.7585 10.8181 14.8708 11.0063 14.9661Z\" transform=\"translate(1, 3)\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div class=\"market-catalog__elements-item_info-description\"\n\t\t\t\t\t v-if=\"!$parent.isInstalledList\"\n\t\t\t\t\t :title=\"item.SHORT_DESC\"\n\t\t\t\t>\n\t\t\t\t\t{{ item.SHORT_DESC }}\n\t\t\t\t</div>\n\t\t\t\t\n\t\t\t\t<div class=\"market-rating__container\">\n\t\t\t\t\t<div class=\"market-rating__stars\" v-if=\"!this.isSiteTemplate\">\n\t\t\t\t\t\t<svg class=\"market-rating__star\"\n\t\t\t\t\t\t\t :class=\"{'--active': isActiveStar(1, getAppRating(item.RATING))}\"\n\t\t\t\t\t\t\t width=\"16\" height=\"16\" viewBox=\"0 0 16 16\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path d=\"M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t<svg class=\"market-rating__star\"\n\t\t\t\t\t\t\t :class=\"{'--active': isActiveStar(2, getAppRating(item.RATING))}\"\n\t\t\t\t\t\t\t width=\"16\" height=\"16\" viewBox=\"0 0 16 16\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path d=\"M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t<svg class=\"market-rating__star\"\n\t\t\t\t\t\t\t :class=\"{'--active': isActiveStar(3, getAppRating(item.RATING))}\"\n\t\t\t\t\t\t\t width=\"16\" height=\"16\" viewBox=\"0 0 16 16\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path d=\"M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t<svg class=\"market-rating__star\"\n\t\t\t\t\t\t\t :class=\"{'--active': isActiveStar(4, getAppRating(item.RATING))}\"\n\t\t\t\t\t\t\t width=\"16\" height=\"16\" viewBox=\"0 0 16 16\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path d=\"M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t<svg class=\"market-rating__star\"\n\t\t\t\t\t\t\t :class=\"{'--active': isActiveStar(5, getAppRating(item.RATING))}\"\n\t\t\t\t\t\t\t width=\"16\" height=\"16\" viewBox=\"0 0 16 16\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"\n\t\t\t\t\t\t>\n\t\t\t\t\t\t\t<path d=\"M7.53505 3.17539C7.70176 2.75395 8.29824 2.75395 8.46495 3.17539L9.55466 5.93021C9.62451 6.1068 9.78837 6.22857 9.97761 6.24452L12.8494 6.4866C13.2857 6.52338 13.4673 7.06336 13.142 7.35636L10.9179 9.35965C10.7833 9.48081 10.7248 9.66523 10.7649 9.84179L11.4379 12.8084C11.5369 13.2448 11.0566 13.5815 10.6801 13.3397L8.27019 11.792C8.10558 11.6863 7.89442 11.6863 7.72981 11.792L5.31993 13.3397C4.94338 13.5815 4.46312 13.2448 4.56213 12.8084L5.23514 9.84179C5.27519 9.66523 5.21667 9.48081 5.08215 9.35965L2.85797 7.35636C2.53266 7.06336 2.71434 6.52338 3.15059 6.4866L6.02239 6.24452C6.21163 6.22857 6.37549 6.1068 6.44534 5.93021L7.53505 3.17539Z\"/>\n\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t<span class=\"market-rating__stars-amount\"\n\t\t\t\t\t\t\t  v-if=\"item.REVIEWS_NUMBER\"\n\t\t\t\t\t\t>({{ item.REVIEWS_NUMBER }})</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"market-rating__download\">\n\t\t\t\t\t\t<span class=\"market-rating__download-icon\"></span>\n\t\t\t\t\t\t<div class=\"market-rating__download-amount\">{{ item.NUM_INSTALLS }}</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<template v-if=\"$parent.isInstalledList\">\n\t\t\t\t\t<a class=\"market-catalog__elements-item_info-partner\"\n\t\t\t\t\t   v-if=\"item.PARTNER_URL\"\n\t\t\t\t\t   :href=\"item.PARTNER_URL\"\n\t\t\t\t\t   :title=\"item.PARTNER_NAME\"\n\t\t\t\t\t   target=\"_blank\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ item.PARTNER_NAME }}\n\t\t\t\t\t</a>\n\t\t\t\t\t<span class=\"market-catalog__elements-item_info-partner\"\n\t\t\t\t\t\t  :title=\"item.PARTNER_NAME\"\n\t\t\t\t\t\t  v-else\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ item.PARTNER_NAME }}\n\t\t\t\t\t</span>\n\t\t\t\t</template>\n\n\t\t\t\t<div class=\"market-catalog__elements-item_btn-block\" v-if=\"$parent.isInstalledList\">\n\t\t\t\t\t<button class=\"ui-btn ui-btn-xs ui-btn-success\"\n\t\t\t\t\t\t\tv-if=\"item.BUTTONS.UPDATE === 'Y'\"\n\t\t\t\t\t\t\t@click=\"updateApp\"\n\t\t\t\t\t>\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('MARKET_LIST_ITEM_JS_BTN_REFRESH') }}\n\t\t\t\t\t</button>\n\n\t\t\t\t\t<button class=\"ui-btn ui-btn-xs ui-btn-light market-catalog__elements-item_btn-more\"\n\t\t\t\t\t\t\tv-if=\"showContextMenu\"\n\t\t\t\t\t\t\t@click=\"showMenu($event)\"\n\t\t\t\t\t\t\tref=\"listItemContextMenu\"\n\t\t\t\t\t>\n\t\t\t\t\t\t<BIcon :name=\"iconSet.MORE\"/>\n\t\t\t\t\t</button>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</template>\n\t\t<div v-if=\"$parent.isInstalledList\">\n\t\t\t<div style=\"display: none\">\n\t\t\t\t<PopupInstall\n\t\t\t\t\tv-if=\"item.BUTTONS.UPDATE === 'Y'\"\n\t\t\t\t\t:appInfo=\"item\"\n\t\t\t\t\t:licenseInfo=\"item.LICENSE\"\n\t\t\t\t/>\n\t\t\t\t<PopupUninstall\n\t\t\t\t\tv-if=\"item.BUTTONS.DELETE === 'Y'\"\n\t\t\t\t\t:appCode=\"item.CODE\"\n\t\t\t\t\t:appName=\"item.NAME\"\n\t\t\t\t/>\n\t\t\t</div>\n\t\t</div>\n\t</div>\n\t"
	};

	exports.ListItem = ListItem;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Market,BX.Vue3.Pinia,BX.Market,BX.Market,BX.Market,BX.UI.IconSet,BX.Main));
