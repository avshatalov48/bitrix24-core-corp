/**
 * @module tasks/layout/task/actionMenu/src/button
 */
jn.define('tasks/layout/task/actionMenu/src/button', (require, exports, module) => {
	const AppTheme = require('apptheme');

	/**
	 * @class ActionMenuButton
	 */
	class ActionMenuButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				action: props.action,
				isAnimating: false,
			};

			this.moreButtonRef = null;
			this.saveButtonRef = null;
		}

		/**
		 * @public
		 * @param {{ type: string, callback: function, text: string }} action
		 */
		setAction(action)
		{
			const { type: currentType } = this.state.action;
			const { type: nextType } = action;

			if (!this.moreButtonRef || !this.saveButtonRef)
			{
				this.setState({ action });

				return;
			}

			if (this.state.isAnimating)
			{
				if (nextType === currentType)
				{
					this.state.action = action;
				}
				else
				{
					this.setState({ action });
				}

				return;
			}

			if (currentType === Types.MORE && nextType === Types.SAVE)
			{
				this.animateSaveButton(action);
			}
			else if (currentType === Types.SAVING && nextType === Types.MORE)
			{
				this.animateMoreButton(action);
			}
			else
			{
				this.setState({ action });
			}
		}

		/**
		 * @private
		 * @param {{ type: string, callback: function, text?: string }} action
		 */
		animateSaveButton(action)
		{
			this.state.action = action;
			this.state.isAnimating = true;

			this.saveButtonRef.animate({
				opacity: 1,
				duration: 100,
			}, () => {
				this.state.isAnimating = false;
			});
		}

		/**
		 * @private
		 * @param {{ type: string, callback: function, text: string }} action
		 */
		animateMoreButton(action)
		{
			this.state.action = action;
			this.state.isAnimating = true;

			this.saveButtonRef.animate({
				opacity: 0,
				duration: 100,
			}, () => {
				this.state.isAnimating = false;
			});
		}

		/**
		 * @private
		 * @return {string}
		 */
		getTestId()
		{
			const { type } = this.state.action;

			return `${this.props.testId}_${type}`;
		}

		render()
		{
			return View(
				{
					testId: this.getTestId(),
					style: {
						width: 111,
						height: 47,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						position: 'absolute',
						top: 0,
						right: 0,
						flexDirection: 'row',
						justifyContent: 'flex-end',
						alignItems: 'center',
						paddingRight: 16,
					},
					onClick: () => this.onClick(),
				},
				this.renderMoreButton(),
				this.renderSaveButton(),
			);
		}

		renderMoreButton()
		{
			return Image({
				ref: (ref) => {
					this.moreButtonRef = ref;
				},
				style: {
					width: 24,
					height: 24,
				},
				tintColor: AppTheme.colors.base3,
				svg: {
					content: Icons.more,
				},
			});
		}

		renderSaveButton()
		{
			const { text, type } = this.state.action;

			return View(
				{
					ref: (ref) => {
						this.saveButtonRef = ref;
					},
					style: {
						backgroundColor: AppTheme.colors.accentMainPrimary,
						paddingVertical: 6,
						paddingHorizontal: 4,
						borderRadius: 88,
						width: 95,
						justifyContent: 'center',
						alignItems: 'center',
						opacity: type === Types.MORE ? 0 : 1,
						position: 'absolute',
						left: 0,
					},
				},
				Text({
					text,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						color: AppTheme.colors.bgContentPrimary,
						fontSize: 14,
					},
				}),
			);
		}

		onClick()
		{
			const { callback } = this.state.action;

			if (callback)
			{
				callback();
			}
		}
	}

	const Types = {
		MORE: 'more',
		SAVING: 'saving',
		SAVE: 'save',
	};

	const Icons = {
		more: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 14C7.10457 14 8 13.1046 8 12C8 10.8954 7.10457 10 6 10C4.89543 10 4 10.8954 4 12C4 13.1046 4.89543 14 6 14Z" fill="#A8ADB4"/><path d="M12 14C13.1046 14 14 13.1046 14 12C14 10.8954 13.1046 10 12 10C10.8954 10 10 10.8954 10 12C10 13.1046 10.8954 14 12 14Z" fill="#A8ADB4"/><path d="M20 12C20 13.1046 19.1046 14 18 14C16.8954 14 16 13.1046 16 12C16 10.8954 16.8954 10 18 10C19.1046 10 20 10.8954 20 12Z" fill="#A8ADB4"/></svg>',
		check: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.60189 19.0175C9.48539 19.1311 9.29963 19.1311 9.18313 19.0175L3.3319 13.315C3.1708 13.158 3.1708 12.8991 3.3319 12.7421L4.98288 11.1331C5.13821 10.9817 5.3859 10.9817 5.54123 11.1331L9.39251 14.8865L18.459 6.0504C18.6144 5.89902 18.8621 5.89902 19.0174 6.0504L20.6684 7.65942C20.8295 7.81642 20.8295 8.07534 20.6684 8.23234L9.60189 19.0175Z" fill="#525C69"/></svg>',
	};

	module.exports = { ActionMenuButton };
});
