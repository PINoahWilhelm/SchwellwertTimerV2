<?
require(__DIR__ . "\\pimodule.php");

    // Klassendefinition
    class SymconSchwellwertTimerV2 extends PISymconModule {

        public $Details = true;

        // Eigene Variablen 
        public $Status;
        public $Targets;
        public $Sensoren;
        public $Events;

        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);



            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {

            parent::Create();

 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
           
            parent::ApplyChanges();


        }

        protected function onDetailsChangeShow () {

            $prnt = IPS_GetParent($this->InstanceID);
            $name = IPS_GetName($this->InstanceID);

            $this->linkVar($this->Sensoren, $name . " Sensoren", $prnt,99,true);
            $this->linkVar($this->Targets, $name . " Geräte", $prnt,99,true);

        }

        protected function onDetailsChangeHide () {

            $prnt = IPS_GetParent($this->InstanceID);
            $name = IPS_GetName($this->InstanceID);

            $this->deleteObject($this->searchObjectByName($name . " Sensoren", $prnt));
            $this->deleteObject($this->searchObjectByName($name . " Geräte", $prnt));

        }

        protected function setGlobalized () {

            return array("Targets", "Sensoren", "Status", "Events");

        }

        protected function setExcludedHide() {

            return array($this->AutomatikVar, $this->SperreVar, $this->detailsVar, $this->Status);

        }

        protected function setExcludedShow () {

            return array("script", "instance", $this->searchObjectByName("Nachlauf aktiv"));

        }

        public function CheckVariables () {

            $switches = $this->createSwitches(array("Automatik|false|0", "Sperre|false|1", "Status|false|2"));

            $verzögerung = $this->checkInteger("Verzögerung", false, "", 3, $this->secondsToTimestamp(300));
            $nachlauf = $this->checkInteger("Nachlauf", false, "", 4, $this->secondsToTimestamp(1800));

            $nachlaufAktiv = $this->checkBoolean("Nachlauf aktiv", false);

            $this->addProfile($verzögerung, "~UnixTimestampTime");
            $this->addProfile($nachlauf, "~UnixTimestampTime");

            $this->addSetValue($verzögerung);
            $this->addSetValue($nachlauf);

            $this->setIcon($verzögerung, "Clock");
            $this->setIcon($nachlauf, "Clock");

            $targets = $this->checkFolder("Targets");
            $sensoren = $this->checkFolder("Sensoren");

            $this->createOnChangeEvents(array($this->AutomatikVar . "|onAutomaticChange", $this->Status . "|onStatusChange"), $this->Events);

            $this->hide($targets);
            $this->hide($sensoren);
            $this->hide($nachlaufAktiv);

            $this->checkSensorVars();

        }

        public function CheckScripts () {

            // Scripts checken -und erstellen
            $this->checkScript("DelayEnd", $this->prefix . "_onDelayEnd");
            $this->checkScript("Trailing", $this->prefix . "_trailing");
            $this->checkScript("onTrailingEnd", $this->prefix . "_onTrailingEnd");

        }

        public function RegisterProperties () {

            $this->RegisterPropertyInteger("Sensor1", null);
            $this->RegisterPropertyInteger("Sensor2", null);
            $this->RegisterPropertyInteger("Sensor3", null);

            $this->RegisterPropertyInteger("Sensor1Profile", 5);
            $this->RegisterPropertyInteger("Sensor2Profile", 5);
            $this->RegisterPropertyInteger("Sensor3Profile", 5);

            $this->RegisterPropertyInteger("Mode", 1);

            $this->RegisterPropertyInteger("SchwellwertMode", 1);

            $this->RegisterPropertyInteger("valueOn", 1);
            $this->RegisterPropertyInteger("valueOff", 0);

            $this->RegisterPropertyInteger("ScriptOn", null);
            $this->RegisterPropertyInteger("ScriptOff", null);

        }

        protected function checkSensorVars() {

            $sensor1 = $this->ReadPropertyInteger("Sensor1");
            $sensor2 = $this->ReadPropertyInteger("Sensor2");
            $sensor3 = $this->ReadPropertyInteger("Sensor3");

            $sensor1profil = $this->ReadPropertyInteger("Sensor1Profile");
            $sensor2profil = $this->ReadPropertyInteger("Sensor2Profile");
            $sensor3profil = $this->ReadPropertyInteger("Sensor3Profile");

            if ($sensor1 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 1", $this->Sensoren))) {

                    $sensor1link = $this->linkVar($sensor1, "Sensor 1", $this->Sensoren, 0, true);

                    $sensor1schwellwert = $this->checkVar("Sensor 1 Schwellwert", $this->getVarType($sensor1), "", "", 999);

                    $this->giveTresholdProfile($sensor1schwellwert, $sensor1profil);

                    $this->createOnChangeEvents(array($sensor1schwellwert . "|onTresholdChange", $sensor1 . "|onSensorChange"), $this->Events);

                } else {

                    if ($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)) != $sensor1) {
                        
                        $this->deleteObject($this->searchObjectByName("Sensor 1", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Sensor 1 Schwellwert"));
                        $this->deleteObject($this->searchObjectByName("onChange Sensor 1 Schwellwert", $this->Events));
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor1), $this->Events));

                        $sensor1link = $this->linkVar($sensor1, "Sensor 1", $this->Sensoren, 0, true);

                        $sensor1schwellwert = $this->checkVar("Sensor 1 Schwellwert", $this->getVarType($sensor1), "", "", 999);

                        $this->giveTresholdProfile($sensor1schwellwert, $sensor1profil);

                        $this->createOnChangeEvents(array($sensor1schwellwert . "|onTresholdChange", $sensor1 . "|onSensorChange"), $this->Events);

                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Sensor 1 Schwellwert"), $sensor1profil);

                    } 

                }
                

            } else {

                $this->deleteObject($this->searchObjectByName("Sensor 1", $this->Sensoren));
                $this->deleteObject($this->searchObjectByName("Sensor 1 Schwellwert"));
                $this->deleteObject($this->searchObjectByName("onChange Sensor 1 Schwellwert", $this->Events));
                $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor1), $this->Events));

            }

            if ($sensor2 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 2", $this->Sensoren))) {

                    $sensor2link = $this->linkVar($sensor2, "Sensor 2", $this->Sensoren, 0, true);

                    $sensor2schwellwert = $this->checkVar("Sensor 2 Schwellwert", $this->getVarType($sensor2), "", "", 999);

                    $this->giveTresholdProfile($sensor2schwellwert, $sensor2profil);

                    $this->createOnChangeEvents(array($sensor2schwellwert . "|onTresholdChange", $sensor2 . "|onSensorChange"), $this->Events);

                } else {

                    if ($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)) != $sensor2) {
                        
                        $this->deleteObject($this->searchObjectByName("Sensor 2", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Sensor 2 Schwellwert"));
                        $this->deleteObject($this->searchObjectByName("onChange Sensor 2 Schwellwert", $this->Events));
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor2), $this->Events));

                        $sensor2link = $this->linkVar($sensor2, "Sensor 2", $this->Sensoren, 0, true);

                        $sensor2schwellwert = $this->checkVar("Sensor 2 Schwellwert", $this->getVarType($sensor2), "", "", 999);

                        $this->giveTresholdProfile($sensor2schwellwert, $sensor2profil);

                        $this->createOnChangeEvents(array($sensor2schwellwert . "|onTresholdChange", $sensor2 . "|onSensorChange"), $this->Events);

                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Sensor 2 Schwellwert"), $sensor2profil);

                    }

                }
                

            } else {

                $this->deleteObject($this->searchObjectByName("Sensor 2", $this->Sensoren));
                $this->deleteObject($this->searchObjectByName("Sensor 2 Schwellwert"));
                $this->deleteObject($this->searchObjectByName("onChange Sensor 2 Schwellwert", $this->Events));
                $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor2), $this->Events));

            }

            if ($sensor3 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 3", $this->Sensoren))) {

                    $Sensor3link = $this->linkVar($sensor3, "Sensor 3", $this->Sensoren, 0, true);

                    $Sensor3schwellwert = $this->checkVar("Sensor 3 Schwellwert", $this->getVarType($sensor3), "", "", 999);

                    $this->giveTresholdProfile($Sensor3schwellwert, $Sensor3profil);

                    $this->createOnChangeEvents(array($Sensor3schwellwert . "|onTresholdChange", $sensor3 . "|onSensorChange"), $this->Events);

                } else {

                    if ($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)) != $sensor3) {
                        
                        $this->deleteObject($this->searchObjectByName("Sensor 3", $this->Sensoren));
                        $this->deleteObject($this->searchObjectByName("Sensor 3 Schwellwert"));
                        $this->deleteObject($this->searchObjectByName("onChange Sensor 3 Schwellwert", $this->Events));
                        $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor3), $this->Events));

                        $Sensor3link = $this->linkVar($sensor3, "Sensor 3", $this->Sensoren, 0, true);

                        $Sensor3schwellwert = $this->checkVar("Sensor 3 Schwellwert", $this->getVarType($sensor3), "", "", 999);

                        $this->giveTresholdProfile($Sensor3schwellwert, $Sensor3profil);

                        $this->createOnChangeEvents(array($Sensor3schwellwert . "|onTresholdChange", $sensor3 . "|onSensorChange"), $this->Events);

                    } else {

                        $this->giveTresholdProfile($this->searchObjectByName("Sensor 3 Schwellwert"), $sensor3profil);

                    }

                }
                

            } else {

                $this->deleteObject($this->searchObjectByName("Sensor 3", $this->Sensoren));
                $this->deleteObject($this->searchObjectByName("Sensor 3 Schwellwert"));
                $this->deleteObject($this->searchObjectByName("onChange Sensor 3 Schwellwert", $this->Events));
                $this->deleteObject($this->searchObjectByName("onChange " . IPS_GetName($sensor3), $this->Events));

            }

        }

        protected function setNeededProfiles () {
            return array("Lux", "Temperature_F", "Temperature_C", "Wattage");
        }

        protected function giveTresholdProfile ($tresholdVar, $tresholdVal) {

            // Grad_F
            if ($tresholdVal == 1) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_F_float");
                    $this->setIcon($tresholdVar, "Temperature");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_F_int");
                    $this->setIcon($tresholdVar, "Temperature");

                }

            }

            // Grad_C
            if ($tresholdVal == 2) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_float");
                    $this->setIcon($tresholdVar, "Temperature");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_int");
                    $this->setIcon($tresholdVar, "Temperature");

                }

            }

            // Lux
            if ($tresholdVal == 3) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_float");
                    $this->setIcon($tresholdVar, "Sun");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_int");
                    $this->setIcon($tresholdVar, "Sun");

                }

            }

            // Wattage
            if ($tresholdVal == 4) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_float");
                    $this->setIcon($tresholdVar, "Energy");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_int");
                    $this->setIcon($tresholdVar, "Energy");

                }

            }

            if ($tresholdVal == 5) {

                $this->addProfile($tresholdVar, $this->getVarProfile($tresholdVal));

            }

        }

        ###################################################################################################################################

        public function onAutomaticChange () {

            $automatik = $this->AutomatikVar;
            $automatikVal = GetValue($automatik);

            if ($automatikVal) {

                $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));
                $this->deleteObject($this->searchObjectByName("Nachlauf Timer"));

                IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), 0);
                IPS_SetScriptTimer($this->searchObjectByName("Trailing"), 0);
                IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);

            }

        }

        public function onStatusChange () {

            $var = $_IPS['VARIABLE'];
            $val = GetValue($var);

            $mode = $this->ReadPropertyInteger("Mode");
            $valueOn = $this->ReadPropertyInteger("valueOn");
            $valueOff = $this->ReadPropertyInteger("valueOff");

            $scriptOn = $this->ReadPropertyInteger("ScriptOn");
            $scriptOff = $this->ReadPropertyInteger("ScriptOff");

            if ($val) {

                if ($mode == 1) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), true);

                } else if ($mode == 2) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), false);

                } else if ($mode == 3) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), $valueOn);

                }

                if ($scriptOn != null) {
                    IPS_RunScript($scriptOn);
                } 

            } else {

                if ($mode == 1) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), false);

                } else if ($mode == 2) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), true);

                } else if ($mode == 3) {

                    $this->setAllInLinkList($this->searchObjectByName("Targets"), $valueOff);

                }

                if ($scriptOff != null) {
                    IPS_RunScript($scriptOff);
                } 

            }

        }

        public function onTresholdChange () {

            $this->onSensorChange();

        }

        public function onSensorChange ($fromtrailing = false) {

            //$senderVar = $_IPS['VARIABLE'];
            //$senderVal = GetValue($senderVar);
            $automatik = GetValue($this->AutomatikVar);
            $statusVar = $this->Status;
            $statusVal = GetValue($statusVar);

            $sensor1 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)));
            $sensor2 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)));
            $sensor3 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)));

            $sensor1schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 1 Schwellwert"));
            $sensor2schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 2 Schwellwert"));
            $sensor3schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 3 Schwellwert"));

            $trailingActive = $this->getValueIfPossible($this->searchObjectByName("Nachlauf aktiv"));

            $currentStatus = GetValue($this->searchObjectByName("Status"));

            if ($automatik) {

                $newStatus = false;

                if ($this->ReadPropertyInteger("SchwellwertMode") == 1) {

                    if ($sensor1schwellwert <= $sensor1 && $sensor2schwellwert <= $sensor2 && $sensor3schwellwert <= $sensor3) {

                        $newStatus = true;
    
                    }

                } else {

                    if ($sensor1schwellwert <= $sensor1 || $sensor2schwellwert <= $sensor2 || $sensor3schwellwert <= $sensor3) {

                        $newStatus = true;
    
                    }

                }

                if (!$fromtrailing) {

                    if ($trailingActive) {
                        return;
                    }                    

                    if ($newStatus) {

                        if (!$this->doesExist($this->searchObjectByName("Verzögerung Timer"))) {

                            $verzögerung = GetValue($this->searchObjectByName("Verzögerung"));
    
                            IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), $this->timestampToSeconds($verzögerung));
    
                            $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Clock");
    
                            $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Verzögerung Timer", null, "last", true);

                        }
    
                    } else {
    
                        $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));
    
                        IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);
    
                    }

                } 


            }

        }

        public function onDelayEnd () {

            SetValue($this->searchObjectByName("Nachlauf aktiv"), true);

            IPS_SetScriptTimer($this->searchObjectByName("Trailing"), 5);

            $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));

            SetValue($this->Status, true);

            IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);

            $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));
            IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));

            $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("onTrailingEnd")), "Nachlauf Timer", null, "last", true);

            $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("onTrailingEnd")), "Clock");

        }

        public function trailing () {

            $this->onSensorChange(true);

            $automatik = GetValue($this->AutomatikVar);
            $statusVar = $this->Status;
            $statusVal = GetValue($statusVar);

            $sensor1 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 1", $this->Sensoren)));
            $sensor2 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 2", $this->Sensoren)));
            $sensor3 = $this->getValueIfPossible($this->getTargetID($this->searchObjectByName("Sensor 3", $this->Sensoren)));

            $sensor1schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 1 Schwellwert"));
            $sensor2schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 2 Schwellwert"));
            $sensor3schwellwert = $this->getValueIfPossible($this->searchObjectByName("Sensor 3 Schwellwert"));

            $trailingActive = $this->getValueIfPossible($this->searchObjectByName("Nachlauf aktiv"));

            $currentStatus = GetValue($this->searchObjectByName("Status"));

            if ($automatik) {

                $newStatus = false;

                if ($this->ReadPropertyInteger("SchwellwertMode") == 1) {

                    if ($sensor1schwellwert <= $sensor1 && $sensor2schwellwert <= $sensor2 && $sensor3schwellwert <= $sensor3) {

                        $newStatus = true;
    
                    }

                } else {

                    if ($sensor1schwellwert <= $sensor1 || $sensor2schwellwert <= $sensor2 || $sensor3schwellwert <= $sensor3) {

                        $newStatus = true;
    
                    }

                }

            $nachlaufactive = GetValue($this->searchObjectByName("Nachlauf aktiv"));

            if ($newStatus && $nachlaufactive) {

                $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));
                IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));

            }

        }

        public function onTrailingEnd () {

            SetValue($this->searchObjectByName("Nachlauf aktiv"), false);
            SetValue($this->searchObjectByName("Status"), false);

            $this->deleteObject($this->searchObjectByName("Nachlauf Timer"));

        }

}

?>