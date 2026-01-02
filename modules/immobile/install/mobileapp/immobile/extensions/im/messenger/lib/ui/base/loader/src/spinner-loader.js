/**
 * @module im/messenger/lib/ui/base/loader/src/spinner-loader
 */
jn.define('im/messenger/lib/ui/base/loader/src/spinner-loader', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { Indent, Color } = require('tokens');
	const { Text2 } = require('ui-system/typography/text');
	const { PropTypes } = require('utils/validation');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');

	/**
	 * @class SpinnerLoader
	 */
	class SpinnerLoaderItem extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {boolean} props.testId
		 * @param {boolean} [props.enable]
		 * @param {string} [props.text]
		 */
		constructor(props)
		{
			super(props);

			this.initState(props);
		}

		componentWillReceiveProps(nextProps)
		{
			this.initState(nextProps);
		}

		initState(props)
		{
			this.setState({
				enable: Boolean(props.enable),
			});
		}

		render()
		{
			if (!this.isEnable())
			{
				return null;
			}

			return View(
				{
					testId: this.getTestId(),
					style: {
						flexDirection: 'row',
						padding: Indent.L.toNumber(),
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				SpinnerLoader(),
				this.renderTitle(),
			);
		}

		renderTitle()
		{
			const title = this.getTitle();

			if (!title && title !== '')
			{
				return null;
			}

			return Text2(
				{
					text: title,
					color: Color.base3,
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				},
			);
		}

		getTitle()
		{
			const { text } = this.props;

			return Type.isString(text) ? text : Loc.getMessage('IMMOBILE_LOADER_ITEM_DEFAULT_TEXT');
		}

		getTestId()
		{
			const { testId } = this.props;

			return testId ? `${testId}-spinner-loader-item` : null;
		}

		disable()
		{
			this.setState({ enable: false });
		}

		enable()
		{
			this.setState({ enable: true });
		}

		isEnable()
		{
			return this.state.enable;
		}
	}

	SpinnerLoaderItem.propTypes = {
		testId: PropTypes.string.isRequired,
		text: PropTypes.string,
		enable: PropTypes.bool,
	};

	module.exports = {
		SpinnerLoaderItem,
		SpinnerDesign
	};
});
