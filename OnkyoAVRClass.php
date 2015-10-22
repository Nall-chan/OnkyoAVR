<?

//  API Datentypen
class IPSVarType extends stdClass
{

    const vtNone = -1;
    const vtBoolean = 0;
    const vtInteger = 1;
    const vtFloat = 2;
    const vtString = 3;

}

class IPSProfiles extends stdClass
{

    const ptSwitch = '~Switch';
    const ptSpeakerLayout = 'SpeakerLayout.Onkyo';
    const ptVolume = '~Intensity.100';
    const ptSleep ='Sleep.Onkyo';
    const ptDisplayMode='DisplayMode.Onkyo';
    const ptDisplayDimmer ='DisplayDimmer.Onkyo';
    
    
    const ptSelectInput = 'SelectInput.Onkyo';
    const ptListeningMode = 'LMD.Onkyo';
    const ptNetRadioPreset = 'NetRadioPreset.Onkyo';
    const ptRadioPreset = 'RadioPreset.Onkyo';
    const ptVideoResolution = 'VideoResolution.Onkyo';
    const ptSelectInputAudio = 'SelectInputAudio.Onkyo';
    const ptVideoWideMode = 'VideoWideMode.Onkyo';
    const ptTunerFrequenz = 'TunerFrequenz.Onkyo';
    const ptNetTuneCommand = 'NetTuneCommand.Onkyo';

