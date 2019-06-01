<?php

declare(strict_types=1);
/*
 * @addtogroup onkyoavr
 * @{
 *
 * @file          OnkyoAVRClass.php
 *
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2018 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 *
 * @version       0.4
 */

namespace OnkyoAVR;

//  API Datentypen
class Remotes
{
    const OSD = 0;
    const CTV = 1;
    const CDV = 2;
    const CCD = 3;
    const CAP = 4;
    const TUN = 99;

    public static function ToRemoteName($ID)
    {
        switch ($ID) {
            case self::OSD:
                return 'OSD Receiver Control';
            case self::CTV:
                return 'TV Control';
            case self::CDV:
                return 'DVD/BD Control';
            case self::CCD:
                return 'CD Control';
            case self::CAP:
                return 'ext. Amplifier Control';
            case self::TUN:
                return 'Tuner';
        }
    }

    public static function ToRemoteID($Name)
    {
        $Parts = explode(' ', $Name);
        switch (strtoupper($Parts[0])) {
            case 'AMP':
                return self::CAP;
            case 'CD':
                return self::CCD;
            case 'BD':
            case 'DVD':
                return self::CDV;
            case 'TV':
                return self::CTV;
            case 'OSD':
                return self::OSD;
            case 'TUNER':
                return self::TUN;
            default:
                return -1;
        }
    }

}

class IPSVarType
{
    const vtNone = -1;
    const vtBoolean = 0;
    const vtInteger = 1;
    const vtFloat = 2;
    const vtString = 3;
    const vtDualInteger = 10;

}

class IPSProfiles
{
    const ptSwitch = '~Switch';
    const ptVolume = 'Onkyo.Volume.%d';
    const ptToneOffset = 'Onkyo.ToneOffset.%d';
    const ptCenterLevel = 'Onkyo.CenterLevel.%d'; //CTL
    const ptSubwooferLevel = 'Onkyo.SubwooferLevel.%d'; //SWL
    const ptSubwoofer2Level = 'Onkyo.Subwoofer2Level.%d'; //SW2
    const ptSleep = 'Onkyo.Sleep';
    const ptDisplayMode = 'Onkyo.DisplayMode';
    const ptDisplayDimmer = 'Onkyo.DisplayDimmer';
    const ptSelectInput = 'Onkyo.SelectInput.%d';
    const ptSelectInputAudio = 'Onkyo.SelectInputAudio';
    const ptSelectLMD = 'Onkyo.SelectLMD.%d';
    const ptHDMIOutput = 'Onkyo.HDMIOutput';
    const ptHDMIAudioOutput = 'Onkyo.HDMIAudioOutput';
    const ptVideoResolution = 'Onkyo.VideoResolution';
    const ptVideoWideMode = 'Onkyo.VideoWideMode';
    const ptVideoPictureMode = 'Onkyo.VideoPictureMode';
    const ptListeningMode = 'Onkyo.LMD';
    const ptLateNight = 'Onkyo.LateNight';
    const ptAudyssey = 'Onkyo.Audyssey';
    const ptAudysseyDynamic = 'Onkyo.AudysseyDynamic';

    //Main end
    //const ptNetRadioPreset = 'Onkyo.NetRadioPreset';
    //const ptNetTuneCommand = 'Onkyo.NetTuneCommand';

