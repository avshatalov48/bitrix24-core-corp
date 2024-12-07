<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

class PromptCategoryTable extends Entity\DataManager
{
	use DeleteByFilterTrait;

    public static function getTableName(): string
    {
        return 'b_ai_prompt_category';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('PROMPT_ID'))
				->configurePrimary()
				->configureRequired(),
            (new Entity\StringField('CODE'))
				->configurePrimary()
				->configureRequired(),
        ];
    }
}
