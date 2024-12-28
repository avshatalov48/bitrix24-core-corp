import { ajax as Ajax, Tag, Type } from 'main.core';
import { Popup as MainPopup, PopupManager } from 'main.popup';
import type { AnalyticsOptions } from 'ui.analytics';
import { sendData } from 'ui.analytics';
import { SidePanel } from 'ui.sidepanel';
import { Button, ButtonColor } from 'ui.buttons';

// language=Vue
export const BaseApp = {
	props: {
		appParams: {
			type: Object,
			required: false,
			default: {},
		},
	},
	data()
	{
		return {
			steps: {},
			shownPopups: {},
			isChanged: false,
			isSaveComplete: false,
			lastChangedStep: null,
		};
	},
	computed: {
		sourceCode()
		{
			return '';
		},
		loadParams()
		{
			return {};
		},
		saveParams()
		{
			return {};
		},
		isEditMode()
		{
			return false;
		},
		datasetId()
		{
			return this.$store.state.config.datasetProperties.id;
		},
		isSaveEnabled(): boolean
		{
			return !(this.$store.getters.areNoRowsVisible) && this.isValidatedForSave && !this.previewError;
		},
		isValidatedForSave(): boolean
		{
			return true;
		},
		unsavedChangesPopupTitle(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TITLE_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TITLE')
			;
		},
		unsavedChangesPopupText(): string
		{
			return this.isEditMode
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TEXT_EDIT')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_UNSAVED_CHANGES_TEXT')
			;
		},
	},
	mounted()
	{
		this.$Bitrix.eventEmitter.subscribe('biconnector:dataset-import:createButtonClick', this.onSaveButtonClick);
		this.$Bitrix.eventEmitter.subscribe('biconnector:dataset-import:cancelButtonClick', this.onCancelButtonClick);

		const slider = SidePanel.Instance.getTopSlider();
		if (slider)
		{
			top.BX.addCustomEvent(slider, 'SidePanel.Slider:onClose', this.onSliderClose);

			addEventListener('beforeunload', (event) => {
				top.BX.removeCustomEvent(slider, 'SidePanel.Slider:onClose', this.onSliderClose);
			});
		}
	},
	beforeUnmount()
	{
		this.$Bitrix.eventEmitter.unsubscribe('biconnector:dataset-import:createButtonClick', this.onSaveButtonClick);
		this.$Bitrix.eventEmitter.unsubscribe('biconnector:dataset-import:cancelButtonClick', this.onCancelButtonClick);
	},
	methods: {
		markAsChanged()
		{
			this.isChanged = true;
		},
		onSliderClose(event)
		{
			if (!this.isChanged)
			{
				if (!this.isSaveComplete)
				{
					this.sendAnalytics({
						event: this.isEditMode ? 'edit_end' : 'creation_end',
						status: 'error',
					});
				}

				return;
			}

			event.denyAction();

			if (PopupManager.getPopupById('unsaved'))
			{
				return;
			}

			const continueButton = new Button({
				color: ButtonColor.PRIMARY,
				text: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONTINUE_IMPORT'),
				events: {
					click() {
						popup.destroy();
					},
				},
			});

			const closeButton = new Button({
				color: ButtonColor.LINK,
				text: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_CONFIRM_CANCEL_IMPORT'),
				events: {
					click: () => {
						this.isChanged = false;
						this.closeApp();
					},
				},
			});

			const popupHeader = this.unsavedChangesPopupTitle;
			const popupText = this.unsavedChangesPopupText;

			const popup = new MainPopup({
				id: 'unsaved',
				content: Tag.render`
					<div class="generic-popup">
						<h3 class="generic-popup__header">${popupHeader}</h3>
						<div class="generic-popup__content">
							${popupText}
						</div>
						<div class="generic-popup__buttons-wrapper">
							${continueButton.render()}
							${closeButton.render()}
						</div>
					</div>
				`,
				width: 440,
				noAllPaddings: true,
				autoHide: false,
				fixed: true,
				overlay: true,
			});

			popup.show();
		},
		closeApp()
		{
			SidePanel.Instance.getTopSlider().close();
		},
		toggleStepState(step: string, disabled: ?boolean = null)
		{
			if (!this.steps[step])
			{
				return;
			}

			if (Type.isNull(disabled))
			{
				this.steps[step].disabled = !Boolean(this.steps[step].disabled);
			}
			else
			{
				this.steps[step].disabled = disabled;
			}
		},
		togglePopup(step: string, shown: ?boolean = null)
		{
			if (Type.isNull(shown))
			{
				this.shownPopups[step] = !Boolean(this.shownPopups[step]);
			}
			else
			{
				this.shownPopups[step] = shown;
			}
		},
		loadDataset()
		{
			Ajax.runAction('biconnector.externalsource.dataset.view', {
				data: {
					type: this.sourceCode,
					fields: this.loadParams,
				},
			})
				.then((response) => {
					this.onLoadSuccess(response);
				})
				.catch((error) => {
					this.onLoadError(error);
				});
		},
		onStepValidation(step, validationResult)
		{
			if (!this.steps[step])
			{
				return;
			}

			this.steps[step].valid = validationResult;
		},
		onSaveButtonClick()
		{
			if (!this.onSaveStart())
			{
				return;
			}

			if (this.isEditMode)
			{
				this.updateDataset();
			}
			else
			{
				this.saveDataset();
			}
		},
		onCancelButtonClick()
		{
			this.closeApp();
		},
		saveDataset()
		{
			Ajax.runAction('biconnector.externalsource.dataset.add', {
				data: {
					type: this.sourceCode,
					fields: this.saveParams,
				},
			})
				.then((response) => {
					this.onSaveEnd(response);
				})
				.catch((error) => {
					this.onSaveError();
				});
		},
		updateDataset()
		{
			Ajax.runAction('biconnector.externalsource.dataset.update', {
				data: {
					id: this.datasetId,
					type: this.sourceCode,
					fields: this.saveParams,
				},
			})
				.then((response) => {
					this.onSaveEnd(response);
				})
				.catch((error) => {
					this.onSaveError();
				});
		},
		onSaveStart()
		{
			return true;
		},
		onSaveEnd()
		{
		},
		onSaveError()
		{
		},
		onLoadStart()
		{
		},
		onLoadSuccess(response)
		{
		},
		onLoadError(response)
		{
		},
		reload()
		{
			window.location.reload();
		},
		sendAnalytics(params: AnalyticsOptions)
		{
			if (this.sourceCode)
			{
				sendData({
					...this.getBaseAnalyticsParams(),
					...params,
				});
			}
		},
		getBaseAnalyticsParams()
		{
			return {
				tool: 'BI_Builder',
				c_section: 'BI_Builder',
				category: this.sourceCode.toUpperCase(),
			};
		},
	},
};
