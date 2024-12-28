import '../../css/fields-settings.css';
import 'ui.icon-set.crm';

export const SliderButton = {
	// language=Vue
	template: `
		<div class="ui-form-row">
			<div class="ui-ctl ui-ctl-before-icon ui-ctl-after-icon ui-ctl-w100 fields-settings__slider-button">
				<div class="ui-ctl-before">
					<div class="ui-icon-set --form-settings fields-settings__slider-icon"></div>
				</div>
				<div class="ui-ctl-after ui-ctl-icon-angle fields-settings__slider-chevron">
				</div>
				<div class="ui-ctl-element">
					{{ this.$Bitrix.Loc.getMessage('DATASET_IMPORT_FIELD_SETTINGS_FORMAT_BUTTON') }}
				</div>
			</div>
		</div>
	`,
};
