/**
 * @module stafftrack/department-statistics/table-user
 */
jn.define('stafftrack/department-statistics/table-user', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { Avatar } = require('layout/ui/user/avatar');

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
				id: user.id,
				name: user.name,
				image: user.avatar,
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
