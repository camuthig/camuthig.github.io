.PHONY: help

.DEFAULT_GOAL := help

init: ## Set up the project
	git submodule update --init themes/minimal

post: ## Create a new post. Pass the name as "name=x"
	hugo new posts/${name}

preview: ## Serve the blog, including future and draft posts
	hugo server  --buildFuture --buildDrafts

help: ## Show this message
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
