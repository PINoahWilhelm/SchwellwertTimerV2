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

        protected function setGlobalized () {

            return array("Targets", "Sensoren", "Status", "Events");

        }

        protected function setExcludedHide() {

            return array($this->AutomatikVar, $this->SperreVar, $this->detailsVar, $this->Status);

        }

        protected function setExcludedShow () {

            return array("script", "instance");

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

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_F_int");

                }

            }

            // Grad_C
            if ($tresholdVal == 2) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_float");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Temperature_C_int");

                }

            }

            // Lux
            if ($tresholdVal == 3) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_float");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Lux_int");

                }

            }

            // Wattage
            if ($tresholdVal == 4) {

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("float")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_float");

                }

                if ($this->getVarType($tresholdVar) == $this->varTypeByName("int")) {

                    $this->addProfile($tresholdVar, $this->prefix . ".Wattage_int");

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

            }

        }

        public function onStatusChange () {

            echo "Status changed";

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

                        $verzögerung = GetValue($this->searchObjectByName("Verzögerung"));
    
                        IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), $this->timestampToSeconds($verzögerung));
    
                        $this->setIcon($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Clock");
    
                        $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("DelayEnd")), "Verzögerung Timer", null, "last", true);
    
                    } else {
    
                        $this->deleteObject($this->searchObjectByName("Verzögerung Timer"));
    
                        IPS_SetScriptTimer($this->searchObjectByName("DelayEnd"), 0);
    
                    }

                } else {

                    $nachlaufactive = GetValue($this->searchObjectByName("Nachlauf aktiv"));

                    if ($newStatus && $nachlaufactive) {

                        $nachlauf = GetValue($this->searchObjectByName("Nachlauf"));
                        IPS_SetScriptTimer($this->searchObjectByName("onTrailingEnd"), $this->timestampToSeconds($nachlauf));
                        $this->linkVar($this->getFirstChildFrom($this->searchObjectByName("onTrailingEnd")), "Nachlauf Timer", null, "last", true);

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

        }

        public function trailing () {

            $this->onSensorChange(true);

        }

        public function onTrailingEnd () {

            SetValue($this->searchObjectByName("Nachlauf aktiv"), false);
            SetValue($this->searchObjectByName("Status"), false);

            $this->deleteObject($this->searchObjectByName("Nachlauf Timer"));

        }

}

?>