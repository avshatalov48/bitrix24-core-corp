/**
 * @module crm/required-fields/required-backdrop
 */

jn.define('crm/required-fields/required-backdrop', (require, exports, module) => {
	const { EntityManager } = require('layout/ui/entity-editor/manager');

	/**
	 * @class RequiredFieldsBackdrop
	 */
	class RequiredFieldsBackdrop extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;

			/** @type {EntityEditor} */
			this.refEntityManager = null;
			this.isSaved = false;
			this.onSave = this.handlerOnSave.bind(this);
		}

		componentDidMount()
		{
			this.layout.enableNavigationBarBorder(false);
			this.layout.setTitle({ text: BX.message('CRM_REQUIRED_FIELDS') });
			this.layout.setRightButtons([{
				name: BX.message('CRM_REQUIRED_SAVE'),
				type: 'text',
				color: '#2066b0',
				callback: this.onSave,
			}]);
			this.layout.setListener((eventName) => {
				if (eventName === 'onViewHidden')
				{
					this.handlerOnClose();
				}
			});
		}

		handlerOnSave()
		{
			if (!this.refEntityManager.validate())
			{
				return;
			}

			this.refEntityManager
				.getValuesToSave()
				.then((fields) => {
					this.isSaved = true;

					this.layout.close(() => {
						const { onSave } = this.props;
						if (typeof onSave === 'function')
						{
							onSave(fields);
						}
					});
				})
				.catch(() => this.layout.close());
		}

		handlerOnClose()
		{
			const { onClose } = this.props;
			if (!this.isSaved && typeof onClose === 'function')
			{
				onClose();
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
					},
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
				},
				this.renderEditor(),
			);
		}

		renderEditor()
		{
			const { editorData } = this.props;
			return EntityManager.create({
				refCallback: (ref) => this.refEntityManager = ref,
				componentId: 'crm.tabs',
				layout: this.layout,
				editorProps: {
					...editorData,
					ENABLE_BOTTOM_PANEL: true,
					IS_TOOL_PANEL_ALWAYS_VISIBLE: true,
				},
			});
		}
	}

	module.exports = { RequiredFieldsBackdrop };
});
