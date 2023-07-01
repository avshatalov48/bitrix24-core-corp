/**
 * @module crm/timeline/ui/text-editor
 */
jn.define('crm/timeline/ui/text-editor', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Textarea } = require('crm/timeline/ui/textarea');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');

	/**
	 * @class TimelineTextEditor
	 */
	class TimelineTextEditor extends LayoutComponent
	{
		/**
		 * @public
		 * @param {string} title
		 * @param {*} options
		 */
		static open({ title, ...options })
		{
			PageManager.openWidget('layout', {
				modal: true,
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: true,
					showOnTop: false,
					mediumPositionPercent: 70,
					navigationBarColor: '#eef2f4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				},
			})
				.then((widget) => {
					widget.setTitle({ text: title });
					widget.enableNavigationBarBorder(false);
					widget.showComponent(new TimelineTextEditor({
						layout: widget,
						...options,
					}));
				})
			;
		}

		constructor(props)
		{
			super(props);

			this.state = {
				text: props.text,
				maxHeight: 1000,
			};

			this.layout = props.layout;
			this.textInputRef = null;

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

		/**
		 * @public
		 */
		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		/**
		 * @public
		 */
		clear()
		{
			this.state.text = '';
			if (this.textInputRef)
			{
				this.textInputRef.clear();
			}
			this.refreshSaveButton();
		}

		/**
		 * @public
		 * @return {string}
		 */
		get value()
		{
			return this.state.text;
		}

		get placeholder()
		{
			return this.props.placeholder || Loc.getMessage('M_CRM_TIMELINE_TYPE_SOMETHING');
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: '#eef2f4',
					},
					resizableByKeyboard: true,
				},
				View(
					{
						style: {
							flex: 1,
							backgroundColor: '#ffffff',
							borderTopLeftRadius: 12,
							borderTopRightRadius: 12,
							maxHeight: this.state.maxHeight,
						},
						onLayout: ({ height }) => this.setMaxHeight(height),
					},
					this.renderTextField(),
					this.renderClearIcon(),
				),
			);
		}

		setMaxHeight(height)
		{
			const { maxHeight } = this.state;
			const newMaxHeight = Math.ceil(Math.min(height, maxHeight));

			if (newMaxHeight < maxHeight)
			{
				this.setState({ maxHeight: newMaxHeight });
			}
		}

		renderTextField()
		{
			const isIOS = Application.getPlatform() === 'ios';

			return View(
				{
					style: {
						flexGrow: 1,
						paddingRight: 48,
						paddingLeft: isIOS ? 8 : 0,
					},
				},
				Textarea({
					ref: (ref) => this.textInputRef = ref,
					text: this.state.text,
					placeholder: this.placeholder,
					onChange: (text) => {
						this.state.text = text;
						this.refreshSaveButton();
					},
				}),
			);
		}

		renderClearIcon()
		{
			return View(
				{
					onClick: () => this.clear(),
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
						content: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 18C13.9706 18 18 13.9706 18 9C18 4.02944 13.9706 0 9 0C4.02944 0 0 4.02944 0 9C0 13.9706 4.02944 18 9 18ZM13 11.8873L10.1127 9L13 6.11272L11.8873 5L9 7.88728L6.11272 5L5 6.11272L7.88728 9L5 11.8873L6.11272 13L9 10.1127L11.8873 13L13 11.8873Z" fill="#D5D7DB"/></svg>',
					},
					style: {
						width: 18,
						height: 18,
					},
				}),
			);
		}

		isSaveAllowed()
		{
			return this.props.required ? this.state.text.length > 0 : true;
		}

		refreshSaveButton()
		{
			if (this.isSaveAllowed())
			{
				this.saveButton.enable();
			}
			else
			{
				this.saveButton.disable();
			}
		}

		/**
		 * @return {Promise}
		 */
		save()
		{
			return this.beforeSave().then(() => {
				if (this.props.onSave)
				{
					this.props.onSave(this.state.text);
				}

				return this.close();
			});
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		beforeSave()
		{
			if (this.props.onBeforeSave)
			{
				const promise = this.props.onBeforeSave(this);
				if (!(promise instanceof Promise))
				{
					throw new TypeError('Timeline text editor: onBeforeSave hook must return Promise');
				}
				return promise;
			}
			return Promise.resolve();
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		close()
		{
			return new Promise((resolve) => {
				if (this.layout)
				{
					this.layout.close();
				}
				resolve();
			});
		}
	}

	module.exports = { TimelineTextEditor };
});
