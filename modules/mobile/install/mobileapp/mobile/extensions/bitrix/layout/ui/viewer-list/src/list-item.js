/**
 * @module layout/ui/viewer-list/src/list-item
 */
jn.define('layout/ui/viewer-list/src/list-item', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { ListItem } = require('layout/ui/user-list');

	class ViewerListItem extends Base
	{
		constructor(props)
		{
			super(props);
			this.layoutWidget = null;
		}

		renderItemContent()
		{
			return View(
				{},
				this.renderUser(this.props.item),
			);
		}

		renderUser(user)
		{
			const { id, showBorder } = user;
			const testId = `${this.props.testId}_USER_${id}`;

			return new ListItem({
				user,
				showBorder,
				testId,
				parentWidget: this.props.layout,
				renderCustomDescription: this.props.params.renderCustomDescription,
			});
		}
	}

	module.exports = { ViewerListItem };
});
