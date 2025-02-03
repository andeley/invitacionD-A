<?php

require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Sheets;

// 2. Obtener el ID de la invitación desde la URL.
$id_invitacion = isset($_GET['id']) ? $_GET['id'] : null;

header('Content-Type: application/json');

try {
    // Configura las credenciales de Google API
    $client = new Client();
    $client->setApplicationName('Google Sheets API PHP');
    $client->setScopes([Sheets::SPREADSHEETS]);
    $client->setAuthConfig('/var/www/html/credentials/beaming-ion-169223-8c296d2f50bc.json');
    $client->setAccessType('offline');

    $service = new Sheets($client);

    // ID de la hoja de cálculo (desde la URL de la hoja de cálculo)
    $spreadsheetId = '1oIaODUwnz3eIBF8I1mLBP-SZnsxe_JKF-XgekGJf4vM';
    $range = 'Hoja1!C6:H';  // Ajusta este rango según tu hoja de cálculo

    // Obtén los datos de la hoja de cálculo
    $response = $service->spreadsheets_values->get($spreadsheetId, $range);
    $values = $response->getValues();

    // Validar si la hoja está vacía
    if (empty($values)) {
        echo json_encode(['success' => false, 'message' => 'La hoja de cálculo está vacía', 'data' => []]);
        exit;
    }

    // Si la solicitud es POST, procesamos la confirmación
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Capturar los datos enviados por POST
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null) {
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error decoding JSON',
                    'raw' => file_get_contents('php://input'),
                    'error' => json_last_error_msg() // Mensaje de error de JSON
                ]);
                exit;
            }
        }

        // Verificar si la confirmación fue enviada
        if (isset($data['confirmacion'])) {
            $confirmacion = intval($data['confirmacion']); // Convertir a entero
            $rowIndex = -1;
            $cuposDisponibles = 0;

            // Buscar el índice de la fila correspondiente al ID de invitación
            foreach ($values as $index => $row) {
                if (trim($row[0]) == trim($id_invitacion)) {
                    $rowIndex = $index + 6; // +6 porque la hoja empieza en la fila 6
                    $cuposDisponibles = intval($row[3]); // Cupos están en la columna D
                    break;
                }
            }

            if ($rowIndex === -1) {
                echo json_encode(['success' => false, 'message' => 'No se encontró el ID de invitación']);
                exit;
            }

            // Validar que la confirmación no exceda los cupos disponibles
            if ($confirmacion > $cuposDisponibles) {
                echo json_encode([
                    'success' => false,
                    'message' => 'La cantidad confirmada no puede ser mayor que los cupos disponibles'
                ]);
                exit;
            }

            // Actualizar la columna G (campos_confirmados) con el valor confirmado
            $updateRange = "Hoja1!G$rowIndex"; // Columna G en la fila correspondiente
            $updateBody = new Google\Service\Sheets\ValueRange([
                'values' => [[strval($confirmacion)]] // Convertir la confirmación a string
            ]);

            $service->spreadsheets_values->update(
                $spreadsheetId,
                $updateRange,
                $updateBody,
                ['valueInputOption' => 'RAW']
            );

            echo json_encode(['success' => true, 'message' => 'Confirmación registrada correctamente']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se envió la confirmación']);
            exit;
        }
    }

    // Si la solicitud es GET, obtenemos los datos del grupo, nombres y cupos
    $result = ['success' => false, 'message' => 'No se encontraron datos para ese ID', 'data' => []];

    // Buscar datos del ID
    if ($id_invitacion) {
        foreach ($values as $row) {
            if ($row[0] == $id_invitacion) {  // ID en la primera columna
                $result = [
                    'success' => true,
                    'message' => 'Datos encontrados',
                    'data' => [
                        'nombres' => $row[2],
                        'cupos' =>  $row[3] // Cupos en la cuarta columna (debería ser la columna D, no la C)
                    ]
                ];
                break;
            }
        }
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
