/**
 * @module calendar/sync-page/wizard/factory
 */
jn.define('calendar/sync-page/wizard/factory', (require, exports, module) => {
	const { Loc } = require('loc');
	const { SyncWizard } = require('calendar/sync-page/wizard/sync-wizard');

	/**
	 * @class SyncWizardFactory
	 */
	class SyncWizardFactory
	{
		constructor(props)
		{
			this.props = props;
			this.syncWizardRef = null;
		}

		// eslint-disable-next-line consistent-return
		open(additionalProps = {})
		{
			const type = this.props.type;
			if (!providerTypes[type])
			{
				return null;
			}

			const { firstStage, secondStage, thirdStage } = stageNames[type];

			const props = {
				...this.props,
				firstStage,
				secondStage,
				thirdStage,
			};

			if (additionalProps.accountName)
			{
				props.title = additionalProps.accountName;
			}

			const title = wizardTitle[type];

			// eslint-disable-next-line promise/catch-or-return
			PageManager.openWidget('layout', { title })
				.then((widget) => {
					widget.showComponent(
						new SyncWizard({
							...props,
							layoutWidget: widget,
							ref: (ref) => {
								this.syncWizardRef = ref;
							},
						}),
					);
				});
		}

		setErrorState()
		{
			if (this.syncWizardRef)
			{
				this.syncWizardRef.setErrorState();
			}
		}

		setConnectionCreatedState()
		{
			if (this.syncWizardRef)
			{
				this.syncWizardRef.setConnectionCreatedState();
			}
		}
	}

	const stageNames = {
		google: {
			firstStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_GOOGLE_FIRST_STAGE'),
			secondStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_GOOGLE_SECOND_STAGE'),
			thirdStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_GOOGLE_THIRD_STAGE'),
		},
		office365: {
			firstStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_OFFICE365_FIRST_STAGE'),
			secondStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_OFFICE365_SECOND_STAGE'),
			thirdStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_OFFICE365_THIRD_STAGE'),
		},
		icloud: {
			firstStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_ICLOUD_FIRST_STAGE'),
			secondStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_ICLOUD_SECOND_STAGE'),
			thirdStage: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_ICLOUD_THIRD_STAGE'),
		},
	};

	const providerTypes = {
		google: 'google',
		office365: 'office365',
		icloud: 'icloud',
	};

	const wizardTitle = {
		google: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_TITLE_GOOGLE'),
		office365: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_TITLE_OFFICE365'),
		icloud: Loc.getMessage('M_CALENDAR_SYNC_WIZARD_TITLE_ICLOUD'),
	};

	module.exports = { SyncWizardFactory };
});
