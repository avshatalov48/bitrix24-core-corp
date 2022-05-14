/** @memberof BX.Crm.Timeline.Tools */
export default class AudioPlaybackRateSelector
{
	constructor(params)
	{
		this.name = params.name || 'crm-timeline-audio-playback-rate-selector';
		this.menuId = this.name + '-menu';
		if(BX.Type.isArray(params.availableRates))
		{
			this.availableRates = params.availableRates;
		}
		else
		{
			this.availableRates = [1, 1.5, 2, 3];
		}
		this.currentRate = this.normalizeRate(params.currentRate);
		this.textMessageCode = params.textMessageCode;
		this.renderedItems = [];
		this.players = [];
	}

	isRateCurrent(rateDescription, rate)
	{
		return ((rateDescription.rate && rate === rateDescription.rate) || rate === rateDescription);
	}

	normalizeRate(rate)
	{
		rate = parseFloat(rate);
		let i = 0;
		const length = this.availableRates.length;
		for(; i < length; i++)
		{
			if(this.isRateCurrent(this.availableRates[i], rate))
			{
				return rate;
			}
		}

		return (this.availableRates[0].rate || this.availableRates[0]);
	}

	getMenuItems()
	{
		const selectedRate = this.getRate();

		return this.availableRates.map(function(item) {
			return {
				text: (item.text || item) + '',
				html: (item.html || item) + '',
				className: (this.isRateCurrent(item, selectedRate)) ? 'menu-popup-item-text-active' : null,
				onclick: function() {
					this.setRate(item.rate || item)
				}.bind(this)
			}
		}.bind(this))
	}

	getPopup(node)
	{
		let popupMenu = BX.Main.MenuManager.getMenuById(this.menuId);
		if(popupMenu)
		{
			const popupWindow = popupMenu.getPopupWindow();
			if (popupWindow)
			{
				popupWindow.setBindElement(node);
			}
		}
		else
		{
			popupMenu = BX.Main.MenuManager.create({
				id: this.menuId,
				bindElement: node,
				items: this.getMenuItems(),
				className: 'crm-audio-cap-speed-popup'
			});
		}

		return popupMenu;
	}

	getRate()
	{
		return this.normalizeRate(this.currentRate);
	}

	setRate(rate)
	{
		this.getPopup().destroy();

		rate = this.normalizeRate(rate);
		if(this.currentRate === rate)
		{
			return;
		}
		this.currentRate = rate;
		BX.userOptions.save("crm", this.name, 'rate', rate);

		for(let i = 0, length = this.renderedItems.length; i < length; i++)
		{
			const textNode = this.renderedItems[i].querySelector('.crm-audio-cap-speed-text');
			if(textNode)
			{
				textNode.innerHTML = this.getText();
			}
		}

		for(let i = 0, length = this.players.length; i < length; i++)
		{
			this.players[i].vjsPlayer.playbackRate(this.getRate());
		}
	}

	getText()
	{
		let text;

		if(this.textMessageCode)
		{
			text = BX.Loc.getMessage(this.textMessageCode);
		}
		if(!text)
		{
			text = '#RATE#';
		}

		return text.replace('#RATE#', '<span>' + this.getRate() + 'x</span>');
	}

	render()
	{
		const item = BX.Dom.create('div', {
			attrs: {
				className: 'crm-audio-cap-speed-wrapper'
			},
			children: [
				BX.Dom.create('div', {
					attrs: {
						className: 'crm-audio-cap-speed',
					},
					children: [
						BX.Dom.create('div', {
							attrs: {
								className: 'crm-audio-cap-speed-text'
							},
							html: this.getText()
						})
					],
				})
			],
			events: {
				click: function (event) {
					event.preventDefault();
					this.getPopup(event.target).show()
				}.bind(this)
			}
		});
		this.renderedItems.push(item);

		return item;
	}

	addPlayer(player)
	{
		if(BX.Fileman.Player && player instanceof BX.Fileman.Player)
		{
			this.players.push(player);
		}
	}
}
