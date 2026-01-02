import { ajax as Ajax, Dom } from 'main.core';
import type { PopupOptions } from 'main.popup';
import { Popup } from '../../layout/popup';
import { Button, ButtonState, ButtonSize, ButtonColor } from 'ui.buttons';
import '../../css/csv-errors-popup.css';

// noinspection JSUnusedGlobalSymbols
export const FileErrorsPopup = {
	emits: ['close', 'saveIgnoringErrors'],
	props: {
		errors: {
			type: Array,
			required: true,
		},
		isEditMode: {
			type: Boolean,
			required: true,
		},
		isSavingMode: {
			type: Boolean,
			required: true,
		},
	},
	data()
	{
		return {
			reportDownloadLink: null,
		};
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				width: 830,
				closeIcon: false,
				noAllPaddings: true,
				overlay: true,
			};
		},
		title(): string
		{
			if (!this.isSavingMode)
			{
				if (this.errors.length >= 200)
				{
					return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TITLE_MANY');
				}

				return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TITLE');
			}

			if (this.errors.length <= 200)
			{
				return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TITLE');
			}

			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TITLE_STOPPED_UPDATING')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TITLE_STOPPED_CREATING')
			;
		},
		hintContent(): string
		{
			if (this.errors.length > 200)
			{
				if (this.isEditMode)
				{
					return this.prepareHintContent('DATASET_IMPORT_CSV_ERROR_POPUP_HINT_UPDATING_MANY_ERRORS');
				}

				return this.prepareHintContent('DATASET_IMPORT_CSV_ERROR_POPUP_HINT_CREATION_MANY_ERRORS');
			}

			if (this.isSavingMode)
			{
				if (this.isEditMode)
				{
					return this.prepareHintContent('DATASET_IMPORT_CSV_ERROR_POPUP_HINT_UPDATING');
				}

				return this.prepareHintContent('DATASET_IMPORT_CSV_ERROR_POPUP_HINT_CREATION');
			}

			return this.prepareHintContent('DATASET_IMPORT_CSV_ERROR_POPUP_HINT_CHECKING');
		},
		errorsCountText(): string
		{
			return this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_SUMMARY')
				.replace('[wrapper]', '<span class="errors-counter">')
				.replace('#COUNT#', this.errors.length > 200 ? '200+' : this.errors.length)
				.replace('[/wrapper]', '</span>')
			;
		},
	},
	methods: {
		onClose()
		{
			this.$emit('close');
		},
		onIgnoreErrorsClick()
		{
			this.$emit('saveIgnoringErrors');
		},
		onDownloadLogButtonClick()
		{
			this.downloadLogButton.setState(ButtonState.WAITING);

			if (this.reportDownloadLink)
			{
				this.downloadLogButton.setState(null);
				this.downloadLog();

				return;
			}

			Ajax.runAction(
				'biconnector.externalsource.dataset.logErrorsIntoFile',
				{
					data: {
						fields: this.$store.state.config,
						type: 'csv',
					},
				},
			)
				.then((response) => {
					this.downloadLogButton.setState(null);
					const blob = new Blob([response.data], { type: 'text/html' });
					this.reportDownloadLink = window.URL.createObjectURL(blob);
					this.downloadLog();
				})
				.catch(() => {
					this.downloadLogButton.setState(null);
				})
			;
		},
		downloadLog()
		{
			const link = document.createElement('a');
			link.href = this.reportDownloadLink;
			link.download = `${this.$store.state.config.datasetProperties.name ?? 'csv_dataset'}_errors.html`;
			Dom.append(link, document.body);
			link.click();
			Dom.remove(link);
		},
		prepareHintContent(phraseCode: string): string
		{
			const message = this.$Bitrix.Loc.getMessage(phraseCode);

			return message
				.replace('[link]', '<a onclick="top.BX.Helper.show(`redirect=detail&code=23779844`)">')
				.replace('[/link]', '</a>')
			;
		},
	},
	mounted()
	{
		this.downloadLogButton = new Button({
			size: ButtonSize.EXTRA_SMALL,
			color: ButtonColor.LIGHT,
			noCaps: true,
			onclick: this.onDownloadLogButtonClick,
			text: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_DOWNLOAD_LOG'),
			className: 'popup-errors-log-button',
		});
		this.downloadLogButton.renderTo(this.$refs.summaryBlock);
	},
	beforeUnmount()
	{
		window.URL.revokeObjectURL(this.reportDownloadLink);
		this.reportDownloadLink = null;
	},
	components: {
		Popup,
	},
	// language=Vue
	template: `
		<Popup id="generic" @close="this.onClose" :options="popupOptions" wrapper-class="errors-popup">
			<h3 class="popup-header">{{ title }}</h3>
			<div class="ui-alert ui-alert-primary popup-hint">
				<span v-html="hintContent"></span>
			</div>

			<div class="popup-errors-summary" ref="summaryBlock">
				<div
					class="popup-errors-count"
					v-html="errorsCountText"
				></div>
			</div>

			<div 
				class="popup-error-header-wrapper"
				:style="errors.length >= 6 ? {'padding-right': '13px'} : {}"
			>
				<table class="popup-error-header">
					<tbody>
					<tr>
					<td class="popup-error-header-cell cell-error-number">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TABLE_HEADER_ERROR_NUMBER') }}
					</td>
					<td class="popup-error-header-cell cell-error-message">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TABLE_HEADER_ERROR_MESSAGE') }}
					</td>
					<td class="popup-error-header-cell cell-error-line">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TABLE_HEADER_LINE') }}
					</td>
					<td class="popup-error-header-cell cell-error-column-name">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_TABLE_HEADER_COL_NAME') }}
					</td>
					</tr>
					</tbody>
				</table>
			</div>

			<div 
				class="popup-error-table-wrapper"
				:style="errors.length >= 6 ? {'padding-right': '3px'} : {}"
			>
				<div class="popup-error-table-scroll">
					<table class="popup-error-table">
						<tr v-for="(error, index) in errors">
							<template v-if="index < 200">
								<td class="popup-error-table-row-cell cell-error-number">{{ index + 1 }}</td>
								<td class="popup-error-table-row-cell cell-error-message">{{ error.errorMessage }}</td>
								<td class="popup-error-table-row-cell cell-error-line">{{ error.lineNumber }}</td>
								<td class="popup-error-table-row-cell cell-error-column-name" :title="error.columnName">{{ error.columnName }}</td>
							</template>
						</tr>
					</table>
				</div>
			</div>

			<div class="popup-buttons-wrapper">
				<template v-if="isSavingMode">
					<template v-if="errors.length > 200">
						<button @click="this.onClose" class="ui-btn ui-btn-md ui-btn-primary">
							{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_BUTTON_CLOSE') }}
						</button>
					</template>
					<template v-else>
						<button v-if="isEditMode" @click="this.onClose" class="ui-btn ui-btn-md ui-btn-primary">
							{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_BUTTON_STOP_UPDATE') }}
						</button>
						<button v-else @click="this.onClose" class="ui-btn ui-btn-md ui-btn-primary">
							{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_BUTTON_STOP') }}
						</button>

						<button @click="this.onIgnoreErrorsClick" class="ui-btn ui-btn-md ui-btn-light-border">
							{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_BUTTON_IGNORE') }}
						</button>
					</template>
				</template>
				<template v-else>
					<button @click="this.onClose" class="ui-btn ui-btn-md ui-btn-primary">
						{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_BUTTON_CLOSE') }}
					</button>
				</template>
			</div>
		</Popup>
	`,
};