    public static $ProfileListIndexToProfile = [
        'Bass'             => self::ptToneOffset,
        'Center Level'     => self::ptCenterLevel,
        'Subwoofer Level'  => self::ptSubwooferLevel,
        'Subwoofer1 Level' => self::ptSubwooferLevel,
        'Subwoofer2 Level' => self::ptSubwoofer2Level
    ];
    public static $ProfilInteger = [
        self::ptToneOffset => [-10, 10, 2, ''],
        self::ptSleep      => [0x00, 0x5A, 1, ''],
        self::ptVolume     => [0, 80, 1, ' %']
//        self::ptNetRadioPreset => [0x01, 0x28, 1],
    ];
    public static $ProfilFloat = [
        self::ptCenterLevel     => [-12, 12, 1],
        self::ptSubwooferLevel  => [-15, 12, 1],
        self::ptSubwoofer2Level => [-15, 12, 1]
    ];
    public static $ProfilAssociations = [
        self::ptSelectLMD        => [
            [0x01, 'Movie/TV', '', -1],
            [0x02, 'Music', '', -1],
            [0x03, 'Game', '', -1],
            [0x04, 'Direct', '', -1]
        ],
        self::ptDisplayMode      => [
            [0x00, 'Input & Volume', '', -1],
            [0x01, 'Input & Listening Mode', '', -1],
            [0x02, 'Digital Format', '', -1],
            [0x03, 'Video Format', '', -1]
        ],
        self::ptDisplayDimmer    => [
            [0x00, 'Bright', '', -1],
            [0x01, 'Dim', '', -1],
            [0x02, 'Dark', '', -1],
            [0x03, 'Off', '', -1],
            [0x08, 'Bright & LED Off', '', -1]
        ],
        self::ptSelectInput      => [
            [0x00, 'Video 1 VCR/DVR', '', -1], //not z
            [0x01, 'Video 2 CBL/SAT', '', -1],
            [0x02, 'Video 3 GAME/TV', '', -1],
            [0x03, 'Video 4 AUX1(AUX)', '', -1],
            [0x04, 'Video 5 AUX2', '', -1], //not z
            [0x05, 'Video 6 PC', '', -1],
//'Video 7', //not z
            [0x10, 'BD/DVD', '', -1],
            [0x12, 'TV', '', -1],
            [0x20, 'TAPE 1', '', -1], // not z
//'TAPE2', // not z
            [0x22, 'PHONO', '', -1],
            [0x23, 'TV/CD', '', -1],
            [0x24, 'Tuner (FM)', '', -1],
            [0x25, 'Tuner (AM)', '', -1],
//            array(0x26, 'TUNER', '', -1), // not z
//'MUSIC SERVER DLNA',
//'INTERNET RADIO',
            [0x29, 'USB(Front)', '', -1],
//'USB(Rear)', // not z
            [0x2B, 'NETWORK', '', -1],
//'USB(toggle)', //lol
            [0x2D, 'Aiplay', '', -1], //?
            [0x2E, 'Bluetooth', '', -1], //?
//0x30 => 'MULTI CH', //not z
//'XM', //not z
//'SIRIUS', // not z
//'DAB', // not z
            [0x40, 'Universal PORT', '', -1] //not z
        ],
        self::ptSelectInputAudio => [
            [0x00, 'AUTO', '', -1],
            [0x01, 'MULTI-CHANNEL', '', -1],
            [0x02, 'ANALOG', '', -1],
            [0x03, 'iLINK', '', -1],
            [0x04, 'HDMI', '', -1],
            [0x05, 'COAX/OPT', '', -1],
            [0x06, 'BALANCE', '', -1],
            [0x07, 'ARC', '', -1],
            [0x0F, 'None', '', -1]
        ],
        self::ptHDMIOutput       => [
            [0x00, 'OFF (Analog)', '', -1],
            [0x01, 'Main', '', -1],
            [0x02, 'Sub', '', -1],
            [0x03, 'Both', '', -1],
            [0x04, 'Both (Main)', '', -1],
            [0x05, 'Both (Sub)', '', -1]
        ],
        self::ptHDMIAudioOutput  => [
            [0x00, 'Off', '', -1],
            [0x01, 'On', '', -1],
            [0x02, 'Auto', '', -1]
        ],
        self::ptVideoResolution  => [
            [0x00, 'Through', '', -1],
            [0x01, 'Auto(HDMI Output Only)', '', -1],
            [0x02, '480p', '', -1],
            [0x03, '720p', '', -1],
            [0x04, '1080i', '', -1],
            [0x05, '1080p', '', -1],
            [0x06, 'Source', '', -1],
            [0x07, '1080p/24fs', '', -1],
            [0x08, '4K Upcaling', '', -1]
        ],
        self::ptVideoWideMode    => [
            [0x00, 'Auto', '', -1],
            [0x01, '4:3', '', -1],
            [0x02, 'Full', '', -1],
            [0x03, 'Zoom', '', -1],
            [0x04, 'Wide Zoom', '', -1],
            [0x05, 'Smart Zoom', '', -1]
        ],
        self::ptVideoPictureMode => [
            [0x00, 'Through', '', -1],
            [0x01, 'Custom', '', -1],
            [0x02, 'Cinema', '', -1],
            [0x03, 'Game', '', -1],
            [0x05, 'ISF Day', '', -1],
            [0x06, 'ISF Night', '', -1],
            [0x07, 'Streaming', '', -1],
            [0x08, 'Direct (Bypass)', '', -1]
        ],
        self::ptListeningMode    => [
            [0x00, 'STEREO', '', -1],
            [0x01, 'DIRECT', '', -1],
            [0x02, 'SURROUND', '', -1],
            [0x03, 'FILM', '', -1],
            [0x04, 'THX', '', -1],
            [0x05, 'ACTION', '', -1],
            [0x06, 'MUSICAL', '', -1],
            [0x08, 'ORCHESTRA', '', -1],
            [0x09, 'UNPLUGGED', '', -1],
            [0x0A, 'STUDIO-MIX', '', -1],
            [0x0B, 'TV LOGIC', '', -1],
            [0x0C, 'ALL CH STEREO', '', -1],
            [0x0D, 'THEATER-DIMENSIONAL', '', -1],
            [0x0E, 'ENHANCED', '', -1],
            [0x0F, 'MONO', '', -1],
            [0x11, 'PURE AUDIO', '', -1],
            [0x13, 'FULL MONO', '', -1],
            //[0x16, 'Audyssey DSX', '', -1],
            [0x40, 'Straight Decode', '', -1],
            //[0x41, 'Dolby EX', '', -1],
            [0x42, 'THX Cinema', '', -1],
            [0x43, 'THX Surround EX', '', -1],
            [0x44, 'THX Music', '', -1],
            [0x45, 'THX Games', '', -1],
            [0x50, 'THX Cinema Mode, THX U2/S2/I/S Cinema', '', -1],
            [0x51, 'THX Music Mode, THX U2/S2/I/S Music', '', -1],
            [0x52, 'THX Games Mode, THX U2/S2/I/S Games', '', -1],
            [0x80, 'PLII/PLIIx Movie', '', -1],
            [0x81, 'PLII/PLIIx Music', '', -1],
            [0x82, 'Neo:6 Cinema/Neo:X Cinema', '', -1],
            [0x83, 'Neo:6 Music/Neo:X Music', '', -1],
            [0x84, 'PLII/PLIIx THX Cinema', '', -1],
            [0x85, 'Neo:6/Neo:X THX Cinema', '', -1],
            [0x86, 'PLII/PLIIx Game', '', -1],
            [0x89, 'PLII/PLIIx THX Games', '', -1],
            [0x8A, 'Neo:6/Neo:X THX Games', '', -1],
            [0x8B, 'PLII/PLIIx THX Music', '', -1],
            [0x8C, 'Neo:6/Neo:X THX Music', '', -1]],
        self::ptLateNight        => [
            [0x00, 'Off', '', -1],
            [0x01, 'Low', '', -1],
            [0x02, 'High', '', -1],
            [0x03, 'Auto', '', -1]
        ],
        self::ptAudyssey         => [
            [0x00, 'Off', '', -1],
            [0x01, 'On (Movie)', '', -1],
            [0x02, 'On (Music)', '', -1]
        ],
        self::ptAudysseyDynamic  => [
            [0x00, 'Off', '', -1],
            [0x01, 'Light', '', -1],
            [0x02, 'Medium', '', -1],
            [0x03, 'Heavy', '', -1]
        ]
    ];

}

