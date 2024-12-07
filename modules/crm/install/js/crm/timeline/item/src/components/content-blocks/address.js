import { Address, ControlMode, Format } from 'location.core';
import { Extension, Loc } from 'main.core';

export default {
	props: {
		addressFormatted: String,
	},
	mounted()
	{
		void this.$nextTick(() => {
			this.renderAddressWidget();
		});
	},
	methods: {
		renderAddressWidget(): void
		{
			const settings = Extension.getSettings('crm.timeline.item');

			if (!settings.hasLocationModule)
			{
				return;
			}

			const widgetFactory = new BX.Location.Widget.Factory();

			const format = new Format(JSON.parse(Loc.getMessage('CRM_ACTIVITY_TODO_ADDRESS_FORMAT')));
			const address = new Address({
				languageId: format.languageId,
			});

			address.setFieldValue(format.fieldForUnRecognized, this.addressFormatted);

			const addressWidget = widgetFactory.createAddressWidget({
				address,
				mode: ControlMode.view,
			});

			const addressWidgetParams = {
				mode: ControlMode.view,
				mapBindElement: this.$refs.mapBindElement,
				controlWrapper: this.$refs.controlWrapper,
			};

			addressWidget.render(addressWidgetParams);
		},
	},
	template: `
		<div class="crm-timeline__text-block crm-timeline__address-block">
			<div ref="mapBindElement">
				<div ref="controlWrapper" class="crm-timeline__address-block-address-wrapper">
					<span 
						:title="addressFormatted"
						class="ui-link ui-link-dark ui-link-dotted"
					>
						{{addressFormatted}}
					</span>
				</div>
			</div>
		</div>
	`,
};
