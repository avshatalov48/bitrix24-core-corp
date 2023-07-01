this.BX = this.BX || {};
(function (exports,market_slider,market_collections,market_categories) {
	'use strict';

	const Main = {
	  components: {
	    Slider: market_slider.Slider,
	    Collections: market_collections.Collections,
	    Categories: market_categories.Categories
	  },
	  props: ['params', 'result'],
	  template: `
		<img class="market-skeleton-img"
			 :src="$root.getSkeletonPath"
			 v-if="$root.showSkeleton"
		>
		<template v-else>
			<Slider
				:info="result.SLIDER"
				:options="{
					sliderId: params.SLIDER_ID,
					borderRadius: 12,
					autoSlide: false,
					arrows: true,
					column: 1,
					controls: true,
				}"
			/>
			<Collections
				:params="params"
				:items="result.COLLECTIONS"
				:nextPage="result.ENABLE_NEXT_PAGE"
			/>
			<Categories
				v-if="!$root.hideCategories"
				:categories="$root.categories"
			/>
		</template>
	`
	};

	exports.Main = Main;

}((this.BX.Market = this.BX.Market || {}),BX.Market,BX.Market,BX.Market));
