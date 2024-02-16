<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="Content/css/bootstrap.min.css" rel="stylesheet">
    <title>Resultat</title>
</head>
<body>

    <div id="page">
<h1>Resultat</h1>

<?php

shell_exec('python3 /var/www/html/GitHub/SAE301/scripts/api.py');

$source = 'nm0614165'; // Exemple d'identifiant source
$target = 'nm0000226'; // Exemple d'identifiant cible

// Construire l'URL de l'API
$url = "http://127.0.0.1:5000/shortest_path?source=$source&target=$target";

// Utiliser file_get_contents pour appeler l'API
$response = file_get_contents($url);
$result = json_decode($response, true);

// Afficher le résultat
echo "<pre>";
print_r($result);
echo "</pre>";




#####$source= escapeshellarg('nm0614165');
#####$target= escapeshellarg('nm0000226');
#####$scriptPath = '/var/www/html/GitHub/SAE301/scripts/rapprochement.py'; 
#####$command = escapeshellcmd("/usr/bin/python3 $scriptPath $source $target");
#####
######exec("/usr/bin/python3 ../../scripts/rapprochement.py nm0614165 nm0000226", $output, $resultVar);
######print_r($output, true);
######print($output); 
######print($output.$maxLength[0]);
#####
#####$output = shell_exec("/usr/bin/python3 " . $scriptPath . $source . $target); 
#####foreach($output as $key => $val)
#####{
#####    echo "<pre>";
#####    echo $output[$key]; 
#####    echo "</pre>"; 
#####}
######$result = json_decode(file_get_contents('/var/www/html/GitHub/SAE301/scripts/result.json'), true);
#####
#####echo "<pre>"; 
#####print_r($output); 
#####echo "</pre>";
#####echo "Code de retour : $returnVar";
#####
#####// Chemin vers le fichier JSON généré par le script Python
#####$filePath = '/var/www/html/GitHub/SAE301/scripts/result.json';
#####
#####// Lire le contenu du fichier JSON
#####$jsonContent = file_get_contents($filePath);
#####
#####// Décoder le contenu JSON en tableau PHP
#####$resultData = json_decode($jsonContent, true);
#####
#####// Vérifier si le résultat contient un chemin
#####//if (isset($resultData[0])) {
#####    echo "<h2>Chemin trouvé :</h2>";
#####    echo "<pre>";
#####    print_r($resultData['path']);
#####    echo "</pre>";
#####//} elseif (isset($resultData['error'])) {
#####    // Gérer l'erreur si les arguments ne sont pas fournis ou une autre erreur survient
#####    echo "<h2>Erreur :</h2>";
#####    echo "<p>" . $resultData['error'] . "</p>";
#####//} else {
#####    // Gérer le cas où le résultat est inattendu
#####    echo "<h2>Résultat inattendu</h2>";
#####    echo "<pre>";
#####    print_r($resultData);
#####    echo "</pre>";
#####//}


#echo nl2br("result: " . "\n");
#echo "<pre>";
#print_r($result[0]); 
#echo "</pre>";
##
##
#$jsonString = implode("", $result);
#$path = json_decode($jsonString, true);
#
#// Le second paramètre à true pour obtenir un tableau associatif
#print_r($path[0]);
#
#$personIds = array_filter($path, function($id) { return strpos($id, 'nm') === 0; });
#$titleIds = array_filter($path, function($id) { return strpos($id, 'tt') === 0; });
#
#// Afficher les tableaux filtrés
#print_r($personIds);
#print_r($titleIds);
#
#
#// Convertir la sortie JSON en tableau PHP
#$path = json_decode($output, true); 
#
#echo "hehe";
#print_r($path);
#
#$source = 'nm0614165';
#$target = 'nm0000226';
#$command = escapeshellcmd("../../scripts/rapprochement.py \'$source\' '$target'");
#$output = shell_exec('python' . $command);
#
#$path = json_decode($output, true); 
#print_r($output);
#echo "hoho";
#print_r($path);
?>


<div><?php echo $data[0]?></div>
<div> 
    
    <?php foreach($data as $key => $ligne) : ?>
    <ul>
        <?php // Supposons que $key peut être une date ou une autre propriété significative pour chaque $ligne ?>
        <li><strong><?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?></strong></li>
        
        <?php if($key =='films'): ?>
            <ul>
        <?php foreach($ligne as $k => $val) : ?> 
            <li>
                <?php foreach($val as $c => $v) : ?>
                    <li>
                        <?= htmlspecialchars($v, ENT_QUOTES, 'UTF-8') ?>
                    </li>
                <?php endforeach; ?>
                </li>
        <?php endforeach; ?>
                </ul>
        <?php else : ?>
            <?php foreach($ligne as $k => $val) : ?> 
            <li>
                <?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>
            </li>
            <?php endforeach; ?>
        <?php endif; ?>
            
    </ul>
    <?php endforeach; ?>

   
</div>
        </body>
        </html>






