import { MarketLinks } from "market.market-links";

import "./categories.css";

export const Categories = {
	props: [
		'categories',
	],
	data() {
		return {
			MarketLinks: MarketLinks,
		}
	},
	template: `
		<div class="market-category-items">
			<span class="market-category-list-container"
				  v-if="categories.ITEMS"
			>
				<span class="market-category-list-header">
					<h2 class="market-category-list-title">{{ $Bitrix.Loc.getMessage('MARKET_CATEGORIES_JS_VIEW_THE_ENTIRE_CATALOG') }}</h2>
				</span>
				<span class="market-category-list-content">
					<a class="market-category-list-item"
					   v-for="category in categories.ITEMS"
					   :href="MarketLinks.categoryLink(category.CODE)"
					   data-slider-ignore-autobinding="true"
					   data-load-content="list"
					   @click.prevent="$root.emitLoadContent"
					>
						<span class="market-category-list-item-inner"
							  :style="{
								'background-color': category.COLOR,
								'background-image': 'url(\\'' + category.IMAGE + '\\')',
							  }"
							  :title="category.NAME"
						>
							<span class="market-category-list-item-title">{{ category.NAME }}</span>
							<span class="market-category-list-item-counter"
								  v-if="parseInt(category.CNT, 10) > 0"
							>
								{{ category.CNT }}
							</span>
						</span>
					</a>
				</span>
			</span>
		</div>
	`,
}