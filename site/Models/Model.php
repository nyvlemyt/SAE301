<?php

require_once('Utils/API/vendor/autoload.php');

class Model {

    private $bd;

    private static $instance = null;

    private function __construct()
    {
        //Ajouter les informations de connexion à la base de données dans credentials.php
        include "Utils/credentials.php";
        $this->bd = new PDO("pgsql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        $this->bd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->bd->query("SET nameS 'utf8'");
    }

    public static function getModel()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

  
    public function recherche($expression) 
    {
        if (strlen($expression) < 3) {
            return "Les recherches doivent avoir trois caractères minimum";
        }

        // Exécute la requête SQL pour récupérer les données de la base de données
        $requete = $this->bd->prepare("SELECT * FROM titlebasics JOIN titleratings USING(tconst) WHERE originaltitle ~* :expression ORDER BY numvotes DESC LIMIT 30"); 
        $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        $requete->execute();
        $resultats_bdd = $requete->fetchAll(PDO::FETCH_ASSOC);

        require_once('Utils/API/vendor/autoload.php');
        $client = new \GuzzleHttp\Client();
        $donnees = [];

        // Parcours des résultats de la base de données
        foreach($resultats_bdd as $film_bdd) {
            try {
                // Fait une requête à l'API pour obtenir les données supplémentaires
                $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $film_bdd['tconst'] . '?language=fr-fr', [
                    'headers' => [
                        'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiI2NzAxNTJmZGQ1ZWYyMmUyYzdkNmRkZmQ1NzIyNzE3NyIsInN1YiI6IjY1OWQ2YmRiYjZjZmYxMDFhNjc0OWQyOSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.XMVnYm5EpfHU2S-X3FojIPw0CyNkvu8fEppBrw0Bt5s',
                        'accept' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() === 404) {
                    // Passe au film suivant si le film n'est pas trouvé dans l'API
                    continue;
                }

                if ($response->getStatusCode() === 200) {
                    $donnees_film_api = json_decode($response->getBody(), true);

                    // Vérifie si le film a un poster
                    if (isset($donnees_film_api['poster_path'])) {
                        // Fusionne les données de la base de données avec les données de l'API
                        $donnees_fusionnees = array_merge($film_bdd, $donnees_film_api);

                        $donnees[] = $donnees_fusionnees;
                    }
                }
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                // Gère l'exception si la requête échoue
                continue;
            }
        }
        
        return $donnees;
    }




    public function recherche_avancee($expression, $filtres)
    {
        if (strlen($expression) < 3) 
        {
            return "Les recherches doivent avoir trois caractères minimum";
        }

        if ($filtres['type'] == 'film')
        {
            $sql = "SELECT * FROM titlebasics JOIN titleratings USING(tconst) WHERE SIMILARITY(originaltitle, :expression) > 0.4"; 
        }
        elseif($filtres['type'] == 'personne')
        {
            $sql = "SELECT * FROM namebasics WHERE SIMILARITY(primaryname, :expression) > 0.5"; 
        }

        $firstIteration = true;
        foreach($filtres as $filtre => $val)
        {
            if ($firstIteration) 
            {
                $firstIteration = false;
                continue;
            }
            if ($val == "") {
                continue ;
            }
            $val = $this->bd->quote($val); 
            if ($filtre == 'genres') {
                $sql .= ' and ' . $filtre . '~*' . $val;
            }
            else {
            $sql .= ' and ' . $filtre . '=' . $val;
            }

        }

        if ($filtres['type'] == 'film') {
            $sql.= 'ORDER BY numvotes DESC' ;
        }

        $sql .= ';'; 
        $requete = $this->bd->prepare($sql);
        $requete->bindParam(":expression", $expression, PDO::PARAM_STR);
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }


    function recherche_commun($param1, $param2) {
        $query = '';
        $paramType = '';
    
        //Condition pour vérifier si ce sont des personnes
        if (strpos($param1, 'nm') === 0 && strpos($param2, 'nm') === 0) {
            $query = '
                SELECT DISTINCT tp.tconst, tb.primaryTitle
                FROM titleprincipals tp
                JOIN titlebasics tb ON tp.tconst = tb.tconst
                JOIN titleprincipals tp2 ON tp.tconst = tp2.tconst
                JOIN namebasics nb ON tp2.nconst = nb.nconst
                WHERE tp.nconst = :person1 AND tp2.nconst = :person2
            ';
            $paramType = 'people';
    
        // Autre condition pour vérifier si ce sont des films
        } elseif (strpos($param1, 'tt') === 0 && strpos($param2, 'tt') === 0) {
            // Les deux paramètres sont des films
            $query = '
                SELECT DISTINCT nb.nconst, nb.primaryName
                FROM titleprincipals tp
                JOIN namebasics nb ON tp.nconst = nb.nconst
                JOIN titleprincipals tp2 ON tp.nconst = tp2.nconst
                JOIN titlebasics tb ON tp2.tconst = tb.tconst
                WHERE tp.tconst = :movie1 AND tp2.tconst = :movie2
            ';
            $paramType = 'movies';
        } else {
            echo "Paramètres invalides ! Veuillez mettre des paramètres correct svp";
            return;
        }
    
        $stmt = $this->bd->prepare($query);
    
        if ($paramType === 'people') {
            $stmt->bindParam(':person1', $param1, PDO::PARAM_STR);
            $stmt->bindParam(':person2', $param2, PDO::PARAM_STR);
        } elseif ($paramType === 'movies') {
            $stmt->bindParam(':movie1', $param1, PDO::PARAM_STR);
            $stmt->bindParam(':movie2', $param2, PDO::PARAM_STR);
        }
    
        $stmt->execute();
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if ($paramType === 'people') {
            echo "Ensemble des films en commun :";
        } elseif ($paramType === 'movies') {
            echo "Ensemble des personnes en commun :";
        }
    
        return $result;
    }

    public function getMoviesInfo($number) {
        $sql = "SELECT tb.tconst, tb.primarytitle, tr.averagerating, tr.numvotes
                FROM titlebasics tb
                JOIN titleratings tr ON tb.tconst = tr.tconst
                WHERE tb.startyear = EXTRACT(YEAR FROM CURRENT_DATE) - 1
                AND tb.titletype = 'movie'
                ORDER BY tr.numvotes DESC
                LIMIT :number ;";
        $requete = $this->bd->prepare($sql);
        $requete->bindParam(":number", $number, PDO::PARAM_INT);
        $requete->execute();
        $result = $requete->fetchAll(PDO::FETCH_ASSOC);

        require_once('Utils/API/vendor/autoload.php');
            
            $client = new \GuzzleHttp\Client();

        $donnees = [];

        foreach($result as $film) {
            $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $film['tconst'] . '?language=fr-fr', [
              'headers' => [
                'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiI2NzAxNTJmZGQ1ZWYyMmUyYzdkNmRkZmQ1NzIyNzE3NyIsInN1YiI6IjY1OWQ2YmRiYjZjZmYxMDFhNjc0OWQyOSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.XMVnYm5EpfHU2S-X3FojIPw0CyNkvu8fEppBrw0Bt5s',
                'accept' => 'application/json',
              ],
            ]);

            $data = json_decode($response->getBody(), true);
            $data['tconst'] = $film['tconst'];
            $data['primarytitle'] = $film['primarytitle'];
            $data['averagerating'] = $film['averagerating'];
            $data['numvotes'] = $film['numvotes'];
            array_push($donnees, $data);
        }
        return $donnees;
    }

    public function graphe2($expression, $expression2)
    {
        // Recherche l'acteur le plus populaire correspondant à l'expression
        $actorRes1= $this->nom($expression);
        $actorRes2= $this->nom($expression2); 
    
        // Initialise un tableau pour stocker les résultats finaux
        $finalResults = [];
    
        if (!empty($actorResults)) {
            // Supposons que le résultat contient un seul acteur le plus populaire
            $actor1 = $actorRes1[0];
            $actor2 = $actorRes2[0];
            
            // Utilise l'ID de cet acteur pour trouver tous les films associés
            $filmsResults = $this->trouver($actor1['nconst'], $actor2['nconst']);
            
            // Prépare les résultats finaux
            $finalResults = [
                'actor 1' => $actor1,
                'actor 2' => $actor2,
                'films' => $filmsResults
            ];
        }

        return $finalResults;

        //$requete = $this->bd->prepare("SELECT tconst FROM titleprincipals WHERE nconst= 'nm0000226';");
       //// $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        //$requete->execute();
        //$resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        //return $resultat;
    }
    public function graphe($expression)
    {
        
        // Recherche l'acteur le plus populaire correspondant à l'expression
        $actorRes1= $this->nom($expression); 
    
        // Initialise un tableau pour stocker les résultats finaux
        $finalResults = [];
    
        if (!empty($actorResults)) {
            // Supposons que le résultat contient un seul acteur le plus populaire
            $actor1 = $actorRes1[0];
            
            
            // Utilise l'ID de cet acteur pour trouver tous les films associés
            $filmsResults = $this->films($actor1['nconst']);
            
            // Prépare les résultats finaux
            $finalResults = [
                'actor 1' => $actor1,
                'films' => $filmsResults
            ];
        }

        return $finalResults;

        //$requete = $this->bd->prepare("SELECT tconst FROM titleprincipals WHERE nconst= 'nm0000226';");
       //// $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        //$requete->execute();
        //$resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        //return $resultat;
    }

    public function rapprochementNom($expression1, $expression2)
    {
        $source= $this->nom($expression1);
        $target= $this->nom($expression2);
        // Exécution du script Python et capture de la sortie
        // Construire la commande pour exécuter le script Python avec les arguments source et target
        $$scriptPath = '../../scripts/rapprochement.py'; 
        $command = escapeshellcmd("python3 $scriptPath $source $target");
        exec($command, $output, $resultaVar);
        

        // Convertir la sortie JSON en tableau PHP
        $path = json_decode($output, true); // Le second paramètre à true pour obtenir un tableau associatif
        $personIds = array_filter($path, function($id) { return strpos($id, 'nm') === 0; });
        $titleIds = array_filter($path, function($id) { return strpos($id, 'tt') === 0; });


        // Fonction pour préparer et exécuter une requête
        // Récupérer les informations pour tconst
        $titleDetails = execution($tconsts, true);
        // Récupérer les informations pour nconst
        $nameDetails = execution($nconsts, false);

        return $this->alternerTableaux($nameDetails,$titleDetails); 
    }

    public function rapprochementFilm($expression1, $expression2)
    {
        $source= $this->film($expression1);
        $target= $this->film($expression2);
        // Exécution du script Python et capture de la sortie
        // Construire la commande pour exécuter le script Python avec les arguments source et target
        $$scriptPath = '../../scripts/rapprochement.py'; 
        $command = escapeshellcmd("python3 $scriptPath \'$source\' \'$target\'");
        exec($command, $output, $resultaVar);

        // Convertir la sortie JSON en tableau PHP
        $path = json_decode($output, true); // Le second paramètre à true pour obtenir un tableau associatif

        $personIds = array_filter($path, function($id) { return strpos($id, 'nm') === 0; });
        $titleIds = array_filter($path, function($id) { return strpos($id, 'tt') === 0; });


        // Fonction pour préparer et exécuter une requête
        // Récupérer les informations pour tconst
        $titleDetails = execution($tconsts, true);
        // Récupérer les informations pour nconst
        $nameDetails = execution($nconsts, false);
        
        return $this->alternerTableaux($titleDetails,$nameDetails); 
    }

    function execution($ids, $isTitle = true) 
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        if ($isTitle) {
            $query = "SELECT tconst, originaltitle FROM resultats_intermediaires WHERE tconst IN ($placeholders)";
        } else {
            $query = "SELECT nconst, primaryname FROM resultats_intermediaires WHERE nconst IN ($placeholders)";
        }
    
        $requete = $this->bd->prepare($query);
        $stmt->execute(array_values($ids)); // Utilisez array_values pour ré-indexer les clés si nécessaire
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }

