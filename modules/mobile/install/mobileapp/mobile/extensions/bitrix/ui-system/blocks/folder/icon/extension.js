/**
 * @module ui-system/blocks/folder/icon
 */
jn.define('ui-system/blocks/folder/icon', (require, exports, module) => {
	const { FolderIconSize } = require('ui-system/blocks/folder/icon/src/size-enum');
	const { FolderIconMode } = require('ui-system/blocks/folder/icon/src/mode-enum');
	const { IconView } = require('ui-system/blocks/icon');
	const { Color } = require('tokens');

	/**
	 * @typedef {Object} FolderIconProps
	 * @property {FolderIconMode} [mode=FolderIconMode.BASIC]
	 * @property {FolderIconSize} [design=FolderIconSize.NORMAL]
	 * @property {testId} testId
	 */

	/**
	 * @param {FolderIconProps} props
	 * @returns {LayoutComponent}
	 */
	function FolderIcon(props)
	{
		const resolvedMode = FolderIconMode.resolve(props.mode, FolderIconMode.BASIC);
		const backgroundIcon = resolvedMode.getBackgroundIcon();
		const icon = resolvedMode.getIcon();

		const resolvedSize = FolderIconSize.resolve(props.size, FolderIconSize.NORMAL);
		const size = resolvedSize.getSize();
		const iconSize = resolvedSize.getIconSize();
		const iconCoordinates = resolvedSize.getIconCoordinates();

		return View(
			{
				style: {
					width: size,
					height: size,
					position: 'relative',
				},
				testId: props.testId,
			},
			IconView({
				icon: backgroundIcon,
				size,
				color: null,
			}),
			icon && IconView({
				icon,
				size: iconSize,
				color: Color.accentSoftBlue2,
				style: {
					position: 'absolute',
					...iconCoordinates,
				},
			}),
		);
	}

	FolderIcon.propTypes = {
		testId: PropTypes.string.isRequired,
		size: PropTypes.instanceOf(FolderIconSize),
		mode: PropTypes.instanceOf(FolderIconMode),
	};

	module.exports = { FolderIcon, FolderIconMode, FolderIconSize };
});
