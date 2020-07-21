[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.00-blue.svg)]()
[![Version](https://img.shields.io/badge/Symcon%20Version-5.1%20%3E-green.svg)](https://www.symcon.de/forum/threads/30857-IP-Symcon-5-1-%28Stable%29-Changelog)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Check Style](https://github.com/Nall-chan/OnkyoAVR/workflows/Check%20Style/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions) [![Run Tests](https://github.com/Nall-chan/OnkyoAVR/workflows/Run%20Tests/badge.svg)](https://github.com/Nall-chan/OnkyoAVR/actions)  


# Onkyo & Pioneer Tuner (Onkyo Tuner)
Bildet einen Tuner eines Gerätes in IP-Symcon ab.  

## Inhaltsverzeichnis  <!-- omit in toc -->

- [1. Funktionsumfang](#1-funktionsumfang)
- [2. Voraussetzungen](#2-voraussetzungen)
- [3. Software-Installation](#3-software-installation)
- [4. Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
- [5. Statusvariablen und Profile](#5-statusvariablen-und-profile)
- [6. WebFront](#6-webfront)
- [7. PHP-Befehlsreferenz](#7-php-befehlsreferenz)
- [8. Lizenz](#8-lizenz)

## 1. Funktionsumfang

 - Darstellen von Zuständen des Tuner.    
 - Bedienung aus dem WebFront.  
 - Bereitstellung von PHP-Befehlen zur Steuerung durch Scripte.  

## 2. Voraussetzungen

 - IPS ab Version 5.1  
 - kompatibler AV-Receiver mit LAN-Anschluss oder RS232 (RS232 Geräte haben einen eingeschränkten Leistungsumfang)  

## 3. Software-Installation

Dieses Modul ist ein Bestandteil des Symcon-Modul: [Onkyo & Pioneer AVR](../)  

## 4. Einrichten der Instanzen in IP-Symcon

Eine einfache Einrichtung ist über die im Objektbaum unter 'Konfigurator' zu findende Instanz [Onkyo bzw Pioneer Configurator'](../OnkyoConfigurator/readme.md) möglich.  

Bei der manuellen Einrichtung ist das Modul im Dialog 'Instanz hinzufügen' unter den Hersteller 'Onkyo' zu finden.  
![Instanz hinzufügen](../imgs/instanzen.png)  

In dem sich öffnenden Konfigurationsformular ist die gewünschte Zone auszuwählen, welche beim senden vom Kommandos an das Gerät benutzt wird.  
![Konfiguration keine Zone](../imgs/conf_tuner.png)  

## 5. Statusvariablen und Profile

Jede Instanz erstellt einige Profile dynamisch, je nach Fähigkeiten der Geräte.  

**Statusvariablen MainZone:**  

|      Name      |   Typ   | Ident |             Beschreibung              |
| :------------: | :-----: | :---: | :-----------------------------------: |
|   Tuner Band   | integer |  SLI  |        Aktive Quelle der Zone         |
|  Radiosender   | integer |  PRS  | Aktueller Speicherplatz eines Senders |
| Tuner-Frequenz |  float  |  TUN  |           Aktuelle Frequenz           |


**Profile**:
 
 Alle Profile mit .* am Ende, enthalten immer die InstanzID und sind dynamische Profile.  
 Diese können sich während des Betriebes, oder beim ändern von Geräteeinstellungen dynamisch verändern.  

|        Name         |   Typ   | verwendet von Statusvariablen  (Ident) |
| :-----------------: | :-----: | :------------------------------------: |
|  Onkyo.TunerBand.*  | integer |                  SLI                   |
| Onkyo.TunerPreset.* | integer |                  PRS                   |
|  Onkyo.TunerFreq.*  |  float  |                  TUN                   |

## 6. WebFront

Die direkte Darstellung im WebFront ist möglich, es wird aber empfohlen mit Links zu arbeiten.  

![WebFront Beispiel](../imgs/webfront_tuner.png)  


## 7. PHP-Befehlsreferenz

**Schaltbare Statusvariablen können universell mit RequestAction angesteuert werden.**  
Siehe Symcon Dokumentation: [RequestAction](https://www.symcon.de/service/dokumentation/befehlsreferenz/variablenzugriff/requestaction/)

---  

**Folgende Funktionen liefern 'TRUE' bei Erfolg.  
Im Fehlerfall wird eine Warnung erzeugt und 'FALSE' zurückgegeben.**  


```php
bool OAVR_RequestState(int $InstanzeID, string $Ident);
```
Fordert den aktuellen Wert einer Statusvariable beim Gerät an.  

```php
bool OAVR_SetBand(int $InstanzeID, int $Value);
```
Schaltes auf das in '$Value' angegeben Frequenzband um.  
Hierbei entspricht der Wert 0x24 dem FM und 0x25 dem AM Band.  

```php
bool OAVR_SetFrequency(int $InstanzeID, float $Value);
```
Setzt den Tuner auf die in '$Value' übergebenen Frequenz.
'$Value' besitzt keine Einheit, somit sind z.B. 100,2 ein gültiger Wert für 100,2 MHz FM, und 702 ein gültiger Wert für 702 kHz.    
Es wird dabei wird automatisch zwischen AM und FM umgeschaltet.   
  
```php
bool OAVR_CallPreset(int $InstanzeID, int $Value);
```
Ruft direkt den in '$Value' übergeben Sender auf auf.  
  
```php
bool OAVR_SetPreset(int $InstanzeID, int $Value);
```
Speichert die aktuelle Frequenz in den in '$Value' übergebenen Speicherplatz.  
 

## 8. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
