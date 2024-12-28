<?php

namespace Bitrix\Mobile\Collab\Dto;

use Bitrix\Main\ArgumentException;
use Bitrix\Mobile\Collab\Enum\CollabPermissionsEnum;

class CollabCreatePermissionsSettingsDto
{
    /**
     * @throws ArgumentException
     */
    public function __construct(
        public readonly int $ownerId,
        public readonly array $moderatorIds,
        public CollabPermissionsEnum $inviters,
        public CollabPermissionsEnum $messageWriters,
    )
    {
        if ($ownerId < 1) {
            throw new \Bitrix\Main\ArgumentException('Owner id should be a positive number');
        }

        foreach ($moderatorIds as $id) {
            if (!is_int($id) || $id < 1) {
                throw new \Bitrix\Main\ArgumentException('Moderator id should be a positive number');
            }
        }
    }
}