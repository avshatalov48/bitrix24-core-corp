<?php
namespace Bitrix\Tasks\UI\Controls\Fields;

class Deadline
{
	public static function getCountTimesItems()
	{
		return [
			['TITLE'=>'Many times', 'VALUE'=>'*'],
			['TITLE'=>'1 time', 'VALUE'=>'1'],
			['TITLE'=>'2 times', 'VALUE'=>'2'],
			['TITLE'=>'3 times', 'VALUE'=>'3']
		];
	}
	public static function getTimesItems()
	{
		return [
			['TITLE'=>'Anytime', 'VALUE'=>'*'],
			['TITLE'=>'7 days', 'VALUE'=>'7 days'],
			['TITLE'=>'2 week', 'VALUE'=>'2 week'],
			['TITLE'=>'1 month', 'VALUE'=>'1 month']
		];
	}
}