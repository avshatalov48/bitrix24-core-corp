/**
 * @module calendar/layout/fields/layout/selector
 */
jn.define('calendar/layout/fields/layout/selector', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { Color } = require('tokens');

	/**
	 * @class Selector
	 */
	class Selector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				items: props.items,
				selected: [...props.selected],
			};

			this.layoutWidget = props.layoutWidget;
		}

		get checkedBackground()
		{
			if (this.props.checkedBackground === null)
			{
				return null;
			}

			return this.props.checkedBackground ?? AppTheme.colors.accentSoftBlue2;
		}

		get checkColor()
		{
			return this.props.checkColor ?? AppTheme.colors.accentMainPrimary;
		}

		render()
		{
			return View(
				{},
				this.renderHeader(),
				this.renderOptions(),
			);
		}

		renderHeader()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						paddingVertical: 10,
						paddingHorizontal: 15,
					},
				},
				this.renderCancelButton(),
				this.renderTitle(),
				this.renderSaveButton(),
			);
		}

		renderTitle()
		{
			return Text({
				style: {
					fontSize: 17,
					color: AppTheme.colors.base1,
					flex: 1,
				},
				text: this.props.title,
			});
		}

		renderCancelButton()
		{
			return View(
				{
					style: {
						paddingRight: 20,
					},
					clickable: true,
					onClick: this.onCancelButtonClickHandler.bind(this),
				},
				Image(
					{
						svg: {
							content: icons.leftArrow,
						},
						style: {
							width: 9,
							height: 16,
						},
					},
				),
			);
		}

		onCancelButtonClickHandler()
		{
			this.layoutWidget.close();
		}

		renderSaveButton()
		{
			return View(
				{
					clickable: true,
					onClick: this.onSaveButtonClickHandler.bind(this),
				},
				Text(
					{
						style: {
							fontSize: 16,
							color: AppTheme.colors.accentMainLinks,
						},
						text: Loc.getMessage('M_CALENDAR_FIELDS_SAVE'),
					},
				),
			);
		}

		onSaveButtonClickHandler()
		{
			this.props.onChange(this.state.selected);
			this.layoutWidget.close();
		}

		renderOptions()
		{
			return ScrollView(
				{
					style: {
						flex: 1,
					},
				},
				View(
					{},
					View(
						{
							style: {
								backgroundColor: Color.bgContentPrimary.toHex(),
							},
						},
						...this.state.items.map((item) => this.renderOption(item)),
					),
				),
			);
		}

		renderOption(item)
		{
			const isChecked = this.state.selected.includes(item.value);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						margin: 6,
						paddingHorizontal: 12,
						paddingVertical: 14,
						borderRadius: 8,
						backgroundColor: isChecked ? this.checkedBackground : undefined,
					},
					clickable: true,
					onClick: this.onOptionClickHandler.bind(this, item),
				},
				this.renderOptionText(item.name),
				isChecked && this.renderOptionCheck(),
			);
		}

		onOptionClickHandler(item)
		{
			const { selected } = this.state;

			if (selected.includes(item.value))
			{
				if (selected.length <= 1)
				{
					return;
				}

				selected.splice(selected.indexOf(item.value), 1);
			}
			else
			{
				selected.push(item.value);
			}

			this.setState({ selected });
		}

		renderOptionText(text)
		{
			return Text({
				text,
				style: {
					fontSize: 16,
					color: AppTheme.colors.base1,
					flex: 1,
				},
			});
		}

		renderOptionCheck()
		{
			return View({
				style: {
					width: 12,
					height: 10,
					backgroundImageSvg: icons.check(this.checkColor),
				},
			});
		}
	}

	const icons = {
		check: (color) => `<svg width="12" height="10" viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.36008 3.89341L4.0133 6.54663L10.3161 0.447021L11.6761 1.8071L5.37656 7.90671L4.0133 9.26679L2.65321 7.90671L0 5.2535L1.36008 3.89341Z" fill="${color}"/></svg>`,
		leftArrow: '<svg width="9" height="16" viewBox="0 0 9 16" fill="none" xmlns="http://www.w3.org/2000/svg"> <path fill-rule="evenodd" clip-rule="evenodd" d="M4.3341 9.13027L8.86115 13.6573L7.26368 15.2547L0.00952148 8.0005L7.26368 0.746338L8.86115 2.34381L4.3341 6.87086L3.20009 8.0001L4.3341 9.13027Z" fill="#828B95"/></svg>',
	};

	module.exports = { Selector };
});
