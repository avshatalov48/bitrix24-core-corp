<?
namespace Bitrix\Tasks\Copy;

use Bitrix\Main\Copy\EntityCopier;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Copy\Implement\Template as TemplateImplementer;

Loc::loadMessages(__FILE__);

class Template extends EntityCopier
{
	public function __construct(TemplateImplementer $implementer)
	{
		$implementer->setTemplateCopier($this);

		parent::__construct($implementer);
	}
}