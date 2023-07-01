/**
 * @module crm/timeline/stream/utils/item-position-calculator
 */
jn.define('crm/timeline/stream/utils/item-position-calculator', (require, exports, module) => {
	class ItemPositionCalculator
	{
		constructor(streams)
		{
			/** @type {TimelineStreamBase[]} */
			this.streams = streams;
		}

		/**
		 * @param {string} key
		 * @return {number}
		 */
		calculateByKey(key)
		{
			// Initial offset is important due to ListView contains hacky first and last elements to emulate paddings.
			let offset = 1;

			for (const i in this.streams)
			{
				const stream = this.streams[i];
				const exported = stream.exportToListView();
				const index = exported.findIndex((item) => item.key === key);
				if (index > -1)
				{
					return index + offset;
				}
				offset += exported.length;
			}
			return -1;
		}
	}

	module.exports = { ItemPositionCalculator };
});
