/**
 * @module stafftrack/department-statistics/table-user
 */
jn.define('stafftrack/department-statistics/table-user', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { Avatar } = require('ui-system/blocks/avatar');

	const TableUser = (user) => View(
		{
			style: {
				flexDirection: 'row',
				flexShrink: 1,
			},
		},
		View(
			{
				style: {
					borderRadius: 24,
				},
			},
			Avatar({
				testId: `stafftrack-department-statistics-user-avatar-${user.id}`,
				id: user.id,
				name: user.name,
				uri: user.avatar,
				size: 24,
			}),
		),
		Text2({
			testId: `stafftrack-department-statistics-user-${user.id}`,
			text: user.name,
			color: Color.base1,
			numberOfLines: 1,
			ellipsize: 'end',
			style: {
				marginLeft: Indent.M.toNumber(),
				flexShrink: 1,
			},
		}),
	);

	module.exports = { TableUser };
});
