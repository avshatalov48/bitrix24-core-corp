/**
 * @module im/messenger/controller/participants-list/view
 */
jn.define('im/messenger/controller/participants-list/view', (require, exports, module) => {

	const { List } = require('im/messenger/lib/ui/base/list');
	class ParticipantsListView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}
		render()
		{
			return View(
				{},
				new List(
					{
						itemList: this.props.itemList,
						onItemSelected: (itemData) => this.props.onItemSelected(itemData),
					}
				)
			);
		}
	}

	module.exports = { ParticipantsListView };
});