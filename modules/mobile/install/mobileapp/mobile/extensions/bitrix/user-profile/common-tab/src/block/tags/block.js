/**
 * @module user-profile/common-tab/src/block/tags/block
 */
jn.define('user-profile/common-tab/src/block/tags/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { Loc } = require('loc');
	const { ViewMode } = require('user-profile/common-tab/src/block/base-view');
	const { Tags } = require('user-profile/common-tab/src/block/tags/view');
	const { Type } = require('type');

	class TagsBlock extends BaseBlock
	{
		isAvailable()
		{
			return Boolean(this.props.isEditMode || Type.isArrayFilled(this.props.items));
		}

		prepareProps(commonTabProps)
		{
			const { tags, ownerId, isEditMode, onChange, cacheManager } = commonTabProps ?? {};

			return {
				ownerId,
				isEditMode,
				onChange,
				cacheManager,
				items: tags,
				testId: 'tags-card',
			};
		}

		getContentClass()
		{
			return Tags;
		}

		getViewMode()
		{
			return ViewMode.FULL_WIDTH;
		}

		getTitle()
		{
			if (this.props.isEditMode)
			{
				return Loc.getMessage('M_PROFILE_TAGS_EDIT_MODE_TITLE');
			}

			return Loc.getMessage('M_PROFILE_TAGS_TITLE');
		}
	}

	module.exports = {
		TagsBlock,
	};
});
