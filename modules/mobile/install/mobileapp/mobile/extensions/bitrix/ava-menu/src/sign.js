/**
 * @module ava-menu/sign
 */
jn.define('ava-menu/sign', (require, exports, module) => {
	const { menu } = require('native/avamenu') || {};
	const { Color } = require('tokens');

	let Entry = null;
	try
	{
		Entry = require('sign/entry').Entry;
	}
	catch (e)
	{
		console.warn(e);
	}

	const ITEM_ID = 'start_signing';

	class Sign
	{
		/** Open sign-grid component and sign-master in backdrop */
		static open()
		{
			if (Entry)
			{
				Entry.openE2bMaster();
			}
		}

		static handleItemColor()
		{
			const items = menu.getItems();

			if (!items)
			{
				return;
			}

			const item = items.find(({ id }) => ITEM_ID === id);

			if (!item)
			{
				return;
			}

			if (item.customData?.enabledBySettings)
			{
				Sign.#updateItemColor(true);
			}
		}

		static #updateItemColor(isEnabled)
		{
			const color = isEnabled ? Color.accentMainPrimary.toHex() : Color.base0.toHex();

			menu.updateItem(ITEM_ID, {
				titleColor: String(color),
				iconColor: String(color),
			});
		}
	}

	module.exports = { Sign };
});
