(() => {

	/**
	 * @class ListItems.Terminal
	 */
	class Terminal extends ListItems.Base
	{
		prepareActions(actions)
		{
			const { isPaid, permissions } = this.props.item.data;

			if (!permissions.delete || isPaid)
			{
				const deleteAction = this.findAction(actions, 'delete');
				deleteAction.isDisabled = true;
			}
		}

		findAction(actions, id)
		{
			return actions.find(action => action.id === id);
		}
	}

	this.ListItems = this.ListItems || {};
	this.ListItems.Terminal = Terminal;
})();