class ONKYO_Zone_NetPlayer
{
    const ZoneMain = 1;
    const Zone2 = 2;
    const Zone3 = 3;
    const Zone4 = 4;

    public $thisZone = self::ZoneMain;

    public function __construct($Zone = self::ZoneMain)
    {
        $this->thisZone = $Zone;
    }

    public function GetName()
    {
        switch ($this->thisZone) {
            case 1:
                return 'NetPlayer Main';
            case 2:
                return 'NetPlayer Zone 2';
            case 3:
                return 'NetPlayer Zone 3';
            case 4:
                return 'NetPlayer Zone 4';
        }
    }

    public static $ZoneCMDs = [
        self::ZoneMain => [
            ISCP_API_Commands::NPR
        ],
        self::Zone2    => [
            ISCP_API_Commands::NPZ
        ],
        self::Zone3    => [
            ISCP_API_Commands::NP3
        ],
        self::Zone4    => [
            ISCP_API_Commands::NP4
        ]
    ];

    public function GetReadAPICommands()
    {
        return [
            ISCP_API_Commands::NMS, // todo
            ISCP_API_Commands::NDS, // todo 
            ISCP_API_Commands::NDS,
            ISCP_API_Commands::NTR,
            ISCP_API_Commands::NTM,
            ISCP_API_Commands::NST,
            ISCP_API_Commands::NAL,
            ISCP_API_Commands::NTI,
            ISCP_API_Commands::NAT
        ];
    }

    public function GetZoneCommand(string $APICommand)
    {
        $key = array_search($APICommand, self::$ZoneCMDs[self::ZoneMain]);
        return self::$ZoneCMDs[$this->thisZone][$key];
    }

}

class ONKYO_Zone_Tuner
{
    const ZoneMain = 1;
    const Zone2 = 2;
    const Zone3 = 3;
    const Zone4 = 4;
    const SLI_FM = 0x24;
    const SLI_AM = 0x25;
    const FM = 'FM';
    const AM = 'AM';

    public $thisZone = self::ZoneMain;

    public function __construct($Zone = self::ZoneMain)
    {
        $this->thisZone = $Zone;
    }

    public function GetName()
    {
        switch ($this->thisZone) {
            case 1:
                return 'Tuner Main';
            case 2:
                return 'Tuner Zone 2';
            case 3:
                return 'Tuner Zone 3';
            case 4:
                return 'Tuner Zone 4';
        }
    }

    public static $TunerProfile = [
        self::FM => [
            'SLI'    => self::SLI_FM,
            'Min'    => 87,
            'Max'    => 108,
            'Step'   => 0.5,
            'Suffix' => ' MHz',
            'Digits' => 1
        ],
        self::AM => [
            'SLI'    => self::SLI_AM,
            'Min'    => 522,
            'Max'    => 1629,
            'Step'   => 9,
            'Suffix' => ' kHz',
            'Digits' => 0
        ]
    ];
    public static $ZoneCMDs = [
        self::ZoneMain => [
            ISCP_API_Commands::TUN,
            ISCP_API_Commands::PRS,
            ISCP_API_Commands::SLI
        ], self::Zone2    => [
            ISCP_API_Commands::TUZ,
            ISCP_API_Commands::PRZ,
            ISCP_API_Commands::SLZ
        ], self::Zone3    => [
            ISCP_API_Commands::TU3,
            ISCP_API_Commands::PR3,
            ISCP_API_Commands::SL3
        ], self::Zone4    => [
            ISCP_API_Commands::TU4,
            ISCP_API_Commands::PR4,
            ISCP_API_Commands::SL4
        ]
    ];

    public function GetReadAPICommands()
    {
        return self::$ZoneCMDs[$this->thisZone];
    }

    public function GetZoneCommand(string $APICommand)
    {
        $key = array_search($APICommand, self::$ZoneCMDs[self::ZoneMain]);
        return self::$ZoneCMDs[$this->thisZone][$key];
    }

}

class ONKYO_Zone
{
    const None = 0;
    const ZoneMain = 1;
    const Zone2 = 2;
    const Zone3 = 3;
    const Zone4 = 4;
    const Tuner = 5;
    const Netplayer = 6;

    public $thisZone = self::None;

    public function GetName()
    {
        switch ($this->thisZone) {
            case 1:
                return 'Main';
            case 2:
                return 'Zone 2';
            case 3:
                return 'Zone 3';
            case 4:
                return 'Zone 4';
            default:
                return 'Zone not set';
        }
    }

