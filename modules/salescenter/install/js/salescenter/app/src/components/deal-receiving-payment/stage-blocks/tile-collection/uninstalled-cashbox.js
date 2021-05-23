import {TileCollectionMixins} from "./tile-collection-mixins";

const UninstalledCashbox = {
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
				<tile-hint-img-caption-block	v-if="tile.img.length > 0 && tile.showTitle"
					:src="tile.img"
					:name="tile.name"
					:caption="tile.name"
					v-on:tile-hint-img-label-on-click="openSlider(index)"
					v-on:tile-hint-img-label-on-mouseenter="showHint(index, $event)"
					v-on:tile-hint-img-label-on-mouseleave="hideHint"
				/>
				<tile-hint-img-block	v-else-if="tile.img.length > 0"
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
	UninstalledCashbox
}