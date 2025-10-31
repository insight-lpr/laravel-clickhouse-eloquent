<?php

namespace LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums;

/**
 * Formats.
 */
enum Format: string
{
    case BLOCK_TAB_SEPARATED = 'BlockTabSeparated';
    case CSV = 'CSV';
    case CSV_WITH_NAMES = 'CSVWithNames';
    case JSON = 'JSON';
    case JSON_COMPACT = 'JSONCompact';
    case JSON_EACH_ROW = 'JSONEachRow';
    case NATIVE = 'Native';
    case NULL = 'Null';
    case PRETTY = 'Pretty';
    case PRETTY_COMPACT = 'PrettyCompact';
    case PRETTY_COMPACT_MONO_BLOCK = 'PrettyCompactMonoBlock';
    case PRETTY_NO_ESCAPES = 'PrettyNoEscapes';
    case PRETTY_COMPACT_NO_ESCAPES = 'PrettyCompactNoEscapes';
    case PRETTY_SPACE_NO_ESCAPES = 'PrettySpaceNoEscapes';
    case PRETTY_SPACE = 'PrettySpace';
    case ROW_BINARY = 'RowBinary';
    case TAB_SEPARATED = 'TabSeparated';
    case TAB_SEPARATED_RAW = 'TabSeparatedRaw';
    case TAB_SEPARATED_WITH_NAMES = 'TabSeparatedWithNames';
    case TAB_SEPARATED_WITH_NAMES_AND_TYPES = 'TabSeparatedWithNamesAndTypes';
    case TSKV = 'TSKV';
    case VALUES = 'Values';
    case VERTICAL = 'Vertical';
    case XML = 'XML';
    case TSV = 'TSV';

    /**
     * Check if a value is a valid enum value.
     */
    public static function isValid(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return self::tryFrom($value) !== null;
    }

    /**
     * Get the enum value as a string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the enum case name.
     */
    public function getKey(): string
    {
        return $this->name;
    }
}
