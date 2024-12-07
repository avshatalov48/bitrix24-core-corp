/**
 * @module tasks/flow-list/simple-list/items/flow-redux/src/flows-information-card
 */
jn.define('tasks/flow-list/simple-list/items/flow-redux/src/flows-information-card', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Color, Component, Indent } = require('tokens');
	const { H5 } = require('ui-system/typography/heading');
	const { Text6 } = require('ui-system/typography/text');

	class FlowsInformationCard extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.onCloseClickHandler = this.onCloseClickHandler.bind(this);
		}

		get testId()
		{
			return 'flow-information-card';
		}

		render()
		{
			return Card(
				{
					testId: this.testId,
					hideCross: false,
					style: {
						flexDirection: 'row',
						marginHorizontal: Component.paddingLr.toNumber(),
						marginTop: Indent.XL2.toNumber(),
					},
					design: CardDesign.ACCENT,
					onClose: this.onCloseClickHandler,
				},
				this.renderImage(),
				this.renderContent(),
			);
		}

		onCloseClickHandler()
		{
			if (this.props.onCloseButtonClick)
			{
				this.props.onCloseButtonClick();
			}
		}

		renderContent()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderHeader(),
				this.renderDescription(),
			);
		}

		renderHeader()
		{
			return H5({
				testId: 'flows-info-header',
				text: Loc.getMessage('TASKSMOBILE_FLOWS_INFO_HEADER_TEXT'),
				color: Color.base1,
				numberOfLines: 2,
				ellipsize: 'end',
			});
		}

		renderDescription()
		{
			return Text6({
				testId: 'flows-info-description',
				text: Loc.getMessage('TASKSMOBILE_FLOWS_INFO_DESCRIPTION_TEXT'),
				color: Color.base2,
				ellipsize: 'end',
				style: {
					marginTop: Indent.XS.toNumber(),
				},
			});
		}

		getUri()
		{
			return `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/flow-list/simple-list/items/flow-redux/images/${AppTheme.id}/info-card.svg`;
		}

		renderImage()
		{
			return Image({
				style: {
					alignSelf: 'center',
					width: 88,
					height: 88,
					marginRight: Indent.XL.toNumber(),
				},
				resizeMode: 'contain',
				svg: {
					uri: this.getUri(),
				},
			});
		}
	}

	module.exports = {
		FlowsInformationCard,
	};
});
