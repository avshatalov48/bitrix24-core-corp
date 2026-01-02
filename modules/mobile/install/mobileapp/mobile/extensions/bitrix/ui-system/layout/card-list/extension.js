/**
 * @module ui-system/layout/card-list
 */
jn.define('ui-system/layout/card-list', (require, exports, module) => {
	const { Component } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { ScrollView } = require('layout/ui/scroll-view');

	/**
	 * @typedef {Object} CardListProps
	 * @property {boolean} [divided]
	 * @property {boolean} [withScroll=true]
	 * @property {boolean} [horizontal=false]
	 * @property {...Card} children
	 *
	 * @class CardList
	 */
	class CardList extends LayoutComponent
	{
		render()
		{
			return this.withScroll()
				? this.renderScroll()
				: this.renderView();
		}

		renderView()
		{
			const style = {};

			if (this.isHorizontal())
			{
				style.flexDirection = 'row';
			}

			return View(
				mergeImmutable(
					{
						style,
					},
					this.getRestProps(),
				),
				...this.getChildren(),
			);
		}

		renderScroll()
		{
			return ScrollView(
				mergeImmutable(
					{
						style: {
							height: '100%',
						},
						horizontal: this.isHorizontal(),
					},
					this.getRestProps(),
				),
				...this.getChildren(),
			);
		}

		getRestProps()
		{
			const { horizontal, withScroll, children, ...restProps } = this.props;

			return restProps;
		}

		getChildren()
		{
			const {
				divided = true,
				children,
			} = this.props;

			return children.map((child, index) => {
				const isFirst = index === 0;

				if (!divided || isFirst)
				{
					return child;
				}

				return this.renderDividedWrapper(child);
			});
		}

		renderDividedWrapper(child)
		{
			const marginType = this.isHorizontal() ? 'marginLeft' : 'marginTop';

			return View(
				{
					style: {
						[marginType]: Component.cardListGap.toNumber(),
					},
				},
				child,
			);
		}

		withScroll()
		{
			const { withScroll = true } = this.props;

			return Boolean(withScroll);
		}

		isHorizontal()
		{
			const { horizontal = false } = this.props;

			return Boolean(horizontal);
		}
	}

	CardList.defaultProps = {
		divided: true,
		withScroll: true,
		horizontal: false,
	};

	CardList.propTypes = {
		divided: PropTypes.bool,
		withScroll: PropTypes.bool,
		horizontal: PropTypes.bool,
	};

	module.exports = {
		/**
		 * @param {CardListProps} props
		 * @param {Object[]} children
		 */
		CardList: (props, ...children) => new CardList({ ...props, children }),
	};
});
