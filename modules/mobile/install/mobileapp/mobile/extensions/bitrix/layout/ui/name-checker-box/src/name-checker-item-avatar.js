/**
 * @module layout/ui/src/name-checker-item-avatar
 */
jn.define('layout/ui/src/name-checker-item-avatar', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Avatar, AvatarShape } = require('ui-system/blocks/avatar');
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

		get testId()
		{
			return 'name-checker-item-avatar';
		}

		get avatarEntityType()
		{
			return this.props.avatarEntityType;
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
			return Avatar({
				testId: this.testId,
				size: 36,
				accent: true,
				shape: AvatarShape.CIRCLE,
				entityType: this.avatarEntityType,
				useLetterImage: true,
				name: `${this.state.firstName} ${this.state.secondName}`,
				style: {
					marginRight: Indent.L.toNumber(),
				},
			});
		}
	}

	module.exports = { NameCheckerItemAvatar };
});
