/**
 * @module layout/ui/reaction-list/user-list/src/redux-list-item
 */
jn.define('layout/ui/reaction-list/user-list/src/redux-list-item', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { ReduxReactionView } = require('layout/ui/reaction-list/user-list/src/reaction');

	class ReduxReactionListItem extends Base
	{
		renderItemContent()
		{
			return ReduxReactionView({
				id: this.props.item?.id,
				reactionId: this.props.item?.reactionId,
				testId: this.props.testId,
				showBorder: this.props.item?.showBorder,
				showIcon: this.props.item?.showIcon,
			});
		}
	}

	module.exports = { ReduxReactionListItem };
});
