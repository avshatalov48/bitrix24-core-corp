/**
 * @module layout/ui/search-bar/ui
 */
jn.define('layout/ui/search-bar/ui', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const CloseIcon = (testId = '') => Image({
		testId,
		style: {
			marginLeft: 13,
			marginRight: 2,
			width: 8,
			height: 8,
		},
		svg: {
			content: `<svg width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.05882 0.000222688L8 0.941373L0.941178 8L1.38837e-06 7.05885L7.05882 0.000222688Z" fill="${AppTheme.colors.base3}"/><path d="M0 0.94115L0.941176 0L8 7.05863L7.05882 7.99978L0 0.94115Z" fill="${AppTheme.colors.base3}"/></svg>`,
		},
	});

	/**
	 * @param {string} text
	 * @param {boolean} disabled
	 * @return {object}
	 */
	const Title = ({ text, disabled = false }) => Text({
		text,
		numberOfLines: 1,
		ellipsize: 'middle',
		style: {
			color: (disabled ? AppTheme.colors.base4 : AppTheme.colors.base1),
			fontWeight: '500',
			fontSize: 16,
			lineHeight: 10,
			maxWidth: 300,
		},
	});

	/**
	 * @param {string} value
	 * @param {string|null} color
	 * @return {object}
	 */
	const CounterValue = ({ value, color = null }) => {
		const counter = parseInt(value, 10);

		return Text({
			style: {
				color: AppTheme.colors.baseWhiteFixed,
				borderRadius: 8,
				fontSize: 12,
				height: 17,
				backgroundColor: counter > 0 && color ? color : AppTheme.colors.base4,
				marginLeft: 5,
				paddingHorizontal: Application.getPlatform() === 'android' ? 4 : 7,
				textAlign: 'center',
				fontWeight: '700',
			},
			text: value,
		});
	};

	/**
	 * @param {function} onClick
	 * @return {object}
	 */
	const MoreButton = ({ onClick }) => View(
		{
			onClick,
			style: {
				width: 50,
				borderColor: AppTheme.colors.accentSoftBlue1,
				borderRadius: 20,
				borderWidth: 2,
				height: 34,
				justifyContent: 'center',
				alignItems: 'center',
			},
		},
		Image({
			style: {
				width: 16,
				height: 4,
			},
			svg: {
				content: `<svg width="16" height="4" viewBox="0 0 16 4" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4C3.10457 4 4 3.10457 4 2C4 0.89543 3.10457 0 2 0C0.89543 0 0 0.89543 0 2C0 3.10457 0.89543 4 2 4Z" fill="${AppTheme.colors.base3}"/><path d="M8 4C9.10457 4 10 3.10457 10 2C10 0.89543 9.10457 0 8 0C6.89543 0 6 0.89543 6 2C6 3.10457 6.89543 4 8 4Z" fill="${AppTheme.colors.base3}"/><path d="M16 2C16 3.10457 15.1046 4 14 4C12.8954 4 12 3.10457 12 2C12 0.89543 12.8954 0 14 0C15.1046 0 16 0.89543 16 2Z" fill="${AppTheme.colors.base3}"/></svg>`,
			},
		}),
	);

	const MINIMAL_SEARCH_LENGTH = 3;
	const DEFAULT_ICON_BACKGROUND = AppTheme.colors.accentBrandBlue;
	const ENTER_PRESSED_EVENT = 'clickEnter';

	module.exports = {
		CloseIcon,
		Title,
		CounterValue,
		MoreButton,
		MINIMAL_SEARCH_LENGTH,
		DEFAULT_ICON_BACKGROUND,
		ENTER_PRESSED_EVENT,
	};
});
