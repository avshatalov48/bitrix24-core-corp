/**
 * @module layout/ui/jn-list/src/skeleton
 */
jn.define('layout/ui/jn-list/src/skeleton', (require, exports, module) => {
	const { Color, Indent, Corner } = require('tokens');
	const { Line, Circle } = require('utils/skeleton');
	const { Random } = require('utils/random');

	/**
	 * @typedef {Object} JnListSkeletonProps
	 * @property {number} itemsCount
	 * @property {boolean} showSkeletonOnlyForList

	 * @class JnListSkeleton
	 */
	class JnListSkeleton extends LayoutComponent
	{
		render()
		{
			const {
				showSkeletonOnlyForList,
				itemsCount = 10,
			} = this.props;

			const items = [];
			for (let i = 0; i < itemsCount; i++)
			{
				items.push(this.renderListItemSkeleton());
			}

			return View(
				{
					style: {
						flexDirection: 'column',
						width: '100%',
						height: '100%',
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				!showSkeletonOnlyForList && this.renderSearchBarSkeleton(),
				!showSkeletonOnlyForList && this.renderScopesSkeleton(),
				...items,
			);
		}

		renderListItemSkeleton()
		{
			const textWidth = Random.getInt(130, 220);

			return View(
				{
					style: {
						height: 70,
						flexDirection: 'row',
						paddingHorizontal: Indent.XL2.toNumber(),
						alignItems: 'center',
					},
				},
				Circle(40),
				View({
					style: {
						paddingLeft: Indent.XL.toNumber(),
					},
				}),
				Line(textWidth, 22, 0, 0, 4),
				View(
					{
						style: {
							flex: 1,
							justifyContent: 'flex-end',
							alignItems: 'flex-end',
						},
					},
					Line(24, 22, 0, 0, 4),
				),
			);
		}

		renderSearchBarSkeleton()
		{
			return View(
				{
					style: {
						width: '100%',
						paddingHorizontal: Indent.XL2.toNumber(),
						paddingVertical: Indent.XS.toNumber(),
					},
				},
				Line('100%', 36, 0, 0, Corner.M.toNumber()),
			);
		}

		renderScopesSkeleton()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						width: '100%',
						height: 50,
						marginTop: Indent.S.toNumber(),
						paddingLeft: Indent.XL2.toNumber(),
						paddingRight: Indent.XS.toNumber(),
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.#renderShimmerBullet(100),
				this.#renderShimmerBullet(120),
				this.#renderShimmerBullet(80),
			);
		}

		#renderShimmerBullet(width, paddingRight = Indent.M.toNumber())
		{
			return View(
				{
					style: {
						paddingRight,
					},
				},
				Line(width, 36, 0, 0, Corner.M.toNumber()),
			);
		}
	}

	module.exports = {
		/**
		 * @param {JnListSkeletonProps} props
		 * @returns {JnListSkeleton}
		 */
		JnListSkeleton: (props) => new JnListSkeleton(props),
	};
});
