/**
 * @module layout/ui/kanban/toolbar/stage-summary
 */
jn.define('layout/ui/kanban/toolbar/stage-summary', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Filler } = require('layout/ui/kanban/toolbar/filler');
	const Title = (text) => Text({
		style: {
			color: AppTheme.colors.base4,
			fontSize: 14,
			fontWeight: '500',
			marginBottom: Application.getPlatform() === 'android' ? 0 : 2,
		},
		text,
		ellipsize: 'end',
		numberOfLines: 1,
	});
	const Body = (text) => Text({
		style: {
			color: AppTheme.colors.base2,
			fontSize: 14,
			fontWeight: '500',
			marginTop: Application.getPlatform() === 'android' ? 1 : 3,
		},
		text,
		ellipsize: 'end',
		numberOfLines: 1,
	});

	const StageSummary = ({ title, text, useFiller = false }) => View(
		{
			style: {
				flex: 4,
				paddingRight: 10,
			},
		},
		Title(title),
		(useFiller ? Filler(78) : Body(text)),
	);

	module.exports = { StageSummary };
});
