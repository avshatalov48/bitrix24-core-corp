(function(window) {
	if (window.JCCatalogTopSliderList)
	{
		return;
	}

	window.JCCatalogTopSliderList = function(arParams) {
		this.params = null;
		this.currentIndex = 0;
		this.size = 0;
		this.rotate = false;
		this.rotateTimer = 3000;
		this.rotatePause = false;
		this.showPages = false;
		this.errorCode = 0;

		this.slider = {
			cont: null,
			rows: null,
			left: null,
			right: null,
			pagination: null,
			pages: null
		};

		if (!arParams || 'object' !== typeof arParams)
		{
			this.errorCode = -1;
		}

		if (this.errorCode === 0)
		{
			this.params = arParams;
		}

		if (this.params.rotate)
		{
			this.rotate = this.params.rotate;
		}

		if (this.params.rotateTimer)
		{
			this.params.rotateTimer = parseInt(this.params.rotateTimer, 10);

			if (!isNaN(this.params.rotateTimer) && this.params.rotateTimer >= 0)
			{
				this.rotateTimer = this.params.rotateTimer;
			}
		}

		if (this.errorCode === 0)
		{
			BX.ready(BX.proxy(this.init, this));
		}
	};

	window.JCCatalogTopSliderList.prototype.init = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		var i = 0;
		if (this.params.cont)
		{
			this.slider.cont = BX(this.params.cont);
		}

		if (this.params.rows && BX.type.isArray(this.params.rows))
		{
			this.slider.rows = [];

			for (i = 0; i < this.params.rows.length; i++)
			{
				this.slider.rows[this.slider.rows.length] = BX(this.params.rows[i]);

				if (!this.slider.cont)
				{
					this.slider.cont = this.slider.rows[this.slider.rows.length - 1].parent;
				}
			}

			this.size = this.slider.rows.length;
		}

		if (this.params.left)
		{
			if (BX.type.isDomNode(this.params.left))
			{
				this.slider.left = this.params.left;
			}
			else if ('object' === typeof(this.params.left))
			{
				this.slider.left = this.slider.cont.appendChild(BX.create(
					'DIV',
					{
						props: {
							id: this.params.left.id,
							className: this.params.left.className
						}
					}
				));
			}
			else if (BX.type.isNotEmptyString(this.params.left))
			{
				this.slider.left = BX(this.params.left);
			}
		}

		if (this.params.right)
		{
			if (BX.type.isDomNode(this.params.right))
			{
				this.slider.right = this.params.right;
			}
			else if ('object' === typeof(this.params.right))
			{
				this.slider.right = this.slider.cont.appendChild(BX.create(
					'DIV',
					{
						props: {
							id: this.params.right.id,
							className: this.params.right.className
						}
					}
				));
			}
			else if (BX.type.isNotEmptyString(this.params.right))
			{
				this.slider.right = BX(this.params.right);
			}
		}

		if (this.params.pagination)
		{
			if (BX.type.isDomNode(this.params.pagination))
			{
				this.slider.pagination = this.params.pagination;
			}
			else if ('object' === typeof(this.params.pagination))
			{
				this.slider.pagination = this.slider.cont.appendChild(BX.create(
					'UL',
					{
						props: {
							id: this.params.pagination.id,
							className: this.params.pagination.className
						}
					}
				));
			}
			else if (BX.type.isNotEmptyString(this.params.pagination))
			{
				this.slider.pagination = BX(this.params.pagination);
			}
		}

		if (this.slider.pagination)
		{
			this.showPages = true;
			this.slider.pages = [];

			for (i = 0; i < this.slider.rows.length; i++)
			{
				this.slider.pages[this.slider.pages.length] = this.slider.pagination.appendChild(
					BX.create(
						'LI',
						{
							props: {
								className: (i === 0 ? 'active' : 'not-active')
							},
							attrs: {
								'data-pagevalue': i.toString()
							},
							events: {
								'click': BX.proxy(this.rowMove, this)
							},
							html: '<span></span>'
						}
					)
				);
			}
		}

		if (this.errorCode === 0)
		{
			if (this.rotate && this.slider.cont && this.rotateTimer > 0)
			{
				BX.bind(this.slider.cont, 'mouseover', BX.proxy(this.rotateStop, this));
				BX.bind(this.slider.cont, 'mouseout', BX.proxy(this.rotateStart, this));
				setTimeout(BX.proxy(this.rowRotate, this), this.rotateTimer);
			}

			if (this.slider.left)
			{
				BX.bind(this.slider.left, 'click', BX.proxy(this.rowLeft, this));
			}

			if (this.slider.right)
			{
				BX.bind(this.slider.right, 'click', BX.proxy(this.rowRight, this));
			}
		}
	};

	window.JCCatalogTopSliderList.prototype.rowLeft = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		if (this.showPages)
		{
			BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'not-active'}});
		}

		BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide not-active'}});
		this.currentIndex = (0 === this.currentIndex ? this.size : this.currentIndex) - 1;

		if (this.showPages)
		{
			BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'active'}});
		}

		BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide active'}});
	};

	window.JCCatalogTopSliderList.prototype.rowRight = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		if (this.showPages)
		{
			BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'not-active'}});
		}

		BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide not-active'}});
		this.currentIndex++;

		if (this.currentIndex === this.size)
		{
			this.currentIndex = 0;
		}

		if (this.showPages)
		{
			BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'active'}});
		}

		BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide active'}});
	};

	window.JCCatalogTopSliderList.prototype.rowMove = function() {
		if (this.errorCode < 0)
		{
			return;
		}
		var pageValue = 0,
			target = BX.proxy_context;

		if (target && target.hasAttribute('data-pagevalue'))
		{
			pageValue = parseInt(target.getAttribute('data-pagevalue'), 10);
			
			if (!isNaN(pageValue) && pageValue < this.size)
			{
				if (this.showPages)
				{
					BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'not-active'}});
				}

				BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide not-active'}});
				this.currentIndex = pageValue;

				if (this.showPages)
				{
					BX.adjust(this.slider.pages[this.currentIndex], {props: {className: 'active'}});
				}

				BX.adjust(this.slider.rows[this.currentIndex], {props: {className: 'row catalog-top-slide active'}});
			}
		}
	};

	window.JCCatalogTopSliderList.prototype.rowRotate = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		if (!this.rotatePause)
		{
			this.rowRight();
		}

		setTimeout(BX.proxy(this.rowRotate, this), this.rotateTimer);
	};

	window.JCCatalogTopSliderList.prototype.rotateStart = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		this.rotatePause = false;
	};

	window.JCCatalogTopSliderList.prototype.rotateStop = function() {
		if (this.errorCode < 0)
		{
			return;
		}

		this.rotatePause = true;
	};
})(window);