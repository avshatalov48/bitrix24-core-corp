/**
 * @module layout/ui/fields/url
 */
jn.define('layout/ui/fields/url', (require, exports, module) => {

	const { pen } = require('assets/common');
	const { StringFieldClass } = require('layout/ui/fields/string');
	const { URL, isValidLink, getHttpPath } = require('utils/url');
	const { inAppUrl } = require('in-app-url');

	/**
	 * @class UrlField
	 */
	class UrlField extends StringFieldClass
	{
		renderLink(link)
		{
			const { showFavicon } = this.getConfig();
			const value = this.getValue();

			const originUrl = URL(link).origin;
			let field;

			if (Application.getPlatform() === 'ios')
			{
				field = TextField({
					...this.getReadOnlyRenderParams(),
					style: {
						...this.styles.value,
						color: '#0b66c3',
					},
					enable: false,
					value,
				});
			}
			else
			{
				field = Text({
					...this.getReadOnlyRenderParams(),
					style: {
						...this.styles.value,
						color: '#0b66c3',
					},
					text: value,
				})
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
					},
					onClick: () => {
						inAppUrl.open(link)
					},
				},
				showFavicon && Image({
					style: {
						width: 20,
						height: 20,
						alignSelf: 'flex-start',
						marginRight: 5,
					},
					uri: `${originUrl}/favicon.ico`,
				}),
				field,
			);
		}

		getConfig()
		{
			const config = super.getConfig();

			return {
				...config,
				autoCapitalize: 'none',
			};
		}

		focus() {
			if (!this.isEmpty() && this.isPossibleToFocus())
			{
				return this.setFocus();
			}

			return super.focus();
		}

		renderEditableContent()
		{
			if (this.state.focus || this.isEmpty())
			{
				return TextField(this.getFieldInputProps());
			}

			return this.renderReadOnlyContent();
		}

		renderReadOnlyContent()
		{
			const value = this.getValue();
			const link = getHttpPath(value);

			if (!this.isEmpty() && isValidLink(link))
			{
				return this.renderLink(link);
			}

			return super.renderReadOnlyContent();
		}

		renderEditIcon()
		{
			if (this.state.focus || this.isEmpty())
			{
				return null;
			}

			return View(
				{
					style: {
						width: 24,
						height: 24,
						justifyContent: 'center',
						alignItems: 'center',
						marginLeft: 5,
					},
				},
				Image(
					{
						style: {
							width: 14,
							height: 14,
						},
						svg: {
							content: pen,
						},
					},
				),
			);
		}
	}

	module.exports = {
		UrlType: 'url',
		UrlField: (props) => new UrlField(props),
	};

});
