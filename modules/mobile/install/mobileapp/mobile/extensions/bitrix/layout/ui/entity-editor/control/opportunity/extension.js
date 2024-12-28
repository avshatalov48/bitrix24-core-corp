/**
 * @module layout/ui/entity-editor/control/opportunity
 */
jn.define('layout/ui/entity-editor/control/opportunity', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EntityEditorField } = require('layout/ui/entity-editor/control/field');
	const { EntityEditorMode } = require('layout/ui/entity-editor/editor-enum/mode');

	const { OpportunityButton } = require('layout/ui/entity-editor/control/opportunity/opportunity-button');
	const { DocumentList } = require('layout/ui/entity-editor/control/opportunity/document-list');
	const { TypeId } = require('crm/type');

	const isManualPriceFieldName = 'IS_MANUAL_OPPORTUNITY';

	/**
	 * @class EntityEditorOpportunityField
	 */
	class EntityEditorOpportunityField extends EntityEditorField
	{
		constructor(props)
		{
			super(props);

			this.state.amountLocked = this.guessAmountIsLocked();

			/** @type {Fields.MoneyInput} */
			this.fieldRef = null;
		}

		initialize(id, uid, type, settings)
		{
			super.initialize(id, uid, 'money', settings);
		}

		initializeStateFromModel()
		{
			super.initializeStateFromModel();

			this.state.amountLocked = this.guessAmountIsLocked();
		}

		renderReceivePaymentButton()
		{
			if (this.isReceivePaymentAvailable())
			{
				return new OpportunityButton({
					uid: this.getUid(),
				});
			}

			return null;
		}

		shouldShowReceivePaymentButton()
		{
			return this.isReceivePaymentAvailable()
					&& this.schemeElement.options.isPayButtonVisible === 'true';
		}

		shouldShowDocumentList()
		{
			if (this.isNewEntity())
			{
				return false;
			}

			if (this.schemeElement.options.isPaymentDocumentsVisible === 'false')
			{
				return false;
			}

			const entityTypeId = this.model.getField('ENTITY_TYPE_ID', 0);

			return entityTypeId === TypeId.Deal && this.schemeElement.data.isReceivePaymentAvailable;
		}

		get marginBottom()
		{
			return this.shouldShowDocumentList() ? 2 : super.marginBottom;
		}

		renderDocumentList()
		{
			const contactId = Number(this.model.getField('CONTACT_ID', 0));
			const entityTypeId = this.model.getField('ENTITY_TYPE_ID', 0);
			const data = {
				documentsData: this.model.getField('DOCUMENTS', {}),
				orderList: this.model.getField('ORDER_LIST', {}),
				uid: this.getUid(),
				entityId: this.model.getField('ID', 0),
				entityTypeId,
				isUsedInventoryManagement: this.model.getField('IS_USED_INVENTORY_MANAGEMENT', false),
				modeWithOrders: this.model.getField('MODE_WITH_ORDERS', false),
				salesOrderRights: this.model.getField('SALES_ORDERS_RIGHTS', {}),
				isTerminalAvailable: this.model.getField('IS_TERMINAL_AVAILABLE', false),
				isOnecMode: this.model.getField('IS_ONEC_MODE', false),
				resendParams: {
					entityHasContact: contactId > 0,
					contactId,
					contactHasPhone: this.model.getField('CONTACT_HAS_PHONE', 'N') === 'Y',
				},
				restrictions: {
					realization: {
						isRestricted:
							this.model.getField('IS_1C_PLAN_RESTRICTED', false)
							&& this.model.getField('IS_ONEC_MODE', false)
						,
					},
				},
				analytics: this.getAnalytics(),
			};

			return new DocumentList(data);
		}

		get showBorder()
		{
			if (this.shouldShowDocumentList())
			{
				return false;
			}

			return super.showBorder;
		}

		renderField()
		{
			return View(
				{
					style: {
						marginBottom: this.marginBottom,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							flexWrap: 'wrap',
							justifyContent: 'space-between',
						},
					},
					View(
						{
							style: {
								flex: this.isReadOnly() ? 0 : 1,
								marginRight: -17,
							},
						},
						this.getFieldInstance(this.getValue()),
					),
					this.shouldShowReceivePaymentButton() && View(
						{
							style: {
								alignItems: 'flex-end',
								justifyContent: 'flex-end',
							},
							onClick: () => Keyboard.dismiss(),
						},
						View(
							{
								style: {
									marginBottom: 15,
									marginRight: 15,
									marginLeft: 17,
								},
							},
							this.renderReceivePaymentButton(),
						),
					),
				),
				this.shouldShowDocumentList()
					? this.renderDocumentList()
					: View(
						{
							style: {
								borderBottomColor: this.isInEditMode() ? AppTheme.colors.bgSeparatorPrimary : AppTheme.colors.base7,
								borderBottomWidth: this.showBorder ? 1 : 0,
								marginHorizontal: 16,
							},
						},
					),
			);
		}

		guessAmountIsLocked()
		{
			if (!this.model.hasField(isManualPriceFieldName))
			{
				return false;
			}

			const isManualPrice = this.model.getField(isManualPriceFieldName, 'N');
			if (isManualPrice === 'Y')
			{
				return false;
			}

			const controller = (
				BX.prop.getArray(this.editor.settings, 'controllers', [])
					.find((controller) => controller.name === 'PRODUCT_LIST')
			);
			if (controller && controller.config)
			{
				const productSummaryFieldName = BX.prop.getString(controller.config, 'productSummaryFieldName', '');
				if (productSummaryFieldName)
				{
					const productSummary = this.model.getField(productSummaryFieldName, null);
					if (productSummary)
					{
						const { count } = productSummary;

						return count > 0;
					}
				}
			}

			return false;
		}

		getValueFromModel(defaultValue = null)
		{
			if (!this.model)
			{
				return null;
			}

			const amountField = this.schemeElement.data.amount;
			const amount = this.model.getField(
				amountField,
				BX.prop.getNumber(defaultValue, 'amount', 0),
			);

			const currencyField = this.schemeElement.data.currency.name;
			const currency = this.model.getField(
				currencyField,
				BX.prop.getString(defaultValue, 'currency', ''),
			);

			return { amount, currency };
		}

		prepareConfig()
		{
			const config = super.prepareConfig();

			return {
				...config,
				amountReadOnly: BX.prop.get(config, 'amountReadOnly', false),
				amountLocked: this.state.amountLocked,
				largeFont: true,
				formatAmount: true,
				currencyReadOnly: false,
				styles: {
					innerWrapper: {
						flex: this.isReadOnly() ? 0 : 1,
					},
				},
			};
		}

		getValuesToSave()
		{
			if (!this.isEditable())
			{
				return {};
			}

			const amountField = this.schemeElement.data.amount;
			const currencyField = this.schemeElement.data.currency.name;

			let amount = '';
			let currency = '';

			if (this.state.value)
			{
				amount = this.state.value.amount;
				currency = this.state.value.currency;
			}

			return {
				[amountField]: amount,
				[currencyField]: currency,
			};
		}

		setCustomAmountClickHandler(handler)
		{
			if (this.fieldRef)
			{
				this.fieldRef.setCustomAmountClickHandler(handler);
			}
		}

		isAmountLocked()
		{
			return this.state.amountLocked;
		}

		isReceivePaymentAvailable()
		{
			const entityTypeId = this.model.getField('ENTITY_TYPE_ID', 0);

			return this.schemeElement.data.isReceivePaymentAvailable
				&& entityTypeId === TypeId.Deal
				&& !this.isInEditMode();
		}

		lockAmount()
		{
			if (this.state.amountLocked !== true)
			{
				return new Promise((resolve) => {
					this.setState({
						amountLocked: true,
						mode: this.parent.getMode(),
					}, resolve);
				});
			}

			return Promise.resolve();
		}

		unlockAmount()
		{
			if (this.state.amountLocked !== false)
			{
				return new Promise((resolve) => {
					this.setState({ amountLocked: false }, resolve);
				});
			}

			return Promise.resolve();
		}

		unlockAmountAndFocus()
		{
			return new Promise((resolve) => {
				this.setState({
					amountLocked: false,
					mode: EntityEditorMode.edit,
				}, () => this.focusField().then(resolve));
			});
		}
	}

	module.exports = { EntityEditorOpportunityField };
});
