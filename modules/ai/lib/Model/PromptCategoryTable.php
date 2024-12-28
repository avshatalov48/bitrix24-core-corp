<?php declare(strict_types=1);

namespace Bitrix\AI\Model;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\Internal\DeleteByFilterTrait;

/**
 * Class PromptCategoryTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_PromptCategory_Query query()
 * @method static EO_PromptCategory_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_PromptCategory_Result getById($id)
 * @method static EO_PromptCategory_Result getList(array $parameters = [])
 * @method static EO_PromptCategory_Entity getEntity()
 * @method static \Bitrix\AI\Model\EO_PromptCategory createObject($setDefaultValues = true)
 * @method static \Bitrix\AI\Model\EO_PromptCategory_Collection createCollection()
 * @method static \Bitrix\AI\Model\EO_PromptCategory wakeUpObject($row)
 * @method static \Bitrix\AI\Model\EO_PromptCategory_Collection wakeUpCollection($rows)
 */
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
