# Variables
# SSH_KEY est surchargeable : `make SSH_KEY=~/.ssh/id_ed25519 deploy`, ou
# `export SSH_KEY=...` une fois pour la session. Le chemin ci-dessous est
# celui du poste d'origine et n'existe pas partout.
SSH_KEY ?= /Users/tidiane/Documents/xassaid/access/id_ed25519
REMOTE_USER = tidiane
REMOTE_HOST = 141.94.115.116
REMOTE_PATH = /home/$(REMOTE_USER)/SERVER/xassaid/back

all: rsync deploy

rsync:
	rsync -rltvz --delete --omit-dir-times \
		-e "ssh -i $(SSH_KEY)" \
		--exclude "vendor" \
		--exclude "node_modules" \
		--exclude ".git" \
		--exclude ".DS_Store" \
		--exclude "storage" \
		--exclude "bootstrap/cache" \
		--exclude ".env" \
		./ $(REMOTE_USER)@$(REMOTE_HOST):$(REMOTE_PATH)

deploy:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose down && \
		docker compose up -d --build \
	"

# Serveur de dev local.
# PHP 8.5 imprime des « Deprecated » (PDO::MYSQL_ATTR_SSL_CA) pendant le
# chargement de la config, donc AVANT que Laravel n'installe son gestionnaire
# d'erreurs : ils se retrouvent en tête de chaque réponse et cassent le parsing
# JSON côté mobile. `php artisan serve` ne transmet pas les options -d au
# serveur intégré qu'il lance, on le démarre donc nous-mêmes.
# La prod (Docker, PHP 8.2) n'est pas concernée.
SERVE_HOST ?= 127.0.0.1
SERVE_PORT ?= 8000

# Worker de queue local : transcodage des vidéos uploadées.
# En prod c'est le container `worker` du docker-compose ; en dev il faut le
# lancer dans un second terminal, sinon les vidéos restent en `processing`.
worker:
	php artisan queue:work --tries=2 --timeout=1200 --sleep=3

serve:
	cd public && php \
		-d display_errors=0 \
		-d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" \
		-d upload_max_filesize=512M \
		-d post_max_size=520M \
		-d memory_limit=512M \
		-S $(SERVE_HOST):$(SERVE_PORT) \
		../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php

# Commandes utiles supplémentaires
logs:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose logs -f \
	"

shell:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec web bash \
	"

migrate:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec web php artisan migrate \
	"

# À jouer après toute modification du .env du serveur : sans ça, un config
# cache périmé continue de servir les anciennes valeurs (DB_*, notamment).
clear:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec -T web php artisan config:clear && \
		docker compose exec -T web php artisan route:clear \
	"

cache:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec web php artisan config:cache && \
		docker compose exec web php artisan route:cache && \
		docker compose exec web php artisan view:cache \
	"

status:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose ps \
	"

sync-db:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "rm -f $(REMOTE_PATH)/database/database.sqlite"
	scp -i $(SSH_KEY) database/database.sqlite $(REMOTE_USER)@$(REMOTE_HOST):$(REMOTE_PATH)/database/
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "chmod 666 $(REMOTE_PATH)/database/database.sqlite"
	@echo "Base de données synchronisée avec succès"

# `scp database.sqlite` seul est incomplet quand la base tourne en WAL : les
# transactions récentes vivent dans database.sqlite-wal, qui n'est pas copié.
# On demande donc au container un snapshot cohérent (VACUUM INTO) d'abord.
# Les -wal/-shm locaux sont supprimés avant l'écrasement : un WAL orphelin à
# côté d'une base remplacée est au mieux ignoré, au pire une corruption.
pull-db:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec -T web php artisan db:snapshot \
	"
	rm -f database/database.sqlite-wal database/database.sqlite-shm
	scp -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST):$(REMOTE_PATH)/database/snapshot.sqlite database/database.sqlite
	@echo "Base de données téléchargée avec succès"

# ---------------------------------------------------------------------------
# Bascule SQLite -> MySQL (cPanel). À jouer une seule fois, dans cet ordre,
# après avoir mis les DB_* MySQL dans le .env DU SERVEUR.
# ---------------------------------------------------------------------------

# 1. Crée le schéma sur MySQL (ne touche pas aux données SQLite).
migrate-mysql:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec -T web php artisan migrate --database=mysql --force \
	"

# 2. Recopie les lignes SQLite -> MySQL (--fresh vide les tables cibles).
to-mysql:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec -T web php artisan db:to-mysql --fresh \
	"

# Simulation : compte les lignes sans rien écrire.
to-mysql-dry:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose exec -T web php artisan db:to-mysql --dry-run \
	"
