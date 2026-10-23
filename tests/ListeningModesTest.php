<?php

declare(strict_types=1);

use OnkyoAVR\ListeningModes;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/OnkyoAVRClass.php';

/**
 * Prüft die Auswahl der angebotenen Hörmodi (Konfiguration, gelernte Modi, aktueller Modus).
 */
class ListeningModesTest extends TestCase
{
    public function testDefaultsAreAllKnownModes(): void
    {
        $Names = ListeningModes::GetNames();
        $this->assertCount(36, $Names);
        foreach (ListeningModes::DefaultActive as $Code) {
            $this->assertArrayHasKey($Code, $Names, sprintf('Standardmodus %02X unbekannt', $Code));
        }
    }

    public function testThxModesAreNotOfferedByDefault(): void
    {
        $Codes = ListeningModes::GetActiveCodes('');
        $this->assertSame(ListeningModes::DefaultActive, $Codes);
        foreach (ListeningModes::GetNames() as $Code => $Name) {
            if (stripos($Name, 'THX') !== false) {
                $this->assertNotContains($Code, $Codes, $Name);
            }
        }
    }

    public function testSavedSelectionOverridesDefaults(): void
    {
        $Saved = self::saved([0x00, 0x04]);
        $this->assertSame([0x00, 0x04], ListeningModes::GetActiveCodes($Saved));
    }

    public function testInvalidPropertyFallsBackToDefaults(): void
    {
        foreach (['', 'kein json', '{}', '[]', 'null', '[1,2]'] as $Saved) {
            $this->assertSame(ListeningModes::DefaultActive, ListeningModes::GetActiveCodes($Saved), $Saved);
        }
    }

    public function testNewModesAreMergedWithSavedList(): void
    {
        // Gespeicherte Liste kennt nur einen Teil der Tabelle: der Rest nimmt den Standard
        $Saved = json_encode([['Code' => '00', 'Active' => false], ['Code' => '04', 'Active' => true]]);
        $Codes = ListeningModes::GetActiveCodes($Saved);
        $this->assertNotContains(0x00, $Codes);
        $this->assertContains(0x04, $Codes);
        $this->assertContains(0x01, $Codes, 'nicht gespeicherte Modi nutzen den Standard');
    }

    public function testAssociationsContainSelectedSeenAndCurrentModes(): void
    {
        $Saved = self::saved([0x00, 0x01]);
        // 0x42 (THX Cinema) wurde gemeldet, 0x11 ist der aktuelle Modus
        $Associations = ListeningModes::GetAssociations($Saved, [0x42], 0x11);
        $this->assertSame([0x00, 0x01, 0x11, 0x42], self::codes($Associations));
        $this->assertSame([0x42, 'THX Cinema', '', -1], $Associations[3]);
    }

    public function testAssociationsHaveNoDuplicatesAndAreSorted(): void
    {
        $Associations = ListeningModes::GetAssociations(self::saved([0x40, 0x00]), [0x00, 0x40, 0x02], 0x02);
        $this->assertSame([0x00, 0x02, 0x40], self::codes($Associations));
    }

    public function testUnknownCodesAreIgnored(): void
    {
        $Associations = ListeningModes::GetAssociations(self::saved([0x00]), [0x99, 0xFF], 0x77);
        $this->assertSame([0x00], self::codes($Associations));
    }

    public function testNoCurrentModeAndNothingSelected(): void
    {
        $this->assertSame([], ListeningModes::GetAssociations(self::saved([]), [], null));
    }

    public function testRowsForTheForm(): void
    {
        $Rows = ListeningModes::GetRows(self::saved([0x00]), [0x42], static fn (string $Text): string => '#' . $Text);
        $this->assertCount(36, $Rows);
        $First = $Rows[0];
        $this->assertSame(['Name' => '#STEREO', 'Code' => '00', 'Active' => true, 'Seen' => ''], $First);
        $Thx = array_values(array_filter($Rows, static fn (array $Row): bool => $Row['Code'] === '42'))[0];
        $this->assertFalse($Thx['Active']);
        $this->assertSame('✓', $Thx['Seen']);
    }

    public function testRowsKeepSelectionWhenSavedAgain(): void
    {
        // Das Formular speichert die Zeilen so, wie GetRows sie liefert
        $Rows = ListeningModes::GetRows(self::saved([0x00, 0x04]), []);
        $this->assertSame([0x00, 0x04], ListeningModes::GetActiveCodes(json_encode($Rows)));
    }
    private static function saved(array $Active): string
    {
        $Rows = [];
        foreach (ListeningModes::GetNames() as $Code => $Name) {
            $Rows[] = ['Code' => sprintf('%02X', $Code), 'Name' => $Name, 'Active' => in_array($Code, $Active, true), 'Seen' => ''];
        }
        return json_encode($Rows);
    }

    private static function codes(array $Associations): array
    {
        return array_column($Associations, 0);
    }
}
