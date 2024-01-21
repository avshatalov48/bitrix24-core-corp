/**
 * @module bizproc/workflow/starter/catalog-step/component
 */
jn.define('bizproc/workflow/starter/catalog-step/component', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { StorageCache } = require('storage-cache');
	const { NotifyManager } = require('notify-manager');
	const { isNil } = require('utils/type');
	const { Duration } = require('utils/date/duration');
	const { PureComponent } = require('layout/pure-component');
	const { CatalogStepView } = require('bizproc/workflow/starter/catalog-step/view');

	class CatalogStepComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = { templates: null, cachedTemplates: null };
			this.selectedTemplate = this.props.selectedTemplate || null;
			this.isLoading = false;

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.cache = new StorageCache(
				'bizproc.workflow-starter.catalog-step',
				`catalog-${env.userId}-${props.documentType}`,
			);
			this.loadFromCache();

			this.handleSelectTemplate = this.handleSelectTemplate.bind(this);
		}

		loadFromCache()
		{
			const templates = this.cache.get();
			if (this.state.templates !== null || Object.keys(templates).length === 0)
			{
				return;
			}

			this.state.cachedTemplates = Object.values(templates);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('CatalogStepView:OnSelectTemplate', this.handleSelectTemplate);

			this.loadTemplates();
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('CatalogStepView:OnSelectTemplate', this.handleSelectTemplate);
		}

		handleSelectTemplate(template)
		{
			this.selectedTemplate = template;
			this.customEventEmitter.emit('CatalogStepComponent:OnSelectTemplate', [template]);
		}

		get isLoaded()
		{
			return this.state.templates !== null;
		}

		get isLoadedFromCache()
		{
			return this.state.cachedTemplates !== null;
		}

		loadTemplates()
		{
			if (this.isLoaded || this.isLoading)
			{
				return;
			}

			this.isLoading = true;
			let templates = [];

			BX.ajax.runAction('bizprocmobile.Workflow.loadTemplates', { data: { signedDocument: this.props.signedDocument } })
				.then((response) => {
					templates = (response.data && response.data.templates) || [];
				})
				.catch((response) => {
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
				})
				.finally(() => {
					this.isLoading = false;

					const preparedTemplates = templates.map((template) => {
						return {
							...template,
							formattedTime: this.getFormattedTime(template),
							isSelected: false,
							key: template.id,
							type: 'template',
						};
					});

					this.cache.set(preparedTemplates);
					this.setState({
						templates: preparedTemplates,
						cachedTemplates: preparedTemplates,
					});
				})
			;
		}

		getFormattedTime(template)
		{
			if (isNil(template.time) || template.time === '')
			{
				return '';
			}

			const duration = Duration.createFromSeconds(template.time);
			const roundedTime = this.roundTime({
				s: duration.getUnitPropertyModByFormat('s'),
				i: duration.getUnitPropertyModByFormat('i'),
				H: duration.getUnitPropertyModByFormat('H'),
				d: duration.getUnitPropertyModByFormat('d'),
				m: duration.getUnitPropertyModByFormat('m'),
				Y: duration.getUnitPropertyModByFormat('Y'),
			});

			if (roundedTime.Y !== 0)
			{
				return (new Duration(roundedTime.Y * Duration.getLengthFormat().YEAR)).format('Y');
			}

			if (roundedTime.m !== 0)
			{
				return (new Duration(roundedTime.m * Duration.getLengthFormat().MONTH)).format('m');
			}

			if (roundedTime.d !== 0)
			{
				return (new Duration(roundedTime.d * Duration.getLengthFormat().DAY)).format('d');
			}

			if (roundedTime.H !== 0)
			{
				return (new Duration(roundedTime.H * Duration.getLengthFormat().HOUR)).format('H');
			}

			if (roundedTime.i !== 0)
			{
				return (new Duration(roundedTime.i * Duration.getLengthFormat().MINUTE)).format('i');
			}

			return duration.format('s');
		}

		/**
		 * @param {{s: number, i: number, H: number, d: number, m: number, Y: number}} time
		 * @return {{s: number, i: number, H: number, d: number, m: number, Y: number}}
		 */
		roundTime(time)
		{
			const seconds = time.s;
			const minutes = (time.i !== 0 && seconds >= 30) ? time.i + 1 : time.i;
			const hours = (time.H !== 0 && minutes >= 30) ? time.H + 1 : time.H;
			const days = (time.d !== 0 && hours >= 12) ? time.d + 1 : time.d;
			const months = (time.m !== 0 && days >= 15) ? time.m + 1 : time.m;
			const years = (time.Y !== 0 && months >= 6) ? time.Y + 1 : time.Y;

			return { s: seconds, i: minutes, H: hours, d: days, m: months, Y: years };
		}

		render()
		{
			return new CatalogStepView({
				uid: this.uid,
				layout: this.props.layout,
				isLoaded: this.isLoaded || this.isLoadedFromCache,
				templates: this.isLoaded ? this.state.templates : this.state.cachedTemplates,
				selectedTemplate: this.selectedTemplate,
			});
		}
	}

	module.exports = { CatalogStepComponent };
});
