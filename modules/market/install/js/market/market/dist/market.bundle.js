/* eslint-disable */
this.BX = this.BX || {};
(function (exports,ui_vue3_pinia,ui_vue3,market_toolbar,market_main,market_listApps,main_core_events) {
	'use strict';

	class Market {
	  constructor(params = {}) {
	    this.params = params.params;
	    this.result = params.result;
	    ui_vue3.BitrixVue.createApp({
	      name: 'Market',
	      components: {
	        Toolbar: market_toolbar.Toolbar,
	        Main: market_main.Main,
	        ListApps: market_listApps.ListApps
	      },
	      data: () => {
	        return {
	          params: this.params,
	          result: this.result,
	          categories: [],
	          searchFilters: [],
	          favNumbers: 0,
	          numUpdates: 0,
	          totalApps: 0,
	          showMarketIcon: 'Y',
	          skeleton: '',
	          marketSlider: '',
	          marketAction: '',
	          searchAction: '',
	          mainUri: '',
	          currentUri: '',
	          hideToolbar: false,
	          hideCategories: false,
	          hideBreadcrumbs: false,
	          showTitle: true,
	          canChangeHistory: true,
	          firstPageHistory: false
	        };
	      },
	      computed: {
	        isMainPage: function () {
	          return this.params.COMPONENT_NAME === 'bitrix:market.main';
	        },
	        isListPage: function () {
	          return this.params.COMPONENT_NAME === 'bitrix:market.list';
	        },
	        getFavNumbers: function () {
	          return this.favNumbers > 99 ? '99+' : this.favNumbers;
	        },
	        getNumUpdates: function () {
	          return this.numUpdates > 99 ? '99+' : this.numUpdates;
	        },
	        showSkeleton: function () {
	          return this.skeleton.length > 0;
	        },
	        getSkeletonPath: function () {
	          return "/bitrix/images/market/slider/" + this.skeleton + ".svg";
	        }
	      },
	      created() {
	        this.marketLogoTitle = this.result.MARKET_LOGO_TITLE;
	        this.marketToolbarTitle = this.result.MARKET_TOOLBAR_TITLE;
	        this.marketNameMessageCode = this.result.MARKET_NAME_MESSAGE_CODE;
	        this.categories = this.result.CATEGORIES;
	        this.searchFilters = this.result.SEARCH_FILTERS;
	        this.favNumbers = this.result.FAV_NUMBERS;
	        this.numUpdates = this.result.NUM_UPDATES;
	        this.totalApps = this.result.TOTAL_APPS;
	        this.showMarketIcon = this.result.SHOW_MARKET_ICON;
	        this.marketSlider = this.result.MARKET_SLIDER;
	        this.marketAction = this.result.ADDITIONAL_MARKET_ACTION;
	        this.searchAction = this.result.ADDITIONAL_SEARCH_ACTION;
	        if (this.params.CURRENT_PAGE && this.params.CURRENT_PAGE.length > 0) {
	          this.currentUri = this.params.CURRENT_PAGE;
	        }
	        if (this.params.HIDE_CATEGORIES && this.params.HIDE_CATEGORIES === 'Y') {
	          this.hideCategories = true;
	        }
	        if (this.params.HIDE_TOOLBAR && this.params.HIDE_TOOLBAR === 'Y') {
	          this.hideToolbar = true;
	        }
	        if (this.params.HIDE_BREADCRUMBS && this.params.HIDE_BREADCRUMBS === 'Y') {
	          this.hideBreadcrumbs = true;
	        }
	        if (this.params.SHOW_TITLE && this.params.SHOW_TITLE === 'N') {
	          this.showTitle = false;
	        }
	        if (this.params.CHANGE_HISTORY && this.params.CHANGE_HISTORY === 'N') {
	          this.canChangeHistory = false;
	        }
	        if (this.params.ADDITIONAL_BODY_CLASS && this.params.ADDITIONAL_BODY_CLASS.length > 0) {
	          document.body.classList.add(this.params.ADDITIONAL_BODY_CLASS);
	        }
	      },
	      mounted() {
	        this.$Bitrix.eventEmitter.subscribe('market:loadContent', this.loadContent);
	        main_core_events.EventEmitter.subscribe('market:refreshUri', this.refreshUri);
	        BX.addCustomEvent("SidePanel.Slider:onMessage", this.onMessageSlider);
	      },
	      methods: {
	        emitLoadContent: function (event) {
	          event.preventDefault();
	          this.$Bitrix.eventEmitter.emit('market:loadContent', {
	            info: event
	          });
	        },
	        loadContent: function (event) {
	          const link = event.data.info.target.closest('[data-load-content]');
	          if (!link) {
	            return;
	          }
	          let href = link.href;
	          if (!href) {
	            href = link.dataset.href;
	          }
	          if (link.dataset.loadContent.length <= 0 || !href) {
	            return;
	          }
	          if (this.result.MAIN_URI && this.result.MAIN_URI.length > 0) {
	            this.mainUri = this.result.MAIN_URI;
	          }
	          this.updatePage(href, link.dataset.loadContent);
	        },
	        refreshUri: function (event) {
	          if (!event.data.refreshUri || !event.data.skeleton) {
	            return;
	          }
	          this.updatePage(event.data.refreshUri, event.data.skeleton);
	        },
	        updatePage: function (uri, skeleton) {
	          this.skeleton = skeleton;
	          this.$Bitrix.eventEmitter.emit('market:closeToolbarPopup');
	          BX.ajax.runAction('market.Content.load', {
	            data: {
	              page: uri
	            },
	            analyticsLabel: {
	              page: uri
	            }
	          }).then(response => {
	            if (response.data) {
	              if (BX.type.isObject(response.data.params) && BX.type.isObject(response.data.result)) {
	                this.params = response.data.params;
	                this.result = response.data.result;
	                if (this.canChangeHistory) {
	                  BX.SidePanel.Instance.getTopSlider().setUrl(uri);
	                  top.history.replaceState({}, '', uri);
	                }
	                if (this.showTitle && response.data.result.hasOwnProperty('TITLE')) {
	                  top.document.title = response.data.result.TITLE;
	                }
	                this.$Bitrix.eventEmitter.emit('market:loadContentFinish');
	                if (document.querySelector('.market-toolbar')) {
	                  window.scrollTo({
	                    top: document.querySelector('.market-toolbar').getBoundingClientRect().top,
	                    behavior: 'smooth'
	                  });
	                }
	                if (this.result.ADDITIONAL_HIT_ACTION) {
	                  try {
	                    eval(this.result.ADDITIONAL_HIT_ACTION.replace("#HIT#", uri).replace("#HIT_PARAMS#", JSON.stringify({
	                      title: top.document.title,
	                      referer: this.currentUri
	                    })));
	                  } catch (e) {}
	                }
	                this.currentUri = uri;
	              }
	            }
	            ui_vue3.nextTick(() => {
	              this.skeleton = '';
	            });
	          }, response => {
	            this.skeleton = '';
	          });
	        },
	        onMessageSlider: function (event) {
	          if (event.eventId === 'total-fav-number') {
	            this.favNumbers = event.data.total;
	          }
	        }
	      },
	      template: `
				<div class="market-wrapper">
					<Toolbar
						:categories="categories"
						:searchFilters="searchFilters"
						:menuInfo="result.MENU_INFO"
						:marketAction="marketAction"
						:searchAction="searchAction"
						v-if="!hideToolbar"
					/>
					<Main
						v-if="isMainPage"
						:params="params"
						:result="result"
					/>
					<ListApps
						v-else
						:params="params"
						:result="result"
					/>
				</div>
			`
	    }).use(ui_vue3_pinia.createPinia()).mount('#market-wrapper-vue');
	  }
	}

	exports.Market = Market;

}((this.BX.Market = this.BX.Market || {}),BX.Vue3.Pinia,BX.Vue3,BX.Market,BX.Market,BX.Market,BX.Event));
