import {CollectionItemAds} from "market.collection-item-ads"
import {CollectionItem} from "market.collection-item"
import {CollectionTop} from "market.collection-top";
import {CollectionTop2} from "market.collection-top2";

export const Collections = {
	components: {
		CollectionItemAds, CollectionItem, CollectionTop, CollectionTop2,
	},
	props: [
		'params', 'items', 'nextPage',
	],
	data() {
		return {
			loader: null,
			collectionPage: 1,
			loadingInProgress: false,
		}
	},
	computed: {
		hasNextPage: function () {
			return this.nextPage === 'Y';
		},
		showNextPage: function () {
			return this.hasNextPage && !this.loadingInProgress;
		},
	},
	methods: {
		nextPageEvent: function () {
			if (this.loadingInProgress) {
				return;
			}

			this.showLoader();

			const scrollToPosition = this.$refs['market-more-collections'].offsetTop - (this.$refs['market-more-collections'].offsetHeight / 2);
			const nextPage = this.collectionPage + 1;
			BX.ajax.runComponentAction(this.params.COMPONENT_NAME, 'getMoreCollections', {
				mode: 'class',
				signedParameters: [],
				data: {
					collectionPage: nextPage,
					placement: this.params.PLACEMENT,
					tags: this.params.TAGS,
				},
				analyticsLabel: {
					collectionPage: nextPage,
					placement: this.params.PLACEMENT,
					tags: this.params.TAGS,
				},
			}).then(
				response => {
					this.hideLoader();
					this.collectionPage++;

					this.$parent.result.ENABLE_NEXT_PAGE = (response.data && response.data.existNextPage === 'Y') ? 'Y' : 'N';
					if (response.data && response.data.items) {
						this.$parent.result.COLLECTIONS = this.$parent.result.COLLECTIONS.concat(response.data.items);
					}

					let adjusrScroll = () => {
						clearTimeout(scrollToTimer);
						window.removeEventListener('scroll', adjusrScroll);
					};

					window.addEventListener('scroll', adjusrScroll);

					let scrollToTimer = setTimeout(() => {
						window.scrollTo({
							top: scrollToPosition,
							behavior: 'smooth'
						});
						window.removeEventListener('scroll', adjusrScroll);
					}, 1000);
				},
				response => {
					this.hideLoader();
				},
			);
		},
		showLoader: function () {
			if (!this.loader) {
				this.loader = new BX.Loader({
					target: document.querySelector('.market-more-collections'),
					size: 40,
				});
			}
			this.loader.show();
			this.loadingInProgress = true;
		},
		hideLoader: function () {
			this.loadingInProgress = false;
			this.loader.hide();
		},
	},
	template: `
		<div class="market-wrapper-content">
			<div class="market-container">
				<template v-for="item in items">
					<div class="market-single-item"
						 v-if="item.SINGLE_VIEW === 'Y'"
					>
						<CollectionItemAds
							v-if="item.IS_AD === 'Y'"
							:item="item"
						/>
						<CollectionItem
							v-else
							:item="item"
						/>
					</div>
					<div class="market-group-items"
						 v-else
					>
						<CollectionTop
							v-if="item.ONE_ROW === 'Y'"
							:item="item"
							:collectionIndex="item.INDEX"
						/>
						<CollectionTop2
							v-else
							:item="item"
							:scrollable="true"
							:showListButton="true"
						/>
					</div>
				</template>
			</div>
			<div class="market-lazyload-btn-container"
				 v-if="hasNextPage"
			>
				<div class="market-lazyload-btn-spacer-block"></div>
				<div class="market-lazyload-btn-block">
					<span class="market-lazyload-btn market-more-collections"
						  :ref="'market-more-collections'"
						  :class="{'market-loader-container': loadingInProgress}"
						  @click="nextPageEvent"
					>
						{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_JS_SHOW_MORE_COLLECTIONS') }}
						<svg width="6" height="9" viewBox="0 0 6 9" fill="none" xmlns="http://www.w3.org/2000/svg"
							 v-if="!loadingInProgress"
						>
							<path fill-rule="evenodd" clip-rule="evenodd" d="M0 0.990883L3.06862 3.79917L3.86345 4.49975L3.06862 5.20075L0 8.00904L1.08283 9L6 4.5L1.08283 0L0 0.990883Z" fill="#B9BFC3"></path>
						</svg>
					</span>
				</div>
				<div class="market-lazyload-btn-spacer-block"></div>
			</div>
		</div>
	`,
}