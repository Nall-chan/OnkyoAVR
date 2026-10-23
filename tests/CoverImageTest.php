<?php

declare(strict_types=1);

use OnkyoAVR\CoverImage;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/OnkyoAVRClass.php';

/**
 * Prüft die Umwandlung der Cover des Gerätes (BMP, JPEG) in PNG.
 */
class CoverImageTest extends TestCase
{
    private const PngSignature = "\x89PNG\r\n\x1a\n";

    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('Die PHP-Erweiterung gd fehlt.');
        }
    }

    public function testBmpIsConvertedToPng(): void
    {
        $Bmp = $this->createImage('bmp');
        $this->assertSame('BM', substr($Bmp, 0, 2));
        $Png = CoverImage::ToPng($Bmp);
        $this->assertSame(self::PngSignature, substr($Png, 0, 8));
        $Size = getimagesizefromstring($Png);
        $this->assertSame([8, 6], [$Size[0], $Size[1]]);
        $this->assertSame('image/png', $Size['mime']);
    }

    public function testJpegIsConvertedToPng(): void
    {
        $Png = CoverImage::ToPng($this->createImage('jpeg'));
        $this->assertSame(self::PngSignature, substr($Png, 0, 8));
        $this->assertSame([8, 6], array_slice(getimagesizefromstring($Png), 0, 2));
    }

    public function testGifIsConvertedToPng(): void
    {
        $this->assertSame(self::PngSignature, substr(CoverImage::ToPng($this->createImage('gif')), 0, 8));
    }

    public function testPngIsKeptUnchanged(): void
    {
        $Png = $this->createImage('png');
        $this->assertSame($Png, CoverImage::ToPng($Png));
    }

    public function testInvalidDataGivesEmptyResult(): void
    {
        $this->assertSame('', CoverImage::ToPng(''));
        $this->assertSame('', CoverImage::ToPng('das ist kein Bild'));
        // abgeschnittene BMP-Daten (unvollständige Übertragung)
        $this->assertSame('', CoverImage::ToPng(substr($this->createImage('bmp'), 0, 20)));
    }

    private function createImage(string $Format): string
    {
        $Image = imagecreatetruecolor(8, 6);
        imagefill($Image, 0, 0, imagecolorallocate($Image, 200, 40, 40));
        ob_start();
        match ($Format) {
            'bmp'  => imagebmp($Image, null, false),
            'jpeg' => imagejpeg($Image),
            'png'  => imagepng($Image),
            'gif'  => imagegif($Image),
        };
        return (string) ob_get_clean();
    }
}
