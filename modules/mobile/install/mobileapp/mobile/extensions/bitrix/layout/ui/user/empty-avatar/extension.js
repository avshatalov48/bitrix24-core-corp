/**
 * @module layout/ui/user/empty-avatar
 */
jn.define('layout/ui/user/empty-avatar', (require, exports, module) => {
	const { Color } = require('tokens');
	const { lighten } = require('utils/color');
	const { Type } = require('type');
	const { makeLibraryImagePath } = require('asset-manager');

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

	const ALLOWED_CHARACTERS_PATTERN = /^[\dA-Za-z\u00C0-\u024F\u0400-\u04FF]+$/;

	/**
	 * @param {string} name
	 * @return {string}
	 */
	const getFirstLetters = (name) => {
		if (!Type.isStringFilled(name))
		{
			return '';
		}

		let initials = '';

		const words = name.split(/[\s,]/);
		for (const word of words)
		{
			if (initials.length === 2)
			{
				return initials;
			}

			const firstLetter = word[0];
			if (firstLetter && ALLOWED_CHARACTERS_PATTERN.test(firstLetter))
			{
				initials += firstLetter;
			}
		}

		return initials;
	};

	const getBackgroundColorStyles = (id) => {
		const backgroundColor = getColor(id);
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
	};

	/**
	 * @function EmptyAvatar
	 * @param {number} id - user id
	 * @param {string} name - user full name or login
	 * @param {number} [size]
	 * @param {object} [additionalStyles]
	 * @param {function} [onClick]
	 * @param {string} [testId]
	 */
	const EmptyAvatar = ({
		id,
		name,
		size = 24,
		additionalStyles = {},
		onClick,
		testId,
	}) => {
		const imageUri = makeLibraryImagePath('person.svg', 'empty-avatar');

		if (Type.isNil(name))
		{
			return View(
				{
					onClick,
					testId,
					style: {
						width: size,
						height: size,
						borderRadius: size / 2,
						alignContent: 'center',
						justifyContent: 'center',
						...additionalStyles,
					},
				},
				Image({
					style: {
						flex: 1,
					},
					svg: {
						uri: imageUri,
					},
				}),
			);
		}

		return UserLetters({
			id,
			size,
			name,
			testId,
			onClick,
			style: {
				...additionalStyles,
				width: size,
				height: size,
				borderRadius: size / 2,
			},
		});
	};

	/**
	 * @function UserLetters;
	 * @param {string} id
	 * @param {string} size
	 * @param {string} name
	 * @param {Object} [style]
	 * @param {Object} restProps
	 */
	const UserLetters = ({ id, size, name, style, ...restProps }) => {
		const firstLetters = getFirstLetters(name).toLocaleUpperCase(env.languageId);

		if (!firstLetters)
		{
			return null;
		}

		return View(
			{
				...restProps,
				style: {
					alignContent: 'center',
					justifyContent: 'center',
					...getBackgroundColorStyles(id),
					...style,
				},
			},
			Text({
				style: {
					fontSize: size / 2,
					alignSelf: 'center',
					color: Color.baseWhiteFixed.toHex(),
				},
				text: firstLetters,
			}),
		);
	};

	module.exports = { EmptyAvatar, UserLetters, getColor, getBackgroundColorStyles, getFirstLetters };
});
