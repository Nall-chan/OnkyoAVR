<?php

/**
 * @addtogroup onkyoavr
 * @{
 *
 * @package       OnkyoAVR
 * @file          OnkyoAVRClass.php
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2018 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 * @version       0.4
 */
if (!defined("IPS_BASE")) {
    // --- BASE MESSAGE
    define('IPS_BASE', 10000);                             //Base Message
    define('IPS_KERNELSTARTED', IPS_BASE + 1);             //Post Ready Message
    define('IPS_KERNELSHUTDOWN', IPS_BASE + 2);            //Pre Shutdown Message, Runlevel UNINIT Follows
}
if (!defined("IPS_KERNELMESSAGE")) {
    // --- KERNEL
    define('IPS_KERNELMESSAGE', IPS_BASE + 100);           //Kernel Message
    define('KR_CREATE', IPS_KERNELMESSAGE + 1);            //Kernel is beeing created
    define('KR_INIT', IPS_KERNELMESSAGE + 2);              //Kernel Components are beeing initialised, Modules loaded, Settings read
    define('KR_READY', IPS_KERNELMESSAGE + 3);             //Kernel is ready and running
    define('KR_UNINIT', IPS_KERNELMESSAGE + 4);            //Got Shutdown Message, unloading all stuff
    define('KR_SHUTDOWN', IPS_KERNELMESSAGE + 5);          //Uninit Complete, Destroying Kernel Inteface
}
if (!defined("IPS_LOGMESSAGE")) {
    // --- KERNEL LOGMESSAGE
    define('IPS_LOGMESSAGE', IPS_BASE + 200);              //Logmessage Message
    define('KL_MESSAGE', IPS_LOGMESSAGE + 1);              //Normal Message                      | FG: Black | BG: White  | STLYE : NONE
    define('KL_SUCCESS', IPS_LOGMESSAGE + 2);              //Success Message                     | FG: Black | BG: Green  | STYLE : NONE
    define('KL_NOTIFY', IPS_LOGMESSAGE + 3);               //Notiy about Changes                 | FG: Black | BG: Blue   | STLYE : NONE
    define('KL_WARNING', IPS_LOGMESSAGE + 4);              //Warnings                            | FG: Black | BG: Yellow | STLYE : NONE
    define('KL_ERROR', IPS_LOGMESSAGE + 5);                //Error Message                       | FG: Black | BG: Red    | STLYE : BOLD
    define('KL_DEBUG', IPS_LOGMESSAGE + 6);                //Debug Informations + Script Results | FG: Grey  | BG: White  | STLYE : NONE
    define('KL_CUSTOM', IPS_LOGMESSAGE + 7);               //User Message                        | FG: Black | BG: White  | STLYE : NONE
}
if (!defined("IPS_MODULEMESSAGE")) {
    // --- MODULE LOADER
    define('IPS_MODULEMESSAGE', IPS_BASE + 300);           //ModuleLoader Message
    define('ML_LOAD', IPS_MODULEMESSAGE + 1);              //Module loaded
    define('ML_UNLOAD', IPS_MODULEMESSAGE + 2);            //Module unloaded
}
if (!defined("IPS_OBJECTMESSAGE")) {
    // --- OBJECT MANAGER
    define('IPS_OBJECTMESSAGE', IPS_BASE + 400);
    define('OM_REGISTER', IPS_OBJECTMESSAGE + 1);          //Object was registered
    define('OM_UNREGISTER', IPS_OBJECTMESSAGE + 2);        //Object was unregistered
    define('OM_CHANGEPARENT', IPS_OBJECTMESSAGE + 3);      //Parent was Changed
    define('OM_CHANGENAME', IPS_OBJECTMESSAGE + 4);        //Name was Changed
    define('OM_CHANGEINFO', IPS_OBJECTMESSAGE + 5);        //Info was Changed
    define('OM_CHANGETYPE', IPS_OBJECTMESSAGE + 6);        //Type was Changed
    define('OM_CHANGESUMMARY', IPS_OBJECTMESSAGE + 7);     //Summary was Changed
    define('OM_CHANGEPOSITION', IPS_OBJECTMESSAGE + 8);    //Position was Changed
    define('OM_CHANGEREADONLY', IPS_OBJECTMESSAGE + 9);    //ReadOnly was Changed
    define('OM_CHANGEHIDDEN', IPS_OBJECTMESSAGE + 10);     //Hidden was Changed
    define('OM_CHANGEICON', IPS_OBJECTMESSAGE + 11);       //Icon was Changed
    define('OM_CHILDADDED', IPS_OBJECTMESSAGE + 12);       //Child for Object was added
    define('OM_CHILDREMOVED', IPS_OBJECTMESSAGE + 13);     //Child for Object was removed
    define('OM_CHANGEIDENT', IPS_OBJECTMESSAGE + 14);      //Ident was Changed
}
if (!defined("IPS_INSTANCEMESSAGE")) {
    // --- INSTANCE MANAGER
    define('IPS_INSTANCEMESSAGE', IPS_BASE + 500);         //Instance Manager Message
    define('IM_CREATE', IPS_INSTANCEMESSAGE + 1);          //Instance created
    define('IM_DELETE', IPS_INSTANCEMESSAGE + 2);          //Instance deleted
    define('IM_CONNECT', IPS_INSTANCEMESSAGE + 3);         //Instance connectged
    define('IM_DISCONNECT', IPS_INSTANCEMESSAGE + 4);      //Instance disconncted
    define('IM_CHANGESTATUS', IPS_INSTANCEMESSAGE + 5);    //Status was Changed
    define('IM_CHANGESETTINGS', IPS_INSTANCEMESSAGE + 6);  //Settings were Changed
    define('IM_CHANGESEARCH', IPS_INSTANCEMESSAGE + 7);    //Searching was started/stopped
    define('IM_SEARCHUPDATE', IPS_INSTANCEMESSAGE + 8);    //Searching found new results
    define('IM_SEARCHPROGRESS', IPS_INSTANCEMESSAGE + 9);  //Searching progress in %
    define('IM_SEARCHCOMPLETE', IPS_INSTANCEMESSAGE + 10); //Searching is complete
}
if (!defined("IPS_VARIABLEMESSAGE")) {
    // --- VARIABLE MANAGER
    define('IPS_VARIABLEMESSAGE', IPS_BASE + 600);              //Variable Manager Message
    define('VM_CREATE', IPS_VARIABLEMESSAGE + 1);               //Variable Created
    define('VM_DELETE', IPS_VARIABLEMESSAGE + 2);               //Variable Deleted
    define('VM_UPDATE', IPS_VARIABLEMESSAGE + 3);               //On Variable Update
    define('VM_CHANGEPROFILENAME', IPS_VARIABLEMESSAGE + 4);    //On Profile Name Change
    define('VM_CHANGEPROFILEACTION', IPS_VARIABLEMESSAGE + 5);  //On Profile Action Change
}
if (!defined("IPS_SCRIPTMESSAGE")) {
    // --- SCRIPT MANAGER
    define('IPS_SCRIPTMESSAGE', IPS_BASE + 700);           //Script Manager Message
    define('SM_CREATE', IPS_SCRIPTMESSAGE + 1);            //On Script Create
    define('SM_DELETE', IPS_SCRIPTMESSAGE + 2);            //On Script Delete
    define('SM_CHANGEFILE', IPS_SCRIPTMESSAGE + 3);        //On Script File changed
    define('SM_BROKEN', IPS_SCRIPTMESSAGE + 4);            //Script Broken Status changed
}
if (!defined("IPS_EVENTMESSAGE")) {
    // --- EVENT MANAGER
    define('IPS_EVENTMESSAGE', IPS_BASE + 800);             //Event Scripter Message
    define('EM_CREATE', IPS_EVENTMESSAGE + 1);             //On Event Create
    define('EM_DELETE', IPS_EVENTMESSAGE + 2);             //On Event Delete
    define('EM_UPDATE', IPS_EVENTMESSAGE + 3);
    define('EM_CHANGEACTIVE', IPS_EVENTMESSAGE + 4);
    define('EM_CHANGELIMIT', IPS_EVENTMESSAGE + 5);
    define('EM_CHANGESCRIPT', IPS_EVENTMESSAGE + 6);
    define('EM_CHANGETRIGGER', IPS_EVENTMESSAGE + 7);
    define('EM_CHANGETRIGGERVALUE', IPS_EVENTMESSAGE + 8);
    define('EM_CHANGETRIGGEREXECUTION', IPS_EVENTMESSAGE + 9);
    define('EM_CHANGECYCLIC', IPS_EVENTMESSAGE + 10);
    define('EM_CHANGECYCLICDATEFROM', IPS_EVENTMESSAGE + 11);
    define('EM_CHANGECYCLICDATETO', IPS_EVENTMESSAGE + 12);
    define('EM_CHANGECYCLICTIMEFROM', IPS_EVENTMESSAGE + 13);
    define('EM_CHANGECYCLICTIMETO', IPS_EVENTMESSAGE + 14);
}
if (!defined("IPS_MEDIAMESSAGE")) {
    // --- MEDIA MANAGER
    define('IPS_MEDIAMESSAGE', IPS_BASE + 900);           //Media Manager Message
    define('MM_CREATE', IPS_MEDIAMESSAGE + 1);             //On Media Create
    define('MM_DELETE', IPS_MEDIAMESSAGE + 2);             //On Media Delete
    define('MM_CHANGEFILE', IPS_MEDIAMESSAGE + 3);         //On Media File changed
    define('MM_AVAILABLE', IPS_MEDIAMESSAGE + 4);          //Media Available Status changed
    define('MM_UPDATE', IPS_MEDIAMESSAGE + 5);
}
if (!defined("IPS_LINKMESSAGE")) {
    // --- LINK MANAGER
    define('IPS_LINKMESSAGE', IPS_BASE + 1000);           //Link Manager Message
    define('LM_CREATE', IPS_LINKMESSAGE + 1);             //On Link Create
    define('LM_DELETE', IPS_LINKMESSAGE + 2);             //On Link Delete
    define('LM_CHANGETARGET', IPS_LINKMESSAGE + 3);       //On Link TargetID change
}
if (!defined("IPS_FLOWMESSAGE")) {
    // --- DATA HANDLER
    define('IPS_FLOWMESSAGE', IPS_BASE + 1100);             //Data Handler Message
    define('FM_CONNECT', IPS_FLOWMESSAGE + 1);             //On Instance Connect
    define('FM_DISCONNECT', IPS_FLOWMESSAGE + 2);          //On Instance Disconnect
}
if (!defined("IPS_ENGINEMESSAGE")) {
    // --- SCRIPT ENGINE
    define('IPS_ENGINEMESSAGE', IPS_BASE + 1200);           //Script Engine Message
    define('SE_UPDATE', IPS_ENGINEMESSAGE + 1);             //On Library Refresh
    define('SE_EXECUTE', IPS_ENGINEMESSAGE + 2);            //On Script Finished execution
    define('SE_RUNNING', IPS_ENGINEMESSAGE + 3);            //On Script Started execution
}
if (!defined("IPS_PROFILEMESSAGE")) {
    // --- PROFILE POOL
    define('IPS_PROFILEMESSAGE', IPS_BASE + 1300);
    define('PM_CREATE', IPS_PROFILEMESSAGE + 1);
    define('PM_DELETE', IPS_PROFILEMESSAGE + 2);
    define('PM_CHANGETEXT', IPS_PROFILEMESSAGE + 3);
    define('PM_CHANGEVALUES', IPS_PROFILEMESSAGE + 4);
    define('PM_CHANGEDIGITS', IPS_PROFILEMESSAGE + 5);
    define('PM_CHANGEICON', IPS_PROFILEMESSAGE + 6);
    define('PM_ASSOCIATIONADDED', IPS_PROFILEMESSAGE + 7);
    define('PM_ASSOCIATIONREMOVED', IPS_PROFILEMESSAGE + 8);
    define('PM_ASSOCIATIONCHANGED', IPS_PROFILEMESSAGE + 9);
}
if (!defined("IPS_TIMERMESSAGE")) {
    // --- TIMER POOL
    define('IPS_TIMERMESSAGE', IPS_BASE + 1400);            //Timer Pool Message
    define('TM_REGISTER', IPS_TIMERMESSAGE + 1);
    define('TM_UNREGISTER', IPS_TIMERMESSAGE + 2);
    define('TM_SETINTERVAL', IPS_TIMERMESSAGE + 3);
    define('TM_UPDATE', IPS_TIMERMESSAGE + 4);
    define('TM_RUNNING', IPS_TIMERMESSAGE + 5);
}

