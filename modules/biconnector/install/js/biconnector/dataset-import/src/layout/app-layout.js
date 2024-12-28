import 'ui.pinner';

import '../css/app-layout.css';
import { Dom } from 'main.core';

export const AppLayout = {
	props: {
		saveLocked: {
			type: Boolean,
			required: false,
			default: false,
		},
		isEditMode: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	computed: {
		saveButtonClass()
		{
			return {
				'ui-btn-base-light': this.saveLocked,
				'app-root__button--blocked': this.saveLocked,
				'ui-btn-success': !this.saveLocked,
			};
		},
		saveButtonText()
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SAVE')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CREATE');
		},
	},
	mounted()
	{
		Dom.addClass(document.querySelector('body'), 'app-body');

		const buttonsPanel = this.$refs.buttonsPanel;
		if (buttonsPanel)
		{
			this.$root.$el.parentNode.appendChild(buttonsPanel);
			new BX.UI.Pinner(
				buttonsPanel,
				{
					fixBottom: true,
					fullWidth: true,
				},
			);
		}
	},
	methods: {
		onCreateButtonClick()
		{
			this.$Bitrix.eventEmitter.emit('biconnector:dataset-import:createButtonClick', {});
		},
		onCancelButtonClick()
		{
			this.$Bitrix.eventEmitter.emit('biconnector:dataset-import:cancelButtonClick', {});
		},
	},
	// language=Vue
	template: `
		<div class="app">
			<div class="app__block app__block--narrow">
				<slot name="left-panel"></slot>
			</div>
			<div class="app__block">
				<slot name="right-panel"></slot>
			</div>
	
			<div class="ui-button-panel-wrapper" ref="buttonsPanel">
				<div class="ui-button-panel">
					<div class="app-root__button-wrapper" :class="saveLocked ? 'app-root__button-wrapper--blocked' : ''">
						<button class="ui-btn ui-btn-md app-root__button" :class="saveButtonClass" @click="onCreateButtonClick" ref="saveButton">
							{{ saveButtonText }}
						</button>
					</div>
					<button class="ui-btn ui-btn-md ui-btn-link app-root__button" @click="onCancelButtonClick">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CANCEL') }}
					</button>
				</div>
			</div>
		</div>
	`,
};
