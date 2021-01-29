import {TileCollectionMixins} from "./tile-collection-mixins";

const Installed = {
	props: {
		tiles: {
			type: Array,
			required: true
		}
	},
	mixins:[TileCollectionMixins],

	template: `	
		<div class="salescenter-app-payment-by-sms-item-container-payment">
			<div class="salescenter-app-payment-by-sms-item-container-payment-inline">
				<tile-label-block class="salescenter-app-payment-by-sms-item-container-payment-item-text"
					v-for="(tile, index) in tiles"
					v-if="isControlTile(tile) === false"
					:name="tile.name" 
					v-on:tile-label-on-click="openSlider(index)"
				/>
				<br>
				<tile-label-block class="salescenter-app-payment-by-sms-item-container-payment-item-text-add"
					v-if="hasTileOfferFromCollection() === true"
					:name="getTileOfferFromCollection().tile.name"
					v-on:tile-label-on-click="openSlider(getTileOfferFromCollection().index)"
				/>
				<tile-label-block class="salescenter-app-payment-by-sms-item-container-payment-item-text-add"
					v-if="hasTileMoreFromCollection() === true"
					:name="getTileMoreFromCollection().tile.name"
					v-on:tile-label-on-click="openSlider(getTileMoreFromCollection().index)"
				/>
			</div>
		</div>
	`
};
export {
	Installed
}