import { TileCollectionMixins } from './tile-collection-mixins';

const Uninstalled = {
	props: {
		tiles: {
			type: Array,
			required: true,
		},
	},
	mixins: [TileCollectionMixins],
	template: `		
		<div>
			<template v-for="(tile, index) in tiles">
				<tile-hint-background-caption-block	v-if="tile.img.length > 0 && tile.showTitle"
					:src="tile.img"
					:name="tile.name"
					:caption="tile.psModeName"
					v-on:tile-hint-bg-label-on-click="openSlider(index)"
					v-on:tile-hint-bg-label-on-mouseenter="showHint(index, $event)"
					v-on:tile-hint-bg-label-on-mouseleave="hideHint"
				/>
				<tile-hint-background-block		v-else-if="tile.img.length > 0"
					:src="tile.img"
					:name="tile.name"
					v-on:tile-hint-bg-on-click="openSlider(index)"
					v-on:tile-label-bg-hint-on-mouseenter="showHint(index, $event)"
					v-on:tile-label-bg-hint-on-mouseleave="hideHint"
				/>
				<tile-hint-plus-block			v-else 
					:name="tile.name" 
					v-on:tile-label-plus-on-click="openSlider(index)"
				/> 
			</template>
		</div>
	`
};

export {
	Uninstalled,
};
