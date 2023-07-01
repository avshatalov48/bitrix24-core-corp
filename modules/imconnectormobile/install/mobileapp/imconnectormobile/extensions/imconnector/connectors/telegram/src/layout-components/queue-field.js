/**
 * @module imconnector/connector/telegram/layout-components/queue-field
 */
jn.define('imconnector/connector/telegram/layout-components/queue-field', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { UserField, UserFieldMode } = require('layout/ui/fields/user');
	class QueueField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state.queue = props.queue;
		}

		render()
		{
			return View(
				{
					style: {
						borderBottomColor: '#A8ADB4',
						borderBottomWidth: 0.5,
						marginBottom: 6,
					},
				},
				UserField({
					readOnly: this.props.readOnly,
					showEditIcon: true,
					title: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_QUEUE_FIELD_TITLE'),
					multiple: true,
					value: this.state.queue.map((user) => user.id),
					config: {
						mode: UserFieldMode.ICONS,
						provider: {
							options: {
								intranetUsersOnly: true,
							},
							context: 'IMOL_QUEUE_USERS',
						},
						entityList: this.state.queue.map((user) => {
							return {
								id: user.id,
								title: user.name,
								imageUrl: (
									!Type.isString(user.icon)
									|| !Type.isStringFilled(user.icon)
									|| user.icon.includes('default_avatar.png')
										? null
										: user.icon
								),
								customData: {
									position: user.workPosition,
								},
							};
						}),
						reloadEntityListFromProps: true,
						parentWidget: this.props.parentWidget,
						selectorTitle: Loc.getMessage('IMCONNECTORMOBILE_TELEGRAM_EDIT_QUEUE_SELECTOR_TITLE'),
					},
					testId: 'queue',
					onChange: (userIds, usersData) => {
						const queue = usersData.map((user) => {
							return {
								id: user.id,
								name: user.title,
								icon: user.imageUrl,
								workPosition: user.customData.position,
							};
						});

						const newQueue = this.state.queue.map((user) => user.id);
						const oldQueue = queue.map((user) => user.id);
						const difference = newQueue
							.filter((id) => !oldQueue.includes(id))
							.concat(oldQueue.filter((id) => !newQueue.includes(id)))
						;

						if (difference.length > 0)
						{
							this.props.onChange(queue);
							this.setState({ queue });
						}
					},
				}),
			);
		}
	}

	module.exports = { QueueField: (props) => new QueueField(props) };
});
