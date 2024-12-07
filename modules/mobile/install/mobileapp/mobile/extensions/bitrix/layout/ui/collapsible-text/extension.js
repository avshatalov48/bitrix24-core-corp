/**
 * @module layout/ui/collapsible-text
 */
jn.define('layout/ui/collapsible-text', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { BBCodeParser } = require('bbcode/parser');
	const { Type } = require('type');
	const { stringify } = require('utils/string');

	/**
	 * @class CollapsibleText
	 */
	class CollapsibleText extends LayoutComponent
	{
		/**
		 * @param {Object} props
		 * @param {Object} [props.style]
		 * @param {string} props.value
		 * @param {string|undefined} [props.placeholder]
		 * @param {boolean} [props.bbCodeMode = false]
		 * @param {boolean} [props.useBBCodeEditor = false]
		 * @param {boolean} [props.canExpand = true]
		 * @param {number} [props.maxLettersCount = 180]
		 * @param {number} [props.maxNewLineCount = 4]
		 * @param {Object} [props.containerStyle]
		 * @param {Function} [props.onLongClick]
		 * @param {Function} [props.onLinkClick]
		 * @param {onClick} [props.onClick]
		 * @param {string} [props.testId]
		 */
		constructor(props)
		{
			super(props);

			this.parser = new BBCodeParser();

			this.state.expanded = false;
		}

		get value()
		{
			return stringify(this.props.value).trim();
		}

		get maxLettersCount()
		{
			return this.props.maxLettersCount ?? 180;
		}

		get maxNewLineCount()
		{
			return this.props.maxNewLineCount ?? 4;
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
			const {
				style,
				containerStyle,
				onClick,
				onLinkClick,
				testId,
				useBBCodeEditor = false,
				canExpand = true,
			} = this.props;

			return View(
				{
					testId: `${testId}_container`,
					style: {
						flexDirection: 'row',
						flexGrow: 1,
						...containerStyle,
					},
					onClick: () => {
						if (onClick && (useBBCodeEditor || !canExpand || !this.isExpandable()))
						{
							onClick();
						}
						else if (canExpand)
						{
							this.toggleExpand();
						}
					},
					onLongClick: () => this.handleContentLongClick(),
				},
				BBCodeText({
					style,
					value: this.getPreparedText(),
					onLinkClick: this.bbCodeMode ? onLinkClick : null,
					testId,
				}),
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
			const buttonColor = this.props.moreButtonColor ?? AppTheme.colors.base3;

			const buttonText = this.state.expanded
				? ` ${Loc.getMessage('COLLAPSIBLE_STRING_VIEW_LESS')}`
				: ` ${Loc.getMessage('COLLAPSIBLE_STRING_VIEW_MORE')}`;

			const button = `[color=${buttonColor}]${buttonText}[/color]`;

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
		useBBCodeEditor: PropTypes.bool,
		bbCodeMode: PropTypes.bool,
		canExpand: PropTypes.bool,
		maxLettersCount: PropTypes.number,
		maxNewLineCount: PropTypes.number,
		containerStyle: PropTypes.object,
		onLongClick: PropTypes.func,
		onLinkClick: PropTypes.func,
		onClick: PropTypes.func,
		testId: PropTypes.string,
	};

	module.exports = {
		CollapsibleText,
	};
});
