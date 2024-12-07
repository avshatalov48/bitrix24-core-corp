/**
 * Attention!
 * This file is generated automatically from the apptheme generator
 * Any manual changes to this file are not allowed.
 */

/**
 * @module tokens/src/typography
 */
jn.define('tokens/src/typography', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { TypographyEnum } = require('tokens/src/enums/typography-enum');

	/**
	 * @class Typography
	 * @extends {BaseEnum<Typography>}
	 */
	class Typography extends TypographyEnum
	{}

	Typography.h1 = new Typography('h1', AppTheme.typography.h1);
	Typography.h1Style = new Typography('h1Style', {
		fontSize: 25,
		fontWeight: '500',
	});
	Typography.h1Accent = new Typography('h1Accent', AppTheme.typography.h1Accent);
	Typography.h1AccentStyle = new Typography('h1AccentStyle', {
		fontSize: 25,
		fontWeight: '600',
	});
	Typography.h2 = new Typography('h2', AppTheme.typography.h2);
	Typography.h2Style = new Typography('h2Style', {
		fontSize: 21,
		fontWeight: '500',
	});
	Typography.h2Accent = new Typography('h2Accent', AppTheme.typography.h2Accent);
	Typography.h2AccentStyle = new Typography('h2AccentStyle', {
		fontSize: 21,
		fontWeight: '600',
	});
	Typography.h3 = new Typography('h3', AppTheme.typography.h3);
	Typography.h3Style = new Typography('h3Style', {
		fontSize: 19,
		fontWeight: '500',
	});
	Typography.h3Accent = new Typography('h3Accent', AppTheme.typography.h3Accent);
	Typography.h3AccentStyle = new Typography('h3AccentStyle', {
		fontSize: 19,
		fontWeight: '600',
	});
	Typography.h4 = new Typography('h4', AppTheme.typography.h4);
	Typography.h4Style = new Typography('h4Style', {
		fontSize: 17,
		fontWeight: '500',
	});
	Typography.h4Accent = new Typography('h4Accent', AppTheme.typography.h4Accent);
	Typography.h4AccentStyle = new Typography('h4AccentStyle', {
		fontSize: 17,
		fontWeight: '600',
	});
	Typography.h5 = new Typography('h5', AppTheme.typography.h5);
	Typography.h5Style = new Typography('h5Style', {
		fontSize: 15,
		fontWeight: '500',
	});
	Typography.h5Accent = new Typography('h5Accent', AppTheme.typography.h5Accent);
	Typography.h5AccentStyle = new Typography('h5AccentStyle', {
		fontSize: 15,
		fontWeight: '600',
	});
	Typography.text1 = new Typography('text1', AppTheme.typography.text1);
	Typography.body1Style = new Typography('body1Style', {
		fontSize: 19,
		fontWeight: '400',
	});
	Typography.text1Accent = new Typography('text1Accent', AppTheme.typography.text1Accent);
	Typography.body1AccentStyle = new Typography('body1AccentStyle', {
		fontSize: 19,
		fontWeight: '500',
	});
	Typography.text2 = new Typography('text2', AppTheme.typography.text2);
	Typography.body2Style = new Typography('body2Style', {
		fontSize: 17,
		fontWeight: '400',
	});
	Typography.text2Accent = new Typography('text2Accent', AppTheme.typography.text2Accent);
	Typography.body2AccentStyle = new Typography('body2AccentStyle', {
		fontSize: 17,
		fontWeight: '500',
	});
	Typography.text3 = new Typography('text3', AppTheme.typography.text3);
	Typography.body3Style = new Typography('body3Style', {
		fontSize: 16,
		fontWeight: '400',
	});
	Typography.text3Accent = new Typography('text3Accent', AppTheme.typography.text3Accent);
	Typography.body3AccentStyle = new Typography('body3AccentStyle', {
		fontSize: 16,
		fontWeight: '500',
	});
	Typography.text4 = new Typography('text4', AppTheme.typography.text4);
	Typography.body4Style = new Typography('body4Style', {
		fontSize: 15,
		fontWeight: '400',
	});
	Typography.text4Accent = new Typography('text4Accent', AppTheme.typography.text4Accent);
	Typography.body4AccentStyle = new Typography('body4AccentStyle', {
		fontSize: 15,
		fontWeight: '500',
	});
	Typography.text5 = new Typography('text5', AppTheme.typography.text5);
	Typography.body5Style = new Typography('body5Style', {
		fontSize: 13,
		fontWeight: '400',
	});
	Typography.text5Accent = new Typography('text5Accent', AppTheme.typography.text5Accent);
	Typography.body5AccentStyle = new Typography('body5AccentStyle', {
		fontSize: 13,
		fontWeight: '500',
	});
	Typography.text6 = new Typography('text6', AppTheme.typography.text6);
	Typography.body6Style = new Typography('body6Style', {
		fontSize: 12,
		fontWeight: '400',
	});
	Typography.text6Accent = new Typography('text6Accent', AppTheme.typography.text6Accent);
	Typography.body6AccentStyle = new Typography('body6AccentStyle', {
		fontSize: 12,
		fontWeight: '500',
	});
	Typography.text7 = new Typography('text7', AppTheme.typography.text7);
	Typography.body7Style = new Typography('body7Style', {
		fontSize: 10,
		fontWeight: '400',
	});
	Typography.text7Accent = new Typography('text7Accent', AppTheme.typography.text7Accent);
	Typography.body7AccentStyle = new Typography('body7AccentStyle', {
		fontSize: 10,
		fontWeight: '500',
	});
	Typography.textCapital = new Typography('textCapital', AppTheme.typography.textCapital);
	Typography.bodyCapitalStyle = new Typography('bodyCapitalStyle', {
		fontSize: 10,
		fontWeight: '500',
	});
	Typography.textCapitalAccent = new Typography('textCapitalAccent', AppTheme.typography.textCapitalAccent);
	Typography.bodyCapitalAccentStyle = new Typography('bodyCapitalAccentStyle', {
		fontSize: 10,
		fontWeight: '600',
	});

	module.exports = { Typography };
});
