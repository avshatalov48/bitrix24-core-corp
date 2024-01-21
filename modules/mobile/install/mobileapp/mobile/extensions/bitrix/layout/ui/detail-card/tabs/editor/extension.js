/**
 * @module layout/ui/detail-card/tabs/editor
 */
jn.define('layout/ui/detail-card/tabs/editor', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Tab } = require('layout/ui/detail-card/tabs');
	const { TabType } = require('layout/ui/detail-card/tabs/factory/type');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { FocusManager } = require('layout/ui/fields/focus-manager');

	/**
	 * @class EditorTab
	 */
	class EditorTab extends Tab
	{
		constructor(props)
		{
			super(props);

			/** @type {EntityEditor} */
			this.editorRef = null;

			this.refCallback = (ref) => {
				this.editorRef = ref;
			};
		}

		componentDidMount()
		{
			this.customEventEmitter
				.on('UI.EntityEditor::onSetEditMode', this.handleModeChange.bind(this, true))
				.on('UI.EntityEditor::onSetViewMode', this.handleModeChange.bind(this, false))
				.on('UI.EntityEditor.Field::onChangeState', this.handleFieldChange.bind(this));
		}

		handleFieldChange(...eventArgs)
		{
			this.customEventEmitter.emit('DetailCard::onTabChange', [this.getId(), ...eventArgs]);
		}

		handleModeChange(...eventArgs)
		{
			this.customEventEmitter.emit('DetailCard::onTabEdit', [this.getId(), ...eventArgs]);
		}

		getType()
		{
			return TabType.EDITOR;
		}

		getData()
		{
			return new Promise((resolve) => {
				if (this.editorRef)
				{
					this.editorRef
						.getValuesToSave()
						.then((fields) => resolve(fields))
						.catch(console.error);
				}
				else
				{
					resolve({});
				}
			});
		}

		validate()
		{
			return new Promise((resolve) => {
				if (this.editorRef)
				{
					resolve(this.editorRef.validate());
				}
				else
				{
					resolve(true);
				}
			});
		}

		scrollTop(animate = true)
		{
			if (this.editorRef)
			{
				this.editorRef.scrollTop(animate);
			}
		}

		getEntityEditor(editorProps, refresh = false)
		{
			const loadFromModel = refresh || !this.editorRef || this.editorRef.getEntityId() !== editorProps.ENTITY_ID;
			const { payload, onScroll, externalFloatingButton } = this.props;

			return View(
				{
					style: {
						flex: 1,
					},
					onClick: () => FocusManager.blurFocusedFieldIfHas(),
				},
				EntityManager.create({
					uid: this.uid,
					onScroll,
					editorProps,
					loadFromModel,
					componentId: 'detail-card',
					refCallback: this.refCallback,
					layout: this.layout,
					showBottomPadding: externalFloatingButton,
					payload,
				}),
			);
		}

		renderResult()
		{
			const editorResult = this.state.result.editor || {};

			return View(
				{
					style: {
						flex: 1,
						backgroundColor: AppTheme.colors.bgPrimary,
					},
				},
				this.getEntityEditor(editorResult, true),
			);
		}
	}

	module.exports = { EditorTab };
});
