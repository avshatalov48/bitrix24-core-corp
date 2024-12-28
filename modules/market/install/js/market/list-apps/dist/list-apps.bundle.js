this.BX = this.BX || {};
(function (exports,market_listItem,market_categories,market_installStore,ui_vue3_pinia,main_core_events,market_marketLinks,ui_vue3,ui_ears) {
	'use strict';

	const ListApps = {
	  components: {
	    ListItem: market_listItem.ListItem,
	    Categories: market_categories.Categories
	  },
	  props: ['params', 'result'],
	  data() {
	    return {
	      selectedTag: '',
	      selectedOrder: {},
	      loader: false,
	      bottomLoader: false,
	      nextPageLoadWait: false,
	      options: {
	        filter: {
	          empty: ''
	        },
	        order: {
	          empty: ''
	        },
	        page: 1,
	        analytics: {}
	      },
	      MarketLinks: market_marketLinks.MarketLinks
	    };
	  },
	  computed: {
	    mainUri: function () {
	      return this.$root.mainUri.length > 0 ? this.$root.mainUri : this.MarketLinks.mainLink();
	    },
	    isCollection: function () {
	      return this.params.IS_COLLECTION === 'Y';
	    },
	    isCategory: function () {
	      return this.params.IS_CATEGORY === 'Y';
	    },
	    isFavorites: function () {
	      return this.params.IS_FAVORITES === 'Y';
	    },
	    isInstalledList: function () {
	      return this.params.IS_INSTALLED === 'Y';
	    },
	    existApps: function () {
	      return !!(this.result.APPS && BX.type.isArray(this.result.APPS) && this.result.APPS.length);
	    },
	    needSortMenu: function () {
	      return this.result.SHOW_SORT_MENU === 'Y';
	    },
	    showCategories: function () {
	      return !this.existApps && (this.isFavorites || this.isInstalledList);
	    },
	    showNextPageButton: function () {
	      if (this.result.CUR_PAGE && this.result.PAGES) {
	        if (this.result.CUR_PAGE < this.result.PAGES) {
	          return true;
	        }
	      }
	      return false;
	    },
	    prevCategory: function () {
	      if (this.isCategory) {
	        for (let category of this.$root.result.CATEGORIES.ITEMS) {
	          if (category.SUB_ITEMS) {
	            for (let subCategory of category.SUB_ITEMS) {
	              if (subCategory.CODE === this.params.CATEGORY) {
	                return {
	                  'code': category.CODE,
	                  'name': category.NAME
	                };
	              }
	            }
	          }
	        }
	      }
	      return '';
	    },
	    showPrevCategory: function () {
	      return typeof this.prevCategory === 'object';
	    },
	    namePrevCategory: function () {
	      if (this.prevCategory && this.prevCategory.hasOwnProperty('name')) {
	        return this.prevCategory.name;
	      }
	      return '';
	    },
	    codePrevCategory: function () {
	      if (this.prevCategory && this.prevCategory.hasOwnProperty('code')) {
	        return this.prevCategory.code;
	      }
	      return '';
	    },
	    currentAppsCount: function () {
	      if (this.isFavorites) {
	        return this.$root.favNumbers;
	      }
	      return this.$root.result.CURRENT_APPS_CNT;
	    },
	    ...ui_vue3_pinia.mapState(market_installStore.marketInstallState, ['installStep', 'timer'])
	  },
	  mounted: function () {
	    this.setCurrentSort();
	    this.setSelectedTag();
	    this.bindNextPageEvent();
	    this.bindPopupEvent();
	    this.initBottomLoader();
	    this.initTagEars();
	    if (!this.$refs.marketCatalogCategories) {
	      setTimeout(() => {
	        this.initTagEars();
	      }, 300);
	    }
	    this.$Bitrix.eventEmitter.subscribe('market:loadContentFinish', this.loadContentFinish);
	  },
	  methods: {
	    ...ui_vue3_pinia.mapActions(market_installStore.marketInstallState, ['resetInstallStep']),
	    isSelectedTag: function (tag) {
	      return tag === this.selectedTag;
	    },
	    setCurrentSort: function () {
	      if (this.needSortMenu) {
	        this.options.order = this.result.SORT_INFO.CURRENT.VALUE;
	        this.selectedOrder = this.result.SORT_INFO.CURRENT;
	      }
	    },
	    setSelectedTag: function () {
	      if (this.result.hasOwnProperty('SELECTED_TAG') && this.result.SELECTED_TAG.length > 0) {
	        this.selectedTag = this.result.SELECTED_TAG;
	      } else {
	        this.selectedTag = '';
	      }
	    },
	    bindNextPageEvent: function () {
	      BX.bind(document, 'scroll', event => {
	        if (this.needLoadNextPage(event.currentTarget)) {
	          this.nextPage();
	        }
	      });
	    },
	    bindPopupEvent: function () {
	      main_core_events.EventEmitter.subscribe('BX.Main.Popup:onClose', this.onClosePopup);
	    },
	    onClosePopup: function () {
	      if (this.installStep === 2 || this.installStep === 3) {
	        clearTimeout(this.timer);
	        this.$root.updatePage(this.MarketLinks.installedLink(), 'list');
	        this.resetInstallStep();
	      }
	    },
	    initBottomLoader: function () {
	      if (!this.$refs.marketCatalogBottomLoader) {
	        return;
	      }
	      this.bottomLoader = new BX.Loader({
	        target: this.$refs.marketCatalogBottomLoader,
	        size: 100
	      });
	    },
	    initTagEars: function () {
	      if (!this.$refs.marketCatalogCategories) {
	        return;
	      }
	      new ui_ears.Ears({
	        container: this.$refs.marketCatalogCategories,
	        smallSize: true,
	        noScrollbar: true
	      }).init();
	      this.setVisibleTags();
	    },
	    setVisibleTags: function () {
	      if (!this.$refs.marketCatalogCategories) {
	        return;
	      }
	      const tags = this.$refs.marketCatalogCategories.querySelectorAll('.market-catalog__categories-item');
	      for (let item of tags) {
	        if (!this.isVisible(item)) {
	          item.dataset.visible = 'N';
	        }
	      }
	    },
	    isCurrentSort: function (item) {
	      return JSON.stringify(item) === JSON.stringify(this.options.order);
	    },
	    filterTag: function (tag, event) {
	      if (this.loader) {
	        return;
	      }
	      this.selectedTag = this.isSelectedTag(tag) ? '' : tag;
	      this.options.filter = this.getFilter();
	      this.params['REQUEST'] = [];
	      this.options.page = 1;
	      this.setTagAnalyticsLabel(event.currentTarget);
	      this.showLoader();
	      this.loadItems();
	      this.cleanTagAnalyticsLabel();
	    },
	    setTagAnalyticsLabel: function (target) {
	      if (!target || !target.dataset || !target.dataset.visible) {
	        return;
	      }
	      this.options.analytics.isFilterTag = 'Y';
	      this.options.analytics.tagIsVisible = target.dataset.visible;
	    },
	    cleanTagAnalyticsLabel: function () {
	      delete this.options.analytics.isFilterTag;
	      delete this.options.analytics.tagIsVisible;
	    },
	    nextPage: function () {
	      if (this.nextPageLoadWait) {
	        return;
	      }
	      this.nextPageLoadWait = true;
	      this.options.filter = this.getFilter();
	      this.options.page = parseInt(this.result.CUR_PAGE, 10) + 1;
	      if (this.bottomLoader) {
	        this.bottomLoader.show();
	      }
	      this.loadItems(true);
	    },
	    loadItems: function (append, favoriteIndex) {
	      append = append || false;
	      BX.ajax.runComponentAction(this.params.COMPONENT_NAME, 'filterApps', {
	        mode: 'class',
	        signedParameters: [],
	        data: {
	          params: this.params,
	          filter: this.options.filter,
	          order: this.options.order,
	          page: this.options.page
	        },
	        analyticsLabel: {
	          params: this.params,
	          filter: this.options.filter,
	          order: this.options.order,
	          page: this.options.page,
	          analyticsOptions: this.options.analytics
	        }
	      }).then(BX.delegate(function (response) {
	        this.loadItemsFinish(favoriteIndex);
	        if (response.data && BX.type.isArray(response.data.apps)) {
	          if (append) {
	            this.result.APPS = this.result.APPS.concat(response.data.apps);
	          } else {
	            this.result.APPS = response.data.apps;
	          }
	          this.result.CUR_PAGE = response.data.cur_page;
	          this.result.PAGES = response.data.pages;
	          if (this.needSortMenu) {
	            this.result.SORT_INFO.CURRENT = this.selectedOrder;
	          }
	          if (response.data.apps_count) {
	            this.$root.result.CURRENT_APPS_CNT = response.data.apps_count;
	          }
	        }
	      }, this), BX.delegate(function (response) {
	        this.loadItemsFinish(favoriteIndex);
	      }, this));
	    },
	    orderBy: function (menuItem) {
	      if (this.loader) {
	        return;
	      }
	      if (this.isCurrentSort(menuItem.VALUE)) {
	        return;
	      }
	      this.options.filter = this.getFilter();
	      this.options.order = menuItem.VALUE;
	      this.selectedOrder = menuItem;
	      this.options.page = 1;
	      this.showLoader();
	      this.loadItems();
	    },
	    getFilter: function () {
	      let filter = {
	        tag: this.selectedTag
	      };
	      if (this.result.CATEGORY_TAGS === 'Y') {
	        filter = {
	          categoryTag: this.selectedTag
	        };
	      }
	      return filter;
	    },
	    showLoader: function () {
	      this.loader = true;
	    },
	    loadItemsFinish: function (favoriteIndex) {
	      this.loader = false;
	      this.nextPageLoadWait = false;
	      if (this.bottomLoader) {
	        this.bottomLoader.hide();
	      }
	      this.$Bitrix.eventEmitter.emit('market:rmFavorite', {
	        favoriteIndex: favoriteIndex
	      });
	      ui_vue3.nextTick(() => {
	        this.initBottomLoader();
	      });
	      if (favoriteIndex) {
	        window.scrollTo({
	          top: this.$refs.marketCatalogTitleBlock.getBoundingClientRect().top,
	          behavior: 'smooth'
	        });
	      }
	    },
	    loadContentFinish: function () {
	      setTimeout(() => {
	        this.setCurrentSort();
	        this.setSelectedTag();
	        this.initTagEars();
	        this.initBottomLoader();
	      }, 300);
	    },
	    needLoadNextPage: function (document) {
	      if (!document || !document.scrollingElement || !document.scrollingElement.scrollHeight || !this.showNextPageButton || this.nextPageLoadWait) {
	        return false;
	      }
	      const doc = document.scrollingElement;
	      return doc.scrollTop >= doc.scrollHeight - doc.offsetHeight * 2;
	    },
	    isVisible: function (target) {
	      const targetPosition = {
	        top: window.pageYOffset + target.getBoundingClientRect().top,
	        left: window.pageXOffset + target.getBoundingClientRect().left,
	        right: window.pageXOffset + target.getBoundingClientRect().right,
	        bottom: window.pageYOffset + target.getBoundingClientRect().bottom
	      };
	      const windowPosition = {
	        top: window.pageYOffset,
	        left: window.pageXOffset,
	        right: window.pageXOffset + document.documentElement.clientWidth,
	        bottom: window.pageYOffset + document.documentElement.clientHeight
	      };
	      return targetPosition.bottom > windowPosition.top && targetPosition.top < windowPosition.bottom && targetPosition.right > windowPosition.left && targetPosition.left < windowPosition.right;
	    }
	  },
	  template: `
		<img class="market-skeleton-img"
			 :src="$root.getSkeletonPath"
			 v-if="$root.showSkeleton"
		>
		<div class="market-catalog" id="market-catalog-container-id"
			 v-else
		>
			<div class="market-catalog__nav">
				<div class="market-catalog__breadcrumbs">
					<div class="market-catalog__breadcrumbs_item" v-if="!$root.hideBreadcrumbs">
						<a class="market-catalog__breadcrumbs_link"
						   data-slider-ignore-autobinding="true"
						   :href="mainUri"
						   data-load-content="main"
						   @click.prevent="$root.emitLoadContent"
						>
							{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_LINK_MAIN') }}
						</a>
					</div>
					<template v-if="showPrevCategory">
						<div class="market-catalog__breadcrumbs_item">
							<span class="market-catalog__breadcrumbs_point">&#183;</span>
						</div>
						<div class="market-catalog__breadcrumbs_item">
							<a class="market-catalog__breadcrumbs_link"
							   :href="MarketLinks.categoryLink(codePrevCategory)"
							   data-slider-ignore-autobinding="true"
							   data-load-content="list"
							   @click.prevent="$root.emitLoadContent"
							>
								{{ namePrevCategory }}
							</a>
						</div>
					</template>
				</div>
		
				<div class="market-catalog__title-block"
					 ref="marketCatalogTitleBlock"
					 v-if="$root.showTitle"
				>
					<div class="market-catalog__title">
						<div class="market-catalog__title_name">{{ $root.result.TITLE }}</div>
						<div class="market-catalog__title_counter"
							 v-if="currentAppsCount > 0"
						>{{ currentAppsCount }}</div>
					</div>
				</div>
				
				<div class="market-catalog__categories" ref="marketCatalogCategories">
					<template v-if="result.FILTER_TAGS">
						<span class="market-catalog__categories-item"
							  data-visible="Y"
							  v-for="tag in result.FILTER_TAGS"
							  :class="[{'--checked': isSelectedTag(tag.value)}]"
							  @click="filterTag(tag.value, $event)"
						>
							{{ tag.name }}
						</span>
					</template>
					<template v-else-if="result.FILTER_CATEGORIES">
						<span class="market-catalog__categories-item"
							  v-for="tag in result.FILTER_CATEGORIES"
							  :class="[{'--checked': isSelectedTag(tag.value)}]"
							  :data-href="MarketLinks.categoryLink(tag.value)"
							  data-load-content="list"
							  @click.prevent="$root.emitLoadContent"
						>
							{{ tag.name }}
						</span>
					</template>
				</div>
			</div>
		
			<div class="market-catalog__content">
				<div class="market-catalog__sorting" v-if="needSortMenu">
					<div class="market-catalog__sorting-name">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_SORT') }}</div>
					<span :class="['market-catalog__sorting-link', {'--active': isCurrentSort(sortItem.VALUE)}]"
						  v-for="sortItem in result.SORT_INFO.LIST"
						  @click="orderBy(sortItem)"
					>
							{{ sortItem.NAME }}
						</span>
				</div>
		
				<div class="market-catalog__elements" v-if="!existApps && !loader">
					<div class="market-catalog__elements_no-updates" v-if="isCollection || isCategory">
						<div class="market-catalog__elements_no-updates-icon">
							<img src="/bitrix/js/market/images/no-apps.svg" alt="">
						</div>
						<div class="market-catalog__elements_no-updates-title">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_NO_APPS_MATCHING_YOUR_REQUEST') }}</div>
					</div>
					<div class="market-catalog__elements_no-updates" v-if="isFavorites">
						<div class="market-catalog__elements_no-updates-icon">
							<img src="/bitrix/js/market/images/no-favorites.svg" alt="">
						</div>
						<div class="market-catalog__elements_no-updates-title">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_THERE_IS_NOTHING_IN_FAVORETES') }}</div>
						<div class="market-catalog__elements_no-updates-description">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_MARK_THE_APPS_YOU_LIKE') }}</div>
						<div class="market-catalog__elements_no-updates-description">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_SEARCH_APPS_ON_CATEGORIES') }}</div>
					</div>
					<div class="market-catalog__elements_no-updates" v-if="isInstalledList && !this.selectedTag">
						<div class="market-catalog__elements_no-updates-icon">
							<img src="/bitrix/js/market/images/no-installed.svg" alt="">
						</div>
						<div class="market-catalog__elements_no-updates-title">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_NO_APPS_INSTALLED') }}</div>
						<div class="market-catalog__elements_no-updates-description">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_CHOOSE_FROM_MANY_APP_CATEGORIES') }}</div>
					</div>
					<div class="market-catalog__elements_no-updates" v-if="isInstalledList && this.selectedTag">
						<div class="market-catalog__elements_no-updates-icon">
							<img src="/bitrix/js/market/images/no-updates.svg" alt="">
						</div>
						<div class="market-catalog__elements_no-updates-title">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_NO_UPDATES') }}</div>
						<div class="market-catalog__elements_no-updates-description">{{ $Bitrix.Loc.getMessage('MARKET_LIST_APPS_JS_THERE_ARE_NO_APPS_THAT_NEED_UPDATING') }}</div>
					</div>
				</div>
				<img class="market-search-skeleton-img"
					 src="/bitrix/images/market/slider/items.svg"
					 v-if="loader"
				>
				<template v-else>
					<div class="market-catalog__elements" v-if="existApps">
						<ListItem
							v-for="(appItem, index) in result.APPS"
							:item="appItem"
							:params="params"
							:index="index"
						/>
					</div>
					<div ref="marketCatalogBottomLoader"
						:class="{'market-catalog__elements_loader': showNextPageButton}"
					></div>
					<Categories
						v-if="showCategories"
						:categories="$root.categories"
					/>
				</template>
			</div>
		</div>
	`
	};

	exports.ListApps = ListApps;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Market,BX.Market,BX.Vue3.Pinia,BX.Event,BX.Market,BX.Vue3,BX.UI));
