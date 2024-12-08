PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public
lint:
	./vendor/bin/phpcs -- -v --standard=PSR12 src templates public/index.php
