<?php
// save_inscription.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Fichier de stockage
$dataFile = 'data/inscriptions.json';

// Initialiser le fichier s'il n'existe pas
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_PRETTY_PRINT));
}

// Lire les inscriptions
function getInscriptions() {
    global $dataFile;
    $data = file_get_contents($dataFile);
    return json_decode($data, true) ?: [];
}

// Sauvegarder les inscriptions
function saveInscriptions($inscriptions) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($inscriptions, JSON_PRETTY_PRINT));
}

// GET - Récupérer toutes les inscriptions
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['export'])) {
    $inscriptions = getInscriptions();
    echo json_encode($inscriptions);
    exit;
}

// GET - Exporter CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $inscriptions = getInscriptions();
    if (empty($inscriptions)) {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Aucune donnée']);
        exit;
    }
    
    $csv = "ID;Date;Source;Motivation;Import;Produits;Budget;Sexe;Commune;WhatsApp;Types\n";
    foreach ($inscriptions as $i) {
        $csv .= '"' . implode('";"', [
            $i['id'],
            $i['date'],
            $i['source'],
            str_replace('"', '""', $i['motivation']),
            $i['import'],
            str_replace('"', '""', $i['produits']),
            $i['budget'],
            $i['sexe'],
            $i['commune'] ?? '',
            $i['whatsapp'],
            implode(', ', $i['types'] ?? [])
        ]) . "\"\n";
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inscriptions_zouwor_' . date('Y-m-d') . '.csv');
    echo $csv;
    exit;
}

// POST - Ajouter une inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['whatsapp']) || empty($input['motivation'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Champs requis manquants']);
        exit;
    }
    
    $inscriptions = getInscriptions();
    $newInscription = $input;
    $newInscription['id'] = time() . rand(100, 999);
    $newInscription['date'] = date('Y-m-d H:i:s');
    $newInscription['whatsapp'] = preg_replace('/[^0-9+]/', '', $newInscription['whatsapp']);
    
    $inscriptions[] = $newInscription;
    saveInscriptions($inscriptions);
    
    echo json_encode(['success' => true, 'inscription' => $newInscription]);
    exit;
}

// DELETE - Supprimer une inscription
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID requis']);
        exit;
    }
    
    $inscriptions = getInscriptions();
    $inscriptions = array_values(array_filter($inscriptions, function($i) use ($id) {
        return $i['id'] != $id;
    }));
    saveInscriptions($inscriptions);
    
    echo json_encode(['success' => true]);
    exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
?>