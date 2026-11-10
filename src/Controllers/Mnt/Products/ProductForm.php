<?php

namespace Controllers\Mnt\Products;

use Controllers\PublicController;
use Views\Renderer;
use Utilities\Site;
use Dao\Store\Products as PrdDao;

const PRODUCT_FORM_VIEW = "mnt/products/productForm";
const PRODUCT_FORM_XSF_SEED = "85a0ed52-52c6-45b0-b641-b57a6262ddb6";
const PRODUCT_LIST_URL = "index.php?page=Mnt-Products-ProductList";

class ProductForm extends PublicController
{
    /*
    productId
    productName
    productDescription
    productPrice
    productImgUrl
    productStock
    productStatus
    */
    private $viewData = [];

    private $productId = 0;
    private $productName = "";
    private $productDescription = "";
    private $productPrice = 0;
    private $productImgUrl = "";
    private $productStock = 0;
    private $productStatus = 'ACT';

    private $mode = "NA";
    private $modeDscArr = [
        "DSP" => "Detalles de %s - %s",
        "INS" => "Crear nuevo Producto",
        "UPD" => "Actualizar %s - %s",
        "DEL" => "Eliminar %s - %s"
    ];

    private $hasErrors = false;
    private $errors = [];

    private $xsfToken = '';


    public function run(): void
    {

        /*
        ---> cargar Paremtros del Get
        --> si no es INS
        -->    cargar datos del modelo por el id
        --> end
        --> si es un postback (click en boton guardas)
        -->   cargar valores del POST
        ????  validar valores
            si no hay errores
                guardar valores
                enviar al listado despues de mostrar mensaje
            else
                llenar variables de errores
            end
        end
        --> prepara los datos para la vista
        --> renderizar la vista
        */
        $this->getQueryParams();
        if ($this->isPostBack()) {
            $this->getPostBody();
            if ($this->validatePostBody()) {
                $this->processPostBody();
            }
        }
        $this->prepareViewData();
        Renderer::render(
            PRODUCT_FORM_VIEW,
            $this->viewData
        );
    }

    private function registerError(string $error, bool $redirect): void
    {
        $this->hasErrors = true;
        $this->errors[] = $error;
        if ($redirect) {
            Site::redirectToWithMsg(PRODUCT_LIST_URL, implode("\n", $this->errors));
        }
    }
    private function getQueryParams(): void
    {
        $tmpMode = $_GET["mode"] ?? "NA";
        if ($tmpMode === "NA") {
            $this->registerError("El modo no fué enviada a este formulario", true);
        }
        if (!isset($this->modeDscArr[$tmpMode])) {
            $this->registerError("El modo no existe para este formulario", true);
        }
        $this->mode = $tmpMode;
        if ($this->mode !== "INS") {
            $tmpProductId = $_GET["productId"] ?? "NA";
            if ($tmpProductId === "NA") {
                $this->registerError("El Código del Producto no fue enviado este formulario", true);
            }
            if (!is_numeric($tmpProductId)) {
                $this->registerError("El Código del Producto no es válido", true);
            }
            $this->productId = intval($tmpProductId);
            $tmpProductoRow = PrdDao::getById($this->productId);
            if (!$tmpProductoRow) {
                $tmpProductoRow = [];
            }
            if (count($tmpProductoRow) === 0) {
                $this->registerError("Producto no existe", true);
            }

            $this->productId = $tmpProductoRow["productId"];
            $this->productName = $tmpProductoRow["productName"];
            $this->productDescription = $tmpProductoRow["productDescription"];
            $this->productPrice = $tmpProductoRow["productPrice"];
            $this->productImgUrl = $tmpProductoRow["productImgUrl"];
            $this->productStock = $tmpProductoRow["productStock"];
            $this->productStatus = $tmpProductoRow["productStatus"];
        }
    }
    private function getPostBody(): void
    {
        $this->validateXsfToken($_POST["xsftoken"] ?? "");
        $this->productId = intval($_POST["productId"] ?? "0");
        $this->productName = $_POST["productName"] ?? "";
        $this->productDescription = $_POST["productDescription"] ?? "";
        $this->productPrice = floatval($_POST["productPrice"] ?? "0");
        $this->productImgUrl = $_POST["productImgUrl"] ?? "";
        $this->productStock = intval($_POST["productStock"] ?? "0");
        $this->productStatus = $_POST["productStatus"] ?? "";
    }
    private function validatePostBody(): bool
    {
        //TODO: implement post Body Validations
        return true;
    }
    private function processPostBody(): void
    {
        switch ($this->mode) {
            case "INS":
                if (PrdDao::new(
                    $this->productName,
                    $this->productDescription,
                    $this->productPrice,
                    $this->productImgUrl,
                    $this->productStock,
                    $this->productStatus
                )) {
                    Site::redirectToWithMsg(PRODUCT_LIST_URL, "Producto Agregado Satisfactoriamente");
                }
                break;
            case "UPD":
                if (PrdDao::update(
                    $this->productName,
                    $this->productDescription,
                    $this->productPrice,
                    $this->productImgUrl,
                    $this->productStock,
                    $this->productStatus,
                    $this->productId
                )) {
                    Site::redirectToWithMsg(PRODUCT_LIST_URL, "Producto Actualizado Satisfactoriamente");
                }
                break;
            case "DEL":
                if (PrdDao::delete(
                    $this->productId
                )) {
                    Site::redirectToWithMsg(PRODUCT_LIST_URL, "Producto Eliminado Satisfactoriamente");
                }
                break;
        }
    }
    private function generarXsfToken()
    {
        $token =  hash("sha256", time() . rand(10000, 99999) . PRODUCT_FORM_XSF_SEED);
        $_SESSION["PRODUCT_FORM_XSF_T"] = $token;
        $this->xsfToken = $token;
    }
    private function validateXsfToken($token)
    {
        if (isset($_SESSION["PRODUCT_FORM_XSF_T"])) {
            if ($token !== $_SESSION["PRODUCT_FORM_XSF_T"]) {
                $this->registerError("Algo no esta bien, intente nuevamente!", true);
            }
        } else {
            $this->registerError("Algo no esta bien, intente nuevamente!", true);
        }
    }
    private function prepareViewData(): void
    {
        $this->viewData["mode"] = $this->mode;

        $this->viewData["modeDsc"] = sprintf(
            $this->modeDscArr[$this->mode],
            $this->productId,
            $this->productName
        );

        $this->viewData["productId"] = $this->productId;
        $this->viewData["productName"] = $this->productName;
        $this->viewData["productDescription"] = $this->productDescription;
        $this->viewData["productPrice"] = $this->productPrice;
        $this->viewData["productImgUrl"] = $this->productImgUrl;
        $this->viewData["productStock"] = $this->productStock;
        $this->viewData["productStatus"] = $this->productStatus;
        $this->viewData["readonly"] = "";
        $this->viewData["showGuardar"] = true;
        if (in_array($this->mode, ["DSP", "DEL"])) {
            $this->viewData["readonly"] = "readonly";
            $this->viewData["showGuardar"] = ($this->mode !== "DSP");
        }


        $this->viewData["action"] = "index.php?page=Mnt-Products-ProductForm&mode=" . $this->mode;
        if ($this->mode !== "INS") {
            $this->viewData["action"] .= "&productId=" . $this->productId;
        }
        $this->generarXsfToken();
        $this->viewData["xsftoken"] = $this->xsfToken;
    }
}
