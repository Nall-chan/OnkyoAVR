<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prüft die Aktionen unter actions/ (siehe Symcon SDK: Aktionen).
 */
class ActionsTest extends TestCase
{
    private const Guid = '/^\{[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}\}$/';

    /**
     * @var array<string, array{folder: string, prefix: string}> ModuleID => Ordner und Präfix
     */
    private static array $Modules = [];

    public static function setUpBeforeClass(): void
    {
        foreach (glob(__DIR__ . '/../Onkyo*/module.json') as $File) {
            $Json = json_decode(file_get_contents($File), true);
            self::$Modules[$Json['id']] = ['folder' => dirname($File), 'prefix' => $Json['prefix']];
        }
    }

    public static function actionFiles(): array
    {
        $Files = [];
        foreach (glob(__DIR__ . '/../actions/*.json') as $File) {
            $Files[basename($File)] = [$File];
        }
        return $Files;
    }

    public function testActionsExist(): void
    {
        $this->assertNotEmpty(self::actionFiles());
    }

    public function testIdsAreUniqueAndValid(): void
    {
        $Ids = [];
        foreach (self::actionFiles() as [$File]) {
            $Id = json_decode(file_get_contents($File), true)['id'] ?? '';
            $this->assertMatchesRegularExpression(self::Guid, $Id, basename($File));
            $this->assertArrayNotHasKey($Id, $Ids, basename($File) . ' hat die gleiche ID wie ' . ($Ids[$Id] ?? ''));
            $Ids[$Id] = basename($File);
        }
    }

    #[DataProvider('actionFiles')]
    public function testStructure(string $File): void
    {
        $Action = json_decode(file_get_contents($File), true);
        $this->assertIsArray($Action, 'Ungültiges JSON');
        foreach (['id', 'caption', 'form', 'action', 'category', 'restrictions', 'locale', 'format', 'description'] as $Key) {
            $this->assertArrayHasKey($Key, $Action, $Key);
        }
        $this->assertSame('target', $Action['category']);
        $this->assertSame([1], $Action['restrictions']['objectType']);
        $this->assertContains('Default', $Action['restrictions']['includeEnvironments']);
        $this->assertContains('TestCommands', $Action['restrictions']['includeEnvironments']);
        $this->assertNotEmpty($Action['restrictions']['moduleID']);
        foreach ($Action['restrictions']['moduleID'] as $ModuleId) {
            $this->assertArrayHasKey($ModuleId, self::$Modules, 'Unbekannte ModuleID ' . $ModuleId);
        }
    }

    #[DataProvider('actionFiles')]
    public function testCodeIsValidPhp(string $File): void
    {
        $Action = json_decode(file_get_contents($File), true);
        foreach (['action', 'form'] as $Key) {
            if (is_array($Action[$Key]) && (array_is_list($Action[$Key])) && (count($Action[$Key]) > 0) && is_string($Action[$Key][0])) {
                $Code = implode("\n", $Action[$Key]);
            } elseif (is_string($Action[$Key])) {
                $Code = $Action[$Key];
            } else {
                continue; // Formular als JSON-Elemente
            }
            try {
                token_get_all('<?php ' . $Code, TOKEN_PARSE);
            } catch (ParseError $Error) {
                $this->fail(basename($File) . ' (' . $Key . '): ' . $Error->getMessage());
            }
            $this->assertTrue(true);
        }
    }

    /**
     * Alle aufgerufenen Funktionen müssen in jedem Zielmodul als öffentliche Methode existieren.
     */
    #[DataProvider('actionFiles')]
    public function testCalledFunctionsExistInTargetModules(string $File): void
    {
        $Action = json_decode(file_get_contents($File), true);
        $Code = implode("\n", (array) $Action['action']);
        preg_match_all('/\b([A-Z]{2,6})_([A-Za-z0-9]+)\(/', $Code, $Matches, PREG_SET_ORDER);
        $Direct = array_map(static fn (array $Match): string => $Match[2], $Matches);
        // Aufrufe mit dynamischem Namen: call_user_func('OAVR_' . $_IPS['VALUE']) -> alle Werte der Auswahl
        if (preg_match("/call_user_func\('OAVR_' \. \\\$_IPS\['VALUE'\]/", $Code)) {
            foreach ($Action['form'][0]['options'] as $Option) {
                $Direct[] = $Option['value'];
            }
        }
        $this->assertNotEmpty($Direct, basename($File));
        foreach ($Action['restrictions']['moduleID'] as $ModuleId) {
            $Module = self::$Modules[$ModuleId];
            $Source = file_get_contents($Module['folder'] . '/module.php');
            preg_match_all('/public function (\w+)\(/', $Source, $Public);
            foreach ($Direct as $Function) {
                $this->assertContains($Function, $Public[1], basename($File) . ': ' . $Module['prefix'] . '_' . $Function . ' fehlt in ' . basename($Module['folder']));
            }
        }
        foreach ($Matches as $Match) {
            $this->assertSame('OAVR', $Match[1], basename($File));
        }
    }

    /**
     * Beschriftungen, Beschreibung und Format müssen übersetzt sein.
     */
    #[DataProvider('actionFiles')]
    public function testTextsAreTranslated(string $File): void
    {
        $Action = json_decode(file_get_contents($File), true);
        $De = $Action['locale']['de'] ?? [];
        $Texts = [$Action['caption'], $Action['description'], $Action['format']];
        $Form = $Action['form'];
        $Collect = function (mixed $Value) use (&$Collect, &$Texts): void
        {
            if (!is_array($Value)) {
                return;
            }
            foreach ($Value as $Key => $Item) {
                if (in_array($Key, ['caption', 'label', 'suffix'], true) && is_string($Item)) {
                    $Texts[] = $Item;
                }
                $Collect($Item);
            }
        };
        if (is_array($Form) && ((count($Form) == 0) || (!is_string($Form[0])))) {
            $Collect($Form);
        } else {
            preg_match_all("/'caption'\s*=>\s*'([^']+)'/", implode("\n", (array) $Form), $Found);
            array_push($Texts, ...$Found[1]);
        }
        foreach (array_unique($Texts) as $Text) {
            $this->assertArrayHasKey($Text, $De, basename($File) . ': "' . $Text . '" ist nicht übersetzt');
        }
    }

    /**
     * Die Select-Werte in Formularen mit festen Elementen müssen eindeutig sein.
     */
    #[DataProvider('actionFiles')]
    public function testSelectValuesAreUnique(string $File): void
    {
        $Action = json_decode(file_get_contents($File), true);
        $this->assertArrayHasKey('form', $Action);
        if (!is_array($Action['form'])) {
            return;
        }
        foreach ($Action['form'] as $Element) {
            if (!is_array($Element) || !isset($Element['options'])) {
                continue;
            }
            $Values = array_column($Element['options'], 'value');
            $this->assertSame(array_values(array_unique($Values)), $Values, basename($File));
            $this->assertContains($Element['value'], $Values, basename($File) . ': Vorgabewert nicht in der Auswahl');
        }
    }
}
