<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider;

use Bitrix\Crm\Item\Company;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\AbstractAvatar;
use Bitrix\Crm\ItemMiniCard\Layout\Avatar\IconAvatar;
use Bitrix\Crm\ItemMiniCard\Provider\AbstractEntityProvider;
use Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\Trait\HasFileImageAvatar;
use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Multifield\Type\Web;

final class CompanyProvider extends AbstractEntityProvider
{
	use HasFileImageAvatar;

	public function provideAvatar(): AbstractAvatar
	{
		return $this->getImageAvatar()
			?? new IconAvatar('o-company');
	}

	public function provideFields(): array
	{
		return [
			$this->fieldFactory->get(Company::FIELD_NAME_TYPE_ID),
			$this->fieldFactory->get(Company::FIELD_NAME_EMPLOYEES),
			...$this->fieldFactory->getFm([
				Phone::ID,
				Email::ID,
				Web::ID,
			]),
		];
	}

	protected function getImageAvatarFileId(): ?int
	{
		if (!$this->item->hasField(Company::FIELD_NAME_LOGO))
		{
			return null;
		}

		$logo = $this->item->get(Company::FIELD_NAME_LOGO);
		if (empty($logo))
		{
			return null;
		}

		return (int)$logo;
	}
}
