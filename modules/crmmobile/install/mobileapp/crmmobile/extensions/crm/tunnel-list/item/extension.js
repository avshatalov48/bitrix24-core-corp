/**
 * @module crm/tunnel-list/item
 */
jn.define('crm/tunnel-list/item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { CategorySelectActions } = require('crm/category-list/actions');
	const { Robot } = require('crm/tunnel-list/item/robot');
	const { confirmDestructiveAction } = require('alert');
	const { trim } = require('utils/string');
	const { Text2, Text5 } = require('ui-system/typography/text');
	const { Color, Indent } = require('tokens');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const DEFAULT_STAGE_BACKGROUND_COLOR = Color.accentSoftBlue1.toHex();

	const DelayIntervalType = {
		After: 'after',
		Before: 'before',
		In: 'in',
	};

	const ConditionGroup = {
		Joiner: {
			And: 'AND',
			Or: 'OR',
		},
		Type: {
			Field: 'field',
			Mixed: 'mixed',
		},
	};

	const IconWidth = 44;

	/**
	 * @class TunnelListItem
	 */
	class TunnelListItem extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.fields = this.getFields(this.props);

			this.selectedCategory = null;
			this.selectedStage = null;
			this.robot = this.getRobotData(this.props.tunnel.robot);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', null);
		}

		getRobotData(robotData)
		{
			return new Robot(robotData);
		}

		getFields(props)
		{
			return BX.prop.getArray(props, 'documentFields', []);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: Indent.M.toNumber(),
						marginBottom: Indent.XL.toNumber(),
						paddingBottom: Indent.S.toNumber(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'column',
							flex: 1,
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'flex-start',
							},
						},
						this.renderIconContainer(),
						this.renderTitle(),
					),
					View(
						{
							style: {
								marginLeft: IconWidth,
							},
						},
						this.renderDelay(),
						this.renderCondition(),
						this.renderResponsible(),
					),
				),
				this.renderMenu(),
			);
		}

		renderIconContainer()
		{
			return View(
				{
					style: {
						width: IconWidth,
						height: 32,
					},
				},
				Image(
					{
						style: {
							width: 23,
							height: 19,
						},
						svg: {
							content: stageFrom.replace(
								'#COLOR#',
								this.props.tunnel.srcStageColor || DEFAULT_STAGE_BACKGROUND_COLOR,
							),
						},
					},
				),
				Image(
					{
						style: {
							width: 23,
							height: 19,
							marginLeft: 9,
							marginTop: -12,
						},
						svg: {
							content: StageTo.replace(
								'#COLOR#',
								this.props.tunnel.dstStageColor || DEFAULT_STAGE_BACKGROUND_COLOR,
							),
						},
					},
				),
			);
		}

		renderTitle()
		{
			const {
				tunnel: {
					dstStageName: tunnelDstStageName,
					dstCategoryName: tunnelDstCategoryName,
				},
			} = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						flex: 1,
						marginBottom: Indent.XS2.toNumber(),
					},
				},
				Text2({
					style: {
						maxWidth: '70%',
						flexWrap: 'no-wrap',
					},
					color: Color.base1,
					numberOfLines: 1,
					ellipsize: 'end',
					text: tunnelDstStageName,
				}),
				Text2({
					style: {
						width: '30%',
						flexWrap: 'no-wrap',
					},
					color: Color.base4,
					numberOfLines: 1,
					ellipsize: 'end',
					text: ` (${tunnelDstCategoryName})`,
				}),
			);
		}

		renderDelay()
		{
			return Text5({
				color: Color.base4,
				numberOfLines: 1,
				ellipsize: 'end',
				text: `${BX.message('TUNNEL_MENU_DELAY_TITLE2')}: ${this.prepareDelayText(this.robot.delay)}`,
				style: {
					marginBottom: Indent.XS2.toNumber(),
				},
			});
		}

		prepareDelayText(delay)
		{
			const basisFields = [];
			for (let i = 0; i < this.fields.length; ++i)
			{
				const field = this.fields[i];
				if (field.type === 'date' || field.type === 'datetime')
				{
					basisFields.push(field);
				}
			}

			let str = BX.message('TUNNEL_MENU_DELAY_AT_ONCE');

			if (delay.type === DelayIntervalType.In)
			{
				str = BX.message('TUNNEL_MENU_DELAY_IN_TIME');
				for (const basisField of basisFields)
				{
					if (delay.basis === basisField.systemExpression)
					{
						str += ` ${basisField.name}`;
						break;
					}
				}
			}
			else if (delay.value)
			{
				const prefix = delay.type === DelayIntervalType.After
					? BX.message('TUNNEL_MENU_DELAY_THROUGH') : BX.message('TUNNEL_MENU_DELAY_FOR_TIME_1');

				str = `${prefix} ${this.getFormattedPeriod(delay.value, delay.valueType)}`;

				const fieldSuffix = delay.type === DelayIntervalType.After
					? BX.message('TUNNEL_MENU_DELAY_AFTER') : BX.message('TUNNEL_MENU_DELAY_BEFORE_1');

				if (delay.basisName)
				{
					str += ` ${fieldSuffix} ${delay.basisName}`;
				}
			}

			if (delay.workTime)
			{
				str += `, ${BX.message('TUNNEL_MENU_DELAY_IN_WORKTIME')}`;
			}

			return str;
		}

		getFormattedPeriod(value, type)
		{
			const label = `${value} `;
			let labelIndex;
			if (value > 20)
			{
				value %= 10;
			}

			if (value === 1)
			{
				labelIndex = 0;
			}
			else if (value > 1 && value < 5)
			{
				labelIndex = 1;
			}
			else
			{
				labelIndex = 2;
			}

			const labels = this.getPeriodLabels(type);

			return label + (labels ? labels[labelIndex] : '');
		}

		getPeriodLabels(period)
		{
			let labels = [];

			switch (period)
			{
				case 'i':
					labels = [
						BX.message('TUNNEL_MENU_DELAY_MIN1'),
						BX.message('TUNNEL_MENU_DELAY_MIN2'),
						BX.message('TUNNEL_MENU_DELAY_MIN3'),
					];
					break;

				case 'h':
					labels = [
						BX.message('TUNNEL_MENU_DELAY_HOUR1'),
						BX.message('TUNNEL_MENU_DELAY_HOUR2'),
						BX.message('TUNNEL_MENU_DELAY_HOUR3'),
					];
					break;

				case 'd':
					labels = [
						BX.message('TUNNEL_MENU_DELAY_DAY1'),
						BX.message('TUNNEL_MENU_DELAY_DAY2'),
						BX.message('TUNNEL_MENU_DELAY_DAY3'),
					];
					break;
			}

			return labels;
		}

		renderCondition()
		{
			return Text5({
				color: Color.base4,
				numberOfLines: 1,
				ellipsize: 'end',
				text: `${BX.message('TUNNEL_MENU_CONDITION_TITLE')}: ${this.prepareConditionText(this.robot.conditionGroup)}`,
				style: {
					marginBottom: Indent.XS2.toNumber(),
				},
			});
		}

		prepareConditionText(conditionGroup)
		{
			if (conditionGroup.items.length === 0)
			{
				return BX.message('TUNNEL_MENU_PROPERTY_EMPTY');
			}

			let str = '';
			conditionGroup.items.forEach((item, index) => {
				if (item[0].field === '')
				{
					str = BX.message('TUNNEL_MENU_PROPERTY_EMPTY');
				}
				else
				{
					const field = this.getField(item[0].object, item[0].field) || '?';
					const valueLabel = (item[0].operator.includes('empty'))
						? ''
						: this.formatValuePrintable(field, item[0].value);

					let joiner;

					if (index === conditionGroup.items.length - 1)
					{
						joiner = '';
					}
					else
					{
						joiner = item[1] === ConditionGroup.Joiner.Or
							? BX.message('TUNNEL_MENU_CONDITION_OR')
							: BX.message('TUNNEL_MENU_CONDITION_AND');
					}

					str += `${field.name} ${this.getOperatorLabel(item[0].operator)} ${valueLabel} ${joiner} `;
				}
			});

			return str;
		}

		formatValuePrintable(property, value)
		{
			let result;
			switch (property.type)
			{
				case 'bool':
				case 'UF:boolean':
					result = BX.message(
						value === 'Y' ? 'TUNNEL_MENU_CONDITION_TYPE_YES' : 'TUNNEL_MENU_CONDITION_TYPE_NO',
					);
					break;

				case 'select':
				case 'internalselect':
					const options = property.options || {};
					if (BX.type.isArray(value))
					{
						result = [];
						value.forEach((v) => {
							result.push(options[v]);
						});
						result = result.join(', ');
					}
					else
					{
						const option = options.find((option) => option.id === value);
						result = option.value;
					}
					break;

				case 'date':
				case 'UF:date':
				case 'datetime':
					result = this.normalizeDateValue(value);
					break;
				case 'text':
				case 'int':
				case 'double':
				case 'string':
					result = value;
					break;
				case 'user':
					result = [];
					let i;
					let name;
					let pair;
					let matches;
					const pairs = Array.isArray(value) ? value : value.split(',');

					for (i = 0; i < pairs.length; ++i)
					{
						pair = trim(pairs[i]);
						if (matches = pair.match(/(.*)\[([A-Z]{0,2}\d+)]/))
						{
							name = trim(matches[1]);
							result.push(name);
						}
						else
						{
							result.push(pair);
						}
					}
					result = result.join(', ');
					break;
				default:
					result = typeof value === 'string' ? value : '(?)';

					break;
			}

			return result;
		}

		normalizeDateValue(value)
		{
			return value ? value.replace(/(\s\[-?\d+])$/, '') : '';
		}

		getField(object, id)
		{
			let field;
			const tpl = null;
			switch (object)
			{
				case 'Document':
					for (let i = 0; i < this.fields.length; ++i)
					{
						if (id === this.fields[i].id)
						{
							field = this.fields[i];
						}
					}
					break;
				case 'Template':
					if (tpl && component && component.triggerManager)
					{
						field = component.triggerManager.getReturnProperty(tpl.getStatusId(), id);
					}
					break;
				case 'Constant':
					if (tpl)
					{
						field = tpl.getConstant(id);
					}
					break;
				default:
					const foundRobot = tpl ? tpl.getRobotById(object) : null;
					if (foundRobot)
					{
						field = foundRobot.getReturnProperty(id);
					}
					break;
			}

			return field || {
				id,
				objectId: object,
				name: id,
				type: 'string',
				expression: id,
				systemExpression: `{=${object}:${id}}`,
			};
		}

		getOperatorLabel(id)
		{
			return this.getOperators()[id];
		}

		getOperators()
		{
			return {
				'!empty': BX.message('TUNNEL_MENU_CONDITION_NOT_EMPTY'),
				empty: BX.message('TUNNEL_MENU_CONDITION_EMPTY'),
				'=': BX.message('TUNNEL_MENU_CONDITION_EQ'),
				'!=': BX.message('TUNNEL_MENU_CONDITION_NE'),
				in: BX.message('TUNNEL_MENU_CONDITION_IN'),
				'!in': BX.message('TUNNEL_MENU_CONDITION_NOT_IN'),
				contain: BX.message('TUNNEL_MENU_CONDITION_CONTAIN'),
				'!contain': BX.message('TUNNEL_MENU_CONDITION_NOT_CONTAIN'),
				'>': BX.message('TUNNEL_MENU_CONDITION_GT'),
				'>=': BX.message('TUNNEL_MENU_CONDITION_GTE'),
				'<': BX.message('TUNNEL_MENU_CONDITION_LT'),
				'<=': BX.message('TUNNEL_MENU_CONDITION_LTE'),
			};
		}

		renderResponsible()
		{
			return Text5({
				color: Color.base4,
				numberOfLines: 1,
				ellipsize: 'end',
				text: `${BX.message('TUNNEL_MENU_RESPONSIBLE_TITLE')}: ${this.prepareResponsibleText(this.robot.responsible)}`,
			});
		}

		prepareResponsibleText(responsible)
		{
			if (!responsible || !responsible.label)
			{
				return BX.message('TUNNEL_MENU_PROPERTY_EMPTY');
			}

			return responsible.label;
		}

		renderMenu()
		{
			const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/tunnel-list/item/`;
			const imagePath = `${pathToExtension}images/settings.png`;

			return IconView({
				icon: Icon.MORE,
				color: Color.base4,
				size: 24,
				onClick: () => {
					this.menu = new ContextMenu({
						banner: {
							featureItems: [
								BX.message('TUNNEL_MENU_BANNER_DELAY'),
								BX.message('TUNNEL_MENU_BANNER_CONDITION'),
								BX.message('TUNNEL_MENU_BANNER_RESPONSIBLE'),
								BX.message('TUNNEL_MENU_BANNER_DEAL_SETTINGS'),
							],
							imagePath,
							qrauth: {
								redirectUrl: `/crm/deal/automation/${this.props.categoryId}/`,
								type: 'crm',
								analyticsSection: 'crm',
							},
						},
						actions: this.getMenuActions(),
						params: {
							title: BX.message('TUNNEL_MENU_TITLE'),
						},
					});
					this.menu.show(this.layout);
				},
			});
		}

		getMenuActions()
		{
			return [
				{
					id: 'tunnelDestination',
					title: BX.message('TUNNEL_MENU_TUNNEL_DESTINATION2'),
					icon: Icon.STAGE,
					onClickCallback: () => new Promise((resolve) => {
						this.menu.close(() => this.openCategoryList());
						resolve({ closeMenu: false });
					}),
				},
				{
					id: 'delete',
					title: BX.message('TUNNEL_MENU_DELETE'),
					icon: Icon.TRASHCAN,
					onClickCallback: () => this.openAlertOnDelete(),
				},
			];
		}

		async openCategoryList()
		{
			const { CategoryListView } = await requireLazy('crm:category-list-view');

			return CategoryListView.open(
				{
					entityTypeId: this.props.entityTypeId,
					kanbanSettingsId: this.props.kanbanSettingsId,
					selectAction: CategorySelectActions.SelectTunnelDestination,
					currentCategoryId: this.props.tunnel && this.props.tunnel.dstCategoryId,
					activeStageId: this.props.tunnel && this.props.tunnel.dstStageId,
					enableSelect: true,
					readOnly: true,
					showCounters: false,
					uid: this.robot.name,
					disabledCategoryIds: [this.props.kanbanSettingsId],
					onViewHidden: (params) => {
						const {
							selectedStage,
							selectedKanbanSettings,
						} = params;
						if (
							this.selectedKanbanSettings
							&& this.selectedKanbanSettings.id === selectedKanbanSettings.id
							&& this.selectedStage
							&& this.selectedStage.id === selectedStage.id
						)
						{
							return;
						}

						this.selectedStage = selectedStage;
						this.selectedKanbanSettings = selectedKanbanSettings;
						if (this.selectedStage && this.selectedKanbanSettings)
						{
							this.onChangeTunnelDestination();
						}
					},
				},
				{},
				this.layout,
			);
		}

		openAlertOnDelete()
		{
			return new Promise((resolve, reject) => {
				const { tunnel } = this.props;

				confirmDestructiveAction({
					title: '',
					description: Loc.getMessage('TUNNEL_MENU_DELETE_CONFIRM'),
					onDestruct: () => this.onDeleteTunnel(tunnel).then(resolve),
					onCancel: reject,
				});
			});
		}

		onChangeTunnelDestination()
		{
			const { onChangeTunnelDestination, tunnel } = this.props;
			if (typeof onChangeTunnelDestination === 'function')
			{
				onChangeTunnelDestination(
					tunnel,
					this.selectedStage,
					this.selectedKanbanSettings,
				);
			}
		}

		onDeleteTunnel(tunnel)
		{
			const { onDeleteTunnel } = this.props;

			if (typeof onDeleteTunnel === 'function')
			{
				onDeleteTunnel(tunnel);
			}

			return Promise.resolve();
		}
	}

	const stageFrom = `<svg width="24" height="19" viewBox="0 0 24 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.6524 8.56749L22.6598 8.57883L22.6678 8.58975C22.9841 9.02315 22.9841 9.60529 22.6678 10.0387L22.6598 10.0496L22.6524 10.0609L18.2125 16.8898C17.6407 17.666 16.7232 18.1284 15.7434 18.1284L3.54747 18.1284C1.85705 18.1284 0.5 16.7812 0.5 15.1356V3.49283C0.5 1.84723 1.85706 0.5 3.54747 0.5H15.7434C16.7232 0.5 17.6407 0.962421 18.2125 1.7386L22.6524 8.56749Z" fill="#COLOR#" stroke="${Color.bgContentPrimary.toHex()}"/></svg>`;
	const StageTo = `<svg width="26" height="21" viewBox="0 0 26 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 4.49283C1 2.5638 2.58826 1 4.54747 1H16.7434C17.8854 1 18.9575 1.54128 19.624 2.45429L24.0716 9.29495C24.5162 9.90397 24.5162 10.7245 24.0716 11.3335L19.624 18.1741C18.9575 19.0872 17.8854 19.6284 16.7434 19.6284L4.54747 19.6284C2.58826 19.6284 1 18.0646 1 16.1356V4.49283Z" fill="#COLOR#" stroke="${Color.bgContentPrimary.toHex()}" stroke-width="2"/></svg>`;

	module.exports = { TunnelListItem };
});
