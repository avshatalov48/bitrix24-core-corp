/**
 * @module layout/ui/reaction-list/user-list/src/list-item
 */
jn.define('layout/ui/reaction-list/user-list/src/list-item', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { ReactionView } = require('layout/ui/reaction-list/user-list/src/reaction');

	class ReactionListItem extends Base
	{
		renderItemContent()
		{
			return ReactionView({
				id: this.props.item?.id,
				reactionId: this.props.item?.reactionId,
				testId: this.props.testId,
				showBorder: this.props.item?.showBorder,
				showIcon: this.props.item?.showIcon,
				user: this.props.item?.user,
				description: this.props.item?.description,
			});
		}
	}

	module.exports = { ReactionListItem };
});
