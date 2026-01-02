/**
 * @module bizproc/workflow/result
 * */
jn.define('bizproc/workflow/result', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text4 } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { throttle } = require('utils/function');
	const { openNativeViewer } = require('utils/file');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	class WorkflowResult extends PureComponent
	{
		/**
		 * @param {{}} props
		 * @param {string} props.text
		 * @param {{}} props.files
		 * @param {string} props.testId
		 * @param {boolean} props.withTitle - default true
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				text: props.text,
				files: props.files,
			};
		}

		get #testId()
		{
			return this.props.testId || '';
		}

		render()
		{
			return View(
				{ style: { marginBottom: Indent.M.toNumber() } },
				this.withTitle && this.#renderTitle(),
				this.#renderResult(),
			);
		}

		#renderTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						marginBottom: Indent.XS.toNumber(),
					},
				},
				this.#renderFlag(),
				Text4({
					text: Loc.getMessage('BPMOBILE_WORKFLOW_RESULT_TITLE'),
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		#renderFlag()
		{
			return IconView({ icon: Icon.FLAG, size: 24 });
		}

		#renderResult()
		{
			return BBCodeText({
				testId: `${this.#testId}WorkflowResultText`,
				linksUnderline: false,
				value: this.state.text,
				style: {
					color: Color.base4.toHex(),
					fontSize: 14,
					fontWeight: '400',
				},
				onLinkClick: ({ url }) => {
					if (this.state.files[url])
					{
						const file = this.state.files[url];
						const openViewer = throttle(openNativeViewer, 500);
						openViewer({
							fileType: UI.File.getType(UI.File.getFileMimeType(file.type, file.name)),
							url: file.url,
							name: file.name,
						});

						return;
					}

					inAppUrl.open(url, { parentWidget: this.layout });
				},
			});
		}

		get withTitle()
		{
			return this.props.withTitle !== false;
		}
	}

	module.exports = { WorkflowResult };
});
