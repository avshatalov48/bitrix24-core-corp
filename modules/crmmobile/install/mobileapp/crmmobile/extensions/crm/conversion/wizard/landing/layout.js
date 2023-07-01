/**
 * @module crm/conversion/wizard/landing/layout
 */
jn.define('crm/conversion/wizard/landing/layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { getEntityMessage } = require('crm/loc');
	const { BackdropHeader } = require('layout/ui/banners');
	const { WizardFields } = require('crm/conversion/wizard/fields');

	const EXTENSION_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/conversion/wizard/images/`;

	/**
	 * @class ConversionWizardLandingLayout
	 */
	class ConversionWizardLandingLayout extends LayoutComponent
	{
		renderFieldsBlocks()
		{
			const { data = [], getFieldsData } = this.props;

			let fieldsData = data;
			if ((!data || data.length === 0) && typeof getFieldsData === 'function')
			{
				fieldsData = getFieldsData();
			}

			return fieldsData.map(({ id, fields, type, onChange }) => {
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
							text: Loc.getMessage('MCRM_CONVERSION_WIZARD_LANDING_FIELDS_TITLE', { '#FIELDS_COUNT#': fields.length }),
						}),
					),
					View(
						{
							style: {
								paddingHorizontal: 16,
								paddingVertical: isFields ? 0 : 10,
								paddingBottom: isFields ? 10 : 0,
							},
						},
						new WizardFields({ type, fields, onChange }),
					),
				);
			});
		}

		render()
		{
			const { entityTypeId } = this.props;

			return View(
				{
					style: {
						height: '100%',
						backgroundColor: '#eef2f4',
					},
				},
				ScrollView(
					{
						style: {
							flex: 1,
							backgroundColor: '#eef2f4',
						},
					},
					View(
						{
							style: {
								flexGrow: 1,
							},
						},
						View(
							{
								style: {
									marginBottom: 12,
								},
							},
							BackdropHeader({
								title: Loc.getMessage('MCRM_CONVERSION_WIZARD_LANDING_HEADER_TITLE'),
								description: getEntityMessage('MCRM_CONVERSION_WIZARD_LANDING_HEADER_DESCRIPTION', entityTypeId),
								image: `${EXTENSION_PATH}/step_fields.png`,
								position: 'flex-start',
							}),
						),
						...this.renderFieldsBlocks(),
					),
				),
			);
		}
	}

	module.exports = { ConversionWizardLandingLayout };
});
