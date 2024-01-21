/**
 * @module lists/entity-detail/detail-tab
*/
jn.define('lists/entity-detail/detail-tab', (require, exports, module) => {
	const { NotifyManager } = require('notify-manager');
	const { EntityManager } = require('lists/entity-detail/entity-manager');
	const { Tab } = require('lists/entity-detail/tab');

	class DetailTab extends Tab
	{
		constructor(props) {
			super(props);

			this.state.editorConfig = null;

			// eslint-disable-next-line no-undef
			this.entityId = props.entityId || 0;
			this.sectionId = props.sectionId || 0;

			this.reRenderEditorFlag = false;
			this.hasFieldsToRender = false;
			this.readOnlyTrustedValues = {};
			/** @type {EntityEditor} */
			this.entityEditorRef = null;

			this.isEntityLoaded = false;
			this.isLoading = false;
		}

		render()
		{
			this.reRenderEditorFlag = !this.reRenderEditorFlag; // hack to rerender editor

			return View(
				{},
				View(
					{
						style: {
							justifyContent: 'center',
							alignItems: 'center',
							opacity: 0.5,
						},
					},
					this.renderLoader(),
				),
				this.reRenderEditorFlag && this.renderEditor(),
				!this.reRenderEditorFlag && this.renderEditor(),
			);
		}

		renderEditor()
		{
			if (this.state.editorConfig)
			{
				this.entityEditorRef = EntityManager.create({
					layout: this.layout,
					editorProps: this.state.editorConfig,
					showBottomPadding: true,

					header: this.iBlock.description && this.entityId <= 0 ? this.iBlock.description : null,
					hasRenderedFields: this.hasFieldsToRender,
				});

				return this.entityEditorRef;
			}

			return null;
		}

		renderLoader()
		{
			if (!this.isEntityLoaded)
			{
				return Loader({
					style: { width: 75, height: 75 },
					animating: true,
				});
			}

			return null;
		}

		load()
		{
			this.loadEntity();
		}

		loadEntity()
		{
			if (this.isEntityLoaded === true || this.isLoading === true)
			{
				return;
			}

			this.isLoading = true;

			const data = {
				id: this.entityId,
				sectionId: this.sectionId,
				iBlockId: this.iBlock.id,
			};

			BX.ajax.runAction('listsmobile.EntityDetails.loadEntity', { data })
				.then(this.onSuccessLoadEntity.bind(this))
				.catch(this.onFailedLoadEntity.bind(this))
				.finally(() => {
					this.isEntityLoaded = true;
					this.customEventEmitter.emit('DetailTab::onAfterEntityLoad');
					this.isLoading = false;
				})
			;
		}

		onSuccessLoadEntity(response)
		{
			const editorConfig = response.data.editor;
			const entityFields = editorConfig.ENTITY_FIELDS ?? [];
			this.hasFieldsToRender = (!Array.isArray(entityFields) && Object.keys(entityFields).length > 0);
			if (this.hasFieldsToRender)
			{
				this.readOnlyTrustedValues = {};
				Object.entries(entityFields).forEach(([key, property]) => {
					if (property.custom.isTrusted)
					{
						this.readOnlyTrustedValues[key] = editorConfig.ENTITY_DATA[key];
					}
				});
			}
			this.setState({ editorConfig });
		}

		onFailedLoadEntity(response)
		{
			NotifyManager.showErrors(response.errors);
		}

		validate()
		{
			return new Promise((resolve, reject) => {
				if (!this.entityEditorRef || this.entityEditorRef.validate())
				{
					resolve(true);

					return;
				}

				reject();
			});
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				if (this.entityEditorRef)
				{
					this.entityEditorRef.getValuesToSave()
						.then((fields) => {
							resolve(Object.assign(this.readOnlyTrustedValues, fields));
						})
						.catch((errors) => reject(errors))
					;

					return;
				}

				resolve({});
			});
		}

		setResult(result)
		{
			return new Promise((resolve, reject) => {
				if (result.data.editor)
				{
					this.entityId = result.data.id;
					this.onSuccessLoadEntity(result);
				}

				resolve();
			});
		}
	}

	module.exports = { DetailTab };
});