    static $ProfilInteger = array(
        self::ptSleep => array(0x00,0x5A),
        
        self::ptNetRadioPreset => array(0x01, 0x30),
        self::ptRadioPreset => array(0x01, 0x30)
    );
    static $ProfilAssociations = array(
//      self::ptMute=> array(),
        self::ptSpeakerLayout=>array(
            array(0x01, "Surround Back", "", -1),
            array(0x02, "Front High", "", -1),
            array(0x03, "Front Wide", "", -1),
            array(0x04, "Front High & Front Wide", "", -1)
            ),
        self::ptDisplayMode=>array(
            array(0x00, "Selector & Volume", -1),
            array(0x01, "Selector & Listening Mode", "", -1),
            array(0x02, "Digital Format", "", -1),
            array(0x03, "Video Format", "", -1)
            ),
        self::ptDisplayDimmer=>array(
            array(0x00, "Bright", -1),
            array(0x01, "Dim", "", -1),
            array(0x02, "Dark", "", -1),
            array(0x03, "Off", "", -1),
            array(0x08, "Bright & LED Off", "", -1)            
            ),
        self::ptSelectInput => array(
            array(0x00, "Video 1 VCR/DVR", "", -1), //not z
            array(0x01, "Video 2 CBL/SAT", "", -1),
            array(0x02, "Video 3 GAME/TV", "", -1),
            array(0x03, "Video 4 AUX1(AUX)", "", -1),
            array(0x04, "Video 5 AUX2", "", -1), //not z
            array(0x05, "Video 6 PC", "", -1),
//"Video 7", //not z
            array(0x10, "BD/DVD", "", -1),
            array(0x20, "TV", "", -1), // not z
//"TAPE2", // not z
            array(0x22, "PHONO", "", -1),
            array(0x23, "TV/CD", "", -1),
            array(0x24, "FM", "", -1),
            array(0x25, "AM", "", -1),
            array(0x26, "TUNER", "", -1), // not z
//"MUSIC SERVER DLNA",
//"INTERNET RADIO",
            array(0x29, "USB(Front)", "", -1),
//"USB(Rear)", // not z
            array(0x2B, "NETWORK", "", -1),
//"USB(toggle)", //lol
            array(0x2D, "Aiplay", "", -1), //?
//0x30 => "MULTI CH", //not z
//"XM", //not z
//"SIRIUS", // not z
//"DAB", // not z
            array(0x40, "Universal PORT", "", -1) //not z
        ),
        self::ptSelectInputAudio => array(
            array(0x00, "AUTO", "", -1),
            array(0x01, "MULTI-CHANNEL", "", -1),
            array(0x02, "ANALOG", "", -1),
            array(0x03, "iLINK", "", -1),
            array(0x04, "HDMI", "", -1),
            array(0x05, "COAX/OPT", "", -1),
            array(0x06, "BALANCE", "", -1),
            array(0x07, "ARC", "", -1),
            array(0x0F, "None", "", -1)
        ),
        // MORE TODO HDO -> VPM
                self::ptVideoResolution => array(
            array(0x00, "Through", "", -1),
            array(0x01, "Auto(HDMI Output Only)", "", -1),
            array(0x02, "480p", "", -1),
            array(0x03, "720p", "", -1),
            array(0x04, "1080i", "", -1),
            array(0x05, "1080p(HDMI Output Only)", "", -1),
            array(0x06, "Source", "", -1),
            array(0x07, "1080p/24fs(HDMI Output Only)", "", -1),
            array(0x08, "4K Upcaling(HDMI Output Only)", "", -1)
        ),
        self::ptVideoWideMode => array(
            array(0x00, "Auto", "", -1),
            array(0x01, "4:3", "", -1),
            array(0x02, "Full", "", -1),
            array(0x03, "Zoom", "", -1),
            array(0x04, "Wide Zoom", "", -1),
            array(0x05, "Smart Zoom", "", -1)
        ),
        
        self::ptListeningMode => array(
            array(0x00, "STEREO", "", -1),
            array(0x01, "DIRECT", "", -1),
            array(0x02, "SURROUND", "", -1),
            array(0x03, "FILM", "", -1),
            array(0x04, "THX", "", -1),
            array(0x05, "ACTION", "", -1),
            array(0x06, "MUSICAL", "", -1),
            array(0x08, "ORCHESTRA", "", -1),
            array(0x09, "UNPLUGGED", "", -1),
            array(0x0A, "STUDIO-MIX", "", -1),
            array(0x0B, "TV LOGIC", "", -1),
            array(0x0C, "ALL CH STEREO", "", -1),
            array(0x0D, "THEATER-DIMENSIONAL", "", -1),
            array(0x0E, "ENHANCED", "", -1),
            array(0x0F, "MONO", "", -1),
            array(0x11, "PURE AUDIO", "", -1),
            array(0x13, "FULL MONO", "", -1),
            array(0x16, "Audyssey DSX", "", -1),
            array(0x40, "Straight Decode", "", -1),
            array(0x41, "Dolby EX", "", -1),
            array(0x42, "THX Cinema", "", -1),
            array(0x43, "THX Surround EX", "", -1),
            array(0x44, "THX Music", "", -1),
            array(0x45, "THX Games", "", -1),
            array(0x50, "THX Cinema Mode, THX U2/S2/I/S Cinema", "", -1),
            array(0x51, "THX Music Mode, THX U2/S2/I/S Music", "", -1),
            array(0x52, "THX Games Mode, THX U2/S2/I/S Games", "", -1),
            array(0x84, "PLII/PLIIx THX Cinema", "", -1),
            array(0x80, "PLII/PLIIx Movie", "", -1),
            array(0x81, "PLII/PLIIx Music", "", -1),
            array(0x82, "Neo:6 Cinema/Neo:X Cinema", "", -1),
            array(0x83, "Neo:6 Music/Neo:X Music", "", -1)
//            array(0x85,"Neo:6/Neo:X THX Cinema", "" ,-1)
        /*
          array(0x86,"PLII/PLIIx Game", "" ,-1),
          array(0x89,"PLII/PLIIx THX Games", "" ,-1),
          "Neo:6/Neo:X THX Games", "" ,-1),
          "PLII/PLIIx THX Music", "" ,-1),
          "Neo:6/Neo:X THX Music", "" ,-1),
          0x90 => "PLIIz Height", "" ,-1),
          0x94 => "PLIIz Height + THX Cinema", "" ,-1),
          "PLIIz Height + THX Music", "" ,-1),
          "PLIIz Height + THX Games", "" ,-1),
          0xA0 => "PLIIx/PLII Movie + Audyssey DSX", "" ,-1),
          "PLIIx/PLII Music + Audyssey DSX", "" ,-1),
          "PLIIx/PLII Game + Audyssey DSX", "" ,-1), */
        )

            //      self::ptTunerFrequenz => array(),
//        self::ptNetTuneCommand => array()
    );

}

