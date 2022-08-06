import { Type } from 'main.core';
import { Base } from './base';

/**
 * Group of tiles
 */
class Group
{
	constructor(props)
	{
		/**
		 * Group id
		 * @type {string}
		 */
		this.id = props.id;
		if (!Type.isString(props.id))
		{
			throw new Error(`Property 'id' is required for Group`);
		}
		
		/**
		 * Group name
		 * @type {string}
		 */
		this.name = Type.isString(props.name) ? props.name : '';
		
		/**
		 * Tiles included in the group
		 * @type {Base[]}
		 */
		this.tiles = [];
	}
	
	/**
	 * Filling group tiles from array
	 * 
	 * @param {Base[]} tiles
	 */
	fillTiles(tiles)
	{
		if (Type.isArray(tiles))
		{
			tiles.forEach((item) => {
				if (item instanceof Base && item.group == this.id)
				{
					this.tiles.push(item);
				}
			});
		}
	}
}

export
{
	Group
}