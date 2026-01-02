/**
 * @module im/messenger/lib/dev/menu/logging/list
 */
jn.define('im/messenger/lib/dev/menu/logging/list', (require, exports, module) => {
	const { Logger, LogType } = require('utils/logger');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { AllLoggerEnabler } = require('im/messenger/lib/dev/menu/logging/all-enabler');
	const { Enabler } = require('im/messenger/lib/dev/menu/logging/enabler');
	const { debounce } = require('utils/function');
	/**
	 * @class LoggerListView
	 */
	class LoggerListView extends LayoutComponent
	{
		constructor()
		{
			super();
			/** @type {LoggerManager} */
			this.loggerManager = LoggerManager.getInstance();
			/** @type {ListViewMethods} */
			this.listViewRef = null;
			this.searchValue = '';

			this.currentLoggerItemState = this.getItemsData();
			this.onAllEnablerValueChange = this.onAllEnablerValueChange.bind(this);
			this.onStartSearch = debounce(this.onStartSearch, 100, this, true);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: '#f6f6f8',
					},
					onClick: () => {
						this.inputRef?.blur({ hideKeyboard: true });
					},
				},
				this.renderSearchView(),
				this.renderAllEnabler(),
				this.renderListItem(),
			);
		}

		renderSearchView()
		{
			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						alignItems: 'flex-start',
						minHeight: 50,
						height: 50,
						maxHeight: 50,
						backgroundColor: '#e7e7e8',
						borderWidth: 1.5,
						borderColor: '#444343',
						borderRadius: 15,
						marginLeft: '5%',
						marginRight: '5%',
					},
					onClick: () => {
						this.inputRef?.focus();
					},
				},
				Text({
					style: {
						fontSize: 18,
						marginLeft: '2%',
						alignSelf: 'center',
						textAlign: 'center',
						color: '#2a2828',
					},
					text: 'SEARCH:',
				}),
				TextInput({
					ref: (ref) => {
						this.inputRef = ref;
					},
					enableKeyboardHide: true,
					autocorrectionType: 'no',
					autoCapitalize: 'none',
					placeholder: 'Enter text here',
					placeholderTextColor: '#afaeae',
					maxLength: 25,
					multiline: false,
					style: {
						flex: 1,
						flexDirection: 'row',
						alignSelf: 'center',
						alignItems: 'center',
						height: 25,
						textAlign: 'left',
						color: '#2a2828',
						fontSize: 18,
						fontWeight: '500',
						marginLeft: '1%',
					},
					onBlur: () => {
						this.inputRef?.blur({ hideKeyboard: true });
					},
					onChangeText: (text) => {
						this.onStartSearch(text);
					},
					onSubmitEditing: (event) => {
						this.inputRef?.blur({ hideKeyboard: true });
						this.onStartSearch(event.text);
					},
				}),
			);
		}

		renderAllEnabler()
		{
			this.allLoggerEnablerView = new AllLoggerEnabler(
				{ value: this.getAllLoggerValue(), onClick: this.onAllEnablerValueChange },
			);

			return this.allLoggerEnablerView;
		}

		renderListItem()
		{
			return ListView({
				isScrollBarEnabled: false,
				style: {
					flex: 1,
				},
				data: this.getSectionData(),
				renderItem: (item) => {
					return this.renderItem(item);
				},
				renderSectionHeader: (headerItem) => {
					return this.renderSectionHeader(headerItem);
				},
				ref: (ref) => {
					this.listViewRef = ref;
				},
			});
		}

		getSectionData()
		{
			const loggerCollection = this.getLoggerCollection();
			const result = [];
			const sortedSection = Object.keys(loggerCollection).sort();
			sortedSection.forEach((section, sectionIndex) => {
				const itemsSorted = loggerCollection[section].sort((a, b) => {
					return (a.title < b.title ? -1 : (a.title > b.title ? 1 : 0));
				});

				result.push({
					title: section,
					sectionIndex,
					items: [...itemsSorted].map((item, index) => ({
						type: `item_${section}`,
						key: `key_${section}_${index}`,
						text: `item_${section}_${index}`,
						sectionIndex,
						section,
						...item,
					})),
				});
			});

			return result;
		}

		getItemsData()
		{
			const sectionData = this.getSectionData();
			const items = [];
			sectionData.forEach((section) => {
				items.push(...section.items);
			});

			return items;
		}

		renderSectionHeader(headerItem)
		{
			return View(
				{
					style: {
						minHeight: 46,
						backgroundColor: '#929cbe',
						borderBottomWidth: 1,
						borderBottomColor: '#050705',
						flexDirection: 'column',
						justifyContent: 'center',
					},
				},
				Text({
					style: {
						marginLeft: '2%',
						fontSize: 20,
					},
					text: headerItem.title,
					value: headerItem.title,
				}),
			);
		}

		renderItem(itemData)
		{
			const enablerBoxView = new Enabler(
				{
					checked: itemData.value,
					onClick: () => this.onLoggerValueChange(itemData),
				},
			);

			return View(
				{
					style: {
						minHeight: 46,
						minWidth: 70,
						borderBottomWidth: 1,
						borderBottomColor: '#535557',
						backgroundColor: '#e1e4ef',
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'space-between',
					},
					onClick: () => {
						this.onLoggerValueChange(itemData);
					},
				},
				Text({
					style: {
						marginLeft: '10%',
						fontSize: 17,
						textAlign: 'center',
						alignSelf: 'center',
						color: '#0c0c0c',
					},
					text: itemData.title,
					value: itemData.id,
				}),
				enablerBoxView,
			);
		}

		getLoggerCollection()
		{
			const loggerCollection = {};
			this.loggerManager.loggerCollection.forEach((logger, name) => {
				if (!name || name === 'undefined')
				{
					return;
				}

				const loggerData = {
					id: name,
					type: 'item',
					title: name,
					value: logger.enabledLogTypes.has('log'),
				};

				const isGroupLogger = name.includes('--');
				if (isGroupLogger)
				{
					const loggerNameParts = name.split('--');
					const sectionName = loggerNameParts[0];
					loggerData.title = loggerNameParts[1];

					loggerCollection[sectionName] ??= [];
					loggerCollection[sectionName].push(loggerData);
				}
				else
				{
					loggerCollection.single ??= [];
					loggerCollection.single.push(loggerData);
				}
			});

			return loggerCollection;
		}

		getAllLoggerValue()
		{
			let result = false;
			const sections = this.getSectionData();
			for (const section of sections)
			{
				const sectionLogger = section.items;
				const isDisabledLogger = sectionLogger.find((logger) => logger.value === false);
				if (isDisabledLogger)
				{
					result = false;
					break;
				}
				else
				{
					result = true;
				}
			}

			return result;
		}

		onStartSearch(value)
		{
			if (typeof value === 'undefined')
			{
				return;
			}

			this.searchValue = value.toLowerCase();

			this.updateListBySearch()
				.catch((error) => console.log(`${this.constructor.name}.onStartSearch.updateListBySearch catch:`, error));
		}

		onLoggerValueChange(loggerData)
		{
			this.inputRef?.blur({ hideKeyboard: true });
			const newLoggerData = { ...loggerData, value: !loggerData.value };
			this.setLoggerValue(newLoggerData.value, loggerData.id);
			this.updateAllEnabler().catch((e) => console.error(`${this.constructor.name}.onLoggerValueChange.updateAllEnabler catch:`, e));
		}

		onAllEnablerValueChange(value)
		{
			this.inputRef?.blur({ hideKeyboard: true });
			this.loggerManager.loggerCollection.forEach((logger, name) => {
				this.setLoggerValue(value, name);
			});
			this.updateAllEnabler(value).catch((e) => console.error(`${this.constructor.name}.onAllEnablerValueChange.updateAllEnabler catch:`, e));
		}

		async updateAllEnabler(newValue = this.getAllLoggerValue())
		{
			const newItems = this.getItemsData();
			const currentItems = this.currentLoggerItemState;
			const differentItems = newItems.filter((item) => {
				const currentItem = currentItems.find((logger) => logger.id === item.id);

				return currentItem?.value !== item.value;
			});

			await this.listViewRef.updateRows(differentItems, 'none')
				.catch((error) => console.error(`${this.constructor.name}.listViewRef.updateRows.catch:`, error));

			this.currentLoggerItemState = this.getItemsData();
			if (this.allLoggerEnablerView.state !== newValue)
			{
				this.allLoggerEnablerView.setState({ value: newValue });
			}
		}

		async updateListBySearch()
		{
			const items = this.getItemsData();
			const removeLoggers = [];
			const addLoggers = {};
			items.forEach((item) => {
				if (!item.title.includes(this.searchValue) && !item.section.includes(this.searchValue))
				{
					return removeLoggers.push(item);
				}

				addLoggers[item.sectionIndex] ??= [];

				return addLoggers[item.sectionIndex].push(item);
			});

			if (removeLoggers.length > 0)
			{
				const deleteRowKeys = removeLoggers.map((item) => item.key);
				this.listViewRef.deleteRowsByKeys(deleteRowKeys, 'none');
			}

			if (Object.keys(addLoggers).length > 0)
			{
				await this.listViewRef.scrollToBegin(false);
				for (const sectionIndexKey of Object.keys(addLoggers))
				{
					const sectionIndexKeyInt = Number(sectionIndexKey);
					// eslint-disable-next-line no-await-in-loop
					await this.listViewRef.appendRowsToSection(
						addLoggers[sectionIndexKey],
						sectionIndexKeyInt,
						'none',
					);
				}
			}
		}

		setLoggerValue(value, name)
		{
			if (value === true)
			{
				/* eslint-disable no-console */
				console.log('Logger', name, 'enabled');
				const supportedTypes = Logger.getSupportedLogTypes();
				supportedTypes.forEach((type) => {
					this.loggerManager.getLogger(name).enable(type);
				});
			}
			else
			{
				console.log('Logger', name, 'disabled');
				const supportedTypes = Logger.getSupportedLogTypes();
				supportedTypes.forEach((type) => {
					if ([LogType.ERROR, LogType.TRACE].includes(type))
					{
						return;
					}
					this.loggerManager.getLogger(name).disable(type);
				});
			}
		}
	}

	module.exports = {
		LoggerListView,
	};
});
