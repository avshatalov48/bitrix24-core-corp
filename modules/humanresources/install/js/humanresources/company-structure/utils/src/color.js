const optionColor = Object.freeze({
	paletteBlue50: {
		tokenClass: '--ui-color-palette-blue-50',
		color: '#2FC6F6',
	},
	paletteGreen55: {
		tokenClass: '--ui-color-palette-green-55',
		color: '#95C500',
	},
	paletteRed40: {
		tokenClass: '--ui-color-palette-red-40',
		color: '#FF9A97',
	},
	accentAqua: {
		tokenClass: '--ui-color-accent-aqua',
		color: '#55D0E0',
	},
	accentTurquoise: {
		tokenClass: '--ui-color-accent-turquoise',
		color: '#05b5ab',
	},
	paletteOrange40: {
		tokenClass: '--ui-color-palette-orange-40',
		color: '#FFC34D',
	},
	lightBlue: {
		tokenClass: '--ui-color-accent-light-blue',
		color: '#559be6',
	},
});

export const getColorCode = (colorKey) => {
	const colorOption = optionColor[colorKey];
	if (colorOption)
	{
		return getComputedStyle(document.body).getPropertyValue(colorOption.tokenClass) || colorOption.color;
	}

	return null;
};
