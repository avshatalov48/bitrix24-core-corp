/**
 * @module layout/ui/fields/shipment-extra-services
 */
jn.define('layout/ui/fields/shipment-extra-services', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');

	/**
	 * @class StringField
	 */
	class ShipmentExtraServicesField extends BaseField
	{
		renderReadOnlyContent()
		{
			return View(
				{
					style: {
						marginLeft: 20,
						marginTop: 10,
					},
				},
				...this.getServicesViews(),
			);
		}

		getServicesViews()
		{
			const servicesViews = [];
			this.getValue().forEach((service, serviceIndex, services) => {
				servicesViews.push(
					View(
						{
							style: {
								marginBottom: serviceIndex < services.length - 1 ? 10 : 0,
							},
						},
						Text({
							style: this.styles.title,
							text: service.name.toLocaleUpperCase(),
						}),
						Text({
							style: this.getDefaultStyles().value,
							text: jnComponent.convertHtmlEntities(service.value),
						}),
					),
				);
			});

			return servicesViews;
		}
	}

	module.exports = {
		ShipmentExtraServicesFieldClass: ShipmentExtraServicesField,
		ShipmentExtraServicesType: 'shipment_extra_services',
		ShipmentExtraServicesField: (props) => new ShipmentExtraServicesField(props),
	};
});
