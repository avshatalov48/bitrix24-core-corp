/**
 * @module user-profile/common-tab/src/block/gratitude/view
 */
jn.define('user-profile/common-tab/src/block/gratitude/view', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');
	const { CircleStack } = require('utils/skeleton');
	const { makeLibraryImagePath } = require('asset-manager');
	const { GratitudeIcon } = require('assets/icons');
	const { Type } = require('type');
	const { selectGratitudesByOwnerId } = require('statemanager/redux/slices/gratitude');
	const { connect } = require('statemanager/redux/connect');
	const { createTestIdGenerator } = require('utils/test');
	const { ViewMode } = require('user-profile/common-tab/src/block/base-view');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { formatQuantityWithLimit } = require('utils/number');

	const IMAGE_SIZE = 34;

	class Gratitude extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.getTestId = createTestIdGenerator({
				prefix: 'gratitude',
				context: this,
			});
		}

		/**
		 * @returns {Array|null}
		 */
		get items()
		{
			return this.props.gratitudes ?? null;
		}

		/**
		 * @returns {number}
		 */
		get totalQuantity()
		{
			const { gratitudeTotalCount = 0, gratitudes = [] } = this.props;

			return Math.max(gratitudes.length, gratitudeTotalCount);
		}

		/**
		 * @returns {number}
		 */
		get quantityForPreview()
		{
			const HALF_WIDTH_QUANTITY = 3;
			const FULL_WIDTH_QUANTITY = 8;

			if (this.viewMode === ViewMode.HALF_WIDTH)
			{
				return HALF_WIDTH_QUANTITY;
			}

			return FULL_WIDTH_QUANTITY;
		}

		/**
		 * @returns {Array}
		 */
		get badgePreview()
		{
			return this.items
				.slice(0, this.quantityForPreview)
				.map((badge) => {
					const badgeImage = GratitudeIcon.getSvgUriByName(badge.name);
					const testId = GratitudeIcon.getTestIdByName(badge.name);

					return {
						...badge,
						testId: testId ?? null,
						svgUri: badgeImage ?? null,
					};
				});
		}

		/**
		 * @returns {string}
		 */
		get viewMode()
		{
			const { efficiency } = this.props;
			if (efficiency)
			{
				return ViewMode.HALF_WIDTH;
			}

			return ViewMode.FULL_WIDTH;
		}

		render()
		{
			if (!this.items)
			{
				return this.renderShimmer();
			}

			if (!Type.isArrayFilled(this.items))
			{
				return this.renderEmptyState();
			}

			return this.renderContent();
		}

		renderShimmer()
		{
			return View(
				{
					testId: this.getTestId('shimmer'),
				},
				CircleStack({ count: 4, size: 34, offsetRatio: 0.8 }),
			);
		}

		renderEmptyState()
		{
			const isHalfWidth = this.viewMode === ViewMode.HALF_WIDTH;
			const imageName = isHalfWidth ? 'small-empty-state.svg' : 'big-empty-state.svg';

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				Image(
					{
						style: {
							width: isHalfWidth ? 85 : 168,
							height: 40,
							marginRight: isHalfWidth ? Indent.L.toNumber() : 0,
						},
						svg: {
							uri: makeLibraryImagePath(imageName, 'gratitude'),
						},
					},
				),
				IconView({
					icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
					color: Color.base4,
					size: 20,
					style: {
						alignSelf: 'flex-end',
					},
				}),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						testId: this.getTestId('content'),
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
					},
				},
				this.renderElementsStack(),
				this.renderTotalQuantity(),
			);
		}

		renderElementsStack()
		{
			const elements = this.getElements();

			return ElementsStack(
				{
					testId: this.getTestId('element-stack'),
					direction: ElementsStackDirection.RIGHT,
					offset: Indent.L.toNumber(),
					radius: null,
					indent: null,
				},
				...elements,
			);
		}

		renderTotalQuantity()
		{
			const quantity = this.totalQuantity;
			const shouldRenderIcon = quantity <= 99;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignSelf: 'flex-end',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Text4({
					testId: this.getTestId(`remaining-quantity-${quantity}`),
					color: Color.base4,
					text: formatQuantityWithLimit(quantity),
					style: {
						marginRight: shouldRenderIcon ? -4 : 0,
						alignSelf: 'flex-end',
					},
				}),
				shouldRenderIcon && IconView({
					icon: Icon.CHEVRON_TO_THE_RIGHT_SIZE_S,
					color: Color.base4,
					size: 20,
				}),
			);
		}

		getElements()
		{
			if (this.badgePreview)
			{
				return this.badgePreview?.map((badge) => {
					return View({
						testId: badge?.testId,
						style: {
							backgroundImageSvgUrl: badge?.svgUri,
							backgroundResizeMode: 'center',
							width: IMAGE_SIZE,
							height: IMAGE_SIZE,
						},
					});
				});
			}

			return [];
		}
	}

	const mapStateToProps = (state, ownProps) => {
		const gratitudes = selectGratitudesByOwnerId(state, ownProps.ownerId);

		return {
			gratitudes,
		};
	};

	module.exports = {
		Gratitude: connect(mapStateToProps)(Gratitude),
		GratitudeClass: Gratitude,
	};
});