class ONKYO_Zone extends stdClass
{

    const None = 0;
    const ZoneMain = 1;
    const Zone2 = 2;
    const Zone3 = 3;
    const Zone4 = 4;

    public $thisZone;
    static $ZoneCMDs = array(
        ONKYO_Zone::ZoneMain => array(
            ISCP_API_Commands::PWR,
            ISCP_API_Commands::AMT,
            ISCP_API_Commands::SPA,
            ISCP_API_Commands::SPB,
            ISCP_API_Commands::SPL,
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
            ISCP_API_Commands::VWM,
            ISCP_API_Commands::VPM,
            ISCP_API_Commands::LMD,
            ISCP_API_Commands::LTN,
            ISCP_API_Commands::RAS,
            ISCP_API_Commands::ADY,
            ISCP_API_Commands::ADQ,
            ISCP_API_Commands::ADV,
            ISCP_API_Commands::DVL,
            ISCP_API_Commands::MOT,
            ISCP_API_Commands::AVS,
            ISCP_API_Commands::ECO,
            ISCP_API_Commands::TUN,
            ISCP_API_Commands::PRS,
            ISCP_API_Commands::PRM,
            ISCP_API_Commands::RDS,
            ISCP_API_Commands::PTS,
            ISCP_API_Commands::TPS,
            ISCP_API_Commands::NTC,
            ISCP_API_Commands::NAT,
            ISCP_API_Commands::NAL,
            ISCP_API_Commands::NTI,
            ISCP_API_Commands::NTM,
            ISCP_API_Commands::NTR,
            ISCP_API_Commands::NST,
            ISCP_API_Commands::NMS,
            ISCP_API_Commands::NTS,
            ISCP_API_Commands::NPR,
            ISCP_API_Commands::NDS,
            ISCP_API_Commands::NLS,
            ISCP_API_Commands::NLA,
            ISCP_API_Commands::NJA,
            ISCP_API_Commands::NSV,
            ISCP_API_Commands::NKY,
            ISCP_API_Commands::NPU,
            ISCP_API_Commands::NLT,
            ISCP_API_Commands::NMD,
            ISCP_API_Commands::NSB,
            ISCP_API_Commands::NRI
        ),
        ONKYO_Zone::Zone2 => array(
            ISCP_API_Commands::LMZ,
            ISCP_API_Commands::ZPW,
            ISCP_API_Commands::ZMT,
            ISCP_API_Commands::ZVL,
            ISCP_API_Commands::ZTN,
            ISCP_API_Commands::ZBL,
            ISCP_API_Commands::SLZ,
            ISCP_API_Commands::TUZ,
            ISCP_API_Commands::PRZ,
            ISCP_API_Commands::NTZ,
            ISCP_API_Commands::NPZ
        ),
        ONKYO_Zone::Zone3 => array(
            ISCP_API_Commands::PW3,
            ISCP_API_Commands::MT3,
            ISCP_API_Commands::VL3,
            ISCP_API_Commands::SL3,
            ISCP_API_Commands::TU3,
            ISCP_API_Commands::PR3,
            ISCP_API_Commands::NT3,
            ISCP_API_Commands::NP3,
            ISCP_API_Commands::BL3,
            ISCP_API_Commands::TN3
        ),
        ONKYO_Zone::Zone4 => array(
            ISCP_API_Commands::PW4,
            ISCP_API_Commands::MT4,
            ISCP_API_Commands::VL4,
            ISCP_API_Commands::SL4,
            ISCP_API_Commands::TU4,
            ISCP_API_Commands::PR4,
            ISCP_API_Commands::NT4,
            ISCP_API_Commands::NP4
        )
    );

    public function CmdAvaiable(ISCP_API_Data $API_Data)
    {
        return (in_array($API_Data->APICommand, self::$ZoneCMDs[$this->thisZone]));
    }

