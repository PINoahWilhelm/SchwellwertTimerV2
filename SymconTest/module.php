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

            $switches = $this->createSwitches(array("Automatik||false", "Sperre||false", "Status||false"));

            $targets = $this->checkFolder("Targets");
            $sensoren = $this->checkFolder("Sensoren");

            $this->createOnChangeEvents(array($this->AutomatikVar . "|onAutomaticChange", $this->Status . "|onStatusChange"), $this->Events);

            $this->hide($targets);
            $this->hide($sensoren);

            $this->checkSensorVars();

        }

        public function CheckScripts () {

            // Scripts checken -und erstellen


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

                    }

                }
                

            } else {



            }

            if ($sensor2 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 2"))) {

                    $sensor2link = $this->linkVar($sensor2, "Sensor 2", $this->Sensoren, 0, true);
                    
                    $sensor2schwellwert = $this->checkVar("Sensor 2 Schwellwert", $this->getVarType($sensor2), "");

                    $this->giveTresholdProfile($sensor2schwellwert, $sensor2profil);

                    $this->createOnChangeEvents(array($sensor2schwellwert . "|onTresholdChange", $sensor2 . "|onSensorChange"), $this->Events);

                }
                

            }

            if ($sensor3 != null) {

                if (!$this->doesExist($this->searchObjectByName("Sensor 3"))) {

                    $sensor3link = $this->linkVar($sensor3, "Sensor 3", $this->Sensoren, 0, true);
                    
                    $sensor3schwellwert = $this->checkVar("Sensor 3 Schwellwert", $this->getVarType($sensor3), "");

                    $this->giveTresholdProfile($sensor3schwellwert, $sensor3profil);

                    $this->createOnChangeEvents(array($sensor3schwellwert . "|onTresholdChange", $sensor3 . "|onSensorChange"), $this->Events);

                }
                

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

        }

        public function onStatusChanged () {

        }

        public function onTresholdChange () {

        }

        public function onSensorChange () {

        }

}

?>