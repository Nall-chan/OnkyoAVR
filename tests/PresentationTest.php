<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/ConstantStubs.php';
require_once __DIR__ . '/../libs/OnkyoAVRClass.php';

use OnkyoAVR\IPSProfiles;
use OnkyoAVR\ISCP_API_Commands;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Prüft die aus den Profilen erzeugten Darstellungen gegen die Parameterlisten der Symcon-Kernel-Darstellungen.
 *
 * Die Dateien unter tests/presentations/*.json stammen aus dem Verzeichnis 'presentations' der Symcon-Installation
 * (Inhalt von IPS_GetPresentation) und müssen bei neuen Symcon-Versionen aktualisiert werden.
 */
class PresentationTest extends TestCase
{
    /**
     * @var array<string, array> GUID => Definition der Darstellung
     */
    private static array $Definitions = [];

    public static function setUpBeforeClass(): void
    {
        foreach (glob(__DIR__ . '/presentations/*.json') as $File) {
            $Definition = json_decode(file_get_contents($File), true);
            self::$Definitions[$Definition['id']] = $Definition;
        }
    }

    public function testDefinitionsAreAvailable(): void
    {
        $this->assertNotEmpty(self::$Definitions);
        foreach (['VARIABLE_PRESENTATION_SLIDER', 'VARIABLE_PRESENTATION_ENUMERATION', 'VARIABLE_PRESENTATION_SWITCH', 'VARIABLE_PRESENTATION_VALUE_PRESENTATION', 'VARIABLE_PRESENTATION_WEB_CONTENT'] as $Constant) {
            $this->assertArrayHasKey(constant($Constant), self::$Definitions, $Constant);
        }
        $this->assertArrayHasKey(VARIABLE_PRESENTATION_VALUE_INPUT, self::$Definitions, 'Werteingabe');
    }

    /**
     * Die Stubs definieren mehr Konstanten als Symcon: Im Code dürfen nur Konstanten stehen, die es in Symcon 9.1 wirklich gibt
     * (geprüft per defined() auf dem Live-System am 2026-10-23). Es gibt z.B. kein VARIABLE_PRESENTATION_TEXT_BOX,
     * mehrzeiliger Text ist VARIABLE_PRESENTATION_VALUE_INPUT mit MULTILINE.
     */
    public function testOnlyExistingPresentationConstantsAreUsed(): void
    {
        $Existing = ['SLIDER', 'ENUMERATION', 'SWITCH', 'VALUE_PRESENTATION', 'WEB_CONTENT', 'PLAYBACK', 'DURATION', 'LEGACY', 'VALUE_INPUT', 'COLOR', 'DATE_TIME', 'SHUTTER'];
        $Files = array_merge(glob(__DIR__ . '/../Onkyo*/module.php'), [__DIR__ . '/../libs/OnkyoAVRClass.php']);
        $this->assertNotEmpty($Files);
        foreach ($Files as $File) {
            preg_match_all('/VARIABLE_PRESENTATION_([A-Z_]+)/', file_get_contents($File), $Matches);
            foreach (array_unique($Matches[1]) as $Name) {
                $this->assertContains($Name, $Existing, basename(dirname($File)) . ': VARIABLE_PRESENTATION_' . $Name . ' existiert in Symcon nicht');
            }
        }
    }

    /**
     * Alle Profil-Schlüssel, die im Mapping vorkommen.
     */
    public static function mappingProfiles(): array
    {
        $Profiles = [];
        foreach (ISCP_API_Commands::$VarMapping as $Cmd => $Mapping) {
            if (!$Mapping[ISCP_API_Commands::IsVariable]) {
                continue;
            }
            $Profiles[$Cmd] = [$Mapping[ISCP_API_Commands::Profile]];
        }
        return $Profiles;
    }

    #[DataProvider('mappingProfiles')]
    public function testEveryMappingProfileHasAPresentation(string $Profile): void
    {
        $this->assertNotEmpty(IPSProfiles::GetPresentation($Profile), $Profile);
    }

    #[DataProvider('mappingProfiles')]
    public function testPresentationParametersAreKnownToTheKernel(string $Profile): void
    {
        $Presentation = IPSProfiles::GetPresentation($Profile);
        $this->assertArrayHasKey('PRESENTATION', $Presentation);
        $Guid = $Presentation['PRESENTATION'];
        $this->assertArrayHasKey($Guid, self::$Definitions, 'Unbekannte Darstellung ' . $Guid);
        $Known = self::$Definitions[$Guid]['presentationParameters'] ?? [];
        foreach (self::$Definitions[$Guid]['groups'] ?? [] as $Group) {
            $Known += $Group['presentationParameters'] ?? [];
        }
        unset($Presentation['PRESENTATION']);
        foreach ($Presentation as $Name => $Value) {
            $this->assertArrayHasKey($Name, $Known, $Profile . ': Parameter ' . $Name . ' unbekannt');
            $this->assertParameterValue($Known[$Name], $Value, $Profile . '.' . $Name);
        }
    }

