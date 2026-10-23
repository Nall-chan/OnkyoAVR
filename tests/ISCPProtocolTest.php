<?php

declare(strict_types=1);

use OnkyoAVR\ISCP_API_Commands;
use OnkyoAVR\ISCP_API_Data;
use OnkyoAVR\ISCP_API_Data_Mapping;
use OnkyoAVR\ISCP_API_Mode;
use OnkyoAVR\ONKYO_Zone;
use OnkyoAVR\ONKYO_Zone_NetPlayer;
use OnkyoAVR\ONKYO_Zone_Tuner;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/OnkyoAVRClass.php';

/**
 * Tests für den Protokoll-Code (ISCP / eISCP) ohne Symcon-Abhängigkeit.
 *
 * Referenz: ISCP-Dokumentation unter /docs. Ein eISCP-Paket besteht aus
 * 'ISCP' + Headerlänge (4 Byte BE, immer 16) + Datenlänge (4 Byte BE) + Version 0x01 + 3 Byte reserviert + Nutzdaten.
 * Die Nutzdaten sind '!1' + Befehl (3 Zeichen) + Wert + "\r\n" (Start-Zeichen '!', Unit-Type '1' = Receiver).
 */
class ISCPProtocolTest extends TestCase
{
    public function testLanFrameForPowerOn(): void
    {
        $Data = new ISCP_API_Data(ISCP_API_Commands::PWR, true);
        $Expected = "ISCP\x00\x00\x00\x10\x00\x00\x00\x09\x01\x00\x00\x00" . "!1PWR01\r\n";
        $this->assertSame($Expected, $Data->ToISCPString(ISCP_API_Mode::LAN));
    }

    public function testLanFrameForPowerRequest(): void
    {
        $Data = new ISCP_API_Data(ISCP_API_Commands::PWR, ISCP_API_Commands::Request);
        $Expected = "ISCP\x00\x00\x00\x10\x00\x00\x00\x0B\x01\x00\x00\x00" . "!1PWRQSTN\r\n";
        $this->assertSame($Expected, $Data->ToISCPString(ISCP_API_Mode::LAN));
    }

    public function testSerialFrameHasNoHeader(): void
    {
        $Data = new ISCP_API_Data(ISCP_API_Commands::PWR, false);
        $this->assertSame("!1PWR00\r\n", $Data->ToISCPString(ISCP_API_Mode::COM));
    }

    public function testIntegerIsSentAsTwoDigitUpperCaseHex(): void
    {
        $Data = new ISCP_API_Data(ISCP_API_Commands::MVL, 42);
        $this->assertSame("!1MVL2A\r\n", $Data->ToISCPString(ISCP_API_Mode::COM));
        $Data = new ISCP_API_Data(ISCP_API_Commands::MVL, 5);
        $this->assertSame("!1MVL05\r\n", $Data->ToISCPString(ISCP_API_Mode::COM));
    }

    public function testFrameLengthFieldsMatchPayload(): void
    {
        $Data = new ISCP_API_Data(ISCP_API_Commands::SLI, 36);
        $Frame = $Data->ToISCPString(ISCP_API_Mode::LAN);
        $this->assertSame('ISCP', substr($Frame, 0, 4));
        $HeaderLen = unpack('N', substr($Frame, 4, 4))[1];
        $PayloadLen = unpack('N', substr($Frame, 8, 4))[1];
        $this->assertSame(16, $HeaderLen);
        $this->assertSame(strlen($Frame) - $HeaderLen, $PayloadLen);
    }

    public function testParseResponseTerminatedWithSubstitute(): void
    {
        // Antworten des Geräts enden auf 0x1A (Substitute)
        $Data = new ISCP_API_Data("!1MVL2A\x1A");
        $this->assertSame(ISCP_API_Commands::MVL, $Data->APICommand);
        $this->assertSame('2A', $Data->Data);
    }

    public function testParseResponseWithLongValue(): void
    {
        $Data = new ISCP_API_Data("!1NATSome Artist\x1A");
        $this->assertSame(ISCP_API_Commands::NAT, $Data->APICommand);
        $this->assertSame('Some Artist', $Data->Data);
    }

