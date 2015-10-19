<?

//  API Datentypen
class ONKYO_Zone extends stdClass
{

    const ZoneMain = 1;
    const Zone2 = 2;
    const Zone3 = 3;
    const Zone4 = 4;

    public $Zone;

}

class ISCP_API_Command extends stdClass
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

    private $ZoneCMDs = array(
        ONKYO_Zone::ZoneMain => array(
            self::PWR,
            self::AMT,
            self::MVL,
            self::SLI,
            self::TUN,
            self::PRS,
            self::NTC,
            self::NPR,
            self::TFR,
            self::TFW,
            self::TFH,
            self::TCT,
            self::TSR,
            self::TSB,
            self::TSW
        ),
        ONKYO_Zone::Zone2 => array(
            self::ZPW,
            self::ZMT,
            self::ZVL,
            self::ZTN,
            self::ZBL,
            self::SLZ,
            self::TUZ,
            self::PRZ,
            self::NTZ,
            self::NPZ),
        ONKYO_Zone::Zone3 => array(
            self::PW3,
            self::MT3,
            self::VL3,
            self::SL3,
            self::TU3,
            self::PR3,
            self::NT3,
            self::NP3,
            self::BL3,
            self::TN3
        ),
        ONKYO_Zone::Zone4 => array(
            self::PW4,
            self::MT4,
            self::VL4,
            self::SL4,
            self::TU4,
            self::PR4,
            self::NT4,
            self::NP4
        )
    );
    public $APICommand;
    public $Data;

    public function GetDataFromJSONObject($Data)
    {
        $this->APICommand = utf8_decode($Data->APICommand);
        $this->Data = utf8_decode($Data->Data);
    }

    public function ToJSONString($GUID)
    {
        $SendData = new stdClass;
        $SendData->DataID = $GUID;
        $SendData->APICommand = utf8_encode($this->APICommand);
        $SendData->Data = utf8_encode($this->Data);
        return json_encode($SendData);
        
    }

    public function CmdAvaiable(ONKYO_Zone $Zone)
    {
        return (in_array($Zone->Zone, $this->ZoneCMDs));
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