<?php

/**
 * @file classes/ritNod/RitNodOrganizations.php
 *
 * Copyright (c) 2023-2026 Sasz Kolomon
 *
 * @class RitNodOrganizations
 *
 * @brief Lookup of the static organization list (organizations.php) by org_id or profile_ins.
 *
 * The source list has one record per organization *address*, so:
 *  - org_id repeats for organizations with several addresses (same name each time);
 *  - profile_ins is shared by many unrelated organizations (e.g. 75, 21899).
 * Lookups therefore return every matching record; callers match on the
 * distinct English names of those records.
 */

namespace PKP\ritNod;

class RitNodOrganizations
{
    public const KEY_ORG_ID = 'org_id';
    public const KEY_PROFILE_INS = 'profile_ins';

    /** @var array<int,array>|null */
    private static ?array $organizations = null;

    /**
     * All organization records.
     */
    public static function getAll(): array
    {
        return self::$organizations ??= require __DIR__ . '/organizations.php';
    }

    /**
     * All records whose $key equals $value.
     *
     * @param string $key One of the KEY_* constants
     */
    public static function findBy(string $key, int $value): array
    {
        if (!in_array($key, [self::KEY_ORG_ID, self::KEY_PROFILE_INS], true)) {
            throw new \InvalidArgumentException("Unsupported organization key {$key}");
        }
        return array_values(array_filter(
            self::getAll(),
            fn (array $org) => $org[$key] !== null && (int) $org[$key] === $value
        ));
    }

    /**
     * Distinct organizations (one record per org_id) from a list of records.
     */
    public static function distinctOrganizations(array $records): array
    {
        $distinct = [];
        foreach ($records as $record) {
            $distinct[$record['org_id']] ??= $record;
        }
        return array_values($distinct);
    }

    /**
     * Normalize an affiliation name the same way the submission Collector does in SQL:
     * tabs/CR/LF become spaces, then surrounding spaces are trimmed.
     */
    public static function normalizeName(?string $name): string
    {
        return trim(str_replace(["\t", "\r", "\n"], ' ', (string) $name), ' ');
    }

    /**
     * Display name of an organization record for the given UI locale.
     */
    public static function getDisplayName(array $org, string $locale): string
    {
        $name = str_starts_with($locale, 'uk') ? $org['full_name_inst'] : $org['full_name_inst_en'];
        return self::normalizeName($name ?: $org['full_name_inst_en']);
    }
}
