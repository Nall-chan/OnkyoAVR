<?php

declare(strict_types=1);

use OnkyoAVR\PlaylistIndex;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/OnkyoAVRClass.php';

/**
 * Prüft die Bestimmung des gespielten Eintrags der Playlist (Index 0 ist '..').
 */
class PlaylistIndexTest extends TestCase
{
    private const Songs = ['..', 'Eins', 'Zwei', 'Drei', 'Vier'];

    public function testTrackNumberMatchesTitle(): void
    {
        $this->assertSame(3, PlaylistIndex::Find(self::Songs, 3, 'Drei'));
        $this->assertSame(1, PlaylistIndex::Find(self::Songs, 1, 'Eins'));
        $this->assertSame(4, PlaylistIndex::Find(self::Songs, 4, 'Vier'));
    }

    public function testTitleWinsWhenTrackNumberDoesNotMatch(): void
    {
        // Die Titelnummer ist noch alt, der Titel ist schon neu (oder umgekehrt): der eindeutige Titel entscheidet
        $this->assertSame(2, PlaylistIndex::Find(self::Songs, 1, 'Zwei'));
    }

    public function testTrackNumberIsUsedWhenTitleIsNotInList(): void
    {
        // Titel im Stück weicht vom Namen in der Liste ab
        $this->assertSame(2, PlaylistIndex::Find(self::Songs, 2, 'Zwei (Remastered)'));
    }

    public function testAmbiguousTitleFallsBackToTrackNumber(): void
    {
        $Songs = ['..', 'Intro', 'Lied', 'Intro', 'Ende'];
        $this->assertSame(3, PlaylistIndex::Find($Songs, 3, 'Intro'));
        $this->assertSame(1, PlaylistIndex::Find($Songs, 1, 'Intro'));
        // zwei Treffer, die Titelnummer passt zu keinem: die Nummer gilt
        $this->assertSame(2, PlaylistIndex::Find($Songs, 2, 'Intro'));
    }

    public function testBackEntryIsNeverCurrent(): void
    {
        $this->assertNull(PlaylistIndex::Find(self::Songs, 0, '..'));
        $this->assertNull(PlaylistIndex::Find(self::Songs, 0, 'Unbekannt'));
    }

    public function testInvalidInputGivesNull(): void
    {
        $this->assertNull(PlaylistIndex::Find(self::Songs, 9, 'Unbekannt'), 'Titelnummer außerhalb der Liste');
        $this->assertNull(PlaylistIndex::Find(self::Songs, -1, 'Unbekannt'));
        $this->assertNull(PlaylistIndex::Find(['..'], 1, 'Eins'), 'Liste ohne Titel');
        $this->assertNull(PlaylistIndex::Find([], 1, 'Eins'));
    }

    public function testEmptyTitleUsesTrackNumber(): void
    {
        $this->assertSame(2, PlaylistIndex::Find(self::Songs, 2, ''));
    }

    public function testNonSequentialKeysAreHandled(): void
    {
        $this->assertSame(2, PlaylistIndex::Find([5 => '..', 7 => 'Eins', 9 => 'Zwei'], 2, 'Zwei'));
    }
}
