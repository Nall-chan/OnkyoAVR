<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class LibraryValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateOnkyoAVRDiscovery(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoAVRDiscovery');
    }

    public function testValidateOnkyoAVRSplitter(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoAVRSplitter');
    }

    public function testValidateOnkyoConfigurator(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoConfigurator');
    }

    public function testValidateOnkyoAVRZone(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoAVRZone');
    }

    public function testValidateOnkyoNetplayer(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoNetplayer');
    }
    public function testValidateOnkyoRemote(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoRemote');
    }
    public function testValidateOnkyoTuner(): void
    {
        $this->validateModule(__DIR__ . '/../OnkyoTuner');
    }
}