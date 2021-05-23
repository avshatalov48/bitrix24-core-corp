import {TileCollectionMixins} from "./tile-collection-mixins";

const UninstalledPayment = {
	props: {
		tiles: {
			type: Array,
			required: true
		}
	},
	mixins:[TileCollectionMixins],
	template: `		
		<div>
			<template v-for="(tile, index) in tiles">
				<tile-hint-img-block	v-if="tile.img.length > 0"
					:src="tile.img"
					:name="tile.name"
					v-on:tile-hint-img-on-click="openSlider(index)"
					v-on:tile-label-img-hint-on-mouseenter="showHint(index, $event)"
					v-on:tile-label-img-hint-on-mouseleave="hideHint"
				/>
				<tile-hint-plus-block	v-else 
					:name="tile.name"
					v-on:tile-label-plus-on-click="openSlider(index)"
				/>				
			</template>
		</div>
	`
};
export {
	UninstalledPayment
}