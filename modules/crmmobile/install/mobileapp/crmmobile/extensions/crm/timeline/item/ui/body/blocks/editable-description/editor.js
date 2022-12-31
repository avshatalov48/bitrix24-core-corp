/**
 * @module crm/timeline/item/ui/body/blocks/editable-description/editor
 */
jn.define('crm/timeline/item/ui/body/blocks/editable-description/editor', (require, exports, module) => {

	const { Loc } = require('loc');
	const {
		Toolbar,
		ToolbarButton,
	} = require('crm/timeline/ui/toolbar');
	const { Textarea } = require('crm/timeline/ui/textarea');
	const { WidgetHeaderButton } = require('crm/timeline/ui/widget-header-button');

	/**
	 * @class TimelineItemBodyTextEditor
	 */
	class TimelineItemBodyTextEditor extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				text: props.text,
			};

			this.layout = props.layout;
			this.textInputRef = null;
			this.createButtonRef = null;

			this.saveButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('M_CRM_TIMELINE_SAVE'),
				loadingText: Loc.getMessage('M_CRM_TIMELINE_SAVING'),
				disabled: !this.isSaveAllowed(),
				onClick: () => this.save(),
			});
		}

		componentDidMount()
		{
			this.focus();
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
					resizableByKeyboard: true,
				},
				this.renderTextField(),
				this.renderClearIcon(),
				// this.renderToolbar(),
			);
		}

		renderTextField()
		{
			return View(
				{
					style: {
						flexGrow: 1,
						paddingRight: 48,
					}
				},
				Textarea({
					ref: (ref) => this.textInputRef = ref,
					text: this.state.text,
					placeholder: Loc.getMessage('M_CRM_TIMELINE_TYPE_SOMETHING'),
					onChange: (text) => {
						this.state.text = text;
						this.refreshSaveButton();
					},
				})
			);
		}

		renderClearIcon()
		{
			return View(
				{
					onClick: () => this.setState(
						{ text: '' },
						() => this.focus()
					),
					style: {
						position: 'absolute',
						top: 14,
						right: 6,
						width: 42,
						height: 42,
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				Image({
					svg: {
						content: `<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 18C13.9706 18 18 13.9706 18 9C18 4.02944 13.9706 0 9 0C4.02944 0 0 4.02944 0 9C0 13.9706 4.02944 18 9 18ZM13 11.8873L10.1127 9L13 6.11272L11.8873 5L9 7.88728L6.11272 5L5 6.11272L7.88728 9L5 11.8873L6.11272 13L9 10.1127L11.8873 13L13 11.8873Z" fill="#D5D7DB"/></svg>`
					},
					style: {
						width: 18,
						height: 18,
					}
				})
			);
		}

		renderToolbar()
		{
			return Toolbar({
				right: () => new ToolbarButton({
					ref: (ref) => this.createButtonRef = ref,
					text: Loc.getMessage('M_CRM_TIMELINE_SAVE'),
					loadingText: Loc.getMessage('M_CRM_TIMELINE_SAVING'),
					disabled: !this.isSaveAllowed(),
					onClick: () => this.save(),
				})
			});
		}

		isSaveAllowed()
		{
			return this.state.text.length;
		}

		refreshSaveButton()
		{
			if (this.isSaveAllowed())
			{
				this.saveButton.enable();
			}
			else
			{
				this.saveButton.disable()
			}
		}

		save()
		{
			if (this.props.onSave)
			{
				this.props.onSave(this.state.text);
			}

			return this.close();
		}

		close()
		{
			return new Promise(resolve => {
				if (this.layout)
				{
					this.layout.close();
				}
				resolve();
			});
		}
	}

	module.exports = { TimelineItemBodyTextEditor };

});