/**
 * @module layout/ui/collapsible-text
 */
jn.define('layout/ui/collapsible-text', (require, exports, module) => {

	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { BBCodeParser } = require('bbcode/parser');
	const { Type } = require('type');

	/**
	 * @class CollapsibleText
	 */
	class CollapsibleText extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} props.style
		 * @param {string} props.value
		 * @param {boolean} [props.bbCodeMode = false]
		 * @param {number} [props.maxLettersCount = 180]
		 * @param {number} [props.maxNewLineCount = 4]
		 * @param {Object} props.containerStyle
		 * @param {Function} props.onLongClick
		 * @param {Function} props.onLinkClick
		 * @param {string} props.testId
		*/
		constructor(props)
		{
			super(props);

			this.parser = new BBCodeParser();

			this.state.expanded = false;
		}

		get value()
		{
			return this.props.value.trim();
		}

		get maxLettersCount()
		{
			return this.props.maxLettersCount || 180;
		}

		get maxNewLineCount()
		{
			return this.props.maxNewLineCount || 4;
		}

		get bbCodeMode()
		{
			if (Type.isBoolean(this.props.bbCodeMode))
			{
				return this.props.bbCodeMode;
			}

			return false;
		}

		render()
		{
			const { style, onLinkClick, testId, containerStyle } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						flexGrow: 2,
						...containerStyle,
					},
					onClick: () => this.toggleExpand(),
					onLongClick: () => this.handleContentLongClick(),
				},
				BBCodeText(
					{
						style,
						value: this.getPreparedText(),
						onLinkClick: this.bbCodeMode ? onLinkClick : null,
						testId,
					},
				),
			);
		}

		handleContentLongClick()
		{
			if (this.props.onLongClick)
			{
				this.props.onLongClick();
			}
		}

		getPreparedText()
		{
			if (!this.isExpandable())
			{
				return this.bbCodeMode ? this.value : this.getPlainText();
			}

			return this.getTextWithButton();
		}

		getTextWithButton()
		{
			const buttonText = this.state.expanded
				? ` ${Loc.getMessage('COLLAPSIBLE_STRING_VIEW_LESS')}`
				: ` ${Loc.getMessage('COLLAPSIBLE_STRING_VIEW_MORE')}`;

			const button = `[color=${AppTheme.colors.base3}]${buttonText}[/color]`;

			if (this.state.expanded)
			{
				if (this.bbCodeMode)
				{
					return this.value + button;
				}

				return this.getPlainText() + button;
			}

			if (this.bbCodeMode)
			{
				return `${this.getCroppedBBCodeValue().trim()}... ${button}`;
			}

			return `${this.getCroppedValue().trim()}... ${button}`;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getCroppedValue()
		{
			return this.getPlainText().slice(0, this.getCropPosition());
		}

		getPlainText()
		{
			const ast = this.parser.parse(this.value);

			return ast.toPlainText();
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getCroppedBBCodeValue()
		{
			const ast = this.parser.parse(this.value);
			const cropPosition = this.getCropPosition();

			if (ast.getPlainTextLength() <= cropPosition)
			{
				return this.value;
			}
			const [leftTree] = ast.split({ offset: cropPosition });

			return leftTree.toString();
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isExpandable()
		{
			const ast = this.parser.parse(this.value);

			return this.getCropPosition() < ast.getPlainTextLength();
		}

		/**
		 * @public
		 * @returns {Number}
		 */
		getCropPosition()
		{
			if (!this.maxNewLineCount)
			{
				return this.maxLettersCount;
			}

			const splittedText = this.getPlainText().split('\n', this.maxNewLineCount);
			const croppedValue = splittedText.join('\n');

			return Math.min(this.maxLettersCount, croppedValue.length);
		}

		toggleExpand()
		{
			if (this.isExpandable())
			{
				this.setState({ expanded: !this.state.expanded });
			}
		}
	}

	CollapsibleText.propTypes = {
		style: PropTypes.object,
		value: PropTypes.string,
		bbCodeMode: PropTypes.bool,
		maxLettersCount: PropTypes.number,
		maxNewLineCount: PropTypes.number,
		onLongClick: PropTypes.func,
		onLinkClick: PropTypes.func,
		testId: PropTypes.string,

	};

	module.exports = {
		CollapsibleText,
	};
});
