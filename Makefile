build:
	docker build -t freeinvoice-ws-client .

dev:
	docker run -it --volume ${PWD}:/var/www/html --name freeinvoice-ws-client --rm freeinvoice-ws-client bash