if (!defined("IS_ACTIVE")) { //Nur wenn Konstanten noch nicht bekannt sind.
// --- STATUS CODES
    define('IS_SBASE', 100);
    define('IS_CREATING', IS_SBASE + 1); //module is being created
    define('IS_ACTIVE', IS_SBASE + 2); //module created and running
    define('IS_DELETING', IS_SBASE + 3); //module us being deleted
    define('IS_INACTIVE', IS_SBASE + 4); //module is not beeing used
// --- ERROR CODES
    define('IS_EBASE', 200);          //default errorcode
    define('IS_NOTCREATED', IS_EBASE + 1); //instance could not be created
}

if (!defined("vtBoolean")) { //Nur wenn Konstanten noch nicht bekannt sind.
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
}

//  API Datentypen
class IPSVarType extends stdClass
{
    const vtNone = -1;
    const vtBoolean = 0;
    const vtInteger = 1;
    const vtFloat = 2;
    const vtString = 3;
    const vtDualInteger = 10;

}

class IPSProfiles extends stdClass
{
    const ptSwitch = '~Switch';
    const ptSpeakerLayout = 'SpeakerLayout.Onkyo';
    const ptVolume = '~Intensity.100';
    const ptToneOffset = 'ToneOffset.Onkyo';
    const ptSleep = 'Sleep.Onkyo';
    // SW & SW2 & CTL
    const ptDisplayMode = 'DisplayMode.Onkyo';
    const ptDisplayDimmer = 'DisplayDimmer.Onkyo';
    const ptSelectInput = 'SelectInput.Onkyo';
    const ptSelectInputAudio = 'SelectInputAudio.Onkyo';
    const ptHDMIOutput = 'HDMIOutput.Onkyo';
    const ptHDMIAudioOutput = 'HDMIAudioOutput.Onkyo';
    const ptVideoResolution = 'VideoResolution.Onkyo';
    const ptVideoWideMode = 'VideoWideMode.Onkyo';
    const ptVideoPictureMode = 'VideoPictureMode.Onkyo';
    const ptListeningMode = 'LMD.Onkyo';
    const ptLateNight = 'LateNight.Onkyo';
    const ptAudyssey = 'Audyssey.Onkyo';
    const ptAudysseyDynamic = 'AudysseyDynamic.Onkyo';
    const ptDolbyVolume = 'DolbyVolume.Onkyo';
//    const ptTunerFrequenz = 'TunerFrequenz.Onkyo';
    const ptRadioPreset = 'RadioPreset.Onkyo';
    //Main end
    const ptNetRadioPreset = 'NetRadioPreset.Onkyo';
    const ptNetTuneCommand = 'NetTuneCommand.Onkyo';