    public static $ZoneCMDs = [
        self::None      => [
        ],
        self::ZoneMain  => [
            ISCP_API_Commands::PWR,
            ISCP_API_Commands::AMT,
            ISCP_API_Commands::MVL,
            ISCP_API_Commands::TFR,
            ISCP_API_Commands::TFW,
            ISCP_API_Commands::TFH,
            ISCP_API_Commands::TCT,
            ISCP_API_Commands::TSR,
            ISCP_API_Commands::TSB,
            ISCP_API_Commands::TSW,
            ISCP_API_Commands::PMB,
            ISCP_API_Commands::SLP,
            ISCP_API_Commands::SLC,
            ISCP_API_Commands::SWL,
            ISCP_API_Commands::SW2,
            ISCP_API_Commands::CTL,
            ISCP_API_Commands::DIF,
            ISCP_API_Commands::DIM,
            ISCP_API_Commands::OSD,
            ISCP_API_Commands::MEM,
            ISCP_API_Commands::IFA,
            ISCP_API_Commands::IFV,
            ISCP_API_Commands::SLI,
            ISCP_API_Commands::SLA,
            ISCP_API_Commands::TGA,
            ISCP_API_Commands::TGB,
            ISCP_API_Commands::TGC,
            ISCP_API_Commands::HDO,
            ISCP_API_Commands::HAO,
            ISCP_API_Commands::HAS,
            ISCP_API_Commands::CEC,
            ISCP_API_Commands::RES,
            ISCP_API_Commands::ISF,
            ISCP_API_Commands::VWM,
            ISCP_API_Commands::VPM,
            ISCP_API_Commands::LMD,
            ISCP_API_Commands::LTN,
            ISCP_API_Commands::RAS,
            ISCP_API_Commands::ADY,
            ISCP_API_Commands::ADQ,
            ISCP_API_Commands::ADV,
            ISCP_API_Commands::MOT,
//Start Airplay
            /*
              ISCP_API_Commands::AAT,
              ISCP_API_Commands::AAL,
              ISCP_API_Commands::ATI,
              ISCP_API_Commands::ATM,
              ISCP_API_Commands::AST,
             */
            //Ende Airplay
//START CMD via PORT
            ISCP_API_Commands::CPT,
//'IAT' - iPod Artist Name Info (Universal Port Dock Only)
//'IAL' - iPod Album Name Info (Universal Port Dock Only)
//'ITI' - iPod Title Name (Universal Port Dock Only)
//'ITM' - iPod Time Info (Universal Port Dock Only)
//'ITR' - iPod Track Info (Universal Port Dock Only)
//'IST' - iPod Play Status (Universal Port Dock Only)
//'ILS' - iPod List Info (Universal Port Dock Extend Mode Only)
//'IMD' - iPod Mode Change (Universal Port Dock Only)
//'UTN' - Tuning Command (Universal Port Dock Only)
//'UPR' - Preset Command (Universal Port Dock Only)
//'UPM' - Preset Memory Command (Universal Port Dock Only)
//'UHP' - HD Radio Channel Program Command (Universal Port Dock Only)
//'UHB' - HD Radio Blend Mode Command (Universal Port Dock Only)
//'UHA' - HD Radio Artist Name Info (Universal Port Dock Only)
//'UHC' - HD Radio Channel Name Info (Universal Port Dock Only)
//'UHT' - HD Radio Title Info (Universal Port Dock Only)
//'UHD' - HD Radio Detail Info (Universal Port Dock Only)
//'UHS' - HD Radio Tuner Status (Universal Port Dock Only)
//'UPR' - DAB Preset Command (Universal Port Dock Only)
//'UPM' - Preset Memory Command (Universal Port Dock Only)
//'UDS' - DAB Sation Name (Universal Port Dock Only)
//'UDD' - DAB Display Info (Universal Port Dock Only)
//ENDE CMD via PORT
        ],
        self::Zone2     => [
            ISCP_API_Commands::ZPW,
            ISCP_API_Commands::ZMT,
            ISCP_API_Commands::ZVL,
            ISCP_API_Commands::ZTN,
            ISCP_API_Commands::SLZ,
        ],
        self::Zone3     => [
            ISCP_API_Commands::PW3,
            ISCP_API_Commands::MT3,
            ISCP_API_Commands::VL3,
            ISCP_API_Commands::TN3,
            ISCP_API_Commands::SL3
        ],
        self::Zone4     => [
            ISCP_API_Commands::PW4,
            ISCP_API_Commands::MT4,
            ISCP_API_Commands::VL4,
            ISCP_API_Commands::SL4
        ],
        self::Tuner     => [
            ISCP_API_Commands::TUN,
            ISCP_API_Commands::TUZ,
            ISCP_API_Commands::TU3,
            ISCP_API_Commands::TU4,
            ISCP_API_Commands::PRS,
            ISCP_API_Commands::PRZ,
            ISCP_API_Commands::PR3,
            ISCP_API_Commands::PR4,
            ISCP_API_Commands::PRM,
            ISCP_API_Commands::RDS,
            ISCP_API_Commands::PTS,
            ISCP_API_Commands::SLI,
            ISCP_API_Commands::SLZ,
            ISCP_API_Commands::SL3,
            ISCP_API_Commands::SL4
        ],
        self::Netplayer => [
            //Start NET/USB
            ISCP_API_Commands::NAT,
            ISCP_API_Commands::NAL,
            ISCP_API_Commands::NTI,
            ISCP_API_Commands::NTM,
            ISCP_API_Commands::NTR,
            ISCP_API_Commands::NST,
            // ISCP_API_Commands::NMS, // todo
            ISCP_API_Commands::NTS, // todo nur senden "NTS" - NET/USB Time Seek
            // ISCP_API_Commands::NDS, // todo
            //ISCP_API_Commands::NLS,  // nur senden
            //ISCP_API_Commands::NLA,  // nur senden
            ISCP_API_Commands::NJA,
            ISCP_API_Commands::NLT,
            //ISCP_API_Commands::NSV, // nur senden
            //ISCP_API_Commands::NKY, // "NKY" - NET Keyboard(for Network Control Only)
            //ISCP_API_Commands::NPU, // "NPU" - NET Popup Message(for Network Control Only)
            ISCP_API_Commands::NTC,
            ISCP_API_Commands::NPR,
            ISCP_API_Commands::NTZ,
            ISCP_API_Commands::NPZ,
            ISCP_API_Commands::NT3,
            ISCP_API_Commands::NP3,
            ISCP_API_Commands::NT4,
            ISCP_API_Commands::NP4,
        /*            ISCP_API_Commands::SLI,
          ISCP_API_Commands::SLZ,
          ISCP_API_Commands::SL3,
          ISCP_API_Commands::SL4 */
        //ENDE Net/USB
        ]
    ];

