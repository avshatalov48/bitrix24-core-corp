import {Instance} from './feed';

class PageScroll
{
	constructor()
	{
		this.canCheckScrollButton = true;
		this.showScrollButtonTimeout = null;
		this.showScrollButtonBottom = false;
		this.showScrollButtonTop = false;

		this.class = {
			scrollButton: 'post-comment-block-scroll',
			scrollButtonTop: 'post-comment-block-scroll-top',
			scrollButtonBottom: 'post-comment-block-scroll-bottom',
			scrollButtonTopActive: 'post-comment-block-scroll-top-active',
			scrollButtonBottomActive: 'post-comment-block-scroll-bottom-active',
		};

		this.init();
	}

	init()
	{
		if (window.platform === 'ios')
		{
			return;
		}

		document.addEventListener('scroll', this.onScrollDetail.bind(this));
	}

	onScrollDetail()
	{
		if (!this.canCheckScrollButton)
		{
			return;
		}

		clearTimeout(this.showScrollButtonTimeout);
		this.showScrollButtonTimeout = setTimeout(() => {
			Instance.setLastActivityDate();
			this.checkScrollButton();
		}, 200);
	}

	checkScrollButton()
	{
		const scrollTop = window.scrollY; // document.body.scrollTop
		const maxScroll = (document.documentElement.scrollHeight - window.innerHeight - 100); // (this.keyboardShown ? 500 : 300)

		this.showScrollButtonBottom = !(
			((document.documentElement.scrollHeight - window.innerHeight) <= 0) // short page
			|| (
				scrollTop >= maxScroll // too much low
				&& (
					scrollTop > 0 // refresh patch
					|| maxScroll > 0
				)
			)
		);

		this.showScrollButtonTop = (scrollTop > 200);
		this.showHideScrollButton();
	}

	showHideScrollButton()
	{
		const postScrollButtonBottom = document.querySelector(`.${this.class.scrollButtonBottom}`);
		const postScrollButtonTop = document.querySelector(`.${this.class.scrollButtonTop}`);

		if (postScrollButtonBottom)
		{
			if (this.showScrollButtonBottom)
			{
				if (!postScrollButtonBottom.classList.contains(`${this.class.scrollButtonBottomActive}`))
				{
					postScrollButtonBottom.classList.add(`${this.class.scrollButtonBottomActive}`);
				}
			}
			else
			{
				if (postScrollButtonBottom.classList.contains(`${this.class.scrollButtonBottomActive}`))
				{
					postScrollButtonBottom.classList.remove(`${this.class.scrollButtonBottomActive}`);
				}
			}
		}

		if (postScrollButtonTop)
		{
			if (this.showScrollButtonTop)
			{
				if (!postScrollButtonTop.classList.contains(`${this.class.scrollButtonTopActive}`))
				{
					postScrollButtonTop.classList.add(`${this.class.scrollButtonTopActive}`);
				}
			}
			else
			{
				if (postScrollButtonTop.classList.contains(`${this.class.scrollButtonTopActive}`))
				{
					postScrollButtonTop.classList.remove(`${this.class.scrollButtonTopActive}`);
				}
			}
		}
	}

	scrollTo(type)
	{
		if (type !== 'top')
		{
			type = 'bottom';
		}

		this.canCheckScrollButton = false;
		this.showScrollButtonBottom = false;
		this.showScrollButtonTop = false;

		this.showHideScrollButton();

		const startValue = window.scrollY; // document.body.scrollTop
		const finishValue = (type == 'bottom' ? document.documentElement.scrollHeight : 0);

		BitrixAnimation.animate({
			duration : 500,
			start : { scroll : startValue },
			finish : { scroll : finishValue },
			transition : BitrixAnimation.makeEaseOut(BitrixAnimation.transitions.quart),
			step : function(state)
			{
				window.scrollTo(0, state.scroll);
			},
			complete : () => {
				this.canCheckScrollButton = true;
				this.checkScrollButton();
			}
		});
	}
}

export {
	PageScroll,
}