/**
 * @module crm/conversion/wizard/layouts/fields
 */
jn.define('crm/conversion/wizard/layouts/fields', (require, exports, module) => {
	const { Loc } = require('loc');
	const { sortBy } = require('utils/array');
	const { getEntityMessage } = require('crm/loc');
	const { BackdropHeader } = require('layout/ui/banners');
	const { WizardFields } = require('crm/conversion/wizard/fields');

	const EXTENSION_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/conversion/wizard/images/`;

	/**
	 * @class ConversionWizardFieldsLayout
	 */
	class ConversionWizardFieldsLayout extends LayoutComponent
	{
		renderFieldsBlocks()
		{
			const { getFieldsConfig, onChange } = this.props;
			let { fieldsConfig = [] } = this.props;

			if (typeof getFieldsConfig === 'function')
			{
				fieldsConfig = getFieldsConfig();
			}

			if (fieldsConfig.length === 0)
			{
				return fieldsConfig;
			}

			return sortBy(fieldsConfig, 'id').map(({ id, data, type }) => {
				const isFields = id === 'fields';

				return View(
					{
						style: {
							backgroundColor: '#ffffff',
							borderRadius: 12,
							marginBottom: 12,
						},
					},
					isFields && View(
						{
							style: {
								height: 38,
								paddingLeft: 20,
								justifyContent: 'center',
							},
						},
						Text({
							style: {
								color: '#525c69',
								fontSize: 14,
							},
							text: Loc.getMessage(
								'MCRM_CONVERSION_WIZARD_LAYOUT_FIELDS_TITLE',
								{ '#FIELDS_COUNT#': data.length },
							),
						}),
					),
					View(
						{
							style: {
								paddingHorizontal: 16,
								paddingTop: isFields ? 0 : 4,
								paddingBottom: isFields ? 10 : 0,
							},
						},
						new WizardFields({
							type,
							fields: data,
							onChange: (entityTypeIds) => {
								onChange({ entityTypeIds });
							},
						}),
					),
				);
			});
		}

		render()
		{
			const { entityTypeId } = this.props;

			return ScrollView(
				{
					style: {
						height: '100%',
						backgroundColor: '#eef2f4',
					},
				},
				View(
					{},
					View(
						{
							style: {
								marginBottom: 12,
								borderRadius: 12,
							},
						},
						BackdropHeader({
							title: Loc.getMessage('MCRM_CONVERSION_WIZARD_LANDING_HEADER_TITLE'),
							description: getEntityMessage(
								'MCRM_CONVERSION_WIZARD_LAYOUT_FIELDS_HEADER_DESCRIPTION',
								entityTypeId,
							),
							image: `${EXTENSION_PATH}/step_fields.png`,
							position: 'flex-start',
						}),
					),
					...this.renderFieldsBlocks(),
				),
			);
		}
	}

	module.exports = { ConversionWizardFieldsLayout };
});