    public function __construct($Zone = \OnkyoAVR\ONKYO_Zone::None)
    {
        $this->thisZone = $Zone;
    }

    public function __sleep()
    {
        return ['thisZone'];
    }

    public function CmdAvaiable(string $APICommand)
    {
        return in_array($APICommand, self::$ZoneCMDs[$this->thisZone]);
    }

    public function GetAPICommands()
    {
        return self::$ZoneCMDs[$this->thisZone];
    }

    /*  public function SubCmdAvaiable(ISCP_API_Data $API_Data)
      {

      //        IPS_LogMessage('APISubCommand', print_r($API_Data->APISubCommand, 1));
      //        IPS_LogMessage('ZoneCMDs', print_r(self::$ZoneCMDs[$this->thisZone], 1));
      if ($API_Data->APISubCommand != null) {
      if (property_exists($API_Data->APISubCommand, (string) $this->thisZone)) {
      return in_array($API_Data->APISubCommand->{$this->thisZone}, self::$ZoneCMDs[$this->thisZone]);
      }
      }
      return false;
      }
     */
}

class ISCP_API_Mode
{
    const LAN = 1;
    const COM = 2;

}

class ISCP_API_Commands
{
    //Special
    const GetBuffer = 'BBB';
    const SelectorList = 'SelectorList';
    const ControlList = 'ControlList';
    const ProfileList = 'ProfileList';
    const LMDList = 'LMDList';
    const NetserviceList = 'NetserviceList';
    const PresetList = 'PresetList';
    const TunerList = 'TunerList';
    const ZoneList = 'ZoneList';
    const PhaseMatchingBass = 'PhaseMatchingBass';
    //MAIN Zone
    const PWR = 'PWR'; // Power
    const AMT = 'AMT'; // Mute
    const MVL = 'MVL'; //'MVL' - Master Volume Command
    const TFR = 'TFR'; //'TFR' - Tone(Front) Command
    const TFW = 'TFW'; //'TFW' - Tone(Front Wide) Command
    const TFH = 'TFH'; //'TFH' - Tone(Front High) Command
    const TCT = 'TCT'; //'TCT' - Tone(Center) Command
    const TSR = 'TSR'; //'TSR' - Tone(Surround) Command
    const TSB = 'TSB'; //'TSB' - Tone(Surround Back) Command
    const TSW = 'TSW'; //'TSW' - Tone(Subwoofer) Command
    const PMB = 'PMB'; //'PMB' - Phase Matching Bass Command
    const SLP = 'SLP'; //'SLP' - Sleep Set Command
    const SLC = 'SLC'; //'SLC' - Speaker Level Calibration Command
    const SWL = 'SWL'; //'SWL' - Subwoofer (temporary) Level Command
    const SW2 = 'SW2'; //'SW2' - Subwoofer 2 (temporary) Level Command
    const CTL = 'CTL'; //'CTL' - Center (temporary) Level Command
    const DIF = 'DIF'; //'DIF' - Display Mode Command
    const DIM = 'DIM'; //'DIM' - Dimmer Level Command
    const OSD = 'OSD'; //'OSD' - Setup Operation Command
    const MEM = 'MEM'; //'MEM' - Memory Setup Command
    const IFA = 'IFA'; //'IFA' - Audio Infomation Command
    const IFV = 'IFV'; //'IFV' - Video Infomation Command
    const SLI = 'SLI'; // 'SLI' - Input Selector Command
    const SLA = 'SLA'; //'SLA' - Audio Selector Command
    const TGA = 'TGA'; //'TGA' - 12V Trigger A Command
    const TGB = 'TGB'; //'TGB' - 12V Trigger B Command
    const TGC = 'TGC'; //'TGC' - 12V Trigger C Command
    const HDO = 'HDO'; //'HDO' - HDMI Output Selector
    const HAO = 'HAO'; //'HAO' -HDMI Audio Out (Main)
    const HAS = 'HAS'; //'HAS' -HDMI Audio Out (Sub)
    const CEC = 'CEC'; //'CEC' - HDMI CEC
    const RES = 'RES'; //'RES' - Monitor Out Resolution
    const ISF = 'ISF';
    const VWM = 'VWM'; //'VWM' - Video Wide Mode
    const VPM = 'VPM'; //'VPM' -Video Picture Mode
    const LMD = 'LMD'; //'LMD' - Listening Mode Command
    const LTN = 'LTN'; //'LTN' - Late Night Command
    const RAS = 'RAS'; //'RAS' - Re-EQ Command
    const ADY = 'ADY'; //'ADY' - Audyssey 2EQ/MultEQ/MultEQ XT
    const ADQ = 'ADQ'; //'ADQ' - Audyssey Dynamic EQ
    const ADV = 'ADV'; //'ADV' - Audyssey Dynamic Volume
    const MOT = 'MOT'; //'MOT' - Music Optimizer
    const ECO = 'ECO'; //'ECO' - for Smart Grid Command
    const TUN = 'TUN'; //'TUN' - Tuning Command (Include Tuner Pack Model Only)
    const PRS = 'PRS'; //'PRS' - Preset Command (Include Tuner Pack Model Only)
    const PRM = 'PRM'; //'PRM' - Preset Memory Command (Include Tuner Pack Model Only)
    const RDS = 'RDS'; //'RDS' - RDS Information Command (RDS Model Only)
    const PTS = 'PTS'; //'PTS' - PTY Scan Command (RDS Model Only)
// HD Radio
    /*
      const HAT = 'HAT';
      const HCN = 'HCN';
      const HTI = 'HTI';
      const HDS = 'HDS';
      const HPR = 'HPR';
      const HBL = 'HBL';
      const HTS = 'HTS';
     */
    //Start NET/USB
    const NTC = 'NTC'; //'NTC' - Network/USB Operation Command (Network Model Only after TX-NR905)
    const NPR = 'NPR'; //'NPR' - Internet Radio Preset Command
    const NAT = 'NAT'; //NET/USB Artist Name Info
    const NAL = 'NAL'; //NET/USB Album Name Info
    const NTI = 'NTI'; // NET/USB Title Name
    const NTM = 'NTM'; // NET/USB Time Info
    const NTR = 'NTR'; // NET/USB Track Info
    const NST = 'NST'; // NET/USB Play Status
    const NMS = 'NMS'; // NET/USB Menu Status
    const NTS = 'NTS'; // 'NTS' - NET/USB Time Seek
    const NDS = 'NDS'; // NET Connection/USB Device Status
    const NLS = 'NLS'; // NET/USB List Info
    const NLT = 'NLT'; // NET/USB List Info(All item, need processing XML data, for Network Control Only)
    const NLA = 'NLA'; // NET/USB List Info(All item, need processing XML data, for Network Control Only)
    const NJA = 'NJA'; // NET/USB Jacket Art (When Jacket Art is available and Output for Network Control Only)
    const NSV = 'NSV'; // NET Service(for Network Control Only)
    const NKY = 'NKY'; // NET Keyboard(for Network Control Only)
    const NPU = 'NPU'; // NET Popup Message(for Network Control Only)
    const NRI = 'NRI'; // Receiver Information (for Network Control Only)
//ENDE Net/USB
//Start Airplay
    /* const AAT = 'AAT'; //'AAT' - Airplay Artist Name Info (Airplay Model Only)
      const AAL = 'AAL'; //'AAL' - Airplay Album Name Info (Airplay Model Only)
      const ATI = 'ATI'; //'ATI' - Airplay Title Name (Airplay Model Only)
      const ATM = 'ATM'; //'ATM' - Airplay Time Info (Airplay Model Only)
      const AST = 'AST'; //'AST' - Airplay Play Status (Airplay Model Only)
     */
    //Ende Airplay
//START CMD via PORT
    const CPT = 'CPT'; //'CPT' - Universal PORT Operation Command
//'IAT' - iPod Artist Name Info (Universal Port Dock Only)
//'IAL' - iPod Album Name Info (Universal Port Dock Only)
//'ITI' - iPod Title Name (Universal Port Dock Only)
//'ITM' - iPod Time Info (Universal Port Dock Only)
//'ITR' - iPod Track Info (Universal Port Dock Only)
//'IST' - iPod Play Status (Universal Port Dock Only)
//'ILS' - iPod List Info (Universal Port Dock Extend Mode Only)
//'IMD' - iPod Mode Change (Universal Port Dock Only)
//'UTN' - Tuning Command (Universal Port Dock Only)
//'UPR' - Preset Command (Universal Port Dock Only)
//'UPM' - Preset Memory Command (Universal Port Dock Only)
//'UHP' - HD Radio Channel Program Command (Universal Port Dock Only)
//'UHB' - HD Radio Blend Mode Command (Universal Port Dock Only)
//'UHA' - HD Radio Artist Name Info (Universal Port Dock Only)
//'UHC' - HD Radio Channel Name Info (Universal Port Dock Only)
//'UHT' - HD Radio Title Info (Universal Port Dock Only)
//'UHD' - HD Radio Detail Info (Universal Port Dock Only)
//'UHS' - HD Radio Tuner Status (Universal Port Dock Only)
//'UPR' - DAB Preset Command (Universal Port Dock Only)
//'UPM' - Preset Memory Command (Universal Port Dock Only)
//'UDS' - DAB Sation Name (Universal Port Dock Only)
//'UDD' - DAB Display Info (Universal Port Dock Only)
//ENDE CMD via PORT
//MAIN end
//Zone2 Zone
    const ZPW = 'ZPW';
    const ZMT = 'ZMT';
    const ZVL = 'ZVL';
    const ZTN = 'ZTN';
    const SLZ = 'SLZ';
    const TUZ = 'TUZ';
    const PRZ = 'PRZ';
    const NTZ = 'NTZ'; // Network Zone
    const NPZ = 'NPZ'; //Network Zone
    //Zone3 Zone
    const PW3 = 'PW3';  // Power
    const MT3 = 'MT3';  // Mute
    const VL3 = 'VL3';  // Volume
    const TN3 = 'TN3';  // Tone
    const SL3 = 'SL3';  // Selector
    const TU3 = 'TU3';  // Tune Tuner Zone
    const PR3 = 'PR3';  // Preset Tuner Zone
    const NT3 = 'NT3';  // Net-Tune Network Zone
    const NP3 = 'NP3';  // Net-Preset Network Zone
//Zone4 Zone
    const PW4 = 'PW4';  // Power
    const MT4 = 'MT4';  // Mute
    const VL4 = 'VL4';  // Volume
    const SL4 = 'SL4';  // Selector
    const TU4 = 'TU4';  // Tuner Zone
    const PR4 = 'PR4';  // Preset Tuner Zone
    const NT4 = 'NT4';  // Net-Tune Network Zone
    const NP4 = 'NP4';  // Net-Preset Network Zone
    const Request = 'QSTN';

