import 'ui.buttons';
import 'ui.forms';
import './style.css';
import { chartAPI } from '../../api';
import { events } from '../../events';
import { BIcon, Set } from 'ui.icon-set.api.vue';
import type { FirstPopupData } from '../../types';

export const FirstPopup = {
	name: 'FirstPopup',
	components: {
		BIcon,
	},

	data(): FirstPopupData
	{
		return {
			show: false,
			title: '',
			description: '',
			subDescription: '',
			features: [],
		};
	},
	async mounted(): Promise<void>
	{
		this.title = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_TITLE');
		this.description = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_DESCRIPTION');
		this.subDescription = this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_SUB_DESCRIPTION');
		this.features = [
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_1'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_2'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_3'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_4'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_5'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_6'),
			this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_FEATURE_7'),
		];

		const { firstTimeOpened } = await chartAPI.getDictionary();
		this.show = firstTimeOpened === 'N' && this.title.length > 0;
	},

	methods: {
		closePopup(): void
		{
			chartAPI.firstTimeOpened();
			this.show = false;
			top.BX.Event.EventEmitter.emit(events.HR_FIRST_POPUP_SHOW);
		},
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},
	computed: {
		set(): Set
		{
			return Set;
		},
	},
	template: `
		<div v-if="show" class="first-popup">
			<div class="first-popup-overlay" @click="closePopup"></div>
			<div class="first-popup-content">
				<div class="title">{{ title }}</div>
				<div class="first-popup-left">
					<p class="description">{{ description }}</p>
					<p class="sub-description">{{ subDescription }}</p>
					<div class="first-popup-list">
						<div class="first-popup-list-item" v-for="(feature, index) in features" :key="index">
							<div class="first-popup-list-item-point">•</div>
							<div class="first-popup-list-item-feature">{{ feature }}</div>
						</div>
					</div>
					<button class="ui-btn ui-btn-success first-popup-ui-btn" @click="closePopup">
						{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_FIRST_OPEN_BUTTON_START') }}
					</button>
				</div>
				<div class="first-popup-right">
					<video
						src="/bitrix/js/humanresources/company-structure/org-chart/src/components/first-popup/images/preview.webm"
						autoplay
						loop
						muted
						playsinline
						class="first-popup-animation"
					></video>
				</div>
				<BIcon :name="set.CROSS_25" :size="24" class="first-popup-close" @click="closePopup"></BIcon>
			</div>
		</div>
	`,
};
