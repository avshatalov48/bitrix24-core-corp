import {Vue} from 'ui.vue';
import {ProductSettingsUpdater} from './updater';
import LocMixin from '../loc';

export default Vue.extend({
	mixins: [LocMixin],
	props: {
		settings: {
			type: Object,
			required: true
		},
	},
	data ()
	{
		return {
			currentIblockName: null,
			allCnt: 0,
			doneCnt: 0,
		};
	},
	computed: {
		progressStyles()
		{
			let width = 0;
			if (this.allCnt > 0)
			{
				width = Math.round((this.doneCnt / this.allCnt) * 100);
			}

			return {
				width: width + '%'
			};
		}
	},
	created()
	{
		(new ProductSettingsUpdater({
			settings: this.settings,
			events: {
				onProgress: (data) => {
					this.currentIblockName = data.currentIblockName;
					this.allCnt = data.allCnt;
					this.doneCnt = data.doneCnt;
				},
				onComplete: () => {
					this.$emit('complete');
				},
			}
		})).startOperation();
	},
	template: `
		<div >
			<div class="ui-progressbar ui-progressbar-column">
				<div style="font-weight: bold;" class="ui-progressbar-text-before">
					{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_UPDATE_TITLE}}				
				</div>
				<div class="ui-progressbar-track">
					<div :style="progressStyles" class="ui-progressbar-bar"></div>
				</div>
				<div class="ui-progressbar-text-after">
					{{doneCnt}} {{loc.CRM_CFG_C_SETTINGS_OUT_OF}} {{allCnt}}
				</div>
			</div>
			<div style="color: rgb(83, 92, 105); font-size: 12px;">
				{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_UPDATE_WAIT}}
				<div
					v-show="currentIblockName"
					style="padding-top: 10px;"
				>
					{{loc.CRM_CFG_C_SETTINGS_PRODUCT_SETTINGS_CURRENT_CATALOG}}: {{currentIblockName}}
				</div>
			</div>
		</div>
	`
});
