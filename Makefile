SHELL := /bin/sh

PLUGIN_FILE := $(shell awk '/^[[:space:]]*\* Plugin Name:/ { print FILENAME; exit }' ./*.php)
VERSION := $(shell awk -F': *' '/^[[:space:]]*\* Version:/ { print $$2; exit }' $(PLUGIN_FILE))
PLUGIN_SLUG := dlit-math-captcha
ZIP_NAME := $(PLUGIN_SLUG)-v$(VERSION).zip
bump_ACTION := $(filter patch minor major sync,$(MAKECMDGOALS))

.PHONY: zip bump patch minor major sync

zip:
	@tmp=$$(mktemp -d) && \
	rsync -a \
		--exclude='.*' \
		--exclude='.git/' \
		--exclude='Makefile' \
		--exclude='*.zip' \
		--exclude='README.md' \
		. "$$tmp/$(PLUGIN_SLUG)/" && \
	cd "$$tmp" && zip -r "$(CURDIR)/$(ZIP_NAME)" "$(PLUGIN_SLUG)" && \
	rm -rf "$$tmp" && \
	echo "Created $(ZIP_NAME)"

bump:
	@action="$(bump_ACTION)"; \
	if [ -z "$$action" ]; then \
		echo "Usage: make bump [patch|minor|major|sync]"; \
		exit 1; \
	fi; \
	current_version="$$(awk -F': *' '/^[[:space:]]*\* Version:/ { print $$2; exit }' $(PLUGIN_FILE))"; \
	new_version="$$current_version"; \
	case "$$action" in \
		patch|minor|major) \
			new_version="$$(CURRENT_VERSION="$$current_version" BUMP_TYPE="$$action" awk 'BEGIN { \
				split(ENVIRON["CURRENT_VERSION"], parts, "."); \
				major = parts[1] + 0; \
				minor = parts[2] + 0; \
				patch = parts[3] + 0; \
				bump = ENVIRON["BUMP_TYPE"]; \
				if (bump == "major") { \
					major += 1; minor = 0; patch = 0; \
				} else if (bump == "minor") { \
					minor += 1; patch = 0; \
				} else { \
					patch += 1; \
				} \
				printf "%d.%d.%d", major, minor, patch; \
			}')"; \
			;; \
		sync) \
			;; \
		*) \
			echo "Usage: make bump [patch|minor|major|sync]"; \
			exit 1; \
			;; \
	esac; \
	SYNC_VERSION="$$new_version" perl -0pi -e 's/^(\s*\* Version:\s*).*$$/$$1$$ENV{SYNC_VERSION}/m; s/(define\(\s*'\''DLIT_MATH_CAPTCHA_VERSION'\'',\s*'\'').*?('\''\s*\);)/$$1$$ENV{SYNC_VERSION}$$2/m' $(PLUGIN_FILE); \
	SYNC_VERSION="$$new_version" perl -0pi -e 's/^(Stable tag:\s*).*$$/$$1$$ENV{SYNC_VERSION}/m' readme.txt; \
	if [ "$$action" = "sync" ]; then \
		echo "Synced version to $$new_version"; \
	else \
		echo "Bumped version: $$current_version -> $$new_version"; \
	fi

patch minor major sync:
	@:
