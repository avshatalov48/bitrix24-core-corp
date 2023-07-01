/**
 * @module layout/ui/product-grid/components/inline-sku-tree
 */
jn.define('layout/ui/product-grid/components/inline-sku-tree', (require, exports, module) => {

	const { Loc } = require('loc');
	const { get, isArray } = require('utils/object');

	class InlineSkuTree extends LayoutComponent
	{
		/**
		 * @param {{
		 *	OFFERS_PROP: {object},
		 *	SELECTED_VALUES: {object},
		 *	editable: {boolean},
		 *	onClick: {function}
		 * }} props
		 */
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						marginBottom: 12,
						display: this.props.OFFERS_PROP ? 'flex' : 'none',
					}
				},
				this.renderProps(
					this.renderPictureProps(),
					this.renderTextProps(),
				),
				this.renderChangeButton(),
			);
		}

		renderProps(...children)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'flex-start',
						flexShrink: 1,
					}
				},
				...children,
			);
		}

		renderPictureProps()
		{
			const pictures = this.getPicturePropertyValues();

			if (!pictures.length)
			{
				return null;
			}

			const prepareImagePath = (src) => {
				src = src.startsWith('/') ? currentDomain + src : src;
				return encodeURI(src);
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					}
				},
				...pictures.flatMap((src, index, arr) => [
					Image({
						style: {
							width: 14,
							height: 14,
							borderRadius: 2,
							marginRight: 6,
							borderWidth: 1,
							borderColor: '#e6e7e9',
						},
						resizeMode: 'cover',
						uri: prepareImagePath(src),
					}),
				])
			);
		}

		renderTextProps()
		{
			const pictures = this.getPicturePropertyValues();
			const textValues = this.getTextPropertyValues();

			if (!pictures.length && !textValues.length)
			{
				return Text({
					text: Loc.getMessage('PRODUCT_GRID_CONTROL_INLINE_SKU_TREE_VARIATION_NOT_SELECTED'),
					ellipsize: 'end',
					numberOfLines: 1,
					style: {
						color: '#525C69',
						fontSize: 16,
						width: 200,
					}
				});
			}

			return Text({
				text: textValues.join(', '),
				ellipsize: 'end',
				numberOfLines: 1,
				style: {
					color: '#525C69',
					fontSize: 16,
					width: 200,
				}
			});
		}

		renderChangeButton()
		{
			if (!this.props.editable)
			{
				return null;
			}

			return View(
				{
					style: {
						paddingLeft: 4,
						marginTop: 2,
					},
					onClick: () => this.onChangeSku(),
				},
				Text({
					text: Loc.getMessage('PRODUCT_GRID_CONTROL_INLINE_SKU_TREE_MAKE_CHANGE'),
					style: {
						fontSize: 13,
						color: '#A8ADB4',
						textDecorationLine: 'underline',
					}
				})
			);
		}

		onChangeSku()
		{
			if (this.props.onChangeSku)
			{
				return this.props.onChangeSku(this);
			}
		}

		/**
		 * @returns {String[]}
		 */
		getPicturePropertyValues()
		{
			const filterPictureProps = (item) => item.SHOW_MODE === 'PICT';
			const usePictureSrc = (property) => get(property, 'PICT.SRC', null);

			return this.getFlattenPropertyValues(filterPictureProps, usePictureSrc);
		}

		/**
		 * @returns {String[]}
		 */
		getTextPropertyValues()
		{
			const filterTextProps = (item) => item.SHOW_MODE !== 'PICT';
			const usePropertyName = (property) => property.NAME || null;

			return this.getFlattenPropertyValues(filterTextProps, usePropertyName);
		}

		/**
		 * @param {Function} propertyTypeFilter
		 * @param {Function} valueFormatter
		 * @returns {String[]}
		 */
		getFlattenPropertyValues(propertyTypeFilter, valueFormatter)
		{
			const allProps = get(this.props, 'OFFERS_PROP', {});
			const selectedValues = get(this.props, 'SELECTED_VALUES', {});
			const filteredProps = Object.values(allProps).filter(propertyTypeFilter);

			if (!filteredProps.length)
			{
				return [];
			}

			const displayValues = [];

			filteredProps.forEach(property => {
				const propertyId = property.ID;
				const selectedValue = selectedValues[propertyId] || null;

				if (selectedValue && property.VALUES && property.VALUES.length)
				{
					property.VALUES.forEach(singleValue => {
						const displayValue = valueFormatter(singleValue);
						const match = isArray(selectedValue)
							? selectedValue.contains(singleValue.ID)
							: selectedValue === singleValue.ID;

						if (match && displayValue)
						{
							displayValues.push(displayValue);
						}
					});
				}
			});

			return displayValues;
		}
	}

	module.exports = { InlineSkuTree };
});