<?php
/**
 * Validator.php
 * Small static helper class used to validate user-entered data
 * on the server side (PHP), as required by the assignment.
 */
class Validator
{
    public static function required($value): bool
    {
        return isset($value) && trim((string)$value) !== '';
    }

    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isPhone(string $value): bool
    {
        // Allow digits, spaces, +, -, ( ) - length between 6 and 20
        return (bool)preg_match('/^[0-9+\-\s()]{6,20}$/', $value);
    }

    public static function isPositiveInt($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false && (int)$value > 0;
    }

    public static function isPositiveNumber($value): bool
    {
        return is_numeric($value) && (float)$value > 0;
    }

    public static function isDate(string $value): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }

    public static function isTime(string $value): bool
    {
        $t = DateTime::createFromFormat('H:i', $value);
        return $t && $t->format('H:i') === $value;
    }

    public static function minLength(string $value, int $len): bool
    {
        return mb_strlen($value) >= $len;
    }

    public static function clean(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
