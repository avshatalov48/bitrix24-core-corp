/**
 * @module intranet/simple-list/items/user-redux
 */
jn.define('intranet/simple-list/items/user-redux', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { UserContentView } = require('intranet/simple-list/items/user-redux/user-content');

	class User extends Base
	{
		renderItemContent()
		{
			return UserContentView({
				id: this.props.item.id,
				testId: this.props.testId,
				customStyles: this.props.customStyles,
				showBorder: this.props.item.showBorder,
				canInvite: this.props.item.canInvite,
			});
		}
	}

	module.exports = { User };
});
