<?php


namespace App\Services;


use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Random\RandomException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class UtilityServices
{
    private $entity;

    private ManagerRegistry $managerRegistry;
    private ParameterBagInterface $params;
    private SerializerInterface $serializer;

    public function __construct(ManagerRegistry $managerRegistry, ParameterBagInterface $params, SerializerInterface $serializer)
    {
        $this->managerRegistry = $managerRegistry;
        $this->params = $params;
        $this->serializer = $serializer;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function indexBy($array, $getter = "getCode", string $caseMode = null)
    {
        $indexed = [];
        foreach ($array as $item) {
            if ($caseMode == 'lower') {
                $indexed[strtolower($item->$getter())] = $item;
            } elseif ($caseMode == 'upper') {
                $indexed[strtoupper($item->$getter())] = $item;
            } else {
                $indexed[$item->$getter()] = $item;
            }
        }
        return $indexed;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity): void
    {
        $this->entity = $entity;
    }

    public function getMethods()
    {
        $reflection = new ReflectionClass($this->entity);
        return array_filter($reflection->getMethods(), function ($method) {
            return strpos($method->name, 'get') === 0;
        });
    }

    public function setMethods()
    {
        $reflection = new ReflectionClass($this->entity);
        return array_filter($reflection->getMethods(), function ($method) {
            return strpos($method->name, 'set') === 0;
        });
    }

    public function getAttributes()
    {
        $reflection = new ReflectionClass($this->entity);
        $attributes = $reflection->getProperties();
        return array_filter($attributes, function ($attribute) {
            return $attribute->getName() !== 'id';
        });
    }

    public function getSetterMethod($attribute)
    {
        $reflection = new ReflectionClass($this->entity);
        $setter = 'set' . ucfirst($attribute);
        if ($reflection->hasMethod($setter)) {
            return $setter;
        }
        return null;
    }


    /**
     * @throws \ReflectionException
     */
    public function generateCode($object)
    {

        $reflect = new \ReflectionClass($object);
        $entityName = $reflect->getShortName();
        $entity = strtoupper(substr(preg_replace('#[aeoui]+#i', '', $entityName), 0, 3));
        return $entity . strtoupper(uniqid());

    }

    public function generatePrefix(string $designation): string
    {
        return strtoupper(substr(preg_replace('#[aeoui]+#i', '', $designation), 0, 3));
    }

    /**
     * @throws Exception
     */
    public function xslx($file, $twoHeader = false)
    {
        $spreadsheet = IOFactory::load($file); // Here we are able to read from the excel file
        $spreadsheet->getActiveSheet()->removeRow(1); // I added this to be able to remove the first file line
        if ($twoHeader)
            $spreadsheet->getActiveSheet()->removeRow(1);

        // here, the read data is turned into an array
        return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    }

    public function exportExcel(array $data, array $header, $filename): StreamedResponse
    {
        // Créer un nouveau fichier Excel en mémoire
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header);
        $sheet->fromArray($data, null, 'A2');

        // Créer un objet Writer pour exporter le fichier Excel
        $writer = new Xlsx($spreadsheet);

        // Créer une réponse stream pour envoyer le contenu du fichier au navigateur
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        // Configurer les en-têtes de la réponse pour indiquer un téléchargement de fichier
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }


    public function extractDataFromSheet(Spreadsheet $spreadsheet, int $sheetNumber = 0): array
    {
        $worksheet = $spreadsheet->getSheet($sheetNumber);
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();


        $data = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = array();
            for ($column = 'A'; $column <= $highestColumn; $column++) {
                $cellValue = $worksheet->getCell($column . $row)->getValue();
                $rowData[] = $cellValue;
            }
            $data[] = $rowData;
        }
        return $data;
    }

    // extractDataFromWorkSheet
    public function extractDataFromWorkSheet(Worksheet $worksheet): array
    {
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        $data = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = array();
            for ($column = 'A'; $column <= $highestColumn; $column++) {
                $cellValue = $worksheet->getCell($column . $row)->getValue();
                $rowData[] = $cellValue;
            }
            $data[] = $rowData;
        }

        return $data;
    }


    // serialize list Entity
    public function serializer($entityList, string $groups): string
    {
        return $this->serializer->serialize($entityList, 'json', [
            'groups' => $groups, // Nom du groupe de sérialisation
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object?->getDesignation();
            },
        ]);
    }

    // isUrlValid
    public function isUrlValid($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @throws RandomException
     */
    public function generateToken(int $sectionNumber = 4): string
    {
        $token = "";

        for ($i = 0; $i < $sectionNumber; $i++) {
            $token .= bin2hex(random_bytes(3));
            if ($i < $sectionNumber - 1) {
                $token .= "-";
            }
        }

        return $token;
    }


}