this.BX = this.BX || {};
(function (exports,market_marketLinks) {
	'use strict';

	const CollectionItem = {
	  props: ['item'],
	  data() {
	    return {
	      MarketLinks: market_marketLinks.MarketLinks
	    };
	  },
	  template: `
		<a class="market-item-container"
		   :href="MarketLinks.collectionLink(item.COLLECTION_ID, item.SHOW_ON_PAGE)"
		   data-slider-ignore-autobinding="true"
		   data-load-content="list"
		   @click.prevent="$root.emitLoadContent"
		>
			<span class="market-item-container-blur-bg"
				  :style="{'background-image': 'url(\\'' + item.IMAGE + '\\')'}"
			></span>
			<span class="market-item-container-inner" :title="item.NAME">
				<span class="market-item-images-block"
					  :style="{'background-image': 'url(\\'' + item.IMAGE + '\\')'}"
				></span>
				<span class="market-item-info-block">
					<span class="market-item-title">
						<h2 class="market-item-title-text"
							:title="item.NAME"
						>{{ item.NAME }}</h2>
						<span class="market-item-title-counter"
							  v-if="parseInt(item.NUMBER_APPS, 10) > 0"
						>
							{{ item.NUMBER_APPS }}
						</span>
						<span class="market-item-title-angel">
							<svg width="6" height="9" viewBox="0 0 6 9" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path fill-rule="evenodd" clip-rule="evenodd" d="M0 0.990883L3.06862 3.79917L3.86345 4.49975L3.06862 5.20075L0 8.00904L1.08283 9L6 4.5L1.08283 0L0 0.990883Z" fill="#fff"/>
							</svg>
						</span>
					</span>
					<span class="market-item-description"
						  :title="item.PREVIEW_TEXT"
					>{{ item.PREVIEW_TEXT }}</span>
				</span>
			</span>
		</a>
	`
	};

	exports.CollectionItem = CollectionItem;

}((this.BX.Market = this.BX.Market || {}),BX.Market));
