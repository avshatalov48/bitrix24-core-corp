/**
 * @module tasks/layout/task/create-new/bottom-panel
 */
jn.define('tasks/layout/task/create-new/bottom-panel', (require, exports, module) => {
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { Icon } = require('assets/icons');
	const { Responsible } = require('tasks/layout/task/create-new/responsible');
	const { Indent } = require('tokens');
	const { Haptics } = require('haptics');

	class BottomPanel extends LayoutComponent
	{
		static get height()
		{
			return 52;
		}

		constructor(props)
		{
			super(props);

			this.state = {
				responsible: props.responsible,
				groupId: props.groupId,
				canSave: props.canSave,
			};

			this.onSave = this.onSave.bind(this);
			this.onResponsibleChange = this.onResponsibleChange.bind(this);
			this.onResponsibleSelectorHidden = this.onResponsibleSelectorHidden.bind(this);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				responsible: props.responsible,
				groupId: props.groupId,
				canSave: props.canSave,
			};
		}

		updateState(newState)
		{
			this.setState({
				responsible: newState.responsible,
				groupId: newState.groupId,
				canSave: newState.canSave,
			});
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						height: BottomPanel.height,
						marginLeft: Indent.XL3.toNumber(),
					},
					testId: 'taskCreateToolbar',
				},
				this.renderResponsible(),
				this.renderButtons(),
			);
		}

		renderResponsible()
		{
			return View(
				{
					style: {
						flex: 1,
						marginRight: Indent.M.toNumber(),
					},
				},
				new Responsible({
					flowId: this.props.flowId,
					groupId: this.state.groupId,
					responsible: this.state.responsible,
					parentWidget: this.props.parentWidget,
					onChange: this.onResponsibleChange,
					onSelectorHidden: this.onResponsibleSelectorHidden,
				}),
			);
		}

		renderButtons()
		{
			return View(
				{
					testId: 'taskCreateToolbar_saveButton',
					style: {
						paddingHorizontal: Indent.XL3.toNumber(),
						height: BottomPanel.height,
						justifyContent: 'center',
					},
					onClick: this.onSave,
				},
				Button({
					testId: 'taskCreateToolbar_saveButtonInner',
					leftIcon: Icon.ARROW_TOP,
					size: ButtonSize.S,
					disabled: !this.state.canSave,
					design: ButtonDesign.FILLED,
					onClick: this.onSave,
				}),
			);
		}

		onSave()
		{
			if (this.state.canSave)
			{
				this.props.onSave();
			}
			else
			{
				Haptics.notifyWarning();
			}
		}

		onResponsibleChange(responsible)
		{
			if (this.props.onResponsibleChange)
			{
				this.props.onResponsibleChange(responsible);
			}
		}

		onResponsibleSelectorHidden()
		{
			if (this.props.onResponsibleSelectorHidden)
			{
				this.props.onResponsibleSelectorHidden();
			}
		}
	}

	module.exports = { BottomPanel };
});