    public static $BoolValueMapping = [
        false => '00',
        true  => '01',
        '00'  => false,
        '01'  => true
    ];

    const IsVariable = 0;
    const VarType = 1;
    const VarName = 2;
    const Profile = 3;
    const EnableAction = 4;
    const RequestValue = 5;
    const ValueMapping = 6;
    const ValuePrefix = 7;

    public static $VarMapping = [
        self::PWR => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::AMT => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ], /*
          self::SPA => [
          self::VarType      => IPSVarType::vtBoolean,
          self::EnableAction => true,
          self::Profile      => IPSProfiles::ptSwitch,
          self::IsVariable   => true,
          self::VarName      => 'Speaker A',
          self::RequestValue => true,
          self::ValueMapping => null
          ],
          self::SPB => [
          self::VarType      => IPSVarType::vtBoolean,
          self::EnableAction => true,
          self::Profile      => IPSProfiles::ptSwitch,
          self::IsVariable   => true,
          self::VarName      => 'Speaker B',
          self::RequestValue => true,
          self::ValueMapping => null
          ], */
        self::MVL => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::TFR => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Front Treble', 'B' => 'Front Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TFW => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Front Wide Treble', 'B' => 'Front Wide Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TFH => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Front High Treble', 'B' => 'Front High Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TCT => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Center Treble', 'B' => 'Center Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TSR => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Surround Treble', 'B' => 'Surround Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TSB => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Surround Back Treble', 'B' => 'Surround Back Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::TSW => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['B' => 'Subwoofer Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['B' => 0],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::PMB => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Phase Matching Bass',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::SLP => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSleep,
            self::IsVariable   => true,
            self::VarName      => 'Sleeptimer',
            self::RequestValue => true,
            self::ValueMapping => ['OFF' => 0]
        ],
        self::CTL => [
            self::VarType      => IPSVarType::vtFloat,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptCenterLevel,
            self::IsVariable   => true,
            self::VarName      => 'Center Level',
            self::RequestValue => true,
            self::ValueMapping => 'Level'
        ],
        self::SWL => [
            self::VarType      => IPSVarType::vtFloat,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSubwooferLevel,
            self::IsVariable   => true,
            self::VarName      => 'Subwoofer Level',
            self::RequestValue => true,
            self::ValueMapping => 'Level'
        ],
        self::SW2 => [
            self::VarType      => IPSVarType::vtFloat,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSubwoofer2Level,
            self::IsVariable   => true,
            self::VarName      => 'Subwoofer 2 Level',
            self::RequestValue => true,
            self::ValueMapping => 'Level'
        ],
        self::DIF => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptDisplayMode,
            self::IsVariable   => true,
            self::VarName      => 'Display Mode',
            self::RequestValue => true,
            self::ValueMapping => ['TG' => 0xFF]
        ],
        self::DIM => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptDisplayDimmer,
            self::IsVariable   => true,
            self::VarName      => 'Display Dimmer',
            self::RequestValue => true,
            self::ValueMapping => ['DIM' => 0xFF]
        ],
        self::IFA => [
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '~TextBox',
            self::IsVariable   => true,
            self::VarName      => 'Audio Information',
            self::RequestValue => true,
            self::ValueMapping => ','
        ],
        self::IFV => [
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '~TextBox',
            self::IsVariable   => true,
            self::VarName      => 'Video Information',
            self::RequestValue => true,
            self::ValueMapping => ','
        ],
        self::SLI => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::SLA => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInputAudio,
            self::IsVariable   => true,
            self::VarName      => 'Audio Input',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::TGA => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger A',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::TGB => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger B',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::TGC => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger C',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::HDO => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptHDMIOutput,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Output',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::HAO => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptHDMIAudioOutput,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Audio Output',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::HAS => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Audio Output (Sub)',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::CEC => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'HDMI CEC Control',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::RES => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoResolution,
            self::IsVariable   => true,
            self::VarName      => 'Monitor Out Resolution',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::VWM => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoWideMode,
            self::IsVariable   => true,
            self::VarName      => 'Video Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::VPM => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoPictureMode,
            self::IsVariable   => true,
            self::VarName      => 'Video Picture Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::LMD => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptListeningMode,
            self::IsVariable   => true,
            self::VarName      => 'Listening Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::LTN => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptLateNight,
            self::IsVariable   => true,
            self::VarName      => 'Late Night',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::RAS => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Re-EQ or Cinema Filter',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ADY => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptAudyssey,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ADQ => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey Dynamic EQ',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ADV => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptAudysseyDynamic,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey Dynamic Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::MOT => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Music Optimizer',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        // Main end
        // Zone2 start
        self::ZPW => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ZMT => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ZVL => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::ZTN => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Treble', 'B' => 'Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        self::SLZ => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        // Zone 2 end
        // Zone 3 start
        self::PW3 => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::MT3 => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::VL3 => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::SL3 => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::TN3 => [
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => ['T' => 'Treble', 'B' => 'Bass'],
            self::RequestValue => true,
            self::ValuePrefix  => ['T' => 0, 'B' => 1],
            self::ValueMapping => ['-A' => -10, '-8' => -8, '-6' => -6, '-4' => -4,
                '-2' => -2, '00' => 0, '+2' => 2, '+4' => 4, '+6' => 6, '+8' => 8,
                '+A' => 10]
        ],
        // Zone 3 end
        // Zone 4 start
        self::PW4 => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::MT4 => [
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::VL4 => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
        self::SL4 => [
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input',
            self::RequestValue => true,
            self::ValueMapping => null
        ],
            // Zone 4 end
            // MORE TODO Network all
            /* self::NTC => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetTuneCommand,
              self::IsVariable   => false,
              self::VarName      => null,
              self::RequestValue => false,
              self::ValueMapping => null
              ], */
            /* self::NTZ => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetTuneCommand,
              self::IsVariable   => false,
              self::VarName      => null,
              self::RequestValue => false,
              self::ValueMapping => null
              ],
              self::NT3 => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetTuneCommand,
              self::IsVariable   => false,
              self::VarName      => null,
              self::RequestValue => false,
              self::ValueMapping => null
              ],
              self::NT4 => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetTuneCommand,
              self::IsVariable   => false,
              self::VarName      => null,
              self::RequestValue => false,
              self::ValueMapping => null
              ], */
            /*        self::NPR => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetRadioPreset,
              self::IsVariable   => true,
              self::VarName      => 'Network Radio Preset',
              self::RequestValue => false,
              self::ValueMapping => null
              ], */
            /* self::NPZ => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetRadioPreset,
              self::IsVariable   => true,
              self::VarName      => 'Network Radio Preset',
              self::RequestValue => false,
              self::ValueMapping => null
              ],
              self::NP3 => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetRadioPreset,
              self::IsVariable   => true,
              self::VarName      => 'Network Radio Preset',
              self::RequestValue => false,
              self::ValueMapping => null
              ],
              self::NP4 => [
              self::VarType      => IPSVarType::vtInteger,
              self::EnableAction => true,
              self::Profile      => IPSProfiles::ptNetRadioPreset,
              self::IsVariable   => true,
              self::VarName      => 'Network Radio Preset',
              self::RequestValue => false,
              self::ValueMapping => null
              ] */
    ];

}

