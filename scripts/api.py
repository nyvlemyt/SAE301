from flask import Flask, request, jsonify
import json
import networkx as nx
import pickle

app = Flask(__name__)

# Charger votre graphe ici
graph_path = '/var/www/html/GitHub/SAE301/scripts/graph.pickle'

with open(graph_path, 'rb') as f:
    G_loaded = pickle.load(f)

def find_shortest_path(graph, source, target):
    try:
        path = nx.shortest_path(graph, source=source, target=target)
        return path
    except nx.NetworkXNoPath:
        return {"error": "Aucun chemin trouvé"}
    except Exception as e:
        return {"error": str(e)}

@app.route('/shortest_path', methods=['GET'])
def get_shortest_path():
    source = request.args.get('source')
    target = request.args.get('target')
    
    if not source or not target:
        return jsonify({"error": "Les paramètres 'source' et 'target' sont requis"}), 400
    
    path = find_shortest_path(G_loaded, source, target)
    return jsonify({"path": path})

if __name__ == '__main__':
    app.run(debug=True)
