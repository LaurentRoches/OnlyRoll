# Documentation API

La documentation Swagger/OpenAPI est désormais **auto-générée** à partir des annotations PHP dans les controllers.

## Accès

- **Swagger UI** : `GET /api/doc`
- **Spécification JSON** : `GET /api/doc.json`

## Fonctionnement

Les annotations `OpenApi\Attributes` (attributs PHP 8) sont directement dans les fichiers controllers (`backend/src/Controller/`). NelmioApiDocBundle les lit automatiquement pour générer la documentation.

## Anciens fichiers

Les fichiers YAML manuels (`swagger_*.yaml`) ont été supprimés car ils sont remplacés par la documentation auto-générée.
