[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.00-blue.svg)]()
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)  
[![Version](https://img.shields.io/badge/Symcon%20Version-5.1%20%3E-green.svg)](https://www.symcon.de/forum/threads/30857-IP-Symcon-5-1-%28Stable%29-Changelog)
[![StyleCI](https://styleci.io/repos/45338104/shield?style=flat)](https://styleci.io/repos/45338104)  

# Symcon-Modul: Onkyo & Pioneer AVR

Diese Implementierung des Integra Serial Communication Protocol 
ermöglich die Einbindung von Onkyo und Pioneer AV-Receiver in IP-Symcon.  

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang) 
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation) 
4. [Einrichten der Instanzen in IP-Symcon](#5-einrichten-der-instanzen-in-ip-symcon)
5. [Anhang](#5-anhang)  
    1. [GUID der Module](#1-guid-der-module)
    2. [Changlog](#2-changlog)
    3. [Spenden](#3-spenden)
6. [Lizenz](#6-lizenz)

## 1. Funktionsumfang

### [OnkyoAVRDiscovery:](OnkyoAVRDiscovery/)  
### [OnkyoConfigurator:](OnkyoConfigurator/)  
### [OnkyoAVRSplitter:](OnkyoAVRSplitter/)  
### [OnkyoAVRZone:](OnkyoAVRZone/)  
### [OnkyoRemote:](OnkyoRemote/)  
### [OnkyoTuner:](OnkyoTuner/)  


## 2. Voraussetzungen

 - IPS 5.1 oder höher
 - kompatibler AV-Receiver mit LAN oder RS232-Anschluß
 
## 3. Software-Installation

**IPS 5.1:**  
   Bei privater Nutzung:
     Über den 'Module-Store' in IPS.  
   **Bei kommerzieller Nutzung (z.B. als Errichter oder Integrator) wenden Sie sich bitte an den Autor.**  


## 4. Einrichten der Instanzen in IP-Symcon

Ist direkt in der Dokumentation der jeweiligen Module beschrieben.  
Es wird empfohlen die Einrichtung mit der Discovery-Instanz zu starten ([OnkyoAVRDiscovery:](OnkyoAVRDiscovery/)).  

## 5. Anhang

###  1. GUID der Module
 
 
| Modul               | Typ          |Prefix  | GUID                                   |
| :-----------------: | :----------: | :----: | :------------------------------------: |
| Onkyo AVR Discovery | Discovery    | OAVR   | {7A3A7067-253F-4270-AC6D-55790FB12F53} |
| Onkyo Configurator  | Configurator | OAVR   | {251DAC2C-5B1F-4B1F-B843-B22D518F553E} |
| ISCP Splitter       | Splitter     | OAVR   | {EB1697D1-2A88-4A1A-89D9-807D73EEA7C9} |
| Onkyo AVR Zone      | Device       | OAVR   | {DEDC12F1-4CF7-4DD1-AE21-B03D7A7FADD7} |
| Onkyo Tuner         | Device       | OAVR   | {47D1BFF5-B6A6-4C3A-A11F-CDA656E3D85F} |
| Onkyo Remote        | Device       | OAVR   | {C7EA583D-2BAC-41B7-A85A-AD0DF648E514} |

### 2. Changlog

**Changlog:**

 Version 2.0:  
 - Modul für IPS 5.1 komplett überarbeitet  
 - Neue Discovery Instanz zum auffinden und einrichten von Geräten in Symcon  
 - Neue Konfigurator Instanz zum einfachen einrichten der Geräte Instanzen in Symcon  
 - Neue Instanzen für Tuner und Fernsteuerung (Remote)  
 - Profile folgen dem Muster Onkyo.<Name>  
 - Zonen können detalierter Konfiguriert werden und unterstützen mehr Funktionen  
 - Übersetzungen hinzugefügt  
 - Automatische Erkennung der verfügbaren Eingänge und Wertebereiche für u.a. Lautstärke und Pegelanpassung  

 Version 0.4:  
 - Bugfix für IPS 5.0  

 Version 0.3:  
 - Bugfix Datenaustausch aus 0.2  

 Version 0.2:  
 - Bugfix Timer & Datenaustausch. Doku falsch / fehlt noch immer. Umbau auf RC Beta1 folgt.  

 Version 0.1:  
 - Testversion  


### 3. Spenden  
  
  Die Library ist für die nicht kommzerielle Nutzung kostenlos, Schenkungen als Unterstützung für den Autor werden hier akzeptiert:  

<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G2SLW2MEMQZH2" target="_blank"><img src="https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donate_LG.gif" border="0" /></a>

## 6. Lizenz

  IPS-Modul:  
  [CC BY-NC-SA 4.0](https://creativecommons.org/licenses/by-nc-sa/4.0/)  