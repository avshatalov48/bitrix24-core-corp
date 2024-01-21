/**
 * @module lists/element-creation-guide/catalog-step
 */
jn.define('lists/element-creation-guide/catalog-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { WizardStep } = require('layout/ui/wizard/step');
	const { ProgressBarNumber } = require('lists/wizard/progress-bar-number');
	const { EventEmitter } = require('event-emitter');
	const { CatalogStepComponent } = require('lists/element-creation-guide/catalog-step/component');

	class CatalogStep extends WizardStep
	{
		/**
		 * @param {Object} props
		 * @param {String} props.title
		 * @param {String} props.uid
		 * @param {Number} props.stepNumber
		 * @param {Number} props.totalSteps
		 * @param {Object} props.layout
		 */
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.selectedItem = null;

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter.on('CatalogStepComponent:OnSelectItem', (item) => {
				this.selectedItem = item;
				this.customEventEmitter.emit('CatalogStep:OnSelectItem', [this.selectedItem]);
				this.stepAvailabilityChangeCallback(true);
			});
		}

		get hasSelectedItem()
		{
			return this.selectedItem !== null;
		}

		renderNumberBlock()
		{
			return new ProgressBarNumber({
				isCompleted: this.hasSelectedItem,
				number: this.props.stepNumber,
			});
		}

		getProgressBarSettings()
		{
			return {
				...super.getProgressBarSettings(),
				isEnabled: true,
				title: {
					text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CATALOG_STEP_TITLE'),
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
			return Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_CATALOG_STEP_NEXT_STEP_TITLE');
		}

		isNextStepEnabled()
		{
			return this.hasSelectedItem;
		}

		isPrevStepEnabled()
		{
			return false;
		}

		createLayout(props)
		{
			return new CatalogStepComponent({
				uid: this.uid,
				selectedItem: this.selectedItem,
				layout: this.props.layout,
			});
		}
	}

	module.exports = { CatalogStep };
});
