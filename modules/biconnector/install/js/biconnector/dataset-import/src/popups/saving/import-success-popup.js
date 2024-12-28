import { ajax as Ajax, Dom } from 'main.core';
import { SavingPopup } from './saving-popup';
import '../../css/save-progress-popup.css';

export const ImportSuccessPopup = {
	emits: ['click', 'oneMoreClick'],
	props: {
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: false,
			default: '',
		},
		datasetId: {
			type: Number,
			required: true,
		},
		showMoreButton: {
			type: Boolean,
			required: false,
			default: false,
		}
	},
	computed: {},
	methods: {
		onButtonClick()
		{
			Dom.addClass(this.$refs.openDatasetButton, 'ui-btn-wait');
			Ajax.runAction(
				'biconnector.externalsource.dataset.getEditUrl',
				{
					data: {
						id: this.datasetId,
					},
				},
			)
				.then((response) => {
					const link = response.data;
					if (link)
					{
						window.open(link, '_blank').focus();
						Dom.removeClass(this.$refs.openDatasetButton, 'ui-btn-wait');
						this.$emit('click');
					}
				})
				.catch((response) => {
					Dom.removeClass(this.$refs.openDatasetButton, 'ui-btn-wait');
					this.$emit('click');
				});
		},
		onOneMoreButtonClick()
		{
			Dom.addClass(this.$refs.oneMoreButton, 'ui-btn-wait');
			this.$emit('oneMoreClick');
		},
	},
	components: {
		SaveProgressPopup: SavingPopup,
	},
	// language=Vue
	template: `
		<SaveProgressPopup
			:title="title"
			:description="description"
		>
			<template v-slot:icon>
				<div class="dataset-save-progress-popup__success-logo"></div>
			</template>
			<template v-slot:buttons>
				<a class="ui-btn ui-btn-md ui-btn-primary" @click="onButtonClick" ref="openDatasetButton">
					{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_BUTTON') }}
				</a>
				<a class="ui-btn ui-btn-md ui-btn-light-border" @click="onOneMoreButtonClick" ref="oneMoreButton" v-if="showMoreButton">
					{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_SUCCESS_POPUP_ONE_MORE_BUTTON') }}
				</a>
			</template>
		</SaveProgressPopup>
	`,
};
