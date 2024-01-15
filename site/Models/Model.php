<?php

require_once('Utils/API/vendor/autoload.php');

class Model {

    private $bd;

    private static $instance = null;

    private function __construct()
    {
        include "./Utils/connexion.php";
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

    public function recherche($expression) {
    if (strlen($expression) < 3) {
        return "Les recherches doivent avoir trois caractères minimum";
    }

    $requete = $this->bd->prepare("SELECT * FROM titlebasics JOIN titleratings USING(tconst) WHERE originaltitle ~* :expression ORDER BY averagerating DESC"); 
    $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
    $requete->execute();
    $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);

    $client = new \GuzzleHttp\Client();

    foreach ($resultat as &$ligne) {
        $response = $client->request('GET', 'https://api.themoviedb.org/3/find/' . $ligne['tconst'] . '?external_source=imdb_id&language=php', [
            'headers' => [
                'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiI2NzAxNTJmZGQ1ZWYyMmUyYzdkNmRkZmQ1NzIyNzE3NyIsInN1YiI6IjY1OWQ2YmRiYjZjZmYxMDFhNjc0OWQyOSIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.XMVnYm5EpfHU2S-X3FojIPw0CyNkvu8fEppBrw0Bt5s',
                'accept' => 'application/json',
            ],
        ]);

        $result = $response->getBody();
        $donnee = json_decode($result, true);

        if (isset($donnee['movie_results'][0]['poster_path'])) {
            $poster_path = $donnee['movie_results'][0]['poster_path'];
        } elseif ((isset($donnee['tv_results'][0]['poster_path']))) {
            $poster_path = $donnee['tv_results'][0]['poster_path'];
        } else {
            $poster_path = 0;
        }

        if (isset($poster_path)) {
        $ligne['poster_path'] = $poster_path;
        }
    }

    return $resultat;

    }


    /*
    public function recherche($expression) 
    {
        if (strlen($expression) < 3) {
            return "Les recherches doivent avoir trois caractères minimum";
        }
        $requete = $this->bd->prepare("SELECT * FROM titlebasics JOIN titleratings USING(tconst) WHERE originaltitle ~* :expression ORDER BY averagerating DESC"); 
        $requete->bindValue(":expression", "$expression", PDO::PARAM_STR);
        $requete->execute();
        $resultat = $requete->fetchAll(PDO::FETCH_ASSOC);
        return $resultat;
    }
    */
}