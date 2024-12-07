/**
 * @module intranet/invite-new/src/name-checker-item-avatar
 */
jn.define('intranet/invite-new/src/name-checker-item-avatar', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { EmptyAvatar } = require('layout/ui/user/empty-avatar');
	const { Indent } = require('tokens');

	class NameCheckerItemAvatar extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				firstName: props.firstName ?? '',
				secondName: props.secondName ?? '',
			};
		}

		/**
		 * @public
		 * @return {void}
		 */
		update({ firstName, secondName })
		{
			this.setState({
				firstName,
				secondName,
			});
		}

		render()
		{
			return EmptyAvatar({
				testId: this.testId,
				id: this.props.index,
				size: 36,
				name: `${this.state.firstName} ${this.state.secondName}`,
				additionalStyles: {
					marginRight: Indent.L.toNumber(),
				},
			});
		}

		get testId()
		{
			return 'name-checker-item-avatar';
		}
	}

	module.exports = { NameCheckerItemAvatar };
});
