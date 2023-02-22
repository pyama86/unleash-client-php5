dev:
	docker rm -f unleash-dev | true
	docker build --platform linux/amd64 -t unleash-dev .
	docker run -d --entrypoint "" --name unleash-dev --platform linux/amd64 -v `pwd`:/opt/unleash -w /opt/unleash -it unleash-dev /sbin/init
	docker exec -it unleash-dev /bin/bash
