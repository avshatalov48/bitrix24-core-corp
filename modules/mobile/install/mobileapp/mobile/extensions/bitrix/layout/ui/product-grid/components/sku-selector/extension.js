/**
 * @module layout/ui/product-grid/components/sku-selector
 */
jn.define('layout/ui/product-grid/components/sku-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { get, clone, mergeImmutable, isEqual } = require('utils/object');
	const { FocusContext } = require('layout/ui/product-grid/services/focus-context');
	const {
		BottomPanel,
		ProductInfo,
		Scrollable,
		SkuTreeContainer,
		SkuTreeProperty,
		SkuTreePropertyValue,
	} = require('layout/ui/product-grid/components/sku-selector/elements');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');

	class SkuSelector extends LayoutComponent
	{
		/**
		 * @param {SkuSelectorProps} props
		 */
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
			this.state = { loading: true };
			this.productVariations = null;
			this.wrapperRef = null;

			this.preloadSkuCollection().then(() => {
				const nextState = this.buildState(props);
				this.setState(nextState, () => this.fadeIn());
			}).catch(console.error);
			this.initLayout();
		}

		/**
		 * @param {SkuSelectorProps} props
		 * @returns {SkuSelectorState}
		 */
		buildState(props)
		{
			return {
				loading: false,
				selectedVariationId: props.selectedVariationId,
				quantity: props.quantity,
			};
		}

		initLayout()
		{
			this.layout.setTitle({ text: Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_CHOOSE_VARIATION') });
			this.layout.enableNavigationBarBorder(false);
		}

		/**
		 * @abstract
		 * @returns {Promise}
		 */
		preloadSkuCollection()
		{
			throw new Error('Abstract method must be implemented in child class');
		}

		/**
		 * @returns {object}
		 */
		get skuTree()
		{
			return this.props.skuTree;
		}

		/**
		 * @returns {SkuSelectorVariation}
		 */
		get selectedVariation()
		{
			return this.productVariations[this.state.selectedVariationId];
		}

		/**
		 * @returns {object}
		 */
		get selectedPropertyValues()
		{
			const offer = this.skuTree.OFFERS.find((item) => item.ID === this.state.selectedVariationId);

			return offer ? offer.TREE : {};
		}

		render()
		{
			return this.wrap(() => [
				Scrollable(
					ProductInfo({
						name: this.selectedVariation.NAME,
						images: this.selectedVariation.GALLERY.map((photo) => photo.previewUrl),
					}),
					this.renderSkuTree(),
				),
				this.renderSavePanel(),
			]);
		}

		wrap(contentFn)
		{
			const children = this.state.loading
				? [new LoadingScreenComponent({ backgroundColor: AppTheme.colors.bgSecondary })]
				: contentFn();

			return View(
				{
					ref: (ref) => {
						this.wrapperRef = ref;
					},
					style: {
						flexDirection: 'column',
						opacity: this.state.loading ? 1 : 0,
					},
					resizableByKeyboard: true,
					onClick: () => FocusContext.blur(),
				},
				...children,
			);
		}

		renderSkuTree()
		{
			const allProps = Object.values(this.skuTree.OFFERS_PROP || {});

			return SkuTreeContainer(
				...allProps.map((property, index) => SkuTreeProperty(
					property.NAME,
					...this.renderPropertyValues(property, index),
				)),
			);
		}

		renderPropertyValues(property, index)
		{
			const allValues = property.VALUES || [];
			const existingValues = this.filterAllowedToChoosePropertyValues(property, index);
			const selectedValues = this.selectedPropertyValues[property.ID] || [];

			const isSelected = (val) => (Array.isArray(selectedValues)
				? selectedValues.includes(val)
				: selectedValues === val);

			return allValues.map((value) => {
				if (!existingValues.includes(value.ID))
				{
					return null;
				}

				if (value.NAME.length === 0)
				{
					return null;
				}

				return SkuTreePropertyValue({
					onClick: () => this.toggleSelection(property.ID, value.ID),
					selected: isSelected(value.ID),
					picture: get(value, 'PICT.SRC', null),
					name: value.NAME,
				});
			});
		}

		renderSavePanel()
		{
			const saveButtonCaption = this.props.saveButtonCaption || Loc.getMessage(
				'PRODUCT_GRID_CONTROL_SKU_SELECTOR_SAVE');

			return BottomPanel({
				saveButtonCaption,
				onSave: () => this.save(),
			});
		}

		/**
		 * @param {object} property
		 * @param {number} index
		 * @returns {number[]}
		 */
		filterAllowedToChoosePropertyValues(property, index)
		{
			const isPartOf = (subtree, tree) => {
				for (const prop in subtree)
				{
					if (!Object.keys(tree).includes(prop))
					{
						return false;
					}

					if (tree[prop] !== subtree[prop])
					{
						return false;
					}
				}

				return true;
			};

			const offerExists = (tree) => this.skuTree.OFFERS.find((offer) => isPartOf(tree, offer.TREE));
			const possiblePropertyValues = () => {
				const existingValues = this.skuTree.OFFERS.map((offer) => offer.TREE[property.ID]);

				return [...new Set(existingValues)];
			};
			const currentlySelectedValues = this.selectedPropertyValues;
			const allowedToChooseValues = [];
			const allProps = Object.values(this.skuTree.OFFERS_PROP || {});
			const potentialOffer = {};
			allProps.map((prevProperty, prevIndex) => {
				if (prevIndex < index)
				{
					potentialOffer[prevProperty.ID] = currentlySelectedValues[prevProperty.ID];
				}
			});

			possiblePropertyValues().forEach((valueId) => {
				potentialOffer[property.ID] = valueId;

				if (offerExists(potentialOffer))
				{
					allowedToChooseValues.push(valueId);
				}
			});

			return allowedToChooseValues;
		}

		/**
		 * @param {number} propertyId
		 * @param {number} valueId
		 */
		toggleSelection(propertyId, valueId)
		{
			const propertyValue = this.preparePropertyValue(propertyId, valueId);
			const nextVariationPropertyValues = mergeImmutable(
				this.selectedPropertyValues,
				{ [propertyId]: propertyValue },
			);
			const nextVariation = this.findNextVariation(propertyId, propertyValue, nextVariationPropertyValues);

			if (nextVariation !== null && this.productVariations[nextVariation.ID])
			{
				this.setState({
					selectedVariationId: nextVariation.ID,
				});

				return;
			}

			void ErrorNotifier.showError(Loc.getMessage('PRODUCT_GRID_CONTROL_SKU_SELECTOR_CHOOSE_VARIATION_ERROR'));
		}

		/**
		 * @param {number} propertyId
		 * @param {number} valueId
		 * @returns {number[]|number}
		 */
		preparePropertyValue(propertyId, valueId)
		{
			let nextValue = clone(this.selectedPropertyValues[propertyId]);

			if (Array.isArray(nextValue))
			{
				const index = nextValue.indexOf(valueId);
				if (index > -1)
				{
					nextValue.splice(index, 1);
				}
				else
				{
					nextValue.push(valueId);
				}
			}
			else
			{
				nextValue = valueId;
			}

			return nextValue;
		}

		/**
		 * @param {number} propertyId
		 * @param {number[]|number} propertyValue
		 * @param {object} variationValues
		 * @returns {object|null}
		 */
		findNextVariation(propertyId, propertyValue, variationValues)
		{
			let nextVariation = null;
			const alternativeOptions = [];

			this.skuTree.OFFERS.map((offer) => {
				if (isEqual(offer.TREE, variationValues))
				{
					nextVariation = offer;
				}

				if (offer.TREE[propertyId] && isEqual(offer.TREE[propertyId], propertyValue))
				{
					alternativeOptions.push(offer);
				}
			});

			if (nextVariation === null && alternativeOptions.length > 0)
			{
				nextVariation = alternativeOptions.shift();
			}

			return nextVariation;
		}

		save()
		{
			if (this.props.onSave)
			{
				const skuTree = clone(this.skuTree);
				skuTree.SELECTED_VALUES = clone(this.selectedPropertyValues);

				this.props.onSave({
					skuTree,
					variationId: this.state.selectedVariationId,
					variationData: this.selectedVariation,
					quantity: this.state.quantity,
				});
			}
			this.layout.close();
		}

		fadeIn()
		{
			if (this.wrapperRef)
			{
				this.wrapperRef.animate({
					duration: 300,
					opacity: 1,
				});
			}
		}
	}

	module.exports = { SkuSelector };
});
