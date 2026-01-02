/**
 * @module im/messenger/controller/dialog/lib/background/configuration
 */
jn.define('im/messenger/controller/dialog/lib/background/configuration', (require, exports, module) => {
	const { DialogBackgroundId } = require('im/messenger/const');

	/**
	 * @type {BackgroundConfigurationRecord}
	 */
	const BackgroundConfiguration = {
		light: {
			[DialogBackgroundId.aiAssistant]: {
				bottomColor: '#FFFFFF',
				gradientColors: [
					'#C0DCEB',
					'#C1DCEB',
					'#BBD2EE',
				],
				angle: 45,
			},
			[DialogBackgroundId.copilot]: {
				bottomColor: '#738AB2',
				gradientColors: [
					'#728AB3',
					'#748DB7',
					'#ABA7BE',
					'#BCB4C3',
				],
				angle: 45,
			},
			[DialogBackgroundId.collab]: {
				bottomColor: '#4CB968',
				gradientColors: [
					'#9BDA98',
					'#82D796',
					'#54C075',
					'#4CB968',
				],
				angle: 45,
			},
		},
		dark: {
			[DialogBackgroundId.aiAssistant]: {
				bottomColor: '#131313',
				gradientColors: [
					'#1A4752',
					'#003A71',
				],
				angle: 45,
			},
			[DialogBackgroundId.copilot]: {
				bottomColor: '#131313',
				gradientColors: [
					'#484B6EA8',
					'#48B300F5',
				],
				angle: 45,
			},
			[DialogBackgroundId.collab]: {
				bottomColor: '#1A1A1A',
				gradientColors: [
					'#6450964D',
					'#643A824B',
					'#6424703B',
					'#641E5D2F',
				],
				angle: 45,
			},
		},
	};

	module.exports = { BackgroundConfiguration };
});
