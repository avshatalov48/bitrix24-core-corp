/**
 * @module tasks/layout/checklist/legacy
 */
jn.define('tasks/layout/checklist/legacy', (require, exports, module) => {
	const platform = Application.getPlatform();
	const AppTheme = require('apptheme');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { CheckListController } = require('tasks/checklist/legacy');
	const { Loc } = require('loc');
	const { CheckBox } = require('layout/ui/checkbox');
	const { Type } = require('type');
	const { ProfileView } = require('user/profile');
	const { Haptics } = require('haptics');

	const defaultStyles = {
		width: '100%',
		background: AppTheme.colors.bgContentPrimary,
		borderRadius: 6,
		borderWidth: 1,
		borderColor: AppTheme.colors.bgSeparatorPrimary,
	};

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/layout/checklist/legacy/images`;

	class CheckList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.handleProps(props);

			this.state = {
				isEmpty: this.checkList.isEmpty(),
			};
		}

		componentWillReceiveProps(props)
		{
			this.handleProps(props);

			this.setState({
				isEmpty: this.checkList.isEmpty(),
			});
		}

		handleProps(props)
		{
			/** @type {CheckListTree} */
			this.checkList = props.checkList;

			this.checkListController = new CheckListController({
				taskId: props.taskId,
				userId: props.userId,
				taskGuid: props.taskGuid,
				mode: this.checkList.checkEditMode() ? 'edit' : 'view',
				diskConfig: props.diskConfig,
			});
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				(
					this.state.isEmpty
						? new Stub({
							isDisabled: !this.props.checkList.checkCanAdd(),
							isLoading: this.props.isLoading,
							onClick: this.onStubClick.bind(this),
						})
						: new Root({
							checkList: this.checkList,
							checkListController: this.checkListController,
							parentWidget: this.props.parentWidget,
							isShown: true,
							onFocus: (inputRef) => {
								this.setFocused(inputRef);
								this.props.onFocus(inputRef);
							},
							onChange: () => this.props.onChange(),
							onToggleSave: this.onToggleSave.bind(this),
							onShowStub: this.onShowStub.bind(this),
							onMoveToCheckList: this.onMoveToCheckList.bind(this),
							onMoveToNewCheckList: this.onMoveToNewCheckList.bind(this),
						})
				),
				(
					!this.state.isEmpty
					&& new CheckListButton({
						isDisabled: !this.props.checkList.checkCanAdd(),
						onClick: this.onAddClick.bind(this),
					})
				),
			);
		}

		onStubClick()
		{
			this.checkList.setActive(true);

			if (this.checkList.isEmpty())
			{
				this.checkList.addListItem(this.checkList.buildDefaultList());
			}

			this.props.onChange();

			this.setState({
				isEmpty: false,
			});
		}

		onAddClick()
		{
			this.checkList.addListItem(this.checkList.buildDefaultList());

			this.props.onChange();

			this.setState();
		}

		onShowStub()
		{
			this.setState({
				isEmpty: true,
			});
		}

		onMoveToCheckList(nodeId, childNodeId)
		{
			const targetCheckList = this.checkList.findChild(nodeId);

			targetCheckList.removeEmptyDescendant();

			const checkList = this.checkList.findChild(childNodeId);

			checkList.makeChildOf(targetCheckList);

			this.props.onChange();

			this.setState();
		}

		onMoveToNewCheckList(nodeId)
		{
			const newCheckList = this.checkList.buildDefaultList(false);

			this.checkList.addListItem(newCheckList);

			const checkList = this.checkList.findChild(nodeId);

			checkList.makeChildOf(newCheckList);

			this.props.onChange();

			this.setState();
		}

		saveCheckList()
		{
			if (!this.checkList.checkEditMode())
			{
				this.checkList.save()
					.then((response) => {
						if (response.hasOwnProperty('checkListItem'))
						{
							const result = response.checkListItem;
							if (result.hasOwnProperty('traversedItems'))
							{
								Object.keys(result.traversedItems)
									.forEach((nodeId) => {
										const item = this.checkList.findChild(nodeId);
										if (
											!Type.isUndefined(item)
											&& item.getId() === null
										)
										{
											item.setId(result.traversedItems[nodeId].id);
										}
									})
								;
							}
						}
					})
				;
			}
		}

		onToggleSave(itemId, isComplete)
		{
			if (isComplete)
			{
				this.checkList.complete(itemId);
			}
			else
			{
				this.checkList.renew(itemId);
			}
		}

		setFocused(inputRef)
		{
			this.inputRef = inputRef;
		}

		isFocused()
		{
			return this.inputRef && this.inputRef.isFocused();
		}

		removeFocus()
		{
			Keyboard.dismiss();
			if (this.inputRef)
			{
				this.inputRef.blur();
			}
		}
	}

	class Stub extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						paddingHorizontal: 11,
						minHeight: 52,
						opacity: (this.props.isDisabled ? 0.5 : 1),
						...defaultStyles,
					},
					onClick: () => {
						if (!this.props.isLoading && !this.props.isDisabled)
						{
							this.props.onClick();
						}
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							opacity: (this.props.isLoading ? 0.5 : 1),
						},
					},
					View(
						{
							style: {
								justifyContent: 'center',
							},
						},
						new CheckBox({
							checked: true,
							isDisabled: true,
							style: {
								opacity: 1,
							},
						}),
					),
					Text({
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_STUB_CREATE_CHECKLIST'),
						style: {
							marginLeft: 6,
							fontSize: 16,
							fontWeight: '400',
							color: AppTheme.colors.base3,
						},
					}),
				),
				(this.props.isLoading && Loader({
					style: {
						position: 'absolute',
						left: '50%',
						alignSelf: 'center',
						width: 30,
						height: 30,
					},
					tintColor: AppTheme.colors.bgSeparatorSecondary,
					animating: true,
					size: 'small',
				})),
			);
		}
	}

	class CheckListButton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {

						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingVertical: 14,
						paddingHorizontal: 11,
						minHeight: 52,
						marginTop: 6,
						opacity: this.props.isDisabled ? 0.5 : 1,
						...defaultStyles,
					},
					testId: 'checklist-add-btn',
					onClick: () => {
						if (!this.props.isDisabled)
						{
							this.props.onClick();
						}
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
					},
					value: `[d type=dot color=${AppTheme.colors.base3}]${Loc.getMessage(
						'TASKSMOBILE_LAYOUT_CHECKLIST_ADD_TEXT',
					)}[/d]`,
				}),
			);
		}
	}

	class Input extends LayoutComponent
	{
		showSelector(context)
		{
			const selector = EntitySelectorFactory.createByType('user', {
				provider: {
					context: `TASKS_MEMBER_SELECTOR_EDIT_${context}`,
				},
				createOptions: {
					enableCreation: false,
				},
				initSelectedIds: [],
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onClose: this.onCloseSelector.bind(this, context),
				},
				widgetParams: {
					title: Loc.getMessage(
						`TASKSMOBILE_LAYOUT_CHECKLIST_${context.toUpperCase()}_SELECTOR_TITLE`,
					),
					backdrop: {
						mediumPositionPercent: 70,
					},
				},
			});

			return selector.show({}, this.props.parentWidget);
		}

		addMember(member)
		{
			const currentTitle = this.props.checkList.getTitle();

			this.props.onMemberSelected(
				member,
				`${currentTitle.slice(0, Math.max(0, this.cursorPosition)).trim()
				} ${member.nameFormatted} ${
					currentTitle.substring(this.cursorPosition, currentTitle.length).trim()}`,
			);
		}

		getTitleWithUrls(title, membersMap)
		{
			const members = [...membersMap.values()];

			const escapeRegExp = (string) => string.replaceAll(/[$()*+./?[\\\]^{|}-]/g, '\\$&');

			members.forEach(({ id, nameFormatted, type }) => {
				const regExp = new RegExp(escapeRegExp(nameFormatted), 'g');
				title = title.replace(
					regExp,
					`[COLOR=${AppTheme.colors.accentMainLinks}][URL=${id}]${nameFormatted}[/URL][/COLOR]`,
				);
			});

			return title;
		}

		onCloseSelector(context, currentEntities)
		{
			currentEntities.forEach((currentEntity) => {
				this.addMember({
					id: currentEntity.id,
					nameFormatted: currentEntity.title,
					type: context,
					avatar: currentEntity.imageUrl,
				});
			});
		}

		onLinkClick({ url })
		{
			const userId = url;
			const widgetParams = { groupStyle: true };
			const isBackdrop = true;

			widgetParams.backdrop = {
				bounceEnable: false,
				swipeAllowed: true,
				showOnTop: true,
				hideNavigationBar: false,
				horizontalSwipeAllowed: false,
				navigationBarColor: AppTheme.colors.bgSecondary,
			};

			this.props.parentWidget.openWidget('list', widgetParams)
				.then((list) => ProfileView.open({ userId, isBackdrop }, list))
			;
		}
	}

	class CheckBoxCounter extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				completedCount: props.completedCount,
				totalCount: props.totalCount,
				text: `${props.completedCount}/${props.totalCount}`,
			};
		}

		componentWillReceiveProps(props)
		{
			this.setState({
				completedCount: props.completedCount,
				totalCount: props.totalCount,
				text: `${props.completedCount}/${props.totalCount}`,
			});
		}

		render()
		{
			let fontSize = (this.state.completedCount > 9 || this.state.totalCount > 9) ? 8 : 9;
			fontSize = (this.state.completedCount > 9 && this.state.totalCount > 9) ? 7 : fontSize;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						width: 23,
						height: 23,
						borderRadius: 11.5,
						opacity: this.props.isDisabled ? 0.5 : 1,
						backgroundColor: AppTheme.colors.base5,
					},
					onClick: () => {
						if (!this.props.isDisabled)
						{
							this.props.onClick();
						}
					},
				},
				ProgressView(
					{
						style: {
							width: 23,
							height: 23,
							justifyContent: 'center',
							alignItems: 'center',
						},
						params: {
							type: 'circle',
							currentPercent: this.calculateCurrentPercent(
								this.state.completedCount,
								this.state.totalCount,
							),
							color: AppTheme.colors.accentExtraDarkblue,
						},
						ref: (ref) => {
							this.progressRef = ref;
						},
					},
					View(
						{
							style: {
								position: 'absolute',
								width: 20,
								height: 20,
								justifyContent: 'center',
								alignItems: 'center',
								borderRadius: 10,
								opacity: 1,
								backgroundColor: AppTheme.colors.bgContentPrimary,
							},
						},
						this.props.visible && Text({
							style: {
								fontSize,
								textAlign: 'center',
								color: AppTheme.colors.base3,
							},
							numberOfLines: 1,
							text: this.state.text,
						}),
					),
				),
			);
		}

		updateProgress(completedCount, totalCount)
		{
			this.progressRef.setProgress(
				this.calculateCurrentPercent(completedCount, totalCount),
				{
					duration: 200,
					style: 'linear',
				},
			);

			// todo change after added callback to setProgress
			setTimeout(() => {
				this.setState({
					completedCount,
					totalCount,
					text: `${completedCount}/${totalCount}`,
				});
			}, 220);
		}

		calculateCurrentPercent(completedCount, totalCount)
		{
			return parseInt(
				(completedCount > 0 ? (completedCount * 100 / totalCount) : 0).toFixed(0),
				10,
			);
		}
	}

	class Root extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.descendantsRefs = [];
		}

		render()
		{
			const descendants = this.renderDescendants();

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				descendants.length > 0
				&& View(
					{},
					...descendants,
				),
			);
		}

		renderDescendants()
		{
			return this.props.checkList.getDescendants()
				.map((descendant, index) => {
					return new List({
						checkListController: this.props.checkListController,
						parentWidget: this.props.parentWidget,
						isShown: this.props.isShown === true,
						checkList: descendant,
						onFocus: (inputRef) => this.props.onFocus(inputRef),
						onChange: () => this.props.onChange(),
						onToggleSave: (itemId, isComplete) => {
							this.props.onToggleSave(itemId, isComplete);
						},
						onMoveToCheckList: (nodeId, childNodeId) => {
							this.props.onMoveToCheckList(nodeId, childNodeId);
						},
						onMoveToNewCheckList: (nodeId) => {
							this.props.onMoveToNewCheckList(nodeId);
						},
						onRemove: () => {
							this.setState(
								{},
								() => {
									if (this.props.checkList.getDescendants().length === 0)
									{
										this.props.onShowStub();
									}
								},
							);
						},
						ref: (ref) => this.descendantsRefs[index] = ref,
					});
				})
			;
		}
	}

	class List extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.descendantsRefs = [];

			this.isTogglingList = false;

			this.title = props.checkList.getTitle();
			this.descendantsHeight = 0;
			this.state = {
				focus: false,
				isShown: props.isShown,
			};
		}

		componentWillReceiveProps(props)
		{
			this.title = props.checkList.getTitle();

			this.setState({
				focus: false,
			});
		}

		render()
		{
			const descendants = this.renderDescendants();

			return View(
				{
					style: {

						marginTop: this.props.checkList.isFirstDescendant() ? 0 : 6,
						flexDirection: 'column',
						height: 0,
						opacity: 0,
						...defaultStyles,
					},
					ref: (ref) => {
						this.ref = ref;
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							paddingHorizontal: 11,
							height: 52,
						},
						testId: `list-${this.props.checkList.getNodeId()}`,
					},
					new ListCheckBox({
						completedCount: this.props.checkList.getCompletedCount(),
						totalCount: this.props.checkList.getTotalCount(),
						ref: (ref) => this.checkBox = ref,
					}),
					new ListInput({
						title: this.title,
						focus: this.state.focus,
						checkList: this.props.checkList,
						parentWidget: this.props.parentWidget,
						onChangeText: (title) => this.title = title,
						onFocus: (inputRef) => this.props.onFocus(inputRef),
						onBlur: () => this.onChangeTitle(this.title),
						onTextClick: () => {
							const focusedItem = this.props.checkList.getFocusedDescendant();
							if (focusedItem)
							{
								focusedItem.blur();
							}
							this.setState({ focus: true });
						},
						onMemberSelected: this.onMemberSelected.bind(this),
						ref: (ref) => this.inputRef = ref,
					}),
					new ListSettings({
						checkList: this.props.checkList,
						parentWidget: this.props.parentWidget,
						isShown: this.state.isShown,
						onListToggle: this.onListToggle.bind(this),
						onShowSelector: (context) => this.inputRef.showSelector(context),
						onChangeTitle: () => this.setState({ focus: true }),
						onRemove: this.onRemove.bind(this),
					}),
				),
				(
					descendants.length > 0
					&& View(
						{
							ref: () => {
								if (this.state.isShown)
								{
									setTimeout(() => {
										this.show();
									}, 100);
								}
							},
							onLayout: this.onLayout.bind(this),
						},
						...descendants,
					)
				),
				new AddItemButton({
					nodeId: this.props.checkList.getNodeId(),
					isDisabled: !this.props.checkList.checkCanAdd(),
					isEmpty: this.props.checkList.isEmpty(),
					isShown: this.state.isShown,
					onClick: this.onAddClick.bind(this),
				}),
			);
		}

		renderDescendants()
		{
			return this.props.checkList.getDescendants()
				.map((descendant, index) => {
					return new Item({
						checkListController: this.props.checkListController,
						parentWidget: this.props.parentWidget,
						isShown: this.state.isShown,
						checkList: descendant,
						onFocus: (inputRef) => this.props.onFocus(inputRef),
						onChange: () => this.props.onChange(),
						onToggleSave: (itemId, isComplete) => {
							this.props.onToggleSave(itemId, isComplete);
						},
						onAdd: (emptyItemNodeId) => {
							this.setState(
								{ focus: false },
								() => {
									this.focusDescendant(emptyItemNodeId);
								},
							);
						},
						onToggleComplete: () => {
							this.checkBox.updateCounter(
								this.props.checkList.getCompletedCount(),
								this.props.checkList.getTotalCount(),
							);
						},
						onMoveToCheckList: (nodeId, childNodeId) => {
							this.props.onMoveToCheckList(nodeId, childNodeId);
						},
						onMoveToNewCheckList: (nodeId) => {
							this.props.onMoveToNewCheckList(nodeId);
						},
						onMoveRight: () => this.setState({ focus: false }),
						onMoveLeft: () => this.setState({ focus: false }),
						onRemove: () => this.setState(),
						ref: (ref) => this.descendantsRefs[index] = ref,
					});
				});
		}

		onLayout({ height })
		{
			if (height === this.descendantsHeight)
			{
				return;
			}

			this.descendantsHeight = height;
			if (!this.isTogglingList && this.state.isShown)
			{
				this.show();
			}
		}

		onAddClick()
		{
			const emptyDescendant = this.props.checkList.getEmptyDescendant();
			if (emptyDescendant)
			{
				this.submitDescendant(emptyDescendant.getNodeId());

				return;
			}

			const focusedItem = this.props.checkList.getFocusedDescendant();
			if (focusedItem && focusedItem.getTitle() !== '')
			{
				this.submitDescendant(focusedItem.getNodeId());
			}
			else
			{
				this.props.checkList.getList().addListItem();

				this.setState({ focus: false }, () => {
					if (!this.props.checkList.checkEditMode())
					{
						this.props.onChange();
					}
				});
			}
		}

		onListToggle()
		{
			this.isTogglingList = true;

			const promise = this.state.isShown ? this.hide() : this.show();

			promise.then(() => {
				this.isTogglingList = false;

				this.setState({
					focus: false,
					isShown: !this.state.isShown,
				});
			});
		}

		onChangeTitle(title)
		{
			if (title === '')
			{
				this.setState({ focus: false });
			}
			else
			{
				this.props.checkList.setTitle(title);

				this.setState(
					{
						focus: false,
					},
					() => {
						if (!this.props.checkList.checkEditMode())
						{
							this.props.onChange();
						}
					},
				);
			}
		}

		onMemberSelected(member, title)
		{
			this.props.checkList.setTitle(title);
			this.props.checkList.addMember(member);

			this.setState(
				{
					title,
					focus: false,
				},
				() => {
					if (!this.props.checkList.checkEditMode())
					{
						this.props.onChange();
					}
				},
			);
		}

		onRemove()
		{
			this.setState(
				{ isShown: false },
				() => {
					if (!this.props.checkList.checkCanRemove())
					{
						return;
					}

					this.props.checkList.removeListItem();

					this.props.onRemove();

					if (!this.props.checkList.checkEditMode())
					{
						this.props.onChange();
					}
				},
			);
		}

		submitDescendant(nodeId)
		{
			this.descendantsRefs.forEach((descendant) => {
				if (descendant)
				{
					const item = descendant.findChild(nodeId);
					if (item)
					{
						item.submit();
					}
				}
			});
		}

		focusDescendant(nodeId)
		{
			this.descendantsRefs.forEach((descendant) => {
				if (descendant)
				{
					const item = descendant.findChild(nodeId);
					if (item)
					{
						item.focus();
					}
				}
			});
		}

		show()
		{
			this.isAnimating = true;

			return new Promise((resolve) => {
				if (this.ref)
				{
					this.ref.animate({
						duration: 200,
						height: this.calculateListHeight(),
						opacity: 1,
						style: 'linear',
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		hide(force = false)
		{
			this.isAnimating = true;

			return new Promise((resolve) => {
				if (this.ref)
				{
					this.ref.animate({
						duration: 200,
						height: force ? 0 : 52,
						opacity: force ? 0 : 1,
						style: 'linear',
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		calculateListHeight()
		{
			const { checkList } = this.props;
			const borderOffset = platform === 'android' ? 4 : 0;
			const btnHeight = 52;
			const headerHeight = 52;
			const itemHeight = btnHeight + 1;
			const minHeight = borderOffset + btnHeight + headerHeight;
			const checklistHeight = minHeight + parseInt(checkList.getDescendantsCount() * itemHeight, 10);
			const descendantsHeight = minHeight + this.descendantsHeight;

			return Math.max(descendantsHeight, checklistHeight);
		}
	}

	class ListCheckBox extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						marginRight: 12,
					},
				},
				new CheckBoxCounter({
					visible: true,
					completedCount: this.props.completedCount,
					totalCount: this.props.totalCount,
					onClick: () => {},
					ref: (ref) => {
						this.counterRef = ref;
					},
				}),
			);
		}

		updateCounter(completedCount, totalCount)
		{
			this.counterRef.updateProgress(completedCount, totalCount);
		}
	}

	class ListInput extends Input
	{
		constructor(props)
		{
			super(props);

			this.cursorPosition = props.checkList.getTitle().length;
		}

		componentWillReceiveProps(props)
		{
			this.cursorPosition = props.checkList.getTitle().length;
		}

		render()
		{
			return (
				this.props.focus
					? this.renderTextField()
					: this.renderText()
			);
		}

		renderTextField()
		{
			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'center',
					},
				},
				TextField({
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_LIST_INPUT_PLACEHOLDER'),
					placeholderTextColor: AppTheme.colors.base4,
					multiline: false,
					focus: true,
					returnKeyType: 'done',
					style: {
						flex: 1,
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
					},
					forcedValue: this.props.title,
					onChangeText: (value) => this.props.onChangeText(value),
					onFocus: () => {
						setTimeout(() => {
							this.props.onFocus(this.inputRef);
						}, 100);
					},
					onBlur: () => this.props.onBlur(),
					onSelectionChange: (data) => {
						const isFocused = this.inputRef && this.inputRef.isFocused();
						if (!isFocused || !data.selection)
						{
							return;
						}

						const { start, end } = data.selection;
						if (start === end)
						{
							this.cursorPosition = start;
						}
					},
					onSubmitEditing: () => {
						Keyboard.dismiss();
						this.inputRef.blur();
					},
					ref: (ref) => this.inputRef = ref,
				}),
			);
		}

		renderText()
		{
			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'center',
					},
					onClick: () => {
						if (this.props.checkList.checkCanUpdate())
						{
							this.props.onTextClick();
						}
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
					},
					ellipsize: 'end',
					numberOfLines: 1,
					linksUnderline: false,
					value: this.getTitleWithUrls(
						this.props.title,
						this.props.checkList.getMembers(),
					),
					onLinkClick: this.onLinkClick.bind(this),
				}),
			);
		}
	}

	class ListSettings extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				View(
					{
						testId: `list-toggle-${this.props.checkList.getNodeId()}`,
						ref: (ref) => this.arrowRef = ref,
					},
					Image({
						style: {
							alignItems: 'center',
							justifyContent: 'center',
							width: 24,
							height: 24,
						},
						tintColor: AppTheme.colors.base3,
						svg: {
							uri: `${pathToExtension}/arrow.svg?2`,
						},
						resizeMode: 'center',
						onClick: () => {
							this.animateArrow().then(() => {
								this.props.onListToggle();
							});
						},
					}),
				),
				View(
					{
						testId: `list-menu-${this.props.checkList.getNodeId()}`,
					},
					Image({
						style: {
							alignItems: 'center',
							justifyContent: 'center',
							width: 24,
							height: 24,
						},
						tintColor: AppTheme.colors.base3,
						svg: {
							uri: `${pathToExtension}/three-dots.svg?2`,
						},
						resizeMode: 'center',
						onClick: () => {
							this.settingsMenu = new ContextMenu({
								actions: this.getActions(),
								params: {
									title: this.props.checkList.getTitle(),
									showCancelButton: false,
								},
							});

							this.settingsMenu.show(this.props.parentWidget);
						},
					}),
				),
			);
		}

		getActions()
		{
			return [
				{
					id: 'list-add-auditor',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_AUDITOR'),
					isDisabled: !this.props.checkList.checkCanUpdate(),
					data: {
						svgIcon: svgImages.addAuditor,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onShowSelector('auditor');
							});
							resolve();
						});
					},
				},
				{
					id: 'list-add-accomplices',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ACCOMPLICE'),
					isDisabled: (
						!this.props.checkList.checkCanUpdate()
						|| !this.props.checkList.checkCanAddAccomplice()
					),
					data: {
						svgIcon: svgImages.addAccomplices,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onShowSelector('accomplice');
							});
							resolve();
						});
					},
				},
				{
					id: 'list-change-title',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_CHANGE_NAME'),
					isDisabled: !this.props.checkList.checkCanUpdate(),
					data: {
						svgIcon: svgImages.change,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onChangeTitle();
							});
							resolve();
						});
					},
				},
				{
					id: 'list-remove',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REMOVE'),
					isDisabled: !this.props.checkList.checkCanRemove(),
					data: {
						svgIcon: svgImages.remove,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onRemove();
							});
							resolve();
						});
					},
				},
			];
		}

		animateArrow()
		{
			return new Promise((resolve) => {
				this.arrowRef.animate({
					duration: 200,
					rotate: this.props.isShown ? 180 : 0,
				}, resolve);
			});
		}
	}

	class Item extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				title: props.checkList.getTitle(),
				focus: props.checkList.isFocused(),
				isComplete: props.checkList.getIsComplete(),
			};

			this.descendantsRefs = [];
		}

		componentWillReceiveProps(props)
		{
			if (
				this.state.title !== props.checkList.getTitle()
				|| this.state.focus !== props.checkList.isFocused()
				|| this.state.isComplete !== props.checkList.getIsComplete()
			)
			{
				this.setState({
					title: props.checkList.getTitle(),
					focus: props.checkList.isFocused(),
					isComplete: props.checkList.getIsComplete(),
				});
			}
		}

		render()
		{
			const descendants = this.renderDescendants();

			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'flex-start',
							paddingHorizontal: 11,
							minHeight: 52,
							borderTopWidth: this.props.checkList.isFirstListDescendant() ? 1 : 0,
							borderTopColor: AppTheme.colors.bgSeparatorSecondary,
							borderBottomWidth: 1,
							borderBottomColor: AppTheme.colors.bgSeparatorSecondary,
						},
						testId: `item-${this.props.checkList.getNodeId()}`,
					},
					View(
						{
							style: {
								justifyContent: 'center',
								alignItems: 'center',
								minHeight: 52,
							},
							testId: `item-${this.props.checkList.getDisplaySortIndex()}`,
						},
						new DepthDots({
							displaySortIndex: this.props.checkList.getDisplaySortIndex(),
						}),
					),
					View(
						{
							style: {
								justifyContent: 'center',
								marginRight: 6,
								alignItems: 'center',
								minHeight: 52,
							},
						},
						(descendants.length > 0 && !this.state.isComplete)
							? new CheckBoxCounter({
								isDisabled: !this.props.checkList.checkCanToggle(),
								visible: false,
								completedCount: this.props.checkList.getCompletedCount(),
								totalCount: this.props.checkList.getTotalCount(),
								onClick: this.onToggleComplete.bind(this),
								ref: (ref) => {
									this.counterRef = ref;
								},
							}) : new CheckBox({
								isDisabled: !this.props.checkList.checkCanToggle(),
								checked: this.state.isComplete,
								onClick: this.onToggleComplete.bind(this),
							}),
					),
					new ItemImportant({
						isImportant: this.props.checkList.getIsImportant(),
						ref: (ref) => {
							this.importantRef = ref;
						},
					}),
					new ItemInput({
						title: this.state.title,
						focus: this.state.focus,
						checkList: this.props.checkList,
						parentWidget: this.props.parentWidget,
						onTextClick: () => {
							this.props.checkList.focus();
							this.setState({ focus: true });
						},
						onChangeText: this.onChangeTitle.bind(this),
						onFocus: (inputRef) => {
							this.props.checkList.focus();
							this.props.onFocus(inputRef);
						},
						onBlur: () => {
							this.props.checkList.blur();
							if (
								this.state.title === ''
								&& this.isShownSettings !== true
							)
							{
								this.remove();
							}
							else
							{
								this.setState({ focus: false });
							}
						},
						onMemberSelected: this.onMemberSelected.bind(this),
						onSubmit: () => this.submit(),
						onRemove: () => this.remove(),
						ref: (ref) => {
							this.inputRef = ref;
						},
					}),
					new ItemAttachments({
						checkList: this.props.checkList,
						checkListController: this.props.checkListController,
						parentWidget: this.props.parentWidget,
						onAttachment: this.onAttachment.bind(this),
						ref: (ref) => {
							this.attachmentsRef = ref;
						},
					}),
					new ItemSettings({
						checkList: this.props.checkList,
						parentWidget: this.props.parentWidget,
						onBeforeShow: () => {
							this.isShownSettings = true;
						},
						onAfterClose: () => {
							this.isShownSettings = false;
							if (this.state.title === '')
							{
								this.focus();
							}
						},
						onMoveRight: () => this.moveRight(),
						onMoveLeft: () => this.moveLeft(),
						onToggleImportant: this.onToggleImportant.bind(this),
						onAddFile: () => this.attachmentsRef.addFile(),
						onShowSelector: (context) => this.inputRef.showSelector(context),
						onMoveTo: () => {
							if (this.props.checkList.hasAnotherCheckLists())
							{
								this.showList();
							}
							else
							{
								this.setState({}, () => {
									this.props.onMoveToNewCheckList(
										this.props.checkList.getNodeId(),
									);
								});
							}
						},
						onRemove: () => this.remove(),
					}),
				),
				descendants.length > 0
				&& View(
					{},
					...descendants,
				),
			);
		}

		renderDescendants()
		{
			return this.props.checkList.getDescendants()
				.map((descendant, index) => {
					return new Item({
						checkListController: this.props.checkListController,
						parentWidget: this.props.parentWidget,
						checkList: descendant,
						onFocus: (inputRef) => this.props.onFocus(inputRef),
						onChange: () => this.props.onChange(),
						onToggleSave: (itemId, isComplete) => {
							this.props.onToggleSave(itemId, isComplete);
						},
						onAdd: (emptyItemNodeId) => this.props.onAdd(emptyItemNodeId),
						onToggleComplete: () => {
							if (this.counterRef)
							{
								this.counterRef.updateProgress(
									this.props.checkList.getCompletedCount(),
									this.props.checkList.getTotalCount(),
								);
							}
						},
						onMoveToCheckList: (nodeId, childNodeId) => {
							this.props.onMoveToCheckList(nodeId, childNodeId);
						},
						onMoveToNewCheckList: (nodeId) => {
							this.props.onMoveToNewCheckList(nodeId);
						},
						onMoveRight: () => this.props.onMoveRight(),
						onMoveLeft: () => this.props.onMoveLeft(),
						onRemove: () => this.props.onRemove(),
						ref: (ref) => {
							this.descendantsRefs[index] = ref;
						},
					});
				});
		}

		onChangeTitle(title)
		{
			if (title === '' && !this.props.checkList.checkCanRemove())
			{
				this.setState({ focus: false });
			}
			else
			{
				this.props.checkList.setTitle(title);

				this.setState(
					{
						title,
						focus: true,
					},
					() => {
						if (!this.props.checkList.checkEditMode())
						{
							this.props.onChange();
						}
					},
				);
			}
		}

		onMemberSelected(member, title)
		{
			this.props.checkList.setTitle(title);
			this.props.checkList.addMember(member);

			this.setState(
				{
					title,
					focus: false,
				},
				() => {
					if (!this.props.checkList.checkEditMode())
					{
						this.props.onChange();
					}
				},
			);
		}

		onToggleComplete()
		{
			Haptics.impactLight();

			this.props.checkList.toggleComplete();
			this.props.onToggleComplete();

			this.setState(
				{
					isComplete: this.props.checkList.getIsComplete(),
				},
				() => {
					if (
						!this.props.checkList.checkEditMode()
						&& this.props.checkList.getId()
					)
					{
						this.props.onToggleSave(
							this.props.checkList.getId(),
							this.props.checkList.getIsComplete(),
						);
					}
				},
			);
		}

		onToggleImportant()
		{
			this.props.checkList.toggleImportant();

			if (!this.props.checkList.checkEditMode())
			{
				this.props.onChange();
			}

			if (this.props.checkList.getIsImportant())
			{
				this.importantRef.show();
			}
			else
			{
				this.importantRef.hide();
			}
		}

		onAttachment()
		{
			this.attachmentsRef.setState();

			if (!this.props.checkList.checkEditMode())
			{
				this.props.onChange();
			}
		}

		findChild(nodeId)
		{
			if (this.props.checkList.getNodeId() === nodeId)
			{
				return this;
			}

			let found = null;

			this.descendantsRefs
				.forEach((descendant) => {
					if (descendant && found === null)
					{
						found = descendant.findChild(nodeId);
					}
				})
			;

			return found;
		}

		moveRight()
		{
			this.props.checkList.tabIn();

			if (!this.props.checkList.checkEditMode())
			{
				this.props.onChange();
			}

			this.setState({}, () => {
				this.props.onMoveRight();
			});
		}

		moveLeft()
		{
			this.props.checkList.tabOut();

			if (!this.props.checkList.checkEditMode())
			{
				this.props.onChange();
			}

			this.setState({}, () => {
				this.props.onMoveLeft();
			});
		}

		showList()
		{
			const actions = [];

			this.props.checkList
				.getAnotherCheckLists()
				.forEach((checkList) => {
					actions.push({
						id: checkList.id,
						title: checkList.title,
						onClickCallback: () => {
							return new Promise((resolve) => {
								this.listMenu.close(() => {
									this.setState({}, () => {
										this.props.onMoveToCheckList(
											checkList.id,
											this.props.checkList.getNodeId(),
										);
									});
								});
								resolve();
							});
						},
					});
				})
			;

			actions.push({
				id: 'new-list',
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO_NEW'),
				isDisabled: !this.props.checkList.checkCanAdd(),
				data: {
					svg: {
						uri: `${pathToExtension}/new-list.svg?2`,
					},
				},
				onClickCallback: () => {
					return new Promise((resolve) => {
						this.listMenu.close(() => {
							this.setState({}, () => {
								this.props.onMoveToNewCheckList(
									this.props.checkList.getNodeId(),
								);
							});
						});
						resolve();
					});
				},
			});

			this.listMenu = new ContextMenu({
				actions,
				params: {
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO'),
					showCancelButton: false,
				},
			});

			this.listMenu.show(this.props.parentWidget);
		}

		submit()
		{
			if (this.state.title)
			{
				this.addEmpty(this.props.checkList.getNodeId());
			}
			else
			{
				if (platform === 'android')
				{
					Keyboard.dismiss();
				}
				this.inputRef.blur();
			}
		}

		focus()
		{
			this.setState({ focus: true });
		}

		addEmpty(nodeId)
		{
			const dependsItem = this.props.checkList.getList().findChild(nodeId);
			const emptyItem = dependsItem.getParent().addListItem(null, dependsItem);

			emptyItem.blur();

			this.props.onAdd(emptyItem.getNodeId());

			if (!this.props.checkList.checkEditMode())
			{
				this.props.onChange();
			}
		}

		remove()
		{
			if (!this.props.checkList.checkCanRemove())
			{
				return;
			}

			this.setState({
				focus: false,
			}, () => {
				this.props.checkList.blur();

				this.props.checkList.removeListItem();

				this.props.onRemove();

				if (!this.props.checkList.checkEditMode())
				{
					this.props.onChange();
				}
			});
		}
	}

	class DepthDots extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.maxDisplayDepth = 5;
		}

		render()
		{
			const dots = [];

			const inputDepth = (
				this.props.displaySortIndex.match(new RegExp('\\.', 'g'))
				|| []
			).length;
			if (!inputDepth)
			{
				return null;
			}

			const depth = Math.min(
				Math.max(
					parseInt(inputDepth, 10),
					1,
				),
				this.maxDisplayDepth,
			);

			for (let k = 0; k < depth; k++)
			{
				dots.push(
					Image({
						style: {
							alignItems: 'center',
							justifyContent: 'center',
							width: 6,
							height: 6,
						},
						tintColor: AppTheme.colors.base3,
						svg: {
							uri: `${pathToExtension}/depth-dot.svg?2`,
						},
						resizeMode: 'center',
					}),
				);
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingRight: 8,
					},
				},
				...dots,
			);
		}
	}

	class ItemImportant extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						minHeight: 52,
						width: this.props.isImportant ? 24 : 0,
						opacity: this.props.isImportant ? 1 : 0,
					},
					ref: (ref) => this.ref = ref,
				},
				Image({
					style: {
						width: 24,
						height: 24,
					},
					svg: {
						uri: `${pathToExtension}/important-item.svg?2`,
					},
					resizeMode: 'center',
				}),
			);
		}

		show()
		{
			return new Promise((resolve) => {
				if (this.ref)
				{
					this.ref.animate({
						duration: 200,
						width: 24,
						opacity: 1,
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}

		hide()
		{
			return new Promise((resolve) => {
				if (this.ref)
				{
					this.ref.animate({
						duration: 200,
						opacity: 0,
						width: 0,
					}, resolve);
				}
				else
				{
					resolve();
				}
			});
		}
	}

	class ItemInput extends Input
	{
		constructor(props)
		{
			super(props);

			this.cursorPosition = props.title.length;
		}

		componentWillReceiveProps(props)
		{
			this.cursorPosition = props.title.length;
		}

		render()
		{
			return (
				this.props.focus
					? this.renderTextField()
					: this.renderText()
			);
		}

		renderTextField()
		{
			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'center',
						alignSelf: 'center',
						paddingVertical: 9,
						marginLeft: 6,
					},
				},
				TextField({
					placeholder: Loc.getMessage('TASKSMOBILE_LAYOUT_ITEM_INPUT_PLACEHOLDER'),
					placeholderTextColor: AppTheme.colors.base4,
					multiline: false,
					focus: true,
					returnKeyType: 'done',
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
					},
					forcedValue: this.props.title,
					onChangeText: (value) => {
						this.props.onChangeText(value);
					},
					onFocus: () => {
						setTimeout(() => {
							if (this.inputRef)
							{
								this.props.onFocus(this.inputRef);
							}
						}, 100);
					},
					onBlur: () => this.props.onBlur(),
					onSelectionChange: (data) => {
						const isFocused = this.inputRef && this.inputRef.isFocused();
						if (!isFocused || !data.selection)
						{
							return;
						}

						const { start, end } = data.selection;
						if (start === end)
						{
							this.cursorPosition = start;
						}
					},
					onSubmitEditing: () => {
						if (this.props.title)
						{
							this.props.onSubmit();
						}
						else
						{
							if (platform === 'android')
							{
								Keyboard.dismiss();
							}
							this.inputRef.blur();
						}
					},
					ref: (ref) => this.inputRef = ref,
				}),
			);
		}

		renderText()
		{
			return View(
				{
					style: {
						flex: 1,
						justifyContent: 'center',
						alignSelf: 'center',
						paddingVertical: 9,
						marginLeft: 6,
					},
					onClick: () => {
						if (this.props.checkList.checkCanUpdate())
						{
							this.props.onTextClick();
						}
					},
				},
				BBCodeText({
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						paddingRight: 4,
						textAlignVertical: 'center',
					},
					linksUnderline: false,
					value: this.getTitleWithUrls(
						this.props.title,
						this.props.checkList.getMembers(),
					),
					onLinkClick: this.onLinkClick.bind(this),
				}),
			);
		}

		focus()
		{
			this.inputRef.focus();
		}

		blur()
		{
			this.inputRef.blur();
		}
	}

	class ItemAttachments extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			props.checkListController.on('addAttachment', this.onAddAttachment.bind(this));
			props.checkListController.on('removeAttachment', this.onRemoveAttachment.bind(this));

			props.checkListController.on('attachFiles', this.onAttachFiles.bind(this));
			props.checkListController.on('removeFiles', this.onRemoveFiles.bind(this));

			props.checkListController.on('fakeAttachFiles', this.onFakeAttachFiles.bind(this));
			props.checkListController.on('fakeRemoveFiles', this.onFakeRemoveFiles.bind(this));
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'center',
						alignItems: 'center',
						minHeight: 52,
						paddingRight: 9,
						width: this.props.checkList.getAttachmentsCount() ? 40 : 0,
						opacity: this.props.checkList.getAttachmentsCount() ? 1 : 0,
					},
					onClick: () => {
						if (this.props.checkList.getAttachmentsCount())
						{
							this.showAttachmentList();
						}
					},
					ref: (ref) => this.ref = ref,
				},
				Image({
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						width: 24,
						height: 24,
					},
					tintColor: AppTheme.colors.base3,
					svg: {
						uri: `${pathToExtension}/file-clip.svg?2`,
					},
					resizeMode: 'center',
				}),
				Text({
					text: this.props.checkList.getAttachmentsCount().toString(),
					style: {
						fontSize: 12,
						fontWeight: '500',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
					},
				}),
			);
		}

		addFile()
		{
			this.props.checkListController.setMode(this.props.checkList.getId() ? 'view' : 'edit');

			this.props.checkListController.addFile(
				this.getAttachmentParams(),
				{
					nodeId: this.props.checkList.getNodeId(),
				},
			);
		}

		showAttachmentList()
		{
			this.props.checkListController.setMode(this.props.checkList.getId() ? 'view' : 'edit');

			this.props.parentWidget.openWidget('list', {
				backdrop: {
					bounceEnable: true,
					swipeAllowed: true,
					showOnTop: false,
				},
				title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_FILE_TITLE'),
				onReady: (list) => {
					this.props.checkListController.initFileList(
						list,
						this.getAttachmentParams(),
					);
				},
			});
		}

		getAttachmentParams()
		{
			const checkListItemId = this.props.checkList.getId()
				? this.props.checkList.getId()
				: this.props.checkList.getNodeId()
			;

			return {
				nodeId: this.props.checkList.getNodeId(),
				checkListItemId,
				canUpdate: this.props.checkList.checkCanUpdate(),
				attachmentsIds: Object.keys(this.props.checkList.getAttachments()),
				ajaxData: {
					mode: this.props.checkList.getId() ? 'view' : 'edit',
					checkListItemId,
					entityTypeId: 'taskId',
					entityId: this.props.checkList.getTaskId(),
					attachmentsIds: Object.keys(this.props.checkList.getAttachments()),
				},
			};
		}

		onAddAttachment(eventData)
		{
			const { nodeId, attachment } = eventData;

			if (this.props.checkList.getNodeId() !== nodeId)
			{
				return;
			}

			const key = Object.keys(attachment)[0];
			const value = Object.values(attachment)[0];

			this.props.checkList.addAttachments({ [`n${key}`]: value });

			this.setAttachmentsCount(this.props.checkList.getAttachmentsCount());
		}

		onRemoveAttachment(eventData)
		{
			const { nodeId, attachmentId } = eventData;

			if (this.props.checkList.getNodeId() !== nodeId)
			{
				return;
			}

			this.props.checkList.removeAttachment(attachmentId);
			this.props.checkList.removeAttachment(`n${attachmentId}`);

			this.setAttachmentsCount(this.props.checkList.getAttachmentsCount());
		}

		onAttachFiles(eventData)
		{
			const { nodeId, attachments, checkListItemId } = eventData;

			const inputId = (nodeId || checkListItemId);
			const currentId = (nodeId
				? this.props.checkList.getNodeId()
				: this.props.checkList.getId()
			);
			if (currentId !== inputId)
			{
				return;
			}

			this.props.checkList.setAttachments(attachments);

			this.setAttachmentsCount(this.props.checkList.getAttachmentsCount());
		}

		onRemoveFiles(eventData)
		{
			const { nodeId, filesToRemove, filesToAdd, attachments } = eventData;

			if (this.props.checkList.getNodeId() !== nodeId)
			{
				return;
			}

			this.props.checkList.setAttachments(attachments);

			this.setAttachmentsCount(this.props.checkList.getAttachmentsCount());
		}

		onFakeAttachFiles(eventData)
		{
			const { nodeId, filesToRemove, filesToAdd, checkListItemId } = eventData;

			const inputId = (nodeId || checkListItemId);
			const currentId = (nodeId
				? this.props.checkList.getNodeId()
				: this.props.checkList.getId()
			);
			if (currentId !== inputId)
			{
				return;
			}

			this.setAttachmentsCount(
				this.props.checkList.getFakeAttachmentsCount(filesToRemove, filesToAdd),
			);
		}

		onFakeRemoveFiles(eventData)
		{
			const { nodeId, filesToRemove, filesToAdd } = eventData;

			if (this.props.checkList.getNodeId() !== nodeId)
			{
				return;
			}

			this.setAttachmentsCount(
				this.props.checkList.getFakeAttachmentsCount(filesToRemove, filesToAdd),
			);
		}

		setAttachmentsCount(attachmentsCount)
		{
			if (attachmentsCount > 0)
			{
				this.show(this.ref).then(() => this.props.onAttachment());
			}
			else
			{
				this.hide(this.ref).then(() => this.props.onAttachment());
			}
		}

		show(ref)
		{
			return new Promise((resolve) => {
				ref.animate({
					duration: 50,
					width: 40,
					opacity: 1,
				}, resolve);
			});
		}

		hide(ref)
		{
			return new Promise((resolve) => {
				ref.animate({
					duration: 200,
					width: 0,
					opacity: 0,
				}, resolve);
			});
		}
	}

	class ItemSettings extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						minHeight: 52,
					},
					testId: `item-menu-${this.props.checkList.getNodeId()}`,
				},
				Image({
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						width: 24,
						height: 24,
					},
					tintColor: AppTheme.colors.base3,
					svg: {
						uri: `${pathToExtension}/three-dots.svg?2`,
					},
					resizeMode: 'center',
					onClick: () => {
						this.props.onBeforeShow();

						this.settingsMenu = new ContextMenu({
							actions: this.getActions(),
							params: {
								title: this.props.checkList.getTitle(),
								showCancelButton: false,
							},
							onClose: () => {
								this.props.onAfterClose();
							},
						});

						this.settingsMenu.show(this.props.parentWidget);
					},
				}),
			);
		}

		getActions()
		{
			return [
				{
					id: 'item-add-file',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_FILE'),
					isDisabled: !this.props.checkList.checkCanUpdate(),
					data: {
						svgIcon: svgImages.itemAddFile,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onAddFile();
							});
							resolve();
						});
					},
				},
				{
					id: 'item-add-auditor',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_AUDITOR'),
					isDisabled: !this.props.checkList.checkCanUpdate(),
					data: {
						svgIcon: svgImages.addAuditor,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onShowSelector('auditor');
							});
							resolve();
						});
					},
				},
				{
					id: 'item-add-accomplices',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ACCOMPLICE'),
					isDisabled: (
						!this.props.checkList.checkCanUpdate()
						|| !this.props.checkList.checkCanAddAccomplice()
					),
					data: {
						svgIcon: svgImages.addAccomplices,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onShowSelector('accomplice');
							});
							resolve();
						});
					},
				},
				{
					id: 'item-move-right',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_RIGHT'),
					isDisabled: (
						!this.props.checkList.checkCanUpdate()
						|| !this.props.checkList.checkCanTabIn()
					),
					data: {
						svgIcon: svgImages.itemMoveRight,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onMoveRight();
							});
							resolve();
						});
					},
				},
				{
					id: 'item-move-left',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_LEFT'),
					isDisabled: (
						!this.props.checkList.checkCanUpdate()
						|| !this.props.checkList.checkCanTabOut()
					),
					data: {
						svgIcon: svgImages.itemMoveLeft,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onMoveLeft();
							});
							resolve();
						});
					},
				},
				{
					id: 'item-important',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_IMPORTANT_MSGVER_1'),
					isDisabled: !this.props.checkList.checkCanUpdate(),
					data: {
						svgIcon: svgImages.itemImportant,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onToggleImportant();
							});
							resolve();
						});
					},
				},
				{
					id: 'item-move-to',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO'),
					isDisabled: (
						(
							this.props.checkList.checkCanUpdate()
							&& !this.props.checkList.checkCanAdd()
							&& !this.props.checkList.hasAnotherCheckLists()
						)
						|| (
							!this.props.checkList.checkCanUpdate()
						)
					),
					data: {
						svgIcon: svgImages.itemMoveTo,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onMoveTo();
							});
							resolve();
						});
					},
				},
				{
					id: 'item-remove',
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_REMOVE'),
					isDisabled: !this.props.checkList.checkCanRemove(),
					data: {
						svgIcon: svgImages.remove,
					},
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.settingsMenu.close(() => {
								this.props.onRemove();
							});
							resolve();
						});
					},
				},
			];
		}
	}

	class AddItemButton extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						height: 52,
						opacity: this.props.isDisabled ? 0.5 : 1,
						paddingHorizontal: 11,
						borderTopWidth: this.props.isEmpty ? 1 : 0,
						borderTopColor: AppTheme.colors.bgSeparatorSecondary,
					},
					testId: `list-add-item-btn-${this.props.nodeId}`,
					onClick: () => {
						if (!this.props.isDisabled)
						{
							this.props.onClick();
						}
					},
					ref: (ref) => {
						this.ref = ref;
					},
				},
				Image({
					style: {
						marginVertical: 19,
						marginLeft: 4,
						marginRight: 13,
						width: 14,
						height: 14,
					},
					tintColor: AppTheme.colors.accentBrandBlue,
					svg: {
						uri: `${pathToExtension}/add.svg?2`,
					},
				}),
				Text({
					text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ITEM_TEXT'),
					style: {
						fontSize: 16,
						fontWeight: '400',
						color: AppTheme.colors.base3,
						textAlignVertical: 'center',
					},
				}),
			);
		}
	}

	// todo maybe convert all svg to png and use imgUri
	const svgImages = {
		itemAddFile: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M22.6407 13.3782C22.789 13.5265 22.789 13.7668 22.6407 13.9151L21.646 14.9098C21.4977 15.0581 21.2574 15.0581 21.1091 14.9098L15.2512 9.05191C13.5665 7.36717 10.8096 7.36717 9.12491 9.05191C7.44017 10.7366 7.44017 13.4935 9.12491 15.1782L16.3111 22.3644C17.3677 23.421 19.0835 23.421 20.1401 22.3644C21.1966 21.3079 21.1966 19.592 20.1401 18.5355L13.7197 12.1151C13.2907 11.6861 12.617 11.6861 12.1881 12.1151C11.7591 12.544 11.7591 13.2177 12.1881 13.6467L17.2802 18.7388C17.4284 18.887 17.4284 19.1274 17.2802 19.2756L16.2854 20.2704C16.1372 20.4186 15.8969 20.4186 15.7486 20.2704L10.6565 15.1782C9.38516 13.9069 9.38516 11.8548 10.6565 10.5835C11.9278 9.31216 13.9799 9.31216 15.2512 10.5835L21.6716 17.0039C23.5705 18.9028 23.5705 21.9972 21.6716 23.896C19.7728 25.7949 16.6784 25.7949 14.7795 23.896L7.59333 16.7098C5.06622 14.1827 5.06622 10.0474 7.59333 7.52033C10.1204 4.99322 14.2557 4.99322 16.7828 7.52033L22.6407 13.3782Z" fill="#6a737f"/></svg>',
		addAuditor: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M25.1829 15.2064C24.3644 16.3761 20.1721 21.9919 15.1233 21.9919C10.0746 21.9919 5.88227 16.3761 5.06381 15.2064C4.97287 15.0764 4.97287 14.9126 5.06381 14.7826C5.88227 13.6129 10.0746 7.99707 15.1233 7.99707C20.1721 7.99707 24.3644 13.6129 25.1829 14.7826C25.2738 14.9126 25.2738 15.0764 25.1829 15.2064ZM18.6926 14.9945C18.6926 16.9658 17.0945 18.5639 15.1232 18.5639C13.1519 18.5639 11.5538 16.9658 11.5538 14.9945C11.5538 13.0231 13.1519 11.4251 15.1232 11.4251C17.0945 11.4251 18.6926 13.0231 18.6926 14.9945Z" fill="#6a737f"/></svg>',
		addAccomplices: '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.3468 18.6565L19.7433 20.6758C19.789 20.9087 19.6651 21.1423 19.4441 21.2287C17.487 21.9936 15.2494 22.449 12.8664 22.5002H11.9628C9.57732 22.4489 7.33753 21.9926 5.37896 21.2263C5.16733 21.1435 5.04309 20.9247 5.07672 20.7C5.16066 20.1392 5.2599 19.5643 5.36186 19.1667C5.63094 18.1177 7.14452 17.3386 8.53722 16.7395C8.90188 16.5826 9.12203 16.4574 9.34445 16.331C9.5617 16.2074 9.78116 16.0826 10.1396 15.9258C10.1803 15.7327 10.1966 15.5354 10.1883 15.3383L10.8052 15.265C10.8052 15.265 10.8864 15.4125 10.7561 14.5459C10.7561 14.5459 10.0629 14.3662 10.0308 12.9863C10.0308 12.9863 9.50955 13.1596 9.47812 12.3235C9.47161 12.1569 9.42857 11.9968 9.38732 11.8433C9.28839 11.4752 9.19983 11.1457 9.65082 10.8585L9.32544 9.99091C9.32544 9.99091 8.98314 6.64049 10.4833 6.91173C9.87465 5.94749 15.0074 5.146 15.3484 8.09838C15.4825 8.98838 15.4825 9.89291 15.3484 10.7829C15.3484 10.7829 16.1152 10.6948 15.6033 12.1526C15.6033 12.1526 15.3214 13.202 14.8886 12.9663C14.8886 12.9663 14.9587 14.2923 14.2772 14.5171C14.2772 14.5171 14.3259 15.2233 14.3259 15.2712L14.8955 15.3563C14.8955 15.3563 14.8783 15.9452 14.9919 16.0089C15.5115 16.3445 16.0811 16.5989 16.6803 16.7629C18.4488 17.2118 19.3468 17.9821 19.3468 18.6565Z" fill="#6a737f"/><path d="M21.3296 8.24964H23.7696V11.2494H26.894V13.7088H23.7696V16.8577H21.3296V13.7088H18.3543V11.2494H21.3296V8.24964Z" fill="#6a737f"/></svg>',
		itemMoveRight: '<svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.9913 0.99707H2.99682V2.99633H17.9913V0.99707Z" fill="#6a737f"/><path d="M10.2384 10.0421L5.0571 4.86088V8.99412H0.997559V10.9934H5.0571V15.2234L10.2384 10.0421Z" fill="#6a737f"/><path d="M17.9915 4.9956H11.9603V6.99486H17.9915V4.9956Z" fill="#6a737f"/><path d="M11.9603 8.99412H17.9915V10.9934H11.9603V8.99412Z" fill="#6a737f"/><path d="M17.9915 12.9927H11.9603V14.9919H17.9915V12.9927Z" fill="#6a737f"/></svg>',
		itemMoveLeft: '<svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.997315 0.99707H15.9918V2.99633H0.997315V0.99707Z" fill="#6a737f"/><path d="M8.55476 10.0421L13.9315 4.6654V8.99413H17.9911V10.9934H13.9315V15.4189L8.55476 10.0421Z" fill="#6a737f"/><path d="M0.99707 4.9956H7.0283V6.99486H0.99707V4.9956Z" fill="#6a737f"/><path d="M7.0283 8.99413H0.99707V10.9934H7.0283V8.99413Z" fill="#6a737f"/><path d="M0.99707 12.9927H7.0283V14.9919H0.99707V12.9927Z" fill="#6a737f"/></svg>',
		itemImportant: '<svg width="15" height="20" viewBox="0 0 15 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.16073 19.6576C10.6262 18.5539 11.4896 16.8272 11.4932 14.9926C11.4932 12.4269 9.72052 12.8328 7.99449 9.16146C5.95444 10.4256 4.65115 12.5977 4.49578 14.9926C4.60813 16.7997 5.45003 18.4835 6.82826 19.6576H6.00023C3.05145 18.5769 1.06592 15.8 0.99707 12.6602C0.99707 7.36546 6.23348 2.41245 9.16073 0.997803C8.60094 5.8855 14.9919 7.1637 14.9919 13.5349C14.9919 18.0564 9.98876 19.6576 9.98876 19.6576H9.16073Z" fill="#6a737f"/></svg>',
		itemMoveTo: '<svg width="19" height="15" viewBox="0 0 19 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M2.99682 10.9934C4.10098 10.9934 4.99609 11.8885 4.99609 12.9927C4.99609 14.0968 4.10098 14.9919 2.99682 14.9919C1.89266 14.9919 0.997559 14.0968 0.997559 12.9927C0.997559 11.8885 1.89266 10.9934 2.99682 10.9934ZM18.9909 11.9931V13.9923H6.99535V11.9931H18.9909ZM2.99682 5.99526C4.10098 5.99526 4.99609 6.89036 4.99609 7.99453C4.99609 9.09869 4.10098 9.99379 2.99682 9.99379C1.89266 9.99379 0.997559 9.09869 0.997559 7.99453C0.997559 6.89036 1.89266 5.99526 2.99682 5.99526ZM18.9909 6.99489V8.99416H6.99535V6.99489H18.9909ZM2.99734 0.99707C4.1015 0.99707 4.9966 1.89217 4.9966 2.99633C4.9966 4.1005 4.1015 4.9956 2.99734 4.9956C1.89318 4.9956 0.998076 4.1005 0.998076 2.99633C0.998076 1.89217 1.89318 0.99707 2.99734 0.99707ZM18.9909 1.99673V3.996H6.99535V1.99673H18.9909Z" fill="#6a737f"/></svg>',
		remove: '<svg width="16" height="20" viewBox="0 0 16 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.36496 0.358276H6.40955V1.8447H1.87918C1.11687 1.8447 0.498901 2.46267 0.498901 3.22498V4.8176H15.276V3.22498C15.276 2.46267 14.658 1.8447 13.8957 1.8447H9.36496V0.358276Z" fill="#6a737f"/><path d="M1.97668 6.30408H13.7983L12.6903 18.8429C12.6483 19.3179 12.2505 19.6821 11.7737 19.6821H4.0013C3.52449 19.6821 3.12666 19.3179 3.08469 18.8429L1.97668 6.30408Z" fill="#6a737f"/></svg>',
		change: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.5212 0.997742L19.8348 5.35676L7.32995 17.8162L3.01635 13.4572L15.5212 0.997742ZM1.01307 19.2731C0.972281 19.4274 1.01599 19.5905 1.1267 19.7041C1.24033 19.8177 1.4035 19.8614 1.55792 19.8177L6.37995 18.5187L2.31254 14.4525L1.01307 19.2731Z" fill="#6a737f"/></svg>',
	};

	module.exports = { CheckList };
});
