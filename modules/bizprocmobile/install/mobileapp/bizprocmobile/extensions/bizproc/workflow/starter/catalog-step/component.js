/**
 * @module bizproc/workflow/starter/catalog-step/component
 */
jn.define('bizproc/workflow/starter/catalog-step/component', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { StorageCache } = require('storage-cache');
	const { NotifyManager } = require('notify-manager');
	const { isNil } = require('utils/type');
	const { PureComponent } = require('layout/pure-component');
	const { CatalogStepView } = require('bizproc/workflow/starter/catalog-step/view');
	const { formatRoundedTime, roundTimeInSeconds } = require('bizproc/helper/duration');

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

			return formatRoundedTime(roundTimeInSeconds(template.time));
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
