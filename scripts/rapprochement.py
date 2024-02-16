import sys
import pickle
import networkx as nx
import json
import psycopg2
import concurrent.futures


# Chemin vers le fichier où votre graphe est sauvegardé
graph_path = '/var/www/html/GitHub/SAE301/scripts/graph.pickle'
#graph_path = './graph.pickle'

# Charger le graphe sérialisé
with open(graph_path, 'rb') as f:
    G_loaded = pickle.load(f)

def find_shortest_path(graph, source, target):
    """
    Trouve et retourne le chemin le plus court entre deux nœuds dans un graphe
    en utilisant l'algorithme de Dijkstra.

    :param graph: Le graphe NetworkX chargé.
    :param source: L'identifiant du nœud source.
    :param target: L'identifiant du nœud cible.
    :return: Une liste des nœuds formant le chemin le plus court.
    """
    try:
        # Calcul du chemin le plus court
        path = nx.shortest_path(graph, source=source, target=target)
        return path
    except nx.NetworkXNoPath:
        #print(f"Aucun chemin trouvé entre {source} et {target}.")
        return None
    except Exception as e:
        #print(f"Erreur lors de la recherche du chemin : {e}")
        return None


# Initialisation d'un dictionnaire pour stocker les résultats
result_data = []
result_data2 = {}
if len(sys.argv) > 2:
    source = sys.argv[1]  # Identifiant de la source
    target = sys.argv[2]  # Identifiant de la cible
    
    # Trouver le chemin le plus court
    path = find_shortest_path(G_loaded, source, target)
    
    # Ajouter les données au dictionnaire
    result_data.append(source)
    result_data.append(target)
    result_data.append(path)  # Inclure le chemin trouvé dans les données de résultat
    result_data2['source'] = source
    result_data2['target'] = target
    result_data2['path'] = path # Inclure le chemin trouvé dans les données de résultat
else:
    # Si les arguments ne sont pas fournis, retourne un message d'erreur
    result_data.append("Source and target arguments are required.")
    result_data2['error'] = "Source and target arguments are required."

print(result_data)
print(result_data2)

# Écrire les données résultantes dans un fichier JSON
with open('/var/www/html/GitHub/SAE301/scripts/result.json', 'w') as file:
    json.dump(result_data, file)
    json.dump(result_data2, file)


#   Afficher resultat dans terminal 

# Paramètres de connexion à la base de données
host = "localhost"
dbname = "sae"
user = "melvyn"
password = "4774"
conn_string = f"host={host} dbname={dbname} user={user} password={password}"

if path:
    print("Chemin le plus court trouvé :", " -> ".join(path))


def fetch_node_details(node_id):
    """
    Fonction pour récupérer les détails d'un nœud (titre ou nom) basé sur son identifiant.
    """
    with psycopg2.connect(conn_string) as conn:
        with conn.cursor() as cursor:
            if node_id.startswith('tt'):
                query = "SELECT tconst, originaltitle FROM titlebasics WHERE tconst = %s;"
            elif node_id.startswith('nm'):
                query = "SELECT nconst, primaryname FROM namebasics WHERE nconst = %s;"
            cursor.execute(query, (node_id,))
            result = cursor.fetchone()
    return node_id, result[1] if result else 'Inconnu'

# Utilisation de ThreadPoolExecutor pour récupérer les détails en parallèle tout en conservant l'ordre
with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
    # executor.map prend en charge le maintien de l'ordre des résultats en fonction de l'ordre des appels
    results = list(executor.map(fetch_node_details, path))
    
    # Affichage des résultats dans l'ordre
    for node_id, details in results:
        print(f"{node_id}: {details}")