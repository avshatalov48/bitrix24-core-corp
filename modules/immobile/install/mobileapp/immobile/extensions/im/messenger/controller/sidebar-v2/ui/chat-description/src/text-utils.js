/**
 * @module im/messenger/controller/sidebar-v2/ui/chat-description/src/text-utils
 */
jn.define('im/messenger/controller/sidebar-v2/ui/chat-description/src/text-utils', (require, exports, module) => {
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');
	const { Color } = require('tokens');

	// todo it actually depends on device
	const MAX_LEN = 80;

	const EXPAND_TEXT = Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_EXPAND_TEXT');
	const EXPAND_BUTTON = `... [color=${Color.accentMainPrimary.toHex()}]${EXPAND_TEXT}[/color]`;
	const EXPAND_BUTTON_VISIBLE_CONTENT = `... ${EXPAND_TEXT}`;
	const CROP_POSITION = MAX_LEN - EXPAND_BUTTON_VISIBLE_CONTENT.length;

	/**
	 * @param {string} text
	 * @return {string}
	 */
	const truncate = (text) => {
		const [crop, cleanText] = shouldCrop(text);

		if (crop)
		{
			return `${cleanText.slice(0, CROP_POSITION).trim()}${EXPAND_BUTTON}`;
		}

		return cleanText;
	};

	/**
	 * @param {string} text
	 * @return {[boolean,string]}
	 */
	const shouldCrop = (text) => {
		const cleanText = normalize(text);

		if (cleanText.length > CROP_POSITION)
		{
			return [true, cleanText];
		}

		return [false, cleanText];
	};

	/**
	 * @param {string} text
	 * @return {string}
	 */
	const normalize = (text) => text
		.split('\n')
		.filter((line) => line.trim() !== '')
		.map((line) => line.trim())
		.join(' ');

	module.exports = { truncate, shouldCrop, normalize };
});