class ISCP_API_Data_Mapping
{
    public static function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$VarMapping)) {
            $result = new \stdClass();
            $result->IsVariable = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::IsVariable];
            $result->VarType = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarType];
            $result->VarName = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarName];
            $result->Profile = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::Profile];
            $result->EnableAction = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::EnableAction];
            $result->RequestValue = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::RequestValue];
            $result->ValueMapping = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::ValueMapping];
            if (array_key_exists(ISCP_API_Commands::ValuePrefix, ISCP_API_Commands::$VarMapping[$Cmd])) {
                $result->ValuePrefix = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::ValuePrefix];
            }
            return $result;
        }
        return null;
    }

}

/**
 * @property ISCP_API_Commands $APICommand
 * @property mixed $Data
 * @property \stdClass $Mapping
 * @property bool $needResponse
 */
class ISCP_API_Data
{
    public $APICommand;
    public $Data;
    public $Mapping;
    public $SubIndex;
    public $needResponse;

    public function __construct(string $Command = null, $Data = null, bool $needResponse = true)
    {
        $this->Mapping = null;
        $this->needResponse = $needResponse;

        if ($Data !== null) {
            $this->APICommand = substr($Command, 0, 3);
            if (strlen($Command) == 4) {
                $this->SubIndex = substr($Command, -1);
            }
            $this->Data = $Data;
            //$this->APISubCommand = (object) ISCP_API_Command_Mapping::GetMapping($this->APICommand);
            return;
        }
        if ($Command === null) {
            return;
        }
        if ($Command[strlen($Command) - 1] === "\x1A") {
            $this->APICommand = substr($Command, 2, 3);
            $this->Data = substr($Command, 5, -1);
        } else {
            $json = json_decode($Command);
            $this->APICommand = $json->APICommand;
            $this->Data = utf8_decode($json->Data);
            $this->needResponse = $json->needResponse;
        }
    }

