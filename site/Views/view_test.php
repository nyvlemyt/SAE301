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




