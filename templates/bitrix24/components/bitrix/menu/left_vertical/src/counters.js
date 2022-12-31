export class Counters
{
	constructor(parent, params)
	{
		this.parent = parent;

		this.allCountersInMenu = {};
		this.livefeedCounter = {
			decrementStack: 0,
			value: 0
		};
		this.hiddenCounters = params.hiddenCounters || [];
		this.allCounters = params.allCounters || {};
	}

	updateCounters(counters, send)
	{
		send = send !== false;

		var valueToShow = 0;

		for (var id in counters)
		{
			if (!counters.hasOwnProperty(id))
			{
				continue;
			}

			this.allCounters[id] = counters[id];

			var counter = BX(id === "**" ? "menu-counter-live-feed" : "menu-counter-" + id.toLowerCase(), true);
			if (counter)
			{
				if (id === "**")
				{
					this.livefeedCounter.value = counters[id];

					if (counters[id] <= 0)
					{
						this.livefeedCounter.decrementStack = 0;
					}

					valueToShow = this.livefeedCounter.value - this.livefeedCounter.decrementStack;
				}
				else
				{
					valueToShow = counters[id];
				}

				this.allCountersInMenu[id] = valueToShow;

				if (valueToShow > 0)
				{
					counter.innerHTML = valueToShow > 99 ? "99+" : valueToShow;
					BX.addClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");
				}
				else
				{
					BX.removeClass(counter.parentNode.parentNode.parentNode, "menu-item-with-index");

					if (valueToShow < 0)
					{
						var warning = BX('menu-counter-warning-'+id.toLowerCase());
						if (warning)
						{
							warning.style.display = 'inline-block';
						}
					}
				}

				if (send)
				{
					BX.localStorage.set('lmc-'+id, counters[id], 5);
				}
			}
		}

		var sumHiddenCounters = 0;
		for (var i = 0, l = this.hiddenCounters.length; i < l; i++)
		{
			if (this.allCounters[this.hiddenCounters[i]])
			{
				sumHiddenCounters += (+this.allCounters[this.hiddenCounters[i]]);
			}
		}

		if (BX.type.isDomNode(BX("menu-hidden-counter")))
		{
			if (sumHiddenCounters > 0)
			{
				BX.removeClass(BX("menu-hidden-counter"), "menu-hidden-counter");
			}
			else
			{
				BX.addClass(BX("menu-hidden-counter"), "menu-hidden-counter");
			}

			BX("menu-hidden-counter").innerHTML = sumHiddenCounters > 99 ? "99+" : sumHiddenCounters;
		}

		this.updateDesktopCounter();
	}

	updateDesktopCounter()
	{
		if (typeof BXIM === "undefined")
		{
			return;
		}

		var countersSum = 0;

		for (var counterId in this.allCountersInMenu)
		{
			if (counterId !== "im-message")
			{
				countersSum += parseInt(this.allCountersInMenu[counterId]);
			}
		}

		if (countersSum < 0)
		{
			countersSum = 0;
		}
		else if (countersSum > 99)
		{
			countersSum = "99";
		}

		BXIM.desktop.setBrowserIconBadge(countersSum);
	}

	decrementCounter(node, iDecrement)
	{
		if (!node)
			return;

		iDecrement = parseInt(iDecrement);

		if (node.id == 'menu-counter-live-feed')
		{
			this.livefeedCounter.decrementStack += iDecrement;
			var counterValue = this.livefeedCounter.value - this.livefeedCounter.decrementStack;

			if (counterValue > 0)
			{
				node.innerHTML = counterValue;
			}
			else
			{
				BX.removeClass(node.parentNode.parentNode.parentNode, "menu-item-with-index");
			}
		}
	}

	recountHiddenCounters()
	{
		var curSumCounters = 0;
		var hiddenItems = BX.findChildren(BX("left-menu-hidden-items-list"), {className: "menu-item-block"}, true);
		this.hiddenCounters = [];

		if (hiddenItems)
		{
			for (var i = 0, l = hiddenItems.length; i < l; i++)
			{
				var curCounter = hiddenItems[i].getAttribute("data-counter-id");
				this.hiddenCounters.push(curCounter);

				if (this.allCounters[curCounter])
				{
					curSumCounters += Number(this.allCounters[curCounter]);
				}
			}
		}

		if (curSumCounters > 0)
		{
			BX.removeClass(BX("menu-hidden-counter"), "menu-hidden-counter");
		}
		else
		{
			BX.addClass(BX("menu-hidden-counter"), "menu-hidden-counter");
		}

		BX("menu-hidden-counter").innerHTML = curSumCounters > 99 ? "99+" : curSumCounters;
	}
}