    public function ToJSONString($GUID)
    {
        $SendData = new \stdClass();
        $SendData->DataID = $GUID;
        $SendData->APICommand = $this->APICommand;
        $SendData->Data = utf8_encode($this->Data);
        $SendData->needResponse = $this->needResponse;
        return json_encode($SendData);
    }

    public function ToISCPString($Mode)
    {
        if (is_bool($this->Data)) {
            $Value = \OnkyoAVR\ISCP_API_Commands::$BoolValueMapping($this->Data);
        } elseif (is_int($this->Data)) {
            $Value = sprintf('%02X', $this->Data);
        } else {
            $Value = $this->Data;
        }
        $Payload = '!1' . $this->APICommand . $Value . "\r\n";
        if ($Mode == \OnkyoAVR\ISCP_API_Mode::LAN) {
            $PayloadLen = pack('N', strlen($Payload));
            $eSICPHeader = $PayloadLen . "\x01\x00\x00\x00";
            $eISCPHeaderlen = pack('N', strlen($eSICPHeader) + 8);
            $Frame = 'ISCP' . $eISCPHeaderlen . $eSICPHeader . $Payload;
        } else {
            $Frame = $Payload;
        }
        return $Frame;
    }

    public function GetMapping()
    {
        return ISCP_API_Data_Mapping::GetMapping($this->APICommand);
    }

    /*
      public function GetSubCommand()
      {
      $this->APISubCommand = (object) ISCP_API_Command_Mapping::GetMapping($this->APICommand);
      } */
}