    public function testSliderUsageTypes(): void
    {
        $this->assertSame(3, IPSProfiles::GetPresentation(IPSProfiles::ptVolume)['USAGE_TYPE'], 'Lautstärke');
        $this->assertSame(5, IPSProfiles::GetPresentation(IPSProfiles::ptSleep)['USAGE_TYPE'], 'keine Verwendung');
        $this->assertSame(0, IPSProfiles::GetPresentation(IPSProfiles::ptSwitch)['USAGE_TYPE'], 'An/Aus');
        $this->assertSame(1, IPSProfiles::GetPresentation(IPSProfiles::ptMute)['USAGE_TYPE'], 'Stumm schalten');
    }

    public function testDeviceValuesOverrideDefaults(): void
    {
        $Volume = IPSProfiles::GetPresentation(IPSProfiles::ptVolume, [IPSProfiles::ptVolume => [0, 100, 0.5, ' %']]);
        $this->assertSame(100, $Volume['MAX']);
        $this->assertSame(1, $Volume['STEP_SIZE'], 'Integer-Variablen brauchen eine Schrittweite von mindestens 1');

        $Invalid = IPSProfiles::GetPresentation(IPSProfiles::ptVolume, [IPSProfiles::ptVolume => [0, 0, 0, ' %']]);
        $this->assertSame(80, $Invalid['MAX'], 'Ungültiger Bereich vom Gerät: Standardwerte');
        $this->assertSame(1, $Invalid['STEP_SIZE']);

        $Level = IPSProfiles::GetPresentation(IPSProfiles::ptCenterLevel, [IPSProfiles::ptCenterLevel => [-12, 12, 0.5]]);
        $this->assertSame(1, $Level['DIGITS'], 'Schrittweite unter 1 braucht eine Nachkommastelle');
        $Level = IPSProfiles::GetPresentation(IPSProfiles::ptCenterLevel);
        $this->assertSame(0, $Level['DIGITS']);

        $Options = json_decode(IPSProfiles::GetPresentation(
            IPSProfiles::ptSelectInput,
            [],
            [IPSProfiles::ptSelectInput => [[0x2B, 'Network', '', -1]]],
            static fn (string $Text): string => '#' . $Text
        )['OPTIONS'], true);
        $this->assertSame([['Value' => 0x2B, 'Caption' => '#Network', 'IconActive' => false, 'IconValue' => '', 'Color' => -1]], $Options);
    }

    public function testEnumerationOptionsKnownToTheKernel(): void
    {
        $Known = self::$Definitions[VARIABLE_PRESENTATION_ENUMERATION]['groups'][1]['presentationParameters']['OPTIONS']['parameters'];
        foreach ([IPSProfiles::ptListeningMode, IPSProfiles::ptSelectInput, IPSProfiles::ptDisplayDimmer] as $Profile) {
            $Options = json_decode(IPSProfiles::GetPresentation($Profile)['OPTIONS'], true);
            $this->assertNotEmpty($Options, $Profile);
            foreach ($Options as $Option) {
                foreach (array_keys($Option) as $Key) {
                    $this->assertArrayHasKey($Key, $Known, $Profile . ': ' . $Key);
                }
            }
            $Values = array_column($Options, 'Value');
            $this->assertSame(array_values(array_unique($Values)), $Values, $Profile . ': doppelte Werte');
        }
    }

    public function testUnknownProfileHasNoPresentation(): void
    {
        $this->assertSame([], IPSProfiles::GetPresentation('~Unknown'));
    }

    /**
     * Prüft Typ und Wertebereich eines Parameters gegen die Kernel-Definition.
     *
     * @param array $Definition Definition des Parameters
     * @param mixed $Value Wert
     * @param string $Context Beschreibung für die Fehlermeldung
     */
    private function assertParameterValue(array $Definition, mixed $Value, string $Context): void
    {
        $Type = $Definition['type'];
        if ($Type === 0) {
            $this->assertIsBool($Value, $Context);
        } elseif ($Type === 1) {
            $this->assertIsInt($Value, $Context);
            if (isset($Definition['values'])) {
                $this->assertContains($Value, $Definition['values'], $Context);
            }
        } elseif ($Type === 2) {
            $this->assertIsFloat($Value + 0.0, $Context);
        } elseif ($Type === 3) {
            $this->assertIsString($Value, $Context);
        } elseif ($Type === 'variableType') {
            $this->assertIsNumeric($Value, $Context);
        }
    }
}
