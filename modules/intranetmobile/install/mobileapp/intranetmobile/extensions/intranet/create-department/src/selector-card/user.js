/**
 * @module intranet/create-department/src/selector-card/user
 */

jn.define('intranet/create-department/src/selector-card/user', (require, exports, module) => {
	const { BaseSelectorCard } = require('intranet/create-department/src/selector-card/base');
	const { SocialNetworkUserSelector } = require('selector/widget/entity/socialnetwork/user');

	/**
	 * @class SelectorCard
	 */
	class UserSelectorCard extends BaseSelectorCard
	{
		onClick = () => {
			this.openUserSelector();
		};

		openUserSelector()
		{
			const { parentWidget, selectedId, onSelected, onViewHidden, editable } = this.props;
			if (!editable)
			{
				return;
			}

			SocialNetworkUserSelector.make({
				provider: {
					options: {},
				},
				initSelectedIds: selectedId ? [selectedId] : null,
				widgetParams: {
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: onSelected,
					onViewHidden,
				},
			}).show({}, parentWidget ?? PageManager);
		}
	}

	module.exports = {
		/**
		 * @param {SelectorCardProps} props
		 * @returns {UserSelectorCard}
		 */
		UserSelectorCard: (props) => new UserSelectorCard(props),
	};
});
