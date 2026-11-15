<?php

namespace Webuild\ThemeKit\Contract;

interface ThemeContract
{
    public const API_VERSION = '1.1';

    /**
     * Map of public view keys to their domain subfolders under pages/
     *
     * @return array<string, string>
     */
    public static function getRequiredPages(): array;

    /**
     * List of semantic section types categorized by layout area.
     *
     * @return array<string, list<string>>
     */
    public static function getRequiredSections(): array;

    /**
     * List of core route names expected by public themes.
     *
     * @return list<string>
     */
    public static function getRequiredRoutes(): array;

    /**
     * Standard view variables injected to layouts and sections.
     *
     * @return list<string>
     */
    public static function getStandardViewVariables(): array;
}

