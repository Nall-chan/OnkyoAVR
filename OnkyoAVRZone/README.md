[![SDK](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Module Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FOnkyoAVR%2Frefs%2Fheads%2Fstrict%2Flibrary.json&query=%24.version&label=Modul%20Version&color=blue)](https://community.symcon.de/t/modul-onkyo-pioneer-avr/53213)
[![Symcon Version](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2FNall-chan%2FOnkyoAVR%2Frefs%2Fheads%2Fstrict%2Flibrary.json&query=%24.compatibility.version&suffix=%3E&label=Symcon%20Version&color=green)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/v80-v81-q3-2025/)  
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Nall-chan/OnkyoAVR/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions) [![Run Tests](https://github.com/Nall-chan/OnkyoAVR/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions)  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](#2-spenden)[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](#2-spenden)

# Onkyo & Pioneer AVR Zone (Onkyo AVR) <!-- omit in toc -->

Bildet eine der Zones eines Gerätes in IP-Symcon ab.  

## Inhaltsverzeichnis <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen und Profile](#5-statusvariablen-und-profile)
- [6. Visualisierung](#6-visualisierung)
- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)
- [8. Aktionen](#8-aktionen)
- [9. Anhang](#9-anhang)
  - [1. Changelog](#1-changelog)
  - [2. Spenden](#2-spenden)
- [10. Lizenz](#10-lizenz)

## 1. Funktionsumfang

- Darstellen von Zuständen der Zone.  
- Bedienung aus dem WebFront.  
- Bereitstellung von PHP-Befehlen zur Steuerung durch Scripte.  

## 2. Voraussetzungen

- Symcon ab Version 8.2  
- kompatibler AV-Receiver mit LAN-Anschluss oder RS232 (RS232 Geräte haben einen eingeschränkten Leistungsumfang)  

## 3. Software-Installation

Dieses Modul ist ein Bestandteil des Symcon-Modul: [Onkyo & Pioneer AVR](../)  

## 4. Einrichten der Instanzen in IP-Symcon

Eine einfache Einrichtung ist über die im Objektbaum unter `Konfigurator` zu findende Instanz [Onkyo bzw Pioneer Configurator](../OnkyoConfigurator/README.md#4-einrichten-der-instanzen-in-ip-symcon) möglich.  

Bei der manuellen Einrichtung ist das Modul im Dialog `Instanz hinzufügen` unter den Hersteller `Onkyo` zu finden.  
![Instanz hinzufügen](../imgs/instanzen.png)  

In dem sich öffnenden Konfigurationsformular ist die gewünschte Zone auszuwählen.  
Weitere Einstellungen ergeben sich auch der gewählten Zone.  
Viele Funktionen stehen nur in der `MainZone` zur Verfügung.  
Zone 2, 3 und 4 haben, je nach Gerät, ein deutlich kleineres Spektrum an verfügbaren Einstellungen und Funktionen.  
![Konfiguration keine Zone](../imgs/conf_zone0.png)  

**Beispiel Konfiguration MainZone:**  
![Konfiguration Main Zone](../imgs/conf_zone1.png)  
**Beispiel Konfiguration Zone 2:**  
![Konfiguration Zone 2](../imgs/conf_zone2.png)  

## 5. Statusvariablen und Profile

Jede Zone erstellt ihre Statusvariablen und einige Profile dynamisch, je nach Fähigkeiten der Geräte/Zonen und der Instanz-Konfiguration.  
Es können in der Konfiguration bestimmte Statusvariablen ab/angewählt werden.  
Diese werden jedoch nur erzeugt, wenn das Gerät auch eine (sinnvolle) Antwort liefert.  
**Beispiel Main Zone, Werkseinstellungen:**  
![Objektbaum Main Zone](../imgs/logbaum_zone1_default.png)  
**Beispiel Main Zone bei TX-626 mit allen Funktionen in der Instanz aktiviert:**  
![Objektbaum Main Zone](../imgs/logbaum_zone1_max.png)  

Die Statusvariablen können in der Konfiguration der Instanz aktiviert / inaktiviert werden.  

**Statusvariablen MainZone:**  

| Name                        |   Typ   | Ident | Beschreibung                               |
| :-------------------------- | :-----: | :---: | :----------------------------------------- |
| Power                       | boolean |  PWR  | Zone ein- oder ausgeschaltet               |
| Volume                      | integer |  MVL  | Lautstärke                                 |
| Mute                        | boolean |  AMT  | Stummschaltung aktiv / inaktiv             |
| Eingang                     | integer |  SLI  | Audio/Video Eingangsquelle                 |
| Vorne Höhen                 | integer | TFR0  | Frontlautsprecher Höhen                    |
| Vorne Bass                  | integer | TFR1  | Frontlautsprecher Bass                     |
| Vorne außen Höhen           | integer | TFW0  | Front-Wide-Lautsprecher Höhen              |
| Vorne außen Bass            | integer | TFW1  | Front-Wide-Lautsprecher Bass               |
| Vorne oben Höhen            | integer | TFH0  | Front-High-Lautsprecher Höhen              |
| Vorne oben Bass             | integer | TFH1  | Front-High-Lautsprecher Bass               |
| Surround Höhen              | integer | TSR0  | Surround-Lautsprecher Höhen                |
| Surround Bass               | integer | TSR1  | Surround-Lautsprecher Bass                 |
| Surround hinten Höhen       | integer | TSB0  | Hintere Surround-Lautsprecher Höhen        |
| Surround hinten Bass        | integer | TSB1  | Hintere Surround-Lautsprecher Bass         |
| Center Höhen                | integer | TCT0  | Center-Lautsprecher Höhen                  |
| Center Bass                 | integer | TCT1  | Center-Lautsprecher Bass                   |
| Subwoofer Bass              | integer | TSW0  | Subwoofer Bass                             |
| Center Pegel                | integer |  CTL  | Center Pegelanpassung                      |
| Subwoofer Pegel             | integer |  SWL  | Subwoofer Pegelanpassung                   |
| Subwoofer 2 Pegel           | integer |  SW2  | Subwoofer 2 Pegelanpassung                 |
| Anzeigemodus                | integer |  DIF  | Display Anzeigemodus                       |
| Anzeigebeleuchtung          | integer |  DIM  | Display Beleuchtung                        |
| Audio Information           | string  |  IFA  | Audio Information der aktuellen Wiedergabe |
| Video Information           | string  |  IFV  | Video Information der aktuellen Wiedergabe |
| Audio Eingang               | integer |  SLA  | Audio Eingangsquelle                       |
| HDMI Ausgang                | integer |  HDO  | HDMI Ausgang                               |
| HDMI Audio Ausgang          | integer |  HAO  | HDMI Audio Ausgang                         |
| HDMI Audio Ausgang (Sub)    | boolean |  HAS  | HDMI Audio Ausgang (Sub) aktiv / inaktiv   |
| HDMI CEC Steuerung          | boolean |  CEC  | HDMI CEC Steuerung aktiv / inaktiv         |
| Monitorauflösung            | integer |  RES  | Monitorauflösung                           |
| Videomodus                  | integer |  VWM  | Videomodus                                 |
| Videobildmodus              | integer |  VPM  | Videobildmodus                             |
| Hörmodus                    | integer |  LMD  | Hörmodus                                   |
| Hörmodus                    | integer | LMD2  | Aktions-Tasten für Hörmodus                |
| Spät-Abends                 | integer |  LTN  | Late Night Steuerung                       |
| Re-EQ oder Kinofilter       | boolean |  RAS  | Re-EQ oder Kinofilter aktiv / inaktiv      |
| Audyssey Modus              | integer |  ADY  | Audyssey Modus                             |
| Audyssey Dynamic EQ         | boolean |  ADQ  | Audyssey aktiv / inaktiv                   |
| Audyssey Dynamic Lautstärke | integer |  ADV  | Audyssey Lautstärke                        |
| Musik Optimierer            | boolean |  MOT  | Musik Optimierer aktiv / inaktiv           |
| Phasenanpassungsbass        | boolean |  PMB  | Phasenanpassungsbass aktiv / inaktiv       |
| EinschlafTimer              | integer |  SLP  | EinschlafTimer                             |

**Statusvariablen Zone 2:**  

| Name    |   Typ   | Ident | Beschreibung                   |
| :------ | :-----: | :---: | :----------------------------- |
| Power   | boolean |  ZPW  | Zone ein- oder ausgeschaltet   |
| Volume  | integer |  ZVL  | Lautstärke                     |
| Mute    | boolean |  ZMT  | Stummschaltung aktiv / inaktiv |
| Eingang | integer |  SLZ  | Audio/Video Eingangsquelle     |
| Höhen   | integer | ZTN0  | Lautsprecher Höhen             |
| Bass    | integer | ZTN0  | Lautsprecher Bass              |

**Statusvariablen Zone 3:**  

| Name    |   Typ   | Ident | Beschreibung                   |
| :------ | :-----: | :---: | :----------------------------- |
| Power   | boolean |  PW3  | Zone ein- oder ausgeschaltet   |
| Volume  | integer |  VL3  | Lautstärke                     |
| Mute    | boolean |  MT3  | Stummschaltung aktiv / inaktiv |
| Eingang | integer |  SL3  | Audio/Video Eingangsquelle     |
| Höhen   | integer | TN30  | Lautsprecher Höhen             |
| Bass    | integer | TN31  | Lautsprecher Bass              |

**Statusvariablen Zone 4:**  

| Name    |   Typ   | Ident | Beschreibung                   |
| :------ | :-----: | :---: | :----------------------------- |
| Power   | boolean |  PW4  | Zone ein- oder ausgeschaltet   |
| Volume  | integer |  VL4  | Lautstärke                     |
| Mute    | boolean |  MT4  | Stummschaltung aktiv / inaktiv |
| Eingang | integer |  SL4  | Audio/Video Eingangsquelle     |

**Profile**:  

Alle Profile mit .* am Ende, enthalten immer die InstanzID und sind dynamische Profile.  
Diese können sich während des Betriebes, oder beim ändern von Geräteeinstellungen dynamisch verändern.  

| Name                    |   Typ   | verwendet von Statusvariablen  (Ident)      |
| :---------------------- | :-----: | :------------------------------------------ |
| Onkyo.Volume.*          | integer | MVL, ZVL, VL3, VL4                          |
| Onkyo.ToneOffset.*      | integer | TRF, TFW, TFH, TCT, TSR, TSB, TSW, ZTN, TN3 |
| Onkyo.CenterLevel.*     |  float  | CTL                                         |
| Onkyo.SubwooferLevel.*  |  float  | SWL                                         |
| Onkyo.Subwoofer2Level.* |  float  | SW2                                         |
| Onkyo.SelectInput.*     | integer | SLI, SLZ, SL3, SL4                          |
| Onkyo.LMD               | integer | LMD                                         |
| Onkyo.SelectLMD.*       | integer | LMD2                                        |
| Onkyo.SelectInputAudio  | integer | SLA                                         |
| Onkyo.DisplayMode       | integer | DIF                                         |
| Onkyo.DisplayDimmer     | integer | DIM                                         |
| Onkyo.HDMIOutput        | integer | HDO                                         |
| Onkyo.HDMIAudioOutput   | integer | HAO                                         |
| Onkyo.VideoResolution   | integer | RES                                         |
| Onkyo.VideoWideMode     | integer | VWM                                         |
| Onkyo.VideoPictureMode  | integer | VPM                                         |
| Onkyo.LateNight         | integer | LTN                                         |
| Onkyo.Audyssey          | integer | ADY                                         |
| Onkyo.AudysseyDynamic   | integer | ADV                                         |
| Onkyo.Sleep             | integer | SLP                                         |

## 6. Visualisierung

Die direkte Darstellung ist möglich, es wird aber empfohlen mit Links zu arbeiten.  

**Beispiel WebFront, Main Zone (Werkseinstellungen):**  
![WebFront Beispiel](../imgs/webfront_mainzone_default.png)  

## 7. PHP-Befehlsreferenz

**Schaltbare Statusvariablen können universell mit RequestAction angesteuert werden.**  
Siehe Symcon Dokumentation: [RequestAction](https://www.symcon.de/service/dokumentation/befehlsreferenz/variablenzugriff/requestaction/)

---  

**Folgende Funktionen liefern `TRUE`  bei Erfolg.  
Im Fehlerfall wird eine Warnung erzeugt und `FALSE` zurückgegeben.**  

```php
bool OAVR_RequestState(int $InstanzID, string $Ident);
```

Fordert den aktuellen Wert einer Statusvariable beim Gerät an.  

---  

```php
bool OAVR_PowerOn(int $InstanzID);
```

Einschalten der Zone.  

---  

```php
bool OAVR_PowerOff(int $InstanzID);
```

Ausschalten der Zone.  

---  

```php
bool OAVR_Power(int $InstanzID);
```

Umschalten der Zone.  

---  

```php
bool OAVR_SetVolume(int $InstanzID, int $Value);
```

Lautstärke der Zone setzen. `Value` ist der absolute Wert (z.B. 0-80).

---  

```php
bool OAVR_SetMute(int $InstanzID, bool $Value);
```

Stummschaltung wird mit `Value` bei `True` aktiviert und bei `False` inaktiviert.  

---  

```php
bool OAVR_SelectInput(int $InstanzID, int $Value);
```

Eingangsquelle der Zone wählen.  
Dabei kann `Value` je nach Gerät und Zone nur bestimmte Werte haben.  
Gültige Werte sind im VariablenProfil `Onkyo.SelectInput.<InstanzId>` zu finden.  
Beispielwerte aus der API sind:  

| Wert  |     Eingang     |
| :---: | :-------------: |
|   0   | Video 1 VCR/DVR |
|   1   | Video 2 CBL/SAT |
|   2   | Video 3 GAME/TV |
|   3   |  Video 4 AUX1   |
|   4   |  Video 5 AUX2   |
|   5   |   Video 6 PC    |
|  16   |     BD/DVD      |
|  18   |       TV        |
|  32   |     TAPE 1      |
|  34   |      PHONO      |
|  35   |      TV/CD      |
|  36   |   Tuner (FM)    |
|  37   |   Tuner (AM)    |
|  41   |   USB(Front)    |
|  43   |     Network     |
|  45   |     Airplay     |
|  46   |    Bluetooth    |
|  64   | Universal PORT  |

---  

```php
bool OAVR_SelectAudioInput(int $InstanzID, int $Value);
```

Audioquelle der Zone wählen.  
Dabei kann `Value` je nach Gerät und Zone nur bestimmte Werte haben.  
Beispielwerte sind im VariablenProfil `Onkyo.SelectInputAudio` zu finden.  
Werte aus der API sind:  

| Wert  |    Eingang    |
| :---: | :-----------: |
|   0   |     AUTO      |
|   1   | MULTI-CHANNEL |
|   2   |    ANALOG     |
|   3   |     iLINK     |
|   4   |     HDMI      |
|   5   |   COAX/OPT    |
|   6   |    BALANCE    |
|   7   |      ARC      |
|  15   |    Keiner     |

---  

```php
bool OAVR_SelectListingMode(int $InstanzID, int $Value);
```

Hörmodus der Zone wählen.  
Dabei kann `Value` je nach Gerät und Zone nur bestimmte Werte haben.  
Beispielwerte sind im VariablenProfil `Onkyo.LMD` zu finden.  
Werte aus der API sind, u.a.:  

| Wert  |               Hörmodus                |
| :---: | :-----------------------------------: |
|  00   |                STEREO                 |
|  01   |                DIRECT                 |
|  02   |               SURROUND                |
|  03   |                 FILM                  |
|  04   |                  THX                  |
|  05   |                ACTION                 |
|  06   |                MUSICAL                |
|  08   |               ORCHESTRA               |
|  09   |               UNPLUGGED               |
|  11   |              STUDIO-MIX               |
|  12   |               TV LOGIC                |
|  13   |             ALL CH STEREO             |
|  14   |          THEATER-DIMENSIONAL          |
|  15   |               ENHANCED                |
|  16   |                 MONO                  |
|  18   |              PURE AUDIO               |
|  19   |               FULL MONO               |
|  64   |            Straight Decode            |
|  66   |              THX Cinema               |
|  67   |            THX Surround EX            |
|  68   |               THX Music               |
|  69   |               THX Games               |
|  80   | THX Cinema Mode, THX U2/S2/I/S Cinema |
|  81   |  THX Music Mode, THX U2/S2/I/S Music  |
|  82   |  THX Games Mode, THX U2/S2/I/S Games  |
|  128  |           PLII/PLIIx Movie            |
|  129  |           PLII/PLIIx Music            |
|  130  |       Neo:6 Cinema/Neo:X Cinema       |
|  131  |        Neo:6 Music/Neo:X Music        |
|  132  |         PLII/PLIIx THX Cinema         |
|  133  |        Neo:6/Neo:X THX Cinema         |
|  134  |            PLII/PLIIx Game            |
|  137  |         PLII/PLIIx THX Games          |
|  138  |         Neo:6/Neo:X THX Games         |
|  139  |         PLII/PLIIx THX Music          |
|  140  |         Neo:6/Neo:X THX Music         |

---  

```php
bool OAVR_SetSleep(int $InstanzID, int $Duration);
```

Aktiviert den Einschlaf-Timer mit den in `Duration` übergeben Wert in Sekunden.  

---  

```php
bool OAVR_SetSubwooferLevel(int $InstanzID, float $Level);
```

Setzt den Level des Subwoofer auf den in `Level` übergeben Wert.  
Je nach Gerät sind verschiedene min/max Werte zulässig.  
Ein gültige Wertebereich ist im VariablenProfil `Onkyo.SubwooferLevel.<InstanzId>` zu finden.  

---  

```php
bool OAVR_SetSubwoofer2Level(int $InstanzID, float $Level);
```

Entspricht `OAVR_SetSubwooferLevel`, nur für den zweiten Subwoofer (sofern vorhanden).  

---  

```php
bool OAVR_SetDisplayMode(int $InstanzID, int $Value);
```

Setzt den Anzeigemodus des Display.  
`Value` muss einen der folgenden Werte enthalten.  

| Wert  |      Anzeigemodus      |
| :---: | :--------------------: |
|   0   |     Input & Volume     |
|   1   | Input & Listening Mode |
|   2   |     Digital Format     |
|   3   |      Video Format      |

---  

```php
bool OAVR_SetDisplayDimmer(int $InstanzID, int $Level);
```

Setzt die Helligkeit des Display.  
`Level` muss einen der folgenden Werte enthalten.  

| Wert  |    Helligkeit    |
| :---: | :--------------: |
|   0   |       Hell       |
|   1   |      Mittel      |
|   2   |      Dunkel      |
|   3   |       Aus        |
|   8   | Hell und LED aus |

---  

```php
array OAVR_GetAudioInformation(int $InstanzID);
```

Liefert ein Array mit bekannten Informationen zum Audiosignal.  
Bei nicht bekannten Daten, ist der Wert leer.  
            'Audio Input Port',  
            'Input Signal Format',  
            'Sampling Frequency',  
            'Input Signal Channel',  
            'Listening Mode',  
            'Output Signal Channel',  
            'Output Sampling Frequency',  
            'PQLS',  
            'Auto Phase Control Current Delay',  
            'Auto Phase Control Phase',  
            'Upmix Mode'  

---  

```php
array OAVR_GetVideoInformation(int $InstanzID);
```

Liefert ein Array mit bekannten Informationen zum Videosignal.  
Bei nicht bekannten Daten, ist der Wert leer.  
            'Video Input Port',  
            'Input Resolution',  
            'RGB/YCbCr',  
            'Color Depth',  
            'Video Output Port',  
            'Output Resolution',  
            'RGB/YCbCr',  
            'Color Depth',  
            'Picture Mode'  

---  

## 8. Aktionen

Die Instanz unterstützt (aktuell) keine Aktionen.  

## 9. Anhang

### 1. Changelog

[Changelog der Library](../README.md#2-changelog)

### 2. Spenden

Die Library ist für die nicht kommerzielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

PayPal:  
[![PayPal.Me](https://img.shields.io/badge/PayPal-Me-lightblue.svg)](https://paypal.me/Nall4chan)  

Wunschliste:  
[![Wunschliste](https://img.shields.io/badge/Wunschliste-Amazon-ff69fb.svg)](https://www.amazon.de/hz/wishlist/ls/YU4AI9AQT9F?ref_=wl_share)  

## 10. Lizenz

IPS-Modul:  
[CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
