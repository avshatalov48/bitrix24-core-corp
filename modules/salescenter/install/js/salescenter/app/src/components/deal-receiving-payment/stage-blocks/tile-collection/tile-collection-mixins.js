import
{
	AppSlider,
	EventTypes
} 								from 'salescenter.marketplace';
import
{
	Label,
	TileLabel,
	TileHintImg,
	TileLabelPlus,
	TileHintImgCaption,
	TileHintBackground,
	TileHintBackgroundCaption,
} 								from 'salescenter.component.stage-block.tile';
import * as Hint 				from 'salescenter.component.stage-block.hint';
import * as Tile 				from 'salescenter.tile';


let TileCollectionMixins = {

	components:
		{
			'label-block'							:	Label,
			'tile-label-block'						:	TileLabel,
			'tile-hint-img-block'					:	TileHintImg,
			'tile-hint-plus-block'					:	TileLabelPlus,
			'tile-hint-img-caption-block'			:	TileHintImgCaption,
			'tile-hint-background-block'			:	TileHintBackground,
			'tile-hint-background-caption-block'	:	TileHintBackgroundCaption
		},
	methods:
		{
			getCollectionTile()
			{
				return this.tiles;
			},

			getCollectionTileByFilter(filter: Array)
			{
				let map = new Map();
				let collection = this.getCollectionTile();

				if(filter.hasOwnProperty('type') && filter.type.length > 0)
				{
					collection
						.forEach((item, index) => {

							if(filter.type === item.getType())
							{
								map.set(index, item);
							}
						});
				}
				else
				{
					collection
						.forEach((item, index) => map.set(index, item));
				}

				return map;
			},

			hasTileOfferFromCollection()
			{
				let map = this.getCollectionTileByFilter({
					type: Tile.Offer.type()
				});

				return map.size > 0;
			},

			hasTileMoreFromCollection()
			{
				let map = this.getCollectionTileByFilter({
					type: Tile.More.type()
				});

				return map.size > 0;
			},

			getTileOfferFromCollection()
			{
				let map = this.getCollectionTileByFilter({
					type: Tile.Offer.type()
				});

				let result = {};
				map.forEach((item, inx)=> { result = {index: inx, tile: item}; return false;});
				return result;
			},

			getTileMoreFromCollection()
			{
				let map = this.getCollectionTileByFilter({
					type: Tile.More.type()
				});

				let result = {};
				map.forEach((item, inx)=> { result = {index: inx, tile: item}; return false;});
				return result;
			},

			getTileByIndex(index)
			{
				let  tile = null;
				this.getCollectionTileByFilter({})
					.forEach((item, inx)=> {

						if(index === inx)
						{
							tile = item;
						}
					});
				return tile;
			},

			openSlider(inx)
			{
				let slider = new AppSlider();
				let tile = this.getTileByIndex(inx);

				slider.openAppLocal(tile, this.getOptionSlider);
				slider.subscribe(EventTypes.AppSliderSliderClose,
					(e) => this.$emit('on-tile-slider-close', {data: e.data})
				);
			},

			isControlTile(tile: Tile.Base)
			{
				return [Tile.More.type(), Tile.Offer.type()].includes(tile.getType());
			},

			showHint(inx, e)
			{
				let event = e.data.event;
				let tile = this.getTileByIndex(inx);

				this.popup = new Hint.Popup();
				this.popup.show(event.target, tile.info);
			},

			hideHint()
			{
				if(this.popup)
				{
					this.popup.hide();
				}
			}
		},
	computed:
		{
			getOptionSlider()
			{
				return {
					width: 1000
				}
			}
		},
};
export {
	TileCollectionMixins
}