    public function SubCmdAvaiable(ISCP_API_Data $API_Data)
    {

//        IPS_LogMessage('APISubCommand', print_r($API_Data->APISubCommand, 1));
//        IPS_LogMessage('ZoneCMDs', print_r(self::$ZoneCMDs[$this->thisZone], 1));
        if ($API_Data->APISubCommand <> null)
            if (property_exists($API_Data->APISubCommand, $this->thisZone))
                return (in_array($API_Data->APISubCommand->{$this->thisZone}, self::$ZoneCMDs[$this->thisZone]));
        return false;
    }

}

class ISCP_API_Commands extends stdClass
{

//MAIN Zone
    const PWR = "PWR"; // Power
    const AMT = "AMT"; // Mute
    const SPA = "SPA"; //"SPA"/"SPB" - Speaker A/B Command
    const SPB = "SPB"; //"SPA"/"SPB" - Speaker A/B Command
    const SPL = "SPL"; //"SPL" - Speaker Layout Command
    const MVL = "MVL"; //"MVL" - Master Volume Command 
    const TFR = "TFR"; //"TFR" - Tone(Front) Command
    const TFW = "TFW"; //"TFW" - Tone(Front Wide) Command
    const TFH = "TFH"; //"TFH" - Tone(Front High) Command
    const TCT = "TCT"; //"TCT" - Tone(Center) Command
    const TSR = "TSR"; //"TSR" - Tone(Surround) Command
    const TSB = "TSB"; //"TSB" - Tone(Surround Back) Command
    const TSW = "TSW"; //"TSW" - Tone(Subwoofer) Command
    const PMB = "PMB"; //"PMB" - Phase Matching Bass Command
    const SLP = "SLP"; //"SLP" - Sleep Set Command 
    const SLC = "SLC"; //"SLC" - Speaker Level Calibration Command 
    const SWL = "SWL"; //"SWL" - Subwoofer (temporary) Level Command 
    const SW2 = "SW2"; //"SW2" - Subwoofer 2 (temporary) Level Command 
    const CTL = "CTL"; //"CTL" - Center (temporary) Level Command 
    const DIF = "DIF"; //"DIF" - Display Mode Command 
    const DIM = "DIM"; //"DIM" - Dimmer Level Command 
    const OSD = "OSD"; //"OSD" - Setup Operation Command 
    const MEM = "MEM"; //"MEM" - Memory Setup Command 
    const IFA = "IFA"; //"IFA" - Audio Infomation Command
    const IFV = "IFV"; //"IFV" - Video Infomation Command
    const SLI = "SLI"; // "SLI" - Input Selector Command 
//    const SLR = "SLR";
    const SLA = "SLA"; //"SLA" - Audio Selector Command 
    const TGA = "TGA"; //"TGA" - 12V Trigger A Command 
    const TGB = "TGB"; //"TGB" - 12V Trigger B Command 
    const TGC = "TGC"; //"TGC" - 12V Trigger C Command 
//    const VOS = "VOS";
    const HDO = "HDO"; //"HDO" - HDMI Output Selector
    const HAO = "HAO"; //"HAO" -HDMI Audio Out (Main)
    const HAS = "HAS"; //"HAS" -HDMI Audio Out (Sub)
    const CEC = "CEC"; //"CEC" - HDMI CEC
    const RES = "RES"; //"RES" - Monitor Out Resolution
//    const ISF = "ISF";
    const VWM = "VWM"; //"VWM" - Video Wide Mode
    const VPM = "VPM"; //"VPM" -Video Picture Mode
    const LMD = "LMD"; //"LMD" - Listening Mode Command
    const LTN = "LTN"; //"LTN" - Late Night Command 
    const RAS = "RAS"; //"RAS" - Re-EQ Command 
    const ADY = "ADY"; //"ADY" - Audyssey 2EQ/MultEQ/MultEQ XT
    const ADQ = "ADQ"; //"ADQ" - Audyssey Dynamic EQ
    const ADV = "ADV"; //"ADV" - Audyssey Dynamic Volume
    const DVL = "DVL"; //"DVL" - Dolby Volume
    const MOT = "MOT"; //"MOT" - Music Optimizer
    const AVS = "AVS"; //"AVS" - A/V Sync
    const ECO = "ECO"; //"ECO" - for Smart Grid Command 
    const TUN = "TUN"; //"TUN" - Tuning Command (Include Tuner Pack Model Only)
    const PRS = "PRS"; //"PRS" - Preset Command (Include Tuner Pack Model Only)
    const PRM = "PRM"; //"PRM" - Preset Memory Command (Include Tuner Pack Model Only)
    const RDS = "RDS"; //"RDS" - RDS Information Command (RDS Model Only)
    const PTS = "PTS"; //"PTS" - PTY Scan Command (RDS Model Only)
    const TPS = "TPS"; //"TPS" - TP Scan Command (RDS Model Only)
    const NTC = "NTC"; //"NTC" - Network/USB Operation Command (Network Model Only after TX-NR905)
    const NAT = "NAT"; //NET/USB Artist Name Info
    const NAL = "NAL"; //NET/USB Album Name Info
    const NTI = "NTI"; // NET/USB Title Name
    const NTM = "NTM"; // NET/USB Time Info
    const NTR = "NTR"; // NET/USB Track Info
    const NST = "NST"; // NET/USB Play Status
    const NMS = "NMS"; // NET/USB Menu Status
    const NTS = "NTS"; // "NTS" - NET/USB Time Seek
    const NPR = "NPR"; //"NPR" - Internet Radio Preset Command
    const NDS = "NDS"; // NET Connection/USB Device Status
    const NLS = "NLS"; // NET/USB List Info
    const NLA = "NLA"; // NET/USB List Info(All item, need processing XML data, for Network Control Only)
    const NJA = "NJA"; // NET/USB Jacket Art (When Jacket Art is available and Output for Network Control Only)
    const NSV = "NSV"; // NET Service(for Network Control Only)
    const NKY = "NKY"; // NET Keyboard(for Network Control Only)
    const NPU = "NPU"; // NET Popup Message(for Network Control Only)
    const NLT = "NLT"; // NET/USB List Title Info(for Network Control Only)
    const NMD = "NMD"; // iPod Mode Change (with USB Connection Only)
    const NSB = "NSB"; // Network Standby Settings (for Network Control Only and Available in AVR is PowerOn)
    const NRI = "NRI"; // Receiver Information (for Network Control Only)
# HD Radio
    /*
      const HAT = "HAT";
      const HCN = "HCN";
      const HTI = "HTI";
      const HDS = "HDS";
      const HPR = "HPR";
      const HBL = "HBL";
      const HTS = "HTS";
     */
//Zone2 Zone

