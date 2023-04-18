<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property array $PredefinedSchedule
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "If true, the module is used in mobile version of the interface",
            ),
            "PredefinedSchedule" => new SettingsProperty(
                [
                    [
                        "DayOfWeek" => "today",
                        "Hour" => "18"
                    ],
                    [
                        "DayOfWeek" => "tomorrow",
                        "Hour" => "8"
                    ],
                    [
                        "DayOfWeek" => "monday",
                        "Hour" => "8"
                    ]
                ],
                "array",
                null,
                "Defines a list of presets used when scheduling a message for sending out",
            ),
        ];
    }
}
