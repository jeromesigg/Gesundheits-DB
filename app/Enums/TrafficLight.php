<?php

namespace App\Enums;

/**
 * Ampel (Dringlichkeit); Werte = IDs der Tabelle health_statuses.
 */
enum TrafficLight: int
{
    case Green = 10;
    case Yellow = 20;
    case Red = 30;

    /**
     * CSS-Klasse des Ampel-Kreises.
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Green => 'green',
            self::Yellow => 'yellow',
            self::Red => 'red',
        };
    }
}
