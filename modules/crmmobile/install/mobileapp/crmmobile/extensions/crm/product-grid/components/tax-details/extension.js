/**
 * @module crm/product-grid/components/tax-details
 */
jn.define('crm/product-grid/components/tax-details', (require, exports, module) => {

	const { Loc } = require('loc');
	const { ProductRow } = require('crm/product-grid/model');
	const { ProductCalculator } = require('crm/product-calculator');
	const { BooleanField } = require('layout/ui/fields/boolean');
	const { MoneyField } = require('layout/ui/fields/money');
	const { SelectField } = require('layout/ui/fields/select');
	const { clone } = require('utils/object');

	/**
	 * @callback calculationFn
	 * @param {ProductCalculator} calc
	 * @returns {ProductRowSchema}
	 */

	/**
	 * @class TaxDetails
	 */
	class TaxDetails extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				productRow: new ProductRow(clone(this.props.productData))
			};

			this.layout = props.layout;
			this.initLayout();
		}

		initLayout()
		{
			const closeButtonText = this.isReadonly()
				? Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_CLOSE')
				: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_DONE');

			this.layout.setTitle({text: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_TITLE')});
			this.layout.setRightButtons([
				{
					name: closeButtonText,
					type: 'text',
					color: '#0B66C3',
					callback: () => this.close(),
				}
			]);
			this.layout.enableNavigationBarBorder(false);
		}

		isReadonly()
		{
			const editable = Boolean(this.props.editable);
			return !editable;
		}

		close()
		{
			if (this.isReadonly())
			{
				this.layout.close();
			}
			else
			{
				this.onChange();
				this.layout.close();
			}
		}

		onChange()
		{
			if (this.props.onChange)
			{
				this.props.onChange(this.state.productRow.getRawValues());
			}
		}

		render()
		{
			return Container(
				FieldsWrapper({
					fields: this.prepareFormFields()
				})
			);
		}

		prepareFormFields()
		{
			const fields = [];

			fields.push(SelectField({
				title: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_TAX_RATE'),
				value: this.state.productRow.getTaxRate(),
				readOnly: this.isReadonly(),
				required: true,
				config: {
					items: this.props.vatRates,
				},
				onChange: (newVal) => {
					this.recalculate(calculator => calculator.calculateTax(newVal));
				}
			}));

			fields.push(BooleanField({
				title: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_TAX_INCLUDED'),
				value: this.state.productRow.isTaxIncluded(),
				readOnly: this.isReadonly(),
				onChange: (newVal) => {
					const strValue = newVal ? 'Y' : 'N';
					this.recalculate(calculator => calculator.calculateTaxIncluded(strValue));
				}
			}));

			fields.push(MoneyField({
				title: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_BASE_SUM'),
				value: {
					amount: this.state.productRow.getBasePrice() * this.state.productRow.getQuantity(),
					currency: this.state.productRow.getCurrency()
				},
				readOnly: true,
				config: {
					currencyReadOnly: true,
					selectionOnFocus: true,
				},
			}));

			fields.push(MoneyField({
				title: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_TAX_SUM'),
				value: {
					amount: this.state.productRow.getTaxSum(),
					currency: this.state.productRow.getCurrency()
				},
				readOnly: true,
				config: {
					currencyReadOnly: true,
					selectionOnFocus: true,
				},
			}));

			fields.push(MoneyField({
				title: Loc.getMessage('PRODUCT_GRID_TAX_DETAILS_FINAL_SUM'),
				value: {
					amount: this.state.productRow.getSum(),
					currency: this.state.productRow.getCurrency()
				},
				readOnly: true,
				config: {
					currencyReadOnly: true,
					selectionOnFocus: true,
				},
			}));

			return fields;
		}

		/**
		 * @param {calculationFn} calculationFn
		 */
		recalculate(calculationFn)
		{
			const productRow = this.state.productRow;
			const calculator = new ProductCalculator(productRow.getRawValues());
			const result = calculationFn(calculator);
			productRow.setFields(result);
			this.setState({productRow});
		}
	}

	function Container(...children)
	{
		return View(
			{
				style: {
					backgroundColor: '#EEF2F4'
				},
				resizableByKeyboard: true,
			},
			ScrollView(
				{
					style: {
						flexDirection: 'column',
						flexGrow: 1,
					},
				},
				View(
					{},
					View(
						{
							style: {
								padding: 16,
								paddingTop: 0,
								backgroundColor: '#ffffff',
								borderRadius: 12,
								marginBottom: 12,
							},
						},
						...children
					)
				)
			)
		);
	}

	module.exports = { TaxDetails };

});