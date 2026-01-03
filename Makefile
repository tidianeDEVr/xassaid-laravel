# Variables
SSH_KEY = /Users/tidiane/Documents/xassaid/access/id_ed25519
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
		--exclude "storage/logs/*" \
		--exclude "storage/framework/cache/*" \
		--exclude "storage/framework/sessions/*" \
		--exclude "storage/framework/views/*" \
		--exclude "storage/indexes" \
		--exclude "bootstrap/cache" \
		--exclude ".env" \
		./ $(REMOTE_USER)@$(REMOTE_HOST):$(REMOTE_PATH)

deploy:
	ssh -i $(SSH_KEY) $(REMOTE_USER)@$(REMOTE_HOST) "\
		cd $(REMOTE_PATH) && \
		docker compose down && \
		docker compose up -d --build \
	"

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
