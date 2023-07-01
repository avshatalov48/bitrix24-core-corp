(() => {

	/**
	 * @class StoreProductCard
	 */
	class StoreProductCard extends LayoutComponent
	{
		render()
		{
			return View(
				{
					style: Styles.container.outer,
				},
				View(
					{
						style: {
							...Styles.container.inner,
							backgroundColor: this.props.justAdded ? '#FFFCEE' : '#ffffff',
						},
						onClick: () => {
							if (this.props.onClick)
							{
								return this.props.onClick(this.props.id);
							}
						},
						onLongClick: () => {
							if (this.props.onLongClick)
							{
								return this.props.onLongClick(this.props.id);
							}
						}
					},
					this.renderIndex(),
					this.renderImageStack(),
					View(
						{
							style: Styles.content
						},
						this.renderCardHeading(),
						this.renderProperties(),
						View(
							{},
							...this.renderStoreAmount(),
							this.renderLineSeparator(),
							...this.renderPurchasePrice(),
							this.renderSellPrice(),
						)
					),
				)
			);
		}

		renderIndex()
		{
			const displayIndex = this.props.index + 1;
			let fontSize = 10;
			if (displayIndex < 100)
			{
				fontSize = 12;
			}

			return View(
				{
					style: Styles.index.wrapper
				},
				Text({
					style: { ...Styles.index.text, fontSize },
					text: String(displayIndex)
				})
			)
		}

		// @todo Extract as separate UI component
		renderImageStack()
		{
			let absPath = null;

			if (this.props.gallery && this.props.gallery.length && this.props.gallery[0])
			{
				const picture = (
					BX.type.isNumber(Number(this.props.gallery[0]))
						? this.props.galleryInfo[this.props.gallery[0]]
						: this.props.gallery[0]
				);
				if (picture && picture.previewUrl)
				{
					absPath = picture.previewUrl.startsWith('/')
						? currentDomain + picture.previewUrl
						: picture.previewUrl;
				}
			}

			const topImage = () => {
				if (absPath)
				{
					return Image({
						style: Styles.image.single,
						resizeMode: 'cover',
						uri: encodeURI(absPath),
					});
				}
				else
				{
					return Image({
						style: Styles.image.noPhoto,
						svg: SvgIcons.noPhoto
					});
				}
			};

			const imagesStack = () => {
				if (this.props.gallery.length > 1)
				{
					return View(
						{
							style: Styles.image.container
						},
						Image({
							style: Styles.image.multiple,
							svg: this.props.gallery.length === 2 ? SvgIcons.doubleImage : SvgIcons.multipleImages
						}),
						topImage()
					);
				}
				else
				{
					return View({style: Styles.image.container}, topImage());
				}
			};

			return View(
				{},
				imagesStack()
			);
		}

		renderCardHeading()
		{
			return View(
				{
					style: Styles.heading
				},

				Text({
					text: this.props.name,
					style: {
						fontSize: 18,
					}
				}),

				View(
					{
						style: Styles.contextMenu.container,
						onClick: () => {
							if (this.props.onContextMenuClick)
							{
								return this.props.onContextMenuClick(this.props.id);
							}
						}
					},
					Image({
						style: Styles.contextMenu.icon,
						svg: SvgIcons.contextMenu
					})
				)
			);
		}

		renderProperties()
		{
			const properties = this.props.properties;

			if (!properties || !properties.length)
			{
				return null;
			}

			return View(
				{
					style: Styles.properties.wrapper
				},
				View(
					{
						style: Styles.properties.left,
					},
					...properties.map(property => View(
						{
							onClick: () => {
								this.showHint(property.name);
							}
						},
						Text({
							text: `${property.name}`,
							ellipsize: 'end',
							numberOfLines: 1,
							style: {
								color: '#bdc1c6',
								fontSize: 14,
							}
						})
					))
				),
				View(
					{
						style: Styles.properties.right,
					},
					...properties.map(property => Text({
						text: `${property.value}`,
						style: {
							color: '#828B95',
							fontSize: 14,
						}
					}))
				),
			);
		}

		renderStoreAmount()
		{
			if (
				!(
					this.props.permissions['catalog_store_all']
					|| this.props.permissions['catalog_store'].includes(this.props.storeToId)
				)

			)
			{
				return [];
			}

			let amount = Number(this.props.amount);
			if (isNaN(amount))
			{
				amount = 0;
			}

			const measure = CommonUtils.objectDeepGet(this.props, 'measure.name', '');

			return [
				View(
					{
						style: Styles.amount.wrapper
					},
					this.renderStoreName(),
					View(
						{
							style: {
								width: '50%',
								flexDirection: 'row',
								justifyContent: 'flex-end',
							}
						},
						Text({
							text: `${amount} `,
							style: Styles.amount.value,
							numberOfLines: 1,
						}),
						Text({
							text: `${measure}`,
							style: {...Styles.amount.value, color: '#828B95'},
							numberOfLines: 1,
						})
					)
				)
			];
		}

		renderStoreName()
		{
			const documentType = this.props.document ? this.props.document.type : '';
			const storeFrom = this.props.storeFrom;
			const storeTo = this.props.storeTo;
			let storeTitle = '';
			if (documentType === 'A' || documentType === 'S')
			{
				storeTitle = storeTo ? storeTo.title : '';
			}
			else if (documentType === 'D')
			{
				storeTitle = storeFrom ? storeFrom.title : '';
			}
			else if (documentType === 'M' && this.props.storeFrom && this.props.storeTo)
			{
				const storeFromTitle =
					this.props.storeFrom.title
						? this.props.storeFrom.title
						: BX.message('CSPL_STORE_EMPTY')
				;
				const storeToTitle =
					this.props.storeTo.title
						? this.props.storeTo.title
						: BX.message('CSPL_STORE_EMPTY')
				;

				return View(
					{
						style: Styles.summaryRow.storesWrapper,
					},
					View(
						{
							onClick: () => {
								this.showHint(storeFromTitle);
							}
						},
						Text({
							text: storeFromTitle,
							style: Styles.summaryRow.title,
							ellipsize: 'end',
							numberOfLines: 1,
						})
					),
					View(
						{
							onClick: () => {
								this.showHint(storeToTitle);
							}
						},
						Text({
							text: storeToTitle,
							style: Styles.summaryRow.title,
							ellipsize: 'end',
							numberOfLines: 1,
						})
					),
				);
			}

			if (storeTitle)
			{
				return View(
					{
						style: Styles.summaryRow.leftWrapper,
						onClick: () => {
							this.showHint(storeTitle);
						}
					},
					Text({
						text: storeTitle,
						style: Styles.summaryRow.title,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
				);
			}

			return View(
				{
					style: Styles.summaryRow.leftWrapper,
				},
				Text({
					text: BX.message('CSPL_STORE_EMPTY'),
					style: Styles.summaryRow.title,
				}),
			);
		}

		renderLineSeparator()
		{
			return View({
				style: {
					height: 1,
					width: '100%',
					backgroundColor: '#F0F2F5',
					marginTop: 4,
					marginBottom: 6
				}
			});
		}

		renderPurchasePrice()
		{
			if (!this.props.permissions['catalog_purchas_info'])
			{
				return [];
			}

			const title = () => View(
				{
					style: Styles.summaryRow.leftWrapper
				},
				Text({
					text: BX.message('CSPL_PURCHASE_PRICE'),
					style: Styles.summaryRow.title
				}),
			);

			const value = () => {
				let node;
				let {amount, currency} = this.props.price.purchase;

				amount = parseFloat(amount);

				if (isFinite(amount))
				{
					node = MoneyView({
						money: Money.create({amount, currency}),
						renderAmount: (formattedAmount) => Text({
							text: formattedAmount,
							style: Styles.summaryRow.purchasePriceExists
						}),
						renderCurrency: (formattedCurrency) => Text({
							text: formattedCurrency,
							style: {
								...Styles.summaryRow.purchasePriceExists,
								color: '#828B95'
							}
						}),
					});
				}
				else
				{
					node = Text({
						text: `${BX.message('CSPL_PRICE_EMPTY')}`,
						style: Styles.summaryRow.purchasePriceEmpty
					})
				}

				return View(
					{
						style: Styles.summaryRow.rightWrapper
					},
					node,
				);
			};

			return [
				View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 4,
						}
					},
					title(),
					value(),
				)
			];
		}

		renderSellPrice()
		{
			const title = () => View(
				{
					style: Styles.summaryRow.leftWrapper
				},
				Text({
					text: BX.message('CSPL_SELLING_PRICE'),
					style: {
						fontSize: 14,
						color: '#bdc1c6',
						textAlign: 'right',
					}
				}),
			);

			const value = () => {
				let node;
				let {amount, currency} = this.props.price.sell;

				amount = parseFloat(amount);

				if (isFinite(amount))
				{
					node = MoneyView({
						money: Money.create({amount, currency}),
						renderAmount: (formattedAmount) => Text({
							text: formattedAmount,
							style: Styles.summaryRow.sellPrice
						}),
						renderCurrency: (formattedCurrency) => Text({
							text: formattedCurrency,
							style: {
								...Styles.summaryRow.sellPrice,
								color: '#bdc1c6'
							}
						}),
					});
				}
				else
				{
					node = Text({
						text: `${BX.message('CSPL_PRICE_EMPTY')}`,
						style: Styles.summaryRow.sellPrice
					})
				}

				return View(
					{
						style: Styles.summaryRow.rightWrapper
					},
					node,
				);
			};

			return View(
				{
					style: {
						flexDirection: 'row',
					}
				},
				title(),
				value(),
			);
		}

		showHint(message)
		{
			const params = {
				title: message,
				showCloseButton: true,
				id: 'catalog-store-product-card-hint',
				backgroundColor: '#000000',
				textColor: '#ffffff',
				hideOnTap: true,
				autoHide: true,
			};

			const callback = () => {};

			dialogs.showSnackbar(params, callback);
		}
	}

	const Styles = {
		container: {
			outer: {
				backgroundColor: '#F0F2F5',
			},
			inner: {
				backgroundColor: '#ffffff',
				borderRadius: 12,
				padding: 16,
				marginBottom: 12,
				flexDirection: 'row',
			},
		},

		index: {
			wrapper: {
				position: 'absolute',
				width: 24,
				height: 16,
				left: 0,
				top: 0,
				backgroundColor: '#bdc1c6',
				borderTopLeftRadius: 12,
				borderBottomRightRadius: 12,
				alignItems: 'center',
				flexDirection: 'column',
				justifyContent: 'center',
			},
			text: {
				color: '#ffffff',
				fontSize: 10,
			}
		},

		image: {
			container: {
				width: 62,
				height: 62,
				marginRight: 11,
				justifyContent: 'center',
				alignItems: 'center'
			},
			single: {
				width: 51,
				height: 51,
				borderRadius: 4,
				// position: 'absolute',
				backgroundColor: '#dadada',
			},
			noPhoto: {
				width: 51,
				height: 51,
				borderRadius: 4
				// position: 'absolute',
			},
			multiple: {
				width: 62,
				height: 62,
				position: 'absolute',
				top: 0,
				left: 0,
			}
		},

		content: {
			flexGrow: 1,
			flexShrink: 1,
			width: 0
		},

		heading: {
			paddingRight: 40,
			marginBottom: 16,
		},

		contextMenu: {
			container: {
				position: 'absolute',
				right: 0,
				top: -8,
				width: 40,
				height: 40,
				alignItems: 'center',
				justifyContent: 'center',
			},
			icon: {
				width: 16,
				height: 4,
			}
		},

		properties: {
			wrapper: {
				borderRadius: 6,
				borderWidth: 1,
				borderColor: '#d5d7db',
				paddingTop: 6,
				paddingBottom: 6,
				paddingLeft: 10,
				paddingRight: 10,
				marginBottom: 16,
				flexDirection: 'row',
			},
			left: {
				flexGrow: 1,
				paddingRight: 4,
				maxWidth: '50%',
			},
			right: {
				flexGrow: 2,
			}
		},

		amount: {
			wrapper: {
				flexDirection: 'row',
			},
			value: {
				fontSize: 18,
				fontWeight: 'bold',
				textAlign: 'right',
				color: '#333333'
			}
		},

		summaryRow: {
			leftWrapper: {
				width: '50%',
				flexDirection: 'row',
				justifyContent: 'flex-end',
				paddingRight: 4,
				alignItems: 'center',
			},
			rightWrapper: {
				width: '50%',
				flexDirection: 'row',
				justifyContent: 'flex-end',
			},
			storesWrapper: {
				width: '50%',
				flexDirection: 'column',
				justifyContent: 'flex-end',
				paddingRight: 4,
			},
			title: {
				fontSize: 16,
				color: '#828B95',
				textAlign: 'right',
			},
			purchasePriceExists: {
				fontSize: 18,
				color: '#333333',
				fontWeight: 'bold',
			},
			purchasePriceEmpty: {
				fontSize: 16,
				fontWeight: 'bold',
				color: '#A8ADB4'
			},
			sellPrice: {
				fontSize: 14,
				color: '#A8ADB4',
				fontWeight: 'bold',
			},
		},
	};

	const SvgIcons = {
		contextMenu: {
			content: '<svg width="16" height="4" viewBox="0 0 16 4" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4C3.10457 4 4 3.10457 4 2C4 0.89543 3.10457 0 2 0C0.89543 0 0 0.89543 0 2C0 3.10457 0.89543 4 2 4Z" fill="#A8ADB4"/><path d="M8 4C9.10457 4 10 3.10457 10 2C10 0.89543 9.10457 0 8 0C6.89543 0 6 0.89543 6 2C6 3.10457 6.89543 4 8 4Z" fill="#A8ADB4"/><path d="M16 2C16 3.10457 15.1046 4 14 4C12.8954 4 12 3.10457 12 2C12 0.89543 12.8954 0 14 0C15.1046 0 16 0.89543 16 2Z" fill="#A8ADB4"/></svg>'
		},
		doubleImage: {
			content: `<svg width="67" height="62" viewBox="0 0 67 62" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.52591 6.98939C8.68672 4.78611 10.6032 3.13036 12.8065 3.29117L57.1265 6.52593C59.3298 6.68674 60.9855 8.60321 60.8247 10.8065L57.59 55.1265C57.4291 57.3298 55.5127 58.9855 53.3094 58.8247L8.98937 55.59C6.78609 55.4292 5.13034 53.5127 5.29115 51.3094L8.52591 6.98939Z" fill="white"/><path d="M12.7701 3.78984L57.0901 7.0246C59.018 7.16531 60.4667 8.84222 60.326 10.7701L57.0913 55.0901C56.9506 57.018 55.2737 58.4668 53.3458 58.3261L9.02577 55.0913C7.0979 54.9506 5.64911 53.2737 5.78982 51.3458L9.02458 7.02579C9.16529 5.09792 10.8422 3.64914 12.7701 3.78984Z" stroke="black" stroke-opacity="0.14"/></svg>`
		},
		multipleImages: {
			content: `<svg width="62" height="63" viewBox="0 0 62 63" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="10.5098" y="0.45459" width="52.4379" height="52.4379" rx="4" transform="rotate(11.519 10.5098 0.45459)" fill="white"/><rect x="10.8998" y="1.04437" width="51.4379" height="51.4379" rx="3.5" transform="rotate(11.519 10.8998 1.04437)" stroke="black" stroke-opacity="0.14"/><rect x="7.08008" y="3.39941" width="52.4379" height="52.4379" rx="4" transform="rotate(4.17441 7.08008 3.39941)" fill="white"/><rect x="7.54236" y="3.93448" width="51.4379" height="51.4379" rx="3.5" transform="rotate(4.17441 7.54236 3.93448)" stroke="black" stroke-opacity="0.14"/></svg>`
		},
		noPhoto: {
			content: `<svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg"><path opacity="0.761" fill-rule="evenodd" clip-rule="evenodd" d="M9.41333 0H70.5867C75.7867 0 80 4.21333 80 9.41333V70.5867C80 75.7867 75.7867 80 70.5867 80H9.41333C4.21333 80 0 75.7867 0 70.5867V9.41333C0 4.21333 4.21333 0 9.41333 0ZM9.41333 70.5867H70.5867V65.8827L54.2773 47.056L46.1173 56.4693L25.7227 32.9387L9.41333 51.7653V70.592V70.5867ZM58.8213 28.24C60.6941 28.24 62.4902 27.496 63.8144 26.1718C65.1387 24.8475 65.8827 23.0514 65.8827 21.1787C65.8827 19.3059 65.1387 17.5098 63.8144 16.1855C62.4902 14.8613 60.6941 14.1173 58.8213 14.1173C56.9829 14.1679 55.2366 14.9337 53.9541 16.252C52.6716 17.5702 51.954 19.3368 51.954 21.176C51.954 23.0152 52.6716 24.7818 53.9541 26.1C55.2366 27.4183 56.9829 28.1841 58.8213 28.2347V28.24Z" fill="#A8ADB4"/></svg>`
		},
	};

	jnexport(StoreProductCard);

})();