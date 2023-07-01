import {CollectionTop2List} from "market.collection-top2-list";

import "./collection-top2.css";

export const CollectionTop2 = {
	components: {
		CollectionTop2List: CollectionTop2List,
	},
	props: [
		'item', 'scrollable', 'showListButton',
	],
	mounted: function() {
		if (this.scrollable) {
			(new BX.UI.Ears({
				container: document.querySelector('#item_' + this.item.CAROUSEL_ID),
				smallSize: true,
				noScrollbar: true,
				className: "market-toplist-inner-carousel-container"
			})).init();
		}
	},
	template: `
		<div class="market-toplist-container"
			 :class="{' --scrollable': scrollable, }"
		>
			<div class="market-toplist-header-container"
				 v-if="showListButton"
			>
				<div class="market-toplist-header-block">
					<h2 class="market-toplist-title-text"
						:title="item.NAME"
					>
						<a class="market-toplist-title-text-link"
						   :href="$root.getCollectionUri(item.COLLECTION_ID, item.SHOW_ON_PAGE)"
						   data-slider-ignore-autobinding="true"
						   data-load-content="list"
						   @click.prevent="$root.emitLoadContent"
						>
							{{ item.NAME }}
						</a>
					</h2>
					<div class="market-toplist-title-counter"
						 v-if="parseInt(item.NUMBER_APPS, 10) > 0"
					>
						{{ item.NUMBER_APPS }}
					</div>
				</div>
				<div class="market-toplist-header-block --not-compressible">
					<a class="market-toplist-more-btn"
					   :href="$root.getCollectionUri(item.COLLECTION_ID, item.SHOW_ON_PAGE)"
					   data-slider-ignore-autobinding="true"
					   data-load-content="list"
					   @click.prevent="$root.emitLoadContent"
					>
						{{ $Bitrix.Loc.getMessage('MARKET_COLLECTIONS_TOP_JS_SHOW_AS_A_LIST') }}
						<svg width="6" height="9" viewBox="0 0 6 9" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" clip-rule="evenodd"
								  d="M0 0.990883L3.06862 3.79917L3.86345 4.49975L3.06862 5.20075L0 8.00904L1.08283 9L6 4.5L1.08283 0L0 0.990883Z"
								  fill="#B9BFC3"/>
						</svg>
					</a>
				</div>
			</div>
			<div :id="'item_' + item.CAROUSEL_ID" class="market-toplist-content-container">
				<div :style="{'min-width': 'calc(' + item.STYLE_FOR_TOP2 + ' * var(--market-top-preview-size))', }"
					 v-if="scrollable"
				>
					<CollectionTop2List
						:apps="item.APPS"
						:scrollable="scrollable"
						:styleNumber="item.STYLE_FOR_TOP2"
					/>
				</div>
				<CollectionTop2List
					v-else
					:apps="item.APPS"
					:scrollable="scrollable"
					:styleNumber="item.STYLE_FOR_TOP2"
				/>
			</div>
		</div>
	`,
}