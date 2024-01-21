/**
 * @module lists/element-creation-guide/detail-step
 */
jn.define('lists/element-creation-guide/detail-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('lists/wizard/progress-bar-number');
	const { EventEmitter } = require('event-emitter');
	const { DetailStepComponent } = require('lists/element-creation-guide/detail-step/component');
	const { FocusManager } = require('layout/ui/fields/focus-manager');
	const { NotifyManager } = require('notify-manager');

	class DetailStep extends WizardStep
	{
		/**
		 * @param {Object} props
		 * @param {String} props.title
		 * @param {String} props.uid
		 * @param {Number} props.stepNumber
		 * @param {Number} props.totalSteps
		 * @param {Number} props.iBlockId
		 * @param {Number} props.elementId
		 */
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.elementId = props.elementId;
			this.iBlockId = props.iBlockId;

			this.prevElementId = null;
			this.prevIBlockId = null;

			this.editorConfig = null;
			this.sign = null;

			this.prevEditorConfig = null;

			/** @type {DetailStepComponent | null} */
			this.component = null;
			this.componentRefCallback = (ref) => {
				this.component = ref;
			};

			this.subscribeOnEvents();
		}

		get isLoaded()
		{
			return this.editorConfig !== null;
		}

		getCurrentTime()
		{
			return Math.round(Date.now() / 1000);
		}

		subscribeOnEvents()
		{
			this.customEventEmitter
				.on('DetailStepComponent:OnAfterLoad', (sign, editorConfig) => {
					this.editorConfig = editorConfig;
					this.sign = sign;
					if (this.prevEditorConfig === null)
					{
						this.prevEditorConfig = this.editorConfig;
					}

					this.stepAvailabilityChangeCallback(true);
				})
				.on('DetailStepComponent:onFieldChangeState', () => {
					this.customEventEmitter.emit('DetailStep:onFieldChangeState');
				})
			;
		}

		get isPreviousSelectedDetailContent()
		{
			return this.prevIBlockId === this.iBlockId && this.prevElementId === this.elementId;
		}

		onEnterStep()
		{
			super.onEnterStep();

			if (this.isPreviousSelectedDetailContent)
			{
				this.editorConfig = this.prevEditorConfig;
				this.stepAvailabilityChangeCallback(true);
			}

			if (!this.isLoaded)
			{
				this.sign = null;
				this.stepAvailabilityChangeCallback(false);
			}
		}

		async onMoveToBackStep(stepId)
		{
			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				this.prevElementId = this.elementId;
				this.prevIBlockId = this.iBlockId;
				this.prevEditorConfig = this.editorConfig;

				this.editorConfig = null;

				this.component.getData()
					.then(
						(fields) => {
							this.prevEditorConfig.ENTITY_DATA = Object.assign(this.prevEditorConfig.ENTITY_DATA, fields);
						},
					)
					.catch((errors) => {
						console.error(errors);
					})
					.finally(() => {
						NotifyManager.hideLoadingIndicator(true);
						resolve();
					})
				;
			});
		}

		updateProps(props)
		{
			if (this.isLoaded)
			{
				this.prevElementId = this.elementId;
				this.prevIBlockId = this.iBlockId;
				this.prevEditorConfig = this.editorConfig;

				this.editorConfig = null;
			}

			this.elementId = props.elementId === undefined ? this.elementId : props.elementId;
			this.iBlockId = props.iBlockId === undefined ? this.iBlockId : props.iBlockId;
			this.startTime = props.startTime === undefined ? this.startTime : props.startTime;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: false,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DETAIL_STEP_TITLE'),
				},
				number: this.props.stepNumber,
				count: this.props.totalSteps,
			};
		}

		getTitle()
		{
			return this.props.title || '';
		}

		getNextStepButtonText()
		{
			return Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_DETAIL_STEP_NEXT_STEP_TITLE');
		}

		isNextStepEnabled()
		{
			return this.isLoaded;
		}

		isPrevStepEnabled()
		{
			return this.isLoaded;
		}

		async onMoveToNextStep(stepId)
		{
			await NotifyManager.showLoadingIndicator();

			return new Promise((resolve) => {
				const failedMove = (errors) => {
					console.error(errors);
					NotifyManager.hideLoadingIndicator(false);

					if (Array.isArray(errors))
					{
						NotifyManager.showErrors(errors);
					}

					resolve({ finish: false, next: false });
				};

				const successMove = () => {
					NotifyManager.hideLoadingIndicator(true);
					resolve({ finish: true, next: true });
				};

				FocusManager.blurFocusedFieldIfHas()
					.then(() => this.component.validate())
					.then(() => this.component.getData())
					.then((fields) => this.startCreateProcess(fields))
					.then(() => successMove())
					.catch((errors) => failedMove(errors))
				;
			});
		}

		startCreateProcess(fields)
		{
			// eslint-disable-next-line no-param-reassign
			fields.IBLOCK_ID = this.iBlockId;

			return new Promise((resolve, reject) => {
				BX.ajax.runAction(
					'listsmobile.ElementCreationGuide.createElement',
					{
						data: {
							sign: this.sign,
							fields,
							timeToStart: this.startTime === undefined ? null : this.getCurrentTime() - this.startTime,
						},
					},
				)
					.then(() => resolve())
					.catch((response) => reject(response.errors))
				;
			});
		}

		createLayout(props)
		{
			return new DetailStepComponent({
				layout: this.props.layout,
				uid: this.uid,
				iBlockId: this.iBlockId,
				elementId: this.elementId,
				editorConfig: this.isPreviousSelectedDetailContent ? this.prevEditorConfig : this.editorConfig,
				ref: this.componentRefCallback,
			});
		}
	}

	module.exports = { DetailStep };
});
