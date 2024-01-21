/**
 * @module crm/document/details/download-link
 */
jn.define('crm/document/details/download-link', (require, exports, module) => {
	const AppTheme = require('apptheme');
	class CrmDocumentDownloadLink extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				text: props.text,
			};
		}

		get defaultText()
		{
			return this.props.text;
		}

		get loadingText()
		{
			return this.props.loadingText || this.props.text;
		}

		render()
		{
			return View(
				{
					style: {
						paddingBottom: 16,
						marginBottom: 48,
						alignItems: 'center',
					},
					onClick: () => this.onClick(),
				},
				Text({
					text: this.state.text,
					style: {
						color: AppTheme.colors.base3,
						fontSize: 16,
					},
				}),
			);
		}

		onClick()
		{
			if (this.props.onClick)
			{
				const result = this.props.onClick();
				if (result instanceof Promise)
				{
					this.setState({ text: this.loadingText });
					result.finally(() => {
						this.setState({ text: this.defaultText });
					});
				}
			}
		}
	}

	module.exports = { CrmDocumentDownloadLink };
});
