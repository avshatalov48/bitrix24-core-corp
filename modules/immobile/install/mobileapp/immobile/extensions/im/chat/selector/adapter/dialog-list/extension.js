/**
 * @module im/chat/selector/adapter/dialog-list
 */
jn.define('im/chat/selector/adapter/dialog-list', (require, exports, module) =>
{
	class SelectorDialogListAdapter extends SelectorListAdapter
	{
		onSearchItemSelected(data)
		{
			if (data.id === 'show-more')
			{
				this.selectorListener('onClickShowMore', data);

				return;
			}

			if (data.sectionCode === 'network')
			{
				if (data.id === 'show-network')
				{
					this.selectorListener('onClickShowNetwork', data);
					return;
				}

				this.selectorListener('onSearchNetworkItemSelected', data);
				return;
			}
			super.onSearchItemSelected(data);
		}

		searchSectionButtonClick(data)
		{
			this.selectorListener('onSearchSectionButtonClick', data);
		}

	}

	module.exports = { SelectorDialogListAdapter };
});