    function alternerTableaux(array $tab1, array $tab2) {
        $result = [];
        $maxLength = max(count($tab1), count($tab2));
    
        for ($i = 0; $i < $maxLength; $i++) {
            if (isset($tab1[$i])) {
                $result[] = $tab1[$i];
            }
            if (isset($tab2[$i])) {
                $result[] = $tab2[$i];
            }
        }
    
        return $result;
    }

    public function nom2($expression)
    {
        $requete = $this->bd->prepare("SELECT nb.nconst, nb.primaryname, SUM(tr.numvotes) AS popularity_score
        FROM namebasics nb
        CROSS JOIN LATERAL UNNEST(string_to_array(nb.knownfortitles, ',')) AS kft(tconst)
        JOIN titleratings tr ON kft.tconst = tr.tconst
        WHERE nb.primaryname = :expression AND cardinality(string_to_array(knownfortitles, ',')) > 2 AND (
            'actor' = any(string_to_array(primaryprofession, ','))
            OR 'actress' = any(string_to_array(primaryprofession, ','))
            )
        GROUP BY nb.nconst, nb.primaryname
        ORDER BY popularity_score DESC limit(1);"); 
        $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }

    public function film($expression)
    {
        $requete = $this->bd->prepare("SELECT tconst FROM titlebasics WHERE originaltitle= :expression");
        $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }

    public function nom($expression)
    {
        $requete = $this->bd->prepare("SELECT * from get_popular_actor(':expression');"); 
        $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }
    
    public function test_films($expression)
    {
        $requete = $this->bd->prepare("WITH UniqueTitles AS (
            SELECT
                tb.tconst,
                COALESCE(te.parenttconst, tb.tconst) AS effective_tconst,
                CASE
                    WHEN te.parenttconst IS NOT NULL THEN (SELECT originaltitle FROM titlebasics WHERE tconst = te.parenttconst)
                    ELSE tb.originaltitle
                END AS title,
                tb.genres
            FROM titlebasics tb
            LEFT JOIN titleepisode te ON tb.tconst = te.tconst
            JOIN titleprincipals tp ON tb.tconst = tp.tconst
            WHERE tp.nconst = '$expression'
        )
        SELECT DISTINCT
            effective_tconst,
            title,
            genres
        FROM UniqueTitles
        ORDER BY effective_tconst;");
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }

    public function test_trouver($expression, $expression2)
    {
        $requete = $this->bd->prepare("WITH UniqueTitlesExpr1 AS (
            SELECT
                COALESCE(te.parenttconst, tb.tconst) AS effective_tconst,
                CASE
                    WHEN te.parenttconst IS NOT NULL THEN (SELECT originaltitle FROM titlebasics WHERE tconst = te.parenttconst)
                    ELSE tb.originaltitle
                END AS title,
                tb.genres
            FROM titlebasics tb
            LEFT JOIN titleepisode te ON tb.tconst = te.tconst
            JOIN titleprincipals tp ON tb.tconst = tp.tconst
            WHERE tp.nconst = '$expression'
        ),
        UniqueTitlesExpr2 AS (
            SELECT
                COALESCE(te.parenttconst, tb.tconst) AS effective_tconst,
                CASE
                    WHEN te.parenttconst IS NOT NULL THEN (SELECT originaltitle FROM titlebasics WHERE tconst = te.parenttconst)
                    ELSE tb.originaltitle
                END AS title,
                tb.genres
            FROM titlebasics tb
            LEFT JOIN titleepisode te ON tb.tconst = te.tconst
            JOIN titleprincipals tp ON tb.tconst = tp.tconst
            WHERE tp.nconst = '$expression2'
        )
        SELECT DISTINCT
            ut1.effective_tconst,
            ut1.title,
            ut1.genres
        FROM UniqueTitlesExpr1 ut1
        JOIN UniqueTitlesExpr2 ut2 ON ut1.effective_tconst = ut2.effective_tconst
        ORDER BY ut1.effective_tconst;");
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
        
    }

}