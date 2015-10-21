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
    const ptMute = 'Onkyo.Mute';
    const ptSLI = 'Onkyo.SLI';
    const ptLMD = 'Onkyo.LMD';
    const ptVolume = '~Intensity.100';
    const ptNetRadioPreset = 'Onkyo.NetRadioPreset';
    const ptRadioPreset = 'Onkyo.RadioPreset';
    const ptVideoResolution = 'Onkyo.VideoResolution';
    const ptSLA = 'Onkyo.SLA';
    const ptVWM = 'Onkyo.VWM';
    const ptTunerFrequenz = 'Onkyo.TunerFrequenz';
    const ptNetTuneCommand = 'Onkyo.NetTuneCommand';

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
            ISCP_API_Commands::MVL,
            ISCP_API_Commands::SLI,
            ISCP_API_Commands::TUN,
            ISCP_API_Commands::PRS,
            ISCP_API_Commands::NTC,
            ISCP_API_Commands::NPR,
            ISCP_API_Commands::TFR,
            ISCP_API_Commands::TFW,
            ISCP_API_Commands::TFH,
            ISCP_API_Commands::TCT,
            ISCP_API_Commands::TSR,
            ISCP_API_Commands::TSB,
            ISCP_API_Commands::TSW
        ),
        ONKYO_Zone::Zone2 => array(
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
        /*        foreach ($API_Data->APISubCommand as $APISubCommand)
          {
          if (in_array($APISubCommand, self::$ZoneCMDs[$this->thisZone]))
          return true;
          }
          return false; */
        return (in_array($API_Data->APISubCommand[$this->thisZone], self::$ZoneCMDs[$this->thisZone]));
    }

}

class ISCP_API_Commands extends stdClass
{

//MAIN Zone
    const PWR = "PWR";  // Power
    const AMT = "AMT";  // Mute
    const MVL = "MVL";  // Volume
    const SLI = "SLI";  // Selector
    const TUN = "TUN";  // Tune
    const PRS = "PRS";  // Preset
    const NTC = "NTC";  // Net-Tune
    const NPR = "NPR";  // Net-Preset
    const TFR = "TFR";
    const TFW = "TFW";
    const TFH = "TFH";
    const TCT = "TCT";
    const TSR = "TSR";
    const TSB = "TSB";
    const TSW = "TSW";
    //
    const SPA = "SPA";
    const SPB = "SPB";
    const SPL = "SPL";
    const PMB = "PMB";
    const SLP = "SLP";
    const SLC = "SLC";
    const SWL = "SWL";
    const SW2 = "SW2";
    const CTL = "CTL";
    const DIF = "DIF";
    const DIM = "DIM";
    const OSD = "OSD";
    const MEM = "MEM";
    const IFA = "IFA";
    const IFV = "IFV";
    const SLR = "SLR";
    const SLA = "SLA";
    const TGA = "TGA";
    const TGB = "TGB";
    const TGC = "TGC";
    const VOS = "VOS";
    const HDO = "HDO";
    const HAO = "HAO";
    const HAS = "HAS";
    const CEC = "CEC";
    const RES = "RES";
    const ISF = "ISF";
    const VWM = "VWM";
    const VPM = "VPM";
    const LMD = "LMD";
    const LTN = "LTN";
    const RAS = "RAS";
    const ADY = "ADY";
    const ADQ = "ADQ";
    const ADV = "ADV";
//const DVL = "DVL";
    const MOT = "MOT";
    const AVS = "AVS";
    const ECO = "ECO";

# Tuner
    const PRM = "PRM";
    const RDS = "RDS";
    const PTS = "PTS";
    const TPS = "TPS";

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
            self::IsVariable => true
        ),
        ISCP_API_Commands::AMT
        => array(
            self::VarType => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptMute,
            self::IsVariable => true
        ),
        ISCP_API_Commands::MVL
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptVolume,
            self::IsVariable => true
        ),
        ISCP_API_Commands::SLI
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptSLI,
            self::IsVariable => true
        ),
        ISCP_API_Commands::TUN
        => array(
            self::VarType => IPSVarType::vtFloat,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptTunerFrequenz,
            self::IsVariable => true
        ),
        ISCP_API_Commands::PRS
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptRadioPreset,
            self::IsVariable => true
        ),
        ISCP_API_Commands::NTC
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptNetTuneCommand,
            self::IsVariable => true
        ),
        ISCP_API_Commands::NPR
        => array(
            self::VarType => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile => IPSProfiles::ptNetRadioPreset,
            self::IsVariable => true
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

    static function GetMapping(ISCP_API_Data $APIData)
    {
        if (array_key_exists($APIData->APICommand, self::$VarMapping))
        {
//$APIData->APICommand            in_array(, self::$VarMapping);
        }
        else
            return false;
    }

}

class ISCP_API_Command_Mapping extends stdClass
{

    static public function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$CMDMapping))
        {
            return ISCP_API_Commands::$CMDMapping[$Cmd];
            /*
              $this->VarType = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::VarType];
              $this->EnableAction = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::EnableAction];
              $this->Profile = ISCP_API_Commands::$VarMapping[$Cmd][ISCP_API_Commands::Profile];
             */
        }
        else
            return false;
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
            return false;
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