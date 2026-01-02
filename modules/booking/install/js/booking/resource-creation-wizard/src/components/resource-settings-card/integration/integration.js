import { Loc } from 'main.core';

import { Set as IconSet } from 'ui.icon-set.api.vue';
import { TextLayout } from '../text-layout/text-layout';
import { TitleLayout } from '../title-layout/title-layout';
import { IntegrationCalendar } from './components/calendar/integration-calendar';

import './integration.css';

export const Integration = {
	name: 'Integration',
	components: {
		TitleLayout,
		TextLayout,
		IntegrationCalendar,
	},
	setup(): IntegrationSetupObject
	{
		const title = Loc.getMessage('BRCW_SETTINGS_CARD_INTEGRATION_TITLE');
		const titleIconType = IconSet.COLLABORATION;

		return {
			title,
			titleIconType,
		};
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-settings">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<TextLayout
				type="IntegrationSettings"
				:text="loc('BRCW_SETTINGS_CARD_INTEGRATION_DESCRIPTION')"
			/>
			<IntegrationCalendar/>
		</div>
	`,
};

type IntegrationSetupObject = {
	title: string,
	titleIconType: string,
}
