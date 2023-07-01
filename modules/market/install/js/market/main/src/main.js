import {Slider} from "market.slider"
import {Collections} from "market.collections"
import {Categories} from "market.categories"

import "./main.css";

export const Main = {
	components: {
		Slider, Collections, Categories,
	},
	props: [
		'params', 'result',
	],
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
	`,
}