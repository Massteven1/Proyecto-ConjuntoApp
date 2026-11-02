<?php
require_once '../models/cuotas.php';


class cuotasController {
    
    private Cuotas $cuota;

    private $datosResitentesPrueba = [
        ['id' => 1, 'nombre' => 'Residente 1', "area_residente_sesion" => 50],
        ['id' => 2, 'nombre' => 'Residente 2', "area_residente_sesion" => 60],
        ['id'=> 3, 'nombre'=> 'Residente 3', "area_residente_sesion" => 70]
    ];
    private $massivequotasModel;


    // el paper es para generar el formato completo de la cuota
    // constando de la siguiente estructura
    // datos del arrendatario (extraidos con el id del residente)
    // area del residente (extraida con la sesion del residente)
    // monto a pagar por el residente
    // coeficiente del residente
    // fecha inicial de la cuota
    // fecha final de la cuota
    // estado de la cuota (pendiente, pagada, etc.)
    private array $paper;

    public function __construct($presupuesto_mensual, $area_completa) {
        $this->cuota = new Cuotas();
        $this->cuota->setPresupuestoMensual($presupuesto_mensual);
        $this->cuota->setAreaCompleta($area_completa);
    }

    public function sp_generar_su_cuota($area_residente_sesion, $residente_id) {


        $this->cuota->setResidenteId($residente_id);
        $this->cuota->setAreaResidenteSesion($area_residente_sesion);
        $this->cuota->calcularCuota();

        $this->paper = Array(
            'arrendatario_id' => $this->cuota->getResidenteId(),
            'area_residente_sesion' => $area_residente_sesion,
            'monto' => $this->cuota->getMonto(),
            'coeficiente' => $this->cuota->getCoeficiente() * 100,
            'fecha_inicial' => $this->cuota->getFechaInicial(),
            'fecha_final' => $this->cuota->getFechaFinal(),
            'estado' => $this->cuota->getEstado()

        );
    }

    public function getPaper() {
        return $this->paper;
    }

    public function setAllPapersforResidentes(){
        $this->massivequotasModel = [];
        foreach ($this->datosResitentesPrueba as $residente) {
            $this->sp_generar_su_cuota(
                $residente["area_residente_sesion"],
                $residente["id"]
            );
            $this->massivequotasModel[] = $this->getPaper();
        }
    }

    public function getMassiveQuotasModel() {
        return $this->massivequotasModel;
    }
}