    public function testJsonRoundTrip(): void
    {
        $GUID = '{0123456789-TEST}';
        $Data = new ISCP_API_Data(ISCP_API_Commands::AMT, '01', false);
        $Json = $Data->ToJSONString($GUID);
        $this->assertSame($GUID, json_decode($Json)->DataID);
        $Copy = new ISCP_API_Data($Json);
        $this->assertSame(ISCP_API_Commands::AMT, $Copy->APICommand);
        $this->assertSame('01', $Copy->Data);
        $this->assertFalse($Copy->needResponse);
    }

    public function testBoolValueMappingBothDirections(): void
    {
        $this->assertSame('00', ISCP_API_Commands::$BoolValueMapping[false]);
        $this->assertSame('01', ISCP_API_Commands::$BoolValueMapping[true]);
        $this->assertFalse(ISCP_API_Commands::$BoolValueMapping['00']);
        $this->assertTrue(ISCP_API_Commands::$BoolValueMapping['01']);
    }

    public function testUnknownCommandHasNoMapping(): void
    {
        $this->assertNull(ISCP_API_Data_Mapping::GetMapping('ZZZ'));
    }

    public function testEveryMappingIsComplete(): void
    {
        $this->assertNotEmpty(ISCP_API_Commands::$VarMapping);
        foreach (array_keys(ISCP_API_Commands::$VarMapping) as $Cmd) {
            $Mapping = ISCP_API_Data_Mapping::GetMapping((string) $Cmd);
            $this->assertNotNull($Mapping, (string) $Cmd);
            foreach (['IsVariable', 'VarType', 'VarName', 'Profile', 'EnableAction', 'RequestValue', 'ValueMapping'] as $Property) {
                $this->assertTrue(property_exists($Mapping, $Property), $Cmd . ' ohne ' . $Property);
            }
        }
    }

    public function testZoneCommandAvailability(): void
    {
        $Main = new ONKYO_Zone(ONKYO_Zone::ZoneMain);
        $this->assertTrue($Main->CmdAvailable(ISCP_API_Commands::PWR));
        $this->assertSame('Main', $Main->GetName());
        $None = new ONKYO_Zone(ONKYO_Zone::None);
        $this->assertFalse($None->CmdAvailable(ISCP_API_Commands::PWR));
        $this->assertSame('Zone not set', $None->GetName());
    }

    public function testZoneCommandsAreMappedAndUnique(): void
    {
        foreach ([ONKYO_Zone::ZoneMain, ONKYO_Zone::Zone2, ONKYO_Zone::Zone3, ONKYO_Zone::Zone4] as $ZoneId) {
            $Commands = (new ONKYO_Zone($ZoneId))->GetAPICommands();
            $this->assertNotEmpty($Commands, 'Zone ' . $ZoneId);
            $this->assertSame(array_values(array_unique($Commands)), array_values($Commands), 'Doppelte Befehle in Zone ' . $ZoneId);
        }
    }

    public function testNetPlayerZonesAreAlignedToMainZone(): void
    {
        $Main = ONKYO_Zone_NetPlayer::$ZoneCMDs[ONKYO_Zone_NetPlayer::ZoneMain];
        foreach ([ONKYO_Zone_NetPlayer::Zone2, ONKYO_Zone_NetPlayer::Zone3, ONKYO_Zone_NetPlayer::Zone4] as $ZoneId) {
            $this->assertCount(count($Main), ONKYO_Zone_NetPlayer::$ZoneCMDs[$ZoneId], 'NetPlayer Zone ' . $ZoneId);
            $Zone = new ONKYO_Zone_NetPlayer($ZoneId);
            $this->assertSame(ONKYO_Zone_NetPlayer::$ZoneCMDs[$ZoneId][0], $Zone->GetZoneCommand($Main[0]));
        }
        $this->assertFalse((new ONKYO_Zone_NetPlayer(ONKYO_Zone_NetPlayer::Zone2))->GetZoneCommand('ZZZ'));
    }

    public function testTunerZonesAreAlignedToMainZone(): void
    {
        $Main = ONKYO_Zone_Tuner::$ZoneCMDs[ONKYO_Zone_Tuner::ZoneMain];
        foreach ([ONKYO_Zone_Tuner::Zone2, ONKYO_Zone_Tuner::Zone3, ONKYO_Zone_Tuner::Zone4] as $ZoneId) {
            $this->assertCount(count($Main), ONKYO_Zone_Tuner::$ZoneCMDs[$ZoneId], 'Tuner Zone ' . $ZoneId);
        }
    }
}
