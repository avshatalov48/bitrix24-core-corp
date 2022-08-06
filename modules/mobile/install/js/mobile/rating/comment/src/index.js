import { Type } from 'main.core';

import { MobileCommentsRatingLike } from './likecomments.js';

if (Type.isUndefined(window.BXRLC))
{
	window.BXRLC = {};
}

window.RatingLikeComments = MobileCommentsRatingLike;

if (
	!Type.isUndefined(window.RatingLikeCommentsQueue)
	&& window.RatingLikeCommentsQueue.length > 0
)
{
	let f;
	while (
		(f = window.RatingLikeCommentsQueue.pop())
		&& f
	)
	{
		f();
	}
	delete window.RatingLikeCommentsQueue;
}
