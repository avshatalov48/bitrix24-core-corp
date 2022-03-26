(() => {
	/**
	 * @class ProductSummarySection
	 */
	class ProductSummarySection extends EntityEditorBaseControl
	{
		constructor(props)
		{
			super(props);

			this.initialize(props.id, props.settings, props.type);

			const summaryData = this.getValueFromModel({});
			this.state = {
				count: summaryData.count,
				total: summaryData.total
			};

			this.buildStyles();

			this.switchToAddingProduct = this.switchToAddingProduct.bind(this);
			this.switchToProductsList = this.switchToProductsList.bind(this);
		}

		initializeStateFromModel()
		{
			const summaryData = this.getValueFromModel({});
			this.setState({
				count: summaryData.count,
				total: summaryData.total
			});
		}

		render()
		{
			const showButton = (!this.hasProducts() && this.readOnly === false);

			return View(
				{},
				showButton ? this.renderButton() : this.renderSummary()
			);
		}

		renderButton()
		{
			return View(
				{
					style: this.styles.addButtonContainer,
					onClick: this.switchToAddingProduct
				},
				Image(
					{
						style: this.styles.addButtonIcon,
						resizeMode: 'center',
						svg: svgImages.cube
					}
				),
				Text(
					{
						style: this.styles.addButtonText,
						text: BX.message('FIELDS_PRODUCT_ADD_PRODUCT')
					}
				)
			);
		}

		renderSummary()
		{
			return View(
				{
					style: this.styles.productWrapper(this.readOnly),
					onClick: this.switchToProductsList
				},
				View(
					{
						style: this.styles.productHeader
					},
					Text(
						{
							style: this.styles.productTitle,
							text: this.getTitle().toLocaleUpperCase(Application.getLang())
						}
					),
					Text(
						{
							style: this.styles.productPriceTitle,
							text: BX.message('FIELDS_PRODUCT_PRICE').toLocaleUpperCase(Application.getLang())
						}
					)
				),
				View(
					{
						style: this.styles.productContent
					},
					Image(
						{
							style: this.styles.productIcon,
							resizeMode: 'center',
							svg: svgImages.cube
						}
					),
					Text(
						{
							style: this.styles.productCountText,
							text: BX.message('FIELDS_PRODUCT_COUNT') + ': ' + this.state.count
						}
					),
					View(
						{
							style: this.styles.separator
						}
					),
					Text({
						text: Money.create(this.state.total).formatted,
						style: {
							fontSize: 16,
							fontWeight: 'bold',
							color: '#333333'
						}
					})
				)
			);
		}

		/**
		 * @returns {Boolean}
		 */
		hasProducts()
		{
			const productsCount = Number(this.state.count);

			return !isNaN(productsCount) && productsCount > 0;
		}

		switchToAddingProduct()
		{
			const tab = {id: 'products'};
			const changed = true;

			BX.postComponentEvent('DetailCard::onTabClick', [tab, changed]);
			// wait when tab opens
			setTimeout(() => {
				BX.postComponentEvent('DetailCard::onAddProductsButtonClick', []);
			}, 300);
		}

		switchToProductsList()
		{
			const tab = {id: 'products'};
			const changed = true;

			BX.postComponentEvent('DetailCard::onTabClick', [tab, changed]);
		}

		getTitle()
		{
			if (!this.schemeElement)
			{
				return "";
			}

			let title = this.schemeElement.getTitle();
			if (title === "")
			{
				title = this.schemeElement.getName();
			}

			return title;
		}

		getValue()
		{
			return {
				count: this.state.count,
				total: this.state.total,
			};
		}

		setValue({count, total})
		{
			this.setState({count, total});
		}

		getValuesToSave()
		{
			return {};
		}

		getValueFromModel(defaultValue = '')
		{
			if (this.model)
			{
				return this.model.getField(this.getName(), defaultValue);
			}

			return defaultValue;
		}

		getName()
		{
			return this.schemeElement ? this.schemeElement.getName() : "";
		}

		buildStyles()
		{
			this.styles = {
				addButtonContainer: {
					marginTop: 10,
					marginBottom: 10,
					paddingTop: 11,
					paddingBottom: 11,
					justifyContent: 'center',
					alignItems: 'center',
					borderColor: '#00a2e8',
					borderWidth: 1,
					borderRadius: 6,
					flexDirection: 'row'
				},
				addButtonIcon: {
					width: 16,
					height: 17,
					marginRight: 10
				},
				addButtonText: {
					color: '#525c69',
					fontSize: 16,
					fontWeight: '500'
				},
				productWrapper: (readOnly) => ({
					paddingTop: readOnly ? 8 : 12,
					borderWidth: 0
				}),
				productHeader: {
					justifyContent: 'space-between',
					alignItems: 'center',
					flexDirection: 'row',
					marginBottom: 4,
					width: '100%'
				},
				productTitle: {
					color: '#b9c0ca',
					fontSize: 10
				},
				productPriceTitle: {
					color: '#b9c0ca',
					fontSize: 10
				},
				productContent: {
					flexDirection: 'row',
					marginBottom: 12.5,
					justifyContent: 'center',
					alignItems: 'center'
				},
				productIcon: {
					width: 13,
					height: 14,
					marginLeft: 4.5,
					marginRight: 7
				},
				productCountText: {
					color: '#0b66c3',
					fontSize: 16
				},
				separator: {
					height: 1,
					flex: 1,
					backgroundColor: '#d4dce0',
					marginLeft: 6,
					marginRight: 6
				},
				productCurrency: {
					color: '#a8adb4',
					fontSize: 16
				}
			}
		}
	}

	const svgImages = {
		cube: {
			content: `<svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.93984 0.793431C6.92683 0.797378 6.91326 0.805271 6.89912 0.812375L0.80446 3.22473C0.651753 3.29656 0.596329 3.45206 0.591797 3.63518V11.4025C0.593494 11.5761 0.68455 11.7411 0.80446 11.7877L6.84033 14.1746C6.91499 14.2077 7.01283 14.203 7.09371 14.1809L13.1837 11.775C13.3036 11.726 13.393 11.5579 13.3919 11.3835V3.68569C13.3953 3.44888 13.3364 3.30995 13.1792 3.23733L7.05298 0.812446C7.01113 0.791134 6.9783 0.78238 6.93984 0.793431ZM6.97604 1.62068L12.0346 3.62878L6.97604 5.62425L1.91298 3.62248L6.97604 1.62068Z" fill="#B9C0CA"/></svg>`
		}
	}

	jnexport(ProductSummarySection);
})();