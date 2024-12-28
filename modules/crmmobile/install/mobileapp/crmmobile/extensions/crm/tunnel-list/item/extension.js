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
	const AppTheme = require('apptheme');

	const DEFAULT_STAGE_BACKGROUND_COLOR = AppTheme.colors.accentSoftBlue1;

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

		componentDidMount()
		{
			// BX.addCustomEvent(`Crm.TunnelListItem::selectTunnelDestinationCategory-${this.robot.name}`, (category) => {
			// 	this.selectedCategory = category;
			// });
			// BX.addCustomEvent(`Crm.TunnelListItem::selectTunnelDestinationStage-${this.robot.name}`, (stage) => {
			// 	this.selectedStage = stage;
			// });
			// BX.addCustomEvent(`Crm.TunnelListItem::onChangeTunnelDestination-${this.robot.name}`, () => {
			// 	this.onChangeTunnelDestination();
			// });
		}

		render()
		{
			return View(
				{
					style: styles.wrapper,
				},
				this.renderIconContainer(),
				this.renderContainer(),
			);
		}

		renderIconContainer()
		{
			return View(
				{},
				Image(
					{
						style: styles.defaultIcon,
						svg: {
							content: svgImages.coloredIcon.replace(
								'#COLOR',
								this.props.tunnel.srcStageColor || DEFAULT_STAGE_BACKGROUND_COLOR,
							),
						},
					},
				),
				Image(
					{
						style: styles.coloredIcon,
						svg: {
							content: svgImages.coloredIcon.replace(
								'#COLOR',
								this.props.tunnel.dstStageColor || DEFAULT_STAGE_BACKGROUND_COLOR,
							),
						},
					},
				),
			);
		}

		renderContainer()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderContent(),
				this.renderMenu(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: styles.content,
				},
				this.renderTitle(),
				this.renderDelay(),
				this.renderCondition(),
				this.renderResponsible(),
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
					style: styles.titleWrapper,
				},
				Text({
					style: styles.titleItem,
					numberOfLines: 1,
					ellipsize: 'end',
					text: tunnelDstStageName,
				}),
				Text({
					style: styles.titleCategoryName,
					numberOfLines: 1,
					ellipsize: 'end',
					text: ` (${tunnelDstCategoryName})`,
				}),
			);
		}

		renderDelay()
		{
			return View(
				{},
				Text({
					style: styles.delayText,
					text: `${BX.message('TUNNEL_MENU_DELAY_TITLE2')}: ${this.prepareDelayText(this.robot.delay)}`,
				}),
			);
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
			return View(
				{},
				Text({
					style: styles.conditionText,
					text: `${BX.message('TUNNEL_MENU_CONDITION_TITLE')}: ${this.prepareConditionText(this.robot.conditionGroup)}`,
				}),
			);
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
			return View(
				{},
				Text({
					style: {
						color: AppTheme.colors.base4,
						fontSize: 12,
					},
					text: `${BX.message('TUNNEL_MENU_RESPONSIBLE_TITLE')}: ${this.prepareResponsibleText(this.robot.responsible)}`,
				}),
			);
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

			return View(
				{
					style: styles.menuContainer,
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
				},
				Image({
					style: styles.menuIcon,
					svg: {
						content: svgImages.menuIcon,
					},
				}),
			);
		}

		getMenuActions()
		{
			return [
				{
					id: 'tunnelDestination',
					title: BX.message('TUNNEL_MENU_TUNNEL_DESTINATION2'),
					data: {
						svgIcon: svgImages.menuTunnelDestinationIcon,
					},
					onClickCallback: () => new Promise((resolve) => {
						this.menu.close(() => this.openCategoryList());
						resolve({ closeMenu: false });
					}),
				},
				{
					id: 'delete',
					title: BX.message('TUNNEL_MENU_DELETE'),
					data: {
						svgIcon: svgImages.menuDeleteIcon,
					},
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

	const svgImages = {
		coloredIcon: '<svg width="24" height="19" viewBox="0 0 24 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.6524 8.56749L22.6598 8.57883L22.6678 8.58975C22.9841 9.02315 22.9841 9.60529 22.6678 10.0387L22.6598 10.0496L22.6524 10.0609L18.2125 16.8898C17.6407 17.666 16.7232 18.1284 15.7434 18.1284L3.54747 18.1284C1.85705 18.1284 0.5 16.7812 0.5 15.1356V3.49283C0.5 1.84723 1.85706 0.5 3.54747 0.5H15.7434C16.7232 0.5 17.6407 0.962421 18.2125 1.7386L22.6524 8.56749Z" fill="#COLOR" stroke="white"/></svg>',
		defaultTunnelIcon: '<svg width="24" height="19" viewBox="0 0 24 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.6524 8.56749L22.6598 8.57883L22.6678 8.58975C22.9841 9.02315 22.9841 9.60529 22.6678 10.0387L22.6598 10.0496L22.6524 10.0609L18.2125 16.8898C17.6407 17.666 16.7232 18.1284 15.7434 18.1284L3.54747 18.1284C1.85705 18.1284 0.5 16.7812 0.5 15.1356V3.49283C0.5 1.84723 1.85706 0.5 3.54747 0.5H15.7434C16.7232 0.5 17.6407 0.962421 18.2125 1.7386L22.6524 8.56749Z" fill="#A8B6C9" stroke="white"/></svg>',
		menuIcon: '<svg width="17" height="4" viewBox="0 0 17 4" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.87097 1.93548C3.87097 3.00442 3.00442 3.87097 1.93548 3.87097C0.866546 3.87097 0 3.00442 0 1.93548C0 0.866546 0.866546 0 1.93548 0C3.00442 0 3.87097 0.866546 3.87097 1.93548Z" fill="#bdc1c6"/><path d="M8.12903 3.87097C9.19797 3.87097 10.0645 3.00442 10.0645 1.93548C10.0645 0.866546 9.19797 0 8.12903 0C7.06009 0 6.19355 0.866546 6.19355 1.93548C6.19355 3.00442 7.06009 3.87097 8.12903 3.87097Z" fill="#bdc1c6"/><path d="M14.3226 3.87097C15.3915 3.87097 16.2581 3.00442 16.2581 1.93548C16.2581 0.866546 15.3915 0 14.3226 0C13.2536 0 12.3871 0.866546 12.3871 1.93548C12.3871 3.00442 13.2536 3.87097 14.3226 3.87097Z" fill="#bdc1c6"/></svg>',
		menuDeadLineIcon: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.70112 4.9459H11.2011V8.6959H14.9511V11.1959H8.70112V4.9459Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M0.87651 14.046C2.54359 17.7531 6.29603 20.0754 10.3575 19.9134C15.7458 19.8032 20.026 15.3486 19.9212 9.96025C19.921 5.89551 17.4509 2.23871 13.6802 0.720855C9.90949 -0.797 5.59477 0.128647 2.77849 3.05963C-0.0378001 5.9906 -0.790571 10.3388 0.87651 14.046ZM3.19531 13.0033C4.4369 15.7642 7.23159 17.4938 10.2565 17.3732C14.2695 17.291 17.4573 13.9734 17.3792 9.96036C17.379 6.93308 15.5394 4.20961 12.7311 3.07916C9.92278 1.94871 6.70932 2.63811 4.61184 4.821C2.51436 7.0039 1.95372 10.2423 3.19531 13.0033Z" fill="#828B95"/></svg>',
		menuConditionIcon: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.16775 4.32446H11.1008L10.7624 10.6607H8.50619L8.16775 4.32446Z" fill="#828B95"/><path d="M9.63401 15.093C10.6295 15.093 11.4365 14.286 11.4365 13.2905C11.4365 12.295 10.6295 11.488 9.63401 11.488C8.63852 11.488 7.83151 12.295 7.83151 13.2905C7.83151 14.286 8.63852 15.093 9.63401 15.093Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.62448 19.249C14.9399 19.249 19.249 14.9399 19.249 9.62448C19.249 4.30902 14.9399 0 9.62448 0C4.30902 0 0 4.30902 0 9.62448C0 14.9399 4.30902 19.249 9.62448 19.249ZM9.62448 16.9625C13.6772 16.9625 16.9625 13.6772 16.9625 9.62448C16.9625 5.5718 13.6772 2.28646 9.62448 2.28646C5.5718 2.28646 2.28645 5.5718 2.28645 9.62448C2.28645 13.6772 5.5718 16.9625 9.62448 16.9625Z" fill="#828B95"/></svg>',
		menuStageNameIcon: '<svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.1994 0H17.8269V19.375H20.1994V0Z" fill="#828B95"/><path fill-rule="evenodd" clip-rule="evenodd" d="M6.23315 1.64803H9.94995L16.1138 18.1313H12.3509L11.1042 14.4606H4.94035L3.64755 18.1313H0L6.23315 1.64803ZM5.88686 11.7596H10.1577L8.08001 5.71112H8.03384L5.88686 11.7596Z" fill="#828B95"/></svg>',
		menuTunnelDestinationIcon: '<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0 2.8125C0 1.2592 1.27237 0 2.84191 0H12.6122C13.527 0 14.3859 0.435847 14.9199 1.17102L18.4829 6.67926C18.839 7.16966 18.839 7.83034 18.4829 8.32074L14.9199 13.829C14.3859 14.5641 13.527 15 12.6122 15L2.84191 15C1.27237 15 0 13.7408 0 12.1875V2.8125Z" fill="#828B95"/></svg>',
		menuUserIcon: '<svg width="21" height="16" viewBox="0 0 21 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.0465 12.868C14.0465 12.1806 13.1676 11.3954 11.4368 10.9378C10.8505 10.7706 10.2931 10.5113 9.78453 10.1692C9.67332 10.1043 9.69022 9.50394 9.69022 9.50394L9.1328 9.4172C9.1328 9.36847 9.08514 8.64858 9.08514 8.64858C9.75208 8.41944 9.68346 7.06779 9.68346 7.06779C10.107 7.30799 10.3828 6.23835 10.3828 6.23835C10.8838 4.75226 10.1334 4.84211 10.1334 4.84211C10.2647 3.93489 10.2647 3.01285 10.1334 2.10563C9.79974 -0.903876 4.77656 -0.0868721 5.37218 0.896022C3.9041 0.61954 4.23909 4.03478 4.23909 4.03478L4.55752 4.91918C4.11615 5.21188 4.20283 5.54779 4.29964 5.92302C4.34001 6.07945 4.38213 6.24271 4.3885 6.41253C4.41926 7.26479 4.92935 7.08818 4.92935 7.08818C4.96079 8.49479 5.63922 8.67795 5.63922 8.67795C5.76666 9.56132 5.68722 9.41098 5.68722 9.41098L5.0835 9.48563C5.09167 9.68651 5.07566 9.88766 5.03583 10.0846C4.68506 10.2444 4.47031 10.3716 4.25768 10.4975C4.04002 10.6264 3.82456 10.754 3.4677 10.914C2.10474 11.5247 0.623481 12.3189 0.360152 13.3882C0.0968242 14.4575 0 16 0 16H14.5232L14.0465 12.868Z" fill="#828B95"/><path d="M15.915 1.8637H18.1447V4.6063H21V6.85494H18.1447V9.73393H15.915V6.85494H13.196V4.6063H15.915V1.8637Z" fill="#828B95"/></svg>',
		menuDeleteIcon: '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22602 0H6.15062V1.54677H1.43631C0.64306 1.54677 0 2.18983 0 2.98309V4.64037H15.377V2.98309C15.377 2.18983 14.7339 1.54677 13.9407 1.54677H9.22602V0Z" fill="#828B95"/><path d="M1.53777 6.18721H13.8394L12.6864 19.2351C12.6427 19.7294 12.2287 20.1084 11.7326 20.1084H3.64459C3.14842 20.1084 2.73444 19.7294 2.69077 19.2351L1.53777 6.18721Z" fill="#828B95"/></svg>',
	};

	const styles = {
		wrapper: {
			flexDirection: 'row',
			paddingTop: 10,
			paddingLeft: 20,
		},
		defaultIcon: {
			width: 24,
			height: 18,
		},
		coloredIcon: {
			width: 24,
			height: 18,
			marginLeft: 4,
			marginTop: -14,
		},
		container: {
			flexDirection: 'row',
			marginLeft: 13,
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 1,
			flex: 1,
		},
		content: {
			flexDirection: 'column',
			alignItems: 'flex-start',
			marginBottom: 14,
			flex: 1,
		},
		titleWrapper: {
			flexDirection: 'row',
			flexGrow: 2,
		},
		titleItem: {
			color: AppTheme.colors.base1,
			fontSize: 18,
			maxWidth: '70%',
			flexWrap: 'no-wrap',
		},
		titleCategoryName: {
			color: AppTheme.colors.base4,
			fontSize: 18,
			width: '30%',
			flexWrap: 'no-wrap',
		},
		delayText: {
			color: AppTheme.colors.base4,
			fontSize: 12,
		},
		conditionText: {
			color: AppTheme.colors.base4,
			fontSize: 12,
		},
		menuContainer: {
			width: 48,
			justifyContent: 'flex-start',
			alignItems: 'center',
		},
		menuIcon: {
			width: 17,
			height: 4,
			marginTop: 8,
		},
	};

	module.exports = { TunnelListItem };
});