    const ZPW = "ZPW";
    const ZMT = "ZMT";
    const ZVL = "ZVL";
    const ZTN = "ZTN";
    const ZBL = "ZBL";
    const SLZ = "SLZ";
    const TUZ = "TUZ";
    const PRZ = "PRZ";
    const NTZ = "NTZ";
    const NPZ = "NPZ";
    const LMZ = "LMZ";
    const LTZ = "LTZ";
    const RAZ = "RAZ";
    /*
      const LMZ ="LMZ";
      const LTZ="LTZ";
      const RAZ="RAZ";
     */
//Zone3 Zone
    const PW3 = "PW3";  // Power
    const MT3 = "MT3";  // Mute
    const VL3 = "VL3";  // Volume
    const SL3 = "SL3";  // Selector
    const TU3 = "TU3";  // Tune
    const PR3 = "PR3";  // Preset
    const NT3 = "NT3";  // Net-Tune
    const NP3 = "NP3";  // Net-Preset
    const BL3 = "BL3";  // Balance
    const TN3 = "TN3";  // Tone
//Zone4 Zone
    const PW4 = "PW4";  // Power
    const MT4 = "MT4";  // Mute
    const VL4 = "VL4";  // Volume
    const SL4 = "SL4";  // Selector
    const TU4 = "TU4";  // Tune
    const PR4 = "PR4";  // Preset
    const NT4 = "NT4";  // Net-Tune
    const NP4 = "NP4";  // Net-Preset
    const Request = "QSTN";

    static $BoolValueMapping = array(
        FALSE => '00',
        TRUE => '01',
        '00' => FALSE,
        '01' => TRUE
    );

    const IsVariable = 0;
    const VarType = 1;
    const VarName = 2;
    const Profile = 3;
    const EnableAction = 4;
    const RequestValue = 5;
    const ValueMapping = 6;

//    const ValuePrefix = 7;
//    const ValueStepSize = 8;

