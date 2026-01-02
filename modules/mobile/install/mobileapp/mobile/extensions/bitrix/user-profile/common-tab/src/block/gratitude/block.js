/**
 * @module user-profile/common-tab/src/block/gratitude/block
 */
jn.define('user-profile/common-tab/src/block/gratitude/block', (require, exports, module) => {
	const { BaseBlock } = require('user-profile/common-tab/src/block/base-block');
	const { Gratitude } = require('user-profile/common-tab/src/block/gratitude/view');
	const { gratitudesUpserted } = require('statemanager/redux/slices/gratitude');
	const { dispatch } = require('statemanager/redux/store');
	const { Loc } = require('loc');
	const { ViewMode } = require('user-profile/common-tab/src/block/base-view');
	const { GratitudeListManager } = require('layout/ui/gratitude-list');

	class GratitudeBlock extends BaseBlock
	{
		prepareProps(commonTabData)
		{
			const { gratitude, ownerId, efficiency, isEditMode, parentWidget } = commonTabData ?? {};

			if (gratitude?.items?.length > 0)
			{
				dispatch(gratitudesUpserted(gratitude.items));
			}

			return {
				ownerId,
				parentWidget,
				efficiency,
				isEditMode,
				gratitudeTotalCount: gratitude?.totalCount,
				gratitudes: gratitude?.items,
				testId: 'gratitude-card',
				onClick: this.onClick.bind(this),
				style: {
					minHeight: 96,
				},
			};
		}

		isAvailable()
		{
			return !this.props.isEditMode;
		}

		getContentClass()
		{
			return Gratitude;
		}

		getViewMode()
		{
			const { efficiency } = this.props;
			if (efficiency)
			{
				return ViewMode.HALF_WIDTH;
			}

			return ViewMode.FULL_WIDTH;
		}

		onClick()
		{
			const { ownerId, parentWidget } = this.props;

			void GratitudeListManager.openList({
				ownerId,
				parentWidget,
			});
		}

		getTitle()
		{
			return Loc.getMessage('M_PROFILE_GRATITUDE_TITLE');
		}
	}

	module.exports = {
		GratitudeBlock,
	};
});