    public static $ProfilInteger = array(
        self::ptToneOffset     => array(-10, 10, 2),
        self::ptSleep          => array(0x00, 0x5A, 1),
        self::ptNetRadioPreset => array(0x01, 0x30, 1),
        self::ptRadioPreset    => array(0x01, 0x30, 1)
    );
    public static $ProfilAssociations = array(
//      self::ptMute=> array(),
        self::ptSpeakerLayout    => array(
            array(0x01, "Surround Back", "", -1),
            array(0x02, "Front High", "", -1),
            array(0x03, "Front Wide", "", -1),
            array(0x04, "Front High & Front Wide", "", -1)
        ),
        self::ptDisplayMode      => array(
            array(0x00, "Selector & Volume", "", -1),
            array(0x01, "Selector & Listening Mode", "", -1),
            array(0x02, "Digital Format", "", -1),
            array(0x03, "Video Format", "", -1)
        ),
        self::ptDisplayDimmer    => array(
            array(0x00, "Bright", "", -1),
            array(0x01, "Dim", "", -1),
            array(0x02, "Dark", "", -1),
            array(0x03, "Off", "", -1),
            array(0x08, "Bright & LED Off", "", -1)
        ),
        self::ptSelectInput      => array(
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
            array(0x24, "Tuner (FM)", "", -1),
            array(0x25, "Tuner (AM)", "", -1),
//            array(0x26, "TUNER", "", -1), // not z
//"MUSIC SERVER DLNA",
//"INTERNET RADIO",
            array(0x29, "USB(Front)", "", -1),
//"USB(Rear)", // not z
            array(0x2B, "NETWORK", "", -1),
//"USB(toggle)", //lol
            array(0x2D, "Aiplay", "", -1), //?
            array(0x2E, "Bluetooth", "", -1), //?
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
        self::ptHDMIOutput       => array(
            array(0x00, "OFF (Analog)", "", -1),
            array(0x01, "Main Out", "", -1),
            array(0x02, "Sub Out", "", -1),
            array(0x03, "Both", "", -1),
            array(0x04, "Both (Main)", "", -1),
            array(0x05, "Both (Sub)", "", -1)
        ),
        self::ptHDMIAudioOutput  => array(
            array(0x00, "Off", "", -1),
            array(0x01, "On", "", -1),
            array(0x02, "Auto", "", -1)
        ),
        self::ptVideoResolution  => array(
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
        self::ptVideoWideMode    => array(
            array(0x00, "Auto", "", -1),
            array(0x01, "4:3", "", -1),
            array(0x02, "Full", "", -1),
            array(0x03, "Zoom", "", -1),
            array(0x04, "Wide Zoom", "", -1),
            array(0x05, "Smart Zoom", "", -1)
        ),
        self::ptVideoPictureMode => array(
            array(0x00, "Through", "", -1),
            array(0x01, "Custom", "", -1),
            array(0x02, "Cinema", "", -1),
            array(0x03, "Game", "", -1),
            array(0x05, "ISF Day", "", -1),
            array(0x06, "ISF Night", "", -1),
            array(0x07, "Streaming", "", -1),
            array(0x08, "Direct (Bypass)", "", -1)
        ),
        self::ptListeningMode    => array(
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
        ),
        self::ptLateNight        => array(
            array(0x00, "Off", "", -1),
            array(0x01, "Low", "", -1),
            array(0x02, "High", "", -1),
            array(0x03, "Auto", "", -1)
        ),
        self::ptAudyssey         => array(
            array(0x00, "Off", "", -1),
            array(0x01, "On (Movie)", "", -1),
            array(0x02, "On (Music)", "", -1)
        ),
        self::ptAudysseyDynamic  => array(
            array(0x00, "Off", "", -1),
            array(0x01, "Light", "", -1),
            array(0x02, "Medium", "", -1),
            array(0x03, "Heavy", "", -1)
        ),
        self::ptDolbyVolume      => array(
            array(0x00, "Off", "", -1),
            array(0x01, "Low", "", -1),
            array(0x02, "Medium", "", -1),
            array(0x03, "High", "", -1)
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
    public static $ZoneCMDs = array(
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
            //Start NET/USB
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
            ISCP_API_Commands::NRI,
            //ENDE Net/USB
//Start Airplay
            ISCP_API_Commands::AAT,
            ISCP_API_Commands::AAL,
            ISCP_API_Commands::ATI,
            ISCP_API_Commands::ATM,
            ISCP_API_Commands::AST,
            //Ende Airplay
//START CMD via RI
            ISCP_API_Commands::CDS,
            ISCP_API_Commands::CCD,
            ISCP_API_Commands::CT1,
            ISCP_API_Commands::CT2,
            ISCP_API_Commands::CEQ,
            ISCP_API_Commands::CDT,
            ISCP_API_Commands::CMD,
            ISCP_API_Commands::CCR,
            //ENDE CMD via RI
//START CMD via PORT
            ISCP_API_Commands::CPT,
//"IAT" - iPod Artist Name Info (Universal Port Dock Only)
//"IAL" - iPod Album Name Info (Universal Port Dock Only)
//"ITI" - iPod Title Name (Universal Port Dock Only)
//"ITM" - iPod Time Info (Universal Port Dock Only)
//"ITR" - iPod Track Info (Universal Port Dock Only)
//"IST" - iPod Play Status (Universal Port Dock Only)
//"ILS" - iPod List Info (Universal Port Dock Extend Mode Only)
//"IMD" - iPod Mode Change (Universal Port Dock Only)
//"UTN" - Tuning Command (Universal Port Dock Only)
//"UPR" - Preset Command (Universal Port Dock Only)
//"UPM" - Preset Memory Command (Universal Port Dock Only)
//"UHP" - HD Radio Channel Program Command (Universal Port Dock Only)
//"UHB" - HD Radio Blend Mode Command (Universal Port Dock Only)
//"UHA" - HD Radio Artist Name Info (Universal Port Dock Only)
//"UHC" - HD Radio Channel Name Info (Universal Port Dock Only)
//"UHT" - HD Radio Title Info (Universal Port Dock Only)
//"UHD" - HD Radio Detail Info (Universal Port Dock Only)
//"UHS" - HD Radio Tuner Status (Universal Port Dock Only)
//"UPR" - DAB Preset Command (Universal Port Dock Only)
//"UPM" - Preset Memory Command (Universal Port Dock Only)
//"UDS" - DAB Sation Name (Universal Port Dock Only)
//"UDD" - DAB Display Info (Universal Port Dock Only)
//ENDE CMD via PORT
//START CMD BD via RIHD
            ISCP_API_Commands::CDV,
//ENDE CMD BD via RIHD
//START CMD TV via RIHD
            ISCP_API_Commands::CTV
//ENDE CMD TV via RIHD
        ),
        ONKYO_Zone::Zone2    => array(
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
        ONKYO_Zone::Zone3    => array(
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
        ONKYO_Zone::Zone4    => array(
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
        if ($API_Data->APISubCommand <> null) {
            if (property_exists($API_Data->APISubCommand, $this->thisZone)) {
                return (in_array($API_Data->APISubCommand->{$this->thisZone}, self::$ZoneCMDs[$this->thisZone]));
            }
        }
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
    //Start NET/USB
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
//ENDE Net/USB
//Start Airplay
    const AAT = "AAT"; //"AAT" - Airplay Artist Name Info (Airplay Model Only)
    const AAL = "AAL"; //"AAL" - Airplay Album Name Info (Airplay Model Only)
    const ATI = "ATI"; //"ATI" - Airplay Title Name (Airplay Model Only)
    const ATM = "ATM"; //"ATM" - Airplay Time Info (Airplay Model Only)
    const AST = "AST"; //"AST" - Airplay Play Status (Airplay Model Only)
//Ende Airplay
//START CMD via RI
    const CDS = "CDS"; //"CDS" - Command for Docking Station via RI
    const CCD = "CCD"; //"CCD" - CD Player Operation Command
    const CT1 = "CT1"; //"CT1" - TAPE1(A) Operation Command
    const CT2 = "CT2"; //"CT2" - TAPE2(B) Operation Command
    const CEQ = "CEQ"; //"CEQ" - Graphics Equalizer Operation Command
    const CDT = "CDT"; //"CDT" - DAT Recorder Operation Command
    const CMD = "CMD"; //"CMD" - MD Recorder Operation Command
    const CCR = "CCR"; //"CCR" - CD-R Recorder Operation Command
//ENDE CMD via RI
//START CMD via PORT
    const CPT = "CPT"; //"CPT" - Universal PORT Operation Command
//"IAT" - iPod Artist Name Info (Universal Port Dock Only)
//"IAL" - iPod Album Name Info (Universal Port Dock Only)
//"ITI" - iPod Title Name (Universal Port Dock Only)
//"ITM" - iPod Time Info (Universal Port Dock Only)
//"ITR" - iPod Track Info (Universal Port Dock Only)
//"IST" - iPod Play Status (Universal Port Dock Only)
//"ILS" - iPod List Info (Universal Port Dock Extend Mode Only)
//"IMD" - iPod Mode Change (Universal Port Dock Only)
//"UTN" - Tuning Command (Universal Port Dock Only)
//"UPR" - Preset Command (Universal Port Dock Only)
//"UPM" - Preset Memory Command (Universal Port Dock Only)
//"UHP" - HD Radio Channel Program Command (Universal Port Dock Only)
//"UHB" - HD Radio Blend Mode Command (Universal Port Dock Only)
//"UHA" - HD Radio Artist Name Info (Universal Port Dock Only)
//"UHC" - HD Radio Channel Name Info (Universal Port Dock Only)
//"UHT" - HD Radio Title Info (Universal Port Dock Only)
//"UHD" - HD Radio Detail Info (Universal Port Dock Only)
//"UHS" - HD Radio Tuner Status (Universal Port Dock Only)
//"UPR" - DAB Preset Command (Universal Port Dock Only)
//"UPM" - Preset Memory Command (Universal Port Dock Only)
//"UDS" - DAB Sation Name (Universal Port Dock Only)
//"UDD" - DAB Display Info (Universal Port Dock Only)
//ENDE CMD via PORT
//START CMD BD via RIHD
    const CDV = "CDV"; //"CDV" - DVD/BD Player Operation Command (via RIHD only after TX-NR509)
//ENDE CMD BD via RIHD
//START CMD TV via RIHD
    const CTV = "CTV"; //"CTV" - TV Operation Command (via RIHD)
//ENDE CMD TV via RIHD
//MAIN end
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

    public static $BoolValueMapping = array(
        false => '00',
        true  => '01',
        '00'  => false,
        '01'  => true
    );

    const IsVariable = 0;
    const VarType = 1;
    const VarName = 2;
    const Profile = 3;
    const EnableAction = 4;
    const RequestValue = 5;
    const ValueMapping = 6;
    const ValuePrefix = 7;

//    const ValuePrefix = 7;
//    const ValueStepSize = 8;
    // Mapping von CMDs der Main auf identische CMDs der Zonen
    public static $CMDMapping = array(
        ISCP_API_Commands::TUN => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::TUN,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::TUZ,
            ONKYO_Zone::Zone3    => ISCP_API_Commands::TU3,
            ONKYO_Zone::Zone4    => ISCP_API_Commands::TU4
        ),
        ISCP_API_Commands::PRS => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::PRS,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::PRZ,
            ONKYO_Zone::Zone3    => ISCP_API_Commands::PR3,
            ONKYO_Zone::Zone4    => ISCP_API_Commands::PR4
        ),
        ISCP_API_Commands::NTC => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::NTC,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::NTZ,
            ONKYO_Zone::Zone3    => ISCP_API_Commands::NT3,
            ONKYO_Zone::Zone4    => ISCP_API_Commands::NT4
        ),
        ISCP_API_Commands::NPR => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::NPR,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::NPZ,
            ONKYO_Zone::Zone3    => ISCP_API_Commands::NP3,
            ONKYO_Zone::Zone4    => ISCP_API_Commands::NP4
        ),
        ISCP_API_Commands::LMD => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::LMD,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::LMZ
        ),
        ISCP_API_Commands::LTN => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::LTN,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::LTZ
        ),
        ISCP_API_Commands::RAS => array(
            ONKYO_Zone::ZoneMain => ISCP_API_Commands::RAS,
            ONKYO_Zone::Zone2    => ISCP_API_Commands::RAZ
        )
    );
    // Nur für alle CMDs, welche keine SubCommands sind.
    public static $VarMapping = array(
        ISCP_API_Commands::PWR => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::AMT => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SPA => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Speaker A',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SPB => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Speaker B',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SPL => array(/* TODO */
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSpeakerLayout,
            self::IsVariable   => true,
            self::VarName      => 'Speaker Layout',
            self::RequestValue => true,
            self::ValueMapping => array("SB" => 1, "FH" => 2, "FW" => 3, "HW" => 4) //, 1 => "SB", 2 => "FH", 3 => "FW", 4 => "HW")
        ),
        ISCP_API_Commands::MVL => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TFR => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Front Treble', 'B' => 'Front Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TFW => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Front Wide Treble', 'B' => 'Front Wide Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TFH => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Front High Treble', 'B' => 'Front High Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TCT => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Center Treble', 'B' => 'Center Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TSR => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Surround Treble', 'B' => 'Surround Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TSB => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Surround Back Treble', 'B' => 'Surround Back Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::TSW => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('B' => 'Subwoofer Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('B' => 0),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
        ISCP_API_Commands::PMB => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Phase Matching Bass',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SLP => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSleep,
            self::IsVariable   => true,
            self::VarName      => 'Sleep Set',
            self::RequestValue => true,
            self::ValueMapping => array("OFF" => 0)
        ),
        // TODO SWL SW2 CTL
        ISCP_API_Commands::DIF => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptDisplayMode,
            self::IsVariable   => true,
            self::VarName      => 'Display Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::DIM => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptDisplayDimmer,
            self::IsVariable   => true,
            self::VarName      => 'Display Dimmer',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::IFA => array(
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => "",
            self::IsVariable   => true,
            self::VarName      => 'Audio Information',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::IFV => array(
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => "",
            self::IsVariable   => true,
            self::VarName      => 'Video Information',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SLI => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input Selector',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SLA => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInputAudio,
            self::IsVariable   => true,
            self::VarName      => 'Audio Input Selector',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TGA => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger A',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TGB => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger B',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TGC => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => '12V Trigger C',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::HDO => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptHDMIOutput,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Output',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::HAO => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptHDMIAudioOutput,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Audio Output',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::HAS => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptHDMIAudioOutput,
            self::IsVariable   => true,
            self::VarName      => 'HDMI Audio Output (Sub)',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::CEC => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'HDMI CEC Control',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::RES => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoResolution,
            self::IsVariable   => true,
            self::VarName      => 'Monitor Out Resolution',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::VWM => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoWideMode,
            self::IsVariable   => true,
            self::VarName      => 'Video Wide Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::VPM => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVideoPictureMode,
            self::IsVariable   => true,
            self::VarName      => 'Video Picture Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::LMD => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptListeningMode,
            self::IsVariable   => true,
            self::VarName      => 'Listening Mode',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::LTN => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptLateNight,
            self::IsVariable   => true,
            self::VarName      => 'Late Night',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::RAS => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Re-EQ or Cinema Filter',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ADY => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptAudyssey,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ADQ => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey Dynamic EQ',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ADV => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptAudysseyDynamic,
            self::IsVariable   => true,
            self::VarName      => 'Audyssey Dynamic Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::DVL => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptDolbyVolume,
            self::IsVariable   => true,
            self::VarName      => 'Dolby Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::MOT => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Music Optimizer',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        // MORE TODO AVS -> ECO
        ISCP_API_Commands::TUN => array(
//            self::VarType => IPSVarType::vtFloat,
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '',
            self::IsVariable   => true,
            self::VarName      => 'Tuner Frequenz',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::PRS => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Radio Preset',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        //Main end
//Zone2 start
        ISCP_API_Commands::ZPW => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ZMT => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ZVL => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::ZTN => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Treble', 'B' => 'Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
//ZBL
        ISCP_API_Commands::SLZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input Selector',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::LMZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptListeningMode,
            self::IsVariable   => true,
            self::VarName      => 'Listening Mode',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::LTZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptLateNight,
            self::IsVariable   => true,
            self::VarName      => 'Late Night',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::RAZ => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Re-EQ or Cinema Filter',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TUZ => array(
//            self::VarType => IPSVarType::vtFloat,
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '',
            self::IsVariable   => true,
            self::VarName      => 'Tuner Frequenz',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::PRZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Radio Preset',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        // Zone 2 end
// Zone 3 start
        ISCP_API_Commands::PW3 => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::MT3 => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::VL3 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TN3 => array(
            self::VarType      => IPSVarType::vtDualInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptToneOffset,
            self::IsVariable   => true,
            self::VarName      => array('T' => 'Treble', 'B' => 'Bass'),
            self::RequestValue => true,
            self::ValuePrefix  => array('T' => 0, 'B' => 1),
            self::ValueMapping => array("-A" => -10, "-8" => -8, "-6" => -6, "-4" => -4,
                "-2" => -2, "00" => 0, "+2" => 2, "+4" => 4, "+6" => 6, "+8" => 8,
                "+A" => 10)
        ),
//BL3
        ISCP_API_Commands::SL3 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input Selector',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TU3 => array(
//            self::VarType => IPSVarType::vtFloat,
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '',
            self::IsVariable   => true,
            self::VarName      => 'Tuner Frequenz',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::PR3 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        //Zone 3 end
// Zone 4 start
        ISCP_API_Commands::PW4 => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Power',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::MT4 => array(
            self::VarType      => IPSVarType::vtBoolean,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSwitch,
            self::IsVariable   => true,
            self::VarName      => 'Mute',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::VL4 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptVolume,
            self::IsVariable   => true,
            self::VarName      => 'Volume',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::SL4 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptSelectInput,
            self::IsVariable   => true,
            self::VarName      => 'Input Selector',
            self::RequestValue => true,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::TU4 => array(
//            self::VarType => IPSVarType::vtFloat,
            self::VarType      => IPSVarType::vtString,
            self::EnableAction => false,
            self::Profile      => '',
            self::IsVariable   => true,
            self::VarName      => 'Tuner Frequenz',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::PR4 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        //Zone 4 end
        // MORE TODO Network all
        ISCP_API_Commands::NTC => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetTuneCommand,
            self::IsVariable   => false,
            self::VarName      => null,
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NTZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetTuneCommand,
            self::IsVariable   => false,
            self::VarName      => null,
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NT3 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetTuneCommand,
            self::IsVariable   => false,
            self::VarName      => null,
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NT4 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetTuneCommand,
            self::IsVariable   => false,
            self::VarName      => null,
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NPR => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Network Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NPZ => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Network Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NP3 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Network Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
        ),
        ISCP_API_Commands::NP4 => array(
            self::VarType      => IPSVarType::vtInteger,
            self::EnableAction => true,
            self::Profile      => IPSProfiles::ptNetRadioPreset,
            self::IsVariable   => true,
            self::VarName      => 'Network Radio Preset',
            self::RequestValue => false,
            self::ValueMapping => null
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

}

class ISCP_API_Command_Mapping extends stdClass
{
    public static function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$CMDMapping)) {
            return ISCP_API_Commands::$CMDMapping[$Cmd];
        } else {
            return null;
        }
    }

}

class ISCP_API_Data_Mapping extends stdClass
{
    public static function GetMapping($Cmd) //__construct($Cmd)
    {
        if (array_key_exists($Cmd, ISCP_API_Commands::$VarMapping)) {
            $result = new stdClass;
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
        } else {
            return null;
        }
    }

}

class ISCP_API_Data extends stdClass
{
    public $APICommand;
    public $Data;
    public $Mapping = null;
    public $APISubCommand = null;

    public function GetDataFromJSONObject($Data)
    {
        $this->APICommand = $Data->APICommand;
        $this->Data = utf8_decode($Data->Data);
        if (property_exists($Data, 'APISubCommand')) {
            $this->APISubCommand = $Data->APISubCommand;
        }
    }

    public function ToJSONString($GUID)
    {
        $SendData = new stdClass;
        $SendData->DataID = $GUID;
        $SendData->APICommand = $this->APICommand;
        $SendData->Data = utf8_encode($this->Data);
        $SendData->APISubCommand = $this->APISubCommand;
        return json_encode($SendData);
    }

    public function GetMapping()
    {
        $this->Mapping = ISCP_API_Data_Mapping::GetMapping($this->APICommand);
    }

    public function GetSubCommand()
    {
        $this->APISubCommand = (object) ISCP_API_Command_Mapping::GetMapping($this->APICommand);
    }

}