    static $CMDMapping = array(
        ISCP_API_Commands::TUN => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::TUZ
        ),
        ISCP_API_Commands::PRS => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::PRZ
        ),
        ISCP_API_Commands::NTC => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::NTZ
        ),
        ISCP_API_Commands::NPR => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::NPZ
        ),
        ISCP_API_Commands::LMD => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::LMZ
        ),
        ISCP_API_Commands::LTN => array(
            ONKYO_Zone::Zone2 => ISCP_API_Commands::LTZ
        ),
        ISCP_API_Commands::RAS => array(
            ISCP_API_Commands::RAZ
        )
    );
    // Nur für alle CMDs, welche keine SubCommands sind.
    static $VarMapping = array(
        ISCP_API_Commands::PWR
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => 'Power',
            self::RequestValue => true
        ),
        ISCP_API_Commands::AMT
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => 'Mute',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SPA
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => 'Speaker A',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SPB
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => 'Speaker B',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SPL // TODO
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSpeakerLayout,
            self::IsVariable => true,
            self::VarName => 'Speaker Layout',
            self::RequestValue => true,
            self::ValueMapping => array("SB" => 1, "FH" => 2, "FW" => 3, "HW" => 4, 1 => "SB", 2 => "FH", 3 => "FW", 4 => "HW")
        ),
        ISCP_API_Commands::MVL
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptVolume,
            self::IsVariable => true,
            self::VarName => 'Volume',
            self::RequestValue => true
        ),
        ISCP_API_Commands::PMB
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => 'Phase Matching Bass',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SLP
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => self::ptSleep,
            self::IsVariable => true,
            self::VarName => 'Sleep Set',
            self::RequestValue => true,
            self::ValueMapping=>array("OFF"=> 0, 0 => "OFF")
        ),
        ISCP_API_Commands::DIF
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptDisplayMode,
            self::IsVariable => true,
            self::VarName => 'Display Mode',
            self::RequestValue => true
        ),
        ISCP_API_Commands::DIM
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptDisplayDimmer,
            self::IsVariable => true,
            self::VarName => 'Display Dimmer',
            self::RequestValue => true
        ),
        ISCP_API_Commands::IFA
        => array(
            self::VarType => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile => "",
            self::IsVariable => true,
            self::VarName => 'Audio Information',
            self::RequestValue => true
        ),
        ISCP_API_Commands::IFV
        => array(
            self::VarType => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile => "",
            self::IsVariable => true,
            self::VarName => 'Video Information',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SLI
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSelectInput,
            self::IsVariable => true,
            self::VarName => 'Input Selector',
            self::RequestValue => true
        ),
        ISCP_API_Commands::SLA
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSelectInputAudio,
            self::IsVariable => true,
            self::VarName => 'Audio Input Selector'
        ),
        ISCP_API_Commands::TGA
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => '12V Trigger A',
            self::RequestValue => true
        ),
        ISCP_API_Commands::TGB
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => '12V Trigger B',
            self::RequestValue => true
        ),
        ISCP_API_Commands::TGC
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSwitch,
            self::IsVariable => true,
            self::VarName => '12V Trigger C',
            self::RequestValue => true
        ),
        // MORE TODO HDO -> VPM
        ISCP_API_Commands::LMD
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptListeningMode,
            self::IsVariable => true,
            self::VarName => 'Listening Mode'
        ),
        // MORE TODO LTN -> ECO
        ISCP_API_Commands::TUN
        => array(
            self::VarType => IPSVarType::vtFloat,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptTunerFrequenz,
            self::IsVariable => true,
            self::VarName => 'Tuner Frequenz'
        ),
        ISCP_API_Commands::PRS
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptRadioPreset,
            self::IsVariable => true,
            self::VarName => 'Radio Preset'
        ),
        // MORE TODO Network all
        ISCP_API_Commands::NTC
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptNetTuneCommand,
            self::IsVariable => false
        ),
        ISCP_API_Commands::NPR
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptNetRadioPreset,
            self::IsVariable => true,
            self::VarName => 'Network Radio Preset'
        )
            /*
              ISCP_API_Commands::TFR,
              ISCP_API_Commands::TFW,
              ISCP_API_Commands::TFH,
              ISCP_API_Commands::TCT,
              ISCP_API_Commands::TSR,
              ISCP_API_Commands::TSB,
              ISCP_API_Commands::TSW */
    );

    /*
      static function GetMapping(ISCP_API_Data $APIData)
      {
      if (array_key_exists($APIData->APICommand, self::$VarMapping))
      {
      //$APIData->APICommand            in_array(, self::$VarMapping);
      }
      else
      return false;
      }
     */
}

