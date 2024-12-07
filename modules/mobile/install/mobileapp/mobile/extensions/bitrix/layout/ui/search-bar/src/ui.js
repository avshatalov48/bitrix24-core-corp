/**
 * @module layout/ui/search-bar/ui
 */
jn.define('layout/ui/search-bar/ui', (require, exports, module) => {
	const { ChipFilter } = require('ui-system/blocks/chips/chip-filter');
	const { Indent, Color } = require('tokens');

	/**
	 * @param {function} onClick
	 * @return {object}
	 */
	const MoreButton = ({ onClick }) => ChipFilter(
		{
			onClick,
			modeMore: true,
			style: {
				marginLeft: Indent.M.toNumber(),
				flexShrink: null,
				flexGrow: 2,
			},
		},
	);

	const MINIMAL_SEARCH_LENGTH = 3;
	const DEFAULT_ICON_BACKGROUND = Color.accentMainPrimary.toHex();
	const ENTER_PRESSED_EVENT = 'clickEnter';

	module.exports = {
		MoreButton,
		MINIMAL_SEARCH_LENGTH,
		DEFAULT_ICON_BACKGROUND,
		ENTER_PRESSED_EVENT,
	};
});
