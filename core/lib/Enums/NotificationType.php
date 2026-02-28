<?php

namespace Gov2lib\Enums;

/**
 * Notification/alert type mapping.
 * Maps between legacy Bulma-style class names and Bootstrap-style variants.
 */
enum NotificationType: string
{
    case PRIMARY = 'is-primary';
    case INFO = 'is-info';
    case SUCCESS = 'is-success';
    case WARNING = 'is-warning';
    case DANGER = 'is-danger';

    /**
     * Convert to Bootstrap 5 alert variant.
     */
    public function toBootstrap(): string
    {
        return match ($this) {
            self::PRIMARY => 'primary',
            self::INFO => 'info',
            self::SUCCESS => 'success',
            self::WARNING => 'warning',
            self::DANGER => 'danger',
        };
    }

    /**
     * Create from a legacy Bulma class string.
     */
    public static function fromLegacy(string $class): self
    {
        return self::tryFrom($class) ?? self::INFO;
    }
}
