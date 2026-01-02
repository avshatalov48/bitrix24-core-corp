/**
 * @module selector/widget/entity/intranet/profile/tag/provider
 */

jn.define('selector/widget/entity/intranet/profile/tag/provider', (require, exports, module) => {
	const { CommonSelectorProvider } = require('selector/providers/common');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Icon } = require('ui-system/blocks/icon');
	const { Color } = require('tokens');

	const SEARCH_ACTION = 'mobile.Profile.searchTags';

	/**
	 * @class ProfileTagSelectorProvider
	 */
	class ProfileTagSelectorProvider extends CommonSelectorProvider
	{
		fetchRecent()
		{
			new RunActionExecutor(
				SEARCH_ACTION,
				{
					ownerId: this.options.ownerId,
				},
			)
				.enableJson()
				.setCacheTtl(84000)
				.setHandler(this.handleResent)
				.setCacheHandler(this.handleResent)
				.call(true)
			;
		}

		getSearchAction()
		{
			return SEARCH_ACTION;
		}

		getSearchActionConfig(query)
		{
			return {
				json: {
					ownerId: this.options.ownerId,
					searchString: query,
				},
			};
		}

		getItemsFromSearchResponse(response)
		{
			return response.data.tags;
		}

		handleResent = (response) => {
			let { tags } = response.data;
			const selectedItems = this.options.selectedItems;

			tags = tags.filter((tag) => {
				return !selectedItems.some((selectedItem) => selectedItem.name === tag.name);
			});

			const items = tags;
			if (items.length > 0)
			{
				this.addItems(items, true);
			}

			const result = this.prepareItems(tags);
			this.cache.save(result, 'recent', { saveDisk: true });

			this.listener.onRecentResult(result, false);
			this.recentLoaded = true;
		};

		prepareItemForDrawing(tag)
		{
			return {
				title: tag.name,
				sectionCode: 'common',
				height: 64,
				disabled: false,
				id: tag.name,
				useLetterImage: false,
				params: {
					name: tag.name,
				},
				avatar: {
					type: 'square',
					backgroundColor: '#fff',
					hideOutline: true,
					placeholder: {
						type: 'svg',
						backgroundColor: Color.accentSoftBlue2.toHex(),
						svg: {
							named: Icon.TAG.getIconName(),
							tintColor: Color.accentMainPrimaryalt.toHex(),
						},
					},
				},
			};
		}
	}

	module.exports = { ProfileTagSelectorProvider };
});