class ISCP_API_Command_Mapping extends stdClass
{

    static public function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$CMDMapping))
        {
//            IPS_LogMessage('GetMapping', print_r(ISCP_API_Commands::$CMDMapping[$Cmd], 1));
            return ISCP_API_Commands::$CMDMapping[$Cmd];
            /*
              $this->VarType = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarType];
              $this->EnableAction = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::EnableAction];
              $this->Profile = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::Profile];
             */
        }
        else
            return null;
    }

}

class ISCP_API_Data_Mapping extends stdClass
{

//    public $VarType;
//    public $EnableAction;
//    public $Profile;

    static public function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$VarMapping))
        {
            $result = new stdClass;
            $result->IsVariable = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::IsVariable];
            $result->VarType = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarType];
            $result->VarName = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarName];
            $result->Profile = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::Profile];
            $result->EnableAction = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::EnableAction];
//            if (array_key_exists(ISCP_API_Commands::APIMainCommand, ISCP_API_Commands::$VarMapping[$Cmd]))
//                $result->APIMainCommand = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::APIMainCommand];

            return $result;
            /*
              $this->VarType = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarType];
              $this->EnableAction = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::EnableAction];
              $this->Profile = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::Profile];
             */
        }
        else
            return null;
    }

}

class ISCP_API_Data extends stdClass
{

    public $APICommand;
    public $Data;
    public $Mapping;
    public $APISubCommand;

    public function GetDataFromJSONObject($Data)
    {
        $this->APICommand = $Data->APICommand;
        $this->Data = utf8_decode($Data->Data);
        if (property_exists($Data, 'APISubCommand'))
            $this->APISubCommand = $Data->APISubCommand;
    }

    public function ToJSONString($GUID)
    {
        $SendData = new stdClass;
        $SendData->DataID = $GUID;
        $SendData->APICommand = $this->APICommand;
        $SendData->Data = utf8_encode($this->Data);
        if (is_array($this->APISubCommand))
            $SendData->APISubCommand = $this->APISubCommand;
        return json_encode($SendData);
    }

    public function GetMapping()
    {
        $this->Mapping = ISCP_API_Data_Mapping::GetMapping($this->APICommand);
    }

    public function GetSubCommand()
    {
//        IPS_LogMessage('GetSubCommand', print_r(ISCP_API_Command_Mapping::GetMapping($this->APICommand), 1));
        $this->APISubCommand = ISCP_API_Command_Mapping::GetMapping($this->APICommand);
    }

}

/*
  class TXB_Node extends stdClass
  {

  public $NodeAddr64;
  public $NodeAddr16;
  public $NodeName;

  public function utf8_encode()
  {
  $this->NodeAddr16 = utf8_encode($this->NodeAddr16);
  $this->NodeAddr64 = utf8_encode($this->NodeAddr64);
  $this->NodeName = utf8_encode($this->NodeName);
  }

  public function utf8_decode()
  {
  $this->NodeAddr16 = utf8_decode($this->NodeAddr16);
  $this->NodeAddr64 = utf8_decode($this->NodeAddr64);
  $this->NodeName = utf8_decode($this->NodeName);
  }

  }

  class TXB_NodeFromGeneric extends TXB_Node
  {

  public function __construct($object)
  {
  $this->NodeAddr16 = $object->NodeAddr16;
  $this->NodeAddr64 = $object->NodeAddr64;
  $this->NodeName = $object->NodeName;
  }
  }
 */
?>