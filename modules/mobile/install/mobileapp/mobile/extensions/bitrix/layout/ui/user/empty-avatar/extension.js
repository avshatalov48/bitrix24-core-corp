/**
 * @module layout/ui/user/empty-avatar
 */
jn.define('layout/ui/user/empty-avatar', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { lighten } = require('utils/color');

	const COLORS = [
		'#df532d',
		'#64a513',
		'#4ba984',
		'#4ba5c3',
		'#3e99ce',
		'#8474c8',
		'#1eb4aa',
		'#f76187',
		'#58cc47',
		'#ab7761',
		'#29619b',
		'#728f7a',
		'#ba9c7b',
		'#e8a441',
		'#556574',
		'#909090',
		'#5e5f5e',
	];

	const getColor = (id) => {
		return COLORS[id % COLORS.length];
	};

	// const SPECIAL_SYMBOLS_PATTERN = /[!"#$%&'()*,./:;<>?@[\\\]^`{|}~-]/;

	/**
	 * @param {string} name
	 * @return {string}
	 */
	const getFirstLetters = (name) => {
		let initials = '';

		const words = name.split(/[\s,]/);
		for (const word of words)
		{
			if (initials.length === 2)
			{
				return initials;
			}

			for (const letter of word)
			{
				// ToDo unify logic with web and messenger
				// if (
				// 	!SPECIAL_SYMBOLS_PATTERN.test(letter)
				// 	&& !WordSeparator.hasEmojiWord(letter)
				// )
				initials += letter;
				break;
			}
		}

		return initials;
	};

	const isGradientWithTextSupported = Application.getPlatform() === 'android' || Application.getApiVersion() >= 52;

	const getBackgroundColorStyles = (id) => {
		const backgroundColor = getColor(id);

		if (isGradientWithTextSupported)
		{
			const startColor = lighten(backgroundColor, 0.4);
			const middleColor = lighten(backgroundColor, 0.2);

			return {
				backgroundColor,
				backgroundColorGradient: {
					start: startColor,
					middle: middleColor,
					end: backgroundColor,
					angle: 90,
				},
			};
		}

		return { backgroundColor };
	};

	/**
	 * @function EmptyAvatar
	 * @param {number} id - user id
	 * @param {string} name - user full name or login
	 * @param {number} [size]
	 * @param {object} [additionalStyles]
	 * @param {function} [onClick]
	 * @param {string} [testId]
	 * @return {View}
	 */
	const EmptyAvatar = ({
		id,
		name,
		size = 24,
		additionalStyles = {},
		onClick,
		testId,
	}) => View(
		{
			onClick,
			testId,
			style: {
				...getBackgroundColorStyles(id),
				width: size,
				height: size,
				borderRadius: size / 2,
				alignContent: 'center',
				justifyContent: 'center',
				...additionalStyles,
			},
		},
		Text({
			style: {
				fontSize: size / 2,
				alignSelf: 'center',
				color: AppTheme.colors.base8,
			},
			text: getFirstLetters(name).toLocaleUpperCase(env.languageId),
		}),
	);

	module.exports = { EmptyAvatar };
});
